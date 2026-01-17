<?php
/**
 * Local SEO service for business schema and local search optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
		if ( ! \Saman\SEO\Helpers\module_enabled( 'local_seo' ) ) {
			return;
		}

		// V1 menu disabled - React UI handles menu registration
		// add_action( 'admin_menu', [ $this, 'register_menu' ], 100 );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_local_business_to_graph' ], 20, 1 );
	}

	/**
	 * Register submenu page.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'saman-seo',
			__( 'Local SEO', 'saman-seo' ),
			__( 'Local SEO', 'saman-seo' ),
			'manage_options',
			'saman-seo-local-seo',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$group = 'SAMAN_SEO_local_seo';

		// Business Information.
		register_setting( $group, 'SAMAN_SEO_local_business_name', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_business_type', [ $this, 'sanitize_business_type' ] );
		register_setting( $group, 'SAMAN_SEO_local_description', 'sanitize_textarea_field' );
		register_setting( $group, 'SAMAN_SEO_local_logo', 'esc_url_raw' );
		register_setting( $group, 'SAMAN_SEO_local_image', 'esc_url_raw' );
		register_setting( $group, 'SAMAN_SEO_local_price_range', 'sanitize_text_field' );

		// Contact Information.
		register_setting( $group, 'SAMAN_SEO_local_phone', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_email', 'sanitize_email' );

		// Address.
		register_setting( $group, 'SAMAN_SEO_local_street', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_city', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_state', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_zip', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_country', 'sanitize_text_field' );

		// Geo Coordinates.
		register_setting( $group, 'SAMAN_SEO_local_latitude', [ $this, 'sanitize_coordinate' ] );
		register_setting( $group, 'SAMAN_SEO_local_longitude', [ $this, 'sanitize_coordinate' ] );

		// Social Profiles.
		register_setting( $group, 'SAMAN_SEO_local_social_profiles', [ $this, 'sanitize_social_profiles' ] );

		// Opening Hours.
		register_setting( $group, 'SAMAN_SEO_local_opening_hours', [ $this, 'sanitize_opening_hours' ] );

		// Multiple Locations.
		register_setting( $group, 'SAMAN_SEO_local_enable_locations', [ $this, 'sanitize_bool' ] );
		register_setting( $group, 'SAMAN_SEO_local_locations', [ $this, 'sanitize_locations' ] );
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
			'LocalBusiness'       => __( 'Local Business (Generic)', 'saman-seo' ),
			'Restaurant'          => __( 'Restaurant', 'saman-seo' ),
			'Dentist'             => __( 'Dentist', 'saman-seo' ),
			'Physician'           => __( 'Physician', 'saman-seo' ),
			'MedicalClinic'       => __( 'Medical Clinic', 'saman-seo' ),
			'Attorney'            => __( 'Attorney', 'saman-seo' ),
			'RealEstateAgent'     => __( 'Real Estate Agent', 'saman-seo' ),
			'Store'               => __( 'Store', 'saman-seo' ),
			'AutoDealer'          => __( 'Auto Dealer', 'saman-seo' ),
			'HairSalon'           => __( 'Hair Salon', 'saman-seo' ),
			'BeautySalon'         => __( 'Beauty Salon', 'saman-seo' ),
			'Plumber'             => __( 'Plumber', 'saman-seo' ),
			'Electrician'         => __( 'Electrician', 'saman-seo' ),
			'Locksmith'           => __( 'Locksmith', 'saman-seo' ),
			'AccountingService'   => __( 'Accounting Service', 'saman-seo' ),
			'FinancialService'    => __( 'Financial Service', 'saman-seo' ),
			'InsuranceAgency'     => __( 'Insurance Agency', 'saman-seo' ),
			'TravelAgency'        => __( 'Travel Agency', 'saman-seo' ),
			'AutomotiveBusiness'  => __( 'Automotive Business', 'saman-seo' ),
			'FoodEstablishment'   => __( 'Food Establishment', 'saman-seo' ),
			'EntertainmentBusiness' => __( 'Entertainment Business', 'saman-seo' ),
			'LodgingBusiness'     => __( 'Lodging Business', 'saman-seo' ),
			'SportsActivityLocation' => __( 'Sports Activity Location', 'saman-seo' ),
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

		// Check if multi-location is enabled.
		$enable_locations = get_option( 'SAMAN_SEO_local_enable_locations', '0' );

		if ( '1' === $enable_locations ) {
			// Output schema for each enabled location.
			$locations = get_option( 'SAMAN_SEO_local_locations', [] );

			if ( ! empty( $locations ) && is_array( $locations ) ) {
				foreach ( $locations as $index => $location ) {
					// Skip disabled locations.
					if ( isset( $location['enabled'] ) && ! $location['enabled'] ) {
						continue;
					}

					$location_schema = $this->build_location_schema( $location, $index );
					if ( ! empty( $location_schema ) ) {
						$graph[] = $location_schema;
					}
				}
			}
		} else {
			// Single location mode - use primary business settings.
			$schema = $this->build_schema();

			if ( ! empty( $schema ) ) {
				$graph[] = $schema;
			}
		}

		return $graph;
	}

	/**
	 * Build schema for a specific location.
	 *
	 * @param array $location Location data.
	 * @param int   $index    Location index for unique ID.
	 * @return array|null
	 */
	private function build_location_schema( $location, $index ) {
		// Require at minimum a location name.
		if ( empty( $location['name'] ) ) {
			return null;
		}

		$site_url      = home_url( '/' );
		$business_type = ! empty( $location['type'] ) ? $location['type'] : 'LocalBusiness';

		$schema = [
			'@type' => $business_type,
			'@id'   => $site_url . '#location-' . $index,
			'name'  => $location['name'],
			'url'   => $site_url,
		];

		// Use primary business logo.
		$logo = get_option( 'SAMAN_SEO_local_logo', '' );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = $logo;
		}

		// Phone.
		if ( ! empty( $location['phone'] ) ) {
			$schema['telephone'] = $location['phone'];
		}

		// Email.
		if ( ! empty( $location['email'] ) ) {
			$schema['email'] = $location['email'];
		}

		// Address.
		$address = $this->build_location_address( $location );
		if ( ! empty( $address ) ) {
			$schema['address'] = $address;
		}

		// Geo Coordinates.
		if ( ! empty( $location['latitude'] ) && ! empty( $location['longitude'] ) ) {
			$schema['geo'] = [
				'@type'     => 'GeoCoordinates',
				'latitude'  => (float) $location['latitude'],
				'longitude' => (float) $location['longitude'],
			];
		}

		// Opening hours from primary settings (shared across locations for now).
		$opening_hours = $this->build_opening_hours();
		if ( ! empty( $opening_hours ) ) {
			$schema['openingHoursSpecification'] = $opening_hours;
		}

		return $schema;
	}

	/**
	 * Build postal address for a location.
	 *
	 * @param array $location Location data.
	 * @return array|null
	 */
	private function build_location_address( $location ) {
		// Require at least street and city.
		if ( empty( $location['street'] ) || empty( $location['city'] ) ) {
			return null;
		}

		$address = [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $location['street'],
			'addressLocality' => $location['city'],
		];

		if ( ! empty( $location['state'] ) ) {
			$address['addressRegion'] = $location['state'];
		}

		if ( ! empty( $location['zip'] ) ) {
			$address['postalCode'] = $location['zip'];
		}

		if ( ! empty( $location['country'] ) ) {
			$address['addressCountry'] = $location['country'];
		}

		return $address;
	}

	/**
	 * Build Local Business schema.
	 *
	 * @return array|null
	 */
	private function build_schema() {
		$business_name = get_option( 'SAMAN_SEO_local_business_name', '' );

		// Require at minimum a business name.
		if ( empty( $business_name ) ) {
			return null;
		}

		$business_type = get_option( 'SAMAN_SEO_local_business_type', 'LocalBusiness' );
		$site_url = home_url( '/' );

		$schema = [
			'@context' => 'https://schema.org',
			'@type'    => $business_type,
			'@id'      => $site_url . '#localbusiness',
			'name'     => $business_name,
			'url'      => $site_url,
		];

		// Logo.
		$logo = get_option( 'SAMAN_SEO_local_logo', '' );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = $logo;
		}

		// Image.
		$image = get_option( 'SAMAN_SEO_local_image', '' );
		if ( ! empty( $image ) ) {
			$schema['image'] = $image;
		}

		// Description.
		$description = get_option( 'SAMAN_SEO_local_description', '' );
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Phone.
		$phone = get_option( 'SAMAN_SEO_local_phone', '' );
		if ( ! empty( $phone ) ) {
			$schema['telephone'] = $phone;
		}

		// Email.
		$email = get_option( 'SAMAN_SEO_local_email', '' );
		if ( ! empty( $email ) ) {
			$schema['email'] = $email;
		}

		// Price Range.
		$price_range = get_option( 'SAMAN_SEO_local_price_range', '' );
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
		$social_profiles = get_option( 'SAMAN_SEO_local_social_profiles', [] );
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
		$street  = get_option( 'SAMAN_SEO_local_street', '' );
		$city    = get_option( 'SAMAN_SEO_local_city', '' );
		$state   = get_option( 'SAMAN_SEO_local_state', '' );
		$zip     = get_option( 'SAMAN_SEO_local_zip', '' );
		$country = get_option( 'SAMAN_SEO_local_country', '' );

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
		$latitude  = get_option( 'SAMAN_SEO_local_latitude', '' );
		$longitude = get_option( 'SAMAN_SEO_local_longitude', '' );

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
		$hours = get_option( 'SAMAN_SEO_local_opening_hours', [] );

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
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/css/admin.css',
			[],
			SAMAN_SEO_VERSION
		);

		wp_enqueue_style(
			'saman-seo-plugin',
			SAMAN_SEO_URL . 'assets/css/plugin.css',
			[],
			SAMAN_SEO_VERSION
		);

		wp_enqueue_script(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			SAMAN_SEO_VERSION,
			true
		);

		$business_types = $this->get_business_types();

		include SAMAN_SEO_PATH . 'templates/local-seo.php';
	}
}
