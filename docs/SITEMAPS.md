# Sitemap Configuration

Complete guide to configuring and customizing XML sitemaps in Saman SEO.

---

## Table of Contents

- [Overview](#overview)
- [Basic Configuration](#basic-configuration)
- [Advanced Settings](#advanced-settings)
- [Sitemap Types](#sitemap-types)
- [Filtering & Customization](#filtering--customization)
- [Performance Optimization](#performance-optimization)
- [Troubleshooting](#troubleshooting)

---

## Overview

Saman SEO provides comprehensive XML sitemap functionality that:

- Enhances WordPress core sitemaps with advanced features
- Includes images, taxonomies, and custom post types
- Supports Google News and RSS sitemaps
- Offers granular control over what's included
- Provides filters for complete customization

**Sitemap URL:** `https://yoursite.com/wp-sitemap.xml`

**Location:** `includes/class-wpseopilot-service-sitemap-enhancer.php`, `includes/class-wpseopilot-service-sitemap-settings.php`

---

## Basic Configuration

### Accessing Settings

Navigate to **Saman SEO → Sitemaps** in your WordPress admin.

---

### Enable/Disable Sitemaps

**Option:** `wpseopilot_sitemap_enabled`

```php
// Via admin UI or programmatically
update_option( 'wpseopilot_sitemap_enabled', '1' ); // Enable
update_option( 'wpseopilot_sitemap_enabled', '0' ); // Disable
```

---

### Post Types

**Option:** `wpseopilot_sitemap_post_types`

Select which post types to include in the sitemap:

- Posts
- Pages
- Custom Post Types (Products, Portfolio, etc.)

**Default:** All public post types are included.

**Example:**

```php
update_option( 'wpseopilot_sitemap_post_types', [
    'post',
    'page',
    'product'
]);
```

---

### Taxonomies

**Option:** `wpseopilot_sitemap_taxonomies`

Include category and tag archive pages:

- Categories
- Tags
- Custom Taxonomies

**Example:**

```php
update_option( 'wpseopilot_sitemap_taxonomies', [
    'category',
    'post_tag',
    'product_cat'
]);
```

---

### Max URLs Per Sitemap

**Option:** `wpseopilot_sitemap_max_urls`

Limit the number of URLs per sitemap page (Google recommends max 50,000).

**Default:** 2000

```php
update_option( 'wpseopilot_sitemap_max_urls', 2000 );
```

---

## Advanced Settings

### Sitemap Index

**Option:** `wpseopilot_sitemap_enable_index`

Enable sitemap index for large sites (splits sitemaps by post type).

**Default:** Enabled

```php
update_option( 'wpseopilot_sitemap_enable_index', '1' );
```

---

### Dynamic vs. Static Generation

**Option:** `wpseopilot_sitemap_dynamic_generation`

- **Dynamic (Default):** Sitemaps generated on-the-fly when requested
- **Static:** Pre-generate and cache sitemaps

```php
// Dynamic generation
update_option( 'wpseopilot_sitemap_dynamic_generation', '1' );

// Static generation (caching)
update_option( 'wpseopilot_sitemap_dynamic_generation', '0' );
```

---

### Scheduled Updates

**Option:** `wpseopilot_sitemap_schedule_updates`

Schedule automatic sitemap regeneration:

- `none` - Manual only
- `hourly` - Every hour
- `twicedaily` - Twice per day
- `daily` - Once per day
- `weekly` - Once per week

```php
update_option( 'wpseopilot_sitemap_schedule_updates', 'daily' );
```

---

### Include Author Pages

**Option:** `wpseopilot_sitemap_include_author_pages`

Include author archive pages in sitemap.

```php
update_option( 'wpseopilot_sitemap_include_author_pages', '1' );
```

---

### Include Date Archives

**Option:** `wpseopilot_sitemap_include_date_archives`

Include date-based archive pages (year, month, day).

```php
update_option( 'wpseopilot_sitemap_include_date_archives', '1' );
```

---

### Exclude Images

**Option:** `wpseopilot_sitemap_exclude_images`

Exclude images from sitemap (reduces file size).

**Default:** Images are included

```php
update_option( 'wpseopilot_sitemap_exclude_images', '0' ); // Include images
update_option( 'wpseopilot_sitemap_exclude_images', '1' ); // Exclude images
```

---

## Sitemap Types

### 1. Standard XML Sitemap

**URL:** `https://yoursite.com/wp-sitemap.xml`

Includes all public post types and taxonomies based on settings.

**Example Output:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    <url>
        <loc>https://example.com/sample-post/</loc>
        <lastmod>2025-12-15T10:30:00+00:00</lastmod>
        <changefreq>monthly</changefreq>
        <priority>0.8</priority>
        <image:image>
            <image:loc>https://example.com/wp-content/uploads/image.jpg</image:loc>
        </image:image>
    </url>
</urlset>
```

---

### 2. Google News Sitemap

**Option:** `wpseopilot_sitemap_enable_google_news`

Special sitemap for Google News (includes only recent articles, typically last 2 days).

**URL:** `https://yoursite.com/news-sitemap.xml`

**Configuration:**

```php
// Enable Google News sitemap
update_option( 'wpseopilot_sitemap_enable_google_news', '1' );

// Set publication name
update_option( 'wpseopilot_sitemap_google_news_name', 'My News Site' );

// Set post types to include
update_option( 'wpseopilot_sitemap_google_news_post_types', [ 'post', 'news' ] );
```

**Example Output:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
    <url>
        <loc>https://example.com/breaking-news/</loc>
        <news:news>
            <news:publication>
                <news:name>My News Site</news:name>
                <news:language>en</news:language>
            </news:publication>
            <news:publication_date>2025-12-15T10:30:00+00:00</news:publication_date>
            <news:title>Breaking News Title</news:title>
        </news:news>
    </url>
</urlset>
```

---

### 3. RSS Feed Sitemap

**Option:** `wpseopilot_sitemap_enable_rss`

RSS-style sitemap for feed readers.

**URL:** `https://yoursite.com/rss-sitemap.xml`

```php
update_option( 'wpseopilot_sitemap_enable_rss', '1' );
```

---

### 4. Custom Pages

**Option:** `wpseopilot_sitemap_additional_pages`

Add custom URLs not managed by WordPress.

**Format:** JSON array

```php
$custom_pages = [
    [
        'loc' => 'https://example.com/custom-page/',
        'lastmod' => '2025-12-15',
        'changefreq' => 'weekly',
        'priority' => 0.7
    ],
    [
        'loc' => 'https://example.com/another-page/',
        'lastmod' => '2025-12-10',
        'changefreq' => 'monthly',
        'priority' => 0.5
    ]
];

update_option( 'wpseopilot_sitemap_additional_pages', json_encode( $custom_pages ) );
```

---

## Filtering & Customization

### Filter Individual Sitemap Entries

**Filter:** `wpseopilot_sitemap_entry`

```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    // Set high priority for featured posts
    if ( get_post_meta( $post_id, '_is_featured', true ) ) {
        $entry['priority'] = 1.0;
        $entry['changefreq'] = 'daily';
    }

    // Exclude posts with specific meta
    if ( get_post_meta( $post_id, '_exclude_from_sitemap', true ) ) {
        return null; // Exclude from sitemap
    }

    return $entry;
}, 10, 3 );
```

**Entry Structure:**

```php
[
    'loc' => 'https://example.com/post-url/',
    'lastmod' => '2025-12-15T10:30:00+00:00',
    'changefreq' => 'monthly',
    'priority' => 0.6,
    'images' => [
        'https://example.com/image1.jpg',
        'https://example.com/image2.jpg'
    ]
]
```

---

### Filter Sitemap Images

**Filter:** `wpseopilot_sitemap_images`

```php
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
    // Add product gallery images
    $gallery = get_post_meta( $post_id, '_product_gallery', true );

    if ( $gallery && is_array( $gallery ) ) {
        foreach ( $gallery as $image_id ) {
            $images[] = wp_get_attachment_url( $image_id );
        }
    }

    return $images;
}, 10, 2 );
```

---

### Filter Post Query Arguments

**Filter:** `wpseopilot_sitemap_post_query_args`

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    // Only include in-stock products
    if ( $post_type === 'product' ) {
        $args['meta_query'] = [
            [
                'key' => '_stock_status',
                'value' => 'instock'
            ]
        ];
    }

    // Exclude private posts
    $args['post_status'] = 'publish';

    return $args;
}, 10, 2 );
```

---

### Filter Sitemap Index Items

**Filter:** `wpseopilot_sitemap_index_items`

```php
add_filter( 'wpseopilot_sitemap_index_items', function( $items ) {
    // Add external sitemap to index
    $items[] = [
        'loc' => 'https://example.com/external-sitemap.xml',
        'lastmod' => gmdate( 'c' )
    ];

    return $items;
});
```

---

### Modify Last Modified Timestamp

**Filter:** `wpseopilot_sitemap_lastmod`

```php
add_filter( 'wpseopilot_sitemap_lastmod', function( $lastmod, $group, $page ) {
    // Force current timestamp for posts
    if ( $group === 'post' ) {
        return gmdate( 'c' );
    }

    return $lastmod;
}, 10, 3 );
```

---

### Disable Core WordPress Sitemaps

**Filter:** `wpseopilot_disable_core_sitemaps`

```php
// Keep core sitemaps enabled alongside Saman SEO
add_filter( 'wpseopilot_disable_core_sitemaps', '__return_false' );
```

---

### Custom Sitemap Stylesheet

**Filter:** `wpseopilot_sitemap_stylesheet`

```php
add_filter( 'wpseopilot_sitemap_stylesheet', function( $url ) {
    return get_stylesheet_directory_uri() . '/sitemap.xsl';
});
```

---

## Performance Optimization

### 1. Use Static Generation for Large Sites

For sites with 10,000+ URLs:

```php
update_option( 'wpseopilot_sitemap_dynamic_generation', '0' );
```

---

### 2. Limit URLs Per Page

```php
update_option( 'wpseopilot_sitemap_max_urls', 1000 );
```

---

### 3. Exclude Unnecessary Post Types

```php
update_option( 'wpseopilot_sitemap_post_types', [ 'post', 'page' ] );
```

---

### 4. Exclude Images for Faster Generation

```php
update_option( 'wpseopilot_sitemap_exclude_images', '1' );
```

---

### 5. Cache Sitemap Queries

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    $args['cache_results'] = true;
    $args['update_post_meta_cache'] = false;
    $args['update_post_term_cache'] = false;

    return $args;
}, 10, 2 );
```

---

### 6. Schedule Regular Regeneration

Instead of on-demand generation, pre-generate sitemaps:

```php
update_option( 'wpseopilot_sitemap_schedule_updates', 'hourly' );
```

---

## Manual Regeneration

### Via Admin UI

Navigate to **Saman SEO → Sitemaps** and click **Regenerate Sitemap**.

---

### Via PHP

```php
// Trigger manual regeneration
do_action( 'wpseopilot_sitemap_regenerated' );
```

---

### Via WP-CLI

While not directly available, you can trigger via PHP:

```bash
wp eval 'do_action( "wpseopilot_sitemap_regenerated" );'
```

---

## Submitting Sitemaps to Search Engines

### Google Search Console

1. Go to [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Navigate to **Sitemaps**
4. Enter: `wp-sitemap.xml`
5. Click **Submit**

---

### Bing Webmaster Tools

1. Go to [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Select your site
3. Navigate to **Sitemaps**
4. Enter: `https://yoursite.com/wp-sitemap.xml`
5. Click **Submit**

---

### Automated Ping

Automatically ping search engines when sitemap updates:

```php
add_action( 'wpseopilot_sitemap_regenerated', function() {
    $sitemap_url = home_url( '/wp-sitemap.xml' );

    // Ping Google
    wp_remote_get( 'http://www.google.com/ping?sitemap=' . urlencode( $sitemap_url ) );

    // Ping Bing
    wp_remote_get( 'http://www.bing.com/ping?sitemap=' . urlencode( $sitemap_url ) );
});
```

---

## Troubleshooting

### Sitemap Returns 404

**Causes:**
- Permalinks not flushed
- Plugin not activated
- Conflicting plugin

**Solution:**

```bash
# Flush rewrite rules
wp rewrite flush

# Or via admin: Settings → Permalinks → Save Changes
```

---

### Sitemap Missing Posts

**Check:**

1. Post status is `publish`
2. Post type is included in settings
3. No conflicting `noindex` meta
4. No custom filter excluding posts

**Debug:**

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    error_log( 'Sitemap Query Args: ' . print_r( $args, true ) );
    return $args;
}, 10, 2 );
```

---

### Sitemap Too Large

**Error:** "Sitemap exceeds 50MB or 50,000 URLs"

**Solution:**

1. Reduce `wpseopilot_sitemap_max_urls`:
   ```php
   update_option( 'wpseopilot_sitemap_max_urls', 1000 );
   ```

2. Exclude images:
   ```php
   update_option( 'wpseopilot_sitemap_exclude_images', '1' );
   ```

3. Split by post type (enable sitemap index)

---

### Images Not Appearing

**Check:**

1. `wpseopilot_sitemap_exclude_images` is set to `0`
2. Posts have featured images
3. Image URLs are valid

**Add custom images:**

```php
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
    // Add first gallery image
    $gallery = get_post_gallery_images( $post_id );
    if ( ! empty( $gallery ) ) {
        $images[] = $gallery[0];
    }

    return $images;
}, 10, 2 );
```

---

## Best Practices

### 1. Keep Sitemaps Under 50,000 URLs

Split large sitemaps using sitemap index.

### 2. Include Only Indexable Content

Exclude noindex pages, drafts, and private content.

### 3. Set Realistic Change Frequencies

- Homepage: `daily`
- Blog posts: `monthly`
- Static pages: `yearly`

### 4. Use Priority Wisely

Reserve 1.0 for only the most important pages.

### 5. Include Images for Rich Results

Images improve appearance in search results.

### 6. Update Lastmod Accurately

Only update `lastmod` when content meaningfully changes.

### 7. Test Before Submitting

Validate sitemaps using [XML Sitemap Validator](https://www.xml-sitemaps.com/validate-xml-sitemap.html).

---

## Related Documentation

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Sitemap filters and hooks
- **[Filter Reference](FILTERS.md#sitemap-filters)** - Complete sitemap filter docs
- **[Getting Started](GETTING_STARTED.md)** - Basic sitemap setup

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
