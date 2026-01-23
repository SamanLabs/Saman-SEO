---
phase: 01-schema-engine-foundation
plan: 02
subsystem: schema
tags: [php, json-ld, schema.org, registry-pattern, singleton, dependency-injection]

# Dependency graph
requires:
  - phase: 01-01
    provides: Abstract_Schema base class, Schema_Context value object, Schema_IDs helper
provides:
  - Schema_Registry singleton for extensible type registration
  - Schema_Graph_Manager for JSON-LD @graph orchestration
  - Backward-compatible SAMAN_SEO_jsonld_graph filter support
affects: [01-03, schema-migrations, third-party-extensions]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Singleton pattern for registry (private constructor, static instance())"
    - "Dependency injection of registry into graph manager"
    - "Priority-based processing with uasort"
    - "Filter hooks at registration, type output, and graph levels"

key-files:
  created:
    - includes/Schema/class-schema-registry.php
    - includes/Schema/class-schema-graph-manager.php
  modified: []

key-decisions:
  - "Registry uses singleton pattern for WordPress plugin compatibility"
  - "Graph_Manager receives registry via constructor injection for testability"
  - "Legacy SAMAN_SEO_jsonld_graph filter maintained for backward compatibility"
  - "@context added ONLY in Graph_Manager root, never in individual pieces"

patterns-established:
  - "Schema types registered with register(slug, class, args) where args includes label, post_types, priority"
  - "Graph building sorts types by priority (lower = earlier) before is_needed() checks"
  - "Type-specific filters follow pattern: saman_seo_schema_{slug}_output"

# Metrics
duration: 2min
completed: 2026-01-23
---

# Phase 01 Plan 02: Schema Registry and Graph Manager Summary

**Schema_Registry singleton for type registration with filter/action extensibility, and Schema_Graph_Manager orchestrator that builds JSON-LD @graph output with backward-compatible SAMAN_SEO_jsonld_graph support**

## Performance

- **Duration:** 2 min
- **Started:** 2026-01-23T18:18:14Z
- **Completed:** 2026-01-23T18:22:XX Z
- **Tasks:** 2
- **Files created:** 2

## Accomplishments

- Schema_Registry provides 6 public methods for type management (instance, register, get_types, has, make, get)
- Schema_Graph_Manager collects applicable schemas via is_needed() and combines into @graph array
- Full extensibility via WordPress filters at type registration, per-type output, and complete graph levels
- Backward compatibility maintained via SAMAN_SEO_jsonld_graph legacy filter

## Task Commits

Each task was committed atomically:

1. **Task 1: Create Schema_Registry singleton** - `41c994e` (feat)
2. **Task 2: Create Schema_Graph_Manager orchestrator** - `12ca336` (feat)

## Files Created/Modified

- `includes/Schema/class-schema-registry.php` - Singleton registry with register(), get_types(), has(), make(), get() methods; fires saman_seo_schema_type_registered action; applies saman_seo_schema_types filter
- `includes/Schema/class-schema-graph-manager.php` - Orchestrator accepting registry via DI; builds @graph from is_needed() schemas; applies type-specific and graph-wide filters; @context at root only

## Decisions Made

- **Singleton over DI container:** WordPress plugins conventionally use singletons; DI container adds complexity without commensurate benefit
- **Constructor injection for Graph_Manager:** Enables testing with mock registry while keeping registry as singleton for global access
- **Legacy filter signature:** SAMAN_SEO_jsonld_graph passes $context->post as second parameter to match existing implementations

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all tasks completed successfully.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Schema engine core complete: Abstract_Schema + Schema_Context + Schema_IDs + Schema_Registry + Schema_Graph_Manager
- Ready for Plan 03: Migrate existing schema types (WebSite, WebPage, Article, Breadcrumb) to new architecture
- No blockers for proceeding

---
*Phase: 01-schema-engine-foundation*
*Completed: 2026-01-23*
