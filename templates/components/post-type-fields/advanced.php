<?php
/**
 * Advanced Settings Sub-Tab Content
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
	<h4><?php esc_html_e( 'Advanced SEO Settings', 'saman-seo' ); ?></h4>
	<p class="description">
		<?php esc_html_e( 'Configure advanced robots meta directives, canonical URLs, and crawl settings.', 'saman-seo' ); ?>
	</p>
</div>

<div class="saman-seo-form-row">
	<div class="saman-seo-form-row">
		<label>
			<strong><?php esc_html_e( 'Robots Meta Settings', 'saman-seo' ); ?></strong>
			<span class="saman-seo-label-hint"><?php esc_html_e( 'Control how search engines index this content type.', 'saman-seo' ); ?></span>
		</label>
		<div class="saman-seo-flex">
			<label class="saman-seo-toggle">
				<input
					type="checkbox"
					name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noarchive]"
					value="1"
					<?php checked( $settings['noarchive'] ?? false, 1 ); ?>
				/>
				<span class="saman-seo-toggle-label"><?php esc_html_e( 'No Archive', 'saman-seo' ); ?></span>
			</label>
			<label class="saman-seo-toggle">
				<input
					type="checkbox"
					name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nosnippet]"
					value="1"
					<?php checked( $settings['nosnippet'] ?? false, 1 ); ?>
				/>
				<span class="saman-seo-toggle-label"><?php esc_html_e( 'No Snippet', 'saman-seo' ); ?></span>
			</label>
			<label class="saman-seo-toggle">
				<input
					type="checkbox"
					name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nofollow]"
					value="1"
					<?php checked( $settings['nofollow'] ?? false, 1 ); ?>
				/>
				<span class="saman-seo-toggle-label"><?php esc_html_e( 'No Follow', 'saman-seo' ); ?></span>
			</label>
			<label class="saman-seo-toggle">
				<input
					type="checkbox"
					name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noimageindex]"
					value="1"
					<?php checked( $settings['noimageindex'] ?? false, 1 ); ?>
				/>
				<span class="saman-seo-toggle-label"><?php esc_html_e( 'No Image Index', 'saman-seo' ); ?></span>
			</label>
		</div>
	</div>

	<div class="saman-seo-form-row">
		<label for="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>">
			<strong><?php esc_html_e( 'Breadcrumbs Title', 'saman-seo' ); ?></strong>
			<span class="saman-seo-label-hint"><?php esc_html_e( 'Title to use in breadcrumbs (optional)', 'saman-seo' ); ?></span>
		</label>
		<input
			type="text"
			id="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>"
			name="SAMAN_SEO_post_type_defaults[<?php echo esc_attr( $slug ); ?>][breadcrumb_title]"
			value="<?php echo esc_attr( $settings['breadcrumb_title'] ?? '' ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Default: Post Title', 'saman-seo' ); ?>"
		/>
	</div>

	<div class="saman-seo-form-row">
		<label>
			<strong><?php esc_html_e( 'Canonical URL', 'saman-seo' ); ?></strong>
		</label>
		<p class="description">
			<?php esc_html_e( 'Canonical URLs are set per-post. You can configure a base rule here in future updates.', 'saman-seo' ); ?>
		</p>
	</div>
</div>
