<?php
/**
 * JSON-LD payload builder.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Schema_Graph_Manager;
use Saman\SEO\Schema\Schema_Context;
use function Saman\SEO\Helpers\breadcrumbs;

defined( 'ABSPATH' ) || exit;

/**
 * JSON-LD service.
 */
class JsonLD {

	/**
	 * Hook filters.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld', [ $this, 'build_payload' ], 10, 2 );
	}

	/**
	 * Build JSON-LD graph.
	 *
	 * Delegates to Schema_Graph_Manager for graph construction.
	 * The new schema engine handles all registered types and applies filters.
	 *
	 * @param array         $payload Existing payload (ignored, kept for filter signature).
	 * @param \WP_Post|null $post    Post.
	 *
	 * @return array Complete JSON-LD structure with @context and @graph.
	 */
	public function build_payload( $payload, $post ) {
		$context  = $post ? Schema_Context::from_post( $post ) : Schema_Context::from_current();
		$registry = Schema_Registry::instance();
		$manager  = new Schema_Graph_Manager( $registry );

		return $manager->build( $context );
	}

	/**
	 * Build breadcrumb list.
	 *
	 * @deprecated 1.0.0 Use Breadcrumb_Schema class instead.
	 * @param \WP_Post $post Post obj.
	 *
	 * @return array
	 */
	private function breadcrumb_ld( $post ) {
		$crumbs = [];
		$rank   = 1;

		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank++,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		];

		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = [
				'@type'    => 'ListItem',
				'position' => $rank++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			];
		}

		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		];

		return [
			'@type'    => 'BreadcrumbList',
			'@id'      => get_permalink( $post ) . '#breadcrumb',
			'itemListElement' => $crumbs,
		];
	}

	/**
	 * Get publisher/entity schema based on Knowledge Graph settings.
	 *
	 * @deprecated 1.0.0 Use Organization_Schema or Person_Schema classes instead.
	 * @param string $knowledge_type 'organization' or 'person'.
	 *
	 * @return array
	 */
	private function get_publisher_schema( $knowledge_type ) {
		if ( 'person' === $knowledge_type ) {
			return $this->get_person_schema();
		}

		return $this->get_organization_schema();
	}

	/**
	 * Build Organization schema from settings.
	 *
	 * @deprecated 1.0.0 Use Organization_Schema class instead.
	 * @return array
	 */
	private function get_organization_schema() {
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
			'@type' => 'Organization',
			'@id'   => home_url( '/' ) . '#organization',
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
	 * Build Person schema from settings.
	 *
	 * @deprecated 1.0.0 Use Person_Schema class instead.
	 * @return array
	 */
	private function get_person_schema() {
		$person_name  = get_option( 'SAMAN_SEO_homepage_person_name', '' );
		$person_image = get_option( 'SAMAN_SEO_homepage_person_image', '' );
		$person_job   = get_option( 'SAMAN_SEO_homepage_person_job_title', '' );
		$person_url   = get_option( 'SAMAN_SEO_homepage_person_url', '' );

		// Fallback to site name if not set.
		if ( empty( $person_name ) ) {
			$person_name = get_bloginfo( 'name' );
		}

		$schema = [
			'@type' => 'Person',
			'@id'   => home_url( '/' ) . '#person',
			'name'  => $person_name,
		];

		if ( ! empty( $person_image ) ) {
			$schema['image'] = $person_image;
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
	 * @deprecated 1.0.0 Social profiles are now handled in Organization_Schema and Person_Schema.
	 * @return array
	 */
	private function get_social_profiles() {
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
