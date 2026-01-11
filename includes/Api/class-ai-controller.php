<?php
/**
 * AI REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for AI settings and generation.
 */
class Ai_Controller extends REST_Controller {

    /**
     * AI setting keys.
     *
     * @var array
     */
    private $ai_settings = [
        'wpseopilot_openai_api_key',
        'wpseopilot_ai_model',
        'wpseopilot_ai_prompt_system',
        'wpseopilot_ai_prompt_title',
        'wpseopilot_ai_prompt_description',
    ];

    /**
     * Default values for AI settings.
     *
     * @var array
     */
    private $defaults = [
        'wpseopilot_openai_api_key'         => '',
        'wpseopilot_ai_model'               => 'gpt-4o-mini',
        'wpseopilot_ai_prompt_system'       => 'You are an SEO assistant generating concise metadata. Respond with plain text only.',
        'wpseopilot_ai_prompt_title'        => 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.',
        'wpseopilot_ai_prompt_description'  => 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.',
    ];

    /**
     * Available AI models.
     *
     * @var array
     */
    private $models = [
        'gpt-4o-mini'  => 'GPT-4o mini (fast, affordable)',
        'gpt-4o'       => 'GPT-4o (highest quality)',
        'gpt-4.1-mini' => 'GPT-4.1 mini',
        'gpt-4.1'      => 'GPT-4.1',
        'gpt-3.5-turbo'=> 'GPT-3.5 Turbo',
    ];

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get all AI settings
        register_rest_route( $this->namespace, '/ai/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get available models
        register_rest_route( $this->namespace, '/ai/models', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_models' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Reset to defaults
        register_rest_route( $this->namespace, '/ai/reset', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'reset_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Test AI generation
        register_rest_route( $this->namespace, '/ai/generate', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'generate' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get API status
        register_rest_route( $this->namespace, '/ai/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_status' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all AI settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $settings = [];

        foreach ( $this->ai_settings as $key ) {
            $short_key = str_replace( 'wpseopilot_', '', $key );
            $value = get_option( $key );

            // Handle defaults
            if ( false === $value ) {
                $value = isset( $this->defaults[ $key ] ) ? $this->defaults[ $key ] : '';
            }

            // Mask the API key for security
            if ( 'openai_api_key' === $short_key && ! empty( $value ) ) {
                $settings['api_key_configured'] = true;
                $settings[ $short_key ] = $this->mask_api_key( $value );
            } else {
                $settings[ $short_key ] = $value;
            }
        }

        return $this->success( $settings );
    }

    /**
     * Update AI settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        foreach ( $params as $key => $value ) {
            $option_key = 'wpseopilot_' . $key;

            // Skip if not a valid AI setting
            if ( ! in_array( $option_key, $this->ai_settings, true ) ) {
                continue;
            }

            // Sanitize based on key
            if ( 'openai_api_key' === $key ) {
                // Don't update if it's the masked value
                if ( strpos( $value, '••••' ) !== false ) {
                    continue;
                }
                $value = sanitize_text_field( $value );
            } elseif ( 'ai_model' === $key ) {
                $value = sanitize_text_field( $value );
                // Validate model exists
                if ( ! isset( $this->models[ $value ] ) ) {
                    $value = 'gpt-4o-mini';
                }
            } else {
                $value = sanitize_textarea_field( $value );
            }

            update_option( $option_key, $value );
        }

        return $this->success( null, __( 'AI settings saved successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Get available AI models.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_models( $request ) {
        $models = [];

        foreach ( $this->models as $value => $label ) {
            $models[] = [
                'value' => $value,
                'label' => $label,
            ];
        }

        return $this->success( $models );
    }

    /**
     * Reset AI settings to defaults.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function reset_settings( $request ) {
        // Reset prompts and model to defaults (keep API key)
        $reset_keys = [
            'wpseopilot_ai_model',
            'wpseopilot_ai_prompt_system',
            'wpseopilot_ai_prompt_title',
            'wpseopilot_ai_prompt_description',
        ];

        foreach ( $reset_keys as $key ) {
            if ( isset( $this->defaults[ $key ] ) ) {
                update_option( $key, $this->defaults[ $key ] );
            }
        }

        // Return the reset values
        $settings = [];
        foreach ( $reset_keys as $key ) {
            $short_key = str_replace( 'wpseopilot_', '', $key );
            $settings[ $short_key ] = $this->defaults[ $key ];
        }

        return $this->success( $settings, __( 'AI settings restored to defaults.', 'wp-seo-pilot' ) );
    }

    /**
     * Test AI generation.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function generate( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $content = isset( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : '';
        $type = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'both';

        if ( empty( $content ) ) {
            return $this->error( __( 'Content is required for AI generation.', 'wp-seo-pilot' ), 'missing_content', 400 );
        }

        // Get API key
        $api_key = get_option( 'wpseopilot_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return $this->error( __( 'OpenAI API key is not configured.', 'wp-seo-pilot' ), 'no_api_key', 400 );
        }

        // Get settings
        $model = get_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );
        $system_prompt = get_option( 'wpseopilot_ai_prompt_system', $this->defaults['wpseopilot_ai_prompt_system'] );
        $title_prompt = get_option( 'wpseopilot_ai_prompt_title', $this->defaults['wpseopilot_ai_prompt_title'] );
        $description_prompt = get_option( 'wpseopilot_ai_prompt_description', $this->defaults['wpseopilot_ai_prompt_description'] );

        $results = [];

        // Generate title if requested
        if ( 'title' === $type || 'both' === $type ) {
            $title_result = $this->call_openai( $api_key, $model, $system_prompt, $title_prompt, $content, 60 );
            if ( is_wp_error( $title_result ) ) {
                return $this->error( $title_result->get_error_message(), 'api_error', 500 );
            }
            $results['title'] = $title_result;
        }

        // Generate description if requested
        if ( 'description' === $type || 'both' === $type ) {
            $desc_result = $this->call_openai( $api_key, $model, $system_prompt, $description_prompt, $content, 120 );
            if ( is_wp_error( $desc_result ) ) {
                return $this->error( $desc_result->get_error_message(), 'api_error', 500 );
            }
            $results['description'] = $desc_result;
        }

        return $this->success( $results, __( 'AI generation completed.', 'wp-seo-pilot' ) );
    }

    /**
     * Get API status.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_status( $request ) {
        $api_key = get_option( 'wpseopilot_openai_api_key', '' );

        if ( empty( $api_key ) ) {
            return $this->success( [
                'configured' => false,
                'status'     => 'not_configured',
                'message'    => __( 'API key not configured', 'wp-seo-pilot' ),
            ] );
        }

        // Optionally verify the key with a minimal request
        // For now, just check if it looks valid
        $is_valid_format = strpos( $api_key, 'sk-' ) === 0 && strlen( $api_key ) > 20;

        return $this->success( [
            'configured'   => true,
            'valid_format' => $is_valid_format,
            'status'       => $is_valid_format ? 'configured' : 'invalid_format',
            'message'      => $is_valid_format
                ? __( 'API key configured', 'wp-seo-pilot' )
                : __( 'API key format may be invalid', 'wp-seo-pilot' ),
        ] );
    }

    /**
     * Call OpenAI API.
     *
     * @param string $api_key     API key.
     * @param string $model       Model to use.
     * @param string $system      System prompt.
     * @param string $instruction User instruction.
     * @param string $content     Content to analyze.
     * @param int    $max_tokens  Max tokens for response.
     * @return string|\WP_Error
     */
    private function call_openai( $api_key, $model, $system, $instruction, $content, $max_tokens = 60 ) {
        $url = 'https://api.openai.com/v1/chat/completions';

        // Build the user message
        $user_message = $instruction . "\n\nContent:\n" . $content;

        $body = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => $system,
                ],
                [
                    'role'    => 'user',
                    'content' => $user_message,
                ],
            ],
            'max_tokens'  => $max_tokens,
            'temperature' => 0.3,
        ];

        $response = wp_remote_post( $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ],
            'body'    => wp_json_encode( $body ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = isset( $body['error']['message'] )
                ? $body['error']['message']
                : __( 'Unknown API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        if ( ! isset( $body['choices'][0]['message']['content'] ) ) {
            return new \WP_Error( 'invalid_response', __( 'Invalid response from OpenAI', 'wp-seo-pilot' ) );
        }

        return trim( $body['choices'][0]['message']['content'] );
    }

    /**
     * Mask API key for display.
     *
     * @param string $key API key.
     * @return string
     */
    private function mask_api_key( $key ) {
        if ( strlen( $key ) <= 8 ) {
            return str_repeat( '•', strlen( $key ) );
        }

        return substr( $key, 0, 4 ) . str_repeat( '•', strlen( $key ) - 8 ) . substr( $key, -4 );
    }
}
