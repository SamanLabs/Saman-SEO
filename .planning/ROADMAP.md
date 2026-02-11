# Roadmap: Saman SEO

## Milestones

- [x] **v1.0 Schema Engine** - Phases 1-6 (shipped 2026-01-23)
- [x] **v1.1 WooCommerce Product Schemas** - Phases 7-10 (shipped 2026-01-24)
- [ ] **v1.2 Legacy Cleanup & Hardening** - Phases 11-12 (in progress)

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

<details>
<summary>v1.1 WooCommerce Product Schemas (Phases 7-10) - SHIPPED 2026-01-24</summary>

### Phase 7: Foundation
**Goal**: Establish WooCommerce integration foundation with proper schema conflict prevention
**Plans**: 1 plan (complete)

### Phase 8: Simple Products
**Goal**: Complete Product schema output for simple products with single Offer
**Plans**: 2 plans (complete)

### Phase 9: Variable Products
**Goal**: AggregateOffer support for variable products with price ranges
**Plans**: 1 plan (complete)

### Phase 10: Reviews & Ratings
**Goal**: AggregateRating and Review schema from WooCommerce reviews
**Plans**: 1 plan (complete)

</details>

<details open>
<summary>v1.2 Legacy Cleanup & Hardening (Phases 11-12) - IN PROGRESS</summary>

### Phase 11: Legacy Removal
**Goal**: Remove deprecated schema type settings from UI and backend

**Dependencies**: None (cleanup phase)

**Requirements**: UI-01, UI-02, UI-03, BACK-01, BACK-02, BACK-03

**Success Criteria**:
1. Search Appearances content type settings show no schema type dropdowns (Default Schema Type, Schema Page Type, Schema Article Type removed)
2. REST API for Search Appearances no longer accepts or returns schema_type/schema_page/schema_article fields
3. Schema_Context::determine_schema_type() derives type from post meta or defaults only, no global settings lookup
4. JsonLD service contains no deprecated methods (breadcrumb_ld, get_publisher_schema removed)
5. Existing per-post schema overrides continue to work (no regression)

### Phase 12: Hardening
**Goal**: Add defensive error handling throughout schema generation pipeline

**Dependencies**: Phase 11 (clean codebase simplifies hardening)

**Requirements**: HARD-01, HARD-02, HARD-03

**Success Criteria**:
1. Template variable resolution failures are caught and logged without breaking page output
2. Schema generation gracefully handles null/missing $post object in all schema types
3. Database migration failures are caught, logged, and do not crash the plugin
4. Error conditions produce meaningful log entries for debugging

</details>

## Progress

**Execution Order:** Phases execute in numeric order

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
| 9. Variable Products | v1.1 | 1/1 | Complete | 2026-01-24 |
| 10. Reviews & Ratings | v1.1 | 1/1 | Complete | 2026-01-24 |
| 11. Legacy Removal | v1.2 | 0/? | Pending | — |
| 12. Hardening | v1.2 | 0/? | Pending | — |

---
*Roadmap created: 2026-01-23*
*Last updated: 2026-01-25 — v1.2 phases added*
