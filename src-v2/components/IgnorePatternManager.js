import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
const IgnorePatternManager = ( { onClose, onPatternChange } ) => {
	const [ patterns, setPatterns ] = useState( [] );
	const [ loading, setLoading ] = useState( true );
	const [ newPattern, setNewPattern ] = useState( '' );
	const [ isRegex, setIsRegex ] = useState( false );
	const [ reason, setReason ] = useState( '' );
	const [ adding, setAdding ] = useState( false );
	const [ deletingId, setDeletingId ] = useState( null );
	const [ error, setError ] = useState( '' );

	// Fetch patterns on mount
	useEffect( () => {
		fetchPatterns();
	}, [] );
	const fetchPatterns = async () => {
		setLoading( true );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/404-ignore-patterns',
			} );
			if ( response.success ) {
				setPatterns( response.data );
			}
		} catch ( err ) {
			console.error( 'Failed to fetch patterns:', err );
		} finally {
			setLoading( false );
		}
	};
	const handleAddPattern = async ( e ) => {
		e.preventDefault();
		if ( ! newPattern.trim() ) return;
		setAdding( true );
		setError( '' );
		try {
			const response = await apiFetch( {
				path: '/saman-seo/v1/404-ignore-patterns',
				method: 'POST',
				data: {
					pattern: newPattern.trim(),
					is_regex: isRegex,
					reason: reason.trim(),
				},
			} );
			if ( response.success ) {
				setPatterns( ( prev ) => [ ...prev, response.data ] );
				setNewPattern( '' );
				setIsRegex( false );
				setReason( '' );
				onPatternChange?.();
			} else {
				setError(
					response.message ||
						__( 'Failed to add pattern', 'saman-seo' )
				);
			}
		} catch ( err ) {
			setError(
				err.message || __( 'Failed to add pattern', 'saman-seo' )
			);
		} finally {
			setAdding( false );
		}
	};
	const handleDeletePattern = async ( id ) => {
		setDeletingId( id );
		try {
			await apiFetch( {
				path: `/saman-seo/v1/404-ignore-patterns/${ id }`,
				method: 'DELETE',
			} );
			setPatterns( ( prev ) => prev.filter( ( p ) => p.id !== id ) );
			onPatternChange?.();
		} catch ( err ) {
			console.error( 'Failed to delete pattern:', err );
		} finally {
			setDeletingId( null );
		}
	};
	const formatDate = ( dateStr ) => {
		if ( ! dateStr || dateStr === '0000-00-00 00:00:00' ) return '-';
		const date = new Date( dateStr );
		return date.toLocaleDateString();
	};
	return (
		<div className="modal-overlay" onClick={ onClose }>
			<div
				className="modal ignore-pattern-modal"
				onClick={ ( e ) => e.stopPropagation() }
			>
				<div className="modal-header">
					<h2>{ __( 'Manage Ignore Patterns', 'saman-seo' ) }</h2>
					<button
						type="button"
						className="modal-close"
						onClick={ onClose }
					>
						<svg
							viewBox="0 0 20 20"
							fill="currentColor"
							width="20"
							height="20"
						>
							<path
								fillRule="evenodd"
								d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
								clipRule="evenodd"
							/>
						</svg>
					</button>
				</div>

				<div className="modal-body">
					<p className="modal-description">
						{ __(
							'Add URL patterns to automatically ignore matching 404 errors. Use',
							'saman-seo'
						) }{ ' ' }
						<code>*</code>{ ' ' }
						{ __( 'as wildcard (e.g.,', 'saman-seo' ) }{ ' ' }
						<code>/uploads/*</code>
						{ __(
							') or enable regex for complex patterns.',
							'saman-seo'
						) }
					</p>

					{ /* Add Pattern Form */ }
					<form
						onSubmit={ handleAddPattern }
						className="pattern-form"
					>
						<div className="form-row">
							<label className="form-field">
								<span>{ __( 'Pattern', 'saman-seo' ) }</span>
								<input
									type="text"
									value={ newPattern }
									onChange={ ( e ) =>
										setNewPattern( e.target.value )
									}
									placeholder={ __(
										'/uploads/*',
										'saman-seo'
									) }
									disabled={ adding }
								/>
							</label>
						</div>
						<div className="form-row form-row--split">
							<label className="form-field">
								<span>
									{ __( 'Reason (optional)', 'saman-seo' ) }
								</span>
								<input
									type="text"
									value={ reason }
									onChange={ ( e ) =>
										setReason( e.target.value )
									}
									placeholder={ __(
										'e.g., Legacy URLs',
										'saman-seo'
									) }
									disabled={ adding }
								/>
							</label>
							<label className="form-checkbox">
								<input
									type="checkbox"
									checked={ isRegex }
									onChange={ ( e ) =>
										setIsRegex( e.target.checked )
									}
									disabled={ adding }
								/>
								<span>{ __( 'Use Regex', 'saman-seo' ) }</span>
							</label>
						</div>
						{ error && <p className="form-error">{ error }</p> }
						<div className="form-actions">
							<button
								type="submit"
								className="button primary"
								disabled={ adding || ! newPattern.trim() }
							>
								{ adding
									? __( 'Adding\u2026', 'saman-seo' )
									: __( 'Add Pattern', 'saman-seo' ) }
							</button>
						</div>
					</form>

					{ /* Patterns List */ }
					<div className="patterns-list">
						<h3>{ __( 'Current Patterns', 'saman-seo' ) }</h3>
						{ loading ? (
							<div className="loading-state">
								{ __( 'Loading patterns\u2026', 'saman-seo' ) }
							</div>
						) : patterns.length === 0 ? (
							<div className="empty-state small">
								<p>
									{ __(
										'No ignore patterns defined yet.',
										'saman-seo'
									) }
								</p>
							</div>
						) : (
							<table className="data-table compact">
								<thead>
									<tr>
										<th>
											{ __( 'Pattern', 'saman-seo' ) }
										</th>
										<th>{ __( 'Type', 'saman-seo' ) }</th>
										<th>{ __( 'Reason', 'saman-seo' ) }</th>
										<th>
											{ __( 'Created', 'saman-seo' ) }
										</th>
										<th>{ __( 'Action', 'saman-seo' ) }</th>
									</tr>
								</thead>
								<tbody>
									{ patterns.map( ( pattern ) => (
										<tr key={ pattern.id }>
											<td>
												<code>{ pattern.pattern }</code>
											</td>
											<td>
												<span
													className={ `badge ${
														pattern.is_regex
															? 'info'
															: 'muted'
													}` }
												>
													{ pattern.is_regex
														? __(
																'Regex',
																'saman-seo'
														  )
														: __(
																'Wildcard',
																'saman-seo'
														  ) }
												</span>
											</td>
											<td>{ pattern.reason || '-' }</td>
											<td>
												{ formatDate(
													pattern.created_at
												) }
											</td>
											<td>
												<button
													type="button"
													className="button ghost small danger"
													onClick={ () =>
														handleDeletePattern(
															pattern.id
														)
													}
													disabled={
														deletingId ===
														pattern.id
													}
												>
													{ deletingId === pattern.id
														? '...'
														: __(
																'Delete',
																'saman-seo'
														  ) }
												</button>
											</td>
										</tr>
									) ) }
								</tbody>
							</table>
						) }
					</div>
				</div>

				<div className="modal-footer">
					<button
						type="button"
						className="button"
						onClick={ onClose }
					>
						{ __( 'Close', 'saman-seo' ) }
					</button>
				</div>
			</div>
		</div>
	);
};
export default IgnorePatternManager;
