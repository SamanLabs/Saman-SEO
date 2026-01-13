import { useState, useEffect, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const STATUS_CODES = [
    { value: 301, label: '301 Permanent' },
    { value: 302, label: '302 Temporary' },
    { value: 307, label: '307' },
    { value: 410, label: '410 Gone' },
];

// Empty form state
const emptyForm = {
    source: '',
    target: '',
    status_code: 301,
    is_regex: false,
    group_name: '',
    start_date: '',
    end_date: '',
    notes: '',
};

const Redirects = () => {
    // Redirects state
    const [redirects, setRedirects] = useState([]);
    const [redirectsLoading, setRedirectsLoading] = useState(true);
    const [pagination, setPagination] = useState({ page: 1, per_page: 50, total: 0, total_pages: 1 });

    // Form state
    const [formData, setFormData] = useState({ ...emptyForm });
    const [editingId, setEditingId] = useState(null);
    const [showModal, setShowModal] = useState(false);
    const [formLoading, setFormLoading] = useState(false);
    const [formError, setFormError] = useState('');
    const [chainWarnings, setChainWarnings] = useState([]);
    const [showAdvanced, setShowAdvanced] = useState(false);

    // Filters state
    const [search, setSearch] = useState('');
    const [filterGroup, setFilterGroup] = useState('');
    const [filterStatus, setFilterStatus] = useState('');
    const [groups, setGroups] = useState([]);

    // Bulk selection
    const [selectedIds, setSelectedIds] = useState([]);
    const [bulkLoading, setBulkLoading] = useState(false);

    // Import/Export
    const [showImport, setShowImport] = useState(false);
    const [importData, setImportData] = useState('');
    const [importFormat, setImportFormat] = useState('json');
    const [importOverwrite, setImportOverwrite] = useState(false);
    const [importLoading, setImportLoading] = useState(false);
    const [importResult, setImportResult] = useState(null);

    // Slug suggestions state
    const [suggestions, setSuggestions] = useState([]);
    const [suggestionsLoading, setSuggestionsLoading] = useState(true);

    const fileInputRef = useRef(null);

    // Fetch redirects with filters
    const fetchRedirects = useCallback(async (page = 1) => {
        setRedirectsLoading(true);
        try {
            const params = new URLSearchParams();
            params.append('page', page);
            params.append('per_page', pagination.per_page);
            if (search) params.append('search', search);
            if (filterGroup) params.append('group', filterGroup);
            if (filterStatus) params.append('status_code', filterStatus);

            const response = await apiFetch({ path: `/wpseopilot/v2/redirects?${params}` });
            if (response.success) {
                setRedirects(response.data.items || []);
                setPagination(prev => ({
                    ...prev,
                    page: response.data.page,
                    total: response.data.total,
                    total_pages: response.data.total_pages,
                }));
            }
        } catch (error) {
            console.error('Failed to fetch redirects:', error);
        } finally {
            setRedirectsLoading(false);
        }
    }, [search, filterGroup, filterStatus, pagination.per_page]);

    // Fetch groups
    const fetchGroups = useCallback(async () => {
        try {
            const response = await apiFetch({ path: '/wpseopilot/v2/redirects/groups' });
            if (response.success) {
                setGroups(response.data || []);
            }
        } catch (error) {
            console.error('Failed to fetch groups:', error);
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
        fetchGroups();
        fetchSuggestions();

        // Check if there's a redirect source from 404 Log page
        const storedSource = sessionStorage.getItem('wpseopilot_redirect_source');
        if (storedSource) {
            setFormData(prev => ({ ...prev, source: storedSource }));
            setShowModal(true);
            sessionStorage.removeItem('wpseopilot_redirect_source');
        }
    }, []);

    // Refetch when filters change
    useEffect(() => {
        const timer = setTimeout(() => {
            fetchRedirects(1);
        }, 300);
        return () => clearTimeout(timer);
    }, [search, filterGroup, filterStatus]);

    // Validate chain/loop
    const validateChain = async (source, target) => {
        if (!source || !target) return;
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/redirects/validate-chain',
                method: 'POST',
                data: { source, target, exclude_id: editingId || 0 },
            });
            if (response.success) {
                setChainWarnings(response.data.warnings || []);
            }
        } catch (error) {
            console.error('Chain validation failed:', error);
        }
    };

    // Debounced chain validation
    useEffect(() => {
        const timer = setTimeout(() => {
            validateChain(formData.source, formData.target);
        }, 500);
        return () => clearTimeout(timer);
    }, [formData.source, formData.target]);

    // Update form field
    const updateForm = (field, value) => {
        setFormData(prev => ({ ...prev, [field]: value }));
        setFormError('');
    };

    // Open modal for creating
    const openCreateModal = () => {
        setFormData({ ...emptyForm });
        setEditingId(null);
        setShowModal(true);
        setChainWarnings([]);
        setShowAdvanced(false);
        setFormError('');
    };

    // Open modal for editing
    const openEditModal = (redirect) => {
        setFormData({
            source: redirect.source || '',
            target: redirect.target || '',
            status_code: redirect.status_code || 301,
            is_regex: redirect.is_regex || false,
            group_name: redirect.group_name || '',
            start_date: redirect.start_date ? redirect.start_date.slice(0, 16) : '',
            end_date: redirect.end_date ? redirect.end_date.slice(0, 16) : '',
            notes: redirect.notes || '',
        });
        setEditingId(redirect.id);
        setShowModal(true);
        setChainWarnings([]);
        setShowAdvanced(!!redirect.start_date || !!redirect.end_date || !!redirect.notes);
        setFormError('');
    };

    // Close modal
    const closeModal = () => {
        setShowModal(false);
        setEditingId(null);
        setFormData({ ...emptyForm });
        setChainWarnings([]);
        setFormError('');
    };

    // Save redirect (create or update)
    const handleSaveRedirect = async (e) => {
        e.preventDefault();
        setFormLoading(true);
        setFormError('');

        try {
            const data = { ...formData };
            // Convert empty strings to null for dates
            if (!data.start_date) data.start_date = null;
            if (!data.end_date) data.end_date = null;

            let response;
            if (editingId) {
                response = await apiFetch({
                    path: `/wpseopilot/v2/redirects/${editingId}`,
                    method: 'PUT',
                    data,
                });
            } else {
                response = await apiFetch({
                    path: '/wpseopilot/v2/redirects',
                    method: 'POST',
                    data,
                });
            }

            if (response.success) {
                closeModal();
                fetchRedirects(pagination.page);
                fetchGroups();
                fetchSuggestions();
            } else {
                setFormError(response.message || 'Failed to save redirect');
            }
        } catch (error) {
            setFormError(error.message || 'Failed to save redirect');
        } finally {
            setFormLoading(false);
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
            fetchRedirects(pagination.page);
            fetchGroups();
        } catch (error) {
            console.error('Failed to delete redirect:', error);
        }
    };

    // Bulk delete
    const handleBulkDelete = async () => {
        if (selectedIds.length === 0) return;
        if (!window.confirm(`Delete ${selectedIds.length} selected redirect(s)?`)) return;

        setBulkLoading(true);
        try {
            await apiFetch({
                path: '/wpseopilot/v2/redirects/bulk-delete',
                method: 'POST',
                data: { ids: selectedIds },
            });
            setSelectedIds([]);
            fetchRedirects(pagination.page);
            fetchGroups();
        } catch (error) {
            console.error('Failed to bulk delete:', error);
        } finally {
            setBulkLoading(false);
        }
    };

    // Toggle selection
    const toggleSelect = (id) => {
        setSelectedIds(prev =>
            prev.includes(id) ? prev.filter(i => i !== id) : [...prev, id]
        );
    };

    // Toggle all selection
    const toggleSelectAll = () => {
        if (selectedIds.length === redirects.length) {
            setSelectedIds([]);
        } else {
            setSelectedIds(redirects.map(r => r.id));
        }
    };

    // Export redirects
    const handleExport = async (format) => {
        try {
            const response = await apiFetch({ path: `/wpseopilot/v2/redirects/export?format=${format}` });
            if (response.success) {
                const blob = new Blob([response.data.content], { type: format === 'json' ? 'application/json' : 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                a.click();
                URL.revokeObjectURL(url);
            }
        } catch (error) {
            console.error('Export failed:', error);
        }
    };

    // Import redirects
    const handleImport = async () => {
        if (!importData.trim()) return;

        setImportLoading(true);
        setImportResult(null);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/redirects/import',
                method: 'POST',
                data: {
                    format: importFormat,
                    data: importData,
                    overwrite: importOverwrite,
                },
            });

            if (response.success) {
                setImportResult(response.data);
                fetchRedirects(1);
                fetchGroups();
            } else {
                setImportResult({ error: response.message });
            }
        } catch (error) {
            setImportResult({ error: error.message });
        } finally {
            setImportLoading(false);
        }
    };

    // Handle file upload
    const handleFileUpload = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;

        const reader = new FileReader();
        reader.onload = (event) => {
            setImportData(event.target.result);
            setImportFormat(file.name.endsWith('.csv') ? 'csv' : 'json');
        };
        reader.readAsText(file);
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
        setFormData(prev => ({
            ...prev,
            source: suggestion.source,
            target: suggestion.target,
            status_code: 301,
        }));
        setShowModal(true);
    };

    // Format date
    const formatDate = (dateStr) => {
        if (!dateStr || dateStr === '0000-00-00 00:00:00') return '-';
        const date = new Date(dateStr);
        return date.toLocaleDateString() + ', ' + date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    };

    // Format short date for display
    const formatShortDate = (dateStr) => {
        if (!dateStr) return null;
        const date = new Date(dateStr);
        return date.toLocaleDateString();
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Redirects</h1>
                    <p>Create and manage URL redirects to maintain SEO value when URLs change.</p>
                </div>
                <div className="page-header-actions">
                    <button type="button" className="button ghost" onClick={() => setShowImport(true)}>Import</button>
                    <div className="dropdown">
                        <button type="button" className="button ghost">Export</button>
                        <div className="dropdown-menu">
                            <button onClick={() => handleExport('json')}>Export as JSON</button>
                            <button onClick={() => handleExport('csv')}>Export as CSV</button>
                        </div>
                    </div>
                    <button type="button" className="button primary" onClick={openCreateModal}>Add Redirect</button>
                </div>
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
                                            <button type="button" className="button primary small" onClick={() => handleApplySuggestion(suggestion.key)}>Apply</button>
                                            <button type="button" className="button ghost small" onClick={() => handleUseSuggestion(suggestion)}>Edit</button>
                                            <button type="button" className="link-button danger" onClick={() => handleDismissSuggestion(suggestion.key)}>Dismiss</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                )}

                {/* Filters */}
                <div className="table-toolbar">
                    <div className="table-toolbar-filters">
                        <input
                            type="search"
                            placeholder="Search redirects..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="search-input"
                        />
                        <select value={filterGroup} onChange={(e) => setFilterGroup(e.target.value)}>
                            <option value="">All Groups</option>
                            {groups.map(group => (
                                <option key={group} value={group}>{group}</option>
                            ))}
                        </select>
                        <select value={filterStatus} onChange={(e) => setFilterStatus(e.target.value)}>
                            <option value="">All Status</option>
                            {STATUS_CODES.map(code => (
                                <option key={code.value} value={code.value}>{code.label}</option>
                            ))}
                        </select>
                    </div>
                    <div className="table-toolbar-info">
                        <span className="muted">{pagination.total} redirect{pagination.total !== 1 ? 's' : ''}</span>
                    </div>
                </div>

                {/* Bulk actions */}
                {selectedIds.length > 0 && (
                    <div className="bulk-actions">
                        <span>{selectedIds.length} selected</span>
                        <button type="button" className="button danger small" onClick={handleBulkDelete} disabled={bulkLoading}>
                            {bulkLoading ? 'Deleting...' : 'Delete Selected'}
                        </button>
                        <button type="button" className="link-button" onClick={() => setSelectedIds([])}>Clear Selection</button>
                    </div>
                )}

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
                        <h3>No redirects found</h3>
                        <p>{search || filterGroup || filterStatus ? 'Try adjusting your filters.' : 'Add your first redirect to get started.'}</p>
                        {!search && !filterGroup && !filterStatus && (
                            <button type="button" className="button primary" onClick={openCreateModal}>Add Redirect</button>
                        )}
                    </div>
                ) : (
                    <>
                        <table className="data-table">
                            <thead>
                                <tr>
                                    <th className="checkbox-col">
                                        <input
                                            type="checkbox"
                                            checked={selectedIds.length === redirects.length && redirects.length > 0}
                                            onChange={toggleSelectAll}
                                        />
                                    </th>
                                    <th>Source</th>
                                    <th>Target</th>
                                    <th>Status</th>
                                    <th>Hits</th>
                                    <th>Last Hit</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                {redirects.map(redirect => (
                                    <tr key={redirect.id} className={!redirect.is_active ? 'inactive-row' : ''}>
                                        <td className="checkbox-col">
                                            <input
                                                type="checkbox"
                                                checked={selectedIds.includes(redirect.id)}
                                                onChange={() => toggleSelect(redirect.id)}
                                            />
                                        </td>
                                        <td>
                                            <code>{redirect.source}</code>
                                            {redirect.is_regex && <span className="pill info small" style={{ marginLeft: '6px' }}>Regex</span>}
                                            {redirect.group_name && <span className="pill muted small" style={{ marginLeft: '6px' }}>{redirect.group_name}</span>}
                                        </td>
                                        <td>
                                            <a href={redirect.target} target="_blank" rel="noopener noreferrer" className="truncate-link">
                                                {redirect.target}
                                            </a>
                                        </td>
                                        <td>
                                            <span className={`pill ${redirect.status_code === 301 ? 'success' : redirect.status_code === 410 ? 'danger' : 'warning'}`}>
                                                {redirect.status_code}
                                            </span>
                                        </td>
                                        <td>{redirect.hits}</td>
                                        <td>
                                            {formatDate(redirect.last_hit)}
                                            {!redirect.is_active && (
                                                <div className="text-muted text-small">
                                                    {redirect.start_date && new Date(redirect.start_date) > new Date()
                                                        ? `Starts: ${formatShortDate(redirect.start_date)}`
                                                        : `Ended: ${formatShortDate(redirect.end_date)}`}
                                                </div>
                                            )}
                                        </td>
                                        <td className="action-buttons">
                                            <button type="button" className="link-button" onClick={() => openEditModal(redirect)}>Edit</button>
                                            <button type="button" className="link-button danger" onClick={() => handleDeleteRedirect(redirect.id)}>Delete</button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        {/* Pagination */}
                        {pagination.total_pages > 1 && (
                            <div className="pagination">
                                <button
                                    type="button"
                                    className="button ghost small"
                                    disabled={pagination.page <= 1}
                                    onClick={() => fetchRedirects(pagination.page - 1)}
                                >
                                    Previous
                                </button>
                                <span className="pagination-info">
                                    Page {pagination.page} of {pagination.total_pages}
                                </span>
                                <button
                                    type="button"
                                    className="button ghost small"
                                    disabled={pagination.page >= pagination.total_pages}
                                    onClick={() => fetchRedirects(pagination.page + 1)}
                                >
                                    Next
                                </button>
                            </div>
                        )}
                    </>
                )}
            </section>

            {/* Create/Edit Modal */}
            {showModal && (
                <div className="modal-overlay" onClick={closeModal}>
                    <div className="modal" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>{editingId ? 'Edit Redirect' : 'Add Redirect'}</h2>
                            <button type="button" className="modal-close" onClick={closeModal}>&times;</button>
                        </div>
                        <form onSubmit={handleSaveRedirect}>
                            <div className="modal-body">
                                {/* Chain/Loop Warnings */}
                                {chainWarnings.length > 0 && (
                                    <div className="chain-warnings">
                                        {chainWarnings.map((warning, i) => (
                                            <div key={i} className={`alert-inline ${warning.type === 'loop' ? 'danger' : 'warning'}`}>
                                                {warning.message}
                                            </div>
                                        ))}
                                    </div>
                                )}

                                <div className="form-group">
                                    <label htmlFor="modal-source">Source Path</label>
                                    <input
                                        type="text"
                                        id="modal-source"
                                        placeholder={formData.is_regex ? '^/old-path/(.*)$' : '/old-url'}
                                        value={formData.source}
                                        onChange={(e) => updateForm('source', e.target.value)}
                                        required
                                    />
                                    <label className="checkbox-label">
                                        <input
                                            type="checkbox"
                                            checked={formData.is_regex}
                                            onChange={(e) => updateForm('is_regex', e.target.checked)}
                                        />
                                        Use regex pattern
                                    </label>
                                    {formData.is_regex && (
                                        <p className="field-hint">Use capture groups like (.*) and reference them in target as $1, $2, etc.</p>
                                    )}
                                </div>

                                <div className="form-group">
                                    <label htmlFor="modal-target">Target URL</label>
                                    <input
                                        type="text"
                                        id="modal-target"
                                        placeholder={formData.is_regex ? 'https://example.com/new-path/$1' : 'https://example.com/new-url'}
                                        value={formData.target}
                                        onChange={(e) => updateForm('target', e.target.value)}
                                        required
                                    />
                                </div>

                                <div className="form-row">
                                    <div className="form-group">
                                        <label htmlFor="modal-status">Status Code</label>
                                        <select
                                            id="modal-status"
                                            value={formData.status_code}
                                            onChange={(e) => updateForm('status_code', parseInt(e.target.value, 10))}
                                        >
                                            {STATUS_CODES.map(code => (
                                                <option key={code.value} value={code.value}>{code.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="form-group">
                                        <label htmlFor="modal-group">Group (optional)</label>
                                        <input
                                            type="text"
                                            id="modal-group"
                                            placeholder="e.g., migration, campaign"
                                            value={formData.group_name}
                                            onChange={(e) => updateForm('group_name', e.target.value)}
                                            list="group-suggestions"
                                        />
                                        <datalist id="group-suggestions">
                                            {groups.map(group => (
                                                <option key={group} value={group} />
                                            ))}
                                        </datalist>
                                    </div>
                                </div>

                                <button
                                    type="button"
                                    className="link-button advanced-toggle"
                                    onClick={() => setShowAdvanced(!showAdvanced)}
                                >
                                    {showAdvanced ? 'Hide Advanced Options' : 'Show Advanced Options'}
                                </button>

                                {showAdvanced && (
                                    <div className="advanced-options">
                                        <div className="form-row">
                                            <div className="form-group">
                                                <label htmlFor="modal-start-date">Start Date (optional)</label>
                                                <input
                                                    type="datetime-local"
                                                    id="modal-start-date"
                                                    value={formData.start_date}
                                                    onChange={(e) => updateForm('start_date', e.target.value)}
                                                />
                                                <p className="field-hint">Redirect activates at this time</p>
                                            </div>
                                            <div className="form-group">
                                                <label htmlFor="modal-end-date">End Date (optional)</label>
                                                <input
                                                    type="datetime-local"
                                                    id="modal-end-date"
                                                    value={formData.end_date}
                                                    onChange={(e) => updateForm('end_date', e.target.value)}
                                                />
                                                <p className="field-hint">Redirect expires after this time</p>
                                            </div>
                                        </div>

                                        <div className="form-group">
                                            <label htmlFor="modal-notes">Notes (optional)</label>
                                            <textarea
                                                id="modal-notes"
                                                placeholder="Internal notes about this redirect..."
                                                value={formData.notes}
                                                onChange={(e) => updateForm('notes', e.target.value)}
                                                rows="2"
                                            />
                                        </div>
                                    </div>
                                )}

                                {formError && <p className="form-error">{formError}</p>}
                            </div>
                            <div className="modal-footer">
                                <button type="button" className="button ghost" onClick={closeModal}>Cancel</button>
                                <button type="submit" className="button primary" disabled={formLoading}>
                                    {formLoading ? 'Saving...' : (editingId ? 'Update Redirect' : 'Add Redirect')}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            )}

            {/* Import Modal */}
            {showImport && (
                <div className="modal-overlay" onClick={() => setShowImport(false)}>
                    <div className="modal modal-wide" onClick={e => e.stopPropagation()}>
                        <div className="modal-header">
                            <h2>Import Redirects</h2>
                            <button type="button" className="modal-close" onClick={() => setShowImport(false)}>&times;</button>
                        </div>
                        <div className="modal-body">
                            <div className="form-group">
                                <label>Upload File</label>
                                <input
                                    type="file"
                                    ref={fileInputRef}
                                    accept=".json,.csv"
                                    onChange={handleFileUpload}
                                />
                            </div>

                            <div className="form-group">
                                <label>Or paste data directly</label>
                                <textarea
                                    value={importData}
                                    onChange={(e) => setImportData(e.target.value)}
                                    placeholder="Paste JSON or CSV data here..."
                                    rows="8"
                                    className="code-textarea"
                                />
                            </div>

                            <div className="form-row">
                                <div className="form-group">
                                    <label>Format</label>
                                    <select value={importFormat} onChange={(e) => setImportFormat(e.target.value)}>
                                        <option value="json">JSON</option>
                                        <option value="csv">CSV</option>
                                    </select>
                                </div>
                                <div className="form-group">
                                    <label className="checkbox-label" style={{ marginTop: '28px' }}>
                                        <input
                                            type="checkbox"
                                            checked={importOverwrite}
                                            onChange={(e) => setImportOverwrite(e.target.checked)}
                                        />
                                        Overwrite existing redirects
                                    </label>
                                </div>
                            </div>

                            {importResult && (
                                <div className={`import-result ${importResult.error ? 'error' : 'success'}`}>
                                    {importResult.error ? (
                                        <p>Error: {importResult.error}</p>
                                    ) : (
                                        <>
                                            <p>Imported: {importResult.imported} | Skipped: {importResult.skipped}</p>
                                            {importResult.errors?.length > 0 && (
                                                <ul className="import-errors">
                                                    {importResult.errors.slice(0, 5).map((err, i) => (
                                                        <li key={i}>{err}</li>
                                                    ))}
                                                    {importResult.errors.length > 5 && <li>...and {importResult.errors.length - 5} more</li>}
                                                </ul>
                                            )}
                                        </>
                                    )}
                                </div>
                            )}
                        </div>
                        <div className="modal-footer">
                            <button type="button" className="button ghost" onClick={() => setShowImport(false)}>Close</button>
                            <button
                                type="button"
                                className="button primary"
                                onClick={handleImport}
                                disabled={importLoading || !importData.trim()}
                            >
                                {importLoading ? 'Importing...' : 'Import'}
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
};

export default Redirects;
