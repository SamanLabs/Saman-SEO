<?php
/**
 * Tests for _SAMAN_SEO_meta registration: REST schema and late post types.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Post meta registration coverage.
 */
class PostMetaRegistrationTest extends TestCase {

	/**
	 * Post types registered with register_post_meta(), in order.
	 *
	 * @var string[]
	 */
	private $registered = array();

	/**
	 * Post types returned by get_post_types().
	 *
	 * @var string[]
	 */
	private $public_types = array( 'post', 'page', 'attachment' );

	/**
	 * Install registration stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->registered   = array();
		$this->public_types = array( 'post', 'page', 'attachment' );

		Functions\when( 'register_post_meta' )->alias(
			function ( $post_type, $key, $args ) {
				$this->registered[] = $post_type;
				return true;
			}
		);
		Functions\when( 'post_type_supports' )->justReturn( true );
		Functions\when( 'add_post_type_support' )->justReturn( true );
		Functions\when( 'get_post_types' )->alias(
			function () {
				return array_combine( $this->public_types, $this->public_types );
			}
		);
	}

	/**
	 * Every object in the REST schema allows additional properties, so a
	 * stored key the schema does not list never nulls the whole field.
	 */
	public function test_every_object_allows_additional_properties(): void {
		$schema = Post_Meta::get_rest_schema();

		$this->assertTrue( $schema['additionalProperties'] );
		$this->assertTrue( $schema['properties']['recipe']['additionalProperties'] );
		$this->assertTrue( $schema['properties']['event']['additionalProperties'] );

		$walk = function ( $node, $path ) use ( &$walk ) {
			$types = (array) ( $node['type'] ?? array() );
			if ( in_array( 'object', $types, true ) ) {
				$this->assertArrayHasKey( 'additionalProperties', $node, $path . ' must set additionalProperties' );
			}
			foreach ( $node['properties'] ?? array() as $name => $child ) {
				$walk( $child, $path . '.' . $name );
			}
			if ( isset( $node['items'] ) && is_array( $node['items'] ) ) {
				$walk( $node['items'], $path . '[]' );
			}
		};
		$walk( $schema, 'meta' );
	}

	/**
	 * Apply core's rest_default_additional_properties_to_false(): object
	 * schemas without an explicit additionalProperties get false.
	 *
	 * @param array $schema Schema.
	 * @return array
	 */
	private static function default_additional_properties_to_false( array $schema ): array {
		$types = (array) ( $schema['type'] ?? array() );

		if ( in_array( 'object', $types, true ) ) {
			foreach ( $schema['properties'] ?? array() as $key => $child ) {
				$schema['properties'][ $key ] = self::default_additional_properties_to_false( $child );
			}
			if ( ! isset( $schema['additionalProperties'] ) ) {
				$schema['additionalProperties'] = false;
			}
		}

		if ( in_array( 'array', $types, true ) && isset( $schema['items'] ) ) {
			$schema['items'] = self::default_additional_properties_to_false( $schema['items'] );
		}

		return $schema;
	}

	/**
	 * Keys core's validator would reject as additional properties.
	 *
	 * @param mixed  $value  Value.
	 * @param array  $schema Schema.
	 * @param string $path   Path for messages.
	 * @return string[]
	 */
	private static function rejected_keys( $value, array $schema, string $path = 'meta' ): array {
		$types    = (array) ( $schema['type'] ?? array() );
		$rejected = array();

		if ( is_array( $value ) && in_array( 'object', $types, true ) ) {
			foreach ( $value as $key => $child ) {
				if ( isset( $schema['properties'][ $key ] ) ) {
					$rejected = array_merge( $rejected, self::rejected_keys( $child, $schema['properties'][ $key ], $path . '.' . $key ) );
				} elseif ( false === ( $schema['additionalProperties'] ?? true ) ) {
					$rejected[] = $path . '.' . $key;
				}
			}
		} elseif ( is_array( $value ) && in_array( 'array', $types, true ) && isset( $schema['items'] ) ) {
			foreach ( $value as $index => $child ) {
				$rejected = array_merge( $rejected, self::rejected_keys( $child, $schema['items'], $path . '[' . $index . ']' ) );
			}
		}

		return $rejected;
	}

	/**
	 * Stored recipe/event blocks and legacy keys pass core's validation
	 * (with its additionalProperties=false default applied), so the REST
	 * API returns the meta instead of null.
	 */
	public function test_stored_meta_with_nested_blocks_validates(): void {
		$stored = ( new Post_Meta() )->sanitize(
			array(
				'title'                => 'Soup',
				'secondary_keyphrases' => array( 'broth' ),
				'recipe'               => array(
					'name'               => 'Soup',
					'recipeIngredient'   => array( 'Water' ),
					'recipeInstructions' => array(
						array(
							'name' => 'Boil',
							'text' => 'Boil it.',
						),
					),
				),
				'event'                => array(
					'name'       => 'Launch',
					'start_date' => '2026-11-01T10:00',
				),
			)
		);
		$this->assertNotEmpty( $stored['recipe'] );
		$this->assertNotEmpty( $stored['event'] );

		$stored['legacy_key'] = 'written by an older version';

		$schema = self::default_additional_properties_to_false( Post_Meta::get_rest_schema() );

		$this->assertSame( array(), self::rejected_keys( $stored, $schema ) );
	}

	/**
	 * Init-time registration covers public types except attachments.
	 */
	public function test_register_meta_covers_public_types(): void {
		$this->public_types = array( 'post', 'page', 'attachment', 'book' );

		( new Post_Meta() )->register_meta();

		$this->assertSame( array( 'post', 'page', 'book' ), $this->registered );
	}

	/**
	 * A post type registered after init:99 gets the meta field too.
	 */
	public function test_late_post_type_is_registered(): void {
		$meta = new Post_Meta();
		$meta->register_meta();
		$this->registered = array();

		$this->public_types[] = 'movie';
		$meta->register_meta_for_late_post_type( 'movie' );
		$meta->register_meta_for_late_post_type( 'movie' );

		$this->assertSame( array( 'movie' ), $this->registered, 'Registered once.' );
	}

	/**
	 * Late types that do not qualify, or that the filter removes, are skipped.
	 */
	public function test_late_ineligible_types_are_skipped(): void {
		$meta = new Post_Meta();
		$meta->register_meta();
		$this->registered = array();

		// Not public / no UI: get_post_types() does not return it.
		$meta->register_meta_for_late_post_type( 'internal_log' );
		$meta->register_meta_for_late_post_type( 'attachment' );

		$this->public_types[] = 'secret';
		add_filter(
			'saman_seo_editor_post_types',
			static function ( $types ) {
				return array_values( array_diff( $types, array( 'secret' ) ) );
			}
		);
		$meta->register_meta_for_late_post_type( 'secret' );

		$this->assertSame( array(), $this->registered );
	}

	/**
	 * Types registered before init:99 are left to register_meta().
	 */
	public function test_early_types_wait_for_init(): void {
		$this->public_types[] = 'book';

		( new Post_Meta() )->register_meta_for_late_post_type( 'book' );

		$this->assertSame( array(), $this->registered );
	}

	/**
	 * Boot hooks the late registration.
	 */
	public function test_boot_hooks_registered_post_type(): void {
		( new Post_Meta() )->boot();

		$this->assertNotEmpty( self::$hooks['registered_post_type'] ?? array() );
	}
}
