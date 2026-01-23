---
id: quick-003
type: quick
title: Fix SEO Modal Tab UX and Styling
status: complete
completed: 2026-01-23
duration: 2 min
commits:
  - 724eef6: fix(editor): stabilize SEO modal height for consistent tab UX
files_modified:
  - src-v2/editor/editor.css
---

# Quick Task 003: Fix SEO Modal Tab UX and Styling Summary

**Fixed SEO modal with 70vh fixed height and flex layout to prevent tab bar shifting when switching tabs.**

## What Was Done

### Task 1: Add fixed-height layout to SEO modal

Updated CSS in `src-v2/editor/editor.css` to create a fixed-height modal layout:

**Key CSS changes:**

1. **Modal content fixed height:**
   - Changed from `max-height: 80vh` with `overflow-y: auto` to `height: 70vh` with flex column layout
   - This ensures the modal maintains consistent height regardless of content

2. **Panel flex layout:**
   - Added `display: flex; flex-direction: column; flex: 1; min-height: 0; overflow: hidden;` to `.saman-seo-editor-panel`
   - This allows proper flex child behavior for internal scrolling

3. **Fixed header and tabs:**
   - Added `flex-shrink: 0;` to both `.saman-seo-score-header` and `.saman-seo-tabs`
   - These elements no longer change position when content changes

4. **Scrollable tab content:**
   - Added `flex: 1; overflow-y: auto; min-height: 0;` to `.saman-seo-tab-content`
   - Content scrolls within the tab area while header and tabs stay fixed

## Technical Details

The fix uses CSS flexbox properties to create a stable layout:
- `min-height: 0` is critical for flex children to properly scroll (overrides default `min-height: auto`)
- `overflow: hidden` on the panel prevents double scrollbars
- `flex-shrink: 0` prevents header/tabs from compressing when content is large

## Verification

- Build completed successfully: `npm run build:editor`
- Modal now maintains consistent 70vh height across all tabs
- Tab bar stays fixed at same Y position when switching tabs
- Content scrolls within tab area for long content (like Analysis tab)

## Deviations from Plan

None - plan executed exactly as written.
