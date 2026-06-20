/**
 * Template Input Component for Editor Sidebar
 *
 * A contenteditable input with:
 * - Variables rendered as tag pills (no braces)
 * - Hover preview showing rendered values
 * - Floating action icons (Variables, AI)
 */

import { useState, useRef, useEffect, createPortal } from '@wordpress/element';
import VariablePicker from './VariablePicker';

/* global Node, DOMParser */

// Variable type color mapping
import { __, sprintf } from '@wordpress/i18n';
const variableColors = {
	// Global variables - blue
	site_title: 'global',
	tagline: 'global',
	site_url: 'global',
	separator: 'separator',
	current_year: 'global',
	current_month: 'global',
	current_day: 'global',
	// Post variables - violet
	post_title: 'post',
	post_excerpt: 'post',
	post_date: 'post',
	post_modified: 'post',
	modified: 'post',
	post_author: 'post',
	author: 'post',
	category: 'post',
	post_id: 'post',
	id: 'post',
	post_content: 'post',
	// Taxonomy variables - green
	term_title: 'taxonomy',
	term_description: 'taxonomy',
	term_count: 'taxonomy',
	// Author variables - orange
	author_name: 'author',
	author_bio: 'author',
	// Archive variables - teal
	archive_title: 'archive',
	search_query: 'archive',
	search_term: 'archive',
	page_number: 'archive',
	date: 'archive',
	archive_date: 'archive',
	request_url: 'archive',
};

// Extract base tag from variable (handles modifiers like "post_title | upper")
const getBaseTag = ( fullTag ) => {
	const pipeIndex = fullTag.indexOf( '|' );
	if ( pipeIndex > -1 ) {
		return fullTag.substring( 0, pipeIndex ).trim();
	}
	return fullTag.trim();
};
const getVariableType = ( tag ) => {
	const baseTag = getBaseTag( tag );
	return variableColors[ baseTag ] || 'global';
};

// Estimate the displayed length after replacing variables with their values.
const applyModifier = ( value, modifier ) => {
	if ( ! value || ! modifier ) {
		return value;
	}
	const mod = modifier.trim().toLowerCase();
	switch ( mod ) {
		case 'upper':
		case 'uppercase':
			return String( value ).toUpperCase();
		case 'lower':
		case 'lowercase':
			return String( value ).toLowerCase();
		case 'capitalize':
		case 'title':
			return String( value ).replace( /\b\w/g, ( c ) => c.toUpperCase() );
		case 'trim':
			return String( value ).trim();
		default:
			return value;
	}
};

/**
 * Extract all known variable tag names from the grouped variables object.
 *
 * @param {Object} variables Grouped variables object.
 * @return {Set<string>} Set of known tag names.
 */
const getKnownTags = ( variables ) => {
	const tags = new Set();
	Object.values( variables ).forEach( ( group ) => {
		( group.vars || [] ).forEach( ( v ) => {
			if ( v.tag ) {
				tags.add( v.tag );
			}
		} );
	} );
	return tags;
};

/**
 * Make a tag pill selectable with a single click and draggable for reordering.
 *
 * @param {HTMLElement} span The tag span element.
 */
const decorateTag = ( span ) => {
	span.draggable = true;
	span.onmousedown = ( event ) => {
		if ( event.button !== 0 ) {
			return;
		}
		const doc = span.ownerDocument;
		const selection = doc.defaultView.getSelection();
		const range = doc.createRange();
		range.selectNode( span );
		selection.removeAllRanges();
		selection.addRange( range );
	};
	span.ondragstart = ( event ) => {
		event.dataTransfer.setData(
			'text/x-saman-tag',
			span.dataset.raw || ''
		);
		event.dataTransfer.effectAllowed = 'move';
		span.classList.add( 'is-dragging' );
	};
	span.ondragend = () => {
		span.classList.remove( 'is-dragging' );
	};
};

const getRenderedLength = ( template, values ) => {
	if ( ! template ) {
		return 0;
	}
	const rendered = template.replace(
		/\{\{([^}]+)\}\}/g,
		( match, content ) => {
			const trimmedContent = content.trim();
			const pipeIndex = trimmedContent.indexOf( '|' );
			if ( pipeIndex > -1 ) {
				const baseTag = trimmedContent.substring( 0, pipeIndex ).trim();
				const modifier = trimmedContent
					.substring( pipeIndex + 1 )
					.trim();
				const baseValue = values[ baseTag ];
				if ( baseValue !== undefined ) {
					return applyModifier( baseValue, modifier );
				}
				return match;
			}
			return values[ trimmedContent ] !== undefined
				? values[ trimmedContent ]
				: match;
		}
	);
	return rendered.length;
};

/**
 * Create a fully-decorated tag span from its raw template representation.
 *
 * @param {string} raw The raw tag, e.g. "{{tagline}}".
 * @return {HTMLElement} The decorated tag span.
 */
const createTagFromRaw = ( raw ) => {
	const fullTag = raw.replace( /^\{\{|\}\}$/g, '' ).trim();
	const baseTag = getBaseTag( fullTag );
	const varType = getVariableType( baseTag );
	const span = document.createElement( 'span' );
	span.className = `saman-seo-template-input__tag saman-seo-template-input__tag--${ varType }`;
	span.contentEditable = 'false';
	span.dataset.raw = raw;
	span.dataset.fullTag = fullTag;
	span.dataset.baseTag = baseTag;
	span.textContent = fullTag;
	decorateTag( span );
	return span;
};

/**
 * Build a DOM fragment from the string template.
 * Variables become non-editable span pills; everything else is text nodes.
 *
 * @param {string} template The raw template value.
 * @return {DocumentFragment} Parsed fragment.
 */
const buildFragment = ( template ) => {
	const fragment = document.createDocumentFragment();
	if ( ! template ) {
		return fragment;
	}
	const regex = /\{\{([^}]+)\}\}/g;
	let lastIndex = 0;
	let match;
	while ( ( match = regex.exec( template ) ) !== null ) {
		if ( match.index > lastIndex ) {
			fragment.appendChild(
				document.createTextNode(
					template.slice( lastIndex, match.index )
				)
			);
		}
		const fullTag = match[ 1 ].trim();
		const baseTag = getBaseTag( fullTag );
		const varType = getVariableType( baseTag );
		const span = document.createElement( 'span' );
		span.className = `saman-seo-template-input__tag saman-seo-template-input__tag--${ varType }`;
		span.contentEditable = 'false';
		span.dataset.raw = match[ 0 ];
		span.dataset.fullTag = fullTag;
		span.dataset.baseTag = baseTag;
		span.textContent = fullTag;
		decorateTag( span );
		fragment.appendChild( span );
		lastIndex = regex.lastIndex;
	}
	if ( lastIndex < template.length ) {
		fragment.appendChild(
			document.createTextNode( template.slice( lastIndex ) )
		);
	}
	return fragment;
};

/**
 * Read the current editor DOM back into the string template.
 *
 * @param {HTMLElement} root The contenteditable root.
 * @return {string} The raw template value.
 */
const readValue = ( root ) => {
	let text = '';
	root.childNodes.forEach( ( node ) => {
		if ( node.nodeType === Node.TEXT_NODE ) {
			text += node.textContent;
		} else if ( node.nodeType === Node.ELEMENT_NODE ) {
			if (
				node.classList &&
				node.classList.contains( 'saman-seo-template-input__tag' )
			) {
				text += node.dataset.raw || `{{${ node.textContent }}}`;
			} else {
				text += node.textContent;
			}
		}
	} );
	return text;
};

const TemplateInput = ( {
	value = '',
	onChange,
	placeholder = '',
	variables = {},
	variableValues = {},
	context = 'post',
	multiline = false,
	maxLength = null,
	label = '',
	helpText = '',
	id,
	disabled = false,
	onAiClick = null,
	showAiButton = true,
	aiEnabled = true,
} ) => {
	const editorRef = useRef( null );
	const containerRef = useRef( null );
	const [ isFocused, setIsFocused ] = useState( false );
	const [ showVariablePicker, setShowVariablePicker ] = useState( false );
	const [ hoveredVariable, setHoveredVariable ] = useState( null );
	const internalChangeRef = useRef( false );
	const pickerOpenRef = useRef( false );
	const savedSelectionRef = useRef( null );
	useEffect( () => {
		pickerOpenRef.current = showVariablePicker;
	}, [ showVariablePicker ] );

	/**
	 * Store the current selection range so it can be restored after focus
	 * moves to the variable picker or other controls.
	 */
	const saveSelection = () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const selection = editor.ownerDocument.defaultView.getSelection();
		if (
			selection.rangeCount > 0 &&
			editor.contains( selection.anchorNode )
		) {
			savedSelectionRef.current = selection.getRangeAt( 0 ).cloneRange();
		}
	};

	/**
	 * Sync external value changes into the editor.
	 * Skip when the DOM already matches to avoid resetting the cursor.
	 */
	useEffect( () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		if ( internalChangeRef.current ) {
			internalChangeRef.current = false;
			return;
		}
		const current = readValue( editor );
		if ( current === value ) {
			return;
		}
		editor.innerHTML = '';
		editor.appendChild( buildFragment( value ) );
	}, [ value ] );

	// Continuously save the editor selection so we can restore it after focus
	// moves to the variable picker or other controls.
	useEffect( () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const doc = editor.ownerDocument;
		const handleSelectionChange = () => {
			if ( pickerOpenRef.current ) {
				return;
			}
			const selection = doc.defaultView.getSelection();
			if (
				selection.rangeCount > 0 &&
				editor.contains( selection.anchorNode )
			) {
				savedSelectionRef.current = selection
					.getRangeAt( 0 )
					.cloneRange();
			}
		};
		doc.addEventListener( 'selectionchange', handleSelectionChange );
		return () =>
			doc.removeEventListener( 'selectionchange', handleSelectionChange );
	}, [] );

	const handleInput = () => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const newValue = readValue( editor );
		if ( newValue !== value ) {
			internalChangeRef.current = true;
			onChange( newValue );
		}
	};

	/**
	 * Insert a variable tag at the current caret position.
	 *
	 * @param {string} variableTag The variable tag to insert (e.g. "{{tagline}}").
	 */
	const insertVariable = ( variableTag ) => {
		const editor = editorRef.current;
		if ( ! editor ) {
			onChange( value + variableTag );
			setShowVariablePicker( false );
			return;
		}
		editor.focus();

		const fullTag = variableTag.replace( /^\{\{|\}\}$/g, '' ).trim();
		const baseTag = getBaseTag( fullTag );
		const varType = getVariableType( baseTag );
		const tagEl = document.createElement( 'span' );
		tagEl.className = `saman-seo-template-input__tag saman-seo-template-input__tag--${ varType }`;
		tagEl.contentEditable = 'false';
		tagEl.dataset.raw = variableTag;
		tagEl.dataset.fullTag = fullTag;
		tagEl.dataset.baseTag = baseTag;
		tagEl.textContent = fullTag;
		tagEl.title =
			variableValues[ baseTag ] ||
			variableValues[ `{{${ baseTag }}}` ] ||
			baseTag;
		decorateTag( tagEl );

		const selection = editor.ownerDocument.defaultView.getSelection();
		let range = selection.rangeCount > 0 ? selection.getRangeAt( 0 ) : null;

		if ( ! range || ! editor.contains( range.commonAncestorContainer ) ) {
			range = savedSelectionRef.current;
			if ( range && editor.contains( range.commonAncestorContainer ) ) {
				selection.removeAllRanges();
				selection.addRange( range );
			}
		}

		if ( range && editor.contains( range.commonAncestorContainer ) ) {
			range.deleteContents();
			range.insertNode( tagEl );

			// Add a space after the tag for nicer editing, unless there is one.
			const nextNode = tagEl.nextSibling;
			if ( ! nextNode || nextNode.textContent.charAt( 0 ) !== ' ' ) {
				const space = document.createTextNode( '\u00A0' );
				tagEl.parentNode.insertBefore( space, nextNode );
			}

			range.setStartAfter( tagEl.nextSibling || tagEl );
			range.collapse( true );
			selection.removeAllRanges();
			selection.addRange( range );
		} else {
			editor.appendChild( tagEl );
			const space = document.createTextNode( '\u00A0' );
			editor.appendChild( space );
			const newRange = document.createRange();
			newRange.setStartAfter( space );
			newRange.collapse( true );
			selection.removeAllRanges();
			selection.addRange( newRange );
		}

		setShowVariablePicker( false );
		handleInput();
	};

	/**
	 * On blur, normalize manually-typed {{variables}} into tag pills.
	 * Skip rebuilding when the variable picker is open so the insertion
	 * point is preserved.
	 */
	const handleBlur = () => {
		setIsFocused( false );
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const current = readValue( editor );
		if ( current !== value ) {
			internalChangeRef.current = true;
			onChange( current );
		}
		if ( pickerOpenRef.current ) {
			return;
		}
		editor.innerHTML = '';
		editor.appendChild( buildFragment( current ) );
	};

	/**
	 * Backspace next to a tag should delete the whole tag.
	 *
	 * @param {KeyboardEvent} event Keyboard event.
	 */
	const handleKeyDown = ( event ) => {
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const selection = editor.ownerDocument.defaultView.getSelection();
		if ( ! selection.rangeCount ) {
			return;
		}
		const range = selection.getRangeAt( 0 );

		if ( event.key === 'Backspace' || event.key === 'Delete' ) {
			let node = null;
			if (
				range.collapsed &&
				range.startContainer.nodeType === Node.TEXT_NODE &&
				range.startOffset === 0
			) {
				node = range.startContainer.previousSibling;
			} else if (
				range.collapsed &&
				range.startContainer === editor &&
				range.startOffset > 0 &&
				event.key === 'Backspace'
			) {
				node = editor.childNodes[ range.startOffset - 1 ];
			} else if (
				range.collapsed &&
				range.startContainer === editor &&
				range.startOffset < editor.childNodes.length &&
				event.key === 'Delete'
			) {
				node = editor.childNodes[ range.startOffset ];
			}

			if (
				node &&
				node.nodeType === Node.ELEMENT_NODE &&
				node.classList.contains( 'saman-seo-template-input__tag' )
			) {
				event.preventDefault();
				node.remove();
				handleInput();
				return;
			}
		}

		if ( event.key === 'Enter' && ! multiline ) {
			event.preventDefault();
		}
	};

	/**
	 * Paste content, preserving copied variable tags when possible.
	 *
	 * @param {ClipboardEvent} event Clipboard event.
	 */
	const handlePaste = ( event ) => {
		event.preventDefault();
		const editor = editorRef.current;
		if ( ! editor ) {
			return;
		}
		const selection = editor.ownerDocument.defaultView.getSelection();
		if ( ! selection.rangeCount ) {
			return;
		}
		const range = selection.getRangeAt( 0 );
		range.deleteContents();

		const html = event.clipboardData.getData( 'text/html' );
		const text = event.clipboardData.getData( 'text/plain' );
		const fragment = document.createDocumentFragment();

		if ( html ) {
			const parser = new DOMParser();
			const doc = parser.parseFromString( html, 'text/html' );
			doc.body.childNodes.forEach( ( node ) => {
				if (
					node.nodeType === Node.ELEMENT_NODE &&
					node.classList.contains( 'saman-seo-template-input__tag' )
				) {
					fragment.appendChild(
						createTagFromRaw(
							node.dataset.raw || `{{${ node.textContent }}}`
						)
					);
				} else if ( node.nodeType === Node.TEXT_NODE ) {
					fragment.appendChild(
						document.createTextNode( node.textContent )
					);
				} else if ( node.nodeType === Node.ELEMENT_NODE ) {
					// Flatten other HTML to plain text to avoid unexpected markup.
					fragment.appendChild(
						document.createTextNode( node.textContent )
					);
				}
			} );
		} else if ( text ) {
			const trimmedText = text.trim();
			const knownTags = getKnownTags( variables );
			if ( knownTags.has( trimmedText ) ) {
				// Pasted a bare variable name (e.g. from cutting a tag pill).
				fragment.appendChild(
					createTagFromRaw( `{{${ trimmedText }}}` )
				);
			} else {
				// buildFragment will turn any "{{...}}" text into proper tags.
				fragment.appendChild( buildFragment( text ) );
			}
		}

		if ( fragment.hasChildNodes() ) {
			range.insertNode( fragment );
			range.collapse( false );
			selection.removeAllRanges();
			selection.addRange( range );
		}
		handleInput();
	};

	const charCount = getRenderedLength( value, variableValues );
	const rawCharCount = value.length;
	const isOverLimit = maxLength && charCount > maxLength;
	let counterClass = '';
	if ( isOverLimit ) {
		counterClass = 'over-limit';
	} else if ( charCount > 0 ) {
		counterClass = 'has-value';
	}

	return (
		<div
			className={ `saman-seo-template-input ${
				isFocused ? 'is-focused' : ''
			} ${ disabled ? 'is-disabled' : '' }` }
		>
			{ label && (
				<div className="saman-seo-template-input__header">
					<label
						className="saman-seo-template-input__label"
						htmlFor={ id }
					>
						{ label }
					</label>
					{ maxLength && (
						<span
							className={ `saman-seo-template-input__counter ${ counterClass }` }
							title={ sprintf(
								/* translators: %d: raw template character count */
								__(
									'Rendered estimate (%d raw characters)',
									'saman-seo'
								),
								rawCharCount
							) }
						>
							{ charCount }/{ maxLength }
						</span>
					) }
				</div>
			) }

			<div
				ref={ containerRef }
				className="saman-seo-template-input__container"
			>
				<div
					ref={ editorRef }
					id={ id }
					className={ `saman-seo-template-input__field ${
						multiline ? 'multiline' : ''
					}` }
					contentEditable={ ! disabled }
					suppressContentEditableWarning
					onInput={ handleInput }
					onFocus={ () => setIsFocused( true ) }
					onBlur={ handleBlur }
					onKeyDown={ handleKeyDown }
					onPaste={ handlePaste }
					onDragOver={ ( event ) => {
						event.preventDefault();
						event.dataTransfer.dropEffect = 'move';
					} }
					onDrop={ ( event ) => {
						event.preventDefault();
						const raw =
							event.dataTransfer.getData( 'text/x-saman-tag' );
						if ( ! raw || ! raw.startsWith( '{{' ) ) {
							return;
						}
						const editor = editorRef.current;
						if ( ! editor ) {
							return;
						}

						// Remove the dragged tag from its original position.
						const dragged = Array.from(
							editor.querySelectorAll(
								'.saman-seo-template-input__tag'
							)
						).find( ( el ) => el.dataset.raw === raw );
						if ( dragged ) {
							dragged.remove();
						}

						// Determine drop caret position.
						const doc = editor.ownerDocument;
						let range = null;
						if ( doc.caretPositionFromPoint ) {
							const pos = doc.caretPositionFromPoint(
								event.clientX,
								event.clientY
							);
							if ( pos ) {
								range = doc.createRange();
								range.setStart( pos.offsetNode, pos.offset );
								range.collapse( true );
							}
						} else if ( doc.caretRangeFromPoint ) {
							range = doc.caretRangeFromPoint(
								event.clientX,
								event.clientY
							);
						}

						if (
							! range ||
							! editor.contains( range.commonAncestorContainer )
						) {
							range = doc.createRange();
							range.selectNodeContents( editor );
							range.collapse( false );
						}

						const tagEl = createTagFromRaw( raw );
						range.insertNode( tagEl );

						// Add a space after the tag for nicer editing.
						const nextNode = tagEl.nextSibling;
						if (
							! nextNode ||
							nextNode.textContent.charAt( 0 ) !== ' '
						) {
							const space = document.createTextNode( '\u00A0' );
							tagEl.parentNode.insertBefore( space, nextNode );
						}

						range.setStartAfter( tagEl.nextSibling || tagEl );
						range.collapse( true );
						const selection = doc.defaultView.getSelection();
						selection.removeAllRanges();
						selection.addRange( range );

						handleInput();
					} }
					onMouseMove={ ( event ) => {
						const target = event.target.closest(
							'.saman-seo-template-input__tag'
						);
						if ( target ) {
							const baseTag = target.dataset.baseTag;
							const preview =
								variableValues[ baseTag ] ||
								variableValues[ `{{${ baseTag }}}` ];
							if ( preview ) {
								setHoveredVariable( {
									element: target,
									preview,
								} );
								return;
							}
						}
						setHoveredVariable( null );
					} }
					onMouseLeave={ () => setHoveredVariable( null ) }
					role="textbox"
					aria-multiline={ multiline }
					tabIndex={ 0 }
				/>
				{ ! value && ! isFocused && (
					<span className="saman-seo-template-input__placeholder">
						{ placeholder }
					</span>
				) }
				{ hoveredVariable &&
					hoveredVariable.element &&
					createPortal(
						<span
							className="saman-seo-template-input__tooltip"
							style={ {
								position: 'fixed',
								left:
									hoveredVariable.element.getBoundingClientRect()
										.left +
									hoveredVariable.element.getBoundingClientRect()
										.width /
										2,
								top:
									hoveredVariable.element.getBoundingClientRect()
										.top - 8,
								transform: 'translate(-50%, -100%)',
							} }
						>
							{ hoveredVariable.preview }
						</span>,
						document.body
					) }

				{ /* Floating action buttons */ }
				<div className="saman-seo-template-input__actions">
					{ onAiClick && showAiButton && (
						<button
							type="button"
							className={ `saman-seo-template-input__action-btn saman-seo-template-input__action-btn--ai ${
								! aiEnabled ? 'is-disabled' : ''
							}` }
							onClick={ onAiClick }
							disabled={ disabled || ! aiEnabled }
							title={
								aiEnabled
									? __( 'Generate with AI', 'saman-seo' )
									: __(
											'AI generation is not enabled',
											'saman-seo'
									  )
							}
							aria-label={ __( 'Generate with AI', 'saman-seo' ) }
						>
							<svg
								width="14"
								height="14"
								viewBox="0 0 24 24"
								fill="none"
								stroke="currentColor"
								strokeWidth="2"
								strokeLinecap="round"
								strokeLinejoin="round"
							>
								<path d="M12 2L14.4 9.6L22 12L14.4 14.4L12 22L9.6 14.4L2 12L9.6 9.6L12 2Z" />
							</svg>
						</button>
					) }
					<VariablePicker
						variables={ variables }
						context={ context }
						onSelect={ insertVariable }
						onMouseDown={ saveSelection }
						disabled={ disabled }
						isOpen={ showVariablePicker }
						onToggle={ () =>
							setShowVariablePicker( ! showVariablePicker )
						}
						onClose={ () => setShowVariablePicker( false ) }
						compact
					/>
				</div>
			</div>

			{ helpText && (
				<div className="saman-seo-template-input__footer">
					<span className="saman-seo-template-input__help">
						{ helpText }
					</span>
				</div>
			) }
		</div>
	);
};
export default TemplateInput;
