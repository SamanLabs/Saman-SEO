<?php
/**
 * Shared helper functions.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Helpers {

	use WP_Post;

	defined( 'ABSPATH' ) || exit;

	/**
	 * Fetch option with default fallback.
	 *
	 * @param string $key Option name.
	 * @param mixed  $default Default.
	 *
	 * @return mixed
	 */
	function get_option( $key, $default = '' ) {
		$value = \get_option( $key, $default );

		return '' === $value ? $default : $value;
	}

	/**
	 * Fetch SEO meta for a post with sane defaults.
	 *
	 * @param int|WP_Post $post Post or ID.
	 *
	 * @return array{
	 *     title:string,
	 *     description:string,
	 *     canonical:string,
	 *     noindex:string,
	 *     nofollow:string,
	 *     og_image:string
	 * }
	 */
	function get_post_meta( $post ) {
		$post = \get_post( $post );

		if ( ! $post ) {
			return [
				'title'       => '',
				'description' => '',
				'canonical'   => '',
				'noindex'     => '',
				'nofollow'    => '',
				'og_image'    => '',
			];
		}

		$meta = (array) \get_post_meta( $post->ID, '_wpseopilot_meta', true );

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
	 * Replace template variables with context data.
	 *
	 * @param string $template Template string.
	 * @param mixed  $context  Context (Post, Term, or null).
	 *
	 * @return string
	 */
	function replace_template_variables( $template, $context = null ) {
		if ( empty( $template ) ) {
			return '';
		}

		if ( ! class_exists( 'Twiglet\Twiglet' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'src/Twiglet.php';
		}

		// Generic Global Replacements
		$vars = [
			'site_title'    => \get_bloginfo( 'name' ),
			'sitename'      => \get_bloginfo( 'name' ), // Add sitename here too
			'tagline'       => \get_bloginfo( 'description' ),
			'separator'     => get_option( 'wpseopilot_title_separator', '-' ),
			'current_year'  => date_i18n( 'Y' ),
			'current_month' => date_i18n( 'F' ),
			'current_day'   => date_i18n( 'j' ),
		];

		// Context Specific
		if ( $context instanceof WP_Post ) {
			$vars['post_title']   = \wp_strip_all_tags( $context->post_title );
			$vars['post_excerpt'] = \wp_strip_all_tags( \get_the_excerpt( $context ) );
			$vars['post_date']    = get_the_date( '', $context );
			$vars['post_author']  = get_the_author_meta( 'display_name', $context->post_author );
			$vars['modified']     = get_the_modified_date( '', $context );
			$vars['id']           = $context->ID;
			
			$cats = get_the_category( $context->ID );
			$vars['category'] = ( ! empty( $cats ) && ! is_wp_error( $cats ) ) ? $cats[0]->name : '';

			// Custom Fields: {{cf_key_name}}
			// Prefetch all meta for context or rely on lazy fetching in regex? 
			// Twiglet regex is generic so we need to pass data. 
			// For dynamic custom fields, we might need to pre-populate common ones or just rely on regex match in Twiglet if extended?
			// Twiglet simple implementation relies on passed $vars.
			// Let's populate *known* custom fields if possible, or add a catch-all?
			// Since Twiglet is strict on passed vars, we should fetch what we can.
			$all_meta = get_post_meta( $context->ID );
			foreach ( $all_meta as $k => $v ) {
				if ( ! is_protected_meta( $k, 'post' ) ) {
					if ( is_array( $v ) ) {
						$val = isset( $v[0] ) ? $v[0] : '';
					} else {
						$val = $v;
					}
					if ( is_serialized( $val ) ) {
						$val = implode( ', ', maybe_unserialize( $val ) );
					}
					$vars[ 'cf_' . $k ] = $val;
				}
			}

		} elseif ( is_category() || is_tag() || is_tax() ) {
			// Tax context
			$term = $context instanceof \WP_Term ? $context : get_queried_object();
			if ( $term instanceof \WP_Term ) {
				$vars['term_title']       = $term->name;
				$vars['term']             = $term->name; // Alias for term_title
				$vars['term_description'] = wp_strip_all_tags( term_description( $term->term_id ) );
			}
		} elseif ( is_post_type_archive() ) {
			$vars['archive_title'] = post_type_archive_title( '', false );
		} elseif ( is_date() ) {
			$vars['archive_date']  = get_the_date();
			$vars['archive_title'] = get_the_archive_title();
		} elseif ( is_author() ) {
			$vars['author_name'] = get_the_author();
			$vars['author_bio']  = get_the_author_meta( 'description' );
		} elseif ( is_404() ) {
			// 404 page variables
			$vars['request_url'] = isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( home_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) ) : '';
		} elseif ( is_search() ) {
			// Search results variables
			$vars['search_term'] = get_search_query();
		}

		$twiglet = new \Twiglet\Twiglet();
		return $twiglet->render_string( $template, $vars );
	}

	/**
	 * Determine default title using template tags.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 *
	 * @return string
	 */
	function generate_title_from_template( $post ) {
		$post = \get_post( $post );

		if ( ! $post ) {
			return '';
		}

		$post_type_templates = \get_option( 'wpseopilot_post_type_title_templates', [] );
		if ( ! is_array( $post_type_templates ) ) {
			$post_type_templates = [];
		}

		if ( ! empty( $post->post_type ) && ! empty( $post_type_templates[ $post->post_type ] ) ) {
			$template = $post_type_templates[ $post->post_type ];
		} else {
			$template = get_option( 'wpseopilot_default_title_template', '{{post_title}} | {{site_title}}' );
		}

		return replace_template_variables( $template, $post );
	}

	/**
	 * Generate a trimmed snippet from post content.
	 *
	 * @param WP_Post|int $post  Post object or ID.
	 * @param int         $words Number of words.
	 *
	 * @return string
	 */
	function generate_content_snippet( $post, $words = 30 ) {
		$post = \get_post( $post );

		if ( ! $post instanceof WP_Post ) {
			return '';
		}

		$content = $post->post_content;
		if ( \has_blocks( $post ) ) {
			$content = \strip_shortcodes( $content );
		}

		$content = preg_replace( '/<!--(.|\s)*?-->/', ' ', $content );
		$content = \wp_strip_all_tags( $content );
		$content = trim( preg_replace( '/\s+/', ' ', $content ) );

		if ( empty( $content ) ) {
			$content = \wp_strip_all_tags( $post->post_excerpt ?: \get_the_excerpt( $post ) );
		}

		if ( empty( $content ) ) {
			return '';
		}

		return \wp_trim_words( $content, $words );
	}

	/**
	 * Calculate a simple SEO score for a post.
	 *
	 * @param WP_Post|int $post Post object or ID.
	 *
	 * @return array{
	 *     score:int,
	 *     level:string,
	 *     label:string,
	 *     summary:string,
	 *     metrics:array<int,array{
	 *         key:string,
	 *         label:string,
	 *         issue_label:string,
	 *         status:string,
	 *         score:int,
	 *         max:int,
	 *         is_pass:bool
	 *     }>
	 * }
	 */
	function calculate_seo_score( $post ) {
		$post = \get_post( $post );

		$default_summary = \__( 'Add content to generate a score.', 'wp-seo-pilot' );
		$default_result  = [
			'score'   => 0,
			'level'   => 'low',
			'label'   => \__( 'Needs attention', 'wp-seo-pilot' ),
			'summary' => $default_summary,
			'metrics' => [],
		];

		if ( ! $post instanceof WP_Post ) {
			return $default_result;
		}

		$meta        = get_post_meta( $post );
		$title_text  = trim( $meta['title'] ?: $post->post_title );
		$desc_text   = trim( $meta['description'] );
		$content_raw = (string) $post->post_content;

		$content_html = $content_raw;

		if ( function_exists( 'do_blocks' ) && \has_blocks( $post ) ) {
			$content_html = \do_blocks( $content_html );
		}

		if ( function_exists( 'do_shortcode' ) ) {
			$content_html = \do_shortcode( $content_html );
		}

		$content_html = \wpautop( $content_html );
		$content_html = \wp_kses_post( $content_html );

		$strlen = static function ( $value ) {
			return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
		};

		$metrics     = [];
		$total_score = 0;

		// Title length metric (max 20).
		$title_length = $strlen( $title_text );
		if ( 0 === $title_length ) {
			$title_score  = 0;
			$title_status = \__( 'Add a meta title for this post.', 'wp-seo-pilot' );
		} elseif ( $title_length < 30 ) {
			$title_score  = 10;
			$title_status = sprintf(
				/* translators: %d is the title length in characters. */
				\__( 'Length: %d chars (too short).', 'wp-seo-pilot' ),
				$title_length
			);
		} elseif ( $title_length <= 65 ) {
			$title_score  = 20;
			$title_status = sprintf(
				/* translators: %d is the title length in characters. */
				\__( 'Length: %d chars (ideal).', 'wp-seo-pilot' ),
				$title_length
			);
		} elseif ( $title_length <= 80 ) {
			$title_score  = 15;
			$title_status = sprintf(
				/* translators: %d is the title length in characters. */
				\__( 'Length: %d chars (slightly long).', 'wp-seo-pilot' ),
				$title_length
			);
		} else {
			$title_score  = 10;
			$title_status = sprintf(
				/* translators: %d is the title length in characters. */
				\__( 'Length: %d chars (too long).', 'wp-seo-pilot' ),
				$title_length
			);
		}

		$metrics[] = [
			'key'         => 'title',
			'label'       => \__( 'Title length', 'wp-seo-pilot' ),
			'issue_label' => \__( 'Title', 'wp-seo-pilot' ),
			'status'      => $title_status,
			'score'       => $title_score,
			'max'         => 20,
			'is_pass'     => $title_score >= 18,
		];
		$total_score += $title_score;

		// Meta description completeness (max 20).
		$desc_length = $strlen( $desc_text );
		if ( 0 === $desc_length ) {
			$desc_score  = 0;
			$desc_status = \__( 'Add a custom meta description.', 'wp-seo-pilot' );
		} elseif ( $desc_length < 80 ) {
			$desc_score  = 10;
			$desc_status = sprintf(
				/* translators: %d is the description length in characters. */
				\__( 'Length: %d chars (extend toward 155).', 'wp-seo-pilot' ),
				$desc_length
			);
		} elseif ( $desc_length <= 160 ) {
			$desc_score  = 20;
			$desc_status = sprintf(
				/* translators: %d is the description length in characters. */
				\__( 'Length: %d chars (ideal).', 'wp-seo-pilot' ),
				$desc_length
			);
		} elseif ( $desc_length <= 200 ) {
			$desc_score  = 15;
			$desc_status = sprintf(
				/* translators: %d is the description length in characters. */
				\__( 'Length: %d chars (trim slightly).', 'wp-seo-pilot' ),
				$desc_length
			);
		} else {
			$desc_score  = 10;
			$desc_status = sprintf(
				/* translators: %d is the description length in characters. */
				\__( 'Length: %d chars (too long).', 'wp-seo-pilot' ),
				$desc_length
			);
		}

		$metrics[] = [
			'key'         => 'meta_description',
			'label'       => \__( 'Meta description', 'wp-seo-pilot' ),
			'issue_label' => \__( 'Description', 'wp-seo-pilot' ),
			'status'      => $desc_status,
			'score'       => $desc_score,
			'max'         => 20,
			'is_pass'     => $desc_score >= 18,
		];
		$total_score += $desc_score;

		// H1 presence (max 15).
		$has_h1 = (bool) preg_match( '/<h1\b[^>]*>/i', $content_html );
		$metrics[]    = [
			'key'         => 'h1',
			'label'       => \__( 'H1 heading', 'wp-seo-pilot' ),
			'issue_label' => \__( 'H1', 'wp-seo-pilot' ),
			'status'      => $has_h1 ? \__( 'Primary heading present.', 'wp-seo-pilot' ) : \__( 'Add a single H1 to introduce the page.', 'wp-seo-pilot' ),
			'score'       => $has_h1 ? 15 : 0,
			'max'         => 15,
			'is_pass'     => $has_h1,
		];
		$total_score += $has_h1 ? 15 : 0;

		// Internal links (max 20).
		$internal_links = 0;
		if ( preg_match_all( '/<a\s[^>]*href\s*=\s*(["\'])(.*?)\1/iu', $content_html, $link_matches ) ) {
			$home_host = \wp_parse_url( \home_url(), PHP_URL_HOST );
			$home_host = $home_host ? strtolower( preg_replace( '/^www\./', '', $home_host ) ) : '';

			foreach ( $link_matches[2] as $href ) {
				$href = trim( $href );
				if ( '' === $href || '#' === $href[0] ) {
					continue;
				}

				if ( preg_match( '#^(mailto|tel|javascript):#i', $href ) ) {
					continue;
				}

				$parsed = \wp_parse_url( $href );

				if ( empty( $parsed['host'] ) ) {
					$internal_links++;
					continue;
				}

				$link_host = strtolower( preg_replace( '/^www\./', '', $parsed['host'] ) );
				if ( $home_host && $home_host === $link_host ) {
					$internal_links++;
				}
			}
		}

		if ( 0 === $internal_links ) {
			$link_score  = 0;
			$link_status = \__( 'Add internal links to related posts.', 'wp-seo-pilot' );
		} elseif ( 1 === $internal_links ) {
			$link_score  = 10;
			$link_status = \__( '1 internal link found — add another.', 'wp-seo-pilot' );
		} elseif ( 2 === $internal_links ) {
			$link_score  = 15;
			$link_status = \__( '2 internal links found — good start.', 'wp-seo-pilot' );
		} else {
			$link_score  = 20;
			$link_status = sprintf(
				/* translators: %d is the internal link count. */
				\__( '%d internal links keep readers exploring.', 'wp-seo-pilot' ),
				$internal_links
			);
		}

		$metrics[] = [
			'key'         => 'internal_links',
			'label'       => \__( 'Internal links', 'wp-seo-pilot' ),
			'issue_label' => \__( 'Links', 'wp-seo-pilot' ),
			'status'      => $link_status,
			'score'       => $link_score,
			'max'         => 20,
			'is_pass'     => $link_score >= 15,
		];
		$total_score += $link_score;

		// Image alt coverage (max 25).
		$images_total = 0;
		$images_with_alt = 0;
		if ( preg_match_all( '/<img\s[^>]*>/i', $content_html, $image_matches ) ) {
			foreach ( $image_matches[0] as $img_tag ) {
				$images_total++;
				if ( preg_match( '/alt\s*=\s*(["\'])(.*?)\1/iu', $img_tag, $alt_match ) ) {
					if ( '' !== trim( $alt_match[2] ) ) {
						$images_with_alt++;
					}
				}
			}
		}

		if ( 0 === $images_total ) {
			$alt_score  = 25;
			$alt_status = \__( 'No inline images detected.', 'wp-seo-pilot' );
		} else {
			$coverage   = $images_with_alt / max( 1, $images_total );
			$alt_score  = (int) round( 25 * $coverage );
			if ( $coverage >= 0.9 ) {
				$alt_status = sprintf(
					/* translators: 1: images with alt, 2: total images. */
					\__( 'Alt text on %1$d of %2$d images (great).', 'wp-seo-pilot' ),
					$images_with_alt,
					$images_total
				);
			} elseif ( $coverage >= 0.6 ) {
				$alt_status = sprintf(
					/* translators: 1: images with alt, 2: total images. */
					\__( 'Alt text on %1$d of %2$d images — add a bit more detail.', 'wp-seo-pilot' ),
					$images_with_alt,
					$images_total
				);
			} else {
				$alt_status = sprintf(
					/* translators: 1: images with alt, 2: total images. */
					\__( 'Only %1$d of %2$d images have alt text.', 'wp-seo-pilot' ),
					$images_with_alt,
					$images_total
				);
			}
		}

		$metrics[] = [
			'key'         => 'image_alts',
			'label'       => \__( 'Image alt text', 'wp-seo-pilot' ),
			'issue_label' => \__( 'Image alts', 'wp-seo-pilot' ),
			'status'      => $alt_status,
			'score'       => $alt_score,
			'max'         => 25,
			'is_pass'     => $alt_score >= 19,
		];
		$total_score += $alt_score;

		$total_score = max( 0, min( 100, (int) round( $total_score ) ) );

		if ( $total_score >= 70 ) {
			$level = 'good';
			$label = \__( 'Green zone', 'wp-seo-pilot' );
		} elseif ( $total_score >= 40 ) {
			$level = 'fair';
			$label = \__( 'Needs tweaks', 'wp-seo-pilot' );
		} else {
			$level = 'low';
			$label = \__( 'Needs attention', 'wp-seo-pilot' );
		}

		$issues = array_values(
			array_filter(
				$metrics,
				static function ( $metric ) {
					return empty( $metric['is_pass'] );
				}
			)
		);

		if ( $issues ) {
			$summary = implode(
				' • ',
				array_map(
					static function ( $metric ) {
						return $metric['issue_label'];
					},
					$issues
				)
			);
		} else {
			$summary = \__( 'All baseline checks look good.', 'wp-seo-pilot' );
		}

		$result = [
			'score'   => $total_score,
			'level'   => $level,
			'label'   => $label,
			'summary' => $summary,
			'metrics' => $metrics,
		];

		return \apply_filters( 'wpseopilot_seo_score', $result, $post );
	}

	/**
	 * Convenience wrapper for breadcrumbs markup.
	 *
	 * @param WP_Post|int|null $post Post.
	 * @param bool             $echo Whether to echo.
	 *
	 * @return string|null
	 */
	function breadcrumbs( $post = null, $echo = true ) {
		$post    = $post ? \get_post( $post ) : \get_post();
		$crumbs  = [];
		$crumbs[] = [
			'url'   => home_url( '/' ),
			'title' => \get_bloginfo( 'name' ),
		];

		// Handle single posts/pages
		if ( $post && is_singular() && ! is_front_page() ) {
			// Add CPT archive link if applicable
			$post_type = \get_post_type( $post );
			$post_type_object = \get_post_type_object( $post_type );

			if ( $post_type_object && $post_type_object->has_archive && ! in_array( $post_type, [ 'post', 'page' ], true ) ) {
				$archive_link = \get_post_type_archive_link( $post_type );
				if ( $archive_link ) {
					$crumbs[] = [
						'url'   => $archive_link,
						'title' => $post_type_object->labels->name ?? $post_type_object->label,
					];
				}
			}

			$ancestors = \get_post_ancestors( $post );
			$ancestors = array_reverse( $ancestors );

			foreach ( $ancestors as $ancestor_id ) {
				$crumbs[] = [
					'url'   => \get_permalink( $ancestor_id ),
					'title' => \get_the_title( $ancestor_id ),
				];
			}

			$crumbs[] = [
				'url'   => \get_permalink( $post ),
				'title' => \get_the_title( $post ),
			];
		}
		// Handle category archives
		elseif ( is_category() ) {
			$category = get_queried_object();
			if ( $category instanceof \WP_Term ) {
				// Add parent categories if any
				$ancestors = get_ancestors( $category->term_id, 'category' );
				$ancestors = array_reverse( $ancestors );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor_term = get_term( $ancestor_id, 'category' );
					if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
						$crumbs[] = [
							'url'   => get_term_link( $ancestor_term ),
							'title' => $ancestor_term->name,
						];
					}
				}

				$crumbs[] = [
					'url'   => get_term_link( $category ),
					'title' => $category->name,
				];
			}
		}
		// Handle tag archives
		elseif ( is_tag() ) {
			$tag = get_queried_object();
			if ( $tag instanceof \WP_Term ) {
				$crumbs[] = [
					'url'   => get_term_link( $tag ),
					'title' => $tag->name,
				];
			}
		}
		// Handle taxonomy archives
		elseif ( is_tax() ) {
			$term = get_queried_object();
			if ( $term instanceof \WP_Term ) {
				// Add parent terms if any
				$ancestors = get_ancestors( $term->term_id, $term->taxonomy );
				$ancestors = array_reverse( $ancestors );
				foreach ( $ancestors as $ancestor_id ) {
					$ancestor_term = get_term( $ancestor_id, $term->taxonomy );
					if ( $ancestor_term && ! is_wp_error( $ancestor_term ) ) {
						$crumbs[] = [
							'url'   => get_term_link( $ancestor_term ),
							'title' => $ancestor_term->name,
						];
					}
				}

				$crumbs[] = [
					'url'   => get_term_link( $term ),
					'title' => $term->name,
				];
			}
		}
		// Handle post type archives
		elseif ( is_post_type_archive() ) {
			$post_type = get_queried_object();
			if ( $post_type instanceof \WP_Post_Type ) {
				$crumbs[] = [
					'url'   => get_post_type_archive_link( $post_type->name ),
					'title' => $post_type->labels->name ?? $post_type->label,
				];
			}
		}
		// Handle author archives
		elseif ( is_author() ) {
			$author = get_queried_object();
			if ( $author instanceof \WP_User ) {
				$crumbs[] = [
					'url'   => get_author_posts_url( $author->ID ),
					'title' => $author->display_name,
				];
			}
		}
		// Handle date archives
		elseif ( is_date() ) {
			$crumbs[] = [
				'url'   => '',
				'title' => get_the_archive_title(),
			];
		}

		$crumbs = apply_filters( 'wpseopilot_breadcrumb_links', $crumbs, $post );

		if ( empty( $crumbs ) || ! is_array( $crumbs ) ) {
			return null;
		}

		ob_start();
		?>
		<nav class="wpseopilot-breadcrumb" aria-label="Breadcrumb">
			<ol>
				<?php
				$total = count( $crumbs );
				foreach ( $crumbs as $index => $crumb ) :
					$is_last = ( $index === $total - 1 );
				?>
					<li>
						<?php if ( $is_last ) : ?>
							<span class="current"><?php echo esc_html( $crumb['title'] ); ?></span>
						<?php else : ?>
							<a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['title'] ); ?></a>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php
		$html = trim( ob_get_clean() );

		if ( $echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return null;
		}

		return $html;
	}
}

namespace {
	/**
	 * Global helper for template usage.
	 *
	 * @param \WP_Post|int|null $post Post.
	 * @param bool              $echo Echo?
	 *
	 * @return string|null
	 */
	function wpseopilot_breadcrumbs( $post = null, $echo = true ) {
		return \WPSEOPilot\Helpers\breadcrumbs( $post, $echo );
	}

	/**
	 * Programmatically create a redirect.
	 *
	 * @param string $source      The request path to match (e.g. '/old-url').
	 * @param string $target      The destination URL (can be relative or absolute).
	 * @param int    $status_code HTTP Status Code (default 301).
	 *
	 * @return int|\WP_Error Redirect ID or WP_Error.
	 */
	function wpseopilot_create_redirect( $source, $target, $status_code = 301 ) {
		$plugin = \WPSEOPilot\Plugin::instance();
		$svc    = $plugin->get( 'redirects' );

		if ( ! $svc ) {
			return new \WP_Error( 'service_unavailable', 'Redirect service not loaded.' );
		}

		return $svc->create_redirect( $source, $target, $status_code );
	}
}
