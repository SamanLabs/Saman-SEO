<?php
/**
 * Tests for the static page cache service.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Page_Cache;
use Saman\SEO\Tests\TestCase;
use Brain\Monkey\Functions;
use Saman\SEO\Tests\Unit\Service\Fake_Router;

/**
 * Page cache unit tests (pure logic only; filesystem paths mocked).
 */
class PageCacheTest extends TestCase {

	/**
	 * Original global $wp reference.
	 *
	 * @var mixed
	 */
	private $wp_backup = null;

	/**
	 * Install stubs used by this suite.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', '/tmp/wp-content' );
		}

		if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
			define( 'HOUR_IN_SECONDS', 3600 );
		}

		$this->wp_backup = isset( $GLOBALS['wp'] ) ? $GLOBALS['wp'] : null;

		// Minimal router stand-in: the service only reads ->request.
		$GLOBALS['wp'] = new class() {
			/**
			 * Request path relative to home.
			 *
			 * @var string
			 */
			public $request = '';
		};
	}

	/**
	 * Restore globals.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		$GLOBALS['wp'] = $this->wp_backup;

		parent::tearDown();
	}

	/*
	---------------------------------------------------------------------
	 * Cache keys
	 * ------------------------------------------------------------------- */

	public function test_cache_keys_are_deterministic_per_url() {
		$service = new Page_Cache();

		$this->assertSame(
			$service->make_key( 'https://example.org/coffee/' ),
			$service->make_key( 'https://example.org/coffee/' )
		);
	}

	public function test_cache_keys_ignore_query_strings_and_host_case() {
		$service  = new Page_Cache();
		$plain    = $service->make_key( 'https://Example.org/page/' );
		$cached_q = $service->make_key( 'https://example.org/page/?utm=x' );

		// Query strings never change the key because they are not cached.
		$this->assertSame( $plain, $cached_q );
	}

	public function test_scheme_is_part_of_the_key_to_prevent_collisions() {
		$service = new Page_Cache();

		$this->assertNotSame(
			$service->make_key( 'http://example.org/x/' ),
			$service->make_key( 'https://example.org/x/' )
		);
	}

	public function test_root_and_empty_path_share_a_key() {
		$service = new Page_Cache();

		$this->assertSame( $service->make_key( 'https://example.org' ), $service->make_key( 'https://example.org/' ) );
	}

	/*
	---------------------------------------------------------------------
	 * Path exclusions
	 * ------------------------------------------------------------------- */

	public function test_default_dynamic_paths_are_excluded() {
		$GLOBALS['wp']->request = 'checkout';

		$this->assertTrue( ( new Page_Cache() )->is_excluded_path() );
	}

	public function test_regular_paths_are_not_excluded() {
		$GLOBALS['wp']->request = '2026/01/hello-world';

		$this->assertFalse( ( new Page_Cache() )->is_excluded_path() );
	}

	public function test_user_exclusions_normalize_slashes() {
		update_option( Page_Cache::OPTION_EXCLUSIONS, "members\n" );

		$GLOBALS['wp']->request = 'members/gold';

		$this->assertTrue( ( new Page_Cache() )->is_excluded_path() );

		$GLOBALS['wp']->request = 'membership';

		$this->assertFalse( ( new Page_Cache() )->is_excluded_path() );
	}

	/*
	---------------------------------------------------------------------
	 * Drop-in generation
	 * ------------------------------------------------------------------- */

	public function test_drop_in_source_is_valid_standalone_php() {
		$source = ( new Page_Cache() )->build_drop_in_source();

		$this->assertStringStartsWith( '<?php', $source );
		$this->assertStringContainsString( Page_Cache::DROPIN_MARKER, $source );

		// No unresolved placeholders may survive generation.
		$this->assertStringNotContainsString( '{$dir}', $source );
		$this->assertStringNotContainsString( '{DROPIN_VERSION}', $source );
		$this->assertStringNotContainsString( '$version', $source );
	}

	public function test_drop_in_skips_logged_in_cookies_and_non_get() {
		$source = ( new Page_Cache() )->build_drop_in_source();

		$this->assertStringContainsString( 'wordpress_logged_in_', $source );
		$this->assertStringContainsString( 'comment_author_', $source );
		$this->assertStringContainsString( 'REQUEST_METHOD', $source );
		$this->assertStringContainsString( 'X-Saman-Cache', $source );
	}

	public function test_drop_in_skips_every_no_cache_cookie() {
		$source = ( new Page_Cache() )->build_drop_in_source();

		foreach ( Page_Cache::NO_CACHE_COOKIES as $prefix ) {
			$this->assertStringContainsString( "'{$prefix}'", $source );
		}
	}

	/**
	 * A page rendered for a returning commenter (form pre-filled with their name and email) or
	 * for a visitor who unlocked a password post must not be stored, or the drop-in serves it to all.
	 */
	public function test_visitor_cookies_keep_a_page_out_of_the_cache() {
		self::$options[ Page_Cache::OPTION_ENABLED ] = '1';
		$_SERVER['REQUEST_METHOD']                   = 'GET';
		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'is_user_logged_in' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_preview' )->justReturn( false );
		self::$context['singular'] = false;

		$cache = new Page_Cache();
		$saved = $_COOKIE;
		try {
			$_COOKIE = array( 'theme_pref' => 'dark' );
			$this->assertTrue( $cache->is_cacheable_request(), 'an ordinary anonymous visit is cached' );

			foreach ( array( 'comment_author_abc', 'wp-postpass_abc', 'wordpress_logged_in_abc' ) as $cookie ) {
				$_COOKIE = array( $cookie => 'x' );
				$this->assertFalse( $cache->is_cacheable_request(), $cookie );
			}
		} finally {
			$_COOKIE = $saved;
			unset( $_SERVER['REQUEST_METHOD'] );
		}
	}

	/**
	 * Unpublishing or a new slug moves the permalink, so the old address is purged explicitly.
	 */
	public function test_moving_a_published_post_purges_its_old_address() {
		Functions\when( 'get_permalink' )->alias(
			static function ( $post ) {
				return 'publish' === $post->post_status
					? 'https://example.org/' . $post->post_name . '/'
					: 'https://example.org/?p=' . $post->ID;
			}
		);
		$cache = new class() extends Page_Cache {
			/**
			 * Purged URLs.
			 *
			 * @var string[]
			 */
			public $purged = array();

			/**
			 * Record instead of touching the disk.
			 *
			 * @param string $url URL.
			 * @return void
			 */
			public function purge_url( $url ) {
				$this->purged[] = $url;
			}
		};

		$before = $this->make_post( array( 'post_name' => 'old-slug' ) );
		$cases  = array(
			'draft'   => array( 'post_status' => 'draft' ),
			'renamed' => array( 'post_name' => 'new-slug' ),
		);
		foreach ( $cases as $label => $change ) {
			$cache->purged = array();
			$after         = new \WP_Post( array_merge( (array) $before, $change ) );
			$cache->purge_previous_url( $before->ID, $after, $before );
			$this->assertSame( array( 'https://example.org/old-slug/' ), $cache->purged, $label );
		}

		$cache->purged = array();
		$cache->purge_previous_url( $before->ID, $before, $before );
		$this->assertSame( array(), $cache->purged, 'an edit that keeps the address purges nothing extra' );
	}

	/*
	---------------------------------------------------------------------
	 * Totals & stats math
	 * ------------------------------------------------------------------- */

	public function test_fresh_totals_start_at_zero() {
		$totals = ( new Page_Cache() )->fresh_totals();

		$this->assertSame( 0, $totals['hits'] );
		$this->assertSame( 0, $totals['misses'] );
		$this->assertSame( 0.0, $totals['cold_sum'] );
	}

	public function test_stats_compute_hit_rate_from_totals() {
		update_option(
			Page_Cache::OPTION_TOTALS,
			array(
				'hits'       => 90,
				'misses'     => 10,
				'warm_sum'   => 4700.0,
				'warm_count' => 90,
				'cold_sum'   => 82000.0,
				'cold_count' => 10,
			),
			false
		);

		$stats = ( new Page_Cache() )->get_stats();

		$this->assertSame( 90.0, $stats['hit_rate'] );
		$this->assertSame( 8200, (int) $stats['ttfb_cold_ms'] );
		$this->assertSame( 52, (int) $stats['ttfb_warm_ms'] );
	}
}
