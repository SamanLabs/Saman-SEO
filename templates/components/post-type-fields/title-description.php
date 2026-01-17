<?php
/**
 * Title & Description Sub-Tab Content
 *
 * @package Saman\SEO
 *
 * Variables expected:
 * - $slug (string): Post type slug
 * - $settings (array): Post type settings
 */

defined( 'ABSPATH' ) || exit;
?>

<div class="saman-seo-form-row">
	<label>
		<strong><?php esc_html_e( 'Show in Search Results?', 'saman-seo' ); ?></strong>
	</label>
	<label class="saman-seo-toggle">
		<input
			type="checkbox"
			name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noindex]"
			value="1"
			<?php checked( $settings['noindex'] ?? false, 1 ); ?>
		/>
		<span class="saman-seo-toggle-label">
			<?php esc_html_e( 'Hide from search engines (noindex)', 'saman-seo' ); ?>
		</span>
	</label>
	<p class="description">
		<?php esc_html_e( 'When enabled, search engines will not index this content type in their results.', 'saman-seo' ); ?>
	</p>
</div>

<div class="saman-seo-form-row">
	<label for="title_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Title Template', 'saman-seo' ); ?></strong>
		<span class="saman-seo-label-hint">
			<?php esc_html_e( 'Use variables like {{post_title}}, {{site_title}}, {{separator}}', 'saman-seo' ); ?>
		</span>
	</label>
	<div class="saman-seo-flex-input">
		<input
			type="text"
			id="title_template_<?php echo esc_attr( $slug ); ?>"
			name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][title_template]"
			value="<?php echo esc_attr( $settings['title_template'] ?? '{{post_title}} {{separator}} {{site_title}}' ); ?>"
			class="regular-text"
			data-preview-field="title"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		/>
		<button
			type="button"
			class="button saman-seo-trigger-vars"
			data-target="title_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
		</button>
	</div>
</div>

<div class="saman-seo-form-row">
	<label for="desc_template_<?php echo esc_attr( $slug ); ?>">
		<strong><?php esc_html_e( 'Description Template', 'saman-seo' ); ?></strong>
		<span class="saman-seo-label-hint">
			<?php esc_html_e( 'Use variables like {{post_excerpt}}, {{post_date}}, {{category}}', 'saman-seo' ); ?>
		</span>
	</label>
	<div class="saman-seo-flex-input">
		<textarea
			id="desc_template_<?php echo esc_attr( $slug ); ?>"
			name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][description_template]"
			rows="2"
			class="large-text"
			data-preview-field="description"
			data-context="post_type:<?php echo esc_attr( $slug ); ?>"
		><?php echo esc_textarea( $settings['description_template'] ?? '{{post_excerpt}}' ); ?></textarea>
		<button
			type="button"
			class="button saman-seo-trigger-vars"
			data-target="desc_template_<?php echo esc_attr( $slug ); ?>"
		>
			<span class="dashicons dashicons-editor-code"></span>
			<?php esc_html_e( 'Variables', 'saman-seo' ); ?>
		</button>
	</div>
</div>
