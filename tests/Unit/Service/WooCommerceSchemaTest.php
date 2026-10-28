<?php
/**
 * Tests that WooCommerce's own product schema is removed only when Saman prints its own.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Integration\WooCommerce;
use Saman\SEO\Tests\TestCase;

/**
 * WooCommerce structured data hand-off coverage.
 */
class WooCommerceSchemaTest extends TestCase {

	/**
	 * Hooks removed by the integration.
	 *
	 * @var array
	 */
	private $removed = array();

	/**
	 * Stand in for WooCommerce.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->removed = array();
		$structured    = new \stdClass();
		Functions\when( 'WC' )->justReturn( (object) array( 'structured_data' => $structured ) );
		Functions\when( 'remove_action' )->alias(
			function ( $hook ) {
				$this->removed[] = $hook;
				return true;
			}
		);
	}

	/**
	 * With Saman's head output on, WooCommerce's duplicate schema is removed.
	 */
	public function test_woocommerce_schema_is_replaced_when_saman_prints_schema() {
		( new WooCommerce() )->disable_wc_structured_data();

		$this->assertContains( 'wp_footer', $this->removed );
	}

	/**
	 * Another SEO plugin active turns Saman's head output off: WooCommerce keeps its schema.
	 */
	public function test_woocommerce_schema_stays_when_saman_head_output_is_off() {
		add_filter(
			'saman_seo_feature_toggle',
			static function ( $enabled, $feature ) {
				return 'frontend_head' === $feature ? false : $enabled;
			},
			10,
			2
		);

		( new WooCommerce() )->disable_wc_structured_data();

		$this->assertSame( array(), $this->removed );
	}

	/**
	 * The schema module switched off: WooCommerce keeps its schema.
	 */
	public function test_woocommerce_schema_stays_when_the_schema_module_is_off() {
		self::$options['SAMAN_SEO_module_schema'] = '0';

		( new WooCommerce() )->disable_wc_structured_data();

		$this->assertSame( array(), $this->removed );
	}
}
