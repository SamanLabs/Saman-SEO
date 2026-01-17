# Developer Guide

Saman SEO is built with extensibility in mind. This guide covers how developers can extend, customize, and integrate with the plugin using hooks, filters, and programmatic controls.

---

## Table of Contents

- [Plugin Architecture](#plugin-architecture)
- [Action Hooks](#action-hooks)
- [Filter Hooks](#filter-hooks)
- [Programmatic Functions](#programmatic-functions)
- [Helper Functions](#helper-functions)
- [Database Structure](#database-structure)
- [REST API Integration](#rest-api-integration)
- [Custom Capabilities](#custom-capabilities)
- [Feature Toggles](#feature-toggles)
- [Best Practices](#best-practices)

---

## Plugin Architecture

Saman SEO follows a service-oriented architecture with clean separation of concerns.

### Core Services

| Service | File | Responsibility |
|---------|------|----------------|
| **Frontend** | `class-wpseopilot-service-frontend.php` | Meta tag rendering, title generation |
| **Admin UI** | `class-wpseopilot-service-admin-ui.php` | Meta boxes, admin columns, scores |
| **Post Meta** | `class-wpseopilot-service-post-meta.php` | REST API, meta storage |
| **Sitemap Enhancer** | `class-wpseopilot-service-sitemap-enhancer.php` | Sitemap generation and filtering |
| **Redirect Manager** | `class-wpseopilot-service-redirect-manager.php` | 301 redirects, 404 tracking |
| **Internal Linking** | `class-wpseopilot-service-internal-linking.php` | Automated link insertion |
| **AI Assistant** | `class-wpseopilot-service-ai-assistant.php` | OpenAI integration |
| **JSON-LD** | `class-wpseopilot-service-jsonld.php` | Structured data generation |
| **Local SEO** | `class-wpseopilot-service-local-seo.php` | LocalBusiness schema |
| **Audit** | `class-wpseopilot-service-audit.php` | Site-wide SEO analysis |

### Plugin Bootstrap

The plugin is bootstrapped via the singleton pattern:

```php
$plugin = \WPSEOPilot\Plugin::get_instance();
```

**Main Plugin File:** `wp-seo-pilot.php`
**Plugin Class:** `includes/class-wpseopilot-plugin.php`

All services are registered and initialized in `Plugin::init()` (line 60).

---

## Action Hooks

### `wpseopilot_booted`

Fires when the plugin has fully initialized all services.

**Parameters:**
- `$plugin` (object) - Plugin instance

**Usage:**

```php
add_action( 'wpseopilot_booted', function( $plugin ) {
    // Plugin is fully loaded, all services available
    error_log( 'Saman SEO initialized!' );
}, 10, 1 );
```

**Location:** `includes/class-wpseopilot-plugin.php:78`

---

### `wpseopilot_sitemap_regenerated`

Fires after the sitemap has been regenerated (manual or scheduled).

**Parameters:** None

**Usage:**

```php
add_action( 'wpseopilot_sitemap_regenerated', function() {
    // Notify external service that sitemap updated
    wp_remote_post( 'https://api.example.com/sitemap-update', [
        'body' => [ 'sitemap_url' => home_url( '/wp-sitemap.xml' ) ]
    ]);
});
```

**Location:** `includes/class-wpseopilot-service-sitemap-settings.php:365`

---

## Filter Hooks

For complete filter documentation with examples, see **[Filter Reference](FILTERS.md)**.

### Meta Tag Filters

#### `wpseopilot_title`

Filter the page title before output.

**Parameters:**
- `$title` (string) - Generated title
- `$post` (WP_Post|null) - Current post object

**Usage:**

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    // Add store name to all product titles
    if ( $post && get_post_type( $post ) === 'product' ) {
        return $title . ' | MyStore';
    }
    return $title;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:64, 305, 665`

---

#### `wpseopilot_description`

Filter the meta description before output.

**Parameters:**
- `$description` (string) - Generated description
- `$post` (WP_Post) - Current post object

**Usage:**

```php
add_filter( 'wpseopilot_description', function( $description, $post ) {
    // Append CTA to all descriptions
    if ( is_singular( 'product' ) ) {
        return $description . ' Order now for free shipping!';
    }
    return $description;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:100`

---

#### `wpseopilot_canonical`

Filter the canonical URL before output.

**Parameters:**
- `$canonical` (string) - Canonical URL
- `$post` (WP_Post) - Current post object

**Usage:**

```php
add_filter( 'wpseopilot_canonical', function( $canonical, $post ) {
    // Force HTTPS on all canonical URLs
    return str_replace( 'http://', 'https://', $canonical );
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:103, 170`

---

#### `wpseopilot_robots`

Filter the robots meta tag content.

**Parameters:**
- `$robots` (string) - Comma-separated robots directives

**Usage:**

```php
add_filter( 'wpseopilot_robots', function( $robots ) {
    // Force noindex on staging environment
    if ( wp_get_environment_type() === 'staging' ) {
        return 'noindex, nofollow';
    }
    return $robots;
});
```

**Location:** `includes/class-wpseopilot-service-frontend.php:521`

---

### Social Media Filters

#### `wpseopilot_og_image`

Filter the Open Graph image URL.

**Parameters:**
- `$image` (string) - Image URL
- `$post` (WP_Post) - Current post object
- `$meta` (array) - Post meta data
- `$defaults` (array) - Default settings

**Usage:**

```php
add_filter( 'wpseopilot_og_image', function( $image, $post, $meta, $defaults ) {
    // Use CDN URL for all OG images
    if ( $post && $post->ID === 42 ) {
        return 'https://cdn.example.com/special-promo.jpg';
    }
    return $image;
}, 10, 4 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:587`

---

#### `wpseopilot_og_title`

Filter the Open Graph title tag.

**Parameters:**
- `$title` (string) - OG title
- `$post` (WP_Post) - Current post

**Usage:**

```php
add_filter( 'wpseopilot_og_title', function( $title, $post ) {
    // Customize OG title for products
    if ( get_post_type( $post ) === 'product' ) {
        return 'Shop: ' . $title;
    }
    return $title;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:194`

---

#### `wpseopilot_social_tags`

Filter all social meta tags at once.

**Parameters:**
- `$tags` (array) - Associative array of tag name => content
- `$post` (WP_Post) - Current post
- `$meta` (array) - Post meta data
- `$defaults` (array) - Default settings

**Usage:**

```php
add_filter( 'wpseopilot_social_tags', function( $tags, $post, $meta, $defaults ) {
    // Add custom OG tag
    $tags['og:custom'] = 'custom-value';

    // Remove Twitter card
    unset( $tags['twitter:card'] );

    return $tags;
}, 10, 4 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:274`

---

### Structured Data Filters

#### `wpseopilot_jsonld`

Filter the complete JSON-LD output before rendering.

**Parameters:**
- `$payload` (array) - Complete JSON-LD schema
- `$post` (WP_Post) - Current post

**Usage:**

```php
add_filter( 'wpseopilot_jsonld', function( $payload, $post ) {
    // Add custom schema
    $payload['@graph'][] = [
        '@type' => 'Product',
        'name' => get_the_title( $post ),
        'offers' => [
            '@type' => 'Offer',
            'price' => '99.99',
            'priceCurrency' => 'USD'
        ]
    ];

    return $payload;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-frontend.php:328`

---

#### `wpseopilot_schema_webpage`

Filter the WebPage schema specifically.

**Parameters:**
- `$schema` (array) - WebPage schema
- `$post` (WP_Post) - Current post

**Usage:**

```php
add_filter( 'wpseopilot_schema_webpage', function( $schema, $post ) {
    // Add breadcrumb to schema
    $schema['breadcrumb'] = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [ '@type' => 'ListItem', 'position' => 1, 'name' => 'Home' ]
        ]
    ];

    return $schema;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-jsonld.php:75`

---

#### `wpseopilot_schema_article`

Filter the Article schema for posts.

**Parameters:**
- `$schema` (array) - Article schema
- `$post` (WP_Post) - Current post

**Usage:**

```php
add_filter( 'wpseopilot_schema_article', function( $schema, $post ) {
    // Add author information
    $author = get_userdata( $post->post_author );
    $schema['author'] = [
        '@type' => 'Person',
        'name' => $author->display_name,
        'url' => get_author_posts_url( $author->ID )
    ];

    return $schema;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-jsonld.php:94`

---

### Sitemap Filters

#### `wpseopilot_sitemap_entry`

Filter individual sitemap entries.

**Parameters:**
- `$entry` (array) - Sitemap entry data (loc, lastmod, changefreq, priority, images)
- `$post_id` (int) - Post ID
- `$post_type` (string) - Post type

**Usage:**

```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    // Set high priority for featured posts
    if ( get_post_meta( $post_id, '_is_featured', true ) ) {
        $entry['priority'] = 1.0;
        $entry['changefreq'] = 'daily';
    }

    return $entry;
}, 10, 3 );
```

**Location:** `includes/class-wpseopilot-service-sitemap-enhancer.php:165`

---

#### `wpseopilot_sitemap_images`

Filter images included in sitemap for a post.

**Parameters:**
- `$images` (array) - Array of image URLs
- `$post_id` (int) - Post ID

**Usage:**

```php
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
    // Add gallery images to sitemap
    $gallery = get_post_meta( $post_id, '_product_gallery', true );
    if ( $gallery ) {
        $images = array_merge( $images, $gallery );
    }

    return $images;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-sitemap-enhancer.php:237`

---

#### `wpseopilot_sitemap_post_query_args`

Filter WP_Query arguments for sitemap generation.

**Parameters:**
- `$args` (array) - Query arguments
- `$post_type` (string) - Post type being queried

**Usage:**

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    // Only include published products in stock
    if ( $post_type === 'product' ) {
        $args['meta_query'] = [
            [
                'key' => '_stock_status',
                'value' => 'instock'
            ]
        ];
    }

    return $args;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-sitemap-enhancer.php:292`

---

### Breadcrumb Filters

#### `wpseopilot_breadcrumb_links`

Filter the breadcrumb trail array.

**Parameters:**
- `$crumbs` (array) - Array of breadcrumb items
- `$post` (WP_Post) - Current post

**Breadcrumb Structure:**
```php
[
    [ 'url' => 'https://example.com', 'label' => 'Home' ],
    [ 'url' => 'https://example.com/category', 'label' => 'Category' ],
    [ 'url' => '', 'label' => 'Current Page' ] // No URL for current page
]
```

**Usage:**

```php
add_filter( 'wpseopilot_breadcrumb_links', function( $crumbs, $post ) {
    // Insert custom breadcrumb for products
    if ( get_post_type( $post ) === 'product' ) {
        array_splice( $crumbs, 1, 0, [
            [ 'url' => '/shop', 'label' => 'Shop' ]
        ]);
    }

    return $crumbs;
}, 10, 2 );
```

**Location:** `includes/helpers.php:674`

---

### Score & Analysis Filters

#### `wpseopilot_seo_score`

Filter the calculated SEO score for a post.

**Parameters:**
- `$result` (array) - Score result with keys: `score`, `issues`, `suggestions`
- `$post` (WP_Post) - Post being scored

**Usage:**

```php
add_filter( 'wpseopilot_seo_score', function( $result, $post ) {
    // Adjust scoring for products
    if ( get_post_type( $post ) === 'product' ) {
        // Penalize if missing price
        if ( ! get_post_meta( $post->ID, '_price', true ) ) {
            $result['score'] -= 10;
            $result['issues'][] = 'Missing product price';
        }
    }

    return $result;
}, 10, 2 );
```

**Location:** `includes/helpers.php:540`

---

### Feature Toggle Filters

#### `wpseopilot_feature_toggle`

Enable or disable specific plugin features.

**Parameters:**
- `$enabled` (bool) - Whether feature is enabled
- `$feature` (string) - Feature key

**Available Features:**
- `metabox` - Admin meta box
- `frontend_head` - Meta tag rendering
- `sitemaps` - Sitemap functionality
- `redirects` - Redirect manager
- `llm_txt` - LLM.txt generation

**Usage:**

```php
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    // Disable redirects on development
    if ( $feature === 'redirects' && wp_get_environment_type() === 'local' ) {
        return false;
    }

    return $enabled;
}, 10, 2 );
```

**Location:** `includes/class-wpseopilot-service-admin-ui.php:27`

---

#### `wpseopilot_score_post_types`

Filter which post types show SEO score in admin.

**Parameters:**
- `$post_types` (array) - Array of post type slugs

**Usage:**

```php
add_filter( 'wpseopilot_score_post_types', function( $post_types ) {
    // Add 'product' post type to scored types
    $post_types[] = 'product';

    // Remove 'page' from scoring
    $post_types = array_diff( $post_types, [ 'page' ] );

    return $post_types;
});
```

**Location:** `includes/class-wpseopilot-service-admin-ui.php:65`

---

### Internal Linking Filters

#### `wpseopilot_link_suggestions`

Filter the internal link suggestions shown in post editor.

**Parameters:**
- `$suggestions` (array) - Array of suggested links
- `$post_id` (int) - Current post ID

**Usage:**

```php
add_filter( 'wpseopilot_link_suggestions', function( $suggestions, $post_id ) {
    // Add custom link suggestion
    $suggestions[] = [
        'url' => '/custom-page',
        'title' => 'Custom Suggestion',
        'excerpt' => 'This is a custom link suggestion',
        'keyword' => 'custom keyword'
    ];

    return $suggestions;
}, 10, 2 );
```

**Location:** `templates/meta-box.php:124`

---

#### `wpseopilot_internal_link_roles`

Filter which user roles can manage internal linking.

**Parameters:**
- `$roles` (array) - Array of role slugs

**Usage:**

```php
add_filter( 'wpseopilot_internal_link_roles', function( $roles ) {
    // Allow editors to manage internal links
    $roles[] = 'editor';

    return $roles;
});
```

**Location:** `includes/class-wpseopilot-service-internal-linking.php:755`

---

### LLM.txt Filters

#### `wpseopilot_llm_txt_content`

Filter the complete llm.txt file content.

**Parameters:**
- `$content` (string) - Generated llm.txt content

**Usage:**

```php
add_filter( 'wpseopilot_llm_txt_content', function( $content ) {
    // Append custom instructions for AI
    $content .= "\n\n# Custom Instructions\n";
    $content .= "This site specializes in WordPress SEO solutions.\n";

    return $content;
});
```

**Location:** `includes/class-wpseopilot-service-llm-txt-generator.php:176`

---

## Programmatic Functions

### Creating Redirects

**Function:** `wpseopilot_create_redirect()`

```php
/**
 * Create a redirect programmatically
 *
 * @param string $source Source path (e.g., '/old-url')
 * @param string $target Target URL (e.g., '/new-url' or 'https://external.com')
 * @param int    $status_code HTTP status code (301, 302, 307, 308)
 * @return bool|WP_Error True on success, WP_Error on failure
 */
$result = wpseopilot_create_redirect( '/old-url', '/new-url', 301 );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect failed: ' . $result->get_error_message() );
} else {
    echo 'Redirect created successfully!';
}
```

**Location:** `includes/helpers.php:733`

---

### Rendering Breadcrumbs

**Function:** `wpseopilot_breadcrumbs()`

```php
/**
 * Render breadcrumbs with Schema.org markup
 *
 * @param WP_Post|null $post Post object (defaults to current post)
 * @param bool         $echo Whether to echo or return (default: true)
 * @return string|void HTML markup if $echo is false
 */

// In your theme template
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}

// Or get the HTML
$breadcrumbs_html = wpseopilot_breadcrumbs( null, false );
```

**Location:** `includes/helpers.php:720`

---

## Helper Functions

Saman SEO provides namespaced helper functions for common tasks.

**Namespace:** `WPSEOPilot\Helpers`

### Get Option

```php
use function WPSEOPilot\Helpers\get_option;

$default_title = get_option( 'wpseopilot_default_title_template', '{{post_title}} | {{site_title}}' );
```

### Get Post Meta

```php
use function WPSEOPilot\Helpers\get_post_meta;

$meta = get_post_meta( $post_id );
// Returns: [ 'title' => '', 'description' => '', 'canonical' => '', ... ]
```

### Replace Template Variables

```php
use function WPSEOPilot\Helpers\replace_template_variables;

$template = '{{post_title}} | {{site_title}}';
$output = replace_template_variables( $template, $post );
// Returns: "My Blog Post | My Site Name"
```

### Generate Title from Template

```php
use function WPSEOPilot\Helpers\generate_title_from_template;

$title = generate_title_from_template( $post, 'post' );
```

### Calculate SEO Score

```php
use function WPSEOPilot\Helpers\calculate_seo_score;

$score_data = calculate_seo_score( $post );
// Returns: [ 'score' => 85, 'issues' => [...], 'suggestions' => [...] ]
```

### Generate Breadcrumbs

```php
use function WPSEOPilot\Helpers\breadcrumbs;

$breadcrumbs_html = breadcrumbs( $post, false );
```

**Location:** `includes/helpers.php`

---

## Database Structure

### Redirects Table

**Table Name:** `wp_wpseopilot_redirects`

```sql
CREATE TABLE wp_wpseopilot_redirects (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    source VARCHAR(255) NOT NULL,
    target VARCHAR(255) NOT NULL,
    status_code INT(3) NOT NULL DEFAULT 301,
    hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
    last_hit DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY request_uri (source)
)
```

**Direct Query Example:**

```php
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_redirects';

$redirects = $wpdb->get_results( "
    SELECT * FROM {$table}
    WHERE status_code = 301
    ORDER BY hits DESC
    LIMIT 10
" );
```

---

### 404 Log Table

**Table Name:** `wp_wpseopilot_404_log`

```sql
CREATE TABLE wp_wpseopilot_404_log (
    id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
    request_uri VARCHAR(255) NOT NULL,
    user_agent TEXT DEFAULT NULL,
    device_label VARCHAR(50) DEFAULT NULL,
    hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 1,
    last_seen DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY request_uri (request_uri)
)
```

**Direct Query Example:**

```php
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_404_log';

$top_404s = $wpdb->get_results( "
    SELECT request_uri, hits, last_seen
    FROM {$table}
    ORDER BY hits DESC
    LIMIT 20
" );
```

---

## REST API Integration

All SEO meta fields are exposed via the WordPress REST API.

### Accessing Post Meta

**Endpoint:** `GET /wp-json/wp/v2/posts/{id}`

**Response:**

```json
{
  "id": 123,
  "title": { "rendered": "Post Title" },
  "meta": {
    "_wpseopilot_meta": {
      "title": "Custom SEO Title",
      "description": "Custom meta description",
      "canonical": "",
      "noindex": "0",
      "nofollow": "0",
      "og_image": "https://example.com/image.jpg"
    }
  }
}
```

### Updating Post Meta

**Endpoint:** `POST /wp-json/wp/v2/posts/{id}`

**Request:**

```json
{
  "meta": {
    "_wpseopilot_meta": {
      "title": "New SEO Title",
      "description": "New description"
    }
  }
}
```

**JavaScript Example:**

```javascript
wp.apiFetch({
    path: '/wp/v2/posts/123',
    method: 'POST',
    data: {
        meta: {
            _wpseopilot_meta: {
                title: 'New SEO Title',
                description: 'Updated description'
            }
        }
    }
}).then( response => {
    console.log( 'Meta updated!', response );
});
```

**Location:** `includes/class-wpseopilot-service-post-meta.php`

---

## Custom Capabilities

### Built-in Capabilities

| Capability | Default Role | Purpose |
|------------|--------------|---------|
| `manage_options` | Administrator | Most admin pages and settings |
| `manage_seopilot_links` | Administrator | Internal linking management |

### Granting Custom Capabilities

```php
// Grant capability to editors
$role = get_role( 'editor' );
$role->add_cap( 'manage_seopilot_links' );

// Remove capability from administrators
$role = get_role( 'administrator' );
$role->remove_cap( 'manage_seopilot_links' );
```

### Checking Capabilities

```php
if ( current_user_can( 'manage_seopilot_links' ) ) {
    // User can manage internal links
}
```

---

## Feature Toggles

Control which features are enabled using the `wpseopilot_feature_toggle` filter.

### Available Features

| Feature | Default | Description |
|---------|---------|-------------|
| `metabox` | Enabled | Admin meta box display |
| `frontend_head` | Enabled | Meta tag rendering in `<head>` |
| `sitemaps` | Enabled | Sitemap generation |
| `redirects` | Enabled | Redirect manager |
| `llm_txt` | Enabled | LLM.txt generation |

### Example: Disable on Staging

```php
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    if ( wp_get_environment_type() !== 'production' ) {
        // Disable certain features on non-production
        if ( in_array( $feature, [ 'redirects', 'llm_txt' ], true ) ) {
            return false;
        }
    }

    return $enabled;
}, 10, 2 );
```

---

## Best Practices

### 1. Use Namespaced Helpers

Always import helper functions to avoid conflicts:

```php
use function WPSEOPilot\Helpers\get_option;
use function WPSEOPilot\Helpers\replace_template_variables;
```

### 2. Check for Function Existence

When using public functions in themes, check if they exist:

```php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
```

### 3. Validate Filter Returns

Always return the original value if you don't modify it:

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( some_condition() ) {
        return modified_title();
    }

    return $title; // Always return original if no modification
}, 10, 2 );
```

### 4. Use Proper Priorities

Be mindful of filter priorities:

```php
// Run early (before default processing)
add_filter( 'wpseopilot_title', 'my_function', 5, 2 );

// Run late (after other modifications)
add_filter( 'wpseopilot_title', 'my_function', 20, 2 );
```

### 5. Cache Expensive Operations

If your filter does heavy processing, cache the result:

```php
add_filter( 'wpseopilot_og_image', function( $image, $post ) {
    $cache_key = 'custom_og_image_' . $post->ID;
    $cached = wp_cache_get( $cache_key, 'wpseopilot' );

    if ( false !== $cached ) {
        return $cached;
    }

    $custom_image = expensive_operation( $post );
    wp_cache_set( $cache_key, $custom_image, 'wpseopilot', HOUR_IN_SECONDS );

    return $custom_image;
}, 10, 2 );
```

### 6. Sanitize User Input

When creating redirects or modifying metadata programmatically:

```php
$source = sanitize_text_field( $_POST['source'] );
$target = esc_url_raw( $_POST['target'] );

wpseopilot_create_redirect( $source, $target, 301 );
```

### 7. Handle Errors Gracefully

Check for `WP_Error` returns:

```php
$result = wpseopilot_create_redirect( '/old', '/new' );

if ( is_wp_error( $result ) ) {
    wp_die( $result->get_error_message() );
}
```

---

## Advanced Examples

### Custom Post Type Integration

```php
// Register custom post type with SEO support
add_action( 'init', function() {
    register_post_type( 'portfolio', [
        'public' => true,
        'label'  => 'Portfolio',
        'supports' => [ 'title', 'editor', 'thumbnail', 'custom-fields' ]
    ]);
});

// Set custom title template for portfolio
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( get_post_type( $post ) === 'portfolio' ) {
        $client = get_post_meta( $post->ID, 'client_name', true );
        return $title . ' - Client: ' . $client . ' | ' . get_bloginfo( 'name' );
    }

    return $title;
}, 10, 2 );

// Custom schema for portfolio items
add_filter( 'wpseopilot_jsonld', function( $payload, $post ) {
    if ( get_post_type( $post ) === 'portfolio' ) {
        $payload['@graph'][] = [
            '@type' => 'CreativeWork',
            'name' => get_the_title( $post ),
            'creator' => [
                '@type' => 'Organization',
                'name' => get_bloginfo( 'name' )
            ],
            'dateCreated' => get_the_date( 'c', $post )
        ];
    }

    return $payload;
}, 10, 2 );
```

### Multisite Support

```php
// Apply different settings per site in multisite
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_multisite() ) {
        $site_id = get_current_blog_id();

        if ( $site_id === 2 ) {
            // Custom logic for site #2
            return 'Special: ' . $title;
        }
    }

    return $title;
}, 10, 2 );
```

### Conditional Noindex

```php
// Automatically noindex old posts
add_filter( 'wpseopilot_robots_array', function( $directives ) {
    global $post;

    if ( $post && is_singular( 'post' ) ) {
        $post_age_days = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;

        // Noindex posts older than 2 years
        if ( $post_age_days > 730 ) {
            $directives[] = 'noindex';
        }
    }

    return $directives;
});
```

---

## Related Documentation

- **[Filter Reference](FILTERS.md)** - Complete list of all filters
- **[Template Tags](TEMPLATE_TAGS.md)** - Theme integration functions
- **[WP-CLI Commands](WP_CLI.md)** - Command-line tools
- **[Sitemap Configuration](SITEMAPS.md)** - Advanced sitemap customization

---

**For questions or contributions, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
