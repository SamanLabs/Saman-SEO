<?php
/**
 * Sitemap Settings Admin UI
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap Settings controller.
 */
class Sitemap_Settings {

	/**
	 * Option keys with defaults.
	 *
	 * @var array<string,mixed>
	 */
	private $defaults = [
		'wpseopilot_sitemap_enabled'                => '1',
		'wpseopilot_sitemap_max_urls'              => 1000,
		'wpseopilot_sitemap_enable_index'          => '1',
		'wpseopilot_sitemap_dynamic_generation'    => '1',
		'wpseopilot_sitemap_schedule_updates'      => '',
		'wpseopilot_sitemap_post_types'            => [],
		'wpseopilot_sitemap_taxonomies'            => [],
		'wpseopilot_sitemap_include_author_pages'  => '0',
		'wpseopilot_sitemap_include_date_archives' => '0',
		'wpseopilot_sitemap_exclude_images'        => '0',
		'wpseopilot_sitemap_enable_rss'            => '0',
		'wpseopilot_sitemap_enable_google_news'    => '0',
		'wpseopilot_sitemap_google_news_name'      => '',
		'wpseopilot_sitemap_google_news_post_types' => [],
		'wpseopilot_sitemap_additional_pages'      => [],
	];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'wp_ajax_wpseopilot_regenerate_sitemap', [ $this, 'ajax_regenerate_sitemap' ] );

		// Schedule sitemap regeneration if enabled
		if ( get_option( 'wpseopilot_sitemap_schedule_updates', '' ) ) {
			add_action( 'wpseopilot_sitemap_cron', [ $this, 'regenerate_sitemap' ] );
		}
	}

	/**
	 * Register settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'wpseopilot',
			__( 'Sitemap Settings', 'wp-seo-pilot' ),
			__( 'Sitemap', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-sitemap',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Register all sitemap settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		// Initialize defaults
		foreach ( $this->defaults as $key => $default ) {
			add_option( $key, $default );
		}

		$group = 'wpseopilot_sitemap';

		register_setting( $group, 'wpseopilot_sitemap_enabled', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_max_urls', 'absint' );
		register_setting( $group, 'wpseopilot_sitemap_enable_index', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_dynamic_generation', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_schedule_updates', [ $this, 'sanitize_schedule' ] );
		register_setting( $group, 'wpseopilot_sitemap_post_types', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'wpseopilot_sitemap_taxonomies', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'wpseopilot_sitemap_include_author_pages', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_include_date_archives', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_exclude_images', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_enable_rss', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_enable_google_news', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_sitemap_google_news_name', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_sitemap_google_news_post_types', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'wpseopilot_sitemap_additional_pages', [ $this, 'sanitize_additional_pages' ] );
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'wp-seo-pilot_page_wpseopilot-sitemap' !== $hook ) {
			return;
		}

		// Enqueue new modern plugin styles, NOT the admin.js that converts inputs
		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

		// Enqueue jQuery separately for our inline scripts
		wp_enqueue_script( 'jquery' );

		// Add inline script data without loading the full admin.js
		wp_localize_script(
			'jquery',
			'WPSEOPilotSitemap',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'wpseopilot_sitemap_action' ),
				'strings'  => [
					'regenerating' => __( 'Regenerating sitemap...', 'wp-seo-pilot' ),
					'success'      => __( 'Sitemap regenerated successfully!', 'wp-seo-pilot' ),
					'error'        => __( 'Failed to regenerate sitemap.', 'wp-seo-pilot' ),
				],
			]
		);
	}

	/**
	 * Render sitemap settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Handle form submission
		if ( isset( $_POST['wpseopilot_sitemap_submit'] ) && check_admin_referer( 'wpseopilot_sitemap_settings' ) ) {
			$this->save_settings();
			echo '<div class="notice notice-success"><p>' . esc_html__( 'Settings saved successfully!', 'wp-seo-pilot' ) . '</p></div>';
		}

		// Prepare all variables for template
		$enabled                = get_option( 'wpseopilot_sitemap_enabled', '1' );
		$max_urls              = get_option( 'wpseopilot_sitemap_max_urls', 1000 );
		$enable_index          = get_option( 'wpseopilot_sitemap_enable_index', '1' );
		$dynamic_generation    = get_option( 'wpseopilot_sitemap_dynamic_generation', '1' );
		$schedule_updates      = get_option( 'wpseopilot_sitemap_schedule_updates', '' );
		$selected_post_types   = get_option( 'wpseopilot_sitemap_post_types', null );
		$selected_taxonomies   = get_option( 'wpseopilot_sitemap_taxonomies', null );
		$include_author        = get_option( 'wpseopilot_sitemap_include_author_pages', '0' );
		$include_date          = get_option( 'wpseopilot_sitemap_include_date_archives', '0' );
		$exclude_images        = get_option( 'wpseopilot_sitemap_exclude_images', '0' );
		$enable_rss            = get_option( 'wpseopilot_sitemap_enable_rss', '0' );
		$enable_google_news    = get_option( 'wpseopilot_sitemap_enable_google_news', '0' );
		$google_news_name      = get_option( 'wpseopilot_sitemap_google_news_name', get_bloginfo( 'name' ) );
		$google_news_post_types = get_option( 'wpseopilot_sitemap_google_news_post_types', [] );
		$additional_pages      = get_option( 'wpseopilot_sitemap_additional_pages', [] );

		// Get available post types
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		// Get available taxonomies
		$taxonomies = get_taxonomies(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		// If null (never been set), default to all
		if ( null === $selected_post_types ) {
			$selected_post_types = array_keys( $post_types );
		}

		// If null (never been set), default to all
		if ( null === $selected_taxonomies ) {
			$selected_taxonomies = array_keys( $taxonomies );
		}

		// Ensure arrays
		if ( ! is_array( $selected_post_types ) ) {
			$selected_post_types = [];
		}
		if ( ! is_array( $selected_taxonomies ) ) {
			$selected_taxonomies = [];
		}

		// Schedule options
		$schedule_options = [
			''         => __( 'No Schedule', 'wp-seo-pilot' ),
			'hourly'   => __( 'Hourly', 'wp-seo-pilot' ),
			'twicedaily' => __( 'Twice Daily', 'wp-seo-pilot' ),
			'daily'    => __( 'Daily', 'wp-seo-pilot' ),
			'weekly'   => __( 'Weekly', 'wp-seo-pilot' ),
		];

		// Load template
		include WPSEOPILOT_PATH . 'templates/sitemap-settings.php';
	}

	/**
	 * Save settings.
	 *
	 * @return void
	 */
	private function save_settings() {
		// Save all settings manually
		update_option( 'wpseopilot_sitemap_enabled', isset( $_POST['wpseopilot_sitemap_enabled'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_max_urls', isset( $_POST['wpseopilot_sitemap_max_urls'] ) ? absint( $_POST['wpseopilot_sitemap_max_urls'] ) : 1000 );
		update_option( 'wpseopilot_sitemap_enable_index', isset( $_POST['wpseopilot_sitemap_enable_index'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_dynamic_generation', isset( $_POST['wpseopilot_sitemap_dynamic_generation'] ) ? '1' : '0' );

		// Handle post types
		$post_types = isset( $_POST['wpseopilot_sitemap_post_types'] ) && is_array( $_POST['wpseopilot_sitemap_post_types'] )
			? array_map( 'sanitize_text_field', $_POST['wpseopilot_sitemap_post_types'] )
			: [];
		update_option( 'wpseopilot_sitemap_post_types', $post_types );

		// Handle taxonomies
		$taxonomies = isset( $_POST['wpseopilot_sitemap_taxonomies'] ) && is_array( $_POST['wpseopilot_sitemap_taxonomies'] )
			? array_map( 'sanitize_text_field', $_POST['wpseopilot_sitemap_taxonomies'] )
			: [];
		update_option( 'wpseopilot_sitemap_taxonomies', $taxonomies );

		update_option( 'wpseopilot_sitemap_include_author_pages', isset( $_POST['wpseopilot_sitemap_include_author_pages'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_include_date_archives', isset( $_POST['wpseopilot_sitemap_include_date_archives'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_exclude_images', isset( $_POST['wpseopilot_sitemap_exclude_images'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_enable_rss', isset( $_POST['wpseopilot_sitemap_enable_rss'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_enable_google_news', isset( $_POST['wpseopilot_sitemap_enable_google_news'] ) ? '1' : '0' );
		update_option( 'wpseopilot_sitemap_google_news_name', isset( $_POST['wpseopilot_sitemap_google_news_name'] ) ? sanitize_text_field( $_POST['wpseopilot_sitemap_google_news_name'] ) : get_bloginfo( 'name' ) );

		// Handle Google News post types
		$google_news_post_types = isset( $_POST['wpseopilot_sitemap_google_news_post_types'] ) && is_array( $_POST['wpseopilot_sitemap_google_news_post_types'] )
			? array_map( 'sanitize_text_field', $_POST['wpseopilot_sitemap_google_news_post_types'] )
			: [];
		update_option( 'wpseopilot_sitemap_google_news_post_types', $google_news_post_types );

		// Handle additional pages
		$additional_pages = [];
		if ( isset( $_POST['wpseopilot_sitemap_additional_pages'] ) && is_array( $_POST['wpseopilot_sitemap_additional_pages'] ) ) {
			foreach ( $_POST['wpseopilot_sitemap_additional_pages'] as $page ) {
				if ( ! empty( $page['url'] ) ) {
					$additional_pages[] = [
						'url'      => esc_url_raw( $page['url'] ),
						'priority' => isset( $page['priority'] ) ? floatval( $page['priority'] ) : 0.5,
					];
				}
			}
		}
		update_option( 'wpseopilot_sitemap_additional_pages', $additional_pages );

		// Schedule updates
		$old_schedule = get_option( 'wpseopilot_sitemap_schedule_updates', '' );
		$new_schedule = isset( $_POST['wpseopilot_sitemap_schedule_updates'] ) ? sanitize_text_field( $_POST['wpseopilot_sitemap_schedule_updates'] ) : '';
		update_option( 'wpseopilot_sitemap_schedule_updates', $new_schedule );

		if ( $old_schedule !== $new_schedule ) {
			// Clear old schedule
			wp_clear_scheduled_hook( 'wpseopilot_sitemap_cron' );

			// Set new schedule
			if ( ! empty( $new_schedule ) ) {
				wp_schedule_event( time(), $new_schedule, 'wpseopilot_sitemap_cron' );
			}
		}

		// Flush rewrite rules to ensure new sitemap routes work
		flush_rewrite_rules();
	}

	/**
	 * AJAX handler to regenerate sitemap.
	 *
	 * @return void
	 */
	public function ajax_regenerate_sitemap() {
		check_ajax_referer( 'wpseopilot_sitemap_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$this->regenerate_sitemap();

		wp_send_json_success( [ 'message' => __( 'Sitemap regenerated successfully!', 'wp-seo-pilot' ) ] );
	}

	/**
	 * Regenerate sitemap (clear cache).
	 *
	 * @return void
	 */
	public function regenerate_sitemap() {
		// Clear any sitemap cache if we implement caching
		do_action( 'wpseopilot_sitemap_regenerated' );

		// Flush rewrite rules to ensure sitemap URLs work
		flush_rewrite_rules();
	}

	/**
	 * Sanitize bool-ish values.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	public function sanitize_bool( $value ) {
		return ( ! empty( $value ) ) ? '1' : '0';
	}

	/**
	 * Sanitize array values.
	 *
	 * @param mixed $value Value.
	 *
	 * @return array
	 */
	public function sanitize_array( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_map( 'sanitize_text_field', $value );
	}

	/**
	 * Sanitize schedule value.
	 *
	 * @param mixed $value Value.
	 *
	 * @return string
	 */
	public function sanitize_schedule( $value ) {
		$allowed = [ '', 'hourly', 'twicedaily', 'daily', 'weekly' ];

		return in_array( $value, $allowed, true ) ? $value : '';
	}

	/**
	 * Sanitize additional pages.
	 *
	 * @param mixed $value Value.
	 *
	 * @return array
	 */
	public function sanitize_additional_pages( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $value as $page ) {
			if ( empty( $page['url'] ) ) {
				continue;
			}

			$sanitized[] = [
				'url'      => esc_url_raw( $page['url'] ),
				'priority' => floatval( $page['priority'] ?? 0.5 ),
			];
		}

		return $sanitized;
	}
}
