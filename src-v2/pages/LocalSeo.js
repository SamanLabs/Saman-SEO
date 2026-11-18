import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import {
	IconFacebook,
	IconXBrand,
	IconInstagram,
	IconLinkedin,
	IconYoutube,
	IconLink,
} from '../components/Icons';
import SubTabs from '../components/SubTabs';
const localSeoTabs = [
	{
		id: 'business',
		label: __( 'Business information', 'saman-seo' ),
	},
	{
		id: 'hours',
		label: __( 'Opening hours', 'saman-seo' ),
	},
	{
		id: 'reviews',
		label: __( 'Reviews', 'saman-seo' ),
	},
];
const WEEKDAYS = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ];

// One label + control row inside a panel.
const Field = ( { id, label, children } ) => (
	<div className="settings-row">
		<div className="settings-label">
			<label htmlFor={ id }>{ label }</label>
		</div>
		<div className="settings-control">{ children }</div>
	</div>
);
const businessTypes = [
	{
		value: 'LocalBusiness',
		label: __( 'Local business (generic)', 'saman-seo' ),
	},
	{
		value: 'Restaurant',
		label: __( 'Restaurant', 'saman-seo' ),
	},
	{
		value: 'Dentist',
		label: __( 'Dentist', 'saman-seo' ),
	},
	{
		value: 'Physician',
		label: __( 'Physician', 'saman-seo' ),
	},
	{
		value: 'MedicalClinic',
		label: __( 'Medical clinic', 'saman-seo' ),
	},
	{
		value: 'Attorney',
		label: __( 'Attorney', 'saman-seo' ),
	},
	{
		value: 'RealEstateAgent',
		label: __( 'Real estate agent', 'saman-seo' ),
	},
	{
		value: 'Store',
		label: __( 'Store', 'saman-seo' ),
	},
	{
		value: 'AutoDealer',
		label: __( 'Auto dealer', 'saman-seo' ),
	},
	{
		value: 'HairSalon',
		label: __( 'Hair salon', 'saman-seo' ),
	},
	{
		value: 'BeautySalon',
		label: __( 'Beauty salon', 'saman-seo' ),
	},
	{
		value: 'Plumber',
		label: __( 'Plumber', 'saman-seo' ),
	},
	{
		value: 'Electrician',
		label: __( 'Electrician', 'saman-seo' ),
	},
	{
		value: 'AccountingService',
		label: __( 'Accounting service', 'saman-seo' ),
	},
	{
		value: 'FinancialService',
		label: __( 'Financial service', 'saman-seo' ),
	},
	{
		value: 'InsuranceAgency',
		label: __( 'Insurance agency', 'saman-seo' ),
	},
	{
		value: 'Hotel',
		label: __( 'Hotel', 'saman-seo' ),
	},
	{
		value: 'Bakery',
		label: __( 'Bakery', 'saman-seo' ),
	},
	{
		value: 'BarOrPub',
		label: __( 'Bar or pub', 'saman-seo' ),
	},
	{
		value: 'CafeOrCoffeeShop',
		label: __( 'Cafe / coffee shop', 'saman-seo' ),
	},
	{
		value: 'Pharmacy',
		label: __( 'Pharmacy', 'saman-seo' ),
	},
	{
		value: 'SportsClub',
		label: __( 'Sports club', 'saman-seo' ),
	},
	{
		value: 'HealthClub',
		label: __( 'Health club / gym', 'saman-seo' ),
	},
];
const days = [
	{
		key: 'monday',
		label: __( 'Monday', 'saman-seo' ),
		short: __( 'Mon', 'saman-seo' ),
	},
	{
		key: 'tuesday',
		label: __( 'Tuesday', 'saman-seo' ),
		short: __( 'Tue', 'saman-seo' ),
	},
	{
		key: 'wednesday',
		label: __( 'Wednesday', 'saman-seo' ),
		short: __( 'Wed', 'saman-seo' ),
	},
	{
		key: 'thursday',
		label: __( 'Thursday', 'saman-seo' ),
		short: __( 'Thu', 'saman-seo' ),
	},
	{
		key: 'friday',
		label: __( 'Friday', 'saman-seo' ),
		short: __( 'Fri', 'saman-seo' ),
	},
	{
		key: 'saturday',
		label: __( 'Saturday', 'saman-seo' ),
		short: __( 'Sat', 'saman-seo' ),
	},
	{
		key: 'sunday',
		label: __( 'Sunday', 'saman-seo' ),
		short: __( 'Sun', 'saman-seo' ),
	},
];
const defaultHours = days.reduce( ( acc, day ) => {
	acc[ day.key ] = {
		enabled: false,
		open: '09:00',
		close: '17:00',
	};
	return acc;
}, {} );
const hourPresets = [
	{
		id: '9-5-weekdays',
		label: __( '9–5 Mon–Fri', 'saman-seo' ),
		apply: () => {
			const hours = {
				...defaultHours,
			};
			[ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ].forEach(
				( day ) => {
					hours[ day ] = {
						enabled: true,
						open: '09:00',
						close: '17:00',
					};
				}
			);
			return hours;
		},
	},
	{
		id: '9-6-mon-sat',
		label: __( '9–6 Mon–Sat', 'saman-seo' ),
		apply: () => {
			const hours = {
				...defaultHours,
			};
			[
				'monday',
				'tuesday',
				'wednesday',
				'thursday',
				'friday',
				'saturday',
			].forEach( ( day ) => {
				hours[ day ] = {
					enabled: true,
					open: '09:00',
					close: '18:00',
				};
			} );
			return hours;
		},
	},
	{
		id: '24-7',
		label: '24/7',
		apply: () => {
			const hours = {};
			days.forEach( ( day ) => {
				hours[ day.key ] = {
					enabled: true,
					open: '00:00',
					close: '23:59',
				};
			} );
			return hours;
		},
	},
	{
		id: 'custom',
		label: __( 'Custom', 'saman-seo' ),
		apply: null,
	},
];

// Knowledge Panel Preview Component
const KnowledgePanelPreview = ( { business, hours, socialProfiles } ) => {
	const addressParts = [
		business.street,
		business.city,
		business.state,
	].filter( Boolean );
	const addressString =
		addressParts.join( ', ' ) + ( business.zip ? ' ' + business.zip : '' );

	// Calculate if currently open
	const getHoursStatus = () => {
		if ( ! hours || Object.keys( hours ).length === 0 ) {
			return {
				isOpen: false,
				text: __( 'Hours not set', 'saman-seo' ),
			};
		}
		const now = new Date();
		const dayNames = [
			'sunday',
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
		];
		const currentDay = dayNames[ now.getDay() ];
		const currentTime =
			now.getHours().toString().padStart( 2, '0' ) +
			':' +
			now.getMinutes().toString().padStart( 2, '0' );
		const todayHours = hours[ currentDay ];
		if ( ! todayHours || ! todayHours.enabled ) {
			return {
				isOpen: false,
				text: __( 'Closed today', 'saman-seo' ),
			};
		}
		if (
			currentTime >= todayHours.open &&
			currentTime <= todayHours.close
		) {
			const closeTime = new Date( `2000-01-01T${ todayHours.close }` );
			return {
				isOpen: true,
				text: sprintf(
					/* translators: %s: placeholder */ __(
						'Open \xB7 Closes %s',
						'saman-seo'
					),
					closeTime.toLocaleTimeString( [], {
						hour: 'numeric',
						minute: '2-digit',
					} )
				),
			};
		}
		const openTime = new Date( `2000-01-01T${ todayHours.open }` );
		return {
			isOpen: false,
			text: sprintf(
				/* translators: %s: placeholder */ __(
					'Closed \xB7 Opens %s',
					'saman-seo'
				),
				openTime.toLocaleTimeString( [], {
					hour: 'numeric',
					minute: '2-digit',
				} )
			),
		};
	};
	const hoursStatus = getHoursStatus();
	const businessTypeLabel =
		businessTypes.find( ( t ) => t.value === business.type )?.label ||
		'Local Business';

	// Parse social profiles
	const getSocialIcon = ( url ) => {
		if ( ! url ) return null;
		if ( url.includes( 'facebook.com' ) ) return <IconFacebook />;
		if ( url.includes( 'twitter.com' ) || url.includes( 'x.com' ) )
			return <IconXBrand />;
		if ( url.includes( 'instagram.com' ) ) return <IconInstagram />;
		if ( url.includes( 'linkedin.com' ) ) return <IconLinkedin />;
		if ( url.includes( 'youtube.com' ) ) return <IconYoutube />;
		return <IconLink />;
	};
	const socialLinks = ( socialProfiles || [] )
		.filter( Boolean )
		.map( ( url ) => ( {
			url,
			icon: getSocialIcon( url ),
		} ) );
	return (
		<div className="side-card knowledge-panel">
			<h3 className="knowledge-panel__title">
				<svg
					viewBox="0 0 24 24"
					width="16"
					height="16"
					fill="currentColor"
				>
					<path
						d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
						fill="#4285F4"
					/>
					<path
						d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
						fill="#34A853"
					/>
					<path
						d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
						fill="#FBBC05"
					/>
					<path
						d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
						fill="#EA4335"
					/>
				</svg>
				<span>{ __( 'Knowledge panel preview', 'saman-seo' ) }</span>
			</h3>

			{ business.image ? (
				<div
					className="knowledge-panel__cover has-image"
					style={ {
						backgroundImage: `url(${ business.image })`,
					} }
				/>
			) : (
				<div className="knowledge-panel__cover">
					<svg
						viewBox="0 0 24 24"
						width="32"
						height="32"
						fill="currentColor"
						opacity="0.3"
					>
						<rect
							x="3"
							y="3"
							width="18"
							height="18"
							rx="2"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						/>
						<circle cx="8.5" cy="8.5" r="1.5" />
						<path d="M21 15l-5-5L5 21" />
					</svg>
				</div>
			) }

			<div className="knowledge-panel__body">
				<div className="knowledge-panel__identity">
					{ business.logo ? (
						<div
							className="knowledge-panel__logo has-image"
							style={ {
								backgroundImage: `url(${ business.logo })`,
							} }
						/>
					) : (
						<div className="knowledge-panel__logo">
							<svg
								viewBox="0 0 24 24"
								width="24"
								height="24"
								fill="currentColor"
								opacity="0.4"
							>
								<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
							</svg>
						</div>
					) }
					<div className="knowledge-panel__info">
						<h3
							className={ `knowledge-panel__name ${
								! business.name ? 'is-empty' : ''
							}` }
						>
							{ business.name ||
								__( 'Business name', 'saman-seo' ) }
						</h3>
						<p className="knowledge-panel__type">
							{ businessTypeLabel }
						</p>
					</div>
				</div>

				{ business.description ? (
					<p className="knowledge-panel__description">
						{ business.description }
					</p>
				) : (
					<p className="knowledge-panel__description is-empty">
						{ __(
							'Add a description to tell customers about your business.',
							'saman-seo'
						) }
					</p>
				) }

				<div className="knowledge-panel__details">
					<div
						className={ `knowledge-panel__detail ${
							! addressString ? 'is-empty' : ''
						}` }
					>
						<svg
							viewBox="0 0 24 24"
							width="16"
							height="16"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z" />
							<circle cx="12" cy="10" r="3" />
						</svg>
						<span>
							{ addressString ||
								__( 'No address set', 'saman-seo' ) }
						</span>
					</div>

					<div
						className={ `knowledge-panel__detail ${
							! business.phone ? 'is-empty' : ''
						}` }
					>
						<svg
							viewBox="0 0 24 24"
							width="16"
							height="16"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z" />
						</svg>
						<span>
							{ business.phone ||
								__( 'No phone set', 'saman-seo' ) }
						</span>
					</div>

					<div className="knowledge-panel__detail">
						<svg
							viewBox="0 0 24 24"
							width="16"
							height="16"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<circle cx="12" cy="12" r="10" />
							<polyline points="12 6 12 12 16 14" />
						</svg>
						<span
							className={ `knowledge-panel__hours-status ${
								hoursStatus.isOpen ? 'is-open' : 'is-closed'
							}` }
						>
							{ hoursStatus.text }
						</span>
					</div>

					{ business.priceRange && (
						<div className="knowledge-panel__detail">
							<svg
								viewBox="0 0 24 24"
								width="16"
								height="16"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
							>
								<line x1="12" y1="1" x2="12" y2="23" />
								<path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6" />
							</svg>
							<span className="knowledge-panel__price">
								{ business.priceRange }
							</span>
						</div>
					) }
				</div>

				{ socialLinks.length > 0 && (
					<div className="knowledge-panel__social">
						{ socialLinks.map( ( social, idx ) => (
							<a
								key={ idx }
								href={ social.url }
								target="_blank"
								rel="noopener noreferrer"
								title={ social.url }
							>
								{ social.icon }
							</a>
						) ) }
					</div>
				) }
			</div>

			<div className="knowledge-panel__actions">
				<a
					href="https://search.google.com/test/rich-results"
					target="_blank"
					rel="noopener noreferrer"
					className="button ghost small"
				>
					{ __( 'Test with Google', 'saman-seo' ) }
				</a>
			</div>
		</div>
	);
};
const LocalSeo = () => {
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ activeTab, setActiveTab ] = useState( 'business' );
	const [ notice, setNotice ] = useState( null );

	// Business info state
	const [ business, setBusiness ] = useState( {
		name: '',
		type: 'LocalBusiness',
		description: '',
		phone: '',
		email: '',
		street: '',
		city: '',
		state: '',
		zip: '',
		country: '',
		latitude: '',
		longitude: '',
		priceRange: '',
		logo: '',
		image: '',
	} );

	// Opening hours state
	const [ hours, setHours ] = useState( defaultHours );
	const [ activePreset, setActivePreset ] = useState( 'custom' );

	// Social profiles state
	const [ socialProfiles, setSocialProfiles ] = useState( [
		'',
		'',
		'',
		'',
		'',
	] );

	// Reviews schema state (off until the owner turns it on)
	const [ reviews, setReviews ] = useState( {
		enabled: false,
		scope: 'front_page',
		pageIds: [],
		ratingValue: '',
		count: '',
		items: [],
	} );
	const [ pages, setPages ] = useState( [] );
	const loadSettings = useCallback( async () => {
		try {
			setLoading( true );
			const response = await apiFetch( {
				path: '/saman-seo/v1/settings',
			} );
			if ( response.success ) {
				const data = response.data;
				setBusiness( {
					name:
						data.local_business_name ||
						data.homepage_organization_name ||
						'',
					type: data.local_business_type || 'LocalBusiness',
					description: data.local_description || '',
					phone: data.local_phone || '',
					email: data.local_email || '',
					street: data.local_street || '',
					city: data.local_city || '',
					state: data.local_state || '',
					zip: data.local_zip || '',
					country: data.local_country || '',
					latitude: data.local_latitude || '',
					longitude: data.local_longitude || '',
					priceRange: data.local_price_range || '',
					logo:
						data.local_logo ||
						data.homepage_organization_logo ||
						'',
					image: data.local_image || '',
				} );

				// Load reviews
				setReviews( {
					enabled: '1' === String( data.reviews_enabled || '' ),
					scope: 'pages' === data.reviews_scope ? 'pages' : 'front_page',
					pageIds: Array.isArray( data.reviews_page_ids )
						? data.reviews_page_ids.map( Number )
						: [],
					ratingValue: data.reviews_rating_value || '',
					count: data.reviews_count ? String( data.reviews_count ) : '',
					items: Array.isArray( data.reviews_items )
						? data.reviews_items.map( ( item ) => ( {
								author: item.author || '',
								text: item.text || '',
								rating: item.rating ? String( item.rating ) : '',
								date: item.date || '',
						  } ) )
						: [],
				} );

				// Load hours
				if ( data.local_opening_hours ) {
					try {
						const parsedHours =
							typeof data.local_opening_hours === 'string'
								? JSON.parse( data.local_opening_hours )
								: data.local_opening_hours;
						setHours( {
							...defaultHours,
							...parsedHours,
						} );
					} catch {
						setHours( defaultHours );
					}
				}

				// Load social profiles
				if ( data.local_social_profiles ) {
					try {
						const profiles =
							typeof data.local_social_profiles === 'string'
								? JSON.parse( data.local_social_profiles )
								: data.local_social_profiles;
						setSocialProfiles(
							Array.isArray( profiles )
								? [ ...profiles, '', '', '', '', '' ].slice(
										0,
										5
								  )
								: [ '', '', '', '', '' ]
						);
					} catch {
						setSocialProfiles( [ '', '', '', '', '' ] );
					}
				}
			}
		} catch ( error ) {
			console.error( 'Failed to load settings:', error );
			setNotice( {
				type: 'error',
				message: __( 'Failed to load settings.', 'saman-seo' ),
			} );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		loadSettings();
	}, [ loadSettings ] );

	// Pages the owner can limit the reviews markup to.
	useEffect( () => {
		apiFetch( {
			path: '/wp/v2/pages?per_page=100&status=publish&_fields=id,title',
		} )
			.then( ( list ) =>
				setPages(
					Array.isArray( list )
						? list.map( ( page ) => ( {
								id: page.id,
								// Titles arrive HTML-escaped; read them back as plain text.
								title:
									new window.DOMParser().parseFromString(
										page.title?.rendered || '',
										'text/html'
									).documentElement.textContent ||
									`#${ page.id }`,
						  } ) )
						: []
				)
			)
			.catch( () => setPages( [] ) );
	}, [] );
	useEffect( () => {
		if ( notice ) {
			const timer = setTimeout( () => setNotice( null ), 5000 );
			return () => clearTimeout( timer );
		}
	}, [ notice ] );
	const saveSettings = async () => {
		setSaving( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/settings',
				method: 'POST',
				data: {
					local_business_name: business.name,
					local_business_type: business.type,
					local_description: business.description,
					local_phone: business.phone,
					local_email: business.email,
					local_street: business.street,
					local_city: business.city,
					local_state: business.state,
					local_zip: business.zip,
					local_country: business.country,
					local_latitude: business.latitude,
					local_longitude: business.longitude,
					local_price_range: business.priceRange,
					local_logo: business.logo,
					local_image: business.image,
					local_opening_hours: JSON.stringify( hours ),
					local_social_profiles: JSON.stringify(
						socialProfiles.filter( Boolean )
					),
					// Sync with Knowledge Graph
					homepage_organization_name: business.name,
					homepage_organization_logo: business.logo,
					// Reviews schema
					reviews_enabled: reviews.enabled,
					reviews_scope: reviews.scope,
					reviews_page_ids: reviews.pageIds,
					reviews_rating_value: reviews.ratingValue,
					reviews_count: reviews.count === '' ? 0 : Number( reviews.count ),
					reviews_items: reviews.items,
				},
			} );
			setNotice( {
				type: 'success',
				message: __( 'Settings saved successfully.', 'saman-seo' ),
			} );
		} catch ( error ) {
			console.error( 'Failed to save settings:', error );
			setNotice( {
				type: 'error',
				message: __( 'Failed to save settings.', 'saman-seo' ),
			} );
		} finally {
			setSaving( false );
		}
	};
	const handleBusinessChange = ( field, value ) => {
		setBusiness( ( prev ) => ( {
			...prev,
			[ field ]: value,
		} ) );
	};
	const handleHoursChange = ( day, field, value ) => {
		setHours( ( prev ) => ( {
			...prev,
			[ day ]: {
				...prev[ day ],
				[ field ]: value,
			},
		} ) );
		setActivePreset( 'custom' );
	};
	const applyPreset = ( preset ) => {
		if ( preset.apply ) {
			setHours( preset.apply() );
		}
		setActivePreset( preset.id );
	};
	const copyToWeekdays = ( sourceDay ) => {
		const sourceHours = hours[ sourceDay ];
		const weekdays = [
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
		];
		setHours( ( prev ) => {
			const updated = {
				...prev,
			};
			weekdays.forEach( ( day ) => {
				updated[ day ] = {
					...sourceHours,
				};
			} );
			return updated;
		} );
		setActivePreset( 'custom' );
	};
	const handleReviewsChange = ( field, value ) => {
		setReviews( ( prev ) => ( { ...prev, [ field ]: value } ) );
	};
	const handleReviewItemChange = ( index, field, value ) => {
		setReviews( ( prev ) => ( {
			...prev,
			items: prev.items.map( ( item, i ) =>
				i === index ? { ...item, [ field ]: value } : item
			),
		} ) );
	};
	const addReviewItem = () => {
		setReviews( ( prev ) => ( {
			...prev,
			items: [
				...prev.items,
				{ author: '', text: '', rating: '', date: '' },
			],
		} ) );
	};
	const removeReviewItem = ( index ) => {
		setReviews( ( prev ) => ( {
			...prev,
			items: prev.items.filter( ( item, i ) => i !== index ),
		} ) );
	};
	const togglePage = ( id ) => {
		setReviews( ( prev ) => ( {
			...prev,
			pageIds: prev.pageIds.includes( id )
				? prev.pageIds.filter( ( pageId ) => pageId !== id )
				: [ ...prev.pageIds, id ],
		} ) );
	};
	const handleSocialChange = ( index, value ) => {
		setSocialProfiles( ( prev ) => {
			const updated = [ ...prev ];
			updated[ index ] = value;
			return updated;
		} );
	};

	// Media library picker
	const openMediaLibrary = ( field ) => {
		if ( window.wp && window.wp.media ) {
			const frame = window.wp.media( {
				title:
					field === 'logo'
						? __( 'Select logo', 'saman-seo' )
						: __( 'Select cover image', 'saman-seo' ),
				button: {
					text: __( 'Use image', 'saman-seo' ),
				},
				multiple: false,
			} );
			frame.on( 'select', () => {
				const attachment = frame
					.state()
					.get( 'selection' )
					.first()
					.toJSON();
				handleBusinessChange( field, attachment.url );
			} );
			frame.open();
		}
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="loading-state">
					<span className="spinner is-active"></span>
					<p>{ __( 'Loading Local SEO settings…', 'saman-seo' ) }</p>
				</div>
			</div>
		);
	}
	const renderInput = ( field, label, inputProps = {} ) => (
		<Field id={ `local-${ field }` } label={ label }>
			<input
				id={ `local-${ field }` }
				type="text"
				value={ business[ field ] }
				onChange={ ( e ) =>
					handleBusinessChange( field, e.target.value )
				}
				{ ...inputProps }
			/>
		</Field>
	);
	const renderImagePicker = ( field, label, buttonLabel, alt ) => (
		<Field id={ `local-${ field }` } label={ label }>
			<div className="image-picker">
				{ business[ field ] ? (
					<div className="image-preview">
						<img src={ business[ field ] } alt={ alt } />
						<button
							type="button"
							className="image-remove"
							aria-label={ __( 'Remove image', 'saman-seo' ) }
							onClick={ () => handleBusinessChange( field, '' ) }
						>
							×
						</button>
					</div>
				) : (
					<button
						type="button"
						id={ `local-${ field }` }
						className="button ghost"
						onClick={ () => openMediaLibrary( field ) }
					>
						{ buttonLabel }
					</button>
				) }
			</div>
		</Field>
	);
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Local SEO', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Configure your business information for local search results and Google Knowledge Panel.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<button
						type="button"
						className="button primary"
						onClick={ saveSettings }
						disabled={ saving }
					>
						{ saving
							? __( 'Saving…', 'saman-seo' )
							: __( 'Save changes', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<SubTabs
				tabs={ localSeoTabs }
				activeTab={ activeTab }
				onChange={ setActiveTab }
				ariaLabel={ __( 'Local SEO sections', 'saman-seo' ) }
			/>

			{ notice && (
				<div
					className={ `notice-message ${ notice.type }` }
					role="status"
				>
					<span>{ notice.message }</span>
					<button
						type="button"
						className="link-button"
						onClick={ () => setNotice( null ) }
					>
						{ __( 'Dismiss', 'saman-seo' ) }
					</button>
				</div>
			) }

			<div className="page-body two-column">
				<div className="main-column">
					{ activeTab === 'business' && (
						<>
							<section className="panel">
								<h3>
									{ __( 'Business identity', 'saman-seo' ) }
								</h3>
								{ renderInput(
									'name',
									__( 'Business name', 'saman-seo' ),
									{
										placeholder: __(
											'Your business name',
											'saman-seo'
										),
									}
								) }
								<Field
									id="local-type"
									label={ __( 'Business type', 'saman-seo' ) }
								>
									<select
										id="local-type"
										value={ business.type }
										onChange={ ( e ) =>
											handleBusinessChange(
												'type',
												e.target.value
											)
										}
									>
										{ businessTypes.map( ( type ) => (
											<option
												key={ type.value }
												value={ type.value }
											>
												{ type.label }
											</option>
										) ) }
									</select>
								</Field>
								<Field
									id="local-description"
									label={ __( 'Description', 'saman-seo' ) }
								>
									<textarea
										id="local-description"
										value={ business.description }
										onChange={ ( e ) =>
											handleBusinessChange(
												'description',
												e.target.value
											)
										}
										placeholder={ __(
											'Brief description of your business',
											'saman-seo'
										) }
										rows={ 3 }
									/>
								</Field>
								{ renderImagePicker(
									'logo',
									__( 'Logo', 'saman-seo' ),
									__( 'Select logo', 'saman-seo' ),
									__( 'Logo', 'saman-seo' )
								) }
								{ renderImagePicker(
									'image',
									__( 'Cover image', 'saman-seo' ),
									__( 'Select image', 'saman-seo' ),
									__( 'Cover', 'saman-seo' )
								) }
								<Field
									id="local-price-range"
									label={ __( 'Price range', 'saman-seo' ) }
								>
									<select
										id="local-price-range"
										value={ business.priceRange }
										onChange={ ( e ) =>
											handleBusinessChange(
												'priceRange',
												e.target.value
											)
										}
									>
										<option value="">
											{ __( 'Select…', 'saman-seo' ) }
										</option>
										<option value="$">
											{ __( '$ (Budget)', 'saman-seo' ) }
										</option>
										<option value="$$">
											{ __(
												'$$ (Moderate)',
												'saman-seo'
											) }
										</option>
										<option value="$$$">
											{ __(
												'$$$ (Expensive)',
												'saman-seo'
											) }
										</option>
										<option value="$$$$">
											{ __(
												'$$$$ (Luxury)',
												'saman-seo'
											) }
										</option>
									</select>
								</Field>
							</section>

							<section className="panel">
								<h3>
									{ __( 'Contact information', 'saman-seo' ) }
								</h3>
								{ renderInput(
									'phone',
									__( 'Phone', 'saman-seo' ),
									{
										type: 'tel',
										placeholder: '+1 (555) 123-4567',
									}
								) }
								{ renderInput(
									'email',
									__( 'Email', 'saman-seo' ),
									{
										type: 'email',
										placeholder: __(
											'contact@yourbusiness.com',
											'saman-seo'
										),
									}
								) }
							</section>

							<section className="panel">
								<h3>{ __( 'Address', 'saman-seo' ) }</h3>
								{ renderInput(
									'street',
									__( 'Street address', 'saman-seo' ),
									{
										placeholder: __(
											'123 Main Street',
											'saman-seo'
										),
									}
								) }
								{ renderInput(
									'city',
									__( 'City', 'saman-seo' ),
									{
										placeholder: __(
											'New York',
											'saman-seo'
										),
									}
								) }
								{ renderInput(
									'state',
									__( 'State/Province', 'saman-seo' ),
									{
										placeholder: __( 'NY', 'saman-seo' ),
									}
								) }
								{ renderInput(
									'zip',
									__( 'ZIP/Postal code', 'saman-seo' ),
									{
										placeholder: '10001',
									}
								) }
								{ renderInput(
									'country',
									__( 'Country', 'saman-seo' ),
									{
										placeholder: __( 'US', 'saman-seo' ),
									}
								) }
							</section>

							<section className="panel">
								<h3>
									{ __( 'Geo coordinates', 'saman-seo' ) }
								</h3>
								<p className="panel-desc">
									{ __(
										'Optional but recommended for precise location in search results.',
										'saman-seo'
									) }{ ' ' }
									<a
										href={ `https://www.google.com/maps/search/${ encodeURIComponent(
											[
												business.street,
												business.city,
												business.state,
												business.zip,
											]
												.filter( Boolean )
												.join( ', ' )
										) }` }
										target="_blank"
										rel="noopener noreferrer"
									>
										{ __(
											'Find coordinates on Google Maps →',
											'saman-seo'
										) }
									</a>
								</p>
								{ renderInput(
									'latitude',
									__( 'Latitude', 'saman-seo' ),
									{
										placeholder: '40.7128',
									}
								) }
								{ renderInput(
									'longitude',
									__( 'Longitude', 'saman-seo' ),
									{
										placeholder: '-74.0060',
									}
								) }
							</section>

							<section className="panel">
								<h3>
									{ __( 'Social profiles', 'saman-seo' ) }
								</h3>
								<p className="panel-desc">
									{ __(
										'Add your social media profile URLs for rich search results.',
										'saman-seo'
									) }
								</p>
								{ socialProfiles.map( ( url, index ) => (
									<Field
										key={ index }
										id={ `local-social-${ index }` }
										label={ sprintf(
											/* translators: %s: placeholder */ __(
												'Social profile URL %s',
												'saman-seo'
											),
											index + 1
										) }
									>
										<input
											id={ `local-social-${ index }` }
											type="url"
											value={ url }
											onChange={ ( e ) =>
												handleSocialChange(
													index,
													e.target.value
												)
											}
											placeholder="https://"
										/>
									</Field>
								) ) }
							</section>
						</>
					) }

					{ activeTab === 'hours' && (
						<section className="panel">
							<h3>{ __( 'Opening hours', 'saman-seo' ) }</h3>
							<p className="panel-desc">
								{ __(
									'Set your business hours. These appear in search results and Google Knowledge Panel.',
									'saman-seo'
								) }
							</p>
							<div className="settings-row hours-day-row">
								<div className="settings-label">
									<span className="settings-label__text">
										{ __( 'Quick presets', 'saman-seo' ) }
									</span>
								</div>
								<div className="settings-control">
									<div className="hours-control">
										{ hourPresets.map( ( preset ) => (
											<button
												key={ preset.id }
												type="button"
												className={ `button ${
													activePreset === preset.id
														? 'primary'
														: 'ghost'
												} small` }
												aria-pressed={
													activePreset === preset.id
												}
												onClick={ () =>
													applyPreset( preset )
												}
											>
												{ preset.label }
											</button>
										) ) }
									</div>
								</div>
							</div>
							{ days.map( ( day ) => (
								<div
									key={ day.key }
									className="settings-row hours-day-row"
								>
									<div className="settings-label">
										<label htmlFor={ `hours-${ day.key }` }>
											{ day.label }
										</label>
									</div>
									<div className="settings-control">
										<div className="hours-control">
											<label className="toggle">
												<input
													id={ `hours-${ day.key }` }
													type="checkbox"
													checked={
														hours[ day.key ]
															?.enabled || false
													}
													onChange={ ( e ) =>
														handleHoursChange(
															day.key,
															'enabled',
															e.target.checked
														)
													}
												/>
												<span className="toggle-track"></span>
											</label>
											{ hours[ day.key ]?.enabled ? (
												<>
													<input
														type="time"
														aria-label={ sprintf(
															/* translators: %s: day of the week */ __(
																'%s opening time',
																'saman-seo'
															),
															day.label
														) }
														value={
															hours[ day.key ]
																?.open ||
															'09:00'
														}
														onChange={ ( e ) =>
															handleHoursChange(
																day.key,
																'open',
																e.target.value
															)
														}
														className="hours-time"
													/>
													<span className="hours-separator">
														{ __(
															'to',
															'saman-seo'
														) }
													</span>
													<input
														type="time"
														aria-label={ sprintf(
															/* translators: %s: day of the week */ __(
																'%s closing time',
																'saman-seo'
															),
															day.label
														) }
														value={
															hours[ day.key ]
																?.close ||
															'17:00'
														}
														onChange={ ( e ) =>
															handleHoursChange(
																day.key,
																'close',
																e.target.value
															)
														}
														className="hours-time"
													/>
													{ WEEKDAYS.includes(
														day.key
													) && (
														<button
															type="button"
															className="link-button"
															onClick={ () =>
																copyToWeekdays(
																	day.key
																)
															}
															title={ __(
																'Copy to all weekdays',
																'saman-seo'
															) }
														>
															{ __(
																'Copy to weekdays',
																'saman-seo'
															) }
														</button>
													) }
												</>
											) : (
												<span className="hours-closed">
													{ __(
														'Closed',
														'saman-seo'
													) }
												</span>
											) }
										</div>
									</div>
								</div>
							) ) }
						</section>
					) }
					{ activeTab === 'reviews' && (
						<>
							<div className="alert-banner alert-banner--warning">
								<div>
									<p>
										<strong>
											{ __(
												'This markup does not earn Google review stars.',
												'saman-seo'
											) }
										</strong>{ ' ' }
										{ __(
											'Google does not show star ratings for LocalBusiness or Organization pages when the business controls the reviews about itself. This is valid schema.org data that other consumers may read, but do not expect rich results in Google Search.',
											'saman-seo'
										) }
									</p>
									<p>
										{ __(
											'Only enter reviews that are real, from genuine customers, and shown to visitors on the pages you pick below. Markup for content people cannot see breaks Google’s structured data guidelines.',
											'saman-seo'
										) }
									</p>
								</div>
							</div>

							<section className="panel">
								<h3>{ __( 'Review schema', 'saman-seo' ) }</h3>
								<p className="panel-desc">
									{ __(
										'Adds AggregateRating and Review structured data about your business, using the values you enter here. Nothing is fetched from Google or any review service.',
										'saman-seo'
									) }
								</p>
								<Field
									id="reviews-enabled"
									label={ __(
										'Output review schema',
										'saman-seo'
									) }
								>
									<label className="toggle">
										<input
											id="reviews-enabled"
											type="checkbox"
											checked={ reviews.enabled }
											onChange={ ( e ) =>
												handleReviewsChange(
													'enabled',
													e.target.checked
												)
											}
										/>
										<span className="toggle-track"></span>
									</label>
								</Field>
								<Field
									id="reviews-scope"
									label={ __( 'Output on', 'saman-seo' ) }
								>
									<select
										id="reviews-scope"
										value={ reviews.scope }
										onChange={ ( e ) =>
											handleReviewsChange(
												'scope',
												e.target.value
											)
										}
									>
										<option value="front_page">
											{ __(
												'Front page only',
												'saman-seo'
											) }
										</option>
										<option value="pages">
											{ __(
												'Selected pages only',
												'saman-seo'
											) }
										</option>
									</select>
								</Field>
								{ reviews.scope === 'pages' && (
									<div className="settings-row">
										<div className="settings-label">
											<span className="settings-label__text">
												{ __( 'Pages', 'saman-seo' ) }
											</span>
										</div>
										<div className="settings-control">
											{ pages.length === 0 ? (
												<p className="settings-help">
													{ __(
														'No published pages found.',
														'saman-seo'
													) }
												</p>
											) : (
												<div className="checkbox-grid reviews-pages">
													{ pages.map( ( page ) => (
														<label
															key={ page.id }
															className="checkbox-item"
														>
															<input
																type="checkbox"
																checked={ reviews.pageIds.includes(
																	page.id
																) }
																onChange={ () =>
																	togglePage(
																		page.id
																	)
																}
															/>
															<span>
																{ page.title }
															</span>
														</label>
													) ) }
												</div>
											) }
										</div>
									</div>
								) }
							</section>

							<section className="panel">
								<h3>{ __( 'Overall rating', 'saman-seo' ) }</h3>
								<p className="panel-desc">
									{ __(
										'Leave both blank to calculate them from the complete reviews below. A review count lower than the reviews listed is raised to match.',
										'saman-seo'
									) }
								</p>
								<Field
									id="reviews-rating"
									label={ __(
										'Rating value (1–5)',
										'saman-seo'
									) }
								>
									<input
										id="reviews-rating"
										className="input-narrow"
										type="number"
										min="1"
										max="5"
										step="0.1"
										value={ reviews.ratingValue }
										onChange={ ( e ) =>
											handleReviewsChange(
												'ratingValue',
												e.target.value
											)
										}
									/>
								</Field>
								<Field
									id="reviews-count"
									label={ __(
										'Total number of reviews',
										'saman-seo'
									) }
								>
									<input
										id="reviews-count"
										className="input-narrow"
										type="number"
										min="0"
										step="1"
										value={ reviews.count }
										onChange={ ( e ) =>
											handleReviewsChange(
												'count',
												e.target.value
											)
										}
									/>
								</Field>
							</section>

							<section className="panel">
								<h3>{ __( 'Reviews', 'saman-seo' ) }</h3>
								<p className="panel-desc">
									{ __(
										'A review is only output when it has a name, text and a rating. Incomplete rows are saved but skipped.',
										'saman-seo'
									) }
								</p>
								{ reviews.items.map( ( item, index ) => (
									<div className="review-item" key={ index }>
										<div className="review-item__fields">
											<div className="form-field">
												<label
													htmlFor={ `review-author-${ index }` }
												>
													{ __(
														'Reviewer name',
														'saman-seo'
													) }
												</label>
												<input
													id={ `review-author-${ index }` }
													type="text"
													maxLength={ 99 }
													value={ item.author }
													onChange={ ( e ) =>
														handleReviewItemChange(
															index,
															'author',
															e.target.value
														)
													}
												/>
											</div>
											<div className="form-field">
												<label
													htmlFor={ `review-rating-${ index }` }
												>
													{ __(
														'Rating (1–5)',
														'saman-seo'
													) }
												</label>
												<input
													id={ `review-rating-${ index }` }
													type="number"
													min="1"
													max="5"
													step="0.5"
													value={ item.rating }
													onChange={ ( e ) =>
														handleReviewItemChange(
															index,
															'rating',
															e.target.value
														)
													}
												/>
											</div>
											<div className="form-field">
												<label
													htmlFor={ `review-date-${ index }` }
												>
													{ __(
														'Date (optional)',
														'saman-seo'
													) }
												</label>
												<input
													id={ `review-date-${ index }` }
													type="date"
													value={ item.date }
													onChange={ ( e ) =>
														handleReviewItemChange(
															index,
															'date',
															e.target.value
														)
													}
												/>
											</div>
										</div>
										<div className="form-field">
											<label
												htmlFor={ `review-text-${ index }` }
											>
												{ __(
													'Review text',
													'saman-seo'
												) }
											</label>
											<textarea
												id={ `review-text-${ index }` }
												rows="3"
												value={ item.text }
												onChange={ ( e ) =>
													handleReviewItemChange(
														index,
														'text',
														e.target.value
													)
												}
											/>
										</div>
										<button
											type="button"
											className="link-button danger"
											onClick={ () =>
												removeReviewItem( index )
											}
										>
											{ __(
												'Remove review',
												'saman-seo'
											) }
										</button>
									</div>
								) ) }
								<div className="review-item__add">
									<button
										type="button"
										className="button ghost"
										onClick={ addReviewItem }
									>
										{ __( 'Add review', 'saman-seo' ) }
									</button>
								</div>
							</section>
						</>
					) }
				</div>

				<aside className="side-panel local-seo-preview">
					<KnowledgePanelPreview
						business={ business }
						hours={ hours }
						socialProfiles={ socialProfiles }
					/>
				</aside>
			</div>
		</div>
	);
};
export default LocalSeo;
