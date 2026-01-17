# Saman Labs SEO — Roadmap

> Milestone 1: Full rebrand and architecture refactor

## Phases

### Phase 1: Core Bootstrap Rebrand
**Goal:** Rename main plugin file, constants, and namespace foundation

- Rename `wp-seo-pilot.php` → `saman-labs-seo.php`
- Update plugin header metadata
- Rename constants `WPSEOPILOT_*` → `SAMANLABS_SEO_*`
- Update root namespace `WPSEOPilot` → `SamanLabs\SEO`
- Update autoloader paths and mappings

**Research needed:** No — straightforward file/string changes

---

### Phase 2: PHP Classes & Services Rebrand
**Goal:** Rename all PHP class files and update internal references

- Rename all `class-wpseopilot-*` files → `class-samanlabs-seo-*`
- Update class names in service layer
- Update API controller namespaces
- Update integration class names
- Update all `use` statements and internal references

**Research needed:** No — systematic find-replace with verification

---

### Phase 3: Database & Options Rebrand
**Goal:** Rename all database tables, options, and post meta keys

- Rename tables: `wp_wpseopilot_*` → `wp_samanlabs_seo_*`
- Rename options: `wpseopilot_*` → `samanlabs_seo_*`
- Rename post meta: `_wpseopilot_meta` → `_samanlabs_seo_meta`
- Update all database queries and option references

**Research needed:** No — systematic changes, can reinstall fresh

---

### Phase 4: REST API & Frontend Rebrand
**Goal:** Update REST namespace, CSS classes, hooks, and React paths

- Change REST namespace: `wpseopilot/v2` → `samanlabs-seo/v1`
- Update all React `apiFetch` paths
- Rename CSS classes: `.wpseopilot-*` → `.samanlabs-seo-*`
- Update LESS source files
- Rename hooks: `wpseopilot_*` → `samanlabs_seo_*`
- Rebuild all assets

**Research needed:** No — systematic changes

---

### Phase 5: AI Integration Refactor
**Goal:** Remove built-in AI, delegate to Saman Labs AI plugin

- Remove OpenAI API client code
- Remove Anthropic API client code
- Remove Ollama API client code
- Rename integration: `WP_AI_Pilot` → `Saman_Labs_AI`
- Update integration hooks and action names
- Implement graceful degradation when AI plugin not installed
- Add admin notice prompting to install Saman Labs AI

**Research needed:** Yes — need to understand current AI integration flow

---

### Phase 6: Feature Toggle Fix
**Goal:** Ensure feature toggles actually disable features

- Audit current toggle implementation (find why it doesn't work)
- Implement conditional hook registration based on toggles
- Implement conditional asset loading based on toggles
- Hide UI for disabled features
- Verify each toggle works correctly

**Research needed:** Yes — need to understand current toggle flow

---

### Phase 7: Final Testing & Cleanup
**Goal:** Verify everything works, clean build

- Full plugin activation/deactivation test
- Test all SEO features work
- Test AI graceful degradation
- Test all feature toggles
- Remove any dead code
- Final asset build
- Update version number

**Research needed:** No — verification phase

---

## Summary

| Phase | Name | Research | Complexity |
|-------|------|----------|------------|
| 1 | Core Bootstrap Rebrand | No | Low |
| 2 | PHP Classes & Services | No | Medium |
| 3 | Database & Options | No | Medium |
| 4 | REST API & Frontend | No | Medium |
| 5 | AI Integration Refactor | Yes | High |
| 6 | Feature Toggle Fix | Yes | Medium |
| 7 | Final Testing & Cleanup | No | Low | ✅ |

---
*Created: 2026-01-16*
