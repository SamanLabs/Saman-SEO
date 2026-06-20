/**
 * Variable Picker Component for Editor Sidebar
 *
 * A modern dropdown for inserting template variables.
 * Supports compact mode for inline use within inputs.
 * The dropdown is rendered in a portal so it is not clipped by
 * overflow: auto/hidden ancestors (e.g. the Gutenberg SEO modal).
 */

import {
	useState,
	useRef,
	useEffect,
	useCallback,
	createPortal,
} from '@wordpress/element';
import { __ } from '@wordpress/i18n';
const VariablePicker = ( {
	variables = {},
	onSelect,
	context = 'post',
	buttonLabel = 'Variables',
	disabled = false,
	compact = false,
	isOpen: controlledOpen,
	onToggle,
	onClose,
	onMouseDown,
} ) => {
	const [ internalOpen, setInternalOpen ] = useState( false );
	const [ searchTerm, setSearchTerm ] = useState( '' );
	const [ dropdownStyle, setDropdownStyle ] = useState( {} );
	const containerRef = useRef( null );
	const dropdownRef = useRef( null );

	// Use controlled or internal state
	const isOpen = controlledOpen !== undefined ? controlledOpen : internalOpen;
	const setIsOpen = useCallback(
		( value ) => {
			if ( controlledOpen !== undefined ) {
				if ( value ) {
					onToggle?.();
				} else {
					onClose?.();
				}
			} else {
				setInternalOpen( value );
			}
		},
		[ controlledOpen, onToggle, onClose ]
	);

	// Position the dropdown when it opens.
	useEffect( () => {
		if ( ! isOpen ) {
			setDropdownStyle( {} );
			return;
		}
		const container = containerRef.current;
		if ( ! container ) {
			return;
		}
		const rect = container.getBoundingClientRect();
		const dropdownWidth = compact ? 280 : Math.max( 220, rect.width );
		const viewportWidth = window.innerWidth;
		const viewportHeight = window.innerHeight;

		let left = rect.left;
		if ( compact ) {
			// Align right edge of dropdown with right edge of button.
			left = rect.right - dropdownWidth;
		}
		left = Math.max(
			8,
			Math.min( left, viewportWidth - dropdownWidth - 8 )
		);

		const top = rect.bottom + 4;
		const maxHeight = Math.min( 360, viewportHeight - top - 16 );

		setDropdownStyle( {
			position: 'fixed',
			top,
			left,
			width: dropdownWidth,
			maxHeight,
			zIndex: 100000,
		} );
	}, [ isOpen, compact ] );

	// Close on outside click. The dropdown is portaled to document.body, so we
	// must check both the trigger container and the dropdown itself.
	useEffect( () => {
		const handleClickOutside = ( e ) => {
			const inContainer =
				containerRef.current &&
				containerRef.current.contains( e.target );
			const inDropdown =
				dropdownRef.current && dropdownRef.current.contains( e.target );
			if ( ! inContainer && ! inDropdown ) {
				setIsOpen( false );
			}
		};
		if ( isOpen ) {
			document.addEventListener( 'mousedown', handleClickOutside );
		}
		return () =>
			document.removeEventListener( 'mousedown', handleClickOutside );
	}, [ isOpen, controlledOpen, setIsOpen ] );

	// Close dropdown on window resize so the fixed position does not drift away
	// from its anchor. Scroll events inside the dropdown are stopped below.
	useEffect( () => {
		if ( ! isOpen ) {
			return;
		}
		const handleClose = () => setIsOpen( false );
		window.addEventListener( 'resize', handleClose );
		return () => window.removeEventListener( 'resize', handleClose );
	}, [ isOpen, controlledOpen, setIsOpen ] );

	// Stop scroll events inside the portaled dropdown from bubbling to window,
	// otherwise scrolling the variable list would close the dropdown.
	useEffect( () => {
		const dropdown = dropdownRef.current;
		if ( ! dropdown ) {
			return;
		}
		const stopScroll = ( e ) => e.stopPropagation();
		dropdown.addEventListener( 'scroll', stopScroll, true );
		return () => dropdown.removeEventListener( 'scroll', stopScroll, true );
	}, [ isOpen ] );

	// Filter variables by context and search term
	const getFilteredVariables = () => {
		const filtered = {};
		const contextGroups = {
			global: [ 'global' ],
			post: [ 'global', 'post' ],
			taxonomy: [ 'global', 'taxonomy' ],
			archive: [ 'global', 'archive', 'author' ],
			author: [ 'global', 'author' ],
			date: [ 'global', 'archive' ],
			search: [ 'global' ],
			404: [ 'global' ],
		};
		const allowedGroups = contextGroups[ context ] || [ 'global', 'post' ];
		Object.entries( variables ).forEach( ( [ groupKey, group ] ) => {
			if ( ! allowedGroups.includes( groupKey ) ) {
				return;
			}
			const filteredVars = ( group.vars || [] ).filter( ( v ) => {
				if ( ! searchTerm ) {
					return true;
				}
				const term = searchTerm.toLowerCase();
				return (
					v.tag.toLowerCase().includes( term ) ||
					v.label.toLowerCase().includes( term ) ||
					( v.desc && v.desc.toLowerCase().includes( term ) )
				);
			} );
			if ( filteredVars.length > 0 ) {
				filtered[ groupKey ] = {
					...group,
					vars: filteredVars,
				};
			}
		} );
		return filtered;
	};
	const handleSelect = ( variable ) => {
		if ( onSelect ) {
			onSelect( `{{${ variable.tag }}}` );
		}
		setIsOpen( false );
		setSearchTerm( '' );
	};
	const handleToggle = () => {
		if ( controlledOpen !== undefined ) {
			onToggle?.();
		} else {
			setInternalOpen( ! internalOpen );
		}
	};
	const filteredVariables = isOpen ? getFilteredVariables() : {};

	const renderDropdown = () => {
		if ( ! isOpen ) {
			return null;
		}
		const dropdown = (
			<div
				ref={ dropdownRef }
				className="saman-seo-variable-picker__dropdown"
				style={ dropdownStyle }
			>
				<div className="saman-seo-variable-picker__search">
					<input
						type="text"
						value={ searchTerm }
						onChange={ ( e ) => setSearchTerm( e.target.value ) }
						placeholder={ __( 'Search variables…', 'saman-seo' ) }
					/>
				</div>
				<div className="saman-seo-variable-picker__groups">
					{ Object.keys( filteredVariables ).length === 0 ? (
						<div className="saman-seo-variable-picker__empty">
							{ __( 'No variables found', 'saman-seo' ) }
						</div>
					) : (
						Object.entries( filteredVariables ).map(
							( [ groupKey, group ] ) => (
								<div
									key={ groupKey }
									className="saman-seo-variable-picker__group"
								>
									<div
										className={ `saman-seo-variable-picker__group-label saman-seo-variable-picker__group-label--${ groupKey }` }
									>
										{ group.label }
									</div>
									<div className="saman-seo-variable-picker__items">
										{ group.vars.map( ( variable ) => (
											<button
												key={ variable.tag }
												type="button"
												className="saman-seo-variable-picker__item"
												onMouseDown={ ( event ) =>
													event.preventDefault()
												}
												onClick={ () =>
													handleSelect( variable )
												}
											>
												<div className="saman-seo-variable-picker__item-header">
													<span
														className={ `saman-seo-variable-picker__tag saman-seo-variable-picker__tag--${ groupKey }` }
													>
														{ `{{${ variable.tag }}}` }
													</span>
													<span className="saman-seo-variable-picker__label">
														{ variable.label }
													</span>
												</div>
												{ variable.preview && (
													<div className="saman-seo-variable-picker__preview">
														{ variable.preview }
													</div>
												) }
											</button>
										) ) }
									</div>
								</div>
							)
						)
					) }
				</div>
			</div>
		);
		return createPortal( dropdown, document.body );
	};

	// Compact mode - just an icon button
	if ( compact ) {
		return (
			<div
				className="saman-seo-variable-picker saman-seo-variable-picker--compact"
				ref={ containerRef }
			>
				<button
					type="button"
					className="saman-seo-template-input__action-btn saman-seo-template-input__action-btn--vars"
					onMouseDown={ ( event ) => {
						onMouseDown?.( event );
						event.preventDefault();
					} }
					onClick={ handleToggle }
					disabled={ disabled }
					title={ __( 'Insert variable', 'saman-seo' ) }
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
						<path d="M4 7h3a1 1 0 0 0 1-1V3" />
						<path d="M20 7h-3a1 1 0 0 1-1-1V3" />
						<path d="M4 17h3a1 1 0 0 1 1 1v3" />
						<path d="M20 17h-3a1 1 0 0 0-1 1v3" />
						<path d="M9 12h6" />
						<path d="M12 9v6" />
					</svg>
				</button>

				{ renderDropdown() }
			</div>
		);
	}

	// Full mode - button with text
	return (
		<div className="saman-seo-variable-picker" ref={ containerRef }>
			<button
				type="button"
				className="saman-seo-variable-picker__trigger"
				onMouseDown={ ( event ) => {
					onMouseDown?.( event );
					event.preventDefault();
				} }
				onClick={ handleToggle }
				disabled={ disabled }
				title={ __( 'Insert variable', 'saman-seo' ) }
			>
				<svg
					xmlns="http://www.w3.org/2000/svg"
					viewBox="0 0 20 20"
					fill="currentColor"
					width="16"
					height="16"
				>
					<path
						fillRule="evenodd"
						d="M4.5 2A2.5 2.5 0 002 4.5v3.879a2.5 2.5 0 00.732 1.767l7.5 7.5a2.5 2.5 0 003.536 0l3.878-3.878a2.5 2.5 0 000-3.536l-7.5-7.5A2.5 2.5 0 008.38 2H4.5zM5 6a1 1 0 100-2 1 1 0 000 2z"
						clipRule="evenodd"
					/>
				</svg>
				<span>{ buttonLabel }</span>
			</button>

			{ renderDropdown() }
		</div>
	);
};
export default VariablePicker;
