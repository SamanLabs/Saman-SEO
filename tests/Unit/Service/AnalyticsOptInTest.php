<?php
/**
 * Tests that usage analytics is opt-in and existing installs are migrated.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Analytics;
use Saman\SEO\Service\Settings;
use Saman\SEO\Tests\TestCase;

/**
 * Analytics opt-in coverage.
 */
class AnalyticsOptInTest extends TestCase {

	/**
	 * Analytics is off when nothing has been stored.
	 */
	public function test_disabled_without_options(): void {
		$this->assertFalse( ( new Analytics() )->is_enabled() );
	}

	/**
	 * Existing installs that inherited the old "on" default are switched off once.
	 */
	public function test_migration_turns_tracking_off_once(): void {
		self::$options['SAMAN_SEO_module_analytics'] = '1';
		self::$options['SAMAN_SEO_enable_analytics'] = '1';

		Settings::maybe_migrate_analytics_opt_in();

		$this->assertSame( '0', self::$options['SAMAN_SEO_module_analytics'] );
		$this->assertSame( '0', self::$options['SAMAN_SEO_enable_analytics'] );
		$this->assertFalse( ( new Analytics() )->is_enabled() );

		// A later explicit opt-in survives subsequent requests.
		self::$options['SAMAN_SEO_module_analytics'] = '1';
		Settings::maybe_migrate_analytics_opt_in();

		$this->assertSame( '1', self::$options['SAMAN_SEO_module_analytics'] );
		$this->assertTrue( ( new Analytics() )->is_enabled() );
	}

	/**
	 * No script is enqueued while analytics is off.
	 */
	public function test_no_script_when_disabled(): void {
		$enqueued = false;

		Functions\when( 'wp_enqueue_script' )->alias(
			static function () use ( &$enqueued ) {
				$enqueued = true;
			}
		);
		Functions\when( 'current_user_can' )->justReturn( true );

		$_GET['page'] = 'saman-seo';
		( new Analytics() )->enqueue_matomo_tracking( 'toplevel_page_saman-seo' );
		unset( $_GET['page'] );

		$this->assertFalse( $enqueued );
	}
}
