<?php
/**
 * Title & Description Sub-Tab Content
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
	<label>
		<strong><?php esc_html_e( 'Show in Search Results?', 'wp-seo-pilot' ); ?></strong>
	</label>
	<label class="wpseopilot-toggle">
		<input
			type="checkbox"
			name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noindex]"
			value="1"
			<?php checked( $settings['noindex'] ?? false, 1 ); ?>
		/>
		<span class="wpseopilot-toggle-label">
			<?php esc_html_e( 'Hide from search engines (noindex)', 'wp-seo-pilot' ); ?>
		</span>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, search engines will not index this content type in their results.', 'wp-seo-pilot' ); ?>
	</p>
</div>

<div class="wpseopilot-form-row">
	<label for="title_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Title Template', 'wp-seo-pilot' ); ?></strong>
		<span class="wpseopilot-label-hint">
			<?php esc_html_e( 'Use variables like {{post_title}}, {{site_title}}, {{separator}}', 'wp-seo-pilot' ); ?>
		</span>
	</label>
	<div class="wpseopilot-flex-input">
		<input
			type="text"
			id="title_template_<?php echo esc_attr( $slug ); ?>"
			name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][title_template]"
			value="<?php echo esc_attr( $settings['title_template'] ?? '{{post_title}} {{separator}} {{site_title}}' ); ?>"
			class="regular-text"
			data-preview-field="title"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		/>
		<button
			type="button"
			class="button wpseopilot-trigger-vars"
			data-target="title_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
		</button>
	</div>
</div>

<div class="wpseopilot-form-row">
	<label for="desc_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Description Template', 'wp-seo-pilot' ); ?></strong>
		<span class="wpseopilot-label-hint">
			<?php esc_html_e( 'Use variables like {{post_excerpt}}, {{post_date}}, {{category}}', 'wp-seo-pilot' ); ?>
		</span>
	</label>
	<div class="wpseopilot-flex-input">
		<textarea
			id="desc_template_<?php echo esc_attr( $slug ); ?>"
			name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][description_template]"
			rows="2"
			class="large-text"
			data-preview-field="description"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		><?php echo esc_textarea( $settings['description_template'] ?? '{{post_excerpt}}' ); ?></textarea>
		<button
			type="button"
			class="button wpseopilot-trigger-vars"
			data-target="desc_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'wp-seo-pilot' ); ?>
		</button>
	</div>
</div>
