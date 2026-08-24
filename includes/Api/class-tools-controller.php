<?php
/**
 * AI-Powered Tools REST Controller
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Integration\AI_Pilot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for AI-powered tools.
 *
 * All AI operations are delegated to Saman Labs AI plugin via AI_Pilot.
 */
class Tools_Controller extends REST_Controller {

	/**
	 * Register routes.
	 */
	public function register_routes() {
		// === Bulk Editor Routes ===
		register_rest_route(
			$this->namespace,
			'/tools/bulk-editor/posts',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_posts_for_bulk_edit' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/bulk-editor/generate',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_suggestions' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/bulk-editor/save',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_bulk_changes' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// === Content Gaps Routes ===
		register_rest_route(
			$this->namespace,
			'/tools/content-gaps/analyze',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'analyze_content_gaps' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/content-gaps/outline',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_outline' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// === Schema Builder Routes ===
		register_rest_route(
			$this->namespace,
			'/tools/schema/detect',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'detect_schema_type' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/schema/generate',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_schema' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/schema/validate',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'validate_schema' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/schema/save',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_schema' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/schema/import',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'import_schema' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/schema/templates',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_schema_templates' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_schema_template' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// === Robots.txt Routes ===
		register_rest_route(
			$this->namespace,
			'/tools/robots-txt',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_robots_txt' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_robots_txt' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/robots-txt/reset',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'reset_robots_txt' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/robots-txt/test',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'test_robots_txt' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		// === Image SEO Routes ===
		register_rest_route(
			$this->namespace,
			'/images',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_images' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'filter'   => array(
							'type'    => 'string',
							'default' => 'all',
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
						),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 20,
						),
						'search'   => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/images/(?P<id>\d+)',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update_image_alt' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'id'  => array(
							'type'     => 'integer',
							'required' => true,
						),
						'alt' => array(
							'type'    => 'string',
							'default' => '',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/images/(?P<id>\d+)/generate-alt',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'generate_image_alt' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/tools/delete-all-data',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'delete_all_data' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Get schema templates.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_schema_templates( $request ) {
		$templates = get_option( 'SAMAN_SEO_schema_templates', array() );
		return $this->success( $templates );
	}

	/**
	 * Save schema template.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_schema_template( $request ) {
		$params = $request->get_json_params();

		$name = isset( $params['name'] ) ? sanitize_text_field( $params['name'] ) : '';
		$type = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : '';

		if ( empty( $name ) ) {
			return $this->error( __( 'Template name is required.', 'saman-seo' ), 'missing_name', 400 );
		}

		if ( empty( $type ) ) {
			return $this->error( __( 'Schema type is required.', 'saman-seo' ), 'missing_type', 400 );
		}

		$templates = get_option( 'SAMAN_SEO_schema_templates', array() );
		if ( ! is_array( $templates ) ) {
			$templates = array();
		}

		// Limit total stored templates to prevent option bloat.
		$max_templates = saman_seo_apply_filters( 'saman_seo_max_schema_templates', 100 );
		if ( count( $templates ) >= $max_templates ) {
			return $this->error(
				__( 'Maximum number of schema templates reached.', 'saman-seo' ),
				'template_limit_reached',
				400
			);
		}

		$template = array(
			'id'          => 'template_' . time() . '_' . wp_rand( 1000, 9999 ),
			'name'        => $name,
			'type'        => $type,
			'description' => isset( $params['description'] ) ? sanitize_textarea_field( $params['description'] ) : '',
			'data'        => $this->sanitize_schema_template_data( $params['data'] ?? array() ),
			'created_at'  => current_time( 'mysql' ),
		);

		$templates[] = $template;
		update_option( 'SAMAN_SEO_schema_templates', $templates );

		return $this->success( $templates );
	}

	/**
	 * Recursively sanitize schema template data.
	 *
	 * @param mixed $data Raw data.
	 * @return mixed
	 */
	private function sanitize_schema_template_data( $data ) {
		if ( is_string( $data ) ) {
			return wp_kses_post( $data );
		}

		if ( is_numeric( $data ) ) {
			return $data;
		}

		if ( is_bool( $data ) ) {
			return $data;
		}

		if ( is_array( $data ) ) {
			$clean = array();
			foreach ( $data as $key => $value ) {
				$clean_key           = is_string( $key ) ? sanitize_text_field( $key ) : $key;
				$clean[ $clean_key ] = $this->sanitize_schema_template_data( $value );
			}
			return $clean;
		}

		return null;
	}

	/**
	 * Import schema from URL.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function import_schema( $request ) {
		$params = $request->get_json_params();
		$url    = isset( $params['url'] ) ? esc_url_raw( $params['url'] ) : '';

		if ( empty( $url ) ) {
			return $this->error( __( 'URL is required.', 'saman-seo' ), 'missing_url', 400 );
		}

		if ( ! wp_http_validate_url( $url ) ) {
			return $this->error( __( 'Invalid or unsafe URL.', 'saman-seo' ), 'invalid_url', 400 );
		}

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return $this->error( $response->get_error_message(), 'remote_error', 500 );
		}

		$body = wp_remote_retrieve_body( $response );
		$dom  = new \DOMDocument();
		@$dom->loadHTML( $body );

		$xpath   = new \DOMXPath( $dom );
		$scripts = $xpath->query( '//script[@type="application/ld+json"]' );

		if ( $scripts->length === 0 ) {
			return $this->error( __( 'No schema found at the specified URL.', 'saman-seo' ), 'no_schema_found', 404 );
		}

		$schema_data = json_decode( $scripts[0]->nodeValue, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return $this->error( __( 'Invalid JSON format in schema.', 'saman-seo' ), 'invalid_json', 400 );
		}

		return $this->success(
			array(
				'type' => $schema_data['@type'] ?? 'Article',
				'data' => $schema_data,
			)
		);
	}

	// =========================================================================
	// BULK EDITOR
	// =========================================================================

	/**
	 * Get posts for bulk editing.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_posts_for_bulk_edit( $request ) {
		$post_type = sanitize_text_field( $request->get_param( 'post_type' ) ?? 'post' );
		$per_page  = intval( $request->get_param( 'per_page' ) ?? 50 );
		$page      = intval( $request->get_param( 'page' ) ?? 1 );
		$filter    = sanitize_text_field( $request->get_param( 'filter' ) ?? 'all' );
		$search    = sanitize_text_field( $request->get_param( 'search' ) ?? '' );

		$args = array(
			'post_type'      => $post_type,
			'post_status'    => 'publish',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		$query = new \WP_Query( $args );
		$posts = array();

		foreach ( $query->posts as $post ) {
			$meta      = $this->get_canonical_seo_meta( $post->ID );
			$seo_title = $meta['title'];
			$seo_desc  = $meta['description'];

			// Apply filters after reading canonical meta.
			if ( 'missing_title' === $filter && ! empty( $seo_title ) ) {
				continue;
			}
			if ( 'missing_description' === $filter && ! empty( $seo_desc ) ) {
				continue;
			}

			$posts[] = array(
				'id'              => $post->ID,
				'title'           => $post->post_title,
				'slug'            => $post->post_name,
				'status'          => $post->post_status,
				'date'            => $post->post_date,
				'modified'        => $post->post_modified,
				'seo_title'       => $seo_title,
				'seo_description' => $seo_desc,
				'has_seo_title'   => ! empty( $seo_title ),
				'has_seo_desc'    => ! empty( $seo_desc ),
				'edit_url'        => get_edit_post_link( $post->ID, 'raw' ),
				'view_url'        => get_permalink( $post->ID ),
			);
		}

		return $this->success(
			array(
				'posts'        => $posts,
				'total'        => $query->found_posts,
				'pages'        => $query->max_num_pages,
				'current_page' => $page,
			)
		);
	}

	/**
	 * Generate AI suggestions for posts.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function generate_suggestions( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$post_ids = isset( $params['post_ids'] ) ? array_map( 'intval', $params['post_ids'] ) : array();
		$type     = isset( $params['type'] ) ? sanitize_text_field( $params['type'] ) : 'both';

		if ( empty( $post_ids ) ) {
			return $this->error( __( 'No posts selected.', 'saman-seo' ), 'no_posts', 400 );
		}

		$suggestions = array();

		foreach ( $post_ids as $post_id ) {
			$post = get_post( $post_id );
			if ( ! $post ) {
				continue;
			}

			$content = \Saman\SEO\Helpers\strip_content_to_text( $post->post_content );
			$content = substr( $content, 0, 2000 ); // Limit content

			$suggestion = array(
				'post_id' => $post_id,
				'title'   => $post->post_title,
			);

			if ( $type === 'title' || $type === 'both' ) {
				$title_result            = $this->generate_meta( $content, 'title', $post->post_title );
				$suggestion['seo_title'] = $title_result;
			}

			if ( $type === 'description' || $type === 'both' ) {
				$desc_result                   = $this->generate_meta( $content, 'description', $post->post_title );
				$suggestion['seo_description'] = $desc_result;
			}

			$suggestions[] = $suggestion;
		}

		return $this->success( $suggestions );
	}

	/**
	 * Save bulk changes.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_bulk_changes( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$changes = isset( $params['changes'] ) ? $params['changes'] : array();

		if ( empty( $changes ) ) {
			return $this->error( __( 'No changes to save.', 'saman-seo' ), 'no_changes', 400 );
		}

		$saved = 0;

		foreach ( $changes as $change ) {
			$post_id = intval( $change['post_id'] ?? 0 );
			if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
				continue;
			}

			$meta    = $this->get_canonical_seo_meta( $post_id );
			$updated = false;

			if ( isset( $change['seo_title'] ) ) {
				$meta['title'] = sanitize_text_field( $change['seo_title'] );
				$updated       = true;
			}

			if ( isset( $change['seo_description'] ) ) {
				$meta['description'] = sanitize_textarea_field( $change['seo_description'] );
				$updated             = true;
			}

			if ( $updated ) {
				$this->update_canonical_seo_meta( $post_id, $meta );
				++$saved;
			}
		}

		// translators: Placeholder values
		return $this->success( array( 'saved' => $saved ), sprintf( __( '%d posts updated.', 'saman-seo' ), $saved ) );
	}

	/**
	 * Read canonical SEO meta for bulk editor operations.
	 *
	 * @param int $post_id Post ID.
	 * @return array{title:string,description:string}
	 */
	private function get_canonical_seo_meta( $post_id ) {
		$meta = get_post_meta( $post_id, '_SAMAN_SEO_meta', true );

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		return array(
			'title'       => isset( $meta['title'] ) ? sanitize_text_field( $meta['title'] ) : '',
			'description' => isset( $meta['description'] ) ? sanitize_textarea_field( $meta['description'] ) : '',
		);
	}

	/**
	 * Update canonical SEO meta while preserving other keys.
	 *
	 * @param int   $post_id Post ID.
	 * @param array $data    Title/description data to merge.
	 * @return void
	 */
	private function update_canonical_seo_meta( $post_id, $data ) {
		$meta = get_post_meta( $post_id, '_SAMAN_SEO_meta', true );

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		if ( isset( $data['title'] ) ) {
			$meta['title'] = sanitize_text_field( $data['title'] );
		}

		if ( isset( $data['description'] ) ) {
			$meta['description'] = sanitize_textarea_field( $data['description'] );
		}

		update_post_meta( $post_id, '_SAMAN_SEO_meta', $meta );
	}

	// =========================================================================
	// CONTENT GAPS
	// =========================================================================

	/**
	 * Analyze content gaps.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function analyze_content_gaps( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$category_id = isset( $params['category'] ) ? intval( $params['category'] ) : 0;
		$topic       = isset( $params['topic'] ) ? sanitize_text_field( $params['topic'] ) : '';

		// Get existing content
		$args = array(
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => 50,
		);

		if ( $category_id ) {
			$args['cat'] = $category_id;
		}

		$query            = new \WP_Query( $args );
		$existing_titles  = array();
		$existing_content = array();

		foreach ( $query->posts as $post ) {
			$existing_titles[]  = $post->post_title;
			$existing_content[] = array(
				'title'   => $post->post_title,
				'excerpt' => wp_trim_words( $post->post_content, 50 ),
			);
		}

		// Build AI prompt
		$content_summary = implode(
			"\n",
			array_map(
				function ( $t ) {
					return '- ' . $t;
				},
				$existing_titles
			)
		);

		$prompt  = "Analyze this website's existing content and find gaps:\n\n";
		$prompt .= "Existing articles:\n{$content_summary}\n\n";

		if ( $topic ) {
			$prompt .= "Main topic/niche: {$topic}\n\n";
		}

		$prompt .= "Find content gaps and suggest new articles. Return a JSON object with this structure:\n";
		$prompt .= '{"gaps":[{"title":"Title","reason":"Why needed","priority":"high|medium|low","keywords":["kw1","kw2"]}],"clusters":[{"name":"Cluster Name","topics":["Topic 1","Topic 2"]}],"next_post":{"title":"Recommended next article","outline":["Point 1","Point 2"]}}';

		$system = 'You are an SEO content strategist. Analyze content and find gaps. Be specific and actionable. Always respond with valid JSON only, no markdown formatting.';

		$result = $this->call_ai( $system, $prompt );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), 'ai_error', 500 );
		}

		// Parse JSON response
		$data = json_decode( $result, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			// Try to extract JSON from response
			if ( preg_match( '/\{[\s\S]*\}/', $result, $matches ) ) {
				$data = json_decode( $matches[0], true );
			}
		}

		if ( ! $data ) {
			return $this->success(
				array(
					'gaps'      => array(),
					'clusters'  => array(),
					'next_post' => null,
					'raw'       => $result,
				)
			);
		}

		return $this->success( $data );
	}

	/**
	 * Generate content outline.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function generate_outline( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$title    = isset( $params['title'] ) ? sanitize_text_field( $params['title'] ) : '';
		$keywords = isset( $params['keywords'] ) ? array_map( 'sanitize_text_field', $params['keywords'] ) : array();

		if ( empty( $title ) ) {
			return $this->error( __( 'Title is required.', 'saman-seo' ), 'missing_title', 400 );
		}

		$keywords_str = ! empty( $keywords ) ? implode( ', ', $keywords ) : '';

		$prompt = "Create a detailed content outline for an article titled: \"{$title}\"\n\n";
		if ( $keywords_str ) {
			$prompt .= "Target keywords: {$keywords_str}\n\n";
		}
		$prompt .= "Return a JSON object with this structure:\n";
		$prompt .= '{"title":"SEO Title","meta_description":"Meta desc","sections":[{"heading":"H2 heading","subheadings":["H3 1","H3 2"],"key_points":["Point 1","Point 2"]}],"word_count_target":1500,"internal_links_suggestions":["Topic to link to"]}';

		$system = 'You are an SEO content strategist. Create detailed, actionable content outlines. Always respond with valid JSON only.';

		$result = $this->call_ai( $system, $prompt );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), 'ai_error', 500 );
		}

		$data = json_decode( $result, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			if ( preg_match( '/\{[\s\S]*\}/', $result, $matches ) ) {
				$data = json_decode( $matches[0], true );
			}
		}

		if ( ! $data ) {
			return $this->success( array( 'raw' => $result ) );
		}

		return $this->success( $data );
	}

	// =========================================================================
	// SCHEMA BUILDER
	// =========================================================================

	/**
	 * Detect schema type for a post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function detect_schema_type( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$post_id = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0;

		if ( ! $post_id ) {
			return $this->error( __( 'Post ID is required.', 'saman-seo' ), 'missing_post_id', 400 );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error( __( 'Post not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$content = \Saman\SEO\Helpers\strip_content_to_text( $post->post_content );
		$content = substr( $content, 0, 1500 );

		$prompt  = "Analyze this content and determine the best Schema.org type:\n\n";
		$prompt .= "Title: {$post->post_title}\n";
		$prompt .= "Content: {$content}\n\n";
		$prompt .= "Return a JSON object with this structure:\n";
		$prompt .= '{"primary_type":"Article|Recipe|HowTo|FAQPage|Product|LocalBusiness|Event|Review","confidence":"high|medium|low","reason":"Brief explanation","suggested_types":["Type1","Type2"],"detected_elements":{"has_recipe":false,"has_steps":false,"has_faq":false,"has_review":false}}';

		$system = 'You are a Schema.org expert. Detect the best schema type for content. Always respond with valid JSON only.';

		$result = $this->call_ai( $system, $prompt );

		if ( is_wp_error( $result ) ) {
			return $this->error( $result->get_error_message(), 'ai_error', 500 );
		}

		$data = json_decode( $result, true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			if ( preg_match( '/\{[\s\S]*\}/', $result, $matches ) ) {
				$data = json_decode( $matches[0], true );
			}
		}

		if ( ! $data ) {
			return $this->success(
				array(
					'primary_type' => 'Article',
					'confidence'   => 'low',
					'reason'       => 'Could not determine schema type.',
				)
			);
		}

		return $this->success( $data );
	}

	/**
	 * Generate schema markup.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function generate_schema( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$post_id     = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0;
		$schema_type = isset( $params['schema_type'] ) ? sanitize_text_field( $params['schema_type'] ) : 'Article';
		$custom_data = isset( $params['custom_data'] ) ? $params['custom_data'] : array();

		if ( ! $post_id ) {
			return $this->error( __( 'Post ID is required.', 'saman-seo' ), 'missing_post_id', 400 );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->error( __( 'Post not found.', 'saman-seo' ), 'not_found', 404 );
		}

		$content        = \Saman\SEO\Helpers\strip_content_to_text( $post->post_content );
		$content        = substr( $content, 0, 2000 );
		$featured_image = get_the_post_thumbnail_url( $post_id, 'full' );
		$author         = get_the_author_meta( 'display_name', $post->post_author );
		$date_published = get_the_date( 'c', $post );
		$date_modified  = get_the_modified_date( 'c', $post );

		// Build base schema
		$schema = array(
			'@context' => 'https://schema.org',
			'@type'    => $schema_type,
		);

		// Add common fields
		$schema['headline'] = $post->post_title;
		$schema['url']      = get_permalink( $post_id );

		if ( $featured_image ) {
			$schema['image'] = $featured_image;
		}

		if ( $schema_type === 'Article' || $schema_type === 'BlogPosting' || $schema_type === 'NewsArticle' ) {
			$schema['author']        = array(
				'@type' => 'Person',
				'name'  => $author,
			);
			$schema['datePublished'] = $date_published;
			$schema['dateModified']  = $date_modified;
			$logo_url                = get_option( 'SAMAN_SEO_homepage_organization_logo', '' );
			if ( empty( $logo_url ) ) {
				$logo_url = get_site_icon_url();
			}
			$publisher = array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
			);
			if ( ! empty( $logo_url ) ) {
				$publisher['logo'] = $logo_url;
			}
			$schema['publisher'] = $publisher;
		}

		// For other types, use AI to fill in
		if ( in_array( $schema_type, array( 'Recipe', 'HowTo', 'FAQPage', 'Product', 'Review' ), true ) ) {
			$prompt  = "Generate Schema.org {$schema_type} markup for this content:\n\n";
			$prompt .= "Title: {$post->post_title}\n";
			$prompt .= "Content: {$content}\n\n";
			$prompt .= "Return ONLY valid JSON-LD schema. Include all required properties for {$schema_type}.";

			$system = 'You are a Schema.org expert. Generate valid, complete schema markup. Return only JSON, no explanation.';

			$result = $this->call_ai( $system, $prompt );

			if ( ! is_wp_error( $result ) ) {
				$ai_schema = json_decode( $result, true );
				if ( $ai_schema && is_array( $ai_schema ) ) {
					$schema = array_merge( $schema, $ai_schema );
				}
			}
		}

		// Merge custom data
		if ( ! empty( $custom_data ) && is_array( $custom_data ) ) {
			$schema = array_merge( $schema, $custom_data );
		}

		return $this->success(
			array(
				'schema'      => $schema,
				'json_ld'     => wp_json_encode( $schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ),
				'post_id'     => $post_id,
				'schema_type' => $schema_type,
			)
		);
	}

	/**
	 * Validate schema markup.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function validate_schema( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$schema = isset( $params['schema'] ) ? $params['schema'] : '';

		if ( empty( $schema ) ) {
			return $this->error( __( 'Schema is required.', 'saman-seo' ), 'missing_schema', 400 );
		}

		// Basic validation
		$errors   = array();
		$warnings = array();

		if ( is_string( $schema ) ) {
			$schema = json_decode( $schema, true );
			if ( json_last_error() !== JSON_ERROR_NONE ) {
				$errors[] = __( 'Invalid JSON format.', 'saman-seo' );
				return $this->success(
					array(
						'valid'    => false,
						'errors'   => $errors,
						'warnings' => $warnings,
					)
				);
			}
		}

		// Check required fields
		if ( ! isset( $schema['@context'] ) ) {
			$errors[] = __( 'Missing @context (should be https://schema.org).', 'saman-seo' );
		}

		if ( ! isset( $schema['@type'] ) ) {
			$errors[] = __( 'Missing @type.', 'saman-seo' );
		}

		// Type-specific validation
		$type = $schema['@type'] ?? '';

		if ( $type === 'Article' || $type === 'BlogPosting' || $type === 'NewsArticle' ) {
			if ( empty( $schema['headline'] ) ) {
				$errors[] = __( 'Missing headline (required for Article).', 'saman-seo' );
			}
			if ( empty( $schema['image'] ) ) {
				$warnings[] = __( 'Missing image (recommended for Article).', 'saman-seo' );
			}
			if ( empty( $schema['author'] ) ) {
				$warnings[] = __( 'Missing author (recommended for Article).', 'saman-seo' );
			}
			if ( empty( $schema['datePublished'] ) ) {
				$warnings[] = __( 'Missing datePublished (recommended for Article).', 'saman-seo' );
			}
		}

		if ( $type === 'Recipe' ) {
			if ( empty( $schema['name'] ) && empty( $schema['headline'] ) ) {
				$errors[] = __( 'Missing name (required for Recipe).', 'saman-seo' );
			}
			if ( empty( $schema['recipeIngredient'] ) ) {
				$warnings[] = __( 'Missing recipeIngredient (recommended for Recipe).', 'saman-seo' );
			}
			if ( empty( $schema['recipeInstructions'] ) ) {
				$warnings[] = __( 'Missing recipeInstructions (recommended for Recipe).', 'saman-seo' );
			}
		}

		if ( $type === 'FAQPage' ) {
			if ( empty( $schema['mainEntity'] ) ) {
				$errors[] = __( 'Missing mainEntity (required for FAQPage).', 'saman-seo' );
			}
		}

		return $this->success(
			array(
				'valid'    => empty( $errors ),
				'errors'   => $errors,
				'warnings' => $warnings,
			)
		);
	}

	/**
	 * Save schema to post.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_schema( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$post_id = isset( $params['post_id'] ) ? intval( $params['post_id'] ) : 0;
		$schema  = isset( $params['schema'] ) ? $params['schema'] : '';

		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			return $this->error( __( 'Invalid post or permission denied.', 'saman-seo' ), 'permission_denied', 403 );
		}

		if ( empty( $schema ) ) {
			return $this->error( __( 'Schema is required.', 'saman-seo' ), 'missing_schema', 400 );
		}

		// Store as JSON string
		if ( is_array( $schema ) ) {
			$schema = wp_json_encode( $schema );
		}

		update_post_meta( $post_id, '_SAMAN_SEO_schema', $schema );

		return $this->success( null, __( 'Schema saved successfully.', 'saman-seo' ) );
	}

	// =========================================================================
	// ROBOTS.TXT
	// =========================================================================

	/**
	 * Get robots.txt content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_robots_txt( $request ) {
		$content     = get_option( 'SAMAN_SEO_robots_txt', '' );
		$site_url    = home_url();
		$sitemap_url = home_url( '/sitemap.xml' );

		return $this->success(
			array(
				'content'     => $content,
				'site_url'    => $site_url,
				'sitemap_url' => $sitemap_url,
			)
		);
	}

	/**
	 * Save robots.txt content.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function save_robots_txt( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$content = isset( $params['content'] ) ? $params['content'] : '';

		// Sanitize - allow only safe characters
		$content = wp_kses( $content, array() );

		update_option( 'SAMAN_SEO_robots_txt', $content );

		return $this->success(
			array(
				'content' => $content,
			),
			__( 'robots.txt saved successfully.', 'saman-seo' )
		);
	}

	/**
	 * Reset robots.txt to default.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function reset_robots_txt( $request ) {
		delete_option( 'SAMAN_SEO_robots_txt' );

		// Generate default content
		$default = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . home_url( '/sitemap.xml' );

		return $this->success(
			array(
				'content' => $default,
			),
			__( 'robots.txt reset to default.', 'saman-seo' )
		);
	}

	/**
	 * Test a path against robots.txt rules.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function test_robots_txt( $request ) {
		$params = $request->get_json_params();
		if ( empty( $params ) ) {
			$params = $request->get_params();
		}

		$path    = isset( $params['path'] ) ? $params['path'] : '/';
		$content = isset( $params['content'] ) ? $params['content'] : '';

		// Parse robots.txt rules
		$lines          = explode( "\n", $content );
		$rules          = array();
		$current_agents = array();

		foreach ( $lines as $line ) {
			$line = trim( $line );

			// Skip empty lines and comments
			if ( empty( $line ) || strpos( $line, '#' ) === 0 ) {
				continue;
			}

			// Parse directive
			$parts = explode( ':', $line, 2 );
			if ( count( $parts ) !== 2 ) {
				continue;
			}

			$directive = strtolower( trim( $parts[0] ) );
			$value     = trim( $parts[1] );

			if ( $directive === 'user-agent' ) {
				$current_agents = array( $value );
			} elseif ( in_array( $directive, array( 'disallow', 'allow' ), true ) ) {
				foreach ( $current_agents as $agent ) {
					if ( $agent === '*' || strtolower( $agent ) === 'googlebot' ) {
						$rules[] = array(
							'type'  => $directive,
							'path'  => $value,
							'agent' => $agent,
						);
					}
				}
			}
		}

		// Test path against rules (more specific rules take precedence)
		$allowed           = true;
		$matching_rule     = null;
		$best_match_length = -1;

		foreach ( $rules as $rule ) {
			$rule_path = $rule['path'];

			// Empty Disallow means allow all
			if ( $rule['type'] === 'disallow' && empty( $rule_path ) ) {
				continue;
			}

			// Check if rule matches
			if ( strpos( $path, $rule_path ) === 0 || fnmatch( $rule_path . '*', $path ) ) {
				$match_length = strlen( $rule_path );

				if ( $match_length > $best_match_length ) {
					$best_match_length = $match_length;
					$allowed           = ( $rule['type'] === 'allow' );
					$matching_rule     = $rule['type'] . ': ' . $rule_path;
				}
			}
		}

		return $this->success(
			array(
				'path'    => $path,
				'allowed' => $allowed,
				'rule'    => $matching_rule,
			)
		);
	}

	// =========================================================================
	// HELPER METHODS
	// =========================================================================

	/**
	 * Generate meta title or description.
	 *
	 * @param string $content Post content.
	 * @param string $type    Type (title or description).
	 * @param string $title   Post title.
	 * @return string
	 */
	private function generate_meta( $content, $type, $title ) {
		if ( $type === 'title' ) {
			$prompt     = "Write an SEO meta title (max 60 characters) for this content. Be compelling and include the main topic.\n\nPost title: {$title}\nContent: {$content}";
			$max_tokens = 30;
		} else {
			$prompt     = "Write an SEO meta description (max 155 characters) for this content. Summarize and invite clicks.\n\nPost title: {$title}\nContent: {$content}";
			$max_tokens = 80;
		}

		$system = 'You are an SEO expert. Write concise, compelling meta tags. Respond with only the meta text, no quotes or explanation.';

		$result = $this->call_ai( $system, $prompt, $max_tokens );

		if ( is_wp_error( $result ) ) {
			return '';
		}

		// Clean up result
		$result = trim( $result, "\"' \n\r\t" );

		return $result;
	}

	/**
	 * Call AI API via Saman Labs AI integration.
	 *
	 * All AI operations are delegated to Saman Labs AI plugin.
	 *
	 * @param string $system     System prompt.
	 * @param string $prompt     User prompt.
	 * @param int    $max_tokens Max tokens.
	 * @return string|\WP_Error
	 */
	private function call_ai( $system, $prompt, $max_tokens = 500 ) {
		// Check if Saman Labs AI is ready
		if ( ! AI_Pilot::is_ready() ) {
			return new \WP_Error(
				'ai_not_ready',
				__( 'Saman Labs AI is not configured. Please install and configure Saman Labs AI to use AI features.', 'saman-seo' )
			);
		}

		// Build messages array for chat
		$messages = array(
			array(
				'role'    => 'system',
				'content' => $system,
			),
			array(
				'role'    => 'user',
				'content' => $prompt,
			),
		);

		// Delegate to Saman Labs AI
		$result = AI_Pilot::chat(
			$messages,
			array(
				'max_tokens'  => $max_tokens,
				'temperature' => 0.3,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		// Extract content from response
		if ( is_array( $result ) && isset( $result['content'] ) ) {
			return trim( $result['content'] );
		}

		if ( is_string( $result ) ) {
			return trim( $result );
		}

		return new \WP_Error( 'ai_error', __( 'Unexpected AI response format.', 'saman-seo' ) );
	}

	// =========================================================================
	// IMAGE SEO
	// =========================================================================

	/**
	 * Get images from media library with alt text status.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function get_images( $request ) {
		$filter   = sanitize_text_field( $request->get_param( 'filter' ) ?? 'all' );
		$page     = max( 1, intval( $request->get_param( 'page' ) ?? 1 ) );
		$per_page = min( 100, max( 1, intval( $request->get_param( 'per_page' ) ?? 20 ) ) );
		$search   = sanitize_text_field( $request->get_param( 'search' ) ?? '' );

		// Build query args.
		$args = array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'posts_per_page' => $per_page,
			'paged'          => $page,
			'orderby'        => 'date',
			'order'          => 'DESC',
		);

		if ( ! empty( $search ) ) {
			$args['s'] = $search;
		}

		// Filter by alt text status.
		if ( $filter === 'missing' ) {
			$args['meta_query'] = array(
				'relation' => 'OR',
				array(
					'key'     => '_wp_attachment_image_alt',
					'compare' => 'NOT EXISTS',
				),
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '=',
				),
			);
		} elseif ( $filter === 'has-alt' ) {
			$args['meta_query'] = array(
				array(
					'key'     => '_wp_attachment_image_alt',
					'value'   => '',
					'compare' => '!=',
				),
			);
		}

		$query  = new \WP_Query( $args );
		$images = array();

		foreach ( $query->posts as $attachment ) {
			$meta = wp_get_attachment_metadata( $attachment->ID );
			$alt  = get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true );

			$images[] = array(
				'id'        => $attachment->ID,
				'filename'  => basename( get_attached_file( $attachment->ID ) ),
				'url'       => wp_get_attachment_url( $attachment->ID ),
				'thumbnail' => wp_get_attachment_image_url( $attachment->ID, 'thumbnail' ),
				'alt'       => $alt ?: '',
				'title'     => $attachment->post_title,
				'width'     => $meta['width'] ?? 0,
				'height'    => $meta['height'] ?? 0,
				'date'      => $attachment->post_date,
			);
		}

		// Get overall stats.
		$stats = $this->get_image_stats();

		return $this->success(
			array(
				'images'      => $images,
				'total'       => $query->found_posts,
				'total_pages' => $query->max_num_pages,
				'page'        => $page,
				'stats'       => $stats,
			)
		);
	}

	/**
	 * Get image statistics.
	 *
	 * @return array
	 */
	private function get_image_stats() {
		global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
		// Total images.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
		$total = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->posts}
             WHERE post_type = 'attachment'
             AND post_mime_type LIKE 'image/%'
             AND post_status = 'inherit'"
		);
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
		// Images with alt text.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct DB access intentional.
		$with_alt = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT p.ID) FROM {$wpdb->posts} p
             INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
             WHERE p.post_type = 'attachment'
             AND p.post_mime_type LIKE 'image/%'
             AND p.post_status = 'inherit'
             AND pm.meta_key = '_wp_attachment_image_alt'
             AND pm.meta_value != ''"
		);

		return array(
			'total'      => $total,
			'withAlt'    => $with_alt,
			'missingAlt' => $total - $with_alt,
			'emptyAlt'   => 0, // Not tracking separately.
		);
	}

	/**
	 * Update image alt text.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function update_image_alt( $request ) {
		$image_id = intval( $request->get_param( 'id' ) );
		$alt      = sanitize_text_field( $request->get_param( 'alt' ) ?? '' );

		if ( ! $image_id ) {
			return $this->error( __( 'Image ID is required.', 'saman-seo' ), 'missing_id', 400 );
		}

		$attachment = get_post( $image_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return $this->error( __( 'Image not found.', 'saman-seo' ), 'not_found', 404 );
		}

		if ( ! current_user_can( 'edit_post', $image_id ) ) {
			return $this->error( __( 'Permission denied.', 'saman-seo' ), 'permission_denied', 403 );
		}

		update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );

		return $this->success(
			array(
				'id'  => $image_id,
				'alt' => $alt,
			),
			__( 'Alt text updated.', 'saman-seo' )
		);
	}

	/**
	 * Generate alt text from filename.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function generate_image_alt( $request ) {
		$image_id = intval( $request->get_param( 'id' ) );

		if ( ! $image_id ) {
			return $this->error( __( 'Image ID is required.', 'saman-seo' ), 'missing_id', 400 );
		}

		$attachment = get_post( $image_id );
		if ( ! $attachment || $attachment->post_type !== 'attachment' ) {
			return $this->error( __( 'Image not found.', 'saman-seo' ), 'not_found', 404 );
		}

		if ( ! current_user_can( 'edit_post', $image_id ) ) {
			return $this->error( __( 'Permission denied.', 'saman-seo' ), 'permission_denied', 403 );
		}

		// Generate alt text from filename.
		$filename = pathinfo( get_attached_file( $image_id ), PATHINFO_FILENAME );

		// Clean up filename: replace hyphens/underscores with spaces, remove numbers.
		$alt = str_replace( array( '-', '_' ), ' ', $filename );
		$alt = preg_replace( '/\d+/', '', $alt );
		$alt = preg_replace( '/\s+/', ' ', $alt );
		$alt = trim( $alt );
		$alt = ucfirst( strtolower( $alt ) );

		if ( ! empty( $alt ) ) {
			update_post_meta( $image_id, '_wp_attachment_image_alt', $alt );
		}

		return $this->success(
			array(
				'id'  => $image_id,
				'alt' => $alt,
			),
			__( 'Alt text generated from filename.', 'saman-seo' )
		);
	}

	/**
	 * Delete all plugin data (tables, options, and post meta).
	 *
	 * @return \WP_REST_Response
	 */
	public function delete_all_data() {
		global $wpdb;

		if ( ! current_user_can( 'manage_options' ) ) {
			return $this->error( __( 'Permission denied.', 'saman-seo' ), 'permission_denied', 403 );
		}

		$tables = array(
			$wpdb->prefix . 'SAMAN_SEO_redirects',
			$wpdb->prefix . 'SAMAN_SEO_404_log',
			$wpdb->prefix . 'SAMAN_SEO_404_ignore_patterns',
			$wpdb->prefix . 'SAMAN_SEO_link_health',
			$wpdb->prefix . 'SAMAN_SEO_link_scans',
			$wpdb->prefix . 'SAMAN_SEO_indexnow_log',
			$wpdb->prefix . 'SAMAN_SEO_custom_assistants',
			$wpdb->prefix . 'SAMAN_SEO_assistant_usage',
		);

		foreach ( $tables as $table ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Data removal requires direct query.
			$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
		}

		// Delete all SAMAN_SEO options.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct option removal required.
		$options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'SAMAN_SEO_%'" );
		foreach ( $options as $option ) {
			delete_option( $option );
		}

		// Delete all Saman SEO post meta.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Direct meta removal required.
		$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_saman_seo_%'" );

		// Clear caches.
		wp_cache_flush();

		return $this->success( null, __( 'All plugin data has been deleted.', 'saman-seo' ) );
	}
}
