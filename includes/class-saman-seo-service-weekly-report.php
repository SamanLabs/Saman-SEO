<?php
/**
 * Scheduled weekly SEO digest email.
 *
 * Collects the site's key SEO movements for the trailing seven days —
 * performance deltas, crawl errors, redirect usage, link health, and Search
 * Console traffic when connected — and mails a compact HTML digest.
 *
 * Follows the Maintenance service philosophy: WP-Cron is visitor-triggered,
 * so everything here is idempotent, self-healing on boot, and safe to fire
 * late or twice.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Weekly report service.
 */
class Weekly_Report {

	/**
	 * Cron hook name.
	 *
	 * @var string
	 */
	public const HOOK = 'saman_seo_weekly_report';

	/**
	 * Option: digest enabled ('1'|'0').
	 *
	 * @var string
	 */
	public const OPTION_ENABLED = 'SAMAN_SEO_weekly_report_enabled';

	/**
	 * Option: recipient email. Empty means site admin email.
	 *
	 * @var string
	 */
	public const OPTION_EMAIL = 'SAMAN_SEO_weekly_report_email';

	/**
	 * Option: delivery weekday (monday..sunday).
	 *
	 * @var string
	 */
	public const OPTION_DAY = 'SAMAN_SEO_weekly_report_day';

	/**
	 * Option: unix timestamp of the last successful send.
	 *
	 * @var int
	 */
	public const OPTION_LAST_SENT = 'SAMAN_SEO_weekly_report_last_sent';

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
	 * Ensure the scheduled event matches current settings.
	 *
	 * Self-healing: re-registers if WP-Cron dropped the event, and reschedules
	 * whenever the configured weekday changes.
	 *
	 * @return void
	 */
	public function maybe_schedule() {
		if ( '1' !== (string) get_option( self::OPTION_ENABLED, '0' ) ) {
			self::unschedule();
			return;
		}

		$next    = wp_next_scheduled( self::HOOK );
		$desired = self::next_run_timestamp( (string) get_option( self::OPTION_DAY, 'monday' ) );

		if ( ! $next ) {
			wp_schedule_event( $desired, 'weekly', self::HOOK );
			return;
		}

		// Same target weekday within ±36h? Leave it alone; otherwise reschedule.
		if ( abs( $next - $desired ) > 36 * HOUR_IN_SECONDS ) {
			self::unschedule();
			wp_schedule_event( $desired, 'weekly', self::HOOK );
		}
	}

	/**
	 * Remove the scheduled event.
	 *
	 * @return void
	 */
	public static function unschedule() {
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Timestamp of the next occurrence of the given weekday at 09:00 site time.
	 *
	 * @param string $day Lowercase weekday name (monday..sunday).
	 *
	 * @return int Unix timestamp.
	 */
	public static function next_run_timestamp( $day ) {
		$map = array(
			'sunday'    => 0,
			'monday'    => 1,
			'tuesday'   => 2,
			'wednesday' => 3,
			'thursday'  => 4,
			'friday'    => 5,
			'saturday'  => 6,
		);

		$target    = $map[ strtolower( (string) $day ) ] ?? 1;
		$now       = current_time( 'timestamp' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested -- Site-relative scheduling is intentional for digests.
		$today_dow = (int) current_time( 'w' );

		$days_ahead = ( $target - $today_dow + 7 ) % 7;
		if ( 0 === $days_ahead ) {
			// Today already past 09:00? Ship next week.
			$days_ahead = (int) current_time( 'H' ) >= 9 ? 7 : 0;
		}

		return strtotime( "+{$days_ahead} days", mktime( 9, 0, 0, (int) current_time( 'n' ), (int) current_time( 'j' ), (int) current_time( 'Y' ) ) );
	}

	/*
	---------------------------------------------------------------------
	 * Digest generation
	 * ------------------------------------------------------------------- */

	/**
	 * Cron entry point: gather, compose, deliver.
	 *
	 * @return bool Whether an email attempt was made.
	 */
	public function run() {
		return $this->send_digest();
	}

	/**
	 * Build and send the digest now (also used for preview/test sends).
	 *
	 * @param string|null $override_recipient Send to this address instead of the stored one.
	 *
	 * @return true|\WP_Error
	 */
	public function send_digest( $override_recipient = null ) {
		$recipient = '' !== (string) $override_recipient
			? $override_recipient
			: $this->get_recipient();

		if ( ! is_email( $recipient ) ) {
			return new \WP_Error( 'saman_seo_report_no_recipient', __( 'No valid recipient email address is configured.', 'saman-seo' ) );
		}

		$metrics = $this->get_metrics();

		$subject = sprintf(
			/* translators: %s: site name */
			__( '[%s] Your weekly SEO digest', 'saman-seo' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES )
		);

		$html    = $this->build_email_html( $metrics );
		$headers = array( 'Content-Type: text/html; charset=UTF-8' );

		$sent = wp_mail( $recipient, $subject, $html, $headers );

		if ( ! $sent ) {
			return new \WP_Error( 'saman_seo_report_send_failed', __( 'WordPress could not send the email.', 'saman-seo' ) );
		}

		if ( null === $override_recipient ) {
			update_option( self::OPTION_LAST_SENT, time(), false );
		}

		return true;
	}

	/**
	 * Resolve delivery recipient.
	 *
	 * @return string
	 */
	public function get_recipient() {
		$email = trim( (string) get_option( self::OPTION_EMAIL, '' ) );

		return '' !== $email ? $email : (string) get_option( 'admin_email', '' );
	}

	/**
	 * Gather all digest metrics. Each collector fails soft so one broken
	 * subsystem never blocks the whole email.
	 *
	 * @return array<string,mixed>
	 */
	public function get_metrics() {
		$metrics = array(
			'range_label'   => sprintf(
				'%s – %s',
				gmdate( 'M j', strtotime( '-7 days' ) ),
				gmdate( 'M j', strtotime( '-1 day' ) )
			),
			'seo_score'     => $this->collect_score_trend(),
			'errors_404'    => $this->collect_404_count(),
			'redirect_hits' => $this->collect_redirect_hits(),
			'link_health'   => $this->collect_link_health(),
			'new_content'   => $this->collect_new_content(),
			'gsc'           => $this->collect_search_console(),
		);

		/**
		 * Filters the metrics collected for the weekly digest.
		 *
		 * @since 2.1.0
		 *
		 * @param array $metrics Metric buckets keyed by section id.
		 */
		return saman_seo_apply_filters( 'saman_seo_weekly_report_metrics', $metrics );
	}

	/**
	 * Average SEO score this week vs previous week from the dashboard history.
	 *
	 * @return array<string,float|int>|null
	 */
	private function collect_score_trend() {
		$history = get_option( 'SAMAN_SEO_score_history', array() );

		if ( ! is_array( $history ) || empty( $history ) ) {
			return null;
		}

		$current  = array();
		$previous = array();

		foreach ( $history as $date => $score ) {
			$ts = strtotime( (string) $date );

			if ( false === $ts ) {
				continue;
			}

			if ( $ts >= strtotime( '-7 days' ) ) {
				$current[] = (float) $score;
			} elseif ( $ts >= strtotime( '-14 days' ) ) {
				$previous[] = (float) $score;
			}
		}

		if ( empty( $current ) && empty( $previous ) ) {
			return null;
		}

		$avg_current  = empty( $current ) ? null : round( array_sum( $current ) / count( $current ) );
		$avg_previous = empty( $previous ) ? null : round( array_sum( $previous ) / count( $previous ) );

		$change = ( null === $avg_current || null === $avg_previous ) ? 0 : ( $avg_current - $avg_previous );

		return array(
			'current'  => $avg_current,
			'previous' => $avg_previous,
			'change'   => (int) round( $change ),
		);
	}

	/**
	 * 404 hits over the trailing seven days.
	 *
	 * @return int|null Null when logging is unavailable.
	 */
	private function collect_404_count() {
		global $wpdb;

		if ( ! \Saman\SEO\Helpers\module_enabled( '404_log' ) ) {
			return null;
		}

		$table = $wpdb->prefix . 'SAMAN_SEO_404_log';
		if ( ! $this->table_exists( $table ) ) {
			return null;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate read from custom table.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE last_seen >= %s",
				$cutoff
			)
		);
	}

	/**
	 * Redirect hits over the trailing seven days.
	 *
	 * @return int|null
	 */
	private function collect_redirect_hits() {
		global $wpdb;

		if ( ! \Saman\SEO\Helpers\module_enabled( 'redirects' ) ) {
			return null;
		}

		$table = $wpdb->prefix . 'SAMAN_SEO_redirects';
		if ( ! $this->table_exists( $table ) ) {
			return null;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate read from custom table.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COALESCE(SUM(hits), 0) FROM {$table} WHERE last_hit IS NOT NULL AND last_hit >= %s",
				$cutoff
			)
		);
	}

	/**
	 * Latest completed link scan summary.
	 *
	 * @return array<string,int>|null
	 */
	private function collect_link_health() {
		global $wpdb;

		$table = $wpdb->prefix . 'SAMAN_SEO_link_scans';
		if ( ! $this->table_exists( $table ) ) {
			return null;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Single-row read from custom table.
		$row = $wpdb->get_row(
			"SELECT total_links, broken_links, completed_at FROM {$table} WHERE status = 'completed' ORDER BY id DESC LIMIT 1",
			ARRAY_A
		);

		if ( ! $row ) {
			return null;
		}

		return array(
			'total_links'  => (int) $row['total_links'],
			'broken_links' => (int) $row['broken_links'],
			'completed_at' => (string) $row['completed_at'],
		);
	}

	/**
	 * Number of posts published in the trailing seven days.
	 *
	 * @return int
	 */
	private function collect_new_content() {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Lightweight aggregate read; avoids booting WP_Query inside cron.
		return (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ( 'post', 'page' ) AND post_date >= %s",
				$cutoff
			)
		);
	}

	/**
	 * Search Console week-over-week movement, when connected.
	 *
	 * @return array<string,mixed>|null
	 */
	private function collect_search_console() {
		$plugin = \Saman\SEO\Plugin::instance();
		$gsc    = $plugin->get( 'search_console' );

		if ( ! $gsc instanceof Search_Console || ! $gsc->is_connected() ) {
			return null;
		}

		$deltas = $gsc->get_weekly_deltas();

		if ( is_wp_error( $deltas ) ) {
			return array(
				'error' => $deltas->get_error_message(),
			);
		}

		return $deltas;
	}

	/*
	---------------------------------------------------------------------
	 * Email composition
	 * ------------------------------------------------------------------- */

	/**
	 * Compose the HTML digest.
	 *
	 * @param array $metrics Metrics from get_metrics().
	 *
	 * @return string HTML email body.
	 */
	public function build_email_html( array $metrics ) {
		$rows = array();

		$seo = $metrics['seo_score'] ?? null;

		if ( $seo ) {
			$direction = 'stable';
			if ( $seo['change'] > 0 ) {
				$direction = 'up';
			} elseif ( $seo['change'] < 0 ) {
				$direction = 'down';
			}

			$rows[] = $this->metric_row(
				__( 'Avg. SEO score', 'saman-seo' ),
				null !== $seo['current']
					? sprintf( '%d/100', $seo['current'] )
					: __( 'Not scored yet', 'saman-seo' ),
				$this->delta_badge( $seo['change'], true ),
				$direction
			);
		}

		$gsc = $metrics['gsc'] ?? null;

		if ( is_array( $gsc ) && isset( $gsc['current'] ) ) {
			$click_delta       = (int) $gsc['current']['clicks'] - (int) $gsc['previous']['clicks'];
			$impressions_delta = (int) $gsc['current']['impressions'] - (int) $gsc['previous']['impressions'];

			$rows[] = $this->metric_row(
				__( 'Search clicks (7 days)', 'saman-seo' ),
				number_format_i18n( $gsc['current']['clicks'] ),
				$this->delta_badge( $click_delta, true ),
				$click_delta > 0 ? 'up' : ( $click_delta < 0 ? 'down' : 'stable' )
			);

			$rows[] = $this->metric_row(
				__( 'Impressions (7 days)', 'saman-seo' ),
				number_format_i18n( $gsc['current']['impressions'] ),
				$this->delta_badge( $impressions_delta, true ),
				$impressions_delta > 0 ? 'up' : ( $impressions_delta < 0 ? 'down' : 'stable' )
			);
		}

		$new_content = $metrics['new_content'] ?? 0;
		$rows[]      = $this->metric_row(
			__( 'New pages published', 'saman-seo' ),
			number_format_i18n( $new_content ),
			'',
			'stable'
		);

		if ( null !== ( $metrics['errors_404'] ?? null ) ) {
			$count_404 = (int) $metrics['errors_404'];
			$rows[]    = $this->metric_row(
				__( '404 errors (7 days)', 'saman-seo' ),
				number_format_i18n( $count_404 ),
				$count_404 > 20 ? __( 'needs attention', 'saman-seo' ) : '',
				$count_404 > 20 ? 'down' : 'stable'
			);
		}

		if ( null !== ( $metrics['redirect_hits'] ?? null ) ) {
			$rows[] = $this->metric_row(
				__( 'Redirect hits (7 days)', 'saman-seo' ),
				number_format_i18n( (int) $metrics['redirect_hits'] ),
				'',
				'stable'
			);
		}

		$link_health = $metrics['link_health'] ?? null;

		if ( $link_health ) {
			$broken = (int) $link_health['broken_links'];
			$rows[] = $this->metric_row(
				__( 'Broken links (last scan)', 'saman-seo' ),
				sprintf(
					/* translators: 1: broken count, 2: total links */
					__( '%1$s of %2$s links', 'saman-seo' ),
					number_format_i18n( $broken ),
					number_format_i18n( (int) $link_health['total_links'] )
				),
				$broken > 0 ? __( 'worth fixing', 'saman-seo' ) : __( 'all healthy', 'saman-seo' ),
				$broken > 0 ? 'down' : 'stable'
			);
		}

		$top_queries_html = '';

		$top_queries = is_array( $gsc['top_queries'] ?? null ) ? array_slice( $gsc['top_queries'], 0, 5 ) : array();

		foreach ( $top_queries as $index => $query_row ) {
			$position = (string) $query_row['query'];

			$top_queries_html .= '<tr>'
				. '<td style="padding:6px 10px;border-bottom:1px solid #eee;color:#333;">' . esc_html( $position ) . '</td>'
				. '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">' . number_format_i18n( $query_row['clicks'] ) . '</td>'
				. '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">' . number_format_i18n( $query_row['impressions'] ) . '</td>'
				. '<td style="padding:6px 10px;border-bottom:1px solid #eee;text-align:right;">#' . esc_html( $query_row['position'] ) . '</td>'
				. '</tr>';
		}

		$site_name = get_bloginfo( 'name' );
		$dashboard = admin_url( 'admin.php?page=saman-seo' );

		$html  = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#f5f5f7;font-family:-apple-system,BlinkMacSystemFont,\'Segoe UI\',Roboto,sans-serif;">';
		$html .= '<div style="max-width:600px;margin:24px auto;background:#ffffff;border-radius:8px;border:1px solid #e5e5ea;overflow:hidden;">';
		$html .= '<div style="background:#1a1a36;padding:20px 24px;">';
		$html .= '<h1 style="margin:0;font-size:18px;color:#ffffff;">' . esc_html( sprintf( /* translators: %s: site name */ __( 'Weekly SEO digest — %s', 'saman-seo' ), $site_name ) ) . '</h1>';
		$html .= '<p style="margin:4px 0 0;color:#9aa3c7;font-size:13px;">' . esc_html( $metrics['range_label'] ?? '' ) . '</p>';
		$html .= '</div>';

		$html .= '<table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tbody>';

		foreach ( $rows as $row ) {
			$html .= $row;
		}

		$html .= '</tbody></table>';

		if ( '' !== $top_queries_html ) {
			$html .= '<div style="padding:16px 24px 8px;"><h2 style="font-size:14px;margin:0 0 8px;color:#1a1a36;">' . esc_html__( 'Top search queries', 'saman-seo' ) . '</h2>';
			$html .= '<table width="100%" cellpadding="0" cellspacing="0">';
			$html .= '<tr><th align="left" style="padding:6px 10px;border-bottom:2px solid #e5e5ea;font-size:12px;color:#777;">' . esc_html__( 'Query', 'saman-seo' ) . '</th>'
				. '<th align="right" style="padding:6px 10px;border-bottom:2px solid #e5e5ea;font-size:12px;color:#777;">' . esc_html__( 'Clicks', 'saman-seo' ) . '</th>'
				. '<th align="right" style="padding:6px 10px;border-bottom:2px solid #e5e5ea;font-size:12px;color:#777;">' . esc_html__( 'Impr.', 'saman-seo' ) . '</th>'
				. '<th align="right" style="padding:6px 10px;border-bottom:2px solid #e5e5ea;font-size:12px;color:#777;">' . esc_html__( 'Pos.', 'saman-seo' ) . '</th></tr>';
			$html .= $top_queries_html;
			$html .= '</table></div>';
		}

		if ( ! empty( $gsc['error'] ) ) {
			$html .= '<div style="margin:12px 24px;padding:10px 14px;background:#fff4f4;border-left:3px solid #d63638;font-size:13px;color:#8a1f21;">'
				. esc_html( sprintf( /* translators: %s: error message */ __( 'Search Console reported: %s', 'saman-seo' ), $gsc['error'] ) )
				. '</div>';
		}

		$html .= '<div style="padding:16px 24px 24px;text-align:center;">';
		$html .= '<a href="' . esc_url( $dashboard ) . '" style="display:inline-block;padding:10px 22px;background:#5a84ff;color:#ffffff;text-decoration:none;border-radius:6px;font-size:14px;font-weight:600;">' . esc_html__( 'Open Saman SEO', 'saman-seo' ) . '</a>';
		$html .= '</div>';

		$html .= '</div></body></html>';

		return $html;
	}

	/**
	 * Render one metric row of the digest table.
	 *
	 * @param string $label  Row label.
	 * @param string $value  Primary value.
	 * @param string $badge  Small annotation.
	 * @param string $direction up|down|stable for color coding.
	 *
	 * @return string HTML.
	 */
	private function metric_row( $label, $value, $badge, $direction ) {
		$value_color = '#1a1a36';

		if ( 'up' === $direction ) {
			$value_color = '#00a32a';
		} elseif ( 'down' === $direction ) {
			$value_color = '#d63638';
		}

		$badge_html = '';
		if ( '' !== (string) $badge ) {
			$badge_html = '<span style="margin-left:8px;font-size:11px;color:#777;font-weight:400;">' . esc_html( (string) $badge ) . '</span>';
		}

		return '<tr>'
			. '<td style="padding:12px 24px;border-bottom:1px solid #f0f0f2;color:#555;font-size:13px;">' . esc_html( $label ) . '</td>'
			. '<td style="padding:12px 24px;border-bottom:1px solid #f0f0f2;text-align:right;font-size:15px;font-weight:700;color:' . $value_color . ';">'
			. esc_html( (string) $value ) . $badge_html
			. '</td></tr>';
	}

	/**
	 * Signed delta badge text.
	 *
	 * @param int  $delta Signed change.
	 * @param bool $signed Prepend +/-.
	 *
	 * @return string
	 */
	private function delta_badge( $delta, $signed ) {
		$delta = (int) $delta;

		if ( 0 === $delta ) {
			return '';
		}

		return ( $signed ? ( $delta > 0 ? '+' : '' ) : '' ) . number_format_i18n( $delta );
	}

	/**
	 * Whether a database table exists.
	 *
	 * @param string $table Fully-prefixed table name.
	 *
	 * @return bool
	 */
	private function table_exists( $table ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence probe.
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}
}
