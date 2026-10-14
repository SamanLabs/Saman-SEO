<?php
/**
 * Course Schema service for course schema optimization.
 *
 * Emits a Course node for posts of the `course` post type. Optional fields
 * are read from post meta and omitted when empty:
 *
 * - `_saman_seo_course_provider`     Provider (organization) name.
 * - `_saman_seo_course_provider_url` Provider URL (sameAs).
 * - `_saman_seo_course_code`         Course code.
 *
 * Use the `saman_seo_schema_course` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Course Schema controller.
 */
class Course_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_course' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_course_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add Course schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_course_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'course';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'course';
	}

	/**
	 * Build Course schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		return array(
			'@type'       => 'Course',
			'@id'         => $this->get_node_id( $post ),
			'name'        => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description' => $this->get_description( $post ),
			'url'         => $this->get_url( $post ),
			'image'       => $this->get_image( $post ),
			'provider'    => $this->get_entity( $post, 'Organization', 'provider', 'provider_url' ),
			'courseCode'  => $this->get_field( $post, 'code' ),
		);
	}
}
