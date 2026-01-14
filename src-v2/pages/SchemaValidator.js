/**
 * Schema Validator Page
 *
 * Test and validate structured data on any URL.
 */

import { useState, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const SchemaValidator = ({ onNavigate }) => {
    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState(null);
    const [error, setError] = useState(null);
    const [expandedSchema, setExpandedSchema] = useState(null);

    // Validate URL
    const handleValidate = useCallback(async () => {
        if (!url.trim()) {
            setError('Please enter a URL to validate');
            return;
        }

        setLoading(true);
        setError(null);
        setResults(null);

        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/schema-validator/validate',
                method: 'POST',
                data: { url: url.trim() },
            });

            if (response.success) {
                setResults(response.data);
            } else {
                setError(response.message || 'Failed to validate schema');
            }
        } catch (err) {
            setError(err.message || 'Failed to fetch URL');
        } finally {
            setLoading(false);
        }
    }, [url]);

    // Validate current site URL
    const handleValidateSite = useCallback(async () => {
        const siteUrl = window.wpseopilotV2Settings?.adminUrl?.replace('/wp-admin/', '') || '';
        if (siteUrl) {
            setUrl(siteUrl);
            // Trigger validation after state update
            setTimeout(() => {
                document.getElementById('validate-btn')?.click();
            }, 100);
        }
    }, []);

    // Get schema type icon
    const getSchemaIcon = (type) => {
        const icons = {
            Article: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <path d="M14 2v6h6M16 13H8M16 17H8M10 9H8"/>
                </svg>
            ),
            Product: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M6 2L3 6v14a2 2 0 002 2h14a2 2 0 002-2V6l-3-4zM3 6h18M16 10a4 4 0 01-8 0"/>
                </svg>
            ),
            Organization: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M3 21h18M9 8h1M9 12h1M9 16h1M14 8h1M14 12h1M14 16h1M5 21V5a2 2 0 012-2h10a2 2 0 012 2v16"/>
                </svg>
            ),
            LocalBusiness: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            ),
            WebSite: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M2 12h20M12 2a15.3 15.3 0 014 10 15.3 15.3 0 01-4 10 15.3 15.3 0 01-4-10 15.3 15.3 0 014-10z"/>
                </svg>
            ),
            BreadcrumbList: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            ),
            FAQPage: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9 9h.01M15 9h.01M9 15h6"/>
                </svg>
            ),
            HowTo: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/>
                    <rect x="9" y="3" width="6" height="4" rx="1"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            ),
            VideoObject: (
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <rect x="2" y="4" width="20" height="16" rx="2"/>
                    <path d="M10 9l5 3-5 3V9z"/>
                </svg>
            ),
        };
        return icons[type] || icons.Article;
    };

    // Get validation status badge
    const StatusBadge = ({ status, count }) => {
        const config = {
            valid: { label: 'Valid', className: 'badge--success' },
            warnings: { label: `${count} Warning${count !== 1 ? 's' : ''}`, className: 'badge--warning' },
            errors: { label: `${count} Error${count !== 1 ? 's' : ''}`, className: 'badge--error' },
        };
        const c = config[status] || config.valid;
        return <span className={`badge ${c.className}`}>{c.label}</span>;
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Schema Validator</h1>
                    <p>Test and validate structured data (JSON-LD) on any URL.</p>
                </div>
            </div>

            {/* URL Input */}
            <div className="card">
                <div className="schema-validator-input">
                    <div className="input-group">
                        <input
                            type="url"
                            className="form-input form-input--large"
                            placeholder="Enter URL to validate (e.g., https://example.com/page)"
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleValidate()}
                        />
                        <button
                            id="validate-btn"
                            type="button"
                            className="btn btn--primary btn--large"
                            onClick={handleValidate}
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <span className="btn-spinner" />
                                    Validating...
                                </>
                            ) : (
                                <>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18">
                                        <circle cx="11" cy="11" r="8"/>
                                        <path d="M21 21l-4.35-4.35"/>
                                    </svg>
                                    Validate
                                </>
                            )}
                        </button>
                    </div>
                    <div className="quick-actions">
                        <button
                            type="button"
                            className="btn btn--small btn--secondary"
                            onClick={handleValidateSite}
                        >
                            Test Homepage
                        </button>
                        <span className="quick-actions__hint">
                            Enter any URL to check its structured data markup
                        </span>
                    </div>
                </div>
            </div>

            {/* Error */}
            {error && (
                <div className="notice notice--error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4m0 4h.01"/>
                    </svg>
                    <span>{error}</span>
                </div>
            )}

            {/* Results */}
            {results && (
                <div className="schema-validator-results">
                    {/* Summary */}
                    <div className="card">
                        <div className="schema-summary">
                            <div className="schema-summary__header">
                                <h3>Validation Results</h3>
                                <a href={results.url} target="_blank" rel="noopener noreferrer" className="schema-summary__url">
                                    {results.url}
                                </a>
                            </div>
                            <div className="schema-summary__stats">
                                <div className="schema-stat">
                                    <div className="schema-stat__value">{results.schemas?.length || 0}</div>
                                    <div className="schema-stat__label">Schema{results.schemas?.length !== 1 ? 's' : ''} Found</div>
                                </div>
                                <div className="schema-stat schema-stat--success">
                                    <div className="schema-stat__value">{results.valid_count || 0}</div>
                                    <div className="schema-stat__label">Valid</div>
                                </div>
                                <div className="schema-stat schema-stat--warning">
                                    <div className="schema-stat__value">{results.warning_count || 0}</div>
                                    <div className="schema-stat__label">Warnings</div>
                                </div>
                                <div className="schema-stat schema-stat--error">
                                    <div className="schema-stat__value">{results.error_count || 0}</div>
                                    <div className="schema-stat__label">Errors</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* No Schemas */}
                    {(!results.schemas || results.schemas.length === 0) && (
                        <div className="card">
                            <div className="empty-state">
                                <div className="empty-state__icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16"/>
                                    </svg>
                                </div>
                                <h3>No Structured Data Found</h3>
                                <p>This page doesn't contain any JSON-LD structured data markup.</p>
                            </div>
                        </div>
                    )}

                    {/* Schema List */}
                    {results.schemas && results.schemas.length > 0 && (
                        <div className="schema-list">
                            {results.schemas.map((schema, index) => (
                                <div key={index} className="card schema-card">
                                    <div
                                        className="schema-card__header"
                                        onClick={() => setExpandedSchema(expandedSchema === index ? null : index)}
                                    >
                                        <div className="schema-card__type">
                                            <div className="schema-card__icon">
                                                {getSchemaIcon(schema.type)}
                                            </div>
                                            <div className="schema-card__info">
                                                <h4>{schema.type}</h4>
                                                {schema.name && <span className="schema-card__name">{schema.name}</span>}
                                            </div>
                                        </div>
                                        <div className="schema-card__status">
                                            {schema.errors?.length > 0 ? (
                                                <StatusBadge status="errors" count={schema.errors.length} />
                                            ) : schema.warnings?.length > 0 ? (
                                                <StatusBadge status="warnings" count={schema.warnings.length} />
                                            ) : (
                                                <StatusBadge status="valid" />
                                            )}
                                            <svg
                                                className={`schema-card__toggle ${expandedSchema === index ? 'schema-card__toggle--open' : ''}`}
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                strokeWidth="2"
                                            >
                                                <path d="M6 9l6 6 6-6"/>
                                            </svg>
                                        </div>
                                    </div>

                                    {expandedSchema === index && (
                                        <div className="schema-card__body">
                                            {/* Errors */}
                                            {schema.errors?.length > 0 && (
                                                <div className="schema-issues schema-issues--error">
                                                    <h5>Errors</h5>
                                                    <ul>
                                                        {schema.errors.map((err, i) => (
                                                            <li key={i}>{err}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}

                                            {/* Warnings */}
                                            {schema.warnings?.length > 0 && (
                                                <div className="schema-issues schema-issues--warning">
                                                    <h5>Warnings</h5>
                                                    <ul>
                                                        {schema.warnings.map((warn, i) => (
                                                            <li key={i}>{warn}</li>
                                                        ))}
                                                    </ul>
                                                </div>
                                            )}

                                            {/* Properties */}
                                            <div className="schema-properties">
                                                <h5>Properties</h5>
                                                <div className="schema-properties__list">
                                                    {Object.entries(schema.properties || {}).map(([key, value]) => (
                                                        <div key={key} className="schema-property">
                                                            <span className="schema-property__key">{key}</span>
                                                            <span className="schema-property__value">
                                                                {typeof value === 'object'
                                                                    ? JSON.stringify(value, null, 2)
                                                                    : String(value).substring(0, 200)}
                                                            </span>
                                                        </div>
                                                    ))}
                                                </div>
                                            </div>

                                            {/* Raw JSON */}
                                            <details className="schema-raw">
                                                <summary>View Raw JSON-LD</summary>
                                                <pre>{JSON.stringify(schema.raw, null, 2)}</pre>
                                            </details>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    )}

                    {/* Tips */}
                    <div className="card schema-tips">
                        <h4>Testing Tips</h4>
                        <ul>
                            <li>Use <a href="https://search.google.com/test/rich-results" target="_blank" rel="noopener noreferrer">Google Rich Results Test</a> for official validation</li>
                            <li>Use <a href="https://validator.schema.org/" target="_blank" rel="noopener noreferrer">Schema.org Validator</a> for detailed schema checking</li>
                            <li>Common issues: missing required properties, incorrect data types, invalid URLs</li>
                        </ul>
                    </div>
                </div>
            )}
        </div>
    );
};

export default SchemaValidator;
