/**
 * robots.txt Editor Page
 *
 * Visual editor for managing robots.txt with presets and validation.
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Common robots.txt presets
import { __, sprintf } from '@wordpress/i18n';
const presets = [
	{
		id: 'default',
		name: __( 'Default (WordPress)', 'saman-seo' ),
		description: __(
			'Standard WordPress robots.txt with sitemap',
			'saman-seo'
		),
		content: `User-agent: *
Disallow: /wp-admin/
Allow: /wp-admin/admin-ajax.php

Sitemap: {{sitemap_url}}`,
	},
	{
		id: 'block-all',
		name: __( 'Block All Robots', 'saman-seo' ),
		description: __(
			'Prevent all search engines from crawling',
			'saman-seo'
		),
		content: `User-agent: *
Disallow: /`,
	},
	{
		id: 'allow-all',
		name: __( 'Allow All', 'saman-seo' ),
		description: __(
			'Allow all search engines to crawl everything',
			'saman-seo'
		),
		content: `User-agent: *
Disallow:

Sitemap: {{sitemap_url}}`,
	},
	{
		id: 'seo-optimized',
		name: __( 'SEO Optimized', 'saman-seo' ),
		description: __(
			'Block unnecessary paths, allow important ones',
			'saman-seo'
		),
		content: `User-agent: *
Disallow: /wp-admin/
Disallow: /wp-content/cache/
Disallow: /xmlrpc.php
Disallow: /?s=
Disallow: /search/
Disallow: /author/
Disallow: /trackback/
Disallow: /feed/
Disallow: */feed/
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

Sitemap: {{sitemap_url}}`,
	},
	{
		id: 'ecommerce',
		name: __( 'E-Commerce', 'saman-seo' ),
		description: __(
			'Optimized for WooCommerce and online stores',
			'saman-seo'
		),
		content: `User-agent: *
Disallow: /wp-admin/
Disallow: /cart/
Disallow: /checkout/
Disallow: /my-account/
Disallow: /?add-to-cart=
Disallow: /*?orderby=
Disallow: /*?filter_
Allow: /wp-admin/admin-ajax.php
Allow: /wp-content/uploads/

Sitemap: {{sitemap_url}}`,
	},
];

// Common directives for quick insertion
const directives = [
	{
		label: __( 'Disallow path', 'saman-seo' ),
		value: 'Disallow: /',
	},
	{
		label: __( 'Allow path', 'saman-seo' ),
		value: 'Allow: /',
	},
	{
		label: __( 'User-agent (all)', 'saman-seo' ),
		value: 'User-agent: *',
	},
	{
		label: __( 'User-agent (Googlebot)', 'saman-seo' ),
		value: 'User-agent: Googlebot',
	},
	{
		label: __( 'User-agent (Bingbot)', 'saman-seo' ),
		value: 'User-agent: Bingbot',
	},
	{
		label: __( 'Crawl-delay', 'saman-seo' ),
		value: 'Crawl-delay: 10',
		description: __(
			'Googlebot ignores Crawl-delay; only some other crawlers, such as Bingbot, honor it.',
			'saman-seo'
		),
	},
	{
		label: __( 'Sitemap', 'saman-seo' ),
		value: 'Sitemap: ',
	},
];
// AI crawlers. "training" bots collect content for model training,
// "search" bots power AI search answers and citations, "user" agents fetch a
// page when a person asks an assistant about it.
const aiCrawlers = [
	{
		token: 'GPTBot',
		owner: 'OpenAI',
		purpose: 'training',
		description: __(
			'Collects content to train OpenAI models.',
			'saman-seo'
		),
	},
	{
		token: 'OAI-SearchBot',
		owner: 'OpenAI',
		purpose: 'search',
		description: __(
			'Indexes pages for ChatGPT search. Blocking it removes the site from ChatGPT search answers.',
			'saman-seo'
		),
	},
	{
		token: 'ChatGPT-User',
		owner: 'OpenAI',
		purpose: 'user',
		description: __(
			'Fetches a page when a ChatGPT user asks about it. User-initiated, so robots.txt may not apply.',
			'saman-seo'
		),
	},
	{
		token: 'ClaudeBot',
		owner: 'Anthropic',
		purpose: 'training',
		description: __(
			'Collects content to train Anthropic models.',
			'saman-seo'
		),
	},
	{
		token: 'Claude-SearchBot',
		owner: 'Anthropic',
		purpose: 'search',
		description: __(
			'Indexes pages for Claude search results. Blocking it removes the site from those answers.',
			'saman-seo'
		),
	},
	{
		token: 'Claude-User',
		owner: 'Anthropic',
		purpose: 'user',
		description: __(
			'Fetches a page when a Claude user asks about it.',
			'saman-seo'
		),
	},
	{
		token: 'PerplexityBot',
		owner: 'Perplexity',
		purpose: 'search',
		description: __(
			'Indexes pages for Perplexity answers. Blocking it removes the site from Perplexity search.',
			'saman-seo'
		),
	},
	{
		token: 'CCBot',
		owner: 'Common Crawl',
		purpose: 'training',
		description: __(
			'Builds the open Common Crawl dataset, widely used to train AI models.',
			'saman-seo'
		),
	},
	{
		token: 'Applebot-Extended',
		owner: 'Apple',
		purpose: 'training',
		description: __(
			'Controls use of content to train Apple AI models. Does not affect Applebot, Siri or Spotlight.',
			'saman-seo'
		),
	},
	{
		token: 'Google-Extended',
		owner: 'Google',
		purpose: 'training',
		description: __(
			'Controls use of content for Gemini training and grounding. Does not affect Google Search, AI Overviews or AI Mode.',
			'saman-seo'
		),
	},
	{
		token: 'meta-externalagent',
		owner: 'Meta',
		purpose: 'training',
		description: __(
			'Collects content to train Meta AI models.',
			'saman-seo'
		),
	},
	{
		token: 'Bytespider',
		owner: 'ByteDance',
		purpose: 'training',
		description: __(
			'Collects content for ByteDance AI models.',
			'saman-seo'
		),
	},
];

const aiPurposeLabels = {
	training: __( 'AI training', 'saman-seo' ),
	search: __( 'AI search', 'saman-seo' ),
	user: __( 'User fetch', 'saman-seo' ),
};

/**
 * Whether robots.txt content already has a User-agent line for a token.
 *
 * @param {string} text  robots.txt content.
 * @param {string} token User-agent token.
 * @return {boolean} True when present.
 */
const contentHasUserAgent = ( text, token ) => {
	const escaped = token.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	return new RegExp( `^\\s*user-agent\\s*:\\s*${ escaped }\\s*$`, 'im' ).test(
		text
	);
};

const RobotsTxt = () => {
	const [ content, setContent ] = useState( '' );
	const [ originalContent, setOriginalContent ] = useState( '' );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ testing, setTesting ] = useState( false );
	const [ testUrl, setTestUrl ] = useState( '/' );
	const [ testResult, setTestResult ] = useState( null );
	const [ validation, setValidation ] = useState( {
		valid: true,
		errors: [],
		warnings: [],
	} );
	const [ siteUrl, setSiteUrl ] = useState( '' );
	const [ sitemapUrl, setSitemapUrl ] = useState( '' );
	const [ activePreset, setActivePreset ] = useState( null );
	const [ selectedBots, setSelectedBots ] = useState( [] );
	useEffect( () => {
		loadRobotsTxt();
	}, [] );
	useEffect( () => {
		validateContent( content );
	}, [ content ] );
	const loadRobotsTxt = async () => {
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/robots-txt',
			} );
			setContent( response.content || '' );
			setOriginalContent( response.content || '' );
			setSiteUrl( response.site_url || '' );
			setSitemapUrl( response.sitemap_url || '' );
		} catch ( error ) {
			console.error( 'Error loading robots.txt:', error );
		} finally {
			setLoading( false );
		}
	};
	const saveRobotsTxt = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/tools/robots-txt',
				method: 'POST',
				data: {
					content,
				},
			} );
			setOriginalContent( content );
		} catch ( error ) {
			console.error( 'Error saving robots.txt:', error );
		} finally {
			setSaving( false );
		}
	};
	const resetToDefault = async () => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/robots-txt/reset',
				method: 'POST',
			} );
			setContent( response.content || '' );
			setOriginalContent( response.content || '' );
			setActivePreset( null );
		} catch ( error ) {
			console.error( 'Error resetting robots.txt:', error );
		} finally {
			setSaving( false );
		}
	};
	const applyPreset = ( preset ) => {
		const processed = preset.content.replace(
			/\{\{sitemap_url\}\}/g,
			sitemapUrl
		);
		setContent( processed );
		setActivePreset( preset.id );
	};
	const insertDirective = ( directive ) => {
		setContent( ( prev ) => prev + '\n' + directive );
	};
	const toggleBot = ( token ) => {
		setSelectedBots( ( prev ) =>
			prev.includes( token )
				? prev.filter( ( t ) => t !== token )
				: [ ...prev, token ]
		);
	};
	const selectTrainingBots = () => {
		setSelectedBots(
			aiCrawlers
				.filter(
					( bot ) =>
						bot.purpose === 'training' &&
						! contentHasUserAgent( content, bot.token )
				)
				.map( ( bot ) => bot.token )
		);
	};
	// Append one group that disallows every selected crawler. This only edits
	// the editor content; nothing changes until the user saves.
	const insertAiBlock = () => {
		const tokens = aiCrawlers
			.map( ( bot ) => bot.token )
			.filter(
				( token ) =>
					selectedBots.includes( token ) &&
					! contentHasUserAgent( content, token )
			);
		if ( ! tokens.length ) {
			return;
		}
		const block = [
			'# AI crawlers',
			...tokens.map( ( token ) => `User-agent: ${ token }` ),
			'Disallow: /',
		].join( '\n' );
		setContent( ( prev ) =>
			prev.trim()
				? `${ prev.replace( /\s+$/, '' ) }\n\n${ block }\n`
				: `${ block }\n`
		);
		setSelectedBots( [] );
	};
	const testPath = async () => {
		setTesting( true );
		setTestResult( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/robots-txt/test',
				method: 'POST',
				data: {
					path: testUrl,
					content,
				},
			} );
			setTestResult( response );
		} catch ( error ) {
			console.error( 'Error testing path:', error );
			setTestResult( {
				allowed: false,
				error: error.message,
			} );
		} finally {
			setTesting( false );
		}
	};
	const validateContent = ( text ) => {
		const errors = [];
		const warnings = [];
		const lines = text.split( '\n' );
		let hasUserAgent = false;
		let lastUserAgent = null;
		lines.forEach( ( line, index ) => {
			const trimmed = line.trim();
			const lineNum = index + 1;

			// Skip empty lines and comments
			if ( ! trimmed || trimmed.startsWith( '#' ) ) {
				return;
			}

			// Check for valid directives
			const validDirectives = [
				'user-agent',
				'disallow',
				'allow',
				'sitemap',
				'crawl-delay',
				'host',
			];
			const directivePart = trimmed.split( ':' )[ 0 ].toLowerCase();
			if ( ! validDirectives.includes( directivePart ) ) {
				warnings.push(
					sprintf(
						/* translators: %1$s: placeholder, %2$s: placeholder */ __(
							'Line %1$s: Unknown directive "%2$s"',
							'saman-seo'
						),
						lineNum,
						directivePart
					)
				);
			}

			// User-agent tracking
			if ( directivePart === 'user-agent' ) {
				hasUserAgent = true;
				lastUserAgent = trimmed.split( ':' )[ 1 ]?.trim();
			}

			// Check if Disallow/Allow comes before User-agent
			if (
				( directivePart === 'disallow' || directivePart === 'allow' ) &&
				! hasUserAgent
			) {
				errors.push(
					sprintf(
						/* translators: %1$s: placeholder, %2$s: placeholder */ __(
							'Line %1$s: %2$s must come after User-agent',
							'saman-seo'
						),
						lineNum,
						directivePart
					)
				);
			}

			// Check for trailing spaces
			if ( line !== trimmed && line.endsWith( ' ' ) ) {
				warnings.push(
					sprintf(
						/* translators: %s: placeholder */ __(
							'Line %s: Trailing spaces detected',
							'saman-seo'
						),
						lineNum
					)
				);
			}
		} );
		if ( ! hasUserAgent && text.trim().length > 0 ) {
			errors.push( __( 'Missing User-agent directive', 'saman-seo' ) );
		}
		setValidation( {
			valid: errors.length === 0,
			errors,
			warnings,
		} );
	};
	const hasChanges = content !== originalContent;
	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<h1>{ __( 'robots.txt Editor', 'saman-seo' ) }</h1>
				</div>
				<div className="loading-spinner">
					{ __( 'Loading\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'robots.txt Editor', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Control how search engines crawl your site. Changes are saved to the database and override the default WordPress robots.txt.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header__actions">
					<button
						type="button"
						className="btn btn--secondary"
						onClick={ resetToDefault }
						disabled={ saving }
					>
						{ __( 'Reset to Default', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="btn btn--primary"
						onClick={ saveRobotsTxt }
						disabled={ saving || ! hasChanges }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save Changes', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<div className="robots-editor">
				{ /* Presets Section */ }
				<div className="robots-editor__presets card">
					<h3>{ __( 'Quick Presets', 'saman-seo' ) }</h3>
					<p className="text-muted">
						{ __(
							'Apply a preset configuration to get started quickly.',
							'saman-seo'
						) }
					</p>
					<div className="presets-grid">
						{ presets.map( ( preset ) => (
							<button
								key={ preset.id }
								type="button"
								className={ `preset-card ${
									activePreset === preset.id
										? 'preset-card--active'
										: ''
								}` }
								onClick={ () => applyPreset( preset ) }
							>
								<strong>{ preset.name }</strong>
								<span>{ preset.description }</span>
							</button>
						) ) }
					</div>
				</div>

				{ /* AI crawlers */ }
				<div className="robots-editor__ai card">
					<h3>{ __( 'AI crawlers', 'saman-seo' ) }</h3>
					<p className="text-muted">
						{ __(
							'Select AI crawlers to add a Disallow rule for them to the editor below. Nothing changes until you save.',
							'saman-seo'
						) }
					</p>
					<ul className="ai-notes">
						<li>
							{ __(
								'Google-Extended does not affect Google Search, AI Overviews or AI Mode. To limit how pages appear there, use the nosnippet / max-snippet settings in the post editor (Advanced tab).',
								'saman-seo'
							) }
						</li>
						<li>
							{ __(
								'Blocking AI search crawlers (OAI-SearchBot, Claude-SearchBot, PerplexityBot) removes the site from those AI search products and their citations. Training crawlers can be blocked without that effect.',
								'saman-seo'
							) }
						</li>
						<li>
							{ __(
								'robots.txt is a request, not access control: each crawler decides whether to honor it.',
								'saman-seo'
							) }
						</li>
					</ul>
					<div className="ai-actions">
						<button
							type="button"
							className="btn btn--secondary"
							onClick={ selectTrainingBots }
						>
							{ __( 'Select AI training crawlers', 'saman-seo' ) }
						</button>
						<button
							type="button"
							className="btn btn--primary"
							onClick={ insertAiBlock }
							disabled={ ! selectedBots.length }
						>
							{ sprintf(
								/* translators: %d: number of selected crawlers */ __(
									'Add Disallow rules (%d)',
									'saman-seo'
								),
								selectedBots.length
							) }
						</button>
					</div>
					<div className="ai-grid">
						{ aiCrawlers.map( ( bot ) => {
							const present = contentHasUserAgent(
								content,
								bot.token
							);
							const inputId = `saman-seo-ai-bot-${ bot.token }`;
							return (
								<div
									key={ bot.token }
									className={ `ai-bot ${
										present ? 'ai-bot--present' : ''
									}` }
								>
									<input
										id={ inputId }
										type="checkbox"
										checked={
											present ||
											selectedBots.includes( bot.token )
										}
										disabled={ present }
										aria-describedby={ `${ inputId }-desc` }
										onChange={ () =>
											toggleBot( bot.token )
										}
									/>
									<span className="ai-bot__body">
										<span className="ai-bot__head">
											<label htmlFor={ inputId }>
												{ bot.token }
											</label>
											<span
												className={ `ai-bot__tag ai-bot__tag--${ bot.purpose }` }
											>
												{
													aiPurposeLabels[
														bot.purpose
													]
												}
											</span>
										</span>
										<span
											id={ `${ inputId }-desc` }
											className="ai-bot__desc"
										>
											{ bot.owner }: { bot.description }
											{ present &&
												` ${ __(
													'(already in robots.txt)',
													'saman-seo'
												) }` }
										</span>
									</span>
								</div>
							);
						} ) }
					</div>
				</div>

				<div className="robots-editor__main">
					{ /* Editor */ }
					<div className="robots-editor__editor card">
						<div className="editor-header">
							<h3>{ __( 'robots.txt Content', 'saman-seo' ) }</h3>
							<a
								href={ `${ siteUrl }/robots.txt` }
								target="_blank"
								rel="noopener noreferrer"
								className="btn btn--text"
							>
								{ __( 'View Live robots.txt', 'saman-seo' ) }
								<svg
									width="14"
									height="14"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
								>
									<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6M15 3h6v6M10 14L21 3" />
								</svg>
							</a>
						</div>
						<textarea
							className="robots-textarea"
							value={ content }
							onChange={ ( e ) => setContent( e.target.value ) }
							placeholder={ `${ __(
								'# robots.txt file',
								'saman-seo'
							) }\nUser-agent: *\nDisallow:` }
							spellCheck={ false }
						/>

						{ /* Quick Insert */ }
						<div className="quick-insert">
							<span className="quick-insert__label">
								{ __( 'Insert:', 'saman-seo' ) }
							</span>
							{ directives.map( ( d, i ) => (
								<button
									key={ i }
									type="button"
									className="quick-insert__btn"
									onClick={ () => insertDirective( d.value ) }
									title={ d.description }
								>
									{ d.label }
								</button>
							) ) }
						</div>
					</div>

					{ /* Sidebar */ }
					<div className="robots-editor__sidebar">
						{ /* Validation */ }
						<div className="card">
							<h3>
								<svg
									className="icon-inline"
									width="18"
									height="18"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
								>
									{ validation.valid ? (
										<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
									) : (
										<path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
									) }
								</svg>
								{ __( 'Validation', 'saman-seo' ) }
							</h3>
							{ validation.valid &&
							validation.warnings.length === 0 ? (
								<p className="validation-success">
									{ __( 'No issues found', 'saman-seo' ) }
								</p>
							) : (
								<div className="validation-issues">
									{ validation.errors.map( ( err, i ) => (
										<div
											key={ `e${ i }` }
											className="validation-issue validation-issue--error"
										>
											<svg
												width="14"
												height="14"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="2"
											>
												<circle
													cx="12"
													cy="12"
													r="10"
												/>
												<path d="M15 9l-6 6M9 9l6 6" />
											</svg>
											{ err }
										</div>
									) ) }
									{ validation.warnings.map( ( warn, i ) => (
										<div
											key={ `w${ i }` }
											className="validation-issue validation-issue--warning"
										>
											<svg
												width="14"
												height="14"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="2"
											>
												<path d="M12 9v2m0 4h.01" />
												<circle
													cx="12"
													cy="12"
													r="10"
												/>
											</svg>
											{ warn }
										</div>
									) ) }
								</div>
							) }
						</div>

						{ /* Path Tester */ }
						<div className="card">
							<h3>{ __( 'Test Path', 'saman-seo' ) }</h3>
							<p className="text-muted">
								{ __(
									'Check if a URL path is allowed or blocked.',
									'saman-seo'
								) }
							</p>
							<div className="path-tester">
								<input
									type="text"
									value={ testUrl }
									onChange={ ( e ) =>
										setTestUrl( e.target.value )
									}
									placeholder={ __(
										'/example-path/',
										'saman-seo'
									) }
								/>
								<button
									type="button"
									className="btn btn--secondary"
									onClick={ testPath }
									disabled={ testing }
								>
									{ testing
										? __( 'Testing\u2026', 'saman-seo' )
										: __( 'Test', 'saman-seo' ) }
								</button>
							</div>
							{ testResult && (
								<div
									className={ `test-result ${
										testResult.allowed
											? 'test-result--allowed'
											: 'test-result--blocked'
									}` }
								>
									{ testResult.allowed ? (
										<>
											<svg
												width="18"
												height="18"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="2"
											>
												<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
											</svg>
											{ __( 'Allowed', 'saman-seo' ) }
										</>
									) : (
										<>
											<svg
												width="18"
												height="18"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="2"
											>
												<circle
													cx="12"
													cy="12"
													r="10"
												/>
												<path d="M15 9l-6 6M9 9l6 6" />
											</svg>
											{ __( 'Blocked', 'saman-seo' ) }
										</>
									) }
									{ testResult.rule && (
										<span className="test-result__rule">
											{ __( 'by:', 'saman-seo' ) }{ ' ' }
											{ testResult.rule }
										</span>
									) }
								</div>
							) }
						</div>

						{ /* Help */ }
						<div className="card">
							<h3>{ __( 'Reference', 'saman-seo' ) }</h3>
							<dl className="reference-list">
								<dt>{ __( 'User-agent: *', 'saman-seo' ) }</dt>
								<dd>
									{ __(
										'Apply rules to all crawlers',
										'saman-seo'
									) }
								</dd>
								<dt>
									{ __( 'Disallow: /path/', 'saman-seo' ) }
								</dt>
								<dd>
									{ __(
										'Block crawling of path',
										'saman-seo'
									) }
								</dd>
								<dt>{ __( 'Allow: /path/', 'saman-seo' ) }</dt>
								<dd>
									{ __(
										'Override Disallow for path',
										'saman-seo'
									) }
								</dd>
								<dt>{ __( 'Sitemap: url', 'saman-seo' ) }</dt>
								<dd>
									{ __(
										'Point to XML sitemap',
										'saman-seo'
									) }
								</dd>
								<dt>
									{ __( 'Crawl-delay: 10', 'saman-seo' ) }
								</dt>
								<dd>
									{ __(
										'Seconds between requests. Googlebot ignores this directive.',
										'saman-seo'
									) }
								</dd>
							</dl>
						</div>
					</div>
				</div>
			</div>

			<style>{ `
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
                .robots-editor__ai {
                    margin-bottom: 24px;
                }
                .robots-editor__ai h3 {
                    margin: 0 0 4px;
                }
                .ai-notes {
                    margin: 12px 0;
                    padding-left: 18px;
                    list-style: disc;
                    color: #50575e;
                    font-size: 12px;
                }
                .ai-notes li {
                    margin-bottom: 4px;
                }
                .ai-actions {
                    display: flex;
                    flex-wrap: wrap;
                    gap: 8px;
                    margin-bottom: 12px;
                }
                .ai-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
                    gap: 8px;
                }
                .ai-bot {
                    display: flex;
                    align-items: flex-start;
                    gap: 8px;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    background: #fff;
                }
                .ai-bot input {
                    margin-top: 2px;
                }
                .ai-bot--present {
                    background: #f6f7f7;
                    cursor: default;
                }
                .ai-bot__body {
                    display: flex;
                    flex-direction: column;
                    gap: 4px;
                    min-width: 0;
                }
                .ai-bot__head {
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    flex-wrap: wrap;
                }
                .ai-bot__head label {
                    font-family: monospace;
                    font-size: 12px;
                    font-weight: 600;
                    cursor: pointer;
                }
                .ai-bot__tag {
                    font-size: 11px;
                    padding: 1px 6px;
                    border-radius: 3px;
                    background: #f0f0f1;
                    color: #50575e;
                }
                .ai-bot__tag--search {
                    background: #fcf9e8;
                    color: #996800;
                }
                .ai-bot__desc {
                    font-size: 12px;
                    color: #646970;
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
            ` }</style>
		</div>
	);
};
export default RobotsTxt;
