<?php
/**
 * Sitemap Settings Admin UI
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
		'SAMAN_SEO_sitemap_enabled'                => '1',
		'SAMAN_SEO_sitemap_max_urls'              => 1000,
		'SAMAN_SEO_sitemap_enable_index'          => '1',
		'SAMAN_SEO_sitemap_dynamic_generation'    => '1',
		'SAMAN_SEO_sitemap_schedule_updates'      => '',
		'SAMAN_SEO_sitemap_post_types'            => [],
		'SAMAN_SEO_sitemap_taxonomies'            => [],
		'SAMAN_SEO_sitemap_include_author_pages'  => '0',
		'SAMAN_SEO_sitemap_include_date_archives' => '0',
		'SAMAN_SEO_sitemap_exclude_images'        => '0',
		'SAMAN_SEO_sitemap_enable_rss'            => '0',
		'SAMAN_SEO_sitemap_enable_google_news'    => '0',
		'SAMAN_SEO_sitemap_google_news_name'      => '',
		'SAMAN_SEO_sitemap_google_news_post_types' => [],
		'SAMAN_SEO_sitemap_additional_pages'      => [],
		'SAMAN_SEO_enable_llm_txt'                => '1',
		'SAMAN_SEO_llm_txt_posts_per_type'        => 50,
		'SAMAN_SEO_llm_txt_title'                 => '',
		'SAMAN_SEO_llm_txt_description'           => '',
		'SAMAN_SEO_llm_txt_include_excerpt'       => '1',
	];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'wp_ajax_SAMAN_SEO_regenerate_sitemap', [ $this, 'ajax_regenerate_sitemap' ] );

		// Schedule sitemap regeneration if enabled
		if ( get_option( 'SAMAN_SEO_sitemap_schedule_updates', '' ) ) {
			add_action( 'SAMAN_SEO_sitemap_cron', [ $this, 'regenerate_sitemap' ] );
		}
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

		$group = 'SAMAN_SEO_sitemap';

		register_setting( $group, 'SAMAN_SEO_sitemap_enabled', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_max_urls', 'absint' );
		register_setting( $group, 'SAMAN_SEO_sitemap_enable_index', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_dynamic_generation', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_schedule_updates', [ $this, 'sanitize_schedule' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_post_types', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_taxonomies', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_include_author_pages', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_include_date_archives', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_exclude_images', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_enable_rss', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_enable_google_news', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_google_news_name', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_sitemap_google_news_post_types', [ $this, 'sanitize_array' ] );
		register_setting( $group, 'SAMAN_SEO_sitemap_additional_pages', [ $this, 'sanitize_additional_pages' ] );

		// LLM.txt settings
		register_setting( $group, 'SAMAN_SEO_enable_llm_txt', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_llm_txt_posts_per_type', 'absint' );
		register_setting( $group, 'SAMAN_SEO_llm_txt_title', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_llm_txt_description', 'sanitize_textarea_field' );
		register_setting( $group, 'SAMAN_SEO_llm_txt_include_excerpt', [ $this, 'sanitize_bool' ] );
	}

	/**
	 * Render sitemap settings page.
	 *
	 * @return void
	 */
	/**
	 * Save settings.
	 *
	 * @return void
	 */
	private function save_settings() {
		// Save all settings manually
		update_option( 'SAMAN_SEO_sitemap_enabled', isset( $_POST['SAMAN_SEO_sitemap_enabled'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_max_urls', isset( $_POST['SAMAN_SEO_sitemap_max_urls'] ) ? absint( $_POST['SAMAN_SEO_sitemap_max_urls'] ) : 1000 );
		update_option( 'SAMAN_SEO_sitemap_enable_index', isset( $_POST['SAMAN_SEO_sitemap_enable_index'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_dynamic_generation', isset( $_POST['SAMAN_SEO_sitemap_dynamic_generation'] ) ? '1' : '0' );

		// Handle post types
		$post_types = isset( $_POST['SAMAN_SEO_sitemap_post_types'] ) && is_array( $_POST['SAMAN_SEO_sitemap_post_types'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['SAMAN_SEO_sitemap_post_types'] ) )
			: [];
		update_option( 'SAMAN_SEO_sitemap_post_types', $post_types );

		// Handle taxonomies
		$taxonomies = isset( $_POST['SAMAN_SEO_sitemap_taxonomies'] ) && is_array( $_POST['SAMAN_SEO_sitemap_taxonomies'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['SAMAN_SEO_sitemap_taxonomies'] ) )
			: [];
		update_option( 'SAMAN_SEO_sitemap_taxonomies', $taxonomies );

		update_option( 'SAMAN_SEO_sitemap_include_author_pages', isset( $_POST['SAMAN_SEO_sitemap_include_author_pages'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_include_date_archives', isset( $_POST['SAMAN_SEO_sitemap_include_date_archives'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_exclude_images', isset( $_POST['SAMAN_SEO_sitemap_exclude_images'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_enable_rss', isset( $_POST['SAMAN_SEO_sitemap_enable_rss'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_enable_google_news', isset( $_POST['SAMAN_SEO_sitemap_enable_google_news'] ) ? '1' : '0' );
		update_option( 'SAMAN_SEO_sitemap_google_news_name', isset( $_POST['SAMAN_SEO_sitemap_google_news_name'] ) ? sanitize_text_field( wp_unslash( $_POST['SAMAN_SEO_sitemap_google_news_name'] ) ) : get_bloginfo( 'name' ) );

		// Handle Google News post types
		$google_news_post_types = isset( $_POST['SAMAN_SEO_sitemap_google_news_post_types'] ) && is_array( $_POST['SAMAN_SEO_sitemap_google_news_post_types'] )
			? array_map( 'sanitize_text_field', wp_unslash( $_POST['SAMAN_SEO_sitemap_google_news_post_types'] ) )
			: [];
		update_option( 'SAMAN_SEO_sitemap_google_news_post_types', $google_news_post_types );

		// Handle additional pages
		$additional_pages = [];
		if ( isset( $_POST['SAMAN_SEO_sitemap_additional_pages'] ) && is_array( $_POST['SAMAN_SEO_sitemap_additional_pages'] ) ) {
			foreach ( wp_unslash( $_POST['SAMAN_SEO_sitemap_additional_pages'] ) as $page ) {
				if ( ! empty( $page['url'] ) ) {
					$additional_pages[] = [
						'url'      => esc_url_raw( $page['url'] ),
						'priority' => isset( $page['priority'] ) ? floatval( $page['priority'] ) : 0.5,
					];
				}
			}
		}
		update_option( 'SAMAN_SEO_sitemap_additional_pages', $additional_pages );

		// Schedule updates
		$old_schedule = get_option( 'SAMAN_SEO_sitemap_schedule_updates', '' );
		$new_schedule = isset( $_POST['SAMAN_SEO_sitemap_schedule_updates'] ) ? sanitize_text_field( wp_unslash( $_POST['SAMAN_SEO_sitemap_schedule_updates'] ) ) : '';
		update_option( 'SAMAN_SEO_sitemap_schedule_updates', $new_schedule );

		if ( $old_schedule !== $new_schedule ) {
			// Clear old schedule
			wp_clear_scheduled_hook( 'SAMAN_SEO_sitemap_cron' );

			// Set new schedule
			if ( ! empty( $new_schedule ) ) {
				wp_schedule_event( time(), $new_schedule, 'SAMAN_SEO_sitemap_cron' );
			}
		}

		// Flush rewrite rules to ensure new sitemap routes work
		flush_rewrite_rules();
	}

	/**
	 * Save LLM.txt settings.
	 *
	 * @return void
	 */
	private function save_llm_txt_settings() {
		update_option( 'SAMAN_SEO_enable_llm_txt', isset( $_POST['SAMAN_SEO_enable_llm_txt'] ) ? '1' : '0' );

		$posts_per_type = isset( $_POST['SAMAN_SEO_llm_txt_posts_per_type'] ) ? absint( $_POST['SAMAN_SEO_llm_txt_posts_per_type'] ) : 50;
		$posts_per_type = max( 1, min( 500, $posts_per_type ) );
		update_option( 'SAMAN_SEO_llm_txt_posts_per_type', $posts_per_type );

		update_option( 'SAMAN_SEO_llm_txt_title', isset( $_POST['SAMAN_SEO_llm_txt_title'] ) ? sanitize_text_field( wp_unslash( $_POST['SAMAN_SEO_llm_txt_title'] ) ) : '' );
		update_option( 'SAMAN_SEO_llm_txt_description', isset( $_POST['SAMAN_SEO_llm_txt_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['SAMAN_SEO_llm_txt_description'] ) ) : '' );
		update_option( 'SAMAN_SEO_llm_txt_include_excerpt', isset( $_POST['SAMAN_SEO_llm_txt_include_excerpt'] ) ? '1' : '0' );
	}

	/**
	 * AJAX handler to regenerate sitemap.
	 *
	 * @return void
	 */
	public function ajax_regenerate_sitemap() {
		check_ajax_referer( 'SAMAN_SEO_sitemap_action', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$this->regenerate_sitemap();

		wp_send_json_success( [ 'message' => __( 'Sitemap regenerated successfully!', 'saman-seo' ) ] );
	}

	/**
	 * Regenerate sitemap (clear cache).
	 *
	 * @return void
	 */
	public function regenerate_sitemap() {
		// Clear any sitemap cache if we implement caching
		do_action( 'SAMAN_SEO_sitemap_regenerated' );

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
