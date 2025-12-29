<?php
/**
 * Global Admin Top Bar
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the global plugin top bar.
 */
class Admin_Topbar {

	/**
	 * Render the top bar.
	 *
	 * @param string $active_page Active page slug.
	 * @param string $section_label Optional section label.
	 * @param array  $actions Optional actions to show in the top bar.
	 */
	public static function render( $active_page = '', $section_label = '', $actions = [] ) {
		$nav_items = self::get_nav_items();
		?>
		<div class="wpseopilot-topbar">
			<div class="wpseopilot-topbar-inner">
				<div class="wpseopilot-topbar-left">
					<div class="wpseopilot-branding">
						<span class="dashicons dashicons-airplane"></span>
						<h1><?php esc_html_e( 'WP SEO Pilot', 'wp-seo-pilot' ); ?></h1>
					</div>

					<?php if ( ! empty( $section_label ) ) : ?>
						<span class="wpseopilot-section-label"><?php echo esc_html( $section_label ); ?></span>
					<?php endif; ?>

					<nav class="wpseopilot-nav">
						<ul>
							<?php foreach ( $nav_items as $slug => $item ) : ?>
								<li>
									<a href="<?php echo esc_url( $item['url'] ); ?>"
									   class="<?php echo $active_page === $slug ? 'active' : ''; ?>">
										<?php echo esc_html( $item['label'] ); ?>
									</a>
								</li>
							<?php endforeach; ?>
						</ul>
					</nav>
				</div>

				<?php if ( ! empty( $actions ) ) : ?>
					<div class="wpseopilot-topbar-actions">
						<?php foreach ( $actions as $action ) : ?>
							<?php if ( isset( $action['type'] ) && $action['type'] === 'button' ) : ?>
								<a href="<?php echo esc_url( $action['url'] ?? '#' ); ?>"
								   class="button <?php echo esc_attr( $action['class'] ?? 'button-primary' ); ?>"
								   <?php if ( ! empty( $action['target'] ) ) : ?>target="<?php echo esc_attr( $action['target'] ); ?>"<?php endif; ?>>
									<?php echo esc_html( $action['label'] ); ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Get navigation items.
	 *
	 * @return array
	 */
	private static function get_nav_items() {
		return [
			'defaults'   => [
				'label' => __( 'Defaults', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot' ),
			],
			'types'      => [
				'label' => __( 'Search Appearance', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-types' ),
			],
			'social'     => [
				'label' => __( 'Social', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-social' ),
			],
			'sitemap'    => [
				'label' => __( 'Sitemap', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-sitemap' ),
			],
			'redirects'  => [
				'label' => __( 'Redirects', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-redirects' ),
			],
			'audit'      => [
				'label' => __( 'Audit', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-audit' ),
			],
			'404-log'    => [
				'label' => __( '404 Log', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-404' ),
			],
			'internal-linking' => [
				'label' => __( 'Internal Links', 'wp-seo-pilot' ),
				'url'   => admin_url( 'admin.php?page=wpseopilot-links' ),
			],
		];
	}
}
