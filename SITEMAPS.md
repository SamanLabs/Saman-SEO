# Sitemap Configuration Guide

Complete guide to configuring and customizing XML sitemaps in Saman SEO.

---

## Overview

Saman SEO replaces WordPress core sitemaps with a comprehensive sitemap system that includes:

- **Sitemap Index**: Main `/sitemap_index.xml` listing all sub-sitemaps
- **Post Type Sitemaps**: Individual sitemaps for each content type
- **Taxonomy Sitemaps**: Sitemaps for categories, tags, and custom taxonomies
- **RSS Sitemap**: Latest posts in RSS format
- **Google News Sitemap**: Optimized for Google News inclusion
- **Additional Pages**: Custom URLs not managed by WordPress

Each entry is enriched with:
- Change frequency (`changefreq`)
- Priority weighting (`priority`)
- Images with captions
- Last modification timestamps
- News and video metadata (when applicable)

---

## Admin Configuration

Navigate to **Saman SEO → Sitemap** to access all sitemap settings.

### Basic Settings

**Schedule Updates**
Automatically regenerate sitemaps on a schedule:
- Hourly
- Twice Daily
- Daily
- Weekly
- No Schedule (manual only)

**Enable Sitemap Indexes**
Use sitemap index files for better organization on large sites. When enabled, individual sitemaps are grouped under `/sitemap_index.xml`.

**Maximum Posts Per Sitemap Page**
Configure how many URLs appear per sitemap page:
- Default: 1,000
- Maximum: 50,000
- Recommendation: 1,000-5,000 for optimal crawler performance

**Dynamically Generate Sitemap**
- **On-demand (Recommended)**: Generate sitemaps when requested
- **Cached**: Pre-generate and store sitemaps, refresh on schedule

### Content Selection

**Post Types**
Select which post types to include:
- Posts (default: enabled)
- Pages (default: enabled)
- Custom Post Types (individually selectable)

**Taxonomies**
Select which taxonomies to include:
- Categories (default: enabled)
- Tags (default: enabled)
- Custom Taxonomies (individually selectable)

**Archive Pages**
- **Include Date Archive Pages**: Add date-based archives (`/2024/01/`, etc.)
- **Include Author Pages**: Add author archive pages

**Media Settings**
- **Exclude Images**: Toggle whether images should be included in sitemap entries

### Additional Sitemaps

**RSS Sitemap**
Enable RSS sitemap at `/sitemap-rss.xml`:
- Includes latest 50 posts
- Standard RSS 2.0 format
- Automatically updates with new content

**Google News Sitemap**
Enable Google News sitemap at `/sitemap-news.xml`:
- Configure publication name
- Select included post types
- Automatically filters posts from last 2 days (Google News requirement)
- Includes required News schema elements

**Additional Pages**
Add custom URLs not managed by WordPress:
1. Enter the URL
2. Set priority (0.0 to 1.0)
3. Save settings

Perfect for:
- External landing pages
- Subdomain content
- Microsite URLs
- Special marketing pages

---

## Available Sitemap URLs

Once configured, access your sitemaps at these URLs:

### Primary Sitemaps
```
https://yoursite.com/sitemap_index.xml    # Main sitemap index
https://yoursite.com/sitemap-rss.xml      # RSS sitemap (if enabled)
https://yoursite.com/sitemap-news.xml     # Google News (if enabled)
```

### Post Type Sitemaps
```
https://yoursite.com/post-sitemap.xml
https://yoursite.com/page-sitemap.xml
https://yoursite.com/product-sitemap.xml  # Custom post types
```

### Taxonomy Sitemaps
```
https://yoursite.com/category-sitemap.xml
https://yoursite.com/post_tag-sitemap.xml
https://yoursite.com/product_cat-sitemap.xml  # Custom taxonomies
```

### Other Sitemaps
```
https://yoursite.com/author-sitemap.xml       # If enabled
https://yoursite.com/additional-sitemap.xml   # If custom pages configured
```

---

## Sitemap Structure

### Post Type Sitemap Entry

```xml
<url>
    <loc>https://yoursite.com/sample-post/</loc>
    <lastmod>2024-01-15T10:30:00+00:00</lastmod>
    <changefreq>weekly</changefreq>
    <priority>0.8</priority>
    <image:image>
        <image:loc>https://yoursite.com/wp-content/uploads/2024/01/image.jpg</image:loc>
        <image:caption>Sample image caption</image:caption>
    </image:image>
</url>
```

### News Sitemap Entry

```xml
<url>
    <loc>https://yoursite.com/breaking-news/</loc>
    <news:news>
        <news:publication>
            <news:name>Your Site Name</news:name>
            <news:language>en</news:language>
        </news:publication>
        <news:publication_date>2024-01-15T10:30:00+00:00</news:publication_date>
        <news:title>Breaking News Title</news:title>
    </news:news>
</url>
```

---

## Programmatic Customization

### Exclude Specific Posts from Sitemaps

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    if ( 'post' === $post_type ) {
        // Exclude specific post IDs
        $args['post__not_in'] = [ 123, 456, 789 ];
    }
    return $args;
}, 10, 2 );
```

### Exclude Posts by Custom Field

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    // Exclude posts marked as "hidden from search"
    $args['meta_query'] = [
        [
            'key' => 'hide_from_sitemap',
            'compare' => 'NOT EXISTS',
        ],
    ];
    return $args;
}, 10, 2 );
```

### Remove Entire Post Type from Sitemap

```php
add_filter( 'wpseopilot_sitemap_map', function( $map ) {
    foreach ( $map as $key => $group ) {
        if ( 'private_docs' === $group['subtype'] ) {
            unset( $map[ $key ] );
        }
    }
    return $map;
});
```

### Customize Individual Entry Data

```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    // High priority for featured posts
    if ( get_post_meta( $post_id, 'featured', true ) ) {
        $entry['priority'] = 1.0;
        $entry['changefreq'] = 'daily';
    }
    
    // Lower priority for old posts
    $post_date = get_post_time( 'U', true, $post_id );
    if ( time() - $post_date > YEAR_IN_SECONDS ) {
        $entry['priority'] = 0.3;
        $entry['changefreq'] = 'yearly';
    }
    
    return $entry;
}, 10, 3 );
```

### Add Custom Images to Sitemap

```php
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
    // Add gallery images
    $gallery = get_post_meta( $post_id, 'product_gallery', true );
    if ( ! empty( $gallery ) ) {
        foreach ( $gallery as $image_id ) {
            $images[] = [
                'image:loc' => wp_get_attachment_url( $image_id ),
                'image:caption' => get_post_meta( $image_id, '_wp_attachment_image_alt', true ),
            ];
        }
    }
    
    // Add CDN fallback image
    $images[] = [
        'image:loc' => 'https://cdn.example.com/fallback.jpg',
        'image:caption' => get_the_title( $post_id ),
    ];
    
    return $images;
}, 10, 2 );
```

### Add News Schema to Specific Posts

```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    // Only add news schema to posts in "Breaking News" category
    if ( has_category( 'breaking-news', $post_id ) ) {
        $entry['news:news'] = [
            'news:publication' => [
                'news:name' => get_bloginfo( 'name' ),
                'news:language' => get_locale(),
            ],
            'news:publication_date' => get_post_time( DATE_W3C, true, $post_id ),
            'news:title' => get_the_title( $post_id ),
        ];
    }
    
    return $entry;
}, 10, 3 );
```

### Add Video Schema

```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    $video_url = get_post_meta( $post_id, 'video_url', true );
    if ( ! empty( $video_url ) ) {
        $entry['video:video'] = [
            'video:content_loc' => esc_url( $video_url ),
            'video:title' => get_the_title( $post_id ),
            'video:description' => get_the_excerpt( $post_id ),
            'video:thumbnail_loc' => get_the_post_thumbnail_url( $post_id, 'large' ),
        ];
    }
    
    return $entry;
}, 10, 3 );
```

---

## Advanced Integration Example

Pull custom data from a database table and integrate it into both sitemap and Open Graph tags:

```php
function wpseopilot_get_quote_row( $post_id ) {
    static $cache = [];
    
    if ( array_key_exists( $post_id, $cache ) ) {
        return $cache[ $post_id ];
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_quotes';
    
    $cache[ $post_id ] = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT quote_text, author, image_url FROM {$table} WHERE post_id = %d",
            $post_id
        ),
        ARRAY_A
    );
    
    return $cache[ $post_id ];
}

// Add quote image to sitemap
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    if ( 'page' !== $post_type ) {
        return $entry;
    }
    
    $quote = wpseopilot_get_quote_row( $post_id );
    if ( empty( $quote ) || empty( $quote['image_url'] ) ) {
        return $entry;
    }
    
    $entry['image:image'] = [
        [
            'image:loc' => esc_url_raw( $quote['image_url'] ),
            'image:caption' => wp_strip_all_tags( $quote['quote_text'] . ' - ' . $quote['author'] ),
        ],
    ];
    
    return $entry;
}, 10, 3 );

// Use same data for Open Graph
add_filter( 'wpseopilot_og_title', function( $title, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $title;
    }
    
    $quote = wpseopilot_get_quote_row( $post->ID );
    if ( empty( $quote ) ) {
        return $title;
    }
    
    return wp_strip_all_tags( $quote['author'] . ' Quote' );
}, 10, 2 );

add_filter( 'wpseopilot_og_description', function( $description, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $description;
    }
    
    $quote = wpseopilot_get_quote_row( $post->ID );
    if ( empty( $quote ) ) {
        return $description;
    }
    
    return wp_strip_all_tags( $quote['quote_text'] );
}, 10, 2 );

add_filter( 'wpseopilot_og_image', function( $image, $post ) {
    if ( ! $post instanceof WP_Post ) {
        return $image;
    }
    
    $quote = wpseopilot_get_quote_row( $post->ID );
    if ( empty( $quote['image_url'] ) ) {
        return $image;
    }
    
    return esc_url_raw( $quote['image_url'] );
}, 10, 2 );
```

---

## Sitemap Optimization Tips

### Performance

1. **Use Caching**: Enable object caching (Redis, Memcached) for database query optimization
2. **Limit Image Inclusion**: Disable images on large sites unless necessary
3. **Reduce Max URLs**: Lower `wpseopilot_sitemap_max_urls` on sites with many posts
4. **Index Organization**: Use sitemap indexes on sites with 10,000+ URLs

### SEO Best Practices

1. **Priority Allocation**:
   - Homepage: 1.0
   - Important pages: 0.8-1.0
   - Regular content: 0.5-0.7
   - Old/archived: 0.3-0.4

2. **Change Frequency**:
   - News/blog: `daily` or `weekly`
   - Static pages: `monthly` or `yearly`
   - Products: `weekly` (if inventory changes)

3. **Image Optimization**:
   - Include only relevant, high-quality images
   - Use descriptive captions
   - Ensure images are accessible (not behind auth)

### Troubleshooting

**Sitemap not updating?**
- Clear object cache
- Check scheduled tasks: `wp cron event list`
- Manually regenerate: Navigate to sitemap settings and click "Regenerate Now"

**404 errors on sitemap URLs?**
- Flush rewrite rules: Settings → Permalinks → Save
- Check `.htaccess` for conflicts
- Verify sitemap feature is enabled

**Missing posts in sitemap?**
- Check post status (must be `publish`)
- Verify post type is enabled in settings
- Check for `post__not_in` exclusions in filters

---

## Submit Sitemaps to Search Engines

### Google Search Console

1. Visit [Google Search Console](https://search.google.com/search-console)
2. Select your property
3. Navigate to Sitemaps
4. Submit: `https://yoursite.com/sitemap_index.xml`

### Bing Webmaster Tools

1. Visit [Bing Webmaster Tools](https://www.bing.com/webmasters)
2. Select your site
3. Navigate to Sitemaps
4. Submit: `https://yoursite.com/sitemap_index.xml`

### Google News

If using News sitemap:
1. Apply for [Google News](https://publishercenter.google.com/)
2. Once approved, submit: `https://yoursite.com/sitemap-news.xml`

---

## robots.txt Configuration

Add sitemap references to your `robots.txt`:

```
User-agent: *
Allow: /

Sitemap: https://yoursite.com/sitemap_index.xml
Sitemap: https://yoursite.com/sitemap-news.xml
```

Edit via **Saman SEO → robots.txt Editor**.
