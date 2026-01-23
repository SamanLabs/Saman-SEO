# Phase 1: Schema Engine Foundation - Research

**Researched:** 2026-01-23
**Domain:** WordPress PHP plugin architecture, JSON-LD structured data, schema registry patterns
**Confidence:** HIGH

## Summary

This research examines how to build an extensible schema engine for WordPress that follows industry-standard patterns. The analysis covers three areas: (1) JSON-LD graph structure best practices from W3C and schema.org, (2) WordPress SEO plugin schema architectures (Yoast, Rank Math), and (3) PHP design patterns for registry and migration.

The existing Saman SEO codebase already has a functional JSON-LD service (`class-saman-seo-service-jsonld.php`) that outputs WebSite, WebPage, Article, and Breadcrumb schemas via the `SAMAN_SEO_jsonld` and `SAMAN_SEO_jsonld_graph` filters. Multiple schema services (Video, Course, Book, etc.) hook into these filters via their `boot()` methods. The challenge is refactoring this into a registry-based architecture without breaking existing output.

**Primary recommendation:** Implement an Abstract_Schema class with `is_needed()` and `generate()` methods (following Yoast's proven pattern), a Schema_Registry singleton for type registration, and a Graph_Manager that orchestrates collection and output. Migrate existing schemas by wrapping current output logic in new class structure while maintaining filter compatibility.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Component | Implementation | Purpose | Why Standard |
|-----------|----------------|---------|--------------|
| Registry | Singleton with registration API | Stores schema type definitions | Industry standard for plugin extensibility (Yoast, Rank Math both use this) |
| Base Schema Class | Abstract class with interface | Common structure for all schemas | Enforces contract, enables polymorphism |
| Graph Manager | Service class | Combines schemas into @graph array | Separation of concerns, testable |
| Context Object | Value object passed to schemas | Environment data (URL, post, site info) | Decouples schemas from global state |

### Supporting
| Component | Implementation | When to Use |
|-----------|----------------|-------------|
| Schema_IDs helper | Static class with constants | Generating consistent @id values across schemas |
| Filter hooks | WordPress add_filter/apply_filters | Extension points at key stages |
| Migration adapter | Wrapper service | Bridge old JsonLD service to new engine during transition |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Singleton Registry | Dependency Injection Container | DI is cleaner but adds complexity; WordPress plugins typically use singletons |
| Abstract class | Interface-only | Abstract class provides shared implementation; interface requires more boilerplate |
| WordPress filters | Event dispatcher | Filters are WordPress-native and familiar to developers |

**Installation:**
No external dependencies required - pure PHP using WordPress APIs.

## Architecture Patterns

### Recommended Project Structure
```
includes/
  Schema/                           # New schema engine namespace
    class-abstract-schema.php       # Base schema class
    class-schema-registry.php       # Type registration
    class-schema-graph-manager.php  # Graph composition
    class-schema-context.php        # Context value object
    class-schema-ids.php            # ID generation helpers
    Types/                          # Concrete schema implementations
      class-website-schema.php
      class-webpage-schema.php
      class-article-schema.php
      class-breadcrumb-schema.php
      # ... future schema types
```

### Pattern 1: Abstract Schema Piece (Yoast-inspired)

**What:** Base class that all schema types extend, providing common interface and shared logic.

**When to use:** Every schema type implementation.

**Example:**
```php
// Source: Based on Yoast's Abstract_Schema_Piece pattern
// https://developer.yoast.com/features/schema/api/

abstract class Abstract_Schema {

    /** @var Schema_Context */
    protected $context;

    /**
     * Constructor receives context with all environment data.
     *
     * @param Schema_Context $context Environment context.
     */
    public function __construct( Schema_Context $context ) {
        $this->context = $context;
    }

    /**
     * Determine if this schema should be output.
     *
     * @return bool
     */
    abstract public function is_needed(): bool;

    /**
     * Generate the schema array.
     *
     * @return array Schema.org structured data array.
     */
    abstract public function generate(): array;

    /**
     * Get the schema @type value.
     *
     * @return string|array
     */
    abstract public function get_type();

    /**
     * Generate a standard @id for this schema.
     *
     * @return string URL#fragment identifier.
     */
    protected function get_id(): string {
        return $this->context->canonical . '#' . strtolower( $this->get_type() );
    }
}
```

### Pattern 2: Schema Registry (Registration Pattern)

**What:** Central registry that stores and retrieves schema type definitions.

**When to use:** For registering schema types, listing available types, instantiating schemas.

**Example:**
```php
// Source: PHP Registry pattern
// https://designpatternsphp.readthedocs.io/en/latest/Structural/Registry/README.html

class Schema_Registry {

    /** @var Schema_Registry|null */
    private static $instance = null;

    /** @var array<string, string> Type slug => class name */
    private $types = [];

    /**
     * Get singleton instance.
     *
     * @return Schema_Registry
     */
    public static function instance(): Schema_Registry {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a schema type.
     *
     * @param string $slug        Unique type identifier (e.g., 'article').
     * @param string $class_name  Fully qualified class name extending Abstract_Schema.
     * @param array  $args        Optional configuration (label, post_types, etc.).
     */
    public function register( string $slug, string $class_name, array $args = [] ): void {
        $this->types[ $slug ] = [
            'class'      => $class_name,
            'label'      => $args['label'] ?? ucfirst( $slug ),
            'post_types' => $args['post_types'] ?? [],
            'priority'   => $args['priority'] ?? 10,
        ];

        do_action( 'saman_seo_schema_type_registered', $slug, $class_name, $args );
    }

    /**
     * Get all registered types.
     *
     * @return array
     */
    public function get_types(): array {
        return apply_filters( 'saman_seo_schema_types', $this->types );
    }

    /**
     * Check if a type is registered.
     *
     * @param string $slug Type slug.
     * @return bool
     */
    public function has( string $slug ): bool {
        return isset( $this->types[ $slug ] );
    }

    /**
     * Create schema instance for a type.
     *
     * @param string         $slug    Type slug.
     * @param Schema_Context $context Context object.
     * @return Abstract_Schema|null
     */
    public function make( string $slug, Schema_Context $context ): ?Abstract_Schema {
        if ( ! $this->has( $slug ) ) {
            return null;
        }

        $class = $this->types[ $slug ]['class'];
        return new $class( $context );
    }
}
```

### Pattern 3: Graph Manager (Orchestration)

**What:** Service that collects applicable schemas and combines them into a single @graph output.

**When to use:** Final JSON-LD output generation.

**Example:**
```php
// Source: Yoast graph building approach
// https://developer.yoast.com/features/schema/api/

class Schema_Graph_Manager {

    /** @var Schema_Registry */
    private $registry;

    public function __construct( Schema_Registry $registry ) {
        $this->registry = $registry;
    }

    /**
     * Build complete JSON-LD graph for current context.
     *
     * @param Schema_Context $context Environment context.
     * @return array Complete JSON-LD structure with @context and @graph.
     */
    public function build( Schema_Context $context ): array {
        $graph = [];

        // Collect schemas that should be output
        foreach ( $this->registry->get_types() as $slug => $config ) {
            $schema = $this->registry->make( $slug, $context );

            if ( $schema && $schema->is_needed() ) {
                $piece = $schema->generate();
                $piece = apply_filters( "saman_seo_schema_{$slug}_output", $piece, $context );

                if ( ! empty( $piece ) ) {
                    $graph[] = $piece;
                }
            }
        }

        // Allow filtering complete graph
        $graph = apply_filters( 'saman_seo_schema_graph', $graph, $context );
        // Backward compatibility with existing filter
        $graph = apply_filters( 'SAMAN_SEO_jsonld_graph', $graph, $context->post );

        return [
            '@context' => 'https://schema.org',
            '@graph'   => $graph,
        ];
    }
}
```

### Pattern 4: Context Object (Value Object)

**What:** Immutable object containing all environment data schemas need.

**When to use:** Pass to every schema instead of using globals.

**Example:**
```php
class Schema_Context {

    /** @var string Canonical URL */
    public $canonical;

    /** @var string Site URL */
    public $site_url;

    /** @var string Site name */
    public $site_name;

    /** @var \WP_Post|null Current post */
    public $post;

    /** @var string Post type */
    public $post_type;

    /** @var array Post meta */
    public $meta;

    /** @var string Schema type for this post */
    public $schema_type;

    /**
     * Create context from current WordPress state.
     *
     * @return Schema_Context
     */
    public static function from_current(): Schema_Context {
        $context = new self();

        $context->site_url   = home_url( '/' );
        $context->site_name  = get_bloginfo( 'name' );
        $context->post       = get_post();
        $context->post_type  = $context->post ? get_post_type( $context->post ) : '';
        $context->canonical  = $context->post ? get_permalink( $context->post ) : $context->site_url;
        $context->meta       = $context->post ? (array) get_post_meta( $context->post->ID, '_SAMAN_SEO_meta', true ) : [];

        // Determine schema type from post meta or post type defaults
        $type_settings = get_option( 'SAMAN_SEO_post_type_seo_settings', [] );
        $context->schema_type = $context->meta['schema_type']
            ?? $type_settings[ $context->post_type ]['schema_type']
            ?? 'Article';

        return $context;
    }
}
```

### Anti-Patterns to Avoid

- **Global state access in schemas:** Don't use `get_post()` or `get_option()` inside schema `generate()` methods. Pass all data via context object.
- **Hardcoded @id values:** Always generate @id dynamically using canonical URL + fragment. Never hardcode URLs.
- **@context in individual pieces:** Only the root JSON-LD object should have @context. Individual graph pieces should NOT include it (unlike some existing schema stubs which do).
- **Tight coupling to JsonLD service:** New schema classes should not depend on or call the existing JsonLD service directly.

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| @id generation | Custom URL concatenation | Consistent helper method with canonical URL | Fragment identifiers must be stable and predictable |
| JSON encoding | Manual escaping | `wp_json_encode()` with proper flags | Handles unicode, slashes correctly |
| Date formatting | Custom formatting | `DATE_W3C` constant | ISO 8601 compliance for schema.org |
| Post type detection | Multiple conditionals | Context object with cached value | Centralized, tested once |
| Filter application | Inline apply_filters calls | Dedicated hook points in Graph Manager | Consistent extension API |

**Key insight:** The temptation is to add @context to every schema piece for "completeness." Don't do this - the @graph structure means only one @context at the root. Individual pieces should be bare objects with @type, @id, and properties only.

## Common Pitfalls

### Pitfall 1: @context Duplication in Graph Pieces

**What goes wrong:** Each schema piece includes its own `@context: https://schema.org`, resulting in redundant and technically incorrect JSON-LD.

**Why it happens:** Developers treat each piece as standalone JSON-LD rather than part of a graph.

**How to avoid:** Only add @context at the root level in Graph_Manager. All pieces returned from `generate()` should omit @context entirely.

**Warning signs:** Existing Video_Schema, Course_Schema classes include @context in their output - this will need fixing during migration.

### Pitfall 2: Inconsistent @id Patterns

**What goes wrong:** Different schemas use different @id formats (some use `#type`, others use `#webpage`, etc.), making cross-references unreliable.

**Why it happens:** No central convention for @id generation.

**How to avoid:** Create `Schema_IDs` helper class with constants/methods:
```php
Schema_IDs::website()    // home_url() . '#website'
Schema_IDs::organization() // home_url() . '#organization'
Schema_IDs::webpage( $url )  // $url . '#webpage'
Schema_IDs::article( $url )  // $url . '#article'
```

**Warning signs:** Hardcoded string fragments in schema classes.

### Pitfall 3: Breaking Existing Filter Hooks During Migration

**What goes wrong:** New engine removes or changes `SAMAN_SEO_jsonld` and `SAMAN_SEO_jsonld_graph` filters, breaking third-party code.

**Why it happens:** Complete replacement instead of wrapper approach.

**How to avoid:** Graph_Manager should apply BOTH new hooks (`saman_seo_schema_*`) AND legacy hooks (`SAMAN_SEO_jsonld_graph`). Deprecate old hooks with notices but maintain backward compatibility for 2+ versions.

**Warning signs:** Tests that only check new filter names.

### Pitfall 4: Registering Schemas Too Early or Too Late

**What goes wrong:** Schemas registered before WordPress is ready (no `home_url()` available) or after frontend output begins.

**Why it happens:** Improper hook timing for registration.

**How to avoid:** Register built-in schemas on `init` at priority 10. Document that third-party registration should use `init` at priority 20+. Use `SAMAN_SEO_booted` action for custom registrations.

**Warning signs:** PHP warnings about undefined functions during registration.

### Pitfall 5: Not Handling Missing Post Context

**What goes wrong:** Schemas assume `$context->post` always exists, causing null errors on non-singular pages.

**Why it happens:** Testing only on single post pages.

**How to avoid:** Every schema's `is_needed()` must check for required context. Article schema needs a post; WebSite schema doesn't.

**Warning signs:** Fatal errors on archive/home pages.

## Code Examples

Verified patterns from official sources:

### Complete WebSite Schema Implementation
```php
// Source: Based on existing JsonLD service output + Yoast patterns
// https://developer.yoast.com/features/schema/pieces/website/

class WebSite_Schema extends Abstract_Schema {

    public function get_type() {
        return 'WebSite';
    }

    public function is_needed(): bool {
        // WebSite schema always needed
        return true;
    }

    public function generate(): array {
        $schema = [
            '@type'       => $this->get_type(),
            '@id'         => Schema_IDs::website(),
            'url'         => $this->context->site_url,
            'name'        => $this->context->site_name,
            'description' => get_option( 'SAMAN_SEO_default_meta_description', get_bloginfo( 'description' ) ),
            'publisher'   => [ '@id' => $this->get_publisher_id() ],
        ];

        return apply_filters( 'saman_seo_schema_website_fields', $schema, $this->context );
    }

    private function get_publisher_id(): string {
        $type = get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' );
        return 'person' === $type ? Schema_IDs::person() : Schema_IDs::organization();
    }
}
```

### Complete WebPage Schema Implementation
```php
// Source: Based on existing JsonLD service
class WebPage_Schema extends Abstract_Schema {

    public function get_type() {
        return 'WebPage';
    }

    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post;
    }

    public function generate(): array {
        $post = $this->context->post;

        $schema = [
            '@type'              => $this->get_type(),
            '@id'                => Schema_IDs::webpage( $this->context->canonical ),
            'url'                => $this->context->canonical,
            'name'               => get_the_title( $post ),
            'datePublished'      => get_the_date( DATE_W3C, $post ),
            'dateModified'       => get_the_modified_date( DATE_W3C, $post ),
            'isPartOf'           => [ '@id' => Schema_IDs::website() ],
            'breadcrumb'         => [ '@id' => Schema_IDs::breadcrumb( $this->context->canonical ) ],
            'primaryImageOfPage' => $this->get_primary_image(),
        ];

        return apply_filters( 'saman_seo_schema_webpage_fields', $schema, $this->context );
    }

    private function get_primary_image(): array {
        $image_url = get_the_post_thumbnail_url( $this->context->post, 'full' )
            ?: get_option( 'SAMAN_SEO_default_og_image' );

        return [
            '@type' => 'ImageObject',
            'url'   => $image_url,
        ];
    }
}
```

### Schema IDs Helper
```php
// Source: Yoast Schema_IDs pattern
// https://developer.yoast.com/features/schema/api/

class Schema_IDs {

    public static function website(): string {
        return home_url( '/' ) . '#website';
    }

    public static function organization(): string {
        return home_url( '/' ) . '#organization';
    }

    public static function person(): string {
        return home_url( '/' ) . '#person';
    }

    public static function webpage( string $url ): string {
        return $url . '#webpage';
    }

    public static function article( string $url ): string {
        return $url . '#article';
    }

    public static function breadcrumb( string $url ): string {
        return $url . '#breadcrumb';
    }

    public static function author( int $user_id ): string {
        return get_author_posts_url( $user_id ) . '#author';
    }
}
```

### Migration: JsonLD Service Adapter
```php
// Adapter pattern for backward compatibility
// https://refactoring.guru/design-patterns/adapter/php/example

class JsonLD_Migration_Adapter {

    /** @var Schema_Graph_Manager */
    private $graph_manager;

    /** @var JsonLD Original service */
    private $legacy_service;

    public function __construct( Schema_Graph_Manager $graph_manager, JsonLD $legacy_service ) {
        $this->graph_manager  = $graph_manager;
        $this->legacy_service = $legacy_service;
    }

    public function boot(): void {
        // Replace original filter with new engine
        remove_filter( 'SAMAN_SEO_jsonld', [ $this->legacy_service, 'build_payload' ], 10 );
        add_filter( 'SAMAN_SEO_jsonld', [ $this, 'build_payload' ], 10, 2 );
    }

    public function build_payload( array $payload, ?\WP_Post $post ): array {
        $context = Schema_Context::from_post( $post );
        return $this->graph_manager->build( $context );
    }
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Inline @context per entity | Single @context with @graph array | JSON-LD 1.1 (2019) | Required change for proper graph semantics |
| String @id references | Object with @id property | Ongoing best practice | Better entity resolution |
| Separate JSON-LD blocks | Single unified graph | Yoast 11.0 (2019) | Cleaner output, better entity connections |
| Hardcoded schemas | Registry-based extensibility | Industry standard | Third-party can extend without modifying core |

**Deprecated/outdated:**
- Multiple `<script type="application/ld+json">` blocks on same page: Combine into single graph instead
- @context inside @graph pieces: Only root should have @context
- Inline nested entities for everything: Use @id references for entities that appear multiple times

## Open Questions

Things that couldn't be fully resolved:

1. **Priority ordering within @graph**
   - What we know: Graph array order doesn't affect semantic meaning
   - What's unclear: Does Google have any preference for entity order?
   - Recommendation: Order by dependency (WebSite first, then Organization/Person, then WebPage, then content-specific schemas). Provides logical reading order.

2. **Handling multiple posts on archive pages**
   - What we know: Archive pages show multiple posts; current implementation only handles singular
   - What's unclear: Should each post get Article schema on archives? What's the main entity?
   - Recommendation: On archives, only output WebSite + Organization + CollectionPage schema. Don't output Article for each post (that happens on the post's own page).

3. **Caching JSON-LD output**
   - What we know: Schema generation involves multiple database calls
   - What's unclear: Should output be cached? How to invalidate?
   - Recommendation: Defer caching to Phase 5 or later. Not critical for correctness; can optimize later.

## Sources

### Primary (HIGH confidence)
- [W3C JSON-LD Best Practices](https://w3c.github.io/json-ld-bp/) - @context, @id, @graph patterns
- [Yoast Schema API Documentation](https://developer.yoast.com/features/schema/api/) - Abstract_Schema_Piece pattern, filter hooks
- [Yoast Schema Integration Guidelines](https://developer.yoast.com/features/schema/integration-guidelines/) - is_needed(), generate() contract
- Existing codebase: `class-saman-seo-service-jsonld.php` - Current implementation to migrate

### Secondary (MEDIUM confidence)
- [Momentic Marketing @id Guide](https://momenticmarketing.com/blog/id-schema-for-seo-llms-knowledge-graphs) - @id patterns, entity connections
- [DesignPatternsPHP Registry Pattern](https://designpatternsphp.readthedocs.io/en/latest/Structural/Registry/README.html) - Registry implementation
- [Refactoring.guru Adapter Pattern](https://refactoring.guru/design-patterns/adapter/php/example) - Migration adapter strategy

### Tertiary (LOW confidence)
- Various WebSearch results on Rank Math architecture - General feature comparison only
- Blog posts on schema best practices - Used for pattern validation, not primary source

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Based on Yoast's proven, documented architecture
- Architecture: HIGH - Patterns directly from W3C JSON-LD spec and Yoast developer docs
- Pitfalls: HIGH - Observed in existing codebase + documented migration challenges
- Migration strategy: MEDIUM - Adapter pattern is standard, but specific integration needs testing

**Research date:** 2026-01-23
**Valid until:** 2026-03-23 (60 days - schema.org and JSON-LD specs are stable)
