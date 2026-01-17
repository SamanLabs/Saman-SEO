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
        register_rest_route( $this->namespace, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get/Update single setting
        register_rest_route( $this->namespace, '/settings/(?P<key>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_setting' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_setting' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Content Templates
        register_rest_route( $this->namespace, '/templates', [
            [
                'methods'             => 'GET',
                'callback'            => [ $this, 'get_templates' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [ $this, 'create_template' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/templates/(?P<id>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'PUT',
                'callback'            => [ $this, 'update_template' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => 'DELETE',
                'callback'            => [ $this, 'delete_template' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all plugin settings.
     *
     * @return \WP_REST_Response
     */
    public function get_settings() {
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'SAMAN_SEO_%'"
        );

        $settings = [];
        foreach ( $options as $opt ) {
            $key = str_replace( 'SAMAN_SEO_', '', $opt->option_name );
            $settings[ $key ] = maybe_unserialize( $opt->option_value );
        }

        return $this->success( $settings );
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

        return $this->success( [
            'key'   => $key,
            'value' => $value,
        ] );
    }

    /**
     * Update multiple settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $settings = $request->get_json_params();

        foreach ( $settings as $key => $value ) {
            update_option( 'SAMAN_SEO_' . $key, $value );
        }

        // Sync breadcrumb settings to consolidated option for the service.
        $this->sync_breadcrumb_settings( $settings );

        // Sync IndexNow settings to consolidated option for the service.
        $this->sync_indexnow_settings( $settings );

        return $this->success( null, __( 'Settings saved successfully.', 'saman-seo' ) );
    }

    /**
     * Sync breadcrumb settings to the consolidated option.
     *
     * @param array $settings All settings from request.
     * @return void
     */
    private function sync_breadcrumb_settings( $settings ) {
        $breadcrumb_keys = [
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
        ];

        $breadcrumb_settings = get_option( 'SAMAN_SEO_breadcrumb_settings', [] );
        $updated             = false;

        foreach ( $breadcrumb_keys as $request_key => $service_key ) {
            if ( isset( $settings[ $request_key ] ) ) {
                $breadcrumb_settings[ $service_key ] = $settings[ $request_key ];
                $updated = true;
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
        $indexnow_keys = [
            'module_indexnow'              => 'enabled',
            'indexnow_submit_on_publish'   => 'submit_on_publish',
            'indexnow_submit_on_update'    => 'submit_on_update',
        ];

        $indexnow_settings = get_option( 'SAMAN_SEO_indexnow_settings', [] );
        $updated           = false;

        foreach ( $indexnow_keys as $request_key => $service_key ) {
            if ( isset( $settings[ $request_key ] ) ) {
                $indexnow_settings[ $service_key ] = $settings[ $request_key ];
                $updated = true;
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
     * @return \WP_REST_Response
     */
    public function update_setting( $request ) {
        $key   = $request->get_param( 'key' );
        $body  = $request->get_json_params();
        $value = isset( $body['value'] ) ? $body['value'] : null;

        update_option( 'SAMAN_SEO_' . $key, $value );

        return $this->success( [
            'key'   => $key,
            'value' => $value,
        ], __( 'Setting saved.', 'saman-seo' ) );
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
        $templates = get_option( 'SAMAN_SEO_content_templates', [] );

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

        $templates = get_option( 'SAMAN_SEO_content_templates', [] );

        $id = 'custom_' . time() . '_' . wp_rand( 1000, 9999 );

        $templates[ $id ] = [
            'id'          => $id,
            'name'        => $name,
            'title'       => $title,
            'description' => $description,
            'category'    => $category,
            'is_default'  => false,
            'created_at'  => current_time( 'mysql' ),
        ];

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

        $templates = get_option( 'SAMAN_SEO_content_templates', [] );

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

        $templates = get_option( 'SAMAN_SEO_content_templates', [] );

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
        return [
            'blog_standard' => [
                'id'          => 'blog_standard',
                'name'        => 'Blog Post - Standard',
                'title'       => '{{post_title}} | {{site_title}}',
                'description' => '{{post_excerpt}}',
                'category'    => 'blog',
                'is_default'  => true,
            ],
            'blog_keyword' => [
                'id'          => 'blog_keyword',
                'name'        => 'Blog Post - Keyword Focus',
                'title'       => '{{post_title}} - Guide {{current_year}}',
                'description' => 'Learn about {{post_title}} in this comprehensive guide. {{post_excerpt}}',
                'category'    => 'blog',
                'is_default'  => true,
            ],
            'product' => [
                'id'          => 'product',
                'name'        => 'Product Page',
                'title'       => '{{post_title}} - Buy Online | {{site_title}}',
                'description' => 'Shop {{post_title}} at {{site_title}}. {{post_excerpt}}',
                'category'    => 'ecommerce',
                'is_default'  => true,
            ],
            'service' => [
                'id'          => 'service',
                'name'        => 'Service Page',
                'title'       => '{{post_title}} Services | {{site_title}}',
                'description' => 'Professional {{post_title}} services. {{post_excerpt}}',
                'category'    => 'business',
                'is_default'  => true,
            ],
            'landing' => [
                'id'          => 'landing',
                'name'        => 'Landing Page',
                'title'       => '{{post_title}} - Get Started Today',
                'description' => '{{post_excerpt}} Start now with {{site_title}}.',
                'category'    => 'marketing',
                'is_default'  => true,
            ],
            'how_to' => [
                'id'          => 'how_to',
                'name'        => 'How-To Guide',
                'title'       => 'How to {{post_title}} (Step-by-Step Guide)',
                'description' => 'Learn how to {{post_title}} with this easy step-by-step guide. {{post_excerpt}}',
                'category'    => 'tutorial',
                'is_default'  => true,
            ],
            'listicle' => [
                'id'          => 'listicle',
                'name'        => 'Listicle / List Post',
                'title'       => '{{post_title}} ({{current_year}} Edition)',
                'description' => 'Discover {{post_title}}. {{post_excerpt}}',
                'category'    => 'blog',
                'is_default'  => true,
            ],
            'local_business' => [
                'id'          => 'local_business',
                'name'        => 'Local Business Page',
                'title'       => '{{post_title}} Near You | {{site_title}}',
                'description' => 'Find the best {{post_title}} at {{site_title}}. {{post_excerpt}}',
                'category'    => 'local',
                'is_default'  => true,
            ],
        ];
    }
}
