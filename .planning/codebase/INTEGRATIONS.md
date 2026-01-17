# Integrations

> Generated: 2026-01-16
> Plugin: Saman SEO

## External APIs

### OpenAI API

| Attribute | Value |
|-----------|-------|
| **Endpoint** | `https://api.openai.com/v1/chat/completions` |
| **Authentication** | Bearer token (API key) |
| **Files** | `includes/class-wpseopilot-service-ai-assistant.php`, `includes/Api/class-setup-controller.php`, `includes/Api/class-tools-controller.php` |
| **Key Storage** | `wpseopilot_openai_api_key` option |

**Usage:**
- Generate SEO titles (max 60 characters)
- Generate meta descriptions (max 155 characters)
- Bulk editor suggestions
- Content gap analysis
- Outline generation

**Models Supported:** gpt-4o-mini, gpt-4o (configurable)

### Anthropic API

| Attribute | Value |
|-----------|-------|
| **Endpoint** | `https://api.anthropic.com/v1/messages` |
| **Authentication** | Bearer token |
| **File** | `includes/Api/class-setup-controller.php` |

**Usage:** Alternative AI provider for SEO content generation

### Ollama (Local AI)

| Attribute | Value |
|-----------|-------|
| **Endpoint** | `http://localhost:11434/api/chat` |
| **Authentication** | None (local) |
| **File** | `includes/Api/class-setup-controller.php` |

**Usage:** Local AI model support for self-hosted deployments

### IndexNow API

| Attribute | Value |
|-----------|-------|
| **Primary Endpoint** | `https://api.indexnow.org/indexnow` |
| **Authentication** | API key in request body |
| **File** | `includes/class-wpseopilot-service-indexnow.php` |

**Alternative Endpoints:**
- Bing: `https://www.bing.com/indexnow`
- Yandex: `https://yandex.com/indexnow`
- Seznam: `https://search.seznam.cz/indexnow`
- Naver: `https://searchadvisor.naver.com/indexnow`

**Request Format:**
```json
{
  "host": "example.com",
  "key": "api-key",
  "keyLocation": "https://example.com/api-key.txt",
  "urlList": ["https://example.com/page1", "https://example.com/page2"]
}
```

**Logging:** Submissions logged to `wp_wpseopilot_indexnow_log` table

### GitHub API

| Attribute | Value |
|-----------|-------|
| **Endpoint** | `https://api.github.com` |
| **File** | `includes/Updater/class-github-updater.php` |

**Managed Plugins:**
- wp-seo-pilot/wp-seo-pilot.php (jhd3197/WP-SEO-Pilot)
- wp-ai-pilot/wp-ai-pilot.php (jhd3197/WP-AI-Pilot)
- wp-security-pilot/wp-security-pilot.php (jhd3197/WP-Security-Pilot)

**Caching:**
- Stable releases: 12 hours
- Beta releases: 6 hours

### Matomo Analytics

| Attribute | Value |
|-----------|-------|
| **Endpoint** | `https://matomo.builditdesign.com` |
| **File** | `includes/class-wpseopilot-service-analytics.php` |

**Usage:** Plugin activation tracking and usage analytics

## Third-Party Plugin Integrations

### WP AI Pilot Integration

| Attribute | Value |
|-----------|-------|
| **File** | `includes/Integration/class-ai-pilot.php` |
| **Hook** | `wp_ai_pilot_loaded` action |
| **Status** | Optional (checks if installed) |

**Features:**
- Registers SEO Pilot with WP AI Pilot
- Registers AI assistants (General SEO, SEO Reporter)
- Source identifier: `wp-seo-pilot`

**Helper Methods:**
```php
AI_Pilot::is_installed() // Check if plugin exists
AI_Pilot::is_loaded()    // Check if plugin is active
```

### WooCommerce Integration

| Attribute | Value |
|-----------|-------|
| **File** | `includes/Integration/class-woocommerce.php` |
| **Status** | Optional (checks if WooCommerce active) |
| **Hook** | `wpseopilot_jsonld_graph` filter (priority 25) |

**Features:**
- Adds Product schema for WooCommerce products
- Generates structured data for:
  - Product pricing
  - SKU
  - Ratings/reviews
  - Availability

**Helper Method:**
```php
WooCommerce::is_active() // Check if WooCommerce is active
```

## WordPress Integration Points

### REST API Namespace

**Namespace:** `wpseopilot/v2`

**Controllers (20+):**
| Controller | Endpoints |
|------------|-----------|
| Setup | `/setup/status`, `/setup/test-api`, `/setup/complete`, `/setup/skip`, `/setup/reset` |
| AI | `/ai/generate`, `/ai/status`, `/ai/models`, `/ai/settings`, `/ai/reset` |
| Assistants | AI assistant conversation endpoints |
| Mobile Test | `/mobile-test/analyze`, `/mobile-test/recent` |
| Schema Validator | `/schema-validator/validate` |
| Tools | Bulk editor, content gaps, schema builder, robots.txt |
| Settings | Global settings management |
| Dashboard | Dashboard data and statistics |
| Redirects | CRUD operations, groups |
| Link Health | Summary, broken links, orphans, scanning |
| Breadcrumbs | Settings, options, preview |
| IndexNow | URL submission, settings |
| Internal Links | Link management |
| Audit | SEO audit data |
| Sitemap | Configuration |
| Search Appearance | SERP preview, title/description |
| HtAccess | .htaccess editing |

### WordPress Actions (Key)

| Action | Priority | Service | Purpose |
|--------|----------|---------|---------|
| `plugins_loaded` | 10 | Bootstrap | Initialize plugin |
| `rest_api_init` | 10 | Admin_V2 | Register REST routes |
| `admin_menu` | 5 | Admin_V2 | Register admin menu |
| `wp_head` | 1 | Frontend | Render head tags |
| `wp_head` | 5 | Frontend | Render social tags |
| `wp_head` | 20 | Frontend | Render JSON-LD |
| `template_redirect` | 0 | Redirect_Manager | Handle redirects |
| `save_post` | 20 | Multiple | Save post meta |
| `add_meta_boxes` | 10 | Admin_UI | Register meta box |

### WordPress Filters (Key)

| Filter | Priority | Service | Purpose |
|--------|----------|---------|---------|
| `pre_get_document_title` | 0 | Frontend | Override page title |
| `post_row_actions` | 10 | Admin_UI | Add SEO actions |
| `bulk_actions-edit-post` | 10 | Admin_UI | Bulk SEO actions |
| `wpseopilot_jsonld_graph` | 15 | Breadcrumbs | Add breadcrumb schema |
| `wpseopilot_jsonld_graph` | 20 | Local_SEO | Add local business schema |
| `wpseopilot_jsonld_graph` | 25 | WooCommerce | Add product schema |
| `wpseopilot_feature_toggle` | 10 | Compatibility | Enable/disable features |
| `script_loader_tag` | 10 | Analytics | Add async/defer |

### Custom WordPress Hooks

**Actions:**
```php
do_action( 'wpseopilot_booted' );              // After plugin initialized
do_action( 'wpseopilot_404_logged', $url );   // After 404 recorded
```

**Filters:**
```php
apply_filters( 'wpseopilot_title', $title );           // Modify title
apply_filters( 'wpseopilot_og_image', $image_url );    // Override OG image
apply_filters( 'wpseopilot_jsonld_graph', $graph );    // Modify JSON-LD
apply_filters( 'wpseopilot_link_suggestions', $links ); // Filter suggestions
apply_filters( 'wpseopilot_managed_plugins', $plugins ); // Add to updater
```

## Database Integration

### WordPress Tables Used

| Table | Usage |
|-------|-------|
| `wp_options` | Plugin settings (50+ options) |
| `wp_postmeta` | Per-post SEO data (`_wpseopilot_meta`) |
| `wp_posts` | Content for analysis |

### Custom Tables

| Table | Purpose | Service |
|-------|---------|---------|
| `wp_wpseopilot_redirects` | 301/302 redirect storage | Redirect_Manager |
| `wp_wpseopilot_404_log` | 404 error logging | Request_Monitor |
| `wp_wpseopilot_404_ignore_patterns` | 404 exclusion patterns | Request_Monitor |
| `wp_wpseopilot_link_health` | Broken link tracking | Link_Health |
| `wp_wpseopilot_link_scans` | Link scan history | Link_Health |
| `wp_wpseopilot_indexnow_log` | IndexNow submissions | IndexNow |
| `wp_wpseopilot_custom_models` | Custom AI models | Tools_Controller |

### Transients

| Transient | TTL | Purpose |
|-----------|-----|---------|
| `wpseopilot_slug_changed_{user_id}` | 60s | Slug change detection |
| Redirect cache | 30s | Redirect matching |
| Update checks | 12h/6h | GitHub release caching |

## Scheduled Tasks (Cron)

| Action | Frequency | Service | Purpose |
|--------|-----------|---------|---------|
| `wpseopilot_check_updates` | Daily | GitHub_Updater | Check for updates |
| `wpseopilot_404_cleanup` | Daily | Request_Monitor | Clean old 404 logs |
| `wpseopilot_link_health_scan` | Configurable | Link_Health | Scan for broken links |

## AJAX Handlers

| Action | Service | Purpose |
|--------|---------|---------|
| `wp_ajax_wpseopilot_generate_ai` | AI_Assistant | Generate AI suggestions |
| `wp_ajax_wpseopilot_render_preview` | Post_Meta | Render SERP/social previews |
| `wp_ajax_wpseopilot_create_automatic_redirect` | Redirect_Manager | Create redirects on slug change |

## WP-CLI Support

| Attribute | Value |
|-----------|-------|
| **Service** | `Service\CLI` |
| **Condition** | Enabled only if `WP_CLI` is defined |
| **File** | `includes/class-wpseopilot-service-cli.php` |

**Purpose:** Command-line interface for plugin management (redirects, bulk operations)

## Schema.org Integration

**JSON-LD Context:** `https://schema.org`

**Schema Types Generated:**
| Type | Service | Trigger |
|------|---------|---------|
| Article | JsonLD | Blog posts |
| WebPage | JsonLD | Pages |
| BreadcrumbList | Breadcrumbs | All pages |
| LocalBusiness | Local_SEO | Local business settings |
| Product | WooCommerce | WooCommerce products |
| VideoObject | Video_Schema | Video meta |
| Course | Course_Schema | Course meta |
| Book | Book_Schema | Book meta |
| MusicRecording | Music_Schema | Music meta |
| Movie | Movie_Schema | Movie meta |
| Restaurant | Restaurant_Schema | Restaurant meta |
| Service | Service_Schema | Service meta |
| JobPosting | Job_Posting_Schema | Job meta |
| SoftwareApplication | Software_Schema | Software meta |
| FAQPage | FAQ Block | FAQ block usage |
| HowTo | HowTo Block | HowTo block usage |
