<?php
/**
 * Tests for Product schema offer, review and category details.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Types\Product_Schema;
use Saman\SEO\Tests\TestCase;

require_once __DIR__ . '/Fixtures/woocommerce-stubs.php';

/**
 * Product schema coverage.
 */
class ProductSchemaTest extends TestCase {

	/**
	 * Set up WooCommerce function stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'get_woocommerce_currency' )->justReturn( 'USD' );
		Functions\when( 'is_wp_error' )->justReturn( false );
	}

	/**
	 * Build the schema object for a product page.
	 *
	 * @return Product_Schema
	 */
	private function make_schema(): Product_Schema {
		$post = $this->make_post(
			array(
				'ID'        => 10,
				'post_type' => 'product',
			)
		);

		$context            = new Schema_Context();
		$context->site_url  = 'https://example.org/';
		$context->site_name = 'Test Site';
		$context->post      = $post;
		$context->post_type = 'product';
		$context->canonical = 'https://example.org/product/widget/';
		$context->meta      = array();

		return new Product_Schema( $context );
	}

	/**
	 * Call a protected method on the schema.
	 *
	 * @param Product_Schema $schema Schema.
	 * @param string         $method Method.
	 * @param array          $args   Arguments.
	 * @return mixed
	 */
	private function call( Product_Schema $schema, string $method, array $args ) {
		$ref = new \ReflectionMethod( $schema, $method );
		$ref->setAccessible( true );

		return $ref->invokeArgs( $schema, $args );
	}

	/**
	 * Woo stock statuses map to the matching schema.org availability.
	 */
	public function test_backorder_maps_to_backorder(): void {
		$schema  = $this->make_schema();
		$product = new \WC_Product();

		$product->stock_status = 'onbackorder';
		$this->assertSame( 'https://schema.org/BackOrder', $this->call( $schema, 'get_availability_url', array( $product ) ) );

		$product->stock_status = 'outofstock';
		$this->assertSame( 'https://schema.org/OutOfStock', $this->call( $schema, 'get_availability_url', array( $product ) ) );

		$product->stock_status = 'instock';
		$this->assertSame( 'https://schema.org/InStock', $this->call( $schema, 'get_availability_url', array( $product ) ) );
	}

	/**
	 * An active scheduled sale emits validFrom and priceValidUntil.
	 */
	public function test_sale_window_emits_valid_from_and_until(): void {
		$schema  = $this->make_schema();
		$product = new \WC_Product();

		$product->on_sale   = true;
		$product->sale_from = new \WC_DateTime( '2026-10-01 00:00:00', new \DateTimeZone( 'UTC' ) );
		$product->sale_to   = new \WC_DateTime( '2026-10-31 00:00:00', new \DateTimeZone( 'UTC' ) );

		$offer = $this->call( $schema, 'build_offer', array( $product ) );

		$this->assertSame( '2026-10-01T00:00:00+00:00', $offer['validFrom'] );
		$this->assertSame( '2026-10-31', $offer['priceValidUntil'] );
	}

	/**
	 * A sale that has not started does not bound the current price.
	 */
	public function test_future_sale_does_not_emit_window(): void {
		$schema  = $this->make_schema();
		$product = new \WC_Product();

		$product->sale_from = new \WC_DateTime( '2099-01-01 00:00:00' );
		$product->sale_to   = new \WC_DateTime( '2099-01-31 00:00:00' );

		$offer = $this->call( $schema, 'build_offer', array( $product ) );

		$this->assertArrayNotHasKey( 'validFrom', $offer );
		$this->assertArrayNotHasKey( 'priceValidUntil', $offer );
	}

	/**
	 * Rating-only reviews (empty body) are skipped.
	 */
	public function test_review_without_body_is_skipped(): void {
		Functions\when( 'get_comment_meta' )->justReturn( '5' );

		$schema = $this->make_schema();

		$empty = new \WP_Comment(
			array(
				'comment_author'  => 'Ann',
				'comment_content' => '   ',
			)
		);
		$this->assertSame( array(), $this->call( $schema, 'build_review', array( $empty ) ) );

		$full   = new \WP_Comment(
			array(
				'comment_author'  => 'Ann',
				'comment_content' => 'Works great.',
			)
		);
		$review = $this->call( $schema, 'build_review', array( $full ) );

		$this->assertSame( 'Works great.', $review['reviewBody'] );
		$this->assertSame( 5, $review['reviewRating']['ratingValue'] );
	}

	/**
	 * Category comes from the primary product_cat, else a non-default term.
	 */
	public function test_category_from_primary_or_first_term(): void {
		$terms = array(
			7 => new \WP_Term(
				array(
					'term_id'  => 7,
					'name'     => 'Uncategorized',
					'taxonomy' => 'product_cat',
				)
			),
			8 => new \WP_Term(
				array(
					'term_id'  => 8,
					'name'     => 'Gadgets',
					'taxonomy' => 'product_cat',
				)
			),
			9 => new \WP_Term(
				array(
					'term_id'  => 9,
					'name'     => 'Tools',
					'taxonomy' => 'product_cat',
				)
			),
		);

		Functions\when( 'get_term' )->alias(
			static function ( $term_id ) use ( $terms ) {
				return $terms[ (int) $term_id ] ?? null;
			}
		);
		Functions\when( 'get_the_terms' )->justReturn( array( $terms[7], $terms[8], $terms[9] ) );
		self::$options['default_product_cat'] = 7;

		$schema  = $this->make_schema();
		$product = new \WC_Product();

		$data = array();
		$this->call( $schema, 'add_category', array( &$data, $product ) );
		$this->assertSame( 'Gadgets', $data['category'] );

		self::$post_meta[10]['_yoast_wpseo_primary_product_cat'] = 9;

		$data = array();
		$this->call( $schema, 'add_category', array( &$data, $product ) );
		$this->assertSame( 'Tools', $data['category'] );
	}

	/**
	 * No category terms means no category property.
	 */
	public function test_no_category_when_unassigned(): void {
		Functions\when( 'get_the_terms' )->justReturn( false );

		$schema = $this->make_schema();
		$data   = array();
		$this->call( $schema, 'add_category', array( &$data, new \WC_Product() ) );

		$this->assertArrayNotHasKey( 'category', $data );
	}
}
