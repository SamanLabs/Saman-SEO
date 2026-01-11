const Sitemap = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Sitemap</h1>
                    <p>Configure XML sitemap generation and monitor indexing status.</p>
                </div>
                <button type="button" className="button primary">Regenerate Sitemap</button>
            </div>
            <div className="page-body two-column">
                <section className="panel">
                    <h3>Sitemap Settings</h3>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="enable-sitemap">Enable XML Sitemap</label>
                            <p className="settings-help">Generate and serve XML sitemaps for search engines.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="enable-sitemap" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="include-images">Include Images</label>
                            <p className="settings-help">Add image entries to sitemap for Google Images.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="include-images" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="max-entries">Max Entries Per Sitemap</label>
                            <p className="settings-help">Maximum URLs per sitemap file (default: 1000).</p>
                        </div>
                        <div className="settings-control">
                            <input id="max-entries" type="number" defaultValue="1000" style={{ width: '100px' }} />
                        </div>
                    </div>
                    <h3 style={{ marginTop: 'var(--space-lg)' }}>Post Types</h3>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="sitemap-posts">Posts</label>
                            <p className="settings-help">Include posts in sitemap.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="sitemap-posts" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="sitemap-pages">Pages</label>
                            <p className="settings-help">Include pages in sitemap.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="sitemap-pages" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <h3 style={{ marginTop: 'var(--space-lg)' }}>Taxonomies</h3>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="sitemap-categories">Categories</label>
                            <p className="settings-help">Include category archives in sitemap.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="sitemap-categories" type="checkbox" defaultChecked />
                                <span className="toggle-track" />
                                <span className="toggle-text">Enabled</span>
                            </label>
                        </div>
                    </div>
                    <div className="settings-row compact">
                        <div className="settings-label">
                            <label htmlFor="sitemap-tags">Tags</label>
                            <p className="settings-help">Include tag archives in sitemap.</p>
                        </div>
                        <div className="settings-control">
                            <label className="toggle">
                                <input id="sitemap-tags" type="checkbox" />
                                <span className="toggle-track" />
                                <span className="toggle-text">Disabled</span>
                            </label>
                        </div>
                    </div>
                </section>
                <aside className="side-panel">
                    <div className="side-card highlight">
                        <h3>Sitemap URL</h3>
                        <p className="muted">Your sitemap is available at:</p>
                        <div className="key-preview">
                            <span className="code">/wp-sitemap.xml</span>
                        </div>
                        <button type="button" className="button ghost" style={{ marginTop: '12px' }}>View Sitemap</button>
                    </div>
                    <div className="side-card">
                        <h3>Sitemap Stats</h3>
                        <p className="muted">Current sitemap statistics.</p>
                        <div className="key-preview">
                            <span className="muted">Total URLs:</span>
                            <span className="code">128</span>
                        </div>
                        <div className="key-preview">
                            <span className="muted">Last Generated:</span>
                            <span className="code">1 hour ago</span>
                        </div>
                    </div>
                </aside>
            </div>
        </div>
    );
};

export default Sitemap;
