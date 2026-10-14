<?php
/**
 * Schema Validator REST API Controller
 *
 * Validates JSON-LD structured data from URLs.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schema_Validator_Controller class.
 */
class Schema_Validator_Controller extends REST_Controller {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->namespace = 'saman-seo/v1';
		$this->rest_base = 'schema-validator';
	}

	/**
	 * Register routes.
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/validate',
			array(
				'methods'             => \WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'validate_url' ),
				'permission_callback' => array( $this, 'permission_check' ),
				'args'                => array(
					'url' => array(
						'required'          => true,
						'type'              => 'string',
						'sanitize_callback' => 'esc_url_raw',
					),
				),
			)
		);
	}

	/**
	 * Validate structured data from a URL.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response
	 */
	public function validate_url( $request ) {
		$url = $request->get_param( 'url' );

		if ( empty( $url ) || ! wp_http_validate_url( $url ) ) {
			return $this->error( 'Please provide a valid URL' );
		}

		// Fetch the page content
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'    => 15,
				'user-agent' => 'Saman SEO Schema Validator/1.0',
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->error( 'Failed to fetch URL: ' . $response->get_error_message() );
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		if ( $status_code !== 200 ) {
			return $this->error( "URL returned status code: {$status_code}" );
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return $this->error( 'Empty response from URL' );
		}

		// Extract JSON-LD scripts
		$schemas = $this->extract_jsonld( $body );

		// Validate each schema
		$validated_schemas = array();
		$valid_count       = 0;
		$warning_count     = 0;
		$error_count       = 0;
		$notice_count      = 0;

		foreach ( $schemas as $schema ) {
			$validation          = $this->validate_schema( $schema );
			$validated_schemas[] = $validation;
			$notice_count       += count( $validation['notices'] );

			if ( ! empty( $validation['errors'] ) ) {
				$error_count += count( $validation['errors'] );
			} elseif ( ! empty( $validation['warnings'] ) ) {
				$warning_count += count( $validation['warnings'] );
			} else {
				++$valid_count;
			}
		}

		return $this->success(
			array(
				'url'           => $url,
				'schemas'       => $validated_schemas,
				'valid_count'   => $valid_count,
				'warning_count' => $warning_count,
				'error_count'   => $error_count,
				'notice_count'  => $notice_count,
			)
		);
	}

	/**
	 * Extract JSON-LD scripts from HTML.
	 *
	 * @param string $html HTML content.
	 * @return array Array of decoded JSON-LD nodes (top-level and @graph members).
	 */
	public function extract_jsonld( $html ) {
		$schemas = array();

		// Match JSON-LD script tags.
		if ( preg_match_all( '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/si', (string) $html, $matches ) ) {
			foreach ( $matches[1] as $json_string ) {
				$json_string = trim( $json_string );
				if ( empty( $json_string ) ) {
					continue;
				}

				$decoded = json_decode( $json_string, true );
				if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
					$this->collect_nodes( $decoded, $schemas );
				}
			}
		}

		return $schemas;
	}

	/**
	 * Flatten a decoded JSON-LD document into its top-level nodes.
	 *
	 * Handles a single object, a JSON array of objects, and @graph
	 * containers (including a @graph wrapper inside an array).
	 *
	 * @param array $data    Decoded JSON-LD.
	 * @param array $schemas Collected nodes (by reference).
	 * @return void
	 */
	private function collect_nodes( $data, &$schemas ) {
		if ( isset( $data['@graph'] ) && is_array( $data['@graph'] ) ) {
			foreach ( $data['@graph'] as $item ) {
				if ( is_array( $item ) ) {
					$this->collect_nodes( $item, $schemas );
				}
			}
			return;
		}

		if ( array_values( $data ) === $data ) {
			foreach ( $data as $item ) {
				if ( is_array( $item ) ) {
					$this->collect_nodes( $item, $schemas );
				}
			}
			return;
		}

		$schemas[] = $data;
	}

	/**
	 * Normalize a node's @type (string or array) into a list of type names.
	 *
	 * @param array $schema Schema node.
	 * @return string[]
	 */
	public static function get_types( $schema ) {
		if ( ! is_array( $schema ) || ! isset( $schema['@type'] ) ) {
			return array();
		}

		$types = array();
		foreach ( (array) $schema['@type'] as $type ) {
			if ( is_string( $type ) && '' !== trim( $type ) ) {
				// Strip a schema.org prefix such as "schema:FAQPage" or a full IRI.
				$types[] = preg_replace( '#^(?:https?://schema\.org/|schema:)#i', '', trim( $type ) );
			}
		}

		return array_values( array_unique( $types ) );
	}

	/**
	 * Types whose Google rich result has been retired or no longer shows.
	 *
	 * The markup stays valid schema.org, so these surface as info-level
	 * notices, never as errors or warnings.
	 *
	 * @return array<string,string> Type => notice.
	 */
	public static function retired_rich_result_notices() {
		$notices = array(
			'FAQPage'                         => __( 'FAQ rich results were retired by Google in May 2026. FAQPage is still valid schema.org markup, but it no longer produces a rich result in Google Search.', 'saman-seo' ),
			'HowTo'                           => __( 'HowTo rich results were removed from Google Search in 2023. The markup is still valid schema.org but does not produce a rich result.', 'saman-seo' ),
			'Course'                          => __( 'Google retired the Course info rich result in 2025. Course markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'Book'                            => __( 'Google retired Book actions rich results in 2025. Book markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'ClaimReview'                     => __( 'Google retired ClaimReview (fact check) rich results in 2025. The markup is still valid schema.org but no longer produces a rich result.', 'saman-seo' ),
			'EstimatedSalary'                 => __( 'Google retired Estimated salary rich results in 2025. The markup is still valid schema.org but no longer produces a rich result.', 'saman-seo' ),
			'Occupation'                      => __( 'Google retired Estimated salary rich results in 2025. Occupation markup is still valid schema.org but no longer produces a rich result.', 'saman-seo' ),
			'OccupationAggregationByEmployer' => __( 'Google retired Estimated salary rich results in 2025. The markup is still valid schema.org but no longer produces a rich result.', 'saman-seo' ),
			'LearningResource'                => __( 'Google retired Learning video rich results in 2025. LearningResource markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'SpecialAnnouncement'             => __( 'Google retired Special announcement rich results in 2025. The markup is still valid schema.org but no longer produces a rich result.', 'saman-seo' ),
			'Vehicle'                         => __( 'Google retired Vehicle listing rich results in 2025. Vehicle markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'Car'                             => __( 'Google retired Vehicle listing rich results in 2025. Car markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'Motorcycle'                      => __( 'Google retired Vehicle listing rich results in 2025. Motorcycle markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'Quiz'                            => __( 'Google removed practice problem rich results in January 2026. Quiz markup is still valid schema.org but no longer produces that rich result.', 'saman-seo' ),
			'Dataset'                         => __( 'Google deprecated Dataset rich results in Google Search in November 2025. The markup is still valid schema.org and may be read by other dataset tools.', 'saman-seo' ),
		);

		/**
		 * Filter the info notices shown for types without a Google rich result.
		 *
		 * @param array<string,string> $notices Type => notice.
		 */
		return (array) saman_seo_apply_filters( 'saman_seo_schema_validator_retired_types', $notices );
	}

	/**
	 * Validate a single schema object.
	 *
	 * @param array $schema Schema data.
	 * @return array Validation result with errors, warnings and info notices.
	 */
	public function validate_schema( $schema ) {
		$schema     = is_array( $schema ) ? $schema : array();
		$types      = self::get_types( $schema );
		$type       = ! empty( $types ) ? implode( ', ', $types ) : 'Unknown';
		$errors     = array();
		$warnings   = array();
		$notices    = array();
		$properties = array();

		// Extract key properties for display.
		$display_keys = array( 'name', 'headline', 'description', 'url', 'image', 'thumbnailUrl', 'author', 'datePublished', 'startDate', 'price', 'availability' );
		foreach ( $display_keys as $key ) {
			if ( isset( $schema[ $key ] ) ) {
				$properties[ $key ] = $schema[ $key ];
			}
		}

		// Common validations.
		if ( empty( $types ) ) {
			$errors[] = __( 'Missing required @type property', 'saman-seo' );
		}

		// Type-specific validations. A node may carry several types
		// (e.g. ["VideoObject", "LearningResource"]); each is checked once.
		foreach ( $types as $single_type ) {
			switch ( $single_type ) {
				case 'Article':
				case 'NewsArticle':
				case 'BlogPosting':
					// Google lists no required Article properties; all are recommended.
					if ( empty( $schema['headline'] ) ) {
						$warnings[] = __( 'Missing recommended "headline" property', 'saman-seo' );
					}
					if ( empty( $schema['author'] ) ) {
						$warnings[] = __( 'Missing recommended "author" property', 'saman-seo' );
					}
					if ( empty( $schema['datePublished'] ) ) {
						$warnings[] = __( 'Missing recommended "datePublished" property', 'saman-seo' );
					}
					if ( empty( $schema['image'] ) ) {
						$warnings[] = __( 'Missing recommended "image" property', 'saman-seo' );
					}
					break;

				case 'Product':
					if ( empty( $schema['name'] ) ) {
						$errors[] = __( 'Missing required "name" property', 'saman-seo' );
					}
					if ( empty( $schema['offers'] ) ) {
						$warnings[] = __( 'Missing recommended "offers" property for rich results', 'saman-seo' );
					}
					if ( empty( $schema['image'] ) ) {
						$warnings[] = __( 'Missing recommended "image" property', 'saman-seo' );
					}
					break;

				case 'Organization':
				case 'LocalBusiness':
					if ( empty( $schema['name'] ) ) {
						$errors[] = __( 'Missing required "name" property', 'saman-seo' );
					}
					if ( empty( $schema['url'] ) ) {
						$warnings[] = __( 'Missing recommended "url" property', 'saman-seo' );
					}
					if ( 'LocalBusiness' === $single_type && empty( $schema['address'] ) ) {
						$warnings[] = __( 'Missing recommended "address" property for LocalBusiness', 'saman-seo' );
					}
					break;

				case 'WebSite':
					if ( empty( $schema['name'] ) ) {
						$warnings[] = __( 'Missing recommended "name" property', 'saman-seo' );
					}
					if ( empty( $schema['url'] ) ) {
						$errors[] = __( 'Missing required "url" property', 'saman-seo' );
					}
					break;

				case 'BreadcrumbList':
					if ( empty( $schema['itemListElement'] ) ) {
						$errors[] = __( 'Missing required "itemListElement" property', 'saman-seo' );
					}
					break;

				case 'FAQPage':
					// No rich result any more, so gaps are not blocking errors.
					if ( empty( $schema['mainEntity'] ) ) {
						$warnings[] = __( 'Missing "mainEntity" property with questions', 'saman-seo' );
					}
					break;

				case 'HowTo':
					if ( empty( $schema['name'] ) ) {
						$warnings[] = __( 'Missing "name" property', 'saman-seo' );
					}
					if ( empty( $schema['step'] ) ) {
						$warnings[] = __( 'Missing "step" property', 'saman-seo' );
					}
					break;

				case 'VideoObject':
					if ( empty( $schema['name'] ) ) {
						$errors[] = __( 'Missing required "name" property', 'saman-seo' );
					}
					if ( empty( $schema['uploadDate'] ) ) {
						$errors[] = __( 'Missing required "uploadDate" property', 'saman-seo' );
					}
					if ( empty( $schema['thumbnailUrl'] ) ) {
						$errors[] = __( 'Missing required "thumbnailUrl" property', 'saman-seo' );
					}
					break;

				case 'Event':
					if ( empty( $schema['name'] ) ) {
						$errors[] = __( 'Missing required "name" property', 'saman-seo' );
					}
					if ( empty( $schema['startDate'] ) ) {
						$errors[] = __( 'Missing required "startDate" property', 'saman-seo' );
					} elseif ( is_string( $schema['startDate'] ) && self::datetime_lacks_offset( $schema['startDate'] ) ) {
						$warnings[] = __( '"startDate" has a time but no timezone offset (for example 2026-09-15T19:00:00-05:00)', 'saman-seo' );
					}
					if ( empty( $schema['location'] ) ) {
						$errors[] = __( 'Missing required "location" property', 'saman-seo' );
					}
					if ( isset( $schema['eventAttendanceMode'] ) && is_string( $schema['eventAttendanceMode'] ) && false !== stripos( $schema['eventAttendanceMode'], 'OnlineEventAttendanceMode' ) ) {
						$notices[] = __( 'Online-only events are valid schema.org markup, but Google no longer documents them for event rich results.', 'saman-seo' );
					}
					break;
			}
		}

		// Info notices for types that no longer produce a Google rich result.
		$retired = self::retired_rich_result_notices();
		foreach ( $types as $single_type ) {
			if ( isset( $retired[ $single_type ] ) ) {
				$notices[] = $retired[ $single_type ];
			}
		}

		// URL validation.
		if ( ! empty( $schema['url'] ) && is_string( $schema['url'] ) && ! filter_var( $schema['url'], FILTER_VALIDATE_URL ) ) {
			$errors[] = __( 'Invalid URL format in "url" property', 'saman-seo' );
		}

		// Image validation.
		if ( ! empty( $schema['image'] ) ) {
			$image_url = is_array( $schema['image'] ) ? ( $schema['image']['url'] ?? $schema['image'][0] ?? '' ) : $schema['image'];
			if ( is_array( $image_url ) ) {
				$image_url = $image_url['url'] ?? '';
			}
			if ( ! empty( $image_url ) && is_string( $image_url ) && ! filter_var( $image_url, FILTER_VALIDATE_URL ) ) {
				$warnings[] = __( 'Invalid URL format in "image" property', 'saman-seo' );
			}
		}

		return array(
			'type'       => $type,
			'types'      => $types,
			'name'       => $schema['name'] ?? $schema['headline'] ?? null,
			'errors'     => array_values( array_unique( $errors ) ),
			'warnings'   => array_values( array_unique( $warnings ) ),
			'notices'    => array_values( array_unique( $notices ) ),
			'properties' => $properties,
			'raw'        => $schema,
		);
	}

	/**
	 * Whether an ISO 8601 datetime has a time component but no offset.
	 *
	 * Date-only values ("2026-09-15") are fine; "2026-09-15T19:00" is not.
	 *
	 * @param string $value Datetime string.
	 * @return bool
	 */
	public static function datetime_lacks_offset( $value ) {
		$value = trim( (string) $value );

		if ( ! preg_match( '/T\d{2}:\d{2}/', $value ) ) {
			return false;
		}

		return ! preg_match( '/(Z|[+-]\d{2}:?\d{2})$/i', $value );
	}
}
