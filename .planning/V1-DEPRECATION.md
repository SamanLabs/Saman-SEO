# V1 Deprecation - Files to Remove

This document lists all V1 (legacy) files that should be removed in a future release.
The React-based V2 interface is now the primary and only UI.

## Migration Status

- [x] V2 menu now uses `wpseopilot` slug (was `wpseopilot-v2`)
- [x] V1 menu registration disabled
- [x] Legacy V1 URLs redirect to new V2 URLs
- [x] Legacy V2 URLs (with `-v2` prefix) redirect to new URLs

## URL Mapping

| Old V1 URL | New URL |
|------------|---------|
| `wpseopilot-types` | `wpseopilot-search-appearance` |
| `wpseopilot-sitemap` | `wpseopilot-sitemap` |
| `wpseopilot-redirects` | `wpseopilot-redirects` |
| `wpseopilot-404-errors` | `wpseopilot-404-log` |
| `wpseopilot-internal` | `wpseopilot-internal-linking` |
| `wpseopilot-audit` | `wpseopilot-audit` |
| `wpseopilot-ai` | `wpseopilot-ai-assistant` |
| `wpseopilot-local-seo` | `wpseopilot-settings` |

## Files to Remove

### Templates (V1 PHP Views)
These templates were used by the old V1 interface and are no longer needed:

```
templates/
├── 404-log.php                          # Replaced by React Log404.js
├── ai-assistant.php                     # Replaced by React AiAssistant.js
├── audit.php                            # Replaced by React Audit.js
├── internal-linking.php                 # Replaced by React InternalLinking.js
├── local-seo.php                        # Replaced by React Settings.js
├── meta-box.php                         # Replaced by Gutenberg sidebar (build-editor)
├── post-type-defaults.php               # Replaced by React SearchAppearance.js
├── post-type-defaults.php.backup        # Old backup file
├── redirects.php                        # Replaced by React Redirects.js
├── search-appearance.php                # Replaced by React SearchAppearance.js
├── settings-page.php                    # Replaced by React Settings.js
├── sitemap-settings.php                 # Replaced by React Sitemap.js
├── social-settings.php                  # Replaced by React SearchAppearance.js
├── components/
│   ├── google-preview.php               # Replaced by React component
│   ├── social-cards-tab.php             # Replaced by React component
│   ├── social-settings-tab.php          # Replaced by React component
│   └── post-type-fields/
│       ├── advanced.php                 # Replaced by React component
│       ├── custom-fields.php            # Replaced by React component
│       ├── schema.php                   # Replaced by React component
│       └── title-description.php        # Replaced by React component
└── partials/
    ├── internal-linking-categories.php  # Replaced by React component
    ├── internal-linking-rule-form.php   # Replaced by React component
    ├── internal-linking-rules.php       # Replaced by React component
    ├── internal-linking-settings.php    # Replaced by React component
    └── internal-linking-utms.php        # Replaced by React component
```

### Assets (V1 CSS/LESS)
These are V1-specific assets. Some may still be used by remaining V1 services:

```
assets/
├── less/
│   ├── admin.less                       # V1 admin styles
│   ├── core.less                        # Shared core (keep if used elsewhere)
│   ├── editor.less                      # Used by classic editor meta box
│   ├── internal-linking.less            # V1 internal linking styles
│   ├── mixins.less                      # Shared mixins (keep if used elsewhere)
│   ├── plugin.less                      # V1 plugin styles
│   ├── topbar.less                      # V1 topbar styles
│   ├── components/
│   │   ├── accordion-tabs.less          # V1 component
│   │   ├── cards.less                   # V1 component
│   │   ├── forms.less                   # V1 component
│   │   └── google-preview.less          # V1 component
│   └── pages/
│       ├── search-appearance.less       # V1 page styles
│       ├── sitemap.less                 # V1 page styles
│       └── social.less                  # V1 page styles
├── css/
│   ├── admin.css                        # Compiled V1 admin styles
│   ├── editor.css                       # Classic editor styles (may need to keep)
│   ├── internal-linking.css             # V1 internal linking styles
│   └── plugin.css                       # V1 plugin styles
└── js/
    ├── admin-v2.js                      # Old V2 experiment (remove)
    ├── admin-v2.js.map                  # Source map (remove)
    ├── admin-v2.asset.php               # Asset file (remove)
    ├── index.css                        # V1 index styles
    ├── index.css.map                    # Source map
    └── index-rtl.css                    # RTL styles
```

### PHP Files

#### Can Be Removed (V1 Only)
```
includes/
├── class-wpseopilot-service-admin-ui.php    # V1 admin UI service - renders PHP templates
```

#### Keep But Review
```
includes/
├── class-wpseopilot-admin-topbar.php        # May still be used, review
├── class-wpseopilot-service-settings.php    # Has V1 menu code disabled, keep rest
```

## Services Still in Use

These services provide backend functionality used by both V1 and V2:

- `class-wpseopilot-service-analytics.php` - Analytics tracking
- `class-wpseopilot-service-audit.php` - Site audit functionality
- `class-wpseopilot-service-compatibility.php` - Other SEO plugin compatibility
- `class-wpseopilot-service-frontend.php` - Frontend meta output
- `class-wpseopilot-service-internal-linking.php` - Internal linking engine
- `class-wpseopilot-service-jsonld.php` - Schema/JSON-LD output
- `class-wpseopilot-service-local-seo.php` - Local SEO settings
- `class-wpseopilot-service-post-meta.php` - Post meta handling
- `class-wpseopilot-service-redirect-manager.php` - Redirect handling
- `class-wpseopilot-service-robots-manager.php` - Robots.txt handling
- `class-wpseopilot-service-sitemap-enhancer.php` - Sitemap enhancements
- `class-wpseopilot-service-sitemap-settings.php` - Sitemap settings
- `class-wpseopilot-service-social-settings.php` - Social meta settings

## Migration Steps

### Phase 1 - Current (Completed)
1. ✅ V2 uses main menu slug (`wpseopilot`)
2. ✅ V1 menu registration disabled
3. ✅ Legacy URL redirects in place
4. ✅ React app updated to use new URLs

### Phase 2 - Cleanup (Future)
1. Remove V1 templates directory
2. Remove V1 LESS/CSS files
3. Remove `class-wpseopilot-service-admin-ui.php`
4. Remove V1 menu code from settings service
5. Clean up assets directory

### Phase 3 - Final (Future)
1. Remove legacy redirect code (after sufficient time)
2. Remove `view_map` legacy entries from Admin_V2
3. Update documentation
4. Bump major version

## Notes

- Keep classic editor support (`build-editor/`) as it's separate from V1
- Keep `test-analytics.php` until analytics verified working
- The `vendor/` directory is Composer dependencies, not V1 specific
- `src-v2/` folder name can be renamed to `src/` in a future refactor
- `build-v2/` folder name can be renamed to `build/` in a future refactor
