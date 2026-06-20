import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

/**
 * Setup Wizard - First-time plugin configuration
 */
import { __ } from '@wordpress/i18n';
const Setup = ( { onComplete, onSkip } ) => {
	const [ step, setStep ] = useState( 1 );
	const [ loading, setLoading ] = useState( false );
	const [ aiStatus, setAiStatus ] = useState( null );

	// Form data across all steps
	const [ data, setData ] = useState( {
		// Step 2: Site Info
		site_type: '',
		primary_goal: '',
		industry: '',
		// Step 4: Quick Wins
		enable_sitemap: true,
		enable_404_log: true,
		enable_redirects: true,
		title_template: '{{post_title}} - {{site_title}}',
	} );
	const updateData = ( key, value ) => {
		setData( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};

	// Check AI plugin status when entering step 3
	useEffect( () => {
		if ( step === 3 && ! aiStatus ) {
			checkAiStatus();
		}
	}, [ step ] );
	const checkAiStatus = async () => {
		setAiStatus( {
			status: 'loading',
		} );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/setup/test-api',
				method: 'POST',
			} );
			setAiStatus( response.data || response );
		} catch ( err ) {
			setAiStatus( {
				status: 'error',
				message: __( 'Could not check AI status.', 'saman-seo' ),
			} );
		}
	};
	const handleNext = () => {
		if ( step < 5 ) setStep( step + 1 );
	};
	const handleBack = () => {
		if ( step > 1 ) setStep( step - 1 );
	};
	const handleComplete = async () => {
		setLoading( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/setup/complete',
				method: 'POST',
				data,
			} );
			if ( onComplete ) onComplete();
		} catch ( err ) {
			console.error( 'Failed to save setup:', err );
		} finally {
			setLoading( false );
		}
	};
	const handleSkip = async () => {
		try {
			await apiFetch( {
				path: '/saman-seo/v1/setup/skip',
				method: 'POST',
			} );
		} catch ( err ) {
			// Ignore errors, just navigate away
		}
		if ( onSkip ) onSkip();
	};
	const siteTypes = [
		{
			value: 'blog',
			label: __( 'Blog / News', 'saman-seo' ),
			icon: '📝',
		},
		{
			value: 'business',
			label: __( 'Business / Company', 'saman-seo' ),
			icon: '🏢',
		},
		{
			value: 'ecommerce',
			label: __( 'E-commerce / Store', 'saman-seo' ),
			icon: '🛒',
		},
		{
			value: 'portfolio',
			label: __( 'Portfolio / Personal', 'saman-seo' ),
			icon: '🎨',
		},
		{
			value: 'agency',
			label: __( 'Agency / Services', 'saman-seo' ),
			icon: '💼',
		},
		{
			value: 'nonprofit',
			label: __( 'Non-profit / Charity', 'saman-seo' ),
			icon: '❤️',
		},
	];
	const goals = [
		{
			value: 'traffic',
			label: __( 'Get more traffic', 'saman-seo' ),
			icon: '📈',
		},
		{
			value: 'leads',
			label: __( 'Generate leads', 'saman-seo' ),
			icon: '📋',
		},
		{
			value: 'sales',
			label: __( 'Increase sales', 'saman-seo' ),
			icon: '💰',
		},
		{
			value: 'brand',
			label: __( 'Build brand awareness', 'saman-seo' ),
			icon: '🌟',
		},
	];
	return (
		<div className="setup-wizard">
			{ /* Progress bar */ }
			<div className="setup-progress">
				<div
					className="setup-progress__bar"
					style={ {
						width: `${ ( step / 5 ) * 100 }%`,
					} }
				/>
			</div>

			<div className="setup-content">
				{ /* Step 1: Welcome */ }
				{ step === 1 && (
					<div className="setup-step setup-step--welcome">
						<div className="setup-step__icon">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M5 12h14M12 5l7 7-7 7" />
							</svg>
						</div>
						<h1>{ __( 'Welcome to Saman SEO', 'saman-seo' ) }</h1>
						<p className="setup-step__subtitle">
							{ __(
								"Let's get your site ready for search engines. This will only take a minute.",
								'saman-seo'
							) }
						</p>

						<div className="setup-features">
							<div className="setup-feature">
								<span className="setup-feature__icon">🚀</span>
								<span>
									{ __(
										'AI-powered optimization',
										'saman-seo'
									) }
								</span>
							</div>
							<div className="setup-feature">
								<span className="setup-feature__icon">📊</span>
								<span>
									{ __(
										'Real-time SEO analysis',
										'saman-seo'
									) }
								</span>
							</div>
							<div className="setup-feature">
								<span className="setup-feature__icon">🔧</span>
								<span>
									{ __( 'Easy-to-use tools', 'saman-seo' ) }
								</span>
							</div>
						</div>

						<div className="setup-actions">
							<button
								type="button"
								className="button primary large"
								onClick={ handleNext }
							>
								{ __( "Let's Get Started", 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="button ghost"
								onClick={ handleSkip }
							>
								{ __( 'Skip for now', 'saman-seo' ) }
							</button>
						</div>
					</div>
				) }

				{ /* Step 2: Site Info */ }
				{ __( 'e.g., Technology, Health, Finance\u2026', 'saman-seo' ) }

				{ /* Step 3: AI Status */ }
				{ step === 3 && (
					<div className="setup-step">
						<span className="setup-step__number">
							{ __( 'Step 2 of 4', 'saman-seo' ) }
						</span>
						<h2>{ __( 'AI Features', 'saman-seo' ) }</h2>
						<p className="setup-step__subtitle">
							{ __(
								'AI-powered features are provided by the Saman Labs AI plugin.',
								'saman-seo'
							) }
						</p>

						{ aiStatus?.status === 'loading' && (
							<div className="setup-info-box">
								<p>
									{ __(
										'Checking AI status\u2026',
										'saman-seo'
									) }
								</p>
							</div>
						) }

						{ aiStatus?.status === 'ready' && (
							<div className="setup-test-result setup-test-result--success">
								{ __(
									'Saman Labs AI is ready! AI features are available.',
									'saman-seo'
								) }
							</div>
						) }

						{ aiStatus?.status === 'not_installed' && (
							<div className="setup-info-box">
								<h4>
									{ __(
										'Saman Labs AI Not Installed',
										'saman-seo'
									) }
								</h4>
								<p>
									{ __(
										'Install the Saman Labs AI plugin to enable AI-powered features like content suggestions and meta generation.',
										'saman-seo'
									) }
								</p>
								<a
									href={ aiStatus.install_url }
									className="button"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Install Plugin', 'saman-seo' ) }
								</a>
							</div>
						) }

						{ aiStatus?.status === 'not_active' && (
							<div className="setup-info-box">
								<h4>
									{ __(
										'Saman Labs AI Not Active',
										'saman-seo'
									) }
								</h4>
								<p>
									{ __(
										'The plugin is installed but needs to be activated.',
										'saman-seo'
									) }
								</p>
								<a
									href={ aiStatus.plugins_url }
									className="button"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Activate Plugin', 'saman-seo' ) }
								</a>
							</div>
						) }

						{ aiStatus?.status === 'not_configured' && (
							<div className="setup-info-box">
								<h4>
									{ __(
										'Saman Labs AI Not Configured',
										'saman-seo'
									) }
								</h4>
								<p>
									{ __(
										'The plugin is active but needs to be configured with your AI provider.',
										'saman-seo'
									) }
								</p>
								<a
									href={ aiStatus.settings_url }
									className="button"
									target="_blank"
									rel="noopener noreferrer"
								>
									{ __( 'Configure AI', 'saman-seo' ) }
								</a>
							</div>
						) }

						{ aiStatus?.status === 'error' && (
							<div className="setup-test-result setup-test-result--error">
								{ __(
									'Could not check AI status. You can configure this later in Settings.',
									'saman-seo'
								) }
							</div>
						) }

						<div className="setup-actions">
							<button
								type="button"
								className="button ghost"
								onClick={ handleBack }
							>
								{ __( 'Back', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="button primary"
								onClick={ handleNext }
							>
								{ __( 'Continue', 'saman-seo' ) }
							</button>
						</div>
					</div>
				) }

				{ /* Step 4: Quick Wins */ }
				{ step === 4 && (
					<div className="setup-step">
						<span className="setup-step__number">
							{ __( 'Step 3 of 4', 'saman-seo' ) }
						</span>
						<h2>{ __( 'Quick Wins', 'saman-seo' ) }</h2>
						<p className="setup-step__subtitle">
							{ __(
								'Enable these essential features to get started quickly.',
								'saman-seo'
							) }
						</p>

						<div className="setup-toggles">
							<label className="setup-toggle">
								<div className="setup-toggle__content">
									<strong>
										{ __( 'XML Sitemap', 'saman-seo' ) }
									</strong>
									<span>
										{ __(
											'Help search engines discover your content',
											'saman-seo'
										) }
									</span>
								</div>
								<input
									type="checkbox"
									checked={ data.enable_sitemap }
									onChange={ ( e ) =>
										updateData(
											'enable_sitemap',
											e.target.checked
										)
									}
								/>
								<span className="setup-toggle__switch" />
							</label>

							<label className="setup-toggle">
								<div className="setup-toggle__content">
									<strong>
										{ __(
											'404 Error Logging',
											'saman-seo'
										) }
									</strong>
									<span>
										{ __(
											'Track broken links and fix them',
											'saman-seo'
										) }
									</span>
								</div>
								<input
									type="checkbox"
									checked={ data.enable_404_log }
									onChange={ ( e ) =>
										updateData(
											'enable_404_log',
											e.target.checked
										)
									}
								/>
								<span className="setup-toggle__switch" />
							</label>

							<label className="setup-toggle">
								<div className="setup-toggle__content">
									<strong>
										{ __(
											'Redirects Manager',
											'saman-seo'
										) }
									</strong>
									<span>
										{ __(
											'Create and manage URL redirects',
											'saman-seo'
										) }
									</span>
								</div>
								<input
									type="checkbox"
									checked={ data.enable_redirects }
									onChange={ ( e ) =>
										updateData(
											'enable_redirects',
											e.target.checked
										)
									}
								/>
								<span className="setup-toggle__switch" />
							</label>
						</div>

						<div className="setup-section">
							<label className="setup-label">
								{ __( 'Title Template', 'saman-seo' ) }
							</label>
							<select
								className="setup-select"
								value={ data.title_template }
								onChange={ ( e ) =>
									updateData(
										'title_template',
										e.target.value
									)
								}
							>
								<option value="{{post_title}} - {{site_title}}">
									{ __(
										'Page Title - Site Name',
										'saman-seo'
									) }
								</option>
								<option value="{{post_title}} | {{site_title}}">
									{ __(
										'Page Title | Site Name',
										'saman-seo'
									) }
								</option>
								<option value="{{site_title}} - {{post_title}}">
									{ __(
										'Site Name - Page Title',
										'saman-seo'
									) }
								</option>
								<option value="{{post_title}}">
									{ __( 'Page Title Only', 'saman-seo' ) }
								</option>
							</select>
							<p className="setup-help">
								{ __(
									'How titles will appear in search results.',
									'saman-seo'
								) }
							</p>
						</div>

						<div className="setup-actions">
							<button
								type="button"
								className="button ghost"
								onClick={ handleBack }
							>
								{ __( 'Back', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="button primary"
								onClick={ handleNext }
							>
								{ __( 'Continue', 'saman-seo' ) }
							</button>
						</div>
					</div>
				) }

				{ /* Step 5: Done */ }
				{ step === 5 && (
					<div className="setup-step setup-step--done">
						<div className="setup-step__icon setup-step__icon--success">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M20 6L9 17l-5-5" />
							</svg>
						</div>
						<h1>{ __( "You're All Set!", 'saman-seo' ) }</h1>
						<p className="setup-step__subtitle">
							{ __(
								'Saman SEO is configured and ready to help you rank higher.',
								'saman-seo'
							) }
						</p>

						<div className="setup-summary">
							<div className="setup-summary__item">
								<span className="setup-summary__label">
									{ __( 'Site Type', 'saman-seo' ) }
								</span>
								<span className="setup-summary__value">
									{ siteTypes.find(
										( t ) => t.value === data.site_type
									)?.label || __( 'Not set', 'saman-seo' ) }
								</span>
							</div>
							<div className="setup-summary__item">
								<span className="setup-summary__label">
									{ __( 'AI Features', 'saman-seo' ) }
								</span>
								<span className="setup-summary__value">
									{ aiStatus?.status === 'ready'
										? __( 'Enabled', 'saman-seo' )
										: __( 'Not configured', 'saman-seo' ) }
								</span>
							</div>
							<div className="setup-summary__item">
								<span className="setup-summary__label">
									{ __( 'Features Enabled', 'saman-seo' ) }
								</span>
								<span className="setup-summary__value">
									{ [
										data.enable_sitemap && 'Sitemap',
										data.enable_404_log && '404 Log',
										data.enable_redirects && 'Redirects',
									]
										.filter( Boolean )
										.join( ', ' ) ||
										__( 'None', 'saman-seo' ) }
								</span>
							</div>
						</div>

						<div className="setup-actions">
							<button
								type="button"
								className="button primary large"
								onClick={ handleComplete }
								disabled={ loading }
							>
								{ loading
									? __( 'Saving\u2026', 'saman-seo' )
									: __( 'Go to Dashboard', 'saman-seo' ) }
							</button>
						</div>

						<p className="setup-note">
							{ __(
								'You can change these settings anytime in Settings.',
								'saman-seo'
							) }
						</p>
					</div>
				) }
			</div>
		</div>
	);
};
export default Setup;
