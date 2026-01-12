<?php
/**
 * WP AI Pilot Integration
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WP AI Pilot plugin.
 */
class AI_Pilot {

	/**
	 * Plugin source identifier for usage tracking.
	 */
	const SOURCE = 'wp-seo-pilot';

	/**
	 * Check if WP AI Pilot is installed.
	 *
	 * @return bool
	 */
	public static function is_installed(): bool {
		return function_exists( 'wp_ai_pilot_is_installed' )
			&& wp_ai_pilot_is_installed();
	}

	/**
	 * Check if WP AI Pilot is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return function_exists( 'wp_ai_pilot_is_active' )
			&& wp_ai_pilot_is_active();
	}

	/**
	 * Check if WP AI Pilot is ready (active + configured).
	 *
	 * @return bool
	 */
	public static function is_ready(): bool {
		return function_exists( 'wp_ai_pilot_is_ready' )
			&& wp_ai_pilot_is_ready();
	}

	/**
	 * Get complete status information.
	 *
	 * @return array Status array with installed, active, ready, version, providers, models.
	 */
	public static function get_status(): array {
		if ( function_exists( 'wp_ai_pilot_get_status' ) ) {
			return wp_ai_pilot_get_status();
		}

		return [
			'installed' => false,
			'active'    => false,
			'ready'     => false,
			'version'   => null,
			'providers' => [],
			'models'    => [],
		];
	}

	/**
	 * Check if AI features should be enabled.
	 * Uses WP AI Pilot if available, falls back to native keys.
	 *
	 * @return bool
	 */
	public static function ai_enabled(): bool {
		// First check WP AI Pilot
		if ( self::is_ready() ) {
			return true;
		}

		// Fallback to SEO Pilot's own keys (legacy support)
		return ! empty( get_option( 'wpseopilot_openai_api_key', '' ) )
			|| ! empty( get_option( 'wpseopilot_anthropic_api_key', '' ) )
			|| ! empty( get_option( 'wpseopilot_google_api_key', '' ) );
	}

	/**
	 * Get the AI provider being used.
	 *
	 * @return string 'wp-ai-pilot', 'native', or 'none'.
	 */
	public static function get_provider(): string {
		if ( self::is_ready() ) {
			return 'wp-ai-pilot';
		}

		if ( ! empty( get_option( 'wpseopilot_openai_api_key', '' ) )
			|| ! empty( get_option( 'wpseopilot_anthropic_api_key', '' ) )
			|| ! empty( get_option( 'wpseopilot_google_api_key', '' ) ) ) {
			return 'native';
		}

		return 'none';
	}

	/**
	 * Get available models from WP AI Pilot.
	 *
	 * @return array Array of model configurations.
	 */
	public static function get_models(): array {
		if ( ! self::is_ready() ) {
			return [];
		}

		return wp_ai_pilot()->get_models();
	}

	/**
	 * Generate text using WP AI Pilot.
	 *
	 * @param string $prompt  The prompt to send.
	 * @param string $type    'title' or 'description'.
	 * @param array  $context Optional context data.
	 *
	 * @return string|\WP_Error Generated text or error.
	 */
	public static function generate( string $prompt, string $type = 'title', array $context = [] ) {
		if ( ! self::is_ready() ) {
			return new \WP_Error( 'ai_not_ready', __( 'WP AI Pilot is not configured.', 'wp-seo-pilot' ) );
		}

		$system_prompt = self::get_system_prompt( $type );

		return wp_ai_pilot()->generate( $prompt, [
			'source'        => self::SOURCE,
			'system_prompt' => $system_prompt,
			'max_tokens'    => 'title' === $type ? 100 : 250,
			'temperature'   => 0.7,
		] );
	}

	/**
	 * Chat with message history using WP AI Pilot.
	 *
	 * @param array $messages Message array with role/content.
	 * @param array $options  Optional settings.
	 *
	 * @return array|\WP_Error Response array or error.
	 */
	public static function chat( array $messages, array $options = [] ) {
		if ( ! self::is_ready() ) {
			return new \WP_Error( 'ai_not_ready', __( 'WP AI Pilot is not configured.', 'wp-seo-pilot' ) );
		}

		$defaults = [
			'source'      => self::SOURCE,
			'max_tokens'  => 500,
			'temperature' => 0.7,
		];

		return wp_ai_pilot()->chat( $messages, array_merge( $defaults, $options ) );
	}

	/**
	 * Get system prompt for SEO generation.
	 *
	 * @param string $type 'title' or 'description'.
	 *
	 * @return string System prompt.
	 */
	private static function get_system_prompt( string $type ): string {
		if ( 'title' === $type ) {
			return "You are an SEO expert specializing in writing compelling page titles.

Requirements:
- Maximum 60 characters (strict limit)
- Include the primary keyword near the beginning
- Make it click-worthy but not clickbait
- Use power words when appropriate
- Match search intent

Return ONLY the title text. No quotes, no explanation, no alternatives.";
		}

		return "You are an SEO expert specializing in writing meta descriptions.

Requirements:
- Maximum 155 characters (strict limit)
- Include a clear call-to-action
- Summarize the page content accurately
- Include the primary keyword naturally
- Create urgency or curiosity when appropriate

Return ONLY the description text. No quotes, no explanation, no alternatives.";
	}

	/**
	 * Get usage statistics for WP SEO Pilot.
	 *
	 * @param string $period '24hours', '7days', '30days', '90days', 'all'.
	 *
	 * @return array Usage statistics.
	 */
	public static function get_usage( string $period = '30days' ): array {
		if ( ! self::is_ready() ) {
			return [];
		}

		return wp_ai_pilot()->get_usage( self::SOURCE, $period );
	}

	/**
	 * Register WP SEO Pilot with WP AI Pilot.
	 *
	 * @return void
	 */
	public static function register_plugin(): void {
		if ( ! function_exists( 'wp_ai_pilot' ) ) {
			return;
		}

		wp_ai_pilot()->register_plugin( [
			'slug'        => 'wp-seo-pilot',
			'file'        => 'wp-seo-pilot/wp-seo-pilot.php',
			'name'        => 'WP SEO Pilot',
			'permissions' => [ 'generate', 'chat', 'assistants' ],
		] );
	}

	/**
	 * Register SEO Assistant with WP AI Pilot.
	 *
	 * @return void
	 */
	public static function register_assistant(): void {
		if ( ! function_exists( 'wp_ai_pilot' ) ) {
			return;
		}

		wp_ai_pilot()->register_assistant( [
			'id'                 => 'seo-pilot-assistant',
			'name'               => 'SEO Assistant',
			'description'        => 'Expert SEO advice for titles, descriptions, and content optimization.',
			'plugin'             => 'wp-seo-pilot/wp-seo-pilot.php',
			'system_prompt'      => "You are an expert SEO consultant with deep knowledge of:
- On-page SEO optimization
- Meta title and description best practices
- Keyword research and placement
- Content optimization
- Technical SEO fundamentals
- Search intent matching
- SERP feature optimization

Help users optimize their WordPress content for search engines. Provide specific, actionable advice. When generating titles or descriptions, always respect character limits (60 for titles, 155 for descriptions).

Current site: " . get_bloginfo( 'name' ) . "
Site tagline: " . get_bloginfo( 'description' ),
			'icon'               => 'search',
			'color'              => '#10b981',
			'model'              => 'gpt-4o-mini',
			'temperature'        => 0.7,
			'max_tokens'         => 1000,
			'supports_vision'    => false,
			'save_conversations' => true,
			'suggested_prompts'  => [
				'Analyze my homepage SEO',
				'Write a meta title for my About page',
				'How can I improve my keyword targeting?',
				'What makes a good meta description?',
			],
		] );
	}
}
