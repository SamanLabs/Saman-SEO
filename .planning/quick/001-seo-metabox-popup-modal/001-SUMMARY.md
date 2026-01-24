---
phase: quick-001
plan: 01
subsystem: editor
tags: [react, modal, gutenberg, toolbar, UX]
dependency-graph:
  requires: [existing SEOPanel component]
  provides: [modal-based SEO panel, toolbar button trigger]
  affects: [user workflow, editor UX]
tech-stack:
  added: []
  patterns: [WordPress Modal component, PluginBlockEditorToolbarItem]
key-files:
  created: []
  modified:
    - src-v2/editor/index.js
    - src-v2/editor/editor.css
decisions:
  - id: modal-over-sidebar
    summary: Modal gives users control over when to see SEO panel, freeing screen real estate
metrics:
  duration: 2 min
  completed: 2026-01-23
---

# Quick Task 001: SEO Metabox Popup Modal Summary

**One-liner:** Converted SEO sidebar to toolbar-triggered modal dialog using WordPress Modal component for better screen real estate management.

## What Was Done

Converted the always-visible SEO sidebar panel to an on-demand popup modal dialog, triggered by a toolbar button in the Gutenberg block editor.

### Key Changes

1. **Replaced sidebar with modal approach:**
   - Removed `PluginSidebar` and `PluginSidebarMoreMenuItem` imports
   - Added `Modal` from `@wordpress/components`
   - Added `PluginBlockEditorToolbarItem` from `@wordpress/edit-post`
   - Added `useState` to track modal open/closed state

2. **Toolbar button integration:**
   - Added "SEO" button to Gutenberg top toolbar
   - Uses existing `PluginIcon` component for consistent branding
   - Button click toggles modal visibility

3. **Modal configuration:**
   - Title: "Saman SEO"
   - Dismissible via X button, Escape key, or clicking outside
   - Max-width: 540px for comfortable reading
   - Max-height: 80vh with scroll for long content
   - Responsive on smaller screens (< 600px)

4. **All existing functionality preserved:**
   - State management (seoMeta, seoScore, etc.)
   - API fetching (post meta, score calculation)
   - Variable values computation
   - updateMeta function
   - All 5 tabs (General, Analysis, Advanced, Social, Schema)

## Commits

| Commit | Type | Description |
|--------|------|-------------|
| 33a81ef | feat | Convert SEO sidebar to modal with toolbar button |
| e91debe | style | Add modal styles for optimal UX |

## Files Modified

| File | Changes |
|------|---------|
| `src-v2/editor/index.js` | Replaced PluginSidebar with Modal, added toolbar button |
| `src-v2/editor/editor.css` | Added modal wrapper styles, toolbar button styles, responsive styles |

## Verification

- [x] `npm run build:editor` completes without errors
- [x] Build output exists (46.2 KiB JS, 29 KiB CSS)
- [x] All existing SEOPanel logic preserved
- [x] Modal closes via X, Escape, or outside click

## User Impact

- **Before:** SEO panel always visible in sidebar, taking up screen space with 4+ tabs
- **After:** SEO panel opens on-demand via toolbar button, freeing editor space

## Deviations from Plan

None - plan executed exactly as written.
