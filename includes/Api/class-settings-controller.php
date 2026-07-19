<?php
/**
 * Settings REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST controller for plugin settings.
 */
class Settings_Controller extends REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Get/Update all settings
		register_rest_route(
			$this->namespace,
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'update_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// Get/Update single setting
		register_rest_route(
			$this->namespace,
			'/settings/(?P<key>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_setting' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_setting' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// Content Templates
		register_rest_route(
			$this->namespace,
			'/templates',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'create_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/templates/(?P<id>[a-zA-Z0-9_-]+)',
			array(
				array(
					'methods'             => 'PUT',
					'callback'            => array( $this, 'update_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( $this, 'delete_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Get all plugin settings.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_settings() {
		global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
		$options = $wpdb->get_results(
			"SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'SAMAN_SEO_%'"
		);

		$settings = array();
		foreach ( $options as $opt ) {
			$key              = str_replace( 'SAMAN_SEO_', '', $opt->option_name );
			$settings[ $key ] = maybe_unserialize( $opt->option_value );
		}

		// Add system info.
		$settings['system_info'] = $this->get_system_info();

		// Expose theme-config overrides so the UI can render a "from theme"
		// badge per field and warn before letting the user save over them.
		$settings['theme_config'] = $this->get_theme_config_payload();

		return $this->success( $settings );
	}

	/**
	 * Build the payload describing the active theme's saman-seo.config.php
	 * (if any). Keys are stripped of the SAMAN_SEO_ prefix to match the rest
	 * of the settings payload the React app already consumes.
	 *
	 * @return array
	 */
	private function get_theme_config_payload() {
		$service = \Saman\SEO\Plugin::instance()->get( 'theme_config' );

		if ( ! $service ) {
			return array(
				'loaded'    => false,
				'path'      => '',
				'overrides' => (object) array(),
			);
		}

		$raw   = $service->get_overrides();
		$short = array();
		foreach ( $raw as $key => $value ) {
			$short_key           = ( 0 === strpos( $key, 'SAMAN_SEO_' ) ) ? substr( $key, 10 ) : $key;
			$short[ $short_key ] = $value;
		}

		$path = $service->get_loaded_path();

		return array(
			'loaded'    => '' !== $path,
			'path'      => $path,
			'overrides' => empty( $short ) ? (object) array() : $short,
		);
	}

	/**
	 * Get system information.
	 *
	 * @return array
	 */
	private function get_system_info() {
		global $wp_version;

		return array(
			'plugin_version'  => defined( 'SAMAN_SEO_VERSION' ) ? SAMAN_SEO_VERSION : 'Unknown',
			'wordpress'       => $wp_version,
			'php'             => phpversion(),
			'mysql'           => $this->get_mysql_version(),
			'server'          => isset( $_SERVER['SERVER_SOFTWARE'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SERVER_SOFTWARE'] ) ) : 'Unknown',
			'memory_limit'    => ini_get( 'memory_limit' ),
			'max_upload_size' => size_format( wp_max_upload_size() ),
			'timezone'        => wp_timezone_string(),
			'is_multisite'    => is_multisite(),
			'debug_mode'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
			'theme'           => wp_get_theme()->get( 'Name' ),
			'theme_version'   => wp_get_theme()->get( 'Version' ),
		);
	}

	/**
	 * Get MySQL version.
	 *
	 * @return string
	 */
	private function get_mysql_version() {
		global $wpdb;
		$version = $wpdb->get_var( 'SELECT VERSION()' ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $version ? $version : 'Unknown';
	}

	/**
	 * Get a single setting.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_setting( $request ) {
		$key   = $request->get_param( 'key' );
		$value = get_option( 'SAMAN_SEO_' . $key );

		return $this->success(
			array(
				'key'   => $key,
				'value' => $value,
			)
		);
	}

	/**
	 * Update multiple settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_settings( $request ) {
		$settings = $request->get_json_params();

		if ( ! is_array( $settings ) ) {
			return $this->error( __( 'Invalid settings payload.', 'saman-seo' ), 'invalid_payload', 400 );
		}

		$allowed   = $this->get_allowed_settings();
		$sanitized = array();
		$rejected  = array();

		foreach ( $settings as $key => $value ) {
			if ( ! isset( $allowed[ $key ] ) ) {
				$rejected[] = $key;
				continue;
			}

			$sanitized[ $key ] = $this->sanitize_setting_value( $key, $value, $allowed[ $key ] );
			update_option( 'SAMAN_SEO_' . $key, $sanitized[ $key ] );
		}

		// Sync breadcrumb settings to consolidated option for the service.
		$this->sync_breadcrumb_settings( $sanitized );

		// Sync IndexNow settings to consolidated option for the service.
		$this->sync_indexnow_settings( $sanitized );

		$response = array( 'saved' => array_keys( $sanitized ) );
		if ( ! empty( $rejected ) ) {
			$response['rejected'] = $rejected;
		}

		return $this->success( $response, __( 'Settings saved successfully.', 'saman-seo' ) );
	}

	/**
	 * Allowed setting keys and their expected data types.
	 *
	 * The list is intentionally explicit. Use the
	 * `saman_seo_allowed_settings` filter to extend it for custom modules.
	 *
	 * @return array<string,string>
	 */
	private function get_allowed_settings() {
		$settings = array(
			// Core title / meta.
			'title_separator'                  => 'text',
			'default_title_template'           => 'text',
			'default_meta_description'         => 'textarea',
			'default_og_image'                 => 'url',
			'homepage_title'                   => 'text',
			'homepage_description'             => 'textarea',
			'homepage_keywords'                => 'textarea',
			'homepage_social_profiles'         => 'textarea',
			'local_social_profiles'            => 'textarea',

			// Per-post-type options.
			'post_type_title_templates'        => 'array',
			'post_type_meta_descriptions'      => 'array',
			'post_type_keywords'               => 'array',
			'post_type_social_defaults'        => 'array',

			// Archives.
			'archive_defaults'                 => 'array',

			// Breadcrumbs.
			'breadcrumb_separator'             => 'text',
			'breadcrumb_separator_custom'      => 'text',
			'breadcrumb_show_home'             => 'bool',
			'breadcrumb_home_label'            => 'text',
			'breadcrumb_show_current'          => 'bool',
			'breadcrumb_link_current'          => 'bool',
			'breadcrumb_truncate_length'       => 'int',
			'breadcrumb_show_on_front'         => 'bool',
			'breadcrumb_style_preset'          => 'text',

			// Modules.
			'module_breadcrumbs'               => 'bool',
			'module_sitemap'                   => 'bool',
			'module_redirects'                 => 'bool',
			'module_404_log'                   => 'bool',
			'module_indexnow'                  => 'bool',
			'module_local_seo'                 => 'bool',
			'module_social_cards'              => 'bool',
			'module_analytics'                 => 'bool',
			'module_admin_bar'                 => 'bool',
			'module_internal_links'            => 'bool',
			'module_ai_assistant'              => 'bool',
			'module_llm_txt'                   => 'bool',
			'module_agents_md'                 => 'bool',

			// IndexNow.
			'indexnow_submit_on_publish'       => 'bool',
			'indexnow_submit_on_update'        => 'bool',

			// Misc.
			'llm_txt'                          => 'textarea',
			'hreflang_map'                     => 'textarea',
			'sidebar_logo'                     => 'url',

			// Redirect settings.
			'redirect_case_insensitive'        => 'bool',
			'redirect_ignore_trailing_slashes' => 'bool',
			'redirect_query_matching'          => 'text',
			'redirect_cache_header_hours'      => 'int',
			'redirect_object_cache'            => 'bool',
			'redirect_auto_generate_url'       => 'text',
			'redirect_monitor_post_types'      => 'array',
			'redirect_monitor_trash'           => 'bool',

			// 404 log privacy/logging settings.
			'404_log_ip_level'                 => 'text',
			'404_log_ip_header'                => 'text',
			'404_log_referer'                  => 'bool',
			'404_log_ignore_bots'              => 'bool',

			// Data cleanup.
			'delete_data_on_uninstall'         => 'bool',
		);

		return saman_seo_apply_filters( 'saman_seo_allowed_settings', $settings );
	}

	/**
	 * Sanitize a setting value based on its registered type.
	 *
	 * @param string $key     Setting key (without SAMAN_SEO_ prefix).
	 * @param mixed  $value   Raw value.
	 * @param string $type    Registered type.
	 * @return mixed
	 */
	private function sanitize_setting_value( $key, $value, $type ) {
		switch ( $type ) {
			case 'bool':
				return rest_sanitize_boolean( $value );

			case 'int':
				return (int) $value;

			case 'url':
				return '' === $value ? '' : esc_url_raw( $value );

			case 'textarea':
				return sanitize_textarea_field( $value );

			case 'array':
				if ( ! is_array( $value ) ) {
					return array();
				}

				if ( 'redirect_monitor_post_types' === $key ) {
					$public = get_post_types( array( 'public' => true ), 'names' );
					$clean  = array();
					foreach ( $value as $slug ) {
						$slug = sanitize_key( $slug );
						if ( in_array( $slug, $public, true ) ) {
							$clean[] = $slug;
						}
					}
					return $clean;
				}

				return $value;

			case 'text':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Sync breadcrumb settings to the consolidated option.
	 *
	 * @param array $settings All settings from request.
	 * @return void
	 */
	private function sync_breadcrumb_settings( $settings ) {
		$breadcrumb_keys = array(
			'breadcrumb_separator'        => 'separator',
			'breadcrumb_separator_custom' => 'separator_custom',
			'breadcrumb_show_home'        => 'show_home',
			'breadcrumb_home_label'       => 'home_label',
			'breadcrumb_show_current'     => 'show_current',
			'breadcrumb_link_current'     => 'link_current',
			'breadcrumb_truncate_length'  => 'truncate_length',
			'breadcrumb_show_on_front'    => 'show_on_front',
			'breadcrumb_style_preset'     => 'style_preset',
			'module_breadcrumbs'          => 'enabled',
		);

		$breadcrumb_settings = get_option( 'SAMAN_SEO_breadcrumb_settings', array() );
		$updated             = false;

		foreach ( $breadcrumb_keys as $request_key => $service_key ) {
			if ( isset( $settings[ $request_key ] ) ) {
				$breadcrumb_settings[ $service_key ] = $settings[ $request_key ];
				$updated                             = true;
			}
		}

		if ( $updated ) {
			update_option( 'SAMAN_SEO_breadcrumb_settings', $breadcrumb_settings );
		}
	}

	/**
	 * Sync IndexNow settings to the consolidated option.
	 *
	 * @param array $settings All settings from request.
	 * @return void
	 */
	private function sync_indexnow_settings( $settings ) {
		$indexnow_keys = array(
			'module_indexnow'            => 'enabled',
			'indexnow_submit_on_publish' => 'submit_on_publish',
			'indexnow_submit_on_update'  => 'submit_on_update',
		);

		$indexnow_settings = get_option( 'SAMAN_SEO_indexnow_settings', array() );
		$updated           = false;

		foreach ( $indexnow_keys as $request_key => $service_key ) {
			if ( isset( $settings[ $request_key ] ) ) {
				$indexnow_settings[ $service_key ] = $settings[ $request_key ];
				$updated                           = true;
			}
		}

		// Generate API key when enabling IndexNow for the first time.
		if ( $updated && ! empty( $indexnow_settings['enabled'] ) && empty( $indexnow_settings['api_key'] ) ) {
			$indexnow_service = \Saman\SEO\Plugin::instance()->get( 'indexnow' );
			if ( $indexnow_service ) {
				$indexnow_settings['api_key'] = $indexnow_service->generate_api_key();
				flush_rewrite_rules();
			}
		}

		if ( $updated ) {
			update_option( 'SAMAN_SEO_indexnow_settings', $indexnow_settings );
		}
	}

	/**
	 * Update a single setting.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_setting( $request ) {
		$key   = $request->get_param( 'key' );
		$body  = $request->get_json_params();
		$value = isset( $body['value'] ) ? $body['value'] : null;

		$allowed = $this->get_allowed_settings();

		if ( ! isset( $allowed[ $key ] ) ) {
			return $this->error(
				__( 'This setting is not allowed.', 'saman-seo' ),
				'setting_not_allowed',
				403
			);
		}

		$value = $this->sanitize_setting_value( $key, $value, $allowed[ $key ] );

		update_option( 'SAMAN_SEO_' . $key, $value );

		return $this->success(
			array(
				'key'   => $key,
				'value' => $value,
			),
			__( 'Setting saved.', 'saman-seo' )
		);
	}

	// =========================================================================
	// CONTENT TEMPLATES
	// =========================================================================

	/**
	 * Get all content templates.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_templates() {
		$templates = get_option( 'SAMAN_SEO_content_templates', array() );

		// Add default templates if none exist.
		if ( empty( $templates ) ) {
			$templates = $this->get_default_templates();
			update_option( 'SAMAN_SEO_content_templates', $templates );
		}

		return $this->success( $templates );
	}

	/**
	 * Create a new content template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function create_template( $request ) {
		$params = $request->get_json_params();

		$name        = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$title       = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$description = isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '';
		$category    = isset( $params['category'] ) ? sanitize_text_field( $params['category'] ) : 'custom';

		if ( empty( $name ) ) {
			return $this->error( __( 'Template name is required.', 'saman-seo' ), 'missing_name', 400 );
		}

		$templates = get_option( 'SAMAN_SEO_content_templates', array() );

		$id = 'custom_' . time() . '_' . wp_rand( 1000, 9999 );

		$templates[ $id ] = array(
			'id'          => $id,
			'name'        => $name,
			'title'       => $title,
			'description' => $description,
			'category'    => $category,
			'is_default'  => false,
			'created_at'  => current_time( 'mysql' ),
		);

		update_option( 'SAMAN_SEO_content_templates', $templates );

		return $this->success( $templates[ $id ], __( 'Template created.', 'saman-seo' ) );
	}

	/**
	 * Update a content template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_template( $request ) {
		$id     = $request->get_param( 'id' );
		$params = $request->get_json_params();

		$templates = get_option( 'SAMAN_SEO_content_templates', array() );

		if ( ! isset( $templates[ $id ] ) ) {
			return $this->error( __( 'Template not found.', 'saman-seo' ), 'not_found', 404 );
		}

		// Don't allow editing default templates.
		if ( ! empty( $templates[ $id ]['is_default'] ) ) {
			return $this->error( __( 'Cannot edit default templates.', 'saman-seo' ), 'cannot_edit', 403 );
		}

		if ( isset( $params['name'] ) ) {
			$templates[ $id ]['name'] = sanitize_text_field( $params['name'] );
		}
		if ( isset( $params['title'] ) ) {
			$templates[ $id ]['title'] = sanitize_text_field( $params['title'] );
		}
		if ( isset( $params['description'] ) ) {
			$templates[ $id ]['description'] = sanitize_textarea_field( $params['description'] );
		}
		if ( isset( $params['category'] ) ) {
			$templates[ $id ]['category'] = sanitize_text_field( $params['category'] );
		}

		$templates[ $id ]['updated_at'] = current_time( 'mysql' );

		update_option( 'SAMAN_SEO_content_templates', $templates );

		return $this->success( $templates[ $id ], __( 'Template updated.', 'saman-seo' ) );
	}

	/**
	 * Delete a content template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function delete_template( $request ) {
		$id = $request->get_param( 'id' );

		$templates = get_option( 'SAMAN_SEO_content_templates', array() );

		if ( ! isset( $templates[ $id ] ) ) {
			return $this->error( __( 'Template not found.', 'saman-seo' ), 'not_found', 404 );
		}

		// Don't allow deleting default templates.
		if ( ! empty( $templates[ $id ]['is_default'] ) ) {
			return $this->error( __( 'Cannot delete default templates.', 'saman-seo' ), 'cannot_delete', 403 );
		}

		unset( $templates[ $id ] );
		update_option( 'SAMAN_SEO_content_templates', $templates );

		return $this->success( null, __( 'Template deleted.', 'saman-seo' ) );
	}

	/**
	 * Get default content templates.
	 *
	 * @return array
	 */
	private function get_default_templates() {
		return array(
			'blog_standard'  => array(
				'id'          => 'blog_standard',
				'name'        => 'Blog Post - Standard',
				'title'       => '{{post_title}} | {{site_title}}',
				'description' => '{{post_excerpt}}',
				'category'    => 'blog',
				'is_default'  => true,
			),
			'blog_keyword'   => array(
				'id'          => 'blog_keyword',
				'name'        => 'Blog Post - Keyword Focus',
				'title'       => '{{post_title}} - Guide {{current_year}}',
				'description' => 'Learn about {{post_title}} in this comprehensive guide. {{post_excerpt}}',
				'category'    => 'blog',
				'is_default'  => true,
			),
			'product'        => array(
				'id'          => 'product',
				'name'        => 'Product Page',
				'title'       => '{{post_title}} - Buy Online | {{site_title}}',
				'description' => 'Shop {{post_title}} at {{site_title}}. {{post_excerpt}}',
				'category'    => 'ecommerce',
				'is_default'  => true,
			),
			'service'        => array(
				'id'          => 'service',
				'name'        => 'Service Page',
				'title'       => '{{post_title}} Services | {{site_title}}',
				'description' => 'Professional {{post_title}} services. {{post_excerpt}}',
				'category'    => 'business',
				'is_default'  => true,
			),
			'landing'        => array(
				'id'          => 'landing',
				'name'        => 'Landing Page',
				'title'       => '{{post_title}} - Get Started Today',
				'description' => '{{post_excerpt}} Start now with {{site_title}}.',
				'category'    => 'marketing',
				'is_default'  => true,
			),
			'how_to'         => array(
				'id'          => 'how_to',
				'name'        => 'How-To Guide',
				'title'       => 'How to {{post_title}} (Step-by-Step Guide)',
				'description' => 'Learn how to {{post_title}} with this easy step-by-step guide. {{post_excerpt}}',
				'category'    => 'tutorial',
				'is_default'  => true,
			),
			'listicle'       => array(
				'id'          => 'listicle',
				'name'        => 'Listicle / List Post',
				'title'       => '{{post_title}} ({{current_year}} Edition)',
				'description' => 'Discover {{post_title}}. {{post_excerpt}}',
				'category'    => 'blog',
				'is_default'  => true,
			),
			'local_business' => array(
				'id'          => 'local_business',
				'name'        => 'Local Business Page',
				'title'       => '{{post_title}} Near You | {{site_title}}',
				'description' => 'Find the best {{post_title}} at {{site_title}}. {{post_excerpt}}',
				'category'    => 'local',
				'is_default'  => true,
			),
		);
	}
}
