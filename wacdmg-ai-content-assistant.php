<?php
/**
 * Plugin Name: AI Content & Meta Assistant
 * Plugin URI: https://wordpress.org/plugins/wacdmg-ai-content-assistant/
 * Description: AI writer for WordPress and WooCommerce. Generate product descriptions, SEO meta, blog content, and images with ChatGPT, Gemini, or Claude.
 * Version: 2.1.1
 * Requires at least: 6.0
 * Tested up to: 7.1
 * Requires PHP: 7.4
 * Author: Techbysh
 * Author URI: https://techbysh.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wacdmg-ai-content-assistant
 * Domain Path: /languages
 * Built from source: https://github.com/TechbyshOrg/wordpress-ai-assistant
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WACDMG_PLUGIN_FILE', __FILE__ );
define( 'WACDMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WACDMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WACDMG_PLUGIN_VERSION', '2.1.1' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );

// Core classes.
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin-api.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-seo.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-image-ai.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-field-ui.php';

/**
 * On deactivation, cancel queued auto-SEO jobs so they do not run while the plugin is inactive.
 * (Data cleanup on delete lives in uninstall.php.)
 */
function wacdmg_deactivate() {
    if ( function_exists( 'as_unschedule_all_actions' ) ) {
        as_unschedule_all_actions( 'wacdmg_auto_seo_job', null, 'wacdmg' );
    }
    wp_unschedule_hook( 'wacdmg_auto_seo_job' );
}
register_deactivation_hook( __FILE__, 'wacdmg_deactivate' );

/**
 * Declare WooCommerce feature compatibility.
 *
 * The plugin only edits products, product terms, and media. It never reads or writes orders,
 * the cart, or checkout, so it is compatible with High-Performance Order Storage (HPOS) and the
 * Cart & Checkout blocks. The new block-based product editor is not declared (not yet verified).
 */
add_action( 'before_woocommerce_init', function() {
    if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WACDMG_PLUGIN_FILE, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WACDMG_PLUGIN_FILE, true );
    }
} );

// Initialize classes.
add_action( 'plugins_loaded', function() {
    new WACDMG_Admin_API();
    new WACDMG_Admin();
    new WACDMG_Field_UI();
} );
