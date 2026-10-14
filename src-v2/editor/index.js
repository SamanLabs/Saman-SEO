/**
 * Saman SEO V2 - Gutenberg Editor Modal
 *
 * Registers a pinned sidebar button that opens a modal for SEO settings.
 * Uses PluginSidebar for the button, but intercepts to show a modal instead.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { Modal } from '@wordpress/components';
import { select as dataSelect, useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SEOPanel from './components/SEOPanel';
import { renderTemplatePreview } from '../utils/template';
import './editor.css';

import { __ } from '@wordpress/i18n';

// Complementary area identifier: `${ plugin name }/${ sidebar name }`.
const SIDEBAR_NAME = 'saman-seo/seo-sidebar';
// Complementary area scope shared by the post and site editors (WP 6.6+).
const SIDEBAR_SCOPE = 'core';
// Hash and admin bar node that open the modal ("Edit SEO Settings" link).
const OPEN_MODAL_HASH = '#saman-seo-open-modal';
const ADMIN_BAR_EDIT_LINK = '#wp-admin-bar-saman-seo-seo-edit a';

// Get localized data
const editorData = window.SamanSEOEditor || {};
const variables = editorData.variables || {};
const aiEnabled = editorData.aiEnabled || false;
const aiProvider = editorData.aiProvider || 'none';
const aiPilot = editorData.aiPilot || null;
const sidebarLogo = editorData.sidebarLogo || '';

// Plugin icon - Custom logo or "SEO" text badge
const PluginIcon = () => {
	if ( sidebarLogo ) {
		return (
			<img
				src={ sidebarLogo }
				alt={ __( 'SEO', 'saman-seo' ) }
				style={ {
					width: '20px',
					height: '20px',
					objectFit: 'contain',
				} }
			/>
		);
	}
	return (
		<span
			style={ {
				display: 'inline-flex',
				alignItems: 'center',
				justifyContent: 'center',
				fontWeight: 700,
				fontSize: '10px',
				letterSpacing: '0.5px',
				color: 'currentColor',
				lineHeight: 1,
			} }
		>
			{ __( 'SEO', 'saman-seo' ) }
		</span>
	);
};

/**
 * Main SEO Modal Component
 */
const SEOModalPlugin = () => {
	const [ isModalOpen, setIsModalOpen ] = useState( false );
	const [ seoMeta, setSeoMeta ] = useState( {
		title: '',
		description: '',
		canonical: '',
		noindex: false,
		nofollow: false,
		sitemap_exclude: false,
		og_image: '',
		focus_keyphrase: '',
		schema_type: '',
		secondary_keyphrases: [],
	} );
	const [ seoScore, setSeoScore ] = useState( null );
	const [ isSaving, setIsSaving ] = useState( false );
	const [ hasChanges, setHasChanges ] = useState( false );
	const [ variableValues, setVariableValues ] = useState( {} );
	const wasOpenRef = useRef( false );
	// Post ID whose stored meta has been copied into local state. Until it
	// matches, edits are ignored so blank fields never overwrite stored meta.
	const loadedPostIdRef = useRef( null );

	// Check if our sidebar is active
	const isSidebarActive = useSelect(
		( select ) =>
			select( 'core/interface' ).getActiveComplementaryArea(
				SIDEBAR_SCOPE
			) === SIDEBAR_NAME,
		[]
	);

	// Get sidebar controls
	const { disableComplementaryArea } = useDispatch( 'core/interface' );

	// When sidebar becomes active, close it and open modal instead
	useEffect( () => {
		if ( isSidebarActive && ! wasOpenRef.current ) {
			// Sidebar was just activated - close it and open modal
			disableComplementaryArea( SIDEBAR_SCOPE );
			setIsModalOpen( true );
		}
		wasOpenRef.current = isSidebarActive;
	}, [ isSidebarActive, disableComplementaryArea ] );

	// Open the modal from the admin bar "Edit SEO Settings" link, or when the
	// editor was reached through that link from another screen.
	useEffect( () => {
		const { location, history } = window;
		if ( location.hash === OPEN_MODAL_HASH ) {
			setIsModalOpen( true );
			history.replaceState(
				history.state,
				'',
				location.pathname + location.search
			);
		}

		const onClick = ( event ) => {
			const target = event.target;
			if ( ! target || ! target.closest ) {
				return;
			}
			if ( target.closest( ADMIN_BAR_EDIT_LINK ) ) {
				event.preventDefault();
				setIsModalOpen( true );
			}
		};

		document.addEventListener( 'click', onClick );
		return () => document.removeEventListener( 'click', onClick );
	}, [] );

	// Get post data from editor
	const {
		postId,
		postTitle,
		postExcerpt,
		postContent,
		postType,
		postSlug,
		featuredImage,
		storedSeoMeta,
		isMetaReady,
	} = useSelect( ( select ) => {
		const editor = select( 'core/editor' );
		const post = editor.getCurrentPost();
		const editedMeta = editor.getEditedPostAttribute( 'meta' );
		const featuredImageId =
			editor.getEditedPostAttribute( 'featured_media' );
		let featuredImageUrl = '';
		if ( featuredImageId ) {
			const media = select( 'core' ).getMedia( featuredImageId );
			if ( media ) {
				featuredImageUrl = media.source_url;
			}
		}
		return {
			postId: editor.getCurrentPostId(),
			postTitle: editor.getEditedPostAttribute( 'title' ) || '',
			postExcerpt: editor.getEditedPostAttribute( 'excerpt' ) || '',
			postContent: editor.getEditedPostContent() || '',
			postType: editor.getCurrentPostType(),
			postSlug: post?.slug || '',
			featuredImage: featuredImageUrl,
			// The editor already holds the post's meta (whatever its REST
			// base), so read it from the store instead of refetching.
			storedSeoMeta: editedMeta?._SAMAN_SEO_meta,
			isMetaReady:
				!! editor.getCurrentPostId() &&
				( !! editedMeta || !! post?.id ),
		};
	}, [] );
	const { editPost } = useDispatch( 'core/editor' );

	// Load initial meta from the editor store, once per post. Later store
	// changes come from updateMeta() itself, so local state is not reset.
	useEffect( () => {
		if ( ! isMetaReady || loadedPostIdRef.current === postId ) {
			return;
		}
		const meta =
			storedSeoMeta && typeof storedSeoMeta === 'object'
				? storedSeoMeta
				: {};
		setSeoMeta( {
			title: meta.title || '',
			description: meta.description || '',
			canonical: meta.canonical || '',
			noindex: meta.noindex === '1',
			nofollow: meta.nofollow === '1',
			sitemap_exclude: meta.sitemap_exclude === '1',
			og_image: meta.og_image || '',
			focus_keyphrase: meta.focus_keyphrase || '',
			schema_type: meta.schema_type || '',
			secondary_keyphrases: Array.isArray( meta.secondary_keyphrases )
				? meta.secondary_keyphrases
				: [],
		} );
		loadedPostIdRef.current = postId;
	}, [ isMetaReady, postId, storedSeoMeta ] );

	// Calculate SEO score
	useEffect( () => {
		if ( ! postId ) {
			return;
		}
		const timer = setTimeout( () => {
			apiFetch( {
				path: `/saman-seo/v1/audit/post/${ postId }`,
			} )
				.then( ( response ) => {
					if ( response.success && response.data ) {
						setSeoScore( response.data );
					}
				} )
				.catch( () => {
					// Score calculation might fail
				} );
		}, 500 );
		return () => clearTimeout( timer );
	}, [ postId, seoMeta, postTitle, postContent ] );

	// Build variable values from current post data
	useEffect( () => {
		const values = {
			post_title: postTitle || '',
			post_excerpt: postExcerpt || '',
			site_title: editorData.siteTitle || '',
			tagline: editorData.tagline || '',
			separator: editorData.separator || '-',
			current_year: new Date().getFullYear().toString(),
		};
		setVariableValues( values );
	}, [ postTitle, postExcerpt ] );

	// Update meta field
	const updateMeta = useCallback(
		( field, value ) => {
			// Never write before the stored meta is loaded: the local state
			// is still blank and would overwrite it.
			if ( ! postId || loadedPostIdRef.current !== postId ) {
				return;
			}

			setSeoMeta( ( prev ) => ( {
				...prev,
				[ field ]: value,
			} ) );
			setHasChanges( true );

			// Also update post meta for saving
			const newMeta = {
				...seoMeta,
				[ field ]: value,
			};

			// Start from the meta the editor already holds so keys this
			// panel doesn't manage (secondary keyphrases, custom schema,
			// snippet controls, recipe/event data) survive the save.
			const editedMeta =
				dataSelect( 'core/editor' ).getEditedPostAttribute( 'meta' )
					?._SAMAN_SEO_meta || {};

			// Convert booleans to strings for storage
			const metaForSave = {
				...editedMeta,
				...( newMeta.secondary_keyphrases !== undefined && {
					secondary_keyphrases: newMeta.secondary_keyphrases,
				} ),
				...( newMeta.custom_schema !== undefined && {
					custom_schema: newMeta.custom_schema,
				} ),
				title: newMeta.title,
				description: newMeta.description,
				canonical: newMeta.canonical,
				noindex: newMeta.noindex ? '1' : '',
				nofollow: newMeta.nofollow ? '1' : '',
				sitemap_exclude: newMeta.sitemap_exclude ? '1' : '',
				og_image: newMeta.og_image,
				focus_keyphrase: newMeta.focus_keyphrase,
				schema_type: newMeta.schema_type || '',
			};
			editPost( {
				meta: {
					_SAMAN_SEO_meta: metaForSave,
				},
			} );
		},
		[ seoMeta, editPost, postId ]
	);

	// Get effective title and description (with fallbacks).
	// Resolve any {{variables}} so raw template syntax never leaks to previews.
	const effectiveTitle = renderTemplatePreview(
		seoMeta.title || postTitle || 'Untitled',
		variableValues
	);
	const effectiveDescription = renderTemplatePreview(
		seoMeta.description || postExcerpt || '',
		variableValues
	);
	const siteUrl = window.location.origin;
	const postUrl = postSlug ? `${ siteUrl }/${ postSlug }/` : siteUrl;
	return (
		<>
			{ /* PluginSidebar creates the pinned icon button in the header */ }
			<PluginSidebar
				name="seo-sidebar"
				title={ __( 'Saman SEO', 'saman-seo' ) }
				icon={ <PluginIcon /> }
			>
				{ /* Empty - we intercept and show modal instead */ }
				<div
					style={ {
						padding: '16px',
						textAlign: 'center',
						color: '#757575',
					} }
				>
					{ __( 'Opening SEO settings\u2026', 'saman-seo' ) }
				</div>
			</PluginSidebar>

			{ /* Also add to the Options menu for discoverability */ }
			<PluginSidebarMoreMenuItem
				target="seo-sidebar"
				icon={ <PluginIcon /> }
			>
				{ __( 'SEO Settings', 'saman-seo' ) }
			</PluginSidebarMoreMenuItem>

			{ /* The actual modal with SEO content */ }
			{ isModalOpen && (
				<Modal
					title={ __( 'Saman SEO', 'saman-seo' ) }
					onRequestClose={ () => setIsModalOpen( false ) }
					className="saman-seo-modal-wrapper"
				>
					<SEOPanel
						postId={ postId }
						postType={ postType }
						seoMeta={ seoMeta }
						updateMeta={ updateMeta }
						seoScore={ seoScore }
						effectiveTitle={ effectiveTitle }
						effectiveDescription={ effectiveDescription }
						postUrl={ postUrl }
						postTitle={ postTitle }
						postContent={ postContent }
						featuredImage={ featuredImage }
						hasChanges={ hasChanges }
						variables={ variables }
						variableValues={ variableValues }
						aiEnabled={ aiEnabled }
						aiProvider={ aiProvider }
						aiPilot={ aiPilot }
					/>
				</Modal>
			) }
		</>
	);
};

// Register the plugin
registerPlugin( 'saman-seo', {
	render: SEOModalPlugin,
	icon: <PluginIcon />,
} );
