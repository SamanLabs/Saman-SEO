<?php
/**
 * Shared helper functions.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Helpers {

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
	 * Check if a module is enabled.
	 *
	 * Uses the new `SAMAN_SEO_module_*` option keys that match the React UI.
	 * Falls back to legacy `SAMAN_SEO_enable_*` options for backward compatibility.
	 *
	 * @param string $module Module slug (e.g., 'sitemap', 'redirects', '404_log').
	 *
	 * @return bool True if the module is enabled, false otherwise.
	 */
	function module_enabled( string $module ): bool {
		$value = \get_option( 'SAMAN_SEO_module_' . $module );

		// If the new option exists, use it.
		if ( false !== $value ) {
			return '1' === $value;
		}

		// Fall back to legacy option if new one doesn't exist.
		$legacy_map = [
			'sitemap'       => 'SAMAN_SEO_enable_sitemap_enhancer',
			'redirects'     => 'SAMAN_SEO_enable_redirect_manager',
			'404_log'       => 'SAMAN_SEO_enable_404_logging',
			'llm_txt'       => 'SAMAN_SEO_enable_llm_txt',
			'local_seo'     => 'SAMAN_SEO_enable_local_seo',
			'social_cards'  => 'SAMAN_SEO_enable_og_preview',
			'analytics'     => 'SAMAN_SEO_enable_analytics',
			'admin_bar'     => 'SAMAN_SEO_enable_admin_bar',
			'internal_links' => 'SAMAN_SEO_enable_internal_linking',
			'ai_assistant'  => 'SAMAN_SEO_enable_ai_assistant',
		];

		if ( isset( $legacy_map[ $module ] ) ) {
			return '1' === \get_option( $legacy_map[ $module ], '1' );
		}

		// Default enabled for new/unknown modules.
		return true;
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

		$meta = (array) \get_post_meta( $post->ID, '_SAMAN_SEO_meta', true );

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
			'separator'     => get_option( 'SAMAN_SEO_title_separator', '-' ),
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

		$post_type_templates = \get_option( 'SAMAN_SEO_post_type_title_templates', [] );
		if ( ! is_array( $post_type_templates ) ) {
			$post_type_templates = [];
		}

		if ( ! empty( $post->post_type ) && ! empty( $post_type_templates[ $post->post_type ] ) ) {
			$template = $post_type_templates[ $post->post_type ];
		} else {
			$template = get_option( 'SAMAN_SEO_default_title_template', '{{post_title}} | {{site_title}}' );
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
	 * Check if text contains keyphrase (case-insensitive).
	 *
	 * @param string $text      Text to search in.
	 * @param string $keyphrase Keyphrase to find.
	 *
	 * @return bool
	 */
	function contains_keyphrase( $text, $keyphrase ) {
		if ( empty( $keyphrase ) || empty( $text ) ) {
			return false;
		}
		$text      = function_exists( 'mb_strtolower' ) ? mb_strtolower( $text ) : strtolower( $text );
		$keyphrase = function_exists( 'mb_strtolower' ) ? mb_strtolower( $keyphrase ) : strtolower( $keyphrase );
		return false !== strpos( $text, $keyphrase );
	}

	/**
	 * Calculate keyword density as percentage.
	 *
	 * @param string $content_text Plain text content.
	 * @param string $keyphrase    Keyphrase to count.
	 * @param int    $word_count   Total word count.
	 *
	 * @return float Density percentage.
	 */
	function calculate_keyphrase_density( $content_text, $keyphrase, $word_count ) {
		if ( $word_count < 1 || empty( $keyphrase ) ) {
			return 0.0;
		}

		$text      = function_exists( 'mb_strtolower' ) ? mb_strtolower( $content_text ) : strtolower( $content_text );
		$keyphrase = function_exists( 'mb_strtolower' ) ? mb_strtolower( $keyphrase ) : strtolower( $keyphrase );

		$keyphrase_count = substr_count( $text, $keyphrase );
		$keyphrase_words = str_word_count( $keyphrase );

		if ( $keyphrase_words < 1 ) {
			return 0.0;
		}

		return ( $keyphrase_count * $keyphrase_words / $word_count ) * 100;
	}

	/**
	 * Extract first paragraph text from HTML.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string Plain text of first paragraph.
	 */
	function extract_first_paragraph( $html ) {
		// Look for first <p> tag content.
		if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $html, $match ) ) {
			return \wp_strip_all_tags( $match[1] );
		}
		// Fallback: first 150 words.
		$text = \wp_strip_all_tags( $html );
		return \wp_trim_words( $text, 150, '' );
	}

	/**
	 * Count external links in content.
	 *
	 * @param string $html HTML content.
	 *
	 * @return int External link count.
	 */
	function count_external_links( $html ) {
		$external_count = 0;

		if ( preg_match_all( '/<a\s[^>]*href\s*=\s*(["\'])(.*?)\1/iu', $html, $matches ) ) {
			$home_host = \wp_parse_url( \home_url(), PHP_URL_HOST );
			$home_host = $home_host ? strtolower( preg_replace( '/^www\./', '', $home_host ) ) : '';

			foreach ( $matches[2] as $href ) {
				$href = trim( $href );
				if ( empty( $href ) || '#' === $href[0] ) {
					continue;
				}
				if ( preg_match( '#^(mailto|tel|javascript):#i', $href ) ) {
					continue;
				}

				$parsed = \wp_parse_url( $href );
				if ( empty( $parsed['host'] ) ) {
					continue; // Relative = internal.
				}

				$link_host = strtolower( preg_replace( '/^www\./', '', $parsed['host'] ) );
				if ( $home_host !== $link_host ) {
					$external_count++;
				}
			}
		}

		return $external_count;
	}

	/**
	 * Count headings by level.
	 *
	 * @param string $html  HTML content.
	 * @param int    $level Heading level (1-6).
	 *
	 * @return int Heading count.
	 */
	function count_headings( $html, $level = 2 ) {
		preg_match_all( "/<h{$level}\b[^>]*>/i", $html, $matches );
		return count( $matches[0] );
	}

	/**
	 * Extract H1 text from HTML.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string H1 text or empty string.
	 */
	function extract_h1_text( $html ) {
		if ( preg_match( '/<h1[^>]*>(.*?)<\/h1>/is', $html, $match ) ) {
			return \wp_strip_all_tags( $match[1] );
		}
		return '';
	}

	/**
	 * Calculate a comprehensive SEO score for a post.
	 *
	 * Enhanced scoring with 14 factors across 4 categories:
	 * - Basic SEO (40 pts): title, description, H1, content length
	 * - Keyword Optimization (30 pts): keyphrase in title/desc/H1, density, first paragraph
	 * - Content Structure (15 pts): H2 and H3 headings
	 * - Links & Media (15 pts): internal links, external links, image alts
	 *
	 * @param WP_Post|int $post Post object or ID.
	 *
	 * @return array{
	 *     score:int,
	 *     level:string,
	 *     label:string,
	 *     summary:string,
	 *     has_keyphrase:bool,
	 *     metrics:array<int,array{
	 *         key:string,
	 *         label:string,
	 *         issue_label:string,
	 *         status:string,
	 *         score:int,
	 *         max:int,
	 *         is_pass:bool,
	 *         category:string
	 *     }>
	 * }
	 */
	function calculate_seo_score( $post ) {
		$post = \get_post( $post );

		$default_summary = \__( 'Add content to generate a score.', 'saman-seo' );
		$default_result  = [
			'score'                => 0,
			'level'                => 'low',
			'label'                => \__( 'Needs attention', 'saman-seo' ),
			'summary'              => $default_summary,
			'has_keyphrase'        => false,
			'metrics'              => [],
			'secondary_keyphrases' => [],
		];

		if ( ! $post instanceof WP_Post ) {
			return $default_result;
		}

		// Get SEO meta including focus keyphrase and secondary keyphrases.
		$meta                  = get_post_meta( $post );
		$all_meta              = (array) \get_post_meta( $post->ID, '_SAMAN_SEO_meta', true );
		$focus_keyphrase       = isset( $all_meta['focus_keyphrase'] ) ? trim( sanitize_text_field( $all_meta['focus_keyphrase'] ) ) : '';
		$has_keyphrase         = ! empty( $focus_keyphrase );
		$secondary_keyphrases  = isset( $all_meta['secondary_keyphrases'] ) && is_array( $all_meta['secondary_keyphrases'] )
			? array_filter( array_map( 'sanitize_text_field', $all_meta['secondary_keyphrases'] ) )
			: [];

		$title_text  = trim( $meta['title'] ?: $post->post_title );
		$desc_text   = trim( $meta['description'] );
		$content_raw = (string) $post->post_content;

		// Process content HTML.
		$content_html = $content_raw;
		if ( function_exists( 'do_blocks' ) && \has_blocks( $post ) ) {
			$content_html = \do_blocks( $content_html );
		}
		if ( function_exists( 'do_shortcode' ) ) {
			$content_html = \do_shortcode( $content_html );
		}
		$content_html = \wpautop( $content_html );
		$content_html = \wp_kses_post( $content_html );

		// Prepare plain text and word count.
		$content_text = \wp_strip_all_tags( $content_html );
		$word_count   = str_word_count( $content_text );

		// Extract elements for analysis.
		$first_paragraph = extract_first_paragraph( $content_html );
		$h1_text         = extract_h1_text( $content_html );

		$strlen = static function ( $value ) {
			return function_exists( 'mb_strlen' ) ? mb_strlen( $value ) : strlen( $value );
		};

		$metrics     = [];
		$total_score = 0;

		// =====================================================
		// BASIC SEO (40 points max)
		// =====================================================

		// 1. Title length (max 10 pts).
		$title_length = $strlen( $title_text );
		if ( 0 === $title_length ) {
			$title_score  = 0;
			$title_status = \__( 'Add a meta title for this post.', 'saman-seo' );
		} elseif ( $title_length < 30 ) {
			$title_score  = 5;
			$title_status = sprintf( \__( 'Length: %d chars (too short, aim for 50-60).', 'saman-seo' ), $title_length );
		} elseif ( $title_length <= 60 ) {
			$title_score  = 10;
			$title_status = sprintf( \__( 'Length: %d chars (ideal).', 'saman-seo' ), $title_length );
		} elseif ( $title_length <= 70 ) {
			$title_score  = 8;
			$title_status = sprintf( \__( 'Length: %d chars (slightly long).', 'saman-seo' ), $title_length );
		} else {
			$title_score  = 5;
			$title_status = sprintf( \__( 'Length: %d chars (too long, may truncate).', 'saman-seo' ), $title_length );
		}

		$metrics[]    = [
			'key'         => 'title_length',
			'label'       => \__( 'Title length', 'saman-seo' ),
			'issue_label' => \__( 'Title', 'saman-seo' ),
			'status'      => $title_status,
			'score'       => $title_score,
			'max'         => 10,
			'is_pass'     => $title_score >= 8,
			'category'    => 'basic',
		];
		$total_score += $title_score;

		// 2. Meta description length (max 10 pts).
		$desc_length = $strlen( $desc_text );
		if ( 0 === $desc_length ) {
			$desc_score  = 0;
			$desc_status = \__( 'Add a custom meta description.', 'saman-seo' );
		} elseif ( $desc_length < 80 ) {
			$desc_score  = 5;
			$desc_status = sprintf( \__( 'Length: %d chars (extend toward 120-155).', 'saman-seo' ), $desc_length );
		} elseif ( $desc_length <= 155 ) {
			$desc_score  = 10;
			$desc_status = sprintf( \__( 'Length: %d chars (ideal).', 'saman-seo' ), $desc_length );
		} elseif ( $desc_length <= 180 ) {
			$desc_score  = 8;
			$desc_status = sprintf( \__( 'Length: %d chars (trim slightly).', 'saman-seo' ), $desc_length );
		} else {
			$desc_score  = 5;
			$desc_status = sprintf( \__( 'Length: %d chars (too long, will truncate).', 'saman-seo' ), $desc_length );
		}

		$metrics[]    = [
			'key'         => 'description_length',
			'label'       => \__( 'Meta description', 'saman-seo' ),
			'issue_label' => \__( 'Description', 'saman-seo' ),
			'status'      => $desc_status,
			'score'       => $desc_score,
			'max'         => 10,
			'is_pass'     => $desc_score >= 8,
			'category'    => 'basic',
		];
		$total_score += $desc_score;

		// 3. H1 presence (max 8 pts).
		$has_h1    = ! empty( $h1_text );
		$h1_score  = $has_h1 ? 8 : 0;
		$h1_status = $has_h1
			? \__( 'Primary heading present.', 'saman-seo' )
			: \__( 'Add a single H1 to introduce the page.', 'saman-seo' );

		$metrics[]    = [
			'key'         => 'h1_presence',
			'label'       => \__( 'H1 heading', 'saman-seo' ),
			'issue_label' => \__( 'H1', 'saman-seo' ),
			'status'      => $h1_status,
			'score'       => $h1_score,
			'max'         => 8,
			'is_pass'     => $has_h1,
			'category'    => 'basic',
		];
		$total_score += $h1_score;

		// 4. Content length (max 12 pts).
		if ( $word_count < 100 ) {
			$content_score  = 0;
			$content_status = sprintf( \__( '%d words (add more content, aim for 300+).', 'saman-seo' ), $word_count );
		} elseif ( $word_count < 300 ) {
			$content_score  = 6;
			$content_status = sprintf( \__( '%d words (thin content, aim for 300+).', 'saman-seo' ), $word_count );
		} elseif ( $word_count < 600 ) {
			$content_score  = 10;
			$content_status = sprintf( \__( '%d words (good length).', 'saman-seo' ), $word_count );
		} else {
			$content_score  = 12;
			$content_status = sprintf( \__( '%d words (comprehensive).', 'saman-seo' ), $word_count );
		}

		$metrics[]    = [
			'key'         => 'content_length',
			'label'       => \__( 'Content length', 'saman-seo' ),
			'issue_label' => \__( 'Content', 'saman-seo' ),
			'status'      => $content_status,
			'score'       => $content_score,
			'max'         => 12,
			'is_pass'     => $content_score >= 10,
			'category'    => 'basic',
		];
		$total_score += $content_score;

		// =====================================================
		// KEYWORD OPTIMIZATION (30 points max - only if keyphrase set)
		// =====================================================
		if ( $has_keyphrase ) {
			// 5. Keyphrase in title (max 8 pts).
			$kw_in_title       = contains_keyphrase( $title_text, $focus_keyphrase );
			$kw_title_score    = $kw_in_title ? 8 : 0;
			$kw_title_status   = $kw_in_title
				? \__( 'Focus keyphrase appears in title.', 'saman-seo' )
				: \__( 'Add your keyphrase to the title.', 'saman-seo' );

			$metrics[]    = [
				'key'         => 'keyphrase_in_title',
				'label'       => \__( 'Keyphrase in title', 'saman-seo' ),
				'issue_label' => \__( 'Keyphrase in title', 'saman-seo' ),
				'status'      => $kw_title_status,
				'score'       => $kw_title_score,
				'max'         => 8,
				'is_pass'     => $kw_in_title,
				'category'    => 'keyword',
			];
			$total_score += $kw_title_score;

			// 6. Keyphrase in description (max 6 pts).
			$kw_in_desc      = contains_keyphrase( $desc_text, $focus_keyphrase );
			$kw_desc_score   = $kw_in_desc ? 6 : 0;
			$kw_desc_status  = $kw_in_desc
				? \__( 'Focus keyphrase appears in description.', 'saman-seo' )
				: \__( 'Add your keyphrase to the meta description.', 'saman-seo' );

			$metrics[]    = [
				'key'         => 'keyphrase_in_description',
				'label'       => \__( 'Keyphrase in description', 'saman-seo' ),
				'issue_label' => \__( 'Keyphrase in desc', 'saman-seo' ),
				'status'      => $kw_desc_status,
				'score'       => $kw_desc_score,
				'max'         => 6,
				'is_pass'     => $kw_in_desc,
				'category'    => 'keyword',
			];
			$total_score += $kw_desc_score;

			// 7. Keyphrase in H1 (max 5 pts).
			$kw_in_h1      = $has_h1 && contains_keyphrase( $h1_text, $focus_keyphrase );
			$kw_h1_score   = $kw_in_h1 ? 5 : 0;
			$kw_h1_status  = $kw_in_h1
				? \__( 'Focus keyphrase appears in H1.', 'saman-seo' )
				: ( $has_h1 ? \__( 'Add your keyphrase to the H1 heading.', 'saman-seo' ) : \__( 'Add an H1 with your keyphrase.', 'saman-seo' ) );

			$metrics[]    = [
				'key'         => 'keyphrase_in_h1',
				'label'       => \__( 'Keyphrase in H1', 'saman-seo' ),
				'issue_label' => \__( 'Keyphrase in H1', 'saman-seo' ),
				'status'      => $kw_h1_status,
				'score'       => $kw_h1_score,
				'max'         => 5,
				'is_pass'     => $kw_in_h1,
				'category'    => 'keyword',
			];
			$total_score += $kw_h1_score;

			// 8. Keyphrase density (max 6 pts).
			$density = calculate_keyphrase_density( $content_text, $focus_keyphrase, $word_count );
			if ( $density < 0.3 ) {
				$density_score  = 0;
				$density_status = sprintf( \__( 'Density: %.1f%% (too low, aim for 0.5-2.5%%).', 'saman-seo' ), $density );
			} elseif ( $density < 0.5 ) {
				$density_score  = 3;
				$density_status = sprintf( \__( 'Density: %.1f%% (slightly low).', 'saman-seo' ), $density );
			} elseif ( $density <= 2.5 ) {
				$density_score  = 6;
				$density_status = sprintf( \__( 'Density: %.1f%% (ideal range).', 'saman-seo' ), $density );
			} elseif ( $density <= 3.5 ) {
				$density_score  = 3;
				$density_status = sprintf( \__( 'Density: %.1f%% (slightly high).', 'saman-seo' ), $density );
			} else {
				$density_score  = 0;
				$density_status = sprintf( \__( 'Density: %.1f%% (keyword stuffing risk).', 'saman-seo' ), $density );
			}

			$metrics[]    = [
				'key'         => 'keyphrase_density',
				'label'       => \__( 'Keyphrase density', 'saman-seo' ),
				'issue_label' => \__( 'Keyword density', 'saman-seo' ),
				'status'      => $density_status,
				'score'       => $density_score,
				'max'         => 6,
				'is_pass'     => $density_score >= 5,
				'category'    => 'keyword',
				'value'       => round( $density, 2 ),
			];
			$total_score += $density_score;

			// 9. Keyphrase in first paragraph (max 5 pts).
			$kw_in_intro       = contains_keyphrase( $first_paragraph, $focus_keyphrase );
			$kw_intro_score    = $kw_in_intro ? 5 : 0;
			$kw_intro_status   = $kw_in_intro
				? \__( 'Focus keyphrase appears in introduction.', 'saman-seo' )
				: \__( 'Mention your keyphrase in the first paragraph.', 'saman-seo' );

			$metrics[]    = [
				'key'         => 'keyphrase_in_intro',
				'label'       => \__( 'Keyphrase in intro', 'saman-seo' ),
				'issue_label' => \__( 'Keyphrase in intro', 'saman-seo' ),
				'status'      => $kw_intro_status,
				'score'       => $kw_intro_score,
				'max'         => 5,
				'is_pass'     => $kw_in_intro,
				'category'    => 'keyword',
			];
			$total_score += $kw_intro_score;
		}

		// =====================================================
		// SECONDARY KEYPHRASES (informational only, no score impact)
		// =====================================================
		$secondary_analysis = [];
		if ( ! empty( $secondary_keyphrases ) ) {
			foreach ( $secondary_keyphrases as $idx => $sec_keyphrase ) {
				if ( empty( $sec_keyphrase ) ) {
					continue;
				}

				$sec_in_title   = contains_keyphrase( $title_text, $sec_keyphrase );
				$sec_in_desc    = contains_keyphrase( $desc_text, $sec_keyphrase );
				$sec_in_content = contains_keyphrase( $content_text, $sec_keyphrase );
				$sec_in_h1      = $has_h1 && contains_keyphrase( $h1_text, $sec_keyphrase );
				$sec_density    = calculate_keyphrase_density( $content_text, $sec_keyphrase, $word_count );

				$sec_checks_passed = (int) $sec_in_title + (int) $sec_in_desc + (int) $sec_in_content + (int) $sec_in_h1;

				$secondary_analysis[] = [
					'keyphrase'  => $sec_keyphrase,
					'in_title'   => $sec_in_title,
					'in_desc'    => $sec_in_desc,
					'in_content' => $sec_in_content,
					'in_h1'      => $sec_in_h1,
					'density'    => round( $sec_density, 2 ),
					'coverage'   => $sec_checks_passed . '/4',
					'status'     => $sec_checks_passed >= 2 ? 'good' : ( $sec_checks_passed >= 1 ? 'fair' : 'poor' ),
				];

				// Add informational metric for the analysis tab.
				$sec_status = sprintf(
					/* translators: %1$s: coverage score, %2$.1f: density percentage */
					\__( 'Coverage: %1$s ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢ Density: %2$.1f%%', 'saman-seo' ),
					$sec_checks_passed . '/4',
					$sec_density
				);

				$metrics[] = [
					'key'         => 'secondary_keyphrase_' . ( $idx + 1 ),
					'label'       => sprintf( \__( 'Secondary: "%s"', 'saman-seo' ), $sec_keyphrase ),
					'issue_label' => sprintf( \__( 'Secondary #%d', 'saman-seo' ), $idx + 1 ),
					'status'      => $sec_status,
					'score'       => 0, // Informational only.
					'max'         => 0,
					'is_pass'     => $sec_checks_passed >= 2,
					'category'    => 'secondary_keyword',
					'value'       => $sec_checks_passed,
				];
			}
		}

		// =====================================================
		// CONTENT STRUCTURE (15 points max)
		// =====================================================

		// 10. H2 headings (max 8 pts).
		$h2_count = count_headings( $content_html, 2 );
		if ( 0 === $h2_count ) {
			$h2_score  = 0;
			$h2_status = \__( 'No H2 headings found. Add subheadings.', 'saman-seo' );
		} elseif ( 1 === $h2_count ) {
			$h2_score  = 5;
			$h2_status = \__( '1 H2 heading found. Consider adding more.', 'saman-seo' );
		} elseif ( $h2_count <= 5 ) {
			$h2_score  = 8;
			$h2_status = sprintf( \__( '%d H2 headings (well structured).', 'saman-seo' ), $h2_count );
		} else {
			$h2_score  = 6;
			$h2_status = sprintf( \__( '%d H2 headings (many sections).', 'saman-seo' ), $h2_count );
		}

		$metrics[]    = [
			'key'         => 'h2_headings',
			'label'       => \__( 'H2 subheadings', 'saman-seo' ),
			'issue_label' => \__( 'H2 headings', 'saman-seo' ),
			'status'      => $h2_status,
			'score'       => $h2_score,
			'max'         => 8,
			'is_pass'     => $h2_score >= 5,
			'category'    => 'structure',
			'value'       => $h2_count,
		];
		$total_score += $h2_score;

		// 11. H3 headings (max 7 pts).
		$h3_count = count_headings( $content_html, 3 );
		if ( 0 === $h3_count ) {
			$h3_score  = 3;
			$h3_status = \__( 'No H3 headings. Consider adding for longer content.', 'saman-seo' );
		} elseif ( $h3_count <= 6 ) {
			$h3_score  = 7;
			$h3_status = sprintf( \__( '%d H3 headings (good detail).', 'saman-seo' ), $h3_count );
		} else {
			$h3_score  = 5;
			$h3_status = sprintf( \__( '%d H3 headings (many subsections).', 'saman-seo' ), $h3_count );
		}

		$metrics[]    = [
			'key'         => 'h3_headings',
			'label'       => \__( 'H3 subheadings', 'saman-seo' ),
			'issue_label' => \__( 'H3 headings', 'saman-seo' ),
			'status'      => $h3_status,
			'score'       => $h3_score,
			'max'         => 7,
			'is_pass'     => $h3_score >= 5,
			'category'    => 'structure',
			'value'       => $h3_count,
		];
		$total_score += $h3_score;

		// =====================================================
		// LINKS & MEDIA (15 points max)
		// =====================================================

		// 12. Internal links (max 8 pts).
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
			$int_link_score  = 0;
			$int_link_status = \__( 'Add internal links to related posts.', 'saman-seo' );
		} elseif ( 1 === $internal_links ) {
			$int_link_score  = 4;
			$int_link_status = \__( '1 internal link found ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â add more.', 'saman-seo' );
		} elseif ( $internal_links <= 3 ) {
			$int_link_score  = 6;
			$int_link_status = sprintf( \__( '%d internal links ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â good start.', 'saman-seo' ), $internal_links );
		} else {
			$int_link_score  = 8;
			$int_link_status = sprintf( \__( '%d internal links (excellent).', 'saman-seo' ), $internal_links );
		}

		$metrics[]    = [
			'key'         => 'internal_links',
			'label'       => \__( 'Internal links', 'saman-seo' ),
			'issue_label' => \__( 'Internal links', 'saman-seo' ),
			'status'      => $int_link_status,
			'score'       => $int_link_score,
			'max'         => 8,
			'is_pass'     => $int_link_score >= 6,
			'category'    => 'links',
			'value'       => $internal_links,
		];
		$total_score += $int_link_score;

		// 13. External links (max 4 pts).
		$external_links = count_external_links( $content_html );
		if ( 0 === $external_links ) {
			$ext_link_score  = 0;
			$ext_link_status = \__( 'No external links. Consider citing sources.', 'saman-seo' );
		} elseif ( $external_links <= 3 ) {
			$ext_link_score  = 4;
			$ext_link_status = sprintf( \__( '%d external link(s) (good for credibility).', 'saman-seo' ), $external_links );
		} else {
			$ext_link_score  = 3;
			$ext_link_status = sprintf( \__( '%d external links (watch link equity).', 'saman-seo' ), $external_links );
		}

		$metrics[]    = [
			'key'         => 'external_links',
			'label'       => \__( 'External links', 'saman-seo' ),
			'issue_label' => \__( 'External links', 'saman-seo' ),
			'status'      => $ext_link_status,
			'score'       => $ext_link_score,
			'max'         => 4,
			'is_pass'     => $ext_link_score >= 3,
			'category'    => 'links',
			'value'       => $external_links,
		];
		$total_score += $ext_link_score;

		// 14. Image alt text coverage (max 3 pts).
		$images_total    = 0;
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
			$alt_score  = 3;
			$alt_status = \__( 'No inline images (not required).', 'saman-seo' );
		} else {
			$coverage  = $images_with_alt / max( 1, $images_total );
			$alt_score = (int) round( 3 * $coverage );
			if ( $coverage >= 0.9 ) {
				$alt_status = sprintf( \__( 'Alt text on %1$d of %2$d images (great).', 'saman-seo' ), $images_with_alt, $images_total );
			} elseif ( $coverage >= 0.5 ) {
				$alt_status = sprintf( \__( 'Alt text on %1$d of %2$d images ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â add more.', 'saman-seo' ), $images_with_alt, $images_total );
			} else {
				$alt_status = sprintf( \__( 'Only %1$d of %2$d images have alt text.', 'saman-seo' ), $images_with_alt, $images_total );
			}
		}

		$metrics[]    = [
			'key'         => 'image_alts',
			'label'       => \__( 'Image alt text', 'saman-seo' ),
			'issue_label' => \__( 'Image alts', 'saman-seo' ),
			'status'      => $alt_status,
			'score'       => $alt_score,
			'max'         => 3,
			'is_pass'     => $alt_score >= 2,
			'category'    => 'links',
		];
		$total_score += $alt_score;

		// =====================================================
		// SCORE NORMALIZATION
		// =====================================================
		// When no keyphrase is set, normalize score from 70 to 100.
		$max_possible = $has_keyphrase ? 100 : 70;
		if ( ! $has_keyphrase && $max_possible < 100 ) {
			$total_score = (int) round( ( $total_score / $max_possible ) * 100 );
		}

		$total_score = max( 0, min( 100, $total_score ) );

		// Determine level and label.
		if ( $total_score >= 70 ) {
			$level = 'good';
			$label = \__( 'Green zone', 'saman-seo' );
		} elseif ( $total_score >= 40 ) {
			$level = 'fair';
			$label = \__( 'Needs tweaks', 'saman-seo' );
		} else {
			$level = 'low';
			$label = \__( 'Needs attention', 'saman-seo' );
		}

		// Build summary from failing metrics.
		$issues = array_values(
			array_filter(
				$metrics,
				static function ( $metric ) {
					return empty( $metric['is_pass'] );
				}
			)
		);

		if ( $issues ) {
			$issue_labels = array_slice(
				array_map(
					static function ( $metric ) {
						return $metric['issue_label'];
					},
					$issues
				),
				0,
				3
			);
			$summary = implode( ' ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¢ ', $issue_labels );
			if ( count( $issues ) > 3 ) {
				$summary .= sprintf( ' (+%d more)', count( $issues ) - 3 );
			}
		} else {
			$summary = \__( 'All checks look good!', 'saman-seo' );
		}

		$result = [
			'score'                => $total_score,
			'level'                => $level,
			'label'                => $label,
			'summary'              => $summary,
			'has_keyphrase'        => $has_keyphrase,
			'metrics'              => $metrics,
			'secondary_keyphrases' => $secondary_analysis,
		];

		return \apply_filters( 'SAMAN_SEO_seo_score', $result, $post );
	}

	/**
	 * Render breadcrumbs markup.
	 *
	 * Uses the Breadcrumbs service for full-featured output with styling and JSON-LD schema.
	 *
	 * @param array|null $args Optional arguments to override settings.
	 * @param bool       $echo Whether to echo (default true).
	 *
	 * @return string|null
	 */
	function breadcrumbs( $args = null, $echo = true ) {
		$plugin  = \Saman\SEO\Plugin::instance();
		$service = $plugin->get( 'breadcrumbs' );

		if ( ! $service ) {
			return null;
		}

		// Support legacy signature: breadcrumbs($post, $echo).
		if ( $args instanceof \WP_Post || is_numeric( $args ) ) {
			$args = [];
			$echo = ( func_num_args() > 1 ) ? (bool) func_get_arg( 1 ) : true;
		}

		if ( ! is_array( $args ) ) {
			$args = [];
		}

		$html = $service->render( $args );

		if ( $echo ) {
			echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return null;
		}

		return $html;
	}
}

namespace {
	/**
	 * Check if a module is enabled.
	 *
	 * @param string $module Module slug (e.g., 'sitemap', 'redirects', '404_log').
	 *
	 * @return bool True if the module is enabled, false otherwise.
	 */
	function SAMAN_SEO_module_enabled( string $module ): bool {
		return \Saman\SEO\Helpers\module_enabled( $module );
	}

	/**
	 * Render breadcrumbs in theme templates.
	 *
	 * @param array|null $args Optional arguments to override settings.
	 * @param bool       $echo Whether to echo (default true).
	 *
	 * @return string|null
	 */
	function SAMAN_SEO_breadcrumbs( $args = null, $echo = true ) {
		return \Saman\SEO\Helpers\breadcrumbs( $args, $echo );
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
	function SAMAN_SEO_create_redirect( $source, $target, $status_code = 301 ) {
		$plugin = \Saman\SEO\Plugin::instance();
		$svc    = $plugin->get( 'redirects' );

		if ( ! $svc ) {
			return new \WP_Error( 'service_unavailable', 'Redirect service not loaded.' );
		}

		return $svc->create_redirect( $source, $target, $status_code );
	}
}
