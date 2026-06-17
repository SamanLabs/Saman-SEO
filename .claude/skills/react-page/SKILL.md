---
name: react-page
description: Create a new React admin page for the Saman SEO plugin dashboard. Use when adding new settings pages, tool interfaces, or admin features that need a UI.
argument-hint: [page-name] [description]
---

# Create React Admin Page

Generate a new React page component for the Saman SEO admin interface.

## Arguments
- `$ARGUMENTS` should contain: page name (e.g., "KeywordPlanner") and description

## Steps

1. **Analyze the request** to determine:
   - Page name (PascalCase for React component)
   - Page purpose and main features
   - Required API endpoints
   - UI components needed

2. **Create the React page** at `src-v2/pages/{PageName}.js`

3. **Follow this template**:

```jsx
/**
 * {PageName} page component.
 *
 * {Description}
 *
 * @package Saman SEO
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import Header from '../components/Header';

/**
 * {PageName} component.
 *
 * @return {JSX.Element} The rendered component.
 */
const {PageName} = () => {
    // State
    const [isLoading, setIsLoading] = useState(true);
    const [isSaving, setIsSaving] = useState(false);
    const [data, setData] = useState(null);
    const [error, setError] = useState(null);

    // Fetch data on mount
    useEffect(() => {
        fetchData();
    }, []);

    /**
     * Fetch data from API.
     */
    const fetchData = useCallback(async () => {
        setIsLoading(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/{endpoint}',
            });
            setData(response);
        } catch (err) {
            setError(err.message || __('Failed to load data.', 'saman-seo'));
            console.error('Error fetching data:', err);
        } finally {
            setIsLoading(false);
        }
    }, []);

    /**
     * Save data to API.
     */
    const handleSave = useCallback(async () => {
        setIsSaving(true);
        setError(null);

        try {
            await apiFetch({
                path: '/saman-seo/v1/{endpoint}',
                method: 'POST',
                data: data,
            });
            // Show success notice
        } catch (err) {
            setError(err.message || __('Failed to save.', 'saman-seo'));
            console.error('Error saving:', err);
        } finally {
            setIsSaving(false);
        }
    }, [data]);

    // Loading state
    if (isLoading) {
        return (
            <div className="samanseo-admin-page">
                <Header
                    title={__('{Page Title}', 'saman-seo')}
                    description={__('{Page description}', 'saman-seo')}
                />
                <div className="samanseo-loading">
                    <span className="spinner is-active"></span>
                    {__('Loading...', 'saman-seo')}
                </div>
            </div>
        );
    }

    // Error state
    if (error) {
        return (
            <div className="samanseo-admin-page">
                <Header
                    title={__('{Page Title}', 'saman-seo')}
                    description={__('{Page description}', 'saman-seo')}
                />
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            </div>
        );
    }

    return (
        <div className="samanseo-admin-page">
            <Header
                title={__('{Page Title}', 'saman-seo')}
                description={__('{Page description}', 'saman-seo')}
            />

            <div className="samanseo-content">
                {/* Page content here */}
            </div>

            <div className="samanseo-footer">
                <button
                    type="button"
                    className="button button-primary"
                    onClick={handleSave}
                    disabled={isSaving}
                >
                    {isSaving
                        ? __('Saving...', 'saman-seo')
                        : __('Save Changes', 'saman-seo')
                    }
                </button>
            </div>
        </div>
    );
};

export default {PageName};
```

4. **Register the page in App.js**:

```jsx
// Add lazy import at the top
const {PageName} = lazy(() => import('./pages/{PageName}'));

// Add route in the pages array
{ path: '{page-slug}', component: {PageName} },
```

5. **Register the page in Admin_V2 PHP** (`includes/class-saman-seo-admin-v2.php`):
   - Add `'saman-seo-{slug}' => '{view-name}'` to the `$view_map` property (maps WP page slug to React view name)
   - Add `'{slug}' => __( '{Page Title}', 'saman-seo' )` to the `$hidden_subpages` array inside `register_menu()` (registers a hidden WP submenu so direct-URL / refresh works)
   - **WARNING**: NEVER create a separate PHP service that calls `add_submenu_page()` for the same slug — this causes a routing conflict that breaks the React SPA on refresh

6. **Create styles** if needed in `src-v2/less/pages/{page-name}.less`

## Patterns to Follow

- **Imports**: Use `@wordpress/element`, `@wordpress/i18n`, `@wordpress/api-fetch`
- **State**: Use React hooks (`useState`, `useEffect`, `useCallback`)
- **API**: Use `apiFetch()` for REST API calls
- **i18n**: Wrap all strings in `__()` or `_n()` for translation
- **Loading**: Always handle loading and error states
- **Styling**: Use BEM naming with `samanseo-` prefix

## Common Components Available

- `Header` - Page header with title and description
- `SearchPreview` - SERP preview component
- `TemplateInput` - Input with template variable support
- `VariablePicker` - Template variable selector
- `AiGenerateModal` - AI content generation modal

## Example Usage

```
/react-page KeywordPlanner Plan and manage target keywords for content
```
