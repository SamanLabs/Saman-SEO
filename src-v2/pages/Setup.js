import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Setup Wizard - First-time plugin configuration
 */
const Setup = ({ onComplete, onSkip }) => {
    const [step, setStep] = useState(1);
    const [loading, setLoading] = useState(false);
    const [aiStatus, setAiStatus] = useState(null);

    // Form data across all steps
    const [data, setData] = useState({
        // Step 2: Site Info
        site_type: '',
        primary_goal: '',
        industry: '',
        // Step 4: Quick Wins
        enable_sitemap: true,
        enable_404_log: true,
        enable_redirects: true,
        title_template: '%title% - %sitename%',
    });

    const updateData = (key, value) => {
        setData((prev) => ({ ...prev, [key]: value }));
    };

    // Check AI plugin status when entering step 3
    useEffect(() => {
        if (step === 3 && !aiStatus) {
            checkAiStatus();
        }
    }, [step]);

    const checkAiStatus = async () => {
        setAiStatus({ status: 'loading' });
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/setup/test-api',
                method: 'POST',
            });
            setAiStatus(response.data || response);
        } catch (err) {
            setAiStatus({ status: 'error', message: 'Could not check AI status.' });
        }
    };

    const handleNext = () => {
        if (step < 5) setStep(step + 1);
    };

    const handleBack = () => {
        if (step > 1) setStep(step - 1);
    };

    const handleComplete = async () => {
        setLoading(true);

        try {
            await apiFetch({
                path: '/saman-seo/v1/setup/complete',
                method: 'POST',
                data,
            });

            if (onComplete) onComplete();
        } catch (err) {
            console.error('Failed to save setup:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleSkip = async () => {
        try {
            await apiFetch({
                path: '/saman-seo/v1/setup/skip',
                method: 'POST',
            });
        } catch (err) {
            // Ignore errors, just navigate away
        }

        if (onSkip) onSkip();
    };

    const siteTypes = [
        { value: 'blog', label: 'Blog / News', icon: '📝' },
        { value: 'business', label: 'Business / Company', icon: '🏢' },
        { value: 'ecommerce', label: 'E-commerce / Store', icon: '🛒' },
        { value: 'portfolio', label: 'Portfolio / Personal', icon: '🎨' },
        { value: 'agency', label: 'Agency / Services', icon: '💼' },
        { value: 'nonprofit', label: 'Non-profit / Charity', icon: '❤️' },
    ];

    const goals = [
        { value: 'traffic', label: 'Get more traffic', icon: '📈' },
        { value: 'leads', label: 'Generate leads', icon: '📋' },
        { value: 'sales', label: 'Increase sales', icon: '💰' },
        { value: 'brand', label: 'Build brand awareness', icon: '🌟' },
    ];

    return (
        <div className="setup-wizard">
            {/* Progress bar */}
            <div className="setup-progress">
                <div className="setup-progress__bar" style={{ width: `${(step / 5) * 100}%` }} />
            </div>

            <div className="setup-content">
                {/* Step 1: Welcome */}
                {step === 1 && (
                    <div className="setup-step setup-step--welcome">
                        <div className="setup-step__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M5 12h14M12 5l7 7-7 7"/>
                            </svg>
                        </div>
                        <h1>Welcome to Saman SEO</h1>
                        <p className="setup-step__subtitle">
                            Let's get your site ready for search engines. This will only take a minute.
                        </p>

                        <div className="setup-features">
                            <div className="setup-feature">
                                <span className="setup-feature__icon">🚀</span>
                                <span>AI-powered optimization</span>
                            </div>
                            <div className="setup-feature">
                                <span className="setup-feature__icon">📊</span>
                                <span>Real-time SEO analysis</span>
                            </div>
                            <div className="setup-feature">
                                <span className="setup-feature__icon">🔧</span>
                                <span>Easy-to-use tools</span>
                            </div>
                        </div>

                        <div className="setup-actions">
                            <button type="button" className="button primary large" onClick={handleNext}>
                                Let's Get Started
                            </button>
                            <button type="button" className="button ghost" onClick={handleSkip}>
                                Skip for now
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 2: Site Info */}
                {step === 2 && (
                    <div className="setup-step">
                        <span className="setup-step__number">Step 1 of 4</span>
                        <h2>Tell us about your site</h2>
                        <p className="setup-step__subtitle">
                            This helps us customize SEO recommendations for your needs.
                        </p>

                        <div className="setup-section">
                            <label className="setup-label">What type of site is this?</label>
                            <div className="setup-options setup-options--grid">
                                {siteTypes.map((type) => (
                                    <button
                                        key={type.value}
                                        type="button"
                                        className={`setup-option ${data.site_type === type.value ? 'active' : ''}`}
                                        onClick={() => updateData('site_type', type.value)}
                                    >
                                        <span className="setup-option__icon">{type.icon}</span>
                                        <span className="setup-option__label">{type.label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="setup-section">
                            <label className="setup-label">What's your primary goal?</label>
                            <div className="setup-options">
                                {goals.map((goal) => (
                                    <button
                                        key={goal.value}
                                        type="button"
                                        className={`setup-option setup-option--horizontal ${data.primary_goal === goal.value ? 'active' : ''}`}
                                        onClick={() => updateData('primary_goal', goal.value)}
                                    >
                                        <span className="setup-option__icon">{goal.icon}</span>
                                        <span className="setup-option__label">{goal.label}</span>
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="setup-section">
                            <label className="setup-label" htmlFor="industry">Industry / Niche (optional)</label>
                            <input
                                id="industry"
                                type="text"
                                className="setup-input"
                                value={data.industry}
                                onChange={(e) => updateData('industry', e.target.value)}
                                placeholder="e.g., Technology, Health, Finance..."
                            />
                        </div>

                        <div className="setup-actions">
                            <button type="button" className="button ghost" onClick={handleBack}>
                                Back
                            </button>
                            <button
                                type="button"
                                className="button primary"
                                onClick={handleNext}
                                disabled={!data.site_type}
                            >
                                Continue
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 3: AI Status */}
                {step === 3 && (
                    <div className="setup-step">
                        <span className="setup-step__number">Step 2 of 4</span>
                        <h2>AI Features</h2>
                        <p className="setup-step__subtitle">
                            AI-powered features are provided by the Saman Labs AI plugin.
                        </p>

                        {aiStatus?.status === 'loading' && (
                            <div className="setup-info-box">
                                <p>Checking AI status...</p>
                            </div>
                        )}

                        {aiStatus?.status === 'ready' && (
                            <div className="setup-test-result setup-test-result--success">
                                Saman Labs AI is ready! AI features are available.
                            </div>
                        )}

                        {aiStatus?.status === 'not_installed' && (
                            <div className="setup-info-box">
                                <h4>Saman Labs AI Not Installed</h4>
                                <p>Install the Saman Labs AI plugin to enable AI-powered features like content suggestions and meta generation.</p>
                                <a href={aiStatus.install_url} className="button" target="_blank" rel="noopener noreferrer">Install Plugin</a>
                            </div>
                        )}

                        {aiStatus?.status === 'not_active' && (
                            <div className="setup-info-box">
                                <h4>Saman Labs AI Not Active</h4>
                                <p>The plugin is installed but needs to be activated.</p>
                                <a href={aiStatus.plugins_url} className="button" target="_blank" rel="noopener noreferrer">Activate Plugin</a>
                            </div>
                        )}

                        {aiStatus?.status === 'not_configured' && (
                            <div className="setup-info-box">
                                <h4>Saman Labs AI Not Configured</h4>
                                <p>The plugin is active but needs to be configured with your AI provider.</p>
                                <a href={aiStatus.settings_url} className="button" target="_blank" rel="noopener noreferrer">Configure AI</a>
                            </div>
                        )}

                        {aiStatus?.status === 'error' && (
                            <div className="setup-test-result setup-test-result--error">
                                Could not check AI status. You can configure this later in Settings.
                            </div>
                        )}

                        <div className="setup-actions">
                            <button type="button" className="button ghost" onClick={handleBack}>
                                Back
                            </button>
                            <button type="button" className="button primary" onClick={handleNext}>
                                Continue
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 4: Quick Wins */}
                {step === 4 && (
                    <div className="setup-step">
                        <span className="setup-step__number">Step 3 of 4</span>
                        <h2>Quick Wins</h2>
                        <p className="setup-step__subtitle">
                            Enable these essential features to get started quickly.
                        </p>

                        <div className="setup-toggles">
                            <label className="setup-toggle">
                                <div className="setup-toggle__content">
                                    <strong>XML Sitemap</strong>
                                    <span>Help search engines discover your content</span>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={data.enable_sitemap}
                                    onChange={(e) => updateData('enable_sitemap', e.target.checked)}
                                />
                                <span className="setup-toggle__switch" />
                            </label>

                            <label className="setup-toggle">
                                <div className="setup-toggle__content">
                                    <strong>404 Error Logging</strong>
                                    <span>Track broken links and fix them</span>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={data.enable_404_log}
                                    onChange={(e) => updateData('enable_404_log', e.target.checked)}
                                />
                                <span className="setup-toggle__switch" />
                            </label>

                            <label className="setup-toggle">
                                <div className="setup-toggle__content">
                                    <strong>Redirects Manager</strong>
                                    <span>Create and manage URL redirects</span>
                                </div>
                                <input
                                    type="checkbox"
                                    checked={data.enable_redirects}
                                    onChange={(e) => updateData('enable_redirects', e.target.checked)}
                                />
                                <span className="setup-toggle__switch" />
                            </label>
                        </div>

                        <div className="setup-section">
                            <label className="setup-label">Title Template</label>
                            <select
                                className="setup-select"
                                value={data.title_template}
                                onChange={(e) => updateData('title_template', e.target.value)}
                            >
                                <option value="%title% - %sitename%">Page Title - Site Name</option>
                                <option value="%title% | %sitename%">Page Title | Site Name</option>
                                <option value="%sitename% - %title%">Site Name - Page Title</option>
                                <option value="%title%">Page Title Only</option>
                            </select>
                            <p className="setup-help">How titles will appear in search results.</p>
                        </div>

                        <div className="setup-actions">
                            <button type="button" className="button ghost" onClick={handleBack}>
                                Back
                            </button>
                            <button type="button" className="button primary" onClick={handleNext}>
                                Continue
                            </button>
                        </div>
                    </div>
                )}

                {/* Step 5: Done */}
                {step === 5 && (
                    <div className="setup-step setup-step--done">
                        <div className="setup-step__icon setup-step__icon--success">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </div>
                        <h1>You're All Set!</h1>
                        <p className="setup-step__subtitle">
                            Saman SEO is configured and ready to help you rank higher.
                        </p>

                        <div className="setup-summary">
                            <div className="setup-summary__item">
                                <span className="setup-summary__label">Site Type</span>
                                <span className="setup-summary__value">
                                    {siteTypes.find((t) => t.value === data.site_type)?.label || 'Not set'}
                                </span>
                            </div>
                            <div className="setup-summary__item">
                                <span className="setup-summary__label">AI Features</span>
                                <span className="setup-summary__value">
                                    {aiStatus?.status === 'ready' ? 'Enabled' : 'Not configured'}
                                </span>
                            </div>
                            <div className="setup-summary__item">
                                <span className="setup-summary__label">Features Enabled</span>
                                <span className="setup-summary__value">
                                    {[
                                        data.enable_sitemap && 'Sitemap',
                                        data.enable_404_log && '404 Log',
                                        data.enable_redirects && 'Redirects',
                                    ].filter(Boolean).join(', ') || 'None'}
                                </span>
                            </div>
                        </div>

                        <div className="setup-actions">
                            <button
                                type="button"
                                className="button primary large"
                                onClick={handleComplete}
                                disabled={loading}
                            >
                                {loading ? 'Saving...' : 'Go to Dashboard'}
                            </button>
                        </div>

                        <p className="setup-note">
                            You can change these settings anytime in Settings.
                        </p>
                    </div>
                )}
            </div>
        </div>
    );
};

export default Setup;
