const More = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>More from Pilot</h1>
                    <p>Expand your security and SEO toolkit with trusted companion plugins.</p>
                </div>
                <button type="button" className="button ghost">View All Plugins</button>
            </div>
            <div className="pilot-grid">
                <div className="pilot-card active">
                    <div className="pilot-card-head">
                        <div className="pilot-card-identity">
                            <span className="pilot-card-mark seo" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" focusable="false">
                                    <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
                                </svg>
                            </span>
                            <div>
                                <div className="pilot-card-title">
                                    <h3>WP SEO Pilot</h3>
                                    <span className="badge success">Installed</span>
                                </div>
                                <p className="pilot-card-tagline">Performance-led SEO insights.</p>
                            </div>
                        </div>
                        <label className="toggle">
                            <input type="checkbox" defaultChecked />
                            <span className="toggle-track" />
                            <span className="toggle-text">Enabled</span>
                        </label>
                    </div>
                    <p className="pilot-card-desc">Actionable SEO guidance, audits, sitemaps, redirects, and ranking insights.</p>
                    <div className="pilot-card-meta">
                        <span className="pill success">Active</span>
                        <span className="pill">Version 0.1.41</span>
                    </div>
                </div>
                <div className="pilot-card">
                    <div className="pilot-card-head">
                        <div className="pilot-card-identity">
                            <span className="pilot-card-mark security" aria-hidden="true">
                                <svg viewBox="0 0 24 24" role="img" focusable="false">
                                    <path d="M12 2L4 5.4v6.2c0 5.1 3.4 9.7 8 10.4 4.6-.7 8-5.3 8-10.4V5.4L12 2zm0 2.2l6 2.3v5.1c0 4-2.5 7.6-6 8.3-3.5-.7-6-4.3-6-8.3V6.5l6-2.3z" />
                                    <path d="M10.5 12.7l-2-2-1.3 1.3 3.3 3.3 5.3-5.3-1.3-1.3-4 4z" />
                                </svg>
                            </span>
                            <div>
                                <div className="pilot-card-title">
                                    <h3>WP Security Pilot</h3>
                                    <span className="badge">Available</span>
                                </div>
                                <p className="pilot-card-tagline">Open standard security for WordPress.</p>
                            </div>
                        </div>
                        <label className="toggle">
                            <input type="checkbox" />
                            <span className="toggle-track" />
                            <span className="toggle-text">Disabled</span>
                        </label>
                    </div>
                    <p className="pilot-card-desc">Core security suite with firewall, scans, and hardening controls.</p>
                    <div className="pilot-card-meta">
                        <span className="pill warning">Not Installed</span>
                        <button type="button" className="button primary">Get Plugin</button>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default More;
