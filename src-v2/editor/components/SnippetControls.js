/**
 * Per-post snippet controls: nosnippet, max-snippet, max-image-preview and
 * noimageindex. Google applies these to classic snippets and to how the
 * page may appear in AI Overviews / AI Mode.
 *
 * Values use the stored format: flags are '1' / '', max_snippet is a
 * numeric string ('' = inherit), max_image_preview is '' (inherit) or
 * none|standard|large.
 */

import { useCallback, useEffect } from '@wordpress/element';
import { useSelect, useDispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';

export const SNIPPET_KEYS = [
	'nosnippet',
	'max_snippet',
	'max_image_preview',
	'noimageindex',
];

const META_KEY = '_SAMAN_SEO_meta';
const EMPTY = {};

/**
 * Normalize a snippet value to its stored string format.
 *
 * @param {string} key   Meta key.
 * @param {*}      value Raw value.
 * @return {string} Stored value.
 */
const normalize = ( key, value ) => {
	if ( key === 'nosnippet' || key === 'noimageindex' ) {
		return value === true || value === '1' || value === 1 ? '1' : '';
	}
	if ( value === undefined || value === null ) {
		return '';
	}
	return String( value ).trim();
};

/**
 * Snippet values plus a setter.
 *
 * In the block editor the SEO modal saves only a fixed subset of the meta
 * object, so this hook also keeps the snippet keys inside the edited
 * `_SAMAN_SEO_meta` value (and restores them after another field's update
 * replaces the object). In the classic editor the panel state is saved as
 * a whole, so the hook only reads/writes that state.
 *
 * @param {Object}   seoMeta    Panel state.
 * @param {Function} updateMeta Panel state setter ( field, value ).
 * @return {{values: Object, setValue: Function, isBlockEditor: boolean}} Values, setter and context.
 */
export const useSnippetMeta = ( seoMeta, updateMeta ) => {
	const editorMeta = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		if (
			! editor ||
			typeof editor.getCurrentPost !== 'function' ||
			typeof editor.getEditedPostAttribute !== 'function'
		) {
			return null;
		}
		const saved = editor.getCurrentPost()?.meta;
		// The meta key is only registered for some post types.
		if (
			! saved ||
			! Object.prototype.hasOwnProperty.call( saved, META_KEY )
		) {
			return null;
		}
		const edited = editor.getEditedPostAttribute( 'meta' ) || EMPTY;
		return {
			saved: saved[ META_KEY ] || EMPTY,
			edited: edited[ META_KEY ] || EMPTY,
		};
	}, [] );
	const registryDispatch = useDispatch();

	const values = {};
	SNIPPET_KEYS.forEach( ( key ) => {
		let raw = seoMeta ? seoMeta[ key ] : undefined;
		if ( raw === undefined && editorMeta ) {
			raw =
				editorMeta.edited[ key ] !== undefined
					? editorMeta.edited[ key ]
					: editorMeta.saved[ key ];
		}
		values[ key ] = normalize( key, raw );
	} );

	// Block editor only: make sure the edited meta object carries the
	// current snippet values.
	const valuesKey = SNIPPET_KEYS.map( ( key ) => values[ key ] ).join( '|' );
	useEffect( () => {
		if ( ! editorMeta ) {
			return;
		}
		const { edited } = editorMeta;
		const missing = SNIPPET_KEYS.some(
			( key ) => normalize( key, edited[ key ] ) !== values[ key ]
		);
		if ( ! missing ) {
			return;
		}
		const editor = registryDispatch( 'core/editor' );
		if ( editor && typeof editor.editPost === 'function' ) {
			editor.editPost( {
				meta: { [ META_KEY ]: { ...edited, ...values } },
			} );
		}
		// eslint-disable-next-line react-hooks/exhaustive-deps
	}, [ editorMeta, valuesKey, registryDispatch ] );

	const setValue = useCallback(
		( key, value ) => updateMeta( key, normalize( key, value ) ),
		[ updateMeta ]
	);

	return { values, setValue, isBlockEditor: !! editorMeta };
};

/**
 * Robots directives produced by the snippet values (for the preview).
 *
 * @param {Object} values Snippet values.
 * @return {string[]} Directives.
 */
export const getSnippetDirectives = ( values ) => {
	const directives = [];
	if ( values.nosnippet === '1' ) {
		directives.push( 'nosnippet' );
	} else if ( /^-?\d+$/.test( values.max_snippet || '' ) ) {
		directives.push(
			`max-snippet:${ Math.max(
				-1,
				parseInt( values.max_snippet, 10 )
			) }`
		);
	}
	if (
		[ 'none', 'standard', 'large' ].includes( values.max_image_preview )
	) {
		directives.push( `max-image-preview:${ values.max_image_preview }` );
	}
	if ( values.noimageindex === '1' ) {
		directives.push( 'noimageindex' );
	}
	return directives;
};

const AI_HELP = __(
	'Also limits how this page appears in Google AI Overviews/AI Mode.',
	'saman-seo'
);

/**
 * Snippet controls UI.
 *
 * @param {Object}   props               Props.
 * @param {Object}   props.values        Snippet values.
 * @param {Function} props.setValue      Setter ( key, value ).
 * @param {boolean}  props.isBlockEditor Whether the block editor is in use.
 * @return {Element} Controls.
 */
const SnippetControls = ( { values, setValue, isBlockEditor } ) => {
	const noSnippet = values.nosnippet === '1';

	return (
		<div className="saman-seo-robots-section saman-seo-snippet-controls">
			<div className="saman-seo-section-label">
				{ __( 'Snippets & AI search', 'saman-seo' ) }
			</div>
			<p className="saman-seo-field-help" style={ { marginBottom: 12 } }>
				{ __(
					'Leave a setting empty to use the site-wide default.',
					'saman-seo'
				) }
			</p>

			<label className="saman-seo-toggle" htmlFor="saman-seo-nosnippet">
				<span className="saman-seo-toggle-label">
					{ __( 'Hide text snippet', 'saman-seo' ) }
					<small>
						{ __( 'Add nosnippet.', 'saman-seo' ) } { AI_HELP }
					</small>
				</span>
				<input
					id="saman-seo-nosnippet"
					type="checkbox"
					checked={ noSnippet }
					onChange={ ( e ) =>
						setValue( 'nosnippet', e.target.checked )
					}
				/>
				<span className="saman-seo-toggle-slider"></span>
			</label>

			<div className="saman-seo-field">
				<div className="saman-seo-field-header">
					<label htmlFor="saman-seo-max-snippet">
						{ __( 'Max snippet length', 'saman-seo' ) }
					</label>
				</div>
				<input
					id="saman-seo-max-snippet"
					type="number"
					min="-1"
					step="1"
					className="saman-seo-field-input"
					value={ noSnippet ? '' : values.max_snippet }
					disabled={ noSnippet }
					placeholder={ __( 'Site default', 'saman-seo' ) }
					onChange={ ( e ) =>
						setValue( 'max_snippet', e.target.value )
					}
				/>
				<p className="saman-seo-field-help">
					{ noSnippet
						? __(
								'Not used while the text snippet is hidden.',
								'saman-seo'
						  )
						: __(
								'Characters. -1 = no limit, 0 = no snippet, empty = site default.',
								'saman-seo'
						  ) }{ ' ' }
					{ AI_HELP }
				</p>
			</div>

			<div className="saman-seo-field">
				<div className="saman-seo-field-header">
					<label htmlFor="saman-seo-max-image-preview">
						{ __( 'Max image preview', 'saman-seo' ) }
					</label>
				</div>
				<select
					id="saman-seo-max-image-preview"
					className="saman-seo-field-input"
					value={ values.max_image_preview }
					onChange={ ( e ) =>
						setValue( 'max_image_preview', e.target.value )
					}
				>
					<option value="">
						{ __( 'Site default', 'saman-seo' ) }
					</option>
					<option value="large">
						{ __( 'Large', 'saman-seo' ) }
					</option>
					<option value="standard">
						{ __( 'Standard', 'saman-seo' ) }
					</option>
					<option value="none">{ __( 'None', 'saman-seo' ) }</option>
				</select>
				<p className="saman-seo-field-help">
					{ __(
						'Largest image preview search engines may show for this page.',
						'saman-seo'
					) }{ ' ' }
					{ AI_HELP }
				</p>
			</div>

			<label
				className="saman-seo-toggle"
				htmlFor="saman-seo-noimageindex"
			>
				<span className="saman-seo-toggle-label">
					{ __( "Don't index images", 'saman-seo' ) }
					<small>
						{ __(
							'Add noimageindex: images on this page are not indexed.',
							'saman-seo'
						) }
					</small>
				</span>
				<input
					id="saman-seo-noimageindex"
					type="checkbox"
					checked={ values.noimageindex === '1' }
					onChange={ ( e ) =>
						setValue( 'noimageindex', e.target.checked )
					}
				/>
				<span className="saman-seo-toggle-slider"></span>
			</label>

			{ isBlockEditor && (
				<p className="saman-seo-field-help">
					{ __(
						'To keep only part of the content out of snippets, select the text in a block and choose "Exclude from snippets" in the toolbar.',
						'saman-seo'
					) }
				</p>
			) }
		</div>
	);
};

export default SnippetControls;
