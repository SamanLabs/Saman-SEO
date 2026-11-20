import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { AssistantProvider, AssistantChat } from '../assistants';
import { assistantIconKeys, resolveAssistantIcon } from '../components/Icons';
import { KpiBand, MetricCard } from '../components/Kpi';

// Get AI status from global settings
import { __, _n, sprintf } from '@wordpress/i18n';
import { confirmDialog, toast } from '../components/Dialogs';
const globalSettings = window.samanSeoV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;

/**
 * Renders an assistant's stored icon key as an SVG
 * (legacy emoji values are resolved to their SVG equivalent).
 *
 * @param {Object} props      Component props.
 * @param {string} props.icon Icon key or legacy emoji value.
 *
 * @return {*} The resolved icon element.
 */
const AssistantIcon = ( { icon } ) => {
	const IconComponent = resolveAssistantIcon( icon );
	return <IconComponent />;
};

/**
 * Assistants page - Management view with create + stats.
 */
// The database returns the flag as "0" / "1"; a missing flag means active.
const isActive = ( assistant ) =>
	! [ false, 0, '0', '' ].includes( assistant?.is_active ?? true );

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
		icon: 'bot',
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
			icon: 'bot',
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
			icon: 'bot',
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
			icon: assistant.icon || 'bot',
			color: assistant.color || '#6366f1',
			model_id: assistant.model_id || '',
			is_active: isActive( assistant ),
		} );
		setEditingAssistant( assistant );
		setView( 'edit' );
	};
	const handleSave = async () => {
		if ( ! form.name || ! form.system_prompt ) {
			toast( __( 'Name and system prompt are required.', 'saman-seo' ), 'error' );
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
			toast( __( 'Failed to save assistant.', 'saman-seo' ), 'error' );
		} finally {
			setSaving( false );
		}
	};
	const handleDelete = async ( id ) => {
		if (
			! await confirmDialog(
				__(
					'Are you sure you want to delete this assistant?',
					'saman-seo'
				),
				{ confirmLabel: __( 'Delete', 'saman-seo' ), danger: true }
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
	const icons = assistantIconKeys;
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
				<KpiBand loading count={ 4 } />
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
						<span>{ __( 'All assistants', 'saman-seo' ) }</span>
					</button>
					<div className="page-header__info">
						<div
							className="page-header__icon"
							style={ {
								backgroundColor: `${ selectedAssistant.color }15`,
								color: selectedAssistant.color,
							} }
						>
							<AssistantIcon icon={ selectedAssistant.icon } />
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
						<span>{ __( 'All assistants', 'saman-seo' ) }</span>
					</button>
					<div>
						<h1>
							{ view === 'edit'
								? __( 'Edit assistant', 'saman-seo' )
								: __( 'Create assistant', 'saman-seo' ) }
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
					<section className="panel">
						<h3>{ __( 'Basic info', 'saman-seo' ) }</h3>
						<div className="settings-form">
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="name">
										{ __( 'Name *', 'saman-seo' ) }
									</label>
								</div>
								<div className="settings-control">
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
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="description">
										{ __( 'Description', 'saman-seo' ) }
									</label>
								</div>
								<div className="settings-control">
									<input
										id="description"
										type="text"
										value={ form.description }
										onChange={ ( e ) =>
											updateForm(
												'description',
												e.target.value
											)
										}
										placeholder={ __(
											'A helpful assistant for\u2026',
											'saman-seo'
										) }
									/>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<strong>
										{ __( 'Icon', 'saman-seo' ) }
									</strong>
								</div>
								<div className="settings-control">
									<div className="icon-picker">
										{ icons.map( ( icon ) => (
											<button
												key={ icon }
												type="button"
												className={ `icon-option ${
													form.icon === icon
														? 'active'
														: ''
												}` }
												aria-label={ icon }
												aria-pressed={
													form.icon === icon
												}
												onClick={ () =>
													updateForm( 'icon', icon )
												}
											>
												<AssistantIcon icon={ icon } />
											</button>
										) ) }
									</div>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<strong>
										{ __( 'Color', 'saman-seo' ) }
									</strong>
								</div>
								<div className="settings-control">
									<div className="color-picker">
										{ colors.map( ( color ) => (
											<button
												key={ color }
												type="button"
												className={ `color-option ${
													form.color === color
														? 'active'
														: ''
												}` }
												aria-label={ color }
												aria-pressed={
													form.color === color
												}
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
						</div>
					</section>

					<section className="panel">
						<h3>{ __( 'AI configuration', 'saman-seo' ) }</h3>
						<div className="settings-form">
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="system_prompt">
										{ __( 'System prompt *', 'saman-seo' ) }
									</label>
									<p className="settings-help">
										{ __(
											"Define the assistant's personality and expertise.",
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
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
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="initial_message">
										{ __( 'Welcome message', 'saman-seo' ) }
									</label>
								</div>
								<div className="settings-control">
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
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<label htmlFor="model_id">
										{ __(
											'Model (optional)',
											'saman-seo'
										) }
									</label>
									<p className="settings-help">
										{ __(
											'Use custom_ID for custom models (e.g., custom_1).',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="settings-control">
									<input
										id="model_id"
										type="text"
										value={ form.model_id }
										onChange={ ( e ) =>
											updateForm(
												'model_id',
												e.target.value
											)
										}
										placeholder={ __(
											'Leave empty to use default model',
											'saman-seo'
										) }
									/>
								</div>
							</div>
							<div className="settings-row">
								<div className="settings-label">
									<strong>
										{ __( 'Status', 'saman-seo' ) }
									</strong>
								</div>
								<div className="settings-control">
									<label className="checkbox-row">
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
										<span>
											{ __( 'Active', 'saman-seo' ) }
										</span>
									</label>
								</div>
							</div>
						</div>
					</section>

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
								? __( 'Save changes', 'saman-seo' )
								: __( 'Create assistant', 'saman-seo' ) }
						</button>
					</div>
				</div>
			</div>
		);
	}

	// List view (default)
	const builtinAssistants = assistants.filter( ( a ) => a.is_builtin );
	return (
		<div className="page">
			{ stats ? (
				<KpiBand label={ __( 'Assistant usage', 'saman-seo' ) }>
					<MetricCard
						label={ __( 'Total messages', 'saman-seo' ) }
						value={ Number(
							stats.total_messages || 0
						).toLocaleString() }
					/>
					<MetricCard
						label={ __( 'Today', 'saman-seo' ) }
						value={ Number( stats.today || 0 ).toLocaleString() }
					/>
					<MetricCard
						label={ __( 'This week', 'saman-seo' ) }
						value={ Number(
							stats.this_week || 0
						).toLocaleString() }
					/>
					<MetricCard
						label={ __( 'This month', 'saman-seo' ) }
						value={ Number(
							stats.this_month || 0
						).toLocaleString() }
					/>
				</KpiBand>
			) : (
				<KpiBand idle count={ 4 } />
			) }

			<div className="page-header">
				<div>
					<h1>{ __( 'AI assistants', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Manage your AI assistants and track usage.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					{ aiProvider === 'wp-ai-pilot' && (
						<a
							href={
								aiPilot?.settingsUrl ||
								'admin.php?page=wp-ai-pilot'
							}
							className="button ghost"
						>
							{ __( 'WP AI Pilot', 'saman-seo' ) }
						</a>
					) }
					<button
						type="button"
						className="button primary"
						onClick={ handleCreateNew }
					>
						{ __( '+ Create assistant', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ /* AI Not Configured Notice */ }
			{ ! aiEnabled && (
				<section className="panel">
					<h3>
						{ __(
							'AI assistants powered by WP AI Pilot',
							'saman-seo'
						) }
					</h3>
					{ aiPilot?.installed ? (
						<>
							<p className="muted">
								{ __(
									'WP AI Pilot is installed but needs configuration. Add an API key to enable AI assistants.',
									'saman-seo'
								) }
							</p>
							<div className="header-actions">
								<a
									href={
										aiPilot.settingsUrl ||
										'admin.php?page=wp-ai-pilot'
									}
									className="button"
								>
									{ __(
										'Configure WP AI Pilot',
										'saman-seo'
									) }
								</a>
							</div>
						</>
					) : (
						<>
							<p className="muted">
								{ __(
									'Install WP AI Pilot to access AI-powered assistants for SEO optimization, content generation, and more.',
									'saman-seo'
								) }
							</p>
							<div className="header-actions">
								<a
									href="plugin-install.php?s=wp+ai+pilot&tab=search"
									className="button"
								>
									{ __( 'Install WP AI Pilot', 'saman-seo' ) }
								</a>
							</div>
						</>
					) }
				</section>
			) }

			{ /* Built-in Assistants */ }
			{ builtinAssistants.length > 0 && (
				<section className="improvements-group">
					<h2 className="improvements-group__title">
						{ __( 'Built-in assistants', 'saman-seo' ) }
					</h2>
					<ul className="improvements-list">
						{ builtinAssistants.map( ( assistant ) => (
							<li
								key={ assistant.id }
								className="improvement-row"
							>
								<span
									className="assistant-row__icon"
									aria-hidden="true"
								>
									<AssistantIcon icon={ assistant.icon } />
								</span>
								<div className="improvement-row__main">
									<div className="improvement-row__title">
										<h3>{ assistant.name }</h3>
									</div>
									<p className="improvement-row__desc">
										{ assistant.description }
									</p>
								</div>
								<button
									type="button"
									className="button small"
									onClick={ () =>
										handleSelectAssistant( assistant )
									}
								>
									{ __( 'Open chat', 'saman-seo' ) }
								</button>
							</li>
						) ) }
					</ul>
				</section>
			) }

			{ /* Custom Assistants */ }
			<section className="improvements-group">
				<h2 className="improvements-group__title">
					{ __( 'Custom assistants', 'saman-seo' ) }
				</h2>
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
					<ul className="improvements-list">
						{ customAssistants.map( ( assistant ) => (
							<li
								key={ assistant.id }
								className="improvement-row"
							>
								<span
									className="assistant-row__icon"
									aria-hidden="true"
								>
									<AssistantIcon
										icon={ assistant.icon || 'bot' }
									/>
								</span>
								<div className="improvement-row__main">
									<div className="improvement-row__title">
										<h3>{ assistant.name }</h3>
										{ ! isActive( assistant ) && (
											<span className="pill">
												{ __(
													'Inactive',
													'saman-seo'
												) }
											</span>
										) }
									</div>
									<p className="improvement-row__desc">
										{ assistant.description ||
											__(
												'Custom assistant',
												'saman-seo'
											) }
									</p>
								</div>
								{ assistant.usage > 0 && (
									<span className="improvement-row__impact">
										{ sprintf(
											/* translators: %d: number of messages */
											_n(
												'%d message',
												'%d messages',
												assistant.usage,
												'saman-seo'
											),
											assistant.usage
										) }
									</span>
								) }
								<div className="assistant-row__actions">
									<button
										type="button"
										className="link-button danger"
										onClick={ () =>
											handleDelete( assistant.id )
										}
									>
										{ __( 'Delete', 'saman-seo' ) }
									</button>
									<button
										type="button"
										className="button small"
										onClick={ () =>
											handleEdit( assistant )
										}
									>
										{ __( 'Edit', 'saman-seo' ) }
									</button>
									<button
										type="button"
										className="button small"
										onClick={ () =>
											handleSelectAssistant( {
												...assistant,
												id: `custom_${ assistant.id }`,
											} )
										}
									>
										{ __( 'Open chat', 'saman-seo' ) }
									</button>
								</div>
							</li>
						) ) }
					</ul>
				) }
			</section>
		</div>
	);
};
export default Assistants;
