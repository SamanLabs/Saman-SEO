import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { AssistantProvider, AssistantChat } from '../assistants';

// Get AI status from global settings
import { __ } from '@wordpress/i18n';
const globalSettings = window.samanSeoV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;

/**
 * Assistants page - Management view with create + stats.
 */
const Assistants = ( { initialAssistant = null } ) => {
	const [ view, setView ] = useState( 'list' ); // 'list', 'chat', 'create', 'edit'
	const [ assistants, setAssistants ] = useState( [] );
	const [ customAssistants, setCustomAssistants ] = useState( [] );
	const [ stats, setStats ] = useState( null );
	const [ selectedAssistant, setSelectedAssistant ] = useState( null );
	const [ editingAssistant, setEditingAssistant ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );

	// Form state for create/edit
	const [ form, setForm ] = useState( {
		name: '',
		description: '',
		system_prompt: '',
		initial_message: '',
		icon: '🤖',
		color: '#6366f1',
		model_id: '',
		is_active: true,
	} );
	const fetchData = useCallback( async () => {
		setLoading( true );
		try {
			const [ assistantsRes, customRes, statsRes ] = await Promise.all( [
				apiFetch( {
					path: '/saman-seo/v1/assistants',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/assistants/custom',
				} ),
				apiFetch( {
					path: '/saman-seo/v1/assistants/stats',
				} ),
			] );
			if ( assistantsRes.success ) {
				setAssistants( assistantsRes.data );
			}
			if ( customRes.success ) {
				setCustomAssistants( customRes.data );
			}
			if ( statsRes.success ) {
				setStats( statsRes.data );
			}
		} catch ( err ) {
			console.error( 'Failed to fetch assistants:', err );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Handle initial assistant from URL
	useEffect( () => {
		if ( initialAssistant && assistants.length > 0 ) {
			const found = assistants.find( ( a ) => a.id === initialAssistant );
			if ( found ) {
				setSelectedAssistant( found );
				setView( 'chat' );
			}
		}
	}, [ initialAssistant, assistants ] );
	const handleSelectAssistant = ( assistant ) => {
		setSelectedAssistant( assistant );
		setView( 'chat' );
	};
	const handleBack = () => {
		setSelectedAssistant( null );
		setEditingAssistant( null );
		setView( 'list' );
		setForm( {
			name: '',
			description: '',
			system_prompt: '',
			initial_message: '',
			icon: '🤖',
			color: '#6366f1',
			model_id: '',
			is_active: true,
		} );
	};
	const handleCreateNew = () => {
		setForm( {
			name: '',
			description: '',
			system_prompt: '',
			initial_message: '',
			icon: '🤖',
			color: '#6366f1',
			model_id: '',
			is_active: true,
		} );
		setEditingAssistant( null );
		setView( 'create' );
	};
	const handleEdit = ( assistant ) => {
		setForm( {
			name: assistant.name || '',
			description: assistant.description || '',
			system_prompt: assistant.system_prompt || '',
			initial_message: assistant.initial_message || '',
			icon: assistant.icon || '🤖',
			color: assistant.color || '#6366f1',
			model_id: assistant.model_id || '',
			is_active: assistant.is_active !== false,
		} );
		setEditingAssistant( assistant );
		setView( 'edit' );
	};
	const handleSave = async () => {
		if ( ! form.name || ! form.system_prompt ) {
			alert( __( 'Name and system prompt are required.', 'saman-seo' ) );
			return;
		}
		setSaving( true );
		try {
			if ( editingAssistant ) {
				await apiFetch( {
					path: `/saman-seo/v1/assistants/custom/${ editingAssistant.id }`,
					method: 'PUT',
					data: form,
				} );
			} else {
				await apiFetch( {
					path: '/saman-seo/v1/assistants/custom',
					method: 'POST',
					data: form,
				} );
			}
			await fetchData();
			handleBack();
		} catch ( err ) {
			console.error( 'Failed to save assistant:', err );
			alert( __( 'Failed to save assistant.', 'saman-seo' ) );
		} finally {
			setSaving( false );
		}
	};
	const handleDelete = async ( id ) => {
		if (
			! window.confirm(
				__(
					'Are you sure you want to delete this assistant?',
					'saman-seo'
				)
			)
		) {
			return;
		}
		try {
			await apiFetch( {
				path: `/saman-seo/v1/assistants/custom/${ id }`,
				method: 'DELETE',
			} );
			await fetchData();
		} catch ( err ) {
			console.error( 'Failed to delete assistant:', err );
		}
	};
	const updateForm = ( key, value ) => {
		setForm( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};
	const icons = [
		'🤖',
		'💬',
		'📊',
		'🎯',
		'✨',
		'🔍',
		'📝',
		'💡',
		'🚀',
		'⚡',
	];
	const colors = [
		'#3b82f6',
		'#8b5cf6',
		'#6366f1',
		'#ec4899',
		'#f59e0b',
		'#10b981',
		'#ef4444',
		'#6b7280',
	];
	if ( loading ) {
		return (
			<div className="page">
				<div className="loading-state">
					{ __( 'Loading assistants\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}

	// Chat view
	if ( view === 'chat' && selectedAssistant ) {
		return (
			<div className="page assistants-page">
				<div className="page-header page-header--with-back">
					<button
						type="button"
						className="back-button"
						onClick={ handleBack }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M15 18l-6-6 6-6" />
						</svg>
						<span>{ __( 'All Assistants', 'saman-seo' ) }</span>
					</button>
					<div className="page-header__info">
						<div
							className="page-header__icon"
							style={ {
								backgroundColor: `${ selectedAssistant.color }15`,
								color: selectedAssistant.color,
							} }
						>
							{ selectedAssistant.icon }
						</div>
						<div>
							<h1>{ selectedAssistant.name }</h1>
							<p>{ selectedAssistant.description }</p>
						</div>
					</div>
				</div>

				<div className="assistants-chat-container">
					<AssistantProvider
						key={ selectedAssistant.id }
						assistantId={ selectedAssistant.id }
						initialMessage={
							selectedAssistant.initial_message ||
							selectedAssistant.initialMessage
						}
					>
						<AssistantChat
							suggestedPrompts={
								selectedAssistant.suggested_prompts ||
								selectedAssistant.suggestedPrompts
							}
						/>
					</AssistantProvider>
				</div>
			</div>
		);
	}

	// Create/Edit form
	if ( view === 'create' || view === 'edit' ) {
		return (
			<div className="page">
				<div className="page-header page-header--with-back">
					<button
						type="button"
						className="back-button"
						onClick={ handleBack }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M15 18l-6-6 6-6" />
						</svg>
						<span>{ __( 'All Assistants', 'saman-seo' ) }</span>
					</button>
					<div>
						<h1>
							{ view === 'edit'
								? __( 'Edit Assistant', 'saman-seo' )
								: __( 'Create Assistant', 'saman-seo' ) }
						</h1>
						<p>
							{ __(
								'Configure your custom AI assistant.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>

				<div className="assistants-form">
					<div className="panel">
						<h3>{ __( 'Basic Info', 'saman-seo' ) }</h3>
						<div className="form-row">
							<label htmlFor="name">
								{ __( 'Name *', 'saman-seo' ) }
							</label>
							<input
								id="name"
								type="text"
								value={ form.name }
								onChange={ ( e ) =>
									updateForm( 'name', e.target.value )
								}
								placeholder={ __(
									'My SEO Assistant',
									'saman-seo'
								) }
							/>
						</div>
						<div className="form-row">
							<label htmlFor="description">
								{ __( 'Description', 'saman-seo' ) }
							</label>
							<input
								id="description"
								type="text"
								value={ form.description }
								onChange={ ( e ) =>
									updateForm( 'description', e.target.value )
								}
								placeholder={ __(
									'A helpful assistant for\u2026',
									'saman-seo'
								) }
							/>
						</div>
						<div className="form-row">
							<label>{ __( 'Icon', 'saman-seo' ) }</label>
							<div className="icon-picker">
								{ icons.map( ( icon ) => (
									<button
										key={ icon }
										type="button"
										className={ `icon-option ${
											form.icon === icon ? 'active' : ''
										}` }
										onClick={ () =>
											updateForm( 'icon', icon )
										}
									>
										{ icon }
									</button>
								) ) }
							</div>
						</div>
						<div className="form-row">
							<label>{ __( 'Color', 'saman-seo' ) }</label>
							<div className="color-picker">
								{ colors.map( ( color ) => (
									<button
										key={ color }
										type="button"
										className={ `color-option ${
											form.color === color ? 'active' : ''
										}` }
										style={ {
											backgroundColor: color,
										} }
										onClick={ () =>
											updateForm( 'color', color )
										}
									/>
								) ) }
							</div>
						</div>
					</div>

					<div className="panel">
						<h3>{ __( 'AI Configuration', 'saman-seo' ) }</h3>
						<div className="form-row">
							<label htmlFor="system_prompt">
								{ __( 'System Prompt *', 'saman-seo' ) }
							</label>
							<textarea
								id="system_prompt"
								value={ form.system_prompt }
								onChange={ ( e ) =>
									updateForm(
										'system_prompt',
										e.target.value
									)
								}
								placeholder={ __(
									'You are a helpful SEO assistant\u2026',
									'saman-seo'
								) }
								rows={ 6 }
							/>
							<p className="form-help">
								{ __(
									"Define the assistant's personality and expertise.",
									'saman-seo'
								) }
							</p>
						</div>
						<div className="form-row">
							<label htmlFor="initial_message">
								{ __( 'Welcome Message', 'saman-seo' ) }
							</label>
							<textarea
								id="initial_message"
								value={ form.initial_message }
								onChange={ ( e ) =>
									updateForm(
										'initial_message',
										e.target.value
									)
								}
								placeholder={ __(
									"Hi! I'm here to help with\u2026",
									'saman-seo'
								) }
								rows={ 2 }
							/>
						</div>
						<div className="form-row">
							<label htmlFor="model_id">
								{ __( 'Model (optional)', 'saman-seo' ) }
							</label>
							<input
								id="model_id"
								type="text"
								value={ form.model_id }
								onChange={ ( e ) =>
									updateForm( 'model_id', e.target.value )
								}
								placeholder={ __(
									'Leave empty to use default model',
									'saman-seo'
								) }
							/>
							<p className="form-help">
								{ __(
									'Use custom_ID for custom models (e.g., custom_1).',
									'saman-seo'
								) }
							</p>
						</div>
						<div className="form-row form-row--checkbox">
							<label>
								<input
									type="checkbox"
									checked={ form.is_active }
									onChange={ ( e ) =>
										updateForm(
											'is_active',
											e.target.checked
										)
									}
								/>
								<span>{ __( 'Active', 'saman-seo' ) }</span>
							</label>
						</div>
					</div>

					<div className="form-actions">
						<button
							type="button"
							className="button ghost"
							onClick={ handleBack }
						>
							{ __( 'Cancel', 'saman-seo' ) }
						</button>
						<button
							type="button"
							className="button primary"
							onClick={ handleSave }
							disabled={ saving }
						>
							{ saving
								? __( 'Saving\u2026', 'saman-seo' )
								: view === 'edit'
								? __( 'Save Changes', 'saman-seo' )
								: __( 'Create Assistant', 'saman-seo' ) }
						</button>
					</div>
				</div>
			</div>
		);
	}

	// List view (default)
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'AI Assistants', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Manage your AI assistants and track usage.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header__actions">
					{ aiProvider === 'wp-ai-pilot' && (
						<a
							href={
								aiPilot?.settingsUrl ||
								'admin.php?page=wp-ai-pilot'
							}
							className="button ghost"
						>
							<svg
								width="16"
								height="16"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
							</svg>
							{ __( 'WP AI Pilot', 'saman-seo' ) }
						</a>
					) }
					<button
						type="button"
						className="button primary"
						onClick={ handleCreateNew }
					>
						{ __( '+ Create Assistant', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ /* AI Not Configured Notice */ }
			{ ! aiEnabled && (
				<div className="assistants-notice">
					<div className="assistants-notice__icon">
						<svg
							width="32"
							height="32"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
						</svg>
					</div>
					<div className="assistants-notice__content">
						<h3>
							{ __(
								'AI Assistants Powered by WP AI Pilot',
								'saman-seo'
							) }
						</h3>
						{ aiPilot?.installed ? (
							<>
								<p>
									{ __(
										'WP AI Pilot is installed but needs configuration. Add an API key to enable AI assistants.',
										'saman-seo'
									) }
								</p>
								<a
									href={
										aiPilot.settingsUrl ||
										'admin.php?page=wp-ai-pilot'
									}
									className="button primary"
								>
									{ __(
										'Configure WP AI Pilot',
										'saman-seo'
									) }
								</a>
							</>
						) : (
							<>
								<p>
									{ __(
										'Install WP AI Pilot to access AI-powered assistants for SEO optimization, content generation, and more.',
										'saman-seo'
									) }
								</p>
								<a
									href="plugin-install.php?s=wp+ai+pilot&tab=search"
									className="button primary"
								>
									{ __( 'Install WP AI Pilot', 'saman-seo' ) }
								</a>
							</>
						) }
					</div>
				</div>
			) }

			{ /* Stats Cards */ }
			{ stats && (
				<div className="stats-grid">
					<div className="stat-card">
						<div className="stat-card__value">
							{ stats.total_messages }
						</div>
						<div className="stat-card__label">
							{ __( 'Total Messages', 'saman-seo' ) }
						</div>
					</div>
					<div className="stat-card">
						<div className="stat-card__value">{ stats.today }</div>
						<div className="stat-card__label">
							{ __( 'Today', 'saman-seo' ) }
						</div>
					</div>
					<div className="stat-card">
						<div className="stat-card__value">
							{ stats.this_week }
						</div>
						<div className="stat-card__label">
							{ __( 'This Week', 'saman-seo' ) }
						</div>
					</div>
					<div className="stat-card">
						<div className="stat-card__value">
							{ stats.this_month }
						</div>
						<div className="stat-card__label">
							{ __( 'This Month', 'saman-seo' ) }
						</div>
					</div>
				</div>
			) }

			{ /* Built-in Assistants */ }
			<div className="assistants-section">
				<h2>{ __( 'Built-in Assistants', 'saman-seo' ) }</h2>
				<div className="assistants-grid">
					{ assistants
						.filter( ( a ) => a.is_builtin )
						.map( ( assistant ) => (
							<button
								key={ assistant.id }
								type="button"
								className="assistant-card"
								onClick={ () =>
									handleSelectAssistant( assistant )
								}
							>
								<div
									className="assistant-card__icon"
									style={ {
										backgroundColor: `${ assistant.color }15`,
										color: assistant.color,
									} }
								>
									{ assistant.icon }
								</div>
								<div className="assistant-card__content">
									<h3 className="assistant-card__name">
										{ assistant.name }
									</h3>
									<p className="assistant-card__desc">
										{ assistant.description }
									</p>
								</div>
								<div
									className="assistant-card__arrow"
									style={ {
										color: assistant.color,
									} }
								>
									<svg
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
									>
										<path d="M9 18l6-6-6-6" />
									</svg>
								</div>
							</button>
						) ) }
				</div>
			</div>

			{ /* Custom Assistants */ }
			<div className="assistants-section">
				<h2>{ __( 'Custom Assistants', 'saman-seo' ) }</h2>
				{ customAssistants.length === 0 ? (
					<div className="empty-state">
						<p>
							{ __( 'No custom assistants yet.', 'saman-seo' ) }
						</p>
						<button
							type="button"
							className="button"
							onClick={ handleCreateNew }
						>
							{ __( 'Create your first assistant', 'saman-seo' ) }
						</button>
					</div>
				) : (
					<div className="assistants-grid">
						{ __( 'Delete', 'saman-seo' ) }
					</div>
				) }
			</div>
		</div>
	);
};
export default Assistants;
