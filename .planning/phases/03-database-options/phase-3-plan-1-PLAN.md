# Phase 3: Database & Options Rebrand - Plan 1

## Objective
Rename all database table references, option keys, post meta keys, and transient names from `samanseo_` to `samanlabs_seo_`.

## Scope

### Database Table References (~9 tables)
Tables defined via `$wpdb->prefix . 'samanseo_*'`:
- `samanseo_redirects` → `samanlabs_seo_redirects`
- `samanseo_404_log` → `samanlabs_seo_404_log`
- `samanseo_404_ignore_patterns` → `samanlabs_seo_404_ignore_patterns`
- `samanseo_link_health` → `samanlabs_seo_link_health`
- `samanseo_link_scans` → `samanlabs_seo_link_scans`
- `samanseo_indexnow_log` → `samanlabs_seo_indexnow_log`
- `samanseo_custom_assistants` → `samanlabs_seo_custom_assistants`
- `samanseo_assistant_usage` → `samanlabs_seo_assistant_usage`
- `samanseo_custom_models` → `samanlabs_seo_custom_models`

### Option Keys (~1259 occurrences across 81 files)
Pattern: `samanseo_*` → `samanlabs_seo_*`
- All `get_option('samanseo_*')` calls
- All `update_option('samanseo_*')` calls
- All `delete_option('samanseo_*')` calls
- Option constants in class files

### Post Meta Keys (~57 occurrences across 17 files)
Pattern: `_samanseo_*` → `_samanlabs_seo_*`
- `_samanseo_meta` → `_samanlabs_seo_meta`
- `_samanseo_title` → `_samanlabs_seo_title`
- `_samanseo_description` → `_samanlabs_seo_description`
- `_samanseo_breadcrumb_override` → `_samanlabs_seo_breadcrumb_override`
- `_samanseo_primary_category` → `_samanlabs_seo_primary_category`
- `_samanseo_gtin`, `_samanseo_mpn`, `_samanseo_brand`, `_samanseo_condition`

### Transient Names
- `samanseo_audit_results` → `samanlabs_seo_audit_results`
- `samanseo_dashboard_data` → `samanlabs_seo_dashboard_data`
- `samanseo_dashboard_seo_score` → `samanlabs_seo_dashboard_seo_score`
- `samanseo_content_coverage` → `samanlabs_seo_content_coverage`
- `samanseo_sitemap_stats` → `samanlabs_seo_sitemap_stats`
- `samanseo_slug_changed_*` → `samanlabs_seo_slug_changed_*`
- `samanseo_links_notices` → `samanlabs_seo_links_notices`

---

## Tasks

### Task 1: Update database table name references
**Files:** 10 files with `$wpdb->prefix . 'samanseo_*'`

Use sed to replace all table name definitions:
```
$wpdb->prefix . 'samanseo_ → $wpdb->prefix . 'samanlabs_seo_
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
**Files:** All files with `samanseo_` option keys

Replace all option key patterns:
```
'samanseo_ → 'samanlabs_seo_
"samanseo_ → "samanlabs_seo_
```

This covers:
- `get_option()` calls
- `update_option()` calls
- `delete_option()` calls
- Option constants (e.g., `OPTION_RULES = 'samanseo_*'`)
- Settings error keys
- Filter names containing option keys

**Commit:** `refactor(3-1): rename option keys to samanlabs_seo prefix`

---

### Task 3: Update post meta key references
**Files:** 17 files with `_samanseo_` meta keys

Replace all post meta key patterns:
```
'_samanseo_ → '_samanlabs_seo_
"_samanseo_ → "_samanlabs_seo_
```

**Commit:** `refactor(3-1): rename post meta keys to samanlabs_seo prefix`

---

### Task 4: Update transient names
**Files:** Files using `samanseo_*` transients

Replace transient name patterns (already covered by Task 2 if they use `'samanseo_` pattern).

Verify all transient calls use new naming.

**Commit:** Included in Task 2 commit (same pattern)

---

### Task 5: Verify no old references remain
**Verification steps:**
1. Grep for `samanseo_` in PHP files (should return 0 except .planning/)
2. Grep for `_samanseo_` in PHP files (should return 0)
3. Grep for `'samanseo` pattern (should return 0 except .planning/)

**Commit:** Not needed (verification only)

---

## Verification

After all changes:
```bash
# Should return 0 matches (except .planning/ docs)
grep -r "samanseo_" --include="*.php" . | grep -v ".planning"

# Should return 0 matches
grep -r "_samanseo_" --include="*.php" .
```

---

## Success Criteria

- [ ] All database table name references updated (9 tables)
- [ ] All option key references updated (~1259 occurrences)
- [ ] All post meta key references updated (~57 occurrences)
- [ ] All transient names updated
- [ ] Grep confirms no old `samanseo_` references in PHP files

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
