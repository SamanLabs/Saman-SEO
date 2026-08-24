<?php
/**
 * PHPUnit bootstrap for Saman SEO unit tests.
 *
 * Loads the Composer autoloader (which classmaps includes/), defines a
 * minimal WP_Post stub, loads the plugin helper functions, and starts
 * Patchwork so Brain Monkey can stub WordPress functions on demand.
 *
 * @package Saman\SEO
 */

use function Brain\Monkey\Functions\when;

defined( 'ABSPATH' ) || define( 'ABSPATH', __DIR__ . '/' );

if ( ! defined( 'SAMAN_SEO_VERSION' ) ) {
	define( 'SAMAN_SEO_VERSION', '2.0.0-test' );
}
if ( ! defined( 'SAMAN_SEO_PATH' ) ) {
	define( 'SAMAN_SEO_PATH', dirname( __DIR__ ) . '/' );
}
if ( ! defined( 'SAMAN_SEO_URL' ) ) {
	define( 'SAMAN_SEO_URL', 'https://example.org/wp-content/plugins/saman-seo/' );
}
if ( ! defined( 'MINUTE_IN_SECONDS' ) ) {
	define( 'MINUTE_IN_SECONDS', 60 );
	define( 'HOUR_IN_SECONDS', 3600 );
	define( 'DAY_IN_SECONDS', 86400 );
	define( 'WEEK_IN_SECONDS', 604800 );
}

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Minimal WP_Post stub: Frontend and schema classes type-check against it.
if ( ! class_exists( 'WP_Post' ) ) {
	/**
	 * Lightweight WP_Post replacement for unit tests.
	 */
	final class WP_Post {
		public $ID                = 0;
		public $post_author       = 0;
		public $post_type         = 'post';
		public $post_title        = '';
		public $post_content      = '';
		public $post_excerpt      = '';
		public $post_status       = 'publish';
		public $post_date         = '';
		public $post_date_gmt     = '';
		public $post_modified     = '';
		public $post_modified_gmt = '';
		public $post_name         = '';
		public $post_parent       = 0;
		public $post_password     = '';
		public $comment_count     = 0;

		public function __construct( array $args = array() ) {
			foreach ( $args as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					$this->{$key} = $value;
				}
			}
		}
	}
}

if ( ! class_exists( 'WP_Term' ) ) {
	/**
	 * Lightweight WP_Term replacement for unit tests.
	 */
	final class WP_Term {
		public $term_id          = 0;
		public $name             = '';
		public $slug             = '';
		public $taxonomy         = 'category';
		public $description      = '';
		public $parent           = 0;

		public function __construct( array $args = array() ) {
			foreach ( $args as $key => $value ) {
				if ( property_exists( $this, $key ) ) {
					$this->{$key} = $value;
				}
			}
		}
	}
}

if ( ! class_exists( 'WP_Error' ) ) {
	/**
	 * Lightweight WP_Error replacement for unit tests.
	 */
	final class WP_Error {
		public $errors = array();

		public function __construct( $code = '', $message = '' ) {
			if ( '' !== $code ) {
				$this->errors[ $code ] = array( $message );
			}
		}

		public function get_error_message() {
			$first = reset( $this->errors );

			return is_array( $first ) ? (string) reset( $first ) : '';
		}
	}
}

// Load plugin helpers (namespace Saman\SEO\Helpers + global shims).
require_once SAMAN_SEO_PATH . 'includes/helpers.php';

/*
 * Pre-seed the handful of WP functions that must exist before any test
 * runs (e.g. called from code loaded at include time). Everything else is
 * stubbed lazily via Brain Monkey inside the base TestCase.
 */
when( 'wp_load_alloptions' )->justReturn( array() );
