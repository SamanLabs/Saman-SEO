<?php
/**
 * Setup Wizard REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for setup wizard.
 */
class Setup_Controller extends REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get setup status
        register_rest_route( $this->namespace, '/setup/status', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_status' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Test API connection
        register_rest_route( $this->namespace, '/setup/test-api', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'test_api' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Complete setup
        register_rest_route( $this->namespace, '/setup/complete', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'complete_setup' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Skip setup
        register_rest_route( $this->namespace, '/setup/skip', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'skip_setup' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Reset setup (show wizard again)
        register_rest_route( $this->namespace, '/setup/reset', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'reset_setup' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get setup status.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_status( $request ) {
        $completed = get_option( 'wpseopilot_setup_completed', false );
        $skipped = get_option( 'wpseopilot_setup_skipped', false );
        $setup_data = get_option( 'wpseopilot_setup_data', [] );

        return $this->success( [
            'completed'   => (bool) $completed,
            'skipped'     => (bool) $skipped,
            'show_wizard' => ! $completed && ! $skipped,
            'setup_data'  => $setup_data,
        ] );
    }

    /**
     * Test API connection.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function test_api( $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        $provider = isset( $params['provider'] ) ? sanitize_text_field( $params['provider'] ) : 'openai';
        $api_key = isset( $params['api_key'] ) ? sanitize_text_field( $params['api_key'] ) : '';
        $model = isset( $params['model'] ) ? sanitize_text_field( $params['model'] ) : 'gpt-4o-mini';

        if ( empty( $api_key ) && $provider !== 'ollama' ) {
            return $this->error( __( 'API key is required.', 'wp-seo-pilot' ), 'missing_key', 400 );
        }

        $test_prompt = 'Say "Hello!" in one word.';

        switch ( $provider ) {
            case 'openai':
                $result = $this->test_openai( $api_key, $model, $test_prompt );
                break;
            case 'anthropic':
                $result = $this->test_anthropic( $api_key, $test_prompt );
                break;
            case 'ollama':
                $result = $this->test_ollama( $test_prompt );
                break;
            default:
                return $this->error( __( 'Unsupported provider.', 'wp-seo-pilot' ), 'invalid_provider', 400 );
        }

        if ( is_wp_error( $result ) ) {
            return $this->success( [
                'success' => false,
                'message' => $result->get_error_message(),
            ] );
        }

        return $this->success( [
            'success'  => true,
            'message'  => __( 'Connection successful!', 'wp-seo-pilot' ),
            'response' => $result,
        ] );
    }

    /**
     * Complete setup wizard.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function complete_setup( $request ) {
        $params = $request->get_json_params();
        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        // Save setup data
        $setup_data = [
            'site_type'     => isset( $params['site_type'] ) ? sanitize_text_field( $params['site_type'] ) : '',
            'primary_goal'  => isset( $params['primary_goal'] ) ? sanitize_text_field( $params['primary_goal'] ) : '',
            'industry'      => isset( $params['industry'] ) ? sanitize_text_field( $params['industry'] ) : '',
            'completed_at'  => current_time( 'mysql' ),
        ];

        update_option( 'wpseopilot_setup_data', $setup_data );

        // Save AI settings
        if ( ! empty( $params['ai_provider'] ) ) {
            update_option( 'wpseopilot_ai_active_provider', sanitize_text_field( $params['ai_provider'] ) );
        }

        if ( ! empty( $params['ai_api_key'] ) ) {
            update_option( 'wpseopilot_openai_api_key', sanitize_text_field( $params['ai_api_key'] ) );
        }

        if ( ! empty( $params['ai_model'] ) ) {
            update_option( 'wpseopilot_ai_model', sanitize_text_field( $params['ai_model'] ) );
        }

        // Save module settings
        $modules_to_toggle = [
            'enable_sitemap'   => 'wpseopilot_module_sitemap',
            'enable_404_log'   => 'wpseopilot_module_404_log',
            'enable_redirects' => 'wpseopilot_module_redirects',
        ];

        foreach ( $modules_to_toggle as $param_key => $option_key ) {
            if ( isset( $params[ $param_key ] ) ) {
                update_option( $option_key, $params[ $param_key ] ? '1' : '0' );
            }
        }

        // Save title template
        if ( ! empty( $params['title_template'] ) ) {
            update_option( 'wpseopilot_title_template', sanitize_text_field( $params['title_template'] ) );
        }

        // Mark setup as completed
        update_option( 'wpseopilot_setup_completed', true );
        delete_option( 'wpseopilot_setup_skipped' );

        return $this->success( null, __( 'Setup completed successfully!', 'wp-seo-pilot' ) );
    }

    /**
     * Skip setup wizard.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function skip_setup( $request ) {
        update_option( 'wpseopilot_setup_skipped', true );

        return $this->success( null, __( 'Setup skipped.', 'wp-seo-pilot' ) );
    }

    /**
     * Reset setup wizard (show again).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function reset_setup( $request ) {
        delete_option( 'wpseopilot_setup_completed' );
        delete_option( 'wpseopilot_setup_skipped' );
        delete_option( 'wpseopilot_setup_data' );

        return $this->success( null, __( 'Setup wizard reset. It will show on next page load.', 'wp-seo-pilot' ) );
    }

    /**
     * Test OpenAI connection.
     *
     * @param string $api_key API key.
     * @param string $model   Model ID.
     * @param string $prompt  Test prompt.
     * @return string|\WP_Error
     */
    private function test_openai( $api_key, $model, $prompt ) {
        $response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', [
            'headers' => [
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ],
            'body'    => wp_json_encode( [
                'model'      => $model,
                'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
                'max_tokens' => 10,
            ] ),
            'timeout' => 30,
        ] );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            $error_message = $body['error']['message'] ?? __( 'OpenAI API error', 'wp-seo-pilot' );
            return new \WP_Error( 'api_error', $error_message );
        }

        return trim( $body['choices'][0]['message']['content'] ?? '' );
    }

    /**
     * Test Anthropic connection.
     *
     * @param string $api_key API key.
     * @param string $prompt  Test prompt.
     * @return string|\WP_Error
     */
    private function test_anthropic( $api_key, $prompt ) {
        $response = wp_remote_post( 'https://api.anthropic.com/v1/messages', [
            'headers' => [
                'Content-Type'      => 'application/json',
                'x-api-key'         => $api_key,
                'anthropic-version' => '2023-06-01',
            ],
            'body'    => wp_json_encode( [
                'model'      => 'claude-3-haiku-20240307',
                'max_tokens' => 10,
                'messages'   => [ [ 'role' => 'user', 'content' => $prompt ] ],
            ] ),
            'timeout' => 30,
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
     * Test Ollama connection.
     *
     * @param string $prompt Test prompt.
     * @return string|\WP_Error
     */
    private function test_ollama( $prompt ) {
        $response = wp_remote_post( 'http://localhost:11434/api/chat', [
            'headers' => [ 'Content-Type' => 'application/json' ],
            'body'    => wp_json_encode( [
                'model'    => 'llama2',
                'messages' => [ [ 'role' => 'user', 'content' => $prompt ] ],
                'stream'   => false,
            ] ),
            'timeout' => 60,
        ] );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'connection_error', __( 'Could not connect to Ollama. Make sure it\'s running on localhost:11434.', 'wp-seo-pilot' ) );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code !== 200 ) {
            return new \WP_Error( 'api_error', __( 'Ollama returned an error. Make sure a model is installed.', 'wp-seo-pilot' ) );
        }

        return trim( $body['message']['content'] ?? '' );
    }
}
