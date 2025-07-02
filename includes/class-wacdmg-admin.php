<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class WACDMG_Admin {

    public function __construct() {
        $this->init_hooks();
    }

    public function init_hooks() {
        
        add_action('edit_form_after_title', array($this, 'add_div_above_product_description'));
        // create a settings sub menu page on WooCommerce menu
        add_action( 'admin_menu', function() {
            add_submenu_page(
                'woocommerce',
                __( 'AI for Woocommerce', 'wacdmg' ),
                __( 'AI for Woocommerce', 'wacdmg' ),
                'manage_options',
                'wacdmg-settings',
                array( $this, 'wacdmg_render_settings_page' )
            );
        });

        //enque admin scripts and styles
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
    }

    public function enqueue_admin_scripts() {
        wp_enqueue_style( 'wacdmg-admin-style', WACDMG_PLUGIN_URL . 'css/admin-style.css' );
        wp_enqueue_script( 'wacdmg-admin-script', WACDMG_PLUGIN_URL . 'assets/js/app.js', array( 'wp-i18n' ), null, true );

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
    public function wacdmg_render_settings_page() {
        // Check user permissions
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Render the settings page
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-settings.php';
    }

    public function add_div_above_product_description($post) {
        if ($post->post_type === 'product') {
            echo '<div id="wacdmg-description-container" class="wacdmg-description-container"></div>';
        }
    }

}