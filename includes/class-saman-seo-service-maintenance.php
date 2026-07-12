<?php
/**
 * Shared daily maintenance.
 *
 * Prunes the plugin's growing log tables and options so nothing accumulates
 * without bound. This is the single scheduled janitor for the whole plugin;
 * individual services should not need their own retention crons.
 *
 * WP-Cron is visitor-triggered, not guaranteed to fire on time. Everything
 * here is therefore idempotent and safe to run late, early, or twice, and the
 * schedule self-heals on every boot if the event went missing.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Daily maintenance service.
 */
class Maintenance {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	public const HOOK = 'saman_seo_daily_maintenance';

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( self::HOOK, array( $this, 'run' ) );
		$this->maybe_schedule();
	}

	/**
	 * Ensure the daily event is scheduled. Self-healing: re-registers if a
	 * missed/cleared WP-Cron dropped it.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::HOOK );
		}
	}

	/**
	 * Remove the scheduled event (used on deactivation).
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Run all maintenance tasks. Each is wrapped so one failure never blocks
	 * the rest.
	 *
	 * @return void
	 */
	public function run() {
		$tasks = array(
			'prune_404_log',
			'prune_indexnow_log',
			'prune_assistant_usage',
			'prune_link_scans',
			'prune_monitor_slugs',
		);

		foreach ( $tasks as $task ) {
			try {
				$this->{$task}();
			} catch ( \Throwable $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Debug-only.
					error_log( 'Saman SEO maintenance task ' . $task . ' failed: ' . $e->getMessage() );
				}
			}
		}

		/**
		 * Fires after the daily maintenance run completes.
		 *
		 * Lets add-ons hook their own retention work into the shared janitor
		 * instead of registering separate cron events.
		 *
		 * @since 0.2.0
		 */
		saman_seo_do_action( 'saman_seo_daily_maintenance_complete' );
	}

	/**
	 * 404 log: enforce a hard row cap so a bot spraying random URLs can never
	 * blow the table up, regardless of the user's time-based cleanup setting.
	 * Deletes the oldest rows (by last_seen) beyond the cap.
	 *
	 * @return void
	 */
	private function prune_404_log() {
		global $wpdb;

		$table = $wpdb->prefix . 'SAMAN_SEO_404_log';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}

		/**
		 * Filters the hard cap on 404 log rows.
		 *
		 * @since 0.2.0
		 *
		 * @param int $max_rows Maximum rows to retain. Default 10000.
		 */
		$max_rows = (int) saman_seo_apply_filters( 'saman_seo_404_log_max_rows', 10000 );
		if ( $max_rows < 1 ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance count.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		if ( $total <= $max_rows ) {
			return;
		}

		$overflow = $total - $max_rows;

		// Delete the oldest rows beyond the cap. Ordered by last_seen so the
		// most recently active 404s are kept.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name safe.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} ORDER BY last_seen ASC LIMIT %d",
				$overflow
			)
		);
	}

	/**
	 * IndexNow submission log: delete rows older than the retention window.
	 * Reuses the service's own clear_logs() so the delete stays in one place.
	 *
	 * @return void
	 */
	private function prune_indexnow_log() {
		global $wpdb;

		$table = $wpdb->prefix . 'SAMAN_SEO_indexnow_log';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}

		/**
		 * Filters the IndexNow log retention window in days.
		 *
		 * @since 0.2.0
		 *
		 * @param int $days Days to retain. Default 90.
		 */
		$days = (int) saman_seo_apply_filters( 'saman_seo_indexnow_log_retention_days', 90 );
		if ( $days < 1 ) {
			return;
		}

		( new IndexNow() )->clear_logs( $days );
	}

	/**
	 * Assistant usage: delete rows older than the retention window.
	 *
	 * @return void
	 */
	private function prune_assistant_usage() {
		global $wpdb;

		$table = $wpdb->prefix . 'SAMAN_SEO_assistant_usage';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}

		/**
		 * Filters the assistant usage retention window in days.
		 *
		 * @since 0.2.0
		 *
		 * @param int $days Days to retain. Default 365.
		 */
		$days = (int) saman_seo_apply_filters( 'saman_seo_assistant_usage_retention_days', 365 );
		if ( $days < 1 ) {
			return;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name safe.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE created_at < %s",
				$cutoff
			)
		);
	}

	/**
	 * Link scans: keep only the most recent N scan rows, and reap stale scans
	 * left stuck in 'running' by a PHP timeout (which otherwise permanently
	 * block all future scans).
	 *
	 * @return void
	 */
	private function prune_link_scans() {
		global $wpdb;

		$table = $wpdb->prefix . 'SAMAN_SEO_link_scans';
		if ( ! $this->table_exists( $table ) ) {
			return;
		}

		/**
		 * Filters how long (in minutes) a scan may stay 'running' before it is
		 * treated as failed.
		 *
		 * @since 0.2.0
		 *
		 * @param int $minutes Stale threshold in minutes. Default 60.
		 */
		$stale_minutes = (int) saman_seo_apply_filters( 'saman_seo_link_scan_stale_minutes', 60 );
		if ( $stale_minutes > 0 ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$stale_minutes} minutes" ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name safe.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$table} SET status = 'failed', completed_at = %s, error_message = %s WHERE status = 'running' AND started_at < %s",
					current_time( 'mysql' ),
					'Scan timed out and was reaped by daily maintenance.',
					$cutoff
				)
			);
		}

		/**
		 * Filters the maximum number of link scan history rows to retain.
		 *
		 * @since 0.2.0
		 *
		 * @param int $max_rows Maximum rows. Default 50.
		 */
		$max_rows = (int) saman_seo_apply_filters( 'saman_seo_link_scans_max_rows', 50 );
		if ( $max_rows < 1 ) {
			return;
		}

		// Find the id cutoff: the id of the Nth most recent scan. Anything with
		// a smaller id is older and gets pruned.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name safe.
		$threshold_id = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$table} ORDER BY id DESC LIMIT %d, 1",
				$max_rows - 1
			)
		);

		if ( $threshold_id < 1 ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Table name safe.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE id < %d",
				$threshold_id
			)
		);
	}

	/**
	 * Recommended-redirects option: cap the entry count and drop stale entries
	 * so this autoloaded array cannot grow without bound.
	 *
	 * @return void
	 */
	private function prune_monitor_slugs() {
		$slugs = get_option( 'SAMAN_SEO_monitor_slugs', array() );
		if ( ! is_array( $slugs ) || empty( $slugs ) ) {
			return;
		}

		$original_count = count( $slugs );

		/**
		 * Filters how many days a recommended-redirect suggestion is retained.
		 *
		 * @since 0.2.0
		 *
		 * @param int $days Days to retain. Default 90.
		 */
		$days = (int) saman_seo_apply_filters( 'saman_seo_monitor_slugs_retention_days', 90 );
		if ( $days > 0 ) {
			$cutoff = strtotime( "-{$days} days" );
			foreach ( $slugs as $key => $entry ) {
				$date = isset( $entry['date'] ) ? strtotime( $entry['date'] ) : false;
				if ( $date && $date < $cutoff ) {
					unset( $slugs[ $key ] );
				}
			}
		}

		/**
		 * Filters the maximum number of recommended-redirect suggestions kept.
		 *
		 * @since 0.2.0
		 *
		 * @param int $max Maximum entries. Default 100.
		 */
		$max = (int) saman_seo_apply_filters( 'saman_seo_monitor_slugs_max', 100 );
		if ( $max > 0 && count( $slugs ) > $max ) {
			// Keep the newest entries by date.
			uasort(
				$slugs,
				static function ( $a, $b ) {
					$da = isset( $a['date'] ) ? strtotime( $a['date'] ) : 0;
					$db = isset( $b['date'] ) ? strtotime( $b['date'] ) : 0;
					return $db <=> $da;
				}
			);
			$slugs = array_slice( $slugs, 0, $max, true );
		}

		if ( count( $slugs ) !== $original_count ) {
			// Store without autoload so this list stops riding every request.
			update_option( 'SAMAN_SEO_monitor_slugs', $slugs, false );
		}
	}

	/**
	 * Whether a database table exists.
	 *
	 * @param string $table Fully-prefixed table name.
	 * @return bool
	 */
	private function table_exists( $table ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence probe.
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
