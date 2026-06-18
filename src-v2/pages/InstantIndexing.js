/**
 * Instant Indexing Page
 *
 * Bulk indexing management via IndexNow protocol.
 */

import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
const InstantIndexing = ( { onNavigate } ) => {
	// State
	const [ settings, setSettings ] = useState( null );
	const [ posts, setPosts ] = useState( [] );
	const [ stats, setStats ] = useState( null );
	const [ logs, setLogs ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ submitting, setSubmitting ] = useState( false );
	const [ selectedPosts, setSelectedPosts ] = useState( [] );
	const [ activeTab, setActiveTab ] = useState( 'posts' );

	// Filters
	const [ postType, setPostType ] = useState( 'post' );
	const [ search, setSearch ] = useState( '' );
	const [ statusFilter, setStatusFilter ] = useState( '' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );

	// Post types
	const [ postTypes, setPostTypes ] = useState( [] );

	// Fetch settings and options
	useEffect( () => {
		const fetchInitial = async () => {
			try {
				const [ settingsRes, optionsRes, statsRes ] = await Promise.all(
					[
						apiFetch( {
							path: '/saman-seo/v1/indexnow/settings',
						} ),
						apiFetch( {
							path: '/saman-seo/v1/indexnow/options',
						} ),
						apiFetch( {
							path: '/saman-seo/v1/indexnow/stats',
						} ),
					]
				);
				if ( settingsRes.success ) {
					setSettings( settingsRes.data );
				}
				if ( optionsRes.success ) {
					setPostTypes( optionsRes.data.post_types || [] );
				}
				if ( statsRes.success ) {
					setStats( statsRes.data );
				}
			} catch ( err ) {
				console.error( 'Failed to fetch settings:', err );
			}
		};
		fetchInitial();
	}, [] );

	// Fetch posts
	useEffect( () => {
		const fetchPosts = async () => {
			setLoading( true );
			try {
				const response = await apiFetch( {
					path: `/saman-seo/v1/indexnow/posts?post_type=${ postType }&page=${ page }&per_page=20&search=${ search }&status_filter=${ statusFilter }`,
				} );
				if ( response.success ) {
					setPosts( response.data.posts || [] );
					setTotalPages( response.data.pages || 1 );
					if ( response.data.stats ) {
						setStats( ( prev ) => ( {
							...prev,
							...response.data.stats,
						} ) );
					}
				}
			} catch ( err ) {
				console.error( 'Failed to fetch posts:', err );
			} finally {
				setLoading( false );
			}
		};
		fetchPosts();
	}, [ postType, page, search, statusFilter ] );

	// Fetch logs
	useEffect( () => {
		if ( activeTab !== 'logs' ) return;
		const fetchLogs = async () => {
			try {
				const response = await apiFetch( {
					path: '/saman-seo/v1/indexnow/logs?per_page=50',
				} );
				if ( response.success ) {
					setLogs( response.data.items || [] );
				}
			} catch ( err ) {
				console.error( 'Failed to fetch logs:', err );
			}
		};
		fetchLogs();
	}, [ activeTab ] );

	// Handle select all
	const handleSelectAll = useCallback(
		( e ) => {
			if ( e.target.checked ) {
				setSelectedPosts( posts.map( ( p ) => p.id ) );
			} else {
				setSelectedPosts( [] );
			}
		},
		[ posts ]
	);

	// Handle select single
	const handleSelectPost = useCallback( ( postId ) => {
		setSelectedPosts( ( prev ) => {
			if ( prev.includes( postId ) ) {
				return prev.filter( ( id ) => id !== postId );
			}
			return [ ...prev, postId ];
		} );
	}, [] );

	// Handle bulk submit
	const handleBulkSubmit = useCallback( async () => {
		if ( selectedPosts.length === 0 || submitting ) return;
		setSubmitting( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/indexnow/bulk-submit',
				method: 'POST',
				data: {
					post_ids: selectedPosts,
				},
			} );
			if ( response.success ) {
				// Refresh posts and stats
				const [ postsRes, statsRes ] = await Promise.all( [
					apiFetch( {
						path: `/saman-seo/v1/indexnow/posts?post_type=${ postType }&page=${ page }&per_page=20`,
					} ),
					apiFetch( {
						path: '/saman-seo/v1/indexnow/stats',
					} ),
				] );
				if ( postsRes.success ) {
					setPosts( postsRes.data.posts || [] );
				}
				if ( statsRes.success ) {
					setStats( statsRes.data );
				}
				setSelectedPosts( [] );
			}
		} catch ( err ) {
			console.error( 'Failed to submit:', err );
		} finally {
			setSubmitting( false );
		}
	}, [ selectedPosts, submitting, postType, page ] );

	// Handle submit single
	const handleSubmitSingle = useCallback(
		async ( postId ) => {
			try {
				await apiFetch( {
					path: `/saman-seo/v1/indexnow/submit-post/${ postId }`,
					method: 'POST',
				} );

				// Refresh posts
				const response = await apiFetch( {
					path: `/saman-seo/v1/indexnow/posts?post_type=${ postType }&page=${ page }&per_page=20`,
				} );
				if ( response.success ) {
					setPosts( response.data.posts || [] );
				}
			} catch ( err ) {
				console.error( 'Failed to submit:', err );
			}
		},
		[ postType, page ]
	);

	// Status badge component
	const StatusBadge = ( { status } ) => {
		const statusConfig = {
			success: {
				label: __( 'Indexed', 'saman-seo' ),
				className: 'success',
			},
			failed: {
				label: __( 'Failed', 'saman-seo' ),
				className: 'danger',
			},
			never: {
				label: __( 'Not Submitted', 'saman-seo' ),
				className: '',
			},
		};
		const config = statusConfig[ status ] || statusConfig.never;
		return (
			<span className={ `pill ${ config.className }` }>
				{ config.label }
			</span>
		);
	};

	// Not enabled state
	if ( settings && ! settings.enabled ) {
		return (
			<div className="page">
				<div className="page-header">
					<div>
						<h1>{ __( 'Instant Indexing', 'saman-seo' ) }</h1>
						<p>
							{ __(
								'Submit URLs to search engines via IndexNow for faster discovery.',
								'saman-seo'
							) }
						</p>
					</div>
				</div>

				<div className="panel">
					<div className="empty-state">
						<div className="empty-state__icon">
							<svg
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
								width="48"
								height="48"
							>
								<path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" />
							</svg>
						</div>
						<h3>
							{ __( 'IndexNow is not enabled', 'saman-seo' ) }
						</h3>
						<p>
							{ __(
								'Enable IndexNow in Settings to use instant indexing features.',
								'saman-seo'
							) }
						</p>
						<button
							type="button"
							className="button primary"
							onClick={ () =>
								onNavigate && onNavigate( 'settings' )
							}
						>
							{ __( 'Go to Settings', 'saman-seo' ) }
						</button>
					</div>
				</div>
			</div>
		);
	}
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Instant Indexing', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Submit URLs to search engines via IndexNow for faster discovery.',
							'saman-seo'
						) }
					</p>
				</div>
				{ selectedPosts.length > 0 && (
					<button
						type="button"
						className="button primary"
						onClick={ handleBulkSubmit }
						disabled={ submitting }
					>
						{ submitting
							? __( 'Submitting\u2026', 'saman-seo' )
							: sprintf(
									/* translators: %s: placeholder */ __(
										'Submit %s URLs',
										'saman-seo'
									),
									selectedPosts.length
							  ) }
					</button>
				) }
			</div>

			{ /* Stats Cards */ }
			{ stats && (
				<div className="indexing-stats">
					<div className="indexing-stat">
						<div className="indexing-stat__value">
							{ stats.total || 0 }
						</div>
						<div className="indexing-stat__label">
							{ __( 'Total Submissions', 'saman-seo' ) }
						</div>
					</div>
					<div className="indexing-stat indexing-stat--success">
						<div className="indexing-stat__value">
							{ stats.success || 0 }
						</div>
						<div className="indexing-stat__label">
							{ __( 'Successful', 'saman-seo' ) }
						</div>
					</div>
					<div className="indexing-stat indexing-stat--danger">
						<div className="indexing-stat__value">
							{ stats.failed || 0 }
						</div>
						<div className="indexing-stat__label">
							{ __( 'Failed', 'saman-seo' ) }
						</div>
					</div>
					<div className="indexing-stat">
						<div className="indexing-stat__value">
							{ stats.today || 0 }
						</div>
						<div className="indexing-stat__label">
							{ __( 'Today', 'saman-seo' ) }
						</div>
					</div>
				</div>
			) }

			{ /* Tabs */ }
			<div className="sub-tabs">
				<button
					type="button"
					className={ `sub-tab ${
						activeTab === 'posts' ? 'is-active' : ''
					}` }
					onClick={ () => setActiveTab( 'posts' ) }
				>
					{ __( 'Posts', 'saman-seo' ) }
				</button>
				<button
					type="button"
					className={ `sub-tab ${
						activeTab === 'logs' ? 'is-active' : ''
					}` }
					onClick={ () => setActiveTab( 'logs' ) }
				>
					{ __( 'Submission Logs', 'saman-seo' ) }
				</button>
			</div>

			{ /* Posts Tab */ }
			{ __( 'Search posts\u2026', 'saman-seo' ) }

			{ /* Logs Tab */ }
			{ activeTab === 'logs' && (
				<table className="data-table">
					<thead>
						<tr>
							<th>{ __( 'URL', 'saman-seo' ) }</th>
							<th>{ __( 'Status', 'saman-seo' ) }</th>
							<th>{ __( 'Response', 'saman-seo' ) }</th>
							<th>{ __( 'Search Engine', 'saman-seo' ) }</th>
							<th>{ __( 'Submitted', 'saman-seo' ) }</th>
						</tr>
					</thead>
					<tbody>
						{ logs.length === 0 ? (
							<tr>
								<td colSpan="5" className="empty-cell">
									{ __(
										'No submission logs yet.',
										'saman-seo'
									) }
								</td>
							</tr>
						) : (
							logs.map( ( log ) => (
								<tr key={ log.id }>
									<td>
										<a
											href={ log.url }
											target="_blank"
											rel="noopener noreferrer"
											className="truncate-link"
										>
											{ log.url }
										</a>
									</td>
									<td>
										<StatusBadge status={ log.status } />
									</td>
									<td>{ log.response_code || '-' }</td>
									<td>{ log.search_engine }</td>
									<td>{ log.submitted_at }</td>
								</tr>
							) )
						) }
					</tbody>
				</table>
			) }
		</div>
	);
};
export default InstantIndexing;
