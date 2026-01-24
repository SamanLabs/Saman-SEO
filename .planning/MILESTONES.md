# Project Milestones: Saman SEO Schema Engine

## v1.1 WooCommerce Product Schemas (Shipped: 2026-01-24)

**Delivered:** Complete Product rich results for WooCommerce stores with offers, reviews, and ratings.

**Phases completed:** 7-10 (5 plans total)

**Key accomplishments:**
- WooCommerce schema conflict prevention — disabled native WC JSON-LD at init priority 0
- Complete Product schema — name, description, images, SKU, brand (3-level fallback), identifiers
- Offer schema for simple products — price, currency, availability, priceValidUntil, seller
- AggregateOffer for variable products — lowPrice/highPrice with offerCount
- Review system — AggregateRating + individual Review objects from WC reviews

**Stats:**
- 21 files created/modified
- 750 lines of PHP in core v1.1 files
- 4 phases, 5 plans
- 2 days from v1.0 to ship

**Git range:** `feat(07-01)` → `feat(10-01)`

**What's next:** TBD — planning next milestone

---

## v1.0 Schema Engine (Shipped: 2026-01-23)

**Delivered:** Extensible, registry-based JSON-LD Schema Engine with 8 schema types, live preview UI, and full developer API for third-party extension.

**Phases completed:** 1-6 (14 plans total)

**Key accomplishments:**
- Registry-based schema architecture with Abstract_Schema base class, Schema_Context value objects, and Graph_Manager for combining multiple schemas into JSON-LD @graph
- Content schemas (Article, BlogPosting, NewsArticle) with full author Person objects, wordCount, articleBody, and type-specific properties
- Interactive schemas (FAQPage, HowTo) with automatic Gutenberg block parsing and schema generation
- LocalBusiness schema with OpeningHoursSpecification, GeoCoordinates, and zero-configuration Local SEO integration
- Admin UI with post editor Schema tab, type selector dropdown, live JSON-LD preview with 500ms debounce, and Search Appearance post type defaults
- Developer API with 4 hooks (`saman_seo_register_schema_type`, `saman_seo_schema_{type}_fields`, `saman_seo_schema_{type}_output`, `saman_seo_schema_types`) and 1,368-line developer documentation

**Stats:**
- 77 files created/modified
- 37,578 lines of PHP + 21,382 lines of JavaScript
- 6 phases, 14 plans, ~50 tasks
- 1 day from start to ship

**Git range:** `feat(01-01)` → `docs(phase-6)`

**What's next:** v1.1 WooCommerce Product Schemas ✓

---
