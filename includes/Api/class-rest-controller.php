<?php
/**
 * REST Controller Base Class
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for REST API controllers.
 */
abstract class REST_Controller {

	/**
	 * REST namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'saman-seo/v1';

	/**
	 * REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = '';

	/**
	 * Register routes - must be implemented by child classes.
	 */
	abstract public function register_routes();

	/**
	 * Permission callback - checks if user can manage options.
	 *
	 * @return bool
	 */
	public function permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * For routes the post editor's SEO sidebar calls with a post `id`: anyone who may edit
	 * that post (the sidebar loads for Authors and Editors too, not only administrators).
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool
	 */
	public function edit_post_permission_check( $request ) {
		$post_id = (int) $request['id'];

		return $post_id > 0 && current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Return a success response.
	 *
	 * @param mixed  $data    Response data.
	 * @param string $message Optional message.
	 * @return \WP_REST_Response
	 */
	protected function success( $data = null, $message = '' ) {
		return rest_ensure_response(
			array(
				'success' => true,
				'data'    => $data,
				'message' => $message,
			)
		);
	}

	/**
	 * Return an error response.
	 *
	 * @param string $message Error message.
	 * @param string $code    Error code.
	 * @param int    $status  HTTP status code.
	 * @return \WP_Error
	 */
	protected function error( $message, $code = 'error', $status = 400 ) {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
