# Technology Stack

**Analysis Date:** 2026-01-23

## Languages

**Primary:**
- PHP 7.4+ - Core plugin backend, WordPress hooks, REST API controllers
- JavaScript (ES6+) - Frontend React components, build tooling, npm scripts

**Secondary:**
- JSX - React component syntax in `src-v2/` directory
- LESS - Stylesheet preprocessing for admin UI styling

## Runtime

**Environment:**
- WordPress (dependency) - Plugin runs as WordPress plugin
- Node.js - For development build tooling and asset compilation

**Package Manager:**
- npm - JavaScript package management
- Lockfile: `package-lock.json` present

## Frameworks

**Core:**
- React (via @wordpress/element) - Admin UI and dashboard components
- @wordpress/scripts v27.6.0 - Build tooling and bundling
- WordPress REST API - Backend API endpoints

**Testing:**
- Not detected

**Build/Dev:**
- LESS v4.2.0 - CSS preprocessing
- less-watch-compiler v1.16.3 - Live LESS compilation during development
- concurrently v8.2.2 - Run multiple npm tasks in parallel
- rimraf v5.0.5 - Cross-platform file deletion for build cleanup

## Key Dependencies

**Critical:**
- @wordpress/api-fetch v6.50.0 - WordPress REST API client for frontend
- @wordpress/element v5.30.0 - React rendering engine for WordPress

**Infrastructure:**
- @wordpress/scripts - Encompasses webpack, babel, eslint, formatting tooling

## Configuration

**Environment:**
- WordPress plugin constants defined in `saman-seo.php`:
  - `SAMAN_SEO_VERSION` - Plugin version
  - `SAMAN_SEO_PATH` - Plugin directory path
  - `SAMAN_SEO_URL` - Plugin directory URL
- Custom post meta keys use `_SAMAN_SEO_*` naming convention

**Build:**
- Multiple build scripts in `package.json`:
  - `build:less` - Compiles LESS stylesheets to CSS
  - `build:react` - Bundles main React app
  - `build:editor` - Bundles editor block component
  - `build:admin-list` - Bundles admin list component
- Output directory: `build/` for compiled assets
- Source directories:
  - `assets/less/` - Legacy LESS stylesheets
  - `src-v2/less/` - V2 React LESS stylesheets
  - `src-v2/` - React application source

## Platform Requirements

**Development:**
- Node.js (version specified in `.nvmrc` if present)
- npm v8+
- LESS compiler support

**Production:**
- WordPress 5.0+ (as WordPress plugin)
- PHP 7.4+
- MySQL/MariaDB (via WordPress)
- Web server with PHP support

---

*Stack analysis: 2026-01-23*
