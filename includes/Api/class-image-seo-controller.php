<?php
/**
 * REST API Controller for Image SEO.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_Error;
use Saman\SEO\Service\Image_SEO;

defined( 'ABSPATH' ) || exit;

/**
 * Image SEO REST API Controller.
 */
class Image_SEO_Controller extends REST_Controller {

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
	protected $rest_base = 'image-seo';

	/**
	 * Image SEO service.
	 *
	 * @var Image_SEO
	 */
	private $service;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->service = new Image_SEO();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/audit',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_audit' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'page'     => array(
							'type'              => 'integer',
							'default'           => 1,
							'sanitize_callback' => 'absint',
						),
						'per_page' => array(
							'type'              => 'integer',
							'default'           => 20,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/fix-alt',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'fix_alt' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'id' => array(
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/settings',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'auto_alt'       => array(
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'min_alt_length' => array(
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Audit media library attachments.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return object|WP_Error
	 */
	public function get_audit( WP_REST_Request $request ) {
		$page     = max( 1, (int) $request->get_param( 'page' ) );
		$per_page = min( 100, max( 1, (int) $request->get_param( 'per_page' ) ) );

		$attachments = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_mime_type' => 'image',
				'posts_per_page' => $per_page,
				'paged'          => $page,
				'post_status'    => 'inherit',
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		if ( is_wp_error( $attachments ) ) {
			return $this->error( __( 'Could not query attachments.', 'saman-seo' ), 'query_failed', 500 );
		}

		$items  = array();
		$issues = array(
			'missing_alt'      => 0,
			'short_alt'        => 0,
			'generic_alt'      => 0,
			'generic_filename' => 0,
		);

		foreach ( $attachments as $attachment_id ) {
			$report  = $this->service->audit_attachment( (int) $attachment_id );
			$items[] = $report;

			foreach ( $report['issues'] as $issue ) {
				if ( isset( $issues[ $issue['code'] ] ) ) {
					++$issues[ $issue['code'] ];
				}
			}
		}

		return $this->success(
			array(
				'items'  => $items,
				'counts' => array_merge(
					$issues,
					array(
						'total_images' => count( $items ),
						'total_issues' => array_sum( $issues ),
					)
				),
				'page'   => $page,
			)
		);
	}

	/**
	 * Apply the suggested alt text to an attachment.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return object|WP_Error
	 */
	public function fix_alt( WP_REST_Request $request ) {
		$id         = (int) $request->get_param( 'id' );
		$suggestion = $this->service->suggest_alt_text( $id );

		if ( '' === trim( $suggestion ) ) {
			return $this->error(
				__( 'No descriptive alt text could be generated for this image. Please write one manually.', 'saman-seo' ),
				'no_suggestion',
				422
			);
		}

		$result = update_post_meta( $id, '_wp_attachment_image_alt', sanitize_text_field( $suggestion ) );

		if ( false === $result && (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) !== $suggestion ) {
			return $this->error( __( 'Could not update alt text.', 'saman-seo' ), 'update_failed', 500 );
		}

		return $this->success(
			array(
				'id'  => $id,
				'alt' => $suggestion,
			),
			__( 'Alt text updated.', 'saman-seo' )
		);
	}

	/**
	 * Return current settings.
	 *
	 * @return object
	 */
	public function get_settings() {
		return $this->success( $this->service->get_settings() );
	}

	/**
	 * Save settings.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return object
	 */
	public function save_settings( WP_REST_Request $request ) {
		$incoming = array(
			'auto_alt'       => (bool) $request->get_param( 'auto_alt' ),
			'min_alt_length' => (int) $request->get_param( 'min_alt_length' ),
		);

		// Only overwrite provided keys so partial saves keep other values.
		$current = $this->service->get_settings();
		foreach ( $incoming as $key => $value ) {
			if ( null !== $request->get_param( $key ) ) {
				$current[ $key ] = $value;
			}
		}

		$this->service->save_settings( $current );

		return $this->success( $this->service->get_settings(), __( 'Settings saved.', 'saman-seo' ) );
	}
}
