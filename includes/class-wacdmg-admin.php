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
        add_action( 'elementor/editor/after_enqueue_scripts', array( $this, 'wacdmg_enqueue_field_assistant' ) );
        add_action( 'admin_init', array( $this, 'wacdmg_register_term_panels' ) );
        add_filter( 'attachment_fields_to_edit', array( $this, 'wacdmg_attachment_alt_field' ), 10, 2 );
        add_filter( 'bulk_actions-edit-product', array( $this, 'wacdmg_register_product_bulk_action' ) );
        add_filter( 'handle_bulk_actions-edit-product', array( $this, 'wacdmg_handle_product_bulk_action' ), 10, 3 );
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

        add_submenu_page(
            'wacdmg-settings',
            __( 'Bulk Fill', 'wacdmg-ai-content-assistant' ),
            __( 'Bulk Fill', 'wacdmg-ai-content-assistant' ),
            'edit_posts',
            'wacdmg-bulk-products',
            array( $this, 'wacdmg_render_bulk_products_page' )
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
            'ai-assistant_page_wacdmg-bulk-products',
        );

        $is_product_page    = $this->wacdmg_is_classic_product_screen( $hook );
        $is_classic_editor  = $this->wacdmg_is_classic_editor_screen( $hook );
        $is_term_page       = $this->wacdmg_is_term_screen();

        $should_enqueue = in_array( $hook, $wacdmg_pages, true ) || $is_classic_editor || $is_term_page;

        $this->wacdmg_enqueue_media_alt_script( $hook );
        $this->wacdmg_enqueue_field_assistant( $hook );

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

        $screen    = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        $post_type = ( $screen && ! empty( $screen->post_type ) ) ? $screen->post_type : '';
        $seo_plugins = $this->wacdmg_detect_seo_plugins();

        $current_page = 'other';
        if ( in_array( $hook, $wacdmg_pages, true ) ) {
            $page_map = array(
                'toplevel_page_wacdmg-settings'              => 'settings',
                'ai-assistant_page_wacdmg-image-generator'  => 'image-generator',
                'ai-assistant_page_wacdmg-content-templates' => 'content-templates',
                'ai-assistant_page_wacdmg-usage-log'         => 'usage-log',
                'ai-assistant_page_wacdmg-bulk-products'     => 'bulk-products',
            );
            $current_page = $page_map[ $hook ] ?? 'settings';
        } elseif ( $is_product_page ) {
            $current_page = 'product-editor';
        } elseif ( $is_classic_editor ) {
            $current_page = 'post-editor';
        } elseif ( $is_term_page ) {
            $current_page = 'term-editor';
        }

        $taxonomy = '';
        if ( $is_term_page ) {
            $taxonomy = ( $screen && ! empty( $screen->taxonomy ) ) ? $screen->taxonomy : '';
        }

        $term_id = 0;
        if ( $is_term_page && isset( $_GET['tag_ID'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $term_id = intval( $_GET['tag_ID'] );
        }

        wp_localize_script( 'wacdmg-admin-script', 'wacdmgAdmin', array(
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'wacdmg_admin_nonce' ),
            'rest_nonce'       => wp_create_nonce( 'wp_rest' ),
            'apiBaseUrl'       => esc_url_raw( $api_base_url ),
            'currentPage'      => $current_page,
            'seoPlugins'       => $seo_plugins,
            'pluginUrl'        => WACDMG_PLUGIN_URL,
            'wpDate'           => current_time( 'Y-m-d' ),
            'wpMonth'          => current_time( 'Y-m' ),
            'taxonomy'         => $taxonomy,
            'termId'           => $term_id,
            'postType'         => $post_type,
            'isProduct'        => ( $post_type === 'product' ),
            'hasBrands'        => taxonomy_exists( 'product_brand' ),
            'hasWpml'          => defined( 'ICL_SITEPRESS_VERSION' ),
            'hasPolylang'      => function_exists( 'pll_get_post' ),
            'hasYikesTabs'     => class_exists( 'YIKES_Custom_Product_Tabs' ) || defined( 'YIKES_CUSTOM_PRODUCT_TABS_VERSION' ),
            'translationLangs' => $this->wacdmg_get_translation_languages(),
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
     * Whether the current screen is a classic (non-block) post editor.
     *
     * @param string $hook Current admin hook.
     * @return bool
     */
    private function wacdmg_is_classic_editor_screen( $hook ) {
        if ( $hook !== 'post.php' && $hook !== 'post-new.php' ) {
            return false;
        }

        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( $screen && ! empty( $screen->is_block_editor ) ) {
            return false;
        }

        $post_type = '';
        if ( $screen && ! empty( $screen->post_type ) ) {
            $post_type = $screen->post_type;
        } elseif ( isset( $_GET['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_type = sanitize_key( wp_unslash( $_GET['post_type'] ) );
        } elseif ( $hook === 'post-new.php' ) {
            $post_type = 'post';
        } elseif ( $hook === 'post.php' && isset( $_GET['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            $post_type = get_post_type( intval( $_GET['post'] ) );
        }

        if ( ! $post_type || $post_type === 'attachment' ) {
            return false;
        }

        $object = get_post_type_object( $post_type );
        return $object && ! empty( $object->show_ui );
    }

    /**
     * Whether the current screen is a public taxonomy term editor.
     *
     * @return bool
     */
    private function wacdmg_is_term_screen() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        if ( ! $screen || empty( $screen->taxonomy ) ) {
            return false;
        }
        $taxonomy = get_taxonomy( $screen->taxonomy );
        return $taxonomy && ! empty( $taxonomy->show_ui );
    }

    /**
     * Back-compat alias.
     *
     * @return bool
     */
    private function wacdmg_is_product_term_screen() {
        return $this->wacdmg_is_term_screen();
    }

    /**
     * Detect active SEO plugins.
     *
     * @return array
     */
    private function wacdmg_detect_seo_plugins() {
        if ( class_exists( 'WACDMG_SEO' ) ) {
            $seo = new WACDMG_SEO();
            return $seo->wacdmg_detect_seo_plugins();
        }
        $plugins = array();
        if ( defined( 'WPSEO_VERSION' ) ) {
            $plugins[] = 'yoast';
        }
        if ( defined( 'RANK_MATH_VERSION' ) ) {
            $plugins[] = 'rankmath';
        }
        if ( class_exists( 'AIOSEO\Plugin\AIOSEO' ) ) {
            $plugins[] = 'aioseo';
        }
        return $plugins;
    }

    /**
     * Other languages available for WPML / Polylang.
     *
     * @return array
     */
    private function wacdmg_get_translation_languages() {
        $langs = array();
        $current = '';
        if ( defined( 'ICL_LANGUAGE_CODE' ) ) {
            $current = ICL_LANGUAGE_CODE;
        } elseif ( function_exists( 'pll_current_language' ) ) {
            $current = pll_current_language();
        }

        if ( has_filter( 'wpml_active_languages' ) ) {
            $wpml = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
            if ( is_array( $wpml ) ) {
                foreach ( $wpml as $code => $data ) {
                    if ( $current && $code === $current ) {
                        continue;
                    }
                    $langs[] = array(
                        'code' => $code,
                        'name' => isset( $data['native_name'] ) ? $data['native_name'] : $code,
                    );
                }
            }
        } elseif ( function_exists( 'pll_languages_list' ) ) {
            $list = pll_languages_list( array( 'fields' => 'slug' ) );
            $names = function_exists( 'pll_languages_list' ) ? pll_languages_list( array( 'fields' => 'name' ) ) : array();
            if ( is_array( $list ) ) {
                foreach ( $list as $i => $code ) {
                    if ( $current && $code === $current ) {
                        continue;
                    }
                    $langs[] = array(
                        'code' => $code,
                        'name' => isset( $names[ $i ] ) ? $names[ $i ] : $code,
                    );
                }
            }
        }
        return $langs;
    }

    /**
     * Register the term assistant on public taxonomies.
     */
    public function wacdmg_register_term_panels() {
        $taxes = get_taxonomies( array( 'show_ui' => true ), 'names' );
        if ( ! is_array( $taxes ) ) {
            return;
        }
        foreach ( $taxes as $taxonomy ) {
            add_action( $taxonomy . '_edit_form', array( $this, 'wacdmg_render_term_panel' ) );
        }
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

        wp_localize_script(
            'wacdmg-media-alt',
            'wacdmgMediaAlt',
            array(
                'apiBaseUrl' => esc_url_raw( $api_base_url ),
                'restNonce'  => wp_create_nonce( 'wp_rest' ),
            )
        );
    }

    /**
     * Enqueue field-assistant on post, term, and Elementor editor screens.
     *
     * @param string $hook Current admin hook or empty on Elementor.
     */
    public function wacdmg_enqueue_field_assistant( $hook = '' ) {
        $media_hooks = array( 'post.php', 'post-new.php', 'edit-tags.php', 'term.php' );
        $is_elementor = doing_action( 'elementor/editor/after_enqueue_scripts' );
        if ( ! $is_elementor && ! in_array( $hook, $media_hooks, true ) ) {
            return;
        }
        if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_products' ) ) {
            return;
        }

        $api_namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $api_base_url  = rest_url( $api_namespace );
        if ( strpos( $api_base_url, '?rest_route=' ) !== false ) {
            $api_base_url = home_url( "/wp-json/{$api_namespace}" );
        }

        wp_enqueue_style(
            'wacdmg-admin-style',
            WACDMG_PLUGIN_URL . 'css/admin-style.css',
            array(),
            WACDMG_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'wacdmg-field-assistant',
            WACDMG_PLUGIN_URL . 'assets/js/field-assistant.js',
            array(),
            WACDMG_PLUGIN_VERSION,
            true
        );

        wp_localize_script(
            'wacdmg-field-assistant',
            'wacdmgFieldAssistant',
            array(
                'apiBaseUrl' => esc_url_raw( $api_base_url ),
                'restNonce'  => wp_create_nonce( 'wp_rest' ),
            )
        );
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

        $fields['wacdmg_ai_caption'] = array(
            'label' => __( 'AI Caption', 'wacdmg-ai-content-assistant' ),
            'input' => 'html',
            'html'  => sprintf(
                '<button type="button" class="button wacdmg-gen-caption-btn" data-attachment-id="%1$d">%2$s</button> <span class="wacdmg-caption-status" aria-live="polite"></span>',
                intval( $post->ID ),
                esc_html__( 'Generate caption', 'wacdmg-ai-content-assistant' )
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

        $seo_plugins = $this->wacdmg_detect_seo_plugins();

        wp_localize_script( 'wacdmg-block-enhancer', 'wacdmgAdmin', array(
            'ajax_url'         => admin_url( 'admin-ajax.php' ),
            'nonce'            => wp_create_nonce( 'wacdmg_admin_nonce' ),
            'rest_nonce'       => wp_create_nonce( 'wp_rest' ),
            'apiBaseUrl'       => esc_url_raw( $api_base_url ),
            'seoPlugins'       => $seo_plugins,
            'pluginUrl'        => WACDMG_PLUGIN_URL,
            'wpDate'           => current_time( 'Y-m-d' ),
            'wpMonth'          => current_time( 'Y-m' ),
            'hasBrands'        => taxonomy_exists( 'product_brand' ),
            'hasWpml'          => defined( 'ICL_SITEPRESS_VERSION' ),
            'hasPolylang'      => function_exists( 'pll_get_post' ),
            'translationLangs' => $this->wacdmg_get_translation_languages(),
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
     * Render the bulk empty-field fill page.
     */
    public function wacdmg_render_bulk_products_page() {
        if ( ! current_user_can( 'edit_posts' ) ) {
            return;
        }
        include_once WACDMG_PLUGIN_DIR . 'templates/admin-wacdmg-bulk-products.php';
    }

    /**
     * Mount the AI assistant on product category and tag edit screens.
     */
    public function wacdmg_render_term_panel() {
        echo '<div id="wacdmg-term-container" class="wacdmg-term-container"></div>';
    }

    /**
     * Add a Products list bulk action for empty-field fill.
     *
     * @param array $actions Bulk actions.
     * @return array
     */
    public function wacdmg_register_product_bulk_action( $actions ) {
        $actions['wacdmg_fill_empty'] = __( 'AI fill empty content', 'wacdmg-ai-content-assistant' );
        return $actions;
    }

    /**
     * Send selected products to the bulk fill review screen.
     *
     * @param string $redirect Redirect URL.
     * @param string $action   Bulk action slug.
     * @param array  $post_ids Selected IDs.
     * @return string
     */
    public function wacdmg_handle_product_bulk_action( $redirect, $action, $post_ids ) {
        if ( $action !== 'wacdmg_fill_empty' ) {
            return $redirect;
        }
        $ids = array_slice( array_values( array_unique( array_filter( array_map( 'intval', $post_ids ) ) ) ), 0, 20 );
        return add_query_arg(
            array(
                'page' => 'wacdmg-bulk-products',
                'ids'  => implode( ',', $ids ),
            ),
            admin_url( 'admin.php' )
        );
    }

    /**
     * Add a React mount div above the product description in the WooCommerce product edit screen.
     *
     * @param WP_Post $post The current post object.
     */
    public function wacdmg_add_div_above_product_description( $post ) {
        if ( ! $post || empty( $post->post_type ) || $post->post_type === 'attachment' ) {
            return;
        }

        $object = get_post_type_object( $post->post_type );
        if ( ! $object || empty( $object->show_ui ) ) {
            return;
        }

        if ( $post->post_type !== 'product' ) {
            $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
            if ( $screen && ! empty( $screen->is_block_editor ) ) {
                return;
            }
        }

        echo '<div id="wacdmg-description-container" class="wacdmg-description-container"></div>';
    }
}