# Saman SEO - Schema Engine Overhaul

## What This Is

A comprehensive JSON-LD Schema Engine for the Saman SEO WordPress plugin. Replaces the current hardcoded schema implementation with an extensible, registry-based architecture that supports multiple schema types, per-post configuration, live preview, and a full developer API for third-party extension.

## Core Value

**Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.**

## Requirements

### Validated

<!-- Existing capabilities from current codebase -->

- ✓ Basic JSON-LD graph output (WebSite, WebPage, Breadcrumb) — existing
- ✓ Organization/Person publisher schema with social profiles — existing
- ✓ Article schema for posts with author/dates — existing
- ✓ Local SEO data integration (address, phone, email) — existing
- ✓ Filter hooks for schema modification (`SAMAN_SEO_jsonld`, `SAMAN_SEO_jsonld_graph`) — existing
- ✓ Post type schema type setting in Search Appearance — existing
- ✓ FAQ and HowTo Gutenberg blocks (UI exists, schema weak) — existing

### Active

<!-- Current scope for this milestone -->

**Schema Engine Architecture:**
- [ ] Type registry system for registering/managing schema types
- [ ] Base schema class with common properties (@context, @id, @type)
- [ ] Field definition system (required, optional, nested types)
- [ ] Schema builder that assembles JSON-LD from field values
- [ ] Graph manager for combining multiple schemas on a page

**High-Impact Schema Types:**
- [ ] FAQPage schema (mainEntity with Question/Answer pairs)
- [ ] HowTo schema (steps, tools, supplies, total time)
- [ ] Article schema improvements (wordCount, speakable, full author)
- [ ] BlogPosting schema (distinct from Article)
- [ ] NewsArticle schema (dateline, print metadata)
- [ ] Event schema (startDate, endDate, location, offers, performer)
- [ ] LocalBusiness schema (openingHours, geo, priceRange, aggregateRating)

**Admin UI Integration:**
- [ ] Post type defaults in Search Appearance settings
- [ ] Per-post schema type selector in editor sidebar
- [ ] Live JSON-LD preview panel in post editor
- [ ] Schema field editor for type-specific properties

**Developer API:**
- [ ] `saman_seo_register_schema_type` action for custom types
- [ ] `saman_seo_schema_{type}_fields` filter for modifying fields
- [ ] `saman_seo_schema_{type}_output` filter for final output
- [ ] `saman_seo_schema_types` filter for type registry
- [ ] PHP API for programmatic schema generation

### Out of Scope

- WooCommerce Product schema — defer to dedicated e-commerce milestone
- Video schema — defer to v2 (requires media detection complexity)
- Recipe schema — defer to v2 (requires ingredient/instruction parsing)
- Built-in schema.org validation — live preview sufficient for v1
- Carousel/ItemList schemas — defer to v2
- Profile page schema — defer to v2

## Context

**Existing Architecture:**
- `includes/class-saman-seo-service-jsonld.php` - Main JSON-LD service, builds graph
- `includes/Service/class-saman-seo-service-*-schema.php` - Individual schema stubs (mostly placeholder)
- Filter-based extension via `SAMAN_SEO_jsonld_graph`
- React admin in `src-v2/` with REST API controllers

**Reference Documentation:**
- `jsonld_examples/` folder contains 27 documented schema types with real-world examples
- High-impact types: `faq-json-ld-snippet.md`, `event.md`, `local-business.md`, `article.md`
- Foundation types: `breadcrumb.md`, `organization.md`, `person.md`, `website.md`

**Current Pain Points:**
- Schema types are hardcoded, not extensible
- Individual schema services have dummy data
- No unified field system or validation
- No per-post schema configuration UI
- Developers can't register custom schema types

## Constraints

- **WordPress Compatibility**: Must work with WordPress 5.0+ and PHP 7.4+
- **Existing Data**: Must not break existing JSON-LD output or post meta
- **React Admin**: Schema UI must integrate with existing React admin architecture
- **Performance**: Schema generation must not impact frontend page load significantly
- **Google Guidelines**: Schemas must follow Google's structured data requirements for rich results eligibility

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Registry-based architecture | Enables extensibility without modifying core code | — Pending |
| Post type defaults + per-post override | Balances convenience (defaults) with flexibility (override) | — Pending |
| Live preview over built-in validation | Simpler implementation, users can use Google's validator | — Pending |
| Skip WooCommerce for this milestone | Focus on content schemas, e-commerce has unique complexity | — Pending |
| FAQ/HowTo/Article/Event/LocalBusiness first | Highest Google rich results impact, most requested | — Pending |

---
*Last updated: 2026-01-23 after initialization*
