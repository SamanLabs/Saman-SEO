/**
 * Analytics Privacy Notice Component
 *
 * Displays information about what data is collected and how it's used.
 */

import { useState } from '@wordpress/element';

const AnalyticsNotice = ({ isEnabled, onToggle }) => {
    const [expanded, setExpanded] = useState(false);

    return (
        <div className="analytics-notice">
            <div className="analytics-notice__header">
                <div className="analytics-notice__icon">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                        <path d="M12 16v-4" />
                        <path d="M12 8h.01" />
                    </svg>
                </div>
                <div className="analytics-notice__content">
                    <h4>Help Improve Saman SEO</h4>
                    <p>
                        Share anonymous usage data to help us understand which features are most valuable
                        and improve the plugin for everyone.
                    </p>
                </div>
                <label className="toggle">
                    <input
                        type="checkbox"
                        checked={isEnabled}
                        onChange={(e) => onToggle(e.target.checked)}
                    />
                    <span className="toggle-track" />
                </label>
            </div>

            <button
                type="button"
                className="analytics-notice__expand"
                onClick={() => setExpanded(!expanded)}
            >
                {expanded ? 'Hide details' : 'What data is collected?'}
                <svg
                    width="16"
                    height="16"
                    viewBox="0 0 24 24"
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="2"
                    style={{ transform: expanded ? 'rotate(180deg)' : 'none' }}
                >
                    <polyline points="6 9 12 15 18 9" />
                </svg>
            </button>

            {expanded && (
                <div className="analytics-notice__details">
                    <div className="analytics-privacy-info">
                        <h5>What We Collect</h5>
                        <ul>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>Feature usage (e.g., "redirect created", "AI title generated")</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>Plugin version number</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>Pages visited within the plugin</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <polyline points="20 6 9 17 4 12" />
                                </svg>
                                <span>Anonymized site identifier (hashed URL)</span>
                            </li>
                        </ul>

                        <h5>What We Never Collect</h5>
                        <ul className="analytics-privacy-info__never">
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                                <span>Personal information (names, emails, IP addresses)</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                                <span>Your content (posts, pages, meta data)</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                                <span>API keys or credentials</span>
                            </li>
                            <li>
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                    <line x1="18" y1="6" x2="6" y2="18" />
                                    <line x1="6" y1="6" x2="18" y2="18" />
                                </svg>
                                <span>Your site's URL or domain name</span>
                            </li>
                        </ul>

                        <h5>Privacy Measures</h5>
                        <div className="analytics-privacy-info__measures">
                            <div className="privacy-measure">
                                <span className="privacy-measure__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2" />
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                    </svg>
                                </span>
                                <div>
                                    <strong>No Cookies</strong>
                                    <span>We don't use any tracking cookies</span>
                                </div>
                            </div>
                            <div className="privacy-measure">
                                <span className="privacy-measure__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z" />
                                    </svg>
                                </span>
                                <div>
                                    <strong>Admin Only</strong>
                                    <span>Only tracks within plugin admin pages</span>
                                </div>
                            </div>
                            <div className="privacy-measure">
                                <span className="privacy-measure__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <circle cx="12" cy="12" r="10" />
                                        <line x1="4.93" y1="4.93" x2="19.07" y2="19.07" />
                                    </svg>
                                </span>
                                <div>
                                    <strong>Opt-out Anytime</strong>
                                    <span>Disable tracking at any time</span>
                                </div>
                            </div>
                            <div className="privacy-measure">
                                <span className="privacy-measure__icon">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4" />
                                    </svg>
                                </span>
                                <div>
                                    <strong>Self-Hosted</strong>
                                    <span>Analytics on our own Matomo instance</span>
                                </div>
                            </div>
                        </div>

                        <p className="analytics-privacy-info__footer">
                            This data helps us prioritize features, fix common issues, and understand how the plugin
                            is used in real-world scenarios. Thank you for helping us improve!
                        </p>
                    </div>
                </div>
            )}
        </div>
    );
};

export default AnalyticsNotice;
