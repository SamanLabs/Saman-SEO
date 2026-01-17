<?php
/**
 * Internal Linking Ã¢â‚¬â€ Module settings.
 *
 * @package Saman\SEO
 */

$form_action        = admin_url( 'admin-post.php' );
$heading_behavior   = $settings['default_heading_behavior'] ?? 'none';
$heading_levels     = $settings['default_heading_levels'] ?? [];

?>
<div class="saman-seo-card">
	<h3><?php esc_html_e( 'Module settings', 'saman-seo' ); ?></h3>
	<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="saman-seo-links__settings-form">
		<?php wp_nonce_field( 'SAMAN_SEO_save_link_settings' ); ?>
		<input type="hidden" name="action" value="SAMAN_SEO_save_link_settings" />

		<section>
			<h4><?php esc_html_e( 'Global Defaults', 'saman-seo' ); ?></h4>
			<div class="saman-seo-grid">
				<label>
					<span><?php esc_html_e( 'Default max links per page', 'saman-seo' ); ?></span>
					<input type="number" min="0" max="50" name="settings[default_max_links_per_page]" value="<?php echo esc_attr( $settings['default_max_links_per_page'] ?? 0 ); ?>" />
				</label>
				<fieldset>
					<legend><?php esc_html_e( 'Default heading behavior', 'saman-seo' ); ?></legend>
					<label class="saman-seo-links__choice">
						<input type="radio" name="settings[default_heading_behavior]" value="none" <?php checked( 'none', $heading_behavior ); ?> />
						<span><?php esc_html_e( 'None', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="settings[default_heading_behavior]" value="selected" <?php checked( 'selected', $heading_behavior ); ?> />
						<span><?php esc_html_e( 'Selected', 'saman-seo' ); ?></span>
					</label>
					<label class="saman-seo-links__choice">
						<input type="radio" name="settings[default_heading_behavior]" value="all" <?php checked( 'all', $heading_behavior ); ?> />
						<span><?php esc_html_e( 'All', 'saman-seo' ); ?></span>
					</label>
					<div class="saman-seo-links__heading-levels" data-settings-heading <?php echo ( 'selected' === $heading_behavior ) ? '' : 'hidden'; ?>>
						<?php foreach ( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ] as $level ) : ?>
							<label class="saman-seo-links__choice">
								<input type="checkbox" name="settings[default_heading_levels][]" value="<?php echo esc_attr( $level ); ?>" <?php checked( in_array( $level, $heading_levels, true ) ); ?> />
								<span><?php echo esc_html( strtoupper( $level ) ); ?></span>
							</label>
						<?php endforeach; ?>
					</div>
				</fieldset>
			</div>
		</section>

		<section>
			<h4><?php esc_html_e( 'Safeties', 'saman-seo' ); ?></h4>
			<div class="saman-seo-links__toggles">
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="settings[avoid_existing_links]" value="1" <?php checked( ! empty( $settings['avoid_existing_links'] ) ); ?> />
					<span><?php esc_html_e( 'Avoid replacing inside existing links', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="settings[prefer_word_boundaries]" value="1" <?php checked( ! empty( $settings['prefer_word_boundaries'] ) ); ?> />
					<span><?php esc_html_e( 'Prefer word boundaries', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="settings[normalize_accents]" value="1" <?php checked( ! empty( $settings['normalize_accents'] ) ); ?> />
					<span><?php esc_html_e( 'Normalize accents/diacritics when matching', 'saman-seo' ); ?></span>
				</label>
			</div>
		</section>

		<section>
			<h4><?php esc_html_e( 'Performance', 'saman-seo' ); ?></h4>
			<div class="saman-seo-links__toggles">
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="settings[cache_rendered_content]" value="1" <?php checked( ! empty( $settings['cache_rendered_content'] ) ); ?> />
					<span><?php esc_html_e( 'Cache rendered content', 'saman-seo' ); ?></span>
				</label>
				<label class="saman-seo-links__choice">
					<input type="checkbox" name="settings[chunk_long_documents]" value="1" <?php checked( ! empty( $settings['chunk_long_documents'] ) ); ?> />
					<span><?php esc_html_e( 'Chunk long documents (prevents timeouts)', 'saman-seo' ); ?></span>
				</label>
			</div>
		</section>

		<?php submit_button( __( 'Save settings', 'saman-seo' ) ); ?>
	</form>
</div>
