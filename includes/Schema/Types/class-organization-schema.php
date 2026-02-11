<?php
/**
 * Organization Schema class.
 *
 * Generates Organization schema for sites configured with 'organization'
 * as the Knowledge Graph type.
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
 * Organization schema type.
 *
 * Outputs Organization structured data with logo, contact info,
 * address, and social profiles based on plugin settings.
 */
class Organization_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'Organization';
	}

	/**
	 * Determine if Organization schema should be output.
	 *
	 * Only outputs when Knowledge Graph type is set to 'organization'.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		return get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ) === 'organization';
	}

	/**
	 * Generate Organization schema.
	 *
	 * Includes: @type, @id, name, url, logo, contact info,
	 * address, and social profiles.
	 *
	 * @return array Schema.org Organization structured data.
	 */
	public function generate(): array {
		$org_name = get_option( 'SAMAN_SEO_homepage_organization_name', '' );
		$org_logo = get_option( 'SAMAN_SEO_homepage_organization_logo', '' );

		// Fallback to site name and icon if not set.
		if ( empty( $org_name ) ) {
			$org_name = get_bloginfo( 'name' );
		}
		if ( empty( $org_logo ) ) {
			$org_logo = get_site_icon_url();
		}

		$schema = [
			'@type' => $this->get_type(),
			'@id'   => Schema_IDs::organization(),
			'name'  => $org_name,
			'url'   => home_url( '/' ),
		];

		if ( ! empty( $org_logo ) ) {
			$schema['logo'] = $org_logo;
		}

		// Add Local SEO data if available.
		$local_phone       = get_option( 'SAMAN_SEO_local_phone', '' );
		$local_email       = get_option( 'SAMAN_SEO_local_email', '' );
		$local_street      = get_option( 'SAMAN_SEO_local_street', '' );
		$local_city        = get_option( 'SAMAN_SEO_local_city', '' );
		$local_state       = get_option( 'SAMAN_SEO_local_state', '' );
		$local_zip         = get_option( 'SAMAN_SEO_local_zip', '' );
		$local_country     = get_option( 'SAMAN_SEO_local_country', '' );
		$local_description = get_option( 'SAMAN_SEO_local_description', '' );

		if ( ! empty( $local_phone ) ) {
			$schema['telephone'] = $local_phone;
		}

		if ( ! empty( $local_email ) ) {
			$schema['email'] = $local_email;
		}

		if ( ! empty( $local_description ) ) {
			$schema['description'] = $local_description;
		}

		// Build address if we have location data.
		if ( ! empty( $local_street ) || ! empty( $local_city ) ) {
			$address = [ '@type' => 'PostalAddress' ];
			if ( ! empty( $local_street ) ) {
				$address['streetAddress'] = $local_street;
			}
			if ( ! empty( $local_city ) ) {
				$address['addressLocality'] = $local_city;
			}
			if ( ! empty( $local_state ) ) {
				$address['addressRegion'] = $local_state;
			}
			if ( ! empty( $local_zip ) ) {
				$address['postalCode'] = $local_zip;
			}
			if ( ! empty( $local_country ) ) {
				$address['addressCountry'] = $local_country;
			}
			$schema['address'] = $address;
		}

		// Add social profiles.
		$social_profiles = $this->get_social_profiles();
		if ( ! empty( $social_profiles ) ) {
			$schema['sameAs'] = $social_profiles;
		}

		return $schema;
	}

	/**
	 * Get social profiles from settings.
	 *
	 * Merges profiles from Local SEO and Homepage social settings.
	 *
	 * @return array Array of social profile URLs.
	 */
	private function get_social_profiles(): array {
		$profiles = [];

		// Try Local SEO social profiles first.
		$local_social = get_option( 'SAMAN_SEO_local_social_profiles', '' );
		if ( ! empty( $local_social ) ) {
			$parsed = json_decode( $local_social, true );
			if ( is_array( $parsed ) ) {
				$profiles = array_merge( $profiles, array_filter( $parsed ) );
			}
		}

		// Also check homepage social profiles (Person schema).
		$homepage_social = get_option( 'SAMAN_SEO_homepage_social_profiles', '' );
		if ( ! empty( $homepage_social ) ) {
			$lines = explode( "\n", $homepage_social );
			foreach ( $lines as $line ) {
				$url = trim( $line );
				if ( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ) {
					$profiles[] = $url;
				}
			}
		}

		return array_unique( array_filter( $profiles ) );
	}
}
