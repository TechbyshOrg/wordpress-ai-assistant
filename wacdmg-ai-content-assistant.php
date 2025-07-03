<?php
/*
Plugin Name: AI Content & Meta Assistant
Plugin URI: https://wordpress.org/plugins/smart-ai-assistant-content/
Description: Smart AI content generator for WordPress and WooCommerce — create product descriptions, blog posts, and SEO meta with a click.
Version: 1.0.0
Author: Techbysh
Author URI: https://techbysh.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: wacdmg-ai-content-assistant
Domain Path: /languages
*/

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WACDMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WACDMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WACDMG_PLUGIN_VERSION', '1.0.0' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );

/**
 * Load plugin translations.
 *
 * As of WP 6.8 (and since 4.6 for .org-hosted plugins), manual loading
 * is usually unnecessary—but if you include your own .mo/.po or support
 * pre‑6.8 installs, defer this to init with a low priority.
 */
// function wacdmg_load_textdomain() {
//     load_plugin_textdomain(
//         'wacdmg‑ai‑content‑assistant',
//         false,
//         dirname( plugin_basename( __FILE__ ) ) . '/languages/'
//     );
// }
// add_action( 'init', 'wacdmg_load_textdomain', 5 );

require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin-api.php';

// Initialize classes.
add_action( 'plugins_loaded', function() {
    new WACDMG_Admin_API();
    new WACDMG_Admin();
} );
