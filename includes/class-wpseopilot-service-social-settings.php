<?php
/**
 * Social Settings Admin UI
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Social Settings controller.
 */
class Social_Settings {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_menu', [ $this, 'register_menu' ], 20 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
	}

	/**
	 * Register settings menu.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'wpseopilot',
			__( 'Social Settings', 'wp-seo-pilot' ),
			__( 'Social', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-social',
			[ $this, 'render_settings_page' ]
		);
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'wp-seo-pilot_page_wpseopilot-social' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);
	}

	/**
	 * Render social settings page.
	 *
	 * @return void
	 */
	public function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Get social defaults
		$social_defaults = get_option( 'wpseopilot_social_defaults', [] );
		if ( ! is_array( $social_defaults ) ) {
			$social_defaults = [];
		}

		$social_field_defaults = [
			'og_title'            => '',
			'og_description'      => '',
			'twitter_title'       => '',
			'twitter_description' => '',
			'image_source'        => '',
			'schema_itemtype'     => '',
		];

		$social_defaults = wp_parse_args( $social_defaults, $social_field_defaults );

		// Get post type social defaults
		$post_type_social_defaults = get_option( 'wpseopilot_post_type_social_defaults', [] );
		if ( ! is_array( $post_type_social_defaults ) ) {
			$post_type_social_defaults = [];
		}

		// Get post types
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		// Load template
		include WPSEOPILOT_PATH . 'templates/social-settings.php';
	}
}
