<?php
/**
 * LocalBusiness Schema class.
 *
 * Generates LocalBusiness schema for sites with Local SEO settings configured.
 * Reads from existing SAMAN_SEO_local_* options to avoid duplicate data entry.
 *
 * @package Saman\SEO\Schema\Types
 * @since   1.0.0
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * LocalBusiness schema type.
 *
 * Outputs LocalBusiness structured data with address, contact info,
 * opening hours, geo coordinates, and social profiles based on
 * Local SEO plugin settings. Supports specific business type variants
 * (Restaurant, Dentist, Store, etc.) and multiple locations.
 */
class LocalBusiness_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * Returns the specific business type from settings, allowing for
	 * LocalBusiness subtypes like Restaurant, Dentist, Store, etc.
	 *
	 * @return string Business type (LocalBusiness, Restaurant, Dentist, etc.)
	 */
	public function get_type() {
		return get_option( 'SAMAN_SEO_local_business_type', 'LocalBusiness' );
	}

	/**
	 * Determine if LocalBusiness schema should be output.
	 *
	 * Only outputs on homepage/front_page when the module is enabled and
	 * business information exists.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'local_seo' ) ) {
			return false;
		}

		if ( ! is_front_page() && ! is_home() ) {
			return false;
		}

		// Single-location mode requires a business name.
		if ( ! empty( get_option( 'SAMAN_SEO_local_business_name', '' ) ) ) {
			return true;
		}

		// Multi-location mode can output using location names.
		if ( '1' !== get_option( 'SAMAN_SEO_local_enable_locations', '0' ) ) {
			return false;
		}

		$locations = get_option( 'SAMAN_SEO_local_locations', [] );
		if ( empty( $locations ) || ! is_array( $locations ) ) {
			return false;
		}

		foreach ( $locations as $location ) {
			if ( is_array( $location ) && ! empty( $location['name'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Generate LocalBusiness schema.
	 *
	 * Builds one LocalBusiness piece for single-location mode, or a list
	 * of LocalBusiness pieces when multi-location mode is enabled.
	 *
	 * @return array Single schema array, or list of schema arrays.
	 */
	public function generate(): array {
		$multi_enabled = '1' === get_option( 'SAMAN_SEO_local_enable_locations', '0' );
		$locations     = get_option( 'SAMAN_SEO_local_locations', [] );

		if ( $multi_enabled && ! empty( $locations ) && is_array( $locations ) ) {
			$schemas = [];

			foreach ( $locations as $index => $location ) {
				if ( ! is_array( $location ) ) {
					continue;
				}

				if ( isset( $location['enabled'] ) && '1' !== $location['enabled'] ) {
					continue;
				}

				$location_schema = $this->build_location_schema( $location, $index );
				if ( ! empty( $location_schema ) ) {
					$schemas[] = $location_schema;
				}
			}

			return $schemas;
		}

		$business_name = get_option( 'SAMAN_SEO_local_business_name', '' );
		if ( empty( $business_name ) ) {
			return [];
		}

		$schema = [
			'@type' => $this->get_type(),
			'@id'   => Schema_IDs::localbusiness(),
			'name'  => $business_name,
			'url'   => home_url( '/' ),
		];

		// Add optional properties via helper methods.
		$this->add_logo( $schema );
		$this->add_image( $schema );
		$this->add_description( $schema );
		$this->add_contact( $schema );
		$this->add_price_range( $schema );
		$this->add_address( $schema );
		$this->add_geo( $schema );
		$this->add_opening_hours( $schema );
		$this->add_social_profiles( $schema );

		return $schema;
	}

	/**
	 * Build a LocalBusiness schema for a single location.
	 *
	 * Inherits shared settings (logo, image, price range, opening hours,
	 * social profiles) and overrides name, address, phone and geo
	 * coordinates with location-specific values.
	 *
	 * @param array $location Location data.
	 * @param int   $index    Location index for unique @id.
	 * @return array Empty array if location has no name.
	 */
	private function build_location_schema( array $location, int $index ): array {
		$name = ! empty( $location['name'] ) ? sanitize_text_field( $location['name'] ) : '';
		if ( empty( $name ) ) {
			return [];
		}

		$business_type = ! empty( $location['type'] ) ? sanitize_text_field( $location['type'] ) : $this->get_type();
		$site_url      = home_url( '/' );

		$schema = [
			'@type' => $business_type,
			'@id'   => $site_url . '#localbusiness-' . $index,
			'name'  => $name,
			'url'   => $site_url,
		];

		// Shared optional properties from primary settings.
		$this->add_logo( $schema );
		$this->add_image( $schema );
		$this->add_price_range( $schema );
		$this->add_opening_hours( $schema );
		$this->add_social_profiles( $schema );

		// Location-specific contact details.
		$phone = ! empty( $location['phone'] ) ? sanitize_text_field( $location['phone'] ) : '';
		if ( ! empty( $phone ) ) {
			$schema['telephone'] = $phone;
		}

		$email = ! empty( $location['email'] ) ? sanitize_email( $location['email'] ) : '';
		if ( ! empty( $email ) ) {
			$schema['email'] = $email;
		}

		$address = $this->build_location_address( $location );
		if ( ! empty( $address ) ) {
			$schema['address'] = $address;
		}

		$geo = $this->build_location_geo( $location );
		if ( ! empty( $geo ) ) {
			$schema['geo'] = $geo;
		}

		return $schema;
	}

	/**
	 * Build postal address for a location.
	 *
	 * @param array $location Location data.
	 * @return array|null
	 */
	private function build_location_address( array $location ): ?array {
		$street = ! empty( $location['street'] ) ? sanitize_text_field( $location['street'] ) : '';
		$city   = ! empty( $location['city'] ) ? sanitize_text_field( $location['city'] ) : '';

		// Require at least street and city.
		if ( empty( $street ) || empty( $city ) ) {
			return null;
		}

		$address = [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
		];

		if ( ! empty( $location['state'] ) ) {
			$address['addressRegion'] = sanitize_text_field( $location['state'] );
		}

		if ( ! empty( $location['zip'] ) ) {
			$address['postalCode'] = sanitize_text_field( $location['zip'] );
		}

		if ( ! empty( $location['country'] ) ) {
			$address['addressCountry'] = sanitize_text_field( $location['country'] );
		}

		return $address;
	}

	/**
	 * Build geo coordinates for a location.
	 *
	 * @param array $location Location data.
	 * @return array|null
	 */
	private function build_location_geo( array $location ): ?array {
		$latitude  = $location['latitude'] ?? '';
		$longitude = $location['longitude'] ?? '';

		if ( empty( $latitude ) || empty( $longitude ) ) {
			return null;
		}

		if ( ! is_numeric( $latitude ) || ! is_numeric( $longitude ) ) {
			return null;
		}

		return [
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $latitude,
			'longitude' => (float) $longitude,
		];
	}

	/**
	 * Add logo property if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_logo( array &$schema ): void {
		$logo = get_option( 'SAMAN_SEO_local_logo', '' );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = $logo;
		}
	}

	/**
	 * Add image property if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_image( array &$schema ): void {
		$image = get_option( 'SAMAN_SEO_local_image', '' );
		if ( ! empty( $image ) ) {
			$schema['image'] = $image;
		}
	}

	/**
	 * Add description property if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_description( array &$schema ): void {
		$description = get_option( 'SAMAN_SEO_local_description', '' );
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}
	}

	/**
	 * Add telephone and email contact properties if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_contact( array &$schema ): void {
		$phone = get_option( 'SAMAN_SEO_local_phone', '' );
		$email = get_option( 'SAMAN_SEO_local_email', '' );

		if ( ! empty( $phone ) ) {
			$schema['telephone'] = $phone;
		}
		if ( ! empty( $email ) ) {
			$schema['email'] = $email;
		}
	}

	/**
	 * Add priceRange property if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_price_range( array &$schema ): void {
		$price_range = get_option( 'SAMAN_SEO_local_price_range', '' );
		if ( ! empty( $price_range ) ) {
			$schema['priceRange'] = $price_range;
		}
	}

	/**
	 * Add PostalAddress nested type if street and city are set.
	 *
	 * Requires both street and city as minimum for valid address.
	 * Adds optional region, postal code, and country if present.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_address( array &$schema ): void {
		$street = get_option( 'SAMAN_SEO_local_street', '' );
		$city   = get_option( 'SAMAN_SEO_local_city', '' );

		// Require at least street and city.
		if ( empty( $street ) || empty( $city ) ) {
			return;
		}

		$address = [
			'@type'           => 'PostalAddress',
			'streetAddress'   => $street,
			'addressLocality' => $city,
		];

		$state = get_option( 'SAMAN_SEO_local_state', '' );
		if ( ! empty( $state ) ) {
			$address['addressRegion'] = $state;
		}

		$zip = get_option( 'SAMAN_SEO_local_zip', '' );
		if ( ! empty( $zip ) ) {
			$address['postalCode'] = $zip;
		}

		$country = get_option( 'SAMAN_SEO_local_country', '' );
		if ( ! empty( $country ) ) {
			$address['addressCountry'] = $country;
		}

		$schema['address'] = $address;
	}

	/**
	 * Add GeoCoordinates nested type if both lat and lng are set.
	 *
	 * Requires both latitude and longitude. Casts to float for
	 * proper numeric representation in JSON output.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_geo( array &$schema ): void {
		$latitude  = get_option( 'SAMAN_SEO_local_latitude', '' );
		$longitude = get_option( 'SAMAN_SEO_local_longitude', '' );

		if ( empty( $latitude ) || empty( $longitude ) ) {
			return;
		}

		$schema['geo'] = [
			'@type'     => 'GeoCoordinates',
			'latitude'  => (float) $latitude,
			'longitude' => (float) $longitude,
		];
	}

	/**
	 * Add OpeningHoursSpecification array if hours are configured.
	 *
	 * Groups days with identical hours into single specifications
	 * for compact output. Only includes enabled days with valid
	 * open/close times.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_opening_hours( array &$schema ): void {
		$hours = get_option( 'SAMAN_SEO_local_opening_hours', [] );

		if ( empty( $hours ) || ! is_array( $hours ) ) {
			return;
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

		$grouped_hours = [];

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
					'days'   => [],
					'opens'  => $open,
					'closes' => $close,
				];
			}

			if ( isset( $day_map[ $day ] ) ) {
				$grouped_hours[ $key ]['days'][] = $day_map[ $day ];
			}
		}

		// Build specifications.
		$specifications = [];
		foreach ( $grouped_hours as $group ) {
			$specifications[] = [
				'@type'     => 'OpeningHoursSpecification',
				'dayOfWeek' => count( $group['days'] ) === 1 ? $group['days'][0] : $group['days'],
				'opens'     => $group['opens'],
				'closes'    => $group['closes'],
			];
		}

		if ( ! empty( $specifications ) ) {
			$schema['openingHoursSpecification'] = $specifications;
		}
	}

	/**
	 * Add sameAs social profiles array if configured.
	 *
	 * Filters empty values from the profiles array.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_social_profiles( array &$schema ): void {
		$profiles = get_option( 'SAMAN_SEO_local_social_profiles', [] );

		if ( ! empty( $profiles ) && is_array( $profiles ) ) {
			$filtered = array_values( array_filter( $profiles ) );
			if ( ! empty( $filtered ) ) {
				$schema['sameAs'] = $filtered;
			}
		}
	}
}
