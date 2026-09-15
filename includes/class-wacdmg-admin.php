<?php
/**
 * WACDMG Admin Class
 *
 * Handles admin-side functionality including menu setup, scripts/styles, and UI elements.
 *
 * @package WACDMG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WACDMG_Admin {

    /**
     * Constructor to initialize hooks and actions.
     */
    public function __construct() {
        $this->wacdmg_init_hooks();
    }

    /**
     * Initialize hooks for admin functionality.
     */
    public function wacdmg_init_hooks() {
        add_action( 'edit_form_after_title', array( $this, 'wacdmg_add_div_above_product_description' ) );
        add_action( 'admin_menu', array( $this, 'wacdmg_register_admin_menus' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'wacdmg_enqueue_admin_scripts' ) );
        add_action( 'enqueue_block_editor_assets', array( $this, 'wacdmg_enqueue_admin_block_scripts' ) );
        add_filter( 'attachment_fields_to_edit', array( $this, 'wacdmg_attachment_alt_field' ), 10, 2 );
    }

    /**
     * Register the main menu and all sub-menu pages.
     */
    public function wacdmg_register_admin_menus() {
        // Main menu item.
        add_menu_page(
            __( 'AI Assistant', 'wacdmg-ai-content-assistant' ),
            __( 'AI Assistant', 'wacdmg-ai-content-assistant' ),
            'manage_options',
            'wacdmg-settings',
            array( $this, 'wacdmg_render_settings_page' ),
            'data:image/svg+xml;base64,' . base64_encode( file_get_contents( WACDMG_PLUGIN_DIR . 'assets/images/main-icon.svg' ) ),
            56
        );

        // Settings sub-menu (duplicate of parent for clarity).
        add_submenu_page(
            'wacdmg-settings',
            __( 'Settings', 'wacdmg-ai-content-assistant' ),
            __( 'Settings', 'wacdmg-ai-content-assistant' ),
            'manage_options',
            'wacdmg-settings',
            array( $this, 'wacdmg_render_settings_page' )
        );

        // Image Generator sub-menu.
        add_submenu_page(
            'wacdmg-settings',
            __( 'Image Generator', 'wacdmg-ai-content-assistant' ),
            __( 'Image Generator', 'wacdmg-ai-content-assistant' ),
            'manage_options',
            'wacdmg-image-generator',
            array( $this, 'wacdmg_render_image_generator_page' )
        );

        // Content Templates sub-menu.
        add_submenu_page(
            'wacdmg-settings',
            __( 'Content Templates', 'wacdmg-ai-content-assistant' ),
            __( 'Content Templates', 'wacdmg-ai-content-assistant' ),
            'manage_options',
            'wacdmg-content-templates',
            array( $this, 'wacdmg_render_content_templates_page' )
        );

        // Usage Log sub-menu.
        add_submenu_page(
            'wacdmg-settings',
            __( 'Usage Log', 'wacdmg-ai-content-assistant' ),
            __( 'Usage Log', 'wacdmg-ai-content-assistant' ),
            'manage_options',
            'wacdmg-usage-log',
            array( $this, 'wacdmg_render_usage_log_page' )
        );
    }

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook The current admin page hook suffix.
     */
    public function wacdmg_enqueue_admin_scripts( $hook ) {
        // Only enqueue on our plugin pages.
        $wacdmg_pages = array(
            'toplevel_page_wacdmg-settings',
            'ai-assistant_page_wacdmg-image-generator',
            'ai-assistant_page_wacdmg-content-templates',
            'ai-assistant_page_wacdmg-usage-log',
        );

        // Also enqueue on WooCommerce classic product edit screen only.
        $is_product_page = $this->wacdmg_is_classic_product_screen( $hook );

        $should_enqueue = in_array( $hook, $wacdmg_pages, true ) || $is_product_page;

        $this->wacdmg_enqueue_media_alt_script( $hook );

        if ( ! $should_enqueue ) {
            return;
        }

        // Styles.
        wp_enqueue_style(
            'wacdmg-admin-style',
            WACDMG_PLUGIN_URL . 'css/admin-style.css',
            array(),
            WACDMG_PLUGIN_VERSION
        );

        // Main JS bundle.
        wp_enqueue_script(
            'wacdmg-admin-script',
            WACDMG_PLUGIN_URL . 'assets/js/app.js',
            array( 'wp-i18n' ),
            WACDMG_PLUGIN_VERSION,
            true
        );

        // Localize data.
        $api_namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $api_base_url  = rest_url( $api_namespace );
        if ( strpos( $api_base_url, '?rest_route=' ) !== false ) {
            $api_base_url = home_url( "/wp-json/{$api_namespace}" );
        }

        // Detect active SEO plugins.
        $seo_plugins = array();
        if ( defined( 'WPSEO_VERSION' ) ) {
            $seo_plugins[] = 'yoast';
        }
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $seo_plugins[] = 'rankmath';
        }
        if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            $seo_plugins[] = 'aioseo';
        }

        $current_page = 'other';
        if ( in_array( $hook, $wacdmg_pages, true ) ) {
            $page_map = array(
                'toplevel_page_wacdmg-settings'              => 'settings',
                'ai-assistant_page_wacdmg-image-generator'  => 'image-generator',
                'ai-assistant_page_wacdmg-content-templates' => 'content-templates',
                'ai-assistant_page_wacdmg-usage-log'         => 'usage-log',
            );
            $current_page = $page_map[ $hook ] ?? 'settings';
        } elseif ( $is_product_page ) {
            $current_page = 'product-editor';
        }

        wp_localize_script( 'wacdmg-admin-script', 'wacdmgAdmin', array(
            'ajax_url'    => admin_url( 'admin-ajax.php' ),
            'nonce'       => wp_create_nonce( 'wacdmg_admin_nonce' ),
            'rest_nonce'  => wp_create_nonce( 'wp_rest' ),
            'apiBaseUrl'  => esc_url_raw( $api_base_url ),
            'currentPage' => $current_page,
            'seoPlugins'  => $seo_plugins,
            'pluginUrl'   => WACDMG_PLUGIN_URL,
            'wpDate'      => current_time( 'Y-m-d' ),
            'wpMonth'     => current_time( 'Y-m' ),
        ) );
    }

    /**
     * Whether the current admin screen is a classic WooCommerce product editor.
     *
     * @param string $hook Current admin hook.
     * @return bool
     */
    private function wacdmg_is_classic_product_screen( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return false;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && isset( $screen->post_type ) && $screen->post_type === 'product' ) {
            return true;
        }

        if ( isset( $_GET['post_type'] ) && sanitize_key( wp_unslash( $_GET['post_type'] ) ) === 'product' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }

        if ( $hook === 'post.php' && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_id = intval( $_GET['post'] );
            return $post_id && get_post_type( $post_id ) === 'product';
        }

        return false;
    }

    /**
     * Enqueue Media Library alt-text helper on upload and editor screens.
     *
     * @param string $hook Current admin hook.
     */
    private function wacdmg_enqueue_media_alt_script( $hook ) {
        $media_hooks = array( 'upload.php', 'post.php', 'post-new.php', 'media.php' );
        if ( ! in_array( $hook, $media_hooks, true ) ) {
            return;
        }
        if ( ! current_user_can( 'upload_files' ) ) {
            return;
        }

        $api_namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $api_base_url  = rest_url( $api_namespace );
        if ( strpos( $api_base_url, '?rest_route=' ) !== false ) {
            $api_base_url = home_url( "/wp-json/{$api_namespace}" );
        }

        wp_enqueue_script(
            'wacdmg-media-alt',
            WACDMG_PLUGIN_URL . 'assets/js/media-alt.js',
            array( 'jquery' ),
            WACDMG_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'wacdmg-media-alt', 'wacdmgMediaAlt', array(
            'apiBaseUrl' => esc_url_raw( $api_base_url ),
            'restNonce'  => wp_create_nonce( 'wp_rest' ),
        ) );
    }

    /**
     * Add an AI alt-text button to the attachment edit form.
     *
     * @param array   $fields Existing attachment fields.
     * @param WP_Post $post   Attachment post.
     * @return array
     */
    public function wacdmg_attachment_alt_field( $fields, $post ) {
        if ( ! $post || ! wp_attachment_is_image( $post->ID ) || ! current_user_can( 'edit_post', $post->ID ) ) {
            return $fields;
        }

        $fields['wacdmg_ai_alt'] = array(
            'label' => __( 'AI Alt Text', 'wacdmg-ai-content-assistant' ),
            'input' => 'html',
            'html'  => sprintf(
                '<button type="button" class="button wacdmg-gen-alt-btn" data-attachment-id="%1$d">%2$s</button> <span class="wacdmg-alt-status" aria-live="polite"></span>',
                intval( $post->ID ),
                esc_html__( 'Generate with AI', 'wacdmg-ai-content-assistant' )
            ),
        );

        return $fields;
    }

    /**
     * Enqueue scripts for the Gutenberg block editor.
     */
    public function wacdmg_enqueue_admin_block_scripts() {
        wp_enqueue_script(
            'wacdmg-block-enhancer',
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
                'wp-plugins',
            ),
            WACDMG_PLUGIN_VERSION,
            true
        );

        $api_namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $api_base_url  = rest_url( $api_namespace );
        if ( strpos( $api_base_url, '?rest_route=' ) !== false ) {
            $api_base_url = home_url( "/wp-json/{$api_namespace}" );
        }

        // Detect active SEO plugins.
        $seo_plugins = array();
        if ( defined( 'WPSEO_VERSION' ) ) {
            $seo_plugins[] = 'yoast';
        }
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $seo_plugins[] = 'rankmath';
        }
        if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            $seo_plugins[] = 'aioseo';
        }

        wp_localize_script( 'wacdmg-block-enhancer', 'wacdmgAdmin', array(
            'ajax_url'   => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'wacdmg_admin_nonce' ),
            'rest_nonce' => wp_create_nonce( 'wp_rest' ),
            'apiBaseUrl' => esc_url_raw( $api_base_url ),
            'seoPlugins' => $seo_plugins,
            'pluginUrl'  => WACDMG_PLUGIN_URL,
            'wpDate'     => current_time( 'Y-m-d' ),
            'wpMonth'    => current_time( 'Y-m' ),
        ) );
    }

    /**
     * Render the Settings page.
     */
    public function wacdmg_render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-settings.php';
    }

    /**
     * Render the Image Generator page.
     */
    public function wacdmg_render_image_generator_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-image-generator.php';
    }

    /**
     * Render the Content Templates page.
     */
    public function wacdmg_render_content_templates_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-templates.php';
    }

    /**
     * Render the Usage Log page.
     */
    public function wacdmg_render_usage_log_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-usage-log.php';
    }

    /**
     * Add a React mount div above the product description in the WooCommerce product edit screen.
     *
     * @param WP_Post $post The current post object.
     */
    public function wacdmg_add_div_above_product_description( $post ) {
        if ( $post->post_type === 'product' ) {
            echo '<div id="wacdmg-description-container" class="wacdmg-description-container"></div>';
        }
    }
}