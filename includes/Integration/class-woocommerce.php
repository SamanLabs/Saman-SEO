<?php
/**
 * WooCommerce Integration
 *
 * Integrates with WooCommerce to disable native schema output and register
 * Product schema type with the schema engine.
 *
 * @package Saman\SEO
 * @since   1.0.0
 */

namespace Saman\SEO\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WooCommerce plugin.
 *
 * Disables WooCommerce's native JSON-LD output to prevent duplicate schema,
 * then registers the Product_Schema type with the schema registry.
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
	 *
	 * Hooks into WordPress to disable WooCommerce native schema and register
	 * Product schema type with the schema engine.
	 *
	 * @return void
	 */
	public function boot(): void {
		if ( ! self::is_active() ) {
			return;
		}

		add_action( 'init', [ $this, 'disable_wc_structured_data' ], 0 );
		add_action( 'saman_seo_register_schema_type', [ $this, 'register_product_schema' ] );
	}

	/**
	 * Disable WooCommerce native structured data output.
	 *
	 * WooCommerce outputs its own JSON-LD schema which would conflict with
	 * our Product schema. This removes their output hooks at the earliest
	 * opportunity (init priority 0).
	 *
	 * @return void
	 */
	public function disable_wc_structured_data(): void {
		if ( ! function_exists( 'WC' ) || null === WC()->structured_data ) {
			return;
		}
		remove_action( 'wp_footer', [ WC()->structured_data, 'output_structured_data' ], 10 );
		remove_action( 'woocommerce_email_order_details', [ WC()->structured_data, 'output_email_structured_data' ], 30 );
	}

	/**
	 * Register Product schema type with the schema registry.
	 *
	 * Called during the saman_seo_register_schema_type action to add
	 * Product schema support for WooCommerce products.
	 *
	 * @param \Saman\SEO\Schema\Schema_Registry $registry The schema registry instance.
	 * @return void
	 */
	public function register_product_schema( \Saman\SEO\Schema\Schema_Registry $registry ): void {
		$registry->register(
			'product',
			\Saman\SEO\Schema\Types\Product_Schema::class,
			[
				'label'      => __( 'Product', 'saman-seo' ),
				'post_types' => [ 'product' ],
				'priority'   => 16,
			]
		);
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
