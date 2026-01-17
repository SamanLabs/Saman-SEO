import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Plugin icon configurations
const PLUGIN_ICONS = {
    'saman-seo': {
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
    'saman-seo': 'Performance-led SEO insights.',
    'wp-ai-pilot': 'Centralized AI management.',
    'wp-security-pilot': 'Open standard security.',
};

const More = () => {
    const [plugins, setPlugins] = useState({});
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [actionLoading, setActionLoading] = useState({});
    const [betaLoading, setBetaLoading] = useState({});
    const [notice, setNotice] = useState(null);

    // Load plugins on mount
    const loadPlugins = useCallback(async () => {
        try {
            setLoading(true);
            const data = await apiFetch({ path: '/saman-seo/v1/updater/plugins' });
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
        setNotice(null);
        try {
            await apiFetch({ path: '/saman-seo/v1/updater/check', method: 'POST' });
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
        setNotice(null);
        try {
            const response = await apiFetch({
                path: `/saman-seo/v1/updater/${action}`,
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

    // Handle beta toggle
    const handleToggleBeta = async (slug, currentEnabled) => {
        setBetaLoading(prev => ({ ...prev, [slug]: true }));
        setNotice(null);
        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/updater/beta',
                method: 'POST',
                data: { slug, enabled: !currentEnabled },
            });
            setNotice({ type: 'success', message: response.message });
            await loadPlugins();
        } catch (error) {
            console.error('Failed to toggle beta:', error);
            setNotice({ type: 'error', message: error.message || 'Failed to toggle beta versions.' });
        } finally {
            setBetaLoading(prev => ({ ...prev, [slug]: false }));
        }
    };

    // Get icon config for a plugin
    const getIconConfig = (slug) => PLUGIN_ICONS[slug] || PLUGIN_ICONS['saman-seo'];

    // Get tagline for a plugin
    const getTagline = (slug) => PLUGIN_TAGLINES[slug] || '';

    // Determine card state classes
    const getCardClasses = (plugin) => {
        const classes = ['managed-plugin-card'];
        if (plugin.active) classes.push('active');
        if (plugin.update_available) classes.push('has-update');
        if (plugin.update_is_beta) classes.push('has-beta-update');
        return classes.join(' ');
    };

    // Get status badge for plugin state
    const getStatusBadge = (plugin) => {
        if (!plugin.installed) {
            return <span className="badge">Not Installed</span>;
        }
        if (plugin.update_available && plugin.update_is_beta) {
            return <span className="badge beta">Beta Update</span>;
        }
        if (plugin.update_available) {
            return <span className="badge warning">Update Available</span>;
        }
        if (plugin.active) {
            return <span className="badge success">Active</span>;
        }
        return <span className="badge">Inactive</span>;
    };

    // Get version display
    const getVersionInfo = (plugin) => {
        if (!plugin.installed) {
            return plugin.remote_version ? `v${plugin.remote_version} available` : 'Checking...';
        }
        if (plugin.update_available) {
            const updateVersion = plugin.update_version || plugin.remote_version;
            const betaLabel = plugin.update_is_beta ? ' (beta)' : '';
            return `v${plugin.current_version} → v${updateVersion}${betaLabel}`;
        }
        return plugin.current_version ? `v${plugin.current_version}` : '';
    };

    // Render plugin icon
    const renderPluginIcon = (slug, plugin) => {
        const iconConfig = getIconConfig(slug);

        if (plugin.icon) {
            return (
                <>
                    <img
                        src={plugin.icon}
                        alt={plugin.name}
                        onError={(e) => {
                            e.target.style.display = 'none';
                            e.target.nextSibling.style.display = 'flex';
                        }}
                    />
                    <div className={`managed-plugin-icon-fallback ${iconConfig.className}`} style={{ display: 'none' }}>
                        {iconConfig.svg}
                    </div>
                </>
            );
        }

        return (
            <div className={`managed-plugin-icon-fallback ${iconConfig.className}`}>
                {iconConfig.svg}
            </div>
        );
    };

    // Render action buttons
    const renderActions = (slug, plugin) => {
        const isLoading = actionLoading[slug];

        return (
            <div className="managed-plugin-actions">
                {/* Install button */}
                {!plugin.installed && (
                    <button
                        className="button primary"
                        onClick={() => handleAction(slug, 'install')}
                        disabled={isLoading || !plugin.download_url}
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
                        className={`button ${plugin.update_is_beta ? 'beta' : 'warning'}`}
                        onClick={() => handleAction(slug, 'update')}
                        disabled={isLoading}
                    >
                        {isLoading === 'update' ? (
                            <>
                                <span className="spinner is-active"></span>
                                Updating...
                            </>
                        ) : (
                            plugin.update_is_beta ? 'Install Beta' : 'Update'
                        )}
                    </button>
                )}

                {/* Activate/Deactivate button */}
                {plugin.installed && (
                    plugin.active ? (
                        <button
                            className="button ghost"
                            onClick={() => handleAction(slug, 'deactivate')}
                            disabled={isLoading}
                        >
                            {isLoading === 'deactivate' ? 'Deactivating...' : 'Deactivate'}
                        </button>
                    ) : (
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
                    )
                )}

                {/* GitHub link */}
                <a
                    href={plugin.github_url}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="button ghost"
                >
                    GitHub
                </a>
            </div>
        );
    };

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>Pilot Plugins</h1>
                    <p>Install and manage plugins from the Pilot ecosystem.</p>
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
                <div className="managed-plugins-grid">
                    {Object.entries(plugins).map(([slug, plugin]) => (
                        <div key={slug} className={getCardClasses(plugin)}>
                            <div className="managed-plugin-header">
                                <div className="managed-plugin-icon">
                                    {renderPluginIcon(slug, plugin)}
                                </div>
                                <div className="managed-plugin-info">
                                    <div className="managed-plugin-title">
                                        <h3>{plugin.name}</h3>
                                        {getStatusBadge(plugin)}
                                    </div>
                                    <p className="managed-plugin-version">{getVersionInfo(plugin)}</p>
                                </div>
                            </div>

                            <p className="managed-plugin-description">{plugin.description}</p>
                            <p className="managed-plugin-tagline">{getTagline(slug)}</p>

                            {/* Beta Toggle - only show for installed plugins */}
                            {plugin.installed && (
                                <div className="managed-plugin-beta">
                                    <label className="beta-toggle">
                                        <input
                                            type="checkbox"
                                            checked={plugin.beta_enabled || false}
                                            onChange={() => handleToggleBeta(slug, plugin.beta_enabled)}
                                            disabled={betaLoading[slug]}
                                        />
                                        <span className="beta-toggle-slider"></span>
                                        <span className="beta-toggle-label">
                                            Beta versions
                                            {plugin.beta_available && !plugin.beta_enabled && plugin.beta_version && (
                                                <span className="beta-available-hint"> (v{plugin.beta_version} available)</span>
                                            )}
                                        </span>
                                    </label>
                                </div>
                            )}

                            {renderActions(slug, plugin)}
                        </div>
                    ))}
                </div>
            )}

            {/* Empty state */}
            {!loading && Object.keys(plugins).length === 0 && (
                <div className="empty-state">
                    <p>No managed plugins found.</p>
                </div>
            )}
        </div>
    );
};

export default More;
