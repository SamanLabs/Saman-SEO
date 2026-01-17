# Technology Stack

> Generated: 2026-01-16
> Plugin: Saman SEO

## Primary Languages

| Language | Version | Usage |
|----------|---------|-------|
| PHP | 7.4+ (required) | Core plugin logic, services, REST API |
| JavaScript | ES6+ (Node 18 for builds) | React admin interface, Gutenberg blocks |
| LESS | 4.2.0 | Stylesheet preprocessing |

## Frameworks & Runtime

| Framework | Version | Purpose |
|-----------|---------|---------|
| WordPress | 5.8+ (required) | Plugin host platform |
| React | (via @wordpress/element) | V2 Admin interface |
| WordPress REST API | v2 | All data operations |

## Key Dependencies

### PHP Dependencies

No external PHP dependencies via Composer. Plugin is self-contained using WordPress APIs.

### NPM Dependencies (package.json)

**Runtime:**
| Package | Version | Purpose |
|---------|---------|---------|
| @wordpress/api-fetch | ^6.50.0 | REST API communication |
| @wordpress/element | ^5.30.0 | React wrapper for WordPress |

**Development:**
| Package | Version | Purpose |
|---------|---------|---------|
| @wordpress/scripts | ^27.6.0 | Build tooling (webpack, babel) |
| concurrently | ^8.2.2 | Parallel npm script execution |
| less | ^4.2.0 | LESS compilation |
| less-watch-compiler | ^1.16.3 | LESS file watching |
| rimraf | ^5.0.5 | Cross-platform file deletion |

## Build System

**Toolchain:** npm with @wordpress/scripts

**Build Commands:**
```bash
npm run build          # Full production build
npm run build:less     # Compile LESS to CSS
npm run build:v2       # Build React admin interface
npm run build:editor   # Build Gutenberg editor components
npm run build:admin-list # Build admin list components
npm run watch:less     # Development LESS watcher
npm run start:v2       # Development server for React
npm run lint:js        # ESLint JavaScript
npm run format:js      # Prettier formatting
```

**Build Outputs:**
- `build-v2/` - React admin application
- `build-editor/` - Gutenberg block editor assets
- `build-admin-list/` - Admin post list enhancements
- `assets/css/` - Compiled CSS from LESS

## Development Tools

| Tool | Purpose | Configuration |
|------|---------|---------------|
| ESLint | JavaScript linting | WordPress defaults via @wordpress/scripts |
| Prettier | Code formatting | WordPress defaults |
| wp-scripts | Build orchestration | package.json scripts |
| LESS compiler | CSS preprocessing | less-watch-compiler |

## Database Requirements

**WordPress Tables Used:**
- `wp_options` - Plugin settings (50+ options)
- `wp_postmeta` - Per-post SEO metadata (`_wpseopilot_meta`)
- `wp_posts` - Content for analysis

**Custom Tables Created:**
| Table | Purpose | Schema Version |
|-------|---------|----------------|
| `wp_wpseopilot_redirects` | 301/302 redirect storage | 2 |
| `wp_wpseopilot_404_log` | 404 error logging | 5 |
| `wp_wpseopilot_404_ignore_patterns` | 404 exclusion patterns | 1 |
| `wp_wpseopilot_link_health` | Broken link tracking | 1 |
| `wp_wpseopilot_link_scans` | Link scan history | 1 |
| `wp_wpseopilot_indexnow_log` | IndexNow submissions | 1 |
| `wp_wpseopilot_custom_models` | Custom AI models | 1 |

## System Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 7.4 | 8.1+ |
| WordPress | 5.8 | 6.0+ |
| MySQL | 5.6 | 8.0 |
| Node.js (dev) | 18 | 20 |

## Version History

| Version | Status | Notes |
|---------|--------|-------|
| 0.1.41 | Current | Main plugin version |
| 0.2.0 | Components | Schema services, V2 admin |
