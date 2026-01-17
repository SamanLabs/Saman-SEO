<?php
/**
 * Service Schema service for service schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Service Schema controller.
 */
class Service_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_service_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add Service schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_service_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a service post.
		if ( 'service' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Service schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$service_name = get_the_title( $post );
		$provider_name = get_bloginfo( 'name' );

		// In a real implementation, these would come from post meta.
		$service_type = 'Web Development';
		$area_served  = 'Worldwide';

		if ( empty( $service_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Service',
			'name'        => $service_name,
			'serviceType' => $service_type,
			'provider'    => [
				'@type' => 'Organization',
				'name'  => $provider_name,
			],
			'areaServed'  => $area_served,
		];

		return $schema;
	}
}
