import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';

const STATUS_META = {
	on: {
		label: __( 'Active', 'saman-seo' ),
		pill: 'success',
	},
	partial: {
		label: __( 'Incomplete', 'saman-seo' ),
		pill: 'warning',
	},
	attention: {
		label: __( 'Needs Attention', 'saman-seo' ),
		pill: 'danger',
	},
	off: {
		label: __( 'Not Enabled', 'saman-seo' ),
		pill: 'muted',
	},
};

const IMPACT_LABEL = {
	high: __( 'High impact', 'saman-seo' ),
	medium: __( 'Medium impact', 'saman-seo' ),
	low: __( 'Nice to have', 'saman-seo' ),
};

/**
 * Improvements hub — everything the plugin can optimize, with live status
 * and measured before/after metrics where available.
 *
 * @param {Object}   root0            Component props.
 * @param {Function} root0.onNavigate View switcher from the app shell.
 * @return {*} The improvements page.
 */
const Improvements = ( { onNavigate } ) => {
	const [ data, setData ] = useState( null );
	const [ loading, setLoading ] = useState( true );

	const fetchImprovements = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/improvements',
			} );
			if ( res?.success ) {
				setData( res.data );
			}
		} catch {
			// Empty state renders below.
		} finally {
			setLoading( false );
		}
	}, [] );

	useEffect( () => {
		fetchImprovements();
	}, [ fetchImprovements ] );

	if ( loading ) {
		return (
			<div className="page">
				<div className="page-header">
					<div>
						<h1>{ __( 'Improvements', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'Everything Saman SEO can optimize, and what each one is doing for you.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading improvements…', 'saman-seo' ) }
				</div>
			</div>
		);
	}

	const groups = data?.groups || [];
	const counts = data?.counts || {
		on: 0,
		partial: 0,
		attention: 0,
		off: 0,
	};
	const total = counts.on + counts.partial + counts.attention + counts.off;
	const pct = total > 0 ? Math.round( ( counts.on / total ) * 100 ) : 0;

	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Improvements', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Everything Saman SEO can optimize, and what each one is doing for you.',
							'saman-seo'
						) }
					</p>
				</div>
			</div>

			<div className="improvements-summary">
				<div className="improvements-summary__bar">
					<div
						className="improvements-summary__fill"
						style={ { width: `${ pct }%` } }
					/>
				</div>
				<div className="improvements-summary__legend">
					<span>
						{ sprintf(
							/* translators: 1: active count, 2: total count, 3: percent */
							__(
								'%1$d of %2$d optimizations fully active (%3$d%%)',
								'saman-seo'
							),
							counts.on,
							total,
							pct
						) }
					</span>
					{ counts.attention > 0 && (
						<span className="imp-legend imp-legend--danger">
							{ sprintf(
								/* translators: %d: count */
								__( '%d need attention', 'saman-seo' ),
								counts.attention
							) }
						</span>
					) }
					{ counts.off > 0 && (
						<span className="imp-legend imp-legend--muted">
							{ sprintf(
								/* translators: %d: count */
								__( '%d not enabled', 'saman-seo' ),
								counts.off
							) }
						</span>
					) }
				</div>
			</div>

			{ groups.map( ( group ) => (
				<section key={ group.id } className="improvements-group">
					<h2 className="improvements-group__title">
						{ group.label }
					</h2>
					<div className="improvements-grid">
						{ group.items.map( ( item ) => {
							const meta =
								STATUS_META[ item.status ] || STATUS_META.off;

							return (
								<div
									key={ item.id }
									className={ `dashboard-card improvement-card status-${ item.status }` }
								>
									<div className="card-header">
										<h3>{ item.title }</h3>
										<span
											className={ `pill ${ meta.pill }` }
										>
											{ meta.label }
										</span>
									</div>

									<p className="improvement-card__desc">
										{ item.description }
									</p>

									{ item.metrics.length > 0 && (
										<div className="improvement-card__metrics">
											{ item.metrics.map( ( metric ) => (
												<div
													key={ metric.label }
													className="imp-metric"
												>
													<span className="imp-metric__value">
														{ metric.value }
													</span>
													<span className="imp-metric__label">
														{ metric.label }
													</span>
												</div>
											) ) }
										</div>
									) }

									<div className="improvement-card__footer">
										<span
											className={ `imp-impact imp-impact--${ item.impact }` }
										>
											{ IMPACT_LABEL[ item.impact ] ||
												item.impact }
										</span>
										<button
											type="button"
											className="button"
											onClick={ () =>
												item.view &&
												onNavigate &&
												onNavigate( item.view )
											}
										>
											{ item.status === 'on'
												? __( 'Open', 'saman-seo' )
												: __( 'Set up', 'saman-seo' ) }
										</button>
									</div>
								</div>
							);
						} ) }
					</div>
				</section>
			) ) }
		</div>
	);
};

export default Improvements;
