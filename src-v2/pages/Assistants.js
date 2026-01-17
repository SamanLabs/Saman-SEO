import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { AssistantProvider, AssistantChat } from '../assistants';

// Get AI status from global settings
const globalSettings = window?.SamanSEOSettings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;

/**
 * Assistants page - Management view with create + stats.
 */
const Assistants = ({ initialAssistant = null }) => {
    const [view, setView] = useState('list'); // 'list', 'chat', 'create', 'edit'
    const [assistants, setAssistants] = useState([]);
    const [customAssistants, setCustomAssistants] = useState([]);
    const [stats, setStats] = useState(null);
    const [selectedAssistant, setSelectedAssistant] = useState(null);
    const [editingAssistant, setEditingAssistant] = useState(null);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);

    // Form state for create/edit
    const [form, setForm] = useState({
        name: '',
        description: '',
        system_prompt: '',
        initial_message: '',
        icon: '🤖',
        color: '#6366f1',
        model_id: '',
        is_active: true,
    });

    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const [assistantsRes, customRes, statsRes] = await Promise.all([
                apiFetch({ path: '/saman-seo/v1/assistants' }),
                apiFetch({ path: '/saman-seo/v1/assistants/custom' }),
                apiFetch({ path: '/saman-seo/v1/assistants/stats' }),
            ]);

            if (assistantsRes.success) {
                setAssistants(assistantsRes.data);
            }
            if (customRes.success) {
                setCustomAssistants(customRes.data);
            }
            if (statsRes.success) {
                setStats(statsRes.data);
            }
        } catch (err) {
            console.error('Failed to fetch assistants:', err);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Handle initial assistant from URL
    useEffect(() => {
        if (initialAssistant && assistants.length > 0) {
            const found = assistants.find((a) => a.id === initialAssistant);
            if (found) {
                setSelectedAssistant(found);
                setView('chat');
            }
        }
    }, [initialAssistant, assistants]);

    const handleSelectAssistant = (assistant) => {
        setSelectedAssistant(assistant);
        setView('chat');
    };

    const handleBack = () => {
        setSelectedAssistant(null);
        setEditingAssistant(null);
        setView('list');
        setForm({
            name: '',
            description: '',
            system_prompt: '',
            initial_message: '',
            icon: '🤖',
            color: '#6366f1',
            model_id: '',
            is_active: true,
        });
    };

    const handleCreateNew = () => {
        setForm({
            name: '',
            description: '',
            system_prompt: '',
            initial_message: '',
            icon: '🤖',
            color: '#6366f1',
            model_id: '',
            is_active: true,
        });
        setEditingAssistant(null);
        setView('create');
    };

    const handleEdit = (assistant) => {
        setForm({
            name: assistant.name || '',
            description: assistant.description || '',
            system_prompt: assistant.system_prompt || '',
            initial_message: assistant.initial_message || '',
            icon: assistant.icon || '🤖',
            color: assistant.color || '#6366f1',
            model_id: assistant.model_id || '',
            is_active: assistant.is_active !== false,
        });
        setEditingAssistant(assistant);
        setView('edit');
    };

    const handleSave = async () => {
        if (!form.name || !form.system_prompt) {
            alert('Name and system prompt are required.');
            return;
        }

        setSaving(true);
        try {
            if (editingAssistant) {
                await apiFetch({
                    path: `/saman-seo/v1/assistants/custom/${editingAssistant.id}`,
                    method: 'PUT',
                    data: form,
                });
            } else {
                await apiFetch({
                    path: '/saman-seo/v1/assistants/custom',
                    method: 'POST',
                    data: form,
                });
            }
            await fetchData();
            handleBack();
        } catch (err) {
            console.error('Failed to save assistant:', err);
            alert('Failed to save assistant.');
        } finally {
            setSaving(false);
        }
    };

    const handleDelete = async (id) => {
        if (!window.confirm('Are you sure you want to delete this assistant?')) {
            return;
        }

        try {
            await apiFetch({
                path: `/saman-seo/v1/assistants/custom/${id}`,
                method: 'DELETE',
            });
            await fetchData();
        } catch (err) {
            console.error('Failed to delete assistant:', err);
        }
    };

    const updateForm = (key, value) => {
        setForm((prev) => ({ ...prev, [key]: value }));
    };

    const icons = ['🤖', '💬', '📊', '🎯', '✨', '🔍', '📝', '💡', '🚀', '⚡'];
    const colors = ['#3b82f6', '#8b5cf6', '#6366f1', '#ec4899', '#f59e0b', '#10b981', '#ef4444', '#6b7280'];

    if (loading) {
        return (
            <div className="page">
                <div className="loading-state">Loading assistants...</div>
            </div>
        );
    }

    // Chat view
    if (view === 'chat' && selectedAssistant) {
        return (
            <div className="page assistants-page">
                <div className="page-header page-header--with-back">
                    <button type="button" className="back-button" onClick={handleBack}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        <span>All Assistants</span>
                    </button>
                    <div className="page-header__info">
                        <div
                            className="page-header__icon"
                            style={{ backgroundColor: `${selectedAssistant.color}15`, color: selectedAssistant.color }}
                        >
                            {selectedAssistant.icon}
                        </div>
                        <div>
                            <h1>{selectedAssistant.name}</h1>
                            <p>{selectedAssistant.description}</p>
                        </div>
                    </div>
                </div>

                <div className="assistants-chat-container">
                    <AssistantProvider
                        key={selectedAssistant.id}
                        assistantId={selectedAssistant.id}
                        initialMessage={selectedAssistant.initial_message || selectedAssistant.initialMessage}
                    >
                        <AssistantChat suggestedPrompts={selectedAssistant.suggested_prompts || selectedAssistant.suggestedPrompts} />
                    </AssistantProvider>
                </div>
            </div>
        );
    }

    // Create/Edit form
    if (view === 'create' || view === 'edit') {
        return (
            <div className="page">
                <div className="page-header page-header--with-back">
                    <button type="button" className="back-button" onClick={handleBack}>
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M15 18l-6-6 6-6"/>
                        </svg>
                        <span>All Assistants</span>
                    </button>
                    <div>
                        <h1>{view === 'edit' ? 'Edit Assistant' : 'Create Assistant'}</h1>
                        <p>Configure your custom AI assistant.</p>
                    </div>
                </div>

                <div className="assistants-form">
                    <div className="panel">
                        <h3>Basic Info</h3>
                        <div className="form-row">
                            <label htmlFor="name">Name *</label>
                            <input
                                id="name"
                                type="text"
                                value={form.name}
                                onChange={(e) => updateForm('name', e.target.value)}
                                placeholder="My SEO Assistant"
                            />
                        </div>
                        <div className="form-row">
                            <label htmlFor="description">Description</label>
                            <input
                                id="description"
                                type="text"
                                value={form.description}
                                onChange={(e) => updateForm('description', e.target.value)}
                                placeholder="A helpful assistant for..."
                            />
                        </div>
                        <div className="form-row">
                            <label>Icon</label>
                            <div className="icon-picker">
                                {icons.map((icon) => (
                                    <button
                                        key={icon}
                                        type="button"
                                        className={`icon-option ${form.icon === icon ? 'active' : ''}`}
                                        onClick={() => updateForm('icon', icon)}
                                    >
                                        {icon}
                                    </button>
                                ))}
                            </div>
                        </div>
                        <div className="form-row">
                            <label>Color</label>
                            <div className="color-picker">
                                {colors.map((color) => (
                                    <button
                                        key={color}
                                        type="button"
                                        className={`color-option ${form.color === color ? 'active' : ''}`}
                                        style={{ backgroundColor: color }}
                                        onClick={() => updateForm('color', color)}
                                    />
                                ))}
                            </div>
                        </div>
                    </div>

                    <div className="panel">
                        <h3>AI Configuration</h3>
                        <div className="form-row">
                            <label htmlFor="system_prompt">System Prompt *</label>
                            <textarea
                                id="system_prompt"
                                value={form.system_prompt}
                                onChange={(e) => updateForm('system_prompt', e.target.value)}
                                placeholder="You are a helpful SEO assistant..."
                                rows={6}
                            />
                            <p className="form-help">Define the assistant's personality and expertise.</p>
                        </div>
                        <div className="form-row">
                            <label htmlFor="initial_message">Welcome Message</label>
                            <textarea
                                id="initial_message"
                                value={form.initial_message}
                                onChange={(e) => updateForm('initial_message', e.target.value)}
                                placeholder="Hi! I'm here to help with..."
                                rows={2}
                            />
                        </div>
                        <div className="form-row">
                            <label htmlFor="model_id">Model (optional)</label>
                            <input
                                id="model_id"
                                type="text"
                                value={form.model_id}
                                onChange={(e) => updateForm('model_id', e.target.value)}
                                placeholder="Leave empty to use default model"
                            />
                            <p className="form-help">Use custom_ID for custom models (e.g., custom_1).</p>
                        </div>
                        <div className="form-row form-row--checkbox">
                            <label>
                                <input
                                    type="checkbox"
                                    checked={form.is_active}
                                    onChange={(e) => updateForm('is_active', e.target.checked)}
                                />
                                <span>Active</span>
                            </label>
                        </div>
                    </div>

                    <div className="form-actions">
                        <button type="button" className="button ghost" onClick={handleBack}>
                            Cancel
                        </button>
                        <button
                            type="button"
                            className="button primary"
                            onClick={handleSave}
                            disabled={saving}
                        >
                            {saving ? 'Saving...' : view === 'edit' ? 'Save Changes' : 'Create Assistant'}
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    // List view (default)
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>AI Assistants</h1>
                    <p>Manage your AI assistants and track usage.</p>
                </div>
                <div className="page-header__actions">
                    {aiProvider === 'wp-ai-pilot' && (
                        <a
                            href={aiPilot?.settingsUrl || 'admin.php?page=wp-ai-pilot'}
                            className="button ghost"
                        >
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                            WP AI Pilot
                        </a>
                    )}
                    <button type="button" className="button primary" onClick={handleCreateNew}>
                        + Create Assistant
                    </button>
                </div>
            </div>

            {/* AI Not Configured Notice */}
            {!aiEnabled && (
                <div className="assistants-notice">
                    <div className="assistants-notice__icon">
                        <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                        </svg>
                    </div>
                    <div className="assistants-notice__content">
                        <h3>AI Assistants Powered by WP AI Pilot</h3>
                        {aiPilot?.installed ? (
                            <>
                                <p>WP AI Pilot is installed but needs configuration. Add an API key to enable AI assistants.</p>
                                <a href={aiPilot.settingsUrl || 'admin.php?page=wp-ai-pilot'} className="button primary">
                                    Configure WP AI Pilot
                                </a>
                            </>
                        ) : (
                            <>
                                <p>Install WP AI Pilot to access AI-powered assistants for SEO optimization, content generation, and more.</p>
                                <a href="plugin-install.php?s=wp+ai+pilot&tab=search" className="button primary">
                                    Install WP AI Pilot
                                </a>
                            </>
                        )}
                    </div>
                </div>
            )}

            {/* Stats Cards */}
            {stats && (
                <div className="stats-grid">
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.total_messages}</div>
                        <div className="stat-card__label">Total Messages</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.today}</div>
                        <div className="stat-card__label">Today</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.this_week}</div>
                        <div className="stat-card__label">This Week</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.this_month}</div>
                        <div className="stat-card__label">This Month</div>
                    </div>
                </div>
            )}

            {/* Built-in Assistants */}
            <div className="assistants-section">
                <h2>Built-in Assistants</h2>
                <div className="assistants-grid">
                    {assistants.filter((a) => a.is_builtin).map((assistant) => (
                        <button
                            key={assistant.id}
                            type="button"
                            className="assistant-card"
                            onClick={() => handleSelectAssistant(assistant)}
                        >
                            <div
                                className="assistant-card__icon"
                                style={{ backgroundColor: `${assistant.color}15`, color: assistant.color }}
                            >
                                {assistant.icon}
                            </div>
                            <div className="assistant-card__content">
                                <h3 className="assistant-card__name">{assistant.name}</h3>
                                <p className="assistant-card__desc">{assistant.description}</p>
                            </div>
                            <div className="assistant-card__arrow" style={{ color: assistant.color }}>
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M9 18l6-6-6-6"/>
                                </svg>
                            </div>
                        </button>
                    ))}
                </div>
            </div>

            {/* Custom Assistants */}
            <div className="assistants-section">
                <h2>Custom Assistants</h2>
                {customAssistants.length === 0 ? (
                    <div className="empty-state">
                        <p>No custom assistants yet.</p>
                        <button type="button" className="button" onClick={handleCreateNew}>
                            Create your first assistant
                        </button>
                    </div>
                ) : (
                    <div className="assistants-grid">
                        {customAssistants.map((assistant) => (
                            <div key={assistant.id} className="assistant-card assistant-card--custom">
                                <button
                                    type="button"
                                    className="assistant-card__main"
                                    onClick={() => handleSelectAssistant({
                                        ...assistant,
                                        id: `custom_${assistant.id}`,
                                    })}
                                >
                                    <div
                                        className="assistant-card__icon"
                                        style={{ backgroundColor: `${assistant.color || '#6366f1'}15`, color: assistant.color || '#6366f1' }}
                                    >
                                        {assistant.icon || '🤖'}
                                    </div>
                                    <div className="assistant-card__content">
                                        <h3 className="assistant-card__name">
                                            {assistant.name}
                                            {!assistant.is_active && <span className="badge badge--muted">Inactive</span>}
                                        </h3>
                                        <p className="assistant-card__desc">{assistant.description || 'Custom assistant'}</p>
                                        {assistant.usage > 0 && (
                                            <span className="assistant-card__usage">{assistant.usage} messages</span>
                                        )}
                                    </div>
                                </button>
                                <div className="assistant-card__actions">
                                    <button
                                        type="button"
                                        className="icon-button"
                                        onClick={() => handleEdit(assistant)}
                                        title="Edit"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/>
                                            <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                        </svg>
                                    </button>
                                    <button
                                        type="button"
                                        className="icon-button icon-button--danger"
                                        onClick={() => handleDelete(assistant.id)}
                                        title="Delete"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M3 6h18M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
};

export default Assistants;
