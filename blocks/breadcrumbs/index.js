/**
 * Saman SEO Breadcrumbs Block
 *
 * @package WPSEOPilot
 */

( function( blocks, element, blockEditor, components, serverSideRender, i18n ) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { InspectorControls, useBlockProps } = blockEditor;
	const { PanelBody, ToggleControl, SelectControl, TextControl, Placeholder } = components;
	const ServerSideRender = serverSideRender;
	const { __ } = i18n;

	registerBlockType( 'samanseo/breadcrumbs', {
		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { showHome, homeLabel, separator, showCurrent, linkCurrent, stylePreset } = attributes;
			const blockProps = useBlockProps();

			const separatorOptions = [
				{ value: '', label: __( 'Default (from settings)', 'saman-seo' ) },
				{ value: '>', label: '>' },
				{ value: '/', label: '/' },
				{ value: '|', label: '|' },
				{ value: '-', label: '-' },
				{ value: 'arrow', label: __( 'Arrow', 'saman-seo' ) + ' (→)' },
				{ value: 'chevron', label: __( 'Chevron', 'saman-seo' ) + ' (»)' },
			];

			const styleOptions = [
				{ value: '', label: __( 'Default (from settings)', 'saman-seo' ) },
				{ value: 'default', label: __( 'Default', 'saman-seo' ) },
				{ value: 'minimal', label: __( 'Minimal', 'saman-seo' ) },
				{ value: 'rounded', label: __( 'Rounded', 'saman-seo' ) },
				{ value: 'pills', label: __( 'Pills', 'saman-seo' ) },
				{ value: 'none', label: __( 'No Styling', 'saman-seo' ) },
			];

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'Breadcrumb Settings', 'saman-seo' ), initialOpen: true },
						el( SelectControl, {
							label: __( 'Separator', 'saman-seo' ),
							value: separator,
							options: separatorOptions,
							onChange: function( value ) {
								setAttributes( { separator: value } );
							},
						} ),
						el( SelectControl, {
							label: __( 'Style Preset', 'saman-seo' ),
							value: stylePreset,
							options: styleOptions,
							onChange: function( value ) {
								setAttributes( { stylePreset: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Home Link', 'saman-seo' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Home Link', 'saman-seo' ),
							checked: showHome,
							onChange: function( value ) {
								setAttributes( { showHome: value } );
							},
						} ),
						showHome && el( TextControl, {
							label: __( 'Custom Home Label', 'saman-seo' ),
							value: homeLabel,
							placeholder: __( 'Home', 'saman-seo' ),
							onChange: function( value ) {
								setAttributes( { homeLabel: value } );
							},
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Current Page', 'saman-seo' ), initialOpen: false },
						el( ToggleControl, {
							label: __( 'Show Current Page', 'saman-seo' ),
							checked: showCurrent,
							onChange: function( value ) {
								setAttributes( { showCurrent: value } );
							},
						} ),
						showCurrent && el( ToggleControl, {
							label: __( 'Link Current Page', 'saman-seo' ),
							checked: linkCurrent,
							onChange: function( value ) {
								setAttributes( { linkCurrent: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( Placeholder, {
						icon: 'admin-links',
						label: __( 'SEO Breadcrumbs', 'saman-seo' ),
						instructions: __( 'Breadcrumb navigation will be displayed here based on the current page context.', 'saman-seo' ),
					},
					el(
						'div',
						{ className: 'samanseo-breadcrumbs-preview' },
						el( 'span', null, __( 'Home', 'saman-seo' ) ),
						el( 'span', { className: 'samanseo-breadcrumbs-preview__sep' }, ' ' + ( separator || '>' ) + ' ' ),
						el( 'span', null, __( 'Category', 'saman-seo' ) ),
						el( 'span', { className: 'samanseo-breadcrumbs-preview__sep' }, ' ' + ( separator || '>' ) + ' ' ),
						el( 'span', { style: { opacity: 0.7 } }, __( 'Current Page', 'saman-seo' ) )
					) )
				)
			);
		},

		save: function() {
			// Dynamic block - rendered via PHP
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.serverSideRender,
	window.wp.i18n
);
