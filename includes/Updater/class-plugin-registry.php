<?php
/**
 * Plugin Registry
 *
 * Provides information about Saman Labs plugins for the "More" page.
 *
 * @package Saman\SEO
 * @since 2.0.0
 */

namespace Saman\SEO\Updater;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin_Registry class - Static plugin information for promotion.
 */
class Plugin_Registry {

	/**
	 * Get the list of Saman Labs plugins.
	 *
	 * @return array List of plugins with their info.
	 */
	public static function get_plugins() {
		return [
			'saman-seo' => [
				'name'        => 'Saman SEO',
				'slug'        => 'saman-seo',
				'repo'        => 'SamanLabs/Saman-SEO',
				'description' => __( 'The Open Standard for WordPress SEO. A comprehensive, transparent SEO solution.', 'saman-seo' ),
				'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-SEO/main/assets/images/icon-128.png',
				'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-SEO/main/assets/images/banner-772x250.png',
				'type'        => 'seo',
				'coming_soon' => false,
			],
			'saman-ai' => [
				'name'        => 'Saman AI',
				'slug'        => 'saman-ai',
				'repo'        => 'SamanLabs/Saman-AI',
				'description' => __( 'Centralized AI management for WordPress.', 'saman-seo' ),
				'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-AI/main/assets/images/icon-128.png',
				'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-AI/main/assets/images/banner-772x250.png',
				'type'        => 'ai',
				'coming_soon' => true,
			],
			'saman-security' => [
				'name'        => 'Saman Security',
				'slug'        => 'saman-security',
				'repo'        => 'SamanLabs/Saman-Security',
				'description' => __( 'Core security suite with firewall, malware scans, and hardening.', 'saman-seo' ),
				'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-Security/main/assets/images/icon-128.png',
				'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-Security/main/assets/images/banner-772x250.png',
				'type'        => 'security',
				'coming_soon' => true,
			],
		];
	}

	/**
	 * Get a single plugin's info.
	 *
	 * @param string $slug Plugin slug.
	 * @return array|null Plugin info or null if not found.
	 */
	public static function get_plugin( $slug ) {
		$plugins = self::get_plugins();
		return $plugins[ $slug ] ?? null;
	}

	/**
	 * Check if a plugin is installed.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool True if installed.
	 */
	public static function is_installed( $slug ) {
		$plugin_file = $slug . '/' . $slug . '.php';
		$all_plugins = get_plugins();
		return isset( $all_plugins[ $plugin_file ] );
	}

	/**
	 * Check if a plugin is active.
	 *
	 * @param string $slug Plugin slug.
	 * @return bool True if active.
	 */
	public static function is_active( $slug ) {
		$plugin_file = $slug . '/' . $slug . '.php';
		return is_plugin_active( $plugin_file );
	}

	/**
	 * Get all plugins with their current status.
	 *
	 * @return array Plugins with status info.
	 */
	public static function get_plugins_with_status() {
		$plugins = self::get_plugins();
		$result  = [];

		foreach ( $plugins as $slug => $plugin ) {
			$plugin['is_installed'] = self::is_installed( $slug );
			$plugin['is_active']    = self::is_active( $slug );
			$result[ $slug ]        = $plugin;
		}

		return $result;
	}
}
