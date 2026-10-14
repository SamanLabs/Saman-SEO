<?php
/**
 * Tests for the Schema Builder tools: review stripping and saving.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Tools_Controller;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Schema Builder backend coverage.
 */
class ToolsSchemaTest extends TestCase {

	/**
	 * Install slashing stubs that mirror WordPress.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_slash' )->alias(
			static function ( $value ) {
				return is_array( $value ) ? array_map( 'addslashes', $value ) : addslashes( (string) $value );
			}
		);
		Functions\when( 'wp_unslash' )->alias(
			static function ( $value ) {
				return is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( (string) $value );
			}
		);
	}

	/**
	 * Make update_post_meta() behave like core: unslash, then optionally run
	 * a sanitize callback that unslashes custom_schema again.
	 *
	 * @param bool $double_unslash Whether the sanitize callback unslashes.
	 * @return void
	 */
	private function use_core_like_meta_writes( bool $double_unslash ): void {
		Functions\when( 'update_post_meta' )->alias(
			static function ( $post_id, $key, $value ) use ( $double_unslash ) {
				$value = is_array( $value ) ? array_map( 'stripslashes', $value ) : stripslashes( (string) $value );

				if ( $double_unslash && is_array( $value ) && isset( $value['custom_schema'] ) ) {
					$value['custom_schema'] = stripslashes( $value['custom_schema'] );
				}

				self::$post_meta[ (int) $post_id ][ $key ] = $value;
				return true;
			}
		);
	}

	/**
	 * Schema containing characters JSON must escape.
	 *
	 * @return array
	 */
	private function tricky_schema(): array {
		return array(
			'@context' => 'https://schema.org',
			'@type'    => 'Product',
			'name'     => 'The "Best" Widget \\ v2',
			'url'      => 'https://example.org/widget/',
		);
	}

	/**
	 * Save writes custom_schema into _SAMAN_SEO_meta and keeps other keys.
	 *
	 * @dataProvider sanitize_modes
	 *
	 * @param bool $double_unslash Sanitize callback behavior.
	 */
	public function test_store_custom_schema_round_trips( bool $double_unslash ): void {
		$this->use_core_like_meta_writes( $double_unslash );

		self::$post_meta[7][ Post_Meta::META_KEY ] = array(
			'title'       => 'Keep me',
			'description' => 'And me',
		);

		$this->assertTrue( Tools_Controller::store_custom_schema( 7, $this->tricky_schema() ) );

		$stored = self::$post_meta[7][ Post_Meta::META_KEY ];

		$this->assertSame( 'Keep me', $stored['title'] );
		$this->assertSame( 'And me', $stored['description'] );
		$this->assertSame( $this->tricky_schema(), json_decode( $stored['custom_schema'], true ) );
		$this->assertArrayNotHasKey( '_SAMAN_SEO_schema', self::$post_meta[7] );
	}

	/**
	 * Sanitize callback modes.
	 *
	 * @return array<string,array{0:bool}>
	 */
	public function sanitize_modes(): array {
		return array(
			'core unslash only'         => array( false ),
			'sanitize unslashes again' => array( true ),
		);
	}

	/**
	 * Review and rating types are recognized in any form.
	 */
	public function test_review_type_detection(): void {
		$this->assertTrue( Tools_Controller::is_review_type( 'Review' ) );
		$this->assertTrue( Tools_Controller::is_review_type( 'AggregateRating' ) );
		$this->assertTrue( Tools_Controller::is_review_type( array( 'Thing', 'schema:Review' ) ) );
		$this->assertTrue( Tools_Controller::is_review_type( 'https://schema.org/AggregateRating' ) );
		$this->assertFalse( Tools_Controller::is_review_type( 'Product' ) );
		$this->assertFalse( Tools_Controller::is_review_type( 'FAQPage' ) );
	}

	/**
	 * AI output loses review/rating properties and nodes at any depth.
	 */
	public function test_strip_review_markup(): void {
		$stripped = 0;
		$result   = Tools_Controller::strip_review_markup(
			array(
				'name'            => 'Widget',
				'aggregateRating' => array( '@type' => 'AggregateRating', 'ratingValue' => 5 ),
				'review'          => array( array( '@type' => 'Review' ) ),
				'offers'          => array(
					'@type'  => 'Offer',
					'price'  => '10',
					'review' => array( '@type' => 'Review' ),
				),
				'subjectOf'       => array(
					array( '@type' => 'Review' ),
					array( '@type' => 'CreativeWork' ),
				),
			),
			$stripped
		);

		$this->assertSame(
			array(
				'name'      => 'Widget',
				'offers'    => array(
					'@type' => 'Offer',
					'price' => '10',
				),
				'subjectOf' => array(
					array( '@type' => 'CreativeWork' ),
				),
			),
			$result
		);
		$this->assertSame( 4, $stripped );
	}
}
