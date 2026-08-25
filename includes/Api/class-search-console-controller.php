<?php
/**
 * Search Console REST Controller
 *
 * @package Saman\SEO
 * @since 2.1.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Service\Search_Console;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for the Google Search Console integration.
 */
class Search_Console_Controller extends REST_Controller {

	/**
	 * Search Console service.
	 *
	 * @var Search_Console
	 */
	private $service;

	/**
	 * Constructor.
	 *
	 * @param Search_Console|null $service Optional service injection.
	 */
	public function __construct( ?Search_Console $service = null ) {
		$this->service = $service ?? new Search_Console();
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/search-console/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/search-console/credentials',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_credentials' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'client_id'     => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
						'client_secret' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/search-console/auth-url',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_auth_url' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/search-console/disconnect',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'disconnect' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/search-console/site',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'select_site' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'url' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/search-console/analytics',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_analytics' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'days' => array(
							'required'          => false,
							'type'              => 'integer',
							'default'           => 28,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Connection status + property list + any pending OAuth error.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_status( $request ) {
		$status = $this->service->get_status();
		$error  = $this->service->pop_oauth_error();

		if ( '' !== $error ) {
			$status['error'] = sprintf(
				/* translators: %s: OAuth error code */
				__( 'Google authorization failed (%s). Please try connecting again.', 'saman-seo' ),
				$error
			);
		}

		return $this->success( $status );
	}

	/**
	 * Store OAuth client credentials.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function save_credentials( $request ) {
		$this->service->set_credentials(
			$request->get_param( 'client_id' ),
			$request->get_param( 'client_secret' )
		);

		return $this->success(
			$this->service->get_status(),
			__( 'Google OAuth credentials saved.', 'saman-seo' )
		);
	}

	/**
	 * Return the Google authorization URL to redirect the browser to.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_auth_url( $request ) {
		$url = $this->service->get_auth_url();

		if ( is_wp_error( $url ) ) {
			return $url;
		}

		return $this->success( array( 'auth_url' => $url ) );
	}

	/**
	 * Disconnect and wipe tokens.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function disconnect( $request ) {
		$this->service->disconnect();

		return $this->success( $this->service->get_status(), __( 'Search Console disconnected.', 'saman-seo' ) );
	}

	/**
	 * Select the active Search Console property.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function select_site( $request ) {
		$this->service->set_site( $request->get_param( 'url' ) );

		return $this->success( $this->service->get_status() );
	}

	/**
	 * Cached analytics summary for the dashboard card.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_analytics( $request ) {
		if ( ! $this->service->is_connected() || ! $this->service->is_configured() ) {
			return $this->error(
				__( 'Search Console is not connected yet.', 'saman-seo' ),
				'saman_seo_gsc_not_connected',
				400
			);
		}

		$this->service->discover_account_email();

		$summary = $this->service->get_summary( (int) $request->get_param( 'days' ) );

		if ( is_wp_error( $summary ) ) {
			return $summary;
		}

		$summary['status'] = $this->service->get_status();

		return $this->success( $summary );
	}
}
