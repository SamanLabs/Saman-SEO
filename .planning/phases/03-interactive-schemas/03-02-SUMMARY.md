---
phase: 03-interactive-schemas
plan: 02
subsystem: schema-types
tags: [howto, schema, gutenberg, blocks]

dependency-graph:
  requires: [03-01]
  provides: [howto-schema-type]
  affects: []

tech-stack:
  added: []
  patterns: [block-to-schema-mapping]

key-files:
  created:
    - includes/Schema/Types/class-howto-schema.php
  modified:
    - includes/Schema/class-schema-ids.php
    - includes/class-saman-seo-plugin.php
    - blocks/howto/index.js

decisions:
  - HowTo_Schema uses first block only (multiple HowTo schemas semantically unclear)
  - showSchema attribute controls inclusion (same as FAQPage)
  - Time parsing supports minutes and hours only (common use cases)
  - Inline schema removed from block save (registry is sole source)

metrics:
  duration: 3 min
  completed: 2026-01-23
---

# Phase 03 Plan 02: HowTo Schema Type Summary

HowTo schema generation from samanseo/howto Gutenberg blocks with step, tool, supply, and time properties parsed to ISO 8601 duration format.

## What Was Built

### HowTo_Schema Class
Created `includes/Schema/Types/class-howto-schema.php`:
- Extends Abstract_Schema following established pattern
- Parses HowTo blocks from post content with recursive inner block support
- Uses first HowTo block only (multiple HowTo schemas per page is semantically unclear)
- Respects showSchema attribute to skip disabled blocks

### Schema Properties Generated
- `name` - HowTo title stripped of HTML
- `description` - HowTo description
- `totalTime` - ISO 8601 duration (PT30M, PT2H)
- `estimatedCost` - MonetaryAmount with currency and value
- `tool` - Array of HowToTool objects
- `supply` - Array of HowToSupply objects
- `step` - Array of HowToStep with position, name, text, image

### Time Parsing
`parse_time_to_iso()` converts human-readable time strings:
- "30 minutes", "30 min", "30m" -> "PT30M"
- "2 hours", "2 h" -> "PT2H"

### Schema_IDs Extension
Added `Schema_IDs::howto($url)` returning `URL#howto` format.

### Registry Registration
Registered `howto` type at priority 18 (same as FAQPage, after content schemas).

### Block Update
Removed inline JSON-LD output from HowTo block save function:
- Schema now handled exclusively by PHP registry
- showSchema attribute preserved for registry to check
- Microdata attributes preserved for fallback structured data

## Key Decisions

| Decision | Rationale |
|----------|-----------|
| First block only | Multiple HowTo schemas on one page is semantically unclear and would require @id differentiation |
| Time parsing limited to min/hours | Covers 95%+ of HowTo use cases; complex durations rare |
| Inline schema removed | Registry is authoritative source; prevents duplicate schema output |
| parseTime kept in JS | Still needed for microdata content attributes in visual output |

## Files Changed

| File | Change |
|------|--------|
| `includes/Schema/class-schema-ids.php` | Added howto() method |
| `includes/Schema/Types/class-howto-schema.php` | New HowTo schema class |
| `includes/class-saman-seo-plugin.php` | Added HowTo_Schema use statement and registration |
| `blocks/howto/index.js` | Removed inline JSON-LD script output |

## Commits

| Hash | Message |
|------|---------|
| 38fce73 | feat(03-02): add Schema_IDs::howto() method |
| e80d00c | feat(03-02): create HowTo_Schema class and register in plugin |
| 34f1a45 | refactor(03-02): remove inline schema from HowTo block save |

## Deviations from Plan

None - plan executed exactly as written.

## Verification Results

All verification checks passed:
- Schema_IDs::howto() method exists
- HowTo_Schema class valid PHP (no syntax errors)
- Registration in plugin confirmed
- Inline schema removed from block (no application/ld+json)
- Visual microdata preserved (itemType HowTo/HowToTool/HowToSupply/HowToStep)
- Both interactive schemas (faqpage, howto) registered

## Next Phase Readiness

Phase 03 complete:
- Plan 01: FAQPage schema from FAQ blocks
- Plan 02: HowTo schema from HowTo blocks

Both interactive schema types now generate from their respective Gutenberg blocks with:
- Consistent @id generation via Schema_IDs
- showSchema toggle support
- Priority 18 (after content schemas, before breadcrumb)
- Inline schema removed from blocks (registry is sole source)
