import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

// Issue type labels
import { __, sprintf } from '@wordpress/i18n';
const ISSUE_TYPE_LABELS = {
	title_missing: __( 'Missing Meta Title', 'saman-seo' ),
	title_length: __( 'Title Too Long', 'saman-seo' ),
	description_missing: __( 'Missing Meta Description', 'saman-seo' ),
	description_length: __( 'Description Too Long', 'saman-seo' ),
	missing_alt: __( 'Missing Alt Text', 'saman-seo' ),
	low_word_count: __( 'Low Word Count', 'saman-seo' ),
	missing_h1: __( 'Missing H1 Heading', 'saman-seo' ),
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
				<div className="page-header">
					<div>
						<h1>{ __( 'SEO Audit', 'saman-seo' ) }</h1>
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
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'SEO Audit', 'saman-seo' ) }</h1>
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
							__( 'Run Full Audit', 'saman-seo' )
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
			<div className="audit-stats-grid">
				<div
					className={ `audit-stat-card ${
						stats.severity.high > 0 ? 'has-issues' : 'no-issues'
					}` }
				>
					<div className="audit-stat-icon danger">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M12 9v4M12 17h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
							/>
						</svg>
					</div>
					<div className="audit-stat-content">
						<div className="audit-stat-number">
							{ stats.severity.high }
						</div>
						<div className="audit-stat-label">
							{ __( 'Critical Issues', 'saman-seo' ) }
						</div>
						<div className="audit-stat-desc">
							{ stats.severity.high === 0
								? __(
										'All critical checks passed',
										'saman-seo'
								  )
								: __(
										'Issues severely impacting SEO',
										'saman-seo'
								  ) }
						</div>
					</div>
				</div>

				<div
					className={ `audit-stat-card ${
						stats.severity.medium > 0 ? 'has-issues' : 'no-issues'
					}` }
				>
					<div className="audit-stat-icon warning">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</div>
					<div className="audit-stat-content">
						<div className="audit-stat-number">
							{ stats.severity.medium }
						</div>
						<div className="audit-stat-label">
							{ __( 'Warnings', 'saman-seo' ) }
						</div>
						<div className="audit-stat-desc">
							{ stats.severity.medium === 0
								? __( 'No warnings found', 'saman-seo' )
								: __(
										'Issues that may affect rankings',
										'saman-seo'
								  ) }
						</div>
					</div>
				</div>

				<div className="audit-stat-card">
					<div className="audit-stat-icon muted">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</div>
					<div className="audit-stat-content">
						<div className="audit-stat-number">
							{ stats.severity.low }
						</div>
						<div className="audit-stat-label">
							{ __( 'Suggestions', 'saman-seo' ) }
						</div>
						<div className="audit-stat-desc">
							{ stats.severity.low === 0
								? __( 'No suggestions', 'saman-seo' )
								: __(
										'Optional improvements available',
										'saman-seo'
								  ) }
						</div>
					</div>
				</div>

				<div className="audit-stat-card info">
					<div className="audit-stat-icon info">
						<svg
							width="24"
							height="24"
							viewBox="0 0 24 24"
							fill="none"
						>
							<path
								d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							/>
						</svg>
					</div>
					<div className="audit-stat-content">
						<div className="audit-stat-number">
							{ auditData?.scanned || 0 }
						</div>
						<div className="audit-stat-label">
							{ __( 'Posts Scanned', 'saman-seo' ) }
						</div>
						<div className="audit-stat-desc">
							{ stats.posts_with_issues || 0 }{ ' ' }
							{ __( 'with issues', 'saman-seo' ) }
						</div>
					</div>
				</div>
			</div>

			{ /* Issues by Type */ }
			<section className="audit-section">
				<div className="audit-section-header">
					<h2>{ __( 'Issues by Type', 'saman-seo' ) }</h2>
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
																				'Edit Post',
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
							<h2>{ __( 'Quick Fixes', 'saman-seo' ) }</h2>
							<p className="muted">
								{ __(
									'Apply suggested meta titles and descriptions with one click.',
									'saman-seo'
								) }
							</p>
						</div>

						<div className="recommendations-list">
							{ auditData.recommendations.map( ( rec ) => (
								<div
									key={ rec.post_id }
									className="recommendation-card"
								>
									<div className="recommendation-header">
										<h4>{ rec.title }</h4>
										<a
											href={ rec.edit_url }
											target="_blank"
											rel="noopener noreferrer"
											className="link-button small"
										>
											{ __( 'Edit Post', 'saman-seo' ) }
										</a>
									</div>
									<div className="recommendation-suggestions">
										<div className="suggestion-item">
											<label>
												{ __(
													'Suggested Title',
													'saman-seo'
												) }
											</label>
											<div className="suggestion-value">
												{ rec.suggested_title }
											</div>
										</div>
										<div className="suggestion-item">
											<label>
												{ __(
													'Suggested Description',
													'saman-seo'
												) }
											</label>
											<div className="suggestion-value">
												{ rec.suggested_description ||
													__(
														'(No suggestion available)',
														'saman-seo'
													) }
											</div>
										</div>
									</div>
									<div className="recommendation-actions">
										<button
											type="button"
											className="button primary small"
											onClick={ () =>
												handleApplyRecommendation( rec )
											}
											disabled={
												applyingRecommendation ===
												rec.post_id
											}
										>
											{ applyingRecommendation ===
											rec.post_id
												? __(
														'Applying\u2026',
														'saman-seo'
												  )
												: __(
														'Apply Suggestions',
														'saman-seo'
												  ) }
										</button>
									</div>
								</div>
							) ) }
						</div>
					</section>
				) }
		</div>
	);
};
export default Audit;
