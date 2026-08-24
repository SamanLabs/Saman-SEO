<?php
/**
 * Tests for Frontend canonical, hreflang and pagination output.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Head tag coverage (canonical/hreflang/pagination).
 */
class FrontendHeadTagsTest extends TestCase {

	/**
	 * Meta canonical overrides the permalink.
	 */
	public function test_canonical_from_meta_override(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'canonical' => 'https://example.org/custom-canonical/' );

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_head_tags' ) );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.org/custom-canonical/"', $html );
	}

	/**
	 * Without a meta override, the permalink is the canonical.
	 */
	public function test_canonical_falls_back_to_permalink(): void {
		$this->make_post();

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_head_tags' ) );

		$this->assertStringContainsString( '<link rel="canonical" href="https://example.org/sample-post/"', $html );
	}

	/**
	 * Plain permalinks (?p=) are never emitted as canonical.
	 */
	public function test_plain_permalink_never_canonical(): void {
		$post = $this->make_post();
		self::$permalinks[ $post->ID ] = 'https://example.org/?post_type=community&p=42';
		self::$query_vars['request']   = 0;

		// Simulate a resolved pretty request path.
		Functions\expect( 'user_trailingslashit' )->andReturnUsing(
			static function ( $value ) {
				return rtrim( (string) $value, '/' ) . '/';
			}
		);

		global $wp;
		$wp             = new \stdClass();
		$wp->request    = 'pretty-post-slug';

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_head_tags' ) );

		$this->assertStringNotContainsString( '?post_type=community', $html );
		$this->assertStringContainsString( '<link rel="canonical" href="https://example.org/pretty-post-slug/"', $html );
		unset( $GLOBALS['wp'] );
	}

	/**
	 * Meta description renders escaped inside a meta tag.
	 */
	public function test_description_renders_escaped(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'description' => 'Cats & dogs "quoted"' );

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_head_tags' ) );

		$this->assertStringContainsString( '<meta name="description" content="Cats &amp; dogs &quot;quoted&quot;"', $html );
	}

	/**
	 * Keywords fall back to tags + categories joined by commas.
	 */
	public function test_keywords_from_terms(): void {
		$post = $this->make_post();

		Functions\when( 'get_the_tags' )->alias(
			static function () {
				return array(
					(object) array( 'name' => 'Coffee' ),
					(object) array( 'name' => 'Brewing' ),
				);
			}
		);
		Functions\when( 'get_the_category' )->alias(
			static function () {
				return array( (object) array( 'name' => 'Guides' ) );
			}
		);

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_head_tags' ) );

		$this->assertStringContainsString( '<meta name="keywords" content="Coffee, Brewing, Guides"', $html );
		unset( $post );
	}

	/**
	 * hreflang map renders one link per locale; empty map emits nothing.
	 */
	public function test_hreflang_rendering(): void {
		$frontend = new Frontend();

		$this->assertSame( '', $this->capture_output( array( $frontend, 'render_hreflang' ) ) );

		self::$options['SAMAN_SEO_hreflang_map'] = wp_json_encode(
			array(
				'en' => 'https://example.org/en/',
				'de' => 'https://example.org/de/',
			)
		);

		$html = $this->capture_output( array( $frontend, 'render_hreflang' ) );

		$this->assertSame( 2, substr_count( $html, 'rel="alternate" hreflang=' ) );
		$this->assertStringContainsString( 'hreflang="de" href="https://example.org/de/"', $html );
	}

	/**
	 * Invalid JSON in the hreflang map is ignored safely.
	 */
	public function test_hreflang_invalid_json_ignored(): void {
		self::$options['SAMAN_SEO_hreflang_map'] = '{not-json';

		$frontend = new Frontend();

		$this->assertSame( '', $this->capture_output( array( $frontend, 'render_hreflang' ) ) );
	}

	/**
	 * Pagination links render for multi-page posts only.
	 */
	public function test_pagination_links(): void {
		$created  = $this->make_post();
		$frontend = new Frontend();

		// Single page: nothing.
		$this->assertSame( '', $this->capture_output( array( $frontend, 'render_pagination_links' ) ) );

		// Multi-page post on page 2 of 3: both prev and next.
		$GLOBALS['post']           = $created;
		$GLOBALS['numpages']       = 3;
		self::$query_vars['page'] = 2;

		$html = $this->capture_output( array( $frontend, 'render_pagination_links' ) );

		$this->assertStringContainsString( '<link rel="prev" href="https://example.org/sample-post/"', $html );
		$this->assertStringContainsString( '<link rel="next" href="https://example.org/sample-post/3"', $html );

		unset( $GLOBALS['post'], $GLOBALS['numpages'] );
	}
}
