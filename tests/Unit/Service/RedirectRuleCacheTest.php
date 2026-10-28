<?php
/**
 * Tests for the redirect hot path: regex rule cache and exact-match SQL.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Redirect_Manager;
use Saman\SEO\Tests\TestCase;

/**
 * Redirect_Manager caching coverage.
 */
class RedirectRuleCacheTest extends TestCase {

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
		$this->db            = new class() {
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
			public $insert_id = 11;

			/**
			 * Recorded calls.
			 *
			 * @var array
			 */
			public $calls = array();

			/**
			 * Rows for get_results().
			 *
			 * @var array
			 */
			public $results = array();

			/**
			 * Queue for get_var().
			 *
			 * @var array
			 */
			public $vars = array();

			/**
			 * Queue for get_row().
			 *
			 * @var array
			 */
			public $rows = array();

			/**
			 * Record prepare.
			 *
			 * @param string $query Query.
			 * @param mixed  ...$args Args.
			 * @return array
			 */
			public function prepare( $query, ...$args ) {
				return array(
					'sql'  => $query,
					'args' => $args,
				);
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
			 * Record update.
			 *
			 * @param string $table Table.
			 * @param array  $data  Data.
			 * @return int
			 */
			public function update( $table, $data ) {
				$this->calls[] = array( 'update', $data );
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
			 * Record query.
			 *
			 * @param mixed $query Query.
			 * @return int
			 */
			public function query( $query ) {
				$this->calls[] = array( 'query', $query );
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
		$GLOBALS['wpdb']     = $this->db;

		Functions\when( 'wp_cache_delete' )->justReturn( true );
		Functions\when( 'current_time' )->justReturn( '2026-10-14 12:00:00' );
	}

	/**
	 * Restore the $wpdb global.
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	/**
	 * Seed regex rows.
	 *
	 * @return void
	 */
	private function seed_regex_rows(): void {
		$this->db->results = array(
			(object) array(
				'id'          => '3',
				'source'      => '^/blog/(\d+)$',
				'target'      => '/posts/$1',
				'status_code' => '301',
				'start_date'  => null,
				'end_date'    => null,
				'hits'        => '99',
				'notes'       => 'not cached',
			),
			(object) array(
				'id'          => '4',
				'source'      => '^/broken/(',
				'target'      => '/x',
				'status_code' => '302',
				'start_date'  => null,
				'end_date'    => null,
			),
		);
	}

	/**
	 * The rule list is read once and then served from the option.
	 */
	public function test_regex_rules_are_cached(): void {
		$this->seed_regex_rows();
		$manager = new Redirect_Manager();

		$first  = $manager->get_regex_rules();
		$second = $manager->get_regex_rules();

		$this->assertSame( $first, $second );
		$this->assertCount( 1, $this->db->calls_of( 'get_results' ) );

		// Only the fields needed for matching, invalid patterns dropped.
		$this->assertSame(
			array(
				array(
					'id'          => 3,
					'source'      => '^/blog/(\d+)$',
					'target'      => '/posts/$1',
					'status_code' => 301,
					'start_date'  => null,
					'end_date'    => null,
				),
			),
			$first
		);
	}

	/**
	 * A cache older than the max age is rebuilt.
	 */
	public function test_stale_cache_is_rebuilt(): void {
		$this->seed_regex_rows();
		self::$options[ Redirect_Manager::RULES_CACHE_OPTION ] = array(
			'built' => time() - Redirect_Manager::RULES_CACHE_MAX_AGE - 1,
			'rules' => array(),
		);

		$rules = ( new Redirect_Manager() )->get_regex_rules();

		$this->assertCount( 1, $rules );
		$this->assertCount( 1, $this->db->calls_of( 'get_results' ) );
	}

	/**
	 * Flushing drops the rule cache (CLI import and bulk delete use it).
	 */
	public function test_flush_cache_drops_rule_cache(): void {
		self::$options[ Redirect_Manager::RULES_CACHE_OPTION ] = array(
			'built' => time(),
			'rules' => array(),
		);

		Redirect_Manager::flush_cache();

		$this->assertArrayNotHasKey( Redirect_Manager::RULES_CACHE_OPTION, self::$options );
	}

	/**
	 * Every manager write path invalidates the cache.
	 */
	public function test_writes_invalidate_rule_cache(): void {
		$manager = new Redirect_Manager();
		$seed    = static function () {
			self::$options[ Redirect_Manager::RULES_CACHE_OPTION ] = array(
				'built' => time(),
				'rules' => array(),
			);
		};

		$seed();
		$this->db->vars = array( null );
		$this->assertSame( 11, $manager->create_redirect( '/old', '/new' ) );
		$this->assertArrayNotHasKey( Redirect_Manager::RULES_CACHE_OPTION, self::$options, 'create' );

		$seed();
		$this->db->vars = array( 11 );
		$this->assertTrue( $manager->update_redirect( 11, array( 'target' => '/newer' ) ) );
		$this->assertArrayNotHasKey( Redirect_Manager::RULES_CACHE_OPTION, self::$options, 'update' );

		$seed();
		$this->assertTrue( $manager->delete_redirect( 11 ) );
		$this->assertArrayNotHasKey( Redirect_Manager::RULES_CACHE_OPTION, self::$options, 'delete' );
	}

	/**
	 * Regex matching runs from the cache and still applies captures.
	 */
	public function test_regex_match_uses_cached_rules(): void {
		self::$options[ Redirect_Manager::RULES_CACHE_OPTION ] = array(
			'built' => time(),
			'rules' => array(
				array(
					'id'          => 8,
					'source'      => '^/blog/(\d+)$',
					'target'      => '/posts/$1',
					'status_code' => 301,
					'start_date'  => null,
					'end_date'    => null,
				),
			),
		);

		$method = new \ReflectionMethod( Redirect_Manager::class, 'find_regex_redirect' );
		$method->setAccessible( true );

		$row = $method->invoke(
			new Redirect_Manager(),
			'/BLOG/42',
			'',
			array(
				'query_matching'   => 'ignore',
				'case_insensitive' => true,
			)
		);

		$this->assertSame( array(), $this->db->calls, 'No query when the cache is warm.' );
		$this->assertSame( 8, $row->id );
		$this->assertSame( '/posts/42', $row->target );
		$this->assertSame( '/posts/$1', $row->configured_target );
	}

	/**
	 * Collation names are classified correctly.
	 */
	public function test_collation_classification(): void {
		$this->assertTrue( Redirect_Manager::collation_is_case_insensitive( 'utf8mb4_unicode_ci' ) );
		$this->assertTrue( Redirect_Manager::collation_is_case_insensitive( 'utf8mb4_0900_ai_ci' ) );
		$this->assertTrue( Redirect_Manager::collation_is_case_insensitive( 'UTF8MB4_UNICODE_520_CI' ) );
		$this->assertFalse( Redirect_Manager::collation_is_case_insensitive( 'utf8mb4_bin' ) );
		$this->assertFalse( Redirect_Manager::collation_is_case_insensitive( 'utf8mb4_0900_as_cs' ) );
		$this->assertFalse( Redirect_Manager::collation_is_case_insensitive( '' ) );
	}

	/**
	 * The column collation is inspected once and remembered.
	 */
	public function test_source_collation_is_detected_once(): void {
		$this->db->rows = array(
			(object) array(
				'Field'     => 'source',
				'Collation' => 'utf8mb4_unicode_520_ci',
			),
		);

		$manager = new Redirect_Manager();

		$this->assertTrue( $manager->source_column_is_case_insensitive() );
		$this->assertTrue( $manager->source_column_is_case_insensitive() );
		$this->assertCount( 1, $this->db->calls_of( 'get_row' ) );
		$this->assertSame( '1', self::$options[ Redirect_Manager::SOURCE_CI_OPTION ] );
	}

	/**
	 * Case-insensitive lookups use the index on `_ci` columns.
	 */
	public function test_case_insensitive_exact_match_avoids_lower_on_ci_column(): void {
		self::$options[ Redirect_Manager::SOURCE_CI_OPTION ] = '1';

		$sql = $this->exact_lookup_sql( true );

		$this->assertStringContainsString( 'WHERE source = %s', $sql );
		$this->assertStringNotContainsString( 'LOWER(', $sql );
	}

	/**
	 * A case-sensitive column keeps the LOWER() comparison.
	 */
	public function test_case_insensitive_exact_match_falls_back_on_bin_column(): void {
		self::$options[ Redirect_Manager::SOURCE_CI_OPTION ] = '0';

		$this->assertStringContainsString( 'LOWER(source) = LOWER(%s)', $this->exact_lookup_sql( true ) );
	}

	/**
	 * The case-sensitive setting is unchanged.
	 */
	public function test_case_sensitive_exact_match_is_unchanged(): void {
		$sql = $this->exact_lookup_sql( false );

		$this->assertStringContainsString( 'WHERE source = %s', $sql );
		$this->assertCount( 1, $this->db->calls, 'No collation probe when the setting is off.' );
	}

	/**
	 * Run find_exact_redirect and return the SQL it issued.
	 *
	 * @param bool $case_insensitive Setting value.
	 * @return string
	 */
	private function exact_lookup_sql( bool $case_insensitive ): string {
		$method = new \ReflectionMethod( Redirect_Manager::class, 'find_exact_redirect' );
		$method->setAccessible( true );

		$method->invoke(
			new Redirect_Manager(),
			'/Some/Path',
			'',
			array(
				'query_matching'   => 'ignore',
				'case_insensitive' => $case_insensitive,
			)
		);

		$reads = $this->db->calls_of( 'get_row' );
		$last  = end( $reads );

		return $last[1]['sql'];
	}
}
