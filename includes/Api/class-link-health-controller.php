<?php
/**
 * REST API Controller for Link Health.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Saman\SEO\Service\Link_Health;

defined( 'ABSPATH' ) || exit;

/**
 * Link Health REST API Controller.
 */
class Link_Health_Controller extends WP_REST_Controller {

	/**
	 * Namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'saman-seo/v1';

	/**
	 * Resource base.
	 *
	 * @var string
	 */
	protected $rest_base = 'link-health';

	/**
	 * Link Health service.
	 *
	 * @var Link_Health
	 */
	private $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new Link_Health();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Summary endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/summary',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_summary' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Broken links endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/broken',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_broken_links' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'page'     => [
							'type'    => 'integer',
							'default' => 1,
						],
						'per_page' => [
							'type'    => 'integer',
							'default' => 50,
						],
						'type'     => [
							'type'    => 'string',
							'enum'    => [ '', 'internal', 'external' ],
							'default' => '',
						],
					],
				],
			]
		);

		// Orphan pages endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/orphans',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_orphan_pages' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'page'     => [
							'type'    => 'integer',
							'default' => 1,
						],
						'per_page' => [
							'type'    => 'integer',
							'default' => 50,
						],
					],
				],
			]
		);

		// Start scan endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scan',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'start_scan' ],
					'permission_callback' => [ $this, 'check_permission' ],
					'args'                => [
						'type'    => [
							'type'    => 'string',
							'enum'    => [ 'full', 'partial', 'single' ],
							'default' => 'full',
						],
						'post_id' => [
							'type'    => 'integer',
							'default' => 0,
						],
					],
				],
			]
		);

		// Scan status endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scan/status',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_scan_status' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Scan history endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/scan/history',
			[
				[
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => [ $this, 'get_scan_history' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Single link actions.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/link/(?P<id>\d+)',
			[
				[
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => [ $this, 'delete_link' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);

		// Recheck link endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/link/(?P<id>\d+)/recheck',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'recheck_link' ],
					'permission_callback' => [ $this, 'check_permission' ],
				],
			]
		);
	}

	/**
	 * Check if user has permission.
	 *
	 * @return bool
	 */
	public function check_permission() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get link health summary.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_summary( $request ) {
		$summary = $this->service->get_summary();

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $summary,
		] );
	}

	/**
	 * Get broken links.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_broken_links( $request ) {
		$args = [
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
			'type'     => $request->get_param( 'type' ),
		];

		$result = $this->service->get_broken_links( $args );

		// Format items for response.
		$items = array_map( function( $link ) {
			return [
				'id'            => (int) $link->id,
				'source_post_id' => (int) $link->source_post_id,
				'source_title'  => $link->source_title ?? '',
				'source_url'    => get_permalink( $link->source_post_id ),
				'target_url'    => $link->target_url,
				'link_text'     => $link->link_text,
				'link_type'     => $link->link_type,
				'status'        => $link->status,
				'http_code'     => $link->http_code ? (int) $link->http_code : null,
				'error_message' => $link->error_message,
				'last_checked'  => $link->last_checked,
			];
		}, $result['items'] );

		return new WP_REST_Response( [
			'success' => true,
			'data'    => [
				'items'       => $items,
				'total'       => $result['total'],
				'page'        => $result['page'],
				'per_page'    => $result['per_page'],
				'total_pages' => $result['total_pages'],
			],
		] );
	}

	/**
	 * Get orphan pages.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_orphan_pages( $request ) {
		$args = [
			'page'     => $request->get_param( 'page' ),
			'per_page' => $request->get_param( 'per_page' ),
		];

		$result = $this->service->get_orphan_pages( $args );

		// Format items for response.
		$items = array_map( function( $page ) {
			return [
				'id'         => (int) $page->ID,
				'title'      => $page->post_title,
				'post_type'  => $page->post_type,
				'url'        => get_permalink( $page->ID ),
				'edit_url'   => get_edit_post_link( $page->ID, 'raw' ),
				'post_date'  => $page->post_date,
			];
		}, $result['items'] );

		return new WP_REST_Response( [
			'success' => true,
			'data'    => [
				'items'       => $items,
				'total'       => $result['total'],
				'page'        => $result['page'],
				'per_page'    => $result['per_page'],
				'total_pages' => $result['total_pages'],
			],
		] );
	}

	/**
	 * Start a new scan.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function start_scan( $request ) {
		$type    = $request->get_param( 'type' );
		$post_id = $request->get_param( 'post_id' );

		$scan_id = $this->service->start_scan( $type, $post_id );

		if ( false === $scan_id ) {
			return new WP_Error(
				'scan_failed',
				__( 'Could not start scan. A scan may already be running.', 'saman-seo' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => [
				'scan_id' => $scan_id,
				'message' => __( 'Scan started successfully.', 'saman-seo' ),
			],
		] );
	}

	/**
	 * Get current scan status.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_scan_status( $request ) {
		$scan = $this->service->get_current_scan();

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $scan,
		] );
	}

	/**
	 * Get scan history.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_scan_history( $request ) {
		$history = $this->service->get_scan_history( 10 );

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $history,
		] );
	}

	/**
	 * Delete a link entry.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function delete_link( $request ) {
		$id = (int) $request->get_param( 'id' );

		if ( $this->service->delete_link( $id ) ) {
			return new WP_REST_Response( [
				'success' => true,
				'message' => __( 'Link deleted.', 'saman-seo' ),
			] );
		}

		return new WP_Error(
			'delete_failed',
			__( 'Could not delete link.', 'saman-seo' ),
			[ 'status' => 400 ]
		);
	}

	/**
	 * Recheck a link.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function recheck_link( $request ) {
		$id = (int) $request->get_param( 'id' );

		$result = $this->service->recheck_link( $id );

		if ( false === $result ) {
			return new WP_Error(
				'recheck_failed',
				__( 'Could not recheck link.', 'saman-seo' ),
				[ 'status' => 400 ]
			);
		}

		return new WP_REST_Response( [
			'success' => true,
			'data'    => $result,
		] );
	}
}
