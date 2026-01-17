/**
 * Saman SEO Analytics Utility
 *
 * Tracks feature usage to help improve the plugin.
 * All tracking is anonymous and privacy-respecting:
 * - No cookies used
 * - No personal data collected
 * - Only tracks within the plugin admin pages
 * - Can be disabled in Settings > Modules > Analytics
 */

// Check if analytics is available and enabled
const isAnalyticsEnabled = () => {
    return typeof window._paq !== 'undefined';
};

/**
 * Track a feature usage event
 *
 * @param {string} category - Event category (e.g., 'Redirects', 'AI', 'Sitemap')
 * @param {string} action - Event action (e.g., 'Create', 'Generate', 'Export')
 * @param {string} name - Optional event name/label
 * @param {number} value - Optional numeric value
 */
export const trackEvent = (category, action, name = null, value = null) => {
    if (!isAnalyticsEnabled()) return;

    try {
        const eventData = ['trackEvent', category, action];
        if (name) eventData.push(name);
        if (value !== null) eventData.push(value);

        window._paq.push(eventData);

        // Debug log in development
        if (window.SamanSEODebug) {
            console.log('Saman SEO Analytics:', { category, action, name, value });
        }
    } catch (e) {
        // Silently fail - analytics should never break functionality
    }
};

/**
 * Track a page view (for SPA navigation)
 *
 * @param {string} pageName - Page name/title
 * @param {string} pageUrl - Optional custom URL
 */
export const trackPageView = (pageName, pageUrl = null) => {
    if (!isAnalyticsEnabled()) return;

    try {
        if (pageUrl) {
            window._paq.push(['setCustomUrl', pageUrl]);
        }
        window._paq.push(['setDocumentTitle', `Saman SEO - ${pageName}`]);
        window._paq.push(['trackPageView']);
    } catch (e) {
        // Silently fail
    }
};

/**
 * Track feature-specific events with predefined categories
 */
export const analytics = {
    // Redirects
    redirects: {
        create: (type) => trackEvent('Redirects', 'Create', type),
        edit: () => trackEvent('Redirects', 'Edit'),
        delete: () => trackEvent('Redirects', 'Delete'),
        import: (count) => trackEvent('Redirects', 'Import', 'count', count),
        export: (count) => trackEvent('Redirects', 'Export', 'count', count),
    },

    // Sitemap
    sitemap: {
        generate: () => trackEvent('Sitemap', 'Generate'),
        submit: (engine) => trackEvent('Sitemap', 'Submit', engine),
        configure: () => trackEvent('Sitemap', 'Configure'),
    },

    // AI Features
    ai: {
        generate: (type) => trackEvent('AI', 'Generate', type), // 'title', 'description', 'both'
        assistant: (assistantId) => trackEvent('AI', 'Assistant', assistantId),
        modelChange: (model) => trackEvent('AI', 'ModelChange', model),
        customModel: (action) => trackEvent('AI', 'CustomModel', action), // 'create', 'edit', 'delete', 'test'
    },

    // Search Appearance
    searchAppearance: {
        save: (section) => trackEvent('SearchAppearance', 'Save', section),
        aiGenerate: (field) => trackEvent('SearchAppearance', 'AIGenerate', field),
    },

    // Internal Linking
    internalLinking: {
        createRule: () => trackEvent('InternalLinking', 'CreateRule'),
        applySuggestion: () => trackEvent('InternalLinking', 'ApplySuggestion'),
        analyze: () => trackEvent('InternalLinking', 'Analyze'),
    },

    // Tools
    tools: {
        bulkEditor: (action) => trackEvent('Tools', 'BulkEditor', action),
        contentGaps: () => trackEvent('Tools', 'ContentGaps'),
        schemaBuilder: (schemaType) => trackEvent('Tools', 'SchemaBuilder', schemaType),
    },

    // Settings
    settings: {
        save: (tab) => trackEvent('Settings', 'Save', tab),
        moduleToggle: (module, enabled) => trackEvent('Settings', 'ModuleToggle', module, enabled ? 1 : 0),
        export: () => trackEvent('Settings', 'Export'),
        import: () => trackEvent('Settings', 'Import'),
        reset: () => trackEvent('Settings', 'Reset'),
    },

    // Setup Wizard
    setup: {
        start: () => trackEvent('Setup', 'Start'),
        complete: () => trackEvent('Setup', 'Complete'),
        skip: (step) => trackEvent('Setup', 'Skip', step),
    },

    // Audit
    audit: {
        run: () => trackEvent('Audit', 'Run'),
        fixIssue: (issueType) => trackEvent('Audit', 'FixIssue', issueType),
    },

    // 404 Errors
    errors404: {
        createRedirect: () => trackEvent('404Errors', 'CreateRedirect'),
        ignore: () => trackEvent('404Errors', 'Ignore'),
        clear: () => trackEvent('404Errors', 'Clear'),
    },

    // Generic tracking for custom events
    track: trackEvent,
    pageView: trackPageView,
};

export default analytics;
