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
	private $youtube_patterns = array(
		// iframe embeds
		'#<iframe[^>]+src=["\'](?:https?:)?//(?:www\.)?youtube\.com/embed/([a-zA-Z0-9_-]{11})[^"\']*["\'][^>]*>#i',
		// youtube.com/watch links
		'#(?:https?:)?//(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#i',
		// youtu.be short links
		'#(?:https?:)?//youtu\.be/([a-zA-Z0-9_-]{11})#i',
		// WordPress embedded video blocks
		'#wp:embed.*?url":"https?://(?:www\.)?youtube\.com/watch\?v=([a-zA-Z0-9_-]{11})#i',
		'#wp:embed.*?url":"https?://youtu\.be/([a-zA-Z0-9_-]{11})#i',
	);

	/**
	 * Vimeo embed patterns.
	 *
	 * @var array
	 */
	private $vimeo_patterns = array(
		// iframe embeds
		'#<iframe[^>]+src=["\'](?:https?:)?//(?:player\.)?vimeo\.com/video/(\d+)[^"\']*["\'][^>]*>#i',
		// vimeo.com links
		'#(?:https?:)?//(?:www\.)?vimeo\.com/(\d+)#i',
		// WordPress embedded video blocks
		'#wp:embed.*?url":"https?://(?:www\.)?vimeo\.com/(\d+)#i',
	);

	/**
	 * Cron hook that fetches oEmbed metadata in the background.
	 *
	 * @var string
	 */
	const OEMBED_FETCH_HOOK = 'saman_seo_fetch_video_oembed';

	/**
	 * Videos seen during this request whose metadata is not cached yet.
	 *
	 * @var array<string,array{platform:string,id:string}>
	 */
	private $pending_oembed = array();

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_video' ) ) {
			return;
		}

		// Match the sibling schema services: canonical hook, priority 20, two
		// args ($graph, $post).
		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_video_schema_to_graph' ), 20, 2 );
		add_action( self::OEMBED_FETCH_HOOK, array( $this, 'fetch_pending_oembed' ) );
	}

	/**
	 * Add VideoObject schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The post the graph is built for.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_video_schema_to_graph( $graph, $post = null ) {
		if ( ! $post instanceof \WP_Post ) {
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

		$this->schedule_oembed_fetch();

		return $graph;
	}

	/**
	 * Detect videos in post content.
	 *
	 * @param \WP_Post $post The post object.
	 * @return array Array of detected videos with platform and ID.
	 */
	public function detect_videos( $post ) {
		$videos  = array();
		$content = $post->post_content;

		// Detect YouTube videos.
		foreach ( $this->youtube_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $video_id ) {
					$videos[] = array(
						'platform' => 'youtube',
						'id'       => $video_id,
					);
				}
			}
		}

		// Detect Vimeo videos.
		foreach ( $this->vimeo_patterns as $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				foreach ( $matches[1] as $video_id ) {
					$videos[] = array(
						'platform' => 'vimeo',
						'id'       => $video_id,
					);
				}
			}
		}

		// Remove duplicates.
		$unique = array();
		$seen   = array();
		foreach ( $videos as $video ) {
			$key = $video['platform'] . '_' . $video['id'];
			if ( ! isset( $seen[ $key ] ) ) {
				$unique[]     = $video;
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
		$schema = array(
			'@type'       => 'VideoObject',
			'name'        => get_the_title( $post ),
			'description' => \Saman\SEO\Helpers\generate_content_snippet( $post, 50 ),
			'uploadDate'  => get_the_date( 'c', $post ),
		);

		// contentUrl is meant for the media file itself. YouTube and Vimeo
		// watch pages are HTML players, so only embedUrl is emitted.
		if ( 'youtube' === $video['platform'] ) {
			$schema['@id']      = get_permalink( $post ) . '#video-youtube-' . $video['id'];
			$schema['embedUrl'] = 'https://www.youtube.com/embed/' . $video['id'];

			// Try to get additional metadata from oEmbed.
			$oembed = $this->get_cached_oembed( 'youtube', $video['id'] );

			$schema['thumbnailUrl'] = $this->get_youtube_thumbnail_url( $video['id'], $oembed );

			if ( is_array( $oembed ) ) {
				if ( ! empty( $oembed['title'] ) ) {
					$schema['name'] = sanitize_text_field( $oembed['title'] );
				}
				$this->add_author( $schema, $oembed );
			}
		} elseif ( 'vimeo' === $video['platform'] ) {
			$schema['@id']      = get_permalink( $post ) . '#video-vimeo-' . $video['id'];
			$schema['embedUrl'] = 'https://player.vimeo.com/video/' . $video['id'];

			// Try to get additional metadata from oEmbed.
			$oembed = $this->get_cached_oembed( 'vimeo', $video['id'] );
			if ( is_array( $oembed ) ) {
				if ( ! empty( $oembed['title'] ) ) {
					$schema['name'] = sanitize_text_field( $oembed['title'] );
				}
				if ( ! empty( $oembed['description'] ) ) {
					$schema['description'] = sanitize_text_field( $oembed['description'] );
				}
				if ( ! empty( $oembed['thumbnail_url'] ) ) {
					$schema['thumbnailUrl'] = esc_url_raw( $oembed['thumbnail_url'] );
				}
				$this->add_author( $schema, $oembed );
				if ( ! empty( $oembed['duration'] ) ) {
					$schema['duration'] = 'PT' . (int) $oembed['duration'] . 'S';
				}
			}
		}

		// Google treats name, thumbnailUrl and uploadDate as required and
		// reports a missing description. Vimeo only supplies a thumbnail via
		// oEmbed, which fails for private videos and on timeouts.
		if ( empty( $schema['thumbnailUrl'] ) ) {
			$schema['thumbnailUrl'] = $this->get_fallback_thumbnail_url( $post );
		}

		if ( '' === trim( (string) $schema['description'] ) ) {
			$excerpt               = trim( wp_strip_all_tags( (string) $post->post_excerpt ) );
			$schema['description'] = '' !== $excerpt ? $excerpt : $schema['name'];
		}

		// Without a thumbnail the node can only produce a critical error.
		if ( empty( $schema['thumbnailUrl'] ) || '' === trim( (string) $schema['name'] ) ) {
			return null;
		}

		return $schema;
	}

	/**
	 * Thumbnail for a video whose platform did not supply one: the post's
	 * featured image, then the site's default social image.
	 *
	 * @param \WP_Post $post The post object.
	 * @return string URL, or empty string when neither exists.
	 */
	private function get_fallback_thumbnail_url( $post ) {
		$thumbnail = (string) get_the_post_thumbnail_url( $post, 'full' );

		if ( '' === $thumbnail ) {
			$thumbnail = (string) get_option( 'SAMAN_SEO_default_og_image', '' );
		}

		return esc_url_raw( $thumbnail );
	}

	/**
	 * Add author and creator from oEmbed channel data.
	 *
	 * The oEmbed response only names who published the video, not whether that is a
	 * person or a company, so the entity stays a Person. VideoObject.creator mirrors
	 * it for consumers that read creator instead of author.
	 *
	 * @param array $schema Schema array by reference.
	 * @param array $oembed oEmbed response data.
	 * @return void
	 */
	private function add_author( array &$schema, array $oembed ) {
		if ( empty( $oembed['author_name'] ) ) {
			return;
		}

		$author = array(
			'@type' => 'Person',
			'name'  => sanitize_text_field( $oembed['author_name'] ),
		);

		if ( ! empty( $oembed['author_url'] ) ) {
			$author_url = esc_url_raw( $oembed['author_url'] );
			if ( '' !== $author_url ) {
				$author['url'] = $author_url;
			}
		}

		$schema['author']  = $author;
		$schema['creator'] = $author;
	}

	/**
	 * Resolve a YouTube thumbnail that is known to exist.
	 *
	 * The maxresdefault.jpg size is missing for many videos (YouTube returns a 404
	 * for it), while hqdefault.jpg is always generated. Only use maxres when
	 * the sitemap enhancer has already confirmed it for this video; never
	 * make a request from here, since this runs while the page renders.
	 *
	 * @param string     $video_id YouTube video ID.
	 * @param array|null $oembed   oEmbed response data, if any.
	 * @return string
	 */
	private function get_youtube_thumbnail_url( $video_id, $oembed = null ) {
		$video_id = sanitize_text_field( (string) $video_id );

		// Shared with the sitemap enhancer's HEAD-checked lookup.
		if ( 'maxres' === get_transient( 'saman_seo_yt_thumb_' . md5( $video_id ) ) ) {
			return 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg';
		}

		if ( is_array( $oembed ) && ! empty( $oembed['thumbnail_url'] ) ) {
			$thumbnail = esc_url_raw( $oembed['thumbnail_url'] );
			if ( '' !== $thumbnail ) {
				return $thumbnail;
			}
		}

		return 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
	}

	/**
	 * Transient key for a video's oEmbed metadata.
	 *
	 * @param string $platform youtube or vimeo.
	 * @param string $video_id Video ID.
	 * @return string
	 */
	private static function oembed_cache_key( $platform, $video_id ) {
		return 'SAMAN_SEO_' . $platform . '_oembed_' . $video_id;
	}

	/**
	 * Read a video's oEmbed metadata from cache, never from the network.
	 *
	 * This runs while the page renders, so a remote request here would add
	 * its full latency to the first view of every post with a video. A miss
	 * is queued for fetch_pending_oembed() and the schema is built from the
	 * post's own title, date and images until the metadata arrives.
	 *
	 * @param string $platform youtube or vimeo.
	 * @param string $video_id Video ID.
	 * @return array|null
	 */
	private function get_cached_oembed( $platform, $video_id ) {
		$cached = get_transient( self::oembed_cache_key( $platform, $video_id ) );

		if ( false === $cached ) {
			$this->pending_oembed[ $platform . ':' . $video_id ] = array(
				'platform' => $platform,
				'id'       => (string) $video_id,
			);

			return null;
		}

		// 'none' is the sentinel for a previously-failed lookup.
		return is_array( $cached ) ? $cached : null;
	}

	/**
	 * Queue one background event for the metadata this request missed.
	 *
	 * @return void
	 */
	private function schedule_oembed_fetch() {
		if ( empty( $this->pending_oembed ) || ! function_exists( 'wp_schedule_single_event' ) ) {
			return;
		}

		$videos               = array_slice( array_values( $this->pending_oembed ), 0, 20 );
		$this->pending_oembed = array();

		if ( ! wp_next_scheduled( self::OEMBED_FETCH_HOOK, array( $videos ) ) ) {
			wp_schedule_single_event( time() + MINUTE_IN_SECONDS, self::OEMBED_FETCH_HOOK, array( $videos ) );
		}
	}

	/**
	 * Cron handler: fetch and cache oEmbed metadata for queued videos.
	 *
	 * @param array $videos List of arrays with platform and id.
	 * @return void
	 */
	public function fetch_pending_oembed( $videos ) {
		foreach ( array_slice( (array) $videos, 0, 20 ) as $video ) {
			$platform = is_array( $video ) ? (string) ( $video['platform'] ?? '' ) : '';
			$video_id = is_array( $video ) ? (string) ( $video['id'] ?? '' ) : '';

			if ( 'youtube' === $platform && preg_match( '/^[A-Za-z0-9_-]{11}$/', $video_id ) ) {
				$url = 'https://www.youtube.com/oembed?format=json&url=' . rawurlencode( 'https://www.youtube.com/watch?v=' . $video_id );
			} elseif ( 'vimeo' === $platform && preg_match( '/^\d+$/', $video_id ) ) {
				$url = 'https://vimeo.com/api/oembed.json?url=' . rawurlencode( 'https://vimeo.com/' . $video_id );
			} else {
				continue;
			}

			$cache_key = self::oembed_cache_key( $platform, $video_id );

			if ( false !== get_transient( $cache_key ) ) {
				continue;
			}

			$response = wp_remote_get( $url, array( 'timeout' => 5 ) );

			if ( is_wp_error( $response ) ) {
				// Network failure: leave uncached so a later view queues it again.
				continue;
			}

			$data = 200 === (int) wp_remote_retrieve_response_code( $response )
				? json_decode( wp_remote_retrieve_body( $response ), true )
				: null;

			if ( is_array( $data ) && ! empty( $data ) ) {
				set_transient( $cache_key, $data, 7 * DAY_IN_SECONDS );
			} else {
				// Private or removed video: do not ask again for a day.
				set_transient( $cache_key, 'none', DAY_IN_SECONDS );
			}
		}
	}

	/**
	 * Get all posts with videos for sitemap generation.
	 *
	 * @param int   $limit      Maximum number of posts to return.
	 * @param array $query_args Extra WP_Query args, e.g. the sitemap's exclusions.
	 * @return array Array of posts with video data.
	 */
	public function get_posts_with_videos( $limit = 1000, array $query_args = array() ) {
		$posts_with_videos = array();

		$args = array_merge(
			$query_args,
			array(
				'post_type'      => 'post',
				'post_status'    => 'publish',
				'posts_per_page' => $limit,
				'orderby'        => 'modified',
				'order'          => 'DESC',
			)
		);

		$query = new \WP_Query( $args );

		if ( $query->have_posts() ) {
			while ( $query->have_posts() ) {
				$query->the_post();
				global $post;

				$videos = $this->detect_videos( $post );

				if ( ! empty( $videos ) ) {
					$posts_with_videos[] = array(
						'post'   => $post,
						'videos' => $videos,
					);
				}
			}
			wp_reset_postdata();
		}

		return $posts_with_videos;
	}
}
