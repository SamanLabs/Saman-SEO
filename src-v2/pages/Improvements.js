import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';

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
		label: __( 'Needs attention', 'saman-seo' ),
		pill: 'danger',
	},
	off: {
		label: __( 'Not enabled', 'saman-seo' ),
		pill: '',
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

			{ groups.map( ( group ) => (
				<section key={ group.id } className="improvements-group">
					<h2 className="improvements-group__title">
						{ group.label }
					</h2>
					<ul className="improvements-list">
						{ group.items.map( ( item ) => {
							const meta =
								STATUS_META[ item.status ] || STATUS_META.off;

							return (
								<li key={ item.id } className="improvement-row">
									<div className="improvement-row__main">
										<div className="improvement-row__title">
											<h3>{ item.title }</h3>
											<span
												className={ `pill ${ meta.pill }` }
											>
												{ meta.label }
											</span>
										</div>
										<p className="improvement-row__desc">
											{ item.description }
										</p>
										{ item.metrics.length > 0 && (
											<dl className="improvement-row__metrics">
												{ item.metrics.map(
													( metric ) => (
														<div
															key={ metric.label }
														>
															<dd>
																{ metric.value }
															</dd>
															<dt>
																{ metric.label }
															</dt>
														</div>
													)
												) }
											</dl>
										) }
									</div>
									<span className="improvement-row__impact">
										{ IMPACT_LABEL[ item.impact ] ||
											item.impact }
									</span>
									<button
										type="button"
										className={ `button small${
											item.status === 'on'
												? ''
												: ' primary'
										}` }
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
								</li>
							);
						} ) }
					</ul>
				</section>
			) ) }
		</div>
	);
};

export default Improvements;
