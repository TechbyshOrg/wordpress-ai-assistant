<?php
/*
Plugin Name: AI Content & Meta Assistant
Plugin URI: https://wordpress.org/plugins/smart-ai-assistant-content/
Description: Smart AI content generator for WordPress and WooCommerce — create product descriptions, blog posts, SEO meta, and AI images with a click. Supports OpenAI, Google Gemini, Anthropic Claude, Mistral, and Groq.
 * Version: 2.0.0
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
define( 'WACDMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WACDMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WACDMG_PLUGIN_VERSION', '2.0.0' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );

// Core classes.
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin-api.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-seo.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-image-ai.php';

// Initialize classes.
add_action( 'plugins_loaded', function() {
    new WACDMG_Admin_API();
    new WACDMG_Admin();
} );
