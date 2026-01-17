# Phase 4: REST API & Frontend Rebrand - Plan 1

## Objective
Update REST API namespace, admin menu slugs, CSS class prefixes, and rebuild all frontend assets.

## Scope

### REST API Namespace (7 occurrences in PHP)
- `wpseopilot/v2` → `samanlabs-seo/v1`

### Admin Menu Slugs (~56 occurrences in PHP)
- `wpseopilot` → `samanlabs-seo`
- `wpseopilot-dashboard` → `samanlabs-seo-dashboard`
- All subpage slugs

### JavaScript API Paths (~164 occurrences across 57 JS files)
**Source files (need updating):**
- `src-v2/` (26 JS files)
- `src-v2/editor/` (Gutenberg editor panel)

**Built files (will be regenerated):**
- `build-v2/` (React app build)
- `build-editor/` (Gutenberg editor build)
- `build-admin-list/` (Admin list build)

### CSS Classes (~1310 occurrences across 23 files)
**Source LESS files (need updating):**
- `assets/less/` (13 files)
- `src-v2/less/` (27 files)

**Built CSS files (will be regenerated):**
- `assets/css/` (7 files)
- Build directories

### HTML Element IDs/Classes in PHP Templates
- Various PHP templates with `wpseopilot-*` identifiers

---

## Tasks

### Task 1: Update REST API namespace in PHP
**Files:** 7 PHP files with REST namespace

Replace:
```
wpseopilot/v2 → samanlabs-seo/v1
```

Files:
- `includes/Api/class-rest-controller.php` (base class)
- `includes/Api/class-breadcrumbs-controller.php`
- `includes/Api/class-htaccess-controller.php`
- `includes/Api/class-link-health-controller.php`
- `includes/Api/class-mobile-test-controller.php`
- `includes/Api/class-schema-validator-controller.php`
- `includes/class-samanlabs-seo-admin-v2.php`

**Commit:** `refactor(4-1): update REST API namespace to samanlabs-seo/v1`

---

### Task 2: Update admin menu slugs in PHP
**Files:** PHP files with admin menu slugs

Replace all patterns:
```
'wpseopilot' → 'samanlabs-seo'
'wpseopilot-dashboard' → 'samanlabs-seo-dashboard'
... (all admin page slugs)
```

Key files:
- `includes/class-samanlabs-seo-admin-v2.php` (MENU_SLUG constant, page mappings)
- `includes/class-samanlabs-seo-service-admin-ui.php`
- `includes/class-samanlabs-seo-service-admin-bar.php`
- Template files with menu slug references

**Commit:** `refactor(4-1): update admin menu slugs to samanlabs-seo prefix`

---

### Task 3: Update REST API paths in JavaScript source
**Files:** 26+ JS files in `src-v2/`

Replace:
```
wpseopilot/v2 → samanlabs-seo/v1
```

Files include:
- `src-v2/App.js`
- `src-v2/pages/*.js` (17 files)
- `src-v2/hooks/*.js`
- `src-v2/components/*.js`
- `src-v2/assistants/*.js`
- `src-v2/editor/*.js`

**Commit:** `refactor(4-1): update REST API paths in JavaScript source`

---

### Task 4: Update CSS class prefixes in LESS source
**Files:** ~40 LESS files

Replace:
```
.wpseopilot- → .samanlabs-seo-
wpseopilot- → samanlabs-seo- (in ID selectors)
```

Directories:
- `assets/less/` (13 files)
- `src-v2/less/` (27 files)

**Commit:** `refactor(4-1): update CSS class prefixes in LESS source`

---

### Task 5: Update HTML identifiers in PHP templates
**Files:** PHP templates with HTML class/id attributes

Replace:
```
class="wpseopilot- → class="samanlabs-seo-
id="wpseopilot- → id="samanlabs-seo-
```

Template directories:
- `templates/`
- `templates/components/`
- `templates/partials/`

**Commit:** `refactor(4-1): update HTML identifiers in PHP templates`

---

### Task 6: Update remaining PHP identifiers
**Files:** PHP files with misc identifiers

Replace all remaining:
```
'wpseopilot → 'samanlabs-seo
wpseopilot- → samanlabs-seo-
```

This catches:
- Script/style handles
- Localization object names
- Filter/hook prefixes without underscore

**Commit:** `refactor(4-1): update remaining PHP identifiers`

---

### Task 7: Rebuild all frontend assets
**Commands:**
```bash
npm run build
```

This regenerates:
- `build-v2/` (React admin app)
- `build-editor/` (Gutenberg editor panel)
- `build-admin-list/` (Admin list enhancements)
- `assets/css/` (Compiled LESS)

**Note:** If build fails, fix issues and rebuild.

**Commit:** `chore(4-1): rebuild frontend assets with new branding`

---

## Verification

After all changes:
1. Grep for `wpseopilot` in PHP/JS/LESS files (should return 0)
2. Verify builds complete successfully
3. Check no broken references remain

```bash
# PHP files
grep -r "wpseopilot" --include="*.php" . | grep -v ".planning"

# JS source files
grep -r "wpseopilot" --include="*.js" src-v2/

# LESS source files
grep -r "wpseopilot" --include="*.less" assets/less/ src-v2/less/
```

---

## Success Criteria

- [ ] REST API namespace changed to `samanlabs-seo/v1`
- [ ] Admin menu slugs changed to `samanlabs-seo-*`
- [ ] All JS source files updated
- [ ] All LESS source files updated
- [ ] All PHP templates updated
- [ ] Frontend builds complete successfully
- [ ] No `wpseopilot` references remain in source files

---

## Output

- Updated PHP, JS, and LESS source files
- Rebuilt frontend assets
- 7 commits with clear messages
- Ready for Phase 5 (AI Integration Refactor)
