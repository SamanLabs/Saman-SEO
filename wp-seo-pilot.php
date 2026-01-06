<?php
/**
 * Plugin Name: WP SEO Pilot
 * Plugin URI:  https://github.com/jhd3197/WP-SEO-Pilot
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
* Version: 0.1.38
 * Author:      Juan Denis
 * Author URI:  https://github.com/jhd3197
 * Text Domain: wp-seo-pilot
 * License:     GPL-2.0-or-later
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WPSEOPILOT_VERSION' ) ) {
	define( 'WPSEOPILOT_VERSION', '0.1.38' );
}

if ( ! defined( 'WPSEOPILOT_PATH' ) ) {
	define( 'WPSEOPILOT_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'WPSEOPILOT_URL' ) ) {
	define( 'WPSEOPILOT_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Simple PSR-4-ish autoloader for plugin classes.
 *
 * @param string $class The requested class.
 */
spl_autoload_register(
	static function ( $class ) {
		$class = ltrim( $class, '\\' );

		if ( 0 !== strpos( $class, 'WPSEOPilot\\' ) ) {
			return;
		}

		$path = strtolower(
			str_replace(
				[ '\\', '_' ],
				'-',
				$class
			)
		);

		$file = WPSEOPILOT_PATH . 'includes/class-' . $path . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once WPSEOPILOT_PATH . 'includes/helpers.php';

if ( file_exists( WPSEOPILOT_PATH . 'test-analytics-simple.php' ) ) {
	require_once WPSEOPILOT_PATH . 'test-analytics-simple.php';
}

/**
 * Bootstrap the plugin.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\WPSEOPilot\Plugin' ) ) {
			return;
		}

		\WPSEOPilot\Plugin::instance()->boot();
	}
);

register_activation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'deactivate' ] );
