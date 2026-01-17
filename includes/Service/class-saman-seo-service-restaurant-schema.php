<?php
/**
 * Restaurant Schema service for restaurant schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Restaurant Schema controller.
 */
class Restaurant_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_restaurant_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add Restaurant schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_restaurant_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a restaurant post.
		if ( 'restaurant' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Restaurant schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$restaurant_name = get_the_title( $post );

		// In a real implementation, these would come from post meta.
		$serves_cuisine = 'American';
		$price_range    = '$$';

		if ( empty( $restaurant_name ) ) {
			return null;
		}

		$schema = [
			'@context'       => 'https://schema.org',
			'@type'          => 'Restaurant',
			'name'           => $restaurant_name,
			'servesCuisine'  => $serves_cuisine,
			'priceRange'     => $price_range,
		];

		return $schema;
	}
}
