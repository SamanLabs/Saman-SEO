<?php
/**
 * Global Admin Top Bar
 *
 * @package Saman\SEO
 */

namespace Saman\SEO;

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
		<div class="saman-seo-topbar">
			<div class="saman-seo-topbar-inner">
				<div class="saman-seo-topbar-left">
					<div class="saman-seo-branding">
						<span class="dashicons dashicons-airplane"></span>
						<h1><?php esc_html_e( 'Saman SEO', 'saman-seo' ); ?></h1>
					</div>

					<?php if ( ! empty( $section_label ) ) : ?>
						<span class="saman-seo-section-label"><?php echo esc_html( $section_label ); ?></span>
					<?php endif; ?>

					<nav class="saman-seo-nav">
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

				<div class="saman-seo-topbar-actions">
					<?php if ( ! empty( $actions ) ) : ?>
						<?php foreach ( $actions as $action ) : ?>
							<?php if ( isset( $action['type'] ) && $action['type'] === 'button' ) : ?>
								<a href="<?php echo esc_url( $action['url'] ?? '#' ); ?>"
								   class="button <?php echo esc_attr( $action['class'] ?? 'button-primary' ); ?>"
								   <?php if ( ! empty( $action['target'] ) ) : ?>target="<?php echo esc_attr( $action['target'] ); ?>"<?php endif; ?>>
									<?php echo esc_html( $action['label'] ); ?>
								</a>
							<?php endif; ?>
						<?php endforeach; ?>
					<?php endif; ?>

					<!-- GitHub Link -->
					<a href="https://github.com/jhd3197/WP-SEO-Pilot"
					   class="saman-seo-github-link"
					   target="_blank"
					   rel="noopener noreferrer"
					   title="<?php esc_attr_e( 'Star on GitHub', 'saman-seo' ); ?>">
						<svg width="20" height="20" viewBox="0 0 16 16" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
							<path fill-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/>
						</svg>
					</a>
				</div>
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
		$items = [
			'defaults'   => [
				'label' => __( 'Defaults', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo' ),
			],
			'types'      => [
				'label' => __( 'Search Appearance', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-types' ),
			],
			'ai'         => [
				'label' => __( 'AI', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-ai' ),
			],
			'internal-linking' => [
				'label' => __( 'Internal Links', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-links' ),
			],
			'redirects'  => [
				'label' => __( 'Redirects', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-redirects' ),
			],
			'audit'      => [
				'label' => __( 'Audit', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-audit' ),
			],
			'404-log'    => [
				'label' => __( '404 Log', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-404' ),
			],
			'sitemap'    => [
				'label' => __( 'Sitemap', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-sitemap' ),
			],
		];

		// Only show Local SEO if module is enabled.
		if ( '1' === get_option( 'SAMAN_SEO_enable_local_seo', '0' ) ) {
			$items['local-seo'] = [
				'label' => __( 'Local SEO', 'saman-seo' ),
				'url'   => admin_url( 'admin.php?page=saman-seo-local-seo' ),
			];
		}

		return $items;
	}
}
