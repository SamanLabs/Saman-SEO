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
		'wpseopilot_default_title_template' => '{{post_title}} | {{site_title}}',
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
		'wpseopilot_homepage_keywords' => '',
		'wpseopilot_homepage_description_prompt' => '',
		'wpseopilot_homepage_knowledge_type' => 'organization',
		'wpseopilot_homepage_organization_name' => '',
		'wpseopilot_homepage_organization_logo' => '',
		'wpseopilot_title_separator' => '-',
		'wpseopilot_openai_api_key' => '',
		'wpseopilot_default_meta_description' => '',
		'wpseopilot_default_og_image' => '',
		'wpseopilot_social_defaults' => [
			'og_title'            => '',
			'og_description'      => '',
			'twitter_title'       => '',
			'twitter_description' => '',
			'image_source'        => '',
			'schema_itemtype'     => '',
		],
		'wpseopilot_post_type_social_defaults' => [],
		'wpseopilot_default_social_width' => 1200,
		'wpseopilot_default_social_height' => 630,
		'wpseopilot_default_noindex' => '0',
		'wpseopilot_default_nofollow' => '0',
		'wpseopilot_global_robots' => 'index, follow',
		'wpseopilot_hreflang_map' => '',
		'wpseopilot_robots_txt' => '',
		'wpseopilot_enable_sitemap_enhancer' => '1',
		'wpseopilot_enable_redirect_manager' => '1',
		'wpseopilot_enable_404_logging' => '1',
		'wpseopilot_enable_og_preview' => '1',
		'wpseopilot_enable_llm_txt' => '1',
		'wpseopilot_enable_local_seo' => '0',
		'wpseopilot_enable_analytics' => '1',
		'wpseopilot_social_card_design' => [
			'background_color' => '#1a1a36',
			'accent_color'     => '#5a84ff',
			'text_color'       => '#ffffff',
			'title_font_size'  => 48,
			'site_font_size'   => 24,
			'logo_url'         => '',
			'logo_position'    => 'bottom-left',
			'layout'           => 'default',
		],
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

		// Consolidated Search Appearance Settings
		$group = 'wpseopilot_search_appearance';
		register_setting( $group, 'wpseopilot_post_type_title_templates', [ $this, 'sanitize_post_type_templates' ] );
		register_setting( $group, 'wpseopilot_post_type_meta_descriptions', [ $this, 'sanitize_post_type_descriptions' ] );
		register_setting( $group, 'wpseopilot_post_type_keywords', [ $this, 'sanitize_post_type_keywords' ] );
		register_setting( $group, 'wpseopilot_post_type_settings', [ $this, 'sanitize_post_type_settings' ] );
		register_setting( $group, 'wpseopilot_taxonomy_settings', [ $this, 'sanitize_taxonomy_settings' ] );
		register_setting( $group, 'wpseopilot_archive_settings', [ $this, 'sanitize_archive_settings' ] );
		register_setting( $group, 'wpseopilot_homepage_title', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_homepage_description', 'sanitize_textarea_field' );
		register_setting( $group, 'wpseopilot_homepage_keywords', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_title_separator', [ $this, 'sanitize_separator' ] );

		// New consolidated options for Search Appearance page redesign
		register_setting( $group, 'wpseopilot_homepage_defaults', [ $this, 'sanitize_homepage_defaults' ] );
		register_setting( $group, 'wpseopilot_post_type_defaults', [ $this, 'sanitize_post_type_defaults' ] );
		register_setting( $group, 'wpseopilot_taxonomy_defaults', [ $this, 'sanitize_taxonomy_defaults' ] );
		register_setting( $group, 'wpseopilot_archive_defaults', [ $this, 'sanitize_archive_defaults_new' ] );

		// Social settings (also registered under search_appearance group)
		register_setting( $group, 'wpseopilot_social_defaults', [ $this, 'sanitize_social_defaults' ] );
		register_setting( $group, 'wpseopilot_post_type_social_defaults', [ $this, 'sanitize_post_type_social_defaults' ] );
		register_setting( $group, 'wpseopilot_social_card_design', [ $this, 'sanitize_social_card_design' ] );

		// Other settings
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_model', [ $this, 'sanitize_ai_model' ] );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_system', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_title', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_tuning', 'wpseopilot_ai_prompt_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_ai_key', 'wpseopilot_openai_api_key', [ $this, 'sanitize_api_key' ] );
		
		register_setting( 'wpseopilot', 'wpseopilot_homepage_description_prompt', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot_knowledge', 'wpseopilot_homepage_knowledge_type', [ $this, 'sanitize_knowledge_type' ] );
		register_setting( 'wpseopilot_knowledge', 'wpseopilot_homepage_organization_name', 'sanitize_text_field' );
		register_setting( 'wpseopilot_knowledge', 'wpseopilot_homepage_organization_logo', 'esc_url_raw' );
		register_setting( 'wpseopilot', 'wpseopilot_default_meta_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot', 'wpseopilot_default_og_image', 'esc_url_raw' );
		register_setting( 'wpseopilot_social', 'wpseopilot_social_defaults', [ $this, 'sanitize_social_defaults' ] );
		register_setting( 'wpseopilot_social', 'wpseopilot_post_type_social_defaults', [ $this, 'sanitize_post_type_social_defaults' ] );
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
		register_setting( 'wpseopilot', 'wpseopilot_enable_og_preview', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_llm_txt', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_local_seo', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_analytics', [ $this, 'sanitize_bool' ] );
	}

	/**
	 * Get variables available for different contexts.
	 *
	 * @return array
	 */
	public function get_context_variables() {
		$variables = [
			'global' => [
				'label' => __( 'General', 'wp-seo-pilot' ),
				'vars'  => [
					[ 'tag' => 'site_title', 'label' => __( 'Site Title', 'wp-seo-pilot' ), 'desc' => __( 'The main title of your site', 'wp-seo-pilot' ), 'preview' => get_bloginfo( 'name' ) ],
					[ 'tag' => 'tagline', 'label' => __( 'Tagline', 'wp-seo-pilot' ), 'desc' => __( 'Site description / tagline', 'wp-seo-pilot' ), 'preview' => get_bloginfo( 'description' ) ],
					[ 'tag' => 'separator', 'label' => __( 'Separator', 'wp-seo-pilot' ), 'desc' => __( 'Separator character (e.g. -)', 'wp-seo-pilot' ), 'preview' => $this->get( 'wpseopilot_title_separator' ) ],
					[ 'tag' => 'current_year', 'label' => __( 'Current Year', 'wp-seo-pilot' ), 'desc' => __( 'The current year (4 digits)', 'wp-seo-pilot' ), 'preview' => date_i18n( 'Y' ) ],
				],
			],
			'post' => [
				'label' => __( 'Post Variables', 'wp-seo-pilot' ),
				'vars'  => [
					[ 'tag' => 'post_title', 'label' => __( 'Post Title', 'wp-seo-pilot' ), 'desc' => __( 'Title of the current post/page', 'wp-seo-pilot' ), 'preview' => 'Hello World' ],
					[ 'tag' => 'post_excerpt', 'label' => __( 'Excerpt', 'wp-seo-pilot' ), 'desc' => __( 'Post excerpt or auto-generated snippet', 'wp-seo-pilot' ), 'preview' => 'This is an example excerpt...' ],
					[ 'tag' => 'post_date', 'label' => __( 'Date', 'wp-seo-pilot' ), 'desc' => __( 'Publish date', 'wp-seo-pilot' ), 'preview' => date_i18n( get_option( 'date_format' ) ) ],
					[ 'tag' => 'post_author', 'label' => __( 'Author', 'wp-seo-pilot' ), 'desc' => __( 'Display name of the author', 'wp-seo-pilot' ), 'preview' => 'John Doe' ],
					[ 'tag' => 'category', 'label' => __( 'Primary Category', 'wp-seo-pilot' ), 'desc' => __( 'The main category for this post', 'wp-seo-pilot' ), 'preview' => 'Technology' ],
					[ 'tag' => 'modified', 'label' => __( 'Modified Date', 'wp-seo-pilot' ), 'desc' => __( 'Last modified date', 'wp-seo-pilot' ), 'preview' => date_i18n( get_option( 'date_format' ) ) ],
					[ 'tag' => 'id', 'label' => __( 'ID', 'wp-seo-pilot' ), 'desc' => __( 'The numeric post ID', 'wp-seo-pilot' ), 'preview' => '123' ],
				],
			],
			'taxonomy' => [
				'label' => __( 'Taxonomy Variables', 'wp-seo-pilot' ),
				'vars'  => [
					[ 'tag' => 'term_title', 'label' => __( 'Term Name', 'wp-seo-pilot' ), 'desc' => __( 'Name of the current category/tag', 'wp-seo-pilot' ), 'preview' => 'My Category' ],
					[ 'tag' => 'term_description', 'label' => __( 'Term Description', 'wp-seo-pilot' ), 'desc' => __( 'Description of the term', 'wp-seo-pilot' ), 'preview' => 'A list of all posts about...' ],
				],
			],
			'archive' => [
				'label' => __( 'Archive Variables', 'wp-seo-pilot' ),
				'vars'  => [
					[ 'tag' => 'archive_title', 'label' => __( 'Archive Title', 'wp-seo-pilot' ), 'desc' => __( 'Title based on date or type', 'wp-seo-pilot' ), 'preview' => 'Archives for June 2025' ],
					[ 'tag' => 'archive_date', 'label' => __( 'Archive Date', 'wp-seo-pilot' ), 'desc' => __( 'Date for daily/monthly archives', 'wp-seo-pilot' ), 'preview' => 'June 2025' ],
				],
			],
			'author' => [
				'label' => __( 'Author Variables', 'wp-seo-pilot' ),
				'vars'  => [
					[ 'tag' => 'author_name', 'label' => __( 'Author Name', 'wp-seo-pilot' ), 'desc' => __( 'Name of the author being viewed', 'wp-seo-pilot' ), 'preview' => 'Jane Smith' ],
					[ 'tag' => 'author_bio', 'label' => __( 'Author Bio', 'wp-seo-pilot' ), 'desc' => __( 'Biographical info', 'wp-seo-pilot' ), 'preview' => 'Jane is a writer...' ],
				],
			],
		];
		
		// Discover Custom Fields per Post Type
		$post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $post_types as $pt ) {
			$latest = get_posts( [
				'post_type'      => $pt->name,
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'post_status'    => 'publish',
			] );

			if ( ! empty( $latest ) ) {
				$post_id = $latest[0];
				$keys    = get_post_custom_keys( $post_id );
				$custom_vars = [];

				if ( $keys ) {
					foreach ( $keys as $key ) {
						if ( is_protected_meta( $key, 'post' ) ) {
							continue;
						}
						// Get sample value
						$vals = get_post_meta( $post_id, $key, true );
						$preview = is_string( $vals ) && strlen( $vals ) < 50 ? $vals : 'Sample Value';
						
						$custom_vars[] = [
							'tag'     => 'cf_' . $key,
							'label'   => $key,
							'desc'    => sprintf( __( 'Custom Field: %s', 'wp-seo-pilot' ), $key ),
							'preview' => $preview,
						];
					}
				}

				if ( ! empty( $custom_vars ) ) {
					// Use a key like "post_type:book" so frontend can match it
					$context_key = 'post_type:' . $pt->name;
					$variables[ $context_key ] = [
						'label' => sprintf( __( '%s Custom Fields', 'wp-seo-pilot' ), $pt->label ),
						'vars'  => $custom_vars,
					];
				}
			}
		}

		return $variables;
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
			__( 'Defaults', 'wp-seo-pilot' ),
			__( 'Defaults', 'wp-seo-pilot' ),
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
	 * @return string
	 */
	public function sanitize_template( $value ) {
		$value = sanitize_text_field( $value );

		$contexts = $this->get_context_variables();
		$allowed = [];


		foreach ( $contexts as $group ) {
			if ( ! empty( $group['vars'] ) && is_array( $group['vars'] ) ) {
				foreach ( $group['vars'] as $var_def ) {
					$var = $var_def['tag'];
					$allowed[] = '{{' . $var . '}}';
					$allowed[] = '%' . $var . '%';
				}
			}
		}

		// Also allow custom fields patterns if desired, but for now stick to the list.
		// The user mentioned "detect if the theme has custom type yes but how about variables".
		// We'll trust the list for now.

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
	 * Sanitize global social defaults.
	 *
	 * @param array|string $value Values.
	 *
	 * @return array
	 */
	public function sanitize_social_defaults( $value ) {
		if ( ! is_array( $value ) ) {
			$value = [];
		}

		$value = wp_parse_args( $value, [] );

		$schema = sanitize_text_field( $value['schema_itemtype'] ?? '' );

		return [
			'og_title'            => sanitize_text_field( $value['og_title'] ?? '' ),
			'og_description'      => sanitize_textarea_field( $value['og_description'] ?? '' ),
			'twitter_title'       => sanitize_text_field( $value['twitter_title'] ?? '' ),
			'twitter_description' => sanitize_textarea_field( $value['twitter_description'] ?? '' ),
			'image_source'        => esc_url_raw( $value['image_source'] ?? '' ),
			'schema_itemtype'     => $schema,
		];
	}

	/**
	 * Sanitize per-post-type social defaults.
	 *
	 * @param array|string $value Values.
	 *
	 * @return array<string,array<string,string>>
	 */
	public function sanitize_post_type_social_defaults( $value ) {
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

		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		$sanitized = [];
		foreach ( $post_types as $slug => $label ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : [];

			$clean = [
				'og_title'            => sanitize_text_field( $data['og_title'] ?? '' ),
				'og_description'      => sanitize_textarea_field( $data['og_description'] ?? '' ),
				'twitter_title'       => sanitize_text_field( $data['twitter_title'] ?? '' ),
				'twitter_description' => sanitize_textarea_field( $data['twitter_description'] ?? '' ),
				'image_source'        => esc_url_raw( $data['image_source'] ?? '' ),
				'schema_itemtype'     => sanitize_text_field( $data['schema_itemtype'] ?? '' ),
			];

			$clean = array_filter(
				$clean,
				static function ( $field ) {
					return '' !== $field && null !== $field;
				}
			);

			if ( empty( $clean ) ) {
				continue;
			}

			$sanitized[ $slug ] = $clean;
		}

		return $sanitized;
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
	 * Sanitize homepage defaults.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_homepage_defaults( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		// Also update individual options for backward compatibility
		update_option( 'wpseopilot_homepage_title', sanitize_text_field( $value['meta_title'] ?? '' ) );
		update_option( 'wpseopilot_homepage_description', sanitize_textarea_field( $value['meta_description'] ?? '' ) );
		update_option( 'wpseopilot_homepage_keywords', sanitize_text_field( $value['meta_keywords'] ?? '' ) );

		return [
			'meta_title'       => sanitize_text_field( $value['meta_title'] ?? '' ),
			'meta_description' => sanitize_textarea_field( $value['meta_description'] ?? '' ),
			'meta_keywords'    => sanitize_text_field( $value['meta_keywords'] ?? '' ),
		];
	}

	/**
	 * Sanitize post type defaults (new structure).
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_post_type_defaults( $value ) {
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

		$sanitized = [];
		foreach ( $post_types as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : [];

			$sanitized[ $slug ] = [
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
				'schema_type'          => isset( $data['schema_type'] ) ? sanitize_text_field( $data['schema_type'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize taxonomy defaults (new structure).
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_taxonomy_defaults( $value ) {
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
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize archive defaults (new structure).
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_archive_defaults_new( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$allowed = [ 'author', 'date', 'search', '404' ];
		$sanitized = [];

		foreach ( $allowed as $key ) {
			$data = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : [];

			$sanitized[ $key ] = [
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
			];
		}

		return $sanitized;
	}

	/**
	 * Sanitize title separator character.
	 *
	 * @param string $value Separator value.
	 *
	 * @return string
	 */
	public function sanitize_separator( $value ) {
		$value = sanitize_text_field( $value );
		$value = trim( $value );

		// Default to hyphen if empty
		if ( empty( $value ) ) {
			return '-';
		}

		// Limit to 3 characters max
		return mb_substr( $value, 0, 3 );
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
	 * Sanitize social card design settings.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_social_card_design( $value ) {
		if ( ! is_array( $value ) ) {
			return $this->defaults['wpseopilot_social_card_design'];
		}

		return [
			'background_color' => sanitize_hex_color( $value['background_color'] ?? '#1a1a36' ),
			'accent_color'     => sanitize_hex_color( $value['accent_color'] ?? '#5a84ff' ),
			'text_color'       => sanitize_hex_color( $value['text_color'] ?? '#ffffff' ),
			'title_font_size'  => absint( $value['title_font_size'] ?? 48 ),
			'site_font_size'   => absint( $value['site_font_size'] ?? 24 ),
			'logo_url'         => esc_url_raw( $value['logo_url'] ?? '' ),
			'logo_position'    => in_array( $value['logo_position'] ?? '', [ 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'center' ], true )
			                      ? $value['logo_position']
			                      : 'bottom-left',
			'layout'           => in_array( $value['layout'] ?? '', [ 'default', 'centered', 'minimal', 'bold' ], true )
			                      ? $value['layout']
			                      : 'default',
		];
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

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
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

		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

		// Prepare homepage defaults
		$homepage_defaults = [
			'meta_title'       => get_option( 'wpseopilot_homepage_title', '' ),
			'meta_description' => get_option( 'wpseopilot_homepage_description', '' ),
			'meta_keywords'    => get_option( 'wpseopilot_homepage_keywords', '' ),
		];

		// Prepare post type defaults
		$post_type_defaults = get_option( 'wpseopilot_post_type_defaults', [] );
		if ( ! is_array( $post_type_defaults ) ) {
			$post_type_defaults = [];
		}

		// Prepare taxonomy defaults
		$taxonomy_defaults = get_option( 'wpseopilot_taxonomy_defaults', [] );
		if ( ! is_array( $taxonomy_defaults ) ) {
			$taxonomy_defaults = [];
		}

		// Prepare archive defaults with fallback values
		$archive_defaults = get_option( 'wpseopilot_archive_defaults', [] );
		if ( ! is_array( $archive_defaults ) ) {
			$archive_defaults = [];
		}

		// Define default templates for each archive type
		$archive_default_templates = [
			'author' => [
				'noindex'              => '0',
				'title_template'       => '{{author}} {{separator}} {{sitename}}',
				'description_template' => 'Articles written by {{author}}. {{author_bio}}',
			],
			'date'   => [
				'noindex'              => '0',
				'title_template'       => '{{date}} Archives {{separator}} {{sitename}}',
				'description_template' => 'Browse our articles from {{date}}.',
			],
			'search' => [
				'noindex'              => '1',
				'title_template'       => 'Search: {{search_term}} {{separator}} {{sitename}}',
				'description_template' => 'Search results for "{{search_term}}" on {{sitename}}.',
			],
			'404'    => [
				'noindex'              => '1',
				'title_template'       => 'Page Not Found {{separator}} {{sitename}}',
				'description_template' => 'The page you are looking for could not be found.',
			],
		];

		// Merge saved values with defaults (use defaults for empty values)
		foreach ( $archive_default_templates as $type => $defaults ) {
			if ( ! isset( $archive_defaults[ $type ] ) || ! is_array( $archive_defaults[ $type ] ) ) {
				$archive_defaults[ $type ] = $defaults;
			} else {
				// Merge with defaults, but also replace empty strings with defaults
				$archive_defaults[ $type ] = wp_parse_args( $archive_defaults[ $type ], $defaults );

				// Replace empty strings with default values
				foreach ( $defaults as $key => $default_value ) {
					if ( isset( $archive_defaults[ $type ][ $key ] ) && '' === $archive_defaults[ $type ][ $key ] ) {
						$archive_defaults[ $type ][ $key ] = $default_value;
					}
				}
			}
		}

		include WPSEOPILOT_PATH . 'templates/search-appearance.php';
	}
}
