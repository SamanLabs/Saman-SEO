import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import CreateRedirectModal from '../components/CreateRedirectModal';
import IgnorePatternManager from '../components/IgnorePatternManager';
import { __ } from '@wordpress/i18n';
const SORT_OPTIONS = [
	{
		value: 'recent',
		label: __( 'Most recent', 'saman-seo' ),
	},
	{
		value: 'top',
		label: __( 'Top hits', 'saman-seo' ),
	},
];
const PER_PAGE_OPTIONS = [ 25, 50, 100, 200 ];
const Log404 = ( { onNavigate } ) => {
	// 404 Log state
	const [ logEntries, setLogEntries ] = useState( [] );
	const [ logLoading, setLogLoading ] = useState( true );
	const [ logTotal, setLogTotal ] = useState( 0 );
	const [ botCount, setBotCount ] = useState( 0 );
	const [ logPage, setLogPage ] = useState( 1 );
	const [ logPerPage, setLogPerPage ] = useState( 50 );
	const [ logTotalPages, setLogTotalPages ] = useState( 1 );
	const [ logSort, setLogSort ] = useState( 'recent' );
	const [ hideSpam, setHideSpam ] = useState( true );
	const [ hideImages, setHideImages ] = useState( false );
	const [ hideBots, setHideBots ] = useState( false );
	const [ clearingLog, setClearingLog ] = useState( false );

	// Modal state
	const [ redirectModalEntry, setRedirectModalEntry ] = useState( null );
	const [ exporting, setExporting ] = useState( false );
	const [ showExportMenu, setShowExportMenu ] = useState( false );
	const [ showIgnoreManager, setShowIgnoreManager ] = useState( false );
	const [ showIgnored, setShowIgnored ] = useState( false );
	const [ ignoredCount, setIgnoredCount ] = useState( 0 );
	const [ ignoringEntry, setIgnoringEntry ] = useState( null );

	// Fetch 404 log
	const fetchLog = useCallback( async () => {
		setLogLoading( true );
		try {
			const params = new URLSearchParams( {
				sort: logSort,
				per_page: logPerPage,
				page: logPage,
				hide_spam: hideSpam ? '1' : '0',
				hide_images: hideImages ? '1' : '0',
				hide_bots: hideBots ? '1' : '0',
				hide_ignored: showIgnored ? '0' : '1',
			} );
			const response = await apiFetch( {
				path: `/saman-seo/v1/404-log?${ params }`,
			} );
			if ( response.success ) {
				setLogEntries( response.data.items );
				setLogTotal( response.data.total );
				setLogTotalPages( response.data.total_pages );
				setBotCount( response.data.bot_count || 0 );
				setIgnoredCount( response.data.ignored_count || 0 );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch 404 log:', error );
		} finally {
			setLogLoading( false );
		}
	}, [
		logSort,
		logPerPage,
		logPage,
		hideSpam,
		hideImages,
		hideBots,
		showIgnored,
	] );

	// Load data on mount
	useEffect( () => {
		fetchLog();
	}, [ fetchLog ] );

	// Create redirect from 404 entry - open modal
	const handleCreateFrom404 = ( entry ) => {
		setRedirectModalEntry( entry );
	};

	// Handle successful redirect creation
	const handleRedirectCreated = ( data ) => {
		setRedirectModalEntry( null );
		// Remove the entry from the list if it was deleted
		if ( data.entry_deleted ) {
			setLogEntries( ( prev ) =>
				prev.filter( ( e ) => e.id !== data.redirect.id )
			);
			setLogTotal( ( prev ) => Math.max( 0, prev - 1 ) );
		} else {
			// Mark the entry as having a redirect
			setLogEntries( ( prev ) =>
				prev.map( ( e ) =>
					e.request_uri === data.redirect.source
						? {
								...e,
								redirect_exists: true,
						  }
						: e
				)
			);
		}
		// Refresh the list to get accurate data
		fetchLog();
	};

	// Clear 404 log
	const handleClearLog = async () => {
		if (
			! window.confirm(
				__(
					'Are you sure you want to clear the entire 404 log? This cannot be undone.',
					'saman-seo'
				)
			)
		) {
			return;
		}
		setClearingLog( true );
		try {
			await apiFetch( {
				path: '/saman-seo/v1/404-log',
				method: 'DELETE',
			} );
			setLogEntries( [] );
			setLogTotal( 0 );
			setLogTotalPages( 1 );
			setLogPage( 1 );
		} catch ( error ) {
			console.error( 'Failed to clear log:', error );
		} finally {
			setClearingLog( false );
		}
	};

	// Close export menu when clicking outside
	useEffect( () => {
		if ( ! showExportMenu ) {
			return;
		}
		const handleClickOutside = ( event ) => {
			const wrapper = document.querySelector( '.log-export-dropdown' );
			if ( wrapper && ! wrapper.contains( event.target ) ) {
				setShowExportMenu( false );
			}
		};
		document.addEventListener( 'mousedown', handleClickOutside );
		return () =>
			document.removeEventListener( 'mousedown', handleClickOutside );
	}, [ showExportMenu ] );

	// Export 404 log
	const handleExport = async ( format ) => {
		setExporting( true );
		setShowExportMenu( false );
		try {
			const params = new URLSearchParams( {
				format,
				hide_spam: hideSpam ? '1' : '0',
				hide_images: hideImages ? '1' : '0',
				hide_bots: hideBots ? '1' : '0',
			} );
			const response = await apiFetch( {
				path: `/saman-seo/v1/404-log/export?${ params }`,
			} );
			if ( response.success && response.data.content ) {
				// Trigger download
				const blob = new Blob( [ response.data.content ], {
					type: format === 'csv' ? 'text/csv' : 'application/json',
				} );
				const url = URL.createObjectURL( blob );
				const a = document.createElement( 'a' );
				a.href = url;
				a.download = response.data.filename;
				document.body.appendChild( a );
				a.click();
				document.body.removeChild( a );
				URL.revokeObjectURL( url );
			}
		} catch ( error ) {
			console.error( 'Failed to export:', error );
		} finally {
			setExporting( false );
		}
	};

	// Ignore 404 entry
	const handleIgnoreEntry = async ( entry ) => {
		setIgnoringEntry( entry.id );
		try {
			await apiFetch( {
				path: `/saman-seo/v1/404-log/${ entry.id }/ignore`,
				method: 'POST',
			} );
			// Update the entry in the list
			setLogEntries( ( prev ) =>
				prev.map( ( e ) =>
					e.id === entry.id
						? {
								...e,
								is_ignored: true,
						  }
						: e
				)
			);
			setIgnoredCount( ( prev ) => prev + 1 );
			// If we're hiding ignored, remove from view
			if ( ! showIgnored ) {
				setLogEntries( ( prev ) =>
					prev.filter( ( e ) => e.id !== entry.id )
				);
				setLogTotal( ( prev ) => Math.max( 0, prev - 1 ) );
			}
		} catch ( error ) {
			console.error( 'Failed to ignore entry:', error );
		} finally {
			setIgnoringEntry( null );
		}
	};

	// Unignore 404 entry
	const handleUnignoreEntry = async ( entry ) => {
		setIgnoringEntry( entry.id );
		try {
			await apiFetch( {
				path: `/saman-seo/v1/404-log/${ entry.id }/ignore`,
				method: 'DELETE',
			} );
			// Update the entry in the list
			setLogEntries( ( prev ) =>
				prev.map( ( e ) =>
					e.id === entry.id
						? {
								...e,
								is_ignored: false,
						  }
						: e
				)
			);
			setIgnoredCount( ( prev ) => Math.max( 0, prev - 1 ) );
		} catch ( error ) {
			console.error( 'Failed to unignore entry:', error );
		} finally {
			setIgnoringEntry( null );
		}
	};

	// Format date
	const formatDate = ( dateStr ) => {
		if ( ! dateStr || dateStr === '0000-00-00 00:00:00' ) return '-';
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
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( '404 Log', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Monitor broken links and create redirects to fix them.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<button
						type="button"
						className="button"
						onClick={ () => setShowIgnoreManager( true ) }
					>
						{ __( 'Manage Ignore Patterns', 'saman-seo' ) }
					</button>
					<div
						className={ `dropdown log-export-dropdown ${
							showExportMenu ? 'is-open' : ''
						}` }
					>
						<button
							type="button"
							className="button dropdown__trigger"
							onClick={ () =>
								setShowExportMenu( ! showExportMenu )
							}
							disabled={ exporting || logEntries.length === 0 }
							aria-expanded={ showExportMenu }
							aria-haspopup="true"
						>
							{ exporting
								? __( 'Exporting\u2026', 'saman-seo' )
								: __( 'Export', 'saman-seo' ) }
							<svg
								className="dropdown__chevron"
								viewBox="0 0 20 20"
								fill="currentColor"
								width="16"
								height="16"
							>
								<path
									fillRule="evenodd"
									d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
									clipRule="evenodd"
								/>
							</svg>
						</button>
						<div className="dropdown-menu">
							<button
								type="button"
								className="dropdown-menu__item"
								onClick={ () => handleExport( 'csv' ) }
							>
								{ __( 'Export as CSV', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="dropdown-menu__item"
								onClick={ () => handleExport( 'json' ) }
							>
								{ __( 'Export as JSON', 'saman-seo' ) }
							</button>
						</div>
					</div>
					<button
						type="button"
						className="button ghost"
						onClick={ handleClearLog }
						disabled={ clearingLog || logEntries.length === 0 }
					>
						{ clearingLog
							? __( 'Clearing\u2026', 'saman-seo' )
							: __( 'Clear Log', 'saman-seo' ) }
					</button>
				</div>
			</div>

			<section className="page-body">
				{ /* Summary + Filters */ }
				<div className="page-toolbar">
					<div className="page-summary">
						<div className="stat-list">
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ logTotal.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Total entries', 'saman-seo' ) }
								</span>
							</div>
							<div className="stat-list__item">
								<span className="stat-list__value">
									{
										logEntries.filter(
											( e ) => ! e.redirect_exists
										).length
									}
								</span>
								<span className="stat-list__label">
									{ __( 'Need redirect', 'saman-seo' ) }
								</span>
							</div>
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ botCount.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Bot hits', 'saman-seo' ) }
								</span>
							</div>
							<div className="stat-list__item">
								<span className="stat-list__value">
									{ ignoredCount.toLocaleString() }
								</span>
								<span className="stat-list__label">
									{ __( 'Ignored', 'saman-seo' ) }
								</span>
							</div>
						</div>
					</div>
					<div className="page-toolbar__filters">
						<label className="filter-field">
							<span>{ __( 'Sort by', 'saman-seo' ) }</span>
							<select
								value={ logSort }
								onChange={ ( e ) => {
									setLogSort( e.target.value );
									setLogPage( 1 );
								} }
							>
								{ SORT_OPTIONS.map( ( opt ) => (
									<option
										key={ opt.value }
										value={ opt.value }
									>
										{ opt.label }
									</option>
								) ) }
							</select>
						</label>
						<label className="filter-field">
							<span>{ __( 'Rows', 'saman-seo' ) }</span>
							<select
								value={ logPerPage }
								onChange={ ( e ) => {
									setLogPerPage(
										parseInt( e.target.value, 10 )
									);
									setLogPage( 1 );
								} }
							>
								{ PER_PAGE_OPTIONS.map( ( opt ) => (
									<option key={ opt } value={ opt }>
										{ opt }
									</option>
								) ) }
							</select>
						</label>
						<label className="filter-checkbox">
							<input
								type="checkbox"
								checked={ hideSpam }
								onChange={ ( e ) => {
									setHideSpam( e.target.checked );
									setLogPage( 1 );
								} }
							/>
							<span>{ __( 'Hide spam', 'saman-seo' ) }</span>
						</label>
						<label className="filter-checkbox">
							<input
								type="checkbox"
								checked={ hideImages }
								onChange={ ( e ) => {
									setHideImages( e.target.checked );
									setLogPage( 1 );
								} }
							/>
							<span>{ __( 'Hide images', 'saman-seo' ) }</span>
						</label>
						<label className="filter-checkbox">
							<input
								type="checkbox"
								checked={ hideBots }
								onChange={ ( e ) => {
									setHideBots( e.target.checked );
									setLogPage( 1 );
								} }
							/>
							<span>{ __( 'Hide bots', 'saman-seo' ) }</span>
						</label>
						<label className="filter-checkbox">
							<input
								type="checkbox"
								checked={ showIgnored }
								onChange={ ( e ) => {
									setShowIgnored( e.target.checked );
									setLogPage( 1 );
								} }
							/>
							<span>{ __( 'Show ignored', 'saman-seo' ) }</span>
						</label>
					</div>
				</div>

				{ /* 404 Log Table */ }
				{ logLoading ? (
					<div className="loading-state">
						{ __( 'Loading 404 log\u2026', 'saman-seo' ) }
					</div>
				) : logEntries.length === 0 ? (
					<div className="empty-state">
						<div className="empty-state__icon">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="1.5"
								width="48"
								height="48"
							>
								<circle cx="12" cy="12" r="10" />
								<path d="M9 9l6 6m0-6l-6 6" />
							</svg>
						</div>
						<h3>{ __( 'No 404 errors logged', 'saman-seo' ) }</h3>
						<p>
							{ __(
								"Great news! Your site doesn't have any broken links recorded yet.",
								'saman-seo'
							) }
						</p>
					</div>
				) : (
					<>
						<div className="table-wrap">
							<table className="data-table data-table--compact">
								<thead>
									<tr>
										<th className="col-url">
											{ __( 'Request URL', 'saman-seo' ) }
										</th>
										<th className="col-numeric">
											{ __( 'Hits', 'saman-seo' ) }
										</th>
										<th className="col-date">
											{ __( 'Last seen', 'saman-seo' ) }
										</th>
										<th className="col-device">
											{ __( 'Device', 'saman-seo' ) }
										</th>
										<th className="col-actions">
											{ __( 'Action', 'saman-seo' ) }
										</th>
									</tr>
								</thead>
								<tbody>
									{ logEntries.map( ( entry ) => (
										<tr
											key={ entry.id }
											className={ `${
												entry.is_bot ? 'is-bot-row' : ''
											} ${
												entry.is_ignored
													? 'is-ignored-row'
													: ''
											}` }
										>
											<td className="url-cell">
												<span
													className="url-cell__path"
													title={ entry.request_uri }
												>
													<code>
														{ entry.request_uri }
													</code>
												</span>
												<span className="url-cell__badges">
													{ entry.redirect_exists && (
														<span className="badge success">
															{ __(
																'Redirect exists',
																'saman-seo'
															) }
														</span>
													) }
													{ __(
														'Bot/crawler request',
														'saman-seo'
													) }
													{ __(
														'This URL is ignored',
														'saman-seo'
													) }
												</span>
											</td>
											<td className="numeric-cell">
												<span
													className={ `hits-badge ${
														entry.hits > 10
															? 'high'
															: entry.hits > 5
															? 'medium'
															: ''
													}` }
												>
													{ entry.hits }
												</span>
											</td>
											<td className="date-cell">
												{ formatDate(
													entry.last_seen
												) }
											</td>
											<td
												className="device-cell"
												title={ entry.device_label }
											>
												{ entry.device_label }
											</td>
											<td className="action-cell">
												<div className="table-actions">
													{ __(
														'Create redirect',
														'saman-seo'
													) }
													{ __(
														'Ignore this URL',
														'saman-seo'
													) }
												</div>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						</div>

						{ /* Pagination */ }
						{ logTotalPages > 1 && (
							<div className="pagination">
								<span className="pagination-info">
									{ logTotal.toLocaleString() }{ ' ' }
									{ logTotal === 1
										? __( 'item', 'saman-seo' )
										: __( 'items', 'saman-seo' ) }
								</span>
								<div className="pagination-links">
									<button
										type="button"
										className="pagination-btn"
										disabled={ logPage <= 1 }
										onClick={ () =>
											setLogPage( logPage - 1 )
										}
									>
										{ __( '\u2039 Previous', 'saman-seo' ) }
									</button>
									<span className="pagination-current">
										{ logPage } { __( 'of', 'saman-seo' ) }{ ' ' }
										{ logTotalPages }
									</span>
									<button
										type="button"
										className="pagination-btn"
										disabled={ logPage >= logTotalPages }
										onClick={ () =>
											setLogPage( logPage + 1 )
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

			{ /* Create Redirect Modal */ }
			{ redirectModalEntry && (
				<CreateRedirectModal
					entry={ redirectModalEntry }
					onClose={ () => setRedirectModalEntry( null ) }
					onSuccess={ handleRedirectCreated }
				/>
			) }

			{ /* Ignore Pattern Manager Modal */ }
			{ showIgnoreManager && (
				<IgnorePatternManager
					onClose={ () => setShowIgnoreManager( false ) }
					onPatternChange={ () => fetchLog() }
				/>
			) }
		</div>
	);
};
export default Log404;
