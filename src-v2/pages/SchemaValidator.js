/**
 * Schema Validator Page
 *
 * Test and validate structured data on any URL.
 */

import { useState, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';
import { __, sprintf } from '@wordpress/i18n';
import { icons as sharedIcons } from '../components/Icons';

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

const SchemaValidator = ( { onNavigate } ) => {
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
	return (
		<div className="page">
			<div className="page-header">
				<div>
					<h1>{ __( 'Schema Validator', 'saman-seo' ) }</h1>
					<p>
						{ __(
							'Test and validate structured data (JSON-LD) on any URL.',
							'saman-seo'
						) }
					</p>
				</div>
			</div>

			{ /* URL Input */ }
			<div className="card">
				<div className="schema-validator-input">
					<div className="input-group">
						<input
							type="url"
							className="tool-input"
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
							id="validate-btn"
							type="button"
							className="button primary large"
							onClick={ handleValidate }
							disabled={ loading }
						>
							{ loading ? (
								<>
									<span className="spinner" />
									{ __( 'Validating\u2026', 'saman-seo' ) }
								</>
							) : (
								<>
									<svg
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
										width="18"
										height="18"
									>
										<circle cx="11" cy="11" r="8" />
										<path d="M21 21l-4.35-4.35" />
									</svg>
									{ __( 'Validate', 'saman-seo' ) }
								</>
							) }
						</button>
					</div>
					<div className="quick-actions">
						<button
							type="button"
							className="button ghost small"
							onClick={ handleValidateSite }
						>
							{ __( 'Test Homepage', 'saman-seo' ) }
						</button>
						<span className="quick-actions__hint">
							{ __(
								'Enter any URL to check its structured data markup',
								'saman-seo'
							) }
						</span>
					</div>
				</div>
			</div>

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

			{ /* Results */ }
			{ results && (
				<div className="schema-validator-results">
					{ /* Summary */ }
					<div className="card">
						<div className="schema-summary">
							<div className="schema-summary__header">
								<h3>
									{ __( 'Validation Results', 'saman-seo' ) }
								</h3>
								<a
									href={ results.url }
									target="_blank"
									rel="noopener noreferrer"
									className="schema-summary__url"
								>
									{ results.url }
								</a>
							</div>
							<div className="schema-summary__stats">
								<div className="schema-stat">
									<div className="schema-stat__value">
										{ results.schemas?.length || 0 }
									</div>
									<div className="schema-stat__label">
										{ __( 'Schema', 'saman-seo' ) }
										{ results.schemas?.length !== 1
											? 's'
											: '' }{ ' ' }
										{ __( 'Found', 'saman-seo' ) }
									</div>
								</div>
								<div className="schema-stat schema-stat--success">
									<div className="schema-stat__value">
										{ results.valid_count || 0 }
									</div>
									<div className="schema-stat__label">
										{ __( 'Valid', 'saman-seo' ) }
									</div>
								</div>
								<div className="schema-stat schema-stat--warning">
									<div className="schema-stat__value">
										{ results.warning_count || 0 }
									</div>
									<div className="schema-stat__label">
										{ __( 'Warnings', 'saman-seo' ) }
									</div>
								</div>
								<div className="schema-stat schema-stat--error">
									<div className="schema-stat__value">
										{ results.error_count || 0 }
									</div>
									<div className="schema-stat__label">
										{ __( 'Errors', 'saman-seo' ) }
									</div>
								</div>
							</div>
						</div>
					</div>

					{ /* No Schemas */ }
					{ ( ! results.schemas || results.schemas.length === 0 ) && (
						<div className="card">
							<div className="empty-state">
								<div className="empty-state__icon">
									<svg
										viewBox="0 0 24 24"
										fill="none"
										stroke="currentColor"
										strokeWidth="2"
									>
										<path d="M16 18l2-2-2-2M8 18l-2-2 2-2M14 4l-4 16" />
									</svg>
								</div>
								<h3>
									{ __(
										'No Structured Data Found',
										'saman-seo'
									) }
								</h3>
								<p>
									{ __(
										"This page doesn't contain any JSON-LD structured data markup.",
										'saman-seo'
									) }
								</p>
							</div>
						</div>
					) }

					{ /* Schema List */ }
					{ results.schemas && results.schemas.length > 0 && (
						<div className="schema-list">
							{ results.schemas.map( ( schema, index ) => (
								<div key={ index } className="card schema-card">
									<div
										className="schema-card__header"
										onClick={ () =>
											setExpandedSchema(
												expandedSchema === index
													? null
													: index
											)
										}
									>
										<div className="schema-card__type">
											<div className="schema-card__icon">
												{ getSchemaIcon( schema.type ) }
											</div>
											<div className="schema-card__info">
												<h4>{ schema.type }</h4>
												{ schema.name && (
													<span className="schema-card__name">
														{ schema.name }
													</span>
												) }
											</div>
										</div>
										<div className="schema-card__status">
											{ schema.errors?.length > 0 ? (
												<StatusBadge
													status="errors"
													count={
														schema.errors.length
													}
												/>
											) : schema.warnings?.length > 0 ? (
												<StatusBadge
													status="warnings"
													count={
														schema.warnings.length
													}
												/>
											) : (
												<StatusBadge status="valid" />
											) }
											<svg
												className={ `schema-card__toggle ${
													expandedSchema === index
														? 'schema-card__toggle--open'
														: ''
												}` }
												viewBox="0 0 24 24"
												fill="none"
												stroke="currentColor"
												strokeWidth="2"
											>
												<path d="M6 9l6 6 6-6" />
											</svg>
										</div>
									</div>

									{ expandedSchema === index && (
										<div className="schema-card__body">
											{ /* Errors */ }
											{ schema.errors?.length > 0 && (
												<div className="schema-issues schema-issues--error">
													<h5>
														{ __(
															'Errors',
															'saman-seo'
														) }
													</h5>
													<ul>
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
											{ schema.warnings?.length > 0 && (
												<div className="schema-issues schema-issues--warning">
													<h5>
														{ __(
															'Warnings',
															'saman-seo'
														) }
													</h5>
													<ul>
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

											{ /* Properties */ }
											<div className="schema-properties">
												<h5>
													{ __(
														'Properties',
														'saman-seo'
													) }
												</h5>
												<div className="schema-properties__list">
													{ Object.entries(
														schema.properties || {}
													).map(
														( [ key, value ] ) => (
															<div
																key={ key }
																className="schema-property"
															>
																<span className="schema-property__key">
																	{ key }
																</span>
																<span className="schema-property__value">
																	{ typeof value ===
																	__(
																		'object',
																		'saman-seo'
																	)
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
											<details className="schema-raw">
												<summary>
													{ __(
														'View Raw JSON-LD',
														'saman-seo'
													) }
												</summary>
												<pre>
													{ JSON.stringify(
														schema.raw,
														null,
														2
													) }
												</pre>
											</details>
										</div>
									) }
								</div>
							) ) }
						</div>
					) }

					{ /* Tips */ }
					<div className="card schema-tips">
						<h4>{ __( 'Testing Tips', 'saman-seo' ) }</h4>
						<ul>
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
									{ __(
										'Schema.org Validator',
										'saman-seo'
									) }
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
						</ul>
					</div>
				</div>
			) }
		</div>
	);
};
export default SchemaValidator;
