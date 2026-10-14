/**
 * "Exclude from snippets" rich-text format.
 *
 * Wraps the selected text in <span data-nosnippet>, which tells Google not
 * to show that text in search snippets, AI Overviews or AI Mode. WordPress
 * KSES already allows data-* attributes on span/div/section for every role.
 *
 * The block-editor packages are read from the wp.* globals at runtime
 * instead of being imported: SEOPanel is shared with the classic-editor
 * bundle, and a static import would make that bundle depend on (and load)
 * the whole block editor. Outside the block editor this is a no-op.
 */

import { __ } from '@wordpress/i18n';

export const NOSNIPPET_FORMAT = 'saman-seo/nosnippet';

/**
 * Register the format type once, when the block editor is present.
 *
 * @return {boolean} Whether the format is registered.
 */
export const registerNoSnippetFormat = () => {
	const wp = window.wp || {};
	const richText = wp.richText;
	const blockEditor = wp.blockEditor;

	if (
		! richText ||
		! blockEditor ||
		typeof richText.registerFormatType !== 'function' ||
		typeof richText.toggleFormat !== 'function' ||
		! blockEditor.RichTextToolbarButton
	) {
		return false;
	}

	const formatStore = wp.data && wp.data.select( 'core/rich-text' );
	if ( formatStore && formatStore.getFormatType( NOSNIPPET_FORMAT ) ) {
		return true;
	}

	const { toggleFormat } = richText;
	const { RichTextToolbarButton } = blockEditor;
	const title = __( 'Exclude from snippets', 'saman-seo' );

	const Edit = ( { isActive, value, onChange } ) => (
		<RichTextToolbarButton
			icon="hidden"
			title={ title }
			isActive={ isActive }
			onClick={ () =>
				onChange(
					toggleFormat( value, {
						type: NOSNIPPET_FORMAT,
						attributes: { nosnippet: 'true' },
					} )
				)
			}
		/>
	);

	richText.registerFormatType( NOSNIPPET_FORMAT, {
		title,
		tagName: 'span',
		className: 'saman-seo-nosnippet',
		attributes: {
			nosnippet: 'data-nosnippet',
		},
		edit: Edit,
	} );

	return true;
};
