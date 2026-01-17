# Template Tags & Shortcodes

Reference for theme integration with Saman SEO template tags and shortcodes.

---

## Template Tags

Template tags are PHP functions you can use in your theme files to display SEO-related content.

### `wpseopilot_breadcrumbs()`

Render a breadcrumb trail for the current page.

**Syntax:**
```php
wpseopilot_breadcrumbs( $post = null, $echo = true );
```

**Parameters:**
- `$post` (WP_Post|int|null) - Post object, post ID, or null for current post. Default: `null`
- `$echo` (bool) - Whether to echo output or return it. Default: `true`

**Return:**
- (string|void) Breadcrumb HTML if `$echo` is false, otherwise outputs directly

**Basic Usage:**
```php
<?php
// In your theme's header.php, page.php, single.php, etc.
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
?>
```

**Advanced Usage:**
```php
<?php
// Get breadcrumbs as string without echoing
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    $breadcrumbs = wpseopilot_breadcrumbs( null, false );
    
    // Wrap in custom container
    echo '<nav class="my-breadcrumbs">' . $breadcrumbs . '</nav>';
}
?>

<?php
// Breadcrumbs for specific post
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs( 123 ); // Post ID
}
?>

<?php
// Breadcrumbs for post object
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    $post = get_post( 456 );
    wpseopilot_breadcrumbs( $post );
}
?>
```

**Output Example:**
```html
<div class="wpseopilot-breadcrumbs">
    <a href="https://example.com">Home</a>
    <span class="separator">»</span>
    <a href="https://example.com/category/tutorials">Tutorials</a>
    <span class="separator">»</span>
    <span class="current">How to Use Template Tags</span>
</div>
```

**Custom Styling:**
```css
.wpseopilot-breadcrumbs {
    padding: 10px 0;
    font-size: 14px;
    color: #666;
}

.wpseopilot-breadcrumbs a {
    color: #0073aa;
    text-decoration: none;
    transition: color 0.2s;
}

.wpseopilot-breadcrumbs a:hover {
    color: #005177;
    text-decoration: underline;
}

.wpseopilot-breadcrumbs .separator {
    margin: 0 8px;
    color: #999;
}

.wpseopilot-breadcrumbs .current {
    color: #333;
    font-weight: 600;
}
```

**Filter the Output:**
```php
// Customize breadcrumb links
add_filter( 'wpseopilot_breadcrumb_links', function( $links ) {
    // Add custom breadcrumb after home
    array_splice( $links, 1, 0, [
        [
            'title' => 'Shop',
            'url' => home_url( '/shop/' ),
        ],
    ]);
    
    return $links;
});

// Change separator
add_filter( 'wpseopilot_breadcrumb_separator', function( $separator ) {
    return ' / '; // Change from » to /
});
```

---

## Shortcodes

Shortcodes can be used in post content, widgets, and many page builders.

### `[wpseopilot_breadcrumbs]`

Display breadcrumb trail via shortcode.

**Basic Usage:**
```
[wpseopilot_breadcrumbs]
```

**In Post Content:**
```
<p>You are here: [wpseopilot_breadcrumbs]</p>
```

**In Widget:**
Add the shortcode to a text widget:
```
[wpseopilot_breadcrumbs]
```

**In Page Builder:**
Most page builders support shortcodes in their text/code modules:
```
[wpseopilot_breadcrumbs]
```

**Output:**
Same HTML structure as the template tag version.

---

## Helper Functions

### `wpseopilot_get_meta()`

Retrieve SEO metadata for a post.

**Syntax:**
```php
wpseopilot_get_meta( $post_id, $key );
```

**Parameters:**
- `$post_id` (int) - Post ID
- `$key` (string) - Meta key: `title`, `description`, `canonical`, `robots`, `og_image`, `og_title`, `og_description`, `twitter_image`

**Return:**
- (string|false) Meta value or false if not set

**Usage:**
```php
<?php
$post_id = get_the_ID();

// Get custom SEO title
$seo_title = wpseopilot_get_meta( $post_id, 'title' );

if ( $seo_title ) {
    echo '<h1>' . esc_html( $seo_title ) . '</h1>';
} else {
    echo '<h1>' . get_the_title() . '</h1>';
}

// Get meta description
$meta_desc = wpseopilot_get_meta( $post_id, 'description' );

// Get canonical URL
$canonical = wpseopilot_get_meta( $post_id, 'canonical' );

// Get Open Graph image
$og_image = wpseopilot_get_meta( $post_id, 'og_image' );
?>
```

### `wpseopilot_update_meta()`

Update SEO metadata for a post.

**Syntax:**
```php
wpseopilot_update_meta( $post_id, $key, $value );
```

**Parameters:**
- `$post_id` (int) - Post ID
- `$key` (string) - Meta key
- `$value` (mixed) - Meta value

**Return:**
- (bool) True on success, false on failure

**Usage:**
```php
<?php
// Set SEO title programmatically
$post_id = 123;
wpseopilot_update_meta( $post_id, 'title', 'Custom SEO Title' );

// Set meta description
wpseopilot_update_meta( $post_id, 'description', 'Custom meta description for this post.' );

// Set canonical URL
wpseopilot_update_meta( $post_id, 'canonical', 'https://example.com/custom-url' );

// Set Open Graph image
wpseopilot_update_meta( $post_id, 'og_image', 'https://example.com/image.jpg' );
?>
```

### `wpseopilot_create_redirect()`

Create a redirect programmatically.

**Syntax:**
```php
wpseopilot_create_redirect( $source, $target, $status_code = 301 );
```

**Parameters:**
- `$source` (string) - Source path (e.g., `/old-page`)
- `$target` (string) - Target URL (e.g., `/new-page`)
- `$status_code` (int) - HTTP status code. Default: `301`

**Return:**
- (int|WP_Error) Redirect ID on success, WP_Error on failure

**Usage:**
```php
<?php
// Create 301 redirect
$result = wpseopilot_create_redirect( '/old-url', '/new-url' );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect failed: ' . $result->get_error_message() );
} else {
    echo 'Redirect created with ID: ' . $result;
}

// Create 302 temporary redirect
wpseopilot_create_redirect( '/temp-page', '/final-page', 302 );

// Bulk redirects from CSV
$csv_data = [
    [ '/old-1', '/new-1' ],
    [ '/old-2', '/new-2' ],
    [ '/old-3', '/new-3' ],
];

foreach ( $csv_data as $row ) {
    wpseopilot_create_redirect( $row[0], $row[1] );
}
?>
```

---

## Theme Integration Examples

### Header with Breadcrumbs

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
    <!-- Your header content -->
</header>

<?php if ( ! is_front_page() && function_exists( 'wpseopilot_breadcrumbs' ) ) : ?>
    <nav class="breadcrumb-container">
        <?php wpseopilot_breadcrumbs(); ?>
    </nav>
<?php endif; ?>
```

### Custom Single Post Template

**single.php:**
```php
<?php get_header(); ?>

<?php while ( have_posts() ) : the_post(); ?>
    
    <?php if ( function_exists( 'wpseopilot_breadcrumbs' ) ) : ?>
        <div class="breadcrumbs">
            <?php wpseopilot_breadcrumbs(); ?>
        </div>
    <?php endif; ?>
    
    <article <?php post_class(); ?>>
        <h1><?php the_title(); ?></h1>
        
        <div class="entry-content">
            <?php the_content(); ?>
        </div>
    </article>

<?php endwhile; ?>

<?php get_footer(); ?>
```

### Archive Page with Conditional Breadcrumbs

**archive.php:**
```php
<?php get_header(); ?>

<div class="archive-header">
    <?php if ( function_exists( 'wpseopilot_breadcrumbs' ) ) : ?>
        <?php wpseopilot_breadcrumbs(); ?>
    <?php endif; ?>
    
    <h1><?php the_archive_title(); ?></h1>
    <?php the_archive_description( '<div class="archive-description">', '</div>' ); ?>
</div>

<div class="archive-content">
    <?php if ( have_posts() ) : ?>
        <?php while ( have_posts() ) : the_post(); ?>
            <?php get_template_part( 'template-parts/content', get_post_type() ); ?>
        <?php endwhile; ?>
    <?php endif; ?>
</div>

<?php get_footer(); ?>
```

### Custom Meta Display

**functions.php:**
```php
<?php
/**
 * Display SEO meta in admin list
 */
add_filter( 'manage_posts_columns', function( $columns ) {
    $columns['seo_title'] = 'SEO Title';
    $columns['seo_desc'] = 'Meta Description';
    return $columns;
});

add_action( 'manage_posts_custom_column', function( $column, $post_id ) {
    if ( 'seo_title' === $column ) {
        $title = wpseopilot_get_meta( $post_id, 'title' );
        echo $title ? esc_html( $title ) : '—';
    }
    
    if ( 'seo_desc' === $column ) {
        $desc = wpseopilot_get_meta( $post_id, 'description' );
        echo $desc ? esc_html( wp_trim_words( $desc, 10 ) ) : '—';
    }
}, 10, 2 );
?>
```

### Programmatic SEO on Post Save

**functions.php:**
```php
<?php
/**
 * Auto-generate SEO data when post is published
 */
add_action( 'transition_post_status', function( $new_status, $old_status, $post ) {
    // Only on first publish
    if ( 'publish' !== $new_status || 'publish' === $old_status ) {
        return;
    }
    
    // Only for posts
    if ( 'post' !== $post->post_type ) {
        return;
    }
    
    // Generate SEO title if not set
    if ( ! wpseopilot_get_meta( $post->ID, 'title' ) ) {
        $category = get_the_category( $post->ID );
        $cat_name = ! empty( $category ) ? $category[0]->name : '';
        
        $seo_title = get_the_title( $post->ID );
        if ( $cat_name ) {
            $seo_title .= ' - ' . $cat_name;
        }
        $seo_title .= ' | ' . get_bloginfo( 'name' );
        
        wpseopilot_update_meta( $post->ID, 'title', $seo_title );
    }
    
    // Generate meta description if not set
    if ( ! wpseopilot_get_meta( $post->ID, 'description' ) ) {
        $excerpt = get_the_excerpt( $post );
        if ( empty( $excerpt ) ) {
            $excerpt = wp_trim_words( $post->post_content, 30, '...' );
        }
        
        wpseopilot_update_meta( $post->ID, 'description', $excerpt );
    }
}, 10, 3 );
?>
```

---

## Widget Integration

### Create Custom SEO Widget

**functions.php:**
```php
<?php
class WPSeoPilot_Breadcrumb_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'wpseopilot_breadcrumbs',
            'SEO Breadcrumbs',
            [ 'description' => 'Display breadcrumb navigation' ]
        );
    }
    
    public function widget( $args, $instance ) {
        if ( ! function_exists( 'wpseopilot_breadcrumbs' ) || is_front_page() ) {
            return;
        }
        
        echo $args['before_widget'];
        
        if ( ! empty( $instance['title'] ) ) {
            echo $args['before_title'] . esc_html( $instance['title'] ) . $args['after_title'];
        }
        
        wpseopilot_breadcrumbs();
        
        echo $args['after_widget'];
    }
    
    public function form( $instance ) {
        $title = ! empty( $instance['title'] ) ? $instance['title'] : '';
        ?>
        <p>
            <label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>">
                Title:
            </label>
            <input 
                class="widefat" 
                id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
                name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>" 
                type="text" 
                value="<?php echo esc_attr( $title ); ?>"
            >
        </p>
        <?php
    }
    
    public function update( $new_instance, $old_instance ) {
        $instance = [];
        $instance['title'] = ! empty( $new_instance['title'] ) 
            ? sanitize_text_field( $new_instance['title'] ) 
            : '';
        return $instance;
    }
}

add_action( 'widgets_init', function() {
    register_widget( 'WPSeoPilot_Breadcrumb_Widget' );
});
?>
```

---

## Block Editor (Gutenberg) Integration

### Register Custom Breadcrumb Block

**functions.php:**
```php
<?php
/**
 * Register breadcrumb block
 */
add_action( 'init', function() {
    if ( ! function_exists( 'register_block_type' ) ) {
        return;
    }
    
    register_block_type( 'wpseopilot/breadcrumbs', [
        'render_callback' => function( $attributes ) {
            if ( ! function_exists( 'wpseopilot_breadcrumbs' ) ) {
                return '';
            }
            
            ob_start();
            wpseopilot_breadcrumbs();
            return ob_get_clean();
        },
    ]);
});
?>
```

**Usage in Block Editor:**
1. Add a "Shortcode" block
2. Enter: `[wpseopilot_breadcrumbs]`

Or use the custom block if registered above.

---

## Conditional Loading

### Load Breadcrumbs Only on Specific Templates

```php
<?php
// In your theme template
if ( 
    ! is_front_page() && 
    ! is_404() && 
    function_exists( 'wpseopilot_breadcrumbs' ) 
) {
    wpseopilot_breadcrumbs();
}
?>
```

### Load Breadcrumbs Only for Certain Post Types

```php
<?php
if ( 
    is_singular( [ 'post', 'page', 'product' ] ) && 
    function_exists( 'wpseopilot_breadcrumbs' ) 
) {
    wpseopilot_breadcrumbs();
}
?>
```

---

## Best Practices

### Always Check if Function Exists

```php
<?php
// Good
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}

// Bad - will cause fatal error if plugin is deactivated
wpseopilot_breadcrumbs();
?>
```

### Escape Output When Using Helper Functions

```php
<?php
$title = wpseopilot_get_meta( get_the_ID(), 'title' );

// Good
echo esc_html( $title );

// Bad
echo $title;
?>
```

### Provide Fallbacks

```php
<?php
$seo_title = wpseopilot_get_meta( get_the_ID(), 'title' );

// Good - has fallback
echo esc_html( $seo_title ? $seo_title : get_the_title() );

// Bad - might be empty
echo esc_html( $seo_title );
?>
```

---

For more advanced customization, see:
- **[Developer Guide](DEVELOPER_GUIDE.md)** - Filters and hooks
- **[Filter Reference](FILTERS.md)** - Complete filter documentation
