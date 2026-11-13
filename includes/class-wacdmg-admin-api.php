<?php
/**
 * WACDMG Admin API Class
 *
 * Handles custom REST API endpoints for the plugin.
 *
 * @package WACDMG
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

class WACDMG_Admin_API {

    /**
     * Default OpenAI image model.
     *
     * @var string
     */
    const DEFAULT_IMAGE_MODEL = 'gpt-image-2';

    /**
     * Default Together.ai image model.
     *
     * @var string
     */
    const DEFAULT_TOGETHER_IMAGE_MODEL = 'black-forest-labs/FLUX.2-pro';

    /**
     * Default text model per provider, used when no model is saved.
     *
     * Settings loads each provider's live model list; these only apply before a model is chosen.
     *
     * @var array
     */
    const DEFAULT_TEXT_MODELS = array(
        'chatgpt'    => 'gpt-5.4-mini',
        'groq'       => 'openai/gpt-oss-120b',
        'gemini'     => 'gemini-3.8-flash',
        'claude'     => 'claude-sonnet-5-5',
        'mistral'    => 'mistral-large-latest',
        'openrouter' => 'openrouter/auto',
    );

    /**
     * Default text model for a provider.
     *
     * @param string $provider Provider slug.
     * @return string
     */
    private function wacdmg_default_text_model( $provider ) {
        $defaults = self::DEFAULT_TEXT_MODELS;
        return isset( $defaults[ $provider ] ) ? $defaults[ $provider ] : '';
    }

    /**
     * Plugin namespace for REST API
     *
     * @var string
     */
    private $namespace;

    /**
     * Initialize the class
     */
    public function __construct() {
        $this->namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $this->wacdmg_init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    public function wacdmg_init_hooks() {
        add_action( 'rest_api_init', array( $this, 'wacdmg_register_routes' ) );
        add_action( 'transition_post_status', array( $this, 'wacdmg_auto_seo_on_publish' ), 20, 3 );
        add_action( 'wacdmg_auto_seo_job', array( $this, 'wacdmg_run_auto_seo_job' ), 10, 2 );
    }

    /**
     * Register custom REST API routes
     *
     * @since 1.0.0
     */
    public function wacdmg_register_routes() {
        $can_generate = array( $this, 'wacdmg_can_generate' );
        $can_manage   = array( $this, 'wacdmg_can_manage_settings' );

        // Text generation endpoints — editors and shop managers.
        $this->wacdmg_register_route( '/generate-description', array( $this, 'wacdmg_generate_description' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-paragraph-content', array( $this, 'wacdmg_generate_description' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-short-description', array( $this, 'wacdmg_generate_short_description' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-tags', array( $this, 'wacdmg_generate_tags' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-categories', array( $this, 'wacdmg_generate_categories' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-attributes', array( $this, 'wacdmg_generate_attributes' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/product-content-status', array( $this, 'wacdmg_product_content_status' ), 'GET', $can_generate );
        $this->wacdmg_register_route( '/products-content-scan', array( $this, 'wacdmg_products_content_scan' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/apply-product-content', array( $this, 'wacdmg_apply_product_content' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-seo-meta', array( $this, 'wacdmg_generate_seo_meta' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-alt-text', array( $this, 'wacdmg_generate_alt_text' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/apply-translation', array( $this, 'wacdmg_apply_translation' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/chat', array( $this, 'wacdmg_chat' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/generate-image', array( $this, 'wacdmg_generate_image' ), 'POST', $can_generate );
        $this->wacdmg_register_route( '/get-templates', array( $this, 'wacdmg_get_templates' ), 'GET', $can_generate );

        // Settings, usage, and template CRUD — administrators only.
        $this->wacdmg_register_route( '/save-settings', array( $this, 'wacdmg_save_settings' ), 'POST', $can_manage );
        $this->wacdmg_register_route( '/get-settings', array( $this, 'wacdmg_get_settings' ), 'GET', $can_manage );
        $this->wacdmg_register_route( '/test-connection', array( $this, 'wacdmg_test_connection' ), 'POST', $can_manage );
        $this->wacdmg_register_route( '/fetch-models', array( $this, 'wacdmg_fetch_models' ), 'POST', $can_manage );
        $this->wacdmg_register_route( '/get-usage', array( $this, 'wacdmg_get_usage' ), 'GET', $can_manage );
        $this->wacdmg_register_route( '/reset-usage', array( $this, 'wacdmg_reset_usage' ), 'POST', $can_manage );
        $this->wacdmg_register_route( '/save-template', array( $this, 'wacdmg_save_template' ), 'POST', $can_manage );
        $this->wacdmg_register_route( '/delete-template', array( $this, 'wacdmg_delete_template' ), 'POST', $can_manage );
    }

    /**
     * Whether the current user may generate AI content.
     *
     * @return bool
     */
    public function wacdmg_can_generate() {
        return current_user_can( 'edit_posts' ) || current_user_can( 'edit_products' );
    }

    /**
     * Whether the current user may manage plugin settings.
     *
     * @return bool
     */
    public function wacdmg_can_manage_settings() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Register a custom REST API route
     *
     * @param string          $route      The route to register.
     * @param callable        $callback   The callback function for the route.
     * @param string          $method     The HTTP method (default: 'GET').
     * @param callable|string $permission The permission callback.
     */
    public function wacdmg_register_route( $route, $callback, $method = 'GET', $permission = null ) {
        if ( null === $permission ) {
            $permission = array( $this, 'wacdmg_can_manage_settings' );
        }
        $namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        register_rest_route( $namespace, $route, array(
            'methods'             => $method,
            'callback'            => $callback,
            'permission_callback' => $permission,
        ) );
    }

    /**
     * Keep a stored API key when the UI sends a masked or empty value.
     *
     * @param mixed $incoming      Value from the settings form.
     * @param array $existing      Previously saved credentials.
     * @param array $existing_keys Keys to read from $existing, in priority order.
     * @return string
     */
    private function wacdmg_resolve_saved_key( $incoming, $existing, $existing_keys ) {
        $incoming = is_string( $incoming ) ? $incoming : '';
        if ( $incoming === '' || strpos( $incoming, '•' ) !== false ) {
            foreach ( $existing_keys as $key ) {
                if ( ! empty( $existing[ $key ] ) && is_string( $existing[ $key ] ) && strpos( $existing[ $key ], '•' ) === false ) {
                    return $existing[ $key ];
                }
            }
            return '';
        }
        return sanitize_text_field( $incoming );
    }

    /**
     * Normalize the image provider. Legacy "dalle" maps to "openai".
     *
     * @param mixed $value Raw value.
     * @return string openai|together
     */
    private function wacdmg_normalize_image_provider( $value ) {
        $value = sanitize_key( (string) $value );
        return 'together' === $value ? 'together' : 'openai';
    }

    /**
     * Normalize an OpenAI image model ID. Retired DALL-E models fall back to the default.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function wacdmg_normalize_image_model( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( '' === $value || 0 === strpos( $value, 'dall-e' ) || ! preg_match( '/^[A-Za-z0-9._\-]+$/', $value ) ) {
            return self::DEFAULT_IMAGE_MODEL;
        }
        return $value;
    }

    /**
     * Normalize a Together.ai image model ID. Removed FLUX.1 schnell models fall back to the default.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function wacdmg_normalize_together_image_model( $value ) {
        $value = sanitize_text_field( (string) $value );
        if ( '' === $value || false !== stripos( $value, 'FLUX.1-schnell' ) || ! preg_match( '/^[A-Za-z0-9._\-\/]+$/', $value ) ) {
            return self::DEFAULT_TOGETHER_IMAGE_MODEL;
        }
        return $value;
    }

    /**
     * Normalize an image size. Legacy DALL-E 3 sizes map to the closest GPT Image size.
     *
     * @param mixed $value Raw value.
     * @return string
     */
    private function wacdmg_normalize_image_size( $value ) {
        $value  = sanitize_text_field( (string) $value );
        $legacy = array(
            '1792x1024' => '1536x1024',
            '1024x1792' => '1024x1536',
        );
        if ( isset( $legacy[ $value ] ) ) {
            $value = $legacy[ $value ];
        }
        $allowed = array( '1024x1024', '1536x1024', '1024x1536' );
        return in_array( $value, $allowed, true ) ? $value : '1024x1024';
    }

    /**
     * Normalize image quality. Legacy DALL-E 3 values map to GPT Image values.
     *
     * @param mixed $value Raw value.
     * @return string low|medium|high
     */
    private function wacdmg_normalize_image_quality( $value ) {
        $value  = sanitize_key( (string) $value );
        $legacy = array(
            'standard' => 'medium',
            'hd'       => 'high',
        );
        if ( isset( $legacy[ $value ] ) ) {
            $value = $legacy[ $value ];
        }
        return in_array( $value, array( 'low', 'medium', 'high' ), true ) ? $value : 'medium';
    }

    /**
     * Saved image settings, normalized for the current image API.
     *
     * @return array { provider, model, size, quality }
     */
    private function wacdmg_get_image_settings() {
        $creds = get_option( 'wacdmg_ai_creds', array() );
        if ( ! is_array( $creds ) ) {
            $creds = array();
        }
        return array(
            'provider' => $this->wacdmg_normalize_image_provider( $creds['image_provider'] ?? 'openai' ),
            'model'    => $this->wacdmg_normalize_image_model( $creds['image_model'] ?? '' ),
            'together_model' => $this->wacdmg_normalize_together_image_model( $creds['together_image_model'] ?? '' ),
            'size'     => $this->wacdmg_normalize_image_size( $creds['image_size'] ?? '1024x1024' ),
            'quality'  => $this->wacdmg_normalize_image_quality( $creds['image_quality'] ?? 'medium' ),
        );
    }

    /**
     * Build a REST error payload, preserving HTTP 429 for rate limits.
     *
     * @param array $result Result array with error/code keys.
     * @return WP_REST_Response
     */
    private function wacdmg_error_rest_response( $result ) {
        $code = isset( $result['code'] ) ? intval( $result['code'] ) : 500;
        if ( $code < 400 ) {
            $code = 500;
        }
        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => isset( $result['error'] ) ? $result['error'] : 'Unknown error.' ),
        ), $code );
    }

    /**
     * Whether today's successful generations have reached the configured cap.
     *
     * @return bool
     */
    private function wacdmg_is_over_daily_limit() {
        $creds = get_option( 'wacdmg_ai_creds', array() );
        $limit = isset( $creds['rate_limit_day'] ) ? intval( $creds['rate_limit_day'] ) : 100;
        if ( $limit <= 0 ) {
            return false;
        }

        $usage = get_option( 'wacdmg_usage', array() );
        $today = current_time( 'Y-m-d' );
        $today_total = 0;
        if ( ! empty( $usage['daily'][ $today ] ) && is_array( $usage['daily'][ $today ] ) ) {
            $today_total = array_sum( $usage['daily'][ $today ] );
        }

        return $today_total >= $limit;
    }

    /**
     * Rate-limit error array, or null when under the cap.
     *
     * @return array|null
     */
    private function wacdmg_get_rate_limit_error() {
        if ( ! $this->wacdmg_is_over_daily_limit() ) {
            return null;
        }
        $creds = get_option( 'wacdmg_ai_creds', array() );
        $limit = intval( $creds['rate_limit_day'] ?? 100 );
        return array(
            'success' => false,
            'error'   => sprintf( 'Daily generation limit of %d reached. Increase the limit in Settings or try again tomorrow.', $limit ),
            'code'    => 429,
        );
    }

    // =========================================================================
    // SETTINGS ENDPOINTS
    // =========================================================================

    /**
     * Save settings endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_save_settings( WP_REST_Request $request ) {
        $formData = $request->get_param( 'formData' );

        if ( empty( $formData['provider'] ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Invalid provider selected.' ),
            ), 400 );
        }

        $existing = get_option( 'wacdmg_ai_creds', array() );
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }

        // Build settings array - support multiple API keys
        $settingsData = array(
            'provider'            => sanitize_text_field( $formData['provider'] ),
            'model'               => sanitize_text_field( $formData['model'] ?? '' ),
            'chatgpt_key'         => $this->wacdmg_resolve_saved_key( $formData['chatgptKey'] ?? '', $existing, array( 'chatgpt_key', 'apiKey' ) ),
            'groq_key'            => $this->wacdmg_resolve_saved_key( $formData['groqKey'] ?? '', $existing, array( 'groq_key' ) ),
            'gemini_key'          => $this->wacdmg_resolve_saved_key( $formData['geminiKey'] ?? '', $existing, array( 'gemini_key' ) ),
            'claude_key'          => $this->wacdmg_resolve_saved_key( $formData['claudeKey'] ?? '', $existing, array( 'claude_key' ) ),
            'mistral_key'         => $this->wacdmg_resolve_saved_key( $formData['mistralKey'] ?? '', $existing, array( 'mistral_key' ) ),
            'openrouter_key'      => $this->wacdmg_resolve_saved_key( $formData['openrouterKey'] ?? '', $existing, array( 'openrouter_key' ) ),
            'image_provider'      => $this->wacdmg_normalize_image_provider( $formData['imageProvider'] ?? 'openai' ),
            'image_model'         => $this->wacdmg_normalize_image_model( $formData['imageModel'] ?? '' ),
            'together_image_model' => $this->wacdmg_normalize_together_image_model( $formData['togetherImageModel'] ?? '' ),
            'together_key'        => $this->wacdmg_resolve_saved_key( $formData['togetherKey'] ?? '', $existing, array( 'together_key' ) ),
            'image_size'          => $this->wacdmg_normalize_image_size( $formData['imageSize'] ?? '1024x1024' ),
            'image_quality'       => $this->wacdmg_normalize_image_quality( $formData['imageQuality'] ?? 'medium' ),
            'seo_integration'     => ( function () use ( $formData ) {
                $seo = sanitize_key( $formData['seoIntegration'] ?? 'auto' );
                $allowed = array( 'auto', 'yoast', 'rankmath', 'aioseo', 'seopress', 'generic' );
                return in_array( $seo, $allowed, true ) ? $seo : 'auto';
            } )(),
            'auto_seo_on_publish' => ! empty( $formData['autoSeoOnPublish'] ),
            'rate_limit_day'      => max( 0, intval( $formData['rateLimitDay'] ?? 100 ) ), // 0 = no limit.
            'keep_data_on_uninstall' => ! empty( $formData['keepDataOnUninstall'] ),
        );
        $settingsData['apiKey'] = $settingsData['chatgpt_key'];

        update_option( 'wacdmg_ai_creds', $settingsData );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array( 'message' => 'Settings saved successfully.' ),
        ), 200 );
    }

    /**
     * Get settings endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_get_settings( WP_REST_Request $request ) {
        $settings = get_option( 'wacdmg_ai_creds', array() );

        // Detect installed SEO plugins
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
        if ( class_exists( 'WACDMG_SEO' ) ) {
            $seo_plugins = ( new WACDMG_SEO() )->wacdmg_detect_seo_plugins();
        }

        // Mask API keys for security (return only last 4 chars)
        $masked = array();
        $key_fields = array( 'chatgpt_key', 'groq_key', 'gemini_key', 'claude_key', 'mistral_key', 'openrouter_key', 'together_key', 'apiKey' );
        foreach ( $settings as $k => $v ) {
            if ( in_array( $k, $key_fields ) && ! empty( $v ) ) {
                $masked[ $k ] = str_repeat( '•', max( 0, strlen( $v ) - 4 ) ) . substr( $v, -4 );
            } else {
                $masked[ $k ] = $v;
            }
        }

        // Return image settings in their current (normalized) form so legacy values display correctly.
        $image = $this->wacdmg_get_image_settings();
        $masked['image_provider'] = $image['provider'];
        $masked['image_model']    = $image['model'];
        $masked['together_image_model'] = $image['together_model'];
        $masked['image_size']     = $image['size'];
        $masked['image_quality']  = $image['quality'];
        unset( $masked['image_style'] );

        return new WP_REST_Response( array(
            'success'     => true,
            'data'        => $masked,
            'seo_plugins' => $seo_plugins,
        ), 200 );
    }

    /**
     * Test connection for a given provider and key
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_test_connection( WP_REST_Request $request ) {
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );
        $api_key  = sanitize_text_field( $request->get_param( 'api_key' ) );
        $model    = sanitize_text_field( $request->get_param( 'model' ) );

        if ( empty( $provider ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Provider is required.' )
            ), 400 );
        }

        // Retrieve saved key if the input key is masked (contains bullet points) or empty
        if ( empty( $api_key ) || strpos( $api_key, '•' ) !== false ) {
            $settings = get_option( 'wacdmg_ai_creds', array() );
            switch ( $provider ) {
                case 'chatgpt':
                    $api_key = $settings['chatgpt_key'] ?? ( $settings['apiKey'] ?? '' );
                    break;
                case 'groq':
                    $api_key = $settings['groq_key'] ?? '';
                    break;
                case 'gemini':
                    $api_key = $settings['gemini_key'] ?? '';
                    break;
                case 'claude':
                    $api_key = $settings['claude_key'] ?? '';
                    break;
                case 'mistral':
                    $api_key = $settings['mistral_key'] ?? '';
                    break;
                case 'openrouter':
                    $api_key = $settings['openrouter_key'] ?? '';
                    break;
                case 'together':
                    $api_key = $settings['together_key'] ?? '';
                    break;
            }
        }

        if ( empty( $api_key ) && $provider !== 'openrouter' ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'API Key is empty.' )
            ), 400 );
        }

        $test_prompt = 'Hello';
        $result = array( 'success' => false, 'error' => 'Unsupported provider.' );

        switch ( $provider ) {
            case 'chatgpt':
                $result = $this->wacdmg_handle_ai_prompt_chatgpt( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'chatgpt' ) );
                break;
            case 'groq':
                $result = $this->wacdmg_handle_ai_prompt_groq( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'groq' ) );
                break;
            case 'gemini':
                $result = $this->wacdmg_handle_ai_prompt_gemini( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'gemini' ) );
                break;
            case 'claude':
                $result = $this->wacdmg_handle_ai_prompt_claude( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'claude' ) );
                break;
            case 'mistral':
                $result = $this->wacdmg_handle_ai_prompt_mistral( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'mistral' ) );
                break;
            case 'openrouter':
                $result = $this->wacdmg_handle_ai_prompt_openrouter( $test_prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'openrouter' ) );
                break;
            case 'together':
                $result = $this->wacdmg_test_together_key( $api_key );
                break;
        }

        if ( $result['success'] ) {
            // Also fetch models list on successful connection so client can update dropdown immediately
            $fetched_models = $this->wacdmg_fetch_models_from_provider( $provider, $api_key );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'message' => 'Connection successful! Key and model are working.',
                    'models'  => $fetched_models,
                )
            ), 200 );
        } else {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Connection failed: ' . $result['error'] )
            ), 200 );
        }
    }

    /**
     * Test a Together.ai API key by listing models.
     *
     * Listing models is free and does not depend on any specific model still being available.
     *
     * @param string $api_key Together.ai API key.
     * @return array
     */
    private function wacdmg_test_together_key( $api_key ) {
        $response = wp_remote_get( 'https://api.together.xyz/v1/models', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 === $code && is_array( $data ) ) {
            return array( 'success' => true );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    // =========================================================================
    // TEXT GENERATION ENDPOINTS
    // =========================================================================

    /**
     * Generate product description / paragraph content endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_description( WP_REST_Request $request ) {
        $prompt = $request->get_param( 'prompt' );
        $tone     = sanitize_text_field( $request->get_param( 'tone' ) ?: 'persuasive' );
        $language = sanitize_text_field( $request->get_param( 'language' ) ?: 'English' );

        $result = $this->wacdmg_run_ai_prompt( $prompt );

        if ( $result['success'] ) {
            $this->wacdmg_log_usage( 'description' );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'description' => $result['description'],
                    'content'     => $result['description'], // block-app uses 'content' key
                ),
            ), 200 );
        }

        return $this->wacdmg_error_rest_response( $result );
    }

    /**
     * Generate short description endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_short_description( WP_REST_Request $request ) {
        $prompt = $request->get_param( 'prompt' );
        $result = $this->wacdmg_run_ai_prompt( $prompt );

        if ( $result['success'] ) {
            $this->wacdmg_log_usage( 'short_description' );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'short_description' => $result['description'] ),
            ), 200 );
        }

        return $this->wacdmg_error_rest_response( $result );
    }

    /**
     * Generate product/post tags endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_tags( WP_REST_Request $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        $apply   = (bool) $request->get_param( 'apply' );
        $incoming_tags = $request->get_param( 'tags' );

        if ( $apply && is_array( $incoming_tags ) && $post_id ) {
            $tags = array_values( array_filter( array_map( 'sanitize_text_field', $incoming_tags ) ) );
            $applied = $this->wacdmg_apply_tags_to_post( $post_id, $tags );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'tags'     => $tags,
                    'applied'  => ! empty( $applied['success'] ),
                    'term_ids' => isset( $applied['term_ids'] ) ? $applied['term_ids'] : array(),
                    'taxonomy' => isset( $applied['taxonomy'] ) ? $applied['taxonomy'] : '',
                    'skipped'  => isset( $applied['skipped'] ) ? $applied['skipped'] : array(),
                ),
            ), 200 );
        }

        $prompt = $request->get_param( 'prompt' );
        $result = $this->wacdmg_run_ai_prompt( $prompt );

        if ( $result['success'] ) {
            $this->wacdmg_log_usage( 'tags' );
            $raw  = wp_strip_all_tags( $result['description'] );
            $tags = array_map( 'trim', explode( ',', $raw ) );
            $tags = array_filter( $tags );
            $tags = array_values( $tags );

            $applied = array();
            if ( $apply && $post_id ) {
                $applied = $this->wacdmg_apply_tags_to_post( $post_id, $tags );
            }

            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'tags'     => $tags,
                    'raw'      => $raw,
                    'applied'  => ! empty( $applied['success'] ),
                    'term_ids' => isset( $applied['term_ids'] ) ? $applied['term_ids'] : array(),
                    'taxonomy' => isset( $applied['taxonomy'] ) ? $applied['taxonomy'] : '',
                    'skipped'  => isset( $applied['skipped'] ) ? $applied['skipped'] : array(),
                ),
            ), 200 );
        }

        return $this->wacdmg_error_rest_response( $result );
    }

    /**
     * Assign generated tags to a post or product.
     *
     * @param int   $post_id Post ID.
     * @param array $tags    Tag names.
     * @return array { success: bool, taxonomy: string, term_ids: int[] }
     */
    private function wacdmg_apply_tags_to_post( $post_id, $tags ) {
        $taxonomy = ( get_post_type( $post_id ) === 'product' && taxonomy_exists( 'product_tag' ) )
            ? 'product_tag'
            : 'post_tag';

        $applied = $this->wacdmg_apply_terms_to_post( $post_id, $tags, $taxonomy );

        return array(
            'success'  => ! empty( $applied['applied'] ),
            'taxonomy' => $applied['taxonomy'],
            'term_ids' => $applied['term_ids'],
            'skipped'  => $applied['skipped'],
        );
    }

    /**
     * Split AI text into a clean list of names.
     *
     * @param string $raw Raw model output.
     * @return array
     */
    private function wacdmg_parse_comma_list( $raw ) {
        $raw = wp_strip_all_tags( (string) $raw );
        $parts = preg_split( '/[,;\n]+/', $raw );
        $items = array();
        foreach ( $parts as $part ) {
            $part = trim( $part );
            $part = preg_replace( '/^[\-\*\d\.\)\s]+/', '', $part );
            if ( $part !== '' ) {
                $items[] = sanitize_text_field( $part );
            }
        }
        return array_values( array_unique( $items ) );
    }

    /**
     * Parse "Name: Value" attribute lines from AI output.
     *
     * @param string $raw Raw model output.
     * @return array
     */
    private function wacdmg_parse_attribute_pairs( $raw ) {
        $raw = wp_strip_all_tags( (string) $raw );
        $chunks = preg_split( '/[\n\|]+/', $raw );
        $pairs = array();
        foreach ( $chunks as $chunk ) {
            if ( strpos( $chunk, ':' ) === false ) {
                continue;
            }
            $bits = array_map( 'trim', explode( ':', $chunk, 2 ) );
            if ( empty( $bits[0] ) || empty( $bits[1] ) ) {
                continue;
            }
            $pairs[] = array(
                'name'  => sanitize_text_field( $bits[0] ),
                'value' => sanitize_text_field( $bits[1] ),
            );
        }
        return $pairs;
    }

    /**
     * Generate and optionally apply product categories.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_categories( WP_REST_Request $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        $apply   = (bool) $request->get_param( 'apply' );
        $incoming = $request->get_param( 'categories' );
        $taxonomy = sanitize_key( $request->get_param( 'taxonomy' ) );
        if ( $taxonomy === '' ) {
            $taxonomy = 'product_cat';
        }

        if ( $apply && is_array( $incoming ) && $post_id ) {
            $names = array_values( array_filter( array_map( 'sanitize_text_field', $incoming ) ) );
            $applied = $this->wacdmg_apply_terms_to_post( $post_id, $names, $taxonomy );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array_merge( array( 'categories' => $names ), $applied ),
            ), 200 );
        }

        $prompt = $request->get_param( 'prompt' );
        $result = $this->wacdmg_run_ai_prompt( $prompt );
        if ( ! $result['success'] ) {
            return $this->wacdmg_error_rest_response( $result );
        }

        $this->wacdmg_log_usage( 'categories' );
        $names = $this->wacdmg_parse_comma_list( $result['description'] );
        $applied = array( 'applied' => false, 'term_ids' => array(), 'taxonomy' => $taxonomy );
        if ( $apply && $post_id ) {
            $applied = $this->wacdmg_apply_terms_to_post( $post_id, $names, $taxonomy );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array_merge( array(
                'categories' => $names,
                'raw'        => $result['description'],
            ), $applied ),
        ), 200 );
    }

    /**
     * Append taxonomy terms, matching existing terms when possible.
     *
     * Follows WordPress capabilities: the user needs the taxonomy's assign_terms capability to
     * attach terms, and its edit_terms capability to create terms that do not exist yet.
     * Names that would need a new term the user cannot create are returned in 'skipped'.
     *
     * @param int    $post_id  Post ID.
     * @param array  $names    Term names.
     * @param string $taxonomy Taxonomy slug.
     * @return array { applied: bool, term_ids: int[], taxonomy: string, skipped: string[] }
     */
    private function wacdmg_apply_terms_to_post( $post_id, $names, $taxonomy = 'product_cat' ) {
        $taxonomy = sanitize_key( $taxonomy );
        $empty = array(
            'applied'  => false,
            'term_ids' => array(),
            'taxonomy' => $taxonomy,
            'skipped'  => array(),
        );
        if ( ! $post_id || empty( $names ) || ! current_user_can( 'edit_post', $post_id ) || ! taxonomy_exists( $taxonomy ) ) {
            return $empty;
        }

        $object_taxes = get_object_taxonomies( get_post_type( $post_id ) );
        if ( ! in_array( $taxonomy, $object_taxes, true ) ) {
            return $empty;
        }

        $tax_object = get_taxonomy( $taxonomy );
        if ( ! $tax_object || ! current_user_can( $tax_object->cap->assign_terms ) ) {
            return $empty;
        }
        $can_create = current_user_can( $tax_object->cap->edit_terms );

        $term_ids = array();
        $skipped  = array();
        foreach ( $names as $name ) {
            $name = trim( (string) $name );
            if ( '' === $name ) {
                continue;
            }
            $existing = get_term_by( 'name', $name, $taxonomy );
            if ( $existing && ! is_wp_error( $existing ) ) {
                $term_ids[] = (int) $existing->term_id;
                continue;
            }
            if ( ! $can_create ) {
                $skipped[] = $name;
                continue;
            }
            $inserted = wp_insert_term( $name, $taxonomy );
            if ( ! is_wp_error( $inserted ) && ! empty( $inserted['term_id'] ) ) {
                $term_ids[] = (int) $inserted['term_id'];
            } elseif ( is_wp_error( $inserted ) && 'term_exists' === $inserted->get_error_code() ) {
                // Same slug, different name (e.g. "T-Shirts" vs "T Shirts"): reuse it.
                $term_ids[] = (int) $inserted->get_error_data( 'term_exists' );
            }
        }
        $term_ids = array_values( array_unique( array_filter( $term_ids ) ) );
        if ( empty( $term_ids ) ) {
            $empty['skipped'] = $skipped;
            return $empty;
        }
        wp_set_object_terms( $post_id, $term_ids, $taxonomy, true );
        $all = wp_get_object_terms( $post_id, $taxonomy, array( 'fields' => 'ids' ) );
        if ( is_wp_error( $all ) ) {
            $all = $term_ids;
        }
        return array(
            'applied'  => true,
            'term_ids' => array_map( 'intval', $all ),
            'taxonomy' => $taxonomy,
            'skipped'  => $skipped,
        );
    }

    /**
     * Append product categories, matching existing terms when possible.
     *
     * @param int   $post_id Post ID.
     * @param array $names   Category names.
     * @return array
     */
    private function wacdmg_apply_categories_to_product( $post_id, $names ) {
        return $this->wacdmg_apply_terms_to_post( $post_id, $names, 'product_cat' );
    }

    /**
     * Generate and optionally apply custom product attributes.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_attributes( WP_REST_Request $request ) {
        $post_id  = intval( $request->get_param( 'post_id' ) );
        $apply    = (bool) $request->get_param( 'apply' );
        $incoming = $request->get_param( 'attributes' );

        if ( $apply && is_array( $incoming ) && $post_id ) {
            $pairs = array();
            foreach ( $incoming as $row ) {
                if ( ! is_array( $row ) ) {
                    continue;
                }
                $name  = sanitize_text_field( $row['name'] ?? '' );
                $value = sanitize_text_field( $row['value'] ?? '' );
                if ( $name && $value ) {
                    $pairs[] = array( 'name' => $name, 'value' => $value );
                }
            }
            $applied = $this->wacdmg_apply_attributes_to_product( $post_id, $pairs );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'attributes' => $pairs,
                    'applied'    => ! empty( $applied['success'] ),
                ),
            ), 200 );
        }

        $prompt = $request->get_param( 'prompt' );
        $result = $this->wacdmg_run_ai_prompt( $prompt );
        if ( ! $result['success'] ) {
            return $this->wacdmg_error_rest_response( $result );
        }

        $this->wacdmg_log_usage( 'attributes' );
        $pairs = $this->wacdmg_parse_attribute_pairs( $result['description'] );
        $applied = array( 'success' => false );
        if ( $apply && $post_id ) {
            $applied = $this->wacdmg_apply_attributes_to_product( $post_id, $pairs );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'attributes' => $pairs,
                'raw'        => $result['description'],
                'applied'    => ! empty( $applied['success'] ),
            ),
        ), 200 );
    }

    /**
     * Merge custom visible attributes onto a WooCommerce product.
     *
     * @param int   $post_id Product ID.
     * @param array $pairs   Name/value pairs.
     * @return array
     */
    private function wacdmg_apply_attributes_to_product( $post_id, $pairs ) {
        if ( ! $post_id || empty( $pairs ) || ! current_user_can( 'edit_post', $post_id ) ) {
            return array( 'success' => false );
        }
        if ( get_post_type( $post_id ) !== 'product' || ! function_exists( 'wc_get_product' ) ) {
            return array( 'success' => false );
        }

        $product = wc_get_product( $post_id );
        if ( ! $product ) {
            return array( 'success' => false );
        }

        $existing = $product->get_attributes();
        if ( ! is_array( $existing ) ) {
            $existing = array();
        }

        foreach ( $pairs as $pair ) {
            $slug = sanitize_title( $pair['name'] );
            if ( $slug === '' ) {
                continue;
            }
            if ( isset( $existing[ $slug ] ) || isset( $existing[ 'pa_' . $slug ] ) ) {
                continue;
            }
            $options = array_values( array_filter( array_map( 'trim', explode( '|', $pair['value'] ) ) ) );
            if ( class_exists( 'WC_Product_Attribute' ) ) {
                $attribute = new WC_Product_Attribute();
                $attribute->set_id( 0 );
                $attribute->set_name( $pair['name'] );
                $attribute->set_options( $options );
                $attribute->set_visible( true );
                $attribute->set_variation( false );
                $existing[ $slug ] = $attribute;
            }
        }

        $product->set_attributes( $existing );
        $product->save();
        return array( 'success' => true );
    }

    /**
     * Empty-field checklist for one product.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_product_content_status( WP_REST_Request $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Invalid product.' ),
            ), 403 );
        }
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $this->wacdmg_get_product_content_status( $post_id ),
        ), 200 );
    }

    /**
     * Scan selected products for empty content fields.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_products_content_scan( WP_REST_Request $request ) {
        $ids = $request->get_param( 'ids' );
        if ( ! is_array( $ids ) ) {
            $ids = array();
        }
        $ids = array_slice( array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) ), 0, 20 );
        $items = array();
        foreach ( $ids as $post_id ) {
            if ( get_post_type( $post_id ) !== 'product' || ! current_user_can( 'edit_post', $post_id ) ) {
                continue;
            }
            $items[] = $this->wacdmg_get_product_content_status( $post_id );
        }
        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array( 'products' => $items ),
        ), 200 );
    }

    /**
     * Build empty-field status for a product.
     *
     * @param int $post_id Product ID.
     * @return array
     */
    private function wacdmg_get_product_content_status( $post_id ) {
        $post = get_post( $post_id );
        $title = $post ? $post->post_title : '';
        $content = $post ? trim( wp_strip_all_tags( $post->post_content ) ) : '';
        $excerpt = $post ? trim( wp_strip_all_tags( $post->post_excerpt ) ) : '';
        $tag_ids = taxonomy_exists( 'product_tag' ) ? wp_get_object_terms( $post_id, 'product_tag', array( 'fields' => 'ids' ) ) : array();
        $cat_ids = taxonomy_exists( 'product_cat' ) ? wp_get_object_terms( $post_id, 'product_cat', array( 'fields' => 'ids' ) ) : array();
        if ( is_wp_error( $tag_ids ) ) {
            $tag_ids = array();
        }
        if ( is_wp_error( $cat_ids ) ) {
            $cat_ids = array();
        }
        $tag_count = count( $tag_ids );
        $cat_count = count( $cat_ids );

        $seo_empty = true;
        if ( class_exists( 'WACDMG_SEO' ) ) {
            $seo = new WACDMG_SEO();
            $meta = $seo->wacdmg_get_existing_seo_meta( $post_id );
            $seo_empty = empty( $meta['seo_title'] ) && empty( $meta['meta_description'] );
        }

        $featured = (bool) get_post_thumbnail_id( $post_id );
        $gaps = array();

        /**
         * Filter the length (in characters) below which a product description counts as thin.
         *
         * Characters are used rather than words so languages without spaces (Chinese, Japanese, Thai)
         * are measured correctly.
         *
         * @param int $chars Default 160.
         */
        $thin_chars        = (int) apply_filters( 'wacdmg_thin_description_chars', 160 );
        $content_length    = function_exists( 'mb_strlen' ) ? mb_strlen( $content ) : strlen( $content );
        $description_state = 'ok';
        if ( '' === $content ) {
            $description_state = 'empty';
            $gaps[]            = 'description';
        } elseif ( $content_length < $thin_chars ) {
            $description_state = 'thin';
            $gaps[]            = 'description';
        }
        if ( $excerpt === '' ) {
            $gaps[] = 'excerpt';
        }
        if ( ! $tag_count ) {
            $gaps[] = 'tags';
        }
        $uncat = taxonomy_exists( 'product_cat' ) ? get_term_by( 'slug', 'uncategorized', 'product_cat' ) : false;
        $only_uncat = $cat_count === 1 && $uncat && in_array( (int) $uncat->term_id, $cat_ids, true );
        if ( $cat_count === 0 || $only_uncat ) {
            $gaps[] = 'categories';
        }
        if ( $seo_empty ) {
            $gaps[] = 'seo';
        }
        if ( ! $featured ) {
            $gaps[] = 'image';
        }

        $empty_gallery_alts = array();
        $gallery = get_post_meta( $post_id, '_product_image_gallery', true );
        if ( is_string( $gallery ) && $gallery !== '' ) {
            $gallery_ids = array_slice( array_filter( array_map( 'intval', explode( ',', $gallery ) ) ), 0, 10 );
            foreach ( $gallery_ids as $attachment_id ) {
                $alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
                if ( $alt === '' ) {
                    $empty_gallery_alts[] = $attachment_id;
                }
            }
        }

        return array(
            'id'                  => $post_id,
            'title'               => $title,
            'edit_url'            => get_edit_post_link( $post_id, 'raw' ),
            'gaps'                => $gaps,
            'description_state'   => $description_state,
            'has_title'           => $title !== '',
            'content'             => $content,
            'excerpt'             => $excerpt,
            'empty_gallery_alts'  => $empty_gallery_alts,
        );
    }

    /**
     * Apply generated fields to a product without wiping unspecified data.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_apply_product_content( WP_REST_Request $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        if ( ! $post_id || get_post_type( $post_id ) !== 'product' || ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'You cannot edit this product.' ),
            ), 403 );
        }

        // Fields that already have a value are only replaced when the request explicitly asks for it.
        $replace = $request->get_param( 'replace' );
        $replace = is_array( $replace ) ? array_map( 'sanitize_key', $replace ) : array();
        $current = get_post( $post_id );
        $kept    = array();

        $update = array( 'ID' => $post_id );
        $title = $request->get_param( 'title' );
        $content = $request->get_param( 'content' );
        $excerpt = $request->get_param( 'excerpt' );
        if ( is_string( $title ) && $title !== '' ) {
            if ( '' === trim( $current->post_title ) || in_array( 'title', $replace, true ) ) {
                $update['post_title'] = sanitize_text_field( $title );
            } else {
                $kept[] = 'title';
            }
        }
        if ( is_string( $content ) && $content !== '' ) {
            if ( '' === trim( wp_strip_all_tags( $current->post_content ) ) || in_array( 'content', $replace, true ) ) {
                $update['post_content'] = wp_kses_post( $content );
            } else {
                $kept[] = 'content';
            }
        }
        if ( is_string( $excerpt ) && $excerpt !== '' ) {
            if ( '' === trim( wp_strip_all_tags( $current->post_excerpt ) ) || in_array( 'excerpt', $replace, true ) ) {
                $update['post_excerpt'] = wp_kses_post( $excerpt );
            } else {
                $kept[] = 'excerpt';
            }
        }
        if ( count( $update ) > 1 ) {
            wp_update_post( $update );
        }

        $tags = $request->get_param( 'tags' );
        if ( is_array( $tags ) && ! empty( $tags ) ) {
            $this->wacdmg_apply_tags_to_post( $post_id, array_map( 'sanitize_text_field', $tags ) );
        }
        $categories = $request->get_param( 'categories' );
        if ( is_array( $categories ) && ! empty( $categories ) ) {
            $this->wacdmg_apply_categories_to_product( $post_id, array_map( 'sanitize_text_field', $categories ) );
        }
        $seo = $request->get_param( 'seo' );
        if ( is_array( $seo ) && class_exists( 'WACDMG_SEO' ) ) {
            $writer   = new WACDMG_SEO();
            $existing = $writer->wacdmg_get_existing_seo_meta( $post_id );
            $has_seo  = ! empty( $existing['seo_title'] ) || ! empty( $existing['meta_description'] );
            if ( ! $has_seo || in_array( 'seo', $replace, true ) ) {
                $writer->wacdmg_write_seo_meta( $post_id, $seo );
            } else {
                $kept[] = 'seo';
            }
        }

        $purchase_note = $request->get_param( 'purchase_note' );
        if ( is_string( $purchase_note ) && $purchase_note !== '' ) {
            update_post_meta( $post_id, '_purchase_note', sanitize_textarea_field( $purchase_note ) );
        }

        $status         = $this->wacdmg_get_product_content_status( $post_id );
        $status['kept'] = $kept;

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $status,
        ), 200 );
    }

    /**
     * Generate SEO meta (title + description + keywords) endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_seo_meta( WP_REST_Request $request ) {
        $title_prompt = $request->get_param( 'title_prompt' );
        $desc_prompt  = $request->get_param( 'desc_prompt' );
        $kw_prompt    = $request->get_param( 'kw_prompt' );
        $post_id      = intval( $request->get_param( 'post_id' ) );
        $term_id      = intval( $request->get_param( 'term_id' ) );
        $taxonomy     = sanitize_key( $request->get_param( 'taxonomy' ) ?: '' );

        $rate_error = $this->wacdmg_get_rate_limit_error();
        if ( $rate_error ) {
            return $this->wacdmg_error_rest_response( $rate_error );
        }

        $results = array();
        $any_success = false;

        // Generate SEO title
        if ( ! empty( $title_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $title_prompt );
            $results['seo_title'] = ( $r['success'] ) ? wp_strip_all_tags( $r['description'] ) : '';
            $any_success = $any_success || $r['success'];
            if ( ! $r['success'] && empty( $results['seo_title'] ) && empty( $desc_prompt ) && empty( $kw_prompt ) ) {
                return $this->wacdmg_error_rest_response( $r );
            }
        }

        // Generate meta description
        if ( ! empty( $desc_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $desc_prompt );
            $results['meta_description'] = ( $r['success'] ) ? wp_strip_all_tags( $r['description'] ) : '';
            $any_success = $any_success || $r['success'];
        }

        // Generate focus keywords
        if ( ! empty( $kw_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $kw_prompt );
            $results['focus_keywords'] = ( $r['success'] ) ? wp_strip_all_tags( $r['description'] ) : '';
            $any_success = $any_success || $r['success'];
        }

        // Write to SEO plugins if post_id or term_id provided.
        if ( $any_success && class_exists( 'WACDMG_SEO' ) ) {
            $seo = new WACDMG_SEO();
            if ( $term_id && current_user_can( 'edit_term', $term_id ) ) {
                $seo->wacdmg_write_term_seo_meta( $term_id, $results, $taxonomy );
            } elseif ( $post_id && current_user_can( 'edit_post', $post_id ) ) {
                $seo->wacdmg_write_seo_meta( $post_id, $results );
            }
        }

        if ( $any_success ) {
            $this->wacdmg_log_usage( 'seo_meta' );
        }

        return new WP_REST_Response( array(
            'success' => $any_success,
            'data'    => $any_success ? $results : array( 'message' => 'Failed to generate SEO meta.' ),
        ), $any_success ? 200 : 500 );
    }

    /**
     * Build the vision prompt for alt text or a caption.
     *
     * @param string $target  'alt' or 'caption'.
     * @param string $context Optional page/product title that may name the subject.
     * @return string
     */
    private function wacdmg_build_image_text_prompt( $target, $context = '' ) {
        $locale  = get_locale();
        $context = $context !== '' ? "\nContext — this image is used on a page or product titled: \"" . $context . '". Use it only to name the subject if it matches what you see.' : '';

        if ( 'caption' === $target ) {
            return 'Write a short caption for the attached image: one or two sentences that add useful context for readers.'
                . "\nDescribe only what is actually visible. Do not invent details you cannot see."
                . $context
                . "\nWrite in the language for the locale \"" . $locale . '".'
                . "\nReturn only the caption as plain text, without quotes.";
        }

        return 'Write alt text for the attached image, for people using screen readers.'
            . "\nDescribe the important visible content specifically and factually, in under 125 characters."
            . "\nDo not invent details you cannot see. Do not start with \"Image of\" or \"Picture of\"."
            . $context
            . "\nWrite in the language for the locale \"" . $locale . '".'
            . "\nReturn only the alt text as plain text, without quotes.";
    }

    /**
     * Generate alt text or a caption by sending the image itself to a vision model.
     *
     * Request params:
     * - attachment_id (int, required): image attachment.
     * - target ('alt'|'caption'): what to write. Default 'alt'.
     * - context (string): optional page/product title.
     * - save (bool): write the result to the attachment. Default false, so the UI can show it for review.
     * - skip_if_filled (bool): bulk mode — skip images that already have a value, and save the result.
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_alt_text( WP_REST_Request $request ) {
        $attachment_id  = intval( $request->get_param( 'attachment_id' ) );
        $target         = 'caption' === sanitize_key( $request->get_param( 'target' ) ?: 'alt' ) ? 'caption' : 'alt';
        $context        = sanitize_text_field( (string) $request->get_param( 'context' ) );
        $skip_if_filled = (bool) $request->get_param( 'skip_if_filled' );
        $save           = (bool) $request->get_param( 'save' ) || $skip_if_filled;

        if ( ! $attachment_id ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Choose an image from the Media Library first. AI alt text needs to see the image itself.' ),
            ), 400 );
        }

        if ( ! current_user_can( 'edit_post', $attachment_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'You cannot edit this attachment.' ),
            ), 403 );
        }

        if ( $target === 'caption' && $attachment_id && $skip_if_filled ) {
            $attachment = get_post( $attachment_id );
            if ( $attachment && trim( (string) $attachment->post_excerpt ) !== '' ) {
                return new WP_REST_Response( array(
                    'success' => true,
                    'data'    => array( 'caption' => $attachment->post_excerpt, 'skipped' => true ),
                ), 200 );
            }
        }

        if ( $target !== 'caption' && $attachment_id && $skip_if_filled ) {
            $existing_alt = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
            if ( $existing_alt !== '' ) {
                return new WP_REST_Response( array(
                    'success' => true,
                    'data'    => array( 'alt_text' => $existing_alt, 'skipped' => true ),
                ), 200 );
            }
        }

        $image = ( new WACDMG_Image_AI() )->wacdmg_get_image_for_vision( $attachment_id );
        if ( is_wp_error( $image ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => $image->get_error_message() ),
            ), 400 );
        }

        $prompt = $this->wacdmg_build_image_text_prompt( $target, $context );
        $result = $this->wacdmg_run_ai_prompt( $prompt, 'vision', $image );

        if ( ! $result['success'] ) {
            return $this->wacdmg_error_rest_response( $result );
        }

        $this->wacdmg_log_usage( 'alt_text' );
        $text = trim( wp_strip_all_tags( $result['description'] ), " \t\n\r\0\x0B\"'" );

        if ( 'caption' === $target ) {
            $text = sanitize_textarea_field( $text );
            if ( $save ) {
                wp_update_post( array(
                    'ID'           => $attachment_id,
                    'post_excerpt' => $text,
                ) );
            }
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'caption' => $text, 'saved' => $save ),
            ), 200 );
        }

        $text = sanitize_text_field( $text );
        if ( $save ) {
            update_post_meta( $attachment_id, '_wp_attachment_image_alt', $text );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array( 'alt_text' => $text, 'saved' => $save ),
        ), 200 );
    }

    /**
     * Copy generated fields onto an existing WPML/Polylang translation.
     *
     * @param WP_REST_Request $request Request.
     * @return WP_REST_Response
     */
    public function wacdmg_apply_translation( WP_REST_Request $request ) {
        $post_id = intval( $request->get_param( 'post_id' ) );
        $lang    = sanitize_text_field( $request->get_param( 'lang' ) );
        if ( ! $post_id || ! $lang || ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Invalid post or language.' ),
            ), 403 );
        }

        $translated_id = 0;
        $post_type     = get_post_type( $post_id );
        if ( has_filter( 'wpml_object_id' ) ) {
            $translated_id = intval( apply_filters( 'wpml_object_id', $post_id, $post_type, false, $lang ) );
        }
        if ( ! $translated_id && function_exists( 'pll_get_post' ) ) {
            $translated_id = intval( pll_get_post( $post_id, $lang ) );
        }

        if ( ! $translated_id || $translated_id === $post_id ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'No translation exists for that language. Create the translation first.' ),
            ), 404 );
        }
        if ( ! current_user_can( 'edit_post', $translated_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'You cannot edit the translated post.' ),
            ), 403 );
        }

        $update = array( 'ID' => $translated_id );
        $title = $request->get_param( 'title' );
        $content = $request->get_param( 'content' );
        $excerpt = $request->get_param( 'excerpt' );
        if ( is_string( $title ) && $title !== '' ) {
            $update['post_title'] = sanitize_text_field( $title );
        }
        if ( is_string( $content ) && $content !== '' ) {
            $update['post_content'] = wp_kses_post( $content );
        }
        if ( is_string( $excerpt ) && $excerpt !== '' ) {
            $update['post_excerpt'] = wp_kses_post( $excerpt );
        }
        if ( count( $update ) === 1 ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'No fields provided.' ),
            ), 400 );
        }

        $result = wp_update_post( $update, true );
        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => $result->get_error_message() ),
            ), 500 );
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array( 'translated_id' => $translated_id ),
        ), 200 );
    }

    /**
     * AI Chat endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_chat( WP_REST_Request $request ) {
        $message     = sanitize_text_field( $request->get_param( 'message' ) );
        $system_ctx  = sanitize_text_field( $request->get_param( 'context' ) ?: '' );
        $history     = $request->get_param( 'history' ) ?: array();

        if ( empty( $message ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Message is required.' ),
            ), 400 );
        }

        $prompt = $system_ctx ? "Context: {$system_ctx}\n\nUser: {$message}" : $message;
        $result = $this->wacdmg_run_ai_prompt( $prompt, 'chat' );

        if ( $result['success'] ) {
            $this->wacdmg_log_usage( 'chat' );
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'reply' => $result['description'] ),
            ), 200 );
        }

        return $this->wacdmg_error_rest_response( $result );
    }

    // =========================================================================
    // IMAGE GENERATION ENDPOINT
    // =========================================================================

    /**
     * Generate AI image endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_image( WP_REST_Request $request ) {
        $defaults     = $this->wacdmg_get_image_settings();
        $prompt       = sanitize_textarea_field( (string) $request->get_param( 'prompt' ) );
        $size         = $this->wacdmg_normalize_image_size( $request->get_param( 'size' ) ?: $defaults['size'] );
        $quality      = $this->wacdmg_normalize_image_quality( $request->get_param( 'quality' ) ?: $defaults['quality'] );
        $save_to_lib  = (bool) $request->get_param( 'save_to_library' );
        $post_id      = intval( $request->get_param( 'post_id' ) );
        $set_featured = (bool) $request->get_param( 'set_as_featured' );

        if ( empty( $prompt ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Image prompt is required.' ),
            ), 400 );
        }

        $rate_error = $this->wacdmg_get_rate_limit_error();
        if ( $rate_error ) {
            return $this->wacdmg_error_rest_response( $rate_error );
        }

        if ( $post_id && $set_featured && ! current_user_can( 'edit_post', $post_id ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'You cannot edit this post.' ),
            ), 403 );
        }

        if ( $save_to_lib && ! current_user_can( 'upload_files' ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'You do not have permission to upload files.' ),
            ), 403 );
        }

        $aiCred = get_option( 'wacdmg_ai_creds', array() );

        if ( 'together' === $defaults['provider'] ) {
            if ( empty( $aiCred['together_key'] ) ) {
                return new WP_REST_Response( array(
                    'success' => false,
                    'data'    => array( 'message' => 'Together.ai is selected as the image provider, but no Together.ai API key is saved. Add one under AI Assistant → Settings → Image Generation.' ),
                ), 400 );
            }
            $result = $this->wacdmg_generate_image_together( $prompt, $aiCred['together_key'], $size, $defaults['together_model'] );
        } else {
            $api_key = $aiCred['chatgpt_key'] ?? ( $aiCred['apiKey'] ?? '' );
            if ( empty( $api_key ) ) {
                return new WP_REST_Response( array(
                    'success' => false,
                    'data'    => array( 'message' => 'An OpenAI API key is required for image generation. Add one under AI Assistant → Settings.' ),
                ), 400 );
            }
            $result = $this->wacdmg_generate_image_openai( $prompt, $api_key, $defaults['model'], $size, $quality );
        }

        if ( ! $result['success'] ) {
            return $this->wacdmg_error_rest_response( $result );
        }

        // The image was produced (and billed), so count it before any save step.
        $this->wacdmg_log_usage( 'image' );

        $attachment_id = null;
        $display_url   = '';
        $save_error    = '';

        if ( $save_to_lib && class_exists( 'WACDMG_Image_AI' ) ) {
            $img_ai      = new WACDMG_Image_AI();
            $attach_to   = ( $post_id && current_user_can( 'edit_post', $post_id ) ) ? $post_id : 0;
            $saved       = ! empty( $result['data'] )
                ? $img_ai->wacdmg_save_image_data( $result['data'], $prompt, $attach_to )
                : $img_ai->wacdmg_save_image_to_library( $result['url'], $prompt, $attach_to );

            if ( is_wp_error( $saved ) ) {
                $save_error = 'The image was generated but could not be saved to the Media Library: ' . $saved->get_error_message();
            } else {
                $attachment_id = (int) $saved;
                $display_url   = (string) wp_get_attachment_url( $attachment_id );
                if ( $post_id && $set_featured && current_user_can( 'edit_post', $post_id ) ) {
                    set_post_thumbnail( $post_id, $attachment_id );
                }
            }
        }

        // Unsaved images are returned inline so the preview never expires.
        if ( '' === $display_url ) {
            $display_url = ! empty( $result['data'] )
                ? 'data:' . $result['mime'] . ';base64,' . base64_encode( $result['data'] )
                : $result['url'];
        }

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'url'            => $display_url,
                'attachment_id'  => $attachment_id,
                'revised_prompt' => $result['revised_prompt'] ?? '',
                'save_error'     => $save_error,
            ),
        ), 200 );
    }

    // =========================================================================
    // TEMPLATE ENDPOINTS
    // =========================================================================

    /**
     * Save a prompt template
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_save_template( WP_REST_Request $request ) {
        $template = $request->get_param( 'template' );
        if ( empty( $template['name'] ) || empty( $template['prompt'] ) ) {
            return new WP_REST_Response( array( 'success' => false, 'data' => array( 'message' => 'Name and prompt required.' ) ), 400 );
        }

        $templates  = get_option( 'wacdmg_templates', array() );
        if ( ! is_array( $templates ) ) {
            $templates = array();
        }

        $defaults    = $this->wacdmg_get_default_templates();
        $incoming_id = isset( $template['id'] ) ? sanitize_text_field( $template['id'] ) : '';
        if ( $incoming_id !== '' && ( isset( $templates[ $incoming_id ] ) || isset( $defaults[ $incoming_id ] ) ) ) {
            $id      = $incoming_id;
            $created = isset( $templates[ $id ]['created'] ) ? $templates[ $id ]['created'] : current_time( 'mysql' );
        } else {
            $id      = str_replace( '.', '', uniqid( 'tpl_', true ) );
            $created = current_time( 'mysql' );
        }

        $templates[ $id ] = array(
            'id'      => $id,
            'name'    => sanitize_text_field( $template['name'] ),
            'prompt'  => sanitize_textarea_field( $template['prompt'] ),
            'type'    => sanitize_text_field( $template['type'] ?? 'general' ),
            'created' => $created,
        );
        update_option( 'wacdmg_templates', $templates );

        return new WP_REST_Response( array( 'success' => true, 'data' => array( 'id' => $id ) ), 200 );
    }

    /**
     * Get all saved templates
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_get_templates( WP_REST_Request $request ) {
        $templates = $this->wacdmg_get_merged_templates();

        return new WP_REST_Response( array( 'success' => true, 'data' => array_values( $templates ) ), 200 );
    }

    /**
     * Delete a template
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_delete_template( WP_REST_Request $request ) {
        $id = sanitize_text_field( $request->get_param( 'id' ) );
        if ( strpos( $id, 'default_' ) === 0 ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Built-in templates cannot be deleted.' ),
            ), 400 );
        }

        $templates = get_option( 'wacdmg_templates', array() );

        if ( isset( $templates[ $id ] ) ) {
            unset( $templates[ $id ] );
            update_option( 'wacdmg_templates', $templates );
        }

        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    // =========================================================================
    // USAGE LOG ENDPOINTS
    // =========================================================================

    /**
     * Get usage statistics
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_get_usage( WP_REST_Request $request ) {
        $usage = get_option( 'wacdmg_usage', array() );
        if ( ! is_array( $usage ) ) {
            $usage = array();
        }
        $usage['today_date'] = current_time( 'Y-m-d' );
        $usage['month_date'] = current_time( 'Y-m' );
        return new WP_REST_Response( array( 'success' => true, 'data' => $usage ), 200 );
    }

    /**
     * Reset usage statistics
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_reset_usage( WP_REST_Request $request ) {
        delete_option( 'wacdmg_usage' );
        return new WP_REST_Response( array( 'success' => true, 'data' => array( 'message' => 'Usage stats reset.' ) ), 200 );
    }

    // =========================================================================
    // CORE AI ROUTER
    // =========================================================================

    /**
     * Route a prompt to the configured AI provider
     *
     * @param string     $prompt The prompt to send.
     * @param string     $type   Optional type hint (e.g. 'chat').
     * @param array|null $image  Optional image for vision models: array( 'mime' => string, 'data' => base64 string ).
     * @return array Result array with 'success', 'description', and 'error' keys.
     */
    private function wacdmg_run_ai_prompt( $prompt, $type = 'text', $image = null ) {
        $result = $this->wacdmg_dispatch_ai_prompt( $prompt, $image );

        // Providers retire models regularly; point people to the fix instead of a raw API error.
        if ( empty( $result['success'] ) && $this->wacdmg_is_model_unavailable_error( $result['error'] ?? '' ) ) {
            $result['error'] = ( $result['error'] ?? '' )
                . ' — The selected model may have been retired. Choose another model under AI Assistant → Settings.';
            return $result;
        }

        if ( $image && empty( $result['success'] ) && 429 !== (int) ( $result['code'] ?? 0 ) ) {
            $result['error'] = ( $result['error'] ?? 'Request failed.' )
                . ' — This request includes an image. If your selected model cannot read images, choose a vision-capable model in AI Assistant → Settings.';
        }

        return $result;
    }

    /**
     * Whether a provider error says the requested model does not exist or was retired.
     *
     * @param string $error Error message from the provider.
     * @return bool
     */
    private function wacdmg_is_model_unavailable_error( $error ) {
        $error = strtolower( (string) $error );
        if ( false === strpos( $error, 'model' ) ) {
            return false;
        }
        foreach ( array( 'not found', 'does not exist', 'not exist', 'deprecated', 'decommissioned', 'retired', 'no longer', 'invalid model', 'unknown model', 'model_not_found' ) as $needle ) {
            if ( false !== strpos( $error, $needle ) ) {
                return true;
            }
        }
        return false;
    }

    /**
     * Send a prompt (and optional image) to the configured provider.
     *
     * @param string     $prompt The prompt to send.
     * @param array|null $image  Optional image: array( 'mime' => string, 'data' => base64 string ).
     * @return array
     */
    private function wacdmg_dispatch_ai_prompt( $prompt, $image = null ) {
        if ( empty( $prompt ) ) {
            return array( 'success' => false, 'error' => 'Prompt is required.' );
        }

        $rate_error = $this->wacdmg_get_rate_limit_error();
        if ( $rate_error ) {
            return $rate_error;
        }

        $aiCred = get_option( 'wacdmg_ai_creds', array() );

        if ( empty( $aiCred ) || empty( $aiCred['provider'] ) ) {
            return array( 'success' => false, 'error' => 'AI credentials are missing. Please check Settings.' );
        }

        $provider = $aiCred['provider'];
        $model    = $aiCred['model'] ?? '';

        // Allow third-party providers via filter
        $other_provider = apply_filters( 'wacdmg_use_additional_provider', false );
        if ( $other_provider ) {
            $default = array( 'success' => false, 'error' => 'Other provider not implemented.' );
            return apply_filters( 'wacdmg_handle_ai_prompt_other', $default, $prompt, $aiCred, $image );
        }

        switch ( $provider ) {
            case 'chatgpt':
                $api_key = $aiCred['chatgpt_key'] ?? ( $aiCred['apiKey'] ?? '' );
                return $this->wacdmg_handle_ai_prompt_chatgpt( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'chatgpt' ), $image );

            case 'groq':
                $api_key = $aiCred['groq_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_groq( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'groq' ), $image );

            case 'gemini':
                $api_key = $aiCred['gemini_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_gemini( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'gemini' ), $image );

            case 'claude':
                $api_key = $aiCred['claude_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_claude( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'claude' ), $image );

            case 'mistral':
                $api_key = $aiCred['mistral_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_mistral( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'mistral' ), $image );

            case 'openrouter':
                $api_key = $aiCred['openrouter_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_openrouter( $prompt, $api_key, $model ?: $this->wacdmg_default_text_model( 'openrouter' ), $image );

            default:
                return array( 'success' => false, 'error' => 'Unsupported AI provider: ' . esc_html( $provider ) );
        }
    }

    // =========================================================================
    // AI PROVIDER HANDLERS — TEXT
    // =========================================================================

    /**
     * User message content for OpenAI-compatible APIs (OpenAI, Groq, Mistral, OpenRouter).
     *
     * @param string     $prompt Prompt text.
     * @param array|null $image  Optional image: array( 'mime', 'data' ).
     * @return string|array
     */
    private function wacdmg_openai_user_content( $prompt, $image = null ) {
        if ( empty( $image['data'] ) ) {
            return $prompt;
        }
        return array(
            array( 'type' => 'text', 'text' => $prompt ),
            array(
                'type'      => 'image_url',
                'image_url' => array( 'url' => 'data:' . $image['mime'] . ';base64,' . $image['data'] ),
            ),
        );
    }

    /**
     * User message content for the Anthropic Messages API.
     *
     * @param string     $prompt Prompt text.
     * @param array|null $image  Optional image: array( 'mime', 'data' ).
     * @return string|array
     */
    private function wacdmg_claude_user_content( $prompt, $image = null ) {
        if ( empty( $image['data'] ) ) {
            return $prompt;
        }
        return array(
            array(
                'type'   => 'image',
                'source' => array(
                    'type'       => 'base64',
                    'media_type' => $image['mime'],
                    'data'       => $image['data'],
                ),
            ),
            array( 'type' => 'text', 'text' => $prompt ),
        );
    }

    /**
     * Content parts for the Gemini generateContent API.
     *
     * @param string     $prompt Prompt text.
     * @param array|null $image  Optional image: array( 'mime', 'data' ).
     * @return array
     */
    private function wacdmg_gemini_user_parts( $prompt, $image = null ) {
        $parts = array();
        if ( ! empty( $image['data'] ) ) {
            $parts[] = array(
                'inline_data' => array(
                    'mime_type' => $image['mime'],
                    'data'      => $image['data'],
                ),
            );
        }
        $parts[] = array( 'text' => $prompt );
        return $parts;
    }

    /**
     * Handle AI prompt via OpenAI ChatGPT
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key OpenAI API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_chatgpt( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'OpenAI API key is missing.' );
        }

        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.' ),
                    array( 'role' => 'user', 'content' => $this->wacdmg_openai_user_content( $prompt, $image ) ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        return $this->wacdmg_parse_openai_response( $response );
    }

    /**
     * Handle AI prompt via Groq
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key Groq API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_groq( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Groq API key is missing.' );
        }

        $response = wp_remote_post( 'https://api.groq.com/openai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.' ),
                    array( 'role' => 'user', 'content' => $this->wacdmg_openai_user_content( $prompt, $image ) ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        return $this->wacdmg_parse_openai_response( $response );
    }

    /**
     * Handle AI prompt via Google Gemini
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key Google AI Studio API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_gemini( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Google Gemini API key is missing.' );
        }

        // The key goes in a header, not the URL, so it never appears in proxy or debug logs.
        $endpoint = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode( $model ) . ':generateContent';

        $response = wp_remote_post( $endpoint, array(
            'headers' => array(
                'Content-Type'   => 'application/json',
                'x-goog-api-key' => $api_key,
            ),
            'body'    => json_encode( array(
                'contents'         => array(
                    array(
                        'role'  => 'user',
                        'parts' => $this->wacdmg_gemini_user_parts( $prompt, $image ),
                    ),
                ),
                'systemInstruction' => array(
                    'parts' => array( array( 'text' => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.' ) ),
                ),
                'generationConfig' => array(
                    'temperature'     => 0.7,
                    'maxOutputTokens' => 2048,
                ),
            ) ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && isset( $data['candidates'][0]['content']['parts'][0]['text'] ) ) {
            return array(
                'success'     => true,
                'description' => $data['candidates'][0]['content']['parts'][0]['text'],
            );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    /**
     * Handle AI prompt via Anthropic Claude
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key Anthropic API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_claude( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Anthropic Claude API key is missing.' );
        }

        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', array(
            'headers' => array(
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
                'Content-Type'      => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'      => $model,
                'max_tokens' => 2048,
                'system'     => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.',
                'messages'   => array(
                    array( 'role' => 'user', 'content' => $this->wacdmg_claude_user_content( $prompt, $image ) ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && isset( $data['content'][0]['text'] ) ) {
            return array(
                'success'     => true,
                'description' => $data['content'][0]['text'],
            );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    /**
     * Handle AI prompt via Mistral AI
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key Mistral API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_mistral( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Mistral API key is missing.' );
        }

        $response = wp_remote_post( 'https://api.mistral.ai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.' ),
                    array( 'role' => 'user', 'content' => $this->wacdmg_openai_user_content( $prompt, $image ) ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        return $this->wacdmg_parse_openai_response( $response );
    }

    // =========================================================================
    // AI PROVIDER HANDLERS — IMAGE GENERATION
    // =========================================================================

    /**
     * Generate an image with an OpenAI GPT Image model.
     *
     * GPT Image models return base64 image data rather than a URL.
     *
     * @param string $prompt  The image prompt.
     * @param string $api_key OpenAI API key.
     * @param string $model   Image model ID (e.g. 'gpt-image-2').
     * @param string $size    1024x1024, 1536x1024 or 1024x1536.
     * @param string $quality low, medium or high.
     * @return array { success, data?: binary string, mime?: string, url?: string, revised_prompt?: string, error?: string }
     */
    private function wacdmg_generate_image_openai( $prompt, $api_key, $model, $size = '1024x1024', $quality = 'medium' ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'OpenAI API key is missing.' );
        }

        /**
         * Filter the OpenAI image model used for generation.
         *
         * @param string $model Model ID from Settings.
         */
        $model = (string) apply_filters( 'wacdmg_openai_image_model', $model );

        $response = wp_remote_post( 'https://api.openai.com/v1/images/generations', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => wp_json_encode( array(
                'model'   => $model,
                'prompt'  => $prompt,
                'n'       => 1,
                'size'    => $size,
                'quality' => $quality,
            ) ),
            'timeout' => 180,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 === $code && ! empty( $data['data'][0]['b64_json'] ) ) {
            $binary = base64_decode( $data['data'][0]['b64_json'], true );
            if ( false === $binary || '' === $binary ) {
                return array( 'success' => false, 'error' => 'The image provider returned invalid image data.' );
            }
            $formats = array(
                'png'  => 'image/png',
                'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
            );
            $format = isset( $data['output_format'] ) ? strtolower( (string) $data['output_format'] ) : 'png';
            return array(
                'success'        => true,
                'data'           => $binary,
                'mime'           => $formats[ $format ] ?? 'image/png',
                'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
            );
        }

        if ( 200 === $code && ! empty( $data['data'][0]['url'] ) ) {
            return array(
                'success'        => true,
                'url'            => $data['data'][0]['url'],
                'revised_prompt' => $data['data'][0]['revised_prompt'] ?? '',
            );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    /**
     * Generate image via Together.ai (Stable Diffusion / FLUX)
     *
     * @param string $prompt  The image prompt.
     * @param string $api_key Together.ai API key.
     * @param string $size    Image size (e.g. '1024x1024').
     * @return array
     */
    private function wacdmg_generate_image_together( $prompt, $api_key, $size = '1024x1024', $model = self::DEFAULT_TOGETHER_IMAGE_MODEL ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Together.ai API key is missing.' );
        }

        // Parse width/height from size string
        $dimensions = explode( 'x', $size );
        $width  = intval( $dimensions[0] ?? 1024 );
        $height = intval( $dimensions[1] ?? 1024 );

        /**
         * Filter the Together.ai image model.
         *
         * @param string $model Model ID.
         */
        $model = (string) apply_filters( 'wacdmg_together_image_model', $model );

        $response = wp_remote_post( 'https://api.together.xyz/v1/images/generations', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'  => $model,
                'prompt' => $prompt,
                'width'  => $width,
                'height' => $height,
                'n'      => 1,
            ) ),
            'timeout' => 120,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( 200 === $code && ! empty( $data['data'][0]['b64_json'] ) ) {
            $binary = base64_decode( $data['data'][0]['b64_json'], true );
            if ( false !== $binary && '' !== $binary ) {
                return array(
                    'success' => true,
                    'data'    => $binary,
                    'mime'    => 'image/png',
                );
            }
        }

        if ( 200 === $code && ! empty( $data['data'][0]['url'] ) ) {
            return array(
                'success' => true,
                'url'     => $data['data'][0]['url'],
            );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    /**
     * Clean and simplify error messages to keep them readable and non-overwhelming.
     *
     * @param string $body Raw error body response.
     * @param int    $code HTTP response code.
     * @return string Cleaned error message.
     */
    private function wacdmg_clean_error_message( $body, $code ) {
        if ( empty( $body ) ) {
            return "API returned HTTP {$code} (Empty Response)";
        }

        // Check if response is JSON
        $data = json_decode( $body, true );
        if ( is_array( $data ) ) {
            // Check standard OpenAI / Together.ai / Claude error structure
            if ( isset( $data['error']['message'] ) ) {
                return $data['error']['message'];
            }
            if ( isset( $data['error_description'] ) ) {
                return $data['error_description'];
            }
            if ( isset( $data['message'] ) ) {
                return $data['message'];
            }
        }

        // If it starts with HTML or contains tags, don't output raw HTML
        if ( strpos( $body, '<!DOCTYPE' ) !== false || strpos( $body, '<html' ) !== false ) {
            if ( preg_match( '/<title>(.*?)<\/title>/is', $body, $matches ) ) {
                return "API returned HTTP {$code}: " . trim( $matches[1] );
            }
            return "API returned HTTP {$code} (HTML Error Page)";
        }

        // If it's a long plain text, truncate it
        if ( strlen( $body ) > 200 ) {
            return "API returned HTTP {$code}: " . substr( strip_tags( $body ), 0, 200 ) . '...';
        }

        return "API returned HTTP {$code}: " . strip_tags( $body );
    }

    /**
     * Parse OpenAI-compatible response (works for ChatGPT, Groq, Mistral)
     *
     * @param array|WP_Error $response WP HTTP response.
     * @return array
     */
    private function wacdmg_parse_openai_response( $response ) {
        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && isset( $data['choices'][0]['message']['content'] ) ) {
            return array(
                'success'     => true,
                'description' => $data['choices'][0]['message']['content'],
            );
        }

        $error_msg = $this->wacdmg_clean_error_message( $body, $code );
        return array( 'success' => false, 'error' => $error_msg );
    }

    /**
     * Log usage to wp_options
     *
     * @param string $type The type of generation performed.
     */
    private function wacdmg_log_usage( $type ) {
        $usage   = get_option( 'wacdmg_usage', array() );
        $today   = current_time( 'Y-m-d' );
        $month   = current_time( 'Y-m' );

        // Total counts
        $usage['total']          = ( $usage['total'] ?? 0 ) + 1;
        $usage['by_type'][ $type ] = ( $usage['by_type'][ $type ] ?? 0 ) + 1;

        // Daily counts
        $usage['daily'][ $today ][ $type ] = ( $usage['daily'][ $today ][ $type ] ?? 0 ) + 1;

        // Monthly counts
        $usage['monthly'][ $month ] = ( $usage['monthly'][ $month ] ?? 0 ) + 1;

        // Prune daily log older than 30 days
        if ( isset( $usage['daily'] ) && count( $usage['daily'] ) > 30 ) {
            ksort( $usage['daily'] );
            $usage['daily'] = array_slice( $usage['daily'], -30, 30, true );
        }

        update_option( 'wacdmg_usage', $usage );
    }

    /**
     * Get default prompt templates
     *
     * @return array
     */
    private function wacdmg_get_default_templates() {
        return array(
            'default_product'     => array(
                'id'      => 'default_product',
                'name'    => 'E-commerce Product Description',
                'prompt'  => 'Write a compelling WooCommerce product description for: [PRODUCT_NAME]. Include key benefits, features, and a call to action. Use HTML formatting with bullet points.',
                'type'    => 'product',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_product_short' => array(
                'id'      => 'default_product_short',
                'name'    => 'Short Product Description',
                'prompt'  => 'Write a short WooCommerce product summary for: [PRODUCT_NAME]. 2-3 sentences, max 100 words. Highlight the main benefit and who it is for. Return only plain text — no HTML, no heading.',
                'type'    => 'product',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_product_faq' => array(
                'id'      => 'default_product_faq',
                'name'    => 'Product FAQ',
                'prompt'  => 'Write 5 practical FAQs for the WooCommerce product: [PRODUCT_NAME]. Use only facts implied by the product name and this content: [CONTENT]. Cover buying questions such as who it is for, how to use it, what is included, and care or compatibility. Use HTML with h3 questions and short paragraph answers. Return only the FAQ HTML.',
                'type'    => 'product',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_product_howto' => array(
                'id'      => 'default_product_howto',
                'name'    => 'How to Use / Care Instructions',
                'prompt'  => 'Write how-to-use and care instructions for: [PRODUCT_NAME]. Use this product content if available: [CONTENT]. Keep it factual. Use HTML with a short intro and numbered steps. Return only the HTML.',
                'type'    => 'product',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_product_promo' => array(
                'id'      => 'default_product_promo',
                'name'    => 'Promotional Product Copy',
                'prompt'  => 'Write short promotional copy for: [PRODUCT_NAME]. Suitable for an email, banner, or product campaign. 40-70 words, one clear benefit, and a call to action. Return only plain text.',
                'type'    => 'product',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_blog'        => array(
                'id'      => 'default_blog',
                'name'    => 'Blog Post Introduction',
                'prompt'  => 'Write an engaging blog post introduction for: [TITLE]. Hook the reader, explain what they will learn, and keep it under 150 words.',
                'type'    => 'post',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_blog_outline' => array(
                'id'      => 'default_blog_outline',
                'name'    => 'Blog Post Outline',
                'prompt'  => 'Create a blog post outline for: [TITLE]. Include H2 headings and 2-3 bullet points per section. Target a helpful 1200-1800 word article. Use HTML (h2, ul, li). Return only the outline.',
                'type'    => 'post',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_page_about'  => array(
                'id'      => 'default_page_about',
                'name'    => 'About Page Content',
                'prompt'  => 'Write About page content titled: [TITLE]. 3-5 short HTML paragraphs covering who the business is, what it offers, and why customers can trust it. Keep claims general and realistic. Return only the HTML.',
                'type'    => 'page',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_page_category' => array(
                'id'      => 'default_page_category',
                'name'    => 'Shop Category Description',
                'prompt'  => 'Write an SEO-friendly WooCommerce category or collection description for: [TITLE]. 2-4 sentences that help shoppers understand what they will find in this category. Return only HTML paragraphs — no heading.',
                'type'    => 'page',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_seo_title'   => array(
                'id'      => 'default_seo_title',
                'name'    => 'SEO Meta Title',
                'prompt'  => 'Write an SEO-optimized meta title for: [TITLE]. Keep it under 60 characters, include the primary keyword naturally.',
                'type'    => 'seo',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_seo_desc'    => array(
                'id'      => 'default_seo_desc',
                'name'    => 'SEO Meta Description',
                'prompt'  => 'Write a compelling SEO meta description for: [TITLE]. Keep it between 140-160 characters, include a call to action.',
                'type'    => 'seo',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_seo_keywords' => array(
                'id'      => 'default_seo_keywords',
                'name'    => 'SEO Focus Keywords',
                'prompt'  => 'Suggest 3-5 focus keywords for: [TITLE]. Use this content if available: [CONTENT]. Return only a comma-separated list of keywords. No explanation.',
                'type'    => 'seo',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_image_product' => array(
                'id'      => 'default_image_product',
                'name'    => 'Product Photo Prompt',
                'prompt'  => 'Write a detailed image-generation prompt for a professional product photo of: [PRODUCT_NAME]. Specify lighting, background, camera angle, and style suitable for a WooCommerce catalog. Return only the image prompt as one paragraph.',
                'type'    => 'image',
                'created' => '2025-01-01 00:00:00',
            ),
            'default_image_featured' => array(
                'id'      => 'default_image_featured',
                'name'    => 'Blog Featured Image Prompt',
                'prompt'  => 'Write a detailed image-generation prompt for a featured image about: [TITLE]. Specify composition, mood, and style suitable for a blog or landing page hero. Return only the image prompt as one paragraph.',
                'type'    => 'image',
                'created' => '2025-01-01 00:00:00',
            ),
        );
    }

    /**
     * Merge built-in templates with user-saved templates (user values win).
     *
     * @return array
     */
    private function wacdmg_get_merged_templates() {
        $defaults = $this->wacdmg_get_default_templates();
        $saved    = get_option( 'wacdmg_templates', array() );
        if ( ! is_array( $saved ) ) {
            $saved = array();
        }
        return array_merge( $defaults, $saved );
    }

    /**
     * Post types that auto-SEO may run on.
     *
     * Defaults to public, front-end-viewable types (posts, pages, products, public CPTs).
     * Internal types such as menu items, reusable blocks, navigation, and templates are excluded.
     *
     * @return string[]
     */
    private function wacdmg_get_auto_seo_post_types() {
        $types = array();
        foreach ( get_post_types( array( 'public' => true ), 'names' ) as $type ) {
            if ( 'attachment' !== $type && is_post_type_viewable( $type ) ) {
                $types[] = $type;
            }
        }

        /**
         * Filter the post types that get SEO meta generated automatically on publish.
         *
         * @param string[] $types Post type slugs.
         */
        return (array) apply_filters( 'wacdmg_auto_seo_post_types', $types );
    }

    /**
     * Whether a post is eligible for auto-SEO right now.
     *
     * @param WP_Post $post Post object.
     * @return bool
     */
    private function wacdmg_auto_seo_is_eligible( $post ) {
        if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status || '' === trim( $post->post_title ) ) {
            return false;
        }
        if ( ! in_array( $post->post_type, $this->wacdmg_get_auto_seo_post_types(), true ) ) {
            return false;
        }
        $creds = get_option( 'wacdmg_ai_creds', array() );
        if ( empty( $creds['auto_seo_on_publish'] ) || ! class_exists( 'WACDMG_SEO' ) ) {
            return false;
        }
        $existing = ( new WACDMG_SEO() )->wacdmg_get_existing_seo_meta( $post->ID );
        return empty( $existing['seo_title'] ) && empty( $existing['meta_description'] );
    }

    /**
     * Queue SEO meta generation when a post is first published and its meta is empty.
     *
     * Runs in the background (Action Scheduler when available, otherwise WP-Cron)
     * so publishing never waits on the AI provider.
     *
     * @param string  $new_status New post status.
     * @param string  $old_status Previous post status.
     * @param WP_Post $post       Post object.
     */
    public function wacdmg_auto_seo_on_publish( $new_status, $old_status, $post ) {
        if ( 'publish' !== $new_status || 'publish' === $old_status ) {
            return;
        }
        if ( ! $post instanceof WP_Post || wp_is_post_autosave( $post->ID ) || wp_is_post_revision( $post->ID ) ) {
            return;
        }
        if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ( defined( 'WP_IMPORTING' ) && WP_IMPORTING ) ) {
            return;
        }

        // The status is not saved yet during this hook, so check eligibility without it.
        $check              = clone $post;
        $check->post_status = 'publish';
        if ( ! $this->wacdmg_auto_seo_is_eligible( $check ) ) {
            return;
        }

        // Run the job as the publishing user; scheduled posts publish from cron, so fall back to the author.
        $user_id = get_current_user_id();
        if ( ! $user_id || ! user_can( $user_id, 'edit_post', $post->ID ) ) {
            $user_id = (int) $post->post_author;
        }
        if ( ! $user_id || ! user_can( $user_id, 'edit_post', $post->ID ) ) {
            return;
        }

        $args = array( (int) $post->ID, $user_id );

        if ( function_exists( 'as_enqueue_async_action' ) ) {
            if ( ! function_exists( 'as_has_scheduled_action' ) || ! as_has_scheduled_action( 'wacdmg_auto_seo_job', $args, 'wacdmg' ) ) {
                as_enqueue_async_action( 'wacdmg_auto_seo_job', $args, 'wacdmg' );
            }
            return;
        }

        if ( ! wp_next_scheduled( 'wacdmg_auto_seo_job', $args ) ) {
            wp_schedule_single_event( time(), 'wacdmg_auto_seo_job', $args );
        }
    }

    /**
     * Background job: generate and write SEO meta for one published post.
     *
     * @param int $post_id Post ID.
     * @param int $user_id User the job runs as.
     */
    public function wacdmg_run_auto_seo_job( $post_id, $user_id ) {
        $post_id = (int) $post_id;
        $user_id = (int) $user_id;
        $post    = get_post( $post_id );

        if ( ! $user_id || ! user_can( $user_id, 'edit_post', $post_id ) ) {
            return;
        }
        // Re-check: the post may have been unpublished, or meta filled in, since it was queued.
        if ( ! $this->wacdmg_auto_seo_is_eligible( $post ) || $this->wacdmg_is_over_daily_limit() ) {
            return;
        }

        $previous_user = get_current_user_id();
        wp_set_current_user( $user_id );

        $title   = wp_strip_all_tags( $post->post_title );
        $content = wp_strip_all_tags( strip_shortcodes( $post->post_excerpt ? $post->post_excerpt : $post->post_content ) );
        $content = wp_trim_words( $content, 80, '' );

        $prompt = 'Write SEO meta for a web page.'
            . "\nPage title: \"" . $title . '"'
            . ( '' !== $content ? "\nContent preview: \"" . $content . '"' : '' )
            . "\n\nReturn a JSON object with exactly these keys:"
            . "\n- \"seo_title\": under 60 characters"
            . "\n- \"meta_description\": 140-160 characters"
            . "\n- \"focus_keywords\": 3-5 keywords as one comma-separated string"
            . "\nWrite in the same language as the page title and content."
            . "\nReturn only the JSON object, with no code fences or explanation.";

        $result = $this->wacdmg_run_ai_prompt( $prompt );
        if ( $result['success'] ) {
            $meta = $this->wacdmg_parse_seo_json( $result['description'] );
            if ( ! empty( $meta ) ) {
                ( new WACDMG_SEO() )->wacdmg_write_seo_meta( $post_id, $meta );
                $this->wacdmg_log_usage( 'seo_meta' );
            }
        }

        wp_set_current_user( $previous_user );
    }

    /**
     * Parse a JSON SEO response from the model.
     *
     * Tolerates code fences or text around the JSON object.
     *
     * @param string $raw Model output.
     * @return array seo_title, meta_description, focus_keywords (only non-empty keys).
     */
    private function wacdmg_parse_seo_json( $raw ) {
        $raw   = (string) $raw;
        $start = strpos( $raw, '{' );
        $end   = strrpos( $raw, '}' );
        if ( false === $start || false === $end || $end <= $start ) {
            return array();
        }

        $data = json_decode( substr( $raw, $start, $end - $start + 1 ), true );
        if ( ! is_array( $data ) ) {
            return array();
        }

        $meta = array();
        foreach ( array( 'seo_title', 'meta_description', 'focus_keywords' ) as $key ) {
            $value = isset( $data[ $key ] ) ? $data[ $key ] : '';
            if ( is_array( $value ) ) {
                $value = implode( ', ', array_filter( array_map( 'strval', $value ) ) );
            }
            $value = trim( wp_strip_all_tags( (string) $value ) );
            if ( '' !== $value ) {
                $meta[ $key ] = $value;
            }
        }
        return $meta;
    }

    /**
     * Endpoint to fetch available models for a provider
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_fetch_models( WP_REST_Request $request ) {
        $provider = sanitize_text_field( $request->get_param( 'provider' ) );
        $api_key  = sanitize_text_field( $request->get_param( 'api_key' ) );

        if ( empty( $provider ) ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Provider is required.' ), 400 );
        }

        if ( empty( $api_key ) || strpos( $api_key, '•' ) !== false ) {
            $settings = get_option( 'wacdmg_ai_creds', array() );
            switch ( $provider ) {
                case 'chatgpt':
                case 'openai_image':
                    $api_key = $settings['chatgpt_key'] ?? ( $settings['apiKey'] ?? '' );
                    break;
                case 'groq':
                    $api_key = $settings['groq_key'] ?? '';
                    break;
                case 'gemini':
                    $api_key = $settings['gemini_key'] ?? '';
                    break;
                case 'claude':
                    $api_key = $settings['claude_key'] ?? '';
                    break;
                case 'mistral':
                    $api_key = $settings['mistral_key'] ?? '';
                    break;
                case 'openrouter':
                    $api_key = $settings['openrouter_key'] ?? '';
                    break;
                case 'together_image':
                    $api_key = $settings['together_key'] ?? '';
                    break;
            }
        }

        if ( empty( $api_key ) && $provider !== 'openrouter' ) {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'API Key is empty.' ), 400 );
        }

        $models = $this->wacdmg_fetch_models_from_provider( $provider, $api_key );

        if ( ! empty( $models ) ) {
            return new WP_REST_Response( array( 'success' => true, 'models' => $models ), 200 );
        } else {
            return new WP_REST_Response( array( 'success' => false, 'message' => 'Could not retrieve models. Please check your API key.' ), 200 );
        }
    }

    /**
     * Fetch models list directly from provider API
     *
     * @param string $provider The provider key.
     * @param string $api_key  API Key.
     * @return array List of models as value/label pairs, or empty array.
     */
    private function wacdmg_fetch_models_from_provider( $provider, $api_key ) {
        $models = array();

        switch ( $provider ) {
            case 'chatgpt':
                $response = wp_remote_get( 'https://api.openai.com/v1/models', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        // Text-generation models only: skip image, audio, speech, embedding, and moderation models.
                        $excluded = array( 'embed', 'moderation', 'realtime', 'audio', 'image', 'tts', 'transcribe', 'whisper', 'dall-e', 'search' );
                        foreach ( $data['data'] as $m ) {
                            $id = isset( $m['id'] ) ? (string) $m['id'] : '';
                            if ( ! preg_match( '/^(gpt-|o\d)/', $id ) ) {
                                continue;
                            }
                            foreach ( $excluded as $needle ) {
                                if ( false !== strpos( $id, $needle ) ) {
                                    continue 2;
                                }
                            }
                            $models[] = array( 'value' => $id, 'label' => $id );
                        }
                        // Sort models alphabetically
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'openai_image':
                $response = wp_remote_get( 'https://api.openai.com/v1/models', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        foreach ( $data['data'] as $m ) {
                            $id = isset( $m['id'] ) ? (string) $m['id'] : '';
                            if ( 0 === strpos( $id, 'gpt-image' ) ) {
                                $models[] = array( 'value' => $id, 'label' => $id );
                            }
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $b['label'], $a['label'] ); } );
                    }
                }
                break;

            case 'together_image':
                $response = wp_remote_get( 'https://api.together.xyz/v1/models', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    // Together returns a plain array; tolerate an OpenAI-style { data: [] } wrapper too.
                    $list = ( is_array( $data ) && isset( $data['data'] ) && is_array( $data['data'] ) ) ? $data['data'] : $data;
                    if ( is_array( $list ) ) {
                        foreach ( $list as $m ) {
                            if ( ! is_array( $m ) || empty( $m['id'] ) || ( $m['type'] ?? '' ) !== 'image' ) {
                                continue;
                            }
                            $models[] = array(
                                'value' => (string) $m['id'],
                                'label' => ! empty( $m['display_name'] ) ? (string) $m['display_name'] : (string) $m['id'],
                            );
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'groq':
                $response = wp_remote_get( 'https://api.groq.com/openai/v1/models', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        foreach ( $data['data'] as $m ) {
                            $id = $m['id'];
                            if ( strpos( $id, 'whisper' ) === false && strpos( $id, 'guard' ) === false ) {
                                $models[] = array( 'value' => $id, 'label' => $id );
                            }
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'gemini':
                $response = wp_remote_get( 'https://generativelanguage.googleapis.com/v1beta/models?pageSize=1000', array(
                    'headers' => array( 'x-goog-api-key' => $api_key ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['models'] ) && is_array( $data['models'] ) ) {
                        foreach ( $data['models'] as $m ) {
                            $name = $m['name'];
                            $val = str_replace( 'models/', '', $name );
                            $display = $m['displayName'] ?? $val;
                            $methods = $m['supportedGenerationMethods'] ?? array();
                            if ( in_array( 'generateContent', $methods ) ) {
                                $models[] = array( 'value' => $val, 'label' => $display );
                            }
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'claude':
                $response = wp_remote_get( 'https://api.anthropic.com/v1/models', array(
                    'headers' => array(
                        'x-api-key'         => $api_key,
                        'anthropic-version' => '2023-06-01',
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        foreach ( $data['data'] as $m ) {
                            $id = $m['id'];
                            $display = $m['display_name'] ?? $id;
                            $models[] = array( 'value' => $id, 'label' => $display );
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'mistral':
                $response = wp_remote_get( 'https://api.mistral.ai/v1/models', array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $api_key,
                    ),
                    'timeout' => 15,
                ) );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        foreach ( $data['data'] as $m ) {
                            $id = $m['id'];
                            if ( strpos( $id, 'embed' ) === false ) {
                                $models[] = array( 'value' => $id, 'label' => $id );
                            }
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;

            case 'openrouter':
                $args = array( 'timeout' => 15 );
                if ( ! empty( $api_key ) ) {
                    $args['headers'] = array( 'Authorization' => 'Bearer ' . $api_key );
                }
                $response = wp_remote_get( 'https://openrouter.ai/api/v1/models', $args );
                if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) === 200 ) {
                    $data = json_decode( wp_remote_retrieve_body( $response ), true );
                    if ( isset( $data['data'] ) && is_array( $data['data'] ) ) {
                        foreach ( $data['data'] as $m ) {
                            $id = $m['id'];
                            $name = $m['name'] ?? $id;
                            $models[] = array( 'value' => $id, 'label' => $name );
                        }
                        usort( $models, function( $a, $b ) { return strcmp( $a['label'], $b['label'] ); } );
                    }
                }
                break;
        }

        return $models;
    }

    /**
     * Handle AI prompt via OpenRouter
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key OpenRouter API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_openrouter( $prompt, $api_key, $model = '', $image = null ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'OpenRouter API key is missing.' );
        }

        $response = wp_remote_post( 'https://openrouter.ai/api/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
                'HTTP-Referer'  => esc_url( home_url() ),
                'X-Title'       => 'WordPress AI Assistant',
            ),
            'body'    => json_encode( array(
                'model'    => $model,
                'messages' => array(
                    array( 'role' => 'system', 'content' => 'You are a professional WordPress content writer and SEO expert. Always follow user instructions precisely.' ),
                    array( 'role' => 'user', 'content' => $this->wacdmg_openai_user_content( $prompt, $image ) ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        return $this->wacdmg_parse_openai_response( $response );
    }
}
