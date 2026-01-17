import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import SearchPreview from '../components/SearchPreview';
import TemplateInput from '../components/TemplateInput';
import AiGenerateModal from '../components/AiGenerateModal';
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

// Social card layout options
const cardLayoutOptions = [
    { value: 'default', label: 'Default', description: 'Title with accent bar at bottom' },
    { value: 'centered', label: 'Centered', description: 'Centered text layout' },
    { value: 'minimal', label: 'Minimal', description: 'Text only, no accent' },
    { value: 'bold', label: 'Bold', description: 'Large accent block' },
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
        title_font_size: 48,
        site_font_size: 24,
        logo_url: '',
        logo_position: 'bottom-left',
        layout: 'default',
    });
    const [cardPreviewTitle, setCardPreviewTitle] = useState('Sample Post Title - Understanding Core Web Vitals');
    const [cardModuleEnabled, setCardModuleEnabled] = useState(true);

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
                    title_font_size: 48,
                    site_font_size: 24,
                    logo_url: '',
                    logo_position: 'bottom-left',
                    layout: 'default',
                });
                setCardModuleEnabled(data.card_module_enabled !== false);
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
                                    return (
                                        <tr key={pt.slug}>
                                            <td>
                                                <strong>{pt.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{pt.slug}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template)}
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
                                    return (
                                        <tr key={tax.slug}>
                                            <td>
                                                <strong>{tax.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{tax.slug}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template)}
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
                                    return (
                                        <tr key={archive.slug}>
                                            <td>
                                                <strong>{archive.name}</strong>
                                                <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{archive.description}</span>
                                            </td>
                                            <td>
                                                <div className="title-preview-cell">
                                                    <span className="title-preview-cell__title">
                                                        {renderTemplatePreview(template)}
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
                                    <label>Image URL</label>
                                    <p className="settings-help">Fallback image for social sharing.</p>
                                </div>
                                <div className="settings-control">
                                    <input
                                        type="url"
                                        className="input"
                                        value={editingPostTypeSocial.image_source || ''}
                                        onChange={(e) => setEditingPostTypeSocial({ ...editingPostTypeSocial, image_source: e.target.value })}
                                        placeholder="https://example.com/image.jpg"
                                    />
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
                                        <label>Fallback Image URL</label>
                                        <p className="settings-help">Used when posts don't have a featured image (1200x630px recommended).</p>
                                    </div>
                                    <div className="settings-control">
                                        <input
                                            type="url"
                                            className="input"
                                            value={socialDefaults.image_source || ''}
                                            onChange={(e) => setSocialDefaults({ ...socialDefaults, image_source: e.target.value })}
                                            placeholder="https://example.com/default-social.jpg"
                                        />
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
                        <div style={{ marginBottom: '32px', padding: '24px', background: '#f8f9fa', borderRadius: '8px' }}>
                            <h4 style={{ marginBottom: '16px' }}>Live Preview</h4>
                            <div style={{ display: 'flex', gap: '16px', marginBottom: '16px', alignItems: 'flex-end' }}>
                                <div style={{ flex: 1 }}>
                                    <label style={{ display: 'block', marginBottom: '8px', fontSize: '14px', fontWeight: 500 }}>
                                        Sample Title
                                    </label>
                                    <input
                                        type="text"
                                        className="input"
                                        value={cardPreviewTitle}
                                        onChange={(e) => setCardPreviewTitle(e.target.value)}
                                    />
                                </div>
                            </div>
                            <div
                                className="social-card-preview"
                                style={{
                                    width: '100%',
                                    maxWidth: '600px',
                                    aspectRatio: '1200/630',
                                    background: cardDesign.background_color,
                                    borderRadius: '8px',
                                    display: 'flex',
                                    flexDirection: 'column',
                                    justifyContent: cardDesign.layout === 'centered' ? 'center' : 'flex-end',
                                    alignItems: cardDesign.layout === 'centered' ? 'center' : 'flex-start',
                                    padding: '32px',
                                    position: 'relative',
                                    overflow: 'hidden',
                                }}
                            >
                                {cardDesign.layout !== 'minimal' && (
                                    <div
                                        style={{
                                            position: 'absolute',
                                            bottom: 0,
                                            left: 0,
                                            right: 0,
                                            height: cardDesign.layout === 'bold' ? '30%' : '6px',
                                            background: cardDesign.accent_color,
                                        }}
                                    />
                                )}
                                {cardDesign.logo_url && (
                                    <img
                                        src={cardDesign.logo_url}
                                        alt="Logo"
                                        style={{
                                            position: 'absolute',
                                            width: '48px',
                                            height: '48px',
                                            objectFit: 'contain',
                                            ...(cardDesign.logo_position === 'top-left' && { top: '24px', left: '24px' }),
                                            ...(cardDesign.logo_position === 'top-right' && { top: '24px', right: '24px' }),
                                            ...(cardDesign.logo_position === 'bottom-left' && { bottom: '24px', left: '24px' }),
                                            ...(cardDesign.logo_position === 'bottom-right' && { bottom: '24px', right: '24px' }),
                                            ...(cardDesign.logo_position === 'center' && { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' }),
                                        }}
                                    />
                                )}
                                <h2
                                    style={{
                                        color: cardDesign.text_color,
                                        fontSize: `${Math.max(16, cardDesign.title_font_size / 3)}px`,
                                        fontWeight: 700,
                                        margin: 0,
                                        marginBottom: '8px',
                                        textAlign: cardDesign.layout === 'centered' ? 'center' : 'left',
                                        zIndex: 1,
                                    }}
                                >
                                    {cardPreviewTitle}
                                </h2>
                                <span
                                    style={{
                                        color: cardDesign.text_color,
                                        fontSize: `${Math.max(10, cardDesign.site_font_size / 3)}px`,
                                        opacity: 0.8,
                                        zIndex: 1,
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
                                <p className="settings-help">Choose the overall layout for social cards.</p>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '12px', flexWrap: 'wrap' }}>
                                    {cardLayoutOptions.map((layout) => (
                                        <label
                                            key={layout.value}
                                            style={{
                                                display: 'flex',
                                                flexDirection: 'column',
                                                padding: '12px 16px',
                                                border: `2px solid ${cardDesign.layout === layout.value ? '#2271b1' : '#ddd'}`,
                                                borderRadius: '8px',
                                                cursor: 'pointer',
                                                background: cardDesign.layout === layout.value ? '#f0f6fc' : '#fff',
                                                minWidth: '120px',
                                            }}
                                        >
                                            <input
                                                type="radio"
                                                name="card-layout"
                                                value={layout.value}
                                                checked={cardDesign.layout === layout.value}
                                                onChange={(e) => setCardDesign({ ...cardDesign, layout: e.target.value })}
                                                style={{ display: 'none' }}
                                            />
                                            <strong style={{ fontSize: '14px' }}>{layout.label}</strong>
                                            <span style={{ fontSize: '12px', color: '#666' }}>{layout.description}</span>
                                        </label>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Colors */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Background Color</label>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                    <input
                                        type="color"
                                        value={cardDesign.background_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, background_color: e.target.value })}
                                        style={{ width: '48px', height: '36px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer' }}
                                    />
                                    <input
                                        type="text"
                                        className="input"
                                        value={cardDesign.background_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, background_color: e.target.value })}
                                        style={{ width: '100px' }}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Accent Color</label>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                    <input
                                        type="color"
                                        value={cardDesign.accent_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, accent_color: e.target.value })}
                                        style={{ width: '48px', height: '36px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer' }}
                                    />
                                    <input
                                        type="text"
                                        className="input"
                                        value={cardDesign.accent_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, accent_color: e.target.value })}
                                        style={{ width: '100px' }}
                                    />
                                </div>
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Text Color</label>
                            </div>
                            <div className="settings-control">
                                <div style={{ display: 'flex', gap: '8px', alignItems: 'center' }}>
                                    <input
                                        type="color"
                                        value={cardDesign.text_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, text_color: e.target.value })}
                                        style={{ width: '48px', height: '36px', border: '1px solid #ddd', borderRadius: '4px', cursor: 'pointer' }}
                                    />
                                    <input
                                        type="text"
                                        className="input"
                                        value={cardDesign.text_color}
                                        onChange={(e) => setCardDesign({ ...cardDesign, text_color: e.target.value })}
                                        style={{ width: '100px' }}
                                    />
                                </div>
                            </div>
                        </div>

                        {/* Font Sizes */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Title Font Size (px)</label>
                            </div>
                            <div className="settings-control">
                                <input
                                    type="number"
                                    className="input"
                                    value={cardDesign.title_font_size}
                                    onChange={(e) => setCardDesign({ ...cardDesign, title_font_size: parseInt(e.target.value) || 48 })}
                                    min={24}
                                    max={96}
                                    style={{ width: '100px' }}
                                />
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Site Name Font Size (px)</label>
                            </div>
                            <div className="settings-control">
                                <input
                                    type="number"
                                    className="input"
                                    value={cardDesign.site_font_size}
                                    onChange={(e) => setCardDesign({ ...cardDesign, site_font_size: parseInt(e.target.value) || 24 })}
                                    min={12}
                                    max={48}
                                    style={{ width: '100px' }}
                                />
                            </div>
                        </div>

                        {/* Logo Settings */}
                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Logo URL</label>
                                <p className="settings-help">Upload a logo to display on social cards (200x200px recommended).</p>
                            </div>
                            <div className="settings-control">
                                <input
                                    type="url"
                                    className="input"
                                    value={cardDesign.logo_url}
                                    onChange={(e) => setCardDesign({ ...cardDesign, logo_url: e.target.value })}
                                    placeholder="https://example.com/logo.png"
                                />
                            </div>
                        </div>

                        <div className="settings-row compact">
                            <div className="settings-label">
                                <label>Logo Position</label>
                            </div>
                            <div className="settings-control">
                                <select
                                    className="input"
                                    value={cardDesign.logo_position}
                                    onChange={(e) => setCardDesign({ ...cardDesign, logo_position: e.target.value })}
                                >
                                    {logoPositionOptions.map((opt) => (
                                        <option key={opt.value} value={opt.value}>{opt.label}</option>
                                    ))}
                                </select>
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
