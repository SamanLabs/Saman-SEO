import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
const STATUS_CODES = [
	{
		value: 301,
		label: __( '301 - Permanent Redirect', 'saman-seo' ),
	},
	{
		value: 302,
		label: __( '302 - Temporary Redirect', 'saman-seo' ),
	},
	{
		value: 307,
		label: __( '307 - Temporary (Preserve Method)', 'saman-seo' ),
	},
	{
		value: 410,
		label: __( '410 - Gone (Deleted)', 'saman-seo' ),
	},
];
const CreateRedirectModal = ( { entry, onClose, onSuccess } ) => {
	const [ suggestions, setSuggestions ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ creating, setCreating ] = useState( false );
	const [ selectedUrl, setSelectedUrl ] = useState( '' );
	const [ customUrl, setCustomUrl ] = useState( '' );
	const [ statusCode, setStatusCode ] = useState( 301 );
	const [ deleteEntry, setDeleteEntry ] = useState( true );
	const [ error, setError ] = useState( '' );

	// Fetch suggestions on mount
	useEffect( () => {
		const fetchSuggestions = async () => {
			setLoading( true );
			try {
				const response = await apiFetch( {
					path: `/saman-seo/v1/404-log/${ entry.id }/suggestions`,
				} );
				if ( response.success && response.data.suggestions ) {
					setSuggestions( response.data.suggestions );
					// Auto-select the top suggestion if available
					if ( response.data.suggestions.length > 0 ) {
						setSelectedUrl( response.data.suggestions[ 0 ].url );
					}
				}
			} catch ( err ) {
				console.error( 'Failed to fetch suggestions:', err );
			} finally {
				setLoading( false );
			}
		};
		fetchSuggestions();
	}, [ entry.id ] );
	const handleCreate = async () => {
		const targetUrl = selectedUrl === 'custom' ? customUrl : selectedUrl;
		if ( ! targetUrl ) {
			setError(
				__( 'Please select or enter a target URL.', 'saman-seo' )
			);
			return;
		}
		setCreating( true );
		setError( '' );
		try {
			const response = await apiFetch( {
				path: `/saman-seo/v1/404-log/${ entry.id }/create-redirect`,
				method: 'POST',
				data: {
					target: targetUrl,
					status_code: statusCode,
					delete_entry: deleteEntry,
				},
			} );
			if ( response.success ) {
				onSuccess( response.data );
			} else {
				setError(
					response.message ||
						__( 'Failed to create redirect.', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to create redirect.', 'saman-seo' )
			);
		} finally {
			setCreating( false );
		}
	};
	return (
		<div
			className="modal-overlay"
			onClick={ ( e ) => e.target === e.currentTarget && onClose() }
		>
			<div className="modal redirect-modal">
				<div className="modal__header">
					<h2>{ __( 'Create Redirect', 'saman-seo' ) }</h2>
					<button
						type="button"
						className="modal__close"
						onClick={ onClose }
					>
						<svg
							viewBox="0 0 24 24"
							fill="none"
							stroke="currentColor"
							strokeWidth="2"
							width="20"
							height="20"
						>
							<path d="M18 6L6 18M6 6l12 12" />
						</svg>
					</button>
				</div>

				<div className="modal__body">
					{ /* Source URL */ }
					<div className="form-group">
						<label>{ __( 'Source URL (404)', 'saman-seo' ) }</label>
						<div className="source-url">
							<code>{ entry.request_uri }</code>
							<span className="hits-badge">
								{ entry.hits } { __( 'hits', 'saman-seo' ) }
							</span>
						</div>
					</div>

					{ /* Suggestions */ }
					<div className="form-group">
						<label>{ __( 'Target URL', 'saman-seo' ) }</label>
						{ loading ? (
							<div className="suggestions-loading">
								{ __(
									'Loading suggestions\u2026',
									'saman-seo'
								) }
							</div>
						) : (
							<>
								{ suggestions.length > 0 && (
									<div className="suggestions-list">
										<p className="suggestions-label">
											{ __(
												'Suggested targets:',
												'saman-seo'
											) }
										</p>
										{ suggestions.map(
											( suggestion, idx ) => (
												<label
													key={ idx }
													className="suggestion-item"
												>
													<input
														type="radio"
														name="target"
														value={ suggestion.url }
														checked={
															selectedUrl ===
															suggestion.url
														}
														onChange={ () =>
															setSelectedUrl(
																suggestion.url
															)
														}
													/>
													<div className="suggestion-content">
														<span className="suggestion-title">
															{ suggestion.title }
														</span>
														<span className="suggestion-url">
															{ suggestion.url }
														</span>
														<span className="suggestion-score">
															{ suggestion.score }
															{ __(
																'% match',
																'saman-seo'
															) }
														</span>
													</div>
												</label>
											)
										) }
									</div>
								) }

								<div className="custom-url-option">
									<label className="suggestion-item">
										<input
											type="radio"
											name="target"
											value="custom"
											checked={ selectedUrl === 'custom' }
											onChange={ () =>
												setSelectedUrl( 'custom' )
											}
										/>
										<span>
											{ __(
												'Enter custom URL',
												'saman-seo'
											) }
										</span>
									</label>
									{ __(
										'https://example.com/page',
										'saman-seo'
									) }
								</div>
							</>
						) }
					</div>

					{ /* Status Code */ }
					<div className="form-group">
						<label htmlFor="status-code">
							{ __( 'Status Code', 'saman-seo' ) }
						</label>
						<select
							id="status-code"
							value={ statusCode }
							onChange={ ( e ) =>
								setStatusCode( parseInt( e.target.value, 10 ) )
							}
						>
							{ STATUS_CODES.map( ( opt ) => (
								<option key={ opt.value } value={ opt.value }>
									{ opt.label }
								</option>
							) ) }
						</select>
					</div>

					{ /* Delete Entry Option */ }
					<div className="form-group">
						<label className="checkbox-label">
							<input
								type="checkbox"
								checked={ deleteEntry }
								onChange={ ( e ) =>
									setDeleteEntry( e.target.checked )
								}
							/>
							<span>
								{ __(
									'Remove 404 entry after creating redirect',
									'saman-seo'
								) }
							</span>
						</label>
					</div>

					{ /* Error Message */ }
					{ error && <div className="form-error">{ error }</div> }
				</div>

				<div className="modal__footer">
					<button
						type="button"
						className="button"
						onClick={ onClose }
						disabled={ creating }
					>
						{ __( 'Cancel', 'saman-seo' ) }
					</button>
					<button
						type="button"
						className="button primary"
						onClick={ handleCreate }
						disabled={
							creating ||
							loading ||
							! selectedUrl ||
							( selectedUrl === 'custom' && ! customUrl )
						}
					>
						{ creating
							? __( 'Creating\u2026', 'saman-seo' )
							: __( 'Create Redirect', 'saman-seo' ) }
					</button>
				</div>
			</div>
		</div>
	);
};
export default CreateRedirectModal;
