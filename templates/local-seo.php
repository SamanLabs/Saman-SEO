<?php
/**
 * Local SEO Settings Template
 *
 * @var array $business_types Available business types.
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

// Get current values.
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

// Ensure social profiles is array.
if ( ! is_array( $social_profiles ) ) {
	$social_profiles = [];
}

// Render top bar.
\Saman\SEO\Admin_Topbar::render( 'local-seo' );
?>

<div class="wrap saman-seo-page saman-seo-local-seo-page">

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
			<button
				type="button"
				class="nav-tab"
				id="saman-seo-tab-link-schema-preview"
				role="tab"
				aria-selected="false"
				aria-controls="saman-seo-tab-schema-preview"
				data-saman-seo-tab="saman-seo-tab-schema-preview"
			>
				<?php esc_html_e( 'Schema Preview', 'saman-seo' ); ?>
			</button>
		</div>

		<!-- Business Information Tab -->
		<div
			id="saman-seo-tab-business-info"
			class="saman-seo-tab-panel is-active"
			role="tabpanel"
			aria-labelledby="saman-seo-tab-link-business-info"
		>
			<form action="options.php" method="post">
				<?php settings_fields( 'SAMAN_SEO_local_seo' ); ?>

				<!-- Business Details Card -->
				<div class="saman-seo-card">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Business Details', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Basic information about your business that will appear in search results and knowledge panels.', 'saman-seo' ); ?></p>
					</div>
					<div class="saman-seo-card-body">

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
								required
							/>
						</div>

						<div class="saman-seo-form-row">
							<label for="SAMAN_SEO_local_business_type">
								<strong><?php esc_html_e( 'Business Type', 'saman-seo' ); ?></strong>
								<span class="saman-seo-label-hint"><?php esc_html_e( 'Select the type that best describes your business', 'saman-seo' ); ?></span>
							</label>
							<select id="SAMAN_SEO_local_business_type" name="SAMAN_SEO_local_business_type" class="regular-text">
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
							><?php echo esc_textarea( $description ); ?></textarea>
						</div>

						<div class="saman-seo-form-row">
							<label for="SAMAN_SEO_local_logo">
								<strong><?php esc_html_e( 'Business Logo', 'saman-seo' ); ?></strong>
								<span class="saman-seo-label-hint"><?php esc_html_e( 'Official logo of your business', 'saman-seo' ); ?></span>
							</label>
							<div class="saman-seo-media-field">
								<input
									type="url"
									id="SAMAN_SEO_local_logo"
									name="SAMAN_SEO_local_logo"
									value="<?php echo esc_url( $logo ); ?>"
									class="regular-text"
								/>
								<button type="button" class="button saman-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-seo' ); ?></button>
							</div>
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
							/>
						</div>

						<p class="description">
							<?php
							printf(
								/* translators: %s: Google Maps geocoding URL */
								esc_html__( 'Find coordinates using %s', 'saman-seo' ),
								'<a href="https://www.google.com/maps" target="_blank" rel="noopener">' . esc_html__( 'Google Maps', 'saman-seo' ) . '</a>'
							);
							?>
						</p>

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
									<strong><?php echo esc_html( sprintf( __( 'Social Profile %d', 'saman-seo' ), $i + 1 ) ); ?></strong>
								</label>
								<input
									type="url"
									id="SAMAN_SEO_local_social_<?php echo esc_attr( $i ); ?>"
									name="SAMAN_SEO_local_social_profiles[]"
									value="<?php echo esc_url( $profile_url ); ?>"
									class="regular-text"
									placeholder="https://facebook.com/yourpage"
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
			<form action="options.php" method="post">
				<?php settings_fields( 'SAMAN_SEO_local_seo' ); ?>

				<div class="saman-seo-card">
					<div class="saman-seo-card-header">
						<h2><?php esc_html_e( 'Opening Hours', 'saman-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure your business hours for each day of the week. Leave days unchecked if closed.', 'saman-seo' ); ?></p>
					</div>
					<div class="saman-seo-card-body">

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
							?>
							<div class="saman-seo-opening-hours-row">
								<label class="saman-seo-day-toggle">
									<input
										type="checkbox"
										name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][enabled]"
										value="1"
										<?php checked( $enabled ); ?>
									/>
									<strong><?php echo esc_html( $label ); ?></strong>
								</label>
								<div class="saman-seo-hours-inputs">
									<input
										type="time"
										name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][open]"
										value="<?php echo esc_attr( $open ); ?>"
										class="saman-seo-time-input"
									/>
									<span class="saman-seo-hours-separator">Ã¢â‚¬â€</span>
									<input
										type="time"
										name="SAMAN_SEO_local_opening_hours[<?php echo esc_attr( $day ); ?>][close]"
										value="<?php echo esc_attr( $close ); ?>"
										class="saman-seo-time-input"
									/>
								</div>
							</div>
						<?php endforeach; ?>

						<p class="description">
							<?php esc_html_e( 'Use 24-hour format (HH:MM). Unchecked days will not appear in your opening hours schema.', 'saman-seo' ); ?>
						</p>

					</div>
				</div>

				<?php submit_button( __( 'Save Opening Hours', 'saman-seo' ) ); ?>
			</form>
		</div>

		<!-- Schema Preview Tab -->
		<div
			id="saman-seo-tab-schema-preview"
			class="saman-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="saman-seo-tab-link-schema-preview"
		>
			<div class="saman-seo-card">
				<div class="saman-seo-card-header">
					<h2><?php esc_html_e( 'Schema Markup Preview', 'saman-seo' ); ?></h2>
					<p><?php esc_html_e( 'Preview of the LocalBusiness JSON-LD schema that will be output on your homepage.', 'saman-seo' ); ?></p>
				</div>
				<div class="saman-seo-card-body">

					<?php
					$local_seo_service = new \Saman\SEO\Service\Local_SEO();
					$schema_method = new ReflectionMethod( $local_seo_service, 'build_schema' );
					$schema_method->setAccessible( true );
					$schema = $schema_method->invoke( $local_seo_service );

					if ( ! empty( $schema ) ) :
						?>
						<pre class="saman-seo-schema-preview"><code><?php echo esc_html( wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) ); ?></code></pre>
						<p class="description">
							<?php esc_html_e( 'This schema markup is automatically added to your homepage. Test it using Google\'s Rich Results Test.', 'saman-seo' ); ?>
						</p>
						<a href="https://search.google.com/test/rich-results" class="button button-secondary" target="_blank" rel="noopener">
							<?php esc_html_e( 'Test with Google', 'saman-seo' ); ?>
						</a>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'Complete the Business Information tab to see your schema preview. At minimum, a business name is required.', 'saman-seo' ); ?>
						</p>
					<?php endif; ?>

				</div>
			</div>
		</div>

	</div>

</div>
