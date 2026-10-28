<?php
/**
 * Tests for who may switch and purge the page cache.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Page_Cache_Controller;
use Saman\SEO\Service\Page_Cache;
use Saman\SEO\Tests\TestCase;

/**
 * Page_Cache_Controller permission coverage.
 */
class PageCacheControllerTest extends TestCase {

	/**
	 * Controller under test.
	 *
	 * @var Page_Cache_Controller
	 */
	private $controller;

	/**
	 * Build the controller.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );
		$this->controller = new Page_Cache_Controller( new Page_Cache() );
	}

	/**
	 * A single-site admin may toggle and purge.
	 */
	public function test_single_site_admin_may_toggle_and_purge(): void {
		$this->assertTrue( $this->controller->toggle_permission_check() );
		$this->assertTrue( $this->controller->network_permission_check() );
	}

	/**
	 * The drop-in, wp-config.php and the cache folder serve the whole network.
	 */
	public function test_subsite_admin_cannot_toggle_or_purge_the_shared_cache(): void {
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'is_super_admin' )->justReturn( false );

		$this->assertFalse( $this->controller->toggle_permission_check() );
		$this->assertFalse( $this->controller->network_permission_check() );
		$this->assertTrue( $this->controller->permission_check(), 'status and per-site settings stay readable' );
	}

	/**
	 * Toggling writes wp-config.php, which DISALLOW_FILE_MODS exists to forbid.
	 */
	public function test_toggle_respects_disallowed_file_mods(): void {
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( false );

		$this->assertInstanceOf( \WP_Error::class, $this->controller->toggle_permission_check() );
		$this->assertTrue( $this->controller->network_permission_check(), 'a purge writes no code and stays allowed' );
	}
}
