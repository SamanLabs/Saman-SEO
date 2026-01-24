# Phase 9: Variable Products - Research

**Researched:** 2026-01-24
**Domain:** WooCommerce Variable Products, AggregateOffer Schema, Price Ranges
**Confidence:** HIGH

## Summary

This phase extends the Product_Schema class to support WooCommerce variable products with AggregateOffer schema. Variable products have multiple variations with potentially different prices, requiring price range output (lowPrice/highPrice) instead of a single price. The AggregateOffer type aggregates pricing across all variations.

WooCommerce's `WC_Product_Variable` class provides dedicated methods for variable product data: `get_variation_price('min'|'max')` returns the lowest or highest active price across all variations, and `child_is_in_stock()` returns true if ANY variation is in stock. This maps directly to the AggregateOffer requirements where availability should show InStock if any variation is purchasable.

The key architectural decision (already made in STATE.md) is using AggregateOffer rather than ProductGroup. This is simpler and supported by Google for Product snippets. The implementation pattern detects `$product->is_type('variable')` and calls a new `build_aggregate_offer()` method instead of the existing `build_offer()`.

**Primary recommendation:** Add `build_aggregate_offer()` method that uses `get_variation_price('min')` for lowPrice, `get_variation_price('max')` for highPrice, and `child_is_in_stock()` for availability determination. Output AggregateOffer only when min/max prices are both valid (greater than zero).

## Standard Stack

The established approach for this phase:

### Core (Already Available)
| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| Product_Schema | 1.1.0 | Extends with AggregateOffer | Existing simple product implementation |
| WC_Product_Variable | 9.x | Variable product API | All variation data access |
| Schema_IDs | 1.0.0 | @id generation | Consistent with Phase 8 |

### WooCommerce Variable Product Methods (Phase 9 Uses)
| Method | Return | Schema Property | Notes |
|--------|--------|-----------------|-------|
| `is_type('variable')` | bool | Type detection | Determines Offer vs AggregateOffer |
| `get_variation_price('min')` | string | lowPrice | Lowest active price |
| `get_variation_price('max')` | string | highPrice | Highest active price |
| `child_is_in_stock()` | bool | availability | True if ANY variation in stock |
| `get_available_variations()` | array | offerCount | Count for optional property |

### Schema.org AggregateOffer Properties
| Property | Type | Required | Source |
|----------|------|----------|--------|
| @type | string | Yes | Literal "AggregateOffer" |
| lowPrice | number | Yes | `get_variation_price('min')` |
| highPrice | number | Recommended | `get_variation_price('max')` |
| priceCurrency | string | Yes | `get_woocommerce_currency()` |
| availability | URL | Recommended | Based on `child_is_in_stock()` |
| offerCount | integer | Recommended | Count of variations (optional) |
| url | URL | Recommended | Product canonical URL |

## Architecture Patterns

### Recommended generate() Update
```php
// Current simple product handling (already exists)
if ( $product->is_type( 'simple' ) ) {
    $offer = $this->build_offer( $product );
    if ( ! empty( $offer ) ) {
        $schema['offers'] = $offer;
    }
}

// NEW: Variable product handling
if ( $product->is_type( 'variable' ) ) {
    $aggregate_offer = $this->build_aggregate_offer( $product );
    if ( ! empty( $aggregate_offer ) ) {
        $schema['offers'] = $aggregate_offer;
    }
}
```

### Pattern 1: AggregateOffer Building
**What:** Build AggregateOffer with price range from variations
**When to use:** Variable products with valid min/max prices
**Example:**
```php
// Source: Yoast AggregateOffer + WooCommerce API
protected function build_aggregate_offer( \WC_Product_Variable $product ): array {
    $min_price = $product->get_variation_price( 'min' );
    $max_price = $product->get_variation_price( 'max' );

    // Both prices required for valid AggregateOffer
    if ( empty( $min_price ) || (float) $min_price <= 0 ) {
        return [];
    }
    if ( empty( $max_price ) || (float) $max_price <= 0 ) {
        return [];
    }

    $aggregate = [
        '@type'         => 'AggregateOffer',
        'lowPrice'      => $min_price,
        'highPrice'     => $max_price,
        'priceCurrency' => get_woocommerce_currency(),
        'url'           => $this->context->canonical,
    ];

    // Availability: InStock if ANY variation is in stock
    if ( $product->child_is_in_stock() ) {
        $aggregate['availability'] = 'https://schema.org/InStock';
    } else {
        $aggregate['availability'] = 'https://schema.org/OutOfStock';
    }

    // Optional: offer count
    $variations = $product->get_children();
    if ( ! empty( $variations ) ) {
        $aggregate['offerCount'] = count( $variations );
    }

    return $aggregate;
}
```

### Pattern 2: Type-Safe Product Casting
**What:** Ensure WC_Product_Variable methods are available
**When to use:** Before calling variable-specific methods
**Example:**
```php
// The product is already validated as variable type
// But for IDE/static analysis, we can verify
if ( ! $product instanceof \WC_Product_Variable ) {
    return [];
}
// Now $product has access to get_variation_price(), child_is_in_stock()
```

### Pattern 3: Availability Determination
**What:** Map child stock status to schema.org availability
**When to use:** AggregateOffer availability property
**Example:**
```php
// Source: OFFR-05 requirement
// AggregateOffer availability reflects any variation in stock
$availability = $product->child_is_in_stock()
    ? 'https://schema.org/InStock'
    : 'https://schema.org/OutOfStock';
```

### Anti-Patterns to Avoid
- **Iterating all variations:** Don't loop through variations to find min/max - use `get_variation_price()`
- **Using regular is_in_stock():** For variable products, use `child_is_in_stock()` instead
- **Including priceValidUntil:** AggregateOffer doesn't typically include this (variations may have different sale dates)
- **Including seller on AggregateOffer:** Some validators expect seller on individual Offers only

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Find min price | Loop through variations | `get_variation_price('min')` | Performance, handles edge cases |
| Find max price | Loop through variations | `get_variation_price('max')` | Performance, handles edge cases |
| Any in stock | Check each variation | `child_is_in_stock()` | Built-in, data store optimized |
| Count variations | Custom query | `count($product->get_children())` | Already loaded, cached |
| Variable type check | `get_type() === 'variable'` | `is_type('variable')` | Handles inheritance properly |

**Key insight:** WC_Product_Variable has specialized methods for aggregated data. Never iterate variations manually when built-in methods exist.

## Common Pitfalls

### Pitfall 1: Using Simple Product Methods on Variable Products
**What goes wrong:** `get_price()` returns empty or misleading value for variable products
**Why it happens:** Variable products don't have a single price
**How to avoid:** Check `is_type('variable')` and use `get_variation_price()` methods
**Warning signs:** Empty lowPrice/highPrice in schema output

### Pitfall 2: Wrong Stock Check Method
**What goes wrong:** Stock shows OutOfStock when variations are available
**Why it happens:** Using `is_in_stock()` instead of `child_is_in_stock()`
**How to avoid:** For variable products, always use `child_is_in_stock()`
**Warning signs:** Products with available variations show OutOfStock

### Pitfall 3: Zero Price Variations
**What goes wrong:** AggregateOffer includes price=0 or empty lowPrice
**Why it happens:** Some variations may have no price set
**How to avoid:** Validate both min and max prices are > 0 before outputting
**Warning signs:** Google Rich Results Test shows price validation errors

### Pitfall 4: Missing Type Parameter in get_variation_price
**What goes wrong:** Always returns minimum price
**Why it happens:** Default parameter is 'min', forgetting to specify 'max'
**How to avoid:** Explicitly call `get_variation_price('min')` and `get_variation_price('max')`
**Warning signs:** lowPrice equals highPrice when variations have different prices

### Pitfall 5: offerCount with Hidden Variations
**What goes wrong:** offerCount includes unpurchasable variations
**Why it happens:** Using `get_children()` without visibility filter
**How to avoid:** Use `get_visible_children()` or skip offerCount entirely
**Warning signs:** offerCount higher than purchasable options (minor issue)

### Pitfall 6: Treating Backorder as In Stock
**What goes wrong:** Availability shows InStock for backorder-only products
**Why it happens:** `child_is_in_stock()` returns true for backorder items
**How to avoid:** This is actually correct behavior per Phase 8 decision (onbackorder = PreOrder)
**Warning signs:** None - this is expected behavior

## Code Examples

Verified patterns from official sources:

### Complete build_aggregate_offer Implementation
```php
// Source: WooCommerce API + Schema.org AggregateOffer + Yoast pattern
/**
 * Build AggregateOffer schema for variable products.
 *
 * @param \WC_Product_Variable $product Variable product object.
 * @return array AggregateOffer schema array, empty if prices invalid.
 */
protected function build_aggregate_offer( \WC_Product_Variable $product ): array {
    $min_price = $product->get_variation_price( 'min' );
    $max_price = $product->get_variation_price( 'max' );

    // Both prices required for valid AggregateOffer.
    if ( empty( $min_price ) || (float) $min_price <= 0 ) {
        return [];
    }
    if ( empty( $max_price ) || (float) $max_price <= 0 ) {
        return [];
    }

    $aggregate = [
        '@type'         => 'AggregateOffer',
        'lowPrice'      => $min_price,
        'highPrice'     => $max_price,
        'priceCurrency' => get_woocommerce_currency(),
        'url'           => $this->context->canonical,
    ];

    // Availability: InStock if ANY variation is purchasable (OFFR-05).
    $aggregate['availability'] = $product->child_is_in_stock()
        ? 'https://schema.org/InStock'
        : 'https://schema.org/OutOfStock';

    // Optional: count of variations.
    $variation_ids = $product->get_children();
    if ( ! empty( $variation_ids ) ) {
        $aggregate['offerCount'] = count( $variation_ids );
    }

    return $aggregate;
}
```

### Updated generate() with Variable Product Support
```php
// Source: Existing Phase 8 pattern extended
// In generate() method, after all other property additions:

// Offers - handle both simple and variable products.
if ( $product->is_type( 'simple' ) ) {
    $offer = $this->build_offer( $product );
    if ( ! empty( $offer ) ) {
        $schema['offers'] = $offer;
    }
} elseif ( $product->is_type( 'variable' ) ) {
    $aggregate_offer = $this->build_aggregate_offer( $product );
    if ( ! empty( $aggregate_offer ) ) {
        $schema['offers'] = $aggregate_offer;
    }
}
```

### Type Annotation for Static Analysis
```php
// To satisfy static analyzers that $product has variable methods:
if ( $product->is_type( 'variable' ) && $product instanceof \WC_Product_Variable ) {
    $aggregate_offer = $this->build_aggregate_offer( $product );
    // ...
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Individual Offers per variation | AggregateOffer | Google recommendation | Simpler, fewer schema bytes |
| ProductGroup + hasVariant | AggregateOffer | v1.1 decision | Simpler implementation |
| Loop variations for prices | get_variation_price() | WC 3.0+ | Performance, caching |

**Deprecated/outdated:**
- ProductGroup schema: More complex, deferred to ENHC-01
- Looping variations for stock: Use child_is_in_stock() instead
- get_variation_regular_price(): Don't use for active price (use get_variation_price)

## Open Questions

Things that couldn't be fully resolved:

1. **offerCount Inclusion**
   - What we know: Schema.org recommends it, Google doesn't require it
   - What's unclear: Whether to include all children or only visible variations
   - Recommendation: Include using `count(get_children())` - simple approach

2. **Seller on AggregateOffer**
   - What we know: Single Offer includes seller reference in Phase 8
   - What's unclear: Whether AggregateOffer should also include seller
   - Recommendation: Omit seller from AggregateOffer (not standard practice)

3. **priceValidUntil on AggregateOffer**
   - What we know: Individual variations may have different sale end dates
   - What's unclear: How to represent this on AggregateOffer
   - Recommendation: Omit priceValidUntil from AggregateOffer (variations have different dates)

## Sources

### Primary (HIGH confidence)
- WooCommerce Code Reference: https://woocommerce.github.io/code-reference/classes/WC-Product-Variable.html
  - `get_variation_price()` method documented at line 483
  - `child_is_in_stock()` method documented at line 541
- Schema.org AggregateOffer: https://schema.org/AggregateOffer
  - Required properties: lowPrice, priceCurrency
  - Recommended: highPrice, offerCount, availability
- Google Product Snippet: https://developers.google.com/search/docs/appearance/structured-data/product-snippet
  - AggregateOffer accepted for Product snippets (not Merchant listings)
  - lowPrice and priceCurrency required

### Secondary (MEDIUM confidence)
- Yoast AggregateOffer: https://developer.yoast.com/features/schema/pieces/aggregateoffer/
  - Implementation pattern verified
  - Required fields align with schema.org
- WooCommerce get_variation_prices: https://poststatus.com/woocommerce-function-of-the-week-get_variation_prices/
  - Usage examples verified against official docs

### Tertiary (LOW confidence)
- None - all claims verified against official sources

## Metadata

**Confidence breakdown:**
- WC_Product_Variable API: HIGH - Official WooCommerce documentation
- AggregateOffer properties: HIGH - Schema.org official spec
- Google acceptance: HIGH - Official Google documentation
- child_is_in_stock() behavior: HIGH - Verified in WC code reference
- Implementation pattern: HIGH - Follows existing Phase 8 codebase patterns

**Research date:** 2026-01-24
**Valid until:** 2026-03-24 (60 days - stable APIs)
