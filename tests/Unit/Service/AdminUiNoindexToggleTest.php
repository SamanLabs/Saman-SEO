<?php
/**
 * Tests for the post list "Toggle noindex" quick action.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Admin_UI;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Noindex toggle authorization coverage.
 */
class AdminUiNoindexToggleTest extends TestCase {

	/**
	 * Nonce actions verified during the test.
	 *
	 * @var string[]
	 */
	private $nonce_actions = array();

	/**
	 * Install stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->nonce_actions = array();
		$actions             = &$this->nonce_actions;

		Functions\when( 'wp_die' )->alias(
			static function () {
				throw new \RuntimeException( 'wp_die' );
			}
		);
		Functions\when( 'check_admin_referer' )->alias(
			static function ( $action ) use ( &$actions ) {
				$actions[] = $action;
				return 1;
			}
		);
		Functions\when( 'wp_get_referer' )->justReturn( false );
		Functions\when( 'admin_url' )->justReturn( 'https://example.org/wp-admin/' );
		Functions\when( 'wp_safe_redirect' )->alias(
			static function () {
				throw new \LogicException( 'redirect' );
			}
		);
	}

	/**
	 * Clean request globals.
	 */
	protected function tearDown(): void {
		unset( $_GET['post_id'] );
		parent::tearDown();
	}

	/**
	 * A user who cannot edit this specific post is refused.
	 */
	public function test_denies_without_edit_post(): void {
		$_GET['post_id'] = '7';

		Functions\when( 'current_user_can' )->alias(
			static function ( $cap, ...$args ) {
				// Can edit posts in general, but not post 7.
				return 'edit_posts' === $cap;
			}
		);

		try {
			( new Admin_UI() )->handle_toggle_noindex();
			$this->fail( 'Expected wp_die().' );
		} catch ( \RuntimeException $e ) {
			$this->assertSame( 'wp_die', $e->getMessage() );
		}

		$this->assertArrayNotHasKey( 7, self::$post_meta );
		$this->assertSame( array(), $this->nonce_actions );
	}

	/**
	 * An editor of the post toggles it, with a nonce bound to the post.
	 */
	public function test_toggles_with_edit_post_and_post_bound_nonce(): void {
		$_GET['post_id'] = '7';

		Functions\when( 'current_user_can' )->alias(
			static function ( $cap, ...$args ) {
				return 'edit_post' === $cap && array( 7 ) === $args;
			}
		);

		try {
			( new Admin_UI() )->handle_toggle_noindex();
		} catch ( \LogicException $e ) {
			$this->assertSame( 'redirect', $e->getMessage() );
		}

		$this->assertSame( array( 'SAMAN_SEO_toggle_noindex_7' ), $this->nonce_actions );
		$this->assertSame( '1', self::$post_meta[7][ Post_Meta::META_KEY ]['noindex'] ?? null );
	}
}
