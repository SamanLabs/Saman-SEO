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
	<h4><?php esc_html_e( 'Advanced SEO Settings', 'saman-labs-seo' ); ?></h4>
	<p class="description">
		<?php esc_html_e( 'Configure advanced robots meta directives, canonical URLs, and crawl settings.', 'saman-labs-seo' ); ?>
	</p>
</div>

<div class="wpseopilot-form-row">
	<div class="wpseopilot-form-row">
		<label>
			<strong><?php esc_html_e( 'Robots Meta Settings', 'saman-labs-seo' ); ?></strong>
			<span class="wpseopilot-label-hint"><?php esc_html_e( 'Control how search engines index this content type.', 'saman-labs-seo' ); ?></span>
		</label>
		<div class="wpseopilot-flex">
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noarchive]"
					value="1"
					<?php checked( $settings['noarchive'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Archive', 'saman-labs-seo' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nosnippet]"
					value="1"
					<?php checked( $settings['nosnippet'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Snippet', 'saman-labs-seo' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][nofollow]"
					value="1"
					<?php checked( $settings['nofollow'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Follow', 'saman-labs-seo' ); ?></span>
			</label>
			<label class="wpseopilot-toggle">
				<input
					type="checkbox"
					name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][noimageindex]"
					value="1"
					<?php checked( $settings['noimageindex'] ?? false, 1 ); ?>
				/>
				<span class="wpseopilot-toggle-label"><?php esc_html_e( 'No Image Index', 'saman-labs-seo' ); ?></span>
			</label>
		</div>
	</div>

	<div class="wpseopilot-form-row">
		<label for="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>">
			<strong><?php esc_html_e( 'Breadcrumbs Title', 'saman-labs-seo' ); ?></strong>
			<span class="wpseopilot-label-hint"><?php esc_html_e( 'Title to use in breadcrumbs (optional)', 'saman-labs-seo' ); ?></span>
		</label>
		<input
			type="text"
			id="breadcrumbs-title-<?php echo esc_attr( $slug ); ?>"
			name="wpseopilot_post_type_defaults[<?php echo esc_attr( $slug ); ?>][breadcrumb_title]"
			value="<?php echo esc_attr( $settings['breadcrumb_title'] ?? '' ); ?>"
			class="regular-text"
			placeholder="<?php esc_attr_e( 'Default: Post Title', 'saman-labs-seo' ); ?>"
		/>
	</div>

	<div class="wpseopilot-form-row">
		<label>
			<strong><?php esc_html_e( 'Canonical URL', 'saman-labs-seo' ); ?></strong>
		</label>
		<p class="description">
			<?php esc_html_e( 'Canonical URLs are set per-post. You can configure a base rule here in future updates.', 'saman-labs-seo' ); ?>
		</p>
	</div>
</div>
