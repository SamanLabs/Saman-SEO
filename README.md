## WP SEO Pilot

<img width="1280" height="640" alt="WP-SEO-Pilot" src="https://github.com/user-attachments/assets/0eb73154-5c3a-4920-b25f-0d413e4c147b" />

WP SEO Pilot is an all-in-one SEO workflow plugin focused on fast editorial UX and crawler-friendly output.

### Highlights

- Per-post SEO fields stored in `_wpseopilot_meta` (title, description, canonical, robots, OG image) with Gutenberg sidebar + classic meta box.
- Server-rendered `<title>`, meta description, canonical, robots, Open Graph, Twitter Card, and JSON-LD (WebSite, WebPage, Article, Breadcrumb).
- Site-wide defaults for templates, descriptions, social images, robots, hreflang, and module toggles — plus dedicated per-post-type defaults for titles, descriptions, and keywords.
- Snippet + social previews, internal link suggestions, quick actions, and compatibility detection for other SEO plugins.
- Internal Linking: create rules that automatically convert chosen keywords into links across your content, complete with categories, limits, and preview tools.
- AI assistant connects to OpenAI for one-click title & meta description suggestions, with configurable prompts, model selection, and inline editor buttons.
- SEO Audit dashboard with severity graph, issue log, and auto-generated fallback titles/descriptions/tags for posts that are missing metadata.
- Redirect manager (DB table `wpseopilot_redirects`), WP-CLI commands, 404 logging with hashed referrers, sitemap enhancer module, robots.txt editor, and JSON export for quick backups.

### Template Tags & Shortcodes

- `wpseopilot_breadcrumbs( $post = null, $echo = true )` renders breadcrumb trail markup.
- `[wpseopilot_breadcrumbs]` shortcode outputs the same breadcrumb list.

### Filters

- `wpseopilot_title`, `wpseopilot_description`, `wpseopilot_canonical` allow programmatic overrides.
- `wpseopilot_og_title`, `wpseopilot_og_description`, `wpseopilot_og_url`, `wpseopilot_og_type`, `wpseopilot_og_image` let you override Open Graph output per post.
- `wpseopilot_social_tags` filters the full Open Graph + Twitter tag map (supports duplicate tags).
- `wpseopilot_social_multi_tags` controls which social tags may appear multiple times (defaults include `og:image`, `og:video`, `twitter:image`).
- `wpseopilot_keywords` filters the meta keywords tag derived from post-type defaults.
- `wpseopilot_jsonld` filters the Structured Data graph before output.
- `wpseopilot_feature_toggle` receives feature keys (`frontend_head`, `metabox`, `redirects`, `sitemaps`) for compatibility fallbacks.
- `wpseopilot_link_suggestions` lets you augment/replace link suggestions in the meta box.
- `wpseopilot_sitemap_map` adjusts which post types, taxonomies, or custom groups appear in the Yoast-style sitemap structure.
- `wpseopilot_sitemap_max_urls` changes how many URLs are emitted per sitemap page (defaults to 1,000).
- `wpseopilot_sitemap_images` customizes the `<image:image>` entries gathered from featured images, attached media, and inline content.
- `wpseopilot_sitemap_group_count` overrides the calculated object totals per sitemap group.
- `wpseopilot_sitemap_count_statuses` customizes which post statuses count toward post-type totals.
- `wpseopilot_sitemap_lastmod` supplies last modified timestamps without forcing full URL hydration.
- `wpseopilot_sitemap_excluded_terms` removes specific taxonomy slugs (e.g. `uncategorized`) from sitemap queries.
- `wpseopilot_sitemap_index_items` filters the compiled sitemap index entries before rendering.
- `wpseopilot_sitemap_stylesheet` swaps the pretty XSL front-end for `/sitemap_index.xml` and individual sitemaps.
- `wpseopilot_sitemap_redirect` overrides the destination when requests hit WordPress core's `/wp-sitemap*.xml`.

### Developer Helper Functions

You can use these global functions in your theme's `functions.php` or other plugins.

**Create Redirects Programmatically**

Use `wpseopilot_create_redirect()` to register 301 redirects dynamically (e.g., during a migration script or custom event). It handles normalization and cache clearing automatically.

```php
/**
 * Create a redirect.
 *
 * @param string $source      The path to redirect FROM (e.g. '/old-page').
 * @param string $target      The URL to redirect TO (e.g. 'https://example.com/new-page').
 * @param int    $status_code HTTP status code (default 301).
 *
 * @return int|\WP_Error      Returns the new Redirect ID or a WP_Error on failure.
 */
$result = wpseopilot_create_redirect( '/old-url', '/new-url' );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect failed: ' . $result->get_error_message() );
}
```

**Breadcrumbs**

Render a breadcrumb trail anywhere in your theme.

```php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
```

**Get Template Title**

Retrieve the auto-generated SEO title for a post (resolving all variables like `{{post_title}}`).

### SEO Filters Cheatsheet

Below is a complete reference of filters you can use to customize WP SEO Pilot output.

#### 🔧 Global SEO Output
```php
// Change the entire SEO title
add_filter( 'wpseopilot_title', function( $title ) {
    return $title;
});

// Change the meta description
add_filter( 'wpseopilot_description', function( $desc ) {
    return $desc;
});

// Change the canonical URL
add_filter( 'wpseopilot_canonical', function( $canonical ) {
    return $canonical;
});

// Change robots meta string
add_filter( 'wpseopilot_robots', function( $robots ) {
    return 'noindex, nofollow';
});
```

#### 🧠 Contextual SEO Filters
```php
// Modify title by post ID or post type
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_singular( 'post' ) || ( $post && $post->ID === 123 ) ) {
        return 'Custom Title';
    }
    return $title;
}, 10, 2 );

// Modify description by post
add_filter( 'wpseopilot_description', function( $desc, $post ) {
    if ( $post && $post->ID === 42 ) {
        return 'Custom description';
    }
    return $desc;
}, 10, 2 );
```

#### 🧾 Robots and Indexing
```php
// Disable indexing for specific post types
add_filter( 'wpseopilot_robots_array', function( $robots ) {
    if ( is_post_type_archive( 'private' ) ) {
        $robots[] = 'noindex';
    }
    return $robots;
});
```

#### 🔗 Canonical & URL Control
```php
// Remove canonical
add_filter( 'wpseopilot_canonical', '__return_false' );

// Modify canonical dynamically
add_filter( 'wpseopilot_canonical', function( $url ) {
    return home_url( '/custom-url/' );
});
```

#### 🖼️ OpenGraph Filters
```php
add_filter( 'wpseopilot_og_title', function( $title ) { return $title; } );
add_filter( 'wpseopilot_og_description', function( $desc ) { return $desc; } );
add_filter( 'wpseopilot_og_url', function( $url, $post ) { return $url; }, 10, 2 );
add_filter( 'wpseopilot_og_type', function( $type, $post ) { return $type; }, 10, 2 );
add_filter( 'wpseopilot_og_image', function( $img ) { return $img; } );
```

#### 🐦 Twitter Card Filters
```php
add_filter( 'wpseopilot_twitter_title', function( $title ) { return $title; } );
add_filter( 'wpseopilot_twitter_description', function( $desc ) { return $desc; } );
add_filter( 'wpseopilot_twitter_image', function( $img ) { return $img; } );
```

#### 🧠 Schema / Structured Data
```php
// Filter entire schema graph
add_filter( 'wpseopilot_jsonld', function( $graph ) {
    return $graph;
});

// Filter specific schema pieces
add_filter( 'wpseopilot_schema_webpage', function( $data ) {
    return $data;
});

add_filter( 'wpseopilot_schema_article', function( $data ) {
    return $data;
});
```

#### 🧩 Breadcrumbs
```php
// Filter breadcrumbs output (array of links)
add_filter( 'wpseopilot_breadcrumb_links', function( $links ) {
    // $links[] = ['title' => 'Custom', 'url' => '/custom'];
    return $links;
});
```

#### 🗂️ XML SITEMAPS
```php
// Exclude a post type entirely from the sitemap index
add_filter( 'wpseopilot_sitemap_map', function( $map ) {
    foreach ( $map as $key => $group ) {
        if ( 'private_type' === $group['subtype'] ) {
            unset( $map[ $key ] );
        }
    }
    return $map;
});

// Exclude specific posts from the sitemap query
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    if ( 'post' === $post_type ) {
        $args['post__not_in'] = [ 123, 456 ]; // Exclude IDs
    }
    return $args;
}, 10, 2 );

// Modify individual sitemap entries (add images, change priority)
add_filter( 'wpseopilot_sitemap_entry', function( $entry, $post_id, $post_type ) {
    return $entry;
}, 10, 3 );
```

#### ⚙️ Features
```php
// Disable specific module features
add_filter( 'wpseopilot_feature_toggle', function( $enabled, $feature ) {
    if ( 'frontend_head' === $feature ) {
        return false; // Disable head output
    }
    return $enabled;
}, 10, 2 );
```

### Advanced Config

Add or duplicate social meta tags with `wpseopilot_social_tags`:

```php
add_filter( 'wpseopilot_social_tags', function ( $tags ) {
	$tags['og:image'] = array_filter(
		[
			$tags['og:image'] ?? '',
			'https://cdn.example.com/secondary.jpg',
		]
	);

	$tags[] = [
		'property' => 'og:image:alt',
		'content'  => 'Secondary image alt text',
	];

	return $tags;
}, 10, 1 );
```

### WP-CLI

```
wp wpseopilot redirects list --format=table
wp wpseopilot redirects export redirects.json
wp wpseopilot redirects import redirects.json
```

### Export

Export site defaults + postmeta as JSON via **WP SEO Pilot → SEO Defaults** for easy backups or migrations.

### Privacy

404 logging is opt-in and stores hashed referrers only. No telemetry or external requests are performed.

### Asset build

The plugin styles now compile from Less sources located in `assets/less`.

1. Install dependencies once with `npm install`.
2. Run `npm run build` to regenerate the CSS in `assets/css`.
3. Use `npm run watch` during development to recompile files on change.

### Sitemap enhancer

WP SEO Pilot replaces WordPress core sitemaps with `/sitemap_index.xml` plus Yoast-style `*-sitemap.xml` endpoints and enriches every entry with changefreq, priority, news/video data, and now multiple images. The sitemap image list is built from the featured image, any images attached to the post, and `<img>` tags found in the post content. You can tweak the output with `wpseopilot_sitemap_images`, for example to append a custom CDN URL:

```php
add_filter( 'wpseopilot_sitemap_images', function ( $images, $post_id ) {
	$images[] = [
		'image:loc'     => 'https://cdn.example.com/fallback.jpg',
		'image:caption' => get_the_title( $post_id ),
	];

	return $images;
}, 10, 2 );
```

To fully customize a single page’s sitemap entry—including changefreq, priority, news/video data, and images—use `wpseopilot_sitemap_entry`:

```php
add_filter( 'wpseopilot_sitemap_entry', function ( $entry, $post_id, $post_type ) {
	if ( $post_id !== 42 ) { // target a specific page or post
		return $entry;
	}

	$entry['priority']   = 1.0;
	$entry['changefreq'] = 'daily';
	$entry['news:news']  = [
		'news:publication'      => [
			'news:name'     => get_bloginfo( 'name' ),
			'news:language' => get_locale(),
		],
		'news:publication_date' => get_post_time( DATE_W3C, true, $post_id ),
		'news:title'            => get_the_title( $post_id ),
	];

	$entry['video:video'] = [
		'video:content_loc' => 'https://cdn.example.com/video.mp4',
		'video:title'       => get_the_title( $post_id ),
	];

	$entry['image:image'] = [
		[
			'image:loc'     => 'https://cdn.example.com/custom-hero.jpg',
			'image:caption' => 'Custom hero image for this page',
		],
		[
			'image:loc'     => 'https://cdn.example.com/custom-gallery.jpg',
			'image:caption' => 'Gallery highlight',
		],
	];

return $entry;
}, 10, 3 );
```

Example: pull a quote + image from a custom table and use it for both the sitemap image and OG headers on a single page.

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

add_filter( 'wpseopilot_sitemap_entry', function ( $entry, $post_id, $post_type ) {
	if ( 'page' !== $post_type ) {
		return $entry;
	}

	$quote = wpseopilot_get_quote_row( $post_id );
	if ( empty( $quote ) || empty( $quote['image_url'] ) ) {
		return $entry;
	}

	$entry['image:image'] = [
		[
			'image:loc'     => esc_url_raw( $quote['image_url'] ),
			'image:caption' => wp_strip_all_tags( $quote['quote_text'] . ' - ' . $quote['author'] ),
		],
	];

	return $entry;
}, 10, 3 );

add_filter( 'wpseopilot_og_title', function ( $title, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return $title;
	}

	$quote = wpseopilot_get_quote_row( $post->ID );
	if ( empty( $quote ) ) {
		return $title;
	}

	return wp_strip_all_tags( $quote['author'] . ' Quote' );
}, 10, 2 );

add_filter( 'wpseopilot_og_description', function ( $description, $post ) {
	if ( ! $post instanceof WP_Post ) {
		return $description;
	}

	$quote = wpseopilot_get_quote_row( $post->ID );
	if ( empty( $quote ) ) {
		return $description;
	}

	return wp_strip_all_tags( $quote['quote_text'] );
}, 10, 2 );

add_filter( 'wpseopilot_og_image', function ( $image, $post ) {
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
