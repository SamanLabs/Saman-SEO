<?php
/**
 * IndexNow Service.
 *
 * Provides instant URL submission to search engines via the IndexNow protocol.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * IndexNow service class.
 */
class IndexNow {

	/**
	 * Database table name (without prefix).
	 *
	 * @var string
	 */
	private $table_name = 'SAMAN_SEO_indexnow_log';

	/**
	 * Schema version for migrations.
	 *
	 * @var int
	 */
	const SCHEMA_VERSION = 1;

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = [
		'enabled'           => false,
		'api_key'           => '',
		'submit_on_publish' => true,
		'submit_on_update'  => true,
		'post_types'        => [ 'post', 'page' ],
		'search_engine'     => 'api.indexnow.org',
	];

	/**
	 * Available search engine endpoints.
	 *
	 * @var array
	 */
	private $search_engines = [
		'api.indexnow.org'     => 'IndexNow (Bing, Yandex, Seznam)',
		'www.bing.com'         => 'Bing',
		'yandex.com'           => 'Yandex',
		'search.seznam.cz'     => 'Seznam',
		'searchadvisor.naver.com' => 'Naver',
	];

	/**
	 * Boot the service.
	 *
	 * @return void
	 */
	public function boot() {
		$settings = $this->get_settings();

		// Register rewrite rule for key file verification.
		add_action( 'init', [ $this, 'register_rewrite_rules' ] );
		add_filter( 'query_vars', [ $this, 'add_query_vars' ] );
		add_action( 'template_redirect', [ $this, 'serve_key_file' ] );

		// Only register submission hooks if enabled.
		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		if ( ! apply_filters( 'SAMAN_SEO_feature_toggle', true, 'indexnow' ) ) {
			return;
		}

		// Auto-submission hooks.
		if ( ! empty( $settings['submit_on_publish'] ) ) {
			add_action( 'transition_post_status', [ $this, 'maybe_submit_on_status_change' ], 10, 3 );
		}

		if ( ! empty( $settings['submit_on_update'] ) ) {
			add_action( 'post_updated', [ $this, 'maybe_submit_on_update' ], 10, 3 );
		}
	}

	/**
	 * Get IndexNow settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( 'SAMAN_SEO_indexnow_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return wp_parse_args( $settings, $this->defaults );
	}

	/**
	 * Save IndexNow settings.
	 *
	 * @param array $settings Settings to save.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$current = $this->get_settings();

		$sanitized = [
			'enabled'           => ! empty( $settings['enabled'] ),
			'api_key'           => sanitize_text_field( $settings['api_key'] ?? $current['api_key'] ),
			'submit_on_publish' => ! empty( $settings['submit_on_publish'] ),
			'submit_on_update'  => ! empty( $settings['submit_on_update'] ),
			'post_types'        => $this->sanitize_post_types( $settings['post_types'] ?? $current['post_types'] ),
			'search_engine'     => sanitize_text_field( $settings['search_engine'] ?? $current['search_engine'] ),
		];

		// Generate API key if enabled and none exists.
		if ( $sanitized['enabled'] && empty( $sanitized['api_key'] ) ) {
			$sanitized['api_key'] = $this->generate_api_key();
		}

		$result = update_option( 'SAMAN_SEO_indexnow_settings', $sanitized );

		// Flush rewrite rules if key changed.
		if ( $result && $sanitized['api_key'] !== $current['api_key'] ) {
			flush_rewrite_rules();
		}

		return $result;
	}

	/**
	 * Sanitize post types array.
	 *
	 * @param array $post_types Post types.
	 * @return array
	 */
	private function sanitize_post_types( $post_types ) {
		if ( ! is_array( $post_types ) ) {
			return [ 'post', 'page' ];
		}

		$valid_types = get_post_types( [ 'public' => true ] );

		return array_values( array_intersect( $post_types, $valid_types ) );
	}

	/**
	 * Generate a UUID v4 API key.
	 *
	 * @return string
	 */
	public function generate_api_key() {
		return sprintf(
			'%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0x0fff ) | 0x4000,
			wp_rand( 0, 0x3fff ) | 0x8000,
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff ),
			wp_rand( 0, 0xffff )
		);
	}

	/**
	 * Register rewrite rules for key file.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule(
			'^([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})\.txt$',
			'index.php?SAMAN_SEO_indexnow_key=$matches[1]',
			'top'
		);
	}

	/**
	 * Add query vars for key file.
	 *
	 * @param array $vars Query vars.
	 * @return array
	 */
	public function add_query_vars( $vars ) {
		$vars[] = 'SAMAN_SEO_indexnow_key';
		return $vars;
	}

	/**
	 * Serve the API key file for verification.
	 *
	 * @return void
	 */
	public function serve_key_file() {
		$requested_key = get_query_var( 'SAMAN_SEO_indexnow_key' );

		if ( empty( $requested_key ) ) {
			return;
		}

		$settings = $this->get_settings();

		if ( $requested_key === $settings['api_key'] ) {
			header( 'Content-Type: text/plain; charset=utf-8' );
			header( 'X-Robots-Tag: noindex' );
			echo esc_html( $settings['api_key'] );
			exit;
		}

		status_header( 404 );
		exit;
	}

	/**
	 * Handle post status transitions (for publish).
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function maybe_submit_on_status_change( $new_status, $old_status, $post ) {
		// Only submit when transitioning to 'publish' from a non-publish state.
		if ( 'publish' !== $new_status || 'publish' === $old_status ) {
			return;
		}

		$this->maybe_submit_post( $post );
	}

	/**
	 * Handle post updates.
	 *
	 * @param int      $post_id    Post ID.
	 * @param \WP_Post $post_after Post object after update.
	 * @param \WP_Post $post_before Post object before update.
	 * @return void
	 */
	public function maybe_submit_on_update( $post_id, $post_after, $post_before ) {
		// Only submit if post is published.
		if ( 'publish' !== $post_after->post_status ) {
			return;
		}

		// Only submit if post was already published (not new publish).
		if ( 'publish' !== $post_before->post_status ) {
			return;
		}

		// Check if content actually changed.
		$content_changed = (
			$post_after->post_content !== $post_before->post_content ||
			$post_after->post_title !== $post_before->post_title ||
			$post_after->post_name !== $post_before->post_name
		);

		if ( ! $content_changed ) {
			return;
		}

		$this->maybe_submit_post( $post_after );
	}

	/**
	 * Check if post should be submitted and submit it.
	 *
	 * @param \WP_Post $post Post object.
	 * @return bool
	 */
	private function maybe_submit_post( $post ) {
		$settings = $this->get_settings();

		if ( empty( $settings['enabled'] ) || empty( $settings['api_key'] ) ) {
			return false;
		}

		// Check if post type is allowed.
		$allowed_types = $settings['post_types'] ?? [ 'post', 'page' ];
		if ( ! in_array( $post->post_type, $allowed_types, true ) ) {
			return false;
		}

		// Don't submit password-protected posts.
		if ( ! empty( $post->post_password ) ) {
			return false;
		}

		// Allow filtering.
		if ( ! apply_filters( 'SAMAN_SEO_indexnow_should_submit', true, $post ) ) {
			return false;
		}

		$url = get_permalink( $post->ID );

		return $this->submit_url( $url, $post->ID );
	}

	/**
	 * Submit a single URL to IndexNow.
	 *
	 * @param string   $url     URL to submit.
	 * @param int|null $post_id Optional post ID for logging.
	 * @return bool
	 */
	public function submit_url( $url, $post_id = null ) {
		return $this->submit_urls( [ $url ], $post_id );
	}

	/**
	 * Submit multiple URLs to IndexNow.
	 *
	 * @param array    $urls    URLs to submit.
	 * @param int|null $post_id Optional post ID for logging.
	 * @return bool
	 */
	public function submit_urls( $urls, $post_id = null ) {
		$settings = $this->get_settings();

		if ( empty( $settings['enabled'] ) || empty( $settings['api_key'] ) ) {
			return false;
		}

		if ( empty( $urls ) || ! is_array( $urls ) ) {
			return false;
		}

		// Limit to 10,000 URLs per request (IndexNow limit).
		$urls = array_slice( $urls, 0, 10000 );

		$host         = wp_parse_url( home_url(), PHP_URL_HOST );
		$api_key      = $settings['api_key'];
		$search_engine = $settings['search_engine'] ?? 'api.indexnow.org';

		$endpoint = sprintf( 'https://%s/indexnow', $search_engine );

		$body = [
			'host'        => $host,
			'key'         => $api_key,
			'keyLocation' => home_url( $api_key . '.txt' ),
			'urlList'     => array_values( $urls ),
		];

		$response = wp_remote_post(
			$endpoint,
			[
				'headers' => [
					'Content-Type' => 'application/json; charset=utf-8',
				],
				'body'    => wp_json_encode( $body ),
				'timeout' => 15,
			]
		);

		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		if ( is_wp_error( $response ) ) {
			$response_code    = 0;
			$response_message = $response->get_error_message();
		}

		// 200 = OK, 202 = Accepted.
		$success = in_array( $response_code, [ 200, 202 ], true );
		$status  = $success ? 'success' : 'failed';

		// Log each URL.
		foreach ( $urls as $url ) {
			$this->log_submission( $url, $post_id, $status, $response_code, $response_message, $search_engine );
		}

		do_action( 'SAMAN_SEO_indexnow_submitted', $urls, $success, $response_code );

		return $success;
	}

	/**
	 * Log a submission to the database.
	 *
	 * @param string $url              URL submitted.
	 * @param int    $post_id          Post ID (optional).
	 * @param string $status           Status (success, failed, pending).
	 * @param int    $response_code    HTTP response code.
	 * @param string $response_message Response message.
	 * @param string $search_engine    Search engine used.
	 * @return int|false
	 */
	public function log_submission( $url, $post_id, $status, $response_code, $response_message = '', $search_engine = '' ) {
		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		return $wpdb->insert(
			$table,
			[
				'url'              => $url,
				'post_id'          => $post_id,
				'status'           => $status,
				'response_code'    => $response_code,
				'response_message' => substr( $response_message, 0, 255 ),
				'search_engine'    => $search_engine ?: 'api.indexnow.org',
				'submitted_at'     => current_time( 'mysql' ),
			],
			[ '%s', '%d', '%s', '%d', '%s', '%s', '%s' ]
		);
	}

	/**
	 * Get submission logs.
	 *
	 * @param array $args Query arguments.
	 * @return array
	 */
	public function get_logs( $args = [] ) {
		global $wpdb;

		$defaults = [
			'per_page' => 50,
			'page'     => 1,
			'status'   => '',
			'search'   => '',
			'orderby'  => 'submitted_at',
			'order'    => 'DESC',
		];

		$args  = wp_parse_args( $args, $defaults );
		$table = $wpdb->prefix . $this->table_name;

		$where = [];
		$params = [];

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$params[] = $args['status'];
		}

		if ( ! empty( $args['search'] ) ) {
			$where[]  = 'url LIKE %s';
			$params[] = '%' . $wpdb->esc_like( $args['search'] ) . '%';
		}

		$where_sql = ! empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Get total count.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		if ( ! empty( $params ) ) {
			$count_sql = $wpdb->prepare( $count_sql, $params ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		$total = (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		// Get items.
		$orderby   = in_array( $args['orderby'], [ 'submitted_at', 'url', 'status', 'response_code' ], true ) ? $args['orderby'] : 'submitted_at';
		$order     = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';
		$per_page  = absint( $args['per_page'] );
		$offset    = ( absint( $args['page'] ) - 1 ) * $per_page;

		$query = "SELECT * FROM {$table} {$where_sql} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";

		$query_params = array_merge( $params, [ $per_page, $offset ] );
		$items        = $wpdb->get_results( $wpdb->prepare( $query, $query_params ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared

		return [
			'items'      => $items,
			'total'      => $total,
			'pages'      => ceil( $total / $per_page ),
			'page'       => absint( $args['page'] ),
			'per_page'   => $per_page,
		];
	}

	/**
	 * Get submission statistics.
	 *
	 * @return array
	 */
	public function get_stats() {
		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$success = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'success' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$failed  = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", 'failed' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		// Today's submissions.
		$today = (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			"SELECT COUNT(*) FROM {$table} WHERE DATE(submitted_at) = %s",
			current_time( 'Y-m-d' )
		) );

		return [
			'total'        => $total,
			'success'      => $success,
			'failed'       => $failed,
			'today'        => $today,
			'success_rate' => $total > 0 ? round( ( $success / $total ) * 100, 1 ) : 0,
		];
	}

	/**
	 * Clear submission logs.
	 *
	 * @param int $days Optional. Clear logs older than X days. 0 = all.
	 * @return int Number of rows deleted.
	 */
	public function clear_logs( $days = 0 ) {
		global $wpdb;

		$table = $wpdb->prefix . $this->table_name;

		if ( $days > 0 ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
			return $wpdb->query( $wpdb->prepare( "DELETE FROM {$table} WHERE submitted_at < %s", $cutoff ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		}

		return $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	/**
	 * Create database tables.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;

		$table           = $wpdb->prefix . $this->table_name;
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE {$table} (
			id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			url VARCHAR(2048) NOT NULL,
			post_id BIGINT(20) UNSIGNED DEFAULT NULL,
			status VARCHAR(20) NOT NULL DEFAULT 'pending',
			response_code INT DEFAULT NULL,
			response_message VARCHAR(255) DEFAULT '',
			search_engine VARCHAR(50) DEFAULT 'api.indexnow.org',
			submitted_at DATETIME DEFAULT NULL,
			PRIMARY KEY (id),
			KEY post_id (post_id),
			KEY status (status),
			KEY submitted_at (submitted_at)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( 'SAMAN_SEO_indexnow_schema_version', self::SCHEMA_VERSION );
	}

	/**
	 * Get available search engines.
	 *
	 * @return array
	 */
	public function get_search_engines() {
		return $this->search_engines;
	}

	/**
	 * Verify the API key file is accessible.
	 *
	 * @return array
	 */
	public function verify_key_file() {
		$settings = $this->get_settings();

		if ( empty( $settings['api_key'] ) ) {
			return [
				'valid'   => false,
				'message' => __( 'No API key configured.', 'saman-seo' ),
				'url'     => '',
			];
		}

		$key_url  = home_url( $settings['api_key'] . '.txt' );
		$response = wp_remote_get( $key_url, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) ) {
			return [
				'valid'   => false,
				'message' => $response->get_error_message(),
				'url'     => $key_url,
			];
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		$valid = 200 === $code && trim( $body ) === $settings['api_key'];

		return [
			'valid'   => $valid,
			'message' => $valid
				? __( 'Key file is accessible and valid.', 'saman-seo' )
				: __( 'Key file not accessible or content mismatch.', 'saman-seo' ),
			'url'     => $key_url,
		];
	}
}
