<?php
/**
 * Tests for the 404 log write path, ignore-pattern cache and retention.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Request_Monitor;
use Saman\SEO\Tests\TestCase;

/**
 * Request_Monitor performance and data-hygiene coverage.
 */
class RequestMonitor404LogTest extends TestCase {

	/**
	 * Original $wpdb global.
	 *
	 * @var mixed
	 */
	private $original_wpdb;

	/**
	 * Recording $wpdb double.
	 *
	 * @var object
	 */
	private $db;

	/**
	 * Install a recording $wpdb and common stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->original_wpdb = $GLOBALS['wpdb'];
		$this->db            = self::make_db();
		$GLOBALS['wpdb']     = $this->db;

		Functions\when( 'current_time' )->justReturn( '2026-10-14 12:00:00' );
		Functions\when( 'wp_date' )->alias(
			static function ( $format, $timestamp = null ) {
				return gmdate( $format, null === $timestamp ? time() : $timestamp );
			}
		);
		Functions\when( 'wp_unslash' )->returnArg();
	}

	/**
	 * Restore the $wpdb global.
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		unset( $_SERVER['REQUEST_URI'], $_SERVER['HTTP_USER_AGENT'] );
		parent::tearDown();
	}

	/**
	 * Build a $wpdb double that records every call.
	 *
	 * @return object
	 */
	private static function make_db() {
		return new class() {
			/**
			 * Table prefix.
			 *
			 * @var string
			 */
			public $prefix = 'wp_';

			/**
			 * Insert id.
			 *
			 * @var int
			 */
			public $insert_id = 7;

			/**
			 * Recorded calls: [ method, sql/args ].
			 *
			 * @var array
			 */
			public $calls = array();

			/**
			 * Rows returned by get_results().
			 *
			 * @var array
			 */
			public $results = array();

			/**
			 * Queue of values for get_var().
			 *
			 * @var array
			 */
			public $vars = array();

			/**
			 * Queue of values for get_row().
			 *
			 * @var array
			 */
			public $rows = array();

			/**
			 * Record a prepare() call and return a readable query.
			 *
			 * @param string $query Query.
			 * @param mixed  ...$args Args.
			 * @return array
			 */
			public function prepare( $query, ...$args ) {
				if ( 1 === count( $args ) && is_array( $args[0] ) ) {
					$args = $args[0];
				}

				return array(
					'sql'  => $query,
					'args' => $args,
				);
			}

			/**
			 * Record a query.
			 *
			 * @param mixed $query Query.
			 * @return int
			 */
			public function query( $query ) {
				$this->calls[] = array( 'query', $query );
				return 1;
			}

			/**
			 * Record get_results.
			 *
			 * @param mixed $query Query.
			 * @return array
			 */
			public function get_results( $query ) {
				$this->calls[] = array( 'get_results', $query );
				return $this->results;
			}

			/**
			 * Record get_var.
			 *
			 * @param mixed $query Query.
			 * @return mixed
			 */
			public function get_var( $query ) {
				$this->calls[] = array( 'get_var', $query );
				return array_shift( $this->vars );
			}

			/**
			 * Record get_row.
			 *
			 * @param mixed $query Query.
			 * @return mixed
			 */
			public function get_row( $query ) {
				$this->calls[] = array( 'get_row', $query );
				return array_shift( $this->rows );
			}

			/**
			 * Record insert.
			 *
			 * @param string $table Table.
			 * @param array  $data  Data.
			 * @return int
			 */
			public function insert( $table, $data ) {
				$this->calls[] = array( 'insert', $data );
				return 1;
			}

			/**
			 * Record delete.
			 *
			 * @param string $table Table.
			 * @param array  $where Where.
			 * @return int
			 */
			public function delete( $table, $where ) {
				$this->calls[] = array( 'delete', $where );
				return 1;
			}

			/**
			 * Calls of one method.
			 *
			 * @param string $method Method.
			 * @return array
			 */
			public function calls_of( $method ) {
				return array_values(
					array_filter(
						$this->calls,
						static function ( $call ) use ( $method ) {
							return $method === $call[0];
						}
					)
				);
			}
		};
	}

	// ------------------------------------------------------------------
	// Retention.
	// ------------------------------------------------------------------

	/**
	 * Never-saved settings default to 30 days of retention.
	 */
	public function test_retention_defaults_to_30_days_when_never_saved(): void {
		$this->assertSame( 30, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * An explicitly saved "off" is respected (individual option).
	 */
	public function test_retention_respects_explicit_disable(): void {
		self::$options['SAMAN_SEO_enable_404_cleanup'] = '';
		$this->assertSame( 0, ( new Request_Monitor() )->get_retention_days() );

		self::$options['SAMAN_SEO_enable_404_cleanup'] = '0';
		$this->assertSame( 0, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * An explicit "off" in the legacy consolidated array is respected.
	 */
	public function test_retention_respects_legacy_disable(): void {
		self::$options['SAMAN_SEO_settings'] = array( 'enable_404_cleanup' => false );

		$this->assertSame( 0, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * Saved day counts are used; invalid ones fall back to 30.
	 */
	public function test_retention_uses_saved_days(): void {
		self::$options['SAMAN_SEO_enable_404_cleanup'] = '1';
		self::$options['SAMAN_SEO_cleanup_404_days']   = '7';
		$this->assertSame( 7, ( new Request_Monitor() )->get_retention_days() );

		self::$options['SAMAN_SEO_cleanup_404_days'] = '0';
		$this->assertSame( 30, ( new Request_Monitor() )->get_retention_days() );

		unset( self::$options['SAMAN_SEO_enable_404_cleanup'] );
		self::$options['SAMAN_SEO_cleanup_404_days'] = '14';
		$this->assertSame( 14, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * The row cap defaults to 10k and is filterable.
	 */
	public function test_max_rows_is_filterable(): void {
		$monitor = new Request_Monitor();
		$this->assertSame( 10000, $monitor->get_max_rows() );

		add_filter(
			'saman_seo_404_log_max_rows',
			static function () {
				return -5;
			}
		);
		$this->assertSame( 0, $monitor->get_max_rows() );
	}

	/**
	 * The cleanup cron is scheduled even though no setting was ever saved.
	 */
	public function test_cleanup_is_scheduled_by_default(): void {
		$scheduled = array();
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\when( 'wp_schedule_event' )->alias(
			static function ( $time, $recurrence, $hook ) use ( &$scheduled ) {
				$scheduled[] = array( $recurrence, $hook );
				return true;
			}
		);

		( new Request_Monitor() )->maybe_schedule_cleanup();

		$this->assertSame( array( array( 'daily', 'SAMAN_SEO_404_cleanup' ) ), $scheduled );
	}

	/**
	 * Scheduled cleanup deletes old rows and trims to the cap.
	 */
	public function test_scheduled_cleanup_applies_age_and_row_cap(): void {
		add_filter(
			'saman_seo_404_log_max_rows',
			static function () {
				return 100;
			}
		);

		$this->db->vars = array( 150 );
		$this->db->rows = array(
			(object) array(
				'id'        => 42,
				'last_seen' => '2026-10-01 00:00:00',
			),
		);

		( new Request_Monitor() )->run_scheduled_cleanup();

		$queries = $this->db->calls_of( 'query' );
		$this->assertCount( 2, $queries );
		$this->assertStringContainsString( 'DELETE FROM wp_SAMAN_SEO_404_log WHERE last_seen < %s', $queries[0][1]['sql'] );
		$this->assertStringContainsString( 'id <= %d', $queries[1][1]['sql'] );
		$this->assertSame( array( '2026-10-01 00:00:00', '2026-10-01 00:00:00', 42 ), $queries[1][1]['args'] );

		$cut = $this->db->calls_of( 'get_row' );
		$this->assertSame( array( 100 ), $cut[0][1]['args'] );
	}

	/**
	 * Under the cap nothing is trimmed, and an explicit "off" skips the age pass.
	 */
	public function test_cleanup_under_cap_with_age_disabled_deletes_nothing(): void {
		self::$options['SAMAN_SEO_enable_404_cleanup'] = '';
		$this->db->vars                                = array( 50 );

		( new Request_Monitor() )->run_scheduled_cleanup();

		$this->assertCount( 0, $this->db->calls_of( 'query' ) );
		$this->assertCount( 0, $this->db->calls_of( 'get_row' ) );
	}

	// ------------------------------------------------------------------
	// Ignore patterns.
	// ------------------------------------------------------------------

	/**
	 * Compiled patterns keep the regex / wildcard / exact semantics.
	 */
	public function test_compiled_patterns_match_like_before(): void {
		$this->db->results = array(
			(object) array(
				'pattern'  => '^/wp-.*\.php$',
				'is_regex' => 1,
			),
			(object) array(
				'pattern'  => '/old/*',
				'is_regex' => 0,
			),
			(object) array(
				'pattern'  => '/exact',
				'is_regex' => 0,
			),
			(object) array(
				'pattern'  => '',
				'is_regex' => 0,
			),
		);

		$monitor = new Request_Monitor();

		$this->assertTrue( $monitor->is_url_ignored( '/WP-login.php' ) );
		$this->assertTrue( $monitor->is_url_ignored( '/OLD/thing' ) );
		$this->assertTrue( $monitor->is_url_ignored( '/exact' ) );
		$this->assertFalse( $monitor->is_url_ignored( '/Exact' ) );
		$this->assertFalse( $monitor->is_url_ignored( '/fine' ) );
	}

	/**
	 * Patterns are read once, then served from the cache without SHOW TABLES.
	 */
	public function test_patterns_are_cached(): void {
		$this->db->results = array(
			(object) array(
				'pattern'  => '/x',
				'is_regex' => 0,
			),
		);

		$monitor = new Request_Monitor();
		$monitor->is_url_ignored( '/a' );
		$monitor->is_url_ignored( '/b' );
		$monitor->is_url_ignored( '/x' );

		$this->assertCount( 1, $this->db->calls_of( 'get_results' ) );
		$this->assertCount( 0, $this->db->calls_of( 'get_var' ), 'No SHOW TABLES probe on the hot path.' );
		$this->assertArrayHasKey( Request_Monitor::PATTERNS_CACHE_OPTION, self::$options );
	}

	/**
	 * Adding or deleting a pattern invalidates the cache.
	 */
	public function test_pattern_writes_invalidate_cache(): void {
		Functions\when( 'current_time' )->justReturn( '2026-10-14 12:00:00' );

		$monitor = new Request_Monitor();

		$monitor->is_url_ignored( '/a' );
		$this->assertArrayHasKey( Request_Monitor::PATTERNS_CACHE_OPTION, self::$options );

		$monitor->add_ignore_pattern( '/new' );
		$this->assertArrayNotHasKey( Request_Monitor::PATTERNS_CACHE_OPTION, self::$options );

		$this->db->results = array(
			(object) array(
				'pattern'  => '/new',
				'is_regex' => 0,
			),
		);
		$this->assertTrue( $monitor->is_url_ignored( '/new' ) );
		$this->assertArrayHasKey( Request_Monitor::PATTERNS_CACHE_OPTION, self::$options );

		$monitor->delete_ignore_pattern( 3 );
		$this->assertArrayNotHasKey( Request_Monitor::PATTERNS_CACHE_OPTION, self::$options );
	}

	// ------------------------------------------------------------------
	// Write path.
	// ------------------------------------------------------------------

	/**
	 * The dedupe hash folds case, like the old collation-based lookup.
	 */
	public function test_uri_hash_is_case_insensitive(): void {
		$this->assertSame( Request_Monitor::uri_hash( '/Foo/Bar' ), Request_Monitor::uri_hash( '/foo/bar' ) );
		$this->assertSame( md5( '/foo' ), Request_Monitor::uri_hash( '/foo' ) );
		$this->assertNotSame( Request_Monitor::uri_hash( '/foo' ), Request_Monitor::uri_hash( '/foo2' ) );
	}

	/**
	 * A 404 is logged with one upsert and no read-back by default.
	 */
	public function test_logging_a_404_is_a_single_upsert(): void {
		$this->set_context( 'is_404', true );
		self::$options[ Request_Monitor::PATTERNS_CACHE_OPTION ] = array();

		$_SERVER['REQUEST_URI']     = '/Missing/Page/?utm=1';
		$_SERVER['HTTP_USER_AGENT'] = '';

		( new Request_Monitor() )->maybe_log_404();

		$this->assertCount( 1, $this->db->calls, 'Exactly one database call per logged 404.' );

		$call = $this->db->calls[0];
		$this->assertSame( 'query', $call[0] );
		$this->assertStringContainsString( 'ON DUPLICATE KEY UPDATE', $call[1]['sql'] );
		$this->assertStringContainsString( 'hits = hits + 1', $call[1]['sql'] );
		$this->assertSame( '/Missing/Page', $call[1]['args'][0] );
		$this->assertSame( md5( '/missing/page' ), $call[1]['args'][1] );
	}

	/**
	 * Over-long values are truncated to their column lengths.
	 */
	public function test_long_values_are_truncated(): void {
		$this->set_context( 'is_404', true );
		self::$options[ Request_Monitor::PATTERNS_CACHE_OPTION ] = array();

		$_SERVER['REQUEST_URI'] = '/' . str_repeat( 'a', 400 );

		( new Request_Monitor() )->maybe_log_404();

		$args = $this->db->calls[0][1]['args'];
		$this->assertSame( 255, strlen( $args[0] ) );
		$this->assertSame( Request_Monitor::uri_hash( $args[0] ), $args[1] );
	}

	/**
	 * With notifications on, the row is read back to check the threshold.
	 */
	public function test_notifications_read_back_the_row(): void {
		$this->set_context( 'is_404', true );
		self::$options[ Request_Monitor::PATTERNS_CACHE_OPTION ] = array();
		self::$options['SAMAN_SEO_enable_404_notifications']     = '1';
		self::$options['SAMAN_SEO_notification_404_threshold']   = 99;

		$_SERVER['REQUEST_URI'] = '/gone';
		$this->db->rows         = array(
			(object) array(
				'id'   => 5,
				'hits' => 3,
			),
		);

		( new Request_Monitor() )->maybe_log_404();

		$reads = $this->db->calls_of( 'get_row' );
		$this->assertCount( 1, $reads );
		$this->assertSame( array( md5( '/gone' ) ), $reads[0][1]['args'] );
	}

	/**
	 * Ignored URLs never reach the database.
	 */
	public function test_ignored_url_is_not_written(): void {
		$this->set_context( 'is_404', true );
		self::$options[ Request_Monitor::PATTERNS_CACHE_OPTION ] = Request_Monitor::compile_ignore_patterns(
			array(
				array(
					'pattern'  => '/skip/*',
					'is_regex' => 0,
				),
			)
		);

		$_SERVER['REQUEST_URI'] = '/skip/me';

		( new Request_Monitor() )->maybe_log_404();

		$this->assertSame( array(), $this->db->calls );
	}
}
