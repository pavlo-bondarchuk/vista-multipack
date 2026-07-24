<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Feed {

	const GOOGLE_NAMESPACE = 'http://base.google.com/ns/1.0';

	/**
	 * Register the documented offer extension hook exposed by the feed plugin.
	 *
	 * @return void
	 */
	public static function init() {
		add_filter( 'xfgmc_f_after_simple_offer', array( __CLASS__, 'append_pack_offer' ), 20, 3 );
	}

	/**
	 * Clone the standard offer and turn the clone into a Merchant-valid pack.
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

		$pack_item = $items->item( 0 )->cloneNode( true );
		if ( ! $pack_item ) {
			return $result_xml;
		}

		$size               = (int) $config['size'];
		$pack_display_price = Vista_Multipack_Product::get_display_price( $product, $config );
		$base_id            = self::get_google_value( $pack_item, 'id' );
		$base_title         = self::get_google_value( $pack_item, 'title' );
		$base_description   = self::get_google_value( $pack_item, 'description' );

		self::set_google_value( $document, $pack_item, 'id', $base_id . '-multipack-' . $size );
		self::set_google_value(
			$document,
			$pack_item,
			'title',
			sprintf(
				/* translators: 1: product title, 2: units in pack. */
				__( '%1$s — Set of %2$d', 'vista-multipack' ),
				$base_title,
				$size
			)
		);
		self::set_google_value(
			$document,
			$pack_item,
			'description',
			trim(
				$base_description . ' ' .
				sprintf(
					/* translators: %d: units in pack. */
					_n( 'This offer contains %d identical unit.', 'This offer contains %d identical units.', $size, 'vista-multipack' ),
					$size
				)
			)
		);
		self::set_google_value( $document, $pack_item, 'link', Vista_Multipack_Product::get_pack_url( $product ) );
		self::set_google_value(
			$document,
			$pack_item,
			'price',
			wc_format_decimal( $pack_display_price, wc_get_price_decimals() ) . ' ' . get_woocommerce_currency()
		);
		self::remove_google_value( $pack_item, 'sale_price' );
		self::remove_google_value( $pack_item, 'sale_price_effective_date' );
		self::set_google_value( $document, $pack_item, 'multipack', (string) $size );

		if ( $product->managing_stock() ) {
			$stock_quantity = (int) $product->get_stock_quantity();
			self::set_google_value(
				$document,
				$pack_item,
				'availability',
				$stock_quantity >= $size ? 'in_stock' : 'out_of_stock'
			);

			if ( self::has_google_value( $pack_item, 'quantity' ) ) {
				self::set_google_value(
					$document,
					$pack_item,
					'quantity',
					(string) max( 0, (int) floor( $stock_quantity / $size ) )
				);
			}
		}

		$pack_xml = $document->saveXML( $pack_item );
		return $pack_xml ? $result_xml . $pack_xml : $result_xml;
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
	 * Whether an item contains a namespaced element.
	 *
	 * @param DOMElement $item      Item.
	 * @param string     $localname Local tag name.
	 * @return bool
	 */
	private static function has_google_value( $item, $localname ) {
		return $item->getElementsByTagNameNS( self::GOOGLE_NAMESPACE, $localname )->length > 0;
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
