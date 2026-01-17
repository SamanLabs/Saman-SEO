import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const TAB_OPTIONS = [
    { value: 'overview', label: 'Overview' },
    { value: 'broken', label: 'Broken Links' },
    { value: 'orphans', label: 'Orphan Pages' },
];

const LinkHealth = () => {
    const [activeTab, setActiveTab] = useState('overview');
    const [loading, setLoading] = useState(true);
    const [summary, setSummary] = useState(null);
    const [brokenLinks, setBrokenLinks] = useState({ items: [], total: 0, page: 1, total_pages: 1 });
    const [orphanPages, setOrphanPages] = useState({ items: [], total: 0, page: 1, total_pages: 1 });
    const [scanning, setScanning] = useState(false);
    const [scanStatus, setScanStatus] = useState(null);
    const [recheckingId, setRecheckingId] = useState(null);
    const [linkTypeFilter, setLinkTypeFilter] = useState('');

    // Fetch summary
    const fetchSummary = useCallback(async () => {
        try {
            const res = await apiFetch({ path: '/saman-seo/v1/link-health/summary' });
            if (res.success) {
                setSummary(res.data);
            }
        } catch (error) {
            console.error('Failed to fetch summary:', error);
        }
    }, []);

    // Fetch broken links
    const fetchBrokenLinks = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page, per_page: 50, type: linkTypeFilter });
            const res = await apiFetch({ path: `/saman-seo/v1/link-health/broken?${params}` });
            if (res.success) {
                setBrokenLinks(res.data);
            }
        } catch (error) {
            console.error('Failed to fetch broken links:', error);
        } finally {
            setLoading(false);
        }
    }, [linkTypeFilter]);

    // Fetch orphan pages
    const fetchOrphanPages = useCallback(async (page = 1) => {
        setLoading(true);
        try {
            const params = new URLSearchParams({ page, per_page: 50 });
            const res = await apiFetch({ path: `/saman-seo/v1/link-health/orphans?${params}` });
            if (res.success) {
                setOrphanPages(res.data);
            }
        } catch (error) {
            console.error('Failed to fetch orphan pages:', error);
        } finally {
            setLoading(false);
        }
    }, []);

    // Check scan status
    const checkScanStatus = useCallback(async () => {
        try {
            const res = await apiFetch({ path: '/saman-seo/v1/link-health/scan/status' });
            if (res.success && res.data) {
                setScanStatus(res.data);
                if (res.data.status === 'running') {
                    setScanning(true);
                } else {
                    setScanning(false);
                }
            } else {
                setScanStatus(null);
                setScanning(false);
            }
        } catch (error) {
            console.error('Failed to check scan status:', error);
        }
    }, []);

    // Initial load
    useEffect(() => {
        fetchSummary();
        checkScanStatus();
    }, [fetchSummary, checkScanStatus]);

    // Load data based on active tab
    useEffect(() => {
        if (activeTab === 'broken') {
            fetchBrokenLinks(1);
        } else if (activeTab === 'orphans') {
            fetchOrphanPages(1);
        }
    }, [activeTab, fetchBrokenLinks, fetchOrphanPages]);

    // Poll scan status while scanning
    useEffect(() => {
        if (!scanning) return;
        const interval = setInterval(() => {
            checkScanStatus();
            fetchSummary();
        }, 3000);
        return () => clearInterval(interval);
    }, [scanning, checkScanStatus, fetchSummary]);

    // Start scan
    const handleStartScan = async () => {
        setScanning(true);
        try {
            await apiFetch({
                path: '/saman-seo/v1/link-health/scan',
                method: 'POST',
                data: { type: 'full' },
            });
            checkScanStatus();
        } catch (error) {
            console.error('Failed to start scan:', error);
            setScanning(false);
        }
    };

    // Recheck a link
    const handleRecheckLink = async (linkId) => {
        setRecheckingId(linkId);
        try {
            const res = await apiFetch({
                path: `/saman-seo/v1/link-health/link/${linkId}/recheck`,
                method: 'POST',
            });
            if (res.success) {
                // Refresh broken links
                fetchBrokenLinks(brokenLinks.page);
                fetchSummary();
            }
        } catch (error) {
            console.error('Failed to recheck link:', error);
        } finally {
            setRecheckingId(null);
        }
    };

    // Delete a link entry
    const handleDeleteLink = async (linkId) => {
        if (!window.confirm('Remove this link from the report? This will not remove the actual link from your content.')) {
            return;
        }
        try {
            await apiFetch({
                path: `/saman-seo/v1/link-health/link/${linkId}`,
                method: 'DELETE',
            });
            fetchBrokenLinks(brokenLinks.page);
            fetchSummary();
        } catch (error) {
            console.error('Failed to delete link:', error);
        }
    };

    // Format date
    const formatDate = (dateStr) => {
        if (!dateStr) return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    // Health score color
    const getHealthColor = (score) => {
        if (score >= 90) return 'success';
        if (score >= 70) return 'warning';
        return 'danger';
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Link Health</h1>
                    <p>Monitor and fix broken links across your site.</p>
                </div>
                <div className="header-actions">
                    <button
                        type="button"
                        className="button primary"
                        onClick={handleStartScan}
                        disabled={scanning}
                    >
                        {scanning ? 'Scanning...' : 'Run Full Scan'}
                    </button>
                </div>
            </div>

            {/* Scan Progress */}
            {scanning && scanStatus && (
                <div className="scan-progress">
                    <div className="scan-progress__info">
                        <span>Scanning posts... {scanStatus.scanned_posts} / {scanStatus.total_posts}</span>
                    </div>
                    <div className="scan-progress__bar">
                        <div
                            className="scan-progress__fill"
                            style={{ width: `${(scanStatus.scanned_posts / scanStatus.total_posts) * 100}%` }}
                        />
                    </div>
                </div>
            )}

            {/* Tabs */}
            <div className="tabs">
                {TAB_OPTIONS.map(tab => (
                    <button
                        key={tab.value}
                        type="button"
                        className={`tab ${activeTab === tab.value ? 'active' : ''}`}
                        onClick={() => setActiveTab(tab.value)}
                    >
                        {tab.label}
                        {tab.value === 'broken' && summary?.broken_links > 0 && (
                            <span className="tab-badge danger">{summary.broken_links}</span>
                        )}
                        {tab.value === 'orphans' && summary?.orphan_pages > 0 && (
                            <span className="tab-badge warning">{summary.orphan_pages}</span>
                        )}
                    </button>
                ))}
            </div>

            {/* Overview Tab */}
            {activeTab === 'overview' && (
                <section className="panel">
                    {!summary ? (
                        <div className="loading-state">Loading summary...</div>
                    ) : (
                        <>
                            {/* Health Score */}
                            <div className="health-score-card">
                                <div className={`health-score ${getHealthColor(summary.health_score)}`}>
                                    <span className="health-score__value">{summary.health_score}%</span>
                                    <span className="health-score__label">Link Health Score</span>
                                </div>
                                <div className="health-score__info">
                                    <p>
                                        {summary.health_score >= 90
                                            ? 'Excellent! Your links are in great shape.'
                                            : summary.health_score >= 70
                                            ? 'Good, but there are some issues to address.'
                                            : 'Needs attention. Multiple broken links detected.'}
                                    </p>
                                    {summary.last_scan && (
                                        <span className="muted">Last scan: {formatDate(summary.last_scan)}</span>
                                    )}
                                </div>
                            </div>

                            {/* Stats Grid */}
                            <div className="stats-grid">
                                <div className="stat-card">
                                    <span className="stat-card__value">{summary.total_links.toLocaleString()}</span>
                                    <span className="stat-card__label">Total Links</span>
                                </div>
                                <div className="stat-card danger">
                                    <span className="stat-card__value">{summary.broken_links.toLocaleString()}</span>
                                    <span className="stat-card__label">Broken Links</span>
                                </div>
                                <div className="stat-card warning">
                                    <span className="stat-card__value">{summary.redirects.toLocaleString()}</span>
                                    <span className="stat-card__label">Redirects</span>
                                </div>
                                <div className="stat-card">
                                    <span className="stat-card__value">{summary.internal.toLocaleString()}</span>
                                    <span className="stat-card__label">Internal Links</span>
                                </div>
                                <div className="stat-card">
                                    <span className="stat-card__value">{summary.external.toLocaleString()}</span>
                                    <span className="stat-card__label">External Links</span>
                                </div>
                                <div className="stat-card warning">
                                    <span className="stat-card__value">{summary.orphan_pages.toLocaleString()}</span>
                                    <span className="stat-card__label">Orphan Pages</span>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            {(summary.broken_links > 0 || summary.orphan_pages > 0) && (
                                <div className="quick-actions">
                                    <h3>Recommended Actions</h3>
                                    {summary.broken_links > 0 && (
                                        <button
                                            type="button"
                                            className="action-card"
                                            onClick={() => setActiveTab('broken')}
                                        >
                                            <span className="action-card__icon danger">!</span>
                                            <div className="action-card__content">
                                                <strong>Fix {summary.broken_links} broken link{summary.broken_links !== 1 ? 's' : ''}</strong>
                                                <span>Update or remove links that return errors</span>
                                            </div>
                                        </button>
                                    )}
                                    {summary.orphan_pages > 0 && (
                                        <button
                                            type="button"
                                            className="action-card"
                                            onClick={() => setActiveTab('orphans')}
                                        >
                                            <span className="action-card__icon warning">?</span>
                                            <div className="action-card__content">
                                                <strong>Link to {summary.orphan_pages} orphan page{summary.orphan_pages !== 1 ? 's' : ''}</strong>
                                                <span>Pages with no internal links pointing to them</span>
                                            </div>
                                        </button>
                                    )}
                                </div>
                            )}
                        </>
                    )}
                </section>
            )}

            {/* Broken Links Tab */}
            {activeTab === 'broken' && (
                <section className="panel">
                    <div className="panel-header">
                        <h3>Broken Links</h3>
                        <div className="filter-row">
                            <label className="filter-field">
                                <span>Type</span>
                                <select
                                    value={linkTypeFilter}
                                    onChange={(e) => setLinkTypeFilter(e.target.value)}
                                >
                                    <option value="">All Links</option>
                                    <option value="internal">Internal Only</option>
                                    <option value="external">External Only</option>
                                </select>
                            </label>
                        </div>
                    </div>

                    {loading ? (
                        <div className="loading-state">Loading broken links...</div>
                    ) : brokenLinks.items.length === 0 ? (
                        <div className="empty-state">
                            <div className="empty-state__icon success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="48" height="48">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                            </div>
                            <h3>No broken links found</h3>
                            <p>All your links are working correctly.</p>
                        </div>
                    ) : (
                        <>
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Source Page</th>
                                        <th>Broken URL</th>
                                        <th>Link Text</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {brokenLinks.items.map(link => (
                                        <tr key={link.id}>
                                            <td>
                                                <a href={link.source_url} target="_blank" rel="noopener noreferrer">
                                                    {link.source_title || 'Untitled'}
                                                </a>
                                            </td>
                                            <td className="url-cell">
                                                <code>{link.target_url}</code>
                                                <span className={`badge ${link.link_type === 'external' ? 'info' : 'muted'}`}>
                                                    {link.link_type}
                                                </span>
                                            </td>
                                            <td>{link.link_text || '-'}</td>
                                            <td>
                                                <span className="badge danger">
                                                    {link.http_code || 'Error'}
                                                </span>
                                                {link.error_message && (
                                                    <span className="muted small" title={link.error_message}>
                                                        {link.error_message.substring(0, 30)}...
                                                    </span>
                                                )}
                                            </td>
                                            <td className="action-cell">
                                                <div className="action-buttons">
                                                    <a
                                                        href={`/wp-admin/post.php?post=${link.source_post_id}&action=edit`}
                                                        className="button small"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                    >
                                                        Edit
                                                    </a>
                                                    <button
                                                        type="button"
                                                        className="button ghost small"
                                                        onClick={() => handleRecheckLink(link.id)}
                                                        disabled={recheckingId === link.id}
                                                    >
                                                        {recheckingId === link.id ? '...' : 'Recheck'}
                                                    </button>
                                                    <button
                                                        type="button"
                                                        className="button ghost small danger"
                                                        onClick={() => handleDeleteLink(link.id)}
                                                    >
                                                        Dismiss
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {brokenLinks.total_pages > 1 && (
                                <div className="pagination">
                                    <span className="pagination-info">
                                        {brokenLinks.total.toLocaleString()} broken link{brokenLinks.total !== 1 ? 's' : ''}
                                    </span>
                                    <div className="pagination-links">
                                        <button
                                            type="button"
                                            className="pagination-btn"
                                            disabled={brokenLinks.page <= 1}
                                            onClick={() => fetchBrokenLinks(brokenLinks.page - 1)}
                                        >
                                            &lsaquo; Previous
                                        </button>
                                        <span className="pagination-current">
                                            {brokenLinks.page} of {brokenLinks.total_pages}
                                        </span>
                                        <button
                                            type="button"
                                            className="pagination-btn"
                                            disabled={brokenLinks.page >= brokenLinks.total_pages}
                                            onClick={() => fetchBrokenLinks(brokenLinks.page + 1)}
                                        >
                                            Next &rsaquo;
                                        </button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </section>
            )}

            {/* Orphan Pages Tab */}
            {activeTab === 'orphans' && (
                <section className="panel">
                    <div className="panel-header">
                        <h3>Orphan Pages</h3>
                        <p className="panel-desc">
                            Pages with no internal links pointing to them. Consider adding links from other content.
                        </p>
                    </div>

                    {loading ? (
                        <div className="loading-state">Loading orphan pages...</div>
                    ) : orphanPages.items.length === 0 ? (
                        <div className="empty-state">
                            <div className="empty-state__icon success">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="48" height="48">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M9 12l2 2 4-4"/>
                                </svg>
                            </div>
                            <h3>No orphan pages found</h3>
                            <p>All your pages have at least one internal link pointing to them.</p>
                        </div>
                    ) : (
                        <>
                            <table className="data-table">
                                <thead>
                                    <tr>
                                        <th>Page Title</th>
                                        <th>Type</th>
                                        <th>Published</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {orphanPages.items.map(page => (
                                        <tr key={page.id}>
                                            <td>
                                                <a href={page.url} target="_blank" rel="noopener noreferrer">
                                                    {page.title || 'Untitled'}
                                                </a>
                                            </td>
                                            <td>
                                                <span className="badge muted">{page.post_type}</span>
                                            </td>
                                            <td>{formatDate(page.post_date)}</td>
                                            <td className="action-cell">
                                                <a
                                                    href={page.edit_url}
                                                    className="button small"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >
                                                    Edit
                                                </a>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>

                            {/* Pagination */}
                            {orphanPages.total_pages > 1 && (
                                <div className="pagination">
                                    <span className="pagination-info">
                                        {orphanPages.total.toLocaleString()} orphan page{orphanPages.total !== 1 ? 's' : ''}
                                    </span>
                                    <div className="pagination-links">
                                        <button
                                            type="button"
                                            className="pagination-btn"
                                            disabled={orphanPages.page <= 1}
                                            onClick={() => fetchOrphanPages(orphanPages.page - 1)}
                                        >
                                            &lsaquo; Previous
                                        </button>
                                        <span className="pagination-current">
                                            {orphanPages.page} of {orphanPages.total_pages}
                                        </span>
                                        <button
                                            type="button"
                                            className="pagination-btn"
                                            disabled={orphanPages.page >= orphanPages.total_pages}
                                            onClick={() => fetchOrphanPages(orphanPages.page + 1)}
                                        >
                                            Next &rsaquo;
                                        </button>
                                    </div>
                                </div>
                            )}
                        </>
                    )}
                </section>
            )}
        </div>
    );
};

export default LinkHealth;
