/**
 * Saman SEO V2 - Gutenberg Editor Modal
 *
 * Registers a pinned sidebar button that opens a modal for SEO settings.
 * Uses PluginSidebar for the button, but intercepts to show a modal instead.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { Modal } from '@wordpress/components';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback, useRef } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SEOPanel from './components/SEOPanel';
import './editor.css';

// Sidebar name constant
const SIDEBAR_NAME = 'saman-seo/seo-sidebar';

// Get localized data
const editorData = window.SamanSEOEditor || {};
const variables = editorData.variables || {};
const aiEnabled = editorData.aiEnabled || false;
const aiProvider = editorData.aiProvider || 'none';
const aiPilot = editorData.aiPilot || null;
const sidebarLogo = editorData.sidebarLogo || '';

// Plugin icon - Custom logo or "SEO" text badge
const PluginIcon = () => {
    if (sidebarLogo) {
        return (
            <img
                src={sidebarLogo}
                alt="SEO"
                style={{
                    width: '20px',
                    height: '20px',
                    objectFit: 'contain',
                }}
            />
        );
    }

    return (
        <span style={{
            display: 'inline-flex',
            alignItems: 'center',
            justifyContent: 'center',
            fontWeight: 700,
            fontSize: '10px',
            letterSpacing: '0.5px',
            color: 'currentColor',
            lineHeight: 1,
        }}>
            SEO
        </span>
    );
};

/**
 * Main SEO Modal Component
 */
const SEOModalPlugin = () => {
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [seoMeta, setSeoMeta] = useState({
        title: '',
        description: '',
        canonical: '',
        noindex: false,
        nofollow: false,
        og_image: '',
        focus_keyphrase: '',
        schema_type: '',
    });
    const [seoScore, setSeoScore] = useState(null);
    const [isSaving, setIsSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);
    const [variableValues, setVariableValues] = useState({});
    const wasOpenRef = useRef(false);

    // Check if our sidebar is active
    const isSidebarActive = useSelect((select) => {
        const activeGeneralSidebar = select('core/edit-post').getActiveGeneralSidebarName();
        return activeGeneralSidebar === SIDEBAR_NAME;
    }, []);

    // Get sidebar controls
    const { closeGeneralSidebar } = useDispatch('core/edit-post');

    // When sidebar becomes active, close it and open modal instead
    useEffect(() => {
        if (isSidebarActive && !wasOpenRef.current) {
            // Sidebar was just activated - close it and open modal
            closeGeneralSidebar();
            setIsModalOpen(true);
        }
        wasOpenRef.current = isSidebarActive;
    }, [isSidebarActive, closeGeneralSidebar]);

    // Get post data from editor
    const { postId, postTitle, postExcerpt, postContent, postType, postSlug, featuredImage } = useSelect((select) => {
        const editor = select('core/editor');
        const post = editor.getCurrentPost();
        const featuredImageId = editor.getEditedPostAttribute('featured_media');

        let featuredImageUrl = '';
        if (featuredImageId) {
            const media = select('core').getMedia(featuredImageId);
            if (media) {
                featuredImageUrl = media.source_url;
            }
        }

        return {
            postId: editor.getCurrentPostId(),
            postTitle: editor.getEditedPostAttribute('title') || '',
            postExcerpt: editor.getEditedPostAttribute('excerpt') || '',
            postContent: editor.getEditedPostContent() || '',
            postType: editor.getCurrentPostType(),
            postSlug: post?.slug || '',
            featuredImage: featuredImageUrl,
        };
    }, []);

    const { editPost } = useDispatch('core/editor');

    // Get post type REST base
    const getRestBase = (type) => {
        // Common post type mappings
        const bases = {
            post: 'posts',
            page: 'pages',
            attachment: 'media',
        };
        return bases[type] || type;
    };

    // Load initial meta from post
    useEffect(() => {
        if (!postId || !postType) return;

        const restBase = getRestBase(postType);
        apiFetch({ path: `/wp/v2/${restBase}/${postId}` })
            .then((post) => {
                if (post.meta && post.meta._SAMAN_SEO_meta) {
                    const meta = post.meta._SAMAN_SEO_meta;
                    setSeoMeta({
                        title: meta.title || '',
                        description: meta.description || '',
                        canonical: meta.canonical || '',
                        noindex: meta.noindex === '1',
                        nofollow: meta.nofollow === '1',
                        og_image: meta.og_image || '',
                        focus_keyphrase: meta.focus_keyphrase || '',
                        schema_type: meta.schema_type || '',
                    });
                }
            })
            .catch(() => {
                // Post meta might not exist yet
            });
    }, [postId, postType]);

    // Calculate SEO score
    useEffect(() => {
        if (!postId) return;

        const timer = setTimeout(() => {
            apiFetch({ path: `/saman-seo/v1/audit/post/${postId}` })
                .then((response) => {
                    if (response.success && response.data) {
                        setSeoScore(response.data);
                    }
                })
                .catch(() => {
                    // Score calculation might fail
                });
        }, 500);

        return () => clearTimeout(timer);
    }, [postId, seoMeta, postTitle, postContent]);

    // Build variable values from current post data
    useEffect(() => {
        const values = {
            post_title: postTitle || '',
            post_excerpt: postExcerpt || '',
            site_title: editorData.siteTitle || '',
            tagline: editorData.tagline || '',
            separator: editorData.separator || '-',
            current_year: new Date().getFullYear().toString(),
        };
        setVariableValues(values);
    }, [postTitle, postExcerpt]);

    // Update meta field
    const updateMeta = useCallback((field, value) => {
        setSeoMeta((prev) => ({ ...prev, [field]: value }));
        setHasChanges(true);

        // Also update post meta for saving
        const newMeta = {
            ...seoMeta,
            [field]: value,
        };

        // Convert booleans to strings for storage
        const metaForSave = {
            title: newMeta.title,
            description: newMeta.description,
            canonical: newMeta.canonical,
            noindex: newMeta.noindex ? '1' : '',
            nofollow: newMeta.nofollow ? '1' : '',
            og_image: newMeta.og_image,
            focus_keyphrase: newMeta.focus_keyphrase,
            schema_type: newMeta.schema_type || '',
        };

        editPost({ meta: { _SAMAN_SEO_meta: metaForSave } });
    }, [seoMeta, editPost]);

    // Get effective title and description (with fallbacks)
    const effectiveTitle = seoMeta.title || postTitle || 'Untitled';
    const effectiveDescription = seoMeta.description || postExcerpt || '';
    const siteUrl = window.location.origin;
    const postUrl = postSlug ? `${siteUrl}/${postSlug}/` : siteUrl;

    return (
        <>
            {/* PluginSidebar creates the pinned icon button in the header */}
            <PluginSidebar
                name="seo-sidebar"
                title="Saman SEO"
                icon={<PluginIcon />}
            >
                {/* Empty - we intercept and show modal instead */}
                <div style={{ padding: '16px', textAlign: 'center', color: '#757575' }}>
                    Opening SEO settings...
                </div>
            </PluginSidebar>

            {/* Also add to the Options menu for discoverability */}
            <PluginSidebarMoreMenuItem
                target="seo-sidebar"
                icon={<PluginIcon />}
            >
                SEO Settings
            </PluginSidebarMoreMenuItem>

            {/* The actual modal with SEO content */}
            {isModalOpen && (
                <Modal
                    title="Saman SEO"
                    onRequestClose={() => setIsModalOpen(false)}
                    className="saman-seo-modal-wrapper"
                    isDismissible={true}
                    shouldCloseOnClickOutside={true}
                    shouldCloseOnEsc={true}
                >
                    <SEOPanel
                        postId={postId}
                        postType={postType}
                        seoMeta={seoMeta}
                        updateMeta={updateMeta}
                        seoScore={seoScore}
                        effectiveTitle={effectiveTitle}
                        effectiveDescription={effectiveDescription}
                        postUrl={postUrl}
                        postTitle={postTitle}
                        postContent={postContent}
                        featuredImage={featuredImage}
                        hasChanges={hasChanges}
                        variables={variables}
                        variableValues={variableValues}
                        aiEnabled={aiEnabled}
                        aiProvider={aiProvider}
                        aiPilot={aiPilot}
                    />
                </Modal>
            )}
        </>
    );
};

// Register the plugin
registerPlugin('saman-seo', {
    render: SEOModalPlugin,
    icon: <PluginIcon />,
});
