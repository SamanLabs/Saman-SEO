<?php
/**
 * More Plugins REST Controller
 *
 * Handles REST API endpoints for the "More" plugins page.
 *
 * @package Saman\SEO
 * @since 2.0.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Updater\Plugin_Registry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * More_Controller class - REST API for plugin promotion page.
 */
class More_Controller extends REST_Controller {

	/**
	 * REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'more';

	/**
	 * Register REST routes.
	 */
	public function register_routes() {
		// Get all Saman plugins.
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/plugins',
			[
				'methods'             => 'GET',
				'callback'            => [ $this, 'get_plugins' ],
				'permission_callback' => [ $this, 'admin_permission_check' ],
			]
		);
	}

	/**
	 * Check if user has admin permissions.
	 *
	 * @return bool True if user can manage options.
	 */
	public function admin_permission_check() {
		return current_user_can( 'manage_options' );
	}

	/**
	 * Get all Saman plugins.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_plugins( $request ) {
		// Load the registry class if not autoloaded.
		if ( ! class_exists( 'Saman\SEO\Updater\Plugin_Registry' ) ) {
			require_once SAMAN_SEO_PATH . 'includes/Updater/class-plugin-registry.php';
		}

		$plugins = Plugin_Registry::get_plugins_with_status();

		return $this->success( array_values( $plugins ) );
	}
}
