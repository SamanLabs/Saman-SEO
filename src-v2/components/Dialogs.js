/**
 * In-app replacements for the browser's alert(), confirm() and prompt().
 *
 * Call confirmDialog() / promptDialog() / alertDialog() / toast() from
 * anywhere; <DialogHost /> (mounted once in App) renders them.
 */
import { useState, useEffect, useRef, useCallback } from '@wordpress/element';
import { __ } from '@wordpress/i18n';

let host = null;
let nextId = 1;

const open = ( dialog ) =>
	new Promise( ( resolve ) => {
		if ( ! host ) {
			// No host on screen (should not happen): fall back to the browser.
			/* eslint-disable no-alert */
			if ( dialog.kind === 'prompt' ) {
				resolve( window.prompt( dialog.message, dialog.defaultValue ) );
			} else if ( dialog.kind === 'alert' ) {
				window.alert( dialog.message );
				resolve( true );
			} else {
				resolve( window.confirm( dialog.message ) );
			}
			/* eslint-enable no-alert */
			return;
		}
		host.open( { ...dialog, id: nextId++, resolve } );
	} );

/**
 * Ask a yes/no question.
 *
 * @param {string} message Question to show.
 * @param {Object} options { confirmLabel, cancelLabel, danger }.
 * @return {Promise<boolean>} True when confirmed.
 */
export const confirmDialog = ( message, options = {} ) =>
	open( { kind: 'confirm', message, ...options } );

/**
 * Show a message the user has to acknowledge.
 *
 * @param {string} message Message to show.
 * @return {Promise<boolean>} Resolves when dismissed.
 */
export const alertDialog = ( message ) => open( { kind: 'alert', message } );

/**
 * Ask for a line of text.
 *
 * @param {string} message Label for the field.
 * @param {Object} options { defaultValue, confirmLabel }.
 * @return {Promise<string|null>} The text, or null when cancelled.
 */
export const promptDialog = ( message, options = {} ) =>
	open( { kind: 'prompt', message, ...options } );

/**
 * Show a short message that goes away on its own.
 *
 * @param {string} message Message to show.
 * @param {string} tone    'success' (default) or 'error'.
 */
export const toast = ( message, tone = 'success' ) => {
	if ( host ) {
		host.toast( { id: nextId++, message, tone } );
	}
};

const Dialog = ( { dialog, onClose } ) => {
	const [ value, setValue ] = useState( dialog.defaultValue || '' );
	const primaryRef = useRef( null );
	const inputRef = useRef( null );
	const isPrompt = dialog.kind === 'prompt';
	const isAlert = dialog.kind === 'alert';
	const cancelValue = isPrompt ? null : false;
	const confirmValue = isPrompt ? value : true;

	useEffect( () => {
		const previous = document.activeElement;
		( inputRef.current || primaryRef.current )?.focus();
		return () => previous?.focus?.();
	}, [] );

	const onKeyDown = ( e ) => {
		if ( e.key === 'Escape' ) {
			e.stopPropagation();
			onClose( isAlert ? true : cancelValue );
		}
	};

	const submit = ( e ) => {
		e.preventDefault();
		if ( isPrompt && ! value.trim() ) {
			return;
		}
		onClose( confirmValue );
	};

	let confirmLabel = dialog.confirmLabel;
	if ( ! confirmLabel ) {
		confirmLabel = isPrompt
			? __( 'Save', 'saman-seo' )
			: __( 'Confirm', 'saman-seo' );
	}
	if ( isAlert ) {
		confirmLabel = __( 'OK', 'saman-seo' );
	}

	return (
		// eslint-disable-next-line jsx-a11y/no-static-element-interactions
		<div
			className="modal-overlay"
			onMouseDown={ ( e ) =>
				e.target === e.currentTarget &&
				onClose( isAlert ? true : cancelValue )
			}
			onKeyDown={ onKeyDown }
		>
			<form
				className="modal modal--dialog"
				role="alertdialog"
				aria-modal="true"
				aria-describedby={ `saman-dialog-${ dialog.id }` }
				onSubmit={ submit }
			>
				<div className="modal__body">
					{ isPrompt ? (
						<label htmlFor={ `saman-dialog-input-${ dialog.id }` }>
							<span id={ `saman-dialog-${ dialog.id }` }>
								{ dialog.message }
							</span>
							<input
								id={ `saman-dialog-input-${ dialog.id }` }
								ref={ inputRef }
								type="text"
								value={ value }
								onChange={ ( e ) => setValue( e.target.value ) }
							/>
						</label>
					) : (
						<p id={ `saman-dialog-${ dialog.id }` }>
							{ dialog.message }
						</p>
					) }
				</div>
				<div className="modal__footer">
					{ ! isAlert && (
						<button
							type="button"
							className="button ghost"
							onClick={ () => onClose( cancelValue ) }
						>
							{ dialog.cancelLabel || __( 'Cancel', 'saman-seo' ) }
						</button>
					) }
					<button
						ref={ primaryRef }
						type="submit"
						className={ `button ${
							dialog.danger ? 'danger' : 'primary'
						}` }
					>
						{ confirmLabel }
					</button>
				</div>
			</form>
		</div>
	);
};

export const DialogHost = () => {
	const [ dialogs, setDialogs ] = useState( [] );
	const [ toasts, setToasts ] = useState( [] );

	const dismissToast = useCallback( ( id ) => {
		setToasts( ( list ) => list.filter( ( t ) => t.id !== id ) );
	}, [] );

	useEffect( () => {
		const self = {
			open: ( dialog ) => setDialogs( ( list ) => [ ...list, dialog ] ),
			toast: ( item ) => {
				setToasts( ( list ) => [ ...list.slice( -3 ), item ] );
				setTimeout(
					() => dismissToast( item.id ),
					item.tone === 'error' ? 8000 : 4000
				);
			},
		};
		host = self;
		return () => {
			if ( host === self ) {
				host = null;
			}
		};
	}, [ dismissToast ] );

	const current = dialogs[ 0 ];
	const close = ( result ) => {
		current.resolve( result );
		setDialogs( ( list ) => list.slice( 1 ) );
	};

	return (
		<>
			{ current && (
				<Dialog key={ current.id } dialog={ current } onClose={ close } />
			) }
			<div className="toast-stack">
				{ toasts.map( ( item ) => (
					<div
						key={ item.id }
						className={ `toast toast--${ item.tone }` }
						role={ item.tone === 'error' ? 'alert' : 'status' }
					>
						<span>{ item.message }</span>
						<button
							type="button"
							aria-label={ __( 'Dismiss', 'saman-seo' ) }
							onClick={ () => dismissToast( item.id ) }
						>
							×
						</button>
					</div>
				) ) }
			</div>
		</>
	);
};
