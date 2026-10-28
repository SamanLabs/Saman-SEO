<?php
/**
 * Tests that dashboard coverage tells posts with a blank SEO title or description apart.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Dashboard_Controller;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Dashboard coverage pattern coverage.
 */
class DashboardCoverageTest extends TestCase {

	/**
	 * Original $wpdb global.
	 *
	 * @var mixed
	 */
	private $original_wpdb;

	/**
	 * Keep the original $wpdb.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->original_wpdb = $GLOBALS['wpdb'] ?? null;
	}

	/**
	 * Restore the original $wpdb.
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	/**
	 * Whether a SQL LIKE pattern built with esc_like() matches a value.
	 *
	 * @param string $pattern LIKE pattern.
	 * @param string $value   Column value.
	 * @return bool
	 */
	private static function sql_like( $pattern, $value ) {
		$regex = '';
		foreach ( preg_split( '/(\\\\.|%|_)/', $pattern, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY ) as $part ) {
			if ( '%' === $part ) {
				$regex .= '.*';
			} elseif ( '_' === $part ) {
				$regex .= '.';
			} else {
				$regex .= preg_quote( '\\' === $part[0] && 2 === strlen( $part ) ? $part[1] : $part, '/' );
			}
		}

		return 1 === preg_match( '/^' . $regex . '$/s', $value );
	}

	/**
	 * The patterns the coverage queries use must match how Post_Meta stores a blank title.
	 */
	public function test_blank_fields_are_not_counted_as_optimized() {
		$GLOBALS['wpdb'] = new class() {
			/**
			 * Table names.
			 *
			 * @var string
			 */
			public $postmeta = 'wp_postmeta';

			/**
			 * Table names.
			 *
			 * @var string
			 */
			public $posts = 'wp_posts';

			/**
			 * Arguments of each prepare() call.
			 *
			 * @var array
			 */
			public $prepared = array();

			/**
			 * Escape LIKE wildcards like core.
			 *
			 * @param string $text Text.
			 * @return string
			 */
			public function esc_like( $text ) {
				return addcslashes( $text, '_%\\' );
			}

			/**
			 * Record the arguments.
			 *
			 * @param string $query Query.
			 * @param mixed  ...$args Args.
			 * @return string
			 */
			public function prepare( $query, ...$args ) {
				$this->prepared[] = $args;
				return $query;
			}

			/**
			 * No rows.
			 *
			 * @return int
			 */
			public function get_var() {
				return 0;
			}
		};
		Functions\when( 'get_transient' )->justReturn( false );
		Functions\when( 'set_transient' )->justReturn( true );
		Functions\when( 'wp_count_posts' )->justReturn( (object) array( 'publish' => 1 ) );

		$coverage = new \ReflectionMethod( Dashboard_Controller::class, 'get_content_coverage_data' );
		$coverage->setAccessible( true );
		$coverage->invoke( ( new \ReflectionClass( Dashboard_Controller::class ) )->newInstanceWithoutConstructor() );

		list( $has_title, $empty_title ) = $GLOBALS['wpdb']->prepared[0];
		list( $has_desc, $empty_desc )   = $GLOBALS['wpdb']->prepared[1];

		$sanitize = array( ( new \ReflectionClass( Post_Meta::class ) )->newInstanceWithoutConstructor(), 'sanitize' );
		$blank    = serialize( call_user_func( $sanitize, array( 'focus_keyphrase' => 'coffee' ) ) );
		$filled   = serialize(
			call_user_func(
				$sanitize,
				array(
					'title'       => 'Cold Brew',
					'description' => 'How to brew.',
				)
			)
		);

		$this->assertTrue( self::sql_like( $has_title, $blank ) && self::sql_like( $empty_title, $blank ), 'blank title is excluded' );
		$this->assertTrue( self::sql_like( $has_desc, $blank ) && self::sql_like( $empty_desc, $blank ), 'blank description is excluded' );
		$this->assertTrue( self::sql_like( $has_title, $filled ) && ! self::sql_like( $empty_title, $filled ), 'a real title counts' );
		$this->assertTrue( self::sql_like( $has_desc, $filled ) && ! self::sql_like( $empty_desc, $filled ), 'a real description counts' );
	}
}
