<?php
/**
 * Social Settings Tab Content
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Get social defaults
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

// Get post type social defaults
$post_type_social_defaults = get_option( 'wpseopilot_post_type_social_defaults', [] );
if ( ! is_array( $post_type_social_defaults ) ) {
	$post_type_social_defaults = [];
}

// Get post types
$post_types_for_social = get_post_types(
	[
		'public'  => true,
		'show_ui' => true,
	],
	'objects'
);

if ( isset( $post_types_for_social['attachment'] ) ) {
	unset( $post_types_for_social['attachment'] );
}

// Schema options
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

<div class="wpseopilot-settings-grid">
	<div class="wpseopilot-settings-main">

		<!-- Global Social Defaults Card -->
		<div class="wpseopilot-card">
			<div class="wpseopilot-card-header">
				<h2><?php esc_html_e( 'Global Social Defaults', 'wp-seo-pilot' ); ?></h2>
				<p><?php esc_html_e( 'Set default Open Graph, Twitter, and schema values that will be used when posts don\'t have custom values.', 'wp-seo-pilot' ); ?></p>
			</div>
			<div class="wpseopilot-card-body">

				<!-- Open Graph -->
				<div class="wpseopilot-form-section">
					<h3><?php esc_html_e( 'Open Graph (Facebook)', 'wp-seo-pilot' ); ?></h3>
					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_og_title"><?php esc_html_e( 'Fallback Title', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<input type="text" class="regular-text" id="wpseopilot_social_defaults_og_title" name="wpseopilot_social_defaults[og_title]" value="<?php echo esc_attr( $social_defaults['og_title'] ); ?>" />
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Default title for Facebook shares', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>

					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_og_description"><?php esc_html_e( 'Fallback Description', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<textarea class="large-text" rows="3" id="wpseopilot_social_defaults_og_description" name="wpseopilot_social_defaults[og_description]"><?php echo esc_textarea( $social_defaults['og_description'] ); ?></textarea>
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Default description for Facebook shares', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Twitter -->
				<div class="wpseopilot-form-section">
					<h3><?php esc_html_e( 'Twitter Card', 'wp-seo-pilot' ); ?></h3>
					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_twitter_title"><?php esc_html_e( 'Fallback Title', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<input type="text" class="regular-text" id="wpseopilot_social_defaults_twitter_title" name="wpseopilot_social_defaults[twitter_title]" value="<?php echo esc_attr( $social_defaults['twitter_title'] ); ?>" />
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Default title for Twitter shares', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>

					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_twitter_description"><?php esc_html_e( 'Fallback Description', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<textarea class="large-text" rows="3" id="wpseopilot_social_defaults_twitter_description" name="wpseopilot_social_defaults[twitter_description]"><?php echo esc_textarea( $social_defaults['twitter_description'] ); ?></textarea>
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Default description for Twitter shares', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Image Source -->
				<div class="wpseopilot-form-section">
					<h3><?php esc_html_e( 'Social Image', 'wp-seo-pilot' ); ?></h3>
					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_image_source"><?php esc_html_e( 'Fallback Image URL', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<input type="url" class="regular-text" id="wpseopilot_social_defaults_image_source" name="wpseopilot_social_defaults[image_source]" value="<?php echo esc_url( $social_defaults['image_source'] ); ?>" />
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Used when posts don\'t have a featured image (1200x630px recommended)', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Schema -->
				<div class="wpseopilot-form-section">
					<h3><?php esc_html_e( 'Schema.org Type', 'wp-seo-pilot' ); ?></h3>
					<div class="wpseopilot-form-row">
						<div class="wpseopilot-form-label">
							<label for="wpseopilot_social_defaults_schema_itemtype"><?php esc_html_e( 'Default Schema Type', 'wp-seo-pilot' ); ?></label>
						</div>
						<div class="wpseopilot-form-control">
							<?php
							$render_schema_control(
								'wpseopilot_social_defaults[schema_itemtype]',
								$social_defaults['schema_itemtype'],
								'wpseopilot_social_defaults_schema_itemtype'
							);
							?>
							<span class="wpseopilot-helper-text"><?php esc_html_e( 'Controls the og:type meta tag for content without specific overrides', 'wp-seo-pilot' ); ?></span>
						</div>
					</div>
				</div>

			</div>
		</div>

		<!-- Per Post Type Overrides Card -->
		<?php if ( ! empty( $post_types_for_social ) ) : ?>
		<div class="wpseopilot-card">
			<div class="wpseopilot-card-header">
				<h2><?php esc_html_e( 'Post Type Specific Settings', 'wp-seo-pilot' ); ?></h2>
				<p><?php esc_html_e( 'Override default social settings for specific post types. Leave blank to inherit global defaults.', 'wp-seo-pilot' ); ?></p>
			</div>
			<div class="wpseopilot-card-body wpseopilot-card-body--no-padding">
				<?php foreach ( $post_types_for_social as $slug => $object ) : ?>
					<?php
					$label = $object->labels->name ?: $object->label ?: ucfirst( $slug );
					$raw_values = isset( $post_type_social_defaults[ $slug ] ) ? (array) $post_type_social_defaults[ $slug ] : [];
					$values = wp_parse_args( $raw_values, $social_field_defaults );
					?>
					<details class="wpseopilot-accordion">
						<summary>
							<span class="wpseopilot-accordion-title"><?php echo esc_html( $label ); ?></span>
							<span class="wpseopilot-accordion-badge"><?php echo esc_html( $slug ); ?></span>
						</summary>
						<div class="wpseopilot-accordion__body">

							<!-- Open Graph -->
							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_title"><?php esc_html_e( 'OG Title', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<input type="text" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_title" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_title]" value="<?php echo esc_attr( $values['og_title'] ); ?>" />
								</div>
							</div>

							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_description"><?php esc_html_e( 'OG Description', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<textarea class="large-text" rows="2" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_og_description" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][og_description]"><?php echo esc_textarea( $values['og_description'] ); ?></textarea>
								</div>
							</div>

							<!-- Twitter -->
							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_title"><?php esc_html_e( 'Twitter Title', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<input type="text" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_title" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_title]" value="<?php echo esc_attr( $values['twitter_title'] ); ?>" />
								</div>
							</div>

							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_description"><?php esc_html_e( 'Twitter Description', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<textarea class="large-text" rows="2" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_twitter_description" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][twitter_description]"><?php echo esc_textarea( $values['twitter_description'] ); ?></textarea>
								</div>
							</div>

							<!-- Image and Schema -->
							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_image_source"><?php esc_html_e( 'Image URL', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<input type="url" class="regular-text" id="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_image_source" name="wpseopilot_post_type_social_defaults[<?php echo esc_attr( $slug ); ?>][image_source]" value="<?php echo esc_url( $values['image_source'] ); ?>" />
								</div>
							</div>

							<div class="wpseopilot-form-row">
								<div class="wpseopilot-form-label">
									<label for="wpseopilot_social_<?php echo esc_attr( $slug ); ?>_schema_itemtype"><?php esc_html_e( 'Schema Type', 'wp-seo-pilot' ); ?></label>
								</div>
								<div class="wpseopilot-form-control">
									<?php
									$render_schema_control(
										"wpseopilot_post_type_social_defaults[{$slug}][schema_itemtype]",
										$values['schema_itemtype'],
										"wpseopilot_social_{$slug}_schema_itemtype"
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
	<div class="wpseopilot-settings-sidebar">

		<!-- Info Card -->
		<div class="wpseopilot-card wpseopilot-card--info">
			<div class="wpseopilot-card-header">
				<h2><?php esc_html_e( 'About Social Settings', 'wp-seo-pilot' ); ?></h2>
			</div>
			<div class="wpseopilot-card-body">
				<p><?php esc_html_e( 'Social settings control how your content appears when shared on social media platforms like Facebook and Twitter.', 'wp-seo-pilot' ); ?></p>
				<ul class="wpseopilot-info-list">
					<li><?php esc_html_e( 'Global defaults apply site-wide', 'wp-seo-pilot' ); ?></li>
					<li><?php esc_html_e( 'Post type settings override globals', 'wp-seo-pilot' ); ?></li>
					<li><?php esc_html_e( 'Individual post meta takes highest priority', 'wp-seo-pilot' ); ?></li>
				</ul>
			</div>
		</div>

		<!-- Best Practices Card -->
		<div class="wpseopilot-card wpseopilot-card--info">
			<div class="wpseopilot-card-header">
				<h2><?php esc_html_e( 'Best Practices', 'wp-seo-pilot' ); ?></h2>
			</div>
			<div class="wpseopilot-card-body">
				<ul class="wpseopilot-info-list">
					<li><strong><?php esc_html_e( 'Image Size:', 'wp-seo-pilot' ); ?></strong> <?php esc_html_e( '1200×630px for best results', 'wp-seo-pilot' ); ?></li>
					<li><strong><?php esc_html_e( 'Title Length:', 'wp-seo-pilot' ); ?></strong> <?php esc_html_e( '60 characters or less', 'wp-seo-pilot' ); ?></li>
					<li><strong><?php esc_html_e( 'Description:', 'wp-seo-pilot' ); ?></strong> <?php esc_html_e( '155 characters or less', 'wp-seo-pilot' ); ?></li>
				</ul>
			</div>
		</div>

	</div>
</div>
