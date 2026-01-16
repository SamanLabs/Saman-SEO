<?php
/**
 * Link Health Checker Service.
 *
 * Scans content for broken links, detects orphan pages,
 * and provides link health reports.
 *
 * @package SamanLabs\SEO
 */

namespace SamanLabs\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Link Health Checker Service.
 */
class Link_Health {

	private const SCHEMA_VERSION = 1;
	private const SCHEMA_OPTION  = 'wpseopilot_link_health_schema';

	/**
	 * Links table name.
	 *
	 * @var string
	 */
	private $links_table;

	/**
	 * Scans table name.
	 *
	 * @var string
	 */
	private $scans_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->links_table = $wpdb->prefix . 'wpseopilot_link_health';
		$this->scans_table = $wpdb->prefix . 'wpseopilot_link_scans';
	}

	/**
	 * Boot the service.
	 */
	public function boot() {
		$this->maybe_upgrade_schema();

		// Schedule periodic scans if enabled.
		add_action( 'wpseopilot_link_health_scan', [ $this, 'run_scheduled_scan' ] );
		$this->maybe_schedule_scan();

		// Update link data when posts are saved.
		add_action( 'save_post', [ $this, 'on_post_save' ], 20, 2 );
		add_action( 'delete_post', [ $this, 'on_post_delete' ] );
	}

	/**
	 * Create required tables.
	 */
	public function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();

		// Links table - stores discovered links and their status.
		$links_sql = "CREATE TABLE {$this->links_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source_post_id bigint(20) unsigned NOT NULL,
			target_url varchar(500) NOT NULL,
			target_post_id bigint(20) unsigned DEFAULT NULL,
			link_text varchar(255) DEFAULT '',
			link_type enum('internal','external') NOT NULL DEFAULT 'internal',
			status enum('ok','broken','redirect','timeout','unknown') NOT NULL DEFAULT 'unknown',
			http_code smallint(3) unsigned DEFAULT NULL,
			redirect_url varchar(500) DEFAULT NULL,
			last_checked datetime DEFAULT NULL,
			error_message varchar(255) DEFAULT NULL,
			created_at datetime NOT NULL,
			PRIMARY KEY (id),
			KEY source_post_id (source_post_id),
			KEY target_post_id (target_post_id),
			KEY link_type (link_type),
			KEY status (status),
			KEY target_url (target_url(191))
		) {$charset};";

		dbDelta( $links_sql );

		// Scans table - stores scan history.
		$scans_sql = "CREATE TABLE {$this->scans_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			scan_type enum('full','partial','single') NOT NULL DEFAULT 'full',
			status enum('pending','running','completed','failed') NOT NULL DEFAULT 'pending',
			total_posts int(11) unsigned NOT NULL DEFAULT 0,
			scanned_posts int(11) unsigned NOT NULL DEFAULT 0,
			total_links int(11) unsigned NOT NULL DEFAULT 0,
			broken_links int(11) unsigned NOT NULL DEFAULT 0,
			started_at datetime DEFAULT NULL,
			completed_at datetime DEFAULT NULL,
			error_message text DEFAULT NULL,
			PRIMARY KEY (id),
			KEY status (status)
		) {$charset};";

		dbDelta( $scans_sql );

		update_option( self::SCHEMA_OPTION, self::SCHEMA_VERSION );
	}

	/**
	 * Maybe upgrade schema.
	 */
	private function maybe_upgrade_schema() {
		$current = (int) get_option( self::SCHEMA_OPTION, 0 );
		if ( $current < self::SCHEMA_VERSION ) {
			$this->create_tables();
		}
	}

	/**
	 * Schedule or unschedule scan based on settings.
	 */
	public function maybe_schedule_scan() {
		$settings = get_option( 'wpseopilot_settings', [] );
		$enabled  = isset( $settings['enable_link_health_scan'] ) ? $settings['enable_link_health_scan'] : false;

		if ( $enabled ) {
			if ( ! wp_next_scheduled( 'wpseopilot_link_health_scan' ) ) {
				wp_schedule_event( time(), 'weekly', 'wpseopilot_link_health_scan' );
			}
		} else {
			$this->unschedule_scan();
		}
	}

	/**
	 * Unschedule scan cron.
	 */
	public function unschedule_scan() {
		$timestamp = wp_next_scheduled( 'wpseopilot_link_health_scan' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'wpseopilot_link_health_scan' );
		}
	}

	/**
	 * Run scheduled scan.
	 */
	public function run_scheduled_scan() {
		$this->start_scan( 'full' );
	}

	/**
	 * Start a new scan.
	 *
	 * @param string $type Scan type (full, partial, single).
	 * @param int    $post_id Optional post ID for single scan.
	 * @return int|false Scan ID or false on failure.
	 */
	public function start_scan( $type = 'full', $post_id = 0 ) {
		global $wpdb;

		// Check if a scan is already running.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$running = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->scans_table} WHERE status = 'running'"
		);

		if ( $running > 0 ) {
			return false;
		}

		// Get posts to scan.
		$posts = $this->get_posts_to_scan( $type, $post_id );
		if ( empty( $posts ) ) {
			return false;
		}

		// Create scan record.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->insert(
			$this->scans_table,
			[
				'scan_type'     => $type,
				'status'        => 'running',
				'total_posts'   => count( $posts ),
				'scanned_posts' => 0,
				'total_links'   => 0,
				'broken_links'  => 0,
				'started_at'    => current_time( 'mysql' ),
			],
			[ '%s', '%s', '%d', '%d', '%d', '%d', '%s' ]
		);

		$scan_id = $wpdb->insert_id;

		// Process in batches to avoid timeouts.
		$this->process_scan_batch( $scan_id, $posts );

		return $scan_id;
	}

	/**
	 * Get posts to scan.
	 *
	 * @param string $type    Scan type.
	 * @param int    $post_id Optional post ID.
	 * @return array Post IDs.
	 */
	private function get_posts_to_scan( $type, $post_id = 0 ) {
		$args = [
			'post_type'      => [ 'post', 'page' ],
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		];

		if ( 'single' === $type && $post_id > 0 ) {
			return [ $post_id ];
		}

		return get_posts( $args );
	}

	/**
	 * Process scan batch.
	 *
	 * @param int   $scan_id Scan ID.
	 * @param array $posts   Post IDs to scan.
	 */
	private function process_scan_batch( $scan_id, $posts ) {
		global $wpdb;

		$total_links  = 0;
		$broken_links = 0;

		foreach ( $posts as $post_id ) {
			$links = $this->scan_post_links( $post_id );
			$total_links += count( $links );

			foreach ( $links as $link ) {
				if ( 'broken' === $link['status'] ) {
					$broken_links++;
				}
			}

			// Update progress.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->query( $wpdb->prepare(
				"UPDATE {$this->scans_table} SET scanned_posts = scanned_posts + 1, total_links = %d, broken_links = %d WHERE id = %d",
				$total_links,
				$broken_links,
				$scan_id
			) );
		}

		// Mark scan as completed.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$this->scans_table,
			[
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
			],
			[ 'id' => $scan_id ],
			[ '%s', '%s' ],
			[ '%d' ]
		);
	}

	/**
	 * Scan a single post for links.
	 *
	 * @param int $post_id Post ID.
	 * @return array Found links with status.
	 */
	public function scan_post_links( $post_id ) {
		global $wpdb;

		$post = get_post( $post_id );
		if ( ! $post ) {
			return [];
		}

		// Extract links from content.
		$links = $this->extract_links_from_content( $post->post_content );

		// Delete old links for this post.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->links_table, [ 'source_post_id' => $post_id ], [ '%d' ] );

		$results = [];
		$now     = current_time( 'mysql' );

		foreach ( $links as $link ) {
			$link_data = $this->check_link( $link['url'] );
			$link_data['source_post_id'] = $post_id;
			$link_data['link_text']      = $link['text'];
			$link_data['created_at']     = $now;
			$link_data['last_checked']   = $now;

			// Insert into database.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$this->links_table,
				$link_data,
				[ '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' ]
			);

			$link_data['id'] = $wpdb->insert_id;
			$results[]       = $link_data;
		}

		return $results;
	}

	/**
	 * Extract links from content.
	 *
	 * @param string $content Post content.
	 * @return array Links with URL and text.
	 */
	private function extract_links_from_content( $content ) {
		$links = [];

		// Match all <a> tags.
		if ( preg_match_all( '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$url  = $match[1];
				$text = wp_strip_all_tags( $match[2] );

				// Skip anchors, javascript, mailto, tel links.
				if ( preg_match( '/^(#|javascript:|mailto:|tel:)/i', $url ) ) {
					continue;
				}

				$links[] = [
					'url'  => $url,
					'text' => mb_substr( $text, 0, 255 ),
				];
			}
		}

		return $links;
	}

	/**
	 * Check a single link.
	 *
	 * @param string $url URL to check.
	 * @return array Link data.
	 */
	private function check_link( $url ) {
		$site_url = home_url();
		$is_internal = strpos( $url, $site_url ) === 0 || strpos( $url, '/' ) === 0;

		$data = [
			'target_url'     => $url,
			'target_post_id' => null,
			'link_type'      => $is_internal ? 'internal' : 'external',
			'status'         => 'unknown',
			'http_code'      => null,
			'redirect_url'   => null,
			'error_message'  => null,
		];

		// For internal links, try to find the post.
		if ( $is_internal ) {
			$post_id = url_to_postid( $url );
			if ( $post_id ) {
				$data['target_post_id'] = $post_id;
				$data['status']         = 'ok';
				$data['http_code']      = 200;
				return $data;
			}

			// Check if it's a valid URL that returns 200.
			$full_url = strpos( $url, '/' ) === 0 ? $site_url . $url : $url;
			$response = $this->check_url_status( $full_url );
		} else {
			$response = $this->check_url_status( $url );
		}

		$data['http_code'] = $response['code'];
		$data['status']    = $response['status'];

		if ( ! empty( $response['redirect_url'] ) ) {
			$data['redirect_url'] = $response['redirect_url'];
		}

		if ( ! empty( $response['error'] ) ) {
			$data['error_message'] = $response['error'];
		}

		return $data;
	}

	/**
	 * Check URL status via HTTP request.
	 *
	 * @param string $url URL to check.
	 * @return array Status data.
	 */
	private function check_url_status( $url ) {
		$result = [
			'code'         => null,
			'status'       => 'unknown',
			'redirect_url' => null,
			'error'        => null,
		];

		$response = wp_remote_head( $url, [
			'timeout'     => 10,
			'redirection' => 0,
			'sslverify'   => false,
		] );

		if ( is_wp_error( $response ) ) {
			$result['status'] = 'broken';
			$result['error']  = $response->get_error_message();
			return $result;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$result['code'] = $code;

		if ( $code >= 200 && $code < 300 ) {
			$result['status'] = 'ok';
		} elseif ( $code >= 300 && $code < 400 ) {
			$result['status']       = 'redirect';
			$result['redirect_url'] = wp_remote_retrieve_header( $response, 'location' );
		} elseif ( $code >= 400 ) {
			$result['status'] = 'broken';
		}

		return $result;
	}

	/**
	 * Handle post save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 */
	public function on_post_save( $post_id, $post ) {
		// Skip revisions and autosaves.
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		// Skip non-public post types.
		if ( ! in_array( $post->post_type, [ 'post', 'page' ], true ) ) {
			return;
		}

		// Skip non-published posts.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Scan links in this post (in background if possible).
		$this->scan_post_links( $post_id );
	}

	/**
	 * Handle post delete.
	 *
	 * @param int $post_id Post ID.
	 */
	public function on_post_delete( $post_id ) {
		global $wpdb;

		// Delete links from this post.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->links_table, [ 'source_post_id' => $post_id ], [ '%d' ] );

		// Update links pointing to this post.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$this->links_table,
			[ 'status' => 'broken', 'target_post_id' => null ],
			[ 'target_post_id' => $post_id ],
			[ '%s', '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Get link health summary.
	 *
	 * @return array Summary stats.
	 */
	public function get_summary() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$broken = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} WHERE status = 'broken'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$redirects = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} WHERE status = 'redirect'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$internal = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} WHERE link_type = 'internal'" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$external = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} WHERE link_type = 'external'" );

		// Get orphan pages count.
		$orphans = $this->get_orphan_pages_count();

		// Get last scan.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$last_scan = $wpdb->get_row(
			"SELECT * FROM {$this->scans_table} WHERE status = 'completed' ORDER BY completed_at DESC LIMIT 1"
		);

		return [
			'total_links'   => $total,
			'broken_links'  => $broken,
			'redirects'     => $redirects,
			'internal'      => $internal,
			'external'      => $external,
			'orphan_pages'  => $orphans,
			'last_scan'     => $last_scan ? $last_scan->completed_at : null,
			'health_score'  => $total > 0 ? round( ( ( $total - $broken ) / $total ) * 100 ) : 100,
		];
	}

	/**
	 * Get broken links.
	 *
	 * @param array $args Query arguments.
	 * @return array Links data.
	 */
	public function get_broken_links( $args = [] ) {
		global $wpdb;

		$defaults = [
			'per_page' => 50,
			'page'     => 1,
			'type'     => '', // internal, external, or empty for all.
		];
		$args = wp_parse_args( $args, $defaults );

		$where = "WHERE status = 'broken'";
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( ' AND link_type = %s', $args['type'] );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} {$where}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$links = $wpdb->get_results( $wpdb->prepare(
			"SELECT l.*, p.post_title as source_title
			FROM {$this->links_table} l
			LEFT JOIN {$wpdb->posts} p ON l.source_post_id = p.ID
			{$where}
			ORDER BY l.last_checked DESC
			LIMIT %d OFFSET %d",
			$args['per_page'],
			$offset
		) );

		return [
			'items'       => $links,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => max( 1, ceil( $total / $args['per_page'] ) ),
		];
	}

	/**
	 * Get orphan pages (pages with no incoming internal links).
	 *
	 * @param array $args Query arguments.
	 * @return array Orphan pages.
	 */
	public function get_orphan_pages( $args = [] ) {
		global $wpdb;

		$defaults = [
			'per_page' => 50,
			'page'     => 1,
		];
		$args = wp_parse_args( $args, $defaults );

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Get all published posts/pages.
		$post_types = "'post', 'page'";

		// Find posts that have no incoming internal links.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$orphans = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_title, p.post_type, p.post_date
			FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish'
			AND p.post_type IN ({$post_types})
			AND p.ID NOT IN (
				SELECT DISTINCT target_post_id
				FROM {$this->links_table}
				WHERE target_post_id IS NOT NULL
				AND link_type = 'internal'
			)
			AND p.ID NOT IN (
				SELECT option_value FROM {$wpdb->options} WHERE option_name IN ('page_on_front', 'page_for_posts')
			)
			ORDER BY p.post_date DESC
			LIMIT %d OFFSET %d",
			$args['per_page'],
			$offset
		) );

		$total = $this->get_orphan_pages_count();

		return [
			'items'       => $orphans,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => max( 1, ceil( $total / $args['per_page'] ) ),
		];
	}

	/**
	 * Get orphan pages count.
	 *
	 * @return int Count.
	 */
	private function get_orphan_pages_count() {
		global $wpdb;

		$post_types = "'post', 'page'";

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (int) $wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$wpdb->posts} p
			WHERE p.post_status = 'publish'
			AND p.post_type IN ({$post_types})
			AND p.ID NOT IN (
				SELECT DISTINCT target_post_id
				FROM {$this->links_table}
				WHERE target_post_id IS NOT NULL
				AND link_type = 'internal'
			)
			AND p.ID NOT IN (
				SELECT option_value FROM {$wpdb->options} WHERE option_name IN ('page_on_front', 'page_for_posts')
			)"
		);
	}

	/**
	 * Delete a broken link entry.
	 *
	 * @param int $link_id Link ID.
	 * @return bool Success.
	 */
	public function delete_link( $link_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->delete( $this->links_table, [ 'id' => $link_id ], [ '%d' ] );
	}

	/**
	 * Recheck a link.
	 *
	 * @param int $link_id Link ID.
	 * @return array|false Updated link data or false.
	 */
	public function recheck_link( $link_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$link = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$this->links_table} WHERE id = %d",
			$link_id
		) );

		if ( ! $link ) {
			return false;
		}

		$check = $this->check_link( $link->target_url );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$this->links_table,
			[
				'status'        => $check['status'],
				'http_code'     => $check['http_code'],
				'redirect_url'  => $check['redirect_url'],
				'error_message' => $check['error_message'],
				'last_checked'  => current_time( 'mysql' ),
			],
			[ 'id' => $link_id ],
			[ '%s', '%d', '%s', '%s', '%s' ],
			[ '%d' ]
		);

		return array_merge( (array) $link, $check );
	}

	/**
	 * Get current scan status.
	 *
	 * @return array|null Scan data or null.
	 */
	public function get_current_scan() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_row(
			"SELECT * FROM {$this->scans_table} WHERE status IN ('pending', 'running') ORDER BY id DESC LIMIT 1",
			ARRAY_A
		);
	}

	/**
	 * Get scan history.
	 *
	 * @param int $limit Number of scans.
	 * @return array Scans.
	 */
	public function get_scan_history( $limit = 10 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( $wpdb->prepare(
			"SELECT * FROM {$this->scans_table} ORDER BY id DESC LIMIT %d",
			$limit
		), ARRAY_A );
	}
}
