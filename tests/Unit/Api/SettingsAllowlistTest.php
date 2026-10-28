<?php
/**
 * Tests that what the Settings and Local SEO pages save is accepted by the settings API.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Settings_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Settings allowlist coverage.
 */
class SettingsAllowlistTest extends TestCase {

	/**
	 * Install the stubs the controller needs.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'rest_ensure_response' )->returnArg();
		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				return filter_var( trim( (string) $email ), FILTER_VALIDATE_EMAIL ) ? trim( (string) $email ) : '';
			}
		);
	}

	/**
	 * Minimal request double.
	 *
	 * @param array $json JSON body.
	 * @return object
	 */
	private function request( array $json ) {
		return new class( $json ) {
			/**
			 * JSON body.
			 *
			 * @var array
			 */
			private $json;

			/**
			 * Constructor.
			 *
			 * @param array $json JSON body.
			 */
			public function __construct( $json ) {
				$this->json = $json;
			}

			/**
			 * JSON body.
			 *
			 * @return array
			 */
			public function get_json_params() {
				return $this->json;
			}
		};
	}

	/**
	 * The schema reads these options; the UI reported "Saved" while the API dropped them.
	 */
	public function test_knowledge_graph_and_local_business_details_are_stored() {
		$response = ( new Settings_Controller() )->update_settings(
			$this->request(
				array(
					'homepage_knowledge_type'    => 'person',
					'homepage_person_name'       => 'Ada Lovelace',
					'homepage_organization_logo' => 'https://example.org/logo.png',
					'local_business_name'        => 'Corner Bakery',
					'local_email'                => ' hello@example.org ',
					'local_opening_hours'        => '[{"day":"Monday","open":"08:00","close":"17:00"}]',
				)
			)
		);

		$this->assertArrayNotHasKey( 'rejected', $response['data'] );
		$this->assertSame( 'person', self::$options['SAMAN_SEO_homepage_knowledge_type'] );
		$this->assertSame( 'Ada Lovelace', self::$options['SAMAN_SEO_homepage_person_name'] );
		$this->assertSame( 'Corner Bakery', self::$options['SAMAN_SEO_local_business_name'] );
		$this->assertSame( 'hello@example.org', self::$options['SAMAN_SEO_local_email'] );
		$this->assertSame( '[{"day":"Monday","open":"08:00","close":"17:00"}]', self::$options['SAMAN_SEO_local_opening_hours'] );
	}

	/**
	 * The Search Console refresh token and client secret never go to the browser.
	 */
	public function test_search_console_credentials_are_not_readable() {
		self::$options['SAMAN_SEO_gsc_tokens'] = array( 'refresh_token' => 'secret' );
		$request = new class() {
			/**
			 * Read a param.
			 *
			 * @return string
			 */
			public function get_param() {
				return 'gsc_tokens';
			}
		};

		$this->assertInstanceOf( \WP_Error::class, ( new Settings_Controller() )->get_setting( $request ) );
	}

	/**
	 * A module key the admin uses but the API does not accept is a toggle that never saves
	 * (module_internal_linking vs module_internal_links).
	 */
	public function test_every_module_key_in_the_admin_is_accepted() {
		$allowed = new \ReflectionMethod( Settings_Controller::class, 'get_allowed_settings' );
		$allowed->setAccessible( true );
		$keys = $allowed->invoke( new Settings_Controller() );

		foreach ( array( 'src-v2/pages/Settings.js', 'src-v2/pages/Tools.js' ) as $file ) {
			preg_match_all( '/\bmodule_[a-z0-9_]+/', (string) file_get_contents( SAMAN_SEO_PATH . $file ), $matches );
			foreach ( array_unique( $matches[0] ) as $module ) {
				$this->assertArrayHasKey( $module, $keys, "{$file} uses {$module}" );
			}
		}
	}
}
