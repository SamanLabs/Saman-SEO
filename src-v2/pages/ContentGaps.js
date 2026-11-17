import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import { KpiBand, MetricCard } from '../components/Kpi';
// The analysis endpoint returns gaps as { title, reason, priority, keywords }
// and clusters as { name, topics }. These helpers map that onto the field
// names the page renders, and tolerate the older names too.
const normalizeResults = ( data = {} ) => ( {
	...data,
	gaps: ( data.gaps || [] ).map( ( gap ) => ( {
		...gap,
		topic: gap.topic || gap.title,
	} ) ),
	clusters: ( data.clusters || [] ).map( ( cluster ) => ( {
		...cluster,
		missing_subtopics: cluster.missing_subtopics || cluster.topics || [],
	} ) ),
} );

// The outline endpoint returns { title, meta_description, sections: [ {
// heading, subheadings, key_points } ] }.
const normalizeOutline = ( topic, data = {} ) => ( {
	topic,
	...data,
	suggested_title: data.suggested_title || data.title,
	outline:
		data.outline ||
		( data.sections || [] ).map( ( section ) => ( {
			level: 'h2',
			heading: section.heading,
			points: [
				...( section.subheadings || [] ),
				...( section.key_points || [] ),
			],
		} ) ),
} );

const ContentGaps = ( { onNavigate } ) => {
	const [ analyzing, setAnalyzing ] = useState( false );
	const [ results, setResults ] = useState( null );
	const [ generatingOutline, setGeneratingOutline ] = useState( null );
	const [ outline, setOutline ] = useState( null );
	const [ topic, setTopic ] = useState( '' );
	const [ selectedCategory, setSelectedCategory ] = useState( '' );
	const [ categories, setCategories ] = useState( [] );
	useEffect( () => {
		fetchCategories();
	}, [] );
	const fetchCategories = async () => {
		try {
			const response = await apiFetch( {
				path: '/wp/v2/categories?per_page=100',
			} );
			setCategories( response );
		} catch ( error ) {
			console.error( 'Failed to fetch categories:', error );
		}
	};
	const handleAnalyze = async () => {
		setAnalyzing( true );
		setResults( null );
		setOutline( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/content-gaps/analyze',
				method: 'POST',
				data: {
					topic: topic || null,
					category: selectedCategory || null,
				},
			} );
			if ( response.success ) {
				setResults( normalizeResults( response.data ) );
			}
		} catch ( error ) {
			console.error( 'Failed to analyze:', error );
		} finally {
			setAnalyzing( false );
		}
	};
	const handleGenerateOutline = async ( gapTopic, keywords = [] ) => {
		setGeneratingOutline( gapTopic );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/tools/content-gaps/outline',
				method: 'POST',
				data: {
					title: gapTopic,
					keywords,
				},
			} );
			if ( response.success ) {
				setOutline( normalizeOutline( gapTopic, response.data ) );
			}
		} catch ( error ) {
			console.error( 'Failed to generate outline:', error );
		} finally {
			setGeneratingOutline( null );
		}
	};
	const getPriorityTone = ( priority ) => {
		switch ( priority ) {
			case 'high':
				return 'danger';
			case 'medium':
				return 'warning';
			case 'low':
				return 'success';
			default:
				return '';
		}
	};
	const gaps = results?.gaps || [];
	const clusters = results?.clusters || [];
	const renderBand = () => {
		if ( analyzing ) {
			return <KpiBand loading count={ 4 } />;
		}
		if ( ! results ) {
			return null;
		}
		const highPriority = gaps.filter(
			( gap ) => gap.priority === 'high'
		).length;
		return (
			<KpiBand label={ __( 'Content gap summary', 'saman-seo' ) }>
				<MetricCard
					label={ __( 'Posts analyzed', 'saman-seo' ) }
					value={ results.posts_analyzed ?? '\u2013' }
				/>
				<MetricCard
					label={ __( 'Gaps found', 'saman-seo' ) }
					value={ gaps.length }
				/>
				<MetricCard
					label={ __( 'High priority gaps', 'saman-seo' ) }
					value={ highPriority }
					tone={ highPriority > 0 ? 'danger' : 'success' }
				/>
				<MetricCard
					label={ __( 'Content clusters', 'saman-seo' ) }
					value={ clusters.length }
				/>
			</KpiBand>
		);
	};
	return (
		<div className="page content-gaps-page">
			{ renderBand() }

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
						<span>
							{ __( 'Content gaps finder', 'saman-seo' ) }
						</span>
					</div>
					<h1>{ __( 'Content gaps finder', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Discover missing topics and content opportunities based on your existing content.',
							'saman-seo'
						) }
					</p>
				</div>
				<div className="header-actions">
					<button
						type="button"
						className="button primary"
						onClick={ handleAnalyze }
						disabled={ analyzing }
					>
						{ analyzing ? (
							<>
								<span className="spinner"></span>
								{ __( 'Analyzing\u2026', 'saman-seo' ) }
							</>
						) : (
							__( 'Find content gaps', 'saman-seo' )
						) }
					</button>
				</div>
			</div>

			<section className="page-body">
				<div className="page-toolbar">
					<div className="page-toolbar__filters">
						<label
							className="filter-field"
							htmlFor="content-gaps-topic"
						>
							<span>
								{ __( 'Focus Topic (optional)', 'saman-seo' ) }
							</span>
							<input
								id="content-gaps-topic"
								type="text"
								value={ topic }
								onChange={ ( e ) => setTopic( e.target.value ) }
								placeholder={ __(
									'e.g., coffee brewing, SEO, cooking\u2026',
									'saman-seo'
								) }
							/>
						</label>

						<label
							className="filter-field"
							htmlFor="content-gaps-category"
						>
							<span>
								{ __( 'Category filter', 'saman-seo' ) }
							</span>
							<select
								id="content-gaps-category"
								value={ selectedCategory }
								onChange={ ( e ) =>
									setSelectedCategory( e.target.value )
								}
							>
								<option value="">
									{ __( 'All categories', 'saman-seo' ) }
								</option>
								{ categories.map( ( cat ) => (
									<option key={ cat.id } value={ cat.id }>
										{ cat.name } ({ cat.count })
									</option>
								) ) }
							</select>
						</label>
					</div>
				</div>

				{ analyzing && (
					<div className="loading-state">
						<p>
							{ __(
								'Analyzing your content\u2026',
								'saman-seo'
							) }
						</p>
						<span className="muted">
							{ __(
								'This may take a moment as we review your posts.',
								'saman-seo'
							) }
						</span>
					</div>
				) }

				{ results &&
					! analyzing &&
					results.existing_topics?.length > 0 && (
						<section className="panel">
							<h3>
								{ __( 'Your current topics', 'saman-seo' ) }
							</h3>
							<div className="tag-list">
								{ results.existing_topics.map(
									( existingTopic, idx ) => (
										<span key={ idx } className="pill">
											{ existingTopic }
										</span>
									)
								) }
							</div>
						</section>
					) }

				{ results && ! analyzing && gaps.length > 0 && (
					<section className="improvements-group">
						<div className="section-head">
							<h2 className="improvements-group__title">
								{ __( 'Content gaps', 'saman-seo' ) }
							</h2>
							<p className="section-desc">
								{ __(
									'Topics you should consider writing about:',
									'saman-seo'
								) }
							</p>
						</div>
						<ul className="improvements-list">
							{ gaps.map( ( gap, idx ) => {
								// The analysis may name the gap `topic` or `title`.
								const gapTopic = gap.topic || gap.title;
								const tone = getPriorityTone( gap.priority );
								return (
									<li key={ idx } className="improvement-row">
										<div className="improvement-row__main">
											<div className="improvement-row__title">
												<h3>{ gapTopic }</h3>
												{ gap.priority && (
													<span
														className={ `pill gap-priority ${ tone }`.trim() }
													>
														{ gap.priority }
													</span>
												) }
											</div>
											<p className="improvement-row__desc">
												{ gap.reason }
											</p>
											{ gap.search_volume && (
												<dl className="improvement-row__metrics">
													<div>
														<dd>
															~
															{
																gap.search_volume
															}
														</dd>
														<dt>
															{ __(
																'/mo',
																'saman-seo'
															) }
														</dt>
													</div>
												</dl>
											) }
										</div>
										<button
											type="button"
											className="button small"
											onClick={ () =>
												handleGenerateOutline(
													gapTopic,
													gap.keywords || []
												)
											}
											disabled={
												generatingOutline === gapTopic
											}
										>
											{ generatingOutline === gapTopic ? (
												<>
													<span className="spinner-small"></span>
													{ __(
														'Generating\u2026',
														'saman-seo'
													) }
												</>
											) : (
												__(
													'Generate outline',
													'saman-seo'
												)
											) }
										</button>
									</li>
								);
							} ) }
						</ul>
					</section>
				) }

				{ results && ! analyzing && clusters.length > 0 && (
					<section className="improvements-group">
						<div className="section-head">
							<h2 className="improvements-group__title">
								{ __( 'Content clusters', 'saman-seo' ) }
							</h2>
							<p className="section-desc">
								{ __(
									'Group related content for better internal linking:',
									'saman-seo'
								) }
							</p>
						</div>
						<ul className="improvements-list">
							{ clusters.map( ( cluster, idx ) => (
								<li key={ idx } className="improvement-row">
									<div className="improvement-row__main">
										<div className="improvement-row__title">
											<h3>{ cluster.name }</h3>
										</div>
										{ cluster.missing_subtopics?.length >
											0 && (
											<div className="cluster-missing">
												<span className="missing-label">
													{ __(
														'Missing subtopics:',
														'saman-seo'
													) }
												</span>
												<div className="tag-list">
													{ cluster.missing_subtopics.map(
														( sub, subIdx ) => (
															<span
																key={ subIdx }
																className="pill warning"
															>
																{ sub }
															</span>
														)
													) }
												</div>
											</div>
										) }
									</div>
									<span className="improvement-row__impact">
										{ cluster.missing_subtopics?.length ||
											0 }{ ' ' }
										{ __( 'topics', 'saman-seo' ) }
									</span>
								</li>
							) ) }
						</ul>
					</section>
				) }

				{ ! results && ! analyzing && (
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
								<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
								<rect x="9" y="3" width="6" height="4" rx="1" />
								<path d="M12 12v4M10 14h4" />
							</svg>
						</div>
						<h3>
							{ __(
								'Find your content opportunities',
								'saman-seo'
							) }
						</h3>
						<p>
							{ __(
								'Enter a focus topic or select a category, then click "Find content gaps" to discover what topics you should be writing about based on your existing content.',
								'saman-seo'
							) }
						</p>
					</div>
				) }
			</section>

			{ outline && (
				<div
					className="modal-overlay"
					onClick={ () => setOutline( null ) }
				>
					<div
						className="modal modal-wide"
						role="dialog"
						aria-modal="true"
						aria-labelledby="content-gaps-outline-title"
						onClick={ ( e ) => e.stopPropagation() }
					>
						<div className="modal-header">
							<h2 id="content-gaps-outline-title">
								{ __( 'Content outline:', 'saman-seo' ) }{ ' ' }
								{ outline.topic }
							</h2>
							<button
								type="button"
								className="modal-close"
								aria-label={ __( 'Close', 'saman-seo' ) }
								onClick={ () => setOutline( null ) }
							>
								<svg
									viewBox="0 0 24 24"
									fill="none"
									stroke="currentColor"
									strokeWidth="2"
									width="20"
									height="20"
									aria-hidden="true"
								>
									<line x1="18" y1="6" x2="6" y2="18" />
									<line x1="6" y1="6" x2="18" y2="18" />
								</svg>
							</button>
						</div>
						<div className="modal-body outline-body">
							{ outline.suggested_title && (
								<div className="outline-section">
									<h4>
										{ __( 'Suggested title', 'saman-seo' ) }
									</h4>
									<p className="suggested-title">
										{ outline.suggested_title }
									</p>
								</div>
							) }
							{ outline.meta_description && (
								<div className="outline-section">
									<h4>
										{ __(
											'Meta description',
											'saman-seo'
										) }
									</h4>
									<p>{ outline.meta_description }</p>
								</div>
							) }
							{ outline.outline?.length > 0 && (
								<div className="outline-section">
									<h4>
										{ __(
											'Content structure',
											'saman-seo'
										) }
									</h4>
									<ul className="outline-list">
										{ outline.outline.map(
											( item, idx ) => (
												<li
													key={ idx }
													className={ `outline-item outline-item--${
														item.level || 'h2'
													}` }
												>
													<span className="outline-heading">
														{ item.heading }
													</span>
													{ item.points?.length >
														0 && (
														<ul className="outline-points">
															{ item.points.map(
																(
																	point,
																	pIdx
																) => (
																	<li
																		key={
																			pIdx
																		}
																	>
																		{
																			point
																		}
																	</li>
																)
															) }
														</ul>
													) }
												</li>
											)
										) }
									</ul>
								</div>
							) }
							{ outline.target_keywords?.length > 0 && (
								<div className="outline-section">
									<h4>
										{ __( 'Target keywords', 'saman-seo' ) }
									</h4>
									<div className="tag-list">
										{ outline.target_keywords.map(
											( kw, idx ) => (
												<span
													key={ idx }
													className="pill"
												>
													{ kw }
												</span>
											)
										) }
									</div>
								</div>
							) }
						</div>
						<div className="modal-footer">
							<button
								type="button"
								className="button"
								onClick={ () => {
									const text = `# ${
										outline.suggested_title || outline.topic
									}\n\n${
										outline.outline
											?.map(
												( item ) =>
													`## ${ item.heading }\n${
														item.points
															?.map(
																( p ) =>
																	`- ${ p }`
															)
															.join( '\n' ) || ''
													}`
											)
											.join( '\n\n' ) || ''
									}`;
									navigator.clipboard.writeText( text );
								} }
							>
								{ __( 'Copy outline', 'saman-seo' ) }
							</button>
							<button
								type="button"
								className="button primary"
								onClick={ () => setOutline( null ) }
							>
								{ __( 'Done', 'saman-seo' ) }
							</button>
						</div>
					</div>
				</div>
			) }
		</div>
	);
};
export default ContentGaps;
