# Requirements: Saman SEO v1.2

**Defined:** 2026-01-25
**Core Value:** Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.

## v1.2 Requirements

Requirements for Legacy Cleanup & Hardening release.

### UI Cleanup

- [ ] **UI-01**: Remove Default Schema Type dropdown from Search Appearances content type settings
- [ ] **UI-02**: Remove Schema Page Type dropdown from Search Appearances content type settings
- [ ] **UI-03**: Remove Schema Article Type dropdown from Search Appearances content type settings

### Backend Cleanup

- [ ] **BACK-01**: Remove schema_type/schema_page/schema_article handling from SearchAppearance REST controller
- [ ] **BACK-02**: Simplify Schema_Context::determine_schema_type() to use only post meta and sensible defaults
- [ ] **BACK-03**: Remove deprecated methods from JsonLD service (breadcrumb_ld, get_publisher_schema, etc.)

### Hardening

- [ ] **HARD-01**: Add try-catch error handling around Twiglet template variable resolution
- [ ] **HARD-02**: Add null safety checks for $post before schema generation in all schema types
- [ ] **HARD-03**: Add error handling and validation to database migration/upgrade logic

## Future Requirements

Deferred to later milestones.

### Additional Schema Types

- **SCHEMA-01**: VideoObject schema with media detection
- **SCHEMA-02**: Recipe schema with ingredient/instruction parsing
- **SCHEMA-03**: Event schema
- **SCHEMA-04**: Course schema

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| Schema.org validation | Live preview sufficient, users can use Google's validator |
| Carousel/ItemList schemas | Low priority, complex implementation |
| Breaking changes to post meta storage | Would require migration, keep `_SAMAN_SEO_meta['schema_type']` format |
| Removing per-post schema override | Phase 5 feature, actively used |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| UI-01 | Phase 11 | Pending |
| UI-02 | Phase 11 | Pending |
| UI-03 | Phase 11 | Pending |
| BACK-01 | Phase 11 | Pending |
| BACK-02 | Phase 11 | Pending |
| BACK-03 | Phase 11 | Pending |
| HARD-01 | Phase 12 | Pending |
| HARD-02 | Phase 12 | Pending |
| HARD-03 | Phase 12 | Pending |

**Coverage:**
- v1.2 requirements: 9 total
- Mapped to phases: 9
- Unmapped: 0

---
*Requirements defined: 2026-01-25*
*Last updated: 2026-01-25 — Traceability table completed*
