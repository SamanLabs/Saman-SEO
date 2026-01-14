/**
 * WP SEO Pilot HowTo Block
 *
 * Creates a step-by-step guide with automatic HowTo schema markup.
 *
 * @package WPSEOPilot
 */

( function( blocks, element, blockEditor, components, i18n ) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment } = element;
	const { InspectorControls, RichText, MediaUpload, MediaUploadCheck, useBlockProps } = blockEditor;
	const { PanelBody, ToggleControl, TextControl, Button } = components;
	const { __ } = i18n;

	registerBlockType( 'wpseopilot/howto', {
		title: __( 'How To', 'wp-seo-pilot' ),
		description: __( 'Create step-by-step instructions with automatic schema markup for rich results.', 'wp-seo-pilot' ),
		category: 'widgets',
		icon: 'list-view',
		keywords: [ 'howto', 'how to', 'steps', 'instructions', 'schema', 'seo', 'tutorial' ],
		supports: {
			html: false,
		},
		attributes: {
			title: {
				type: 'string',
				default: '',
			},
			description: {
				type: 'string',
				default: '',
			},
			totalTime: {
				type: 'string',
				default: '',
			},
			estimatedCost: {
				type: 'string',
				default: '',
			},
			currency: {
				type: 'string',
				default: 'USD',
			},
			steps: {
				type: 'array',
				default: [
					{ title: '', description: '', image: null }
				],
			},
			tools: {
				type: 'array',
				default: [],
			},
			supplies: {
				type: 'array',
				default: [],
			},
			showSchema: {
				type: 'boolean',
				default: true,
			},
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { title, description, totalTime, estimatedCost, currency, steps, tools, supplies, showSchema } = attributes;
			const blockProps = useBlockProps( { className: 'wpseopilot-howto-block' } );

			const updateStep = ( index, field, value ) => {
				const newSteps = [ ...steps ];
				newSteps[ index ] = { ...newSteps[ index ], [ field ]: value };
				setAttributes( { steps: newSteps } );
			};

			const addStep = () => {
				setAttributes( { steps: [ ...steps, { title: '', description: '', image: null } ] } );
			};

			const removeStep = ( index ) => {
				const newSteps = steps.filter( ( _, i ) => i !== index );
				if ( newSteps.length === 0 ) {
					newSteps.push( { title: '', description: '', image: null } );
				}
				setAttributes( { steps: newSteps } );
			};

			const moveStep = ( index, direction ) => {
				const newSteps = [ ...steps ];
				const newIndex = index + direction;
				if ( newIndex < 0 || newIndex >= steps.length ) {
					return;
				}
				[ newSteps[ index ], newSteps[ newIndex ] ] = [ newSteps[ newIndex ], newSteps[ index ] ];
				setAttributes( { steps: newSteps } );
			};

			const updateTools = ( value ) => {
				setAttributes( { tools: value.split( '\n' ).filter( t => t.trim() ) } );
			};

			const updateSupplies = ( value ) => {
				setAttributes( { supplies: value.split( '\n' ).filter( s => s.trim() ) } );
			};

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'HowTo Settings', 'wp-seo-pilot' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Include HowTo Schema', 'wp-seo-pilot' ),
							help: __( 'Add structured data for Google rich results.', 'wp-seo-pilot' ),
							checked: showSchema,
							onChange: function( value ) {
								setAttributes( { showSchema: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Total Time (e.g., 30 minutes)', 'wp-seo-pilot' ),
							value: totalTime,
							onChange: ( value ) => setAttributes( { totalTime: value } ),
						} ),
						el( TextControl, {
							label: __( 'Estimated Cost', 'wp-seo-pilot' ),
							value: estimatedCost,
							onChange: ( value ) => setAttributes( { estimatedCost: value } ),
							type: 'number',
						} ),
						el( TextControl, {
							label: __( 'Currency', 'wp-seo-pilot' ),
							value: currency,
							onChange: ( value ) => setAttributes( { currency: value } ),
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Tools & Supplies', 'wp-seo-pilot' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Tools Required (one per line)', 'wp-seo-pilot' ),
							value: tools.join( '\n' ),
							onChange: updateTools,
							help: __( 'List each tool on a new line', 'wp-seo-pilot' ),
						} ),
						el( TextControl, {
							label: __( 'Supplies Needed (one per line)', 'wp-seo-pilot' ),
							value: supplies.join( '\n' ),
							onChange: updateSupplies,
							help: __( 'List each supply/material on a new line', 'wp-seo-pilot' ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'div', { className: 'wpseopilot-howto-header' },
						el( 'span', { className: 'wpseopilot-howto-icon' }, '1-2-3' ),
						el( 'span', { className: 'wpseopilot-howto-label' }, __( 'How-To Block', 'wp-seo-pilot' ) ),
						showSchema && el( 'span', { className: 'wpseopilot-howto-badge' }, __( 'Schema Enabled', 'wp-seo-pilot' ) )
					),
					el( RichText, {
						tagName: 'h3',
						className: 'wpseopilot-howto-title',
						placeholder: __( 'How to... (enter title)', 'wp-seo-pilot' ),
						value: title,
						onChange: ( value ) => setAttributes( { title: value } ),
						allowedFormats: [],
					} ),
					el( RichText, {
						tagName: 'p',
						className: 'wpseopilot-howto-description',
						placeholder: __( 'Brief description of what this guide covers...', 'wp-seo-pilot' ),
						value: description,
						onChange: ( value ) => setAttributes( { description: value } ),
						allowedFormats: [ 'core/bold', 'core/italic' ],
					} ),
					( tools.length > 0 || supplies.length > 0 ) && el(
						'div',
						{ className: 'wpseopilot-howto-meta' },
						tools.length > 0 && el(
							'div',
							{ className: 'wpseopilot-howto-tools' },
							el( 'strong', null, __( 'Tools: ', 'wp-seo-pilot' ) ),
							tools.join( ', ' )
						),
						supplies.length > 0 && el(
							'div',
							{ className: 'wpseopilot-howto-supplies' },
							el( 'strong', null, __( 'Supplies: ', 'wp-seo-pilot' ) ),
							supplies.join( ', ' )
						)
					),
					el(
						'ol',
						{ className: 'wpseopilot-howto-steps' },
						steps.map( ( step, index ) =>
							el(
								'li',
								{ key: index, className: 'wpseopilot-howto-step' },
								el(
									'div',
									{ className: 'wpseopilot-howto-step-header' },
									el( 'span', { className: 'wpseopilot-howto-step-number' }, __( 'Step', 'wp-seo-pilot' ) + ' ' + ( index + 1 ) ),
									el(
										'div',
										{ className: 'wpseopilot-howto-controls' },
										el( Button, {
											icon: 'arrow-up-alt2',
											label: __( 'Move up', 'wp-seo-pilot' ),
											onClick: () => moveStep( index, -1 ),
											disabled: index === 0,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'arrow-down-alt2',
											label: __( 'Move down', 'wp-seo-pilot' ),
											onClick: () => moveStep( index, 1 ),
											disabled: index === steps.length - 1,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'trash',
											label: __( 'Remove', 'wp-seo-pilot' ),
											onClick: () => removeStep( index ),
											isSmall: true,
											isDestructive: true,
										} )
									)
								),
								el( RichText, {
									tagName: 'div',
									className: 'wpseopilot-howto-step-title',
									placeholder: __( 'Step title...', 'wp-seo-pilot' ),
									value: step.title,
									onChange: ( value ) => updateStep( index, 'title', value ),
									allowedFormats: [],
								} ),
								el( RichText, {
									tagName: 'div',
									className: 'wpseopilot-howto-step-description',
									placeholder: __( 'Step instructions...', 'wp-seo-pilot' ),
									value: step.description,
									onChange: ( value ) => updateStep( index, 'description', value ),
									allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
								} ),
								el(
									MediaUploadCheck,
									null,
									el( MediaUpload, {
										onSelect: ( media ) => updateStep( index, 'image', media.url ),
										allowedTypes: [ 'image' ],
										render: ( { open } ) => el(
											'div',
											{ className: 'wpseopilot-howto-step-image' },
											step.image
												? el(
													Fragment,
													null,
													el( 'img', { src: step.image, alt: step.title } ),
													el( Button, {
														isSmall: true,
														isDestructive: true,
														onClick: () => updateStep( index, 'image', null ),
													}, __( 'Remove', 'wp-seo-pilot' ) )
												)
												: el( Button, {
													isSmall: true,
													onClick: open,
												}, __( 'Add Image (optional)', 'wp-seo-pilot' ) )
										),
									} )
								)
							)
						)
					),
					el(
						Button,
						{
							className: 'wpseopilot-howto-add',
							icon: 'plus-alt2',
							onClick: addStep,
						},
						__( 'Add Step', 'wp-seo-pilot' )
					)
				)
			);
		},

		save: function( { attributes } ) {
			const { title, description, totalTime, estimatedCost, currency, steps, tools, supplies, showSchema } = attributes;
			const blockProps = useBlockProps.save( { className: 'wpseopilot-howto' } );

			// Filter out empty steps
			const validSteps = steps.filter( step => step.title || step.description );

			if ( validSteps.length === 0 ) {
				return null;
			}

			// Parse time to ISO 8601 duration
			const parseTime = ( timeStr ) => {
				if ( ! timeStr ) return null;
				const match = timeStr.match( /(\d+)\s*(min|hour|h|m)/i );
				if ( match ) {
					const num = parseInt( match[ 1 ] );
					const unit = match[ 2 ].toLowerCase();
					if ( unit === 'h' || unit === 'hour' ) {
						return 'PT' + num + 'H';
					}
					return 'PT' + num + 'M';
				}
				return null;
			};

			// Build schema
			const schema = showSchema ? {
				'@context': 'https://schema.org',
				'@type': 'HowTo',
				'name': title.replace( /<[^>]*>/g, '' ),
				'description': description.replace( /<[^>]*>/g, '' ),
				...( parseTime( totalTime ) && { 'totalTime': parseTime( totalTime ) } ),
				...( estimatedCost && {
					'estimatedCost': {
						'@type': 'MonetaryAmount',
						'currency': currency,
						'value': estimatedCost,
					}
				} ),
				...( tools.length > 0 && {
					'tool': tools.map( t => ( { '@type': 'HowToTool', 'name': t } ) )
				} ),
				...( supplies.length > 0 && {
					'supply': supplies.map( s => ( { '@type': 'HowToSupply', 'name': s } ) )
				} ),
				'step': validSteps.map( ( step, index ) => ( {
					'@type': 'HowToStep',
					'position': index + 1,
					'name': step.title.replace( /<[^>]*>/g, '' ),
					'text': step.description.replace( /<[^>]*>/g, '' ),
					...( step.image && { 'image': step.image } ),
				} ) )
			} : null;

			return el(
				'div',
				blockProps,
				showSchema && el(
					'script',
					{ type: 'application/ld+json' },
					JSON.stringify( schema )
				),
				el(
					'div',
					{ className: 'wpseopilot-howto-content', itemScope: true, itemType: 'https://schema.org/HowTo' },
					title && el(
						'h3',
						{ className: 'wpseopilot-howto-title', itemProp: 'name' },
						el( RichText.Content, { value: title } )
					),
					description && el(
						'p',
						{ className: 'wpseopilot-howto-description', itemProp: 'description' },
						el( RichText.Content, { value: description } )
					),
					( tools.length > 0 || supplies.length > 0 || totalTime || estimatedCost ) && el(
						'div',
						{ className: 'wpseopilot-howto-meta' },
						totalTime && el(
							'span',
							{ className: 'wpseopilot-howto-time' },
							__( 'Time: ', 'wp-seo-pilot' ),
							el( 'span', { itemProp: 'totalTime', content: parseTime( totalTime ) }, totalTime )
						),
						estimatedCost && el(
							'span',
							{ className: 'wpseopilot-howto-cost', itemProp: 'estimatedCost', itemScope: true, itemType: 'https://schema.org/MonetaryAmount' },
							__( 'Cost: ', 'wp-seo-pilot' ),
							el( 'span', { itemProp: 'value' }, estimatedCost ),
							el( 'span', { itemProp: 'currency', content: currency }, ' ' + currency )
						),
						tools.length > 0 && el(
							'div',
							{ className: 'wpseopilot-howto-tools' },
							el( 'strong', null, __( 'Tools: ', 'wp-seo-pilot' ) ),
							tools.map( ( tool, i ) => el( 'span', { key: i, itemProp: 'tool', itemScope: true, itemType: 'https://schema.org/HowToTool' },
								el( 'span', { itemProp: 'name' }, tool ),
								i < tools.length - 1 ? ', ' : ''
							) )
						),
						supplies.length > 0 && el(
							'div',
							{ className: 'wpseopilot-howto-supplies' },
							el( 'strong', null, __( 'Supplies: ', 'wp-seo-pilot' ) ),
							supplies.map( ( supply, i ) => el( 'span', { key: i, itemProp: 'supply', itemScope: true, itemType: 'https://schema.org/HowToSupply' },
								el( 'span', { itemProp: 'name' }, supply ),
								i < supplies.length - 1 ? ', ' : ''
							) )
						)
					),
					el(
						'ol',
						{ className: 'wpseopilot-howto-steps' },
						validSteps.map( ( step, index ) =>
							el(
								'li',
								{
									key: index,
									className: 'wpseopilot-howto-step',
									itemProp: 'step',
									itemScope: true,
									itemType: 'https://schema.org/HowToStep',
								},
								el( 'meta', { itemProp: 'position', content: index + 1 } ),
								step.image && el( 'img', {
									className: 'wpseopilot-howto-step-image',
									src: step.image,
									alt: step.title.replace( /<[^>]*>/g, '' ),
									itemProp: 'image',
								} ),
								step.title && el(
									'strong',
									{ className: 'wpseopilot-howto-step-title', itemProp: 'name' },
									el( RichText.Content, { value: step.title } )
								),
								step.description && el(
									'div',
									{ className: 'wpseopilot-howto-step-description', itemProp: 'text' },
									el( RichText.Content, { value: step.description } )
								)
							)
						)
					)
				)
			);
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
