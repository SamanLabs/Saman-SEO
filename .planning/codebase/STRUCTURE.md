# Codebase Structure

**Analysis Date:** 2026-01-23

## Directory Layout

```
saman-seo/
├── saman-seo.php                    # Plugin entry point, PSR-4 autoloader
├── package.json                     # Build pipeline and npm dependencies
├── .planning/                       # GSD documentation and planning
│   └── codebase/                    # Codebase analysis documents
├── .claude/                         # Claude AI skills and settings
│   └── skills/                      # Custom Claude prompts for GSD tasks
├── assets/                          # Static assets (compiled CSS, images)
│   ├── js/                          # Admin JavaScript
│   │   ├── admin-v2.js             # Main admin app bundle
│   │   ├── admin-v2.asset.php      # Dependency manifest
│   │   ├── admin.js                 # Legacy admin JS
│   │   └── index.css                # Compiled admin styles
│   ├── less/                        # LESS source files
│   │   ├── admin.less              # Admin styles
│   │   ├── editor.less             # Editor styles
│   │   ├── plugin.less             # Plugin styles
│   │   ├── breadcrumbs.less        # Breadcrumbs styles
│   │   ├── internal-linking.less   # Internal linking styles
│   │   └── components/             # LESS component fragments
│   └── images/                      # SVG/PNG assets
├── build/                           # Compiled/generated assets (gitignored)
│   ├── v2/                         # React admin build output
│   ├── editor/                     # Editor block build output
│   ├── admin-list/                 # Admin list view build output
│   └── css/                        # Compiled CSS from LESS
├── blocks/                          # Gutenberg blocks with schema
│   ├── breadcrumbs/
│   │   └── index.js               # Breadcrumbs block (schema enabled)
│   ├── faq/
│   │   └── index.js               # FAQ block with FAQ schema
│   └── howto/
│       └── index.js               # HowTo block with HowTo schema
├── docs/                           # Documentation (not auto-generated)
├── includes/                        # Backend PHP code
│   ├── saman-seo.php              # LEGACY: Main plugin file (old pattern)
│   ├── helpers.php                 # Shared helper functions (Saman\SEO\Helpers namespace)
│   ├── class-saman-seo-plugin.php  # Plugin orchestrator singleton
│   ├── class-saman-seo-admin-v2.php # React admin interface loader
│   ├── class-saman-seo-admin-topbar.php # Admin top bar
│   ├── class-saman-seo-service-*.php   # Service classes (see below)
│   ├── class-saman-seo-internal-linking-*.php # Internal linking engine
│   ├── Api/                        # REST API controllers
│   │   ├── class-rest-controller.php      # Abstract base for controllers
│   │   ├── class-dashboard-controller.php      # Dashboard data
│   │   ├── class-settings-controller.php       # Plugin settings
│   │   ├── class-redirects-controller.php      # Redirect management
│   │   ├── class-404-log-controller.php        # 404 logging
│   │   ├── class-audit-controller.php          # SEO audit data
│   │   ├── class-sitemap-controller.php        # Sitemap settings
│   │   ├── class-search-appearance-controller.php # Schema/structured data
│   │   ├── class-tools-controller.php          # Tools endpoints
│   │   ├── class-ai-controller.php             # AI assistant endpoints
│   │   ├── class-assistants-controller.php     # AI assistants list
│   │   ├── class-link-health-controller.php    # Link health data
│   │   ├── class-breadcrumbs-controller.php    # Breadcrumbs config
│   │   ├── class-internal-links-controller.php # Internal links data
│   │   ├── class-mobile-test-controller.php    # Mobile friendly test
│   │   ├── class-htaccess-controller.php       # .htaccess editor
│   │   ├── class-schema-validator-controller.php # Schema validation
│   │   ├── class-indexnow-controller.php       # IndexNow integration
│   │   ├── class-more-controller.php           # Miscellaneous tools
│   │   └── Assistants/             # AI assistant implementations
│   │       ├── class-base-assistant.php        # Base assistant class
│   │       ├── class-general-seo-assistant.php # General SEO AI
│   │       └── class-seo-reporter-assistant.php # Report generator AI
│   ├── Service/                    # Schema type services
│   │   ├── class-saman-seo-service-video-schema.php
│   │   ├── class-saman-seo-service-course-schema.php
│   │   ├── class-saman-seo-service-book-schema.php
│   │   ├── class-saman-seo-service-music-schema.php
│   │   ├── class-saman-seo-service-movie-schema.php
│   │   ├── class-saman-seo-service-restaurant-schema.php
│   │   ├── class-saman-seo-service-service-schema.php
│   │   ├── class-saman-seo-service-software-schema.php
│   │   └── class-saman-seo-service-job-posting-schema.php
│   ├── Integration/                # External plugin integrations
│   │   ├── class-ai-pilot.php      # Saman Labs AI integration
│   │   └── class-woocommerce.php   # WooCommerce integration
│   ├── Updater/                    # Plugin update handling
│   │   ├── class-plugin-installer.php
│   │   └── class-plugin-registry.php
│   ├── src/                        # Utilities library
│   │   └── Twiglet.php            # Template engine
│   └── useragent_detect_browser.php # User-agent detection utility
├── src-v2/                         # React admin source code
│   ├── index.js                   # Main admin app entry
│   ├── editor/                    # Block editor setup
│   │   └── index.js              # Editor JS entry
│   ├── admin-list/                # Post list admin columns
│   │   └── index.js              # Admin list entry
│   ├── less/                      # React app styles
│   │   └── v2.less               # Main app styles
│   ├── components/                # React components
│   ├── pages/                     # React page components
│   ├── hooks/                     # React custom hooks
│   ├── utils/                     # React utility functions
│   └── store/                     # State management (if any)
├── .git/                          # Git repository
├── .github/                       # GitHub configuration
│   └── workflows/                # CI/CD workflows
├── .vscode/                       # VS Code settings
├── .marketing/                    # Marketing materials
└── node_modules/                 # npm dependencies (gitignored)
```

## Directory Purposes

**saman-seo.php (root):**
- Purpose: WordPress plugin entry point
- Contains: Plugin header, PSR-4 autoloader, bootstrap hooks
- Key files: None (entire file)

**includes/:**
- Purpose: All backend PHP code organized by concern
- Contains: Services, API controllers, integrations, helpers
- Key files: `class-saman-seo-plugin.php`, `helpers.php`

**includes/Api/:**
- Purpose: REST API endpoints for admin UI
- Contains: Controller classes extending REST_Controller base
- Key files: `class-rest-controller.php` (base), `class-dashboard-controller.php`

**includes/Service/:**
- Purpose: Schema type implementations
- Contains: Video, Course, Book, Music, Movie, Restaurant, Service, Software, Job Posting schemas
- Key files: All follow `class-saman-seo-service-*-schema.php` pattern

**includes/Integration/:**
- Purpose: Third-party plugin integrations
- Contains: AI_Pilot (Saman Labs AI), WooCommerce
- Key files: `class-ai-pilot.php`, `class-woocommerce.php`

**blocks/:**
- Purpose: Gutenberg blocks with SEO schema support
- Contains: FAQ, HowTo, Breadcrumbs blocks
- Key files: Each block has `index.js`

**assets/:**
- Purpose: Compiled static assets
- Contains: Compiled CSS, images, built JavaScript
- Key files: `assets/js/admin-v2.js` (built React app)

**assets/less/:**
- Purpose: LESS source styles
- Contains: Admin, editor, plugin, breadcrumbs, internal linking styles
- Key files: `admin.less`, `editor.less`, `plugin.less`

**src-v2/:**
- Purpose: React admin interface source code
- Contains: React components, pages, hooks, utilities
- Key files: `index.js` (entry), `pages/` (page components)

**build/:**
- Purpose: Compiled outputs (generated, not committed)
- Contains: React bundles (v2, editor, admin-list), CSS from LESS
- Key files: `v2/index.js` (compiled admin), `css/` (compiled styles)

## Key File Locations

**Entry Points:**
- `saman-seo.php`: WordPress plugin bootstrap, autoloader, integration hooks
- `includes/class-saman-seo-plugin.php`: Plugin orchestrator, service registration
- `includes/class-saman-seo-admin-v2.php`: React admin interface loader
- `src-v2/index.js`: React app entry point

**Configuration:**
- `saman-seo.php`: Plugin constants (SAMAN_SEO_VERSION, SAMAN_SEO_PATH, SAMAN_SEO_URL)
- `includes/class-saman-seo-plugin.php`: Service registration and activation defaults
- `includes/helpers.php`: Module enable/disable options

**Core Logic:**
- `includes/class-saman-seo-service-post-meta.php`: Post metadata registration and persistence
- `includes/class-saman-seo-service-frontend.php`: Frontend tag rendering
- `includes/class-saman-seo-service-jsonld.php`: JSON-LD schema output
- `includes/class-saman-seo-service-redirect-manager.php`: Redirect management
- `includes/Api/class-dashboard-controller.php`: Dashboard aggregation

**Testing:**
- No test files found in current codebase

**REST Endpoints:**
- Base: `/wp-json/saman-seo/v1/`
- Controllers in: `includes/Api/`
- Routes registered in each controller's `register_routes()` method

## Naming Conventions

**Files:**
- Classes: `class-saman-seo-service-{feature}.php` or `class-saman-seo-{feature}.php`
- API controllers: `class-{feature}-controller.php` (in includes/Api/)
- Integrations: `class-{service-name}.php` (in includes/Integration/)
- Blocks: `{block-name}/index.js` (in blocks/)
- Styles: `{feature}.less` (in assets/less/)

**Directories:**
- Grouped by function: `Api/`, `Service/`, `Integration/`, `Updater/`, `Assistants/`
- Feature blocks: `blocks/{block-name}/`
- Build outputs: `build/{target}/` (v2, editor, admin-list)

**PHP Classes:**
- Namespace: `Saman\SEO` with subnamespaces (Api, Service, Integration, Helpers)
- Naming: PascalCase, follows namespace hierarchy (e.g., `Saman\SEO\Service\Post_Meta`)
- Abstract classes: REST_Controller base class for API layer

**PHP Functions:**
- Namespace: `Saman\SEO\Helpers` for shared utilities
- Naming: snake_case (e.g., `module_enabled()`, `get_post_meta()`)
- Prefix: All helper functions use full namespace

**WordPress Hooks:**
- Action: `SAMAN_SEO_booted` (after plugin initialization)
- Filters: `SAMAN_SEO_feature_toggle` (feature flags)
- Options: `SAMAN_SEO_*` prefix (e.g., `SAMAN_SEO_default_title_template`)
- Post meta: `_SAMAN_SEO_meta` (private post meta key)

**REST Routes:**
- Pattern: `/saman-seo/v1/{resource}` (e.g., `/saman-seo/v1/dashboard`)
- Namespace: `saman-seo/v1` defined in REST_Controller base

## Where to Add New Code

**New Feature (Complete):**
- Primary code: Create service in `includes/class-saman-seo-service-{feature}.php`
- REST endpoints: Add controller in `includes/Api/class-{feature}-controller.php`
- Registration: Register in `Plugin::boot()` method in `includes/class-saman-seo-plugin.php`
- Settings: Add options in `Plugin::activate()` method
- React UI: Add pages/components in `src-v2/pages/{feature}/` and `src-v2/components/`

**New REST Endpoint:**
- Create controller: `includes/Api/class-{feature}-controller.php` extending `REST_Controller`
- Implement: `register_routes()` method (registers routes and callbacks)
- Register: Instantiate in `Admin_V2::register_routes()` or during admin initialization
- Pattern: Return `$this->success($data)` or `$this->error($message)`

**New Schema Type:**
- Create service: `includes/Service/class-saman-seo-service-{type}-schema.php`
- Follow: Schema pattern in existing files (Video_Schema, Book_Schema, etc.)
- Register: Boot in `Plugin::boot()` method
- Location: Use Service subdirectory for schema types

**New Gutenberg Block:**
- Create directory: `blocks/{block-name}/`
- Create file: `blocks/{block-name}/index.js`
- Register: Block type registration with WordPress block API
- Schema: Include structured data via schema service

**Utility/Helper Functions:**
- Add to: `includes/helpers.php` under `Saman\SEO\Helpers` namespace
- Naming: snake_case function names
- Export: Add function to namespace

**New Integration:**
- Create class: `includes/Integration/class-{service-name}.php`
- Namespace: `Saman\SEO\Integration`
- Pattern: Follow AI_Pilot or WooCommerce examples
- Boot: Called from main plugin bootstrap in `saman-seo.php`

## Special Directories

**build/:**
- Purpose: Compiled assets from npm build process
- Generated: Yes (via `npm run build`)
- Committed: No (generated on build)
- Patterns: `build/v2/` (React admin), `build/editor/` (editor JS), `build/css/` (compiled LESS)

**includes/Updater/:**
- Purpose: Plugin installer and update registry
- Generated: No
- Committed: Yes
- Functions: Manage plugin versions and update checks

**includes/src/:**
- Purpose: Third-party utility libraries
- Generated: No
- Committed: Yes
- Contains: Twiglet template engine

**.claude/:**
- Purpose: Claude AI integration (GSD skills)
- Generated: No (hand-written)
- Committed: Yes
- Contains: Custom prompts for code generation tasks

**.planning/codebase/:**
- Purpose: Architecture and codebase documentation
- Generated: Via `/gsd:map-codebase` command
- Committed: Yes (documents, not code)
- Contains: ARCHITECTURE.md, STRUCTURE.md, CONVENTIONS.md, TESTING.md, CONCERNS.md

## Modification Safety Guidelines

**Safe to Modify:**
- Services: Add new methods, extend boot(), follow existing patterns
- REST controllers: Add new routes, follow existing callback pattern
- Helpers: Add new utility functions to helpers.php
- React components: Modify src-v2/ (recompile with npm run build)
- LESS files: Modify assets/less/ (recompile with npm run build:less)

**Careful Modification:**
- `Plugin::boot()`: Order matters, all services must register
- `Admin_V2::view_map`: Mapping to React pages, affects URL routing
- PSR-4 autoloader: Class naming conventions must match file paths
- REST namespaces: Changing `/saman-seo/v1/` breaks React fetch calls

**Never Modify:**
- Plugin header in saman-seo.php (version, name, description)
- Meta key `_SAMAN_SEO_meta` (breaks post data storage)
- Option prefix `SAMAN_SEO_` (breaks settings retrieval)

---

*Structure analysis: 2026-01-23*
