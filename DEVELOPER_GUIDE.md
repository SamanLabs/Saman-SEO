# Developer Guide

Comprehensive guide for developers integrating and extending Saman SEO.

---

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [Helper Functions](#helper-functions)
- [Template Tags](#template-tags)
- [Hooks & Filters](#hooks--filters)
- [Database Structure](#database-structure)
- [Custom Integrations](#custom-integrations)
- [Best Practices](#best-practices)

---

## Architecture Overview

Saman SEO follows WordPress coding standards and uses a modular architecture:

```
wp-seo-pilot/
├── includes/
│   ├── class-frontend.php      # <head> tag rendering
│   ├── class-metabox.php       # Post editor integration
│   ├── class-sitemaps.php      # Sitemap generation
│   ├── class-redirects.php     # Redirect handling
│   ├── class-audit.php         # SEO audit functionality
│   └── class-ai.php            # AI integration
├── admin/
│   ├── settings.php            # Admin settings UI
│   └── assets/                 # Admin CSS/JS
├── assets/
│   ├── css/                    # Compiled stylesheets
│   └── less/                   # Less source files
└── cli/
    └── commands.php            # WP-CLI commands
```

### Key Components

**Frontend Output** (`class-frontend.php`)
- Renders SEO meta tags in `<head>`
- Handles template variable replacement
- Manages Open Graph and Twitter Cards
- Outputs JSON-LD structured data

**Meta Box** (`class-metabox.php`)
- Provides per-post SEO fields
- Gutenberg sidebar integration
- Classic editor meta box
- AJAX-powered previews

**Sitemap Manager** (`class-sitemaps.php`)
- XML sitemap generation
- Sitemap index management
- RSS and News sitemaps
- Image inclusion

**Redirect Manager** (`class-redirects.php`)
- 301/302 redirect handling
- 404 logging and monitoring
- Database-backed storage
- Cache integration

---

## Helper Functions

### Create Redirects Programmatically

```php
/**
 * Create a redirect.
 *
 * @param string $source      Path to redirect FROM (e.g. '/old-page').
 * @param string $target      URL to redirect TO (e.g. 'https://example.com/new-page').
 * @param int    $status_code HTTP status code (default 301).
 *
 * @return int|WP_Error       Redirect ID or WP_Error on failure.
 */
function wpseopilot_create_redirect( $source, $target, $status_code = 301 );
```

**Example:**
```php
// During site migration
$old_urls = [
    '/about-us' => '/company/about',
    '/contact' => '/get-in-touch',
    '/blog/old-post' => '/resources/new-post',
];

foreach ( $old_urls as $source => $target ) {
    $result = wpseopilot_create_redirect( $source, $target );
    
    if ( is_wp_error( $result ) ) {
        error_log( sprintf(
            'Failed to create redirect from %s to %s: %s',
            $source,
            $target,
            $result->get_error_message()
        ) );
    }
}
```

### Get SEO Meta Data

```php
/**
 * Get SEO meta value for a post.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key ('title', 'description', 'canonical', etc.).
 *
 * @return string|false   Meta value or false if not set.
 */
function wpseopilot_get_meta( $post_id, $key );
```

**Example:**
```php
$post_id = 123;
$seo_title = wpseopilot_get_meta( $post_id, 'title' );

if ( ! $seo_title ) {
    // Fall back to post title
    $seo_title = get_the_title( $post_id );
}
```

### Update SEO Meta Data

```php
/**
 * Update SEO meta value for a post.
 *
 * @param int    $post_id Post ID.
 * @param string $key     Meta key.
 * @param mixed  $value   Meta value.
 *
 * @return bool           True on success, false on failure.
 */
function wpseopilot_update_meta( $post_id, $key, $value );
```

**Example:**
```php
// Programmatically set SEO metadata
$post_id = wp_insert_post([
    'post_title' => 'New Product Launch',
    'post_content' => '...',
    'post_status' => 'publish',
]);

if ( $post_id ) {
    wpseopilot_update_meta( $post_id, 'title', 'New Product Launch 2024 | Buy Now' );
    wpseopilot_update_meta( $post_id, 'description', 'Discover our latest product with advanced features...' );
    wpseopilot_update_meta( $post_id, 'og_image', 'https://cdn.example.com/product-launch.jpg' );
}
```

---

## Template Tags

### Breadcrumbs

```php
/**
 * Render breadcrumb trail.
 *
 * @param WP_Post|int|null $post Post object, ID, or null for current post.
 * @param bool             $echo Whether to echo or return output.
 *
 * @return string|void         Breadcrumb HTML if $echo is false.
 */
function wpseopilot_breadcrumbs( $post = null, $echo = true );
```

**Usage in Theme:**
```php
<?php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
?>
```

**Shortcode:**
```
[wpseopilot_breadcrumbs]
```

**Custom Styling:**
```css
.wpseopilot-breadcrumbs {
    font-size: 14px;
    color: #666;
}

.wpseopilot-breadcrumbs a {
    color: #0073aa;
    text-decoration: none;
}

.wpseopilot-breadcrumbs a:hover {
    text-decoration: underline;
}

.wpseopilot-breadcrumbs .separator {
    margin: 0 8px;
}
```

---

## Hooks & Filters

### Action Hooks

#### `wpseopilot_before_head_output`

Fires before Saman SEO outputs any `<head>` tags.

```php
add_action( 'wpseopilot_before_head_output', function() {
    // Add custom meta tags
    echo '<meta name="custom-tag" content="value">';
});
```

#### `wpseopilot_after_head_output`

Fires after Saman SEO outputs all `<head>` tags.

```php
add_action( 'wpseopilot_after_head_output', function() {
    // Add verification codes
    echo '<meta name="google-site-verification" content="your-code">';
});
```

#### `wpseopilot_sitemap_generated`

Fires after a sitemap is generated.

**Parameters:**
- `$sitemap_type` (string) - Type of sitemap ('post', 'page', 'category', etc.)

```php
add_action( 'wpseopilot_sitemap_generated', function( $sitemap_type ) {
    // Log sitemap generation
    error_log( "Sitemap generated: {$sitemap_type}" );
    
    // Ping search engines
    if ( 'post' === $sitemap_type ) {
        wp_remote_get( 'https://www.google.com/ping?sitemap=' . urlencode( home_url( '/sitemap_index.xml' ) ) );
    }
});
```

### Filter Hooks

See the **[Filter Reference](FILTERS.md)** for comprehensive filter documentation.

**Most Common Filters:**
- `wpseopilot_title` - Modify SEO title
- `wpseopilot_description` - Modify meta description
- `wpseopilot_canonical` - Modify canonical URL
- `wpseopilot_og_image` - Modify Open Graph image
- `wpseopilot_jsonld` - Modify structured data
- `wpseopilot_sitemap_entry` - Modify sitemap entries

---

## Database Structure

### Post Meta Storage

SEO data is stored in a single serialized meta field:

**Meta Key:** `_wpseopilot_meta`

**Structure:**
```php
[
    'title' => 'Custom SEO Title',
    'description' => 'Custom meta description',
    'canonical' => 'https://example.com/custom-canonical',
    'robots' => 'index, follow',
    'og_image' => 'https://example.com/image.jpg',
    'og_title' => 'Custom OG Title',
    'og_description' => 'Custom OG Description',
    'twitter_image' => 'https://example.com/twitter.jpg',
]
```

**Direct Database Access:**
```php
global $wpdb;

// Get all posts with custom SEO titles
$results = $wpdb->get_results(
    "SELECT post_id, meta_value 
     FROM {$wpdb->postmeta} 
     WHERE meta_key = '_wpseopilot_meta' 
     AND meta_value LIKE '%\"title\"%'"
);

foreach ( $results as $row ) {
    $meta = maybe_unserialize( $row->meta_value );
    if ( ! empty( $meta['title'] ) ) {
        echo "Post {$row->post_id}: {$meta['title']}\n";
    }
}
```

### Redirects Table

**Table Name:** `{prefix}wpseopilot_redirects`

**Schema:**
```sql
CREATE TABLE {prefix}wpseopilot_redirects (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    source varchar(255) NOT NULL,
    target varchar(255) NOT NULL,
    status_code int(3) NOT NULL DEFAULT 301,
    hits bigint(20) unsigned DEFAULT 0,
    created datetime NOT NULL,
    modified datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY source (source)
);
```

**Direct Database Access:**
```php
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_redirects';

// Get most used redirects
$top_redirects = $wpdb->get_results(
    "SELECT source, target, hits 
     FROM {$table} 
     ORDER BY hits DESC 
     LIMIT 10"
);
```

---

## Custom Integrations

### WooCommerce Integration

Add product-specific SEO enhancements:

```php
// Add price to product titles in search results
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( $post->ID );
        if ( $product ) {
            $price = $product->get_price_html();
            return $title . ' - ' . wp_strip_all_tags( $price );
        }
    }
    return $title;
}, 10, 2 );

// Add product schema to structured data
add_filter( 'wpseopilot_jsonld', function( $graph ) {
    if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
        $product = wc_get_product( get_queried_object_id() );
        
        if ( $product ) {
            $graph[] = [
                '@type' => 'Product',
                '@id' => get_permalink() . '#product',
                'name' => $product->get_name(),
                'description' => $product->get_short_description(),
                'image' => wp_get_attachment_url( $product->get_image_id() ),
                'sku' => $product->get_sku(),
                'offers' => [
                    '@type' => 'Offer',
                    'price' => $product->get_price(),
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability' => $product->is_in_stock() ? 'InStock' : 'OutOfStock',
                    'url' => get_permalink(),
                ],
            ];
        }
    }
    
    return $graph;
});
```

### Custom Post Type SEO

Configure SEO for custom post types:

```php
// Register custom post type with SEO support
register_post_type( 'portfolio', [
    'public' => true,
    'label' => 'Portfolio',
    'supports' => [ 'title', 'editor', 'thumbnail' ],
    'has_archive' => true,
    'rewrite' => [ 'slug' => 'work' ],
]);

// Set default SEO template for portfolio items
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_singular( 'portfolio' ) ) {
        $client = get_post_meta( $post->ID, 'client_name', true );
        if ( $client ) {
            return sprintf( '%s - %s | Portfolio', $title, $client );
        }
    }
    return $title;
}, 10, 2 );

// Add portfolio items to sitemap with high priority
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    if ( 'portfolio' === $post_type ) {
        $entry['priority'] = 0.9;
        $entry['changefreq'] = 'monthly';
    }
    return $entry;
}, 10, 3 );
```

### Multilingual SEO (Polylang/WPML)

```php
// Add language-specific title suffix
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
        $suffixes = [
            'fr' => ' | Version Française',
            'es' => ' | Versión en Español',
            'de' => ' | Deutsche Version',
        ];
        
        if ( isset( $suffixes[ $lang ] ) ) {
            return $title . $suffixes[ $lang ];
        }
    }
    return $title;
}, 10, 2 );

// Set canonical to default language version
add_filter( 'wpseopilot_canonical', function( $url ) {
    if ( function_exists( 'pll_current_language' ) && function_exists( 'pll_get_post' ) ) {
        $lang = pll_current_language();
        if ( $lang !== pll_default_language() ) {
            $post = get_queried_object();
            $default_post_id = pll_get_post( $post->ID, pll_default_language() );
            if ( $default_post_id ) {
                return get_permalink( $default_post_id );
            }
        }
    }
    return $url;
});

// Add hreflang tags
add_action( 'wpseopilot_after_head_output', function() {
    if ( function_exists( 'pll_the_languages' ) && is_singular() ) {
        $post = get_queried_object();
        $translations = pll_the_languages([
            'raw' => 1,
            'post_id' => $post->ID,
        ]);
        
        foreach ( $translations as $lang ) {
            printf(
                '<link rel="alternate" hreflang="%s" href="%s" />' . "\n",
                esc_attr( $lang['locale'] ),
                esc_url( $lang['url'] )
            );
        }
    }
});
```

---

## Best Practices

### Performance Optimization

**1. Use Object Caching**
```php
// Cache expensive operations
$cache_key = 'wpseopilot_custom_' . $post_id;
$cached = wp_cache_get( $cache_key );

if ( false === $cached ) {
    // Expensive operation
    $cached = perform_expensive_operation( $post_id );
    wp_cache_set( $cache_key, $cached, '', HOUR_IN_SECONDS );
}

return $cached;
```

**2. Lazy Load Filters**
```php
// Only add filters when needed
if ( is_singular( 'product' ) ) {
    add_filter( 'wpseopilot_title', 'my_product_title_filter', 10, 2 );
    add_filter( 'wpseopilot_jsonld', 'my_product_schema_filter' );
}
```

**3. Database Query Optimization**
```php
// Bad: N+1 query problem
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id ) {
    $custom_data = get_post_meta( $post_id, 'custom_field', true ); // Runs for EVERY post
    // ...
}, 10, 2 );

// Good: Batch query
add_action( 'wpseopilot_sitemap_before_generate', function( $post_ids ) {
    global $custom_data_cache;
    $custom_data_cache = [];
    
    foreach ( $post_ids as $id ) {
        $custom_data_cache[ $id ] = get_post_meta( $id, 'custom_field', true );
    }
});

add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id ) {
    global $custom_data_cache;
    $custom_data = $custom_data_cache[ $post_id ] ?? '';
    // ...
}, 10, 2 );
```

### Security Best Practices

**1. Sanitize Output**
```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    $custom_title = get_post_meta( $post->ID, 'custom_title', true );
    
    // Always sanitize user input
    return ! empty( $custom_title ) ? esc_html( $custom_title ) : $title;
}, 10, 2 );
```

**2. Validate URLs**
```php
add_filter( 'wpseopilot_canonical', function( $url ) {
    $custom_canonical = get_option( 'custom_canonical' );
    
    // Validate URL before using
    if ( ! empty( $custom_canonical ) && filter_var( $custom_canonical, FILTER_VALIDATE_URL ) ) {
        return esc_url_raw( $custom_canonical );
    }
    
    return $url;
});
```

**3. Capability Checks**
```php
add_action( 'admin_init', function() {
    if ( isset( $_POST['wpseopilot_custom_action'] ) ) {
        // Always check capabilities
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Unauthorized' );
        }
        
        // Verify nonce
        if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpseopilot_custom_action' ) ) {
            wp_die( 'Invalid nonce' );
        }
        
        // Process action
    }
});
```

### Code Organization

**Create a Custom Plugin for Your Modifications:**

```php
<?php
/**
 * Plugin Name: My Site SEO Customizations
 * Description: Custom SEO modifications for Saman SEO
 * Version: 1.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class My_SEO_Customizations {
    
    public function __construct() {
        add_filter( 'wpseopilot_title', [ $this, 'custom_title' ], 10, 2 );
        add_filter( 'wpseopilot_jsonld', [ $this, 'custom_schema' ] );
    }
    
    public function custom_title( $title, $post ) {
        // Your custom logic
        return $title;
    }
    
    public function custom_schema( $graph ) {
        // Your custom logic
        return $graph;
    }
}

new My_SEO_Customizations();
```

---

## Debugging

### Enable WP_DEBUG

```php
// wp-config.php
define( 'WP_DEBUG', true );
define( 'WP_DEBUG_LOG', true );
define( 'WP_DEBUG_DISPLAY', false );
```

### Log Filter Output

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    error_log( sprintf(
        'SEO Title for post %d: %s',
        $post ? $post->ID : 0,
        $title
    ) );
    
    return $title;
}, 10, 2 );
```

### Inspect Generated Sitemaps

```php
// Add to functions.php temporarily
add_action( 'template_redirect', function() {
    if ( isset( $_GET['debug_sitemap'] ) && current_user_can( 'manage_options' ) ) {
        header( 'Content-Type: text/plain' );
        
        // Get sitemap data
        $sitemap = new WPSeoPilot_Sitemaps();
        $data = $sitemap->generate_post_sitemap( 'post', 1 );
        
        echo "Sitemap Entry Count: " . count( $data ) . "\n\n";
        print_r( $data );
        exit;
    }
});

// Visit: https://yoursite.com/?debug_sitemap
```

---

## Testing

### Unit Testing Example

```php
class Test_WPSeoPilot_Filters extends WP_UnitTestCase {
    
    public function test_custom_title_filter() {
        $post_id = $this->factory->post->create([
            'post_title' => 'Test Post',
        ]);
        
        add_filter( 'wpseopilot_title', function( $title ) {
            return $title . ' | Custom Suffix';
        });
        
        $title = apply_filters( 'wpseopilot_title', get_the_title( $post_id ), get_post( $post_id ) );
        
        $this->assertEquals( 'Test Post | Custom Suffix', $title );
    }
}
```

---

For more examples and use cases, see the **[Filter Reference](FILTERS.md)**.
