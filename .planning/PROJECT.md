# Saman Labs SEO

> Rebrand and refactor of Saman SEO to Saman Labs SEO with clean AI plugin dependency

## Vision

Transform Saman SEO into Saman Labs SEO — a focused SEO plugin that delegates AI functionality to the separate Saman Labs AI plugin. Clean rebrand with working feature toggles.

## Requirements

### Validated

*Existing capabilities from current codebase:*

- ✓ Service-based architecture with 30+ services — existing
- ✓ React admin interface (V2) with REST API — existing
- ✓ Per-post SEO meta (title, description, canonical, noindex, nofollow, og_image) — existing
- ✓ JSON-LD structured data generation — existing
- ✓ 301/302 redirect management with custom table — existing
- ✓ XML sitemap enhancement — existing
- ✓ Internal linking engine with keyword-to-link automation — existing
- ✓ Breadcrumb markup with schema — existing
- ✓ Local SEO / local business schema — existing
- ✓ 9 schema types (Video, Course, Book, Music, Movie, Restaurant, Service, Job_Posting, Software) — existing
- ✓ 404 monitoring and logging — existing
- ✓ Link health / broken link detection — existing
- ✓ IndexNow search engine notification — existing
- ✓ Gutenberg blocks (FAQ, HowTo, Breadcrumbs) — existing
- ✓ WooCommerce product schema integration — existing
- ✓ SEO audit capabilities — existing

### Active

*New requirements for this project:*

- [ ] **Full rebrand to "Saman Labs SEO"** — rename plugin, all strings, CSS classes, function prefixes, database table prefixes, option keys, REST API namespace, constants, file names
- [ ] **Clean AI plugin dependency** — remove built-in AI providers (OpenAI, Anthropic, Ollama), delegate to Saman Labs AI plugin via integration layer
- [ ] **Graceful degradation** — when Saman Labs AI not installed: SEO features work fully, AI features disabled with clear admin notice/messaging
- [ ] **Fix feature toggles** — ensure when a feature is toggled off, it is actually disabled (hooks not registered, assets not loaded, UI hidden)
- [ ] **Update WP AI Pilot integration to Saman Labs AI** — rename integration class, update hook names, ensure seamless handoff

### Out of Scope

- New SEO features — focus is rebrand and architecture fixes
- Migration tooling — personal use only, can reinstall fresh
- Backward compatibility — clean break from wpseopilot_ prefixes

## Context

### User Profile

- Solo developer using plugin on own sites
- No external users requiring migration support
- Can reinstall and reconfigure as needed

### Technical Context

- PHP 7.4+, WordPress 5.8+
- React admin via @wordpress/element
- 5 custom database tables (will need renamed)
- 50+ wp_options entries (will need renamed)
- REST API namespace `wpseopilot/v2` → `samanlabs-seo/v1`

### Key Files Affected

**Bootstrap:**
- `wp-seo-pilot.php` → `saman-labs-seo.php`
- Constants: `WPSEOPILOT_*` → `SAMANLABS_SEO_*`
- Namespace: `WPSEOPilot` → `SamanLabs\SEO`

**Database:**
- Tables: `wp_wpseopilot_*` → `wp_samanlabs_seo_*`
- Options: `wpseopilot_*` → `samanlabs_seo_*`
- Post meta: `_wpseopilot_meta` → `_samanlabs_seo_meta`

**REST API:**
- Namespace: `wpseopilot/v2` → `samanlabs-seo/v1`

**CSS Classes:**
- `.wpseopilot-*` → `.samanlabs-seo-*`

**Hooks:**
- Actions/filters: `wpseopilot_*` → `samanlabs_seo_*`

## Key Decisions

| Decision | Rationale | Outcome |
|----------|-----------|---------|
| Full rebrand (not user-facing only) | Personal use, no migration needed, want clean codebase | — Pending |
| Graceful degradation for AI | SEO should work without AI plugin, AI is enhancement | — Pending |
| Clean break, no backward compat | Solo user, can reinstall fresh | — Pending |
| Fix feature toggles | Current toggles don't actually disable features | — Pending |

## Risks

| Risk | Likelihood | Impact | Mitigation |
|------|------------|--------|------------|
| Missing a rename somewhere | High | Medium | Systematic search-and-replace, test thoroughly |
| Feature toggle fix breaks things | Medium | Medium | Understand current toggle flow before changing |
| AI integration changes break existing assistant logic | Medium | High | Map current integration points carefully |

---
*Last updated: 2026-01-16 after initialization*
