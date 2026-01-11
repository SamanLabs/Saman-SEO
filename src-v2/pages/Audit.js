const Audit = () => {
    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>SEO Audit</h1>
                    <p>Scan your site for SEO issues and get actionable recommendations.</p>
                </div>
                <button type="button" className="button primary">Run Full Audit</button>
            </div>
            <div className="card-grid">
                <div className="card">
                    <div className="card-header">
                        <h3>Critical Issues</h3>
                        <span className="pill success">0</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot success" aria-hidden="true" />
                        <div>
                            <div className="status-title">No critical issues</div>
                            <div className="status-subtitle">All critical checks passed</div>
                        </div>
                    </div>
                    <p className="card-note">Issues that severely impact SEO.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>Warnings</h3>
                        <span className="pill warning">3</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot warning" aria-hidden="true" />
                        <div>
                            <div className="status-title">3 warnings found</div>
                            <div className="status-subtitle">Review recommended</div>
                        </div>
                    </div>
                    <p className="card-note">Issues that may affect rankings.</p>
                </div>
                <div className="card">
                    <div className="card-header">
                        <h3>Suggestions</h3>
                        <span className="pill">8</span>
                    </div>
                    <div className="status-row">
                        <span className="status-dot" aria-hidden="true" style={{ background: 'var(--color-text-muted)' }} />
                        <div>
                            <div className="status-title">8 suggestions</div>
                            <div className="status-subtitle">Optional improvements</div>
                        </div>
                    </div>
                    <p className="card-note">Minor optimizations available.</p>
                </div>
            </div>
            <section className="panel" style={{ marginTop: 'var(--space-lg)' }}>
                <div className="table-toolbar">
                    <div>
                        <h3>Issue Details</h3>
                        <p className="muted">Review and address detected SEO issues.</p>
                    </div>
                    <button type="button" className="button ghost">Export Report</button>
                </div>
                <table className="data-table">
                    <thead>
                        <tr>
                            <th>Issue</th>
                            <th>Affected</th>
                            <th>Severity</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Missing meta description</td>
                            <td>5 posts</td>
                            <td><span className="pill warning">Warning</span></td>
                            <td><button type="button" className="link-button">View Posts</button></td>
                        </tr>
                        <tr>
                            <td>Title too long</td>
                            <td>2 pages</td>
                            <td><span className="pill warning">Warning</span></td>
                            <td><button type="button" className="link-button">View Pages</button></td>
                        </tr>
                        <tr>
                            <td>Missing alt text on images</td>
                            <td>12 images</td>
                            <td><span className="pill">Suggestion</span></td>
                            <td><button type="button" className="link-button">View Images</button></td>
                        </tr>
                        <tr>
                            <td>Low word count</td>
                            <td>3 posts</td>
                            <td><span className="pill">Suggestion</span></td>
                            <td><button type="button" className="link-button">View Posts</button></td>
                        </tr>
                    </tbody>
                </table>
            </section>
        </div>
    );
};

export default Audit;
