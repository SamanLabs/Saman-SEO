<?php
/**
 * Sitemap REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for sitemap settings.
 */
class Sitemap_Controller extends REST_Controller {

    /**
     * Sitemap setting keys.
     *
     * @var array
     */
    private $sitemap_settings = [
        'wpseopilot_sitemap_enabled',
        'wpseopilot_sitemap_max_urls',
        'wpseopilot_sitemap_enable_index',
        'wpseopilot_sitemap_dynamic_generation',
        'wpseopilot_sitemap_schedule_updates',
        'wpseopilot_sitemap_post_types',
        'wpseopilot_sitemap_taxonomies',
        'wpseopilot_sitemap_include_author_pages',
        'wpseopilot_sitemap_include_date_archives',
        'wpseopilot_sitemap_exclude_images',
        'wpseopilot_sitemap_enable_rss',
        'wpseopilot_sitemap_enable_google_news',
        'wpseopilot_sitemap_google_news_name',
        'wpseopilot_sitemap_google_news_post_types',
        'wpseopilot_sitemap_additional_pages',
    ];

    /**
     * LLM.txt setting keys.
     *
     * @var array
     */
    private $llm_settings = [
        'wpseopilot_enable_llm_txt',
        'wpseopilot_llm_txt_title',
        'wpseopilot_llm_txt_description',
        'wpseopilot_llm_txt_posts_per_type',
        'wpseopilot_llm_txt_include_excerpt',
    ];

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get all sitemap settings
        register_rest_route( $this->namespace, '/sitemap/settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'update_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get available post types
        register_rest_route( $this->namespace, '/sitemap/post-types', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_types' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get available taxonomies
        register_rest_route( $this->namespace, '/sitemap/taxonomies', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_taxonomies' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Regenerate sitemap
        register_rest_route( $this->namespace, '/sitemap/regenerate', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'regenerate_sitemap' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get sitemap stats
        register_rest_route( $this->namespace, '/sitemap/stats', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_stats' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // LLM.txt settings
        register_rest_route( $this->namespace, '/sitemap/llm-settings', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_llm_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'update_llm_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all sitemap settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $settings = [];

        foreach ( $this->sitemap_settings as $key ) {
            $short_key = str_replace( 'wpseopilot_sitemap_', '', $key );
            $value = get_option( $key );

            // Handle defaults
            if ( false === $value ) {
                $value = $this->get_default_value( $key );
            }

            $settings[ $short_key ] = $value;
        }

        // Add site URL for sitemap links
        $settings['site_url'] = home_url();
        $settings['sitemap_url'] = home_url( '/sitemap_index.xml' );
        $settings['rss_sitemap_url'] = home_url( '/sitemap-rss.xml' );
        $settings['news_sitemap_url'] = home_url( '/sitemap-news.xml' );

        return $this->success( $settings );
    }

    /**
     * Update sitemap settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_settings( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        foreach ( $params as $key => $value ) {
            $option_key = 'wpseopilot_sitemap_' . $key;

            if ( in_array( $option_key, $this->sitemap_settings, true ) ) {
                // Sanitize based on type
                if ( is_array( $value ) ) {
                    $value = array_map( 'sanitize_text_field', $value );
                } elseif ( is_numeric( $value ) ) {
                    $value = absint( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }

                update_option( $option_key, $value );
            }
        }

        // Clear any sitemap caches
        delete_transient( 'wpseopilot_sitemap_stats' );

        return $this->success( null, __( 'Settings saved successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Get available post types.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_post_types( $request ) {
        $post_types = get_post_types( [
            'public' => true,
        ], 'objects' );

        $data = [];
        foreach ( $post_types as $post_type ) {
            if ( 'attachment' === $post_type->name ) {
                continue;
            }

            $count = wp_count_posts( $post_type->name );
            $published = isset( $count->publish ) ? (int) $count->publish : 0;

            $data[] = [
                'name'      => $post_type->name,
                'label'     => $post_type->label,
                'count'     => $published,
            ];
        }

        return $this->success( $data );
    }

    /**
     * Get available taxonomies.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_taxonomies( $request ) {
        $taxonomies = get_taxonomies( [
            'public' => true,
        ], 'objects' );

        $data = [];
        foreach ( $taxonomies as $taxonomy ) {
            if ( 'post_format' === $taxonomy->name ) {
                continue;
            }

            $count = wp_count_terms( $taxonomy->name );
            if ( is_wp_error( $count ) ) {
                $count = 0;
            }

            $data[] = [
                'name'  => $taxonomy->name,
                'label' => $taxonomy->label,
                'count' => (int) $count,
            ];
        }

        return $this->success( $data );
    }

    /**
     * Regenerate sitemap.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function regenerate_sitemap( $request ) {
        // Clear sitemap caches
        delete_transient( 'wpseopilot_sitemap_stats' );

        // Flush rewrite rules to ensure sitemap URLs work
        flush_rewrite_rules();

        // Update last regenerated timestamp
        update_option( 'wpseopilot_sitemap_last_regenerated', current_time( 'mysql' ) );

        return $this->success( [
            'regenerated_at' => current_time( 'mysql' ),
        ], __( 'Sitemap regenerated successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Get sitemap statistics.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_stats( $request ) {
        // Try to get cached stats
        $stats = get_transient( 'wpseopilot_sitemap_stats' );

        if ( false === $stats ) {
            $stats = $this->calculate_sitemap_stats();
            set_transient( 'wpseopilot_sitemap_stats', $stats, HOUR_IN_SECONDS );
        }

        $last_regenerated = get_option( 'wpseopilot_sitemap_last_regenerated' );

        return $this->success( [
            'total_urls'       => $stats['total_urls'],
            'post_count'       => $stats['post_count'],
            'page_count'       => $stats['page_count'],
            'taxonomy_count'   => $stats['taxonomy_count'],
            'last_regenerated' => $last_regenerated ? $last_regenerated : null,
        ] );
    }

    /**
     * Get LLM.txt settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_llm_settings( $request ) {
        $settings = [];

        foreach ( $this->llm_settings as $key ) {
            $short_key = str_replace( 'wpseopilot_', '', $key );
            $value = get_option( $key );

            // Handle defaults
            if ( false === $value ) {
                $value = $this->get_llm_default_value( $key );
            }

            $settings[ $short_key ] = $value;
        }

        // Add LLM.txt URL
        $settings['llm_txt_url'] = home_url( '/llm.txt' );

        return $this->success( $settings );
    }

    /**
     * Update LLM.txt settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function update_llm_settings( $request ) {
        $params = $request->get_json_params();

        if ( empty( $params ) ) {
            $params = $request->get_params();
        }

        foreach ( $params as $key => $value ) {
            $option_key = 'wpseopilot_' . $key;

            if ( in_array( $option_key, $this->llm_settings, true ) ) {
                // Sanitize based on type
                if ( is_numeric( $value ) ) {
                    $value = absint( $value );
                } elseif ( 'llm_txt_description' === $key ) {
                    $value = sanitize_textarea_field( $value );
                } else {
                    $value = sanitize_text_field( $value );
                }

                update_option( $option_key, $value );
            }
        }

        return $this->success( null, __( 'LLM.txt settings saved successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Calculate sitemap statistics.
     *
     * @return array
     */
    private function calculate_sitemap_stats() {
        $selected_post_types = get_option( 'wpseopilot_sitemap_post_types', [] );
        $selected_taxonomies = get_option( 'wpseopilot_sitemap_taxonomies', [] );

        if ( ! is_array( $selected_post_types ) ) {
            $selected_post_types = [];
        }

        if ( ! is_array( $selected_taxonomies ) ) {
            $selected_taxonomies = [];
        }

        $total_urls = 0;
        $post_count = 0;
        $page_count = 0;
        $taxonomy_count = 0;

        // Count posts
        foreach ( $selected_post_types as $post_type ) {
            $count = wp_count_posts( $post_type );
            $published = isset( $count->publish ) ? (int) $count->publish : 0;
            $total_urls += $published;

            if ( 'post' === $post_type ) {
                $post_count = $published;
            } elseif ( 'page' === $post_type ) {
                $page_count = $published;
            }
        }

        // Count taxonomy terms
        foreach ( $selected_taxonomies as $taxonomy ) {
            $count = wp_count_terms( $taxonomy );
            if ( ! is_wp_error( $count ) ) {
                $total_urls += (int) $count;
                $taxonomy_count += (int) $count;
            }
        }

        // Add author pages if enabled
        if ( '1' === get_option( 'wpseopilot_sitemap_include_author_pages', '0' ) ) {
            $authors = count_users();
            $total_urls += isset( $authors['total_users'] ) ? $authors['total_users'] : 0;
        }

        return [
            'total_urls'     => $total_urls,
            'post_count'     => $post_count,
            'page_count'     => $page_count,
            'taxonomy_count' => $taxonomy_count,
        ];
    }

    /**
     * Get default value for sitemap setting.
     *
     * @param string $key Setting key.
     * @return mixed
     */
    private function get_default_value( $key ) {
        $defaults = [
            'wpseopilot_sitemap_enabled'                => '1',
            'wpseopilot_sitemap_max_urls'               => 1000,
            'wpseopilot_sitemap_enable_index'           => '1',
            'wpseopilot_sitemap_dynamic_generation'     => '1',
            'wpseopilot_sitemap_schedule_updates'       => '',
            'wpseopilot_sitemap_post_types'             => [ 'post', 'page' ],
            'wpseopilot_sitemap_taxonomies'             => [ 'category' ],
            'wpseopilot_sitemap_include_author_pages'   => '0',
            'wpseopilot_sitemap_include_date_archives'  => '0',
            'wpseopilot_sitemap_exclude_images'         => '0',
            'wpseopilot_sitemap_enable_rss'             => '0',
            'wpseopilot_sitemap_enable_google_news'     => '0',
            'wpseopilot_sitemap_google_news_name'       => get_bloginfo( 'name' ),
            'wpseopilot_sitemap_google_news_post_types' => [],
            'wpseopilot_sitemap_additional_pages'       => [],
        ];

        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
    }

    /**
     * Get default value for LLM setting.
     *
     * @param string $key Setting key.
     * @return mixed
     */
    private function get_llm_default_value( $key ) {
        $defaults = [
            'wpseopilot_enable_llm_txt'           => '0',
            'wpseopilot_llm_txt_title'            => get_bloginfo( 'name' ),
            'wpseopilot_llm_txt_description'      => get_bloginfo( 'description' ),
            'wpseopilot_llm_txt_posts_per_type'   => 50,
            'wpseopilot_llm_txt_include_excerpt'  => '1',
        ];

        return isset( $defaults[ $key ] ) ? $defaults[ $key ] : '';
    }
}
