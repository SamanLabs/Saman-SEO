import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Issue type labels
const ISSUE_TYPE_LABELS = {
    title_missing: 'Missing Meta Title',
    title_length: 'Title Too Long',
    description_missing: 'Missing Meta Description',
    description_length: 'Description Too Long',
    missing_alt: 'Missing Alt Text',
    low_word_count: 'Low Word Count',
    missing_h1: 'Missing H1 Heading',
};

// Severity colors and labels
const SEVERITY_CONFIG = {
    high: { label: 'Critical', class: 'danger', color: 'var(--color-danger)' },
    medium: { label: 'Warning', class: 'warning', color: 'var(--color-warning)' },
    low: { label: 'Suggestion', class: 'muted', color: 'var(--color-muted)' },
};

const Audit = () => {
    const [loading, setLoading] = useState(true);
    const [running, setRunning] = useState(false);
    const [auditData, setAuditData] = useState(null);
    const [message, setMessage] = useState({ type: '', text: '' });
    const [expandedType, setExpandedType] = useState(null);
    const [applyingRecommendation, setApplyingRecommendation] = useState(null);

    // Fetch audit data
    const fetchAudit = useCallback(async () => {
        setLoading(true);
        try {
            const res = await apiFetch({ path: '/saman-seo/v1/audit' });
            if (res.success) {
                setAuditData(res.data);
            }
        } catch (error) {
            console.error('Failed to fetch audit:', error);
            setMessage({ type: 'error', text: 'Failed to load audit data.' });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchAudit();
    }, [fetchAudit]);

    // Run new audit
    const handleRunAudit = async () => {
        setRunning(true);
        setMessage({ type: '', text: '' });
        try {
            const res = await apiFetch({
                path: '/saman-seo/v1/audit/run',
                method: 'POST',
                data: { post_type: 'any', limit: 100 },
            });
            if (res.success) {
                setAuditData(res.data);
                setMessage({ type: 'success', text: `Audit complete! Scanned ${res.data.scanned} posts.` });
            }
        } catch (error) {
            console.error('Failed to run audit:', error);
            setMessage({ type: 'error', text: 'Failed to run audit.' });
        } finally {
            setRunning(false);
        }
    };

    // Apply recommendation
    const handleApplyRecommendation = async (rec) => {
        setApplyingRecommendation(rec.post_id);
        try {
            const res = await apiFetch({
                path: `/saman-seo/v1/audit/apply/${rec.post_id}`,
                method: 'POST',
                data: {
                    title: rec.suggested_title,
                    description: rec.suggested_description,
                },
            });
            if (res.success) {
                setMessage({ type: 'success', text: `Applied recommendations to "${rec.title}"` });
                // Remove from recommendations list
                setAuditData(prev => ({
                    ...prev,
                    recommendations: prev.recommendations.filter(r => r.post_id !== rec.post_id),
                }));
            }
        } catch (error) {
            console.error('Failed to apply recommendation:', error);
            setMessage({ type: 'error', text: 'Failed to apply recommendation.' });
        } finally {
            setApplyingRecommendation(null);
        }
    };

    // Group issues by type
    const getIssuesByType = () => {
        if (!auditData?.issues) return {};
        const grouped = {};
        auditData.issues.forEach(issue => {
            if (!grouped[issue.type]) {
                grouped[issue.type] = [];
            }
            grouped[issue.type].push(issue);
        });
        return grouped;
    };

    // Get severity for an issue type (use highest severity in group)
    const getTypeSeverity = (issues) => {
        if (issues.some(i => i.severity === 'high')) return 'high';
        if (issues.some(i => i.severity === 'medium')) return 'medium';
        return 'low';
    };

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>SEO Audit</h1>
                        <p>Scan your site for SEO issues and get actionable recommendations.</p>
                    </div>
                </div>
                <div className="loading-state">Loading audit data...</div>
            </div>
        );
    }

    const stats = auditData?.stats || { severity: { high: 0, medium: 0, low: 0 }, total: 0 };
    const issuesByType = getIssuesByType();

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>SEO Audit</h1>
                    <p>Scan your site for SEO issues and get actionable recommendations.</p>
                </div>
                <div className="header-actions">
                    {auditData?.from_cache && (
                        <span className="cache-badge">Cached results</span>
                    )}
                    <button
                        type="button"
                        className="button primary"
                        onClick={handleRunAudit}
                        disabled={running}
                    >
                        {running ? (
                            <>
                                <span className="spinner"></span>
                                Running Audit...
                            </>
                        ) : (
                            'Run Full Audit'
                        )}
                    </button>
                </div>
            </div>

            {message.text && (
                <div className={`notice-message ${message.type}`}>
                    {message.text}
                </div>
            )}

            {/* Stats Cards */}
            <div className="audit-stats-grid">
                <div className={`audit-stat-card ${stats.severity.high > 0 ? 'has-issues' : 'no-issues'}`}>
                    <div className="audit-stat-icon danger">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 9v4M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                        </svg>
                    </div>
                    <div className="audit-stat-content">
                        <div className="audit-stat-number">{stats.severity.high}</div>
                        <div className="audit-stat-label">Critical Issues</div>
                        <div className="audit-stat-desc">
                            {stats.severity.high === 0 ? 'All critical checks passed' : 'Issues severely impacting SEO'}
                        </div>
                    </div>
                </div>

                <div className={`audit-stat-card ${stats.severity.medium > 0 ? 'has-issues' : 'no-issues'}`}>
                    <div className="audit-stat-icon warning">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>
                    <div className="audit-stat-content">
                        <div className="audit-stat-number">{stats.severity.medium}</div>
                        <div className="audit-stat-label">Warnings</div>
                        <div className="audit-stat-desc">
                            {stats.severity.medium === 0 ? 'No warnings found' : 'Issues that may affect rankings'}
                        </div>
                    </div>
                </div>

                <div className="audit-stat-card">
                    <div className="audit-stat-icon muted">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>
                    <div className="audit-stat-content">
                        <div className="audit-stat-number">{stats.severity.low}</div>
                        <div className="audit-stat-label">Suggestions</div>
                        <div className="audit-stat-desc">
                            {stats.severity.low === 0 ? 'No suggestions' : 'Optional improvements available'}
                        </div>
                    </div>
                </div>

                <div className="audit-stat-card info">
                    <div className="audit-stat-icon info">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                            <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                    </div>
                    <div className="audit-stat-content">
                        <div className="audit-stat-number">{auditData?.scanned || 0}</div>
                        <div className="audit-stat-label">Posts Scanned</div>
                        <div className="audit-stat-desc">
                            {stats.posts_with_issues || 0} with issues
                        </div>
                    </div>
                </div>
            </div>

            {/* Issues by Type */}
            <section className="audit-section">
                <div className="audit-section-header">
                    <h2>Issues by Type</h2>
                    <p className="muted">Click on an issue type to see affected posts.</p>
                </div>

                {Object.keys(issuesByType).length === 0 ? (
                    <div className="empty-state">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" style={{ color: 'var(--color-success)' }}>
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                        </svg>
                        <h3>No issues found!</h3>
                        <p>Your site is in great SEO shape.</p>
                    </div>
                ) : (
                    <div className="audit-issues-list">
                        {Object.entries(issuesByType).map(([type, issues]) => {
                            const severity = getTypeSeverity(issues);
                            const config = SEVERITY_CONFIG[severity];
                            const isExpanded = expandedType === type;

                            return (
                                <div key={type} className={`audit-issue-group ${isExpanded ? 'expanded' : ''}`}>
                                    <button
                                        type="button"
                                        className="audit-issue-header"
                                        onClick={() => setExpandedType(isExpanded ? null : type)}
                                    >
                                        <div className="audit-issue-info">
                                            <span className={`severity-dot ${config.class}`}></span>
                                            <span className="audit-issue-type">{ISSUE_TYPE_LABELS[type] || type}</span>
                                            <span className={`pill ${config.class}`}>{issues.length}</span>
                                        </div>
                                        <svg
                                            className={`chevron ${isExpanded ? 'expanded' : ''}`}
                                            width="20"
                                            height="20"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                        >
                                            <path d="M19 9l-7 7-7-7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                        </svg>
                                    </button>

                                    {isExpanded && (
                                        <div className="audit-issue-posts">
                                            <table className="data-table compact">
                                                <thead>
                                                    <tr>
                                                        <th>Post</th>
                                                        <th>Issue</th>
                                                        <th>Action</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {issues.map((issue, idx) => (
                                                        <tr key={idx}>
                                                            <td>
                                                                <a href={issue.edit_url} target="_blank" rel="noopener noreferrer">
                                                                    {issue.title}
                                                                </a>
                                                            </td>
                                                            <td>{issue.message}</td>
                                                            <td>
                                                                <a
                                                                    href={issue.edit_url}
                                                                    target="_blank"
                                                                    rel="noopener noreferrer"
                                                                    className="link-button"
                                                                >
                                                                    Edit Post
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
            </section>

            {/* Recommendations */}
            {auditData?.recommendations && auditData.recommendations.length > 0 && (
                <section className="audit-section">
                    <div className="audit-section-header">
                        <h2>Quick Fixes</h2>
                        <p className="muted">Apply suggested meta titles and descriptions with one click.</p>
                    </div>

                    <div className="recommendations-list">
                        {auditData.recommendations.map(rec => (
                            <div key={rec.post_id} className="recommendation-card">
                                <div className="recommendation-header">
                                    <h4>{rec.title}</h4>
                                    <a href={rec.edit_url} target="_blank" rel="noopener noreferrer" className="link-button small">
                                        Edit Post
                                    </a>
                                </div>
                                <div className="recommendation-suggestions">
                                    <div className="suggestion-item">
                                        <label>Suggested Title</label>
                                        <div className="suggestion-value">{rec.suggested_title}</div>
                                    </div>
                                    <div className="suggestion-item">
                                        <label>Suggested Description</label>
                                        <div className="suggestion-value">{rec.suggested_description || '(No suggestion available)'}</div>
                                    </div>
                                </div>
                                <div className="recommendation-actions">
                                    <button
                                        type="button"
                                        className="button primary small"
                                        onClick={() => handleApplyRecommendation(rec)}
                                        disabled={applyingRecommendation === rec.post_id}
                                    >
                                        {applyingRecommendation === rec.post_id ? 'Applying...' : 'Apply Suggestions'}
                                    </button>
                                </div>
                            </div>
                        ))}
                    </div>
                </section>
            )}
        </div>
    );
};

export default Audit;
