<?php
/**
 * Lightweight SEO audit and reporting.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Audit service.
 */
class Audit {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_filter( 'wpseopilot_link_suggestions', [ $this, 'link_suggestions' ], 10, 2 );
	}

	/**
	 * Add submenu.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'wpseopilot',
			__( 'SEO Audit', 'wp-seo-pilot' ),
			__( 'SEO Audit', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-audit',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render audit table.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$issues = $this->collect_issues();

		include WPSEOPILOT_PATH . 'templates/audit.php';
	}

	/**
	 * Evaluate posts for SEO issues.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function collect_issues() {
		$issues = [];

		$query = new \WP_Query(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
			]
		);

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$title   = get_the_title();
			$meta    = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

			if ( empty( $meta['title'] ) || strlen( $meta['title'] ) > 65 ) {
				$issues[] = [
					'post_id'  => $post_id,
					'title'    => $title,
					'severity' => empty( $meta['title'] ) ? 'high' : 'medium',
					'message'  => empty( $meta['title'] ) ? __( 'Missing meta title.', 'wp-seo-pilot' ) : __( 'Meta title longer than 65 characters.', 'wp-seo-pilot' ),
					'action'   => __( 'Edit SEO fields.', 'wp-seo-pilot' ),
				];
			}

			if ( empty( $meta['description'] ) ) {
				$issues[] = [
					'post_id'  => $post_id,
					'title'    => $title,
					'severity' => 'high',
					'message'  => __( 'Missing meta description.', 'wp-seo-pilot' ),
					'action'   => __( 'Add keyword-rich summary.', 'wp-seo-pilot' ),
				];
			}

			if ( substr_count( wp_strip_all_tags( get_the_content() ), ' alt="' ) < substr_count( get_the_content(), '<img' ) ) {
				$issues[] = [
					'post_id'  => $post_id,
					'title'    => $title,
					'severity' => 'medium',
					'message'  => __( 'Images missing alt text.', 'wp-seo-pilot' ),
					'action'   => __( 'Add descriptive alt attributes.', 'wp-seo-pilot' ),
				];
			}
		}

		wp_reset_postdata();

		return $issues;
	}

	/**
	 * Suggest internal links for a post.
	 *
	 * @param array $suggestions Defaults.
	 * @param int   $post_id     Post ID.
	 *
	 * @return array
	 */
	public function link_suggestions( $suggestions, $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return $suggestions;
		}

		$keywords = wp_list_pluck( get_the_category( $post_id ), 'slug' );

		$query = new \WP_Query(
			[
				'post_type'      => $post->post_type,
				'post__not_in'   => [ $post_id ],
				'posts_per_page' => 5,
				's'              => $post->post_title,
			]
		);

		$list = [];
		while ( $query->have_posts() ) {
			$query->the_post();
			$list[] = [
				'title' => get_the_title(),
				'url'   => get_permalink(),
			];
		}
		wp_reset_postdata();

		return $list;
	}
}
