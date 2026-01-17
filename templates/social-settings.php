<?php
/**
 * Social Settings Page Template
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

// Schema itemtype options
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
\Saman\SEO\Admin_Topbar::render( 'social' );
?>

<div class="wrap saman-seo-page saman-seo-social-page">
	<form action="options.php" method="post">
		<?php settings_fields( 'SAMAN_SEO_social' ); ?>

		<div class="saman-seo-settings-grid">
			<!-- Main Settings Column -->
			<div class="saman-seo-settings-main">

				<!-- Global Social Defaults Card -->
				<div class="saman-seo-card">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Global Social Defaults', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Set default Open Graph, Twitter, and schema values that will be used when posts don\'t have custom values.', 'saman-seo' ); ?></p>
					</div>
					<div class="saman-seo-card-body">

						<!-- Open Graph -->
						<div class="saman-seo-form-section">
							<h3><?php esc_html_e( 'Open Graph (Facebook)', 'saman-seo' ); ?></h3>
							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_og_title"><?php esc_html_e( 'Fallback Title', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<input type="text" class="regular-text" id="SAMAN_SEO_social_defaults_og_title" name="SAMAN_SEO_social_defaults[og_title]" value="<?php echo esc_attr( $social_defaults['og_title'] ); ?>" />
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Default title for Facebook shares', 'saman-seo' ); ?></span>
								</div>
							</div>

							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_og_description"><?php esc_html_e( 'Fallback Description', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<textarea class="large-text" rows="3" id="SAMAN_SEO_social_defaults_og_description" name="SAMAN_SEO_social_defaults[og_description]"><?php echo esc_textarea( $social_defaults['og_description'] ); ?></textarea>
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Default description for Facebook shares', 'saman-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Twitter -->
						<div class="saman-seo-form-section">
							<h3><?php esc_html_e( 'Twitter Card', 'saman-seo' ); ?></h3>
							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_twitter_title"><?php esc_html_e( 'Fallback Title', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<input type="text" class="regular-text" id="SAMAN_SEO_social_defaults_twitter_title" name="SAMAN_SEO_social_defaults[twitter_title]" value="<?php echo esc_attr( $social_defaults['twitter_title'] ); ?>" />
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Default title for Twitter shares', 'saman-seo' ); ?></span>
								</div>
							</div>

							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_twitter_description"><?php esc_html_e( 'Fallback Description', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<textarea class="large-text" rows="3" id="SAMAN_SEO_social_defaults_twitter_description" name="SAMAN_SEO_social_defaults[twitter_description]"><?php echo esc_textarea( $social_defaults['twitter_description'] ); ?></textarea>
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Default description for Twitter shares', 'saman-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Image Source -->
						<div class="saman-seo-form-section">
							<h3><?php esc_html_e( 'Social Image', 'saman-seo' ); ?></h3>
							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_image_source"><?php esc_html_e( 'Fallback Image URL', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<input type="url" class="regular-text" id="SAMAN_SEO_social_defaults_image_source" name="SAMAN_SEO_social_defaults[image_source]" value="<?php echo esc_url( $social_defaults['image_source'] ); ?>" />
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Used when posts don\'t have a featured image (1200x630px recommended)', 'saman-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Schema -->
						<div class="saman-seo-form-section">
							<h3><?php esc_html_e( 'Schema.org Type', 'saman-seo' ); ?></h3>
							<div class="saman-seo-form-row">
								<div class="saman-seo-form-label">
									<label for="SAMAN_SEO_social_defaults_schema_itemtype"><?php esc_html_e( 'Default Schema Type', 'saman-seo' ); ?></label>
								</div>
								<div class="saman-seo-form-control">
									<?php
									$render_schema_control(
										'SAMAN_SEO_social_defaults[schema_itemtype]',
										$social_defaults['schema_itemtype'],
										'SAMAN_SEO_social_defaults_schema_itemtype'
									);
									?>
									<span class="saman-seo-helper-text"><?php esc_html_e( 'Controls the og:type meta tag for content without specific overrides', 'saman-seo' ); ?></span>
								</div>
							</div>
						</div>

					</div>
				</div>

				<!-- Per Post Type Overrides Card -->
				<?php if ( ! empty( $post_types ) ) : ?>
				<div class="saman-seo-card">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Post Type Specific Settings', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Override default social settings for specific post types. Leave blank to inherit global defaults.', 'saman-seo' ); ?></p>
					</div>
					<div class="saman-seo-card-body saman-seo-card-body--no-padding">
						<?php foreach ( $post_types as $slug => $object ) : ?>
							<?php
							$label = $object->labels->name ?: $object->label ?: ucfirst( $slug );
							$raw_values = isset( $post_type_social_defaults[ $slug ] ) ? (array) $post_type_social_defaults[ $slug ] : [];
							$values = wp_parse_args( $raw_values, $social_field_defaults );
							?>
							<details class="saman-seo-accordion">
								<summary>
									<span class="saman-seo-accordion-title"><?php echo esc_html( $label ); ?></span>
									<span class="saman-seo-accordion-badge"><?php echo esc_html( $slug ); ?></span>
								</summary>
								<div class="saman-seo-accordion__body">

									<!-- Open Graph -->
									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_og_title"><?php esc_html_e( 'OG Title', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<input type="text" class="regular-text" id="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_og_title" name="SAMAN_SEO_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_title]" value="<?php echo esc_attr( $values['og_title'] ); ?>" />
										</div>
									</div>

									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_og_description"><?php esc_html_e( 'OG Description', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<textarea class="large-text" rows="2" id="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_og_description" name="SAMAN_SEO_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_description]"><?php echo esc_textarea( $values['og_description'] ); ?></textarea>
										</div>
									</div>

									<!-- Twitter -->
									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_twitter_title"><?php esc_html_e( 'Twitter Title', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<input type="text" class="regular-text" id="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_twitter_title" name="SAMAN_SEO_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_title]" value="<?php echo esc_attr( $values['twitter_title'] ); ?>" />
										</div>
									</div>

									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_twitter_description"><?php esc_html_e( 'Twitter Description', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<textarea class="large-text" rows="2" id="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_twitter_description" name="SAMAN_SEO_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_description]"><?php echo esc_textarea( $values['twitter_description'] ); ?></textarea>
										</div>
									</div>

									<!-- Image and Schema -->
									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_image_source"><?php esc_html_e( 'Image URL', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<input type="url" class="regular-text" id="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_image_source" name="SAMAN_SEO_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][image_source]" value="<?php echo esc_url( $values['image_source'] ); ?>" />
										</div>
									</div>

									<div class="saman-seo-form-row">
										<div class="saman-seo-form-label">
											<label for="SAMAN_SEO_social_<?php echo esc_attr( $slug ); ?>_schema_itemtype"><?php esc_html_e( 'Schema Type', 'saman-seo' ); ?></label>
										</div>
										<div class="saman-seo-form-control">
											<?php
											$render_schema_control(
												"SAMAN_SEO_post_type_social_defaults[{$slug}][schema_itemtype]",
												$values['schema_itemtype'],
												"SAMAN_SEO_social_{$slug}_schema_itemtype"
											);
											?>
										</div>
									</div>

								</div>
							</details>
						<?php endforeach; ?>
					</div>
				</div>
				<?php endif; ?>

			</div>

			<!-- Sidebar Column -->
			<div class="saman-seo-settings-sidebar">

				<!-- Info Card -->
				<div class="saman-seo-card saman-seo-card--info">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'About Social Settings', 'saman-seo' ); ?></h2>
					</div>
					<div class="saman-seo-card-body">
						<p><?php esc_html_e( 'Social settings control how your content appears when shared on social media platforms like Facebook and Twitter.', 'saman-seo' ); ?></p>
						<ul class="saman-seo-info-list">
							<li><?php esc_html_e( 'Global defaults apply site-wide', 'saman-seo' ); ?></li>
							<li><?php esc_html_e( 'Post type settings override globals', 'saman-seo' ); ?></li>
							<li><?php esc_html_e( 'Individual post meta takes highest priority', 'saman-seo' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Best Practices Card -->
				<div class="saman-seo-card saman-seo-card--info">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Best Practices', 'saman-seo' ); ?></h2>
					</div>
					<div class="saman-seo-card-body">
						<ul class="saman-seo-info-list">
							<li><strong><?php esc_html_e( 'Image Size:', 'saman-seo' ); ?></strong> <?php esc_html_e( '1200Ãƒâ€”630px for best results', 'saman-seo' ); ?></li>
							<li><strong><?php esc_html_e( 'Title Length:', 'saman-seo' ); ?></strong> <?php esc_html_e( '60 characters or less', 'saman-seo' ); ?></li>
							<li><strong><?php esc_html_e( 'Description:', 'saman-seo' ); ?></strong> <?php esc_html_e( '155 characters or less', 'saman-seo' ); ?></li>
						</ul>
					</div>
				</div>

			</div>
		</div>

		<!-- Save Button -->
		<div class="saman-seo-settings-footer">
			<?php submit_button( __( 'Save Social Settings', 'saman-seo' ), 'primary', 'submit', false ); ?>
		</div>

	</form>
</div>
