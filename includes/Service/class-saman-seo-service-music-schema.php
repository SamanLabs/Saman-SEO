<?php
/**
 * Music Schema service for music schema optimization.
 *
 * Emits a MusicAlbum node for posts of the `music` post type. Optional
 * fields are read from post meta and omitted when empty:
 *
 * - `_saman_seo_music_artist`     Artist / group name (byArtist).
 * - `_saman_seo_music_artist_url` Artist URL (sameAs).
 * - `_saman_seo_music_num_tracks` Number of tracks.
 *
 * Use the `saman_seo_schema_music` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Music Schema controller.
 */
class Music_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_music' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_music_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add MusicAlbum schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_music_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'music';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'music';
	}

	/**
	 * Build MusicAlbum schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$num_tracks = absint( $this->get_field( $post, 'num_tracks' ) );

		return array(
			'@type'       => 'MusicAlbum',
			'@id'         => $this->get_node_id( $post ),
			'name'        => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description' => $this->get_description( $post ),
			'url'         => $this->get_url( $post ),
			'image'       => $this->get_image( $post ),
			'byArtist'    => $this->get_entity( $post, 'MusicGroup', 'artist', 'artist_url' ),
			'numTracks'   => $num_tracks > 0 ? $num_tracks : null,
		);
	}
}
