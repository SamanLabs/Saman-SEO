<?php
/**
 * Assistants REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Load assistant classes.
require_once __DIR__ . '/Assistants/class-base-assistant.php';
require_once __DIR__ . '/Assistants/class-general-seo-assistant.php';
require_once __DIR__ . '/Assistants/class-seo-reporter-assistant.php';

use WPSEOPilot\Api\Assistants\General_SEO_Assistant;
use WPSEOPilot\Api\Assistants\SEO_Reporter_Assistant;

/**
 * REST API controller for AI assistants.
 */
class Assistants_Controller extends REST_Controller {

    /**
     * Registered assistants.
     *
     * @var array
     */
    private $assistants = [];

    /**
     * Custom models table name.
     *
     * @var string
     */
    private $custom_models_table;

    /**
     * Supported providers.
     *
     * @var array
     */
    private $providers = [
        'openai' => [
            'name'    => 'OpenAI',
            'api_url' => 'https://api.openai.com/v1/chat/completions',
        ],
        'anthropic' => [
            'name'    => 'Anthropic',
            'api_url' => 'https://api.anthropic.com/v1/messages',
        ],
        'google' => [
            'name'    => 'Google AI',
            'api_url' => 'https://generativelanguage.googleapis.com/v1beta/models/{model}:generateContent',
        ],
        'openai_compatible' => [
            'name'    => 'OpenAI Compatible',
            'api_url' => '',
        ],
        'lmstudio' => [
            'name'    => 'LM Studio',
            'api_url' => 'http://localhost:1234/v1/chat/completions',
        ],
        'ollama' => [
            'name'    => 'Ollama',
            'api_url' => 'http://localhost:11434/api/chat',
        ],
    ];

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->custom_models_table = $wpdb->prefix . 'wpseopilot_custom_models';

        // Register built-in assistants.
        $this->register_assistant( new General_SEO_Assistant() );
        $this->register_assistant( new SEO_Reporter_Assistant() );
    }

    /**
     * Register an assistant.
     *
     * @param \WPSEOPilot\Api\Assistants\Base_Assistant $assistant Assistant instance.
     */
    public function register_assistant( $assistant ) {
        $this->assistants[ $assistant->get_id() ] = $assistant;
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get all assistants.
        register_rest_route( $this->namespace, '/assistants', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_assistants' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get single assistant.
        register_rest_route( $this->namespace, '/assistants/(?P<id>[a-z0-9-]+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_assistant' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Chat with assistant.
        register_rest_route( $this->namespace, '/assistants/chat', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'chat' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Execute assistant action.
        register_rest_route( $this->namespace, '/assistants/action', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'execute_action' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all available assistants.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_assistants( $request ) {
        $assistants = [];

        foreach ( $this->assistants as $assistant ) {
            $assistants[] = [
                'id'               => $assistant->get_id(),
                'name'             => $assistant->get_name(),
                'description'      => $assistant->get_description(),
                'initial_message'  => $assistant->get_initial_message(),
                'suggested_prompts'=> $assistant->get_suggested_prompts(),
                'actions'          => $assistant->get_available_actions(),
            ];
        }

        return $this->success( $assistants );
    }

    /**
     * Get a single assistant.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_assistant( $request ) {
        $id = sanitize_text_field( $request->get_param( 'id' ) );

        if ( ! isset( $this->assistants[ $id ] ) ) {
            return $this->error( __( 'Assistant not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $assistant = $this->assistants[ $id ];

        return $this->success( [
            'id'               => $assistant->get_id(),
            'name'             => $assistant->get_name(),
            'description'      => $assistant->get_description(),
            'initial_message'  => $assistant->get_initial_message(),
            'suggested_prompts'=> $assistant->get_suggested_prompts(),
            'actions'          => $assistant->get_available_actions(),
        ] );
    }

    /**
     * Handle chat message.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function chat( $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $assistant_id = isset( $params['assistant'] ) ? sanitize_text_field( $params['assistant'] ) : '';
        $message = isset( $params['message'] ) ? sanitize_textarea_field( $params['message'] ) : '';
        $context = isset( $params['context'] ) ? $params['context'] : [];

        if ( empty( $assistant_id ) ) {
            return $this->error( __( 'Assistant ID is required.', 'wp-seo-pilot' ), 'missing_assistant', 400 );
        }

        if ( empty( $message ) ) {
            return $this->error( __( 'Message is required.', 'wp-seo-pilot' ), 'missing_message', 400 );
        }

        if ( ! isset( $this->assistants[ $assistant_id ] ) ) {
            return $this->error( __( 'Assistant not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $assistant = $this->assistants[ $assistant_id ];

        // Check if this is an action request.
        if ( ! empty( $context['action'] ) ) {
            $result = $assistant->process_action( $context['action'], $context );
            return $this->success( $result );
        }

        // Build prompt with context.
        $system_prompt = $assistant->get_system_prompt();
        $user_prompt = $assistant->build_prompt( $message, $context );

        // Call AI.
        $response = $this->call_ai( $system_prompt, $user_prompt );

        if ( is_wp_error( $response ) ) {
            return $this->error( $response->get_error_message(), 'ai_error', 500 );
        }

        // Parse response.
        $parsed = $assistant->parse_response( $response );

        return $this->success( $parsed );
    }

    /**
     * Execute an assistant action.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function execute_action( $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $assistant_id = isset( $params['assistant'] ) ? sanitize_text_field( $params['assistant'] ) : '';
        $action = isset( $params['action'] ) ? sanitize_text_field( $params['action'] ) : '';
        $context = isset( $params['context'] ) ? $params['context'] : [];

        if ( empty( $assistant_id ) || empty( $action ) ) {
            return $this->error( __( 'Assistant ID and action are required.', 'wp-seo-pilot' ), 'missing_params', 400 );
        }

        if ( ! isset( $this->assistants[ $assistant_id ] ) ) {
            return $this->error( __( 'Assistant not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $assistant = $this->assistants[ $assistant_id ];
        $result = $assistant->process_action( $action, $context );

        return $this->success( $result );
    }

    /**
     * Call AI API using configured model.
     *
     * @param string $system_prompt System prompt.
     * @param string $user_prompt   User prompt.
     * @return string|\WP_Error
     */
    private function call_ai( $system_prompt, $user_prompt ) {
        $model = get_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );

        // Check if using custom model.
        if ( strpos( $model, 'custom_' ) === 0 ) {
            $custom_id = intval( str_replace( 'custom_', '', $model ) );
            return $this->call_custom_model( $custom_id, $system_prompt, $user_prompt );
        }

        // Use default OpenAI.
        $api_key = get_option( 'wpseopilot_openai_api_key', '' );
        if ( empty( $api_key ) ) {
            return new \WP_Error( 'no_api_key', __( 'No API key configured. Please set up your AI provider in Settings.', 'wp-seo-pilot' ) );
        }

        return $this->call_openai( $api_key, $model, $system_prompt, $user_prompt );
    }

    /**
     * Call OpenAI API.
     *
     * @param string $api_key       API key.
     * @param string $model         Model ID.
     * @param string $system_prompt System prompt.
     * @param string $user_prompt   User prompt.
     * @return string|\WP_Error
     */
    private function call_openai( $api_key, $model, $system_prompt, $user_prompt ) {
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( [
                'model'       => $model,
                'messages'    => [
                    [ 'role' => 'system', 'content' => $system_prompt ],
                    [ 'role' => 'user', 'content' => $user_prompt ],
                ],
                'max_tokens'  => 1000,
                'temperature' => 0.7,
            ] ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['choices'][0]['message']['content'] ?? '' );
    }

    /**
     * Call custom model.
     *
     * @param int    $custom_id     Custom model ID.
     * @param string $system_prompt System prompt.
     * @param string $user_prompt   User prompt.
     * @return string|\WP_Error
     */
    private function call_custom_model( $custom_id, $system_prompt, $user_prompt ) {
        global $wpdb;

        $model = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$this->custom_models_table} WHERE id = %d", $custom_id ),
            ARRAY_A
        );

        if ( ! $model ) {
            return new \WP_Error( 'model_not_found', __( 'Custom model not found.', 'wp-seo-pilot' ) );
        }

        if ( ! $model['is_active'] ) {
            return new \WP_Error( 'model_inactive', __( 'Custom model is not active.', 'wp-seo-pilot' ) );
        }

        $provider = $model['provider'];
        $api_url = ! empty( $model['api_url'] ) ? $model['api_url'] : ( $this->providers[ $provider ]['api_url'] ?? '' );
        $api_key = $model['api_key'] ?? '';
        $model_id = $model['model_id'];
        $temperature = floatval( $model['temperature'] ?? 0.7 );
        $max_tokens = intval( $model['max_tokens'] ?? 1000 );

        switch ( $provider ) {
            case 'openai':
            case 'openai_compatible':
            case 'lmstudio':
                return $this->call_openai_compatible( $api_url, $api_key, $model_id, $system_prompt, $user_prompt, $max_tokens, $temperature );

            case 'anthropic':
                return $this->call_anthropic( $api_url, $api_key, $model_id, $system_prompt, $user_prompt, $max_tokens, $temperature );

            case 'google':
                return $this->call_google( $api_url, $api_key, $model_id, $system_prompt, $user_prompt, $max_tokens, $temperature );

            case 'ollama':
                return $this->call_ollama( $api_url, $model_id, $system_prompt, $user_prompt, $max_tokens, $temperature );

            default:
                return new \WP_Error( 'unsupported_provider', __( 'Unsupported provider.', 'wp-seo-pilot' ) );
        }
    }

    /**
     * Call OpenAI-compatible API.
     */
    private function call_openai_compatible( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $headers = [ 'Content-Type' => 'application/json' ];

        if ( ! empty( $api_key ) ) {
            $headers['Authorization'] = 'Bearer ' . $api_key;
        }

        $response = wp_remote_post( $api_url, [
            'headers' => $headers,
            'body'    => wp_json_encode( [
                'model'       => $model,
                'messages'    => [
                    [ 'role' => 'system', 'content' => $system ],
                    [ 'role' => 'user', 'content' => $prompt ],
                ],
                'max_tokens'  => $max_tokens,
                'temperature' => $temperature,
            ] ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['choices'][0]['message']['content'] ?? '' );
    }

    /**
     * Call Anthropic API.
     */
    private function call_anthropic( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $response = wp_remote_post( $api_url, [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body'    => wp_json_encode( [
                'model'      => $model,
                'max_tokens' => $max_tokens,
                'system'     => $system,
                'messages'   => [
                    [ 'role' => 'user', 'content' => $prompt ],
                ],
            ] ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'Anthropic API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['content'][0]['text'] ?? '' );
    }

    /**
     * Call Google AI API.
     */
    private function call_google( $api_url, $api_key, $model, $system, $prompt, $max_tokens, $temperature ) {
        $url = str_replace( '{model}', $model, $api_url ) . '?key=' . $api_key;

        $response = wp_remote_post( $url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'contents' => [
                    [
                        'parts' => [
                            [ 'text' => $system . "\n\n" . $prompt ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'maxOutputTokens' => $max_tokens,
                    'temperature'     => $temperature,
                ],
            ] ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'Google AI API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['candidates'][0]['content']['parts'][0]['text'] ?? '' );
    }

    /**
     * Call Ollama API.
     */
    private function call_ollama( $api_url, $model, $system, $prompt, $max_tokens, $temperature ) {
        $response = wp_remote_post( $api_url, [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'model'    => $model,
                'messages' => [
                    [ 'role' => 'system', 'content' => $system ],
                    [ 'role' => 'user', 'content' => $prompt ],
                ],
                'stream'   => false,
                'options'  => [
                    'temperature' => $temperature,
                    'num_predict' => $max_tokens,
                ],
            ] ),
            'timeout' => 120,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error'] ?? __( 'Ollama API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['message']['content'] ?? '' );
    }
}
