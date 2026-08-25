import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Google Search Console dashboard card.
 *
 * Handles the full lifecycle inline: credential setup, OAuth connect,
 * property selection, and the cached 28-day analytics summary.
 */
const GscCard = () => {
	const [ status, setStatus ] = useState( null );
	const [ analytics, setAnalytics ] = useState( null );
	const [ loadingStatus, setLoadingStatus ] = useState( true );
	const [ loadingAnalytics, setLoadingAnalytics ] = useState( false );
	const [ busy, setBusy ] = useState( false );
	const [ error, setError ] = useState( '' );
	const [ notice, setNotice ] = useState( '' );
	const [ clientId, setClientId ] = useState( '' );
	const [ clientSecret, setClientSecret ] = useState( '' );
	const [ showSetup, setShowSetup ] = useState( false );

	const fetchAnalytics = useCallback( async () => {
		setLoadingAnalytics( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/analytics?days=28',
			} );
			if ( res?.success ) {
				setAnalytics( res.data );
				if ( res.data?.status ) {
					setStatus( res.data.status );
				}
				setError( '' );
			}
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Could not load Search Console data.', 'saman-seo' )
			);
		} finally {
			setLoadingAnalytics( false );
		}
	}, [] );

	const fetchStatus = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/status',
			} );
			if ( res?.success ) {
				const next = res.data;
				setStatus( next );
				setClientId( '' );
				if ( ! next.connected && next.configured && next.error ) {
					setError( next.error );
				}
				if ( next.connected ) {
					fetchAnalytics();
				}
			}
		} catch {
			// Card stays in "not connected" state on failure.
		} finally {
			setLoadingStatus( false );
		}
	}, [ fetchAnalytics ] );

	useEffect( () => {
		fetchStatus();
	}, [ fetchStatus ] );

	const handleSaveCredentials = async ( event ) => {
		event.preventDefault();
		if ( ! clientId.trim() || ! clientSecret.trim() ) {
			setError(
				__( 'Both the client ID and secret are required.', 'saman-seo' )
			);
			return;
		}
		setBusy( true );
		setError( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/credentials',
				method: 'POST',
				data: {
					client_id: clientId.trim(),
					client_secret: clientSecret.trim(),
				},
			} );
			if ( res?.success ) {
				setNotice(
					__(
						'Credentials saved. Finish by connecting your Google account.',
						'saman-seo'
					)
				);
				await fetchStatus();
				startConnect();
			}
		} catch ( err ) {
			setError(
				err?.message || __( 'Saving credentials failed.', 'saman-seo' )
			);
		} finally {
			setBusy( false );
		}
	};

	const startConnect = async () => {
		setBusy( true );
		setError( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/auth-url',
			} );
			if ( res?.success && res.data?.auth_url ) {
				window.location.href = res.data.auth_url;
				return;
			}
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Could not start Google authorization.', 'saman-seo' )
			);
		}
		setBusy( false );
	};

	const handleDisconnect = async () => {
		setBusy( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/disconnect',
				method: 'POST',
			} );
			if ( res?.success ) {
				setStatus( res.data );
				setAnalytics( null );
				setShowSetup( false );
			}
		} catch ( err ) {
			setError( err?.message || __( 'Disconnect failed.', 'saman-seo' ) );
		} finally {
			setBusy( false );
		}
	};

	const handleSelectSite = async ( siteUrl ) => {
		setBusy( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/search-console/site',
				method: 'POST',
				data: { url: siteUrl },
			} );
			if ( res?.success ) {
				setStatus( res.data );
				setAnalytics( null );
				fetchAnalytics();
			}
		} catch ( err ) {
			setError(
				err?.message || __( 'Could not switch property.', 'saman-seo' )
			);
		} finally {
			setBusy( false );
		}
	};

	if ( loadingStatus ) {
		return (
			<div className="dashboard-card gsc-card">
				<div className="card-header">
					<h3>{ __( 'Search Performance', 'saman-seo' ) }</h3>
				</div>
				<div className="gsc-loading">
					{ __( 'Checking Search Console connection…', 'saman-seo' ) }
				</div>
			</div>
		);
	}

	const connected = status?.connected;
	const configured = status?.configured;

	return (
		<div className="dashboard-card gsc-card">
			<div className="card-header">
				<h3>{ __( 'Search Performance', 'saman-seo' ) }</h3>
				{ connected ? (
					<span className="pill success">
						{ __( 'Connected', 'saman-seo' ) }
					</span>
				) : (
					<span className="pill">
						{ __( 'Not connected', 'saman-seo' ) }
					</span>
				) }
			</div>

			{ error && (
				<div className="gsc-alert gsc-alert--error" role="alert">
					{ error }
					<button
						type="button"
						className="gsc-alert__close"
						onClick={ () => setError( '' ) }
						aria-label={ __( 'Dismiss', 'saman-seo' ) }
					>
						×
					</button>
				</div>
			) }
			{ notice && (
				<div className="gsc-alert gsc-alert--info">{ notice }</div>
			) }

			{ ! connected &&
				( configured && ! showSetup ? (
					<div className="gsc-connect">
						<p>
							{ __(
								'Your Google account is not linked yet. Connect it to show real clicks and impressions from Search Console.',
								'saman-seo'
							) }
						</p>
						<button
							type="button"
							className="button primary"
							disabled={ busy }
							onClick={ startConnect }
						>
							{ busy
								? __( 'Redirecting…', 'saman-seo' )
								: __( 'Connect Google Account', 'saman-seo' ) }
						</button>
						<p className="gsc-fineprint">
							{ status?.email && (
								<>
									{ sprintf(
										/* translators: %s: email address */
										__( 'Last used: %s', 'saman-seo' ),
										status.email
									) }{ ' ' }
									·{ ' ' }
								</>
							) }
							<button
								type="button"
								className="gsc-link-button"
								onClick={ () => setShowSetup( true ) }
							>
								{ __( 'Change API credentials', 'saman-seo' ) }
							</button>
						</p>
					</div>
				) : (
					<form
						className="gsc-setup"
						onSubmit={ handleSaveCredentials }
					>
						<p>
							{ __(
								'Show real clicks, impressions, and rankings from Google Search Console. Create a free OAuth client in Google Cloud, then paste both values here.',
								'saman-seo'
							) }
						</p>
						<details className="gsc-howto">
							<summary>
								{ __(
									'How do I get these credentials?',
									'saman-seo'
								) }
							</summary>
							<ol>
								<li>
									{ __(
										'Open console.cloud.google.com → APIs & Services → enable “Search Console API”.',
										'saman-seo'
									) }
								</li>
								<li>
									{ __(
										'Under “OAuth consent screen”, add yourself as a test user.',
										'saman-seo'
									) }
								</li>
								<li>
									{ __(
										'Under “Credentials”, create an OAuth client ID of type “Web application”.',
										'saman-seo'
									) }
								</li>
								<li>
									{ __(
										'Add this authorized redirect URI:',
										'saman-seo'
									) }
									<code className="gsc-redirect-uri">
										{ status?.redirect_uri }
									</code>
								</li>
							</ol>
						</details>
						<label
							className="gsc-field"
							htmlFor="saman-gsc-client-id"
						>
							<span>{ __( 'Client ID', 'saman-seo' ) }</span>
							<input
								id="saman-gsc-client-id"
								type="text"
								value={ clientId }
								onChange={ ( e ) =>
									setClientId( e.target.value )
								}
								placeholder="1234567890-abc.apps.googleusercontent.com"
								autoComplete="off"
								spellCheck="false"
							/>
						</label>
						<label
							className="gsc-field"
							htmlFor="saman-gsc-client-secret"
						>
							<span>{ __( 'Client secret', 'saman-seo' ) }</span>
							<input
								id="saman-gsc-client-secret"
								type="password"
								value={ clientSecret }
								onChange={ ( e ) =>
									setClientSecret( e.target.value )
								}
								autoComplete="new-password"
							/>
						</label>
						<div className="gsc-setup-actions">
							<button
								type="submit"
								className="button primary"
								disabled={ busy }
							>
								{ busy
									? __( 'Working…', 'saman-seo' )
									: __( 'Save & Connect', 'saman-seo' ) }
							</button>
							{ configured && (
								<button
									type="button"
									className="button"
									onClick={ () => setShowSetup( false ) }
								>
									{ __( 'Cancel', 'saman-seo' ) }
								</button>
							) }
						</div>
					</form>
				) ) }

			{ connected && (
				<div className="gsc-connected">
					{ loadingAnalytics || ! analytics ? (
						<div className="gsc-loading">
							{ __( 'Loading search data…', 'saman-seo' ) }
						</div>
					) : (
						<>
							<div className="gsc-metrics">
								<div className="gsc-metric">
									<span className="gsc-metric__value">
										{ formatNumber(
											analytics.totals?.clicks
										) }
									</span>
									<span className="gsc-metric__label">
										{ __( 'Clicks', 'saman-seo' ) }
									</span>
								</div>
								<div className="gsc-metric">
									<span className="gsc-metric__value">
										{ formatNumber(
											analytics.totals?.impressions
										) }
									</span>
									<span className="gsc-metric__label">
										{ __( 'Impressions', 'saman-seo' ) }
									</span>
								</div>
								<div className="gsc-metric">
									<span className="gsc-metric__value">
										{ analytics.totals?.ctr ?? 0 }%
									</span>
									<span className="gsc-metric__label">
										{ __( 'CTR', 'saman-seo' ) }
									</span>
								</div>
								<div className="gsc-metric">
									<span className="gsc-metric__value">
										#{ analytics.totals?.position ?? '-' }
									</span>
									<span className="gsc-metric__label">
										{ __( 'Avg. position', 'saman-seo' ) }
									</span>
								</div>
							</div>

							<Sparkline series={ analytics.series || {} } />

							{ ( analytics.queries || [] ).length > 0 && (
								<table className="gsc-queries">
									<thead>
										<tr>
											<th>
												{ __(
													'Top queries',
													'saman-seo'
												) }
											</th>
											<th className="num">
												{ __( 'Clicks', 'saman-seo' ) }
											</th>
											<th className="num">#</th>
										</tr>
									</thead>
									<tbody>
										{ analytics.queries
											.slice( 0, 5 )
											.map( ( row ) => (
												<tr key={ row.query }>
													<td className="gsc-query-text">
														{ row.query }
													</td>
													<td className="num">
														{ formatNumber(
															row.clicks
														) }
													</td>
													<td className="num">
														{ row.position }
													</td>
												</tr>
											) ) }
									</tbody>
								</table>
							) }

							<div className="gsc-footer">
								{ ( status?.sites || [] ).length > 0 && (
									<select
										className="gsc-site-select"
										value={ status?.site || '' }
										disabled={ busy }
										onChange={ ( e ) =>
											handleSelectSite( e.target.value )
										}
										aria-label={ __(
											'Search Console property',
											'saman-seo'
										) }
									>
										{ status.sites.map( ( site ) => (
											<option key={ site } value={ site }>
												{ prettySite( site ) }
											</option>
										) ) }
									</select>
								) }
								<button
									type="button"
									className="gsc-link-button"
									disabled={ busy }
									onClick={ handleDisconnect }
								>
									{ __( 'Disconnect', 'saman-seo' ) }
								</button>
							</div>
							<p className="gsc-fineprint">
								{ sprintf(
									/* translators: 1: start date, 2: end date */
									__(
										'Data for %1$s – %2$s (Google delays reporting by ~2 days).',
										'saman-seo'
									),
									analytics.range?.start_date,
									analytics.range?.end_date
								) }
							</p>
						</>
					) }
				</div>
			) }
		</div>
	);
};

const formatNumber = ( value ) =>
	new Intl.NumberFormat().format( Math.round( Number( value ) || 0 ) );

const prettySite = ( site ) =>
	site.startsWith( 'sc-domain:' )
		? site.replace( 'sc-domain:', '' ) + ' (domain)'
		: site.replace( /^https?:\/\//, '' ).replace( /\/$/, '' );

/**
 * Minimal CSS bar chart over the daily click series.
 *
 * @param {Object} root0        Component props.
 * @param {Object} root0.series Map of ISO date to daily metrics.
 * @return {*} Chart or null when there is no data yet.
 */
const Sparkline = ( { series } ) => {
	const entries = Object.entries( series );
	if ( ! entries.length ) {
		return null;
	}
	const max = Math.max( ...entries.map( ( [ , v ] ) => v.clicks ), 1 );

	return (
		<div
			className="gsc-sparkline"
			role="img"
			aria-label={ __( 'Daily clicks, last four weeks', 'saman-seo' ) }
		>
			{ entries.map( ( [ date, value ] ) => (
				<div
					key={ date }
					className="gsc-bar"
					style={ {
						height: `${ Math.max(
							( value.clicks / max ) * 100,
							4
						) }%`,
					} }
					title={ `${ date }: ${ value.clicks } ${ __(
						'clicks',
						'saman-seo'
					) }` }
				/>
			) ) }
		</div>
	);
};

export default GscCard;
