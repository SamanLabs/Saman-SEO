import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const linkingTabs = [
    { id: 'rules', label: 'Rules' },
    { id: 'categories', label: 'Categories' },
    { id: 'utm-templates', label: 'UTM Templates' },
    { id: 'settings', label: 'Settings' },
];

const HEADING_LEVELS = ['h1', 'h2', 'h3', 'h4', 'h5', 'h6'];

const InternalLinking = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: linkingTabs, defaultTab: 'rules' });

    // Rules state
    const [rules, setRules] = useState([]);
    const [rulesLoading, setRulesLoading] = useState(true);
    const [categories, setCategories] = useState([]);
    const [templates, setTemplates] = useState([]);
    const [settings, setSettings] = useState({});
    const [stats, setStats] = useState({ total_rules: 0, active_rules: 0, categories: 0, utm_templates: 0 });

    // Filters
    const [filterStatus, setFilterStatus] = useState('');
    const [filterCategory, setFilterCategory] = useState('');
    const [filterSearch, setFilterSearch] = useState('');

    // Bulk selection
    const [selectedRules, setSelectedRules] = useState([]);
    const [bulkAction, setBulkAction] = useState('');
    const [bulkCategory, setBulkCategory] = useState('');

    // Modal states
    const [ruleModalOpen, setRuleModalOpen] = useState(false);
    const [editingRule, setEditingRule] = useState(null);
    const [categoryModalOpen, setCategoryModalOpen] = useState(false);
    const [editingCategory, setEditingCategory] = useState(null);
    const [templateModalOpen, setTemplateModalOpen] = useState(false);
    const [editingTemplate, setEditingTemplate] = useState(null);

    // Fetch all data
    const fetchData = useCallback(async () => {
        setRulesLoading(true);
        try {
            const [rulesRes, categoriesRes, templatesRes, settingsRes, statsRes] = await Promise.all([
                apiFetch({ path: '/saman-seo/v1/internal-links/rules' }),
                apiFetch({ path: '/saman-seo/v1/internal-links/categories' }),
                apiFetch({ path: '/saman-seo/v1/internal-links/templates' }),
                apiFetch({ path: '/saman-seo/v1/internal-links/settings' }),
                apiFetch({ path: '/saman-seo/v1/internal-links/stats' }),
            ]);

            if (rulesRes.success) setRules(rulesRes.data);
            if (categoriesRes.success) setCategories(categoriesRes.data);
            if (templatesRes.success) setTemplates(templatesRes.data);
            if (settingsRes.success) setSettings(settingsRes.data);
            if (statsRes.success) setStats(statsRes.data);
        } catch (error) {
            console.error('Failed to fetch internal linking data:', error);
        } finally {
            setRulesLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Filter rules
    const filteredRules = rules.filter(rule => {
        if (filterStatus && rule.status !== filterStatus) return false;
        if (filterCategory && rule.category !== filterCategory) return false;
        if (filterSearch) {
            const search = filterSearch.toLowerCase();
            const inTitle = (rule.title || '').toLowerCase().includes(search);
            const inKeywords = (rule.keywords || []).some(k => k.toLowerCase().includes(search));
            if (!inTitle && !inKeywords) return false;
        }
        return true;
    });

    // Get category by ID
    const getCategoryById = (id) => categories.find(c => c.id === id);
    const getTemplateById = (id) => templates.find(t => t.id === id);

    // Rule actions
    const handleDeleteRule = async (id) => {
        if (!window.confirm('Are you sure you want to delete this rule?')) return;
        try {
            await apiFetch({ path: `/saman-seo/v1/internal-links/rules/${id}`, method: 'DELETE' });
            setRules(rules.filter(r => r.id !== id));
            setSelectedRules(selectedRules.filter(rid => rid !== id));
        } catch (error) {
            console.error('Failed to delete rule:', error);
        }
    };

    const handleToggleRule = async (id) => {
        try {
            const res = await apiFetch({ path: `/saman-seo/v1/internal-links/rules/${id}/toggle`, method: 'POST' });
            if (res.success) {
                setRules(rules.map(r => r.id === id ? res.data : r));
            }
        } catch (error) {
            console.error('Failed to toggle rule:', error);
        }
    };

    const handleDuplicateRule = async (id) => {
        try {
            const res = await apiFetch({ path: `/saman-seo/v1/internal-links/rules/${id}/duplicate`, method: 'POST' });
            if (res.success) {
                setRules([res.data, ...rules]);
            }
        } catch (error) {
            console.error('Failed to duplicate rule:', error);
        }
    };

    const handleBulkAction = async () => {
        if (!bulkAction || selectedRules.length === 0) return;

        try {
            const payload = { ids: selectedRules, action: bulkAction };
            if (bulkAction === 'change_category') {
                payload.category = bulkCategory;
            }

            await apiFetch({
                path: '/saman-seo/v1/internal-links/rules/bulk',
                method: 'POST',
                data: payload,
            });

            setSelectedRules([]);
            setBulkAction('');
            setBulkCategory('');
            fetchData();
        } catch (error) {
            console.error('Failed to perform bulk action:', error);
        }
    };

    const handleSelectAll = (e) => {
        if (e.target.checked) {
            setSelectedRules(filteredRules.map(r => r.id));
        } else {
            setSelectedRules([]);
        }
    };

    const handleSelectRule = (id) => {
        if (selectedRules.includes(id)) {
            setSelectedRules(selectedRules.filter(rid => rid !== id));
        } else {
            setSelectedRules([...selectedRules, id]);
        }
    };

    // Category actions
    const handleDeleteCategory = async (id) => {
        const category = getCategoryById(id);
        if (!category) return;

        const rulesInCategory = rules.filter(r => r.category === id).length;
        let reassign = null;

        if (rulesInCategory > 0) {
            const message = `This category has ${rulesInCategory} rule(s). Delete anyway and remove category from rules?`;
            if (!window.confirm(message)) return;
            reassign = '__none__';
        } else {
            if (!window.confirm('Are you sure you want to delete this category?')) return;
        }

        try {
            await apiFetch({
                path: `/saman-seo/v1/internal-links/categories/${id}`,
                method: 'DELETE',
                data: reassign ? { reassign } : undefined,
            });
            setCategories(categories.filter(c => c.id !== id));
            if (reassign) {
                setRules(rules.map(r => r.category === id ? { ...r, category: '' } : r));
            }
        } catch (error) {
            console.error('Failed to delete category:', error);
        }
    };

    // Template actions
    const handleDeleteTemplate = async (id) => {
        if (!window.confirm('Are you sure you want to delete this UTM template?')) return;
        try {
            await apiFetch({ path: `/saman-seo/v1/internal-links/templates/${id}`, method: 'DELETE' });
            setTemplates(templates.filter(t => t.id !== id));
        } catch (error) {
            console.error('Failed to delete template:', error);
        }
    };

    // Save settings
    const handleSaveSettings = async () => {
        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/internal-links/settings',
                method: 'POST',
                data: settings,
            });
            if (res.success) {
                setSettings(res.data);
            }
        } catch (error) {
            console.error('Failed to save settings:', error);
        }
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Internal Linking</h1>
                    <p>Automatically add internal links to your content based on keywords.</p>
                </div>
                <div className="page-header__stats">
                    <div className="stat-chip">
                        <span className="stat-chip__value">{stats.active_rules}</span>
                        <span className="stat-chip__label">Active Rules</span>
                    </div>
                    <div className="stat-chip">
                        <span className="stat-chip__value">{stats.categories}</span>
                        <span className="stat-chip__label">Categories</span>
                    </div>
                </div>
            </div>

            <SubTabs tabs={linkingTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="Internal linking sections" />

            {activeTab === 'rules' && (
                <RulesTab
                    rules={filteredRules}
                    rulesLoading={rulesLoading}
                    categories={categories}
                    templates={templates}
                    selectedRules={selectedRules}
                    filterStatus={filterStatus}
                    filterCategory={filterCategory}
                    filterSearch={filterSearch}
                    bulkAction={bulkAction}
                    bulkCategory={bulkCategory}
                    onFilterStatus={setFilterStatus}
                    onFilterCategory={setFilterCategory}
                    onFilterSearch={setFilterSearch}
                    onSelectAll={handleSelectAll}
                    onSelectRule={handleSelectRule}
                    onEditRule={(rule) => { setEditingRule(rule); setRuleModalOpen(true); }}
                    onDeleteRule={handleDeleteRule}
                    onToggleRule={handleToggleRule}
                    onDuplicateRule={handleDuplicateRule}
                    onBulkAction={setBulkAction}
                    onBulkCategory={setBulkCategory}
                    onApplyBulk={handleBulkAction}
                    onAddRule={() => { setEditingRule(null); setRuleModalOpen(true); }}
                    getCategoryById={getCategoryById}
                    getTemplateById={getTemplateById}
                />
            )}

            {activeTab === 'categories' && (
                <CategoriesTab
                    categories={categories}
                    templates={templates}
                    onEdit={(cat) => { setEditingCategory(cat); setCategoryModalOpen(true); }}
                    onDelete={handleDeleteCategory}
                    onAdd={() => { setEditingCategory(null); setCategoryModalOpen(true); }}
                />
            )}

            {activeTab === 'utm-templates' && (
                <TemplatesTab
                    templates={templates}
                    onEdit={(tpl) => { setEditingTemplate(tpl); setTemplateModalOpen(true); }}
                    onDelete={handleDeleteTemplate}
                    onAdd={() => { setEditingTemplate(null); setTemplateModalOpen(true); }}
                />
            )}

            {activeTab === 'settings' && (
                <SettingsTab
                    settings={settings}
                    onChange={setSettings}
                    onSave={handleSaveSettings}
                />
            )}

            {ruleModalOpen && (
                <RuleModal
                    rule={editingRule}
                    categories={categories}
                    templates={templates}
                    onClose={() => { setRuleModalOpen(false); setEditingRule(null); }}
                    onSave={(saved) => {
                        if (editingRule) {
                            setRules(rules.map(r => r.id === saved.id ? saved : r));
                        } else {
                            setRules([saved, ...rules]);
                        }
                        setRuleModalOpen(false);
                        setEditingRule(null);
                    }}
                />
            )}

            {categoryModalOpen && (
                <CategoryModal
                    category={editingCategory}
                    templates={templates}
                    onClose={() => { setCategoryModalOpen(false); setEditingCategory(null); }}
                    onSave={(saved) => {
                        if (editingCategory) {
                            setCategories(categories.map(c => c.id === saved.id ? saved : c));
                        } else {
                            setCategories([...categories, saved]);
                        }
                        setCategoryModalOpen(false);
                        setEditingCategory(null);
                    }}
                />
            )}

            {templateModalOpen && (
                <TemplateModal
                    template={editingTemplate}
                    onClose={() => { setTemplateModalOpen(false); setEditingTemplate(null); }}
                    onSave={(saved) => {
                        if (editingTemplate) {
                            setTemplates(templates.map(t => t.id === saved.id ? saved : t));
                        } else {
                            setTemplates([...templates, saved]);
                        }
                        setTemplateModalOpen(false);
                        setEditingTemplate(null);
                    }}
                />
            )}
        </div>
    );
};

// Rules Tab Component
const RulesTab = ({
    rules, rulesLoading, categories, templates, selectedRules,
    filterStatus, filterCategory, filterSearch, bulkAction, bulkCategory,
    onFilterStatus, onFilterCategory, onFilterSearch,
    onSelectAll, onSelectRule, onEditRule, onDeleteRule, onToggleRule, onDuplicateRule,
    onBulkAction, onBulkCategory, onApplyBulk, onAddRule,
    getCategoryById, getTemplateById
}) => {
    const clearFilters = () => {
        onFilterStatus('');
        onFilterCategory('');
        onFilterSearch('');
    };

    const hasFilters = filterStatus || filterCategory || filterSearch;

    return (
        <section className="panel">
            <div className="table-toolbar">
                <div>
                    <h3>Linking Rules</h3>
                    <p className="muted">Define keywords and their target URLs for automatic linking.</p>
                </div>
                <button type="button" className="button primary" onClick={onAddRule}>Add Rule</button>
            </div>

            <div className="filters-bar">
                <div className="filters-bar__filters">
                    <select value={filterStatus} onChange={(e) => onFilterStatus(e.target.value)}>
                        <option value="">All statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <select value={filterCategory} onChange={(e) => onFilterCategory(e.target.value)}>
                        <option value="">All categories</option>
                        {categories.map(cat => (
                            <option key={cat.id} value={cat.id}>{cat.name}</option>
                        ))}
                    </select>
                    <input
                        type="search"
                        placeholder="Search rules..."
                        value={filterSearch}
                        onChange={(e) => onFilterSearch(e.target.value)}
                    />
                    {hasFilters && (
                        <button type="button" className="link-button" onClick={clearFilters}>Clear</button>
                    )}
                </div>
            </div>

            {rulesLoading ? (
                <div className="loading-state">Loading rules...</div>
            ) : rules.length === 0 ? (
                <div className="empty-state">
                    <div className="empty-state__icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" width="48" height="48">
                            <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71"/>
                            <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"/>
                        </svg>
                    </div>
                    <h3>{hasFilters ? 'No rules match your filters' : 'No linking rules yet'}</h3>
                    <p>{hasFilters ? 'Try adjusting your filters.' : 'Create your first internal link rule to get started.'}</p>
                    {!hasFilters && (
                        <button type="button" className="button primary" onClick={onAddRule}>Create Rule</button>
                    )}
                </div>
            ) : (
                <>
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th className="check-column">
                                    <input
                                        type="checkbox"
                                        checked={selectedRules.length === rules.length && rules.length > 0}
                                        onChange={onSelectAll}
                                    />
                                </th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Keywords</th>
                                <th>Destination</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rules.map(rule => {
                                const category = getCategoryById(rule.category);
                                return (
                                    <tr key={rule.id}>
                                        <td className="check-column">
                                            <input
                                                type="checkbox"
                                                checked={selectedRules.includes(rule.id)}
                                                onChange={() => onSelectRule(rule.id)}
                                            />
                                        </td>
                                        <td>
                                            <strong>
                                                <button type="button" className="link-button" onClick={() => onEditRule(rule)}>
                                                    {rule.title}
                                                </button>
                                            </strong>
                                        </td>
                                        <td>
                                            {category ? (
                                                <span className="category-pill" style={{ '--pill-color': category.color }}>
                                                    {category.name}
                                                </span>
                                            ) : (
                                                <span className="muted">—</span>
                                            )}
                                        </td>
                                        <td>
                                            <span className="keywords-preview">
                                                {(rule.keywords || []).slice(0, 3).join(', ')}
                                                {(rule.keywords || []).length > 3 && (
                                                    <span className="muted"> +{rule.keywords.length - 3}</span>
                                                )}
                                            </span>
                                        </td>
                                        <td>
                                            {rule.destination_url ? (
                                                <a href={rule.destination_url} target="_blank" rel="noopener noreferrer" className="destination-link">
                                                    {rule.destination_label}
                                                </a>
                                            ) : (
                                                <span className="muted">—</span>
                                            )}
                                        </td>
                                        <td>
                                            <span className={`pill ${rule.status === 'active' ? 'success' : 'muted'}`}>
                                                {rule.status === 'active' ? 'Active' : 'Inactive'}
                                            </span>
                                        </td>
                                        <td>
                                            <div className="action-buttons">
                                                <button type="button" className="link-button" onClick={() => onEditRule(rule)}>Edit</button>
                                                <button type="button" className="link-button" onClick={() => onDuplicateRule(rule.id)}>Duplicate</button>
                                                <button type="button" className="link-button" onClick={() => onToggleRule(rule.id)}>
                                                    {rule.status === 'active' ? 'Deactivate' : 'Activate'}
                                                </button>
                                                <button type="button" className="link-button danger" onClick={() => onDeleteRule(rule.id)}>Delete</button>
                                            </div>
                                        </td>
                                    </tr>
                                );
                            })}
                        </tbody>
                    </table>

                    {selectedRules.length > 0 && (
                        <div className="bulk-actions">
                            <span className="bulk-actions__count">{selectedRules.length} selected</span>
                            <select value={bulkAction} onChange={(e) => onBulkAction(e.target.value)}>
                                <option value="">Bulk actions</option>
                                <option value="activate">Activate</option>
                                <option value="deactivate">Deactivate</option>
                                <option value="change_category">Change category</option>
                                <option value="delete">Delete</option>
                            </select>
                            {bulkAction === 'change_category' && (
                                <select value={bulkCategory} onChange={(e) => onBulkCategory(e.target.value)}>
                                    <option value="__none__">Remove category</option>
                                    {categories.map(cat => (
                                        <option key={cat.id} value={cat.id}>{cat.name}</option>
                                    ))}
                                </select>
                            )}
                            <button type="button" className="button" onClick={onApplyBulk} disabled={!bulkAction}>
                                Apply
                            </button>
                        </div>
                    )}
                </>
            )}
        </section>
    );
};

// Categories Tab Component
const CategoriesTab = ({ categories, templates, onEdit, onDelete, onAdd }) => {
    const getTemplateById = (id) => templates.find(t => t.id === id);

    return (
        <section className="panel">
            <div className="table-toolbar">
                <div>
                    <h3>Rule Categories</h3>
                    <p className="muted">Group rules, pick a color, and set per-category limits.</p>
                </div>
                <button type="button" className="button primary" onClick={onAdd}>Add Category</button>
            </div>

            {categories.length === 0 ? (
                <div className="empty-state">
                    <h3>No categories yet</h3>
                    <p>Create categories to organize your linking rules.</p>
                    <button type="button" className="button primary" onClick={onAdd}>Create Category</button>
                </div>
            ) : (
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Color</th>
                            <th>Default UTM</th>
                            <th>Cap</th>
                            <th>Rules</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {categories.map(cat => {
                            const template = getTemplateById(cat.default_utm);
                            return (
                                <tr key={cat.id}>
                                    <td>
                                        <strong>{cat.name}</strong>
                                        {cat.description && <div className="muted small">{cat.description}</div>}
                                    </td>
                                    <td>
                                        <span className="color-chip" style={{ backgroundColor: cat.color }} />
                                    </td>
                                    <td>{template ? template.name : <span className="muted">—</span>}</td>
                                    <td>{cat.category_cap || <span className="muted">—</span>}</td>
                                    <td>{cat.rule_count || 0}</td>
                                    <td>
                                        <div className="action-buttons">
                                            <button type="button" className="link-button" onClick={() => onEdit(cat)}>Edit</button>
                                            <button type="button" className="link-button danger" onClick={() => onDelete(cat.id)}>Delete</button>
                                        </div>
                                    </td>
                                </tr>
                            );
                        })}
                    </tbody>
                </table>
            )}
        </section>
    );
};

// Templates Tab Component
const TemplatesTab = ({ templates, onEdit, onDelete, onAdd }) => {
    const applyToLabel = (value) => {
        switch (value) {
            case 'internal': return 'Internal only';
            case 'external': return 'External only';
            default: return 'Both';
        }
    };

    const appendModeLabel = (value) => {
        switch (value) {
            case 'always_overwrite': return 'Always overwrite';
            case 'never': return 'Never overwrite';
            default: return 'Append if missing';
        }
    };

    return (
        <section className="panel">
            <div className="table-toolbar">
                <div>
                    <h3>UTM Templates</h3>
                    <p className="muted">Define reusable parameter sets for consistent tracking.</p>
                </div>
                <button type="button" className="button primary" onClick={onAdd}>Add Template</button>
            </div>

            {templates.length === 0 ? (
                <div className="empty-state">
                    <h3>No UTM templates yet</h3>
                    <p>Create templates to add tracking parameters to your links.</p>
                    <button type="button" className="button primary" onClick={onAdd}>Create Template</button>
                </div>
            ) : (
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>utm_source</th>
                            <th>utm_medium</th>
                            <th>utm_campaign</th>
                            <th>Apply to</th>
                            <th>Mode</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        {templates.map(tpl => (
                            <tr key={tpl.id}>
                                <td><strong>{tpl.name}</strong></td>
                                <td>{tpl.utm_source || <span className="muted">—</span>}</td>
                                <td>{tpl.utm_medium || <span className="muted">—</span>}</td>
                                <td>{tpl.utm_campaign || <span className="muted">—</span>}</td>
                                <td>{applyToLabel(tpl.apply_to)}</td>
                                <td>{appendModeLabel(tpl.append_mode)}</td>
                                <td>
                                    <div className="action-buttons">
                                        <button type="button" className="link-button" onClick={() => onEdit(tpl)}>Edit</button>
                                        <button type="button" className="link-button danger" onClick={() => onDelete(tpl.id)}>Delete</button>
                                    </div>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            )}
        </section>
    );
};

// Settings Tab Component
const SettingsTab = ({ settings, onChange, onSave }) => {
    const updateSetting = (key, value) => {
        onChange({ ...settings, [key]: value });
    };

    const toggleHeadingLevel = (level) => {
        const levels = settings.default_heading_levels || [];
        if (levels.includes(level)) {
            updateSetting('default_heading_levels', levels.filter(l => l !== level));
        } else {
            updateSetting('default_heading_levels', [...levels, level]);
        }
    };

    return (
        <section className="panel">
            <div className="table-toolbar">
                <div>
                    <h3>Module Settings</h3>
                    <p className="muted">Configure how internal links are applied to your content.</p>
                </div>
            </div>

            <div className="settings-section">
                <h4 className="settings-section__title">Global Defaults</h4>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="default-max-links">Default max links per page</label>
                        <p className="settings-help">0 means no limit.</p>
                    </div>
                    <div className="settings-control">
                        <input
                            id="default-max-links"
                            type="number"
                            min="0"
                            max="50"
                            value={settings.default_max_links_per_page || 0}
                            onChange={(e) => updateSetting('default_max_links_per_page', parseInt(e.target.value, 10) || 0)}
                            style={{ width: '80px' }}
                        />
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label>Default heading behavior</label>
                        <p className="settings-help">Control whether links can appear in headings.</p>
                    </div>
                    <div className="settings-control">
                        <div className="radio-group">
                            {['none', 'selected', 'all'].map(opt => (
                                <label key={opt} className="radio-item">
                                    <input
                                        type="radio"
                                        name="heading_behavior"
                                        value={opt}
                                        checked={settings.default_heading_behavior === opt}
                                        onChange={() => updateSetting('default_heading_behavior', opt)}
                                    />
                                    <span>{opt.charAt(0).toUpperCase() + opt.slice(1)}</span>
                                </label>
                            ))}
                        </div>
                        {settings.default_heading_behavior === 'selected' && (
                            <div className="checkbox-group" style={{ marginTop: '8px' }}>
                                {HEADING_LEVELS.map(level => (
                                    <label key={level} className="checkbox-item">
                                        <input
                                            type="checkbox"
                                            checked={(settings.default_heading_levels || []).includes(level)}
                                            onChange={() => toggleHeadingLevel(level)}
                                        />
                                        <span>{level.toUpperCase()}</span>
                                    </label>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            <div className="settings-section">
                <h4 className="settings-section__title">Safeties</h4>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="avoid-existing">Avoid replacing inside existing links</label>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                id="avoid-existing"
                                type="checkbox"
                                checked={settings.avoid_existing_links}
                                onChange={(e) => updateSetting('avoid_existing_links', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="word-boundaries">Prefer word boundaries</label>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                id="word-boundaries"
                                type="checkbox"
                                checked={settings.prefer_word_boundaries}
                                onChange={(e) => updateSetting('prefer_word_boundaries', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="normalize-accents">Normalize accents/diacritics</label>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                id="normalize-accents"
                                type="checkbox"
                                checked={settings.normalize_accents}
                                onChange={(e) => updateSetting('normalize_accents', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>
                </div>
            </div>

            <div className="settings-section">
                <h4 className="settings-section__title">Performance</h4>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="cache-content">Cache rendered content</label>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                id="cache-content"
                                type="checkbox"
                                checked={settings.cache_rendered_content}
                                onChange={(e) => updateSetting('cache_rendered_content', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>
                </div>

                <div className="settings-row compact">
                    <div className="settings-label">
                        <label htmlFor="chunk-docs">Chunk long documents</label>
                        <p className="settings-help">Prevents timeouts on large content.</p>
                    </div>
                    <div className="settings-control">
                        <label className="toggle">
                            <input
                                id="chunk-docs"
                                type="checkbox"
                                checked={settings.chunk_long_documents}
                                onChange={(e) => updateSetting('chunk_long_documents', e.target.checked)}
                            />
                            <span className="toggle-track" />
                        </label>
                    </div>
                </div>
            </div>

            <div className="panel-footer">
                <button type="button" className="button primary" onClick={onSave}>Save Settings</button>
            </div>
        </section>
    );
};

// Rule Modal Component - Simplified
const RuleModal = ({ rule, categories, templates, onClose, onSave }) => {
    const isEdit = !!rule;

    const [formData, setFormData] = useState(() => {
        if (rule) {
            return { ...rule };
        }
        return {
            title: '',
            category: '',
            keywords: [],
            destination: { type: 'post', post: 0, url: '' },
            utm_template: 'inherit',
            attributes: { nofollow: false, new_tab: false },
            limits: { max_page: 1 },
            status: 'active',
        };
    });

    const [keywordInput, setKeywordInput] = useState('');
    const [postSearchQuery, setPostSearchQuery] = useState('');
    const [postSearchResults, setPostSearchResults] = useState([]);
    const [selectedPost, setSelectedPost] = useState(null);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    useEffect(() => {
        if (rule && rule.destination?.type === 'post' && rule.destination?.post) {
            setSelectedPost({ id: rule.destination.post, title: rule.destination_label || 'Loading...' });
        }
    }, [rule]);

    useEffect(() => {
        if (postSearchQuery.length < 2) { setPostSearchResults([]); return; }
        const timer = setTimeout(async () => {
            try {
                const res = await apiFetch({ path: `/saman-seo/v1/internal-links/search-posts?search=${encodeURIComponent(postSearchQuery)}` });
                if (res.success) setPostSearchResults(res.data);
            } catch (err) { console.error('Post search failed:', err); }
        }, 300);
        return () => clearTimeout(timer);
    }, [postSearchQuery]);

    const updateFormData = (key, value) => setFormData(prev => ({ ...prev, [key]: value }));
    const updateNested = (parent, key, value) => setFormData(prev => ({ ...prev, [parent]: { ...prev[parent], [key]: value } }));

    const addKeyword = () => {
        const keyword = keywordInput.trim();
        if (keyword && !formData.keywords.includes(keyword)) {
            updateFormData('keywords', [...formData.keywords, keyword]);
            setKeywordInput('');
        }
    };

    const removeKeyword = (keyword) => updateFormData('keywords', formData.keywords.filter(k => k !== keyword));
    const handleKeywordKeyDown = (e) => { if (e.key === 'Enter') { e.preventDefault(); addKeyword(); } };

    const selectPost = (post) => {
        setSelectedPost(post);
        updateNested('destination', 'post', post.id);
        updateNested('destination', 'type', 'post');
        setPostSearchQuery('');
        setPostSearchResults([]);
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSaving(true);
        try {
            const path = isEdit ? `/saman-seo/v1/internal-links/rules/${rule.id}` : '/saman-seo/v1/internal-links/rules';
            const res = await apiFetch({ path, method: isEdit ? 'PUT' : 'POST', data: formData });
            if (res.success) { onSave(res.data); } else { setError(res.message || 'Failed to save rule'); }
        } catch (err) { setError(err.message || 'Failed to save rule'); }
        finally { setSaving(false); }
    };

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal modal--large" onClick={e => e.stopPropagation()}>
                <div className="modal__header">
                    <h2>{isEdit ? 'Edit Rule' : 'Add Rule'}</h2>
                    <button type="button" className="modal__close" onClick={onClose}>&times;</button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="modal__body">
                        {error && <div className="form-error">{error}</div>}

                        <div className="form-field">
                            <label htmlFor="rule-title">Title</label>
                            <input id="rule-title" type="text" value={formData.title} onChange={(e) => updateFormData('title', e.target.value)} placeholder="e.g., Link to Services page" required />
                        </div>

                        <div className="form-row">
                            <div className="form-field">
                                <label htmlFor="rule-category">Category</label>
                                <select id="rule-category" value={formData.category} onChange={(e) => updateFormData('category', e.target.value)}>
                                    <option value="">None</option>
                                    {categories.map(cat => <option key={cat.id} value={cat.id}>{cat.name}</option>)}
                                </select>
                            </div>
                            <div className="form-field">
                                <label>Status</label>
                                <label className="toggle">
                                    <input type="checkbox" checked={formData.status === 'active'} onChange={(e) => updateFormData('status', e.target.checked ? 'active' : 'inactive')} />
                                    <span className="toggle-track" />
                                </label>
                            </div>
                        </div>

                        <div className="form-field">
                            <label>Destination</label>
                            <div className="radio-group" style={{ marginBottom: '12px' }}>
                                <label className="radio-item">
                                    <input type="radio" name="destination_type" checked={formData.destination.type === 'post'} onChange={() => updateNested('destination', 'type', 'post')} />
                                    <span>Post/Page</span>
                                </label>
                                <label className="radio-item">
                                    <input type="radio" name="destination_type" checked={formData.destination.type === 'url'} onChange={() => updateNested('destination', 'type', 'url')} />
                                    <span>Custom URL</span>
                                </label>
                            </div>
                            {formData.destination.type === 'post' ? (
                                selectedPost ? (
                                    <div className="selected-post">
                                        <span>{selectedPost.title}</span>
                                        <button type="button" className="link-button" onClick={() => { setSelectedPost(null); updateNested('destination', 'post', 0); }}>Change</button>
                                    </div>
                                ) : (
                                    <div className="post-search">
                                        <input type="text" placeholder="Search posts..." value={postSearchQuery} onChange={(e) => setPostSearchQuery(e.target.value)} />
                                        {postSearchResults.length > 0 && (
                                            <ul className="post-search__results">
                                                {postSearchResults.map(post => (
                                                    <li key={post.id}><button type="button" onClick={() => selectPost(post)}>{post.title} <span className="muted">({post.post_type})</span></button></li>
                                                ))}
                                            </ul>
                                        )}
                                    </div>
                                )
                            ) : (
                                <input type="url" value={formData.destination.url} onChange={(e) => updateNested('destination', 'url', e.target.value)} placeholder="https://example.com" />
                            )}
                        </div>

                        <div className="form-field">
                            <label>Keywords</label>
                            <div className="tag-input">
                                <div className="tag-input__tags">
                                    {formData.keywords.map(keyword => (
                                        <span key={keyword} className="tag">{keyword}<button type="button" onClick={() => removeKeyword(keyword)}>&times;</button></span>
                                    ))}
                                </div>
                                <input type="text" placeholder="Type and press Enter" value={keywordInput} onChange={(e) => setKeywordInput(e.target.value)} onKeyDown={handleKeywordKeyDown} onBlur={addKeyword} />
                            </div>
                        </div>

                        <div className="form-row">
                            <div className="form-field narrow">
                                <label htmlFor="max-page">Max per page</label>
                                <input id="max-page" type="number" min="0" max="50" value={formData.limits?.max_page || 1} onChange={(e) => updateNested('limits', 'max_page', parseInt(e.target.value, 10) || 1)} />
                            </div>
                            <div className="form-field">
                                <label>Options</label>
                                <div className="checkbox-group">
                                    <label className="checkbox-item">
                                        <input type="checkbox" checked={formData.attributes?.nofollow} onChange={(e) => updateNested('attributes', 'nofollow', e.target.checked)} />
                                        <span>nofollow</span>
                                    </label>
                                    <label className="checkbox-item">
                                        <input type="checkbox" checked={formData.attributes?.new_tab} onChange={(e) => updateNested('attributes', 'new_tab', e.target.checked)} />
                                        <span>New tab</span>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="modal__footer">
                        <button type="button" className="button ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="button primary" disabled={saving}>{saving ? 'Saving...' : (isEdit ? 'Update' : 'Create')}</button>
                    </div>
                </form>
            </div>
        </div>
    );
};

// Category Modal Component
const CategoryModal = ({ category, templates, onClose, onSave }) => {
    const isEdit = !!category;

    const [formData, setFormData] = useState(() => {
        if (category) {
            return { ...category };
        }
        return {
            name: '',
            color: '#4F46E5',
            description: '',
            default_utm: '',
            category_cap: 0,
        };
    });

    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSaving(true);

        try {
            const path = isEdit
                ? `/saman-seo/v1/internal-links/categories/${category.id}`
                : '/saman-seo/v1/internal-links/categories';
            const method = isEdit ? 'PUT' : 'POST';

            const res = await apiFetch({ path, method, data: formData });

            if (res.success) {
                onSave(res.data);
            } else {
                setError(res.message || 'Failed to save category');
            }
        } catch (err) {
            setError(err.message || 'Failed to save category');
        } finally {
            setSaving(false);
        }
    };

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()}>
                <div className="modal__header">
                    <h2>{isEdit ? 'Edit Category' : 'Add Category'}</h2>
                    <button type="button" className="modal__close" onClick={onClose}>&times;</button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="modal__body">
                        {error && <div className="form-error">{error}</div>}

                        <div className="form-field">
                            <label htmlFor="cat-name">Name</label>
                            <input
                                id="cat-name"
                                type="text"
                                value={formData.name}
                                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                required
                            />
                        </div>

                        <div className="form-field">
                            <label htmlFor="cat-color">Color</label>
                            <input
                                id="cat-color"
                                type="color"
                                value={formData.color}
                                onChange={(e) => setFormData({ ...formData, color: e.target.value })}
                            />
                        </div>

                        <div className="form-field">
                            <label htmlFor="cat-desc">Description</label>
                            <textarea
                                id="cat-desc"
                                rows="3"
                                value={formData.description}
                                onChange={(e) => setFormData({ ...formData, description: e.target.value })}
                            />
                        </div>

                        <div className="form-field">
                            <label htmlFor="cat-utm">Default UTM Template</label>
                            <select
                                id="cat-utm"
                                value={formData.default_utm}
                                onChange={(e) => setFormData({ ...formData, default_utm: e.target.value })}
                            >
                                <option value="">None</option>
                                {templates.map(tpl => (
                                    <option key={tpl.id} value={tpl.id}>{tpl.name}</option>
                                ))}
                            </select>
                        </div>

                        <div className="form-field">
                            <label htmlFor="cat-cap">Category-level cap (per page)</label>
                            <input
                                id="cat-cap"
                                type="number"
                                min="0"
                                max="50"
                                value={formData.category_cap || ''}
                                onChange={(e) => setFormData({ ...formData, category_cap: parseInt(e.target.value, 10) || 0 })}
                                placeholder="0 = no extra cap"
                            />
                        </div>
                    </div>

                    <div className="modal__footer">
                        <button type="button" className="button ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="button primary" disabled={saving}>
                            {saving ? 'Saving...' : (isEdit ? 'Update Category' : 'Create Category')}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    );
};

// Template Modal Component - Simplified
const TemplateModal = ({ template, onClose, onSave }) => {
    const isEdit = !!template;
    const [formData, setFormData] = useState(() => template ? { ...template } : { name: '', utm_source: '', utm_medium: '', utm_campaign: '', apply_to: 'both' });
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState('');

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSaving(true);
        try {
            const path = isEdit ? `/saman-seo/v1/internal-links/templates/${template.id}` : '/saman-seo/v1/internal-links/templates';
            const res = await apiFetch({ path, method: isEdit ? 'PUT' : 'POST', data: formData });
            if (res.success) { onSave(res.data); } else { setError(res.message || 'Failed to save template'); }
        } catch (err) { setError(err.message || 'Failed to save template'); }
        finally { setSaving(false); }
    };

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal" onClick={e => e.stopPropagation()}>
                <div className="modal__header">
                    <h2>{isEdit ? 'Edit Template' : 'Add Template'}</h2>
                    <button type="button" className="modal__close" onClick={onClose}>&times;</button>
                </div>

                <form onSubmit={handleSubmit}>
                    <div className="modal__body">
                        {error && <div className="form-error">{error}</div>}

                        <div className="form-field">
                            <label htmlFor="tpl-name">Name</label>
                            <input id="tpl-name" type="text" value={formData.name} onChange={(e) => setFormData({ ...formData, name: e.target.value })} required />
                        </div>

                        <div className="form-row">
                            <div className="form-field">
                                <label htmlFor="tpl-source">utm_source</label>
                                <input id="tpl-source" type="text" value={formData.utm_source || ''} onChange={(e) => setFormData({ ...formData, utm_source: e.target.value })} placeholder="e.g., website" />
                            </div>
                            <div className="form-field">
                                <label htmlFor="tpl-medium">utm_medium</label>
                                <input id="tpl-medium" type="text" value={formData.utm_medium || ''} onChange={(e) => setFormData({ ...formData, utm_medium: e.target.value })} placeholder="e.g., internal_link" />
                            </div>
                        </div>

                        <div className="form-field">
                            <label htmlFor="tpl-campaign">utm_campaign</label>
                            <input id="tpl-campaign" type="text" value={formData.utm_campaign || ''} onChange={(e) => setFormData({ ...formData, utm_campaign: e.target.value })} placeholder="e.g., cross_selling" />
                        </div>

                        <div className="form-field">
                            <label>Apply to</label>
                            <div className="radio-group">
                                <label className="radio-item"><input type="radio" name="apply_to" checked={formData.apply_to === 'internal'} onChange={() => setFormData({ ...formData, apply_to: 'internal' })} /><span>Internal</span></label>
                                <label className="radio-item"><input type="radio" name="apply_to" checked={formData.apply_to === 'external'} onChange={() => setFormData({ ...formData, apply_to: 'external' })} /><span>External</span></label>
                                <label className="radio-item"><input type="radio" name="apply_to" checked={formData.apply_to === 'both'} onChange={() => setFormData({ ...formData, apply_to: 'both' })} /><span>Both</span></label>
                            </div>
                        </div>
                    </div>

                    <div className="modal__footer">
                        <button type="button" className="button ghost" onClick={onClose}>Cancel</button>
                        <button type="submit" className="button primary" disabled={saving}>{saving ? 'Saving...' : (isEdit ? 'Update' : 'Create')}</button>
                    </div>
                </form>
            </div>
        </div>
    );
};

export default InternalLinking;
