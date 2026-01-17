# Phase 7: Final Testing & Cleanup — Plan 1

> GSD Workflow | Mode: yolo | Depth: standard

## Overview

**Goal:** Verify everything works, clean build

This is the final verification phase to ensure the full rebrand (Phases 1-6) was successful. We'll validate PHP syntax, check for any remaining old prefixes, test activation/deactivation, verify feature toggles, and prepare a clean release build.

## Tasks

### Task 1: Validate PHP syntax across all files
Run PHP linter on all plugin PHP files to catch any syntax errors from the refactoring.
```bash
find includes/ -name "*.php" -exec php -l {} \; 2>&1 | grep -v "No syntax errors"
php -l saman-labs-seo.php
```

### Task 2: Check for remaining old prefixes
Search for any lingering old brand references that should have been renamed:
- `wpseopilot_` (lowercase with underscore)
- `WPSEOPILOT_` (uppercase constants)
- `WPSEOPilot` (class/namespace references)
- `wp-seo-pilot` (slug references in strings)

### Task 3: Verify plugin activation works
Test that the plugin activates without errors:
- Check activation hook fires correctly
- Verify database tables are created
- Verify default options are set
- Check no PHP errors in error log

### Task 4: Verify plugin deactivation works
Test that the plugin deactivates cleanly:
- Deactivation hook fires correctly
- No orphaned hooks or scheduled events

### Task 5: Test feature toggles work correctly
Verify the module_enabled() system from Phase 6:
- Toggle a module OFF in settings
- Verify the service doesn't boot
- Verify the menu is hidden
- Toggle module ON
- Verify service boots and menu appears

### Task 6: Test AI graceful degradation
Verify plugin works without Saman Labs AI installed:
- AI features show appropriate admin notice
- No fatal errors when AI functions are called
- Plugin remains fully functional for non-AI features

### Task 7: Remove dead code and cleanup
Identify and remove:
- Unused/commented-out code blocks
- Legacy files no longer referenced
- Debug/test code that shouldn't ship
- The `test-analytics.php` file in root (if test-only)

### Task 8: Final asset build
Rebuild all frontend assets:
```bash
npm run build
```
Verify all built files are current and match source.

### Task 9: Update version number
Bump version to 2.0.0 to signify the major rebrand:
- Update `SAMANLABS_SEO_VERSION` constant
- Update plugin header Version field
- Update package.json version

### Task 10: Final verification
Run comprehensive sanity checks:
- Plugin can be activated fresh
- Admin pages load without errors
- REST API endpoints respond correctly
- No console errors in browser

## Commit Strategy

Use prefix `cleanup(7-1):` for all commits in this phase.

Example commits:
- `cleanup(7-1): remove dead code and test files`
- `cleanup(7-1): bump version to 2.0.0`
- `cleanup(7-1): final asset build`

## Success Criteria

- [ ] All PHP files pass syntax check
- [ ] No old prefixes remain (wpseopilot_, WPSEOPILOT_, WPSEOPilot)
- [ ] Plugin activates/deactivates without errors
- [ ] All feature toggles work correctly
- [ ] AI graceful degradation works
- [ ] Dead code removed
- [ ] Assets rebuilt
- [ ] Version bumped to 2.0.0
- [ ] Plugin fully functional

## Files to Modify

- `saman-labs-seo.php` — Version bump
- `package.json` — Version bump
- `test-analytics.php` — Delete (if test-only)
- Various files — Dead code removal as needed

---
*Plan created: 2026-01-16*
