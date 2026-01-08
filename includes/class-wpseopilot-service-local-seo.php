<?php
/**
 * Local SEO service for business schema and local search optimization.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Local SEO controller.
 */
class Local_SEO {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		// Only initialize if module is enabled.
		if ( '1' !== get_option( 'wpseopilot_enable_local_seo', '0' ) ) {
			return;
		}

		add_action( 'admin_menu', [ $this, 'register_menu' ], 100 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'wpseopilot_jsonld_graph', [ $this, 'add_local_business_to_graph' ], 20, 1 );
	}

	/**
	 * Register submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'wpseopilot',
			__( 'Local SEO', 'wp-seo-pilot' ),
			__( 'Local SEO', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-local-seo',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$group = 'wpseopilot_local_seo';

		// Business Information.
		register_setting( $group, 'wpseopilot_local_business_name', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_business_type', [ $this, 'sanitize_business_type' ] );
		register_setting( $group, 'wpseopilot_local_description', 'sanitize_textarea_field' );
		register_setting( $group, 'wpseopilot_local_logo', 'esc_url_raw' );
		register_setting( $group, 'wpseopilot_local_image', 'esc_url_raw' );
		register_setting( $group, 'wpseopilot_local_price_range', 'sanitize_text_field' );

		// Contact Information.
		register_setting( $group, 'wpseopilot_local_phone', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_email', 'sanitize_email' );

		// Address.
		register_setting( $group, 'wpseopilot_local_street', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_city', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_state', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_zip', 'sanitize_text_field' );
		register_setting( $group, 'wpseopilot_local_country', 'sanitize_text_field' );

		// Geo Coordinates.
		register_setting( $group, 'wpseopilot_local_latitude', [ $this, 'sanitize_coordinate' ] );
		register_setting( $group, 'wpseopilot_local_longitude', [ $this, 'sanitize_coordinate' ] );

		// Social Profiles.
		register_setting( $group, 'wpseopilot_local_social_profiles', [ $this, 'sanitize_social_profiles' ] );

		// Opening Hours.
		register_setting( $group, 'wpseopilot_local_opening_hours', [ $this, 'sanitize_opening_hours' ] );

		// Multiple Locations.
		register_setting( $group, 'wpseopilot_local_enable_locations', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'wpseopilot_local_locations', [ $this, 'sanitize_locations' ] );
	}

	/**
	 * Sanitize boolean value.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	public function sanitize_bool( $value ) {
		return ! empty( $value ) ? '1' : '0';
	}

	/**
	 * Sanitize business type.
	 *
	 * @param string $value Business type.
	 * @return string
	 */
	public function sanitize_business_type( $value ) {
		$allowed = array_keys( $this->get_business_types() );
		return in_array( $value, $allowed, true ) ? $value : 'LocalBusiness';
	}

	/**
	 * Sanitize coordinate value.
	 *
	 * @param string $value Coordinate.
	 * @return string
	 */
	public function sanitize_coordinate( $value ) {
		$value = sanitize_text_field( $value );
		if ( ! is_numeric( $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Sanitize social profiles.
	 *
	 * @param array $value Social profiles.
	 * @return array
	 */
	public function sanitize_social_profiles( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		return array_values( array_filter( array_map( 'esc_url_raw', $value ) ) );
	}

	/**
	 * Sanitize opening hours.
	 *
	 * @param array $value Opening hours.
	 * @return array
	 */
	public function sanitize_opening_hours( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sanitized = [];
		$days = [ 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ];

		foreach ( $days as $day ) {
			if ( isset( $value[ $day ] ) && is_array( $value[ $day ] ) ) {
				$sanitized[ $day ] = [
					'enabled' => ! empty( $value[ $day ]['enabled'] ) ? '1' : '0',
					'open'    => sanitize_text_field( $value[ $day ]['open'] ?? '' ),
					'close'   => sanitize_text_field( $value[ $day ]['close'] ?? '' ),
				];
			}
		}

		return $sanitized;
	}

	/**
	 * Sanitize locations.
	 *
	 * @param array $value Locations.
	 * @return array
	 */
	public function sanitize_locations( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$sanitized = [];

		foreach ( $value as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}

			$sanitized[] = [
				'name'      => sanitize_text_field( $location['name'] ?? '' ),
				'street'    => sanitize_text_field( $location['street'] ?? '' ),
				'city'      => sanitize_text_field( $location['city'] ?? '' ),
				'state'     => sanitize_text_field( $location['state'] ?? '' ),
				'zip'       => sanitize_text_field( $location['zip'] ?? '' ),
				'country'   => sanitize_text_field( $location['country'] ?? '' ),
				'phone'     => sanitize_text_field( $location['phone'] ?? '' ),
				'latitude'  => $this->sanitize_coordinate( $location['latitude'] ?? '' ),
				'longitude' => $this->sanitize_coordinate( $location['longitude'] ?? '' ),
			];
		}

		return $sanitized;
	}

	/**
	 * Get available business types.
	 *
	 * @return array
	 */
	public function get_business_types() {
		return [
			'LocalBusiness'       => __( 'Local Business (Generic)', 'wp-seo-pilot' ),
			'Restaurant'          => __( 'Restaurant', 'wp-seo-pilot' ),
			'Dentist'             => __( 'Dentist', 'wp-seo-pilot' ),
			'Physician'           => __( 'Physician', 'wp-seo-pilot' ),
			'MedicalClinic'       => __( 'Medical Clinic', 'wp-seo-pilot' ),
			'Attorney'            => __( 'Attorney', 'wp-seo-pilot' ),
			'RealEstateAgent'     => __( 'Real Estate Agent', 'wp-seo-pilot' ),
			'Store'               => __( 'Store', 'wp-seo-pilot' ),
			'AutoDealer'          => __( 'Auto Dealer', 'wp-seo-pilot' ),
			'HairSalon'           => __( 'Hair Salon', 'wp-seo-pilot' ),
			'BeautySalon'         => __( 'Beauty Salon', 'wp-seo-pilot' ),
			'Plumber'             => __( 'Plumber', 'wp-seo-pilot' ),
			'Electrician'         => __( 'Electrician', 'wp-seo-pilot' ),
			'Locksmith'           => __( 'Locksmith', 'wp-seo-pilot' ),
			'AccountingService'   => __( 'Accounting Service', 'wp-seo-pilot' ),
			'FinancialService'    => __( 'Financial Service', 'wp-seo-pilot' ),
			'InsuranceAgency'     => __( 'Insurance Agency', 'wp-seo-pilot' ),
			'TravelAgency'        => __( 'Travel Agency', 'wp-seo-pilot' ),
			'AutomotiveBusiness'  => __( 'Automotive Business', 'wp-seo-pilot' ),
			'FoodEstablishment'   => __( 'Food Establishment', 'wp-seo-pilot' ),
			'EntertainmentBusiness' => __( 'Entertainment Business', 'wp-seo-pilot' ),
			'LodgingBusiness'     => __( 'Lodging Business', 'wp-seo-pilot' ),
			'SportsActivityLocation' => __( 'Sports Activity Location', 'wp-seo-pilot' ),
		];
	}

	/**
	 * Add Local Business schema to the JSON-LD graph.
	 *
	 * @param array $graph The existing JSON-LD graph.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_local_business_to_graph( $graph ) {
		// Only output on homepage or is_front_page by default.
		if ( ! is_front_page() && ! is_home() ) {
			return $graph;
		}

		$schema = $this->build_schema();

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Local Business schema.
	 *
	 * @return array|null
	 */
	private function build_schema() {
		$business_name = get_option( 'wpseopilot_local_business_name', '' );

		// Require at minimum a business name.
		if ( empty( $business_name ) ) {
			return null;
		}

		$business_type = get_option( 'wpseopilot_local_business_type', 'LocalBusiness' );
		$site_url = home_url( '/' );

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => $business_type,
			'@id'      => $site_url . '#localbusiness',
			'name'     => $business_name,
			'url'      => $site_url,
		];

		// Logo.
		$logo = get_option( 'wpseopilot_local_logo', '' );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = $logo;
		}

		// Image.
		$image = get_option( 'wpseopilot_local_image', '' );
		if ( ! empty( $image ) ) {
			$schema['image'] = $image;
		}

		// Description.
		$description = get_option( 'wpseopilot_local_description', '' );
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Phone.
		$phone = get_option( 'wpseopilot_local_phone', '' );
		if ( ! empty( $phone ) ) {
			$schema['telephone'] = $phone;
		}

		// Email.
		$email = get_option( 'wpseopilot_local_email', '' );
		if ( ! empty( $email ) ) {
			$schema['email'] = $email;
		}

		// Price Range.
		$price_range = get_option( 'wpseopilot_local_price_range', '' );
		if ( ! empty( $price_range ) ) {
			$schema['priceRange'] = $price_range;
		}

		// Address.
		$address = $this->build_address();
		if ( ! empty( $address ) ) {
			$schema['address'] = $address;
		}

		// Geo Coordinates.
		$geo = $this->build_geo();
		if ( ! empty( $geo ) ) {
			$schema['geo'] = $geo;
		}

		// Opening Hours.
		$opening_hours = $this->build_opening_hours();
		if ( ! empty( $opening_hours ) ) {
			$schema['openingHoursSpecification'] = $opening_hours;
		}

		// Social Profiles.
		$social_profiles = get_option( 'wpseopilot_local_social_profiles', [] );
		if ( ! empty( $social_profiles ) && is_array( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return $schema;
	}

	/**
	 * Build postal address schema.
	 *
	 * @return array|null
	 */
	private function build_address() {
		$street  = get_option( 'wpseopilot_local_street', '' );
		$city    = get_option( 'wpseopilot_local_city', '' );
		$state   = get_option( 'wpseopilot_local_state', '' );
		$zip     = get_option( 'wpseopilot_local_zip', '' );
		$country = get_option( 'wpseopilot_local_country', '' );

		// Require at least street and city.
		if ( empty( $street ) || empty( $city ) ) {
			return null;
		}

		$address = [
			'@type' => 'PostalAddress',
		];

		if ( ! empty( $street ) ) {
			$address['streetAddress'] = $street;
		}

		if ( ! empty( $city ) ) {
			$address['addressLocality'] = $city;
		}

		if ( ! empty( $state ) ) {
			$address['addressRegion'] = $state;
		}

		if ( ! empty( $zip ) ) {
			$address['postalCode'] = $zip;
		}

		if ( ! empty( $country ) ) {
			$address['addressCountry'] = $country;
		}

		return $address;
	}

	/**
	 * Build geo coordinates schema.
	 *
	 * @return array|null
	 */
	private function build_geo() {
		$latitude  = get_option( 'wpseopilot_local_latitude', '' );
		$longitude = get_option( 'wpseopilot_local_longitude', '' );

		if ( empty( $latitude ) || empty( $longitude ) ) {
			return null;
		}

		return [
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $latitude,
			'longitude' => (float) $longitude,
		];
	}

	/**
	 * Build opening hours specification schema.
	 *
	 * @return array
	 */
	private function build_opening_hours() {
		$hours = get_option( 'wpseopilot_local_opening_hours', [] );

		if ( empty( $hours ) || ! is_array( $hours ) ) {
			return [];
		}

		$day_map = [
			'monday'    => 'Monday',
			'tuesday'   => 'Tuesday',
			'wednesday' => 'Wednesday',
			'thursday'  => 'Thursday',
			'friday'    => 'Friday',
			'saturday'  => 'Saturday',
			'sunday'    => 'Sunday',
		];

		$specifications = [];
		$grouped_hours  = [];

		// Group days with same hours.
		foreach ( $hours as $day => $data ) {
			if ( empty( $data['enabled'] ) || '1' !== $data['enabled'] ) {
				continue;
			}

			$open  = $data['open'] ?? '';
			$close = $data['close'] ?? '';

			if ( empty( $open ) || empty( $close ) ) {
				continue;
			}

			$key = $open . '-' . $close;

			if ( ! isset( $grouped_hours[ $key ] ) ) {
				$grouped_hours[ $key ] = [
					'days'  => [],
					'opens' => $open,
					'closes' => $close,
				];
			}

			$grouped_hours[ $key ]['days'][] = $day_map[ $day ];
		}

		// Build specifications.
		foreach ( $grouped_hours as $group ) {
			$spec = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => count( $group['days'] ) === 1 ? $group['days'][0] : $group['days'],
				'opens'     => $group['opens'],
				'closes'    => $group['closes'],
			];

			$specifications[] = $spec;
		}

		return $specifications;
	}

	/**
	 * Render settings page.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		wp_enqueue_media();

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);

		$business_types = $this->get_business_types();

		include WPSEOPILOT_PATH . 'templates/local-seo.php';
	}
}
