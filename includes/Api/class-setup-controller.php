<?php
/**
 * Setup Wizard REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Integration\AI_Pilot;

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
        $completed = get_option( 'SAMAN_SEO_setup_completed', false );
        $skipped = get_option( 'SAMAN_SEO_setup_skipped', false );
        $setup_data = get_option( 'SAMAN_SEO_setup_data', [] );

        return $this->success( [
            'completed'   => (bool) $completed,
            'skipped'     => (bool) $skipped,
            'show_wizard' => ! $completed && ! $skipped,
            'setup_data'  => $setup_data,
        ] );
    }

    /**
     * Test API connection via Saman Labs AI.
     *
     * All AI operations are now delegated to the Saman Labs AI plugin.
     * This endpoint checks the status of that integration.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function test_api( $request ) {
        $status = AI_Pilot::get_status();

        // Check if Saman Labs AI is installed
        if ( ! AI_Pilot::is_installed() ) {
            return $this->success( [
                'success'      => false,
                'message'      => __( 'Saman Labs AI is not installed. Please install Saman Labs AI to use AI features.', 'saman-seo' ),
                'install_url'  => admin_url( 'plugin-install.php?s=saman-ai&tab=search&type=term' ),
                'status'       => 'not_installed',
            ] );
        }

        // Check if Saman Labs AI is active
        if ( ! AI_Pilot::is_active() ) {
            return $this->success( [
                'success'      => false,
                'message'      => __( 'Saman Labs AI is installed but not activated. Please activate it in your plugins.', 'saman-seo' ),
                'plugins_url'  => admin_url( 'plugins.php' ),
                'status'       => 'not_active',
            ] );
        }

        // Check if Saman Labs AI is configured
        if ( ! AI_Pilot::is_ready() ) {
            return $this->success( [
                'success'      => false,
                'message'      => __( 'Saman Labs AI is active but not configured. Please configure your AI provider in Saman Labs AI settings.', 'saman-seo' ),
                'settings_url' => admin_url( 'admin.php?page=Saman-ai' ),
                'status'       => 'not_configured',
            ] );
        }

        // All good - Saman Labs AI is ready
        return $this->success( [
            'success'   => true,
            'message'   => __( 'Saman Labs AI is ready! AI features are available.', 'saman-seo' ),
            'status'    => 'ready',
            'providers' => $status['providers'] ?? [],
            'models'    => $status['models'] ?? [],
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

        update_option( 'SAMAN_SEO_setup_data', $setup_data );

        // AI settings are now managed by Saman Labs AI plugin
        // Only save provider preference for compatibility
        if ( ! empty( $params['ai_provider'] ) ) {
            update_option( 'SAMAN_SEO_ai_active_provider', sanitize_text_field( $params['ai_provider'] ) );
        }

        // Save module settings
        $modules_to_toggle = [
            'enable_sitemap'   => 'SAMAN_SEO_module_sitemap',
            'enable_404_log'   => 'SAMAN_SEO_module_404_log',
            'enable_redirects' => 'SAMAN_SEO_module_redirects',
        ];

        foreach ( $modules_to_toggle as $param_key => $option_key ) {
            if ( isset( $params[ $param_key ] ) ) {
                update_option( $option_key, $params[ $param_key ] ? '1' : '0' );
            }
        }

        // Save title template
        if ( ! empty( $params['title_template'] ) ) {
            update_option( 'SAMAN_SEO_title_template', sanitize_text_field( $params['title_template'] ) );
        }

        // Mark setup as completed
        update_option( 'SAMAN_SEO_setup_completed', true );
        delete_option( 'SAMAN_SEO_setup_skipped' );

        return $this->success( null, __( 'Setup completed successfully!', 'saman-seo' ) );
    }

    /**
     * Skip setup wizard.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function skip_setup( $request ) {
        update_option( 'SAMAN_SEO_setup_skipped', true );

        return $this->success( null, __( 'Setup skipped.', 'saman-seo' ) );
    }

    /**
     * Reset setup wizard (show again).
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function reset_setup( $request ) {
        delete_option( 'SAMAN_SEO_setup_completed' );
        delete_option( 'SAMAN_SEO_setup_skipped' );
        delete_option( 'SAMAN_SEO_setup_data' );

        return $this->success( null, __( 'Setup wizard reset. It will show on next page load.', 'saman-seo' ) );
    }
}
