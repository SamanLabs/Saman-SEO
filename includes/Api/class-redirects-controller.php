<?php
/**
 * Redirects REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for redirects and 404 logs.
 */
class Redirects_Controller extends REST_Controller {

    /**
     * Redirects table name.
     *
     * @var string
     */
    private $redirects_table;

    /**
     * 404 log table name.
     *
     * @var string
     */
    private $log_table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->redirects_table = $wpdb->prefix . 'wpseopilot_redirects';
        $this->log_table       = $wpdb->prefix . 'wpseopilot_404_log';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // Redirects endpoints
        register_rest_route( $this->namespace, '/redirects', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_redirects' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'search' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'group' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'status_code' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'is_regex' => [
                        'required' => false,
                        'type'     => 'boolean',
                    ],
                    'per_page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 50,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_redirect' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'source' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'target' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                    'status_code' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 301,
                        'sanitize_callback' => 'absint',
                    ],
                    'is_regex' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'group_name' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'start_date' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'end_date' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'notes' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/redirects/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_redirect' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::EDITABLE,
                'callback'            => [ $this, 'update_redirect' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'source' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'target' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                    'status_code' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'is_regex' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'group_name' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'start_date' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'end_date' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'notes' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                    ],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_redirect' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Groups endpoint
        register_rest_route( $this->namespace, '/redirects/groups', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_redirect_groups' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Chain detection endpoint
        register_rest_route( $this->namespace, '/redirects/validate-chain', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'validate_chain' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'source' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'target' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'exclude_id' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Import/Export endpoints
        register_rest_route( $this->namespace, '/redirects/export', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'export_redirects' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'format' => [
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'json',
                        'enum'     => [ 'json', 'csv' ],
                    ],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/redirects/import', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'import_redirects' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'format' => [
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'json',
                        'enum'     => [ 'json', 'csv' ],
                    ],
                    'data' => [
                        'required' => true,
                        'type'     => 'string',
                    ],
                    'overwrite' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                ],
            ],
        ] );

        // Bulk delete endpoint
        register_rest_route( $this->namespace, '/redirects/bulk-delete', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'bulk_delete_redirects' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'ids' => [
                        'required' => true,
                        'type'     => 'array',
                        'items'    => [ 'type' => 'integer' ],
                    ],
                ],
            ],
        ] );

        // 404 log endpoints
        register_rest_route( $this->namespace, '/404-log', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_404_log' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'sort' => [
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'recent',
                        'enum'     => [ 'recent', 'top' ],
                    ],
                    'per_page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 50,
                        'sanitize_callback' => 'absint',
                    ],
                    'page' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 1,
                        'sanitize_callback' => 'absint',
                    ],
                    'hide_spam' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => true,
                    ],
                    'hide_images' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'hide_bots' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'hide_ignored' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => true,
                    ],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'clear_404_log' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/404-log/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_404_entry' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Get redirect suggestions for a 404 entry
        register_rest_route( $this->namespace, '/404-log/(?P<id>\d+)/suggestions', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_404_suggestions' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Create redirect from a 404 entry (one-click)
        register_rest_route( $this->namespace, '/404-log/(?P<id>\d+)/create-redirect', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_redirect_from_404' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                    'target' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'esc_url_raw',
                    ],
                    'status_code' => [
                        'required'          => false,
                        'type'              => 'integer',
                        'default'           => 301,
                        'sanitize_callback' => 'absint',
                    ],
                    'delete_entry' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => true,
                    ],
                ],
            ],
        ] );

        // Export 404 log
        register_rest_route( $this->namespace, '/404-log/export', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'export_404_log' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'format' => [
                        'required' => false,
                        'type'     => 'string',
                        'default'  => 'json',
                        'enum'     => [ 'json', 'csv' ],
                    ],
                    'hide_spam' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => true,
                    ],
                    'hide_images' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'hide_bots' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                ],
            ],
        ] );

        // Ignore a 404 entry
        register_rest_route( $this->namespace, '/404-log/(?P<id>\d+)/ignore', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'ignore_404_entry' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'unignore_404_entry' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Ignore patterns management
        register_rest_route( $this->namespace, '/404-ignore-patterns', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_ignore_patterns' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'create_ignore_pattern' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'pattern' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                    'is_regex' => [
                        'required' => false,
                        'type'     => 'boolean',
                        'default'  => false,
                    ],
                    'reason' => [
                        'required'          => false,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/404-ignore-patterns/(?P<id>\d+)', [
            [
                'methods'             => \WP_REST_Server::DELETABLE,
                'callback'            => [ $this, 'delete_ignore_pattern' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'id' => [
                        'required'          => true,
                        'type'              => 'integer',
                        'sanitize_callback' => 'absint',
                    ],
                ],
            ],
        ] );

        // Slug suggestions endpoints
        register_rest_route( $this->namespace, '/slug-suggestions', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_slug_suggestions' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        register_rest_route( $this->namespace, '/slug-suggestions/(?P<key>[a-f0-9]+)/dismiss', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'dismiss_slug_suggestion' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'key' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/slug-suggestions/(?P<key>[a-f0-9]+)/apply', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'apply_slug_suggestion' ],
                'permission_callback' => [ $this, 'permission_check' ],
                'args'                => [
                    'key' => [
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_text_field',
                    ],
                ],
            ],
        ] );
    }

    /**
     * Get all redirects with filtering and pagination.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_redirects( $request ) {
        global $wpdb;

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->redirects_table
        ) );

        if ( ! $table_exists ) {
            return $this->success( [
                'items'       => [],
                'total'       => 0,
                'page'        => 1,
                'per_page'    => 50,
                'total_pages' => 1,
            ] );
        }

        $search      = $request->get_param( 'search' );
        $group       = $request->get_param( 'group' );
        $status_code = $request->get_param( 'status_code' );
        $is_regex    = $request->get_param( 'is_regex' );
        $per_page    = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $page        = max( 1, (int) $request->get_param( 'page' ) );
        $offset      = ( $page - 1 ) * $per_page;

        $where   = [];
        $params  = [];

        // Search filter
        if ( ! empty( $search ) ) {
            $where[]  = '(source LIKE %s OR target LIKE %s OR notes LIKE %s)';
            $like     = '%' . $wpdb->esc_like( $search ) . '%';
            $params[] = $like;
            $params[] = $like;
            $params[] = $like;
        }

        // Group filter
        if ( ! empty( $group ) ) {
            $where[]  = 'group_name = %s';
            $params[] = $group;
        }

        // Status code filter
        if ( ! empty( $status_code ) ) {
            $where[]  = 'status_code = %d';
            $params[] = $status_code;
        }

        // Regex filter
        if ( null !== $is_regex ) {
            $where[]  = 'is_regex = %d';
            $params[] = $is_regex ? 1 : 0;
        }

        $where_sql = $where ? ' WHERE ' . implode( ' AND ', $where ) : '';

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->redirects_table}{$where_sql}";
        if ( $params ) {
            $count_sql = $wpdb->prepare( $count_sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total = (int) $wpdb->get_var( $count_sql );

        // Get paginated results
        $sql = "SELECT * FROM {$this->redirects_table}{$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $redirects = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, [ $per_page, $offset ] ) ) );

        $data = [];
        if ( $redirects ) {
            foreach ( $redirects as $redirect ) {
                $data[] = $this->format_redirect( $redirect );
            }
        }

        return $this->success( [
            'items'       => $data,
            'total'       => $total,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => max( 1, (int) ceil( $total / $per_page ) ),
        ] );
    }

    /**
     * Get a single redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_redirect( $request ) {
        global $wpdb;

        $id = $request->get_param( 'id' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $redirect = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->redirects_table} WHERE id = %d",
            $id
        ) );

        if ( ! $redirect ) {
            return $this->error( __( 'Redirect not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        return $this->success( $this->format_redirect( $redirect ) );
    }

    /**
     * Format a redirect row for API response.
     *
     * @param object $redirect Database row.
     * @return array
     */
    private function format_redirect( $redirect ) {
        return [
            'id'          => (int) $redirect->id,
            'source'      => $redirect->source,
            'target'      => $redirect->target,
            'status_code' => (int) $redirect->status_code,
            'hits'        => (int) $redirect->hits,
            'last_hit'    => $redirect->last_hit,
            'is_regex'    => isset( $redirect->is_regex ) ? (bool) $redirect->is_regex : false,
            'group_name'  => isset( $redirect->group_name ) ? $redirect->group_name : '',
            'start_date'  => isset( $redirect->start_date ) ? $redirect->start_date : null,
            'end_date'    => isset( $redirect->end_date ) ? $redirect->end_date : null,
            'notes'       => isset( $redirect->notes ) ? $redirect->notes : '',
            'created_at'  => isset( $redirect->created_at ) ? $redirect->created_at : null,
            'is_active'   => $this->is_redirect_active( $redirect ),
        ];
    }

    /**
     * Check if a timed redirect is currently active.
     *
     * @param object $redirect Redirect row.
     * @return bool
     */
    private function is_redirect_active( $redirect ) {
        $now = current_time( 'mysql' );

        if ( ! empty( $redirect->start_date ) && $now < $redirect->start_date ) {
            return false;
        }

        if ( ! empty( $redirect->end_date ) && $now > $redirect->end_date ) {
            return false;
        }

        return true;
    }

    /**
     * Create a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_redirect( $request ) {
        $manager = new \WPSEOPilot\Service\Redirect_Manager();

        $source      = $request->get_param( 'source' );
        $target      = $request->get_param( 'target' );
        $status_code = $request->get_param( 'status_code' );

        $extra = [
            'is_regex'   => $request->get_param( 'is_regex' ),
            'group_name' => $request->get_param( 'group_name' ),
            'start_date' => $request->get_param( 'start_date' ),
            'end_date'   => $request->get_param( 'end_date' ),
            'notes'      => $request->get_param( 'notes' ),
        ];

        $result = $manager->create_redirect( $source, $target, $status_code, $extra );

        if ( is_wp_error( $result ) ) {
            return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
        }

        $redirect = $manager->get_redirect( $result );

        return $this->success( $this->format_redirect( $redirect ), __( 'Redirect created successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Update a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function update_redirect( $request ) {
        $manager = new \WPSEOPilot\Service\Redirect_Manager();

        $id   = $request->get_param( 'id' );
        $data = [];

        // Collect all provided fields
        $fields = [ 'source', 'target', 'status_code', 'is_regex', 'group_name', 'start_date', 'end_date', 'notes' ];
        foreach ( $fields as $field ) {
            $value = $request->get_param( $field );
            if ( null !== $value ) {
                $data[ $field ] = $value;
            }
        }

        $result = $manager->update_redirect( $id, $data );

        if ( is_wp_error( $result ) ) {
            $status = 'not_found' === $result->get_error_code() ? 404 : 400;
            return $this->error( $result->get_error_message(), $result->get_error_code(), $status );
        }

        $redirect = $manager->get_redirect( $id );

        return $this->success( $this->format_redirect( $redirect ), __( 'Redirect updated successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Delete a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_redirect( $request ) {
        $manager = new \WPSEOPilot\Service\Redirect_Manager();

        $id     = $request->get_param( 'id' );
        $result = $manager->delete_redirect( $id );

        if ( is_wp_error( $result ) ) {
            return $this->error( $result->get_error_message(), $result->get_error_code(), 404 );
        }

        return $this->success( null, __( 'Redirect deleted.', 'wp-seo-pilot' ) );
    }

    /**
     * Bulk delete redirects.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function bulk_delete_redirects( $request ) {
        global $wpdb;

        $ids = $request->get_param( 'ids' );

        if ( empty( $ids ) || ! is_array( $ids ) ) {
            return $this->error( __( 'No IDs provided.', 'wp-seo-pilot' ), 'missing_ids', 400 );
        }

        $ids          = array_map( 'absint', $ids );
        $ids          = array_filter( $ids );
        $placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->query( $wpdb->prepare(
            "DELETE FROM {$this->redirects_table} WHERE id IN ({$placeholders})",
            $ids
        ) );

        if ( class_exists( '\WPSEOPilot\Service\Redirect_Manager' ) ) {
            \WPSEOPilot\Service\Redirect_Manager::flush_cache();
        }

        return $this->success( [ 'deleted' => $deleted ], sprintf( __( '%d redirects deleted.', 'wp-seo-pilot' ), $deleted ) );
    }

    /**
     * Get unique redirect groups.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_redirect_groups( $request ) {
        global $wpdb;

        // Check if table and column exist
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->redirects_table
        ) );

        if ( ! $table_exists ) {
            return $this->success( [] );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $groups = $wpdb->get_col(
            "SELECT DISTINCT group_name FROM {$this->redirects_table} WHERE group_name != '' ORDER BY group_name ASC"
        );

        return $this->success( $groups ? $groups : [] );
    }

    /**
     * Validate redirect chain/loop.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function validate_chain( $request ) {
        global $wpdb;

        $source     = $request->get_param( 'source' );
        $target     = $request->get_param( 'target' );
        $exclude_id = $request->get_param( 'exclude_id' );

        // Normalize source
        $source = '/' . ltrim( $source, '/' );
        $source = '/' === $source ? '/' : rtrim( $source, '/' );

        // Extract path from target
        $target_path = wp_parse_url( $target, PHP_URL_PATH );
        if ( $target_path ) {
            $target_path = '/' === $target_path ? '/' : rtrim( $target_path, '/' );
        }

        $warnings = [];

        // Check for direct loop (A → A)
        if ( $source === $target_path ) {
            $warnings[] = [
                'type'    => 'loop',
                'message' => __( 'Source and target are the same URL. This will create an infinite loop.', 'wp-seo-pilot' ),
            ];
        }

        // Check for reverse redirect (B → A when creating A → B)
        if ( $target_path ) {
            $exclude_sql = $exclude_id ? $wpdb->prepare( ' AND id != %d', $exclude_id ) : '';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $reverse = $wpdb->get_row( $wpdb->prepare(
                "SELECT * FROM {$this->redirects_table} WHERE source = %s{$exclude_sql} LIMIT 1",
                $target_path
            ) );

            if ( $reverse ) {
                // Check if reverse target points back to our source (loop)
                $reverse_target_path = wp_parse_url( $reverse->target, PHP_URL_PATH );
                if ( $reverse_target_path ) {
                    $reverse_target_path = '/' === $reverse_target_path ? '/' : rtrim( $reverse_target_path, '/' );
                }

                if ( $reverse_target_path === $source ) {
                    $warnings[] = [
                        'type'    => 'loop',
                        'message' => sprintf(
                            __( 'This will create a redirect loop: %1$s → %2$s → %1$s', 'wp-seo-pilot' ),
                            $source,
                            $target_path
                        ),
                    ];
                } else {
                    // It's a chain
                    $warnings[] = [
                        'type'    => 'chain',
                        'message' => sprintf(
                            __( 'This creates a redirect chain: %1$s → %2$s → %3$s. Consider redirecting directly to the final destination.', 'wp-seo-pilot' ),
                            $source,
                            $target_path,
                            $reverse->target
                        ),
                    ];
                }
            }
        }

        // Check if source already has a redirect pointing to it
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $incoming = $wpdb->get_results( $wpdb->prepare(
            "SELECT source, target FROM {$this->redirects_table} WHERE target LIKE %s LIMIT 5",
            '%' . $wpdb->esc_like( $source ) . '%'
        ) );

        if ( $incoming ) {
            foreach ( $incoming as $redirect ) {
                $warnings[] = [
                    'type'    => 'chain',
                    'message' => sprintf(
                        __( 'Note: %s already redirects to this source. After adding this redirect, it will become a chain.', 'wp-seo-pilot' ),
                        $redirect->source
                    ),
                ];
            }
        }

        return $this->success( [
            'valid'    => empty( array_filter( $warnings, function( $w ) { return 'loop' === $w['type']; } ) ),
            'warnings' => $warnings,
        ] );
    }

    /**
     * Export redirects.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function export_redirects( $request ) {
        global $wpdb;

        $format = $request->get_param( 'format' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $redirects = $wpdb->get_results( "SELECT * FROM {$this->redirects_table} ORDER BY id DESC" );

        if ( 'csv' === $format ) {
            $csv = "source,target,status_code,is_regex,group_name,start_date,end_date,notes\n";
            foreach ( $redirects as $r ) {
                $csv .= sprintf(
                    '"%s","%s",%d,%d,"%s","%s","%s","%s"' . "\n",
                    str_replace( '"', '""', $r->source ),
                    str_replace( '"', '""', $r->target ),
                    $r->status_code,
                    isset( $r->is_regex ) ? $r->is_regex : 0,
                    str_replace( '"', '""', isset( $r->group_name ) ? $r->group_name : '' ),
                    isset( $r->start_date ) ? $r->start_date : '',
                    isset( $r->end_date ) ? $r->end_date : '',
                    str_replace( '"', '""', isset( $r->notes ) ? $r->notes : '' )
                );
            }
            return $this->success( [ 'content' => $csv, 'filename' => 'redirects-export.csv' ] );
        }

        // JSON format
        $data = [];
        foreach ( $redirects as $r ) {
            $data[] = [
                'source'      => $r->source,
                'target'      => $r->target,
                'status_code' => (int) $r->status_code,
                'is_regex'    => isset( $r->is_regex ) ? (bool) $r->is_regex : false,
                'group_name'  => isset( $r->group_name ) ? $r->group_name : '',
                'start_date'  => isset( $r->start_date ) ? $r->start_date : null,
                'end_date'    => isset( $r->end_date ) ? $r->end_date : null,
                'notes'       => isset( $r->notes ) ? $r->notes : '',
            ];
        }

        return $this->success( [ 'content' => wp_json_encode( $data, JSON_PRETTY_PRINT ), 'filename' => 'redirects-export.json' ] );
    }

    /**
     * Import redirects.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function import_redirects( $request ) {
        $format    = $request->get_param( 'format' );
        $data      = $request->get_param( 'data' );
        $overwrite = $request->get_param( 'overwrite' );

        $manager = new \WPSEOPilot\Service\Redirect_Manager();

        $imported = 0;
        $skipped  = 0;
        $errors   = [];

        if ( 'csv' === $format ) {
            $lines = explode( "\n", $data );
            $header = null;

            foreach ( $lines as $i => $line ) {
                $line = trim( $line );
                if ( empty( $line ) ) {
                    continue;
                }

                // Parse CSV line
                $row = str_getcsv( $line );

                // First non-empty line is header
                if ( null === $header ) {
                    $header = array_map( 'strtolower', array_map( 'trim', $row ) );
                    continue;
                }

                if ( count( $row ) < 2 ) {
                    continue;
                }

                $redirect_data = [];
                foreach ( $header as $idx => $col ) {
                    if ( isset( $row[ $idx ] ) ) {
                        $redirect_data[ $col ] = trim( $row[ $idx ] );
                    }
                }

                $result = $this->import_single_redirect( $redirect_data, $manager, $overwrite );
                if ( true === $result ) {
                    $imported++;
                } elseif ( 'skipped' === $result ) {
                    $skipped++;
                } else {
                    $errors[] = sprintf( 'Line %d: %s', $i + 1, $result );
                }
            }
        } else {
            // JSON format
            $json_data = json_decode( $data, true );

            if ( ! is_array( $json_data ) ) {
                return $this->error( __( 'Invalid JSON data.', 'wp-seo-pilot' ), 'invalid_json', 400 );
            }

            foreach ( $json_data as $i => $redirect_data ) {
                $result = $this->import_single_redirect( $redirect_data, $manager, $overwrite );
                if ( true === $result ) {
                    $imported++;
                } elseif ( 'skipped' === $result ) {
                    $skipped++;
                } else {
                    $errors[] = sprintf( 'Item %d: %s', $i + 1, $result );
                }
            }
        }

        return $this->success( [
            'imported' => $imported,
            'skipped'  => $skipped,
            'errors'   => $errors,
        ], sprintf( __( 'Imported %d redirects, skipped %d.', 'wp-seo-pilot' ), $imported, $skipped ) );
    }

    /**
     * Import a single redirect.
     *
     * @param array                                $data      Redirect data.
     * @param \WPSEOPilot\Service\Redirect_Manager $manager   Redirect manager instance.
     * @param bool                                 $overwrite Whether to overwrite existing.
     * @return bool|string True on success, 'skipped' if skipped, error message on failure.
     */
    private function import_single_redirect( $data, $manager, $overwrite ) {
        $source = isset( $data['source'] ) ? $data['source'] : '';
        $target = isset( $data['target'] ) ? $data['target'] : '';

        if ( empty( $source ) || empty( $target ) ) {
            return __( 'Missing source or target.', 'wp-seo-pilot' );
        }

        $status_code = isset( $data['status_code'] ) ? (int) $data['status_code'] : 301;

        $extra = [
            'is_regex'   => isset( $data['is_regex'] ) ? (bool) $data['is_regex'] : false,
            'group_name' => isset( $data['group_name'] ) ? $data['group_name'] : '',
            'start_date' => isset( $data['start_date'] ) ? $data['start_date'] : null,
            'end_date'   => isset( $data['end_date'] ) ? $data['end_date'] : null,
            'notes'      => isset( $data['notes'] ) ? $data['notes'] : '',
        ];

        $result = $manager->create_redirect( $source, $target, $status_code, $extra );

        if ( is_wp_error( $result ) ) {
            if ( 'redirect_exists' === $result->get_error_code() && $overwrite ) {
                // Find existing and update
                global $wpdb;
                $table = $manager->get_table();

                $normalized = '/' . ltrim( $source, '/' );
                $normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery
                $existing_id = $wpdb->get_var( $wpdb->prepare(
                    "SELECT id FROM {$table} WHERE source = %s",
                    $normalized
                ) );

                if ( $existing_id ) {
                    $update_data = array_merge( [ 'source' => $source, 'target' => $target, 'status_code' => $status_code ], $extra );
                    $update_result = $manager->update_redirect( $existing_id, $update_data );

                    if ( is_wp_error( $update_result ) ) {
                        return $update_result->get_error_message();
                    }
                    return true;
                }
            }

            if ( 'redirect_exists' === $result->get_error_code() ) {
                return 'skipped';
            }

            return $result->get_error_message();
        }

        return true;
    }

    /**
     * Get 404 log entries.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_404_log( $request ) {
        global $wpdb;

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->log_table
        ) );

        if ( ! $table_exists ) {
            return $this->success( [
                'items'         => [],
                'total'         => 0,
                'page'          => 1,
                'per_page'      => 50,
                'total_pages'   => 1,
                'bot_count'     => 0,
                'ignored_count' => 0,
            ] );
        }

        $sort         = $request->get_param( 'sort' );
        $per_page     = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $page         = max( 1, (int) $request->get_param( 'page' ) );
        $hide_spam    = (bool) $request->get_param( 'hide_spam' );
        $hide_images  = (bool) $request->get_param( 'hide_images' );
        $hide_bots    = (bool) $request->get_param( 'hide_bots' );
        $hide_ignored = (bool) $request->get_param( 'hide_ignored' );

        $order_by = ( 'top' === $sort ) ? 'hits' : 'last_seen';
        $offset   = ( $page - 1 ) * $per_page;

        $filters = [];
        $params  = [];

        if ( $hide_spam ) {
            foreach ( $this->get_spam_url_patterns() as $pattern ) {
                $filters[] = 'request_uri NOT LIKE %s';
                $params[]  = $pattern;
            }
        }

        if ( $hide_images ) {
            foreach ( $this->get_image_url_patterns() as $pattern ) {
                $filters[] = 'request_uri NOT LIKE %s';
                $params[]  = $pattern;
            }
        }

        // Bot filter - check if column exists first
        if ( $hide_bots && $this->has_column( $this->log_table, 'is_bot' ) ) {
            $filters[] = 'is_bot = 0';
        }

        // Ignored filter - check if column exists first
        if ( $hide_ignored && $this->has_column( $this->log_table, 'is_ignored' ) ) {
            $filters[] = 'is_ignored = 0';
        }

        $where_sql = $filters ? ' WHERE ' . implode( ' AND ', $filters ) : '';

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->log_table}{$where_sql}";
        if ( $params ) {
            $count_sql = $wpdb->prepare( $count_sql, $params );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total_count = (int) $wpdb->get_var( $count_sql );

        // Get bot count for stats
        $bot_count = 0;
        if ( $this->has_column( $this->log_table, 'is_bot' ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $bot_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->log_table} WHERE is_bot = 1" );
        }

        // Get ignored count for stats
        $ignored_count = 0;
        if ( $this->has_column( $this->log_table, 'is_ignored' ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $ignored_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->log_table} WHERE is_ignored = 1" );
        }

        // Get rows
        $sql = "SELECT * FROM {$this->log_table}{$where_sql} ORDER BY {$order_by} DESC LIMIT %d OFFSET %d";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $params, [ $per_page, $offset ] ) ) );

        // Check which rows have redirects
        if ( $rows ) {
            $rows = $this->annotate_redirect_status( $rows );
        } else {
            $rows = [];
        }

        $data = [];
        foreach ( $rows as $row ) {
            $data[] = [
                'id'              => (int) $row->id,
                'request_uri'     => $row->request_uri,
                'hits'            => (int) $row->hits,
                'last_seen'       => $row->last_seen,
                'first_seen'      => isset( $row->first_seen ) ? $row->first_seen : null,
                'device_label'    => ! empty( $row->device_label ) ? $row->device_label : __( 'Unknown device', 'wp-seo-pilot' ),
                'redirect_exists' => ! empty( $row->redirect_exists ),
                'is_bot'          => isset( $row->is_bot ) ? (bool) $row->is_bot : false,
                'is_ignored'      => isset( $row->is_ignored ) ? (bool) $row->is_ignored : false,
                'referrer'        => isset( $row->referrer ) ? $row->referrer : '',
            ];
        }

        $total_pages = max( 1, (int) ceil( $total_count / $per_page ) );

        return $this->success( [
            'items'         => $data,
            'total'         => $total_count,
            'page'          => $page,
            'per_page'      => $per_page,
            'total_pages'   => $total_pages,
            'bot_count'     => $bot_count,
            'ignored_count' => $ignored_count,
        ] );
    }

    /**
     * Check if a table has a specific column.
     *
     * @param string $table  Table name.
     * @param string $column Column name.
     * @return bool
     */
    private function has_column( $table, $column ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $result = $wpdb->get_var( $wpdb->prepare(
            'SHOW COLUMNS FROM ' . $table . ' LIKE %s', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $column
        ) );

        return ! empty( $result );
    }

    /**
     * Clear entire 404 log.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function clear_404_log( $request ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $wpdb->query( "TRUNCATE TABLE {$this->log_table}" );

        return $this->success( null, __( '404 log cleared.', 'wp-seo-pilot' ) );
    }

    /**
     * Delete a single 404 log entry.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_404_entry( $request ) {
        global $wpdb;

        $id = $request->get_param( 'id' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->delete(
            $this->log_table,
            [ 'id' => $id ],
            [ '%d' ]
        );

        if ( ! $deleted ) {
            return $this->error( __( 'Entry not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        return $this->success( null, __( 'Entry deleted.', 'wp-seo-pilot' ) );
    }

    /**
     * Get redirect suggestions for a 404 entry using fuzzy URL matching.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_404_suggestions( $request ) {
        global $wpdb;

        $id = $request->get_param( 'id' );

        // Get the 404 entry
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $entry = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->log_table} WHERE id = %d",
            $id
        ) );

        if ( ! $entry ) {
            return $this->error( __( 'Entry not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $request_uri = $entry->request_uri;
        $suggestions = $this->get_similar_urls( $request_uri, 5 );

        return $this->success( [
            'entry'       => [
                'id'          => (int) $entry->id,
                'request_uri' => $entry->request_uri,
                'hits'        => (int) $entry->hits,
            ],
            'suggestions' => $suggestions,
        ] );
    }

    /**
     * Get similar URLs using Levenshtein distance for fuzzy matching.
     *
     * @param string $request_uri The 404 request URI.
     * @param int    $limit       Maximum number of suggestions.
     * @return array
     */
    private function get_similar_urls( $request_uri, $limit = 5 ) {
        global $wpdb;

        // Extract the slug/path for comparison
        $search_slug = basename( wp_parse_url( $request_uri, PHP_URL_PATH ) );
        if ( empty( $search_slug ) || '/' === $search_slug ) {
            $search_slug = ltrim( $request_uri, '/' );
        }

        // Get all published posts/pages
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $posts = $wpdb->get_results( "
            SELECT ID, post_title, post_name, post_type
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type IN ('post', 'page')
            LIMIT 500
        " );

        if ( ! $posts ) {
            return [];
        }

        $suggestions = [];

        foreach ( $posts as $post ) {
            // Calculate similarity using Levenshtein distance
            $distance = levenshtein( strtolower( $search_slug ), strtolower( $post->post_name ) );
            $max_len  = max( strlen( $search_slug ), strlen( $post->post_name ) );
            $score    = $max_len > 0 ? 1 - ( $distance / $max_len ) : 0;

            // Also check if the search term is contained in the slug or title
            if ( false !== stripos( $post->post_name, $search_slug ) || false !== stripos( $post->post_title, $search_slug ) ) {
                $score = max( $score, 0.7 ); // Boost score for substring matches
            }

            // Only include if score is above threshold (40% similarity)
            if ( $score > 0.4 ) {
                $suggestions[] = [
                    'url'       => get_permalink( $post->ID ),
                    'title'     => $post->post_title,
                    'slug'      => $post->post_name,
                    'score'     => round( $score * 100 ),
                    'type'      => $post->post_type,
                    'post_id'   => (int) $post->ID,
                ];
            }
        }

        // Sort by score descending
        usort( $suggestions, function( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );

        // Limit results
        return array_slice( $suggestions, 0, $limit );
    }

    /**
     * Create a redirect from a 404 entry.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_redirect_from_404( $request ) {
        global $wpdb;

        $id           = $request->get_param( 'id' );
        $target       = $request->get_param( 'target' );
        $status_code  = $request->get_param( 'status_code' );
        $delete_entry = $request->get_param( 'delete_entry' );

        // Get the 404 entry
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $entry = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$this->log_table} WHERE id = %d",
            $id
        ) );

        if ( ! $entry ) {
            return $this->error( __( '404 entry not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $source = $entry->request_uri;

        // Create the redirect
        $manager = new \WPSEOPilot\Service\Redirect_Manager();
        $result  = $manager->create_redirect( $source, $target, $status_code );

        if ( is_wp_error( $result ) ) {
            return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
        }

        // Delete the 404 entry if requested
        if ( $delete_entry ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $wpdb->delete(
                $this->log_table,
                [ 'id' => $id ],
                [ '%d' ]
            );
        }

        $redirect = $manager->get_redirect( $result );

        return $this->success(
            [
                'redirect'      => [
                    'id'          => (int) $redirect->id,
                    'source'      => $redirect->source,
                    'target'      => $redirect->target,
                    'status_code' => (int) $redirect->status_code,
                ],
                'entry_deleted' => $delete_entry,
            ],
            __( 'Redirect created successfully.', 'wp-seo-pilot' )
        );
    }

    /**
     * Export 404 log entries.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function export_404_log( $request ) {
        global $wpdb;

        $format      = $request->get_param( 'format' );
        $hide_spam   = (bool) $request->get_param( 'hide_spam' );
        $hide_images = (bool) $request->get_param( 'hide_images' );
        $hide_bots   = (bool) $request->get_param( 'hide_bots' );

        $filters = [];
        $params  = [];

        if ( $hide_spam ) {
            foreach ( $this->get_spam_url_patterns() as $pattern ) {
                $filters[] = 'request_uri NOT LIKE %s';
                $params[]  = $pattern;
            }
        }

        if ( $hide_images ) {
            foreach ( $this->get_image_url_patterns() as $pattern ) {
                $filters[] = 'request_uri NOT LIKE %s';
                $params[]  = $pattern;
            }
        }

        if ( $hide_bots && $this->has_column( $this->log_table, 'is_bot' ) ) {
            $filters[] = 'is_bot = 0';
        }

        $where_sql = $filters ? ' WHERE ' . implode( ' AND ', $filters ) : '';

        $sql = "SELECT * FROM {$this->log_table}{$where_sql} ORDER BY last_seen DESC";
        if ( $params ) {
            $sql = $wpdb->prepare( $sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $rows = $wpdb->get_results( $sql );

        if ( 'csv' === $format ) {
            $csv = "request_uri,hits,last_seen,first_seen,device_label,is_bot,referrer\n";
            foreach ( $rows as $row ) {
                $csv .= sprintf(
                    '"%s",%d,"%s","%s","%s",%d,"%s"' . "\n",
                    str_replace( '"', '""', $row->request_uri ),
                    (int) $row->hits,
                    $row->last_seen,
                    isset( $row->first_seen ) ? $row->first_seen : '',
                    str_replace( '"', '""', $row->device_label ?? '' ),
                    isset( $row->is_bot ) ? (int) $row->is_bot : 0,
                    str_replace( '"', '""', isset( $row->referrer ) ? $row->referrer : '' )
                );
            }
            return $this->success( [ 'content' => $csv, 'filename' => '404-log-export.csv' ] );
        }

        // JSON format
        $data = [];
        foreach ( $rows as $row ) {
            $data[] = [
                'request_uri'  => $row->request_uri,
                'hits'         => (int) $row->hits,
                'last_seen'    => $row->last_seen,
                'first_seen'   => isset( $row->first_seen ) ? $row->first_seen : null,
                'device_label' => $row->device_label ?? '',
                'is_bot'       => isset( $row->is_bot ) ? (bool) $row->is_bot : false,
                'referrer'     => isset( $row->referrer ) ? $row->referrer : '',
            ];
        }

        return $this->success( [ 'content' => wp_json_encode( $data, JSON_PRETTY_PRINT ), 'filename' => '404-log-export.json' ] );
    }

    /**
     * Ignore a 404 entry.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function ignore_404_entry( $request ) {
        $id      = $request->get_param( 'id' );
        $monitor = new \WPSEOPilot\Service\Request_Monitor();

        if ( $monitor->ignore_entry( $id ) ) {
            return $this->success( null, __( 'Entry ignored.', 'wp-seo-pilot' ) );
        }

        return $this->error( __( 'Failed to ignore entry.', 'wp-seo-pilot' ), 'ignore_failed', 400 );
    }

    /**
     * Unignore a 404 entry.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function unignore_404_entry( $request ) {
        $id      = $request->get_param( 'id' );
        $monitor = new \WPSEOPilot\Service\Request_Monitor();

        if ( $monitor->unignore_entry( $id ) ) {
            return $this->success( null, __( 'Entry unignored.', 'wp-seo-pilot' ) );
        }

        return $this->error( __( 'Failed to unignore entry.', 'wp-seo-pilot' ), 'unignore_failed', 400 );
    }

    /**
     * Get all ignore patterns.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_ignore_patterns( $request ) {
        $monitor  = new \WPSEOPilot\Service\Request_Monitor();
        $patterns = $monitor->get_ignore_patterns();

        $data = [];
        foreach ( $patterns as $pattern ) {
            $data[] = [
                'id'         => (int) $pattern->id,
                'pattern'    => $pattern->pattern,
                'is_regex'   => (bool) $pattern->is_regex,
                'reason'     => $pattern->reason,
                'created_at' => $pattern->created_at,
            ];
        }

        return $this->success( $data );
    }

    /**
     * Create an ignore pattern.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_ignore_pattern( $request ) {
        $pattern  = $request->get_param( 'pattern' );
        $is_regex = $request->get_param( 'is_regex' );
        $reason   = $request->get_param( 'reason' );

        $monitor = new \WPSEOPilot\Service\Request_Monitor();
        $id      = $monitor->add_ignore_pattern( $pattern, $is_regex, $reason );

        if ( false === $id ) {
            return $this->error( __( 'Failed to create pattern.', 'wp-seo-pilot' ), 'create_failed', 400 );
        }

        return $this->success(
            [
                'id'         => $id,
                'pattern'    => $pattern,
                'is_regex'   => $is_regex,
                'reason'     => $reason,
            ],
            __( 'Pattern created successfully.', 'wp-seo-pilot' )
        );
    }

    /**
     * Delete an ignore pattern.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_ignore_pattern( $request ) {
        $id      = $request->get_param( 'id' );
        $monitor = new \WPSEOPilot\Service\Request_Monitor();

        if ( $monitor->delete_ignore_pattern( $id ) ) {
            return $this->success( null, __( 'Pattern deleted.', 'wp-seo-pilot' ) );
        }

        return $this->error( __( 'Failed to delete pattern.', 'wp-seo-pilot' ), 'delete_failed', 400 );
    }

    /**
     * Get slug change suggestions.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function get_slug_suggestions( $request ) {
        $suggestions = get_option( 'wpseopilot_monitor_slugs', [] );

        $data = [];
        foreach ( $suggestions as $key => $suggestion ) {
            $data[] = [
                'key'     => $key,
                'source'  => $suggestion['source'],
                'target'  => $suggestion['target'],
                'post_id' => isset( $suggestion['post_id'] ) ? (int) $suggestion['post_id'] : null,
                'date'    => isset( $suggestion['date'] ) ? $suggestion['date'] : null,
            ];
        }

        return $this->success( $data );
    }

    /**
     * Dismiss a slug suggestion.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function dismiss_slug_suggestion( $request ) {
        $key = $request->get_param( 'key' );

        $suggestions = get_option( 'wpseopilot_monitor_slugs', [] );

        if ( ! isset( $suggestions[ $key ] ) ) {
            return $this->error( __( 'Suggestion not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        unset( $suggestions[ $key ] );
        update_option( 'wpseopilot_monitor_slugs', $suggestions );

        return $this->success( null, __( 'Suggestion dismissed.', 'wp-seo-pilot' ) );
    }

    /**
     * Apply a slug suggestion as a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function apply_slug_suggestion( $request ) {
        $key = $request->get_param( 'key' );

        $suggestions = get_option( 'wpseopilot_monitor_slugs', [] );

        if ( ! isset( $suggestions[ $key ] ) ) {
            return $this->error( __( 'Suggestion not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        $suggestion = $suggestions[ $key ];

        // Create the redirect
        $redirect_request = new \WP_REST_Request( 'POST', '/' . $this->namespace . '/redirects' );
        $redirect_request->set_param( 'source', $suggestion['source'] );
        $redirect_request->set_param( 'target', $suggestion['target'] );
        $redirect_request->set_param( 'status_code', 301 );

        $result = $this->create_redirect( $redirect_request );

        if ( is_wp_error( $result ) ) {
            return $result;
        }

        // Remove from suggestions (already handled in create_redirect)
        return $this->success( $result->get_data()['data'], __( 'Redirect created from suggestion.', 'wp-seo-pilot' ) );
    }

    /**
     * Mark rows that already have redirects configured.
     *
     * @param array $rows Log rows.
     * @return array
     */
    private function annotate_redirect_status( $rows ) {
        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $has_table = $wpdb->get_var( $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $this->redirects_table
        ) );

        if ( $this->redirects_table !== $has_table ) {
            foreach ( $rows as $row ) {
                $row->redirect_exists = false;
            }
            return $rows;
        }

        $requests = [];
        foreach ( $rows as $row ) {
            if ( ! empty( $row->request_uri ) ) {
                $requests[] = $row->request_uri;
            }
        }

        if ( ! $requests ) {
            foreach ( $rows as $row ) {
                $row->redirect_exists = false;
            }
            return $rows;
        }

        $requests     = array_values( array_unique( $requests ) );
        $placeholders = implode( ',', array_fill( 0, count( $requests ), '%s' ) );
        $sql          = "SELECT source FROM {$this->redirects_table} WHERE source IN ({$placeholders})";
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $sources = $wpdb->get_col( $wpdb->prepare( $sql, $requests ) );

        $lookup = $sources ? array_fill_keys( $sources, true ) : [];
        foreach ( $rows as $row ) {
            $row->redirect_exists = isset( $lookup[ $row->request_uri ] );
        }

        return $rows;
    }

    /**
     * Spammy file and path patterns to suppress.
     *
     * @return string[]
     */
    private function get_spam_url_patterns() {
        return [
            '%.php', '%.env', '%.ini', '%.log', '%.bak', '%.old', '%.sql',
            '%.zip', '%.tar', '%.gz', '%.rar', '%.exe', '%.sh', '%.bat',
            '%.cmd', '%.bin', '%.dll', '%.com', '%.scr', '%.sys',
            '%.htaccess', '%.htpasswd',
            '%/.git/config', '%/.git%', '%/.svn%', '%/.hg%', '%/.DS_Store',
            '%/wp-admin%', '%/wp-includes%', '%/wp-login%', '%/xmlrpc.php%',
            '%/readme.html%', '%/license.txt%', '%/wp-config.php%',
            '%/wp-content/plugins/%', '%/wp-content/themes/%',
            '%/wp-content/mu-plugins/%', '%/wp-content/debug.log%',
            '%/wp-json/%', '%/wp-cron.php%', '%/wp-trackback.php%',
            '%.ttf', '%.woff', '%.woff2', '%.eot', '%.otf', '%.sfnt', '%.fnt', '%.fon',
            '%.css', '%.js', '%.map', '%.less', '%.scss', '%.sass', '%.styl',
            '%.xml', '%.json', '%.rss', '%.atom', '%.yaml', '%.yml', '%.csv',
            '%.txt', '%.md', '%.markdown', '%.pdf', '%.doc', '%.docx',
            '%.xls', '%.xlsx', '%.ppt', '%.pptx',
            '%/wp-content/uploads%', '%/wp-content/cache%',
            '%/wp-content/ai1wm-backups%', '%/wp-content/backup%',
            '%/cgi-bin%', '%/vendor%', '%/node_modules%',
            '%/composer.json%', '%/package.json%',
        ];
    }

    /**
     * Image and static asset file patterns.
     *
     * @return string[]
     */
    private function get_image_url_patterns() {
        return [
            '%.png', '%.jpg', '%.jpeg', '%.gif', '%.webp', '%.svg', '%.ico',
            '%.bmp', '%.tiff', '%.heic', '%.avif', '%.psd', '%.ai', '%.eps',
            '%.mp4', '%.webm', '%.mp3', '%.wav', '%.ogg',
        ];
    }
}
