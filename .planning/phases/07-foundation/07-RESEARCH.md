# Phase 7: Foundation - Research

**Researched:** 2026-01-23
**Domain:** WooCommerce Integration, JSON-LD Schema Conflict Prevention
**Confidence:** HIGH

## Summary

This research addresses the WooCommerce integration foundation phase, focusing on how to properly disable WooCommerce's native JSON-LD schema output and integrate Product schema with the existing Saman SEO schema engine architecture.

WooCommerce outputs its own Product schema via the `WC_Structured_Data` class, hooked to `wp_footer` with priority 10. To prevent duplicate schema, this MUST be disabled before our plugin outputs its own Product schema. The existing codebase already has a WooCommerce integration file (`includes/Integration/class-woocommerce.php`) that hooks into `SAMAN_SEO_jsonld_graph` but does NOT disable WooCommerce's native output - this is a critical gap.

The existing schema architecture uses `Schema_Registry` for type registration with `Abstract_Schema` base class, `Schema_Context` for environment data, and `Schema_Graph_Manager` for building the final JSON-LD output. Product schema must follow this pattern exactly, registering via `saman_seo_register_schema_type` action when WooCommerce is active.

**Primary recommendation:** Disable WooCommerce native schema via `remove_action()` on init hook, then create `Product_Schema` class extending `Abstract_Schema` with `is_needed()` returning true only when `is_singular('product')` evaluates true.

## Standard Stack

The established approach for this domain:

### Core (Existing Architecture)
| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| Schema_Registry | 1.0.0 | Singleton type registration | Plugin's established pattern |
| Abstract_Schema | 1.0.0 | Base class for all schemas | Contract for schema generation |
| Schema_Context | 1.0.0 | Environment data injection | Decouples from WordPress globals |
| Schema_IDs | 1.0.0 | Consistent @id generation | Cross-reference integrity |
| Schema_Graph_Manager | 1.0.0 | Graph assembly | Single @context, combined @graph |

### WooCommerce Dependencies
| Component | Version | Purpose | When to Use |
|-----------|---------|---------|-------------|
| WC_Structured_Data | 9.x | WC native schema | DISABLE this |
| wc_get_product() | 9.x | Product object retrieval | Always for product data |
| WC_Product | 9.x | Product model interface | Type hints, method access |
| get_woocommerce_currency() | 9.x | Current currency code | Offer priceCurrency |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Extend Abstract_Schema | Hook SAMAN_SEO_jsonld_graph | Existing code does this, but doesn't integrate with registry |
| WC()->structured_data removal | woocommerce_structured_data_product filter | Filter allows modification but not complete removal |

## Architecture Patterns

### Recommended Integration Structure
```
includes/
├── Integration/
│   └── class-woocommerce.php    # Already exists, needs refactoring
└── Schema/
    └── Types/
        └── class-product-schema.php  # NEW: Extends Abstract_Schema
```

### Pattern 1: Conditional Schema Registration
**What:** Register Product schema type only when WooCommerce is active
**When to use:** Plugin initialization, after WooCommerce loads
**Example:**
```php
// Source: Existing pattern in saman-seo.php
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $registry->register(
        'product',
        \Saman\SEO\Schema\Types\Product_Schema::class,
        [
            'label'      => 'Product',
            'post_types' => [ 'product' ],
            'priority'   => 16, // After Article (15), before FAQPage (18)
        ]
    );
});
```

### Pattern 2: Disable Native Schema on Init
**What:** Remove WooCommerce schema generation actions early
**When to use:** `init` hook with priority 0 (earliest possible)
**Example:**
```php
// Source: WooCommerce Code Reference + Community verification
add_action( 'init', function() {
    if ( ! function_exists( 'WC' ) || ! WC()->structured_data ) {
        return;
    }

    // Remove frontend structured data output
    remove_action( 'wp_footer', [ WC()->structured_data, 'output_structured_data' ], 10 );

    // Remove product data generation on single product pages
    remove_action( 'woocommerce_single_product_summary', [ WC()->structured_data, 'generate_product_data' ], 60 );
}, 0 );
```

### Pattern 3: Context-Aware is_needed()
**What:** Product schema only on singular product pages
**When to use:** Abstract_Schema::is_needed() implementation
**Example:**
```php
// Source: Existing Article_Schema pattern
public function is_needed(): bool {
    // Never output on archives/shop pages per Google guidelines
    if ( ! is_singular( 'product' ) ) {
        return false;
    }

    // Require valid WC_Product
    $product = wc_get_product( $this->context->post );
    return $product instanceof \WC_Product;
}
```

### Anti-Patterns to Avoid
- **Filtering WC data instead of removing:** Using `woocommerce_structured_data_product` filter returns modified data, not removal. Google will still see WC's schema alongside ours = duplicate.
- **Checking WooCommerce late:** `class_exists('WooCommerce')` after init may miss WC if it loads later. Use `plugins_loaded` priority 10+ or `woocommerce_loaded` action.
- **Output on shop/category pages:** Google explicitly states merchant listings are for "pages where a shopper can purchase a product" - never archive pages.

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Product object access | `get_post_meta()` | `wc_get_product()` | Handles all product types, variable/grouped |
| Currency code | Hardcoded 'USD' | `get_woocommerce_currency()` | Multi-currency support |
| Stock status mapping | Custom switch | WC stock status constants | Matches WC admin UI |
| Price retrieval | `get_post_meta('_price')` | `$product->get_price()` | Sale price logic, tax handling |
| Schema @id generation | Custom strings | `Schema_IDs::product()` | Consistency (add this method) |

**Key insight:** WooCommerce's `WC_Product` class has methods for everything - never access `_price`, `_sku`, `_stock_status` meta directly. The product object handles sale prices, variable product ranges, tax, etc.

## Common Pitfalls

### Pitfall 1: Duplicate Schema Output
**What goes wrong:** Both WooCommerce and our plugin output Product schema, Google sees duplicate entities
**Why it happens:** Failing to disable WC native schema OR disabling on wrong hook timing
**How to avoid:**
1. Disable WC schema on `init` priority 0 (before WC registers hooks)
2. Verify with View Page Source that only one `"@type": "Product"` exists
**Warning signs:** Google Rich Results Test shows warnings about duplicate Product entities

### Pitfall 2: Schema on Archive Pages
**What goes wrong:** Product schema appears on shop page, category pages
**Why it happens:** Checking `is_single()` instead of `is_singular('product')` or not checking at all
**How to avoid:** Explicit `is_singular('product')` check in `is_needed()`
**Warning signs:** Multiple products in single @graph array on non-product pages

### Pitfall 3: WooCommerce Not Loaded
**What goes wrong:** PHP fatal error when accessing `WC()` or `wc_get_product()`
**Why it happens:** Code runs before WooCommerce initializes
**How to avoid:**
1. Check `class_exists('WooCommerce')` AND `function_exists('WC')`
2. Register schema on `saman_seo_register_schema_type` which fires after `plugins_loaded`
**Warning signs:** "Call to undefined function WC()" errors

### Pitfall 4: Missing @id Cross-References
**What goes wrong:** Product schema doesn't link to Organization seller
**Why it happens:** Using inline Organization instead of @id reference
**How to avoid:** Use `['@id' => Schema_IDs::organization()]` for seller property
**Warning signs:** Duplicate Organization entities in graph

### Pitfall 5: Empty Required Fields
**What goes wrong:** Rich results don't appear
**Why it happens:** Products without images, zero price, or missing name
**How to avoid:** Return empty array from `generate()` if required fields missing
**Warning signs:** Google Search Console errors for invalid structured data

## Code Examples

Verified patterns from official sources and existing codebase:

### Disable WooCommerce Native Schema
```php
// Source: WooCommerce Code Reference, verified against community patterns
// Place in Integration/WooCommerce class boot() method

public function boot(): void {
    if ( ! self::is_active() ) {
        return;
    }

    // Disable WC native schema - MUST happen before WC registers hooks
    add_action( 'init', [ $this, 'disable_wc_structured_data' ], 0 );

    // Register Product schema type
    add_action( 'saman_seo_register_schema_type', [ $this, 'register_product_schema' ] );
}

public function disable_wc_structured_data(): void {
    if ( ! function_exists( 'WC' ) || null === WC()->structured_data ) {
        return;
    }

    // Remove ALL WC structured data output (Product, Website, Breadcrumbs, etc.)
    remove_action( 'wp_footer', [ WC()->structured_data, 'output_structured_data' ], 10 );

    // Also remove email structured data (optional, but cleaner)
    remove_action( 'woocommerce_email_order_details', [ WC()->structured_data, 'output_email_structured_data' ], 30 );
}
```

### Product Schema Registration
```php
// Source: Existing Plugin class pattern

public function register_product_schema( Schema_Registry $registry ): void {
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
```

### Product Schema Class Skeleton
```php
// Source: Existing Article_Schema pattern

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

class Product_Schema extends Abstract_Schema {

    public function get_type(): string {
        return 'Product';
    }

    public function is_needed(): bool {
        // CRITICAL: Only singular product pages
        if ( ! is_singular( 'product' ) ) {
            return false;
        }

        // Require valid product
        $product = wc_get_product( $this->context->post );
        return $product instanceof \WC_Product;
    }

    public function generate(): array {
        $product = wc_get_product( $this->context->post );

        // Validate required fields exist
        if ( ! $product || empty( $product->get_name() ) ) {
            return [];
        }

        $schema = [
            '@type' => $this->get_type(),
            '@id'   => $this->get_id(),
            'name'  => $product->get_name(),
            'url'   => $this->context->canonical,
        ];

        // Additional properties added in Phase 8

        return $this->apply_fields_filter( $schema );
    }
}
```

### Schema_IDs Extension
```php
// Source: Add to existing Schema_IDs class

/**
 * Get the Product schema @id for a specific URL.
 *
 * @param string $url The canonical URL of the product.
 * @return string URL#product identifier.
 */
public static function product( string $url ): string {
    return $url . '#product';
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Inline schema in templates | JSON-LD in footer | WC 3.0 (2017) | Cleaner, no template modifications |
| Single Offer for variables | AggregateOffer for variables | Google guidance 2023+ | Shows price range in SERPs |
| ProductGroup for variants | AggregateOffer OR ProductGroup | Google 2024 update | ProductGroup provides more detail |
| Hook into WC filter | Remove WC + add own | Best practice | Eliminates duplicate risk |

**Deprecated/outdated:**
- Direct template schema output (pre-WC 3.0): Replaced by JSON-LD class
- Microdata format: Still valid but JSON-LD strongly preferred by Google

## Open Questions

Things that couldn't be fully resolved:

1. **ProductGroup vs AggregateOffer timing**
   - What we know: Google now supports ProductGroup for variants, which is more detailed
   - What's unclear: Whether to implement ProductGroup in v1.1 or defer to future
   - Recommendation: Per prior decisions, use AggregateOffer for v1.1 (simpler), defer ProductGroup

2. **WC Breadcrumb schema conflict**
   - What we know: WC outputs BreadcrumbList, our plugin also outputs Breadcrumb_Schema
   - What's unclear: Whether disabling `output_structured_data` removes WC breadcrumbs too
   - Recommendation: Test during implementation, likely yes since it removes all WC structured data

3. **WC Website schema conflict**
   - What we know: WC outputs WebSite schema with SearchAction
   - What's unclear: Whether our WebSite_Schema handles SearchAction for WC
   - Recommendation: Verify during Phase 7 implementation, may need WebSite_Schema enhancement

## Sources

### Primary (HIGH confidence)
- Existing codebase: `includes/Schema/class-abstract-schema.php` - Contract pattern
- Existing codebase: `includes/Schema/class-schema-registry.php` - Registration API
- Existing codebase: `includes/class-saman-seo-plugin.php` - Boot/registration order
- WooCommerce Code Reference: https://woocommerce.github.io/code-reference/classes/WC-Structured-Data.html
- WooCommerce Wiki: https://github.com/woocommerce/woocommerce/wiki/Structured-data-for-products
- Google Merchant Listing: https://developers.google.com/search/docs/appearance/structured-data/merchant-listing

### Secondary (MEDIUM confidence)
- Community pattern: https://remicorson.com/remove-the-woocommerce-3-json-ld-structured-data-format/
- Community pattern: https://aceplugins.com/remove-structured-data-partially-from-woocommerce/
- WPCode Library: https://library.wpcode.com/snippet/e50xnlw5/

### Tertiary (LOW confidence)
- None - all claims verified against official sources

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Based on existing codebase analysis
- Architecture: HIGH - Extends proven existing patterns
- WC disable method: HIGH - Multiple official and community sources agree
- Pitfalls: HIGH - Documented in Google guidelines and community experience

**Research date:** 2026-01-23
**Valid until:** 2026-03-23 (60 days - stable APIs, WC hooks unlikely to change)
