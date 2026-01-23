---
phase: 03-interactive-schemas
plan: 01
subsystem: schema
tags: [faqpage, json-ld, gutenberg-blocks, schema-registry]

# Dependency graph
requires:
  - phase: 01-schema-engine-foundation
    provides: Abstract_Schema, Schema_IDs, Schema_Registry, Graph_Manager
provides:
  - FAQPage_Schema class for block-based FAQ schema generation
  - Schema_IDs::faqpage() method for @id generation
  - Registry registration of faqpage type at priority 18
  - FAQ block with inline schema removed (registry is sole source)
affects: [03-02-howto-schema, phase-5-ui]

# Tech tracking
tech-stack:
  added: []
  patterns: [block-content-parsing, recursive-innerblock-extraction]

key-files:
  created:
    - includes/Schema/Types/class-faqpage-schema.php
  modified:
    - includes/Schema/class-schema-ids.php
    - includes/class-saman-seo-plugin.php
    - blocks/faq/index.js

key-decisions:
  - "FAQPage_Schema parses blocks from post_content rather than relying on block rendering"
  - "showSchema attribute on FAQ block controls inclusion in registry output"
  - "Multiple FAQ blocks combine into single mainEntity array"
  - "Recursive extraction handles FAQ blocks nested in columns/groups"
  - "Priority 18 places interactive schemas between content (15) and breadcrumb (20)"

patterns-established:
  - "Block-based schema: Schema type parses blocks via parse_blocks() in generate()"
  - "Interactive schema priority: 18 (after content 15, before breadcrumb 20)"

# Metrics
duration: 3min
completed: 2026-01-23
---

# Phase 3 Plan 1: FAQPage Schema from FAQ Blocks Summary

**FAQPage schema auto-generated from samanseo/faq Gutenberg blocks with showSchema toggle and multi-block combination**

## Performance

- **Duration:** 3 min
- **Started:** 2026-01-23T00:00:00Z
- **Completed:** 2026-01-23T00:03:00Z
- **Tasks:** 3
- **Files modified:** 4

## Accomplishments

- FAQPage_Schema class that parses FAQ blocks from post content
- Automatic combination of questions from multiple FAQ blocks into single mainEntity
- Respect for showSchema attribute allowing per-block schema toggle
- Recursive extraction supporting nested blocks (columns, groups)
- Removed inline JSON-LD from FAQ block - registry is now sole schema source

## Task Commits

Each task was committed atomically:

1. **Task 1: Add Schema_IDs::faqpage() method** - `8b334db` (feat)
2. **Task 2: Create FAQPage_Schema class and register in plugin** - `aad85c3` (feat)
3. **Task 3: Remove inline schema from FAQ block save output** - `ecee009` (refactor)

## Files Created/Modified

- `includes/Schema/Types/class-faqpage-schema.php` - FAQPage schema with block parsing and recursive extraction
- `includes/Schema/class-schema-ids.php` - Added faqpage() method returning URL#faqpage
- `includes/class-saman-seo-plugin.php` - Use statement and registry registration at priority 18
- `blocks/faq/index.js` - Removed inline JSON-LD script, kept visual microdata output

## Decisions Made

- **Block parsing approach:** FAQPage_Schema uses parse_blocks() on post_content rather than relying on rendered output. This allows schema generation in head without block rendering.
- **showSchema respect:** When showSchema attribute is false/empty, that FAQ block's questions are excluded from schema. Allows granular control per block.
- **Multi-block combination:** All FAQ blocks in a post contribute to a single FAQPage with combined mainEntity array (per schema.org spec).
- **Recursive extraction:** extract_faq_blocks() handles innerBlocks recursively, supporting FAQ blocks inside columns, groups, or other container blocks.
- **Priority 18:** Interactive schemas placed between content schemas (15) and breadcrumb (20) in the graph ordering.

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- FAQPage schema foundation complete
- Pattern established for block-based schema types (parse_blocks in generate)
- Ready for HowTo schema (03-02) which will follow same block-parsing pattern
- showSchema attribute mechanism can be reused for HowTo block

---
*Phase: 03-interactive-schemas*
*Completed: 2026-01-23*
