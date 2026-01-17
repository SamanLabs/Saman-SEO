<?php
/**
 * SEO Reporter Assistant
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api\Assistants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * SEO Reporter - Weekly updates and site analysis.
 */
class SEO_Reporter_Assistant extends Base_Assistant {

    /**
     * Get assistant ID.
     *
     * @return string
     */
    public function get_id() {
        return 'seo-reporter';
    }

    /**
     * Get assistant name.
     *
     * @return string
     */
    public function get_name() {
        return __( 'SEO Reporter', 'saman-seo' );
    }

    /**
     * Get assistant description.
     *
     * @return string
     */
    public function get_description() {
        return __( 'Your weekly SEO buddy that gives you the rundown on your site.', 'saman-seo' );
    }

    /**
     * Get system prompt.
     *
     * @return string
     */
    public function get_system_prompt() {
        return "You are an SEO Reporter that provides friendly updates about a WordPress site's SEO health.

RULES:
- Start with good news first when possible
- Use simple, plain language
- Celebrate wins genuinely but briefly
- Prioritize what matters most
- Offer actionable next steps
- Never sound robotic or use corporate speak
- Use contractions (you're, it's, don't)
- Keep it brief - bullet points are great

RESPONSE FORMAT:
Use emoji labels to organize your reports:
- Use relevant emoji for categories (like good news, warnings, links, etc.)
- Keep each point to 1-2 sentences max
- End with suggested next steps when relevant

EXAMPLE STYLE:
'Here's what I found on your site:

Good stuff - Your homepage title is well-optimized at 58 characters.

Needs attention - Found 3 posts without meta descriptions. These show up blank in search results.

Quick wins:
1. Add descriptions to those 3 posts
2. Check your About page title (it's too long at 72 chars)

Want me to help write those descriptions?'

BAD: 'I have completed an analysis of your website's search engine optimization status.'
GOOD: 'Looked at your site. Here's what's up:'";
    }

    /**
     * Get initial greeting.
     *
     * @return string
     */
    public function get_initial_message() {
        return __( "Hey! I can give you a quick rundown of your site's SEO health. Want me to take a look?", 'saman-seo' );
    }

    /**
     * Get suggested prompts.
     *
     * @return array
     */
    public function get_suggested_prompts() {
        return [
            __( 'Give me a quick SEO report', 'saman-seo' ),
            __( 'What SEO issues should I fix first?', 'saman-seo' ),
            __( 'Check my meta titles and descriptions', 'saman-seo' ),
            __( 'Find posts missing SEO data', 'saman-seo' ),
        ];
    }

    /**
     * Get available actions.
     *
     * @return array
     */
    public function get_available_actions() {
        return [
            [
                'id'    => 'generate_report',
                'label' => __( 'Generate Report', 'saman-seo' ),
            ],
            [
                'id'    => 'find_issues',
                'label' => __( 'Find Issues', 'saman-seo' ),
            ],
        ];
    }

    /**
     * Process actions.
     *
     * @param string $action  Action ID.
     * @param array  $context Additional context.
     * @return array
     */
    public function process_action( $action, $context = [] ) {
        switch ( $action ) {
            case 'generate_report':
                return $this->generate_report();
            case 'find_issues':
                return $this->find_issues();
            default:
                return parent::process_action( $action, $context );
        }
    }

    /**
     * Generate SEO report data.
     *
     * @return array
     */
    private function generate_report() {
        $report = $this->gather_site_data();

        $message = $this->format_report( $report );

        return [
            'message' => $message,
            'actions' => [
                [ 'id' => 'find_issues', 'label' => __( 'Show me the issues', 'saman-seo' ) ],
            ],
            'data'    => $report,
        ];
    }

    /**
     * Find and list SEO issues.
     *
     * @return array
     */
    private function find_issues() {
        $issues = [];

        // Check posts without meta descriptions
        $posts_without_desc = $this->get_posts_without_meta( '_SAMAN_SEO_description' );
        if ( ! empty( $posts_without_desc ) ) {
            $issues[] = [
                'type'    => 'warning',
                'message' => sprintf(
                    __( '%d posts are missing meta descriptions', 'saman-seo' ),
                    count( $posts_without_desc )
                ),
                'count'   => count( $posts_without_desc ),
                'posts'   => array_slice( $posts_without_desc, 0, 5 ),
            ];
        }

        // Check posts without meta titles
        $posts_without_title = $this->get_posts_without_meta( '_SAMAN_SEO_title' );
        if ( ! empty( $posts_without_title ) ) {
            $issues[] = [
                'type'    => 'info',
                'message' => sprintf(
                    __( '%d posts are using default titles (no custom SEO title)', 'saman-seo' ),
                    count( $posts_without_title )
                ),
                'count'   => count( $posts_without_title ),
            ];
        }

        // Check for long titles
        $long_titles = $this->get_posts_with_long_titles();
        if ( ! empty( $long_titles ) ) {
            $issues[] = [
                'type'    => 'warning',
                'message' => sprintf(
                    __( '%d posts have titles longer than 60 characters', 'saman-seo' ),
                    count( $long_titles )
                ),
                'count'   => count( $long_titles ),
                'posts'   => array_slice( $long_titles, 0, 5 ),
            ];
        }

        if ( empty( $issues ) ) {
            return [
                'message' => __( "Looking good! I didn't find any major SEO issues.", 'saman-seo' ),
                'actions' => [],
                'data'    => [ 'issues' => [] ],
            ];
        }

        $message = __( "Found some things to fix:\n\n", 'saman-seo' );
        foreach ( $issues as $issue ) {
            $emoji = $issue['type'] === 'warning' ? 'ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â' : 'ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¹ÃƒÂ¯Ã‚Â¸Ã‚Â';
            $message .= $emoji . ' ' . $issue['message'] . "\n";
        }

        return [
            'message' => $message,
            'actions' => [],
            'data'    => [ 'issues' => $issues ],
        ];
    }

    /**
     * Gather site data for context.
     *
     * @return array
     */
    private function gather_site_data() {
        $data = [
            'site_name'   => get_bloginfo( 'name' ),
            'site_url'    => home_url(),
            'posts'       => [],
            'pages'       => [],
            'stats'       => [],
        ];

        // Post counts
        $post_count = wp_count_posts( 'post' );
        $page_count = wp_count_posts( 'page' );

        $data['stats']['total_posts'] = $post_count->publish ?? 0;
        $data['stats']['total_pages'] = $page_count->publish ?? 0;

        // Posts without meta
        $data['stats']['posts_without_description'] = count( $this->get_posts_without_meta( '_SAMAN_SEO_description' ) );
        $data['stats']['posts_without_title'] = count( $this->get_posts_without_meta( '_SAMAN_SEO_title' ) );
        $data['stats']['posts_with_long_titles'] = count( $this->get_posts_with_long_titles() );

        return $data;
    }

    /**
     * Format report as a message.
     *
     * @param array $report Report data.
     * @return string
     */
    private function format_report( $report ) {
        $stats = $report['stats'];

        $message = __( "Here's your site's SEO snapshot:\n\n", 'saman-seo' );

        // Content overview
        $message .= "ÃƒÂ°Ã…Â¸Ã¢â‚¬Å“Ã…Â  **Content**\n";
        $message .= sprintf( __( "- %d published posts\n", 'saman-seo' ), $stats['total_posts'] );
        $message .= sprintf( __( "- %d published pages\n\n", 'saman-seo' ), $stats['total_pages'] );

        // Issues summary
        $has_issues = false;

        if ( $stats['posts_without_description'] > 0 ) {
            $has_issues = true;
            $message .= sprintf(
                __( "ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â %d posts missing meta descriptions\n", 'saman-seo' ),
                $stats['posts_without_description']
            );
        }

        if ( $stats['posts_with_long_titles'] > 0 ) {
            $has_issues = true;
            $message .= sprintf(
                __( "ÃƒÂ¢Ã…Â¡Ã‚Â ÃƒÂ¯Ã‚Â¸Ã‚Â %d posts have titles too long for search results\n", 'saman-seo' ),
                $stats['posts_with_long_titles']
            );
        }

        if ( ! $has_issues ) {
            $message .= __( "ÃƒÂ¢Ã…â€œÃ¢â‚¬Â¦ No major issues found! Your basics look good.\n", 'saman-seo' );
        }

        return $message;
    }

    /**
     * Get posts without a specific meta field.
     *
     * @param string $meta_key Meta key to check.
     * @return array Post IDs.
     */
    private function get_posts_without_meta( $meta_key ) {
        global $wpdb;

        $results = $wpdb->get_col( $wpdb->prepare(
            "SELECT p.ID FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
            LIMIT 100",
            $meta_key
        ) );

        return $results ?? [];
    }

    /**
     * Get posts with titles longer than 60 characters.
     *
     * @return array Posts with long titles.
     */
    private function get_posts_with_long_titles() {
        global $wpdb;

        $results = $wpdb->get_results(
            "SELECT ID, post_title FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type IN ('post', 'page')
            AND CHAR_LENGTH(post_title) > 60
            LIMIT 50",
            ARRAY_A
        );

        return $results ?? [];
    }

    /**
     * Get context with SEO report data.
     *
     * @param array $request_context Context from request.
     * @return string
     */
    public function get_context( $request_context = [] ) {
        $data = $this->gather_site_data();

        $context = "Site: {$data['site_name']}\n";
        $context .= "URL: {$data['site_url']}\n\n";
        $context .= "Current stats:\n";
        $context .= "- Total published posts: {$data['stats']['total_posts']}\n";
        $context .= "- Total published pages: {$data['stats']['total_pages']}\n";
        $context .= "- Posts without meta descriptions: {$data['stats']['posts_without_description']}\n";
        $context .= "- Posts with long titles (>60 chars): {$data['stats']['posts_with_long_titles']}\n";

        return $context;
    }
}
