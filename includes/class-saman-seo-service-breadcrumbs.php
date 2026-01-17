<?php
/**
 * Breadcrumbs Service.
 *
 * Provides breadcrumb navigation with JSON-LD schema support.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use WP_Post;
use WP_Term;
use WP_User;
use WP_Post_Type;

defined( 'ABSPATH' ) || exit;

/**
 * Breadcrumbs service class.
 */
class Breadcrumbs {

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	private $defaults = [
		'enabled'           => true,
		'separator'         => '>',
		'separator_custom'  => '',
		'show_home'         => true,
		'home_label'        => '',
		'show_current'      => true,
		'link_current'      => false,
		'truncate_length'   => 0,
		'show_on_front'     => false,
		'style_preset'      => 'default',
		'post_type_labels'  => [],
		'taxonomy_labels'   => [],
	];

	/**
	 * Separator options.
	 *
	 * @var array
	 */
	private $separator_options = [
		'>'      => '&raquo;',
		'/'      => '/',
		'|'      => '|',
		'-'      => '-',
		'arrow'  => '&rarr;',
		'chevron' => '&#8250;',
		'custom' => '',
	];

	/**
	 * Boot the service.
	 *
	 * @return void
	 */
	public function boot() {
		// Add JSON-LD schema to head.
		add_filter( 'SAMAN_SEO_jsonld', [ $this, 'add_breadcrumb_schema' ], 15 );

		// Register shortcode (replaces existing one).
		add_shortcode( 'SAMAN_SEO_breadcrumbs', [ $this, 'shortcode' ] );

		// Register Gutenberg block.
		add_action( 'init', [ $this, 'register_block' ] );

		// Enqueue frontend styles.
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_styles' ] );
	}

	/**
	 * Get breadcrumb settings.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = get_option( 'SAMAN_SEO_breadcrumb_settings', [] );

		if ( ! is_array( $settings ) ) {
			$settings = [];
		}

		return wp_parse_args( $settings, $this->defaults );
	}

	/**
	 * Save breadcrumb settings.
	 *
	 * @param array $settings Settings to save.
	 * @return bool
	 */
	public function save_settings( $settings ) {
		$sanitized = [
			'enabled'           => ! empty( $settings['enabled'] ),
			'separator'         => sanitize_text_field( $settings['separator'] ?? '>' ),
			'separator_custom'  => sanitize_text_field( $settings['separator_custom'] ?? '' ),
			'show_home'         => ! empty( $settings['show_home'] ),
			'home_label'        => sanitize_text_field( $settings['home_label'] ?? '' ),
			'show_current'      => ! empty( $settings['show_current'] ),
			'link_current'      => ! empty( $settings['link_current'] ),
			'truncate_length'   => absint( $settings['truncate_length'] ?? 0 ),
			'show_on_front'     => ! empty( $settings['show_on_front'] ),
			'style_preset'      => sanitize_text_field( $settings['style_preset'] ?? 'default' ),
			'post_type_labels'  => $this->sanitize_labels( $settings['post_type_labels'] ?? [] ),
			'taxonomy_labels'   => $this->sanitize_labels( $settings['taxonomy_labels'] ?? [] ),
		];

		return update_option( 'SAMAN_SEO_breadcrumb_settings', $sanitized );
	}

	/**
	 * Sanitize label arrays.
	 *
	 * @param array $labels Labels to sanitize.
	 * @return array
	 */
	private function sanitize_labels( $labels ) {
		if ( ! is_array( $labels ) ) {
			return [];
		}

		$sanitized = [];
		foreach ( $labels as $key => $value ) {
			$sanitized[ sanitize_key( $key ) ] = sanitize_text_field( $value );
		}

		return $sanitized;
	}

	/**
	 * Generate breadcrumb trail.
	 *
	 * @param array $args Optional arguments.
	 * @return array Array of breadcrumb items.
	 */
	public function get_breadcrumbs( $args = [] ) {
		$settings = $this->get_settings();
		$args     = wp_parse_args( $args, $settings );

		// Check if breadcrumbs are enabled.
		if ( empty( $args['enabled'] ) ) {
			return [];
		}

		// Don't show on front page unless explicitly enabled.
		if ( is_front_page() && empty( $args['show_on_front'] ) ) {
			return [];
		}

		$crumbs = [];

		// Add home link.
		if ( ! empty( $args['show_home'] ) ) {
			$home_label = ! empty( $args['home_label'] ) ? $args['home_label'] : __( 'Home', 'saman-seo' );
			$crumbs[]   = [
				'url'   => home_url( '/' ),
				'title' => $home_label,
			];
		}

		// Build breadcrumb trail based on context.
		$crumbs = $this->build_trail( $crumbs, $args );

		// Apply filter for customization.
		$crumbs = apply_filters( 'SAMAN_SEO_breadcrumb_items', $crumbs, $args );

		// Truncate titles if needed.
		if ( ! empty( $args['truncate_length'] ) && $args['truncate_length'] > 0 ) {
			$crumbs = $this->truncate_titles( $crumbs, $args['truncate_length'] );
		}

		return $crumbs;
	}

	/**
	 * Build the breadcrumb trail based on current context.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_trail( $crumbs, $args ) {
		$post = get_post();

		// Single post/page.
		if ( is_singular() && ! is_front_page() ) {
			return $this->build_singular_trail( $crumbs, $post, $args );
		}

		// Category archive.
		if ( is_category() ) {
			return $this->build_category_trail( $crumbs, $args );
		}

		// Tag archive.
		if ( is_tag() ) {
			return $this->build_tag_trail( $crumbs, $args );
		}

		// Custom taxonomy archive.
		if ( is_tax() ) {
			return $this->build_taxonomy_trail( $crumbs, $args );
		}

		// Post type archive.
		if ( is_post_type_archive() ) {
			return $this->build_post_type_archive_trail( $crumbs, $args );
		}

		// Author archive.
		if ( is_author() ) {
			return $this->build_author_trail( $crumbs, $args );
		}

		// Date archive.
		if ( is_date() ) {
			return $this->build_date_trail( $crumbs, $args );
		}

		// Search results.
		if ( is_search() ) {
			$crumbs[] = [
				'url'   => '',
				'title' => sprintf(
					/* translators: %s: search query */
					__( 'Search: %s', 'saman-seo' ),
					get_search_query()
				),
			];
		}

		// 404 page.
		if ( is_404() ) {
			$crumbs[] = [
				'url'   => '',
				'title' => __( 'Page Not Found', 'saman-seo' ),
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for singular posts/pages.
	 *
	 * @param array        $crumbs Existing crumbs.
	 * @param WP_Post|null $post   Post object.
	 * @param array        $args   Arguments.
	 * @return array
	 */
	private function build_singular_trail( $crumbs, $post, $args ) {
		if ( ! $post instanceof WP_Post ) {
			return $crumbs;
		}

		$post_type        = get_post_type( $post );
		$post_type_object = get_post_type_object( $post_type );

		// Check for per-post breadcrumb override.
		$override = get_post_meta( $post->ID, '_SAMAN_SEO_breadcrumb_override', true );
		if ( ! empty( $override ) && is_array( $override ) ) {
			return array_merge( $crumbs, $override );
		}

		// Add post type archive for CPTs (not post or page).
		if ( $post_type_object && $post_type_object->has_archive && ! in_array( $post_type, [ 'post', 'page' ], true ) ) {
			$archive_link = get_post_type_archive_link( $post_type );
			if ( $archive_link ) {
				$label    = $args['post_type_labels'][ $post_type ] ?? ( $post_type_object->labels->name ?? $post_type_object->label );
				$crumbs[] = [
					'url'   => $archive_link,
					'title' => $label,
				];
			}
		}

		// Add primary category for posts.
		if ( 'post' === $post_type ) {
			$crumbs = $this->add_primary_category( $crumbs, $post, $args );
		}

		// Add parent pages/posts (ancestors).
		$ancestors = get_post_ancestors( $post );
		$ancestors = array_reverse( $ancestors );

		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = [
				'url'   => get_permalink( $ancestor_id ),
				'title' => get_the_title( $ancestor_id ),
			];
		}

		// Add current item.
		if ( ! empty( $args['show_current'] ) ) {
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_permalink( $post ) : '',
				'title' => get_the_title( $post ),
			];
		}

		return $crumbs;
	}

	/**
	 * Add primary category to breadcrumb trail.
	 *
	 * @param array   $crumbs Existing crumbs.
	 * @param WP_Post $post   Post object.
	 * @param array   $args   Arguments.
	 * @return array
	 */
	private function add_primary_category( $crumbs, $post, $args ) {
		// Check for Yoast primary category.
		$primary_term_id = get_post_meta( $post->ID, '_yoast_wpseo_primary_category', true );

		if ( empty( $primary_term_id ) ) {
			// Check for our own primary category meta.
			$primary_term_id = get_post_meta( $post->ID, '_SAMAN_SEO_primary_category', true );
		}

		if ( ! empty( $primary_term_id ) ) {
			$primary_term = get_term( $primary_term_id, 'category' );
			if ( $primary_term && ! is_wp_error( $primary_term ) ) {
				// Add ancestors of primary category.
				$ancestors = get_ancestors( $primary_term->term_id, 'category' );
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
					'url'   => get_term_link( $primary_term ),
					'title' => $primary_term->name,
				];

				return $crumbs;
			}
		}

		// Fall back to first category.
		$categories = get_the_category( $post->ID );
		if ( ! empty( $categories ) ) {
			$category = $categories[0];

			// Add ancestors.
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

		return $crumbs;
	}

	/**
	 * Build trail for category archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_category_trail( $crumbs, $args ) {
		$category = get_queried_object();

		if ( ! $category instanceof WP_Term ) {
			return $crumbs;
		}

		// Add parent categories.
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

		// Add current category.
		if ( ! empty( $args['show_current'] ) ) {
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_term_link( $category ) : '',
				'title' => $args['taxonomy_labels']['category'] ?? $category->name,
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for tag archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_tag_trail( $crumbs, $args ) {
		$tag = get_queried_object();

		if ( ! $tag instanceof WP_Term ) {
			return $crumbs;
		}

		if ( ! empty( $args['show_current'] ) ) {
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_term_link( $tag ) : '',
				'title' => sprintf(
					/* translators: %s: tag name */
					__( 'Tag: %s', 'saman-seo' ),
					$tag->name
				),
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for custom taxonomy archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_taxonomy_trail( $crumbs, $args ) {
		$term = get_queried_object();

		if ( ! $term instanceof WP_Term ) {
			return $crumbs;
		}

		// Add parent terms.
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

		// Add current term.
		if ( ! empty( $args['show_current'] ) ) {
			$label    = $args['taxonomy_labels'][ $term->taxonomy ] ?? $term->name;
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_term_link( $term ) : '',
				'title' => $label,
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for post type archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_post_type_archive_trail( $crumbs, $args ) {
		$post_type = get_queried_object();

		if ( ! $post_type instanceof WP_Post_Type ) {
			return $crumbs;
		}

		if ( ! empty( $args['show_current'] ) ) {
			$label    = $args['post_type_labels'][ $post_type->name ] ?? ( $post_type->labels->name ?? $post_type->label );
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_post_type_archive_link( $post_type->name ) : '',
				'title' => $label,
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for author archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_author_trail( $crumbs, $args ) {
		$author = get_queried_object();

		if ( ! $author instanceof WP_User ) {
			return $crumbs;
		}

		if ( ! empty( $args['show_current'] ) ) {
			$crumbs[] = [
				'url'   => ! empty( $args['link_current'] ) ? get_author_posts_url( $author->ID ) : '',
				'title' => sprintf(
					/* translators: %s: author name */
					__( 'Author: %s', 'saman-seo' ),
					$author->display_name
				),
			];
		}

		return $crumbs;
	}

	/**
	 * Build trail for date archives.
	 *
	 * @param array $crumbs Existing crumbs.
	 * @param array $args   Arguments.
	 * @return array
	 */
	private function build_date_trail( $crumbs, $args ) {
		// Year.
		if ( is_year() ) {
			if ( ! empty( $args['show_current'] ) ) {
				$crumbs[] = [
					'url'   => '',
					'title' => get_the_date( 'Y' ),
				];
			}
		}
		// Month.
		elseif ( is_month() ) {
			$crumbs[] = [
				'url'   => get_year_link( get_the_date( 'Y' ) ),
				'title' => get_the_date( 'Y' ),
			];

			if ( ! empty( $args['show_current'] ) ) {
				$crumbs[] = [
					'url'   => '',
					'title' => get_the_date( 'F' ),
				];
			}
		}
		// Day.
		elseif ( is_day() ) {
			$crumbs[] = [
				'url'   => get_year_link( get_the_date( 'Y' ) ),
				'title' => get_the_date( 'Y' ),
			];

			$crumbs[] = [
				'url'   => get_month_link( get_the_date( 'Y' ), get_the_date( 'm' ) ),
				'title' => get_the_date( 'F' ),
			];

			if ( ! empty( $args['show_current'] ) ) {
				$crumbs[] = [
					'url'   => '',
					'title' => get_the_date( 'j' ),
				];
			}
		}

		return $crumbs;
	}

	/**
	 * Truncate breadcrumb titles.
	 *
	 * @param array $crumbs Breadcrumbs.
	 * @param int   $length Max length.
	 * @return array
	 */
	private function truncate_titles( $crumbs, $length ) {
		foreach ( $crumbs as &$crumb ) {
			if ( strlen( $crumb['title'] ) > $length ) {
				$crumb['title'] = substr( $crumb['title'], 0, $length - 3 ) . '...';
			}
		}

		return $crumbs;
	}

	/**
	 * Get the separator HTML.
	 *
	 * @param array $args Arguments.
	 * @return string
	 */
	public function get_separator( $args = [] ) {
		$settings  = $this->get_settings();
		$args      = wp_parse_args( $args, $settings );
		$separator = $args['separator'] ?? '>';

		if ( 'custom' === $separator && ! empty( $args['separator_custom'] ) ) {
			return esc_html( $args['separator_custom'] );
		}

		return $this->separator_options[ $separator ] ?? '&raquo;';
	}

	/**
	 * Render breadcrumbs HTML.
	 *
	 * @param array $args Optional arguments.
	 * @return string
	 */
	public function render( $args = [] ) {
		$crumbs = $this->get_breadcrumbs( $args );

		if ( empty( $crumbs ) ) {
			return '';
		}

		$settings  = $this->get_settings();
		$args      = wp_parse_args( $args, $settings );
		$separator = $this->get_separator( $args );
		$preset    = $args['style_preset'] ?? 'default';
		$total     = count( $crumbs );

		ob_start();
		?>
		<nav class="saman-seo-breadcrumbs saman-seo-breadcrumbs--<?php echo esc_attr( $preset ); ?>" aria-label="<?php esc_attr_e( 'Breadcrumb', 'saman-seo' ); ?>">
			<ol class="saman-seo-breadcrumbs__list" itemscope itemtype="https://schema.org/BreadcrumbList">
				<?php foreach ( $crumbs as $index => $crumb ) : ?>
					<?php
					$is_last  = ( $index === $total - 1 );
					$position = $index + 1;
					?>
					<li class="saman-seo-breadcrumbs__item<?php echo $is_last ? ' saman-seo-breadcrumbs__item--current' : ''; ?>" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
						<?php if ( ! empty( $crumb['url'] ) && ! $is_last ) : ?>
							<a class="saman-seo-breadcrumbs__link" href="<?php echo esc_url( $crumb['url'] ); ?>" itemprop="item">
								<span itemprop="name"><?php echo esc_html( $crumb['title'] ); ?></span>
							</a>
						<?php else : ?>
							<span class="saman-seo-breadcrumbs__current" itemprop="item">
								<span itemprop="name"><?php echo esc_html( $crumb['title'] ); ?></span>
							</span>
						<?php endif; ?>
						<meta itemprop="position" content="<?php echo esc_attr( $position ); ?>" />
						<?php if ( ! $is_last ) : ?>
							<span class="saman-seo-breadcrumbs__separator" aria-hidden="true"><?php echo $separator; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
						<?php endif; ?>
					</li>
				<?php endforeach; ?>
			</ol>
		</nav>
		<?php

		return trim( ob_get_clean() );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function shortcode( $atts ) {
		$atts = shortcode_atts(
			[
				'separator'    => '',
				'show_home'    => '',
				'home_label'   => '',
				'show_current' => '',
				'style'        => '',
			],
			$atts,
			'SAMAN_SEO_breadcrumbs'
		);

		$args = [];

		if ( '' !== $atts['separator'] ) {
			$args['separator'] = $atts['separator'];
		}

		if ( '' !== $atts['show_home'] ) {
			$args['show_home'] = filter_var( $atts['show_home'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( '' !== $atts['home_label'] ) {
			$args['home_label'] = $atts['home_label'];
		}

		if ( '' !== $atts['show_current'] ) {
			$args['show_current'] = filter_var( $atts['show_current'], FILTER_VALIDATE_BOOLEAN );
		}

		if ( '' !== $atts['style'] ) {
			$args['style_preset'] = $atts['style'];
		}

		return $this->render( $args );
	}

	/**
	 * Register Gutenberg block.
	 *
	 * @return void
	 */
	public function register_block() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		// Register the editor script.
		wp_register_script(
			'saman-seo-breadcrumbs-block',
			SAMAN_SEO_URL . 'blocks/breadcrumbs/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-server-side-render', 'wp-i18n' ],
			SAMAN_SEO_VERSION,
			true
		);

		register_block_type(
			'saman-seo/breadcrumbs',
			[
				'editor_script'   => 'saman-seo-breadcrumbs-block',
				'render_callback' => [ $this, 'render_block' ],
				'attributes'      => [
					'separator'   => [
						'type'    => 'string',
						'default' => '',
					],
					'showHome'    => [
						'type'    => 'boolean',
						'default' => true,
					],
					'homeLabel'   => [
						'type'    => 'string',
						'default' => '',
					],
					'showCurrent' => [
						'type'    => 'boolean',
						'default' => true,
					],
					'linkCurrent' => [
						'type'    => 'boolean',
						'default' => false,
					],
					'stylePreset' => [
						'type'    => 'string',
						'default' => '',
					],
				],
			]
		);
	}

	/**
	 * Render Gutenberg block.
	 *
	 * @param array $attributes Block attributes.
	 * @return string
	 */
	public function render_block( $attributes ) {
		$args = [];

		if ( ! empty( $attributes['separator'] ) ) {
			$args['separator'] = $attributes['separator'];
		}

		if ( isset( $attributes['showHome'] ) ) {
			$args['show_home'] = $attributes['showHome'];
		}

		if ( ! empty( $attributes['homeLabel'] ) ) {
			$args['home_label'] = $attributes['homeLabel'];
		}

		if ( isset( $attributes['showCurrent'] ) ) {
			$args['show_current'] = $attributes['showCurrent'];
		}

		if ( isset( $attributes['linkCurrent'] ) ) {
			$args['link_current'] = $attributes['linkCurrent'];
		}

		if ( ! empty( $attributes['stylePreset'] ) ) {
			$args['style_preset'] = $attributes['stylePreset'];
		}

		return $this->render( $args );
	}

	/**
	 * Add breadcrumb JSON-LD schema.
	 *
	 * @param array $payload Existing JSON-LD payload.
	 * @return array
	 */
	public function add_breadcrumb_schema( $payload ) {
		$settings = $this->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return $payload;
		}

		$crumbs = $this->get_breadcrumbs();

		if ( empty( $crumbs ) ) {
			return $payload;
		}

		$items = [];
		foreach ( $crumbs as $index => $crumb ) {
			$item = [
				'@type'    => 'ListItem',
				'position' => $index + 1,
				'name'     => $crumb['title'],
			];

			if ( ! empty( $crumb['url'] ) ) {
				$item['item'] = $crumb['url'];
			}

			$items[] = $item;
		}

		$breadcrumb_schema = [
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $items,
		];

		// Handle @graph structure.
		if ( isset( $payload['@graph'] ) && is_array( $payload['@graph'] ) ) {
			$payload['@graph'][] = $breadcrumb_schema;
		} elseif ( isset( $payload['@type'] ) ) {
			// Convert to @graph structure.
			$payload = [
				'@context' => 'https://schema.org',
				'@graph'   => [ $payload, $breadcrumb_schema ],
			];
		} else {
			// Just add breadcrumb schema.
			$payload = array_merge( $payload, $breadcrumb_schema );
		}

		return $payload;
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		$settings = $this->get_settings();

		if ( empty( $settings['enabled'] ) ) {
			return;
		}

		wp_enqueue_style(
			'saman-seo-breadcrumbs',
			SAMAN_SEO_URL . 'assets/css/breadcrumbs.css',
			[],
			SAMAN_SEO_VERSION
		);
	}

	/**
	 * Get available separator options.
	 *
	 * @return array
	 */
	public function get_separator_options() {
		return [
			'>'       => __( 'Angle bracket (>)', 'saman-seo' ),
			'/'       => __( 'Slash (/)', 'saman-seo' ),
			'|'       => __( 'Pipe (|)', 'saman-seo' ),
			'-'       => __( 'Dash (-)', 'saman-seo' ),
			'arrow'   => __( 'Arrow (ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢)', 'saman-seo' ),
			'chevron' => __( 'Chevron (ÃƒÂ¢Ã¢â€šÂ¬Ã‚Âº)', 'saman-seo' ),
			'custom'  => __( 'Custom', 'saman-seo' ),
		];
	}

	/**
	 * Get available style presets.
	 *
	 * @return array
	 */
	public function get_style_presets() {
		return [
			'default' => __( 'Default', 'saman-seo' ),
			'minimal' => __( 'Minimal', 'saman-seo' ),
			'rounded' => __( 'Rounded', 'saman-seo' ),
			'pills'   => __( 'Pills', 'saman-seo' ),
			'none'    => __( 'No styling', 'saman-seo' ),
		];
	}
}
