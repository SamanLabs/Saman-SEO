---
phase: 05-admin-ui
plan: 03
status: complete
subsystem: schema-ui
tags: [react, settings, schema-type, api]

dependency-graph:
  requires:
    - 01-01 (Schema_Context with schema_type priority)
  provides:
    - UI for post type schema_type defaults
    - Full flow: UI -> API -> Option -> Schema_Context
  affects:
    - 02-content-schemas (Article variants can now be configured per post type)

tech-stack:
  patterns:
    - React controlled select for schema_type dropdown
    - REST API field whitelist pattern for schema_type

files:
  key-files:
    created: []
    modified:
      - src-v2/pages/SearchAppearance.js
      - includes/Api/class-searchappearance-controller.php
      - includes/Schema/class-schema-context.php

decisions:
  - id: schema-type-option-name
    choice: Use SAMAN_SEO_post_type_settings
    reason: Backend already saves all post type settings there; Schema_Context was incorrectly reading from non-existent SAMAN_SEO_post_type_seo_settings
  - id: schema-type-dropdown-position
    choice: Before legacy Schema Page/Article Type selectors
    reason: schema_type is the primary schema setting affecting content schema output

metrics:
  duration: 3 min
  completed: 2026-01-23
---

# Phase 05 Plan 03: Schema Type Defaults UI Summary

Schema type dropdown in Content Types settings with full backend persistence and Schema_Context integration.

## What Was Built

### Task 1: Frontend Schema Type Dropdown
Added "Default Schema Type" dropdown to PostTypeEditor component in SearchAppearance.js:
- Uses existing `schemaTypeOptions` array (14 schema types including Article, BlogPosting, NewsArticle, etc.)
- Wired to `editingPostType.schema_type` state
- Positioned before legacy Schema Page/Article Type selectors
- Includes descriptive help text: "Schema type used for posts of this type unless overridden per-post"

### Task 2: Backend Schema Type Support
Extended REST API controller to handle schema_type:
- `get_post_types_data()`: Returns `schema_type` from `SAMAN_SEO_post_type_settings` option
- `save_post_types()`: Sanitizes and saves `schema_type` in bulk operations
- `save_single_post_type()`: Sanitizes and saves `schema_type` for individual post type

Fixed Schema_Context option name mismatch:
- Was incorrectly reading from `SAMAN_SEO_post_type_seo_settings` (non-existent)
- Now correctly reads from `SAMAN_SEO_post_type_settings` (where API saves data)

## Data Flow

```
User selects "Blog posting" for Posts
         |
         v
SearchAppearance.js -> apiFetch POST /saman-seo/v1/search-appearance/post-types/post
         |
         v
SearchAppearance_Controller::save_single_post_type()
         |
         v
update_option('SAMAN_SEO_post_type_settings', ['post' => ['schema_type' => 'blogposting', ...]])
         |
         v
On frontend render, Schema_Context::determine_schema_type() reads option
         |
         v
Article_Schema uses context->schema_type to output BlogPosting @type
```

## Must-Haves Verified

| Truth | Status |
|-------|--------|
| Content Types tab shows schema type dropdown | Verified (lines 2007-2022) |
| Selected schema type saved on Save click | Verified (API handler + option) |
| Schema_Context respects post type default | Verified (line 152-155) |

| Artifact | Contains | Verified |
|----------|----------|----------|
| src-v2/pages/SearchAppearance.js | schema_type | Lines 2014-2015 |
| class-searchappearance-controller.php | schema_type | Lines 439, 482, 517 |

| Key Link | Pattern | Verified |
|----------|---------|----------|
| SearchAppearance.js -> /post-types API | apiFetch POST | Line 368 |
| Schema_Context -> SAMAN_SEO_post_type_settings | get_option | Line 152 |

## Deviations from Plan

### Auto-fixed Issues

**1. [Rule 1 - Bug] Fixed option name mismatch in Schema_Context**
- **Found during:** Task 2 analysis
- **Issue:** Schema_Context was reading from `SAMAN_SEO_post_type_seo_settings` but backend saves to `SAMAN_SEO_post_type_settings`
- **Fix:** Changed Schema_Context to read from correct option `SAMAN_SEO_post_type_settings`
- **Files modified:** includes/Schema/class-schema-context.php
- **Commit:** 1dc5794

**2. [Rule 2 - Missing Critical] Added schema_type to API data loading**
- **Found during:** Task 2 analysis
- **Issue:** `get_post_types_data()` didn't return `schema_type` field, so UI wouldn't show saved values
- **Fix:** Added `schema_type` to returned data array
- **Files modified:** includes/Api/class-searchappearance-controller.php
- **Commit:** 1dc5794

## Commits

| Hash | Type | Description |
|------|------|-------------|
| 6026983 | feat | Add schema_type dropdown to PostTypeEditor |
| 1dc5794 | feat | Add schema_type backend support + fix option name |

## Files Changed

```
src-v2/pages/SearchAppearance.js      | 17 +++++++++++++++++
includes/Api/class-searchappearance-controller.php | 3 +++
includes/Schema/class-schema-context.php | 3 +--
```

## Next Phase Readiness

Phase 05-admin-ui continues with remaining plans:
- 05-04: News/Print Section Schema UI (schema meta box fields)
- 05-05: FAQ/HowTo Block Settings (block-level showSchema toggle)
- 05-06: LocalBusiness Settings UI (organization/business type selection)
