# Requirements: Saman SEO Schema Engine

**Defined:** 2026-01-23
**Core Value:** Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.

## v1 Requirements

Requirements for initial release. Each maps to roadmap phases.

### Schema Engine Core

- [x] **ENG-01**: Schema type registry that stores and retrieves registered schema types
- [x] **ENG-02**: Base schema class with @context, @id, @type properties and render method
- [x] **ENG-03**: Graph manager that combines multiple schema objects into single JSON-LD output
- [x] **ENG-04**: Migration from existing hardcoded JsonLD service to new engine

### High-Impact Schema Types

- [x] **FAQ-01**: FAQPage schema type with mainEntity containing Question/Answer pairs
- [x] **FAQ-02**: Integration with existing FAQ Gutenberg block to auto-generate schema
- [x] **HOW-01**: HowTo schema type with step, tool, supply, totalTime properties
- [x] **HOW-02**: Integration with existing HowTo Gutenberg block to auto-generate schema
- [ ] **LOC-01**: LocalBusiness schema type with full property support
- [ ] **LOC-02**: OpeningHoursSpecification for business hours
- [ ] **LOC-03**: GeoCoordinates for location data
- [ ] **LOC-04**: Integration with existing Local SEO settings as data source

### Content Schema Types

- [x] **ART-01**: Article schema with full author Person schema
- [x] **ART-02**: Article schema with wordCount and articleBody excerpt
- [x] **ART-03**: BlogPosting schema type extending Article
- [x] **ART-04**: NewsArticle schema type with dateline and print section
- [x] **ART-05**: Automatic schema type selection based on post type settings

### Admin UI

- [x] **UI-01**: Post type schema defaults in Search Appearance settings
- [x] **UI-02**: Schema type dropdown selector in post editor sidebar
- [x] **UI-03**: Live JSON-LD preview panel showing rendered schema
- [x] **UI-04**: Preview updates in real-time as post content changes
- [x] **UI-05**: REST API endpoint for fetching schema preview data

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
| ENG-01 | Phase 1 | Complete |
| ENG-02 | Phase 1 | Complete |
| ENG-03 | Phase 1 | Complete |
| ENG-04 | Phase 1 | Complete |
| FAQ-01 | Phase 3 | Complete |
| FAQ-02 | Phase 3 | Complete |
| HOW-01 | Phase 3 | Complete |
| HOW-02 | Phase 3 | Complete |
| LOC-01 | Phase 4 | Pending |
| LOC-02 | Phase 4 | Pending |
| LOC-03 | Phase 4 | Pending |
| LOC-04 | Phase 4 | Pending |
| ART-01 | Phase 2 | Complete |
| ART-02 | Phase 2 | Complete |
| ART-03 | Phase 2 | Complete |
| ART-04 | Phase 2 | Complete |
| ART-05 | Phase 2 | Complete |
| UI-01 | Phase 5 | Complete |
| UI-02 | Phase 5 | Complete |
| UI-03 | Phase 5 | Complete |
| UI-04 | Phase 5 | Complete |
| UI-05 | Phase 5 | Complete |
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
*Last updated: 2026-01-23 - Phase 3 requirements complete (FAQ-01, FAQ-02, HOW-01, HOW-02)*
