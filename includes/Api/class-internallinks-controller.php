<?php
/**
 * Internal Links REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Internal_Linking\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for internal linking rules, categories, templates, and settings.
 */
class InternalLinks_Controller extends REST_Controller {

	/**
	 * Repository instance.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->repository = new Repository();
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// Rules endpoints
		register_rest_route(
			$this->namespace,
			'/internal-links/rules',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rules' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'status'    => array(
							'required' => false,
							'type'     => 'string',
							'enum'     => array( '', 'active', 'inactive' ),
						),
						'category'  => array(
							'required' => false,
							'type'     => 'string',
						),
						'post_type' => array(
							'required' => false,
							'type'     => 'string',
						),
						'search'    => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/rules/(?P<id>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/rules/(?P<id>[a-z0-9_-]+)/duplicate',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'duplicate_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/rules/(?P<id>[a-z0-9_-]+)/toggle',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle_rule' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/rules/bulk',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'bulk_update_rules' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'ids'      => array(
							'required' => true,
							'type'     => 'array',
						),
						'action'   => array(
							'required' => true,
							'type'     => 'string',
							'enum'     => array( 'activate', 'deactivate', 'delete', 'change_category' ),
						),
						'category' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);

		// Categories endpoints
		register_rest_route(
			$this->namespace,
			'/internal-links/categories',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_categories' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_category' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/categories/(?P<id>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_category' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_category' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_category' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'reassign' => array(
							'required' => false,
							'type'     => 'string',
						),
					),
				),
			)
		);

		// UTM Templates endpoints
		register_rest_route(
			$this->namespace,
			'/internal-links/templates',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/internal-links/templates/(?P<id>[a-z0-9_-]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// Settings endpoints
		register_rest_route(
			$this->namespace,
			'/internal-links/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// Post search endpoint for destination picker
		register_rest_route(
			$this->namespace,
			'/internal-links/search-posts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_posts' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'search' => array(
							'required'          => true,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		// Stats endpoint
		register_rest_route(
			$this->namespace,
			'/internal-links/stats',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Get all rules with optional filtering.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_rules( $request ) {
		$args = array();

		if ( $request->get_param( 'status' ) ) {
			$args['status'] = $request->get_param( 'status' );
		}
		if ( $request->get_param( 'category' ) ) {
			$args['category'] = $request->get_param( 'category' );
		}
		if ( $request->get_param( 'post_type' ) ) {
			$args['post_type'] = $request->get_param( 'post_type' );
		}
		if ( $request->get_param( 'search' ) ) {
			$args['search'] = $request->get_param( 'search' );
		}

		$rules = $this->repository->get_rules( $args );

		// Enrich with destination labels
		$rules = array_map( array( $this, 'enrich_rule' ), $rules );

		return $this->success( array_values( $rules ) );
	}

	/**
	 * Get single rule.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_rule( $request ) {
		$rule = $this->repository->get_rule( $request->get_param( 'id' ) );

		if ( ! $rule ) {
			return $this->error( __( 'Rule not found.', 'saman-seo' ), 'not_found', 404 );
		}

		return $this->success( $this->enrich_rule( $rule ) );
	}

	/**
	 * Create a new rule.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_rule( $request ) {
		$data   = $request->get_json_params();
		$result = $this->repository->save_rule( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $this->enrich_rule( $result ), __( 'Rule created.', 'saman-seo' ) );
	}

	/**
	 * Update existing rule.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_rule( $request ) {
		$id       = $request->get_param( 'id' );
		$existing = $this->repository->get_rule( $id );

		if ( ! $existing ) {
			return $this->error( __( 'Rule not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$data               = $request->get_json_params();
		$data['id']         = $id;
		$data['created_at'] = $existing['created_at'];

		$result = $this->repository->save_rule( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $this->enrich_rule( $result ), __( 'Rule updated.', 'saman-seo' ) );
	}

	/**
	 * Delete a rule.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_rule( $request ) {
		$deleted = $this->repository->delete_rule( $request->get_param( 'id' ) );

		if ( ! $deleted ) {
			return $this->error( __( 'Rule not found.', 'saman-seo' ), 'not_found', 404 );
		}

		return $this->success( null, __( 'Rule deleted.', 'saman-seo' ) );
	}

	/**
	 * Duplicate a rule.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function duplicate_rule( $request ) {
		$result = $this->repository->duplicate_rule( $request->get_param( 'id' ) );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $this->enrich_rule( $result ), __( 'Rule duplicated.', 'saman-seo' ) );
	}

	/**
	 * Toggle rule status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle_rule( $request ) {
		$id   = $request->get_param( 'id' );
		$rule = $this->repository->get_rule( $id );

		if ( ! $rule ) {
			return $this->error( __( 'Rule not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$rule['status'] = ( 'active' === $rule['status'] ) ? 'inactive' : 'active';
		$result         = $this->repository->save_rule( $rule );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $this->enrich_rule( $result ) );
	}

	/**
	 * Bulk update rules.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function bulk_update_rules( $request ) {
		$ids    = $request->get_param( 'ids' );
		$action = $request->get_param( 'action' );

		if ( 'change_category' === $action ) {
			$category = $request->get_param( 'category' );
			$count    = 0;

			foreach ( $ids as $id ) {
				$rule = $this->repository->get_rule( $id );
				if ( $rule ) {
					$rule['category'] = ( '__none__' === $category ) ? '' : $category;
					$this->repository->save_rule( $rule );
					++$count;
				}
			}

			return $this->success( array( 'affected' => $count ) );
		}

		$count = $this->repository->bulk_update_rules( $ids, $action );

		return $this->success( array( 'affected' => $count ) );
	}

	/**
	 * Get all categories.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_categories( $request ) {
		$categories = $this->repository->get_categories();

		// Add rule count for each category
		$rules           = $this->repository->get_rules();
		$category_counts = array();

		foreach ( $rules as $rule ) {
			$cat_id = $rule['category'] ?? '';
			if ( $cat_id ) {
				$category_counts[ $cat_id ] = ( $category_counts[ $cat_id ] ?? 0 ) + 1;
			}
		}

		foreach ( $categories as &$category ) {
			$category['rule_count'] = $category_counts[ $category['id'] ] ?? 0;
		}

		return $this->success( array_values( $categories ) );
	}

	/**
	 * Get single category.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_category( $request ) {
		$category = $this->repository->get_category( $request->get_param( 'id' ) );

		if ( ! $category ) {
			return $this->error( __( 'Category not found.', 'saman-seo' ), 'not_found', 404 );
		}

		return $this->success( $category );
	}

	/**
	 * Create a category.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_category( $request ) {
		$data   = $request->get_json_params();
		$result = $this->repository->save_category( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $result, __( 'Category created.', 'saman-seo' ) );
	}

	/**
	 * Update a category.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_category( $request ) {
		$id       = $request->get_param( 'id' );
		$existing = $this->repository->get_category( $id );

		if ( ! $existing ) {
			return $this->error( __( 'Category not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$data               = $request->get_json_params();
		$data['id']         = $id;
		$data['created_at'] = $existing['created_at'];

		$result = $this->repository->save_category( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $result, __( 'Category updated.', 'saman-seo' ) );
	}

	/**
	 * Delete a category.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_category( $request ) {
		$id       = $request->get_param( 'id' );
		$reassign = $request->get_param( 'reassign' );

		$result = $this->repository->delete_category( $id, $reassign );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( null, __( 'Category deleted.', 'saman-seo' ) );
	}

	/**
	 * Get all UTM templates.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_templates( $request ) {
		return $this->success( array_values( $this->repository->get_templates() ) );
	}

	/**
	 * Get single template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_template( $request ) {
		$template = $this->repository->get_template( $request->get_param( 'id' ) );

		if ( ! $template ) {
			return $this->error( __( 'Template not found.', 'saman-seo' ), 'not_found', 404 );
		}

		return $this->success( $template );
	}

	/**
	 * Create a template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function create_template( $request ) {
		$data   = $request->get_json_params();
		$result = $this->repository->save_template( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $result, __( 'Template created.', 'saman-seo' ) );
	}

	/**
	 * Update a template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_template( $request ) {
		$id       = $request->get_param( 'id' );
		$existing = $this->repository->get_template( $id );

		if ( ! $existing ) {
			return $this->error( __( 'Template not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$data               = $request->get_json_params();
		$data['id']         = $id;
		$data['created_at'] = $existing['created_at'];

		$result = $this->repository->save_template( $data );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), $result->get_error_code(), 400 );
		}

		return $this->success( $result, __( 'Template updated.', 'saman-seo' ) );
	}

	/**
	 * Delete a template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_template( $request ) {
		$deleted = $this->repository->delete_template( $request->get_param( 'id' ) );

		if ( ! $deleted ) {
			return $this->error( __( 'Template not found.', 'saman-seo' ), 'not_found', 404 );
		}

		return $this->success( null, __( 'Template deleted.', 'saman-seo' ) );
	}

	/**
	 * Get settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_settings( $request ) {
		return $this->success( $this->repository->get_settings() );
	}

	/**
	 * Save settings.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_settings( $request ) {
		$data   = $request->get_json_params();
		$result = $this->repository->save_settings( $data );

		return $this->success( $result, __( 'Settings saved.', 'saman-seo' ) );
	}

	/**
	 * Search posts for destination picker.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function search_posts( $request ) {
		$search = $request->get_param( 'search' );

		$post_types = get_post_types( array( 'public' => true ), 'names' );
		unset( $post_types['attachment'] );

		$query = new \WP_Query(
			array(
				'post_type'      => array_values( $post_types ),
				'post_status'    => 'publish',
				's'              => $search,
				'posts_per_page' => 10,
				'orderby'        => 'relevance',
			)
		);

		$results = array();
		foreach ( $query->posts as $post ) {
			$results[] = array(
				'id'        => $post->ID,
				'title'     => get_the_title( $post ),
				'post_type' => $post->post_type,
				'url'       => get_permalink( $post ),
			);
		}

		return $this->success( $results );
	}

	/**
	 * Get internal linking stats.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_stats( $request ) {
		$rules      = $this->repository->get_rules();
		$categories = $this->repository->get_categories();
		$templates  = $this->repository->get_templates();

		$active_rules = count(
			array_filter(
				$rules,
				function ( $r ) {
					return 'active' === ( $r['status'] ?? '' );
				}
			)
		);

		return $this->success(
			array(
				'total_rules'   => count( $rules ),
				'active_rules'  => $active_rules,
				'categories'    => count( $categories ),
				'utm_templates' => count( $templates ),
			)
		);
	}

	/**
	 * Enrich rule with destination label.
	 *
	 * @param array $rule Rule data.
	 * @return array
	 */
	private function enrich_rule( $rule ) {
		$destination = $rule['destination'] ?? array();

		if ( 'post' === ( $destination['type'] ?? 'post' ) && ! empty( $destination['post'] ) ) {
			$post = get_post( $destination['post'] );
			if ( $post ) {
				$rule['destination_label'] = sprintf( '%s (%s)', get_the_title( $post ), $post->post_type );
				$rule['destination_url']   = get_permalink( $post );
			} else {
				$rule['destination_label'] = __( 'Post not found', 'saman-seo' );
				$rule['destination_url']   = '';
			}
		} elseif ( ! empty( $destination['url'] ) ) {
			$rule['destination_label'] = $destination['url'];
			$rule['destination_url']   = $destination['url'];
		} else {
			$rule['destination_label'] = '';
			$rule['destination_url']   = '';
		}

		return $rule;
	}
}
