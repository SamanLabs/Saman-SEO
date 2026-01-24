# Saman SEO - Schema Engine

## What This Is

A comprehensive JSON-LD Schema Engine for the Saman SEO WordPress plugin. Provides an extensible, registry-based architecture supporting 8 schema types (WebSite, WebPage, Breadcrumb, Organization, Person, Article variants, FAQPage, HowTo, LocalBusiness), per-post configuration with live preview, and a full developer API for third-party extension.

## Core Value

**Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.**

## Requirements

### Validated

- ✓ Schema type registry that stores and retrieves registered schema types — v1.0
- ✓ Base schema class with @context, @id, @type properties and render method — v1.0
- ✓ Graph manager that combines multiple schema objects into single JSON-LD output — v1.0
- ✓ Migration from existing hardcoded JsonLD service to new engine — v1.0
- ✓ FAQPage schema type with mainEntity containing Question/Answer pairs — v1.0
- ✓ Integration with existing FAQ Gutenberg block to auto-generate schema — v1.0
- ✓ HowTo schema type with step, tool, supply, totalTime properties — v1.0
- ✓ Integration with existing HowTo Gutenberg block to auto-generate schema — v1.0
- ✓ LocalBusiness schema type with full property support — v1.0
- ✓ OpeningHoursSpecification for business hours — v1.0
- ✓ GeoCoordinates for location data — v1.0
- ✓ Integration with existing Local SEO settings as data source — v1.0
- ✓ Article schema with full author Person schema — v1.0
- ✓ Article schema with wordCount and articleBody excerpt — v1.0
- ✓ BlogPosting schema type extending Article — v1.0
- ✓ NewsArticle schema type with dateline and print section — v1.0
- ✓ Automatic schema type selection based on post type settings — v1.0
- ✓ Post type schema defaults in Search Appearance settings — v1.0
- ✓ Schema type dropdown selector in post editor sidebar — v1.0
- ✓ Live JSON-LD preview panel showing rendered schema — v1.0
- ✓ Preview updates in real-time as post content changes — v1.0
- ✓ REST API endpoint for fetching schema preview data — v1.0
- ✓ `saman_seo_register_schema_type` action for registering custom types — v1.0
- ✓ `saman_seo_schema_{type}_fields` filter for modifying schema fields — v1.0
- ✓ `saman_seo_schema_{type}_output` filter for modifying final output — v1.0
- ✓ `saman_seo_schema_types` filter for filtering available types — v1.0
- ✓ Documentation for extending schema system — v1.0

### Active

- [ ] Product schema with name, description, image, sku, brand properties
- [ ] Offer schema with price, priceCurrency, availability, url
- [ ] Variable product support with per-variation Offers
- [ ] AggregateRating schema from WooCommerce product reviews
- [ ] Review schema for individual product reviews
- [ ] Automatic Product schema on WooCommerce product pages
- [ ] Integration with existing schema engine architecture

## Current Milestone: v1.1 WooCommerce Product Schemas

**Goal:** Enable Product rich results in Google search for WooCommerce stores with price, availability, images, and review ratings.

**Target features:**
- Product schema with full property support (name, description, image, SKU, brand)
- Offer schema with price, currency, availability status
- Variable products with each variation as separate Offer
- AggregateRating from WooCommerce reviews
- Seamless integration with existing schema engine

### Out of Scope
- VideoObject schema — requires media detection complexity (v2 candidate)
- Recipe schema — requires ingredient/instruction parsing (v2 candidate)
- Built-in schema.org validation — live preview sufficient, users can use Google's validator
- Carousel/ItemList schemas — low priority, complex implementation
- Profile page schema — niche use case

## Context

**Current State (v1.0 shipped):**
- 37,578 lines of PHP + 21,382 lines of JavaScript
- Tech stack: PHP 7.4+, WordPress 5.0+, React (Gutenberg editor)
- 8 schema types in registry with priority ordering
- 4 public hooks for developer extension
- 1,368-line SCHEMA_DEVELOPER_API.md

**Architecture:**
- `includes/Schema/` — Core schema engine (Abstract_Schema, Schema_Context, Schema_IDs, Schema_Registry, Schema_Graph_Manager)
- `includes/Schema/Types/` — Schema type implementations (WebSite, WebPage, Breadcrumb, Organization, Person, Article, BlogPosting, NewsArticle, FAQPage, HowTo, LocalBusiness)
- `includes/Api/class-schema-preview-controller.php` — REST endpoint for live preview
- `src-v2/editor/components/` — React components (SchemaTypeSelector, SchemaPreview)
- `src-v2/editor/hooks/useSchemaPreview.js` — Preview hook with debounce
- `docs/SCHEMA_DEVELOPER_API.md` — Developer documentation

## Constraints

- **WordPress Compatibility**: Must work with WordPress 5.0+ and PHP 7.4+
- **Existing Data**: Must not break existing JSON-LD output or post meta
- **React Admin**: Schema UI must integrate with existing React admin architecture
- **Performance**: Schema generation must not impact frontend page load significantly
- **Google Guidelines**: Schemas must follow Google's structured data requirements for rich results eligibility

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Registry-based architecture | Enables extensibility without modifying core code | ✓ Good |
| Post type defaults + per-post override | Balances convenience (defaults) with flexibility (override) | ✓ Good |
| Live preview over built-in validation | Simpler implementation, users can use Google's validator | ✓ Good |
| Skip WooCommerce for v1.0 | Focus on content schemas, e-commerce has unique complexity | ✓ Good |
| FAQ/HowTo/Article/LocalBusiness first | Highest Google rich results impact, most requested | ✓ Good |
| @context only at graph root | Prevents duplicate @context in individual schemas | ✓ Good |
| Author as full Person object | Required for Google rich results eligibility | ✓ Good |
| FAQPage combines multiple blocks | Single mainEntity per page is cleaner for search engines | ✓ Good |
| HowTo uses first block only | Multiple HowTo schemas per page is semantically unclear | ✓ Good |
| LocalBusiness on homepage only | Matches Organization pattern, avoids per-page business data | ✓ Good |
| 500ms debounce for preview | Balances responsiveness with API efficiency | ✓ Good |
| Fields filter is optional | Concrete classes decide if they need it, reduces coupling | ✓ Good |

---
*Last updated: 2026-01-23 after starting v1.1 milestone*
