<?php
/**
 * Lightweight Audit and reporting.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use WP_Post;
use function Saman\SEO\Helpers\generate_title_from_template;

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
		// V1 menu disabled - React UI handles menu registration
		// add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_filter( 'SAMAN_SEO_link_suggestions', [ $this, 'link_suggestions' ], 10, 2 );
	}

	/**
	 * Add submenu.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'saman-seo',
			__( 'Audit', 'saman-seo' ),
			__( 'Audit', 'saman-seo' ),
			'manage_options',
			'saman-seo-audit',
			[ $this, 'render_page' ],
			13
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

		wp_enqueue_style(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/css/admin.css',
			[],
			SAMAN_SEO_VERSION
		);

		wp_enqueue_style(
			'saman-seo-plugin',
			SAMAN_SEO_URL . 'assets/css/plugin.css',
			[],
			SAMAN_SEO_VERSION
		);

		$results = $this->collect_issues();
		$issues  = $results['issues'];
		$stats   = $results['stats'];
		$scanned = $results['scanned'];
		$recommendations = $results['recommendations'];

		include SAMAN_SEO_PATH . 'templates/audit.php';
	}

	/**
	 * Evaluate posts for SEO issues.
	 *
	 * @return array{
	 *     issues:array<int,array<string,mixed>>,
	 *     stats:array<string,mixed>,
	 *     scanned:int,
	 *     recommendations:array<int,array<string,mixed>>
	 * }
	 */
	private function collect_issues() {
		$data = [
			'issues'          => [],
			'stats'           => [
				'severity' => [
					'high'   => 0,
					'medium' => 0,
					'low'    => 0,
				],
				'types'  => [],
				'total'  => 0,
				'posts'  => [],
			],
			'scanned'        => 0,
			'recommendations' => [],
		];

		$query = new \WP_Query(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
			]
		);

		$post_type_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );
		if ( ! is_array( $post_type_descriptions ) ) {
			$post_type_descriptions = [];
		}

		while ( $query->have_posts() ) {
			$query->the_post();
			$post_id = get_the_ID();
			$title   = get_the_title();
			$post    = get_post( $post_id );
			$content = get_the_content( null, false, $post );
			$meta    = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

			++$data['scanned'];

			if ( empty( $meta['title'] ) || strlen( $meta['title'] ) > 65 ) {
				$data = $this->add_issue(
					$data,
					[
						'post_id'  => $post_id,
						'title'    => $title,
						'severity' => empty( $meta['title'] ) ? 'high' : 'medium',
						'message'  => empty( $meta['title'] ) ? __( 'Missing meta title.', 'saman-seo' ) : __( 'Meta title longer than 65 characters.', 'saman-seo' ),
						'action'   => __( 'Edit SEO fields.', 'saman-seo' ),
						'type'     => empty( $meta['title'] ) ? 'title_missing' : 'title_length',
					]
				);

				if ( empty( $meta['title'] ) ) {
					$this->ensure_recommendation( $data['recommendations'], $post, $post_type_descriptions );
				}
			}

			if ( empty( $meta['description'] ) ) {
				$data = $this->add_issue(
					$data,
					[
						'post_id'  => $post_id,
						'title'    => $title,
						'severity' => 'high',
						'message'  => __( 'Missing meta description.', 'saman-seo' ),
						'action'   => __( 'Add keyword-rich summary.', 'saman-seo' ),
						'type'     => 'description_missing',
					]
				);
				$this->ensure_recommendation( $data['recommendations'], $post, $post_type_descriptions );
			}

			if ( substr_count( $content, ' alt="' ) < substr_count( $content, '<img' ) ) {
				$data = $this->add_issue(
					$data,
					[
						'post_id'  => $post_id,
						'title'    => $title,
						'severity' => 'medium',
						'message'  => __( 'Images missing alt text.', 'saman-seo' ),
						'action'   => __( 'Add descriptive alt attributes.', 'saman-seo' ),
						'type'     => 'missing_alt',
					]
				);
			}
		}

		wp_reset_postdata();

		$data['stats']['posts_with_issues'] = count( $data['stats']['posts'] );
		unset( $data['stats']['posts'] );

		arsort( $data['stats']['types'] );
		$data['recommendations'] = array_values( $data['recommendations'] );

		return $data;
	}

	/**
	 * Track statistics for an issue.
	 *
	 * @param array $data  Data bucket.
	 * @param array $issue Issue payload.
	 *
	 * @return array
	 */
	private function add_issue( $data, $issue ) {
		$data['issues'][] = $issue;
		++$data['stats']['total'];

		$severity = $issue['severity'];
		if ( isset( $data['stats']['severity'][ $severity ] ) ) {
			++$data['stats']['severity'][ $severity ];
		} else {
			$data['stats']['severity'][ $severity ] = 1;
		}

		$type = $issue['type'] ?? 'general';
		$data['stats']['types'][ $type ] = ( $data['stats']['types'][ $type ] ?? 0 ) + 1;
		$data['stats']['posts'][ $issue['post_id'] ] = true;

		return $data;
	}

	/**
	 * Build a recommendation payload per post if missing metadata.
	 *
	 * @param array         $recommendations Current map.
	 * @param WP_Post|false $post            Post object.
	 * @param array         $type_descriptions Default descriptions.
	 *
	 * @return void
	 */
	private function ensure_recommendation( &$recommendations, $post, $type_descriptions ) {
		if ( ! $post instanceof WP_Post ) {
			return;
		}

		if ( isset( $recommendations[ $post->ID ] ) ) {
			return;
		}

		$title_suggestion = generate_title_from_template( $post );
		if ( empty( $title_suggestion ) ) {
			$title_suggestion = get_the_title( $post );
		}

		$excerpt = $post->post_excerpt ?: wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 );
		if ( empty( $excerpt ) && ! empty( $type_descriptions[ $post->post_type ] ) ) {
			$excerpt = $type_descriptions[ $post->post_type ];
		}

		$tags = get_the_tags( $post->ID );
		if ( $tags ) {
			$tag_names = wp_list_pluck( $tags, 'name' );
		} else {
			$tag_names = wp_list_pluck( get_the_category( $post->ID ), 'name' );
		}

		$recommendations[ $post->ID ] = [
			'post_id'               => $post->ID,
			'title'                 => get_the_title( $post ),
			'edit_url'              => get_edit_post_link( $post->ID ),
			'suggested_title'       => $title_suggestion,
			'suggested_description' => $excerpt,
			'suggested_tags'        => array_filter( (array) $tag_names ),
		];
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
				'posts_per_page' => 6, // Fetch a buffer so we can skip the current post without using post__not_in.
				's'              => $post->post_title,
				'no_found_rows'  => true,
			]
		);

		$list = [];
		while ( $query->have_posts() && count( $list ) < 5 ) {
			$query->the_post();
			if ( get_the_ID() === $post_id ) {
				continue;
			}
			$list[] = [
				'title' => get_the_title(),
				'url'   => get_permalink(),
			];
		}
		wp_reset_postdata();

		return $list;
	}
}
