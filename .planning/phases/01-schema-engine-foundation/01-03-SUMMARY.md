---
phase: 01-schema-engine-foundation
plan: 03
subsystem: schema
tags: [php, json-ld, schema.org, migration, inheritance, dependency-injection]

# Dependency graph
requires:
  - phase: 01-01
    provides: Abstract_Schema base class, Schema_Context, Schema_IDs
  - phase: 01-02
    provides: Schema_Registry singleton, Schema_Graph_Manager orchestration
provides:
  - Organization_Schema for Knowledge Graph organization entities
  - Person_Schema for Knowledge Graph person entities
  - WebSite_Schema for site-level schema with publisher reference
  - WebPage_Schema for post pages with dates and breadcrumb ref
  - Breadcrumb_Schema for BreadcrumbList with ListItem positioning
  - Complete schema engine integration in JsonLD service
affects: [02-article-schema, 03-faq-howto, all-future-schema-types]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - Schema type classes extend Abstract_Schema with is_needed()/generate()/get_type()
    - Schema types registered in plugin boot() before services
    - Priority-based ordering for graph output sequence

key-files:
  created:
    - includes/Schema/Types/class-organization-schema.php
    - includes/Schema/Types/class-person-schema.php
    - includes/Schema/Types/class-website-schema.php
    - includes/Schema/Types/class-webpage-schema.php
    - includes/Schema/Types/class-breadcrumb-schema.php
  modified:
    - includes/class-saman-seo-service-jsonld.php
    - includes/class-saman-seo-plugin.php

key-decisions:
  - "Legacy JsonLD methods marked @deprecated but kept for third-party compatibility"
  - "Priority ordering: WebSite(1), Org/Person(2), WebPage(10), Breadcrumb(20)"
  - "get_social_profiles() helper duplicated in both Organization and Person for encapsulation"

patterns-established:
  - "Schema Types in includes/Schema/Types/ namespace Saman\\SEO\\Schema\\Types"
  - "Each schema type file named class-{type}-schema.php with {Type}_Schema class"
  - "Schema registration happens in Plugin::boot() before service registration"

# Metrics
duration: 5min
completed: 2026-01-23
---

# Phase 01 Plan 03: Core Schema Types Migration Summary

**Migrated 5 core schema types (Organization, Person, WebSite, WebPage, Breadcrumb) to new engine architecture with complete JsonLD service delegation**

## Performance

- **Duration:** 5 min
- **Started:** 2026-01-23T18:23:30Z
- **Completed:** 2026-01-23T18:28:01Z
- **Tasks:** 3
- **Files modified:** 7

## Accomplishments

- Created Organization_Schema and Person_Schema for Knowledge Graph publisher entities
- Created WebSite_Schema, WebPage_Schema, and Breadcrumb_Schema for core page schemas
- Integrated schema engine into JsonLD service (delegates to Schema_Graph_Manager)
- Registered 5 core schema types in plugin bootstrap with priority ordering
- Maintained backward compatibility with legacy methods marked @deprecated

## Task Commits

Each task was committed atomically:

1. **Task 1: Create Organization and Person schema types** - `5866268` (feat)
2. **Task 2: Create WebSite, WebPage, and Breadcrumb schema types** - `109c5b9` (feat)
3. **Task 3: Wire up schema engine in JsonLD service and plugin bootstrap** - `f8d6ea9` (feat)

## Files Created/Modified

- `includes/Schema/Types/class-organization-schema.php` - Organization schema with logo, contact, address, social profiles
- `includes/Schema/Types/class-person-schema.php` - Person schema with image, jobTitle, url, social profiles
- `includes/Schema/Types/class-website-schema.php` - WebSite schema with publisher reference
- `includes/Schema/Types/class-webpage-schema.php` - WebPage schema with dates, breadcrumb ref, primaryImageOfPage
- `includes/Schema/Types/class-breadcrumb-schema.php` - BreadcrumbList schema with ListItem array
- `includes/class-saman-seo-service-jsonld.php` - Now delegates to Schema_Graph_Manager, legacy methods deprecated
- `includes/class-saman-seo-plugin.php` - Registers 5 core schema types in boot()

## Decisions Made

- **Legacy method preservation:** Kept get_organization_schema(), get_person_schema(), breadcrumb_ld(), get_publisher_schema(), get_social_profiles() with @deprecated tags for third-party code that may call them directly
- **Priority ordering:** WebSite first (1), Organization/Person second (2), WebPage later (10), Breadcrumb last (20) to match existing output structure
- **Social profile helper duplication:** Each publisher schema (Organization, Person) has its own get_social_profiles() method for encapsulation rather than sharing

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all implementations followed existing JsonLD service logic patterns.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Schema engine foundation complete with 5 working schema types
- Ready for Article schema migration (likely Phase 2 scope)
- Ready for FAQ/HowTo schema additions (likely Phase 3 scope)
- All existing JSON-LD output should remain functionally identical
- SAMAN_SEO_jsonld_graph filter still works for backward compatibility

---
*Phase: 01-schema-engine-foundation*
*Completed: 2026-01-23*
