import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const aiTabs = [
    { id: 'settings', label: 'Settings' },
    { id: 'custom-models', label: 'Custom Models' },
];

// Provider info for display
const PROVIDERS = {
    openai: { name: 'OpenAI', logo: 'https://models.dev/logos/openai.svg' },
    anthropic: { name: 'Anthropic', logo: 'https://models.dev/logos/anthropic.svg' },
    google: { name: 'Google AI', logo: 'https://models.dev/logos/google.svg' },
    openai_compatible: { name: 'OpenAI Compatible', logo: null },
    lmstudio: { name: 'LM Studio', logo: null },
    ollama: { name: 'Ollama', logo: null },
};

const AiAssistant = () => {
    const [activeTab, setActiveTab] = useUrlTab({ tabs: aiTabs, defaultTab: 'settings' });

    // Loading states
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [resetting, setResetting] = useState(false);
    const [generating, setGenerating] = useState(false);

    // Settings state
    const [settings, setSettings] = useState({
        openai_api_key: '',
        api_key_configured: false,
        ai_model: 'gpt-4o-mini',
        ai_prompt_system: '',
        ai_prompt_title: '',
        ai_prompt_description: '',
    });

    // Models list
    const [models, setModels] = useState([]);

    // API status
    const [apiStatus, setApiStatus] = useState({
        configured: false,
        status: 'not_configured',
        message: 'Not configured',
    });

    // Test generation
    const [testContent, setTestContent] = useState('');
    const [generatedTitle, setGeneratedTitle] = useState('');
    const [generatedDescription, setGeneratedDescription] = useState('');

    // Custom models state
    const [customModels, setCustomModels] = useState([]);
    const [customModelsLoading, setCustomModelsLoading] = useState(false);
    const [providers, setProviders] = useState([]);
    const [modelsDatabase, setModelsDatabase] = useState({ models: [], last_sync: null });
    const [modelsDatabaseLoading, setModelsDatabaseLoading] = useState(false);
    const [syncing, setSyncing] = useState(false);
    const [modelSearch, setModelSearch] = useState('');
    const [modelSearchResults, setModelSearchResults] = useState([]);
    const [searching, setSearching] = useState(false);

    // Edit/Add form state
    const [editingModel, setEditingModel] = useState(null);
    const [showModelForm, setShowModelForm] = useState(false);
    const [modelFormData, setModelFormData] = useState({
        name: '',
        provider: 'openai_compatible',
        model_id: '',
        api_url: '',
        api_key: '',
        temperature: 0.7,
        max_tokens: 2048,
        is_active: true,
    });
    const [savingModel, setSavingModel] = useState(false);
    const [testingModel, setTestingModel] = useState(null);

    // Messages
    const [message, setMessage] = useState({ type: '', text: '' });

    // Fetch data
    const fetchData = useCallback(async () => {
        setLoading(true);
        try {
            const [settingsRes, modelsRes, statusRes] = await Promise.all([
                apiFetch({ path: '/wpseopilot/v2/ai/settings' }),
                apiFetch({ path: '/wpseopilot/v2/ai/models' }),
                apiFetch({ path: '/wpseopilot/v2/ai/status' }),
            ]);

            if (settingsRes.success) setSettings(settingsRes.data);
            if (modelsRes.success) setModels(modelsRes.data);
            if (statusRes.success) setApiStatus(statusRes.data);
        } catch (error) {
            console.error('Failed to fetch AI settings:', error);
            setMessage({ type: 'error', text: 'Failed to load AI settings.' });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchData();
    }, [fetchData]);

    // Save all settings
    const handleSaveSettings = async () => {
        setSaving(true);
        setMessage({ type: '', text: '' });
        try {
            const res = await apiFetch({
                path: '/wpseopilot/v2/ai/settings',
                method: 'POST',
                data: {
                    openai_api_key: settings.openai_api_key,
                    ai_model: settings.ai_model,
                    ai_prompt_system: settings.ai_prompt_system,
                    ai_prompt_title: settings.ai_prompt_title,
                    ai_prompt_description: settings.ai_prompt_description,
                },
            });

            if (res.success) {
                setMessage({ type: 'success', text: 'Settings saved successfully!' });
                // Refresh status
                const statusRes = await apiFetch({ path: '/wpseopilot/v2/ai/status' });
                if (statusRes.success) setApiStatus(statusRes.data);
            }
        } catch (error) {
            console.error('Failed to save settings:', error);
            setMessage({ type: 'error', text: 'Failed to save settings.' });
        } finally {
            setSaving(false);
        }
    };

    // Reset to defaults
    const handleReset = async () => {
        if (!window.confirm('Reset prompts to defaults? Your API key will be preserved.')) {
            return;
        }

        setResetting(true);
        setMessage({ type: '', text: '' });
        try {
            const res = await apiFetch({
                path: '/wpseopilot/v2/ai/reset',
                method: 'POST',
            });

            if (res.success) {
                setSettings(prev => ({ ...prev, ...res.data }));
                setMessage({ type: 'success', text: 'Prompts restored to defaults.' });
            }
        } catch (error) {
            console.error('Failed to reset settings:', error);
            setMessage({ type: 'error', text: 'Failed to reset settings.' });
        } finally {
            setResetting(false);
        }
    };

    // Test generation
    const handleGenerate = async () => {
        if (!testContent.trim()) {
            setMessage({ type: 'error', text: 'Please enter some content to analyze.' });
            return;
        }

        if (!apiStatus.configured) {
            setMessage({ type: 'error', text: 'Please configure your OpenAI API key first.' });
            return;
        }

        setGenerating(true);
        setMessage({ type: '', text: '' });
        setGeneratedTitle('');
        setGeneratedDescription('');

        try {
            const res = await apiFetch({
                path: '/wpseopilot/v2/ai/generate',
                method: 'POST',
                data: { content: testContent, type: 'both' },
            });

            if (res.success) {
                setGeneratedTitle(res.data.title || '');
                setGeneratedDescription(res.data.description || '');
                setMessage({ type: 'success', text: 'Generation complete!' });
            }
        } catch (error) {
            console.error('Failed to generate:', error);
            setMessage({ type: 'error', text: error.message || 'Failed to generate content.' });
        } finally {
            setGenerating(false);
        }
    };

    // Fetch custom models data
    const fetchCustomModelsData = useCallback(async () => {
        setCustomModelsLoading(true);
        try {
            const [customModelsRes, providersRes] = await Promise.all([
                apiFetch({ path: '/wpseopilot/v2/ai/custom-models' }),
                apiFetch({ path: '/wpseopilot/v2/ai/providers' }),
            ]);

            if (customModelsRes.success) {
                setCustomModels(Array.isArray(customModelsRes.data) ? customModelsRes.data : []);
            }
            if (providersRes.success) {
                setProviders(Array.isArray(providersRes.data) ? providersRes.data : []);
            }
        } catch (error) {
            console.error('Failed to fetch custom models:', error);
        } finally {
            setCustomModelsLoading(false);
        }
    }, []);

    // Fetch models database
    const fetchModelsDatabase = useCallback(async () => {
        setModelsDatabaseLoading(true);
        try {
            const res = await apiFetch({ path: '/wpseopilot/v2/ai/models-database' });
            if (res.success && res.data) {
                setModelsDatabase({
                    models: Array.isArray(res.data.models) ? res.data.models : [],
                    last_sync: res.data.last_sync || null,
                });
            }
        } catch (error) {
            console.error('Failed to fetch models database:', error);
        } finally {
            setModelsDatabaseLoading(false);
        }
    }, []);

    // Sync models database from models.dev
    const handleSyncModelsDatabase = async () => {
        setSyncing(true);
        try {
            const res = await apiFetch({
                path: '/wpseopilot/v2/ai/models-database/sync',
                method: 'POST',
            });
            if (res.success && res.data) {
                const models = Array.isArray(res.data.models) ? res.data.models : [];
                setModelsDatabase({
                    models,
                    last_sync: res.data.last_sync || null,
                });
                setMessage({ type: 'success', text: `Synced ${models.length} models from models.dev` });
            }
        } catch (error) {
            console.error('Failed to sync models database:', error);
            setMessage({ type: 'error', text: 'Failed to sync models database.' });
        } finally {
            setSyncing(false);
        }
    };

    // Search models in database
    const handleSearchModels = async (query) => {
        if (!query.trim()) {
            setModelSearchResults([]);
            return;
        }

        setSearching(true);
        try {
            const res = await apiFetch({
                path: `/wpseopilot/v2/ai/models-database/search?query=${encodeURIComponent(query)}`,
            });
            if (res.success) setModelSearchResults(Array.isArray(res.data) ? res.data : []);
        } catch (error) {
            console.error('Failed to search models:', error);
        } finally {
            setSearching(false);
        }
    };

    // Debounced search
    useEffect(() => {
        const timer = setTimeout(() => {
            handleSearchModels(modelSearch);
        }, 300);
        return () => clearTimeout(timer);
    }, [modelSearch]);

    // Load custom models when tab changes
    useEffect(() => {
        if (activeTab === 'custom-models') {
            fetchCustomModelsData();
            fetchModelsDatabase();
        }
    }, [activeTab, fetchCustomModelsData, fetchModelsDatabase]);

    // Open add form
    const handleAddModel = () => {
        setEditingModel(null);
        setModelFormData({
            name: '',
            provider: 'openai_compatible',
            model_id: '',
            api_url: '',
            api_key: '',
            temperature: 0.7,
            max_tokens: 2048,
            is_active: true,
        });
        setShowModelForm(true);
    };

    // Open edit form
    const handleEditModel = (model) => {
        setEditingModel(model);
        setModelFormData({
            name: model.name,
            provider: model.provider,
            model_id: model.model_id,
            api_url: model.api_url || '',
            api_key: model.api_key || '',
            temperature: parseFloat(model.temperature) || 0.7,
            max_tokens: parseInt(model.max_tokens, 10) || 2048,
            is_active: !!model.is_active,
        });
        setShowModelForm(true);
    };

    // Pre-fill from models.dev model
    const handleSelectDatabaseModel = (dbModel) => {
        setModelFormData(prev => ({
            ...prev,
            name: dbModel.name,
            model_id: dbModel.id,
            provider: dbModel.provider?.toLowerCase().replace(/\s+/g, '_') || 'openai_compatible',
        }));
        setModelSearch('');
        setModelSearchResults([]);
    };

    // Save custom model
    const handleSaveModel = async () => {
        if (!modelFormData.name.trim() || !modelFormData.model_id.trim()) {
            setMessage({ type: 'error', text: 'Name and Model ID are required.' });
            return;
        }

        setSavingModel(true);
        try {
            const method = editingModel ? 'PUT' : 'POST';
            const path = editingModel
                ? `/wpseopilot/v2/ai/custom-models/${editingModel.id}`
                : '/wpseopilot/v2/ai/custom-models';

            const res = await apiFetch({
                path,
                method,
                data: modelFormData,
            });

            if (res.success) {
                setMessage({ type: 'success', text: editingModel ? 'Model updated!' : 'Model added!' });
                setShowModelForm(false);
                fetchCustomModelsData();
            }
        } catch (error) {
            console.error('Failed to save model:', error);
            setMessage({ type: 'error', text: error.message || 'Failed to save model.' });
        } finally {
            setSavingModel(false);
        }
    };

    // Delete custom model
    const handleDeleteModel = async (id) => {
        if (!window.confirm('Delete this custom model?')) return;

        try {
            const res = await apiFetch({
                path: `/wpseopilot/v2/ai/custom-models/${id}`,
                method: 'DELETE',
            });

            if (res.success) {
                setMessage({ type: 'success', text: 'Model deleted.' });
                fetchCustomModelsData();
            }
        } catch (error) {
            console.error('Failed to delete model:', error);
            setMessage({ type: 'error', text: 'Failed to delete model.' });
        }
    };

    // Test custom model connection
    const handleTestModel = async (id) => {
        setTestingModel(id);
        try {
            const res = await apiFetch({
                path: `/wpseopilot/v2/ai/custom-models/${id}/test`,
                method: 'POST',
            });

            if (res.success) {
                setMessage({ type: 'success', text: `Connection successful! Response: "${res.data.response?.substring(0, 50)}..."` });
            }
        } catch (error) {
            console.error('Failed to test model:', error);
            setMessage({ type: 'error', text: error.message || 'Connection test failed.' });
        } finally {
            setTestingModel(null);
        }
    };

    // Format cost for display
    const formatCost = (cost) => {
        if (!cost) return '-';
        return `$${parseFloat(cost).toFixed(4)}/1k`;
    };

    // Get provider display info
    const getProviderInfo = (providerKey) => {
        return PROVIDERS[providerKey] || { name: providerKey, logo: null };
    };

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>AI Assistant</h1>
                        <p>Configure AI-powered SEO content generation.</p>
                    </div>
                </div>
                <div className="loading-state">Loading AI settings...</div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>AI Assistant</h1>
                    <p>Configure AI-powered SEO content generation.</p>
                </div>
                <div className="header-actions">
                    <div className={`api-status-badge ${apiStatus.configured ? 'connected' : 'disconnected'}`}>
                        <span className="status-dot"></span>
                        {apiStatus.configured ? 'Connected' : 'Not Connected'}
                    </div>
                </div>
            </div>

            <SubTabs tabs={aiTabs} activeTab={activeTab} onChange={setActiveTab} ariaLabel="AI Assistant sections" />

            {message.text && (
                <div className={`notice-message ${message.type}`}>
                    {message.text}
                </div>
            )}

            {activeTab === 'settings' ? (
                <div className="ai-settings-layout">
                    {/* Left Column - Configuration */}
                    <div className="ai-config-column">
                        {/* API & Model Card */}
                        <div className="ai-card">
                            <div className="ai-card-header">
                                <h3>Connection</h3>
                                <a
                                    href="https://platform.openai.com/api-keys"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="link-button"
                                >
                                    Get API Key
                                </a>
                            </div>
                            <div className="ai-card-body">
                                <div className="ai-form-grid">
                                    <div className="ai-form-field">
                                        <label htmlFor="openai-api-key">API Key</label>
                                        <input
                                            type="password"
                                            id="openai-api-key"
                                            value={settings.openai_api_key}
                                            onChange={(e) => setSettings(prev => ({ ...prev, openai_api_key: e.target.value }))}
                                            placeholder="sk-..."
                                            autoComplete="off"
                                        />
                                    </div>
                                    <div className="ai-form-field">
                                        <label htmlFor="ai-model">Model</label>
                                        <select
                                            id="ai-model"
                                            value={settings.ai_model}
                                            onChange={(e) => setSettings(prev => ({ ...prev, ai_model: e.target.value }))}
                                        >
                                            {models.map(model => (
                                                <option key={model.value} value={model.value}>
                                                    {model.label}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Prompts Card */}
                        <div className="ai-card">
                            <div className="ai-card-header">
                                <h3>Prompt Configuration</h3>
                                <button
                                    type="button"
                                    className="link-button"
                                    onClick={handleReset}
                                    disabled={resetting}
                                >
                                    {resetting ? 'Resetting...' : 'Reset Defaults'}
                                </button>
                            </div>
                            <div className="ai-card-body">
                                <div className="ai-prompts-stack">
                                    <div className="ai-prompt-field">
                                        <label htmlFor="system-prompt">
                                            System Prompt
                                            <span className="label-hint">Base instructions for every request</span>
                                        </label>
                                        <textarea
                                            id="system-prompt"
                                            value={settings.ai_prompt_system}
                                            onChange={(e) => setSettings(prev => ({ ...prev, ai_prompt_system: e.target.value }))}
                                            rows="2"
                                            placeholder="You are an SEO assistant..."
                                        />
                                    </div>
                                    <div className="ai-prompts-row">
                                        <div className="ai-prompt-field">
                                            <label htmlFor="title-prompt">
                                                Title Prompt
                                                <span className="label-hint">How to craft titles</span>
                                            </label>
                                            <textarea
                                                id="title-prompt"
                                                value={settings.ai_prompt_title}
                                                onChange={(e) => setSettings(prev => ({ ...prev, ai_prompt_title: e.target.value }))}
                                                rows="2"
                                                placeholder="Write an SEO meta title..."
                                            />
                                        </div>
                                        <div className="ai-prompt-field">
                                            <label htmlFor="desc-prompt">
                                                Description Prompt
                                                <span className="label-hint">How to craft descriptions</span>
                                            </label>
                                            <textarea
                                                id="desc-prompt"
                                                value={settings.ai_prompt_description}
                                                onChange={(e) => setSettings(prev => ({ ...prev, ai_prompt_description: e.target.value }))}
                                                rows="2"
                                                placeholder="Write a meta description..."
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div className="ai-card-footer">
                                <button
                                    type="button"
                                    className="button primary"
                                    onClick={handleSaveSettings}
                                    disabled={saving}
                                >
                                    {saving ? 'Saving...' : 'Save All Settings'}
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Test & Info */}
                    <div className="ai-test-column">
                        {/* Test Generation */}
                        <div className="ai-card ai-test-card">
                            <div className="ai-card-header">
                                <h3>Test Generation</h3>
                            </div>
                            <div className="ai-card-body">
                                <div className="ai-test-input">
                                    <textarea
                                        value={testContent}
                                        onChange={(e) => setTestContent(e.target.value)}
                                        rows="4"
                                        placeholder="Paste content here to test AI generation. Provide at least 100 words for best results..."
                                    />
                                    <button
                                        type="button"
                                        className="button primary"
                                        onClick={handleGenerate}
                                        disabled={generating || !apiStatus.configured}
                                    >
                                        {generating ? (
                                            <>
                                                <span className="spinner"></span>
                                                Generating...
                                            </>
                                        ) : (
                                            'Generate'
                                        )}
                                    </button>
                                </div>

                                {(generatedTitle || generatedDescription) && (
                                    <div className="ai-results">
                                        {generatedTitle && (
                                            <div className="ai-result-item">
                                                <div className="ai-result-header">
                                                    <span className="ai-result-label">Title</span>
                                                    <span className="ai-result-count">{generatedTitle.length} chars</span>
                                                </div>
                                                <div className="ai-result-value">{generatedTitle}</div>
                                            </div>
                                        )}
                                        {generatedDescription && (
                                            <div className="ai-result-item">
                                                <div className="ai-result-header">
                                                    <span className="ai-result-label">Description</span>
                                                    <span className="ai-result-count">{generatedDescription.length} chars</span>
                                                </div>
                                                <div className="ai-result-value">{generatedDescription}</div>
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Quick Info Cards */}
                        <div className="ai-info-grid">
                            <div className="ai-info-card">
                                <div className="ai-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                </div>
                                <div className="ai-info-content">
                                    <strong>Editor Integration</strong>
                                    <p>AI buttons appear in post editor once API key is saved</p>
                                </div>
                            </div>
                            <div className="ai-info-card">
                                <div className="ai-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                </div>
                                <div className="ai-info-content">
                                    <strong>Privacy First</strong>
                                    <p>API key stored locally, nothing saved externally</p>
                                </div>
                            </div>
                            <div className="ai-info-card">
                                <div className="ai-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                </div>
                                <div className="ai-info-content">
                                    <strong>Fast Results</strong>
                                    <p>GPT-4o-mini is quick and affordable for most use cases</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            ) : (
                <div className="custom-models-layout">
                    {/* Left Column - Model List & Form */}
                    <div className="custom-models-main">
                        {/* Model Form Modal/Card */}
                        {showModelForm && (
                            <div className="ai-card custom-model-form-card">
                                <div className="ai-card-header">
                                    <h3>{editingModel ? 'Edit Model' : 'Add Custom Model'}</h3>
                                    <button
                                        type="button"
                                        className="link-button"
                                        onClick={() => setShowModelForm(false)}
                                    >
                                        Cancel
                                    </button>
                                </div>
                                <div className="ai-card-body">
                                    {/* Model Search from Database */}
                                    <div className="model-search-section">
                                        <div className="ai-form-field">
                                            <label>Search models.dev database</label>
                                            <input
                                                type="text"
                                                value={modelSearch}
                                                onChange={(e) => setModelSearch(e.target.value)}
                                                placeholder="Search for a model (e.g., GPT-4, Claude)..."
                                            />
                                        </div>
                                        {modelSearchResults.length > 0 && (
                                            <div className="model-search-results">
                                                {modelSearchResults.slice(0, 5).map((m, idx) => (
                                                    <button
                                                        key={idx}
                                                        type="button"
                                                        className="model-search-item"
                                                        onClick={() => handleSelectDatabaseModel(m)}
                                                    >
                                                        <div className="model-search-item-info">
                                                            <strong>{m.name}</strong>
                                                            <span className="model-search-item-id">{m.id}</span>
                                                        </div>
                                                        <span className="model-search-item-provider">{m.provider}</span>
                                                    </button>
                                                ))}
                                            </div>
                                        )}
                                    </div>

                                    <div className="form-divider">
                                        <span>or configure manually</span>
                                    </div>

                                    <div className="ai-form-grid">
                                        <div className="ai-form-field">
                                            <label htmlFor="model-name">Display Name *</label>
                                            <input
                                                type="text"
                                                id="model-name"
                                                value={modelFormData.name}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, name: e.target.value }))}
                                                placeholder="My Custom Model"
                                            />
                                        </div>
                                        <div className="ai-form-field">
                                            <label htmlFor="model-provider">Provider</label>
                                            <select
                                                id="model-provider"
                                                value={modelFormData.provider}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, provider: e.target.value }))}
                                            >
                                                {Object.entries(PROVIDERS).map(([key, info]) => (
                                                    <option key={key} value={key}>{info.name}</option>
                                                ))}
                                            </select>
                                        </div>
                                    </div>

                                    <div className="ai-form-field">
                                        <label htmlFor="model-id">Model ID *</label>
                                        <input
                                            type="text"
                                            id="model-id"
                                            value={modelFormData.model_id}
                                            onChange={(e) => setModelFormData(prev => ({ ...prev, model_id: e.target.value }))}
                                            placeholder="gpt-4o, claude-3-opus, etc."
                                        />
                                    </div>

                                    {(modelFormData.provider === 'openai_compatible' || modelFormData.provider === 'lmstudio' || modelFormData.provider === 'ollama') && (
                                        <div className="ai-form-field">
                                            <label htmlFor="model-url">API URL</label>
                                            <input
                                                type="text"
                                                id="model-url"
                                                value={modelFormData.api_url}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, api_url: e.target.value }))}
                                                placeholder={
                                                    modelFormData.provider === 'lmstudio' ? 'http://localhost:1234/v1/chat/completions' :
                                                    modelFormData.provider === 'ollama' ? 'http://localhost:11434/api/chat' :
                                                    'https://api.example.com/v1/chat/completions'
                                                }
                                            />
                                        </div>
                                    )}

                                    {modelFormData.provider !== 'lmstudio' && modelFormData.provider !== 'ollama' && (
                                        <div className="ai-form-field">
                                            <label htmlFor="model-api-key">API Key</label>
                                            <input
                                                type="password"
                                                id="model-api-key"
                                                value={modelFormData.api_key}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, api_key: e.target.value }))}
                                                placeholder="sk-... or leave empty to use default"
                                            />
                                        </div>
                                    )}

                                    <div className="ai-form-grid">
                                        <div className="ai-form-field">
                                            <label htmlFor="model-temp">Temperature</label>
                                            <input
                                                type="number"
                                                id="model-temp"
                                                value={modelFormData.temperature}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, temperature: parseFloat(e.target.value) || 0 }))}
                                                min="0"
                                                max="2"
                                                step="0.1"
                                            />
                                        </div>
                                        <div className="ai-form-field">
                                            <label htmlFor="model-tokens">Max Tokens</label>
                                            <input
                                                type="number"
                                                id="model-tokens"
                                                value={modelFormData.max_tokens}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, max_tokens: parseInt(e.target.value, 10) || 2048 }))}
                                                min="100"
                                                max="128000"
                                            />
                                        </div>
                                    </div>

                                    <div className="ai-form-field checkbox-field">
                                        <label>
                                            <input
                                                type="checkbox"
                                                checked={modelFormData.is_active}
                                                onChange={(e) => setModelFormData(prev => ({ ...prev, is_active: e.target.checked }))}
                                            />
                                            Enable this model
                                        </label>
                                    </div>
                                </div>
                                <div className="ai-card-footer">
                                    <button
                                        type="button"
                                        className="button primary"
                                        onClick={handleSaveModel}
                                        disabled={savingModel}
                                    >
                                        {savingModel ? 'Saving...' : (editingModel ? 'Update Model' : 'Add Model')}
                                    </button>
                                </div>
                            </div>
                        )}

                        {/* Custom Models List */}
                        <div className="ai-card">
                            <div className="ai-card-header">
                                <h3>Your Custom Models</h3>
                                <button
                                    type="button"
                                    className="button small"
                                    onClick={handleAddModel}
                                >
                                    + Add Model
                                </button>
                            </div>
                            <div className="ai-card-body">
                                {customModelsLoading ? (
                                    <div className="loading-state">Loading custom models...</div>
                                ) : customModels.length === 0 ? (
                                    <div className="empty-state">
                                        <p>No custom models configured yet.</p>
                                        <p className="muted">Add a custom model to use alternative AI providers.</p>
                                    </div>
                                ) : (
                                    <div className="custom-models-list">
                                        {customModels.map(model => {
                                            const providerInfo = getProviderInfo(model.provider);
                                            return (
                                                <div key={model.id} className={`custom-model-item ${model.is_active ? 'active' : 'inactive'}`}>
                                                    <div className="custom-model-info">
                                                        <div className="custom-model-header">
                                                            {providerInfo.logo && (
                                                                <img
                                                                    src={providerInfo.logo}
                                                                    alt={providerInfo.name}
                                                                    className="provider-logo"
                                                                />
                                                            )}
                                                            <div>
                                                                <strong>{model.name}</strong>
                                                                <span className="custom-model-id">{model.model_id}</span>
                                                            </div>
                                                        </div>
                                                        <div className="custom-model-meta">
                                                            <span className="provider-badge">{providerInfo.name}</span>
                                                            <span className={`status-badge ${model.is_active ? 'active' : 'inactive'}`}>
                                                                {model.is_active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <div className="custom-model-actions">
                                                        <button
                                                            type="button"
                                                            className="button small"
                                                            onClick={() => handleTestModel(model.id)}
                                                            disabled={testingModel === model.id}
                                                        >
                                                            {testingModel === model.id ? 'Testing...' : 'Test'}
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="button small"
                                                            onClick={() => handleEditModel(model)}
                                                        >
                                                            Edit
                                                        </button>
                                                        <button
                                                            type="button"
                                                            className="button small danger"
                                                            onClick={() => handleDeleteModel(model.id)}
                                                        >
                                                            Delete
                                                        </button>
                                                    </div>
                                                </div>
                                            );
                                        })}
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Right Column - Models Database */}
                    <div className="custom-models-sidebar">
                        <div className="ai-card">
                            <div className="ai-card-header">
                                <h3>Models Database</h3>
                                <button
                                    type="button"
                                    className="link-button"
                                    onClick={handleSyncModelsDatabase}
                                    disabled={syncing}
                                >
                                    {syncing ? 'Syncing...' : 'Sync Now'}
                                </button>
                            </div>
                            <div className="ai-card-body">
                                {modelsDatabase.last_sync && (
                                    <p className="sync-info">
                                        Last synced: {new Date(modelsDatabase.last_sync).toLocaleDateString()}
                                        <br />
                                        <span className="muted">{(modelsDatabase.models || []).length} models in database</span>
                                    </p>
                                )}

                                {modelsDatabaseLoading ? (
                                    <div className="loading-state">Loading models database...</div>
                                ) : (!modelsDatabase.models || modelsDatabase.models.length === 0) ? (
                                    <div className="empty-state">
                                        <p>No models in database.</p>
                                        <button
                                            type="button"
                                            className="button primary"
                                            onClick={handleSyncModelsDatabase}
                                            disabled={syncing}
                                        >
                                            {syncing ? 'Syncing...' : 'Sync from models.dev'}
                                        </button>
                                    </div>
                                ) : (
                                    <div className="models-database-list">
                                        {(modelsDatabase.models || []).slice(0, 15).map((m, idx) => (
                                            <div key={idx} className="database-model-item">
                                                <div className="database-model-info">
                                                    <strong>{m.name}</strong>
                                                    <span className="database-model-provider">{m.provider}</span>
                                                </div>
                                                <div className="database-model-costs">
                                                    {m.inputCost && (
                                                        <span className="cost-badge" title="Input cost per 1k tokens">
                                                            In: {formatCost(m.inputCost)}
                                                        </span>
                                                    )}
                                                    {m.outputCost && (
                                                        <span className="cost-badge" title="Output cost per 1k tokens">
                                                            Out: {formatCost(m.outputCost)}
                                                        </span>
                                                    )}
                                                </div>
                                            </div>
                                        ))}
                                        {(modelsDatabase.models || []).length > 15 && (
                                            <p className="muted" style={{ textAlign: 'center', marginTop: '12px' }}>
                                                +{(modelsDatabase.models || []).length - 15} more models...
                                            </p>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Provider Info Cards */}
                        <div className="ai-info-grid">
                            <div className="ai-info-card">
                                <div className="ai-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
                                        <path d="M12 16v-4M12 8h.01" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                    </svg>
                                </div>
                                <div className="ai-info-content">
                                    <strong>Local Models</strong>
                                    <p>Use Ollama or LM Studio for local, private AI</p>
                                </div>
                            </div>
                            <div className="ai-info-card">
                                <div className="ai-info-icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                </div>
                                <div className="ai-info-content">
                                    <strong>Any Provider</strong>
                                    <p>OpenAI, Anthropic, Google, or any compatible API</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AiAssistant;
