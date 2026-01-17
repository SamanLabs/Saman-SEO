<?php
/**
 * Plugin Name: Saman SEO
 * Plugin URI:  https://github.com/jhd3197/saman-labs-seo
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
 * Version:     2.0.0
 * Author:      Juan Denis
 * Author URI:  https://github.com/jhd3197
 * Text Domain: saman-labs-seo
 * License:     GPL-2.0-or-later
 *
 * @package SamanLabs\SEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SAMANLABS_SEO_VERSION' ) ) {
	define( 'SAMANLABS_SEO_VERSION', '2.0.0' );
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

		if ( 0 !== strpos( $class, 'SamanLabs\SEO\\' ) ) {
			return;
		}

		// Handle Api namespace separately (in includes/Api/ directory)
		if ( 0 === strpos( $class, 'SamanLabs\SEO\\Api\\' ) ) {
			$class_name = str_replace( 'SamanLabs\SEO\\Api\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = SAMANLABS_SEO_PATH . 'includes/Api/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Integration namespace (in includes/Integration/ directory)
		if ( 0 === strpos( $class, 'SamanLabs\SEO\\Integration\\' ) ) {
			$class_name = str_replace( 'SamanLabs\SEO\\Integration\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = SAMANLABS_SEO_PATH . 'includes/Integration/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

	// Handle Service namespace (in includes/Service/ directory)
	if ( 0 === strpos( $class, 'SamanLabs\SEO\\Service\\' ) ) {
		$class_name = str_replace( 'SamanLabs\SEO\\Service\\', '', $class );
		$slug       = strtolower( str_replace( [ '_' ], '-', $class_name ) );
		$candidates = [
			// Primary naming convention (samanlabs-seo-service-*)
			SAMANLABS_SEO_PATH . 'includes/Service/class-samanlabs-seo-service-' . $slug . '.php',
			SAMANLABS_SEO_PATH . 'includes/class-samanlabs-seo-service-' . $slug . '.php',
			// Simple naming fallback (class-*)
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

		// Convert class name to slug for file lookup
		$class_name = str_replace( 'SamanLabs\SEO\\', '', $class );
		$slug       = strtolower( str_replace( [ '\\', '_' ], '-', $class_name ) );

		// Try naming conventions
		$candidates = [
			// Primary naming convention (samanlabs-seo-*)
			SAMANLABS_SEO_PATH . 'includes/class-samanlabs-seo-' . $slug . '.php',
			// Simple naming fallback (class-*)
			SAMANLABS_SEO_PATH . 'includes/class-' . $slug . '.php',
		];

		foreach ( $candidates as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				break;
			}
		}
	}
);

require_once SAMANLABS_SEO_PATH . 'includes/helpers.php';

/**
 * Bootstrap the plugin.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\SamanLabs\SEO\Plugin' ) ) {
			return;
		}

		\SamanLabs\SEO\Plugin::instance()->boot();

		// Initialize Saman Labs AI integration (registers assistants, hooks for AI features).
		if ( class_exists( '\SamanLabs\SEO\Integration\AI_Pilot' ) ) {
			\SamanLabs\SEO\Integration\AI_Pilot::init();
		}

		// Initialize WooCommerce integration (Product schema for WC products).
		if ( class_exists( '\SamanLabs\SEO\Integration\WooCommerce' ) ) {
			( new \SamanLabs\SEO\Integration\WooCommerce() )->boot();
		}

		// Initialize V2 React Admin (runs alongside V1)
		// Also load for REST API requests so endpoints are registered
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false );

		if ( ( is_admin() || $is_rest_request ) && class_exists( '\SamanLabs\SEO\Admin_V2' ) ) {
			\SamanLabs\SEO\Admin_V2::get_instance();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Video_Schema' ) ) {
			new \SamanLabs\SEO\Service\Video_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Course_Schema' ) ) {
			new \SamanLabs\SEO\Service\Course_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Software_Schema' ) ) {
			new \SamanLabs\SEO\Service\Software_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Book_Schema' ) ) {
			new \SamanLabs\SEO\Service\Book_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Music_Schema' ) ) {
			new \SamanLabs\SEO\Service\Music_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Movie_Schema' ) ) {
			new \SamanLabs\SEO\Service\Movie_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Restaurant_Schema' ) ) {
			new \SamanLabs\SEO\Service\Restaurant_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Service_Schema' ) ) {
			new \SamanLabs\SEO\Service\Service_Schema();
		}

		if ( class_exists( '\SamanLabs\SEO\Service\Job_Posting_Schema' ) ) {
			new \SamanLabs\SEO\Service\Job_Posting_Schema();
		}

		// Initialize Schema Blocks (FAQ and HowTo Gutenberg blocks with schema).
		if ( class_exists( '\SamanLabs\SEO\Service\Schema_Blocks' ) ) {
			( new \SamanLabs\SEO\Service\Schema_Blocks() )->boot();
		}
	}
);

register_activation_hook( __FILE__, [ '\SamanLabs\SEO\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\SamanLabs\SEO\Plugin', 'deactivate' ] );
