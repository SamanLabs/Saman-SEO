<?php
/**
 * Tests that every writer of _SAMAN_SEO_meta keeps custom_schema JSON intact.
 *
 * update_post_meta() unslashes its value before the registered sanitize
 * callback runs, so a writer that does not wp_slash() the array turns
 * `\"` inside the JSON into `"` and the sanitizer rejects it.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Audit_Controller;
use Saman\SEO\Api\SearchAppearance_Controller;
use Saman\SEO\Api\Tools_Controller;
use Saman\SEO\Service\Admin_UI;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Slashing coverage for meta writers.
 */
class SeoMetaSlashingTest extends TestCase {

	/**
	 * Custom schema containing characters JSON escapes with backslashes.
	 */
	private const SCHEMA_JSON = '{"@type":"Product","name":"The \"Best\" Widget \\\\ v2"}';

	/**
	 * Optional extra transformation applied by the "sanitize callback".
	 *
	 * @var callable|null
	 */
	private static $sanitize_extra = null;

	/**
	 * Install core-like slashing and meta write stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$sanitize_extra = null;

		$deep = static function ( $callback ) {
			$map = static function ( $value ) use ( &$map, $callback ) {
				if ( is_array( $value ) ) {
					return array_map( $map, $value );
				}

				return is_string( $value ) ? $callback( $value ) : $value;
			};

			return $map;
		};

		Functions\when( 'wp_slash' )->alias( $deep( 'addslashes' ) );
		Functions\when( 'wp_unslash' )->alias( $deep( 'stripslashes' ) );

		// Like core: unslash, then run the registered sanitize callback.
		Functions\when( 'update_post_meta' )->alias(
			static function ( $post_id, $key, $value ) use ( $deep ) {
				$value = $deep( 'stripslashes' )( $value );

				if ( Post_Meta::META_KEY === $key ) {
					$value = ( new Post_Meta() )->sanitize( $value );

					if ( self::$sanitize_extra ) {
						$value = ( self::$sanitize_extra )( $value );
					}
				}

				self::$post_meta[ (int) $post_id ][ $key ] = $value;
				return true;
			}
		);

		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'delete_transient' )->justReturn( true );
		Functions\when( 'rest_ensure_response' )->returnArg();
	}

	/**
	 * Seed post 7 with stored meta holding a custom schema.
	 *
	 * @return void
	 */
	private function seed_meta(): void {
		$this->make_post( array( 'ID' => 7 ) );

		self::$post_meta[7][ Post_Meta::META_KEY ] = ( new Post_Meta() )->sanitize(
			array(
				'title'         => 'Stored title',
				'custom_schema' => self::SCHEMA_JSON,
			)
		);
	}

	/**
	 * Assert the stored custom schema still decodes to the original.
	 *
	 * @return void
	 */
	private function assert_schema_kept(): void {
		$stored = self::$post_meta[7][ Post_Meta::META_KEY ];

		$this->assertNotSame( '', $stored['custom_schema'] );
		$this->assertSame( json_decode( self::SCHEMA_JSON, true ), json_decode( $stored['custom_schema'], true ) );
	}

	/**
	 * Request double.
	 *
	 * @param array $params Params.
	 * @return object
	 */
	private function request( array $params ) {
		return new class( $params ) {
			/**
			 * Params.
			 *
			 * @var array
			 */
			private $params;

			/**
			 * Constructor.
			 *
			 * @param array $params Params.
			 */
			public function __construct( $params ) {
				$this->params = $params;
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

			/**
			 * JSON body.
			 *
			 * @return array
			 */
			public function get_json_params() {
				return $this->params;
			}
		};
	}

	/**
	 * Bulk "Mark as noindex" keeps the custom schema.
	 */
	public function test_admin_bulk_action_keeps_schema(): void {
		$this->seed_meta();

		( new Admin_UI() )->handle_bulk_actions( 'https://example.org/wp-admin/edit.php', 'SAMAN_SEO_noindex', array( 7 ) );

		$this->assertSame( '1', self::$post_meta[7][ Post_Meta::META_KEY ]['noindex'] );
		$this->assert_schema_kept();
	}

	/**
	 * The quick noindex toggle keeps the custom schema.
	 */
	public function test_admin_noindex_toggle_keeps_schema(): void {
		$this->seed_meta();
		$_GET['post_id'] = '7';

		Functions\when( 'check_admin_referer' )->justReturn( 1 );
		Functions\when( 'wp_get_referer' )->justReturn( false );
		Functions\when( 'admin_url' )->justReturn( 'https://example.org/wp-admin/' );
		Functions\when( 'wp_safe_redirect' )->alias(
			static function () {
				throw new \LogicException( 'redirect' );
			}
		);

		try {
			( new Admin_UI() )->handle_toggle_noindex();
		} catch ( \LogicException $e ) {
			$this->assertSame( 'redirect', $e->getMessage() );
		} finally {
			unset( $_GET['post_id'] );
		}

		$this->assertSame( '1', self::$post_meta[7][ Post_Meta::META_KEY ]['noindex'] );
		$this->assert_schema_kept();
	}

	/**
	 * Applying an audit recommendation keeps the custom schema.
	 */
	public function test_audit_recommendation_keeps_schema(): void {
		$this->seed_meta();

		( new Audit_Controller() )->apply_recommendation(
			$this->request(
				array(
					'id'    => 7,
					'title' => 'Better title',
				)
			)
		);

		$this->assertSame( 'Better title', self::$post_meta[7][ Post_Meta::META_KEY ]['title'] );
		$this->assert_schema_kept();
	}

	/**
	 * Bulk editor title/description writes keep the custom schema.
	 */
	public function test_tools_bulk_editor_keeps_schema(): void {
		$this->seed_meta();

		$method = new \ReflectionMethod( Tools_Controller::class, 'update_canonical_seo_meta' );
		$method->setAccessible( true );
		$method->invoke( new Tools_Controller(), 7, array( 'description' => 'New description' ) );

		$this->assertSame( 'New description', self::$post_meta[7][ Post_Meta::META_KEY ]['description'] );
		$this->assert_schema_kept();
	}

	/**
	 * Homepage defaults synced to the front page keep its custom schema.
	 */
	public function test_homepage_sync_keeps_schema(): void {
		$this->seed_meta();
		self::$options['page_on_front'] = 7;

		( new SearchAppearance_Controller() )->save_homepage_defaults(
			$this->request(
				array(
					'meta_title'       => 'Home',
					'meta_description' => 'Welcome',
				)
			)
		);

		$this->assertSame( 'Home', self::$post_meta[7][ Post_Meta::META_KEY ]['title'] );
		$this->assert_schema_kept();
	}

	/**
	 * Schema Builder saves round-trip through the real sanitizer on the
	 * first write.
	 */
	public function test_store_custom_schema_round_trips_with_real_sanitizer(): void {
		$this->seed_meta();
		$schema = array(
			'@type' => 'Thing',
			'name'  => 'Quote " and backslash \\',
		);

		$this->assertTrue( Tools_Controller::store_custom_schema( 7, $schema ) );

		$stored = self::$post_meta[7][ Post_Meta::META_KEY ];
		$this->assertSame( $schema, json_decode( $stored['custom_schema'], true ) );
		$this->assertSame( 'Stored title', $stored['title'] );
	}

	/**
	 * When a filter alters the JSON (still valid), the first write is kept
	 * instead of the over-slashed retry, which would not decode.
	 */
	public function test_store_custom_schema_keeps_valid_altered_first_write(): void {
		$this->seed_meta();

		self::$sanitize_extra = static function ( $value ) {
			if ( '' !== $value['custom_schema'] ) {
				$value['custom_schema'] = str_replace( 'Widget', 'Gadget', $value['custom_schema'] );
			}

			return $value;
		};

		$schema = array(
			'@type' => 'Product',
			'name'  => 'Widget "Pro"',
		);

		$this->assertTrue( Tools_Controller::store_custom_schema( 7, $schema ) );

		$stored = json_decode( self::$post_meta[7][ Post_Meta::META_KEY ]['custom_schema'], true );
		$this->assertSame( 'Gadget "Pro"', $stored['name'] );
	}
}
