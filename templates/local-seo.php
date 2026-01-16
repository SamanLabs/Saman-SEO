<?php
/**
 * Local SEO Settings Template
 *
 * @var array $business_types Available business types.
 *
 * @package WPSEOPilot
 */

defined( 'ABSPATH' ) || exit;

// Get current values.
$business_name  = get_option( 'wpseopilot_local_business_name', '' );
$business_type  = get_option( 'wpseopilot_local_business_type', 'LocalBusiness' );
$description    = get_option( 'wpseopilot_local_description', '' );
$logo           = get_option( 'wpseopilot_local_logo', '' );
$image          = get_option( 'wpseopilot_local_image', '' );
$price_range    = get_option( 'wpseopilot_local_price_range', '' );
$phone          = get_option( 'wpseopilot_local_phone', '' );
$email          = get_option( 'wpseopilot_local_email', '' );
$street         = get_option( 'wpseopilot_local_street', '' );
$city           = get_option( 'wpseopilot_local_city', '' );
$state          = get_option( 'wpseopilot_local_state', '' );
$zip            = get_option( 'wpseopilot_local_zip', '' );
$country        = get_option( 'wpseopilot_local_country', '' );
$latitude       = get_option( 'wpseopilot_local_latitude', '' );
$longitude      = get_option( 'wpseopilot_local_longitude', '' );
$social_profiles = get_option( 'wpseopilot_local_social_profiles', [] );
$opening_hours  = get_option( 'wpseopilot_local_opening_hours', [] );

// Ensure social profiles is array.
if ( ! is_array( $social_profiles ) ) {
	$social_profiles = [];
}

// Render top bar.
\WPSEOPilot\Admin_Topbar::render( 'local-seo' );
?>

<div class="wrap wpseopilot-page wpseopilot-local-seo-page">

	<div class="wpseopilot-tabs" data-component="wpseopilot-tabs">
		<div class="nav-tab-wrapper wpseopilot-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Local SEO sections', 'saman-labs-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="wpseopilot-tab-link-business-info"
				role="tab"
				aria-selected="true"
				aria-controls="wpseopilot-tab-business-info"
				data-wpseopilot-tab="wpseopilot-tab-business-info"
			>
				<?php esc_html_e( 'Business Information', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-opening-hours"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-opening-hours"
				data-wpseopilot-tab="wpseopilot-tab-opening-hours"
			>
				<?php esc_html_e( 'Opening Hours', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="wpseopilot-tab-link-schema-preview"
				role="tab"
				aria-selected="false"
				aria-controls="wpseopilot-tab-schema-preview"
				data-wpseopilot-tab="wpseopilot-tab-schema-preview"
			>
				<?php esc_html_e( 'Schema Preview', 'saman-labs-seo' ); ?>
			</button>
		</div>

		<!-- Business Information Tab -->
		<div
			id="wpseopilot-tab-business-info"
			class="wpseopilot-tab-panel is-active"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-business-info"
		>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpseopilot_local_seo' ); ?>

				<!-- Business Details Card -->
				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Business Details', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Basic information about your business that will appear in search results and knowledge panels.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_business_name">
								<strong><?php esc_html_e( 'Business Name', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Official name of your business (required)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="wpseopilot_local_business_name"
								name="wpseopilot_local_business_name"
								value="<?php echo esc_attr( $business_name ); ?>"
								class="regular-text"
								required
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_business_type">
								<strong><?php esc_html_e( 'Business Type', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Select the type that best describes your business', 'saman-labs-seo' ); ?></span>
							</label>
							<select id="wpseopilot_local_business_type" name="wpseopilot_local_business_type" class="regular-text">
								<?php foreach ( $business_types as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $business_type, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_description">
								<strong><?php esc_html_e( 'Business Description', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Short, human-readable description of your business', 'saman-labs-seo' ); ?></span>
							</label>
							<textarea
								id="wpseopilot_local_description"
								name="wpseopilot_local_description"
								rows="3"
								class="large-text"
							><?php echo esc_textarea( $description ); ?></textarea>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_logo">
								<strong><?php esc_html_e( 'Business Logo', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Official logo of your business', 'saman-labs-seo' ); ?></span>
							</label>
							<div class="wpseopilot-media-field">
								<input
									type="url"
									id="wpseopilot_local_logo"
									name="wpseopilot_local_logo"
									value="<?php echo esc_url( $logo ); ?>"
									class="regular-text"
								/>
								<button type="button" class="button wpseopilot-media-trigger"><?php esc_html_e( 'Select image', 'saman-labs-seo' ); ?></button>
							</div>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_image">
								<strong><?php esc_html_e( 'Cover Image', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Featured image representing your business', 'saman-labs-seo' ); ?></span>
							</label>
							<div class="wpseopilot-media-field">
								<input
									type="url"
									id="wpseopilot_local_image"
									name="wpseopilot_local_image"
									value="<?php echo esc_url( $image ); ?>"
									class="regular-text"
								/>
								<button type="button" class="button wpseopilot-media-trigger"><?php esc_html_e( 'Select image', 'saman-labs-seo' ); ?></button>
							</div>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_price_range">
								<strong><?php esc_html_e( 'Price Range', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Use $ symbols (e.g., $, $$, $$$, $$$$)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="wpseopilot_local_price_range"
								name="wpseopilot_local_price_range"
								value="<?php echo esc_attr( $price_range ); ?>"
								class="small-text"
								placeholder="$$"
							/>
						</div>

					</div>
				</div>

				<!-- Contact Information Card -->
				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Contact Information', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'How customers can reach your business.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_phone">
								<strong><?php esc_html_e( 'Phone Number', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Include country code (e.g., +1-305-555-1234)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="tel"
								id="wpseopilot_local_phone"
								name="wpseopilot_local_phone"
								value="<?php echo esc_attr( $phone ); ?>"
								class="regular-text"
								placeholder="+1-555-555-5555"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_email">
								<strong><?php esc_html_e( 'Email Address', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Public contact email for your business', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="email"
								id="wpseopilot_local_email"
								name="wpseopilot_local_email"
								value="<?php echo esc_attr( $email ); ?>"
								class="regular-text"
								placeholder="contact@example.com"
							/>
						</div>

					</div>
				</div>

				<!-- Address Card -->
				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Business Address', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Physical location of your business for local search results.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_street">
								<strong><?php esc_html_e( 'Street Address', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_street"
								name="wpseopilot_local_street"
								value="<?php echo esc_attr( $street ); ?>"
								class="regular-text"
								placeholder="123 Main Street"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_city">
								<strong><?php esc_html_e( 'City', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_city"
								name="wpseopilot_local_city"
								value="<?php echo esc_attr( $city ); ?>"
								class="regular-text"
								placeholder="Miami"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_state">
								<strong><?php esc_html_e( 'State / Province', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_state"
								name="wpseopilot_local_state"
								value="<?php echo esc_attr( $state ); ?>"
								class="regular-text"
								placeholder="FL"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_zip">
								<strong><?php esc_html_e( 'Postal Code', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_zip"
								name="wpseopilot_local_zip"
								value="<?php echo esc_attr( $zip ); ?>"
								class="small-text"
								placeholder="33101"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_country">
								<strong><?php esc_html_e( 'Country', 'saman-labs-seo' ); ?></strong>
								<span class="wpseopilot-label-hint"><?php esc_html_e( 'Two-letter country code (e.g., US, GB, CA)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="wpseopilot_local_country"
								name="wpseopilot_local_country"
								value="<?php echo esc_attr( $country ); ?>"
								class="small-text"
								placeholder="US"
								maxlength="2"
							/>
						</div>

					</div>
				</div>

				<!-- Geo Coordinates Card -->
				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Geo Coordinates', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Precise location coordinates improve map accuracy and local search rankings.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_latitude">
								<strong><?php esc_html_e( 'Latitude', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_latitude"
								name="wpseopilot_local_latitude"
								value="<?php echo esc_attr( $latitude ); ?>"
								class="regular-text"
								placeholder="25.761681"
							/>
						</div>

						<div class="wpseopilot-form-row">
							<label for="wpseopilot_local_longitude">
								<strong><?php esc_html_e( 'Longitude', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="wpseopilot_local_longitude"
								name="wpseopilot_local_longitude"
								value="<?php echo esc_attr( $longitude ); ?>"
								class="regular-text"
								placeholder="-80.191788"
							/>
						</div>

						<p class="description">
							<?php
							printf(
								/* translators: %s: Google Maps geocoding URL */
								esc_html__( 'Find coordinates using %s', 'saman-labs-seo' ),
								'<a href="https://www.google.com/maps" target="_blank" rel="noopener">' . esc_html__( 'Google Maps', 'saman-labs-seo' ) . '</a>'
							);
							?>
						</p>

					</div>
				</div>

				<!-- Social Profiles Card -->
				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Social Profiles', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Links to your business social media profiles for brand verification.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<?php
						$profile_count = max( count( $social_profiles ), 3 );
						for ( $i = 0; $i < $profile_count + 1; $i++ ) :
							$profile_url = $social_profiles[ $i ] ?? '';
							?>
							<div class="wpseopilot-form-row">
								<label for="wpseopilot_local_social_<?php echo esc_attr( $i ); ?>">
									<strong><?php echo esc_html( sprintf( __( 'Social Profile %d', 'saman-labs-seo' ), $i + 1 ) ); ?></strong>
								</label>
								<input
									type="url"
									id="wpseopilot_local_social_<?php echo esc_attr( $i ); ?>"
									name="wpseopilot_local_social_profiles[]"
									value="<?php echo esc_url( $profile_url ); ?>"
									class="regular-text"
									placeholder="https://facebook.com/yourpage"
								/>
							</div>
						<?php endfor; ?>

						<p class="description">
							<?php esc_html_e( 'Add URLs to your official social media profiles (Facebook, Instagram, LinkedIn, Twitter, etc.). Only include profiles you actually maintain.', 'saman-labs-seo' ); ?>
						</p>

					</div>
				</div>

				<?php submit_button( __( 'Save Business Information', 'saman-labs-seo' ) ); ?>
			</form>
		</div>

		<!-- Opening Hours Tab -->
		<div
			id="wpseopilot-tab-opening-hours"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-opening-hours"
		>
			<form action="options.php" method="post">
				<?php settings_fields( 'wpseopilot_local_seo' ); ?>

				<div class="wpseopilot-card">
					<div class="wpseopilot-card-header">
						<h2><?php esc_html_e( 'Opening Hours', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure your business hours for each day of the week. Leave days unchecked if closed.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="wpseopilot-card-body">

						<?php
						$days = [
							'monday'    => __( 'Monday', 'saman-labs-seo' ),
							'tuesday'   => __( 'Tuesday', 'saman-labs-seo' ),
							'wednesday' => __( 'Wednesday', 'saman-labs-seo' ),
							'thursday'  => __( 'Thursday', 'saman-labs-seo' ),
							'friday'    => __( 'Friday', 'saman-labs-seo' ),
							'saturday'  => __( 'Saturday', 'saman-labs-seo' ),
							'sunday'    => __( 'Sunday', 'saman-labs-seo' ),
						];

						foreach ( $days as $day => $label ) :
							$day_data = $opening_hours[ $day ] ?? [];
							$enabled  = ! empty( $day_data['enabled'] ) && '1' === $day_data['enabled'];
							$open     = $day_data['open'] ?? '09:00';
							$close    = $day_data['close'] ?? '17:00';
							?>
							<div class="wpseopilot-opening-hours-row">
								<label class="wpseopilot-day-toggle">
									<input
										type="checkbox"
										name="wpseopilot_local_opening_hours[<?php echo esc_attr( $day ); ?>][enabled]"
										value="1"
										<?php checked( $enabled ); ?>
									/>
									<strong><?php echo esc_html( $label ); ?></strong>
								</label>
								<div class="wpseopilot-hours-inputs">
									<input
										type="time"
										name="wpseopilot_local_opening_hours[<?php echo esc_attr( $day ); ?>][open]"
										value="<?php echo esc_attr( $open ); ?>"
										class="wpseopilot-time-input"
									/>
									<span class="wpseopilot-hours-separator">—</span>
									<input
										type="time"
										name="wpseopilot_local_opening_hours[<?php echo esc_attr( $day ); ?>][close]"
										value="<?php echo esc_attr( $close ); ?>"
										class="wpseopilot-time-input"
									/>
								</div>
							</div>
						<?php endforeach; ?>

						<p class="description">
							<?php esc_html_e( 'Use 24-hour format (HH:MM). Unchecked days will not appear in your opening hours schema.', 'saman-labs-seo' ); ?>
						</p>

					</div>
				</div>

				<?php submit_button( __( 'Save Opening Hours', 'saman-labs-seo' ) ); ?>
			</form>
		</div>

		<!-- Schema Preview Tab -->
		<div
			id="wpseopilot-tab-schema-preview"
			class="wpseopilot-tab-panel"
			role="tabpanel"
			aria-labelledby="wpseopilot-tab-link-schema-preview"
		>
			<div class="wpseopilot-card">
				<div class="wpseopilot-card-header">
					<h2><?php esc_html_e( 'Schema Markup Preview', 'saman-labs-seo' ); ?></h2>
					<p><?php esc_html_e( 'Preview of the LocalBusiness JSON-LD schema that will be output on your homepage.', 'saman-labs-seo' ); ?></p>
				</div>
				<div class="wpseopilot-card-body">

					<?php
					$local_seo_service = new \WPSEOPilot\Service\Local_SEO();
					$schema_method = new ReflectionMethod( $local_seo_service, 'build_schema' );
					$schema_method->setAccessible( true );
					$schema = $schema_method->invoke( $local_seo_service );

					if ( ! empty( $schema ) ) :
						?>
						<pre class="wpseopilot-schema-preview"><code><?php echo esc_html( wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) ); ?></code></pre>
						<p class="description">
							<?php esc_html_e( 'This schema markup is automatically added to your homepage. Test it using Google\'s Rich Results Test.', 'saman-labs-seo' ); ?>
						</p>
						<a href="https://search.google.com/test/rich-results" class="button button-secondary" target="_blank" rel="noopener">
							<?php esc_html_e( 'Test with Google', 'saman-labs-seo' ); ?>
						</a>
					<?php else : ?>
						<p class="description">
							<?php esc_html_e( 'Complete the Business Information tab to see your schema preview. At minimum, a business name is required.', 'saman-labs-seo' ); ?>
						</p>
					<?php endif; ?>

				</div>
			</div>
		</div>

	</div>

</div>
