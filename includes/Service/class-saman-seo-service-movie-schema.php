<?php
/**
 * Movie Schema service for movie schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Movie Schema controller.
 */
class Movie_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_movie_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add Movie schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_movie_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a movie post.
		if ( 'movie' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Movie schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$movie_name = get_the_title( $post );
		$director_name = 'Example Director';

		if ( empty( $movie_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Movie',
			'name'        => $movie_name,
			'director'    => [
				'@type' => 'Person',
				'name'  => $director_name,
			],
			'dateCreated' => get_the_date( 'c', $post ),
		];

		return $schema;
	}
}
