/**
 * Mobile Friendly Test Page
 *
 * Check if pages are mobile-friendly and identify issues.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const MobileFriendly = ({ onNavigate }) => {
    const [url, setUrl] = useState('');
    const [loading, setLoading] = useState(false);
    const [results, setResults] = useState(null);
    const [error, setError] = useState(null);
    const [recentTests, setRecentTests] = useState([]);

    // Load recent tests
    useEffect(() => {
        const fetchRecent = async () => {
            try {
                const response = await apiFetch({ path: '/wpseopilot/v2/mobile-test/recent' });
                if (response.success) {
                    setRecentTests(response.data || []);
                }
            } catch (err) {
                // Ignore errors for recent tests
            }
        };
        fetchRecent();
    }, []);

    // Run mobile test
    const handleTest = useCallback(async () => {
        if (!url.trim()) {
            setError('Please enter a URL to test');
            return;
        }

        setLoading(true);
        setError(null);
        setResults(null);

        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/mobile-test/analyze',
                method: 'POST',
                data: { url: url.trim() },
            });

            if (response.success) {
                setResults(response.data);
                // Update recent tests
                const newTest = { url: response.data.url, score: response.data.score, date: new Date().toLocaleString() };
                setRecentTests(prev => [newTest, ...prev.slice(0, 4)]);
            } else {
                setError(response.message || 'Failed to analyze page');
            }
        } catch (err) {
            setError(err.message || 'Failed to analyze page');
        } finally {
            setLoading(false);
        }
    }, [url]);

    // Test homepage
    const handleTestHomepage = useCallback(() => {
        const siteUrl = window.wpseopilotV2Settings?.adminUrl?.replace('/wp-admin/', '') || '';
        if (siteUrl) {
            setUrl(siteUrl);
            setTimeout(() => {
                document.getElementById('test-btn')?.click();
            }, 100);
        }
    }, []);

    // Get score color
    const getScoreColor = (score) => {
        if (score >= 90) return '#00a32a';
        if (score >= 70) return '#f0b849';
        return '#d63638';
    };

    // Issue severity icon
    const SeverityIcon = ({ severity }) => {
        if (severity === 'critical') {
            return (
                <svg className="issue-icon issue-icon--critical" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="15" y1="9" x2="9" y2="15"/>
                    <line x1="9" y1="9" x2="15" y2="15"/>
                </svg>
            );
        }
        if (severity === 'warning') {
            return (
                <svg className="issue-icon issue-icon--warning" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            );
        }
        return (
            <svg className="issue-icon issue-icon--info" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                <circle cx="12" cy="12" r="10"/>
                <line x1="12" y1="16" x2="12" y2="12"/>
                <line x1="12" y1="8" x2="12.01" y2="8"/>
            </svg>
        );
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Mobile Friendly Test</h1>
                    <p>Check if your pages are optimized for mobile devices.</p>
                </div>
            </div>

            {/* URL Input */}
            <div className="card">
                <div className="mobile-test-input">
                    <div className="input-group">
                        <input
                            type="url"
                            className="form-input form-input--large"
                            placeholder="Enter URL to test (e.g., https://example.com)"
                            value={url}
                            onChange={(e) => setUrl(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleTest()}
                        />
                        <button
                            id="test-btn"
                            type="button"
                            className="btn btn--primary btn--large"
                            onClick={handleTest}
                            disabled={loading}
                        >
                            {loading ? (
                                <>
                                    <span className="btn-spinner" />
                                    Testing...
                                </>
                            ) : (
                                <>
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="18" height="18">
                                        <rect x="5" y="2" width="14" height="20" rx="2"/>
                                        <path d="M12 18h.01"/>
                                    </svg>
                                    Test Page
                                </>
                            )}
                        </button>
                    </div>
                    <div className="quick-actions">
                        <button
                            type="button"
                            className="btn btn--small btn--secondary"
                            onClick={handleTestHomepage}
                        >
                            Test Homepage
                        </button>
                        <span className="quick-actions__hint">
                            Analyzes viewport, touch targets, font sizes, and more
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
                <div className="mobile-test-results">
                    {/* Score Card */}
                    <div className="card mobile-score-card">
                        <div className="mobile-score">
                            <div
                                className="mobile-score__circle"
                                style={{ '--score-color': getScoreColor(results.score) }}
                            >
                                <svg viewBox="0 0 36 36" className="mobile-score__ring">
                                    <path
                                        className="mobile-score__bg"
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                    <path
                                        className="mobile-score__fg"
                                        strokeDasharray={`${results.score}, 100`}
                                        d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"
                                    />
                                </svg>
                                <span className="mobile-score__value">{results.score}</span>
                            </div>
                            <div className="mobile-score__info">
                                <h3 className={`mobile-score__status mobile-score__status--${results.status}`}>
                                    {results.status === 'pass' ? 'Mobile Friendly' : results.status === 'warning' ? 'Needs Improvement' : 'Not Mobile Friendly'}
                                </h3>
                                <p className="mobile-score__url">{results.url}</p>
                            </div>
                        </div>
                    </div>

                    {/* Checks Grid */}
                    <div className="mobile-checks">
                        <h3>Checks Performed</h3>
                        <div className="mobile-checks__grid">
                            {results.checks?.map((check, index) => (
                                <div key={index} className={`mobile-check mobile-check--${check.status}`}>
                                    <div className="mobile-check__icon">
                                        {check.status === 'pass' ? (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                                                <path d="M22 4L12 14.01l-3-3"/>
                                            </svg>
                                        ) : check.status === 'fail' ? (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <line x1="15" y1="9" x2="9" y2="15"/>
                                                <line x1="9" y1="9" x2="15" y2="15"/>
                                            </svg>
                                        ) : (
                                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                                                <line x1="12" y1="9" x2="12" y2="13"/>
                                                <line x1="12" y1="17" x2="12.01" y2="17"/>
                                            </svg>
                                        )}
                                    </div>
                                    <div className="mobile-check__content">
                                        <span className="mobile-check__name">{check.name}</span>
                                        <span className="mobile-check__detail">{check.detail}</span>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Issues */}
                    {results.issues && results.issues.length > 0 && (
                        <div className="card mobile-issues">
                            <h3>Issues Found ({results.issues.length})</h3>
                            <ul className="mobile-issues__list">
                                {results.issues.map((issue, index) => (
                                    <li key={index} className={`mobile-issue mobile-issue--${issue.severity}`}>
                                        <SeverityIcon severity={issue.severity} />
                                        <div className="mobile-issue__content">
                                            <strong>{issue.title}</strong>
                                            <p>{issue.description}</p>
                                            {issue.fix && (
                                                <span className="mobile-issue__fix">
                                                    <strong>Fix:</strong> {issue.fix}
                                                </span>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    {/* External Tools */}
                    <div className="card mobile-external">
                        <h4>Test with Official Tools</h4>
                        <div className="mobile-external__links">
                            <a
                                href={`https://search.google.com/test/mobile-friendly?url=${encodeURIComponent(results.url)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn--secondary"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                                </svg>
                                Google Mobile Test
                            </a>
                            <a
                                href={`https://pagespeed.web.dev/report?url=${encodeURIComponent(results.url)}`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn--secondary"
                            >
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" strokeWidth="2">
                                    <circle cx="12" cy="12" r="10"/>
                                    <path d="M12 6v6l4 2"/>
                                </svg>
                                PageSpeed Insights
                            </a>
                        </div>
                    </div>
                </div>
            )}

            {/* Recent Tests */}
            {!results && recentTests.length > 0 && (
                <div className="card">
                    <h3>Recent Tests</h3>
                    <ul className="recent-tests">
                        {recentTests.map((test, index) => (
                            <li key={index} className="recent-test">
                                <button
                                    type="button"
                                    className="recent-test__btn"
                                    onClick={() => setUrl(test.url)}
                                >
                                    <span className="recent-test__url">{test.url}</span>
                                    <span
                                        className="recent-test__score"
                                        style={{ color: getScoreColor(test.score) }}
                                    >
                                        {test.score}/100
                                    </span>
                                </button>
                            </li>
                        ))}
                    </ul>
                </div>
            )}
        </div>
    );
};

export default MobileFriendly;
