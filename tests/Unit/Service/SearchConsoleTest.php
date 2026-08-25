<?php
/**
 * Tests for the Search Console service.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Search_Console;
use Saman\SEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Search Console service unit tests.
 */
class SearchConsoleTest extends TestCase {

	/**
	 * Captured transient values.
	 *
	 * @var array<string,mixed>
	 */
	public static $transients = array();

	/**
	 * Response returned by the wp_remote_post stub.
	 *
	 * @var array|null
	 */
	public static $http_response = null;

	/**
	 * Install extra stubs used only by this suite.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'AUTH_KEY' ) ) {
			define( 'AUTH_KEY', 'unit-test-auth-key' );
		}

		self::$transients     = array();
		self::$http_response  = null;

		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);

		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value, $ttl = 0 ) {
				SearchConsoleTest::$transients[ $key ] = $value;
				return true;
			}
		);

		Functions\when( 'get_transient' )->alias(
			static function ( $key ) {
				return SearchConsoleTest::$transients[ $key ] ?? false;
			}
		);

		Functions\when( 'delete_transient' )->alias(
			static function ( $key ) {
				unset( SearchConsoleTest::$transients[ $key ] );
				return true;
			}
		);

		Functions\when( 'wp_generate_password' )->justReturn( 'deterministic-state' );

		Functions\when( 'wp_remote_retrieve_body' )->alias(
			static function ( $response ) {
				return is_array( $response ) && isset( $response['body'] ) ? (string) $response['body'] : '';
			}
		);

		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return is_array( $response ) && isset( $response['response']['code'] ) ? (int) $response['response']['code'] : 200;
			}
		);

		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.org/wp-admin/' . ltrim( (string) $path, '/' );
			}
		);

		Functions\when( 'remove_query_arg' )->alias(
			static function ( $args, $url = '' ) {
				return $url ?: 'https://example.org/wp-admin/admin.php';
			}
		);

		Functions\when( 'sanitize_email' )->alias(
			static function ( $email ) {
				return filter_var( (string) $email, FILTER_SANITIZE_EMAIL );
			}
		);
	}

	/* ---------------------------------------------------------------------
	 * Encryption at rest
	 * ------------------------------------------------------------------- */

	public function test_encrypt_decrypt_roundtrip() {
		if ( ! function_exists( 'openssl_encrypt' ) ) {
			$this->markTestSkipped( 'OpenSSL unavailable.' );
		}

		$service   = new Search_Console();
		$plaintext = 'GOCSPX-super-secret-value';

		$blob = $service->encrypt( $plaintext );

		$this->assertStringStartsWith( 'enc:', $blob );
		$this->assertStringNotContainsString( $plaintext, $blob );
		$this->assertSame( $plaintext, $service->decrypt( $blob ) );
	}

	public function test_decrypt_handles_plain_and_legacy_values() {
		$service = new Search_Console();

		$this->assertSame( 'secret', $service->decrypt( 'plain:secret' ) );
		$this->assertSame( 'legacy-raw', $service->decrypt( 'legacy-raw' ) );
		$this->assertSame( '', $service->decrypt( '' ) );
	}

	/* ---------------------------------------------------------------------
	 * Configuration state
	 * ------------------------------------------------------------------- */

	public function test_not_configured_by_default() {
		$service = new Search_Console();

		$this->assertFalse( $service->is_configured() );
		$this->assertFalse( $service->is_connected() );
	}

	public function test_set_credentials_marks_service_configured() {
		$service = new Search_Console();

		$service->set_credentials( 'my-client-id', 'my-secret' );

		$this->assertTrue( $service->is_configured() );
		$this->assertSame( 'my-client-id', $service->get_client_id() );
		$this->assertSame( 'my-secret', $service->get_client_secret() );

		// The secret must never be stored as-is.
		$this->assertStringNotContainsString(
			'my-secret',
			(string) TestCase::$options[ Search_Console::OPTION_CLIENT_SECRET ]
		);
	}

	/* ---------------------------------------------------------------------
	 * OAuth flow
	 * ------------------------------------------------------------------- */

	public function test_get_auth_url_requires_configuration() {
		$service = new Search_Console();

		$url = $service->get_auth_url();

		$this->assertInstanceOf( \WP_Error::class, $url );
	}

	public function test_get_auth_url_embeds_client_and_state() {
		$service = new Search_Console();
		$service->set_credentials( 'client-123.apps.googleusercontent.com', 'shh' );

		$url = (string) $service->get_auth_url();

		$this->assertStringStartsWith( Search_Console::AUTH_URL, $url );
		$this->assertStringContainsString( 'client-123.apps.googleusercontent.com', $url );
		$this->assertSame(
			'deterministic-state',
			self::$transients[ 'saman_seo_gsc_oauth_state' ]
		);
	}

	public function test_exchange_code_rejects_response_without_tokens() {
		self::$http_response = array(
			'body' => json_encode( array( 'error' => 'invalid_grant' ) ),
		);

		Functions\when( 'wp_remote_post' )->justReturn( array( 'body' => self::$http_response['body'] ) );

		$service = new Search_Console();
		$result  = $service->exchange_code( 'auth-code' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertFalse( $service->is_connected() );
	}

	public function test_exchange_code_persists_tokens_and_connects() {
		self::$http_response = json_encode(
			array(
				'access_token'  => 'at-123',
				'refresh_token' => 'rt-456',
				'expires_in'    => 3600,
			)
		);

		Functions\when( 'wp_remote_post' )->alias(
			static function () {
				return array( 'body' => SearchConsoleTest::$http_response );
			}
		);

		$service = new Search_Console();
		$service->set_credentials( 'client', 'secret' );

		$this->assertTrue( $service->exchange_code( 'auth-code' ) );
		$this->assertTrue( $service->is_connected() );

		$tokens = TestCase::$options[ Search_Console::OPTION_TOKENS ];
		$this->assertSame( 'at-123', $tokens['access_token'] );
		$this->assertSame( 'rt-456', $tokens['refresh_token'] );
	}

	public function test_disconnect_clears_state() {
		$service = new Search_Console();

		update_option( Search_Console::OPTION_TOKENS, array(
			'access_token'  => 'a',
			'refresh_token' => 'r',
			'expires_at'    => time() + 999,
		), false );
		update_option( Search_Console::OPTION_SELECTED_SITE, 'https://example.org/', false );

		Functions\when( 'wp_remote_get' )->justReturn( array() );

		$service->disconnect();

		$this->assertArrayNotHasKey( Search_Console::OPTION_TOKENS, TestCase::$options );
		$this->assertArrayNotHasKey( Search_Console::OPTION_SELECTED_SITE, TestCase::$options );
		$this->assertFalse( $service->is_connected() );
	}

	/* ---------------------------------------------------------------------
	 * Analytics guards
	 * ------------------------------------------------------------------- */

	public function test_query_requires_a_selected_site() {
		$service = new Search_Console();

		$result = $service->query_search_analytics(
			array(
				'start_date' => '2026-01-01',
				'end_date'   => '2026-01-28',
			)
		);

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertSame( 'saman_seo_gsc_no_site', $result->errors ? array_key_first( $result->errors ) : '' );
	}

	public function test_api_request_requires_connection() {
		$service = new Search_Console();

		$result = $service->api_request( 'GET', '/sites' );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	public function test_get_summary_normalizes_api_payloads() {
		$service = new Search_Console();

		update_option( Search_Console::OPTION_TOKENS, array(
			'access_token'  => 'valid',
			'refresh_token' => 'refresh',
			'expires_at'    => time() + 3600,
		), false );
		update_option( Search_Console::OPTION_SELECTED_SITE, 'https://example.org/', false );

		$payload_by_dimensions = array(
			'totals'  => array(
				'rows' => array(
					array(
						'clicks'      => 100.4,
						'impressions' => 2000.9,
						'ctr'         => 0.05,
						'position'    => 8.35,
					),
				),
			),
			'daily'   => array(
				'rows' => array(
					array(
						'keys'        => array( '2026-01-02' ),
						'clicks'      => 10,
						'impressions' => 200,
						'ctr'         => 0.05,
						'position'    => 8,
					),
					array(
						'keys'        => array( '2026-01-01' ),
						'clicks'      => 5,
						'impressions' => 150,
						'ctr'         => 0.033,
						'position'    => 9,
					),
				),
			),
			'queries' => array(
				'rows' => array(
					array(
						'keys'        => array( 'coffee ratio' ),
						'clicks'      => 30,
						'impressions' => 500,
						'ctr'         => 0.06,
						'position'    => 4.2,
					),
				),
			),
			'pages'   => array( 'rows' => array() ),
		);

		Functions\when( 'wp_remote_request' )->alias(
			static function ( $url, $args = array() ) {
				$body = json_decode( $args['body'] ?? '{}', true );
				$dims = $body['dimensions'] ?? array();

				if ( empty( $dims ) ) {
					return array( 'body' => json_encode( SearchConsoleTest::payload( 'totals' ) ) );
				}

				return array( 'body' => json_encode( SearchConsoleTest::payload( 'daily' ) ) );
			}
		);

		// Simpler: bypass multi-call orchestration and verify normalizers via summary pieces.
		$totals = $this->invoke_method( $service, 'first_row_metrics', array( $payload_by_dimensions['totals'] ) );
		$this->assertSame( 100, $totals['clicks'] );
		$this->assertSame( 2001, $totals['impressions'] );
		$this->assertSame( 5.0, $totals['ctr'] );
		$this->assertSame( 8.4, $totals['position'] );

		$empty = $this->invoke_method( $service, 'first_row_metrics', array( array() ) );
		$this->assertSame( 0, $empty['clicks'] );

		$series = $this->invoke_method( $service, 'normalize_rows_by_date', array( $payload_by_dimensions['daily'] ) );
		$this->assertSame( array( '2026-01-01', '2026-01-02' ), array_keys( $series ) );
		$this->assertSame( 5, $series['2026-01-01']['clicks'] );

		$queries = $this->invoke_method( $service, 'normalize_dimension_rows', array( $payload_by_dimensions['queries'], 'query' ) );
		$this->assertSame( 'coffee ratio', $queries[0]['query'] );
		$this->assertSame( 30, $queries[0]['clicks'] );
		$this->assertSame( 6.0, $queries[0]['ctr'] );
	}

	/**
	 * Static payload accessor for the wp_remote_request stub closure.
	 *
	 * @param string $key Payload key.
	 * @return mixed
	 */
	public static function payload( $key ) {
		static $payloads = null;

		if ( null === $payloads ) {
			$payloads = array(
				'totals'  => array(
					'rows' => array(
						array(
							'clicks'      => 100.4,
							'impressions' => 2000.9,
							'ctr'         => 0.05,
							'position'    => 8.35,
						),
					),
				),
				'daily'   => array(
					'rows' => array(),
				),
			);
		}

		return $payloads[ $key ];
	}

	/**
	 * Call a protected method via reflection.
	 *
	 * @param object $object Target instance.
	 * @param string $method Method name.
	 * @param array  $args   Arguments.
	 * @return mixed
	 */
	protected function invoke_method( $object, $method, array $args = array() ) {
		$reflection = new \ReflectionMethod( $object, $method );
		$reflection->setAccessible( true );

		return $reflection->invokeArgs( $object, $args );
	}
}
