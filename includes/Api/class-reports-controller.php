<?php
/**
 * Weekly Reports REST Controller
 *
 * @package Saman\SEO
 * @since 2.1.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Service\Weekly_Report;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for the scheduled weekly digest.
 */
class Reports_Controller extends REST_Controller {

	/**
	 * Weekly report service.
	 *
	 * @var Weekly_Report
	 */
	private $service;

	/**
	 * Allowed delivery weekdays.
	 *
	 * @var array<int,string>
	 */
	private $allowed_days = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

	/**
	 * Constructor.
	 *
	 * @param Weekly_Report|null $service Optional service injection.
	 */
	public function __construct( ?Weekly_Report $service = null ) {
		$this->service = $service ?? new Weekly_Report();
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/reports/weekly',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'enabled' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'email'   => array(
							'required'          => false,
							'type'              => 'string',
							'default'           => '',
							'sanitize_callback' => 'sanitize_email',
						),
						'day'     => array(
							'required'          => false,
							'type'              => 'string',
							'default'           => 'monday',
							'sanitize_callback' => 'sanitize_key',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/reports/weekly/send-test',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send_test' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Current digest configuration and schedule state.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status( $request ) {
		return $this->success( $this->get_status_data() );
	}

	/**
	 * Save digest settings and reschedule the cron event.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function save_settings( $request ) {
		$day   = (string) $request->get_param( 'day' );
		$email = (string) $request->get_param( 'email' );

		if ( ! in_array( $day, $this->allowed_days, true ) ) {
			return $this->error( __( 'Invalid delivery day.', 'saman-seo' ), 'saman_seo_invalid_day' );
		}

		if ( '' !== $email && ! is_email( $email ) ) {
			return $this->error( __( 'Please provide a valid email address.', 'saman-seo' ), 'saman_seo_invalid_email' );
		}

		update_option( Weekly_Report::OPTION_ENABLED, $request->get_param( 'enabled' ) ? '1' : '0' );
		update_option( Weekly_Report::OPTION_EMAIL, $email );
		update_option( Weekly_Report::OPTION_DAY, $day );

		$this->service->maybe_schedule();

		return $this->success(
			$this->get_status_data(),
			__( 'Weekly digest settings saved.', 'saman-seo' )
		);
	}

	/**
	 * Send a one-off digest to the configured recipient for verification.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function send_test( $request ) {
		$result = $this->service->send_digest();

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return $this->success(
			$this->get_status_data(),
			sprintf(
				/* translators: %s: email address */
				__( 'Test digest sent to %s.', 'saman-seo' ),
				$this->service->get_recipient()
			)
		);
	}

	/**
	 * Build the shared status payload.
	 *
	 * @return array<string,mixed>
	 */
	private function get_status_data() {
		$next = wp_next_scheduled( Weekly_Report::HOOK );

		return array(
			'enabled'        => '1' === (string) get_option( Weekly_Report::OPTION_ENABLED, '0' ),
			'email'          => (string) get_option( Weekly_Report::OPTION_EMAIL, '' ),
			'day'            => (string) get_option( Weekly_Report::OPTION_DAY, 'monday' ),
			'recipient'      => $this->service->get_recipient(),
			'next_run'       => $next ? (int) $next : 0,
			'next_run_human' => $next ? human_time_diff( $next ) : '',
			'last_sent'      => (int) get_option( Weekly_Report::OPTION_LAST_SENT, 0 ),
			'gsc_connected'  => $this->is_gsc_connected(),
		);
	}

	/**
	 * Whether Search Console is connected (digest includes traffic when so).
	 *
	 * @return bool
	 */
	private function is_gsc_connected() {
		$gsc = \Saman\SEO\Plugin::instance()->get( 'search_console' );

		return $gsc instanceof \Saman\SEO\Service\Search_Console && $gsc->is_connected();
	}
}
