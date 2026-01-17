# Directory Structure

> Generated: 2026-01-16
> Plugin: Saman SEO

## Root Directory

```
wp-seo-pilot/
├── wp-seo-pilot.php              # Plugin entry point (bootstrap, autoloader)
├── package.json                   # NPM build configuration
├── README.md                      # Plugin documentation
├── ROADMAP.md                     # Feature roadmap
├── ARCHITECTURE.md                # Architecture overview
├── CONTRIBUTING.md                # Contribution guidelines
├── .planning/                     # Planning documents (this folder)
│
├── includes/                      # Core PHP logic
├── src-v2/                        # React source (admin V2)
├── blocks/                        # Gutenberg block sources
├── build-v2/                      # Compiled React admin
├── build-editor/                  # Compiled editor assets
├── build-admin-list/              # Compiled admin list assets
├── assets/                        # CSS/JS/images
├── templates/                     # Legacy PHP templates
├── docs/                          # Developer documentation
├── vendor/                        # Composer packages (PHPUnit binaries)
└── .github/                       # GitHub workflows
```

## Detailed Structure

### `/includes/` - Core PHP

```
includes/
├── class-wpseopilot-plugin.php           # Main service container (singleton)
├── class-wpseopilot-admin-v2.php         # React admin loader
├── class-wpseopilot-admin-topbar.php     # Admin header component
├── helpers.php                            # Utility functions (34KB)
│
├── Api/                                   # REST API controllers (20+ files)
│   ├── class-rest-controller.php         # Abstract base controller
│   ├── class-dashboard-controller.php
│   ├── class-settings-controller.php
│   ├── class-searchappearance-controller.php
│   ├── class-redirects-controller.php    # 72KB - largest file
│   ├── class-audit-controller.php
│   ├── class-ai-controller.php
│   ├── class-assistants-controller.php
│   ├── class-tools-controller.php        # 52KB
│   ├── class-sitemap-controller.php
│   ├── class-link-health-controller.php
│   ├── class-breadcrumbs-controller.php
│   ├── class-indexnow-controller.php
│   ├── class-internallinks-controller.php
│   ├── class-mobile-test-controller.php
│   ├── class-schema-validator-controller.php
│   ├── class-htaccess-controller.php
│   ├── class-setup-controller.php
│   │
│   └── Assistants/                        # AI assistant definitions
│       ├── class-base-assistant.php       # Abstract base
│       ├── class-general-seo-assistant.php
│       └── class-seo-reporter-assistant.php
│
├── Service/                               # Specialized schema services
│   ├── class-wpseopilot-service-video-schema.php
│   ├── class-wpseopilot-service-course-schema.php
│   ├── class-wpseopilot-service-book-schema.php
│   ├── class-wpseopilot-service-music-schema.php
│   ├── class-wpseopilot-service-movie-schema.php
│   ├── class-wpseopilot-service-restaurant-schema.php
│   ├── class-wpseopilot-service-service-schema.php
│   ├── class-wpseopilot-service-job-posting-schema.php
│   └── class-wpseopilot-service-software-schema.php
│
├── Integration/                           # Third-party integrations
│   ├── class-ai-pilot.php                 # WP AI Pilot integration
│   └── class-woocommerce.php              # WooCommerce integration
│
├── Updater/                               # Version management
│   └── class-github-updater.php           # GitHub release updater
│
├── src/                                   # Legacy utilities
│   └── Twiglet.php                        # Template variable engine
│
└── [Service files in root includes/]
    ├── class-wpseopilot-service-frontend.php     # 28KB - head rendering
    ├── class-wpseopilot-service-settings.php     # 34KB - global settings
    ├── class-wpseopilot-service-post-meta.php    # Per-post meta
    ├── class-wpseopilot-service-admin-ui.php     # 22KB - classic editor
    ├── class-wpseopilot-service-redirect-manager.php   # 26KB
    ├── class-wpseopilot-service-sitemap-enhancer.php   # 40KB - largest service
    ├── class-wpseopilot-service-sitemap-settings.php
    ├── class-wpseopilot-service-jsonld.php       # Structured data
    ├── class-wpseopilot-service-local-seo.php    # Local business
    ├── class-wpseopilot-service-breadcrumbs.php  # Breadcrumb markup
    ├── class-wpseopilot-service-audit.php        # SEO audit
    ├── class-wpseopilot-service-ai-assistant.php # AI integration
    ├── class-wpseopilot-service-internal-linking.php
    ├── class-wpseopilot-service-link-health.php
    ├── class-wpseopilot-service-request-monitor.php   # 28KB - 404 logging
    ├── class-wpseopilot-service-analytics.php
    ├── class-wpseopilot-service-admin-bar.php
    ├── class-wpseopilot-service-dashboard-widget.php
    ├── class-wpseopilot-service-social-settings.php
    ├── class-wpseopilot-service-social-card-generator.php
    ├── class-wpseopilot-service-robots-manager.php
    ├── class-wpseopilot-service-llm-txt-generator.php
    ├── class-wpseopilot-service-indexnow.php
    ├── class-wpseopilot-service-compatibility.php
    ├── class-wpseopilot-service-cli.php
    ├── class-wpseopilot-service-schema-blocks.php
    ├── class-wpseopilot-internal-linking-engine.php
    └── class-wpseopilot-internal-linking-repository.php
```

### `/src-v2/` - React Source

```
src-v2/
├── index.js                              # Main entry point
├── App.js                                # Root component
│
├── editor/                               # Gutenberg block editor
│   ├── index.js                          # Editor entry point
│   └── components/
│       ├── SEOPanel.js                   # Main SEO panel
│       ├── SearchPreview.js              # SERP preview
│       └── [other editor components]
│
├── admin-list/                           # Admin post list
│   └── components/
│       └── SEOScoreBadge.js              # Score indicator
│
├── assistants/                           # AI chat interface
│   ├── AssistantChat.js                  # Chat UI
│   ├── AssistantProvider.js              # Context provider
│   └── agents/
│       ├── GeneralSEO.js
│       └── SEOReporter.js
│
├── components/                           # Shared React components
│   ├── Header.js
│   ├── SearchPreview.js
│   ├── TemplateInput.js
│   ├── VariablePicker.js
│   ├── AiGenerateModal.js
│   └── [other shared components]
│
├── hooks/                                # React hooks
│   ├── useSettings.js
│   └── useUrlTab.js
│
└── less/                                 # Component styles
    └── v2.less
```

### `/blocks/` - Gutenberg Blocks

```
blocks/
├── faq/                                  # FAQ block with schema
│   └── index.js
├── howto/                                # HowTo block with schema
│   └── index.js
└── breadcrumbs/                          # Breadcrumb block
    └── [block files]
```

### `/assets/` - Styles & Scripts

```
assets/
├── js/                                   # Bundled JavaScript
│   └── admin-v2.js
│
├── less/                                 # LESS source files
│   ├── admin.less                        # Admin interface
│   ├── editor.less                       # Editor metabox
│   ├── internal-linking.less
│   ├── plugin.less                       # Main entry point
│   └── components/                       # Component styles
│       ├── header.less
│       ├── tabs.less
│       └── cards.less
│
└── css/                                  # Compiled CSS
    ├── admin.css                         # 20KB
    ├── admin-v2.css                      # 12KB
    ├── breadcrumbs.css                   # 4.5KB
    ├── editor.css                        # 512B
    ├── internal-linking.css              # 8.8KB
    └── plugin.css                        # 35KB - largest CSS
```

### `/templates/` - Legacy PHP Templates

```
templates/
├── settings-page.php                     # SEO Defaults
├── search-appearance.php                 # Post type defaults (26KB)
├── social-settings.php                   # Social media settings
├── sitemap-settings.php                  # Sitemap configuration (27KB)
├── redirects.php                         # Redirect manager
├── audit.php                             # Audit dashboard
├── 404-log.php                           # 404 logging interface
├── internal-linking.php
├── local-seo.php                         # Local business settings
├── meta-box.php                          # Classic editor metabox
│
├── components/                           # Reusable template components
│   └── google-preview.php
│
└── partials/                             # Template partials
    └── [partial templates]
```

### `/docs/` - Documentation

```
docs/
├── GETTING_STARTED.md
├── DEVELOPER_GUIDE.md
├── FILTERS.md                            # Available filters
├── TEMPLATE_TAGS.md
├── SITEMAPS.md
├── WP_CLI.md
└── [other documentation]
```

### `/.github/` - GitHub Configuration

```
.github/
└── workflows/
    ├── beta-release.yml                  # Beta release automation
    └── release.yml                       # Production release automation
```

## File Naming Conventions

### PHP Classes

**Pattern:** `class-{namespace-slug}.php`

| Namespace | File Pattern | Example |
|-----------|--------------|---------|
| `WPSEOPilot\Service\*` | `class-wpseopilot-service-{name}.php` | `class-wpseopilot-service-frontend.php` |
| `WPSEOPilot\Api\*` | `class-{name}-controller.php` | `class-dashboard-controller.php` |
| `WPSEOPilot\Api\Assistants\*` | `class-{name}-assistant.php` | `class-general-seo-assistant.php` |
| `WPSEOPilot\Integration\*` | `class-{name}.php` | `class-ai-pilot.php` |

### React Components

| Type | Convention | Example |
|------|------------|---------|
| Components | PascalCase | `SearchPreview.js` |
| Hooks | camelCase with `use` prefix | `useSettings.js` |
| Agents | PascalCase | `GeneralSEO.js` |

### Assets

| Type | Pattern | Example |
|------|---------|---------|
| LESS | `{feature}.less` | `admin.less` |
| CSS | `{feature}.css` | `admin.css` |
| JS | `{module}.js` | `admin-v2.js` |

## Entry Points

| Entry Point | File | Purpose |
|-------------|------|---------|
| Plugin | `wp-seo-pilot.php` | Bootstrap, autoloader |
| React Admin | `src-v2/index.js` | V2 admin interface |
| Gutenberg Editor | `src-v2/editor/index.js` | Block editor integration |
| Admin List | `src-v2/admin-list/` | Post list enhancements |
| REST API | `includes/Api/class-rest-controller.php` | API base class |
| Frontend | `includes/class-wpseopilot-service-frontend.php` | Head rendering |

## Notable File Sizes

| File | Size | Notes |
|------|------|-------|
| `includes/Api/class-redirects-controller.php` | 72KB | Largest - complex CRUD |
| `includes/Api/class-tools-controller.php` | 52KB | Bulk operations |
| `includes/class-wpseopilot-service-sitemap-enhancer.php` | 40KB | Largest service |
| `includes/helpers.php` | 34KB | All utility functions |
| `includes/class-wpseopilot-service-settings.php` | 34KB | Many options |
| `assets/css/plugin.css` | 35KB | Main compiled CSS |
