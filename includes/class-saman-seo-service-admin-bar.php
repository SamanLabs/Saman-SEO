<?php
/**
 * Admin Bar SEO Command Center
 *
 * Displays Saman SEO menu in WordPress admin bar.
 * Shows SEO score indicator on single posts/pages, navigation links everywhere else.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use WP_Post;
use function Saman\SEO\Helpers\calculate_seo_score;

defined( 'ABSPATH' ) || exit;

/**
 * Admin Bar SEO indicator service.
 */
class Admin_Bar {

	/**
	 * Boot admin bar hooks.
	 *
	 * @return void
	 */
	public function boot() {
		// Check if admin bar is enabled in settings.
		if ( ! \Saman\SEO\Helpers\module_enabled( 'admin_bar' ) ) {
			return;
		}

		add_action( 'admin_bar_menu', [ $this, 'add_seo_menu' ], 100 );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_admin_bar_styles' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_bar_styles' ] );

		// Enqueue dashicons on frontend for admin bar icons
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_dashicons' ] );
	}

	/**
	 * Enqueue dashicons on frontend for admin bar.
	 *
	 * @return void
	 */
	public function enqueue_dashicons() {
		if ( is_admin_bar_showing() && current_user_can( 'edit_posts' ) ) {
			wp_enqueue_style( 'dashicons' );
		}
	}

	/**
	 * Add SEO menu to admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	public function add_seo_menu( $wp_admin_bar ) {
		// Only show for users who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		// Check if we're on a single post/page context
		$post = $this->get_current_post();
		$has_post_context = $post instanceof WP_Post;

		// Build menu based on context
		if ( $has_post_context ) {
			$this->add_post_seo_menu( $wp_admin_bar, $post );
		} else {
			$this->add_general_menu( $wp_admin_bar );
		}
	}

	/**
	 * Add SEO menu with post score details.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @param WP_Post       $post         Current post.
	 * @return void
	 */
	private function add_post_seo_menu( $wp_admin_bar, $post ) {
		// Get SEO score
		$seo_data = $this->get_seo_data( $post );
		$score    = $seo_data['score'] ?? 0;
		$level    = $seo_data['level'] ?? 'poor';
		$issues   = $seo_data['issues'] ?? [];

		// Ensure issues is an array
		if ( ! is_array( $issues ) ) {
			$issues = [];
		}

		// Determine indicator color class
		$color_class = $this->get_color_class( $level );

		// Build the main menu item with score
		$wp_admin_bar->add_node( [
			'id'    => 'saman-seo-seo',
			'title' => $this->render_indicator_html( $score, $color_class ),
			'href'  => get_edit_post_link( $post->ID ),
			'meta'  => [
				'class' => 'saman-seo-admin-bar-item saman-seo-admin-bar-item--has-score',
				// translators: Placeholder values
				'title' => sprintf( __( 'SEO Score: %d/100', 'saman-seo' ), $score ),
			],
		] );

		// Add score details submenu
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-score',
			'parent' => 'saman-seo-seo',
			'title'  => sprintf(
				'<span class="saman-seo-ab-label">%s</span><span class="saman-seo-ab-value">%d/100</span>',
				__( 'Score', 'saman-seo' ),
				$score
			),
			'meta'   => [ 'class' => 'saman-seo-ab-score-item' ],
		] );

		// Add status text
		$status_text = $this->get_status_text( $level );
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-status',
			'parent' => 'saman-seo-seo',
			'title'  => sprintf(
				'<span class="saman-seo-ab-label">%s</span><span class="saman-seo-ab-status saman-seo-ab-status--%s">%s</span>',
				__( 'Status', 'saman-seo' ),
				esc_attr( $level ),
				esc_html( $status_text )
			),
			'meta'   => [ 'class' => 'saman-seo-ab-status-item' ],
		] );

		// Add issues count if any
		$issue_count = count( $issues );
		if ( $issue_count > 0 ) {
			$wp_admin_bar->add_node( [
				'id'     => 'saman-seo-seo-issues',
				'parent' => 'saman-seo-seo',
				'title'  => sprintf(
					'<span class="saman-seo-ab-label">%s</span><span class="saman-seo-ab-value saman-seo-ab-issues">%d</span>',
					__( 'Issues', 'saman-seo' ),
					$issue_count
				),
				'meta'   => [ 'class' => 'saman-seo-ab-issues-item' ],
			] );

			// Show top 3 issues
			$top_issues = array_slice( $issues, 0, 3 );
			foreach ( $top_issues as $index => $issue ) {
				$severity = isset( $issue['severity'] ) ? $issue['severity'] : 'warning';
				$message  = isset( $issue['message'] ) ? $issue['message'] : '';
				$wp_admin_bar->add_node( [
					'id'     => 'saman-seo-seo-issue-' . $index,
					'parent' => 'saman-seo-seo',
					'title'  => sprintf(
						'<span class="saman-seo-ab-issue-icon">%s</span><span class="saman-seo-ab-issue-text">%s</span>',
						'high' === $severity ? '!' : '?',
						esc_html( wp_trim_words( $message, 8, '...' ) )
					),
					'meta'   => [
						'class' => 'saman-seo-ab-issue-item saman-seo-ab-issue--' . esc_attr( $severity ),
					],
				] );
			}
		}

		// Add separator
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-sep',
			'parent' => 'saman-seo-seo',
			'title'  => '<hr class="saman-seo-ab-separator" />',
			'meta'   => [ 'class' => 'saman-seo-ab-separator-item' ],
		] );

		// Add edit link
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-edit',
			'parent' => 'saman-seo-seo',
			'title'  => sprintf(
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg> %s',
				__( 'Edit SEO Settings', 'saman-seo' )
			),
			'href'   => get_edit_post_link( $post->ID ) . '#saman-seo-seo-panel',
			'meta'   => [ 'class' => 'saman-seo-ab-action-link' ],
		] );

		// Add view full analysis link
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-analyze',
			'parent' => 'saman-seo-seo',
			'title'  => sprintf(
				'<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg> %s',
				__( 'Full Analysis', 'saman-seo' )
			),
			'href'   => admin_url( 'admin.php?page=saman-seo-audit&post_id=' . $post->ID ),
			'meta'   => [ 'class' => 'saman-seo-ab-action-link' ],
		] );

		// Add separator before navigation
		$wp_admin_bar->add_node( [
			'id'     => 'saman-seo-seo-sep2',
			'parent' => 'saman-seo-seo',
			'title'  => '<hr class="saman-seo-ab-separator" />',
			'meta'   => [ 'class' => 'saman-seo-ab-separator-item' ],
		] );

		// Add quick navigation links
		$this->add_nav_links( $wp_admin_bar );
	}

	/**
	 * Add general navigation menu (no post context).
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	private function add_general_menu( $wp_admin_bar ) {
		// Build the main menu item (just branding)
		$wp_admin_bar->add_node( [
			'id'    => 'saman-seo-seo',
			'title' => '<svg class="saman-seo-ab-icon" width="16" height="16" viewBox="491 491 2048 2048" fill="currentColor"><path d="M2009.2,1575.62c-20.77,97.29-56.47,168.25-111.98,249.43l44.32,45.17c29.65-3.23,51.62,10.65,71.38,30.51l247,248.18c33.25,33.41,45.11,80.99,29.16,125.89-16.43,46.25-59.94,78.94-109.58,81.07-36.57,1.57-68.29-15.42-93.1-40.42l-49.57-49.96-198.34-199.1c-17.61-17.68-26.2-39.72-25.72-64.12l-48.52-44.54c-4.29,2.17-7.75,4.08-11.36,6.68-174.71,125.76-394.51,153.76-595.73,77.03-41.9-15.98-80.46-36.62-117.89-60.92-63.98-41.55-119.43-93.05-165.46-153.92-39.66-52.45-70.22-110.63-92.23-172.79-47.86-135.17-47.62-277.57-3.08-413.39,37.19-113.4,110.51-217.97,202.41-293.57,55.77-45.87,107.56-76.55,174.88-103.29,50.67-20.12,102.36-32.12,156.56-38.67,106.97-12.93,225.92,4.33,324.11,48.58,42.42,19.12,82.29,39.78,119.05,69.06l-59.71,61.31-28.09,29.35-42.37-24.49c-33.48-19.35-69.27-31.7-106.93-42.27-89.28-25.07-184.2-23.74-273.39,1.03-28.97,8.05-54.55,18.2-81.3,31.09-257.28,123.98-366.11,433.13-239.74,690.72,19.64,40.04,44.9,75.12,73.49,108.79,15.75,18.54,32.2,34.45,50.38,50.37,141.85,124.23,340.92,162.64,517.54,93.77,51.48-20.07,99.06-46.68,141.65-81.9,36.95-30.56,69.48-63.27,97.07-102.83,25.12-36.02,45.92-74.51,61.13-116.11,26.8-73.29,36.13-151.35,28.93-229.05-6.93-72.22-27.7-140.49-63.88-204.66l89.83-92.32c22.95,35.88,40.95,72.55,57.2,111.34,12.81,33.54,23.36,65.9,30.61,101.04,20.68,88.49,20.24,179.09,1.28,267.92Z"/><path d="M958.58,1578.07c-9.76-28.54-16.85-55.64-17.74-85.19-.19-6.31,2.22-10.1,6.66-14.52l149.64-148.92,152.41-152.13c7.93-7.92,49.65-51.13,55.97-50.64,2.04.16,6.05,2.26,8.05,4.25l166.59,165.17,214.54-218.28,165.52-167.93-97.61-98.1,39.87-6.7,150.59-23.78,131.62-19.78-1.87,11.77-26.41,158.67-24.61,149.71-45.07-44.26-50.09-50.26-250.57,254.99-137.84,140.16-65.36,65.68-19.39-19.26-157.09-156.33c-20.87,18.85-38.51,38.26-57.83,57.76l-204.48,206.48-67.63,68.54c-7.72,7.82-15.16-19.12-17.88-27.08Z"/><path d="M1478.64,1861.67c0,7.32-2.73,10.09-8.61,11.13-64.81,11.48-107.08,11.42-171.66-.54-3.65-.68-6.87-3.89-6.87-8.31l.06-475.69c0-7.98,10.04-15.35,16.58-12.66l143.78,144.8c15.11,15.21,26.64,7.34,26.64,27.5l.06,313.78Z"/><path d="M1735.84,1707.89c-38.05,54.54-99.32,100.25-158.33,129.87-3.97,1.99-7.09,4.54-11.89,1.92l.43-359.73c0-7.78.53-17.63,4.55-23.32,7.96-11.27,16.97-19.9,26.69-29.73l153.03-154.62c4.23,8.13,4.92,16.39,4.93,25.6l.3,337.95c.02,17.31-4.09,33.32-8.31,49.63-2,7.74-6.81,15.85-11.39,22.41Z"/><path d="M1032.27,1708.49c-8.34-12.18-12.95-27.87-7.29-41.4,3.62-8.64,11.57-15.25,17.95-21.98l57.64-60.7,91.89-95.92c.91-.95,4.39-3.67,5.19-2.7s2.27,4.21,2.27,5.91l.03,350.17c-43.86-16.17-87.21-49.17-119.56-78.46-18.32-16.58-33.8-34-48.12-54.92Z"/></svg><span class="saman-seo-ab-text">Saman SEO</span>',
			'href'  => admin_url( 'admin.php?page=saman-seo' ),
			'meta'  => [
				'class' => 'saman-seo-admin-bar-item',
				'title' => __( 'Saman SEO', 'saman-seo' ),
			],
		] );

		// Add quick navigation links
		$this->add_nav_links( $wp_admin_bar );
	}

	/**
	 * Add navigation links to the dropdown.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar Admin bar instance.
	 * @return void
	 */
	private function add_nav_links( $wp_admin_bar ) {
		// SVG icons for each nav item (14x14)
		$icons = [
			'dashboard'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>',
			'audit'      => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>',
			'redirects'  => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M18 11v2h4v-2h-4zm-2 6.61c.96.71 2.21 1.65 3.2 2.39.4-.53.8-1.07 1.2-1.6-.99-.74-2.24-1.68-3.2-2.4-.4.54-.8 1.08-1.2 1.61zM20.4 5.6c-.4-.53-.8-1.07-1.2-1.6-.99.74-2.24 1.68-3.2 2.4.4.53.8 1.07 1.2 1.6.96-.72 2.21-1.65 3.2-2.4zM4 9c-1.1 0-2 .9-2 2v2c0 1.1.9 2 2 2h1v4h2v-4h1l5 3V6L8 9H4zm11.5 3c0-1.33-.58-2.53-1.5-3.35v6.69c.92-.81 1.5-2.01 1.5-3.34z"/></svg>',
			'404'        => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-2h2v2zm0-4h-2V7h2v6z"/></svg>',
			'sitemap'    => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M15 21h2v-2h-2v2zm4-12h2V7h-2v2zM3 5v14c0 1.1.9 2 2 2h4v-2H5V5h4V3H5c-1.1 0-2 .9-2 2zm16-2v2h2c0-1.1-.9-2-2-2zm-8 20h2V1h-2v22zm8-6h2v-2h-2v2zM15 5h2V3h-2v2zm4 8h2v-2h-2v2zm0 8c1.1 0 2-.9 2-2h-2v2z"/></svg>',
			'settings'   => '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor"><path d="M19.14 12.94c.04-.31.06-.63.06-.94 0-.31-.02-.63-.06-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.04.31-.06.63-.06.94s.02.63.06.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/></svg>',
		];

		$nav_items = [
			'dashboard'  => [
				'label' => __( 'Dashboard', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo' ),
			],
			'redirects'  => [
				'label' => __( 'Redirects', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-redirects' ),
			],
			'404'        => [
				'label' => __( '404 Monitor', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-404' ),
			],
			'audit'      => [
				'label' => __( 'Site Audit', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-audit' ),
			],
			'sitemap'    => [
				'label' => __( 'Sitemap', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-sitemap' ),
			],
			'settings'   => [
				'label' => __( 'Settings', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-settings' ),
			],
		];

		foreach ( $nav_items as $key => $item ) {
			$icon = isset( $icons[ $key ] ) ? $icons[ $key ] : '';
			$wp_admin_bar->add_node( [
				'id'     => 'saman-seo-nav-' . $key,
				'parent' => 'saman-seo-seo',
				'title'  => sprintf(
					'%s <span>%s</span>',
					$icon,
					esc_html( $item['label'] )
				),
				'href'   => $item['url'],
				'meta'   => [ 'class' => 'saman-seo-ab-nav-link' ],
			] );
		}
	}

	/**
	 * Render the indicator HTML for the admin bar.
	 *
	 * @param int    $score       SEO score.
	 * @param string $color_class Color class (good, fair, poor).
	 * @return string
	 */
	private function render_indicator_html( $score, $color_class ) {
		return sprintf(
			'<span class="saman-seo-ab-indicator saman-seo-ab-indicator--%s"></span>
			<span class="saman-seo-ab-text">SEO</span>
			<span class="saman-seo-ab-score">%d</span>',
			esc_attr( $color_class ),
			$score
		);
	}

	/**
	 * Get SEO data for a post.
	 *
	 * @param WP_Post $post Post object.
	 * @return array
	 */
	private function get_seo_data( $post ) {
		$meta    = get_post_meta( $post->ID, Post_Meta::META_KEY, true );
		$meta    = is_array( $meta ) ? $meta : [];
		$content = $post->post_content;

		// Calculate SEO score using the helper function
		$score_data = calculate_seo_score(
			$meta['title'] ?? '',
			$meta['description'] ?? '',
			$content,
			$meta['focus_keyphrase'] ?? ''
		);

		// Ensure we return a properly structured array
		return [
			'score'  => $score_data['score'] ?? 0,
			'level'  => $score_data['level'] ?? 'poor',
			'issues' => isset( $score_data['issues'] ) && is_array( $score_data['issues'] ) ? $score_data['issues'] : [],
		];
	}

	/**
	 * Get color class based on score level.
	 *
	 * @param string $level Score level (good, fair, poor).
	 * @return string
	 */
	private function get_color_class( $level ) {
		$classes = [
			'good' => 'good',
			'fair' => 'fair',
			'poor' => 'poor',
		];

		return $classes[ $level ] ?? 'poor';
	}

	/**
	 * Get status text based on level.
	 *
	 * @param string $level Score level.
	 * @return string
	 */
	private function get_status_text( $level ) {
		$texts = [
			'good' => __( 'Good', 'saman-seo' ),
			'fair' => __( 'Needs Work', 'saman-seo' ),
			'poor' => __( 'Poor', 'saman-seo' ),
		];

		return $texts[ $level ] ?? __( 'Unknown', 'saman-seo' );
	}

	/**
	 * Get the current post being viewed or edited.
	 *
	 * @return WP_Post|null
	 */
	private function get_current_post() {
		// Frontend single post/page
		if ( ! is_admin() && is_singular() ) {
			$post = get_post();
			return $post instanceof WP_Post ? $post : null;
		}

		// Admin post edit screen
		if ( is_admin() ) {
			global $pagenow, $post;

			// Classic editor
			if ( in_array( $pagenow, [ 'post.php', 'post-new.php' ], true ) ) {
				if ( $post instanceof WP_Post ) {
					return $post;
				}
				// Try to get from query string
				$post_id = isset( $_GET['post'] ) ? absint( $_GET['post'] ) : 0;
				if ( $post_id ) {
					$fetched_post = get_post( $post_id );
					return $fetched_post instanceof WP_Post ? $fetched_post : null;
				}
			}
		}

		return null;
	}

	/**
	 * Enqueue admin bar styles.
	 *
	 * @return void
	 */
	public function enqueue_admin_bar_styles() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		// Only show for users who can edit posts.
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}

		$css = '
			/* Admin Bar SEO Menu */
			#wpadminbar .saman-seo-admin-bar-item > .ab-item {
				display: flex !important;
				align-items: center;
				gap: 6px;
				height: 32px;
			}

			#wpadminbar .saman-seo-ab-icon {
				font-size: 16px;
				width: 16px;
				height: 16px;
				line-height: 16px;
			}

			#wpadminbar .saman-seo-ab-indicator {
				width: 10px;
				height: 10px;
				border-radius: 50%;
				flex-shrink: 0;
				box-shadow: 0 0 0 2px rgba(255,255,255,0.2);
			}

			#wpadminbar .saman-seo-ab-indicator--good {
				background: #00a32a;
				box-shadow: 0 0 0 2px rgba(0,163,42,0.3);
			}

			#wpadminbar .saman-seo-ab-indicator--fair {
				background: #dba617;
				box-shadow: 0 0 0 2px rgba(219,166,23,0.3);
			}

			#wpadminbar .saman-seo-ab-indicator--poor {
				background: #d63638;
				box-shadow: 0 0 0 2px rgba(214,54,56,0.3);
			}

			#wpadminbar .saman-seo-ab-text {
				font-weight: 600;
				font-size: 11px;
				text-transform: uppercase;
				letter-spacing: 0.5px;
			}

			#wpadminbar .saman-seo-ab-score {
				background: rgba(255,255,255,0.1);
				padding: 2px 6px;
				border-radius: 3px;
				font-size: 11px;
				font-weight: 600;
			}

			/* Dropdown Styles */
			#wpadminbar .saman-seo-admin-bar-item .ab-submenu {
				min-width: 200px !important;
				padding: 8px 0 !important;
			}

			#wpadminbar .saman-seo-admin-bar-item .ab-submenu .ab-item {
				display: flex !important;
				align-items: center;
				padding: 6px 12px !important;
				line-height: 1.4 !important;
			}

			#wpadminbar .saman-seo-ab-label {
				color: rgba(255,255,255,0.6);
				font-size: 11px;
				flex: 1;
			}

			#wpadminbar .saman-seo-ab-value {
				font-weight: 600;
				font-size: 12px;
			}

			#wpadminbar .saman-seo-ab-status {
				font-weight: 600;
				font-size: 11px;
				padding: 2px 8px;
				border-radius: 3px;
			}

			#wpadminbar .saman-seo-ab-status--good {
				background: rgba(0,163,42,0.2);
				color: #68de7c;
			}

			#wpadminbar .saman-seo-ab-status--fair {
				background: rgba(219,166,23,0.2);
				color: #f0c33c;
			}

			#wpadminbar .saman-seo-ab-status--poor {
				background: rgba(214,54,56,0.2);
				color: #f86368;
			}

			#wpadminbar .saman-seo-ab-issues {
				background: rgba(214,54,56,0.2);
				color: #f86368;
				padding: 2px 8px;
				border-radius: 3px;
			}

			/* Issue items */
			#wpadminbar .saman-seo-ab-issue-item .ab-item {
				font-size: 11px !important;
				color: rgba(255,255,255,0.7) !important;
				gap: 8px;
			}

			#wpadminbar .saman-seo-ab-issue-icon {
				width: 16px;
				height: 16px;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				font-size: 10px;
				font-weight: 700;
				flex-shrink: 0;
			}

			#wpadminbar .saman-seo-ab-issue--high .saman-seo-ab-issue-icon {
				background: rgba(214,54,56,0.3);
				color: #f86368;
			}

			#wpadminbar .saman-seo-ab-issue--warning .saman-seo-ab-issue-icon,
			#wpadminbar .saman-seo-ab-issue--medium .saman-seo-ab-issue-icon {
				background: rgba(219,166,23,0.3);
				color: #f0c33c;
			}

			#wpadminbar .saman-seo-ab-issue-text {
				flex: 1;
				white-space: nowrap;
				overflow: hidden;
				text-overflow: ellipsis;
			}

			/* Separator */
			#wpadminbar .saman-seo-ab-separator {
				margin: 6px 12px;
				border: 0;
				border-top: 1px solid rgba(255,255,255,0.1);
			}

			#wpadminbar .saman-seo-ab-separator-item .ab-item {
				padding: 0 !important;
				height: auto !important;
			}

			/* Action & Nav links */
			#wpadminbar .saman-seo-ab-action-link .ab-item,
			#wpadminbar .saman-seo-ab-nav-link .ab-item {
				display: flex !important;
				align-items: center;
				gap: 8px;
			}

			#wpadminbar .saman-seo-ab-action-link svg,
			#wpadminbar .saman-seo-ab-nav-link svg {
				flex-shrink: 0;
				opacity: 0.7;
			}

			#wpadminbar .saman-seo-ab-action-link:hover svg,
			#wpadminbar .saman-seo-ab-nav-link:hover svg {
				opacity: 1;
			}

			/* Hover states */
			#wpadminbar .saman-seo-admin-bar-item:hover > .ab-item {
				background: rgba(255,255,255,0.1) !important;
			}
		';

		wp_add_inline_style( 'admin-bar', $css );
	}
}
