# Requirements: Saman SEO Schema Engine

**Defined:** 2026-01-23
**Core Value:** Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Schema Engine Core

- [ ] **ENG-01**: Schema type registry that stores and retrieves registered schema types
- [ ] **ENG-02**: Base schema class with @context, @id, @type properties and render method
- [ ] **ENG-03**: Graph manager that combines multiple schema objects into single JSON-LD output
- [ ] **ENG-04**: Migration from existing hardcoded JsonLD service to new engine

### High-Impact Schema Types

- [ ] **FAQ-01**: FAQPage schema type with mainEntity containing Question/Answer pairs
- [ ] **FAQ-02**: Integration with existing FAQ Gutenberg block to auto-generate schema
- [ ] **HOW-01**: HowTo schema type with step, tool, supply, totalTime properties
- [ ] **HOW-02**: Integration with existing HowTo Gutenberg block to auto-generate schema
- [ ] **LOC-01**: LocalBusiness schema type with full property support
- [ ] **LOC-02**: OpeningHoursSpecification for business hours
- [ ] **LOC-03**: GeoCoordinates for location data
- [ ] **LOC-04**: Integration with existing Local SEO settings as data source

### Content Schema Types

- [ ] **ART-01**: Article schema with full author Person schema
- [ ] **ART-02**: Article schema with wordCount and articleBody excerpt
- [ ] **ART-03**: BlogPosting schema type extending Article
- [ ] **ART-04**: NewsArticle schema type with dateline and print section
- [ ] **ART-05**: Automatic schema type selection based on post type settings

### Admin UI

- [ ] **UI-01**: Post type schema defaults in Search Appearance settings
- [ ] **UI-02**: Schema type dropdown selector in post editor sidebar
- [ ] **UI-03**: Live JSON-LD preview panel showing rendered schema
- [ ] **UI-04**: Preview updates in real-time as post content changes
- [ ] **UI-05**: REST API endpoint for fetching schema preview data

### Developer API

- [ ] **DEV-01**: `saman_seo_register_schema_type` action for registering custom types
- [ ] **DEV-02**: `saman_seo_schema_{type}_fields` filter for modifying schema fields
- [ ] **DEV-03**: `saman_seo_schema_{type}_output` filter for modifying final output
- [ ] **DEV-04**: `saman_seo_schema_types` filter for filtering available types
- [ ] **DEV-05**: Documentation for extending schema system

## v2 Requirements

Deferred to future release. Tracked but not in current roadmap.

### Additional Schema Types

- **VID-01**: VideoObject schema type
- **VID-02**: Video detection from post content (YouTube, Vimeo embeds)
- **RCP-01**: Recipe schema type with ingredients and instructions
- **EVT-01**: Event schema type with dates, location, offers
- **EVT-02**: Event series support (recurring events)

### Advanced Features

- **FLD-01**: Formal field definition system with type validation
- **VAL-01**: Built-in schema.org validation
- **VAL-02**: Google Rich Results Test integration
- **EDI-01**: Per-post schema field editor for type-specific properties
- **API-01**: PHP API for programmatic schema generation
- **WOO-01**: WooCommerce Product schema integration

## Out of Scope

Explicitly excluded. Documented to prevent scope creep.

| Feature | Reason |
|---------|--------|
| WooCommerce Product schema | Dedicated e-commerce milestone, different complexity |
| Carousel/ItemList schemas | Low priority, complex implementation |
| Profile page schema | Niche use case |
| Vehicle listing schema | Niche use case |
| Restaurant menu schema | Subset of LocalBusiness, can add later |
| Built-in JSON-LD validator | Live preview sufficient, users can use Google's validator |

## Traceability

Which phases cover which requirements. Updated during roadmap creation.

| Requirement | Phase | Status |
|-------------|-------|--------|
| ENG-01 | Phase 1 | Pending |
| ENG-02 | Phase 1 | Pending |
| ENG-03 | Phase 1 | Pending |
| ENG-04 | Phase 1 | Pending |
| FAQ-01 | Phase 3 | Pending |
| FAQ-02 | Phase 3 | Pending |
| HOW-01 | Phase 3 | Pending |
| HOW-02 | Phase 3 | Pending |
| LOC-01 | Phase 4 | Pending |
| LOC-02 | Phase 4 | Pending |
| LOC-03 | Phase 4 | Pending |
| LOC-04 | Phase 4 | Pending |
| ART-01 | Phase 2 | Pending |
| ART-02 | Phase 2 | Pending |
| ART-03 | Phase 2 | Pending |
| ART-04 | Phase 2 | Pending |
| ART-05 | Phase 2 | Pending |
| UI-01 | Phase 5 | Pending |
| UI-02 | Phase 5 | Pending |
| UI-03 | Phase 5 | Pending |
| UI-04 | Phase 5 | Pending |
| UI-05 | Phase 5 | Pending |
| DEV-01 | Phase 6 | Pending |
| DEV-02 | Phase 6 | Pending |
| DEV-03 | Phase 6 | Pending |
| DEV-04 | Phase 6 | Pending |
| DEV-05 | Phase 6 | Pending |

**Coverage:**
- v1 requirements: 27 total
- Mapped to phases: 27
- Unmapped: 0

---
*Requirements defined: 2026-01-23*
*Last updated: 2026-01-23 - Traceability updated after roadmap creation*
