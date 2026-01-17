<?php
/**
 * Updater REST Controller
 *
 * Handles REST API endpoints for plugin updates management.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Updater\GitHub_Updater;
use Saman\SEO\Updater\Plugin_Installer;

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

        // Toggle beta versions for a plugin.
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/beta', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'toggle_beta' ],
            'permission_callback' => [ $this, 'install_permission_check' ],
            'args'                => [
                'slug' => [
                    'required'          => true,
                    'type'              => 'string',
                    'sanitize_callback' => 'sanitize_text_field',
                ],
                'enabled' => [
                    'required'          => true,
                    'type'              => 'boolean',
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
        return $this->success( $results, __( 'Update check complete.', 'saman-seo' ) );
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
                __( 'Plugin not found in managed plugins list.', 'saman-seo' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is already installed.', 'saman-seo' ),
                'already_installed',
                400
            );
        }

        if ( empty( $plugin['download_url'] ) ) {
            return $this->error(
                __( 'No download URL available for this plugin.', 'saman-seo' ),
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
                __( 'Plugin not found in managed plugins list.', 'saman-seo' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'saman-seo' ),
                'not_installed',
                400
            );
        }

        if ( ! $plugin['update_available'] ) {
            return $this->error(
                __( 'No update available for this plugin.', 'saman-seo' ),
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
                __( 'Plugin not found in managed plugins list.', 'saman-seo' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'saman-seo' ),
                'not_installed',
                400
            );
        }

        if ( $plugin['active'] ) {
            return $this->error(
                __( 'Plugin is already active.', 'saman-seo' ),
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
                __( 'Plugin not found in managed plugins list.', 'saman-seo' ),
                'invalid_plugin',
                404
            );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['installed'] ) {
            return $this->error(
                __( 'Plugin is not installed.', 'saman-seo' ),
                'not_installed',
                400
            );
        }

        if ( ! $plugin['active'] ) {
            return $this->error(
                __( 'Plugin is not active.', 'saman-seo' ),
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

    /**
     * Toggle beta versions for a plugin.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function toggle_beta( $request ) {
        $slug    = $request->get_param( 'slug' );
        $enabled = $request->get_param( 'enabled' );
        $updater = GitHub_Updater::get_instance();

        // Verify plugin exists in managed plugins.
        $plugins = $updater->get_managed_plugins();
        $found   = false;
        foreach ( $plugins as $plugin_file => $plugin_data ) {
            if ( $plugin_data['slug'] === $slug ) {
                $found = true;
                // Clear caches to refresh data.
                delete_transient( 'SAMAN_SEO_gh_' . md5( $plugin_data['repo'] ) );
                delete_transient( 'SAMAN_SEO_gh_beta_' . md5( $plugin_data['repo'] ) );
                break;
            }
        }

        if ( ! $found ) {
            return $this->error(
                __( 'Plugin not found in managed plugins list.', 'saman-seo' ),
                'invalid_plugin',
                404
            );
        }

        // Update beta setting.
        $updater->set_beta_enabled( $slug, $enabled );

        // Clear update transient.
        delete_site_transient( 'update_plugins' );

        return $this->success(
            [
                'slug'         => $slug,
                'beta_enabled' => $enabled,
            ],
            $enabled
                ? __( 'Beta updates enabled. Checking for updates...', 'saman-seo' )
                : __( 'Beta updates disabled.', 'saman-seo' )
        );
    }
}
