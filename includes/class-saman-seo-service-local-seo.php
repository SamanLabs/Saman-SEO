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

		// Page rendering handled by Admin_V2 React app (saman-seo-local-seo slug).
		// Only register settings here for sanitization and Options API support.
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		// Removed: LocalBusiness schema now handled by Schema Registry (LocalBusiness_Schema class).
		// Legacy filter disabled to prevent duplicate output with incorrect @context.
		// add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_local_business_to_graph' ], 20, 1 );
	}

	/**
	 * Register settings.
	 *
	 * @return void
	 */
	public function register_settings() {
		$group = 'SAMAN_SEO_local_seo';

		// Synced Knowledge Graph settings (also saved from this page).
		register_setting( $group, 'SAMAN_SEO_homepage_knowledge_type', array( $this, 'sanitize_knowledge_type' ) );

		// Business Information.
		register_setting( $group, 'SAMAN_SEO_local_business_name', 'sanitize_text_field' );
		register_setting( $group, 'SAMAN_SEO_local_business_type', array( $this, 'sanitize_business_type' ) );
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
		register_setting( $group, 'SAMAN_SEO_local_latitude', array( $this, 'sanitize_coordinate' ) );
		register_setting( $group, 'SAMAN_SEO_local_longitude', array( $this, 'sanitize_coordinate' ) );

		// Social Profiles.
		register_setting( $group, 'SAMAN_SEO_local_social_profiles', array( $this, 'sanitize_social_profiles' ) );

		// Opening Hours.
		register_setting( $group, 'SAMAN_SEO_local_opening_hours', array( $this, 'sanitize_opening_hours' ) );

		// Google Maps API Key.
		register_setting( $group, 'SAMAN_SEO_google_maps_api_key', 'sanitize_text_field' );

		// Multiple Locations.
		register_setting( $group, 'SAMAN_SEO_local_enable_locations', array( $this, 'sanitize_bool' ) );
		register_setting( $group, 'SAMAN_SEO_local_locations', array( $this, 'sanitize_locations' ) );
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
	 * Sanitize knowledge type.
	 *
	 * @param string $value Knowledge type (organization or person).
	 * @return string
	 */
	public function sanitize_knowledge_type( $value ) {
		return in_array( $value, array( 'organization', 'person' ), true ) ? $value : 'organization';
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
			return array();
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
			return array();
		}

		$sanitized = array();
		$days      = array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' );

		foreach ( $days as $day ) {
			if ( isset( $value[ $day ] ) && is_array( $value[ $day ] ) ) {
				$sanitized[ $day ] = array(
					'enabled' => ! empty( $value[ $day ]['enabled'] ) ? '1' : '0',
					'open'    => sanitize_text_field( $value[ $day ]['open'] ?? '' ),
					'close'   => sanitize_text_field( $value[ $day ]['close'] ?? '' ),
				);
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
			return array();
		}

		$sanitized = array();

		foreach ( $value as $location ) {
			if ( ! is_array( $location ) ) {
				continue;
			}

			$sanitized[] = array(
				'name'      => sanitize_text_field( $location['name'] ?? '' ),
				'type'      => sanitize_text_field( $location['type'] ?? '' ),
				'enabled'   => $this->sanitize_bool( $location['enabled'] ?? '' ),
				'street'    => sanitize_text_field( $location['street'] ?? '' ),
				'city'      => sanitize_text_field( $location['city'] ?? '' ),
				'state'     => sanitize_text_field( $location['state'] ?? '' ),
				'zip'       => sanitize_text_field( $location['zip'] ?? '' ),
				'country'   => sanitize_text_field( $location['country'] ?? '' ),
				'phone'     => sanitize_text_field( $location['phone'] ?? '' ),
				'email'     => sanitize_email( $location['email'] ?? '' ),
				'latitude'  => $this->sanitize_coordinate( $location['latitude'] ?? '' ),
				'longitude' => $this->sanitize_coordinate( $location['longitude'] ?? '' ),
			);
		}

		return $sanitized;
	}

	/**
	 * Get available business types.
	 *
	 * @return array
	 */
	public function get_business_types() {
		return array(
			'LocalBusiness'          => __( 'Local Business (Generic)', 'saman-seo' ),
			'Restaurant'             => __( 'Restaurant', 'saman-seo' ),
			'Dentist'                => __( 'Dentist', 'saman-seo' ),
			'Physician'              => __( 'Physician', 'saman-seo' ),
			'MedicalClinic'          => __( 'Medical Clinic', 'saman-seo' ),
			'Attorney'               => __( 'Attorney', 'saman-seo' ),
			'RealEstateAgent'        => __( 'Real Estate Agent', 'saman-seo' ),
			'Store'                  => __( 'Store', 'saman-seo' ),
			'AutoDealer'             => __( 'Auto Dealer', 'saman-seo' ),
			'HairSalon'              => __( 'Hair Salon', 'saman-seo' ),
			'BeautySalon'            => __( 'Beauty Salon', 'saman-seo' ),
			'Plumber'                => __( 'Plumber', 'saman-seo' ),
			'Electrician'            => __( 'Electrician', 'saman-seo' ),
			'Locksmith'              => __( 'Locksmith', 'saman-seo' ),
			'AccountingService'      => __( 'Accounting Service', 'saman-seo' ),
			'FinancialService'       => __( 'Financial Service', 'saman-seo' ),
			'InsuranceAgency'        => __( 'Insurance Agency', 'saman-seo' ),
			'TravelAgency'           => __( 'Travel Agency', 'saman-seo' ),
			'AutomotiveBusiness'     => __( 'Automotive Business', 'saman-seo' ),
			'FoodEstablishment'      => __( 'Food Establishment', 'saman-seo' ),
			'EntertainmentBusiness'  => __( 'Entertainment Business', 'saman-seo' ),
			'LodgingBusiness'        => __( 'Lodging Business', 'saman-seo' ),
			'SportsActivityLocation' => __( 'Sports Activity Location', 'saman-seo' ),
		);
	}

	/*
	 * Legacy LocalBusiness schema generation has been removed from this service.
	 * Output is now handled by the schema registry (LocalBusiness_Schema).
	 */
}
