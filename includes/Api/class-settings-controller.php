<?php
/**
 * Settings REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST controller for plugin settings.
 */
class Settings_Controller extends REST_Controller {

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get/Update all settings
        register_rest_route( $this->namespace, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get/Update single setting
        register_rest_route( $this->namespace, '/settings/(?P<key>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_setting' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_setting' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all plugin settings.
     *
     * @return \WP_REST_Response
     */
    public function get_settings() {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'wpseopilot_%'"
        );

        $settings = [];
        foreach ( $options as $opt ) {
            $key = str_replace( 'wpseopilot_', '', $opt->option_name );
            $settings[ $key ] = maybe_unserialize( $opt->option_value );
        }

        return $this->success( $settings );
    }

    /**
     * Get a single setting.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_setting( $request ) {
        $key   = $request->get_param( 'key' );
        $value = get_option( 'wpseopilot_' . $key );

        return $this->success( [
            'key'   => $key,
            'value' => $value,
        ] );
    }

    /**
     * Update multiple settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $settings = $request->get_json_params();

        foreach ( $settings as $key => $value ) {
            update_option( 'wpseopilot_' . $key, $value );
        }

        return $this->success( null, __( 'Settings saved successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Update a single setting.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_setting( $request ) {
        $key   = $request->get_param( 'key' );
        $body  = $request->get_json_params();
        $value = isset( $body['value'] ) ? $body['value'] : null;

        update_option( 'wpseopilot_' . $key, $value );

        return $this->success( [
            'key'   => $key,
            'value' => $value,
        ], __( 'Setting saved.', 'wp-seo-pilot' ) );
    }
}
