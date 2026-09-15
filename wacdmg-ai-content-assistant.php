<?php
/**
 * Plugin Name: AI Content & Meta Assistant
 * Plugin URI: https://wordpress.org/plugins/smart-ai-assistant-content/
 * Description: AI writer for WordPress and WooCommerce. Generate product descriptions, SEO meta, blog content, and images with ChatGPT, Gemini, or Claude.
 * Version: 2.1.0
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
define( 'WACDMG_PLUGIN_VERSION', '2.1.0' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );

// Core classes.
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin-api.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-seo.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-image-ai.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-field-ui.php';

// Initialize classes.
add_action( 'plugins_loaded', function() {
    new WACDMG_Admin_API();
    new WACDMG_Admin();
    new WACDMG_Field_UI();
} );
