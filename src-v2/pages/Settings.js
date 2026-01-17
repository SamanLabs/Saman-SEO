import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import AnalyticsNotice from '../components/AnalyticsNotice';
import useUrlTab from '../hooks/useUrlTab';
import { analytics } from '../utils/analytics';

const settingsTabs = [
    { id: 'general', label: 'General' },
    { id: 'modules', label: 'Modules' },
    { id: 'breadcrumbs', label: 'Breadcrumbs' },
    { id: 'social', label: 'Social' },
    { id: 'advanced', label: 'Advanced' },
    { id: 'tools', label: 'Tools' },
];

const defaultSettings = {
    // General
    separator: '-',
    knowledge_graph_type: 'organization',
    organization_name: '',
    organization_logo: '',
    person_name: '',
    // Webmaster tools
    google_verification: '',
    bing_verification: '',
    pinterest_verification: '',
    yandex_verification: '',
    baidu_verification: '',
    // Modules
    module_sitemap: true,
    module_redirects: true,
    module_404_log: true,
    module_social_cards: true,
    // 404 Monitor settings
    show_404_dashboard_widget: true,
    enable_404_cleanup: false,
    cleanup_404_days: 30,
    enable_404_notifications: false,
    notification_404_threshold: 10,
    notification_404_email: '',
    module_llm_txt: false,
    module_local_seo: false,
    module_internal_linking: true,
    module_schema: true,
    module_breadcrumbs: false,
    // Breadcrumbs settings
    breadcrumb_separator: '>',
    breadcrumb_separator_custom: '',
    breadcrumb_show_home: true,
    breadcrumb_home_label: '',
    breadcrumb_show_current: true,
    breadcrumb_link_current: false,
    breadcrumb_truncate_length: 0,
    breadcrumb_show_on_front: false,
    breadcrumb_style_preset: 'default',
    module_analytics: false,
    module_search_console: false,
    module_ai_assistant: true,
    module_indexnow: false,
    // IndexNow settings
    indexnow_submit_on_publish: true,
    indexnow_submit_on_update: true,
    // Social
    default_og_image: '',
    twitter_card_type: 'summary_large_image',
    twitter_username: '',
    facebook_app_id: '',
    facebook_admin_id: '',
    // Advanced
    enable_admin_bar: true,
    output_clean_head: true,
    remove_shortlinks: true,
    remove_rsd_link: true,
    remove_wlwmanifest: true,
    remove_wp_generator: true,
    remove_feed_links: false,
    disable_json_ld: false,
    disable_emoji: false,
    disable_comments_css: false,
    disable_gutenberg_css: false,
    enable_link_suggestions: true,
    enable_internal_link_count: true,
    enable_cornerstone_content: true,
    cache_schema: true,
    purge_on_save: true,
    enable_rest_api: true,
    debug_mode: false,
    // Performance
    lazy_load_schema: true,
    minify_schema_output: true,
    async_schema_validation: false,
    // API Keys
    openai_api_key: '',
    google_api_key: '',
    bing_api_key: '',
};

const Settings = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: settingsTabs, defaultTab: 'general' });
    const [settings, setSettings] = useState(defaultSettings);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saved, setSaved] = useState(false);
    const [importFile, setImportFile] = useState(null);
    const [resettingWizard, setResettingWizard] = useState(false);

    // Fetch settings
    const fetchSettings = useCallback(async () => {
        setLoading(true);
        try {
            const res = await apiFetch({ path: '/saman-seo/v1/settings' });
            if (res.success && res.data) {
                setSettings(prev => ({ ...prev, ...res.data }));
            }
        } catch (error) {
            console.error('Failed to fetch settings:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    // Update setting
    const updateSetting = (key, value) => {
        setSettings(prev => ({ ...prev, [key]: value }));
        setSaved(false);
    };

    // Save settings
    const handleSave = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/settings',
                method: 'POST',
                data: settings,
            });
            setSaved(true);
            setTimeout(() => setSaved(false), 3000);
        } catch (error) {
            console.error('Failed to save settings:', error);
        } finally {
            setSaving(false);
        }
    };

    // Export settings
    const handleExport = () => {
        const data = JSON.stringify(settings, null, 2);
        const blob = new Blob([data], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `saman-seo-settings-${new Date().toISOString().split('T')[0]}.json`;
        a.click();
        URL.revokeObjectURL(url);
    };

    // Import settings
    const handleImport = () => {
        if (!importFile) return;
        const reader = new FileReader();
        reader.onload = (e) => {
            try {
                const imported = JSON.parse(e.target.result);
                setSettings(prev => ({ ...prev, ...imported }));
                setSaved(false);
                setImportFile(null);
            } catch (error) {
                alert('Invalid JSON file');
            }
        };
        reader.readAsText(importFile);
    };

    // Reset to defaults
    const handleReset = () => {
        if (window.confirm('Are you sure you want to reset all settings to defaults? This cannot be undone.')) {
            setSettings(defaultSettings);
            setSaved(false);
        }
    };

    // Reset setup wizard
    const handleResetWizard = async () => {
        setResettingWizard(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/setup/reset',
                method: 'POST',
            });
            alert('Setup wizard has been reset. It will appear on the next page load.');
        } catch (error) {
            console.error('Failed to reset wizard:', error);
            alert('Failed to reset the setup wizard.');
        } finally {
            setResettingWizard(false);
        }
    };

    if (loading) {
        return (
            <div className="page">
                <div className="loading-state">Loading settings...</div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Configure Saman SEO features, integrations, and preferences.</p>
                </div>
                <div className="page-header__actions">
                    {saved && <span className="save-indicator">Saved</span>}
                    <button type="button" className="button primary" onClick={handleSave} disabled={saving}>
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </div>

            <SubTabs tabs={settingsTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Settings sections" />

            {activeTab === 'general' && (
                <GeneralTab settings={settings} updateSetting={updateSetting} />
            )}

            {activeTab === 'modules' && (
                <ModulesTab settings={settings} updateSetting={updateSetting} />
            )}

            {activeTab === 'breadcrumbs' && (
                <BreadcrumbsTab settings={settings} updateSetting={updateSetting} />
            )}

            {activeTab === 'social' && (
                <SocialTab settings={settings} updateSetting={updateSetting} />
            )}

            {activeTab === 'advanced' && (
                <AdvancedTab settings={settings} updateSetting={updateSetting} />
            )}

            {activeTab === 'tools' && (
                <ToolsTab
                    settings={settings}
                    onExport={handleExport}
                    onImport={handleImport}
                    onReset={handleReset}
                    onResetWizard={handleResetWizard}
                    resettingWizard={resettingWizard}
                    importFile={importFile}
                    setImportFile={setImportFile}
                />
            )}
        </div>
    );
};

// General Tab
const GeneralTab = ({ settings, updateSetting }) => {
    const separators = [
        { value: '-', label: 'Dash (-)' },
        { value: '|', label: 'Pipe (|)' },
        { value: '>', label: 'Greater than (>)' },
        { value: '<', label: 'Less than (<)' },
        { value: '~', label: 'Tilde (~)' },
        { value: '•', label: 'Bullet (•)' },
        { value: '—', label: 'Em dash (—)' },
    ];

    return (
        <div className="settings-layout">
            <div className="settings-main">
                <section className="panel">
                    <h3>Title Separator</h3>
                    <p className="panel-desc">Character used between title parts across your site (e.g., "Page Title | Site Name").</p>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label>Separator</label>
                            <p className="settings-help">Click to select your preferred separator.</p>
                        </div>
                        <div className="settings-control">
                            <div className="separator-picker">
                                {separators.map(sep => (
                                    <button
                                        key={sep.value}
                                        type="button"
                                        className={`separator-option ${settings.separator === sep.value ? 'active' : ''}`}
                                        onClick={() => updateSetting('separator', sep.value)}
                                        title={sep.label}
                                    >
                                        {sep.value}
                                    </button>
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="settings-info-box">
                        <strong>Site Name &amp; Tagline</strong>
                        <p>These are managed in WordPress Settings. <a href="options-general.php">Edit in Settings &rarr; General</a></p>
                    </div>
                </section>

                <section className="panel">
                    <h3>Knowledge Graph</h3>
                    <p className="panel-desc">Tell search engines who you are with structured data.</p>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label>Site Represents</label>
                            <p className="settings-help">Is this site for a person or organization?</p>
                        </div>
                        <div className="settings-control">
                            <div className="radio-group">
                                <label className="radio-item">
                                    <input type="radio" name="kg_type" checked={settings.knowledge_graph_type === 'organization'} onChange={() => updateSetting('knowledge_graph_type', 'organization')} />
                                    <span>Organization</span>
                                </label>
                                <label className="radio-item">
                                    <input type="radio" name="kg_type" checked={settings.knowledge_graph_type === 'person'} onChange={() => updateSetting('knowledge_graph_type', 'person')} />
                                    <span>Person</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {settings.knowledge_graph_type === 'organization' ? (
                        <>
                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="org-name">Organization Name</label>
                                </div>
                                <div className="settings-control">
                                    <input id="org-name" type="text" value={settings.organization_name} onChange={(e) => updateSetting('organization_name', e.target.value)} placeholder="Acme Corporation" />
                                </div>
                            </div>
                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="org-logo">Logo URL</label>
                                    <p className="settings-help">Recommended: 112x112px minimum.</p>
                                </div>
                                <div className="settings-control">
                                    <input id="org-logo" type="url" value={settings.organization_logo} onChange={(e) => updateSetting('organization_logo', e.target.value)} placeholder="https://example.com/logo.png" />
                                </div>
                            </div>
                        </>
                    ) : (
                        <div className="settings-row">
                            <div className="settings-label">
                                <label htmlFor="person-name">Person Name</label>
                            </div>
                            <div className="settings-control">
                                <input id="person-name" type="text" value={settings.person_name} onChange={(e) => updateSetting('person_name', e.target.value)} placeholder="John Doe" />
                            </div>
                        </div>
                    )}
                </section>

                <section className="panel">
                    <h3>Webmaster Tools Verification</h3>
                    <p className="panel-desc">Verify your site with search engines and services.</p>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="google-verify">Google Search Console</label>
                            <p className="settings-help">Meta tag content value.</p>
                        </div>
                        <div className="settings-control">
                            <input id="google-verify" type="text" value={settings.google_verification} onChange={(e) => updateSetting('google_verification', e.target.value)} placeholder="abc123..." />
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="bing-verify">Bing Webmaster Tools</label>
                        </div>
                        <div className="settings-control">
                            <input id="bing-verify" type="text" value={settings.bing_verification} onChange={(e) => updateSetting('bing_verification', e.target.value)} placeholder="abc123..." />
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="pinterest-verify">Pinterest</label>
                        </div>
                        <div className="settings-control">
                            <input id="pinterest-verify" type="text" value={settings.pinterest_verification} onChange={(e) => updateSetting('pinterest_verification', e.target.value)} placeholder="abc123..." />
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="yandex-verify">Yandex</label>
                        </div>
                        <div className="settings-control">
                            <input id="yandex-verify" type="text" value={settings.yandex_verification} onChange={(e) => updateSetting('yandex_verification', e.target.value)} placeholder="abc123..." />
                        </div>
                    </div>
                </section>
            </div>

            <aside className="settings-sidebar">
                <div className="side-card highlight">
                    <h4>Title Preview</h4>
                    <div className="title-preview">
                        <span className="title-preview__text">
                            Page Title {settings.separator} Site Name
                        </span>
                    </div>
                    <p className="muted" style={{ marginTop: '8px', fontSize: '12px' }}>
                        This is how titles will be structured across your site.
                    </p>
                </div>
            </aside>
        </div>
    );
};

// Modules Tab
const ModulesTab = ({ settings, updateSetting }) => {
    const modules = [
        { key: 'module_sitemap', name: 'XML Sitemap', desc: 'Generate and manage XML sitemaps for search engines.', icon: '🗺️' },
        { key: 'module_redirects', name: 'Redirects', desc: 'Create and manage URL redirects (301, 302, 307).', icon: '↪️' },
        { key: 'module_404_log', name: '404 Error Log', desc: 'Track and monitor 404 errors on your site.', icon: '🚫' },
        { key: 'module_internal_linking', name: 'Internal Linking', desc: 'Automatic internal link suggestions and management.', icon: '🔗' },
        { key: 'module_schema', name: 'Schema Markup', desc: 'Add structured data for rich search results.', icon: '📊' },
        { key: 'module_social_cards', name: 'Social Cards', desc: 'Dynamic Open Graph and Twitter Card generation.', icon: '🃏' },
        { key: 'module_breadcrumbs', name: 'Breadcrumbs', desc: 'SEO-friendly breadcrumb navigation.', icon: '🥖' },
        { key: 'module_llm_txt', name: 'LLM.txt', desc: 'Generate llm.txt file for AI crawlers and LLMs.', icon: '🤖' },
        { key: 'module_local_seo', name: 'Local SEO', desc: 'Local business schema and location pages.', icon: '📍' },
        { key: 'module_ai_assistant', name: 'AI Assistant', desc: 'AI-powered content optimization suggestions.', icon: '✨' },
        { key: 'module_indexnow', name: 'IndexNow', desc: 'Instant URL submission to search engines (Bing, Yandex).', icon: '⚡' },
        { key: 'module_search_console', name: 'Search Console', desc: 'Google Search Console integration.', icon: '🔍' },
    ];

    const enabledCount = modules.filter(m => settings[m.key]).length;

    // Handle module toggle with analytics tracking
    const handleModuleToggle = (key, enabled) => {
        updateSetting(key, enabled);
        analytics.settings.moduleToggle(key.replace('module_', ''), enabled);
    };

    // Handle analytics toggle separately (it's special)
    const handleAnalyticsToggle = (enabled) => {
        updateSetting('module_analytics', enabled);
        // Note: Can't track disabling analytics since it would disable itself
        if (enabled) {
            analytics.settings.moduleToggle('analytics', true);
        }
    };

    return (
        <div className="settings-layout">
            <div className="settings-main">
                {/* Analytics Notice - Always at top */}
                <AnalyticsNotice
                    isEnabled={settings.module_analytics}
                    onToggle={handleAnalyticsToggle}
                />

                <section className="panel">
                    <div className="panel-header">
                        <div>
                            <h3>Feature Modules</h3>
                            <p className="panel-desc">Enable or disable plugin features. Disabled modules are completely unloaded for performance.</p>
                        </div>
                        <span className="module-count">{enabledCount} of {modules.length} enabled</span>
                    </div>

                    <div className="modules-grid">
                        {modules.map(module => (
                            <div key={module.key} className={`module-card ${settings[module.key] ? 'active' : ''}`}>
                                <div className="module-card__icon">{module.icon}</div>
                                <div className="module-card__content">
                                    <h4>{module.name}</h4>
                                    <p>{module.desc}</p>
                                </div>
                                <label className="toggle">
                                    <input
                                        type="checkbox"
                                        checked={settings[module.key]}
                                        onChange={(e) => handleModuleToggle(module.key, e.target.checked)}
                                    />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        ))}
                    </div>
                </section>
            </div>

            <aside className="settings-sidebar">
                <div className="side-card">
                    <h4>Performance Tip</h4>
                    <p className="muted">Disable modules you don't use to reduce database queries and improve page load times.</p>
                </div>
                <div className="side-card warning">
                    <h4>Dependencies</h4>
                    <p className="muted">Some modules require others. For example, "404 Error Log" works best with "Redirects" enabled.</p>
                </div>
            </aside>
        </div>
    );
};

// Breadcrumbs Tab
const BreadcrumbsTab = ({ settings, updateSetting }) => {
    const separatorOptions = [
        { value: '>', label: 'Angle bracket (>)' },
        { value: '/', label: 'Slash (/)' },
        { value: '|', label: 'Pipe (|)' },
        { value: '-', label: 'Dash (-)' },
        { value: 'arrow', label: 'Arrow (→)' },
        { value: 'chevron', label: 'Chevron (›)' },
        { value: 'custom', label: 'Custom' },
    ];

    const stylePresets = [
        { value: 'default', label: 'Default', desc: 'Gray background with rounded corners' },
        { value: 'minimal', label: 'Minimal', desc: 'Clean text without background' },
        { value: 'rounded', label: 'Rounded', desc: 'Pill-shaped items' },
        { value: 'pills', label: 'Pills', desc: 'Arrow-shaped connected items' },
        { value: 'none', label: 'No Styling', desc: 'Unstyled for custom CSS' },
    ];

    const isEnabled = settings.module_breadcrumbs;

    return (
        <div className="settings-layout">
            <div className="settings-main">
                {/* Enable/Disable Section */}
                <section className="panel">
                    <div className="panel-header">
                        <div>
                            <h3>Breadcrumbs Navigation</h3>
                            <p className="panel-desc">Add SEO-friendly breadcrumb navigation to your pages.</p>
                        </div>
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={isEnabled}
                                onChange={(e) => updateSetting('module_breadcrumbs', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>

                    {!isEnabled && (
                        <div className="notice notice-info">
                            <p>Enable breadcrumbs to configure settings below.</p>
                        </div>
                    )}
                </section>

                {isEnabled && (
                    <>
                        {/* Display Settings */}
                        <section className="panel">
                            <h3>Display Settings</h3>
                            <p className="panel-desc">Configure how breadcrumbs appear on your site.</p>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-separator">Separator</label>
                                    <p className="settings-help">Character between breadcrumb items.</p>
                                </div>
                                <div className="settings-control">
                                    <select
                                        id="breadcrumb-separator"
                                        value={settings.breadcrumb_separator}
                                        onChange={(e) => updateSetting('breadcrumb_separator', e.target.value)}
                                    >
                                        {separatorOptions.map(opt => (
                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            {settings.breadcrumb_separator === 'custom' && (
                                <div className="settings-row">
                                    <div className="settings-label">
                                        <label htmlFor="breadcrumb-separator-custom">Custom Separator</label>
                                        <p className="settings-help">Enter your custom separator character or HTML.</p>
                                    </div>
                                    <div className="settings-control">
                                        <input
                                            id="breadcrumb-separator-custom"
                                            type="text"
                                            value={settings.breadcrumb_separator_custom}
                                            onChange={(e) => updateSetting('breadcrumb_separator_custom', e.target.value)}
                                            placeholder="e.g., •"
                                        />
                                    </div>
                                </div>
                            )}

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-style">Style Preset</label>
                                    <p className="settings-help">Visual style for breadcrumbs.</p>
                                </div>
                                <div className="settings-control">
                                    <select
                                        id="breadcrumb-style"
                                        value={settings.breadcrumb_style_preset}
                                        onChange={(e) => updateSetting('breadcrumb_style_preset', e.target.value)}
                                    >
                                        {stylePresets.map(opt => (
                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-truncate">Truncate Length</label>
                                    <p className="settings-help">Max characters per item. 0 = no truncation.</p>
                                </div>
                                <div className="settings-control">
                                    <input
                                        id="breadcrumb-truncate"
                                        type="number"
                                        min="0"
                                        max="100"
                                        value={settings.breadcrumb_truncate_length}
                                        onChange={(e) => updateSetting('breadcrumb_truncate_length', parseInt(e.target.value, 10) || 0)}
                                    />
                                </div>
                            </div>
                        </section>

                        {/* Home Link Settings */}
                        <section className="panel">
                            <h3>Home Link</h3>
                            <p className="panel-desc">Configure the home link in breadcrumbs.</p>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-show-home">Show Home Link</label>
                                    <p className="settings-help">Include a link to the homepage in breadcrumbs.</p>
                                </div>
                                <div className="settings-control">
                                    <label className="toggle">
                                        <input
                                            id="breadcrumb-show-home"
                                            type="checkbox"
                                            checked={settings.breadcrumb_show_home}
                                            onChange={(e) => updateSetting('breadcrumb_show_home', e.target.checked)}
                                        />
                                        <span className="toggle-track" />
                                    </label>
                                </div>
                            </div>

                            {settings.breadcrumb_show_home && (
                                <div className="settings-row">
                                    <div className="settings-label">
                                        <label htmlFor="breadcrumb-home-label">Home Label</label>
                                        <p className="settings-help">Text for the home link. Leave empty for "Home".</p>
                                    </div>
                                    <div className="settings-control">
                                        <input
                                            id="breadcrumb-home-label"
                                            type="text"
                                            value={settings.breadcrumb_home_label}
                                            onChange={(e) => updateSetting('breadcrumb_home_label', e.target.value)}
                                            placeholder="Home"
                                        />
                                    </div>
                                </div>
                            )}
                        </section>

                        {/* Current Page Settings */}
                        <section className="panel">
                            <h3>Current Page</h3>
                            <p className="panel-desc">Configure how the current page appears in breadcrumbs.</p>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-show-current">Show Current Page</label>
                                    <p className="settings-help">Display the current page title in breadcrumbs.</p>
                                </div>
                                <div className="settings-control">
                                    <label className="toggle">
                                        <input
                                            id="breadcrumb-show-current"
                                            type="checkbox"
                                            checked={settings.breadcrumb_show_current}
                                            onChange={(e) => updateSetting('breadcrumb_show_current', e.target.checked)}
                                        />
                                        <span className="toggle-track" />
                                    </label>
                                </div>
                            </div>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-link-current">Link Current Page</label>
                                    <p className="settings-help">Make the current page title a clickable link.</p>
                                </div>
                                <div className="settings-control">
                                    <label className="toggle">
                                        <input
                                            id="breadcrumb-link-current"
                                            type="checkbox"
                                            checked={settings.breadcrumb_link_current}
                                            onChange={(e) => updateSetting('breadcrumb_link_current', e.target.checked)}
                                        />
                                        <span className="toggle-track" />
                                    </label>
                                </div>
                            </div>

                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="breadcrumb-show-front">Show on Front Page</label>
                                    <p className="settings-help">Display breadcrumbs on the homepage.</p>
                                </div>
                                <div className="settings-control">
                                    <label className="toggle">
                                        <input
                                            id="breadcrumb-show-front"
                                            type="checkbox"
                                            checked={settings.breadcrumb_show_on_front}
                                            onChange={(e) => updateSetting('breadcrumb_show_on_front', e.target.checked)}
                                        />
                                        <span className="toggle-track" />
                                    </label>
                                </div>
                            </div>
                        </section>
                    </>
                )}
            </div>

            <aside className="settings-sidebar">
                <div className="side-card">
                    <h4>Usage</h4>
                    <p className="muted">Add breadcrumbs to your theme using:</p>
                    <code className="code-block">[Saman_seo_breadcrumbs]</code>
                    <p className="muted" style={{ marginTop: '8px' }}>Or in PHP:</p>
                    <code className="code-block">Saman_seo_breadcrumbs();</code>
                </div>

                <div className="side-card">
                    <h4>Gutenberg Block</h4>
                    <p className="muted">Search for "Saman SEO Breadcrumbs" in the block inserter to add breadcrumbs to any page.</p>
                </div>

                <div className="side-card success">
                    <h4>Schema Markup</h4>
                    <p className="muted">BreadcrumbList JSON-LD schema is automatically added when breadcrumbs are enabled.</p>
                </div>
            </aside>
        </div>
    );
};

// Social Tab
const SocialTab = ({ settings, updateSetting }) => {
    const sampleTitle = 'Your Page Title';
    const sampleDesc = 'Your page description will appear here when shared on social media platforms.';
    const sampleDomain = 'yoursite.com';

    return (
        <div className="settings-layout">
            <div className="settings-main">
                <section className="panel">
                    <h3>Open Graph Defaults</h3>
                    <p className="panel-desc">Default settings for Facebook and other social platforms.</p>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="default-og-image">Default Share Image</label>
                            <p className="settings-help">Used when no featured image is available. Recommended: 1200x630px.</p>
                        </div>
                        <div className="settings-control">
                            <input id="default-og-image" type="url" value={settings.default_og_image} onChange={(e) => updateSetting('default_og_image', e.target.value)} placeholder="https://example.com/share-image.jpg" />
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="fb-app-id">Facebook App ID</label>
                            <p className="settings-help">For Facebook Insights.</p>
                        </div>
                        <div className="settings-control">
                            <input id="fb-app-id" type="text" value={settings.facebook_app_id} onChange={(e) => updateSetting('facebook_app_id', e.target.value)} placeholder="123456789" />
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="fb-admin-id">Facebook Admin ID</label>
                        </div>
                        <div className="settings-control">
                            <input id="fb-admin-id" type="text" value={settings.facebook_admin_id} onChange={(e) => updateSetting('facebook_admin_id', e.target.value)} placeholder="123456789" />
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>Twitter/X Settings</h3>
                    <p className="panel-desc">Configure Twitter Card appearance.</p>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label>Card Type</label>
                            <p className="settings-help">How your content appears on Twitter.</p>
                        </div>
                        <div className="settings-control">
                            <div className="radio-group">
                                <label className="radio-item">
                                    <input type="radio" name="twitter_card" checked={settings.twitter_card_type === 'summary'} onChange={() => updateSetting('twitter_card_type', 'summary')} />
                                    <span>Summary</span>
                                </label>
                                <label className="radio-item">
                                    <input type="radio" name="twitter_card" checked={settings.twitter_card_type === 'summary_large_image'} onChange={() => updateSetting('twitter_card_type', 'summary_large_image')} />
                                    <span>Large Image</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div className="settings-row">
                        <div className="settings-label">
                            <label htmlFor="twitter-username">Twitter Username</label>
                            <p className="settings-help">Your @handle without the @.</p>
                        </div>
                        <div className="settings-control">
                            <div className="input-with-prefix">
                                <span className="input-prefix">@</span>
                                <input id="twitter-username" type="text" value={settings.twitter_username} onChange={(e) => updateSetting('twitter_username', e.target.value)} placeholder="username" />
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            <aside className="settings-sidebar">
                {/* Facebook Preview */}
                <div className="social-preview social-preview--facebook">
                    <div className="social-preview__header">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="#1877f2"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        <span>Facebook</span>
                    </div>
                    <div className="social-preview__card">
                        <div
                            className="social-preview__image"
                            style={{ backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none' }}
                        >
                            {!settings.default_og_image && (
                                <div className="social-preview__placeholder">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                                </div>
                            )}
                        </div>
                        <div className="social-preview__body">
                            <span className="social-preview__domain">{sampleDomain}</span>
                            <span className="social-preview__title">{sampleTitle}</span>
                            <span className="social-preview__desc">{sampleDesc}</span>
                        </div>
                    </div>
                </div>

                {/* Twitter Preview */}
                <div className={`social-preview social-preview--twitter ${settings.twitter_card_type === 'summary' ? 'social-preview--summary' : ''}`}>
                    <div className="social-preview__header">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="#000"><path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/></svg>
                        <span>X (Twitter)</span>
                    </div>
                    {settings.twitter_card_type === 'summary' ? (
                        <div className="social-preview__card social-preview__card--horizontal">
                            <div
                                className="social-preview__image social-preview__image--square"
                                style={{ backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none' }}
                            >
                                {!settings.default_og_image && (
                                    <div className="social-preview__placeholder">
                                        <svg width="32" height="32" viewBox="0 0 24 24" fill="currentColor" opacity="0.3"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                                    </div>
                                )}
                            </div>
                            <div className="social-preview__body">
                                <span className="social-preview__title">{sampleTitle}</span>
                                <span className="social-preview__desc">{sampleDesc}</span>
                                <span className="social-preview__domain">{sampleDomain}</span>
                            </div>
                        </div>
                    ) : (
                        <div className="social-preview__card">
                            <div
                                className="social-preview__image"
                                style={{ backgroundImage: settings.default_og_image ? `url(${settings.default_og_image})` : 'none' }}
                            >
                                {!settings.default_og_image && (
                                    <div className="social-preview__placeholder">
                                        <svg width="48" height="48" viewBox="0 0 24 24" fill="currentColor" opacity="0.3"><path d="M21 19V5c0-1.1-.9-2-2-2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2zM8.5 13.5l2.5 3.01L14.5 12l4.5 6H5l3.5-4.5z"/></svg>
                                    </div>
                                )}
                                <span className="social-preview__domain-overlay">{sampleDomain}</span>
                            </div>
                            <div className="social-preview__body">
                                <span className="social-preview__title">{sampleTitle}</span>
                                <span className="social-preview__desc">{sampleDesc}</span>
                            </div>
                        </div>
                    )}
                </div>
            </aside>
        </div>
    );
};

// Advanced Tab
const AdvancedTab = ({ settings, updateSetting }) => {
    return (
        <div className="settings-layout">
            <div className="settings-main">
                <section className="panel">
                    <h3>User Interface</h3>
                    <p className="panel-desc">Customize how Saman SEO appears in your WordPress admin.</p>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Admin Bar Menu</label>
                            <p className="settings-help">Show SEO Pilot menu in the WordPress admin bar with quick access to features and SEO score on posts/pages.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_admin_bar} onChange={(e) => updateSetting('enable_admin_bar', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>WordPress Head Cleanup</h3>
                    <p className="panel-desc">Remove unnecessary tags from your site's &lt;head&gt; section.</p>

                    <div className="settings-grid">
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Remove Shortlinks</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.remove_shortlinks} onChange={(e) => updateSetting('remove_shortlinks', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Remove RSD Link</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.remove_rsd_link} onChange={(e) => updateSetting('remove_rsd_link', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Remove WLW Manifest</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.remove_wlwmanifest} onChange={(e) => updateSetting('remove_wlwmanifest', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Remove WP Generator</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.remove_wp_generator} onChange={(e) => updateSetting('remove_wp_generator', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Remove Feed Links</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.remove_feed_links} onChange={(e) => updateSetting('remove_feed_links', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Disable Emoji Scripts</label>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.disable_emoji} onChange={(e) => updateSetting('disable_emoji', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>Content Analysis</h3>
                    <p className="panel-desc">Features for content optimization in the editor.</p>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Link Suggestions</label>
                            <p className="settings-help">Show internal link suggestions while editing.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_link_suggestions} onChange={(e) => updateSetting('enable_link_suggestions', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Internal Link Counter</label>
                            <p className="settings-help">Show count of internal links in post list.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_internal_link_count} onChange={(e) => updateSetting('enable_internal_link_count', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Cornerstone Content</label>
                            <p className="settings-help">Enable cornerstone content marking.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_cornerstone_content} onChange={(e) => updateSetting('enable_cornerstone_content', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>Performance</h3>
                    <p className="panel-desc">Optimize plugin performance.</p>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Cache Schema Output</label>
                            <p className="settings-help">Cache generated schema markup.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.cache_schema} onChange={(e) => updateSetting('cache_schema', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Minify Schema</label>
                            <p className="settings-help">Minify JSON-LD output.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.minify_schema_output} onChange={(e) => updateSetting('minify_schema_output', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Purge Cache on Save</label>
                            <p className="settings-help">Clear caches when posts are updated.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.purge_on_save} onChange={(e) => updateSetting('purge_on_save', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>
                </section>

                {settings.module_indexnow && (
                    <section className="panel">
                        <h3>IndexNow Settings</h3>
                        <p className="panel-desc">Configure instant URL submission to search engines.</p>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Submit on Publish</label>
                                <p className="settings-help">Automatically submit URLs when new content is published.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.indexnow_submit_on_publish} onChange={(e) => updateSetting('indexnow_submit_on_publish', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Submit on Update</label>
                                <p className="settings-help">Automatically submit URLs when existing content is updated.</p>
                            </div>
                            <div className="settings-control">
                                <label className="toggle">
                                    <input type="checkbox" checked={settings.indexnow_submit_on_update} onChange={(e) => updateSetting('indexnow_submit_on_update', e.target.checked)} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="settings-info">
                            <p className="muted">IndexNow instantly notifies Bing, Yandex, Seznam, and Naver when your content changes. An API key is automatically generated when you enable the module.</p>
                        </div>
                    </section>
                )}

                <section className="panel panel--deprecated">
                    <h3>API Keys <span className="deprecated-badge">Deprecated</span></h3>
                    <p className="panel-desc">API key management has moved to WP AI Pilot for centralized AI configuration.</p>

                    <div className="deprecation-notice deprecation-notice--block">
                        <div className="deprecation-notice__icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                        </div>
                        <div className="deprecation-notice__content">
                            <h4>AI Features Now Powered by WP AI Pilot</h4>
                            <p>Configure your OpenAI, Anthropic, Google AI, and other API keys in WP AI Pilot. This provides unified AI management across all your WordPress plugins that support it.</p>
                            <div className="deprecation-notice__actions">
                                <a href="admin.php?page=wp-ai-pilot" className="button primary">
                                    Open WP AI Pilot
                                </a>
                                <a href="plugin-install.php?s=wp+ai+pilot&tab=search" className="button ghost">
                                    Install WP AI Pilot
                                </a>
                            </div>
                        </div>
                    </div>

                    <details className="legacy-settings-toggle">
                        <summary>Show legacy API key fields (for reference only)</summary>
                        <div className="legacy-settings-content">
                            <p className="muted">These fields are read-only. Use WP AI Pilot to manage API keys.</p>
                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="openai-key">OpenAI API Key</label>
                                </div>
                                <div className="settings-control">
                                    <input id="openai-key" type="password" value={settings.openai_api_key ? '••••••••••••' : ''} disabled placeholder="Configured in WP AI Pilot" />
                                </div>
                            </div>
                            <div className="settings-row">
                                <div className="settings-label">
                                    <label htmlFor="google-key">Google API Key</label>
                                </div>
                                <div className="settings-control">
                                    <input id="google-key" type="password" value={settings.google_api_key ? '••••••••••••' : ''} disabled placeholder="Configured in WP AI Pilot" />
                                </div>
                            </div>
                        </div>
                    </details>
                </section>

                <section className="panel">
                    <h3>404 Monitor</h3>
                    <p className="panel-desc">Configure 404 error logging and cleanup options.</p>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Dashboard Widget</label>
                            <p className="settings-help">Show 404 summary widget on the WordPress dashboard.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.show_404_dashboard_widget !== false} onChange={(e) => updateSetting('show_404_dashboard_widget', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Auto-Cleanup</label>
                            <p className="settings-help">Automatically delete old 404 entries to keep the database clean.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_404_cleanup} onChange={(e) => updateSetting('enable_404_cleanup', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    {settings.enable_404_cleanup && (
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Cleanup Age (days)</label>
                                <p className="settings-help">Delete 404 entries older than this many days.</p>
                            </div>
                            <div className="settings-control">
                                <input
                                    type="number"
                                    min="1"
                                    max="365"
                                    value={settings.cleanup_404_days || 30}
                                    onChange={(e) => updateSetting('cleanup_404_days', parseInt(e.target.value, 10) || 30)}
                                    style={{ width: '80px' }}
                                />
                            </div>
                        </div>
                    )}

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Email Notifications</label>
                            <p className="settings-help">Send email alerts when a URL hits the notification threshold.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_404_notifications} onChange={(e) => updateSetting('enable_404_notifications', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    {settings.enable_404_notifications && (
                        <>
                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Hit Threshold</label>
                                    <p className="settings-help">Send notification when a 404 URL reaches this many hits.</p>
                                </div>
                                <div className="settings-control">
                                    <input
                                        type="number"
                                        min="1"
                                        max="1000"
                                        value={settings.notification_404_threshold || 10}
                                        onChange={(e) => updateSetting('notification_404_threshold', parseInt(e.target.value, 10) || 10)}
                                        style={{ width: '80px' }}
                                    />
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Notification Email</label>
                                    <p className="settings-help">Leave empty to use admin email.</p>
                                </div>
                                <div className="settings-control">
                                    <input
                                        type="email"
                                        value={settings.notification_404_email || ''}
                                        onChange={(e) => updateSetting('notification_404_email', e.target.value)}
                                        placeholder="admin@example.com"
                                        style={{ width: '220px' }}
                                    />
                                </div>
                            </div>
                        </>
                    )}
                </section>

                <section className="panel">
                    <h3>Developer</h3>
                    <p className="panel-desc">Options for developers and debugging.</p>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Enable REST API</label>
                            <p className="settings-help">Allow external access to SEO data via REST.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.enable_rest_api} onChange={(e) => updateSetting('enable_rest_api', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>

                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label>Debug Mode</label>
                            <p className="settings-help">Enable verbose logging and debug output.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input type="checkbox" checked={settings.debug_mode} onChange={(e) => updateSetting('debug_mode', e.target.checked)} />
                                <span className="toggle-track" />
                            </label>
                        </div>
                    </div>
                </section>
            </div>

            <aside className="settings-sidebar">
                <div className="side-card warning">
                    <h4>Caution</h4>
                    <p className="muted">Changes to advanced settings may affect site functionality. Make sure you understand what each option does.</p>
                </div>
            </aside>
        </div>
    );
};

// Tools Tab
const ToolsTab = ({ settings, onExport, onImport, onReset, onResetWizard, resettingWizard, importFile, setImportFile }) => {
    return (
        <div className="settings-layout">
            <div className="settings-main">
                <section className="panel">
                    <h3>Import / Export</h3>
                    <p className="panel-desc">Backup your settings or transfer them to another site.</p>

                    <div className="tools-actions">
                        <div className="tool-action">
                            <h4>Export Settings</h4>
                            <p className="muted">Download all plugin settings as a JSON file.</p>
                            <button type="button" className="button primary" onClick={onExport}>
                                Export Settings
                            </button>
                        </div>

                        <div className="tool-action">
                            <h4>Import Settings</h4>
                            <p className="muted">Upload a previously exported JSON file.</p>
                            <div className="import-controls">
                                <input
                                    type="file"
                                    accept=".json"
                                    onChange={(e) => setImportFile(e.target.files[0])}
                                    id="import-file"
                                />
                                <label htmlFor="import-file" className="button ghost">
                                    {importFile ? importFile.name : 'Choose File'}
                                </label>
                                <button type="button" className="button" onClick={onImport} disabled={!importFile}>
                                    Import
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>Database Tools</h3>
                    <p className="panel-desc">Manage plugin data stored in your database.</p>

                    <div className="tools-actions">
                        <div className="tool-action">
                            <h4>Clear Cache</h4>
                            <p className="muted">Clear all cached SEO data (schema, sitemaps, etc).</p>
                            <button type="button" className="button ghost">Clear Cache</button>
                        </div>

                        <div className="tool-action">
                            <h4>Reindex Content</h4>
                            <p className="muted">Rebuild internal link index and content analysis.</p>
                            <button type="button" className="button ghost">Reindex</button>
                        </div>
                    </div>
                </section>

                <section className="panel">
                    <h3>Setup Wizard</h3>
                    <p className="panel-desc">Run the setup wizard again to reconfigure the plugin.</p>

                    <div className="tools-actions">
                        <div className="tool-action">
                            <h4>Reset Setup Wizard</h4>
                            <p className="muted">Show the setup wizard on next page load. Existing settings will be preserved.</p>
                            <button
                                type="button"
                                className="button ghost"
                                onClick={onResetWizard}
                                disabled={resettingWizard}
                            >
                                {resettingWizard ? 'Resetting...' : 'Reset Wizard'}
                            </button>
                        </div>
                    </div>
                </section>

                <section className="panel danger-zone">
                    <h3>Danger Zone</h3>
                    <p className="panel-desc">Destructive actions that cannot be undone.</p>

                    <div className="tools-actions">
                        <div className="tool-action">
                            <h4>Reset to Defaults</h4>
                            <p className="muted">Reset all settings to their default values.</p>
                            <button type="button" className="button danger" onClick={onReset}>Reset All Settings</button>
                        </div>

                        <div className="tool-action">
                            <h4>Delete All Data</h4>
                            <p className="muted">Remove all plugin data including redirects, 404 logs, and meta.</p>
                            <button type="button" className="button danger">Delete All Data</button>
                        </div>
                    </div>
                </section>
            </div>

            <aside className="settings-sidebar">
                <div className="side-card highlight">
                    <h4>Plugin Info</h4>
                    <div className="info-rows">
                        <div className="info-row">
                            <span>Version</span>
                            <code>0.2.0</code>
                        </div>
                        <div className="info-row">
                            <span>Interface</span>
                            <code>React SPA</code>
                        </div>
                        <div className="info-row">
                            <span>PHP</span>
                            <code>8.1</code>
                        </div>
                        <div className="info-row">
                            <span>WordPress</span>
                            <code>6.4</code>
                        </div>
                    </div>
                </div>

                <div className="side-card">
                    <h4>Need Help?</h4>
                    <p className="muted">Check the documentation or contact support.</p>
                    <a href="#" className="button ghost">View Documentation</a>
                </div>

                <div className="side-card">
                    <h4>Legacy Interface</h4>
                    <p className="muted">Access V1 for features not yet migrated.</p>
                    <a href="admin.php?page=saman-seo" className="button ghost">Open V1</a>
                </div>
            </aside>
        </div>
    );
};

export default Settings;
