<?php
/*
Plugin Name: AI Assistant for WooCommerce and WordPress
Plugin URI: https://wordpress.org/plugins/ai-assistant-woocommerce-wordpress/
Description: Generate AI-powered product descriptions, meta tags, and more directly within WP and WooCommerce.
Version: 1.0.0
Author: Techbysh
Author URI: https://techbysh.com
Text Domain: wacdmg-ai-assistant-woocommerce-wordpress
Domain Path: /languages
License: GPLv2 or later
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


