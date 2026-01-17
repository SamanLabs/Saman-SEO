/**
 * Saman SEO V2 - Gutenberg Editor Sidebar
 *
 * Registers a sidebar panel in the block editor for SEO settings.
 */

import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/edit-post';
import { useSelect, useDispatch } from '@wordpress/data';
import { useEffect, useState, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import SEOPanel from './components/SEOPanel';
import './editor.css';

// Plugin icon
const PluginIcon = () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
        <path
            d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"
            fill="currentColor"
        />
    </svg>
);

// Get localized data
const editorData = window.SamanSEOEditor || {};
const variables = editorData.variables || {};
const aiEnabled = editorData.aiEnabled || false;
const aiProvider = editorData.aiProvider || 'none';
const aiPilot = editorData.aiPilot || null;

/**
 * Main SEO Sidebar Component
 */
const SEOSidebar = () => {
    const [seoMeta, setSeoMeta] = useState({
        title: '',
        description: '',
        canonical: '',
        noindex: false,
        nofollow: false,
        og_image: '',
        focus_keyphrase: '',
    });
    const [seoScore, setSeoScore] = useState(null);
    const [isSaving, setIsSaving] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);
    const [variableValues, setVariableValues] = useState({});

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
                if (post.meta && post.meta._Saman_seo_meta) {
                    const meta = post.meta._Saman_seo_meta;
                    setSeoMeta({
                        title: meta.title || '',
                        description: meta.description || '',
                        canonical: meta.canonical || '',
                        noindex: meta.noindex === '1',
                        nofollow: meta.nofollow === '1',
                        og_image: meta.og_image || '',
                        focus_keyphrase: meta.focus_keyphrase || '',
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
        };

        editPost({ meta: { _Saman_seo_meta: metaForSave } });
    }, [seoMeta, editPost]);

    // Get effective title and description (with fallbacks)
    const effectiveTitle = seoMeta.title || postTitle || 'Untitled';
    const effectiveDescription = seoMeta.description || postExcerpt || '';
    const siteUrl = window.location.origin;
    const postUrl = postSlug ? `${siteUrl}/${postSlug}/` : siteUrl;

    return (
        <>
            <PluginSidebarMoreMenuItem target="saman-seo-sidebar" icon={<PluginIcon />}>
                Saman SEO
            </PluginSidebarMoreMenuItem>
            <PluginSidebar
                name="saman-seo-sidebar"
                title="Saman SEO"
                icon={<PluginIcon />}
            >
                <SEOPanel
                    postId={postId}
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
            </PluginSidebar>
        </>
    );
};

// Register the plugin
registerPlugin('saman-seo', {
    render: SEOSidebar,
    icon: <PluginIcon />,
});
