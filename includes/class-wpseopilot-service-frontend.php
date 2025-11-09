<?php
/**
 * Frontend rendering of meta tags, Open Graph, JSON-LD, etc.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

use WP_Post;
use function WPSEOPilot\Helpers\breadcrumbs;
use function WPSEOPilot\Helpers\generate_content_snippet;
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

		$post = $this->get_context_post();
		$meta = $this->get_meta( $post );
		$post_type_descriptions = $this->get_post_type_option( 'wpseopilot_post_type_meta_descriptions' );
		$post_type_keywords     = $this->get_post_type_option( 'wpseopilot_post_type_keywords' );
		$content_snippet        = ( $post instanceof WP_Post ) ? generate_content_snippet( $post ) : '';
		$is_home_view           = is_front_page() || is_home();
		$homepage_title         = $is_home_view ? get_option( 'wpseopilot_homepage_title', '' ) : '';
		$homepage_description   = $is_home_view ? get_option( 'wpseopilot_homepage_description', '' ) : '';
		$homepage_keywords      = $is_home_view ? trim( (string) get_option( 'wpseopilot_homepage_keywords', '' ) ) : '';

		$title = $meta['title'];
		if ( empty( $title ) && $is_home_view && ! empty( $homepage_title ) ) {
			$title = $homepage_title;
		}
		if ( empty( $title ) && $post instanceof WP_Post ) {
			$title = generate_title_from_template( $post );
		}
		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}

		$title = apply_filters( 'wpseopilot_title', $title, $post );

		$description = $meta['description'] ?? '';
		if ( empty( $description ) && $is_home_view && ! empty( $homepage_description ) ) {
			$description = $homepage_description;
		}
		if ( empty( $description ) && $post instanceof WP_Post && ! empty( $post_type_descriptions[ $post->post_type ] ) ) {
			$description = $post_type_descriptions[ $post->post_type ];
		}
		if ( empty( $description ) && ! empty( $content_snippet ) ) {
			$description = $content_snippet;
		}
		if ( empty( $description ) ) {
			$description = get_option( 'wpseopilot_default_meta_description', get_bloginfo( 'description' ) );
		}
		if ( empty( $description ) ) {
			$description = get_bloginfo( 'description' );
		}
		$description = apply_filters( 'wpseopilot_description', $description, $post );

		$canonical = $this->get_canonical( $post, $meta );
		$canonical = apply_filters( 'wpseopilot_canonical', $canonical, $post );

		$robots = $this->get_robots( $meta );
		$keywords = $homepage_keywords;
		if ( empty( $keywords ) && $post instanceof WP_Post && ! empty( $post_type_keywords[ $post->post_type ] ) ) {
			$keywords = $post_type_keywords[ $post->post_type ];
		}
		if ( empty( $keywords ) && $post instanceof WP_Post ) {
			$term_names = [];

			$tags = get_the_tags( $post->ID );
			if ( $tags && ! is_wp_error( $tags ) ) {
				$term_names = array_merge( $term_names, wp_list_pluck( $tags, 'name' ) );
			}

			$categories = get_the_category( $post->ID );
			if ( $categories && ! is_wp_error( $categories ) ) {
				$term_names = array_merge( $term_names, wp_list_pluck( $categories, 'name' ) );
			}

			$term_names = array_filter( array_unique( array_map( 'trim', $term_names ) ) );

			if ( $term_names ) {
				$keywords = implode( ', ', $term_names );
			}
		}
		$keywords = apply_filters( 'wpseopilot_keywords', $keywords, $post );

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

		if ( ! empty( $keywords ) ) {
			printf( "<meta name=\"keywords\" content=\"%s\" />\n", esc_attr( $keywords ) );
		}
	}

	/**
	 * Render Open Graph + Twitter card tags.
	 *
	 * @return void
	 */
	public function render_social_tags() {
		$is_home_view = is_front_page() || is_home();

		if ( ! is_singular() && ! $is_home_view ) {
			return;
		}

		$post = $this->get_context_post();
		$meta = $this->get_meta( $post );
		$url  = $this->get_canonical( $post, $meta );
		$post_type_descriptions = $this->get_post_type_option( 'wpseopilot_post_type_meta_descriptions' );
		$content_snippet        = ( $post instanceof WP_Post ) ? generate_content_snippet( $post ) : '';
		$social_defaults        = $this->get_social_defaults( $post );

		$raw_title = $meta['title'] ?? '';
		if ( $is_home_view && empty( $raw_title ) && ! empty( $social_defaults['og_title'] ) ) {
			$raw_title = $social_defaults['og_title'];
		}
		if ( empty( $raw_title ) && $post instanceof WP_Post ) {
			$raw_title = get_the_title( $post );
		}
		if ( empty( $raw_title ) && ! empty( $social_defaults['og_title'] ) ) {
			$raw_title = $social_defaults['og_title'];
		}
		if ( empty( $raw_title ) ) {
			$raw_title = get_bloginfo( 'name' );
		}
		$title = apply_filters( 'wpseopilot_og_title', $raw_title, $post );

		$description = $meta['description'] ?? '';
		if ( $is_home_view && empty( $description ) && ! empty( $social_defaults['og_description'] ) ) {
			$description = $social_defaults['og_description'];
		}
		if ( empty( $description ) && $post instanceof WP_Post && ! empty( $post_type_descriptions[ $post->post_type ] ) ) {
			$description = $post_type_descriptions[ $post->post_type ];
		}
		if ( empty( $description ) && ! empty( $content_snippet ) ) {
			$description = $content_snippet;
		}
		if ( empty( $description ) ) {
			$description = get_option( 'wpseopilot_default_meta_description', '' );
		}
		if ( empty( $description ) && ! empty( $social_defaults['og_description'] ) ) {
			$description = $social_defaults['og_description'];
		}
		if ( empty( $description ) ) {
			$description = get_bloginfo( 'description' );
		}
		$description = apply_filters( 'wpseopilot_og_description', $description, $post );
		$image = $this->get_social_image( $post, $meta, $social_defaults );

		$twitter_title = $title;
		$twitter_description = $description;

		if ( $is_home_view ) {
			if ( ! empty( $social_defaults['twitter_title'] ) ) {
				$twitter_title = $social_defaults['twitter_title'];
			}
			if ( ! empty( $social_defaults['twitter_description'] ) ) {
				$twitter_description = $social_defaults['twitter_description'];
			}
		} else {
			if ( empty( $twitter_title ) && ! empty( $social_defaults['twitter_title'] ) ) {
				$twitter_title = $social_defaults['twitter_title'];
			}

			if ( empty( $twitter_description ) && ! empty( $social_defaults['twitter_description'] ) ) {
				$twitter_description = $social_defaults['twitter_description'];
			}
		}

		$og_type = sanitize_text_field( $social_defaults['schema_itemtype'] ?? '' );
		if ( empty( $og_type ) ) {
			$og_type = 'article';
		}

		$tags = [
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:type'        => $og_type,
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:image'       => $image,
			'twitter:card'   => 'summary_large_image',
			'twitter:title'  => $twitter_title,
			'twitter:description' => $twitter_description,
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

			$page = (int) get_query_var( 'page' );
			if ( $page > 1 ) {
				$url = trailingslashit( $url ) . user_trailingslashit( $page, 'single_paged' );
			}

			return esc_url_raw( $url );
		}

		$paged = (int) get_query_var( 'paged' );
		if ( $paged < 1 ) {
			$paged = (int) get_query_var( 'page' );
		}

		if ( $paged < 1 ) {
			$paged = 1;
		}

		$url = get_pagenum_link( $paged );

		if ( empty( $url ) ) {
			$url = home_url( '/' );
		}

		return esc_url_raw( $url );
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
	 * @param array        $social_defaults Social defaults.
	 *
	 * @return string
	 */
	private function get_social_image( $post, $meta, $social_defaults = [] ) {
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

		$fallback = $social_defaults['image_source'] ?? '';
		if ( ! empty( $fallback ) ) {
			return esc_url_raw( $fallback );
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
	 * Resolve merged social defaults.
	 *
	 * @param WP_Post|null $post Post.
	 *
	 * @return array<string,string>
	 */
	private function get_social_defaults( $post ) {
		$global = get_option( 'wpseopilot_social_defaults', [] );
		if ( ! is_array( $global ) ) {
			$global = [];
		}

		$global = wp_parse_args(
			array_filter(
				$global,
				static function ( $value ) {
					return null !== $value;
				}
			),
			[
				'og_title'            => '',
				'og_description'      => '',
				'twitter_title'       => '',
				'twitter_description' => '',
				'image_source'        => '',
				'schema_itemtype'     => 'article',
			]
		);

		if ( $post instanceof WP_Post ) {
			$post_type_defaults = $this->get_post_type_option( 'wpseopilot_post_type_social_defaults' );
			if ( isset( $post_type_defaults[ $post->post_type ] ) && is_array( $post_type_defaults[ $post->post_type ] ) ) {
				$per_type = array_filter(
					$post_type_defaults[ $post->post_type ],
					static function ( $value ) {
						return '' !== $value && null !== $value;
					}
				);

				if ( ! empty( $per_type ) ) {
					$global = wp_parse_args( $per_type, $global );
				}
			}
		}

		if ( empty( $global['schema_itemtype'] ) ) {
			$global['schema_itemtype'] = 'article';
		}

		return $global;
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

	/**
	 * Retrieve a sanitized per-post-type option array.
	 *
	 * @param string $option Option name.
	 *
	 * @return array
	 */
	private function get_post_type_option( $option ) {
		$value = get_option( $option, [] );

		return is_array( $value ) ? $value : [];
	}

	/**
	 * Resolve the queried post for the current request context.
	 *
	 * @return WP_Post|null
	 */
	private function get_context_post() {
		if ( is_singular() ) {
			$post = get_post();

			return $post instanceof WP_Post ? $post : null;
		}

		if ( is_home() && ! is_front_page() ) {
			$posts_page_id = (int) get_option( 'page_for_posts' );
			if ( $posts_page_id ) {
				$posts_page = get_post( $posts_page_id );
				if ( $posts_page instanceof WP_Post ) {
					return $posts_page;
				}
			}
		}

		return null;
	}

}
