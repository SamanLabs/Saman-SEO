<?php
/**
 * Job Posting Schema service for job posting schema optimization.
 *
 * Emits a JobPosting node for posts of the `job_posting` post type, but only
 * when every property Google requires is present: title, description,
 * datePosted, hiringOrganization, and either a jobLocation address or
 * jobLocationType TELECOMMUTE with applicantLocationRequirements. Expired
 * postings (validThrough in the past) are not emitted.
 *
 * Fields read from post meta (prefix `_saman_seo_job_posting_`):
 *
 * - `description`              Full job description (defaults to the post content).
 * - `hiring_organization`      Hiring organization name. Defaults to the site's
 *                              Organization when the site is set up as one.
 * - `hiring_organization_url`  Hiring organization URL (sameAs).
 * - `employment_type`          Comma-separated Google values: FULL_TIME, PART_TIME,
 *                              CONTRACTOR, TEMPORARY, INTERN, VOLUNTEER, PER_DIEM,
 *                              OTHER ("Full-time" style input is normalized).
 * - `valid_through`            Expiry date (any strtotime() format).
 * - `street_address`           jobLocation address: street.
 * - `address_locality`         jobLocation address: city.
 * - `address_region`           jobLocation address: state / region.
 * - `postal_code`              jobLocation address: postal code.
 * - `address_country`          jobLocation address: country (ISO 3166-1 alpha-2).
 * - `location_type`            "TELECOMMUTE" for remote jobs.
 * - `applicant_location`       Comma-separated countries remote applicants may
 *                              live in (applicantLocationRequirements).
 *
 * Use the `saman_seo_schema_job_posting` filter to supply or change data
 * (for example baseSalary or identifier).
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Job Posting Schema controller.
 */
class Job_Posting_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Employment types Google accepts.
	 *
	 * @var string[]
	 */
	const EMPLOYMENT_TYPES = array(
		'FULL_TIME',
		'PART_TIME',
		'CONTRACTOR',
		'TEMPORARY',
		'INTERN',
		'VOLUNTEER',
		'PER_DIEM',
		'OTHER',
	);

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_job_posting' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_job_posting_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add JobPosting schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_job_posting_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'job_posting';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'job_posting';
	}

	/**
	 * Build JobPosting schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$hiring_organization = $this->get_entity( $post, 'Organization', 'hiring_organization', 'hiring_organization_url' );
		if ( empty( $hiring_organization ) ) {
			$hiring_organization = $this->get_site_organization();
		}

		$node = array(
			'@type'              => 'JobPosting',
			'@id'                => $this->get_node_id( $post ),
			'title'              => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description'        => $this->get_job_description( $post ),
			'url'                => $this->get_url( $post ),
			'datePosted'         => (string) get_the_date( 'c', $post ),
			'validThrough'       => $this->get_valid_through( $post ),
			'hiringOrganization' => $hiring_organization,
			'employmentType'     => $this->get_employment_type( $post ),
			'jobLocation'        => array(
				'@type'   => 'Place',
				'address' => array(
					'@type'           => 'PostalAddress',
					'streetAddress'   => $this->get_field( $post, 'street_address' ),
					'addressLocality' => $this->get_field( $post, 'address_locality' ),
					'addressRegion'   => $this->get_field( $post, 'address_region' ),
					'postalCode'      => $this->get_field( $post, 'postal_code' ),
					'addressCountry'  => $this->get_field( $post, 'address_country' ),
				),
			),
		);

		if ( 'TELECOMMUTE' === strtoupper( $this->get_field( $post, 'location_type' ) ) ) {
			$node['jobLocationType'] = 'TELECOMMUTE';

			$countries = array_filter( array_map( 'trim', explode( ',', $this->get_field( $post, 'applicant_location' ) ) ) );
			foreach ( $countries as $country ) {
				$node['applicantLocationRequirements'][] = array(
					'@type' => 'Country',
					'name'  => $country,
				);
			}
		}

		return $node;
	}

	/**
	 * Check Google's required JobPosting properties.
	 *
	 * @param array $node Filtered, cleaned node.
	 * @return bool
	 */
	protected function is_valid_node( array $node ): bool {
		foreach ( array( 'title', 'description', 'datePosted' ) as $required ) {
			if ( empty( $node[ $required ] ) ) {
				return false;
			}
		}

		$organization = $node['hiringOrganization'] ?? array();
		if ( empty( $organization ) || ( is_array( $organization ) && empty( $organization['name'] ) && empty( $organization['@id'] ) ) ) {
			return false;
		}

		// Expired postings must not carry JobPosting markup.
		if ( ! empty( $node['validThrough'] ) ) {
			$expires = strtotime( (string) $node['validThrough'] );
			if ( false !== $expires && $expires < time() ) {
				return false;
			}
		}

		if ( $this->has_job_location( $node ) ) {
			return true;
		}

		return isset( $node['jobLocationType'] )
			&& 'TELECOMMUTE' === $node['jobLocationType']
			&& ! empty( $node['applicantLocationRequirements'] );
	}

	/**
	 * Whether the node has a jobLocation with an actual address.
	 *
	 * @param array $node Node.
	 * @return bool
	 */
	private function has_job_location( array $node ): bool {
		if ( empty( $node['jobLocation'] ) || ! is_array( $node['jobLocation'] ) ) {
			return false;
		}

		// Accept a single Place or a list of Places.
		$places = isset( $node['jobLocation']['@type'] ) || isset( $node['jobLocation']['address'] )
			? array( $node['jobLocation'] )
			: $node['jobLocation'];

		foreach ( $places as $place ) {
			if ( ! is_array( $place ) || empty( $place['address'] ) ) {
				continue;
			}

			$address = $place['address'];
			if ( is_string( $address ) ) {
				return true;
			}

			if ( is_array( $address ) && count( array_diff( array_keys( $address ), array( '@type' ) ) ) > 0 ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Full job description: stored meta, else the post content as text.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function get_job_description( \WP_Post $post ): string {
		$description = get_post_meta( $post->ID, $this->get_meta_key( 'description' ), true );

		if ( ! is_string( $description ) || '' === trim( $description ) ) {
			$description = strip_shortcodes( (string) $post->post_content );
		}

		$description = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $description ) ) );

		if ( '' === $description ) {
			$description = trim( wp_strip_all_tags( (string) get_the_excerpt( $post ) ) );
		}

		return $description;
	}

	/**
	 * Expiry date as ISO 8601, or empty string.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	private function get_valid_through( \WP_Post $post ): string {
		$raw = $this->get_field( $post, 'valid_through' );
		if ( '' === $raw ) {
			return '';
		}

		$timestamp = strtotime( $raw );

		return false === $timestamp ? '' : gmdate( 'c', $timestamp );
	}

	/**
	 * Employment type(s) normalized to Google's enum.
	 *
	 * Accepts values such as "Full-time" or "part time" and maps them to
	 * FULL_TIME / PART_TIME. Unknown values are dropped.
	 *
	 * @param \WP_Post $post Post.
	 * @return string|string[] Single value, list of values, or empty string.
	 */
	private function get_employment_type( \WP_Post $post ) {
		$types = array();

		foreach ( explode( ',', $this->get_field( $post, 'employment_type' ) ) as $value ) {
			$normalized = self::normalize_employment_type( $value );
			if ( '' !== $normalized && ! in_array( $normalized, $types, true ) ) {
				$types[] = $normalized;
			}
		}

		if ( empty( $types ) ) {
			return '';
		}

		return 1 === count( $types ) ? $types[0] : $types;
	}

	/**
	 * Map a free-form employment type to Google's enum value.
	 *
	 * @param string $value Raw value.
	 * @return string Enum value, or empty string when unrecognized.
	 */
	public static function normalize_employment_type( $value ): string {
		$value = strtoupper( trim( (string) $value ) );
		$value = preg_replace( '/[\s\-]+/', '_', $value );

		$aliases = array(
			'FULLTIME'   => 'FULL_TIME',
			'PARTTIME'   => 'PART_TIME',
			'CONTRACT'   => 'CONTRACTOR',
			'TEMP'       => 'TEMPORARY',
			'INTERNSHIP' => 'INTERN',
			'PERDIEM'    => 'PER_DIEM',
		);

		if ( isset( $aliases[ $value ] ) ) {
			$value = $aliases[ $value ];
		}

		return in_array( $value, self::EMPLOYMENT_TYPES, true ) ? $value : '';
	}
}
