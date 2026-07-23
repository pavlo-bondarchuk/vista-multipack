<?php

defined( 'ABSPATH' ) || exit;

final class Vista_Multipack_Plugin {

	/**
	 * Start the plugin after WooCommerce is available.
	 *
	 * @return void
	 */
	public static function init() {
		load_plugin_textdomain(
			'vista-multipack',
			false,
			dirname( plugin_basename( VISTA_MULTIPACK_FILE ) ) . '/languages'
		);

		if ( ! class_exists( 'WooCommerce' ) ) {
			add_action( 'admin_notices', array( __CLASS__, 'woocommerce_missing_notice' ) );
			return;
		}

		Vista_Multipack_Admin::init();
		Vista_Multipack_Frontend::init();
		Vista_Multipack_Cart::init();
		Vista_Multipack_Feed::init();
	}

	/**
	 * Migrate the feed plugin's existing multipack values into this plugin.
	 *
	 * A pack price is deliberately not guessed. A migrated product remains
	 * unavailable as a pack until its pack price is entered by an administrator.
	 *
	 * @return void
	 */
	public static function activate() {
		if ( ! function_exists( 'wc_get_products' ) ) {
			return;
		}

		$product_ids = wc_get_products(
			array(
				'limit'  => -1,
				'return' => 'ids',
				'type'   => 'simple',
				'status' => array( 'publish', 'draft', 'private' ),
			)
		);

		foreach ( $product_ids as $product_id ) {
			$existing_size = absint( get_post_meta( $product_id, '_xfgmc_multipack', true ) );

			if ( $existing_size < 2 || metadata_exists( 'post', $product_id, Vista_Multipack_Product::META_SIZE ) ) {
				continue;
			}

			update_post_meta( $product_id, Vista_Multipack_Product::META_ENABLED, 'yes' );
			update_post_meta( $product_id, Vista_Multipack_Product::META_SIZE, $existing_size );
		}

		update_option( 'vista_multipack_version', VISTA_MULTIPACK_VERSION, false );
	}

	/**
	 * Show a dependency notice without failing the site.
	 *
	 * @return void
	 */
	public static function woocommerce_missing_notice() {
		echo '<div class="notice notice-error"><p>';
		echo esc_html__( 'Vista Multipack requires WooCommerce to be active.', 'vista-multipack' );
		echo '</p></div>';
	}
}
