---
phase: 01-schema-engine-foundation
plan: 01
subsystem: schema
tags: [php, json-ld, schema.org, abstract-class, value-object, helper]

# Dependency graph
requires: []
provides:
  - Abstract_Schema base class with is_needed()/generate()/get_type() contract
  - Schema_Context value object for environment data
  - Schema_IDs helper for consistent @id fragment generation
affects: [01-02, 01-03, all-future-schema-implementations]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Abstract base class with abstract methods for contract enforcement"
    - "Value object pattern for immutable context data"
    - "Static helper class for utility functions"
    - "Dependency injection via constructor"

key-files:
  created:
    - includes/Schema/class-abstract-schema.php
    - includes/Schema/class-schema-context.php
    - includes/Schema/class-schema-ids.php
  modified: []

key-decisions:
  - "Schema_IDs uses static methods (not constants) for dynamic URL generation"
  - "Schema_Context determines schema_type with priority: post meta > post type settings > Article default"
  - "Abstract_Schema::get_id() handles both string and array @type values"

patterns-established:
  - "All schema classes extend Abstract_Schema and implement is_needed()/generate()/get_type()"
  - "Schema generation receives context via constructor, never accesses globals directly"
  - "Individual schema pieces never include @context (only Graph_Manager root does)"

# Metrics
duration: 2min
completed: 2026-01-23
---

# Phase 01 Plan 01: Schema Engine Foundation Classes Summary

**Three foundational PHP classes for the schema engine: Abstract_Schema base class with contract methods, Schema_Context value object for environment data, Schema_IDs helper for consistent @id generation**

## Performance

- **Duration:** 2 min
- **Started:** 2026-01-23T18:13:31Z
- **Completed:** 2026-01-23T18:15:35Z
- **Tasks:** 3
- **Files created:** 3

## Accomplishments

- Created includes/Schema/ directory as new namespace for schema engine
- Schema_IDs provides 7 static methods for consistent @id fragment generation
- Schema_Context captures WordPress state via from_current() and from_post() factories
- Abstract_Schema establishes is_needed()/generate()/get_type() contract for all schema types

## Task Commits

Each task was committed atomically:

1. **Task 1: Create Schema_IDs helper class** - `4c88cf6` (feat)
2. **Task 2: Create Schema_Context value object** - `8a7387e` (feat)
3. **Task 3: Create Abstract_Schema base class** - `6a372ea` (feat)

## Files Created/Modified

- `includes/Schema/class-schema-ids.php` - Static helper with 7 methods for @id fragment generation (website, organization, person, webpage, article, breadcrumb, author)
- `includes/Schema/class-schema-context.php` - Value object with public properties (canonical, site_url, site_name, post, post_type, meta, schema_type) and factory methods
- `includes/Schema/class-abstract-schema.php` - Abstract base class with constructor accepting Schema_Context, 3 abstract methods, and protected get_id() helper

## Decisions Made

- **Schema_IDs uses static methods:** Methods like `website()` must call `home_url()` dynamically; constants would be evaluated too early
- **Schema_Context.schema_type priority:** Post-specific meta overrides post type defaults, with Article as fallback
- **get_id() array handling:** When @type is an array (multi-typed entities), uses first element for @id fragment

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all tasks completed successfully.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Schema foundation classes ready for Schema_Registry (plan 01-02)
- Classes follow patterns from RESEARCH.md exactly
- No blockers for proceeding to registry implementation

---
*Phase: 01-schema-engine-foundation*
*Completed: 2026-01-23*
