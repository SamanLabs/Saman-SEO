/**
 * Image SEO Page
 *
 * Bulk alt text editor and missing alt report.
 */

import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { KpiBand, MetricCard } from '../components/Kpi';
import SubTabs from '../components/SubTabs';
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
			// The endpoint wraps its payload in { success, data }.
			const payload = response?.data || response || {};
			setImages( payload.images || [] );
			setStats( payload.stats || {} );
			setTotalPages( payload.total_pages || 1 );
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
			const suggestedAlt = response?.data?.alt || response?.alt;
			if ( suggestedAlt ) {
				setImages(
					images.map( ( img ) =>
						img.id === imageId
							? {
									...img,
									alt: suggestedAlt,
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
	useEffect( () => {
		if ( message ) {
			const timer = setTimeout( () => setMessage( null ), 3000 );
			return () => clearTimeout( timer );
		}
	}, [ message ] );
	const filterTabs = [
		{
			id: 'all',
			label: `${ __( 'All (', 'saman-seo' ) }${ stats.total })`,
		},
		{
			id: 'missing',
			label: `${ __( 'Missing alt (', 'saman-seo' ) }${
				stats.missingAlt
			})`,
		},
		{
			id: 'has-alt',
			label: `${ __( 'Has alt (', 'saman-seo' ) }${ stats.withAlt })`,
		},
	];
	return (
		<div className="page image-seo-page">
			{ loading && ! stats.total ? (
				<KpiBand loading count={ 4 } />
			) : (
				<KpiBand label={ __( 'Image summary', 'saman-seo' ) }>
					<MetricCard
						label={ __( 'Total images', 'saman-seo' ) }
						value={ ( stats.total || 0 ).toLocaleString() }
					/>
					<MetricCard
						label={ __( 'With alt text', 'saman-seo' ) }
						value={ ( stats.withAlt || 0 ).toLocaleString() }
						tone={ 'success' }
					/>
					<MetricCard
						label={ __( 'Missing alt text', 'saman-seo' ) }
						value={ ( stats.missingAlt || 0 ).toLocaleString() }
						tone={ stats.missingAlt > 0 ? 'danger' : 'success' }
					/>
					<MetricCard
						label={ __( 'Coverage', 'saman-seo' ) }
						value={ `${
							stats.total > 0
								? Math.round(
										( stats.withAlt / stats.total ) * 100
								  )
								: 0
						}%` }
					/>
				</KpiBand>
			) }

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

			<SubTabs
				tabs={ filterTabs }
				activeTab={ filter }
				onChange={ ( id ) => {
					setFilter( id );
					setPage( 1 );
				} }
				ariaLabel={ __( 'Filter images', 'saman-seo' ) }
			/>

			<section className="page-body">
				<div className="page-toolbar">
					<form
						onSubmit={ handleSearch }
						className="page-toolbar__filters"
					>
						<input
							type="search"
							className="search-input"
							placeholder={ __(
								'Search by filename\u2026',
								'saman-seo'
							) }
							aria-label={ __(
								'Search by filename',
								'saman-seo'
							) }
							value={ search }
							onChange={ ( e ) => setSearch( e.target.value ) }
						/>
						<button type="submit" className="button small">
							{ __( 'Search', 'saman-seo' ) }
						</button>
					</form>
				</div>

				{ message && (
					<div
						className={ `alert-banner alert-banner--${ message.type }` }
					>
						{ message.text }
					</div>
				) }

				{ loading && (
					<div className="loading-state">
						{ __( 'Loading images\u2026', 'saman-seo' ) }
					</div>
				) }

				{ ! loading && images.length === 0 && (
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
								<rect
									x="3"
									y="3"
									width="18"
									height="18"
									rx="2"
								/>
								<circle cx="8.5" cy="8.5" r="1.5" />
								<path d="M21 15l-5-5L5 21" />
							</svg>
						</div>
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
				) }

				{ ! loading && images.length > 0 && (
					<div className="table-wrap">
						<table className="data-table">
							<thead>
								<tr>
									<th className="col-thumb">
										{ __( 'Preview', 'saman-seo' ) }
									</th>
									<th className="col-filename">
										{ __( 'Filename', 'saman-seo' ) }
									</th>
									<th>{ __( 'Alt text', 'saman-seo' ) }</th>
									<th className="col-actions">
										{ __( 'Actions', 'saman-seo' ) }
									</th>
								</tr>
							</thead>
							<tbody>
								{ images.map( ( image ) => (
									<tr key={ image.id }>
										<td className="col-thumb">
											<div className="image-preview">
												<img
													src={
														image.thumbnail ||
														image.url
													}
													alt={ image.alt || '' }
												/>
											</div>
										</td>
										<td className="col-filename">
											<div className="image-filename">
												<span className="filename">
													{ image.filename }
												</span>
												<span className="dimensions">
													{ image.width }x
													{ image.height }
												</span>
											</div>
										</td>
										<td>
											{ editingId === image.id ? (
												<div className="inline-edit">
													<input
														type="text"
														aria-label={ __(
															'Alt text',
															'saman-seo'
														) }
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
																saving[
																	image.id
																]
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
														<span className="pill danger">
															{ __(
																'No alt text',
																'saman-seo'
															) }
														</span>
													) }
												</div>
											) }
										</td>
										<td className="action-cell">
											<div className="table-actions">
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
													{ __(
														'Edit',
														'saman-seo'
													) }
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

				<section className="panel">
					<h3>{ __( 'Image SEO tips', 'saman-seo' ) }</h3>
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
								{ __(
									'Include keywords naturally:',
									'saman-seo'
								) }
							</strong>{ ' ' }
							{ __(
								'If relevant, include your target keyword but avoid stuffing.',
								'saman-seo'
							) }
						</li>
						<li>
							<strong>
								{ __( 'Keep it short:', 'saman-seo' ) }
							</strong>{ ' ' }
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
				</section>
			</section>
		</div>
	);
};
export default ImageSeo;
