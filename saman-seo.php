<?php
/**
 * Plugin Name: Saman SEO
 * Plugin URI:  https://github.com/SamanLabs/saman-seo
 * Description: Opinionated all-in-one SEO toolkit that keeps titles, metadata, structured data, redirects, and audits in sync with WordPress.
 * Version:     2.0.16
 * Author:      SamanLabs
 * Author URI:  https://github.com/SamanLabs
 * Text Domain: saman-seo
 * License:     GPL-2.0-or-later
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'SAMAN_SEO_VERSION' ) ) {
	define( 'SAMAN_SEO_VERSION', '2.0.16' );
}

if ( ! defined( 'SAMAN_SEO_PATH' ) ) {
	define( 'SAMAN_SEO_PATH', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'SAMAN_SEO_URL' ) ) {
	define( 'SAMAN_SEO_URL', plugin_dir_url( __FILE__ ) );
}

/**
 * Load Composer autoloader if available.
 */
if ( file_exists( SAMAN_SEO_PATH . 'vendor/autoload.php' ) ) {
	require_once SAMAN_SEO_PATH . 'vendor/autoload.php';
}

/**
 * Legacy PSR-4-ish autoloader for plugin classes.
 *
 * Registered as a fallback for any plugin class Composer does not know about
 * (for example after vendor/ was generated without scanning the includes/
 * directories).
 *
 * @param string $classname The requested class.
 */
spl_autoload_register(
	static function ( $classname ) {
		$classname = ltrim( $classname, '\\' );

		if ( 0 !== strpos( $classname, 'Saman\SEO\\' ) ) {
			return;
		}

		// Handle Api namespace separately (in includes/Api/ directory).
		if ( 0 === strpos( $classname, 'Saman\SEO\\Api\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Api\\', '', $classname );
			$file_name  = 'class-' . strtolower( str_replace( array( '_' ), '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Api/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Integration namespace (in includes/Integration/ directory).
		if ( 0 === strpos( $classname, 'Saman\SEO\\Integration\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Integration\\', '', $classname );
			$file_name  = 'class-' . strtolower( str_replace( array( '_' ), '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Integration/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Schema\Types namespace (in includes/Schema/Types/ directory).
		if ( 0 === strpos( $classname, 'Saman\SEO\\Schema\\Types\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Schema\\Types\\', '', $classname );
			$file_name  = 'class-' . strtolower( str_replace( array( '_' ), '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Schema/Types/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Schema namespace (in includes/Schema/ directory).
		if ( 0 === strpos( $classname, 'Saman\SEO\\Schema\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Schema\\', '', $classname );
			$file_name  = 'class-' . strtolower( str_replace( array( '_' ), '-', $class_name ) ) . '.php';
			$file       = SAMAN_SEO_PATH . 'includes/Schema/' . $file_name;

			if ( file_exists( $file ) ) {
				require_once $file;
			}
			return;
		}

		// Handle Service namespace (in includes/Service/ directory).
		if ( 0 === strpos( $classname, 'Saman\SEO\\Service\\' ) ) {
			$class_name = str_replace( 'Saman\SEO\\Service\\', '', $classname );
			$slug       = strtolower( str_replace( array( '_' ), '-', $class_name ) );
			$candidates = array(
				// Primary naming convention (saman-seo-service-*).
				SAMAN_SEO_PATH . 'includes/Service/class-saman-seo-service-' . $slug . '.php',
				SAMAN_SEO_PATH . 'includes/class-saman-seo-service-' . $slug . '.php',
				// Simple naming fallback (class-*).
				SAMAN_SEO_PATH . 'includes/Service/class-' . $slug . '.php',
			);

			foreach ( $candidates as $file ) {
				if ( file_exists( $file ) ) {
					require_once $file;
					break;
				}
			}
			return;
		}

		// Convert class name to slug for file lookup.
		$class_name = str_replace( 'Saman\SEO\\', '', $classname );
		$slug       = strtolower( str_replace( array( '\\', '_' ), '-', $class_name ) );

		// Try naming conventions.
			$candidates = array(
				// Primary naming convention (saman-seo-*).
				SAMAN_SEO_PATH . 'includes/class-saman-seo-' . $slug . '.php',
				// Simple naming fallback (class-*).
				SAMAN_SEO_PATH . 'includes/class-' . $slug . '.php',
			);

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

		// Initialize the React admin interface.
		// Also load for REST API requests so endpoints are registered
		$request_uri     = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Unslashed below.
		$is_rest_request = ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ||
			( strpos( $request_uri, '/wp-json/' ) !== false );

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

register_activation_hook( __FILE__, array( '\Saman\SEO\Plugin', 'activate' ) );
register_deactivation_hook( __FILE__, array( '\Saman\SEO\Plugin', 'deactivate' ) );
