<?php
/**
 * Handles plugin options and settings UI.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Settings controller.
 */
class Settings {

	/**
	 * Option keys with defaults.
	 *
	 * @var array<string,mixed>
	 */
	private $defaults = [
		'wpseopilot_default_title_template' => '%post_title% | %site_title%',
		'wpseopilot_post_type_title_templates' => [],
		'wpseopilot_post_type_meta_descriptions' => [],
		'wpseopilot_post_type_keywords' => [],
		'wpseopilot_post_type_settings' => [],
		'wpseopilot_taxonomy_settings' => [],
		'wpseopilot_archive_settings' => [],
		'wpseopilot_ai_model' => 'gpt-4o-mini',
		'wpseopilot_ai_prompt_system' => 'You are an SEO assistant generating concise metadata. Respond with plain text only.',
		'wpseopilot_ai_prompt_title' => 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.',
		'wpseopilot_ai_prompt_description' => 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.',
		'wpseopilot_homepage_title' => '',
		'wpseopilot_homepage_description' => '',
		'wpseopilot_homepage_description_prompt' => '',
		'wpseopilot_homepage_knowledge_type' => 'organization',
		'wpseopilot_homepage_organization_name' => '',
		'wpseopilot_homepage_organization_logo' => '',
		'wpseopilot_openai_api_key' => '',
		'wpseopilot_default_meta_description' => '',
		'wpseopilot_default_og_image' => '',
		'wpseopilot_default_social_width' => 1200,
		'wpseopilot_default_social_height' => 630,
		'wpseopilot_default_noindex' => '0',
		'wpseopilot_default_nofollow' => '0',
		'wpseopilot_global_robots' => 'index, follow',
		'wpseopilot_hreflang_map' => '',
		'wpseopilot_robots_txt' => '',
		'wpseopilot_enable_sitemap_enhancer' => '0',
		'wpseopilot_enable_redirect_manager' => '1',
		'wpseopilot_enable_404_logging' => '0',
	];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Fetch option with fallback.
	 *
	 * @param string $key Key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		$default = $this->defaults[ $key ] ?? '';

		return get_option( $key, $default );
	}

	/**
	 * Register settings + fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		foreach ( $this->defaults as $key => $default ) {
			add_option( $key, $default );
		}

		register_setting( 'wpseopilot', 'wpseopilot_default_title_template', [ $this, 'sanitize_template' ] );
		register_setting( 'wpseopilot_post_types', 'wpseopilot_post_type_title_templates', [ $this, 'sanitize_post_type_templates' ] );
		register_setting( 'wpseopilot_post_types', 'wpseopilot_post_type_meta_descriptions', [ $this, 'sanitize_post_type_descriptions' ] );
		register_setting( 'wpseopilot_post_types', 'wpseopilot_post_type_keywords', [ $this, 'sanitize_post_type_keywords' ] );
		register_setting( 'wpseopilot_post_types', 'wpseopilot_post_type_settings', [ $this, 'sanitize_post_type_settings' ] );
		register_setting( 'wpseopilot_taxonomies', 'wpseopilot_taxonomy_settings', [ $this, 'sanitize_taxonomy_settings' ] );
		register_setting( 'wpseopilot_archives', 'wpseopilot_archive_settings', [ $this, 'sanitize_archive_settings' ] );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_model', [ $this, 'sanitize_ai_model' ] );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_system', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_title', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_key', 'wpseopilot_openai_api_key', [ $this, 'sanitize_api_key' ] );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_title', 'sanitize_text_field' );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_description_prompt', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_knowledge_type', [ $this, 'sanitize_knowledge_type' ] );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_organization_name', 'sanitize_text_field' );
		register_setting( 'wpseopilot_homepage', 'wpseopilot_homepage_organization_logo', 'esc_url_raw' );
		register_setting( 'wpseopilot', 'wpseopilot_default_meta_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot', 'wpseopilot_default_og_image', 'esc_url_raw' );
		register_setting( 'wpseopilot', 'wpseopilot_default_social_width', 'absint' );
		register_setting( 'wpseopilot', 'wpseopilot_default_social_height', 'absint' );
		register_setting( 'wpseopilot', 'wpseopilot_default_noindex', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_default_nofollow', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_global_robots', 'sanitize_text_field' );
		register_setting( 'wpseopilot', 'wpseopilot_hreflang_map', [ $this, 'sanitize_json' ] );
		register_setting( 'wpseopilot', 'wpseopilot_robots_txt', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot', 'wpseopilot_enable_sitemap_enhancer', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_redirect_manager', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_404_logging', [ $this, 'sanitize_bool' ] );
	}

	/**
	 * Add settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WP SEO Pilot', 'wp-seo-pilot' ),
			__( 'WP SEO Pilot', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot',
			[ $this, 'render_settings_page' ],
			'dashicons-airplane',
			58
		);

		add_submenu_page(
			'wpseopilot',
			__( 'SEO Defaults', 'wp-seo-pilot' ),
			__( 'SEO Defaults', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot',
			[ $this, 'render_settings_page' ]
		);

		add_submenu_page(
			'wpseopilot',
			__( 'Search Appearance', 'wp-seo-pilot' ),
			__( 'Search Appearance', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-types',
			[ $this, 'render_post_type_defaults_page' ]
		);
	}

	/**
	 * Sanitize bool-ish values.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	public function sanitize_bool( $value ) {
		return ( ! empty( $value ) ) ? '1' : '0';
	}

	/**
	 * Ensure template placeholders stay safe.
	 *
	 * @param string $value Template.
	 *
	 * @return string
	 */
	public function sanitize_template( $value ) {
		$value = sanitize_text_field( $value );

		$allowed = [
			'%post_title%',
			'%site_title%',
			'%tagline%',
			'%post_author%',
		];

		return str_replace( $allowed, $allowed, $value );
	}

	/**
	 * Sanitize per-post-type template map.
	 *
	 * @param array|string $value Templates.
	 *
	 * @return array
	 */
	public function sanitize_post_type_templates( $value ) {
		return $this->sanitize_post_type_text_map( $value, [ $this, 'sanitize_template' ] );
	}

	/**
	 * Sanitize per-post-type description map.
	 *
	 * @param array|string $value Descriptions.
	 *
	 * @return array
	 */
	public function sanitize_post_type_descriptions( $value ) {
		return $this->sanitize_post_type_text_map( $value, 'sanitize_textarea_field' );
	}

	/**
	 * Sanitize per-post-type keywords map.
	 *
	 * @param array|string $value Keywords.
	 *
	 * @return array
	 */
	public function sanitize_post_type_keywords( $value ) {
		return $this->sanitize_post_type_text_map( $value, 'sanitize_text_field' );
	}

	/**
	 * Shared sanitizer for associative post-type arrays.
	 *
	 * @param array|string $value Value.
	 * @param callable     $callback Sanitizer callback.
	 *
	 * @return array
	 */
	private function sanitize_post_type_text_map( $value, $callback ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$allowed = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			]
		);

		unset( $allowed['attachment'] );

		$sanitized = [];

		foreach ( $value as $post_type => $text ) {
			if ( ! isset( $allowed[ $post_type ] ) ) {
				continue;
			}

			$text = call_user_func( $callback, $text );
			if ( '' === $text ) {
				continue;
			}

			$sanitized[ $post_type ] = $text;
		}

		return $sanitized;
	}

	/**
	 * Sanitize JSON stored as text.
	 *
	 * @param string $value JSON.
	 *
	 * @return string
	 */
	public function sanitize_json( $value ) {
		$decoded = json_decode( wp_unslash( $value ), true );

		if ( null === $decoded || ! is_array( $decoded ) ) {
			return '';
		}

		return wp_json_encode( array_map( 'esc_url_raw', $decoded ) );
	}

	/**
	 * Ensure selected AI model is supported.
	 *
	 * @param string $value Model identifier.
	 *
	 * @return string
	 */
	public function sanitize_ai_model( $value ) {
		$models = $this->get_ai_models();

		if ( isset( $models[ $value ] ) ) {
			return $value;
		}

		return 'gpt-4o-mini';
	}

	/**
	 * Sanitize knowledge graph representation type.
	 *
	 * @param string $value Submitted value.
	 *
	 * @return string
	 */
	public function sanitize_knowledge_type( $value ) {
		$value = sanitize_key( $value );

		return in_array( $value, [ 'organization', 'person' ], true ) ? $value : 'organization';
	}

	/**
	 * Sanitize per-post-type appearance settings.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_post_type_settings( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'names'
		);

		$page_options    = array_keys( $this->get_schema_page_options() );
		$article_options = array_keys( $this->get_schema_article_options() );

		$sanitized = [];
		foreach ( $post_types as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : [];

			$sanitized[ $slug ] = [
				'show_search'   => ! empty( $data['show_search'] ) ? '1' : '0',
				'show_seo'      => ! empty( $data['show_seo'] ) ? '1' : '0',
				'schema_page'   => in_array( $data['schema_page'] ?? '', $page_options, true ) ? $data['schema_page'] : 'WebPage',
				'schema_article' => in_array( $data['schema_article'] ?? '', $article_options, true ) ? $data['schema_article'] : 'Article',
				'analysis_fields' => isset( $data['analysis_fields'] ) ? sanitize_text_field( $data['analysis_fields'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize taxonomy appearance settings.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_taxonomy_settings( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$taxonomies = get_taxonomies(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'names'
		);

		$sanitized = [];
		foreach ( $taxonomies as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : [];

			$sanitized[ $slug ] = [
				'show_search' => ! empty( $data['show_search'] ) ? '1' : '0',
				'show_seo'    => ! empty( $data['show_seo'] ) ? '1' : '0',
				'title'       => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize archive appearance settings.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_archive_settings( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$allowed = [ 'author', 'date', 'search' ];
		$sanitized = [];

		foreach ( $allowed as $key ) {
			$data = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : [];

			$sanitized[ $key ] = [
				'show'        => ! empty( $data['show'] ) ? '1' : '0',
				'title'       => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize stored OpenAI API key.
	 *
	 * @param string $value Value.
	 *
	 * @return string
	 */
	public function sanitize_api_key( $value ) {
		$value = sanitize_text_field( $value );

		return trim( $value );
	}

	/**
	 * Provide allowed OpenAI model list.
	 *
	 * @return array<string,string>
	 */
	public function get_ai_models() {
		return [
			'gpt-4o-mini'          => __( 'GPT-4o mini (fast, affordable)', 'wp-seo-pilot' ),
			'gpt-4o'               => __( 'GPT-4o (highest quality)', 'wp-seo-pilot' ),
			'gpt-4.1-mini'         => __( 'GPT-4.1 mini', 'wp-seo-pilot' ),
			'gpt-4.1'              => __( 'GPT-4.1', 'wp-seo-pilot' ),
			'gpt-3.5-turbo'        => __( 'GPT-3.5 Turbo', 'wp-seo-pilot' ),
		];
	}

	/**
	 * Schema page type options.
	 *
	 * @return array<string,string>
	 */
	public function get_schema_page_options() {
		return [
			'WebPage'            => __( 'Web Page (default)', 'wp-seo-pilot' ),
			'ItemPage'           => __( 'Item Page', 'wp-seo-pilot' ),
			'ProfilePage'        => __( 'Profile Page', 'wp-seo-pilot' ),
			'ContactPage'        => __( 'Contact Page', 'wp-seo-pilot' ),
			'SearchResultsPage'  => __( 'Search Results Page', 'wp-seo-pilot' ),
		];
	}

	/**
	 * Schema article type options.
	 *
	 * @return array<string,string>
	 */
	public function get_schema_article_options() {
		return [
			'Article'      => __( 'Article (default)', 'wp-seo-pilot' ),
			'BlogPosting'  => __( 'Blog Posting', 'wp-seo-pilot' ),
			'NewsArticle'  => __( 'News Article', 'wp-seo-pilot' ),
			'TechArticle'  => __( 'Tech Article', 'wp-seo-pilot' ),
			'ScholarlyArticle' => __( 'Scholarly Article', 'wp-seo-pilot' ),
		];
	}

	/**
	 * Fetch default value for a registered option key.
	 *
	 * @param string $key Option key.
	 *
	 * @return mixed
	 */
	public function get_default( $key ) {
		return $this->defaults[ $key ] ?? '';
	}

	/**
	 * Retrieve all defaults.
	 *
	 * @return array<string,mixed>
	 */
	public function get_defaults() {
		return $this->defaults;
	}

	/**
	 * Render settings markup.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);

		wp_localize_script(
			'wpseopilot-admin',
			'WPSEOPilotAdmin',
			[
				'mediaTitle'  => __( 'Select default image', 'wp-seo-pilot' ),
				'mediaButton' => __( 'Use image', 'wp-seo-pilot' ),
			]
		);

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		include WPSEOPILOT_PATH . 'templates/settings-page.php';
	}

	/**
	 * Render post type defaults page.
	 *
	 * @return void
	 */
	public function render_post_type_defaults_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		$post_type_templates    = get_option( 'wpseopilot_post_type_title_templates', [] );
		$post_type_descriptions = get_option( 'wpseopilot_post_type_meta_descriptions', [] );
		$post_type_keywords     = get_option( 'wpseopilot_post_type_keywords', [] );
		$post_type_settings     = get_option( 'wpseopilot_post_type_settings', [] );
		$taxonomy_settings      = get_option( 'wpseopilot_taxonomy_settings', [] );
		$archive_settings       = get_option( 'wpseopilot_archive_settings', [] );
		$taxonomies             = get_taxonomies(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		if ( ! is_array( $post_type_templates ) ) {
			$post_type_templates = [];
		}

		if ( ! is_array( $post_type_descriptions ) ) {
			$post_type_descriptions = [];
		}

		if ( ! is_array( $post_type_keywords ) ) {
			$post_type_keywords = [];
		}

		if ( ! is_array( $post_type_settings ) ) {
			$post_type_settings = [];
		}

		if ( ! is_array( $taxonomy_settings ) ) {
			$taxonomy_settings = [];
		}

		if ( ! is_array( $archive_settings ) ) {
			$archive_settings = [];
		}

		include WPSEOPILOT_PATH . 'templates/post-type-defaults.php';
	}
}
