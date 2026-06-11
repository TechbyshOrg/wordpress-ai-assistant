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
    }

    /**
     * Register custom REST API routes
     *
     * @since 1.0.0
     */
    public function wacdmg_register_routes() {
        // Text generation endpoints
        $this->wacdmg_register_route( '/generate-description', array( $this, 'wacdmg_generate_description' ), 'POST' );
        $this->wacdmg_register_route( '/generate-paragraph-content', array( $this, 'wacdmg_generate_description' ), 'POST' );
        $this->wacdmg_register_route( '/generate-short-description', array( $this, 'wacdmg_generate_short_description' ), 'POST' );
        $this->wacdmg_register_route( '/generate-tags', array( $this, 'wacdmg_generate_tags' ), 'POST' );
        $this->wacdmg_register_route( '/generate-seo-meta', array( $this, 'wacdmg_generate_seo_meta' ), 'POST' );
        $this->wacdmg_register_route( '/generate-alt-text', array( $this, 'wacdmg_generate_alt_text' ), 'POST' );
        $this->wacdmg_register_route( '/chat', array( $this, 'wacdmg_chat' ), 'POST' );

        // Image generation endpoints
        $this->wacdmg_register_route( '/generate-image', array( $this, 'wacdmg_generate_image' ), 'POST' );

        // Settings endpoints
        $this->wacdmg_register_route( '/save-settings', array( $this, 'wacdmg_save_settings' ), 'POST' );
        $this->wacdmg_register_route( '/get-settings', array( $this, 'wacdmg_get_settings' ), 'GET' );
        $this->wacdmg_register_route( '/test-connection', array( $this, 'wacdmg_test_connection' ), 'POST' );
        $this->wacdmg_register_route( '/fetch-models', array( $this, 'wacdmg_fetch_models' ), 'POST' );
        $this->wacdmg_register_route( '/get-usage', array( $this, 'wacdmg_get_usage' ), 'GET' );
        $this->wacdmg_register_route( '/reset-usage', array( $this, 'wacdmg_reset_usage' ), 'POST' );

        // Templates endpoints
        $this->wacdmg_register_route( '/save-template', array( $this, 'wacdmg_save_template' ), 'POST' );
        $this->wacdmg_register_route( '/get-templates', array( $this, 'wacdmg_get_templates' ), 'GET' );
        $this->wacdmg_register_route( '/delete-template', array( $this, 'wacdmg_delete_template' ), 'POST' );
    }

    /**
     * Register a custom REST API route
     *
     * @param string          $route      The route to register.
     * @param callable        $callback   The callback function for the route.
     * @param string          $method     The HTTP method (default: 'GET').
     * @param callable|string $permission The permission callback.
     */
    public function wacdmg_register_route( $route, $callback, $method = 'GET', $permission = '__return_true' ) {
        $namespace = defined( 'WACDMG_API_NAMESPACE' ) ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        register_rest_route( $namespace, $route, array(
            'methods'             => $method,
            'callback'            => $callback,
            'permission_callback' => $permission,
        ) );
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

        // Build settings array - support multiple API keys
        $settingsData = array(
            'provider'            => sanitize_text_field( $formData['provider'] ),
            'model'               => sanitize_text_field( $formData['model'] ?? '' ),
            'chatgpt_key'         => sanitize_text_field( $formData['chatgptKey'] ?? '' ),
            'groq_key'            => sanitize_text_field( $formData['groqKey'] ?? '' ),
            'gemini_key'          => sanitize_text_field( $formData['geminiKey'] ?? '' ),
            'claude_key'          => sanitize_text_field( $formData['claudeKey'] ?? '' ),
            'mistral_key'         => sanitize_text_field( $formData['mistralKey'] ?? '' ),
            'openrouter_key'      => sanitize_text_field( $formData['openrouterKey'] ?? '' ),
            'image_provider'      => sanitize_text_field( $formData['imageProvider'] ?? 'dalle' ),
            'together_key'        => sanitize_text_field( $formData['togetherKey'] ?? '' ),
            'image_size'          => sanitize_text_field( $formData['imageSize'] ?? '1024x1024' ),
            'image_quality'       => sanitize_text_field( $formData['imageQuality'] ?? 'standard' ),
            'image_style'         => sanitize_text_field( $formData['imageStyle'] ?? 'vivid' ),
            'seo_integration'     => sanitize_text_field( $formData['seoIntegration'] ?? 'auto' ),
            'auto_seo_on_publish' => ! empty( $formData['autoSeoOnPublish'] ),
            'rate_limit_day'      => intval( $formData['rateLimitDay'] ?? 100 ),
            // Legacy field for backwards compat
            'apiKey'              => sanitize_text_field( $formData['chatgptKey'] ?? '' ),
        );

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
                $result = $this->wacdmg_handle_ai_prompt_chatgpt( $test_prompt, $api_key, $model ?: 'gpt-4o-mini' );
                break;
            case 'groq':
                $result = $this->wacdmg_handle_ai_prompt_groq( $test_prompt, $api_key, $model ?: 'llama-3.1-8b-instant' );
                break;
            case 'gemini':
                $result = $this->wacdmg_handle_ai_prompt_gemini( $test_prompt, $api_key, $model ?: 'gemini-3.5-flash' );
                break;
            case 'claude':
                $result = $this->wacdmg_handle_ai_prompt_claude( $test_prompt, $api_key, $model ?: 'claude-sonnet-4-6' );
                break;
            case 'mistral':
                $result = $this->wacdmg_handle_ai_prompt_mistral( $test_prompt, $api_key, $model ?: 'mistral-small-latest' );
                break;
            case 'openrouter':
                $result = $this->wacdmg_handle_ai_prompt_openrouter( $test_prompt, $api_key, $model ?: 'openai/gpt-4o-mini' );
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
     * Test Together.ai API key using a lightweight completion
     *
     * @param string $api_key Together.ai API key.
     * @return array
     */
    private function wacdmg_test_together_key( $api_key ) {
        $response = wp_remote_post( 'https://api.together.xyz/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'      => 'meta-llama/Llama-3-8b-chat-hf',
                'messages'   => array( array( 'role' => 'user', 'content' => 'test' ) ),
                'max_tokens' => 5,
            ) ),
            'timeout' => 15,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && isset( $data['choices'][0]['message']['content'] ) ) {
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
        $this->wacdmg_log_usage( 'description' );

        if ( $result['success'] ) {
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'description' => $result['description'],
                    'content'     => $result['description'], // block-app uses 'content' key
                ),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => $result['error'] ),
        ), 500 );
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
        $this->wacdmg_log_usage( 'short_description' );

        if ( $result['success'] ) {
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'short_description' => $result['description'] ),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => $result['error'] ),
        ), 500 );
    }

    /**
     * Generate product/post tags endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_tags( WP_REST_Request $request ) {
        $prompt = $request->get_param( 'prompt' );
        $result = $this->wacdmg_run_ai_prompt( $prompt );
        $this->wacdmg_log_usage( 'tags' );

        if ( $result['success'] ) {
            // Parse comma-separated tags from AI response
            $raw  = strip_tags( $result['description'] );
            $tags = array_map( 'trim', explode( ',', $raw ) );
            $tags = array_filter( $tags );
            $tags = array_values( $tags );

            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array(
                    'tags' => $tags,
                    'raw'  => $raw,
                ),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => $result['error'] ),
        ), 500 );
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

        $results = array();

        // Generate SEO title
        if ( ! empty( $title_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $title_prompt );
            $results['seo_title'] = $r['success'] ? strip_tags( $r['description'] ) : '';
        }

        // Generate meta description
        if ( ! empty( $desc_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $desc_prompt );
            $results['meta_description'] = $r['success'] ? strip_tags( $r['description'] ) : '';
        }

        // Generate focus keywords
        if ( ! empty( $kw_prompt ) ) {
            $r = $this->wacdmg_run_ai_prompt( $kw_prompt );
            $results['focus_keywords'] = $r['success'] ? strip_tags( $r['description'] ) : '';
        }

        // Write to SEO plugins if post_id provided
        if ( $post_id && ! empty( $results ) ) {
            if ( class_exists( 'WACDMG_SEO' ) ) {
                $seo = new WACDMG_SEO();
                $seo->wacdmg_write_seo_meta( $post_id, $results );
            }
        }

        $this->wacdmg_log_usage( 'seo_meta' );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => $results,
        ), 200 );
    }

    /**
     * Generate alt text for an image endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response
     */
    public function wacdmg_generate_alt_text( WP_REST_Request $request ) {
        $prompt       = $request->get_param( 'prompt' );
        $attachment_id = intval( $request->get_param( 'attachment_id' ) );

        $result = $this->wacdmg_run_ai_prompt( $prompt );
        $this->wacdmg_log_usage( 'alt_text' );

        if ( $result['success'] ) {
            $alt = strip_tags( $result['description'] );

            // Save alt text to attachment if ID provided
            if ( $attachment_id ) {
                update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
            }

            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'alt_text' => $alt ),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => $result['error'] ),
        ), 500 );
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
        $this->wacdmg_log_usage( 'chat' );

        if ( $result['success'] ) {
            return new WP_REST_Response( array(
                'success' => true,
                'data'    => array( 'reply' => $result['description'] ),
            ), 200 );
        }

        return new WP_REST_Response( array(
            'success' => false,
            'data'    => array( 'message' => $result['error'] ),
        ), 500 );
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
        $prompt       = sanitize_text_field( $request->get_param( 'prompt' ) );
        $size         = sanitize_text_field( $request->get_param( 'size' ) ?: '1024x1024' );
        $quality      = sanitize_text_field( $request->get_param( 'quality' ) ?: 'standard' );
        $style        = sanitize_text_field( $request->get_param( 'style' ) ?: 'vivid' );
        $save_to_lib  = (bool) $request->get_param( 'save_to_library' );
        $post_id      = intval( $request->get_param( 'post_id' ) );
        $set_featured = (bool) $request->get_param( 'set_as_featured' );

        if ( empty( $prompt ) ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => 'Image prompt is required.' ),
            ), 400 );
        }

        $aiCred        = get_option( 'wacdmg_ai_creds', array() );
        $image_provider = $aiCred['image_provider'] ?? 'dalle';

        if ( $image_provider === 'together' && ! empty( $aiCred['together_key'] ) ) {
            $result = $this->wacdmg_generate_image_together( $prompt, $aiCred['together_key'], $size );
        } else {
            // Default: DALL-E 3 via OpenAI
            $api_key = $aiCred['chatgpt_key'] ?? ( $aiCred['apiKey'] ?? '' );
            if ( empty( $api_key ) ) {
                return new WP_REST_Response( array(
                    'success' => false,
                    'data'    => array( 'message' => 'OpenAI API key is required for DALL-E image generation.' ),
                ), 400 );
            }
            $result = $this->wacdmg_generate_image_dalle( $prompt, $api_key, $size, $quality, $style );
        }

        if ( ! $result['success'] ) {
            return new WP_REST_Response( array(
                'success' => false,
                'data'    => array( 'message' => $result['error'] ),
            ), 500 );
        }

        $image_url     = $result['url'];
        $attachment_id = null;

        // Save to Media Library
        if ( $save_to_lib && ! empty( $image_url ) ) {
            if ( class_exists( 'WACDMG_Image_AI' ) ) {
                $img_ai        = new WACDMG_Image_AI();
                $attachment_id = $img_ai->wacdmg_save_image_to_library( $image_url, $prompt );
            }

            // Set as featured image
            if ( $attachment_id && $post_id && $set_featured ) {
                set_post_thumbnail( $post_id, $attachment_id );
            }
        }

        $this->wacdmg_log_usage( 'image' );

        return new WP_REST_Response( array(
            'success' => true,
            'data'    => array(
                'url'           => $image_url,
                'attachment_id' => $attachment_id,
                'revised_prompt'=> $result['revised_prompt'] ?? '',
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
        $id         = sanitize_key( uniqid( 'tpl_', true ) );
        $templates[ $id ] = array(
            'id'       => $id,
            'name'     => sanitize_text_field( $template['name'] ),
            'prompt'   => sanitize_textarea_field( $template['prompt'] ),
            'type'     => sanitize_text_field( $template['type'] ?? 'general' ),
            'created'  => current_time( 'mysql' ),
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
        $templates = get_option( 'wacdmg_templates', array() );

        // Add built-in defaults if empty
        if ( empty( $templates ) ) {
            $templates = $this->wacdmg_get_default_templates();
        }

        return new WP_REST_Response( array( 'success' => true, 'data' => array_values( $templates ) ), 200 );
    }

    /**
     * Delete a template
     *
     * @param WP_REST_Request $request
     * @return WP_REST_Response
     */
    public function wacdmg_delete_template( WP_REST_Request $request ) {
        $id        = sanitize_key( $request->get_param( 'id' ) );
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
     * @param string $prompt The prompt to send.
     * @param string $type   Optional type hint (e.g. 'chat').
     * @return array Result array with 'success', 'description', and 'error' keys.
     */
    private function wacdmg_run_ai_prompt( $prompt, $type = 'text' ) {
        if ( empty( $prompt ) ) {
            return array( 'success' => false, 'error' => 'Prompt is required.' );
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
            return apply_filters( 'wacdmg_handle_ai_prompt_other', $default, $prompt, $aiCred );
        }

        switch ( $provider ) {
            case 'chatgpt':
                $api_key = $aiCred['chatgpt_key'] ?? ( $aiCred['apiKey'] ?? '' );
                return $this->wacdmg_handle_ai_prompt_chatgpt( $prompt, $api_key, $model ?: 'gpt-4o' );

            case 'groq':
                $api_key = $aiCred['groq_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_groq( $prompt, $api_key, $model ?: 'llama-3.3-70b-versatile' );

            case 'gemini':
                $api_key = $aiCred['gemini_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_gemini( $prompt, $api_key, $model ?: 'gemini-3.5-flash' );

            case 'claude':
                $api_key = $aiCred['claude_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_claude( $prompt, $api_key, $model ?: 'claude-sonnet-4-6' );

            case 'mistral':
                $api_key = $aiCred['mistral_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_mistral( $prompt, $api_key, $model ?: 'mistral-large-latest' );

            case 'openrouter':
                $api_key = $aiCred['openrouter_key'] ?? '';
                return $this->wacdmg_handle_ai_prompt_openrouter( $prompt, $api_key, $model ?: 'openai/gpt-4o' );

            default:
                return array( 'success' => false, 'error' => 'Unsupported AI provider: ' . esc_html( $provider ) );
        }
    }

    // =========================================================================
    // AI PROVIDER HANDLERS — TEXT
    // =========================================================================

    /**
     * Handle AI prompt via OpenAI ChatGPT
     *
     * @param string $prompt  The prompt text.
     * @param string $api_key OpenAI API key.
     * @param string $model   Model identifier.
     * @return array
     */
    private function wacdmg_handle_ai_prompt_chatgpt( $prompt, $api_key, $model = 'gpt-4o' ) {
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
                    array( 'role' => 'user', 'content' => $prompt ),
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
    private function wacdmg_handle_ai_prompt_groq( $prompt, $api_key, $model = 'llama-3.3-70b-versatile' ) {
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
                    array( 'role' => 'user', 'content' => $prompt ),
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
    private function wacdmg_handle_ai_prompt_gemini( $prompt, $api_key, $model = 'gemini-3.5-flash' ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Google Gemini API key is missing.' );
        }

        $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$api_key}";

        $response = wp_remote_post( $endpoint, array(
            'headers' => array( 'Content-Type' => 'application/json' ),
            'body'    => json_encode( array(
                'contents'         => array(
                    array(
                        'role'  => 'user',
                        'parts' => array( array( 'text' => $prompt ) ),
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
    private function wacdmg_handle_ai_prompt_claude( $prompt, $api_key, $model = 'claude-sonnet-4-6' ) {
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
                    array( 'role' => 'user', 'content' => $prompt ),
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
    private function wacdmg_handle_ai_prompt_mistral( $prompt, $api_key, $model = 'mistral-large-latest' ) {
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
                    array( 'role' => 'user', 'content' => $prompt ),
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
     * Generate image via OpenAI DALL-E 3
     *
     * @param string $prompt  The image prompt.
     * @param string $api_key OpenAI API key.
     * @param string $size    Image size (e.g. '1024x1024').
     * @param string $quality Image quality ('standard' or 'hd').
     * @param string $style   Image style ('vivid' or 'natural').
     * @return array
     */
    private function wacdmg_generate_image_dalle( $prompt, $api_key, $size = '1024x1024', $quality = 'standard', $style = 'vivid' ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'OpenAI API key is missing.' );
        }

        $response = wp_remote_post( 'https://api.openai.com/v1/images/generations', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'           => 'dall-e-3',
                'prompt'          => $prompt,
                'n'               => 1,
                'size'            => $size,
                'quality'         => $quality,
                'style'           => $style,
                'response_format' => 'url',
            ) ),
            'timeout' => 120,
        ) );

        if ( is_wp_error( $response ) ) {
            return array( 'success' => false, 'error' => $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( $code === 200 && isset( $data['data'][0]['url'] ) ) {
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
    private function wacdmg_generate_image_together( $prompt, $api_key, $size = '1024x1024' ) {
        if ( empty( $api_key ) ) {
            return array( 'success' => false, 'error' => 'Together.ai API key is missing.' );
        }

        // Parse width/height from size string
        $dimensions = explode( 'x', $size );
        $width  = intval( $dimensions[0] ?? 1024 );
        $height = intval( $dimensions[1] ?? 1024 );

        $response = wp_remote_post( 'https://api.together.xyz/v1/images/generations', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body'    => json_encode( array(
                'model'  => 'black-forest-labs/FLUX.1-schnell-Free',
                'prompt' => $prompt,
                'width'  => $width,
                'height' => $height,
                'steps'  => 4,
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

        if ( $code === 200 && isset( $data['data'][0]['url'] ) ) {
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
            'default_blog'        => array(
                'id'      => 'default_blog',
                'name'    => 'Blog Post Introduction',
                'prompt'  => 'Write an engaging blog post introduction for: [TITLE]. Hook the reader, explain what they will learn, and keep it under 150 words.',
                'type'    => 'post',
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
        );
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
                        foreach ( $data['data'] as $m ) {
                            $id = $m['id'];
                            // Filter chat/reasoning models
                            if ( ( strpos( $id, 'gpt-' ) !== false || strpos( $id, 'o1-' ) !== false || strpos( $id, 'o3-' ) !== false || $id === 'o1' ) 
                                 && strpos( $id, 'embed' ) === false 
                                 && strpos( $id, 'moderation' ) === false 
                                 && strpos( $id, 'realtime' ) === false 
                                 && strpos( $id, 'audio' ) === false ) {
                                $models[] = array( 'value' => $id, 'label' => $id );
                            }
                        }
                        // Sort models alphabetically
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
                $url = "https://generativelanguage.googleapis.com/v1beta/models?key=" . urlencode( $api_key );
                $response = wp_remote_get( $url, array( 'timeout' => 15 ) );
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
    private function wacdmg_handle_ai_prompt_openrouter( $prompt, $api_key, $model = 'openai/gpt-4o' ) {
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
                    array( 'role' => 'user', 'content' => $prompt ),
                ),
            ) ),
            'timeout' => 60,
        ) );

        return $this->wacdmg_parse_openai_response( $response );
    }
}
