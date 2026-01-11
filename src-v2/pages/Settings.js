const Settings = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Settings</h1>
                    <p>Manage feature toggles, data, and plugin preferences.</p>
                </div>
                <button type="button" className="button ghost">Reset Defaults</button>
            </div>
            <div className="page-body two-column">
                <section className="panel">
                    <h3>Feature Modules</h3>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="sitemap-enhancer">XML Sitemap Enhancement</label>
                            <p className="settings-help">Enable enhanced XML sitemap generation.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="sitemap-enhancer" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="redirect-manager">Redirect Manager</label>
                            <p className="settings-help">Enable 301 redirect management and 404 logging.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="redirect-manager" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="social-cards">Social Card Generation</label>
                            <p className="settings-help">Enable dynamic OG image generation.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="social-cards" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="llm-txt">LLM.txt Generation</label>
                            <p className="settings-help">Generate llm.txt file for AI crawlers.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="llm-txt" type="checkbox" />
                                <span className="toggle-track" />
                                <span className="toggle-text">Disabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="local-seo">Local SEO Module</label>
                            <p className="settings-help">Enable local business schema markup.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="local-seo" type="checkbox" />
                                <span className="toggle-track" />
                                <span className="toggle-text">Disabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="analytics">Analytics Tracking</label>
                            <p className="settings-help">Enable Matomo analytics integration.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="analytics" type="checkbox" />
                                <span className="toggle-track" />
                                <span className="toggle-text">Disabled</span>
                            </label>
                        </div>
                    </div>
                </section>
                <aside className="side-panel">
                    <div className="side-card">
                        <h3>Data Management</h3>
                        <p className="muted">Export or import plugin settings as JSON.</p>
                        <button type="button" className="button primary">Export Settings</button>
                        <button type="button" className="button ghost" style={{ marginTop: '8px' }}>Import Settings</button>
                    </div>
                    <div className="side-card highlight">
                        <h3>Plugin Info</h3>
                        <p className="muted">WP SEO Pilot V2 Interface</p>
                        <div className="key-preview">
                            <span className="muted">Version:</span>
                            <span className="code">0.1.41</span>
                        </div>
                        <div className="key-preview">
                            <span className="muted">Interface:</span>
                            <span className="code">React SPA</span>
                        </div>
                    </div>
                    <div className="side-card">
                        <h3>Legacy Interface</h3>
                        <p className="muted">Access the V1 interface for features not yet migrated.</p>
                        <a href="admin.php?page=wpseopilot" className="button ghost">Open V1 Interface</a>
                    </div>
                </aside>
            </div>
        </div>
    );
};

export default Settings;
