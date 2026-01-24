<?php
/**
 * Product Schema class.
 *
 * Generates Product schema for WooCommerce products.
 * Only outputs on singular product pages.
 *
 * @package Saman\SEO\Schema\Types
 * @since   1.1.0
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product schema implementation for WooCommerce products.
 *
 * Integrates with the schema engine to provide structured data
 * for Google Product rich results.
 */
class Product_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The schema type.
	 */
	public function get_type(): string {
		return 'Product';
	}

	/**
	 * Determine if Product schema should be output.
	 *
	 * Only outputs on singular product pages with valid WC_Product.
	 *
	 * @return bool True if on singular product page with valid product.
	 */
	public function is_needed(): bool {
		// CRITICAL: Never output on archives/shop pages per Google guidelines.
		if ( ! is_singular( 'product' ) ) {
			return false;
		}

		// Require valid WC_Product object.
		$product = wc_get_product( $this->context->post );
		return $product instanceof \WC_Product;
	}

	/**
	 * Generate the Product schema array.
	 *
	 * @return array Product schema data (without @context).
	 */
	public function generate(): array {
		$product = wc_get_product( $this->context->post );

		// Validate required fields exist.
		if ( ! $product || empty( $product->get_name() ) ) {
			return [];
		}

		$schema = [
			'@type' => $this->get_type(),
			'@id'   => $this->get_id(),
			'name'  => $product->get_name(),
			'url'   => $this->context->canonical,
		];

		// Add optional properties conditionally.
		$this->add_description( $schema, $product );
		$this->add_images( $schema, $product );
		$this->add_sku( $schema, $product );
		$this->add_brand( $schema, $product );
		$this->add_identifiers( $schema, $product );
		$this->add_condition( $schema, $product );

		// Offers (only for simple products in this phase).
		if ( $product->is_type( 'simple' ) ) {
			$offer = $this->build_offer( $product );
			if ( ! empty( $offer ) ) {
				$schema['offers'] = $offer;
			}
		}

		return $this->apply_fields_filter( $schema );
	}

	/**
	 * Get the Product schema @id.
	 *
	 * @return string URL#product identifier.
	 */
	protected function get_id(): string {
		return Schema_IDs::product( $this->context->canonical );
	}

	/**
	 * Add description from short description.
	 *
	 * Strips HTML tags for clean schema output.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_description( array &$schema, \WC_Product $product ): void {
		$description = $product->get_short_description();
		if ( ! empty( $description ) ) {
			$schema['description'] = wp_strip_all_tags( $description );
		}
	}

	/**
	 * Add images (featured + gallery).
	 *
	 * Featured image is first, followed by gallery images.
	 * Uses 'full' size for maximum quality.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_images( array &$schema, \WC_Product $product ): void {
		$images = $this->get_images( $product );
		if ( ! empty( $images ) ) {
			$schema['image'] = $images;
		}
	}

	/**
	 * Get product images as URL array.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return array Array of image URLs.
	 */
	protected function get_images( \WC_Product $product ): array {
		$images = [];

		// Featured image first.
		$featured_id = $product->get_image_id();
		if ( $featured_id ) {
			$url = wp_get_attachment_image_url( $featured_id, 'full' );
			if ( $url ) {
				$images[] = $url;
			}
		}

		// Gallery images.
		$gallery_ids = $product->get_gallery_image_ids();
		foreach ( $gallery_ids as $attachment_id ) {
			$url = wp_get_attachment_image_url( $attachment_id, 'full' );
			if ( $url ) {
				$images[] = $url;
			}
		}

		return $images;
	}

	/**
	 * Add SKU to schema.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_sku( array &$schema, \WC_Product $product ): void {
		$sku = $product->get_sku();
		if ( ! empty( $sku ) ) {
			$schema['sku'] = $sku;
		}
	}

	/**
	 * Get brand with 3-level fallback chain.
	 *
	 * Priority: 1. Custom meta field, 2. Product attribute, 3. Global setting.
	 *
	 * @param \WC_Product $product WooCommerce product.
	 * @return string Brand name or empty string.
	 */
	protected function get_brand( \WC_Product $product ): string {
		// 1. Custom meta field (highest priority).
		$brand = get_post_meta( $product->get_id(), '_SAMAN_SEO_brand', true );
		if ( ! empty( $brand ) ) {
			return $brand;
		}

		// 2. Product attribute 'brand'.
		$brand = $product->get_attribute( 'brand' );
		if ( ! empty( $brand ) ) {
			return $brand;
		}

		// 3. Global fallback setting.
		return get_option( 'SAMAN_SEO_product_default_brand', '' );
	}

	/**
	 * Add brand to schema as Brand object.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_brand( array &$schema, \WC_Product $product ): void {
		$brand = $this->get_brand( $product );
		if ( ! empty( $brand ) ) {
			$schema['brand'] = [
				'@type' => 'Brand',
				'name'  => $brand,
			];
		}
	}

	/**
	 * Add GTIN and MPN identifiers from custom fields.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_identifiers( array &$schema, \WC_Product $product ): void {
		$gtin = get_post_meta( $product->get_id(), '_SAMAN_SEO_gtin', true );
		if ( ! empty( $gtin ) ) {
			$schema['gtin'] = $gtin;
		}

		$mpn = get_post_meta( $product->get_id(), '_SAMAN_SEO_mpn', true );
		if ( ! empty( $mpn ) ) {
			$schema['mpn'] = $mpn;
		}
	}

	/**
	 * Add itemCondition with full schema.org URL format.
	 *
	 * Defaults to NewCondition if not specified.
	 * Uses full URL format per Google requirements.
	 *
	 * @param array      $schema  Schema array by reference.
	 * @param \WC_Product $product WooCommerce product.
	 */
	protected function add_condition( array &$schema, \WC_Product $product ): void {
		$condition = get_post_meta( $product->get_id(), '_SAMAN_SEO_condition', true );

		// Default to NewCondition.
		if ( empty( $condition ) ) {
			$condition = 'NewCondition';
		}

		// Valid conditions per schema.org.
		$valid = [
			'NewCondition',
			'UsedCondition',
			'RefurbishedCondition',
			'DamagedCondition',
		];

		if ( in_array( $condition, $valid, true ) ) {
			$schema['itemCondition'] = 'https://schema.org/' . $condition;
		}
	}

	/**
	 * Get schema.org availability URL from WooCommerce stock status.
	 *
	 * @param \WC_Product $product The product object.
	 * @return string Schema.org availability URL.
	 */
	protected function get_availability_url( \WC_Product $product ): string {
		$status = $product->get_stock_status();

		$map = [
			'instock'     => 'https://schema.org/InStock',
			'outofstock'  => 'https://schema.org/OutOfStock',
			'onbackorder' => 'https://schema.org/PreOrder',
		];

		return $map[ $status ] ?? 'https://schema.org/InStock';
	}

	/**
	 * Build Offer schema for simple products.
	 *
	 * @param \WC_Product $product The product object.
	 * @return array Offer schema array, empty if price is invalid.
	 */
	protected function build_offer( \WC_Product $product ): array {
		$price = $product->get_price();

		// Price is required for merchant listings - skip if zero/empty.
		if ( empty( $price ) || (float) $price <= 0 ) {
			return [];
		}

		$offer = [
			'@type'         => 'Offer',
			'price'         => $price,
			'priceCurrency' => get_woocommerce_currency(),
			'availability'  => $this->get_availability_url( $product ),
			'url'           => $this->context->canonical,
		];

		// priceValidUntil from sale end date (OFFR-03).
		$sale_end = $product->get_date_on_sale_to();
		if ( $sale_end instanceof \WC_DateTime ) {
			$offer['priceValidUntil'] = $sale_end->format( 'Y-m-d' );
		}

		// Seller reference to Organization (OFFR-06).
		// Only add when Organization type is active (most stores).
		if ( get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ) === 'organization' ) {
			$offer['seller'] = [ '@id' => Schema_IDs::organization() ];
		}

		return $offer;
	}
}
