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
		add_action( 'woocommerce_after_add_to_cart_button', array( __CLASS__, 'render_pack_button' ) );
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
	 * Show the pack total and per-unit comparison next to the regular price.
	 *
	 * @return void
	 */
	public static function render_pack_price() {
		global $product;

		$config = Vista_Multipack_Product::get_config( $product );
		if ( ! $config ) {
			return;
		}

		$pack_display_price = Vista_Multipack_Product::get_display_price( $product, $config );
		$unit_pack_price    = $pack_display_price / $config['size'];
		$is_selected        = isset( $_GET['vista_purchase'] ) && 'pack' === sanitize_key( wp_unslash( $_GET['vista_purchase'] ) );

		printf(
			'<div id="vista-multipack" class="vista-multipack-price%s"><span class="vista-multipack-price__label">%s</span><strong class="vista-multipack-price__total">%s</strong><span class="vista-multipack-price__unit">%s</span></div>',
			$is_selected ? ' is-selected' : '',
			esc_html(
				sprintf(
					/* translators: %d: number of units in a pack. */
					_n( 'Pack of %d unit', 'Pack of %d units', $config['size'], 'vista-multipack' ),
					$config['size']
				)
			),
			wp_kses_post( wc_price( $pack_display_price ) ),
			esc_html(
				sprintf(
					/* translators: %s: formatted price per unit. */
					__( '%s per unit in a pack', 'vista-multipack' ),
					wp_strip_all_tags( wc_price( $unit_pack_price ) )
				)
			)
		);
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
		echo '<input type="hidden" name="vista_purchase_mode" value="unit">';
	}

	/**
	 * Render the second submit button.
	 *
	 * @return void
	 */
	public static function render_pack_button() {
		global $product;

		$config = Vista_Multipack_Product::get_config( $product );
		if ( ! $config || ! $product->is_purchasable() || ! $product->is_in_stock() ) {
			return;
		}

		$pack_display_price = Vista_Multipack_Product::get_display_price( $product, $config );

		printf(
			'<button type="submit" name="vista_purchase_mode" value="pack" class="button alt vista-multipack-button">%s</button>',
			esc_html(
				sprintf(
					/* translators: 1: units in a pack, 2: formatted pack price. */
					__( 'Order pack (%1$d units) — %2$s', 'vista-multipack' ),
					$config['size'],
					wp_strip_all_tags( wc_price( $pack_display_price ) )
				)
			)
		);
	}
}
