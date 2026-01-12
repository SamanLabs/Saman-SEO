<?php
/**
 * Plugin Name: WP SEO Pilot
 * Plugin URI:  https://github.com/jhd3197/WP-SEO-Pilot
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
* Version: 0.1.41
 * Author:      Juan Denis
 * Author URI:  https://github.com/jhd3197
 * Text Domain: wp-seo-pilot
 * License:     GPL-2.0-or-later
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'WPSEOPILOT_VERSION' ) ) {
	define( 'WPSEOPILOT_VERSION', '0.1.41' );
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

		// Handle Api namespace separately (in includes/Api/ directory)
		if ( 0 === strpos( $class, 'WPSEOPilot\\Api\\' ) ) {
			$class_name = str_replace( 'WPSEOPilot\\Api\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = WPSEOPILOT_PATH . 'includes/Api/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Integration namespace (in includes/Integration/ directory)
		if ( 0 === strpos( $class, 'WPSEOPilot\\Integration\\' ) ) {
			$class_name = str_replace( 'WPSEOPilot\\Integration\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = WPSEOPILOT_PATH . 'includes/Integration/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
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

		// Initialize V2 React Admin (runs alongside V1)
		// Also load for REST API requests so endpoints are registered
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false );

		if ( ( is_admin() || $is_rest_request ) && class_exists( '\WPSEOPilot\Admin_V2' ) ) {
			\WPSEOPilot\Admin_V2::get_instance();
		}
	}
);

register_activation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'deactivate' ] );
