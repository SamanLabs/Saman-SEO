<?php
/**
 * WooCommerce Integration
 *
 * Adds Product schema support for WooCommerce products.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WooCommerce plugin.
 */
class WooCommerce {

	/**
	 * Check if WooCommerce is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return class_exists( 'WooCommerce' );
	}

	/**
	 * Initialize the integration.
	 */
	public function boot(): void {
		if ( ! self::is_active() ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_product_schema' ], 25, 1 );
	}

	/**
	 * Add Product schema to the JSON-LD graph for WooCommerce products.
	 *
	 * @param array $graph The JSON-LD graph array.
	 * @return array Modified graph.
	 */
	public function add_product_schema( array $graph ): array {
		if ( ! is_singular( 'product' ) ) {
			return $graph;
		}

		global $post;
		if ( ! $post ) {
			return $graph;
		}

		$product = wc_get_product( $post->ID );
		if ( ! $product ) {
			return $graph;
		}

		$product_schema = $this->build_product_schema( $product );
		if ( $product_schema ) {
			$graph[] = $product_schema;
		}

		return $graph;
	}

	/**
	 * Build the Product schema array.
	 *
	 * @param \WC_Product $product The WooCommerce product object.
	 * @return array|null Product schema or null if invalid.
	 */
	private function build_product_schema( $product ): ?array {
		if ( ! $product instanceof \WC_Product ) {
			return null;
		}

		$url   = get_permalink( $product->get_id() );
		$image = wp_get_attachment_url( $product->get_image_id() );

		$schema = [
			'@type'       => 'Product',
			'@id'         => $url . '#product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
			'url'         => $url,
		];

		// Image.
		if ( $image ) {
			$schema['image'] = $image;
		}

		// SKU.
		$sku = $product->get_sku();
		if ( $sku ) {
			$schema['sku'] = $sku;
		}

		// Brand (from product attribute or custom field).
		$brand = $this->get_product_brand( $product );
		if ( $brand ) {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}

		// GTIN/MPN/ISBN.
		$gtin = get_post_meta( $product->get_id(), '_SAMAN_SEO_gtin', true );
		if ( $gtin ) {
			$schema['gtin'] = $gtin;
		}

		$mpn = get_post_meta( $product->get_id(), '_SAMAN_SEO_mpn', true );
		if ( $mpn ) {
			$schema['mpn'] = $mpn;
		}

		// Offers.
		$schema['offers'] = $this->build_offer_schema( $product );

		// Aggregate Rating.
		$rating_schema = $this->build_rating_schema( $product );
		if ( $rating_schema ) {
			$schema['aggregateRating'] = $rating_schema;
		}

		// Reviews.
		$reviews = $this->build_reviews_schema( $product );
		if ( ! empty( $reviews ) ) {
			$schema['review'] = $reviews;
		}

		return $schema;
	}

	/**
	 * Get product brand from attribute or custom field.
	 *
	 * @param \WC_Product $product The product.
	 * @return string|null Brand name or null.
	 */
	private function get_product_brand( $product ): ?string {
		// Check custom field first.
		$brand = get_post_meta( $product->get_id(), '_SAMAN_SEO_brand', true );
		if ( $brand ) {
			return $brand;
		}

		// Check for brand attribute.
		$brand_attr = $product->get_attribute( 'brand' );
		if ( $brand_attr ) {
			return $brand_attr;
		}

		// Check for pa_brand taxonomy.
		$brand_terms = wp_get_post_terms( $product->get_id(), 'pa_brand', [ 'fields' => 'names' ] );
		if ( ! is_wp_error( $brand_terms ) && ! empty( $brand_terms ) ) {
			return $brand_terms[0];
		}

		return null;
	}

	/**
	 * Build the Offer schema for a product.
	 *
	 * @param \WC_Product $product The product.
	 * @return array Offer schema.
	 */
	private function build_offer_schema( $product ): array {
		$offer = [
			'@type'         => 'Offer',
			'url'           => get_permalink( $product->get_id() ),
			'priceCurrency' => get_woocommerce_currency(),
			'price'         => $product->get_price(),
			'availability'  => $this->get_availability_url( $product ),
		];

		// Price valid until (for sale items).
		$sale_end = $product->get_date_on_sale_to();
		if ( $sale_end ) {
			$offer['priceValidUntil'] = $sale_end->format( 'Y-m-d' );
		}

		// Item condition.
		$condition = get_post_meta( $product->get_id(), '_SAMAN_SEO_condition', true );
		if ( $condition ) {
			$offer['itemCondition'] = 'https://schema.org/' . $condition;
		} else {
			$offer['itemCondition'] = 'https://schema.org/NewCondition';
		}

		// Seller.
		$offer['seller'] = [
			'@type' => 'Organization',
			'name'  => get_bloginfo( 'name' ),
			'url'   => home_url(),
		];

		return $offer;
	}

	/**
	 * Get the schema.org availability URL for a product.
	 *
	 * @param \WC_Product $product The product.
	 * @return string Availability URL.
	 */
	private function get_availability_url( $product ): string {
		$stock_status = $product->get_stock_status();

		$availability_map = [
			'instock'     => 'https://schema.org/InStock',
			'outofstock'  => 'https://schema.org/OutOfStock',
			'onbackorder' => 'https://schema.org/BackOrder',
		];

		return $availability_map[ $stock_status ] ?? 'https://schema.org/InStock';
	}

	/**
	 * Build the AggregateRating schema.
	 *
	 * @param \WC_Product $product The product.
	 * @return array|null Rating schema or null if no reviews.
	 */
	private function build_rating_schema( $product ): ?array {
		$review_count = $product->get_review_count();
		$rating       = $product->get_average_rating();

		if ( $review_count < 1 || ! $rating ) {
			return null;
		}

		return [
			'@type'       => 'AggregateRating',
			'ratingValue' => number_format( (float) $rating, 1 ),
			'reviewCount' => $review_count,
			'bestRating'  => '5',
			'worstRating' => '1',
		];
	}

	/**
	 * Build Review schema from WooCommerce reviews.
	 *
	 * @param \WC_Product $product The product.
	 * @return array Array of review schemas (max 5).
	 */
	private function build_reviews_schema( $product ): array {
		$reviews = [];

		$comments = get_comments( [
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => 5, // Limit to 5 reviews in schema.
		] );

		foreach ( $comments as $comment ) {
			$rating = get_comment_meta( $comment->comment_ID, 'rating', true );
			if ( ! $rating ) {
				continue;
			}

			$reviews[] = [
				'@type'         => 'Review',
				'author'        => [
					'@type' => 'Person',
					'name'  => $comment->comment_author,
				],
				'datePublished' => get_comment_date( 'c', $comment ),
				'reviewBody'    => wp_strip_all_tags( $comment->comment_content ),
				'reviewRating'  => [
					'@type'       => 'Rating',
					'ratingValue' => $rating,
					'bestRating'  => '5',
					'worstRating' => '1',
				],
			];
		}

		return $reviews;
	}

	/**
	 * Get product schema settings/meta fields.
	 *
	 * @return array Array of meta field definitions.
	 */
	public static function get_meta_fields(): array {
		return [
			'_SAMAN_SEO_brand'     => [
				'label'       => __( 'Brand', 'saman-seo' ),
				'description' => __( 'Product brand name for schema.', 'saman-seo' ),
				'type'        => 'text',
			],
			'_SAMAN_SEO_gtin'      => [
				'label'       => __( 'GTIN/UPC/EAN', 'saman-seo' ),
				'description' => __( 'Global Trade Item Number (barcode).', 'saman-seo' ),
				'type'        => 'text',
			],
			'_SAMAN_SEO_mpn'       => [
				'label'       => __( 'MPN', 'saman-seo' ),
				'description' => __( 'Manufacturer Part Number.', 'saman-seo' ),
				'type'        => 'text',
			],
			'_SAMAN_SEO_condition' => [
				'label'       => __( 'Condition', 'saman-seo' ),
				'description' => __( 'Product condition for schema.', 'saman-seo' ),
				'type'        => 'select',
				'options'     => [
					'NewCondition'         => __( 'New', 'saman-seo' ),
					'UsedCondition'        => __( 'Used', 'saman-seo' ),
					'RefurbishedCondition' => __( 'Refurbished', 'saman-seo' ),
					'DamagedCondition'     => __( 'Damaged', 'saman-seo' ),
				],
			],
		];
	}
}
