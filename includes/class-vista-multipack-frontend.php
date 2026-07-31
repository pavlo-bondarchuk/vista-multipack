<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Frontend {

	/**
	 * Register storefront hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_styles' ) );
		add_action( 'woocommerce_single_product_summary', array( __CLASS__, 'render_pack_price' ), 11 );
		add_action( 'woocommerce_before_add_to_cart_button', array( __CLASS__, 'render_form_fields' ) );
		add_filter( 'woocommerce_get_price_html', array( __CLASS__, 'render_selected_unit_price' ), 50, 2 );
		add_filter( 'woocommerce_structured_data_product_offer', array( __CLASS__, 'filter_selected_unit_offer' ), 50, 2 );
	}

	/**
	 * Load the small, theme-independent presentation layer.
	 *
	 * @return void
	 */
	public static function enqueue_styles() {
		if ( is_product() || is_cart() || is_checkout() ) {
			wp_enqueue_style(
				'vista-multipack',
				VISTA_MULTIPACK_URL . 'assets/css/frontend.css',
				array(),
				VISTA_MULTIPACK_VERSION
			);
		}
	}

	/**
	 * Show a compact set purchase option next to the regular price.
	 *
	 * @return void
	 */
	public static function render_pack_price() {
		global $product;

		$config = Vista_Multipack_Product::get_config( $product );
		if ( ! $config ) {
			return;
		}

		$unit_display_price = Vista_Multipack_Product::get_unit_display_price( $product, $config );
		$is_pack_selected   = self::is_selected_purchase( 'pack' );
		$is_unit_selected   = self::is_selected_purchase( 'set-unit' );

		printf(
			'<div id="vista-multipack" class="vista-multipack-price%s">',
			( $is_pack_selected || $is_unit_selected ) ? ' is-selected' : ''
		);

		if ( $is_unit_selected ) {
			self::render_unit_form(
				$product,
				Vista_Multipack_Product::get_unit_display_price( $product, $config )
			);
		} else {
			self::render_pack_form( $product, $config, $unit_display_price );
		}

		echo '</div>';
	}

	/**
	 * Provide a default unit mode and a product ID for either submit button.
	 *
	 * @return void
	 */
	public static function render_form_fields() {
		global $product;

		if ( ! Vista_Multipack_Product::get_config( $product ) ) {
			return;
		}

		printf( '<input type="hidden" name="add-to-cart" value="%d">', absint( $product->get_id() ) );
		printf(
			'<input type="hidden" name="vista_purchase_mode" value="%s">',
			self::is_selected_purchase( 'set-unit' ) ? 'pack_unit' : 'unit'
		);
	}

	/**
	 * Replace the prominent product price on the selected feed landing URL.
	 *
	 * @param string     $price_html Existing price HTML.
	 * @param WC_Product $product    Product.
	 * @return string
	 */
	public static function render_selected_unit_price( $price_html, $product ) {
		if ( ! self::is_current_selected_product( $product ) ) {
			return $price_html;
		}

		$config = Vista_Multipack_Product::get_config( $product );
		if ( ! $config ) {
			return $price_html;
		}

		return wp_kses_post(
			sprintf(
				/* translators: %s: formatted price of one independently purchasable unit. */
				__( 'One unit at set price: %s', 'vista-multipack' ),
				wc_price( Vista_Multipack_Product::get_unit_display_price( $product, $config ) )
			)
		);
	}

	/**
	 * Make WooCommerce structured data match the selected one-unit feed offer.
	 *
	 * @param array      $offer   Existing Offer markup.
	 * @param WC_Product $product Product.
	 * @return array
	 */
	public static function filter_selected_unit_offer( $offer, $product ) {
		if ( ! self::is_current_selected_product( $product ) ) {
			return $offer;
		}

		$config = Vista_Multipack_Product::get_config( $product );
		if ( ! $config ) {
			return $offer;
		}

		$price    = wc_format_decimal(
			Vista_Multipack_Product::get_unit_display_price( $product, $config ),
			wc_get_price_decimals()
		);
		$currency = get_woocommerce_currency();

		unset( $offer['lowPrice'], $offer['highPrice'] );

		$offer['price']              = $price;
		$offer['priceCurrency']      = $currency;
		$offer['url']                = Vista_Multipack_Product::get_unit_url( $product );
		$offer['priceSpecification'] = array(
			array(
				'@type'                 => 'UnitPriceSpecification',
				'price'                 => $price,
				'priceCurrency'         => $currency,
				'valueAddedTaxIncluded' => 'incl' === get_option( 'woocommerce_tax_display_shop' ),
			),
		);

		return $offer;
	}

	/**
	 * Render a standalone pack form inside the pack price block.
	 *
	 * The standard WooCommerce cart form is rendered later in the product
	 * summary. Keeping a separate form here lets the button remain visually
	 * attached to the pack offer without relying on JavaScript.
	 *
	 * @param WC_Product $product            Product.
	 * @param array      $config             Pack configuration.
	 * @param float      $unit_display_price Unit price including display tax.
	 * @return void
	 */
	private static function render_pack_form( $product, $config, $unit_display_price ) {
		if ( ! $config || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$form_action = apply_filters( 'woocommerce_add_to_cart_form_action', $product->get_permalink() );

		printf(
			'<form class="vista-multipack-price__form" action="%s" method="post" enctype="multipart/form-data">',
			esc_url( $form_action )
		);
		printf( '<input type="hidden" name="add-to-cart" value="%d">', absint( $product->get_id() ) );
		echo '<input type="hidden" name="quantity" value="1">';
		echo '<input type="hidden" name="vista_purchase_mode" value="pack">';
		printf(
			'<button type="submit" class="vista-multipack-button">%s</button>',
			esc_html(
				sprintf(
					/* translators: 1: units in the set, 2: formatted price of one unit. */
					__( 'Price per unit in a set of %1$d units — %2$s', 'vista-multipack' ),
					$config['size'],
					wp_strip_all_tags( wc_price( $unit_display_price ) )
				)
			)
		);
		echo '</form>';
	}

	/**
	 * Render the independently purchasable unit selected by the feed URL.
	 *
	 * @param WC_Product $product           Product.
	 * @param float      $unit_display_price Unit price including display tax.
	 * @return void
	 */
	private static function render_unit_form( $product, $unit_display_price ) {
		if ( ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$form_action = apply_filters( 'woocommerce_add_to_cart_form_action', Vista_Multipack_Product::get_unit_url( $product ) );

		printf(
			'<form class="vista-multipack-price__form" action="%s" method="post" enctype="multipart/form-data">',
			esc_url( $form_action )
		);
		printf( '<input type="hidden" name="add-to-cart" value="%d">', absint( $product->get_id() ) );
		echo '<input type="hidden" name="quantity" value="1">';
		echo '<input type="hidden" name="vista_purchase_mode" value="pack_unit">';
		printf(
			'<button type="submit" class="vista-multipack-button">%s</button>',
			esc_html(
				sprintf(
					/* translators: %s: formatted price of one independently purchasable unit. */
					__( 'One unit at set price — %s', 'vista-multipack' ),
					wp_strip_all_tags( wc_price( $unit_display_price ) )
				)
			)
		);
		echo '</form>';
	}

	/**
	 * Whether the current URL selected a plugin purchase option.
	 *
	 * @param string $mode URL mode.
	 * @return bool
	 */
	private static function is_selected_purchase( $mode ) {
		return isset( $_GET['vista_purchase'] )
			&& $mode === sanitize_key( wp_unslash( $_GET['vista_purchase'] ) );
	}

	/**
	 * Whether the current product page selected the special one-unit offer.
	 *
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	private static function is_current_selected_product( $product ) {
		return is_product()
			&& self::is_selected_purchase( 'set-unit' )
			&& $product
			&& (int) get_queried_object_id() === (int) $product->get_id();
	}
}
