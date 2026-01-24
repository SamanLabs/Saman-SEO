# Schema Developer API

Complete documentation for extending the Saman SEO JSON-LD schema system. This guide covers registering custom schema types, modifying existing schemas, and using the public API.

---

## Table of Contents

- [Overview](#overview)
- [Architecture](#architecture)
- [Registering Custom Schema Types](#registering-custom-schema-types)
- [Modifying Existing Schemas](#modifying-existing-schemas)
- [Filtering Available Types](#filtering-available-types)
- [API Reference](#api-reference)
- [Complete Examples](#complete-examples)
- [Troubleshooting](#troubleshooting)
- [Related Documentation](#related-documentation)

---

## Overview

The Schema Developer API enables third-party developers to:

- **Register custom schema types** - Add new JSON-LD schema types (e.g., Product, Event, Recipe)
- **Modify existing schemas** - Enhance built-in schemas with additional fields
- **Filter available types** - Control which schema types appear in the admin UI
- **Build upon established patterns** - Use the same infrastructure as core schemas

### When to Use Each Hook

| Hook | Purpose | Use Case |
|------|---------|----------|
| `saman_seo_register_schema_type` | Add new schema types | WooCommerce Product schema, custom Event schema |
| `saman_seo_schema_{type}_fields` | Modify schema before generation | Add speakable to Article |
| `saman_seo_schema_{type}_output` | Modify final schema output | Add custom fields after generation |
| `saman_seo_schema_types` | Filter available types | Remove types from dropdown, change labels |

---

## Architecture

The schema system follows a Registry -> Graph Manager -> Output flow:

```
1. Registration Phase
   - Core types registered in Plugin::boot()
   - Third-party types via saman_seo_register_schema_type action
   - All types stored in Schema_Registry singleton

2. Generation Phase (Schema_Graph_Manager::build)
   - Retrieves types from registry (filterable via saman_seo_schema_types)
   - For each type:
     a. Checks is_needed() to determine if schema applies
     b. Calls generate() to build schema data
     c. Applies saman_seo_schema_{type}_output filter
   - Combines all pieces into @graph array
   - Applies saman_seo_schema_graph filter

3. Output Phase
   - Wraps @graph in root object with @context
   - Encodes as JSON-LD
   - Outputs in <head> via wp_head action
```

### Key Classes

| Class | Purpose |
|-------|---------|
| `Schema_Registry` | Singleton registry for type management |
| `Abstract_Schema` | Base class all schemas extend |
| `Schema_Context` | Value object with environment data |
| `Schema_IDs` | Static helper for @id generation |
| `Schema_Graph_Manager` | Builds the JSON-LD @graph output |

---

## Registering Custom Schema Types

### `saman_seo_register_schema_type`

Action hook that fires after core schema types are registered, allowing third-party plugins to add custom types.

**Parameters:**
- `$registry` (Schema_Registry) - The schema registry singleton instance

**File:** `includes/class-samanseo-plugin.php`

**When to Use:**
- Adding schema for custom post types (Events, Products, Recipes)
- Integrating with other plugins (WooCommerce, The Events Calendar)
- Supporting content types not covered by core schemas

**Basic Registration:**

```php
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register(
        'my_event',                           // Unique slug (prefix to avoid conflicts)
        MyPlugin\Schema\Event_Schema::class,  // Fully qualified class name
        [
            'label'      => __( 'Event', 'my-plugin' ),
            'post_types' => [ 'event' ],      // Post types this applies to
            'priority'   => 15,               // Processing order (lower = earlier)
        ]
    );
});
```

**Registration Options:**

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `label` | string | ucfirst($slug) | Human-readable label for admin UI |
| `post_types` | array | [] | Post types this schema applies to |
| `priority` | int | 10 | Processing order in graph (lower = earlier) |

**Priority Guidelines:**

| Range | Use For | Examples |
|-------|---------|----------|
| 1-5 | Site-wide schemas | WebSite, Organization |
| 6-10 | Page-level schemas | WebPage |
| 11-15 | Content schemas | Article, Product, Event |
| 16-20 | Interactive schemas | FAQPage, HowTo |
| 21+ | Supplementary schemas | BreadcrumbList |

### Creating a Schema Class

Your schema class must extend `Abstract_Schema` and implement three methods:

```php
<?php
namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

/**
 * Custom Event schema for event post types.
 */
class Event_Schema extends Abstract_Schema {

    /**
     * Get the schema @type value.
     *
     * @return string|array The @type value(s).
     */
    public function get_type() {
        return 'Event';
    }

    /**
     * Determine if this schema should output.
     *
     * @return bool True to include in output, false to skip.
     */
    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post
            && 'event' === $this->context->post_type;
    }

    /**
     * Generate the schema array.
     *
     * IMPORTANT: Do NOT include @context in return value.
     *
     * @return array Schema.org structured data.
     */
    public function generate(): array {
        $post = $this->context->post;

        return [
            '@type'       => $this->get_type(),
            '@id'         => $this->context->canonical . '#event',
            'name'        => get_the_title( $post ),
            'description' => get_the_excerpt( $post ),
            'url'         => $this->context->canonical,
            'startDate'   => get_post_meta( $post->ID, '_event_start', true ),
            'endDate'     => get_post_meta( $post->ID, '_event_end', true ),
        ];
    }
}
```

### Schema Class Requirements

**MUST:**
- Extend `Saman\SEO\Schema\Abstract_Schema`
- Implement `get_type()` returning the schema.org @type
- Implement `is_needed()` returning boolean
- Implement `generate()` returning array without @context

**MUST NOT:**
- Include `@context` in returned array (Graph_Manager adds this)
- Output directly (return array, never echo)
- Access WordPress globals directly (use `$this->context`)

### Complete Event Schema Example

```php
<?php
/**
 * Plugin Name: My Events Schema
 * Description: Adds Event schema support to Saman SEO
 */

namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Schema_IDs;

// Only load if Saman SEO is active
if ( ! class_exists( 'Saman\SEO\Schema\Abstract_Schema' ) ) {
    return;
}

/**
 * Event schema implementation.
 */
class Event_Schema extends Abstract_Schema {

    /**
     * Get the schema @type.
     *
     * @return string
     */
    public function get_type() {
        return 'Event';
    }

    /**
     * Check if schema should output.
     *
     * @return bool
     */
    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post
            && 'event' === $this->context->post_type;
    }

    /**
     * Generate Event schema.
     *
     * @return array
     */
    public function generate(): array {
        $post   = $this->context->post;
        $schema = [
            '@type'       => $this->get_type(),
            '@id'         => $this->context->canonical . '#event',
            'name'        => get_the_title( $post ),
            'description' => get_the_excerpt( $post ),
            'url'         => $this->context->canonical,
            'startDate'   => get_post_meta( $post->ID, '_event_start_date', true ),
        ];

        // Optional: End date
        $end_date = get_post_meta( $post->ID, '_event_end_date', true );
        if ( $end_date ) {
            $schema['endDate'] = $end_date;
        }

        // Optional: Location
        $location = $this->build_location();
        if ( $location ) {
            $schema['location'] = $location;
        }

        // Optional: Organizer
        $organizer = $this->build_organizer();
        if ( $organizer ) {
            $schema['organizer'] = $organizer;
        }

        // Optional: Image
        if ( has_post_thumbnail( $post ) ) {
            $schema['image'] = get_the_post_thumbnail_url( $post, 'full' );
        }

        // Optional: Offers
        $offers = $this->build_offers();
        if ( $offers ) {
            $schema['offers'] = $offers;
        }

        return $schema;
    }

    /**
     * Build location object.
     *
     * @return array|null
     */
    private function build_location(): ?array {
        $venue   = get_post_meta( $this->context->post->ID, '_event_venue', true );
        $address = get_post_meta( $this->context->post->ID, '_event_address', true );

        if ( empty( $venue ) && empty( $address ) ) {
            return null;
        }

        $location = [
            '@type' => 'Place',
        ];

        if ( $venue ) {
            $location['name'] = $venue;
        }

        if ( $address ) {
            $location['address'] = [
                '@type'         => 'PostalAddress',
                'streetAddress' => $address,
            ];
        }

        return $location;
    }

    /**
     * Build organizer object.
     *
     * @return array|null
     */
    private function build_organizer(): ?array {
        $organizer_name = get_post_meta( $this->context->post->ID, '_event_organizer', true );

        if ( empty( $organizer_name ) ) {
            return null;
        }

        return [
            '@type' => 'Organization',
            'name'  => $organizer_name,
            'url'   => $this->context->site_url,
        ];
    }

    /**
     * Build offers object for ticketed events.
     *
     * @return array|null
     */
    private function build_offers(): ?array {
        $price = get_post_meta( $this->context->post->ID, '_event_price', true );

        if ( empty( $price ) ) {
            return null;
        }

        return [
            '@type'         => 'Offer',
            'price'         => $price,
            'priceCurrency' => 'USD',
            'availability'  => 'https://schema.org/InStock',
            'url'           => $this->context->canonical,
        ];
    }
}

// Register the schema type
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register(
        'myplugin_event',
        Event_Schema::class,
        [
            'label'      => __( 'Event', 'my-plugin' ),
            'post_types' => [ 'event', 'tribe_events' ],
            'priority'   => 15,
        ]
    );
});
```

---

## Modifying Existing Schemas

### `saman_seo_schema_{type}_fields`

Filter to modify schema data before generation. Use this to add fields to existing schema types.

**Parameters:**
- `$data` (array) - The schema data array
- `$context` (Schema_Context) - The current schema context

**File:** `includes/Schema/class-abstract-schema.php`

**Example: Adding Speakable to Article**

```php
add_filter( 'saman_seo_schema_article_fields', function( $data, $context ) {
    // Add speakable specification for voice search optimization
    $data['speakable'] = [
        '@type'       => 'SpeakableSpecification',
        'cssSelector' => [
            'article h1',
            'article .entry-content p:first-of-type',
        ],
    ];

    return $data;
}, 10, 2 );
```

**Example: Adding Custom Fields from Post Meta**

```php
add_filter( 'saman_seo_schema_article_fields', function( $data, $context ) {
    if ( ! $context->post ) {
        return $data;
    }

    // Add word count
    $content    = get_post_field( 'post_content', $context->post );
    $word_count = str_word_count( strip_tags( $content ) );

    $data['wordCount'] = $word_count;

    // Add estimated reading time (assuming 200 words/minute)
    $data['timeRequired'] = 'PT' . ceil( $word_count / 200 ) . 'M';

    return $data;
}, 10, 2 );
```

---

### `saman_seo_schema_{type}_output`

Filter to modify the final schema output after generation. Use this for post-processing modifications.

**Parameters:**
- `$piece` (array) - The complete schema piece
- `$context` (Schema_Context) - The current schema context

**File:** `includes/Schema/class-schema-graph-manager.php`

**Example: Modifying Publisher Logo**

```php
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    // Add dimensions to publisher logo
    if ( isset( $piece['publisher']['logo'] ) ) {
        $piece['publisher']['logo']['width']  = 600;
        $piece['publisher']['logo']['height'] = 60;
    }

    return $piece;
}, 10, 2 );
```

**Example: Adding isAccessibleForFree**

```php
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    // Mark all articles as free to read
    $piece['isAccessibleForFree'] = true;

    return $piece;
}, 10, 2 );
```

**Example: Conditional Paywall Schema**

```php
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    if ( ! $context->post ) {
        return $piece;
    }

    $is_premium = get_post_meta( $context->post->ID, '_is_premium', true );

    if ( $is_premium ) {
        $piece['isAccessibleForFree'] = false;
        $piece['hasPart'] = [
            '@type'               => 'WebPageElement',
            'isAccessibleForFree' => false,
            'cssSelector'         => '.premium-content',
        ];
    } else {
        $piece['isAccessibleForFree'] = true;
    }

    return $piece;
}, 10, 2 );
```

### Fields vs Output Filter

| Filter | When Applied | Best For |
|--------|--------------|----------|
| `_fields` | Before generate() builds schema | Adding new properties, modifying base data |
| `_output` | After generate() completes | Post-processing, conditional logic based on full schema |

---

## Filtering Available Types

### `saman_seo_schema_types`

Filter to modify the list of available schema types. Use this to remove types, change labels, or hide options from the admin UI.

**Parameters:**
- `$types` (array) - All registered schema types

**File:** `includes/Schema/class-schema-registry.php`

**Example: Removing Schema Types**

```php
add_filter( 'saman_seo_schema_types', function( $types ) {
    // Remove NewsArticle for non-news sites
    unset( $types['newsarticle'] );

    // Remove BlogPosting (only want Article)
    unset( $types['blogposting'] );

    return $types;
});
```

**Example: Changing Labels**

```php
add_filter( 'saman_seo_schema_types', function( $types ) {
    if ( isset( $types['article'] ) ) {
        $types['article']['label'] = __( 'Standard Article', 'my-theme' );
    }

    if ( isset( $types['newsarticle'] ) ) {
        $types['newsarticle']['label'] = __( 'News Story', 'my-theme' );
    }

    return $types;
});
```

**Example: Restricting Types by User Role**

```php
add_filter( 'saman_seo_schema_types', function( $types ) {
    // Only show NewsArticle to editors and above
    if ( ! current_user_can( 'edit_others_posts' ) ) {
        unset( $types['newsarticle'] );
    }

    return $types;
});
```

**Example: Adding Custom Type Labels for Admin**

```php
add_filter( 'saman_seo_schema_types', function( $types ) {
    // Add descriptions to help content editors
    foreach ( $types as $slug => &$config ) {
        switch ( $slug ) {
            case 'article':
                $config['label'] = __( 'Article (general content)', 'my-theme' );
                break;
            case 'newsarticle':
                $config['label'] = __( 'News Article (timely news)', 'my-theme' );
                break;
            case 'blogposting':
                $config['label'] = __( 'Blog Post (informal)', 'my-theme' );
                break;
        }
    }

    return $types;
});
```

---

## API Reference

### Schema_Registry

Singleton registry for schema type management.

**File:** `includes/Schema/class-schema-registry.php`

#### `Schema_Registry::instance()`

Get the singleton instance.

```php
$registry = \Saman\SEO\Schema\Schema_Registry::instance();
```

**Returns:** `Schema_Registry` - The singleton instance.

---

#### `Schema_Registry::register( $slug, $class_name, $args )`

Register a schema type.

```php
$registry->register(
    'my_type',
    MyPlugin\Schema\My_Schema::class,
    [
        'label'      => 'My Type',
        'post_types' => [ 'post', 'page' ],
        'priority'   => 15,
    ]
);
```

**Parameters:**
- `$slug` (string) - Unique type identifier
- `$class_name` (string) - Fully qualified class name extending Abstract_Schema
- `$args` (array) - Configuration options:
  - `label` (string) - Human-readable label
  - `post_types` (array) - Post types this applies to
  - `priority` (int) - Processing order (default: 10)

**Returns:** void

---

#### `Schema_Registry::get_types()`

Get all registered schema types.

```php
$types = $registry->get_types();
// Returns: [ 'article' => [...], 'webpage' => [...], ... ]
```

**Returns:** `array` - All registered types (filterable via `saman_seo_schema_types`).

---

#### `Schema_Registry::has( $slug )`

Check if a schema type is registered.

```php
if ( $registry->has( 'article' ) ) {
    // Article type is registered
}
```

**Parameters:**
- `$slug` (string) - Type slug to check

**Returns:** `bool` - True if registered.

---

#### `Schema_Registry::make( $slug, $context )`

Create a schema instance for a registered type.

```php
$context = \Saman\SEO\Schema\Schema_Context::from_current();
$schema  = $registry->make( 'article', $context );

if ( $schema && $schema->is_needed() ) {
    $data = $schema->generate();
}
```

**Parameters:**
- `$slug` (string) - Type slug
- `$context` (Schema_Context) - Context object

**Returns:** `Abstract_Schema|null` - Schema instance or null if not registered.

---

#### `Schema_Registry::get( $slug )`

Get configuration for a registered type.

```php
$config = $registry->get( 'article' );
// Returns: [ 'class' => '...', 'label' => '...', 'post_types' => [...], 'priority' => 10 ]
```

**Parameters:**
- `$slug` (string) - Type slug

**Returns:** `array|null` - Type configuration or null if not registered.

---

### Abstract_Schema

Base class for all schema implementations.

**File:** `includes/Schema/class-abstract-schema.php`

#### `Abstract_Schema::__construct( $context )`

Constructor receives context with all environment data.

```php
// Typically called by Schema_Registry::make()
$schema = new My_Schema( $context );
```

**Parameters:**
- `$context` (Schema_Context) - Environment context

---

#### `Abstract_Schema::is_needed()`

Determine if this schema should be output.

```php
public function is_needed(): bool {
    return $this->context->post instanceof \WP_Post
        && 'product' === $this->context->post_type;
}
```

**Returns:** `bool` - True if schema should be included.

---

#### `Abstract_Schema::generate()`

Generate the schema array.

```php
public function generate(): array {
    return [
        '@type' => $this->get_type(),
        '@id'   => $this->get_id(),
        'name'  => get_the_title( $this->context->post ),
    ];
}
```

**Returns:** `array` - Schema data (without @context).

---

#### `Abstract_Schema::get_type()`

Get the schema @type value.

```php
public function get_type() {
    return 'Product';
    // Or for multi-typed: return [ 'Article', 'NewsArticle' ];
}
```

**Returns:** `string|array` - The @type value(s).

---

#### `Abstract_Schema::get_id()` (protected)

Generate a standard @id for this schema.

```php
// Default implementation:
protected function get_id(): string {
    $type = $this->get_type();
    if ( is_array( $type ) ) {
        $type = reset( $type );
    }
    return $this->context->canonical . '#' . strtolower( $type );
}

// Override in subclass:
protected function get_id(): string {
    return \Saman\SEO\Schema\Schema_IDs::article( $this->context->canonical );
}
```

**Returns:** `string` - URL#fragment identifier.

---

### Schema_Context

Value object containing environment data for schema generation.

**File:** `includes/Schema/class-schema-context.php`

#### Properties

| Property | Type | Description |
|----------|------|-------------|
| `$canonical` | string | Canonical URL for current page |
| `$site_url` | string | Site home URL (home_url('/')) |
| `$site_name` | string | Site name (get_bloginfo('name')) |
| `$post` | WP_Post\|null | Current post object or null |
| `$post_type` | string | Post type slug or empty string |
| `$meta` | array | Post SEO meta from _SAMAN_SEO_meta |
| `$schema_type` | string | Determined schema type for post |

#### `Schema_Context::from_current()`

Create context from current WordPress state.

```php
$context = \Saman\SEO\Schema\Schema_Context::from_current();
```

**Returns:** `Schema_Context` - Populated context object.

---

#### `Schema_Context::from_post( $post )`

Create context from an explicit post.

```php
$post    = get_post( 123 );
$context = \Saman\SEO\Schema\Schema_Context::from_post( $post );
```

**Parameters:**
- `$post` (WP_Post) - The post to create context for

**Returns:** `Schema_Context` - Populated context object.

---

### Schema_IDs

Static helper class for generating consistent @id values.

**File:** `includes/Schema/class-schema-ids.php`

All methods return URL#fragment identifiers for cross-referencing schemas in the @graph.

#### Methods

```php
use Saman\SEO\Schema\Schema_IDs;

// Site-level schemas (no parameters)
Schema_IDs::website();       // https://example.com/#website
Schema_IDs::organization();  // https://example.com/#organization
Schema_IDs::person();        // https://example.com/#person
Schema_IDs::localbusiness(); // https://example.com/#localbusiness

// Page-level schemas (require URL)
Schema_IDs::webpage( $url );    // https://example.com/page/#webpage
Schema_IDs::article( $url );    // https://example.com/page/#article
Schema_IDs::breadcrumb( $url ); // https://example.com/page/#breadcrumb
Schema_IDs::faqpage( $url );    // https://example.com/page/#faqpage
Schema_IDs::howto( $url );      // https://example.com/page/#howto

// User-level schemas (require user ID)
Schema_IDs::author( $user_id ); // https://example.com/author/name/#author
```

**Example Usage in Schema Class:**

```php
public function generate(): array {
    return [
        '@type'     => 'Article',
        '@id'       => Schema_IDs::article( $this->context->canonical ),
        'isPartOf'  => [ '@id' => Schema_IDs::webpage( $this->context->canonical ) ],
        'publisher' => [ '@id' => Schema_IDs::organization() ],
    ];
}
```

---

## Complete Examples

### Example 1: Full Custom Schema Type with Registration

A complete example of a Recipe schema for food blogs:

```php
<?php
/**
 * Plugin Name: Recipe Schema for Saman SEO
 * Description: Adds Recipe schema support
 * Version: 1.0.0
 */

namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

// Only load if Saman SEO is active
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'Saman\SEO\Schema\Abstract_Schema' ) ) {
        return;
    }

    // Define the schema class
    class Recipe_Schema extends Abstract_Schema {

        public function get_type() {
            return 'Recipe';
        }

        public function is_needed(): bool {
            return $this->context->post instanceof \WP_Post
                && 'recipe' === $this->context->post_type;
        }

        public function generate(): array {
            $post = $this->context->post;
            $id   = $post->ID;

            $schema = [
                '@type'       => $this->get_type(),
                '@id'         => $this->context->canonical . '#recipe',
                'name'        => get_the_title( $post ),
                'description' => get_the_excerpt( $post ),
                'url'         => $this->context->canonical,
                'author'      => [
                    '@type' => 'Person',
                    'name'  => get_the_author_meta( 'display_name', $post->post_author ),
                ],
                'datePublished' => get_the_date( 'c', $post ),
            ];

            // Image
            if ( has_post_thumbnail( $post ) ) {
                $schema['image'] = get_the_post_thumbnail_url( $post, 'full' );
            }

            // Prep time
            $prep_time = get_post_meta( $id, '_recipe_prep_time', true );
            if ( $prep_time ) {
                $schema['prepTime'] = 'PT' . intval( $prep_time ) . 'M';
            }

            // Cook time
            $cook_time = get_post_meta( $id, '_recipe_cook_time', true );
            if ( $cook_time ) {
                $schema['cookTime'] = 'PT' . intval( $cook_time ) . 'M';
            }

            // Total time
            if ( $prep_time && $cook_time ) {
                $total = intval( $prep_time ) + intval( $cook_time );
                $schema['totalTime'] = 'PT' . $total . 'M';
            }

            // Yield
            $yield = get_post_meta( $id, '_recipe_yield', true );
            if ( $yield ) {
                $schema['recipeYield'] = $yield;
            }

            // Ingredients
            $ingredients = get_post_meta( $id, '_recipe_ingredients', true );
            if ( is_array( $ingredients ) && ! empty( $ingredients ) ) {
                $schema['recipeIngredient'] = $ingredients;
            }

            // Instructions
            $instructions = get_post_meta( $id, '_recipe_instructions', true );
            if ( is_array( $instructions ) && ! empty( $instructions ) ) {
                $schema['recipeInstructions'] = array_map( function( $step, $index ) {
                    return [
                        '@type' => 'HowToStep',
                        'position' => $index + 1,
                        'text' => $step,
                    ];
                }, $instructions, array_keys( $instructions ) );
            }

            // Nutrition
            $calories = get_post_meta( $id, '_recipe_calories', true );
            if ( $calories ) {
                $schema['nutrition'] = [
                    '@type'    => 'NutritionInformation',
                    'calories' => $calories . ' calories',
                ];
            }

            return $schema;
        }
    }

    // Register the schema type
    add_action( 'saman_seo_register_schema_type', function( $registry ) {
        $registry->register(
            'myplugin_recipe',
            Recipe_Schema::class,
            [
                'label'      => __( 'Recipe', 'my-plugin' ),
                'post_types' => [ 'recipe' ],
                'priority'   => 15,
            ]
        );
    });
});
```

### Example 2: Modifying Article Schema with Speakable

```php
<?php
/**
 * Add speakable specification to Article schemas for voice search.
 */
add_filter( 'saman_seo_schema_article_fields', function( $data, $context ) {
    $data['speakable'] = [
        '@type'       => 'SpeakableSpecification',
        'cssSelector' => [
            '.entry-title',
            '.entry-content > p:first-of-type',
            '.entry-content > p:nth-of-type(2)',
        ],
    ];

    return $data;
}, 10, 2 );
```

### Example 3: Removing Schema Types from Dropdown

```php
<?php
/**
 * Remove NewsArticle from non-news sites.
 */
add_filter( 'saman_seo_schema_types', function( $types ) {
    // Only keep Article and BlogPosting for standard blogs
    $allowed = [ 'article', 'blogposting', 'webpage', 'website' ];

    return array_filter( $types, function( $slug ) use ( $allowed ) {
        return in_array( $slug, $allowed, true );
    }, ARRAY_FILTER_USE_KEY );
});
```

### Example 4: Conditional Schema Based on Post Meta

```php
<?php
/**
 * Add Video schema to articles that have embedded videos.
 */
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    if ( ! $context->post ) {
        return $piece;
    }

    $video_url = get_post_meta( $context->post->ID, '_featured_video_url', true );

    if ( ! $video_url ) {
        return $piece;
    }

    // Add video object to article
    $piece['video'] = [
        '@type'        => 'VideoObject',
        'name'         => get_the_title( $context->post ),
        'description'  => get_the_excerpt( $context->post ),
        'thumbnailUrl' => get_the_post_thumbnail_url( $context->post, 'full' ),
        'contentUrl'   => $video_url,
        'uploadDate'   => get_the_date( 'c', $context->post ),
    ];

    return $piece;
}, 10, 2 );
```

### Example 5: WooCommerce Product Schema Integration

```php
<?php
/**
 * Product schema for WooCommerce.
 */

namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;

class Product_Schema extends Abstract_Schema {

    public function get_type() {
        return 'Product';
    }

    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post
            && 'product' === $this->context->post_type
            && function_exists( 'wc_get_product' );
    }

    public function generate(): array {
        $product = wc_get_product( $this->context->post->ID );

        if ( ! $product ) {
            return [];
        }

        $schema = [
            '@type'       => $this->get_type(),
            '@id'         => $this->context->canonical . '#product',
            'name'        => $product->get_name(),
            'description' => $product->get_short_description(),
            'url'         => $this->context->canonical,
            'sku'         => $product->get_sku(),
        ];

        // Image
        $image_id = $product->get_image_id();
        if ( $image_id ) {
            $schema['image'] = wp_get_attachment_url( $image_id );
        }

        // Offers
        $schema['offers'] = [
            '@type'           => 'Offer',
            'price'           => $product->get_price(),
            'priceCurrency'   => get_woocommerce_currency(),
            'availability'    => $product->is_in_stock()
                ? 'https://schema.org/InStock'
                : 'https://schema.org/OutOfStock',
            'url'             => $this->context->canonical,
            'priceValidUntil' => date( 'Y-12-31' ),
        ];

        // Brand
        $brand = $product->get_attribute( 'brand' );
        if ( $brand ) {
            $schema['brand'] = [
                '@type' => 'Brand',
                'name'  => $brand,
            ];
        }

        // Reviews
        if ( $product->get_review_count() > 0 ) {
            $schema['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => $product->get_average_rating(),
                'reviewCount' => $product->get_review_count(),
            ];
        }

        return $schema;
    }
}

// Register
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    if ( ! class_exists( 'WooCommerce' ) ) {
        return;
    }

    $registry->register(
        'woo_product',
        Product_Schema::class,
        [
            'label'      => __( 'Product', 'my-plugin' ),
            'post_types' => [ 'product' ],
            'priority'   => 15,
        ]
    );
});
```

---

## Troubleshooting

### Registration Timing Issues

**Problem:** Custom schema type not appearing in output or admin dropdowns.

**Cause:** Hooking at `init` or `plugins_loaded` which runs before Saman SEO registers core types.

**Solution:** Always use the `saman_seo_register_schema_type` action:

```php
// WRONG - too early
add_action( 'init', function() {
    $registry = \Saman\SEO\Schema\Schema_Registry::instance();
    $registry->register( ... );
});

// CORRECT - proper timing
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register( ... );
});
```

---

### @context Duplication

**Problem:** JSON-LD validator shows duplicate or nested @context.

**Cause:** Custom schema class includes `'@context' => 'https://schema.org'` in generate() return.

**Solution:** Never include @context in schema pieces:

```php
// WRONG
public function generate(): array {
    return [
        '@context' => 'https://schema.org', // DON'T DO THIS
        '@type'    => 'Product',
        'name'     => 'Example',
    ];
}

// CORRECT
public function generate(): array {
    return [
        '@type' => 'Product',
        'name'  => 'Example',
    ];
}
```

---

### Missing is_needed() Implementation

**Problem:** Custom schema appears on every page instead of just target pages.

**Cause:** is_needed() returns true unconditionally.

**Solution:** Implement proper conditional checks:

```php
// WRONG - outputs everywhere
public function is_needed(): bool {
    return true;
}

// CORRECT - outputs only on product pages
public function is_needed(): bool {
    return $this->context->post instanceof \WP_Post
        && 'product' === $this->context->post_type;
}
```

---

### Breaking Schema Graph

**Problem:** Other schemas stop working after adding custom filter.

**Cause:** Filter callback doesn't return the value.

**Solution:** Always return the filtered value:

```php
// WRONG - breaks all article schemas
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    $piece['customField'] = 'value';
    // Missing return!
});

// CORRECT
add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    $piece['customField'] = 'value';
    return $piece; // Always return
}, 10, 2 );
```

---

### Namespace Collisions

**Problem:** Schema type conflicts with existing type or another plugin.

**Cause:** Using generic slugs like 'product' or 'event'.

**Solution:** Prefix custom slugs with your plugin identifier:

```php
// WRONG - may conflict
$registry->register( 'product', ... );
$registry->register( 'event', ... );

// CORRECT - namespaced
$registry->register( 'myplugin_product', ... );
$registry->register( 'myplugin_event', ... );
```

---

### Class Not Found Errors

**Problem:** Fatal error when Saman SEO is deactivated.

**Cause:** Extending Abstract_Schema without checking if it exists.

**Solution:** Check class existence before defining dependent classes:

```php
// WRONG - fatal error if Saman SEO inactive
class My_Schema extends \Saman\SEO\Schema\Abstract_Schema {
    // ...
}

// CORRECT - safe loading
if ( class_exists( 'Saman\SEO\Schema\Abstract_Schema' ) ) {
    class My_Schema extends \Saman\SEO\Schema\Abstract_Schema {
        // ...
    }
}

// Or wrap in plugins_loaded
add_action( 'plugins_loaded', function() {
    if ( ! class_exists( 'Saman\SEO\Schema\Abstract_Schema' ) ) {
        return;
    }

    // Define classes here
});
```

---

### Debugging Schema Output

**Check if schema is being generated:**

```php
add_action( 'wp_footer', function() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $registry = \Saman\SEO\Schema\Schema_Registry::instance();
    $context  = \Saman\SEO\Schema\Schema_Context::from_current();

    echo '<pre style="display:none">';
    echo 'Schema Type: ' . $context->schema_type . "\n";
    echo 'Post Type: ' . $context->post_type . "\n";
    echo 'Registered Types: ' . print_r( array_keys( $registry->get_types() ), true );
    echo '</pre>';
});
```

**Validate JSON-LD output:**

1. View page source and find `<script type="application/ld+json">`
2. Copy the JSON content
3. Validate at https://validator.schema.org/
4. Test with Google's Rich Results Test: https://search.google.com/test/rich-results

---

## Related Documentation

- **[Filter Reference](FILTERS.md)** - Complete filter hooks reference
- **[Developer Guide](DEVELOPER_GUIDE.md)** - General plugin development documentation
- **[Getting Started](GETTING_STARTED.md)** - Basic plugin usage

---

**For questions or contributions, visit the [GitHub repository](https://github.com/SamanLabs/Saman-SEO).**
