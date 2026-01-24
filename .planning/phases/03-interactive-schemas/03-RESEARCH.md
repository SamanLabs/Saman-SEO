# Phase 3: Interactive Schemas - Research

**Researched:** 2026-01-23
**Domain:** WordPress Gutenberg block parsing, FAQPage/HowTo JSON-LD schemas, schema engine integration
**Confidence:** HIGH

## Summary

This phase integrates the existing FAQ and HowTo Gutenberg blocks with the schema engine from Phase 1. The plugin already has fully functional `samanseo/faq` and `samanseo/howto` blocks that store structured data in block attributes and output inline JSON-LD in their `save()` functions. The goal is to:

1. Create FAQPage_Schema and HowTo_Schema classes that extend Abstract_Schema
2. Parse block content to extract FAQ/HowTo data from block attributes
3. Move schema output from individual block inline scripts to the unified @graph

**Critical discovery:** Google deprecated HowTo rich results in September 2023 and restricted FAQPage rich results to only "well-known, authoritative government and health websites." However, other search engines (Bing, etc.) may still use this markup, and keeping valid schema markup has no negative impact. The plugin should continue supporting these schemas for Bing compatibility and future Google policy changes.

**Primary recommendation:** Create FAQPage_Schema and HowTo_Schema classes that parse block attributes from post_content using WordPress's `parse_blocks()`, output proper schema structures via the registry, and remove duplicate inline schema from block save output by making schema output conditional on the `showSchema` attribute being disabled (since registry will handle it).

## Standard Stack

### Core (Existing - No New Dependencies)

| Component | Purpose | Why Standard |
|-----------|---------|--------------|
| Abstract_Schema | Base class for FAQPage/HowTo schemas | Established in Phase 1 |
| Schema_Context | Passes post content for block parsing | Already has post object |
| Schema_IDs | Generates @id values | Needs new methods for faq/howto |
| Schema_Registry | Register FAQPage/HowTo types | Supports priority ordering |

### Supporting WordPress Functions

| Function | Purpose | Notes |
|----------|---------|-------|
| `parse_blocks( $content )` | Parse post_content into block array | Returns blockName, attrs, innerBlocks |
| `has_block( 'samanseo/faq', $post )` | Check if post contains FAQ block | Faster than full parse for is_needed() |
| `has_block( 'samanseo/howto', $post )` | Check if post contains HowTo block | Faster than full parse for is_needed() |
| `wp_strip_all_tags()` | Clean HTML from block content | For question/answer text extraction |

### Block Attribute Structure (Existing)

**FAQ Block (`samanseo/faq`):**
```php
$attrs = [
    'faqs' => [
        [ 'question' => '...', 'answer' => '...' ],
        [ 'question' => '...', 'answer' => '...' ],
    ],
    'showSchema' => true,  // boolean
    'style' => 'accordion',
];
```

**HowTo Block (`samanseo/howto`):**
```php
$attrs = [
    'title' => '...',
    'description' => '...',
    'totalTime' => '30 minutes',
    'estimatedCost' => '50',
    'currency' => 'USD',
    'steps' => [
        [ 'title' => '...', 'description' => '...', 'image' => 'url' ],
    ],
    'tools' => [ 'tool1', 'tool2' ],
    'supplies' => [ 'supply1', 'supply2' ],
    'showSchema' => true,
];
```

## Architecture Patterns

### Recommended Project Structure

```
includes/Schema/Types/
  class-faqpage-schema.php     # FAQPage schema from FAQ blocks
  class-howto-schema.php       # HowTo schema from HowTo blocks
```

### Pattern 1: Block Content Parser

**What:** Abstract_Schema subclass that parses post content for specific blocks
**When to use:** For any schema that derives data from Gutenberg block content
**Example:**

```php
// Source: WordPress parse_blocks() + existing Article_Schema pattern

class FAQPage_Schema extends Abstract_Schema {

    public function get_type() {
        return 'FAQPage';
    }

    public function is_needed(): bool {
        // Must have post and contain FAQ block
        if ( ! $this->context->post instanceof \WP_Post ) {
            return false;
        }
        return has_block( 'samanseo/faq', $this->context->post );
    }

    public function generate(): array {
        $faqs = $this->get_faq_data_from_blocks();
        if ( empty( $faqs ) ) {
            return [];
        }

        return [
            '@type'      => $this->get_type(),
            '@id'        => Schema_IDs::faqpage( $this->context->canonical ),
            'mainEntity' => $this->build_questions( $faqs ),
        ];
    }

    /**
     * Extract FAQ data from all FAQ blocks in post content.
     *
     * @return array Array of [ 'question' => '...', 'answer' => '...' ]
     */
    private function get_faq_data_from_blocks(): array {
        $blocks = parse_blocks( $this->context->post->post_content );
        $all_faqs = [];

        foreach ( $blocks as $block ) {
            if ( 'samanseo/faq' === $block['blockName'] ) {
                $attrs = $block['attrs'] ?? [];
                // Only include if showSchema is true
                if ( ! empty( $attrs['showSchema'] ) && ! empty( $attrs['faqs'] ) ) {
                    foreach ( $attrs['faqs'] as $faq ) {
                        if ( ! empty( $faq['question'] ) && ! empty( $faq['answer'] ) ) {
                            $all_faqs[] = $faq;
                        }
                    }
                }
            }
        }

        return $all_faqs;
    }

    private function build_questions( array $faqs ): array {
        return array_map( function( $faq ) {
            return [
                '@type' => 'Question',
                'name'  => wp_strip_all_tags( $faq['question'] ),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $faq['answer'] ),
                ],
            ];
        }, $faqs );
    }
}
```

### Pattern 2: HowTo Schema with Nested Types

**What:** HowTo schema using HowToStep, HowToTool, HowToSupply sub-types
**When to use:** For HowTo blocks with steps, tools, and supplies
**Example:**

```php
class HowTo_Schema extends Abstract_Schema {

    public function get_type() {
        return 'HowTo';
    }

    public function is_needed(): bool {
        if ( ! $this->context->post instanceof \WP_Post ) {
            return false;
        }
        return has_block( 'samanseo/howto', $this->context->post );
    }

    public function generate(): array {
        $howto_data = $this->get_howto_data_from_blocks();
        if ( empty( $howto_data ) ) {
            return [];
        }

        // Use first HowTo block (multiple HowTo schemas would need @id differentiation)
        $data = reset( $howto_data );

        $schema = [
            '@type'       => $this->get_type(),
            '@id'         => Schema_IDs::howto( $this->context->canonical ),
            'name'        => wp_strip_all_tags( $data['title'] ?? '' ),
            'description' => wp_strip_all_tags( $data['description'] ?? '' ),
        ];

        // Add totalTime in ISO 8601 duration format
        if ( ! empty( $data['totalTime'] ) ) {
            $iso_time = $this->parse_time_to_iso( $data['totalTime'] );
            if ( $iso_time ) {
                $schema['totalTime'] = $iso_time;
            }
        }

        // Add estimatedCost as MonetaryAmount
        if ( ! empty( $data['estimatedCost'] ) ) {
            $schema['estimatedCost'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => $data['currency'] ?? 'USD',
                'value'    => $data['estimatedCost'],
            ];
        }

        // Add tools as HowToTool array
        if ( ! empty( $data['tools'] ) ) {
            $schema['tool'] = array_map( fn( $t ) => [
                '@type' => 'HowToTool',
                'name'  => $t,
            ], $data['tools'] );
        }

        // Add supplies as HowToSupply array
        if ( ! empty( $data['supplies'] ) ) {
            $schema['supply'] = array_map( fn( $s ) => [
                '@type' => 'HowToSupply',
                'name'  => $s,
            ], $data['supplies'] );
        }

        // Add steps as HowToStep array
        if ( ! empty( $data['steps'] ) ) {
            $schema['step'] = $this->build_steps( $data['steps'] );
        }

        return $schema;
    }

    private function build_steps( array $steps ): array {
        $result = [];
        $position = 1;

        foreach ( $steps as $step ) {
            if ( empty( $step['title'] ) && empty( $step['description'] ) ) {
                continue;
            }

            $step_schema = [
                '@type'    => 'HowToStep',
                'position' => $position,
                'name'     => wp_strip_all_tags( $step['title'] ?? '' ),
                'text'     => wp_strip_all_tags( $step['description'] ?? '' ),
            ];

            if ( ! empty( $step['image'] ) ) {
                $step_schema['image'] = $step['image'];
            }

            $result[] = $step_schema;
            $position++;
        }

        return $result;
    }

    /**
     * Parse human-readable time string to ISO 8601 duration.
     *
     * @param string $time_str e.g., "30 minutes", "2 hours"
     * @return string|null ISO 8601 duration e.g., "PT30M", "PT2H"
     */
    private function parse_time_to_iso( string $time_str ): ?string {
        if ( preg_match( '/(\d+)\s*(min|hour|h|m)/i', $time_str, $match ) ) {
            $num = (int) $match[1];
            $unit = strtolower( $match[2] );
            if ( 'h' === $unit || 'hour' === $unit ) {
                return "PT{$num}H";
            }
            return "PT{$num}M";
        }
        return null;
    }
}
```

### Pattern 3: Schema IDs for Interactive Types

**What:** Add faqpage() and howto() methods to Schema_IDs
**When to use:** When generating @id values for FAQ/HowTo schemas
**Example:**

```php
// In class-schema-ids.php - add these static methods

/**
 * Get FAQPage schema @id for a specific URL.
 *
 * @param string $url The canonical URL of the page.
 * @return string URL#faqpage identifier.
 */
public static function faqpage( string $url ): string {
    return $url . '#faqpage';
}

/**
 * Get HowTo schema @id for a specific URL.
 *
 * @param string $url The canonical URL of the page.
 * @return string URL#howto identifier.
 */
public static function howto( string $url ): string {
    return $url . '#howto';
}
```

### Pattern 4: Conditional Block Schema Output

**What:** Prevent duplicate schema by disabling inline block schema when registry handles it
**When to use:** Integration between block save() and schema registry
**Discussion:**

The current blocks output inline `<script type="application/ld+json">` in their `save()` function. With registry integration, this creates duplicate schema. Two approaches:

**Option A: Remove inline schema from blocks entirely**
- Modify block JS to never output inline schema
- Registry always handles schema output
- Pros: Clean separation
- Cons: Blocks don't work standalone without registry

**Option B: Respect showSchema attribute for inline, registry always adds**
- Keep inline schema output controlled by showSchema toggle
- Registry checks blocks but respects showSchema = false
- Pros: Backward compatible
- Cons: Need coordination

**Recommended: Option A** - Remove inline schema from blocks. The registry will handle all schema output. The `showSchema` attribute remains useful for users who want FAQ/HowTo visual display without any schema (e.g., if they have another schema solution).

### Anti-Patterns to Avoid

- **Parsing blocks on every request:** Use `has_block()` for is_needed() check before expensive `parse_blocks()` call
- **Ignoring showSchema attribute:** Respect user's choice to disable schema for specific blocks
- **Multiple HowTo schemas without differentiation:** If post has multiple HowTo blocks, need unique @id (e.g., `#howto-1`, `#howto-2`) or combine into one
- **Including @context in schema pieces:** Only Graph_Manager adds @context (established in Phase 1)

## Don't Hand-Roll

Problems that look simple but have edge cases:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Block detection | String search in content | `has_block( 'samanseo/faq', $post )` | WordPress handles edge cases, nested blocks |
| Block parsing | Regex extraction | `parse_blocks( $content )` | Standard parser handles all block formats |
| Time duration parsing | Complex regex | Simple pattern matching | Only need basic "30 min" / "2 hours" format |
| HTML stripping | Manual str_replace | `wp_strip_all_tags()` | Handles all HTML entity variations |

**Key insight:** The blocks already store structured data in their attributes. The schema classes are transformers, not extractors - they convert existing block data to Schema.org format.

## Common Pitfalls

### Pitfall 1: Duplicate Schema Output

**What goes wrong:** Both block inline `<script>` and registry output same schema
**Why it happens:** Block save() outputs schema + registry outputs schema
**How to avoid:** Remove inline schema from block save() entirely; let registry be sole source
**Warning signs:** Google Structured Data Test shows duplicate FAQPage/HowTo entries

### Pitfall 2: Empty Block Arrays Cause Invalid Schema

**What goes wrong:** FAQPage with empty mainEntity[] or HowTo with empty step[]
**Why it happens:** Not filtering out incomplete FAQ items or steps
**How to avoid:** Filter arrays before including; return [] from generate() if no valid content
**Warning signs:** Schema validation errors for missing required properties

### Pitfall 3: HTML in Schema Text Values

**What goes wrong:** Schema text contains `<p>`, `<strong>`, raw HTML tags
**Why it happens:** Block RichText stores HTML in attributes
**How to avoid:** Always use `wp_strip_all_tags()` when extracting text for schema
**Warning signs:** Schema validator shows HTML entities in text fields

### Pitfall 4: Multiple FAQ Blocks Not Combined

**What goes wrong:** Post has 2 FAQ blocks, only first one's questions in schema
**Why it happens:** generate() only processes first FAQ block found
**How to avoid:** Iterate all FAQ blocks and combine questions into single FAQPage mainEntity
**Warning signs:** Structured data test shows fewer questions than visible on page

### Pitfall 5: showSchema Attribute Ignored

**What goes wrong:** User disables schema in block settings but schema still outputs
**Why it happens:** Schema class doesn't check showSchema attribute from block attrs
**How to avoid:** In get_faq_data_from_blocks(), only include blocks where showSchema is true
**Warning signs:** User complaint that disabling schema doesn't work

### Pitfall 6: Performance on Posts with Many Blocks

**What goes wrong:** Slow page load due to parsing hundreds of blocks
**Why it happens:** parse_blocks() is O(n) on content length
**How to avoid:** Use has_block() first (faster), only parse if needed; consider caching parsed blocks
**Warning signs:** Slow load times on long-form content

## Code Examples

### Complete FAQPage Schema with Multi-Block Support

```php
<?php
/**
 * FAQPage Schema class.
 *
 * Generates FAQPage schema from samanseo/faq Gutenberg blocks.
 * Combines questions from all FAQ blocks in the post.
 *
 * @package Saman\SEO\Schema\Types
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

class FAQPage_Schema extends Abstract_Schema {

    public function get_type() {
        return 'FAQPage';
    }

    public function is_needed(): bool {
        return $this->context->post instanceof \WP_Post
            && has_block( 'samanseo/faq', $this->context->post );
    }

    public function generate(): array {
        $questions = $this->collect_questions_from_blocks();

        if ( empty( $questions ) ) {
            return [];
        }

        return [
            '@type'      => $this->get_type(),
            '@id'        => Schema_IDs::faqpage( $this->context->canonical ),
            'mainEntity' => $questions,
        ];
    }

    /**
     * Collect all FAQ questions from FAQ blocks in post content.
     *
     * Iterates through all samanseo/faq blocks, respects showSchema
     * attribute, and combines all valid Q&A pairs.
     *
     * @return array Array of Question objects for mainEntity.
     */
    private function collect_questions_from_blocks(): array {
        $blocks = parse_blocks( $this->context->post->post_content );
        $questions = [];

        $this->extract_faq_blocks( $blocks, $questions );

        return $questions;
    }

    /**
     * Recursively extract FAQ blocks (handles inner blocks).
     *
     * @param array $blocks    Blocks to search.
     * @param array $questions Reference to questions array to populate.
     */
    private function extract_faq_blocks( array $blocks, array &$questions ): void {
        foreach ( $blocks as $block ) {
            if ( 'samanseo/faq' === $block['blockName'] ) {
                $attrs = $block['attrs'] ?? [];

                // Respect showSchema toggle
                if ( empty( $attrs['showSchema'] ) ) {
                    continue;
                }

                $faqs = $attrs['faqs'] ?? [];
                foreach ( $faqs as $faq ) {
                    $q = trim( wp_strip_all_tags( $faq['question'] ?? '' ) );
                    $a = trim( wp_strip_all_tags( $faq['answer'] ?? '' ) );

                    if ( ! empty( $q ) && ! empty( $a ) ) {
                        $questions[] = [
                            '@type' => 'Question',
                            'name'  => $q,
                            'acceptedAnswer' => [
                                '@type' => 'Answer',
                                'text'  => $a,
                            ],
                        ];
                    }
                }
            }

            // Handle nested blocks
            if ( ! empty( $block['innerBlocks'] ) ) {
                $this->extract_faq_blocks( $block['innerBlocks'], $questions );
            }
        }
    }
}
```

### Schema Registry Registration

```php
// In the plugin's schema registration (init hook, priority 10)

$registry = Schema_Registry::instance();

// ... existing registrations ...

$registry->register(
    'faqpage',
    Types\FAQPage_Schema::class,
    [
        'label'    => __( 'FAQ Page', 'saman-seo' ),
        'priority' => 18, // After Article (15), before Breadcrumb (20)
    ]
);

$registry->register(
    'howto',
    Types\HowTo_Schema::class,
    [
        'label'    => __( 'How To', 'saman-seo' ),
        'priority' => 18, // Same priority as FAQPage
    ]
);
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| HowTo rich results in Google | HowTo deprecated by Google | Sept 2023 | No Google rich results; keep for Bing |
| FAQPage for all sites | FAQPage limited to gov/health sites | Aug 2023 | Most sites won't see FAQ rich results |
| Inline block schema | Registry-managed graph schema | This phase | Single unified JSON-LD output |
| Multiple `<script>` blocks | Single @graph with all schemas | Phase 1 pattern | Better entity connections |

**Deprecated/outdated:**
- **Google HowTo rich results:** Removed entirely in September 2023
- **Google FAQPage for regular sites:** Limited to authoritative government/health websites only
- **Inline schema in Gutenberg block save():** Should be removed in favor of registry output

**Why still implement?**
1. Bing and other search engines may still use this markup
2. Having valid schema has no negative impact
3. Google policy could change in the future
4. Provides complete schema coverage for the plugin

## Open Questions

### 1. Multiple HowTo Blocks in Same Post

**What we know:** Post could have multiple HowTo blocks (e.g., different tutorials)
**What's unclear:** Should they be separate HowTo schemas or combined?
**Recommendation:** Output only the first HowTo block's schema. Multiple HowTo schemas on one page is semantically unclear. Users wanting multiple should use separate posts.

### 2. Block Migration Strategy

**What we know:** Existing blocks output inline schema; need to remove that
**What's unclear:** What about existing saved posts with inline schema?
**Recommendation:** Modify block JS to remove inline schema output. Existing posts will need re-save to update saved HTML, but having both temporarily (inline + registry) won't cause issues - it's just redundant. Add admin notice encouraging re-save.

### 3. RichText HTML in Answers

**What we know:** Google allows some HTML in FAQ answers (links, lists, bold)
**What's unclear:** Should we preserve allowed HTML or strip all?
**Recommendation:** Strip all HTML for simplicity. The blocks use RichText which can have arbitrary formatting; safer to strip. Google will render plain text. Phase 4 or later could add intelligent HTML preservation.

### 4. Integration with Existing showSchema Toggle

**What we know:** Blocks have showSchema attribute users can toggle
**What's unclear:** If showSchema=false, should registry also skip?
**Recommendation:** YES - respect the toggle. Users may want the visual FAQ/HowTo without schema. The schema class should check showSchema attribute when extracting block data.

## Sources

### Primary (HIGH confidence)

- [Google FAQ Structured Data Docs](https://developers.google.com/search/docs/appearance/structured-data/faqpage) - FAQPage requirements, limited to gov/health sites
- [Google HowTo/FAQ Changes Blog](https://developers.google.com/search/blog/2023/08/howto-faq-changes) - Deprecation announcement
- [WordPress parse_blocks() Reference](https://developer.wordpress.org/reference/functions/parse_blocks/) - Block parsing function
- [Schema.org FAQPage](https://schema.org/FAQPage) - Full FAQPage type definition
- [Schema.org HowTo](https://schema.org/HowTo) - Full HowTo type with step, tool, supply
- Existing codebase: `blocks/faq/index.js`, `blocks/howto/index.js` - Block attribute structure

### Secondary (MEDIUM confidence)

- [Bill Erickson Block Parsing Guide](https://www.billerickson.net/access-gutenberg-block-data/) - Block parsing patterns
- Schema App FAQ/HowTo Changes article - Verified Google announcement details
- Multiple SEO industry sources confirming HowTo/FAQ deprecation

### Tertiary (LOW confidence)

- Various SEO blog posts on FAQ/HowTo best practices - Pre-deprecation content

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Using established WordPress functions and Phase 1 patterns
- Architecture: HIGH - Block parsing is well-documented WordPress pattern
- Pitfalls: HIGH - Based on actual block code analysis + Google changes
- Google changes: HIGH - Verified from official Google blog and multiple sources

**Research date:** 2026-01-23
**Valid until:** 2026-02-23 (Google schema policies can change; monitor announcements)
