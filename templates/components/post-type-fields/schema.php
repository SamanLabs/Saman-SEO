<?php
/**
 * Schema Markup Sub-Tab Content
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
	<label for="schema_type_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Schema Type', 'wp-seo-pilot' ); ?></strong>
		<span class="wpseopilot-label-hint">
			<?php esc_html_e( 'Select the schema.org type for this content', 'wp-seo-pilot' ); ?>
		</span>
	</label>
	<select
		id="schema_type_<?php echo esc_attr( $slug ); ?>"
		name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][schema_type]"
		class="regular-text"
	>
		<option value="">
			<?php esc_html_e( 'None', 'wp-seo-pilot' ); ?>
		</option>
		<option value="Article" <?php selected( $settings['schema_type'] ?? '', 'Article' ); ?>>
			<?php esc_html_e( 'Article', 'wp-seo-pilot' ); ?>
		</option>
		<option value="BlogPosting" <?php selected( $settings['schema_type'] ?? '', 'BlogPosting' ); ?>>
			<?php esc_html_e( 'Blog Posting', 'wp-seo-pilot' ); ?>
		</option>
		<option value="NewsArticle" <?php selected( $settings['schema_type'] ?? '', 'NewsArticle' ); ?>>
			<?php esc_html_e( 'News Article', 'wp-seo-pilot' ); ?>
		</option>
		<option value="WebPage" <?php selected( $settings['schema_type'] ?? '', 'WebPage' ); ?>>
			<?php esc_html_e( 'Web Page', 'wp-seo-pilot' ); ?>
		</option>
		<option value="Product" <?php selected( $settings['schema_type'] ?? '', 'Product' ); ?>>
			<?php esc_html_e( 'Product', 'wp-seo-pilot' ); ?>
		</option>
	</select>
	<p class="description">
		<?php esc_html_e( 'Schema markup helps search engines better understand your content type.', 'wp-seo-pilot' ); ?>
	</p>
</div>

<div class="wpseopilot-form-row">
	<p class="wpseopilot-info-notice">
		<span class="dashicons dashicons-info"></span>
		<?php esc_html_e( 'Additional schema configuration options will be available in future updates.', 'wp-seo-pilot' ); ?>
	</p>
</div>
