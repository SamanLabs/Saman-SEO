---
phase: quick-001
plan: 01
type: execute
wave: 1
depends_on: []
files_modified:
  - src-v2/editor/index.js
  - src-v2/editor/editor.css
autonomous: true

must_haves:
  truths:
    - "SEO panel opens as a centered modal dialog when toolbar button is clicked"
    - "Modal can be closed via X button, Escape key, or clicking overlay"
    - "All existing SEO functionality works identically in modal form"
  artifacts:
    - path: "src-v2/editor/index.js"
      provides: "Modal-based SEO panel with toolbar button trigger"
      contains: "Modal"
    - path: "src-v2/editor/editor.css"
      provides: "Modal overlay and container styles"
      contains: "saman-seo-modal"
  key_links:
    - from: "toolbar button"
      to: "modal open state"
      via: "useState toggle"
    - from: "SEOPanel component"
      to: "Modal container"
      via: "children prop (unchanged)"
---

<objective>
Convert the SEO sidebar panel to a popup modal dialog triggered from the Gutenberg top toolbar.

Purpose: Give users more screen real estate when editing by moving SEO controls from the always-visible sidebar to an on-demand modal. This addresses the user's concern about having 4 tabs open taking too much space.

Output: Modified editor integration that shows SEO panel in a modal instead of sidebar, with a prominent toolbar button to open it.
</objective>

<execution_context>
@~/.claude/get-shit-done/workflows/execute-plan.md
@~/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@src-v2/editor/index.js
@src-v2/editor/components/SEOPanel.js
@src-v2/editor/editor.css
</context>

<tasks>

<task type="auto">
  <name>Task 1: Convert sidebar to modal with toolbar button</name>
  <files>src-v2/editor/index.js</files>
  <action>
Refactor the SEOSidebar component to use a modal approach instead of PluginSidebar:

1. Replace `PluginSidebar` and `PluginSidebarMoreMenuItem` with a modal-based approach:
   - Import `Modal` from `@wordpress/components`
   - Import `PluginBlockEditorToolbarItem` from `@wordpress/edit-post` for toolbar placement
   - Use `useState` to track modal open/closed state

2. Create a toolbar button using `PluginBlockEditorToolbarItem`:
   - Use the existing `PluginIcon` component for the button icon
   - Button text: "SEO" (compact for toolbar)
   - On click: toggle modal open state
   - Use `registerPlugin` with the toolbar item instead of sidebar

3. Wrap SEOPanel in Modal component when open:
   ```jsx
   {isOpen && (
     <Modal
       title="Saman SEO"
       onRequestClose={() => setIsOpen(false)}
       className="saman-seo-modal-wrapper"
       isDismissible={true}
       shouldCloseOnClickOutside={true}
       shouldCloseOnEsc={true}
     >
       <SEOPanel {...props} />
     </Modal>
   )}
   ```

4. Keep ALL existing logic intact:
   - State management (seoMeta, seoScore, etc.)
   - API fetching (post meta, score calculation)
   - Variable values computation
   - updateMeta function
   - All props passed to SEOPanel remain the same

5. The registerPlugin call changes from rendering PluginSidebar to rendering:
   - PluginBlockEditorToolbarItem (always visible toolbar button)
   - Conditional Modal (only when isOpen is true)
  </action>
  <verify>
Run `npm run build:editor` - should compile without errors.
Open WordPress post editor, verify "SEO" button appears in top toolbar.
  </verify>
  <done>
Toolbar button visible in Gutenberg editor. Clicking it opens modal with SEO panel.
  </done>
</task>

<task type="auto">
  <name>Task 2: Style the modal for optimal UX</name>
  <files>src-v2/editor/editor.css</files>
  <action>
Add modal-specific styles to editor.css:

1. Modal wrapper override for size:
   ```css
   .saman-seo-modal-wrapper {
     /* Override WordPress modal defaults for larger size */
   }

   .saman-seo-modal-wrapper .components-modal__content {
     /* Set max-width: 540px for comfortable reading */
     /* Set max-height: 80vh with overflow-y: auto */
     /* Remove default padding (SEOPanel has its own) */
     padding: 0;
   }

   .saman-seo-modal-wrapper .components-modal__header {
     /* Style header to match plugin branding */
     border-bottom: 1px solid #e0e0e0;
   }
   ```

2. Ensure existing .saman-seo-editor-panel styles work in modal context:
   - Panel should fill modal width
   - Tabs should remain functional
   - Score header should display properly

3. Add toolbar button styles if needed:
   ```css
   .saman-seo-toolbar-btn {
     /* Ensure consistent appearance in toolbar */
   }
   ```

4. Responsive consideration:
   - On smaller screens, modal should use nearly full width
   - Add media query for screens < 600px to use width: calc(100% - 32px)
  </action>
  <verify>
Run `npm run build:editor` - should compile without errors.
Open modal in editor, verify it displays centered, properly sized, scrollable if content overflows.
  </verify>
  <done>
Modal appears centered with appropriate width (540px max), proper scrolling, and clean styling.
  </done>
</task>

<task type="auto">
  <name>Task 3: Build and verify integration</name>
  <files>build/editor/index.js</files>
  <action>
1. Run the full build:
   ```bash
   npm run build:editor
   ```

2. Verify the build output exists and is reasonable size

3. Test in WordPress:
   - Clear browser cache
   - Open any post/page in Gutenberg editor
   - Verify toolbar button appears (look for "SEO" in top bar)
   - Click button - modal should open
   - Test all 5 tabs (General, Analysis, Advanced, Social, Schema)
   - Test closing modal (X button, Escape key, click outside)
   - Verify SEO data saves correctly when post is updated
  </action>
  <verify>
Build completes successfully. Editor loads without console errors. All SEO panel functionality works in modal form.
  </verify>
  <done>
Complete working modal implementation. User can open/close SEO panel via toolbar button, all features functional.
  </done>
</task>

</tasks>

<verification>
1. `npm run build:editor` completes without errors
2. Toolbar button visible in Gutenberg top bar area
3. Clicking button opens modal with full SEO panel
4. All 5 tabs work correctly within modal
5. Modal closes via X, Escape, or outside click
6. SEO settings save properly when post is saved
7. No console errors during operation
</verification>

<success_criteria>
- SEO panel appears as modal instead of sidebar
- Toolbar button provides quick access
- All existing functionality preserved
- Clean, professional modal appearance
- Responsive on various screen sizes
</success_criteria>

<output>
After completion, create `.planning/quick/001-seo-metabox-popup-modal/001-SUMMARY.md`
</output>
