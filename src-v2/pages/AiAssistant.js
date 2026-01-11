import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';

const aiTabs = [
    { id: 'settings', label: 'Settings' },
    { id: 'custom-models', label: 'Custom Models' },
];

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
                <div className="ai-coming-soon">
                    <div className="ai-coming-soon-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none">
                            <path d="M9 21c0 .55.45 1 1 1h4c.55 0 1-.45 1-1v-1H9v1zm3-19C8.14 2 5 5.14 5 9c0 2.38 1.19 4.47 3 5.74V17c0 .55.45 1 1 1h6c.55 0 1-.45 1-1v-2.26c1.81-1.27 3-3.36 3-5.74 0-3.86-3.14-7-7-7zm2.85 11.1l-.85.6V16h-4v-2.3l-.85-.6A4.997 4.997 0 017 9c0-2.76 2.24-5 5-5s5 2.24 5 5c0 1.63-.8 3.16-2.15 4.1z" fill="currentColor"/>
                        </svg>
                    </div>
                    <h2>Custom AI Models</h2>
                    <p className="muted">Support for custom endpoints and providers coming soon.</p>

                    <div className="ai-coming-soon-list">
                        <div className="ai-coming-soon-item">
                            <span className="check">&#10003;</span>
                            <span>OpenAI-compatible API endpoints</span>
                        </div>
                        <div className="ai-coming-soon-item">
                            <span className="check">&#10003;</span>
                            <span>Anthropic Claude, Google Gemini</span>
                        </div>
                        <div className="ai-coming-soon-item">
                            <span className="check">&#10003;</span>
                            <span>Local models via Ollama</span>
                        </div>
                        <div className="ai-coming-soon-item">
                            <span className="check">&#10003;</span>
                            <span>Custom parameters per model</span>
                        </div>
                    </div>

                    <a
                        href="https://github.com/jhd3197/WP-SEO-Pilot"
                        target="_blank"
                        rel="noopener noreferrer"
                        className="button primary"
                    >
                        Follow on GitHub
                    </a>
                </div>
            )}
        </div>
    );
};

export default AiAssistant;
