<?php
/**
 * Tests for Frontend robots directives and core head tags.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Robots and canonical coverage.
 */
class FrontendRobotsTest extends TestCase {

	/**
	 * Default output keeps index/follow implicit and emits advanced directives.
	 */
	public function test_default_directives(): void {
		$this->make_post();

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		// index/follow are implicit defaults — never emitted explicitly.
		$this->assertArrayNotHasKey( 'noindex', $robots );
		$this->assertArrayNotHasKey( 'nofollow', $robots );
		$this->assertTrue( $robots['max-snippet:-1'] );
		$this->assertTrue( $robots['max-image-preview:large'] );
		$this->assertTrue( $robots['max-video-preview:-1'] );
	}

	/**
	 * Meta noindex suppresses index and keeps nofollow out of conflicts.
	 */
	public function test_noindex_removes_index(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array(
			'noindex' => '1',
			'nofollow' => '1',
		);

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots(
			array(
				'index'  => true,
				'follow' => true,
			)
		);

		$this->assertArrayNotHasKey( 'index', $robots );
		$this->assertArrayNotHasKey( 'follow', $robots );
		$this->assertTrue( $robots['noindex'] );
		$this->assertTrue( $robots['nofollow'] );
	}

	/**
	 * Global default noindex applies to every page.
	 */
	public function test_global_default_noindex(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_default_noindex'] = '1';

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		$this->assertTrue( $robots['noindex'] );
		$this->assertArrayNotHasKey( 'index', $robots );
	}

	/**
	 * Search archives are noindexed by their saved defaults.
	 */
	public function test_search_archive_noindexed_by_default(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'archive', true );
		$this->set_context( 'search', true );

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		$this->assertTrue( $robots['noindex'] );
		$this->assertArrayNotHasKey( 'index', $robots );
	}

	/**
	 * Regular pages are indexable by default.
	 */
	public function test_regular_page_is_indexable(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'archive', true );
		$this->set_context( 'category', true );

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		$this->assertArrayNotHasKey( 'noindex', $robots );
	}

	/**
	 * Password-protected posts are noindexed.
	 */
	public function test_password_protected_noindex(): void {
		$this->make_post( array( 'post_password' => 'secret' ) );
		self::$context['password_required'] = true;

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		$this->assertTrue( $robots['noindex'] );
	}

	/**
	 * The saman_seo_robots_advanced filter can replace advanced directives.
	 */
	public function test_advanced_directives_filterable(): void {
		$this->make_post();

		add_filter(
			'saman_seo_robots_advanced',
			static function () {
				return array( 'max-snippet:50' );
			}
		);

		$frontend = new Frontend();
		$robots   = $frontend->filter_wp_robots( array() );

		$this->assertArrayHasKey( 'max-snippet:50', $robots );
		$this->assertArrayNotHasKey( 'max-image-preview:large', $robots );
	}
}
