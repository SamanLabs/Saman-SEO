<?php
/**
 * Settings admin template.
 *
 * @var WPSEOPilot\Service\Settings $this
 *
 * @package WPSEOPilot
 */

?>
<div class="wrap wpseopilot-settings">
	<h1><?php esc_html_e( 'WP SEO Pilot — Site Defaults', 'wp-seo-pilot' ); ?></h1>

	<form action="options.php" method="post" class="wpseopilot-settings__form">
		<?php settings_fields( 'wpseopilot' ); ?>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Templates & Content', 'wp-seo-pilot' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="wpseopilot_default_title_template"><?php esc_html_e( 'Title template', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="wpseopilot_default_title_template" name="wpseopilot_default_title_template" value="<?php echo esc_attr( get_option( 'wpseopilot_default_title_template' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Available tags: %post_title%, %site_title%, %tagline%, %post_author%', 'wp-seo-pilot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_default_meta_description"><?php esc_html_e( 'Default meta description', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<textarea class="large-text" rows="3" id="wpseopilot_default_meta_description" name="wpseopilot_default_meta_description"><?php echo esc_textarea( get_option( 'wpseopilot_default_meta_description' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Default OG/Twitter image', 'wp-seo-pilot' ); ?></th>
					<td>
						<div class="wpseopilot-media-field">
							<input type="url" class="regular-text" id="wpseopilot_default_og_image" name="wpseopilot_default_og_image" value="<?php echo esc_url( get_option( 'wpseopilot_default_og_image' ) ); ?>" />
							<button type="button" class="button wpseopilot-media-trigger"><?php esc_html_e( 'Select image', 'wp-seo-pilot' ); ?></button>
						</div>
						<p class="description"><?php esc_html_e( 'Used when a post is missing a featured image or explicit override.', 'wp-seo-pilot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_default_social_width"><?php esc_html_e( 'Fallback social image size', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<input type="number" min="1" id="wpseopilot_default_social_width" name="wpseopilot_default_social_width" value="<?php echo esc_attr( get_option( 'wpseopilot_default_social_width' ) ); ?>" /> ×
						<input type="number" min="1" id="wpseopilot_default_social_height" name="wpseopilot_default_social_height" value="<?php echo esc_attr( get_option( 'wpseopilot_default_social_height' ) ); ?>" />
					</td>
				</tr>
			</table>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Robots & Canonicals', 'wp-seo-pilot' ); ?></h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row"><?php esc_html_e( 'Index by default', 'wp-seo-pilot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpseopilot_default_noindex" value="1" <?php checked( get_option( 'wpseopilot_default_noindex' ), '1' ); ?> />
							<?php esc_html_e( 'Treat new content as noindex', 'wp-seo-pilot' ); ?>
						</label>
						<br />
						<label>
							<input type="checkbox" name="wpseopilot_default_nofollow" value="1" <?php checked( get_option( 'wpseopilot_default_nofollow' ), '1' ); ?> />
							<?php esc_html_e( 'Treat new content as nofollow', 'wp-seo-pilot' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_global_robots"><?php esc_html_e( 'Global robots meta', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<input type="text" class="regular-text" id="wpseopilot_global_robots" name="wpseopilot_global_robots" value="<?php echo esc_attr( get_option( 'wpseopilot_global_robots' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Comma separated instructions (index, follow, max-snippet, etc.)', 'wp-seo-pilot' ); ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_hreflang_map"><?php esc_html_e( 'Hreflang map (JSON)', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<textarea class="large-text code" rows="3" id="wpseopilot_hreflang_map" name="wpseopilot_hreflang_map" placeholder='{"en-us":"https://example.com/","es-es":"https://example.com/es/"}'><?php echo esc_textarea( get_option( 'wpseopilot_hreflang_map' ) ); ?></textarea>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="wpseopilot_robots_txt"><?php esc_html_e( 'Robots.txt override', 'wp-seo-pilot' ); ?></label>
					</th>
					<td>
						<textarea class="large-text code" rows="6" id="wpseopilot_robots_txt" name="wpseopilot_robots_txt"><?php echo esc_textarea( get_option( 'wpseopilot_robots_txt' ) ); ?></textarea>
						<p class="description"><?php esc_html_e( 'Leave blank to respect WP core output.', 'wp-seo-pilot' ); ?></p>
					</td>
				</tr>
			</table>
		</section>

		<section class="wpseopilot-card">
			<h2><?php esc_html_e( 'Modules', 'wp-seo-pilot' ); ?></h2>
			<table class="form-table">
				<tr>
					<th scope="row"><?php esc_html_e( 'Sitemap enhancer', 'wp-seo-pilot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpseopilot_enable_sitemap_enhancer" value="1" <?php checked( get_option( 'wpseopilot_enable_sitemap_enhancer' ), '1' ); ?> />
							<?php esc_html_e( 'Add image, video, and news data to WP core sitemaps.', 'wp-seo-pilot' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Redirect manager', 'wp-seo-pilot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpseopilot_enable_redirect_manager" value="1" <?php checked( get_option( 'wpseopilot_enable_redirect_manager' ), '1' ); ?> />
							<?php esc_html_e( 'Enable UI + WP-CLI commands for redirects.', 'wp-seo-pilot' ); ?>
						</label>
					</td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( '404 logging', 'wp-seo-pilot' ); ?></th>
					<td>
						<label>
							<input type="checkbox" name="wpseopilot_enable_404_logging" value="1" <?php checked( get_option( 'wpseopilot_enable_404_logging' ), '1' ); ?> />
							<?php esc_html_e( 'Monitor and anonymize 404 referrers.', 'wp-seo-pilot' ); ?>
						</label>
					</td>
				</tr>
			</table>
		</section>

		<?php submit_button( __( 'Save SEO defaults', 'wp-seo-pilot' ) ); ?>
	</form>

	<section class="wpseopilot-card wpseopilot-card--split">
		<div>
			<h2><?php esc_html_e( 'Import settings & metadata', 'wp-seo-pilot' ); ?></h2>
			<p><?php esc_html_e( 'Migrate from Yoast SEO, Rank Math, or All in One SEO.', 'wp-seo-pilot' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpseopilot_import' ); ?>
				<input type="hidden" name="action" value="wpseopilot_import" />
				<select name="vendor">
					<option value="yoast"><?php esc_html_e( 'Yoast SEO', 'wp-seo-pilot' ); ?></option>
					<option value="rankmath"><?php esc_html_e( 'Rank Math', 'wp-seo-pilot' ); ?></option>
					<option value="aioseo"><?php esc_html_e( 'All in One SEO', 'wp-seo-pilot' ); ?></option>
				</select>
				<label style="margin-left:12px;">
					<input type="checkbox" name="dry_run" value="1" />
					<?php esc_html_e( 'Dry run (preview counts only)', 'wp-seo-pilot' ); ?>
				</label>
				<?php submit_button( __( 'Run importer', 'wp-seo-pilot' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
		<div>
			<h2><?php esc_html_e( 'Export / Backup', 'wp-seo-pilot' ); ?></h2>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( 'wpseopilot_export' ); ?>
				<input type="hidden" name="action" value="wpseopilot_export" />
				<?php submit_button( __( 'Download JSON', 'wp-seo-pilot' ), 'secondary', 'submit', false ); ?>
			</form>
		</div>
	</section>
</div>
