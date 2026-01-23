# Phase 2: Content Schemas - Research

**Researched:** 2026-01-23
**Domain:** JSON-LD Article schema types for WordPress SEO
**Confidence:** HIGH

## Summary

This phase implements Article, BlogPosting, and NewsArticle schema types extending the schema engine foundation from Phase 1. The schema engine patterns (Abstract_Schema, Schema_Context, Schema_IDs, Schema_Registry) are already established, making this a straightforward extension.

The primary complexity is in the **author Person object** - Google recommends a full Person object with `@type`, `name`, and `url` (not just a name string), and for entities that appear multiple times in the graph, using `@id` references creates connected data. The secondary complexity is in **schema type selection** - allowing per-post override while maintaining post-type defaults.

**Primary recommendation:** Create an Article base class with BlogPosting and NewsArticle extending it, reusing the existing author Person pattern from the site-wide Person_Schema but generating author-specific `@id` values using Schema_IDs::author().

## Standard Stack

### Core (Existing - No New Dependencies)

| Component | Purpose | Why Standard |
|-----------|---------|--------------|
| Abstract_Schema | Base class for all schemas | Established in Phase 1 |
| Schema_Context | Passes post, meta, schema_type | Already has schema_type resolution |
| Schema_IDs | Generates consistent @id URLs | Has author() method ready |
| Schema_Registry | Register/instantiate schemas | Supports priority ordering |

### Supporting WordPress Functions

| Function | Purpose | Notes |
|----------|---------|-------|
| `get_the_author_meta()` | Get author display name, URL, email | Replaces deprecated `get_profile()` |
| `get_author_posts_url()` | Author archive URL | Used in Schema_IDs::author() |
| `get_avatar_url()` | Author avatar/Gravatar URL | For author image |
| `wp_strip_all_tags()` | Remove HTML from content | For articleBody excerpt |
| `str_word_count()` | Count words in string | For wordCount property |
| `wp_trim_words()` | Truncate to N words | For articleBody excerpt |

### Alternatives Considered

| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| `str_word_count()` | `preg_split()` with UTF-8 | Better i18n but more complex; `str_word_count()` works for most cases |
| Inline author Person | `@id` reference only | Full object provides better data; can include `@id` for cross-referencing |

## Architecture Patterns

### Recommended Structure

```
includes/Schema/Types/
  class-article-schema.php        # Base Article (default for posts)
  class-blogposting-schema.php    # BlogPosting (extends Article, @type change only)
  class-newsarticle-schema.php    # NewsArticle (extends Article, adds dateline/printSection)
```

### Pattern 1: Schema Type Inheritance

Article is the parent; BlogPosting and NewsArticle extend it with minimal changes.

**What:** Create Article_Schema base class, then extend for subtypes
**When to use:** When schema types share 90%+ of their properties
**Example:**

```php
// Article_Schema (base)
class Article_Schema extends Abstract_Schema {
    public function get_type() {
        return 'Article';
    }

    public function generate(): array {
        return [
            '@type'         => $this->get_type(),
            '@id'           => Schema_IDs::article( $this->context->canonical ),
            'headline'      => get_the_title( $this->context->post ),
            'datePublished' => get_the_date( DATE_W3C, $this->context->post ),
            'dateModified'  => get_the_modified_date( DATE_W3C, $this->context->post ),
            'author'        => $this->get_author(),
            'wordCount'     => $this->get_word_count(),
            'articleBody'   => $this->get_article_body_excerpt(),
            'image'         => $this->get_images(),
            'mainEntityOfPage' => [ '@id' => Schema_IDs::webpage( $this->context->canonical ) ],
            'publisher'     => $this->get_publisher(),
        ];
    }
}

// BlogPosting_Schema (minimal extension)
class BlogPosting_Schema extends Article_Schema {
    public function get_type() {
        return 'BlogPosting';
    }
    // Inherits everything else from Article_Schema
}

// NewsArticle_Schema (adds news-specific properties)
class NewsArticle_Schema extends Article_Schema {
    public function get_type() {
        return 'NewsArticle';
    }

    public function generate(): array {
        $schema = parent::generate();

        // Add NewsArticle-specific properties
        if ( $dateline = $this->get_dateline() ) {
            $schema['dateline'] = $dateline;
        }
        if ( $print_section = $this->get_print_section() ) {
            $schema['printSection'] = $print_section;
        }

        return $schema;
    }
}
```

### Pattern 2: Author as Full Person Object with @id

Google recommends full Person objects for author, and using `@id` allows cross-referencing.

**What:** Build complete Person object with @id, not just name string
**When to use:** Always for author property
**Example:**

```php
protected function get_author(): array {
    $post   = $this->context->post;
    $author_id = (int) $post->post_author;

    if ( ! $author_id ) {
        return [];
    }

    $author = [
        '@type' => 'Person',
        '@id'   => Schema_IDs::author( $author_id ),
        'name'  => get_the_author_meta( 'display_name', $author_id ),
    ];

    // Add URL (author archive or website field)
    $author_url = get_the_author_meta( 'user_url', $author_id );
    if ( empty( $author_url ) ) {
        $author_url = get_author_posts_url( $author_id );
    }
    $author['url'] = $author_url;

    // Add image (Gravatar)
    $avatar_url = get_avatar_url( $author_id, [ 'size' => 96 ] );
    if ( $avatar_url ) {
        $author['image'] = [
            '@type' => 'ImageObject',
            'url'   => $avatar_url,
        ];
    }

    return $author;
}
```

### Pattern 3: Schema Type Conditional Registration

Register only the schema type matching context.schema_type.

**What:** Register Article/BlogPosting/NewsArticle and let is_needed() filter
**When to use:** When multiple types represent the same content differently
**Example:**

```php
// In Article_Schema::is_needed()
public function is_needed(): bool {
    // Only on singular posts
    if ( ! $this->context->post instanceof \WP_Post ) {
        return false;
    }

    // Only when schema_type matches (Article is default)
    return $this->context->schema_type === 'Article';
}

// In BlogPosting_Schema::is_needed()
public function is_needed(): bool {
    if ( ! $this->context->post instanceof \WP_Post ) {
        return false;
    }
    return $this->context->schema_type === 'BlogPosting';
}
```

### Anti-Patterns to Avoid

- **Duplicating Article logic in subtypes:** Use inheritance instead of copy-paste
- **Hardcoding author as string:** `"author": "John Doe"` - always use full Person object
- **Including @context in schema pieces:** Only Graph_Manager adds @context (Phase 1 decision)
- **Building author for every request:** Consider caching author data for multi-author sites

## Don't Hand-Roll

Problems that look simple but have edge cases:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Word count | Manual counting | `str_word_count( wp_strip_all_tags( $content ) )` | Handles shortcodes, HTML consistently |
| Article excerpt | Custom truncation | `wp_trim_words( wp_strip_all_tags( $content ), 150 )` | WordPress's proven truncation |
| Author URL | Custom logic | `get_author_posts_url()` + `get_the_author_meta('user_url')` | Handles permalinks, custom URLs |
| Image multiple sizes | Manual URL manipulation | `wp_get_attachment_image_src( $id, 'full' )` | Handles all image formats, retina |
| Date formatting | `date()` | `get_the_date( DATE_W3C )` | Proper WordPress timezone handling |

**Key insight:** WordPress already handles author metadata, word counts, and date formatting correctly. The schema classes should compose these functions, not replace them.

## Common Pitfalls

### Pitfall 1: Author as Name String Instead of Person Object

**What goes wrong:** Schema outputs `"author": "John Doe"` instead of full Person object
**Why it happens:** Copying older examples or simplifying implementation
**How to avoid:** Always build Person object with `@type`, `name`, `url`, and optionally `@id`
**Warning signs:** Google Structured Data Testing Tool warns about author format

### Pitfall 2: wordCount Includes HTML/Shortcodes

**What goes wrong:** Word count is inflated by counting HTML tags and shortcode syntax
**Why it happens:** Counting raw `$post->post_content` without stripping
**How to avoid:** Use `str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) )`
**Warning signs:** wordCount is much higher than expected for post length

### Pitfall 3: Missing Publisher Reference

**What goes wrong:** Article has no publisher, Google may not recognize site authority
**Why it happens:** Forgetting publisher is an important Article property
**How to avoid:** Reference existing Organization/Person via @id
**Warning signs:** Structured data test shows publisher warning

### Pitfall 4: Multiple Article Types Outputting for Same Post

**What goes wrong:** Graph contains both Article AND BlogPosting for same content
**Why it happens:** is_needed() checks don't properly gate on schema_type
**How to avoid:** Each schema type's is_needed() MUST check `$this->context->schema_type`
**Warning signs:** Duplicate @id values in graph, redundant schemas

### Pitfall 5: dateline Format for NewsArticle

**What goes wrong:** dateline formatted incorrectly or missing expected format
**Why it happens:** Unclear what dateline should contain
**How to avoid:** dateline is text like "WASHINGTON, Jan 23" - location and date
**Warning signs:** NewsArticle missing expected news context

### Pitfall 6: Schema Type Selection Not Respecting Priority

**What goes wrong:** Post-level override doesn't work, always uses default
**Why it happens:** Reading post type settings instead of post meta
**How to avoid:** Schema_Context already implements correct priority (post meta > post type > default)
**Warning signs:** Selecting BlogPosting in UI but getting Article output

## Code Examples

### Complete Article Schema Generation

```php
// Source: Combining Google docs + existing codebase patterns
public function generate(): array {
    $post = $this->context->post;

    $schema = [
        '@type'         => $this->get_type(),
        '@id'           => Schema_IDs::article( $this->context->canonical ),
        'headline'      => get_the_title( $post ),
        'datePublished' => get_the_date( DATE_W3C, $post ),
        'dateModified'  => get_the_modified_date( DATE_W3C, $post ),
        'mainEntityOfPage' => [
            '@id' => Schema_IDs::webpage( $this->context->canonical ),
        ],
    ];

    // Author (full Person object)
    $author = $this->get_author();
    if ( ! empty( $author ) ) {
        $schema['author'] = $author;
    }

    // Publisher (reference to Organization or Person)
    $schema['publisher'] = $this->get_publisher_reference();

    // Word count
    $word_count = $this->get_word_count();
    if ( $word_count > 0 ) {
        $schema['wordCount'] = $word_count;
    }

    // Article body excerpt (first ~150 words)
    $article_body = $this->get_article_body_excerpt();
    if ( ! empty( $article_body ) ) {
        $schema['articleBody'] = $article_body;
    }

    // Images (array of URLs for multiple aspect ratios)
    $images = $this->get_images();
    if ( ! empty( $images ) ) {
        $schema['image'] = $images;
    }

    return $schema;
}
```

### Word Count Calculation

```php
// Source: WordPress core functions
protected function get_word_count(): int {
    $content = $this->context->post->post_content;

    // Remove shortcodes and HTML
    $content = strip_shortcodes( $content );
    $content = wp_strip_all_tags( $content );

    // Count words
    return str_word_count( $content );
}
```

### Article Body Excerpt

```php
// Source: WordPress core wp_trim_words
protected function get_article_body_excerpt(): string {
    $content = $this->context->post->post_content;

    // Process shortcodes first
    $content = do_shortcode( $content );

    // Strip HTML
    $content = wp_strip_all_tags( $content );

    // Trim to reasonable length (150 words)
    return wp_trim_words( $content, 150, '...' );
}
```

### Publisher Reference

```php
// Source: Existing Organization_Schema/Person_Schema patterns
protected function get_publisher_reference(): array {
    $knowledge_type = get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' );

    if ( 'person' === $knowledge_type ) {
        return [ '@id' => Schema_IDs::person() ];
    }

    return [ '@id' => Schema_IDs::organization() ];
}
```

### NewsArticle dateline

```php
// Source: Schema.org NewsArticle spec
protected function get_dateline(): string {
    // dateline is typically "CITY, Date" format
    // Could come from post meta or be generated
    $dateline = $this->context->meta['dateline'] ?? '';

    if ( empty( $dateline ) ) {
        // Auto-generate from Local SEO city if available
        $city = get_option( 'SAMAN_SEO_local_city', '' );
        if ( ! empty( $city ) ) {
            $dateline = strtoupper( $city ) . ', ' . get_the_date( 'M j', $this->context->post );
        }
    }

    return $dateline;
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| `"author": "Name"` | Full Person object with @id, name, url | Google 2024 guidance | Better author disambiguation |
| Single image URL | Array of images (16:9, 4:3, 1:1) | Google 2024 docs | Better rich results eligibility |
| publisher inline | publisher as @id reference | JSON-LD best practices | Cleaner graph, entity reuse |

**Deprecated/outdated:**
- **Inline publisher objects:** Use @id references to site-wide Organization/Person instead
- **author as string:** Google explicitly recommends Person objects with multiple properties

## Open Questions

### 1. NewsArticle Meta Fields UI

**What we know:** dateline and printSection are NewsArticle-specific properties
**What's unclear:** Where/how users input these values (post meta sidebar?)
**Recommendation:** Store in `_SAMAN_SEO_meta['dateline']` and `_SAMAN_SEO_meta['print_section']`. UI can be added in later Admin UI phase; for now, auto-generate dateline from Local SEO city.

### 2. Multi-Author Posts

**What we know:** WordPress has single `post_author`, some plugins add co-authors
**What's unclear:** Should we support array of authors?
**Recommendation:** Start with single author from `post_author`. Filter hook allows extending to multi-author. Google examples show array of authors is valid.

### 3. Image Aspect Ratios

**What we know:** Google recommends 16:9, 4:3, and 1:1 aspect ratio images
**What's unclear:** Should we generate all three from featured image, or expect them pre-created?
**Recommendation:** Use featured image URL only (WordPress doesn't auto-crop to aspect ratios). Note in docs that multiple image sizes improve rich results.

## Sources

### Primary (HIGH confidence)

- [Google Article Structured Data](https://developers.google.com/search/docs/appearance/structured-data/article) - Official requirements
- [Schema.org Article](https://schema.org/Article) - Property definitions
- [Schema.org NewsArticle](https://schema.org/NewsArticle) - dateline, printSection properties
- [Schema.org BlogPosting](https://schema.org/BlogPosting) - Inheritance from Article
- Existing codebase: `includes/Schema/` - Established patterns

### Secondary (MEDIUM confidence)

- [WordPress get_the_author_meta()](https://developer.wordpress.org/reference/functions/get_the_author_meta/) - Author data retrieval
- [WordPress get_userdata()](https://developer.wordpress.org/reference/functions/get_userdata/) - User profile access
- [JSON-LD Best Practices](https://w3c.github.io/json-ld-bp/) - @id usage patterns
- [Using @id in Schema.org](https://momenticmarketing.com/blog/id-schema-for-seo-llms-knowledge-graphs) - Entity reference patterns

### Tertiary (LOW confidence)

- jsonld_examples/article.md - Local examples (may be outdated)
- jsonld_examples/blog-post.md - Local examples (may be outdated)

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Using established WordPress functions and Phase 1 patterns
- Architecture: HIGH - Inheritance pattern is straightforward, matches existing codebase
- Pitfalls: HIGH - Based on Google's explicit guidelines and common schema issues

**Research date:** 2026-01-23
**Valid until:** 2026-02-23 (Google's schema docs are stable)
