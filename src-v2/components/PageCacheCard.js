import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Static page cache card with measured before/after TTFB.
 *
 * Refuses to enable while other cache plugins are active and shows real
 * hit-rate plus cold vs warm server timings once traffic has flowed.
 */
const PageCacheCard = () => {
	const [ status, setStatus ] = useState( null );
	const [ loading, setLoading ] = useState( true );
	const [ busy, setBusy ] = useState( false );
	const [ message, setMessage ] = useState( '' );
	const [ error, setError ] = useState( '' );
	const [ showSettings, setShowSettings ] = useState( false );
	const [ ttl, setTtl ] = useState( 24 );
	const [ purgeOnSave, setPurgeOnSave ] = useState( true );
	const [ exclusions, setExclusions ] = useState( '' );

	const applyStatus = useCallback( ( data ) => {
		setStatus( data );
		setTtl( data.settings?.ttl || 24 );
		setPurgeOnSave( !! data.settings?.purge_on_save );
		setExclusions( data.settings?.excluded_urls || '' );
	}, [] );

	const fetchStatus = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/page-cache/status',
			} );
			if ( res?.success ) {
				applyStatus( res.data );
			}
		} catch {
			// Leave the card in loading-failed state.
		} finally {
			setLoading( false );
		}
	}, [ applyStatus ] );

	useEffect( () => {
		fetchStatus();
	}, [ fetchStatus ] );

	const handleToggle = async ( enabled ) => {
		setBusy( true );
		setError( '' );
		setMessage( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/page-cache/toggle',
				method: 'POST',
				data: { enabled },
			} );
			if ( res?.success ) {
				applyStatus( res.data );
				if ( enabled ) {
					setMessage(
						__(
							'Page cache is on. Pages snapshot as visitors hit them.',
							'saman-seo'
						)
					);
				}
			}
		} catch ( err ) {
			setError(
				err?.message ||
					__( 'Could not change cache state.', 'saman-seo' )
			);
		} finally {
			setBusy( false );
		}
	};

	const handlePurge = async () => {
		setBusy( true );
		setMessage( '' );
		setError( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/page-cache/purge',
				method: 'POST',
			} );
			if ( res?.success ) {
				applyStatus( res.data );
				setMessage( res.message );
			}
		} catch ( err ) {
			setError(
				err?.message || __( 'Could not clear the cache.', 'saman-seo' )
			);
		} finally {
			setBusy( false );
		}
	};

	const handleSaveSettings = async () => {
		setBusy( true );
		setError( '' );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/page-cache/settings',
				method: 'POST',
				data: {
					ttl,
					purge_on_save: purgeOnSave,
					excluded_urls: exclusions,
				},
			} );
			if ( res?.success ) {
				applyStatus( res.data );
				setShowSettings( false );
				setMessage( __( 'Cache settings saved.', 'saman-seo' ) );
			}
		} catch ( err ) {
			setError(
				err?.message || __( 'Saving settings failed.', 'saman-seo' )
			);
		} finally {
			setBusy( false );
		}
	};

	if ( loading ) {
		return (
			<div className="dashboard-card gsc-card">
				<div className="card-header">
					<h3>{ __( 'Site Speed', 'saman-seo' ) }</h3>
				</div>
				<div className="gsc-loading">
					{ __( 'Checking page cache…', 'saman-seo' ) }
				</div>
			</div>
		);
	}

	const stats = status?.stats || {};
	const conflicts = status?.conflicts || {};
	const conflictList = Object.values( conflicts );
	const hasConflicts = conflictList.length > 0;
	const enabled = !! status?.enabled;

	return (
		<div className="dashboard-card gsc-card">
			<div className="card-header">
				<h3>{ __( 'Site Speed', 'saman-seo' ) }</h3>
				{ enabled ? (
					<span className="pill success">
						{ status.tier === 'dropin'
							? __( 'Fast mode', 'saman-seo' )
							: __( 'On', 'saman-seo' ) }
					</span>
				) : (
					<span className="pill">{ __( 'Off', 'saman-seo' ) }</span>
				) }
			</div>

			{ error && (
				<div className="gsc-alert gsc-alert--error" role="alert">
					{ error }
				</div>
			) }
			{ message && (
				<div className="gsc-alert gsc-alert--info">{ message }</div>
			) }

			{ ! enabled && (
				<div className="gsc-connect">
					<p>
						{ __(
							'Snapshot your public pages as static HTML and serve them instantly. WordPress rebuilds each snapshot automatically when you edit content.',
							'saman-seo'
						) }
					</p>
					{ hasConflicts ? (
						<div className="gsc-alert gsc-alert--error">
							{ sprintf(
								/* translators: %s: plugin names */
								__(
									'These caching plugins are active: %s. Saman SEO will not stack a second cache — disable them first.',
									'saman-seo'
								),
								conflictList.join( ', ' )
							) }
						</div>
					) : (
						<button
							type="button"
							className="button primary"
							disabled={ busy }
							onClick={ () => handleToggle( true ) }
						>
							{ busy
								? __( 'Working…', 'saman-seo' )
								: __( 'Enable Page Cache', 'saman-seo' ) }
						</button>
					) }
				</div>
			) }

			{ enabled && (
				<>
					<div className="gsc-metrics">
						<div className="gsc-metric">
							<span className="gsc-metric__value">
								{ stats.hit_rate ?? 0 }%
							</span>
							<span className="gsc-metric__label">
								{ __( 'Hit rate', 'saman-seo' ) }
							</span>
						</div>
						<div className="gsc-metric">
							<span className="gsc-metric__value">
								{ formatMs( stats.ttfb_cold_ms ) }
							</span>
							<span className="gsc-metric__label">
								{ __( 'Before (ms)', 'saman-seo' ) }
							</span>
						</div>
						<div className="gsc-metric">
							<span
								className="gsc-metric__value"
								style={ {
									color:
										( stats.ttfb_warm_ms || 0 ) <
										( stats.ttfb_cold_ms || 1 )
											? 'var(--color-success)'
											: undefined,
								} }
							>
								{ formatMs( stats.ttfb_warm_ms ) }
							</span>
							<span className="gsc-metric__label">
								{ __( 'After (ms)', 'saman-seo' ) }
							</span>
						</div>
						<div className="gsc-metric">
							<span className="gsc-metric__value">
								{ formatNumber( stats.pages_cached ) }
							</span>
							<span className="gsc-metric__label">
								{ __( 'Pages cached', 'saman-seo' ) }
							</span>
						</div>
					</div>

					{ stats.ttfb_cold_ms > 0 &&
						stats.ttfb_warm_ms > 0 &&
						stats.ttfb_cold_ms > stats.ttfb_warm_ms && (
							<p className="gsc-speed-win">
								{ sprintf(
									/* translators: 1: speed multiplier, 2: before ms, 3: after ms */
									__(
										'Cached pages load ~%1$d× faster (%2$dms → %3$dms).',
										'saman-seo'
									),
									Math.max(
										1,
										Math.round(
											stats.ttfb_cold_ms /
												Math.max(
													stats.ttfb_warm_ms,
													1
												)
										)
									),
									stats.ttfb_cold_ms,
									stats.ttfb_warm_ms
								) }
							</p>
						) }

					<div className="gsc-footer">
						<div>
							<button
								type="button"
								className="button"
								disabled={ busy }
								onClick={ handlePurge }
							>
								{ __( 'Clear Cache', 'saman-seo' ) }
							</button>{ ' ' }
							<button
								type="button"
								className="button"
								onClick={ () =>
									setShowSettings( ! showSettings )
								}
							>
								{ __( 'Settings', 'saman-seo' ) }
							</button>
						</div>
						<button
							type="button"
							className="gsc-link-button"
							disabled={ busy }
							onClick={ () => handleToggle( false ) }
						>
							{ __( 'Disable', 'saman-seo' ) }
						</button>
					</div>

					{ showSettings && (
						<div className="gsc-cache-settings">
							<label className="gsc-field" htmlFor="saman-pc-ttl">
								<span>
									{ __(
										'Snapshot lifetime (hours)',
										'saman-seo'
									) }
								</span>
								<input
									id="saman-pc-ttl"
									type="number"
									min="1"
									max="168"
									value={ ttl }
									onChange={ ( e ) =>
										setTtl( e.target.value )
									}
								/>
							</label>
							<label
								className="gsc-digest-toggle"
								htmlFor="saman-pc-purge"
							>
								<input
									id="saman-pc-purge"
									type="checkbox"
									checked={ purgeOnSave }
									onChange={ ( e ) =>
										setPurgeOnSave( e.target.checked )
									}
								/>
								<span>
									{ __(
										'Refresh pages when content changes',
										'saman-seo'
									) }
								</span>
							</label>
							<label
								className="gsc-field"
								htmlFor="saman-pc-exclusions"
							>
								<span>
									{ __(
										'Never cache these paths (one per line)',
										'saman-seo'
									) }
								</span>
								<textarea
									id="saman-pc-exclusions"
									rows="4"
									value={ exclusions }
									placeholder={
										'/membership/\n/custom-page/'
									}
									onChange={ ( e ) =>
										setExclusions( e.target.value )
									}
								/>
							</label>
							<button
								type="button"
								className="button primary"
								disabled={ busy }
								onClick={ handleSaveSettings }
							>
								{ __( 'Save Settings', 'saman-seo' ) }
							</button>
						</div>
					) }

					<p className="gsc-fineprint">
						{ status.tier_label } ·{ ' ' }
						{ formatBytes( stats.disk_bytes ) }
					</p>
				</>
			) }
		</div>
	);
};

const formatNumber = ( value ) =>
	new Intl.NumberFormat().format( Math.round( Number( value ) || 0 ) );

const formatMs = ( value ) => formatNumber( value || 0 );

const formatBytes = ( bytes ) => {
	bytes = Number( bytes ) || 0;

	if ( bytes < 1024 ) {
		return `${ bytes } B`;
	}

	if ( bytes < 1024 * 1024 ) {
		return `${ ( bytes / 1024 ).toFixed( 1 ) } KB`;
	}

	return `${ ( bytes / ( 1024 * 1024 ) ).toFixed( 1 ) } MB`;
};

export default PageCacheCard;
