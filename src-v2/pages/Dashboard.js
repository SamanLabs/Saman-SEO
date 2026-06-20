import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Score level configuration
import { __, sprintf } from '@wordpress/i18n';
const SCORE_LEVELS = {
	excellent: {
		label: __( 'Excellent', 'saman-seo' ),
		color: 'var(--color-success)',
		class: 'success',
	},
	good: {
		label: __( 'Good', 'saman-seo' ),
		color: 'var(--color-success)',
		class: 'success',
	},
	fair: {
		label: __( 'Fair', 'saman-seo' ),
		color: 'var(--color-warning)',
		class: 'warning',
	},
	poor: {
		label: __( 'Needs Work', 'saman-seo' ),
		color: 'var(--color-danger)',
		class: 'danger',
	},
};

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
const COVERAGE_VIEWS = {
	breakdown: 'breakdown',
	timeline: 'timeline',
	checklist: 'checklist',
};
const Dashboard = ( { onNavigate } ) => {
	const [ loading, setLoading ] = useState( true );
	const [ data, setData ] = useState( null );
	const [ dismissing, setDismissing ] = useState( null );
	const [ showAllNotifications, setShowAllNotifications ] = useState( false );
	const [ coverageView, setCoverageView ] = useState( () => {
		if ( typeof window === 'undefined' ) {
			return COVERAGE_VIEWS.breakdown;
		}
		const saved = window.localStorage.getItem( 'saman_seo_coverage_view' );
		return Object.values( COVERAGE_VIEWS ).includes( saved )
			? saved
			: COVERAGE_VIEWS.breakdown;
	} );

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

	// Switch coverage card visualization and persist preference.
	const handleCoverageViewChange = ( view ) => {
		setCoverageView( view );
		if ( typeof window !== 'undefined' ) {
			window.localStorage.setItem( 'saman_seo_coverage_view', view );
		}
	};

	// Navigate to audit
	const handleRunAudit = () => {
		handleNavigation( 'audit' );
	};
	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<div>
						<h1>{ __( 'Dashboard', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'SEO health, content insights, and optimization status at a glance.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading dashboard data\u2026', 'saman-seo' ) }
				</div>
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
	};
	const schema = data?.schema || {
		status: 'partial',
		types: [],
	};
	const allNotifications = data?.notifications || [];

	// Sort notifications by priority
	const sortedNotifications = [ ...allNotifications ].sort( ( a, b ) => {
		const priorityA = PRIORITY_ORDER[ a.type ] || 99;
		const priorityB = PRIORITY_ORDER[ b.type ] || 99;
		return priorityA - priorityB;
	} );

	// Show max 3 notifications on dashboard, or all if expanded
	const visibleNotifications = showAllNotifications
		? sortedNotifications
		: sortedNotifications.slice( 0, 3 );
	const hasMoreNotifications = sortedNotifications.length > 3;
	const scoreConfig = SCORE_LEVELS[ seoScore.level ] || SCORE_LEVELS.poor;
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Dashboard', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'SEO health, content insights, and optimization status at a glance.',
							'saman-seo'
						) }
					</p>
				</div>
				<button
					type="button"
					className="button primary"
					onClick={ handleRunAudit }
				>
					{ __( 'Run SEO Audit', 'saman-seo' ) }
				</button>
			</div>

			{ /* Notifications */ }
			{ visibleNotifications.length > 0 && (
				<div className="dashboard-notifications">
					{ __( 'Dismiss', 'saman-seo' ) }
					{ hasMoreNotifications && (
						<button
							type="button"
							className="notifications-toggle"
							onClick={ () =>
								setShowAllNotifications(
									! showAllNotifications
								)
							}
						>
							{ showAllNotifications
								? __( 'Show less', 'saman-seo' )
								: sprintf(
										/* translators: %s: placeholder */ __(
											'View all %s notifications',
											'saman-seo'
										),
										sortedNotifications.length
								  ) }
						</button>
					) }
				</div>
			) }

			{ /* Main Stats Grid */ }
			<div className="dashboard-grid">
				{ /* SEO Score Card - Large */ }
				<div className="dashboard-card seo-score-card">
					<div className="card-header">
						<h3>{ __( 'SEO Score', 'saman-seo' ) }</h3>
						<span className={ `pill ${ scoreConfig.class }` }>
							{ scoreConfig.label }
						</span>
					</div>
					<div className="seo-score-content">
						<div className="score-gauge">
							<svg viewBox="0 0 120 120" className="gauge-svg">
								<circle
									className="gauge-bg"
									cx="60"
									cy="60"
									r="50"
									fill="none"
									strokeWidth="10"
								/>
								<circle
									className="gauge-fill"
									cx="60"
									cy="60"
									r="50"
									fill="none"
									strokeWidth="10"
									strokeDasharray={ `${
										( seoScore.score / 100 ) * 314
									} 314` }
									strokeLinecap="round"
									style={ {
										stroke: scoreConfig.color,
									} }
								/>
							</svg>
							<div className="gauge-center">
								<div className="gauge-value">
									{ seoScore.score }%
								</div>
								<div className="gauge-label">
									{ seoScore.posts_scored || 0 }{ ' ' }
									{ __( 'posts', 'saman-seo' ) }
								</div>
							</div>
						</div>
						<div className="score-breakdown">
							<div className="breakdown-item">
								<span className="breakdown-dot excellent"></span>
								<span className="breakdown-label">
									{ __( 'Excellent (80+)', 'saman-seo' ) }
								</span>
								<span className="breakdown-value">
									{ seoScore.distribution?.excellent || 0 }
								</span>
							</div>
							<div className="breakdown-item">
								<span className="breakdown-dot good"></span>
								<span className="breakdown-label">
									{ __( 'Good (60–79)', 'saman-seo' ) }
								</span>
								<span className="breakdown-value">
									{ seoScore.distribution?.good || 0 }
								</span>
							</div>
							<div className="breakdown-item">
								<span className="breakdown-dot fair"></span>
								<span className="breakdown-label">
									{ __( 'Fair (40–59)', 'saman-seo' ) }
								</span>
								<span className="breakdown-value">
									{ seoScore.distribution?.fair || 0 }
								</span>
							</div>
							<div className="breakdown-item">
								<span className="breakdown-dot poor"></span>
								<span className="breakdown-label">
									{ __( 'Poor (<40)', 'saman-seo' ) }
								</span>
								<span className="breakdown-value">
									{ seoScore.distribution?.poor || 0 }
								</span>
							</div>
						</div>
					</div>
					{ seoScore.issues > 0 && (
						<p className="card-note">
							{ seoScore.issues } { __( 'post', 'saman-seo' ) }
							{ seoScore.issues !== 1 ? 's' : '' }{ ' ' }
							{ __( 'could use optimization.', 'saman-seo' ) }
						</p>
					) }
				</div>

				{ /* Content Coverage Card - Large */ }
				<div className="dashboard-card content-coverage-card">
					<div className="card-header">
						<h3>{ __( 'Content Coverage', 'saman-seo' ) }</h3>
						<div className="coverage-view-toggle">
							<button
								type="button"
								className={ `coverage-view-btn ${
									coverageView === COVERAGE_VIEWS.breakdown
										? 'is-active'
										: ''
								}` }
								onClick={ () =>
									handleCoverageViewChange(
										COVERAGE_VIEWS.breakdown
									)
								}
								aria-label={ __(
									'Breakdown view',
									'saman-seo'
								) }
								title={ __( 'Breakdown', 'saman-seo' ) }
							>
								<svg
									width="16"
									height="16"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								>
									<circle cx="12" cy="12" r="10" />
									<path d="M12 2a10 10 0 0 1 10 10H12V2z" />
								</svg>
							</button>
							<button
								type="button"
								className={ `coverage-view-btn ${
									coverageView === COVERAGE_VIEWS.timeline
										? 'is-active'
										: ''
								}` }
								onClick={ () =>
									handleCoverageViewChange(
										COVERAGE_VIEWS.timeline
									)
								}
								aria-label={ __(
									'Timeline view',
									'saman-seo'
								) }
								title={ __( 'Timeline', 'saman-seo' ) }
							>
								<svg
									width="16"
									height="16"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								>
									<line x1="18" y1="20" x2="18" y2="10" />
									<line x1="12" y1="20" x2="12" y2="4" />
									<line x1="6" y1="20" x2="6" y2="14" />
								</svg>
							</button>
							<button
								type="button"
								className={ `coverage-view-btn ${
									coverageView === COVERAGE_VIEWS.checklist
										? 'is-active'
										: ''
								}` }
								onClick={ () =>
									handleCoverageViewChange(
										COVERAGE_VIEWS.checklist
									)
								}
								aria-label={ __(
									'Checklist view',
									'saman-seo'
								) }
								title={ __( 'Checklist', 'saman-seo' ) }
							>
								<svg
									width="16"
									height="16"
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								>
									<path d="M9 11l3 3L22 4" />
									<path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
								</svg>
							</button>
							<span className="pill">
								{ coverage.coverage_pct || 0 }
								{ __( '% Optimized', 'saman-seo' ) }
							</span>
						</div>
					</div>
					<div className="coverage-content">
						{ coverageView === COVERAGE_VIEWS.timeline && (
							<div className="coverage-chart">
								<div className="spark-bars" aria-hidden="true">
									{ ( coverage.daily_stats || [] ).map(
										( day, idx ) => {
											const maxOptimized = Math.max(
												...coverage.daily_stats.map(
													( d ) => d.optimized || 0
												),
												1
											);
											const height = Math.max(
												15,
												( ( day.optimized || 0 ) /
													maxOptimized ) *
													100
											);
											return (
												<div
													key={ idx }
													className="spark-bar-wrapper"
													title={ sprintf(
														/* translators: %1$s: placeholder, %2$s: placeholder */ __(
															'%1$s: %2$s optimized',
															'saman-seo'
														),
														day.label,
														day.optimized
													) }
												>
													<span
														style={ {
															height: `${ height }%`,
														} }
													/>
													<span className="spark-label">
														{ day.label }
													</span>
												</div>
											);
										}
									) }
								</div>
							</div>
						) }
						{ coverageView === COVERAGE_VIEWS.breakdown && (
							<div className="coverage-chart coverage-chart--breakdown">
								<div
									className="coverage-donut"
									aria-hidden="true"
								>
									<svg
										viewBox="0 0 120 120"
										className="coverage-donut__svg"
									>
										<circle
											className="coverage-donut__bg"
											cx="60"
											cy="60"
											r="50"
										/>
										<circle
											className="coverage-donut__fill"
											cx="60"
											cy="60"
											r="50"
											strokeDasharray={ `${
												( coverage.coverage_pct /
													100 ) *
												314
											} 314` }
											style={ {
												stroke:
													coverage.coverage_pct >= 80
														? 'var(--color-success)'
														: coverage.coverage_pct >=
														  50
														? 'var(--color-warning)'
														: 'var(--color-danger)',
											} }
										/>
									</svg>
									<div className="coverage-donut__center">
										<span className="coverage-donut__value">
											{ coverage.coverage_pct || 0 }%
										</span>
										<span className="coverage-donut__label">
											{ __( 'Optimized', 'saman-seo' ) }
										</span>
									</div>
								</div>
								<div className="coverage-legend">
									<div className="coverage-legend__item">
										<span className="coverage-legend__dot coverage-legend__dot--optimized" />
										<span className="coverage-legend__label">
											{ __( 'Optimized', 'saman-seo' ) }
										</span>
										<span className="coverage-legend__value">
											{ coverage.optimized || 0 }
										</span>
									</div>
									<div className="coverage-legend__item">
										<span className="coverage-legend__dot coverage-legend__dot--pending" />
										<span className="coverage-legend__label">
											{ __( 'Pending', 'saman-seo' ) }
										</span>
										<span className="coverage-legend__value">
											{ coverage.pending || 0 }
										</span>
									</div>
								</div>
							</div>
						) }
						{ coverageView === COVERAGE_VIEWS.checklist && (
							<div className="coverage-chart coverage-chart--checklist">
								<div className="coverage-checklist">
									<div
										className={ `coverage-checklist__item ${
											coverage.with_title >=
												coverage.total &&
											coverage.total > 0
												? 'is-done'
												: ''
										}` }
									>
										<span className="coverage-checklist__icon">
											<svg
												width="16"
												height="16"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="3"
												strokeLinecap="round"
												strokeLinejoin="round"
											>
												<path d="M20 6L9 17l-5-5" />
											</svg>
										</span>
										<span className="coverage-checklist__text">
											{ coverage.with_title || 0 }{ ' ' }
											{ __( 'of', 'saman-seo' ) }{ ' ' }
											{ coverage.total || 0 }{ ' ' }
											{ __(
												'pages have SEO titles',
												'saman-seo'
											) }
										</span>
									</div>
									<div
										className={ `coverage-checklist__item ${
											coverage.with_description >=
												coverage.total &&
											coverage.total > 0
												? 'is-done'
												: ''
										}` }
									>
										<span className="coverage-checklist__icon">
											<svg
												width="16"
												height="16"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="3"
												strokeLinecap="round"
												strokeLinejoin="round"
											>
												<path d="M20 6L9 17l-5-5" />
											</svg>
										</span>
										<span className="coverage-checklist__text">
											{ coverage.with_description || 0 }{ ' ' }
											{ __( 'of', 'saman-seo' ) }{ ' ' }
											{ coverage.total || 0 }{ ' ' }
											{ __(
												'pages have meta descriptions',
												'saman-seo'
											) }
										</span>
									</div>
									<div
										className={ `coverage-checklist__item ${
											coverage.optimized >=
												coverage.total &&
											coverage.total > 0
												? 'is-done'
												: ''
										}` }
									>
										<span className="coverage-checklist__icon">
											<svg
												width="16"
												height="16"
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="3"
												strokeLinecap="round"
												strokeLinejoin="round"
											>
												<path d="M20 6L9 17l-5-5" />
											</svg>
										</span>
										<span className="coverage-checklist__text">
											{ coverage.optimized || 0 }{ ' ' }
											{ __( 'of', 'saman-seo' ) }{ ' ' }
											{ coverage.total || 0 }{ ' ' }
											{ __(
												'pages are fully optimized',
												'saman-seo'
											) }
										</span>
									</div>
								</div>
							</div>
						) }
						<div className="coverage-stats">
							<div className="coverage-stat">
								<div className="coverage-stat-value">
									{ coverage.optimized || 0 }
								</div>
								<div className="coverage-stat-label">
									{ __( 'Optimized', 'saman-seo' ) }
								</div>
							</div>
							<div className="coverage-stat pending">
								<div className="coverage-stat-value">
									{ coverage.pending || 0 }
								</div>
								<div className="coverage-stat-label">
									{ __( 'Pending', 'saman-seo' ) }
								</div>
							</div>
						</div>
					</div>
					<p className="card-note">
						{ coverage.total || 0 }{ ' ' }
						{ __( 'total pages \xB7', 'saman-seo' ) }{ ' ' }
						{ coverage.with_title || 0 }{ ' ' }
						{ __( 'with titles \xB7', 'saman-seo' ) }{ ' ' }
						{ coverage.with_description || 0 }{ ' ' }
						{ __( 'with descriptions', 'saman-seo' ) }
					</p>
				</div>
			</div>

			{ /* Secondary Stats Grid */ }
			<div
				className="card-grid"
				style={ {
					marginTop: 'var(--space-lg)',
				} }
			>
				{ /* Sitemap Status */ }
				<div className="card">
					<div className="card-header">
						<h3>{ __( 'Sitemap Status', 'saman-seo' ) }</h3>
						<span
							className={ `pill ${
								sitemap.enabled ? 'success' : 'warning'
							}` }
						>
							{ sitemap.status_label ||
								( sitemap.enabled
									? __( 'Active', 'saman-seo' )
									: __( 'Disabled', 'saman-seo' ) ) }
						</span>
					</div>
					<div className="status-row">
						<span
							className={ `status-dot ${
								sitemap.enabled ? 'success' : 'warning'
							}` }
							aria-hidden="true"
						/>
						<div>
							<div className="status-title">
								{ sitemap.enabled
									? sprintf(
											/* translators: %s: placeholder */ __(
												'%s URLs indexed',
												'saman-seo'
											),
											sitemap.total_urls
									  )
									: __( 'Sitemap disabled', 'saman-seo' ) }
							</div>
							<div className="status-subtitle">
								{ sitemap.enabled
									? sprintf(
											/* translators: %s: placeholder */ __(
												'Last generated: %s',
												'saman-seo'
											),
											sitemap.last_generated
									  )
									: __(
											'Enable to help search engines',
											'saman-seo'
									  ) }
							</div>
						</div>
					</div>
					<button
						type="button"
						className="button ghost small"
						onClick={ () => handleNavigation( 'sitemap' ) }
					>
						{ sitemap.enabled
							? __( 'View Sitemap', 'saman-seo' )
							: __( 'Enable Sitemap', 'saman-seo' ) }
					</button>
				</div>

				{ /* Redirects */ }
				<div className="card">
					<div className="card-header">
						<h3>{ __( 'Redirects', 'saman-seo' ) }</h3>
						<span
							className={ `pill ${
								redirects.active > 0 ? 'success' : ''
							}` }
						>
							{ redirects.active } { __( 'Active', 'saman-seo' ) }
						</span>
					</div>
					<div className="status-row">
						<span
							className={ `status-dot ${
								redirects.suggestions > 0
									? 'warning'
									: 'success'
							}` }
							aria-hidden="true"
						/>
						<div>
							<div className="status-title">
								{ redirects.suggestions > 0
									? sprintf(
											/* translators: %1$s: placeholder, %2$s: placeholder */ __(
												'%1$s pending suggestion%2$s',
												'saman-seo'
											),
											redirects.suggestions,
											redirects.suggestions !== 1
												? 's'
												: ''
									  )
									: __(
											'All redirects working',
											'saman-seo'
									  ) }
							</div>
							<div className="status-subtitle">
								{ redirects.hits_today || 0 }{ ' ' }
								{ __( 'hits today', 'saman-seo' ) }
							</div>
						</div>
					</div>
					<button
						type="button"
						className="button ghost small"
						onClick={ () => handleNavigation( 'redirects' ) }
					>
						{ __( 'Manage Redirects', 'saman-seo' ) }
					</button>
				</div>

				{ /* 404 Errors */ }
				<div className="card">
					<div className="card-header">
						<h3>{ __( '404 Errors', 'saman-seo' ) }</h3>
						<span
							className={ `pill ${
								errors404.last_30_days > 0
									? 'warning'
									: 'success'
							}` }
						>
							{ errors404.last_30_days > 0
								? sprintf(
										/* translators: %s: placeholder */ __(
											'%s Found',
											'saman-seo'
										),
										errors404.last_30_days
								  )
								: __( 'None', 'saman-seo' ) }
						</span>
					</div>
					<div className="status-row">
						<span
							className={ `status-dot ${
								errors404.last_30_days > 0
									? 'warning'
									: 'success'
							}` }
							aria-hidden="true"
						/>
						<div>
							<div className="status-title">
								{ errors404.last_7_days > 0
									? sprintf(
											/* translators: %s: placeholder */ __(
												'%s errors this week',
												'saman-seo'
											),
											errors404.last_7_days
									  )
									: __( 'No recent errors', 'saman-seo' ) }
							</div>
							<div className="status-subtitle">
								{ __( 'Last 30 days', 'saman-seo' ) }
							</div>
						</div>
					</div>
					<button
						type="button"
						className="button ghost small"
						onClick={ () => handleNavigation( '404-log' ) }
					>
						{ __( 'View 404 Log', 'saman-seo' ) }
					</button>
				</div>

				{ /* Schema Status */ }
				<div className="card">
					<div className="card-header">
						<h3>{ __( 'Schema Markup', 'saman-seo' ) }</h3>
						<span
							className={ `pill ${
								schema.status === 'valid' ? 'success' : ''
							}` }
						>
							{ schema.status_label || schema.status }
						</span>
					</div>
					<div className="status-row">
						<span
							className={ `status-dot ${
								schema.status === 'valid' ? 'success' : ''
							}` }
							aria-hidden="true"
						/>
						<div>
							<div className="status-title">
								{ schema.types?.length > 0
									? schema.types.slice( 0, 3 ).join( ', ' )
									: __( 'Basic markup', 'saman-seo' ) }
							</div>
							<div className="status-subtitle">
								{ schema.types?.length > 3
									? sprintf(
											/* translators: %s: placeholder */ __(
												'+%s more types',
												'saman-seo'
											),
											schema.types.length - 3
									  )
									: __( 'Schema types active', 'saman-seo' ) }
							</div>
						</div>
					</div>
					<button
						type="button"
						className="button ghost small"
						onClick={ () =>
							handleNavigation( 'search-appearance' )
						}
					>
						{ __( 'Configure Schema', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ /* Quick Actions */ }
			<div className="dashboard-actions">
				<h3>{ __( 'Quick Actions', 'saman-seo' ) }</h3>
				<div className="actions-grid">
					<button
						type="button"
						className="action-card"
						onClick={ () => handleNavigation( 'audit' ) }
					>
						<div className="action-icon">
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</div>
						<div className="action-content">
							<strong>
								{ __( 'Run SEO Audit', 'saman-seo' ) }
							</strong>
							<span>
								{ __( 'Scan for issues', 'saman-seo' ) }
							</span>
						</div>
					</button>
					<button
						type="button"
						className="action-card"
						onClick={ () => handleNavigation( 'redirects' ) }
					>
						<div className="action-icon">
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M13 10V3L4 14h7v7l9-11h-7z"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
									strokeLinejoin="round"
								/>
							</svg>
						</div>
						<div className="action-content">
							<strong>
								{ __( 'Manage Redirects', 'saman-seo' ) }
							</strong>
							<span>
								{ redirects.suggestions > 0
									? sprintf(
											/* translators: %s: placeholder */ __(
												'%s suggestions',
												'saman-seo'
											),
											redirects.suggestions
									  )
									: __( 'All good', 'saman-seo' ) }
							</span>
						</div>
					</button>
					<button
						type="button"
						className="action-card"
						onClick={ () => handleNavigation( 'sitemap' ) }
					>
						<div className="action-icon">
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M4 6h16M4 12h16M4 18h10"
									stroke="currentColor"
									strokeWidth="2"
									strokeLinecap="round"
								/>
							</svg>
						</div>
						<div className="action-content">
							<strong>
								{ __( 'View Sitemap', 'saman-seo' ) }
							</strong>
							<span>
								{ sitemap.total_urls }{ ' ' }
								{ __( 'URLs', 'saman-seo' ) }
							</span>
						</div>
					</button>
					<button
						type="button"
						className="action-card"
						onClick={ () => handleNavigation( 'ai-assistant' ) }
					>
						<div className="action-icon">
							<svg
								width="24"
								height="24"
								viewBox="0 0 24 24"
								fill="none"
							>
								<path
									d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z"
									stroke="currentColor"
									strokeWidth="2"
								/>
								<circle
									cx="9"
									cy="13"
									r="1"
									fill="currentColor"
								/>
								<circle
									cx="15"
									cy="13"
									r="1"
									fill="currentColor"
								/>
							</svg>
						</div>
						<div className="action-content">
							<strong>
								{ __( 'AI Assistant', 'saman-seo' ) }
							</strong>
							<span>
								{ __( 'Generate content', 'saman-seo' ) }
							</span>
						</div>
					</button>
				</div>
			</div>
		</div>
	);
};
export default Dashboard;
