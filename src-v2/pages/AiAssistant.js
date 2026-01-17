import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const AiAssistant = () => {
    // Loading states
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [resetting, setResetting] = useState(false);
    const [generating, setGenerating] = useState(false);

    // Settings state (prompts only - API keys deprecated)
    const [settings, setSettings] = useState({
        ai_prompt_system: '',
        ai_prompt_title: '',
        ai_prompt_description: '',
    });

    // API status from AI Controller (includes WP AI Pilot status)
    const [apiStatus, setApiStatus] = useState({
        configured: false,
        status: 'not_configured',
        message: 'Not configured',
        provider: 'none',
        ai_pilot: null,
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
            const [settingsRes, statusRes] = await Promise.all([
                apiFetch({ path: '/saman-seo/v1/ai/settings' }),
                apiFetch({ path: '/saman-seo/v1/ai/status' }),
            ]);

            if (settingsRes.success) {
                setSettings(prev => ({
                    ...prev,
                    ai_prompt_system: settingsRes.data.ai_prompt_system || '',
                    ai_prompt_title: settingsRes.data.ai_prompt_title || '',
                    ai_prompt_description: settingsRes.data.ai_prompt_description || '',
                }));
            }
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

    // Save prompt settings only
    const handleSaveSettings = async () => {
        setSaving(true);
        setMessage({ type: '', text: '' });
        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/ai/settings',
                method: 'POST',
                data: {
                    ai_prompt_system: settings.ai_prompt_system,
                    ai_prompt_title: settings.ai_prompt_title,
                    ai_prompt_description: settings.ai_prompt_description,
                },
            });

            if (res.success) {
                setMessage({ type: 'success', text: 'Prompt settings saved successfully!' });
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
        if (!window.confirm('Reset prompts to defaults?')) {
            return;
        }

        setResetting(true);
        setMessage({ type: '', text: '' });
        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/ai/reset',
                method: 'POST',
            });

            if (res.success) {
                setSettings(prev => ({
                    ...prev,
                    ai_prompt_system: res.data.ai_prompt_system || '',
                    ai_prompt_title: res.data.ai_prompt_title || '',
                    ai_prompt_description: res.data.ai_prompt_description || '',
                }));
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
            setMessage({ type: 'error', text: 'Please configure WP AI Pilot to enable AI generation.' });
            return;
        }

        setGenerating(true);
        setMessage({ type: '', text: '' });
        setGeneratedTitle('');
        setGeneratedDescription('');

        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/ai/generate',
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

    // Get provider display name
    const getProviderName = () => {
        switch (apiStatus.provider) {
            case 'wp-ai-pilot':
                return 'WP AI Pilot';
            case 'native':
                return 'Native API Keys (Deprecated)';
            default:
                return 'Not Configured';
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

            {message.text && (
                <div className={`notice-message ${message.type}`}>
                    {message.text}
                </div>
            )}

            <div className="ai-settings-layout">
                {/* Left Column - Configuration */}
                <div className="ai-config-column">
                    {/* AI Provider Status Card */}
                    <div className="ai-card">
                        <div className="ai-card-header">
                            <h3>AI Provider</h3>
                            {apiStatus.provider === 'wp-ai-pilot' && (
                                <span className="provider-badge provider-badge--active">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                    </svg>
                                    WP AI Pilot
                                </span>
                            )}
                        </div>
                        <div className="ai-card-body">
                            {apiStatus.provider === 'wp-ai-pilot' ? (
                                <div className="ai-provider-status ai-provider-status--connected">
                                    <div className="ai-provider-status__icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                            <polyline points="22 4 12 14.01 9 11.01"/>
                                        </svg>
                                    </div>
                                    <div className="ai-provider-status__content">
                                        <h4>Connected to WP AI Pilot</h4>
                                        <p>AI features are powered by WP AI Pilot. Manage your API keys and models there.</p>
                                        <a
                                            href={apiStatus.ai_pilot?.settings_url || 'admin.php?page=wp-ai-pilot'}
                                            className="button primary"
                                        >
                                            Open WP AI Pilot Settings
                                        </a>
                                    </div>
                                </div>
                            ) : apiStatus.ai_pilot?.installed ? (
                                <div className="ai-provider-status ai-provider-status--warning">
                                    <div className="ai-provider-status__icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <circle cx="12" cy="12" r="10"/>
                                            <path d="M12 8v4m0 4h.01"/>
                                        </svg>
                                    </div>
                                    <div className="ai-provider-status__content">
                                        <h4>WP AI Pilot Needs Configuration</h4>
                                        <p>WP AI Pilot is installed but not configured. Add an API key to enable AI features.</p>
                                        <a
                                            href={apiStatus.ai_pilot?.settings_url || 'admin.php?page=wp-ai-pilot'}
                                            className="button primary"
                                        >
                                            Configure WP AI Pilot
                                        </a>
                                    </div>
                                </div>
                            ) : (
                                <div className="ai-provider-status ai-provider-status--info">
                                    <div className="ai-provider-status__icon">
                                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                            <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                                        </svg>
                                    </div>
                                    <div className="ai-provider-status__content">
                                        <h4>Install WP AI Pilot</h4>
                                        <p>AI features in Saman SEO are now powered by WP AI Pilot. Install it to enable AI-powered title and description generation.</p>
                                        <a
                                            href="plugin-install.php?s=wp+ai+pilot&tab=search"
                                            className="button primary"
                                        >
                                            Install WP AI Pilot
                                        </a>
                                    </div>
                                </div>
                            )}

                            {/* Deprecation Notice */}
                            <div className="deprecation-notice">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 16v-4M12 8h.01"/>
                                </svg>
                                <div>
                                    <strong>API Key Management Deprecated</strong>
                                    <p>Direct API key configuration has been moved to WP AI Pilot. This provides centralized AI management across all your WordPress plugins.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Prompts Card - Still functional */}
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
                                {saving ? 'Saving...' : 'Save Prompt Settings'}
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
                            {apiStatus.provider && apiStatus.provider !== 'none' && (
                                <span className="provider-tag">via {getProviderName()}</span>
                            )}
                        </div>
                        <div className="ai-card-body">
                            <div className="ai-test-input">
                                <textarea
                                    value={testContent}
                                    onChange={(e) => setTestContent(e.target.value)}
                                    rows="4"
                                    placeholder="Paste content here to test AI generation. Provide at least 100 words for best results..."
                                    disabled={!apiStatus.configured}
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

                            {!apiStatus.configured && (
                                <div className="ai-test-disabled-notice">
                                    <p>Configure WP AI Pilot to test AI generation.</p>
                                </div>
                            )}

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
                                <strong>Unified AI Platform</strong>
                                <p>WP AI Pilot manages AI for all your plugins in one place</p>
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
                                <p>API keys stored locally, nothing saved externally</p>
                            </div>
                        </div>
                        <div className="ai-info-card">
                            <div className="ai-info-icon">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                    <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                </svg>
                            </div>
                            <div className="ai-info-content">
                                <strong>Multiple Providers</strong>
                                <p>OpenAI, Anthropic, Google AI, and more via WP AI Pilot</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default AiAssistant;
