import { render } from '@wordpress/element';
import App from './App';

import './index.css';

// Page to view mapping (must match App.js pageToView)
const pageToView = {
    'saman-seo': 'dashboard',
    'saman-seo-dashboard': 'dashboard',
    'saman-seo-search-appearance': 'search-appearance',
    'saman-seo-sitemap': 'sitemap',
    'saman-seo-tools': 'tools',
    'saman-seo-redirects': 'redirects',
    'saman-seo-404-log': '404-log',
    'saman-seo-internal-linking': 'internal-linking',
    'saman-seo-audit': 'audit',
    'saman-seo-ai-assistant': 'ai-assistant',
    'saman-seo-assistants': 'assistants',
    'saman-seo-settings': 'settings',
    'saman-seo-more': 'more',
    'saman-seo-bulk-editor': 'bulk-editor',
    'saman-seo-content-gaps': 'content-gaps',
    'saman-seo-schema-builder': 'schema-builder',
    'saman-seo-link-health': 'link-health',
    'saman-seo-local-seo': 'local-seo',
    'saman-seo-robots-txt': 'robots-txt',
    'saman-seo-image-seo': 'image-seo',
    'saman-seo-instant-indexing': 'instant-indexing',
    'saman-seo-schema-validator': 'schema-validator',
    'saman-seo-htaccess-editor': 'htaccess-editor',
    'saman-seo-mobile-friendly': 'mobile-friendly',
};

// Get initial view from URL (most reliable) or fallback to settings
const getInitialView = () => {
    const url = new URL(window.location.href);
    const page = url.searchParams.get('page');
    if (page && pageToView[page]) {
        return pageToView[page];
    }
    // Fallback to settings if URL doesn't have page param
    const settings = window.samanSeoV2Settings || {};
    return settings.initialView || 'dashboard';
};

render(<App initialView={getInitialView()} />, document.getElementById('saman-seo-v2-root'));
