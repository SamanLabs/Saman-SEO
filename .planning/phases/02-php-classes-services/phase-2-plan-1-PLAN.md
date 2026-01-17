# Phase 2: PHP Classes & Services Rebrand - Plan 1

## Objective
Rename all PHP class files from `class-wpseopilot-*` to `class-samanlabs-seo-*` pattern and verify autoloader compatibility.

## Scope

**Total files to rename:** 42 files

### includes/ directory (33 files)
Files with `class-wpseopilot-*` prefix:
- class-wpseopilot-plugin.php
- class-wpseopilot-admin-topbar.php
- class-wpseopilot-admin-v2.php
- class-wpseopilot-internal-linking-engine.php
- class-wpseopilot-internal-linking-repository.php
- class-wpseopilot-service-admin-bar.php
- class-wpseopilot-service-admin-ui.php
- class-wpseopilot-service-ai-assistant.php
- class-wpseopilot-service-analytics.php
- class-wpseopilot-service-audit.php
- class-wpseopilot-service-breadcrumbs.php
- class-wpseopilot-service-cli.php
- class-wpseopilot-service-compatibility.php
- class-wpseopilot-service-dashboard-widget.php
- class-wpseopilot-service-frontend.php
- class-wpseopilot-service-importers.php
- class-wpseopilot-service-indexnow.php
- class-wpseopilot-service-internal-linking.php
- class-wpseopilot-service-jsonld.php
- class-wpseopilot-service-link-health.php
- class-wpseopilot-service-llm-txt-generator.php
- class-wpseopilot-service-local-seo.php
- class-wpseopilot-service-post-meta.php
- class-wpseopilot-service-redirect-manager.php
- class-wpseopilot-service-request-monitor.php
- class-wpseopilot-service-robots-manager.php
- class-wpseopilot-service-schema-blocks.php
- class-wpseopilot-service-settings.php
- class-wpseopilot-service-sitemap-enhancer.php
- class-wpseopilot-service-sitemap-settings.php
- class-wpseopilot-service-social-card-generator.php
- class-wpseopilot-service-social-settings.php
- class-wpseopilot-service-video-schema.php

### includes/Service/ directory (9 files)
Files with `class-wpseopilot-service-*` prefix:
- class-wpseopilot-service-book-schema.php
- class-wpseopilot-service-course-schema.php
- class-wpseopilot-service-job-posting-schema.php
- class-wpseopilot-service-movie-schema.php
- class-wpseopilot-service-music-schema.php
- class-wpseopilot-service-restaurant-schema.php
- class-wpseopilot-service-service-schema.php
- class-wpseopilot-service-software-schema.php
- class-wpseopilot-service-video-schema.php

### Files NOT needing rename (already generic)
- includes/Api/*.php (19 files) - use `class-*-controller.php` pattern
- includes/Api/Assistants/*.php (3 files) - use `class-*-assistant.php` pattern
- includes/Integration/*.php (2 files) - use `class-*.php` pattern
- includes/Updater/*.php (2 files) - use `class-*.php` pattern

---

## Tasks

### Task 1: Rename core class files in includes/
**Files:** 5 non-service files in includes/

Use `git mv` to rename:
```
class-wpseopilot-plugin.php → class-samanlabs-seo-plugin.php
class-wpseopilot-admin-topbar.php → class-samanlabs-seo-admin-topbar.php
class-wpseopilot-admin-v2.php → class-samanlabs-seo-admin-v2.php
class-wpseopilot-internal-linking-engine.php → class-samanlabs-seo-internal-linking-engine.php
class-wpseopilot-internal-linking-repository.php → class-samanlabs-seo-internal-linking-repository.php
```

**Commit:** `refactor(2-1): rename core class files to samanlabs-seo prefix`

---

### Task 2: Rename service class files in includes/
**Files:** 28 service files in includes/

Use `git mv` to rename all `class-wpseopilot-service-*` files to `class-samanlabs-seo-service-*`.

**Commit:** `refactor(2-1): rename service class files to samanlabs-seo prefix`

---

### Task 3: Rename service class files in includes/Service/
**Files:** 9 service files in includes/Service/

Use `git mv` to rename all `class-wpseopilot-service-*` files to `class-samanlabs-seo-service-*`.

**Commit:** `refactor(2-1): rename Service directory files to samanlabs-seo prefix`

---

### Task 4: Verify autoloader works with new file names
**File:** saman-labs-seo.php

The autoloader was updated in Phase 1 to support both naming patterns. Verify:
1. New naming convention candidates are searched first
2. Legacy fallback patterns are still present (can be removed in Phase 7)

**Verification steps:**
- Check autoloader has `class-samanlabs-seo-*` patterns as primary
- Confirm no explicit file path references exist that use old names

**Commit:** Not needed if autoloader already correct

---

### Task 5: Clean up any explicit file references
**Search:** Look for any hardcoded references to old file names

Search patterns:
- `class-wpseopilot-` in require/include statements
- `wpseopilot-` in file path strings

Update any found references to use new names.

**Commit:** `refactor(2-1): update any explicit file path references` (if changes needed)

---

## Verification

After all renames:
1. `git status` shows all renames tracked
2. No references to old file names remain in PHP files
3. Grep for `class-wpseopilot-` returns only matches in .planning/ docs

---

## Success Criteria

- [ ] All 42 files renamed from `class-wpseopilot-*` to `class-samanlabs-seo-*`
- [ ] Git history preserved via `git mv`
- [ ] No broken autoload references
- [ ] Grep confirms no old file name references in code

---

## Output

- 42 renamed PHP class files
- 3-5 commits with clear messages
- Ready for Phase 3 (Database & Options Rebrand)
