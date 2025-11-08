<?php
/**
 * Handles plugin options and settings UI.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Settings controller.
 */
class Settings {

	/**
	 * Option keys with defaults.
	 *
	 * @var array<string,mixed>
	 */
	private $defaults = [
		'wpseopilot_default_title_template' => '%post_title% | %site_title%',
		'wpseopilot_default_meta_description' => '',
		'wpseopilot_default_og_image' => '',
		'wpseopilot_default_social_width' => 1200,
		'wpseopilot_default_social_height' => 630,
		'wpseopilot_default_noindex' => '0',
		'wpseopilot_default_nofollow' => '0',
		'wpseopilot_global_robots' => 'index, follow',
		'wpseopilot_hreflang_map' => '',
		'wpseopilot_robots_txt' => '',
		'wpseopilot_enable_sitemap_enhancer' => '0',
		'wpseopilot_enable_redirect_manager' => '1',
		'wpseopilot_enable_404_logging' => '0',
	];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
	}

	/**
	 * Fetch option with fallback.
	 *
	 * @param string $key Key.
	 *
	 * @return mixed
	 */
	public function get( $key ) {
		$default = $this->defaults[ $key ] ?? '';

		return get_option( $key, $default );
	}

	/**
	 * Register settings + fields.
	 *
	 * @return void
	 */
	public function register_settings() {
		foreach ( $this->defaults as $key => $default ) {
			add_option( $key, $default );
		}

		register_setting( 'wpseopilot', 'wpseopilot_default_title_template', [ $this, 'sanitize_template' ] );
		register_setting( 'wpseopilot', 'wpseopilot_default_meta_description', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot', 'wpseopilot_default_og_image', 'esc_url_raw' );
		register_setting( 'wpseopilot', 'wpseopilot_default_social_width', 'absint' );
		register_setting( 'wpseopilot', 'wpseopilot_default_social_height', 'absint' );
		register_setting( 'wpseopilot', 'wpseopilot_default_noindex', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_default_nofollow', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_global_robots', 'sanitize_text_field' );
		register_setting( 'wpseopilot', 'wpseopilot_hreflang_map', [ $this, 'sanitize_json' ] );
		register_setting( 'wpseopilot', 'wpseopilot_robots_txt', 'sanitize_textarea_field' );
		register_setting( 'wpseopilot', 'wpseopilot_enable_sitemap_enhancer', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_redirect_manager', [ $this, 'sanitize_bool' ] );
		register_setting( 'wpseopilot', 'wpseopilot_enable_404_logging', [ $this, 'sanitize_bool' ] );
	}

	/**
	 * Add settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_menu_page(
			__( 'WP SEO Pilot', 'wp-seo-pilot' ),
			__( 'WP SEO Pilot', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot',
			[ $this, 'render_settings_page' ],
			'dashicons-airplane',
			58
		);

		add_submenu_page(
			'wpseopilot',
			__( 'SEO Defaults', 'wp-seo-pilot' ),
			__( 'SEO Defaults', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot',
			[ $this, 'render_settings_page' ]
		);
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
	 * Ensure template placeholders stay safe.
	 *
	 * @param string $value Template.
	 *
	 * @return string
	 */
	public function sanitize_template( $value ) {
		$value = sanitize_text_field( $value );

		$allowed = [
			'%post_title%',
			'%site_title%',
			'%tagline%',
			'%post_author%',
		];

		return str_replace( $allowed, $allowed, $value );
	}

	/**
	 * Sanitize JSON stored as text.
	 *
	 * @param string $value JSON.
	 *
	 * @return string
	 */
	public function sanitize_json( $value ) {
		$decoded = json_decode( wp_unslash( $value ), true );

		if ( null === $decoded || ! is_array( $decoded ) ) {
			return '';
		}

		return wp_json_encode( array_map( 'esc_url_raw', $decoded ) );
	}

	/**
	 * Render settings markup.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);

		wp_localize_script(
			'wpseopilot-admin',
			'WPSEOPilotAdmin',
			[
				'mediaTitle'  => __( 'Select default image', 'wp-seo-pilot' ),
				'mediaButton' => __( 'Use image', 'wp-seo-pilot' ),
			]
		);

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		include WPSEOPILOT_PATH . 'templates/settings-page.php';
	}
}
