import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Issue type labels
import { KpiBand, MetricCard, Sparkline } from '../components/Kpi';
import { __, sprintf } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';
const ISSUE_TYPE_LABELS = {
	title_missing: __( 'Missing meta title', 'saman-seo' ),
	title_length: __( 'Title too long', 'saman-seo' ),
	description_missing: __( 'Missing meta description', 'saman-seo' ),
	description_length: __( 'Description too long', 'saman-seo' ),
	missing_alt: __( 'Missing alt text', 'saman-seo' ),
	low_word_count: __( 'Low word count', 'saman-seo' ),
	missing_h1: __( 'Missing H1 heading', 'saman-seo' ),
};

// Severity colors and labels
const SEVERITY_CONFIG = {
	high: {
		label: __( 'Critical', 'saman-seo' ),
		class: 'danger',
		color: 'var(--color-danger)',
	},
	medium: {
		label: __( 'Warning', 'saman-seo' ),
		class: 'warning',
		color: 'var(--color-warning)',
	},
	low: {
		label: __( 'Suggestion', 'saman-seo' ),
		class: 'muted',
		color: 'var(--color-muted)',
	},
};
const Audit = () => {
	const [ loading, setLoading ] = useState( true );
	const [ running, setRunning ] = useState( false );
	const [ auditData, setAuditData ] = useState( null );
	const [ message, setMessage ] = useState( {
		type: '',
		text: '',
	} );
	const [ expandedType, setExpandedType ] = useState( null );
	const [ applyingRecommendation, setApplyingRecommendation ] =
		useState( null );

	// Fetch audit data
	const fetchAudit = useCallback( async () => {
		setLoading( true );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/audit',
			} );
			if ( res.success ) {
				setAuditData( res.data );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch audit:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to load audit data.', 'saman-seo' ),
			} );
		} finally {
			setLoading( false );
		}
	}, [] );
	useEffect( () => {
		fetchAudit();
	}, [ fetchAudit ] );

	// Run new audit
	const handleRunAudit = async () => {
		setRunning( true );
		setMessage( {
			type: '',
			text: '',
		} );
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/audit/run',
				method: 'POST',
				data: {
					post_type: 'any',
					limit: 100,
				},
			} );
			if ( res.success ) {
				setAuditData( res.data );
				setMessage( {
					type: 'success',
					text: sprintf(
						/* translators: %s: placeholder */ __(
							'Audit complete! Scanned %s posts.',
							'saman-seo'
						),
						res.data.scanned
					),
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to run audit:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to run audit.', 'saman-seo' ),
			} );
		} finally {
			setRunning( false );
		}
	};

	// Apply recommendation
	const handleApplyRecommendation = async ( rec ) => {
		setApplyingRecommendation( rec.post_id );
		try {
			const res = await apiFetch( {
				path: `/saman-seo/v1/audit/apply/${ rec.post_id }`,
				method: 'POST',
				data: {
					title: rec.suggested_title,
					description: rec.suggested_description,
				},
			} );
			if ( res.success ) {
				setMessage( {
					type: 'success',
					text: sprintf(
						/* translators: %s: placeholder */ __(
							'Applied recommendations to "%s"',
							'saman-seo'
						),
						rec.title
					),
				} );
				// Remove from recommendations list
				setAuditData( ( prev ) => ( {
					...prev,
					recommendations: prev.recommendations.filter(
						( r ) => r.post_id !== rec.post_id
					),
				} ) );
			}
		} catch ( error ) {
			console.error( 'Failed to apply recommendation:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to apply recommendation.', 'saman-seo' ),
			} );
		} finally {
			setApplyingRecommendation( null );
		}
	};

	// Group issues by type
	const getIssuesByType = () => {
		if ( ! auditData?.issues ) return {};
		const grouped = {};
		auditData.issues.forEach( ( issue ) => {
			if ( ! grouped[ issue.type ] ) {
				grouped[ issue.type ] = [];
			}
			grouped[ issue.type ].push( issue );
		} );
		return grouped;
	};

	// Get severity for an issue type (use highest severity in group)
	const getTypeSeverity = ( issues ) => {
		if ( issues.some( ( i ) => i.severity === 'high' ) ) return 'high';
		if ( issues.some( ( i ) => i.severity === 'medium' ) ) return 'medium';
		return 'low';
	};
	if ( loading ) {
		return (
			<div className="page">
				<KpiBand loading count={ 4 } />
				<div className="page-header">
					<div>
						<h1>{ __( 'SEO audit', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'Scan your site for SEO issues and get actionable recommendations.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>
				<div className="loading-state">
					{ __( 'Loading audit data\u2026', 'saman-seo' ) }
				</div>
			</div>
		);
	}
	const stats = auditData?.stats || {
		severity: {
			high: 0,
			medium: 0,
			low: 0,
		},
		total: 0,
	};
	const issuesByType = getIssuesByType();
	const scoreHistory = auditData?.score_history || [];
	return (
		<div className="page">
			<KpiBand label={ __( 'Audit summary', 'saman-seo' ) }>
				<MetricCard
					label={ __( 'Critical issues', 'saman-seo' ) }
					value={ stats.severity.high }
					tone={ stats.severity.high > 0 ? 'danger' : 'success' }
					sub={
						stats.severity.high === 0
							? __( 'All critical checks passed', 'saman-seo' )
							: __( 'Issues severely impacting SEO', 'saman-seo' )
					}
				/>
				<MetricCard
					label={ __( 'Warnings', 'saman-seo' ) }
					value={ stats.severity.medium }
					tone={ stats.severity.medium > 0 ? 'warning' : 'success' }
					sub={
						stats.severity.medium === 0
							? __( 'No warnings found', 'saman-seo' )
							: __(
									'Issues that may affect rankings',
									'saman-seo'
							  )
					}
				/>
				<MetricCard
					label={ __( 'Suggestions', 'saman-seo' ) }
					value={ stats.severity.low }
					sub={
						stats.severity.low === 0
							? __( 'No suggestions', 'saman-seo' )
							: __(
									'Optional improvements available',
									'saman-seo'
							  )
					}
				/>
				{ scoreHistory.length > 1 ? (
					<MetricCard
						label={ __( 'Site SEO score', 'saman-seo' ) }
						value={ scoreHistory[ scoreHistory.length - 1 ] }
						unit={ __( 'of 100', 'saman-seo' ) }
						sub={ sprintf(
							/* translators: 1: posts scanned, 2: posts with issues */
							__(
								'%1$s posts scanned, %2$s with issues',
								'saman-seo'
							),
							auditData?.scanned || 0,
							stats.posts_with_issues || 0
						) }
						spark={ <Sparkline data={ scoreHistory } /> }
					/>
				) : (
					<MetricCard
						label={ __( 'Posts scanned', 'saman-seo' ) }
						value={ auditData?.scanned || 0 }
						sub={ sprintf(
							/* translators: %s: number of posts */
							__( '%s with issues', 'saman-seo' ),
							stats.posts_with_issues || 0
						) }
					/>
				) }
			</KpiBand>
			<div className="page-header">
				<div>
					<h1>{ __( 'SEO audit', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Scan your site for SEO issues and get actionable recommendations.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					{ auditData?.from_cache && (
						<span className="cache-badge">
							{ __( 'Cached results', 'saman-seo' ) }
						</span>
					) }
					<button
						type="button"
						className="button primary"
						onClick={ handleRunAudit }
						disabled={ running }
					>
						{ running ? (
							<>
								<span className="spinner"></span>
								{ __( 'Running Audit\u2026', 'saman-seo' ) }
							</>
						) : (
							__( 'Run full audit', 'saman-seo' )
						) }
					</button>
				</div>
			</div>

			{ message.text && (
				<div className={ `notice-message ${ message.type }` }>
					{ message.text }
				</div>
			) }

			{ /* Stats Cards */ }

			{ /* Issues by Type */ }
			<section className="audit-section">
				<div className="audit-section-header">
					<h2>{ __( 'Issues by type', 'saman-seo' ) }</h2>
					<p className="muted">
						{ __(
							'Click on an issue type to see affected posts.',
							'saman-seo'
						) }
					</p>
				</div>

				{ Object.keys( issuesByType ).length === 0 ? (
					<div className="empty-state">
						<svg
							width="48"
							height="48"
							viewBox="0 0 24 24"
							fill="none"
							style={ {
								color: 'var(--color-success)',
							} }
						>
							<path
								d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
						<h3>{ __( 'No issues found!', 'saman-seo' ) }</h3>
						<p>
							{ __(
								'Your site is in great SEO shape.',
								'saman-seo'
							) }
						</p>
					</div>
				) : (
					<div className="audit-issues-list">
						{ Object.entries( issuesByType ).map(
							( [ type, issues ] ) => {
								const severity = getTypeSeverity( issues );
								const config = SEVERITY_CONFIG[ severity ];
								const isExpanded = expandedType === type;
								return (
									<div
										key={ type }
										className={ `audit-issue-group ${
											isExpanded ? 'expanded' : ''
										}` }
									>
										<button
											type="button"
											className="audit-issue-header"
											onClick={ () =>
												setExpandedType(
													isExpanded ? null : type
												)
											}
										>
											<div className="audit-issue-info">
												<span
													className={ `severity-dot ${ config.class }` }
												></span>
												<span className="audit-issue-type">
													{ ISSUE_TYPE_LABELS[
														type
													] || type }
												</span>
												<span
													className={ `pill ${ config.class }` }
												>
													{ issues.length }
												</span>
											</div>
											<svg
												className={ `chevron ${
													isExpanded ? 'expanded' : ''
												}` }
												width="20"
												height="20"
												viewBox="0 0 24 24"
												fill="none"
											>
												<path
													d="M19 9l-7 7-7-7"
													stroke="currentColor"
													strokeWidth="2"
													strokeLinecap="round"
													strokeLinejoin="round"
												/>
											</svg>
										</button>

										{ isExpanded && (
											<div className="audit-issue-posts">
												<table className="data-table compact">
													<thead>
														<tr>
															<th>
																{ __(
																	'Post',
																	'saman-seo'
																) }
															</th>
															<th>
																{ __(
																	'Issue',
																	'saman-seo'
																) }
															</th>
															<th>
																{ __(
																	'Action',
																	'saman-seo'
																) }
															</th>
														</tr>
													</thead>
													<tbody>
														{ issues.map(
															( issue, idx ) => (
																<tr key={ idx }>
																	<td>
																		<a
																			href={
																				issue.edit_url
																			}
																			target="_blank"
																			rel="noopener noreferrer"
																		>
																			{
																				issue.title
																			}
																		</a>
																	</td>
																	<td>
																		{
																			issue.message
																		}
																	</td>
																	<td>
																		<a
																			href={
																				issue.edit_url
																			}
																			target="_blank"
																			rel="noopener noreferrer"
																			className="link-button"
																		>
																			{ __(
																				'Edit post',
																				'saman-seo'
																			) }
																		</a>
																	</td>
																</tr>
															)
														) }
													</tbody>
												</table>
											</div>
										) }
									</div>
								);
							}
						) }
					</div>
				) }
			</section>

			{ /* Recommendations */ }
			{ auditData?.recommendations &&
				auditData.recommendations.length > 0 && (
					<section className="audit-section">
						<div className="audit-section-header">
							<h2>{ __( 'Quick fixes', 'saman-seo' ) }</h2>
							<p className="muted">
								{ __(
									'Apply suggested meta titles and descriptions with one click.',
									'saman-seo'
								) }
							</p>
						</div>

						<ul className="quick-fixes">
							{ auditData.recommendations.map( ( rec ) => (
								<li key={ rec.post_id } className="quick-fix">
									<div className="quick-fix__main">
										<div className="quick-fix__head">
											<h3>
												{ decodeEntities( rec.title ) }
											</h3>
											<a
												href={ rec.edit_url }
												target="_blank"
												rel="noopener noreferrer"
											>
												{ __(
													'Edit post',
													'saman-seo'
												) }
											</a>
										</div>
										<dl className="quick-fix__fields">
											<dt>
												{ __( 'Title', 'saman-seo' ) }
											</dt>
											<dd>
												{ decodeEntities(
													rec.suggested_title || ''
												) }
											</dd>
											<dt>
												{ __(
													'Description',
													'saman-seo'
												) }
											</dt>
											<dd
												className={
													rec.suggested_description
														? ''
														: 'is-empty'
												}
											>
												{ rec.suggested_description
													? decodeEntities(
															rec.suggested_description
													  )
													: __(
															'No suggestion available',
															'saman-seo'
													  ) }
											</dd>
										</dl>
									</div>
									<button
										type="button"
										className="button small"
										onClick={ () =>
											handleApplyRecommendation( rec )
										}
										disabled={
											applyingRecommendation ===
											rec.post_id
										}
									>
										{ applyingRecommendation === rec.post_id
											? __(
													'Applying\u2026',
													'saman-seo'
											  )
											: __( 'Apply', 'saman-seo' ) }
									</button>
								</li>
							) ) }
						</ul>
					</section>
				) }
		</div>
	);
};
export default Audit;
