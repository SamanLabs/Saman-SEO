import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import useUrlTab from '../hooks/useUrlTab';
import { __, sprintf } from '@wordpress/i18n';
const sitemapTabs = [
	{
		id: 'xml-sitemap',
		label: __( 'XML Sitemap', 'saman-seo' ),
	},
	{
		id: 'llm-txt',
		label: __( 'LLM.txt', 'saman-seo' ),
	},
];
const SCHEDULE_OPTIONS = [
	{
		value: '',
		label: __( 'Manual only', 'saman-seo' ),
	},
	{
		value: 'hourly',
		label: __( 'Hourly', 'saman-seo' ),
	},
	{
		value: 'twicedaily',
		label: __( 'Twice Daily', 'saman-seo' ),
	},
	{
		value: 'daily',
		label: __( 'Daily', 'saman-seo' ),
	},
	{
		value: 'weekly',
		label: __( 'Weekly', 'saman-seo' ),
	},
];
const Sitemap = () => {
	const [ activeTab, setActiveTab ] = useUrlTab( {
		tabs: sitemapTabs,
		defaultTab: 'xml-sitemap',
	} );

	// Loading states
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ regenerating, setRegenerating ] = useState( false );

	// Sitemap settings
	const [ settings, setSettings ] = useState( {
		max_urls: 1000,
		enable_index: '1',
		dynamic_generation: '1',
		schedule_updates: '',
		post_types: [],
		taxonomies: [],
		include_author_pages: '0',
		include_date_archives: '0',
		exclude_images: '0',
		enable_rss: '0',
		enable_google_news: '0',
		google_news_name: '',
		google_news_post_types: [],
		additional_pages: [],
		site_url: '',
		sitemap_url: '',
		rss_sitemap_url: '',
		news_sitemap_url: '',
	} );

	// LLM settings
	const [ llmSettings, setLlmSettings ] = useState( {
		enable_llm_txt: '0',
		llm_txt_title: '',
		llm_txt_description: '',
		llm_txt_posts_per_type: 50,
		llm_txt_include_excerpt: '1',
		llm_txt_url: '',
		enable_agents_md: '1',
		agents_md_guidelines: '',
		agents_md_url: '',
	} );

	// Available post types and taxonomies
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ taxonomies, setTaxonomies ] = useState( [] );

	// Stats
	const [ stats, setStats ] = useState( {
		total_urls: 0,
		last_regenerated: null,
	} );

	// Fetch all data
	const fetchData = useCallback( async () => {
		setLoading( true );
		try {
			const [
				settingsRes,
				llmRes,
				postTypesRes,
				taxonomiesRes,
				statsRes,
			] = await Promise.all( [
				apiFetch( {
					path: '/saman-seo/v1/sitemap/settings',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/sitemap/llm-settings',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/sitemap/post-types',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/sitemap/taxonomies',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/sitemap/stats',
				} ),
			] );
			if ( settingsRes.success ) setSettings( settingsRes.data );
			if ( llmRes.success ) setLlmSettings( llmRes.data );
			if ( postTypesRes.success ) setPostTypes( postTypesRes.data );
			if ( taxonomiesRes.success ) setTaxonomies( taxonomiesRes.data );
			if ( statsRes.success ) setStats( statsRes.data );
		} catch ( error ) {
			console.error( 'Failed to fetch sitemap data:', error );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Save sitemap settings
	const handleSaveSettings = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/sitemap/settings',
				method: 'POST',
				data: settings,
			} );
			// Refetch stats after saving
			const statsRes = await apiFetch( {
				path: '/saman-seo/v1/sitemap/stats',
			} );
			if ( statsRes.success ) setStats( statsRes.data );
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save LLM settings
	const handleSaveLlmSettings = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/sitemap/llm-settings',
				method: 'POST',
				data: llmSettings,
			} );
		} catch ( error ) {
			console.error( 'Failed to save LLM settings:', error );
		} finally {
			setSaving( false );
		}
	};

	// Regenerate sitemap
	const handleRegenerate = async () => {
		setRegenerating( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/sitemap/regenerate',
				method: 'POST',
			} );
			if ( res.success ) {
				setStats( ( prev ) => ( {
					...prev,
					last_regenerated: res.data.regenerated_at,
				} ) );
			}
		} catch ( error ) {
			console.error( 'Failed to regenerate sitemap:', error );
		} finally {
			setRegenerating( false );
		}
	};

	// Toggle post type selection
	const togglePostType = ( name ) => {
		setSettings( ( prev ) => {
			const current = Array.isArray( prev.post_types )
				? prev.post_types
				: [];
			const updated = current.includes( name )
				? current.filter( ( pt ) => pt !== name )
				: [ ...current, name ];
			return {
				...prev,
				post_types: updated,
			};
		} );
	};

	// Toggle taxonomy selection
	const toggleTaxonomy = ( name ) => {
		setSettings( ( prev ) => {
			const current = Array.isArray( prev.taxonomies )
				? prev.taxonomies
				: [];
			const updated = current.includes( name )
				? current.filter( ( t ) => t !== name )
				: [ ...current, name ];
			return {
				...prev,
				taxonomies: updated,
			};
		} );
	};

	// Toggle Google News post type
	const toggleNewsPostType = ( name ) => {
		setSettings( ( prev ) => {
			const current = Array.isArray( prev.google_news_post_types )
				? prev.google_news_post_types
				: [];
			const updated = current.includes( name )
				? current.filter( ( pt ) => pt !== name )
				: [ ...current, name ];
			return {
				...prev,
				google_news_post_types: updated,
			};
		} );
	};

	// Add additional page
	const addAdditionalPage = () => {
		setSettings( ( prev ) => ( {
			...prev,
			additional_pages: [
				...( prev.additional_pages || [] ),
				{
					url: '',
					priority: '0.5',
				},
			],
		} ) );
	};

	// Update additional page
	const updateAdditionalPage = ( index, field, value ) => {
		setSettings( ( prev ) => {
			const pages = [ ...( prev.additional_pages || [] ) ];
			pages[ index ] = {
				...pages[ index ],
				[ field ]: value,
			};
			return {
				...prev,
				additional_pages: pages,
			};
		} );
	};

	// Remove additional page
	const removeAdditionalPage = ( index ) => {
		setSettings( ( prev ) => ( {
			...prev,
			additional_pages: ( prev.additional_pages || [] ).filter(
				( _, i ) => i !== index
			),
		} ) );
	};

	// Format date
	const formatDate = ( dateStr ) => {
		if ( ! dateStr ) return __( 'Never', 'saman-seo' );
		const date = new Date( dateStr );
		const now = new Date();
		const diff = now - date;
		const hours = Math.floor( diff / ( 1000 * 60 * 60 ) );
		if ( hours < 1 ) return __( 'Just now', 'saman-seo' );
		if ( hours < 24 )
			return sprintf(
				/* translators: %1$s: placeholder, %2$s: placeholder */ __(
					'%1$s hour%2$s ago',
					'saman-seo'
				),
				hours,
				hours > 1 ? 's' : ''
			);
		return date.toLocaleDateString();
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<div>
						<h1>{ __( 'Sitemap', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'Configure XML sitemap generation and LLM.txt settings.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading sitemap settings\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Sitemap', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Configure XML sitemap generation and LLM.txt settings.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<a
						href={ settings.sitemap_url }
						target="_blank"
						rel="noopener noreferrer"
						className="button ghost"
					>
						{ __( 'View Sitemap', 'saman-seo' ) }
					</a>
					{ llmSettings.enable_llm_txt === '1' && (
						<a
							href={ llmSettings.llm_txt_url }
							target="_blank"
							rel="noopener noreferrer"
							className="button ghost"
						>
							{ __( 'Open llm.txt', 'saman-seo' ) }
						</a>
					) }
				</div>
			</div>

			<SubTabs
				tabs={ sitemapTabs }
				activeTab={ activeTab }
				onChange={ setActiveTab }
				ariaLabel={ __( 'Sitemap sections', 'saman-seo' ) }
			/>

			{ activeTab === 'xml-sitemap' ? (
				<div className="page-body two-column">
					<div className="main-column">
						{ /* XML Sitemap Configuration - Single Panel */ }
						<section className="panel">
							<h3>
								{ __( 'XML Sitemap Settings', 'saman-seo' ) }
							</h3>
							<p className="muted">
								{ __(
									'Configure your sitemap generation, content types, and additional options.',
									'saman-seo'
								) }
							</p>

							<div className="settings-form">
								{ /* General Settings */ }
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Automatic Updates',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Regenerate sitemap on a schedule.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<select
											value={ settings.schedule_updates }
											onChange={ ( e ) =>
												setSettings( ( prev ) => ( {
													...prev,
													schedule_updates:
														e.target.value,
												} ) )
											}
										>
											{ SCHEDULE_OPTIONS.map( ( opt ) => (
												<option
													key={ opt.value }
													value={ opt.value }
												>
													{ opt.label }
												</option>
											) ) }
										</select>
									</div>
								</div>

								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Max URLs Per Page',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Maximum URLs per sitemap page.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<input
											type="number"
											value={ settings.max_urls }
											onChange={ ( e ) =>
												setSettings( ( prev ) => ( {
													...prev,
													max_urls:
														parseInt(
															e.target.value,
															10
														) || 1000,
												} ) )
											}
											min="1"
											max="50000"
											style={ {
												width: '120px',
											} }
										/>
									</div>
								</div>

								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Generation Options',
												'saman-seo'
											) }
										</label>
									</div>
									<div className="settings-control">
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.enable_index ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														enable_index: e.target
															.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Enable sitemap indexes',
													'saman-seo'
												) }
											</span>
										</label>
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.dynamic_generation ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														dynamic_generation: e
															.target.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Dynamic generation on-demand',
													'saman-seo'
												) }
											</span>
										</label>
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.exclude_images ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														exclude_images: e.target
															.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Exclude images from entries',
													'saman-seo'
												) }
											</span>
										</label>
									</div>
								</div>

								{ /* Content Types */ }
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __( 'Post Types', 'saman-seo' ) }
										</label>
										<p className="settings-help">
											{ __(
												'Include in sitemap.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<div className="checkbox-grid">
											{ postTypes.map( ( pt ) => (
												<label
													key={ pt.name }
													className="checkbox-row"
												>
													<input
														type="checkbox"
														checked={ (
															settings.post_types ||
															[]
														).includes( pt.name ) }
														onChange={ () =>
															togglePostType(
																pt.name
															)
														}
													/>
													<span>
														{ pt.label } (
														{ pt.count })
													</span>
												</label>
											) ) }
										</div>
									</div>
								</div>

								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __( 'Taxonomies', 'saman-seo' ) }
										</label>
										<p className="settings-help">
											{ __(
												'Include taxonomy archives.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<div className="checkbox-grid">
											{ taxonomies.map( ( tax ) => (
												<label
													key={ tax.name }
													className="checkbox-row"
												>
													<input
														type="checkbox"
														checked={ (
															settings.taxonomies ||
															[]
														).includes( tax.name ) }
														onChange={ () =>
															toggleTaxonomy(
																tax.name
															)
														}
													/>
													<span>
														{ tax.label } (
														{ tax.count })
													</span>
												</label>
											) ) }
										</div>
									</div>
								</div>

								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Archive Pages',
												'saman-seo'
											) }
										</label>
									</div>
									<div className="settings-control">
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.include_author_pages ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														include_author_pages: e
															.target.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Include author pages',
													'saman-seo'
												) }
											</span>
										</label>
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.include_date_archives ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														include_date_archives: e
															.target.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Include date archives',
													'saman-seo'
												) }
											</span>
										</label>
									</div>
								</div>

								{ /* Additional Sitemaps */ }
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __( 'RSS Sitemap', 'saman-seo' ) }
										</label>
									</div>
									<div className="settings-control">
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.enable_rss === '1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														enable_rss: e.target
															.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Generate RSS sitemap (latest 50 posts)',
													'saman-seo'
												) }
											</span>
										</label>
									</div>
								</div>

								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __( 'Google News', 'saman-seo' ) }
										</label>
									</div>
									<div className="settings-control">
										<label className="checkbox-row">
											<input
												type="checkbox"
												checked={
													settings.enable_google_news ===
													'1'
												}
												onChange={ ( e ) =>
													setSettings( ( prev ) => ( {
														...prev,
														enable_google_news: e
															.target.checked
															? '1'
															: '0',
													} ) )
												}
											/>
											<span>
												{ __(
													'Enable Google News sitemap',
													'saman-seo'
												) }
											</span>
										</label>

										{ __(
											'Publication Name',
											'saman-seo'
										) }
									</div>
								</div>

								{ /* Additional Pages */ }
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Custom Pages',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Add URLs not managed by WordPress.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<div className="additional-pages-list">
											{ __(
												'https://example.com/page',
												'saman-seo'
											) }
										</div>
										<button
											type="button"
											className="button ghost small"
											onClick={ addAdditionalPage }
										>
											{ __( '+ Add Page', 'saman-seo' ) }
										</button>
									</div>
								</div>
							</div>

							{ /* Save Button inside panel */ }
							<div
								className="form-actions"
								style={ {
									marginTop: '24px',
									paddingTop: '24px',
									borderTop: '1px solid #e5e7eb',
								} }
							>
								<button
									type="button"
									className="button primary"
									onClick={ handleSaveSettings }
									disabled={ saving }
								>
									{ saving
										? __( 'Saving\u2026', 'saman-seo' )
										: __( 'Save Changes', 'saman-seo' ) }
								</button>
								<button
									type="button"
									className="button ghost"
									onClick={ handleRegenerate }
									disabled={ regenerating }
								>
									{ regenerating
										? __(
												'Regenerating\u2026',
												'saman-seo'
										  )
										: __( 'Regenerate Now', 'saman-seo' ) }
								</button>
							</div>
						</section>
					</div>

					{ /* Sidebar */ }
					<aside className="side-panel">
						<div className="side-card highlight">
							<h3>{ __( 'Your Sitemaps', 'saman-seo' ) }</h3>
							<div className="sitemap-links">
								<div className="sitemap-link-item">
									<strong>
										{ __( 'Main Index', 'saman-seo' ) }
									</strong>
									<a
										href={ settings.sitemap_url }
										target="_blank"
										rel="noopener noreferrer"
									>
										{ settings.sitemap_url }
									</a>
								</div>
								{ settings.enable_rss === '1' && (
									<div className="sitemap-link-item">
										<strong>
											{ __( 'RSS Feed', 'saman-seo' ) }
										</strong>
										<a
											href={ settings.rss_sitemap_url }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ settings.rss_sitemap_url }
										</a>
									</div>
								) }
								{ settings.enable_google_news === '1' && (
									<div className="sitemap-link-item">
										<strong>
											{ __( 'Google News', 'saman-seo' ) }
										</strong>
										<a
											href={ settings.news_sitemap_url }
											target="_blank"
											rel="noopener noreferrer"
										>
											{ settings.news_sitemap_url }
										</a>
									</div>
								) }
							</div>
						</div>

						<div className="side-card">
							<h3>{ __( 'Statistics', 'saman-seo' ) }</h3>
							<div className="stats-list">
								<div className="stat-item">
									<span className="muted">
										{ __( 'Total URLs', 'saman-seo' ) }
									</span>
									<span className="stat-value">
										{ stats.total_urls.toLocaleString() }
									</span>
								</div>
								<div className="stat-item">
									<span className="muted">
										{ __( 'Last Updated', 'saman-seo' ) }
									</span>
									<span className="stat-value">
										{ formatDate( stats.last_regenerated ) }
									</span>
								</div>
							</div>
						</div>

						<div className="side-card">
							<h3>{ __( 'Pro Tip', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'Submit your sitemap to Google Search Console and Bing Webmaster Tools for faster indexing.',
									'saman-seo'
								) }
							</p>
						</div>
					</aside>
				</div>
			) : (
				<div className="page-body two-column">
					<div className="main-column">
						{ /* LLM.txt - Single Panel */ }
						<section className="panel">
							<h3>
								{ __( 'LLM.txt Configuration', 'saman-seo' ) }
							</h3>
							<p className="muted">
								{ __(
									'Help AI engines discover your content. Similar to XML sitemaps for search engines, llm.txt guides AI crawlers like ChatGPT and Claude.',
									'saman-seo'
								) }{ ' ' }
								<a
									href="https://llmstxt.org/"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Learn more', 'saman-seo' ) }
								</a>
							</p>

							<div className="settings-form">
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Enable llm.txt',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Generate and serve the llm.txt file.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<label className="toggle">
											<input
												type="checkbox"
												checked={
													llmSettings.enable_llm_txt ===
													'1'
												}
												onChange={ ( e ) =>
													setLlmSettings(
														( prev ) => ( {
															...prev,
															enable_llm_txt: e
																.target.checked
																? '1'
																: '0',
														} )
													)
												}
											/>
											<span className="toggle-track" />
											<span className="toggle-text">
												{ llmSettings.enable_llm_txt ===
												'1'
													? __(
															'Enabled',
															'saman-seo'
													  )
													: __(
															'Disabled',
															'saman-seo'
													  ) }
											</span>
										</label>
									</div>
								</div>

								{ __(
									'Defaults to site tagline',
									'saman-seo'
								) }
							</div>

							{ /* Save Button inside panel */ }
							{ llmSettings.enable_llm_txt === '1' && (
								<div
									className="form-actions"
									style={ {
										marginTop: '24px',
										paddingTop: '24px',
										borderTop: '1px solid #e5e7eb',
									} }
								>
									<button
										type="button"
										className="button primary"
										onClick={ handleSaveLlmSettings }
										disabled={ saving }
									>
										{ saving
											? __( 'Saving\u2026', 'saman-seo' )
											: __(
													'Save Changes',
													'saman-seo'
											  ) }
									</button>
									<a
										href={ llmSettings.llm_txt_url }
										target="_blank"
										rel="noopener noreferrer"
										className="button ghost"
									>
										{ __( 'View llm.txt', 'saman-seo' ) }
									</a>
								</div>
							) }
						</section>

						{ /* AGENTS.md - Single Panel */ }
						<section className="panel">
							<h3>
								{ __( 'AGENTS.md Configuration', 'saman-seo' ) }
							</h3>
							<p className="muted">
								{ __(
									'Serve an AGENTS.md file with guidance for AI agents on how to represent your site accurately. It reuses your llm.txt title and description so both files stay consistent.',
									'saman-seo'
								) }{ ' ' }
								<a
									href="https://agents.md/"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Learn more', 'saman-seo' ) }
								</a>
							</p>

							<div className="settings-form">
								<div className="settings-row compact">
									<div className="settings-label">
										<label>
											{ __(
												'Enable AGENTS.md',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Generate and serve the AGENTS.md file.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<label className="toggle">
											<input
												type="checkbox"
												checked={
													llmSettings.enable_agents_md ===
													'1'
												}
												onChange={ ( e ) =>
													setLlmSettings(
														( prev ) => ( {
															...prev,
															enable_agents_md: e
																.target.checked
																? '1'
																: '0',
														} )
													)
												}
											/>
											<span className="toggle-track" />
											<span className="toggle-text">
												{ llmSettings.enable_agents_md ===
												'1'
													? __(
															'Enabled',
															'saman-seo'
													  )
													: __(
															'Disabled',
															'saman-seo'
													  ) }
											</span>
										</label>
									</div>
								</div>

								{ llmSettings.enable_agents_md === '1' && (
									<div className="settings-row compact">
										<div className="settings-label">
											<label htmlFor="agents-md-guidelines">
												{ __(
													'Guidelines for AI agents',
													'saman-seo'
												) }
											</label>
											<p className="settings-help">
												{ __(
													'One guideline per line. Leave empty to use sensible defaults.',
													'saman-seo'
												) }
											</p>
										</div>
										<div className="settings-control">
											<textarea
												id="agents-md-guidelines"
												rows={ 5 }
												value={
													llmSettings.agents_md_guidelines
												}
												onChange={ ( e ) =>
													setLlmSettings(
														( prev ) => ( {
															...prev,
															agents_md_guidelines:
																e.target.value,
														} )
													)
												}
											/>
										</div>
									</div>
								) }
							</div>

							{ /* Save Button inside panel */ }
							{ llmSettings.enable_agents_md === '1' && (
								<div
									className="form-actions"
									style={ {
										marginTop: '24px',
										paddingTop: '24px',
										borderTop: '1px solid #e5e7eb',
									} }
								>
									<button
										type="button"
										className="button primary"
										onClick={ handleSaveLlmSettings }
										disabled={ saving }
									>
										{ saving
											? __( 'Saving\u2026', 'saman-seo' )
											: __(
													'Save Changes',
													'saman-seo'
											  ) }
									</button>
									<a
										href={ llmSettings.agents_md_url }
										target="_blank"
										rel="noopener noreferrer"
										className="button ghost"
									>
										{ __( 'View AGENTS.md', 'saman-seo' ) }
									</a>
								</div>
							) }
						</section>
					</div>

					{ /* Sidebar */ }
					<aside className="side-panel">
						<div className="side-card highlight">
							<h3>{ __( 'Your llm.txt', 'saman-seo' ) }</h3>
							{ llmSettings.enable_llm_txt === '1' ? (
								<>
									<code className="url-display">
										{ llmSettings.llm_txt_url }
									</code>
									<p
										className="muted"
										style={ {
											marginTop: '12px',
											fontSize: '13px',
										} }
									>
										{ __(
											'If not accessible, go to Settings > Permalinks and save to flush rewrite rules.',
											'saman-seo'
										) }
									</p>
								</>
							) : (
								<p className="muted">
									{ __(
										'Enable llm.txt to generate your file.',
										'saman-seo'
									) }
								</p>
							) }
						</div>

						<div className="side-card highlight">
							<h3>{ __( 'Your AGENTS.md', 'saman-seo' ) }</h3>
							{ llmSettings.enable_agents_md === '1' ? (
								<>
									<code className="url-display">
										{ llmSettings.agents_md_url }
									</code>
									<p
										className="muted"
										style={ {
											marginTop: '12px',
											fontSize: '13px',
										} }
									>
										{ __(
											'If not accessible, go to Settings > Permalinks and save to flush rewrite rules.',
											'saman-seo'
										) }
									</p>
								</>
							) : (
								<p className="muted">
									{ __(
										'Enable AGENTS.md to generate your file.',
										'saman-seo'
									) }
								</p>
							) }
						</div>

						<div className="side-card">
							<h3>{ __( 'What is llm.txt?', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'A standardized file that helps AI language models like ChatGPT, Claude, and Gemini discover and understand your content structure.',
									'saman-seo'
								) }
							</p>
							<p
								className="muted"
								style={ {
									marginTop: '8px',
								} }
							>
								{ __(
									'This improves how AI systems reference and cite your content when answering questions.',
									'saman-seo'
								) }
							</p>
							<a
								href="https://llmstxt.org/"
								target="_blank"
								rel="noopener noreferrer"
								className="button ghost small"
								style={ {
									marginTop: '12px',
								} }
							>
								{ __( 'Learn More', 'saman-seo' ) }
							</a>
						</div>
					</aside>
				</div>
			) }
		</div>
	);
};
export default Sitemap;
