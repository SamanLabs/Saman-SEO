<?php
/**
 * Plugin Name: Saman Labs SEO
 * Plugin URI:  https://github.com/jhd3197/saman-labs-seo
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
 * Version:     1.0.0
 * Author:      Juan Denis
 * Author URI:  https://github.com/jhd3197
 * Text Domain: saman-labs-seo
 * License:     GPL-2.0-or-later
 *
 * @package SamanLabs\SEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SAMANLABS_SEO_VERSION' ) ) {
	define( 'SAMANLABS_SEO_VERSION', '1.0.0' );
}

if ( ! defined( 'SAMANLABS_SEO_PATH' ) ) {
	define( 'SAMANLABS_SEO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAMANLABS_SEO_URL' ) ) {
	define( 'SAMANLABS_SEO_URL', plugin_dir_url( __FILE__ ) );
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
			$file       = SAMANLABS_SEO_PATH . 'includes/Api/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Integration namespace (in includes/Integration/ directory)
		if ( 0 === strpos( $class, 'WPSEOPilot\\Integration\\' ) ) {
			$class_name = str_replace( 'WPSEOPilot\\Integration\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = SAMANLABS_SEO_PATH . 'includes/Integration/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

	// Handle Service namespace (in includes/Service/ directory)
	if ( 0 === strpos( $class, 'WPSEOPilot\\Service\\' ) ) {
		$class_name = str_replace( 'WPSEOPilot\\Service\\', '', $class );
		$slug       = strtolower( str_replace( [ '_' ], '-', $class_name ) );
		$candidates = [
			SAMANLABS_SEO_PATH . 'includes/Service/class-wpseopilot-service-' . $slug . '.php',
			SAMANLABS_SEO_PATH . 'includes/class-wpseopilot-service-' . $slug . '.php',
			SAMANLABS_SEO_PATH . 'includes/Service/class-' . $slug . '.php',
		];

		foreach ( $candidates as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				break;
			}
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

		$file = SAMANLABS_SEO_PATH . 'includes/class-' . $path . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

require_once SAMANLABS_SEO_PATH . 'includes/helpers.php';

if ( file_exists( SAMANLABS_SEO_PATH . 'test-analytics-simple.php' ) ) {
	require_once SAMANLABS_SEO_PATH . 'test-analytics-simple.php';
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

		// Initialize WP AI Pilot integration (registers assistants, hooks for AI features).
		if ( class_exists( '\WPSEOPilot\Integration\AI_Pilot' ) ) {
			\WPSEOPilot\Integration\AI_Pilot::init();
		}

		// Initialize WooCommerce integration (Product schema for WC products).
		if ( class_exists( '\WPSEOPilot\Integration\WooCommerce' ) ) {
			( new \WPSEOPilot\Integration\WooCommerce() )->boot();
		}

		// Initialize V2 React Admin (runs alongside V1)
		// Also load for REST API requests so endpoints are registered
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false );

		if ( ( is_admin() || $is_rest_request ) && class_exists( '\WPSEOPilot\Admin_V2' ) ) {
			\WPSEOPilot\Admin_V2::get_instance();
		}

		if ( class_exists( '\WPSEOPilot\Service\Video_Schema' ) ) {
			new \WPSEOPilot\Service\Video_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Course_Schema' ) ) {
			new \WPSEOPilot\Service\Course_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Software_Schema' ) ) {
			new \WPSEOPilot\Service\Software_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Book_Schema' ) ) {
			new \WPSEOPilot\Service\Book_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Music_Schema' ) ) {
			new \WPSEOPilot\Service\Music_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Movie_Schema' ) ) {
			new \WPSEOPilot\Service\Movie_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Restaurant_Schema' ) ) {
			new \WPSEOPilot\Service\Restaurant_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Service_Schema' ) ) {
			new \WPSEOPilot\Service\Service_Schema();
		}

		if ( class_exists( '\WPSEOPilot\Service\Job_Posting_Schema' ) ) {
			new \WPSEOPilot\Service\Job_Posting_Schema();
		}

		// Initialize Schema Blocks (FAQ and HowTo Gutenberg blocks with schema).
		if ( class_exists( '\WPSEOPilot\Service\Schema_Blocks' ) ) {
			( new \WPSEOPilot\Service\Schema_Blocks() )->boot();
		}
	}
);

register_activation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\WPSEOPilot\Plugin', 'deactivate' ] );
