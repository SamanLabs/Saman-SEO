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
		add_action( 'wp_head', [ $this, 'render_admin_bar_styles' ], 100 );
		add_action( 'admin_head', [ $this, 'render_admin_bar_styles' ], 100 );

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
			'title' => '<svg class="saman-seo-ab-icon" width="16" height="16" viewBox="0 0 24 24" fill="currentColor"><path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z"/></svg><span class="saman-seo-ab-text">Saman SEO</span>',
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
	 * Render admin bar styles.
	 *
	 * @return void
	 */
	public function render_admin_bar_styles() {
		if ( ! is_admin_bar_showing() ) {
			return;
		}

		// Only show for users who can edit posts
		if ( ! current_user_can( 'edit_posts' ) ) {
			return;
		}
		?>
		<style id="saman-seo-admin-bar-css">
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
		</style>
		<?php
	}
}
