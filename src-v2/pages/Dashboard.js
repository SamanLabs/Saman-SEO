import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Score level configuration
const SCORE_LEVELS = {
    excellent: { label: 'Excellent', color: 'var(--color-success)', class: 'success' },
    good: { label: 'Good', color: 'var(--color-success)', class: 'success' },
    fair: { label: 'Fair', color: 'var(--color-warning)', class: 'warning' },
    poor: { label: 'Needs Work', color: 'var(--color-danger)', class: 'danger' },
};

// Priority order for notification types
const PRIORITY_ORDER = {
    error: 1,
    warning: 2,
    info: 3,
};

// Map notification actions to internal views
const ACTION_VIEW_MAP = {
    redirects: 'redirects',
    '404': '404-log',
    audit: 'audit',
    sitemap: 'sitemap',
    content: 'audit',
    seo: 'audit',
};

const Dashboard = ({ onNavigate }) => {
    const [loading, setLoading] = useState(true);
    const [data, setData] = useState(null);
    const [dismissing, setDismissing] = useState(null);
    const [showAllNotifications, setShowAllNotifications] = useState(false);

    // Fetch dashboard data
    const fetchDashboard = useCallback(async () => {
        setLoading(true);
        try {
            const res = await apiFetch({ path: '/saman-seo/v1/dashboard' });
            if (res.success) {
                setData(res.data);
            }
        } catch (error) {
            console.error('Failed to fetch dashboard:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchDashboard();
    }, [fetchDashboard]);

    // Dismiss notification
    const handleDismissNotification = async (id) => {
        setDismissing(id);
        try {
            await apiFetch({
                path: `/saman-seo/v1/dashboard/notifications/${id}/dismiss`,
                method: 'POST',
            });
            setData(prev => ({
                ...prev,
                notifications: prev.notifications.filter(n => n.id !== id),
            }));
        } catch (error) {
            console.error('Failed to dismiss notification:', error);
        } finally {
            setDismissing(null);
        }
    };

    // Handle navigation
    const handleNavigation = (view) => {
        if (onNavigate) {
            onNavigate(view);
        }
    };

    // Navigate to audit
    const handleRunAudit = () => {
        handleNavigation('audit');
    };

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>Dashboard</h1>
                        <p>SEO health, content insights, and optimization status at a glance.</p>
                    </div>
                </div>
                <div className="loading-state">Loading dashboard data...</div>
            </div>
        );
    }

    const seoScore = data?.seo_score || { score: 0, level: 'poor', issues: 0 };
    const coverage = data?.content_coverage || { total: 0, optimized: 0, pending: 0, daily_stats: [] };
    const sitemap = data?.sitemap || { enabled: false, total_urls: 0, last_generated: 'Never' };
    const redirects = data?.redirects || { active: 0, hits_today: 0, suggestions: 0 };
    const errors404 = data?.errors_404 || { total: 0, last_30_days: 0 };
    const schema = data?.schema || { status: 'partial', types: [] };
    const allNotifications = data?.notifications || [];

    // Sort notifications by priority
    const sortedNotifications = [...allNotifications].sort((a, b) => {
        const priorityA = PRIORITY_ORDER[a.type] || 99;
        const priorityB = PRIORITY_ORDER[b.type] || 99;
        return priorityA - priorityB;
    });

    // Show max 3 notifications on dashboard, or all if expanded
    const visibleNotifications = showAllNotifications
        ? sortedNotifications
        : sortedNotifications.slice(0, 3);
    const hasMoreNotifications = sortedNotifications.length > 3;

    const scoreConfig = SCORE_LEVELS[seoScore.level] || SCORE_LEVELS.poor;

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Dashboard</h1>
                    <p>SEO health, content insights, and optimization status at a glance.</p>
                </div>
                <button type="button" className="button primary" onClick={handleRunAudit}>
                    Run SEO Audit
                </button>
            </div>

            {/* Notifications */}
            {visibleNotifications.length > 0 && (
                <div className="dashboard-notifications">
                    {visibleNotifications.map(notif => (
                        <div key={notif.id} className={`notification-banner notification-banner--${notif.type}`}>
                            <div className="notification-icon">
                                {notif.type === 'error' && (
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
                                        <path d="M12 8v4M12 16h.01" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                    </svg>
                                )}
                                {notif.type === 'warning' && (
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 9v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                                    </svg>
                                )}
                                {notif.type === 'info' && (
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="2"/>
                                        <path d="M12 16v-4m0-4h.01" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                    </svg>
                                )}
                            </div>
                            <div className="notification-content">
                                <strong>{notif.title}</strong>
                                <span>{notif.message}</span>
                            </div>
                            <div className="notification-actions">
                                {notif.action && (
                                    <button
                                        type="button"
                                        className="button small"
                                        onClick={() => handleNavigation(ACTION_VIEW_MAP[notif.category] || 'tools')}
                                    >
                                        {notif.action.label}
                                    </button>
                                )}
                                <button
                                    type="button"
                                    className="button ghost small"
                                    onClick={() => handleDismissNotification(notif.id)}
                                    disabled={dismissing === notif.id}
                                    aria-label="Dismiss notification"
                                >
                                    {dismissing === notif.id ? (
                                        <span className="loading-dots">...</span>
                                    ) : (
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                            <path d="M18 6L6 18M6 6l12 12" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                                        </svg>
                                    )}
                                </button>
                            </div>
                        </div>
                    ))}
                    {hasMoreNotifications && (
                        <button
                            type="button"
                            className="notifications-toggle"
                            onClick={() => setShowAllNotifications(!showAllNotifications)}
                        >
                            {showAllNotifications
                                ? 'Show less'
                                : `View all ${sortedNotifications.length} notifications`}
                        </button>
                    )}
                </div>
            )}

            {/* Main Stats Grid */}
            <div className="dashboard-grid">
                {/* SEO Score Card - Large */}
                <div className="dashboard-card seo-score-card">
                    <div className="card-header">
                        <h3>SEO Score</h3>
                        <span className={`pill ${scoreConfig.class}`}>{scoreConfig.label}</span>
                    </div>
                    <div className="seo-score-content">
                        <div className="score-gauge">
                            <svg viewBox="0 0 120 120" className="gauge-svg">
                                <circle
                                    className="gauge-bg"
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    strokeWidth="10"
                                />
                                <circle
                                    className="gauge-fill"
                                    cx="60"
                                    cy="60"
                                    r="50"
                                    fill="none"
                                    strokeWidth="10"
                                    strokeDasharray={`${(seoScore.score / 100) * 314} 314`}
                                    strokeLinecap="round"
                                    style={{ stroke: scoreConfig.color }}
                                />
                            </svg>
                            <div className="gauge-center">
                                <div className="gauge-value">{seoScore.score}%</div>
                                <div className="gauge-label">{seoScore.posts_scored || 0} posts</div>
                            </div>
                        </div>
                        <div className="score-breakdown">
                            <div className="breakdown-item">
                                <span className="breakdown-dot excellent"></span>
                                <span className="breakdown-label">Excellent (80+)</span>
                                <span className="breakdown-value">{seoScore.distribution?.excellent || 0}</span>
                            </div>
                            <div className="breakdown-item">
                                <span className="breakdown-dot good"></span>
                                <span className="breakdown-label">Good (60-79)</span>
                                <span className="breakdown-value">{seoScore.distribution?.good || 0}</span>
                            </div>
                            <div className="breakdown-item">
                                <span className="breakdown-dot fair"></span>
                                <span className="breakdown-label">Fair (40-59)</span>
                                <span className="breakdown-value">{seoScore.distribution?.fair || 0}</span>
                            </div>
                            <div className="breakdown-item">
                                <span className="breakdown-dot poor"></span>
                                <span className="breakdown-label">Poor (&lt;40)</span>
                                <span className="breakdown-value">{seoScore.distribution?.poor || 0}</span>
                            </div>
                        </div>
                    </div>
                    {seoScore.issues > 0 && (
                        <p className="card-note">
                            {seoScore.issues} post{seoScore.issues !== 1 ? 's' : ''} could use optimization.
                        </p>
                    )}
                </div>

                {/* Content Coverage Card - Large */}
                <div className="dashboard-card content-coverage-card">
                    <div className="card-header">
                        <h3>Content Coverage</h3>
                        <span className="pill">{coverage.coverage_pct || 0}% Optimized</span>
                    </div>
                    <div className="coverage-content">
                        <div className="coverage-chart">
                            <div className="spark-bars" aria-hidden="true">
                                {(coverage.daily_stats || []).map((day, idx) => {
                                    const maxOptimized = Math.max(...coverage.daily_stats.map(d => d.optimized || 0), 1);
                                    const height = Math.max(15, ((day.optimized || 0) / maxOptimized) * 100);
                                    return (
                                        <div key={idx} className="spark-bar-wrapper" title={`${day.label}: ${day.optimized} optimized`}>
                                            <span style={{ height: `${height}%` }} />
                                            <span className="spark-label">{day.label}</span>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>
                        <div className="coverage-stats">
                            <div className="coverage-stat">
                                <div className="coverage-stat-value">{coverage.optimized || 0}</div>
                                <div className="coverage-stat-label">Optimized</div>
                            </div>
                            <div className="coverage-stat pending">
                                <div className="coverage-stat-value">{coverage.pending || 0}</div>
                                <div className="coverage-stat-label">Pending</div>
                            </div>
                        </div>
                    </div>
                    <p className="card-note">
                        {coverage.total || 0} total pages &middot; {coverage.with_title || 0} with titles &middot; {coverage.with_description || 0} with descriptions
                    </p>
                </div>
            </div>

            {/* Secondary Stats Grid */}
            <div className="card-grid" style={{ marginTop: 'var(--space-lg)' }}>
                {/* Sitemap Status */}
                <div className="card">
                    <div className="card-header">
                        <h3>Sitemap Status</h3>
                        <span className={`pill ${sitemap.enabled ? 'success' : 'warning'}`}>
                            {sitemap.status_label || (sitemap.enabled ? 'Active' : 'Disabled')}
                        </span>
                    </div>
                    <div className="status-row">
                        <span className={`status-dot ${sitemap.enabled ? 'success' : 'warning'}`} aria-hidden="true" />
                        <div>
                            <div className="status-title">
                                {sitemap.enabled ? `${sitemap.total_urls} URLs indexed` : 'Sitemap disabled'}
                            </div>
                            <div className="status-subtitle">
                                {sitemap.enabled ? `Last generated: ${sitemap.last_generated}` : 'Enable to help search engines'}
                            </div>
                        </div>
                    </div>
                    <button type="button" className="button ghost small" onClick={() => handleNavigation('sitemap')}>
                        {sitemap.enabled ? 'View Sitemap' : 'Enable Sitemap'}
                    </button>
                </div>

                {/* Redirects */}
                <div className="card">
                    <div className="card-header">
                        <h3>Redirects</h3>
                        <span className={`pill ${redirects.active > 0 ? 'success' : ''}`}>{redirects.active} Active</span>
                    </div>
                    <div className="status-row">
                        <span className={`status-dot ${redirects.suggestions > 0 ? 'warning' : 'success'}`} aria-hidden="true" />
                        <div>
                            <div className="status-title">
                                {redirects.suggestions > 0
                                    ? `${redirects.suggestions} pending suggestion${redirects.suggestions !== 1 ? 's' : ''}`
                                    : 'All redirects working'}
                            </div>
                            <div className="status-subtitle">{redirects.hits_today || 0} hits today</div>
                        </div>
                    </div>
                    <button type="button" className="button ghost small" onClick={() => handleNavigation('redirects')}>
                        Manage Redirects
                    </button>
                </div>

                {/* 404 Errors */}
                <div className="card">
                    <div className="card-header">
                        <h3>404 Errors</h3>
                        <span className={`pill ${errors404.last_30_days > 0 ? 'warning' : 'success'}`}>
                            {errors404.last_30_days > 0 ? `${errors404.last_30_days} Found` : 'None'}
                        </span>
                    </div>
                    <div className="status-row">
                        <span className={`status-dot ${errors404.last_30_days > 0 ? 'warning' : 'success'}`} aria-hidden="true" />
                        <div>
                            <div className="status-title">
                                {errors404.last_7_days > 0
                                    ? `${errors404.last_7_days} errors this week`
                                    : 'No recent errors'}
                            </div>
                            <div className="status-subtitle">Last 30 days</div>
                        </div>
                    </div>
                    <button type="button" className="button ghost small" onClick={() => handleNavigation('404-log')}>
                        View 404 Log
                    </button>
                </div>

                {/* Schema Status */}
                <div className="card">
                    <div className="card-header">
                        <h3>Schema Markup</h3>
                        <span className={`pill ${schema.status === 'valid' ? 'success' : ''}`}>
                            {schema.status_label || schema.status}
                        </span>
                    </div>
                    <div className="status-row">
                        <span className={`status-dot ${schema.status === 'valid' ? 'success' : ''}`} aria-hidden="true" />
                        <div>
                            <div className="status-title">
                                {schema.types?.length > 0 ? schema.types.slice(0, 3).join(', ') : 'Basic markup'}
                            </div>
                            <div className="status-subtitle">
                                {schema.types?.length > 3 ? `+${schema.types.length - 3} more types` : 'Schema types active'}
                            </div>
                        </div>
                    </div>
                    <button type="button" className="button ghost small" onClick={() => handleNavigation('search-appearance')}>
                        Configure Schema
                    </button>
                </div>
            </div>

            {/* Quick Actions */}
            <div className="dashboard-actions">
                <h3>Quick Actions</h3>
                <div className="actions-grid">
                    <button type="button" className="action-card" onClick={() => handleNavigation('audit')}>
                        <div className="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                        <div className="action-content">
                            <strong>Run SEO Audit</strong>
                            <span>Scan for issues</span>
                        </div>
                    </button>
                    <button type="button" className="action-card" onClick={() => handleNavigation('redirects')}>
                        <div className="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M13 10V3L4 14h7v7l9-11h-7z" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                            </svg>
                        </div>
                        <div className="action-content">
                            <strong>Manage Redirects</strong>
                            <span>{redirects.suggestions > 0 ? `${redirects.suggestions} suggestions` : 'All good'}</span>
                        </div>
                    </button>
                    <button type="button" className="action-card" onClick={() => handleNavigation('sitemap')}>
                        <div className="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" strokeWidth="2" strokeLinecap="round"/>
                            </svg>
                        </div>
                        <div className="action-content">
                            <strong>View Sitemap</strong>
                            <span>{sitemap.total_urls} URLs</span>
                        </div>
                    </button>
                    <button type="button" className="action-card" onClick={() => handleNavigation('ai-assistant')}>
                        <div className="action-icon">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z" stroke="currentColor" strokeWidth="2"/>
                                <circle cx="9" cy="13" r="1" fill="currentColor"/>
                                <circle cx="15" cy="13" r="1" fill="currentColor"/>
                            </svg>
                        </div>
                        <div className="action-content">
                            <strong>AI Assistant</strong>
                            <span>Generate content</span>
                        </div>
                    </button>
                </div>
            </div>
        </div>
    );
};

export default Dashboard;
