import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, _n, sprintf } from '@wordpress/i18n';
import GscCard from '../components/GscCard';
import PageCacheCard from '../components/PageCacheCard';
import { KpiBand, MetricCard, Sparkline } from '../components/Kpi';
import { ICONS } from '../components/Sidebar';

// Score level configuration
const SCORE_LEVELS = {
	excellent: {
		label: __( 'Excellent', 'saman-seo' ),
		class: 'success',
	},
	good: {
		label: __( 'Good', 'saman-seo' ),
		class: 'success',
	},
	fair: {
		label: __( 'Fair', 'saman-seo' ),
		class: 'warning',
	},
	poor: {
		label: __( 'Needs work', 'saman-seo' ),
		class: 'danger',
	},
};

// Buckets of the per-post score distribution, best first.
const SCORE_BUCKETS = [
	{
		key: 'excellent',
		label: __( 'Excellent', 'saman-seo' ),
		range: '80+',
	},
	{
		key: 'good',
		label: __( 'Good', 'saman-seo' ),
		range: '60–79',
	},
	{
		key: 'fair',
		label: __( 'Fair', 'saman-seo' ),
		range: '40–59',
	},
	{
		key: 'poor',
		label: __( 'Poor', 'saman-seo' ),
		range: '<40',
	},
];

// Priority order for notification types
const PRIORITY_ORDER = {
	error: 1,
	warning: 2,
	info: 3,
};

// Map notification actions to internal views
const ACTION_VIEW_MAP = {
	redirects: 'redirects',
	404: '404-log',
	audit: 'audit',
	sitemap: 'sitemap',
	content: 'audit',
	seo: 'audit',
};

const MAX_VISIBLE_NOTIFICATIONS = 3;

const formatNumber = ( value ) =>
	new Intl.NumberFormat().format( Math.round( Number( value ) || 0 ) );

const Icon = ( { children, size = 16, strokeWidth = 2 } ) => (
	<svg
		width={ size }
		height={ size }
		viewBox="0 0 24 24"
		fill="none"
		stroke="currentColor"
		strokeWidth={ strokeWidth }
		strokeLinecap="round"
		strokeLinejoin="round"
		aria-hidden="true"
		focusable="false"
	>
		{ children }
	</svg>
);

const ArrowIcon = () => (
	<Icon size={ 14 }>
		<path d="M5 12h14M13 6l6 6-6 6" />
	</Icon>
);

const NOTIFICATION_ICONS = {
	error: (
		<Icon>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 8v4.5M12 16h.01" />
		</Icon>
	),
	warning: (
		<Icon>
			<path d="M12 9v4m0 4h.01M4.93 19h14.14c1.54 0 2.5-1.67 1.73-3L13.73 4c-.77-1.33-2.69-1.33-3.46 0L3.2 16c-.77 1.33.19 3 1.73 3z" />
		</Icon>
	),
	info: (
		<Icon>
			<circle cx="12" cy="12" r="9" />
			<path d="M12 16v-4.5M12 8h.01" />
		</Icon>
	),
};

/**
 * Placeholder layout shown while the dashboard request is in flight.
 *
 * @return {*} Skeleton blocks matching the loaded layout.
 */
const DashboardSkeleton = () => (
	<div
		className="dash-skeleton"
		role="status"
		aria-label={ __( 'Loading dashboard data…', 'saman-seo' ) }
	>
		<div className="dash-row dash-row--hero">
			<div className="dash-skeleton__block dash-skeleton__block--lg" />
			<div className="dash-skeleton__block dash-skeleton__block--lg" />
		</div>
		<div className="dash-skeleton__block dash-skeleton__block--sm" />
		<div className="dash-row dash-row--main">
			<div className="dash-skeleton__block dash-skeleton__block--md" />
			<div className="dash-skeleton__block dash-skeleton__block--md" />
		</div>
	</div>
);

/**
 * Issues the site owner should act on, most severe first.
 *
 * @param {Object}   props
 * @param {Array}    props.notifications Sorted notifications.
 * @param {?string}  props.dismissing    ID currently being dismissed.
 * @param {Function} props.onDismiss     Dismiss handler.
 * @param {Function} props.onNavigate    View navigation handler.
 * @return {*} Attention list or null when there is nothing to show.
 */
const AttentionList = ( {
	notifications,
	dismissing,
	onDismiss,
	onNavigate,
} ) => {
	const [ expanded, setExpanded ] = useState( false );
	if ( ! notifications.length ) {
		return null;
	}
	const visible = expanded
		? notifications
		: notifications.slice( 0, MAX_VISIBLE_NOTIFICATIONS );
	const hiddenCount = notifications.length - MAX_VISIBLE_NOTIFICATIONS;

	return (
		<section
			className="dashboard-card attention"
			aria-labelledby="saman-attention-title"
		>
			<div className="attention__head">
				<h2 id="saman-attention-title">
					{ __( 'Needs attention', 'saman-seo' ) }
				</h2>
				<span className="attention__count">
					{ notifications.length }
				</span>
			</div>
			<ul className="attention__list">
				{ visible.map( ( notif ) => (
					<li
						key={ notif.id }
						className={ `attention__item attention__item--${ notif.type }` }
					>
						<span className="attention__icon">
							{ NOTIFICATION_ICONS[ notif.type ] ||
								NOTIFICATION_ICONS.info }
						</span>
						<div className="attention__body">
							<strong>{ notif.title }</strong>
							<span>{ notif.message }</span>
						</div>
						{ notif.action && (
							<button
								type="button"
								className="button small"
								onClick={ () =>
									onNavigate(
										ACTION_VIEW_MAP[ notif.category ] ||
											'tools'
									)
								}
							>
								{ notif.action.label }
							</button>
						) }
						<button
							type="button"
							className="attention__dismiss"
							onClick={ () => onDismiss( notif.id ) }
							disabled={ dismissing === notif.id }
							aria-label={ sprintf(
								/* translators: %s: notification title */
								__( 'Dismiss: %s', 'saman-seo' ),
								notif.title
							) }
							title={ __( 'Dismiss', 'saman-seo' ) }
						>
							<Icon size={ 14 }>
								<path d="M18 6L6 18M6 6l12 12" />
							</Icon>
						</button>
					</li>
				) ) }
			</ul>
			{ hiddenCount > 0 && (
				<button
					type="button"
					className="attention__toggle"
					aria-expanded={ expanded }
					onClick={ () => setExpanded( ! expanded ) }
				>
					{ expanded
						? __( 'Show fewer', 'saman-seo' )
						: sprintf(
								/* translators: %s: number of hidden notifications */
								_n(
									'Show %s more',
									'Show %s more',
									hiddenCount,
									'saman-seo'
								),
								hiddenCount
						  ) }
				</button>
			) }
		</section>
	);
};

/**
 * Site-wide SEO score with the per-post distribution behind it.
 *
 * @param {Object}   props
 * @param {Object}   props.seoScore   Score payload from the dashboard endpoint.
 * @param {Function} props.onNavigate View navigation handler.
 * @return {*} Score card.
 */
const ScoreCard = ( { seoScore, onNavigate } ) => {
	const level = SCORE_LEVELS[ seoScore.level ] || SCORE_LEVELS.poor;
	const score = Math.max( 0, Math.min( 100, Number( seoScore.score ) || 0 ) );
	const distribution = seoScore.distribution || {};
	const scored = SCORE_BUCKETS.reduce(
		( sum, bucket ) => sum + ( Number( distribution[ bucket.key ] ) || 0 ),
		0
	);
	const trend = seoScore.trend;
	const hasTrend = trend && trend.direction !== 'stable' && trend.change > 0;

	return (
		<section className="dashboard-card score-card">
			<div className="card-header">
				<h3>{ __( 'SEO score', 'saman-seo' ) }</h3>
				<span className={ `pill ${ level.class }` }>
					{ level.label }
				</span>
			</div>
			<div className="score-card__body">
				<div
					className={ `score-gauge score-gauge--${ level.class }` }
					role="img"
					aria-label={ sprintf(
						/* translators: %s: score from 0 to 100 */
						__( 'SEO score: %s out of 100', 'saman-seo' ),
						score
					) }
				>
					<svg viewBox="0 0 120 120" aria-hidden="true">
						<circle
							className="score-gauge__track"
							cx="60"
							cy="60"
							r="52"
							pathLength="100"
						/>
						{ score > 0 && (
							<circle
								className="score-gauge__fill"
								cx="60"
								cy="60"
								r="52"
								pathLength="100"
								strokeDasharray={ `${ score } 100` }
							/>
						) }
					</svg>
					<div className="score-gauge__center">
						<span className="score-gauge__value">{ score }</span>
						<span className="score-gauge__label">
							{ __( 'out of 100', 'saman-seo' ) }
						</span>
					</div>
				</div>
				<div className="score-card__detail">
					<p className="score-card__summary">
						{ sprintf(
							/* translators: %s: number of posts */
							_n(
								'Average across your %s most recent post.',
								'Average across your %s most recent posts.',
								seoScore.posts_scored || 0,
								'saman-seo'
							),
							formatNumber( seoScore.posts_scored )
						) }
						{ hasTrend && (
							<span
								className={ `score-trend score-trend--${ trend.direction }` }
							>
								<Icon size={ 12 } strokeWidth={ 2.5 }>
									{ trend.direction === 'up' ? (
										<path d="M12 19V5M6 11l6-6 6 6" />
									) : (
										<path d="M12 5v14M6 13l6 6 6-6" />
									) }
								</Icon>
								{ sprintf(
									/* translators: %s: number of points */
									_n(
										'%s pt vs last week',
										'%s pts vs last week',
										trend.change,
										'saman-seo'
									),
									trend.change
								) }
							</span>
						) }
					</p>
					{ scored > 0 && (
						<div className="score-dist" aria-hidden="true">
							{ SCORE_BUCKETS.map( ( bucket ) => {
								const count =
									Number( distribution[ bucket.key ] ) || 0;
								if ( ! count ) {
									return null;
								}
								return (
									<span
										key={ bucket.key }
										className={ `score-dist__seg score-dist__seg--${ bucket.key }` }
										style={ { flexGrow: count } }
									/>
								);
							} ) }
						</div>
					) }
					<ul className="score-legend">
						{ SCORE_BUCKETS.map( ( bucket ) => (
							<li key={ bucket.key }>
								<span
									className={ `score-legend__dot score-dist__seg--${ bucket.key }` }
									aria-hidden="true"
								/>
								<span className="score-legend__label">
									{ bucket.label }
								</span>
								<span className="score-legend__range">
									{ bucket.range }
								</span>
								<span className="score-legend__value">
									{ formatNumber(
										distribution[ bucket.key ]
									) }
								</span>
							</li>
						) ) }
					</ul>
				</div>
			</div>
			{ seoScore.issues > 0 && (
				<div className="card-foot">
					<span>
						{ sprintf(
							/* translators: %s: number of posts */
							_n(
								'%s post scores below 80.',
								'%s posts score below 80.',
								seoScore.issues,
								'saman-seo'
							),
							formatNumber( seoScore.issues )
						) }
					</span>
					<button
						type="button"
						className="card-link"
						onClick={ () => onNavigate( 'audit' ) }
					>
						{ __( 'Review in audit', 'saman-seo' ) }
						<ArrowIcon />
					</button>
				</div>
			) }
		</section>
	);
};

/**
 * How much of the site has hand-written titles and descriptions.
 *
 * @param {Object}   props
 * @param {Object}   props.coverage   Coverage payload from the dashboard endpoint.
 * @param {Function} props.onNavigate View navigation handler.
 * @return {*} Coverage card.
 */
const CoverageCard = ( { coverage, onNavigate } ) => {
	const total = Number( coverage.total ) || 0;
	const pct = Number( coverage.coverage_pct ) || 0;
	const rows = [
		{
			key: 'title',
			label: __( 'SEO titles', 'saman-seo' ),
			value: Number( coverage.with_title ) || 0,
		},
		{
			key: 'description',
			label: __( 'Meta descriptions', 'saman-seo' ),
			value: Number( coverage.with_description ) || 0,
		},
		{
			key: 'optimized',
			label: __( 'Both set', 'saman-seo' ),
			value: Number( coverage.optimized ) || 0,
		},
	];
	const daily = coverage.daily_stats || [];
	const dailyMax = Math.max( ...daily.map( ( d ) => d.optimized || 0 ), 0 );
	let pillClass = 'danger';
	if ( pct >= 80 ) {
		pillClass = 'success';
	} else if ( pct >= 50 ) {
		pillClass = 'warning';
	}

	return (
		<section className="dashboard-card coverage-card">
			<div className="card-header">
				<h3>{ __( 'Content coverage', 'saman-seo' ) }</h3>
				<span className={ `pill ${ pillClass }` }>
					{ sprintf(
						/* translators: %s: percentage */
						__( '%s%% optimized', 'saman-seo' ),
						pct
					) }
				</span>
			</div>
			<p className="coverage-card__headline">
				<strong>{ formatNumber( coverage.optimized ) }</strong>
				<span>
					{ sprintf(
						/* translators: %s: total number of pages */
						_n(
							'of %s page has a custom title and description',
							'of %s pages have a custom title and description',
							total,
							'saman-seo'
						),
						formatNumber( total )
					) }
				</span>
			</p>
			<ul className="coverage-bars">
				{ rows.map( ( row ) => {
					const rowPct =
						total > 0
							? Math.min( 100, ( row.value / total ) * 100 )
							: 0;
					return (
						<li key={ row.key }>
							<div className="coverage-bars__meta">
								<span>{ row.label }</span>
								<span className="coverage-bars__value">
									{ formatNumber( row.value ) }
									<em> / { formatNumber( total ) }</em>
								</span>
							</div>
							<div
								className="coverage-bars__track"
								role="img"
								aria-label={ sprintf(
									/* translators: 1: metric label, 2: percentage */
									__( '%1$s: %2$s%%', 'saman-seo' ),
									row.label,
									Math.round( rowPct )
								) }
							>
								{ rowPct > 0 && (
									<span
										style={ {
											width: `${ Math.max(
												rowPct,
												1.5
											) }%`,
										} }
									/>
								) }
							</div>
						</li>
					);
				} ) }
			</ul>
			{ dailyMax > 0 && (
				<div className="coverage-daily">
					<span className="coverage-daily__title">
						{ __( 'Optimized per day', 'saman-seo' ) }
					</span>
					<div className="coverage-daily__bars">
						{ daily.map( ( day, idx ) => (
							<div
								key={ day.date || idx }
								className="coverage-daily__col"
								title={ sprintf(
									/* translators: 1: day label, 2: count */
									__( '%1$s: %2$s optimized', 'saman-seo' ),
									day.label,
									day.optimized || 0
								) }
							>
								<span
									className="coverage-daily__bar"
									style={ {
										height: `${ Math.max(
											( ( day.optimized || 0 ) /
												dailyMax ) *
												100,
											4
										) }%`,
									} }
								/>
								<span className="coverage-daily__label">
									{ day.label }
								</span>
							</div>
						) ) }
					</div>
				</div>
			) }
			{ coverage.pending > 0 && (
				<div className="card-foot">
					<span>
						{ sprintf(
							/* translators: %s: number of pages */
							_n(
								'%s page still uses defaults.',
								'%s pages still use defaults.',
								coverage.pending,
								'saman-seo'
							),
							formatNumber( coverage.pending )
						) }
					</span>
					<button
						type="button"
						className="card-link"
						onClick={ () => onNavigate( 'bulk-editor' ) }
					>
						{ __( 'Open bulk editor', 'saman-seo' ) }
						<ArrowIcon />
					</button>
				</div>
			) }
		</section>
	);
};

const Dashboard = ( { onNavigate } ) => {
	const [ loading, setLoading ] = useState( true );
	const [ data, setData ] = useState( null );
	const [ dismissing, setDismissing ] = useState( null );

	// Fetch dashboard data
	const fetchDashboard = useCallback( async () => {
		setLoading( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/dashboard',
			} );
			if ( res.success ) {
				setData( res.data );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch dashboard:', error );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchDashboard();
	}, [ fetchDashboard ] );

	// Dismiss notification
	const handleDismissNotification = async ( id ) => {
		setDismissing( id );
		try {
			await apiFetch( {
				path: `/saman-seo/v1/dashboard/notifications/${ id }/dismiss`,
				method: 'POST',
			} );
			setData( ( prev ) => ( {
				...prev,
				notifications: prev.notifications.filter(
					( n ) => n.id !== id
				),
			} ) );
		} catch ( error ) {
			console.error( 'Failed to dismiss notification:', error );
		} finally {
			setDismissing( null );
		}
	};

	// Handle navigation
	const handleNavigation = ( view ) => {
		if ( onNavigate ) {
			onNavigate( view );
		}
	};

	const header = (
		<div className="page-header">
			<div>
				<h1>{ __( 'Dashboard', 'saman-seo' ) }</h1>
				<p>
					{ __(
						'SEO health, content coverage, and search traffic for this site.',
						'saman-seo'
					) }
				</p>
			</div>
			<div className="header-actions">
				<button
					type="button"
					className="button"
					onClick={ () => handleNavigation( 'improvements' ) }
				>
					{ __( 'Improvements', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className="button primary"
					onClick={ () => handleNavigation( 'audit' ) }
				>
					{ __( 'Run SEO audit', 'saman-seo' ) }
				</button>
			</div>
		</div>
	);

	if ( loading ) {
		return (
			<div className="page dashboard">
				{ header }
				<DashboardSkeleton />
			</div>
		);
	}

	const seoScore = data?.seo_score || {
		score: 0,
		level: 'poor',
		issues: 0,
	};
	const coverage = data?.content_coverage || {
		total: 0,
		optimized: 0,
		pending: 0,
		daily_stats: [],
	};
	const sitemap = data?.sitemap || {
		enabled: false,
		total_urls: 0,
		last_generated: 'Never',
	};
	const redirects = data?.redirects || {
		active: 0,
		hits_today: 0,
		suggestions: 0,
	};
	const errors404 = data?.errors_404 || {
		total: 0,
		last_30_days: 0,
		last_7_days: 0,
	};
	const schema = data?.schema || {
		status: 'partial',
		types: [],
	};

	// Sort notifications by priority
	const notifications = [ ...( data?.notifications || [] ) ].sort(
		( a, b ) =>
			( PRIORITY_ORDER[ a.type ] || 99 ) -
			( PRIORITY_ORDER[ b.type ] || 99 )
	);

	const schemaTypes = schema.types || [];
	let sitemapState = 'off';
	if ( sitemap.enabled ) {
		sitemapState = sitemap.errors > 0 ? 'warning' : 'success';
	}
	const tiles = [
		{
			view: 'sitemap',
			label: __( 'Sitemap', 'saman-seo' ),
			state: sitemapState,
			value: sitemap.enabled
				? formatNumber( sitemap.total_urls )
				: __( 'Off', 'saman-seo' ),
			sub: sitemap.enabled
				? sprintf(
						/* translators: %s: relative time, e.g. "3 hours ago" */
						__( 'URLs · updated %s', 'saman-seo' ),
						sitemap.last_generated
				  )
				: __( 'Turn it on to help crawlers', 'saman-seo' ),
		},
		{
			view: 'redirects',
			series: redirects.daily_hits,
			label: __( 'Redirects', 'saman-seo' ),
			state: redirects.suggestions > 0 ? 'warning' : 'success',
			value: formatNumber( redirects.active ),
			sub:
				redirects.suggestions > 0
					? sprintf(
							/* translators: %s: number of suggestions */
							_n(
								'active · %s suggestion waiting',
								'active · %s suggestions waiting',
								redirects.suggestions,
								'saman-seo'
							),
							formatNumber( redirects.suggestions )
					  )
					: sprintf(
							/* translators: %s: number of hits */
							_n(
								'active · %s hit today',
								'active · %s hits today',
								redirects.hits_today || 0,
								'saman-seo'
							),
							formatNumber( redirects.hits_today )
					  ),
		},
		{
			view: '404-log',
			series: errors404.daily,
			label: __( '404 errors', 'saman-seo' ),
			state: errors404.last_7_days > 0 ? 'warning' : 'success',
			value: formatNumber( errors404.last_7_days ),
			sub: sprintf(
				/* translators: %s: number of errors */
				__( 'this week · %s in 30 days', 'saman-seo' ),
				formatNumber( errors404.last_30_days )
			),
		},
		{
			view: 'search-appearance',
			icon: 'schema-builder',
			label: __( 'Schema markup', 'saman-seo' ),
			state: schema.status === 'valid' ? 'success' : 'off',
			value: formatNumber( schemaTypes.length ),
			sub:
				schemaTypes.length > 0
					? [
							schemaTypes.slice( 0, 3 ).join( ', ' ),
							schemaTypes.length > 3
								? sprintf(
										/* translators: %s: number of additional schema types */
										__( '+%s more', 'saman-seo' ),
										schemaTypes.length - 3
								  )
								: '',
					  ]
							.filter( Boolean )
							.join( ' ' )
					: __( 'Basic markup only', 'saman-seo' ),
		},
	];

	return (
		<div className="page dashboard">
			{ header }

			<AttentionList
				notifications={ notifications }
				dismissing={ dismissing }
				onDismiss={ handleDismissNotification }
				onNavigate={ handleNavigation }
			/>

			<div className="dash-row dash-row--hero">
				<ScoreCard
					seoScore={ seoScore }
					onNavigate={ handleNavigation }
				/>
				<CoverageCard
					coverage={ coverage }
					onNavigate={ handleNavigation }
				/>
			</div>

			<KpiBand label={ __( 'Module status', 'saman-seo' ) }>
				{ tiles.map( ( tile ) => (
					<MetricCard
						key={ tile.view }
						icon={ ICONS[ tile.icon || tile.view ] }
						label={ tile.label }
						value={ tile.value }
						sub={ tile.sub }
						tone={ tile.state }
						spark={
							( tile.series || [] ).some( ( v ) => v > 0 ) && (
								<Sparkline
									data={ tile.series }
									tone={
										tile.state === 'warning'
											? 'warning'
											: undefined
									}
								/>
							)
						}
						onClick={ () => handleNavigation( tile.view ) }
					/>
				) ) }
			</KpiBand>

			<div className="dash-row dash-row--main">
				{ /* Google Search Console - real search traffic */ }
				<GscCard />
				{ /* Static page cache with before/after TTFB */ }
				<PageCacheCard />
			</div>
		</div>
	);
};
export default Dashboard;
