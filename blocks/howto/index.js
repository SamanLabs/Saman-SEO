/**
 * Saman SEO HowTo Block
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

	registerBlockType( 'samanseo/howto', {
		title: __( 'How To', 'saman-seo' ),
		description: __( 'Create step-by-step instructions with automatic schema markup for rich results.', 'saman-seo' ),
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
			const blockProps = useBlockProps( { className: 'samanseo-howto-block' } );

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
						{ title: __( 'HowTo Settings', 'saman-seo' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Include HowTo Schema', 'saman-seo' ),
							help: __( 'Add structured data for Google rich results.', 'saman-seo' ),
							checked: showSchema,
							onChange: function( value ) {
								setAttributes( { showSchema: value } );
							},
						} ),
						el( TextControl, {
							label: __( 'Total Time (e.g., 30 minutes)', 'saman-seo' ),
							value: totalTime,
							onChange: ( value ) => setAttributes( { totalTime: value } ),
						} ),
						el( TextControl, {
							label: __( 'Estimated Cost', 'saman-seo' ),
							value: estimatedCost,
							onChange: ( value ) => setAttributes( { estimatedCost: value } ),
							type: 'number',
						} ),
						el( TextControl, {
							label: __( 'Currency', 'saman-seo' ),
							value: currency,
							onChange: ( value ) => setAttributes( { currency: value } ),
						} )
					),
					el(
						PanelBody,
						{ title: __( 'Tools & Supplies', 'saman-seo' ), initialOpen: false },
						el( TextControl, {
							label: __( 'Tools Required (one per line)', 'saman-seo' ),
							value: tools.join( '\n' ),
							onChange: updateTools,
							help: __( 'List each tool on a new line', 'saman-seo' ),
						} ),
						el( TextControl, {
							label: __( 'Supplies Needed (one per line)', 'saman-seo' ),
							value: supplies.join( '\n' ),
							onChange: updateSupplies,
							help: __( 'List each supply/material on a new line', 'saman-seo' ),
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'div', { className: 'samanseo-howto-header' },
						el( 'span', { className: 'samanseo-howto-icon' }, '1-2-3' ),
						el( 'span', { className: 'samanseo-howto-label' }, __( 'How-To Block', 'saman-seo' ) ),
						showSchema && el( 'span', { className: 'samanseo-howto-badge' }, __( 'Schema Enabled', 'saman-seo' ) )
					),
					el( RichText, {
						tagName: 'h3',
						className: 'samanseo-howto-title',
						placeholder: __( 'How to... (enter title)', 'saman-seo' ),
						value: title,
						onChange: ( value ) => setAttributes( { title: value } ),
						allowedFormats: [],
					} ),
					el( RichText, {
						tagName: 'p',
						className: 'samanseo-howto-description',
						placeholder: __( 'Brief description of what this guide covers...', 'saman-seo' ),
						value: description,
						onChange: ( value ) => setAttributes( { description: value } ),
						allowedFormats: [ 'core/bold', 'core/italic' ],
					} ),
					( tools.length > 0 || supplies.length > 0 ) && el(
						'div',
						{ className: 'samanseo-howto-meta' },
						tools.length > 0 && el(
							'div',
							{ className: 'samanseo-howto-tools' },
							el( 'strong', null, __( 'Tools: ', 'saman-seo' ) ),
							tools.join( ', ' )
						),
						supplies.length > 0 && el(
							'div',
							{ className: 'samanseo-howto-supplies' },
							el( 'strong', null, __( 'Supplies: ', 'saman-seo' ) ),
							supplies.join( ', ' )
						)
					),
					el(
						'ol',
						{ className: 'samanseo-howto-steps' },
						steps.map( ( step, index ) =>
							el(
								'li',
								{ key: index, className: 'samanseo-howto-step' },
								el(
									'div',
									{ className: 'samanseo-howto-step-header' },
									el( 'span', { className: 'samanseo-howto-step-number' }, __( 'Step', 'saman-seo' ) + ' ' + ( index + 1 ) ),
									el(
										'div',
										{ className: 'samanseo-howto-controls' },
										el( Button, {
											icon: 'arrow-up-alt2',
											label: __( 'Move up', 'saman-seo' ),
											onClick: () => moveStep( index, -1 ),
											disabled: index === 0,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'arrow-down-alt2',
											label: __( 'Move down', 'saman-seo' ),
											onClick: () => moveStep( index, 1 ),
											disabled: index === steps.length - 1,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'trash',
											label: __( 'Remove', 'saman-seo' ),
											onClick: () => removeStep( index ),
											isSmall: true,
											isDestructive: true,
										} )
									)
								),
								el( RichText, {
									tagName: 'div',
									className: 'samanseo-howto-step-title',
									placeholder: __( 'Step title...', 'saman-seo' ),
									value: step.title,
									onChange: ( value ) => updateStep( index, 'title', value ),
									allowedFormats: [],
								} ),
								el( RichText, {
									tagName: 'div',
									className: 'samanseo-howto-step-description',
									placeholder: __( 'Step instructions...', 'saman-seo' ),
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
											{ className: 'samanseo-howto-step-image' },
											step.image
												? el(
													Fragment,
													null,
													el( 'img', { src: step.image, alt: step.title } ),
													el( Button, {
														isSmall: true,
														isDestructive: true,
														onClick: () => updateStep( index, 'image', null ),
													}, __( 'Remove', 'saman-seo' ) )
												)
												: el( Button, {
													isSmall: true,
													onClick: open,
												}, __( 'Add Image (optional)', 'saman-seo' ) )
										),
									} )
								)
							)
						)
					),
					el(
						Button,
						{
							className: 'samanseo-howto-add',
							icon: 'plus-alt2',
							onClick: addStep,
						},
						__( 'Add Step', 'saman-seo' )
					)
				)
			);
		},

		save: function( { attributes } ) {
			const { title, description, totalTime, estimatedCost, currency, steps, tools, supplies, showSchema } = attributes;
			const blockProps = useBlockProps.save( { className: 'samanseo-howto' } );

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
					{ className: 'samanseo-howto-content', itemScope: true, itemType: 'https://schema.org/HowTo' },
					title && el(
						'h3',
						{ className: 'samanseo-howto-title', itemProp: 'name' },
						el( RichText.Content, { value: title } )
					),
					description && el(
						'p',
						{ className: 'samanseo-howto-description', itemProp: 'description' },
						el( RichText.Content, { value: description } )
					),
					( tools.length > 0 || supplies.length > 0 || totalTime || estimatedCost ) && el(
						'div',
						{ className: 'samanseo-howto-meta' },
						totalTime && el(
							'span',
							{ className: 'samanseo-howto-time' },
							__( 'Time: ', 'saman-seo' ),
							el( 'span', { itemProp: 'totalTime', content: parseTime( totalTime ) }, totalTime )
						),
						estimatedCost && el(
							'span',
							{ className: 'samanseo-howto-cost', itemProp: 'estimatedCost', itemScope: true, itemType: 'https://schema.org/MonetaryAmount' },
							__( 'Cost: ', 'saman-seo' ),
							el( 'span', { itemProp: 'value' }, estimatedCost ),
							el( 'span', { itemProp: 'currency', content: currency }, ' ' + currency )
						),
						tools.length > 0 && el(
							'div',
							{ className: 'samanseo-howto-tools' },
							el( 'strong', null, __( 'Tools: ', 'saman-seo' ) ),
							tools.map( ( tool, i ) => el( 'span', { key: i, itemProp: 'tool', itemScope: true, itemType: 'https://schema.org/HowToTool' },
								el( 'span', { itemProp: 'name' }, tool ),
								i < tools.length - 1 ? ', ' : ''
							) )
						),
						supplies.length > 0 && el(
							'div',
							{ className: 'samanseo-howto-supplies' },
							el( 'strong', null, __( 'Supplies: ', 'saman-seo' ) ),
							supplies.map( ( supply, i ) => el( 'span', { key: i, itemProp: 'supply', itemScope: true, itemType: 'https://schema.org/HowToSupply' },
								el( 'span', { itemProp: 'name' }, supply ),
								i < supplies.length - 1 ? ', ' : ''
							) )
						)
					),
					el(
						'ol',
						{ className: 'samanseo-howto-steps' },
						validSteps.map( ( step, index ) =>
							el(
								'li',
								{
									key: index,
									className: 'samanseo-howto-step',
									itemProp: 'step',
									itemScope: true,
									itemType: 'https://schema.org/HowToStep',
								},
								el( 'meta', { itemProp: 'position', content: index + 1 } ),
								step.image && el( 'img', {
									className: 'samanseo-howto-step-image',
									src: step.image,
									alt: step.title.replace( /<[^>]*>/g, '' ),
									itemProp: 'image',
								} ),
								step.title && el(
									'strong',
									{ className: 'samanseo-howto-step-title', itemProp: 'name' },
									el( RichText.Content, { value: step.title } )
								),
								step.description && el(
									'div',
									{ className: 'samanseo-howto-step-description', itemProp: 'text' },
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
