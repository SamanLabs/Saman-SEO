import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import AnalyticsNotice from '../components/AnalyticsNotice';
import { FacebookPreview, TwitterPreview } from '../components/SocialPreview';
import useUrlTab from '../hooks/useUrlTab';
import { analytics } from '../utils/analytics';
import { __ } from '@wordpress/i18n';
const settingsTabs = [
	{
		id: 'general',
		label: __( 'General', 'saman-seo' ),
	},
	{
		id: 'modules',
		label: __( 'Modules', 'saman-seo' ),
	},
	{
		id: 'breadcrumbs',
		label: __( 'Breadcrumbs', 'saman-seo' ),
	},
	{
		id: 'social',
		label: __( 'Social', 'saman-seo' ),
	},
	{
		id: 'advanced',
		label: __( 'Advanced', 'saman-seo' ),
	},
	{
		id: 'tools',
		label: __( 'Tools', 'saman-seo' ),
	},
];
const defaultSettings = {
	// General
	separator: '-',
	homepage_knowledge_type: 'organization',
	homepage_organization_name: '',
	homepage_organization_logo: '',
	homepage_person_name: '',
	homepage_person_image: '',
	homepage_person_job_title: '',
	homepage_person_url: '',
	homepage_social_profiles: '',
	sidebar_logo: '',
	// Webmaster tools
	google_verification: '',
	bing_verification: '',
	pinterest_verification: '',
	yandex_verification: '',
	baidu_verification: '',
	// Modules
	module_sitemap: true,
	module_redirects: true,
	module_404_log: true,
	module_social_cards: true,
	// 404 Monitor settings
	show_404_dashboard_widget: true,
	enable_404_cleanup: false,
	cleanup_404_days: 30,
	enable_404_notifications: false,
	notification_404_threshold: 10,
	notification_404_email: '',
	'404_log_ip_level': 'none',
	'404_log_ip_header': 'REMOTE_ADDR',
	'404_log_referer': true,
	'404_log_ignore_bots': false,
	// Redirect settings
	redirect_case_insensitive: false,
	redirect_ignore_trailing_slashes: false,
	redirect_query_matching: 'ignore',
	redirect_cache_header_hours: 1,
	redirect_object_cache: false,
	redirect_auto_generate_url: '',
	redirect_monitor_post_types: [ 'post', 'page' ],
	redirect_monitor_trash: false,
	// Data cleanup
	delete_data_on_uninstall: false,
	module_llm_txt: false,
	module_local_seo: false,
	module_internal_linking: true,
	module_schema: true,
	module_breadcrumbs: false,
	// Breadcrumbs settings
	breadcrumb_separator: '>',
	breadcrumb_separator_custom: '',
	breadcrumb_show_home: true,
	breadcrumb_home_label: '',
	breadcrumb_show_current: true,
	breadcrumb_link_current: false,
	breadcrumb_truncate_length: 0,
	breadcrumb_show_on_front: false,
	breadcrumb_style_preset: 'default',
	module_analytics: false,
	module_search_console: false,
	module_ai_assistant: true,
	module_indexnow: false,
	// IndexNow settings
	indexnow_submit_on_publish: true,
	indexnow_submit_on_update: true,
	// Social
	default_og_image: '',
	twitter_card_type: 'summary_large_image',
	twitter_username: '',
	facebook_app_id: '',
	facebook_admin_id: '',
	// Advanced
	enable_admin_bar: true,
	output_clean_head: true,
	remove_shortlinks: true,
	remove_rsd_link: true,
	remove_wlwmanifest: true,
	remove_wp_generator: true,
	remove_feed_links: false,
	disable_json_ld: false,
	disable_emoji: false,
	disable_comments_css: false,
	disable_gutenberg_css: false,
	enable_link_suggestions: true,
	enable_internal_link_count: true,
	enable_cornerstone_content: true,
	cache_schema: true,
	purge_on_save: true,
	enable_rest_api: true,
	debug_mode: false,
	// Performance
	lazy_load_schema: true,
	minify_schema_output: true,
	async_schema_validation: false,
	// API Keys
	openai_api_key: '',
	google_api_key: '',
	bing_api_key: '',
};
const Settings = () => {
	const [ activeTab, setActiveTab ] = useUrlTab( {
		tabs: settingsTabs,
		defaultTab: 'general',
	} );
	const [ settings, setSettings ] = useState( defaultSettings );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ saved, setSaved ] = useState( false );
	const [ importFile, setImportFile ] = useState( null );
	const [ resettingWizard, setResettingWizard ] = useState( false );
	const [ systemInfo, setSystemInfo ] = useState( {} );

	// Fetch settings
	const fetchSettings = useCallback( async () => {
		setLoading( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/settings',
			} );
			if ( res.success && res.data ) {
				const { system_info, ...settingsData } = res.data;
				setSettings( ( prev ) => ( {
					...prev,
					...settingsData,
				} ) );
				if ( system_info ) {
					setSystemInfo( system_info );
				}
			}
		} catch ( error ) {
			console.error( 'Failed to fetch settings:', error );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchSettings();
	}, [ fetchSettings ] );

	// Update setting
	const updateSetting = ( key, value ) => {
		setSettings( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
		setSaved( false );
	};

	// Save settings
	const handleSave = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/settings',
				method: 'POST',
				data: settings,
			} );
			setSaved( true );
			setTimeout( () => setSaved( false ), 3000 );
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
		} finally {
			setSaving( false );
		}
	};

	// Media library picker
	const openMediaLibrary = ( settingKey ) => {
		if ( window.wp && window.wp.media ) {
			const frame = window.wp.media( {
				title: __( 'Select Image', 'saman-seo' ),
				button: {
					text: __( 'Use Image', 'saman-seo' ),
				},
				multiple: false,
			} );
			frame.on( 'select', () => {
				const attachment = frame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();
				updateSetting( settingKey, attachment.url );
			} );
			frame.open();
		}
	};

	// Export settings
	const handleExport = () => {
		const data = JSON.stringify( settings, null, 2 );
		const blob = new Blob( [ data ], {
			type: 'application/json',
		} );
		const url = URL.createObjectURL( blob );
		const a = document.createElement( 'a' );
		a.href = url;
		a.download = `saman-seo-settings-${
			new Date().toISOString().split( 'T' )[ 0 ]
		}.json`;
		a.click();
		URL.revokeObjectURL( url );
	};

	// Import settings
	const handleImport = () => {
		if ( ! importFile ) return;
		const reader = new FileReader();
		reader.onload = ( e ) => {
			try {
				const imported = JSON.parse( e.target.result );
				setSettings( ( prev ) => ( {
					...prev,
					...imported,
				} ) );
				setSaved( false );
				setImportFile( null );
			} catch ( error ) {
				alert( __( 'Invalid JSON file', 'saman-seo' ) );
			}
		};
		reader.readAsText( importFile );
	};

	// Reset to defaults
	const handleReset = () => {
		if (
			window.confirm(
				__(
					'Are you sure you want to reset all settings to defaults? This cannot be undone.',
					'saman-seo'
				)
			)
		) {
			setSettings( defaultSettings );
			setSaved( false );
		}
	};

	// Reset setup wizard
	const handleResetWizard = async () => {
		setResettingWizard( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/setup/reset',
				method: 'POST',
			} );
			alert(
				__(
					'Setup wizard has been reset. It will appear on the next page load.',
					'saman-seo'
				)
			);
		} catch ( error ) {
			console.error( 'Failed to reset wizard:', error );
			alert( __( 'Failed to reset the setup wizard.', 'saman-seo' ) );
		} finally {
			setResettingWizard( false );
		}
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="loading-state">
					{ __( 'Loading settings\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Settings', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Configure Saman SEO features, integrations, and preferences.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header__actions">
					{ saved && (
						<span className="save-indicator">
							{ __( 'Saved', 'saman-seo' ) }
						</span>
					) }
					<button
						type="button"
						className="button primary"
						onClick={ handleSave }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save Changes', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<SubTabs
				tabs={ settingsTabs }
				activeTab={ activeTab }
				onChange={ setActiveTab }
				ariaLabel={ __( 'Settings sections', 'saman-seo' ) }
			/>

			{ activeTab === 'general' && (
				<GeneralTab
					settings={ settings }
					updateSetting={ updateSetting }
				/>
			) }

			{ activeTab === 'modules' && (
				<ModulesTab
					settings={ settings }
					updateSetting={ updateSetting }
				/>
			) }

			{ activeTab === 'breadcrumbs' && (
				<BreadcrumbsTab
					settings={ settings }
					updateSetting={ updateSetting }
				/>
			) }

			{ activeTab === 'social' && (
				<SocialTab
					settings={ settings }
					updateSetting={ updateSetting }
				/>
			) }

			{ activeTab === 'advanced' && (
				<AdvancedTab
					settings={ settings }
					updateSetting={ updateSetting }
				/>
			) }

			{ activeTab === 'tools' && (
				<ToolsTab
					settings={ settings }
					updateSetting={ updateSetting }
					onExport={ handleExport }
					onImport={ handleImport }
					onReset={ handleReset }
					onResetWizard={ handleResetWizard }
					resettingWizard={ resettingWizard }
					importFile={ importFile }
					setImportFile={ setImportFile }
					systemInfo={ systemInfo }
				/>
			) }
		</div>
	);
};

// General Tab
const GeneralTab = ( { settings, updateSetting } ) => {
	const separators = [
		{
			value: '-',
			label: __( 'Dash (-)', 'saman-seo' ),
		},
		{
			value: '|',
			label: __( 'Pipe (|)', 'saman-seo' ),
		},
		{
			value: '>',
			label: __( 'Greater than (>)', 'saman-seo' ),
		},
		{
			value: '<',
			label: __( 'Less than (<)', 'saman-seo' ),
		},
		{
			value: '~',
			label: __( 'Tilde (~)', 'saman-seo' ),
		},
		{
			value: '•',
			label: __( 'Bullet (\u2022)', 'saman-seo' ),
		},
		{
			value: '—',
			label: __( 'Em dash (\u2014)', 'saman-seo' ),
		},
	];
	return (
		<div className="settings-layout">
			<div className="settings-main">
				<section className="panel">
					<h3>{ __( 'Title Separator', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Character used between title parts across your site (e.g., "Page Title | Site Name").',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label>{ __( 'Separator', 'saman-seo' ) }</label>
							<p className="settings-help">
								{ __(
									'Click to select your preferred separator.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<div className="separator-picker">
								{ separators.map( ( sep ) => (
									<button
										key={ sep.value }
										type="button"
										className={ `separator-option ${
											settings.separator === sep.value
												? 'active'
												: ''
										}` }
										onClick={ () =>
											updateSetting(
												'separator',
												sep.value
											)
										}
										title={ sep.label }
									>
										{ sep.value }
									</button>
								) ) }
							</div>
						</div>
					</div>

					<div className="settings-info-box">
						<strong>
							{ __( 'Site Name & Tagline', 'saman-seo' ) }
						</strong>
						<p>
							{ __(
								'These are managed in WordPress Settings.',
								'saman-seo'
							) }{ ' ' }
							<a href="options-general.php">
								{ __(
									'Edit in Settings \u2192 General',
									'saman-seo'
								) }
							</a>
						</p>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Knowledge Graph', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Tell search engines who you are with structured data.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label>
								{ __( 'Site Represents', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Is this site for a person or organization?',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<div className="radio-group">
								<label className="radio-item">
									<input
										type="radio"
										name="kg_type"
										checked={
											settings.homepage_knowledge_type ===
											'organization'
										}
										onChange={ () =>
											updateSetting(
												'homepage_knowledge_type',
												'organization'
											)
										}
									/>
									<span>
										{ __( 'Organization', 'saman-seo' ) }
									</span>
								</label>
								<label className="radio-item">
									<input
										type="radio"
										name="kg_type"
										checked={
											settings.homepage_knowledge_type ===
											'person'
										}
										onChange={ () =>
											updateSetting(
												'homepage_knowledge_type',
												'person'
											)
										}
									/>
									<span>{ __( 'Person', 'saman-seo' ) }</span>
								</label>
							</div>
						</div>
					</div>

					{ __(
						'https://twitter.com/johndoe https://linkedin.com/in/johndoe',
						'saman-seo'
					) }
				</section>

				<section className="panel">
					<h3>
						{ __( 'Webmaster Tools Verification', 'saman-seo' ) }
					</h3>
					<p className="panel-desc">
						{ __(
							'Verify your site with search engines and services.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="google-verify">
								{ __( 'Google Search Console', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __( 'Meta tag content value.', 'saman-seo' ) }
							</p>
						</div>
						<div className="settings-control">
							<input
								id="google-verify"
								type="text"
								value={ settings.google_verification }
								onChange={ ( e ) =>
									updateSetting(
										'google_verification',
										e.target.value
									)
								}
								placeholder={ __(
									'abc123\u2026',
									'saman-seo'
								) }
							/>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="bing-verify">
								{ __( 'Bing Webmaster Tools', 'saman-seo' ) }
							</label>
						</div>
						<div className="settings-control">
							<input
								id="bing-verify"
								type="text"
								value={ settings.bing_verification }
								onChange={ ( e ) =>
									updateSetting(
										'bing_verification',
										e.target.value
									)
								}
								placeholder={ __(
									'abc123\u2026',
									'saman-seo'
								) }
							/>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="pinterest-verify">
								{ __( 'Pinterest', 'saman-seo' ) }
							</label>
						</div>
						<div className="settings-control">
							<input
								id="pinterest-verify"
								type="text"
								value={ settings.pinterest_verification }
								onChange={ ( e ) =>
									updateSetting(
										'pinterest_verification',
										e.target.value
									)
								}
								placeholder={ __(
									'abc123\u2026',
									'saman-seo'
								) }
							/>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="yandex-verify">
								{ __( 'Yandex', 'saman-seo' ) }
							</label>
						</div>
						<div className="settings-control">
							<input
								id="yandex-verify"
								type="text"
								value={ settings.yandex_verification }
								onChange={ ( e ) =>
									updateSetting(
										'yandex_verification',
										e.target.value
									)
								}
								placeholder={ __(
									'abc123\u2026',
									'saman-seo'
								) }
							/>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Editor Sidebar', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Customize how Saman SEO appears in the block editor.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="sidebar-logo">
								{ __( 'Custom Logo', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Optional. Replace "SEO" text with your own logo. Recommended: 20x20px.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<input
								id="sidebar-logo"
								type="url"
								value={ settings.sidebar_logo }
								onChange={ ( e ) =>
									updateSetting(
										'sidebar_logo',
										e.target.value
									)
								}
								placeholder={ __(
									'https://example.com/icon.png',
									'saman-seo'
								) }
							/>
							{ __( 'Logo preview', 'saman-seo' ) }
						</div>
					</div>

					<div className="settings-info-box">
						<strong>
							{ __( 'Default Appearance', 'saman-seo' ) }
						</strong>
						<p>
							{ __(
								'Leave empty to show "SEO" text in the editor sidebar button.',
								'saman-seo'
							) }
						</p>
					</div>
				</section>
			</div>

			<aside className="settings-sidebar">
				<div className="side-card highlight">
					<h4>{ __( 'Title Preview', 'saman-seo' ) }</h4>
					<div className="title-preview">
						<span className="title-preview__text">
							{ __( 'Page Title', 'saman-seo' ) }{ ' ' }
							{ settings.separator }{ ' ' }
							{ __( 'Site Name', 'saman-seo' ) }
						</span>
					</div>
					<p className="muted-block">
						{ __(
							'This is how titles will be structured across your site.',
							'saman-seo'
						) }
					</p>
				</div>
			</aside>
		</div>
	);
};

// Modules Tab
const ModulesTab = ( { settings, updateSetting } ) => {
	const modules = [
		{
			key: 'module_sitemap',
			name: __( 'XML Sitemap', 'saman-seo' ),
			desc: __(
				'Generate and manage XML sitemaps for search engines.',
				'saman-seo'
			),
			icon: '🗺️',
		},
		{
			key: 'module_redirects',
			name: __( 'Redirects', 'saman-seo' ),
			desc: __(
				'Create and manage URL redirects (301, 302, 307).',
				'saman-seo'
			),
			icon: '↪️',
		},
		{
			key: 'module_404_log',
			name: __( '404 Error Log', 'saman-seo' ),
			desc: __(
				'Track and monitor 404 errors on your site.',
				'saman-seo'
			),
			icon: '🚫',
		},
		{
			key: 'module_internal_linking',
			name: __( 'Internal Linking', 'saman-seo' ),
			desc: __(
				'Automatic internal link suggestions and management.',
				'saman-seo'
			),
			icon: '🔗',
		},
		{
			key: 'module_schema',
			name: __( 'Schema Markup', 'saman-seo' ),
			desc: __(
				'Add structured data for rich search results.',
				'saman-seo'
			),
			icon: '📊',
		},
		{
			key: 'module_social_cards',
			name: __( 'Social Cards', 'saman-seo' ),
			desc: __(
				'Dynamic Open Graph and Twitter Card generation.',
				'saman-seo'
			),
			icon: '🃏',
		},
		{
			key: 'module_breadcrumbs',
			name: __( 'Breadcrumbs', 'saman-seo' ),
			desc: __( 'SEO-friendly breadcrumb navigation.', 'saman-seo' ),
			icon: '🥖',
		},
		{
			key: 'module_llm_txt',
			name: __( 'LLM.txt', 'saman-seo' ),
			desc: __(
				'Generate llm.txt file for AI crawlers and LLMs.',
				'saman-seo'
			),
			icon: '🤖',
		},
		{
			key: 'module_local_seo',
			name: __( 'Local SEO', 'saman-seo' ),
			desc: __(
				'Local business schema and location pages.',
				'saman-seo'
			),
			icon: '📍',
		},
		{
			key: 'module_ai_assistant',
			name: __( 'AI Assistant', 'saman-seo' ),
			desc: __(
				'AI-powered content optimization suggestions.',
				'saman-seo'
			),
			icon: '✨',
		},
		{
			key: 'module_indexnow',
			name: __( 'IndexNow', 'saman-seo' ),
			desc: __(
				'Instant URL submission to search engines (Bing, Yandex).',
				'saman-seo'
			),
			icon: '⚡',
		},
		{
			key: 'module_search_console',
			name: __( 'Search Console', 'saman-seo' ),
			desc: __( 'Google Search Console integration.', 'saman-seo' ),
			icon: '🔍',
		},
	];
	const enabledCount = modules.filter( ( m ) => settings[ m.key ] ).length;

	// Handle module toggle with analytics tracking
	const handleModuleToggle = ( key, enabled ) => {
		updateSetting( key, enabled );
		analytics.settings.moduleToggle(
			key.replace( 'module_', '' ),
			enabled
		);
	};

	// Handle analytics toggle separately (it's special)
	const handleAnalyticsToggle = ( enabled ) => {
		updateSetting( 'module_analytics', enabled );
		// Note: Can't track disabling analytics since it would disable itself
		if ( enabled ) {
			analytics.settings.moduleToggle( 'analytics', true );
		}
	};
	return (
		<div className="settings-layout">
			<div className="settings-main">
				{ /* Analytics Notice - Always at top */ }
				<AnalyticsNotice
					isEnabled={ settings.module_analytics }
					onToggle={ handleAnalyticsToggle }
				/>

				<section className="panel">
					<div className="panel-header">
						<div>
							<h3>{ __( 'Feature Modules', 'saman-seo' ) }</h3>
							<p className="panel-desc">
								{ __(
									'Enable or disable plugin features. Disabled modules are completely unloaded for performance.',
									'saman-seo'
								) }
							</p>
						</div>
						<span className="module-count">
							{ enabledCount } { __( 'of', 'saman-seo' ) }{ ' ' }
							{ modules.length } { __( 'enabled', 'saman-seo' ) }
						</span>
					</div>

					<div className="modules-grid">
						{ modules.map( ( module ) => (
							<div
								key={ module.key }
								className={ `module-card ${
									settings[ module.key ] ? 'active' : ''
								}` }
							>
								<div className="module-card__icon">
									{ module.icon }
								</div>
								<div className="module-card__content">
									<h4>{ module.name }</h4>
									<p>{ module.desc }</p>
								</div>
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings[ module.key ] }
										onChange={ ( e ) =>
											handleModuleToggle(
												module.key,
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						) ) }
					</div>
				</section>
			</div>

			<aside className="settings-sidebar">
				<div className="side-card">
					<h4>{ __( 'Performance Tip', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							"Disable modules you don't use to reduce database queries and improve page load times.",
							'saman-seo'
						) }
					</p>
				</div>
				<div className="side-card warning">
					<h4>{ __( 'Dependencies', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'Some modules require others. For example, "404 Error Log" works best with "Redirects" enabled.',
							'saman-seo'
						) }
					</p>
				</div>
			</aside>
		</div>
	);
};

// Breadcrumbs Tab
const BreadcrumbsTab = ( { settings, updateSetting } ) => {
	const separatorOptions = [
		{
			value: '>',
			label: __( 'Angle bracket (>)', 'saman-seo' ),
		},
		{
			value: '/',
			label: __( 'Slash (/)', 'saman-seo' ),
		},
		{
			value: '|',
			label: __( 'Pipe (|)', 'saman-seo' ),
		},
		{
			value: '-',
			label: __( 'Dash (-)', 'saman-seo' ),
		},
		{
			value: 'arrow',
			label: __( 'Arrow (\u2192)', 'saman-seo' ),
		},
		{
			value: 'chevron',
			label: __( 'Chevron (\u203A)', 'saman-seo' ),
		},
		{
			value: 'custom',
			label: __( 'Custom', 'saman-seo' ),
		},
	];
	const stylePresets = [
		{
			value: 'default',
			label: __( 'Default', 'saman-seo' ),
			desc: __( 'Gray background with rounded corners', 'saman-seo' ),
		},
		{
			value: 'minimal',
			label: __( 'Minimal', 'saman-seo' ),
			desc: __( 'Clean text without background', 'saman-seo' ),
		},
		{
			value: 'rounded',
			label: __( 'Rounded', 'saman-seo' ),
			desc: __( 'Pill-shaped items', 'saman-seo' ),
		},
		{
			value: 'pills',
			label: __( 'Pills', 'saman-seo' ),
			desc: __( 'Arrow-shaped connected items', 'saman-seo' ),
		},
		{
			value: 'none',
			label: __( 'No Styling', 'saman-seo' ),
			desc: __( 'Unstyled for custom CSS', 'saman-seo' ),
		},
	];
	const isEnabled = settings.module_breadcrumbs;
	return (
		<div className="settings-layout">
			<div className="settings-main">
				{ /* Enable/Disable Section */ }
				<section className="panel">
					<div className="panel-header">
						<div>
							<h3>
								{ __( 'Breadcrumbs Navigation', 'saman-seo' ) }
							</h3>
							<p className="panel-desc">
								{ __(
									'Add SEO-friendly breadcrumb navigation to your pages.',
									'saman-seo'
								) }
							</p>
						</div>
						<label className="toggle">
							<input
								type="checkbox"
								checked={ isEnabled }
								onChange={ ( e ) =>
									updateSetting(
										'module_breadcrumbs',
										e.target.checked
									)
								}
							/>
							<span className="toggle-track" />
						</label>
					</div>

					{ ! isEnabled && (
						<div className="notice notice-info">
							<p>
								{ __(
									'Enable breadcrumbs to configure settings below.',
									'saman-seo'
								) }
							</p>
						</div>
					) }
				</section>

				{ isEnabled && (
					<>
						{ /* Display Settings */ }
						<section className="panel">
							<h3>{ __( 'Display Settings', 'saman-seo' ) }</h3>
							<p className="panel-desc">
								{ __(
									'Configure how breadcrumbs appear on your site.',
									'saman-seo'
								) }
							</p>

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-separator">
										{ __( 'Separator', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'Character between breadcrumb items.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<select
										id="breadcrumb-separator"
										value={ settings.breadcrumb_separator }
										onChange={ ( e ) =>
											updateSetting(
												'breadcrumb_separator',
												e.target.value
											)
										}
									>
										{ separatorOptions.map( ( opt ) => (
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

							{ __( 'e.g., \u2022', 'saman-seo' ) }

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-style">
										{ __( 'Style Preset', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'Visual style for breadcrumbs.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<select
										id="breadcrumb-style"
										value={
											settings.breadcrumb_style_preset
										}
										onChange={ ( e ) =>
											updateSetting(
												'breadcrumb_style_preset',
												e.target.value
											)
										}
									>
										{ stylePresets.map( ( opt ) => (
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

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-truncate">
										{ __( 'Truncate Length', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'Max characters per item. 0 = no truncation.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<input
										id="breadcrumb-truncate"
										type="number"
										min="0"
										max="100"
										value={
											settings.breadcrumb_truncate_length
										}
										onChange={ ( e ) =>
											updateSetting(
												'breadcrumb_truncate_length',
												parseInt(
													e.target.value,
													10
												) || 0
											)
										}
									/>
								</div>
							</div>
						</section>

						{ /* Home Link Settings */ }
						<section className="panel">
							<h3>{ __( 'Home Link', 'saman-seo' ) }</h3>
							<p className="panel-desc">
								{ __(
									'Configure the home link in breadcrumbs.',
									'saman-seo'
								) }
							</p>

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-show-home">
										{ __( 'Show Home Link', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'Include a link to the homepage in breadcrumbs.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<label className="toggle">
										<input
											id="breadcrumb-show-home"
											type="checkbox"
											checked={
												settings.breadcrumb_show_home
											}
											onChange={ ( e ) =>
												updateSetting(
													'breadcrumb_show_home',
													e.target.checked
												)
											}
										/>
										<span className="toggle-track" />
									</label>
								</div>
							</div>

							{ __( 'Home', 'saman-seo' ) }
						</section>

						{ /* Current Page Settings */ }
						<section className="panel">
							<h3>{ __( 'Current Page', 'saman-seo' ) }</h3>
							<p className="panel-desc">
								{ __(
									'Configure how the current page appears in breadcrumbs.',
									'saman-seo'
								) }
							</p>

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-show-current">
										{ __(
											'Show Current Page',
											'saman-seo'
										) }
									</label>
									<p className="settings-help">
										{ __(
											'Display the current page title in breadcrumbs.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<label className="toggle">
										<input
											id="breadcrumb-show-current"
											type="checkbox"
											checked={
												settings.breadcrumb_show_current
											}
											onChange={ ( e ) =>
												updateSetting(
													'breadcrumb_show_current',
													e.target.checked
												)
											}
										/>
										<span className="toggle-track" />
									</label>
								</div>
							</div>

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-link-current">
										{ __(
											'Link Current Page',
											'saman-seo'
										) }
									</label>
									<p className="settings-help">
										{ __(
											'Make the current page title a clickable link.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<label className="toggle">
										<input
											id="breadcrumb-link-current"
											type="checkbox"
											checked={
												settings.breadcrumb_link_current
											}
											onChange={ ( e ) =>
												updateSetting(
													'breadcrumb_link_current',
													e.target.checked
												)
											}
										/>
										<span className="toggle-track" />
									</label>
								</div>
							</div>

							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="breadcrumb-show-front">
										{ __(
											'Show on Front Page',
											'saman-seo'
										) }
									</label>
									<p className="settings-help">
										{ __(
											'Display breadcrumbs on the homepage.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<label className="toggle">
										<input
											id="breadcrumb-show-front"
											type="checkbox"
											checked={
												settings.breadcrumb_show_on_front
											}
											onChange={ ( e ) =>
												updateSetting(
													'breadcrumb_show_on_front',
													e.target.checked
												)
											}
										/>
										<span className="toggle-track" />
									</label>
								</div>
							</div>
						</section>
					</>
				) }
			</div>

			<aside className="settings-sidebar">
				<div className="side-card">
					<h4>{ __( 'Usage', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'Add breadcrumbs to your theme using:',
							'saman-seo'
						) }
					</p>
					<code className="code-block">[Saman_seo_breadcrumbs]</code>
					<p className="muted-block">
						{ __( 'Or in PHP:', 'saman-seo' ) }
					</p>
					<code className="code-block">Saman_seo_breadcrumbs();</code>
				</div>

				<div className="side-card">
					<h4>{ __( 'Gutenberg Block', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'Search for "Saman SEO Breadcrumbs" in the block inserter to add breadcrumbs to any page.',
							'saman-seo'
						) }
					</p>
				</div>

				<div className="side-card success">
					<h4>{ __( 'Schema Markup', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'BreadcrumbList JSON-LD schema is automatically added when breadcrumbs are enabled.',
							'saman-seo'
						) }
					</p>
				</div>
			</aside>
		</div>
	);
};

// Social Tab
const SocialTab = ( { settings, updateSetting } ) => {
	const [ siteInfo, setSiteInfo ] = useState( {
		name: __( 'Your Site Name', 'saman-seo' ),
		description: __( 'Your site description', 'saman-seo' ),
		domain: 'yoursite.com',
	} );

	// Fetch site info on mount
	useEffect( () => {
		apiFetch( {
			path: '/saman-seo/v1/search-appearance',
		} )
			.then( ( res ) => {
				if ( res.success && res.data?.site_info ) {
					setSiteInfo( res.data.site_info );
				}
			} )
			.catch( () => {
				// Use defaults if fetch fails
			} );
	}, [] );
	return (
		<div className="settings-layout">
			<div className="settings-main">
				<section className="panel">
					<h3>{ __( 'Open Graph Defaults', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Default settings for Facebook and other social platforms.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label>
								{ __( 'Default Share Image', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Used when no featured image is available. Recommended: 1200x630px.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<div className="image-uploader">
								{ __( 'Remove image', 'saman-seo' ) }
								<button
									type="button"
									className="button"
									onClick={ () => {
										const frame = wp.media( {
											title: __(
												'Select Default Share Image',
												'saman-seo'
											),
											button: {
												text: __(
													'Use Image',
													'saman-seo'
												),
											},
											multiple: false,
											library: {
												type: 'image',
											},
										} );
										frame.on( 'select', () => {
											const attachment = frame
												.state()
												.get( 'selection' )
												.first()
												.toJSON();
											updateSetting(
												'default_og_image',
												attachment.url
											);
										} );
										frame.open();
									} }
								>
									{ settings.default_og_image
										? __( 'Change Image', 'saman-seo' )
										: __( 'Select Image', 'saman-seo' ) }
								</button>
							</div>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="fb-app-id">
								{ __( 'Facebook App ID', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __( 'For Facebook Insights.', 'saman-seo' ) }
							</p>
						</div>
						<div className="settings-control">
							<input
								id="fb-app-id"
								type="text"
								value={ settings.facebook_app_id }
								onChange={ ( e ) =>
									updateSetting(
										'facebook_app_id',
										e.target.value
									)
								}
								placeholder="123456789"
							/>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="fb-admin-id">
								{ __( 'Facebook Admin ID', 'saman-seo' ) }
							</label>
						</div>
						<div className="settings-control">
							<input
								id="fb-admin-id"
								type="text"
								value={ settings.facebook_admin_id }
								onChange={ ( e ) =>
									updateSetting(
										'facebook_admin_id',
										e.target.value
									)
								}
								placeholder="123456789"
							/>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Twitter/X Settings', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Configure Twitter Card appearance.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row">
						<div className="settings-label">
							<label>{ __( 'Card Type', 'saman-seo' ) }</label>
							<p className="settings-help">
								{ __(
									'How your content appears on Twitter.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<div className="radio-group">
								<label className="radio-item">
									<input
										type="radio"
										name="twitter_card"
										checked={
											settings.twitter_card_type ===
											'summary'
										}
										onChange={ () =>
											updateSetting(
												'twitter_card_type',
												'summary'
											)
										}
									/>
									<span>
										{ __( 'Summary', 'saman-seo' ) }
									</span>
								</label>
								<label className="radio-item">
									<input
										type="radio"
										name="twitter_card"
										checked={
											settings.twitter_card_type ===
											'summary_large_image'
										}
										onChange={ () =>
											updateSetting(
												'twitter_card_type',
												'summary_large_image'
											)
										}
									/>
									<span>
										{ __( 'Large Image', 'saman-seo' ) }
									</span>
								</label>
							</div>
						</div>
					</div>

					<div className="settings-row">
						<div className="settings-label">
							<label htmlFor="twitter-username">
								{ __( 'Twitter Username', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Your @handle without the @.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<div className="input-with-prefix">
								<span className="input-prefix">@</span>
								<input
									id="twitter-username"
									type="text"
									value={ settings.twitter_username }
									onChange={ ( e ) =>
										updateSetting(
											'twitter_username',
											e.target.value
										)
									}
									placeholder={ __(
										'username',
										'saman-seo'
									) }
								/>
							</div>
						</div>
					</div>
				</section>
			</div>

			<aside className="settings-sidebar">
				<FacebookPreview
					image={ settings.default_og_image }
					title={ siteInfo.name }
					description={ siteInfo.description }
					domain={ siteInfo.domain }
				/>
				<TwitterPreview
					image={ settings.default_og_image }
					title={ siteInfo.name }
					description={ siteInfo.description }
					domain={ siteInfo.domain }
					cardType={ settings.twitter_card_type }
				/>
			</aside>
		</div>
	);
};

// Redirects Panel
const RedirectsPanel = ( { settings, updateSetting } ) => {
	const [ postTypes, setPostTypes ] = useState( [] );
	useEffect( () => {
		apiFetch( {
			path: '/wp/v2/types',
		} )
			.then( ( types ) => {
				const publicTypes = Object.values( types ).filter(
					( type ) => type.public && type.slug !== 'attachment'
				);
				setPostTypes( publicTypes );
			} )
			.catch( () => {
				setPostTypes( [
					{
						slug: 'post',
						name: __( 'Posts', 'saman-seo' ),
					},
					{
						slug: 'page',
						name: __( 'Pages', 'saman-seo' ),
					},
				] );
			} );
	}, [] );
	const togglePostType = ( slug ) => {
		const current = settings.redirect_monitor_post_types || [];
		const updated = current.includes( slug )
			? current.filter( ( s ) => s !== slug )
			: [ ...current, slug ];
		updateSetting( 'redirect_monitor_post_types', updated );
	};
	return (
		<section className="panel">
			<h3>{ __( 'Redirects', 'saman-seo' ) }</h3>
			<p className="panel-desc">
				{ __(
					'Configure default redirect matching and URL monitoring behavior.',
					'saman-seo'
				) }
			</p>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( 'Case Insensitive Matches', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'/Exciting-Post will match /exciting-post.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<label className="toggle">
						<input
							type="checkbox"
							checked={ settings.redirect_case_insensitive }
							onChange={ ( e ) =>
								updateSetting(
									'redirect_case_insensitive',
									e.target.checked
								)
							}
						/>
						<span className="toggle-track" />
					</label>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( 'Ignore Trailing Slashes', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'/exciting-post/ will match /exciting-post.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<label className="toggle">
						<input
							type="checkbox"
							checked={
								settings.redirect_ignore_trailing_slashes
							}
							onChange={ ( e ) =>
								updateSetting(
									'redirect_ignore_trailing_slashes',
									e.target.checked
								)
							}
						/>
						<span className="toggle-track" />
					</label>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( 'Default Query Matching', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'Applies to all redirects unless configured otherwise.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<select
						value={ settings.redirect_query_matching }
						onChange={ ( e ) =>
							updateSetting(
								'redirect_query_matching',
								e.target.value
							)
						}
					>
						<option value="exact">
							{ __(
								'Exact - match query parameters exactly',
								'saman-seo'
							) }
						</option>
						<option value="ignore">
							{ __(
								'Ignore - ignore unknown query parameters',
								'saman-seo'
							) }
						</option>
						<option value="pass">
							{ __(
								'Pass - copy query parameters to target',
								'saman-seo'
							) }
						</option>
					</select>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( '301 Cache Header (hours)', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'How long to cache 301 redirects via the Expires header. 0 disables caching.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<input
						type="number"
						min="0"
						max="8760"
						value={ settings.redirect_cache_header_hours || 0 }
						onChange={ ( e ) =>
							updateSetting(
								'redirect_cache_header_hours',
								parseInt( e.target.value, 10 ) || 0
							)
						}
						style={ {
							width: '80px',
						} }
					/>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( 'Cache Redirects in Object Cache', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'Improves performance when an object cache is available.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<label className="toggle">
						<input
							type="checkbox"
							checked={ settings.redirect_object_cache }
							onChange={ ( e ) =>
								updateSetting(
									'redirect_object_cache',
									e.target.checked
								)
							}
						/>
						<span className="toggle-track" />
					</label>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label htmlFor="redirect-auto-generate">
						{ __( 'Auto-Generate URL', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'Used when no target URL is given. Use $dec$ or $hex$ for a unique ID.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<input
						id="redirect-auto-generate"
						type="text"
						value={ settings.redirect_auto_generate_url || '' }
						onChange={ ( e ) =>
							updateSetting(
								'redirect_auto_generate_url',
								e.target.value
							)
						}
						placeholder={ __(
							'https://example.com/redirect/$dec$',
							'saman-seo'
						) }
						style={ {
							width: '280px',
						} }
					/>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>{ __( 'Monitor URL Changes', 'saman-seo' ) }</label>
					<p className="settings-help">
						{ __(
							'Automatically suggest redirects when URLs change for these content types.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<div className="checkbox-group">
						{ postTypes.map( ( type ) => (
							<label key={ type.slug } className="checkbox-label">
								<input
									type="checkbox"
									checked={ (
										settings.redirect_monitor_post_types ||
										[]
									).includes( type.slug ) }
									onChange={ () =>
										togglePostType( type.slug )
									}
								/>
								<span>{ type.name || type.slug }</span>
							</label>
						) ) }
					</div>
				</div>
			</div>

			<div className="settings-row compact">
				<div className="settings-label">
					<label>
						{ __( 'Monitor Trashed Content', 'saman-seo' ) }
					</label>
					<p className="settings-help">
						{ __(
							'Suggest a redirect when monitored content is moved to trash.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="settings-control">
					<label className="toggle">
						<input
							type="checkbox"
							checked={ settings.redirect_monitor_trash }
							onChange={ ( e ) =>
								updateSetting(
									'redirect_monitor_trash',
									e.target.checked
								)
							}
						/>
						<span className="toggle-track" />
					</label>
				</div>
			</div>
		</section>
	);
};

// Advanced Tab
const AdvancedTab = ( { settings, updateSetting } ) => {
	return (
		<div className="settings-layout">
			<div className="settings-main">
				<RedirectsPanel
					settings={ settings }
					updateSetting={ updateSetting }
				/>

				<section className="panel">
					<h3>{ __( 'User Interface', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Customize how Saman SEO appears in your WordPress admin.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Admin Bar Menu', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Show SEO Pilot menu in the WordPress admin bar with quick access to features and SEO score on posts/pages.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.enable_admin_bar }
									onChange={ ( e ) =>
										updateSetting(
											'enable_admin_bar',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'WordPress Head Cleanup', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							"Remove unnecessary tags from your site's <head> section.",
							'saman-seo'
						) }
					</p>

					<div className="settings-grid">
						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Remove Shortlinks', 'saman-seo' ) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.remove_shortlinks }
										onChange={ ( e ) =>
											updateSetting(
												'remove_shortlinks',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Remove RSD Link', 'saman-seo' ) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.remove_rsd_link }
										onChange={ ( e ) =>
											updateSetting(
												'remove_rsd_link',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Remove WLW Manifest', 'saman-seo' ) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.remove_wlwmanifest }
										onChange={ ( e ) =>
											updateSetting(
												'remove_wlwmanifest',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Remove WP Generator', 'saman-seo' ) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.remove_wp_generator }
										onChange={ ( e ) =>
											updateSetting(
												'remove_wp_generator',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Remove Feed Links', 'saman-seo' ) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.remove_feed_links }
										onChange={ ( e ) =>
											updateSetting(
												'remove_feed_links',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __(
										'Disable Emoji Scripts',
										'saman-seo'
									) }
								</label>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={ settings.disable_emoji }
										onChange={ ( e ) =>
											updateSetting(
												'disable_emoji',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Content Analysis', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Features for content optimization in the editor.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Link Suggestions', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Show internal link suggestions while editing.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.enable_link_suggestions }
									onChange={ ( e ) =>
										updateSetting(
											'enable_link_suggestions',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Internal Link Counter', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Show count of internal links in post list.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings.enable_internal_link_count
									}
									onChange={ ( e ) =>
										updateSetting(
											'enable_internal_link_count',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Cornerstone Content', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Enable cornerstone content marking.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings.enable_cornerstone_content
									}
									onChange={ ( e ) =>
										updateSetting(
											'enable_cornerstone_content',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Performance', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __( 'Optimize plugin performance.', 'saman-seo' ) }
					</p>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Cache Schema Output', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Cache generated schema markup.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.cache_schema }
									onChange={ ( e ) =>
										updateSetting(
											'cache_schema',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Minify Schema', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __( 'Minify JSON-LD output.', 'saman-seo' ) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.minify_schema_output }
									onChange={ ( e ) =>
										updateSetting(
											'minify_schema_output',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Purge Cache on Save', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Clear caches when posts are updated.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.purge_on_save }
									onChange={ ( e ) =>
										updateSetting(
											'purge_on_save',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>

				{ settings.module_indexnow && (
					<section className="panel">
						<h3>{ __( 'IndexNow Settings', 'saman-seo' ) }</h3>
						<p className="panel-desc">
							{ __(
								'Configure instant URL submission to search engines.',
								'saman-seo'
							) }
						</p>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Submit on Publish', 'saman-seo' ) }
								</label>
								<p className="settings-help">
									{ __(
										'Automatically submit URLs when new content is published.',
										'saman-seo'
									) }
								</p>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={
											settings.indexnow_submit_on_publish
										}
										onChange={ ( e ) =>
											updateSetting(
												'indexnow_submit_on_publish',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Submit on Update', 'saman-seo' ) }
								</label>
								<p className="settings-help">
									{ __(
										'Automatically submit URLs when existing content is updated.',
										'saman-seo'
									) }
								</p>
							</div>
							<div className="settings-control">
								<label className="toggle">
									<input
										type="checkbox"
										checked={
											settings.indexnow_submit_on_update
										}
										onChange={ ( e ) =>
											updateSetting(
												'indexnow_submit_on_update',
												e.target.checked
											)
										}
									/>
									<span className="toggle-track" />
								</label>
							</div>
						</div>

						<div className="settings-info">
							<p className="muted">
								{ __(
									'IndexNow instantly notifies Bing, Yandex, Seznam, and Naver when your content changes. An API key is automatically generated when you enable the module.',
									'saman-seo'
								) }
							</p>
						</div>
					</section>
				) }

				<section className="panel panel--deprecated">
					<h3>
						{ __( 'API Keys', 'saman-seo' ) }{ ' ' }
						<span className="deprecated-badge">
							{ __( 'Deprecated', 'saman-seo' ) }
						</span>
					</h3>
					<p className="panel-desc">
						{ __(
							'API key management has moved to WP AI Pilot for centralized AI configuration.',
							'saman-seo'
						) }
					</p>

					<div className="deprecation-notice deprecation-notice--block">
						<div className="deprecation-notice__icon">
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
							</svg>
						</div>
						<div className="deprecation-notice__content">
							<h4>
								{ __(
									'AI Features Now Powered by WP AI Pilot',
									'saman-seo'
								) }
							</h4>
							<p>
								{ __(
									'Configure your OpenAI, Anthropic, Google AI, and other API keys in WP AI Pilot. This provides unified AI management across all your WordPress plugins that support it.',
									'saman-seo'
								) }
							</p>
							<div className="deprecation-notice__actions">
								<a
									href="admin.php?page=wp-ai-pilot"
									className="button primary"
								>
									{ __( 'Open WP AI Pilot', 'saman-seo' ) }
								</a>
								<a
									href="plugin-install.php?s=wp+ai+pilot&tab=search"
									className="button ghost"
								>
									{ __( 'Install WP AI Pilot', 'saman-seo' ) }
								</a>
							</div>
						</div>
					</div>

					<details className="legacy-settings-toggle">
						<summary>
							{ __(
								'Show legacy API key fields (for reference only)',
								'saman-seo'
							) }
						</summary>
						<div className="legacy-settings-content">
							<p className="muted">
								{ __(
									'These fields are read-only. Use WP AI Pilot to manage API keys.',
									'saman-seo'
								) }
							</p>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="openai-key">
										{ __( 'OpenAI API Key', 'saman-seo' ) }
									</label>
								</div>
								<div className="settings-control">
									<input
										id="openai-key"
										type="password"
										value={
											settings.openai_api_key
												? '••••••••••••'
												: ''
										}
										disabled
										placeholder={ __(
											'Configured in WP AI Pilot',
											'saman-seo'
										) }
									/>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="google-key">
										{ __( 'Google API Key', 'saman-seo' ) }
									</label>
								</div>
								<div className="settings-control">
									<input
										id="google-key"
										type="password"
										value={
											settings.google_api_key
												? '••••••••••••'
												: ''
										}
										disabled
										placeholder={ __(
											'Configured in WP AI Pilot',
											'saman-seo'
										) }
									/>
								</div>
							</div>
						</div>
					</details>
				</section>

				<section className="panel">
					<h3>{ __( '404 Monitor', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Configure 404 error logging and cleanup options.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Dashboard Widget', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Show 404 summary widget on the WordPress dashboard.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings.show_404_dashboard_widget !==
										false
									}
									onChange={ ( e ) =>
										updateSetting(
											'show_404_dashboard_widget',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>{ __( 'Auto-Cleanup', 'saman-seo' ) }</label>
							<p className="settings-help">
								{ __(
									'Automatically delete old 404 entries to keep the database clean.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.enable_404_cleanup }
									onChange={ ( e ) =>
										updateSetting(
											'enable_404_cleanup',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					{ settings.enable_404_cleanup && (
						<div className="settings-row compact">
							<div className="settings-label">
								<label>
									{ __( 'Cleanup Age (days)', 'saman-seo' ) }
								</label>
								<p className="settings-help">
									{ __(
										'Delete 404 entries older than this many days.',
										'saman-seo'
									) }
								</p>
							</div>
							<div className="settings-control">
								<input
									type="number"
									min="1"
									max="365"
									value={ settings.cleanup_404_days || 30 }
									onChange={ ( e ) =>
										updateSetting(
											'cleanup_404_days',
											parseInt( e.target.value, 10 ) || 30
										)
									}
									className="input--narrow"
								/>
							</div>
						</div>
					) }

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Email Notifications', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Send email alerts when a URL hits the notification threshold.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings.enable_404_notifications
									}
									onChange={ ( e ) =>
										updateSetting(
											'enable_404_notifications',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					{ __( 'admin@example.com', 'saman-seo' ) }

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Log IP Address', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Store the visitor IP address with each 404 log entry.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<select
								value={ settings[ '404_log_ip_level' ] }
								onChange={ ( e ) =>
									updateSetting(
										'404_log_ip_level',
										e.target.value
									)
								}
							>
								<option value="none">
									{ __( 'No IP logging', 'saman-seo' ) }
								</option>
								<option value="anonymized">
									{ __(
										'Anonymized (last octet hidden)',
										'saman-seo'
									) }
								</option>
								<option value="full">
									{ __( 'Full IP address', 'saman-seo' ) }
								</option>
							</select>
						</div>
					</div>

					{ __( 'REMOTE_ADDR', 'saman-seo' ) }

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Log HTTP Referer', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Store the referring URL with each 404 log entry.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings[ '404_log_referer' ] }
									onChange={ ( e ) =>
										updateSetting(
											'404_log_referer',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Ignore Bot Requests', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Do not log 404s from known bots and crawlers.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings[ '404_log_ignore_bots' ]
									}
									onChange={ ( e ) =>
										updateSetting(
											'404_log_ignore_bots',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Developer', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Options for developers and debugging.',
							'saman-seo'
						) }
					</p>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>
								{ __( 'Enable REST API', 'saman-seo' ) }
							</label>
							<p className="settings-help">
								{ __(
									'Allow external access to SEO data via REST.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.enable_rest_api }
									onChange={ ( e ) =>
										updateSetting(
											'enable_rest_api',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>

					<div className="settings-row compact">
						<div className="settings-label">
							<label>{ __( 'Debug Mode', 'saman-seo' ) }</label>
							<p className="settings-help">
								{ __(
									'Enable verbose logging and debug output.',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="settings-control">
							<label className="toggle">
								<input
									type="checkbox"
									checked={ settings.debug_mode }
									onChange={ ( e ) =>
										updateSetting(
											'debug_mode',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>
			</div>

			<aside className="settings-sidebar">
				<div className="side-card warning">
					<h4>{ __( 'Caution', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'Changes to advanced settings may affect site functionality. Make sure you understand what each option does.',
							'saman-seo'
						) }
					</p>
				</div>
			</aside>
		</div>
	);
};

// Tools Tab
const ToolsTab = ( {
	settings,
	updateSetting,
	onExport,
	onImport,
	onReset,
	onResetWizard,
	resettingWizard,
	importFile,
	setImportFile,
	systemInfo,
} ) => {
	const [ deletingData, setDeletingData ] = useState( false );
	const handleDeleteAllData = async () => {
		if (
			! window.confirm(
				__(
					'Are you sure you want to delete all Saman SEO data? This will remove redirects, 404 logs, settings, and post meta. This cannot be undone.',
					'saman-seo'
				)
			)
		) {
			return;
		}
		setDeletingData( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/tools/delete-all-data',
				method: 'POST',
			} );
			alert(
				__(
					'All plugin data has been deleted. The page will reload.',
					'saman-seo'
				)
			);
			window.location.reload();
		} catch ( error ) {
			console.error( 'Failed to delete data:', error );
			alert( __( 'Failed to delete plugin data.', 'saman-seo' ) );
		} finally {
			setDeletingData( false );
		}
	};
	return (
		<div className="settings-layout">
			<div className="settings-main">
				<section className="panel">
					<h3>{ __( 'Import / Export', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Backup your settings or transfer them to another site.',
							'saman-seo'
						) }
					</p>

					<div className="tools-actions">
						<div className="tool-action">
							<h4>{ __( 'Export Settings', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Download all plugin settings as a JSON file.',
									'saman-seo'
								) }
							</p>
							<button
								type="button"
								className="button primary"
								onClick={ onExport }
							>
								{ __( 'Export Settings', 'saman-seo' ) }
							</button>
						</div>

						<div className="tool-action">
							<h4>{ __( 'Import Settings', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Upload a previously exported JSON file.',
									'saman-seo'
								) }
							</p>
							<div className="import-controls">
								<input
									type="file"
									accept=".json"
									onChange={ ( e ) =>
										setImportFile( e.target.files[ 0 ] )
									}
									id="import-file"
								/>
								<label
									htmlFor="import-file"
									className="button ghost"
								>
									{ importFile
										? importFile.name
										: __( 'Choose File', 'saman-seo' ) }
								</label>
								<button
									type="button"
									className="button"
									onClick={ onImport }
									disabled={ ! importFile }
								>
									{ __( 'Import', 'saman-seo' ) }
								</button>
							</div>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Database Tools', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Manage plugin data stored in your database.',
							'saman-seo'
						) }
					</p>

					<div className="tools-actions">
						<div className="tool-action">
							<h4>{ __( 'Clear Cache', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Clear all cached SEO data (schema, sitemaps, etc).',
									'saman-seo'
								) }
							</p>
							<button type="button" className="button ghost">
								{ __( 'Clear Cache', 'saman-seo' ) }
							</button>
						</div>

						<div className="tool-action">
							<h4>{ __( 'Reindex Content', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Rebuild internal link index and content analysis.',
									'saman-seo'
								) }
							</p>
							<button type="button" className="button ghost">
								{ __( 'Reindex', 'saman-seo' ) }
							</button>
						</div>
					</div>
				</section>

				<section className="panel">
					<h3>{ __( 'Setup Wizard', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Run the setup wizard again to reconfigure the plugin.',
							'saman-seo'
						) }
					</p>

					<div className="tools-actions">
						<div className="tool-action">
							<h4>{ __( 'Reset Setup Wizard', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Show the setup wizard on next page load. Existing settings will be preserved.',
									'saman-seo'
								) }
							</p>
							<button
								type="button"
								className="button ghost"
								onClick={ onResetWizard }
								disabled={ resettingWizard }
							>
								{ resettingWizard
									? __( 'Resetting\u2026', 'saman-seo' )
									: __( 'Reset Wizard', 'saman-seo' ) }
							</button>
						</div>
					</div>
				</section>

				<section className="panel danger-zone">
					<h3>{ __( 'Danger Zone', 'saman-seo' ) }</h3>
					<p className="panel-desc">
						{ __(
							'Destructive actions that cannot be undone.',
							'saman-seo'
						) }
					</p>

					<div className="tools-actions">
						<div className="tool-action">
							<h4>{ __( 'Reset to Defaults', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Reset all settings to their default values.',
									'saman-seo'
								) }
							</p>
							<button
								type="button"
								className="button danger"
								onClick={ onReset }
							>
								{ __( 'Reset All Settings', 'saman-seo' ) }
							</button>
						</div>

						<div className="tool-action">
							<h4>{ __( 'Delete All Data', 'saman-seo' ) }</h4>
							<p className="muted">
								{ __(
									'Remove all plugin data including redirects, 404 logs, and meta.',
									'saman-seo'
								) }
							</p>
							<button
								type="button"
								className="button danger"
								onClick={ handleDeleteAllData }
								disabled={ deletingData }
							>
								{ deletingData
									? __( 'Deleting\u2026', 'saman-seo' )
									: __( 'Delete All Data', 'saman-seo' ) }
							</button>
						</div>

						<div className="tool-action">
							<h4>
								{ __(
									'Delete Data on Uninstall',
									'saman-seo'
								) }
							</h4>
							<p className="muted">
								{ __(
									'Remove all plugin data when the plugin is uninstalled.',
									'saman-seo'
								) }
							</p>
							<label className="toggle">
								<input
									type="checkbox"
									checked={
										settings.delete_data_on_uninstall
									}
									onChange={ ( e ) =>
										updateSetting(
											'delete_data_on_uninstall',
											e.target.checked
										)
									}
								/>
								<span className="toggle-track" />
							</label>
						</div>
					</div>
				</section>
			</div>

			<aside className="settings-sidebar">
				<div className="side-card highlight">
					<h4>{ __( 'Plugin Info', 'saman-seo' ) }</h4>
					<div className="info-rows">
						<div className="info-row">
							<span>{ __( 'Version', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.plugin_version ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'WordPress', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.wordpress ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'PHP', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.php ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'MySQL', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.mysql ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
					</div>
				</div>

				<div className="side-card">
					<h4>{ __( 'Environment', 'saman-seo' ) }</h4>
					<div className="info-rows">
						<div className="info-row">
							<span>{ __( 'Memory Limit', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.memory_limit ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'Max Upload', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.max_upload_size ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'Timezone', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.timezone ||
									__( 'UTC', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'Debug Mode', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.debug_mode
									? __( 'Enabled', 'saman-seo' )
									: __( 'Disabled', 'saman-seo' ) }
							</code>
						</div>
					</div>
				</div>

				<div className="side-card">
					<h4>{ __( 'Theme', 'saman-seo' ) }</h4>
					<div className="info-rows">
						<div className="info-row">
							<span>{ __( 'Active Theme', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.theme ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
						<div className="info-row">
							<span>{ __( 'Theme Version', 'saman-seo' ) }</span>
							<code>
								{ systemInfo.theme_version ||
									__( 'Unknown', 'saman-seo' ) }
							</code>
						</div>
					</div>
				</div>

				<div className="side-card">
					<h4>{ __( 'Need Help?', 'saman-seo' ) }</h4>
					<p className="muted">
						{ __(
							'Check the documentation or contact support.',
							'saman-seo'
						) }
					</p>
					<a
						href="https://github.com/SamanLabs/Saman-SEO/blob/main/docs/GETTING_STARTED.md"
						className="button ghost"
					>
						{ __( 'View Documentation', 'saman-seo' ) }
					</a>
				</div>
			</aside>
		</div>
	);
};
export default Settings;
