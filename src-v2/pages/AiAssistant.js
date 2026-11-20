import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { confirmDialog } from '../components/Dialogs';
const AiAssistant = () => {
	// Loading states
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ resetting, setResetting ] = useState( false );
	const [ generating, setGenerating ] = useState( false );

	// Settings state (prompts only - API keys deprecated)
	const [ settings, setSettings ] = useState( {
		ai_prompt_system: '',
		ai_prompt_title: '',
		ai_prompt_description: '',
	} );

	// API status from AI Controller (includes WP AI Pilot status)
	const [ apiStatus, setApiStatus ] = useState( {
		configured: false,
		status: 'not_configured',
		message: __( 'Not configured', 'saman-seo' ),
		provider: 'none',
		ai_pilot: null,
	} );

	// Test generation
	const [ testContent, setTestContent ] = useState( '' );
	const [ generatedTitle, setGeneratedTitle ] = useState( '' );
	const [ generatedDescription, setGeneratedDescription ] = useState( '' );

	// Messages
	const [ message, setMessage ] = useState( {
		type: '',
		text: '',
	} );

	// Fetch data
	const fetchData = useCallback( async () => {
		setLoading( true );
		try {
			const [ settingsRes, statusRes ] = await Promise.all( [
				apiFetch( {
					path: '/saman-seo/v1/ai/settings',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/ai/status',
				} ),
			] );
			if ( settingsRes.success ) {
				setSettings( ( prev ) => ( {
					...prev,
					ai_prompt_system: settingsRes.data.ai_prompt_system || '',
					ai_prompt_title: settingsRes.data.ai_prompt_title || '',
					ai_prompt_description:
						settingsRes.data.ai_prompt_description || '',
				} ) );
			}
			if ( statusRes.success ) setApiStatus( statusRes.data );
		} catch ( error ) {
			console.error( 'Failed to fetch AI settings:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to load AI settings.', 'saman-seo' ),
			} );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Save prompt settings only
	const handleSaveSettings = async () => {
		setSaving( true );
		setMessage( {
			type: '',
			text: '',
		} );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/ai/settings',
				method: 'POST',
				data: {
					ai_prompt_system: settings.ai_prompt_system,
					ai_prompt_title: settings.ai_prompt_title,
					ai_prompt_description: settings.ai_prompt_description,
				},
			} );
			if ( res.success ) {
				setMessage( {
					type: 'success',
					text: __(
						'Prompt settings saved successfully!',
						'saman-seo'
					),
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to save settings.', 'saman-seo' ),
			} );
		} finally {
			setSaving( false );
		}
	};

	// Reset to defaults
	const handleReset = async () => {
		if (
			! await confirmDialog( __( 'Reset prompts to defaults?', 'saman-seo' ), { confirmLabel: __( 'Reset', 'saman-seo' ), danger: true } )
		) {
			return;
		}
		setResetting( true );
		setMessage( {
			type: '',
			text: '',
		} );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/ai/reset',
				method: 'POST',
			} );
			if ( res.success ) {
				setSettings( ( prev ) => ( {
					...prev,
					ai_prompt_system: res.data.ai_prompt_system || '',
					ai_prompt_title: res.data.ai_prompt_title || '',
					ai_prompt_description: res.data.ai_prompt_description || '',
				} ) );
				setMessage( {
					type: 'success',
					text: __( 'Prompts restored to defaults.', 'saman-seo' ),
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to reset settings:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to reset settings.', 'saman-seo' ),
			} );
		} finally {
			setResetting( false );
		}
	};

	// Test generation
	const handleGenerate = async () => {
		if ( ! testContent.trim() ) {
			setMessage( {
				type: 'error',
				text: __(
					'Please enter some content to analyze.',
					'saman-seo'
				),
			} );
			return;
		}
		if ( ! apiStatus.configured ) {
			setMessage( {
				type: 'error',
				text: __(
					'Please configure WP AI Pilot to enable AI generation.',
					'saman-seo'
				),
			} );
			return;
		}
		setGenerating( true );
		setMessage( {
			type: '',
			text: '',
		} );
		setGeneratedTitle( '' );
		setGeneratedDescription( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/ai/generate',
				method: 'POST',
				data: {
					content: testContent,
					type: 'both',
				},
			} );
			if ( res.success ) {
				setGeneratedTitle( res.data.title || '' );
				setGeneratedDescription( res.data.description || '' );
				setMessage( {
					type: 'success',
					text: __( 'Generation complete!', 'saman-seo' ),
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to generate:', error );
			setMessage( {
				type: 'error',
				text:
					error.message ||
					__( 'Failed to generate content.', 'saman-seo' ),
			} );
		} finally {
			setGenerating( false );
		}
	};

	// The controller reports a ready connection as 'Saman-ai'; 'wp-ai-pilot'
	// is the older name for the same provider.
	const isAiConnected = [ 'Saman-ai', 'wp-ai-pilot' ].includes(
		apiStatus.provider
	);

	// Get provider display name
	const getProviderName = () => {
		switch ( apiStatus.provider ) {
			case 'Saman-ai':
			case 'wp-ai-pilot':
				return 'WP AI Pilot';
			case 'native':
				return __( 'Native API keys (deprecated)', 'saman-seo' );
			default:
				return __( 'Not configured', 'saman-seo' );
		}
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<div>
						<h1>{ __( 'AI assistant', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'Configure AI-powered SEO content generation.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading AI settings\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	// Provider panel copy for the three connection states.
	let providerState;
	if ( isAiConnected ) {
		providerState = {
			title: __( 'Connected to WP AI Pilot', 'saman-seo' ),
			description: __(
				'AI features are powered by WP AI Pilot. Manage your API keys and models there.',
				'saman-seo'
			),
			href:
				apiStatus.ai_pilot?.settings_url ||
				'admin.php?page=wp-ai-pilot',
			action: __( 'Open WP AI Pilot settings', 'saman-seo' ),
		};
	} else if ( apiStatus.ai_pilot?.installed ) {
		providerState = {
			title: __( 'WP AI Pilot needs configuration', 'saman-seo' ),
			description: __(
				'WP AI Pilot is installed but not configured. Add an API key to enable AI features.',
				'saman-seo'
			),
			href:
				apiStatus.ai_pilot?.settings_url ||
				'admin.php?page=wp-ai-pilot',
			action: __( 'Configure WP AI Pilot', 'saman-seo' ),
		};
	} else {
		providerState = {
			title: __( 'Install WP AI Pilot', 'saman-seo' ),
			description: __(
				'AI features in Saman SEO are now powered by WP AI Pilot. Install it to enable AI-powered title and description generation.',
				'saman-seo'
			),
			href: 'plugin-install.php?s=wp+ai+pilot&tab=search',
			action: __( 'Install WP AI Pilot', 'saman-seo' ),
		};
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'AI assistant', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Configure AI-powered SEO content generation.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<button
						type="button"
						className="button ghost"
						onClick={ handleReset }
						disabled={ resetting }
					>
						{ resetting
							? __( 'Resetting\u2026', 'saman-seo' )
							: __( 'Reset defaults', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button primary"
						onClick={ handleSaveSettings }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save prompt settings', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ message.text && (
				<div className={ `notice-message ${ message.type }` }>
					{ message.text }
				</div>
			) }

			<div className="page-body">
				<div className="main-column">
					{ /* AI provider status */ }
					<section className="panel">
						<div className="card-header">
							<h3>{ __( 'AI provider', 'saman-seo' ) }</h3>
							<span
								className={ `pill ${
									apiStatus.configured ? 'success' : 'warning'
								}` }
							>
								{ apiStatus.configured
									? __( 'Connected', 'saman-seo' )
									: __( 'Not connected', 'saman-seo' ) }
							</span>
						</div>
						<div className="settings-form">
							<div className="settings-row">
								<div className="settings-label">
									<strong>{ providerState.title }</strong>
									<p className="settings-help">
										{ providerState.description }
									</p>
								</div>
								<div className="settings-control">
									<a
										href={ providerState.href }
										className="button"
									>
										{ providerState.action }
									</a>
								</div>
							</div>
						</div>
						<p className="muted">
							<strong>
								{ __(
									'API key management deprecated',
									'saman-seo'
								) }
							</strong>{ ' ' }
							{ __(
								'Direct API key configuration has been moved to WP AI Pilot. This provides centralized AI management across all your WordPress plugins.',
								'saman-seo'
							) }
						</p>
					</section>

					{ /* Prompts */ }
					<section className="panel">
						<h3>{ __( 'Prompt configuration', 'saman-seo' ) }</h3>
						<div className="settings-form">
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="system-prompt">
										{ __( 'System prompt', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'Base instructions for every request',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<textarea
										id="system-prompt"
										value={ settings.ai_prompt_system }
										onChange={ ( e ) =>
											setSettings( ( prev ) => ( {
												...prev,
												ai_prompt_system:
													e.target.value,
											} ) )
										}
										rows="3"
										placeholder={ __(
											'You are an SEO assistant\u2026',
											'saman-seo'
										) }
									/>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="title-prompt">
										{ __( 'Title prompt', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											'How to craft titles',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<textarea
										id="title-prompt"
										value={ settings.ai_prompt_title }
										onChange={ ( e ) =>
											setSettings( ( prev ) => ( {
												...prev,
												ai_prompt_title: e.target.value,
											} ) )
										}
										rows="3"
										placeholder={ __(
											'Write an SEO meta title\u2026',
											'saman-seo'
										) }
									/>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="desc-prompt">
										{ __(
											'Description prompt',
											'saman-seo'
										) }
									</label>
									<p className="settings-help">
										{ __(
											'How to craft descriptions',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<textarea
										id="desc-prompt"
										value={ settings.ai_prompt_description }
										onChange={ ( e ) =>
											setSettings( ( prev ) => ( {
												...prev,
												ai_prompt_description:
													e.target.value,
											} ) )
										}
										rows="3"
										placeholder={ __(
											'Write a meta description\u2026',
											'saman-seo'
										) }
									/>
								</div>
							</div>
						</div>
					</section>

					{ /* Test generation */ }
					<section className="panel">
						<div className="card-header">
							<h3>{ __( 'Test generation', 'saman-seo' ) }</h3>
							{ apiStatus.provider &&
								apiStatus.provider !== 'none' && (
									<span className="muted">
										{ __( 'via', 'saman-seo' ) }{ ' ' }
										{ getProviderName() }
									</span>
								) }
						</div>
						<div className="ai-test-input">
							<textarea
								value={ testContent }
								onChange={ ( e ) =>
									setTestContent( e.target.value )
								}
								rows="4"
								aria-label={ __(
									'Test generation',
									'saman-seo'
								) }
								placeholder={ __(
									'Paste content here to test AI generation. Provide at least 100 words for best results\u2026',
									'saman-seo'
								) }
								disabled={ ! apiStatus.configured }
							/>
							<button
								type="button"
								className="button"
								onClick={ handleGenerate }
								disabled={
									generating || ! apiStatus.configured
								}
							>
								{ generating
									? __( 'Generating\u2026', 'saman-seo' )
									: __( 'Generate', 'saman-seo' ) }
							</button>
						</div>

						{ ! apiStatus.configured && (
							<p className="muted">
								{ __(
									'Configure WP AI Pilot to test AI generation.',
									'saman-seo'
								) }
							</p>
						) }

						{ ( generatedTitle || generatedDescription ) && (
							<div className="ai-results">
								{ generatedTitle && (
									<div className="ai-result-item">
										<div className="ai-result-header">
											<span className="ai-result-label">
												{ __( 'Title', 'saman-seo' ) }
											</span>
											<span className="ai-result-count">
												{ generatedTitle.length }{ ' ' }
												{ __( 'chars', 'saman-seo' ) }
											</span>
										</div>
										<div className="ai-result-value">
											{ generatedTitle }
										</div>
									</div>
								) }
								{ generatedDescription && (
									<div className="ai-result-item">
										<div className="ai-result-header">
											<span className="ai-result-label">
												{ __(
													'Description',
													'saman-seo'
												) }
											</span>
											<span className="ai-result-count">
												{ generatedDescription.length }{ ' ' }
												{ __( 'chars', 'saman-seo' ) }
											</span>
										</div>
										<div className="ai-result-value">
											{ generatedDescription }
										</div>
									</div>
								) }
							</div>
						) }
					</section>
				</div>
			</div>
		</div>
	);
};
export default AiAssistant;
