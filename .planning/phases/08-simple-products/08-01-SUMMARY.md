# Phase 08 Plan 01: Product Core Properties Summary

**One-liner:** Product schema core properties (description, images, sku, brand, gtin/mpn, itemCondition) using WC_Product API with 3-level brand fallback

## What Was Done

Implemented all core Product schema properties for simple products per Google Merchant Listing requirements:

1. **Description** - Outputs from `get_short_description()` with HTML stripped via `wp_strip_all_tags()`
2. **Images** - Array of URLs starting with featured image, then gallery images, using 'full' size
3. **SKU** - Direct output from `get_sku()` when available
4. **Brand** - 3-level fallback chain: custom meta `_SAMAN_SEO_brand` > product attribute 'brand' > global option `SAMAN_SEO_product_default_brand`
5. **Identifiers** - GTIN and MPN from custom meta fields `_SAMAN_SEO_gtin` and `_SAMAN_SEO_mpn`
6. **itemCondition** - Full URL format `https://schema.org/NewCondition` (defaults to NewCondition, validates against schema.org enum)

All properties follow the conditional pattern: check for empty/valid value before adding to schema array.

## Files Changed

| File | Change Type | Summary |
|------|-------------|---------|
| `includes/Schema/Types/class-product-schema.php` | Modified | Added 6 helper methods, updated generate() |

## Key Code

**generate() method call order:**
```php
$this->add_description( $schema, $product );
$this->add_images( $schema, $product );
$this->add_sku( $schema, $product );
$this->add_brand( $schema, $product );
$this->add_identifiers( $schema, $product );
$this->add_condition( $schema, $product );
```

**Brand fallback chain:**
```php
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
```

## Requirements Satisfied

- [x] PROD-01: name property outputs from get_name() (already existed)
- [x] PROD-02: description outputs from short_description, HTML stripped
- [x] PROD-03: image outputs as array with featured + gallery images
- [x] PROD-04: sku outputs when product has SKU
- [x] PROD-05: url outputs from canonical (already existed)
- [x] PROD-06: brand outputs with 3-level fallback chain
- [x] PROD-07: gtin/mpn outputs from custom fields
- [x] PROD-08: itemCondition outputs with full URL format, defaults to NewCondition

## Decisions Made

| Decision | Rationale |
|----------|-----------|
| Use full URL for itemCondition | Google requires `https://schema.org/NewCondition` format, not just `NewCondition` |
| Default to NewCondition | Most WooCommerce products are new; safe default per research |
| Images as URL array (not ImageObject) | Simpler format, Google accepts both, reduces output size |
| Brand as nested object | Google recommends Brand @type with name for rich results |

## Deviations from Plan

None - plan executed exactly as written.

## Next Phase Readiness

**Ready for:** Plan 08-02 (Offers for simple products)
**Dependencies met:** Product core properties complete, offers will be added in next plan
**Blockers:** None

## Metadata

- **Duration:** ~3 min
- **Completed:** 2026-01-24
- **Tasks:** 3/3
- **Commits:** 3
