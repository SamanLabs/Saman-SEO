/**
 * Schema Preview Component
 *
 * Displays live JSON-LD preview with copy functionality.
 */

import { Button, Spinner } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { useState } from '@wordpress/element';

const SchemaPreview = ({ jsonLd, loading, error }) => {
    const [copied, setCopied] = useState(false);

    const handleCopy = () => {
        const formatted = JSON.stringify(jsonLd, null, 2);
        navigator.clipboard.writeText(formatted).then(() => {
            setCopied(true);
            setTimeout(() => setCopied(false), 2000);
        });
    };

    if (loading) {
        return (
            <div className="saman-seo-schema-preview saman-seo-schema-preview--loading">
                <Spinner />
                <span>{__('Loading preview...', 'saman-seo')}</span>
            </div>
        );
    }

    if (error) {
        return (
            <div className="saman-seo-schema-preview saman-seo-schema-preview--error">
                <span className="saman-seo-schema-preview-error">{error}</span>
            </div>
        );
    }

    if (!jsonLd) {
        return (
            <div className="saman-seo-schema-preview saman-seo-schema-preview--empty">
                <p className="saman-seo-field-help">
                    {__('Save post to see schema preview', 'saman-seo')}
                </p>
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
                    onClick={handleCopy}
                    className="saman-seo-schema-preview-copy"
                >
                    {copied ? __('Copied!', 'saman-seo') : __('Copy', 'saman-seo')}
                </Button>
            </div>
            <pre className="saman-seo-schema-preview-code">
                <code>{formatted}</code>
            </pre>
        </div>
    );
};

export default SchemaPreview;
