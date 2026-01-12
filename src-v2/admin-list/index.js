/**
 * WP SEO Pilot - Admin List SEO Badge
 *
 * Hydrates SEO score badges in the WordPress admin post list.
 */

import { createRoot } from '@wordpress/element';
import SEOScoreBadge from './components/SEOScoreBadge';
import './admin-list.css';

/**
 * Initialize all SEO badge placeholders on the page.
 */
const initBadges = () => {
    const placeholders = document.querySelectorAll('.wpseopilot-badge-placeholder');

    placeholders.forEach((placeholder) => {
        const data = placeholder.dataset;

        const props = {
            score: parseInt(data.score, 10) || 0,
            level: data.level || 'poor',
            label: data.label || '',
            issues: data.issues ? JSON.parse(data.issues) : [],
            flags: data.flags ? JSON.parse(data.flags) : [],
        };

        // Create React root and render
        const root = createRoot(placeholder);
        root.render(<SEOScoreBadge {...props} />);

        // Mark as initialized
        placeholder.classList.add('wpseopilot-badge-initialized');
    });
};

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initBadges);
} else {
    initBadges();
}

// Re-initialize on AJAX list refresh (for pagination, filters, etc.)
if (typeof jQuery !== 'undefined') {
    jQuery(document).on('ajaxComplete', (event, xhr, settings) => {
        if (settings.data && settings.data.includes('action=fetch-list')) {
            setTimeout(initBadges, 100);
        }
    });
}
