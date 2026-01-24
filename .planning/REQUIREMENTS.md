# Requirements: Saman SEO v1.1 WooCommerce Product Schemas

**Defined:** 2026-01-23
**Core Value:** Enable Product rich results in Google search for WooCommerce stores with price, availability, images, and review ratings.

## v1.1 Requirements

Requirements for WooCommerce Product schema support. Each maps to roadmap phases.

### Integration

- [x] **INTG-01**: Plugin disables WooCommerce native JSON-LD schema output
- [x] **INTG-02**: Product schema only outputs on singular product pages (never archives)
- [x] **INTG-03**: Product schema type auto-registers when WooCommerce is active
- [x] **INTG-04**: Product schema integrates with existing Schema_Registry architecture

### Product Core

- [x] **PROD-01**: Product schema outputs name property from product title
- [x] **PROD-02**: Product schema outputs description from product short description
- [x] **PROD-03**: Product schema outputs image array from product gallery
- [x] **PROD-04**: Product schema outputs sku property from product SKU
- [x] **PROD-05**: Product schema outputs url linking to product permalink
- [x] **PROD-06**: Product schema outputs brand from product attribute or global setting
- [x] **PROD-07**: Product schema outputs gtin/mpn from product custom fields
- [x] **PROD-08**: Product schema outputs itemCondition property (NewCondition default)

### Offers

- [x] **OFFR-01**: Simple products output single Offer with price and priceCurrency
- [x] **OFFR-02**: Offer outputs availability based on stock status
- [x] **OFFR-03**: Offer outputs priceValidUntil from sale end date or default
- [x] **OFFR-04**: Variable products output AggregateOffer with lowPrice/highPrice
- [x] **OFFR-05**: AggregateOffer availability reflects any variation in stock
- [x] **OFFR-06**: Offer outputs seller referencing Organization schema

### Reviews

- [ ] **REVW-01**: Product schema outputs AggregateRating when product has reviews
- [ ] **REVW-02**: AggregateRating outputs ratingValue from WooCommerce average
- [ ] **REVW-03**: AggregateRating outputs reviewCount from WooCommerce count
- [ ] **REVW-04**: Products with zero reviews do NOT output AggregateRating
- [ ] **REVW-05**: Product schema outputs Review array with individual reviews
- [ ] **REVW-06**: Each Review outputs author, reviewRating, reviewBody, datePublished

## Future Requirements

Deferred to later milestones. Tracked but not in current scope.

### Enhanced Features

- **ENHC-01**: ProductGroup schema for variable products (Google-preferred alternative to AggregateOffer)
- **ENHC-02**: Per-variation images in ProductGroup approach
- **ENHC-03**: OfferShippingDetails for shipping cost rich results
- **ENHC-04**: MerchantReturnPolicy schema
- **ENHC-05**: Multi-currency support with currency switcher plugins

### Admin UI

- **ADUI-01**: Per-product schema override in product editor
- **ADUI-02**: Live schema preview for WooCommerce products
- **ADUI-03**: Brand attribute selector in settings

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Product schema on archive pages | Google penalizes Product schema on category/shop pages |
| Schema validation in plugin | Users can use Google Rich Results Test tool |
| Fake/placeholder reviews | Google penalizes non-customer reviews |
| Multi-currency dynamic prices | Complex edge case, document as limitation |
| WooCommerce Blocks compatibility | Focus on classic product pages first |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| INTG-01 | Phase 7 | Complete |
| INTG-02 | Phase 7 | Complete |
| INTG-03 | Phase 7 | Complete |
| INTG-04 | Phase 7 | Complete |
| PROD-01 | Phase 8 | Complete |
| PROD-02 | Phase 8 | Complete |
| PROD-03 | Phase 8 | Complete |
| PROD-04 | Phase 8 | Complete |
| PROD-05 | Phase 8 | Complete |
| PROD-06 | Phase 8 | Complete |
| PROD-07 | Phase 8 | Complete |
| PROD-08 | Phase 8 | Complete |
| OFFR-01 | Phase 8 | Complete |
| OFFR-02 | Phase 8 | Complete |
| OFFR-03 | Phase 8 | Complete |
| OFFR-04 | Phase 9 | Complete |
| OFFR-05 | Phase 9 | Complete |
| OFFR-06 | Phase 8 | Complete |
| REVW-01 | Phase 10 | Pending |
| REVW-02 | Phase 10 | Pending |
| REVW-03 | Phase 10 | Pending |
| REVW-04 | Phase 10 | Pending |
| REVW-05 | Phase 10 | Pending |
| REVW-06 | Phase 10 | Pending |

**Coverage:**
- v1.1 requirements: 24 total
- Mapped to phases: 24
- Unmapped: 0

---
*Requirements defined: 2026-01-23*
*Last updated: 2026-01-24 after Phase 9 execution*
