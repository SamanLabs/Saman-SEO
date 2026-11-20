import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import {
	IconFileText,
	IconShoppingBag,
	IconMapPin,
	IconHelpCircle,
	IconChecklist,
	IconChefHat,
	IconCalendar,
	IconUser,
	IconBuilding,
	IconGlobe,
	IconLink,
	IconVideo,
	IconGraduationCap,
	IconMonitor,
	IconBookOpen,
	IconMusic,
	IconFilm,
	IconUtensils,
	IconWrench,
	IconBriefcase,
} from '../components/Icons';
import { promptDialog, toast } from '../components/Dialogs';
const schemaTypes = [
	{
		id: 'Article',
		name: __( 'Article', 'saman-seo' ),
		icon: <IconFileText />,
		description: __( 'News, blog posts, and articles', 'saman-seo' ),
	},
	{
		id: 'Product',
		name: __( 'Product', 'saman-seo' ),
		icon: <IconShoppingBag />,
		description: __( 'E-commerce products', 'saman-seo' ),
	},
	{
		id: 'LocalBusiness',
		name: __( 'Local business', 'saman-seo' ),
		icon: <IconMapPin />,
		description: __( 'Physical business location', 'saman-seo' ),
	},
	{
		id: 'FAQPage',
		name: __( 'FAQ page', 'saman-seo' ),
		icon: <IconHelpCircle />,
		description: __( 'Frequently asked questions', 'saman-seo' ),
	},
	{
		id: 'HowTo',
		name: __( 'How-to', 'saman-seo' ),
		icon: <IconChecklist />,
		description: __( 'Step-by-step instructions', 'saman-seo' ),
	},
	{
		id: 'Recipe',
		name: __( 'Recipe', 'saman-seo' ),
		icon: <IconChefHat />,
		description: __( 'Cooking recipes', 'saman-seo' ),
	},
	{
		id: 'Event',
		name: __( 'Event', 'saman-seo' ),
		icon: <IconCalendar />,
		description: __( 'Events and conferences', 'saman-seo' ),
	},
	{
		id: 'Person',
		name: __( 'Person', 'saman-seo' ),
		icon: <IconUser />,
		description: __( 'Author or person profile', 'saman-seo' ),
	},
	{
		id: 'Organization',
		name: __( 'Organization', 'saman-seo' ),
		icon: <IconBuilding />,
		description: __( 'Company or organization', 'saman-seo' ),
	},
	{
		id: 'WebSite',
		name: __( 'Website', 'saman-seo' ),
		icon: <IconGlobe />,
		description: __( 'Website information', 'saman-seo' ),
	},
	{
		id: 'BreadcrumbList',
		name: __( 'Breadcrumbs', 'saman-seo' ),
		icon: <IconLink />,
		description: __( 'Navigation breadcrumbs', 'saman-seo' ),
	},
	{
		id: 'VideoObject',
		name: __( 'Video', 'saman-seo' ),
		icon: <IconVideo />,
		description: __( 'Video content', 'saman-seo' ),
	},
	{
		id: 'Course',
		name: __( 'Course', 'saman-seo' ),
		icon: <IconGraduationCap />,
		description: __( 'Educational courses', 'saman-seo' ),
	},
	{
		id: 'SoftwareApplication',
		name: __( 'Software', 'saman-seo' ),
		icon: <IconMonitor />,
		description: __( 'Software applications', 'saman-seo' ),
	},
	{
		id: 'Book',
		name: __( 'Book', 'saman-seo' ),
		icon: <IconBookOpen />,
		description: __( 'Books and literature', 'saman-seo' ),
	},
	{
		id: 'MusicAlbum',
		name: __( 'Music', 'saman-seo' ),
		icon: <IconMusic />,
		description: __( 'Music albums and playlists', 'saman-seo' ),
	},
	{
		id: 'Movie',
		name: __( 'Movie', 'saman-seo' ),
		icon: <IconFilm />,
		description: __( 'Films and movies', 'saman-seo' ),
	},
	{
		id: 'Restaurant',
		name: __( 'Restaurant', 'saman-seo' ),
		icon: <IconUtensils />,
		description: __( 'Restaurants and eateries', 'saman-seo' ),
	},
	{
		id: 'Service',
		name: __( 'Service', 'saman-seo' ),
		icon: <IconWrench />,
		description: __( 'Offered services', 'saman-seo' ),
	},
	{
		id: 'JobPosting',
		name: __( 'Job posting', 'saman-seo' ),
		icon: <IconBriefcase />,
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
			label: __( 'Author name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'datePublished',
			label: __( 'Date published', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'dateModified',
			label: __( 'Date modified', 'saman-seo' ),
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
			label: __( 'Product name', 'saman-seo' ),
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
			label: __( 'Business name', 'saman-seo' ),
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
			label: __( 'Street address', 'saman-seo' ),
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
			label: __( 'Postal code', 'saman-seo' ),
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
			label: __( 'FAQ items', 'saman-seo' ),
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
			label: __( 'Total time', 'saman-seo' ),
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
			label: __( 'Recipe name', 'saman-seo' ),
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
			label: __( 'Prep time', 'saman-seo' ),
			type: 'text',
			placeholder: __( 'PT15M', 'saman-seo' ),
		},
		{
			key: 'cookTime',
			label: __( 'Cook time', 'saman-seo' ),
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
			label: __( 'Event name', 'saman-seo' ),
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
			label: __( 'Start date', 'saman-seo' ),
			type: 'datetime-local',
		},
		{
			key: 'endDate',
			label: __( 'End date', 'saman-seo' ),
			type: 'datetime-local',
		},
		{
			key: 'location',
			label: __( 'Location name', 'saman-seo' ),
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
			label: __( 'Job title', 'saman-seo' ),
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
			label: __( 'Organization name', 'saman-seo' ),
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
			label: __( 'Site name', 'saman-seo' ),
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
			label: __( 'Enable site search', 'saman-seo' ),
			type: 'checkbox',
		},
	],
	BreadcrumbList: [
		{
			key: 'items',
			label: __( 'Breadcrumb items', 'saman-seo' ),
			type: 'breadcrumb-list',
		},
	],
	VideoObject: [
		{
			key: 'name',
			label: __( 'Video title', 'saman-seo' ),
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
			label: __( 'Upload date', 'saman-seo' ),
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
			label: __( 'Course name', 'saman-seo' ),
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
			label: __( 'Provider name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'courseCode',
			label: __( 'Course code', 'saman-seo' ),
			type: 'text',
		},
	],
	SoftwareApplication: [
		{
			key: 'name',
			label: __( 'Software name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'operatingSystem',
			label: __( 'Operating system', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'applicationCategory',
			label: __( 'Application category', 'saman-seo' ),
			type: 'text',
		},
	],
	Book: [
		{
			key: 'name',
			label: __( 'Book title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'author',
			label: __( 'Author name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'isbn',
			label: __( 'ISBN', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'bookEdition',
			label: __( 'Book edition', 'saman-seo' ),
			type: 'text',
		},
	],
	MusicAlbum: [
		{
			key: 'name',
			label: __( 'Album name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'byArtist',
			label: __( 'Artist name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'numTracks',
			label: __( 'Number of tracks', 'saman-seo' ),
			type: 'number',
		},
	],
	Movie: [
		{
			key: 'name',
			label: __( 'Movie title', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'director',
			label: __( 'Director name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'dateCreated',
			label: __( 'Date created', 'saman-seo' ),
			type: 'date',
		},
	],
	Restaurant: [
		{
			key: 'name',
			label: __( 'Restaurant name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'servesCuisine',
			label: __( 'Serves cuisine', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'priceRange',
			label: __( 'Price range', 'saman-seo' ),
			type: 'text',
		},
	],
	Service: [
		{
			key: 'name',
			label: __( 'Service name', 'saman-seo' ),
			type: 'text',
			required: true,
		},
		{
			key: 'serviceType',
			label: __( 'Service type', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'provider',
			label: __( 'Provider name', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'areaServed',
			label: __( 'Area served', 'saman-seo' ),
			type: 'text',
		},
	],
	JobPosting: [
		{
			key: 'title',
			label: __( 'Job title', 'saman-seo' ),
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
			label: __( 'Hiring organization', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'employmentType',
			label: __( 'Employment type', 'saman-seo' ),
			type: 'text',
		},
		{
			key: 'datePosted',
			label: __( 'Date posted', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'validThrough',
			label: __( 'Valid through', 'saman-seo' ),
			type: 'date',
		},
		{
			key: 'jobLocation',
			label: __( 'Job location', 'saman-seo' ),
			type: 'text',
		},
	],
};
const SchemaBuilder = ( { onNavigate } ) => {
	const [ selectedType, setSelectedType ] = useState( null );
	const [ formData, setFormData ] = useState( {} );
	const [ generatedSchema, setGeneratedSchema ] = useState( null );
	const [ validation, setValidation ] = useState( null );
	const [ generateNotices, setGenerateNotices ] = useState( [] );
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
			.then( ( response ) => {
				const list = response?.data ?? response;
				setTemplates( Array.isArray( list ) ? list : [] );
			} )
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
		setGenerateNotices( [] );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/schema/generate',
				method: 'POST',
				data: {
					type: selectedType,
					data: formData,
					url: postUrl,
				},
			} );
			if ( response.success ) {
				setGeneratedSchema( response.data.schema );
				setGenerateNotices( response.data.notices || [] );
			}
		} catch ( error ) {
			console.error( 'Failed to generate:', error );
			toast(
				error?.message ||
					__( 'Failed to generate schema.', 'saman-seo' ),
					'error'
			);
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
	const handleSaveTemplate = async () => {
		const templateName = await promptDialog(
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
					toast( __( 'Template saved!', 'saman-seo' ) );
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
				toast( __( 'Schema imported successfully!', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to import schema:', error );
			toast(
				__(
					'Failed to import schema. Check the URL and try again.',
					'saman-seo'
				),
				'error'
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
					url: postUrl,
				},
			} );
			if ( response.success ) {
				toast( __( 'Schema saved successfully!', 'saman-seo' ) );
			}
		} catch ( error ) {
			console.error( 'Failed to save schema:', error );
			toast(
				error?.message || __( 'Failed to save schema.', 'saman-seo' ),
				'error'
			);
		} finally {
			setSaving( false );
		}
	};
	const renderField = ( field, id ) => {
		const value = formData[ field.key ] || '';
		switch ( field.type ) {
			case 'textarea':
				return (
					<textarea
						id={ id }
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
						id={ id }
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
							id={ id }
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
						id={ id }
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
	const activeType = schemaTypes.find( ( t ) => t.id === selectedType );
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
						<span>{ __( 'Schema builder', 'saman-seo' ) }</span>
					</div>
					<h1>{ __( 'Visual schema builder', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Create structured data markup for rich search results.',
							'saman-seo'
						) }
					</p>
				</div>
				{ selectedType && (
					<div className="header-actions">
						<button
							type="button"
							className="button ghost"
							onClick={ () => {
								setSelectedType( null );
								setFormData( {} );
								setGeneratedSchema( null );
							} }
						>
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
								width="16"
								height="16"
							>
								<path d="M19 12H5M12 19l-7-7 7-7" />
							</svg>
							{ __( 'Back', 'saman-seo' ) }
						</button>
						{ generatedSchema && (
							<button
								type="button"
								className="button"
								onClick={ handleSaveTemplate }
							>
								{ __( 'Save as template', 'saman-seo' ) }
							</button>
						) }
						<button
							type="button"
							className={
								generatedSchema ? 'button' : 'button primary'
							}
							onClick={ handleGenerateSchema }
							disabled={ generating }
						>
							{ generating ? (
								<>
									<span className="spinner-small"></span>
									{ __( 'Generating\u2026', 'saman-seo' ) }
								</>
							) : (
								__( 'Generate schema', 'saman-seo' )
							) }
						</button>
						{ generatedSchema && (
							<button
								type="button"
								className="button primary"
								onClick={ handleSaveSchema }
								disabled={ saving }
							>
								{ saving
									? __( 'Saving\u2026', 'saman-seo' )
									: __( 'Save schema', 'saman-seo' ) }
							</button>
						) }
					</div>
				) }
			</div>

			<div
				className={ `page-body${
					generatedSchema ? ' two-column' : ''
				}` }
			>
				<div className="main-column">
					{ ! selectedType ? (
						<>
							<section className="panel">
								<h3>
									{ __( 'Start from a URL', 'saman-seo' ) }
								</h3>
								<div className="settings-row">
									<div className="settings-label">
										<label htmlFor="saman-seo-schema-detect-url">
											{ __(
												'Auto-detect from URL',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Suggests a schema type for the page and fills in what it can.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<div className="inline-row">
											<input
												id="saman-seo-schema-detect-url"
												type="url"
												value={ postUrl }
												onChange={ ( e ) =>
													setPostUrl( e.target.value )
												}
												placeholder={ __(
													'Enter a page URL to auto-detect schema type\u2026',
													'saman-seo'
												) }
											/>
											<button
												type="button"
												className="button"
												onClick={ handleDetectSchema }
												disabled={
													detecting || ! postUrl
												}
											>
												{ detecting ? (
													<span className="spinner-small"></span>
												) : (
													<svg
														viewBox="0 0 24 24"
														fill="none"
														stroke="currentColor"
														strokeWidth="2"
														width="16"
														height="16"
													>
														<circle
															cx="11"
															cy="11"
															r="8"
														/>
														<path d="M21 21l-4.35-4.35" />
													</svg>
												) }
												{ __( 'Detect', 'saman-seo' ) }
											</button>
										</div>
									</div>
								</div>
								<div className="settings-row">
									<div className="settings-label">
										<label htmlFor="saman-seo-schema-import-url">
											{ __(
												'Import from URL',
												'saman-seo'
											) }
										</label>
										<p className="settings-help">
											{ __(
												'Copies the structured data an existing page already has.',
												'saman-seo'
											) }
										</p>
									</div>
									<div className="settings-control">
										<div className="inline-row">
											<input
												id="saman-seo-schema-import-url"
												type="url"
												value={ importUrl }
												onChange={ ( e ) =>
													setImportUrl(
														e.target.value
													)
												}
												placeholder={ __(
													'Enter a URL to import schema from\u2026',
													'saman-seo'
												) }
											/>
											<button
												type="button"
												className="button"
												onClick={ handleImportSchema }
												disabled={
													importing || ! importUrl
												}
											>
												{ importing ? (
													<span className="spinner-small"></span>
												) : (
													<svg
														viewBox="0 0 24 24"
														fill="none"
														stroke="currentColor"
														strokeWidth="2"
														width="16"
														height="16"
													>
														<path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3" />
													</svg>
												) }
												{ __( 'Import', 'saman-seo' ) }
											</button>
										</div>
									</div>
								</div>
							</section>

							<section className="improvements-group schema-types">
								<h2 className="improvements-group__title">
									{ __(
										'Or choose schema type',
										'saman-seo'
									) }
								</h2>
								<div className="schema-types-grid">
									{ schemaTypes.map( ( type ) => (
										<button
											key={ type.id }
											type="button"
											className="schema-type-card"
											onClick={ () =>
												handleTypeSelect( type.id )
											}
										>
											<span className="schema-type-icon">
												{ type.icon }
											</span>
											<span className="schema-type-name">
												{ type.name }
											</span>
											<span className="schema-type-desc">
												{ type.description }
											</span>
										</button>
									) ) }
								</div>
							</section>
						</>
					) : (
						<section className="panel schema-fields">
							<h3>
								{ activeType?.icon } { activeType?.name }{ ' ' }
								{ __( 'Schema', 'saman-seo' ) }
							</h3>
							{ schemaFields[ selectedType ]?.map( ( field ) => {
								const fieldId = `saman-seo-schema-field-${ field.key }`;
								const isList = field.type.endsWith( 'list' );
								return (
									<div
										key={ field.key }
										className={ `settings-row${
											isList
												? ' settings-row--stacked'
												: ''
										}` }
									>
										<div className="settings-label">
											{ isList ? (
												<span className="settings-label__text">
													{ field.label }
												</span>
											) : (
												<label htmlFor={ fieldId }>
													{ field.label }
													{ field.required && (
														<span className="required">
															*
														</span>
													) }
												</label>
											) }
										</div>
										<div className="settings-control">
											{ renderField( field, fieldId ) }
										</div>
									</div>
								);
							} ) }
						</section>
					) }

					{ templates.length > 0 && (
						<section className="improvements-group">
							<h2 className="improvements-group__title">
								{ __( 'Saved templates', 'saman-seo' ) }
							</h2>
							<ul className="improvements-list">
								{ templates.map( ( template, idx ) => (
									<li key={ idx } className="improvement-row">
										<div className="improvement-row__main">
											<div className="improvement-row__title">
												<h3>{ template.name }</h3>
												<span className="pill">
													{ template.type }
												</span>
											</div>
										</div>
										<button
											type="button"
											className="button small"
											onClick={ () =>
												handleApplyTemplate( template )
											}
										>
											{ __(
												'Use template',
												'saman-seo'
											) }
										</button>
									</li>
								) ) }
							</ul>
						</section>
					) }
				</div>

				{ generatedSchema && (
					<aside className="side-panel">
						<div className="side-card schema-preview">
							<div className="side-card__head">
								<h3>
									{ __( 'Generated schema', 'saman-seo' ) }
								</h3>
								{ validation && validation.valid && (
									<span className="pill success">
										{ __(
											'Valid schema markup',
											'saman-seo'
										) }
									</span>
								) }
								{ validation && ! validation.valid && (
									<span className="pill danger">
										{ validation.errors?.length || 0 }{ ' ' }
										{ __( 'issues found', 'saman-seo' ) }
									</span>
								) }
							</div>

							{ validation?.errors?.length > 0 && (
								<ul className="note-list is-danger">
									{ validation.errors.map( ( err, idx ) => (
										<li key={ idx }>{ err }</li>
									) ) }
								</ul>
							) }

							{ validation?.warnings?.length > 0 && (
								<ul className="note-list is-warning">
									{ validation.warnings.map(
										( warn, idx ) => (
											<li key={ idx }>{ warn }</li>
										)
									) }
								</ul>
							) }

							{ generateNotices.length > 0 && (
								<ul className="note-list">
									{ generateNotices.map( ( notice, idx ) => (
										<li key={ idx }>{ notice }</li>
									) ) }
								</ul>
							) }

							<pre className="code-block">
								<code>
									{ JSON.stringify(
										generatedSchema,
										null,
										2
									) }
								</code>
							</pre>

							<div className="inline-row">
								<button
									type="button"
									className="button small"
									onClick={ handleValidate }
									disabled={ validating }
								>
									{ validating
										? __( 'Validating\u2026', 'saman-seo' )
										: __( 'Validate', 'saman-seo' ) }
								</button>
								<button
									type="button"
									className="button small"
									onClick={ handleCopySchema }
								>
									{ copied
										? __( 'Copied!', 'saman-seo' )
										: __( 'Copy', 'saman-seo' ) }
								</button>
								<a
									href={ `https://search.google.com/test/rich-results?url=${ encodeURIComponent(
										'data:application/ld+json,' +
											JSON.stringify( generatedSchema )
									) }` }
									target="_blank"
									rel="noopener noreferrer"
									className="button small"
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
							</div>
						</div>
					</aside>
				) }
			</div>
		</div>
	);
};

// Sub-components for complex field types
const RemoveButton = ( { onClick } ) => (
	<button
		type="button"
		className="button ghost small"
		aria-label={ __( 'Remove', 'saman-seo' ) }
		onClick={ onClick }
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
);
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
		<div className="repeat-list">
			{ value.map( ( item, idx ) => (
				<div key={ idx } className="repeat-item">
					<div className="repeat-item__fields">
						<input
							type="text"
							value={ item.question }
							onChange={ ( e ) =>
								updateItem( idx, 'question', e.target.value )
							}
							placeholder={ __( 'Question', 'saman-seo' ) }
						/>
						<textarea
							value={ item.answer }
							onChange={ ( e ) =>
								updateItem( idx, 'answer', e.target.value )
							}
							placeholder={ __( 'Answer', 'saman-seo' ) }
							rows={ 2 }
						/>
					</div>
					<RemoveButton onClick={ () => removeItem( idx ) } />
				</div>
			) ) }
			<button type="button" className="button small" onClick={ addItem }>
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
		<div className="repeat-list">
			{ value.map( ( item, idx ) => (
				<div key={ idx } className="repeat-item">
					<span className="repeat-item__index">{ idx + 1 }</span>
					<div className="repeat-item__fields">
						<input
							type="text"
							value={ item.name }
							onChange={ ( e ) =>
								updateItem( idx, 'name', e.target.value )
							}
							placeholder={ __( 'Step title', 'saman-seo' ) }
						/>
						<textarea
							value={ item.text }
							onChange={ ( e ) =>
								updateItem( idx, 'text', e.target.value )
							}
							placeholder={ __(
								'Step description',
								'saman-seo'
							) }
							rows={ 2 }
						/>
					</div>
					<RemoveButton onClick={ () => removeItem( idx ) } />
				</div>
			) ) }
			<button type="button" className="button small" onClick={ addItem }>
				{ __( '+ Add step', 'saman-seo' ) }
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
		<div className="repeat-list">
			{ value.map( ( item, idx ) => (
				<div key={ idx } className="repeat-item">
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
					<RemoveButton onClick={ () => removeItem( idx ) } />
				</div>
			) ) }
			<button type="button" className="button small" onClick={ addItem }>
				{ __( '+ Add item', 'saman-seo' ) }
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
		<div className="repeat-list">
			{ value.map( ( item, idx ) => (
				<div key={ idx } className="repeat-item">
					<span className="repeat-item__index">{ idx + 1 }</span>
					<input
						type="text"
						value={ item.name }
						onChange={ ( e ) =>
							updateItem( idx, 'name', e.target.value )
						}
						placeholder={ __( 'Page name', 'saman-seo' ) }
					/>
					<input
						type="url"
						value={ item.url }
						onChange={ ( e ) =>
							updateItem( idx, 'url', e.target.value )
						}
						placeholder={ __( 'URL', 'saman-seo' ) }
					/>
					<RemoveButton onClick={ () => removeItem( idx ) } />
				</div>
			) ) }
			<button type="button" className="button small" onClick={ addItem }>
				{ __( '+ Add breadcrumb', 'saman-seo' ) }
			</button>
		</div>
	);
};
export default SchemaBuilder;
