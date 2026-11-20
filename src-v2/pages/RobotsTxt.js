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
		name: __( 'Block all robots', 'saman-seo' ),
		description: __(
			'Prevent all search engines from crawling',
			'saman-seo'
		),
		content: `User-agent: *
Disallow: /`,
	},
	{
		id: 'allow-all',
		name: __( 'Allow all', 'saman-seo' ),
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
		name: __( 'SEO optimized', 'saman-seo' ),
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
			// The endpoint wraps its payload in { success, data }.
			const payload = response?.data || response || {};
			setContent( payload.content || '' );
			setOriginalContent( payload.content || '' );
			setSiteUrl( payload.site_url || '' );
			setSitemapUrl( payload.sitemap_url || '' );
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
			const payload = response?.data || response || {};
			setContent( payload.content || '' );
			setOriginalContent( payload.content || '' );
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
			<div className="page robots-page">
				<div className="page-header">
					<div>
						<h1>{ __( 'robots.txt editor', 'saman-seo' ) }</h1>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page robots-page">
			<div className="page-header">
				<div>
					<h1>{ __( 'robots.txt editor', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Control how search engines crawl your site. Changes are saved to the database and override the default WordPress robots.txt.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<a
						href={ `${ siteUrl }/robots.txt` }
						target="_blank"
						rel="noopener noreferrer"
						className="button ghost"
					>
						{ __( 'View live robots.txt', 'saman-seo' ) }
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
					<button
						type="button"
						className="button"
						onClick={ resetToDefault }
						disabled={ saving }
					>
						{ __( 'Reset to default', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button primary"
						onClick={ saveRobotsTxt }
						disabled={ saving || ! hasChanges }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save changes', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<div className="page-body two-column">
				<div className="main-column">
					{ /* Editor */ }
					<section className="panel code-panel">
						<div className="code-panel__head">
							<h3>{ __( 'robots.txt content', 'saman-seo' ) }</h3>
							{ hasChanges && (
								<span className="pill warning">
									{ __( 'Modified', 'saman-seo' ) }
								</span>
							) }
						</div>
						<textarea
							className="code-editor"
							aria-label={ __(
								'robots.txt content',
								'saman-seo'
							) }
							value={ content }
							onChange={ ( e ) => setContent( e.target.value ) }
							placeholder={ `${ __(
								'# robots.txt file',
								'saman-seo'
							) }\nUser-agent: *\nDisallow:` }
							spellCheck={ false }
						/>

						{ /* Quick Insert */ }
						<div className="code-panel__foot">
							<span className="code-panel__label">
								{ __( 'Insert:', 'saman-seo' ) }
							</span>
							{ directives.map( ( d, i ) => (
								<button
									key={ i }
									type="button"
									className="button small"
									onClick={ () => insertDirective( d.value ) }
									title={ d.description }
								>
									{ d.label }
								</button>
							) ) }
						</div>
					</section>

					{ /* Presets Section */ }
					<section className="improvements-group">
						<h2 className="improvements-group__title">
							{ __( 'Quick presets', 'saman-seo' ) }
						</h2>
						<p className="group-note">
							{ __(
								'Apply a preset configuration to get started quickly.',
								'saman-seo'
							) }
						</p>
						<ul className="improvements-list">
							{ presets.map( ( preset ) => (
								<li
									key={ preset.id }
									className="improvement-row"
								>
									<div className="improvement-row__main">
										<div className="improvement-row__title">
											<h3>{ preset.name }</h3>
											{ activePreset === preset.id && (
												<span className="pill success">
													{ __(
														'Applied',
														'saman-seo'
													) }
												</span>
											) }
										</div>
										<p className="improvement-row__desc">
											{ preset.description }
										</p>
									</div>
									<button
										type="button"
										className="button small"
										onClick={ () => applyPreset( preset ) }
									>
										{ __( 'Apply', 'saman-seo' ) }
									</button>
								</li>
							) ) }
						</ul>
					</section>

					{ /* AI crawlers */ }
					<section className="panel">
						<h3>{ __( 'AI crawlers', 'saman-seo' ) }</h3>
						<p className="panel-desc">
							{ __(
								'Select AI crawlers to add a Disallow rule for them to the editor. Nothing changes until you save.',
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
										className={ `ai-bot${
											present ? ' is-present' : ''
										}` }
									>
										<input
											id={ inputId }
											type="checkbox"
											checked={
												present ||
												selectedBots.includes(
													bot.token
												)
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
													className={
														bot.purpose === 'search'
															? 'pill warning'
															: 'pill'
													}
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
												{ bot.owner }:{ ' ' }
												{ bot.description }
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
						<div className="inline-row">
							<button
								type="button"
								className="button"
								onClick={ selectTrainingBots }
							>
								{ __(
									'Select AI training crawlers',
									'saman-seo'
								) }
							</button>
							<button
								type="button"
								className="button primary"
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
					</section>
				</div>

				{ /* Sidebar */ }
				<aside className="side-panel">
					{ /* Validation */ }
					<div className="side-card">
						<div className="side-card__head">
							<h3>{ __( 'Validation', 'saman-seo' ) }</h3>
							{ validation.valid &&
								validation.warnings.length === 0 && (
									<span className="pill success">
										{ __( 'No issues found', 'saman-seo' ) }
									</span>
								) }
						</div>
						{ ( ! validation.valid ||
							validation.warnings.length > 0 ) && (
							<ul className="plain-list plain-list--notes">
								{ validation.errors.map( ( err, i ) => (
									<li key={ `e${ i }` }>
										<span className="pill danger">
											{ __( 'Error', 'saman-seo' ) }
										</span>
										<span>{ err }</span>
									</li>
								) ) }
								{ validation.warnings.map( ( warn, i ) => (
									<li key={ `w${ i }` }>
										<span className="pill warning">
											{ __( 'Warning', 'saman-seo' ) }
										</span>
										<span>{ warn }</span>
									</li>
								) ) }
							</ul>
						) }
					</div>

					{ /* Path Tester */ }
					<div className="side-card">
						<h3>{ __( 'Test path', 'saman-seo' ) }</h3>
						<p className="panel-desc">
							{ __(
								'Check if a URL path is allowed or blocked.',
								'saman-seo'
							) }
						</p>
						<div className="inline-row">
							<input
								type="text"
								aria-label={ __( 'Test path', 'saman-seo' ) }
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
								className="button"
								onClick={ testPath }
								disabled={ testing }
							>
								{ testing
									? __( 'Testing\u2026', 'saman-seo' )
									: __( 'Test', 'saman-seo' ) }
							</button>
						</div>
						{ testResult && (
							<div className="test-result">
								{ testResult.allowed ? (
									<span className="pill success">
										{ __( 'Allowed', 'saman-seo' ) }
									</span>
								) : (
									<span className="pill danger">
										{ __( 'Blocked', 'saman-seo' ) }
									</span>
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
					<div className="side-card">
						<h3>{ __( 'Reference', 'saman-seo' ) }</h3>
						<dl className="reference-list">
							<dt>{ __( 'User-agent: *', 'saman-seo' ) }</dt>
							<dd>
								{ __(
									'Apply rules to all crawlers',
									'saman-seo'
								) }
							</dd>
							<dt>{ __( 'Disallow: /path/', 'saman-seo' ) }</dt>
							<dd>
								{ __( 'Block crawling of path', 'saman-seo' ) }
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
								{ __( 'Point to XML sitemap', 'saman-seo' ) }
							</dd>
							<dt>{ __( 'Crawl-delay: 10', 'saman-seo' ) }</dt>
							<dd>
								{ __(
									'Seconds between requests. Googlebot ignores this directive.',
									'saman-seo'
								) }
							</dd>
						</dl>
					</div>
				</aside>
			</div>
		</div>
	);
};
export default RobotsTxt;
