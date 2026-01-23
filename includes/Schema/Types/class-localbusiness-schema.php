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
 * (Restaurant, Dentist, Store, etc.).
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
	 * Only outputs on homepage/front_page when business name exists.
	 * LocalBusiness represents the business entity, not a page,
	 * so it should appear on the site's main page.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		// Only on homepage.
		if ( ! is_front_page() && ! is_home() ) {
			return false;
		}

		// Require business name.
		$business_name = get_option( 'SAMAN_SEO_local_business_name', '' );
		return ! empty( $business_name );
	}

	/**
	 * Generate LocalBusiness schema.
	 *
	 * Builds complete LocalBusiness structured data including:
	 * - Core: @type, @id, name, url
	 * - Media: logo, image
	 * - Contact: telephone, email
	 * - Location: address (PostalAddress), geo (GeoCoordinates)
	 * - Business: priceRange, openingHoursSpecification
	 * - Social: sameAs
	 *
	 * @return array LocalBusiness schema or empty array if no business name.
	 */
	public function generate(): array {
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
	 * Add logo property if set.
	 *
	 * @param array $schema Schema array by reference.
	 */
	private function add_logo( array &$schema ): void {
		$logo = get_option( 'SAMAN_SEO_local_logo', '' );
		if ( ! empty( $logo ) ) {
			$schema['logo'] = [
				'@type' => 'ImageObject',
				'url'   => $logo,
			];
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
