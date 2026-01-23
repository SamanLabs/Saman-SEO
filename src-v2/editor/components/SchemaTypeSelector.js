/**
 * Schema Type Selector Component
 *
 * Dropdown for selecting schema type per post.
 */

import { SelectControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

// Schema type options - should match backend registry
// These are the content-focused types relevant for posts/pages
const schemaTypeOptions = [
    { value: '', label: __('Use default', 'saman-seo') },
    { value: 'Article', label: __('Article', 'saman-seo') },
    { value: 'BlogPosting', label: __('Blog Posting', 'saman-seo') },
    { value: 'NewsArticle', label: __('News Article', 'saman-seo') },
];

const SchemaTypeSelector = ({ value, onChange, postType }) => {
    return (
        <div className="saman-seo-field">
            <SelectControl
                label={__('Schema Type', 'saman-seo')}
                value={value || ''}
                options={schemaTypeOptions}
                onChange={onChange}
                help={__('Override the default schema type for this post', 'saman-seo')}
            />
        </div>
    );
};

export default SchemaTypeSelector;
