<?php
/**
 * AI Assistant Service
 *
 * Handles legacy AI functionality. All AI operations are now delegated
 * to Saman Labs AI via the Integration\AI_Pilot class.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * AI Assistant service.
 *
 * Note: The V1 AI UI has been deprecated in favor of the React UI which
 * delegates all AI operations through the AI_Pilot integration layer.
 * This service is retained for backwards compatibility but the direct
 * OpenAI API calls have been removed.
 */
class AI_Assistant {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		// Check if module is enabled.
		if ( ! \Saman\SEO\Helpers\module_enabled( 'ai_assistant' ) ) {
			return;
		}

		// V1 menu disabled - React UI handles AI via REST API
		// All AI operations now delegate to Saman Labs AI via Integration\AI_Pilot
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_SAMAN_SEO_ai_reset', [ $this, 'handle_reset' ] );
	}

	/**
	 * Load assets only on AI page (kept for backwards compatibility).
	 *
	 * @param string $hook Current admin hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( 'SAMAN_SEO_page_saman-seo-ai' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/css/admin.css',
			[],
			SAMAN_SEO_VERSION
		);
	}

	/**
	 * Reset AI prompts/model to defaults.
	 *
	 * @return void
	 */
	public function handle_reset() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_ai_reset' );

		$settings = new Settings();
		$defaults = $settings->get_defaults();

		$keys = [
			'SAMAN_SEO_ai_prompt_system',
			'SAMAN_SEO_ai_prompt_title',
			'SAMAN_SEO_ai_prompt_description',
		];

		foreach ( $keys as $key ) {
			if ( isset( $defaults[ $key ] ) ) {
				update_option( $key, $defaults[ $key ] );
			}
		}

		$redirect_url = add_query_arg(
			[
				'page'                   => 'saman-seo',
				'SAMAN_SEO_ai_reset' => '1',
			],
			admin_url( 'admin.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}
}
