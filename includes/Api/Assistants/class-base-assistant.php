<?php
/**
 * Base Assistant Class
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api\Assistants;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Abstract base class for AI assistants.
 */
abstract class Base_Assistant {

    /**
     * Get assistant ID.
     *
     * @return string
     */
    abstract public function get_id();

    /**
     * Get assistant name.
     *
     * @return string
     */
    abstract public function get_name();

    /**
     * Get assistant description.
     *
     * @return string
     */
    abstract public function get_description();

    /**
     * Get system prompt for this assistant.
     *
     * @return string
     */
    abstract public function get_system_prompt();

    /**
     * Get initial greeting message.
     *
     * @return string
     */
    public function get_initial_message() {
        return __( 'Hello! How can I help you today?', 'saman-seo' );
    }

    /**
     * Get suggested prompts for the user.
     *
     * @return array
     */
    public function get_suggested_prompts() {
        return [];
    }

    /**
     * Get available actions for this assistant.
     *
     * @return array
     */
    public function get_available_actions() {
        return [];
    }

    /**
     * Process an action (like "fix_issues", "show_details").
     *
     * @param string $action  Action ID.
     * @param array  $context Additional context.
     * @return array Response with message and optional data.
     */
    public function process_action( $action, $context = [] ) {
        return [
            'message' => __( 'Action not implemented.', 'saman-seo' ),
            'actions' => [],
            'data'    => null,
        ];
    }

    /**
     * Get context data for the assistant.
     *
     * @param array $request_context Context from request.
     * @return string Context string to add to the prompt.
     */
    public function get_context( $request_context = [] ) {
        $context_parts = [];

        // Add site info
        $context_parts[] = 'Site: ' . get_bloginfo( 'name' );
        $context_parts[] = 'URL: ' . home_url();

        // Add post context if available
        if ( ! empty( $request_context['post_id'] ) ) {
            $post = get_post( intval( $request_context['post_id'] ) );
            if ( $post ) {
                $context_parts[] = 'Current post: ' . $post->post_title;
                $context_parts[] = 'Post type: ' . $post->post_type;
                $context_parts[] = 'Post status: ' . $post->post_status;
            }
        }

        return implode( "\n", $context_parts );
    }

    /**
     * Build the full prompt for the AI.
     *
     * @param string $user_message User's message.
     * @param array  $context      Request context.
     * @return string
     */
    public function build_prompt( $user_message, $context = [] ) {
        $context_str = $this->get_context( $context );

        $prompt = '';
        if ( ! empty( $context_str ) ) {
            $prompt .= "Context:\n" . $context_str . "\n\n";
        }
        $prompt .= "User: " . $user_message;

        return $prompt;
    }

    /**
     * Parse AI response and extract actions.
     *
     * @param string $response Raw AI response.
     * @return array Parsed response with message and actions.
     */
    public function parse_response( $response ) {
        // Default implementation - just return the message
        // Subclasses can override to add action detection
        return [
            'message' => $response,
            'actions' => [],
            'data'    => null,
        ];
    }
}
