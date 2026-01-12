<?php
/**
 * REST Controller Base Class
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

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
    protected $namespace = 'wpseopilot/v2';

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
     * Return a success response.
     *
     * @param mixed  $data    Response data.
     * @param string $message Optional message.
     * @return \WP_REST_Response
     */
    protected function success( $data = null, $message = '' ) {
        return rest_ensure_response( [
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ] );
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
        return new \WP_Error( $code, $message, [ 'status' => $status ] );
    }
}
