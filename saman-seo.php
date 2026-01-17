<?php
/**
 * Plugin Name: Saman SEO
 * Plugin URI:  https://github.com/jhd3197/saman-seo
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
 * Version:     2.0.0
 * Author:      Juan Denis
 * Author URI:  https://github.com/jhd3197
 * Text Domain: saman-seo
 * License:     GPL-2.0-or-later
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SAMAN_SEO_VERSION' ) ) {
	define( 'SAMAN_SEO_VERSION', '2.0.0' );
}

if ( ! defined( 'SAMAN_SEO_PATH' ) ) {
	define( 'SAMAN_SEO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAMAN_SEO_URL' ) ) {
	define( 'SAMAN_SEO_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Simple PSR-4-ish autoloader for plugin classes.
 *
 * @param string $class The requested class.
 */
spl_autoload_register(
	static function ( $class ) {
		$class = ltrim( $class, '\\' );

		if ( 0 !== strpos( $class, 'Saman\SEO\\' ) ) {
			return;
		}

		// Handle Api namespace separately (in includes/Api/ directory)
		if ( 0 === strpos( $class, 'Saman\SEO\\Api\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Api\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Api/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Integration namespace (in includes/Integration/ directory)
		if ( 0 === strpos( $class, 'Saman\SEO\\Integration\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Integration\\', '', $class );
			$file_name  = 'class-' . strtolower( str_replace( [ '_' ], '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Integration/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

	// Handle Service namespace (in includes/Service/ directory)
	if ( 0 === strpos( $class, 'Saman\SEO\\Service\\' ) ) {
		$class_name = str_replace( 'Saman\SEO\\Service\\', '', $class );
		$slug       = strtolower( str_replace( [ '_' ], '-', $class_name ) );
		$candidates = [
			// Primary naming convention (saman-seo-service-*)
			SAMAN_SEO_PATH . 'includes/Service/class-saman-seo-service-' . $slug . '.php',
			SAMAN_SEO_PATH . 'includes/class-saman-seo-service-' . $slug . '.php',
			// Simple naming fallback (class-*)
			SAMAN_SEO_PATH . 'includes/Service/class-' . $slug . '.php',
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
		$class_name = str_replace( 'Saman\SEO\\', '', $class );
		$slug       = strtolower( str_replace( [ '\\', '_' ], '-', $class_name ) );

		// Try naming conventions
		$candidates = [
			// Primary naming convention (saman-seo-*)
			SAMAN_SEO_PATH . 'includes/class-saman-seo-' . $slug . '.php',
			// Simple naming fallback (class-*)
			SAMAN_SEO_PATH . 'includes/class-' . $slug . '.php',
		];

		foreach ( $candidates as $file ) {
			if ( file_exists( $file ) ) {
				require_once $file;
				break;
			}
		}
	}
);

require_once SAMAN_SEO_PATH . 'includes/helpers.php';

/**
 * Bootstrap the plugin.
 */
add_action(
	'plugins_loaded',
	static function () {
		if ( ! class_exists( '\Saman\SEO\Plugin' ) ) {
			return;
		}

		\Saman\SEO\Plugin::instance()->boot();

		// Initialize Saman AI integration (registers assistants, hooks for AI features).
		if ( class_exists( '\Saman\SEO\Integration\AI_Pilot' ) ) {
			\Saman\SEO\Integration\AI_Pilot::init();
		}

		// Initialize WooCommerce integration (Product schema for WC products).
		if ( class_exists( '\Saman\SEO\Integration\WooCommerce' ) ) {
			( new \Saman\SEO\Integration\WooCommerce() )->boot();
		}

		// Initialize V2 React Admin (runs alongside V1)
		// Also load for REST API requests so endpoints are registered
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false );

		if ( ( is_admin() || $is_rest_request ) && class_exists( '\Saman\SEO\Admin_V2' ) ) {
			\Saman\SEO\Admin_V2::get_instance();
		}

		if ( class_exists( '\Saman\SEO\Service\Video_Schema' ) ) {
			new \Saman\SEO\Service\Video_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Course_Schema' ) ) {
			new \Saman\SEO\Service\Course_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Software_Schema' ) ) {
			new \Saman\SEO\Service\Software_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Book_Schema' ) ) {
			new \Saman\SEO\Service\Book_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Music_Schema' ) ) {
			new \Saman\SEO\Service\Music_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Movie_Schema' ) ) {
			new \Saman\SEO\Service\Movie_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Restaurant_Schema' ) ) {
			new \Saman\SEO\Service\Restaurant_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Service_Schema' ) ) {
			new \Saman\SEO\Service\Service_Schema();
		}

		if ( class_exists( '\Saman\SEO\Service\Job_Posting_Schema' ) ) {
			new \Saman\SEO\Service\Job_Posting_Schema();
		}

		// Initialize Schema Blocks (FAQ and HowTo Gutenberg blocks with schema).
		if ( class_exists( '\Saman\SEO\Service\Schema_Blocks' ) ) {
			( new \Saman\SEO\Service\Schema_Blocks() )->boot();
		}
	}
);

register_activation_hook( __FILE__, [ '\Saman\SEO\Plugin', 'activate' ] );
register_deactivation_hook( __FILE__, [ '\Saman\SEO\Plugin', 'deactivate' ] );
