# Technology Stack: WooCommerce Product Schema Integration

**Project:** Saman SEO - WooCommerce Product Schemas (v1.1)
**Researched:** 2026-01-23
**Confidence:** HIGH (verified via official WooCommerce code reference and Google documentation)

## Executive Summary

WooCommerce provides a mature, well-documented PHP API for accessing product data. The existing `Integration\WooCommerce` class already demonstrates the correct patterns. This research documents the complete API surface needed for Product schema generation and identifies integration points with the existing schema engine.

---

## WooCommerce Detection

### Recommended Method

```php
if ( class_exists( 'WooCommerce' ) ) {
    // WooCommerce is active
}
```

**Why this method:**
- Native PHP function, no additional includes required
- Works on both frontend and admin contexts
- Already used in existing `Integration\WooCommerce::is_active()` method
- More reliable than `is_plugin_active()` which requires including `plugin.php`

**Best practice:** Wrap checks in `plugins_loaded` hook to ensure WooCommerce has initialized:

```php
add_action( 'plugins_loaded', function() {
    if ( class_exists( 'WooCommerce' ) ) {
        // Safe to use WooCommerce APIs
    }
});
```

### Alternative Detection Methods

| Method | Use Case | Notes |
|--------|----------|-------|
| `class_exists( 'WooCommerce' )` | General detection | Recommended |
| `function_exists( 'WC' )` | Check WC() singleton available | Alternative |
| `is_plugin_active( 'woocommerce/woocommerce.php' )` | Admin-only check | Requires `plugin.php` include |
| `defined( 'WC_VERSION' )` | Version check | Good for minimum version requirements |

---

## Core WooCommerce Functions

### Product Retrieval

| Function | Purpose | Return Type |
|----------|---------|-------------|
| `wc_get_product( $product_id )` | Get product object from ID | `WC_Product\|false` |
| `wc_get_products( $args )` | Query multiple products | `array` |
| `get_woocommerce_currency()` | Get store currency code | `string` (ISO 4217) |

**Example:**

```php
$product = wc_get_product( $post->ID );
if ( $product ) {
    $currency = get_woocommerce_currency(); // 'USD', 'EUR', etc.
}
```

**Why `wc_get_product()` over `new WC_Product()`:**
- Uses `WC_Product_Factory` internally
- Returns correct subclass automatically (Simple, Variable, etc.)
- Handles caching and object cache integration
- Recommended by WooCommerce documentation

---

## WC_Product API Reference

### Product Information Methods

| Method | Return Type | Schema.org Property | Notes |
|--------|-------------|---------------------|-------|
| `get_id()` | `int` | `@id` generation | Product post ID |
| `get_name( $context )` | `string` | `name` | Product title |
| `get_description( $context )` | `string` | `description` | Full description (HTML) |
| `get_short_description( $context )` | `string` | `description` (fallback) | Excerpt |
| `get_sku( $context )` | `string` | `sku` | Stock Keeping Unit |
| `get_permalink()` | `string` | `url`, `@id` | Product page URL |

**Context parameter:** Pass `'view'` (default) for frontend display, `'edit'` for raw database values.

### Pricing Methods

| Method | Return Type | Schema.org Property | Notes |
|--------|-------------|---------------------|-------|
| `get_price( $context )` | `string` | `offers.price` | Current active price (sale or regular) |
| `get_regular_price( $context )` | `string` | - | Full price before discounts |
| `get_sale_price( $context )` | `string` | - | Discounted price if on sale |
| `get_date_on_sale_from( $context )` | `WC_DateTime\|null` | - | Sale start date |
| `get_date_on_sale_to( $context )` | `WC_DateTime\|null` | `offers.priceValidUntil` | Sale end date |
| `is_on_sale()` | `bool` | - | Check if currently on sale |

**For schema:** Use `get_price()` as it returns the current effective price.

### Stock/Availability Methods

| Method | Return Type | Schema.org Property | Notes |
|--------|-------------|---------------------|-------|
| `get_stock_status( $context )` | `string` | `offers.availability` | 'instock', 'outofstock', 'onbackorder' |
| `get_stock_quantity( $context )` | `int\|null` | - | Quantity available |
| `is_in_stock()` | `bool` | - | Quick availability check |
| `get_manage_stock( $context )` | `bool` | - | Whether stock is managed |
| `get_backorders( $context )` | `string` | - | 'no', 'notify', 'yes' |

**Stock status to Schema.org mapping:**

```php
$availability_map = [
    'instock'     => 'https://schema.org/InStock',
    'outofstock'  => 'https://schema.org/OutOfStock',
    'onbackorder' => 'https://schema.org/BackOrder',
];
```

### Image Methods

| Method | Return Type | Schema.org Property | Notes |
|--------|-------------|---------------------|-------|
| `get_image_id( $context )` | `string\|int` | `image` (via `wp_get_attachment_url`) | Featured image attachment ID |
| `get_gallery_image_ids( $context )` | `array` | `image` (array) | Gallery attachment IDs |

**Getting image URLs:**

```php
// Featured image
$image_id  = $product->get_image_id();
$image_url = wp_get_attachment_url( $image_id );

// Gallery images (for multiple images in schema)
$gallery_ids = $product->get_gallery_image_ids();
$gallery_urls = array_map( 'wp_get_attachment_url', $gallery_ids );
```

### Review/Rating Methods

| Method | Return Type | Schema.org Property | Notes |
|--------|-------------|---------------------|-------|
| `get_average_rating( $context )` | `float` | `aggregateRating.ratingValue` | Average star rating (0-5) |
| `get_review_count( $context )` | `int` | `aggregateRating.reviewCount` | Number of approved reviews |
| `get_rating_count( $context )` | `array` | - | Counts per rating level |

**Note:** Reviews are stored as WordPress comments with `comment_type = 'review'`. Rating stored in comment meta key `'rating'`.

### Product Type Detection

| Method | Return Type | Purpose |
|--------|-------------|---------|
| `get_type()` | `string` | Returns 'simple', 'variable', 'grouped', 'external' |
| `is_type( $type )` | `bool` | Check if product is specific type |
| `is_virtual()` | `bool` | No shipping needed |
| `is_downloadable()` | `bool` | Digital product |

---

## WC_Product_Variable API (Variations)

### Class Hierarchy

```
WC_Product (abstract)
    |-- WC_Product_Simple
    |-- WC_Product_Variable
    |       |-- get_available_variations()
    |       |-- get_children()
    |       |-- get_visible_children()
    |-- WC_Product_Grouped
    |-- WC_Product_External
```

### Variation Methods

| Method | Signature | Return Type | Notes |
|--------|-----------|-------------|-------|
| `get_available_variations()` | `( $return = 'array' )` | `array` | Full variation data arrays |
| `get_children()` | `( $visible_only = '' )` | `array` | Variation post IDs |
| `get_visible_children()` | `()` | `array` | Only visible variation IDs |

**Performance consideration:** `get_available_variations('array')` is expensive for products with many variations. It processes HTML, prices, and images for each variation. For schema generation, prefer:

```php
// Efficient: Get variation IDs, then load only what's needed
$variation_ids = $product->get_children();
foreach ( $variation_ids as $variation_id ) {
    $variation = wc_get_product( $variation_id );
    // Access only needed properties
}
```

### WC_Product_Variation Methods

| Method | Return Type | Notes |
|--------|-------------|-------|
| `get_variation_attributes( $context )` | `array` | Name-value pairs of attributes |
| `get_attribute( $attribute )` | `string` | Single attribute value |
| `get_manage_stock( $context )` | `bool\|string` | Returns 'parent' if inheriting |
| `get_stock_managed_by_id()` | `int` | Parent ID if stock inherited |
| `variation_is_active()` | `bool` | Visibility status |

---

## Google Product Schema Requirements

### Required Properties (Merchant Listing)

| Property | Source | WooCommerce Method |
|----------|--------|-------------------|
| `name` | Product title | `$product->get_name()` |
| `image` | Product image URL | `wp_get_attachment_url( $product->get_image_id() )` |
| `offers.price` | Current price | `$product->get_price()` |
| `offers.priceCurrency` | Store currency | `get_woocommerce_currency()` |

### Recommended Properties

| Property | Source | WooCommerce Method |
|----------|--------|-------------------|
| `description` | Product description | `$product->get_short_description() ?: $product->get_description()` |
| `sku` | SKU | `$product->get_sku()` |
| `brand` | Brand attribute/meta | Custom field or `pa_brand` taxonomy |
| `gtin\|gtin8\|gtin12\|gtin13` | Barcode | Custom post meta `_SAMAN_SEO_gtin` |
| `offers.availability` | Stock status | Map `get_stock_status()` to schema.org URL |
| `offers.itemCondition` | Condition | Custom post meta `_SAMAN_SEO_condition` |
| `offers.priceValidUntil` | Sale end date | `$product->get_date_on_sale_to()` |
| `aggregateRating` | Review data | `get_average_rating()`, `get_review_count()` |
| `review` | Individual reviews | `get_comments()` with type 'review' |

### Schema.org Availability Values

```php
'https://schema.org/InStock'
'https://schema.org/OutOfStock'
'https://schema.org/BackOrder'
'https://schema.org/PreOrder'
'https://schema.org/SoldOut'
'https://schema.org/Discontinued'
```

### Schema.org Condition Values

```php
'https://schema.org/NewCondition'
'https://schema.org/UsedCondition'
'https://schema.org/RefurbishedCondition'
'https://schema.org/DamagedCondition'
```

---

## Integration with Existing Schema Engine

### Current State Analysis

The existing `Integration\WooCommerce` class uses the **legacy hook approach**:

```php
add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_product_schema' ], 25, 1 );
```

**Recommended approach for v1.1:** Migrate to the **registry-based architecture** used by other schema types:

1. Create `Schema/Types/class-product-schema.php` extending `Abstract_Schema`
2. Create `Schema/Types/class-offer-schema.php` for Offer sub-schema
3. Register via `Schema_Registry::register()`
4. Let `Schema_Graph_Manager` handle inclusion

### Schema Context Extension

The `Schema_Context` class needs awareness of WooCommerce products. Options:

**Option A: Extend Schema_Context (recommended)**
```php
// Add to Schema_Context
public $product; // WC_Product|null
public $is_product_page; // bool

public static function from_current(): Schema_Context {
    // ... existing code ...

    if ( class_exists( 'WooCommerce' ) && is_singular( 'product' ) ) {
        $context->is_product_page = true;
        $context->product = wc_get_product( $context->post->ID );
    }
}
```

**Option B: Lazy loading in Product_Schema**
```php
class Product_Schema extends Abstract_Schema {
    protected function get_product(): ?WC_Product {
        if ( ! $this->context->post ) {
            return null;
        }
        return wc_get_product( $this->context->post->ID );
    }
}
```

### Disabling WooCommerce's Built-in Schema

WooCommerce 3.0+ outputs its own JSON-LD. To avoid duplicate/conflicting schema:

```php
// In Integration\WooCommerce::boot()
add_action( 'init', function() {
    remove_action( 'wp_footer', [ WC()->structured_data, 'output_structured_data' ], 10 );
});
```

**Alternative:** Use filter to return empty:
```php
add_filter( 'woocommerce_structured_data_product', '__return_false' );
```

### Variable Product Schema Strategy

For variable products, Google recommends **one Product with multiple Offers**:

```json
{
    "@type": "Product",
    "name": "T-Shirt",
    "offers": [
        {
            "@type": "Offer",
            "name": "Small - Red",
            "price": "19.99",
            "availability": "https://schema.org/InStock"
        },
        {
            "@type": "Offer",
            "name": "Large - Blue",
            "price": "19.99",
            "availability": "https://schema.org/OutOfStock"
        }
    ]
}
```

**Implementation:**

```php
if ( $product->is_type( 'variable' ) ) {
    $offers = [];
    foreach ( $product->get_children() as $variation_id ) {
        $variation = wc_get_product( $variation_id );
        if ( $variation && $variation->variation_is_active() ) {
            $offers[] = $this->build_variation_offer( $variation );
        }
    }
    $schema['offers'] = $offers;
} else {
    $schema['offers'] = $this->build_simple_offer( $product );
}
```

---

## Custom Meta Fields for Product Schema

### Already Implemented (in existing WooCommerce class)

| Meta Key | Purpose | UI Needed |
|----------|---------|-----------|
| `_SAMAN_SEO_brand` | Brand name | Text input |
| `_SAMAN_SEO_gtin` | GTIN/UPC/EAN barcode | Text input |
| `_SAMAN_SEO_mpn` | Manufacturer Part Number | Text input |
| `_SAMAN_SEO_condition` | Item condition | Select dropdown |

### Brand Discovery Fallbacks (existing implementation)

```php
// Priority order:
1. Custom field: _SAMAN_SEO_brand
2. Product attribute: 'brand'
3. Taxonomy: pa_brand terms
```

---

## File Structure Recommendation

```
includes/Schema/Types/
    class-product-schema.php      # Main Product schema (new)
    class-offer-schema.php        # Offer sub-schema (new, optional)

includes/Integration/
    class-woocommerce.php         # Existing - update to use registry

includes/Schema/
    class-schema-context.php      # Add WC product awareness
```

---

## Sources

### Official WooCommerce Documentation (HIGH confidence)
- [WC_Product Class Reference](https://woocommerce.github.io/code-reference/classes/WC-Product.html)
- [WC_Product_Variable Class Reference](https://woocommerce.github.io/code-reference/classes/WC-Product-Variable.html)
- [WC_Product_Variation Class Reference](https://woocommerce.github.io/code-reference/classes/WC-Product-Variation.html)
- [WooCommerce Useful Core Functions](https://developer.woocommerce.com/docs/code-snippets/useful-functions/)
- [WooCommerce Classes Reference](https://developer.woocommerce.com/docs/extensions/core-concepts/class-reference/)

### Official Google Documentation (HIGH confidence)
- [Product Structured Data Overview](https://developers.google.com/search/docs/appearance/structured-data/product)
- [Merchant Listing Structured Data](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing)
- [Product Variant Structured Data](https://developers.google.com/search/docs/appearance/structured-data/product-variants)

### Community Resources (MEDIUM confidence)
- [WooCommerce Structured Data Wiki](https://github.com/woocommerce/woocommerce/wiki/Structured-data-for-products)
- [Remove WooCommerce JSON-LD](https://remicorson.com/remove-the-woocommerce-3-json-ld-structured-data-format/)
- [WC_Product Class Overview - Users Insights](https://usersinsights.com/wc-product-class/)
- [WooCommerce Product Variations Guide](https://rudrastyh.com/woocommerce/get-product-variations-programmatically.html)
