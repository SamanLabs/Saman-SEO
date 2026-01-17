<?php
/**
 * Course Schema service for course schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Course Schema controller.
 */
class Course_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_course_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add Course schema to the JSON-LD graph.
	 *
	 * @param array $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_course_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a course post.
		if ( 'course' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Course schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$course_name        = get_the_title( $post );
		$course_description = get_the_excerpt( $post );

		// In a real implementation, these would come from post meta.
		$provider_name = 'Example University';
		$course_code   = 'CS101';

		if ( empty( $course_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Course',
			'name'        => $course_name,
			'description' => $course_description,
			'provider'    => [
				'@type' => 'Organization',
				'name'  => $provider_name,
			],
			'courseCode'  => $course_code,
		];

		return $schema;
	}
}
