<?php
/**
 * Core plugin bootstrap.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered subsystems.
	 *
	 * @var array<string,object>
	 */
	private $services = [];

	/**
	 * Retrieve singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot() {
		if ( did_action( 'wpseopilot_booted' ) ) {
			return;
		}

		$this->register( 'compatibility', new Service\Compatibility() );
		$this->register( 'settings', new Service\Settings() );
		$this->register( 'meta', new Service\Post_Meta() );
		$this->register( 'frontend', new Service\Frontend() );
		$this->register( 'jsonld', new Service\JsonLD() );
		$this->register( 'admin', new Service\Admin_UI() );
		$this->register( 'ai', new Service\AI_Assistant() );
		$this->register( 'internal_links', new Service\Internal_Linking() );
		$this->register( 'importer', new Service\Importers() );
		$this->register( 'redirects', new Service\Redirect_Manager() );
		$this->register( 'audit', new Service\Audit() );
		$this->register( 'sitemap', new Service\Sitemap_Enhancer() );
		$this->register( 'sitemap_settings', new Service\Sitemap_Settings() );
		$this->register( 'social_settings', new Service\Social_Settings() );
		$this->register( 'robots', new Service\Robots_Manager() );
		$this->register( 'monitor', new Service\Request_Monitor() );
		$this->register( 'social_card', new Service\Social_Card_Generator() );
		$this->register( 'llm_txt', new Service\LLM_TXT_Generator() );
		$this->register( 'local_seo', new Service\Local_SEO() );
		$this->register( 'analytics', new Service\Analytics() );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register( 'cli', new Service\CLI() );
		}

		do_action( 'wpseopilot_booted', $this );
	}

	/**
	 * Store a service for later retrieval.
	 *
	 * @param string $key Identifier.
	 * @param object $service Service instance.
	 *
	 * @return void
	 */
	private function register( $key, $service ) {
		if ( method_exists( $service, 'boot' ) ) {
			$service->boot();
		}

		$this->services[ $key ] = $service;
	}

	/**
	 * Retrieve service by key.
	 *
	 * @param string $key ID.
	 *
	 * @return object|null
	 */
	public function get( $key ) {
		return $this->services[ $key ] ?? null;
	}

	/**
	 * Plugin activation handler.
	 *
	 * @return void
	 */
	public static function activate() {
		Service\Analytics::track_activation();

		( new Service\Redirect_Manager() )->create_tables();
		( new Service\Request_Monitor() )->create_tables();
		Service\Internal_Linking::activate();

		add_option( 'wpseopilot_default_title_template', '{{post_title}} | {{site_title}}' );
		add_option( 'wpseopilot_post_type_title_templates', [] );
		add_option( 'wpseopilot_post_type_meta_descriptions', [] );
		add_option( 'wpseopilot_post_type_keywords', [] );
		add_option( 'wpseopilot_openai_api_key', '' );
		add_option( 'wpseopilot_ai_model', 'gpt-4o-mini' );
		add_option( 'wpseopilot_ai_prompt_system', 'You are an SEO assistant generating concise metadata. Respond with plain text only.' );
		add_option( 'wpseopilot_ai_prompt_title', 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.' );
		add_option( 'wpseopilot_ai_prompt_description', 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.' );
		add_option( 'wpseopilot_default_meta_description', '' );
		add_option( 'wpseopilot_default_og_image', '' );
		add_option( 'wpseopilot_show_tour', '1' );
		add_option( 'wpseopilot_enable_sitemap_enhancer', '1' );
		add_option( 'wpseopilot_enable_redirect_manager', '1' );
		add_option( 'wpseopilot_enable_404_logging', '1' );
		add_option( 'wpseopilot_enable_llm_txt', '1' );
		add_option( 'wpseopilot_llm_txt_posts_per_type', 50 );
		add_option( 'wpseopilot_llm_txt_title', '' );
		add_option( 'wpseopilot_llm_txt_description', '' );
		add_option( 'wpseopilot_llm_txt_include_excerpt', '1' );
		add_option( 'wpseopilot_enable_analytics', '1' );

		// Sitemap settings defaults
		add_option( 'wpseopilot_sitemap_enabled', '1' );
		add_option( 'wpseopilot_sitemap_max_urls', 1000 );
		add_option( 'wpseopilot_sitemap_enable_index', '1' );
		add_option( 'wpseopilot_sitemap_dynamic_generation', '1' );
		add_option( 'wpseopilot_sitemap_schedule_updates', '' );
		add_option( 'wpseopilot_sitemap_post_types', [] );
		add_option( 'wpseopilot_sitemap_taxonomies', [] );
		add_option( 'wpseopilot_sitemap_include_author_pages', '0' );
		add_option( 'wpseopilot_sitemap_include_date_archives', '0' );
		add_option( 'wpseopilot_sitemap_exclude_images', '0' );
		add_option( 'wpseopilot_sitemap_enable_rss', '0' );
		add_option( 'wpseopilot_sitemap_enable_google_news', '0' );
		add_option( 'wpseopilot_sitemap_google_news_name', get_bloginfo( 'name' ) );
		add_option( 'wpseopilot_sitemap_google_news_post_types', [] );
		add_option( 'wpseopilot_sitemap_additional_pages', [] );

		if ( '1' === get_option( 'wpseopilot_enable_sitemap_enhancer', '1' ) ) {
			( new Service\Sitemap_Enhancer() )->register_custom_sitemap();
		}

		if ( '1' === get_option( 'wpseopilot_enable_llm_txt', '1' ) ) {
			( new Service\LLM_TXT_Generator() )->register_rewrite_rules();
		}

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
