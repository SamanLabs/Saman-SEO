# External Integrations

**Analysis Date:** 2026-01-23

## APIs & External Services

**Search Engine Indexing:**
- IndexNow Protocol - Instant URL submission to search engines
  - SDK/Client: WordPress wp_remote_post
  - Supported endpoints: api.indexnow.org (Bing, Yandex, Seznam), www.bing.com, yandex.com, search.seznam.cz, searchadvisor.naver.com
  - Auth: API key stored in plugin option `SAMAN_SEO_indexnow_settings`
  - Implementation: `includes/class-saman-seo-service-indexnow.php`

**YouTube & Vimeo:**
- YouTube oEmbed API - Fetch video metadata
  - Endpoint: `https://www.youtube.com/oembed`
  - Purpose: Video schema generation and thumbnail extraction
  - Implementation: `includes/class-saman-seo-service-video-schema.php`
- Vimeo oEmbed API - Fetch video metadata
  - Endpoint: `https://vimeo.com/api/oembed.json`
  - Purpose: Video schema generation
  - Implementation: `includes/class-saman-seo-service-video-schema.php`

**Schema & Metadata:**
- schema.org - Structured data validation
  - Client: wp_remote_get for URL validation
  - Purpose: JSON-LD schema extraction and validation
  - Implementation: `includes/Api/class-schema-validator-controller.php`

**Analytics:**
- Matomo Analytics - Plugin usage analytics
  - URL: `https://matomo.builditdesign.com`
  - Implementation: `includes/class-saman-seo-service-analytics.php`

**AI Integration:**
- Saman Labs AI Plugin - External AI functionality
  - Function-based integration via `wp_ai_pilot()` API
  - Requires: Saman Labs AI plugin installed and configured
  - Features:
    - Text generation (titles, descriptions)
    - AI assistants (SEO General, SEO Reporter)
    - Chat interface with message history
  - Auth: Configured in Saman Labs AI plugin settings
  - Implementation: `includes/Integration/class-ai-pilot.php`
  - Models used: gpt-4o-mini (default)

## Data Storage

**Databases:**
- WordPress MySQL/MariaDB
  - Connection: Via WordPress wp_db global
  - Client: WordPress wpdb class
  - Custom tables created:
    - `SAMAN_SEO_indexnow_log` - IndexNow submission logs
    - `SAMAN_SEO_redirects` - URL redirect rules
    - `SAMAN_SEO_404_log` - 404 error logging
    - `SAMAN_SEO_link_health` - Internal link health checks
    - `SAMAN_SEO_link_health_scans` - Link scan history
    - `SAMAN_SEO_request_monitor` - HTTP request tracking
    - `SAMAN_SEO_request_patterns` - Request pattern matching
    - Custom AI usage tracking tables

**File Storage:**
- Local filesystem only
- Directories used:
  - `build/` - Compiled CSS/JS assets
  - `includes/` - PHP backend code
  - `templates/` - Admin page templates
  - `assets/` - Static assets (images, legacy JS)

**Caching:**
- WordPress transients API for temporary data storage
- No external cache service (Redis, Memcached) detected

## Authentication & Identity

**Auth Provider:**
- WordPress capabilities system (no external auth)
- Required capability: `manage_options` for plugin administration
- All REST API endpoints require `manage_options` capability
- Implementation: `includes/Api/class-rest-controller.php`

## Monitoring & Observability

**Error Tracking:**
- Custom error logging via WordPress admin pages
- 404 error logging to local database table

**Logs:**
- IndexNow submission logs stored in custom database table
- Link health scan logs stored in custom database table
- Request monitoring stored in custom database table
- No external logging service detected

## CI/CD & Deployment

**Hosting:**
- WordPress.org plugin hosting (reference in plugin header)
- Self-hosted WordPress installation with plugin activation

**CI Pipeline:**
- Not detected - appears to be manual deployment

## Environment Configuration

**Required env vars:**
- None detected - uses WordPress options API for configuration storage

**Secrets location:**
- WordPress wp_options table:
  - `SAMAN_SEO_indexnow_settings` - API keys for IndexNow
  - `SAMAN_SEO_*` prefixed options - Various plugin settings
- Saman Labs AI plugin handles AI provider credentials

## Webhooks & Callbacks

**Incoming:**
- IndexNow key file verification endpoint - URL rewrite rule serves verification file for search engine validation
- WordPress REST API endpoints (all under `saman-seo/v1/` namespace)

**Outgoing:**
- IndexNow submission to search engines - POST requests to IndexNow API endpoints
- Custom action hooks for third-party integrations:
  - `SAMAN_SEO_feature_toggle` - Feature flag system
  - Various WordPress action/filter hooks for extensibility

## REST API Endpoints

**Base Namespace:** `/wp-json/saman-seo/v1/`

Key endpoints:
- `/mobile-test/analyze` - Analyze URL for mobile-friendliness
- `/mobile-test/recent` - Get recent mobile tests
- `/schema-validator/validate` - Validate structured data
- `/indexnow/submit` - Manual IndexNow submission
- `/dashboard/*` - Dashboard data
- `/audit/*` - SEO audit endpoints
- `/redirects/*` - Redirect management
- `/assistants/*` - AI assistant endpoints
- And 15+ other domain-specific controllers

---

*Integration audit: 2026-01-23*
