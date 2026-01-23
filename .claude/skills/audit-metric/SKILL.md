---
name: audit-metric
description: Add a new SEO audit metric to the scoring system. Use when extending the SEO analysis, adding new quality checks, or improving the audit dashboard.
argument-hint: [metric-name] [points] [category]
---

# Add SEO Audit Metric

Add a new metric to the Saman SEO audit scoring system.

## Arguments
- `$ARGUMENTS` should contain: metric name, points value, and category

## Categories (with current point totals)

1. **Basic SEO** (40 points) - Title, description, H1, content length
2. **Keyword Optimization** (30 points) - Keyphrase placement, density
3. **Content Structure** (15 points) - Heading hierarchy
4. **Links & Media** (15 points) - Internal/external links, image alts

## Steps

1. **Define the metric**:
   - Name and description
   - Point value (consider category balance)
   - Pass/fail criteria
   - Category assignment

2. **Add to `calculate_seo_score()` in `includes/helpers.php`**:

```php
// {Metric Name} Check ({points} points)
${metric_key}_check = {your_check_logic};
if ( ${metric_key}_check ) {
    $score += {points};
    $passed_checks[] = '{metric_key}';
} else {
    $failed_checks[] = array(
        'key'         => '{metric_key}',
        'message'     => __( '{Failure message explaining the issue}', 'saman-seo' ),
        'suggestion'  => __( '{Actionable suggestion to fix it}', 'saman-seo' ),
        'points_lost' => {points},
    );
}
```

3. **Update the scoring documentation** in the function's docblock

4. **Add to React Audit UI** (`src-v2/pages/Audit.js`):
   - Add metric to the appropriate category section
   - Include icon and description
   - Handle pass/fail display states

## Metric Check Examples

### Content Length Check
```php
$content_length = str_word_count( wp_strip_all_tags( $content ) );
$has_sufficient_content = $content_length >= 300;
```

### Keyphrase in Title Check
```php
$keyphrase_in_title = ! empty( $focus_keyphrase ) &&
    stripos( $title, $focus_keyphrase ) !== false;
```

### Image Alt Text Check
```php
preg_match_all( '/<img[^>]+>/i', $content, $images );
$images_without_alt = 0;
foreach ( $images[0] as $img ) {
    if ( ! preg_match( '/alt=["\'][^"\']+["\']/', $img ) ) {
        $images_without_alt++;
    }
}
$all_images_have_alt = $images_without_alt === 0;
```

### Internal Links Check
```php
$internal_links = \Saman\SEO\Helpers\count_internal_links( $content );
$has_internal_links = $internal_links >= 2;
```

## Scoring Guidelines

- **Total should equal 100** when all categories are summed
- **Balance impact** - High-impact SEO factors get more points
- **Be actionable** - Every failed check should have a clear fix
- **Avoid false positives** - Don't penalize valid content patterns

## Filter Hook

The score is filterable for third-party customization:

```php
/**
 * Filter the SEO score calculation.
 *
 * @param array $score_data The score data including score, passed, failed checks.
 * @param int   $post_id    The post ID.
 * @param array $meta       The post SEO meta.
 */
$score_data = apply_filters( 'samanseo_seo_score', $score_data, $post_id, $meta );
```

## Example Usage

```
/audit-metric readability-score 10 content-structure Check content readability using Flesch-Kincaid score
```

This will:
1. Add the metric calculation to helpers.php
2. Update point totals for the category
3. Add UI display in the Audit page
