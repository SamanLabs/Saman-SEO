# Template Tags & Shortcodes

This guide covers all public functions, template tags, and shortcodes available for theme integration with Saman SEO.

---

## Table of Contents

- [Breadcrumbs](#breadcrumbs)
- [Helper Functions](#helper-functions)
- [Shortcodes](#shortcodes)
- [Programmatic Functions](#programmatic-functions)
- [Theme Integration Examples](#theme-integration-examples)

---

## Breadcrumbs

### `wpseopilot_breadcrumbs()`

Render SEO-friendly breadcrumb navigation with Schema.org markup.

**Function Signature:**

```php
wpseopilot_breadcrumbs( $post = null, $echo = true )
```

**Parameters:**
- `$post` (WP_Post|null) - Post object (defaults to current post in the loop)
- `$echo` (bool) - Whether to echo output or return HTML (default: `true`)

**Returns:**
- (string|void) HTML markup if `$echo` is `false`, otherwise void

**Location:** `includes/helpers.php:720`

---

### Basic Usage

```php
// In your theme template (header.php, single.php, page.php, etc.)
<?php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
?>
```

---

### Return HTML Instead of Echoing

```php
<?php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    $breadcrumbs_html = wpseopilot_breadcrumbs( null, false );

    // Do something with the HTML
    echo '<div class="custom-wrapper">' . $breadcrumbs_html . '</div>';
}
?>
```

---

### Specify Custom Post

```php
<?php
$custom_post = get_post( 123 );

if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs( $custom_post );
}
?>
```

---

### Output Structure

The breadcrumbs are rendered with:
- Semantic HTML5 `<nav>` element with `aria-label="Breadcrumb"`
- Schema.org BreadcrumbList JSON-LD structured data
- Clean, accessible markup

**Example HTML Output:**

```html
<nav aria-label="Breadcrumb" class="wpseopilot-breadcrumbs">
    <ol itemscope itemtype="https://schema.org/BreadcrumbList">
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="https://example.com/">
                <span itemprop="name">Home</span>
            </a>
            <meta itemprop="position" content="1" />
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a itemprop="item" href="https://example.com/category/">
                <span itemprop="name">Category</span>
            </a>
            <meta itemprop="position" content="2" />
        </li>
        <li itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">Current Page</span>
            <meta itemprop="position" content="3" />
        </li>
    </ol>
</nav>
```

---

### Styling Breadcrumbs

Add custom CSS to your theme:

```css
.wpseopilot-breadcrumbs {
    font-size: 14px;
    color: #666;
    margin-bottom: 20px;
}

.wpseopilot-breadcrumbs ol {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
}

.wpseopilot-breadcrumbs li {
    display: inline;
}

.wpseopilot-breadcrumbs li::after {
    content: ' › ';
    padding: 0 8px;
    color: #999;
}

.wpseopilot-breadcrumbs li:last-child::after {
    content: '';
}

.wpseopilot-breadcrumbs a {
    color: #0073aa;
    text-decoration: none;
}

.wpseopilot-breadcrumbs a:hover {
    text-decoration: underline;
}

.wpseopilot-breadcrumbs li:last-child span {
    color: #333;
}
```

---

### Filtering Breadcrumbs

Customize breadcrumb output using the `wpseopilot_breadcrumb_links` filter:

```php
add_filter( 'wpseopilot_breadcrumb_links', function( $crumbs, $post ) {
    // Add custom breadcrumb for products
    if ( get_post_type( $post ) === 'product' ) {
        array_splice( $crumbs, 1, 0, [
            [ 'url' => home_url( '/shop' ), 'label' => 'Shop' ]
        ]);
    }

    return $crumbs;
}, 10, 2 );
```

See **[Filter Reference](FILTERS.md#wpseopilot_breadcrumb_links)** for more examples.

---

## Helper Functions

Saman SEO provides namespaced helper functions for accessing SEO data programmatically.

**Namespace:** `WPSEOPilot\Helpers`

---

### `get_option()`

Fetch a plugin option with default fallback.

**Function Signature:**

```php
\WPSEOPilot\Helpers\get_option( $key, $default = '' )
```

**Parameters:**
- `$key` (string) - Option name
- `$default` (mixed) - Default value if option doesn't exist

**Returns:**
- (mixed) Option value or default

**Example:**

```php
use function WPSEOPilot\Helpers\get_option;

$default_title_template = get_option( 'wpseopilot_default_title_template', '{{post_title}} | {{site_title}}' );

echo 'Title template: ' . $default_title_template;
```

---

### `get_post_meta()`

Fetch SEO metadata for a post with sensible defaults.

**Function Signature:**

```php
\WPSEOPilot\Helpers\get_post_meta( $post_id )
```

**Parameters:**
- `$post_id` (int) - Post ID

**Returns:**
- (array) Associative array of SEO meta fields

**Return Structure:**

```php
[
    'title' => '',
    'description' => '',
    'canonical' => '',
    'noindex' => '0',
    'nofollow' => '0',
    'og_image' => ''
]
```

**Example:**

```php
use function WPSEOPilot\Helpers\get_post_meta;

$meta = get_post_meta( get_the_ID() );

if ( ! empty( $meta['description'] ) ) {
    echo 'Custom description: ' . esc_html( $meta['description'] );
}
```

---

### `replace_template_variables()`

Replace template variables (e.g., `{{post_title}}`) with actual values.

**Function Signature:**

```php
\WPSEOPilot\Helpers\replace_template_variables( $template, $post )
```

**Parameters:**
- `$template` (string) - Template string with variables
- `$post` (WP_Post) - Post object for variable replacement

**Returns:**
- (string) Processed template with variables replaced

**Available Variables:**
- `{{post_title}}` - Post/page title
- `{{site_title}}` - Site name
- `{{post_type}}` - Post type label
- `{{category}}` - Primary category name
- `{{tag}}` - Primary tag name
- `{{author}}` - Author display name
- `{{date}}` - Publication date
- `{{excerpt}}` - Post excerpt

**Example:**

```php
use function WPSEOPilot\Helpers\replace_template_variables;

$template = '{{post_title}} by {{author}} | {{site_title}}';
$post = get_post( 123 );

$title = replace_template_variables( $template, $post );

echo $title;
// Output: "My Blog Post by John Doe | My Website"
```

---

### `generate_title_from_template()`

Generate a complete SEO title using template and post data.

**Function Signature:**

```php
\WPSEOPilot\Helpers\generate_title_from_template( $post, $post_type )
```

**Parameters:**
- `$post` (WP_Post) - Post object
- `$post_type` (string) - Post type slug

**Returns:**
- (string) Generated title

**Example:**

```php
use function WPSEOPilot\Helpers\generate_title_from_template;

$post = get_post( get_the_ID() );
$title = generate_title_from_template( $post, 'post' );

echo '<h1>' . esc_html( $title ) . '</h1>';
```

---

### `generate_content_snippet()`

Generate a trimmed snippet from post content.

**Function Signature:**

```php
\WPSEOPilot\Helpers\generate_content_snippet( $post, $length = 160 )
```

**Parameters:**
- `$post` (WP_Post) - Post object
- `$length` (int) - Maximum character length (default: 160)

**Returns:**
- (string) Trimmed content snippet

**Example:**

```php
use function WPSEOPilot\Helpers\generate_content_snippet;

$snippet = generate_content_snippet( get_post( 123 ), 200 );

echo '<p class="excerpt">' . esc_html( $snippet ) . '</p>';
```

---

### `calculate_seo_score()`

Calculate the SEO score for a post.

**Function Signature:**

```php
\WPSEOPilot\Helpers\calculate_seo_score( $post )
```

**Parameters:**
- `$post` (WP_Post) - Post object

**Returns:**
- (array) Score data with keys: `score`, `issues`, `suggestions`

**Return Structure:**

```php
[
    'score' => 85,
    'issues' => [
        'Title is too short',
        'Missing meta description'
    ],
    'suggestions' => [
        'Add more internal links',
        'Include focus keyword in first paragraph'
    ]
]
```

**Example:**

```php
use function WPSEOPilot\Helpers\calculate_seo_score;

$post = get_post( get_the_ID() );
$score_data = calculate_seo_score( $post );

echo 'SEO Score: ' . $score_data['score'] . '/100<br>';

if ( ! empty( $score_data['issues'] ) ) {
    echo '<strong>Issues:</strong><ul>';
    foreach ( $score_data['issues'] as $issue ) {
        echo '<li>' . esc_html( $issue ) . '</li>';
    }
    echo '</ul>';
}
```

---

### `breadcrumbs()`

Generate breadcrumb HTML (alias for namespaced version).

**Function Signature:**

```php
\WPSEOPilot\Helpers\breadcrumbs( $post = null, $echo = true )
```

**Parameters:**
- `$post` (WP_Post|null) - Post object
- `$echo` (bool) - Whether to echo or return

**Returns:**
- (string|void) HTML markup if `$echo` is `false`

**Example:**

```php
use function WPSEOPilot\Helpers\breadcrumbs;

$html = breadcrumbs( null, false );
echo '<div class="my-breadcrumbs">' . $html . '</div>';
```

---

## Shortcodes

### `[wpseopilot_breadcrumbs]`

Render breadcrumbs anywhere via shortcode.

**Location:** `includes/class-wpseopilot-service-frontend.php:39`

---

### Basic Usage

```
[wpseopilot_breadcrumbs]
```

---

### In Content

Add breadcrumbs directly in post/page content:

```
Welcome to our site! Here's where you are:

[wpseopilot_breadcrumbs]

Now let's get started...
```

---

### In Widgets

Use the shortcode in a Text widget or Custom HTML widget:

```
[wpseopilot_breadcrumbs]
```

---

### In Template Files

Execute shortcode in PHP:

```php
<?php echo do_shortcode( '[wpseopilot_breadcrumbs]' ); ?>
```

---

## Programmatic Functions

### `wpseopilot_create_redirect()`

Create a redirect programmatically.

**Function Signature:**

```php
wpseopilot_create_redirect( $source, $target, $status_code = 301 )
```

**Parameters:**
- `$source` (string) - Source path (e.g., `/old-url`)
- `$target` (string) - Target URL (e.g., `/new-url` or `https://external.com`)
- `$status_code` (int) - HTTP status code (301, 302, 307, 308) - default: 301

**Returns:**
- (bool|WP_Error) `true` on success, `WP_Error` on failure

**Location:** `includes/helpers.php:733`

---

### Basic Usage

```php
// Create a 301 permanent redirect
$result = wpseopilot_create_redirect( '/old-page', '/new-page' );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect failed: ' . $result->get_error_message() );
} else {
    echo 'Redirect created successfully!';
}
```

---

### Creating Temporary Redirects

```php
// Create a 302 temporary redirect
wpseopilot_create_redirect( '/promo', '/limited-time-offer', 302 );
```

---

### External Redirects

```php
// Redirect to external URL
wpseopilot_create_redirect( '/old-blog', 'https://newblog.com', 301 );
```

---

### Bulk Redirect Creation

```php
$redirects = [
    [ '/old-1', '/new-1' ],
    [ '/old-2', '/new-2' ],
    [ '/old-3', '/new-3' ]
];

foreach ( $redirects as $redirect ) {
    wpseopilot_create_redirect( $redirect[0], $redirect[1], 301 );
}
```

---

### Error Handling

```php
$result = wpseopilot_create_redirect( '/source', '/target', 301 );

if ( is_wp_error( $result ) ) {
    switch ( $result->get_error_code() ) {
        case 'invalid_source':
            echo 'Source path is invalid';
            break;
        case 'invalid_target':
            echo 'Target URL is invalid';
            break;
        case 'duplicate_redirect':
            echo 'Redirect already exists';
            break;
        default:
            echo 'Unknown error: ' . $result->get_error_message();
    }
}
```

---

## Theme Integration Examples

### Example 1: Breadcrumbs in Page Header

**header.php:**

```php
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<header class="site-header">
    <div class="container">
        <div class="site-branding">
            <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                <?php bloginfo( 'name' ); ?>
            </a>
        </div>

        <?php
        // Display breadcrumbs on all pages except homepage
        if ( ! is_front_page() && function_exists( 'wpseopilot_breadcrumbs' ) ) {
            wpseopilot_breadcrumbs();
        }
        ?>
    </div>
</header>
```

---

### Example 2: SEO Score Display in Archive

**archive.php:**

```php
<?php
use function WPSEOPilot\Helpers\calculate_seo_score;

get_header();

if ( have_posts() ) :
    while ( have_posts() ) : the_post();
        $score_data = calculate_seo_score( get_post() );
        ?>

        <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
            <header class="entry-header">
                <h2 class="entry-title">
                    <a href="<?php the_permalink(); ?>">
                        <?php the_title(); ?>
                    </a>
                </h2>

                <?php if ( $score_data['score'] >= 80 ) : ?>
                    <span class="seo-badge seo-excellent">SEO Score: <?php echo esc_html( $score_data['score'] ); ?></span>
                <?php elseif ( $score_data['score'] >= 60 ) : ?>
                    <span class="seo-badge seo-good">SEO Score: <?php echo esc_html( $score_data['score'] ); ?></span>
                <?php else : ?>
                    <span class="seo-badge seo-needs-work">SEO Score: <?php echo esc_html( $score_data['score'] ); ?></span>
                <?php endif; ?>
            </header>

            <div class="entry-content">
                <?php the_excerpt(); ?>
            </div>
        </article>

        <?php
    endwhile;
endif;

get_footer();
?>
```

---

### Example 3: Dynamic Title Generation

**functions.php:**

```php
use function WPSEOPilot\Helpers\generate_title_from_template;
use function WPSEOPilot\Helpers\replace_template_variables;

// Custom title for product archive
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_post_type_archive( 'product' ) ) {
        return 'Shop Our Products | ' . get_bloginfo( 'name' );
    }

    return $title;
}, 10, 2 );

// Add pricing to product titles
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( $post && get_post_type( $post ) === 'product' ) {
        $price = get_post_meta( $post->ID, '_price', true );

        if ( $price ) {
            return $title . ' - $' . number_format( $price, 2 );
        }
    }

    return $title;
}, 10, 2 );
```

---

### Example 4: Custom Meta in Theme

**single.php:**

```php
<?php
use function WPSEOPilot\Helpers\get_post_meta;

get_header();

while ( have_posts() ) : the_post();
    $seo_meta = get_post_meta( get_the_ID() );
    ?>

    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <header class="entry-header">
            <?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>

            <?php if ( ! empty( $seo_meta['description'] ) ) : ?>
                <p class="entry-summary">
                    <?php echo esc_html( $seo_meta['description'] ); ?>
                </p>
            <?php endif; ?>

            <div class="entry-meta">
                <?php
                echo 'Published on ' . get_the_date();
                echo ' by ' . get_the_author();
                ?>
            </div>
        </header>

        <div class="entry-content">
            <?php the_content(); ?>
        </div>

        <?php
        // Display canonical if different from current URL
        if ( ! empty( $seo_meta['canonical'] ) && $seo_meta['canonical'] !== get_permalink() ) :
            ?>
            <div class="canonical-notice">
                <em>Canonical URL: <a href="<?php echo esc_url( $seo_meta['canonical'] ); ?>">
                    <?php echo esc_html( $seo_meta['canonical'] ); ?>
                </a></em>
            </div>
        <?php endif; ?>
    </article>

    <?php
endwhile;

get_footer();
?>
```

---

### Example 5: Automatic Redirects on Post Update

**functions.php:**

```php
// Auto-create redirects when post slug changes
add_action( 'post_updated', function( $post_id, $post_after, $post_before ) {
    // Check if slug changed
    if ( $post_after->post_name !== $post_before->post_name ) {
        $old_url = '/' . $post_before->post_name;
        $new_url = '/' . $post_after->post_name;

        // Create redirect
        if ( function_exists( 'wpseopilot_create_redirect' ) ) {
            $result = wpseopilot_create_redirect( $old_url, $new_url, 301 );

            if ( ! is_wp_error( $result ) ) {
                add_action( 'admin_notices', function() {
                    echo '<div class="notice notice-success"><p>Redirect created automatically!</p></div>';
                });
            }
        }
    }
}, 10, 3 );
```

---

### Example 6: Custom Breadcrumb Styling

**style.css:**

```css
/* Breadcrumb container */
.wpseopilot-breadcrumbs {
    background: #f5f5f5;
    padding: 10px 20px;
    border-radius: 4px;
    margin-bottom: 30px;
    font-size: 14px;
}

/* Breadcrumb list */
.wpseopilot-breadcrumbs ol {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
}

/* Breadcrumb items */
.wpseopilot-breadcrumbs li {
    display: inline-flex;
    align-items: center;
}

/* Separator */
.wpseopilot-breadcrumbs li::after {
    content: '›';
    margin: 0 10px;
    color: #999;
    font-weight: bold;
}

.wpseopilot-breadcrumbs li:last-child::after {
    display: none;
}

/* Links */
.wpseopilot-breadcrumbs a {
    color: #0073aa;
    text-decoration: none;
    transition: color 0.2s;
}

.wpseopilot-breadcrumbs a:hover {
    color: #005177;
    text-decoration: underline;
}

/* Current page */
.wpseopilot-breadcrumbs li:last-child span {
    color: #333;
    font-weight: 600;
}

/* Mobile responsive */
@media (max-width: 768px) {
    .wpseopilot-breadcrumbs {
        font-size: 12px;
        padding: 8px 15px;
    }

    .wpseopilot-breadcrumbs li::after {
        margin: 0 5px;
    }
}
```

---

## Advanced Usage

### Combining Multiple Helpers

```php
<?php
use function WPSEOPilot\Helpers\get_post_meta;
use function WPSEOPilot\Helpers\calculate_seo_score;
use function WPSEOPilot\Helpers\generate_content_snippet;

$post_id = get_the_ID();
$meta = get_post_meta( $post_id );
$score = calculate_seo_score( get_post( $post_id ) );
$snippet = generate_content_snippet( get_post( $post_id ), 200 );
?>

<div class="seo-info-panel">
    <h3>SEO Information</h3>

    <p><strong>SEO Score:</strong> <?php echo esc_html( $score['score'] ); ?>/100</p>

    <?php if ( ! empty( $meta['title'] ) ) : ?>
        <p><strong>SEO Title:</strong> <?php echo esc_html( $meta['title'] ); ?></p>
    <?php endif; ?>

    <?php if ( ! empty( $meta['description'] ) ) : ?>
        <p><strong>Meta Description:</strong> <?php echo esc_html( $meta['description'] ); ?></p>
    <?php else : ?>
        <p><strong>Generated Snippet:</strong> <?php echo esc_html( $snippet ); ?></p>
    <?php endif; ?>

    <?php if ( $score['score'] < 70 ) : ?>
        <div class="seo-suggestions">
            <strong>Suggestions:</strong>
            <ul>
                <?php foreach ( $score['suggestions'] as $suggestion ) : ?>
                    <li><?php echo esc_html( $suggestion ); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
```

---

## Best Practices

### 1. Always Check Function Existence

```php
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
```

### 2. Use Namespaced Helpers

```php
use function WPSEOPilot\Helpers\get_option;
use function WPSEOPilot\Helpers\get_post_meta;
```

### 3. Escape Output

```php
echo esc_html( $meta['title'] );
echo esc_url( $meta['canonical'] );
echo esc_attr( $score['score'] );
```

### 4. Handle Errors

```php
$result = wpseopilot_create_redirect( '/old', '/new' );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect error: ' . $result->get_error_message() );
}
```

---

## Related Documentation

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Complete developer documentation
- **[Filter Reference](FILTERS.md)** - All available filters
- **[Getting Started](GETTING_STARTED.md)** - Plugin basics

---

**For more examples, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
