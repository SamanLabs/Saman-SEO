---
phase: 07
plan: 01
subsystem: woocommerce-integration
tags: [woocommerce, product-schema, schema-engine, integration]
dependency-graph:
  requires:
    - phase-01 (schema-engine-foundation)
  provides:
    - wc-schema-disable
    - product-schema-skeleton
    - schema-ids-product
  affects:
    - phase-08 (simple-products)
    - phase-09 (variable-products)
tech-stack:
  added: []
  patterns:
    - integration-hook-pattern
    - schema-registry-pattern
key-files:
  created:
    - includes/Schema/Types/class-product-schema.php
  modified:
    - includes/Integration/class-woocommerce.php
    - includes/Schema/class-schema-ids.php
decisions:
  - key: wc-schema-disable-priority
    choice: init priority 0
    reason: earliest hook to disable WC native schema before any output
  - key: product-schema-priority
    choice: 16
    reason: after Article (15), maintains schema type ordering convention
metrics:
  duration: 2 min
  completed: 2026-01-24
---

# Phase 7 Plan 1: WooCommerce Foundation Summary

**One-liner:** WC native schema disabled at init priority 0, Product_Schema skeleton registered via schema registry pattern.

## What Was Built

### 1. WooCommerce Integration Refactor

Refactored `class-woocommerce.php` to integrate with the schema engine:

- **Removed:** Old `SAMAN_SEO_jsonld_graph` filter approach and all legacy methods (`build_product_schema`, `build_offer_schema`, `get_product_brand`, `get_availability_url`, `build_rating_schema`, `build_reviews_schema`)
- **Added:** `disable_wc_structured_data()` - removes WC native JSON-LD output at init priority 0
- **Added:** `register_product_schema()` - registers Product_Schema type with schema registry
- **Kept:** `is_active()` and `get_meta_fields()` static methods (still needed)

```php
public function boot(): void {
    if ( ! self::is_active() ) {
        return;
    }
    add_action( 'init', [ $this, 'disable_wc_structured_data' ], 0 );
    add_action( 'saman_seo_register_schema_type', [ $this, 'register_product_schema' ] );
}
```

### 2. Product_Schema Skeleton

Created `class-product-schema.php` extending Abstract_Schema:

- `get_type()` - returns 'Product'
- `is_needed()` - strict check for `is_singular('product')` + valid WC_Product
- `generate()` - minimal schema with @type, @id, name, url
- `get_id()` - uses `Schema_IDs::product()` for consistent @id

```php
public function is_needed(): bool {
    // CRITICAL: Never output on archives/shop pages per Google guidelines.
    if ( ! is_singular( 'product' ) ) {
        return false;
    }
    $product = wc_get_product( $this->context->post );
    return $product instanceof \WC_Product;
}
```

### 3. Schema_IDs Extension

Added `product()` static method to `class-schema-ids.php`:

```php
public static function product( string $url ): string {
    return $url . '#product';
}
```

## Key Decisions Made

| Decision | Choice | Rationale |
|----------|--------|-----------|
| WC disable timing | init priority 0 | Earliest reliable hook to remove WC structured_data actions |
| Product schema priority | 16 | After Article (15), maintains ordering convention |
| is_singular check | In is_needed() | Google guidelines prohibit Product schema on archives |
| Skeleton approach | Minimal generate() | Phase 8 will add full property implementation |

## Deviations from Plan

None - plan executed exactly as written.

## Commits

| Hash | Type | Description |
|------|------|-------------|
| 387397e | refactor | Disable WC native schema and register Product type |
| 10620f5 | feat | Create Product_Schema skeleton class |
| 3f737a4 | feat | Add product() method to Schema_IDs |

## Next Phase Readiness

**Phase 8 (Simple Products) is unblocked:**

- Product_Schema class exists and is registered
- WC native schema is disabled (no duplicates)
- Schema_IDs::product() available for @id generation
- Product_Schema::generate() ready for property expansion

**Integration points established:**
- `saman_seo_schema_product_fields` filter available for customization
- WC_Product dependency pattern set in is_needed()
- Context pattern (`$this->context->post`, `$this->context->canonical`) ready for use
