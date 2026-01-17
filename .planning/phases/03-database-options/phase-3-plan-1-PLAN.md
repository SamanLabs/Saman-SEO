# Phase 3: Database & Options Rebrand - Plan 1

## Objective
Rename all database table references, option keys, post meta keys, and transient names from `wpseopilot_` to `samanlabs_seo_`.

## Scope

### Database Table References (~9 tables)
Tables defined via `$wpdb->prefix . 'wpseopilot_*'`:
- `wpseopilot_redirects` → `samanlabs_seo_redirects`
- `wpseopilot_404_log` → `samanlabs_seo_404_log`
- `wpseopilot_404_ignore_patterns` → `samanlabs_seo_404_ignore_patterns`
- `wpseopilot_link_health` → `samanlabs_seo_link_health`
- `wpseopilot_link_scans` → `samanlabs_seo_link_scans`
- `wpseopilot_indexnow_log` → `samanlabs_seo_indexnow_log`
- `wpseopilot_custom_assistants` → `samanlabs_seo_custom_assistants`
- `wpseopilot_assistant_usage` → `samanlabs_seo_assistant_usage`
- `wpseopilot_custom_models` → `samanlabs_seo_custom_models`

### Option Keys (~1259 occurrences across 81 files)
Pattern: `wpseopilot_*` → `samanlabs_seo_*`
- All `get_option('wpseopilot_*')` calls
- All `update_option('wpseopilot_*')` calls
- All `delete_option('wpseopilot_*')` calls
- Option constants in class files

### Post Meta Keys (~57 occurrences across 17 files)
Pattern: `_wpseopilot_*` → `_samanlabs_seo_*`
- `_wpseopilot_meta` → `_samanlabs_seo_meta`
- `_wpseopilot_title` → `_samanlabs_seo_title`
- `_wpseopilot_description` → `_samanlabs_seo_description`
- `_wpseopilot_breadcrumb_override` → `_samanlabs_seo_breadcrumb_override`
- `_wpseopilot_primary_category` → `_samanlabs_seo_primary_category`
- `_wpseopilot_gtin`, `_wpseopilot_mpn`, `_wpseopilot_brand`, `_wpseopilot_condition`

### Transient Names
- `wpseopilot_audit_results` → `samanlabs_seo_audit_results`
- `wpseopilot_dashboard_data` → `samanlabs_seo_dashboard_data`
- `wpseopilot_dashboard_seo_score` → `samanlabs_seo_dashboard_seo_score`
- `wpseopilot_content_coverage` → `samanlabs_seo_content_coverage`
- `wpseopilot_sitemap_stats` → `samanlabs_seo_sitemap_stats`
- `wpseopilot_slug_changed_*` → `samanlabs_seo_slug_changed_*`
- `wpseopilot_links_notices` → `samanlabs_seo_links_notices`

---

## Tasks

### Task 1: Update database table name references
**Files:** 10 files with `$wpdb->prefix . 'wpseopilot_*'`

Use sed to replace all table name definitions:
```
$wpdb->prefix . 'wpseopilot_ → $wpdb->prefix . 'samanlabs_seo_
```

Files to update:
- `includes/class-samanlabs-seo-service-cli.php`
- `includes/Api/class-assistants-controller.php`
- `includes/Api/class-dashboard-controller.php`
- `includes/Api/class-indexnow-controller.php`
- `includes/Api/class-redirects-controller.php`
- `includes/Api/class-tools-controller.php`
- `includes/class-samanlabs-seo-service-dashboard-widget.php`
- `includes/class-samanlabs-seo-service-link-health.php`
- `includes/class-samanlabs-seo-service-redirect-manager.php`
- `includes/class-samanlabs-seo-service-request-monitor.php`
- `includes/class-samanlabs-seo-service-indexnow.php`

**Commit:** `refactor(3-1): rename database table references to samanlabs_seo prefix`

---

### Task 2: Update option key references
**Files:** All files with `wpseopilot_` option keys

Replace all option key patterns:
```
'wpseopilot_ → 'samanlabs_seo_
"wpseopilot_ → "samanlabs_seo_
```

This covers:
- `get_option()` calls
- `update_option()` calls
- `delete_option()` calls
- Option constants (e.g., `OPTION_RULES = 'wpseopilot_*'`)
- Settings error keys
- Filter names containing option keys

**Commit:** `refactor(3-1): rename option keys to samanlabs_seo prefix`

---

### Task 3: Update post meta key references
**Files:** 17 files with `_wpseopilot_` meta keys

Replace all post meta key patterns:
```
'_wpseopilot_ → '_samanlabs_seo_
"_wpseopilot_ → "_samanlabs_seo_
```

**Commit:** `refactor(3-1): rename post meta keys to samanlabs_seo prefix`

---

### Task 4: Update transient names
**Files:** Files using `wpseopilot_*` transients

Replace transient name patterns (already covered by Task 2 if they use `'wpseopilot_` pattern).

Verify all transient calls use new naming.

**Commit:** Included in Task 2 commit (same pattern)

---

### Task 5: Verify no old references remain
**Verification steps:**
1. Grep for `wpseopilot_` in PHP files (should return 0 except .planning/)
2. Grep for `_wpseopilot_` in PHP files (should return 0)
3. Grep for `'wpseopilot` pattern (should return 0 except .planning/)

**Commit:** Not needed (verification only)

---

## Verification

After all changes:
```bash
# Should return 0 matches (except .planning/ docs)
grep -r "wpseopilot_" --include="*.php" . | grep -v ".planning"

# Should return 0 matches
grep -r "_wpseopilot_" --include="*.php" .
```

---

## Success Criteria

- [ ] All database table name references updated (9 tables)
- [ ] All option key references updated (~1259 occurrences)
- [ ] All post meta key references updated (~57 occurrences)
- [ ] All transient names updated
- [ ] Grep confirms no old `wpseopilot_` references in PHP files

---

## Output

- Updated PHP files with new naming convention
- 3 commits with clear messages
- Ready for Phase 4 (REST API & Frontend Rebrand)

---

## Note

Since user can reinstall fresh, we do NOT need:
- Database migration scripts
- Option migration scripts
- Backward compatibility for old data
