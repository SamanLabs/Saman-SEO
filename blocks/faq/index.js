/**
 * Saman SEO FAQ Block
 *
 * Creates an FAQ section with automatic FAQPage schema markup.
 *
 * @package WPSEOPilot
 */

( function( blocks, element, blockEditor, components, i18n ) {
	const { registerBlockType } = blocks;
	const { createElement: el, Fragment, useState } = element;
	const { InspectorControls, RichText, useBlockProps } = blockEditor;
	const { PanelBody, ToggleControl, Button, TextControl } = components;
	const { __ } = i18n;

	registerBlockType( 'samanseo/faq', {
		title: __( 'FAQ', 'saman-seo' ),
		description: __( 'Add frequently asked questions with automatic schema markup for rich results.', 'saman-seo' ),
		category: 'widgets',
		icon: 'editor-help',
		keywords: [ 'faq', 'questions', 'answers', 'schema', 'seo' ],
		supports: {
			html: false,
		},
		attributes: {
			faqs: {
				type: 'array',
				default: [
					{ question: '', answer: '' }
				],
			},
			showSchema: {
				type: 'boolean',
				default: true,
			},
			style: {
				type: 'string',
				default: 'accordion',
			},
		},

		edit: function( props ) {
			const { attributes, setAttributes } = props;
			const { faqs, showSchema, style } = attributes;
			const blockProps = useBlockProps( { className: 'samanseo-faq-block' } );

			const updateFaq = ( index, field, value ) => {
				const newFaqs = [ ...faqs ];
				newFaqs[ index ] = { ...newFaqs[ index ], [ field ]: value };
				setAttributes( { faqs: newFaqs } );
			};

			const addFaq = () => {
				setAttributes( { faqs: [ ...faqs, { question: '', answer: '' } ] } );
			};

			const removeFaq = ( index ) => {
				const newFaqs = faqs.filter( ( _, i ) => i !== index );
				if ( newFaqs.length === 0 ) {
					newFaqs.push( { question: '', answer: '' } );
				}
				setAttributes( { faqs: newFaqs } );
			};

			const moveFaq = ( index, direction ) => {
				const newFaqs = [ ...faqs ];
				const newIndex = index + direction;
				if ( newIndex < 0 || newIndex >= faqs.length ) {
					return;
				}
				[ newFaqs[ index ], newFaqs[ newIndex ] ] = [ newFaqs[ newIndex ], newFaqs[ index ] ];
				setAttributes( { faqs: newFaqs } );
			};

			return el(
				Fragment,
				null,
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: __( 'FAQ Settings', 'saman-seo' ), initialOpen: true },
						el( ToggleControl, {
							label: __( 'Include FAQPage Schema', 'saman-seo' ),
							help: __( 'Add structured data for Google rich results.', 'saman-seo' ),
							checked: showSchema,
							onChange: function( value ) {
								setAttributes( { showSchema: value } );
							},
						} )
					)
				),
				el(
					'div',
					blockProps,
					el( 'div', { className: 'samanseo-faq-header' },
						el( 'span', { className: 'samanseo-faq-icon' }, '?'),
						el( 'span', { className: 'samanseo-faq-label' }, __( 'FAQ Block', 'saman-seo' ) ),
						showSchema && el( 'span', { className: 'samanseo-faq-badge' }, __( 'Schema Enabled', 'saman-seo' ) )
					),
					el(
						'div',
						{ className: 'samanseo-faq-items' },
						faqs.map( ( faq, index ) =>
							el(
								'div',
								{ key: index, className: 'samanseo-faq-item' },
								el(
									'div',
									{ className: 'samanseo-faq-item-header' },
									el( 'span', { className: 'samanseo-faq-number' }, ( index + 1 ) + '.' ),
									el(
										'div',
										{ className: 'samanseo-faq-controls' },
										el( Button, {
											icon: 'arrow-up-alt2',
											label: __( 'Move up', 'saman-seo' ),
											onClick: () => moveFaq( index, -1 ),
											disabled: index === 0,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'arrow-down-alt2',
											label: __( 'Move down', 'saman-seo' ),
											onClick: () => moveFaq( index, 1 ),
											disabled: index === faqs.length - 1,
											isSmall: true,
										} ),
										el( Button, {
											icon: 'trash',
											label: __( 'Remove', 'saman-seo' ),
											onClick: () => removeFaq( index ),
											isSmall: true,
											isDestructive: true,
										} )
									)
								),
								el( RichText, {
									tagName: 'div',
									className: 'samanseo-faq-question',
									placeholder: __( 'Enter question...', 'saman-seo' ),
									value: faq.question,
									onChange: ( value ) => updateFaq( index, 'question', value ),
									allowedFormats: [],
								} ),
								el( RichText, {
									tagName: 'div',
									className: 'samanseo-faq-answer',
									placeholder: __( 'Enter answer...', 'saman-seo' ),
									value: faq.answer,
									onChange: ( value ) => updateFaq( index, 'answer', value ),
									allowedFormats: [ 'core/bold', 'core/italic', 'core/link' ],
								} )
							)
						)
					),
					el(
						Button,
						{
							className: 'samanseo-faq-add',
							icon: 'plus-alt2',
							onClick: addFaq,
						},
						__( 'Add Question', 'saman-seo' )
					)
				)
			);
		},

		save: function( { attributes } ) {
			const { faqs, showSchema, style } = attributes;
			const blockProps = useBlockProps.save( { className: 'samanseo-faq' } );

			// Filter out empty FAQs
			const validFaqs = faqs.filter( faq => faq.question && faq.answer );

			if ( validFaqs.length === 0 ) {
				return null;
			}

			// Build schema
			const schema = showSchema ? {
				'@context': 'https://schema.org',
				'@type': 'FAQPage',
				'mainEntity': validFaqs.map( faq => ( {
					'@type': 'Question',
					'name': faq.question.replace( /<[^>]*>/g, '' ),
					'acceptedAnswer': {
						'@type': 'Answer',
						'text': faq.answer.replace( /<[^>]*>/g, '' ),
					}
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
					{ className: 'samanseo-faq-list', itemScope: true, itemType: 'https://schema.org/FAQPage' },
					validFaqs.map( ( faq, index ) =>
						el(
							'details',
							{
								key: index,
								className: 'samanseo-faq-item',
								itemScope: true,
								itemProp: 'mainEntity',
								itemType: 'https://schema.org/Question',
							},
							el(
								'summary',
								{ className: 'samanseo-faq-question', itemProp: 'name' },
								el( RichText.Content, { value: faq.question } )
							),
							el(
								'div',
								{
									className: 'samanseo-faq-answer',
									itemScope: true,
									itemProp: 'acceptedAnswer',
									itemType: 'https://schema.org/Answer',
								},
								el(
									'div',
									{ itemProp: 'text' },
									el( RichText.Content, { value: faq.answer } )
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
