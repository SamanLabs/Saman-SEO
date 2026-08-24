<?php
/**
 * Tests for redirect validation, chain walking, and target resolution.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Redirect_Manager;
use Saman\SEO\Tests\TestCase;

/**
 * Redirect hardening coverage.
 */
class RedirectValidationTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var Redirect_Manager
	 */
	private Redirect_Manager $service;

	/**
	 * url_to_postid results: path => post_id.
	 *
	 * @var array<string,int>
	 */
	public static $url_posts = array();

	/**
	 * Post statuses: post_id => status.
	 *
	 * @var array<int,string>
	 */
	public static $post_statuses = array();

	/**
	 * Terms returned by get_terms.
	 *
	 * @var array<int,object>
	 */
	public static $terms = array();

	/**
	 * Term URLs: slug => path.
	 *
	 * @var array<string,string>
	 */
	public static $term_links = array();

	/**
	 * Post types with archives.
	 *
	 * @var array<string,object>
	 */
	public static $archive_types = array();

	/**
	 * Boot service and stub resolution functions.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->service = new Redirect_Manager();

		self::$url_posts     = array();
		self::$post_statuses = array();
		self::$terms         = array();
		self::$term_links    = array();
		self::$archive_types = array();

		Functions\when( 'url_to_postid' )->alias(
			static function ( $url ) {
				$path = wp_parse_url( (string) $url, PHP_URL_PATH );

				if ( null === $path ) {
					return 0;
				}

				$path = '/' === $path ? '/' : rtrim( $path, '/' );

				return self::$url_posts[ $path ] ?? 0;
			}
		);
		Functions\when( 'get_post_status' )->alias(
			static function ( $post_id ) {
				return self::$post_statuses[ (int) $post_id ] ?? 'publish';
			}
		);
		Functions\when( 'get_terms' )->alias(
			static function ( $args = array() ) {
				return self::$terms;
			}
		);
		Functions\when( 'get_term_link' )->alias(
			static function ( $term ) {
				return 'https://example.org' . ( self::$term_links[ $term->slug ] ?? '/unknown-term/' );
			}
		);
		Functions\when( 'get_post_types' )->alias(
			static function ( $args = array() ) {
				return self::$archive_types;
			}
		);
		Functions\when( 'current_time' )->alias(
			static function ( $format ) {
				return 'Ymd' === $format ? date( 'Ymd' ) : date( 'mysql' === $format ? 'Y-m-d H:i:s' : $format );
			}
		);
	}

	/**
	 * Build a fake rule object.
	 *
	 * @param int    $id      ID.
	 * @param string $source  Source.
	 * @param string $target  Target.
	 * @param int    $is_regex Regex flag.
	 * @return object
	 */
	private function rule( int $id, string $source, string $target, int $is_regex = 0 ) {
		return (object) compact( 'id', 'source', 'target', 'is_regex' );
	}

	/**
	 * A chain of two rules resolves to the final destination.
	 */
	public function test_walk_chain_follows_hops(): void {
		$rules = array(
			'/b/' => $this->rule( 1, '/b/', '/c/' ),
			'/c/' => $this->rule( 2, '/c/', '/final-dest/' ),
		);

		$walk = $this->service->walk_chain( '/b/', $rules );

		$this->assertSame( 2, $walk['hops'] );
		$this->assertSame( '/final-dest', $walk['final'] );
		$this->assertSame( '', $walk['loop'] );
	}

	/**
	 * A closed loop is detected at the revisited path.
	 */
	public function test_walk_chain_detects_loop(): void {
		$rules = array(
			'/a/' => $this->rule( 1, '/a/', '/b/' ),
			'/b/' => $this->rule( 2, '/b/', '/a/' ),
		);

		$walk = $this->service->walk_chain( '/a/', $rules );

		$this->assertNotSame( '', $walk['loop'] );
		// Paths normalize without a trailing slash (root excepted).
		$this->assertSame( '/a', $walk['loop'] );
	}

	/**
	 * The walk stops at MAX_CHAIN_DEPTH even on very long chains.
	 */
	public function test_walk_chain_respects_depth_cap(): void {
		$rules = array();

		for ( $i = 0; $i < 20; $i++ ) {
			$rules[ "/hop-{$i}/" ] = $this->rule( $i + 1, "/hop-{$i}/", '/hop-' . ( $i + 1 ) . '/' );
		}

		$walk = $this->service->walk_chain( '/hop-0/', $rules );

		$this->assertSame( Redirect_Manager::MAX_CHAIN_DEPTH, $walk['hops'] );
	}

	/**
	 * Regex rules terminate the walk (targets cannot be resolved statically).
	 */
	public function test_walk_chain_stops_at_regex_rule(): void {
		$rules = array(
			'/b/' => $this->rule( 1, '/b/', '/c/', 1 ),
			'/c/' => $this->rule( 2, '/c/', '/final/' ),
		);

		$walk = $this->service->walk_chain( '/b/', $rules );

		$this->assertSame( 0, $walk['hops'] );
		$this->assertSame( '/b', $walk['final'] );
	}

	/**
	 * The excluded rule is invisible to the walk (editing scenario).
	 */
	public function test_walk_chain_honours_exclude_id(): void {
		$rules = array(
			'/b/' => $this->rule( 1, '/b/', '/c/' ),
			'/c/' => $this->rule( 2, '/c/', '/final/' ),
		);

		$walk = $this->service->walk_chain( '/b/', $rules, 1 );

		$this->assertSame( 0, $walk['hops'] );
		$this->assertSame( '/b', $walk['final'] );
	}

	/**
	 * Source == target produces a loop warning.
	 */
	public function test_validate_flags_direct_loop(): void {
		$warnings = $this->service->validate_redirect( '/same-path/', '/same-path/', 0, false, array() );

		$types = wp_list_pluck( $warnings, 'type' );

		$this->assertContains( 'loop', $types );
	}

	/**
	 * A target that another rule redirects is reported as a chain.
	 */
	public function test_validate_flags_chain(): void {
		$rules = array(
			'/intermediate/' => $this->rule( 5, '/intermediate/', '/final-dest/' ),
		);

		$warnings = $this->service->validate_redirect( '/old-url/', '/intermediate/', 0, false, $rules );

		$chain = array_filter( $warnings, array( $this, 'is_chain' ) );

		$this->assertNotEmpty( $chain );

		$first = reset( $chain );

		$this->assertSame( '/final-dest', $first['final'] );
		$this->assertSame( 1, $first['hops'] );
	}

	/**
	 * A target that resolves to nothing is flagged dead.
	 */
	public function test_validate_flags_dead_target(): void {
		$warnings = $this->service->validate_redirect( '/old/', '/gone-forever/', 0, false, array() );

		$types = wp_list_pluck( $warnings, 'type' );

		$this->assertContains( 'dead_target', $types );
	}

	/**
	 * A published target produces no dead-target warning.
	 */
	public function test_validate_accepts_published_target(): void {
		self::$url_posts['/existing-page'] = 99;

		$warnings = $this->service->validate_redirect( '/old/', '/existing-page/', 0, false, array() );

		$types = wp_list_pluck( $warnings, 'type' );

		$this->assertNotContains( 'dead_target', $types );
		$this->assertNotContains( 'unpublished_target', $types );
	}

	/**
	 * A draft target is flagged unpublished rather than dead.
	 */
	public function test_validate_flags_unpublished_target(): void {
		self::$url_posts['/draft-page']     = 31;
		self::$post_statuses[31]           = 'draft';

		$warnings = $this->service->validate_redirect( '/old/', '/draft-page/', 0, false, array() );

		$types = wp_list_pluck( $warnings, 'type' );

		$this->assertContains( 'unpublished_target', $types );
		$this->assertNotContains( 'dead_target', $types );
	}

	/**
	 * External targets get an info note, never a dead-target warning.
	 */
	public function test_validate_external_target(): void {
		$warnings = $this->service->validate_redirect( '/old/', 'https://partner-site.com/offer/', 0, false, array() );

		$types = wp_list_pluck( $warnings, 'type' );

		$this->assertContains( 'external', $types );
		$this->assertNotContains( 'dead_target', $types );
	}

	/**
	 * Root path resolves as home.
	 */
	public function test_resolve_root_is_home(): void {
		$result = $this->service->resolve_local_path( '/' );

		$this->assertSame( 'home', $result['type'] );
	}

	/**
	 * Date archives resolve without a post lookup.
	 */
	public function test_resolve_date_archive(): void {
		$result = $this->service->resolve_local_path( '/2026/01/' );

		$this->assertSame( 'archive', $result['type'] );
	}

	/**
	 * Pagination suffixes are stripped before resolving the base path.
	 */
	public function test_resolve_strips_pagination(): void {
		self::$url_posts['/blog'] = 7;

		$result = $this->service->resolve_local_path( '/blog/page/3/' );

		$this->assertSame( 'post', $result['type'] );
		$this->assertSame( 7, $result['post_id'] );
	}

	/**
	 * Term archives resolve via the slug scan.
	 */
	public function test_resolve_term_archive(): void {
		self::$terms       = array( (object) array( 'slug' => 'coffee' ) );
		self::$term_links  = array( 'coffee' => '/category/coffee/' );

		$result = $this->service->resolve_local_path( '/category/coffee/' );

		$this->assertSame( 'term', $result['type'] );
	}

	/**
	 * CPT archive slugs resolve as archives.
	 */
	public function test_resolve_post_type_archive(): void {
		self::$archive_types = array(
			'promotion' => (object) array(
				'name'        => 'promotion',
				'has_archive' => true,
			),
		);

		$result = $this->service->resolve_local_path( '/promotion/' );

		$this->assertSame( 'archive', $result['type'] );
	}

	/**
	 * Unknown paths report not_found.
	 */
	public function test_resolve_unknown_path(): void {
		$result = $this->service->resolve_local_path( '/nope/' );

		$this->assertSame( 'not_found', $result['type'] );
	}

	/**
	 * Filter for is_chain helper.
	 *
	 * @param array $warning Warning.
	 * @return bool
	 */
	private function is_chain( $warning ): bool {
		return 'chain' === $warning['type'];
	}
}
