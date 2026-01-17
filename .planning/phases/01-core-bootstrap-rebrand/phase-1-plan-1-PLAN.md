# Phase 1: Core Bootstrap Rebrand

> Rename main plugin file, constants, and namespace foundation

## Objective

Transform the plugin bootstrap from Saman SEO to Saman Labs SEO by renaming the main plugin file, updating all constants, and changing the root namespace throughout the codebase.

## Context

**Current state:**
- Main file: `wp-seo-pilot.php`
- Constants: `WPSEOPILOT_VERSION`, `WPSEOPILOT_PATH`, `WPSEOPILOT_URL`
- Namespace: `WPSEOPilot`
- Text domain: `wp-seo-pilot`

**Target state:**
- Main file: `saman-labs-seo.php`
- Constants: `SAMANLABS_SEO_VERSION`, `SAMANLABS_SEO_PATH`, `SAMANLABS_SEO_URL`
- Namespace: `SamanLabs\SEO`
- Text domain: `saman-labs-seo`

**Scope:**
- 149 constant usages across 25 files
- 73 files with namespace declarations/references

## Tasks

### Task 1: Rename main plugin file and update header

**Files:** `wp-seo-pilot.php` → `saman-labs-seo.php`

1. Rename file `wp-seo-pilot.php` to `saman-labs-seo.php`
2. Update plugin header:
   - Plugin Name: `Saman Labs SEO`
   - Plugin URI: Update to new repo if applicable
   - Description: Keep similar, update branding
   - Text Domain: `saman-labs-seo`
   - @package: `SamanLabs\SEO`

**Verification:** File exists with correct header metadata

---

### Task 2: Rename constants

**Files:** All files containing `WPSEOPILOT_`

1. In `saman-labs-seo.php`, change constant definitions:
   - `WPSEOPILOT_VERSION` → `SAMANLABS_SEO_VERSION`
   - `WPSEOPILOT_PATH` → `SAMANLABS_SEO_PATH`
   - `WPSEOPILOT_URL` → `SAMANLABS_SEO_URL`

2. Search and replace all usages across codebase:
   - `WPSEOPILOT_VERSION` → `SAMANLABS_SEO_VERSION`
   - `WPSEOPILOT_PATH` → `SAMANLABS_SEO_PATH`
   - `WPSEOPILOT_URL` → `SAMANLABS_SEO_URL`

**Verification:** `grep -r "WPSEOPILOT_" includes/` returns no results

---

### Task 3: Update namespace declarations

**Files:** All 73 files with `namespace WPSEOPilot`

1. Replace all namespace declarations:
   - `namespace WPSEOPilot;` → `namespace SamanLabs\SEO;`
   - `namespace WPSEOPilot\Api;` → `namespace SamanLabs\SEO\Api;`
   - `namespace WPSEOPilot\Service;` → `namespace SamanLabs\SEO\Service;`
   - `namespace WPSEOPilot\Integration;` → `namespace SamanLabs\SEO\Integration;`
   - `namespace WPSEOPilot\Updater;` → `namespace SamanLabs\SEO\Updater;`
   - `namespace WPSEOPilot\Api\Assistants;` → `namespace SamanLabs\SEO\Api\Assistants;`
   - `namespace WPSEOPilot\Helpers;` → `namespace SamanLabs\SEO\Helpers;`
   - `namespace WPSEOPilot\Internal_Linking;` → `namespace SamanLabs\SEO\Internal_Linking;`

**Verification:** `grep -r "namespace WPSEOPilot" includes/` returns no results

---

### Task 4: Update namespace references (use statements and fully qualified names)

**Files:** All files with `WPSEOPilot\\` or `\WPSEOPilot\`

1. Replace all `use` statements:
   - `use WPSEOPilot\` → `use SamanLabs\SEO\`

2. Replace all fully qualified class references:
   - `\WPSEOPilot\` → `\SamanLabs\SEO\`
   - `WPSEOPilot\\` → `SamanLabs\\SEO\\`

3. Update string references in autoloader:
   - `'WPSEOPilot\\'` → `'SamanLabs\\SEO\\'`

**Verification:** `grep -r "WPSEOPilot" includes/` returns no results (except comments if any)

---

### Task 5: Update autoloader for new namespace

**File:** `saman-labs-seo.php`

1. Update autoloader namespace prefix check:
   - `strpos( $class, 'WPSEOPilot\\' )` → `strpos( $class, 'SamanLabs\\SEO\\' )`

2. Update all namespace-specific path mappings:
   - `WPSEOPilot\\Api\\` → `SamanLabs\\SEO\\Api\\`
   - `WPSEOPilot\\Integration\\` → `SamanLabs\\SEO\\Integration\\`
   - `WPSEOPilot\\Service\\` → `SamanLabs\\SEO\\Service\\`

3. Update file path patterns in autoloader to use new constant:
   - `WPSEOPILOT_PATH` → `SAMANLABS_SEO_PATH`

**Verification:** Plugin loads without autoloader errors

---

### Task 6: Update text domain

**Files:** All files with `'wp-seo-pilot'` text domain

1. Search and replace text domain in all translation functions:
   - `'wp-seo-pilot'` → `'saman-labs-seo'`

**Verification:** `grep -r "'wp-seo-pilot'" includes/` returns no results

---

## Verification

After completing all tasks:

1. **Syntax check:** `php -l saman-labs-seo.php` - no errors
2. **Constant check:** `grep -r "WPSEOPILOT_" includes/ templates/` - no results
3. **Namespace check:** `grep -r "WPSEOPilot" includes/` - no results (except docs)
4. **Text domain check:** `grep -r "'wp-seo-pilot'" includes/ templates/` - no results
5. **File exists:** `saman-labs-seo.php` exists, `wp-seo-pilot.php` does not

## Success Criteria

- [ ] Main plugin file renamed to `saman-labs-seo.php`
- [ ] Plugin header updated with new branding
- [ ] All 3 constants renamed (`SAMANLABS_SEO_*`)
- [ ] All namespace declarations updated (`SamanLabs\SEO`)
- [ ] All namespace references updated
- [ ] Autoloader works with new namespace
- [ ] Text domain changed to `saman-labs-seo`
- [ ] PHP syntax valid (no parse errors)

## Output

- Renamed main plugin file
- Updated constants across 25+ files
- Updated namespaces across 73 files
- Plugin ready for Phase 2 (class file renames)

---
*Created: 2026-01-16*
