<?php
/**
 * AI REST Controller
 *
 * Simplified controller that delegates all AI operations to Saman Labs AI.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Integration\AI_Pilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for AI generation.
 * All AI operations are handled by Saman Labs AI.
 */
class Ai_Controller extends REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Generate SEO content.
		register_rest_route( $this->namespace, '/ai/generate', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'generate' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Get AI status.
		register_rest_route( $this->namespace, '/ai/status', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_status' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Get available models.
		register_rest_route( $this->namespace, '/ai/models', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_models' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Get/save prompt settings.
		register_rest_route( $this->namespace, '/ai/settings', [
			[
				'methods'             => \WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_settings' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'save_settings' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );

		// Reset prompts to defaults.
		register_rest_route( $this->namespace, '/ai/reset', [
			[
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => [ $this, 'reset_settings' ],
				'permission_callback' => [ $this, 'permission_check' ],
			],
		] );
	}

	/**
	 * Generate SEO content (title/description).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function generate( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$content = isset( $params['content'] ) ? sanitize_textarea_field( $params['content'] ) : '';
		$type    = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'both';

		if ( empty( $content ) ) {
			return $this->error(
				__( 'Content is required for AI generation.', 'saman-seo' ),
				'missing_content',
				400
			);
		}

		// Check if Saman Labs AI is ready.
		if ( ! AI_Pilot::is_ready() ) {
			$status = AI_Pilot::get_status();

			if ( ! $status['installed'] ) {
				return $this->error(
					__( 'Saman Labs AI is required for AI features. Please install it from the More page.', 'saman-seo' ),
					'ai_not_installed',
					400
				);
			}

			if ( ! $status['active'] ) {
				return $this->error(
					__( 'Saman Labs AI is installed but not activated. Please activate it.', 'saman-seo' ),
					'ai_not_active',
					400
				);
			}

			return $this->error(
				__( 'Saman Labs AI needs configuration. Please add an API key in Saman Labs AI settings.', 'saman-seo' ),
				'ai_not_configured',
				400
			);
		}

		// Build context from request.
		$context = [
			'keyword'    => $params['keyword'] ?? '',
			'post_title' => $params['post_title'] ?? '',
			'post_id'    => $params['post_id'] ?? 0,
		];

		$results = [];

		// Generate title.
		if ( 'title' === $type || 'both' === $type ) {
			$result = AI_Pilot::generate( $content, 'title', $context );

			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message(), 'generation_error', 500 );
			}

			$results['title'] = trim( $result );
		}

		// Generate description.
		if ( 'description' === $type || 'both' === $type ) {
			$result = AI_Pilot::generate( $content, 'description', $context );

			if ( is_wp_error( $result ) ) {
				return $this->error( $result->get_error_message(), 'generation_error', 500 );
			}

			$results['description'] = trim( $result );
		}

		return $this->success( $results, __( 'AI generation completed.', 'saman-seo' ), [
			'provider' => 'Saman-ai',
		] );
	}

	/**
	 * Get AI status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_status( $request ) {
		$status   = AI_Pilot::get_status();
		$provider = AI_Pilot::get_provider();
		$ready    = AI_Pilot::is_ready();

		$response = [
			'ready'        => $ready,
			'configured'   => $ready, // Alias for frontend compatibility.
			'provider'     => $provider,
			'ai_pilot'     => [
				'installed'    => $status['installed'],
				'active'       => $status['active'],
				'ready'        => $status['ready'],
				'version'      => $status['version'] ?? null,
				'settings_url' => admin_url( 'admin.php?page=Saman-ai' ),
			],
		];

		if ( $ready ) {
			$response['message']      = __( 'Connected to Saman Labs AI', 'saman-seo' );
			$response['models_count'] = count( AI_Pilot::get_models() );
		} elseif ( $status['installed'] && ! $status['active'] ) {
			$response['message'] = __( 'Saman Labs AI needs to be activated', 'saman-seo' );
		} elseif ( $status['installed'] && ! $status['ready'] ) {
			$response['message'] = __( 'Saman Labs AI needs configuration', 'saman-seo' );
		} else {
			$response['message'] = __( 'Install Saman Labs AI to enable AI features', 'saman-seo' );
		}

		return $this->success( $response );
	}

	/**
	 * Get available AI models.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_models( $request ) {
		if ( ! AI_Pilot::is_ready() ) {
			return $this->success( [] );
		}

		$models = AI_Pilot::get_models();

		// Format for frontend.
		$formatted = [];
		foreach ( $models as $model ) {
			$formatted[] = [
				'value'    => $model['id'] ?? $model['value'] ?? '',
				'label'    => $model['name'] ?? $model['label'] ?? '',
				'provider' => $model['provider'] ?? 'unknown',
			];
		}

		return $this->success( $formatted );
	}

	/**
	 * Get prompt settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_settings( $request ) {
		$settings = [
			'ai_prompt_system'      => get_option( 'SAMAN_SEO_ai_prompt_system', 'You are an SEO assistant generating concise metadata. Respond with plain text only.' ),
			'ai_prompt_title'       => get_option( 'SAMAN_SEO_ai_prompt_title', 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.' ),
			'ai_prompt_description' => get_option( 'SAMAN_SEO_ai_prompt_description', 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.' ),
		];

		return $this->success( $settings );
	}

	/**
	 * Save prompt settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_settings( $request ) {
		$params = $request->get_json_params();

		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		if ( isset( $params['ai_prompt_system'] ) ) {
			update_option( 'SAMAN_SEO_ai_prompt_system', sanitize_textarea_field( $params['ai_prompt_system'] ) );
		}

		if ( isset( $params['ai_prompt_title'] ) ) {
			update_option( 'SAMAN_SEO_ai_prompt_title', sanitize_textarea_field( $params['ai_prompt_title'] ) );
		}

		if ( isset( $params['ai_prompt_description'] ) ) {
			update_option( 'SAMAN_SEO_ai_prompt_description', sanitize_textarea_field( $params['ai_prompt_description'] ) );
		}

		return $this->success( [
			'ai_prompt_system'      => get_option( 'SAMAN_SEO_ai_prompt_system' ),
			'ai_prompt_title'       => get_option( 'SAMAN_SEO_ai_prompt_title' ),
			'ai_prompt_description' => get_option( 'SAMAN_SEO_ai_prompt_description' ),
		], __( 'Settings saved.', 'saman-seo' ) );
	}

	/**
	 * Reset prompts to defaults.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function reset_settings( $request ) {
		$defaults = [
			'ai_prompt_system'      => 'You are an SEO assistant generating concise metadata. Respond with plain text only.',
			'ai_prompt_title'       => 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.',
			'ai_prompt_description' => 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.',
		];

		update_option( 'SAMAN_SEO_ai_prompt_system', $defaults['ai_prompt_system'] );
		update_option( 'SAMAN_SEO_ai_prompt_title', $defaults['ai_prompt_title'] );
		update_option( 'SAMAN_SEO_ai_prompt_description', $defaults['ai_prompt_description'] );

		return $this->success( $defaults, __( 'Settings reset to defaults.', 'saman-seo' ) );
	}
}
