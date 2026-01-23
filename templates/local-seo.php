<?php
/**
 * Local SEO Settings Template
 *
 * @var array $business_types Available business types.
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

// Get Knowledge Graph synced values (these sync with main settings).
$entity_type = get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' );
$org_name    = get_option( 'SAMAN_SEO_homepage_organization_name', '' );
$org_logo    = get_option( 'SAMAN_SEO_homepage_organization_logo', '' );

// Get Local SEO specific values.
$business_name  = get_option( 'SAMAN_SEO_local_business_name', '' );
$business_type  = get_option( 'SAMAN_SEO_local_business_type', 'LocalBusiness' );
$description    = get_option( 'SAMAN_SEO_local_description', '' );
$logo           = get_option( 'SAMAN_SEO_local_logo', '' );
$image          = get_option( 'SAMAN_SEO_local_image', '' );
$price_range    = get_option( 'SAMAN_SEO_local_price_range', '' );
$phone          = get_option( 'SAMAN_SEO_local_phone', '' );
$email          = get_option( 'SAMAN_SEO_local_email', '' );
$street         = get_option( 'SAMAN_SEO_local_street', '' );
$city           = get_option( 'SAMAN_SEO_local_city', '' );
$state          = get_option( 'SAMAN_SEO_local_state', '' );
$zip            = get_option( 'SAMAN_SEO_local_zip', '' );
$country        = get_option( 'SAMAN_SEO_local_country', '' );
$latitude       = get_option( 'SAMAN_SEO_local_latitude', '' );
$longitude      = get_option( 'SAMAN_SEO_local_longitude', '' );
$social_profiles = get_option( 'SAMAN_SEO_local_social_profiles', [] );
$opening_hours  = get_option( 'SAMAN_SEO_local_opening_hours', [] );
$maps_api_key   = get_option( 'SAMAN_SEO_google_maps_api_key', '' );

// Ensure arrays.
if ( ! is_array( $social_profiles ) ) {
	$social_profiles = [];
}
if ( ! is_array( $opening_hours ) ) {
	$opening_hours = [];
}

// Use synced values if local values are empty (for preview).
$preview_name = ! empty( $business_name ) ? $business_name : $org_name;
$preview_logo = ! empty( $logo ) ? $logo : $org_logo;

// Render top bar.
\Saman\SEO\Admin_Topbar::render( 'local-seo' );
?>

<div class="wrap saman-seo-page saman-seo-local-seo-page">

	<div class="saman-seo-settings-grid">
		<!-- Main Content Area -->
		<div class="saman-seo-settings-main">
			<div class="saman-seo-tabs" data-component="saman-seo-tabs">
				<div class="nav-tab-wrapper saman-seo-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Local SEO sections', 'saman-seo' ); ?>">
					<button
						type="button"
						class="nav-tab nav-tab-active"
						id="saman-seo-tab-link-business-info"
						role="tab"
						aria-selected="true"
						aria-controls="saman-seo-tab-business-info"
						data-saman-seo-tab="saman-seo-tab-business-info"
					>
						<?php esc_html_e( 'Business Information', 'saman-seo' ); ?>
					</button>
					<button
						type="button"
						class="nav-tab"
						id="saman-seo-tab-link-opening-hours"
						role="tab"
						aria-selected="false"
						aria-controls="saman-seo-tab-opening-hours"
						data-saman-seo-tab="saman-seo-tab-opening-hours"
					>
						<?php esc_html_e( 'Opening Hours', 'saman-seo' ); ?>
					</button>
				</div>

				<!-- Business Information Tab -->
				<div
					id="saman-seo-tab-business-info"
					class="saman-seo-tab-panel is-active"
					role="tabpanel"
					aria-labelledby="saman-seo-tab-link-business-info"
				>
					<form action="options.php" method="post" data-component="saman-seo-local-seo-form">
						<?php settings_fields( 'SAMAN_SEO_local_seo' ); ?>

						<!-- Business Identity Card (Synced with Knowledge Graph) -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Business Identity', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Core identity information for your business or personal brand.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<div class="saman-seo-synced-notice">
									<span class="dashicons dashicons-admin-links"></span>
									<span>
										<?php
										printf(
											/* translators: %s: link to settings page */
											esc_html__( 'These fields are synced with your %s settings for consistent branding across all schema.', 'saman-seo' ),
											'<a href="' . esc_url( admin_url( 'admin.php?page=saman-seo-settings#knowledge' ) ) . '">' . esc_html__( 'Knowledge Graph', 'saman-seo' ) . '</a>'
										);
										?>
									</span>
								</div>

								<div class="saman-seo-form-row">
									<label>
										<strong><?php esc_html_e( 'This site represents', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Choose whether this site represents a business/organization or a person', 'saman-seo' ); ?></span>
									</label>
									<div class="saman-seo-entity-type-cards" data-component="saman-seo-entity-cards">
										<label class="saman-seo-entity-card <?php echo 'organization' === $entity_type ? 'is-selected' : ''; ?>">
											<input
												type="radio"
												name="SAMAN_SEO_homepage_knowledge_type"
												value="organization"
												<?php checked( $entity_type, 'organization' ); ?>
											/>
											<span class="dashicons dashicons-building"></span>
											<strong><?php esc_html_e( 'Organization', 'saman-seo' ); ?></strong>
											<span><?php esc_html_e( 'Business, company, or brand', 'saman-seo' ); ?></span>
										</label>
										<label class="saman-seo-entity-card <?php echo 'person' === $entity_type ? 'is-selected' : ''; ?>">
											<input
												type="radio"
												name="SAMAN_SEO_homepage_knowledge_type"
												value="person"
												<?php checked( $entity_type, 'person' ); ?>
											/>
											<span class="dashicons dashicons-admin-users"></span>
											<strong><?php esc_html_e( 'Person', 'saman-seo' ); ?></strong>
											<span><?php esc_html_e( 'Personal site or blog', 'saman-seo' ); ?></span>
										</label>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_business_name">
										<strong><?php esc_html_e( 'Business Name', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Official name of your business (required)', 'saman-seo' ); ?></span>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_business_name"
										name="SAMAN_SEO_local_business_name"
										value="<?php echo esc_attr( $business_name ); ?>"
										class="regular-text"
										data-preview-field="name"
										placeholder="<?php echo esc_attr( $org_name ); ?>"
										required
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_logo">
										<strong><?php esc_html_e( 'Business Logo', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Official logo, at least 112x112px', 'saman-seo' ); ?></span>
									</label>
									<div class="saman-seo-media-field">
										<input
											type="url"
											id="SAMAN_SEO_local_logo"
											name="SAMAN_SEO_local_logo"
											value="<?php echo esc_url( $logo ); ?>"
											class="regular-text"
											data-preview-field="logo"
											placeholder="<?php echo esc_url( $org_logo ); ?>"
										/>
										<button type="button" class="button saman-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-seo' ); ?></button>
									</div>
								</div>

							</div>
						</div>

						<!-- Business Details Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Business Details', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Additional information about your business for rich search results.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_business_type">
										<strong><?php esc_html_e( 'Business Type', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Select the type that best describes your business', 'saman-seo' ); ?></span>
									</label>
									<select id="SAMAN_SEO_local_business_type" name="SAMAN_SEO_local_business_type" class="regular-text" data-preview-field="type">
										<?php foreach ( $business_types as $value => $label ) : ?>
											<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $business_type, $value ); ?>>
												<?php echo esc_html( $label ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_description">
										<strong><?php esc_html_e( 'Business Description', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Short, human-readable description of your business', 'saman-seo' ); ?></span>
									</label>
									<textarea
										id="SAMAN_SEO_local_description"
										name="SAMAN_SEO_local_description"
										rows="3"
										class="large-text"
										data-preview-field="description"
									><?php echo esc_textarea( $description ); ?></textarea>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_image">
										<strong><?php esc_html_e( 'Cover Image', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Featured image representing your business', 'saman-seo' ); ?></span>
									</label>
									<div class="saman-seo-media-field">
										<input
											type="url"
											id="SAMAN_SEO_local_image"
											name="SAMAN_SEO_local_image"
											value="<?php echo esc_url( $image ); ?>"
											class="regular-text"
											data-preview-field="cover"
										/>
										<button type="button" class="button saman-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-seo' ); ?></button>
									</div>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_price_range">
										<strong><?php esc_html_e( 'Price Range', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Use $ symbols (e.g., $, $$, $$$, $$$$)', 'saman-seo' ); ?></span>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_price_range"
										name="SAMAN_SEO_local_price_range"
										value="<?php echo esc_attr( $price_range ); ?>"
										class="small-text"
										placeholder="$$"
										data-preview-field="price"
									/>
								</div>

							</div>
						</div>

						<!-- Contact Information Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Contact Information', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'How customers can reach your business.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_phone">
										<strong><?php esc_html_e( 'Phone Number', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Include country code (e.g., +1-305-555-1234)', 'saman-seo' ); ?></span>
									</label>
									<input
										type="tel"
										id="SAMAN_SEO_local_phone"
										name="SAMAN_SEO_local_phone"
										value="<?php echo esc_attr( $phone ); ?>"
										class="regular-text"
										placeholder="+1-555-555-5555"
										data-preview-field="phone"
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_email">
										<strong><?php esc_html_e( 'Email Address', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Public contact email for your business', 'saman-seo' ); ?></span>
									</label>
									<input
										type="email"
										id="SAMAN_SEO_local_email"
										name="SAMAN_SEO_local_email"
										value="<?php echo esc_attr( $email ); ?>"
										class="regular-text"
										placeholder="contact@example.com"
									/>
								</div>

							</div>
						</div>

						<!-- Address Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Business Address', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Physical location of your business for local search results.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_street">
										<strong><?php esc_html_e( 'Street Address', 'saman-seo' ); ?></strong>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_street"
										name="SAMAN_SEO_local_street"
										value="<?php echo esc_attr( $street ); ?>"
										class="regular-text"
										placeholder="123 Main Street"
										data-preview-field="street"
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_city">
										<strong><?php esc_html_e( 'City', 'saman-seo' ); ?></strong>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_city"
										name="SAMAN_SEO_local_city"
										value="<?php echo esc_attr( $city ); ?>"
										class="regular-text"
										placeholder="Miami"
										data-preview-field="city"
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_state">
										<strong><?php esc_html_e( 'State / Province', 'saman-seo' ); ?></strong>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_state"
										name="SAMAN_SEO_local_state"
										value="<?php echo esc_attr( $state ); ?>"
										class="regular-text"
										placeholder="FL"
										data-preview-field="state"
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_zip">
										<strong><?php esc_html_e( 'Postal Code', 'saman-seo' ); ?></strong>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_zip"
										name="SAMAN_SEO_local_zip"
										value="<?php echo esc_attr( $zip ); ?>"
										class="small-text"
										placeholder="33101"
										data-preview-field="zip"
									/>
								</div>

								<div class="saman-seo-form-row">
									<label for="SAMAN_SEO_local_country">
										<strong><?php esc_html_e( 'Country', 'saman-seo' ); ?></strong>
										<span class="saman-seo-label-hint"><?php esc_html_e( 'Two-letter country code (e.g., US, GB, CA)', 'saman-seo' ); ?></span>
									</label>
									<input
										type="text"
										id="SAMAN_SEO_local_country"
										name="SAMAN_SEO_local_country"
										value="<?php echo esc_attr( $country ); ?>"
										class="small-text"
										placeholder="US"
										maxlength="2"
									/>
								</div>

							</div>
						</div>

						<!-- Geo Coordinates Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Geo Coordinates', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Precise location coordinates improve map accuracy and local search rankings.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<div class="saman-seo-map-container" data-component="saman-seo-map">
									<div class="saman-seo-map-container__coords">
										<div class="saman-seo-form-row">
											<label for="SAMAN_SEO_local_latitude">
												<strong><?php esc_html_e( 'Latitude', 'saman-seo' ); ?></strong>
											</label>
											<input
												type="text"
												id="SAMAN_SEO_local_latitude"
												name="SAMAN_SEO_local_latitude"
												value="<?php echo esc_attr( $latitude ); ?>"
												class="regular-text"
												placeholder="25.761681"
												data-coord="lat"
											/>
										</div>

										<div class="saman-seo-form-row">
											<label for="SAMAN_SEO_local_longitude">
												<strong><?php esc_html_e( 'Longitude', 'saman-seo' ); ?></strong>
											</label>
											<input
												type="text"
												id="SAMAN_SEO_local_longitude"
												name="SAMAN_SEO_local_longitude"
												value="<?php echo esc_attr( $longitude ); ?>"
												class="regular-text"
												placeholder="-80.191788"
												data-coord="lng"
											/>
										</div>
									</div>

									<div class="saman-seo-form-row">
										<label for="SAMAN_SEO_google_maps_api_key">
											<strong><?php esc_html_e( 'Google Maps API Key', 'saman-seo' ); ?></strong>
											<span class="saman-seo-label-hint"><?php esc_html_e( 'Optional: Enable interactive map for easier coordinate selection', 'saman-seo' ); ?></span>
										</label>
										<input
											type="text"
											id="SAMAN_SEO_google_maps_api_key"
											name="SAMAN_SEO_google_maps_api_key"
											value="<?php echo esc_attr( $maps_api_key ); ?>"
											class="regular-text"
											placeholder="AIza..."
										/>
									</div>

									<?php if ( ! empty( $maps_api_key ) ) : ?>
										<div class="saman-seo-map-container__map is-interactive" id="saman-seo-google-map" data-api-key="<?php echo esc_attr( $maps_api_key ); ?>"></div>
									<?php else : ?>
										<div class="saman-seo-map-container__map">
											<div class="saman-seo-map-container__placeholder">
												<span class="dashicons dashicons-location-alt"></span>
												<p><?php esc_html_e( 'Add a Google Maps API key to enable interactive map selection.', 'saman-seo' ); ?></p>
												<a href="https://www.google.com/maps" class="button button-secondary" target="_blank" rel="noopener">
													<?php esc_html_e( 'Find on Google Maps', 'saman-seo' ); ?>
												</a>
											</div>
										</div>
									<?php endif; ?>
								</div>

							</div>
						</div>

						<!-- Social Profiles Card -->
						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Social Profiles', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Links to your business social media profiles for brand verification.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<?php
								$profile_count = max( count( $social_profiles ), 3 );
								for ( $i = 0; $i < $profile_count + 1; $i++ ) :
									$profile_url = $social_profiles[ $i ] ?? '';
									?>
									<div class="saman-seo-form-row">
										<label for="SAMAN_SEO_local_social_<?php echo esc_attr( $i ); ?>">
											<?php /* translators: %d: social profile number */ ?>
											<strong><?php echo esc_html( sprintf( __( 'Social Profile %d', 'saman-seo' ), $i + 1 ) ); ?></strong>
										</label>
										<input
											type="url"
											id="SAMAN_SEO_local_social_<?php echo esc_attr( $i ); ?>"
											name="SAMAN_SEO_local_social_profiles[]"
											value="<?php echo esc_url( $profile_url ); ?>"
											class="regular-text"
											placeholder="https://facebook.com/yourpage"
											data-preview-field="social"
										/>
									</div>
								<?php endfor; ?>

								<p class="description">
									<?php esc_html_e( 'Add URLs to your official social media profiles (Facebook, Instagram, LinkedIn, Twitter, etc.). Only include profiles you actually maintain.', 'saman-seo' ); ?>
								</p>

							</div>
						</div>

						<?php submit_button( __( 'Save Business Information', 'saman-seo' ) ); ?>
					</form>
				</div>

				<!-- Opening Hours Tab -->
				<div
					id="saman-seo-tab-opening-hours"
					class="saman-seo-tab-panel"
					role="tabpanel"
					aria-labelledby="saman-seo-tab-link-opening-hours"
				>
					<form action="options.php" method="post" data-component="saman-seo-hours-form">
						<?php settings_fields( 'SAMAN_SEO_local_seo' ); ?>

						<div class="saman-seo-card">
							<div class="saman-seo-card-header">
								<h2><?php esc_html_e( 'Opening Hours', 'saman-seo' ); ?></h2>
								<p><?php esc_html_e( 'Configure your business hours for each day of the week.', 'saman-seo' ); ?></p>
							</div>
							<div class="saman-seo-card-body">

								<!-- Presets -->
								<div class="saman-seo-hours__presets" data-component="saman-seo-hours-presets">
									<span class="description" style="margin-right: 8px;"><?php esc_html_e( 'Quick fill:', 'saman-seo' ); ?></span>
									<button type="button" class="button" data-preset="9-5-mon-fri">
										<?php esc_html_e( '9-5 Mon-Fri', 'saman-seo' ); ?>
									</button>
									<button type="button" class="button" data-preset="9-6-mon-sat">
										<?php esc_html_e( '9-6 Mon-Sat', 'saman-seo' ); ?>
									</button>
									<button type="button" class="button" data-preset="24-7">
										<?php esc_html_e( '24/7', 'saman-seo' ); ?>
									</button>
									<button type="button" class="button" data-preset="clear">
										<?php esc_html_e( 'Clear All', 'saman-seo' ); ?>
									</button>
								</div>

								<div class="saman-seo-hours__grid" data-component="saman-seo-hours-grid">
									<?php
									$days = [
										'monday'    => __( 'Monday', 'saman-seo' ),
										'tuesday'   => __( 'Tuesday', 'saman-seo' ),
										'wednesday' => __( 'Wednesday', 'saman-seo' ),
										'thursday'  => __( 'Thursday', 'saman-seo' ),
										'friday'    => __( 'Friday', 'saman-seo' ),
										'saturday'  => __( 'Saturday', 'saman-seo' ),
										'sunday'    => __( 'Sunday', 'saman-seo' ),
									];

									foreach ( $days as $day => $label ) :
										$day_data = $opening_hours[ $day ] ?? [];
										$enabled  = ! empty( $day_data['enabled'] ) && '1' === $day_data['enabled'];
										$open     = $day_data['open'] ?? '09:00';
										$close    = $day_data['close'] ?? '17:00';
										$is_weekday = in_array( $day, [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday' ], true );
										?>
										<div class="saman-seo-hours__row <?php echo ! $enabled ? 'is-disabled' : ''; ?>" data-day="<?php echo esc_attr( $day ); ?>" data-weekday="<?php echo $is_weekday ? 'true' : 'false'; ?>">
											<div class="saman-seo-hours__day">
												<label class="saman-seo-toggle-switch">
													<input
														type="checkbox"
														name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][enabled]"
														value="1"
														data-day-toggle
														<?php checked( $enabled ); ?>
													/>
													<span class="saman-seo-toggle-slider"></span>
												</label>
												<strong><?php echo esc_html( $label ); ?></strong>
											</div>
											<div class="saman-seo-hours__times">
												<input
													type="time"
													name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][open]"
													value="<?php echo esc_attr( $open ); ?>"
													class="saman-seo-time-input"
													data-time="open"
												/>
												<span class="saman-seo-hours__separator">&ndash;</span>
												<input
													type="time"
													name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][close]"
													value="<?php echo esc_attr( $close ); ?>"
													class="saman-seo-time-input"
													data-time="close"
												/>
											</div>
											<button type="button" class="button button-small saman-seo-hours__copy-btn" data-copy-hours title="<?php esc_attr_e( 'Copy to weekdays', 'saman-seo' ); ?>">
												<?php esc_html_e( 'Copy to weekdays', 'saman-seo' ); ?>
											</button>
										</div>
									<?php endforeach; ?>
								</div>

								<div class="saman-seo-hours__summary" id="saman-seo-hours-summary" style="display: none;">
									<strong><?php esc_html_e( 'Closed days:', 'saman-seo' ); ?></strong>
									<span id="saman-seo-closed-days"></span>
								</div>

								<p class="description" style="margin-top: 16px;">
									<?php esc_html_e( 'Use 24-hour format (HH:MM). Days with the toggle off will be marked as closed.', 'saman-seo' ); ?>
								</p>

							</div>
						</div>

						<?php submit_button( __( 'Save Opening Hours', 'saman-seo' ) ); ?>
					</form>
				</div>

			</div>
		</div>

		<!-- Sidebar -->
		<div class="saman-seo-settings-sidebar">
			<?php
			// Include the knowledge panel preview component.
			include SAMAN_SEO_PATH . 'templates/components/knowledge-panel-preview.php';
			?>

			<!-- Module Status Card -->
			<div class="saman-seo-module-status-card">
				<h3><?php esc_html_e( 'Setup Progress', 'saman-seo' ); ?></h3>
				<div class="saman-seo-module-status-card__item">
					<span class="saman-seo-module-status-card__label"><?php esc_html_e( 'Business Name', 'saman-seo' ); ?></span>
					<span class="saman-seo-module-status-card__value <?php echo ! empty( $preview_name ) ? 'is-complete' : 'is-missing'; ?>">
						<?php echo ! empty( $preview_name ) ? esc_html__( 'Complete', 'saman-seo' ) : esc_html__( 'Missing', 'saman-seo' ); ?>
					</span>
				</div>
				<div class="saman-seo-module-status-card__item">
					<span class="saman-seo-module-status-card__label"><?php esc_html_e( 'Address', 'saman-seo' ); ?></span>
					<span class="saman-seo-module-status-card__value <?php echo ! empty( $street ) && ! empty( $city ) ? 'is-complete' : 'is-incomplete'; ?>">
						<?php echo ! empty( $street ) && ! empty( $city ) ? esc_html__( 'Complete', 'saman-seo' ) : esc_html__( 'Incomplete', 'saman-seo' ); ?>
					</span>
				</div>
				<div class="saman-seo-module-status-card__item">
					<span class="saman-seo-module-status-card__label"><?php esc_html_e( 'Phone', 'saman-seo' ); ?></span>
					<span class="saman-seo-module-status-card__value <?php echo ! empty( $phone ) ? 'is-complete' : 'is-incomplete'; ?>">
						<?php echo ! empty( $phone ) ? esc_html__( 'Complete', 'saman-seo' ) : esc_html__( 'Not set', 'saman-seo' ); ?>
					</span>
				</div>
				<div class="saman-seo-module-status-card__item">
					<span class="saman-seo-module-status-card__label"><?php esc_html_e( 'Opening Hours', 'saman-seo' ); ?></span>
					<?php
					$hours_set = false;
					if ( ! empty( $opening_hours ) ) {
						foreach ( $opening_hours as $day_hours ) {
							if ( ! empty( $day_hours['enabled'] ) && '1' === $day_hours['enabled'] ) {
								$hours_set = true;
								break;
							}
						}
					}
					?>
					<span class="saman-seo-module-status-card__value <?php echo $hours_set ? 'is-complete' : 'is-incomplete'; ?>">
						<?php echo $hours_set ? esc_html__( 'Configured', 'saman-seo' ) : esc_html__( 'Not set', 'saman-seo' ); ?>
					</span>
				</div>
				<div class="saman-seo-module-status-card__item">
					<span class="saman-seo-module-status-card__label"><?php esc_html_e( 'Coordinates', 'saman-seo' ); ?></span>
					<span class="saman-seo-module-status-card__value <?php echo ! empty( $latitude ) && ! empty( $longitude ) ? 'is-complete' : 'is-incomplete'; ?>">
						<?php echo ! empty( $latitude ) && ! empty( $longitude ) ? esc_html__( 'Set', 'saman-seo' ) : esc_html__( 'Not set', 'saman-seo' ); ?>
					</span>
				</div>
			</div>

			<!-- Quick Actions Card -->
			<div class="saman-seo-quick-actions">
				<h3><?php esc_html_e( 'Quick Actions', 'saman-seo' ); ?></h3>
				<div class="saman-seo-quick-actions__list">
					<a href="https://search.google.com/test/rich-results" class="button button-secondary" target="_blank" rel="noopener">
						<span class="dashicons dashicons-external"></span>
						<?php esc_html_e( 'Test with Google Rich Results', 'saman-seo' ); ?>
					</a>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-secondary" target="_blank" rel="noopener">
						<span class="dashicons dashicons-visibility"></span>
						<?php esc_html_e( 'View Homepage', 'saman-seo' ); ?>
					</a>
				</div>
			</div>
		</div>
	</div>

</div>
