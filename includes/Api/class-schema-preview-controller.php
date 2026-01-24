<?php
/**
 * Schema Preview REST Controller
 *
 * Provides REST endpoint for previewing JSON-LD schema output for a post.
 *
 * @package Saman\SEO\Api
 * @since   1.0.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Schema_Graph_Manager;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for schema preview functionality.
 *
 * Enables the editor sidebar to fetch live JSON-LD preview for posts.
 */
class Schema_Preview_Controller extends REST_Controller {

	/**
	 * REST base for this controller.
	 *
	 * @var string
	 */
	protected $rest_base = 'schema/preview';

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<post_id>\d+)',
			[
				[
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'get_preview' ],
					'permission_callback' => [ $this, 'permission_check_post' ],
					'args'                => [
						'post_id'     => [
							'type'              => 'integer',
							'required'          => true,
							'sanitize_callback' => 'absint',
							'description'       => 'The post ID to preview schema for.',
						],
						'schema_type' => [
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
							'description'       => 'Optional schema type override for preview.',
						],
					],
				],
			]
		);
	}

	/**
	 * Permission callback - checks if user can edit the specific post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool True if user can edit the post, false otherwise.
	 */
	public function permission_check_post( $request ) {
		$post_id = absint( $request->get_param( 'post_id' ) );
		return current_user_can( 'edit_post', $post_id );
	}

	/**
	 * Get schema preview for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error Response or error.
	 */
	public function get_preview( $request ) {
		$post_id     = absint( $request->get_param( 'post_id' ) );
		$schema_type = $request->get_param( 'schema_type' );

		// Validate post exists.
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error(
				__( 'Post not found.', 'saman-seo' ),
				'not_found',
				404
			);
		}

		// Create context from the post.
		$context = Schema_Context::from_post( $post );

		// Apply schema_type override if provided.
		if ( ! empty( $schema_type ) ) {
			$context->schema_type = $schema_type;
		}

		// Build the schema graph.
		$manager = new Schema_Graph_Manager( Schema_Registry::instance() );
		$graph   = $manager->build( $context );

		return $this->success(
			[
				'json_ld'     => $graph,
				'schema_type' => $context->schema_type,
			]
		);
	}
}
