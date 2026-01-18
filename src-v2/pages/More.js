import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Plugin type icons
const TYPE_ICONS = {
    seo: (
        <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
        </svg>
    ),
    ai: (
        <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
            <circle cx="12" cy="12" r="1"/>
            <circle cx="8" cy="12" r="1"/>
            <circle cx="16" cy="12" r="1"/>
        </svg>
    ),
    security: (
        <svg viewBox="0 0 24 24" role="img" focusable="false">
            <path d="M12 2L4 5.4v6.2c0 5.1 3.4 9.7 8 10.4 4.6-.7 8-5.3 8-10.4V5.4L12 2zm0 2.2l6 2.3v5.1c0 4-2.5 7.6-6 8.3-3.5-.7-6-4.3-6-8.3V6.5l6-2.3z" />
            <path d="M10.5 12.7l-2-2-1.3 1.3 3.3 3.3 5.3-5.3-1.3-1.3-4 4z" />
        </svg>
    ),
};

// Fallback icon
const DEFAULT_ICON = (
    <svg viewBox="0 0 24 24" role="img" focusable="false">
        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
    </svg>
);

const More = () => {
    const [plugins, setPlugins] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        const loadPlugins = async () => {
            try {
                setLoading(true);
                const data = await apiFetch({ path: '/saman-seo/v1/more/plugins' });
                setPlugins(data || []);
            } catch (err) {
                console.error('Failed to load plugins:', err);
                setError('Failed to load plugins.');
            } finally {
                setLoading(false);
            }
        };
        loadPlugins();
    }, []);

    const getTypeIcon = (type) => TYPE_ICONS[type] || DEFAULT_ICON;

    const getGitHubUrl = (repo) => `https://github.com/${repo}`;

    const getStatusBadge = (plugin) => {
        if (plugin.coming_soon) {
            return <span className="badge coming-soon">Coming Soon</span>;
        }
        if (plugin.is_active) {
            return <span className="badge success">Active</span>;
        }
        if (plugin.is_installed) {
            return <span className="badge">Installed</span>;
        }
        return <span className="badge">Available</span>;
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Saman Plugins</h1>
                    <p>Discover plugins from the Saman Labs ecosystem - open source tools built for WordPress.</p>
                </div>
            </div>

            {error && (
                <div className="notice notice-error">
                    <p>{error}</p>
                </div>
            )}

            {loading && (
                <div className="loading-state">
                    <span className="spinner is-active"></span>
                    <p>Loading plugins...</p>
                </div>
            )}

            {!loading && plugins.length > 0 && (
                <div className="managed-plugins-grid">
                    {plugins.map((plugin) => (
                        <div
                            key={plugin.slug}
                            className={`managed-plugin-card ${plugin.is_active ? 'active' : ''} ${plugin.coming_soon ? 'coming-soon' : ''}`}
                        >
                            <div className="managed-plugin-header">
                                <div className="managed-plugin-icon">
                                    {plugin.icon ? (
                                        <img
                                            src={plugin.icon}
                                            alt={plugin.name}
                                            onError={(e) => {
                                                e.target.style.display = 'none';
                                                e.target.nextSibling.style.display = 'flex';
                                            }}
                                        />
                                    ) : null}
                                    <div
                                        className={`managed-plugin-icon-fallback ${plugin.type || 'default'}`}
                                        style={{ display: plugin.icon ? 'none' : 'flex' }}
                                    >
                                        {getTypeIcon(plugin.type)}
                                    </div>
                                </div>
                                <div className="managed-plugin-info">
                                    <div className="managed-plugin-title">
                                        <h3>{plugin.name}</h3>
                                        {getStatusBadge(plugin)}
                                    </div>
                                </div>
                            </div>

                            <p className="managed-plugin-description">{plugin.description}</p>

                            <div className="managed-plugin-actions">
                                {plugin.coming_soon ? (
                                    <a
                                        href={getGitHubUrl(plugin.repo)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="button ghost"
                                    >
                                        <svg viewBox="0 0 24 24" width="16" height="16" style={{ marginRight: '6px' }}>
                                            <path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                        </svg>
                                        Watch on GitHub
                                    </a>
                                ) : (
                                    <a
                                        href={getGitHubUrl(plugin.repo)}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="button primary"
                                    >
                                        <svg viewBox="0 0 24 24" width="16" height="16" style={{ marginRight: '6px' }}>
                                            <path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                        </svg>
                                        View on GitHub
                                    </a>
                                )}
                            </div>
                        </div>
                    ))}
                </div>
            )}

            {!loading && plugins.length === 0 && !error && (
                <div className="empty-state">
                    <p>No plugins found.</p>
                </div>
            )}

            <div className="saman-labs-footer" style={{ marginTop: '40px', textAlign: 'center', padding: '20px', borderTop: '1px solid #e0e0e0' }}>
                <p style={{ color: '#666', marginBottom: '10px' }}>
                    All Saman plugins are open source and free to use.
                </p>
                <a
                    href="https://github.com/SamanLabs"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="button ghost"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" style={{ marginRight: '6px' }}>
                        <path fill="currentColor" d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                    </svg>
                    Visit Saman Labs on GitHub
                </a>
            </div>
        </div>
    );
};

export default More;
