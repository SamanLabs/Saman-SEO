<?php
/**
 * Software Schema service for software application schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Software Schema controller.
 */
class Software_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_software_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add SoftwareApplication schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_software_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a software post.
		if ( 'software' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build SoftwareApplication schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$software_name = get_the_title( $post );

		// In a real implementation, these would come from post meta.
		$operating_system    = 'Windows, macOS, Linux';
		$application_category = 'Productivity';

		if ( empty( $software_name ) ) {
			return null;
		}

		$schema = [
			'@context'             => 'https://schema.org',
			'@type'                => 'SoftwareApplication',
			'name'                 => $software_name,
			'operatingSystem'      => $operating_system,
			'applicationCategory' => $application_category,
		];

		return $schema;
	}
}
