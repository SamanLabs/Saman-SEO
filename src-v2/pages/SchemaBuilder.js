import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
const schemaTypes = [
	{
		id: 'Article',
		name: __( 'Article', 'saman-seo' ),
		icon: '📄',
		description: __( 'News, blog posts, and articles', 'saman-seo' ),
	},
	{
		id: 'Product',
		name: __( 'Product', 'saman-seo' ),
		icon: '🛍️',
		description: __( 'E-commerce products', 'saman-seo' ),
	},
	{
		id: 'LocalBusiness',
		name: __( 'Local Business', 'saman-seo' ),
		icon: '🏪',
		description: __( 'Physical business location', 'saman-seo' ),
	},
	{
		id: 'FAQPage',
		name: __( 'FAQ Page', 'saman-seo' ),
		icon: '❓',
		description: __( 'Frequently asked questions', 'saman-seo' ),
	},
	{
		id: 'HowTo',
		name: __( 'How To', 'saman-seo' ),
		icon: '📝',
		description: __( 'Step-by-step instructions', 'saman-seo' ),
	},
	{
		id: 'Recipe',
		name: __( 'Recipe', 'saman-seo' ),
		icon: '🍳',
		description: __( 'Cooking recipes', 'saman-seo' ),
	},
	{
		id: 'Event',
		name: __( 'Event', 'saman-seo' ),
		icon: '📅',
		description: __( 'Events and conferences', 'saman-seo' ),
	},
	{
		id: 'Person',
		name: __( 'Person', 'saman-seo' ),
		icon: '👤',
		description: __( 'Author or person profile', 'saman-seo' ),
	},
	{
		id: 'Organization',
		name: __( 'Organization', 'saman-seo' ),
		icon: '🏢',
		description: __( 'Company or organization', 'saman-seo' ),
	},
	{
		id: 'WebSite',
		name: __( 'Website', 'saman-seo' ),
		icon: '🌐',
		description: __( 'Website information', 'saman-seo' ),
	},
	{
		id: 'BreadcrumbList',
		name: __( 'Breadcrumbs', 'saman-seo' ),
		icon: '🔗',
		description: __( 'Navigation breadcrumbs', 'saman-seo' ),
	},
	{
		id: 'VideoObject',
		name: __( 'Video', 'saman-seo' ),
		icon: '🎬',
		description: __( 'Video content', 'saman-seo' ),
	},
	{
		id: 'Course',
		name: __( 'Course', 'saman-seo' ),
		icon: '🎓',
		description: __( 'Educational courses', 'saman-seo' ),
	},
	{
		id: 'SoftwareApplication',
		name: __( 'Software', 'saman-seo' ),
		icon: '💻',
		description: __( 'Software applications', 'saman-seo' ),
	},
	{
		id: 'Book',
		name: __( 'Book', 'saman-seo' ),
		icon: '📚',
		description: __( 'Books and literature', 'saman-seo' ),
	},
	{
		id: 'MusicAlbum',
		name: __( 'Music', 'saman-seo' ),
		icon: '🎵',
		description: __( 'Music albums and playlists', 'saman-seo' ),
	},
	{
		id: 'Movie',
		name: __( 'Movie', 'saman-seo' ),
		icon: '🎬',
		description: __( 'Films and movies', 'saman-seo' ),
	},
	{
		id: 'Restaurant',
		name: __( 'Restaurant', 'saman-seo' ),
		icon: '🍽️',
		description: __( 'Restaurants and eateries', 'saman-seo' ),
	},
	{
		id: 'Service',
		name: __( 'Service', 'saman-seo' ),
		icon: '🛠️',
		description: __( 'Offered services', 'saman-seo' ),
	},
	{
		id: 'JobPosting',
		name: __( 'Job Posting', 'saman-seo' ),
		icon: '💼',
		description: __( 'Job opportunities', 'saman-seo' ),
	},
];
const schemaFields = {
	Article: [
		{
			key: 'headline',
			label: __( 'Headline', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'author',
			label: __( 'Author Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'datePublished',
			label: __( 'Date Published', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'dateModified',
			label: __( 'Date Modified', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'image',
			label: __( 'Image URL', 'saman-seo' ),
			type: 'url',
		},
	],
	Product: [
		{
			key: 'name',
			label: __( 'Product Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'image',
			label: __( 'Image URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'brand',
			label: __( 'Brand', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'sku',
			label: __( 'SKU', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'price',
			label: __( 'Price', 'saman-seo' ),
			type: 'number',
		},
		{
			key: 'priceCurrency',
			label: __( 'Currency', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'USD', 'saman-seo' ),
		},
		{
			key: 'availability',
			label: __( 'Availability', 'saman-seo' ),
			type: 'select',
			options: [
				__( 'InStock', 'saman-seo' ),
				__( 'OutOfStock', 'saman-seo' ),
				__( 'PreOrder', 'saman-seo' ),
			],
		},
	],
	LocalBusiness: [
		{
			key: 'name',
			label: __( 'Business Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'image',
			label: __( 'Image URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'telephone',
			label: __( 'Phone', 'saman-seo' ),
			type: 'tel',
		},
		{
			key: 'streetAddress',
			label: __( 'Street Address', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'addressLocality',
			label: __( 'City', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'addressRegion',
			label: __( 'State/Region', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'postalCode',
			label: __( 'Postal Code', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'addressCountry',
			label: __( 'Country', 'saman-seo' ),
			type: 'text',
		},
	],
	FAQPage: [
		{
			key: 'faqs',
			label: __( 'FAQ Items', 'saman-seo' ),
			type: 'faq-list',
		},
	],
	HowTo: [
		{
			key: 'name',
			label: __( 'Title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'totalTime',
			label: __( 'Total Time', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'PT30M (30 minutes)', 'saman-seo' ),
		},
		{
			key: 'steps',
			label: __( 'Steps', 'saman-seo' ),
			type: 'steps-list',
		},
	],
	Recipe: [
		{
			key: 'name',
			label: __( 'Recipe Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'image',
			label: __( 'Image URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'prepTime',
			label: __( 'Prep Time', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'PT15M', 'saman-seo' ),
		},
		{
			key: 'cookTime',
			label: __( 'Cook Time', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'PT30M', 'saman-seo' ),
		},
		{
			key: 'recipeYield',
			label: __( 'Servings', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'recipeIngredient',
			label: __( 'Ingredients', 'saman-seo' ),
			type: 'list',
		},
		{
			key: 'recipeInstructions',
			label: __( 'Instructions', 'saman-seo' ),
			type: 'steps-list',
		},
	],
	Event: [
		{
			key: 'name',
			label: __( 'Event Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'startDate',
			label: __( 'Start Date', 'saman-seo' ),
			type: 'datetime-local',
		},
		{
			key: 'endDate',
			label: __( 'End Date', 'saman-seo' ),
			type: 'datetime-local',
		},
		{
			key: 'location',
			label: __( 'Location Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'streetAddress',
			label: __( 'Address', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'image',
			label: __( 'Image URL', 'saman-seo' ),
			type: 'url',
		},
	],
	Person: [
		{
			key: 'name',
			label: __( 'Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'jobTitle',
			label: __( 'Job Title', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'description',
			label: __( 'Bio', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'image',
			label: __( 'Photo URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'email',
			label: __( 'Email', 'saman-seo' ),
			type: 'email',
		},
		{
			key: 'url',
			label: __( 'Website', 'saman-seo' ),
			type: 'url',
		},
	],
	Organization: [
		{
			key: 'name',
			label: __( 'Organization Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'logo',
			label: __( 'Logo URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'url',
			label: __( 'Website', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'telephone',
			label: __( 'Phone', 'saman-seo' ),
			type: 'tel',
		},
		{
			key: 'email',
			label: __( 'Email', 'saman-seo' ),
			type: 'email',
		},
	],
	WebSite: [
		{
			key: 'name',
			label: __( 'Site Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'url',
			label: __( 'URL', 'saman-seo' ),
			type: 'url',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'potentialAction',
			label: __( 'Enable Site Search', 'saman-seo' ),
			type: 'checkbox',
		},
	],
	BreadcrumbList: [
		{
			key: 'items',
			label: __( 'Breadcrumb Items', 'saman-seo' ),
			type: 'breadcrumb-list',
		},
	],
	VideoObject: [
		{
			key: 'name',
			label: __( 'Video Title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'thumbnailUrl',
			label: __( 'Thumbnail URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'uploadDate',
			label: __( 'Upload Date', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'duration',
			label: __( 'Duration', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'PT5M30S', 'saman-seo' ),
		},
		{
			key: 'contentUrl',
			label: __( 'Video URL', 'saman-seo' ),
			type: 'url',
		},
		{
			key: 'embedUrl',
			label: __( 'Embed URL', 'saman-seo' ),
			type: 'url',
		},
	],
	Course: [
		{
			key: 'name',
			label: __( 'Course Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'provider',
			label: __( 'Provider Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'courseCode',
			label: __( 'Course Code', 'saman-seo' ),
			type: 'text',
		},
	],
	SoftwareApplication: [
		{
			key: 'name',
			label: __( 'Software Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'operatingSystem',
			label: __( 'Operating System', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'applicationCategory',
			label: __( 'Application Category', 'saman-seo' ),
			type: 'text',
		},
	],
	Book: [
		{
			key: 'name',
			label: __( 'Book Title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'author',
			label: __( 'Author Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'isbn',
			label: __( 'ISBN', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'bookEdition',
			label: __( 'Book Edition', 'saman-seo' ),
			type: 'text',
		},
	],
	MusicAlbum: [
		{
			key: 'name',
			label: __( 'Album Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'byArtist',
			label: __( 'Artist Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'numTracks',
			label: __( 'Number of Tracks', 'saman-seo' ),
			type: 'number',
		},
	],
	Movie: [
		{
			key: 'name',
			label: __( 'Movie Title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'director',
			label: __( 'Director Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'dateCreated',
			label: __( 'Date Created', 'saman-seo' ),
			type: 'date',
		},
	],
	Restaurant: [
		{
			key: 'name',
			label: __( 'Restaurant Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'servesCuisine',
			label: __( 'Serves Cuisine', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'priceRange',
			label: __( 'Price Range', 'saman-seo' ),
			type: 'text',
		},
	],
	Service: [
		{
			key: 'name',
			label: __( 'Service Name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'serviceType',
			label: __( 'Service Type', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'provider',
			label: __( 'Provider Name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'areaServed',
			label: __( 'Area Served', 'saman-seo' ),
			type: 'text',
		},
	],
	JobPosting: [
		{
			key: 'title',
			label: __( 'Job Title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'description',
			label: __( 'Description', 'saman-seo' ),
			type: 'textarea',
		},
		{
			key: 'hiringOrganization',
			label: __( 'Hiring Organization', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'employmentType',
			label: __( 'Employment Type', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'datePosted',
			label: __( 'Date Posted', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'validThrough',
			label: __( 'Valid Through', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'jobLocation',
			label: __( 'Job Location', 'saman-seo' ),
			type: 'text',
		},
	],
};
const SchemaBuilder = ( { onNavigate } ) => {
	const [ selectedType, setSelectedType ] = useState( null );
	const [ formData, setFormData ] = useState( {} );
	const [ generatedSchema, setGeneratedSchema ] = useState( null );
	const [ validation, setValidation ] = useState( null );
	const [ generating, setGenerating ] = useState( false );
	const [ validating, setValidating ] = useState( false );
	const [ saving, setSaving ] = useState( false );
	const [ postUrl, setPostUrl ] = useState( '' );
	const [ detecting, setDetecting ] = useState( false );
	const [ copied, setCopied ] = useState( false );
	const [ templates, setTemplates ] = useState( [] );
	const [ importUrl, setImportUrl ] = useState( '' );
	const [ importing, setImporting ] = useState( false );
	useEffect( () => {
		// Fetch templates from server
		apiFetch( {
			path: '/saman-seo/v1/tools/schema/templates',
		} )
			.then( setTemplates )
			.catch( ( err ) =>
				console.error( 'Failed to fetch templates:', err )
			);
	}, [] );
	useEffect( () => {
		if ( generatedSchema ) {
			handleValidate();
		}
	}, [ generatedSchema ] );
	const handleTypeSelect = ( type ) => {
		setSelectedType( type );
		setFormData( {} );
		setGeneratedSchema( null );
		setValidation( null );
	};
	const handleFieldChange = ( key, value ) => {
		setFormData( ( prev ) => ( {
			...prev,
			[ key ]: value,
		} ) );
	};
	const handleDetectSchema = async () => {
		if ( ! postUrl ) return;
		setDetecting( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/detect',
				method: 'POST',
				data: {
					url: postUrl,
				},
			} );
			if ( response.success && response.data.suggested_type ) {
				setSelectedType( response.data.suggested_type );
				if ( response.data.prefilled_data ) {
					setFormData( response.data.prefilled_data );
				}
			}
		} catch ( error ) {
			console.error( 'Failed to detect:', error );
		} finally {
			setDetecting( false );
		}
	};
	const handleGenerateSchema = async () => {
		if ( ! selectedType ) return;
		setGenerating( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/generate',
				method: 'POST',
				data: {
					type: selectedType,
					data: formData,
				},
			} );
			if ( response.success ) {
				setGeneratedSchema( response.data.schema );
			}
		} catch ( error ) {
			console.error( 'Failed to generate:', error );
		} finally {
			setGenerating( false );
		}
	};
	const handleValidate = async () => {
		if ( ! generatedSchema ) return;
		setValidating( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/validate',
				method: 'POST',
				data: {
					schema: generatedSchema,
				},
			} );
			if ( response.success ) {
				setValidation( response.data );
			}
		} catch ( error ) {
			console.error( 'Failed to validate:', error );
		} finally {
			setValidating( false );
		}
	};
	const handleCopySchema = () => {
		const schemaScript = `<script type="application/ld+json">\n${ JSON.stringify(
			generatedSchema,
			null,
			2
		) }\n</script>`;
		navigator.clipboard.writeText( schemaScript );
		setCopied( true );
		setTimeout( () => setCopied( false ), 2000 );
	};
	const handleSaveTemplate = () => {
		const templateName = prompt(
			__( 'Enter a name for this template:', 'saman-seo' )
		);
		if ( templateName ) {
			const newTemplate = {
				name: templateName,
				type: selectedType,
				data: formData,
			};
			apiFetch( {
				path: '/saman-seo/v1/tools/schema/templates',
				method: 'POST',
				data: newTemplate,
			} )
				.then( () => {
					setTemplates( [ ...templates, newTemplate ] );
					alert( __( 'Template saved!', 'saman-seo' ) );
				} )
				.catch( ( err ) =>
					console.error( 'Failed to save template:', err )
				);
		}
	};
	const handleApplyTemplate = ( template ) => {
		setSelectedType( template.type );
		setFormData( template.data );
	};
	const handleImportSchema = async () => {
		if ( ! importUrl ) return;
		setImporting( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/import',
				method: 'POST',
				data: {
					url: importUrl,
				},
			} );
			if ( response.success ) {
				setSelectedType( response.data.type );
				setFormData( response.data.data );
				alert( __( 'Schema imported successfully!', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to import schema:', error );
			alert(
				__(
					'Failed to import schema. Check the URL and try again.',
					'saman-seo'
				)
			);
		} finally {
			setImporting( false );
		}
	};
	const handleSaveSchema = async () => {
		if ( ! generatedSchema ) return;
		setSaving( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/save',
				method: 'POST',
				data: {
					schema: generatedSchema,
					post_id: new URLSearchParams( window.location.search ).get(
						'post'
					),
				},
			} );
			if ( response.success ) {
				alert( __( 'Schema saved successfully!', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to save schema:', error );
		} finally {
			setSaving( false );
		}
	};
	const renderField = ( field ) => {
		const value = formData[ field.key ] || '';
		switch ( field.type ) {
			case 'textarea':
				return (
					<textarea
						value={ value }
						onChange={ ( e ) =>
							handleFieldChange( field.key, e.target.value )
						}
						placeholder={ field.placeholder || '' }
						rows={ 3 }
					/>
				);
			case 'select':
				return (
					<select
						value={ value }
						onChange={ ( e ) =>
							handleFieldChange( field.key, e.target.value )
						}
					>
						<option value="">
							{ __( 'Select\u2026', 'saman-seo' ) }
						</option>
						{ field.options.map( ( opt ) => (
							<option key={ opt } value={ opt }>
								{ opt }
							</option>
						) ) }
					</select>
				);
			case 'checkbox':
				return (
					<label className="checkbox-label">
						<input
							type="checkbox"
							checked={ !! value }
							onChange={ ( e ) =>
								handleFieldChange( field.key, e.target.checked )
							}
						/>
						{ __( 'Enable', 'saman-seo' ) }
					</label>
				);
			case 'faq-list':
				return (
					<FAQListField
						value={ value || [] }
						onChange={ ( v ) => handleFieldChange( field.key, v ) }
					/>
				);
			case 'steps-list':
				return (
					<StepsListField
						value={ value || [] }
						onChange={ ( v ) => handleFieldChange( field.key, v ) }
					/>
				);
			case 'list':
				return (
					<SimpleListField
						value={ value || [] }
						onChange={ ( v ) => handleFieldChange( field.key, v ) }
					/>
				);
			case 'breadcrumb-list':
				return (
					<BreadcrumbListField
						value={ value || [] }
						onChange={ ( v ) => handleFieldChange( field.key, v ) }
					/>
				);
			default:
				return (
					<input
						type={ field.type }
						value={ value }
						onChange={ ( e ) =>
							handleFieldChange( field.key, e.target.value )
						}
						placeholder={ field.placeholder || '' }
					/>
				);
		}
	};
	return (
		<div className="page schema-builder-page">
			<div className="page-header">
				<div>
					<div className="page-header__breadcrumb">
						<button
							type="button"
							className="breadcrumb-link"
							onClick={ () => onNavigate( 'tools' ) }
						>
							{ __( 'Tools', 'saman-seo' ) }
						</button>
						<span className="breadcrumb-separator">/</span>
						<span>{ __( 'Schema Builder', 'saman-seo' ) }</span>
					</div>
					<h1>{ __( 'Visual Schema Builder', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Create structured data markup for rich search results.',
							'saman-seo'
						) }
					</p>
				</div>
			</div>

			<div className="schema-builder-layout">
				<div className="schema-builder-main">
					{ __(
						'Enter a URL to import schema from\u2026',
						'saman-seo'
					) }
				</div>

				{ generatedSchema && (
					<div className="schema-builder-preview">
						<div className="preview-header">
							<h3>{ __( 'Generated Schema', 'saman-seo' ) }</h3>
							<div className="preview-actions">
								<button
									type="button"
									className="button button--small"
									onClick={ handleValidate }
									disabled={ validating }
								>
									{ validating
										? __( 'Validating\u2026', 'saman-seo' )
										: __( 'Validate', 'saman-seo' ) }
								</button>
								<button
									type="button"
									className="button button--small button--primary"
									onClick={ handleCopySchema }
								>
									{ copied
										? __( 'Copied!', 'saman-seo' )
										: __( 'Copy', 'saman-seo' ) }
								</button>
							</div>
						</div>

						{ validation && (
							<div
								className={ `validation-result ${
									validation.valid ? 'valid' : 'invalid'
								}` }
							>
								{ validation.valid ? (
									<>
										<svg
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2"
											width="16"
											height="16"
										>
											<path d="M22 11.08V12a10 10 0 11-5.93-9.14" />
											<polyline points="22 4 12 14.01 9 11.01" />
										</svg>
										{ __(
											'Valid schema markup',
											'saman-seo'
										) }
									</>
								) : (
									<>
										<svg
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2"
											width="16"
											height="16"
										>
											<circle cx="12" cy="12" r="10" />
											<line
												x1="12"
												y1="8"
												x2="12"
												y2="12"
											/>
											<line
												x1="12"
												y1="16"
												x2="12.01"
												y2="16"
											/>
										</svg>
										{ validation.errors?.length || 0 }{ ' ' }
										{ __( 'issues found', 'saman-seo' ) }
									</>
								) }
							</div>
						) }

						{ validation?.errors?.length > 0 && (
							<ul className="validation-errors">
								{ validation.errors.map( ( err, idx ) => (
									<li key={ idx }>{ err }</li>
								) ) }
							</ul>
						) }

						<pre className="schema-code">
							<code>
								{ JSON.stringify( generatedSchema, null, 2 ) }
							</code>
						</pre>

						<div className="preview-footer">
							<a
								href={ `https://search.google.com/test/rich-results?url=${ encodeURIComponent(
									'data:application/ld+json,' +
										JSON.stringify( generatedSchema )
								) }` }
								target="_blank"
								rel="noopener noreferrer"
								className="button button--secondary"
							>
								{ __( 'Test in Google', 'saman-seo' ) }
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="14"
									height="14"
								>
									<path d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6" />
									<polyline points="15 3 21 3 21 9" />
									<line x1="10" y1="14" x2="21" y2="3" />
								</svg>
							</a>
							<button
								type="button"
								className="button button--primary"
								onClick={ handleSaveSchema }
								disabled={ saving }
							>
								{ saving
									? __( 'Saving\u2026', 'saman-seo' )
									: __( 'Save Schema', 'saman-seo' ) }
							</button>
						</div>
					</div>
				) }
				{ templates.length > 0 && (
					<div className="schema-templates">
						<h3>{ __( 'Saved Templates', 'saman-seo' ) }</h3>
						<div className="templates-grid">
							{ templates.map( ( template, idx ) => (
								<button
									key={ idx }
									type="button"
									className="template-card"
									onClick={ () =>
										handleApplyTemplate( template )
									}
								>
									<span className="template-name">
										{ template.name }
									</span>
									<span className="template-type">
										{ template.type }
									</span>
								</button>
							) ) }
						</div>
					</div>
				) }
			</div>
		</div>
	);
};

// Sub-components for complex field types
const FAQListField = ( { value, onChange } ) => {
	const addItem = () => {
		onChange( [
			...value,
			{
				question: '',
				answer: '',
			},
		] );
	};
	const updateItem = ( index, field, val ) => {
		const newValue = [ ...value ];
		newValue[ index ] = {
			...newValue[ index ],
			[ field ]: val,
		};
		onChange( newValue );
	};
	const removeItem = ( index ) => {
		onChange( value.filter( ( _, i ) => i !== index ) );
	};
	return (
		<div className="list-field">
			{ __( 'Answer', 'saman-seo' ) }
			<button type="button" className="add-item" onClick={ addItem }>
				{ __( '+ Add FAQ', 'saman-seo' ) }
			</button>
		</div>
	);
};
const StepsListField = ( { value, onChange } ) => {
	const addItem = () => {
		onChange( [
			...value,
			{
				name: '',
				text: '',
			},
		] );
	};
	const updateItem = ( index, field, val ) => {
		const newValue = [ ...value ];
		newValue[ index ] = {
			...newValue[ index ],
			[ field ]: val,
		};
		onChange( newValue );
	};
	const removeItem = ( index ) => {
		onChange( value.filter( ( _, i ) => i !== index ) );
	};
	return (
		<div className="list-field">
			{ __( 'Step description', 'saman-seo' ) }
			<button type="button" className="add-item" onClick={ addItem }>
				{ __( '+ Add Step', 'saman-seo' ) }
			</button>
		</div>
	);
};
const SimpleListField = ( { value, onChange } ) => {
	const addItem = () => {
		onChange( [ ...value, '' ] );
	};
	const updateItem = ( index, val ) => {
		const newValue = [ ...value ];
		newValue[ index ] = val;
		onChange( newValue );
	};
	const removeItem = ( index ) => {
		onChange( value.filter( ( _, i ) => i !== index ) );
	};
	return (
		<div className="list-field">
			{ value.map( ( item, idx ) => (
				<div key={ idx } className="list-item simple-item">
					<input
						type="text"
						value={ item }
						onChange={ ( e ) => updateItem( idx, e.target.value ) }
						placeholder={ sprintf(
							/* translators: %s: placeholder */ __(
								'Item %s',
								'saman-seo'
							),
							idx + 1
						) }
					/>
					<button
						type="button"
						className="remove-item"
						onClick={ () => removeItem( idx ) }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="14"
							height="14"
						>
							<line x1="18" y1="6" x2="6" y2="18" />
							<line x1="6" y1="6" x2="18" y2="18" />
						</svg>
					</button>
				</div>
			) ) }
			<button type="button" className="add-item" onClick={ addItem }>
				{ __( '+ Add Item', 'saman-seo' ) }
			</button>
		</div>
	);
};
const BreadcrumbListField = ( { value, onChange } ) => {
	const addItem = () => {
		onChange( [
			...value,
			{
				name: '',
				url: '',
			},
		] );
	};
	const updateItem = ( index, field, val ) => {
		const newValue = [ ...value ];
		newValue[ index ] = {
			...newValue[ index ],
			[ field ]: val,
		};
		onChange( newValue );
	};
	const removeItem = ( index ) => {
		onChange( value.filter( ( _, i ) => i !== index ) );
	};
	return (
		<div className="list-field">
			{ __( 'URL', 'saman-seo' ) }
			<button type="button" className="add-item" onClick={ addItem }>
				{ __( '+ Add Breadcrumb', 'saman-seo' ) }
			</button>
		</div>
	);
};
export default SchemaBuilder;
