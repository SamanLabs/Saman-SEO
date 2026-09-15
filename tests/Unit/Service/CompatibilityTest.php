<?php
/**
 * Tests for the Compatibility service's loopback pinning guard.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Compatibility;
use Saman\SEO\Tests\TestCase;

/**
 * The 127.0.0.1 pin is a Local WP workaround and must never reach production.
 */
class CompatibilityTest extends TestCase {

	/**
	 * Production install on a real domain: no cURL pinning, no timeout bump.
	 */
	public function test_production_does_not_pin_loopback(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );

		( new Compatibility() )->boot();

		$this->assertFalse( has_action( 'http_api_curl' ) );
		$this->assertFalse( has_filter( 'http_request_args' ) );
		$this->assertTrue( has_filter( 'cron_schedules' ), 'unrelated hooks still register' );
	}

	/**
	 * Staging is a real server too; treat it like production.
	 */
	public function test_staging_does_not_pin_loopback(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'staging' );

		( new Compatibility() )->boot();

		$this->assertFalse( has_action( 'http_api_curl' ) );
	}

	/**
	 * Local WP sets WP_ENVIRONMENT_TYPE=local; the workaround applies there.
	 */
	public function test_local_environment_pins_loopback(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );

		( new Compatibility() )->boot();

		$this->assertTrue( has_action( 'http_api_curl' ) );
		$this->assertTrue( has_filter( 'http_request_args' ) );
	}

	/**
	 * A dev stack that never set the environment type but uses a .local host.
	 */
	public function test_dot_local_host_pins_loopback_without_env_type(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
		Functions\when( 'home_url' )->justReturn( 'https://mysite.local/' );

		$this->assertTrue( ( new Compatibility() )->should_pin_loopback() );
	}

	/**
	 * Only whole-label suffixes count; "mylocal.com" is a real domain.
	 */
	public function test_suffix_match_requires_label_boundary(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'production' );
		Functions\when( 'home_url' )->justReturn( 'https://mylocal.com/' );

		$this->assertFalse( ( new Compatibility() )->should_pin_loopback() );
	}

	/**
	 * Site owners can force the decision either way.
	 */
	public function test_filter_overrides_decision(): void {
		Functions\when( 'wp_get_environment_type' )->justReturn( 'local' );
		add_filter( 'saman_seo_pin_loopback', '__return_false' );

		( new Compatibility() )->boot();

		$this->assertFalse( has_action( 'http_api_curl' ) );
	}
}
