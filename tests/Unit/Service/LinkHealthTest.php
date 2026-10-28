<?php
/**
 * Tests for the link health scanner's writes.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Link_Health;
use Saman\SEO\Tests\TestCase;

/**
 * Link_Health storage coverage.
 */
class LinkHealthTest extends TestCase {

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
	 * Install a recording $wpdb.
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
			public $insert_id = 1;

			/**
			 * Recorded insert() calls: [ table, data, format ].
			 *
			 * @var array
			 */
			public $inserts = array();

			/**
			 * Recorded query() calls.
			 *
			 * @var array
			 */
			public $queries = array();

			/**
			 * Return the query and its args.
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
			 * Record a query.
			 *
			 * @param mixed $query Query.
			 * @return int
			 */
			public function query( $query ) {
				$this->queries[] = $query;
				return 1;
			}

			/**
			 * Record an insert.
			 *
			 * @param string $table  Table.
			 * @param array  $data   Row.
			 * @param array  $format Formats.
			 * @return int
			 */
			public function insert( $table, $data, $format = null ) {
				$this->inserts[] = array( $table, $data, $format );
				return 1;
			}

			/**
			 * Accept a delete.
			 *
			 * @return int
			 */
			public function delete() {
				return 1;
			}
		};
		$GLOBALS['wpdb'] = $this->db;

		Functions\when( 'current_time' )->justReturn( '2026-10-28 12:00:00' );
		Functions\when( 'url_to_postid' )->alias(
			static function ( $url ) {
				return false !== strpos( $url, '/about/' ) ? 7 : 0;
			}
		);
		Functions\when( 'wp_http_validate_url' )->justReturn( false );
	}

	/**
	 * Restore the $wpdb global.
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	/**
	 * $wpdb applies formats by position, so every column must get the format of its own type:
	 * a URL cast with %d is stored as "0" and a link type cast with %d as ''.
	 */
	public function test_link_rows_store_url_and_type_with_matching_formats() {
		$this->make_post(
			array(
				'post_content' => '<a href="https://example.org/about/">About</a> <a href="https://elsewhere.test/x">Out</a>',
			)
		);

		( new Link_Health() )->scan_post_links( 42 );

		$this->assertCount( 2, $this->db->inserts );
		$expected = array(
			'source_post_id' => '%d',
			'target_url'     => '%s',
			'target_post_id' => '%d',
			'link_text'      => '%s',
			'link_type'      => '%s',
			'status'         => '%s',
			'http_code'      => '%d',
			'redirect_url'   => '%s',
			'error_message'  => '%s',
			'created_at'     => '%s',
			'last_checked'   => '%s',
		);
		foreach ( $this->db->inserts as list( , $data, $format ) ) {
			$this->assertSame( $expected, array_combine( array_keys( $data ), $format ) );
		}

		list( , $internal ) = $this->db->inserts[0];
		$this->assertSame( 'https://example.org/about/', $internal['target_url'] );
		$this->assertSame( 'internal', $internal['link_type'] );
		$this->assertSame( 7, $internal['target_post_id'] );

		list( , $external ) = $this->db->inserts[1];
		$this->assertSame( 'https://elsewhere.test/x', $external['target_url'] );
		$this->assertSame( 'external', $external['link_type'] );
	}

	/**
	 * started_at is stored in site-local time, so the stale-scan cutoff must be site-local too.
	 * Here the site runs 6 hours behind UTC: a UTC cutoff would reap a scan that just started.
	 */
	public function test_stale_scan_cutoff_uses_site_local_time() {
		$offset = -6 * HOUR_IN_SECONDS;
		Functions\when( 'wp_date' )->alias(
			static function ( $format, $timestamp = null ) use ( $offset ) {
				return gmdate( $format, ( null === $timestamp ? time() : $timestamp ) + $offset );
			}
		);

		$reap = new \ReflectionMethod( Link_Health::class, 'reap_stale_scans' );
		$reap->setAccessible( true );
		$reap->invoke( new Link_Health() );

		$this->assertCount( 1, $this->db->queries );
		$cutoff   = end( $this->db->queries[0]['args'] );
		$expected = gmdate( 'Y-m-d H:i:s', time() - 60 * MINUTE_IN_SECONDS + $offset );
		$this->assertLessThanOrEqual( 2, abs( strtotime( $cutoff ) - strtotime( $expected ) ), $cutoff );
	}
}
