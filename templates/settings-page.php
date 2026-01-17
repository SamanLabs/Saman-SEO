<?php
/**
 * Settings admin template.
 *
 * @var Saman\SEO\Service\Settings $this
 *
 * @package Saman\SEO
 */

$social_defaults = get_option( 'SAMAN_SEO_social_defaults', [] );
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

$post_type_social_defaults = get_option( 'SAMAN_SEO_post_type_social_defaults', [] );
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
	''              => __( 'Use default (Article)', 'saman-seo' ),
	'article'       => __( 'Article', 'saman-seo' ),
	'blogposting'   => __( 'Blog posting', 'saman-seo' ),
	'newsarticle'   => __( 'News article', 'saman-seo' ),
	'product'       => __( 'Product', 'saman-seo' ),
	'profilepage'   => __( 'Profile page', 'saman-seo' ),
	'profile'       => __( 'Profile', 'saman-seo' ),
	'website'       => __( 'Website', 'saman-seo' ),
	'organization'  => __( 'Organization', 'saman-seo' ),
	'event'         => __( 'Event', 'saman-seo' ),
	'recipe'        => __( 'Recipe', 'saman-seo' ),
	'videoobject'   => __( 'Video object', 'saman-seo' ),
	'book'          => __( 'Book', 'saman-seo' ),
	'service'       => __( 'Service', 'saman-seo' ),
	'localbusiness' => __( 'Local business', 'saman-seo' ),
];

$render_schema_control = static function ( $field_name, $current_value, $input_id ) use ( $schema_itemtype_options ) {
	$current_value = (string) $current_value;
	$normalized    = strtolower( trim( $current_value ) );
	$has_preset    = array_key_exists( $normalized, $schema_itemtype_options );
	$select_value  = $has_preset ? $normalized : '__custom';
	$control_class = $has_preset ? 'saman-seo-schema-control is-preset' : 'saman-seo-schema-control is-custom';
	?>
	<div class="<?php echo esc_attr( $control_class ); ?>" data-schema-control>
		<select class="saman-seo-schema-control__select" data-schema-select aria-controls="<?php echo esc_attr( $input_id ); ?>">
			<?php foreach ( $schema_itemtype_options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $select_value, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
			<option value="__custom" <?php selected( $select_value, '__custom' ); ?>>
				<?php esc_html_e( 'Custom valueÃ¢â‚¬Â¦', 'saman-seo' ); ?>
			</option>
		</select>
		<input
			type="text"
			class="regular-text saman-seo-schema-control__input"
			id="<?php echo esc_attr( $input_id ); ?>"
			name="<?php echo esc_attr( $field_name ); ?>"
			value="<?php echo esc_attr( $current_value ); ?>"
			data-schema-input
		/>
	</div>
	<?php
};

// Render top bar
\Saman\SEO\Admin_Topbar::render( 'defaults' );
?>
<div class="wrap saman-seo-page saman-seo-settings">
	<div class="saman-seo-tabs" data-component="saman-seo-tabs">
		<div class="nav-tab-wrapper saman-seo-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Site default sections', 'saman-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="saman-seo-tab-link-robots"
				role="tab"
				aria-selected="true"
				aria-controls="saman-seo-tab-robots"
				data-saman-seo-tab="saman-seo-tab-robots"
			>
				<?php esc_html_e( 'Robots & Canonicals', 'saman-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="saman-seo-tab-link-modules"
				role="tab"
				aria-selected="false"
				aria-controls="saman-seo-tab-modules"
				data-saman-seo-tab="saman-seo-tab-modules"
			>
				<?php esc_html_e( 'Modules', 'saman-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="saman-seo-tab-link-knowledge"
				role="tab"
				aria-selected="false"
				aria-controls="saman-seo-tab-knowledge"
				data-saman-seo-tab="saman-seo-tab-knowledge"
			>
				<?php esc_html_e( 'Knowledge Graph', 'saman-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="saman-seo-tab-link-export"
				role="tab"
				aria-selected="false"
				aria-controls="saman-seo-tab-export"
				data-saman-seo-tab="saman-seo-tab-export"
			>
				<?php esc_html_e( 'Export / Backup', 'saman-seo' ); ?>
			</button>
		</div>

		<form action="options.php" method="post" class="saman-seo-settings__form">
			<?php settings_fields( 'saman-seo' ); ?>

			<div
				id="saman-seo-tab-robots"
				class="saman-seo-tab-panel is-active"
				role="tabpanel"
				aria-labelledby="saman-seo-tab-link-robots"
			>
				<section class="saman-seo-card">
					<h2><?php esc_html_e( 'Robots & Canonicals', 'saman-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Index by default', 'saman-seo' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="SAMAN_SEO_default_noindex" value="1" <?php checked( get_option( 'SAMAN_SEO_default_noindex' ), '1' ); ?> />
									<?php esc_html_e( 'Treat new content as noindex', 'saman-seo' ); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="SAMAN_SEO_default_nofollow" value="1" <?php checked( get_option( 'SAMAN_SEO_default_nofollow' ), '1' ); ?> />
									<?php esc_html_e( 'Treat new content as nofollow', 'saman-seo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="SAMAN_SEO_global_robots"><?php esc_html_e( 'Global robots meta', 'saman-seo' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="SAMAN_SEO_global_robots" name="SAMAN_SEO_global_robots" value="<?php echo esc_attr( get_option( 'SAMAN_SEO_global_robots' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Comma separated instructions (index, follow, max-snippet, etc.)', 'saman-seo' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="SAMAN_SEO_hreflang_map"><?php esc_html_e( 'Hreflang map (JSON)', 'saman-seo' ); ?></label>
							</th>
							<td>
								<textarea class="large-text code" rows="3" id="SAMAN_SEO_hreflang_map" name="SAMAN_SEO_hreflang_map" placeholder='{"en-us":"https://example.com/","es-es":"https://example.com/es/"}'><?php echo esc_textarea( get_option( 'SAMAN_SEO_hreflang_map' ) ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="SAMAN_SEO_robots_txt"><?php esc_html_e( 'Robots.txt override', 'saman-seo' ); ?></label>
							</th>
							<td>
								<textarea class="large-text code" rows="6" id="SAMAN_SEO_robots_txt" name="SAMAN_SEO_robots_txt"><?php echo esc_textarea( get_option( 'SAMAN_SEO_robots_txt' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Leave blank to respect WP core output.', 'saman-seo' ); ?></p>
							</td>
						</tr>
					</table>
				</section>
			</div>

		<div
			id="saman-seo-tab-modules"
			class="saman-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="saman-seo-tab-link-modules"
		>
			<div class="saman-seo-modules-grid">
				<!-- Sitemap Enhancer Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'Sitemap Enhancer', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_sitemap_enhancer" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_sitemap_enhancer' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Add image, video, and news data to WordPress core sitemaps for better search engine indexing.', 'saman-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-sitemap' ) ); ?>" class="saman-seo-module-link">
							<?php esc_html_e( 'Configure Sitemap Ã¢â€ â€™', 'saman-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Redirect Manager Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-controls-forward"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'Redirect Manager', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_redirect_manager" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_redirect_manager' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Manage 301/302 redirects with an intuitive interface and WP-CLI commands.', 'saman-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-redirects' ) ); ?>" class="saman-seo-module-link">
							<?php esc_html_e( 'Manage Redirects Ã¢â€ â€™', 'saman-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- 404 Logging Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( '404 Error Logging', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_404_logging" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_404_logging' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Monitor and track 404 errors with anonymized referrer data to fix broken links.', 'saman-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-404' ) ); ?>" class="saman-seo-module-link">
							<?php esc_html_e( 'View 404 Log Ã¢â€ â€™', 'saman-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Dynamic Social Card Generator Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-share"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'Dynamic Social Card Generator', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_og_preview" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_og_preview', '1' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Generate dynamic PNG social card images on-the-fly for sharing. Note: This only controls dynamic image generation, not Open Graph meta tags.', 'saman-seo' ); ?>
						</p>
					<?php if ( '1' === get_option( 'SAMAN_SEO_enable_og_preview', '1' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-types#social-cards' ) ); ?>" class="saman-seo-module-link">
							<?php esc_html_e( 'Customize Social Cards Ã¢â€ â€™', 'saman-seo' ); ?>
						</a>
					<?php endif; ?>
					</div>
				</div>

				<!-- LLM.txt Generator Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'LLM.txt Generator', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_llm_txt" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_llm_txt', '1' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Help AI engines discover your content with a standardized llm.txt file for better AI indexing.', 'saman-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-sitemap#llm' ) ); ?>" class="saman-seo-module-link">
							<?php esc_html_e( 'Configure LLM.txt Ã¢â€ â€™', 'saman-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Local SEO Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-location"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'Local SEO', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_local_seo" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_local_seo', '0' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Add Local Business schema markup with business info, opening hours, and location data for better local search visibility.', 'saman-seo' ); ?>
						</p>
						<?php if ( '1' === get_option( 'SAMAN_SEO_enable_local_seo', '0' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo-local-seo' ) ); ?>" class="saman-seo-module-link">
								<?php esc_html_e( 'Configure Local SEO Ã¢â€ â€™', 'saman-seo' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Analytics Module -->
				<div class="saman-seo-module-card">
					<div class="saman-seo-module-icon">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<div class="saman-seo-module-content">
						<div class="saman-seo-module-header">
							<h3><?php esc_html_e( 'Usage Analytics', 'saman-seo' ); ?></h3>
							<label class="saman-seo-toggle-switch">
								<input type="checkbox" name="SAMAN_SEO_enable_analytics" value="1" <?php checked( get_option( 'SAMAN_SEO_enable_analytics', '1' ), '1' ); ?> />
								<span class="saman-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="saman-seo-module-description">
							<?php esc_html_e( 'Help improve Saman SEO by sending anonymous usage data. No personal information or user data is collected - only plugin activation and feature usage.', 'saman-seo' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="saman-seo-tabs__actions">
			<?php submit_button( __( 'Save defaults', 'saman-seo' ) ); ?>
		</div>
		</form>

		<div
			id="saman-seo-tab-knowledge"
			class="saman-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="saman-seo-tab-link-knowledge"
		>
			<section class="saman-seo-card">
				<h2><?php esc_html_e( 'Knowledge Graph & Schema.org', 'saman-seo' ); ?></h2>
				<p><?php esc_html_e( 'Help search engines understand who runs this site. This data is used in GoogleÃ¢â‚¬â„¢s Knowledge Graph and other rich results.', 'saman-seo' ); ?></p>
					<form action="options.php" method="post">
						<?php settings_fields( 'SAMAN_SEO_knowledge' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Site represents', 'saman-seo' ); ?></th>
							<td>
								<label>
									<input type="radio" name="SAMAN_SEO_homepage_knowledge_type" value="organization" <?php checked( get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ), 'organization' ); ?> />
									<?php esc_html_e( 'Organization', 'saman-seo' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="SAMAN_SEO_homepage_knowledge_type" value="person" <?php checked( get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ), 'person' ); ?> />
									<?php esc_html_e( 'Person', 'saman-seo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="SAMAN_SEO_homepage_organization_name"><?php esc_html_e( 'Organization name', 'saman-seo' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="SAMAN_SEO_homepage_organization_name" name="SAMAN_SEO_homepage_organization_name" value="<?php echo esc_attr( get_option( 'SAMAN_SEO_homepage_organization_name' ) ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="SAMAN_SEO_homepage_organization_logo"><?php esc_html_e( 'Organization logo', 'saman-seo' ); ?></label>
							</th>
							<td>
								<div class="saman-seo-media-field">
									<input type="url" class="regular-text" id="SAMAN_SEO_homepage_organization_logo" name="SAMAN_SEO_homepage_organization_logo" value="<?php echo esc_url( get_option( 'SAMAN_SEO_homepage_organization_logo' ) ); ?>" />
									<button type="button" class="button saman-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-seo' ); ?></button>
								</div>
								<p class="description"><?php esc_html_e( 'Recommended: square logo at least 112Ãƒâ€”112 px. Used in structured data and social previews.', 'saman-seo' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save Knowledge Graph settings', 'saman-seo' ) ); ?>
				</form>
			</section>
		</div>

		<div
			id="saman-seo-tab-export"
			class="saman-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="saman-seo-tab-link-export"
		>
			<section class="saman-seo-card">
				<h2><?php esc_html_e( 'Export / Backup', 'saman-seo' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'SAMAN_SEO_export' ); ?>
					<input type="hidden" name="action" value="SAMAN_SEO_export" />
					<?php submit_button( __( 'Download JSON', 'saman-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
		</div>
	</div>
</div>
