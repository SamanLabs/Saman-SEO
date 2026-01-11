const Dashboard = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Dashboard</h1>
                    <p>SEO health, content insights, and optimization status at a glance.</p>
                </div>
                <button type="button" className="button primary">Run SEO Audit</button>
            </div>
            <div className="card-grid">
                <div className="card">
                    <div className="card-header">
                        <h3>SEO Score</h3>
                        <span className="pill success">85% Healthy</span>
                    </div>
                    <div className="gauge" style={{ '--value': '85' }}>
                        <div className="gauge-center">
                            <div className="gauge-value">85%</div>
                            <div className="gauge-label">Good</div>
                        </div>
                    </div>
                    <p className="card-note">3 optimization opportunities found.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>Content Coverage</h3>
                        <span className="pill warning">7 days</span>
                    </div>
                    <div className="spark-bars" aria-hidden="true">
                        <span style={{ height: '45%' }} />
                        <span style={{ height: '68%' }} />
                        <span style={{ height: '72%' }} />
                        <span style={{ height: '38%' }} />
                        <span style={{ height: '90%' }} />
                        <span style={{ height: '64%' }} />
                        <span style={{ height: '52%' }} />
                    </div>
                    <p className="card-note">42 pages indexed, 8 pending.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>Sitemap Status</h3>
                        <span className="pill success">Active</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot success" aria-hidden="true" />
                        <div>
                            <div className="status-title">All entries valid</div>
                            <div className="status-subtitle">Last generated: 1 hour ago</div>
                        </div>
                    </div>
                    <p className="card-note">128 URLs in sitemap.</p>
                </div>
            </div>
            <div className="card-grid" style={{ marginTop: 'var(--space-lg)' }}>
                <div className="card">
                    <div className="card-header">
                        <h3>Redirects</h3>
                        <span className="pill">45 Active</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot success" aria-hidden="true" />
                        <div>
                            <div className="status-title">All redirects working</div>
                            <div className="status-subtitle">12 hits today</div>
                        </div>
                    </div>
                    <p className="card-note">No broken redirects detected.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>404 Errors</h3>
                        <span className="pill warning">8 Found</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot warning" aria-hidden="true" />
                        <div>
                            <div className="status-title">8 errors logged</div>
                            <div className="status-subtitle">Last 30 days</div>
                        </div>
                    </div>
                    <p className="card-note">Consider creating redirects.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>Schema Status</h3>
                        <span className="pill success">Valid</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot success" aria-hidden="true" />
                        <div>
                            <div className="status-title">Schema markup active</div>
                            <div className="status-subtitle">Organization + Articles</div>
                        </div>
                    </div>
                    <p className="card-note">No validation errors.</p>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
