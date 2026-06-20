import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
const TAB_OPTIONS = [
	{
		value: 'overview',
		label: __( 'Overview', 'saman-seo' ),
	},
	{
		value: 'broken',
		label: __( 'Broken Links', 'saman-seo' ),
	},
	{
		value: 'orphans',
		label: __( 'Orphan Pages', 'saman-seo' ),
	},
];
const LinkHealth = () => {
	const [ activeTab, setActiveTab ] = useState( 'overview' );
	const [ loading, setLoading ] = useState( true );
	const [ summary, setSummary ] = useState( null );
	const [ brokenLinks, setBrokenLinks ] = useState( {
		items: [],
		total: 0,
		page: 1,
		total_pages: 1,
	} );
	const [ orphanPages, setOrphanPages ] = useState( {
		items: [],
		total: 0,
		page: 1,
		total_pages: 1,
	} );
	const [ scanning, setScanning ] = useState( false );
	const [ scanStatus, setScanStatus ] = useState( null );
	const [ recheckingId, setRecheckingId ] = useState( null );
	const [ linkTypeFilter, setLinkTypeFilter ] = useState( '' );

	// Fetch summary
	const fetchSummary = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/link-health/summary',
			} );
			if ( res.success ) {
				setSummary( res.data );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch summary:', error );
		}
	}, [] );

	// Fetch broken links
	const fetchBrokenLinks = useCallback(
		async ( page = 1 ) => {
			setLoading( true );
			try {
				const params = new URLSearchParams( {
					page,
					per_page: 50,
					type: linkTypeFilter,
				} );
				const res = await apiFetch( {
					path: `/saman-seo/v1/link-health/broken?${ params }`,
				} );
				if ( res.success ) {
					setBrokenLinks( res.data );
				}
			} catch ( error ) {
				console.error( 'Failed to fetch broken links:', error );
			} finally {
				setLoading( false );
			}
		},
		[ linkTypeFilter ]
	);

	// Fetch orphan pages
	const fetchOrphanPages = useCallback( async ( page = 1 ) => {
		setLoading( true );
		try {
			const params = new URLSearchParams( {
				page,
				per_page: 50,
			} );
			const res = await apiFetch( {
				path: `/saman-seo/v1/link-health/orphans?${ params }`,
			} );
			if ( res.success ) {
				setOrphanPages( res.data );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch orphan pages:', error );
		} finally {
			setLoading( false );
		}
	}, [] );

	// Check scan status
	const checkScanStatus = useCallback( async () => {
		try {
			const res = await apiFetch( {
				path: '/saman-seo/v1/link-health/scan/status',
			} );
			if ( res.success && res.data ) {
				setScanStatus( res.data );
				if ( res.data.status === 'running' ) {
					setScanning( true );
				} else {
					setScanning( false );
				}
			} else {
				setScanStatus( null );
				setScanning( false );
			}
		} catch ( error ) {
			console.error( 'Failed to check scan status:', error );
		}
	}, [] );

	// Initial load
	useEffect( () => {
		fetchSummary();
		checkScanStatus();
	}, [ fetchSummary, checkScanStatus ] );

	// Load data based on active tab
	useEffect( () => {
		if ( activeTab === 'broken' ) {
			fetchBrokenLinks( 1 );
		} else if ( activeTab === 'orphans' ) {
			fetchOrphanPages( 1 );
		}
	}, [ activeTab, fetchBrokenLinks, fetchOrphanPages ] );

	// Poll scan status while scanning
	useEffect( () => {
		if ( ! scanning ) return;
		const interval = setInterval( () => {
			checkScanStatus();
			fetchSummary();
		}, 3000 );
		return () => clearInterval( interval );
	}, [ scanning, checkScanStatus, fetchSummary ] );

	// Start scan
	const handleStartScan = async () => {
		setScanning( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/link-health/scan',
				method: 'POST',
				data: {
					type: 'full',
				},
			} );
			checkScanStatus();
		} catch ( error ) {
			console.error( 'Failed to start scan:', error );
			setScanning( false );
		}
	};

	// Recheck a link
	const handleRecheckLink = async ( linkId ) => {
		setRecheckingId( linkId );
		try {
			const res = await apiFetch( {
				path: `/saman-seo/v1/link-health/link/${ linkId }/recheck`,
				method: 'POST',
			} );
			if ( res.success ) {
				// Refresh broken links
				fetchBrokenLinks( brokenLinks.page );
				fetchSummary();
			}
		} catch ( error ) {
			console.error( 'Failed to recheck link:', error );
		} finally {
			setRecheckingId( null );
		}
	};

	// Delete a link entry
	const handleDeleteLink = async ( linkId ) => {
		if (
			! window.confirm(
				__(
					'Remove this link from the report? This will not remove the actual link from your content.',
					'saman-seo'
				)
			)
		) {
			return;
		}
		try {
			await apiFetch( {
				path: `/saman-seo/v1/link-health/link/${ linkId }`,
				method: 'DELETE',
			} );
			fetchBrokenLinks( brokenLinks.page );
			fetchSummary();
		} catch ( error ) {
			console.error( 'Failed to delete link:', error );
		}
	};

	// Format date
	const formatDate = ( dateStr ) => {
		if ( ! dateStr ) return '-';
		const date = new Date( dateStr );
		return (
			date.toLocaleDateString() +
			', ' +
			date.toLocaleTimeString( [], {
				hour: '2-digit',
				minute: '2-digit',
			} )
		);
	};

	// Health score color
	const getHealthColor = ( score ) => {
		if ( score >= 90 ) return 'success';
		if ( score >= 70 ) return 'warning';
		return 'danger';
	};
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Link Health', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Monitor and fix broken links across your site.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<button
						type="button"
						className="button primary"
						onClick={ handleStartScan }
						disabled={ scanning }
					>
						{ scanning
							? __( 'Scanning\u2026', 'saman-seo' )
							: __( 'Run Full Scan', 'saman-seo' ) }
					</button>
				</div>
			</div>

			{ /* Scan Progress */ }
			{ scanning && scanStatus && (
				<div className="scan-progress">
					<div className="scan-progress__info">
						<span>
							{ __( 'Scanning posts\u2026', 'saman-seo' ) }{ ' ' }
							{ scanStatus.scanned_posts } /{ ' ' }
							{ scanStatus.total_posts }
						</span>
					</div>
					<div className="scan-progress__bar">
						<div
							className="scan-progress__fill"
							style={ {
								width: `${
									( scanStatus.scanned_posts /
										scanStatus.total_posts ) *
									100
								}%`,
							} }
						/>
					</div>
				</div>
			) }

			{ /* Tabs */ }
			<div className="tabs">
				{ TAB_OPTIONS.map( ( tab ) => (
					<button
						key={ tab.value }
						type="button"
						className={ `tab ${
							activeTab === tab.value ? 'active' : ''
						}` }
						onClick={ () => setActiveTab( tab.value ) }
					>
						{ tab.label }
						{ tab.value === 'broken' &&
							summary?.broken_links > 0 && (
								<span className="tab-badge danger">
									{ summary.broken_links }
								</span>
							) }
						{ tab.value === 'orphans' &&
							summary?.orphan_pages > 0 && (
								<span className="tab-badge warning">
									{ summary.orphan_pages }
								</span>
							) }
					</button>
				) ) }
			</div>

			{ /* Overview Tab */ }
			{ activeTab === 'overview' && (
				<section className="panel">
					{ ! summary ? (
						<div className="loading-state">
							{ __( 'Loading summary\u2026', 'saman-seo' ) }
						</div>
					) : (
						<>
							{ /* Health Score */ }
							<div className="health-score-card">
								<div
									className={ `health-score ${ getHealthColor(
										summary.health_score
									) }` }
								>
									<span className="health-score__value">
										{ summary.health_score }%
									</span>
									<span className="health-score__label">
										{ __(
											'Link Health Score',
											'saman-seo'
										) }
									</span>
								</div>
								<div className="health-score__info">
									<p>
										{ summary.health_score >= 90
											? __(
													'Excellent! Your links are in great shape.',
													'saman-seo'
											  )
											: summary.health_score >= 70
											? __(
													'Good, but there are some issues to address.',
													'saman-seo'
											  )
											: __(
													'Needs attention. Multiple broken links detected.',
													'saman-seo'
											  ) }
									</p>
									{ summary.last_scan && (
										<span className="muted">
											{ __( 'Last scan:', 'saman-seo' ) }{ ' ' }
											{ formatDate( summary.last_scan ) }
										</span>
									) }
								</div>
							</div>

							{ /* Stats Grid */ }
							<div className="stats-grid">
								<div className="stat-card">
									<span className="stat-card__value">
										{ summary.total_links.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'Total Links', 'saman-seo' ) }
									</span>
								</div>
								<div className="stat-card danger">
									<span className="stat-card__value">
										{ summary.broken_links.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'Broken Links', 'saman-seo' ) }
									</span>
								</div>
								<div className="stat-card warning">
									<span className="stat-card__value">
										{ summary.redirects.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'Redirects', 'saman-seo' ) }
									</span>
								</div>
								<div className="stat-card">
									<span className="stat-card__value">
										{ summary.internal.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'Internal Links', 'saman-seo' ) }
									</span>
								</div>
								<div className="stat-card">
									<span className="stat-card__value">
										{ summary.external.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'External Links', 'saman-seo' ) }
									</span>
								</div>
								<div className="stat-card warning">
									<span className="stat-card__value">
										{ summary.orphan_pages.toLocaleString() }
									</span>
									<span className="stat-card__label">
										{ __( 'Orphan Pages', 'saman-seo' ) }
									</span>
								</div>
							</div>

							{ /* Quick Actions */ }
							{ ( summary.broken_links > 0 ||
								summary.orphan_pages > 0 ) && (
								<div className="quick-actions">
									<h3>
										{ __(
											'Recommended Actions',
											'saman-seo'
										) }
									</h3>
									{ summary.broken_links > 0 && (
										<button
											type="button"
											className="action-card"
											onClick={ () =>
												setActiveTab( 'broken' )
											}
										>
											<span className="action-card__icon danger">
												!
											</span>
											<div className="action-card__content">
												<strong>
													{ __( 'Fix', 'saman-seo' ) }{ ' ' }
													{ summary.broken_links }{ ' ' }
													{ __(
														'broken link',
														'saman-seo'
													) }
													{ summary.broken_links !== 1
														? 's'
														: '' }
												</strong>
												<span>
													{ __(
														'Update or remove links that return errors',
														'saman-seo'
													) }
												</span>
											</div>
										</button>
									) }
									{ summary.orphan_pages > 0 && (
										<button
											type="button"
											className="action-card"
											onClick={ () =>
												setActiveTab( 'orphans' )
											}
										>
											<span className="action-card__icon warning">
												?
											</span>
											<div className="action-card__content">
												<strong>
													{ __(
														'Link to',
														'saman-seo'
													) }{ ' ' }
													{ summary.orphan_pages }{ ' ' }
													{ __(
														'orphan page',
														'saman-seo'
													) }
													{ summary.orphan_pages !== 1
														? 's'
														: '' }
												</strong>
												<span>
													{ __(
														'Pages with no internal links pointing to them',
														'saman-seo'
													) }
												</span>
											</div>
										</button>
									) }
								</div>
							) }
						</>
					) }
				</section>
			) }

			{ /* Broken Links Tab */ }
			{ activeTab === 'broken' && (
				<section className="panel">
					<div className="panel-header">
						<h3>{ __( 'Broken Links', 'saman-seo' ) }</h3>
						<div className="filter-row">
							<label className="filter-field">
								<span>{ __( 'Type', 'saman-seo' ) }</span>
								<select
									value={ linkTypeFilter }
									onChange={ ( e ) =>
										setLinkTypeFilter( e.target.value )
									}
								>
									<option value="">
										{ __( 'All Links', 'saman-seo' ) }
									</option>
									<option value="internal">
										{ __( 'Internal Only', 'saman-seo' ) }
									</option>
									<option value="external">
										{ __( 'External Only', 'saman-seo' ) }
									</option>
								</select>
							</label>
						</div>
					</div>

					{ loading ? (
						<div className="loading-state">
							{ __( 'Loading broken links\u2026', 'saman-seo' ) }
						</div>
					) : brokenLinks.items.length === 0 ? (
						<div className="empty-state">
							<div className="empty-state__icon success">
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="48"
									height="48"
								>
									<circle cx="12" cy="12" r="10" />
									<path d="M9 12l2 2 4-4" />
								</svg>
							</div>
							<h3>
								{ __( 'No broken links found', 'saman-seo' ) }
							</h3>
							<p>
								{ __(
									'All your links are working correctly.',
									'saman-seo'
								) }
							</p>
						</div>
					) : (
						<>
							<table className="data-table">
								<thead>
									<tr>
										<th>
											{ __( 'Source Page', 'saman-seo' ) }
										</th>
										<th>
											{ __( 'Broken URL', 'saman-seo' ) }
										</th>
										<th>
											{ __( 'Link Text', 'saman-seo' ) }
										</th>
										<th>{ __( 'Status', 'saman-seo' ) }</th>
										<th>
											{ __( 'Actions', 'saman-seo' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ brokenLinks.items.map( ( link ) => (
										<tr key={ link.id }>
											<td>
												<a
													href={ link.source_url }
													target="_blank"
													rel="noopener noreferrer"
												>
													{ link.source_title ||
														__(
															'Untitled',
															'saman-seo'
														) }
												</a>
											</td>
											<td className="url-cell">
												<code>{ link.target_url }</code>
												<span
													className={ `badge ${
														link.link_type ===
														'external'
															? 'info'
															: 'muted'
													}` }
												>
													{ link.link_type }
												</span>
											</td>
											<td>{ link.link_text || '-' }</td>
											<td>
												<span className="badge danger">
													{ link.http_code ||
														__(
															'Error',
															'saman-seo'
														) }
												</span>
												{ link.error_message && (
													<span
														className="muted small"
														title={
															link.error_message
														}
													>
														{ link.error_message.substring(
															0,
															30
														) }
														...
													</span>
												) }
											</td>
											<td className="action-cell">
												<div className="action-buttons">
													<a
														href={ `/wp-admin/post.php?post=${ link.source_post_id }&action=edit` }
														className="button small"
														target="_blank"
														rel="noopener noreferrer"
													>
														{ __(
															'Edit',
															'saman-seo'
														) }
													</a>
													<button
														type="button"
														className="button ghost small"
														onClick={ () =>
															handleRecheckLink(
																link.id
															)
														}
														disabled={
															recheckingId ===
															link.id
														}
													>
														{ recheckingId ===
														link.id
															? '...'
															: __(
																	'Recheck',
																	'saman-seo'
															  ) }
													</button>
													<button
														type="button"
														className="button ghost small danger"
														onClick={ () =>
															handleDeleteLink(
																link.id
															)
														}
													>
														{ __(
															'Dismiss',
															'saman-seo'
														) }
													</button>
												</div>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>

							{ /* Pagination */ }
							{ brokenLinks.total_pages > 1 && (
								<div className="pagination">
									<span className="pagination-info">
										{ brokenLinks.total.toLocaleString() }{ ' ' }
										{ __( 'broken link', 'saman-seo' ) }
										{ brokenLinks.total !== 1 ? 's' : '' }
									</span>
									<div className="pagination-links">
										<button
											type="button"
											className="pagination-btn"
											disabled={ brokenLinks.page <= 1 }
											onClick={ () =>
												fetchBrokenLinks(
													brokenLinks.page - 1
												)
											}
										>
											{ __(
												'\u2039 Previous',
												'saman-seo'
											) }
										</button>
										<span className="pagination-current">
											{ brokenLinks.page }{ ' ' }
											{ __( 'of', 'saman-seo' ) }{ ' ' }
											{ brokenLinks.total_pages }
										</span>
										<button
											type="button"
											className="pagination-btn"
											disabled={
												brokenLinks.page >=
												brokenLinks.total_pages
											}
											onClick={ () =>
												fetchBrokenLinks(
													brokenLinks.page + 1
												)
											}
										>
											{ __( 'Next \u203A', 'saman-seo' ) }
										</button>
									</div>
								</div>
							) }
						</>
					) }
				</section>
			) }

			{ /* Orphan Pages Tab */ }
			{ activeTab === 'orphans' && (
				<section className="panel">
					<div className="panel-header">
						<h3>{ __( 'Orphan Pages', 'saman-seo' ) }</h3>
						<p className="panel-desc">
							{ __(
								'Pages with no internal links pointing to them. Consider adding links from other content.',
								'saman-seo'
							) }
						</p>
					</div>

					{ loading ? (
						<div className="loading-state">
							{ __( 'Loading orphan pages\u2026', 'saman-seo' ) }
						</div>
					) : orphanPages.items.length === 0 ? (
						<div className="empty-state">
							<div className="empty-state__icon success">
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="48"
									height="48"
								>
									<circle cx="12" cy="12" r="10" />
									<path d="M9 12l2 2 4-4" />
								</svg>
							</div>
							<h3>
								{ __( 'No orphan pages found', 'saman-seo' ) }
							</h3>
							<p>
								{ __(
									'All your pages have at least one internal link pointing to them.',
									'saman-seo'
								) }
							</p>
						</div>
					) : (
						<>
							<table className="data-table">
								<thead>
									<tr>
										<th>
											{ __( 'Page Title', 'saman-seo' ) }
										</th>
										<th>{ __( 'Type', 'saman-seo' ) }</th>
										<th>
											{ __( 'Published', 'saman-seo' ) }
										</th>
										<th>
											{ __( 'Actions', 'saman-seo' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ orphanPages.items.map( ( page ) => (
										<tr key={ page.id }>
											<td>
												<a
													href={ page.url }
													target="_blank"
													rel="noopener noreferrer"
												>
													{ page.title ||
														__(
															'Untitled',
															'saman-seo'
														) }
												</a>
											</td>
											<td>
												<span className="badge muted">
													{ page.post_type }
												</span>
											</td>
											<td>
												{ formatDate( page.post_date ) }
											</td>
											<td className="action-cell">
												<a
													href={ page.edit_url }
													className="button small"
													target="_blank"
													rel="noopener noreferrer"
												>
													{ __(
														'Edit',
														'saman-seo'
													) }
												</a>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>

							{ /* Pagination */ }
							{ orphanPages.total_pages > 1 && (
								<div className="pagination">
									<span className="pagination-info">
										{ orphanPages.total.toLocaleString() }{ ' ' }
										{ __( 'orphan page', 'saman-seo' ) }
										{ orphanPages.total !== 1 ? 's' : '' }
									</span>
									<div className="pagination-links">
										<button
											type="button"
											className="pagination-btn"
											disabled={ orphanPages.page <= 1 }
											onClick={ () =>
												fetchOrphanPages(
													orphanPages.page - 1
												)
											}
										>
											{ __(
												'\u2039 Previous',
												'saman-seo'
											) }
										</button>
										<span className="pagination-current">
											{ orphanPages.page }{ ' ' }
											{ __( 'of', 'saman-seo' ) }{ ' ' }
											{ orphanPages.total_pages }
										</span>
										<button
											type="button"
											className="pagination-btn"
											disabled={
												orphanPages.page >=
												orphanPages.total_pages
											}
											onClick={ () =>
												fetchOrphanPages(
													orphanPages.page + 1
												)
											}
										>
											{ __( 'Next \u203A', 'saman-seo' ) }
										</button>
									</div>
								</div>
							) }
						</>
					) }
				</section>
			) }
		</div>
	);
};
export default LinkHealth;
