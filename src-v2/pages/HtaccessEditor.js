/**
 * .htaccess Editor Page
 *
 * Safely edit .htaccess file with backups and presets.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const HtaccessEditor = ({ onNavigate }) => {
    const [content, setContent] = useState('');
    const [originalContent, setOriginalContent] = useState('');
    const [backups, setBackups] = useState([]);
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);
    const [success, setSuccess] = useState(null);
    const [hasChanges, setHasChanges] = useState(false);
    const [showBackups, setShowBackups] = useState(false);

    // Presets
    const presets = [
        {
            name: 'Disable Directory Browsing',
            code: 'Options -Indexes',
            description: 'Prevent users from seeing directory contents',
        },
        {
            name: 'Force HTTPS',
            code: `RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`,
            description: 'Redirect all HTTP traffic to HTTPS',
        },
        {
            name: 'Remove www',
            code: `RewriteEngine On
RewriteCond %{HTTP_HOST} ^www\\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]`,
            description: 'Redirect www to non-www version',
        },
        {
            name: 'Add www',
            code: `RewriteEngine On
RewriteCond %{HTTP_HOST} !^www\\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$1 [R=301,L]`,
            description: 'Redirect non-www to www version',
        },
        {
            name: 'Block Bad Bots',
            code: `RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (AhrefsBot|MJ12bot|SemrushBot|DotBot) [NC]
RewriteRule .* - [F,L]`,
            description: 'Block common SEO crawlers/bots',
        },
        {
            name: 'Enable GZIP Compression',
            code: `<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
  AddOutputFilterByType DEFLATE application/javascript application/x-javascript
  AddOutputFilterByType DEFLATE application/json application/xml
</IfModule>`,
            description: 'Compress text-based files for faster loading',
        },
        {
            name: 'Browser Caching',
            code: `<IfModule mod_expires.c>
  ExpiresActive On
  ExpiresByType image/jpg "access plus 1 year"
  ExpiresByType image/jpeg "access plus 1 year"
  ExpiresByType image/png "access plus 1 year"
  ExpiresByType image/gif "access plus 1 year"
  ExpiresByType image/webp "access plus 1 year"
  ExpiresByType text/css "access plus 1 month"
  ExpiresByType application/javascript "access plus 1 month"
</IfModule>`,
            description: 'Set browser caching for static files',
        },
        {
            name: 'Security Headers',
            code: `<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>`,
            description: 'Add security-related HTTP headers',
        },
    ];

    // Fetch current content
    useEffect(() => {
        const fetchContent = async () => {
            try {
                const response = await apiFetch({ path: '/saman-seo/v1/htaccess' });
                if (response.success) {
                    setContent(response.data.content || '');
                    setOriginalContent(response.data.content || '');
                    setBackups(response.data.backups || []);
                }
            } catch (err) {
                setError('Failed to load .htaccess file: ' + (err.message || 'Unknown error'));
            } finally {
                setLoading(false);
            }
        };

        fetchContent();
    }, []);

    // Track changes
    useEffect(() => {
        setHasChanges(content !== originalContent);
    }, [content, originalContent]);

    // Save content
    const handleSave = useCallback(async () => {
        setSaving(true);
        setError(null);
        setSuccess(null);

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/htaccess',
                method: 'POST',
                data: { content },
            });

            if (response.success) {
                setOriginalContent(content);
                setSuccess('File saved successfully! A backup was created.');
                if (response.data.backups) {
                    setBackups(response.data.backups);
                }
            } else {
                setError(response.message || 'Failed to save file');
            }
        } catch (err) {
            setError(err.message || 'Failed to save file');
        } finally {
            setSaving(false);
        }
    }, [content]);

    // Restore backup
    const handleRestore = useCallback(async (backup) => {
        if (!window.confirm(`Restore backup from ${backup.date}? This will overwrite the current .htaccess file.`)) {
            return;
        }

        setSaving(true);
        setError(null);

        try {
            const response = await apiFetch({
                path: '/saman-seo/v1/htaccess/restore',
                method: 'POST',
                data: { backup: backup.file },
            });

            if (response.success) {
                setContent(response.data.content);
                setOriginalContent(response.data.content);
                setSuccess('Backup restored successfully!');
                setShowBackups(false);
            } else {
                setError(response.message || 'Failed to restore backup');
            }
        } catch (err) {
            setError(err.message || 'Failed to restore backup');
        } finally {
            setSaving(false);
        }
    }, []);

    // Insert preset
    const insertPreset = useCallback((preset) => {
        const newContent = content.trim()
            ? content + '\n\n# ' + preset.name + '\n' + preset.code
            : '# ' + preset.name + '\n' + preset.code;
        setContent(newContent);
    }, [content]);

    // Reset to original
    const handleReset = useCallback(() => {
        if (window.confirm('Discard all changes?')) {
            setContent(originalContent);
        }
    }, [originalContent]);

    if (loading) {
        return (
            <div className="page">
                <div className="page-header">
                    <h1>.htaccess Editor</h1>
                </div>
                <div className="card">
                    <div className="loading-state">Loading...</div>
                </div>
            </div>
        );
    }

    return (
        <div className="page">
            <div className="page-header">
                <div>
                    <h1>.htaccess Editor</h1>
                    <p>Edit your server configuration file. Changes take effect immediately.</p>
                </div>
                <div className="page-header__actions">
                    <button
                        type="button"
                        className="btn btn--secondary"
                        onClick={() => setShowBackups(!showBackups)}
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="16" height="16">
                            <path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/>
                            <path d="M3 3v5h5"/>
                        </svg>
                        Backups ({backups.length})
                    </button>
                    {hasChanges && (
                        <button
                            type="button"
                            className="btn btn--secondary"
                            onClick={handleReset}
                        >
                            Discard Changes
                        </button>
                    )}
                    <button
                        type="button"
                        className="btn btn--primary"
                        onClick={handleSave}
                        disabled={saving || !hasChanges}
                    >
                        {saving ? 'Saving...' : 'Save Changes'}
                    </button>
                </div>
            </div>

            {/* Warning */}
            <div className="notice notice--warning">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
                <span>
                    <strong>Caution:</strong> Incorrect .htaccess rules can break your site. A backup is created before each save.
                </span>
            </div>

            {/* Messages */}
            {error && (
                <div className="notice notice--error">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 8v4m0 4h.01"/>
                    </svg>
                    <span>{error}</span>
                </div>
            )}
            {success && (
                <div className="notice notice--success">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" width="20" height="20">
                        <path d="M22 11.08V12a10 10 0 11-5.93-9.14"/>
                        <path d="M22 4L12 14.01l-3-3"/>
                    </svg>
                    <span>{success}</span>
                </div>
            )}

            <div className="htaccess-layout">
                {/* Editor */}
                <div className="card htaccess-editor-card">
                    <div className="htaccess-editor-header">
                        <span className="htaccess-editor-path">/.htaccess</span>
                        {hasChanges && <span className="htaccess-editor-modified">Modified</span>}
                    </div>
                    <textarea
                        className="htaccess-textarea"
                        value={content}
                        onChange={(e) => setContent(e.target.value)}
                        spellCheck="false"
                        placeholder="# Your .htaccess rules here..."
                    />
                </div>

                {/* Sidebar */}
                <div className="htaccess-sidebar">
                    {/* Backups Panel */}
                    {showBackups && (
                        <div className="card htaccess-backups">
                            <h3>Backups</h3>
                            {backups.length === 0 ? (
                                <p className="htaccess-backups__empty">No backups yet</p>
                            ) : (
                                <ul className="htaccess-backups__list">
                                    {backups.map((backup) => (
                                        <li key={backup.file} className="htaccess-backup">
                                            <div className="htaccess-backup__info">
                                                <span className="htaccess-backup__date">{backup.date}</span>
                                                <span className="htaccess-backup__size">{backup.size}</span>
                                            </div>
                                            <button
                                                type="button"
                                                className="btn btn--small btn--secondary"
                                                onClick={() => handleRestore(backup)}
                                            >
                                                Restore
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}

                    {/* Presets */}
                    <div className="card htaccess-presets">
                        <h3>Quick Insert</h3>
                        <p className="htaccess-presets__desc">Click to add common rules</p>
                        <ul className="htaccess-presets__list">
                            {presets.map((preset) => (
                                <li key={preset.name}>
                                    <button
                                        type="button"
                                        className="htaccess-preset"
                                        onClick={() => insertPreset(preset)}
                                        title={preset.description}
                                    >
                                        <span className="htaccess-preset__name">{preset.name}</span>
                                        <span className="htaccess-preset__desc">{preset.description}</span>
                                    </button>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    );
};

export default HtaccessEditor;
