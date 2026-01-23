# Phase 5: Admin UI - Research

**Researched:** 2026-01-23
**Domain:** WordPress Gutenberg Block Editor Integration, REST API, React Admin UI
**Confidence:** HIGH

## Summary

Phase 5 requires implementing admin UI components that allow users to configure and preview schema markup through the WordPress admin. This involves three primary areas: (1) extending the existing Search Appearance settings page with post type schema defaults, (2) adding a schema type selector to the existing Gutenberg editor sidebar, and (3) creating a live JSON-LD preview panel that updates in real-time.

The research confirms that the existing codebase already has a solid foundation with React admin pages using `@wordpress/scripts`, a Gutenberg sidebar via `PluginSidebar`, and a REST API structure following WordPress best practices. The recommended approach leverages these existing patterns rather than introducing new architectural elements.

**Primary recommendation:** Extend the existing `SEOPanel` component with a new "Schema" tab containing a type selector and live JSON-LD preview, and add schema defaults to the SearchAppearance page's Content Types tab. Create a new REST endpoint for schema preview data using the existing controller pattern.

## Standard Stack

The established libraries/tools for this domain:

### Core
| Library | Version | Purpose | Why Standard |
|---------|---------|---------|--------------|
| @wordpress/scripts | ^27.6.0 | Build tooling | Already in use, bundled with WordPress |
| @wordpress/element | ^5.30.0 | React abstraction | Already in use, handles WordPress-specific React patterns |
| @wordpress/api-fetch | ^6.50.0 | REST API requests | Already in use, handles nonces automatically |
| @wordpress/data | bundled | State management | Standard for Gutenberg editor integration |
| @wordpress/components | bundled | UI components | Native WordPress styling, accessibility |
| @wordpress/plugins | bundled | Plugin registration | Required for PluginSidebar |
| @wordpress/editor | bundled | Editor SlotFills | PluginSidebar, PluginDocumentSettingPanel |

### Supporting
| Library | Version | Purpose | When to Use |
|---------|---------|---------|-------------|
| @wordpress/i18n | bundled | Internationalization | All user-facing strings |
| @wordpress/hooks | bundled | Extensibility | Filter/action system for schema preview |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| PluginSidebar | PluginDocumentSettingPanel | Document settings panel is more compact but less visible - sidebar better for complex UI like JSON-LD preview |
| Custom state | @wordpress/data store | Custom state is simpler for isolated components - data store better if multiple components need shared state |

**Installation:**
No new packages needed - all dependencies already present in existing codebase.

## Architecture Patterns

### Recommended Project Structure
```
src-v2/
├── editor/
│   ├── index.js                 # Existing - already registers sidebar
│   ├── components/
│   │   ├── SEOPanel.js          # Existing - add Schema tab here
│   │   ├── SchemaTypeSelector.js # NEW: Schema type dropdown
│   │   └── SchemaPreview.js      # NEW: Live JSON-LD preview panel
│   └── hooks/
│       └── useSchemaPreview.js   # NEW: Hook for fetching preview
├── pages/
│   └── SearchAppearance.js      # Existing - add schema defaults UI
includes/
├── Api/
│   └── class-schema-preview-controller.php  # NEW: Preview endpoint
├── Schema/
│   └── class-schema-context.php  # Existing - already has schema_type logic
└── class-saman-seo-service-post-meta.php    # Update: add schema_type to meta
```

### Pattern 1: Extending Existing Sidebar Tabs
**What:** Add a "Schema" tab to the existing SEOPanel component
**When to use:** When adding related functionality to existing UI
**Example:**
```javascript
// Source: Existing SEOPanel.js pattern
// Add to tabs array:
const tabs = ['general', 'analysis', 'advanced', 'social', 'schema'];

// Add tab button:
<button
    type="button"
    className={`saman-seo-tab ${activeTab === 'schema' ? 'active' : ''}`}
    onClick={() => setActiveTab('schema')}
>
    Schema
</button>

// Add tab content:
{activeTab === 'schema' && (
    <div className="saman-seo-tab-content">
        <SchemaTypeSelector
            value={seoMeta.schema_type}
            onChange={(type) => updateMeta('schema_type', type)}
            postType={postType}
        />
        <SchemaPreview postId={postId} schemaType={seoMeta.schema_type} />
    </div>
)}
```

### Pattern 2: Real-time Preview with Debounced API Fetch
**What:** Fetch preview data as user edits, with debounce to avoid API spam
**When to use:** Live previews that depend on server-side rendering
**Example:**
```javascript
// Source: WordPress @wordpress/api-fetch documentation
import { useState, useEffect, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const useSchemaPreview = (postId, schemaType, dependencies = []) => {
    const [preview, setPreview] = useState(null);
    const [loading, setLoading] = useState(false);
    const timeoutRef = useRef(null);

    useEffect(() => {
        if (!postId) return;

        // Clear previous timeout
        if (timeoutRef.current) {
            clearTimeout(timeoutRef.current);
        }

        // Debounce: wait 500ms after last change
        timeoutRef.current = setTimeout(() => {
            setLoading(true);
            apiFetch({
                path: `/saman-seo/v1/schema/preview/${postId}`,
                method: 'POST',
                data: { schema_type: schemaType }
            })
            .then((response) => {
                if (response.success) {
                    setPreview(response.data);
                }
            })
            .finally(() => setLoading(false));
        }, 500);

        return () => {
            if (timeoutRef.current) {
                clearTimeout(timeoutRef.current);
            }
        };
    }, [postId, schemaType, ...dependencies]);

    return { preview, loading };
};
```

### Pattern 3: REST Controller for Preview Data
**What:** Server-side endpoint that generates preview JSON-LD
**When to use:** When preview requires server-side schema generation logic
**Example:**
```php
// Source: Existing REST_Controller pattern in codebase
class Schema_Preview_Controller extends REST_Controller {

    protected $rest_base = 'schema/preview';

    public function register_routes() {
        register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<post_id>\d+)', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [$this, 'get_preview'],
                'permission_callback' => [$this, 'permission_check'],
                'args'                => [
                    'post_id'     => ['required' => true, 'type' => 'integer'],
                    'schema_type' => ['type' => 'string'],
                ],
            ],
        ]);
    }

    public function get_preview($request) {
        $post_id = $request->get_param('post_id');
        $schema_type = $request->get_param('schema_type');

        $post = get_post($post_id);
        if (!$post) {
            return $this->error('Post not found', 'not_found', 404);
        }

        // Create context with override
        $context = Schema_Context::from_post($post);
        if ($schema_type) {
            $context->schema_type = $schema_type;
        }

        // Build graph
        $registry = Schema_Registry::instance();
        $manager = new Schema_Graph_Manager($registry);
        $graph = $manager->build($context);

        return $this->success([
            'json_ld' => $graph,
            'schema_type' => $context->schema_type,
        ]);
    }
}
```

### Pattern 4: Post Type Schema Defaults in Settings
**What:** Dropdown in SearchAppearance Content Types for default schema per post type
**When to use:** Site-wide defaults that can be overridden per-post
**Example:**
```javascript
// Source: Existing SearchAppearance.js pattern
// Add to post type settings form:
<SelectControl
    label={__('Default Schema Type', 'saman-seo')}
    value={postType.schema_type || 'Article'}
    options={schemaTypeOptions}
    onChange={(value) => updatePostType(postType.slug, 'schema_type', value)}
    help={__('Schema type used for posts of this type unless overridden', 'saman-seo')}
/>

// schemaTypeOptions already exists in SearchAppearance.js at line 26-41
```

### Anti-Patterns to Avoid
- **Direct DOM manipulation:** Never use jQuery or direct DOM access in Gutenberg - use React state and WordPress data stores
- **Storing schema in separate meta key:** Keep schema_type in the existing `_SAMAN_SEO_meta` object to maintain single source of truth
- **Fetching preview on every keystroke:** Always debounce preview API calls (500ms minimum)
- **Duplicating schema generation logic:** Use the existing Schema_Graph_Manager and Schema_Registry - don't recreate in JavaScript

## Don't Hand-Roll

Problems that look simple but have existing solutions:

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| JSON syntax highlighting | Custom highlighter | `<pre><code>` with CSS | Syntax highlighting adds complexity; raw JSON with monospace font is sufficient for preview |
| Schema type list | Hardcoded list | Pull from Schema_Registry | Registry is the source of truth for available types |
| Post meta persistence | Custom save handler | `editPost({ meta: {...} })` | WordPress data layer handles dirty state, autosave, REST sync |
| Dropdown component | Custom select | `@wordpress/components` SelectControl | Accessible, WordPress-styled, keyboard navigable |
| Loading states | Custom spinner | `@wordpress/components` Spinner | Consistent with WordPress UI |

**Key insight:** The existing codebase has established patterns for every UI element needed. The editor sidebar already handles meta updates via `editPost()` dispatch. The SearchAppearance page already shows post type settings. Extending these is simpler and more maintainable than building new patterns.

## Common Pitfalls

### Pitfall 1: Preview Not Updating on Content Changes
**What goes wrong:** JSON-LD preview shows stale data because it doesn't react to content/title changes
**Why it happens:** Preview only watches `schema_type` but not other post attributes
**How to avoid:** Include post title, content, and other relevant selectors as dependencies in the preview hook
**Warning signs:** Preview stays the same after editing post content
```javascript
// CORRECT: Watch all relevant data
const { postTitle, postContent, featuredImage } = useSelect((select) => ({
    postTitle: select('core/editor').getEditedPostAttribute('title'),
    postContent: select('core/editor').getEditedPostContent(),
    featuredImage: select('core/editor').getEditedPostAttribute('featured_media'),
}));

// Pass as dependencies to preview hook
const { preview } = useSchemaPreview(postId, schemaType, [postTitle, postContent, featuredImage]);
```

### Pitfall 2: Schema Type Not Saving with Post
**What goes wrong:** User selects schema type but it reverts after page reload
**Why it happens:** `schema_type` not registered in post meta schema for REST API
**How to avoid:** Add `schema_type` to the `_SAMAN_SEO_meta` schema in `Post_Meta::register_meta()`
**Warning signs:** Network inspector shows schema_type missing from PUT request body

### Pitfall 3: Dropdown Shows Invalid Options for Post Type
**What goes wrong:** User can select schema types that don't make sense (e.g., LocalBusiness for a blog post)
**Why it happens:** Using generic list without filtering by context
**How to avoid:** Filter schema options based on post type and registered schema configurations
**Warning signs:** Schema validation errors, inappropriate rich results in testing tools

### Pitfall 4: Memory Leak from Unmounted Component Fetching
**What goes wrong:** Component unmounts but API response still tries to setState
**Why it happens:** No cleanup of pending requests when component unmounts
**How to avoid:** Use AbortController or track mounted state
**Warning signs:** React console warnings about updating unmounted components
```javascript
useEffect(() => {
    const controller = new AbortController();

    apiFetch({
        path: `/saman-seo/v1/schema/preview/${postId}`,
        signal: controller.signal,
    }).then(/* ... */);

    return () => controller.abort();
}, [postId]);
```

### Pitfall 5: Post Type Defaults Not Applying
**What goes wrong:** New posts don't get the default schema type from Search Appearance settings
**Why it happens:** Schema_Context fallback priority not followed or option key mismatch
**How to avoid:** Verify option key matches `SAMAN_SEO_post_type_seo_settings` structure used in Schema_Context
**Warning signs:** Posts always default to Article regardless of settings

## Code Examples

Verified patterns from official sources:

### SelectControl for Schema Type
```javascript
// Source: @wordpress/components documentation
import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

const SchemaTypeSelector = ({ value, onChange, availableTypes }) => (
    <SelectControl
        label={__('Schema Type', 'saman-seo')}
        value={value || 'Article'}
        options={availableTypes.map((type) => ({
            value: type.slug,
            label: type.label,
        }))}
        onChange={onChange}
        help={__('Override the default schema type for this post', 'saman-seo')}
    />
);
```

### JSON-LD Preview Panel
```javascript
// Source: Existing SearchPreview component pattern
const SchemaPreview = ({ jsonLd, loading }) => {
    if (loading) {
        return (
            <div className="saman-seo-schema-preview saman-seo-schema-preview--loading">
                <Spinner />
            </div>
        );
    }

    const formatted = JSON.stringify(jsonLd, null, 2);

    return (
        <div className="saman-seo-schema-preview">
            <div className="saman-seo-schema-preview-header">
                <span className="saman-seo-section-label">
                    {__('JSON-LD Preview', 'saman-seo')}
                </span>
                <Button
                    variant="tertiary"
                    onClick={() => navigator.clipboard.writeText(formatted)}
                    icon="clipboard"
                >
                    {__('Copy', 'saman-seo')}
                </Button>
            </div>
            <pre className="saman-seo-schema-preview-code">
                <code>{formatted}</code>
            </pre>
        </div>
    );
};
```

### useSelect for Post Data
```javascript
// Source: @wordpress/data documentation
import { useSelect } from '@wordpress/data';

const usePostData = () => {
    return useSelect((select) => {
        const editor = select('core/editor');
        return {
            postId: editor.getCurrentPostId(),
            postType: editor.getCurrentPostType(),
            postTitle: editor.getEditedPostAttribute('title'),
            postContent: editor.getEditedPostContent(),
            postMeta: editor.getEditedPostAttribute('meta'),
        };
    }, []);
};
```

### REST Route with Validation
```php
// Source: WordPress REST API Handbook
register_rest_route($this->namespace, '/schema/preview/(?P<post_id>\d+)', [
    'methods'             => 'POST',
    'callback'            => [$this, 'get_preview'],
    'permission_callback' => [$this, 'permission_check'],
    'args'                => [
        'post_id' => [
            'required'          => true,
            'validate_callback' => function($param) {
                return is_numeric($param) && get_post($param);
            },
        ],
        'schema_type' => [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ],
    ],
]);
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| withSelect/withDispatch HOCs | useSelect/useDispatch hooks | Gutenberg 5.9 (2019) | Simpler code, no wrapper hell |
| @wordpress/edit-post imports | @wordpress/editor imports | WordPress 6.0+ | PluginSidebar now from @wordpress/editor |
| Class components | Functional components with hooks | React 16.8 (2019) | Industry standard, simpler state management |
| Custom AJAX handlers | REST API with apiFetch | WordPress 4.7 (2016) | Standardized, nonce handling, JSON responses |

**Deprecated/outdated:**
- `withSelect` HOC: Still works but `useSelect` hook preferred
- `wp.editPost.PluginSidebar`: Import from `@wordpress/editor` instead
- Classic editor meta boxes: Still supported but Gutenberg sidebar is primary for block editor

## Open Questions

Things that couldn't be fully resolved:

1. **Schema type filtering per post type**
   - What we know: Registry has `post_types` config per schema type
   - What's unclear: Should dropdown only show applicable types or all types?
   - Recommendation: Show all types but mark recommended ones (derived from registry), let user override

2. **Preview for unsaved posts**
   - What we know: New posts have no ID until first save
   - What's unclear: How to preview schema for draft/unsaved content
   - Recommendation: For new posts without ID, show placeholder message "Save draft to see preview"

3. **Real-time vs save-triggered preview**
   - What we know: Real-time is better UX, but uses more server resources
   - What's unclear: Performance impact on busy sites
   - Recommendation: Use debounced real-time (500ms) with loading indicator

## Sources

### Primary (HIGH confidence)
- [WordPress Block Editor PluginSidebar Documentation](https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-sidebar/) - SlotFill implementation
- [WordPress Block Editor PluginDocumentSettingPanel Documentation](https://developer.wordpress.org/block-editor/reference-guides/slotfills/plugin-document-setting-panel/) - Alternative slot for document settings
- [WordPress @wordpress/data Documentation](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-data/) - useSelect, useDispatch hooks
- [WordPress REST API Handbook](https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/) - Custom endpoint patterns
- Existing codebase: `src-v2/editor/index.js`, `includes/Api/class-rest-controller.php`, `includes/Schema/` - Verified patterns

### Secondary (MEDIUM confidence)
- [10up Gutenberg Best Practices - Data API Guide](https://gutenberg.10up.com/guides/data-api/) - useSelect/useDispatch patterns
- [rtCamp Custom Sidebar Panels Handbook](https://rtcamp.com/handbook/developing-for-block-editor-and-site-editor/custom-sidebar-panels/) - PluginDocumentSettingPanel examples
- [Kinsta WP REST API Custom Endpoints](https://kinsta.com/blog/wp-rest-api-custom-endpoint/) - REST controller best practices

### Tertiary (LOW confidence)
- WebSearch results for SEO plugin patterns (Yoast, RankMath) - Implementation details not verified with source code

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - All tools already in use in existing codebase
- Architecture: HIGH - Patterns verified against existing codebase and official docs
- Pitfalls: MEDIUM - Based on general Gutenberg development experience, some specific to this codebase

**Research date:** 2026-01-23
**Valid until:** 60 days (Gutenberg APIs are relatively stable, existing codebase patterns well established)
