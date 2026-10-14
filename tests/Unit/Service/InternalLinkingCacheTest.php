<?php
/**
 * Tests for the internal-linking rendered-content cache.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Internal_Linking\Engine;
use Saman\SEO\Internal_Linking\Repository;
use Saman\SEO\Tests\TestCase;

/**
 * Cache keying and bypass coverage.
 */
class InternalLinkingCacheTest extends TestCase {

	/**
	 * In-memory transients.
	 *
	 * @var array<string,mixed>
	 */
	public static $transients = array();

	/**
	 * Transient keys read during the test.
	 *
	 * @var string[]
	 */
	public static $reads = array();

	/**
	 * Engine under test.
	 *
	 * @var Engine
	 */
	private Engine $engine;

	/**
	 * Install stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$transients = array();
		self::$reads      = array();

		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'wp_doing_ajax' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_preview' )->justReturn( false );
		Functions\when( 'is_post_publicly_viewable' )->justReturn( true );
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) {
				self::$reads[] = $key;
				return self::$transients[ $key ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value ) {
				self::$transients[ $key ] = $value;
				return true;
			}
		);

		$repository = $this->createMock( Repository::class );
		$repository->method( 'get_settings' )->willReturn( array( 'cache_rendered_content' => true ) );
		$repository->method( 'get_version' )->willReturn( 'v1' );
		$repository->method( 'get_rules' )->willReturn( array() );

		$this->engine = new Engine( $repository );
	}

	/**
	 * Seed a poisoned cache entry for every key the engine could read.
	 *
	 * @param string $html Cached HTML.
	 * @return void
	 */
	private function poison_reads( string $html ): void {
		foreach ( self::$reads as $key ) {
			self::$transients[ $key ] = array(
				'content' => $html,
				'version' => 'v1',
			);
		}
	}

	/**
	 * A render cached for one input is never served for another.
	 */
	public function test_cache_key_depends_on_content(): void {
		$post = $this->make_post( array( 'ID' => 9 ) );

		$this->engine->filter( '<p>Full article</p>', array( 'post' => $post ) );
		$this->poison_reads( '<p>Full article (cached)</p>' );

		$output = $this->engine->filter( '<p>Teaser only</p>', array( 'post' => $post ) );

		$this->assertSame( '<p>Teaser only</p>', $output );
		$this->assertNotSame( self::$reads[0], self::$reads[1] );
	}

	/**
	 * Password-protected posts skip the shared cache entirely.
	 */
	public function test_password_protected_post_bypasses_cache(): void {
		$post = $this->make_post(
			array(
				'ID'            => 10,
				'post_password' => 'secret',
			)
		);

		$this->engine->filter( '<p>Body</p>', array( 'post' => $post ) );

		$this->assertSame( array(), self::$reads );
	}

	/**
	 * Unpublished posts skip the shared cache entirely.
	 */
	public function test_unpublished_post_bypasses_cache(): void {
		$post = $this->make_post(
			array(
				'ID'          => 11,
				'post_status' => 'draft',
			)
		);

		$this->engine->filter( '<p>Body</p>', array( 'post' => $post ) );

		$this->assertSame( array(), self::$reads );
	}

	/**
	 * Saving a post moves it to a new cache generation.
	 */
	public function test_invalidate_changes_key(): void {
		$post = $this->make_post( array( 'ID' => 12 ) );

		$this->engine->filter( '<p>Body</p>', array( 'post' => $post ) );
		Engine::invalidate_post_cache( 12 );
		$this->engine->filter( '<p>Body</p>', array( 'post' => $post ) );

		$this->assertCount( 2, self::$reads );
		$this->assertNotSame( self::$reads[0], self::$reads[1] );
	}
}
