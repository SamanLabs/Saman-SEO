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
	 * Whether a download URL points at a trusted source (GitHub).
	 *
	 * @param string $download_url Download URL.
	 * @return bool
	 */
	private static function is_trusted_source( string $download_url ): bool {
		$host = wp_parse_url( $download_url, PHP_URL_HOST );
		if ( ! $host ) {
			return false;
		}

		$host = strtolower( $host );

		/**
		 * Filters the trusted host suffixes for plugin installation.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $suffixes Allowed host suffixes.
		 */
		$allowed = saman_seo_apply_filters(
			'saman_seo_plugin_install_trusted_hosts',
			array( 'github.com', 'githubusercontent.com' )
		);

		foreach ( $allowed as $suffix ) {
			$suffix = strtolower( $suffix );
			if ( $host === $suffix || substr( $host, -strlen( '.' . $suffix ) ) === '.' . $suffix ) {
				return true;
			}
		}

		return false;
	}

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
		// Only ever install from trusted sources. This method has no caller
		// today, but constrain the download host now so it cannot be wired up
		// later into an arbitrary-source install.
		if ( ! self::is_trusted_source( $download_url ) ) {
			return array(
				'success' => false,
				'message' => __( 'Refusing to install from an untrusted source.', 'saman-seo' ),
			);
		}

		self::load_wp_admin_files();

		// Create upgrader with skin that captures output.
		$skin     = new \WP_Ajax_Upgrader_Skin();
		$upgrader = new \Plugin_Upgrader( $skin );

		// Install the plugin.
		$result = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		if ( ! $result ) {
			// Check skin for errors.
			$errors = $skin->get_errors();
			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				return array(
					'success' => false,
					'message' => $errors->get_error_message(),
				);
			}
			return array(
				'success' => false,
				'message' => __( 'Installation failed. Check file permissions.', 'saman-seo' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Plugin installed successfully.', 'saman-seo' ),
		);
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
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		if ( ! $result ) {
			$errors = $skin->get_errors();
			if ( is_wp_error( $errors ) && $errors->has_errors() ) {
				return array(
					'success' => false,
					'message' => $errors->get_error_message(),
				);
			}
			return array(
				'success' => false,
				'message' => __( 'Update failed.', 'saman-seo' ),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Plugin updated successfully.', 'saman-seo' ),
		);
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
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Plugin activated successfully.', 'saman-seo' ),
		);
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

		return array(
			'success' => true,
			'message' => __( 'Plugin deactivated.', 'saman-seo' ),
		);
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
		$result = delete_plugins( array( $plugin_file ) );

		if ( is_wp_error( $result ) ) {
			return array(
				'success' => false,
				'message' => $result->get_error_message(),
			);
		}

		return array(
			'success' => true,
			'message' => __( 'Plugin deleted.', 'saman-seo' ),
		);
	}
}
