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
Built from source: https://github.com/TechbyshOrg/wordpress-ai-assistant
*/

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'WACDMG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WACDMG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WACDMG_PLUGIN_VERSION', '1.0.0' );
define( 'WACDMG_API_NAMESPACE', 'wacdmg/v1' );

require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin.php';
require_once WACDMG_PLUGIN_DIR . 'includes/class-wacdmg-admin-api.php';

// Initialize classes.
add_action( 'plugins_loaded', function() {
    new WACDMG_Admin_API();
    new WACDMG_Admin();
} );
