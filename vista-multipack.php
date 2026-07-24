<?php
/**
 * Plugin Name: Vista Multipack
 * Description: Adds fixed-size set purchasing to WooCommerce products and compatible multipack offers to XML for Google Merchant Center.
 * Version: 1.0.2
 * Requires at least: 6.5
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * Author: Vista Health
 * Text Domain: vista-multipack
 * Domain Path: /languages
 */

defined( 'ABSPATH' ) || exit;

define( 'VISTA_MULTIPACK_VERSION', '1.0.2' );
define( 'VISTA_MULTIPACK_FILE', __FILE__ );
define( 'VISTA_MULTIPACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'VISTA_MULTIPACK_URL', plugin_dir_url( __FILE__ ) );

require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-product.php';
require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-admin.php';
require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-frontend.php';
require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-cart.php';
require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-feed.php';
require_once VISTA_MULTIPACK_PATH . 'includes/class-vista-multipack-plugin.php';

register_activation_hook( __FILE__, array( 'Vista_Multipack_Plugin', 'activate' ) );

add_action( 'plugins_loaded', array( 'Vista_Multipack_Plugin', 'init' ), 20 );
