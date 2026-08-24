<?php
/**
 * Tests for sitemap post exclusions.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Service\Sitemap_Enhancer;
use Saman\SEO\Tests\TestCase;

/**
 * Per-post and noindex-based sitemap exclusion coverage.
 */
class SitemapExclusionTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var Sitemap_Enhancer
	 */
	private Sitemap_Enhancer $enhancer;

	/**
	 * Boot service before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->enhancer = new Sitemap_Enhancer();
	}

	/**
	 * The default query gains an AND-wrapped meta_query excluding the
	 * per-post toggle, plus noindex when enabled (default).
	 */
	public function test_default_query_args_gain_exclusions(): void {
		$args = $this->enhancer->filter_posts_query_args( array(), 'post' );

		$this->assertSame( 'AND', $args['meta_query']['relation'] );
		$this->assertCount( 3, $args['meta_query'] ); // Two clauses + relation key.

		$first  = $args['meta_query'][0];
		$second = $args['meta_query'][1];

		// First clause: NOT EXISTS OR NOT LIKE the sitemap_exclude flag.
		$this->assertSame( 'OR', $first['relation'] );
		$this->assertSame( 'NOT EXISTS', $first[0]['compare'] );
		$this->assertSame(
			's:15:"sitemap_exclude";s:1:"1";',
			$first[1]['value']
		);
		$this->assertSame( Post_Meta::META_KEY, $first[1]['key'] );

		// Second clause: same shape for the noindex flag.
		$this->assertSame(
			's:7:"noindex";s:1:"1";',
			$second[1]['value']
		);
	}

	/**
	 * Disabling the noindex option removes that clause only.
	 */
	public function test_noindex_clause_optional(): void {
		update_option( 'SAMAN_SEO_sitemap_exclude_noindex', '0' );

		$args = $this->enhancer->filter_posts_query_args( array(), 'post' );

		$this->assertCount( 2, $args['meta_query'] ); // One clause + relation key.
		$this->assertSame(
			's:15:"sitemap_exclude";s:1:"1";',
			$args['meta_query'][0][1]['value']
		);
	}

	/**
	 * Pre-existing meta_query clauses survive by being nested under AND.
	 */
	public function test_existing_meta_query_preserved(): void {
		$existing = array(
			'relation' => 'OR',
			array(
				'key'   => 'custom_flag',
				'value' => 'yes',
			),
		);

		$args = $this->enhancer->filter_posts_query_args(
			array( 'meta_query' => $existing ),
			'post'
		);

		$this->assertSame( 'AND', $args['meta_query']['relation'] );
		$this->assertCount( 4, $args['meta_query'] ); // Two exclusions + original + relation key.

		// Last nested clause is the untouched original with its own relation.
		$this->assertSame( 'OR', $args['meta_query'][2]['relation'] );
		$this->assertSame( 'custom_flag', $args['meta_query'][2][0]['key'] );

		// Exclusions still present.
		$this->assertArrayHasKey( 0, $args['meta_query'][0] );
	}

	/**
	 * The ID blocklist option is parsed into post__not_in.
	 */
	public function test_manual_id_blocklist_applied(): void {
		update_option( 'SAMAN_SEO_sitemap_excluded_post_ids', '878, 12,, abc 0' );

		$args = $this->enhancer->apply_post_exclusions( array() );

		// Garbage tokens and zeroes are dropped by the sanitizer.
		$this->assertSame( array( 878, 12 ), $args['post__not_in'] );
	}

	/**
	 * Existing post__not_in entries are merged, not replaced.
	 */
	public function test_manual_blocklist_merges_with_existing(): void {
		update_option( 'SAMAN_SEO_sitemap_excluded_post_ids', '5,5,7' );

		$args = $this->enhancer->apply_post_exclusions(
			array( 'post__not_in' => array( 7, 9 ) )
		);

		$this->assertSame( array( 7, 9, 5 ), $args['post__not_in'] );
	}

	/**
	 * Developers can extend the blocklist via filter, scoped per post type.
	 */
	public function test_id_filter_extends_blocklist(): void {
		add_filter(
			'saman_seo_sitemap_excluded_post_ids',
			static function ( array $ids, string $post_type ) {
				return 'page' === $post_type ? array( 878 ) : $ids;
			},
			10,
			2
		);

		$page_args = $this->enhancer->get_manually_excluded_post_ids( 'page' );
		$post_args = $this->enhancer->get_manually_excluded_post_ids( 'post' );

		$this->assertSame( array( 878 ), $page_args );
		$this->assertSame( array(), $post_args );
	}

	/**
	 * The serialized flag fragment matches how sanitize stores "1" flags.
	 */
	public function test_serialized_fragment_matches_stored_meta(): void {
		$post    = $this->make_post();
		$service = new Post_Meta();

		$clean = $service->sanitize( array( 'sitemap_exclude' => 'on', 'title' => 'T' ) );
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = $clean;

		$stored = serialize( $clean );

		$this->assertStringContainsString( 's:15:"sitemap_exclude";s:1:"1";', $stored );

		$clean_off = $service->sanitize( array( 'sitemap_exclude' => '', 'title' => 'T' ) );
		$off_blob  = serialize( $clean_off );

		$this->assertStringNotContainsString( 's:15:"sitemap_exclude";s:1:"1";', $off_blob );
	}

	/**
	 * Post meta sanitizer accepts the new key end-to-end.
	 */
	public function test_post_meta_sanitizes_sitemap_exclude(): void {
		$service = new Post_Meta();

		$on  = $service->sanitize( array( 'sitemap_exclude' => true ) );
		$off = $service->sanitize( array() );

		$this->assertSame( '1', $on['sitemap_exclude'] );
		$this->assertSame( '', $off['sitemap_exclude'] );
	}

	/**
	 * Empty blocklist option leaves post__not_in untouched.
	 */
	public function test_empty_blocklist_is_noop(): void {
		update_option( 'SAMAN_SEO_sitemap_excluded_post_ids', '' );

		$args = $this->enhancer->apply_post_exclusions( array() );

		$this->assertArrayNotHasKey( 'post__not_in', $args );
	}
}
