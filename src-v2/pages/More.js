import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Plugin icon configurations
const PLUGIN_ICONS = {
    'wp-seo-pilot': {
        className: 'seo',
        svg: (
            <svg viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/>
            </svg>
        ),
    },
    'wp-ai-pilot': {
        className: 'ai',
        svg: (
            <svg viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"/>
                <circle cx="12" cy="12" r="1"/>
                <circle cx="8" cy="12" r="1"/>
                <circle cx="16" cy="12" r="1"/>
            </svg>
        ),
    },
    'wp-security-pilot': {
        className: 'security',
        svg: (
            <svg viewBox="0 0 24 24" role="img" focusable="false">
                <path d="M12 2L4 5.4v6.2c0 5.1 3.4 9.7 8 10.4 4.6-.7 8-5.3 8-10.4V5.4L12 2zm0 2.2l6 2.3v5.1c0 4-2.5 7.6-6 8.3-3.5-.7-6-4.3-6-8.3V6.5l6-2.3z" />
                <path d="M10.5 12.7l-2-2-1.3 1.3 3.3 3.3 5.3-5.3-1.3-1.3-4 4z" />
            </svg>
        ),
    },
};

// Plugin taglines
const PLUGIN_TAGLINES = {
    'wp-seo-pilot': 'Performance-led SEO insights.',
    'wp-ai-pilot': 'Centralized AI management.',
    'wp-security-pilot': 'Open standard security.',
};

const More = () => {
    const [plugins, setPlugins] = useState({});
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [actionLoading, setActionLoading] = useState({});
    const [notice, setNotice] = useState(null);

    // Load plugins on mount
    const loadPlugins = useCallback(async () => {
        try {
            setLoading(true);
            const data = await apiFetch({ path: '/wpseopilot/v2/updater/plugins' });
            setPlugins(data);
        } catch (error) {
            console.error('Failed to load plugins:', error);
            setNotice({ type: 'error', message: 'Failed to load plugins.' });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadPlugins();
    }, [loadPlugins]);

    // Auto-dismiss notices
    useEffect(() => {
        if (notice) {
            const timer = setTimeout(() => setNotice(null), 5000);
            return () => clearTimeout(timer);
        }
    }, [notice]);

    // Check for updates
    const checkForUpdates = async () => {
        setChecking(true);
        try {
            await apiFetch({ path: '/wpseopilot/v2/updater/check', method: 'POST' });
            await loadPlugins();
            setNotice({ type: 'success', message: 'Update check complete.' });
        } catch (error) {
            console.error('Failed to check updates:', error);
            setNotice({ type: 'error', message: 'Failed to check for updates.' });
        } finally {
            setChecking(false);
        }
    };

    // Handle install/update/activate/deactivate
    const handleAction = async (slug, action) => {
        setActionLoading(prev => ({ ...prev, [slug]: action }));
        try {
            const response = await apiFetch({
                path: `/wpseopilot/v2/updater/${action}`,
                method: 'POST',
                data: { slug },
            });
            setNotice({ type: 'success', message: response.message || `Plugin ${action}d successfully.` });
            await loadPlugins();
        } catch (error) {
            console.error(`Failed to ${action} plugin:`, error);
            setNotice({ type: 'error', message: error.message || `Failed to ${action} plugin.` });
        } finally {
            setActionLoading(prev => ({ ...prev, [slug]: null }));
        }
    };

    // Get icon config for a plugin
    const getIconConfig = (slug) => PLUGIN_ICONS[slug] || PLUGIN_ICONS['wp-seo-pilot'];

    // Get tagline for a plugin
    const getTagline = (slug) => PLUGIN_TAGLINES[slug] || '';

    // Determine card state classes
    const getCardClasses = (plugin) => {
        const classes = ['pilot-card'];
        if (plugin.active) classes.push('active');
        if (plugin.update_available) classes.push('has-update');
        return classes.join(' ');
    };

    // Get badge for plugin state
    const getBadge = (plugin) => {
        if (!plugin.installed) return <span className="badge">Available</span>;
        if (plugin.update_available) return <span className="badge warning">Update Available</span>;
        if (plugin.active) return <span className="badge success">Active</span>;
        return <span className="badge">Inactive</span>;
    };

    // Get pill for plugin state
    const getPill = (plugin) => {
        if (!plugin.installed) return <span className="pill warning">Not Installed</span>;
        if (plugin.active) return <span className="pill success">Active</span>;
        return <span className="pill">Inactive</span>;
    };

    // Get version display
    const getVersionDisplay = (plugin) => {
        if (!plugin.installed) {
            return plugin.remote_version ? `v${plugin.remote_version} available` : '';
        }
        if (plugin.update_available) {
            return `v${plugin.current_version} → v${plugin.remote_version}`;
        }
        return plugin.current_version ? `v${plugin.current_version}` : '';
    };

    // Render action buttons
    const renderActions = (slug, plugin) => {
        const isLoading = actionLoading[slug];

        return (
            <div className="pilot-card-actions">
                {/* Install button */}
                {!plugin.installed && (
                    <button
                        className="button primary"
                        onClick={() => handleAction(slug, 'install')}
                        disabled={isLoading}
                    >
                        {isLoading === 'install' ? (
                            <>
                                <span className="spinner is-active"></span>
                                Installing...
                            </>
                        ) : (
                            'Install'
                        )}
                    </button>
                )}

                {/* Update button */}
                {plugin.installed && plugin.update_available && (
                    <button
                        className="button warning"
                        onClick={() => handleAction(slug, 'update')}
                        disabled={isLoading}
                    >
                        {isLoading === 'update' ? (
                            <>
                                <span className="spinner is-active"></span>
                                Updating...
                            </>
                        ) : (
                            'Update'
                        )}
                    </button>
                )}

                {/* Activate button */}
                {plugin.installed && !plugin.active && (
                    <button
                        className="button primary"
                        onClick={() => handleAction(slug, 'activate')}
                        disabled={isLoading}
                    >
                        {isLoading === 'activate' ? (
                            <>
                                <span className="spinner is-active"></span>
                                Activating...
                            </>
                        ) : (
                            'Activate'
                        )}
                    </button>
                )}

                {/* Deactivate button */}
                {plugin.installed && plugin.active && (
                    <button
                        className="button ghost"
                        onClick={() => handleAction(slug, 'deactivate')}
                        disabled={isLoading}
                    >
                        {isLoading === 'deactivate' ? 'Deactivating...' : 'Deactivate'}
                    </button>
                )}

                {/* GitHub link */}
                <a
                    href={plugin.github_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="pilot-card-link"
                >
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="currentColor">
                        <path d="M12 2C6.5 2 2 6.6 2 12.3c0 4.6 2.9 8.5 6.9 9.9.5.1.7-.2.7-.5v-1.9c-2.8.6-3.3-1.2-3.3-1.2-.5-1.2-1.2-1.5-1.2-1.5-1-.7.1-.7.1-.7 1.1.1 1.7 1.2 1.7 1.2 1 .1.7 1.7 2.6 1.2.1-.8.4-1.2.7-1.5-2.2-.2-4.5-1.2-4.5-5.2 0-1.1.4-2 1-2.7-.1-.2-.4-1.3.1-2.7 0 0 .8-.2 2.7 1a9.2 9.2 0 0 1 4.9 0c1.9-1.2 2.7-1 2.7-1 .5 1.4.2 2.5.1 2.7.6.7 1 1.6 1 2.7 0 4-2.3 5-4.5 5.2.4.3.8 1 .8 2.1v3c0 .3.2.6.7.5 4-1.4 6.9-5.3 6.9-9.9C22 6.6 17.5 2 12 2z"/>
                    </svg>
                    GitHub
                </a>
            </div>
        );
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>More from Pilot</h1>
                    <p>Expand your WordPress toolkit with trusted companion plugins from the Pilot family.</p>
                </div>
                <button
                    className="button ghost"
                    onClick={checkForUpdates}
                    disabled={checking || loading}
                >
                    {checking ? (
                        <>
                            <span className="spinner is-active"></span>
                            Checking...
                        </>
                    ) : (
                        'Check for Updates'
                    )}
                </button>
            </div>

            {/* Notice */}
            {notice && (
                <div className={`notice notice-${notice.type}`}>
                    <p>{notice.message}</p>
                    <button type="button" className="notice-dismiss" onClick={() => setNotice(null)}>
                        <span className="screen-reader-text">Dismiss</span>
                    </button>
                </div>
            )}

            {/* Loading state */}
            {loading && (
                <div className="loading-state">
                    <span className="spinner is-active"></span>
                    <p>Loading plugins...</p>
                </div>
            )}

            {/* Plugin grid */}
            {!loading && (
                <div className="pilot-grid">
                    {Object.entries(plugins).map(([slug, plugin]) => {
                        const iconConfig = getIconConfig(slug);
                        return (
                            <div key={slug} className={getCardClasses(plugin)}>
                                <div className="pilot-card-head">
                                    <div className="pilot-card-identity">
                                        <span className={`pilot-card-mark ${iconConfig.className}`} aria-hidden="true">
                                            {iconConfig.svg}
                                        </span>
                                        <div>
                                            <div className="pilot-card-title">
                                                <h3>{plugin.name}</h3>
                                                {getBadge(plugin)}
                                            </div>
                                            <p className="pilot-card-tagline">{getTagline(slug)}</p>
                                        </div>
                                    </div>
                                </div>
                                <p className="pilot-card-desc">{plugin.description}</p>
                                <p className="pilot-card-version">{getVersionDisplay(plugin)}</p>
                                <div className="pilot-card-meta">
                                    {getPill(plugin)}
                                    {renderActions(slug, plugin)}
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
};

export default More;
