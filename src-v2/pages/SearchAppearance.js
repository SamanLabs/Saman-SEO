import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SubTabs from '../components/SubTabs';
import SearchPreview from '../components/SearchPreview';
import TemplateInput from '../components/TemplateInput';
import AiGenerateModal from '../components/AiGenerateModal';
import { FacebookPreview, TwitterPreview } from '../components/SocialPreview';
import useUrlTab from '../hooks/useUrlTab';

// Get AI status from global settings
import { __ } from '@wordpress/i18n';
const globalSettings = window.samanSeoV2Settings || {};
const aiEnabled = globalSettings.aiEnabled || false;
const aiProvider = globalSettings.aiProvider || 'none';
const aiPilot = globalSettings.aiPilot || null;
const searchAppearanceTabs = [
	{
		id: 'homepage',
		label: __( 'Homepage', 'saman-seo' ),
	},
	{
		id: 'content-types',
		label: __( 'Content Types', 'saman-seo' ),
	},
	{
		id: 'taxonomies',
		label: __( 'Taxonomies', 'saman-seo' ),
	},
	{
		id: 'archives',
		label: __( 'Archives', 'saman-seo' ),
	},
	{
		id: 'social-settings',
		label: __( 'Social Settings', 'saman-seo' ),
	},
	{
		id: 'social-cards',
		label: __( 'Social Cards', 'saman-seo' ),
	},
];

// Schema type options
const schemaTypeOptions = [
	{
		value: '',
		label: __( 'Use default (Article)', 'saman-seo' ),
	},
	{
		value: 'article',
		label: __( 'Article', 'saman-seo' ),
	},
	{
		value: 'blogposting',
		label: __( 'Blog posting', 'saman-seo' ),
	},
	{
		value: 'newsarticle',
		label: __( 'News article', 'saman-seo' ),
	},
	{
		value: 'product',
		label: __( 'Product', 'saman-seo' ),
	},
	{
		value: 'profilepage',
		label: __( 'Profile page', 'saman-seo' ),
	},
	{
		value: 'website',
		label: __( 'Website', 'saman-seo' ),
	},
	{
		value: 'organization',
		label: __( 'Organization', 'saman-seo' ),
	},
	{
		value: 'event',
		label: __( 'Event', 'saman-seo' ),
	},
	{
		value: 'recipe',
		label: __( 'Recipe', 'saman-seo' ),
	},
	{
		value: 'videoobject',
		label: __( 'Video object', 'saman-seo' ),
	},
	{
		value: 'book',
		label: __( 'Book', 'saman-seo' ),
	},
	{
		value: 'service',
		label: __( 'Service', 'saman-seo' ),
	},
	{
		value: 'localbusiness',
		label: __( 'Local business', 'saman-seo' ),
	},
];

// Social card layout options with sensible defaults for each
const cardLayoutOptions = [
	{
		value: 'default',
		label: __( 'Classic', 'saman-seo' ),
		description: __( 'Bottom accent stripe', 'saman-seo' ),
		defaults: {
			title_font_size: 42,
			site_font_size: 18,
			title_weight: 600,
		},
	},
	{
		value: 'centered',
		label: __( 'Centered', 'saman-seo' ),
		description: __( 'Centered text', 'saman-seo' ),
		defaults: {
			title_font_size: 48,
			site_font_size: 20,
			title_weight: 500,
		},
	},
	{
		value: 'minimal',
		label: __( 'Minimal', 'saman-seo' ),
		description: __( 'Clean, no accent', 'saman-seo' ),
		defaults: {
			title_font_size: 36,
			site_font_size: 16,
			title_weight: 400,
		},
	},
	{
		value: 'magazine',
		label: __( 'Magazine', 'saman-seo' ),
		description: __( 'Top bar, elegant', 'saman-seo' ),
		defaults: {
			title_font_size: 44,
			site_font_size: 14,
			title_weight: 600,
		},
	},
	{
		value: 'gradient',
		label: __( 'Gradient', 'saman-seo' ),
		description: __( 'Gradient overlay', 'saman-seo' ),
		defaults: {
			title_font_size: 40,
			site_font_size: 18,
			title_weight: 500,
		},
	},
	{
		value: 'corner',
		label: __( 'Corner', 'saman-seo' ),
		description: __( 'Corner accent', 'saman-seo' ),
		defaults: {
			title_font_size: 38,
			site_font_size: 16,
			title_weight: 500,
		},
	},
];

// Color presets for quick styling
const colorPresets = [
	{
		name: __( 'Dark Blue', 'saman-seo' ),
		bg: '#1a1a36',
		accent: '#5a84ff',
		text: __( '#ffffff', 'saman-seo' ),
	},
	{
		name: __( 'Slate', 'saman-seo' ),
		bg: '#1e293b',
		accent: '#38bdf8',
		text: __( '#f1f5f9', 'saman-seo' ),
	},
	{
		name: __( 'Forest', 'saman-seo' ),
		bg: '#14532d',
		accent: '#86efac',
		text: __( '#f0fdf4', 'saman-seo' ),
	},
	{
		name: __( 'Charcoal', 'saman-seo' ),
		bg: '#18181b',
		accent: '#a78bfa',
		text: __( '#fafafa', 'saman-seo' ),
	},
	{
		name: __( 'Navy', 'saman-seo' ),
		bg: '#0c1929',
		accent: '#f97316',
		text: __( '#fff7ed', 'saman-seo' ),
	},
	{
		name: __( 'Wine', 'saman-seo' ),
		bg: '#450a0a',
		accent: '#fca5a5',
		text: __( '#fef2f2', 'saman-seo' ),
	},
	{
		name: __( 'Ocean', 'saman-seo' ),
		bg: '#083344',
		accent: '#22d3ee',
		text: __( '#ecfeff', 'saman-seo' ),
	},
	{
		name: __( 'Cream', 'saman-seo' ),
		bg: '#fef3c7',
		accent: '#d97706',
		text: __( '#451a03', 'saman-seo' ),
	},
];

// Logo position options
const logoPositionOptions = [
	{
		value: 'top-left',
		label: __( 'Top Left', 'saman-seo' ),
	},
	{
		value: 'top-right',
		label: __( 'Top Right', 'saman-seo' ),
	},
	{
		value: 'bottom-left',
		label: __( 'Bottom Left', 'saman-seo' ),
	},
	{
		value: 'bottom-right',
		label: __( 'Bottom Right', 'saman-seo' ),
	},
	{
		value: 'center',
		label: __( 'Center', 'saman-seo' ),
	},
];
const SearchAppearance = () => {
	const [ activeTab, setActiveTab ] = useUrlTab( {
		tabs: searchAppearanceTabs,
		defaultTab: 'homepage',
	} );

	// Global state
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ saveMessage, setSaveMessage ] = useState( '' );
	const [ siteInfo, setSiteInfo ] = useState( {} );

	// Variables for template rendering
	const [ variables, setVariables ] = useState( {} );
	const [ variableValues, setVariableValues ] = useState( {} );

	// Homepage state
	const [ homepage, setHomepage ] = useState( {
		meta_title: '',
		meta_description: '',
		meta_keywords: '',
	} );
	const [ separator, setSeparator ] = useState( '-' );
	const [ separatorOptions, setSeparatorOptions ] = useState( {} );

	// Post types state
	const [ postTypes, setPostTypes ] = useState( [] );
	const [ editingPostType, setEditingPostType ] = useState( null );
	const [ schemaOptions, setSchemaOptions ] = useState( {
		page: {},
		article: {},
	} );

	// Taxonomies state
	const [ taxonomies, setTaxonomies ] = useState( [] );
	const [ editingTaxonomy, setEditingTaxonomy ] = useState( null );

	// Archives state
	const [ archives, setArchives ] = useState( [] );
	const [ editingArchive, setEditingArchive ] = useState( null );

	// Social Settings state
	const [ socialDefaults, setSocialDefaults ] = useState( {
		og_title: '',
		og_description: '',
		twitter_title: '',
		twitter_description: '',
		image_source: '',
		schema_itemtype: '',
	} );
	const [ postTypeSocialDefaults, setPostTypeSocialDefaults ] = useState(
		{}
	);
	const [ editingPostTypeSocial, setEditingPostTypeSocial ] =
		useState( null );

	// Social Cards state
	const [ cardDesign, setCardDesign ] = useState( {
		background_color: '#1a1a36',
		accent_color: '#5a84ff',
		text_color: '#ffffff',
		title_font_size: 42,
		site_font_size: 18,
		title_weight: 600,
		logo_url: '',
		logo_position: 'bottom-left',
		logo_size: 48,
		layout: 'default',
	} );
	const [ cardPreviewTitle, setCardPreviewTitle ] = useState(
		'Sample Post Title - Understanding Core Web Vitals'
	);
	const [ cardModuleEnabled, setCardModuleEnabled ] = useState( true );
	const [ previewPosts, setPreviewPosts ] = useState( {} );
	const [ selectedPreviewPost, setSelectedPreviewPost ] = useState( null );
	const [ previewPostType, setPreviewPostType ] = useState( 'post' );

	// AI Generation modal state
	const [ aiModal, setAiModal ] = useState( {
		isOpen: false,
		fieldType: 'title',
		onApply: null,
		context: {},
	} );

	// Open AI modal for a specific field
	const openAiModal = useCallback( ( fieldType, onApply, context = {} ) => {
		setAiModal( {
			isOpen: true,
			fieldType,
			onApply,
			context,
		} );
	}, [] );

	// Close AI modal
	const closeAiModal = useCallback( () => {
		setAiModal( {
			isOpen: false,
			fieldType: 'title',
			onApply: null,
			context: {},
		} );
	}, [] );

	// Handle AI generated content
	const handleAiGenerate = useCallback(
		( result ) => {
			if ( aiModal.onApply && result ) {
				aiModal.onApply( result );
			}
		},
		[ aiModal ]
	);

	// Fetch all data on mount
	const fetchData = useCallback( async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/search-appearance',
			} );
			if ( response.success ) {
				const data = response.data;
				setHomepage( data.homepage || {} );
				setSeparator( data.separator || '-' );
				setSeparatorOptions( data.separator_options || {} );
				setPostTypes( data.post_types || [] );
				setTaxonomies( data.taxonomies || [] );
				setArchives( data.archives || [] );
				setSchemaOptions(
					data.schema_options || {
						page: {},
						article: {},
					}
				);
				setSiteInfo( data.site_info || {} );
				setVariables( data.variables || {} );
				setVariableValues( data.variable_values || {} );
				// Social settings
				setSocialDefaults(
					data.social_defaults || {
						og_title: '',
						og_description: '',
						twitter_title: '',
						twitter_description: '',
						image_source: '',
						schema_itemtype: '',
					}
				);
				setPostTypeSocialDefaults(
					data.post_type_social_defaults || {}
				);
				// Social cards
				setCardDesign(
					data.card_design || {
						background_color: '#1a1a36',
						accent_color: '#5a84ff',
						text_color: '#ffffff',
						title_font_size: 42,
						site_font_size: 18,
						title_weight: 600,
						logo_url: '',
						logo_position: 'bottom-left',
						logo_size: 48,
						layout: 'default',
					}
				);
				setCardModuleEnabled( data.card_module_enabled !== false );
				// Fetch posts for preview selector
				if ( data.preview_posts ) {
					setPreviewPosts( data.preview_posts );
				}
			}
		} catch ( error ) {
			console.error(
				'Failed to fetch search appearance settings:',
				error
			);
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchData();
	}, [ fetchData ] );

	// Clear save message after 3 seconds
	useEffect( () => {
		if ( saveMessage ) {
			const timer = setTimeout( () => setSaveMessage( '' ), 3000 );
			return () => clearTimeout( timer );
		}
	}, [ saveMessage ] );

	// Update variable values when separator changes
	useEffect( () => {
		setVariableValues( ( prev ) => ( {
			...prev,
			separator: separator,
		} ) );
	}, [ separator ] );

	// Apply modifier to a value (supports: upper, lower, capitalize, etc.)
	const applyModifier = ( value, modifier ) => {
		if ( ! value || ! modifier ) return value;
		const mod = modifier.trim().toLowerCase();
		switch ( mod ) {
			case 'upper':
			case 'uppercase':
				return String( value ).toUpperCase();
			case 'lower':
			case 'lowercase':
				return String( value ).toLowerCase();
			case 'capitalize':
			case 'title':
				return String( value ).replace( /\b\w/g, ( c ) =>
					c.toUpperCase()
				);
			case 'trim':
				return String( value ).trim();
			default:
				return value;
		}
	};

	// Generate preview from template using variable values
	const renderTemplatePreview = useCallback(
		( template, contextOverrides = {} ) => {
			if ( ! template ) return '';
			let preview = template;
			const allValues = {
				...variableValues,
				...contextOverrides,
			};

			// Replace all {{variable}} or {{variable | modifier}} patterns
			preview = preview.replace(
				/\{\{([^}]+)\}\}/g,
				( match, content ) => {
					const trimmedContent = content.trim();

					// Check for modifier (e.g., "post_title | upper")
					const pipeIndex = trimmedContent.indexOf( '|' );
					if ( pipeIndex > -1 ) {
						const baseTag = trimmedContent
							.substring( 0, pipeIndex )
							.trim();
						const modifier = trimmedContent
							.substring( pipeIndex + 1 )
							.trim();
						const baseValue = allValues[ baseTag ];
						if ( baseValue !== undefined ) {
							return applyModifier( baseValue, modifier );
						}
						return match; // Return original if no value found
					}

					// Simple variable without modifier
					return allValues[ trimmedContent ] !== undefined
						? allValues[ trimmedContent ]
						: match;
				}
			);
			return preview;
		},
		[ variableValues ]
	);

	// Save homepage settings
	const saveHomepage = async () => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/search-appearance/homepage',
				method: 'POST',
				data: homepage,
			} );
			if ( response.success ) {
				setSaveMessage(
					__( 'Homepage settings saved successfully.', 'saman-seo' )
				);
			}
		} catch ( error ) {
			console.error( 'Failed to save homepage settings:', error );
			setSaveMessage( __( 'Failed to save settings.', 'saman-seo' ) );
		} finally {
			setSaving( false );
		}
	};

	// Save separator
	const saveSeparator = async ( newSeparator ) => {
		setSeparator( newSeparator );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/search-appearance/separator',
				method: 'POST',
				data: {
					separator: newSeparator,
				},
			} );
		} catch ( error ) {
			console.error( 'Failed to save separator:', error );
		}
	};

	// Save single post type
	const savePostType = async ( postType ) => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/search-appearance/post-types/${ postType.slug }`,
				method: 'POST',
				data: postType,
			} );
			if ( response.success ) {
				setPostTypes( ( prev ) =>
					prev.map( ( pt ) =>
						pt.slug === postType.slug
							? {
									...pt,
									...postType,
							  }
							: pt
					)
				);
				setEditingPostType( null );
				setSaveMessage(
					__( 'Post type settings saved.', 'saman-seo' )
				);
			}
		} catch ( error ) {
			console.error( 'Failed to save post type:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save single taxonomy
	const saveTaxonomy = async ( taxonomy ) => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/search-appearance/taxonomies/${ taxonomy.slug }`,
				method: 'POST',
				data: taxonomy,
			} );
			if ( response.success ) {
				setTaxonomies( ( prev ) =>
					prev.map( ( tax ) =>
						tax.slug === taxonomy.slug
							? {
									...tax,
									...taxonomy,
							  }
							: tax
					)
				);
				setEditingTaxonomy( null );
				setSaveMessage( __( 'Taxonomy settings saved.', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to save taxonomy:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save archives
	const saveArchives = async () => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/search-appearance/archives',
				method: 'POST',
				data: archives,
			} );
			if ( response.success ) {
				setArchives( response.data );
				setEditingArchive( null );
				setSaveMessage( __( 'Archive settings saved.', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to save archives:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save social defaults
	const saveSocialDefaults = async () => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/search-appearance/social-defaults',
				method: 'POST',
				data: socialDefaults,
			} );
			if ( response.success ) {
				setSaveMessage( __( 'Social settings saved.', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to save social defaults:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save post type social settings
	const savePostTypeSocial = async ( slug, settings ) => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/search-appearance/social-defaults/${ slug }`,
				method: 'POST',
				data: settings,
			} );
			if ( response.success ) {
				setPostTypeSocialDefaults( ( prev ) => ( {
					...prev,
					[ slug ]: settings,
				} ) );
				setEditingPostTypeSocial( null );
				setSaveMessage(
					__( 'Post type social settings saved.', 'saman-seo' )
				);
			}
		} catch ( error ) {
			console.error( 'Failed to save post type social settings:', error );
		} finally {
			setSaving( false );
		}
	};

	// Save card design
	const saveCardDesign = async () => {
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/search-appearance/card-design',
				method: 'POST',
				data: cardDesign,
			} );
			if ( response.success ) {
				setSaveMessage(
					__( 'Social card design saved.', 'saman-seo' )
				);
			}
		} catch ( error ) {
			console.error( 'Failed to save card design:', error );
		} finally {
			setSaving( false );
		}
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="loading-state">
					{ __(
						'Loading search appearance settings\u2026',
						'saman-seo'
					) }
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Search Appearance', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Control how your content appears in search results.',
							'saman-seo'
						) }
					</p>
				</div>
				{ saveMessage && (
					<span className="pill success">{ saveMessage }</span>
				) }
			</div>

			<SubTabs
				tabs={ searchAppearanceTabs }
				activeTab={ activeTab }
				onChange={ setActiveTab }
				ariaLabel={ __( 'Search appearance sections', 'saman-seo' ) }
			/>

			{ /* Homepage Tab */ }
			{ __( 'keyword1, keyword2, keyword3', 'saman-seo' ) }

			{ /* Content Types Tab */ }
			{ activeTab === 'content-types' && (
				<section className="panel">
					<div className="table-toolbar">
						<div>
							<h3>{ __( 'Content Types', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'Configure SEO defaults for each post type.',
									'saman-seo'
								) }
							</p>
						</div>
					</div>

					{ editingPostType ? (
						<PostTypeEditor
							postType={ editingPostType }
							schemaOptions={ schemaOptions }
							separator={ separator }
							siteInfo={ siteInfo }
							variables={ variables }
							variableValues={ variableValues }
							onSave={ savePostType }
							onCancel={ () => setEditingPostType( null ) }
							saving={ saving }
							renderTemplatePreview={ renderTemplatePreview }
							openAiModal={ openAiModal }
						/>
					) : (
						<table className="data-table">
							<thead>
								<tr>
									<th>{ __( 'Post Type', 'saman-seo' ) }</th>
									<th>
										{ __( 'Title Preview', 'saman-seo' ) }
									</th>
									<th>
										{ __( 'Show in Search', 'saman-seo' ) }
									</th>
									<th>{ __( 'Posts', 'saman-seo' ) }</th>
									<th>{ __( 'Action', 'saman-seo' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ postTypes.map( ( pt ) => {
									const template =
										pt.title_template ||
										'{{post_title}} {{separator}} {{site_title}}';
									// Use sample title specific to this post type
									const previewOverrides = {
										post_title:
											pt.sample_title || pt.singular_name,
									};
									return (
										<tr key={ pt.slug }>
											<td>
												<strong>{ pt.name }</strong>
												<span className="muted-block">
													{ pt.slug }
												</span>
											</td>
											<td>
												<div className="title-preview-cell">
													<span className="title-preview-cell__title">
														{ renderTemplatePreview(
															template,
															previewOverrides
														) }
													</span>
													<code className="title-preview-cell__template">
														{ template }
													</code>
												</div>
											</td>
											<td>
												<span
													className={ `pill ${
														pt.noindex
															? 'warning'
															: 'success'
													}` }
												>
													{ pt.noindex
														? __(
																'No',
																'saman-seo'
														  )
														: __(
																'Yes',
																'saman-seo'
														  ) }
												</span>
											</td>
											<td>{ pt.count }</td>
											<td>
												<button
													type="button"
													className="link-button"
													onClick={ () =>
														setEditingPostType( {
															...pt,
														} )
													}
												>
													{ __(
														'Edit',
														'saman-seo'
													) }
												</button>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					) }
				</section>
			) }

			{ /* Taxonomies Tab */ }
			{ activeTab === 'taxonomies' && (
				<section className="panel">
					<div className="table-toolbar">
						<div>
							<h3>{ __( 'Taxonomies', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'Configure SEO settings for categories, tags, and custom taxonomies.',
									'saman-seo'
								) }
							</p>
						</div>
					</div>

					{ editingTaxonomy ? (
						<TaxonomyEditor
							taxonomy={ editingTaxonomy }
							separator={ separator }
							siteInfo={ siteInfo }
							variables={ variables }
							variableValues={ variableValues }
							onSave={ saveTaxonomy }
							onCancel={ () => setEditingTaxonomy( null ) }
							saving={ saving }
							renderTemplatePreview={ renderTemplatePreview }
							openAiModal={ openAiModal }
						/>
					) : (
						<table className="data-table">
							<thead>
								<tr>
									<th>{ __( 'Taxonomy', 'saman-seo' ) }</th>
									<th>
										{ __( 'Title Preview', 'saman-seo' ) }
									</th>
									<th>
										{ __( 'Show in Search', 'saman-seo' ) }
									</th>
									<th>{ __( 'Terms', 'saman-seo' ) }</th>
									<th>{ __( 'Action', 'saman-seo' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ taxonomies.map( ( tax ) => {
									const template =
										tax.title_template ||
										'{{term_title}} {{separator}} {{site_title}}';
									// Use sample term title specific to this taxonomy
									const previewOverrides = {
										term_title:
											tax.sample_term_title ||
											tax.singular_name,
									};
									return (
										<tr key={ tax.slug }>
											<td>
												<strong>{ tax.name }</strong>
												<span className="muted-block">
													{ tax.slug }
												</span>
											</td>
											<td>
												<div className="title-preview-cell">
													<span className="title-preview-cell__title">
														{ renderTemplatePreview(
															template,
															previewOverrides
														) }
													</span>
													<code className="title-preview-cell__template">
														{ template }
													</code>
												</div>
											</td>
											<td>
												<span
													className={ `pill ${
														tax.noindex
															? 'warning'
															: 'success'
													}` }
												>
													{ tax.noindex
														? __(
																'No',
																'saman-seo'
														  )
														: __(
																'Yes',
																'saman-seo'
														  ) }
												</span>
											</td>
											<td>{ tax.count }</td>
											<td>
												<button
													type="button"
													className="link-button"
													onClick={ () =>
														setEditingTaxonomy( {
															...tax,
														} )
													}
												>
													{ __(
														'Edit',
														'saman-seo'
													) }
												</button>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					) }
				</section>
			) }

			{ /* Archives Tab */ }
			{ activeTab === 'archives' && (
				<section className="panel">
					<div className="table-toolbar">
						<div>
							<h3>{ __( 'Archives', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'Configure SEO for author, date, search, and 404 pages.',
									'saman-seo'
								) }
							</p>
						</div>
					</div>

					{ editingArchive ? (
						<ArchiveEditor
							archive={ editingArchive }
							separator={ separator }
							siteInfo={ siteInfo }
							variables={ variables }
							variableValues={ variableValues }
							onSave={ ( updated ) => {
								setArchives( ( prev ) =>
									prev.map( ( a ) =>
										a.slug === updated.slug ? updated : a
									)
								);
								setEditingArchive( null );
							} }
							onCancel={ () => setEditingArchive( null ) }
							renderTemplatePreview={ renderTemplatePreview }
							openAiModal={ openAiModal }
						/>
					) : (
						<table className="data-table">
							<thead>
								<tr>
									<th>
										{ __( 'Archive Type', 'saman-seo' ) }
									</th>
									<th>
										{ __( 'Title Preview', 'saman-seo' ) }
									</th>
									<th>
										{ __( 'Show in Search', 'saman-seo' ) }
									</th>
									<th>{ __( 'Action', 'saman-seo' ) }</th>
								</tr>
							</thead>
							<tbody>
								{ archives.map( ( archive ) => {
									const template =
										archive.title_template ||
										'{{archive_title}} {{separator}} {{site_title}}';
									// Use sample values specific to this archive type
									const previewOverrides =
										archive.sample_values || {};
									return (
										<tr key={ archive.slug }>
											<td>
												<strong>
													{ archive.name }
												</strong>
												<span className="muted-block">
													{ archive.description }
												</span>
											</td>
											<td>
												<div className="title-preview-cell">
													<span className="title-preview-cell__title">
														{ renderTemplatePreview(
															template,
															previewOverrides
														) }
													</span>
													<code className="title-preview-cell__template">
														{ template }
													</code>
												</div>
											</td>
											<td>
												<span
													className={ `pill ${
														archive.noindex
															? 'warning'
															: 'success'
													}` }
												>
													{ archive.noindex
														? __(
																'No',
																'saman-seo'
														  )
														: __(
																'Yes',
																'saman-seo'
														  ) }
												</span>
											</td>
											<td>
												<button
													type="button"
													className="link-button"
													onClick={ () =>
														setEditingArchive( {
															...archive,
														} )
													}
												>
													{ __(
														'Edit',
														'saman-seo'
													) }
												</button>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					) }
				</section>
			) }

			{ /* Social Settings Tab */ }
			{ activeTab === 'social-settings' && (
				<section className="panel">
					<div className="table-toolbar">
						<div>
							<h3>{ __( 'Social Settings', 'saman-seo' ) }</h3>
							<p className="muted">
								{ __(
									'Configure default Open Graph, Twitter, and schema values for social sharing.',
									'saman-seo'
								) }
							</p>
						</div>
					</div>

					{ __( '{{tagline}}', 'saman-seo' ) }
				</section>
			) }

			{ /* AI Generate Modal */ }
			<AiGenerateModal
				isOpen={ aiModal.isOpen }
				onClose={ closeAiModal }
				onGenerate={ handleAiGenerate }
				fieldType={ aiModal.fieldType }
				variableValues={ variableValues }
				context={ aiModal.context }
			/>

			{ /* Social Cards Tab */ }
			{ __( 'Enter a sample title\u2026', 'saman-seo' ) }
		</div>
	);
};

/**
 * Post Type Editor Component
 */
const PostTypeEditor = ( {
	postType,
	schemaOptions,
	separator,
	siteInfo,
	variables,
	variableValues,
	onSave,
	onCancel,
	saving,
	renderTemplatePreview,
	openAiModal,
} ) => {
	const [ data, setData ] = useState( postType );
	const previewTitle = renderTemplatePreview(
		data.title_template || '{{post_title}} {{separator}} {{site_title}}'
	);
	const previewDescription = renderTemplatePreview(
		data.description_template || '{{post_excerpt}}'
	);
	return (
		<div className="type-editor">
			<div className="type-editor__header">
				<h4>
					{ __( 'Edit:', 'saman-seo' ) } { postType.name }
				</h4>
				<button
					type="button"
					className="link-button"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'saman-seo' ) }
				</button>
			</div>

			<SearchPreview
				title={ previewTitle }
				description={ previewDescription }
				domain={ siteInfo.domain }
				favicon={ siteInfo.favicon }
			/>

			<div className="settings-form">
				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Show in Search Results', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Allow search engines to index this content type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<label className="toggle">
							<input
								type="checkbox"
								checked={ ! data.noindex }
								onChange={ ( e ) =>
									setData( {
										...data,
										noindex: ! e.target.checked,
									} )
								}
							/>
							<span className="toggle-track" />
							<span className="toggle-text">
								{ data.noindex
									? __( 'Hidden', 'saman-seo' )
									: __( 'Visible', 'saman-seo' ) }
							</span>
						</label>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>{ __( 'Title Template', 'saman-seo' ) }</label>
						<p className="settings-help">
							{ __(
								'Click "Variables" to insert dynamic content.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.title_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									title_template: val,
								} )
							}
							placeholder={ __(
								'{{post_title}} {{separator}} {{site_title}}',
								'saman-seo'
							) }
							variables={ variables }
							variableValues={ variableValues }
							context="post"
							maxLength={ 60 }
							onAiClick={ () =>
								openAiModal(
									'title',
									( val ) =>
										setData( {
											...data,
											title_template: val,
										} ),
									{
										type: 'Post Type',
										name: postType.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Description Template', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Default meta description for this post type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.description_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									description_template: val,
								} )
							}
							placeholder={ __(
								'{{post_excerpt}}',
								'saman-seo'
							) }
							variables={ variables }
							variableValues={ variableValues }
							context="post"
							multiline
							maxLength={ 160 }
							onAiClick={ () =>
								openAiModal(
									'description',
									( val ) =>
										setData( {
											...data,
											description_template: val,
										} ),
									{
										type: 'Post Type',
										name: postType.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Default Schema Type', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Schema type used for posts of this type unless overridden per-post.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<select
							value={ data.schema_type || '' }
							onChange={ ( e ) =>
								setData( {
									...data,
									schema_type: e.target.value,
								} )
							}
						>
							{ schemaTypeOptions.map( ( opt ) => (
								<option key={ opt.value } value={ opt.value }>
									{ opt.label }
								</option>
							) ) }
						</select>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>{ __( 'Schema Page Type', 'saman-seo' ) }</label>
						<p className="settings-help">
							{ __(
								'Default structured data page type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<select
							value={ data.schema_page }
							onChange={ ( e ) =>
								setData( {
									...data,
									schema_page: e.target.value,
								} )
							}
						>
							{ Object.entries( schemaOptions.page ).map(
								( [ value, label ] ) => (
									<option key={ value } value={ value }>
										{ label }
									</option>
								)
							) }
						</select>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Schema Article Type', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Default structured data article type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<select
							value={ data.schema_article }
							onChange={ ( e ) =>
								setData( {
									...data,
									schema_article: e.target.value,
								} )
							}
						>
							{ Object.entries( schemaOptions.article ).map(
								( [ value, label ] ) => (
									<option key={ value } value={ value }>
										{ label }
									</option>
								)
							) }
						</select>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Show SEO Controls', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Show SEO meta box in editor for this post type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<label className="toggle">
							<input
								type="checkbox"
								checked={ data.show_seo_controls }
								onChange={ ( e ) =>
									setData( {
										...data,
										show_seo_controls: e.target.checked,
									} )
								}
							/>
							<span className="toggle-track" />
							<span className="toggle-text">
								{ data.show_seo_controls
									? __( 'Enabled', 'saman-seo' )
									: __( 'Disabled', 'saman-seo' ) }
							</span>
						</label>
					</div>
				</div>

				<div className="form-actions">
					<button
						type="button"
						className="button primary"
						onClick={ () => onSave( data ) }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save Changes', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button ghost"
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'saman-seo' ) }
					</button>
				</div>
			</div>
		</div>
	);
};

/**
 * Taxonomy Editor Component
 */
const TaxonomyEditor = ( {
	taxonomy,
	separator,
	siteInfo,
	variables,
	variableValues,
	onSave,
	onCancel,
	saving,
	renderTemplatePreview,
	openAiModal,
} ) => {
	const [ data, setData ] = useState( taxonomy );
	const previewTitle = renderTemplatePreview(
		data.title_template ||
			'{{term_title}} Archives {{separator}} {{site_title}}'
	);
	const previewDescription = renderTemplatePreview(
		data.description_template || '{{term_description}}'
	);
	return (
		<div className="type-editor">
			<div className="type-editor__header">
				<h4>
					{ __( 'Edit:', 'saman-seo' ) } { taxonomy.name }
				</h4>
				<button
					type="button"
					className="link-button"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'saman-seo' ) }
				</button>
			</div>

			<SearchPreview
				title={ previewTitle }
				description={ previewDescription }
				domain={ siteInfo.domain }
				favicon={ siteInfo.favicon }
			/>

			<div className="settings-form">
				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Show in Search Results', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Allow search engines to index this taxonomy.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<label className="toggle">
							<input
								type="checkbox"
								checked={ ! data.noindex }
								onChange={ ( e ) =>
									setData( {
										...data,
										noindex: ! e.target.checked,
									} )
								}
							/>
							<span className="toggle-track" />
							<span className="toggle-text">
								{ data.noindex
									? __( 'Hidden', 'saman-seo' )
									: __( 'Visible', 'saman-seo' ) }
							</span>
						</label>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>{ __( 'Title Template', 'saman-seo' ) }</label>
						<p className="settings-help">
							{ __(
								'Click "Variables" to insert dynamic content.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.title_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									title_template: val,
								} )
							}
							placeholder={ __(
								'{{term_title}} Archives {{separator}} {{site_title}}',
								'saman-seo'
							) }
							variables={ variables }
							variableValues={ variableValues }
							context="taxonomy"
							maxLength={ 60 }
							onAiClick={ () =>
								openAiModal(
									'title',
									( val ) =>
										setData( {
											...data,
											title_template: val,
										} ),
									{
										type: 'Taxonomy',
										name: taxonomy.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Description Template', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Default meta description for taxonomy archives.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.description_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									description_template: val,
								} )
							}
							placeholder={ __(
								'{{term_description}}',
								'saman-seo'
							) }
							variables={ variables }
							variableValues={ variableValues }
							context="taxonomy"
							multiline
							maxLength={ 160 }
							onAiClick={ () =>
								openAiModal(
									'description',
									( val ) =>
										setData( {
											...data,
											description_template: val,
										} ),
									{
										type: 'Taxonomy',
										name: taxonomy.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Show SEO Controls', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Show SEO fields when editing terms.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<label className="toggle">
							<input
								type="checkbox"
								checked={ data.show_seo_controls }
								onChange={ ( e ) =>
									setData( {
										...data,
										show_seo_controls: e.target.checked,
									} )
								}
							/>
							<span className="toggle-track" />
							<span className="toggle-text">
								{ data.show_seo_controls
									? __( 'Enabled', 'saman-seo' )
									: __( 'Disabled', 'saman-seo' ) }
							</span>
						</label>
					</div>
				</div>

				<div className="form-actions">
					<button
						type="button"
						className="button primary"
						onClick={ () => onSave( data ) }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving\u2026', 'saman-seo' )
							: __( 'Save Changes', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button ghost"
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'saman-seo' ) }
					</button>
				</div>
			</div>
		</div>
	);
};

/**
 * Archive Editor Component
 */
const ArchiveEditor = ( {
	archive,
	separator,
	siteInfo,
	variables,
	variableValues,
	onSave,
	onCancel,
	renderTemplatePreview,
	openAiModal,
} ) => {
	const [ data, setData ] = useState( archive );

	// Get context for this archive type
	const getArchiveContext = () => {
		switch ( archive.slug ) {
			case 'author':
				return 'author';
			case 'date':
				return 'archive';
			case 'search':
				return 'archive';
			case '404':
				return 'archive';
			default:
				return 'global';
		}
	};
	const previewTitle = renderTemplatePreview( data.title_template );
	const previewDescription = renderTemplatePreview(
		data.description_template
	);
	return (
		<div className="type-editor">
			<div className="type-editor__header">
				<h4>
					{ __( 'Edit:', 'saman-seo' ) } { archive.name }
				</h4>
				<button
					type="button"
					className="link-button"
					onClick={ onCancel }
				>
					{ __( 'Cancel', 'saman-seo' ) }
				</button>
			</div>

			<SearchPreview
				title={ previewTitle }
				description={ previewDescription }
				domain={ siteInfo.domain }
				favicon={ siteInfo.favicon }
			/>

			<div className="settings-form">
				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Show in Search Results', 'saman-seo' ) }
						</label>
						<p className="settings-help">
							{ __(
								'Allow search engines to index this page type.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<label className="toggle">
							<input
								type="checkbox"
								checked={ ! data.noindex }
								onChange={ ( e ) =>
									setData( {
										...data,
										noindex: ! e.target.checked,
									} )
								}
							/>
							<span className="toggle-track" />
							<span className="toggle-text">
								{ data.noindex
									? __( 'Hidden', 'saman-seo' )
									: __( 'Visible', 'saman-seo' ) }
							</span>
						</label>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>{ __( 'Title Template', 'saman-seo' ) }</label>
						<p className="settings-help">
							{ __(
								'Click "Variables" to insert dynamic content.',
								'saman-seo'
							) }
						</p>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.title_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									title_template: val,
								} )
							}
							variables={ variables }
							variableValues={ variableValues }
							context={ getArchiveContext() }
							maxLength={ 60 }
							onAiClick={ () =>
								openAiModal(
									'title',
									( val ) =>
										setData( {
											...data,
											title_template: val,
										} ),
									{
										type: 'Archive',
										name: archive.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="settings-row compact">
					<div className="settings-label">
						<label>
							{ __( 'Description Template', 'saman-seo' ) }
						</label>
					</div>
					<div className="settings-control">
						<TemplateInput
							value={ data.description_template }
							onChange={ ( val ) =>
								setData( {
									...data,
									description_template: val,
								} )
							}
							variables={ variables }
							variableValues={ variableValues }
							context={ getArchiveContext() }
							multiline
							maxLength={ 160 }
							onAiClick={ () =>
								openAiModal(
									'description',
									( val ) =>
										setData( {
											...data,
											description_template: val,
										} ),
									{
										type: 'Archive',
										name: archive.name,
									}
								)
							}
						/>
					</div>
				</div>

				<div className="form-actions">
					<button
						type="button"
						className="button primary"
						onClick={ () => onSave( data ) }
					>
						{ __( 'Save Changes', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button ghost"
						onClick={ onCancel }
					>
						{ __( 'Cancel', 'saman-seo' ) }
					</button>
				</div>
			</div>
		</div>
	);
};
export default SearchAppearance;
