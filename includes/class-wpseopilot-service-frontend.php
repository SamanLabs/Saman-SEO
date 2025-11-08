<?php
/**
 * Frontend rendering of meta tags, Open Graph, JSON-LD, etc.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

use WP_Post;
use function WPSEOPilot\Helpers\breadcrumbs;
use function WPSEOPilot\Helpers\generate_title_from_template;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend output controller.
 */
class Frontend {

	/**
	 * Boot frontend hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! apply_filters( 'wpseopilot_feature_toggle', true, 'frontend_head' ) ) {
			return;
		}

		add_action( 'wp_head', [ $this, 'render_head_tags' ], 1 );
		add_action( 'wp_head', [ $this, 'render_social_tags' ], 5 );
		add_action( 'wp_head', [ $this, 'render_json_ld' ], 20 );
		add_action( 'wp_head', [ $this, 'render_hreflang' ], 8 );
		add_shortcode( 'wpseopilot_breadcrumbs', [ $this, 'breadcrumbs_shortcode' ] );
	}

	/**
	 * Render <title>, meta description, robots, and canonical tags.
	 *
	 * @return void
	 */
	public function render_head_tags() {
		if ( ! is_singular() && ! is_home() && ! is_archive() ) {
			return;
		}

		$post = get_post();
		$meta = $this->get_meta( $post );

		$title = $meta['title'];
		if ( empty( $title ) && $post instanceof WP_Post ) {
			$title = generate_title_from_template( $post );
		}
		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}

		$title = apply_filters( 'wpseopilot_title', $title, $post );

		$description = $meta['description'] ?? get_option( 'wpseopilot_default_meta_description', get_bloginfo( 'description' ) );
		$description = apply_filters( 'wpseopilot_description', $description, $post );

		$canonical = $this->get_canonical( $post, $meta );
		$canonical = apply_filters( 'wpseopilot_canonical', $canonical, $post );

		$robots = $this->get_robots( $meta );

		echo '<title>' . esc_html( $title ) . "</title>\n";

		if ( ! empty( $description ) ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
		}

		if ( ! empty( $canonical ) ) {
			printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
		}

		if ( ! empty( $robots ) ) {
			printf( "<meta name=\"robots\" content=\"%s\" />\n", esc_attr( $robots ) );
		}

	}

	/**
	 * Render Open Graph + Twitter card tags.
	 *
	 * @return void
	 */
	public function render_social_tags() {
		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		$meta = $this->get_meta( $post );
		$url  = $this->get_canonical( $post, $meta );

		$title = apply_filters( 'wpseopilot_og_title', $meta['title'] ?: get_the_title( $post ), $post );
		$description = apply_filters( 'wpseopilot_og_description', $meta['description'] ?: get_option( 'wpseopilot_default_meta_description', '' ), $post );
		$image = $this->get_social_image( $post, $meta );

		$tags = [
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:type'        => 'article',
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:image'       => $image,
			'twitter:card'   => 'summary_large_image',
			'twitter:title'  => $title,
			'twitter:description' => $description,
			'twitter:image'  => $image,
		];

		foreach ( $tags as $property => $value ) {
			if ( empty( $value ) ) {
				continue;
			}

			if ( 0 === strpos( $property, 'og:' ) ) {
				printf( "<meta property=\"%s\" content=\"%s\" />\n", esc_attr( $property ), esc_attr( $value ) );
			} else {
				printf( "<meta name=\"%s\" content=\"%s\" />\n", esc_attr( $property ), esc_attr( $value ) );
			}
		}
	}

	/**
	 * Render JSON-LD payload.
	 *
	 * @return void
	 */
	public function render_json_ld() {
		$post = get_post();

		$payload = apply_filters( 'wpseopilot_jsonld', [], $post );

		if ( empty( $payload ) ) {
			return;
		}

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( $payload )
		);
	}

	/**
	 * Output hreflang alternatives derived from option map.
	 *
	 * @return void
	 */
	public function render_hreflang() {
		$map = get_option( 'wpseopilot_hreflang_map' );
		if ( empty( $map ) ) {
			return;
		}

		$decoded = json_decode( $map, true );
		if ( empty( $decoded ) || ! is_array( $decoded ) ) {
			return;
		}

		foreach ( $decoded as $locale => $url ) {
			printf(
				"<link rel=\"alternate\" hreflang=\"%s\" href=\"%s\" />\n",
				esc_attr( $locale ),
				esc_url( $url )
			);
		}
	}

	/**
	 * Provide breadcrumbs shortcode wrapper.
	 *
	 * @param array $atts Attributes.
	 *
	 * @return string
	 */
	public function breadcrumbs_shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'echo' => 'false',
			],
			$atts
		);

		return breadcrumbs( null, false );
	}

	/**
	 * Resolve canonical URL.
	 *
	 * @param WP_Post|null $post Post.
	 * @param array        $meta Meta.
	 *
	 * @return string
	 */
	private function get_canonical( $post, $meta ) {
		if ( ! empty( $meta['canonical'] ) ) {
			return esc_url_raw( $meta['canonical'] );
		}

		if ( $post instanceof WP_Post ) {
			$url = get_permalink( $post );
		} else {
			$url = home_url( add_query_arg( [] ) );
		}

		if ( is_paged() ) {
			$url = trailingslashit( $url ) . trailingslashit( get_query_var( 'paged' ) );
		}

		return $url;
	}

	/**
	 * Compose robots meta.
	 *
	 * @param array $meta Meta.
	 *
	 * @return string
	 */
	private function get_robots( $meta ) {
		$directives = [];

		if ( ! empty( $meta['noindex'] ) || '1' === get_option( 'wpseopilot_default_noindex' ) ) {
			$directives[] = 'noindex';
		}

		if ( ! empty( $meta['nofollow'] ) || '1' === get_option( 'wpseopilot_default_nofollow' ) ) {
			$directives[] = 'nofollow';
		}

		$global = get_option( 'wpseopilot_global_robots' );
		if ( $global ) {
			$directives = array_merge( $directives, array_map( 'trim', explode( ',', $global ) ) );
		}

		$directives = array_filter( array_unique( array_map( 'trim', $directives ) ) );

		return implode( ', ', $directives );
	}

	/**
	 * Determine OG/Twitter image.
	 *
	 * @param WP_Post|null $post Post.
	 * @param array        $meta Meta.
	 *
	 * @return string
	 */
	private function get_social_image( $post, $meta ) {
		if ( ! empty( $meta['og_image'] ) ) {
			return esc_url_raw( $meta['og_image'] );
		}

		if ( $post instanceof WP_Post ) {
			$image_id = get_post_thumbnail_id( $post );
			if ( $image_id ) {
				$url = wp_get_attachment_image_url( $image_id, 'full' );
				if ( $url ) {
					return $url;
				}
			}
		}

		$fallback = get_option( 'wpseopilot_default_og_image', '' );
		if ( ! empty( $fallback ) ) {
			return $fallback;
		}

		if ( $post instanceof WP_Post ) {
			return add_query_arg(
				[
					'wpseopilot_social_card' => 1,
					'title'                  => get_the_title( $post ),
				],
				home_url( '/' )
			);
		}

		return '';
	}

	/**
	 * Fetch sanitized meta array.
	 *
	 * @param WP_Post|null $post Post.
	 *
	 * @return array
	 */
	private function get_meta( $post ) {
		if ( $post instanceof WP_Post ) {
			$meta = get_post_meta( $post->ID, Post_Meta::META_KEY, true );
		} else {
			$meta = [];
		}

		if ( ! is_array( $meta ) ) {
			$meta = [];
		}

		$defaults = [
			'title'       => '',
			'description' => '',
			'canonical'   => '',
			'noindex'     => '',
			'nofollow'    => '',
			'og_image'    => '',
		];

		return wp_parse_args( $meta, $defaults );
	}
}
