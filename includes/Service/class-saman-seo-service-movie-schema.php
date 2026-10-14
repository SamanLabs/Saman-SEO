<?php
/**
 * Movie Schema service for movie schema optimization.
 *
 * Emits a Movie node for posts of the `movie` post type. Optional fields
 * are read from post meta and omitted when empty:
 *
 * - `_saman_seo_movie_director`     Director name.
 * - `_saman_seo_movie_director_url` Director URL (sameAs).
 * - `_saman_seo_movie_date_created` Release / creation date (any strtotime() format).
 *
 * The featured image is used as the Movie image. Use the
 * `saman_seo_schema_movie` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Movie Schema controller.
 */
class Movie_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_movie' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_movie_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add Movie schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_movie_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'movie';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'movie';
	}

	/**
	 * Build Movie schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$date_created = '';
		$raw_date     = $this->get_field( $post, 'date_created' );
		if ( '' !== $raw_date ) {
			$timestamp = strtotime( $raw_date );
			if ( false !== $timestamp ) {
				$date_created = gmdate( 'Y-m-d', $timestamp );
			}
		}

		return array(
			'@type'       => 'Movie',
			'@id'         => $this->get_node_id( $post ),
			'name'        => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description' => $this->get_description( $post ),
			'url'         => $this->get_url( $post ),
			'image'       => $this->get_image( $post ),
			'director'    => $this->get_entity( $post, 'Person', 'director', 'director_url' ),
			'dateCreated' => $date_created,
		);
	}
}
