<?php
/**
 * Music Schema service for music schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Music Schema controller.
 */
class Music_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_music_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add MusicGroup schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_music_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a music post.
		if ( 'music' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build MusicGroup schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$album_name = get_the_title( $post );
		$artist_name = get_the_author_meta( 'display_name', $post->post_author );

		// In a real implementation, these would come from post meta.
		$num_tracks = 12;

		if ( empty( $album_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'MusicAlbum',
			'name'        => $album_name,
			'byArtist'    => [
				'@type' => 'MusicGroup',
				'name'  => $artist_name,
			],
			'numTracks'   => $num_tracks,
		];

		return $schema;
	}
}
