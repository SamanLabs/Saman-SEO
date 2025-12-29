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
	<div class="wpseopilot-custom-fields-list">
		<?php
		// $this refers to Settings class instance
		$all_vars = $this->get_context_variables();
		$context_key = 'post_type:' . $slug;
		$custom_fields = $all_vars[ $context_key ]['vars'] ?? [];

		if ( ! empty( $custom_fields ) ) :
			?>
			<div class="wpseopilot-tag-cloud-container">
				<p class="description" style="margin-bottom: 12px;">
					<?php esc_html_e( 'Click to copy these custom field variables found in your latest post:', 'wp-seo-pilot' ); ?>
				</p>
				<div class="wpseopilot-tag-cloud">
					<?php foreach ( $custom_fields as $field ) : ?>
						<button type="button" class="wpseopilot-tag-chip wpseopilot-copy-var" data-var="<?php echo esc_attr( $field['tag'] ); ?>" title="<?php echo esc_attr( $field['desc'] ); ?> | Preview: <?php echo esc_attr( $field['preview'] ); ?>">
							<code>{{<?php echo esc_html( $field['tag'] ); ?>}}</code>
						</button>
					<?php endforeach; ?>
				</div>
			</div>
			<?php
		else :
			?>
			<div class="wpseopilot-placeholder-content">
				<span class="dashicons dashicons-list-view" style="font-size: 48px; opacity: 0.3;"></span>
				<p>
					<?php esc_html_e( 'No custom fields detected for this post type yet.', 'wp-seo-pilot' ); ?>
				</p>
				<p class="description">
					<?php esc_html_e( 'Create a post with custom fields to see them listed here.', 'wp-seo-pilot' ); ?>
				</p>
			</div>
			<?php
		endif;
		?>
	</div>
</div>
