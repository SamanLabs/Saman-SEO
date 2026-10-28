<?php
/**
 * Frontend rendering of meta tags, Open Graph, JSON-LD, etc.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use WP_Post;
use function Saman\SEO\Helpers\breadcrumbs;
use function Saman\SEO\Helpers\generate_content_snippet;
use function Saman\SEO\Helpers\generate_title_from_template;
use function Saman\SEO\Helpers\render_template_safely;
use function Saman\SEO\Helpers\replace_template_variables;
use function Saman\SEO\Helpers\strip_unreplaced_variables;

defined( 'ABSPATH' ) || exit;

/**
 * Frontend output controller.
 */
class Frontend {

	/**
	 * Whether the active theme already rendered a <title> during template
	 * load (e.g. a hardcoded wp_title()/wp_get_document_title() call in
	 * header.php). When true, our own fallback renderer stays silent.
	 *
	 * @var bool
	 */
	private $theme_renders_title = false;

	/**
	 * Attachment the unfiltered social image was resolved from, as
	 * array( 'url' => string, 'id' => int ).
	 *
	 * @var array{url:string,id:int}
	 */
	private $social_image_source = array(
		'url' => '',
		'id'  => 0,
	);

	/**
	 * Per-request cache of image URL => attachment ID (0 = not local).
	 *
	 * @var array<string,int>
	 */
	private $attachment_id_cache = array();

	/**
	 * Boot frontend hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! saman_seo_apply_filters( 'saman_seo_feature_toggle', true, 'frontend_head' ) ) {
			return;
		}

		// Title handling must run after themes register their title-tag
		// support (during after_setup_theme), otherwise we cannot detect
		// how the theme renders <title> and risk emitting a duplicate tag.
		if ( did_action( 'after_setup_theme' ) ) {
			$this->init_title_handling();
		} else {
			add_action( 'after_setup_theme', array( $this, 'init_title_handling' ), 999 );
		}

		add_action( 'wp_head', array( $this, 'render_head_tags' ), 1 );
		add_action( 'wp_head', array( $this, 'render_social_tags' ), 5 );
		add_action( 'wp_head', array( $this, 'render_json_ld' ), 20 );
		add_action( 'wp_head', array( $this, 'render_hreflang' ), 8 );
		add_action( 'wp_head', array( $this, 'render_pagination_links' ), 9 );

		// Inject SEO robot directives through the core wp_robots API so our
		// directives merge with (instead of replace) everything else that is
		// registered on the same filter by themes or other plugins.
		add_filter( 'wp_robots', array( $this, 'filter_wp_robots' ) );

		if ( \Saman\SEO\Helpers\module_enabled( 'breadcrumbs' ) ) {
			add_shortcode( 'SAMAN_SEO_breadcrumbs', array( $this, 'breadcrumbs_shortcode' ) );
		}
	}

	/**
	 * Initialize title tag handling.
	 *
	 * The plugin never prints its own <title> unless the theme provides no
	 * mechanism at all. Exactly one of these paths produces the single tag:
	 *
	 * 1. Theme declares title-tag support -> core's _wp_render_title_tag()
	 *    calls wp_get_document_title(), which receives the SEO title from
	 *    the pre_get_document_title filter below.
	 * 2. Legacy theme calls wp_title() in header.php -> the wp_title filter
	 *    supplies the full SEO title.
	 * 3. Neither -> our wp_head fallback renderer outputs one tag.
	 *
	 * @return void
	 */
	public function init_title_handling() {
		// Supply the real SEO title to WordPress's document-title API.
		// Themes and plugins that call wp_get_document_title() will now
		// receive the optimized title instead of the default one.
		add_filter( 'pre_get_document_title', array( $this, 'filter_document_title' ), 1 );

		// Legacy shim for themes calling wp_title() directly in header.php.
		add_filter( 'wp_title', array( $this, 'filter_wp_title' ), 1, 3 );

		// Last-resort renderer for themes with neither mechanism.
		add_action( 'wp_head', array( $this, 'render_plugin_title_tag' ), 0 );
	}

	/**
	 * Filter the document title returned by WordPress.
	 *
	 * @param string $title Current title.
	 * @return string
	 */
	public function filter_document_title( $title ) {
		// Someone (usually the theme template) requested the document title
		// before wp_head ran — remember it so our fallback renderer stays
		// quiet and no duplicate <title> is emitted.
		$this->theme_renders_title = true;

		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
			return $title;
		}

		$post = $this->get_context_post();
		$meta = $this->get_meta( $post );

		$computed = $this->build_document_title( $post, $meta );

		if ( '' === trim( $computed ) ) {
			return $title;
		}

		return $computed;
	}

	/**
	 * Filter wp_title() output for legacy themes.
	 *
	 * Old themes print `<title><?php wp_title( '|', true, 'right' ); ?></title>`
	 * directly in header.php. Return the full computed SEO title so the tag
	 * contains exactly one title instead of a concatenated duplicate.
	 *
	 * @param string $title       Current title passed by wp_title().
	 * @param string $sep         Separator argument (unused, kept for signature).
	 * @param string $seplocation Separator location (unused, kept for signature).
	 * @return string
	 */
	public function filter_wp_title( $title, $sep = '', $seplocation = '' ) {
		unset( $sep, $seplocation );

		// The theme renders its own <title> around this call; never add ours.
		$this->theme_renders_title = true;

		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
			return $title;
		}

		$post     = $this->get_context_post();
		$meta     = $this->get_meta( $post );
		$computed = $this->build_document_title( $post, $meta );

		if ( '' === trim( $computed ) ) {
			return $title;
		}

		return $computed;
	}

	/**
	 * Build the final SEO document title for the current request.
	 *
	 * @param WP_Post|null $post Post.
	 * @param array        $meta Meta.
	 * @return string
	 */
	private function build_document_title( $post, $meta ) {
		$is_home_view   = is_front_page() || is_home();
		$homepage_title = $is_home_view ? get_option( 'SAMAN_SEO_homepage_title', '' ) : '';

		$title = $this->resolve_title( $post, $meta, $is_home_view, $homepage_title, false );

		// Default homepage title when no override is saved.
		if ( $is_home_view && empty( $title ) ) {
			$separator = get_option( 'SAMAN_SEO_title_separator', '|' );
			$title     = sprintf(
				/* translators: 1: Home label, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				__( 'Home', 'saman-seo' ),
				$separator,
				get_bloginfo( 'name' )
			);
		}

		// Handle archive pages (404, search, author, date).
		$archive_defaults = $this->get_archive_defaults();
		$archive_type     = $this->get_archive_type();

		if ( $archive_type ) {
			$title_template = $archive_defaults[ $archive_type ]['title_template'] ?? '';

			if ( ! empty( $title_template ) ) {
				$title = render_template_safely( $title_template, null );
			}

			if ( empty( $title ) ) {
				$separator = get_option( 'SAMAN_SEO_title_separator', '|' );

				if ( '404' === $archive_type ) {
					$title = sprintf(
						/* translators: 1: title separator, 2: site name */
						__( 'Page Not Found %1$s %2$s', 'saman-seo' ),
						$separator,
						get_bloginfo( 'name' )
					);
				} elseif ( 'search' === $archive_type ) {
					$title = sprintf(
						/* translators: 1: search query, 2: title separator, 3: site name */
						__( 'Search Results: %1$s %2$s %3$s', 'saman-seo' ),
						get_search_query(),
						$separator,
						get_bloginfo( 'name' )
					);
				} elseif ( 'author' === $archive_type ) {
					$title = sprintf(
						/* translators: 1: author name, 2: title separator, 3: site name */
						__( '%1$s %2$s %3$s', 'saman-seo' ),
						get_the_author(),
						$separator,
						get_bloginfo( 'name' )
					);
				} elseif ( 'date' === $archive_type ) {
					$title = sprintf(
						/* translators: 1: archive title, 2: title separator, 3: site name */
						__( '%1$s %2$s %3$s', 'saman-seo' ),
						get_the_archive_title(),
						$separator,
						get_bloginfo( 'name' )
					);
				}
			}
		}

		// Handle taxonomy and post-type archives not covered above.
		if ( empty( $title ) && ( is_category() || is_tag() || is_tax() || is_post_type_archive() ) ) {
			$separator = get_option( 'SAMAN_SEO_title_separator', '|' );

			if ( is_post_type_archive() ) {
				$title = sprintf(
					/* translators: 1: post type archive title, 2: title separator, 3: site name */
					__( '%1$s %2$s %3$s', 'saman-seo' ),
					post_type_archive_title( '', false ),
					$separator,
					get_bloginfo( 'name' )
				);
			} else {
				$term      = get_queried_object();
				$term_name = ( $term instanceof \WP_Term ) ? $term->name : get_the_archive_title();
				$title     = sprintf(
					/* translators: 1: term name, 2: title separator, 3: site name */
					__( '%1$s %2$s %3$s', 'saman-seo' ),
					$term_name,
					$separator,
					get_bloginfo( 'name' )
				);
			}
		}

		if ( empty( $title ) ) {
			$title = get_bloginfo( 'name' );
		}

		$title = strip_unreplaced_variables( $title );

		// Truncate overly long titles to keep them search-engine friendly.
		$max_length = absint( saman_seo_apply_filters( 'saman_seo_title_max_length', 60 ) );
		if ( $max_length > 0 && function_exists( 'mb_strlen' ) && mb_strlen( $title ) > $max_length ) {
			$title = mb_strimwidth( $title, 0, $max_length, '...' );
		}

		return saman_seo_apply_filters( 'saman_seo_title', $title, $post );
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

		$post                   = $this->get_context_post();
		$meta                   = $this->get_meta( $post );
		$post_type_descriptions = $this->get_post_type_option( 'SAMAN_SEO_post_type_meta_descriptions' );
		$post_type_keywords     = $this->get_post_type_option( 'SAMAN_SEO_post_type_keywords' );
		$content_snippet        = ( $post instanceof WP_Post ) ? generate_content_snippet( $post ) : '';
		$is_home_view           = is_front_page() || is_home();
		$homepage_title         = $is_home_view ? get_option( 'SAMAN_SEO_homepage_title', '' ) : '';
		$homepage_description   = $is_home_view ? get_option( 'SAMAN_SEO_homepage_description', '' ) : '';
		$homepage_keywords      = $is_home_view ? trim( (string) get_option( 'SAMAN_SEO_homepage_keywords', '' ) ) : '';

		$description = $meta['description'] ?? '';
		if ( empty( $description ) && $is_home_view && ! empty( $homepage_description ) ) {
			$description = $homepage_description;
		}

		// Add archive page description support
		if ( empty( $description ) ) {
			$archive_defaults = $this->get_archive_defaults();
			$archive_type     = $this->get_archive_type();

			if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['description_template'] ) ) {
				$description = $archive_defaults[ $archive_type ]['description_template'];
			}
		}

		// Add taxonomy term description support (use raw term description to
		// avoid wptexturize converting sequences such as "...." into ellipsis).
		if ( empty( $description ) && ( is_category() || is_tag() || is_tax() ) ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term && ! empty( $term->description ) ) {
				$description = wp_strip_all_tags( $term->description );
			}
		}

		if ( empty( $description ) && $post instanceof WP_Post && ! empty( $post_type_descriptions[ $post->post_type ] ) ) {
			$description = $post_type_descriptions[ $post->post_type ];
		}
		if ( empty( $description ) && ! empty( $content_snippet ) ) {
			$description = $content_snippet;
		}
		if ( empty( $description ) ) {
			$description = get_option( 'SAMAN_SEO_default_meta_description', get_bloginfo( 'description' ) );
		}
		if ( empty( $description ) ) {
			$description = get_bloginfo( 'description' );
		}

		// Run Variable Replacer with safety gate so raw tokens never leak.
		$description = render_template_safely( $description, $post );
		$description = saman_seo_apply_filters( 'saman_seo_description', $description, $post );

		$canonical = $this->resolve_canonical_url( $post, $meta );

		$keywords = $homepage_keywords;
		if ( empty( $keywords ) && $post instanceof WP_Post && ! empty( $post_type_keywords[ $post->post_type ] ) ) {
			$keywords = $post_type_keywords[ $post->post_type ];
		}
		if ( empty( $keywords ) && $post instanceof WP_Post ) {
			$term_names = array();

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

		// Run Replacer on Keywords too with safety gate.
		$keywords = render_template_safely( $keywords, $post );
		$keywords = saman_seo_apply_filters( 'saman_seo_keywords', $keywords, $post );

		if ( ! empty( $description ) ) {
			printf( "<meta name=\"description\" content=\"%s\" />\n", esc_attr( $description ) );
		}

		if ( ! empty( $canonical ) ) {
			printf( "<link rel=\"canonical\" href=\"%s\" />\n", esc_url( $canonical ) );
			// Core prints its own canonical later in wp_head; keep only ours.
			remove_action( 'wp_head', 'rel_canonical' );
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
		$is_archive   = is_archive() || is_search();

		if ( ! is_singular() && ! $is_home_view && ! $is_archive ) {
			return;
		}

		$post          = $this->get_context_post();
		$meta          = $this->get_meta( $post );
		$canonical_url = $this->resolve_canonical_url( $post, $meta );

		// OG URL should match canonical URL by default.
		$url                    = saman_seo_apply_filters( 'saman_seo_og_url', $canonical_url, $post );
		$post_type_descriptions = $this->get_post_type_option( 'SAMAN_SEO_post_type_meta_descriptions' );
		$content_snippet        = ( $post instanceof WP_Post ) ? generate_content_snippet( $post ) : '';
		$social_defaults        = $this->get_social_defaults( $post );

		if ( $is_archive ) {
			$title               = $this->get_archive_social_title();
			$description         = $this->get_archive_social_description();
			$twitter_title       = $title;
			$twitter_description = $description;
			$og_type             = 'website';
		} else {
			$raw_title = $meta['title'] ?? '';
			if ( $is_home_view && empty( $raw_title ) ) {
				// Prefer the configured homepage SEO title so social titles match
				// the document <title> instead of falling back to the page title.
				$homepage_title = get_option( 'SAMAN_SEO_homepage_title', '' );
				if ( '' !== trim( (string) $homepage_title ) ) {
					$raw_title = $homepage_title;
				}
			}
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

			// Run Replacer with safety gate.
			$raw_title = render_template_safely( $raw_title, $post );
			$title     = saman_seo_apply_filters( 'saman_seo_og_title', $raw_title, $post );

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
				$description = get_option( 'SAMAN_SEO_default_meta_description', '' );
			}
			if ( empty( $description ) && ! empty( $social_defaults['og_description'] ) ) {
				$description = $social_defaults['og_description'];
			}
			if ( empty( $description ) ) {
				$description = get_bloginfo( 'description' );
			}

			// Run Replacer with safety gate.
			$description = render_template_safely( $description, $post );
			$description = saman_seo_apply_filters( 'saman_seo_og_description', $description, $post );

			$twitter_title       = $title;
			$twitter_description = $description;

			if ( $is_home_view ) {
				if ( ! empty( $social_defaults['twitter_title'] ) ) {
					$twitter_title = render_template_safely( $social_defaults['twitter_title'], $post );
				}
				if ( ! empty( $social_defaults['twitter_description'] ) ) {
					$twitter_description = render_template_safely( $social_defaults['twitter_description'], $post );
				}
			} elseif ( empty( $twitter_title ) && ! empty( $social_defaults['twitter_title'] ) ) {
				$twitter_title = render_template_safely( $social_defaults['twitter_title'], $post );
			}

			// Determine OG type based on context.
			// Homepage and pages should use 'website', only blog posts should use 'article'.
			if ( $is_home_view ) {
				$og_type = 'website';
			} elseif ( $post instanceof WP_Post && 'page' === $post->post_type ) {
				$og_type = 'website';
			} elseif ( $post instanceof WP_Post && 'post' === $post->post_type ) {
				$og_type = 'article';
			} else {
				// For other post types, map the configured schema_itemtype to
				// a valid og:type. Only 'article' and 'website' are legal
				// values; anything else (e.g. full IRI strings) falls back to
				// 'website'.
				$og_type = strtolower( trim( (string) ( $social_defaults['schema_itemtype'] ?? '' ) ) );
				if ( ! in_array( $og_type, array( 'article', 'website' ), true ) ) {
					$og_type = 'website';
				}
			}

			// Allow override via filter.
			$og_type = saman_seo_apply_filters( 'saman_seo_og_type', $og_type, $post );
		}

		$twitter_title       = saman_seo_apply_filters( 'saman_seo_twitter_title', $twitter_title, $post );
		$twitter_description = saman_seo_apply_filters( 'saman_seo_twitter_description', $twitter_description, $post );

		$image         = $this->get_social_image( $post, $meta, $social_defaults );
		$twitter_image = saman_seo_apply_filters( 'saman_seo_twitter_image', $image, $post );

		// Width/height/type/alt are only known for local attachments.
		$image_details   = $this->get_social_image_details( self::social_image_url( $image ) );
		$twitter_details = ( $twitter_image === $image ) ? $image_details : $this->get_social_image_details( self::social_image_url( $twitter_image ) );

		$tags = array(
			'og:title'            => $title,
			'og:description'      => $description,
			'og:url'              => $url,
			'og:type'             => $og_type,
			'og:site_name'        => get_bloginfo( 'name' ),
			'og:locale'           => get_locale(),
			'og:image'            => $image,
			'og:image:width'      => $image_details['width'],
			'og:image:height'     => $image_details['height'],
			'og:image:type'       => $image_details['type'],
			'og:image:alt'        => $image_details['alt'],
			'twitter:card'        => 'summary_large_image',
			'twitter:title'       => $twitter_title,
			'twitter:description' => $twitter_description,
			'twitter:image'       => $twitter_image,
			// The alt text describes the image, not the page: omit it
			// rather than repeat the title when the image has none.
			'twitter:image:alt'   => $twitter_details['alt'],
		);

		$tags = saman_seo_apply_filters( 'saman_seo_social_tags', $tags, $post, $meta, $social_defaults );
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
	 * Only used as a last resort: when the theme neither declares title-tag
	 * support nor calls wp_title()/wp_get_document_title() on its own. In
	 * every other case the theme's single tag carries the SEO title via the
	 * document-title filters, and this stays silent to avoid duplicates.
	 *
	 * @return void
	 */
	public function render_plugin_title_tag() {
		if ( $this->theme_renders_title ) {
			return;
		}

		if ( current_theme_supports( 'title-tag' ) || wp_is_block_theme() ) {
			return;
		}

		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
			return;
		}

		$post  = $this->get_context_post();
		$meta  = $this->get_meta( $post );
		$title = $this->build_document_title( $post, $meta );

		// Avoid outputting an empty title tag.
		if ( '' === trim( $title ) ) {
			$title = get_bloginfo( 'name' );
		}

		echo '<title>' . esc_html( $title ) . "</title>\n";
	}

	/**
	 * Build a social title for archive pages.
	 *
	 * @return string
	 */
	private function get_archive_social_title() {
		$archive_defaults = $this->get_archive_defaults();
		$archive_type     = $this->get_archive_type();

		if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['title_template'] ) ) {
			$title = render_template_safely( $archive_defaults[ $archive_type ]['title_template'], null );
			if ( ! empty( $title ) ) {
				return saman_seo_apply_filters( 'saman_seo_og_title', $title, null );
			}
		}

		$separator = get_option( 'SAMAN_SEO_title_separator', '|' );

		if ( is_search() ) {
			$title = sprintf(
				/* translators: 1: search query, 2: title separator, 3: site name */
				__( 'Search Results: %1$s %2$s %3$s', 'saman-seo' ),
				get_search_query(),
				$separator,
				get_bloginfo( 'name' )
			);
		} elseif ( is_author() ) {
			$title = sprintf(
				/* translators: 1: author name, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				get_the_author(),
				$separator,
				get_bloginfo( 'name' )
			);
		} elseif ( is_date() ) {
			$title = sprintf(
				/* translators: 1: archive title, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				get_the_archive_title(),
				$separator,
				get_bloginfo( 'name' )
			);
		} elseif ( is_category() || is_tag() || is_tax() ) {
			$term      = get_queried_object();
			$term_name = ( $term instanceof \WP_Term ) ? $term->name : get_the_archive_title();
			$title     = sprintf(
				/* translators: 1: term name, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				$term_name,
				$separator,
				get_bloginfo( 'name' )
			);
		} elseif ( is_post_type_archive() ) {
			$title = sprintf(
				/* translators: 1: post type archive title, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				post_type_archive_title( '', false ),
				$separator,
				get_bloginfo( 'name' )
			);
		} else {
			$title = sprintf(
				/* translators: 1: archive title, 2: title separator, 3: site name */
				__( '%1$s %2$s %3$s', 'saman-seo' ),
				get_the_archive_title(),
				$separator,
				get_bloginfo( 'name' )
			);
		}

		return saman_seo_apply_filters( 'saman_seo_og_title', $title, null );
	}

	/**
	 * Build a social description for archive pages.
	 *
	 * @return string
	 */
	private function get_archive_social_description() {
		$archive_defaults = $this->get_archive_defaults();
		$archive_type     = $this->get_archive_type();

		if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['description_template'] ) ) {
			$description = render_template_safely( $archive_defaults[ $archive_type ]['description_template'], null );
			if ( ! empty( $description ) ) {
				return saman_seo_apply_filters( 'saman_seo_og_description', $description, null );
			}
		}

		if ( is_category() || is_tag() || is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$term_desc = term_description( $term->term_id );
				if ( ! empty( $term_desc ) ) {
					return saman_seo_apply_filters( 'saman_seo_og_description', wp_strip_all_tags( $term_desc ), null );
				}
			}
		}

		$description = get_option( 'SAMAN_SEO_default_meta_description', '' );
		if ( empty( $description ) ) {
			$description = get_bloginfo( 'description' );
		}

		return saman_seo_apply_filters( 'saman_seo_og_description', $description, null );
	}

	/**
	 * Render JSON-LD payload.
	 *
	 * @return void
	 */
	public function render_json_ld() {
		// Master switch for structured data output. Admin-side schema
		// previews keep working because they build graphs via REST instead
		// of going through wp_head.
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema' ) ) {
			return;
		}

		// Only a singular view describes a single post. On archives, the blog
		// home and search results get_post() is merely the first post of the
		// loop, so post-specific nodes (Article, FAQ, HowTo, Recipe, Video,
		// Breadcrumb, ...) must not be built from it.
		$post      = is_singular() ? $this->get_context_post() : null;
		$canonical = '';

		if ( is_singular() || is_home() || is_archive() || is_search() || is_404() ) {
			// Same URL the <link rel="canonical"> tag prints, so WebPage
			// url/@id follow the per-post canonical override.
			$canonical_post = $this->get_context_post();
			$canonical      = $this->resolve_canonical_url( $canonical_post, $this->get_meta( $canonical_post ) );
		}

		$payload = saman_seo_apply_filters( 'saman_seo_jsonld', array(), $post, (string) $canonical );

		if ( empty( $payload ) ) {
			return;
		}

		printf(
			"<script type=\"application/ld+json\">%s</script>\n",
			wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP )
		);
	}

	/**
	 * Output hreflang alternatives derived from option map.
	 *
	 * @return void
	 */
	public function render_hreflang() {
		$map = get_option( 'SAMAN_SEO_hreflang_map' );
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
			array(
				'echo' => 'false',
			),
			$atts
		);

		$echo = filter_var( $atts['echo'], FILTER_VALIDATE_BOOLEAN );

		if ( $echo ) {
			breadcrumbs( null, true );
			return '';
		}

		return breadcrumbs( null, false );
	}

	/**
	 * Resolve the canonical URL exactly as the head tag prints it.
	 *
	 * Shared by the canonical <link>, og:url and the JSON-LD graph so all
	 * three agree, including the per-post canonical override.
	 *
	 * @param WP_Post|null $post Post.
	 * @param array        $meta Meta.
	 *
	 * @return string
	 */
	private function resolve_canonical_url( $post, $meta ) {
		$canonical = $this->get_canonical( $post, $meta );

		return saman_seo_apply_filters( 'saman_seo_canonical', $canonical, $post );
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

			// Some custom post types (or a `post_type_link` filter) return the
			// unpretty "plain" permalink (e.g. ?post_type=community&p=123) during
			// wp_head even when a pretty permalink structure is active. Never emit
			// that as a canonical; fall back to the request URL WordPress already
			// resolved, which is the pretty permalink being viewed.
			if ( $this->is_plain_permalink( $url ) ) {
				$resolved = $this->get_resolved_request_url();
				if ( ! empty( $resolved ) ) {
					$url = $resolved;
				}
			}

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
	 * Determine whether a URL is a "plain" (unpretty) permalink.
	 *
	 * These carry a post identifier in the query string
	 * (?p=, ?page_id=, ?post_type=, ?attachment_id=) and must never be used
	 * as a canonical when a pretty permalink is expected.
	 *
	 * @param string $url URL to inspect.
	 *
	 * @return bool
	 */
	private function is_plain_permalink( $url ) {
		if ( empty( $url ) ) {
			return true;
		}

		$query = wp_parse_url( $url, PHP_URL_QUERY );
		if ( empty( $query ) ) {
			return false;
		}

		parse_str( $query, $args );

		foreach ( array( 'p', 'page_id', 'post_type', 'attachment_id' ) as $var ) {
			if ( isset( $args[ $var ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Build the canonical URL from the request WordPress already resolved.
	 *
	 * @return string Absolute URL, or empty string when the request path is unknown.
	 */
	private function get_resolved_request_url() {
		global $wp;

		if ( isset( $wp->request ) && '' !== $wp->request ) {
			return home_url( user_trailingslashit( $wp->request ) );
		}

		return '';
	}

	/**
	 * Merge SEO robot directives into WordPress's wp_robots API.
	 *
	 * Core prints a single <meta name="robots"> tag from everything
	 * registered on the wp_robots filter. Injecting here keeps directives
	 * added by themes and other plugins intact instead of replacing the tag
	 * with our own.
	 *
	 * @param array $robots Directives registered via the wp_robots API.
	 *
	 * @return array
	 */
	public function filter_wp_robots( $robots ) {
		$robots = is_array( $robots ) ? $robots : array();

		if ( ! is_singular() && ! is_home() && ! is_archive() && ! is_search() && ! is_404() ) {
			return $robots;
		}

		$post = $this->get_context_post();
		$meta = $this->get_meta( $post );

		foreach ( $this->get_robots_directives( $meta ) as $directive ) {
			// index/follow are implicit defaults; emitting them explicitly
			// only creates conflict pairs inside the merged tag.
			if ( 'index' === $directive || 'follow' === $directive ) {
				continue;
			}

			// "max-snippet:-1" style directives map to key => value so they
			// merge with core's identical keys instead of duplicating them.
			if ( false !== strpos( $directive, ':' ) ) {
				list( $key, $value ) = array_map( 'trim', explode( ':', $directive, 2 ) );
				$robots[ $key ]      = $value;
				continue;
			}

			$robots[ $directive ] = true;
		}

		// Negative directives win over any positive ones registered earlier.
		if ( ! empty( $robots['noindex'] ) ) {
			unset( $robots['index'] );
		}
		if ( ! empty( $robots['nofollow'] ) ) {
			unset( $robots['follow'] );
		}

		// nosnippet forbids snippets outright; a max-snippet length beside it
		// is contradictory.
		if ( ! empty( $robots['nosnippet'] ) ) {
			unset( $robots['max-snippet'] );
		}

		return $robots;
	}

	/**
	 * Compose robots meta string (legacy string path).
	 *
	 * @param array $meta Meta.
	 *
	 * @return string
	 */
	private function get_robots( $meta ) {
		return implode( ', ', $this->get_robots_directives( $meta ) );
	}

	/**
	 * Build the sanitized list of robots directives for the current request.
	 *
	 * @param array $meta Meta.
	 *
	 * @return array<int,string>
	 */
	private function get_robots_directives( $meta ) {
		$directives = array();

		// Check archive defaults for noindex (404, search, author, date)
		$archive_defaults = $this->get_archive_defaults();
		$archive_type     = $this->get_archive_type();

		if ( $archive_type && ! empty( $archive_defaults[ $archive_type ]['noindex'] ) ) {
			$directives[] = 'noindex';
		}

		// Add noindex for password protected posts
		if ( is_singular() && post_password_required() ) {
			$directives[] = 'noindex';
		}

		if ( ! empty( $meta['noindex'] ) || '1' === get_option( 'SAMAN_SEO_default_noindex' ) ) {
			$directives[] = 'noindex';
		}

		if ( ! empty( $meta['nofollow'] ) || '1' === get_option( 'SAMAN_SEO_default_nofollow' ) ) {
			$directives[] = 'nofollow';
		}

		// Per-post snippet controls. These also govern how the page may be
		// shown in Google AI Overviews / AI Mode, so they sit at the top
		// precedence tier and override the global setting.
		$directives = array_merge( $directives, $this->get_post_snippet_directives( $meta ) );

		$directives = array_values( array_filter( array_unique( array_map( 'trim', $directives ) ) ) );

		// Precedence: per-post / request directives (above) > global setting >
		// hard-coded defaults. A lower tier never overrides a key an upper tier
		// already set, so a global "max-snippet:50" survives the default
		// "max-snippet:-1".
		$global = get_option( 'SAMAN_SEO_global_robots' );
		if ( $global && is_string( $global ) ) {
			$directives = $this->merge_robots_directives( $directives, explode( ',', $global ) );
		}

		// Default advanced directives (can be filtered or disabled).
		$advanced = saman_seo_apply_filters(
			'saman_seo_robots_advanced',
			array(
				'max-snippet:-1',
				'max-image-preview:large',
				'max-video-preview:-1',
			)
		);
		if ( is_array( $advanced ) ) {
			$directives = $this->merge_robots_directives( $directives, $advanced );
		}

		// Filter the array of directives (e.g. ['noindex', 'nofollow']).
		$directives = saman_seo_apply_filters( 'saman_seo_robots_array', $directives );

		if ( ! is_array( $directives ) ) {
			$directives = array();
		}

		$directives = array_filter( array_unique( array_map( 'trim', $directives ) ) );

		// Default to index, follow when no negative directive is present.
		if ( ! in_array( 'noindex', $directives, true ) && ! in_array( 'index', $directives, true ) ) {
			$directives[] = 'index';
		}
		if ( ! in_array( 'nofollow', $directives, true ) && ! in_array( 'follow', $directives, true ) ) {
			$directives[] = 'follow';
		}

		$robots_string = implode( ', ', $directives );

		// Filter the final string (e.g. 'noindex, nofollow').
		$robots_string = saman_seo_apply_filters( 'saman_seo_robots', $robots_string );

		// Sanitize the final directive list: remove duplicates and conflicting pairs.
		$final_directives = array_filter( array_unique( array_map( 'trim', explode( ',', $robots_string ) ) ) );
		if ( in_array( 'noindex', $final_directives, true ) ) {
			$final_directives = array_diff( $final_directives, array( 'index' ) );
		}
		if ( in_array( 'nofollow', $final_directives, true ) ) {
			$final_directives = array_diff( $final_directives, array( 'follow' ) );
		}
		if ( in_array( 'nosnippet', $final_directives, true ) ) {
			$final_directives = array_filter(
				$final_directives,
				static function ( $directive ) {
					return 'max-snippet' !== self::robots_directive_key( $directive );
				}
			);
		}

		return array_values( $final_directives );
	}

	/**
	 * Robots directives from the per-post snippet controls.
	 *
	 * Empty values inherit the global setting and add nothing here.
	 *
	 * @param array $meta Post SEO meta.
	 *
	 * @return array<int,string>
	 */
	private function get_post_snippet_directives( $meta ) {
		$directives = array();

		if ( ! empty( $meta['nosnippet'] ) ) {
			$directives[] = 'nosnippet';
		} elseif ( isset( $meta['max_snippet'] ) && is_numeric( $meta['max_snippet'] ) && '' !== trim( (string) $meta['max_snippet'] ) ) {
			$directives[] = 'max-snippet:' . max( -1, (int) $meta['max_snippet'] );
		}

		$preview = isset( $meta['max_image_preview'] ) ? strtolower( trim( (string) $meta['max_image_preview'] ) ) : '';
		if ( in_array( $preview, array( 'none', 'standard', 'large' ), true ) ) {
			$directives[] = 'max-image-preview:' . $preview;
		}

		if ( ! empty( $meta['noimageindex'] ) ) {
			$directives[] = 'noimageindex';
		}

		return $directives;
	}

	/**
	 * Append lower-precedence robots directives without overriding keys
	 * already present.
	 *
	 * "max-snippet:50" and "max-snippet:-1" share the key "max-snippet", so
	 * only the first (higher-precedence) one is kept. Within one tier,
	 * nosnippet and max-snippet may both appear; the final sanitization lets
	 * nosnippet win.
	 *
	 * @param array<int,string> $directives Higher-precedence directives.
	 * @param array             $additional Lower-precedence directives.
	 *
	 * @return array<int,string>
	 */
	private function merge_robots_directives( array $directives, array $additional ) {
		$upper = array();
		foreach ( $directives as $directive ) {
			$upper[ self::robots_precedence_key( $directive ) ] = true;
		}

		$seen = array();
		foreach ( $additional as $directive ) {
			$directive = trim( (string) $directive );
			if ( '' === $directive ) {
				continue;
			}

			$key = self::robots_directive_key( $directive );
			if ( isset( $upper[ self::robots_precedence_key( $directive ) ] ) || isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$directives[] = $directive;
		}

		return $directives;
	}

	/**
	 * Key of a robots directive: the part before ':' ("max-snippet" for
	 * "max-snippet:50"), lowercased.
	 *
	 * @param string $directive Directive.
	 *
	 * @return string
	 */
	private static function robots_directive_key( $directive ) {
		$parts = explode( ':', (string) $directive, 2 );

		return strtolower( trim( $parts[0] ) );
	}

	/**
	 * Key used to decide which precedence tier owns a directive.
	 *
	 * The nosnippet and max-snippet directives control the same thing
	 * (snippet length), so they share a key: a per-post "max-snippet:160"
	 * overrides a global "nosnippet" and a per-post "nosnippet" overrides a
	 * global "max-snippet:50".
	 *
	 * @param string $directive Directive.
	 *
	 * @return string
	 */
	private static function robots_precedence_key( $directive ) {
		$key = self::robots_directive_key( $directive );

		return 'nosnippet' === $key ? 'max-snippet' : $key;
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
	private function get_social_image( $post, $meta, $social_defaults = array() ) {
		$image = '';

		$this->social_image_source = array(
			'url' => '',
			'id'  => 0,
		);

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

					// Known attachment: no URL lookup needed later.
					$this->social_image_source = array(
						'url' => (string) $url,
						'id'  => (int) $image_id,
					);
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
			$fallback = get_option( 'SAMAN_SEO_default_og_image', '' );
			if ( ! empty( $fallback ) ) {
				$image = $fallback;
			}
		}

		// 5. Dynamic Card Generation (last resort)
		if ( empty( $image ) && $post instanceof WP_Post ) {
			$image = Social_Card_Generator::card_url( get_the_title( $post ) );
		}

		/**
		 * Filter the final Open Graph image URL.
		 *
		 * @param string  $image           The calculated image URL.
		 * @param WP_Post $post            The post object.
		 * @param array   $meta            The custom SEO meta for this post.
		 * @param array   $social_defaults Social default settings.
		 */
		return saman_seo_apply_filters( 'saman_seo_og_image', $image, $post, $meta, $social_defaults );
	}

	/**
	 * Single image URL from a (possibly filtered) social image value.
	 *
	 * The image filters may return something other than a string. An array
	 * with a 'url' key is accepted; anything else yields '' so no image
	 * details are looked up.
	 *
	 * @param mixed $image Image value.
	 * @return string
	 */
	private static function social_image_url( $image ) {
		if ( is_string( $image ) ) {
			return $image;
		}

		if ( is_array( $image ) && isset( $image['url'] ) && is_string( $image['url'] ) ) {
			return $image['url'];
		}

		return '';
	}

	/**
	 * Width, height, MIME type and alt text of a social image.
	 *
	 * Only local attachments are resolved; for anything else (external
	 * URLs, the dynamic social card) every value is empty so the tags are
	 * omitted.
	 *
	 * @param string $url Image URL.
	 *
	 * @return array{width:string,height:string,type:string,alt:string}
	 */
	private function get_social_image_details( $url ) {
		$details = array(
			'width'  => '',
			'height' => '',
			'type'   => '',
			'alt'    => '',
		);

		$attachment_id = $this->get_image_attachment_id( $url );
		if ( ! $attachment_id || ! function_exists( 'wp_get_attachment_image_src' ) ) {
			return $details;
		}

		$width  = 0;
		$height = 0;
		$type   = (string) get_post_mime_type( $attachment_id );

		$full = wp_get_attachment_image_src( $attachment_id, 'full' );
		if ( is_array( $full ) && ! empty( $full[0] ) && $this->same_image_url( (string) $full[0], $url ) ) {
			$width  = isset( $full[1] ) ? (int) $full[1] : 0;
			$height = isset( $full[2] ) ? (int) $full[2] : 0;
		} else {
			// The URL may point at an intermediate size (image-1024x576.jpg).
			$metadata = wp_get_attachment_metadata( $attachment_id );
			$basename = wp_basename( (string) wp_parse_url( $url, PHP_URL_PATH ) );

			if ( is_array( $metadata ) && ! empty( $metadata['sizes'] ) && is_array( $metadata['sizes'] ) ) {
				foreach ( $metadata['sizes'] as $size ) {
					if ( is_array( $size ) && isset( $size['file'] ) && $basename === $size['file'] ) {
						$width  = isset( $size['width'] ) ? (int) $size['width'] : 0;
						$height = isset( $size['height'] ) ? (int) $size['height'] : 0;
						if ( ! empty( $size['mime-type'] ) ) {
							$type = (string) $size['mime-type'];
						}
						break;
					}
				}
			}
		}

		if ( $width > 0 && $height > 0 ) {
			$details['width']  = (string) $width;
			$details['height'] = (string) $height;
		}

		if ( 0 === strpos( $type, 'image/' ) ) {
			$details['type'] = $type;
		}

		$alt            = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
		$details['alt'] = is_string( $alt ) ? trim( wp_strip_all_tags( $alt ) ) : '';

		return $details;
	}

	/**
	 * Resolve the attachment ID behind an image URL.
	 *
	 * Reuses the ID the image was resolved from when available, skips URLs
	 * outside the uploads directory, and caches lookups (per request and in
	 * the object cache) because attachment_url_to_postid() runs a query.
	 *
	 * @param string $url Image URL.
	 *
	 * @return int Attachment ID, or 0.
	 */
	private function get_image_attachment_id( $url ) {
		$url = trim( (string) $url );
		if ( '' === $url ) {
			return 0;
		}

		if ( $this->social_image_source['id'] && $url === $this->social_image_source['url'] ) {
			return (int) $this->social_image_source['id'];
		}

		if ( isset( $this->attachment_id_cache[ $url ] ) ) {
			return $this->attachment_id_cache[ $url ];
		}

		$this->attachment_id_cache[ $url ] = 0;

		if ( ! function_exists( 'attachment_url_to_postid' ) || ! function_exists( 'wp_get_upload_dir' ) ) {
			return 0;
		}

		$uploads = wp_get_upload_dir();
		$baseurl = isset( $uploads['baseurl'] ) ? (string) $uploads['baseurl'] : '';
		if ( '' === $baseurl || 0 !== strpos( $this->strip_url_scheme( $url ), $this->strip_url_scheme( trailingslashit( $baseurl ) ) ) ) {
			return 0;
		}

		$cache_key = 'social_image_id_' . md5( $url );
		$cached    = wp_cache_get( $cache_key, 'saman_seo' );
		if ( false !== $cached ) {
			$this->attachment_id_cache[ $url ] = (int) $cached;

			return (int) $cached;
		}

		$attachment_id = (int) attachment_url_to_postid( $url );

		// Intermediate sizes (image-1024x576.jpg) are not stored as the
		// attached file; retry with the original file name.
		if ( ! $attachment_id ) {
			$original = preg_replace( '/-\d+x\d+(\.[a-z0-9]+)$/i', '$1', $url );
			if ( is_string( $original ) && $original !== $url ) {
				$attachment_id = (int) attachment_url_to_postid( $original );
			}
		}

		wp_cache_set( $cache_key, $attachment_id, 'saman_seo', DAY_IN_SECONDS );
		$this->attachment_id_cache[ $url ] = $attachment_id;

		return $attachment_id;
	}

	/**
	 * Compare two image URLs ignoring the scheme.
	 *
	 * @param string $a First URL.
	 * @param string $b Second URL.
	 *
	 * @return bool
	 */
	private function same_image_url( $a, $b ) {
		return $this->strip_url_scheme( $a ) === $this->strip_url_scheme( $b );
	}

	/**
	 * Remove the scheme from a URL ("https://x" => "//x").
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function strip_url_scheme( $url ) {
		return (string) preg_replace( '#^https?:#i', '', (string) $url );
	}

	/**
	 * Resolve merged social defaults.
	 *
	 * @param WP_Post|null $post Post.
	 *
	 * @return array<string,string>
	 */
	private function get_social_defaults( $post ) {
		$global = get_option( 'SAMAN_SEO_social_defaults', array() );
		if ( ! is_array( $global ) ) {
			$global = array();
		}

		$global = wp_parse_args(
			array_filter(
				$global,
				static function ( $value ) {
					return null !== $value;
				}
			),
			array(
				'og_title'            => '',
				'og_description'      => '',
				'twitter_title'       => '',
				'twitter_description' => '',
				'image_source'        => '',
				'schema_itemtype'     => 'article',
			)
		);

		if ( $post instanceof WP_Post ) {
			$post_type_defaults = $this->get_post_type_option( 'SAMAN_SEO_post_type_social_defaults' );
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
	private function resolve_title( $post, $meta, $is_home_view, $homepage_title, $apply_filter = true ) {
		$title = $meta['title'];
		if ( empty( $title ) && $is_home_view && ! empty( $homepage_title ) ) {
			$title = $homepage_title;
		}
		if ( empty( $title ) && $post instanceof WP_Post ) {
			// generate_title_from_template() already calls the safe replacer.
			$title = generate_title_from_template( $post );
		} elseif ( ! empty( $title ) ) {
			// Per-post `$meta['title']` and the homepage option can both
			// contain {{tokens}} or %tokens% — render safely to prevent leaks.
			$title = render_template_safely( $title, $post );
		}

		if ( $apply_filter ) {
			return saman_seo_apply_filters( 'saman_seo_title', $title, $post );
		}

		return $title;
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
			return array();
		}

		$normalized = array();

		foreach ( $tags as $key => $value ) {
			if ( is_int( $key ) && is_array( $value ) ) {
				$property = isset( $value['property'] ) ? (string) $value['property'] : '';
				$name     = isset( $value['name'] ) ? (string) $value['name'] : '';
				$content  = isset( $value['content'] ) ? strip_unreplaced_variables( (string) $value['content'] ) : '';

				if ( '' === $content ) {
					continue;
				}

				if ( '' !== $property ) {
					$normalized[] = array(
						'attr'     => 'property',
						'property' => $property,
						'content'  => $content,
					);
				} elseif ( '' !== $name ) {
					$normalized[] = array(
						'attr'     => 'name',
						'property' => $name,
						'content'  => $content,
					);
				}

				continue;
			}

			if ( is_array( $value ) ) {
				foreach ( $value as $entry ) {
					if ( '' === $entry || null === $entry ) {
						continue;
					}

					$normalized[] = $this->format_social_tag( (string) $key, strip_unreplaced_variables( (string) $entry ) );
				}
				continue;
			}

			if ( '' === $value || null === $value ) {
				continue;
			}

			$normalized[] = $this->format_social_tag( (string) $key, strip_unreplaced_variables( (string) $value ) );
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
		$multi = array(
			'og:image',
			'og:image:alt',
			'og:video',
			'og:video:secure_url',
			'og:audio',
			'twitter:image',
		);

		$multi = saman_seo_apply_filters( 'saman_seo_social_multi_tags', $multi );
		$multi = array_map( 'strtolower', array_filter( array_map( 'strval', (array) $multi ) ) );

		$deduped      = array();
		$index_by_key = array();

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
			$deduped[]            = $tag;
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

		return array(
			'attr'     => $attr,
			'property' => $property,
			'content'  => $content,
		);
	}

	/**
	 * Fetch sanitized meta array.
	 *
	 * @param WP_Post|null $post Post.
	 *
	 * @return array
	 */
	private function get_meta( $post ) {
		$meta = array();

		if ( $post instanceof WP_Post ) {
			$meta = get_post_meta( $post->ID, Post_Meta::META_KEY, true );

			// Backward compatibility with the previous WP SEO Pilot meta key.
			if ( empty( $meta ) ) {
				$legacy = get_post_meta( $post->ID, '_wpseopilot_meta', true );
				if ( ! empty( $legacy ) && is_array( $legacy ) ) {
					$meta = $legacy;
				}
			}
		}

		if ( ! is_array( $meta ) ) {
			$meta = array();
		}

		$defaults = array(
			'title'       => '',
			'description' => '',
			'canonical'   => '',
			'noindex'     => '',
			'nofollow'    => '',
			'og_image'    => '',
		);

		return wp_parse_args( $meta, $defaults );
	}

	/**
	 * Resolve the current archive-like context.
	 *
	 * @return string|null One of '404', 'search', 'author', 'date', or null
	 *                     when the request is not one of these contexts.
	 */
	private function get_archive_type() {
		if ( is_404() ) {
			return '404';
		}
		if ( is_search() ) {
			return 'search';
		}
		if ( is_author() ) {
			return 'author';
		}
		if ( is_date() ) {
			return 'date';
		}

		return null;
	}

	/**
	 * Get archive defaults with fallback values.
	 *
	 * @return array
	 */
	private function get_archive_defaults() {
		$archive_defaults = get_option( 'SAMAN_SEO_archive_defaults', array() );
		if ( ! is_array( $archive_defaults ) ) {
			$archive_defaults = array();
		}

		// Define default templates for each archive type
		$archive_default_templates = array(
			'author' => array(
				'noindex'              => '0',
				'title_template'       => '{{author}} {{separator}} {{sitename}}',
				'description_template' => 'Articles written by {{author}}. {{author_bio}}',
			),
			'date'   => array(
				'noindex'              => '0',
				'title_template'       => '{{date}} Archives {{separator}} {{sitename}}',
				'description_template' => 'Browse our articles from {{date}}.',
			),
			'search' => array(
				'noindex'              => '1',
				'title_template'       => 'Search Results: {{search_term}} {{separator}} {{sitename}}',
				'description_template' => 'Search results for "{{search_term}}" on {{sitename}}.',
			),
			'404'    => array(
				'noindex'              => '1',
				'title_template'       => 'Page Not Found {{separator}} {{sitename}}',
				'description_template' => 'The page you are looking for could not be found.',
			),
		);

		// Merge saved values with defaults (use defaults for empty values)
		foreach ( $archive_default_templates as $type => $defaults ) {
			if ( ! isset( $archive_defaults[ $type ] ) || ! is_array( $archive_defaults[ $type ] ) ) {
				$archive_defaults[ $type ] = $defaults;
			} else {
				// Merge with defaults, but also replace empty strings with defaults
				$archive_defaults[ $type ] = wp_parse_args( $archive_defaults[ $type ], $defaults );

				// Fill only empty values: a saved template without {{variables}} ("Page not
				// found") is a choice, and %legacy% tokens still render.
				foreach ( $defaults as $key => $default_value ) {
					$current_value = $archive_defaults[ $type ][ $key ] ?? '';
					if ( '' === $current_value ) {
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
		$value = get_option( $option, array() );

		return is_array( $value ) ? $value : array();
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

		if ( is_front_page() ) {
			$front_page_id = (int) get_option( 'page_on_front' );
			if ( $front_page_id ) {
				$front_page = get_post( $front_page_id );
				if ( $front_page instanceof WP_Post ) {
					return $front_page;
				}
			}
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
