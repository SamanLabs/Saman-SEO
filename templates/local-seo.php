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
$business_name  = get_option( 'samanlabs_seo_local_business_name', '' );
$business_type  = get_option( 'samanlabs_seo_local_business_type', 'LocalBusiness' );
$description    = get_option( 'samanlabs_seo_local_description', '' );
$logo           = get_option( 'samanlabs_seo_local_logo', '' );
$image          = get_option( 'samanlabs_seo_local_image', '' );
$price_range    = get_option( 'samanlabs_seo_local_price_range', '' );
$phone          = get_option( 'samanlabs_seo_local_phone', '' );
$email          = get_option( 'samanlabs_seo_local_email', '' );
$street         = get_option( 'samanlabs_seo_local_street', '' );
$city           = get_option( 'samanlabs_seo_local_city', '' );
$state          = get_option( 'samanlabs_seo_local_state', '' );
$zip            = get_option( 'samanlabs_seo_local_zip', '' );
$country        = get_option( 'samanlabs_seo_local_country', '' );
$latitude       = get_option( 'samanlabs_seo_local_latitude', '' );
$longitude      = get_option( 'samanlabs_seo_local_longitude', '' );
$social_profiles = get_option( 'samanlabs_seo_local_social_profiles', [] );
$opening_hours  = get_option( 'samanlabs_seo_local_opening_hours', [] );

// Ensure social profiles is array.
if ( ! is_array( $social_profiles ) ) {
	$social_profiles = [];
}

// Render top bar.
\WPSEOPilot\Admin_Topbar::render( 'local-seo' );
?>

<div class="wrap samanlabs-seo-page samanlabs-seo-local-seo-page">

	<div class="samanlabs-seo-tabs" data-component="samanlabs-seo-tabs">
		<div class="nav-tab-wrapper samanlabs-seo-tabs__nav" role="tablist" aria-label="<?php esc_attr_e( 'Local SEO sections', 'saman-labs-seo' ); ?>">
			<button
				type="button"
				class="nav-tab nav-tab-active"
				id="samanlabs-seo-tab-link-business-info"
				role="tab"
				aria-selected="true"
				aria-controls="samanlabs-seo-tab-business-info"
				data-samanlabs-seo-tab="samanlabs-seo-tab-business-info"
			>
				<?php esc_html_e( 'Business Information', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-opening-hours"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-opening-hours"
				data-samanlabs-seo-tab="samanlabs-seo-tab-opening-hours"
			>
				<?php esc_html_e( 'Opening Hours', 'saman-labs-seo' ); ?>
			</button>
			<button
				type="button"
				class="nav-tab"
				id="samanlabs-seo-tab-link-schema-preview"
				role="tab"
				aria-selected="false"
				aria-controls="samanlabs-seo-tab-schema-preview"
				data-samanlabs-seo-tab="samanlabs-seo-tab-schema-preview"
			>
				<?php esc_html_e( 'Schema Preview', 'saman-labs-seo' ); ?>
			</button>
		</div>

		<!-- Business Information Tab -->
		<div
			id="samanlabs-seo-tab-business-info"
			class="samanlabs-seo-tab-panel is-active"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-business-info"
		>
			<form action="options.php" method="post">
				<?php settings_fields( 'samanlabs_seo_local_seo' ); ?>

				<!-- Business Details Card -->
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Business Details', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Basic information about your business that will appear in search results and knowledge panels.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_business_name">
								<strong><?php esc_html_e( 'Business Name', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Official name of your business (required)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_business_name"
								name="samanlabs_seo_local_business_name"
								value="<?php echo esc_attr( $business_name ); ?>"
								class="regular-text"
								required
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_business_type">
								<strong><?php esc_html_e( 'Business Type', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Select the type that best describes your business', 'saman-labs-seo' ); ?></span>
							</label>
							<select id="samanlabs_seo_local_business_type" name="samanlabs_seo_local_business_type" class="regular-text">
								<?php foreach ( $business_types as $value => $label ) : ?>
									<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $business_type, $value ); ?>>
										<?php echo esc_html( $label ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_description">
								<strong><?php esc_html_e( 'Business Description', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Short, human-readable description of your business', 'saman-labs-seo' ); ?></span>
							</label>
							<textarea
								id="samanlabs_seo_local_description"
								name="samanlabs_seo_local_description"
								rows="3"
								class="large-text"
							><?php echo esc_textarea( $description ); ?></textarea>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_logo">
								<strong><?php esc_html_e( 'Business Logo', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Official logo of your business', 'saman-labs-seo' ); ?></span>
							</label>
							<div class="samanlabs-seo-media-field">
								<input
									type="url"
									id="samanlabs_seo_local_logo"
									name="samanlabs_seo_local_logo"
									value="<?php echo esc_url( $logo ); ?>"
									class="regular-text"
								/>
								<button type="button" class="button samanlabs-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-labs-seo' ); ?></button>
							</div>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_image">
								<strong><?php esc_html_e( 'Cover Image', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Featured image representing your business', 'saman-labs-seo' ); ?></span>
							</label>
							<div class="samanlabs-seo-media-field">
								<input
									type="url"
									id="samanlabs_seo_local_image"
									name="samanlabs_seo_local_image"
									value="<?php echo esc_url( $image ); ?>"
									class="regular-text"
								/>
								<button type="button" class="button samanlabs-seo-media-trigger"><?php esc_html_e( 'Select image', 'saman-labs-seo' ); ?></button>
							</div>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_price_range">
								<strong><?php esc_html_e( 'Price Range', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Use $ symbols (e.g., $, $$, $$$, $$$$)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_price_range"
								name="samanlabs_seo_local_price_range"
								value="<?php echo esc_attr( $price_range ); ?>"
								class="small-text"
								placeholder="$$"
							/>
						</div>

					</div>
				</div>

				<!-- Contact Information Card -->
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Contact Information', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'How customers can reach your business.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_phone">
								<strong><?php esc_html_e( 'Phone Number', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Include country code (e.g., +1-305-555-1234)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="tel"
								id="samanlabs_seo_local_phone"
								name="samanlabs_seo_local_phone"
								value="<?php echo esc_attr( $phone ); ?>"
								class="regular-text"
								placeholder="+1-555-555-5555"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_email">
								<strong><?php esc_html_e( 'Email Address', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Public contact email for your business', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="email"
								id="samanlabs_seo_local_email"
								name="samanlabs_seo_local_email"
								value="<?php echo esc_attr( $email ); ?>"
								class="regular-text"
								placeholder="contact@example.com"
							/>
						</div>

					</div>
				</div>

				<!-- Address Card -->
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Business Address', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Physical location of your business for local search results.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_street">
								<strong><?php esc_html_e( 'Street Address', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_street"
								name="samanlabs_seo_local_street"
								value="<?php echo esc_attr( $street ); ?>"
								class="regular-text"
								placeholder="123 Main Street"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_city">
								<strong><?php esc_html_e( 'City', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_city"
								name="samanlabs_seo_local_city"
								value="<?php echo esc_attr( $city ); ?>"
								class="regular-text"
								placeholder="Miami"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_state">
								<strong><?php esc_html_e( 'State / Province', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_state"
								name="samanlabs_seo_local_state"
								value="<?php echo esc_attr( $state ); ?>"
								class="regular-text"
								placeholder="FL"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_zip">
								<strong><?php esc_html_e( 'Postal Code', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_zip"
								name="samanlabs_seo_local_zip"
								value="<?php echo esc_attr( $zip ); ?>"
								class="small-text"
								placeholder="33101"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_country">
								<strong><?php esc_html_e( 'Country', 'saman-labs-seo' ); ?></strong>
								<span class="samanlabs-seo-label-hint"><?php esc_html_e( 'Two-letter country code (e.g., US, GB, CA)', 'saman-labs-seo' ); ?></span>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_country"
								name="samanlabs_seo_local_country"
								value="<?php echo esc_attr( $country ); ?>"
								class="small-text"
								placeholder="US"
								maxlength="2"
							/>
						</div>

					</div>
				</div>

				<!-- Geo Coordinates Card -->
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Geo Coordinates', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Precise location coordinates improve map accuracy and local search rankings.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_latitude">
								<strong><?php esc_html_e( 'Latitude', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_latitude"
								name="samanlabs_seo_local_latitude"
								value="<?php echo esc_attr( $latitude ); ?>"
								class="regular-text"
								placeholder="25.761681"
							/>
						</div>

						<div class="samanlabs-seo-form-row">
							<label for="samanlabs_seo_local_longitude">
								<strong><?php esc_html_e( 'Longitude', 'saman-labs-seo' ); ?></strong>
							</label>
							<input
								type="text"
								id="samanlabs_seo_local_longitude"
								name="samanlabs_seo_local_longitude"
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
				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Social Profiles', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Links to your business social media profiles for brand verification.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

						<?php
						$profile_count = max( count( $social_profiles ), 3 );
						for ( $i = 0; $i < $profile_count + 1; $i++ ) :
							$profile_url = $social_profiles[ $i ] ?? '';
							?>
							<div class="samanlabs-seo-form-row">
								<label for="samanlabs_seo_local_social_<?php echo esc_attr( $i ); ?>">
									<strong><?php echo esc_html( sprintf( __( 'Social Profile %d', 'saman-labs-seo' ), $i + 1 ) ); ?></strong>
								</label>
								<input
									type="url"
									id="samanlabs_seo_local_social_<?php echo esc_attr( $i ); ?>"
									name="samanlabs_seo_local_social_profiles[]"
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
			id="samanlabs-seo-tab-opening-hours"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-opening-hours"
		>
			<form action="options.php" method="post">
				<?php settings_fields( 'samanlabs_seo_local_seo' ); ?>

				<div class="samanlabs-seo-card">
					<div class="samanlabs-seo-card-header">
						<h2><?php esc_html_e( 'Opening Hours', 'saman-labs-seo' ); ?></h2>
						<p><?php esc_html_e( 'Configure your business hours for each day of the week. Leave days unchecked if closed.', 'saman-labs-seo' ); ?></p>
					</div>
					<div class="samanlabs-seo-card-body">

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
							<div class="samanlabs-seo-opening-hours-row">
								<label class="samanlabs-seo-day-toggle">
									<input
										type="checkbox"
										name="samanlabs_seo_local_opening_hours[<?php echo esc_attr( $day ); ?>][enabled]"
										value="1"
										<?php checked( $enabled ); ?>
									/>
									<strong><?php echo esc_html( $label ); ?></strong>
								</label>
								<div class="samanlabs-seo-hours-inputs">
									<input
										type="time"
										name="samanlabs_seo_local_opening_hours[<?php echo esc_attr( $day ); ?>][open]"
										value="<?php echo esc_attr( $open ); ?>"
										class="samanlabs-seo-time-input"
									/>
									<span class="samanlabs-seo-hours-separator">—</span>
									<input
										type="time"
										name="samanlabs_seo_local_opening_hours[<?php echo esc_attr( $day ); ?>][close]"
										value="<?php echo esc_attr( $close ); ?>"
										class="samanlabs-seo-time-input"
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
			id="samanlabs-seo-tab-schema-preview"
			class="samanlabs-seo-tab-panel"
			role="tabpanel"
			aria-labelledby="samanlabs-seo-tab-link-schema-preview"
		>
			<div class="samanlabs-seo-card">
				<div class="samanlabs-seo-card-header">
					<h2><?php esc_html_e( 'Schema Markup Preview', 'saman-labs-seo' ); ?></h2>
					<p><?php esc_html_e( 'Preview of the LocalBusiness JSON-LD schema that will be output on your homepage.', 'saman-labs-seo' ); ?></p>
				</div>
				<div class="samanlabs-seo-card-body">

					<?php
					$local_seo_service = new \WPSEOPilot\Service\Local_SEO();
					$schema_method = new ReflectionMethod( $local_seo_service, 'build_schema' );
					$schema_method->setAccessible( true );
					$schema = $schema_method->invoke( $local_seo_service );

					if ( ! empty( $schema ) ) :
						?>
						<pre class="samanlabs-seo-schema-preview"><code><?php echo esc_html( wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) ); ?></code></pre>
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
