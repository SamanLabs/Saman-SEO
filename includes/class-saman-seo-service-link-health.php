<?php
/**
 * Link Health Checker Service.
 *
 * Scans content for broken links, detects orphan pages,
 * and provides link health reports.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Link Health Checker Service.
 */
class Link_Health {

	private const SCHEMA_VERSION = 2;
	private const SCHEMA_OPTION  = 'SAMAN_SEO_link_health_schema';

	/**
	 * Cron hook that processes one scan chunk.
	 *
	 * @var string
	 */
	private const PROCESS_HOOK = 'SAMAN_SEO_link_health_process';

	/**
	 * Cron hook that scans a single saved post.
	 *
	 * @var string
	 */
	private const SINGLE_HOOK = 'SAMAN_SEO_link_health_single';

	/**
	 * Transient name guarding against overlapping chunk runs.
	 *
	 * @var string
	 */
	private const LOCK_TRANSIENT = 'SAMAN_SEO_link_scan_lock';

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
		$this->links_table = $wpdb->prefix . 'SAMAN_SEO_link_health';
		$this->scans_table = $wpdb->prefix . 'SAMAN_SEO_link_scans';
	}

	/**
	 * Boot the service.
	 */
	public function boot() {
		$this->maybe_upgrade_schema();

		// Schedule periodic scans if enabled.
		add_action( 'SAMAN_SEO_link_health_scan', array( $this, 'run_scheduled_scan' ) );
		$this->maybe_schedule_scan();

		// Background chunk processor and single-post scanner.
		add_action( self::PROCESS_HOOK, array( $this, 'process_chunk' ) );
		add_action( self::SINGLE_HOOK, array( $this, 'scan_single_post' ) );

		// Update link data when posts are saved.
		add_action( 'save_post', array( $this, 'on_post_save' ), 20, 2 );
		add_action( 'delete_post', array( $this, 'on_post_delete' ) );
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
			last_post_id bigint(20) unsigned NOT NULL DEFAULT 0,
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
		if ( $this->is_scanning_enabled() ) {
			if ( ! wp_next_scheduled( 'SAMAN_SEO_link_health_scan' ) ) {
				wp_schedule_event( time(), 'weekly', 'SAMAN_SEO_link_health_scan' );
			}
		} else {
			$this->unschedule_scan();
		}
	}

	/**
	 * Whether the user has enabled link health scanning.
	 *
	 * @return bool
	 */
	private function is_scanning_enabled() {
		$settings = get_option( 'SAMAN_SEO_settings', array() );

		return ! empty( $settings['enable_link_health_scan'] );
	}

	/**
	 * Unschedule scan cron.
	 */
	public function unschedule_scan() {
		$timestamp = wp_next_scheduled( 'SAMAN_SEO_link_health_scan' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'SAMAN_SEO_link_health_scan' );
		}
	}

	/**
	 * Run scheduled scan.
	 */
	public function run_scheduled_scan() {
		$this->start_scan( 'full' );
	}

	/**
	 * Post types that scans cover.
	 *
	 * @return string[]
	 */
	private function get_scan_post_types() {
		/**
		 * Filters which post types the link health scanner covers.
		 *
		 * @since 0.2.0
		 *
		 * @param string[] $post_types Post type slugs. Default post, page.
		 */
		$types = saman_seo_apply_filters( 'saman_seo_link_health_post_types', array( 'post', 'page' ) );

		return array_values( array_filter( array_map( 'sanitize_key', (array) $types ) ) );
	}

	/**
	 * Number of posts scanned per chunk.
	 *
	 * @return int
	 */
	private function get_chunk_size() {
		/**
		 * Filters how many posts are scanned per background chunk.
		 *
		 * @since 0.2.0
		 *
		 * @param int $size Posts per chunk. Default 25.
		 */
		$size = (int) saman_seo_apply_filters( 'saman_seo_link_health_chunk_size', 25 );

		return max( 1, $size );
	}

	/**
	 * Reap scans stuck in 'running' past the stale threshold so a scan killed
	 * mid-run can never permanently block future scans.
	 *
	 * @return void
	 */
	private function reap_stale_scans() {
		global $wpdb;

		/**
		 * Filters how long (minutes) a scan may stay 'running' before it is
		 * treated as failed. Shared with the daily maintenance reaper.
		 *
		 * @since 0.2.0
		 *
		 * @param int $minutes Stale threshold in minutes. Default 60.
		 */
		$minutes = (int) saman_seo_apply_filters( 'saman_seo_link_scan_stale_minutes', 60 );
		if ( $minutes < 1 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$minutes} minutes" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->scans_table} SET status = 'failed', completed_at = %s, error_message = %s WHERE status = 'running' AND started_at < %s",
				current_time( 'mysql' ),
				'Scan timed out and was reaped.',
				$cutoff
			)
		);
	}

	/**
	 * Count published posts across scanned post types.
	 *
	 * @return int
	 */
	private function count_scan_posts() {
		$total = 0;
		foreach ( $this->get_scan_post_types() as $type ) {
			$counts = wp_count_posts( $type );
			if ( $counts && isset( $counts->publish ) ) {
				$total += (int) $counts->publish;
			}
		}

		return $total;
	}

	/**
	 * Start a new scan.
	 *
	 * Full and partial scans are processed in background chunks via WP-Cron so
	 * the request that starts them returns immediately, no matter how many
	 * posts the site has. Single scans (one explicit post) run inline.
	 *
	 * @param string $type    Scan type (full, partial, single).
	 * @param int    $post_id Optional post ID for single scan.
	 * @return int|false Scan ID or false on failure.
	 */
	public function start_scan( $type = 'full', $post_id = 0 ) {
		global $wpdb;

		// Clear any scan wedged in 'running' first, then refuse if a genuine
		// scan is still in progress.
		$this->reap_stale_scans();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$running = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$this->scans_table} WHERE status = 'running'"
		);
		if ( $running > 0 ) {
			return false;
		}

		if ( 'single' === $type && $post_id > 0 ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$this->scans_table,
				array(
					'scan_type'    => 'single',
					'status'       => 'running',
					'total_posts'  => 1,
					'started_at'   => current_time( 'mysql' ),
					'last_post_id' => 0,
				),
				array( '%s', '%s', '%d', '%s', '%d' )
			);
			$scan_id = (int) $wpdb->insert_id;

			$links = $this->scan_post_links( $post_id );
			$broken = 0;
			foreach ( $links as $link ) {
				if ( 'broken' === $link['status'] ) {
					++$broken;
				}
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->update(
				$this->scans_table,
				array(
					'status'        => 'completed',
					'scanned_posts' => 1,
					'total_links'   => count( $links ),
					'broken_links'  => $broken,
					'completed_at'  => current_time( 'mysql' ),
				),
				array( 'id' => $scan_id ),
				array( '%s', '%d', '%d', '%d', '%s' ),
				array( '%d' )
			);

			return $scan_id;
		}

		$total = $this->count_scan_posts();
		if ( $total < 1 ) {
			return false;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert(
			$this->scans_table,
			array(
				'scan_type'    => 'full',
				'status'       => 'running',
				'total_posts'  => $total,
				'scanned_posts' => 0,
				'total_links'  => 0,
				'broken_links' => 0,
				'started_at'   => current_time( 'mysql' ),
				'last_post_id' => 0,
			),
			array( '%s', '%s', '%d', '%d', '%d', '%d', '%s', '%d' )
		);

		$scan_id = (int) $wpdb->insert_id;

		// Kick off background processing. The event does the work chunk by
		// chunk; spawn_cron nudges it to start without waiting for traffic.
		$this->schedule_next_chunk( $scan_id );

		return $scan_id;
	}

	/**
	 * Schedule the next background chunk for a scan and nudge WP-Cron.
	 *
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	private function schedule_next_chunk( $scan_id ) {
		if ( ! wp_next_scheduled( self::PROCESS_HOOK, array( $scan_id ) ) ) {
			wp_schedule_single_event( time(), self::PROCESS_HOOK, array( $scan_id ) );
		}

		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}

	/**
	 * Process one chunk of a running scan, then reschedule itself until the
	 * post list is exhausted. Cursor-based (id > last_post_id) so memory stays
	 * bounded to one chunk regardless of site size.
	 *
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	public function process_chunk( $scan_id ) {
		global $wpdb;

		$scan_id = (int) $scan_id;

		// Guard against overlapping runs of the same scan (e.g. a slow chunk
		// while the next cron event already fired).
		$lock = get_transient( self::LOCK_TRANSIENT );
		if ( $lock && (int) $lock !== $scan_id ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, $scan_id, 10 * MINUTE_IN_SECONDS );

		try {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$scan = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$this->scans_table} WHERE id = %d", $scan_id )
			);

			if ( ! $scan || 'running' !== $scan->status ) {
				return;
			}

			$post_types = $this->get_scan_post_types();
			if ( empty( $post_types ) ) {
				$this->complete_scan( $scan_id );
				return;
			}

			$chunk_size   = $this->get_chunk_size();
			$placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
			$params       = array_merge( $post_types, array( (int) $scan->last_post_id, $chunk_size ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) AND ID > %d ORDER BY ID ASC LIMIT %d",
					$params
				)
			);

			if ( empty( $ids ) ) {
				$this->complete_scan( $scan_id );
				return;
			}

			$total_links  = 0;
			$broken_links = 0;
			$last_id      = (int) $scan->last_post_id;

			foreach ( $ids as $post_id ) {
				$post_id = (int) $post_id;
				$links   = $this->scan_post_links( $post_id );
				$total_links += count( $links );
				foreach ( $links as $link ) {
					if ( 'broken' === $link['status'] ) {
						++$broken_links;
					}
				}
				$last_id = $post_id;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->scans_table} SET scanned_posts = scanned_posts + %d, total_links = total_links + %d, broken_links = broken_links + %d, last_post_id = %d WHERE id = %d",
					count( $ids ),
					$total_links,
					$broken_links,
					$last_id,
					$scan_id
				)
			);

			if ( count( $ids ) < $chunk_size ) {
				// Short chunk means we reached the end.
				$this->complete_scan( $scan_id );
			} else {
				delete_transient( self::LOCK_TRANSIENT );
				$this->schedule_next_chunk( $scan_id );
				return;
			}
		} finally {
			delete_transient( self::LOCK_TRANSIENT );
		}
	}

	/**
	 * Mark a scan completed.
	 *
	 * @param int $scan_id Scan ID.
	 * @return void
	 */
	private function complete_scan( $scan_id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			$this->scans_table,
			array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
			),
			array( 'id' => (int) $scan_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Scan a single saved post (deferred cron handler for save_post).
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function scan_single_post( $post_id ) {
		$this->scan_post_links( (int) $post_id );
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
			return array();
		}

		// Extract links from content.
		$links = $this->extract_links_from_content( $post->post_content );

		// Delete old links for this post.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->delete( $this->links_table, array( 'source_post_id' => $post_id ), array( '%d' ) );

		$results = array();
		$now     = current_time( 'mysql' );

		foreach ( $links as $link ) {
			$link_data                   = $this->check_link( $link['url'] );
			$link_data['source_post_id'] = $post_id;
			$link_data['link_text']      = $link['text'];
			$link_data['created_at']     = $now;
			$link_data['last_checked']   = $now;

			// Insert into database.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$wpdb->insert(
				$this->links_table,
				$link_data,
				array( '%d', '%s', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s' )
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
		$links = array();

		// Match all <a> tags.
		if ( preg_match_all( '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$url  = $match[1];
				$text = wp_strip_all_tags( $match[2] );

				// Skip anchors, javascript, mailto, tel links.
				if ( preg_match( '/^(#|javascript:|mailto:|tel:)/i', $url ) ) {
					continue;
				}

				$links[] = array(
					'url'  => $url,
					'text' => mb_substr( $text, 0, 255 ),
				);
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
		$site_url    = home_url();
		$is_internal = strpos( $url, $site_url ) === 0 || strpos( $url, '/' ) === 0;

		$data = array(
			'target_url'     => $url,
			'target_post_id' => null,
			'link_type'      => $is_internal ? 'internal' : 'external',
			'status'         => 'unknown',
			'http_code'      => null,
			'redirect_url'   => null,
			'error_message'  => null,
		);

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
		$result = array(
			'code'         => null,
			'status'       => 'unknown',
			'redirect_url' => null,
			'error'        => null,
		);

		// Refuse to probe URLs that resolve to internal/loopback hosts, so a
		// scan can never be turned into an SSRF against the local network.
		if ( ! wp_http_validate_url( $url ) ) {
			$result['error'] = 'URL failed validation and was not checked.';
			return $result;
		}

		/**
		 * Filters the per-link HTTP timeout (seconds) used during scans.
		 *
		 * @since 0.2.0
		 *
		 * @param int $timeout Timeout in seconds. Default 5.
		 */
		$timeout = (int) saman_seo_apply_filters( 'saman_seo_link_health_timeout', 5 );

		$response = wp_remote_head(
			$url,
			array(
				'timeout'     => max( 1, $timeout ),
				'redirection' => 0,
			)
		);

		if ( is_wp_error( $response ) ) {
			$result['status'] = 'broken';
			$result['error']  = $response->get_error_message();
			return $result;
		}

		$code           = wp_remote_retrieve_response_code( $response );
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

		// Skip non-scanned post types.
		if ( ! in_array( $post->post_type, $this->get_scan_post_types(), true ) ) {
			return;
		}

		// Skip non-published posts.
		if ( 'publish' !== $post->post_status ) {
			return;
		}

		// Only keep per-post link data fresh when scanning is enabled.
		if ( ! $this->is_scanning_enabled() ) {
			return;
		}

		// Defer to cron so the editor save never blocks on outbound HTTP.
		if ( ! wp_next_scheduled( self::SINGLE_HOOK, array( (int) $post_id ) ) ) {
			wp_schedule_single_event( time() + 10, self::SINGLE_HOOK, array( (int) $post_id ) );
		}
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
		$wpdb->delete( $this->links_table, array( 'source_post_id' => $post_id ), array( '%d' ) );

		// Update links pointing to this post.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$this->links_table,
			array(
				'status'         => 'broken',
				'target_post_id' => null,
			),
			array( 'target_post_id' => $post_id ),
			array( '%s', '%d' ),
			array( '%d' )
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

		return array(
			'total_links'  => $total,
			'broken_links' => $broken,
			'redirects'    => $redirects,
			'internal'     => $internal,
			'external'     => $external,
			'orphan_pages' => $orphans,
			'last_scan'    => $last_scan ? $last_scan->completed_at : null,
			'health_score' => $total > 0 ? round( ( ( $total - $broken ) / $total ) * 100 ) : 100,
		);
	}

	/**
	 * Get broken links.
	 *
	 * @param array $args Query arguments.
	 * @return array Links data.
	 */
	public function get_broken_links( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 50,
			'page'     => 1,
			'type'     => '', // internal, external, or empty for all.
		);
		$args     = wp_parse_args( $args, $defaults );

		$where = "WHERE status = 'broken'";
		if ( ! empty( $args['type'] ) ) {
			$where .= $wpdb->prepare( ' AND link_type = %s', $args['type'] );
		}

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->links_table} {$where}" );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT l.*, p.post_title as source_title
			FROM {$this->links_table} l
			LEFT JOIN {$wpdb->posts} p ON l.source_post_id = p.ID
			{$where}
			ORDER BY l.last_checked DESC
			LIMIT %d OFFSET %d",
				$args['per_page'],
				$offset
			)
		);

		return array(
			'items'       => $links,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => max( 1, ceil( $total / $args['per_page'] ) ),
		);
	}

	/**
	 * Get orphan pages (pages with no incoming internal links).
	 *
	 * @param array $args Query arguments.
	 * @return array Orphan pages.
	 */
	public function get_orphan_pages( $args = array() ) {
		global $wpdb;

		$defaults = array(
			'per_page' => 50,
			'page'     => 1,
		);
		$args     = wp_parse_args( $args, $defaults );

		$offset = ( $args['page'] - 1 ) * $args['per_page'];

		// Get all published posts/pages.
		$post_types = "'post', 'page'";

		// Find posts that have no incoming internal links.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$orphans = $wpdb->get_results(
			$wpdb->prepare(
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
			)
		);

		$total = $this->get_orphan_pages_count();

		return array(
			'items'       => $orphans,
			'total'       => $total,
			'page'        => $args['page'],
			'per_page'    => $args['per_page'],
			'total_pages' => max( 1, ceil( $total / $args['per_page'] ) ),
		);
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
		return (bool) $wpdb->delete( $this->links_table, array( 'id' => $link_id ), array( '%d' ) );
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
		$link = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->links_table} WHERE id = %d",
				$link_id
			)
		);

		if ( ! $link ) {
			return false;
		}

		$check = $this->check_link( $link->target_url );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$wpdb->update(
			$this->links_table,
			array(
				'status'        => $check['status'],
				'http_code'     => $check['http_code'],
				'redirect_url'  => $check['redirect_url'],
				'error_message' => $check['error_message'],
				'last_checked'  => current_time( 'mysql' ),
			),
			array( 'id' => $link_id ),
			array( '%s', '%d', '%s', '%s', '%s' ),
			array( '%d' )
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
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$this->scans_table} ORDER BY id DESC LIMIT %d",
				$limit
			),
			ARRAY_A
		);
	}
}
