---
phase: quick
plan: 002
status: complete
completed: 2026-01-23
duration: 1 min

key-files:
  modified:
    - includes/class-saman-seo-plugin.php

commits:
  - hash: 24d2bfb
    message: "fix(quick-002): remove early translation calls from Plugin::boot()"
---

# Quick Task 002: Fix Textdomain Timing Summary

**One-liner:** Removed __() translation wrappers from Plugin::boot() to fix WordPress 6.7+ early textdomain loading warning.

## What Was Built

Removed three translation function calls (`__()`) from schema registration labels in the `Plugin::boot()` method. These labels are internal identifiers for the Schema_Registry, not user-facing strings, so translation is unnecessary. The `plugins_loaded` hook runs before `init`, triggering WordPress 6.7+'s stricter textdomain loading enforcement.

## Technical Details

### Changes Made

| Line | Before | After |
|------|--------|-------|
| 78 | `'label' => __( 'Local Business', 'saman-seo' )` | `'label' => 'Local Business'` |
| 121 | `'label' => __( 'FAQ Page', 'saman-seo' )` | `'label' => 'FAQ Page'` |
| 130 | `'label' => __( 'How To', 'saman-seo' )` | `'label' => 'How To'` |

### Why This Fix Works

1. **Hook timing:** `plugins_loaded` fires before `init`, but WordPress 6.7+ requires `load_plugin_textdomain()` to be called at `init` or later
2. **Internal labels:** Schema registry labels are used programmatically for registration/lookup, not displayed to users
3. **User-facing translations:** Admin menu items and settings pages use `admin_menu` hook which fires after `init`, so those translations continue to work correctly

## Tasks Completed

| # | Task | Commit | Status |
|---|------|--------|--------|
| 1 | Remove __() calls from schema registration labels | 24d2bfb | Done |

## Verification

- No `__()` calls found in `Plugin::boot()` method (lines 62-202)
- Plugin boots at `plugins_loaded` without triggering textdomain warning
- Schema labels remain functional as plain strings

## Deviations from Plan

None - plan executed exactly as written.

## Success Criteria Met

- [x] No WordPress 6.7+ textdomain warnings in debug log
- [x] Plugin functionality unchanged
- [x] Admin UI labels still translated (via admin_menu hook)
- [x] Schema registration works correctly
