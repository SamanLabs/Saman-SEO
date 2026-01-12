<?php
/**
 * Updater REST Controller
 *
 * Handles REST API endpoints for plugin updates management.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

use WPSEOPilot\Updater\GitHub_Updater;
use WPSEOPilot\Updater\Plugin_Installer;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updater_Controller class - REST API for plugin management.
 */
class Updater_Controller extends REST_Controller {

    /**
     * REST base for this controller.
     *
     * @var string
     */
    protected $rest_base = 'updater';

    /**
     * Register REST routes.
     */
    public function register_routes() {
        // Get all managed plugins status.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/plugins', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_plugins' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
        ] );

        // Force check for updates.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/check', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'check_updates' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
        ] );

        // Install a plugin.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/install', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'install_plugin' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Update a plugin.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/update', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_plugin' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Activate a plugin.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/activate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'activate_plugin' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );

        // Deactivate a plugin.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/deactivate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'deactivate_plugin' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
            ],
        ] );
    }

    /**
     * Permission callback - checks if user can install plugins.
     *
     * @return bool
     */
    public function install_permission_check() {
        return current_user_can( 'install_plugins' );
    }

    /**
     * Get all managed plugins with status.
     *
     * @return \WP_REST_Response
     */
    public function get_plugins() {
        $updater = GitHub_Updater::get_instance();
        return rest_ensure_response( $updater->get_plugins_status() );
    }

    /**
     * Force check for updates.
     *
     * @return \WP_REST_Response
     */
    public function check_updates() {
        $updater = GitHub_Updater::get_instance();
        $results = $updater->force_check_updates();
        return $this->success( $results, __( 'Update check complete.', 'wp-seo-pilot' ) );
    }

    /**
     * Install a plugin.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function install_plugin( $request ) {
        $slug    = $request->get_param( 'slug' );
        $updater = GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return $this->error(
                __( 'Plugin not found in managed plugins list.', 'wp-seo-pilot' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is already installed.', 'wp-seo-pilot' ),
                'already_installed',
                400
            );
        }

        if ( empty( $plugin['download_url'] ) ) {
            return $this->error(
                __( 'No download URL available for this plugin.', 'wp-seo-pilot' ),
                'no_download_url',
                400
            );
        }

        $result = Plugin_Installer::install(
            $plugin['download_url'],
            $plugin['plugin_file']
        );

        if ( ! $result['success'] ) {
            return $this->error( $result['message'], 'install_failed', 500 );
        }

        return $this->success( null, $result['message'] );
    }

    /**
     * Update a plugin.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_plugin( $request ) {
        $slug    = $request->get_param( 'slug' );
        $updater = GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return $this->error(
                __( 'Plugin not found in managed plugins list.', 'wp-seo-pilot' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'wp-seo-pilot' ),
                'not_installed',
                400
            );
        }

        if ( ! $plugin['update_available'] ) {
            return $this->error(
                __( 'No update available for this plugin.', 'wp-seo-pilot' ),
                'no_update',
                400
            );
        }

        $result = Plugin_Installer::update( $plugin['plugin_file'] );

        if ( ! $result['success'] ) {
            return $this->error( $result['message'], 'update_failed', 500 );
        }

        return $this->success( null, $result['message'] );
    }

    /**
     * Activate a plugin.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function activate_plugin( $request ) {
        $slug    = $request->get_param( 'slug' );
        $updater = GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return $this->error(
                __( 'Plugin not found in managed plugins list.', 'wp-seo-pilot' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'wp-seo-pilot' ),
                'not_installed',
                400
            );
        }

        if ( $plugin['active'] ) {
            return $this->error(
                __( 'Plugin is already active.', 'wp-seo-pilot' ),
                'already_active',
                400
            );
        }

        $result = Plugin_Installer::activate( $plugin['plugin_file'] );

        if ( ! $result['success'] ) {
            return $this->error( $result['message'], 'activate_failed', 500 );
        }

        return $this->success( null, $result['message'] );
    }

    /**
     * Deactivate a plugin.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function deactivate_plugin( $request ) {
        $slug    = $request->get_param( 'slug' );
        $updater = GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return $this->error(
                __( 'Plugin not found in managed plugins list.', 'wp-seo-pilot' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'wp-seo-pilot' ),
                'not_installed',
                400
            );
        }

        if ( ! $plugin['active'] ) {
            return $this->error(
                __( 'Plugin is not active.', 'wp-seo-pilot' ),
                'not_active',
                400
            );
        }

        $result = Plugin_Installer::deactivate( $plugin['plugin_file'] );

        if ( ! $result['success'] ) {
            return $this->error( $result['message'], 'deactivate_failed', 500 );
        }

        return $this->success( null, $result['message'] );
    }
}
