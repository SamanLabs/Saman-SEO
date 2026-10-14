/**
 * Image SEO Page
 *
 * Bulk alt text editor and missing alt report.
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
const ImageSeo = () => {
	const [ images, setImages ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ saving, setSaving ] = useState( {} );
	const [ filter, setFilter ] = useState( 'all' );
	const [ search, setSearch ] = useState( '' );
	const [ stats, setStats ] = useState( {
		total: 0,
		withAlt: 0,
		missingAlt: 0,
		emptyAlt: 0,
	} );
	const [ page, setPage ] = useState( 1 );
	const [ totalPages, setTotalPages ] = useState( 1 );
	const [ editingId, setEditingId ] = useState( null );
	const [ editValue, setEditValue ] = useState( '' );
	const [ message, setMessage ] = useState( null );
	const perPage = 20;
	useEffect( () => {
		fetchImages();
	}, [ filter, page ] );
	const fetchImages = async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: `saman-seo/v1/images?filter=${ filter }&page=${ page }&per_page=${ perPage }&search=${ encodeURIComponent(
					search
				) }`,
			} );
			setImages( response.images || [] );
			setStats( response.stats || {} );
			setTotalPages( response.total_pages || 1 );
		} catch ( error ) {
			console.error( 'Failed to fetch images:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to load images', 'saman-seo' ),
			} );
		}
		setLoading( false );
	};
	const handleSearch = ( e ) => {
		e.preventDefault();
		setPage( 1 );
		fetchImages();
	};
	const handleSaveAlt = async ( imageId, newAlt ) => {
		setSaving( {
			...saving,
			[ imageId ]: true,
		} );
		try {
			await apiFetch( {
				path: `saman-seo/v1/images/${ imageId }`,
				method: 'POST',
				data: {
					alt: newAlt,
				},
			} );
			setImages(
				images.map( ( img ) =>
					img.id === imageId
						? {
								...img,
								alt: newAlt,
						  }
						: img
				)
			);
			setEditingId( null );
			setMessage( {
				type: 'success',
				text: __( 'Alt text updated successfully', 'saman-seo' ),
			} );

			// Update stats
			const wasEmpty =
				images.find( ( img ) => img.id === imageId )?.alt === '';
			if ( wasEmpty && newAlt ) {
				setStats( {
					...stats,
					withAlt: stats.withAlt + 1,
					missingAlt: stats.missingAlt - 1,
				} );
			} else if ( ! wasEmpty && ! newAlt ) {
				setStats( {
					...stats,
					withAlt: stats.withAlt - 1,
					missingAlt: stats.missingAlt + 1,
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to save alt text:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to save alt text', 'saman-seo' ),
			} );
		}
		setSaving( {
			...saving,
			[ imageId ]: false,
		} );
	};
	const handleGenerateAlt = async ( imageId ) => {
		setSaving( {
			...saving,
			[ imageId ]: true,
		} );
		try {
			const response = await apiFetch( {
				path: `saman-seo/v1/images/${ imageId }/generate-alt`,
				method: 'POST',
			} );
			if ( response.alt ) {
				setImages(
					images.map( ( img ) =>
						img.id === imageId
							? {
									...img,
									alt: response.alt,
							  }
							: img
					)
				);
				setMessage( {
					type: 'success',
					text: __( 'Alt text generated from filename', 'saman-seo' ),
				} );
			}
		} catch ( error ) {
			console.error( 'Failed to generate alt text:', error );
			setMessage( {
				type: 'error',
				text: __( 'Failed to generate alt text', 'saman-seo' ),
			} );
		}
		setSaving( {
			...saving,
			[ imageId ]: false,
		} );
	};
	const startEditing = ( image ) => {
		setEditingId( image.id );
		setEditValue( image.alt || '' );
	};
	const cancelEditing = () => {
		setEditingId( null );
		setEditValue( '' );
	};
	const getFilteredCount = () => {
		switch ( filter ) {
			case 'missing':
				return stats.missingAlt;
			case 'has-alt':
				return stats.withAlt;
			default:
				return stats.total;
		}
	};
	useEffect( () => {
		if ( message ) {
			const timer = setTimeout( () => setMessage( null ), 3000 );
			return () => clearTimeout( timer );
		}
	}, [ message ] );
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Image SEO', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Manage alt text for all images in your media library.',
							'saman-seo'
						) }
					</p>
				</div>
			</div>

			{ /* Stats Cards */ }
			<div className="image-seo-stats">
				<div className="stat-card">
					<div className="stat-card__icon stat-card__icon--blue">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<rect x="3" y="3" width="18" height="18" rx="2" />
							<circle cx="8.5" cy="8.5" r="1.5" />
							<path d="M21 15l-5-5L5 21" />
						</svg>
					</div>
					<div className="stat-card__content">
						<span className="stat-card__value">
							{ stats.total }
						</span>
						<span className="stat-card__label">
							{ __( 'Total Images', 'saman-seo' ) }
						</span>
					</div>
				</div>

				<div className="stat-card">
					<div className="stat-card__icon stat-card__icon--green">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M9 12l2 2 4-4" />
							<circle cx="12" cy="12" r="10" />
						</svg>
					</div>
					<div className="stat-card__content">
						<span className="stat-card__value">
							{ stats.withAlt }
						</span>
						<span className="stat-card__label">
							{ __( 'With Alt Text', 'saman-seo' ) }
						</span>
					</div>
				</div>

				<div className="stat-card">
					<div className="stat-card__icon stat-card__icon--red">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<circle cx="12" cy="12" r="10" />
							<path d="M12 8v4m0 4h.01" />
						</svg>
					</div>
					<div className="stat-card__content">
						<span className="stat-card__value">
							{ stats.missingAlt }
						</span>
						<span className="stat-card__label">
							{ __( 'Missing Alt', 'saman-seo' ) }
						</span>
					</div>
				</div>

				<div className="stat-card">
					<div className="stat-card__icon stat-card__icon--purple">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
						>
							<path d="M3 3v18h18" />
							<path d="M18 9l-5 5-4-4-3 3" />
						</svg>
					</div>
					<div className="stat-card__content">
						<span className="stat-card__value">
							{ stats.total > 0
								? Math.round(
										( stats.withAlt / stats.total ) * 100
								  )
								: 0 }
							%
						</span>
						<span className="stat-card__label">
							{ __( 'Coverage', 'saman-seo' ) }
						</span>
					</div>
				</div>
			</div>

			{ /* Message Toast */ }
			{ message && (
				<div
					className={ `alert-banner alert-banner--${ message.type }` }
				>
					{ message.text }
				</div>
			) }

			{ /* Filters and Search */ }
			<div className="card">
				<div className="image-seo-toolbar">
					<div className="image-seo-filters">
						<button
							type="button"
							className={ `sub-tab ${
								filter === 'all' ? 'is-active' : ''
							}` }
							onClick={ () => {
								setFilter( 'all' );
								setPage( 1 );
							} }
						>
							{ __( 'All (', 'saman-seo' ) }
							{ stats.total })
						</button>
						<button
							type="button"
							className={ `sub-tab ${
								filter === 'missing' ? 'is-active' : ''
							}` }
							onClick={ () => {
								setFilter( 'missing' );
								setPage( 1 );
							} }
						>
							{ __( 'Missing Alt (', 'saman-seo' ) }
							{ stats.missingAlt })
						</button>
						<button
							type="button"
							className={ `sub-tab ${
								filter === 'has-alt' ? 'is-active' : ''
							}` }
							onClick={ () => {
								setFilter( 'has-alt' );
								setPage( 1 );
							} }
						>
							{ __( 'Has Alt (', 'saman-seo' ) }
							{ stats.withAlt })
						</button>
					</div>

					<form
						onSubmit={ handleSearch }
						className="image-seo-search"
					>
						<input
							type="text"
							placeholder={ __(
								'Search by filename\u2026',
								'saman-seo'
							) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
						/>
						<button type="submit">
							{ __( 'Search', 'saman-seo' ) }
						</button>
					</form>
				</div>

				{ /* Images Table */ }
				{ loading ? (
					<div className="loading-state">
						<div className="spinner"></div>
						<p>{ __( 'Loading images\u2026', 'saman-seo' ) }</p>
					</div>
				) : images.length === 0 ? (
					<div className="empty-state">
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="1.5"
						>
							<rect x="3" y="3" width="18" height="18" rx="2" />
							<circle cx="8.5" cy="8.5" r="1.5" />
							<path d="M21 15l-5-5L5 21" />
						</svg>
						<h3>{ __( 'No images found', 'saman-seo' ) }</h3>
						<p>
							{ filter === 'missing'
								? __(
										'All your images have alt text. Great job!',
										'saman-seo'
								  )
								: __(
										'No images match your current filters.',
										'saman-seo'
								  ) }
						</p>
					</div>
				) : (
					<table className="data-table">
						<thead>
							<tr>
								<th
									style={ {
										width: '80px',
									} }
								>
									{ __( 'Preview', 'saman-seo' ) }
								</th>
								<th>{ __( 'Filename', 'saman-seo' ) }</th>
								<th>{ __( 'Alt Text', 'saman-seo' ) }</th>
								<th
									style={ {
										width: '150px',
									} }
								>
									{ __( 'Actions', 'saman-seo' ) }
								</th>
							</tr>
						</thead>
						<tbody>
							{ images.map( ( image ) => (
								<tr
									key={ image.id }
									className={
										! image.alt ? 'row--warning' : ''
									}
								>
									<td>
										<div className="image-preview">
											<img
												src={
													image.thumbnail || image.url
												}
												alt={ image.alt || '' }
											/>
										</div>
									</td>
									<td>
										<div className="image-filename">
											<span className="filename">
												{ image.filename }
											</span>
											<span className="dimensions">
												{ image.width }x{ image.height }
											</span>
										</div>
									</td>
									<td>
										{ editingId === image.id ? (
											<div className="inline-edit">
												<input
													type="text"
													value={ editValue }
													onChange={ ( e ) =>
														setEditValue(
															e.target.value
														)
													}
													placeholder={ __(
														'Enter alt text\u2026',
														'saman-seo'
													) }
													autoFocus
												/>
												<div className="inline-edit-actions">
													<button
														type="button"
														className="button primary small"
														onClick={ () =>
															handleSaveAlt(
																image.id,
																editValue
															)
														}
														disabled={
															saving[ image.id ]
														}
													>
														{ saving[ image.id ]
															? __(
																	'Saving\u2026',
																	'saman-seo'
															  )
															: __(
																	'Save',
																	'saman-seo'
															  ) }
													</button>
													<button
														type="button"
														className="button ghost small"
														onClick={
															cancelEditing
														}
													>
														{ __(
															'Cancel',
															'saman-seo'
														) }
													</button>
												</div>
											</div>
										) : (
											<div className="alt-text-display">
												{ image.alt ? (
													<span className="alt-text">
														{ image.alt }
													</span>
												) : (
													<span className="alt-text alt-text--missing">
														{ __(
															'No alt text',
															'saman-seo'
														) }
													</span>
												) }
											</div>
										) }
									</td>
									<td>
										<div className="action-buttons">
											<button
												type="button"
												className="button ghost small"
												onClick={ () =>
													startEditing( image )
												}
												title={ __(
													'Edit alt text',
													'saman-seo'
												) }
											>
												<svg
													viewBox="0 0 24 24"
													fill="none"
													stroke="currentColor"
													strokeWidth="2"
													width="14"
													height="14"
												>
													<path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
													<path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
												</svg>
												{ __( 'Edit', 'saman-seo' ) }
											</button>
											{ ! image.alt && (
												<button
													type="button"
													className="button ghost small"
													onClick={ () =>
														handleGenerateAlt(
															image.id
														)
													}
													disabled={
														saving[ image.id ]
													}
													title={ __(
														'Generate from filename',
														'saman-seo'
													) }
												>
													<svg
														viewBox="0 0 24 24"
														fill="none"
														stroke="currentColor"
														strokeWidth="2"
														width="14"
														height="14"
													>
														<path d="M12 2a2 2 0 012 2c0 .74-.4 1.39-1 1.73V7h1a7 7 0 017 7h1v3h-1v1a2 2 0 01-2 2H5a2 2 0 01-2-2v-1H2v-3h1a7 7 0 017-7h1V5.73A2 2 0 0112 2z" />
													</svg>
													{ __(
														'Auto',
														'saman-seo'
													) }
												</button>
											) }
										</div>
									</td>
								</tr>
							) ) }
						</tbody>
					</table>
				) }

				{ /* Pagination */ }
				{ totalPages > 1 && (
					<div className="pagination">
						<button
							type="button"
							className="button ghost small"
							onClick={ () =>
								setPage( ( p ) => Math.max( 1, p - 1 ) )
							}
							disabled={ page === 1 }
						>
							{ __( 'Previous', 'saman-seo' ) }
						</button>
						<span className="pagination-info">
							{ __( 'Page', 'saman-seo' ) } { page }{ ' ' }
							{ __( 'of', 'saman-seo' ) } { totalPages }
						</span>
						<button
							type="button"
							className="button ghost small"
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
				) }
			</div>

			{ /* Tips Card */ }
			<div className="card">
				<h3>{ __( 'Image SEO Tips', 'saman-seo' ) }</h3>
				<ul className="tips-list">
					<li>
						<strong>
							{ __( 'Be descriptive:', 'saman-seo' ) }
						</strong>{ ' ' }
						{ __(
							'Alt text should describe the image content accurately and concisely.',
							'saman-seo'
						) }
					</li>
					<li>
						<strong>
							{ __( 'Include keywords naturally:', 'saman-seo' ) }
						</strong>{ ' ' }
						{ __(
							'If relevant, include your target keyword but avoid stuffing.',
							'saman-seo'
						) }
					</li>
					<li>
						<strong>{ __( 'Keep it short:', 'saman-seo' ) }</strong>{ ' ' }
						{ __(
							'Aim for 125 characters or less for optimal accessibility.',
							'saman-seo'
						) }
					</li>
					<li>
						<strong>
							{ __( 'Skip decorative images:', 'saman-seo' ) }
						</strong>{ ' ' }
						{ __(
							'Images that are purely decorative can have empty alt attributes.',
							'saman-seo'
						) }
					</li>
				</ul>
			</div>
		</div>
	);
};
export default ImageSeo;
