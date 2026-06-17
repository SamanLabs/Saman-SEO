<?php
/**
 * Handles plugin options and settings UI.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
	private $defaults = array(
		'SAMAN_SEO_default_title_template'      => '{{post_title}} | {{site_title}}',
		'SAMAN_SEO_post_type_title_templates'   => array(),
		'SAMAN_SEO_post_type_meta_descriptions' => array(),
		'SAMAN_SEO_post_type_keywords'          => array(),
		'SAMAN_SEO_post_type_settings'          => array(),
		'SAMAN_SEO_taxonomy_settings'           => array(),
		'SAMAN_SEO_archive_settings'            => array(),
		// AI prompt customization (model selection handled by Saman Labs AI)
		'SAMAN_SEO_ai_prompt_system'            => 'You are an SEO assistant generating concise metadata. Respond with plain text only.',
		'SAMAN_SEO_ai_prompt_title'             => 'Write an SEO meta title (max 60 characters) that is compelling and includes the primary topic.',
		'SAMAN_SEO_ai_prompt_description'       => 'Write a concise SEO meta description (max 155 characters) summarizing the content and inviting clicks.',
		'SAMAN_SEO_homepage_title'              => '',
		'SAMAN_SEO_homepage_description'        => '',
		'SAMAN_SEO_homepage_keywords'           => '',
		'SAMAN_SEO_homepage_description_prompt' => '',
		'SAMAN_SEO_homepage_knowledge_type'     => 'organization',
		'SAMAN_SEO_homepage_organization_name'  => '',
		'SAMAN_SEO_homepage_organization_logo'  => '',
		'SAMAN_SEO_homepage_person_name'        => '',
		'SAMAN_SEO_homepage_person_image'       => '',
		'SAMAN_SEO_homepage_person_job_title'   => '',
		'SAMAN_SEO_homepage_person_url'         => '',
		'SAMAN_SEO_homepage_social_profiles'    => '',
		'SAMAN_SEO_title_separator'             => '-',
		'SAMAN_SEO_default_meta_description'    => '',
		'SAMAN_SEO_default_og_image'            => '',
		'SAMAN_SEO_social_defaults'             => array(
			'og_title'            => '',
			'og_description'      => '',
			'twitter_title'       => '',
			'twitter_description' => '',
			'image_source'        => '',
			'schema_itemtype'     => '',
		),
		'SAMAN_SEO_post_type_social_defaults'   => array(),
		'SAMAN_SEO_default_social_width'        => 1200,
		'SAMAN_SEO_default_social_height'       => 630,
		'SAMAN_SEO_default_noindex'             => '0',
		'SAMAN_SEO_default_nofollow'            => '0',
		'SAMAN_SEO_global_robots'               => 'index, follow',
		'SAMAN_SEO_hreflang_map'                => '',
		'SAMAN_SEO_robots_txt'                  => '',
		// Legacy enable_* options - @deprecated Use SAMAN_SEO_module_* options instead.
		// Kept for backward compatibility; the module_enabled() helper handles migration.
		'SAMAN_SEO_enable_sitemap_enhancer'     => '1',
		'SAMAN_SEO_enable_redirect_manager'     => '1',
		'SAMAN_SEO_enable_404_logging'          => '1',
		'SAMAN_SEO_enable_og_preview'           => '1',
		'SAMAN_SEO_enable_llm_txt'              => '1',
		'SAMAN_SEO_enable_local_seo'            => '0',
		'SAMAN_SEO_enable_analytics'            => '1',

		// New module toggle options (used by React UI).
		'SAMAN_SEO_module_sitemap'              => '1',
		'SAMAN_SEO_module_redirects'            => '1',
		'SAMAN_SEO_module_404_log'              => '1',
		'SAMAN_SEO_module_llm_txt'              => '1',
		'SAMAN_SEO_module_local_seo'            => '0',
		'SAMAN_SEO_module_social_cards'         => '1',
		'SAMAN_SEO_module_analytics'            => '1',
		'SAMAN_SEO_module_admin_bar'            => '1',
		'SAMAN_SEO_module_internal_links'       => '1',
		'SAMAN_SEO_module_ai_assistant'         => '1',
		'SAMAN_SEO_module_breadcrumbs'          => '0',
		'SAMAN_SEO_breadcrumb_settings'         => array(
			'enabled'          => false,
			'separator'        => '>',
			'separator_custom' => '',
			'show_home'        => true,
			'home_label'       => '',
			'show_current'     => true,
			'link_current'     => false,
			'truncate_length'  => 0,
			'show_on_front'    => false,
			'style_preset'     => 'default',
			'post_type_labels' => array(),
			'taxonomy_labels'  => array(),
		),

		'SAMAN_SEO_social_card_design'          => array(
			'background_color' => '#1a1a36',
			'accent_color'     => '#5a84ff',
			'text_color'       => '#ffffff',
			'title_font_size'  => 48,
			'site_font_size'   => 24,
			'logo_url'         => '',
			'logo_position'    => 'bottom-left',
			'layout'           => 'default',
		),
	);

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', array( $this, 'register_settings' ) );
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

		register_setting( 'saman-seo', 'SAMAN_SEO_default_title_template', array( $this, 'sanitize_template' ) );

		// Consolidated Search Appearance Settings
		$group = 'SAMAN_SEO_search_appearance';
		register_setting( $group, 'SAMAN_SEO_post_type_title_templates', array( $this, 'sanitize_post_type_templates' ) );
		register_setting( $group, 'SAMAN_SEO_post_type_meta_descriptions', array( $this, 'sanitize_post_type_descriptions' ) );
		register_setting( $group, 'SAMAN_SEO_post_type_keywords', array( $this, 'sanitize_post_type_keywords' ) );
		register_setting( $group, 'SAMAN_SEO_post_type_settings', array( $this, 'sanitize_post_type_settings' ) );
		register_setting( $group, 'SAMAN_SEO_taxonomy_settings', array( $this, 'sanitize_taxonomy_settings' ) );
		register_setting( $group, 'SAMAN_SEO_archive_settings', array( $this, 'sanitize_archive_settings' ) );
		register_setting( $group, 'SAMAN_SEO_homepage_title', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_homepage_description', 'sanitize_textarea_field' );
		register_setting( $group, 'SAMAN_SEO_homepage_keywords', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_title_separator', array( $this, 'sanitize_separator' ) );

		// New consolidated options for Search Appearance page redesign
		register_setting( $group, 'SAMAN_SEO_homepage_defaults', array( $this, 'sanitize_homepage_defaults' ) );
		register_setting( $group, 'SAMAN_SEO_post_type_defaults', array( $this, 'sanitize_post_type_defaults' ) );
		register_setting( $group, 'SAMAN_SEO_taxonomy_defaults', array( $this, 'sanitize_taxonomy_defaults' ) );
		register_setting( $group, 'SAMAN_SEO_archive_defaults', array( $this, 'sanitize_archive_defaults_new' ) );

		// Social settings (also registered under search_appearance group)
		register_setting( $group, 'SAMAN_SEO_social_defaults', array( $this, 'sanitize_social_defaults' ) );
		register_setting( $group, 'SAMAN_SEO_post_type_social_defaults', array( $this, 'sanitize_post_type_social_defaults' ) );
		register_setting( $group, 'SAMAN_SEO_social_card_design', array( $this, 'sanitize_social_card_design' ) );

		// Editor sidebar customization
		register_setting( $group, 'SAMAN_SEO_sidebar_logo', 'esc_url_raw' );

		// AI prompt customization (model selection and API keys handled by Saman Labs AI)
		register_setting( 'SAMAN_SEO_ai_tuning', 'SAMAN_SEO_ai_prompt_system', 'sanitize_textarea_field' );
		register_setting( 'SAMAN_SEO_ai_tuning', 'SAMAN_SEO_ai_prompt_title', 'sanitize_textarea_field' );
		register_setting( 'SAMAN_SEO_ai_tuning', 'SAMAN_SEO_ai_prompt_description', 'sanitize_textarea_field' );

		register_setting( 'saman-seo', 'SAMAN_SEO_homepage_description_prompt', 'sanitize_textarea_field' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_knowledge_type', array( $this, 'sanitize_knowledge_type' ) );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_organization_name', 'sanitize_text_field' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_organization_logo', 'esc_url_raw' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_person_name', 'sanitize_text_field' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_person_image', 'esc_url_raw' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_person_job_title', 'sanitize_text_field' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_person_url', 'esc_url_raw' );
		register_setting( 'SAMAN_SEO_knowledge', 'SAMAN_SEO_homepage_social_profiles', 'sanitize_textarea_field' );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_meta_description', 'sanitize_textarea_field' );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_og_image', 'esc_url_raw' );
		register_setting( 'SAMAN_SEO_social', 'SAMAN_SEO_social_defaults', array( $this, 'sanitize_social_defaults' ) );
		register_setting( 'SAMAN_SEO_social', 'SAMAN_SEO_post_type_social_defaults', array( $this, 'sanitize_post_type_social_defaults' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_social_width', 'absint' );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_social_height', 'absint' );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_noindex', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_default_nofollow', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_global_robots', 'sanitize_text_field' );
		register_setting( 'saman-seo', 'SAMAN_SEO_hreflang_map', array( $this, 'sanitize_json' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_robots_txt', 'sanitize_textarea_field' );
		// Legacy enable_* options - @deprecated Use SAMAN_SEO_module_* options instead.
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_sitemap_enhancer', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_redirect_manager', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_404_logging', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_og_preview', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_llm_txt', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_local_seo', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_enable_analytics', array( $this, 'sanitize_bool' ) );

		// New module toggle options (used by React UI).
		register_setting( 'saman-seo', 'SAMAN_SEO_module_sitemap', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_redirects', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_404_log', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_llm_txt', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_local_seo', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_social_cards', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_analytics', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_admin_bar', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_internal_links', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_ai_assistant', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_module_breadcrumbs', array( $this, 'sanitize_bool' ) );
		register_setting( 'saman-seo', 'SAMAN_SEO_breadcrumb_settings', array( $this, 'sanitize_breadcrumb_settings' ) );
	}

	/**
	 * Get variables available for different contexts.
	 *
	 * @return array
	 */
	public function get_context_variables() {
		$variables = array(
			'global'   => array(
				'label' => __( 'General', 'saman-seo' ),
				'vars'  => array(
					array(
						'tag'     => 'site_title',
						'label'   => __( 'Site Title', 'saman-seo' ),
						'desc'    => __( 'The main title of your site', 'saman-seo' ),
						'preview' => get_bloginfo( 'name' ),
					),
					array(
						'tag'     => 'tagline',
						'label'   => __( 'Tagline', 'saman-seo' ),
						'desc'    => __( 'Site description / tagline', 'saman-seo' ),
						'preview' => get_bloginfo( 'description' ),
					),
					array(
						'tag'     => 'separator',
						'label'   => __( 'Separator', 'saman-seo' ),
						'desc'    => __( 'Separator character (e.g. -)', 'saman-seo' ),
						'preview' => $this->get( 'SAMAN_SEO_title_separator' ),
					),
					array(
						'tag'     => 'current_year',
						'label'   => __( 'Current Year', 'saman-seo' ),
						'desc'    => __( 'The current year (4 digits)', 'saman-seo' ),
						'preview' => date_i18n( 'Y' ),
					),
				),
			),
			'post'     => array(
				'label' => __( 'Post Variables', 'saman-seo' ),
				'vars'  => array(
					array(
						'tag'     => 'post_title',
						'label'   => __( 'Post Title', 'saman-seo' ),
						'desc'    => __( 'Title of the current post/page', 'saman-seo' ),
						'preview' => 'Hello World',
					),
					array(
						'tag'     => 'post_excerpt',
						'label'   => __( 'Excerpt', 'saman-seo' ),
						'desc'    => __( 'Post excerpt or auto-generated snippet', 'saman-seo' ),
						'preview' => 'This is an example excerpt...',
					),
					array(
						'tag'     => 'post_date',
						'label'   => __( 'Date', 'saman-seo' ),
						'desc'    => __( 'Publish date', 'saman-seo' ),
						'preview' => date_i18n( get_option( 'date_format' ) ),
					),
					array(
						'tag'     => 'post_author',
						'label'   => __( 'Author', 'saman-seo' ),
						'desc'    => __( 'Display name of the author', 'saman-seo' ),
						'preview' => 'John Doe',
					),
					array(
						'tag'     => 'category',
						'label'   => __( 'Primary Category', 'saman-seo' ),
						'desc'    => __( 'The main category for this post', 'saman-seo' ),
						'preview' => 'Technology',
					),
					array(
						'tag'     => 'modified',
						'label'   => __( 'Modified Date', 'saman-seo' ),
						'desc'    => __( 'Last modified date', 'saman-seo' ),
						'preview' => date_i18n( get_option( 'date_format' ) ),
					),
					array(
						'tag'     => 'id',
						'label'   => __( 'ID', 'saman-seo' ),
						'desc'    => __( 'The numeric post ID', 'saman-seo' ),
						'preview' => '123',
					),
				),
			),
			'taxonomy' => array(
				'label' => __( 'Taxonomy Variables', 'saman-seo' ),
				'vars'  => array(
					array(
						'tag'     => 'term_title',
						'label'   => __( 'Term Name', 'saman-seo' ),
						'desc'    => __( 'Name of the current category/tag', 'saman-seo' ),
						'preview' => 'My Category',
					),
					array(
						'tag'     => 'term_description',
						'label'   => __( 'Term Description', 'saman-seo' ),
						'desc'    => __( 'Description of the term', 'saman-seo' ),
						'preview' => 'A list of all posts about...',
					),
				),
			),
			'archive'  => array(
				'label' => __( 'Archive Variables', 'saman-seo' ),
				'vars'  => array(
					array(
						'tag'     => 'archive_title',
						'label'   => __( 'Archive Title', 'saman-seo' ),
						'desc'    => __( 'Title based on date or type', 'saman-seo' ),
						'preview' => 'Archives for June 2025',
					),
					array(
						'tag'     => 'archive_date',
						'label'   => __( 'Archive Date', 'saman-seo' ),
						'desc'    => __( 'Date for daily/monthly archives', 'saman-seo' ),
						'preview' => 'June 2025',
					),
				),
			),
			'author'   => array(
				'label' => __( 'Author Variables', 'saman-seo' ),
				'vars'  => array(
					array(
						'tag'     => 'author_name',
						'label'   => __( 'Author Name', 'saman-seo' ),
						'desc'    => __( 'Name of the author being viewed', 'saman-seo' ),
						'preview' => 'Jane Smith',
					),
					array(
						'tag'     => 'author_bio',
						'label'   => __( 'Author Bio', 'saman-seo' ),
						'desc'    => __( 'Biographical info', 'saman-seo' ),
						'preview' => 'Jane is a writer...',
					),
				),
			),
		);

		// Discover Custom Fields per Post Type
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $post_types as $pt ) {
			$latest = get_posts(
				array(
					'post_type'      => $pt->name,
					'posts_per_page' => 1,
					'fields'         => 'ids',
					'post_status'    => 'publish',
				)
			);

			if ( ! empty( $latest ) ) {
				$post_id     = $latest[0];
				$keys        = get_post_custom_keys( $post_id );
				$custom_vars = array();

				if ( $keys ) {
					foreach ( $keys as $key ) {
						if ( is_protected_meta( $key, 'post' ) ) {
							continue;
						}
						// Get sample value
						$vals    = get_post_meta( $post_id, $key, true );
						$preview = is_string( $vals ) && strlen( $vals ) < 50 ? $vals : 'Sample Value';

						$custom_vars[] = array(
							'tag'     => 'cf_' . $key,
							'label'   => $key,
							// translators: Placeholder values
							'desc'    => sprintf( __( 'Custom Field: %s', 'saman-seo' ), $key ),
							'preview' => $preview,
						);
					}
				}

				if ( ! empty( $custom_vars ) ) {
					// Use a key like "post_type:book" so frontend can match it
					$context_key               = 'post_type:' . $pt->name;
					$variables[ $context_key ] = array(
						// translators: Placeholder values
						'label' => sprintf( __( '%s Custom Fields', 'saman-seo' ), $pt->label ),
						'vars'  => $custom_vars,
					);
				}
			}
		}

		return $variables;
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
	 * @return string
	 */
	public function sanitize_template( $value ) {
		$value = sanitize_text_field( $value );

		$contexts = $this->get_context_variables();
		$allowed  = array();

		foreach ( $contexts as $group ) {
			if ( ! empty( $group['vars'] ) && is_array( $group['vars'] ) ) {
				foreach ( $group['vars'] as $var_def ) {
					$var       = $var_def['tag'];
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
		return $this->sanitize_post_type_text_map( $value, array( $this, 'sanitize_template' ) );
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
			$value = array();
		}

		$value = wp_parse_args( $value, array() );

		$schema = sanitize_text_field( $value['schema_itemtype'] ?? '' );

		return array(
			'og_title'            => sanitize_text_field( $value['og_title'] ?? '' ),
			'og_description'      => sanitize_textarea_field( $value['og_description'] ?? '' ),
			'twitter_title'       => sanitize_text_field( $value['twitter_title'] ?? '' ),
			'twitter_description' => sanitize_textarea_field( $value['twitter_description'] ?? '' ),
			'image_source'        => esc_url_raw( $value['image_source'] ?? '' ),
			'schema_itemtype'     => $schema,
		);
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
			return array();
		}

		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		$sanitized = array();
		foreach ( $post_types as $slug => $label ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$clean = array(
				'og_title'            => sanitize_text_field( $data['og_title'] ?? '' ),
				'og_description'      => sanitize_textarea_field( $data['og_description'] ?? '' ),
				'twitter_title'       => sanitize_text_field( $data['twitter_title'] ?? '' ),
				'twitter_description' => sanitize_textarea_field( $data['twitter_description'] ?? '' ),
				'image_source'        => esc_url_raw( $data['image_source'] ?? '' ),
				'schema_itemtype'     => sanitize_text_field( $data['schema_itemtype'] ?? '' ),
			);

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
			return array();
		}

		$allowed = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			)
		);

		unset( $allowed['attachment'] );

		$sanitized = array();

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
	 * Sanitize knowledge graph representation type.
	 *
	 * @param string $value Submitted value.
	 *
	 * @return string
	 */
	public function sanitize_knowledge_type( $value ) {
		$value = sanitize_key( $value );

		return in_array( $value, array( 'organization', 'person' ), true ) ? $value : 'organization';
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
			return array();
		}

		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		$page_options    = array_keys( $this->get_schema_page_options() );
		$article_options = array_keys( $this->get_schema_article_options() );

		$sanitized = array();
		foreach ( $post_types as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$sanitized[ $slug ] = array(
				'show_search'     => ! empty( $data['show_search'] ) ? '1' : '0',
				'show_seo'        => ! empty( $data['show_seo'] ) ? '1' : '0',
				'schema_page'     => in_array( $data['schema_page'] ?? '', $page_options, true ) ? $data['schema_page'] : 'WebPage',
				'schema_article'  => in_array( $data['schema_article'] ?? '', $article_options, true ) ? $data['schema_article'] : 'Article',
				'analysis_fields' => isset( $data['analysis_fields'] ) ? sanitize_text_field( $data['analysis_fields'] ) : '',
			);
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
			return array();
		}

		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		$sanitized = array();
		foreach ( $taxonomies as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$sanitized[ $slug ] = array(
				'show_search' => ! empty( $data['show_search'] ) ? '1' : '0',
				'show_seo'    => ! empty( $data['show_seo'] ) ? '1' : '0',
				'title'       => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			);
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
			return array();
		}

		$allowed   = array( 'author', 'date', 'search' );
		$sanitized = array();

		foreach ( $allowed as $key ) {
			$data = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();

			$sanitized[ $key ] = array(
				'show'        => ! empty( $data['show'] ) ? '1' : '0',
				'title'       => isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '',
				'description' => isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '',
			);
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
			return array();
		}

		// Also update individual options for backward compatibility
		update_option( 'SAMAN_SEO_homepage_title', sanitize_text_field( $value['meta_title'] ?? '' ) );
		update_option( 'SAMAN_SEO_homepage_description', sanitize_textarea_field( $value['meta_description'] ?? '' ) );
		update_option( 'SAMAN_SEO_homepage_keywords', sanitize_text_field( $value['meta_keywords'] ?? '' ) );

		return array(
			'meta_title'       => sanitize_text_field( $value['meta_title'] ?? '' ),
			'meta_description' => sanitize_textarea_field( $value['meta_description'] ?? '' ),
			'meta_keywords'    => sanitize_text_field( $value['meta_keywords'] ?? '' ),
		);
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
			return array();
		}

		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		$sanitized = array();
		foreach ( $post_types as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$sanitized[ $slug ] = array(
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
				'schema_type'          => isset( $data['schema_type'] ) ? sanitize_text_field( $data['schema_type'] ) : '',
			);
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
			return array();
		}

		$taxonomies = get_taxonomies(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		$sanitized = array();
		foreach ( $taxonomies as $slug ) {
			$data = isset( $value[ $slug ] ) && is_array( $value[ $slug ] ) ? $value[ $slug ] : array();

			$sanitized[ $slug ] = array(
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
			);
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
			return array();
		}

		$allowed   = array( 'author', 'date', 'search', '404' );
		$sanitized = array();

		foreach ( $allowed as $key ) {
			$data = isset( $value[ $key ] ) && is_array( $value[ $key ] ) ? $value[ $key ] : array();

			$sanitized[ $key ] = array(
				'noindex'              => ! empty( $data['noindex'] ) ? '1' : '0',
				'title_template'       => isset( $data['title_template'] ) ? sanitize_text_field( $data['title_template'] ) : '',
				'description_template' => isset( $data['description_template'] ) ? sanitize_textarea_field( $data['description_template'] ) : '',
			);
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
	 * Sanitize social card design settings.
	 *
	 * @param array|string $value Value.
	 *
	 * @return array
	 */
	public function sanitize_social_card_design( $value ) {
		if ( ! is_array( $value ) ) {
			return $this->defaults['SAMAN_SEO_social_card_design'];
		}

		return array(
			'background_color' => sanitize_hex_color( $value['background_color'] ?? '#1a1a36' ),
			'accent_color'     => sanitize_hex_color( $value['accent_color'] ?? '#5a84ff' ),
			'text_color'       => sanitize_hex_color( $value['text_color'] ?? '#ffffff' ),
			'title_font_size'  => absint( $value['title_font_size'] ?? 48 ),
			'site_font_size'   => absint( $value['site_font_size'] ?? 24 ),
			'logo_url'         => esc_url_raw( $value['logo_url'] ?? '' ),
			'logo_position'    => in_array( $value['logo_position'] ?? '', array( 'top-left', 'top-right', 'bottom-left', 'bottom-right', 'center' ), true )
									? $value['logo_position']
									: 'bottom-left',
			'layout'           => in_array( $value['layout'] ?? '', array( 'default', 'centered', 'minimal', 'bold' ), true )
									? $value['layout']
									: 'default',
		);
	}

	/**
	 * Sanitize consolidated breadcrumb settings.
	 *
	 * @param array|string $value Values.
	 *
	 * @return array
	 */
	public function sanitize_breadcrumb_settings( $value ) {
		$defaults = $this->defaults['SAMAN_SEO_breadcrumb_settings'];

		if ( ! is_array( $value ) ) {
			return $defaults;
		}

		$value = wp_parse_args( $value, $defaults );

		$sanitize_labels = function ( $labels ) {
			if ( ! is_array( $labels ) ) {
				return array();
			}

			$clean = array();
			foreach ( $labels as $key => $label ) {
				$clean[ sanitize_key( $key ) ] = sanitize_text_field( $label );
			}
			return $clean;
		};

		return array(
			'enabled'          => ! empty( $value['enabled'] ),
			'separator'        => sanitize_text_field( $value['separator'] ?? $defaults['separator'] ),
			'separator_custom' => sanitize_text_field( $value['separator_custom'] ?? $defaults['separator_custom'] ),
			'show_home'        => ! empty( $value['show_home'] ),
			'home_label'       => sanitize_text_field( $value['home_label'] ?? $defaults['home_label'] ),
			'show_current'     => ! empty( $value['show_current'] ),
			'link_current'     => ! empty( $value['link_current'] ),
			'truncate_length'  => absint( $value['truncate_length'] ?? $defaults['truncate_length'] ),
			'show_on_front'    => ! empty( $value['show_on_front'] ),
			'style_preset'     => sanitize_text_field( $value['style_preset'] ?? $defaults['style_preset'] ),
			'post_type_labels' => $sanitize_labels( $value['post_type_labels'] ?? array() ),
			'taxonomy_labels'  => $sanitize_labels( $value['taxonomy_labels'] ?? array() ),
		);
	}

	/**
	 * Schema page type options.
	 *
	 * @return array<string,string>
	 */
	public function get_schema_page_options() {
		return array(
			'WebPage'           => __( 'Web Page (default)', 'saman-seo' ),
			'ItemPage'          => __( 'Item Page', 'saman-seo' ),
			'ProfilePage'       => __( 'Profile Page', 'saman-seo' ),
			'ContactPage'       => __( 'Contact Page', 'saman-seo' ),
			'SearchResultsPage' => __( 'Search Results Page', 'saman-seo' ),
		);
	}

	/**
	 * Schema article type options.
	 *
	 * @return array<string,string>
	 */
	public function get_schema_article_options() {
		return array(
			'Article'          => __( 'Article (default)', 'saman-seo' ),
			'BlogPosting'      => __( 'Blog Posting', 'saman-seo' ),
			'NewsArticle'      => __( 'News Article', 'saman-seo' ),
			'TechArticle'      => __( 'Tech Article', 'saman-seo' ),
			'ScholarlyArticle' => __( 'Scholarly Article', 'saman-seo' ),
		);
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
}
