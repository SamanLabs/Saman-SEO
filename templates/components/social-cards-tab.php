<?php
/**
 * Social Cards Tab Content
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Get social card design settings
$design_settings = get_option( 'wpseopilot_social_card_design', [] );
if ( ! is_array( $design_settings ) ) {
	$design_settings = [];
}

$design_defaults = [
	'background_color' => '#1a1a36',
	'accent_color'     => '#5a84ff',
	'text_color'       => '#ffffff',
	'title_font_size'  => 48,
	'site_font_size'   => 24,
	'logo_url'         => '',
	'logo_position'    => 'bottom-left',
	'layout'           => 'default',
];

$design_settings = wp_parse_args( $design_settings, $design_defaults );

// Check if module is enabled
$module_enabled = '1' === get_option( 'wpseopilot_enable_og_preview', '1' );
?>

<div class="wpseopilot-settings-grid">
	<div class="wpseopilot-settings-main">

		<!-- Live Preview Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h2><?php esc_html_e( 'Live Preview', 'wp-seo-pilot' ); ?></h2>
				<p class="wpseopilot-settings-card__description">
					<?php esc_html_e( 'Preview how your social cards will look when shared on social media.', 'wp-seo-pilot' ); ?>
				</p>
			</div>
			<div class="wpseopilot-settings-card__body">

				<!-- Preview Controls -->
				<div class="wpseopilot-social-card-preview__controls">
					<div class="wpseopilot-form-field">
						<label for="wpseopilot-preview-title">
							<?php esc_html_e( 'Sample Title', 'wp-seo-pilot' ); ?>
						</label>
						<input
							type="text"
							id="wpseopilot-preview-title"
							class="regular-text"
							value="<?php esc_attr_e( 'Sample Post Title - Understanding Core Web Vitals', 'wp-seo-pilot' ); ?>"
						/>
					</div>
					<button type="button" id="wpseopilot-refresh-preview" class="button">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh Preview', 'wp-seo-pilot' ); ?>
					</button>
				</div>

				<!-- Preview Frame -->
				<div class="wpseopilot-social-card-preview__frame">
					<div class="wpseopilot-social-card-preview__loading">
						<span class="spinner is-active"></span>
					</div>
					<img
						id="wpseopilot-social-card-preview-img"
						src="<?php echo esc_url( home_url( '/?wpseopilot_social_card=1&title=' . urlencode( 'Sample Post Title - Understanding Core Web Vitals' ) ) ); ?>"
						alt="<?php esc_attr_e( 'Social card preview', 'wp-seo-pilot' ); ?>"
					/>
				</div>

			</div>
		</div>

		<!-- Design Customization Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h2><?php esc_html_e( 'Design Customization', 'wp-seo-pilot' ); ?></h2>
				<p class="wpseopilot-settings-card__description">
					<?php esc_html_e( 'Customize the appearance of your social cards to match your brand.', 'wp-seo-pilot' ); ?>
				</p>
			</div>
			<div class="wpseopilot-settings-card__body">

				<!-- Layout Selection -->
				<div class="wpseopilot-form-field">
					<label><?php esc_html_e( 'Layout Style', 'wp-seo-pilot' ); ?></label>
					<div class="wpseopilot-layout-selector">
						<?php
						$layouts = [
							'default'  => [
								'label'       => __( 'Default', 'wp-seo-pilot' ),
								'description' => __( 'Title with accent bar at bottom', 'wp-seo-pilot' ),
							],
							'centered' => [
								'label'       => __( 'Centered', 'wp-seo-pilot' ),
								'description' => __( 'Centered text layout', 'wp-seo-pilot' ),
							],
							'minimal'  => [
								'label'       => __( 'Minimal', 'wp-seo-pilot' ),
								'description' => __( 'Text only, no accent', 'wp-seo-pilot' ),
							],
							'bold'     => [
								'label'       => __( 'Bold', 'wp-seo-pilot' ),
								'description' => __( 'Large accent block', 'wp-seo-pilot' ),
							],
						];

						foreach ( $layouts as $layout_key => $layout_info ) :
							$checked = checked( $design_settings['layout'], $layout_key, false );
							?>
							<label class="wpseopilot-layout-option">
								<input
									type="radio"
									name="wpseopilot_social_card_design[layout]"
									value="<?php echo esc_attr( $layout_key ); ?>"
									<?php echo $checked; ?>
								/>
								<div class="wpseopilot-layout-option__content">
									<strong><?php echo esc_html( $layout_info['label'] ); ?></strong>
									<span><?php echo esc_html( $layout_info['description'] ); ?></span>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Color Settings -->
				<div class="wpseopilot-form-field">
					<label for="wpseopilot-background-color">
						<?php esc_html_e( 'Background Color', 'wp-seo-pilot' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-background-color"
						name="wpseopilot_social_card_design[background_color]"
						class="wpseopilot-color-picker"
						value="<?php echo esc_attr( $design_settings['background_color'] ); ?>"
					/>
					<input
						type="text"
						class="wpseopilot-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['background_color'] ); ?>"
						readonly
					/>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-accent-color">
						<?php esc_html_e( 'Accent Color', 'wp-seo-pilot' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-accent-color"
						name="wpseopilot_social_card_design[accent_color]"
						class="wpseopilot-color-picker"
						value="<?php echo esc_attr( $design_settings['accent_color'] ); ?>"
					/>
					<input
						type="text"
						class="wpseopilot-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['accent_color'] ); ?>"
						readonly
					/>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-text-color">
						<?php esc_html_e( 'Text Color', 'wp-seo-pilot' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-text-color"
						name="wpseopilot_social_card_design[text_color]"
						class="wpseopilot-color-picker"
						value="<?php echo esc_attr( $design_settings['text_color'] ); ?>"
					/>
					<input
						type="text"
						class="wpseopilot-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['text_color'] ); ?>"
						readonly
					/>
				</div>

				<!-- Font Sizes -->
				<div class="wpseopilot-form-field">
					<label for="wpseopilot-title-font-size">
						<?php esc_html_e( 'Title Font Size (px)', 'wp-seo-pilot' ); ?>
					</label>
					<input
						type="number"
						id="wpseopilot-title-font-size"
						name="wpseopilot_social_card_design[title_font_size]"
						class="small-text"
						value="<?php echo esc_attr( $design_settings['title_font_size'] ); ?>"
						min="24"
						max="96"
						step="1"
					/>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-site-font-size">
						<?php esc_html_e( 'Site Name Font Size (px)', 'wp-seo-pilot' ); ?>
					</label>
					<input
						type="number"
						id="wpseopilot-site-font-size"
						name="wpseopilot_social_card_design[site_font_size]"
						class="small-text"
						value="<?php echo esc_attr( $design_settings['site_font_size'] ); ?>"
						min="12"
						max="48"
						step="1"
					/>
				</div>

				<!-- Logo Settings -->
				<div class="wpseopilot-form-field">
					<label for="wpseopilot-logo-url">
						<?php esc_html_e( 'Logo URL', 'wp-seo-pilot' ); ?>
					</label>
					<div class="wpseopilot-media-upload">
						<input
							type="url"
							id="wpseopilot-logo-url"
							name="wpseopilot_social_card_design[logo_url]"
							class="regular-text"
							value="<?php echo esc_attr( $design_settings['logo_url'] ); ?>"
						/>
						<button type="button" class="button wpseopilot-media-upload-btn" data-target="#wpseopilot-logo-url">
							<?php esc_html_e( 'Choose Image', 'wp-seo-pilot' ); ?>
						</button>
					</div>
					<p class="description">
						<?php esc_html_e( 'Upload or select a logo to display on your social cards. Recommended size: 200x200px.', 'wp-seo-pilot' ); ?>
					</p>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-logo-position">
						<?php esc_html_e( 'Logo Position', 'wp-seo-pilot' ); ?>
					</label>
					<select id="wpseopilot-logo-position" name="wpseopilot_social_card_design[logo_position]">
						<option value="top-left" <?php selected( $design_settings['logo_position'], 'top-left' ); ?>>
							<?php esc_html_e( 'Top Left', 'wp-seo-pilot' ); ?>
						</option>
						<option value="top-right" <?php selected( $design_settings['logo_position'], 'top-right' ); ?>>
							<?php esc_html_e( 'Top Right', 'wp-seo-pilot' ); ?>
						</option>
						<option value="bottom-left" <?php selected( $design_settings['logo_position'], 'bottom-left' ); ?>>
							<?php esc_html_e( 'Bottom Left', 'wp-seo-pilot' ); ?>
						</option>
						<option value="bottom-right" <?php selected( $design_settings['logo_position'], 'bottom-right' ); ?>>
							<?php esc_html_e( 'Bottom Right', 'wp-seo-pilot' ); ?>
						</option>
						<option value="center" <?php selected( $design_settings['logo_position'], 'center' ); ?>>
							<?php esc_html_e( 'Center', 'wp-seo-pilot' ); ?>
						</option>
					</select>
				</div>

			</div>
		</div>

	</div>

	<!-- Sidebar -->
	<div class="wpseopilot-settings-sidebar">

		<!-- Module Status -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h3><?php esc_html_e( 'Module Status', 'wp-seo-pilot' ); ?></h3>
			</div>
			<div class="wpseopilot-settings-card__body">
				<?php if ( $module_enabled ) : ?>
					<div class="wpseopilot-status-badge wpseopilot-status-badge--success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Active', 'wp-seo-pilot' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is enabled.', 'wp-seo-pilot' ); ?></p>
				<?php else : ?>
					<div class="wpseopilot-status-badge wpseopilot-status-badge--inactive">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Disabled', 'wp-seo-pilot' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is currently disabled.', 'wp-seo-pilot' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot#modules' ) ); ?>" class="button">
						<?php esc_html_e( 'Enable in Modules', 'wp-seo-pilot' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Info Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h3><?php esc_html_e( 'About Social Cards', 'wp-seo-pilot' ); ?></h3>
			</div>
			<div class="wpseopilot-settings-card__body">
				<p>
					<?php esc_html_e( 'Social cards are automatically generated images that appear when your content is shared on social media platforms like Facebook, Twitter, and LinkedIn.', 'wp-seo-pilot' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'These customizations only affect the dynamically generated PNG images, not the Open Graph meta tags.', 'wp-seo-pilot' ); ?>
				</p>
			</div>
		</div>

	</div>
</div>
