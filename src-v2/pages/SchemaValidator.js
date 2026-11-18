/**
 * Schema Validator Page
 *
 * Test and validate structured data on any URL.
 */

import { useState, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { icons as sharedIcons } from '../components/Icons';
import { KpiBand, MetricCard } from '../components/Kpi';

// Map schema types to shared icon components.
const schemaIconMap = {
	Article: 'fileText',
	Product: 'shoppingBag',
	Organization: 'building',
	LocalBusiness: 'mapPin',
	WebSite: 'globe',
	BreadcrumbList: 'chevronsRight',
	FAQPage: 'helpCircle',
	HowTo: 'checklist',
	VideoObject: 'video',
};

const SchemaValidator = () => {
	const [ url, setUrl ] = useState( '' );
	const [ loading, setLoading ] = useState( false );
	const [ results, setResults ] = useState( null );
	const [ error, setError ] = useState( null );
	const [ expandedSchema, setExpandedSchema ] = useState( null );

	// Validate URL
	const handleValidate = useCallback( async () => {
		if ( ! url.trim() ) {
			setError( __( 'Please enter a URL to validate', 'saman-seo' ) );
			return;
		}
		setLoading( true );
		setError( null );
		setResults( null );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/schema-validator/validate',
				method: 'POST',
				data: {
					url: url.trim(),
				},
			} );
			if ( response.success ) {
				setResults( response.data );
			} else {
				setError(
					response.message ||
						__( 'Failed to validate schema', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError( err.message || __( 'Failed to fetch URL', 'saman-seo' ) );
		} finally {
			setLoading( false );
		}
	}, [ url ] );

	// Validate current site URL
	const handleValidateSite = useCallback( async () => {
		const siteUrl =
			window.samanSeoV2Settings?.adminUrl?.replace( '/wp-admin/', '' ) ||
			'';
		if ( siteUrl ) {
			setUrl( siteUrl );
			// Trigger validation after state update
			setTimeout( () => {
				document.getElementById( 'validate-btn' )?.click();
			}, 100 );
		}
	}, [] );

	// Get schema type icon
	const getSchemaIcon = ( type ) => {
		const IconComponent =
			sharedIcons[ schemaIconMap[ type ] ] || sharedIcons.fileText;
		return <IconComponent />;
	};

	// Get validation status badge
	const StatusBadge = ( { status, count } ) => {
		const config = {
			valid: {
				label: __( 'Valid', 'saman-seo' ),
				className: 'success',
			},
			warnings: {
				label: sprintf(
					/* translators: %1$s: placeholder, %2$s: placeholder */ __(
						'%1$s Warning%2$s',
						'saman-seo'
					),
					count,
					count !== 1 ? 's' : ''
				),
				className: 'warning',
			},
			errors: {
				label: sprintf(
					/* translators: %1$s: placeholder, %2$s: placeholder */ __(
						'%1$s Error%2$s',
						'saman-seo'
					),
					count,
					count !== 1 ? 's' : ''
				),
				className: 'danger',
			},
		};
		const c = config[ status ] || config.valid;
		return <span className={ `pill ${ c.className }` }>{ c.label }</span>;
	};

	// The band is always on the page so the frame does not jump when a
	// result arrives; before the first run its tiles show a dash.
	const schemaCount = results?.schemas?.length || 0;
	const dash = '\u2013';
	return (
		<div className="page schema-validator-page">
			{ ( loading || results ) &&
				( loading ? (
					<KpiBand loading count={ 4 } />
				) : (
					<KpiBand label={ __( 'Validation summary', 'saman-seo' ) }>
						<MetricCard
							label={ __( 'Schemas found', 'saman-seo' ) }
							value={ results ? schemaCount : dash }
						/>
						<MetricCard
							label={ __( 'Valid', 'saman-seo' ) }
							value={ results ? results.valid_count || 0 : dash }
							tone={
								results?.valid_count > 0 ? 'success' : undefined
							}
						/>
						<MetricCard
							label={ __( 'Warnings', 'saman-seo' ) }
							value={
								results ? results.warning_count || 0 : dash
							}
							tone={
								results?.warning_count > 0
									? 'warning'
									: undefined
							}
						/>
						<MetricCard
							label={ __( 'Errors', 'saman-seo' ) }
							value={ results ? results.error_count || 0 : dash }
							tone={
								results?.error_count > 0 ? 'danger' : undefined
							}
						/>
					</KpiBand>
				) ) }

			<div className="page-header">
				<div>
					<h1>{ __( 'Schema validator', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Test and validate structured data (JSON-LD) on any URL.',
							'saman-seo'
						) }
					</p>
				</div>
			</div>

			{ /* URL Input */ }
			<section className="panel">
				<h3>{ __( 'URL to validate', 'saman-seo' ) }</h3>
				<p className="panel-desc">
					{ __(
						'Enter any URL to check its structured data markup',
						'saman-seo'
					) }
				</p>
				<div className="input-group">
					<input
						type="url"
						className="tool-input"
						aria-label={ __( 'URL to validate', 'saman-seo' ) }
						placeholder={ __(
							'Enter URL to validate (e.g., https://example.com/page)',
							'saman-seo'
						) }
						value={ url }
						onChange={ ( e ) => setUrl( e.target.value ) }
						onKeyDown={ ( e ) =>
							e.key === 'Enter' && handleValidate()
						}
					/>
					<button
						type="button"
						className="button"
						onClick={ handleValidateSite }
					>
						{ __( 'Test homepage', 'saman-seo' ) }
					</button>
					<button
						id="validate-btn"
						type="button"
						className="button primary"
						onClick={ handleValidate }
						disabled={ loading }
					>
						{ loading ? (
							<>
								<span className="spinner-small" />
								{ __( 'Validating\u2026', 'saman-seo' ) }
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
									<circle cx="11" cy="11" r="8" />
									<path d="M21 21l-4.35-4.35" />
								</svg>
								{ __( 'Validate', 'saman-seo' ) }
							</>
						) }
					</button>
				</div>
			</section>

			{ /* Error */ }
			{ error && (
				<div className="alert-banner alert-banner--error">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
						width="20"
						height="20"
					>
						<circle cx="12" cy="12" r="10" />
						<path d="M12 8v4m0 4h.01" />
					</svg>
					<span>{ error }</span>
				</div>
			) }

			{ /* No Schemas */ }
			{ results && schemaCount === 0 && (
				<div className="empty-state">
					<svg
						viewBox="0 0 24 24"
						fill="none"
						stroke="currentColor"
						strokeWidth="2"
					>
						<path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16" />
					</svg>
					<h3>{ __( 'No structured data found', 'saman-seo' ) }</h3>
					<p>
						{ __(
							"This page doesn't contain any JSON-LD structured data markup.",
							'saman-seo'
						) }
					</p>
				</div>
			) }

			{ /* Schema List */ }
			{ results && schemaCount > 0 && (
				<section className="improvements-group">
					<h2 className="improvements-group__title">
						{ __( 'Validation results', 'saman-seo' ) }
					</h2>
					<p className="group-note">
						<a
							href={ results.url }
							target="_blank"
							rel="noopener noreferrer"
						>
							{ results.url }
						</a>
					</p>
					<ul className="improvements-list">
						{ results.schemas.map( ( schema, index ) => {
							const isOpen = expandedSchema === index;
							const errorCount = schema.errors?.length || 0;
							const warningCount = schema.warnings?.length || 0;
							return (
								<li key={ index } className="schema-result">
									<button
										type="button"
										className="improvement-row schema-result__head"
										aria-expanded={ isOpen }
										onClick={ () =>
											setExpandedSchema(
												isOpen ? null : index
											)
										}
									>
										<span
											className="schema-result__icon"
											aria-hidden="true"
										>
											{ getSchemaIcon(
												schema.types?.[ 0 ] ||
													schema.type
											) }
										</span>
										<span className="schema-result__main">
											<span className="schema-result__title">
												<span className="schema-result__type">
													{ schema.type }
												</span>
												{ errorCount > 0 && (
													<StatusBadge
														status="errors"
														count={ errorCount }
													/>
												) }
												{ errorCount === 0 &&
													warningCount > 0 && (
														<StatusBadge
															status="warnings"
															count={
																warningCount
															}
														/>
													) }
												{ errorCount === 0 &&
													warningCount === 0 && (
														<StatusBadge status="valid" />
													) }
												{ schema.notices?.length >
													0 && (
													<span className="pill">
														{ __(
															'No Google rich result',
															'saman-seo'
														) }
													</span>
												) }
											</span>
											{ schema.name && (
												<span className="schema-result__name">
													{ schema.name }
												</span>
											) }
										</span>
										<svg
											className={ `schema-result__toggle${
												isOpen ? ' is-open' : ''
											}` }
											viewBox="0 0 24 24"
											fill="none"
											stroke="currentColor"
											strokeWidth="2"
											aria-hidden="true"
										>
											<path d="M6 9l6 6 6-6" />
										</svg>
									</button>

									{ isOpen && (
										<div className="schema-result__body">
											{ /* Errors */ }
											{ errorCount > 0 && (
												<div>
													<h4>
														{ __(
															'Errors',
															'saman-seo'
														) }
													</h4>
													<ul className="note-list is-danger">
														{ schema.errors.map(
															( err, i ) => (
																<li key={ i }>
																	{ err }
																</li>
															)
														) }
													</ul>
												</div>
											) }

											{ /* Warnings */ }
											{ warningCount > 0 && (
												<div>
													<h4>
														{ __(
															'Warnings',
															'saman-seo'
														) }
													</h4>
													<ul className="note-list is-warning">
														{ schema.warnings.map(
															( warn, i ) => (
																<li key={ i }>
																	{ warn }
																</li>
															)
														) }
													</ul>
												</div>
											) }

											{ /* Info notices */ }
											{ schema.notices?.length > 0 && (
												<div>
													<h4>
														{ __(
															'Info',
															'saman-seo'
														) }
													</h4>
													<ul className="note-list">
														{ schema.notices.map(
															( notice, i ) => (
																<li key={ i }>
																	{ notice }
																</li>
															)
														) }
													</ul>
												</div>
											) }

											{ /* Properties */ }
											<div>
												<h4>
													{ __(
														'Properties',
														'saman-seo'
													) }
												</h4>
												<div className="schema-props">
													{ Object.entries(
														schema.properties || {}
													).map(
														( [ key, value ] ) => (
															<div
																key={ key }
																className="schema-props__row"
															>
																<span className="schema-props__key">
																	{ key }
																</span>
																<span className="schema-props__value">
																	{ typeof value ===
																	'object'
																		? JSON.stringify(
																				value,
																				null,
																				2
																		  )
																		: String(
																				value
																		  ).substring(
																				0,
																				200
																		  ) }
																</span>
															</div>
														)
													) }
												</div>
											</div>

											{ /* Raw JSON */ }
											<details>
												<summary>
													{ __(
														'View raw JSON-LD',
														'saman-seo'
													) }
												</summary>
												<pre className="code-block">
													{ JSON.stringify(
														schema.raw,
														null,
														2
													) }
												</pre>
											</details>
										</div>
									) }
								</li>
							);
						} ) }
					</ul>
				</section>
			) }

			{ /* Tips */ }
			{ results && (
				<section className="panel">
					<h3>{ __( 'Testing tips', 'saman-seo' ) }</h3>
					<ul className="note-list">
						<li>
							{ __( 'Use', 'saman-seo' ) }{ ' ' }
							<a
								href="https://search.google.com/test/rich-results"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __(
									'Google Rich Results Test',
									'saman-seo'
								) }
							</a>{ ' ' }
							{ __( 'for official validation', 'saman-seo' ) }
						</li>
						<li>
							{ __( 'Use', 'saman-seo' ) }{ ' ' }
							<a
								href="https://validator.schema.org/"
								target="_blank"
								rel="noopener noreferrer"
							>
								{ __( 'Schema.org validator', 'saman-seo' ) }
							</a>{ ' ' }
							{ __(
								'for detailed schema checking',
								'saman-seo'
							) }
						</li>
						<li>
							{ __(
								'Common issues: missing required properties, incorrect data types, invalid URLs',
								'saman-seo'
							) }
						</li>
						<li>
							{ __(
								'Info notices mark markup that is valid schema.org but no longer produces a Google rich result (for example FAQ and HowTo). It is safe to keep.',
								'saman-seo'
							) }
						</li>
					</ul>
				</section>
			) }
		</div>
	);
};
export default SchemaValidator;
