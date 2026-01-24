# Roadmap: Saman SEO

## Milestones

- [x] **v1.0 Schema Engine** - Phases 1-6 (shipped 2026-01-23)
- [ ] **v1.1 WooCommerce Product Schemas** - Phases 7-10 (in progress)

## Phases

<details>
<summary>v1.0 Schema Engine (Phases 1-6) - SHIPPED 2026-01-23</summary>

### Phase 1: Schema Engine Foundation
**Goal**: Create extensible registry-based schema architecture
**Plans**: 3 plans (complete)

### Phase 2: Content Schemas
**Goal**: Article, BlogPosting, NewsArticle with author support
**Plans**: 2 plans (complete)

### Phase 3: Interactive Schemas
**Goal**: FAQPage and HowTo with Gutenberg block integration
**Plans**: 2 plans (complete)

### Phase 4: LocalBusiness Schema
**Goal**: LocalBusiness with hours and coordinates
**Plans**: 2 plans (complete)

### Phase 5: Admin UI
**Goal**: Schema selector and live preview in editor
**Plans**: 3 plans (complete)

### Phase 6: Developer API
**Goal**: Public hooks and documentation for extensibility
**Plans**: 2 plans (complete)

</details>

### v1.1 WooCommerce Product Schemas (In Progress)

**Milestone Goal:** Enable Product rich results in Google search for WooCommerce stores with price, availability, images, and review ratings.

- [x] **Phase 7: Foundation** - Disable WC schema, create Product_Schema skeleton, integrate with registry
- [x] **Phase 8: Simple Products** - Core properties and single Offer for simple products
- [ ] **Phase 9: Variable Products** - AggregateOffer for products with variations
- [ ] **Phase 10: Reviews & Ratings** - AggregateRating and Review objects

## Phase Details

### Phase 7: Foundation
**Goal**: Establish WooCommerce integration foundation with proper schema conflict prevention
**Depends on**: Phase 6 (v1.0 complete)
**Requirements**: INTG-01, INTG-02, INTG-03, INTG-04
**Success Criteria** (what must be TRUE):
  1. WooCommerce native JSON-LD schema is disabled when plugin is active
  2. Product schema only appears on single product pages, not on shop/category archives
  3. Product schema auto-registers in Schema_Registry when WooCommerce is detected
  4. Product_Schema class extends Abstract_Schema and integrates with Schema_Graph_Manager
**Plans**: 1 plan

Plans:
- [x] 07-01-PLAN.md - Disable WC native schema, create Product_Schema skeleton, extend Schema_IDs

### Phase 8: Simple Products
**Goal**: Complete Product schema output for simple products with single Offer
**Depends on**: Phase 7
**Requirements**: PROD-01, PROD-02, PROD-03, PROD-04, PROD-05, PROD-06, PROD-07, PROD-08, OFFR-01, OFFR-02, OFFR-03, OFFR-06
**Success Criteria** (what must be TRUE):
  1. Simple product pages output valid Product schema with name, description, image, sku, url
  2. Product schema includes brand from product attribute or global fallback setting
  3. Product schema includes gtin/mpn when custom fields are populated
  4. Simple products output single Offer with price, priceCurrency, availability, and seller
  5. Offer availability correctly reflects WooCommerce stock status (InStock/OutOfStock/PreOrder)
**Plans**: 2 plans

Plans:
- [x] 08-01-PLAN.md - Product core properties (description, images, sku, brand, identifiers, condition)
- [x] 08-02-PLAN.md - Offer building (price, availability, priceValidUntil, seller)

### Phase 9: Variable Products
**Goal**: AggregateOffer support for variable products with price ranges
**Depends on**: Phase 8
**Requirements**: OFFR-04, OFFR-05
**Success Criteria** (what must be TRUE):
  1. Variable products output AggregateOffer instead of single Offer
  2. AggregateOffer includes lowPrice/highPrice from variation price range
  3. AggregateOffer availability shows InStock if ANY variation is in stock
**Plans**: 1 plan

Plans:
- [ ] 09-01-PLAN.md - AggregateOffer with lowPrice/highPrice and child_is_in_stock availability

### Phase 10: Reviews & Ratings
**Goal**: AggregateRating and Review schema from WooCommerce reviews
**Depends on**: Phase 8
**Requirements**: REVW-01, REVW-02, REVW-03, REVW-04, REVW-05, REVW-06
**Success Criteria** (what must be TRUE):
  1. Products with reviews output AggregateRating with correct ratingValue and reviewCount
  2. Products with zero reviews do NOT output AggregateRating property
  3. Product schema outputs Review array with individual customer reviews
  4. Each Review includes author, reviewRating, reviewBody, and datePublished
**Plans**: TBD

Plans:
- [ ] 10-01: TBD

## Progress

**Execution Order:** Phases execute in numeric order: 7 -> 8 -> 9 -> 10

| Phase | Milestone | Plans Complete | Status | Completed |
|-------|-----------|----------------|--------|-----------|
| 1. Foundation | v1.0 | 3/3 | Complete | 2026-01-23 |
| 2. Content Schemas | v1.0 | 2/2 | Complete | 2026-01-23 |
| 3. Interactive Schemas | v1.0 | 2/2 | Complete | 2026-01-23 |
| 4. LocalBusiness | v1.0 | 2/2 | Complete | 2026-01-23 |
| 5. Admin UI | v1.0 | 3/3 | Complete | 2026-01-23 |
| 6. Developer API | v1.0 | 2/2 | Complete | 2026-01-23 |
| 7. Foundation | v1.1 | 1/1 | Complete | 2026-01-23 |
| 8. Simple Products | v1.1 | 2/2 | Complete | 2026-01-24 |
| 9. Variable Products | v1.1 | 0/1 | Planned | - |
| 10. Reviews & Ratings | v1.1 | 0/? | Not started | - |

---
*Roadmap created: 2026-01-23*
*Last updated: 2026-01-24 after Phase 9 planning*
