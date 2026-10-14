<?php
/**
 * Tests for the per-post snippet controls (nosnippet, max-snippet,
 * max-image-preview, noimageindex) and their precedence over the global
 * robots setting.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Per-post snippet control coverage.
 */
class FrontendSnippetControlsTest extends TestCase {

	/**
	 * Store per-post meta for the current post and return the resolved
	 * wp_robots array.
	 *
	 * @param array $meta   Per-post SEO meta.
	 * @param array $robots Directives already registered on wp_robots.
	 * @return array
	 */
	private function robots_for( array $meta, array $robots = array() ): array {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = $meta;

		return ( new Frontend() )->filter_wp_robots( $robots );
	}

	/**
	 * Per-post nosnippet beats a global max-snippet and drops it.
	 */
	public function test_per_post_nosnippet_beats_global_max_snippet(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'index, follow, max-snippet:50';

		$robots = $this->robots_for( array( 'nosnippet' => '1' ) );

		$this->assertTrue( $robots['nosnippet'] );
		$this->assertArrayNotHasKey( 'max-snippet', $robots );
	}

	/**
	 * Per-post max-snippet beats both the global value and the default.
	 */
	public function test_per_post_max_snippet_beats_global(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'max-snippet:50';

		$robots = $this->robots_for( array( 'max_snippet' => '160' ) );

		$this->assertSame( '160', $robots['max-snippet'] );
	}

	/**
	 * A per-post max-snippet overrides a global nosnippet (same control,
	 * higher precedence tier).
	 */
	public function test_per_post_max_snippet_overrides_global_nosnippet(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'index, follow, nosnippet';

		$robots = $this->robots_for( array( 'max_snippet' => '-1' ) );

		$this->assertArrayNotHasKey( 'nosnippet', $robots );
		$this->assertSame( '-1', $robots['max-snippet'] );
	}

	/**
	 * Empty per-post values inherit the global setting.
	 */
	public function test_empty_per_post_values_inherit_global(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'max-snippet:50, max-image-preview:standard';

		$robots = $this->robots_for(
			array(
				'nosnippet'         => '',
				'max_snippet'       => '',
				'max_image_preview' => '',
				'noimageindex'      => '',
			)
		);

		$this->assertSame( '50', $robots['max-snippet'] );
		$this->assertSame( 'standard', $robots['max-image-preview'] );
		$this->assertArrayNotHasKey( 'noimageindex', $robots );
		$this->assertArrayNotHasKey( 'nosnippet', $robots );
	}

	/**
	 * Per-post max-image-preview beats the global value and core's default.
	 */
	public function test_per_post_max_image_preview_beats_global_and_core(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'max-image-preview:standard';

		$robots = $this->robots_for( array( 'max_image_preview' => 'none' ), array( 'max-image-preview' => 'large' ) );

		$this->assertSame( 'none', $robots['max-image-preview'] );
	}

	/**
	 * An unknown max-image-preview value is ignored (inherit).
	 */
	public function test_invalid_max_image_preview_is_ignored(): void {
		$robots = $this->robots_for( array( 'max_image_preview' => 'huge' ) );

		$this->assertSame( 'large', $robots['max-image-preview'] );
	}

	/**
	 * Per-post noimageindex is emitted.
	 */
	public function test_per_post_noimageindex(): void {
		$robots = $this->robots_for( array( 'noimageindex' => '1' ) );

		$this->assertTrue( $robots['noimageindex'] );
	}

	/**
	 * A global setting containing both nosnippet and max-snippet still
	 * resolves to nosnippet.
	 */
	public function test_global_nosnippet_and_max_snippet_resolves_to_nosnippet(): void {
		self::$options['SAMAN_SEO_global_robots'] = 'max-snippet:50, nosnippet';

		$robots = $this->robots_for( array() );

		$this->assertTrue( $robots['nosnippet'] );
		$this->assertArrayNotHasKey( 'max-snippet', $robots );
	}
}
