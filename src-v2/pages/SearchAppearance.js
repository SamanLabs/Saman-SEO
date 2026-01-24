import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import SearchPreview from '../components/SearchPreview';
import TemplateInput from '../components/TemplateInput';
import AiGenerateModal from '../components/AiGenerateModal';
import { FacebookPreview, TwitterPreview } from '../components/SocialPreview';
import useUrlTab from '../hooks/useUrlTab';

// Get AI status from global settings
const globalSettings = window?.SamanSEOSettings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;

const searchAppearanceTabs = [
    { id: 'homepage', label: 'Homepage' },
    { id: 'content-types', label: 'Content Types' },
    { id: 'taxonomies', label: 'Taxonomies' },
    { id: 'archives', label: 'Archives' },
    { id: 'social-settings', label: 'Social Settings' },
    { id: 'social-cards', label: 'Social Cards' },
];

// Schema type options
const schemaTypeOptions = [
    { value: '', label: 'Use default (Article)' },
    { value: 'article', label: 'Article' },
    { value: 'blogposting', label: 'Blog posting' },
    { value: 'newsarticle', label: 'News article' },
    { value: 'product', label: 'Product' },
    { value: 'profilepage', label: 'Profile page' },
    { value: 'website', label: 'Website' },
    { value: 'organization', label: 'Organization' },
    { value: 'event', label: 'Event' },
    { value: 'recipe', label: 'Recipe' },
    { value: 'videoobject', label: 'Video object' },
    { value: 'book', label: 'Book' },
    { value: 'service', label: 'Service' },
    { value: 'localbusiness', label: 'Local business' },
];

// Social card layout options with sensible defaults for each
const cardLayoutOptions = [
    {
        value: 'default',
        label: 'Classic',
        description: 'Bottom accent stripe',
        defaults: { title_font_size: 42, site_font_size: 18, title_weight: 600 },
    },
    {
        value: 'centered',
        label: 'Centered',
        description: 'Centered text',
        defaults: { title_font_size: 48, site_font_size: 20, title_weight: 500 },
    },
    {
        value: 'minimal',
        label: 'Minimal',
        description: 'Clean, no accent',
        defaults: { title_font_size: 36, site_font_size: 16, title_weight: 400 },
    },
    {
        value: 'magazine',
        label: 'Magazine',
        description: 'Top bar, elegant',
        defaults: { title_font_size: 44, site_font_size: 14, title_weight: 600 },
    },
    {
        value: 'gradient',
        label: 'Gradient',
        description: 'Gradient overlay',
        defaults: { title_font_size: 40, site_font_size: 18, title_weight: 500 },
    },
    {
        value: 'corner',
        label: 'Corner',
        description: 'Corner accent',
        defaults: { title_font_size: 38, site_font_size: 16, title_weight: 500 },
    },
];

// Color presets for quick styling
const colorPresets = [
    { name: 'Dark Blue', bg: '#1a1a36', accent: '#5a84ff', text: '#ffffff' },
    { name: 'Slate', bg: '#1e293b', accent: '#38bdf8', text: '#f1f5f9' },
    { name: 'Forest', bg: '#14532d', accent: '#86efac', text: '#f0fdf4' },
    { name: 'Charcoal', bg: '#18181b', accent: '#a78bfa', text: '#fafafa' },
    { name: 'Navy', bg: '#0c1929', accent: '#f97316', text: '#fff7ed' },
    { name: 'Wine', bg: '#450a0a', accent: '#fca5a5', text: '#fef2f2' },
    { name: 'Ocean', bg: '#083344', accent: '#22d3ee', text: '#ecfeff' },
    { name: 'Cream', bg: '#fef3c7', accent: '#d97706', text: '#451a03' },
];

// Logo position options
const logoPositionOptions = [
    { value: 'top-left', label: 'Top Left' },
    { value: 'top-right', label: 'Top Right' },
    { value: 'bottom-left', label: 'Bottom Left' },
    { value: 'bottom-right', label: 'Bottom Right' },
    { value: 'center', label: 'Center' },
];

const SearchAppearance = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: searchAppearanceTabs, defaultTab: 'homepage' });

    // Global state
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [saveMessage, setSaveMessage] = useState('');
    const [siteInfo, setSiteInfo] = useState({});

    // Variables for template rendering
    const [variables, setVariables] = useState({});
    const [variableValues, setVariableValues] = useState({});

    // Homepage state
    const [homepage, setHomepage] = useState({
        meta_title: '',
        meta_description: '',
        meta_keywords: '',
    });
    const [separator, setSeparator] = useState('-');
    const [separatorOptions, setSeparatorOptions] = useState({});

    // Post types state
    const [postTypes, setPostTypes] = useState([]);
    const [editingPostType, setEditingPostType] = useState(null);
    const [schemaOptions, setSchemaOptions] = useState({ page: {}, article: {} });

    // Taxonomies state
    const [taxonomies, setTaxonomies] = useState([]);
    const [editingTaxonomy, setEditingTaxonomy] = useState(null);

    // Archives state
    const [archives, setArchives] = useState([]);
    const [editingArchive, setEditingArchive] = useState(null);

    // Social Settings state
    const [socialDefaults, setSocialDefaults] = useState({
        og_title: '',
        og_description: '',
        twitter_title: '',
        twitter_description: '',
        image_source: '',
        schema_itemtype: '',
    });
    const [postTypeSocialDefaults, setPostTypeSocialDefaults] = useState({});
    const [editingPostTypeSocial, setEditingPostTypeSocial] = useState(null);

    // Social Cards state
    const [cardDesign, setCardDesign] = useState({
        background_color: '#1a1a36',
        accent_color: '#5a84ff',
        text_color: '#ffffff',
        title_font_size: 42,
        site_font_size: 18,
        title_weight: 600,
        logo_url: '',
        logo_position: 'bottom-left',
        logo_size: 48,
        layout: 'default',
    });
    const [cardPreviewTitle, setCardPreviewTitle] = useState('Sample Post Title - Understanding Core Web Vitals');
    const [cardModuleEnabled, setCardModuleEnabled] = useState(true);
    const [previewPosts, setPreviewPosts] = useState({});
    const [selectedPreviewPost, setSelectedPreviewPost] = useState(null);
    const [previewPostType, setPreviewPostType] = useState('post');

    // AI Generation modal state
    const [aiModal, setAiModal] = useState({
        isOpen: false,
        fieldType: 'title',
        onApply: null,
        context: {},
    });

    // Open AI modal for a specific field
    const openAiModal = useCallback((fieldType, onApply, context = {}) => {
        setAiModal({
            isOpen: true,
            fieldType,
            onApply,
            context,
        });
    }, []);

    // Close AI modal
    const closeAiModal = useCallback(() => {
        setAiModal({
            isOpen: false,
            fieldType: 'title',
            onApply: null,
            context: {},
        });
    }, []);

    // Handle AI generated content
    const handleAiGenerate = useCallback((result) => {
        if (aiModal.onApply && result) {
            aiModal.onApply(result);
        }
    }, [aiModal]);

    // Fetch all data on mount
    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const response = await apiFetch({ path: '/saman-seo/v1/search-appearance' });
            if (response.success) {
                const data = response.data;
                setHomepage(data.homepage || {});
                setSeparator(data.separator || '-');
                setSeparatorOptions(data.separator_options || {});
                setPostTypes(data.post_types || []);
                setTaxonomies(data.taxonomies || []);
                setArchives(data.archives || []);
                setSchemaOptions(data.schema_options || { page: {}, article: {} });
                setSiteInfo(data.site_info || {});
                setVariables(data.variables || {});
                setVariableValues(data.variable_values || {});
                // Social settings
                setSocialDefaults(data.social_defaults || {
                    og_title: '',
                    og_description: '',
                    twitter_title: '',
                    twitter_description: '',
                    image_source: '',
                    schema_itemtype: '',
                });
                setPostTypeSocialDefaults(data.post_type_social_defaults || {});
                // Social cards
                setCardDesign(data.card_design || {
                    background_color: '#1a1a36',
                    accent_color: '#5a84ff',
                    text_color: '#ffffff',
                    title_font_size: 42,
                    site_font_size: 18,
                    title_weight: 600,
                    logo_url: '',
                    logo_position: 'bottom-left',
                    logo_size: 48,
                    layout: 'default',
                });
                setCardModuleEnabled(data.card_module_enabled !== false);
                // Fetch posts for preview selector
                if (data.preview_posts) {
                    setPreviewPosts(data.preview_posts);
                }
            }
        } catch (error) {
            console.error('Failed to fetch search appearance settings:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Clear save message after 3 seconds
    useEffect(() => {
        if (saveMessage) {
            const timer = setTimeout(() => setSaveMessage(''), 3000);
            return () => clearTimeout(timer);
        }
    }, [saveMessage]);

    // Update variable values when separator changes
    useEffect(() => {
        setVariableValues((prev) => ({
            ...prev,
            separator: separator,
        }));
    }, [separator]);

    // Apply modifier to a value (supports: upper, lower, capitalize, etc.)
    const applyModifier = (value, modifier) => {
        if (!value || !modifier) return value;
        const mod = modifier.trim().toLowerCase();
        switch (mod) {
            case 'upper':
            case 'uppercase':
                return String(value).toUpperCase();
            case 'lower':
            case 'lowercase':
                return String(value).toLowerCase();
            case 'capitalize':
            case 'title':
                return String(value).replace(/\b\w/g, c => c.toUpperCase());
            case 'trim':
                return String(value).trim();
            default:
                return value;
        }
    };

    // Generate preview from template using variable values
    const renderTemplatePreview = useCallback((template, contextOverrides = {}) => {
        if (!template) return '';

        let preview = template;
        const allValues = { ...variableValues, ...contextOverrides };

        // Replace all {{variable}} or {{variable | modifier}} patterns
        preview = preview.replace(/\{\{([^}]+)\}\}/g, (match, content) => {
            const trimmedContent = content.trim();

            // Check for modifier (e.g., "post_title | upper")
            const pipeIndex = trimmedContent.indexOf('|');
            if (pipeIndex > -1) {
                const baseTag = trimmedContent.substring(0, pipeIndex).trim();
                const modifier = trimmedContent.substring(pipeIndex + 1).trim();
                const baseValue = allValues[baseTag];
                if (baseValue !== undefined) {
                    return applyModifier(baseValue, modifier);
                }
                return match; // Return original if no value found
            }

            // Simple variable without modifier
            return allValues[trimmedContent] !== undefined ? allValues[trimmedContent] : match;
        });

        return preview;
    }, [variableValues]);

    // Save homepage settings
    const saveHomepage = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/search-appearance/homepage',
                method: 'POST',
                data: homepage,
            });
            if (response.success) {
                setSaveMessage('Homepage settings saved successfully.');
            }
        } catch (error) {
            console.error('Failed to save homepage settings:', error);
            setSaveMessage('Failed to save settings.');
        } finally {
            setSaving(false);
        }
    };

    // Save separator
    const saveSeparator = async (newSeparator) => {
        setSeparator(newSeparator);
        try {
            await apiFetch({
                path: '/saman-seo/v1/search-appearance/separator',
                method: 'POST',
                data: { separator: newSeparator },
            });
        } catch (error) {
            console.error('Failed to save separator:', error);
        }
    };

    // Save single post type
    const savePostType = async (postType) => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: `/saman-seo/v1/search-appearance/post-types/${postType.slug}`,
                method: 'POST',
                data: postType,
            });
            if (response.success) {
                setPostTypes(prev => prev.map(pt =>
                    pt.slug === postType.slug ? { ...pt, ...postType } : pt
                ));
                setEditingPostType(null);
                setSaveMessage('Post type settings saved.');
            }
        } catch (error) {
            console.error('Failed to save post type:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save single taxonomy
    const saveTaxonomy = async (taxonomy) => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: `/saman-seo/v1/search-appearance/taxonomies/${taxonomy.slug}`,
                method: 'POST',
                data: taxonomy,
            });
            if (response.success) {
                setTaxonomies(prev => prev.map(tax =>
                    tax.slug === taxonomy.slug ? { ...tax, ...taxonomy } : tax
                ));
                setEditingTaxonomy(null);
                setSaveMessage('Taxonomy settings saved.');
            }
        } catch (error) {
            console.error('Failed to save taxonomy:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save archives
    const saveArchives = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/search-appearance/archives',
                method: 'POST',
                data: archives,
            });
            if (response.success) {
                setArchives(response.data);
                setEditingArchive(null);
                setSaveMessage('Archive settings saved.');
            }
        } catch (error) {
            console.error('Failed to save archives:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save social defaults
    const saveSocialDefaults = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/search-appearance/social-defaults',
                method: 'POST',
                data: socialDefaults,
            });
            if (response.success) {
                setSaveMessage('Social settings saved.');
            }
        } catch (error) {
            console.error('Failed to save social defaults:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save post type social settings
    const savePostTypeSocial = async (slug, settings) => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: `/saman-seo/v1/search-appearance/social-defaults/${slug}`,
                method: 'POST',
                data: settings,
            });
            if (response.success) {
                setPostTypeSocialDefaults(prev => ({
                    ...prev,
                    [slug]: settings,
                }));
                setEditingPostTypeSocial(null);
                setSaveMessage('Post type social settings saved.');
            }
        } catch (error) {
            console.error('Failed to save post type social settings:', error);
        } finally {
            setSaving(false);
        }
    };

    // Save card design
    const saveCardDesign = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/search-appearance/card-design',
                method: 'POST',
                data: cardDesign,
            });
            if (response.success) {
                setSaveMessage('Social card design saved.');
            }
        } catch (error) {
            console.error('Failed to save card design:', error);
        } finally {
            setSaving(false);
        }
    };

    if (loading) {
        return (
            <div className="page">
                <div className="loading-state">Loading search appearance settings...</div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Search Appearance</h1>
                    <p>Control how your content appears in search results.</p>
                </div>
                {saveMessage && (
                    <span className="pill success">{saveMessage}</span>
                )}
            </div>

            <SubTabs tabs={searchAppearanceTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Search appearance sections" />

            {/* Homepage Tab */}
            {activeTab === 'homepage' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Homepage SEO</h3>
                            <p className="muted">Configure default title and meta description for your homepage.</p>
                        </div>
                    </div>

                    <SearchPreview
                        title={renderTemplatePreview(homepage.meta_title || `{{site_title}} {{separator}} {{tagline}}`)}
                        description={renderTemplatePreview(homepage.meta_description || siteInfo.description || '')}
                        domain={siteInfo.domain}
                        url={siteInfo.url}
                        favicon={siteInfo.favicon}
                    />

                    <div className="settings-form">
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="home-title">Homepage Title</label>
                                <p className="settings-help">The title tag for your homepage.</p>
                            </div>
                            <div className="settings-control">
                                <TemplateInput
                                    id="home-title"
                                    value={homepage.meta_title}
                                    onChange={(val) => setHomepage({ ...homepage, meta_title: val })}
                                    placeholder={`${siteInfo.name} ${separator} ${siteInfo.description}`}
                                    variables={variables}
                                    variableValues={variableValues}
                                    context="global"
                                    maxLength={60}
                                    onAiClick={() => openAiModal('title', (val) => setHomepage({ ...homepage, meta_title: val }), { type: 'Homepage' })}
                                    aiEnabled={aiEnabled}
                                />
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="home-desc">Meta Description</label>
                                <p className="settings-help">A brief description of your website (150-160 chars recommended).</p>
                            </div>
                            <div className="settings-control">
                                <TemplateInput
                                    id="home-desc"
                                    value={homepage.meta_description}
                                    onChange={(val) => setHomepage({ ...homepage, meta_description: val })}
                                    placeholder="A brief description of your website..."
                                    variables={variables}
                                    variableValues={variableValues}
                                    context="global"
                                    multiline
                                    maxLength={160}
                                    onAiClick={() => openAiModal('description', (val) => setHomepage({ ...homepage, meta_description: val }), { type: 'Homepage' })}
                                    aiEnabled={aiEnabled}
                                />
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Title Separator</label>
                                <p className="settings-help">Character used between title parts across your site.</p>
                            </div>
                            <div className="settings-control">
                                <div className="separator-selector">
                                    {Object.entries(separatorOptions).map(([value, label]) => (
                                        <button
                                            key={value}
                                            type="button"
                                            className={`separator-option ${separator === value ? 'active' : ''}`}
                                            onClick={() => saveSeparator(value)}
                                            title={label}
                                        >
                                            {value}
                                        </button>
                                    ))}
                                    <div className="separator-custom">
                                        <input
                                            type="text"
                                            className="separator-custom__input"
                                            value={!Object.keys(separatorOptions).includes(separator) ? separator : ''}
                                            onChange={(e) => saveSeparator(e.target.value)}
                                            placeholder="Custom"
                                            maxLength={5}
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label htmlFor="home-keywords">Meta Keywords</label>
                                <p className="settings-help">Comma-separated keywords (optional, less relevant for modern SEO).</p>
                            </div>
                            <div className="settings-control">
                                <input
                                    type="text"
                                    id="home-keywords"
                                    className="input"
                                    value={homepage.meta_keywords || ''}
                                    onChange={(e) => setHomepage({ ...homepage, meta_keywords: e.target.value })}
                                    placeholder="keyword1, keyword2, keyword3"
                                />
                            </div>
                        </div>

                        <div className="form-actions">
                            <button
                                type="button"
                                className="button primary"
                                onClick={saveHomepage}
                                disabled={saving}
                            >
                                {saving ? 'Saving...' : 'Save Homepage Settings'}
                            </button>
                        </div>
                    </div>
                </section>
            )}

            {/* Content Types Tab */}
            {activeTab === 'content-types' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Content Types</h3>
                            <p className="muted">Configure SEO defaults for each post type.</p>
                        </div>
                    </div>

                    {editingPostType ? (
                        <PostTypeEditor
                            postType={editingPostType}
                            schemaOptions={schemaOptions}
                            separator={separator}
                            siteInfo={siteInfo}
                            variables={variables}
                            variableValues={variableValues}
                            onSave={savePostType}
                            onCancel={() => setEditingPostType(null)}
                            saving={saving}
                            renderTemplatePreview={renderTemplatePreview}
                            openAiModal={openAiModal}
                        />
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Post Type</th>
                                    <th>Title Preview</th>
                                    <th>Show in Search</th>
                                    <th>Posts</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {postTypes.map((pt) => {
                                    const template = pt.title_template || '{{post_title}} {{separator}} {{site_title}}';
                                    // Use sample title specific to this post type
                                    const previewOverrides = {
                                        post_title: pt.sample_title || pt.singular_name,
                                    };
                                    return (
                                        <tr key={pt.slug}>
                                            <td>
                                                <strong>{pt.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{pt.slug}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template, previewOverrides)}
                                                    </span>
                                                    <code className="title-preview-cell__template">
                                                        {template}
                                                    </code>
                                                </div>
                                            </td>
                                            <td>
                                                <span className={`pill ${pt.noindex ? 'warning' : 'success'}`}>
                                                    {pt.noindex ? 'No' : 'Yes'}
                                                </span>
                                            </td>
                                            <td>{pt.count}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="link-button"
                                                    onClick={() => setEditingPostType({ ...pt })}
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </section>
            )}

            {/* Taxonomies Tab */}
            {activeTab === 'taxonomies' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Taxonomies</h3>
                            <p className="muted">Configure SEO settings for categories, tags, and custom taxonomies.</p>
                        </div>
                    </div>

                    {editingTaxonomy ? (
                        <TaxonomyEditor
                            taxonomy={editingTaxonomy}
                            separator={separator}
                            siteInfo={siteInfo}
                            variables={variables}
                            variableValues={variableValues}
                            onSave={saveTaxonomy}
                            onCancel={() => setEditingTaxonomy(null)}
                            saving={saving}
                            renderTemplatePreview={renderTemplatePreview}
                            openAiModal={openAiModal}
                        />
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Taxonomy</th>
                                    <th>Title Preview</th>
                                    <th>Show in Search</th>
                                    <th>Terms</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {taxonomies.map((tax) => {
                                    const template = tax.title_template || '{{term_title}} {{separator}} {{site_title}}';
                                    // Use sample term title specific to this taxonomy
                                    const previewOverrides = {
                                        term_title: tax.sample_term_title || tax.singular_name,
                                    };
                                    return (
                                        <tr key={tax.slug}>
                                            <td>
                                                <strong>{tax.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{tax.slug}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template, previewOverrides)}
                                                    </span>
                                                    <code className="title-preview-cell__template">
                                                        {template}
                                                    </code>
                                                </div>
                                            </td>
                                            <td>
                                                <span className={`pill ${tax.noindex ? 'warning' : 'success'}`}>
                                                    {tax.noindex ? 'No' : 'Yes'}
                                                </span>
                                            </td>
                                            <td>{tax.count}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="link-button"
                                                    onClick={() => setEditingTaxonomy({ ...tax })}
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </section>
            )}

            {/* Archives Tab */}
            {activeTab === 'archives' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Archives</h3>
                            <p className="muted">Configure SEO for author, date, search, and 404 pages.</p>
                        </div>
                    </div>

                    {editingArchive ? (
                        <ArchiveEditor
                            archive={editingArchive}
                            separator={separator}
                            siteInfo={siteInfo}
                            variables={variables}
                            variableValues={variableValues}
                            onSave={(updated) => {
                                setArchives(prev => prev.map(a =>
                                    a.slug === updated.slug ? updated : a
                                ));
                                setEditingArchive(null);
                            }}
                            onCancel={() => setEditingArchive(null)}
                            renderTemplatePreview={renderTemplatePreview}
                            openAiModal={openAiModal}
                        />
                    ) : (
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Archive Type</th>
                                    <th>Title Preview</th>
                                    <th>Show in Search</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {archives.map((archive) => {
                                    const template = archive.title_template || '{{archive_title}} {{separator}} {{site_title}}';
                                    // Use sample values specific to this archive type
                                    const previewOverrides = archive.sample_values || {};
                                    return (
                                        <tr key={archive.slug}>
                                            <td>
                                                <strong>{archive.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{archive.description}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template, previewOverrides)}
                                                    </span>
                                                    <code className="title-preview-cell__template">
                                                        {template}
                                                    </code>
                                                </div>
                                            </td>
                                            <td>
                                                <span className={`pill ${archive.noindex ? 'warning' : 'success'}`}>
                                                    {archive.noindex ? 'No' : 'Yes'}
                                                </span>
                                            </td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="link-button"
                                                    onClick={() => setEditingArchive({ ...archive })}
                                                >
                                                    Edit
                                                </button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </section>
            )}

            {/* Social Settings Tab */}
            {activeTab === 'social-settings' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Social Settings</h3>
                            <p className="muted">Configure default Open Graph, Twitter, and schema values for social sharing.</p>
                        </div>
                    </div>

                    {editingPostTypeSocial ? (
                        <div className="settings-form">
                            <div className="type-editor__header">
                                <h4>Edit {editingPostTypeSocial.name} Social Settings</h4>
                                <button
                                    type="button"
                                    className="link-button"
                                    onClick={() => setEditingPostTypeSocial(null)}
                                >
                                    Cancel
                                </button>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>OG Title</label>
                                    <p className="settings-help">Open Graph title for Facebook shares.</p>
                                </div>
                                <div className="settings-control">
                                    <TemplateInput
                                        value={editingPostTypeSocial.og_title || ''}
                                        onChange={(val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, og_title: val })}
                                        placeholder="{{post_title}} {{separator}} {{site_title}}"
                                        variables={variables}
                                        variableValues={variableValues}
                                        context="post"
                                        maxLength={60}
                                        onAiClick={() => openAiModal('title', (val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, og_title: val }), { type: editingPostTypeSocial.name, name: 'OG Title' })}
                                    />
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>OG Description</label>
                                    <p className="settings-help">Open Graph description for Facebook shares.</p>
                                </div>
                                <div className="settings-control">
                                    <TemplateInput
                                        value={editingPostTypeSocial.og_description || ''}
                                        onChange={(val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, og_description: val })}
                                        placeholder="{{post_excerpt}}"
                                        variables={variables}
                                        variableValues={variableValues}
                                        context="post"
                                        multiline
                                        maxLength={160}
                                        onAiClick={() => openAiModal('description', (val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, og_description: val }), { type: editingPostTypeSocial.name, name: 'OG Description' })}
                                    />
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Twitter Title</label>
                                    <p className="settings-help">Twitter card title.</p>
                                </div>
                                <div className="settings-control">
                                    <TemplateInput
                                        value={editingPostTypeSocial.twitter_title || ''}
                                        onChange={(val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, twitter_title: val })}
                                        placeholder="{{post_title}} {{separator}} {{site_title}}"
                                        variables={variables}
                                        variableValues={variableValues}
                                        context="post"
                                        maxLength={60}
                                        onAiClick={() => openAiModal('title', (val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, twitter_title: val }), { type: editingPostTypeSocial.name, name: 'Twitter Title' })}
                                    />
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Twitter Description</label>
                                    <p className="settings-help">Twitter card description.</p>
                                </div>
                                <div className="settings-control">
                                    <TemplateInput
                                        value={editingPostTypeSocial.twitter_description || ''}
                                        onChange={(val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, twitter_description: val })}
                                        placeholder="{{post_excerpt}}"
                                        variables={variables}
                                        variableValues={variableValues}
                                        context="post"
                                        multiline
                                        maxLength={160}
                                        onAiClick={() => openAiModal('description', (val) => setEditingPostTypeSocial({ ...editingPostTypeSocial, twitter_description: val }), { type: editingPostTypeSocial.name, name: 'Twitter Description' })}
                                    />
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Fallback Image</label>
                                    <p className="settings-help">Fallback image for social sharing.</p>
                                </div>
                                <div className="settings-control">
                                    <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start' }}>
                                        {editingPostTypeSocial.image_source ? (
                                            <div style={{ position: 'relative', width: '120px', height: '63px', borderRadius: '4px', overflow: 'hidden', border: '1px solid #ddd' }}>
                                                <img
                                                    src={editingPostTypeSocial.image_source}
                                                    alt="Social fallback"
                                                    style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() => setEditingPostTypeSocial({ ...editingPostTypeSocial, image_source: '' })}
                                                    style={{
                                                        position: 'absolute',
                                                        top: '4px',
                                                        right: '4px',
                                                        width: '20px',
                                                        height: '20px',
                                                        borderRadius: '50%',
                                                        background: 'rgba(0,0,0,0.6)',
                                                        color: '#fff',
                                                        border: 'none',
                                                        cursor: 'pointer',
                                                        display: 'flex',
                                                        alignItems: 'center',
                                                        justifyContent: 'center',
                                                        fontSize: '14px',
                                                        lineHeight: 1,
                                                    }}
                                                    title="Remove image"
                                                >
                                                    ×
                                                </button>
                                            </div>
                                        ) : (
                                            <div style={{ width: '120px', height: '63px', borderRadius: '4px', border: '2px dashed #ddd', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999', fontSize: '12px' }}>
                                                No image
                                            </div>
                                        )}
                                        <button
                                            type="button"
                                            className="button"
                                            onClick={() => {
                                                const frame = wp.media({
                                                    title: 'Select Fallback Image',
                                                    button: { text: 'Use Image' },
                                                    multiple: false,
                                                    library: { type: 'image' },
                                                });
                                                frame.on('select', () => {
                                                    const attachment = frame.state().get('selection').first().toJSON();
                                                    setEditingPostTypeSocial({ ...editingPostTypeSocial, image_source: attachment.url });
                                                });
                                                frame.open();
                                            }}
                                        >
                                            {editingPostTypeSocial.image_source ? 'Change Image' : 'Select Image'}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div className="settings-row compact">
                                <div className="settings-label">
                                    <label>Schema Type</label>
                                    <p className="settings-help">Schema.org type for this post type.</p>
                                </div>
                                <div className="settings-control">
                                    <select
                                        className="input"
                                        value={editingPostTypeSocial.schema_itemtype || ''}
                                        onChange={(e) => setEditingPostTypeSocial({ ...editingPostTypeSocial, schema_itemtype: e.target.value })}
                                    >
                                        {schemaTypeOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                        ))}
                                    </select>
                                </div>
                            </div>

                            <div className="form-actions">
                                <button
                                    type="button"
                                    className="button primary"
                                    onClick={() => savePostTypeSocial(editingPostTypeSocial.slug, editingPostTypeSocial)}
                                    disabled={saving}
                                >
                                    {saving ? 'Saving...' : 'Save Settings'}
                                </button>
                                <button
                                    type="button"
                                    className="button"
                                    onClick={() => setEditingPostTypeSocial(null)}
                                >
                                    Cancel
                                </button>
                            </div>
                        </div>
                    ) : (
                        <>
                            {/* Social Preview Cards */}
                            <div style={{ marginBottom: '32px', padding: '24px', background: '#f8f9fa', borderRadius: '8px' }}>
                                <h4 style={{ marginBottom: '16px' }}>Social Preview</h4>
                                <p className="muted" style={{ marginBottom: '24px' }}>
                                    Preview how your content will appear when shared on social media.
                                </p>
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fit, minmax(280px, 1fr))', gap: '24px' }}>
                                    <FacebookPreview
                                        image={socialDefaults.image_source}
                                        title={renderTemplatePreview(socialDefaults.og_title) || siteInfo.name}
                                        description={renderTemplatePreview(socialDefaults.og_description) || siteInfo.description}
                                        domain={siteInfo.domain}
                                    />
                                    <TwitterPreview
                                        image={socialDefaults.image_source}
                                        title={renderTemplatePreview(socialDefaults.twitter_title || socialDefaults.og_title) || siteInfo.name}
                                        description={renderTemplatePreview(socialDefaults.twitter_description || socialDefaults.og_description) || siteInfo.description}
                                        domain={siteInfo.domain}
                                        cardType="summary_large_image"
                                    />
                                </div>
                            </div>

                            {/* Global Social Defaults */}
                            <div className="settings-form" style={{ marginBottom: '32px' }}>
                                <h4 style={{ marginBottom: '16px' }}>Global Social Defaults</h4>
                                <p className="muted" style={{ marginBottom: '24px' }}>
                                    These defaults apply when posts don't have custom social values.
                                </p>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>OG Title</label>
                                        <p className="settings-help">Default Open Graph title for Facebook.</p>
                                    </div>
                                    <div className="settings-control">
                                        <TemplateInput
                                            value={socialDefaults.og_title || ''}
                                            onChange={(val) => setSocialDefaults({ ...socialDefaults, og_title: val })}
                                            placeholder="{{site_title}} {{separator}} {{tagline}}"
                                            variables={variables}
                                            variableValues={variableValues}
                                            context="global"
                                            maxLength={60}
                                            onAiClick={() => openAiModal('title', (val) => setSocialDefaults({ ...socialDefaults, og_title: val }), { type: 'Social', name: 'Open Graph Title' })}
                                        />
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>OG Description</label>
                                        <p className="settings-help">Default Open Graph description.</p>
                                    </div>
                                    <div className="settings-control">
                                        <TemplateInput
                                            value={socialDefaults.og_description || ''}
                                            onChange={(val) => setSocialDefaults({ ...socialDefaults, og_description: val })}
                                            placeholder="{{tagline}}"
                                            variables={variables}
                                            variableValues={variableValues}
                                            context="global"
                                            multiline
                                            maxLength={160}
                                            onAiClick={() => openAiModal('description', (val) => setSocialDefaults({ ...socialDefaults, og_description: val }), { type: 'Social', name: 'Open Graph Description' })}
                                        />
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Twitter Title</label>
                                        <p className="settings-help">Default Twitter card title.</p>
                                    </div>
                                    <div className="settings-control">
                                        <TemplateInput
                                            value={socialDefaults.twitter_title || ''}
                                            onChange={(val) => setSocialDefaults({ ...socialDefaults, twitter_title: val })}
                                            placeholder="{{site_title}} {{separator}} {{tagline}}"
                                            variables={variables}
                                            variableValues={variableValues}
                                            context="global"
                                            maxLength={60}
                                            onAiClick={() => openAiModal('title', (val) => setSocialDefaults({ ...socialDefaults, twitter_title: val }), { type: 'Social', name: 'Twitter Card Title' })}
                                        />
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Twitter Description</label>
                                        <p className="settings-help">Default Twitter card description.</p>
                                    </div>
                                    <div className="settings-control">
                                        <TemplateInput
                                            value={socialDefaults.twitter_description || ''}
                                            onChange={(val) => setSocialDefaults({ ...socialDefaults, twitter_description: val })}
                                            placeholder="{{tagline}}"
                                            variables={variables}
                                            variableValues={variableValues}
                                            context="global"
                                            multiline
                                            maxLength={160}
                                            onAiClick={() => openAiModal('description', (val) => setSocialDefaults({ ...socialDefaults, twitter_description: val }), { type: 'Social', name: 'Twitter Card Description' })}
                                        />
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Fallback Image</label>
                                        <p className="settings-help">Used when posts don't have a featured image (1200x630px recommended).</p>
                                    </div>
                                    <div className="settings-control">
                                        <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start' }}>
                                            {socialDefaults.image_source ? (
                                                <div style={{ position: 'relative', width: '120px', height: '63px', borderRadius: '4px', overflow: 'hidden', border: '1px solid #ddd' }}>
                                                    <img
                                                        src={socialDefaults.image_source}
                                                        alt="Social fallback"
                                                        style={{ width: '100%', height: '100%', objectFit: 'cover' }}
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => setSocialDefaults({ ...socialDefaults, image_source: '' })}
                                                        style={{
                                                            position: 'absolute',
                                                            top: '4px',
                                                            right: '4px',
                                                            width: '20px',
                                                            height: '20px',
                                                            borderRadius: '50%',
                                                            background: 'rgba(0,0,0,0.6)',
                                                            color: '#fff',
                                                            border: 'none',
                                                            cursor: 'pointer',
                                                            display: 'flex',
                                                            alignItems: 'center',
                                                            justifyContent: 'center',
                                                            fontSize: '14px',
                                                            lineHeight: 1,
                                                        }}
                                                        title="Remove image"
                                                    >
                                                        ×
                                                    </button>
                                                </div>
                                            ) : (
                                                <div style={{ width: '120px', height: '63px', borderRadius: '4px', border: '2px dashed #ddd', display: 'flex', alignItems: 'center', justifyContent: 'center', color: '#999', fontSize: '12px' }}>
                                                    No image
                                                </div>
                                            )}
                                            <button
                                                type="button"
                                                className="button"
                                                onClick={() => {
                                                    const frame = wp.media({
                                                        title: 'Select Fallback Image',
                                                        button: { text: 'Use Image' },
                                                        multiple: false,
                                                        library: { type: 'image' },
                                                    });
                                                    frame.on('select', () => {
                                                        const attachment = frame.state().get('selection').first().toJSON();
                                                        setSocialDefaults({ ...socialDefaults, image_source: attachment.url });
                                                    });
                                                    frame.open();
                                                }}
                                            >
                                                {socialDefaults.image_source ? 'Change Image' : 'Select Image'}
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div className="settings-row compact">
                                    <div className="settings-label">
                                        <label>Default Schema Type</label>
                                        <p className="settings-help">Controls the og:type meta tag for content.</p>
                                    </div>
                                    <div className="settings-control">
                                        <select
                                            className="input"
                                            value={socialDefaults.schema_itemtype || ''}
                                            onChange={(e) => setSocialDefaults({ ...socialDefaults, schema_itemtype: e.target.value })}
                                        >
                                            {schemaTypeOptions.map((opt) => (
                                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="form-actions">
                                    <button
                                        type="button"
                                        className="button primary"
                                        onClick={saveSocialDefaults}
                                        disabled={saving}
                                    >
                                        {saving ? 'Saving...' : 'Save Global Defaults'}
                                    </button>
                                </div>
                            </div>

                            {/* Post Type Specific Settings */}
                            <div style={{ marginTop: '32px' }}>
                                <h4 style={{ marginBottom: '8px' }}>Post Type Specific Settings</h4>
                                <p className="muted" style={{ marginBottom: '16px' }}>
                                    Override default social settings for specific post types.
                                </p>

                                <table className="data-table">
                                    <thead>
                                        <tr>
                                            <th>Post Type</th>
                                            <th>OG Title</th>
                                            <th>Schema Type</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {postTypes.map((pt) => {
                                            const socialSettings = postTypeSocialDefaults[pt.slug] || {};
                                            return (
                                                <tr key={pt.slug}>
                                                    <td>
                                                        <strong>{pt.name}</strong>
                                                        <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{pt.slug}</span>
                                                    </td>
                                                    <td>
                                                        <span className="muted">
                                                            {socialSettings.og_title || 'Using global default'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span className="muted">
                                                            {socialSettings.schema_itemtype || 'Article'}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button
                                                            type="button"
                                                            className="link-button"
                                                            onClick={() => setEditingPostTypeSocial({
                                                                slug: pt.slug,
                                                                name: pt.name,
                                                                ...socialSettings,
                                                            })}
                                                        >
                                                            Edit
                                                        </button>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </>
                    )}
                </section>
            )}

            {/* AI Generate Modal */}
            <AiGenerateModal
                isOpen={aiModal.isOpen}
                onClose={closeAiModal}
                onGenerate={handleAiGenerate}
                fieldType={aiModal.fieldType}
                variableValues={variableValues}
                context={aiModal.context}
            />

            {/* Social Cards Tab */}
            {activeTab === 'social-cards' && (
                <section className="panel">
                    <div className="table-toolbar">
                        <div>
                            <h3>Social Cards</h3>
                            <p className="muted">Customize the appearance of dynamically generated social card images.</p>
                        </div>
                        <div>
                            <span className={`pill ${cardModuleEnabled ? 'success' : 'warning'}`}>
                                {cardModuleEnabled ? 'Active' : 'Disabled'}
                            </span>
                        </div>
                    </div>

                    <div className="settings-form">
                        {/* Live Preview */}
                        <div style={{ marginBottom: '32px', padding: '24px', background: '#f8f9fa', borderRadius: '12px' }}>
                            <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '16px' }}>
                                <h4 style={{ margin: 0 }}>Live Preview</h4>
                            </div>

                            {/* Preview Title Input */}
                            <div style={{ display: 'flex', gap: '12px', marginBottom: '20px', flexWrap: 'wrap' }}>
                                <div style={{ flex: '1 1 300px' }}>
                                    <label style={{ display: 'block', marginBottom: '6px', fontSize: '13px', color: '#666' }}>
                                        Preview Title
                                    </label>
                                    <input
                                        type="text"
                                        className="input"
                                        value={cardPreviewTitle}
                                        onChange={(e) => setCardPreviewTitle(e.target.value)}
                                        placeholder="Enter a sample title..."
                                    />
                                </div>
                                {Object.keys(previewPosts).length > 0 && (
                                    <>
                                        <div style={{ flex: '0 0 140px' }}>
                                            <label style={{ display: 'block', marginBottom: '6px', fontSize: '13px', color: '#666' }}>
                                                Content type
                                            </label>
                                            <select
                                                className="input"
                                                value={previewPostType}
                                                onChange={(e) => {
                                                    setPreviewPostType(e.target.value);
                                                    setSelectedPreviewPost('');
                                                }}
                                            >
                                                {Object.entries(previewPosts).map(([slug, data]) => (
                                                    <option key={slug} value={slug}>{data.label}</option>
                                                ))}
                                            </select>
                                        </div>
                                        <div style={{ flex: '1 1 200px' }}>
                                            <label style={{ display: 'block', marginBottom: '6px', fontSize: '13px', color: '#666' }}>
                                                Select content
                                            </label>
                                            <select
                                                className="input"
                                                value={selectedPreviewPost || ''}
                                                onChange={(e) => {
                                                    const postId = e.target.value;
                                                    setSelectedPreviewPost(postId);
                                                    if (postId && previewPosts[previewPostType]) {
                                                        const post = previewPosts[previewPostType].posts.find(p => String(p.id) === postId);
                                                        if (post) {
                                                            setCardPreviewTitle(post.title);
                                                        }
                                                    }
                                                }}
                                            >
                                                <option value="">Custom text</option>
                                                {previewPosts[previewPostType]?.posts?.map(post => (
                                                    <option key={post.id} value={post.id}>{post.title}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </>
                                )}
                            </div>

                            {/* Card Preview */}
                            <div
                                className="social-card-preview"
                                style={{
                                    width: '100%',
                                    maxWidth: '600px',
                                    aspectRatio: '1200/630',
                                    background: cardDesign.layout === 'gradient'
                                        ? `linear-gradient(135deg, ${cardDesign.background_color} 0%, ${cardDesign.accent_color}44 100%)`
                                        : cardDesign.background_color,
                                    borderRadius: '8px',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    justifyContent: cardDesign.layout === 'centered' ? 'center' : cardDesign.layout === 'magazine' ? 'flex-end' : 'flex-end',
                                    alignItems: cardDesign.layout === 'centered' ? 'center' : 'flex-start',
                                    padding: cardDesign.layout === 'magazine' ? '40px 32px' : '32px',
                                    position: 'relative',
                                    overflow: 'hidden',
                                    boxShadow: '0 4px 20px rgba(0,0,0,0.15)',
                                }}
                            >
                                {/* Layout-specific accent elements */}
                                {cardDesign.layout === 'default' && (
                                    <div
                                        style={{
                                            position: 'absolute',
                                            bottom: 0,
                                            left: 0,
                                            right: 0,
                                            height: '4px',
                                            background: cardDesign.accent_color,
                                        }}
                                    />
                                )}
                                {cardDesign.layout === 'magazine' && (
                                    <div
                                        style={{
                                            position: 'absolute',
                                            top: 0,
                                            left: 0,
                                            right: 0,
                                            height: '4px',
                                            background: cardDesign.accent_color,
                                        }}
                                    />
                                )}
                                {cardDesign.layout === 'corner' && (
                                    <>
                                        <div
                                            style={{
                                                position: 'absolute',
                                                top: 0,
                                                left: 0,
                                                width: '80px',
                                                height: '80px',
                                                background: cardDesign.accent_color,
                                                clipPath: 'polygon(0 0, 100% 0, 0 100%)',
                                            }}
                                        />
                                        <div
                                            style={{
                                                position: 'absolute',
                                                bottom: 0,
                                                right: 0,
                                                width: '120px',
                                                height: '120px',
                                                background: cardDesign.accent_color,
                                                clipPath: 'polygon(100% 100%, 0 100%, 100% 0)',
                                                opacity: 0.5,
                                            }}
                                        />
                                    </>
                                )}
                                {cardDesign.layout === 'gradient' && (
                                    <div
                                        style={{
                                            position: 'absolute',
                                            bottom: 0,
                                            left: 0,
                                            right: 0,
                                            height: '50%',
                                            background: `linear-gradient(to top, ${cardDesign.background_color} 0%, transparent 100%)`,
                                        }}
                                    />
                                )}

                                {/* Logo */}
                                {cardDesign.logo_url && (
                                    <img
                                        src={cardDesign.logo_url}
                                        alt="Logo"
                                        style={{
                                            position: 'absolute',
                                            width: `${Math.max(24, (cardDesign.logo_size || 48) / 2.5)}px`,
                                            height: `${Math.max(24, (cardDesign.logo_size || 48) / 2.5)}px`,
                                            objectFit: 'contain',
                                            ...(cardDesign.logo_position === 'top-left' && { top: '20px', left: '20px' }),
                                            ...(cardDesign.logo_position === 'top-right' && { top: '20px', right: '20px' }),
                                            ...(cardDesign.logo_position === 'bottom-left' && { bottom: '20px', left: '20px' }),
                                            ...(cardDesign.logo_position === 'bottom-right' && { bottom: '20px', right: '20px' }),
                                            ...(cardDesign.logo_position === 'center' && { top: '50%', left: '50%', transform: 'translate(-50%, -50%)', opacity: 0.1, width: '40%', height: '40%' }),
                                        }}
                                    />
                                )}

                                {/* Title */}
                                <h2
                                    style={{
                                        color: cardDesign.text_color,
                                        fontSize: `${Math.max(14, cardDesign.title_font_size / 3)}px`,
                                        fontWeight: cardDesign.title_weight || 600,
                                        margin: 0,
                                        marginBottom: '6px',
                                        textAlign: cardDesign.layout === 'centered' ? 'center' : 'left',
                                        zIndex: 1,
                                        lineHeight: 1.3,
                                        maxWidth: cardDesign.layout === 'centered' ? '90%' : '85%',
                                    }}
                                >
                                    {cardPreviewTitle}
                                </h2>

                                {/* Site Name */}
                                <span
                                    style={{
                                        color: cardDesign.text_color,
                                        fontSize: `${Math.max(9, cardDesign.site_font_size / 3)}px`,
                                        opacity: 0.7,
                                        zIndex: 1,
                                        fontWeight: 400,
                                        letterSpacing: cardDesign.layout === 'magazine' ? '0.5px' : '0',
                                        textTransform: cardDesign.layout === 'magazine' ? 'uppercase' : 'none',
                                    }}
                                >
                                    {siteInfo.name || 'Your Site Name'}
                                </span>
                            </div>
                        </div>

                        {/* Layout Selection */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Layout Style</label>
                                <p className="settings-help">Each layout has optimized defaults.</p>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'grid', gridTemplateColumns: 'repeat(auto-fill, minmax(100px, 1fr))', gap: '8px', maxWidth: '500px' }}>
                                    {cardLayoutOptions.map((layout) => (
                                        <label
                                            key={layout.value}
                                            style={{
                                                display: 'flex',
                                                flexDirection: 'column',
                                                alignItems: 'center',
                                                padding: '12px 8px',
                                                border: `2px solid ${cardDesign.layout === layout.value ? '#2271b1' : '#e0e0e0'}`,
                                                borderRadius: '8px',
                                                cursor: 'pointer',
                                                background: cardDesign.layout === layout.value ? '#f0f6fc' : '#fff',
                                                transition: 'all 0.15s ease',
                                            }}
                                        >
                                            <input
                                                type="radio"
                                                name="card-layout"
                                                value={layout.value}
                                                checked={cardDesign.layout === layout.value}
                                                onChange={(e) => {
                                                    const newLayout = e.target.value;
                                                    const layoutConfig = cardLayoutOptions.find(l => l.value === newLayout);
                                                    setCardDesign({
                                                        ...cardDesign,
                                                        layout: newLayout,
                                                        ...(layoutConfig?.defaults || {}),
                                                    });
                                                }}
                                                style={{ display: 'none' }}
                                            />
                                            <span style={{ fontSize: '13px', fontWeight: 500 }}>{layout.label}</span>
                                            <span style={{ fontSize: '11px', color: '#666', textAlign: 'center' }}>{layout.description}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Color Presets */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Color Presets</label>
                                <p className="settings-help">Quick color schemes to get started.</p>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '6px', flexWrap: 'wrap' }}>
                                    {colorPresets.map((preset) => (
                                        <button
                                            key={preset.name}
                                            type="button"
                                            onClick={() => setCardDesign({
                                                ...cardDesign,
                                                background_color: preset.bg,
                                                accent_color: preset.accent,
                                                text_color: preset.text,
                                            })}
                                            style={{
                                                display: 'flex',
                                                alignItems: 'center',
                                                gap: '6px',
                                                padding: '6px 10px',
                                                border: '1px solid #ddd',
                                                borderRadius: '6px',
                                                background: '#fff',
                                                cursor: 'pointer',
                                                fontSize: '12px',
                                            }}
                                            title={preset.name}
                                        >
                                            <span style={{
                                                width: '16px',
                                                height: '16px',
                                                borderRadius: '3px',
                                                background: preset.bg,
                                                border: '1px solid rgba(0,0,0,0.1)',
                                            }} />
                                            <span style={{
                                                width: '16px',
                                                height: '16px',
                                                borderRadius: '3px',
                                                background: preset.accent,
                                                border: '1px solid rgba(0,0,0,0.1)',
                                            }} />
                                            <span style={{ color: '#666' }}>{preset.name}</span>
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Colors Row */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Colors</label>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap' }}>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Background</span>
                                        <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                                            <input
                                                type="color"
                                                value={cardDesign.background_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, background_color: e.target.value })}
                                                style={{ width: '36px', height: '32px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer', padding: 0 }}
                                            />
                                            <input
                                                type="text"
                                                className="input"
                                                value={cardDesign.background_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, background_color: e.target.value })}
                                                style={{ width: '80px', fontSize: '12px' }}
                                            />
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Accent</span>
                                        <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                                            <input
                                                type="color"
                                                value={cardDesign.accent_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, accent_color: e.target.value })}
                                                style={{ width: '36px', height: '32px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer', padding: 0 }}
                                            />
                                            <input
                                                type="text"
                                                className="input"
                                                value={cardDesign.accent_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, accent_color: e.target.value })}
                                                style={{ width: '80px', fontSize: '12px' }}
                                            />
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Text</span>
                                        <div style={{ display: 'flex', gap: '6px', alignItems: 'center' }}>
                                            <input
                                                type="color"
                                                value={cardDesign.text_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, text_color: e.target.value })}
                                                style={{ width: '36px', height: '32px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer', padding: 0 }}
                                            />
                                            <input
                                                type="text"
                                                className="input"
                                                value={cardDesign.text_color}
                                                onChange={(e) => setCardDesign({ ...cardDesign, text_color: e.target.value })}
                                                style={{ width: '80px', fontSize: '12px' }}
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Typography */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Typography</label>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '16px', flexWrap: 'wrap', alignItems: 'flex-end' }}>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Title Size</span>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                            <input
                                                type="range"
                                                min={24}
                                                max={72}
                                                value={cardDesign.title_font_size}
                                                onChange={(e) => setCardDesign({ ...cardDesign, title_font_size: parseInt(e.target.value) })}
                                                style={{ width: '100px' }}
                                            />
                                            <span style={{ fontSize: '12px', color: '#666', minWidth: '40px' }}>{cardDesign.title_font_size}px</span>
                                        </div>
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Title Weight</span>
                                        <select
                                            className="input"
                                            value={cardDesign.title_weight || 600}
                                            onChange={(e) => setCardDesign({ ...cardDesign, title_weight: parseInt(e.target.value) })}
                                            style={{ width: '100px', fontSize: '12px' }}
                                        >
                                            <option value={400}>Regular</option>
                                            <option value={500}>Medium</option>
                                            <option value={600}>Semibold</option>
                                            <option value={700}>Bold</option>
                                        </select>
                                    </div>
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                        <span style={{ fontSize: '12px', color: '#666' }}>Site Name Size</span>
                                        <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                            <input
                                                type="range"
                                                min={12}
                                                max={32}
                                                value={cardDesign.site_font_size}
                                                onChange={(e) => setCardDesign({ ...cardDesign, site_font_size: parseInt(e.target.value) })}
                                                style={{ width: '100px' }}
                                            />
                                            <span style={{ fontSize: '12px', color: '#666', minWidth: '40px' }}>{cardDesign.site_font_size}px</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Logo Settings */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Logo</label>
                                <p className="settings-help">PNG with transparency works best.</p>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '12px', alignItems: 'flex-start', flexWrap: 'wrap' }}>
                                    {cardDesign.logo_url && (
                                        <div style={{
                                            width: '60px',
                                            height: '60px',
                                            border: '1px solid #ddd',
                                            borderRadius: '6px',
                                            display: 'flex',
                                            alignItems: 'center',
                                            justifyContent: 'center',
                                            background: '#f5f5f5',
                                            overflow: 'hidden',
                                        }}>
                                            <img
                                                src={cardDesign.logo_url}
                                                alt="Logo preview"
                                                style={{ maxWidth: '100%', maxHeight: '100%', objectFit: 'contain' }}
                                            />
                                        </div>
                                    )}
                                    <div style={{ display: 'flex', flexDirection: 'column', gap: '8px' }}>
                                        <div style={{ display: 'flex', gap: '8px' }}>
                                            <button
                                                type="button"
                                                className="button"
                                                onClick={() => {
                                                    const frame = wp.media({
                                                        title: 'Select Logo',
                                                        button: { text: 'Use Logo' },
                                                        multiple: false,
                                                        library: { type: 'image' },
                                                    });
                                                    frame.on('select', () => {
                                                        const attachment = frame.state().get('selection').first().toJSON();
                                                        setCardDesign({ ...cardDesign, logo_url: attachment.url });
                                                    });
                                                    frame.open();
                                                }}
                                            >
                                                {cardDesign.logo_url ? 'Change Logo' : 'Select Logo'}
                                            </button>
                                            {cardDesign.logo_url && (
                                                <button
                                                    type="button"
                                                    className="button"
                                                    onClick={() => setCardDesign({ ...cardDesign, logo_url: '' })}
                                                    style={{ color: '#b32d2e' }}
                                                >
                                                    Remove
                                                </button>
                                            )}
                                        </div>
                                        {cardDesign.logo_url && (
                                            <div style={{ display: 'flex', gap: '12px', alignItems: 'center' }}>
                                                <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                                    <span style={{ fontSize: '12px', color: '#666' }}>Size</span>
                                                    <div style={{ display: 'flex', alignItems: 'center', gap: '8px' }}>
                                                        <input
                                                            type="range"
                                                            min={24}
                                                            max={120}
                                                            value={cardDesign.logo_size || 48}
                                                            onChange={(e) => setCardDesign({ ...cardDesign, logo_size: parseInt(e.target.value) })}
                                                            style={{ width: '80px' }}
                                                        />
                                                        <span style={{ fontSize: '12px', color: '#666' }}>{cardDesign.logo_size || 48}px</span>
                                                    </div>
                                                </div>
                                                <div style={{ display: 'flex', flexDirection: 'column', gap: '4px' }}>
                                                    <span style={{ fontSize: '12px', color: '#666' }}>Position</span>
                                                    <select
                                                        className="input"
                                                        value={cardDesign.logo_position}
                                                        onChange={(e) => setCardDesign({ ...cardDesign, logo_position: e.target.value })}
                                                        style={{ width: '120px', fontSize: '12px' }}
                                                    >
                                                        {logoPositionOptions.map((opt) => (
                                                            <option key={opt.value} value={opt.value}>{opt.label}</option>
                                                        ))}
                                                    </select>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="form-actions">
                            <button
                                type="button"
                                className="button primary"
                                onClick={saveCardDesign}
                                disabled={saving}
                            >
                                {saving ? 'Saving...' : 'Save Card Design'}
                            </button>
                        </div>
                    </div>
                </section>
            )}
        </div>
    );
};

/**
 * Post Type Editor Component
 */
const PostTypeEditor = ({
    postType,
    schemaOptions,
    separator,
    siteInfo,
    variables,
    variableValues,
    onSave,
    onCancel,
    saving,
    renderTemplatePreview,
    openAiModal,
}) => {
    const [data, setData] = useState(postType);

    const previewTitle = renderTemplatePreview(data.title_template || '{{post_title}} {{separator}} {{site_title}}');
    const previewDescription = renderTemplatePreview(data.description_template || '{{post_excerpt}}');

    return (
        <div className="type-editor">
            <div className="type-editor__header">
                <h4>Edit: {postType.name}</h4>
                <button type="button" className="link-button" onClick={onCancel}>Cancel</button>
            </div>

            <SearchPreview
                title={previewTitle}
                description={previewDescription}
                domain={siteInfo.domain}
                favicon={siteInfo.favicon}
            />

            <div className="settings-form">
                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Show in Search Results</label>
                        <p className="settings-help">Allow search engines to index this content type.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={!data.noindex}
                                onChange={(e) => setData({ ...data, noindex: !e.target.checked })}
                            />
                            <span className="toggle-track" />
                            <span className="toggle-text">{data.noindex ? 'Hidden' : 'Visible'}</span>
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Title Template</label>
                        <p className="settings-help">Click "Variables" to insert dynamic content.</p>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.title_template}
                            onChange={(val) => setData({ ...data, title_template: val })}
                            placeholder="{{post_title}} {{separator}} {{site_title}}"
                            variables={variables}
                            variableValues={variableValues}
                            context="post"
                            maxLength={60}
                            onAiClick={() => openAiModal('title', (val) => setData({ ...data, title_template: val }), { type: 'Post Type', name: postType.name })}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Description Template</label>
                        <p className="settings-help">Default meta description for this post type.</p>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.description_template}
                            onChange={(val) => setData({ ...data, description_template: val })}
                            placeholder="{{post_excerpt}}"
                            variables={variables}
                            variableValues={variableValues}
                            context="post"
                            multiline
                            maxLength={160}
                            onAiClick={() => openAiModal('description', (val) => setData({ ...data, description_template: val }), { type: 'Post Type', name: postType.name })}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Default Schema Type</label>
                        <p className="settings-help">Schema type used for posts of this type unless overridden per-post.</p>
                    </div>
                    <div className="settings-control">
                        <select
                            value={data.schema_type || ''}
                            onChange={(e) => setData({ ...data, schema_type: e.target.value })}
                        >
                            {schemaTypeOptions.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Schema Page Type</label>
                        <p className="settings-help">Default structured data page type.</p>
                    </div>
                    <div className="settings-control">
                        <select
                            value={data.schema_page}
                            onChange={(e) => setData({ ...data, schema_page: e.target.value })}
                        >
                            {Object.entries(schemaOptions.page).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Schema Article Type</label>
                        <p className="settings-help">Default structured data article type.</p>
                    </div>
                    <div className="settings-control">
                        <select
                            value={data.schema_article}
                            onChange={(e) => setData({ ...data, schema_article: e.target.value })}
                        >
                            {Object.entries(schemaOptions.article).map(([value, label]) => (
                                <option key={value} value={value}>{label}</option>
                            ))}
                        </select>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Show SEO Controls</label>
                        <p className="settings-help">Show SEO meta box in editor for this post type.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={data.show_seo_controls}
                                onChange={(e) => setData({ ...data, show_seo_controls: e.target.checked })}
                            />
                            <span className="toggle-track" />
                            <span className="toggle-text">{data.show_seo_controls ? 'Enabled' : 'Disabled'}</span>
                        </label>
                    </div>
                </div>

                <div className="form-actions">
                    <button
                        type="button"
                        className="button primary"
                        onClick={() => onSave(data)}
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                    <button type="button" className="button ghost" onClick={onCancel}>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
};

/**
 * Taxonomy Editor Component
 */
const TaxonomyEditor = ({
    taxonomy,
    separator,
    siteInfo,
    variables,
    variableValues,
    onSave,
    onCancel,
    saving,
    renderTemplatePreview,
    openAiModal,
}) => {
    const [data, setData] = useState(taxonomy);

    const previewTitle = renderTemplatePreview(data.title_template || '{{term_title}} Archives {{separator}} {{site_title}}');
    const previewDescription = renderTemplatePreview(data.description_template || '{{term_description}}');

    return (
        <div className="type-editor">
            <div className="type-editor__header">
                <h4>Edit: {taxonomy.name}</h4>
                <button type="button" className="link-button" onClick={onCancel}>Cancel</button>
            </div>

            <SearchPreview
                title={previewTitle}
                description={previewDescription}
                domain={siteInfo.domain}
                favicon={siteInfo.favicon}
            />

            <div className="settings-form">
                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Show in Search Results</label>
                        <p className="settings-help">Allow search engines to index this taxonomy.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={!data.noindex}
                                onChange={(e) => setData({ ...data, noindex: !e.target.checked })}
                            />
                            <span className="toggle-track" />
                            <span className="toggle-text">{data.noindex ? 'Hidden' : 'Visible'}</span>
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Title Template</label>
                        <p className="settings-help">Click "Variables" to insert dynamic content.</p>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.title_template}
                            onChange={(val) => setData({ ...data, title_template: val })}
                            placeholder="{{term_title}} Archives {{separator}} {{site_title}}"
                            variables={variables}
                            variableValues={variableValues}
                            context="taxonomy"
                            maxLength={60}
                            onAiClick={() => openAiModal('title', (val) => setData({ ...data, title_template: val }), { type: 'Taxonomy', name: taxonomy.name })}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Description Template</label>
                        <p className="settings-help">Default meta description for taxonomy archives.</p>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.description_template}
                            onChange={(val) => setData({ ...data, description_template: val })}
                            placeholder="{{term_description}}"
                            variables={variables}
                            variableValues={variableValues}
                            context="taxonomy"
                            multiline
                            maxLength={160}
                            onAiClick={() => openAiModal('description', (val) => setData({ ...data, description_template: val }), { type: 'Taxonomy', name: taxonomy.name })}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Show SEO Controls</label>
                        <p className="settings-help">Show SEO fields when editing terms.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={data.show_seo_controls}
                                onChange={(e) => setData({ ...data, show_seo_controls: e.target.checked })}
                            />
                            <span className="toggle-track" />
                            <span className="toggle-text">{data.show_seo_controls ? 'Enabled' : 'Disabled'}</span>
                        </label>
                    </div>
                </div>

                <div className="form-actions">
                    <button
                        type="button"
                        className="button primary"
                        onClick={() => onSave(data)}
                        disabled={saving}
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                    <button type="button" className="button ghost" onClick={onCancel}>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
};

/**
 * Archive Editor Component
 */
const ArchiveEditor = ({
    archive,
    separator,
    siteInfo,
    variables,
    variableValues,
    onSave,
    onCancel,
    renderTemplatePreview,
    openAiModal,
}) => {
    const [data, setData] = useState(archive);

    // Get context for this archive type
    const getArchiveContext = () => {
        switch (archive.slug) {
            case 'author': return 'author';
            case 'date': return 'archive';
            case 'search': return 'archive';
            case '404': return 'archive';
            default: return 'global';
        }
    };

    const previewTitle = renderTemplatePreview(data.title_template);
    const previewDescription = renderTemplatePreview(data.description_template);

    return (
        <div className="type-editor">
            <div className="type-editor__header">
                <h4>Edit: {archive.name}</h4>
                <button type="button" className="link-button" onClick={onCancel}>Cancel</button>
            </div>

            <SearchPreview
                title={previewTitle}
                description={previewDescription}
                domain={siteInfo.domain}
                favicon={siteInfo.favicon}
            />

            <div className="settings-form">
                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Show in Search Results</label>
                        <p className="settings-help">Allow search engines to index this page type.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                type="checkbox"
                                checked={!data.noindex}
                                onChange={(e) => setData({ ...data, noindex: !e.target.checked })}
                            />
                            <span className="toggle-track" />
                            <span className="toggle-text">{data.noindex ? 'Hidden' : 'Visible'}</span>
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Title Template</label>
                        <p className="settings-help">Click "Variables" to insert dynamic content.</p>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.title_template}
                            onChange={(val) => setData({ ...data, title_template: val })}
                            variables={variables}
                            variableValues={variableValues}
                            context={getArchiveContext()}
                            maxLength={60}
                            onAiClick={() => openAiModal('title', (val) => setData({ ...data, title_template: val }), { type: 'Archive', name: archive.name })}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Description Template</label>
                    </div>
                    <div className="settings-control">
                        <TemplateInput
                            value={data.description_template}
                            onChange={(val) => setData({ ...data, description_template: val })}
                            variables={variables}
                            variableValues={variableValues}
                            context={getArchiveContext()}
                            multiline
                            maxLength={160}
                            onAiClick={() => openAiModal('description', (val) => setData({ ...data, description_template: val }), { type: 'Archive', name: archive.name })}
                        />
                    </div>
                </div>

                <div className="form-actions">
                    <button
                        type="button"
                        className="button primary"
                        onClick={() => onSave(data)}
                    >
                        Save Changes
                    </button>
                    <button type="button" className="button ghost" onClick={onCancel}>
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    );
};

export default SearchAppearance;
