import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const sitemapTabs = [
    { id: 'xml-sitemap', label: 'XML Sitemap' },
    { id: 'llm-txt', label: 'LLM.txt' },
];

const SCHEDULE_OPTIONS = [
    { value: '', label: 'Manual only' },
    { value: 'hourly', label: 'Hourly' },
    { value: 'twicedaily', label: 'Twice Daily' },
    { value: 'daily', label: 'Daily' },
    { value: 'weekly', label: 'Weekly' },
];

const Sitemap = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: sitemapTabs, defaultTab: 'xml-sitemap' });

    // Loading states
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [regenerating, setRegenerating] = useState(false);

    // Sitemap settings
    const [settings, setSettings] = useState({
        max_urls: 1000,
        enable_index: '1',
        dynamic_generation: '1',
        schedule_updates: '',
        post_types: [],
        taxonomies: [],
        include_author_pages: '0',
        include_date_archives: '0',
        exclude_images: '0',
        enable_rss: '0',
        enable_google_news: '0',
        google_news_name: '',
        google_news_post_types: [],
        additional_pages: [],
        site_url: '',
        sitemap_url: '',
        rss_sitemap_url: '',
        news_sitemap_url: '',
    });

    // LLM settings
    const [llmSettings, setLlmSettings] = useState({
        enable_llm_txt: '0',
        llm_txt_title: '',
        llm_txt_description: '',
        llm_txt_posts_per_type: 50,
        llm_txt_include_excerpt: '1',
        llm_txt_url: '',
    });

    // Available post types and taxonomies
    const [postTypes, setPostTypes] = useState([]);
    const [taxonomies, setTaxonomies] = useState([]);

    // Stats
    const [stats, setStats] = useState({
        total_urls: 0,
        last_regenerated: null,
    });

    // Fetch all data
    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const [settingsRes, llmRes, postTypesRes, taxonomiesRes, statsRes] = await Promise.all([
                apiFetch({ path: '/saman-seo/v1/sitemap/settings' }),
                apiFetch({ path: '/saman-seo/v1/sitemap/llm-settings' }),
                apiFetch({ path: '/saman-seo/v1/sitemap/post-types' }),
                apiFetch({ path: '/saman-seo/v1/sitemap/taxonomies' }),
                apiFetch({ path: '/saman-seo/v1/sitemap/stats' }),
            ]);

            if (settingsRes.success) setSettings(settingsRes.data);
            if (llmRes.success) setLlmSettings(llmRes.data);
            if (postTypesRes.success) setPostTypes(postTypesRes.data);
            if (taxonomiesRes.success) setTaxonomies(taxonomiesRes.data);
            if (statsRes.success) setStats(statsRes.data);
        } catch (error) {
            console.error('Failed to fetch sitemap data:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Save sitemap settings
    const handleSaveSettings = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/sitemap/settings',
                method: 'POST',
                data: settings,
            });
            // Refetch stats after saving
            const statsRes = await apiFetch({ path: '/saman-seo/v1/sitemap/stats' });
            if (statsRes.success) setStats(statsRes.data);
        } catch (error) {
            console.error('Failed to save settings:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save LLM settings
    const handleSaveLlmSettings = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/sitemap/llm-settings',
                method: 'POST',
                data: llmSettings,
            });
        } catch (error) {
            console.error('Failed to save LLM settings:', error);
        } finally {
            setSaving(false);
        }
    };

    // Regenerate sitemap
    const handleRegenerate = async () => {
        setRegenerating(true);
        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/sitemap/regenerate',
                method: 'POST',
            });
            if (res.success) {
                setStats(prev => ({
                    ...prev,
                    last_regenerated: res.data.regenerated_at,
                }));
            }
        } catch (error) {
            console.error('Failed to regenerate sitemap:', error);
        } finally {
            setRegenerating(false);
        }
    };

    // Toggle post type selection
    const togglePostType = (name) => {
        setSettings(prev => {
            const current = Array.isArray(prev.post_types) ? prev.post_types : [];
            const updated = current.includes(name)
                ? current.filter(pt => pt !== name)
                : [...current, name];
            return { ...prev, post_types: updated };
        });
    };

    // Toggle taxonomy selection
    const toggleTaxonomy = (name) => {
        setSettings(prev => {
            const current = Array.isArray(prev.taxonomies) ? prev.taxonomies : [];
            const updated = current.includes(name)
                ? current.filter(t => t !== name)
                : [...current, name];
            return { ...prev, taxonomies: updated };
        });
    };

    // Toggle Google News post type
    const toggleNewsPostType = (name) => {
        setSettings(prev => {
            const current = Array.isArray(prev.google_news_post_types) ? prev.google_news_post_types : [];
            const updated = current.includes(name)
                ? current.filter(pt => pt !== name)
                : [...current, name];
            return { ...prev, google_news_post_types: updated };
        });
    };

    // Add additional page
    const addAdditionalPage = () => {
        setSettings(prev => ({
            ...prev,
            additional_pages: [...(prev.additional_pages || []), { url: '', priority: '0.5' }],
        }));
    };

    // Update additional page
    const updateAdditionalPage = (index, field, value) => {
        setSettings(prev => {
            const pages = [...(prev.additional_pages || [])];
            pages[index] = { ...pages[index], [field]: value };
            return { ...prev, additional_pages: pages };
        });
    };

    // Remove additional page
    const removeAdditionalPage = (index) => {
        setSettings(prev => ({
            ...prev,
            additional_pages: (prev.additional_pages || []).filter((_, i) => i !== index),
        }));
    };

    // Format date
    const formatDate = (dateStr) => {
        if (!dateStr) return 'Never';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        const hours = Math.floor(diff / (1000 * 60 * 60));

        if (hours < 1) return 'Just now';
        if (hours < 24) return `${hours} hour${hours > 1 ? 's' : ''} ago`;
        return date.toLocaleDateString();
    };

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>Sitemap</h1>
                        <p>Configure XML sitemap generation and LLM.txt settings.</p>
                    </div>
                </div>
                <div className="loading-state">Loading sitemap settings...</div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Sitemap</h1>
                    <p>Configure XML sitemap generation and LLM.txt settings.</p>
                </div>
                <div className="header-actions">
                    <a
                        href={settings.sitemap_url}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="button ghost"
                    >
                        View Sitemap
                    </a>
                    {llmSettings.enable_llm_txt === '1' && (
                        <a
                            href={llmSettings.llm_txt_url}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="button ghost"
                        >
                            Open llm.txt
                        </a>
                    )}
                </div>
            </div>

            <SubTabs tabs={sitemapTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Sitemap sections" />

            {activeTab === 'xml-sitemap' ? (
                <div className="page-body two-column">
                    <div className="main-column">
                        {/* XML Sitemap Configuration - Single Panel */}
                        <section className="panel">
                            <h3>XML Sitemap Settings</h3>
                            <p className="muted">Configure your sitemap generation, content types, and additional options.</p>

                            <div className="settings-form">
                                {/* General Settings */}
                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Automatic Updates</label>
                                        <p className="settings-help">Regenerate sitemap on a schedule.</p>
                                    </div>
                                    <div className="settings-control">
                                        <select
                                            value={settings.schedule_updates}
                                            onChange={(e) => setSettings(prev => ({ ...prev, schedule_updates: e.target.value }))}
                                        >
                                            {SCHEDULE_OPTIONS.map(opt => (
                                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Max URLs Per Page</label>
                                        <p className="settings-help">Maximum URLs per sitemap page.</p>
                                    </div>
                                    <div className="settings-control">
                                        <input
                                            type="number"
                                            value={settings.max_urls}
                                            onChange={(e) => setSettings(prev => ({ ...prev, max_urls: parseInt(e.target.value, 10) || 1000 }))}
                                            min="1"
                                            max="50000"
                                            style={{ width: '120px' }}
                                        />
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Generation Options</label>
                                    </div>
                                    <div className="settings-control">
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.enable_index === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, enable_index: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Enable sitemap indexes</span>
                                        </label>
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.dynamic_generation === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, dynamic_generation: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Dynamic generation on-demand</span>
                                        </label>
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.exclude_images === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, exclude_images: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Exclude images from entries</span>
                                        </label>
                                    </div>
                                </div>

                                {/* Content Types */}
                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Post Types</label>
                                        <p className="settings-help">Include in sitemap.</p>
                                    </div>
                                    <div className="settings-control">
                                        <div className="checkbox-grid">
                                            {postTypes.map(pt => (
                                                <label key={pt.name} className="checkbox-row">
                                                    <input
                                                        type="checkbox"
                                                        checked={(settings.post_types || []).includes(pt.name)}
                                                        onChange={() => togglePostType(pt.name)}
                                                    />
                                                    <span>{pt.label} ({pt.count})</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Taxonomies</label>
                                        <p className="settings-help">Include taxonomy archives.</p>
                                    </div>
                                    <div className="settings-control">
                                        <div className="checkbox-grid">
                                            {taxonomies.map(tax => (
                                                <label key={tax.name} className="checkbox-row">
                                                    <input
                                                        type="checkbox"
                                                        checked={(settings.taxonomies || []).includes(tax.name)}
                                                        onChange={() => toggleTaxonomy(tax.name)}
                                                    />
                                                    <span>{tax.label} ({tax.count})</span>
                                                </label>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Archive Pages</label>
                                    </div>
                                    <div className="settings-control">
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.include_author_pages === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, include_author_pages: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Include author pages</span>
                                        </label>
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.include_date_archives === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, include_date_archives: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Include date archives</span>
                                        </label>
                                    </div>
                                </div>

                                {/* Additional Sitemaps */}
                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>RSS Sitemap</label>
                                    </div>
                                    <div className="settings-control">
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.enable_rss === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, enable_rss: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Generate RSS sitemap (latest 50 posts)</span>
                                        </label>
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Google News</label>
                                    </div>
                                    <div className="settings-control">
                                        <label className="checkbox-row">
                                            <input
                                                type="checkbox"
                                                checked={settings.enable_google_news === '1'}
                                                onChange={(e) => setSettings(prev => ({ ...prev, enable_google_news: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span>Enable Google News sitemap</span>
                                        </label>

                                        {settings.enable_google_news === '1' && (
                                            <div style={{ marginTop: '12px' }}>
                                                <input
                                                    type="text"
                                                    value={settings.google_news_name}
                                                    onChange={(e) => setSettings(prev => ({ ...prev, google_news_name: e.target.value }))}
                                                    placeholder="Publication Name"
                                                    style={{ width: '100%', marginBottom: '8px' }}
                                                />
                                                <div className="checkbox-grid">
                                                    {postTypes.map(pt => (
                                                        <label key={pt.name} className="checkbox-row">
                                                            <input
                                                                type="checkbox"
                                                                checked={(settings.google_news_post_types || []).includes(pt.name)}
                                                                onChange={() => toggleNewsPostType(pt.name)}
                                                            />
                                                            <span>{pt.label}</span>
                                                        </label>
                                                    ))}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Additional Pages */}
                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Custom Pages</label>
                                        <p className="settings-help">Add URLs not managed by WordPress.</p>
                                    </div>
                                    <div className="settings-control">
                                        <div className="additional-pages-list">
                                            {(settings.additional_pages || []).map((page, index) => (
                                                <div key={index} className="additional-page-row">
                                                    <input
                                                        type="url"
                                                        value={page.url}
                                                        onChange={(e) => updateAdditionalPage(index, 'url', e.target.value)}
                                                        placeholder="https://example.com/page"
                                                        className="page-url-input"
                                                    />
                                                    <input
                                                        type="text"
                                                        value={page.priority}
                                                        onChange={(e) => updateAdditionalPage(index, 'priority', e.target.value)}
                                                        placeholder="0.5"
                                                        className="page-priority-input"
                                                    />
                                                    <button
                                                        type="button"
                                                        className="button ghost small"
                                                        onClick={() => removeAdditionalPage(index)}
                                                    >
                                                        Remove
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                        <button type="button" className="button ghost small" onClick={addAdditionalPage}>
                                            + Add Page
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {/* Save Button inside panel */}
                            <div className="form-actions" style={{ marginTop: '24px', paddingTop: '24px', borderTop: '1px solid #e5e7eb' }}>
                                <button
                                    type="button"
                                    className="button primary"
                                    onClick={handleSaveSettings}
                                    disabled={saving}
                                >
                                    {saving ? 'Saving...' : 'Save Changes'}
                                </button>
                                <button
                                    type="button"
                                    className="button ghost"
                                    onClick={handleRegenerate}
                                    disabled={regenerating}
                                >
                                    {regenerating ? 'Regenerating...' : 'Regenerate Now'}
                                </button>
                            </div>
                        </section>
                    </div>

                    {/* Sidebar */}
                    <aside className="side-panel">
                        <div className="side-card highlight">
                            <h3>Your Sitemaps</h3>
                            <div className="sitemap-links">
                                <div className="sitemap-link-item">
                                    <strong>Main Index</strong>
                                    <a href={settings.sitemap_url} target="_blank" rel="noopener noreferrer">
                                        {settings.sitemap_url}
                                    </a>
                                </div>
                                {settings.enable_rss === '1' && (
                                    <div className="sitemap-link-item">
                                        <strong>RSS Feed</strong>
                                        <a href={settings.rss_sitemap_url} target="_blank" rel="noopener noreferrer">
                                            {settings.rss_sitemap_url}
                                        </a>
                                    </div>
                                )}
                                {settings.enable_google_news === '1' && (
                                    <div className="sitemap-link-item">
                                        <strong>Google News</strong>
                                        <a href={settings.news_sitemap_url} target="_blank" rel="noopener noreferrer">
                                            {settings.news_sitemap_url}
                                        </a>
                                    </div>
                                )}
                            </div>
                        </div>

                        <div className="side-card">
                            <h3>Statistics</h3>
                            <div className="stats-list">
                                <div className="stat-item">
                                    <span className="muted">Total URLs</span>
                                    <span className="stat-value">{stats.total_urls.toLocaleString()}</span>
                                </div>
                                <div className="stat-item">
                                    <span className="muted">Last Updated</span>
                                    <span className="stat-value">{formatDate(stats.last_regenerated)}</span>
                                </div>
                            </div>
                        </div>

                        <div className="side-card">
                            <h3>Pro Tip</h3>
                            <p className="muted">
                                Submit your sitemap to Google Search Console and Bing Webmaster Tools for faster indexing.
                            </p>
                        </div>
                    </aside>
                </div>
            ) : (
                <div className="page-body two-column">
                    <div className="main-column">
                        {/* LLM.txt - Single Panel */}
                        <section className="panel">
                            <h3>LLM.txt Configuration</h3>
                            <p className="muted">
                                Help AI engines discover your content. Similar to XML sitemaps for search engines,
                                llm.txt guides AI crawlers like ChatGPT and Claude.{' '}
                                <a href="https://llmstxt.org/" target="_blank" rel="noopener noreferrer">Learn more</a>
                            </p>

                            <div className="settings-form">
                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Enable llm.txt</label>
                                        <p className="settings-help">Generate and serve the llm.txt file.</p>
                                    </div>
                                    <div className="settings-control">
                                        <label className="toggle">
                                            <input
                                                type="checkbox"
                                                checked={llmSettings.enable_llm_txt === '1'}
                                                onChange={(e) => setLlmSettings(prev => ({ ...prev, enable_llm_txt: e.target.checked ? '1' : '0' }))}
                                            />
                                            <span className="toggle-track" />
                                            <span className="toggle-text">
                                                {llmSettings.enable_llm_txt === '1' ? 'Enabled' : 'Disabled'}
                                            </span>
                                        </label>
                                    </div>
                                </div>

                                {llmSettings.enable_llm_txt === '1' && (
                                    <>
                                        <div className="settings-row compact">
                                            <div className="settings-label">
                                                <label>Title</label>
                                                <p className="settings-help">Main title in your llm.txt file.</p>
                                            </div>
                                            <div className="settings-control">
                                                <input
                                                    type="text"
                                                    value={llmSettings.llm_txt_title}
                                                    onChange={(e) => setLlmSettings(prev => ({ ...prev, llm_txt_title: e.target.value }))}
                                                    placeholder="Defaults to site name"
                                                />
                                            </div>
                                        </div>

                                        <div className="settings-row compact">
                                            <div className="settings-label">
                                                <label>Description</label>
                                                <p className="settings-help">Brief description below the title.</p>
                                            </div>
                                            <div className="settings-control">
                                                <textarea
                                                    value={llmSettings.llm_txt_description}
                                                    onChange={(e) => setLlmSettings(prev => ({ ...prev, llm_txt_description: e.target.value }))}
                                                    placeholder="Defaults to site tagline"
                                                    rows="3"
                                                />
                                            </div>
                                        </div>

                                        <div className="settings-row compact">
                                            <div className="settings-label">
                                                <label>Max Posts Per Type</label>
                                                <p className="settings-help">Limit posts included per type (1-500).</p>
                                            </div>
                                            <div className="settings-control">
                                                <input
                                                    type="number"
                                                    value={llmSettings.llm_txt_posts_per_type}
                                                    onChange={(e) => setLlmSettings(prev => ({ ...prev, llm_txt_posts_per_type: parseInt(e.target.value, 10) || 50 }))}
                                                    min="1"
                                                    max="500"
                                                    style={{ width: '120px' }}
                                                />
                                            </div>
                                        </div>

                                        <div className="settings-row compact">
                                            <div className="settings-label">
                                                <label>Options</label>
                                            </div>
                                            <div className="settings-control">
                                                <label className="checkbox-row">
                                                    <input
                                                        type="checkbox"
                                                        checked={llmSettings.llm_txt_include_excerpt === '1'}
                                                        onChange={(e) => setLlmSettings(prev => ({ ...prev, llm_txt_include_excerpt: e.target.checked ? '1' : '0' }))}
                                                    />
                                                    <span>Include post excerpts/descriptions</span>
                                                </label>
                                            </div>
                                        </div>

                                        {/* Content Types Preview */}
                                        <div className="settings-row compact">
                                            <div className="settings-label">
                                                <label>Content Included</label>
                                                <p className="settings-help">Post types in your llm.txt file.</p>
                                            </div>
                                            <div className="settings-control">
                                                <div className="post-types-preview">
                                                    {postTypes.map(pt => {
                                                        const willInclude = Math.min(pt.count, llmSettings.llm_txt_posts_per_type);
                                                        return (
                                                            <div key={pt.name} className="post-type-preview-item">
                                                                <div className="post-type-info">
                                                                    <strong>{pt.label}</strong>
                                                                    <span className="muted">
                                                                        {willInclude} of {pt.count} posts
                                                                    </span>
                                                                </div>
                                                            </div>
                                                        );
                                                    })}
                                                </div>
                                            </div>
                                        </div>
                                    </>
                                )}
                            </div>

                            {/* Save Button inside panel */}
                            {llmSettings.enable_llm_txt === '1' && (
                                <div className="form-actions" style={{ marginTop: '24px', paddingTop: '24px', borderTop: '1px solid #e5e7eb' }}>
                                    <button
                                        type="button"
                                        className="button primary"
                                        onClick={handleSaveLlmSettings}
                                        disabled={saving}
                                    >
                                        {saving ? 'Saving...' : 'Save Changes'}
                                    </button>
                                    <a
                                        href={llmSettings.llm_txt_url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="button ghost"
                                    >
                                        View llm.txt
                                    </a>
                                </div>
                            )}
                        </section>
                    </div>

                    {/* Sidebar */}
                    <aside className="side-panel">
                        <div className="side-card highlight">
                            <h3>Your llm.txt</h3>
                            {llmSettings.enable_llm_txt === '1' ? (
                                <>
                                    <code className="url-display">{llmSettings.llm_txt_url}</code>
                                    <p className="muted" style={{ marginTop: '12px', fontSize: '13px' }}>
                                        If not accessible, go to Settings &gt; Permalinks and save to flush rewrite rules.
                                    </p>
                                </>
                            ) : (
                                <p className="muted">Enable llm.txt to generate your file.</p>
                            )}
                        </div>

                        <div className="side-card">
                            <h3>What is llm.txt?</h3>
                            <p className="muted">
                                A standardized file that helps AI language models like ChatGPT, Claude, and Gemini
                                discover and understand your content structure.
                            </p>
                            <p className="muted" style={{ marginTop: '8px' }}>
                                This improves how AI systems reference and cite your content when answering questions.
                            </p>
                            <a
                                href="https://llmstxt.org/"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="button ghost small"
                                style={{ marginTop: '12px' }}
                            >
                                Learn More
                            </a>
                        </div>
                    </aside>
                </div>
            )}
        </div>
    );
};

export default Sitemap;
