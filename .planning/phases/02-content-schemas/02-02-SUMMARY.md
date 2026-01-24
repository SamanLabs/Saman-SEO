---
phase: 02-content-schemas
plan: 02
subsystem: schema
tags: [jsonld, blogposting, newsarticle, schema.org, structured-data]

# Dependency graph
requires:
  - phase: 02-01
    provides: Article_Schema base class with author, wordCount, articleBody
provides:
  - BlogPosting_Schema extending Article_Schema (changes only @type)
  - NewsArticle_Schema with dateline and printSection properties
  - Registry entries for article, blogposting, newsarticle (priority 15)
affects: [02-03, content-selection, post-meta-ui]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Minimal subclass pattern (BlogPosting overrides only get_type/is_needed)"
    - "Extended subclass pattern (NewsArticle overrides generate with parent::generate())"

key-files:
  created:
    - includes/Schema/Types/class-blogposting-schema.php
    - includes/Schema/Types/class-newsarticle-schema.php
  modified:
    - includes/class-saman-seo-plugin.php

key-decisions:
  - "BlogPosting is minimal subclass - inherits everything except @type"
  - "NewsArticle dateline auto-generates from SAMAN_SEO_local_city if meta not set"
  - "printSection has no auto-generation (meta only)"
  - "All content schemas use priority 15 (after WebPage 10, before Breadcrumb 20)"

patterns-established:
  - "Minimal subclass: override get_type() and is_needed() only when schema differs only in @type"
  - "Extended subclass: call parent::generate() then add additional properties"

# Metrics
duration: 3min
completed: 2026-01-23
---

# Phase 02 Plan 02: BlogPosting and NewsArticle Schema Types Summary

**BlogPosting minimal subclass plus NewsArticle with dateline/printSection, both extending Article_Schema, registered at priority 15**

## Performance

- **Duration:** 3 min
- **Started:** 2026-01-23T19:05:32Z
- **Completed:** 2026-01-23T19:08:29Z
- **Tasks:** 3
- **Files modified:** 3

## Accomplishments

- BlogPosting_Schema created as minimal subclass of Article_Schema (overrides only @type)
- NewsArticle_Schema with dateline auto-generation from Local SEO city and printSection from meta
- Article, BlogPosting, NewsArticle registered in schema registry at priority 15

## Task Commits

1. **Task 1: Create BlogPosting_Schema** - `6dfedc1` (feat)
2. **Task 2: Create NewsArticle_Schema** - `30d9ef2` (feat)
3. **Task 3: Register content schema types in plugin** - `252e06b` (feat)

## Files Created/Modified

- `includes/Schema/Types/class-blogposting-schema.php` - Minimal subclass changing only @type to BlogPosting
- `includes/Schema/Types/class-newsarticle-schema.php` - Extended subclass adding dateline and printSection
- `includes/class-saman-seo-plugin.php` - Added use statements and registry entries for content schemas

## Decisions Made

- BlogPosting inherits all Article properties - only overrides get_type() and is_needed()
- NewsArticle dateline tries meta first, then auto-generates from SAMAN_SEO_local_city option with date
- printSection comes only from post meta (no auto-generation logic)
- All content schemas have priority 15, placing them after WebPage (10) and before Breadcrumb (20)
- Content schemas default to post_types ['post'] (extensible via filter)

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Content schema hierarchy complete (Article, BlogPosting, NewsArticle)
- Ready for 02-03: Post type settings and schema selection meta box
- Schema types now available for selection in post meta and post type settings

---
*Phase: 02-content-schemas*
*Completed: 2026-01-23*
