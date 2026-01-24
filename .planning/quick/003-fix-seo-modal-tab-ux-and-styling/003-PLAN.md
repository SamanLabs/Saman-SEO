---
id: quick-003
type: quick
title: Fix SEO Modal Tab UX and Styling
status: planned
files_modified:
  - src-v2/editor/editor.css
autonomous: true
---

<objective>
Fix the SEO modal popup tab UX so that switching between tabs does not cause the modal height to change, preventing the tab bar from shifting vertically.

**Problems addressed:**
1. Modal height changes when switching tabs (content varies per tab)
2. Tab bar moves up/down on Y-axis instead of staying fixed
3. User has to reposition mouse vertically to switch tabs

**Solution:**
- Set fixed height on modal content area (not the whole modal)
- Make tab content area scrollable with overflow-y: auto
- Keep score header and tab bar fixed at top, only content scrolls
</objective>

<context>
@src-v2/editor/index.js - Modal component using WordPress Modal
@src-v2/editor/components/SEOPanel.js - Panel with tabs and tab content
@src-v2/editor/editor.css - Current styles for modal and tabs
</context>

<tasks>

<task type="auto">
  <name>Task 1: Add fixed-height layout to SEO modal</name>
  <files>src-v2/editor/editor.css</files>
  <action>
Update the modal styles in editor.css to create a fixed-height layout:

1. Set `.saman-seo-modal-wrapper .components-modal__content` to use flexbox column layout with a fixed height (e.g., `height: 70vh` or similar) instead of max-height with auto-grow behavior

2. Make `.saman-seo-editor-panel` use flex layout to organize its children (score header, tabs, content) in a column with the content area taking remaining space

3. Update `.saman-seo-tab-content` to:
   - Use `flex: 1` to fill available space
   - Add `overflow-y: auto` so content scrolls internally
   - Add `min-height: 0` (required for flex children to scroll properly)

4. Ensure `.saman-seo-score-header` and `.saman-seo-tabs` remain fixed (not scrolling) by keeping them as regular flex items without flex-grow

The key CSS changes (add/modify in the "SEO Modal Styles" section around line 2030):

```css
/* Modal content uses fixed height */
.saman-seo-modal-wrapper .components-modal__content {
    padding: 0;
    height: 70vh;
    display: flex;
    flex-direction: column;
}

/* Panel fills modal and uses flex layout */
.saman-seo-modal-wrapper .saman-seo-editor-panel {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
    overflow: hidden;
}

/* Score header and tabs stay fixed */
.saman-seo-modal-wrapper .saman-seo-score-header,
.saman-seo-modal-wrapper .saman-seo-tabs {
    flex-shrink: 0;
}

/* Tab content scrolls internally */
.saman-seo-modal-wrapper .saman-seo-tab-content {
    flex: 1;
    overflow-y: auto;
    min-height: 0;
}
```

Remove or update the existing `max-height: 80vh` and `overflow-y: auto` from `.saman-seo-modal-wrapper .components-modal__content` since we're changing the approach.
  </action>
  <verify>
1. Build the JS bundle: `npm run build:editor`
2. Open WordPress editor, open SEO modal
3. Switch between all 5 tabs (General, Analysis, Advanced, Social, Schema)
4. Verify: Modal height stays constant, tab bar doesn't move vertically
5. Verify: Long content (like Analysis tab with many items) scrolls within the tab content area
6. Verify: Score header and tabs remain visible at all times
  </verify>
  <done>
- Modal maintains consistent height across all tabs
- Tab bar stays fixed in position when switching tabs
- Tab content scrolls internally when content exceeds available space
- User can switch tabs without repositioning mouse
  </done>
</task>

</tasks>

<verification>
1. Open any post/page in Gutenberg editor
2. Click the SEO button to open modal
3. Switch rapidly between all 5 tabs
4. Confirm the tab buttons don't move vertically
5. On tabs with long content, confirm scrolling works within the content area
6. Test on different viewport heights to ensure responsive behavior
</verification>

<success_criteria>
- Tab bar stays in fixed Y position when switching between any tabs
- Modal maintains consistent height (70vh or similar fixed value)
- Content scrolls within tab content area, not the whole modal
- No visual regressions in tab styling or content display
</success_criteria>

<output>
Commit with message: `fix(editor): stabilize SEO modal height for consistent tab UX`
</output>
