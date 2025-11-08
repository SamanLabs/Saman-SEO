<?php
/**
 * Onboarding wizard template.
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap wpseopilot-onboarding">
	<h1><?php esc_html_e( 'Welcome to WP SEO Pilot', 'wp-seo-pilot' ); ?></h1>
	<p><?php esc_html_e( 'Three quick steps to ship optimized snippets.', 'wp-seo-pilot' ); ?></p>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'wpseopilot_onboarding' ); ?>
		<input type="hidden" name="action" value="wpseopilot_onboarding" />

		<table class="form-table">
			<tr>
				<th scope="row"><?php esc_html_e( 'Default description', 'wp-seo-pilot' ); ?></th>
				<td>
					<textarea name="default_meta_description" rows="3" class="large-text"><?php echo esc_textarea( get_option( 'wpseopilot_default_meta_description' ) ); ?></textarea>
					<p class="description"><?php esc_html_e( 'Shown when posts do not have their own description.', 'wp-seo-pilot' ); ?></p>
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Fallback OG image', 'wp-seo-pilot' ); ?></th>
				<td>
					<input type="url" class="regular-text" name="default_og_image" value="<?php echo esc_url( get_option( 'wpseopilot_default_og_image' ) ); ?>" />
				</td>
			</tr>
			<tr>
				<th scope="row"><?php esc_html_e( 'Guided tour', 'wp-seo-pilot' ); ?></th>
				<td>
					<label><input type="checkbox" name="tour_completed" value="1" checked /> <?php esc_html_e( 'Show admin pointers highlighting SEO fields.', 'wp-seo-pilot' ); ?></label>
				</td>
			</tr>
		</table>

		<?php submit_button( __( 'Finish setup', 'wp-seo-pilot' ) ); ?>
	</form>
</div>
