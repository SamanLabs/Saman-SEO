<?php
/**
 * Video Schema service for video schema optimization.
 *
 * Detects YouTube and Vimeo videos in post content and generates VideoObject schema.
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
	 * YouTube embed patterns.
	 *
	 * @var array
	 */
	private $youtube_patterns = [
		// iframe embeds
		'#<iframe[^>]+src=["\'](?:https?:)?//(?:www\.)?youtube\.com/embed/([a-zA-Z0-9_-]{11})[^"\']*["\'][^>]*>#i',
		// youtube.com/watch links
		'#(?:https?:)?//(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#i',
		// youtu.be short links
		'#(?:https?:)?//youtu\.be/([a-zA-Z0-9_-]{11})#i',
		// WordPress embedded video blocks
		'#wp:embed.*?url":"https?://(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#i',
		'#wp:embed.*?url":"https?://youtu\.be/([a-zA-Z0-9_-]{11})#i',
	];

	/**
	 * Vimeo embed patterns.
	 *
	 * @var array
	 */
	private $vimeo_patterns = [
		// iframe embeds
		'#<iframe[^>]+src=["\'](?:https?:)?//(?:player\.)?vimeo\.com/video/(\d+)[^"\']*["\'][^>]*>#i',
		// vimeo.com links
		'#(?:https?:)?//(?:www\.)?vimeo\.com/(\d+)#i',
		// WordPress embedded video blocks
		'#wp:embed.*?url":"https?://(?:www\.)?vimeo\.com/(\d+)#i',
	];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_video_schema_to_graph' ], 25, 1 );
	}

	/**
	 * Add VideoObject schema to the JSON-LD graph.
	 *
	 * @param array $graph The existing JSON-LD graph.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_video_schema_to_graph( $graph ) {
		if ( ! is_singular() ) {
			return $graph;
		}

		global $post;
		if ( ! $post ) {
			return $graph;
		}

		// Detect videos in content.
		$videos = $this->detect_videos( $post );

		if ( empty( $videos ) ) {
			return $graph;
		}

		// Add schema for each detected video.
		foreach ( $videos as $video ) {
			$schema = $this->build_schema( $video, $post );
			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		return $graph;
	}

	/**
	 * Detect videos in post content.
	 *
	 * @param \WP_Post $post The post object.
	 * @return array Array of detected videos with platform and ID.
	 */
	public function detect_videos( $post ) {
		$videos  = [];
		$content = $post->post_content;

		// Detect YouTube videos.
		foreach ( $this->youtube_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $video_id ) {
					$videos[] = [
						'platform' => 'youtube',
						'id'       => $video_id,
					];
				}
			}
		}

		// Detect Vimeo videos.
		foreach ( $this->vimeo_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $video_id ) {
					$videos[] = [
						'platform' => 'vimeo',
						'id'       => $video_id,
					];
				}
			}
		}

		// Remove duplicates.
		$unique = [];
		$seen   = [];
		foreach ( $videos as $video ) {
			$key = $video['platform'] . '_' . $video['id'];
			if ( ! isset( $seen[ $key ] ) ) {
				$unique[] = $video;
				$seen[ $key ] = true;
			}
		}

		return $unique;
	}

	/**
	 * Build VideoObject schema for a detected video.
	 *
	 * @param array    $video Video data with platform and ID.
	 * @param \WP_Post $post  The post object.
	 * @return array|null
	 */
	private function build_schema( $video, $post ) {
		$schema = [
			'@type'       => 'VideoObject',
			'name'        => get_the_title( $post ),
			'description' => wp_trim_words( wp_strip_all_tags( $post->post_content ), 50 ),
			'uploadDate'  => get_the_date( 'c', $post ),
		];

		if ( 'youtube' === $video['platform'] ) {
			$schema['@id']          = get_permalink( $post ) . '#video-youtube-' . $video['id'];
			$schema['embedUrl']     = 'https://www.youtube.com/embed/' . $video['id'];
			$schema['contentUrl']   = 'https://www.youtube.com/watch?v=' . $video['id'];
			$schema['thumbnailUrl'] = 'https://img.youtube.com/vi/' . $video['id'] . '/maxresdefault.jpg';

			// Try to get additional metadata from oEmbed.
			$oembed = $this->get_youtube_oembed( $video['id'] );
			if ( $oembed ) {
				if ( ! empty( $oembed['title'] ) ) {
					$schema['name'] = $oembed['title'];
				}
				if ( ! empty( $oembed['author_name'] ) ) {
					$schema['author'] = [
						'@type' => 'Person',
						'name'  => $oembed['author_name'],
					];
				}
			}
		} elseif ( 'vimeo' === $video['platform'] ) {
			$schema['@id']        = get_permalink( $post ) . '#video-vimeo-' . $video['id'];
			$schema['embedUrl']   = 'https://player.vimeo.com/video/' . $video['id'];
			$schema['contentUrl'] = 'https://vimeo.com/' . $video['id'];

			// Try to get additional metadata from oEmbed.
			$oembed = $this->get_vimeo_oembed( $video['id'] );
			if ( $oembed ) {
				if ( ! empty( $oembed['title'] ) ) {
					$schema['name'] = $oembed['title'];
				}
				if ( ! empty( $oembed['description'] ) ) {
					$schema['description'] = $oembed['description'];
				}
				if ( ! empty( $oembed['thumbnail_url'] ) ) {
					$schema['thumbnailUrl'] = $oembed['thumbnail_url'];
				}
				if ( ! empty( $oembed['author_name'] ) ) {
					$schema['author'] = [
						'@type' => 'Person',
						'name'  => $oembed['author_name'],
					];
				}
				if ( ! empty( $oembed['duration'] ) ) {
					$schema['duration'] = 'PT' . $oembed['duration'] . 'S';
				}
			}
		}

		return $schema;
	}

	/**
	 * Get YouTube video oEmbed data.
	 *
	 * @param string $video_id YouTube video ID.
	 * @return array|null
	 */
	private function get_youtube_oembed( $video_id ) {
		$cache_key = 'SAMAN_SEO_youtube_oembed_' . $video_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$url = 'https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=' . $video_id . '&format=json';

		$response = wp_remote_get( $url, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $data ) {
			set_transient( $cache_key, $data, DAY_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Get Vimeo video oEmbed data.
	 *
	 * @param string $video_id Vimeo video ID.
	 * @return array|null
	 */
	private function get_vimeo_oembed( $video_id ) {
		$cache_key = 'SAMAN_SEO_vimeo_oembed_' . $video_id;
		$cached    = get_transient( $cache_key );

		if ( false !== $cached ) {
			return $cached;
		}

		$url = 'https://vimeo.com/api/oembed.json?url=https://vimeo.com/' . $video_id;

		$response = wp_remote_get( $url, [ 'timeout' => 5 ] );

		if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
			return null;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $data ) {
			set_transient( $cache_key, $data, DAY_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Get all posts with videos for sitemap generation.
	 *
	 * @param int $limit Maximum number of posts to return.
	 * @return array Array of posts with video data.
	 */
	public function get_posts_with_videos( $limit = 1000 ) {
		$posts_with_videos = [];

		$args = [
			'post_type'      => 'post',
			'post_status'    => 'publish',
			'posts_per_page' => $limit,
			'orderby'        => 'modified',
			'order'          => 'DESC',
		];

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				global $post;

				$videos = $this->detect_videos( $post );

				if ( ! empty( $videos ) ) {
					$posts_with_videos[] = [
						'post'   => $post,
						'videos' => $videos,
					];
				}
			}
			wp_reset_postdata();
		}

		return $posts_with_videos;
	}
}
