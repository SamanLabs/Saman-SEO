<?php
/**
 * Social Settings Admin UI
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
		// Social settings moved to Search Appearance page
		// Menu removed as of plugin reorganization
		return;
	}

	/**
	 * Render deprecation notice and redirect link.
	 *
	 * @return void
	 */
	public function render_redirect_notice() {
		\Saman\SEO\Admin_Topbar::render( 'social' );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Social Settings Moved', 'saman-seo' ); ?></h1>
			<div class="notice notice-info">
				<p><?php esc_html_e( 'Social Settings have been moved to Search Appearance for better organization.', 'saman-seo' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-types#social' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Go to Social Settings ÃƒÂ¢Ã¢â‚¬Â Ã¢â‚¬â„¢', 'saman-seo' ); ?>
					</a>
				</p>
			</div>
		</div>
		<?php
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Hook suffix.
	 */
	public function enqueue_assets( $hook ) {
		if ( 'wp-seo-pilot_page_saman-seo-social' !== $hook ) {
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

		wp_enqueue_script(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			SAMAN_SEO_VERSION,
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
		$social_defaults = get_option( 'SAMAN_SEO_social_defaults', [] );
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
		$post_type_social_defaults = get_option( 'SAMAN_SEO_post_type_social_defaults', [] );
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
		include SAMAN_SEO_PATH . 'templates/social-settings.php';
	}
}
