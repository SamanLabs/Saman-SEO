const AiAssistant = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>AI Assistant</h1>
                    <p>Use AI to generate SEO-optimized titles and meta descriptions.</p>
                </div>
                <button type="button" className="button ghost">View Usage Stats</button>
            </div>
            <div className="page-body two-column">
                <section className="panel">
                    <h3>Test AI Generation</h3>
                    <div className="settings-row compact" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 'var(--space-sm)' }}>
                        <div className="settings-label">
                            <label htmlFor="test-content">Content to Analyze</label>
                            <p className="settings-help">Paste content to test AI title and description generation.</p>
                        </div>
                        <textarea id="test-content" rows="6" placeholder="Paste your content here to test AI generation..." style={{ width: '100%' }} />
                    </div>
                    <div style={{ marginTop: 'var(--space-md)' }}>
                        <button type="button" className="button primary">Generate Title & Description</button>
                    </div>
                    <h3 style={{ marginTop: 'var(--space-lg)' }}>Prompt Configuration</h3>
                    <div className="settings-row compact" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 'var(--space-sm)' }}>
                        <div className="settings-label">
                            <label htmlFor="system-prompt">System Prompt</label>
                            <p className="settings-help">Base instructions for the AI model.</p>
                        </div>
                        <textarea id="system-prompt" rows="3" placeholder="You are an SEO expert..." style={{ width: '100%' }} />
                    </div>
                    <div className="settings-row compact" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 'var(--space-sm)', marginTop: 'var(--space-md)' }}>
                        <div className="settings-label">
                            <label htmlFor="title-prompt">Title Generation Prompt</label>
                            <p className="settings-help">Instructions for generating SEO titles.</p>
                        </div>
                        <textarea id="title-prompt" rows="2" placeholder="Generate an SEO-optimized title..." style={{ width: '100%' }} />
                    </div>
                    <div className="settings-row compact" style={{ flexDirection: 'column', alignItems: 'stretch', gap: 'var(--space-sm)', marginTop: 'var(--space-md)' }}>
                        <div className="settings-label">
                            <label htmlFor="desc-prompt">Description Generation Prompt</label>
                            <p className="settings-help">Instructions for generating meta descriptions.</p>
                        </div>
                        <textarea id="desc-prompt" rows="2" placeholder="Generate a compelling meta description..." style={{ width: '100%' }} />
                    </div>
                    <div style={{ marginTop: 'var(--space-md)' }}>
                        <button type="button" className="button primary">Save Prompts</button>
                        <button type="button" className="button ghost" style={{ marginLeft: '8px' }}>Reset to Defaults</button>
                    </div>
                </section>
                <aside className="side-panel">
                    <div className="side-card highlight">
                        <h3>API Configuration</h3>
                        <p className="muted">Configure your OpenAI API settings.</p>
                        <div style={{ marginTop: 'var(--space-sm)' }}>
                            <label htmlFor="api-key" style={{ display: 'block', marginBottom: '4px', fontSize: '13px' }}>API Key</label>
                            <input id="api-key" type="password" placeholder="sk-..." style={{ width: '100%' }} />
                        </div>
                        <div style={{ marginTop: 'var(--space-sm)' }}>
                            <label htmlFor="ai-model" style={{ display: 'block', marginBottom: '4px', fontSize: '13px' }}>Model</label>
                            <select id="ai-model" style={{ width: '100%' }}>
                                <option value="gpt-4o-mini">GPT-4o Mini (Recommended)</option>
                                <option value="gpt-4o">GPT-4o</option>
                                <option value="gpt-4-turbo">GPT-4 Turbo</option>
                            </select>
                        </div>
                        <button type="button" className="button primary" style={{ marginTop: 'var(--space-md)', width: '100%' }}>Save API Settings</button>
                    </div>
                    <div className="side-card">
                        <h3>Usage Tips</h3>
                        <p className="muted">Get the best results from AI generation.</p>
                        <ul style={{ margin: '12px 0 0', paddingLeft: '16px', fontSize: '13px', color: 'var(--color-text-muted)' }}>
                            <li>Provide 100+ words for best results</li>
                            <li>Always review generated content</li>
                            <li>GPT-4o-mini is faster and cheaper</li>
                            <li>GPT-4o produces higher quality</li>
                        </ul>
                    </div>
                    <div className="side-card">
                        <h3>API Status</h3>
                        <p className="muted">Current API configuration status.</p>
                        <div className="key-preview">
                            <span className="muted">Status:</span>
                            <span className="code">Not Configured</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
};

export default AiAssistant;
