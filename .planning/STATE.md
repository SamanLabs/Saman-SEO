# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-23)

**Core value:** Enable Google rich results through properly structured JSON-LD schemas that users can configure per post type and developers can extend.
**Current focus:** Phase 5 - Admin UI (in progress)

## Current Position

Phase: 5 of 6 (Admin UI)
Plan: 2 of 3 in current phase
Status: In progress
Last activity: 2026-01-23 - Completed 05-02-PLAN.md

Progress: [########..] ~92%

## Performance Metrics

**Velocity:**
- Total plans completed: 11
- Average duration: 3 min
- Total execution time: 37 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-schema-engine-foundation | 3 | 9 min | 3 min |
| 02-content-schemas | 2 | 5 min | 2.5 min |
| 03-interactive-schemas | 2 | 6 min | 3 min |
| 04-localbusiness-schema | 2 | 4 min | 2 min |
| 05-admin-ui | 2 | 8 min | 4 min |

**Recent Trend:**
- Last 5 plans: 04-01 (2 min), 04-02 (2 min), 05-01 (3 min), 05-02 (5 min)
- Trend: Consistent

*Updated after each plan completion*

## Accumulated Context

### Decisions

Decisions are logged in PROJECT.md Key Decisions table.
Recent decisions affecting current work:

- Schema_IDs uses static methods for dynamic URL generation (not constants)
- Schema_Context.schema_type priority: post meta > post type settings > Article default
- Abstract_Schema::get_id() handles both string and array @type values
- Registry uses singleton pattern for WordPress plugin compatibility
- Graph_Manager receives registry via constructor injection for testability
- Legacy SAMAN_SEO_jsonld_graph filter maintained for backward compatibility
- @context added ONLY in Graph_Manager root, never in individual pieces
- Legacy JsonLD methods marked @deprecated but kept for third-party compatibility
- Priority ordering: WebSite(1), Org/Person(2), LocalBusiness(5), WebPage(10), Content(15), Interactive(18), Breadcrumb(20)
- Schema Types in includes/Schema/Types/ with Saman\SEO\Schema\Types namespace
- Author is full Person object (not name string) for Google rich results eligibility
- Article_Schema helpers are protected for subclass override capability
- BlogPosting is minimal subclass (inherits everything, changes only @type)
- NewsArticle dateline auto-generates from SAMAN_SEO_local_city if meta not set
- printSection has no auto-generation (meta only)
- FAQPage_Schema parses blocks from post_content via parse_blocks()
- showSchema attribute on FAQ/HowTo blocks controls inclusion in registry output
- Multiple FAQ blocks combine into single mainEntity array
- HowTo_Schema uses first block only (multiple HowTo schemas semantically unclear)
- Recursive extraction handles blocks nested in columns/groups
- Time parsing supports minutes and hours (covers 95%+ of use cases)
- Inline schema removed from HowTo block (registry is sole source)
- LocalBusiness_Schema outputs only on homepage (matches Organization pattern)
- Address requires street + city for PostalAddress output
- Opening hours grouped by identical times for compact output
- LocalBusiness priority 5 positions it after Organization (subtype relationship) and before WebPage
- Legacy filters commented out (not deleted) when migrating to Schema Registry
- Schema_Preview_Controller uses edit_post permission (not manage_options) for editor access
- Post type schema_type settings use SAMAN_SEO_post_type_settings option (fixed from incorrect SAMAN_SEO_post_type_seo_settings)
- Schema preview uses 500ms debounce to avoid API spam during content editing
- Preview refreshes on both schema_type changes and content changes (title/body)

### Pending Todos

None.

### Blockers/Concerns

None.

## Session Continuity

Last session: 2026-01-23
Stopped at: Completed 05-02-PLAN.md
Resume file: None
Next action: Continue to 05-03 (Schema Type Defaults UI)
