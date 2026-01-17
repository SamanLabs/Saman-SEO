<?php
/**
 * Video Schema service for video schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Video Schema controller.
 */
class Video_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_video_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add VideoObject schema to the JSON-LD graph.
	 *
	 * @param array $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_video_schema_to_graph( $graph, $post ) {
		// For now, let's assume we identify a video post by a custom field or post format.
		// This is just a placeholder for the logic to identify a video post.
		if ( 'video' !== get_post_format( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build VideoObject schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		// Dummy data for now.
		$video_name        = get_the_title( $post );
		$video_description = get_the_excerpt( $post );
		$upload_date       = get_the_date( 'c', $post );

		// In a real implementation, these would come from post meta or other sources.
		$thumbnail_url = 'https://example.com/thumbnail.jpg';
		$duration      = 'PT2M30S';
		$content_url   = 'https://example.com/video.mp4';
		$embed_url     = 'https://example.com/embed/123';

		if ( empty( $video_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'VideoObject',
			'name'        => $video_name,
			'description' => $video_description,
			'thumbnailUrl' => $thumbnail_url,
			'uploadDate'  => $upload_date,
			'duration'    => $duration,
			'contentUrl'  => $content_url,
			'embedUrl'    => $embed_url,
		];

		return $schema;
	}
}
