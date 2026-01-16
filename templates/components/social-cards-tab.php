<?php
/**
 * Social Cards Tab Content
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Get social card design settings
$design_settings = get_option( 'samanlabs_seo_social_card_design', [] );
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
$module_enabled = '1' === get_option( 'samanlabs_seo_enable_og_preview', '1' );
?>

<div class="wpseopilot-settings-grid">
	<div class="wpseopilot-settings-main">

		<!-- Live Preview Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h2><?php esc_html_e( 'Live Preview', 'saman-labs-seo' ); ?></h2>
				<p class="wpseopilot-settings-card__description">
					<?php esc_html_e( 'Preview how your social cards will look when shared on social media.', 'saman-labs-seo' ); ?>
				</p>
			</div>
			<div class="wpseopilot-settings-card__body">

				<!-- Preview Controls -->
				<div class="wpseopilot-social-card-preview__controls">
					<div class="wpseopilot-form-field">
						<label for="wpseopilot-preview-title">
							<?php esc_html_e( 'Sample Title', 'saman-labs-seo' ); ?>
						</label>
						<input
							type="text"
							id="wpseopilot-preview-title"
							class="regular-text"
							value="<?php esc_attr_e( 'Sample Post Title - Understanding Core Web Vitals', 'saman-labs-seo' ); ?>"
						/>
					</div>
					<button type="button" id="wpseopilot-refresh-preview" class="button">
						<span class="dashicons dashicons-update"></span>
						<?php esc_html_e( 'Refresh Preview', 'saman-labs-seo' ); ?>
					</button>
				</div>

				<!-- Preview Frame -->
				<div class="wpseopilot-social-card-preview__frame">
					<div class="wpseopilot-social-card-preview__loading">
						<span class="spinner is-active"></span>
					</div>
					<img
						id="wpseopilot-social-card-preview-img"
						src="<?php echo esc_url( home_url( '/?samanlabs_seo_social_card=1&title=' . urlencode( 'Sample Post Title - Understanding Core Web Vitals' ) ) ); ?>"
						alt="<?php esc_attr_e( 'Social card preview', 'saman-labs-seo' ); ?>"
					/>
				</div>

			</div>
		</div>

		<!-- Design Customization Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h2><?php esc_html_e( 'Design Customization', 'saman-labs-seo' ); ?></h2>
				<p class="wpseopilot-settings-card__description">
					<?php esc_html_e( 'Customize the appearance of your social cards to match your brand.', 'saman-labs-seo' ); ?>
				</p>
			</div>
			<div class="wpseopilot-settings-card__body">

				<!-- Layout Selection -->
				<div class="wpseopilot-form-field">
					<label><?php esc_html_e( 'Layout Style', 'saman-labs-seo' ); ?></label>
					<div class="wpseopilot-layout-selector">
						<?php
						$layouts = [
							'default'  => [
								'label'       => __( 'Default', 'saman-labs-seo' ),
								'description' => __( 'Title with accent bar at bottom', 'saman-labs-seo' ),
							],
							'centered' => [
								'label'       => __( 'Centered', 'saman-labs-seo' ),
								'description' => __( 'Centered text layout', 'saman-labs-seo' ),
							],
							'minimal'  => [
								'label'       => __( 'Minimal', 'saman-labs-seo' ),
								'description' => __( 'Text only, no accent', 'saman-labs-seo' ),
							],
							'bold'     => [
								'label'       => __( 'Bold', 'saman-labs-seo' ),
								'description' => __( 'Large accent block', 'saman-labs-seo' ),
							],
						];

						foreach ( $layouts as $layout_key => $layout_info ) :
							$checked = checked( $design_settings['layout'], $layout_key, false );
							?>
							<label class="wpseopilot-layout-option">
								<input
									type="radio"
									name="samanlabs_seo_social_card_design[layout]"
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
						<?php esc_html_e( 'Background Color', 'saman-labs-seo' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-background-color"
						name="samanlabs_seo_social_card_design[background_color]"
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
						<?php esc_html_e( 'Accent Color', 'saman-labs-seo' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-accent-color"
						name="samanlabs_seo_social_card_design[accent_color]"
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
						<?php esc_html_e( 'Text Color', 'saman-labs-seo' ); ?>
					</label>
					<input
						type="color"
						id="wpseopilot-text-color"
						name="samanlabs_seo_social_card_design[text_color]"
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
						<?php esc_html_e( 'Title Font Size (px)', 'saman-labs-seo' ); ?>
					</label>
					<input
						type="number"
						id="wpseopilot-title-font-size"
						name="samanlabs_seo_social_card_design[title_font_size]"
						class="small-text"
						value="<?php echo esc_attr( $design_settings['title_font_size'] ); ?>"
						min="24"
						max="96"
						step="1"
					/>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-site-font-size">
						<?php esc_html_e( 'Site Name Font Size (px)', 'saman-labs-seo' ); ?>
					</label>
					<input
						type="number"
						id="wpseopilot-site-font-size"
						name="samanlabs_seo_social_card_design[site_font_size]"
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
						<?php esc_html_e( 'Logo URL', 'saman-labs-seo' ); ?>
					</label>
					<div class="wpseopilot-media-upload">
						<input
							type="url"
							id="wpseopilot-logo-url"
							name="samanlabs_seo_social_card_design[logo_url]"
							class="regular-text"
							value="<?php echo esc_attr( $design_settings['logo_url'] ); ?>"
						/>
						<button type="button" class="button wpseopilot-media-upload-btn" data-target="#wpseopilot-logo-url">
							<?php esc_html_e( 'Choose Image', 'saman-labs-seo' ); ?>
						</button>
					</div>
					<p class="description">
						<?php esc_html_e( 'Upload or select a logo to display on your social cards. Recommended size: 200x200px.', 'saman-labs-seo' ); ?>
					</p>
				</div>

				<div class="wpseopilot-form-field">
					<label for="wpseopilot-logo-position">
						<?php esc_html_e( 'Logo Position', 'saman-labs-seo' ); ?>
					</label>
					<select id="wpseopilot-logo-position" name="samanlabs_seo_social_card_design[logo_position]">
						<option value="top-left" <?php selected( $design_settings['logo_position'], 'top-left' ); ?>>
							<?php esc_html_e( 'Top Left', 'saman-labs-seo' ); ?>
						</option>
						<option value="top-right" <?php selected( $design_settings['logo_position'], 'top-right' ); ?>>
							<?php esc_html_e( 'Top Right', 'saman-labs-seo' ); ?>
						</option>
						<option value="bottom-left" <?php selected( $design_settings['logo_position'], 'bottom-left' ); ?>>
							<?php esc_html_e( 'Bottom Left', 'saman-labs-seo' ); ?>
						</option>
						<option value="bottom-right" <?php selected( $design_settings['logo_position'], 'bottom-right' ); ?>>
							<?php esc_html_e( 'Bottom Right', 'saman-labs-seo' ); ?>
						</option>
						<option value="center" <?php selected( $design_settings['logo_position'], 'center' ); ?>>
							<?php esc_html_e( 'Center', 'saman-labs-seo' ); ?>
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
				<h3><?php esc_html_e( 'Module Status', 'saman-labs-seo' ); ?></h3>
			</div>
			<div class="wpseopilot-settings-card__body">
				<?php if ( $module_enabled ) : ?>
					<div class="wpseopilot-status-badge wpseopilot-status-badge--success">
						<span class="dashicons dashicons-yes-alt"></span>
						<?php esc_html_e( 'Active', 'saman-labs-seo' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is enabled.', 'saman-labs-seo' ); ?></p>
				<?php else : ?>
					<div class="wpseopilot-status-badge wpseopilot-status-badge--inactive">
						<span class="dashicons dashicons-warning"></span>
						<?php esc_html_e( 'Disabled', 'saman-labs-seo' ); ?>
					</div>
					<p><?php esc_html_e( 'Dynamic social card generator is currently disabled.', 'saman-labs-seo' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wpseopilot#modules' ) ); ?>" class="button">
						<?php esc_html_e( 'Enable in Modules', 'saman-labs-seo' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Info Card -->
		<div class="wpseopilot-settings-card">
			<div class="wpseopilot-settings-card__header">
				<h3><?php esc_html_e( 'About Social Cards', 'saman-labs-seo' ); ?></h3>
			</div>
			<div class="wpseopilot-settings-card__body">
				<p>
					<?php esc_html_e( 'Social cards are automatically generated images that appear when your content is shared on social media platforms like Facebook, Twitter, and LinkedIn.', 'saman-labs-seo' ); ?>
				</p>
				<p>
					<?php esc_html_e( 'These customizations only affect the dynamically generated PNG images, not the Open Graph meta tags.', 'saman-labs-seo' ); ?>
				</p>
			</div>
		</div>

	</div>
</div>
