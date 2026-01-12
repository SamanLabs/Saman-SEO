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
                ],
            ],
        ] );

        register_rest_route( $this->namespace, '/redirects/(?P<id>\d+)', [
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
     * Get all redirects.
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
            return $this->success( [] );
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $redirects = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$this->redirects_table} ORDER BY id DESC LIMIT %d",
                200
            )
        );

        $data = [];
        if ( $redirects ) {
            foreach ( $redirects as $redirect ) {
                $data[] = [
                    'id'          => (int) $redirect->id,
                    'source'      => $redirect->source,
                    'target'      => $redirect->target,
                    'status_code' => (int) $redirect->status_code,
                    'hits'        => (int) $redirect->hits,
                    'last_hit'    => $redirect->last_hit,
                ];
            }
        }

        return $this->success( $data );
    }

    /**
     * Create a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function create_redirect( $request ) {
        global $wpdb;

        $source      = $request->get_param( 'source' );
        $target      = $request->get_param( 'target' );
        $status_code = $request->get_param( 'status_code' );

        if ( empty( $source ) || empty( $target ) ) {
            return $this->error( __( 'Source and target are required.', 'wp-seo-pilot' ), 'missing_params', 400 );
        }

        // Normalize source path
        $normalized = '/' . ltrim( $source, '/' );
        $normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );

        // Check if redirect already exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $exists = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$this->redirects_table} WHERE source = %s",
            $normalized
        ) );

        if ( $exists ) {
            return $this->error( __( 'Redirect already exists for this source URL.', 'wp-seo-pilot' ), 'exists', 400 );
        }

        // Validate status code
        $valid_codes = [ 301, 302, 307, 410 ];
        if ( ! in_array( $status_code, $valid_codes, true ) ) {
            $status_code = 301;
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $inserted = $wpdb->insert(
            $this->redirects_table,
            [
                'source'      => $normalized,
                'target'      => $target,
                'status_code' => $status_code,
            ],
            [ '%s', '%s', '%d' ]
        );

        if ( ! $inserted ) {
            return $this->error( __( 'Could not create redirect.', 'wp-seo-pilot' ), 'db_error', 500 );
        }

        // Flush cache if class is available
        if ( class_exists( '\WPSEOPilot\Service\Redirect_Manager' ) ) {
            \WPSEOPilot\Service\Redirect_Manager::flush_cache();
        }

        // Cleanup from suggestions if exists
        $suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
        $key         = md5( $normalized );
        if ( isset( $suggestions[ $key ] ) ) {
            unset( $suggestions[ $key ] );
            update_option( 'wpseopilot_monitor_slugs', $suggestions );
        }

        return $this->success( [
            'id'          => $wpdb->insert_id,
            'source'      => $normalized,
            'target'      => $target,
            'status_code' => $status_code,
            'hits'        => 0,
            'last_hit'    => null,
        ], __( 'Redirect created successfully.', 'wp-seo-pilot' ) );
    }

    /**
     * Delete a redirect.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response|\WP_Error
     */
    public function delete_redirect( $request ) {
        global $wpdb;

        $id = $request->get_param( 'id' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $deleted = $wpdb->delete(
            $this->redirects_table,
            [ 'id' => $id ],
            [ '%d' ]
        );

        if ( ! $deleted ) {
            return $this->error( __( 'Redirect not found.', 'wp-seo-pilot' ), 'not_found', 404 );
        }

        // Flush cache if class is available
        if ( class_exists( '\WPSEOPilot\Service\Redirect_Manager' ) ) {
            \WPSEOPilot\Service\Redirect_Manager::flush_cache();
        }

        return $this->success( null, __( 'Redirect deleted.', 'wp-seo-pilot' ) );
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
                'items'       => [],
                'total'       => 0,
                'page'        => 1,
                'per_page'    => 50,
                'total_pages' => 1,
            ] );
        }

        $sort        = $request->get_param( 'sort' );
        $per_page    = min( 200, max( 1, (int) $request->get_param( 'per_page' ) ) );
        $page        = max( 1, (int) $request->get_param( 'page' ) );
        $hide_spam   = (bool) $request->get_param( 'hide_spam' );
        $hide_images = (bool) $request->get_param( 'hide_images' );

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

        $where_sql = $filters ? ' WHERE ' . implode( ' AND ', $filters ) : '';

        // Get total count
        $count_sql = "SELECT COUNT(*) FROM {$this->log_table}{$where_sql}";
        if ( $params ) {
            $count_sql = $wpdb->prepare( $count_sql, $params );
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $total_count = (int) $wpdb->get_var( $count_sql );

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
                'device_label'    => ! empty( $row->device_label ) ? $row->device_label : __( 'Unknown device', 'wp-seo-pilot' ),
                'redirect_exists' => ! empty( $row->redirect_exists ),
            ];
        }

        $total_pages = max( 1, (int) ceil( $total_count / $per_page ) );

        return $this->success( [
            'items'       => $data,
            'total'       => $total_count,
            'page'        => $page,
            'per_page'    => $per_page,
            'total_pages' => $total_pages,
        ] );
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
