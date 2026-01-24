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

		// Additional properties (description, image, sku, offers, etc.)
		// will be added in Phase 8: Simple Products.

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
}
