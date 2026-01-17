<?php
/**
 * Social Settings Page Template
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Schema itemtype options
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
\WPSEOPilot\Admin_Topbar::render( 'social' );
?>

<div class="wrap samanlabs-seo-page samanlabs-seo-social-page">
	<form action="options.php" method="post">
		<?php settings_fields( 'samanlabs_seo_social' ); ?>

		<div class="samanlabs-seo-settings-grid">
			<!-- Main Settings Column -->
			<div class="samanlabs-seo-settings-main">

				<!-- Global Social Defaults Card -->
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Global Social Defaults', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Set default Open Graph, Twitter, and schema values that will be used when posts don\'t have custom values.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<!-- Open Graph -->
						<div class="samanlabs-seo-form-section">
							<h3><?php esc_html_e( 'Open Graph (Facebook)', 'saman-labs-seo' ); ?></h3>
							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_og_title"><?php esc_html_e( 'Fallback Title', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<input type="text" class="regular-text" id="samanlabs_seo_social_defaults_og_title" name="samanlabs_seo_social_defaults[og_title]" value="<?php echo esc_attr( $social_defaults['og_title'] ); ?>" />
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Default title for Facebook shares', 'saman-labs-seo' ); ?></span>
								</div>
							</div>

							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_og_description"><?php esc_html_e( 'Fallback Description', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<textarea class="large-text" rows="3" id="samanlabs_seo_social_defaults_og_description" name="samanlabs_seo_social_defaults[og_description]"><?php echo esc_textarea( $social_defaults['og_description'] ); ?></textarea>
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Default description for Facebook shares', 'saman-labs-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Twitter -->
						<div class="samanlabs-seo-form-section">
							<h3><?php esc_html_e( 'Twitter Card', 'saman-labs-seo' ); ?></h3>
							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_twitter_title"><?php esc_html_e( 'Fallback Title', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<input type="text" class="regular-text" id="samanlabs_seo_social_defaults_twitter_title" name="samanlabs_seo_social_defaults[twitter_title]" value="<?php echo esc_attr( $social_defaults['twitter_title'] ); ?>" />
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Default title for Twitter shares', 'saman-labs-seo' ); ?></span>
								</div>
							</div>

							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_twitter_description"><?php esc_html_e( 'Fallback Description', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<textarea class="large-text" rows="3" id="samanlabs_seo_social_defaults_twitter_description" name="samanlabs_seo_social_defaults[twitter_description]"><?php echo esc_textarea( $social_defaults['twitter_description'] ); ?></textarea>
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Default description for Twitter shares', 'saman-labs-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Image Source -->
						<div class="samanlabs-seo-form-section">
							<h3><?php esc_html_e( 'Social Image', 'saman-labs-seo' ); ?></h3>
							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_image_source"><?php esc_html_e( 'Fallback Image URL', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<input type="url" class="regular-text" id="samanlabs_seo_social_defaults_image_source" name="samanlabs_seo_social_defaults[image_source]" value="<?php echo esc_url( $social_defaults['image_source'] ); ?>" />
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Used when posts don\'t have a featured image (1200x630px recommended)', 'saman-labs-seo' ); ?></span>
								</div>
							</div>
						</div>

						<!-- Schema -->
						<div class="samanlabs-seo-form-section">
							<h3><?php esc_html_e( 'Schema.org Type', 'saman-labs-seo' ); ?></h3>
							<div class="samanlabs-seo-form-row">
								<div class="samanlabs-seo-form-label">
									<label for="samanlabs_seo_social_defaults_schema_itemtype"><?php esc_html_e( 'Default Schema Type', 'saman-labs-seo' ); ?></label>
								</div>
								<div class="samanlabs-seo-form-control">
									<?php
									$render_schema_control(
										'samanlabs_seo_social_defaults[schema_itemtype]',
										$social_defaults['schema_itemtype'],
										'samanlabs_seo_social_defaults_schema_itemtype'
									);
									?>
									<span class="samanlabs-seo-helper-text"><?php esc_html_e( 'Controls the og:type meta tag for content without specific overrides', 'saman-labs-seo' ); ?></span>
								</div>
							</div>
						</div>

					</div>
				</div>

				<!-- Per Post Type Overrides Card -->
				<?php if ( ! empty( $post_types ) ) : ?>
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Post Type Specific Settings', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Override default social settings for specific post types. Leave blank to inherit global defaults.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body samanlabs-seo-card-body--no-padding">
						<?php foreach ( $post_types as $slug => $object ) : ?>
							<?php
							$label = $object->labels->name ?: $object->label ?: ucfirst( $slug );
							$raw_values = isset( $post_type_social_defaults[ $slug ] ) ? (array) $post_type_social_defaults[ $slug ] : [];
							$values = wp_parse_args( $raw_values, $social_field_defaults );
							?>
							<details class="samanlabs-seo-accordion">
								<summary>
									<span class="samanlabs-seo-accordion-title"><?php echo esc_html( $label ); ?></span>
									<span class="samanlabs-seo-accordion-badge"><?php echo esc_html( $slug ); ?></span>
								</summary>
								<div class="samanlabs-seo-accordion__body">

									<!-- Open Graph -->
									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_og_title"><?php esc_html_e( 'OG Title', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<input type="text" class="regular-text" id="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_og_title" name="samanlabs_seo_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_title]" value="<?php echo esc_attr( $values['og_title'] ); ?>" />
										</div>
									</div>

									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_og_description"><?php esc_html_e( 'OG Description', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<textarea class="large-text" rows="2" id="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_og_description" name="samanlabs_seo_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_description]"><?php echo esc_textarea( $values['og_description'] ); ?></textarea>
										</div>
									</div>

									<!-- Twitter -->
									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_twitter_title"><?php esc_html_e( 'Twitter Title', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<input type="text" class="regular-text" id="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_twitter_title" name="samanlabs_seo_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_title]" value="<?php echo esc_attr( $values['twitter_title'] ); ?>" />
										</div>
									</div>

									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_twitter_description"><?php esc_html_e( 'Twitter Description', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<textarea class="large-text" rows="2" id="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_twitter_description" name="samanlabs_seo_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_description]"><?php echo esc_textarea( $values['twitter_description'] ); ?></textarea>
										</div>
									</div>

									<!-- Image and Schema -->
									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_image_source"><?php esc_html_e( 'Image URL', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<input type="url" class="regular-text" id="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_image_source" name="samanlabs_seo_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][image_source]" value="<?php echo esc_url( $values['image_source'] ); ?>" />
										</div>
									</div>

									<div class="samanlabs-seo-form-row">
										<div class="samanlabs-seo-form-label">
											<label for="samanlabs_seo_social_<?php echo esc_attr( $slug ); ?>_schema_itemtype"><?php esc_html_e( 'Schema Type', 'saman-labs-seo' ); ?></label>
										</div>
										<div class="samanlabs-seo-form-control">
											<?php
											$render_schema_control(
												"samanlabs_seo_post_type_social_defaults[{$slug}][schema_itemtype]",
												$values['schema_itemtype'],
												"samanlabs_seo_social_{$slug}_schema_itemtype"
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
			<div class="samanlabs-seo-settings-sidebar">

				<!-- Info Card -->
				<div class="samanlabs-seo-card samanlabs-seo-card--info">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'About Social Settings', 'saman-labs-seo' ); ?></h2>
					</div>
					<div class="samanlabs-seo-card-body">
						<p><?php esc_html_e( 'Social settings control how your content appears when shared on social media platforms like Facebook and Twitter.', 'saman-labs-seo' ); ?></p>
						<ul class="samanlabs-seo-info-list">
							<li><?php esc_html_e( 'Global defaults apply site-wide', 'saman-labs-seo' ); ?></li>
							<li><?php esc_html_e( 'Post type settings override globals', 'saman-labs-seo' ); ?></li>
							<li><?php esc_html_e( 'Individual post meta takes highest priority', 'saman-labs-seo' ); ?></li>
						</ul>
					</div>
				</div>

				<!-- Best Practices Card -->
				<div class="samanlabs-seo-card samanlabs-seo-card--info">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Best Practices', 'saman-labs-seo' ); ?></h2>
					</div>
					<div class="samanlabs-seo-card-body">
						<ul class="samanlabs-seo-info-list">
							<li><strong><?php esc_html_e( 'Image Size:', 'saman-labs-seo' ); ?></strong> <?php esc_html_e( '1200×630px for best results', 'saman-labs-seo' ); ?></li>
							<li><strong><?php esc_html_e( 'Title Length:', 'saman-labs-seo' ); ?></strong> <?php esc_html_e( '60 characters or less', 'saman-labs-seo' ); ?></li>
							<li><strong><?php esc_html_e( 'Description:', 'saman-labs-seo' ); ?></strong> <?php esc_html_e( '155 characters or less', 'saman-labs-seo' ); ?></li>
						</ul>
					</div>
				</div>

			</div>
		</div>

		<!-- Save Button -->
		<div class="samanlabs-seo-settings-footer">
			<?php submit_button( __( 'Save Social Settings', 'saman-labs-seo' ), 'primary', 'submit', false ); ?>
		</div>

	</form>
</div>
