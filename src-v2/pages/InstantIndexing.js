/**
 * Instant Indexing Page
 *
 * Bulk indexing management via IndexNow protocol.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const InstantIndexing = ({ onNavigate }) => {
    // State
    const [settings, setSettings] = useState(null);
    const [posts, setPosts] = useState([]);
    const [stats, setStats] = useState(null);
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [selectedPosts, setSelectedPosts] = useState([]);
    const [activeTab, setActiveTab] = useState('posts');

    // Filters
    const [postType, setPostType] = useState('post');
    const [search, setSearch] = useState('');
    const [statusFilter, setStatusFilter] = useState('');
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);

    // Post types
    const [postTypes, setPostTypes] = useState([]);

    // Fetch settings and options
    useEffect(() => {
        const fetchInitial = async () => {
            try {
                const [settingsRes, optionsRes, statsRes] = await Promise.all([
                    apiFetch({ path: '/saman-seo/v1/indexnow/settings' }),
                    apiFetch({ path: '/saman-seo/v1/indexnow/options' }),
                    apiFetch({ path: '/saman-seo/v1/indexnow/stats' }),
                ]);

                if (settingsRes.success) {
                    setSettings(settingsRes.data);
                }
                if (optionsRes.success) {
                    setPostTypes(optionsRes.data.post_types || []);
                }
                if (statsRes.success) {
                    setStats(statsRes.data);
                }
            } catch (err) {
                console.error('Failed to fetch settings:', err);
            }
        };

        fetchInitial();
    }, []);

    // Fetch posts
    useEffect(() => {
        const fetchPosts = async () => {
            setLoading(true);
            try {
                const response = await apiFetch({
                    path: `/saman-seo/v1/indexnow/posts?post_type=${postType}&page=${page}&per_page=20&search=${search}&status_filter=${statusFilter}`,
                });

                if (response.success) {
                    setPosts(response.data.posts || []);
                    setTotalPages(response.data.pages || 1);
                    if (response.data.stats) {
                        setStats(prev => ({ ...prev, ...response.data.stats }));
                    }
                }
            } catch (err) {
                console.error('Failed to fetch posts:', err);
            } finally {
                setLoading(false);
            }
        };

        fetchPosts();
    }, [postType, page, search, statusFilter]);

    // Fetch logs
    useEffect(() => {
        if (activeTab !== 'logs') return;

        const fetchLogs = async () => {
            try {
                const response = await apiFetch({
                    path: '/saman-seo/v1/indexnow/logs?per_page=50',
                });
                if (response.success) {
                    setLogs(response.data.items || []);
                }
            } catch (err) {
                console.error('Failed to fetch logs:', err);
            }
        };

        fetchLogs();
    }, [activeTab]);

    // Handle select all
    const handleSelectAll = useCallback((e) => {
        if (e.target.checked) {
            setSelectedPosts(posts.map(p => p.id));
        } else {
            setSelectedPosts([]);
        }
    }, [posts]);

    // Handle select single
    const handleSelectPost = useCallback((postId) => {
        setSelectedPosts(prev => {
            if (prev.includes(postId)) {
                return prev.filter(id => id !== postId);
            }
            return [...prev, postId];
        });
    }, []);

    // Handle bulk submit
    const handleBulkSubmit = useCallback(async () => {
        if (selectedPosts.length === 0 || submitting) return;

        setSubmitting(true);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/indexnow/bulk-submit',
                method: 'POST',
                data: { post_ids: selectedPosts },
            });

            if (response.success) {
                // Refresh posts and stats
                const [postsRes, statsRes] = await Promise.all([
                    apiFetch({
                        path: `/saman-seo/v1/indexnow/posts?post_type=${postType}&page=${page}&per_page=20`,
                    }),
                    apiFetch({ path: '/saman-seo/v1/indexnow/stats' }),
                ]);

                if (postsRes.success) {
                    setPosts(postsRes.data.posts || []);
                }
                if (statsRes.success) {
                    setStats(statsRes.data);
                }

                setSelectedPosts([]);
            }
        } catch (err) {
            console.error('Failed to submit:', err);
        } finally {
            setSubmitting(false);
        }
    }, [selectedPosts, submitting, postType, page]);

    // Handle submit single
    const handleSubmitSingle = useCallback(async (postId) => {
        try {
            await apiFetch({
                path: `/saman-seo/v1/indexnow/submit-post/${postId}`,
                method: 'POST',
            });

            // Refresh posts
            const response = await apiFetch({
                path: `/saman-seo/v1/indexnow/posts?post_type=${postType}&page=${page}&per_page=20`,
            });
            if (response.success) {
                setPosts(response.data.posts || []);
            }
        } catch (err) {
            console.error('Failed to submit:', err);
        }
    }, [postType, page]);

    // Status badge component
    const StatusBadge = ({ status }) => {
        const statusConfig = {
            success: { label: 'Indexed', className: 'badge--success' },
            failed: { label: 'Failed', className: 'badge--error' },
            never: { label: 'Not Submitted', className: 'badge--neutral' },
        };
        const config = statusConfig[status] || statusConfig.never;
        return <span className={`badge ${config.className}`}>{config.label}</span>;
    };

    // Not enabled state
    if (settings && !settings.enabled) {
        return (
            <div className="page">
                <div className="page-header">
                    <div>
                        <h1>Instant Indexing</h1>
                        <p>Submit URLs to search engines via IndexNow for faster discovery.</p>
                    </div>
                </div>

                <div className="card">
                    <div className="empty-state">
                        <div className="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                            </svg>
                        </div>
                        <h3>IndexNow is not enabled</h3>
                        <p>Enable IndexNow in Settings to use instant indexing features.</p>
                        <button
                            type="button"
                            className="btn btn--primary"
                            onClick={() => onNavigate && onNavigate('settings')}
                        >
                            Go to Settings
                        </button>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Instant Indexing</h1>
                    <p>Submit URLs to search engines via IndexNow for faster discovery.</p>
                </div>
                {selectedPosts.length > 0 && (
                    <button
                        type="button"
                        className="btn btn--primary"
                        onClick={handleBulkSubmit}
                        disabled={submitting}
                    >
                        {submitting ? 'Submitting...' : `Submit ${selectedPosts.length} URLs`}
                    </button>
                )}
            </div>

            {/* Stats Cards */}
            {stats && (
                <div className="stats-grid">
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.total || 0}</div>
                        <div className="stat-card__label">Total Submissions</div>
                    </div>
                    <div className="stat-card stat-card--success">
                        <div className="stat-card__value">{stats.success || 0}</div>
                        <div className="stat-card__label">Successful</div>
                    </div>
                    <div className="stat-card stat-card--error">
                        <div className="stat-card__value">{stats.failed || 0}</div>
                        <div className="stat-card__label">Failed</div>
                    </div>
                    <div className="stat-card">
                        <div className="stat-card__value">{stats.today || 0}</div>
                        <div className="stat-card__label">Today</div>
                    </div>
                </div>
            )}

            {/* Tabs */}
            <div className="tabs-bar">
                <button
                    type="button"
                    className={`tab-btn ${activeTab === 'posts' ? 'tab-btn--active' : ''}`}
                    onClick={() => setActiveTab('posts')}
                >
                    Posts
                </button>
                <button
                    type="button"
                    className={`tab-btn ${activeTab === 'logs' ? 'tab-btn--active' : ''}`}
                    onClick={() => setActiveTab('logs')}
                >
                    Submission Logs
                </button>
            </div>

            {/* Posts Tab */}
            {activeTab === 'posts' && (
                <div className="card">
                    {/* Filters */}
                    <div className="table-filters">
                        <div className="table-filters__left">
                            <select
                                className="form-select"
                                value={postType}
                                onChange={(e) => { setPostType(e.target.value); setPage(1); }}
                            >
                                {postTypes.map(pt => (
                                    <option key={pt.name} value={pt.name}>{pt.label}</option>
                                ))}
                            </select>
                            <select
                                className="form-select"
                                value={statusFilter}
                                onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
                            >
                                <option value="">All Status</option>
                                <option value="never">Not Submitted</option>
                                <option value="indexed">Indexed</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>
                        <div className="table-filters__right">
                            <input
                                type="text"
                                className="form-input"
                                placeholder="Search posts..."
                                value={search}
                                onChange={(e) => { setSearch(e.target.value); setPage(1); }}
                            />
                        </div>
                    </div>

                    {/* Posts Table */}
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th className="data-table__check">
                                    <input
                                        type="checkbox"
                                        checked={posts.length > 0 && selectedPosts.length === posts.length}
                                        onChange={handleSelectAll}
                                    />
                                </th>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Last Indexed</th>
                                <th className="data-table__actions">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            {loading ? (
                                <tr>
                                    <td colSpan="5" className="data-table__loading">Loading...</td>
                                </tr>
                            ) : posts.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="data-table__empty">No posts found.</td>
                                </tr>
                            ) : (
                                posts.map(post => (
                                    <tr key={post.id}>
                                        <td className="data-table__check">
                                            <input
                                                type="checkbox"
                                                checked={selectedPosts.includes(post.id)}
                                                onChange={() => handleSelectPost(post.id)}
                                            />
                                        </td>
                                        <td>
                                            <div className="post-title">
                                                <strong>{post.title || '(no title)'}</strong>
                                                <a
                                                    href={post.url}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="post-url"
                                                >
                                                    {post.url}
                                                </a>
                                            </div>
                                        </td>
                                        <td>
                                            <StatusBadge status={post.indexing_status} />
                                        </td>
                                        <td>
                                            {post.last_indexed_ago || '-'}
                                        </td>
                                        <td className="data-table__actions">
                                            <button
                                                type="button"
                                                className="btn btn--small btn--secondary"
                                                onClick={() => handleSubmitSingle(post.id)}
                                                title="Submit for indexing"
                                            >
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                    <path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>

                    {/* Pagination */}
                    {totalPages > 1 && (
                        <div className="table-pagination">
                            <button
                                type="button"
                                className="btn btn--small"
                                disabled={page === 1}
                                onClick={() => setPage(p => p - 1)}
                            >
                                Previous
                            </button>
                            <span className="pagination-info">
                                Page {page} of {totalPages}
                            </span>
                            <button
                                type="button"
                                className="btn btn--small"
                                disabled={page === totalPages}
                                onClick={() => setPage(p => p + 1)}
                            >
                                Next
                            </button>
                        </div>
                    )}
                </div>
            )}

            {/* Logs Tab */}
            {activeTab === 'logs' && (
                <div className="card">
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>URL</th>
                                <th>Status</th>
                                <th>Response</th>
                                <th>Search Engine</th>
                                <th>Submitted</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="data-table__empty">No submission logs yet.</td>
                                </tr>
                            ) : (
                                logs.map(log => (
                                    <tr key={log.id}>
                                        <td>
                                            <a
                                                href={log.url}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="post-url"
                                            >
                                                {log.url.length > 50 ? log.url.substring(0, 50) + '...' : log.url}
                                            </a>
                                        </td>
                                        <td>
                                            <StatusBadge status={log.status} />
                                        </td>
                                        <td>{log.response_code || '-'}</td>
                                        <td>{log.search_engine}</td>
                                        <td>{log.submitted_at}</td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
            )}
        </div>
    );
};

export default InstantIndexing;
