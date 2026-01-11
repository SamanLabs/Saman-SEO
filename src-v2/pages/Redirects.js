import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const STATUS_CODES = [
    { value: 301, label: '301 Permanent' },
    { value: 302, label: '302 Temporary' },
    { value: 307, label: '307' },
    { value: 410, label: '410 Gone' },
];

const Redirects = () => {
    // Redirects state
    const [redirects, setRedirects] = useState([]);
    const [redirectsLoading, setRedirectsLoading] = useState(true);
    const [newSource, setNewSource] = useState('');
    const [newTarget, setNewTarget] = useState('');
    const [newStatusCode, setNewStatusCode] = useState(301);
    const [createLoading, setCreateLoading] = useState(false);
    const [createError, setCreateError] = useState('');

    // Slug suggestions state
    const [suggestions, setSuggestions] = useState([]);
    const [suggestionsLoading, setSuggestionsLoading] = useState(true);

    // Fetch redirects
    const fetchRedirects = useCallback(async () => {
        setRedirectsLoading(true);
        try {
            const response = await apiFetch({ path: '/wpseopilot/v2/redirects' });
            if (response.success) {
                setRedirects(response.data);
            }
        } catch (error) {
            console.error('Failed to fetch redirects:', error);
        } finally {
            setRedirectsLoading(false);
        }
    }, []);

    // Fetch slug suggestions
    const fetchSuggestions = useCallback(async () => {
        setSuggestionsLoading(true);
        try {
            const response = await apiFetch({ path: '/wpseopilot/v2/slug-suggestions' });
            if (response.success) {
                setSuggestions(response.data);
            }
        } catch (error) {
            console.error('Failed to fetch suggestions:', error);
        } finally {
            setSuggestionsLoading(false);
        }
    }, []);

    // Load data on mount
    useEffect(() => {
        fetchRedirects();
        fetchSuggestions();

        // Check if there's a redirect source from 404 Log page
        const storedSource = sessionStorage.getItem('wpseopilot_redirect_source');
        if (storedSource) {
            setNewSource(storedSource);
            sessionStorage.removeItem('wpseopilot_redirect_source');
            // Focus the target field
            setTimeout(() => {
                document.getElementById('redirect-target')?.focus();
            }, 100);
        }
    }, [fetchRedirects, fetchSuggestions]);

    // Create redirect
    const handleCreateRedirect = async (e) => {
        e.preventDefault();
        setCreateError('');
        setCreateLoading(true);

        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/redirects',
                method: 'POST',
                data: {
                    source: newSource,
                    target: newTarget,
                    status_code: newStatusCode,
                },
            });

            if (response.success) {
                setRedirects([response.data, ...redirects]);
                setNewSource('');
                setNewTarget('');
                setNewStatusCode(301);
                // Refetch suggestions in case one was auto-removed
                fetchSuggestions();
            } else {
                setCreateError(response.message || 'Failed to create redirect');
            }
        } catch (error) {
            setCreateError(error.message || 'Failed to create redirect');
        } finally {
            setCreateLoading(false);
        }
    };

    // Delete redirect
    const handleDeleteRedirect = async (id) => {
        if (!window.confirm('Are you sure you want to delete this redirect?')) {
            return;
        }

        try {
            await apiFetch({
                path: `/wpseopilot/v2/redirects/${id}`,
                method: 'DELETE',
            });
            setRedirects(redirects.filter(r => r.id !== id));
        } catch (error) {
            console.error('Failed to delete redirect:', error);
        }
    };

    // Apply slug suggestion
    const handleApplySuggestion = async (key) => {
        try {
            const response = await apiFetch({
                path: `/wpseopilot/v2/slug-suggestions/${key}/apply`,
                method: 'POST',
            });
            if (response.success) {
                setSuggestions(suggestions.filter(s => s.key !== key));
                fetchRedirects();
            }
        } catch (error) {
            console.error('Failed to apply suggestion:', error);
        }
    };

    // Dismiss slug suggestion
    const handleDismissSuggestion = async (key) => {
        try {
            await apiFetch({
                path: `/wpseopilot/v2/slug-suggestions/${key}/dismiss`,
                method: 'POST',
            });
            setSuggestions(suggestions.filter(s => s.key !== key));
        } catch (error) {
            console.error('Failed to dismiss suggestion:', error);
        }
    };

    // Use suggestion to prefill form
    const handleUseSuggestion = (suggestion) => {
        setNewSource(suggestion.source);
        setNewTarget(suggestion.target);
        setNewStatusCode(301);
        // Scroll to form
        document.getElementById('redirect-source')?.focus();
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
                    <h1>Redirects</h1>
                    <p>Create and manage URL redirects to maintain SEO value when URLs change.</p>
                </div>
                <button type="button" className="button ghost">Import Redirects</button>
            </div>

            <section className="panel">
                {/* Slug Change Suggestions */}
                {!suggestionsLoading && suggestions.length > 0 && (
                    <div className="alert-card warning" style={{ marginBottom: '24px' }}>
                        <div className="alert-header">
                            <h3>Detected Slug Changes</h3>
                        </div>
                        <p className="muted">The following posts have changed their URL structure. Create redirects to prevent 404 errors.</p>
                        <table className="data-table suggestions-table">
                            <thead>
                                <tr>
                                    <th>Old Path</th>
                                    <th>New Target</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {suggestions.map(suggestion => (
                                    <tr key={suggestion.key}>
                                        <td><code>{suggestion.source}</code></td>
                                        <td>
                                            <a href={suggestion.target} target="_blank" rel="noopener noreferrer">
                                                {suggestion.target}
                                            </a>
                                        </td>
                                        <td className="action-buttons">
                                            <button
                                                type="button"
                                                className="button primary small"
                                                onClick={() => handleApplySuggestion(suggestion.key)}
                                            >
                                                Apply
                                            </button>
                                            <button
                                                type="button"
                                                className="button ghost small"
                                                onClick={() => handleUseSuggestion(suggestion)}
                                            >
                                                Use
                                            </button>
                                            <button
                                                type="button"
                                                className="link-button danger"
                                                onClick={() => handleDismissSuggestion(suggestion.key)}
                                            >
                                                Dismiss
                                            </button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Create Redirect Form */}
                <div className="table-toolbar">
                    <div>
                        <h3>Active Redirects</h3>
                        <p className="muted">{redirects.length} redirect{redirects.length !== 1 ? 's' : ''} configured.</p>
                    </div>
                </div>

                <form onSubmit={handleCreateRedirect} className="redirect-form">
                    <div className="form-row">
                        <div className="form-field">
                            <label htmlFor="redirect-source">Source Path</label>
                            <input
                                type="text"
                                id="redirect-source"
                                placeholder="/old-url"
                                value={newSource}
                                onChange={(e) => setNewSource(e.target.value)}
                                required
                            />
                        </div>
                        <div className="form-field">
                            <label htmlFor="redirect-target">Target URL</label>
                            <input
                                type="url"
                                id="redirect-target"
                                placeholder="https://example.com/new-url"
                                value={newTarget}
                                onChange={(e) => setNewTarget(e.target.value)}
                                required
                            />
                        </div>
                        <div className="form-field narrow">
                            <label htmlFor="redirect-status">Status</label>
                            <select
                                id="redirect-status"
                                value={newStatusCode}
                                onChange={(e) => setNewStatusCode(parseInt(e.target.value, 10))}
                            >
                                {STATUS_CODES.map(code => (
                                    <option key={code.value} value={code.value}>{code.label}</option>
                                ))}
                            </select>
                        </div>
                        <div className="form-field button-field">
                            <button type="submit" className="button primary" disabled={createLoading}>
                                {createLoading ? 'Adding...' : 'Add Redirect'}
                            </button>
                        </div>
                    </div>
                    {createError && <p className="form-error">{createError}</p>}
                </form>

                {/* Redirects Table */}
                {redirectsLoading ? (
                    <div className="loading-state">Loading redirects...</div>
                ) : redirects.length === 0 ? (
                    <div className="empty-state">
                        <div className="empty-state__icon">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.5" width="48" height="48">
                                <path d="M9 18l6-6-6-6"/>
                                <path d="M15 6l-6 6 6 6" opacity="0.5"/>
                            </svg>
                        </div>
                        <h3>No redirects configured</h3>
                        <p>Add your first redirect using the form above.</p>
                    </div>
                ) : (
                    <table className="data-table">
                        <thead>
                            <tr>
                                <th>Source</th>
                                <th>Target</th>
                                <th>Status</th>
                                <th>Hits</th>
                                <th>Last Hit</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {redirects.map(redirect => (
                                <tr key={redirect.id}>
                                    <td><code>{redirect.source}</code></td>
                                    <td>
                                        <a href={redirect.target} target="_blank" rel="noopener noreferrer">
                                            {redirect.target}
                                        </a>
                                    </td>
                                    <td>
                                        <span className={`pill ${redirect.status_code === 301 ? 'success' : 'warning'}`}>
                                            {redirect.status_code}
                                        </span>
                                    </td>
                                    <td>{redirect.hits}</td>
                                    <td>{formatDate(redirect.last_hit)}</td>
                                    <td>
                                        <button
                                            type="button"
                                            className="link-button danger"
                                            onClick={() => handleDeleteRedirect(redirect.id)}
                                        >
                                            Delete
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                )}
            </section>
        </div>
    );
};

export default Redirects;
