# Phase 6: Developer API - Research

**Researched:** 2026-01-23
**Domain:** WordPress Plugin API, Hooks Architecture, Developer Documentation
**Confidence:** HIGH

## Summary

Phase 6 implements the Developer API that allows third-party developers to extend the Saman SEO schema system. The research confirms that the existing codebase already has partial hook infrastructure in place:

- `saman_seo_schema_types` filter already exists in Schema_Registry::get_types()
- `saman_seo_schema_{$slug}_output` filter already exists in Schema_Graph_Manager::build()
- `saman_seo_schema_graph` filter already exists in Schema_Graph_Manager::build()
- `saman_seo_schema_type_registered` action already fires after registration

What's missing are:
1. A public `saman_seo_register_schema_type` action hook that fires at an appropriate time for third-party registration
2. A `saman_seo_schema_{type}_fields` filter for modifying schema fields before generation
3. Developer documentation explaining how to use these APIs

The recommended approach follows Yoast SEO's pattern: provide an action hook that fires after core schemas are registered but before output, allowing third-party code to call `Schema_Registry::instance()->register()`. This is simpler than Yoast's `wpseo_schema_graph_pieces` filter approach and aligns with the existing registry architecture.

**Primary recommendation:** Add `saman_seo_register_schema_type` action in Plugin::boot() after core registrations, add `saman_seo_schema_{type}_fields` filter in Abstract_Schema, and create comprehensive developer documentation in `docs/SCHEMA_DEVELOPER_API.md`.

## Standard Stack

### Core
| Component | Implementation | Purpose | Why Standard |
|-----------|----------------|---------|--------------|
| WordPress Hooks API | `do_action()`, `apply_filters()` | Extensibility system | WordPress standard, well-documented |
| Schema_Registry | Singleton via `Schema_Registry::instance()` | Central type registration | Already established in codebase |
| Abstract_Schema | Base class for all schemas | Consistent schema interface | Existing proven pattern |
| PHPDoc DocBlocks | Inline documentation | Developer reference | WordPress Coding Standards requirement |

### Supporting
| Component | Implementation | When to Use |
|-----------|----------------|-------------|
| `saman_seo_` prefix | All public hooks | Namespace isolation per WordPress best practices |
| Dynamic hook names | `saman_seo_schema_{$type}_*` | Per-type customization |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Action for registration | Filter returning pieces (Yoast style) | Action is simpler; developers call register() directly vs returning class instances |
| Singleton registry access | Dependency injection | Singleton is established pattern; DI would require architectural changes |
| PHP-only documentation | Inline + external markdown | Both needed: PHPDoc for IDE support, markdown for human reading |

## Architecture Patterns

### Recommended Hook Structure

```
Plugin::boot() execution order:
  1. Core schema registrations (existing)
  2. do_action('saman_seo_register_schema_type', $registry)  <- NEW
  3. Service registrations (existing)
  4. do_action('SAMAN_SEO_booted', $this) (existing)

Schema_Graph_Manager::build() execution order:
  1. Get types from registry (existing saman_seo_schema_types filter)
  2. For each schema:
     a. Generate base data
     b. apply_filters("saman_seo_schema_{$slug}_fields", $data, $context)  <- NEW
     c. Generate final output
     d. apply_filters("saman_seo_schema_{$slug}_output", $piece, $context) (existing)
  3. apply_filters('saman_seo_schema_graph', $graph, $context) (existing)
```

### Pattern 1: Registration Action Hook

**What:** Dedicated action for third-party schema registration
**When to use:** When a plugin needs to add a completely new schema type
**Example:**
```php
// Source: WordPress Plugin API Handbook + existing Saman SEO patterns

/**
 * Fires after core schema types are registered.
 *
 * Third-party developers use this hook to register custom schema types
 * that will be included in the JSON-LD graph output.
 *
 * @since 1.0.0
 *
 * @param Schema_Registry $registry The schema registry instance.
 */
do_action( 'saman_seo_register_schema_type', $registry );

// Developer usage:
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register(
        'product',
        My_Plugin\Schema\Product_Schema::class,
        [
            'label'      => __( 'Product', 'my-plugin' ),
            'post_types' => [ 'product' ],
            'priority'   => 15,
        ]
    );
});
```

### Pattern 2: Fields Filter

**What:** Filter to modify schema data before final output
**When to use:** When modifying existing schema types without replacing them entirely
**Example:**
```php
// Source: Yoast wpseo_schema_article pattern + WordPress filter conventions

/**
 * Filters the schema fields before generation.
 *
 * Allows modification of the base schema data that will be
 * processed by generate(). Use this to add or modify fields
 * before the schema piece is built.
 *
 * @since 1.0.0
 *
 * @param array          $data    The schema data array.
 * @param Schema_Context $context The current schema context.
 */
$data = apply_filters( "saman_seo_schema_{$slug}_fields", $data, $this->context );

// Developer usage:
add_filter( 'saman_seo_schema_article_fields', function( $data, $context ) {
    // Add custom field to Article schema
    $data['speakable'] = [
        '@type' => 'SpeakableSpecification',
        'cssSelector' => [ '.article-title', '.article-body' ],
    ];
    return $data;
}, 10, 2 );
```

### Pattern 3: Extending Abstract_Schema

**What:** Third-party schema classes extending Abstract_Schema
**When to use:** Creating new schema types for custom content
**Example:**
```php
// Source: Existing Article_Schema pattern

namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

/**
 * Custom Product schema for WooCommerce integration.
 */
class Product_Schema extends Abstract_Schema {

    /**
     * Get the schema @type value.
     *
     * @return string
     */
    public function get_type() {
        return 'Product';
    }

    /**
     * Determine if schema should output.
     *
     * @return bool
     */
    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post
            && 'product' === $this->context->post_type;
    }

    /**
     * Generate Product schema.
     *
     * @return array
     */
    public function generate(): array {
        $post = $this->context->post;

        return [
            '@type' => $this->get_type(),
            '@id'   => $this->get_id(),
            'name'  => get_the_title( $post ),
            'description' => get_the_excerpt( $post ),
            'image' => get_the_post_thumbnail_url( $post, 'full' ),
            // ... custom fields
        ];
    }
}
```

### Pattern 4: Documentation Structure

**What:** Markdown documentation with code examples
**When to use:** All public APIs must be documented
**Example:**
```markdown
## Registering Custom Schema Types

### Basic Registration

```php
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register( 'my-type', MySchema::class, [
        'label'    => 'My Type',
        'priority' => 15,
    ]);
});
```

### Schema Class Requirements

Your schema class MUST:
- Extend `Saman\SEO\Schema\Abstract_Schema`
- Implement `get_type()` returning the @type value
- Implement `is_needed()` returning boolean for when to output
- Implement `generate()` returning the schema array

Your schema class MUST NOT:
- Include `@context` in the returned array (Graph_Manager adds this)
- Output directly (return array, don't echo)
```

### Anti-Patterns to Avoid

- **Including @context in custom schemas:** Only Graph_Manager adds @context at root. Custom schemas return pieces without it.
- **Hooking at wrong timing:** Use `saman_seo_register_schema_type` not `init` or `plugins_loaded` for registration.
- **Modifying registry directly before action:** Always use the official hook for consistent execution order.
- **Not checking is_needed():** Custom schemas must implement proper conditional logic to avoid outputting on wrong pages.
- **Hardcoding @id values:** Use Schema_IDs helper or context->canonical for consistent identifiers.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Schema registration timing | Custom init hooks | `saman_seo_register_schema_type` action | Guarantees correct order after core schemas |
| Type filtering | Custom type arrays | `saman_seo_schema_types` filter | Centralized, filterable registry |
| Output modification | Custom JSON-LD output | `saman_seo_schema_{type}_output` filter | Integrates with existing graph system |
| ID generation | Manual URL#fragment | Schema_IDs helper class | Consistent ID format across all schemas |
| Context data access | Global $post, get_option() | Schema_Context object | Centralized, testable, consistent |

**Key insight:** The existing architecture already provides extensibility points. This phase formalizes them with documentation and adds the missing fields filter. Third-party developers should leverage the existing Abstract_Schema and Schema_IDs infrastructure rather than building parallel systems.

## Common Pitfalls

### Pitfall 1: Registration Timing Issues

**What goes wrong:** Custom schema type not appearing in output or UI dropdowns
**Why it happens:** Developer hooks at `init` or `plugins_loaded` which runs before Saman SEO registers core types
**How to avoid:** Always use `saman_seo_register_schema_type` action which fires at correct time
**Warning signs:** Schema works in isolation but not when plugin active

### Pitfall 2: @context Duplication

**What goes wrong:** JSON-LD validator shows duplicate/nested @context
**Why it happens:** Custom schema class includes `'@context' => 'https://schema.org'` in generate() return
**How to avoid:** Never include @context in schema pieces; Graph_Manager handles this at root
**Warning signs:** JSON-LD output has @context inside @graph pieces

### Pitfall 3: Missing is_needed() Implementation

**What goes wrong:** Custom schema appears on every page
**Why it happens:** is_needed() returns true unconditionally or not implemented properly
**How to avoid:** Implement proper conditional checks (post type, page type, meta values)
**Warning signs:** Product schema showing on blog posts, LocalBusiness on every page

### Pitfall 4: Breaking Schema Graph

**What goes wrong:** Other schemas stop working after adding custom schema
**Why it happens:** Filter callback doesn't return modified value, or returns wrong type
**How to avoid:** Always return the filtered value even if unmodified; validate return types
**Warning signs:** Empty @graph array, missing WebSite or Organization schemas

### Pitfall 5: Namespace Collisions

**What goes wrong:** Schema type slug conflicts with existing type
**Why it happens:** Using generic slugs like 'article' or 'product' that Saman SEO or other plugins use
**How to avoid:** Prefix custom slugs with plugin identifier (e.g., 'myplugin_product')
**Warning signs:** Unexpected schema output, registry overwriting

### Pitfall 6: Undocumented Dependencies

**What goes wrong:** Custom schema silently fails in some environments
**Why it happens:** Relying on Saman SEO classes without checking plugin is active
**How to avoid:** Check `class_exists('Saman\SEO\Schema\Abstract_Schema')` before defining dependent classes
**Warning signs:** Fatal errors when Saman SEO deactivated, no schema output

## Code Examples

### Complete Custom Schema Registration

```php
<?php
/**
 * Example: Registering a custom Event schema type.
 *
 * Source: Combines Yoast wpseo_schema_graph_pieces pattern with
 * existing Saman SEO Abstract_Schema architecture
 */

namespace MyPlugin\Schema;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_Context;

// Only define if Saman SEO is active
if ( ! class_exists( 'Saman\SEO\Schema\Abstract_Schema' ) ) {
    return;
}

/**
 * Event schema for event post types.
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
        $post = $this->context->post;

        $schema = [
            '@type'       => $this->get_type(),
            '@id'         => $this->context->canonical . '#event',
            'name'        => get_the_title( $post ),
            'description' => get_the_excerpt( $post ),
            'url'         => $this->context->canonical,
            'startDate'   => get_post_meta( $post->ID, '_event_start', true ),
        ];

        // Optional end date
        $end_date = get_post_meta( $post->ID, '_event_end', true );
        if ( $end_date ) {
            $schema['endDate'] = $end_date;
        }

        // Optional location
        $location = $this->get_location();
        if ( $location ) {
            $schema['location'] = $location;
        }

        return $schema;
    }

    /**
     * Build location object.
     *
     * @return array|null
     */
    private function get_location(): ?array {
        $venue = get_post_meta( $this->context->post->ID, '_event_venue', true );
        if ( empty( $venue ) ) {
            return null;
        }

        return [
            '@type' => 'Place',
            'name'  => $venue,
        ];
    }
}

// Register the schema type
add_action( 'saman_seo_register_schema_type', function( $registry ) {
    $registry->register(
        'myplugin_event', // Prefixed to avoid conflicts
        Event_Schema::class,
        [
            'label'      => __( 'Event', 'my-plugin' ),
            'post_types' => [ 'event' ],
            'priority'   => 15,
        ]
    );
});
```

### Modifying Existing Schema Fields

```php
<?php
/**
 * Example: Adding speakable specification to Article schema.
 *
 * Source: WordPress apply_filters pattern + Google speakable docs
 */

add_filter( 'saman_seo_schema_article_fields', function( $data, $context ) {
    // Add speakable for voice search optimization
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

### Filtering Final Schema Output

```php
<?php
/**
 * Example: Modifying Article schema output.
 *
 * Source: Existing saman_seo_schema_{slug}_output filter
 */

add_filter( 'saman_seo_schema_article_output', function( $piece, $context ) {
    // Add custom publisher logo dimensions
    if ( isset( $piece['publisher']['logo'] ) ) {
        $piece['publisher']['logo']['width'] = 600;
        $piece['publisher']['logo']['height'] = 60;
    }

    // Add isAccessibleForFree
    $piece['isAccessibleForFree'] = true;

    return $piece;
}, 10, 2 );
```

### Filtering Available Schema Types

```php
<?php
/**
 * Example: Removing schema types from admin dropdown.
 *
 * Source: Existing saman_seo_schema_types filter
 */

add_filter( 'saman_seo_schema_types', function( $types ) {
    // Remove NewsArticle for non-news sites
    unset( $types['newsarticle'] );

    // Change label
    if ( isset( $types['article'] ) ) {
        $types['article']['label'] = __( 'Standard Article', 'my-theme' );
    }

    return $types;
});
```

### PHPDoc for Hook Documentation

```php
/**
 * Fires after core schema types are registered.
 *
 * Allows third-party plugins to register custom schema types that will
 * be included in the JSON-LD @graph output. Custom schemas must extend
 * Abstract_Schema and implement the required methods.
 *
 * @since 1.0.0
 *
 * @param Schema_Registry $registry The schema registry singleton instance.
 *                                  Use $registry->register() to add types.
 *
 * @example
 * // Register a custom schema type
 * add_action( 'saman_seo_register_schema_type', function( $registry ) {
 *     $registry->register( 'myslug', MySchema::class, [
 *         'label'    => 'My Schema',
 *         'priority' => 15,
 *     ]);
 * });
 */
do_action( 'saman_seo_register_schema_type', $registry );
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Single JSON-LD filter | Registry + Graph architecture | Phase 1 of this project | Cleaner extensibility |
| Undocumented hooks | Formal API with docs | This phase | Third-party developer support |
| Global post access | Schema_Context dependency injection | Phase 1 | Testable, predictable |

**Deprecated/outdated:**
- `SAMAN_SEO_jsonld_graph` filter: Still works (legacy) but `saman_seo_schema_graph` preferred
- Direct hook into JsonLD service: Use registry instead
- Modifying JSON-LD output string: Use array filters before encoding

## Open Questions

1. **Fields Filter Placement**
   - What we know: Yoast applies filters after generate(), we could apply before
   - What's unclear: Should filter be on input data (before generate) or output data (after generate)?
   - Recommendation: Add fields filter in generate() methods of base schemas, allowing modification of data before building. The existing `_output` filter handles post-generation modification.

2. **Schema_Context Extensibility**
   - What we know: Context is a value object with fixed properties
   - What's unclear: Should third-party devs be able to add custom context properties?
   - Recommendation: Defer to v2. Current context covers common needs. Advanced use cases can store data in post meta and access via $context->meta.

3. **Version Compatibility Documentation**
   - What we know: PHP 7.4+, WordPress 5.0+ are plugin requirements
   - What's unclear: Should docs specify minimum Saman SEO version for API stability?
   - Recommendation: Document that Developer API is available from version 1.0.0 (this release) and note any breaking changes in future versions.

## Sources

### Primary (HIGH confidence)
- [WordPress Plugin Handbook - Hooks](https://developer.wordpress.org/plugins/hooks/) - Action/filter patterns
- [WordPress PHP Documentation Standards](https://developer.wordpress.org/coding-standards/inline-documentation-standards/php/) - PHPDoc format for hooks
- [Yoast SEO Schema API](https://developer.yoast.com/features/schema/api/) - Industry standard implementation patterns
- Existing codebase: `class-schema-registry.php`, `class-schema-graph-manager.php`, `class-abstract-schema.php`
- Existing documentation: `docs/FILTERS.md`, `docs/DEVELOPER_GUIDE.md`

### Secondary (MEDIUM confidence)
- [RankMath Filters and Hooks API](https://rankmath.com/kb/filters-hooks-api-developer/) - Alternative implementation approach
- [WordPress Custom Hooks Documentation](https://developer.wordpress.org/plugins/hooks/custom-hooks/) - Best practices for creating hooks

### Tertiary (LOW confidence)
- WebSearch for WordPress plugin extensibility patterns

## Documentation Requirements

Phase 6 includes documentation as a requirement (DEV-05). The documentation should be created in `docs/SCHEMA_DEVELOPER_API.md` following the existing FILTERS.md and DEVELOPER_GUIDE.md patterns:

### Recommended Documentation Structure

```markdown
# Schema Developer API

## Overview
- What the API enables
- When to use each hook

## Registering Custom Schema Types
- saman_seo_register_schema_type action
- Schema class requirements
- Complete example

## Modifying Existing Schemas
- saman_seo_schema_{type}_fields filter
- saman_seo_schema_{type}_output filter
- saman_seo_schema_types filter

## API Reference
- Schema_Registry methods
- Abstract_Schema interface
- Schema_Context properties
- Schema_IDs helpers

## Complete Examples
- Custom schema type (full code)
- Modifying Article schema
- Filtering available types

## Troubleshooting
- Common issues and solutions
- Debugging tips
```

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - WordPress hooks API is well-established
- Architecture: HIGH - Building on existing codebase patterns
- Pitfalls: HIGH - Based on Yoast/RankMath docs and WordPress development experience
- Documentation: HIGH - Following existing project patterns

**Research date:** 2026-01-23
**Valid until:** 2026-03-23 (60 days - WordPress hooks API is stable)
