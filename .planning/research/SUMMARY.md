# Project Research Summary

**Project:** Saman SEO - WooCommerce Product Schema (v1.1)
**Domain:** E-commerce Product Structured Data for Google Rich Results
**Researched:** 2026-01-23
**Confidence:** HIGH

## Executive Summary

WooCommerce Product schema integration for Google rich results is a well-documented domain with clear technical requirements. Google provides two distinct schema types: Product Snippets (for affiliate/review sites) and Merchant Listings (for stores selling products). WooCommerce stores need Merchant Listing structured data to show price, availability, shipping, and reviews directly in search results.

The existing Saman SEO schema engine is well-architected for this extension. The current `Integration\WooCommerce` class uses a legacy filter-based approach that bypasses the registry architecture. The recommendation is to migrate to the registry-based pattern while addressing the #1 pitfall: duplicate schema conflicts with WooCommerce's native JSON-LD output. Variable products present the most complex challenge, requiring either AggregateOffer (price ranges) or ProductGroup (Google's recommended but rarely implemented pattern).

The critical risk is content mismatch between schema and visible page data—particularly for prices and availability. Google can issue manual actions for this violation. Mitigation requires pulling live product data at render time, disabling WooCommerce's built-in schema to prevent duplicates, and strict validation that schema only outputs on singular product pages. The domain has established patterns, official documentation, and the WooCommerce API provides all necessary data access.

## Key Findings

### Recommended Stack

WooCommerce provides a mature, well-documented PHP API for product data access. The existing codebase already demonstrates correct integration patterns.

**Core technologies:**
- **WooCommerce Product API**: Access via `wc_get_product()` — returns correct product subclass (Simple, Variable, etc.) with automatic type detection and caching
- **Schema.org Product/Offer types**: Nested Offer inside Product — Google requires this structure, not separate graph entries
- **Registry-based architecture**: Migrate from filter hooks to `Schema_Registry::register()` — provides priority ordering, conditional loading, and standard extensibility filters
- **WC_Structured_Data removal**: Disable WooCommerce native schema via action hooks — critical to prevent duplicate schema violations

**Critical version requirements:**
- WooCommerce 3.0+ (for modern Product API and `wc_get_product()` factory method)
- PHP class_exists() checks required throughout for optional WooCommerce dependency

### Expected Features

Google's Merchant Listing requirements are strict and non-negotiable. Missing any required property blocks rich result eligibility entirely.

**Must have (table stakes):**
- Product name, image, and offers with price/currency — Google requirements, non-negotiable
- Auto-population from WooCommerce data — users expect zero manual entry for basic fields
- Availability mapping (InStock/OutOfStock/BackOrder) — expected for shopping results
- AggregateRating from WooCommerce reviews — drives click-through rate from search
- SKU and brand mapping — common product identifiers users expect

**Should have (competitive):**
- Variable product support with AggregateOffer — shows price ranges for products with variations
- ProductGroup for variants (Google-recommended pattern) — most plugins skip this, creates differentiation
- GTIN/MPN identifier fields — reduces "no global identifier" warnings in Search Console
- Item condition (NewCondition default) — required for used/refurbished products
- priceValidUntil for sale items — prevents warnings for time-limited offers

**Defer (v2+):**
- MerchantReturnPolicy and OfferShippingDetails — causes warnings but not errors, complex WooCommerce integration
- Certification and sustainability claims — emerging 2026 trend, not widely validated yet
- 3D model support (subjectOf) — cutting-edge but niche use case

### Architecture Approach

The existing Saman SEO schema engine uses a registry-based pattern that's ideal for adding Product schema. The current WooCommerce integration uses legacy filters and should be migrated to match the established architecture.

**Major components:**
1. **Product_Schema class** — extends Abstract_Schema, handles Product type with nested Offer/AggregateRating/Review properties (not separate graph entries per schema.org best practices)
2. **Integration\WooCommerce class** — refactored to remove schema generation, keeps only WooCommerce detection and meta field registration for GTIN/brand/MPN fields
3. **Schema_Context extension** — lazy-load product data in schema class rather than coupling core context to optional WooCommerce plugin
4. **Schema_IDs helper** — add `product()` method for consistent @id generation following existing `#product` fragment pattern

**Key architectural decisions:**
- Register Product schema conditionally with `class_exists('WooCommerce')` check
- Nest Offer/AggregateRating/Review inside Product (Google's required structure)
- Use AggregateOffer for variable products showing lowPrice/highPrice ranges
- Seller in Offer references Organization via @id for graph deduplication
- Priority 15 (same as Article) ensures Product outputs after WebPage but before Breadcrumb

### Critical Pitfalls

**1. Duplicate schema with WooCommerce core** — WooCommerce outputs its own Product schema via `WC_Structured_Data`. Google explicitly warns against duplicate schemas. Must remove WooCommerce's native output via `remove_action()` on `wp_footer` hook before adding custom schema. This is Phase 1 requirement before any Product schema outputs.

**2. Schema on archive/category pages** — Product schema must only output on `is_singular('product')` pages. Google rejects Product markup on category pages. Strict conditional check required in `is_needed()` method to prevent hundreds of validation errors per archive page.

**3. Content mismatch between schema and visible page** — Price/availability in schema must exactly match what users see. Multi-currency plugins, caching, and sale price changes cause mismatches. Google can issue manual actions for this violation. Always pull live data via `wc_get_product()` at render time, never cache product schema.

**4. Missing required property (offers OR review OR aggregateRating)** — Google requires at least one of these three. New products lack reviews. Always include offers object even if reviews exist—it's the most reliable option for meeting this requirement.

**5. Fake or manipulated reviews** — Google explicitly penalizes "fake, hidden, or misattributed reviews." Only include reviews visible on page from verified WooCommerce review system. Never output aggregateRating for products with zero reviews. Manual actions are severe and difficult to recover from.

## Implications for Roadmap

Based on research, suggested phase structure organized by dependency order and complexity:

### Phase 1: Foundation & Core Product Schema
**Rationale:** Must establish foundation before any Product schema output. Duplicate schema with WooCommerce is the #1 critical pitfall that must be addressed first. Simple products are the most common type and have well-documented patterns.

**Delivers:**
- Product schema infrastructure (Product_Schema class, registry registration)
- WooCommerce native schema disabled
- Simple product support with all required properties
- Offer schema with price, availability, currency
- GTIN/MPN/brand meta fields in product editor

**Addresses features:**
- Product name, image, offers (required properties)
- Auto-population from WooCommerce data
- Availability mapping
- SKU and brand mapping
- Image handling with validation

**Avoids pitfalls:**
- Pitfall #1: Disables WooCommerce native schema before outputting custom schema
- Pitfall #2: Strict `is_singular('product')` check prevents archive page output
- Pitfall #4: Always includes offers object to meet Google's "one of three" requirement
- Pitfall #13: Image validation with fallback handling

**Research flag:** Standard patterns, skip research-phase. WooCommerce API and schema.org Product type are well-documented.

### Phase 2: Variable Products
**Rationale:** Variable products require special handling for multiple variations with different prices/availability. This is the most complex feature with moderate-risk pitfalls. Must come after simple product foundation is solid.

**Delivers:**
- AggregateOffer for variable products with price ranges
- Variant-specific availability checking
- Variable product type detection and routing

**Uses stack:**
- `WC_Product_Variable` class with `get_children()` for variation IDs
- `get_variation_price('min')` and `get_variation_price('max')` for price ranges
- Per-variation `wc_get_product()` calls for availability

**Implements architecture:**
- `build_aggregate_offer()` method for variable products
- Conditional offer building based on `$product->is_type('variable')`

**Avoids pitfalls:**
- Pitfall #6: Use AggregateOffer instead of single offer for variable products
- Pitfall #7: Check variation-level availability, not just parent product stock

**Research flag:** May need research-phase for ProductGroup implementation if that approach is chosen over AggregateOffer. ProductGroup is Google-recommended but complex and rarely documented in plugins.

### Phase 3: Reviews & Ratings
**Rationale:** Reviews enhance rich results but have strict validation requirements. This phase has high risk due to Google's penalties for fake/manipulated reviews. Must implement with careful validation.

**Delivers:**
- AggregateRating from WooCommerce product reviews
- Individual Review array (limit to 5 most recent)
- Review author Person objects with rating values

**Implements architecture:**
- `build_rating_schema()` with zero-review validation
- `build_reviews_schema()` querying WooCommerce comments
- Rating value validation (only include reviews with star ratings)

**Avoids pitfalls:**
- Pitfall #5: Only include verified WooCommerce reviews visible on page
- Pitfall #12: Skip reviews without rating values (text-only comments)

**Research flag:** Standard patterns, skip research-phase. WooCommerce review system is well-documented and `get_average_rating()`/`get_review_count()` provide all needed data.

### Phase 4: Enhanced Identifiers & Polish
**Rationale:** Addresses warnings (not errors) that improve rich result quality. These are nice-to-have enhancements that don't block basic functionality.

**Delivers:**
- priceValidUntil for sale items
- itemCondition with NewCondition default
- Enhanced brand detection with taxonomy fallback
- Optional MerchantReturnPolicy support
- Optional OfferShippingDetails support

**Addresses features:**
- Item condition (NewCondition default)
- priceValidUntil for sale items

**Avoids pitfalls:**
- Pitfall #9: Add priceValidUntil for sale products to prevent warnings
- Pitfall #11: Optional shipping/return policy (warnings only, not blocking)

**Research flag:** Skip research-phase for most items. MerchantReturnPolicy/OfferShippingDetails may need light research if complex WooCommerce integration is desired.

### Phase Ordering Rationale

- **Phase 1 must come first:** Cannot output any Product schema until WooCommerce native schema is disabled (critical pitfall). Simple products establish the foundation that variable products build on.

- **Phase 2 builds on Phase 1:** Variable products reuse the simple product Offer building logic but add AggregateOffer handling. Attempting this before simple products work would create unnecessary complexity.

- **Phase 3 is independent:** Reviews/ratings can be added anytime after Phase 1, but deferring until after variable products keeps Phase 2 focused on pricing/availability complexity.

- **Phase 4 is polish:** These are warnings not errors. Addressing after core functionality works prevents premature optimization.

### Research Flags

**Phases likely needing deeper research during planning:**
- **Phase 2 (if ProductGroup approach chosen):** Google's ProductGroup pattern for variants is recommended but rarely implemented. Sparse documentation from plugins. Would need research-phase to understand `variesBy`, `hasVariant`, `inProductGroupWithID` properties.

**Phases with standard patterns (skip research-phase):**
- **Phase 1 (Foundation):** WooCommerce Product API and schema.org Product type are extensively documented with official sources.
- **Phase 2 (if AggregateOffer approach chosen):** AggregateOffer with lowPrice/highPrice is well-documented and simpler than ProductGroup.
- **Phase 3 (Reviews):** WooCommerce review system and schema.org Review/AggregateRating types have clear documentation.
- **Phase 4 (Polish):** Minor enhancements with straightforward implementations.

## Confidence Assessment

| Area | Confidence | Notes |
|------|------------|-------|
| Stack | HIGH | Official WooCommerce code reference, proven API patterns in existing codebase |
| Features | HIGH | Google official documentation clearly defines Merchant Listing requirements |
| Architecture | HIGH | Based on existing codebase analysis, established registry pattern proven working |
| Pitfalls | HIGH | Verified with Google official documentation, WooCommerce GitHub issues, and SEO plugin documentation |

**Overall confidence:** HIGH

The domain is mature with extensive official documentation from both Google (schema requirements) and WooCommerce (API reference). The existing Saman SEO codebase provides proven architectural patterns. All critical pitfalls have documented prevention strategies from official Google sources.

### Gaps to Address

**Multi-currency plugin compatibility:** Multi-currency plugins change displayed prices via JavaScript or server-side filters. Schema uses `get_woocommerce_currency()` which returns base currency. This can cause price mismatches for non-base currency visitors.
- **Handling:** Document as known limitation for MVP. Consider `apply_filters('woocommerce_currency', ...)` hook for popular plugins. May require Phase 5 integration research if critical for users.

**ProductGroup vs AggregateOffer decision:** Research shows two valid approaches for variable products. AggregateOffer is simpler (price range), ProductGroup is Google-recommended (individual variant offers).
- **Handling:** Recommend starting with AggregateOffer in Phase 2 for simplicity. ProductGroup can be Phase 5 enhancement if differentiation is desired. Defer decision until roadmap planning.

**Review visibility validation:** Google requires reviews in schema to be visible on page, but WooCommerce allows paginated reviews. If product has 100 reviews but only 10 show per page, should schema include all or just visible?
- **Handling:** Limit schema to 5 most recent reviews (Google's informal limit). This ensures they're likely on first page. Add note in Phase 3 planning to validate this approach.

## Sources

### Primary (HIGH confidence)
- [WC_Product Class Reference](https://woocommerce.github.io/code-reference/classes/WC-Product.html) — comprehensive API documentation
- [Google Product Structured Data](https://developers.google.com/search/docs/appearance/structured-data/product) — official requirements
- [Google Merchant Listing Structured Data](https://developers.google.com/search/docs/appearance/structured-data/merchant-listing) — WooCommerce use case
- [schema.org Product Type](https://schema.org/Product) — canonical schema definition
- [schema.org Offer Type](https://schema.org/Offer) — nested offer structure
- Existing codebase analysis — proven registry architecture pattern

### Secondary (MEDIUM confidence)
- [WooCommerce GitHub Issue #17471](https://github.com/woocommerce/woocommerce/issues/17471) — variable product schema discussion
- [WooCommerce Structured Data Wiki](https://github.com/woocommerce/woocommerce/wiki/Structured-data-for-products) — community documentation
- [Rank Math WooCommerce Guide](https://rankmath.com/kb/woocommerce-product-schema/) — competitive plugin approach
- [Schema.press Variable Products](https://schema.press/docs-extensions/woocommerce-variable-products/) — ProductGroup implementation example

### Tertiary (LOW confidence)
- [SNIP Plugin - Variable Products](https://rich-snippets.io/structured-data-for-variable-products/) — ProductGroup vs AggregateOffer tradeoffs
- [Hill Web Creations - Fix Product Markup](https://www.hillwebcreations.com/fix-product-markup-errors-avoid-google-manual-action/) — manual action recovery guide
- Community forum discussions — anecdotal experiences with multi-currency plugins

---
*Research completed: 2026-01-23*
*Ready for roadmap: yes*
