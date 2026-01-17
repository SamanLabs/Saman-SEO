# Filter Reference

Complete reference for all Saman SEO filters with practical examples.

---

## Table of Contents

- [Global SEO Output](#global-seo-output)
- [Contextual SEO Filters](#contextual-seo-filters)
- [Robots & Indexing](#robots--indexing)
- [Canonical & URL Control](#canonical--url-control)
- [Open Graph](#open-graph)
- [Twitter Cards](#twitter-cards)
- [Schema / Structured Data](#schema--structured-data)
- [Breadcrumbs](#breadcrumbs)
- [XML Sitemaps](#xml-sitemaps)
- [Feature Toggles](#feature-toggles)
- [Social Meta Tags](#social-meta-tags)

---

## Global SEO Output

### `wpseopilot_title`

Modify the SEO title output in the `<title>` tag.

**Parameters:**
- `$title` (string) - The generated title
- `$post` (WP_Post|null) - Current post object if available

**Example:**
```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    // Append site branding to product titles
    if ( is_singular( 'product' ) ) {
        return $title . ' | ' . get_bloginfo( 'name' );
    }
    return $title;
}, 10, 2 );
```

### `wpseopilot_description`

Modify the meta description tag content.

**Parameters:**
- `$description` (string) - The generated description
- `$post` (WP_Post|null) - Current post object if available

**Example:**
```php
add_filter( 'wpseopilot_description', function( $desc, $post ) {
    // Force specific description for homepage
    if ( is_front_page() ) {
        return 'Welcome to our platform - the best solution for your needs.';
    }
    return $desc;
}, 10, 2 );
```

### `wpseopilot_canonical`

Modify or suppress the canonical URL.

**Parameters:**
- `$canonical` (string|false) - The canonical URL

**Example:**
```php
// Remove canonical for specific post types
add_filter( 'wpseopilot_canonical', function( $url ) {
    if ( is_post_type_archive( 'private_docs' ) ) {
        return false; // Suppress canonical
    }
    return $url;
});

// Force HTTPS canonical
add_filter( 'wpseopilot_canonical', function( $url ) {
    return str_replace( 'http://', 'https://', $url );
});
```

### `wpseopilot_robots`

Modify the robots meta tag directives (string format).

**Parameters:**
- `$robots` (string) - Comma-separated robots directives

**Example:**
```php
add_filter( 'wpseopilot_robots', function( $robots ) {
    // Force noindex on staging
    if ( defined( 'WP_ENV' ) && WP_ENV === 'staging' ) {
        return 'noindex, nofollow';
    }
    return $robots;
});
```

---

## Contextual SEO Filters

### Post-Specific Modifications

Modify SEO elements based on post ID, post type, or other contextual data.

**Example:**
```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    // Custom title for specific post
    if ( $post && $post->ID === 123 ) {
        return 'Special Landing Page Title';
    }
    
    // Append category to blog posts
    if ( is_singular( 'post' ) && $post ) {
        $category = get_the_category( $post->ID );
        if ( ! empty( $category ) ) {
            return $title . ' - ' . $category[0]->name;
        }
    }
    
    return $title;
}, 10, 2 );

add_filter( 'wpseopilot_description', function( $desc, $post ) {
    // Pull description from custom field
    if ( $post && get_post_meta( $post->ID, 'custom_seo_desc', true ) ) {
        return get_post_meta( $post->ID, 'custom_seo_desc', true );
    }
    return $desc;
}, 10, 2 );
```

---

## Robots & Indexing

### `wpseopilot_robots_array`

Modify robots directives as an array (more flexible than string format).

**Parameters:**
- `$robots` (array) - Array of robots directives

**Example:**
```php
add_filter( 'wpseopilot_robots_array', function( $robots ) {
    // Noindex all author archives
    if ( is_author() ) {
        $robots[] = 'noindex';
        $robots[] = 'nofollow';
    }
    
    // Remove duplicates and return
    return array_unique( $robots );
});

// Conditional indexing based on custom field
add_filter( 'wpseopilot_robots_array', function( $robots ) {
    if ( is_singular() ) {
        $post = get_queried_object();
        if ( get_post_meta( $post->ID, 'hide_from_search', true ) ) {
            $robots[] = 'noindex';
        }
    }
    return $robots;
});
```

---

## Canonical & URL Control

### Advanced Canonical Manipulation

**Example:**
```php
// Canonical for paginated archives
add_filter( 'wpseopilot_canonical', function( $url ) {
    if ( is_paged() && is_category() ) {
        // Point paginated pages to page 1
        $category = get_queried_object();
        return get_category_link( $category->term_id );
    }
    return $url;
});

// Custom canonical for translated content
add_filter( 'wpseopilot_canonical', function( $url ) {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
        if ( $lang !== 'en' ) {
            // Point to English version
            $post = get_queried_object();
            $en_post_id = pll_get_post( $post->ID, 'en' );
            if ( $en_post_id ) {
                return get_permalink( $en_post_id );
            }
        }
    }
    return $url;
});
```

---

## Open Graph

### Individual OG Filters

```php
// Override OG title
add_filter( 'wpseopilot_og_title', function( $title, $post ) {
    if ( is_singular( 'event' ) ) {
        $event_date = get_post_meta( $post->ID, 'event_date', true );
        return $title . ' - ' . date( 'M j, Y', strtotime( $event_date ) );
    }
    return $title;
}, 10, 2 );

// Override OG description
add_filter( 'wpseopilot_og_description', function( $desc, $post ) {
    if ( is_singular( 'product' ) ) {
        return 'Shop now and save! ' . $desc;
    }
    return $desc;
}, 10, 2 );

// Override OG URL
add_filter( 'wpseopilot_og_url', function( $url, $post ) {
    // Add tracking parameters
    return add_query_arg( 'utm_source', 'facebook', $url );
}, 10, 2 );

// Override OG type
add_filter( 'wpseopilot_og_type', function( $type, $post ) {
    if ( is_singular( 'event' ) ) {
        return 'event';
    }
    if ( is_singular( 'product' ) ) {
        return 'product';
    }
    return $type;
}, 10, 2 );

// Override OG image
add_filter( 'wpseopilot_og_image', function( $image, $post ) {
    // Use custom field image
    if ( $post ) {
        $custom_img = get_post_meta( $post->ID, 'social_share_image', true );
        if ( $custom_img ) {
            return $custom_img;
        }
    }
    return $image;
}, 10, 2 );
```

---

## Twitter Cards

```php
// Override Twitter title
add_filter( 'wpseopilot_twitter_title', function( $title ) {
    // Shorten for Twitter's display
    return wp_trim_words( $title, 10, '...' );
});

// Override Twitter description
add_filter( 'wpseopilot_twitter_description', function( $desc ) {
    // Ensure under 200 characters
    return mb_substr( $desc, 0, 200 );
});

// Override Twitter image
add_filter( 'wpseopilot_twitter_image', function( $img ) {
    if ( empty( $img ) ) {
        return get_template_directory_uri() . '/assets/default-twitter-card.jpg';
    }
    return $img;
});
```

---

## Schema / Structured Data

### `wpseopilot_jsonld`

Filter the entire structured data graph before output.

**Example:**
```php
add_filter( 'wpseopilot_jsonld', function( $graph ) {
    // Add organization schema
    $graph[] = [
        '@type' => 'Organization',
        '@id' => home_url( '/#organization' ),
        'name' => get_bloginfo( 'name' ),
        'url' => home_url( '/' ),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => get_template_directory_uri() . '/assets/logo.png',
        ],
        'sameAs' => [
            'https://facebook.com/yourpage',
            'https://twitter.com/yourhandle',
        ],
    ];
    
    return $graph;
});
```

### `wpseopilot_schema_webpage`

Modify WebPage schema specifically.

**Example:**
```php
add_filter( 'wpseopilot_schema_webpage', function( $data ) {
    if ( is_singular( 'tutorial' ) ) {
        $data['@type'] = 'TechArticle';
        $data['proficiencyLevel'] = 'Beginner';
    }
    return $data;
});
```

### `wpseopilot_schema_article`

Modify Article schema specifically.

**Example:**
```php
add_filter( 'wpseopilot_schema_article', function( $data ) {
    $post = get_queried_object();
    
    // Add word count
    $data['wordCount'] = str_word_count( strip_tags( $post->post_content ) );
    
    // Add reading time
    $reading_time = ceil( $data['wordCount'] / 200 );
    $data['timeRequired'] = 'PT' . $reading_time . 'M';
    
    return $data;
});
```

---

## Breadcrumbs

### `wpseopilot_breadcrumb_links`

Modify the breadcrumb trail array.

**Parameters:**
- `$links` (array) - Array of breadcrumb items, each containing `title` and `url`

**Example:**
```php
add_filter( 'wpseopilot_breadcrumb_links', function( $links ) {
    // Insert custom breadcrumb after home
    array_splice( $links, 1, 0, [
        [
            'title' => 'Shop',
            'url' => home_url( '/shop/' ),
        ],
    ]);
    
    return $links;
});

// Remove category from breadcrumbs
add_filter( 'wpseopilot_breadcrumb_links', function( $links ) {
    return array_filter( $links, function( $link ) {
        return ! str_contains( $link['url'], '/category/' );
    });
});
```

---

## XML Sitemaps

### `wpseopilot_sitemap_map`

Modify which post types, taxonomies, or custom groups appear in the sitemap index.

**Example:**
```php
add_filter( 'wpseopilot_sitemap_map', function( $map ) {
    // Remove private post type
    foreach ( $map as $key => $group ) {
        if ( 'private_docs' === $group['subtype'] ) {
            unset( $map[ $key ] );
        }
    }
    
    // Add custom sitemap group
    $map[] = [
        'type' => 'custom',
        'subtype' => 'landing_pages',
        'loc' => home_url( '/landing-sitemap.xml' ),
    ];
    
    return $map;
});
```

### `wpseopilot_sitemap_post_query_args`

Modify WP_Query arguments for sitemap generation.

**Parameters:**
- `$args` (array) - WP_Query arguments
- `$post_type` (string) - Current post type

**Example:**
```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    // Exclude specific posts
    if ( 'post' === $post_type ) {
        $args['post__not_in'] = [ 123, 456, 789 ];
    }
    
    // Only include posts from last year
    if ( 'news' === $post_type ) {
        $args['date_query'] = [
            [
                'after' => '1 year ago',
            ],
        ];
    }
    
    return $args;
}, 10, 2 );
```

### `wpseopilot_sitemap_entry`

Modify individual sitemap entries (priority, changefreq, images, etc.).

**Parameters:**
- `$entry` (array) - Sitemap entry data
- `$post_id` (int) - Post ID
- `$post_type` (string) - Post type

**Example:**
```php
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    // High priority for featured posts
    if ( get_post_meta( $post_id, 'featured', true ) ) {
        $entry['priority'] = 1.0;
        $entry['changefreq'] = 'daily';
    }
    
    // Add custom image
    if ( 'product' === $post_type ) {
        $gallery = get_post_meta( $post_id, 'product_gallery', true );
        if ( ! empty( $gallery ) ) {
            foreach ( $gallery as $image_id ) {
                $entry['image:image'][] = [
                    'image:loc' => wp_get_attachment_url( $image_id ),
                    'image:title' => get_the_title( $post_id ),
                ];
            }
        }
    }
    
    return $entry;
}, 10, 3 );
```

### `wpseopilot_sitemap_images`

Customize image entries for a specific post.

**Parameters:**
- `$images` (array) - Array of image data
- `$post_id` (int) - Post ID

**Example:**
```php
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
    // Add CDN version of featured image
    $featured_id = get_post_thumbnail_id( $post_id );
    if ( $featured_id ) {
        $images[] = [
            'image:loc' => 'https://cdn.example.com/images/' . basename( get_attached_file( $featured_id ) ),
            'image:caption' => get_the_title( $post_id ),
        ];
    }
    
    return $images;
}, 10, 2 );
```

### `wpseopilot_sitemap_max_urls`

Change the maximum number of URLs per sitemap page.

**Example:**
```php
add_filter( 'wpseopilot_sitemap_max_urls', function( $max ) {
    return 500; // Reduce from default 1000
});
```

### `wpseopilot_sitemap_excluded_terms`

Exclude specific taxonomy terms from sitemaps.

**Example:**
```php
add_filter( 'wpseopilot_sitemap_excluded_terms', function( $excluded ) {
    $excluded[] = 'uncategorized';
    $excluded[] = 'private';
    return $excluded;
});
```

---

## Feature Toggles

### `wpseopilot_feature_toggle`

Enable or disable specific plugin features.

**Parameters:**
- `$enabled` (bool) - Current state
- `$feature` (string) - Feature key

**Available Features:**
- `frontend_head` - SEO tag output in `<head>`
- `metabox` - Post editor meta boxes
- `redirects` - Redirect manager
- `sitemaps` - Sitemap generation

**Example:**
```php
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    // Disable sitemaps (using another plugin)
    if ( 'sitemaps' === $feature ) {
        return false;
    }
    
    // Disable frontend output on staging
    if ( 'frontend_head' === $feature && defined( 'WP_ENV' ) && WP_ENV === 'staging' ) {
        return false;
    }
    
    return $enabled;
}, 10, 2 );
```

---

## Social Meta Tags

### `wpseopilot_social_tags`

Filter the complete social meta tag array (supports duplicates).

**Example:**
```php
add_filter( 'wpseopilot_social_tags', function( $tags ) {
    // Add multiple OG images
    $tags['og:image'] = array_filter([
        $tags['og:image'] ?? '',
        'https://cdn.example.com/secondary.jpg',
        'https://cdn.example.com/tertiary.jpg',
    ]);
    
    // Add image alt text
    $tags[] = [
        'property' => 'og:image:alt',
        'content' => 'Product showcase image',
    ];
    
    // Add custom meta tag
    $tags[] = [
        'property' => 'fb:app_id',
        'content' => '1234567890',
    ];
    
    return $tags;
});
```

### `wpseopilot_social_multi_tags`

Control which social tags can appear multiple times.

**Example:**
```php
add_filter( 'wpseopilot_social_multi_tags', function( $multi_tags ) {
    // Allow multiple authors
    $multi_tags[] = 'article:author';
    return $multi_tags;
});
```

---

## Advanced Examples

### Custom Data Integration

Pull from custom database table for SEO data:

```php
function get_custom_seo_data( $post_id ) {
    static $cache = [];
    
    if ( isset( $cache[ $post_id ] ) ) {
        return $cache[ $post_id ];
    }
    
    global $wpdb;
    $table = $wpdb->prefix . 'custom_seo';
    
    $cache[ $post_id ] = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT title, description, image FROM {$table} WHERE post_id = %d",
            $post_id
        ),
        ARRAY_A
    );
    
    return $cache[ $post_id ];
}

add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( ! $post ) return $title;
    
    $custom = get_custom_seo_data( $post->ID );
    return ! empty( $custom['title'] ) ? $custom['title'] : $title;
}, 10, 2 );

add_filter( 'wpseopilot_og_image', function( $image, $post ) {
    if ( ! $post ) return $image;
    
    $custom = get_custom_seo_data( $post->ID );
    return ! empty( $custom['image'] ) ? $custom['image'] : $image;
}, 10, 2 );
```

### Multilingual SEO

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
        $translations = [
            'fr' => ' | Site Français',
            'es' => ' | Sitio Español',
            'de' => ' | Deutsche Seite',
        ];
        
        if ( isset( $translations[ $lang ] ) ) {
            return $title . $translations[ $lang ];
        }
    }
    return $title;
}, 10, 2 );
```

---

## Filter Priority Guide

When multiple filters may conflict, use priority values strategically:

- **1-5**: Early modifications, foundational changes
- **10**: Default priority (most filters)
- **15-20**: Override other plugins
- **25-50**: Final transformations
- **100+**: Last-resort overrides

**Example:**
```php
// Run before most other filters
add_filter( 'wpseopilot_title', 'my_early_title_mod', 5, 2 );

// Run after most other filters
add_filter( 'wpseopilot_title', 'my_final_title_mod', 50, 2 );
```
