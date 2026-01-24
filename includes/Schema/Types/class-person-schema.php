<?php
/**
 * Person Schema class.
 *
 * Generates Person schema for sites configured with 'person'
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
 * Person schema type.
 *
 * Outputs Person structured data with image, job title,
 * URL, and social profiles based on plugin settings.
 */
class Person_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'Person';
	}

	/**
	 * Determine if Person schema should be output.
	 *
	 * Only outputs when Knowledge Graph type is set to 'person'.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		return get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ) === 'person';
	}

	/**
	 * Generate Person schema.
	 *
	 * Includes: @type, @id, name, image, jobTitle, url, and sameAs.
	 *
	 * @return array Schema.org Person structured data.
	 */
	public function generate(): array {
		$person_name  = get_option( 'SAMAN_SEO_homepage_person_name', '' );
		$person_image = get_option( 'SAMAN_SEO_homepage_person_image', '' );
		$person_job   = get_option( 'SAMAN_SEO_homepage_person_job_title', '' );
		$person_url   = get_option( 'SAMAN_SEO_homepage_person_url', '' );

		// Fallback to site name if not set.
		if ( empty( $person_name ) ) {
			$person_name = get_bloginfo( 'name' );
		}

		$schema = [
			'@type' => $this->get_type(),
			'@id'   => Schema_IDs::person(),
			'name'  => $person_name,
		];

		if ( ! empty( $person_image ) ) {
			$schema['image'] = [
				'@type' => 'ImageObject',
				'url'   => $person_image,
			];
		}

		if ( ! empty( $person_job ) ) {
			$schema['jobTitle'] = $person_job;
		}

		if ( ! empty( $person_url ) ) {
			$schema['url'] = $person_url;
		} else {
			$schema['url'] = home_url( '/' );
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
