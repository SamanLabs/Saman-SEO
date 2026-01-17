# Filter Reference

Complete documentation of all filter hooks available in Saman SEO with practical examples.

---

## Table of Contents

- [Meta Tag Filters](#meta-tag-filters)
- [Social Media Filters](#social-media-filters)
- [Structured Data Filters](#structured-data-filters)
- [Sitemap Filters](#sitemap-filters)
- [Breadcrumb Filters](#breadcrumb-filters)
- [Internal Linking Filters](#internal-linking-filters)
- [Score & Analysis Filters](#score--analysis-filters)
- [Feature Control Filters](#feature-control-filters)
- [Content Filters](#content-filters)

---

## Meta Tag Filters

### `wpseopilot_title`

Filter the page title before output in `<title>` tag.

**Parameters:**
- `$title` (string) - The generated title
- `$post` (WP_Post|null) - Current post object (null on archives/homepage)

**File:** `includes/class-wpseopilot-service-frontend.php:64, 305, 665`

**Examples:**

```php
// Add suffix to all product titles
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( $post && get_post_type( $post ) === 'product' ) {
        return $title . ' - Buy Online';
    }
    return $title;
}, 10, 2 );

// Customize homepage title
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_front_page() ) {
        return 'Welcome to ' . get_bloginfo( 'name' ) . ' - ' . get_bloginfo( 'description' );
    }
    return $title;
}, 10, 2 );

// Add category to post titles
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_single() && $post ) {
        $categories = get_the_category( $post->ID );
        if ( ! empty( $categories ) ) {
            return $categories[0]->name . ': ' . $title;
        }
    }
    return $title;
}, 10, 2 );
```

---

### `wpseopilot_description`

Filter the meta description before output.

**Parameters:**
- `$description` (string) - The generated description
- `$post` (WP_Post) - Current post object

**File:** `includes/class-wpseopilot-service-frontend.php:100`

**Examples:**

```php
// Append CTA to all product descriptions
add_filter( 'wpseopilot_description', function( $description, $post ) {
    if ( get_post_type( $post ) === 'product' ) {
        return $description . ' Free shipping on orders over $50.';
    }
    return $description;
}, 10, 2 );

// Ensure description doesn't exceed 155 characters
add_filter( 'wpseopilot_description', function( $description, $post ) {
    if ( strlen( $description ) > 155 ) {
        return substr( $description, 0, 152 ) . '...';
    }
    return $description;
}, 10, 2 );

// Add location to local business descriptions
add_filter( 'wpseopilot_description', function( $description, $post ) {
    if ( get_post_type( $post ) === 'location' ) {
        $city = get_post_meta( $post->ID, 'city', true );
        return $description . ' Located in ' . $city . '.';
    }
    return $description;
}, 10, 2 );
```

---

### `wpseopilot_canonical`

Filter the canonical URL before output.

**Parameters:**
- `$canonical` (string) - The canonical URL
- `$post` (WP_Post) - Current post object

**File:** `includes/class-wpseopilot-service-frontend.php:103, 170`

**Examples:**

```php
// Force HTTPS on all canonical URLs
add_filter( 'wpseopilot_canonical', function( $canonical, $post ) {
    return str_replace( 'http://', 'https://', $canonical );
}, 10, 2 );

// Point duplicate content to main version
add_filter( 'wpseopilot_canonical', function( $canonical, $post ) {
    // If this is a variation, point to parent product
    $parent_id = get_post_meta( $post->ID, '_parent_product', true );
    if ( $parent_id ) {
        return get_permalink( $parent_id );
    }
    return $canonical;
}, 10, 2 );

// Use custom domain for canonical
add_filter( 'wpseopilot_canonical', function( $canonical, $post ) {
    return str_replace( 'www.example.com', 'example.com', $canonical );
}, 10, 2 );
```

---

### `wpseopilot_keywords`

Filter meta keywords (legacy, not widely used).

**Parameters:**
- `$keywords` (string) - Comma-separated keywords
- `$post` (WP_Post) - Current post object

**File:** `includes/class-wpseopilot-service-frontend.php:132`

**Example:**

```php
// Add post tags as keywords
add_filter( 'wpseopilot_keywords', function( $keywords, $post ) {
    $tags = get_the_tags( $post->ID );
    if ( $tags ) {
        $tag_names = wp_list_pluck( $tags, 'name' );
        $keywords .= ', ' . implode( ', ', $tag_names );
    }
    return $keywords;
}, 10, 2 );
```

---

### `wpseopilot_robots_array`

Filter the robots directives as an array before joining.

**Parameters:**
- `$directives` (array) - Array of directives (e.g., `['index', 'follow']`)

**File:** `includes/class-wpseopilot-service-frontend.php:512`

**Examples:**

```php
// Noindex all posts older than 2 years
add_filter( 'wpseopilot_robots_array', function( $directives ) {
    global $post;

    if ( $post && is_singular( 'post' ) ) {
        $post_age = ( time() - strtotime( $post->post_date ) ) / DAY_IN_SECONDS;

        if ( $post_age > 730 ) {
            $directives[] = 'noindex';
        }
    }

    return array_unique( $directives );
});

// Add max-snippet directive
add_filter( 'wpseopilot_robots_array', function( $directives ) {
    $directives[] = 'max-snippet:160';
    $directives[] = 'max-image-preview:large';
    $directives[] = 'max-video-preview:-1';

    return $directives;
});
```

---

### `wpseopilot_robots`

Filter the final robots meta tag content string.

**Parameters:**
- `$robots` (string) - Comma-separated robots directives

**File:** `includes/class-wpseopilot-service-frontend.php:521`

**Examples:**

```php
// Force noindex on staging
add_filter( 'wpseopilot_robots', function( $robots ) {
    if ( wp_get_environment_type() === 'staging' ) {
        return 'noindex, nofollow';
    }
    return $robots;
});

// Remove nofollow but keep other directives
add_filter( 'wpseopilot_robots', function( $robots ) {
    return str_replace( 'nofollow', '', $robots );
});
```

---

## Social Media Filters

### `wpseopilot_og_url`

Filter the Open Graph URL tag.

**Parameters:**
- `$url` (string) - OG URL
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:173`

**Example:**

```php
// Use custom domain for OG URLs
add_filter( 'wpseopilot_og_url', function( $url, $post ) {
    return str_replace( 'staging.example.com', 'example.com', $url );
}, 10, 2 );
```

---

### `wpseopilot_og_title`

Filter the Open Graph title tag.

**Parameters:**
- `$title` (string) - OG title
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:194`

**Examples:**

```php
// Prepend category to OG title
add_filter( 'wpseopilot_og_title', function( $title, $post ) {
    if ( is_single() ) {
        $categories = get_the_category( $post->ID );
        if ( ! empty( $categories ) ) {
            return $categories[0]->name . ' - ' . $title;
        }
    }
    return $title;
}, 10, 2 );

// Truncate long titles for better social display
add_filter( 'wpseopilot_og_title', function( $title, $post ) {
    if ( strlen( $title ) > 60 ) {
        return substr( $title, 0, 57 ) . '...';
    }
    return $title;
}, 10, 2 );
```

---

### `wpseopilot_og_description`

Filter the Open Graph description tag.

**Parameters:**
- `$description` (string) - OG description
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:218`

**Example:**

```php
// Use excerpt for OG description if no custom description
add_filter( 'wpseopilot_og_description', function( $description, $post ) {
    if ( empty( $description ) && has_excerpt( $post ) ) {
        return wp_trim_words( get_the_excerpt( $post ), 30 );
    }
    return $description;
}, 10, 2 );
```

---

### `wpseopilot_og_type`

Filter the Open Graph type tag.

**Parameters:**
- `$og_type` (string) - OG type (default: 'website' or 'article')
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:259`

**Examples:**

```php
// Set video type for video posts
add_filter( 'wpseopilot_og_type', function( $og_type, $post ) {
    if ( has_post_format( 'video', $post ) ) {
        return 'video.other';
    }
    return $og_type;
}, 10, 2 );

// Set product type for WooCommerce products
add_filter( 'wpseopilot_og_type', function( $og_type, $post ) {
    if ( get_post_type( $post ) === 'product' ) {
        return 'product';
    }
    return $og_type;
}, 10, 2 );
```

---

### `wpseopilot_og_image`

Filter the Open Graph image URL.

**Parameters:**
- `$image` (string) - Image URL
- `$post` (WP_Post) - Current post
- `$meta` (array) - Post meta data
- `$defaults` (array) - Default settings

**File:** `includes/class-wpseopilot-service-frontend.php:587`

**Examples:**

```php
// Use first gallery image if no featured image
add_filter( 'wpseopilot_og_image', function( $image, $post, $meta, $defaults ) {
    if ( empty( $image ) ) {
        $gallery = get_post_gallery_images( $post );
        if ( ! empty( $gallery ) ) {
            return $gallery[0];
        }
    }
    return $image;
}, 10, 4 );

// Use CDN URL for images
add_filter( 'wpseopilot_og_image', function( $image, $post, $meta, $defaults ) {
    return str_replace( 'example.com', 'cdn.example.com', $image );
}, 10, 4 );

// Use specific image for specific post
add_filter( 'wpseopilot_og_image', function( $image, $post, $meta, $defaults ) {
    if ( $post->ID === 42 ) {
        return 'https://cdn.example.com/special-promo.jpg';
    }
    return $image;
}, 10, 4 );
```

---

### `wpseopilot_twitter_title`

Filter the Twitter Card title.

**Parameters:**
- `$title` (string) - Twitter title
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:238`

**Example:**

```php
// Keep Twitter titles short
add_filter( 'wpseopilot_twitter_title', function( $title, $post ) {
    if ( strlen( $title ) > 55 ) {
        return substr( $title, 0, 52 ) . '...';
    }
    return $title;
}, 10, 2 );
```

---

### `wpseopilot_twitter_description`

Filter the Twitter Card description.

**Parameters:**
- `$description` (string) - Twitter description
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:239`

**Example:**

```php
// Shorten Twitter descriptions
add_filter( 'wpseopilot_twitter_description', function( $description, $post ) {
    return wp_trim_words( $description, 25 );
}, 10, 2 );
```

---

### `wpseopilot_twitter_image`

Filter the Twitter Card image URL.

**Parameters:**
- `$image` (string) - Image URL
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:243`

**Example:**

```php
// Use square image for Twitter
add_filter( 'wpseopilot_twitter_image', function( $image, $post ) {
    $square_image = get_post_meta( $post->ID, '_square_image', true );
    return $square_image ?: $image;
}, 10, 2 );
```

---

### `wpseopilot_social_tags`

Filter all social meta tags at once.

**Parameters:**
- `$tags` (array) - Associative array of tag name => content
- `$post` (WP_Post) - Current post
- `$meta` (array) - Post meta data
- `$defaults` (array) - Default settings

**File:** `includes/class-wpseopilot-service-frontend.php:274`

**Examples:**

```php
// Add custom Facebook tags
add_filter( 'wpseopilot_social_tags', function( $tags, $post, $meta, $defaults ) {
    $tags['fb:app_id'] = '1234567890';
    $tags['fb:pages'] = '9876543210';

    return $tags;
}, 10, 4 );

// Add article tags for posts
add_filter( 'wpseopilot_social_tags', function( $tags, $post, $meta, $defaults ) {
    if ( is_single() && get_post_type( $post ) === 'post' ) {
        $tags['article:published_time'] = get_the_date( 'c', $post );
        $tags['article:modified_time'] = get_the_modified_date( 'c', $post );

        $author = get_userdata( $post->post_author );
        $tags['article:author'] = get_author_posts_url( $author->ID );
    }

    return $tags;
}, 10, 4 );

// Remove certain tags
add_filter( 'wpseopilot_social_tags', function( $tags, $post, $meta, $defaults ) {
    unset( $tags['twitter:card'] );
    return $tags;
}, 10, 4 );
```

---

### `wpseopilot_social_multi_tags`

Filter multi-value social tags (tags that can appear multiple times).

**Parameters:**
- `$multi` (array) - Array of multi-value tags

**File:** `includes/class-wpseopilot-service-frontend.php:747`

**Example:**

```php
// Add multiple article:tag values
add_filter( 'wpseopilot_social_multi_tags', function( $multi ) {
    global $post;

    if ( is_single() ) {
        $tags = get_the_tags( $post->ID );
        if ( $tags ) {
            foreach ( $tags as $tag ) {
                $multi[] = [ 'article:tag', $tag->name ];
            }
        }
    }

    return $multi;
});
```

---

## Structured Data Filters

### `wpseopilot_jsonld`

Filter the complete JSON-LD output before rendering.

**Parameters:**
- `$payload` (array) - Complete JSON-LD schema array
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-frontend.php:328`

**Examples:**

```php
// Add Product schema to product posts
add_filter( 'wpseopilot_jsonld', function( $payload, $post ) {
    if ( get_post_type( $post ) === 'product' ) {
        $price = get_post_meta( $post->ID, '_price', true );

        $payload['@graph'][] = [
            '@type' => 'Product',
            'name' => get_the_title( $post ),
            'description' => get_the_excerpt( $post ),
            'image' => get_the_post_thumbnail_url( $post, 'full' ),
            'offers' => [
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => 'USD',
                'availability' => 'https://schema.org/InStock'
            ]
        ];
    }

    return $payload;
}, 10, 2 );

// Add FAQ schema
add_filter( 'wpseopilot_jsonld', function( $payload, $post ) {
    $faqs = get_post_meta( $post->ID, '_faqs', true );

    if ( $faqs ) {
        $entities = [];

        foreach ( $faqs as $faq ) {
            $entities[] = [
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $faq['answer']
                ]
            ];
        }

        $payload['@graph'][] = [
            '@type' => 'FAQPage',
            'mainEntity' => $entities
        ];
    }

    return $payload;
}, 10, 2 );
```

---

### `wpseopilot_schema_webpage`

Filter the WebPage schema specifically.

**Parameters:**
- `$schema` (array) - WebPage schema object
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-jsonld.php:75`

**Example:**

```php
// Add breadcrumb to WebPage schema
add_filter( 'wpseopilot_schema_webpage', function( $schema, $post ) {
    $schema['breadcrumb'] = [
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => home_url( '/' )
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => get_the_title( $post )
            ]
        ]
    ];

    return $schema;
}, 10, 2 );
```

---

### `wpseopilot_schema_article`

Filter the Article schema for posts.

**Parameters:**
- `$schema` (array) - Article schema object
- `$post` (WP_Post) - Current post

**File:** `includes/class-wpseopilot-service-jsonld.php:94`

**Examples:**

```php
// Add author details to Article schema
add_filter( 'wpseopilot_schema_article', function( $schema, $post ) {
    $author = get_userdata( $post->post_author );

    $schema['author'] = [
        '@type' => 'Person',
        'name' => $author->display_name,
        'url' => get_author_posts_url( $author->ID ),
        'description' => get_the_author_meta( 'description', $author->ID )
    ];

    return $schema;
}, 10, 2 );

// Add word count and reading time
add_filter( 'wpseopilot_schema_article', function( $schema, $post ) {
    $content = get_post_field( 'post_content', $post );
    $word_count = str_word_count( strip_tags( $content ) );

    $schema['wordCount'] = $word_count;
    $schema['timeRequired'] = 'PT' . ceil( $word_count / 200 ) . 'M';

    return $schema;
}, 10, 2 );
```

---

### `wpseopilot_jsonld_graph`

Filter the complete JSON-LD @graph array.

**Parameters:**
- `$graph` (array) - Complete @graph array

**File:** `includes/class-wpseopilot-service-jsonld.php:102`

**Example:**

```php
// Add global Organization schema to all pages
add_filter( 'wpseopilot_jsonld_graph', function( $graph ) {
    $graph[] = [
        '@type' => 'Organization',
        'name' => get_bloginfo( 'name' ),
        'url' => home_url( '/' ),
        'logo' => get_theme_mod( 'custom_logo' ),
        'sameAs' => [
            'https://www.facebook.com/example',
            'https://twitter.com/example',
            'https://www.linkedin.com/company/example'
        ]
    ];

    return $graph;
});
```

---

## Sitemap Filters

### `wpseopilot_sitemap_entry`

Filter individual sitemap entry data.

**Parameters:**
- `$entry` (array) - Entry data (loc, lastmod, changefreq, priority, images)
- `$post_id` (int) - Post ID
- `$post_type` (string) - Post type

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:165`

**Examples:**

```php
// Set high priority for featured posts
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    if ( get_post_meta( $post_id, '_is_featured', true ) ) {
        $entry['priority'] = 1.0;
        $entry['changefreq'] = 'daily';
    }

    return $entry;
}, 10, 3 );

// Exclude out-of-stock products
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    if ( $post_type === 'product' ) {
        $stock = get_post_meta( $post_id, '_stock_status', true );

        if ( $stock === 'outofstock' ) {
            return null; // Exclude from sitemap
        }
    }

    return $entry;
}, 10, 3 );
```

---

### `wpseopilot_sitemap_images`

Filter images included in sitemap for a post.

**Parameters:**
- `$images` (array) - Array of image URLs
- `$post_id` (int) - Post ID

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:237`

**Example:**

```php
// Add product gallery images
add_filter( 'wpseopilot_sitemap_images', function( $images, $post_id ) {
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

### `wpseopilot_sitemap_post_query_args`

Filter WP_Query arguments for sitemap generation.

**Parameters:**
- `$args` (array) - Query arguments
- `$post_type` (string) - Post type being queried

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:292`

**Examples:**

```php
// Only include in-stock products
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
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

// Exclude draft and private posts
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    $args['post_status'] = 'publish';
    return $args;
}, 10, 2 );
```

---

### `wpseopilot_sitemap_index_items`

Filter items in the sitemap index.

**Parameters:**
- `$items` (array) - Array of sitemap index items

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:457`

**Example:**

```php
// Add external sitemap to index
add_filter( 'wpseopilot_sitemap_index_items', function( $items ) {
    $items[] = [
        'loc' => 'https://example.com/external-sitemap.xml',
        'lastmod' => date( 'c' )
    ];

    return $items;
});
```

---

### `wpseopilot_sitemap_lastmod`

Filter the lastmod timestamp for sitemap groups.

**Parameters:**
- `$lastmod` (string) - Last modification timestamp
- `$group` (string) - Sitemap group name
- `$page` (int) - Page number

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:777`

**Example:**

```php
// Force current timestamp for frequently updated content
add_filter( 'wpseopilot_sitemap_lastmod', function( $lastmod, $group, $page ) {
    if ( $group === 'post' ) {
        return date( 'c' );
    }

    return $lastmod;
}, 10, 3 );
```

---

### `wpseopilot_disable_core_sitemaps`

Control whether WordPress core sitemaps should be disabled.

**Parameters:**
- `$should_disable` (bool) - Whether to disable core sitemaps

**File:** `includes/class-wpseopilot-service-sitemap-enhancer.php:1174`

**Example:**

```php
// Keep core sitemaps enabled
add_filter( 'wpseopilot_disable_core_sitemaps', '__return_false' );
```

---

## Breadcrumb Filters

### `wpseopilot_breadcrumb_links`

Filter the breadcrumb trail array before rendering.

**Parameters:**
- `$crumbs` (array) - Array of breadcrumb items
- `$post` (WP_Post) - Current post

**Breadcrumb Structure:**
```php
[
    [ 'url' => 'https://example.com', 'label' => 'Home' ],
    [ 'url' => 'https://example.com/category', 'label' => 'Category' ],
    [ 'url' => '', 'label' => 'Current Page' ]
]
```

**File:** `includes/helpers.php:674`

**Examples:**

```php
// Add custom breadcrumb for products
add_filter( 'wpseopilot_breadcrumb_links', function( $crumbs, $post ) {
    if ( get_post_type( $post ) === 'product' ) {
        // Insert "Shop" between Home and product
        array_splice( $crumbs, 1, 0, [
            [ 'url' => home_url( '/shop' ), 'label' => 'Shop' ]
        ]);
    }

    return $crumbs;
}, 10, 2 );

// Remove home breadcrumb
add_filter( 'wpseopilot_breadcrumb_links', function( $crumbs, $post ) {
    array_shift( $crumbs ); // Remove first item (Home)
    return $crumbs;
}, 10, 2 );

// Add parent pages to breadcrumb
add_filter( 'wpseopilot_breadcrumb_links', function( $crumbs, $post ) {
    if ( $post->post_parent ) {
        $parent_crumbs = [];
        $parent_id = $post->post_parent;

        while ( $parent_id ) {
            $parent = get_post( $parent_id );
            array_unshift( $parent_crumbs, [
                'url' => get_permalink( $parent ),
                'label' => get_the_title( $parent )
            ]);
            $parent_id = $parent->post_parent;
        }

        // Insert parent breadcrumbs
        array_splice( $crumbs, 1, 0, $parent_crumbs );
    }

    return $crumbs;
}, 10, 2 );
```

---

## Internal Linking Filters

### `wpseopilot_link_suggestions`

Filter internal link suggestions shown in the post editor.

**Parameters:**
- `$suggestions` (array) - Array of suggested links
- `$post_id` (int) - Current post ID

**File:** `templates/meta-box.php:124`

**Example:**

```php
// Add related posts as suggestions
add_filter( 'wpseopilot_link_suggestions', function( $suggestions, $post_id ) {
    $related = get_post_meta( $post_id, '_related_posts', true );

    if ( $related && is_array( $related ) ) {
        foreach ( $related as $related_id ) {
            $suggestions[] = [
                'url' => get_permalink( $related_id ),
                'title' => get_the_title( $related_id ),
                'excerpt' => get_the_excerpt( $related_id ),
                'keyword' => 'related content'
            ];
        }
    }

    return $suggestions;
}, 10, 2 );
```

---

### `wpseopilot_internal_link_roles`

Filter which user roles can manage internal linking.

**Parameters:**
- `$roles` (array) - Array of role slugs

**File:** `includes/class-wpseopilot-service-internal-linking.php:755`

**Example:**

```php
// Allow editors to manage internal links
add_filter( 'wpseopilot_internal_link_roles', function( $roles ) {
    $roles[] = 'editor';
    $roles[] = 'author';

    return $roles;
});
```

---

## Score & Analysis Filters

### `wpseopilot_seo_score`

Filter the calculated SEO score for a post.

**Parameters:**
- `$result` (array) - Score result with keys: `score`, `issues`, `suggestions`
- `$post` (WP_Post) - Post being scored

**File:** `includes/helpers.php:540`

**Examples:**

```php
// Penalize posts without featured images
add_filter( 'wpseopilot_seo_score', function( $result, $post ) {
    if ( ! has_post_thumbnail( $post ) ) {
        $result['score'] -= 5;
        $result['issues'][] = 'Missing featured image';
        $result['suggestions'][] = 'Add a featured image to improve SEO';
    }

    return $result;
}, 10, 2 );

// Bonus for posts with video
add_filter( 'wpseopilot_seo_score', function( $result, $post ) {
    if ( has_post_format( 'video', $post ) || strpos( $post->post_content, '<video' ) !== false ) {
        $result['score'] += 5;
        $result['suggestions'][] = 'Great! Video content detected.';
    }

    return $result;
}, 10, 2 );

// Check for external links
add_filter( 'wpseopilot_seo_score', function( $result, $post ) {
    preg_match_all( '/<a\s+(?:[^>]*?\s+)?href=(["\'])(.*?)\1/i', $post->post_content, $matches );

    $external_links = 0;
    foreach ( $matches[2] as $url ) {
        if ( strpos( $url, home_url() ) === false && strpos( $url, 'http' ) === 0 ) {
            $external_links++;
        }
    }

    if ( $external_links === 0 ) {
        $result['score'] -= 3;
        $result['suggestions'][] = 'Consider adding relevant external links for authority.';
    }

    return $result;
}, 10, 2 );
```

---

## Feature Control Filters

### `wpseopilot_feature_toggle`

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

**File:** `includes/class-wpseopilot-service-admin-ui.php:27`

**Examples:**

```php
// Disable sitemaps on staging
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    if ( $feature === 'sitemaps' && wp_get_environment_type() === 'staging' ) {
        return false;
    }

    return $enabled;
}, 10, 2 );

// Disable all features except metabox on local
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    if ( wp_get_environment_type() === 'local' && $feature !== 'metabox' ) {
        return false;
    }

    return $enabled;
}, 10, 2 );
```

---

### `wpseopilot_score_post_types`

Filter which post types show SEO score in admin.

**Parameters:**
- `$post_types` (array) - Array of post type slugs

**File:** `includes/class-wpseopilot-service-admin-ui.php:65`

**Example:**

```php
// Add custom post types to scoring
add_filter( 'wpseopilot_score_post_types', function( $post_types ) {
    $post_types[] = 'product';
    $post_types[] = 'portfolio';
    $post_types[] = 'event';

    return $post_types;
});
```

---

## Content Filters

### `wpseopilot_llm_txt_content`

Filter the complete llm.txt file content before output.

**Parameters:**
- `$content` (string) - Generated llm.txt content

**File:** `includes/class-wpseopilot-service-llm-txt-generator.php:176`

**Examples:**

```php
// Add custom sections to llm.txt
add_filter( 'wpseopilot_llm_txt_content', function( $content ) {
    $content .= "\n\n# Custom Instructions\n";
    $content .= "This site focuses on WordPress development and SEO.\n";
    $content .= "All code examples are in PHP unless otherwise noted.\n";

    return $content;
});

// Add sitemap URL to llm.txt
add_filter( 'wpseopilot_llm_txt_content', function( $content ) {
    $content .= "\n\n# Sitemap\n";
    $content .= home_url( '/wp-sitemap.xml' ) . "\n";

    return $content;
});
```

---

## Filter Priority Best Practices

### Default Priorities

Most Saman SEO filters use priority `10` by default.

### Running Before Plugin Filters

Use priority less than `10`:

```php
add_filter( 'wpseopilot_title', 'my_function', 5, 2 );
```

### Running After Plugin Filters

Use priority greater than `10`:

```php
add_filter( 'wpseopilot_title', 'my_function', 15, 2 );
```

### Running Last

Use a high priority:

```php
add_filter( 'wpseopilot_title', 'my_function', 999, 2 );
```

---

## Common Patterns

### Conditional Filtering

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( ! $post || ! some_condition() ) {
        return $title; // Always return original if no modification
    }

    // Your modification
    return $modified_title;
}, 10, 2 );
```

### Type-Safe Filtering

```php
add_filter( 'wpseopilot_og_image', function( $image, $post, $meta, $defaults ) {
    // Ensure $image is a string
    if ( ! is_string( $image ) ) {
        $image = '';
    }

    // Your logic here

    return $image;
}, 10, 4 );
```

### Debugging Filters

```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    error_log( 'Title before: ' . $title );

    $title = your_modification( $title, $post );

    error_log( 'Title after: ' . $title );

    return $title;
}, 10, 2 );
```

---

## Related Documentation

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Complete developer documentation
- **[Template Tags](TEMPLATE_TAGS.md)** - Theme integration functions
- **[Getting Started](GETTING_STARTED.md)** - Basic plugin usage

---

**For more examples and use cases, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
