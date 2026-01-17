<?php
/**
 * Saman Labs AI Integration
 *
 * Central integration layer for Saman Labs AI (formerly WP AI Pilot).
 * All AI functionality is delegated to the Saman Labs AI plugin.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with Saman Labs AI plugin.
 *
 * Note: The underlying function names (wp_ai_pilot_*) will be updated
 * when the Saman Labs AI plugin is fully renamed. This class abstracts
 * the integration to minimize future changes.
 */
class AI_Pilot {

	/**
	 * Plugin source identifier for usage tracking.
	 */
	const SOURCE = 'saman-seo';

	/**
	 * Initialize the integration.
	 * Called during plugin initialization.
	 */
	public static function init(): void {
		// Register with Saman Labs AI when it loads.
		add_action( 'wp_ai_pilot_loaded', [ __CLASS__, 'register_with_ai_pilot' ], 10 );

		// Also try on init in case Saman Labs AI loaded first.
		add_action( 'init', [ __CLASS__, 'maybe_register' ], 20 );
	}

	/**
	 * Check if Saman Labs AI function exists and register.
	 */
	public static function maybe_register(): void {
		if ( function_exists( 'wp_ai_pilot' ) ) {
			self::register_with_ai_pilot();
		}
	}

	/**
	 * Register plugin and assistants with Saman Labs AI.
	 */
	public static function register_with_ai_pilot(): void {
		static $registered = false;

		if ( $registered ) {
			return;
		}

		if ( ! function_exists( 'wp_ai_pilot' ) ) {
			return;
		}

		// Register the plugin.
		self::register_plugin();

		// Register SEO assistants.
		self::register_seo_assistants();

		$registered = true;
	}

	// =========================================================================
	// Status Methods
	// =========================================================================

	/**
	 * Check if Saman Labs AI is installed.
	 *
	 * @return bool
	 */
	public static function is_installed(): bool {
		return function_exists( 'wp_ai_pilot_is_installed' )
			&& wp_ai_pilot_is_installed();
	}

	/**
	 * Check if Saman Labs AI is active.
	 *
	 * @return bool
	 */
	public static function is_active(): bool {
		return function_exists( 'wp_ai_pilot_is_active' )
			&& wp_ai_pilot_is_active();
	}

	/**
	 * Check if Saman Labs AI is ready (active + configured).
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
	 *
	 * @return bool
	 */
	public static function ai_enabled(): bool {
		return self::is_ready();
	}

	/**
	 * Get the AI provider being used.
	 *
	 * @return string 'Saman-ai' or 'none'.
	 */
	public static function get_provider(): string {
		return self::is_ready() ? 'Saman-ai' : 'none';
	}

	// =========================================================================
	// Registration
	// =========================================================================

	/**
	 * Register Saman SEO with Saman Labs AI.
	 */
	public static function register_plugin(): void {
		if ( ! function_exists( 'wp_ai_pilot' ) ) {
			return;
		}

		wp_ai_pilot()->register_plugin( [
			'slug'        => 'saman-seo',
			'file'        => 'saman-seo/saman-seo.php',
			'name'        => 'Saman SEO',
			'permissions' => [ 'generate', 'chat', 'assistants' ],
		] );
	}

	/**
	 * Register SEO Assistants with Saman Labs AI.
	 */
	public static function register_seo_assistants(): void {
		if ( ! function_exists( 'wp_ai_pilot' ) ) {
			return;
		}

		// General SEO Assistant
		wp_ai_pilot()->register_assistant( [
			'id'                 => 'seo-general',
			'name'               => __( 'SEO Assistant', 'saman-seo' ),
			'description'        => __( 'Your helpful SEO buddy for all things search optimization.', 'saman-seo' ),
			'plugin'             => 'saman-seo/saman-seo.php',
			'system_prompt'      => self::get_general_seo_prompt(),
			'icon'               => 'dashicons-search',
			'color'              => '#3b82f6',
			'model'              => 'gpt-4o-mini',
			'temperature'        => 0.7,
			'max_tokens'         => 1000,
			'save_conversations' => true,
			'suggested_prompts'  => [
				__( 'How do I write a good meta description?', 'saman-seo' ),
				__( 'What makes a title tag effective?', 'saman-seo' ),
				__( 'Help me find keywords for my blog post', 'saman-seo' ),
				__( 'What are internal links and why do they matter?', 'saman-seo' ),
			],
		] );

		// SEO Reporter Assistant
		wp_ai_pilot()->register_assistant( [
			'id'                 => 'seo-reporter',
			'name'               => __( 'SEO Reporter', 'saman-seo' ),
			'description'        => __( 'Your weekly SEO buddy that gives you the rundown on your site.', 'saman-seo' ),
			'plugin'             => 'saman-seo/saman-seo.php',
			'system_prompt'      => self::get_reporter_prompt(),
			'icon'               => 'dashicons-chart-bar',
			'color'              => '#8b5cf6',
			'model'              => 'gpt-4o-mini',
			'temperature'        => 0.7,
			'max_tokens'         => 1500,
			'save_conversations' => true,
			'suggested_prompts'  => [
				__( 'Give me a quick SEO report', 'saman-seo' ),
				__( 'What SEO issues should I fix first?', 'saman-seo' ),
				__( 'Check my meta titles and descriptions', 'saman-seo' ),
				__( 'Find posts missing SEO data', 'saman-seo' ),
			],
		] );
	}

	// =========================================================================
	// System Prompts
	// =========================================================================

	/**
	 * Get General SEO Assistant system prompt.
	 *
	 * @return string
	 */
	private static function get_general_seo_prompt(): string {
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		return "You are a helpful SEO assistant for WordPress websites.

CONTEXT:
- Site: {$site_name}
- URL: {$site_url}

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
	 * Get SEO Reporter system prompt.
	 *
	 * @return string
	 */
	private static function get_reporter_prompt(): string {
		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();

		// Gather site stats for context.
		$post_count = wp_count_posts( 'post' );
		$page_count = wp_count_posts( 'page' );
		$total_posts = $post_count->publish ?? 0;
		$total_pages = $page_count->publish ?? 0;

		return "You are an SEO Reporter that provides friendly updates about a WordPress site's SEO health.

CONTEXT:
- Site: {$site_name}
- URL: {$site_url}
- Published posts: {$total_posts}
- Published pages: {$total_pages}

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

	// =========================================================================
	// AI Generation
	// =========================================================================

	/**
	 * Get available models from Saman Labs AI.
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
	 * Generate text using Saman Labs AI.
	 *
	 * @param string $content The content to analyze.
	 * @param string $type    'title' or 'description'.
	 * @param array  $context Optional context data.
	 *
	 * @return string|\WP_Error Generated text or error.
	 */
	public static function generate( string $content, string $type = 'title', array $context = [] ) {
		if ( ! self::is_ready() ) {
			return new \WP_Error(
				'ai_not_ready',
				__( 'Saman Labs AI is not configured. Please install and configure Saman Labs AI to use AI features.', 'saman-seo' )
			);
		}

		$system_prompt = self::get_generation_prompt( $type );
		$user_prompt   = self::build_generation_prompt( $content, $type, $context );

		return wp_ai_pilot()->generate( $user_prompt, [
			'source'        => self::SOURCE,
			'system_prompt' => $system_prompt,
			'max_tokens'    => 'title' === $type ? 100 : 250,
			'temperature'   => 0.7,
		] );
	}

	/**
	 * Chat with message history using Saman Labs AI.
	 *
	 * @param array $messages Message array with role/content.
	 * @param array $options  Optional settings.
	 *
	 * @return array|\WP_Error Response array or error.
	 */
	public static function chat( array $messages, array $options = [] ) {
		if ( ! self::is_ready() ) {
			return new \WP_Error(
				'ai_not_ready',
				__( 'Saman Labs AI is not configured.', 'saman-seo' )
			);
		}

		$defaults = [
			'source'      => self::SOURCE,
			'max_tokens'  => 1000,
			'temperature' => 0.7,
		];

		return wp_ai_pilot()->chat( $messages, array_merge( $defaults, $options ) );
	}

	/**
	 * Chat with a specific assistant.
	 *
	 * @param string $assistant_id The assistant ID.
	 * @param string $message      The user message.
	 * @param array  $context      Optional context.
	 *
	 * @return array|\WP_Error Response or error.
	 */
	public static function assistant_chat( string $assistant_id, string $message, array $context = [] ) {
		if ( ! self::is_ready() ) {
			return new \WP_Error(
				'ai_not_ready',
				__( 'Saman Labs AI is not configured.', 'saman-seo' )
			);
		}

		// Add SEO-specific context.
		$seo_context = self::build_seo_context( $context );

		return wp_ai_pilot()->assistant_chat( $assistant_id, $message, array_merge( $context, [
			'seo_context' => $seo_context,
			'source'      => self::SOURCE,
		] ) );
	}

	// =========================================================================
	// Prompt Helpers
	// =========================================================================

	/**
	 * Get system prompt for SEO generation.
	 *
	 * @param string $type 'title' or 'description'.
	 *
	 * @return string System prompt.
	 */
	private static function get_generation_prompt( string $type ): string {
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
	 * Build user prompt for generation.
	 *
	 * @param string $content The content to analyze.
	 * @param string $type    'title' or 'description'.
	 * @param array  $context Optional context.
	 *
	 * @return string User prompt.
	 */
	private static function build_generation_prompt( string $content, string $type, array $context = [] ): string {
		$prompt = '';

		if ( ! empty( $context['keyword'] ) ) {
			$prompt .= "Target keyword: {$context['keyword']}\n\n";
		}

		if ( ! empty( $context['post_title'] ) ) {
			$prompt .= "Page title: {$context['post_title']}\n\n";
		}

		$prompt .= "Content:\n{$content}";

		return $prompt;
	}

	/**
	 * Build SEO context for assistant chat.
	 *
	 * @param array $context Request context.
	 *
	 * @return string Context string.
	 */
	private static function build_seo_context( array $context = [] ): string {
		$parts = [];

		// Basic site info.
		$parts[] = 'Site: ' . get_bloginfo( 'name' );
		$parts[] = 'URL: ' . home_url();

		// Post context if available.
		if ( ! empty( $context['post_id'] ) ) {
			$post = get_post( intval( $context['post_id'] ) );
			if ( $post ) {
				$parts[] = "\nCurrent post:";
				$parts[] = '- Title: ' . $post->post_title;
				$parts[] = '- Type: ' . $post->post_type;
				$parts[] = '- Status: ' . $post->post_status;

				$meta_title = get_post_meta( $post->ID, '_SAMAN_SEO_title', true );
				$meta_desc  = get_post_meta( $post->ID, '_SAMAN_SEO_description', true );

				if ( $meta_title ) {
					$parts[] = '- SEO Title: ' . $meta_title;
				}
				if ( $meta_desc ) {
					$parts[] = '- SEO Description: ' . $meta_desc;
				}
			}
		}

		// Site stats.
		$post_count = wp_count_posts( 'post' );
		$page_count = wp_count_posts( 'page' );
		$parts[] = "\nSite stats:";
		$parts[] = '- Published posts: ' . ( $post_count->publish ?? 0 );
		$parts[] = '- Published pages: ' . ( $page_count->publish ?? 0 );

		return implode( "\n", $parts );
	}

	// =========================================================================
	// Usage Tracking
	// =========================================================================

	/**
	 * Get usage statistics for Saman SEO.
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
	 * Get registered assistants from Saman Labs AI that belong to Saman SEO.
	 *
	 * @return array Array of assistants.
	 */
	public static function get_seo_assistants(): array {
		if ( ! self::is_ready() ) {
			return [];
		}

		$all = wp_ai_pilot()->get_assistants( true );

		// Filter to Saman SEO assistants.
		return array_filter( $all, function ( $assistant ) {
			return isset( $assistant['plugin'] ) &&
			       $assistant['plugin'] === 'saman-seo/saman-seo.php';
		} );
	}
}
