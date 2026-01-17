<?php
/**
 * Settings admin template.
 *
 * @var WPSEOPilot\Service\Settings $this
 *
 * @package WPSEOPilot
 */

$social_defaults = get_option( 'samanlabs_seo_social_defaults', [] );
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

$post_type_social_defaults = get_option( 'samanlabs_seo_post_type_social_defaults', [] );
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
	''              => __( 'Use default (Article)', 'saman-labs-seo' ),
	'article'       => __( 'Article', 'saman-labs-seo' ),
	'blogposting'   => __( 'Blog posting', 'saman-labs-seo' ),
	'newsarticle'   => __( 'News article', 'saman-labs-seo' ),
	'product'       => __( 'Product', 'saman-labs-seo' ),
	'profilepage'   => __( 'Profile page', 'saman-labs-seo' ),
	'profile'       => __( 'Profile', 'saman-labs-seo' ),
	'website'       => __( 'Website', 'saman-labs-seo' ),
	'organization'  => __( 'Organization', 'saman-labs-seo' ),
	'event'         => __( 'Event', 'saman-labs-seo' ),
	'recipe'        => __( 'Recipe', 'saman-labs-seo' ),
	'videoobject'   => __( 'Video object', 'saman-labs-seo' ),
	'book'          => __( 'Book', 'saman-labs-seo' ),
	'service'       => __( 'Service', 'saman-labs-seo' ),
	'localbusiness' => __( 'Local business', 'saman-labs-seo' ),
];

$render_schema_control = static function ( $field_name, $current_value, $input_id ) use ( $schema_itemtype_options ) {
	$current_value = (string) $current_value;
	$normalized    = strtolower( trim( $current_value ) );
	$has_preset    = array_key_exists( $normalized, $schema_itemtype_options );
	$select_value  = $has_preset ? $normalized : '__custom';
	$control_class = $has_preset ? 'samanlabs-seo-schema-control is-preset' : 'samanlabs-seo-schema-control is-custom';
	?>
	<div class="<?php echo esc_attr( $control_class ); ?>" data-schema-control>
		<select class="samanlabs-seo-schema-control__select" data-schema-select aria-controls="<?php echo esc_attr( $input_id ); ?>">
			<?php foreach ( $schema_itemtype_options as $value => $label ) : ?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $select_value, $value ); ?>>
					<?php echo esc_html( $label ); ?>
				</option>
			<?php endforeach; ?>
			<option value="__custom" <?php selected( $select_value, '__custom' ); ?>>
				<?php esc_html_e( 'Custom value…', 'saman-labs-seo' ); ?>
			</option>
		</select>
		<input
			type="text"
			class="regular-text samanlabs-seo-schema-control__input"
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
<div class="wrap samanlabs-seo-page samanlabs-seo-settings">
	<div class="samanlabs-seo-tabs" data-component="samanlabs-seo-tabs">
		<div class="nav-tab-wrapper samanlabs-seo-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Site default sections', 'saman-labs-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="samanlabs-seo-tab-link-robots"
				role="tab"
				aria-selected="true"
				aria-controls="samanlabs-seo-tab-robots"
				data-samanlabs-seo-tab="samanlabs-seo-tab-robots"
			>
				<?php esc_html_e( 'Robots & Canonicals', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-modules"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-modules"
				data-samanlabs-seo-tab="samanlabs-seo-tab-modules"
			>
				<?php esc_html_e( 'Modules', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-knowledge"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-knowledge"
				data-samanlabs-seo-tab="samanlabs-seo-tab-knowledge"
			>
				<?php esc_html_e( 'Knowledge Graph', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-export"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-export"
				data-samanlabs-seo-tab="samanlabs-seo-tab-export"
			>
				<?php esc_html_e( 'Export / Backup', 'saman-labs-seo' ); ?>
			</button>
		</div>

		<form action="options.php" method="post" class="samanlabs-seo-settings__form">
			<?php settings_fields( 'samanlabs-seo' ); ?>

			<div
				id="samanlabs-seo-tab-robots"
				class="samanlabs-seo-tab-panel is-active"
				role="tabpanel"
				aria-labelledby="samanlabs-seo-tab-link-robots"
			>
				<section class="samanlabs-seo-card">
					<h2><?php esc_html_e( 'Robots & Canonicals', 'saman-labs-seo' ); ?></h2>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Index by default', 'saman-labs-seo' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="samanlabs_seo_default_noindex" value="1" <?php checked( get_option( 'samanlabs_seo_default_noindex' ), '1' ); ?> />
									<?php esc_html_e( 'Treat new content as noindex', 'saman-labs-seo' ); ?>
								</label>
								<br />
								<label>
									<input type="checkbox" name="samanlabs_seo_default_nofollow" value="1" <?php checked( get_option( 'samanlabs_seo_default_nofollow' ), '1' ); ?> />
									<?php esc_html_e( 'Treat new content as nofollow', 'saman-labs-seo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="samanlabs_seo_global_robots"><?php esc_html_e( 'Global robots meta', 'saman-labs-seo' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="samanlabs_seo_global_robots" name="samanlabs_seo_global_robots" value="<?php echo esc_attr( get_option( 'samanlabs_seo_global_robots' ) ); ?>" />
								<p class="description"><?php esc_html_e( 'Comma separated instructions (index, follow, max-snippet, etc.)', 'saman-labs-seo' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="samanlabs_seo_hreflang_map"><?php esc_html_e( 'Hreflang map (JSON)', 'saman-labs-seo' ); ?></label>
							</th>
							<td>
								<textarea class="large-text code" rows="3" id="samanlabs_seo_hreflang_map" name="samanlabs_seo_hreflang_map" placeholder='{"en-us":"https://example.com/","es-es":"https://example.com/es/"}'><?php echo esc_textarea( get_option( 'samanlabs_seo_hreflang_map' ) ); ?></textarea>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="samanlabs_seo_robots_txt"><?php esc_html_e( 'Robots.txt override', 'saman-labs-seo' ); ?></label>
							</th>
							<td>
								<textarea class="large-text code" rows="6" id="samanlabs_seo_robots_txt" name="samanlabs_seo_robots_txt"><?php echo esc_textarea( get_option( 'samanlabs_seo_robots_txt' ) ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Leave blank to respect WP core output.', 'saman-labs-seo' ); ?></p>
							</td>
						</tr>
					</table>
				</section>
			</div>

		<div
			id="samanlabs-seo-tab-modules"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-modules"
		>
			<div class="samanlabs-seo-modules-grid">
				<!-- Sitemap Enhancer Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'Sitemap Enhancer', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_sitemap_enhancer" value="1" <?php checked( get_option( 'samanlabs_seo_enable_sitemap_enhancer' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Add image, video, and news data to WordPress core sitemaps for better search engine indexing.', 'saman-labs-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-sitemap' ) ); ?>" class="samanlabs-seo-module-link">
							<?php esc_html_e( 'Configure Sitemap →', 'saman-labs-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Redirect Manager Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-controls-forward"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'Redirect Manager', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_redirect_manager" value="1" <?php checked( get_option( 'samanlabs_seo_enable_redirect_manager' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Manage 301/302 redirects with an intuitive interface and WP-CLI commands.', 'saman-labs-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-redirects' ) ); ?>" class="samanlabs-seo-module-link">
							<?php esc_html_e( 'Manage Redirects →', 'saman-labs-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- 404 Logging Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-warning"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( '404 Error Logging', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_404_logging" value="1" <?php checked( get_option( 'samanlabs_seo_enable_404_logging' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Monitor and track 404 errors with anonymized referrer data to fix broken links.', 'saman-labs-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-404' ) ); ?>" class="samanlabs-seo-module-link">
							<?php esc_html_e( 'View 404 Log →', 'saman-labs-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Dynamic Social Card Generator Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-share"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'Dynamic Social Card Generator', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_og_preview" value="1" <?php checked( get_option( 'samanlabs_seo_enable_og_preview', '1' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Generate dynamic PNG social card images on-the-fly for sharing. Note: This only controls dynamic image generation, not Open Graph meta tags.', 'saman-labs-seo' ); ?>
						</p>
					<?php if ( '1' === get_option( 'samanlabs_seo_enable_og_preview', '1' ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-types#social-cards' ) ); ?>" class="samanlabs-seo-module-link">
							<?php esc_html_e( 'Customize Social Cards →', 'saman-labs-seo' ); ?>
						</a>
					<?php endif; ?>
					</div>
				</div>

				<!-- LLM.txt Generator Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-media-code"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'LLM.txt Generator', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_llm_txt" value="1" <?php checked( get_option( 'samanlabs_seo_enable_llm_txt', '1' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Help AI engines discover your content with a standardized llm.txt file for better AI indexing.', 'saman-labs-seo' ); ?>
						</p>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-sitemap#llm' ) ); ?>" class="samanlabs-seo-module-link">
							<?php esc_html_e( 'Configure LLM.txt →', 'saman-labs-seo' ); ?>
						</a>
					</div>
				</div>

				<!-- Local SEO Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-location"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'Local SEO', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_local_seo" value="1" <?php checked( get_option( 'samanlabs_seo_enable_local_seo', '0' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Add Local Business schema markup with business info, opening hours, and location data for better local search visibility.', 'saman-labs-seo' ); ?>
						</p>
						<?php if ( '1' === get_option( 'samanlabs_seo_enable_local_seo', '0' ) ) : ?>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=samanlabs-seo-local-seo' ) ); ?>" class="samanlabs-seo-module-link">
								<?php esc_html_e( 'Configure Local SEO →', 'saman-labs-seo' ); ?>
							</a>
						<?php endif; ?>
					</div>
				</div>

				<!-- Analytics Module -->
				<div class="samanlabs-seo-module-card">
					<div class="samanlabs-seo-module-icon">
						<span class="dashicons dashicons-chart-bar"></span>
					</div>
					<div class="samanlabs-seo-module-content">
						<div class="samanlabs-seo-module-header">
							<h3><?php esc_html_e( 'Usage Analytics', 'saman-labs-seo' ); ?></h3>
							<label class="samanlabs-seo-toggle-switch">
								<input type="checkbox" name="samanlabs_seo_enable_analytics" value="1" <?php checked( get_option( 'samanlabs_seo_enable_analytics', '1' ), '1' ); ?> />
								<span class="samanlabs-seo-toggle-slider"></span>
							</label>
						</div>
						<p class="samanlabs-seo-module-description">
							<?php esc_html_e( 'Help improve WP SEO Pilot by sending anonymous usage data. No personal information or user data is collected - only plugin activation and feature usage.', 'saman-labs-seo' ); ?>
						</p>
					</div>
				</div>
			</div>
		</div>

		<div class="samanlabs-seo-tabs__actions">
			<?php submit_button( __( 'Save defaults', 'saman-labs-seo' ) ); ?>
		</div>
		</form>

		<div
			id="samanlabs-seo-tab-knowledge"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-knowledge"
		>
			<section class="samanlabs-seo-card">
				<h2><?php esc_html_e( 'Knowledge Graph & Schema.org', 'saman-labs-seo' ); ?></h2>
				<p><?php esc_html_e( 'Help search engines understand who runs this site. This data is used in Google’s Knowledge Graph and other rich results.', 'saman-labs-seo' ); ?></p>
					<form action="options.php" method="post">
						<?php settings_fields( 'samanlabs_seo_knowledge' ); ?>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Site represents', 'saman-labs-seo' ); ?></th>
							<td>
								<label>
									<input type="radio" name="samanlabs_seo_homepage_knowledge_type" value="organization" <?php checked( get_option( 'samanlabs_seo_homepage_knowledge_type', 'organization' ), 'organization' ); ?> />
									<?php esc_html_e( 'Organization', 'saman-labs-seo' ); ?>
								</label>
								<br />
								<label>
									<input type="radio" name="samanlabs_seo_homepage_knowledge_type" value="person" <?php checked( get_option( 'samanlabs_seo_homepage_knowledge_type', 'organization' ), 'person' ); ?> />
									<?php esc_html_e( 'Person', 'saman-labs-seo' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="samanlabs_seo_homepage_organization_name"><?php esc_html_e( 'Organization name', 'saman-labs-seo' ); ?></label>
							</th>
							<td>
								<input type="text" class="regular-text" id="samanlabs_seo_homepage_organization_name" name="samanlabs_seo_homepage_organization_name" value="<?php echo esc_attr( get_option( 'samanlabs_seo_homepage_organization_name' ) ); ?>" />
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="samanlabs_seo_homepage_organization_logo"><?php esc_html_e( 'Organization logo', 'saman-labs-seo' ); ?></label>
							</th>
							<td>
								<div class="samanlabs-seo-media-field">
									<input type="url" class="regular-text" id="samanlabs_seo_homepage_organization_logo" name="samanlabs_seo_homepage_organization_logo" value="<?php echo esc_url( get_option( 'samanlabs_seo_homepage_organization_logo' ) ); ?>" />
									<button type="button" class="button samanlabs-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-labs-seo' ); ?></button>
								</div>
								<p class="description"><?php esc_html_e( 'Recommended: square logo at least 112×112 px. Used in structured data and social previews.', 'saman-labs-seo' ); ?></p>
							</td>
						</tr>
					</table>
					<?php submit_button( __( 'Save Knowledge Graph settings', 'saman-labs-seo' ) ); ?>
				</form>
			</section>
		</div>

		<div
			id="samanlabs-seo-tab-export"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-export"
		>
			<section class="samanlabs-seo-card">
				<h2><?php esc_html_e( 'Export / Backup', 'saman-labs-seo' ); ?></h2>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<?php wp_nonce_field( 'samanlabs_seo_export' ); ?>
					<input type="hidden" name="action" value="samanlabs_seo_export" />
					<?php submit_button( __( 'Download JSON', 'saman-labs-seo' ), 'secondary', 'submit', false ); ?>
				</form>
			</section>
		</div>
	</div>
</div>
