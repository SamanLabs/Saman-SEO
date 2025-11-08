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
	 * Determine default title using template tags.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string
	 */
	function generate_title_from_template( $post ) {
		$template = get_option( 'wpseopilot_default_title_template', '%post_title% | %site_title%' );

		$replacements = [
			'%post_title%'  => \wp_strip_all_tags( $post->post_title ),
			'%site_title%'  => \get_bloginfo( 'name' ),
			'%tagline%'     => \get_bloginfo( 'description' ),
			'%post_author%' => get_the_author_meta( 'display_name', $post->post_author ),
		];

		return strtr( $template, $replacements );
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

		if ( $post && ! is_front_page() ) {
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

		ob_start();
		?>
		<nav class="wpseopilot-breadcrumb" aria-label="Breadcrumb">
			<ol>
				<?php foreach ( $crumbs as $crumb ) : ?>
					<li><a href="<?php echo esc_url( $crumb['url'] ); ?>"><?php echo esc_html( $crumb['title'] ); ?></a></li>
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
}
