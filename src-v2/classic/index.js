/**
 * Saman SEO - Classic Editor Modal
 *
 * Renders the same SEO popup used in the Gutenberg editor, but driven by the
 * classic editor DOM. An "Edit SEO" button in the Publish box opens the modal;
 * changes are written to a hidden form field and saved with the post.
 */

import { createRoot, useState, useEffect, useCallback } from '@wordpress/element';
import { Modal } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import apiFetch from '@wordpress/api-fetch';
import SEOPanel from '../editor/components/SEOPanel';
import { renderTemplatePreview } from '../utils/template';
import '../editor/editor.css';

const data = window.SamanSEOClassic || {};

/**
 * Map stored meta (string flags) to the panel's working shape (booleans).
 *
 * @param {Object} m Stored meta.
 * @return {Object} Panel meta.
 */
const mapInitial = ( m = {} ) => ( {
	title: m.title || '',
	description: m.description || '',
	canonical: m.canonical || '',
	noindex: m.noindex === '1',
	nofollow: m.nofollow === '1',
	og_image: m.og_image || '',
	focus_keyphrase: m.focus_keyphrase || '',
	secondary_keyphrases: Array.isArray( m.secondary_keyphrases )
		? m.secondary_keyphrases
		: [],
	schema_type: m.schema_type || '',
	custom_schema: m.custom_schema || '',
} );

/**
 * Convert the panel's working shape back to the stored format for saving.
 *
 * @param {Object} s Panel meta.
 * @return {Object} Stored meta.
 */
const toStorage = ( s ) => ( {
	title: s.title || '',
	description: s.description || '',
	canonical: s.canonical || '',
	noindex: s.noindex ? '1' : '',
	nofollow: s.nofollow ? '1' : '',
	og_image: s.og_image || '',
	focus_keyphrase: s.focus_keyphrase || '',
	secondary_keyphrases: Array.isArray( s.secondary_keyphrases )
		? s.secondary_keyphrases
		: [],
	schema_type: s.schema_type || '',
	custom_schema: s.custom_schema || '',
} );

/**
 * Read the live post title from the classic editor.
 *
 * @return {string} Title.
 */
const getPostTitle = () => {
	const el = document.getElementById( 'title' );
	return el ? el.value : data.postTitle || '';
};

/**
 * Read the live post content from TinyMCE (visual) or the textarea (text).
 *
 * @return {string} Content.
 */
const getPostContent = () => {
	if ( window.tinymce ) {
		const editor = window.tinymce.get( 'content' );
		if ( editor && ! editor.isHidden() ) {
			return editor.getContent();
		}
	}
	const textarea = document.getElementById( 'content' );
	return textarea ? textarea.value : '';
};

/**
 * Read the post excerpt field if present.
 *
 * @return {string} Excerpt.
 */
const getPostExcerpt = () => {
	const el = document.getElementById( 'excerpt' );
	return el ? el.value : '';
};

const ClassicSEOModal = () => {
	const [ isOpen, setIsOpen ] = useState( false );
	const [ seoMeta, setSeoMeta ] = useState( mapInitial( data.meta ) );
	const [ seoScore, setSeoScore ] = useState( null );
	const [ hasChanges, setHasChanges ] = useState( false );

	// Open the modal when the Publish-box button is clicked.
	useEffect( () => {
		const btn = document.getElementById( 'saman-seo-open-modal' );
		if ( ! btn ) {
			return;
		}
		const handler = ( e ) => {
			e.preventDefault();
			setIsOpen( true );
		};
		btn.addEventListener( 'click', handler );
		return () => btn.removeEventListener( 'click', handler );
	}, [] );

	// Keep the hidden form field in sync so changes save with the post.
	useEffect( () => {
		const input = document.getElementById( 'saman-seo-meta-json' );
		if ( input ) {
			input.value = JSON.stringify( toStorage( seoMeta ) );
		}
	}, [ seoMeta ] );

	// Fetch the SEO score (debounced) while the modal is in use.
	useEffect( () => {
		if ( ! data.postId || ! isOpen ) {
			return;
		}
		const timer = setTimeout( () => {
			apiFetch( {
				path: `/saman-seo/v1/audit/post/${ data.postId }`,
			} )
				.then( ( response ) => {
					if ( response && response.success && response.data ) {
						setSeoScore( response.data );
					}
				} )
				.catch( () => {} );
		}, 500 );
		return () => clearTimeout( timer );
	}, [ isOpen, seoMeta ] );

	const updateMeta = useCallback( ( field, value ) => {
		setSeoMeta( ( prev ) => ( { ...prev, [ field ]: value } ) );
		setHasChanges( true );
	}, [] );

	if ( ! isOpen ) {
		return null;
	}

	const variableValues = {
		post_title: getPostTitle(),
		post_excerpt: getPostExcerpt(),
		site_title: data.siteTitle || '',
		tagline: data.tagline || '',
		separator: data.separator || '-',
		current_year: new Date().getFullYear().toString(),
	};

	const effectiveTitle = renderTemplatePreview(
		seoMeta.title || getPostTitle() || 'Untitled',
		variableValues
	);
	const effectiveDescription = renderTemplatePreview(
		seoMeta.description || variableValues.post_excerpt || '',
		variableValues
	);
	const postUrl = data.postUrl || window.location.origin;

	return (
		<Modal
			title={ __( 'Saman SEO', 'saman-seo' ) }
			onRequestClose={ () => setIsOpen( false ) }
			className="saman-seo-modal-wrapper"
		>
			<SEOPanel
				postId={ data.postId }
				postType={ data.postType }
				seoMeta={ seoMeta }
				updateMeta={ updateMeta }
				seoScore={ seoScore }
				effectiveTitle={ effectiveTitle }
				effectiveDescription={ effectiveDescription }
				postUrl={ postUrl }
				postTitle={ getPostTitle() }
				postContent={ getPostContent() }
				featuredImage={ data.featuredImage || '' }
				hasChanges={ hasChanges }
				variables={ data.variables || {} }
				variableValues={ variableValues }
				aiEnabled={ data.aiEnabled || false }
				aiProvider={ data.aiProvider || 'none' }
				aiPilot={ data.aiPilot || null }
			/>
		</Modal>
	);
};

const mount = document.getElementById( 'saman-seo-classic-root' );
if ( mount ) {
	createRoot( mount ).render( <ClassicSEOModal /> );
}
