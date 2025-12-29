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
	<div class="wpseopilot-form-row">
		<label>
			<strong><?php esc_html_e( 'Robots Meta Settings', 'wp-seo-pilot' ); ?></strong>
			<span class="wpseopilot-label-hint"><?php esc_html_e( 'Control how search engines index this content type.', 'wp-seo-pilot' ); ?></span>
		</label>
		<div class="wpseopilot-flex">
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noarchive]"
					value="1"
					<?php checked( $settings['noarchive'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Archive', 'wp-seo-pilot' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nosnippet]"
					value="1"
					<?php checked( $settings['nosnippet'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Snippet', 'wp-seo-pilot' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nofollow]"
					value="1"
					<?php checked( $settings['nofollow'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Follow', 'wp-seo-pilot' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noimageindex]"
					value="1"
					<?php checked( $settings['noimageindex'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Image Index', 'wp-seo-pilot' ); ?></span>
			</label>
		</div>
	</div>

	<div class="wpseopilot-form-row">
		<label for="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>">
			<strong><?php esc_html_e( 'Breadcrumbs Title', 'wp-seo-pilot' ); ?></strong>
			<span class="wpseopilot-label-hint"><?php esc_html_e( 'Title to use in breadcrumbs (optional)', 'wp-seo-pilot' ); ?></span>
		</label>
		<input
			type="text"
			id="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>"
			name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][breadcrumb_title]"
			value="<?php echo esc_attr( $settings['breadcrumb_title'] ?? '' ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Default: Post Title', 'wp-seo-pilot' ); ?>"
		/>
	</div>

	<div class="wpseopilot-form-row">
		<label>
			<strong><?php esc_html_e( 'Canonical URL', 'wp-seo-pilot' ); ?></strong>
		</label>
		<p class="description">
			<?php esc_html_e( 'Canonical URLs are set per-post. You can configure a base rule here in future updates.', 'wp-seo-pilot' ); ?>
		</p>
	</div>
</div>
