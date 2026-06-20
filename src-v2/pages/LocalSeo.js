import { useState, useEffect, useCallback, useMemo } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
const businessTypes = [
	{
		value: 'LocalBusiness',
		label: __( 'Local Business (Generic)', 'saman-seo' ),
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
		label: __( 'Medical Clinic', 'saman-seo' ),
	},
	{
		value: 'Attorney',
		label: __( 'Attorney', 'saman-seo' ),
	},
	{
		value: 'RealEstateAgent',
		label: __( 'Real Estate Agent', 'saman-seo' ),
	},
	{
		value: 'Store',
		label: __( 'Store', 'saman-seo' ),
	},
	{
		value: 'AutoDealer',
		label: __( 'Auto Dealer', 'saman-seo' ),
	},
	{
		value: 'HairSalon',
		label: __( 'Hair Salon', 'saman-seo' ),
	},
	{
		value: 'BeautySalon',
		label: __( 'Beauty Salon', 'saman-seo' ),
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
		label: __( 'Accounting Service', 'saman-seo' ),
	},
	{
		value: 'FinancialService',
		label: __( 'Financial Service', 'saman-seo' ),
	},
	{
		value: 'InsuranceAgency',
		label: __( 'Insurance Agency', 'saman-seo' ),
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
		label: __( 'Bar or Pub', 'saman-seo' ),
	},
	{
		value: 'CafeOrCoffeeShop',
		label: __( 'Cafe / Coffee Shop', 'saman-seo' ),
	},
	{
		value: 'Pharmacy',
		label: __( 'Pharmacy', 'saman-seo' ),
	},
	{
		value: 'SportsClub',
		label: __( 'Sports Club', 'saman-seo' ),
	},
	{
		value: 'HealthClub',
		label: __( 'Health Club / Gym', 'saman-seo' ),
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
		if ( url.includes( 'facebook.com' ) ) return '📘';
		if ( url.includes( 'twitter.com' ) || url.includes( 'x.com' ) )
			return '𝕏';
		if ( url.includes( 'instagram.com' ) ) return '📷';
		if ( url.includes( 'linkedin.com' ) ) return '💼';
		if ( url.includes( 'youtube.com' ) ) return '▶️';
		return '🔗';
	};
	const socialLinks = ( socialProfiles || [] )
		.filter( Boolean )
		.map( ( url ) => ( {
			url,
			icon: getSocialIcon( url ),
		} ) );
	return (
		<div className="knowledge-panel">
			<div className="knowledge-panel__header">
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
				<span>{ __( 'Knowledge Panel Preview', 'saman-seo' ) }</span>
			</div>

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
								__( 'Business Name', 'saman-seo' ) }
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
					} catch ( e ) {
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
					} catch ( e ) {
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
						? __( 'Select Logo', 'saman-seo' )
						: __( 'Select Cover Image', 'saman-seo' ),
				button: {
					text: __( 'Use Image', 'saman-seo' ),
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
					<p>
						{ __(
							'Loading Local SEO settings\u2026',
							'saman-seo'
						) }
					</p>
				</div>
			</div>
		);
	}
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
				<button
					className="button primary"
					onClick={ saveSettings }
					disabled={ saving }
				>
					{ saving
						? __( 'Saving\u2026', 'saman-seo' )
						: __( 'Save Changes', 'saman-seo' ) }
				</button>
			</div>

			{ notice && (
				<div className={ `notice notice-${ notice.type }` }>
					<p>{ notice.message }</p>
					<button
						type="button"
						className="notice-dismiss"
						onClick={ () => setNotice( null ) }
					>
						<span className="screen-reader-text">
							{ __( 'Dismiss', 'saman-seo' ) }
						</span>
					</button>
				</div>
			) }

			<div className="local-seo-layout">
				{ /* Main Content */ }
				<div className="local-seo-main">
					{ /* Tabs */ }
					<div className="tabs">
						<button
							className={ `tab ${
								activeTab === 'business' ? 'active' : ''
							}` }
							onClick={ () => setActiveTab( 'business' ) }
						>
							{ __( 'Business Information', 'saman-seo' ) }
						</button>
						<button
							className={ `tab ${
								activeTab === 'hours' ? 'active' : ''
							}` }
							onClick={ () => setActiveTab( 'hours' ) }
						>
							{ __( 'Opening Hours', 'saman-seo' ) }
						</button>
					</div>

					{ /* Business Information Tab */ }
					{ __( 'US', 'saman-seo' ) }

					{ /* Opening Hours Tab */ }
					{ activeTab === 'hours' && (
						<div className="tab-content">
							<div className="card">
								<div className="card-header">
									<h2>
										{ __( 'Opening Hours', 'saman-seo' ) }
									</h2>
									<p>
										{ __(
											'Set your business hours. These appear in search results and Google Knowledge Panel.',
											'saman-seo'
										) }
									</p>
								</div>
								<div className="card-body">
									{ /* Presets */ }
									<div className="hours-presets">
										<span className="presets-label">
											{ __(
												'Quick presets:',
												'saman-seo'
											) }
										</span>
										{ hourPresets.map( ( preset ) => (
											<button
												key={ preset.id }
												className={ `button ${
													activePreset === preset.id
														? 'primary'
														: 'ghost'
												} small` }
												onClick={ () =>
													applyPreset( preset )
												}
											>
												{ preset.label }
											</button>
										) ) }
									</div>

									{ /* Hours Grid */ }
									<div className="hours-grid">
										{ days.map( ( day ) => (
											<div
												key={ day.key }
												className={ `hours-row ${
													hours[ day.key ]?.enabled
														? 'is-open'
														: 'is-closed'
												}` }
											>
												<label className="hours-toggle">
													<input
														type="checkbox"
														checked={
															hours[ day.key ]
																?.enabled ||
															false
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
												<span className="hours-day">
													{ day.label }
												</span>
												{ hours[ day.key ]?.enabled ? (
													<>
														<input
															type="time"
															value={
																hours[ day.key ]
																	?.open ||
																'09:00'
															}
															onChange={ ( e ) =>
																handleHoursChange(
																	day.key,
																	'open',
																	e.target
																		.value
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
															value={
																hours[ day.key ]
																	?.close ||
																'17:00'
															}
															onChange={ ( e ) =>
																handleHoursChange(
																	day.key,
																	'close',
																	e.target
																		.value
																)
															}
															className="hours-time"
														/>
														{ __(
															'Copy to all weekdays',
															'saman-seo'
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
										) ) }
									</div>
								</div>
							</div>
						</div>
					) }
				</div>

				{ /* Sidebar */ }
				<div className="local-seo-sidebar">
					<KnowledgePanelPreview
						business={ business }
						hours={ hours }
						socialProfiles={ socialProfiles }
					/>
				</div>
			</div>
		</div>
	);
};
export default LocalSeo;
