/**
 * SEO Panel Component
 *
 * Main panel containing all SEO fields and previews with AI and Variables support.
 */

import { useState, useEffect, useCallback } from '@wordpress/element';
import { Button } from '@wordpress/components';
import apiFetch from '@wordpress/api-fetch';
import SearchPreview from './SearchPreview';
import ScoreGauge from './ScoreGauge';
import TemplateInput from './TemplateInput';
import AiGenerateModal from './AiGenerateModal';
import MetricsBreakdown from './MetricsBreakdown';
import SchemaTypeSelector from './SchemaTypeSelector';
import SchemaPreview from './SchemaPreview';
import useSchemaPreview from '../hooks/useSchemaPreview';

// Quick template presets for the editor
import { __, sprintf } from '@wordpress/i18n';
const quickTemplates = [
	{
		id: 'standard',
		name: __( 'Standard', 'saman-seo' ),
		title: __( '{{post_title}} | {{site_title}}', 'saman-seo' ),
		description: __( '{{post_excerpt}}', 'saman-seo' ),
	},
	{
		id: 'keyword',
		name: __( 'Keyword Focus', 'saman-seo' ),
		title: __( '{{post_title}} - Guide', 'saman-seo' ),
		description: __(
			'Learn about {{post_title}}. {{post_excerpt}}',
			'saman-seo'
		),
	},
	{
		id: 'how_to',
		name: __( 'How-To', 'saman-seo' ),
		title: __( 'How to {{post_title}}', 'saman-seo' ),
		description: __(
			'Learn how to {{post_title}} with this guide.',
			'saman-seo'
		),
	},
	{
		id: 'list',
		name: __( 'List Post', 'saman-seo' ),
		title: __( 'Best {{post_title}}', 'saman-seo' ),
		description: __(
			'Discover the best {{post_title}}. {{post_excerpt}}',
			'saman-seo'
		),
	},
];
const SEOPanel = ( {
	postId,
	postType,
	seoMeta,
	updateMeta,
	seoScore,
	effectiveTitle,
	effectiveDescription,
	postUrl,
	postTitle,
	postContent,
	featuredImage,
	hasChanges,
	variables,
	variableValues,
	aiEnabled,
	aiProvider = 'none',
	aiPilot = null,
} ) => {
	const [ activeTab, setActiveTab ] = useState( 'general' );
	const [ showTemplates, setShowTemplates ] = useState( false );
	const [ aiModal, setAiModal ] = useState( {
		isOpen: false,
		fieldType: 'title',
		onApply: null,
	} );

	// Indexing state
	const [ indexingStatus, setIndexingStatus ] = useState( null );
	const [ isSubmitting, setIsSubmitting ] = useState( false );
	const [ indexError, setIndexError ] = useState( null );

	// Fetch schema preview with debounce
	const {
		preview: schemaPreview,
		loading: schemaLoading,
		error: schemaError,
	} = useSchemaPreview(
		postId,
		seoMeta.schema_type,
		[ postTitle, postContent ] // Refresh when content changes
	);

	// Fetch indexing status
	useEffect( () => {
		if ( ! postId ) return;
		apiFetch( {
			path: `/saman-seo/v1/indexnow/post-status/${ postId }`,
		} )
			.then( ( response ) => {
				if ( response.success ) {
					setIndexingStatus( response.data );
				}
			} )
			.catch( () => {
				// Ignore errors - IndexNow might not be enabled
			} );
	}, [ postId ] );

	// Handle request indexing
	const handleRequestIndexing = useCallback( async () => {
		if ( ! postId || isSubmitting ) return;
		setIsSubmitting( true );
		setIndexError( null );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/indexnow/submit-post/${ postId }`,
				method: 'POST',
			} );
			if ( response.success ) {
				// Refresh status after submission
				const statusResponse = await apiFetch( {
					path: `/saman-seo/v1/indexnow/post-status/${ postId }`,
				} );
				if ( statusResponse.success ) {
					setIndexingStatus( statusResponse.data );
				}
			} else {
				setIndexError(
					response.message || 'Failed to submit for indexing'
				);
			}
		} catch ( err ) {
			setIndexError( err.message || 'Failed to submit for indexing' );
		} finally {
			setIsSubmitting( false );
		}
	}, [ postId, isSubmitting ] );

	// Apply template handler
	const applyTemplate = useCallback(
		( template ) => {
			updateMeta( 'title', template.title );
			updateMeta( 'description', template.description );
			setShowTemplates( false );
		},
		[ updateMeta ]
	);

	// Character limits
	const TITLE_MAX = 60;
	const DESC_MAX = 160;
	const titleLength = ( seoMeta.title || '' ).length;
	const descLength = ( seoMeta.description || '' ).length;
	const getTitleStatus = () => {
		if ( titleLength === 0 ) return 'empty';
		if ( titleLength < 30 ) return 'short';
		if ( titleLength > TITLE_MAX ) return 'long';
		return 'good';
	};
	const getDescStatus = () => {
		if ( descLength === 0 ) return 'empty';
		if ( descLength < 70 ) return 'short';
		if ( descLength > DESC_MAX ) return 'long';
		return 'good';
	};

	// AI Modal handlers
	const openAiModal = useCallback( ( fieldType, onApply ) => {
		setAiModal( {
			isOpen: true,
			fieldType,
			onApply,
		} );
	}, [] );
	const closeAiModal = useCallback( () => {
		setAiModal( {
			isOpen: false,
			fieldType: 'title',
			onApply: null,
		} );
	}, [] );
	const handleAiGenerate = useCallback(
		( result ) => {
			if ( aiModal.onApply && result ) {
				aiModal.onApply( result );
			}
			closeAiModal();
		},
		[ aiModal, closeAiModal ]
	);
	return (
		<div className="saman-seo-editor-panel">
			{ /* Score Header */ }
			<div className="saman-seo-score-header">
				<ScoreGauge
					score={ seoScore?.score || 0 }
					level={ seoScore?.level || 'poor' }
				/>
				<div className="saman-seo-score-info">
					<div className="saman-seo-score-label">
						{ __( 'SEO Score', 'saman-seo' ) }
					</div>
					<div className="saman-seo-score-status">
						{ seoScore?.issues?.length > 0
							? sprintf(
									/* translators: %1$s: placeholder, %2$s: placeholder */ __(
										'%1$s issue%2$s found',
										'saman-seo'
									),
									seoScore.issues.length,
									seoScore.issues.length !== 1 ? 's' : ''
							  )
							: __( 'Looking good!', 'saman-seo' ) }
					</div>
					{ ! seoMeta.focus_keyphrase && (
						<div className="saman-seo-keyphrase-hint">
							{ __(
								'Add keyphrases for full analysis',
								'saman-seo'
							) }
						</div>
					) }
					{ seoMeta.focus_keyphrase &&
						seoMeta.secondary_keyphrases?.length > 0 && (
							<div
								className="saman-seo-keyphrase-hint"
								style={ {
									color: '#00a32a',
								} }
							>
								{ 1 + seoMeta.secondary_keyphrases.length }{ ' ' }
								{ __( 'keywords tracked', 'saman-seo' ) }
							</div>
						) }
				</div>
			</div>

			{ /* Tab Navigation */ }
			<div className="saman-seo-tabs">
				<button
					type="button"
					className={ `saman-seo-tab ${
						activeTab === 'general' ? 'active' : ''
					}` }
					onClick={ () => setActiveTab( 'general' ) }
				>
					{ __( 'General', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className={ `saman-seo-tab ${
						activeTab === 'analysis' ? 'active' : ''
					}` }
					onClick={ () => setActiveTab( 'analysis' ) }
				>
					{ __( 'Analysis', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className={ `saman-seo-tab ${
						activeTab === 'advanced' ? 'active' : ''
					}` }
					onClick={ () => setActiveTab( 'advanced' ) }
				>
					{ __( 'Advanced', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className={ `saman-seo-tab ${
						activeTab === 'social' ? 'active' : ''
					}` }
					onClick={ () => setActiveTab( 'social' ) }
				>
					{ __( 'Social', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className={ `saman-seo-tab ${
						activeTab === 'schema' ? 'active' : ''
					}` }
					onClick={ () => setActiveTab( 'schema' ) }
				>
					{ __( 'Schema', 'saman-seo' ) }
				</button>
			</div>

			{ /* General Tab */ }
			{ activeTab === 'general' && (
				<div className="saman-seo-tab-content">
					{ /* Search Preview */ }
					<div className="saman-seo-preview-section">
						<label className="saman-seo-section-label">
							{ __( 'Search Preview', 'saman-seo' ) }
						</label>
						<SearchPreview
							title={ effectiveTitle }
							description={ effectiveDescription }
							url={ postUrl }
						/>
					</div>

					{ /* Focus Keyphrase */ }
					<div className="saman-seo-field">
						<div className="saman-seo-field-header">
							<label>
								{ __( 'Focus Keyphrase', 'saman-seo' ) }
							</label>
						</div>
						<input
							type="text"
							className="saman-seo-field-input"
							value={ seoMeta.focus_keyphrase || '' }
							onChange={ ( e ) =>
								updateMeta( 'focus_keyphrase', e.target.value )
							}
							placeholder={ __(
								'Enter your main target keyword',
								'saman-seo'
							) }
						/>
					</div>

					{ /* Quick Templates */ }
					<div className="saman-seo-field">
						<div className="saman-seo-field-header">
							<label>
								{ __( 'Quick Templates', 'saman-seo' ) }
							</label>
						</div>
						<div className="saman-seo-templates">
							<button
								type="button"
								className="saman-seo-templates-toggle"
								onClick={ () =>
									setShowTemplates( ! showTemplates )
								}
							>
								{ showTemplates
									? __( 'Hide templates', 'saman-seo' )
									: __( 'Apply a template', 'saman-seo' ) }
							</button>
							{ showTemplates && (
								<div className="saman-seo-templates-list">
									{ quickTemplates.map( ( template ) => (
										<button
											key={ template.id }
											type="button"
											className="saman-seo-template-item"
											onClick={ () =>
												applyTemplate( template )
											}
										>
											<strong>{ template.name }</strong>
											<span>{ template.title }</span>
										</button>
									) ) }
								</div>
							) }
						</div>
					</div>

					{ /* SEO Title */ }
					<TemplateInput
						label={ __( 'SEO Title', 'saman-seo' ) }
						helpText={ __(
							'Recommended length: 50–60 characters',
							'saman-seo'
						) }
						value={ seoMeta.title || '' }
						onChange={ ( value ) => updateMeta( 'title', value ) }
						variables={ variables }
						variableValues={ variableValues }
						context="post"
						maxLength={ 60 }
						onAiClick={ () =>
							openAiModal( 'title', ( result ) =>
								updateMeta( 'title', result )
							)
						}
						aiEnabled={ aiEnabled }
					/>

					{ /* Meta Description */ }
					<TemplateInput
						label={ __( 'Meta Description', 'saman-seo' ) }
						helpText={ __(
							'Recommended length: 150–160 characters',
							'saman-seo'
						) }
						value={ seoMeta.description || '' }
						onChange={ ( value ) =>
							updateMeta( 'description', value )
						}
						variables={ variables }
						variableValues={ variableValues }
						context="post"
						multiline
						maxLength={ 160 }
						onAiClick={ () =>
							openAiModal( 'description', ( result ) =>
								updateMeta( 'description', result )
							)
						}
						aiEnabled={ aiEnabled }
					/>
				</div>
			) }

			{ /* Analysis Tab */ }
			{ activeTab === 'analysis' && (
				<div className="saman-seo-tab-content">
					<MetricsBreakdown
						metrics={ seoScore?.metrics || [] }
						metricsByCategory={ seoScore?.metrics_by_category }
						hasKeyphrase={ !! seoMeta.focus_keyphrase }
					/>
				</div>
			) }

			{ /* Advanced Tab */ }
			{ activeTab === 'advanced' && (
				<div className="saman-seo-tab-content">
					{ /* Canonical URL */ }
					<div className="saman-seo-field">
						<div className="saman-seo-field-header">
							<label>
								{ __( 'Canonical URL', 'saman-seo' ) }
							</label>
						</div>
						<input
							type="url"
							className="saman-seo-field-input"
							value={ seoMeta.canonical || '' }
							onChange={ ( e ) =>
								updateMeta( 'canonical', e.target.value )
							}
							placeholder={ postUrl }
						/>
						<p className="saman-seo-field-help">
							{ __(
								'Leave empty to use the default URL',
								'saman-seo'
							) }
						</p>
					</div>

					{ /* Robots Settings */ }
					<div className="saman-seo-robots-section">
						<label className="saman-seo-section-label">
							{ __( 'Search Engine Visibility', 'saman-seo' ) }
						</label>

						<label className="saman-seo-toggle">
							<span className="saman-seo-toggle-label">
								{ __(
									'Hide from search results',
									'saman-seo'
								) }
								<small>
									{ __(
										'Add noindex meta tag',
										'saman-seo'
									) }
								</small>
							</span>
							<input
								type="checkbox"
								checked={ seoMeta.noindex || false }
								onChange={ ( e ) =>
									updateMeta( 'noindex', e.target.checked )
								}
							/>
							<span className="saman-seo-toggle-slider"></span>
						</label>

						<label className="saman-seo-toggle">
							<span className="saman-seo-toggle-label">
								{ __( "Don't follow links", 'saman-seo' ) }
								<small>
									{ __(
										'Add nofollow meta tag',
										'saman-seo'
									) }
								</small>
							</span>
							<input
								type="checkbox"
								checked={ seoMeta.nofollow || false }
								onChange={ ( e ) =>
									updateMeta( 'nofollow', e.target.checked )
								}
							/>
							<span className="saman-seo-toggle-slider"></span>
						</label>
					</div>

					{ /* Robots Preview */ }
					<div className="saman-seo-robots-preview">
						<label className="saman-seo-section-label">
							{ __( 'Robots Meta', 'saman-seo' ) }
						</label>
						<code className="saman-seo-robots-code">
							{ seoMeta.noindex || seoMeta.nofollow
								? `${
										seoMeta.noindex
											? __( 'noindex', 'saman-seo' )
											: __( 'index', 'saman-seo' )
								  }, ${
										seoMeta.nofollow
											? __( 'nofollow', 'saman-seo' )
											: __( 'follow', 'saman-seo' )
								  }`
								: __( 'index, follow (default)', 'saman-seo' ) }
						</code>
					</div>

					{ /* Instant Indexing Section */ }
					<div className="saman-seo-indexing-section">
						<label className="saman-seo-section-label">
							{ __( 'Instant Indexing', 'saman-seo' ) }
						</label>

						{ indexingStatus &&
							! indexingStatus.indexnow_enabled && (
								<div className="saman-seo-indexing-notice saman-seo-indexing-notice--info">
									<svg
										width="16"
										height="16"
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
									>
										<circle cx="12" cy="12" r="10" />
										<path d="M12 16v-4M12 8h.01" />
									</svg>
									<span>
										{ __(
											'Enable IndexNow in Settings to use instant indexing',
											'saman-seo'
										) }
									</span>
								</div>
							) }

						{ indexingStatus && indexingStatus.indexnow_enabled && (
							<>
								{ /* Indexing Status */ }
								{ indexingStatus.has_been_indexed &&
									indexingStatus.last_submission && (
										<div
											className={ `saman-seo-indexing-status saman-seo-indexing-status--${ indexingStatus.last_submission.status }` }
										>
											<div className="saman-seo-indexing-status-icon">
												{ indexingStatus.last_submission
													.status === 'success' ? (
													<svg
														width="16"
														height="16"
														viewBox="0 0 24 24"
														fill="none"
														stroke="currentColor"
														strokeWidth="2"
													>
														<path d="M20 6L9 17l-5-5" />
													</svg>
												) : (
													<svg
														width="16"
														height="16"
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
												) }
											</div>
											<div className="saman-seo-indexing-status-text">
												<strong>
													{ indexingStatus
														.last_submission
														.status === 'success'
														? __(
																'Submitted',
																'saman-seo'
														  )
														: __(
																'Failed',
																'saman-seo'
														  ) }
												</strong>
												<span>
													{
														indexingStatus
															.last_submission
															.time_ago
													}
												</span>
											</div>
										</div>
									) }

								{ ! indexingStatus.has_been_indexed && (
									<div className="saman-seo-indexing-status saman-seo-indexing-status--never">
										<div className="saman-seo-indexing-status-icon">
											<svg
												width="16"
												height="16"
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
												<path d="M12 6v6l4 2" />
											</svg>
										</div>
										<div className="saman-seo-indexing-status-text">
											<strong>
												{ __(
													'Not submitted',
													'saman-seo'
												) }
											</strong>
											<span>
												{ __(
													'Request indexing to notify search engines',
													'saman-seo'
												) }
											</span>
										</div>
									</div>
								) }

								{ /* Error message */ }
								{ indexError && (
									<div className="saman-seo-indexing-notice saman-seo-indexing-notice--error">
										{ indexError }
									</div>
								) }

								{ /* Request Indexing Button */ }
								<Button
									variant="secondary"
									className="saman-seo-indexing-button"
									onClick={ handleRequestIndexing }
									disabled={ isSubmitting }
								>
									{ isSubmitting ? (
										<>
											<span className="saman-seo-indexing-spinner" />
											{ __(
												'Submitting\u2026',
												'saman-seo'
											) }
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
												<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
											</svg>
											{ __(
												'Request Indexing',
												'saman-seo'
											) }
										</>
									) }
								</Button>

								<p className="saman-seo-field-help">
									{ __(
										'Submit this URL to search engines via IndexNow for faster discovery.',
										'saman-seo'
									) }
									{ indexingStatus.total_submissions > 0 && (
										<>
											{ ' ' }
											{ __(
												'Submitted',
												'saman-seo'
											) }{ ' ' }
											{ indexingStatus.total_submissions }{ ' ' }
											{ __( 'time', 'saman-seo' ) }
											{ indexingStatus.total_submissions !==
											1
												? 's'
												: '' }
											.
										</>
									) }
								</p>
							</>
						) }
					</div>
				</div>
			) }

			{ /* Social Tab */ }
			{ activeTab === 'social' && (
				<div className="saman-seo-tab-content">
					<div className="saman-seo-social-grid">
						{ /* Left Column - Preview */ }
						<div className="saman-seo-social-col">
							<div className="saman-seo-social-preview">
								<label className="saman-seo-section-label">
									{ __( 'Social Preview', 'saman-seo' ) }
								</label>
								<div className="saman-seo-social-card">
									<div className="saman-seo-social-image">
										{ seoMeta.og_image || featuredImage ? (
											<img
												src={
													seoMeta.og_image ||
													featuredImage
												}
												alt=""
											/>
										) : (
											<div className="saman-seo-social-placeholder">
												<svg
													width="48"
													height="48"
													viewBox="0 0 24 24"
													fill="none"
												>
													<rect
														x="3"
														y="3"
														width="18"
														height="18"
														rx="2"
														stroke="currentColor"
														strokeWidth="2"
													/>
													<circle
														cx="8.5"
														cy="8.5"
														r="1.5"
														fill="currentColor"
													/>
													<path
														d="M21 15l-5-5L5 21"
														stroke="currentColor"
														strokeWidth="2"
														strokeLinecap="round"
														strokeLinejoin="round"
													/>
												</svg>
												<span>
													{ __(
														'No image set',
														'saman-seo'
													) }
												</span>
											</div>
										) }
									</div>
									<div className="saman-seo-social-content">
										<div className="saman-seo-social-url">
											{ new URL( postUrl ).hostname }
										</div>
										<div className="saman-seo-social-title">
											{ effectiveTitle }
										</div>
										<div className="saman-seo-social-desc">
											{ effectiveDescription ||
												__(
													'No description available',
													'saman-seo'
												) }
										</div>
									</div>
								</div>
							</div>
						</div>

						{ /* Right Column - Image Upload */ }
						<div className="saman-seo-social-col">
							<div className="saman-seo-field">
								<div className="saman-seo-field-header">
									<label>
										{ __( 'Social Image', 'saman-seo' ) }
									</label>
								</div>
								<div className="saman-seo-image-picker">
									{ __( 'Remove image', 'saman-seo' ) }
								</div>
								<Button
									variant="secondary"
									className="saman-seo-media-button"
									onClick={ () => {
										const frame = wp.media( {
											title: __(
												'Select Social Image',
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
											updateMeta(
												'og_image',
												attachment.url
											);
										} );
										frame.open();
									} }
								>
									<svg
										width="16"
										height="16"
										viewBox="0 0 24 24"
										fill="none"
										style={ {
											marginRight: '6px',
										} }
									>
										<rect
											x="3"
											y="3"
											width="18"
											height="18"
											rx="2"
											stroke="currentColor"
											strokeWidth="2"
										/>
										<circle
											cx="8.5"
											cy="8.5"
											r="1.5"
											fill="currentColor"
										/>
										<path
											d="M21 15l-5-5L5 21"
											stroke="currentColor"
											strokeWidth="2"
										/>
									</svg>
									{ seoMeta.og_image
										? __( 'Change Image', 'saman-seo' )
										: __( 'Select Image', 'saman-seo' ) }
								</Button>
								<p className="saman-seo-field-help">
									{ __(
										'1200x630 recommended. Leave empty to use featured image.',
										'saman-seo'
									) }
								</p>
								{ ! seoMeta.og_image && featuredImage && (
									<p className="saman-seo-field-note">
										{ __(
											'Using featured image as fallback',
											'saman-seo'
										) }
									</p>
								) }
							</div>
						</div>
					</div>
				</div>
			) }

			{ /* Schema Tab */ }
			{ activeTab === 'schema' && (
				<div className="saman-seo-tab-content">
					<SchemaTypeSelector
						value={ seoMeta.schema_type || '' }
						onChange={ ( value ) =>
							updateMeta( 'schema_type', value )
						}
						postType={ postType }
					/>
					<SchemaPreview
						jsonLd={ schemaPreview }
						loading={ schemaLoading }
						error={ schemaError }
					/>
				</div>
			) }

			{ /* AI Generate Modal */ }
			<AiGenerateModal
				isOpen={ aiModal.isOpen }
				onClose={ closeAiModal }
				onGenerate={ handleAiGenerate }
				fieldType={ aiModal.fieldType }
				currentValue={
					aiModal.fieldType === 'title'
						? seoMeta.title
						: seoMeta.description
				}
				postTitle={ postTitle }
				postContent={ postContent }
				variableValues={ variableValues }
				aiProvider={ aiProvider }
				aiPilot={ aiPilot }
			/>
		</div>
	);
};
export default SEOPanel;
