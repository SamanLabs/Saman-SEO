import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const SORT_OPTIONS = [
    { value: 'recent', label: 'Most recent' },
    { value: 'top', label: 'Top hits' },
];

const PER_PAGE_OPTIONS = [25, 50, 100, 200];

const Log404 = ({ onNavigate }) => {
    // 404 Log state
    const [logEntries, setLogEntries] = useState([]);
    const [logLoading, setLogLoading] = useState(true);
    const [logTotal, setLogTotal] = useState(0);
    const [logPage, setLogPage] = useState(1);
    const [logPerPage, setLogPerPage] = useState(50);
    const [logTotalPages, setLogTotalPages] = useState(1);
    const [logSort, setLogSort] = useState('recent');
    const [hideSpam, setHideSpam] = useState(true);
    const [hideImages, setHideImages] = useState(false);
    const [clearingLog, setClearingLog] = useState(false);

    // Fetch 404 log
    const fetchLog = useCallback(async () => {
        setLogLoading(true);
        try {
            const params = new URLSearchParams({
                sort: logSort,
                per_page: logPerPage,
                page: logPage,
                hide_spam: hideSpam ? '1' : '0',
                hide_images: hideImages ? '1' : '0',
            });
            const response = await apiFetch({ path: `/wpseopilot/v2/404-log?${params}` });
            if (response.success) {
                setLogEntries(response.data.items);
                setLogTotal(response.data.total);
                setLogTotalPages(response.data.total_pages);
            }
        } catch (error) {
            console.error('Failed to fetch 404 log:', error);
        } finally {
            setLogLoading(false);
        }
    }, [logSort, logPerPage, logPage, hideSpam, hideImages]);

    // Load data on mount
    useEffect(() => {
        fetchLog();
    }, [fetchLog]);

    // Create redirect from 404 entry - navigate to redirects page
    const handleCreateFrom404 = (entry) => {
        // Store the source URL in sessionStorage so Redirects page can pick it up
        sessionStorage.setItem('wpseopilot_redirect_source', entry.request_uri);
        if (onNavigate) {
            onNavigate('redirects');
        }
    };

    // Clear 404 log
    const handleClearLog = async () => {
        if (!window.confirm('Are you sure you want to clear the entire 404 log? This cannot be undone.')) {
            return;
        }

        setClearingLog(true);
        try {
            await apiFetch({
                path: '/wpseopilot/v2/404-log',
                method: 'DELETE',
            });
            setLogEntries([]);
            setLogTotal(0);
            setLogTotalPages(1);
            setLogPage(1);
        } catch (error) {
            console.error('Failed to clear log:', error);
        } finally {
            setClearingLog(false);
        }
    };

    // Format date
    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>404 Log</h1>
                    <p>Monitor broken links and create redirects to fix them.</p>
                </div>
                <div className="header-actions">
                    <button
                        type="button"
                        className="button ghost"
                        onClick={handleClearLog}
                        disabled={clearingLog || logEntries.length === 0}
                    >
                        {clearingLog ? 'Clearing...' : 'Clear Log'}
                    </button>
                </div>
            </div>

            <section className="panel">
                {/* Stats Bar */}
                <div className="table-toolbar">
                    <div className="stats-bar">
                        <div className="stat-box">
                            <span className="stat-box__value">{logTotal.toLocaleString()}</span>
                            <span className="stat-box__label">Total Entries</span>
                        </div>
                        <div className="stat-box">
                            <span className="stat-box__value">{logEntries.filter(e => !e.redirect_exists).length}</span>
                            <span className="stat-box__label">Need Redirect</span>
                        </div>
                    </div>
                </div>

                {/* Filters */}
                <div className="filter-form">
                    <div className="filter-row">
                        <label className="filter-field">
                            <span>Sort by</span>
                            <select
                                value={logSort}
                                onChange={(e) => {
                                    setLogSort(e.target.value);
                                    setLogPage(1);
                                }}
                            >
                                {SORT_OPTIONS.map(opt => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                        </label>
                        <label className="filter-field">
                            <span>Rows per page</span>
                            <select
                                value={logPerPage}
                                onChange={(e) => {
                                    setLogPerPage(parseInt(e.target.value, 10));
                                    setLogPage(1);
                                }}
                            >
                                {PER_PAGE_OPTIONS.map(opt => (
                                    <option key={opt} value={opt}>{opt}</option>
                                ))}
                            </select>
                        </label>
                        <label className="filter-checkbox">
                            <input
                                type="checkbox"
                                checked={hideSpam}
                                onChange={(e) => {
                                    setHideSpam(e.target.checked);
                                    setLogPage(1);
                                }}
                            />
                            <span>Hide spammy extensions</span>
                        </label>
                        <label className="filter-checkbox">
                            <input
                                type="checkbox"
                                checked={hideImages}
                                onChange={(e) => {
                                    setHideImages(e.target.checked);
                                    setLogPage(1);
                                }}
                            />
                            <span>Hide image extensions</span>
                        </label>
                    </div>
                </div>

                {/* 404 Log Table */}
                {logLoading ? (
                    <div className="loading-state">Loading 404 log...</div>
                ) : logEntries.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" width="48" height="48">
                                <circle cx="12" cy="12" r="10"/>
                                <path d="M9 9l6 6m0-6l-6 6"/>
                            </svg>
                        </div>
                        <h3>No 404 errors logged</h3>
                        <p>Great news! Your site doesn't have any broken links recorded yet.</p>
                    </div>
                ) : (
                    <>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th>Request URL</th>
                                    <th>Hits</th>
                                    <th>Last Seen</th>
                                    <th>Device</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                {logEntries.map(entry => (
                                    <tr key={entry.id}>
                                        <td className="url-cell">
                                            <code>{entry.request_uri}</code>
                                            {entry.redirect_exists && (
                                                <span className="badge success">Redirect exists</span>
                                            )}
                                        </td>
                                        <td>
                                            <span className={`hits-badge ${entry.hits > 10 ? 'high' : entry.hits > 5 ? 'medium' : ''}`}>
                                                {entry.hits}
                                            </span>
                                        </td>
                                        <td>{formatDate(entry.last_seen)}</td>
                                        <td>{entry.device_label}</td>
                                        <td>
                                            {!entry.redirect_exists ? (
                                                <button
                                                    type="button"
                                                    className="button primary small"
                                                    onClick={() => handleCreateFrom404(entry)}
                                                >
                                                    Create Redirect
                                                </button>
                                            ) : (
                                                <span className="muted">-</span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {logTotalPages > 1 && (
                            <div className="pagination">
                                <span className="pagination-info">
                                    {logTotal.toLocaleString()} {logTotal === 1 ? 'item' : 'items'}
                                </span>
                                <div className="pagination-links">
                                    <button
                                        type="button"
                                        className="pagination-btn"
                                        disabled={logPage <= 1}
                                        onClick={() => setLogPage(logPage - 1)}
                                    >
                                        &lsaquo; Previous
                                    </button>
                                    <span className="pagination-current">
                                        {logPage} of {logTotalPages}
                                    </span>
                                    <button
                                        type="button"
                                        className="pagination-btn"
                                        disabled={logPage >= logTotalPages}
                                        onClick={() => setLogPage(logPage + 1)}
                                    >
                                        Next &rsaquo;
                                    </button>
                                </div>
                            </div>
                        )}
                    </>
                )}
            </section>
        </div>
    );
};

export default Log404;
