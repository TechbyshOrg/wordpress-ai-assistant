<?php
/**
 * WACDMG Admin API Class
 *
 * Handles custom REST API endpoints for the plugin.
 *
 * @package WACDMG
 */

if (!defined('ABSPATH')) {
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
        $this->namespace = defined('WACDMG_API_NAMESPACE') ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    public function init_hooks() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }

    /**
     * Register custom REST API routes
     *
     * @since 1.0.0
     */
    public function register_routes() {
        $this->wacdmg_register_route('/generate-description', array($this, 'wacdmg_generate_description'), 'POST');
        $this->wacdmg_register_route('/save-settings', array($this, 'wacdmg_save_settings'), 'POST');
        $this->wacdmg_register_route('/generate-paragraph-content', array($this, 'wacdmg_generate_description'), 'POST');
    }

    /**
     * Register a custom REST API route
     *
     * @param string $route The route to register.
     * @param callable $callback The callback function for the route.
     * @param string $method The HTTP method (default: 'GET').
     * @param callable|string $permission The permission callback (default: '__return_true').
     */
    public function wacdmg_register_route($route, $callback, $method = 'GET', $permission = '__return_true') {
        $namespace = defined('WACDMG_API_NAMESPACE') ? WACDMG_API_NAMESPACE : 'wacdmg/v1';
        register_rest_route($namespace, $route, array(
            'methods'             => $method,
            'callback'            => $callback,
            'permission_callback' => $permission,
        ));
    }

    /**
     * Verify nonce for AJAX requests
     *
     * @since    1.0.0
     */
    private function wacdmg_verify_nonce() {
        if (!isset($_REQUEST['nonce']) || !check_ajax_referer('wacdmg_admin_nonce', 'nonce', false)) {
            wp_send_json(array('success' => false, 'message' => 'Security check failed'));
            exit;
        }
    }

    /**
     * Save settings endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The response object containing the result of the operation.
     */
    public function wacdmg_save_settings(WP_REST_Request $request) {
        $settings = $request->get_json_params();
        $formData = $request->get_param('formData');

        if (empty($formData['apiKey'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'data'    => array('message' => 'API key is required.')
            ), 400);
        }

        if (empty($formData['provider'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'data'    => array('message' => 'Invalid provider selected.')
            ), 400);
        }

        $settingsData = array(
            'apiKey'  => sanitize_text_field($formData['apiKey']),
            'provider' => sanitize_text_field($formData['provider']),
            'model' => sanitize_text_field($formData['model'])
        );

        update_option('wacdmg_ai_creds', $settingsData);

        return new WP_REST_Response(array(
            'success' => true,
            'data'    => array('message' => 'Settings saved successfully.')
        ), 200);
    }

    /**
     * Generate paragraph content endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The response object containing the generated content.
     */
    public function wacdmg_generate_paragraph_content(WP_REST_Request $request) {
        $prompt = $request->get_param('prompt');
        $method = $request->get_param('method');

        if (empty($prompt)) {
            return new WP_REST_Response(array(
                'success' => false,
                'data'    => array('message' => 'Prompt is required.')
            ), 400);
        }

        // Here you can implement the logic to generate paragraph content based on the prompt
        // For now, we will just return a dummy response
        return new WP_REST_Response(array(
            'success' => true,
            'data'    => array('content' => 'Generated content based on the prompt: ' . $prompt)
        ), 200);
    }

    /**
     * Generate description endpoint
     *
     * @param WP_REST_Request $request The REST request object.
     * @return WP_REST_Response The response object containing the generated description.
     */
    public function wacdmg_generate_description(WP_REST_Request $request) {
        $prompt = $request->get_param('prompt');
        $method = $request->get_param('method');

        $aiCred = get_option('wacdmg_ai_creds');
        if (empty($aiCred) || empty($aiCred['apiKey']) || empty($aiCred['provider']) || empty($aiCred['model'])) {
            return new WP_REST_Response(array(
                'success' => false,
                'data' => array(
                    'message' => 'AI credentials are missing or incomplete. Please check your settings.',
                )
            ), 400);
        }
        $provider = isset($aiCred['provider']) ? $aiCred['provider'] : '';

        $other_provider = apply_filters('wacdmg_use_additional_provider', false);
        
        if ($other_provider) {
            $default_value = array(
            'success' => false,
            'error'   => 'Other provider not implemented yet.'
            );
            $result = apply_filters('wacdmg_handle_ai_prompt_other', $default_value, $prompt, $aiCred['apiKey'], $aiCred['model']);
        } elseif ($provider && $provider === 'groq') {
            $result = $this->wacdmg_handle_ai_prompt_groq($prompt, $aiCred['apiKey']);
        } elseif ($provider && $provider === 'chatgpt') {
            $result = $this->wacdmg_handle_ai_prompt_chatgpt($prompt, $aiCred['apiKey']);
        } else {
            $result = array(
            'success' => false,
            'error'   => 'Unsupported AI provider selected.'
            );
        }

        
        if ($result['success']) {
            return new WP_REST_Response(array(
                'success' => true,
                'data' => array(
                    'description' => $result['description'],
                    'post_id'     => null, // Or get the real post ID if needed
                )
            ), 200);
        }

        return new WP_REST_Response(array(
            'success' => false,
            'data' => array(
                'message' => $result['error'],
            )
        ), 500);
    }

    /**
     * Handle AI prompt for Groq API
     *
     * @param string $prompt The prompt to send to the AI.
     * @param string $api_key The API key for authentication.
     * @param string $model The model to use (default: 'llama3-8b-8192').
     * @return array The response from the AI API.
     */
    private function wacdmg_handle_ai_prompt_groq($prompt, $api_key, $model = 'llama3-8b-8192') {
        $response = wp_remote_post('https://api.groq.com/openai/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array("role" => "system", "content" => "You are a helpful assistant."),
                    array("role" => "user", "content" => $prompt),
                )
            )),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 200 && !empty($body)) {
            $data = json_decode($body, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return array(
                    'success'     => true,
                    'description' => $data['choices'][0]['message']['content']
                );
            } else {
                return array(
                    'success' => false,
                    'error'   => 'Invalid API response format.'
                );
            }
        }

        return array(
            'success' => false,
            'error'   => 'API returned HTTP ' . $code . ': ' . $body
        );
    }

    /**
     * [Function Name]
     *
     * [Brief description of what the function does.]
     *
     * @param [type] $[paramName] Description of the parameter.
     * @return [type] Description of the return value.
     */
    private function wacdmg_handle_ai_prompt_chatgpt($prompt, $api_key, $model = 'gpt-4o') {
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array("role" => "system", "content" => "You are a helpful assistant."),
                    array("role" => "user", "content" => $prompt),
                )
            )),
            'timeout' => 60
        ));

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error'   => $response->get_error_message()
            );
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        if ($code === 200 && !empty($body)) {
            $data = json_decode($body, true);
            if (isset($data['choices'][0]['message']['content'])) {
                return array(
                    'success'     => true,
                    'description' => $data['choices'][0]['message']['content']
                );
            } else {
                return array(
                    'success' => false,
                    'error'   => 'Invalid API response format.'
                );
            }
        }

        return array(
            'success' => false,
            'error'   => 'API returned HTTP ' . $code . ': ' . $body
        );
    }
}
