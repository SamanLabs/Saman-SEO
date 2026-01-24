---
phase: quick
plan: 002
type: execute
wave: 1
depends_on: []
files_modified:
  - includes/class-saman-seo-plugin.php
autonomous: true

must_haves:
  truths:
    - "No WordPress 6.7+ textdomain warning appears in debug log"
    - "Plugin boots correctly at plugins_loaded"
    - "Schema labels remain functional for internal use"
  artifacts:
    - path: "includes/class-saman-seo-plugin.php"
      provides: "Plugin bootstrap without early translation calls"
  key_links:
    - from: "saman-seo.php"
      to: "Plugin::boot()"
      via: "plugins_loaded hook"
      pattern: "plugins_loaded.*Plugin::instance\\(\\)->boot\\(\\)"
---

<objective>
Fix WordPress 6.7+ textdomain early loading warning by removing translation function calls from Plugin::boot()

Purpose: WordPress 6.7+ enforces that translations must be loaded at `init` or later. The `plugins_loaded` hook runs before `init`, so any `__()` calls during boot trigger the "_load_textdomain_just_in_time was called incorrectly" warning.

Output: Plugin boots without triggering translation warnings
</objective>

<execution_context>
@~/.claude/get-shit-done/workflows/execute-plan.md
@~/.claude/get-shit-done/templates/summary.md
</execution_context>

<context>
@includes/class-saman-seo-plugin.php (lines 74-133 contain __() calls in boot())
@saman-seo.php (line 137-144 shows plugins_loaded hook calling boot())
</context>

<tasks>

<task type="auto">
  <name>Task 1: Remove __() calls from schema registration labels</name>
  <files>includes/class-saman-seo-plugin.php</files>
  <action>
In the Plugin::boot() method, remove the __() wrapper from all schema registration labels. These labels are internal identifiers used programmatically, not displayed to users in the admin UI:

1. Line 78: Change `'label' => __( 'Local Business', 'saman-seo' )` to `'label' => 'Local Business'`
2. Line 121: Change `'label' => __( 'FAQ Page', 'saman-seo' )` to `'label' => 'FAQ Page'`
3. Line 129: Change `'label' => __( 'How To', 'saman-seo' )` to `'label' => 'How To'`

These labels are only used in the Schema_Registry for internal identification. The user-facing translations (admin menus, settings pages) are handled in Admin_V2::register_menu() which hooks to `admin_menu` - this runs after `init` so translations work correctly there.

DO NOT change any other code. Only remove the __() wrappers from these three lines.
  </action>
  <verify>
1. `grep -n "__(" includes/class-saman-seo-plugin.php` should show no __() calls in the boot() method (lines 62-202)
2. Enable WP_DEBUG and WP_DEBUG_LOG, load any page - no textdomain warning should appear
  </verify>
  <done>Plugin boots at plugins_loaded without triggering "_load_textdomain_just_in_time was called incorrectly" warning. Schema labels remain functional as plain strings.</done>
</task>

</tasks>

<verification>
1. Enable WP_DEBUG and WP_DEBUG_LOG in wp-config.php
2. Clear debug.log
3. Load any WordPress page (frontend or admin)
4. Check debug.log - should NOT contain "saman-seo was called incorrectly" or "_load_textdomain_just_in_time"
5. Verify admin menu and settings still display correctly (translations work in admin_menu hook)
</verification>

<success_criteria>
- No WordPress 6.7+ textdomain warnings in debug log
- Plugin functionality unchanged
- Admin UI labels still translated (via admin_menu hook)
- Schema registration works correctly
</success_criteria>

<output>
After completion, create `.planning/quick/002-fix-textdomain-timing/002-SUMMARY.md`
</output>
