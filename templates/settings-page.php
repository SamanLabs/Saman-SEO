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
?>
<div class="wrap wpseopilot-settings">
	<h1><?php esc_html_e( 'WP SEO Pilot — SEO Defaults', 'wp-seo-pilot' ); ?></h1>

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
				id="wpseopilot-tab-link-social"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-social"
				data-wpseopilot-tab="wpseopilot-tab-social"
			>
				<?php esc_html_e( 'Social', 'wp-seo-pilot' ); ?>
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
		</div>

		<div class="wpseopilot-tabs__actions">
			<?php submit_button( __( 'Save SEO defaults', 'wp-seo-pilot' ) ); ?>
		</div>
		</form>

		<div
			id="wpseopilot-tab-social"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-social"
		>
			<form action="options.php" method="post" class="wpseopilot-settings__form">
				<?php settings_fields( 'wpseopilot_social' ); ?>
				<section class="wpseopilot-card">
					<h2><?php esc_html_e( 'Social fallbacks', 'wp-seo-pilot' ); ?></h2>
					<p><?php esc_html_e( 'Provide default Open Graph, Twitter, and schema values that WP SEO Pilot will use whenever editors leave fields blank.', 'wp-seo-pilot' ); ?></p>
					<table class="form-table" role="presentation">
						<tr>
							<th scope="row"><?php esc_html_e( 'Open Graph (Facebook)', 'wp-seo-pilot' ); ?></th>
							<td>
								<div class="wpseopilot-social-fields">
									<div class="wpseopilot-field">
										<label for="wpseopilot_social_defaults_og_title"><?php esc_html_e( 'Fallback title', 'wp-seo-pilot' ); ?></label>
										<input type="text" class="regular-text" id="wpseopilot_social_defaults_og_title" name="wpseopilot_social_defaults[og_title]" value="<?php echo esc_attr( $social_defaults['og_title'] ); ?>" />
									</div>
									<div class="wpseopilot-field">
										<label for="wpseopilot_social_defaults_og_description"><?php esc_html_e( 'Fallback description', 'wp-seo-pilot' ); ?></label>
										<textarea class="large-text" rows="3" id="wpseopilot_social_defaults_og_description" name="wpseopilot_social_defaults[og_description]"><?php echo esc_textarea( $social_defaults['og_description'] ); ?></textarea>
									</div>
								</div>
								<?php if ( empty( $social_defaults['og_title'] ) && empty( $social_defaults['og_description'] ) ) : ?>
									<p class="wpseopilot-social-status is-empty"><?php esc_html_e( 'No DATA has been found for OPEN GRAPH (Facebook).', 'wp-seo-pilot' ); ?></p>
								<?php else : ?>
									<p class="wpseopilot-social-status"><?php esc_html_e( 'Used whenever a post is missing a custom social title or description.', 'wp-seo-pilot' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Twitter', 'wp-seo-pilot' ); ?></th>
							<td>
								<div class="wpseopilot-social-fields">
									<div class="wpseopilot-field">
										<label for="wpseopilot_social_defaults_twitter_title"><?php esc_html_e( 'Fallback title', 'wp-seo-pilot' ); ?></label>
										<input type="text" class="regular-text" id="wpseopilot_social_defaults_twitter_title" name="wpseopilot_social_defaults[twitter_title]" value="<?php echo esc_attr( $social_defaults['twitter_title'] ); ?>" />
									</div>
									<div class="wpseopilot-field">
										<label for="wpseopilot_social_defaults_twitter_description"><?php esc_html_e( 'Fallback description', 'wp-seo-pilot' ); ?></label>
										<textarea class="large-text" rows="3" id="wpseopilot_social_defaults_twitter_description" name="wpseopilot_social_defaults[twitter_description]"><?php echo esc_textarea( $social_defaults['twitter_description'] ); ?></textarea>
									</div>
								</div>
								<?php if ( empty( $social_defaults['twitter_title'] ) && empty( $social_defaults['twitter_description'] ) ) : ?>
									<p class="wpseopilot-social-status is-empty"><?php esc_html_e( 'No DATA has been found for TWITTER.', 'wp-seo-pilot' ); ?></p>
								<?php else : ?>
									<p class="wpseopilot-social-status"><?php esc_html_e( 'Overrides the Twitter Card when a post does not provide its own.', 'wp-seo-pilot' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Image source', 'wp-seo-pilot' ); ?></th>
							<td>
								<input type="url" class="regular-text" id="wpseopilot_social_defaults_image_source" name="wpseopilot_social_defaults[image_source]" value="<?php echo esc_url( $social_defaults['image_source'] ); ?>" />
								<p class="description"><?php esc_html_e( 'Optional fallback image URL for Open Graph and Twitter cards.', 'wp-seo-pilot' ); ?></p>
								<?php if ( empty( $social_defaults['image_source'] ) ) : ?>
									<p class="wpseopilot-social-status is-empty"><?php esc_html_e( 'No IMAGE_SRC has been found.', 'wp-seo-pilot' ); ?></p>
								<?php else : ?>
									<p class="wpseopilot-social-status"><?php esc_html_e( 'Used whenever a post is missing a featured image or override.', 'wp-seo-pilot' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Schema.org (itemtype only)', 'wp-seo-pilot' ); ?></th>
							<td>
								<?php
								$render_schema_control(
									'wpseopilot_social_defaults[schema_itemtype]',
									$social_defaults['schema_itemtype'],
									'wpseopilot_social_defaults_schema_itemtype'
								);
								?>
								<p class="description"><?php esc_html_e( 'Example: Article, Product, ProfilePage. Leave blank to keep the default article type.', 'wp-seo-pilot' ); ?></p>
								<?php if ( empty( $social_defaults['schema_itemtype'] ) ) : ?>
									<p class="wpseopilot-social-status is-empty"><?php esc_html_e( 'No DATA has been found for Schema.org (itemtype only).', 'wp-seo-pilot' ); ?></p>
								<?php else : ?>
									<p class="wpseopilot-social-status"><?php esc_html_e( 'Controls the og:type meta tag whenever content lacks a specific schema override.', 'wp-seo-pilot' ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					</table>
				</section>

				<?php if ( ! empty( $post_types ) ) : ?>
				<section class="wpseopilot-card">
					<h2><?php esc_html_e( 'Per post type overrides', 'wp-seo-pilot' ); ?></h2>
					<p><?php esc_html_e( 'Add tailored defaults for specific post or page types. Blank fields inherit the global fallbacks above.', 'wp-seo-pilot' ); ?></p>
					<?php foreach ( $post_types as $slug => $object ) : ?>
						<?php
						$label = $object->labels->name ?: $object->label ?: ucfirst( $slug );
						$raw_values = isset( $post_type_social_defaults[ $slug ] ) ? (array) $post_type_social_defaults[ $slug ] : [];
						$values = wp_parse_args( $raw_values, $social_field_defaults );
						$has_overrides = ! empty( $raw_values );
						?>
						<details class="wpseopilot-accordion">
							<summary>
								<span><?php echo esc_html( $label ); ?></span>
								<span class="wpseopilot-type-slug"><?php echo esc_html( $slug ); ?></span>
							</summary>
							<div class="wpseopilot-accordion__body">
								<table class="form-table" role="presentation">
									<tr>
										<th scope="row"><?php esc_html_e( 'Open Graph (Facebook)', 'wp-seo-pilot' ); ?></th>
										<td>
											<div class="wpseopilot-social-fields">
												<div class="wpseopilot-field">
													<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_title"><?php esc_html_e( 'Fallback title', 'wp-seo-pilot' ); ?></label>
													<input type="text" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_title" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_title]" value="<?php echo esc_attr( $values['og_title'] ); ?>" />
												</div>
												<div class="wpseopilot-field">
													<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_description"><?php esc_html_e( 'Fallback description', 'wp-seo-pilot' ); ?></label>
													<textarea class="large-text" rows="3" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_description" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_description]"><?php echo esc_textarea( $values['og_description'] ); ?></textarea>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Twitter', 'wp-seo-pilot' ); ?></th>
										<td>
											<div class="wpseopilot-social-fields">
												<div class="wpseopilot-field">
													<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_title"><?php esc_html_e( 'Fallback title', 'wp-seo-pilot' ); ?></label>
													<input type="text" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_title" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_title]" value="<?php echo esc_attr( $values['twitter_title'] ); ?>" />
												</div>
												<div class="wpseopilot-field">
													<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_description"><?php esc_html_e( 'Fallback description', 'wp-seo-pilot' ); ?></label>
													<textarea class="large-text" rows="3" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_description" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_description]"><?php echo esc_textarea( $values['twitter_description'] ); ?></textarea>
												</div>
											</div>
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Image source', 'wp-seo-pilot' ); ?></th>
										<td>
											<input type="url" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_image_source" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][image_source]" value="<?php echo esc_url( $values['image_source'] ); ?>" />
										</td>
									</tr>
									<tr>
										<th scope="row"><?php esc_html_e( 'Schema.org (itemtype only)', 'wp-seo-pilot' ); ?></th>
										<td>
											<?php
											$render_schema_control(
												sprintf( 'wpseopilot_post_type_social_defaults[%s][schema_itemtype]', $slug ),
												$values['schema_itemtype'],
												sprintf( 'wpseopilot_social_%s_schema_itemtype', $slug )
											);
											?>
										</td>
									</tr>
								</table>
								<?php if ( ! $has_overrides ) : ?>
									<p class="wpseopilot-social-status is-empty"><?php esc_html_e( 'No custom social data has been defined for this type yet.', 'wp-seo-pilot' ); ?></p>
								<?php else : ?>
									<p class="wpseopilot-social-status"><?php esc_html_e( 'These overrides take precedence over the global fallbacks.', 'wp-seo-pilot' ); ?></p>
								<?php endif; ?>
							</div>
						</details>
					<?php endforeach; ?>
				</section>
				<?php endif; ?>

				<?php submit_button( __( 'Save social defaults', 'wp-seo-pilot' ) ); ?>
			</form>
		</div>

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
