<?php
/**
 * Advanced Settings Sub-Tab Content
 *
 * @package WPSEOPilot
 *
 * Variables expected:
 * - $slug (string): Post type slug
 * - $settings (array): Post type settings
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="wpseopilot-form-row">
	<h4><?php esc_html_e( 'Advanced SEO Settings', 'wp-seo-pilot' ); ?></h4>
	<p class="description">
		<?php esc_html_e( 'Configure advanced robots meta directives, canonical URLs, and crawl settings.', 'wp-seo-pilot' ); ?>
	</p>
</div>

<div class="wpseopilot-form-row">
	<div class="wpseopilot-placeholder-content">
		<span class="dashicons dashicons-admin-tools" style="font-size: 48px; opacity: 0.3;"></span>
		<p>
			<?php esc_html_e( 'Advanced settings like robots meta tags, canonical URLs, and crawl directives will be available in a future update.', 'wp-seo-pilot' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'This feature will allow you to configure:', 'wp-seo-pilot' ); ?>
		</p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Robots meta directives (nofollow, noarchive, noimageindex)', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Canonical URL settings', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Crawl delay and frequency hints', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'XML Sitemap inclusion settings', 'wp-seo-pilot' ); ?></li>
		</ul>
	</div>
</div>
