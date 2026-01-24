# Feature Landscape: WooCommerce Product Schema

**Domain:** E-commerce Product Structured Data for Google Rich Results
**Researched:** 2026-01-23
**Confidence:** HIGH (verified with Google official documentation)

## Executive Summary

Google provides two distinct Product schema types: **Product Snippets** (for affiliate/review sites) and **Merchant Listings** (for stores selling products). WooCommerce stores need **Merchant Listing** structured data to be eligible for shopping-related rich results including price, availability, shipping, and reviews directly in search results.

The key differentiator opportunity lies in handling **variable products with ProductGroup**, which most WordPress plugins either ignore or implement poorly. Google explicitly recommends ProductGroup for variants but WooCommerce core does not implement it natively.

---

## Table Stakes

Features users expect. Missing these = product fails Google Rich Results validation.

### Required Properties (Merchant Listings)

| Feature | Why Expected | Complexity | Implementation Notes |
|---------|--------------|------------|---------------------|
| **Product name** | Google requirement | Low | Map from `WC_Product::get_name()` |
| **Product image** | Google requirement | Low | Map from `WC_Product::get_image_id()` - need multiple aspect ratios (16:9, 4:3, 1:1) |
| **Offer with price** | Google requirement | Low | `WC_Product::get_price()` with currency from WooCommerce settings |
| **Price currency** | Google requirement | Low | ISO 4217 from `get_woocommerce_currency()` |
| **Availability** | Expected for shopping results | Low | Map WC stock status to schema.org ItemAvailability |
| **Product URL** | Required for Merchant Listings | Low | Permalink to product page |

### Expected WooCommerce Integration

| Feature | Why Expected | Complexity | Implementation Notes |
|---------|--------------|------------|---------------------|
| **Auto-populate from WC data** | Users expect schema to read WC fields | Medium | Hook into WC product object, no manual entry for basic fields |
| **SKU mapping** | Common product identifier | Low | `WC_Product::get_sku()` |
| **Simple product support** | Most common product type | Low | Single Offer per Product |
| **On Sale price handling** | WC has sale price | Low | Use `get_sale_price()` when active, with `priceValidUntil` from sale date |

### Expected Rich Result Features

| Feature | Why Expected | Complexity | Implementation Notes |
|---------|--------------|------------|---------------------|
| **AggregateRating** | Star ratings drive CTR | Medium | Map from WC reviews: `get_average_rating()`, `get_review_count()` |
| **Individual reviews** | Google shows review snippets | Medium | Query WC product reviews, map author/rating/content |
| **Brand** | Common product attribute | Low | Option to set globally or per-product, use Organization name as fallback |
| **Description** | Recommended property | Low | `WC_Product::get_short_description()` or `get_description()` |

**Complexity Summary for Table Stakes:**
- Low complexity: 9 features (basic property mapping)
- Medium complexity: 3 features (review aggregation, WC integration)

---

## Differentiators

Features that set the plugin apart. Not universally expected, but create competitive advantage.

### Variable Product Handling (HIGH VALUE)

| Feature | Value Proposition | Complexity | Implementation Notes |
|---------|-------------------|------------|---------------------|
| **ProductGroup for variants** | Google-recommended pattern most plugins skip | HIGH | Wrap variants in `isVariantOf` > `ProductGroup` with `variesBy` and `hasVariant` |
| **Variant-specific offers** | Each size/color gets own price | HIGH | Generate multiple Offer objects with variant-specific data |
| **inProductGroupWithID** | Links variants to parent | Medium | Set same `productGroupID` across all variants |
| **Variant properties** | Color, size, material per variant | Medium | Map WC variation attributes to schema properties |

**Why this differentiates:** WooCommerce core outputs multiple separate Product schemas for variants (one per variation), which Google says is suboptimal. ProductGroup is the correct pattern but requires significant implementation effort. Most free plugins do not support this.

### Merchant-Specific Features

| Feature | Value Proposition | Complexity | Implementation Notes |
|---------|-------------------|------------|---------------------|
| **MerchantReturnPolicy** | Shows return info in search results | Medium | Global setting with `returnPolicyCategory`, `merchantReturnDays`, `returnFees` |
| **OfferShippingDetails** | Shows shipping cost/speed in results | HIGH | Complex: need `shippingRate`, `shippingDestination`, `deliveryTime` |
| **Item condition** | NewCondition, RefurbishedCondition, UsedCondition | Low | Default to NewCondition, allow override |
| **GTIN/MPN identifiers** | Better product matching | Low | Add meta fields for GTIN-8/12/13/14, MPN |

### Review Enhancements

| Feature | Value Proposition | Complexity | Implementation Notes |
|---------|-------------------|------------|---------------------|
| **Pros/cons (positiveNotes/negativeNotes)** | Editorial review features | Medium | For editorial/affiliate use case, not typical WC |
| **Review author with URL** | Richer review data | Low | Link to author profile if available |

### Advanced Structured Data

| Feature | Value Proposition | Complexity | Implementation Notes |
|---------|-------------------|------------|---------------------|
| **hasCertification** | Energy efficiency, organic labels | Medium | Up to 10 certifications per product |
| **Sustainability claims** | Emerging 2026 trend | Low | Schema for recyclable, organic, carbon-neutral |
| **3D model link (subjectOf)** | Cutting-edge feature | Low | Link to glTF 3D model if available |
| **PeopleAudience** | Target gender/age group | Low | Map to WC product attributes if set |

**Complexity Summary for Differentiators:**
- Low complexity: 6 features
- Medium complexity: 5 features
- High complexity: 2 features (ProductGroup implementation, shipping details)

---

## Anti-Features

Features to explicitly NOT build. Common mistakes in this domain.

| Anti-Feature | Why Avoid | What to Do Instead |
|--------------|-----------|-------------------|
| **Schema on category/shop pages** | Google requires Product schema on single product pages only. "Shoes in our shop" is not a specific product. | Only output Product schema on single product pages (`is_product()`) |
| **Duplicate schema output** | Multiple plugins generating schema causes Google to ignore all markup | Detect existing WC/Yoast/RankMath schema, disable theirs or ours with admin notice |
| **Fake/inflated reviews** | Google explicitly prohibits; can cause manual action penalty | Only map real WC reviews with actual customer authors |
| **Price = 0 for Merchant Listings** | Google requires price > 0 for merchant listing eligibility | Skip Offer for free products or use Product Snippet instead |
| **Mismatched price/availability** | #1 cause of Merchant Center disapprovals; Google validates schema against visible page content | Always pull live data from WC product object, never cache |
| **Reviews not visible on page** | Google requires review content to be visible to users | Only include reviews that display on the product page |
| **Self-authored reviews** | "50% off on Black Friday" is not a valid reviewer name | Validate review author is a real customer name |
| **Global AggregateRating** | Cannot use store-wide ratings on individual products | Each product must have its own rating from its own reviews |
| **Schema via JavaScript** | Google recommends server-rendered; dynamic markup causes "less frequent and less reliable" crawls | Output JSON-LD in initial HTML, not via JS |
| **Excessive variation markup (50+)** | WooCommerce warns this causes performance issues | Limit variant schema output to 50 variations max |

---

## Feature Dependencies

```
Core Product Schema
    |
    +-- Simple Product Support
    |       |
    |       +-- Price/Availability → AggregateRating → Reviews
    |
    +-- Variable Product Support (requires Simple first)
            |
            +-- ProductGroup Implementation
            |       |
            |       +-- Variant-specific Offers
            |       +-- variesBy/hasVariant properties
            |
            +-- Attribute Mapping (color, size, material)

Merchant Features (independent, can add anytime)
    |
    +-- MerchantReturnPolicy (global setting)
    +-- OfferShippingDetails (per-product or global)
    +-- GTIN/MPN fields (meta field additions)
```

---

## MVP Recommendation

For MVP (initial WooCommerce milestone), prioritize in order:

### Phase 1: Core Product Schema
1. **Simple product schema** with all required properties
2. **Auto-population from WC data** (name, price, image, SKU, availability)
3. **AggregateRating from WC reviews** (if reviews exist)
4. **Brand support** (global setting with per-product override)

### Phase 2: Variable Products
5. **Variable product support** with multiple Offers (basic approach first)
6. **ProductGroup implementation** (proper Google-recommended pattern)
7. **Variant attribute mapping** (color, size, material)

### Phase 3: Merchant Features
8. **MerchantReturnPolicy** (global setting)
9. **GTIN/MPN meta fields**
10. **OfferShippingDetails** (complex, defer if needed)

### Defer to Post-MVP
- 3D model support (niche use case)
- Certifications (hasCertification)
- Sustainability claims (emerging, not widely validated)
- Pros/cons for editorial reviews (not core WC use case)

---

## Conflict Detection Strategy

WooCommerce and SEO plugins often output their own Product schema. Strategy needed:

| Scenario | Detection | Action |
|----------|-----------|--------|
| WooCommerce core schema | Check `woocommerce_structured_data_product` filter | Unhook WC's default or merge intelligently |
| Yoast WooCommerce SEO | Check if class exists | Admin notice, option to disable theirs |
| RankMath | Check if class exists | Admin notice, option to disable theirs |
| All In One SEO | Check if class exists | Admin notice, option to disable theirs |

**Recommended approach:** Provide a "Disable other plugin's Product schema" toggle rather than automatic override. Let user control to avoid conflicts.

---

## Google Validation Requirements

For a valid Product rich result:

1. **Required:** `name` property
2. **Required:** At least ONE of: `offers`, `review`, or `aggregateRating`
3. **For Merchant Listings:** `offers.price` must be > 0
4. **For Merchant Listings:** `offers.priceCurrency` in ISO 4217 format
5. **Data consistency:** Schema must match visible page content exactly
6. **Single product focus:** Page must be about ONE product (variants OK)

Test with:
- Google Rich Results Test: https://search.google.com/test/rich-results
- Schema.org Validator: https://validator.schema.org/

---

## Sources

### Official Documentation (HIGH confidence)
- [Google Product Structured Data Overview](https://developers.google.com/search/docs/appearance/structured-data/product)
- [Google Merchant Listing Structured Data](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing)
- [Google Product Snippet Structured Data](https://developers.google.com/search/docs/appearance/structured-data/product-snippet)
- [schema.org Product Type](https://schema.org/Product)
- [schema.org AggregateRating Type](https://schema.org/AggregateRating)
- [schema.org MerchantReturnPolicy Type](https://schema.org/MerchantReturnPolicy)

### WooCommerce Resources (MEDIUM confidence)
- [WooCommerce Rich Snippets Documentation](https://woocommerce.com/document/rich-snippets/)
- [WooCommerce ProductGroup Feature Request](https://woocommerce.com/feature-request/use-productgroup-structured-data-for-variants-instead-of-separate-products/)
- [WooCommerce GitHub Issue #17471 - Variable Product Schema](https://github.com/woocommerce/woocommerce/issues/17471)

### Industry Best Practices (MEDIUM confidence)
- [Rank Math WooCommerce Product Schema Guide](https://rankmath.com/kb/woocommerce-product-schema/)
- [Schema.press WooCommerce Variable Products](https://schema.press/docs-extensions/woocommerce-variable-products/)
- [SNIP Plugin - Variable Products Structured Data](https://rich-snippets.io/structured-data-for-variable-products/)
