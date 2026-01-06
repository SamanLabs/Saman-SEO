<?php
/**
 * Settings admin template.
 *
 * @var WPSEOPilot\Service\Settings $this
 *
 * @package WPSEOPilot
 */

$social_defaults = get_option( 'wpseopilot_social_defaults', [] );
if ( ! is_array( $social_defaults ) ) {
	$social_defaults = [];
}

$social_field_defaults = [
	'og_title'            => '',
	'og_description'      => '',
	'twitter_title'       => '',
	'twitter_description' => '',
	'image_source'        => '',
	'schema_itemtype'     => '',
];

$social_defaults = wp_parse_args( $social_defaults, $social_field_defaults );

$post_type_social_defaults = get_option( 'wpseopilot_post_type_social_defaults', [] );
if ( ! is_array( $post_type_social_defaults ) ) {
	$post_type_social_defaults = [];
}

$post_types = get_post_types(
	[
		'public'  => true,
		'show_ui' => true,
	],
	'objects'
);

if ( isset( $post_types['attachment'] ) ) {
	unset( $post_types['attachment'] );
}

$schema_itemtype_options = [
	''              => __( 'Use default (Article)', 'wp-seo-pilot' ),
	'article'       => __( 'Article', 'wp-seo-pilot' ),
	'blogposting'   => __( 'Blog posting', 'wp-seo-pilot' ),
	'newsarticle'   => __( 'News article', 'wp-seo-pilot' ),
	'product'       => __( 'Product', 'wp-seo-pilot' ),
	'profilepage'   => __( 'Profile page', 'wp-seo-pilot' ),
	'profile'       => __( 'Profile', 'wp-seo-pilot' ),
	'website'       => __( 'Website', 'wp-seo-pilot' ),
	'organization'  => __( 'Organization', 'wp-seo-pilot' ),
	'event'         => __( 'Event', 'wp-seo-pilot' ),
	'recipe'        => __( 'Recipe', 'wp-seo-pilot' ),
	'videoobject'   => __( 'Video object', 'wp-seo-pilot' ),
	'book'          => __( 'Book', 'wp-seo-pilot' ),
	'service'       => __( 'Service', 'wp-seo-pilot' ),
	'localbusiness' => __( 'Local business', 'wp-seo-pilot' ),
];

$render_schema_control = static function ( $field_name, $current_value, $input_id ) use ( $schema_itemtype_options ) {
	$current_value = (string) $current_value;
	$normalized    = strtolower( trim( $current_value ) );
	$has_preset    = array_key_exists( $normalized, $schema_itemtype_options );
	$select_value  = $has_preset ? $normalized : '__custom';
	$control_class = $has_preset ? 'wpseopilot-schema-control is-preset' : 'wpseopilot-schema-control is-custom';
	?>
	<div class="<?php echo esc_attr( $control_class ); ?>" data-schema-control>
		<select class="wpseopilot-schema-control__select" data-schema-select aria-controls="<?php echo esc_attr( $input_id ); ?>">
			<?php foreach ( $schema_itemtype_options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $select_value, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
			<option value="__custom" <?php selected( $select_value, '__custom' ); ?>>
				<?php esc_html_e( 'Custom value…', 'wp-seo-pilot' ); ?>
			</option>
		</select>
		<input
			type="text"
			class="regular-text wpseopilot-schema-control__input"
			id="<?php echo esc_attr( $input_id ); ?>"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( $current_value ); ?>"
			data-schema-input
		/>
	</div>
	<?php
};

// Render top bar
\WPSEOPilot\Admin_Topbar::render( 'defaults' );
?>
<div class="wrap wpseopilot-page wpseopilot-settings">
	<div class="wpseopilot-tabs" data-component="wpseopilot-tabs">
		<div class="nav-tab-wrapper wpseopilot-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Site default sections', 'wp-seo-pilot' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="wpseopilot-tab-link-robots"
				role="tab"
				aria-selected="true"
				aria-controls="wpseopilot-tab-robots"
				data-wpseopilot-tab="wpseopilot-tab-robots"
			>
				<?php esc_html_e( 'Robots & Canonicals', 'wp-seo-pilot' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-modules"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-modules"
				data-wpseopilot-tab="wpseopilot-tab-modules"
			>
				<?php esc_html_e( 'Modules', 'wp-seo-pilot' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-knowledge"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-knowledge"
				data-wpseopilot-tab="wpseopilot-tab-knowledge"
			>
				<?php esc_html_e( 'Knowledge Graph', 'wp-seo-pilot' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-export"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-export"
				data-wpseopilot-tab="wpseopilot-tab-export"
			>
				<?php esc_html_e( 'Export / Backup', 'wp-seo-pilot' ); ?>
			</button>
		</div>

		<form action="options.php" method="post" class="wpseopilot-settings__form">
			<?php settings_fields( 'wpseopilot' ); ?>

			<div
				id="wpseopilot-tab-robots"
				class="wpseopilot-tab-panel is-active"
				role="tabpanel"
				aria-labelledby="wpseopilot-tab-link-robots"
			>
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
			</div>

		<div
			id="wpseopilot-tab-modules"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-modules"
		>
			<div class="wpseopilot-modules-grid">
				<!-- Sitemap Enhancer Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'Sitemap Enhancer', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_sitemap_enhancer" value="1" <?php checked( get_option( 'wpseopilot_enable_sitemap_enhancer' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Add image, video, and news data to WordPress core sitemaps for better search engine indexing.', 'wp-seo-pilot' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-sitemap' ) ); ?>" class="wpseopilot-module-link">
							<?php esc_html_e( 'Configure Sitemap →', 'wp-seo-pilot' ); ?>
						</a>
					</div>
				</div>

				<!-- Redirect Manager Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-controls-forward"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'Redirect Manager', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_redirect_manager" value="1" <?php checked( get_option( 'wpseopilot_enable_redirect_manager' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Manage 301/302 redirects with an intuitive interface and WP-CLI commands.', 'wp-seo-pilot' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-redirects' ) ); ?>" class="wpseopilot-module-link">
							<?php esc_html_e( 'Manage Redirects →', 'wp-seo-pilot' ); ?>
						</a>
					</div>
				</div>

				<!-- 404 Logging Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( '404 Error Logging', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_404_logging" value="1" <?php checked( get_option( 'wpseopilot_enable_404_logging' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Monitor and track 404 errors with anonymized referrer data to fix broken links.', 'wp-seo-pilot' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-404' ) ); ?>" class="wpseopilot-module-link">
							<?php esc_html_e( 'View 404 Log →', 'wp-seo-pilot' ); ?>
						</a>
					</div>
				</div>

				<!-- Dynamic Social Card Generator Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-share"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'Dynamic Social Card Generator', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_og_preview" value="1" <?php checked( get_option( 'wpseopilot_enable_og_preview', '1' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Generate dynamic PNG social card images on-the-fly for sharing. Note: This only controls dynamic image generation, not Open Graph meta tags.', 'wp-seo-pilot' ); ?>
						</p>
					<?php if ( '1' === get_option( 'wpseopilot_enable_og_preview', '1' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-types#social-cards' ) ); ?>" class="wpseopilot-module-link">
							<?php esc_html_e( 'Customize Social Cards →', 'wp-seo-pilot' ); ?>
						</a>
					<?php endif; ?>
					</div>
				</div>

				<!-- LLM.txt Generator Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'LLM.txt Generator', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_llm_txt" value="1" <?php checked( get_option( 'wpseopilot_enable_llm_txt', '1' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Help AI engines discover your content with a standardized llm.txt file for better AI indexing.', 'wp-seo-pilot' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-sitemap#llm' ) ); ?>" class="wpseopilot-module-link">
							<?php esc_html_e( 'Configure LLM.txt →', 'wp-seo-pilot' ); ?>
						</a>
					</div>
				</div>

				<!-- Local SEO Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-location"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'Local SEO', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_local_seo" value="1" <?php checked( get_option( 'wpseopilot_enable_local_seo', '0' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Add Local Business schema markup with business info, opening hours, and location data for better local search visibility.', 'wp-seo-pilot' ); ?>
						</p>
						<?php if ( '1' === get_option( 'wpseopilot_enable_local_seo', '0' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot-local-seo' ) ); ?>" class="wpseopilot-module-link">
								<?php esc_html_e( 'Configure Local SEO →', 'wp-seo-pilot' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Analytics Module -->
				<div class="wpseopilot-module-card">
					<div class="wpseopilot-module-icon">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<div class="wpseopilot-module-content">
						<div class="wpseopilot-module-header">
							<h3><?php esc_html_e( 'Usage Analytics', 'wp-seo-pilot' ); ?></h3>
							<label class="wpseopilot-toggle-switch">
								<input type="checkbox" name="wpseopilot_enable_analytics" value="1" <?php checked( get_option( 'wpseopilot_enable_analytics', '1' ), '1' ); ?> />
								<span class="wpseopilot-toggle-slider"></span>
							</label>
						</div>
						<p class="wpseopilot-module-description">
							<?php esc_html_e( 'Help improve WP SEO Pilot by sending anonymous usage data. No personal information or user data is collected - only plugin activation and feature usage.', 'wp-seo-pilot' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="wpseopilot-tabs__actions">
			<?php submit_button( __( 'Save defaults', 'wp-seo-pilot' ) ); ?>
		</div>
		</form>

		<div
			id="wpseopilot-tab-knowledge"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-knowledge"
		>
			<section class="wpseopilot-card">
				<h2><?php esc_html_e( 'Knowledge Graph & Schema.org', 'wp-seo-pilot' ); ?></h2>
				<p><?php esc_html_e( 'Help search engines understand who runs this site. This data is used in Google’s Knowledge Graph and other rich results.', 'wp-seo-pilot' ); ?></p>
					<form action="options.php" method="post">
						<?php settings_fields( 'wpseopilot_knowledge' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Site represents', 'wp-seo-pilot' ); ?></th>
							<td>
								<label>
									<input type="radio" name="wpseopilot_homepage_knowledge_type" value="organization" <?php checked( get_option( 'wpseopilot_homepage_knowledge_type', 'organization' ), 'organization' ); ?> />
									<?php esc_html_e( 'Organization', 'wp-seo-pilot' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="wpseopilot_homepage_knowledge_type" value="person" <?php checked( get_option( 'wpseopilot_homepage_knowledge_type', 'organization' ), 'person' ); ?> />
									<?php esc_html_e( 'Person', 'wp-seo-pilot' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpseopilot_homepage_organization_name"><?php esc_html_e( 'Organization name', 'wp-seo-pilot' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="wpseopilot_homepage_organization_name" name="wpseopilot_homepage_organization_name" value="<?php echo esc_attr( get_option( 'wpseopilot_homepage_organization_name' ) ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="wpseopilot_homepage_organization_logo"><?php esc_html_e( 'Organization logo', 'wp-seo-pilot' ); ?></label>
							</th>
							<td>
								<div class="wpseopilot-media-field">
									<input type="url" class="regular-text" id="wpseopilot_homepage_organization_logo" name="wpseopilot_homepage_organization_logo" value="<?php echo esc_url( get_option( 'wpseopilot_homepage_organization_logo' ) ); ?>" />
									<button type="button" class="button wpseopilot-media-trigger"><?php esc_html_e( 'Select image', 'wp-seo-pilot' ); ?></button>
								</div>
								<p class="description"><?php esc_html_e( 'Recommended: square logo at least 112×112 px. Used in structured data and social previews.', 'wp-seo-pilot' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save Knowledge Graph settings', 'wp-seo-pilot' ) ); ?>
				</form>
			</section>
		</div>

		<div
			id="wpseopilot-tab-export"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-export"
		>
			<section class="wpseopilot-card">
				<h2><?php esc_html_e( 'Export / Backup', 'wp-seo-pilot' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'wpseopilot_export' ); ?>
					<input type="hidden" name="action" value="wpseopilot_export" />
					<?php submit_button( __( 'Download JSON', 'wp-seo-pilot' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
		</div>
	</div>
</div>
