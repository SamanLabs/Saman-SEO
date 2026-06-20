/**
 * .htaccess Editor Page
 *
 * Safely edit .htaccess file with backups and presets.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
const HtaccessEditor = ( { onNavigate } ) => {
	const [ content, setContent ] = useState( '' );
	const [ originalContent, setOriginalContent ] = useState( '' );
	const [ backups, setBackups ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ success, setSuccess ] = useState( null );
	const [ hasChanges, setHasChanges ] = useState( false );
	const [ showBackups, setShowBackups ] = useState( false );

	// Presets
	const presets = [
		{
			name: __( 'Disable Directory Browsing', 'saman-seo' ),
			code: 'Options -Indexes',
			description: __(
				'Prevent users from seeing directory contents',
				'saman-seo'
			),
		},
		{
			name: __( 'Force HTTPS', 'saman-seo' ),
			code: `RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]`,
			description: __(
				'Redirect all HTTP traffic to HTTPS',
				'saman-seo'
			),
		},
		{
			name: __( 'Remove www', 'saman-seo' ),
			code: `RewriteEngine On
RewriteCond %{HTTP_HOST} ^www\\.(.*)$ [NC]
RewriteRule ^(.*)$ https://%1/$1 [R=301,L]`,
			description: __( 'Redirect www to non-www version', 'saman-seo' ),
		},
		{
			name: __( 'Add www', 'saman-seo' ),
			code: `RewriteEngine On
RewriteCond %{HTTP_HOST} !^www\\. [NC]
RewriteRule ^(.*)$ https://www.%{HTTP_HOST}/$1 [R=301,L]`,
			description: __( 'Redirect non-www to www version', 'saman-seo' ),
		},
		{
			name: __( 'Block Bad Bots', 'saman-seo' ),
			code: `RewriteEngine On
RewriteCond %{HTTP_USER_AGENT} (AhrefsBot|MJ12bot|SemrushBot|DotBot) [NC]
RewriteRule .* - [F,L]`,
			description: __( 'Block common SEO crawlers/bots', 'saman-seo' ),
		},
		{
			name: __( 'Enable GZIP Compression', 'saman-seo' ),
			code: `<IfModule mod_deflate.c>
  AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css
  AddOutputFilterByType DEFLATE application/javascript application/x-javascript
  AddOutputFilterByType DEFLATE application/json application/xml
</IfModule>`,
			description: __(
				'Compress text-based files for faster loading',
				'saman-seo'
			),
		},
		{
			name: __( 'Browser Caching', 'saman-seo' ),
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
			description: __(
				'Set browser caching for static files',
				'saman-seo'
			),
		},
		{
			name: __( 'Security Headers', 'saman-seo' ),
			code: `<IfModule mod_headers.c>
  Header set X-Content-Type-Options "nosniff"
  Header set X-Frame-Options "SAMEORIGIN"
  Header set X-XSS-Protection "1; mode=block"
  Header set Referrer-Policy "strict-origin-when-cross-origin"
</IfModule>`,
			description: __( 'Add security-related HTTP headers', 'saman-seo' ),
		},
	];

	// Fetch current content
	useEffect( () => {
		const fetchContent = async () => {
			try {
				const response = await apiFetch( {
					path: '/saman-seo/v1/htaccess',
				} );
				if ( response.success ) {
					setContent( response.data.content || '' );
					setOriginalContent( response.data.content || '' );
					setBackups( response.data.backups || [] );
				}
			} catch ( err ) {
				setError(
					__( 'Failed to load .htaccess file:', 'saman-seo' ) +
						( err.message || __( 'Unknown error', 'saman-seo' ) )
				);
			} finally {
				setLoading( false );
			}
		};
		fetchContent();
	}, [] );

	// Track changes
	useEffect( () => {
		setHasChanges( content !== originalContent );
	}, [ content, originalContent ] );

	// Save content
	const handleSave = useCallback( async () => {
		setSaving( true );
		setError( null );
		setSuccess( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/htaccess',
				method: 'POST',
				data: {
					content,
				},
			} );
			if ( response.success ) {
				setOriginalContent( content );
				setSuccess( 'File saved successfully! A backup was created.' );
				if ( response.data.backups ) {
					setBackups( response.data.backups );
				}
			} else {
				setError(
					response.message || __( 'Failed to save file', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError( err.message || __( 'Failed to save file', 'saman-seo' ) );
		} finally {
			setSaving( false );
		}
	}, [ content ] );

	// Restore backup
	const handleRestore = useCallback( async ( backup ) => {
		if (
			! window.confirm(
				sprintf(
					/* translators: %s: placeholder */ __(
						'Restore backup from %s? This will overwrite the current .htaccess file.',
						'saman-seo'
					),
					backup.date
				)
			)
		) {
			return;
		}
		setSaving( true );
		setError( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/htaccess/restore',
				method: 'POST',
				data: {
					backup: backup.file,
				},
			} );
			if ( response.success ) {
				setContent( response.data.content );
				setOriginalContent( response.data.content );
				setSuccess( 'Backup restored successfully!' );
				setShowBackups( false );
			} else {
				setError(
					response.message ||
						__( 'Failed to restore backup', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to restore backup', 'saman-seo' )
			);
		} finally {
			setSaving( false );
		}
	}, [] );

	// Insert preset
	const insertPreset = useCallback(
		( preset ) => {
			const newContent = content.trim()
				? content + '\n\n# ' + preset.name + '\n' + preset.code
				: '# ' + preset.name + '\n' + preset.code;
			setContent( newContent );
		},
		[ content ]
	);

	// Reset to original
	const handleReset = useCallback( () => {
		if ( window.confirm( __( 'Discard all changes?', 'saman-seo' ) ) ) {
			setContent( originalContent );
		}
	}, [ originalContent ] );
	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<h1>{ __( '.htaccess Editor', 'saman-seo' ) }</h1>
				</div>
				<div className="card">
					<div className="loading-state">
						{ __( 'Loading\u2026', 'saman-seo' ) }
					</div>
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( '.htaccess Editor', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Edit your server configuration file. Changes take effect immediately.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header-actions">
					<button
						type="button"
						className="button ghost"
						onClick={ () => setShowBackups( ! showBackups ) }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="16"
							height="16"
						>
							<path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8" />
							<path d="M3 3v5h5" />
						</svg>
						{ __( 'Backups (', 'saman-seo' ) }
						{ backups.length })
					</button>
					{ hasChanges && (
						<button
							type="button"
							className="button ghost"
							onClick={ handleReset }
						>
							{ __( 'Discard Changes', 'saman-seo' ) }
						</button>
					) }
					<button
						type="button"
						className="button primary"
						onClick={ handleSave }
						disabled={ saving || ! hasChanges }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save Changes', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ /* Warning */ }
			<div className="alert-banner alert-banner--warning">
				<svg
					viewBox="0 0 24 24"
					fill="none"
					stroke="currentColor"
					strokeWidth="2"
					width="20"
					height="20"
				>
					<path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" />
					<line x1="12" y1="9" x2="12" y2="13" />
					<line x1="12" y1="17" x2="12.01" y2="17" />
				</svg>
				<span>
					<strong>{ __( 'Caution:', 'saman-seo' ) }</strong>{ ' ' }
					{ __(
						'Incorrect .htaccess rules can break your site. A backup is created before each save.',
						'saman-seo'
					) }
				</span>
			</div>

			{ /* Messages */ }
			{ error && (
				<div className="alert-banner alert-banner--error">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						width="20"
						height="20"
					>
						<circle cx="12" cy="12" r="10" />
						<path d="M12 8v4m0 4h.01" />
					</svg>
					<span>{ error }</span>
				</div>
			) }
			{ success && (
				<div className="alert-banner alert-banner--success">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						width="20"
						height="20"
					>
						<path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
						<path d="M22 4L12 14.01l-3-3" />
					</svg>
					<span>{ success }</span>
				</div>
			) }

			<div className="htaccess-layout">
				{ /* Editor */ }
				<div className="card htaccess-editor-card">
					<div className="htaccess-editor-header">
						<span className="htaccess-editor-path">
							{ __( '/.htaccess', 'saman-seo' ) }
						</span>
						{ hasChanges && (
							<span className="htaccess-editor-modified">
								{ __( 'Modified', 'saman-seo' ) }
							</span>
						) }
					</div>
					<textarea
						className="htaccess-textarea"
						value={ content }
						onChange={ ( e ) => setContent( e.target.value ) }
						spellCheck="false"
						placeholder={ __(
							'# Your .htaccess rules here\u2026',
							'saman-seo'
						) }
					/>
				</div>

				{ /* Sidebar */ }
				<div className="htaccess-sidebar">
					{ /* Backups Panel */ }
					{ showBackups && (
						<div className="card htaccess-backups">
							<h3>{ __( 'Backups', 'saman-seo' ) }</h3>
							{ backups.length === 0 ? (
								<p className="htaccess-backups__empty">
									{ __( 'No backups yet', 'saman-seo' ) }
								</p>
							) : (
								<ul className="htaccess-backups__list">
									{ backups.map( ( backup ) => (
										<li
											key={ backup.file }
											className="htaccess-backup"
										>
											<div className="htaccess-backup__info">
												<span className="htaccess-backup__date">
													{ backup.date }
												</span>
												<span className="htaccess-backup__size">
													{ backup.size }
												</span>
											</div>
											<button
												type="button"
												className="button ghost small"
												onClick={ () =>
													handleRestore( backup )
												}
											>
												{ __( 'Restore', 'saman-seo' ) }
											</button>
										</li>
									) ) }
								</ul>
							) }
						</div>
					) }

					{ /* Presets */ }
					<div className="card htaccess-presets">
						<h3>{ __( 'Quick Insert', 'saman-seo' ) }</h3>
						<p className="htaccess-presets__desc">
							{ __( 'Click to add common rules', 'saman-seo' ) }
						</p>
						<ul className="htaccess-presets__list">
							{ presets.map( ( preset ) => (
								<li key={ preset.name }>
									<button
										type="button"
										className="htaccess-preset"
										onClick={ () => insertPreset( preset ) }
										title={ preset.description }
									>
										<span className="htaccess-preset__name">
											{ preset.name }
										</span>
										<span className="htaccess-preset__desc">
											{ preset.description }
										</span>
									</button>
								</li>
							) ) }
						</ul>
					</div>
				</div>
			</div>
		</div>
	);
};
export default HtaccessEditor;
