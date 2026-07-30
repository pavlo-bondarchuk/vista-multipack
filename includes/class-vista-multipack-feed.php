<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Feed {

	const GOOGLE_NAMESPACE = 'http://base.google.com/ns/1.0';
	const RETRY_HOOK       = 'vista_multipack_retry_feed_regeneration';

	/**
	 * Whether the current request changed a relevant product configuration.
	 *
	 * @var bool
	 */
	private static $regeneration_requested = false;

	/**
	 * Register the documented offer extension hook exposed by the feed plugin.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'xfgmc_f_after_simple_offer', array( __CLASS__, 'append_pack_offer' ), 20, 3 );
		add_action( 'shutdown', array( __CLASS__, 'start_requested_regeneration' ), 20 );
		add_action( self::RETRY_HOOK, array( __CLASS__, 'retry_feed_regeneration' ), 10, 1 );
	}

	/**
	 * Mark the configured feeds for regeneration after product persistence.
	 *
	 * @return void
	 */
	public static function request_regeneration() {
		self::$regeneration_requested = true;
	}

	/**
	 * Start the feed plugin's own asynchronous generation flow.
	 *
	 * WooCommerce persists the product before WordPress reaches shutdown, so
	 * the feed generator reads the new set metadata rather than the old values.
	 *
	 * @return void
	 */
	public static function start_requested_regeneration() {
		if ( ! self::$regeneration_requested || ! has_action( 'xfgmc_cron_start_feed_creation' ) ) {
			return;
		}

		self::$regeneration_requested = false;

		foreach ( self::get_feed_ids() as $feed_id ) {
			self::start_feed_regeneration( $feed_id, true );
		}
	}

	/**
	 * Retry once when a feed was already being assembled during product save.
	 *
	 * @param string $feed_id Feed ID.
	 * @return void
	 */
	public static function retry_feed_regeneration( $feed_id ) {
		self::start_feed_regeneration( (string) $feed_id, false );
	}

	/**
	 * Trigger the feed plugin's generation action.
	 *
	 * @param string $feed_id     Feed ID.
	 * @param bool   $allow_retry Whether one delayed retry may be scheduled.
	 * @return void
	 */
	private static function start_feed_regeneration( $feed_id, $allow_retry ) {
		if ( ! has_action( 'xfgmc_cron_start_feed_creation' ) ) {
			return;
		}

		$status = function_exists( 'common_option_get' )
			? (int) common_option_get( 'xfgmc_status_sborki', -1, $feed_id, 'xfgmc' )
			: -1;

		if ( -1 !== $status ) {
			if ( $allow_retry && ! wp_next_scheduled( self::RETRY_HOOK, array( $feed_id ) ) ) {
				wp_schedule_single_event( time() + 120, self::RETRY_HOOK, array( $feed_id ) );
			}
			return;
		}

		do_action( 'xfgmc_cron_start_feed_creation', $feed_id );
	}

	/**
	 * Return IDs of feeds configured by XML for Google Merchant Center.
	 *
	 * @return string[]
	 */
	private static function get_feed_ids() {
		$settings = function_exists( 'common_option_get' )
			? common_option_get( 'xfgmc_settings_arr', array() )
			: get_option( 'xfgmc_settings_arr', array() );
		if ( ! is_array( $settings ) ) {
			return array();
		}

		return array_values(
			array_filter(
				array_map( 'strval', array_keys( $settings ) ),
				static function ( $feed_id ) {
					return '' !== $feed_id;
				}
			)
		);
	}

	/**
	 * Clone the standard offer as one independently purchasable set-rate unit.
	 *
	 * @param string $result_xml Existing complete item XML.
	 * @param array  $data       Feed plugin offer context.
	 * @param int    $feed_id    Feed ID.
	 * @return string
	 */
	public static function append_pack_offer( $result_xml, $data, $feed_id ) {
		if ( empty( $result_xml ) || empty( $data['product'] ) || ! is_a( $data['product'], 'WC_Product' ) ) {
			return $result_xml;
		}

		if ( function_exists( 'common_option_get' ) ) {
			$rules = common_option_get( 'xfgmc_xml_rules', false, $feed_id, 'xfgmc' );
			if ( ! in_array( $rules, array( 'merchant_center', 'all_elements' ), true ) ) {
				return $result_xml;
			}
		}

		$product = $data['product'];
		$config  = Vista_Multipack_Product::get_config( $product );

		if ( ! $config || ! class_exists( 'DOMDocument' ) ) {
			return $result_xml;
		}

		$document                     = new DOMDocument( '1.0', 'UTF-8' );
		$document->preserveWhiteSpace = false;

		$previous_errors = libxml_use_internal_errors( true );
		$loaded          = $document->loadXML(
			'<vista-feed-root xmlns:g="' . self::GOOGLE_NAMESPACE . '">' . $result_xml . '</vista-feed-root>',
			LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING
		);
		libxml_clear_errors();
		libxml_use_internal_errors( $previous_errors );

		if ( ! $loaded ) {
			return $result_xml;
		}

		$items = $document->getElementsByTagName( 'item' );
		if ( 0 === $items->length ) {
			return $result_xml;
		}

		$base_item = $items->item( 0 );
		self::remove_google_value( $base_item, 'multipack' );

		$unit_item = $base_item->cloneNode( true );
		if ( ! $unit_item ) {
			return $result_xml;
		}

		$size               = (int) $config['size'];
		$unit_display_price = Vista_Multipack_Product::get_unit_display_price( $product, $config );
		$base_id            = self::get_google_value( $unit_item, 'id' );
		$base_title         = self::get_google_value( $unit_item, 'title' );
		$base_description   = self::get_google_value( $unit_item, 'description' );

		self::set_google_value( $document, $unit_item, 'id', $base_id . '-set-unit-' . $size );
		self::set_google_value(
			$document,
			$unit_item,
			'title',
			sprintf(
				/* translators: %s: product title. */
				__( '%s — One unit at set price', 'vista-multipack' ),
				$base_title
			)
		);
		self::set_google_value(
			$document,
			$unit_item,
			'description',
			trim(
				$base_description . ' ' .
				__( 'This offer contains one independently purchasable unit at the set-equivalent unit price.', 'vista-multipack' )
			)
		);
		self::set_google_value( $document, $unit_item, 'link', Vista_Multipack_Product::get_unit_url( $product ) );
		self::set_google_value(
			$document,
			$unit_item,
			'price',
			wc_format_decimal( $unit_display_price, wc_get_price_decimals() ) . ' ' . get_woocommerce_currency()
		);
		self::remove_google_value( $unit_item, 'sale_price' );
		self::remove_google_value( $unit_item, 'sale_price_effective_date' );
		self::remove_google_value( $unit_item, 'multipack' );

		$base_xml = $document->saveXML( $base_item );
		$unit_xml = $document->saveXML( $unit_item );

		return $base_xml && $unit_xml ? $base_xml . $unit_xml : $result_xml;
	}

	/**
	 * Read a Google namespace element.
	 *
	 * @param DOMElement $item      Item.
	 * @param string     $localname Local tag name.
	 * @return string
	 */
	private static function get_google_value( $item, $localname ) {
		$nodes = $item->getElementsByTagNameNS( self::GOOGLE_NAMESPACE, $localname );
		return $nodes->length ? $nodes->item( 0 )->textContent : '';
	}

	/**
	 * Set or append a Google namespace element.
	 *
	 * @param DOMDocument $document  XML document.
	 * @param DOMElement  $item      Item.
	 * @param string      $localname Local tag name.
	 * @param string      $value     Value.
	 * @return void
	 */
	private static function set_google_value( $document, $item, $localname, $value ) {
		$nodes = $item->getElementsByTagNameNS( self::GOOGLE_NAMESPACE, $localname );
		$node  = $nodes->length ? $nodes->item( 0 ) : null;

		if ( ! $node ) {
			$node = $document->createElementNS( self::GOOGLE_NAMESPACE, 'g:' . $localname );
			$item->appendChild( $node );
		}

		while ( $node->firstChild ) {
			$node->removeChild( $node->firstChild );
		}
		$node->appendChild( $document->createTextNode( (string) $value ) );
	}

	/**
	 * Remove all matching Google namespace elements.
	 *
	 * @param DOMElement $item      Item.
	 * @param string     $localname Local tag name.
	 * @return void
	 */
	private static function remove_google_value( $item, $localname ) {
		$nodes = $item->getElementsByTagNameNS( self::GOOGLE_NAMESPACE, $localname );

		for ( $index = $nodes->length - 1; $index >= 0; $index-- ) {
			$node = $nodes->item( $index );
			if ( $node && $node->parentNode ) {
				$node->parentNode->removeChild( $node );
			}
		}
	}
}
