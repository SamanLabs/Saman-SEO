<?php
/**
 * Tests for robots directive precedence (per-post > global > default).
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Robots precedence coverage.
 */
class FrontendRobotsPrecedenceTest extends TestCase {

	/**
	 * A global max-snippet value survives the hard-coded default.
	 */
	public function test_global_setting_beats_default(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_global_robots'] = 'index, follow, max-snippet:50, max-image-preview:standard';

		$robots = ( new Frontend() )->filter_wp_robots( array() );

		$this->assertSame( '50', $robots['max-snippet'] );
		$this->assertSame( 'standard', $robots['max-image-preview'] );
		// Untouched defaults still apply.
		$this->assertSame( '-1', $robots['max-video-preview'] );
	}

	/**
	 * Our resolved value overrides a core default with the same key.
	 */
	public function test_global_setting_overrides_core_value(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_global_robots'] = 'max-image-preview:none';

		$robots = ( new Frontend() )->filter_wp_robots( array( 'max-image-preview' => 'large' ) );

		$this->assertSame( 'none', $robots['max-image-preview'] );
	}

	/**
	 * Per-post noindex beats a global "index".
	 */
	public function test_per_post_beats_global(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'noindex' => '1' );
		self::$options['SAMAN_SEO_global_robots']         = 'index, follow';

		$robots = ( new Frontend() )->filter_wp_robots( array() );

		$this->assertTrue( $robots['noindex'] );
		$this->assertArrayNotHasKey( 'index', $robots );
	}

	/**
	 * nosnippet drops any max-snippet directive.
	 */
	public function test_nosnippet_drops_max_snippet(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_global_robots'] = 'index, follow, nosnippet';

		$robots = ( new Frontend() )->filter_wp_robots( array() );

		$this->assertTrue( $robots['nosnippet'] );
		$this->assertArrayNotHasKey( 'max-snippet', $robots );
		$this->assertSame( 'large', $robots['max-image-preview'] );
	}

	/**
	 * nosnippet registered by another plugin also drops max-snippet.
	 */
	public function test_external_nosnippet_drops_max_snippet(): void {
		$this->make_post();

		$robots = ( new Frontend() )->filter_wp_robots( array( 'nosnippet' => true ) );

		$this->assertArrayNotHasKey( 'max-snippet', $robots );
	}
}
