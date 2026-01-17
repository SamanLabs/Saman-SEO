# Phase 6: Feature Toggle Fix - Plan 1

## Objective
Fix feature toggles so they actually disable features. The problem is a disconnect between React UI option keys and PHP service option keys.

## Discovery Findings

### The Core Problem

**React UI saves:**
```javascript
// Settings.js - modules array (lines 430-443)
module_sitemap, module_redirects, module_404_log, module_internal_linking,
module_schema, module_social_cards, module_breadcrumbs, module_llm_txt,
module_local_seo, module_ai_assistant, module_indexnow, module_search_console
```

**PHP services check:**
```php
// Various services (boot methods)
samanlabs_seo_enable_sitemap_enhancer
samanlabs_seo_enable_redirect_manager
samanlabs_seo_enable_404_logging
samanlabs_seo_enable_og_preview
samanlabs_seo_enable_llm_txt
samanlabs_seo_enable_local_seo
samanlabs_seo_enable_analytics
```

**Settings_Controller saves to `samanlabs_seo_module_*`** (line 129)
But services check `samanlabs_seo_enable_*` keys - **THEY DON'T MATCH!**

### Current Partial Solutions
- `sync_breadcrumb_settings()` syncs `module_breadcrumbs` → `breadcrumb_settings['enabled']`
- `sync_indexnow_settings()` syncs `module_indexnow` → `indexnow_settings['enabled']`
- **No sync exists for other modules**

### Service Toggle Behavior Analysis

| Service | Option Key Checked | Default | Notes |
|---------|-------------------|---------|-------|
| Redirect_Manager | `samanlabs_seo_enable_redirect_manager` | '1' | Proper early return |
| Request_Monitor (404) | `samanlabs_seo_enable_404_logging` | '1' | Proper early return |
| Sitemap_Enhancer | `samanlabs_seo_enable_sitemap_enhancer` | '0' | Proper early return |
| LLM_TXT_Generator | `samanlabs_seo_enable_llm_txt` | '0' | Proper early return |
| Local_SEO | `samanlabs_seo_enable_local_seo` | '0' | Proper early return |
| Social_Card_Generator | `samanlabs_seo_enable_og_preview` | '1' | Proper early return |
| Analytics | Uses `is_enabled()` method | - | Checks both module_ and enable_ |
| Admin_Bar | `samanlabs_seo_enable_admin_bar` | true | Uses `true` not '1' |

### Services with NO toggle at all (always boot)
- Compatibility
- Settings
- Post_Meta
- Frontend
- JsonLD
- Admin_UI
- AI_Assistant
- Internal_Linking
- Importers
- Audit
- Sitemap_Settings
- Social_Settings
- Robots_Manager
- Dashboard_Widget
- Link_Health
- Breadcrumbs
- All Schema services (Video, Course, etc.)
- IndexNow (has consolidated settings)
- CLI

---

## Solution Strategy

**Option A: Sync module_ keys to enable_ keys in Settings_Controller**
- Pros: Minimal PHP service changes
- Cons: Two sets of options, potential confusion

**Option B: Update PHP services to check module_ keys directly**
- Pros: Single source of truth, cleaner
- Cons: More files to change

**Recommended: Option B** - Update PHP services to use the `module_*` keys that the React UI saves. This eliminates the dual-option problem.

---

## Tasks

### Task 1: Create feature toggle helper function
**File:** `includes/helpers.php`

Add a centralized helper function:
```php
function samanlabs_seo_module_enabled( string $module ) : bool {
    return '1' === get_option( 'samanlabs_seo_module_' . $module, '1' );
}
```

**Commit:** `refactor(6-1): add samanlabs_seo_module_enabled helper function`

---

### Task 2: Update Redirect_Manager service
**File:** `includes/class-samanlabs-seo-service-redirect-manager.php`

Change:
```php
if ( '1' !== get_option( 'samanlabs_seo_enable_redirect_manager', '1' ) )
```
To:
```php
if ( ! \SamanLabs\SEO\Helpers\samanlabs_seo_module_enabled( 'redirects' ) )
```

**Commit:** `refactor(6-1): update Redirect_Manager to use module toggle`

---

### Task 3: Update Request_Monitor (404 logging) service
**File:** `includes/class-samanlabs-seo-service-request-monitor.php`

Change option check from `samanlabs_seo_enable_404_logging` to `module_404_log`.

**Commit:** `refactor(6-1): update Request_Monitor to use module toggle`

---

### Task 4: Update Sitemap_Enhancer service
**File:** `includes/class-samanlabs-seo-service-sitemap-enhancer.php`

Change option check from `samanlabs_seo_enable_sitemap_enhancer` to `module_sitemap`.

**Commit:** `refactor(6-1): update Sitemap_Enhancer to use module toggle`

---

### Task 5: Update LLM_TXT_Generator service
**File:** `includes/class-samanlabs-seo-service-llm-txt-generator.php`

Change option check from `samanlabs_seo_enable_llm_txt` to `module_llm_txt`.

**Commit:** `refactor(6-1): update LLM_TXT_Generator to use module toggle`

---

### Task 6: Update Local_SEO service
**File:** `includes/class-samanlabs-seo-service-local-seo.php`

Change option check from `samanlabs_seo_enable_local_seo` to `module_local_seo`.

**Commit:** `refactor(6-1): update Local_SEO to use module toggle`

---

### Task 7: Update Social_Card_Generator service
**File:** `includes/class-samanlabs-seo-service-social-card-generator.php`

Change option check from `samanlabs_seo_enable_og_preview` to `module_social_cards`.

**Commit:** `refactor(6-1): update Social_Card_Generator to use module toggle`

---

### Task 8: Update Analytics service
**File:** `includes/class-samanlabs-seo-service-analytics.php`

Simplify `is_enabled()` method to only check `module_analytics`:
```php
public function is_enabled() {
    return '1' === get_option( 'samanlabs_seo_module_analytics', '0' );
}
```

**Commit:** `refactor(6-1): simplify Analytics to use module toggle`

---

### Task 9: Update Admin_Bar service
**File:** `includes/class-samanlabs-seo-service-admin-bar.php`

Add toggle check (currently uses `samanlabs_seo_enable_admin_bar`).
Note: This isn't in React UI modules list - decide whether to add it or keep separate.

**Commit:** `refactor(6-1): update Admin_Bar to use consistent toggle`

---

### Task 10: Add toggles to services that lack them
**Files:** Services that should be toggleable but aren't:

1. **Internal_Linking** - Add `module_internal_linking` check
2. **AI_Assistant** - Add `module_ai_assistant` check
3. **Breadcrumbs** - Already has consolidated settings sync
4. **IndexNow** - Already has consolidated settings sync

For each, add early return in `boot()` method if module is disabled.

**Commit:** `refactor(6-1): add module toggles to Internal_Linking and AI_Assistant`

---

### Task 11: Set default values for new module_ options on activation
**File:** `includes/class-samanlabs-seo-plugin.php`

Update `activate()` to set default values for all `module_*` options:
```php
add_option( 'samanlabs_seo_module_sitemap', '1' );
add_option( 'samanlabs_seo_module_redirects', '1' );
add_option( 'samanlabs_seo_module_404_log', '1' );
add_option( 'samanlabs_seo_module_internal_linking', '1' );
add_option( 'samanlabs_seo_module_schema', '1' );
add_option( 'samanlabs_seo_module_social_cards', '1' );
add_option( 'samanlabs_seo_module_breadcrumbs', '1' );
add_option( 'samanlabs_seo_module_llm_txt', '1' );
add_option( 'samanlabs_seo_module_local_seo', '0' );
add_option( 'samanlabs_seo_module_ai_assistant', '1' );
add_option( 'samanlabs_seo_module_indexnow', '1' );
add_option( 'samanlabs_seo_module_analytics', '0' );
```

**Commit:** `refactor(6-1): add default module option values on activation`

---

### Task 12: Remove deprecated enable_ options from Settings service
**File:** `includes/class-samanlabs-seo-service-settings.php`

Remove the old `samanlabs_seo_enable_*` defaults from the `$defaults` array. Keep the settings registration for backward compatibility but mark as deprecated.

**Commit:** `refactor(6-1): deprecate legacy enable_ options in Settings service`

---

### Task 13: Hide UI for disabled features
**Files:** React UI components

Ensure disabled modules hide their UI:
- Dashboard should show "Enable X module" instead of the feature widget
- Sidebar navigation should hide pages for disabled modules
- Already partially implemented (Search Appearance checks `cardModuleEnabled`)

Verify existing implementations and add where missing.

**Commit:** `refactor(6-1): ensure UI hides disabled module features`

---

### Task 14: Verify all toggles work
**Verification only - no commit**

Test each module toggle:
1. Disable in Settings > Modules
2. Verify service doesn't boot (no hooks registered)
3. Verify UI hides module-specific pages/widgets
4. Re-enable and verify functionality returns

```bash
# Grep to ensure no old option keys are used (except for migration)
grep -r "samanlabs_seo_enable_" --include="*.php" . | grep -v ".planning" | grep -v "migration\|deprecated\|backward"
```

---

## Verification

After all changes:
```bash
# All services should use module_ keys
grep -r "samanlabs_seo_module_" --include="*.php" includes/ | wc -l
# Should show multiple matches

# Old enable_ keys should only appear in deprecated/migration contexts
grep -r "samanlabs_seo_enable_" --include="*.php" includes/ | grep -v "deprecated\|migration"
# Should show minimal matches (only backward compat code)
```

---

## Success Criteria

- [ ] Helper function `samanlabs_seo_module_enabled()` created
- [ ] All toggleable services check `module_*` options
- [ ] Default values set on activation for all modules
- [ ] Internal_Linking has a toggle
- [ ] AI_Assistant has a toggle
- [ ] Old `enable_*` options deprecated
- [ ] UI hides features for disabled modules
- [ ] All toggles verified working

---

## Output

- Updated 10+ PHP files with consistent module toggles
- New helper function in helpers.php
- Updated activation defaults
- 10+ commits with clear messages
- Ready for Phase 7 (Final Testing & Cleanup)

---

## Migration Note

For existing installs, the old `enable_*` options won't automatically migrate to `module_*` options. Two approaches:

1. **Lazy migration:** When `module_*` option doesn't exist, fall back to checking corresponding `enable_*` option
2. **Active migration:** Run migration on plugin update

**Recommended:** Use lazy migration in the helper function:
```php
function samanlabs_seo_module_enabled( string $module ) : bool {
    $value = get_option( 'samanlabs_seo_module_' . $module );
    if ( false === $value ) {
        // Fall back to legacy option if new one doesn't exist
        $legacy_map = [
            'sitemap'    => 'samanlabs_seo_enable_sitemap_enhancer',
            'redirects'  => 'samanlabs_seo_enable_redirect_manager',
            '404_log'    => 'samanlabs_seo_enable_404_logging',
            'llm_txt'    => 'samanlabs_seo_enable_llm_txt',
            'local_seo'  => 'samanlabs_seo_enable_local_seo',
            'social_cards' => 'samanlabs_seo_enable_og_preview',
            'analytics'  => 'samanlabs_seo_enable_analytics',
        ];
        if ( isset( $legacy_map[ $module ] ) ) {
            return '1' === get_option( $legacy_map[ $module ], '1' );
        }
        return true; // Default enabled for new modules
    }
    return '1' === $value;
}
```
