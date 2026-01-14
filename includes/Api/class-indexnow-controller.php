<?php
/**
 * REST API Controller for IndexNow.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Api;

use WPSEOPilot\Plugin;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;

defined( 'ABSPATH' ) || exit;

/**
 * IndexNow REST API Controller.
 */
class IndexNow_Controller extends REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Settings endpoints.
		register_rest_route(
			$this->namespace,
			'/indexnow/settings',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_settings' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'save_settings' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		// Generate new API key.
		register_rest_route(
			$this->namespace,
			'/indexnow/generate-key',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'generate_key' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		// Verify key file.
		register_rest_route(
			$this->namespace,
			'/indexnow/verify-key',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'verify_key' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		// Submit URL(s).
		register_rest_route(
			$this->namespace,
			'/indexnow/submit',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'submit_urls' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'urls' => [
							'required'          => true,
							'type'              => 'array',
							'sanitize_callback' => function ( $urls ) {
								return array_map( 'esc_url_raw', (array) $urls );
							},
						],
					],
				],
			]
		);

		// Get submission logs.
		register_rest_route(
			$this->namespace,
			'/indexnow/logs',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_logs' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'page'     => [
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page' => [
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						],
						'status'   => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'search'   => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'clear_logs' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'days' => [
							'type'              => 'integer',
							'default'           => 0,
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Get stats.
		register_rest_route(
			$this->namespace,
			'/indexnow/stats',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_stats' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		// Get options (search engines, post types).
		register_rest_route(
			$this->namespace,
			'/indexnow/options',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_options' ],
					'permission_callback' => [ $this, 'permission_check' ],
				],
			]
		);

		// Submit a single post by ID.
		register_rest_route(
			$this->namespace,
			'/indexnow/submit-post/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'submit_post' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Get indexing status for a single post.
		register_rest_route(
			$this->namespace,
			'/indexnow/post-status/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_post_status' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'id' => [
							'required'          => true,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						],
					],
				],
			]
		);

		// Bulk submit multiple posts by IDs.
		register_rest_route(
			$this->namespace,
			'/indexnow/bulk-submit',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'bulk_submit_posts' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'post_ids' => [
							'required'          => true,
							'type'              => 'array',
							'sanitize_callback' => function ( $ids ) {
								return array_map( 'absint', (array) $ids );
							},
						],
					],
				],
			]
		);

		// Get posts available for bulk indexing.
		register_rest_route(
			$this->namespace,
			'/indexnow/posts',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_posts_for_indexing' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => [
						'page'      => [
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						],
						'per_page'  => [
							'type'              => 'integer',
							'default'           => 50,
							'sanitize_callback' => 'absint',
						],
						'post_type' => [
							'type'              => 'string',
							'default'           => 'post',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'search'    => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
						'status_filter' => [
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_text_field',
						],
					],
				],
			]
		);
	}

	/**
	 * Get IndexNow service.
	 *
	 * @return \WPSEOPilot\Service\IndexNow|null
	 */
	private function get_service() {
		return Plugin::instance()->get( 'indexnow' );
	}

	/**
	 * Get IndexNow settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$settings = $service->get_settings();

		// Mask API key for display (show first 8 and last 4 chars).
		if ( ! empty( $settings['api_key'] ) ) {
			$key                     = $settings['api_key'];
			$settings['api_key_display'] = substr( $key, 0, 8 ) . '...' . substr( $key, -4 );
		}

		return $this->success( $settings );
	}

	/**
	 * Save IndexNow settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_settings( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$settings = $request->get_json_params();

		if ( empty( $settings ) ) {
			$settings = $request->get_params();
		}

		$result = $service->save_settings( $settings );

		if ( $result ) {
			return $this->success(
				$service->get_settings(),
				__( 'IndexNow settings saved successfully.', 'wp-seo-pilot' )
			);
		}

		return $this->error( __( 'Failed to save settings.', 'wp-seo-pilot' ) );
	}

	/**
	 * Generate a new API key.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function generate_key( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$settings            = $service->get_settings();
		$settings['api_key'] = $service->generate_api_key();

		$service->save_settings( $settings );

		// Flush rewrite rules to ensure new key file route works.
		flush_rewrite_rules();

		return $this->success(
			[
				'api_key'     => $settings['api_key'],
				'key_file_url' => home_url( $settings['api_key'] . '.txt' ),
			],
			__( 'New API key generated successfully.', 'wp-seo-pilot' )
		);
	}

	/**
	 * Verify the API key file is accessible.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function verify_key( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$result = $service->verify_key_file();

		return $this->success( $result );
	}

	/**
	 * Submit URLs to IndexNow.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function submit_urls( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$urls = $request->get_param( 'urls' );

		if ( empty( $urls ) ) {
			return $this->error( __( 'No URLs provided.', 'wp-seo-pilot' ) );
		}

		// Filter out invalid URLs.
		$urls = array_filter( $urls, function ( $url ) {
			return filter_var( $url, FILTER_VALIDATE_URL );
		} );

		if ( empty( $urls ) ) {
			return $this->error( __( 'No valid URLs provided.', 'wp-seo-pilot' ) );
		}

		$settings = $service->get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return $this->error( __( 'No API key configured. Please generate an API key first.', 'wp-seo-pilot' ) );
		}

		$success = $service->submit_urls( $urls );

		if ( $success ) {
			return $this->success(
				[
					'submitted' => count( $urls ),
					'urls'      => $urls,
				],
				sprintf(
					/* translators: %d: number of URLs */
					_n(
						'%d URL submitted successfully.',
						'%d URLs submitted successfully.',
						count( $urls ),
						'wp-seo-pilot'
					),
					count( $urls )
				)
			);
		}

		return $this->error( __( 'Failed to submit URLs. Check the submission logs for details.', 'wp-seo-pilot' ) );
	}

	/**
	 * Get submission logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_logs( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$logs = $service->get_logs( [
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
			'status'   => $request->get_param( 'status' ),
			'search'   => $request->get_param( 'search' ),
		] );

		return $this->success( $logs );
	}

	/**
	 * Clear submission logs.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function clear_logs( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$days    = $request->get_param( 'days' );
		$deleted = $service->clear_logs( $days );

		return $this->success(
			[ 'deleted' => $deleted ],
			sprintf(
				/* translators: %d: number of entries */
				_n(
					'%d log entry cleared.',
					'%d log entries cleared.',
					$deleted,
					'wp-seo-pilot'
				),
				$deleted
			)
		);
	}

	/**
	 * Get submission statistics.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_stats( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		return $this->success( $service->get_stats() );
	}

	/**
	 * Get IndexNow options (search engines, post types).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_options( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		// Get public post types.
		$post_types       = [];
		$public_post_types = get_post_types( [ 'public' => true ], 'objects' );
		foreach ( $public_post_types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			$post_types[] = [
				'name'  => $pt->name,
				'label' => $pt->labels->name,
			];
		}

		return $this->success( [
			'search_engines' => $service->get_search_engines(),
			'post_types'     => $post_types,
		] );
	}

	/**
	 * Submit a single post to IndexNow.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function submit_post( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error( __( 'Post not found.', 'wp-seo-pilot' ), 'not_found', 404 );
		}

		if ( 'publish' !== $post->post_status ) {
			return $this->error( __( 'Only published posts can be submitted.', 'wp-seo-pilot' ) );
		}

		$settings = $service->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return $this->error( __( 'IndexNow is not enabled. Enable it in Settings first.', 'wp-seo-pilot' ) );
		}

		if ( empty( $settings['api_key'] ) ) {
			return $this->error( __( 'No API key configured. Generate one in Settings first.', 'wp-seo-pilot' ) );
		}

		$url     = get_permalink( $post_id );
		$success = $service->submit_url( $url, $post_id );

		if ( $success ) {
			return $this->success(
				[
					'post_id' => $post_id,
					'url'     => $url,
					'status'  => 'submitted',
				],
				__( 'URL submitted for indexing successfully.', 'wp-seo-pilot' )
			);
		}

		return $this->error( __( 'Failed to submit URL. Check the logs for details.', 'wp-seo-pilot' ) );
	}

	/**
	 * Get indexing status for a single post.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_post_status( $request ) {
		global $wpdb;

		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$post_id = $request->get_param( 'id' );
		$post    = get_post( $post_id );

		if ( ! $post ) {
			return $this->error( __( 'Post not found.', 'wp-seo-pilot' ), 'not_found', 404 );
		}

		$table = $wpdb->prefix . 'wpseopilot_indexnow_log';

		// Get the most recent submission for this post.
		$latest = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE post_id = %d ORDER BY submitted_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$post_id
		) );

		// Get total submissions for this post.
		$total_submissions = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM {$table} WHERE post_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$post_id
		) );

		$settings = $service->get_settings();
		$enabled  = ! empty( $settings['enabled'] ) && ! empty( $settings['api_key'] );

		return $this->success( [
			'post_id'           => $post_id,
			'url'               => get_permalink( $post_id ),
			'indexnow_enabled'  => $enabled,
			'has_been_indexed'  => ! empty( $latest ),
			'last_submission'   => $latest ? [
				'status'           => $latest->status,
				'response_code'    => (int) $latest->response_code,
				'submitted_at'     => $latest->submitted_at,
				'search_engine'    => $latest->search_engine,
				'time_ago'         => human_time_diff( strtotime( $latest->submitted_at ) ) . ' ago',
			] : null,
			'total_submissions' => (int) $total_submissions,
		] );
	}

	/**
	 * Bulk submit multiple posts to IndexNow.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function bulk_submit_posts( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$settings = $service->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return $this->error( __( 'IndexNow is not enabled. Enable it in Settings first.', 'wp-seo-pilot' ) );
		}

		if ( empty( $settings['api_key'] ) ) {
			return $this->error( __( 'No API key configured. Generate one in Settings first.', 'wp-seo-pilot' ) );
		}

		$post_ids = $request->get_param( 'post_ids' );

		if ( empty( $post_ids ) ) {
			return $this->error( __( 'No posts selected.', 'wp-seo-pilot' ) );
		}

		// Limit to 100 posts at a time.
		$post_ids = array_slice( $post_ids, 0, 100 );

		$urls       = [];
		$skipped    = 0;
		$post_id_map = [];

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( $post && 'publish' === $post->post_status ) {
				$url              = get_permalink( $post_id );
				$urls[]           = $url;
				$post_id_map[$url] = $post_id;
			} else {
				$skipped++;
			}
		}

		if ( empty( $urls ) ) {
			return $this->error( __( 'No valid published posts to submit.', 'wp-seo-pilot' ) );
		}

		// Submit in batches (IndexNow accepts up to 10,000 but we'll batch for better UX).
		$success_count = 0;
		$failed_count  = 0;

		foreach ( array_chunk( $urls, 100 ) as $batch ) {
			$success = $service->submit_urls( $batch );
			if ( $success ) {
				$success_count += count( $batch );
			} else {
				$failed_count += count( $batch );
			}
		}

		return $this->success(
			[
				'submitted' => $success_count,
				'failed'    => $failed_count,
				'skipped'   => $skipped,
				'total'     => count( $post_ids ),
			],
			sprintf(
				/* translators: %d: number of URLs */
				__( '%d URLs submitted for indexing.', 'wp-seo-pilot' ),
				$success_count
			)
		);
	}

	/**
	 * Get posts available for bulk indexing with their status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_posts_for_indexing( $request ) {
		global $wpdb;

		$service = $this->get_service();

		if ( ! $service ) {
			return $this->error( __( 'IndexNow service not available.', 'wp-seo-pilot' ) );
		}

		$page          = $request->get_param( 'page' );
		$per_page      = min( $request->get_param( 'per_page' ), 100 );
		$post_type     = $request->get_param( 'post_type' );
		$search        = $request->get_param( 'search' );
		$status_filter = $request->get_param( 'status_filter' );

		// Build query args.
		$args = [
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		];

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );
		$posts = [];

		$log_table = $wpdb->prefix . 'wpseopilot_indexnow_log';

		foreach ( $query->posts as $post ) {
			// Get latest submission for this post.
			$latest = $wpdb->get_row( $wpdb->prepare(
				"SELECT status, submitted_at FROM {$log_table} WHERE post_id = %d ORDER BY submitted_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$post->ID
			) );

			$indexing_status = 'never';
			$last_indexed    = null;

			if ( $latest ) {
				$indexing_status = $latest->status;
				$last_indexed    = $latest->submitted_at;
			}

			// Apply status filter.
			if ( ! empty( $status_filter ) ) {
				if ( 'never' === $status_filter && $latest ) {
					continue;
				}
				if ( 'indexed' === $status_filter && ( ! $latest || 'success' !== $latest->status ) ) {
					continue;
				}
				if ( 'failed' === $status_filter && ( ! $latest || 'failed' !== $latest->status ) ) {
					continue;
				}
			}

			$posts[] = [
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'url'             => get_permalink( $post->ID ),
				'post_type'       => $post->post_type,
				'date'            => $post->post_date,
				'indexing_status' => $indexing_status,
				'last_indexed'    => $last_indexed,
				'last_indexed_ago' => $last_indexed ? human_time_diff( strtotime( $last_indexed ) ) . ' ago' : null,
			];
		}

		// Get stats for header.
		$total_published = wp_count_posts( $post_type )->publish;
		$total_indexed   = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT post_id) FROM {$log_table} WHERE status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			'success'
		) );

		return $this->success( [
			'posts'       => $posts,
			'total'       => $query->found_posts,
			'pages'       => $query->max_num_pages,
			'page'        => $page,
			'per_page'    => $per_page,
			'stats'       => [
				'total_published' => (int) $total_published,
				'total_indexed'   => (int) $total_indexed,
			],
		] );
	}
}
