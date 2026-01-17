<?php
/**
 * Plugin Installer
 *
 * Handles installation, activation, and updates of managed plugins.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Updater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin_Installer class - Static methods for plugin operations.
 */
class Plugin_Installer {

    /**
     * Load required WordPress admin files.
     */
    private static function load_wp_admin_files() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/misc.php';
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        WP_Filesystem();
    }

    /**
     * Install a plugin from GitHub.
     *
     * @param string $download_url Download URL for the plugin zip.
     * @param string $plugin_file  Plugin file path (e.g., 'plugin-name/plugin.php').
     * @return array Result with success status and message.
     */
    public static function install( string $download_url, string $plugin_file ): array {
        self::load_wp_admin_files();

        // Create upgrader with skin that captures output.
        $skin     = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );

        // Install the plugin.
        $result = $upgrader->install( $download_url );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        if ( ! $result ) {
            // Check skin for errors.
            $errors = $skin->get_errors();
            if ( is_wp_error( $errors ) && $errors->has_errors() ) {
                return [
                    'success' => false,
                    'message' => $errors->get_error_message(),
                ];
            }
            return [
                'success' => false,
                'message' => __( 'Installation failed. Check file permissions.', 'saman-seo' ),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Plugin installed successfully.', 'saman-seo' ),
        ];
    }

    /**
     * Update a plugin.
     *
     * @param string $plugin_file Plugin file path.
     * @return array Result with success status and message.
     */
    public static function update( string $plugin_file ): array {
        self::load_wp_admin_files();

        $skin     = new \WP_Ajax_Upgrader_Skin();
        $upgrader = new \Plugin_Upgrader( $skin );

        $result = $upgrader->upgrade( $plugin_file );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        if ( ! $result ) {
            $errors = $skin->get_errors();
            if ( is_wp_error( $errors ) && $errors->has_errors() ) {
                return [
                    'success' => false,
                    'message' => $errors->get_error_message(),
                ];
            }
            return [
                'success' => false,
                'message' => __( 'Update failed.', 'saman-seo' ),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Plugin updated successfully.', 'saman-seo' ),
        ];
    }

    /**
     * Activate a plugin.
     *
     * @param string $plugin_file Plugin file path.
     * @return array Result with success status and message.
     */
    public static function activate( string $plugin_file ): array {
        self::load_wp_admin_files();

        $result = activate_plugin( $plugin_file );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Plugin activated successfully.', 'saman-seo' ),
        ];
    }

    /**
     * Deactivate a plugin.
     *
     * @param string $plugin_file Plugin file path.
     * @return array Result with success status and message.
     */
    public static function deactivate( string $plugin_file ): array {
        self::load_wp_admin_files();

        deactivate_plugins( $plugin_file );

        return [
            'success' => true,
            'message' => __( 'Plugin deactivated.', 'saman-seo' ),
        ];
    }

    /**
     * Delete a plugin.
     *
     * @param string $plugin_file Plugin file path.
     * @return array Result with success status and message.
     */
    public static function delete( string $plugin_file ): array {
        self::load_wp_admin_files();

        // Deactivate first.
        deactivate_plugins( $plugin_file );

        // Delete.
        $result = delete_plugins( [ $plugin_file ] );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => __( 'Plugin deleted.', 'saman-seo' ),
        ];
    }
}
