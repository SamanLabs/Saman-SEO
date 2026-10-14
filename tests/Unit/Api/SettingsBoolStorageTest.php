<?php
/**
 * Tests for boolean settings storage and 404 retention defaults.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Settings_Controller;
use Saman\SEO\Service\Request_Monitor;
use Saman\SEO\Service\Settings;
use Saman\SEO\Tests\TestCase;

/**
 * Switching a default-on setting off must stick.
 */
class SettingsBoolStorageTest extends TestCase {

	/**
	 * Install core-like option write stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		// Like core: update_option() with the value get_option() already
		// returns is a no-op. For a missing option that value is false.
		Functions\when( 'update_option' )->alias(
			static function ( $option, $value ) {
				$old = array_key_exists( $option, self::$options ) ? self::$options[ $option ] : false;

				if ( $value === $old ) {
					return false;
				}

				self::$options[ $option ] = $value;
				return true;
			}
		);
		Functions\when( 'add_option' )->alias(
			static function ( $option, $value = '' ) {
				if ( array_key_exists( $option, self::$options ) ) {
					return false;
				}

				self::$options[ $option ] = $value;
				return true;
			}
		);
		Functions\when( 'rest_sanitize_boolean' )->alias(
			static function ( $value ) {
				if ( is_string( $value ) ) {
					$value = strtolower( $value );
					if ( in_array( $value, array( '', 'false', '0' ), true ) ) {
						return false;
					}
				}

				return (bool) $value;
			}
		);
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	/**
	 * Minimal request double.
	 *
	 * @param array $json   JSON body.
	 * @param array $params Route params.
	 * @return object
	 */
	private function request( array $json, array $params = array() ) {
		return new class( $json, $params ) {
			/**
			 * JSON body.
			 *
			 * @var array
			 */
			private $json;

			/**
			 * Params.
			 *
			 * @var array
			 */
			private $params;

			/**
			 * Constructor.
			 *
			 * @param array $json   JSON body.
			 * @param array $params Params.
			 */
			public function __construct( $json, $params ) {
				$this->json   = $json;
				$this->params = $params;
			}

			/**
			 * JSON body.
			 *
			 * @return array
			 */
			public function get_json_params() {
				return $this->json;
			}

			/**
			 * One param.
			 *
			 * @param string $key Key.
			 * @return mixed
			 */
			public function get_param( $key ) {
				return $this->params[ $key ] ?? null;
			}
		};
	}

	/**
	 * Turning cleanup off on a site where the option was never stored
	 * persists, and the monitor then stops age-based deletion.
	 */
	public function test_disabling_never_saved_cleanup_sticks(): void {
		( new Settings_Controller() )->update_settings(
			$this->request(
				array(
					'enable_404_cleanup' => false,
					'module_404_log'     => true,
				)
			)
		);

		$this->assertSame( '', self::$options['SAMAN_SEO_enable_404_cleanup'] );
		$this->assertSame( '1', self::$options['SAMAN_SEO_module_404_log'] );
		$this->assertSame( 0, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * The single-setting endpoint stores booleans the same way.
	 */
	public function test_single_setting_stores_bool_as_string(): void {
		$controller = new Settings_Controller();

		$controller->update_setting( $this->request( array( 'value' => false ), array( 'key' => 'module_schema_video' ) ) );
		$this->assertSame( '', self::$options['SAMAN_SEO_module_schema_video'] );

		$response = $controller->update_setting( $this->request( array( 'value' => 'true' ), array( 'key' => 'module_schema_video' ) ) );
		$this->assertSame( '1', self::$options['SAMAN_SEO_module_schema_video'] );
		$this->assertTrue( $response['data']['value'] );
	}

	/**
	 * Non-boolean types are stored unchanged.
	 */
	public function test_other_types_unchanged(): void {
		( new Settings_Controller() )->update_settings( $this->request( array( 'cleanup_404_days' => '14' ) ) );

		$this->assertSame( 14, self::$options['SAMAN_SEO_cleanup_404_days'] );
	}

	/**
	 * Defaults create the retention options on existing installs.
	 */
	public function test_defaults_install_retention_options(): void {
		( new Settings() )->install_default_options();

		$this->assertSame( '1', self::$options['SAMAN_SEO_enable_404_cleanup'] );
		$this->assertSame( '30', self::$options['SAMAN_SEO_cleanup_404_days'] );
	}

	/**
	 * An "off" saved in the legacy consolidated array survives the defaults.
	 */
	public function test_legacy_retention_choice_is_carried_over(): void {
		self::$options['SAMAN_SEO_settings'] = array(
			'enable_404_cleanup' => false,
			'cleanup_404_days'   => '10',
		);

		( new Settings() )->install_default_options();

		$this->assertSame( '', self::$options['SAMAN_SEO_enable_404_cleanup'] );
		$this->assertSame( '10', self::$options['SAMAN_SEO_cleanup_404_days'] );
		$this->assertSame( 0, ( new Request_Monitor() )->get_retention_days() );
	}

	/**
	 * Saved individual options are never overwritten by defaults.
	 */
	public function test_existing_options_are_kept(): void {
		self::$options['SAMAN_SEO_enable_404_cleanup'] = '';
		self::$options['SAMAN_SEO_settings']           = array( 'enable_404_cleanup' => true );

		( new Settings() )->install_default_options();

		$this->assertSame( '', self::$options['SAMAN_SEO_enable_404_cleanup'] );
	}
}
