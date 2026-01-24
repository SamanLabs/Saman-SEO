# Phase 10: Reviews & Ratings - Research

**Researched:** 2026-01-24
**Domain:** WooCommerce Product Reviews, AggregateRating Schema, Review Schema
**Confidence:** HIGH

## Summary

This phase extends the Product_Schema class to include AggregateRating and Review schema from WooCommerce product reviews. WooCommerce stores product reviews as WordPress comments with a `review` comment type and stores ratings in comment meta (`rating`). The implementation uses `get_comments()` to retrieve approved reviews and WC_Product methods for aggregate data.

WooCommerce provides dedicated methods for aggregate review data: `$product->get_average_rating()` returns the average rating as a float, `$product->get_review_count()` returns the total number of reviews. Individual reviews are retrieved via `get_comments(['post_id' => $product_id, 'status' => 'approve', 'type' => 'review'])`. Each WP_Comment object provides `comment_author`, `comment_content`, `comment_date_gmt`, and the rating is in comment meta `rating`.

Google requires that AggregateRating includes `ratingValue` and at least one of `ratingCount` or `reviewCount`. For individual Review objects, Google requires `author` (with valid name), `reviewRating` with `ratingValue`. The `reviewBody` and `datePublished` are recommended. Critical: products with zero reviews must NOT output AggregateRating (would show empty/invalid values).

**Primary recommendation:** Add `add_aggregate_rating()` method that checks `$product->get_review_count() > 0` before output, and `add_reviews()` method that retrieves individual reviews via `get_comments()` with proper filtering. Both methods follow the existing conditional property addition pattern.

## Standard Stack

The established approach for this phase:

### Core (Already Available)
| Component | Version | Purpose | Why Standard |
|-----------|---------|---------|--------------|
| Product_Schema | 1.1.0 | Extends with reviews | Existing product implementation |
| WC_Product API | 9.x | Aggregate review data | get_average_rating(), get_review_count() |
| WordPress Comments | 6.x | Individual reviews | get_comments() with type='review' |
| Schema_IDs | 1.0.0 | @id generation | Consistent ID format |

### WooCommerce Review Methods (Phase 10 Uses)
| Method | Return | Schema Property | Notes |
|--------|--------|-----------------|-------|
| `get_average_rating()` | float | aggregateRating.ratingValue | 0-5 scale average |
| `get_review_count()` | int | aggregateRating.reviewCount | Total approved reviews |
| `get_reviews_allowed()` | bool | N/A | Check if reviews enabled |

### WordPress Comment Properties (Phase 10 Uses)
| Property | Type | Schema Property | Notes |
|----------|------|-----------------|-------|
| `comment_author` | string | review.author.name | Reviewer name |
| `comment_content` | string | review.reviewBody | Review text |
| `comment_date_gmt` | string | review.datePublished | ISO 8601 date |
| `comment_ID` | int | review.@id | Unique identifier |

### Comment Meta (Phase 10 Uses)
| Meta Key | Type | Schema Property | Notes |
|----------|------|-----------------|-------|
| `rating` | int (1-5) | review.reviewRating.ratingValue | Star rating |
| `verified` | int (0/1) | N/A | Optional - not used in schema |

## Architecture Patterns

### Recommended generate() Update
```php
// Existing properties...
$this->add_condition( $schema, $product );

// NEW: Reviews and ratings - Phase 10
$this->add_aggregate_rating( $schema, $product );
$this->add_reviews( $schema, $product );

// Offers (existing)...
```

### Pattern 1: AggregateRating Building
**What:** Build AggregateRating only when product has reviews
**When to use:** Products with review_count > 0
**Example:**
```php
// Source: Google Merchant Listing docs + WooCommerce API
protected function add_aggregate_rating( array &$schema, \WC_Product $product ): void {
    $review_count = $product->get_review_count();

    // CRITICAL: Do NOT output AggregateRating for products with zero reviews.
    if ( $review_count < 1 ) {
        return;
    }

    $average_rating = $product->get_average_rating();

    // Validate rating is valid (not zero/empty).
    if ( empty( $average_rating ) || (float) $average_rating <= 0 ) {
        return;
    }

    $schema['aggregateRating'] = [
        '@type'       => 'AggregateRating',
        'ratingValue' => (float) $average_rating,
        'reviewCount' => (int) $review_count,
        'bestRating'  => 5,
        'worstRating' => 1,
    ];
}
```

### Pattern 2: Individual Reviews Array
**What:** Build array of Review objects from WooCommerce reviews
**When to use:** Products with reviews (same condition as AggregateRating)
**Example:**
```php
// Source: Google Review Snippet docs + WordPress get_comments()
protected function add_reviews( array &$schema, \WC_Product $product ): void {
    $review_count = $product->get_review_count();

    // Skip if no reviews.
    if ( $review_count < 1 ) {
        return;
    }

    $comments = get_comments( [
        'post_id' => $product->get_id(),
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => 10, // Limit for performance
    ] );

    if ( empty( $comments ) ) {
        return;
    }

    $reviews = [];
    foreach ( $comments as $comment ) {
        $review = $this->build_review( $comment );
        if ( ! empty( $review ) ) {
            $reviews[] = $review;
        }
    }

    if ( ! empty( $reviews ) ) {
        $schema['review'] = $reviews;
    }
}
```

### Pattern 3: Single Review Building
**What:** Build individual Review schema from WP_Comment
**When to use:** Each comment in the reviews loop
**Example:**
```php
// Source: Google Review requirements + WooCommerce comment meta
protected function build_review( \WP_Comment $comment ): array {
    // Author name is required by Google.
    $author_name = $comment->comment_author;
    if ( empty( $author_name ) ) {
        return [];
    }

    // Rating from comment meta.
    $rating = get_comment_meta( $comment->comment_ID, 'rating', true );
    if ( empty( $rating ) || (int) $rating < 1 || (int) $rating > 5 ) {
        return [];
    }

    $review = [
        '@type'        => 'Review',
        'author'       => [
            '@type' => 'Person',
            'name'  => $author_name,
        ],
        'reviewRating' => [
            '@type'       => 'Rating',
            'ratingValue' => (int) $rating,
            'bestRating'  => 5,
            'worstRating' => 1,
        ],
        'datePublished' => gmdate( 'Y-m-d', strtotime( $comment->comment_date_gmt ) ),
    ];

    // Optional: reviewBody (the actual review text).
    $review_body = $comment->comment_content;
    if ( ! empty( $review_body ) ) {
        $review['reviewBody'] = wp_strip_all_tags( $review_body );
    }

    return $review;
}
```

### Pattern 4: Review Count Check Gate
**What:** Check review count before any review-related processing
**When to use:** Entry point for both aggregateRating and reviews
**Example:**
```php
// CRITICAL: This check prevents outputting empty/invalid schema.
// Do NOT output AggregateRating with ratingValue=0 or reviewCount=0.
$review_count = $product->get_review_count();
if ( $review_count < 1 ) {
    return; // Skip all review-related schema
}
```

### Anti-Patterns to Avoid
- **Outputting AggregateRating with zero reviews:** Google will flag this as invalid
- **Missing author name:** Google requires valid person/team name, not promotional text
- **Unlimited reviews in schema:** Limit to reasonable number (10-20) for performance
- **Including unapproved reviews:** Only use `status => 'approve'`
- **Using comment_date instead of comment_date_gmt:** GMT is correct for ISO 8601
- **Integer rating as string:** Cast to (int) for proper schema validation

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Average rating | Manual calculation | `$product->get_average_rating()` | Cached, handles edge cases |
| Review count | `count(get_comments(...))` | `$product->get_review_count()` | Already calculated, cached |
| Approved only | Manual status filter | `'status' => 'approve'` in get_comments | WordPress handles this |
| Rating retrieval | Direct meta query | `get_comment_meta($id, 'rating', true)` | Standard WC pattern |
| Date formatting | String manipulation | `gmdate('Y-m-d', strtotime($gmt))` | Proper ISO 8601 |

**Key insight:** WooCommerce caches average rating and review count on the product. Don't recalculate these values.

## Common Pitfalls

### Pitfall 1: AggregateRating with Zero Reviews
**What goes wrong:** Schema outputs `ratingValue: 0` or `reviewCount: 0`
**Why it happens:** Not checking review count before output
**How to avoid:** Always check `$product->get_review_count() > 0` first
**Warning signs:** Rich Results Test shows "Invalid aggregate rating"

### Pitfall 2: Invalid Author Name
**What goes wrong:** Google rejects review due to invalid author
**Why it happens:** Author is promotional text like "50% off!" or empty
**How to avoid:** Validate author name exists and is reasonable
**Warning signs:** Review snippet doesn't appear in search results

### Pitfall 3: Missing Rating on Individual Review
**What goes wrong:** Review has no rating, schema incomplete
**Why it happens:** Some comments may not have rating meta (old comments, imports)
**How to avoid:** Skip reviews without valid rating (1-5)
**Warning signs:** Schema validation errors for individual reviews

### Pitfall 4: Performance with Many Reviews
**What goes wrong:** Page load slow, memory issues
**Why it happens:** Outputting hundreds of reviews in schema
**How to avoid:** Limit reviews with `'number' => 10` in get_comments
**Warning signs:** Slow page loads on products with 100+ reviews

### Pitfall 5: HTML in reviewBody
**What goes wrong:** Schema contains HTML tags
**Why it happens:** Comment content may contain formatting
**How to avoid:** Always `wp_strip_all_tags()` the review body
**Warning signs:** JSON-LD contains `<p>`, `<br>` tags

### Pitfall 6: Wrong Date Format
**What goes wrong:** datePublished not recognized
**Why it happens:** Using local date or wrong format
**How to avoid:** Use `comment_date_gmt` and format as 'Y-m-d'
**Warning signs:** Date parsing errors in validators

### Pitfall 7: Including Ratings Without Reviews
**What goes wrong:** Product has ratings but comments disabled, reviews hidden
**Why it happens:** Assuming get_review_count() always means visible reviews exist
**How to avoid:** Verify actual comments exist with get_comments before outputting
**Warning signs:** aggregateRating present but no reviews array

## Code Examples

Verified patterns from official sources:

### Complete AggregateRating Implementation
```php
// Source: Google Merchant Listing + WooCommerce API
/**
 * Add AggregateRating to schema when product has reviews.
 *
 * @param array       $schema  Schema array by reference.
 * @param \WC_Product $product WooCommerce product.
 */
protected function add_aggregate_rating( array &$schema, \WC_Product $product ): void {
    $review_count = $product->get_review_count();

    // CRITICAL: Do NOT output for products with zero reviews (REVW-04).
    if ( $review_count < 1 ) {
        return;
    }

    $average_rating = $product->get_average_rating();

    // Validate rating is valid.
    if ( empty( $average_rating ) || (float) $average_rating <= 0 ) {
        return;
    }

    $schema['aggregateRating'] = [
        '@type'       => 'AggregateRating',
        'ratingValue' => round( (float) $average_rating, 1 ),
        'reviewCount' => (int) $review_count,
        'bestRating'  => 5,
        'worstRating' => 1,
    ];
}
```

### Complete Reviews Array Implementation
```php
// Source: Google Review Snippet + WordPress Comments API
/**
 * Add individual reviews to schema.
 *
 * @param array       $schema  Schema array by reference.
 * @param \WC_Product $product WooCommerce product.
 */
protected function add_reviews( array &$schema, \WC_Product $product ): void {
    // Skip if no reviews (same check as aggregateRating).
    if ( $product->get_review_count() < 1 ) {
        return;
    }

    $comments = get_comments( [
        'post_id' => $product->get_id(),
        'status'  => 'approve',
        'type'    => 'review',
        'number'  => 10, // Limit for performance.
        'orderby' => 'comment_date_gmt',
        'order'   => 'DESC',
    ] );

    if ( empty( $comments ) ) {
        return;
    }

    $reviews = [];
    foreach ( $comments as $comment ) {
        $review = $this->build_review( $comment );
        if ( ! empty( $review ) ) {
            $reviews[] = $review;
        }
    }

    if ( ! empty( $reviews ) ) {
        $schema['review'] = $reviews;
    }
}
```

### Complete build_review Implementation
```php
// Source: Google Review requirements + schema.org Review
/**
 * Build individual Review schema from WP_Comment.
 *
 * @param \WP_Comment $comment WordPress comment object.
 * @return array Review schema array, empty if invalid.
 */
protected function build_review( \WP_Comment $comment ): array {
    // Author name is required by Google (REVW-06).
    $author_name = trim( $comment->comment_author );
    if ( empty( $author_name ) ) {
        return [];
    }

    // Rating from comment meta (REVW-06).
    $rating = (int) get_comment_meta( $comment->comment_ID, 'rating', true );
    if ( $rating < 1 || $rating > 5 ) {
        return [];
    }

    $review = [
        '@type'        => 'Review',
        'author'       => [
            '@type' => 'Person',
            'name'  => $author_name,
        ],
        'reviewRating' => [
            '@type'       => 'Rating',
            'ratingValue' => $rating,
            'bestRating'  => 5,
            'worstRating' => 1,
        ],
        'datePublished' => gmdate( 'Y-m-d', strtotime( $comment->comment_date_gmt ) ),
    ];

    // Optional: reviewBody (REVW-06).
    $review_body = trim( $comment->comment_content );
    if ( ! empty( $review_body ) ) {
        $review['reviewBody'] = wp_strip_all_tags( $review_body );
    }

    return $review;
}
```

### get_comments Arguments Reference
```php
// Source: WordPress Developer Reference
$args = [
    'post_id' => $product->get_id(), // Specific product
    'status'  => 'approve',          // Only approved (comment_approved = 1)
    'type'    => 'review',           // WooCommerce review type
    'number'  => 10,                 // Limit for performance
    'orderby' => 'comment_date_gmt', // Sort by date
    'order'   => 'DESC',             // Newest first
    'parent'  => 0,                  // Top-level only (no replies)
];
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| Rating without bestRating | Include bestRating/worstRating | Google update 2023 | Clearer scale definition |
| Author as string | Author as Person object | Schema.org best practice | Richer entity data |
| No review limit | Limit to 10-20 reviews | Performance best practice | Faster page loads |
| HTML in reviewBody | Strip all HTML | Always required | Valid JSON-LD |

**Deprecated/outdated:**
- `comment_author_email` in schema: Don't expose emails
- Unlimited reviews: Always limit for performance
- `comment_type = 'comment'`: WooCommerce uses 'review' type

## Open Questions

Things that couldn't be fully resolved:

1. **Review Limit Count**
   - What we know: Google doesn't specify max, but performance matters
   - What's unclear: Optimal number of reviews to include (10? 20? 50?)
   - Recommendation: Start with 10, make filterable via hook for customization

2. **Reviews Without Text**
   - What we know: Some reviews may be rating-only (no reviewBody)
   - What's unclear: Should these be included in schema?
   - Recommendation: Include them - rating is required, reviewBody is recommended

3. **Organization vs Person Author**
   - What we know: Google accepts Person or Organization for author
   - What's unclear: When to use Organization (team reviews?)
   - Recommendation: Use Person for all WooCommerce reviews (standard case)

4. **Verified Owner Badge**
   - What we know: WooCommerce tracks verified purchases
   - What's unclear: Is there schema property for this?
   - Recommendation: No standard property exists - skip for v1.1

## Sources

### Primary (HIGH confidence)
- Google Review Snippet: https://developers.google.com/search/docs/appearance/structured-data/review-snippet
  - Required: author (with name), reviewRating.ratingValue
  - Recommended: reviewBody, datePublished, bestRating
- Google Merchant Listing: https://developers.google.com/search/docs/appearance/structured-data/merchant-listing
  - AggregateRating requires ratingValue, reviewCount (or ratingCount)
- WooCommerce Code Reference: https://woocommerce.github.io/code-reference/classes/WC-Product.html
  - get_average_rating() returns float
  - get_review_count() returns int
- WordPress Developer Reference: https://developer.wordpress.org/reference/functions/get_comments/
  - Comment query parameters and WP_Comment properties
- Schema.org AggregateRating: https://schema.org/AggregateRating
  - Properties: ratingValue, reviewCount, ratingCount, bestRating, worstRating
- Schema.org Review: https://schema.org/Review
  - Properties: author, reviewRating, reviewBody, datePublished

### Secondary (MEDIUM confidence)
- Yoast Product Schema: https://developer.yoast.com/features/schema/pieces/product/
  - Confirms reviews as references in schema graph
- WooCommerce WC_Comments: https://woocommerce.github.io/code-reference/classes/WC-Comments.html
  - Review comment handling methods

### Tertiary (LOW confidence)
- None - all claims verified against official sources

## Metadata

**Confidence breakdown:**
- WC_Product review methods: HIGH - Official WooCommerce documentation
- AggregateRating properties: HIGH - Google and schema.org official docs
- Review properties: HIGH - Google Review Snippet documentation
- WordPress get_comments: HIGH - Official WordPress documentation
- Comment meta 'rating': HIGH - Verified in WooCommerce patterns
- Implementation patterns: HIGH - Follows existing Phase 8/9 codebase patterns

**Research date:** 2026-01-24
**Valid until:** 2026-03-24 (60 days - stable APIs)
