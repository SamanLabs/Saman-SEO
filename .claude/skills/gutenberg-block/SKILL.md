---
name: gutenberg-block
description: Create a new Gutenberg block for the Saman SEO plugin. Use when adding editor blocks for schema markup, SEO elements, or content enhancement tools.
argument-hint: [block-name] [description]
---

# Create Gutenberg Block

Generate a new Gutenberg block for the Saman SEO plugin.

## Arguments
- `$ARGUMENTS` should contain: block name (e.g., "review") and description

## Steps

1. **Create block directory** at `blocks/{block-name}/`

2. **Create block.json** (block metadata):

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "saman-seo/{block-name}",
    "version": "1.0.0",
    "title": "{Block Title}",
    "category": "saman-seo",
    "icon": "{dashicon-name}",
    "description": "{Block description}",
    "keywords": ["{keyword1}", "{keyword2}", "seo"],
    "supports": {
        "html": false,
        "align": ["wide", "full"]
    },
    "textdomain": "saman-seo",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css",
    "render": "file:./render.php",
    "attributes": {
        "exampleAttribute": {
            "type": "string",
            "default": ""
        }
    }
}
```

3. **Create index.js** (editor script):

```jsx
/**
 * {Block Name} Block
 *
 * @package Saman SEO
 */

import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

import metadata from './block.json';
import './editor.scss';

/**
 * Edit component for the block.
 */
const Edit = ({ attributes, setAttributes }) => {
    const blockProps = useBlockProps();
    const { exampleAttribute } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', 'saman-seo')}>
                    <TextControl
                        label={__('Example Setting', 'saman-seo')}
                        value={exampleAttribute}
                        onChange={(value) => setAttributes({ exampleAttribute: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                {/* Block editor preview */}
                <div className="saman-seo-{block-name}">
                    {/* Content */}
                </div>
            </div>
        </>
    );
};

/**
 * Save component - returns null for dynamic blocks.
 */
const Save = () => null;

/**
 * Register the block.
 */
registerBlockType(metadata.name, {
    edit: Edit,
    save: Save,
});
```

4. **Create render.php** (server-side render):

```php
<?php
/**
 * Server-side rendering of the {Block Name} block.
 *
 * @package Saman\SEO
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Block content.
 * @var WP_Block $block      Block instance.
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'saman-seo-{block-name}',
) );

// Generate schema if applicable
$schema = array(
    '@context' => 'https://schema.org',
    '@type'    => '{SchemaType}',
    // Add schema properties
);

?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
    <!-- Block HTML output -->
</div>

<?php if ( ! empty( $schema ) ) : ?>
<script type="application/ld+json">
<?php echo wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ); ?>
</script>
<?php endif; ?>
```

5. **Create editor.scss** (editor styles):

```scss
/**
 * Editor styles for {Block Name} block.
 */
.wp-block-saman-seo-{block-name} {
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    background: #f9f9f9;
}
```

6. **Create style.scss** (frontend styles):

```scss
/**
 * Frontend styles for {Block Name} block.
 */
.saman-seo-{block-name} {
    margin: 1em 0;
    padding: 1em;
}
```

7. **Register the block** in PHP (if not using block.json auto-registration):

Add to `includes/class-saman-seo-plugin.php` or create a dedicated service:

```php
add_action( 'init', function() {
    register_block_type( SAMAN_SEO_PATH . 'blocks/{block-name}' );
} );
```

8. **Update build configuration** in `package.json` if needed

## Existing Block Patterns

Reference existing blocks in `blocks/`:
- `blocks/breadcrumbs/` - Navigation breadcrumbs with schema
- `blocks/faq/` - FAQ schema block
- `blocks/howto/` - How-To schema block

## Schema Types to Consider

- `Review` / `AggregateRating`
- `Product`
- `Event`
- `Recipe`
- `Article` / `NewsArticle`
- `Person`
- `Organization`
- `LocalBusiness`

## Best Practices

1. **Use block.json** for metadata (WordPress 5.8+)
2. **Dynamic rendering** - Use `render.php` for server-side output
3. **Schema validation** - Ensure JSON-LD is valid
4. **Accessibility** - Include proper ARIA attributes
5. **Responsive** - Test on mobile viewports
6. **i18n** - Wrap all strings for translation

## Example Usage

```
/gutenberg-block review Star rating and review schema block for products and services
```
