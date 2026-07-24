<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Admin {

	/**
	 * Register product editor hooks.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'woocommerce_product_options_pricing', array( __CLASS__, 'render_fields' ) );
		add_action( 'woocommerce_admin_process_product_object', array( __CLASS__, 'save_fields' ) );
		add_action( 'admin_notices', array( __CLASS__, 'feed_compatibility_notice' ) );
	}

	/**
	 * Render pack fields beside WooCommerce pricing.
	 *
	 * @return void
	 */
	public static function render_fields() {
		echo '<div class="options_group show_if_simple">';

		woocommerce_wp_checkbox(
			array(
				'id'          => Vista_Multipack_Product::META_ENABLED,
				'label'       => __( 'Enable set purchase', 'vista-multipack' ),
				'description' => __( 'Shows a separate set price and set purchase button.', 'vista-multipack' ),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'                => Vista_Multipack_Product::META_SIZE,
				'label'             => __( 'Units per set', 'vista-multipack' ),
				'description'       => __( 'The real WooCommerce quantity added for one set.', 'vista-multipack' ),
				'type'              => 'number',
				'desc_tip'          => true,
				'custom_attributes' => array(
					'min'  => '2',
					'step' => '1',
				),
			)
		);

		woocommerce_wp_text_input(
			array(
				'id'          => Vista_Multipack_Product::META_PRICE,
				'label'       => __( 'Set price', 'vista-multipack' ) . ' (' . get_woocommerce_currency_symbol() . ')',
				'description' => __( 'Total price for one complete set. The set offer remains hidden until this price is set.', 'vista-multipack' ),
				'type'        => 'text',
				'data_type'   => 'price',
				'desc_tip'    => true,
			)
		);

		echo '</div>';
	}

	/**
	 * Save and sanitize product pack settings.
	 *
	 * @param WC_Product $product Product being saved.
	 * @return void
	 */
	public static function save_fields( $product ) {
		if ( ! $product->is_type( 'simple' ) ) {
			return;
		}

		$enabled = isset( $_POST[ Vista_Multipack_Product::META_ENABLED ] ) ? 'yes' : 'no';
		$size    = isset( $_POST[ Vista_Multipack_Product::META_SIZE ] )
			? absint( wp_unslash( $_POST[ Vista_Multipack_Product::META_SIZE ] ) )
			: 0;
		$price   = isset( $_POST[ Vista_Multipack_Product::META_PRICE ] )
			? wc_format_decimal( wp_unslash( $_POST[ Vista_Multipack_Product::META_PRICE ] ) )
			: '';

		$product->update_meta_data( Vista_Multipack_Product::META_ENABLED, $enabled );
		$product->update_meta_data( Vista_Multipack_Product::META_SIZE, $size >= 2 ? $size : '' );
		$product->update_meta_data( Vista_Multipack_Product::META_PRICE, (float) $price > 0 ? $price : '' );

		if ( 'yes' === $enabled && $size >= 2 ) {
			$product->update_meta_data( '_xfgmc_multipack', $size );
			$_POST['_xfgmc_multipack'] = (string) $size;
		} else {
			$product->delete_meta_data( '_xfgmc_multipack' );
			$_POST['_xfgmc_multipack'] = '';
		}
	}

	/**
	 * Warn when the installed feed integration has not been verified.
	 *
	 * @return void
	 */
	public static function feed_compatibility_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! defined( 'XFGMC_PLUGIN_VERSION' ) ) {
			return;
		}

		if ( '4.3.0' === XFGMC_PLUGIN_VERSION ) {
			return;
		}

		echo '<div class="notice notice-warning"><p>';
		echo esc_html(
			sprintf(
				/* translators: %s: installed XML feed plugin version. */
				__( 'Vista Multipack feed integration was verified with XML for Google Merchant Center 4.3.0. Installed version: %s. Regenerate and validate the feed after updates.', 'vista-multipack' ),
				XFGMC_PLUGIN_VERSION
			)
		);
		echo '</p></div>';
	}
}
