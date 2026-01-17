import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const IgnorePatternManager = ({ onClose, onPatternChange }) => {
    const [patterns, setPatterns] = useState([]);
    const [loading, setLoading] = useState(true);
    const [newPattern, setNewPattern] = useState('');
    const [isRegex, setIsRegex] = useState(false);
    const [reason, setReason] = useState('');
    const [adding, setAdding] = useState(false);
    const [deletingId, setDeletingId] = useState(null);
    const [error, setError] = useState('');

    // Fetch patterns on mount
    useEffect(() => {
        fetchPatterns();
    }, []);

    const fetchPatterns = async () => {
        setLoading(true);
        try {
            const response = await apiFetch({ path: '/saman-seo/v1/404-ignore-patterns' });
            if (response.success) {
                setPatterns(response.data);
            }
        } catch (err) {
            console.error('Failed to fetch patterns:', err);
        } finally {
            setLoading(false);
        }
    };

    const handleAddPattern = async (e) => {
        e.preventDefault();
        if (!newPattern.trim()) return;

        setAdding(true);
        setError('');

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/404-ignore-patterns',
                method: 'POST',
                data: {
                    pattern: newPattern.trim(),
                    is_regex: isRegex,
                    reason: reason.trim(),
                },
            });

            if (response.success) {
                setPatterns(prev => [...prev, response.data]);
                setNewPattern('');
                setIsRegex(false);
                setReason('');
                onPatternChange?.();
            } else {
                setError(response.message || 'Failed to add pattern');
            }
        } catch (err) {
            setError(err.message || 'Failed to add pattern');
        } finally {
            setAdding(false);
        }
    };

    const handleDeletePattern = async (id) => {
        setDeletingId(id);
        try {
            await apiFetch({
                path: `/saman-seo/v1/404-ignore-patterns/${id}`,
                method: 'DELETE',
            });
            setPatterns(prev => prev.filter(p => p.id !== id));
            onPatternChange?.();
        } catch (err) {
            console.error('Failed to delete pattern:', err);
        } finally {
            setDeletingId(null);
        }
    };

    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString();
    };

    return (
        <div className="modal-overlay" onClick={onClose}>
            <div className="modal ignore-pattern-modal" onClick={e => e.stopPropagation()}>
                <div className="modal-header">
                    <h2>Manage Ignore Patterns</h2>
                    <button type="button" className="modal-close" onClick={onClose}>
                        <svg viewBox="0 0 20 20" fill="currentColor" width="20" height="20">
                            <path fillRule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clipRule="evenodd" />
                        </svg>
                    </button>
                </div>

                <div className="modal-body">
                    <p className="modal-description">
                        Add URL patterns to automatically ignore matching 404 errors.
                        Use <code>*</code> as wildcard (e.g., <code>/uploads/*</code>) or enable regex for complex patterns.
                    </p>

                    {/* Add Pattern Form */}
                    <form onSubmit={handleAddPattern} className="pattern-form">
                        <div className="form-row">
                            <label className="form-field">
                                <span>Pattern</span>
                                <input
                                    type="text"
                                    value={newPattern}
                                    onChange={(e) => setNewPattern(e.target.value)}
                                    placeholder={isRegex ? '^/old-.*\\.html$' : '/uploads/*'}
                                    disabled={adding}
                                />
                            </label>
                        </div>
                        <div className="form-row form-row--split">
                            <label className="form-field">
                                <span>Reason (optional)</span>
                                <input
                                    type="text"
                                    value={reason}
                                    onChange={(e) => setReason(e.target.value)}
                                    placeholder="e.g., Legacy URLs"
                                    disabled={adding}
                                />
                            </label>
                            <label className="form-checkbox">
                                <input
                                    type="checkbox"
                                    checked={isRegex}
                                    onChange={(e) => setIsRegex(e.target.checked)}
                                    disabled={adding}
                                />
                                <span>Use Regex</span>
                            </label>
                        </div>
                        {error && <p className="form-error">{error}</p>}
                        <div className="form-actions">
                            <button type="submit" className="button primary" disabled={adding || !newPattern.trim()}>
                                {adding ? 'Adding...' : 'Add Pattern'}
                            </button>
                        </div>
                    </form>

                    {/* Patterns List */}
                    <div className="patterns-list">
                        <h3>Current Patterns</h3>
                        {loading ? (
                            <div className="loading-state">Loading patterns...</div>
                        ) : patterns.length === 0 ? (
                            <div className="empty-state small">
                                <p>No ignore patterns defined yet.</p>
                            </div>
                        ) : (
                            <table className="data-table compact">
                                <thead>
                                    <tr>
                                        <th>Pattern</th>
                                        <th>Type</th>
                                        <th>Reason</th>
                                        <th>Created</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {patterns.map(pattern => (
                                        <tr key={pattern.id}>
                                            <td><code>{pattern.pattern}</code></td>
                                            <td>
                                                <span className={`badge ${pattern.is_regex ? 'info' : 'muted'}`}>
                                                    {pattern.is_regex ? 'Regex' : 'Wildcard'}
                                                </span>
                                            </td>
                                            <td>{pattern.reason || '-'}</td>
                                            <td>{formatDate(pattern.created_at)}</td>
                                            <td>
                                                <button
                                                    type="button"
                                                    className="button ghost small danger"
                                                    onClick={() => handleDeletePattern(pattern.id)}
                                                    disabled={deletingId === pattern.id}
                                                >
                                                    {deletingId === pattern.id ? '...' : 'Delete'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}
                    </div>
                </div>

                <div className="modal-footer">
                    <button type="button" className="button" onClick={onClose}>
                        Close
                    </button>
                </div>
            </div>
        </div>
    );
};

export default IgnorePatternManager;
