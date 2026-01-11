import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import SearchPreview from '../components/SearchPreview';
import TemplateInput from '../components/TemplateInput';
import useUrlTab from '../hooks/useUrlTab';

const searchAppearanceTabs = [
    { id: 'homepage', label: 'Homepage' },
    { id: 'content-types', label: 'Content Types' },
    { id: 'taxonomies', label: 'Taxonomies' },
    { id: 'archives', label: 'Archives' },
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

    // Fetch all data on mount
    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const response = await apiFetch({ path: '/wpseopilot/v2/search-appearance' });
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
                path: '/wpseopilot/v2/search-appearance/homepage',
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
                path: '/wpseopilot/v2/search-appearance/separator',
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
                path: `/wpseopilot/v2/search-appearance/post-types/${postType.slug}`,
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
                path: `/wpseopilot/v2/search-appearance/taxonomies/${taxonomy.slug}`,
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
                path: '/wpseopilot/v2/search-appearance/archives',
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
                                {postTypes.map((pt) => (
                                    <tr key={pt.slug}>
                                        <td>
                                            <strong>{pt.name}</strong>
                                            <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{pt.slug}</span>
                                        </td>
                                        <td>
                                            <span className="template-preview-text">
                                                {renderTemplatePreview(pt.title_template || '{{post_title}} {{separator}} {{site_title}}')}
                                            </span>
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
                                ))}
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
                                {taxonomies.map((tax) => (
                                    <tr key={tax.slug}>
                                        <td>
                                            <strong>{tax.name}</strong>
                                            <span className="muted" style={{ display: 'block', fontSize: '12px' }}>{tax.slug}</span>
                                        </td>
                                        <td>
                                            <span className="template-preview-text">
                                                {renderTemplatePreview(tax.title_template || '{{term_title}} {{separator}} {{site_title}}')}
                                            </span>
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
                                ))}
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
                        />
                    ) : (
                        <>
                            <div className="archives-grid">
                                {archives.map((archive) => (
                                    <div key={archive.slug} className="archive-card">
                                        <div className="archive-card__header">
                                            <h4>{archive.name}</h4>
                                            <span className={`pill ${archive.noindex ? 'warning' : 'success'}`}>
                                                {archive.noindex ? 'Noindex' : 'Indexed'}
                                            </span>
                                        </div>
                                        <p className="muted">{archive.description}</p>
                                        <div className="archive-card__preview">
                                            <span className="template-preview-text">
                                                {renderTemplatePreview(archive.title_template)}
                                            </span>
                                        </div>
                                        <button
                                            type="button"
                                            className="link-button"
                                            onClick={() => setEditingArchive({ ...archive })}
                                        >
                                            Edit Settings
                                        </button>
                                    </div>
                                ))}
                            </div>
                            <div className="form-actions">
                                <button
                                    type="button"
                                    className="button primary"
                                    onClick={saveArchives}
                                    disabled={saving}
                                >
                                    {saving ? 'Saving...' : 'Save Archive Settings'}
                                </button>
                            </div>
                        </>
                    )}
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
