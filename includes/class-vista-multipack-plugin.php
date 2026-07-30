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

		self::maybe_upgrade();

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

		self::remove_legacy_feed_multipack_meta();
		update_option( 'vista_multipack_version', VISTA_MULTIPACK_VERSION, false );
	}

	/**
	 * Apply one-time compatibility migrations for an existing installation.
	 *
	 * @return void
	 */
	private static function maybe_upgrade() {
		$installed_version = (string) get_option( 'vista_multipack_version', '0.0.0' );

		if ( version_compare( $installed_version, '1.1.0', '<' ) ) {
			self::remove_legacy_feed_multipack_meta();
		}

		if ( VISTA_MULTIPACK_VERSION !== $installed_version ) {
			update_option( 'vista_multipack_version', VISTA_MULTIPACK_VERSION, false );
		}
	}

	/**
	 * Prevent the feed plugin from independently exporting the old multipack.
	 *
	 * The configured set size remains in this plugin's own metadata. Only the
	 * third-party feed plugin's legacy compatibility field is removed.
	 *
	 * @return void
	 */
	private static function remove_legacy_feed_multipack_meta() {
		$product_ids = get_posts(
			array(
				'post_type'              => 'product',
				'post_status'            => array( 'publish', 'draft', 'private' ),
				'posts_per_page'         => -1,
				'fields'                 => 'ids',
				'meta_key'               => Vista_Multipack_Product::META_ENABLED,
				'meta_value'             => 'yes',
				'no_found_rows'          => true,
				'suppress_filters'       => true,
				'update_post_meta_cache' => false,
				'update_post_term_cache' => false,
			)
		);

		foreach ( $product_ids as $product_id ) {
			delete_post_meta( $product_id, '_xfgmc_multipack' );
		}
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
