<?php
/**
 * Onboarding experience after activation.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Onboarding controller.
 */
class Onboarding {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_menu', [ $this, 'register_page' ] );
		add_action( 'admin_post_wpseopilot_onboarding', [ $this, 'handle_submission' ] );
	}

	/**
	 * Register onboarding sub page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'wpseopilot',
			__( 'Welcome to WP SEO Pilot', 'wp-seo-pilot' ),
			__( 'Onboarding', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-onboarding',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render onboarding form.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		include WPSEOPILOT_PATH . 'templates/onboarding.php';
	}

	/**
	 * Persist onboarding defaults.
	 *
	 * @return void
	 */
	public function handle_submission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_onboarding' );

		update_option( 'wpseopilot_default_meta_description', sanitize_textarea_field( wp_unslash( $_POST['default_meta_description'] ?? '' ) ) );
		update_option( 'wpseopilot_default_og_image', esc_url_raw( wp_unslash( $_POST['default_og_image'] ?? '' ) ) );
		update_option( 'wpseopilot_show_tour', empty( $_POST['tour_completed'] ) ? '0' : '1' );
		update_option( 'wpseopilot_show_onboarding', '0' );

		wp_redirect( admin_url( 'admin.php?page=wpseopilot' ) );
		exit;
	}
}
