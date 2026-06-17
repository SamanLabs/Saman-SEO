<?php
/**
 * REST API Controller for Breadcrumbs.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Api;

use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;
use Saman\SEO\Plugin;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs REST API Controller.
 */
class Breadcrumbs_Controller extends WP_REST_Controller {

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
	protected $rest_base = 'breadcrumbs';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Settings endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Options endpoint (separators, presets).
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/options',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_options' ),
					'permission_callback' => array( $this, 'check_permission' ),
				),
			)
		);

		// Preview endpoint.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/preview',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_preview' ),
					'permission_callback' => array( $this, 'check_permission' ),
					'args'                => array(
						'post_id' => array(
							'type'    => 'integer',
							'default' => 0,
						),
					),
				),
			)
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
	 * Get breadcrumb service.
	 *
	 * @return \Saman\SEO\Service\Breadcrumbs|null
	 */
	private function get_service() {
		return Plugin::instance()->get( 'breadcrumbs' );
	}

	/**
	 * Get breadcrumb settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_settings( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Breadcrumbs service not available.', 'saman-seo' ),
				),
				500
			);
		}

		$settings = $service->get_settings();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => $settings,
			)
		);
	}

	/**
	 * Save breadcrumb settings.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function save_settings( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Breadcrumbs service not available.', 'saman-seo' ),
				),
				500
			);
		}

		$settings = $request->get_json_params();

		if ( empty( $settings ) ) {
			$settings = $request->get_params();
		}

		$result = $service->save_settings( $settings );

		if ( $result ) {
			return new WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Settings saved successfully.', 'saman-seo' ),
					'data'    => $service->get_settings(),
				)
			);
		}

		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => __( 'Failed to save settings.', 'saman-seo' ),
			),
			500
		);
	}

	/**
	 * Get breadcrumb options (separators, presets).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_options( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Breadcrumbs service not available.', 'saman-seo' ),
				),
				500
			);
		}

		// Get available post types.
		$post_types        = array();
		$public_post_types = get_post_types( array( 'public' => true ), 'objects' );
		foreach ( $public_post_types as $pt ) {
			if ( 'attachment' === $pt->name ) {
				continue;
			}
			$post_types[] = array(
				'name'  => $pt->name,
				'label' => $pt->labels->name,
			);
		}

		// Get available taxonomies.
		$taxonomies        = array();
		$public_taxonomies = get_taxonomies( array( 'public' => true ), 'objects' );
		foreach ( $public_taxonomies as $tax ) {
			$taxonomies[] = array(
				'name'  => $tax->name,
				'label' => $tax->labels->name,
			);
		}

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'separators'    => $service->get_separator_options(),
					'style_presets' => $service->get_style_presets(),
					'post_types'    => $post_types,
					'taxonomies'    => $taxonomies,
				),
			)
		);
	}

	/**
	 * Get breadcrumb preview HTML.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function get_preview( $request ) {
		$service = $this->get_service();

		if ( ! $service ) {
			return new WP_REST_Response(
				array(
					'success' => false,
					'message' => __( 'Breadcrumbs service not available.', 'saman-seo' ),
				),
				500
			);
		}

		$post_id = $request->get_param( 'post_id' );

		// Generate sample breadcrumbs for preview.
		$sample_crumbs = array(
			array(
				'url'   => home_url( '/' ),
				'title' => __( 'Home', 'saman-seo' ),
			),
			array(
				'url'   => home_url( '/category/' ),
				'title' => __( 'Category', 'saman-seo' ),
			),
			array(
				'url'   => '',
				'title' => __( 'Current Page', 'saman-seo' ),
			),
		);

		// Temporarily filter breadcrumbs to use sample data.
		add_filter(
			'SAMAN_SEO_breadcrumb_items',
			function () use ( $sample_crumbs ) {
				return $sample_crumbs;
			},
			999
		);

		$html = $service->render();

		return new WP_REST_Response(
			array(
				'success' => true,
				'data'    => array(
					'html' => $html,
				),
			)
		);
	}
}
