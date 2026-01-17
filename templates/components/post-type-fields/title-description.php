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

<div class="samanlabs-seo-form-row">
	<label>
		<strong><?php esc_html_e( 'Show in Search Results?', 'saman-labs-seo' ); ?></strong>
	</label>
	<label class="samanlabs-seo-toggle">
		<input
			type="checkbox"
			name="samanlabs_seo_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noindex]"
			value="1"
			<?php checked( $settings['noindex'] ?? false, 1 ); ?>
		/>
		<span class="samanlabs-seo-toggle-label">
			<?php esc_html_e( 'Hide from search engines (noindex)', 'saman-labs-seo' ); ?>
		</span>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, search engines will not index this content type in their results.', 'saman-labs-seo' ); ?>
	</p>
</div>

<div class="samanlabs-seo-form-row">
	<label for="title_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Title Template', 'saman-labs-seo' ); ?></strong>
		<span class="samanlabs-seo-label-hint">
			<?php esc_html_e( 'Use variables like {{post_title}}, {{site_title}}, {{separator}}', 'saman-labs-seo' ); ?>
		</span>
	</label>
	<div class="samanlabs-seo-flex-input">
		<input
			type="text"
			id="title_template_<?php echo esc_attr( $slug ); ?>"
			name="samanlabs_seo_post_type_defaults[<?php echo esc_attr( $slug ); ?>][title_template]"
			value="<?php echo esc_attr( $settings['title_template'] ?? '{{post_title}} {{separator}} {{site_title}}' ); ?>"
			class="regular-text"
			data-preview-field="title"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		/>
		<button
			type="button"
			class="button samanlabs-seo-trigger-vars"
			data-target="title_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
		</button>
	</div>
</div>

<div class="samanlabs-seo-form-row">
	<label for="desc_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Description Template', 'saman-labs-seo' ); ?></strong>
		<span class="samanlabs-seo-label-hint">
			<?php esc_html_e( 'Use variables like {{post_excerpt}}, {{post_date}}, {{category}}', 'saman-labs-seo' ); ?>
		</span>
	</label>
	<div class="samanlabs-seo-flex-input">
		<textarea
			id="desc_template_<?php echo esc_attr( $slug ); ?>"
			name="samanlabs_seo_post_type_defaults[<?php echo esc_attr( $slug ); ?>][description_template]"
			rows="2"
			class="large-text"
			data-preview-field="description"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		><?php echo esc_textarea( $settings['description_template'] ?? '{{post_excerpt}}' ); ?></textarea>
		<button
			type="button"
			class="button samanlabs-seo-trigger-vars"
			data-target="desc_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'saman-labs-seo' ); ?>
		</button>
	</div>
</div>
