<?php
<?php
/*
Plugin Name: Smart AI Assistant for Content Creation
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


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly. 
}

// Define plugin constants.
define( 'WACDMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WACDMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WACDMG_PLUGIN_VERSION', '1.0.0' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );
// // Load the plugin text domain for translations.
// function wacdmg_load_textdomain() {
//     load_plugin_textdomain( 'wp-ai-content-description-meta-generator', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
// }
// add_action( 'plugins_loaded', 'wacdmg_load_textdomain' );
// Include the admin and API classes.
require_once plugin_dir_path(__FILE__) . 'includes/class-wacdmg-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-wacdmg-admin-api.php';


new WACDMG_Admin_API();
new WACDMG_Admin();


