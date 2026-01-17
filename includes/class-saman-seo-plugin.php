<?php
/**
 * Core plugin bootstrap.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO;

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
		if ( did_action( 'SAMAN_SEO_booted' ) ) {
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
		$this->register( 'admin_bar', new Service\Admin_Bar() );
		$this->register( 'dashboard_widget', new Service\Dashboard_Widget() );
		$this->register( 'link_health', new Service\Link_Health() );
		$this->register( 'breadcrumbs', new Service\Breadcrumbs() );
		$this->register( 'video_schema', new Service\Video_Schema() );
		$this->register( 'course_schema', new Service\Course_Schema() );
		$this->register( 'software_schema', new Service\Software_Schema() );
		$this->register( 'book_schema', new Service\Book_Schema() );
		$this->register( 'music_schema', new Service\Music_Schema() );
		$this->register( 'movie_schema', new Service\Movie_Schema() );
		$this->register( 'restaurant_schema', new Service\Restaurant_Schema() );
		$this->register( 'service_schema', new Service\Service_Schema() );
		$this->register( 'job_posting_schema', new Service\Job_Posting_Schema() );
		$this->register( 'indexnow', new Service\IndexNow() );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register( 'cli', new Service\CLI() );
		}

		// Note: AI Pilot integration is handled in saman-seo.php via AI_Pilot::init()

		do_action( 'SAMAN_SEO_booted', $this );
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
		( new Service\Link_Health() )->create_tables();
		( new Service\IndexNow() )->create_tables();
		Service\Internal_Linking::activate();

		add_option( 'SAMAN_SEO_default_title_template', '{{post_title}} | {{site_title}}' );
		add_option( 'SAMAN_SEO_post_type_title_templates', [] );
		add_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );
		add_option( 'SAMAN_SEO_post_type_keywords', [] );
		// AI prompt customization (API keys and model selection handled by Saman Labs AI plugin)
		add_option( 'SAMAN_SEO_ai_prompt_system', 'You are an SEO assistant generating concise metadata. Respond with plain text only.' );
		add_option( 'SAMAN_SEO_ai_prompt_title', 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.' );
		add_option( 'SAMAN_SEO_ai_prompt_description', 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.' );
		add_option( 'SAMAN_SEO_default_meta_description', '' );
		add_option( 'SAMAN_SEO_default_og_image', '' );
		add_option( 'SAMAN_SEO_show_tour', '1' );
		// Legacy enable options (kept for backward compatibility).
		add_option( 'SAMAN_SEO_enable_sitemap_enhancer', '1' );
		add_option( 'SAMAN_SEO_enable_redirect_manager', '1' );
		add_option( 'SAMAN_SEO_enable_404_logging', '1' );
		add_option( 'SAMAN_SEO_enable_llm_txt', '1' );
		add_option( 'SAMAN_SEO_llm_txt_posts_per_type', 50 );
		add_option( 'SAMAN_SEO_llm_txt_title', '' );
		add_option( 'SAMAN_SEO_llm_txt_description', '' );
		add_option( 'SAMAN_SEO_llm_txt_include_excerpt', '1' );
		add_option( 'SAMAN_SEO_enable_analytics', '1' );
		add_option( 'SAMAN_SEO_enable_admin_bar', '1' );

		// New module toggle options (used by React UI).
		add_option( 'SAMAN_SEO_module_sitemap', '1' );
		add_option( 'SAMAN_SEO_module_redirects', '1' );
		add_option( 'SAMAN_SEO_module_404_log', '1' );
		add_option( 'SAMAN_SEO_module_llm_txt', '1' );
		add_option( 'SAMAN_SEO_module_local_seo', '0' );
		add_option( 'SAMAN_SEO_module_social_cards', '1' );
		add_option( 'SAMAN_SEO_module_analytics', '1' );
		add_option( 'SAMAN_SEO_module_admin_bar', '1' );
		add_option( 'SAMAN_SEO_module_internal_links', '1' );
		add_option( 'SAMAN_SEO_module_ai_assistant', '1' );

		// Sitemap settings defaults
		add_option( 'SAMAN_SEO_sitemap_enabled', '1' );
		add_option( 'SAMAN_SEO_sitemap_max_urls', 1000 );
		add_option( 'SAMAN_SEO_sitemap_enable_index', '1' );
		add_option( 'SAMAN_SEO_sitemap_dynamic_generation', '1' );
		add_option( 'SAMAN_SEO_sitemap_schedule_updates', '' );
		add_option( 'SAMAN_SEO_sitemap_post_types', [] );
		add_option( 'SAMAN_SEO_sitemap_taxonomies', [] );
		add_option( 'SAMAN_SEO_sitemap_include_author_pages', '0' );
		add_option( 'SAMAN_SEO_sitemap_include_date_archives', '0' );
		add_option( 'SAMAN_SEO_sitemap_exclude_images', '0' );
		add_option( 'SAMAN_SEO_sitemap_enable_rss', '0' );
		add_option( 'SAMAN_SEO_sitemap_enable_google_news', '0' );
		add_option( 'SAMAN_SEO_sitemap_google_news_name', get_bloginfo( 'name' ) );
		add_option( 'SAMAN_SEO_sitemap_google_news_post_types', [] );
		add_option( 'SAMAN_SEO_sitemap_additional_pages', [] );

		if ( \Saman\SEO\Helpers\module_enabled( 'sitemap' ) ) {
			( new Service\Sitemap_Enhancer() )->register_custom_sitemap();
		}

		if ( \Saman\SEO\Helpers\module_enabled( 'llm_txt' ) ) {
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
