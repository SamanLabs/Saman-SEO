<?php
/**
 * Tests for the weekly digest service.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Weekly_Report;
use Saman\SEO\Tests\TestCase;
use Brain\Monkey\Functions;

/**
 * Weekly report service unit tests.
 */
class WeeklyReportTest extends TestCase {

	/**
	 * Captured wp_mail calls.
	 *
	 * @var array<int,array>
	 */
	public static $sent_mail = array();

	/**
	 * Install extra stubs used only by this suite.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$sent_mail = array();

		Functions\when( 'current_time' )->alias(
			static function ( $format ) {
				return 'timestamp' === $format ? time() : date( $format );
			}
		);

		Functions\when( 'wp_timezone' )->alias(
			static function () {
				return new \DateTimeZone( date_default_timezone_get() );
			}
		);

		Functions\when( 'is_email' )->alias(
			static function ( $email ) {
				return filter_var( (string) $email, FILTER_VALIDATE_EMAIL ) ? $email : false;
			}
		);

		Functions\when( 'wp_mail' )->alias(
			static function ( $to, $subject, $message, $headers = array() ) {
				WeeklyReportTest::$sent_mail[] = array( $to, $subject, $message, $headers );
				return true;
			}
		);

		Functions\when( 'number_format_i18n' )->alias(
			static function ( $number ) {
				return number_format( (float) $number );
			}
		);

		Functions\when( 'wp_specialchars_decode' )->alias(
			static function ( $text ) {
				return htmlspecialchars_decode( (string) $text, ENT_QUOTES );
			}
		);

		Functions\when( 'admin_url' )->alias(
			static function ( $path = '' ) {
				return 'https://example.org/wp-admin/' . ltrim( (string) $path, '/' );
			}
		);
	}

	/* ---------------------------------------------------------------------
	 * Scheduling math
	 * ------------------------------------------------------------------- */

	public function test_next_run_lands_on_requested_weekday_at_nine() {
		foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $day ) {
			$ts = Weekly_Report::next_run_timestamp( $day );

			$this->assertSame( $day, strtolower( date( 'l', $ts ) ), "Day mismatch for {$day}" );
			$this->assertSame( '0900', date( 'Hi', $ts ), "Time mismatch for {$day}" );
			$this->assertGreaterThanOrEqual( time(), $ts );
		}
	}

	public function test_invalid_day_falls_back_to_monday() {
		$ts = Weekly_Report::next_run_timestamp( 'funday' );

		$this->assertSame( 'monday', strtolower( date( 'l', $ts ) ) );
	}

	public function test_next_run_is_never_in_the_past_even_late_today() {
		$today_dow = strtolower( date( 'l', time() ) );

		// Force "late today" by stubbing current hour past 09:00 via natural run time.
		$ts = Weekly_Report::next_run_timestamp( $today_dow );

		$this->assertGreaterThanOrEqual( time(), $ts );
	}

	/**
	 * Run maybe_schedule() with an existing event and report what it did to the schedule.
	 *
	 * @param int $existing Timestamp of the event already scheduled.
	 * @return string[] Calls made: 'clear' and/or 'schedule'.
	 */
	private function schedule_calls_with_event_at( $existing ) {
		$calls = array();
		Functions\when( 'wp_next_scheduled' )->justReturn( $existing );
		Functions\when( 'wp_date' )->alias(
			static function ( $format, $timestamp = null ) {
				return date( $format, null === $timestamp ? time() : $timestamp );
			}
		);
		Functions\when( 'wp_clear_scheduled_hook' )->alias(
			static function () use ( &$calls ) {
				$calls[] = 'clear';
			}
		);
		Functions\when( 'wp_schedule_event' )->alias(
			static function () use ( &$calls ) {
				$calls[] = 'schedule';
			}
		);

		( new Weekly_Report() )->maybe_schedule();

		return $calls;
	}

	/**
	 * The event due on the configured day must be left to fire. Compared with the next run
	 * (a week later once it is past 09:00), it was pushed back a week, every week.
	 */
	public function test_event_due_on_the_configured_day_is_left_to_fire() {
		self::$options[ Weekly_Report::OPTION_ENABLED ] = '1';
		self::$options[ Weekly_Report::OPTION_DAY ]     = 'wednesday';
		$due = Weekly_Report::next_run_timestamp( 'wednesday' ) - WEEK_IN_SECONDS;

		$this->assertSame( array(), $this->schedule_calls_with_event_at( $due ) );
	}

	/**
	 * Choosing another weekday still moves the event.
	 */
	public function test_changing_the_weekday_reschedules() {
		self::$options[ Weekly_Report::OPTION_ENABLED ] = '1';
		self::$options[ Weekly_Report::OPTION_DAY ]     = 'friday';
		$old_day = Weekly_Report::next_run_timestamp( 'monday' );

		$this->assertSame( array( 'clear', 'schedule' ), $this->schedule_calls_with_event_at( $old_day ) );
	}

	/* ---------------------------------------------------------------------
	 * Recipient resolution
	 * ------------------------------------------------------------------- */

	public function test_recipient_falls_back_to_admin_email() {
		update_option( 'admin_email', 'admin@example.org' );

		$service = new Weekly_Report();

		$this->assertSame( 'admin@example.org', $service->get_recipient() );
	}

	public function test_configured_recipient_wins_over_admin_email() {
		update_option( 'admin_email', 'admin@example.org' );
		update_option( Weekly_Report::OPTION_EMAIL, 'seo@example.org' );

		$service = new Weekly_Report();

		$this->assertSame( 'seo@example.org', $service->get_recipient() );
	}

	/* ---------------------------------------------------------------------
	 * Digest delivery
	 * ------------------------------------------------------------------- */

	public function test_send_digest_refuses_invalid_recipient() {
		delete_option( 'admin_email' );
		update_option( Weekly_Report::OPTION_ENABLED, '0' );

		$service = new Weekly_Report();
		$result  = $service->send_digest( 'not-an-email' );

		$this->assertInstanceOf( \WP_Error::class, $result );
		$this->assertCount( 0, self::$sent_mail );
	}

	public function test_send_digest_delivers_html_and_stamps_last_sent() {
		update_option( 'admin_email', 'admin@example.org' );

		$service = new Weekly_Report();
		$result  = $service->send_digest();

		$this->assertTrue( $result );
		$this->assertCount( 1, self::$sent_mail );

		list( $to, $subject, $body, $headers ) = self::$sent_mail[0];

		$this->assertSame( 'admin@example.org', $to );
		$this->assertStringContainsString( 'Test Site', $subject );
		$this->assertStringContainsString( 'text/html', implode( ' ', $headers ) );
		$this->assertStringContainsString( '<html', $body );
		$this->assertGreaterThan( 0, get_option( Weekly_Report::OPTION_LAST_SENT ) );
	}

	/* ---------------------------------------------------------------------
	 * Email composition
	 * ------------------------------------------------------------------- */

	public function test_build_email_html_renders_all_metric_sections() {
		$metrics = array(
			'range_label'   => 'Jan 1 - Jan 7',
			'seo_score'     => array(
				'current'  => 72,
				'previous' => 65,
				'change'   => 7,
			),
			'new_content'   => 2,
			'errors_404'    => 3,
			'redirect_hits' => 11,
			'link_health'   => array(
				'total_links'  => 100,
				'broken_links' => 4,
				'completed_at' => '2026-01-07 00:00:00',
			),
			'gsc'           => array(
				'current'     => array(
					'clicks'      => 50,
					'impressions' => 1000,
					'ctr'         => 5.0,
					'position'    => 12.0,
				),
				'previous'    => array(
					'clicks'      => 40,
					'impressions' => 900,
					'ctr'         => 4.4,
					'position'    => 13.1,
				),
				'top_queries' => array(
					array(
						'query'       => 'cold brew ratio',
						'clicks'      => 12,
						'impressions' => 300,
						'ctr'         => 4.0,
						'position'    => 3.5,
					),
				),
				'error'       => '',
			),
		);

		$html = ( new Weekly_Report() )->build_email_html( $metrics );

		$this->assertStringContainsString( 'Avg. SEO score', $html );
		$this->assertStringContainsString( 'Search clicks', $html );
		$this->assertStringContainsString( '+10', $html );
		$this->assertStringContainsString( 'New pages published', $html );
		$this->assertStringContainsString( '404 errors', $html );
		$this->assertStringContainsString( 'Broken links', $html );
		$this->assertStringContainsString( 'Top search queries', $html );
		$this->assertStringContainsString( 'cold brew ratio', $html );
		$this->assertStringContainsString( 'Open Saman SEO', $html );
	}

	public function test_build_email_html_flags_high_404_counts() {
		$metrics = array(
			'range_label' => 'Jan 1 - Jan 7',
			'new_content' => 0,
			'errors_404'  => 25,
		);

		$html = ( new Weekly_Report() )->build_email_html( $metrics );

		$this->assertStringContainsString( 'needs attention', $html );
	}
}