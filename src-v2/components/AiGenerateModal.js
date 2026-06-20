/**
 * AI Generate Modal Component
 *
 * Modal for generating SEO content (titles, descriptions) with AI
 * Supports including template variable context in the generation prompt
 */

import { useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Get AI status from global settings
import { __ } from '@wordpress/i18n';
const globalSettings = window.samanSeoV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;
const AiGenerateModal = ( {
	isOpen,
	onClose,
	onGenerate,
	fieldType = 'title',
	// 'title' or 'description'
	currentValue = '',
	placeholder = '',
	variableValues = {},
	context = {}, // Additional context like post type, taxonomy name, etc.
} ) => {
	const [ isGenerating, setIsGenerating ] = useState( false );
	const [ error, setError ] = useState( null );
	const [ includeVariables, setIncludeVariables ] = useState( true );
	const [ customPrompt, setCustomPrompt ] = useState( '' );
	const [ generatedResult, setGeneratedResult ] = useState( null );

	// Build context string from variable values
	const buildContextString = useCallback( () => {
		if ( ! includeVariables ) return '';
		const contextParts = [];

		// Add context info
		if ( context.type ) {
			contextParts.push( `Content type: ${ context.type }` );
		}
		if ( context.name ) {
			contextParts.push( `Name: ${ context.name }` );
		}

		// Add variable values
		const relevantVars = Object.entries( variableValues )
			.filter( ( [ key, value ] ) => value && typeof value === 'string' )
			.map(
				( [ key, value ] ) =>
					`${ key.replace( /_/g, ' ' ) }: ${ value }`
			);
		if ( relevantVars.length > 0 ) {
			contextParts.push( 'Available data:' );
			contextParts.push( ...relevantVars );
		}
		return contextParts.join( '\n' );
	}, [ includeVariables, variableValues, context ] );
	const handleGenerate = async () => {
		setIsGenerating( true );
		setError( null );
		setGeneratedResult( null );
		try {
			// Build the content for AI
			let content = '';
			if ( customPrompt ) {
				content = customPrompt;
			} else {
				content =
					buildContextString() ||
					'Generate SEO metadata for a website.';
			}

			// Add field-specific instructions
			if ( fieldType === 'title' ) {
				content +=
					'\n\nGenerate an SEO-optimized title (max 60 characters).';
			} else {
				content +=
					'\n\nGenerate an SEO-optimized meta description (max 155 characters).';
			}
			const response = await apiFetch( {
				path: '/saman-seo/v1/ai/generate',
				method: 'POST',
				data: {
					content,
					type: fieldType,
				},
			} );
			if ( response.success && response.data ) {
				const result =
					fieldType === 'title'
						? response.data.title
						: response.data.description;
				setGeneratedResult( result );
			} else {
				setError(
					response.message ||
						__( 'Failed to generate content', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message ||
					__( 'An error occurred during generation', 'saman-seo' )
			);
		} finally {
			setIsGenerating( false );
		}
	};
	const handleApply = () => {
		if ( generatedResult ) {
			onGenerate( generatedResult );
			handleClose();
		}
	};
	const handleClose = () => {
		setGeneratedResult( null );
		setError( null );
		setCustomPrompt( '' );
		onClose();
	};
	if ( ! isOpen ) return null;

	// Show configuration notice if AI is not enabled
	if ( ! aiEnabled ) {
		return (
			<div className="ai-generate-modal-overlay" onClick={ handleClose }>
				<div
					className="ai-generate-modal ai-generate-modal--notice"
					onClick={ ( e ) => e.stopPropagation() }
				>
					<div className="ai-generate-modal__header">
						<h3>
							<svg
								width="18"
								height="18"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<circle cx="12" cy="12" r="10" />
								<path d="M12 8v4m0 4h.01" />
							</svg>
							{ __( 'AI Not Configured', 'saman-seo' ) }
						</h3>
						<button
							type="button"
							className="ai-generate-modal__close"
							onClick={ handleClose }
							aria-label={ __( 'Close', 'saman-seo' ) }
						>
							<svg
								width="20"
								height="20"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M18 6L6 18M6 6l12 12" />
							</svg>
						</button>
					</div>

					<div className="ai-generate-modal__body">
						{ aiPilot?.installed ? (
							<div className="ai-generate-modal__notice ai-generate-modal__notice--warning">
								<div className="ai-generate-modal__notice-icon">
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
								<div className="ai-generate-modal__notice-content">
									<h4>
										{ __(
											'WP AI Pilot Needs Configuration',
											'saman-seo'
										) }
									</h4>
									<p>
										{ __(
											'WP AI Pilot is installed but not yet configured. Add an API key to enable AI-powered SEO suggestions.',
											'saman-seo'
										) }
									</p>
								</div>
							</div>
						) : (
							<div className="ai-generate-modal__notice ai-generate-modal__notice--info">
								<div className="ai-generate-modal__notice-icon">
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
								<div className="ai-generate-modal__notice-content">
									<h4>
										{ __(
											'Enhance with WP AI Pilot',
											'saman-seo'
										) }
									</h4>
									<p>
										{ __(
											'Install WP AI Pilot to unlock AI-powered title and meta description generation.',
											'saman-seo'
										) }
									</p>
								</div>
							</div>
						) }
					</div>

					<div className="ai-generate-modal__footer">
						<button
							type="button"
							className="button ghost"
							onClick={ handleClose }
						>
							{ __( 'Cancel', 'saman-seo' ) }
						</button>
						{ aiPilot?.installed ? (
							<a
								href={
									aiPilot.settingsUrl ||
									'admin.php?page=wp-ai-pilot'
								}
								className="button primary"
							>
								{ __( 'Configure WP AI Pilot', 'saman-seo' ) }
							</a>
						) : (
							<a
								href="plugin-install.php?s=wp+ai+pilot&tab=search"
								className="button primary"
							>
								{ __( 'Install WP AI Pilot', 'saman-seo' ) }
							</a>
						) }
					</div>
				</div>
			</div>
		);
	}
	return (
		<div className="ai-generate-modal-overlay" onClick={ handleClose }>
			<div
				className="ai-generate-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="ai-generate-modal__header">
					<h3>
						<svg
							width="18"
							height="18"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707" />
							<circle cx="12" cy="12" r="4" />
						</svg>
						{ __( 'Generate', 'saman-seo' ) }{ ' ' }
						{ fieldType === 'title'
							? __( 'Title', 'saman-seo' )
							: __( 'Description', 'saman-seo' ) }{ ' ' }
						{ __( 'with AI', 'saman-seo' ) }
					</h3>
					{ aiProvider === 'wp-ai-pilot' && (
						<span className="ai-generate-modal__badge">
							<svg
								width="12"
								height="12"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
							</svg>
							{ __( 'WP AI Pilot', 'saman-seo' ) }
						</span>
					) }
					<button
						type="button"
						className="ai-generate-modal__close"
						onClick={ handleClose }
						aria-label={ __( 'Close', 'saman-seo' ) }
					>
						<svg
							width="20"
							height="20"
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M18 6L6 18M6 6l12 12" />
						</svg>
					</button>
				</div>

				<div className="ai-generate-modal__body">
					{ /* Context Toggle */ }
					<div className="ai-generate-modal__option">
						<label className="ai-generate-modal__checkbox">
							<input
								type="checkbox"
								checked={ includeVariables }
								onChange={ ( e ) =>
									setIncludeVariables( e.target.checked )
								}
							/>
							<span className="ai-generate-modal__checkbox-mark"></span>
							<span className="ai-generate-modal__checkbox-label">
								{ __(
									'Include template variables as context',
									'saman-seo'
								) }
							</span>
						</label>
						<p className="ai-generate-modal__help">
							{ __(
								'Sends available data (site name, post type info, etc.) to help AI generate better content.',
								'saman-seo'
							) }
						</p>
					</div>

					{ /* Context Preview */ }
					{ includeVariables &&
						Object.keys( variableValues ).length > 0 && (
							<div className="ai-generate-modal__context-preview">
								<label>
									{ __(
										'Context that will be sent:',
										'saman-seo'
									) }
								</label>
								<div className="ai-generate-modal__context-box">
									{ Object.entries( variableValues )
										.filter(
											( [ key, value ] ) =>
												value &&
												typeof value === 'string'
										)
										.map( ( [ key, value ] ) => (
											<div
												key={ key }
												className="ai-generate-modal__context-item"
											>
												<span className="ai-generate-modal__context-key">
													{ key }:
												</span>
												<span className="ai-generate-modal__context-value">
													{ value }
												</span>
											</div>
										) ) }
								</div>
							</div>
						) }

					{ /* Custom Prompt */ }
					<div className="ai-generate-modal__field">
						<label htmlFor="ai-custom-prompt">
							{ __(
								'Custom instructions (optional)',
								'saman-seo'
							) }
						</label>
						<textarea
							id="ai-custom-prompt"
							value={ customPrompt }
							onChange={ ( e ) =>
								setCustomPrompt( e.target.value )
							}
							placeholder={ __(
								'highlighting key benefits',
								'saman-seo'
							) }
							rows={ 3 }
						/>
					</div>

					{ /* Error Message */ }
					{ error && (
						<div className="ai-generate-modal__error">
							<svg
								width="16"
								height="16"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<circle cx="12" cy="12" r="10" />
								<path d="M12 8v4m0 4h.01" />
							</svg>
							{ error }
						</div>
					) }

					{ /* Generated Result */ }
					{ generatedResult && (
						<div className="ai-generate-modal__result">
							<label>
								{ __( 'Generated', 'saman-seo' ) }{ ' ' }
								{ fieldType === 'title'
									? __( 'title', 'saman-seo' )
									: __( 'description', 'saman-seo' ) }
								:
							</label>
							<div className="ai-generate-modal__result-box">
								<p>{ generatedResult }</p>
								<span className="ai-generate-modal__char-count">
									{ generatedResult.length }{ ' ' }
									{ __( 'characters', 'saman-seo' ) }
								</span>
							</div>
						</div>
					) }
				</div>

				<div className="ai-generate-modal__footer">
					<button
						type="button"
						className="button ghost"
						onClick={ handleClose }
					>
						{ __( 'Cancel', 'saman-seo' ) }
					</button>

					{ generatedResult ? (
						<>
							<button
								type="button"
								className="button"
								onClick={ handleGenerate }
								disabled={ isGenerating }
							>
								{ __( 'Regenerate', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="button primary"
								onClick={ handleApply }
							>
								{ __( 'Apply', 'saman-seo' ) }
							</button>
						</>
					) : (
						<button
							type="button"
							className="button primary"
							onClick={ handleGenerate }
							disabled={ isGenerating }
						>
							{ isGenerating ? (
								<>
									<span className="ai-generate-modal__spinner"></span>
									{ __( 'Generating\u2026', 'saman-seo' ) }
								</>
							) : (
								<>
									<svg
										width="16"
										height="16"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
									>
										<path d="M12 3v1m0 16v1m-9-9h1m16 0h1m-2.636-6.364l-.707.707M6.343 17.657l-.707.707m12.728 0l-.707-.707M6.343 6.343l-.707-.707" />
										<circle cx="12" cy="12" r="4" />
									</svg>
									{ __( 'Generate', 'saman-seo' ) }
								</>
							) }
						</button>
					) }
				</div>
			</div>
		</div>
	);
};
export default AiGenerateModal;
