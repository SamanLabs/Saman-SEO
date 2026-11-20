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
				setTotalPages( response.data.pages );
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
	const aiIcon = (
		<svg
			viewBox="0 0 24 24"
			fill="none"
			stroke="currentColor"
			strokeWidth="2"
			width="16"
			height="16"
			aria-hidden="true"
		>
			<path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1a1 1 0 011 1v3a1 1 0 01-1 1h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2a1 1 0 01-1-1v-3a1 1 0 011-1h1a7 7 0 017-7h1V5.73c-.6-.34-1-.99-1-1.73a2 2 0 012-2z" />
		</svg>
	);
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
						<span>{ __( 'Bulk editor', 'saman-seo' ) }</span>
					</div>
					<h1>{ __( 'Smart bulk editor', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Edit SEO titles and descriptions in bulk with AI-powered suggestions.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					{ selectedPosts.length > 0 && (
						<button
							type="button"
							className="button"
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
									{ aiIcon }
									{ __( 'Generate AI (', 'saman-seo' ) }
									{ selectedPosts.length })
								</>
							) }
						</button>
					) }
					<button
						type="button"
						className="button primary"
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
								{ __( 'Save changes', 'saman-seo' ) }{ ' ' }
								{ hasChanges &&
									`(${ Object.keys( changes ).length })` }
							</>
						) }
					</button>
				</div>
			</div>

			<section className="page-body">
				<div className="page-toolbar">
					<div className="page-toolbar__filters">
						<label
							className="filter-field"
							htmlFor="bulk-editor-post-type"
						>
							<span>{ __( 'Post type', 'saman-seo' ) }</span>
							<select
								id="bulk-editor-post-type"
								value={ postType }
								onChange={ ( e ) => {
									setPostType( e.target.value );
									setPage( 1 );
								} }
							>
								<option value="post">
									{ __( 'Posts', 'saman-seo' ) }
								</option>
								<option value="page">
									{ __( 'Pages', 'saman-seo' ) }
								</option>
							</select>
						</label>

						<label
							className="filter-field"
							htmlFor="bulk-editor-filter"
						>
							<span>{ __( 'Show', 'saman-seo' ) }</span>
							<select
								id="bulk-editor-filter"
								value={ filter }
								onChange={ ( e ) => {
									setFilter( e.target.value );
									setPage( 1 );
								} }
							>
								<option value="all">
									{ __( 'All', 'saman-seo' ) }
								</option>
								<option value="missing_title">
									{ __( 'Missing SEO title', 'saman-seo' ) }
								</option>
								<option value="missing_description">
									{ __( 'Missing description', 'saman-seo' ) }
								</option>
								<option value="missing_both">
									{ __( 'Missing both', 'saman-seo' ) }
								</option>
							</select>
						</label>

						<input
							type="search"
							className="search-input"
							placeholder={ __(
								'Search posts\u2026',
								'saman-seo'
							) }
							aria-label={ __( 'Search posts', 'saman-seo' ) }
							value={ searchQuery }
							onChange={ ( e ) => {
								setSearchQuery( e.target.value );
								setPage( 1 );
							} }
						/>

						{ selectedPosts.length > 0 && (
							<span className="selected-count">
								{ selectedPosts.length }{ ' ' }
								{ __( 'selected', 'saman-seo' ) }
							</span>
						) }
					</div>
				</div>

				{ loading && (
					<div className="loading-state">
						{ __( 'Loading posts\u2026', 'saman-seo' ) }
					</div>
				) }

				{ ! loading && posts.length === 0 && (
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
								<path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z" />
								<polyline points="14 2 14 8 20 8" />
							</svg>
						</div>
						<p>
							{ __(
								'No posts found matching your criteria.',
								'saman-seo'
							) }
						</p>
					</div>
				) }

				{ ! loading && posts.length > 0 && (
					<div className="table-wrap">
						<table className="data-table">
							<thead>
								<tr>
									<th className="checkbox-col">
										<input
											type="checkbox"
											aria-label={ __(
												'Select all posts',
												'saman-seo'
											) }
											checked={
												selectedPosts.length ===
												posts.length
											}
											onChange={ handleSelectAll }
										/>
									</th>
									<th className="col-title">
										{ __( 'Post title', 'saman-seo' ) }
									</th>
									<th className="col-seo-title">
										{ __( 'SEO title', 'saman-seo' ) }
									</th>
									<th className="col-seo-desc">
										{ __(
											'Meta description',
											'saman-seo'
										) }
									</th>
									<th className="col-actions">
										{ __( 'Actions', 'saman-seo' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ posts.map( ( post ) => {
									const seoTitle = getFieldValue(
										post,
										'seo_title'
									);
									const seoDesc = getFieldValue(
										post,
										'seo_description'
									);
									const isGenerating =
										generating &&
										generating.includes( post.id );
									const hasPostChanges =
										!! changes[ post.id ];
									return (
										<tr
											key={ post.id }
											className={
												hasPostChanges
													? 'row-modified'
													: ''
											}
										>
											<td className="checkbox-col">
												<input
													type="checkbox"
													aria-label={ post.title }
													checked={ selectedPosts.includes(
														post.id
													) }
													onChange={ () =>
														handleSelectPost(
															post.id
														)
													}
												/>
											</td>
											<td className="col-title">
												<div className="post-info">
													<a
														href={
															post.edit_url ||
															post.edit_link
														}
														target="_blank"
														rel="noopener noreferrer"
														className="post-title-link"
													>
														{ post.title }
													</a>
													{ post.post_type && (
														<span className="post-type-badge">
															{ post.post_type }
														</span>
													) }
												</div>
											</td>
											<td className="col-seo-title">
												<div className="field-wrapper">
													<input
														type="text"
														aria-label={ __(
															'SEO title',
															'saman-seo'
														) }
														value={ seoTitle }
														onChange={ ( e ) =>
															handleFieldChange(
																post.id,
																'seo_title',
																e.target.value
															)
														}
														placeholder={
															post.title
														}
														className={
															isGenerating
																? 'generating'
																: ''
														}
														disabled={
															isGenerating
														}
													/>
													<span
														className={ `char-count ${ getCharCountClass(
															seoTitle.length,
															'title'
														) }` }
													>
														{ seoTitle.length }/60
													</span>
												</div>
											</td>
											<td className="col-seo-desc">
												<div className="field-wrapper">
													<textarea
														aria-label={ __(
															'Meta description',
															'saman-seo'
														) }
														value={ seoDesc }
														onChange={ ( e ) =>
															handleFieldChange(
																post.id,
																'seo_description',
																e.target.value
															)
														}
														placeholder={ __(
															'Enter meta description\u2026',
															'saman-seo'
														) }
														rows={ 2 }
														className={
															isGenerating
																? 'generating'
																: ''
														}
														disabled={
															isGenerating
														}
													/>
													<span
														className={ `char-count ${ getCharCountClass(
															seoDesc.length,
															'desc'
														) }` }
													>
														{ seoDesc.length }/160
													</span>
												</div>
											</td>
											<td className="action-cell">
												<div className="table-actions">
													<button
														type="button"
														className="button ghost small"
														onClick={ () =>
															handleGenerateSuggestions(
																[ post.id ]
															)
														}
														disabled={
															isGenerating
														}
														title={ __(
															'Generate AI suggestions',
															'saman-seo'
														) }
														aria-label={ __(
															'Generate AI suggestions',
															'saman-seo'
														) }
													>
														{ isGenerating ? (
															<span className="spinner-small"></span>
														) : (
															aiIcon
														) }
													</button>
												</div>
											</td>
										</tr>
									);
								} ) }
							</tbody>
						</table>
					</div>
				) }

				{ totalPages > 1 && (
					<div className="pagination">
						<span className="pagination-info">
							{ __( 'Page', 'saman-seo' ) } { page }{ ' ' }
							{ __( 'of', 'saman-seo' ) } { totalPages }
						</span>
						<div className="pagination-links">
							<button
								type="button"
								className="pagination-btn"
								onClick={ () =>
									setPage( ( p ) => Math.max( 1, p - 1 ) )
								}
								disabled={ page === 1 }
							>
								{ __( 'Previous', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="pagination-btn"
								onClick={ () =>
									setPage( ( p ) =>
										Math.min( totalPages, p + 1 )
									)
								}
								disabled={ page === totalPages }
							>
								{ __( 'Next', 'saman-seo' ) }
							</button>
						</div>
					</div>
				) }
			</section>
		</div>
	);
};
export default BulkEditor;
