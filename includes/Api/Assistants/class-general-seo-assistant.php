<?php
/**
 * General SEO Assistant
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api\Assistants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * General-purpose SEO assistant.
 */
class General_SEO_Assistant extends Base_Assistant {

    /**
     * Get assistant ID.
     *
     * @return string
     */
    public function get_id() {
        return 'general-seo';
    }

    /**
     * Get assistant name.
     *
     * @return string
     */
    public function get_name() {
        return __( 'SEO Assistant', 'saman-seo' );
    }

    /**
     * Get assistant description.
     *
     * @return string
     */
    public function get_description() {
        return __( 'Your helpful SEO buddy for all things search optimization.', 'saman-seo' );
    }

    /**
     * Get system prompt.
     *
     * @return string
     */
    public function get_system_prompt() {
        return "You are a helpful SEO assistant for WordPress websites.

RULES:
- Be friendly and casual, like a helpful coworker
- Never start with 'Certainly!' or 'Of course!' or 'Great question!'
- Never say 'I hope this helps' or similar filler phrases
- Use contractions (you're, it's, don't)
- Be specific, not generic
- Keep responses concise unless asked for details
- Use emoji sparingly (1-2 max per response)
- Never use corporate speak or marketing fluff
- If you don't know something, say so plainly

You can help with:
- Writing meta titles and descriptions
- Analyzing content for SEO
- Keyword suggestions and research
- Internal linking advice
- Technical SEO questions
- Content optimization tips
- Understanding search rankings

BAD: 'I'd be happy to help you optimize your meta descriptions!'
GOOD: 'Your meta descriptions need work. Here's what I found.'

BAD: 'Here are some suggestions for improvement.'
GOOD: 'Three things to fix: [specific list]'";
    }

    /**
     * Get initial greeting.
     *
     * @return string
     */
    public function get_initial_message() {
        return __( "Hey! I'm your SEO assistant. Ask me about meta tags, keywords, content optimization, or anything SEO-related.", 'saman-seo' );
    }

    /**
     * Get suggested prompts.
     *
     * @return array
     */
    public function get_suggested_prompts() {
        return [
            __( 'How do I write a good meta description?', 'saman-seo' ),
            __( 'What makes a title tag effective?', 'saman-seo' ),
            __( 'Help me find keywords for my blog post', 'saman-seo' ),
            __( 'What are internal links and why do they matter?', 'saman-seo' ),
        ];
    }

    /**
     * Get context with SEO-specific data.
     *
     * @param array $request_context Context from request.
     * @return string
     */
    public function get_context( $request_context = [] ) {
        $context_parts = [];

        // Basic site info
        $context_parts[] = 'Site: ' . get_bloginfo( 'name' );
        $context_parts[] = 'URL: ' . home_url();

        // Add post context if available
        if ( ! empty( $request_context['post_id'] ) ) {
            $post = get_post( intval( $request_context['post_id'] ) );
            if ( $post ) {
                $context_parts[] = "\nCurrent post:";
                $context_parts[] = '- Title: ' . $post->post_title;
                $context_parts[] = '- Type: ' . $post->post_type;
                $context_parts[] = '- Status: ' . $post->post_status;

                // Get meta title/description if set
                $meta_title = get_post_meta( $post->ID, '_SAMAN_SEO_title', true );
                $meta_desc = get_post_meta( $post->ID, '_SAMAN_SEO_description', true );

                if ( $meta_title ) {
                    $context_parts[] = '- SEO Title: ' . $meta_title;
                }
                if ( $meta_desc ) {
                    $context_parts[] = '- SEO Description: ' . $meta_desc;
                }
            }
        }

        // Add some site stats
        $post_count = wp_count_posts( 'post' );
        $page_count = wp_count_posts( 'page' );
        $context_parts[] = "\nSite stats:";
        $context_parts[] = '- Published posts: ' . ( $post_count->publish ?? 0 );
        $context_parts[] = '- Published pages: ' . ( $page_count->publish ?? 0 );

        return implode( "\n", $context_parts );
    }
}
