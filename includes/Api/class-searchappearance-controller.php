<?php
/**
 * Search Appearance REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for Search Appearance settings.
 */
class SearchAppearance_Controller extends REST_Controller {

    /**
     * Title separator options.
     *
     * @var array
     */
    private $separator_options = [
        '-'  => 'Dash (-)',
        '|'  => 'Pipe (|)',
        '~'  => 'Tilde (~)',
        'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢'  => 'Bullet (ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢)',
        'Ãƒâ€šÃ‚Â»'  => 'Double Arrow (Ãƒâ€šÃ‚Â»)',
        '>'  => 'Greater Than (>)',
        'ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¹'  => 'Single Guillemet (ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¹)',
        'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â'  => 'Em Dash (ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â)',
        'Ãƒâ€šÃ‚Â·'  => 'Middle Dot (Ãƒâ€šÃ‚Â·)',
        '/'  => 'Slash (/)',
    ];

    /**
     * Schema page type options.
     *
     * @var array
     */
    private $schema_page_options = [
        'WebPage'           => 'Web Page (default)',
        'ItemPage'          => 'Item Page',
        'ProfilePage'       => 'Profile Page',
        'ContactPage'       => 'Contact Page',
        'SearchResultsPage' => 'Search Results Page',
		'Book'              => 'Book',
		'Course'            => 'Course',
		'Movie'             => 'Movie',
		'MusicAlbum'        => 'Music Album',
		'MusicPlaylist'     => 'Music Playlist',
		'Restaurant'        => 'Restaurant',
		'SoftwareApplication' => 'Software Application',
		'Service'           => 'Service',
		'JobPosting'        => 'Job Posting',
    ];

    /**
     * Schema article type options.
     *
     * @var array
     */
    private $schema_article_options = [
        'Article'          => 'Article (default)',
        'BlogPosting'      => 'Blog Posting',
        'NewsArticle'      => 'News Article',
        'TechArticle'      => 'Tech Article',
        'ScholarlyArticle' => 'Scholarly Article',
    ];

	/**
	 * Schema medical type options.
	 *
	 * @var array
	 */
	private $schema_medical_options = [
		'MedicalCondition' => 'Medical Condition',
		'Drug'             => 'Drug',
		'MedicalProcedure' => 'Medical Procedure',
	];

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get all search appearance settings
        register_rest_route( $this->namespace, '/search-appearance', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_settings' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Homepage defaults
        register_rest_route( $this->namespace, '/search-appearance/homepage', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_homepage_defaults' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_homepage_defaults' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Title separator
        register_rest_route( $this->namespace, '/search-appearance/separator', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_separator' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_separator' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'separator' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );

        // Post types
        register_rest_route( $this->namespace, '/search-appearance/post-types', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_post_types' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_post_types' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Single post type
        register_rest_route( $this->namespace, '/search-appearance/post-types/(?P<slug>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_single_post_type' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Taxonomies
        register_rest_route( $this->namespace, '/search-appearance/taxonomies', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_taxonomies' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_taxonomies' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Single taxonomy
        register_rest_route( $this->namespace, '/search-appearance/taxonomies/(?P<slug>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_single_taxonomy' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Archives
        register_rest_route( $this->namespace, '/search-appearance/archives', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_archives' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'save_archives' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Variables reference
        register_rest_route( $this->namespace, '/search-appearance/variables', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_variables' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get all search appearance settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_settings( $request ) {
        $homepage  = $this->get_homepage_defaults_data();
        $separator = get_option( 'SAMAN_SEO_title_separator', '-' );
        $post_types = $this->get_post_types_data();
        $taxonomies = $this->get_taxonomies_data();
        $archives   = $this->get_archives_data();

        return $this->success( [
            'homepage'          => $homepage,
            'separator'         => $separator,
            'separator_options' => $this->separator_options,
            'post_types'        => $post_types,
            'taxonomies'        => $taxonomies,
            'archives'          => $archives,
            'schema_options'    => [
                'page'    => $this->schema_page_options,
                'article' => $this->schema_article_options,
				'medical' => $this->schema_medical_options,
            ],
            'site_info'         => [
                'name'        => get_bloginfo( 'name' ),
                'description' => get_bloginfo( 'description' ),
                'url'         => home_url(),
                'domain'      => wp_parse_url( home_url(), PHP_URL_HOST ),
                'favicon'     => get_site_icon_url( 32 ),
            ],
            'variables'         => $this->get_variables_data(),
            'variable_values'   => $this->get_variable_values_map(),
        ] );
    }

    /**
     * Get homepage defaults.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_homepage_defaults( $request ) {
        return $this->success( $this->get_homepage_defaults_data() );
    }

    /**
     * Get homepage defaults data.
     *
     * @return array
     */
    private function get_homepage_defaults_data() {
        return [
            'meta_title'       => get_option( 'SAMAN_SEO_homepage_title', '' ),
            'meta_description' => get_option( 'SAMAN_SEO_homepage_description', '' ),
            'meta_keywords'    => get_option( 'SAMAN_SEO_homepage_keywords', '' ),
        ];
    }

    /**
     * Save homepage defaults.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_homepage_defaults( $request ) {
        $params = $request->get_json_params();

        $meta_title       = isset( $params['meta_title'] ) ? sanitize_text_field( $params['meta_title'] ) : '';
        $meta_description = isset( $params['meta_description'] ) ? sanitize_textarea_field( $params['meta_description'] ) : '';
        $meta_keywords    = isset( $params['meta_keywords'] ) ? sanitize_text_field( $params['meta_keywords'] ) : '';

        update_option( 'SAMAN_SEO_homepage_title', $meta_title );
        update_option( 'SAMAN_SEO_homepage_description', $meta_description );
        update_option( 'SAMAN_SEO_homepage_keywords', $meta_keywords );

        // Also update the consolidated option
        update_option( 'SAMAN_SEO_homepage_defaults', [
            'meta_title'       => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords'    => $meta_keywords,
        ] );

        return $this->success( [
            'meta_title'       => $meta_title,
            'meta_description' => $meta_description,
            'meta_keywords'    => $meta_keywords,
        ], __( 'Homepage settings saved.', 'saman-seo' ) );
    }

    /**
     * Get title separator.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_separator( $request ) {
        return $this->success( [
            'separator' => get_option( 'SAMAN_SEO_title_separator', '-' ),
            'options'   => $this->separator_options,
        ] );
    }

    /**
     * Save title separator.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_separator( $request ) {
        $separator = $request->get_param( 'separator' );
        $separator = trim( $separator );

        if ( empty( $separator ) ) {
            $separator = '-';
        }

        // Limit to 3 characters
        $separator = mb_substr( $separator, 0, 3 );

        update_option( 'SAMAN_SEO_title_separator', $separator );

        return $this->success( [
            'separator' => $separator,
        ], __( 'Title separator saved.', 'saman-seo' ) );
    }

    /**
     * Get post types with settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_post_types( $request ) {
        return $this->success( $this->get_post_types_data() );
    }

    /**
     * Get post types data.
     *
     * @return array
     */
    private function get_post_types_data() {
        $post_types = get_post_types(
            [
                'public'  => true,
                'show_ui' => true,
            ],
            'objects'
        );

        // Remove attachment
        unset( $post_types['attachment'] );

        $defaults = get_option( 'SAMAN_SEO_post_type_defaults', [] );
        $settings = get_option( 'SAMAN_SEO_post_type_settings', [] );
        $title_templates = get_option( 'SAMAN_SEO_post_type_title_templates', [] );
        $meta_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );

        $data = [];
        foreach ( $post_types as $slug => $post_type ) {
            $pt_defaults = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : [];
            $pt_settings = isset( $settings[ $slug ] ) ? $settings[ $slug ] : [];

            // Merge old and new structure
            $title_template = ! empty( $pt_defaults['title_template'] )
                ? $pt_defaults['title_template']
                : ( isset( $title_templates[ $slug ] ) ? $title_templates[ $slug ] : '{{post_title}} {{separator}} {{site_title}}' );

            $description_template = ! empty( $pt_defaults['description_template'] )
                ? $pt_defaults['description_template']
                : ( isset( $meta_descriptions[ $slug ] ) ? $meta_descriptions[ $slug ] : '' );

            $data[] = [
                'slug'                 => $slug,
                'name'                 => $post_type->label,
                'singular_name'        => $post_type->labels->singular_name,
                'count'                => (int) wp_count_posts( $slug )->publish,
                'noindex'              => ! empty( $pt_defaults['noindex'] ) || empty( $pt_settings['show_search'] ),
                'show_seo_controls'    => ! isset( $pt_settings['show_seo'] ) || ! empty( $pt_settings['show_seo'] ),
                'title_template'       => $title_template,
                'description_template' => $description_template,
                'schema_page'          => isset( $pt_settings['schema_page'] ) ? $pt_settings['schema_page'] : 'WebPage',
                'schema_article'       => isset( $pt_settings['schema_article'] ) ? $pt_settings['schema_article'] : 'Article',
                'analysis_fields'      => isset( $pt_settings['analysis_fields'] ) ? $pt_settings['analysis_fields'] : '',
            ];
        }

        return $data;
    }

    /**
     * Save post types settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_post_types( $request ) {
        $params = $request->get_json_params();

        if ( ! is_array( $params ) ) {
            return $this->error( __( 'Invalid data.', 'saman-seo' ), 'invalid_data', 400 );
        }

        $defaults = [];
        $settings = [];

        foreach ( $params as $pt_data ) {
            if ( empty( $pt_data['slug'] ) ) {
                continue;
            }

            $slug = sanitize_key( $pt_data['slug'] );

            $defaults[ $slug ] = [
                'noindex'              => ! empty( $pt_data['noindex'] ) ? '1' : '0',
                'title_template'       => isset( $pt_data['title_template'] ) ? sanitize_text_field( $pt_data['title_template'] ) : '',
                'description_template' => isset( $pt_data['description_template'] ) ? sanitize_textarea_field( $pt_data['description_template'] ) : '',
            ];

            $settings[ $slug ] = [
                'show_search'     => empty( $pt_data['noindex'] ) ? '1' : '0',
                'show_seo'        => ! empty( $pt_data['show_seo_controls'] ) ? '1' : '0',
                'schema_page'     => isset( $pt_data['schema_page'] ) ? sanitize_text_field( $pt_data['schema_page'] ) : 'WebPage',
                'schema_article'  => isset( $pt_data['schema_article'] ) ? sanitize_text_field( $pt_data['schema_article'] ) : 'Article',
                'analysis_fields' => isset( $pt_data['analysis_fields'] ) ? sanitize_text_field( $pt_data['analysis_fields'] ) : '',
            ];
        }

        update_option( 'SAMAN_SEO_post_type_defaults', $defaults );
        update_option( 'SAMAN_SEO_post_type_settings', $settings );

        return $this->success( $this->get_post_types_data(), __( 'Post type settings saved.', 'saman-seo' ) );
    }

    /**
     * Save single post type settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_single_post_type( $request ) {
        $slug   = sanitize_key( $request->get_param( 'slug' ) );
        $params = $request->get_json_params();

        $defaults = get_option( 'SAMAN_SEO_post_type_defaults', [] );
        $settings = get_option( 'SAMAN_SEO_post_type_settings', [] );

        $defaults[ $slug ] = [
            'noindex'              => ! empty( $params['noindex'] ) ? '1' : '0',
            'title_template'       => isset( $params['title_template'] ) ? sanitize_text_field( $params['title_template'] ) : '',
            'description_template' => isset( $params['description_template'] ) ? sanitize_textarea_field( $params['description_template'] ) : '',
        ];

        $settings[ $slug ] = [
            'show_search'     => empty( $params['noindex'] ) ? '1' : '0',
            'show_seo'        => ! empty( $params['show_seo_controls'] ) ? '1' : '0',
            'schema_page'     => isset( $params['schema_page'] ) ? sanitize_text_field( $params['schema_page'] ) : 'WebPage',
            'schema_article'  => isset( $params['schema_article'] ) ? sanitize_text_field( $params['schema_article'] ) : 'Article',
            'analysis_fields' => isset( $params['analysis_fields'] ) ? sanitize_text_field( $params['analysis_fields'] ) : '',
        ];

        update_option( 'SAMAN_SEO_post_type_defaults', $defaults );
        update_option( 'SAMAN_SEO_post_type_settings', $settings );

        return $this->success( null, __( 'Post type settings saved.', 'saman-seo' ) );
    }

    /**
     * Get taxonomies with settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_taxonomies( $request ) {
        return $this->success( $this->get_taxonomies_data() );
    }

    /**
     * Get taxonomies data.
     *
     * @return array
     */
    private function get_taxonomies_data() {
        $taxonomies = get_taxonomies(
            [
                'public'  => true,
                'show_ui' => true,
            ],
            'objects'
        );

        $defaults = get_option( 'SAMAN_SEO_taxonomy_defaults', [] );
        $settings = get_option( 'SAMAN_SEO_taxonomy_settings', [] );

        $data = [];
        foreach ( $taxonomies as $slug => $taxonomy ) {
            $tax_defaults = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : [];
            $tax_settings = isset( $settings[ $slug ] ) ? $settings[ $slug ] : [];

            // Merge old and new structure
            $title_template = ! empty( $tax_defaults['title_template'] )
                ? $tax_defaults['title_template']
                : ( isset( $tax_settings['title'] ) ? $tax_settings['title'] : '{{term_title}} Archives {{separator}} {{site_title}}' );

            $description_template = ! empty( $tax_defaults['description_template'] )
                ? $tax_defaults['description_template']
                : ( isset( $tax_settings['description'] ) ? $tax_settings['description'] : '' );

            $data[] = [
                'slug'                 => $slug,
                'name'                 => $taxonomy->label,
                'singular_name'        => $taxonomy->labels->singular_name,
                'count'                => (int) wp_count_terms( [ 'taxonomy' => $slug, 'hide_empty' => false ] ),
                'noindex'              => ! empty( $tax_defaults['noindex'] ) || empty( $tax_settings['show_search'] ),
                'show_seo_controls'    => ! isset( $tax_settings['show_seo'] ) || ! empty( $tax_settings['show_seo'] ),
                'title_template'       => $title_template,
                'description_template' => $description_template,
            ];
        }

        return $data;
    }

    /**
     * Save taxonomies settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_taxonomies( $request ) {
        $params = $request->get_json_params();

        if ( ! is_array( $params ) ) {
            return $this->error( __( 'Invalid data.', 'saman-seo' ), 'invalid_data', 400 );
        }

        $defaults = [];
        $settings = [];

        foreach ( $params as $tax_data ) {
            if ( empty( $tax_data['slug'] ) ) {
                continue;
            }

            $slug = sanitize_key( $tax_data['slug'] );

            $defaults[ $slug ] = [
                'noindex'              => ! empty( $tax_data['noindex'] ) ? '1' : '0',
                'title_template'       => isset( $tax_data['title_template'] ) ? sanitize_text_field( $tax_data['title_template'] ) : '',
                'description_template' => isset( $tax_data['description_template'] ) ? sanitize_textarea_field( $tax_data['description_template'] ) : '',
            ];

            $settings[ $slug ] = [
                'show_search' => empty( $tax_data['noindex'] ) ? '1' : '0',
                'show_seo'    => ! empty( $tax_data['show_seo_controls'] ) ? '1' : '0',
                'title'       => isset( $tax_data['title_template'] ) ? sanitize_text_field( $tax_data['title_template'] ) : '',
                'description' => isset( $tax_data['description_template'] ) ? sanitize_textarea_field( $tax_data['description_template'] ) : '',
            ];
        }

        update_option( 'SAMAN_SEO_taxonomy_defaults', $defaults );
        update_option( 'SAMAN_SEO_taxonomy_settings', $settings );

        return $this->success( $this->get_taxonomies_data(), __( 'Taxonomy settings saved.', 'saman-seo' ) );
    }

    /**
     * Save single taxonomy settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_single_taxonomy( $request ) {
        $slug   = sanitize_key( $request->get_param( 'slug' ) );
        $params = $request->get_json_params();

        $defaults = get_option( 'SAMAN_SEO_taxonomy_defaults', [] );
        $settings = get_option( 'SAMAN_SEO_taxonomy_settings', [] );

        $defaults[ $slug ] = [
            'noindex'              => ! empty( $params['noindex'] ) ? '1' : '0',
            'title_template'       => isset( $params['title_template'] ) ? sanitize_text_field( $params['title_template'] ) : '',
            'description_template' => isset( $params['description_template'] ) ? sanitize_textarea_field( $params['description_template'] ) : '',
        ];

        $settings[ $slug ] = [
            'show_search' => empty( $params['noindex'] ) ? '1' : '0',
            'show_seo'    => ! empty( $params['show_seo_controls'] ) ? '1' : '0',
            'title'       => isset( $params['title_template'] ) ? sanitize_text_field( $params['title_template'] ) : '',
            'description' => isset( $params['description_template'] ) ? sanitize_textarea_field( $params['description_template'] ) : '',
        ];

        update_option( 'SAMAN_SEO_taxonomy_defaults', $defaults );
        update_option( 'SAMAN_SEO_taxonomy_settings', $settings );

        return $this->success( null, __( 'Taxonomy settings saved.', 'saman-seo' ) );
    }

    /**
     * Get archives settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_archives( $request ) {
        return $this->success( $this->get_archives_data() );
    }

    /**
     * Get archives data.
     *
     * @return array
     */
    private function get_archives_data() {
        $defaults = get_option( 'SAMAN_SEO_archive_defaults', [] );
        $settings = get_option( 'SAMAN_SEO_archive_settings', [] );

        $archive_types = [
            'author' => [
                'name'                        => __( 'Author Archives', 'saman-seo' ),
                'description'                 => __( 'Archive pages showing posts by a specific author.', 'saman-seo' ),
                'default_title_template'      => '{{author}} {{separator}} {{site_title}}',
                'default_description_template' => 'Articles written by {{author}}. {{author_bio}}',
                'variables'                   => [ 'author', 'author_bio', 'separator', 'site_title' ],
            ],
            'date'   => [
                'name'                        => __( 'Date Archives', 'saman-seo' ),
                'description'                 => __( 'Archive pages showing posts from a specific date period.', 'saman-seo' ),
                'default_title_template'      => '{{date}} Archives {{separator}} {{site_title}}',
                'default_description_template' => 'Browse our articles from {{date}}.',
                'variables'                   => [ 'date', 'separator', 'site_title' ],
            ],
            'search' => [
                'name'                        => __( 'Search Results', 'saman-seo' ),
                'description'                 => __( 'Pages showing search results.', 'saman-seo' ),
                'default_title_template'      => 'Search: {{search_term}} {{separator}} {{site_title}}',
                'default_description_template' => 'Search results for "{{search_term}}" on {{site_title}}.',
                'variables'                   => [ 'search_term', 'separator', 'site_title' ],
            ],
            '404'    => [
                'name'                        => __( '404 Page', 'saman-seo' ),
                'description'                 => __( 'The page shown when content is not found.', 'saman-seo' ),
                'default_title_template'      => 'Page Not Found {{separator}} {{site_title}}',
                'default_description_template' => 'The page you are looking for could not be found.',
                'variables'                   => [ 'request_url', 'separator', 'site_title' ],
            ],
        ];

        $data = [];
        foreach ( $archive_types as $slug => $info ) {
            $archive_defaults = isset( $defaults[ $slug ] ) ? $defaults[ $slug ] : [];
            $archive_settings = isset( $settings[ $slug ] ) ? $settings[ $slug ] : [];

            // Merge old and new structure
            $title_template = ! empty( $archive_defaults['title_template'] )
                ? $archive_defaults['title_template']
                : ( isset( $archive_settings['title'] ) ? $archive_settings['title'] : $info['default_title_template'] );

            $description_template = ! empty( $archive_defaults['description_template'] )
                ? $archive_defaults['description_template']
                : ( isset( $archive_settings['description'] ) ? $archive_settings['description'] : $info['default_description_template'] );

            // Determine noindex
            $noindex = false;
            if ( isset( $archive_defaults['noindex'] ) ) {
                $noindex = ! empty( $archive_defaults['noindex'] );
            } elseif ( isset( $archive_settings['show'] ) ) {
                $noindex = empty( $archive_settings['show'] );
            } elseif ( in_array( $slug, [ 'search', '404' ], true ) ) {
                $noindex = true; // Default for search and 404
            }

            $data[] = [
                'slug'                 => $slug,
                'name'                 => $info['name'],
                'description'          => $info['description'],
                'noindex'              => $noindex,
                'title_template'       => $title_template,
                'description_template' => $description_template,
                'available_variables'  => $info['variables'],
            ];
        }

        return $data;
    }

    /**
     * Save archives settings.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function save_archives( $request ) {
        $params = $request->get_json_params();

        if ( ! is_array( $params ) ) {
            return $this->error( __( 'Invalid data.', 'saman-seo' ), 'invalid_data', 400 );
        }

        $allowed  = [ 'author', 'date', 'search', '404' ];
        $defaults = [];
        $settings = [];

        foreach ( $params as $archive_data ) {
            if ( empty( $archive_data['slug'] ) || ! in_array( $archive_data['slug'], $allowed, true ) ) {
                continue;
            }

            $slug = $archive_data['slug'];

            $defaults[ $slug ] = [
                'noindex'              => ! empty( $archive_data['noindex'] ) ? '1' : '0',
                'title_template'       => isset( $archive_data['title_template'] ) ? sanitize_text_field( $archive_data['title_template'] ) : '',
                'description_template' => isset( $archive_data['description_template'] ) ? sanitize_textarea_field( $archive_data['description_template'] ) : '',
            ];

            $settings[ $slug ] = [
                'show'        => empty( $archive_data['noindex'] ) ? '1' : '0',
                'title'       => isset( $archive_data['title_template'] ) ? sanitize_text_field( $archive_data['title_template'] ) : '',
                'description' => isset( $archive_data['description_template'] ) ? sanitize_textarea_field( $archive_data['description_template'] ) : '',
            ];
        }

        update_option( 'SAMAN_SEO_archive_defaults', $defaults );
        update_option( 'SAMAN_SEO_archive_settings', $settings );

        return $this->success( $this->get_archives_data(), __( 'Archive settings saved.', 'saman-seo' ) );
    }

    /**
     * Get template variables reference.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_variables( $request ) {
        return $this->success( $this->get_variables_data() );
    }

    /**
     * Get variables data with preview values.
     *
     * @return array
     */
    private function get_variables_data() {
        $separator = get_option( 'SAMAN_SEO_title_separator', '-' );
        $site_name = get_bloginfo( 'name' );
        $tagline   = get_bloginfo( 'description' );

        // Get a sample author
        $users = get_users( [ 'number' => 1, 'capability' => 'edit_posts' ] );
        $author_name = ! empty( $users ) ? $users[0]->display_name : 'John Doe';
        $author_bio  = ! empty( $users ) ? get_user_meta( $users[0]->ID, 'description', true ) : 'Author biography...';
        if ( empty( $author_bio ) ) {
            $author_bio = 'Author biography...';
        }

        // Get a sample post for realistic previews
        $sample_post = get_posts( [ 'posts_per_page' => 1, 'post_status' => 'publish' ] );
        $post_title  = ! empty( $sample_post ) ? $sample_post[0]->post_title : 'Example Post Title';
        $post_excerpt = ! empty( $sample_post ) && ! empty( $sample_post[0]->post_excerpt )
            ? wp_trim_words( $sample_post[0]->post_excerpt, 15 )
            : 'This is an example excerpt from a post...';

        // Get a sample category
        $categories = get_categories( [ 'number' => 1 ] );
        $category_name = ! empty( $categories ) ? $categories[0]->name : 'Technology';
        $category_desc = ! empty( $categories ) && ! empty( $categories[0]->description )
            ? $categories[0]->description
            : 'A description of this category...';

        $variables = [
            'global' => [
                'label' => __( 'General', 'saman-seo' ),
                'vars'  => [
                    [
                        'tag'     => 'site_title',
                        'label'   => __( 'Site Title', 'saman-seo' ),
                        'desc'    => __( 'The main title of your site', 'saman-seo' ),
                        'preview' => $site_name,
                    ],
                    [
                        'tag'     => 'tagline',
                        'label'   => __( 'Tagline', 'saman-seo' ),
                        'desc'    => __( 'Site description / tagline', 'saman-seo' ),
                        'preview' => $tagline,
                    ],
                    [
                        'tag'     => 'separator',
                        'label'   => __( 'Separator', 'saman-seo' ),
                        'desc'    => __( 'Character between title parts', 'saman-seo' ),
                        'preview' => $separator,
                    ],
                    [
                        'tag'     => 'current_year',
                        'label'   => __( 'Current Year', 'saman-seo' ),
                        'desc'    => __( 'The current year (4 digits)', 'saman-seo' ),
                        'preview' => date_i18n( 'Y' ),
                    ],
                ],
            ],
            'post' => [
                'label' => __( 'Post / Page', 'saman-seo' ),
                'vars'  => [
                    [
                        'tag'     => 'post_title',
                        'label'   => __( 'Post Title', 'saman-seo' ),
                        'desc'    => __( 'Title of the current post/page', 'saman-seo' ),
                        'preview' => $post_title,
                    ],
                    [
                        'tag'     => 'post_excerpt',
                        'label'   => __( 'Excerpt', 'saman-seo' ),
                        'desc'    => __( 'Post excerpt or snippet', 'saman-seo' ),
                        'preview' => $post_excerpt,
                    ],
                    [
                        'tag'     => 'post_date',
                        'label'   => __( 'Publish Date', 'saman-seo' ),
                        'desc'    => __( 'Date the post was published', 'saman-seo' ),
                        'preview' => date_i18n( get_option( 'date_format' ) ),
                    ],
                    [
                        'tag'     => 'post_author',
                        'label'   => __( 'Author', 'saman-seo' ),
                        'desc'    => __( 'Display name of the author', 'saman-seo' ),
                        'preview' => $author_name,
                    ],
                    [
                        'tag'     => 'category',
                        'label'   => __( 'Primary Category', 'saman-seo' ),
                        'desc'    => __( 'The main category', 'saman-seo' ),
                        'preview' => $category_name,
                    ],
                    [
                        'tag'     => 'id',
                        'label'   => __( 'Post ID', 'saman-seo' ),
                        'desc'    => __( 'Numeric post ID', 'saman-seo' ),
                        'preview' => ! empty( $sample_post ) ? (string) $sample_post[0]->ID : '123',
                    ],
                ],
            ],
            'taxonomy' => [
                'label' => __( 'Taxonomy', 'saman-seo' ),
                'vars'  => [
                    [
                        'tag'     => 'term_title',
                        'label'   => __( 'Term Name', 'saman-seo' ),
                        'desc'    => __( 'Name of the category/tag', 'saman-seo' ),
                        'preview' => $category_name,
                    ],
                    [
                        'tag'     => 'term_description',
                        'label'   => __( 'Term Description', 'saman-seo' ),
                        'desc'    => __( 'Description of the term', 'saman-seo' ),
                        'preview' => $category_desc,
                    ],
                ],
            ],
            'author' => [
                'label' => __( 'Author', 'saman-seo' ),
                'vars'  => [
                    [
                        'tag'     => 'author',
                        'label'   => __( 'Author Name', 'saman-seo' ),
                        'desc'    => __( 'Name of the author', 'saman-seo' ),
                        'preview' => $author_name,
                    ],
                    [
                        'tag'     => 'author_bio',
                        'label'   => __( 'Author Bio', 'saman-seo' ),
                        'desc'    => __( 'Biographical info', 'saman-seo' ),
                        'preview' => wp_trim_words( $author_bio, 10 ),
                    ],
                ],
            ],
            'archive' => [
                'label' => __( 'Archive', 'saman-seo' ),
                'vars'  => [
                    [
                        'tag'     => 'date',
                        'label'   => __( 'Archive Date', 'saman-seo' ),
                        'desc'    => __( 'Date for date archives', 'saman-seo' ),
                        'preview' => date_i18n( 'F Y' ),
                    ],
                    [
                        'tag'     => 'search_term',
                        'label'   => __( 'Search Query', 'saman-seo' ),
                        'desc'    => __( 'User search keywords', 'saman-seo' ),
                        'preview' => 'example search',
                    ],
                    [
                        'tag'     => 'request_url',
                        'label'   => __( 'Requested URL', 'saman-seo' ),
                        'desc'    => __( 'URL that was requested', 'saman-seo' ),
                        'preview' => '/example-page/',
                    ],
                ],
            ],
        ];

        return $variables;
    }

    /**
     * Get variable values map for preview rendering.
     *
     * @return array Key-value map of variable tag to preview value.
     */
    private function get_variable_values_map() {
        $variables = $this->get_variables_data();
        $map = [];

        foreach ( $variables as $group ) {
            foreach ( $group['vars'] as $var ) {
                $map[ $var['tag'] ] = $var['preview'];
            }
        }

        return $map;
    }
}
