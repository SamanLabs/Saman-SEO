import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
const BulkEditor = ( { onNavigate } ) => {
	const [ posts, setPosts ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( false );
	const [ generating, setGenerating ] = useState( null );
	const [ postType, setPostType ] = useState( 'post' );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ changes, setChanges ] = useState( {} );
	const [ filter, setFilter ] = useState( 'all' );
	const [ searchQuery, setSearchQuery ] = useState( '' );
	const [ selectedPosts, setSelectedPosts ] = useState( [] );
	const perPage = 20;
	const fetchPosts = useCallback( async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/tools/bulk-editor/posts?post_type=${ postType }&page=${ page }&per_page=${ perPage }&filter=${ filter }&search=${ encodeURIComponent(
					searchQuery
				) }`,
			} );
			if ( response.success ) {
				setPosts( response.data.posts );
				setTotalPages( response.data.total_pages );
			}
		} catch ( error ) {
			console.error( 'Failed to fetch posts:', error );
		} finally {
			setLoading( false );
		}
	}, [ postType, page, filter, searchQuery ] );
	useEffect( () => {
		fetchPosts();
	}, [ fetchPosts ] );
	const handleFieldChange = ( postId, field, value ) => {
		setChanges( ( prev ) => ( {
			...prev,
			[ postId ]: {
				...prev[ postId ],
				[ field ]: value,
			},
		} ) );
	};
	const getFieldValue = ( post, field ) => {
		if ( changes[ post.id ] && changes[ post.id ][ field ] !== undefined ) {
			return changes[ post.id ][ field ];
		}
		return post[ field ] || '';
	};
	const hasChanges = Object.keys( changes ).length > 0;
	const handleSaveAll = async () => {
		if ( ! hasChanges ) return;
		setSaving( true );
		try {
			const changesArray = Object.entries( changes ).map(
				( [ postId, data ] ) => ( {
					post_id: parseInt( postId ),
					...data,
				} )
			);
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/bulk-editor/save',
				method: 'POST',
				data: {
					changes: changesArray,
				},
			} );
			if ( response.success ) {
				setChanges( {} );
				fetchPosts();
			}
		} catch ( error ) {
			console.error( 'Failed to save:', error );
		} finally {
			setSaving( false );
		}
	};
	const handleGenerateSuggestions = async ( postIds = null ) => {
		const idsToGenerate = postIds || selectedPosts;
		if ( idsToGenerate.length === 0 ) return;
		setGenerating( idsToGenerate );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/bulk-editor/generate',
				method: 'POST',
				data: {
					post_ids: idsToGenerate,
				},
			} );
			if ( response.success && response.data.suggestions ) {
				const newChanges = {
					...changes,
				};
				response.data.suggestions.forEach( ( suggestion ) => {
					newChanges[ suggestion.post_id ] = {
						...newChanges[ suggestion.post_id ],
						seo_title: suggestion.title,
						seo_description: suggestion.description,
					};
				} );
				setChanges( newChanges );
			}
		} catch ( error ) {
			console.error( 'Failed to generate:', error );
		} finally {
			setGenerating( null );
		}
	};
	const handleSelectAll = () => {
		if ( selectedPosts.length === posts.length ) {
			setSelectedPosts( [] );
		} else {
			setSelectedPosts( posts.map( ( p ) => p.id ) );
		}
	};
	const handleSelectPost = ( postId ) => {
		setSelectedPosts( ( prev ) =>
			prev.includes( postId )
				? prev.filter( ( id ) => id !== postId )
				: [ ...prev, postId ]
		);
	};
	const getCharCountClass = ( length, type ) => {
		if ( type === 'title' ) {
			if ( length === 0 ) return 'empty';
			if ( length < 30 ) return 'short';
			if ( length > 60 ) return 'long';
			return 'good';
		} else {
			if ( length === 0 ) return 'empty';
			if ( length < 70 ) return 'short';
			if ( length > 160 ) return 'long';
			return 'good';
		}
	};
	return (
		<div className="page bulk-editor-page">
			<div className="page-header">
				<div>
					<div className="page-header__breadcrumb">
						<button
							type="button"
							className="breadcrumb-link"
							onClick={ () => onNavigate( 'tools' ) }
						>
							{ __( 'Tools', 'saman-seo' ) }
						</button>
						<span className="breadcrumb-separator">/</span>
						<span>{ __( 'Bulk Editor', 'saman-seo' ) }</span>
					</div>
					<h1>{ __( 'Smart Bulk Editor', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Edit SEO titles and descriptions in bulk with AI-powered suggestions.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="page-header__actions">
					{ selectedPosts.length > 0 && (
						<button
							type="button"
							className="button button--secondary"
							onClick={ () => handleGenerateSuggestions() }
							disabled={ generating }
						>
							{ generating ? (
								<>
									<span className="spinner"></span>
									{ __( 'Generating\u2026', 'saman-seo' ) }
								</>
							) : (
								<>
									<svg
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
										width="16"
										height="16"
									>
										<path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z" />
									</svg>
									{ __( 'Generate AI (', 'saman-seo' ) }
									{ selectedPosts.length })
								</>
							) }
						</button>
					) }
					<button
						type="button"
						className="button button--primary"
						onClick={ handleSaveAll }
						disabled={ ! hasChanges || saving }
					>
						{ saving ? (
							<>
								<span className="spinner"></span>
								{ __( 'Saving\u2026', 'saman-seo' ) }
							</>
						) : (
							<>
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="16"
									height="16"
								>
									<path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z" />
									<polyline points="17 21 17 13 7 13 7 21" />
									<polyline points="7 3 7 8 15 8" />
								</svg>
								{ __( 'Save Changes', 'saman-seo' ) }{ ' ' }
								{ hasChanges &&
									`(${ Object.keys( changes ).length })` }
							</>
						) }
					</button>
				</div>
			</div>

			<div className="bulk-editor-controls">
				<div className="bulk-editor-filters">
					<select
						value={ postType }
						onChange={ ( e ) => {
							setPostType( e.target.value );
							setPage( 1 );
						} }
						className="bulk-editor-select"
					>
						<option value="post">
							{ __( 'Posts', 'saman-seo' ) }
						</option>
						<option value="page">
							{ __( 'Pages', 'saman-seo' ) }
						</option>
					</select>

					<select
						value={ filter }
						onChange={ ( e ) => {
							setFilter( e.target.value );
							setPage( 1 );
						} }
						className="bulk-editor-select"
					>
						<option value="all">
							{ __( 'All', 'saman-seo' ) }
						</option>
						<option value="missing_title">
							{ __( 'Missing SEO Title', 'saman-seo' ) }
						</option>
						<option value="missing_description">
							{ __( 'Missing Description', 'saman-seo' ) }
						</option>
						<option value="missing_both">
							{ __( 'Missing Both', 'saman-seo' ) }
						</option>
					</select>

					<div className="bulk-editor-search">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="16"
							height="16"
						>
							<circle cx="11" cy="11" r="8" />
							<path d="M21 21l-4.35-4.35" />
						</svg>
						<input
							type="text"
							placeholder={ __(
								'Search posts\u2026',
								'saman-seo'
							) }
							value={ searchQuery }
							onChange={ ( e ) => {
								setSearchQuery( e.target.value );
								setPage( 1 );
							} }
						/>
					</div>
				</div>

				<div className="bulk-editor-stats">
					{ selectedPosts.length > 0 && (
						<span className="selected-count">
							{ selectedPosts.length }{ ' ' }
							{ __( 'selected', 'saman-seo' ) }
						</span>
					) }
				</div>
			</div>

			<div className="bulk-editor-table-wrapper">
				{ loading ? (
					<div className="bulk-editor-loading">
						<span className="spinner"></span>
						{ __( 'Loading posts\u2026', 'saman-seo' ) }
					</div>
				) : posts.length === 0 ? (
					<div className="bulk-editor-empty">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="48"
							height="48"
						>
							<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
							<polyline points="14 2 14 8 20 8" />
						</svg>
						<p>
							{ __(
								'No posts found matching your criteria.',
								'saman-seo'
							) }
						</p>
					</div>
				) : (
					<table className="bulk-editor-table">
						<thead>
							<tr>
								<th className="col-checkbox">
									<input
										type="checkbox"
										checked={
											selectedPosts.length ===
											posts.length
										}
										onChange={ handleSelectAll }
									/>
								</th>
								<th className="col-title">
									{ __( 'Post Title', 'saman-seo' ) }
								</th>
								<th className="col-seo-title">
									{ __( 'SEO Title', 'saman-seo' ) }
								</th>
								<th className="col-seo-desc">
									{ __( 'Meta Description', 'saman-seo' ) }
								</th>
								<th className="col-actions">
									{ __( 'Actions', 'saman-seo' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ __( 'Generate AI suggestions', 'saman-seo' ) }
						</tbody>
					</table>
				) }
			</div>

			{ totalPages > 1 && (
				<div className="bulk-editor-pagination">
					<button
						type="button"
						className="pagination-button"
						onClick={ () =>
							setPage( ( p ) => Math.max( 1, p - 1 ) )
						}
						disabled={ page === 1 }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="16"
							height="16"
						>
							<path d="M15 18l-6-6 6-6" />
						</svg>
						{ __( 'Previous', 'saman-seo' ) }
					</button>
					<span className="pagination-info">
						{ __( 'Page', 'saman-seo' ) } { page }{ ' ' }
						{ __( 'of', 'saman-seo' ) } { totalPages }
					</span>
					<button
						type="button"
						className="pagination-button"
						onClick={ () =>
							setPage( ( p ) => Math.min( totalPages, p + 1 ) )
						}
						disabled={ page === totalPages }
					>
						{ __( 'Next', 'saman-seo' ) }
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="16"
							height="16"
						>
							<path d="M9 18l6-6-6-6" />
						</svg>
					</button>
				</div>
			) }
		</div>
	);
};
export default BulkEditor;
