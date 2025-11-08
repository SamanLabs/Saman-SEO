<?php
/**
 * AI assistant admin experience.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

use WP_Post;
use function WPSEOPilot\Helpers\generate_content_snippet;
use function WPSEOPilot\Helpers\generate_title_from_template;

defined( 'ABSPATH' ) || exit;

/**
 * AI Assistant controller.
 */
class AI_Assistant {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_wpseopilot_generate_ai', [ $this, 'handle_generation' ] );
		add_action( 'admin_post_wpseopilot_ai_reset', [ $this, 'handle_reset' ] );
	}

	/**
	 * Register submenu for AI UI.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'wpseopilot',
			__( 'AI Assistant', 'wp-seo-pilot' ),
			__( 'AI Assistant', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-ai',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Load assets only on AI page.
	 *
	 * @param string $hook Current admin hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'wpseopilot_page_wpseopilot-ai' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);
	}

	/**
	 * Render admin page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		$api_key = get_option( 'wpseopilot_openai_api_key', '' );
		$settings = new Settings();
		$defaults = $settings->get_defaults();
		$model   = get_option( 'wpseopilot_ai_model', $defaults['wpseopilot_ai_model'] ?? 'gpt-4o-mini' );
		$prompt_system = get_option( 'wpseopilot_ai_prompt_system', $defaults['wpseopilot_ai_prompt_system'] ?? '' );
		$prompt_title  = get_option( 'wpseopilot_ai_prompt_title', $defaults['wpseopilot_ai_prompt_title'] ?? '' );
		$prompt_description = get_option( 'wpseopilot_ai_prompt_description', $defaults['wpseopilot_ai_prompt_description'] ?? '' );

		$models   = $settings->get_ai_models();

		include WPSEOPILOT_PATH . 'templates/ai-assistant.php';
	}

	/**
	 * Handle AJAX generation request.
	 *
	 * @return void
	 */
	public function handle_generation() {
		check_ajax_referer( 'wpseopilot_ai_generate', 'nonce' );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-pilot' ), 403 );
		}

		$post_id = isset( $_POST['postId'] ) ? absint( $_POST['postId'] ) : 0;
		$field   = isset( $_POST['field'] ) ? sanitize_key( wp_unslash( $_POST['field'] ) ) : '';

		if ( ! in_array( $field, [ 'title', 'description' ], true ) ) {
			wp_send_json_error( __( 'Unknown field requested.', 'wp-seo-pilot' ), 400 );
		}

		$post = get_post( $post_id );

		if ( ! $post instanceof WP_Post ) {
			wp_send_json_error( __( 'Post not found.', 'wp-seo-pilot' ), 404 );
		}

		$api_key = get_option( 'wpseopilot_openai_api_key', '' );

		if ( empty( $api_key ) ) {
			wp_send_json_error( __( 'Add your OpenAI API key first.', 'wp-seo-pilot' ), 400 );
		}

		$content_snippet = generate_content_snippet( $post, 80 );
		$title_template  = generate_title_from_template( $post );

		$settings = new Settings();
		$defaults = $settings->get_defaults();

		$model           = get_option( 'wpseopilot_ai_model', $defaults['wpseopilot_ai_model'] ?? 'gpt-4o-mini' );
		$system_prompt   = get_option( 'wpseopilot_ai_prompt_system', $defaults['wpseopilot_ai_prompt_system'] ?? __( 'You are an SEO assistant generating concise metadata. Respond with plain text only.', 'wp-seo-pilot' ) );
		$title_prompt    = get_option( 'wpseopilot_ai_prompt_title', $defaults['wpseopilot_ai_prompt_title'] ?? __( 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.', 'wp-seo-pilot' ) );
		$description_prompt = get_option( 'wpseopilot_ai_prompt_description', $defaults['wpseopilot_ai_prompt_description'] ?? __( 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.', 'wp-seo-pilot' ) );

		$instructions = ( 'title' === $field ) ? $title_prompt : $description_prompt;

		$messages = [
			[
				'role'    => 'system',
				'content' => wp_strip_all_tags( $system_prompt ),
			],
			[
				'role'    => 'user',
				'content' => wp_strip_all_tags(
					sprintf(
						"Instructions: %s\nPost title: %s\nSuggested template title: %s\nURL: %s\nContent summary: %s",
						$instructions,
						$post->post_title,
						$title_template,
						get_permalink( $post ),
						$content_snippet
					)
				),
			],
		];

		$response = wp_remote_post(
			'https://api.openai.com/v1/chat/completions',
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				],
				'body'    => wp_json_encode(
					[
						'model'       => $model,
						'messages'    => $messages,
						'max_tokens'  => ( 'title' === $field ) ? 60 : 120,
						'temperature' => 0.3,
					]
				),
				'timeout' => 20,
			]
		);

		if ( is_wp_error( $response ) ) {
			wp_send_json_error( $response->get_error_message(), 500 );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code >= 300 || empty( $body['choices'][0]['message']['content'] ) ) {
			$message = $body['error']['message'] ?? __( 'Unable to fetch AI suggestion.', 'wp-seo-pilot' );
			wp_send_json_error( $message, $code );
		}

		$suggestion = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( $body['choices'][0]['message']['content'] ) ) );

		wp_send_json_success(
			[
				'value' => $suggestion,
				'field' => $field,
			]
		);
	}

	/**
	 * Reset AI prompts/model to defaults.
	 *
	 * @return void
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_ai_reset' );

		$settings = new Settings();
		$defaults = $settings->get_defaults();

		$keys = [
			'wpseopilot_ai_model',
			'wpseopilot_ai_prompt_system',
			'wpseopilot_ai_prompt_title',
			'wpseopilot_ai_prompt_description',
		];

		foreach ( $keys as $key ) {
			if ( isset( $defaults[ $key ] ) ) {
				update_option( $key, $defaults[ $key ] );
			}
		}

		wp_redirect(
			add_query_arg(
				[
					'page'               => 'wpseopilot-ai',
					'wpseopilot_ai_reset' => '1',
				],
				admin_url( 'admin.php' )
			)
		);
		exit;
	}
}
