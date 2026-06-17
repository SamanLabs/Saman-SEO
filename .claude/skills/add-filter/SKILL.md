---
name: add-filter
description: Add a new WordPress filter hook to the Saman SEO plugin with proper documentation. Use when making functionality extensible, allowing third-party customization, or adding developer hooks.
argument-hint: [filter-name] [description]
---

# Add Filter Hook

Add a new filter hook to the Saman SEO plugin with proper documentation.

## Arguments
- `$ARGUMENTS` should contain: filter name (e.g., "title_separator") and description

## Steps

1. **Analyze where the filter should be added**:
   - Identify the file and function where the value is computed
   - Determine what data should be filterable
   - Consider what additional context to pass

2. **Add the filter** using `saman_seo_apply_filters()` so legacy aliases work:

```php
/**
 * Filter the {description}.
 *
 * @since {version}
 *
 * @param {type} ${variable} The {description}.
 * @param {additional_params} Additional context parameters.
 */
$variable = saman_seo_apply_filters( 'saman_seo_{filter_name}', $variable, $additional_context );
```

3. **Document the filter** in `FILTERS.md`:

```markdown
### `saman_seo_{filter_name}`

{Description of what this filter does.}

**Parameters:**
- `${variable}` ({type}) - The {description}.
- `$context` (array) - Additional context.

**Example:**
```php
add_filter( 'saman_seo_{filter_name}', function( $value, $context ) {
    // Modify $value
    return $value;
}, 10, 2 );
```

**Since:** {version}
```

## Naming Conventions

- **Prefix**: Always use `saman_seo_` prefix
- **Style**: Use snake_case for filter names
- **Clarity**: Name should describe what's being filtered

## Common Filter Patterns

### Value Filter
```php
$title = apply_filters( 'saman_seo_meta_title', $title, $post_id );
```

### Array Filter
```php
$schema = apply_filters( 'saman_seo_schema_data', $schema, $post, $context );
```

### Boolean Filter
```php
$enabled = apply_filters( 'saman_seo_feature_enabled', $enabled, $feature_name );
```

### Output Filter
```php
$html = apply_filters( 'saman_seo_breadcrumb_html', $html, $breadcrumbs );
```

## Existing Filter Categories

Reference existing filters in `FILTERS.md`:

1. **Title Filters**: `saman_seo_title`, `saman_seo_title_separator`
2. **Meta Filters**: `saman_seo_meta_description`, `saman_seo_canonical_url`
3. **Schema Filters**: `saman_seo_schema_*`, `saman_seo_jsonld_output`
4. **Sitemap Filters**: `saman_seo_sitemap_*`
5. **Feature Toggles**: `saman_seo_feature_toggle`
6. **Admin Filters**: `saman_seo_admin_*`

## Best Practices

1. **Pass sufficient context** - Include related objects (post, term, etc.)
2. **Document the return type** - Be explicit about expected return values
3. **Use appropriate hook priority** - Default is 10, lower = earlier
4. **Consider backwards compatibility** - Don't break existing filter signatures
5. **Add version since tag** - Track when the filter was introduced

## Example Usage

```
/add-filter og_image_size Customize the Open Graph image dimensions
```

This will:
1. Find where OG images are processed
2. Add the filter with proper docblock
3. Update FILTERS.md with documentation
