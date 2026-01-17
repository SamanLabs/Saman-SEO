<?php
/**
 * Social Cards Tab Content
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

// Get social card design settings
$design_settings = get_option( 'SAMAN_SEO_social_card_design', [] );
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
$module_enabled = '1' === get_option( 'SAMAN_SEO_enable_og_preview', '1' );
?>

<div class="saman-seo-settings-grid">
	<div class="saman-seo-settings-main">

		<!-- Live Preview Card -->
		<div class="saman-seo-settings-card">
			<div class="saman-seo-settings-card__header">
				<h2><?php esc_html_e( 'Live Preview', 'saman-seo' ); ?></h2>
				<p class="saman-seo-settings-card__description">
					<?php esc_html_e( 'Preview how your social cards will look when shared on social media.', 'saman-seo' ); ?>
				</p>
			</div>
			<div class="saman-seo-settings-card__body">

				<!-- Preview Controls -->
				<div class="saman-seo-social-card-preview__controls">
					<div class="saman-seo-form-field">
						<label for="saman-seo-preview-title">
							<?php esc_html_e( 'Sample Title', 'saman-seo' ); ?>
						</label>
						<input
							type="text"
							id="saman-seo-preview-title"
							class="regular-text"
							value="<?php esc_attr_e( 'Sample Post Title - Understanding Core Web Vitals', 'saman-seo' ); ?>"
						/>
					</div>
					<button type="button" id="saman-seo-refresh-preview" class="button">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh Preview', 'saman-seo' ); ?>
					</button>
				</div>

				<!-- Preview Frame -->
				<div class="saman-seo-social-card-preview__frame">
					<div class="saman-seo-social-card-preview__loading">
						<span class="spinner is-active"></span>
					</div>
					<img
						id="saman-seo-social-card-preview-img"
						src="<?php echo esc_url( home_url( '/?SAMAN_SEO_social_card=1&title=' . urlencode( 'Sample Post Title - Understanding Core Web Vitals' ) ) ); ?>"
						alt="<?php esc_attr_e( 'Social card preview', 'saman-seo' ); ?>"
					/>
				</div>

			</div>
		</div>

		<!-- Design Customization Card -->
		<div class="saman-seo-settings-card">
			<div class="saman-seo-settings-card__header">
				<h2><?php esc_html_e( 'Design Customization', 'saman-seo' ); ?></h2>
				<p class="saman-seo-settings-card__description">
					<?php esc_html_e( 'Customize the appearance of your social cards to match your brand.', 'saman-seo' ); ?>
				</p>
			</div>
			<div class="saman-seo-settings-card__body">

				<!-- Layout Selection -->
				<div class="saman-seo-form-field">
					<label><?php esc_html_e( 'Layout Style', 'saman-seo' ); ?></label>
					<div class="saman-seo-layout-selector">
						<?php
						$layouts = [
							'default'  => [
								'label'       => __( 'Default', 'saman-seo' ),
								'description' => __( 'Title with accent bar at bottom', 'saman-seo' ),
							],
							'centered' => [
								'label'       => __( 'Centered', 'saman-seo' ),
								'description' => __( 'Centered text layout', 'saman-seo' ),
							],
							'minimal'  => [
								'label'       => __( 'Minimal', 'saman-seo' ),
								'description' => __( 'Text only, no accent', 'saman-seo' ),
							],
							'bold'     => [
								'label'       => __( 'Bold', 'saman-seo' ),
								'description' => __( 'Large accent block', 'saman-seo' ),
							],
						];

						foreach ( $layouts as $layout_key => $layout_info ) :
							$checked = checked( $design_settings['layout'], $layout_key, false );
							?>
							<label class="saman-seo-layout-option">
								<input
									type="radio"
									name="SAMAN_SEO_social_card_design[layout]"
									value="<?php echo esc_attr( $layout_key ); ?>"
									<?php echo $checked; ?>
								/>
								<div class="saman-seo-layout-option__content">
									<strong><?php echo esc_html( $layout_info['label'] ); ?></strong>
									<span><?php echo esc_html( $layout_info['description'] ); ?></span>
								</div>
							</label>
						<?php endforeach; ?>
					</div>
				</div>

				<!-- Color Settings -->
				<div class="saman-seo-form-field">
					<label for="saman-seo-background-color">
						<?php esc_html_e( 'Background Color', 'saman-seo' ); ?>
					</label>
					<input
						type="color"
						id="saman-seo-background-color"
						name="SAMAN_SEO_social_card_design[background_color]"
						class="saman-seo-color-picker"
						value="<?php echo esc_attr( $design_settings['background_color'] ); ?>"
					/>
					<input
						type="text"
						class="saman-seo-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['background_color'] ); ?>"
						readonly
					/>
				</div>

				<div class="saman-seo-form-field">
					<label for="saman-seo-accent-color">
						<?php esc_html_e( 'Accent Color', 'saman-seo' ); ?>
					</label>
					<input
						type="color"
						id="saman-seo-accent-color"
						name="SAMAN_SEO_social_card_design[accent_color]"
						class="saman-seo-color-picker"
						value="<?php echo esc_attr( $design_settings['accent_color'] ); ?>"
					/>
					<input
						type="text"
						class="saman-seo-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['accent_color'] ); ?>"
						readonly
					/>
				</div>

				<div class="saman-seo-form-field">
					<label for="saman-seo-text-color">
						<?php esc_html_e( 'Text Color', 'saman-seo' ); ?>
					</label>
					<input
						type="color"
						id="saman-seo-text-color"
						name="SAMAN_SEO_social_card_design[text_color]"
						class="saman-seo-color-picker"
						value="<?php echo esc_attr( $design_settings['text_color'] ); ?>"
					/>
					<input
						type="text"
						class="saman-seo-color-text regular-text"
						value="<?php echo esc_attr( $design_settings['text_color'] ); ?>"
						readonly
					/>
				</div>

				<!-- Font Sizes -->
				<div class="saman-seo-form-field">
					<label for="saman-seo-title-font-size">
						<?php esc_html_e( 'Title Font Size (px)', 'saman-seo' ); ?>
					</label>
					<input
						type="number"
						id="saman-seo-title-font-size"
						name="SAMAN_SEO_social_card_design[title_font_size]"
						class="small-text"
						value="<?php echo esc_attr( $design_settings['title_font_size'] ); ?>"
						min="24"
						max="96"
						step="1"
					/>
				</div>

				<div class="saman-seo-form-field">
					<label for="saman-seo-site-font-size">
						<?php esc_html_e( 'Site Name Font Size (px)', 'saman-seo' ); ?>
					</label>
					<input
						type="number"
						id="saman-seo-site-font-size"
						name="SAMAN_SEO_social_card_design[site_font_size]"
						class="small-text"
						value="<?php echo esc_attr( $design_settings['site_font_size'] ); ?>"
						min="12"
						max="48"
						step="1"
					/>
				</div>

				<!-- Logo Settings -->
				<div class="saman-seo-form-field">
					<label for="saman-seo-logo-url">
						<?php esc_html_e( 'Logo URL', 'saman-seo' ); ?>
					</label>
					<div class="saman-seo-media-upload">
						<input
							type="url"
							id="saman-seo-logo-url"
							name="SAMAN_SEO_social_card_design[logo_url]"
							class="regular-text"
							value="<?php echo esc_attr( $design_settings['logo_url'] ); ?>"
						/>
						<button type="button" class="button saman-seo-media-upload-btn" data-target="#saman-seo-logo-url">
							<?php esc_html_e( 'Choose Image', 'saman-seo' ); ?>
						</button>
					</div>
					<p class="description">
						<?php esc_html_e( 'Upload or select a logo to display on your social cards. Recommended size: 200x200px.', 'saman-seo' ); ?>
					</p>
				</div>

				<div class="saman-seo-form-field">
					<label for="saman-seo-logo-position">
						<?php esc_html_e( 'Logo Position', 'saman-seo' ); ?>
					</label>
					<select id="saman-seo-logo-position" name="SAMAN_SEO_social_card_design[logo_position]">
						<option value="top-left" <?php selected( $design_settings['logo_position'], 'top-left' ); ?>>
							<?php esc_html_e( 'Top Left', 'saman-seo' ); ?>
						</option>
						<option value="top-right" <?php selected( $design_settings['logo_position'], 'top-right' ); ?>>
							<?php esc_html_e( 'Top Right', 'saman-seo' ); ?>
						</option>
						<option value="bottom-left" <?php selected( $design_settings['logo_position'], 'bottom-left' ); ?>>
							<?php esc_html_e( 'Bottom Left', 'saman-seo' ); ?>
						</option>
						<option value="bottom-right" <?php selected( $design_settings['logo_position'], 'bottom-right' ); ?>>
							<?php esc_html_e( 'Bottom Right', 'saman-seo' ); ?>
						</option>
						<option value="center" <?php selected( $design_settings['logo_position'], 'center' ); ?>>
							<?php esc_html_e( 'Center', 'saman-seo' ); ?>
						</option>
					</select>
				</div>

			</div>
		</div>

	</div>

	<!-- Sidebar -->
	<div class="saman-seo-settings-sidebar">

		<!-- Module Status -->
		<div class="saman-seo-settings-card">
			<div class="saman-seo-settings-card__header">
				<h3><?php esc_html_e( 'Module Status', 'saman-seo' ); ?></h3>
			</div>
			<div class="saman-seo-settings-card__body">
				<?php if ( $module_enabled ) : ?>
					<div class="saman-seo-status-badge saman-seo-status-badge--success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Active', 'saman-seo' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is enabled.', 'saman-seo' ); ?></p>
				<?php else : ?>
					<div class="saman-seo-status-badge saman-seo-status-badge--inactive">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Disabled', 'saman-seo' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is currently disabled.', 'saman-seo' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=saman-seo#modules' ) ); ?>" class="button">
						<?php esc_html_e( 'Enable in Modules', 'saman-seo' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Info Card -->
		<div class="saman-seo-settings-card">
			<div class="saman-seo-settings-card__header">
				<h3><?php esc_html_e( 'About Social Cards', 'saman-seo' ); ?></h3>
			</div>
			<div class="saman-seo-settings-card__body">
				<p>
					<?php esc_html_e( 'Social cards are automatically generated images that appear when your content is shared on social media platforms like Facebook, Twitter, and LinkedIn.', 'saman-seo' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'These customizations only affect the dynamically generated PNG images, not the Open Graph meta tags.', 'saman-seo' ); ?>
				</p>
			</div>
		</div>

	</div>
</div>
