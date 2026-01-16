<?php
/**
 * Frontend rendering of meta tags, Open Graph, JSON-LD, etc.
 *
 * @package SamanLabs\SEO
 */

namespace SamanLabs\SEO\Service;

use WP_Post;
use function SamanLabs\SEO\Helpers\breadcrumbs;
use function SamanLabs\SEO\Helpers\generate_content_snippet;
use function SamanLabs\SEO\Helpers\generate_title_from_template;
use function SamanLabs\SEO\Helpers\replace_template_variables;

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

		// Initialize title handling immediately since we're already past after_setup_theme
		$this->init_title_handling();

		add_action( 'wp_head', [ $this, 'render_head_tags' ], 1 );
		add_action( 'wp_head', [ $this, 'render_social_tags' ], 5 );
		add_action( 'wp_head', [ $this, 'render_json_ld' ], 20 );
		add_action( 'wp_head', [ $this, 'render_hreflang' ], 8 );
		add_action( 'wp_head', [ $this, 'render_pagination_links' ], 9 );
		add_shortcode( 'wpseopilot_breadcrumbs', [ $this, 'breadcrumbs_shortcode' ] );
	}

	/**
	 * Initialize title tag handling.
	 *
	 * @return void
	 */
	public function init_title_handling() {
		// Remove WordPress default title tag generation.
		remove_action( 'wp_head', '_wp_render_title_tag', 1 );

		// Remove theme support for title-tag to prevent conflicts
		remove_theme_support( 'title-tag' );

		// Add our own title tag rendering at the highest priority
		add_action( 'wp_head', [ $this, 'render_plugin_title_tag' ], 0 );

		// Prevent WordPress from generating document title via pre_get_document_title
		add_filter( 'pre_get_document_title', '__return_empty_string', 1 );
	}

	/**
	 * Render <title>, meta description, robots, and canonical tags.
	 *
	 * @return void
	 */
	public function render_head_tags() {
		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
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



		$description = $meta['description'] ?? '';
		if ( empty( $description ) && $is_home_view && ! empty( $homepage_description ) ) {
			$description = $homepage_description;
		}

		// Add archive page description support
		if ( empty( $description ) ) {
			$archive_defaults = $this->get_archive_defaults();
			$archive_type = null;

			if ( is_404() ) {
				$archive_type = '404';
			} elseif ( is_search() ) {
				$archive_type = 'search';
			} elseif ( is_author() ) {
				$archive_type = 'author';
			} elseif ( is_date() ) {
				$archive_type = 'date';
			}

			if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['description_template'] ) ) {
				$description = $archive_defaults[ $archive_type ]['description_template'];
			}
		}

		// Add taxonomy term description support
		if ( empty( $description ) && ( is_category() || is_tag() || is_tax() ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$term_desc = term_description( $term->term_id, $term->taxonomy );
				if ( ! empty( $term_desc ) ) {
					$description = wp_strip_all_tags( $term_desc );
				}
			}
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
		
		// Run Variable Replacer
		$description = replace_template_variables( $description, $post );
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

		// Run Replacer on Keywords too
		$keywords = replace_template_variables( $keywords, $post );
		$keywords = apply_filters( 'wpseopilot_keywords', $keywords, $post );



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
		$canonical_url = $this->get_canonical( $post, $meta );
		$canonical_url = apply_filters( 'wpseopilot_canonical', $canonical_url, $post );

		// OG URL should match canonical URL by default
		$url = apply_filters( 'wpseopilot_og_url', $canonical_url, $post );
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
		
		// Run Replacer
		$raw_title = replace_template_variables( $raw_title, $post );
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
		
		// Run Replacer
		$description = replace_template_variables( $description, $post );
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

		}

		$twitter_title       = apply_filters( 'wpseopilot_twitter_title', $twitter_title, $post );
		$twitter_description = apply_filters( 'wpseopilot_twitter_description', $twitter_description, $post );
		
		// Twitter image is same as OG by default unless overridden here, or if the user wants separate filter content.
		// We use $image for both initially.
		$twitter_image = apply_filters( 'wpseopilot_twitter_image', $image, $post );

		// Determine OG type based on context
		// Homepage and pages should use 'website', only blog posts should use 'article'
		if ( $is_home_view ) {
			$og_type = 'website';
		} elseif ( $post instanceof WP_Post && 'page' === $post->post_type ) {
			$og_type = 'website';
		} elseif ( $post instanceof WP_Post && 'post' === $post->post_type ) {
			$og_type = 'article';
		} else {
			// For other post types, check schema_itemtype from defaults
			$og_type = sanitize_text_field( $social_defaults['schema_itemtype'] ?? 'website' );
		}

		// Allow override via filter
		$og_type = apply_filters( 'wpseopilot_og_type', $og_type, $post );

		$tags = [
			'og:title'       => $title,
			'og:description' => $description,
			'og:url'         => $url,
			'og:type'        => $og_type,
			'og:site_name'   => get_bloginfo( 'name' ),
			'og:image'       => $image,
			'twitter:card'   => 'summary_large_image',
			'twitter:title'       => $twitter_title,
			'twitter:description' => $twitter_description,
			'twitter:image'       => $twitter_image,
		];

		$tags = apply_filters( 'wpseopilot_social_tags', $tags, $post, $meta, $social_defaults );
		$tags = $this->normalize_social_tags( $tags );
		$tags = $this->dedupe_social_tags( $tags );

		foreach ( $tags as $tag ) {
			$property = $tag['property'];
			$value    = $tag['content'];

			if ( empty( $value ) ) {
				continue;
			}

			printf(
				"<meta %s=\"%s\" content=\"%s\" />\n",
				esc_attr( $tag['attr'] ),
				esc_attr( $property ),
				esc_attr( $value )
			);
		}
	}

	/**
	 * Render the plugin's generated <title> tag.
	 *
	 * @return void
	 */
	public function render_plugin_title_tag() {
		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
			return;
		}

		$post = $this->get_context_post();
		$meta = $this->get_meta( $post );
		$is_home_view   = is_front_page() || is_home();
		$homepage_title = $is_home_view ? get_option( 'wpseopilot_homepage_title', '' ) : '';

		$title = $this->resolve_title( $post, $meta, $is_home_view, $homepage_title );

		// Handle archive pages (404, search, author, date)
		$archive_defaults = $this->get_archive_defaults();
		$archive_type = null;

		if ( is_404() ) {
			$archive_type = '404';
		} elseif ( is_search() ) {
			$archive_type = 'search';
		} elseif ( is_author() ) {
			$archive_type = 'author';
		} elseif ( is_date() ) {
			$archive_type = 'date';
		}

		if ( $archive_type ) {
			$title_template = $archive_defaults[ $archive_type ]['title_template'] ?? '';

			if ( ! empty( $title_template ) ) {
				$title = replace_template_variables( $title_template, null );
			} else {
				// Fallback defaults if template is empty
				$separator = get_option( 'wpseopilot_title_separator', '-' );
				if ( '404' === $archive_type ) {
					$title = 'Page Not Found ' . $separator . ' ' . get_bloginfo( 'name' );
				} elseif ( 'search' === $archive_type ) {
					$title = 'Search: ' . get_search_query() . ' ' . $separator . ' ' . get_bloginfo( 'name' );
				} elseif ( 'author' === $archive_type ) {
					$title = get_the_author() . ' ' . $separator . ' ' . get_bloginfo( 'name' );
				} elseif ( 'date' === $archive_type ) {
					$title = get_the_archive_title() . ' ' . $separator . ' ' . get_bloginfo( 'name' );
				}
			}

			$title = apply_filters( 'wpseopilot_title', $title, null );
		}

		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}

		echo '<title>' . esc_html( $title ) . "</title>\n";
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
	 * Render pagination links for multi-page posts.
	 *
	 * @return void
	 */
	public function render_pagination_links() {
		if ( ! is_singular() ) {
			return;
		}

		global $post, $page, $numpages;

		if ( ! $post instanceof WP_Post ) {
			return;
		}

		// Check if this post has multiple pages (using <!--nextpage-->)
		if ( $numpages < 2 ) {
			return;
		}

		$page = (int) get_query_var( 'page' );
		if ( $page < 1 ) {
			$page = 1;
		}

		// Output rel="prev" for page 2 and above
		if ( $page > 1 ) {
			$prev_page = $page - 1;
			$prev_link = $prev_page > 1
				? trailingslashit( get_permalink( $post ) ) . user_trailingslashit( $prev_page, 'single_paged' )
				: get_permalink( $post );

			printf(
				"<link rel=\"prev\" href=\"%s\" />\n",
				esc_url( $prev_link )
			);
		}

		// Output rel="next" for all pages except the last
		if ( $page < $numpages ) {
			$next_page = $page + 1;
			$next_link = trailingslashit( get_permalink( $post ) ) . user_trailingslashit( $next_page, 'single_paged' );

			printf(
				"<link rel=\"next\" href=\"%s\" />\n",
				esc_url( $next_link )
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

		// Check archive defaults for noindex (404, search, author, date)
		$archive_defaults = $this->get_archive_defaults();
		$archive_type = null;

		if ( is_404() ) {
			$archive_type = '404';
		} elseif ( is_search() ) {
			$archive_type = 'search';
		} elseif ( is_author() ) {
			$archive_type = 'author';
		} elseif ( is_date() ) {
			$archive_type = 'date';
		}

		if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['noindex'] ) ) {
			$directives[] = 'noindex';
		}

		// Add noindex for password protected posts
		if ( is_singular() && post_password_required() ) {
			$directives[] = 'noindex';
		}

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

		// Filter the array of directives (e.g. ['noindex', 'nofollow']).
		$directives = apply_filters( 'wpseopilot_robots_array', $directives );

		if ( ! is_array( $directives ) ) {
			$directives = [];
		}

		$robots_string = implode( ', ', $directives );

		// Filter the final string (e.g. 'noindex, nofollow').
		return apply_filters( 'wpseopilot_robots', $robots_string );
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
		$image = '';

		// 1. Check direct post meta override
		if ( ! empty( $meta['og_image'] ) ) {
			$image = esc_url_raw( $meta['og_image'] );
		}

		// 2. Featured Image
		if ( empty( $image ) && $post instanceof WP_Post ) {
			$image_id = get_post_thumbnail_id( $post );
			if ( $image_id ) {
				$url = wp_get_attachment_image_url( $image_id, 'full' );
				if ( $url ) {
					$image = $url;
				}
			}
		}

		// 3. Global Social Defaults / Fallback Source
		if ( empty( $image ) ) {
			$fallback = $social_defaults['image_source'] ?? '';
			if ( ! empty( $fallback ) ) {
				$image = esc_url_raw( $fallback );
			}
		}

		// 4. Site-wide Default
		if ( empty( $image ) ) {
			$fallback = get_option( 'wpseopilot_default_og_image', '' );
			if ( ! empty( $fallback ) ) {
				$image = $fallback;
			}
		}

		// 5. Dynamic Card Generation (last resort)
		if ( empty( $image ) && $post instanceof WP_Post ) {
			$image = add_query_arg(
				[
					'wpseopilot_social_card' => 1,
					'title'                  => get_the_title( $post ),
				],
				home_url( '/' )
			);
		}

		/**
		 * Filter the final Open Graph image URL.
		 *
		 * @param string  $image           The calculated image URL.
		 * @param WP_Post $post            The post object.
		 * @param array   $meta            The custom SEO meta for this post.
		 * @param array   $social_defaults Social default settings.
		 */
		return apply_filters( 'wpseopilot_og_image', $image, $post, $meta, $social_defaults );
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
	 * Resolve a sanitized title string.
	 *
	 * @param WP_Post|null $post Post.
	 * @param array        $meta Meta.
	 * @param bool         $is_home_view Is home/front view.
	 * @param string       $homepage_title Homepage title override.
	 *
	 * @return string
	 */
	private function resolve_title( $post, $meta, $is_home_view, $homepage_title ) {
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

		return apply_filters( 'wpseopilot_title', $title, $post );
	}

	/**
	 * Normalize social meta tags into a consistent list.
	 *
	 * @param mixed $tags Raw tags array.
	 *
	 * @return array<int,array{attr:string,property:string,content:string}>
	 */
	private function normalize_social_tags( $tags ) {
		if ( ! is_array( $tags ) ) {
			return [];
		}

		$normalized = [];

		foreach ( $tags as $key => $value ) {
			if ( is_int( $key ) && is_array( $value ) ) {
				$property = isset( $value['property'] ) ? (string) $value['property'] : '';
				$name     = isset( $value['name'] ) ? (string) $value['name'] : '';
				$content  = isset( $value['content'] ) ? (string) $value['content'] : '';

				if ( '' === $content ) {
					continue;
				}

				if ( '' !== $property ) {
					$normalized[] = [
						'attr'     => 'property',
						'property' => $property,
						'content'  => $content,
					];
				} elseif ( '' !== $name ) {
					$normalized[] = [
						'attr'     => 'name',
						'property' => $name,
						'content'  => $content,
					];
				}

				continue;
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $entry ) {
					if ( '' === $entry || null === $entry ) {
						continue;
					}

					$normalized[] = $this->format_social_tag( (string) $key, (string) $entry );
				}
				continue;
			}

			if ( '' === $value || null === $value ) {
				continue;
			}

			$normalized[] = $this->format_social_tag( (string) $key, (string) $value );
		}

		return $normalized;
	}

	/**
	 * Remove duplicates for single-value social tags.
	 *
	 * @param array<int,array{attr:string,property:string,content:string}> $tags Tags.
	 *
	 * @return array<int,array{attr:string,property:string,content:string}>
	 */
	private function dedupe_social_tags( $tags ) {
		$multi = [
			'og:image',
			'og:image:alt',
			'og:video',
			'og:video:secure_url',
			'og:audio',
			'twitter:image',
		];

		$multi = apply_filters( 'wpseopilot_social_multi_tags', $multi );
		$multi = array_map( 'strtolower', array_filter( array_map( 'strval', (array) $multi ) ) );

		$deduped = [];
		$index_by_key = [];

		foreach ( $tags as $tag ) {
			$key = strtolower( $tag['property'] );
			if ( in_array( $key, $multi, true ) ) {
				$deduped[] = $tag;
				continue;
			}

			if ( isset( $index_by_key[ $key ] ) ) {
				$deduped[ $index_by_key[ $key ] ] = $tag;
				continue;
			}

			$index_by_key[ $key ] = count( $deduped );
			$deduped[] = $tag;
		}

		return $deduped;
	}

	/**
	 * Resolve the attribute + property for a social tag.
	 *
	 * @param string $property Tag key.
	 * @param string $content Tag value.
	 *
	 * @return array{attr:string,property:string,content:string}
	 */
	private function format_social_tag( $property, $content ) {
		$attr = 0 === strpos( $property, 'og:' ) ? 'property' : 'name';

		return [
			'attr'     => $attr,
			'property' => $property,
			'content'  => $content,
		];
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
	 * Get archive defaults with fallback values.
	 *
	 * @return array
	 */
	private function get_archive_defaults() {
		$archive_defaults = get_option( 'wpseopilot_archive_defaults', [] );
		if ( ! is_array( $archive_defaults ) ) {
			$archive_defaults = [];
		}

		// Define default templates for each archive type
		$archive_default_templates = [
			'author' => [
				'noindex'              => '0',
				'title_template'       => '{{author}} {{separator}} {{sitename}}',
				'description_template' => 'Articles written by {{author}}. {{author_bio}}',
			],
			'date'   => [
				'noindex'              => '0',
				'title_template'       => '{{date}} Archives {{separator}} {{sitename}}',
				'description_template' => 'Browse our articles from {{date}}.',
			],
			'search' => [
				'noindex'              => '1',
				'title_template'       => 'Search: {{search_term}} {{separator}} {{sitename}}',
				'description_template' => 'Search results for "{{search_term}}" on {{sitename}}.',
			],
			'404'    => [
				'noindex'              => '1',
				'title_template'       => 'Page Not Found {{separator}} {{sitename}}',
				'description_template' => 'The page you are looking for could not be found.',
			],
		];

		// Merge saved values with defaults (use defaults for empty values)
		foreach ( $archive_default_templates as $type => $defaults ) {
			if ( ! isset( $archive_defaults[ $type ] ) || ! is_array( $archive_defaults[ $type ] ) ) {
				$archive_defaults[ $type ] = $defaults;
			} else {
				// Merge with defaults, but also replace empty strings with defaults
				$archive_defaults[ $type ] = wp_parse_args( $archive_defaults[ $type ], $defaults );

				// Replace empty strings OR old values without variables with default values
				foreach ( $defaults as $key => $default_value ) {
					$current_value = $archive_defaults[ $type ][ $key ] ?? '';
					// Replace if empty OR if it's a template field that doesn't have any {{variables}}
					if ( '' === $current_value || ( in_array( $key, [ 'title_template', 'description_template' ] ) && strpos( $current_value, '{{' ) === false ) ) {
						$archive_defaults[ $type ][ $key ] = $default_value;
					}
				}
			}
		}

		return $archive_defaults;
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
