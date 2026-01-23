---
phase: 05-admin-ui
plan: 02
subsystem: ui
tags: [react, gutenberg, wordpress-components, json-ld, schema-preview, debounce]

# Dependency graph
requires:
  - phase: 05-01
    provides: "Schema Preview REST API endpoint"
provides:
  - "Schema tab in Gutenberg editor sidebar"
  - "Live JSON-LD preview with debounced API calls"
  - "Per-post schema type override selector"
  - "Real-time preview updates on content changes"
affects: [05-03-schema-defaults, phase-6-testing]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "React hooks with debounce and AbortController for API optimization"
    - "WordPress Components integration (SelectControl, Button, Spinner)"
    - "Loading/error/empty state patterns for async data"

key-files:
  created:
    - src-v2/editor/hooks/useSchemaPreview.js
    - src-v2/editor/components/SchemaTypeSelector.js
    - src-v2/editor/components/SchemaPreview.js
  modified:
    - src-v2/editor/components/SEOPanel.js
    - src-v2/editor/index.js
    - src-v2/editor/editor.css

key-decisions:
  - "500ms debounce on schema preview API calls to avoid spam during typing"
  - "AbortController cleanup pattern for unmounting/rapid changes"
  - "Copy to clipboard functionality for JSON-LD inspection"
  - "Preview refreshes on both schema_type changes and content changes"

patterns-established:
  - "useSchemaPreview hook pattern: debounced API fetch with loading/error states"
  - "Schema preview UI pattern: loading spinner, error state, empty state, copy button"
  - "Tab integration pattern in SEOPanel for new features"

# Metrics
duration: 5min
completed: 2026-01-23
---

# Phase 5 Plan 2: Schema Tab UI Summary

**Gutenberg sidebar Schema tab with live JSON-LD preview, debounced API fetching, and per-post schema type override**

## Performance

- **Duration:** 5 min
- **Started:** 2026-01-23T21:06:00Z
- **Completed:** 2026-01-23T21:11:00Z
- **Tasks:** 3
- **Files modified:** 6

## Accomplishments
- Schema tab integrated into Gutenberg editor sidebar alongside existing SEO tabs
- Live JSON-LD preview with 500ms debounce for performance optimization
- Schema type selector dropdown (Article, BlogPosting, NewsArticle) with per-post override
- Real-time preview updates when content or schema type changes
- Copy-to-clipboard functionality for JSON-LD inspection

## Task Commits

Each task was committed atomically:

1. **Task 1: Create useSchemaPreview hook** - `86b9924` (feat)
2. **Task 2: Create SchemaTypeSelector and SchemaPreview components** - `80281c2` (feat)
3. **Task 3: Integrate Schema tab into SEOPanel** - `8e78ed7` (feat)

**Plan metadata:** (to be committed)

## Files Created/Modified

### Created
- `src-v2/editor/hooks/useSchemaPreview.js` - React hook for debounced schema preview API calls with AbortController cleanup
- `src-v2/editor/components/SchemaTypeSelector.js` - Dropdown selector for schema type override (Article, BlogPosting, NewsArticle)
- `src-v2/editor/components/SchemaPreview.js` - JSON-LD preview panel with loading/error/empty states and copy button

### Modified
- `src-v2/editor/components/SEOPanel.js` - Added Schema tab button, integrated selector and preview components
- `src-v2/editor/index.js` - Added schema_type to seoMeta state and persistence layer
- `src-v2/editor/editor.css` - Added styles for schema preview component (dark code theme, loading/error states)

## Decisions Made

**1. 500ms debounce on API calls**
- Prevents API spam during typing/editing
- Balances responsiveness with server load
- Uses setTimeout with AbortController for cleanup

**2. Preview refreshes on content changes**
- Hook depends on postTitle and postContent
- Allows users to see how content affects schema output
- Debounce prevents excessive API calls

**3. Copy-to-clipboard for JSON-LD**
- Enables developers to inspect/validate schema markup
- Uses navigator.clipboard API with 2-second success feedback
- Formats JSON with 2-space indentation for readability

**4. Graceful handling of unsaved posts**
- No API call when postId is null (unsaved draft)
- Shows "Save post to see schema preview" message
- Prevents 404 errors on new posts

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None - all tasks completed without issues.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Schema tab UI complete and functional
- Ready for plan 05-03 (Schema Type Defaults UI in settings)
- Per-post override working, now need global post type defaults
- Preview endpoint integration validated

**Verification completed:**
- User confirmed Schema tab visible in editor sidebar
- Schema Type dropdown functional with Article/BlogPosting/NewsArticle options
- JSON-LD preview displays correctly with dark theme formatting
- Save/reload persistence working (schema_type meta saved correctly)
- Preview updates on content changes after debounce

---
*Phase: 05-admin-ui*
*Completed: 2026-01-23*
