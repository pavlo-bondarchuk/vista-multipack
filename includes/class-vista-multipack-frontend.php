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

		$pack_display_price = Vista_Multipack_Product::get_display_price( $product, $config );
		$is_selected        = isset( $_GET['vista_purchase'] ) && 'pack' === sanitize_key( wp_unslash( $_GET['vista_purchase'] ) );

		printf(
			'<div id="vista-multipack" class="vista-multipack-price%s">',
			$is_selected ? ' is-selected' : ''
		);

		self::render_pack_form( $product, $config, $pack_display_price );

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
		echo '<input type="hidden" name="vista_purchase_mode" value="unit">';
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
	 * @param float      $pack_display_price Pack price including display tax.
	 * @return void
	 */
	private static function render_pack_form( $product, $config, $pack_display_price ) {
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
					/* translators: 1: units in the set, 2: formatted total set price. */
					__( 'Set (%1$d units) — %2$s', 'vista-multipack' ),
					$config['size'],
					wp_strip_all_tags( wc_price( $pack_display_price ) )
				)
			)
		);
		echo '</form>';
	}
}
