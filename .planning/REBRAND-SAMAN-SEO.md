# Rebrand: Saman Labs SEO → Saman SEO

> ✅ **COMPLETED** - 2026-01-17

## Scope Analysis

| Pattern | From | To | Est. Occurrences |
|---------|------|-----|------------------|
| Text domain/slugs | `saman-labs-seo` | `saman-seo` | ~500 |
| Option keys | `samanlabs_seo_` | `saman_seo_` | ~2000 |
| CSS classes | `samanlabs-seo-` | `saman-seo-` | ~1000 |
| PHP Namespace | `SamanLabs\SEO` | `Saman\SEO` | ~300 |
| Constants | `SAMANLABS_SEO_` | `SAMAN_SEO_` | ~120 |
| JS variables | `SamanLabsSEO*` | `SamanSEO*` | ~10 |
| REST namespace | `samanlabs-seo/v1` | `saman-seo/v1` | ~50 |
| File names | `class-samanlabs-seo-*` | `class-saman-seo-*` | 42 files |
| Database tables | `samanlabs_seo_*` | `saman_seo_*` | 9 tables |
| Post meta | `_samanlabs_seo_*` | `_saman_seo_*` | ~10 keys |

**Total: ~5,000+ occurrences across ~100 files**

---

## Phases

### Phase 1: Core Bootstrap ✅
**Goal:** Rename constants and namespace foundation

- [x] Rename constants `SAMANLABS_SEO_*` → `SAMAN_SEO_*` in `saman-seo.php`
- [x] Update namespace `SamanLabs\SEO` → `Saman\SEO` in autoloader
- [x] Update `@package` comments

**Files:** 1 (saman-seo.php)

---

### Phase 2: PHP Class Files ✅
**Goal:** Rename all PHP class files and update namespaces

- [x] Rename 42 files: `class-samanlabs-seo-*` → `class-saman-seo-*`
- [x] Update all `namespace SamanLabs\SEO` → `namespace Saman\SEO` (69 files)
- [x] Update all `use SamanLabs\SEO\*` statements (14 files)
- [x] Update autoloader path mappings
- [x] Update `@package` comments (90 files)

**Files:** ~50 PHP files

---

### Phase 3: Database & Options ✅
**Goal:** Rename all option keys, meta keys, table references

- [x] Rename option keys: `SAMANLABS_SEO_*` → `SAMAN_SEO_*` (80 files)
- [x] Rename post meta references
- [x] Rename table references
- [x] Rename hooks
- [x] Rename transients

**Files:** ~60 PHP files

---

### Phase 4: REST API & Frontend ✅
**Goal:** Update REST namespace, CSS classes, JavaScript

- [x] Change REST namespace: `samanlabs-seo/v1` → `saman-seo/v1` (64 files)
- [x] Update all React `apiFetch` paths
- [x] Rename CSS classes: `.samanlabs-seo-*` → `.saman-seo-*` (80 files)
- [x] Update LESS source files
- [x] Update text domain: `saman-labs-seo` → `saman-seo` (73 files)
- [x] Update JS variables: `SamanLabsSEO*` → `SamanSEO*` (21 files)
- [x] Rebuild all assets

**Files:** ~70 JS files, ~12 LESS files

---

### Phase 5: Final Cleanup ✅
**Goal:** Verify, test, update version

- [x] Verify no old patterns remain in source code
- [x] Final asset build (npm run build)
- [x] All bundles compiled successfully

---

## Migration Notes

**Database migration NOT included** - Existing sites will need a migration script to rename:
- Option keys in `wp_options`
- Post meta keys in `wp_postmeta`
- Table names (if any custom tables)

This can be handled via activation hook or separate migration tool.

---

## Estimated Effort

| Phase | Complexity | Files |
|-------|------------|-------|
| 1 | Low | 1 |
| 2 | Medium | 50 |
| 3 | High | 60 |
| 4 | Medium | 80 |
| 5 | Low | - |

---
*Created: 2026-01-16*
