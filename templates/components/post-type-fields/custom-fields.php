<?php
/**
 * Custom Fields Sub-Tab Content
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
	<h4><?php esc_html_e( 'Custom Field Mapping', 'wp-seo-pilot' ); ?></h4>
	<p class="description">
		<?php esc_html_e( 'Map custom fields to SEO variables for use in title and description templates.', 'wp-seo-pilot' ); ?>
	</p>
</div>

<div class="wpseopilot-form-row">
	<div class="wpseopilot-placeholder-content">
		<span class="dashicons dashicons-admin-generic" style="font-size: 48px; opacity: 0.3;"></span>
		<p>
			<?php esc_html_e( 'Custom field mapping and SEO analysis configuration will be available in a future update.', 'wp-seo-pilot' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'This feature will allow you to:', 'wp-seo-pilot' ); ?>
		</p>
		<ul style="list-style: disc; margin-left: 20px;">
			<li><?php esc_html_e( 'Map custom fields to template variables', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Configure which fields to analyze for SEO', 'wp-seo-pilot' ); ?></li>
			<li><?php esc_html_e( 'Set custom field priorities for content analysis', 'wp-seo-pilot' ); ?></li>
		</ul>
	</div>
</div>
