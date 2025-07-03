<?php
/**
 * WACDMG Admin Class
 *
 * Handles admin-side functionality including menu setup, scripts/styles, and UI elements.
 *
 * @package WACDMG
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WACDMG_Admin {
    /**
     * Constructor to initialize hooks and actions.
     */
    public function __construct() {
        $this->init_hooks();
    }

    /**
     * Initialize hooks for admin functionality.
     */
    public function init_hooks() {
        
        add_action('edit_form_after_title', array($this, 'add_div_above_product_description'));
        // create a settings sub menu page on WooCommerce menu
        add_action( 'admin_menu', function() {
            add_menu_page(
                __( 'AI Assistant', 'wacdmg-ai-content-assistant' ),
                __( 'AI Assistant', 'wacdmg-ai-content-assistant' ),
                'manage_options',
                'wacdmg-settings',
                array( $this, 'wacdmg_render_settings_page' ),
                'data:image/svg+xml;base64,' . base64_encode(file_get_contents(WACDMG_PLUGIN_DIR . 'assets/images/main-icon.svg')), // Custom SVG icon
                56 // Position in the menu
            );
        });

        //enque admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_admin_block_scripts'));

    }

    /**
     * Enqueue admin scripts and styles.
     */
    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'wacdmg-admin-style', WACDMG_PLUGIN_URL . 'css/admin-style.css' );
        wp_enqueue_script( 'wacdmg-admin-script', WACDMG_PLUGIN_URL . 'assets/js/app.js', array( 'wp-i18n' ), WACDMG_PLUGIN_VERSION, true );
        $api_namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $api_base_url = rest_url($api_namespace);
        if (strpos($api_base_url, '?rest_route=') !== false) {
            $api_base_url = home_url("/wp-json/{$api_namespace}");
        }

        $data = array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'wacdmg_admin_nonce' ),
            'rest_nonce' => wp_create_nonce('wp_rest'),
            'apiBaseUrl' => esc_url_raw($api_base_url),
        );
        wp_localize_script( 'wacdmg-admin-script', 'wacdmgAdmin', $data);
    }

    /**
     * Enqueue scripts for the paragraph block extension.
     *
     * This function is called in the block editor to enhance the paragraph block with additional functionality.
     */
    public function enqueue_admin_block_scripts() {
        wp_enqueue_script(
            'paragraph-block-extension',
            WACDMG_PLUGIN_URL . 'assets/js/block-enhancer.js',
            array(
                'wp-blocks', 
                'wp-element', 
                'wp-editor', 
                'wp-components', 
                'wp-hooks',
                'wp-i18n',
                'wp-data',
                'wp-block-editor',
                'wp-plugins' 
            ),
            WACDMG_PLUGIN_VERSION,
            true
        );
        
        // Optional: Localize script for AJAX calls
        wp_localize_script('paragraph-block-extension', 'paragraphBlockAjax', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('paragraph_block_nonce')
        ));
    }

    /**
     * Render the settings page for the plugin.
     *
     * This function is called when the settings page is accessed in the admin area.
     */
    public function wacdmg_render_settings_page() {
        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Render the settings page
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-settings.php';
    }

    /**
     * Add a div above the product description in the product edit screen.
     *
     * This function is hooked to 'edit_form_after_title' to insert a custom div
     * for displaying AI-generated content or other information.
     *
     * @param WP_Post $post The current post object.
     */
    public function add_div_above_product_description($post) {
        if ($post->post_type === 'product') {
            echo '<div id="wacdmg-description-container" class="wacdmg-description-container"></div>';
        }
    }

}