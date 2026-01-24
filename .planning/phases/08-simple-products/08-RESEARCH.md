# Phase 8: Simple Products - Research

**Researched:** 2026-01-23
**Domain:** WooCommerce Product Schema, Google Merchant Listings, Offers
**Confidence:** HIGH

## Summary

This phase completes the Product_Schema skeleton created in Phase 7 with full property output for simple products. The existing foundation provides `is_needed()` (singular product check), `get_type()`, and basic `generate()` returning name/url. Phase 8 adds the remaining Google-required properties: description, image array, SKU, brand, GTIN/MPN, itemCondition, and a single Offer with price, availability, and seller.

WooCommerce's `WC_Product` API provides all necessary data via getter methods. Key methods: `get_short_description()` for description, `get_gallery_image_ids()` + `get_image_id()` for images, `get_price()` for pricing, `get_stock_status()` for availability. Brand discovery uses a fallback chain: custom meta field `_SAMAN_SEO_brand` > product attribute `brand` > global setting (needs new option `SAMAN_SEO_product_default_brand`).

Google Merchant Listing requirements specify: **Required** fields are name, image (50K+ pixels), and offers (with price + priceCurrency). **Recommended** fields include brand, description, sku, gtin/mpn, availability, itemCondition, priceValidUntil, and seller. Availability maps WooCommerce stock status to schema.org URLs: `instock` -> `https://schema.org/InStock`, `outofstock` -> `https://schema.org/OutOfStock`, `onbackorder` -> `https://schema.org/PreOrder`.

**Primary recommendation:** Implement Product_Schema::generate() with all properties using WC_Product getter methods, build Offer inline (not separate class), and map stock status to schema.org availability URLs using a dedicated helper method.

## Standard Stack

The established approach for this phase:

### Core (Already Available)
| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| Product_Schema | 1.1.0 | Skeleton exists | Extend with properties |
| WC_Product API | 9.x | Product data access | Official WooCommerce API |
| Schema_IDs | 1.0.0 | @id generation | `product()` method exists |
| get_woocommerce_currency() | 9.x | Currency code | ISO 4217 format |

### WooCommerce Methods (Phase 8 Uses)
| Method | Return | Schema Property | Notes |
|--------|--------|-----------------|-------|
| `get_short_description()` | string | description | HTML stripped for schema |
| `get_image_id()` | int | image[0] | Featured image |
| `get_gallery_image_ids()` | array | image[1..n] | Gallery images |
| `get_sku()` | string | sku | May be empty |
| `get_price()` | string | offers.price | Active price (sale or regular) |
| `get_stock_status()` | string | offers.availability | instock/outofstock/onbackorder |
| `get_date_on_sale_to()` | WC_DateTime|null | offers.priceValidUntil | Sale end date |
| `get_attribute('brand')` | string | brand.name | Product attribute fallback |

### Meta Fields (Already Defined in WooCommerce Integration)
| Meta Key | Purpose | Schema Property |
|----------|---------|-----------------|
| `_SAMAN_SEO_brand` | Custom brand override | brand.name |
| `_SAMAN_SEO_gtin` | GTIN/UPC/EAN | gtin |
| `_SAMAN_SEO_mpn` | Manufacturer Part Number | mpn |
| `_SAMAN_SEO_condition` | Item condition | itemCondition |

## Architecture Patterns

### Recommended generate() Structure
```php
public function generate(): array {
    $product = wc_get_product( $this->context->post );

    if ( ! $product || empty( $product->get_name() ) ) {
        return [];
    }

    $schema = [
        '@type' => $this->get_type(),
        '@id'   => $this->get_id(),
        'name'  => $product->get_name(),
        'url'   => $this->context->canonical,
    ];

    // Add optional properties conditionally
    $this->add_description( $schema, $product );
    $this->add_images( $schema, $product );
    $this->add_sku( $schema, $product );
    $this->add_brand( $schema, $product );
    $this->add_identifiers( $schema, $product );
    $this->add_condition( $schema, $product );
    $this->add_offers( $schema, $product );

    return $this->apply_fields_filter( $schema );
}
```

### Pattern 1: Conditional Property Addition
**What:** Only add properties to schema when values exist
**When to use:** All optional properties
**Example:**
```php
// Source: Existing Article_Schema pattern
protected function add_description( array &$schema, \WC_Product $product ): void {
    $description = $product->get_short_description();
    if ( ! empty( $description ) ) {
        $schema['description'] = wp_strip_all_tags( $description );
    }
}
```

### Pattern 2: Image Array Building
**What:** Combine featured image + gallery into image array
**When to use:** images property
**Example:**
```php
// Source: WooCommerce documentation + Google requirements
protected function add_images( array &$schema, \WC_Product $product ): void {
    $images = [];

    // Featured image first
    $featured_id = $product->get_image_id();
    if ( $featured_id ) {
        $url = wp_get_attachment_image_url( $featured_id, 'full' );
        if ( $url ) {
            $images[] = $url;
        }
    }

    // Gallery images
    $gallery_ids = $product->get_gallery_image_ids();
    foreach ( $gallery_ids as $attachment_id ) {
        $url = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( $url ) {
            $images[] = $url;
        }
    }

    if ( ! empty( $images ) ) {
        $schema['image'] = $images;
    }
}
```

### Pattern 3: Brand Fallback Chain
**What:** Try multiple sources for brand in priority order
**When to use:** brand property
**Example:**
```php
// Source: STACK.md research + requirements
protected function get_brand( \WC_Product $product ): string {
    // 1. Custom meta field (highest priority)
    $brand = get_post_meta( $product->get_id(), '_SAMAN_SEO_brand', true );
    if ( ! empty( $brand ) ) {
        return $brand;
    }

    // 2. Product attribute 'brand'
    $brand = $product->get_attribute( 'brand' );
    if ( ! empty( $brand ) ) {
        return $brand;
    }

    // 3. Global fallback setting
    return get_option( 'SAMAN_SEO_product_default_brand', '' );
}

protected function add_brand( array &$schema, \WC_Product $product ): void {
    $brand = $this->get_brand( $product );
    if ( ! empty( $brand ) ) {
        $schema['brand'] = [
            '@type' => 'Brand',
            'name'  => $brand,
        ];
    }
}
```

### Pattern 4: Offer Building
**What:** Build Offer inline within Product generate()
**When to use:** offers property for simple products
**Example:**
```php
// Source: Google Merchant Listing docs + Yoast pattern
protected function add_offers( array &$schema, \WC_Product $product ): void {
    $price = $product->get_price();

    // Price is required for merchant listings
    if ( empty( $price ) || (float) $price <= 0 ) {
        return;
    }

    $offer = [
        '@type'         => 'Offer',
        'price'         => $price,
        'priceCurrency' => get_woocommerce_currency(),
        'availability'  => $this->get_availability_url( $product ),
        'url'           => $this->context->canonical,
    ];

    // priceValidUntil from sale end date
    $sale_end = $product->get_date_on_sale_to();
    if ( $sale_end ) {
        $offer['priceValidUntil'] = $sale_end->format( 'Y-m-d' );
    }

    // Seller reference to Organization
    $offer['seller'] = [ '@id' => Schema_IDs::organization() ];

    $schema['offers'] = $offer;
}
```

### Pattern 5: Availability URL Mapping
**What:** Map WooCommerce stock status to schema.org URLs
**When to use:** offers.availability
**Example:**
```php
// Source: Google docs + Yoast implementation
protected function get_availability_url( \WC_Product $product ): string {
    $status = $product->get_stock_status();

    $map = [
        'instock'     => 'https://schema.org/InStock',
        'outofstock'  => 'https://schema.org/OutOfStock',
        'onbackorder' => 'https://schema.org/PreOrder',
    ];

    return $map[ $status ] ?? 'https://schema.org/InStock';
}
```

### Anti-Patterns to Avoid
- **Hardcoding currency:** Always use `get_woocommerce_currency()` for priceCurrency
- **Empty string properties:** Check values before adding - empty strings are invalid schema
- **Full URLs in itemCondition:** Use URL format like `https://schema.org/NewCondition`
- **Including price=0:** Google requires price > 0 for merchant listings

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Product price | `get_post_meta('_price')` | `$product->get_price()` | Handles sale price logic, taxes |
| Currency code | Hardcoded 'USD' | `get_woocommerce_currency()` | Multi-currency stores |
| Image URLs | Manual attachment queries | `wp_get_attachment_image_url()` | Size handling, CDN compat |
| Stock check | `get_post_meta('_stock_status')` | `$product->get_stock_status()` | Handles variable products |
| Sale end date | `get_post_meta('_sale_price_dates_to')` | `$product->get_date_on_sale_to()` | Returns WC_DateTime object |
| Product type check | String comparison | `$product->is_type('simple')` | Handles inheritance |

**Key insight:** WC_Product abstracts all complexity. NEVER access `_price`, `_stock_status`, or other meta directly.

## Common Pitfalls

### Pitfall 1: Empty Price Handling
**What goes wrong:** Offer outputs with price=0 or empty, Google rejects
**Why it happens:** Free products, products without price set
**How to avoid:** Check `(float) $price > 0` before adding offers
**Warning signs:** Rich Results Test shows "price missing or invalid"

### Pitfall 2: HTML in Description
**What goes wrong:** Schema contains HTML tags
**Why it happens:** `get_short_description()` returns HTML
**How to avoid:** Always `wp_strip_all_tags()` before adding to schema
**Warning signs:** JSON-LD contains `<p>`, `<br>` tags

### Pitfall 3: Missing Featured Image
**What goes wrong:** Image array is empty, Google shows warning
**Why it happens:** Product has no featured image set
**How to avoid:** Check `$product->get_image_id()` returns truthy value
**Warning signs:** Rich Results Test warns about missing image

### Pitfall 4: Seller Without Organization
**What goes wrong:** seller @id reference has no matching Organization in graph
**Why it happens:** Organization schema disabled or Person type selected
**How to avoid:** Only add seller when Organization is in graph
**Warning signs:** JSON-LD validator shows "referenced entity not found"

### Pitfall 5: itemCondition Without URL Prefix
**What goes wrong:** Google doesn't recognize condition value
**Why it happens:** Using `NewCondition` instead of `https://schema.org/NewCondition`
**How to avoid:** Always use full URL format for enumeration values
**Warning signs:** Rich Results Test ignores condition

### Pitfall 6: priceValidUntil Format
**What goes wrong:** Invalid date format
**Why it happens:** Using datetime instead of date, wrong format
**How to avoid:** Use `$sale_end->format('Y-m-d')` for ISO 8601 date only
**Warning signs:** Rich Results Test shows date format error

## Code Examples

Verified patterns from official sources:

### Complete Product Schema Generation
```php
// Source: Existing Product_Schema skeleton + Google docs
public function generate(): array {
    $product = wc_get_product( $this->context->post );

    if ( ! $product || empty( $product->get_name() ) ) {
        return [];
    }

    $schema = [
        '@type' => $this->get_type(),
        '@id'   => $this->get_id(),
        'name'  => $product->get_name(),
        'url'   => $this->context->canonical,
    ];

    // Description from short description
    $description = $product->get_short_description();
    if ( ! empty( $description ) ) {
        $schema['description'] = wp_strip_all_tags( $description );
    }

    // Images (featured + gallery)
    $images = $this->get_images( $product );
    if ( ! empty( $images ) ) {
        $schema['image'] = $images;
    }

    // SKU
    $sku = $product->get_sku();
    if ( ! empty( $sku ) ) {
        $schema['sku'] = $sku;
    }

    // Brand (with fallback chain)
    $brand = $this->get_brand( $product );
    if ( ! empty( $brand ) ) {
        $schema['brand'] = [
            '@type' => 'Brand',
            'name'  => $brand,
        ];
    }

    // GTIN/MPN from custom fields
    $this->add_identifiers( $schema, $product );

    // Item condition
    $this->add_condition( $schema, $product );

    // Offers (only for simple products in this phase)
    if ( $product->is_type( 'simple' ) ) {
        $offer = $this->build_offer( $product );
        if ( ! empty( $offer ) ) {
            $schema['offers'] = $offer;
        }
    }

    return $this->apply_fields_filter( $schema );
}
```

### Identifiers (GTIN/MPN) Addition
```php
// Source: Google Merchant Listing docs
protected function add_identifiers( array &$schema, \WC_Product $product ): void {
    $gtin = get_post_meta( $product->get_id(), '_SAMAN_SEO_gtin', true );
    if ( ! empty( $gtin ) ) {
        // Use most specific GTIN type based on length
        $schema['gtin'] = $gtin;
    }

    $mpn = get_post_meta( $product->get_id(), '_SAMAN_SEO_mpn', true );
    if ( ! empty( $mpn ) ) {
        $schema['mpn'] = $mpn;
    }
}
```

### Item Condition Addition
```php
// Source: Google docs - use full URL format
protected function add_condition( array &$schema, \WC_Product $product ): void {
    $condition = get_post_meta( $product->get_id(), '_SAMAN_SEO_condition', true );

    // Default to NewCondition
    if ( empty( $condition ) ) {
        $condition = 'NewCondition';
    }

    // Valid conditions per schema.org
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
```

### Offer Building
```php
// Source: Google Merchant Listing requirements + Yoast pattern
protected function build_offer( \WC_Product $product ): array {
    $price = $product->get_price();

    // Price is required for merchant listings
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

    // priceValidUntil from sale end date
    $sale_end = $product->get_date_on_sale_to();
    if ( $sale_end instanceof \WC_DateTime ) {
        $offer['priceValidUntil'] = $sale_end->format( 'Y-m-d' );
    }

    // Seller reference to Organization (only if Organization schema exists)
    if ( get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ) === 'organization' ) {
        $offer['seller'] = [ '@id' => Schema_IDs::organization() ];
    }

    return $offer;
}
```

### Availability URL Mapping
```php
// Source: Google docs + schema.org ItemAvailability
protected function get_availability_url( \WC_Product $product ): string {
    $status = $product->get_stock_status();

    $map = [
        'instock'     => 'https://schema.org/InStock',
        'outofstock'  => 'https://schema.org/OutOfStock',
        'onbackorder' => 'https://schema.org/PreOrder',
    ];

    return $map[ $status ] ?? 'https://schema.org/InStock';
}
```

### Image Array Building
```php
// Source: WooCommerce docs + Google image requirements
protected function get_images( \WC_Product $product ): array {
    $images = [];

    // Featured image first
    $featured_id = $product->get_image_id();
    if ( $featured_id ) {
        $url = wp_get_attachment_image_url( $featured_id, 'full' );
        if ( $url ) {
            $images[] = $url;
        }
    }

    // Gallery images
    $gallery_ids = $product->get_gallery_image_ids();
    foreach ( $gallery_ids as $attachment_id ) {
        $url = wp_get_attachment_image_url( $attachment_id, 'full' );
        if ( $url ) {
            $images[] = $url;
        }
    }

    return $images;
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `http://schema.org/InStock` | `https://schema.org/InStock` | 2023 | Google prefers HTTPS |
| Simple price property | priceSpecification object | 2024 | Allows tax-inclusive pricing |
| String itemCondition | Full URL itemCondition | Google best practice | Better recognition |
| Product.offers array | Single Offer for simple products | Always | Array only for variables |

**Deprecated/outdated:**
- Direct meta access for prices: Use WC_Product methods
- `get_gallery_attachment_ids()`: Use `get_gallery_image_ids()` (WC 3.0+)

## Open Questions

Things that couldn't be fully resolved:

1. **Global Default Brand Option Name**
   - What we know: Requirement PROD-06 says "or global setting" for brand
   - What's unclear: Option name not yet defined in codebase
   - Recommendation: Create `SAMAN_SEO_product_default_brand` option, document for Admin UI phase

2. **Seller Reference When Person Type**
   - What we know: Organization @id works, but some sites use Person
   - What's unclear: Should seller reference Person when Knowledge Graph type is Person?
   - Recommendation: Only output seller when Organization type is active (most common case)

3. **Tax-Inclusive Pricing (priceSpecification)**
   - What we know: Google now supports priceSpecification with valueAddedTaxIncluded
   - What's unclear: Whether WC's `wc_prices_include_tax()` should influence this
   - Recommendation: Use simple price for v1.1, consider priceSpecification for future enhancement

## Sources

### Primary (HIGH confidence)
- Google Merchant Listing: https://developers.google.com/search/docs/appearance/structured-data/merchant-listing
- WC_Product Class Reference: https://woocommerce.github.io/code-reference/classes/WC-Product.html
- Existing codebase: `includes/Schema/Types/class-product-schema.php`
- Existing codebase: `includes/Integration/class-woocommerce.php`
- Schema.org Offer: https://schema.org/Offer
- Schema.org ItemAvailability: https://schema.org/ItemAvailability

### Secondary (MEDIUM confidence)
- Yoast Offer Schema: https://developer.yoast.com/features/schema/pieces/offer/
- STACK.md research: `.planning/research/STACK.md`

### Tertiary (LOW confidence)
- None - all claims verified against official sources

## Metadata

**Confidence breakdown:**
- WC_Product API: HIGH - Official WooCommerce documentation
- Google requirements: HIGH - Official Google documentation
- Architecture patterns: HIGH - Follows existing codebase patterns
- Stock status mapping: HIGH - Verified via Yoast and Google docs
- Pitfalls: HIGH - Common issues documented in community

**Research date:** 2026-01-23
**Valid until:** 2026-03-23 (60 days - stable APIs)
