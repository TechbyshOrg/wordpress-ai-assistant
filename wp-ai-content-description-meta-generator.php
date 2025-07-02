<?php
/**
 * Plugin Name: WP AI Content Description Meta Generator
 * Plugin URI: https://yourwebsite.com/wp-ai-content-description-meta-generator
 * Description: Automatically generate SEO-friendly content descriptions and meta tags using AI for posts, pages, or custom post types.
 * Version: 1.0.0
 * Author: Your Name or Company
 * Author URI: https://yourwebsite.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-ai-content-description-meta-generator
 * Domain Path: /languages
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


