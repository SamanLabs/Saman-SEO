/**
 * robots.txt Editor Page
 *
 * Visual editor for managing robots.txt with presets and validation.
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Common robots.txt presets
const presets = [
    {
        id: 'default',
        name: 'Default (WordPress)',
        description: 'Standard WordPress robots.txt with sitemap',
        content: `User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: {{sitemap_url}}`
    },
    {
        id: 'block-all',
        name: 'Block All Robots',
        description: 'Prevent all search engines from crawling',
        content: `User-agent: *
Disallow: /`
    },
    {
        id: 'allow-all',
        name: 'Allow All',
        description: 'Allow all search engines to crawl everything',
        content: `User-agent: *
Disallow:

Sitemap: {{sitemap_url}}`
    },
    {
        id: 'seo-optimized',
        name: 'SEO Optimized',
        description: 'Block unnecessary paths, allow important ones',
        content: `User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /wp-content/plugins/
Disallow: /wp-content/cache/
Disallow: /wp-json/
Disallow: /xmlrpc.php
Disallow: /?s=
Disallow: /search/
Disallow: /author/
Disallow: /trackback/
Disallow: /feed/
Disallow: */feed/
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

Sitemap: {{sitemap_url}}`
    },
    {
        id: 'ecommerce',
        name: 'E-Commerce',
        description: 'Optimized for WooCommerce and online stores',
        content: `User-agent: *
Disallow: /wp-admin/
Disallow: /wp-includes/
Disallow: /cart/
Disallow: /checkout/
Disallow: /my-account/
Disallow: /?add-to-cart=
Disallow: /*?orderby=
Disallow: /*?filter_
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

Sitemap: {{sitemap_url}}`
    }
];

// Common directives for quick insertion
const directives = [
    { label: 'Disallow path', value: 'Disallow: /' },
    { label: 'Allow path', value: 'Allow: /' },
    { label: 'User-agent (all)', value: 'User-agent: *' },
    { label: 'User-agent (Googlebot)', value: 'User-agent: Googlebot' },
    { label: 'User-agent (Bingbot)', value: 'User-agent: Bingbot' },
    { label: 'Crawl-delay', value: 'Crawl-delay: 10' },
    { label: 'Sitemap', value: 'Sitemap: ' },
];

const RobotsTxt = () => {
    const [content, setContent] = useState('');
    const [originalContent, setOriginalContent] = useState('');
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [testing, setTesting] = useState(false);
    const [testUrl, setTestUrl] = useState('/');
    const [testResult, setTestResult] = useState(null);
    const [validation, setValidation] = useState({ valid: true, errors: [], warnings: [] });
    const [siteUrl, setSiteUrl] = useState('');
    const [sitemapUrl, setSitemapUrl] = useState('');
    const [activePreset, setActivePreset] = useState(null);

    useEffect(() => {
        loadRobotsTxt();
    }, []);

    useEffect(() => {
        validateContent(content);
    }, [content]);

    const loadRobotsTxt = async () => {
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/robots-txt',
            });
            setContent(response.content || '');
            setOriginalContent(response.content || '');
            setSiteUrl(response.site_url || '');
            setSitemapUrl(response.sitemap_url || '');
        } catch (error) {
            console.error('Error loading robots.txt:', error);
        } finally {
            setLoading(false);
        }
    };

    const saveRobotsTxt = async () => {
        setSaving(true);
        try {
            await apiFetch({
                path: '/wpseopilot/v2/tools/robots-txt',
                method: 'POST',
                data: { content },
            });
            setOriginalContent(content);
        } catch (error) {
            console.error('Error saving robots.txt:', error);
        } finally {
            setSaving(false);
        }
    };

    const resetToDefault = async () => {
        setSaving(true);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/robots-txt/reset',
                method: 'POST',
            });
            setContent(response.content || '');
            setOriginalContent(response.content || '');
            setActivePreset(null);
        } catch (error) {
            console.error('Error resetting robots.txt:', error);
        } finally {
            setSaving(false);
        }
    };

    const applyPreset = (preset) => {
        const processed = preset.content.replace(/\{\{sitemap_url\}\}/g, sitemapUrl);
        setContent(processed);
        setActivePreset(preset.id);
    };

    const insertDirective = (directive) => {
        setContent(prev => prev + '\n' + directive);
    };

    const testPath = async () => {
        setTesting(true);
        setTestResult(null);
        try {
            const response = await apiFetch({
                path: '/wpseopilot/v2/tools/robots-txt/test',
                method: 'POST',
                data: { path: testUrl, content },
            });
            setTestResult(response);
        } catch (error) {
            console.error('Error testing path:', error);
            setTestResult({ allowed: false, error: error.message });
        } finally {
            setTesting(false);
        }
    };

    const validateContent = (text) => {
        const errors = [];
        const warnings = [];
        const lines = text.split('\n');
        let hasUserAgent = false;
        let lastUserAgent = null;

        lines.forEach((line, index) => {
            const trimmed = line.trim();
            const lineNum = index + 1;

            // Skip empty lines and comments
            if (!trimmed || trimmed.startsWith('#')) {
                return;
            }

            // Check for valid directives
            const validDirectives = ['user-agent', 'disallow', 'allow', 'sitemap', 'crawl-delay', 'host'];
            const directivePart = trimmed.split(':')[0].toLowerCase();

            if (!validDirectives.includes(directivePart)) {
                warnings.push(`Line ${lineNum}: Unknown directive "${directivePart}"`);
            }

            // User-agent tracking
            if (directivePart === 'user-agent') {
                hasUserAgent = true;
                lastUserAgent = trimmed.split(':')[1]?.trim();
            }

            // Check if Disallow/Allow comes before User-agent
            if ((directivePart === 'disallow' || directivePart === 'allow') && !hasUserAgent) {
                errors.push(`Line ${lineNum}: ${directivePart} must come after User-agent`);
            }

            // Check for trailing spaces
            if (line !== trimmed && line.endsWith(' ')) {
                warnings.push(`Line ${lineNum}: Trailing spaces detected`);
            }
        });

        if (!hasUserAgent && text.trim().length > 0) {
            errors.push('Missing User-agent directive');
        }

        setValidation({
            valid: errors.length === 0,
            errors,
            warnings
        });
    };

    const hasChanges = content !== originalContent;

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <h1>robots.txt Editor</h1>
                </div>
                <div className="loading-spinner">Loading...</div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>robots.txt Editor</h1>
                    <p>Control how search engines crawl your site. Changes are saved to the database and override the default WordPress robots.txt.</p>
                </div>
                <div className="page-header__actions">
                    <button
                        type="button"
                        className="btn btn--secondary"
                        onClick={resetToDefault}
                        disabled={saving}
                    >
                        Reset to Default
                    </button>
                    <button
                        type="button"
                        className="btn btn--primary"
                        onClick={saveRobotsTxt}
                        disabled={saving || !hasChanges}
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </div>

            <div className="robots-editor">
                {/* Presets Section */}
                <div className="robots-editor__presets card">
                    <h3>Quick Presets</h3>
                    <p className="text-muted">Apply a preset configuration to get started quickly.</p>
                    <div className="presets-grid">
                        {presets.map((preset) => (
                            <button
                                key={preset.id}
                                type="button"
                                className={`preset-card ${activePreset === preset.id ? 'preset-card--active' : ''}`}
                                onClick={() => applyPreset(preset)}
                            >
                                <strong>{preset.name}</strong>
                                <span>{preset.description}</span>
                            </button>
                        ))}
                    </div>
                </div>

                <div className="robots-editor__main">
                    {/* Editor */}
                    <div className="robots-editor__editor card">
                        <div className="editor-header">
                            <h3>robots.txt Content</h3>
                            <a
                                href={`${siteUrl}/robots.txt`}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn--text"
                            >
                                View Live robots.txt
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3"/>
                                </svg>
                            </a>
                        </div>
                        <textarea
                            className="robots-textarea"
                            value={content}
                            onChange={(e) => setContent(e.target.value)}
                            placeholder="# robots.txt file&#10;User-agent: *&#10;Disallow:"
                            spellCheck={false}
                        />

                        {/* Quick Insert */}
                        <div className="quick-insert">
                            <span className="quick-insert__label">Insert:</span>
                            {directives.map((d, i) => (
                                <button
                                    key={i}
                                    type="button"
                                    className="quick-insert__btn"
                                    onClick={() => insertDirective(d.value)}
                                >
                                    {d.label}
                                </button>
                            ))}
                        </div>
                    </div>

                    {/* Sidebar */}
                    <div className="robots-editor__sidebar">
                        {/* Validation */}
                        <div className="card">
                            <h3>
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" style={{ marginRight: '8px' }}>
                                    {validation.valid ? (
                                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    ) : (
                                        <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                    )}
                                </svg>
                                Validation
                            </h3>
                            {validation.valid && validation.warnings.length === 0 ? (
                                <p className="validation-success">No issues found</p>
                            ) : (
                                <div className="validation-issues">
                                    {validation.errors.map((err, i) => (
                                        <div key={`e${i}`} className="validation-issue validation-issue--error">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M15 9l-6 6M9 9l6 6"/>
                                            </svg>
                                            {err}
                                        </div>
                                    ))}
                                    {validation.warnings.map((warn, i) => (
                                        <div key={`w${i}`} className="validation-issue validation-issue--warning">
                                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <path d="M12 9v2m0 4h.01"/>
                                                <circle cx="12" cy="12" r="10"/>
                                            </svg>
                                            {warn}
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        {/* Path Tester */}
                        <div className="card">
                            <h3>Test Path</h3>
                            <p className="text-muted">Check if a URL path is allowed or blocked.</p>
                            <div className="path-tester">
                                <input
                                    type="text"
                                    value={testUrl}
                                    onChange={(e) => setTestUrl(e.target.value)}
                                    placeholder="/example-path/"
                                />
                                <button
                                    type="button"
                                    className="btn btn--secondary"
                                    onClick={testPath}
                                    disabled={testing}
                                >
                                    {testing ? 'Testing...' : 'Test'}
                                </button>
                            </div>
                            {testResult && (
                                <div className={`test-result ${testResult.allowed ? 'test-result--allowed' : 'test-result--blocked'}`}>
                                    {testResult.allowed ? (
                                        <>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                            Allowed
                                        </>
                                    ) : (
                                        <>
                                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                                <circle cx="12" cy="12" r="10"/>
                                                <path d="M15 9l-6 6M9 9l6 6"/>
                                            </svg>
                                            Blocked
                                        </>
                                    )}
                                    {testResult.rule && (
                                        <span className="test-result__rule">by: {testResult.rule}</span>
                                    )}
                                </div>
                            )}
                        </div>

                        {/* Help */}
                        <div className="card">
                            <h3>Reference</h3>
                            <dl className="reference-list">
                                <dt>User-agent: *</dt>
                                <dd>Apply rules to all crawlers</dd>
                                <dt>Disallow: /path/</dt>
                                <dd>Block crawling of path</dd>
                                <dt>Allow: /path/</dt>
                                <dd>Override Disallow for path</dd>
                                <dt>Sitemap: url</dt>
                                <dd>Point to XML sitemap</dd>
                                <dt>Crawl-delay: 10</dt>
                                <dd>Seconds between requests</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <style>{`
                .robots-editor__presets {
                    margin-bottom: 24px;
                }
                .robots-editor__presets h3 {
                    margin: 0 0 4px;
                }
                .presets-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
                    gap: 12px;
                    margin-top: 16px;
                }
                .preset-card {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    padding: 12px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    background: #fff;
                    cursor: pointer;
                    text-align: left;
                    transition: all 0.15s;
                }
                .preset-card:hover {
                    border-color: #2271b1;
                    background: #f0f6fc;
                }
                .preset-card--active {
                    border-color: #2271b1;
                    background: #f0f6fc;
                    box-shadow: 0 0 0 1px #2271b1;
                }
                .preset-card strong {
                    color: #1d2327;
                    font-size: 13px;
                }
                .preset-card span {
                    color: #646970;
                    font-size: 12px;
                }
                .robots-editor__main {
                    display: grid;
                    grid-template-columns: 1fr 320px;
                    gap: 24px;
                }
                @media (max-width: 1024px) {
                    .robots-editor__main {
                        grid-template-columns: 1fr;
                    }
                }
                .robots-editor__editor {
                    min-height: 400px;
                }
                .editor-header {
                    display: flex;
                    justify-content: space-between;
                    align-items: center;
                    margin-bottom: 12px;
                }
                .editor-header h3 {
                    margin: 0;
                }
                .robots-textarea {
                    width: 100%;
                    min-height: 350px;
                    padding: 16px;
                    font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
                    font-size: 13px;
                    line-height: 1.6;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    resize: vertical;
                    background: #f9f9f9;
                }
                .robots-textarea:focus {
                    outline: none;
                    border-color: #2271b1;
                    box-shadow: 0 0 0 1px #2271b1;
                }
                .quick-insert {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    align-items: center;
                    margin-top: 12px;
                    padding-top: 12px;
                    border-top: 1px solid #eee;
                }
                .quick-insert__label {
                    color: #646970;
                    font-size: 12px;
                    font-weight: 500;
                }
                .quick-insert__btn {
                    padding: 4px 10px;
                    font-size: 12px;
                    background: #f0f0f1;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                    cursor: pointer;
                    transition: all 0.15s;
                }
                .quick-insert__btn:hover {
                    background: #e0e0e1;
                    border-color: #c0c0c0;
                }
                .robots-editor__sidebar .card {
                    margin-bottom: 16px;
                }
                .robots-editor__sidebar .card h3 {
                    display: flex;
                    align-items: center;
                    margin: 0 0 8px;
                    font-size: 14px;
                }
                .validation-success {
                    color: #00a32a;
                    font-size: 13px;
                }
                .validation-issues {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                }
                .validation-issue {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    font-size: 12px;
                    padding: 8px;
                    border-radius: 4px;
                }
                .validation-issue svg {
                    flex-shrink: 0;
                    margin-top: 1px;
                }
                .validation-issue--error {
                    background: #fcf0f1;
                    color: #d63638;
                }
                .validation-issue--warning {
                    background: #fcf9e8;
                    color: #996800;
                }
                .path-tester {
                    display: flex;
                    gap: 8px;
                    margin-top: 8px;
                }
                .path-tester input {
                    flex: 1;
                    padding: 6px 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                }
                .test-result {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    margin-top: 12px;
                    padding: 10px;
                    border-radius: 4px;
                    font-weight: 500;
                }
                .test-result--allowed {
                    background: #edfaef;
                    color: #00a32a;
                }
                .test-result--blocked {
                    background: #fcf0f1;
                    color: #d63638;
                }
                .test-result__rule {
                    font-weight: 400;
                    font-size: 12px;
                    opacity: 0.8;
                }
                .reference-list {
                    margin: 0;
                    font-size: 12px;
                }
                .reference-list dt {
                    font-family: monospace;
                    font-weight: 600;
                    color: #1d2327;
                    margin-top: 8px;
                }
                .reference-list dt:first-child {
                    margin-top: 0;
                }
                .reference-list dd {
                    margin: 2px 0 0 0;
                    color: #646970;
                }
                .text-muted {
                    color: #646970;
                    font-size: 13px;
                    margin: 0;
                }
                .btn--text {
                    display: inline-flex;
                    align-items: center;
                    gap: 4px;
                    color: #2271b1;
                    background: none;
                    border: none;
                    padding: 0;
                    font-size: 13px;
                    cursor: pointer;
                    text-decoration: none;
                }
                .btn--text:hover {
                    color: #135e96;
                    text-decoration: underline;
                }
            `}</style>
        </div>
    );
};

export default RobotsTxt;
