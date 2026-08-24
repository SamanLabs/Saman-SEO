<?php
/**
 * Event Schema class.
 *
 * Generates Event structured data from the `event` key of the plugin's
 * post meta (`_SAMAN_SEO_meta`).
 *
 * @package Saman\SEO\Schema\Types
 * @since   2.1.0
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event schema type.
 *
 * Outputs Event structured data with schedule (ISO 8601 datetimes),
 * attendance mode, status, physical/virtual location, performer,
 * organizer, and ticket offers.
 */
class Event_Schema extends Abstract_Schema {

	/**
	 * Allowed eventStatus values.
	 *
	 * @var array<int,string>
	 */
	const STATUSES = array(
		'EventScheduled',
		'EventRescheduled',
		'EventMovedOnline',
		'EventPostponed',
		'EventCancelled',
	);

	/**
	 * Allowed eventAttendanceMode values.
	 *
	 * @var array<int,string>
	 */
	const ATTENDANCE_MODES = array(
		'OfflineEventAttendanceMode',
		'OnlineEventAttendanceMode',
		'MixedEventAttendanceMode',
	);

	/**
	 * Allowed Offer availability values.
	 *
	 * @var array<int,string>
	 */
	const AVAILABILITIES = array(
		'InStock',
		'SoldOut',
		'PreOrder',
	);

	/**
	 * Get the schema @type value.
	 *
	 * @return string The Event type.
	 */
	public function get_type() {
		return 'Event';
	}

	/**
	 * Determine if Event schema should be output.
	 *
	 * Requires a post context and an event entry with a name and start date.
	 *
	 * @return bool True when the post has complete event data.
	 */
	public function is_needed(): bool {
		if ( ! $this->context->post instanceof \WP_Post ) {
			return false;
		}

		$event = $this->get_event_data();

		return ! empty( $event['name'] ) && ! empty( $event['start_date'] );
	}

	/**
	 * Generate the Event schema array.
	 *
	 * @return array Schema.org Event structured data.
	 */
	public function generate(): array {
		$event = $this->get_event_data();

		if ( empty( $event['name'] ) || empty( $event['start_date'] ) ) {
			return array();
		}

		$schema = array(
			'@type'            => $this->get_type(),
			'@id'              => Schema_IDs::event( $this->context->canonical ),
			'name'             => wp_strip_all_tags( (string) $event['name'] ),
			'startDate'        => self::sanitize_iso8601( (string) $event['start_date'] ),
			'mainEntityOfPage' => array(
				'@id' => Schema_IDs::webpage( $this->context->canonical ),
			),
		);

		// Description.
		$description = trim( wp_strip_all_tags( (string) ( $event['description'] ?? '' ) ) );
		if ( '' !== $description ) {
			$schema['description'] = $description;
		}

		// Image.
		$image = esc_url_raw( (string) ( $event['image'] ?? '' ) );
		if ( '' !== $image ) {
			$schema['image'] = array( $image );
		}

		// End date (only when it follows the start).
		$end_date = trim( (string) ( $event['end_date'] ?? '' ) );
		if ( '' !== $end_date && strtotime( $end_date ) >= strtotime( (string) $event['start_date'] ) ) {
			$schema['endDate'] = self::sanitize_iso8601( $end_date );
		}

		// Status + attendance mode (whitelisted enums with full IRI output).
		$status = (string) ( $event['status'] ?? 'EventScheduled' );
		if ( in_array( $status, self::STATUSES, true ) ) {
			$schema['eventStatus'] = 'https://schema.org/' . $status;
		}

		$mode = (string) ( $event['attendance_mode'] ?? 'OfflineEventAttendanceMode' );
		if ( in_array( $mode, self::ATTENDANCE_MODES, true ) ) {
			$schema['eventAttendanceMode'] = 'https://schema.org/' . $mode;
		}

		// Location: virtual, physical, or both for mixed mode.
		$locations = $this->build_locations( $event, $mode );
		foreach ( $locations as $key => $location ) {
			$schema[ $key ] = $location;
		}

		// Performer.
		$performer_name = trim( wp_strip_all_tags( (string) ( $event['performer_name'] ?? '' ) ) );
		if ( '' !== $performer_name ) {
			$performer_type = 'Organization' === ( $event['performer_type'] ?? '' ) ? 'Organization' : 'Person';

			$schema['performer'] = array(
				'@type' => $performer_type,
				'name'  => $performer_name,
			);
		}

		// Organizer.
		$organizer_name = trim( wp_strip_all_tags( (string) ( $event['organizer_name'] ?? '' ) ) );
		if ( '' !== $organizer_name ) {
			$organizer = array(
				'@type' => 'Organization',
				'name'  => $organizer_name,
			);

			$organizer_url = esc_url_raw( (string) ( $event['organizer_url'] ?? '' ) );
			if ( '' !== $organizer_url ) {
				$organizer['url'] = $organizer_url;
			}

			$schema['organizer'] = $organizer;
		}

		// Offers.
		$offers = $this->build_offers( $event );
		if ( ! empty( $offers ) ) {
			$schema['offers'] = $offers;
		}

		// Free entry flag.
		if ( isset( $event['is_accessible_for_free'] ) ) {
			$schema['isAccessibleForFree'] = (bool) $event['is_accessible_for_free'];
		}

		return $this->apply_fields_filter( $schema );
	}

	/**
	 * Fetch the raw event data from post meta.
	 *
	 * @return array<string,mixed>
	 */
	private function get_event_data(): array {
		$data = $this->context->meta['event'] ?? array();

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Build location blocks based on the attendance mode.
	 *
	 * @param array  $event Raw event data.
	 * @param string $mode  Sanitized attendance mode.
	 * @return array<string,array> Keys map to schema properties.
	 */
	private function build_locations( array $event, string $mode ): array {
		$venue_name   = trim( wp_strip_all_tags( (string) ( $event['venue_name'] ?? '' ) ) );
		$street       = trim( wp_strip_all_tags( (string) ( $event['venue_street_address'] ?? '' ) ) );
		$city         = trim( wp_strip_all_tags( (string) ( $event['venue_city'] ?? '' ) ) );
		$region       = trim( wp_strip_all_tags( (string) ( $event['venue_region'] ?? '' ) ) );
		$postal       = trim( wp_strip_all_tags( (string) ( $event['venue_postal_code'] ?? '' ) ) );
		$country      = trim( wp_strip_all_tags( (string) ( $event['venue_country'] ?? '' ) ) );
		$has_physical = '' !== $venue_name || '' !== $city;
		$virtual_url  = esc_url_raw( (string) ( $event['online_url'] ?? '' ) );

		$locations = array();

		if ( in_array( $mode, array( 'OnlineEventAttendanceMode', 'MixedEventAttendanceMode' ), true ) && '' !== $virtual_url ) {
			$locations['location'] = array(
				'@type' => 'VirtualLocation',
				'url'   => $virtual_url,
				'name'  => '' !== $venue_name ? $venue_name : '',
			);
		}

		if ( in_array( $mode, array( 'OfflineEventAttendanceMode', 'MixedEventAttendanceMode' ), true ) && $has_physical ) {
			$address = array_filter(
				array(
					'streetAddress'   => $street,
					'addressLocality' => $city,
					'addressRegion'   => $region,
					'postalCode'      => $postal,
					'addressCountry'  => $country,
				)
			);

			$place = array_merge(
				array(
					'@type' => 'Place',
					'name'  => $venue_name,
				),
				array()
			);

			if ( ! empty( $address ) ) {
				$place['address'] = array_merge(
					array(
						'@type' => 'PostalAddress',
					),
					$address
				);
			}

			if ( isset( $locations['location'] ) ) {
				// Mixed mode: emit both locations as an array.
				$locations['location'] = array( $locations['location'], $place );
			} else {
				$locations['location'] = $place;
			}
		}

		return $locations;
	}

	/**
	 * Build ticket offers.
	 *
	 * Accepts a single offer shorthand (price/price_currency/offer_url/
	 * availability/valid_from) or an offers list of such arrays.
	 *
	 * @param array $event Raw event data.
	 * @return array<int,array>|array Empty when no usable offer exists.
	 */
	private function build_offers( array $event ): array {
		$raw_offers = array();

		if ( isset( $event['offers'] ) && is_array( $event['offers'] ) ) {
			$raw_offers = $event['offers'];
		} elseif ( isset( $event['price'] ) && '' !== trim( (string) $event['price'] ) ) {
			$raw_offers = array( $event );
		}

		$offers = array();

		foreach ( $raw_offers as $raw ) {
			if ( ! is_array( $raw ) ) {
				continue;
			}

			$price = trim( (string) ( $raw['price'] ?? '' ) );

			if ( '' === $price && ! isset( $raw['is_accessible_for_free'] ) ) {
				continue;
			}

			$offer = array(
				'@type' => 'Offer',
				'url'   => esc_url_raw( (string) ( $raw['offer_url'] ?? $this->context->canonical ) ),
			);

			if ( '' !== $price ) {
				$offer['price']         = $price;
				$offer['priceCurrency'] = strtoupper( trim( wp_strip_all_tags( (string) ( $raw['price_currency'] ?? 'USD' ) ) ) );
			}

			$availability = (string) ( $raw['availability'] ?? 'InStock' );
			if ( in_array( $availability, self::AVAILABILITIES, true ) ) {
				$offer['availability'] = 'https://schema.org/' . $availability;
			}

			$valid_from = trim( (string) ( $raw['valid_from'] ?? '' ) );
			if ( '' !== $valid_from ) {
				$offer['validFrom'] = self::sanitize_iso8601( $valid_from );
			}

			$offers[] = $offer;
		}

		return $offers;
	}

	/**
	 * Normalize a date/datetime string into ISO 8601.
	 *
	 * Values already in ISO 8601 format pass through unchanged; anything
	 * else is converted via strtotime() and dropped when unparseable.
	 *
	 * @param string $value Raw datetime value.
	 * @return string ISO 8601 string, or empty string when invalid.
	 */
	public static function sanitize_iso8601( string $value ): string {
		$value = trim( $value );

		if ( '' === $value ) {
			return '';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}(T\d{2}:\d{2}(:\d{2})?(Z|[+-]\d{2}:?\d{2})?)?$/', $value ) ) {
			return str_replace( '+00:00', 'Z', $value );
		}

		$timestamp = strtotime( $value );

		return false === $timestamp ? '' : gmdate( 'c', $timestamp );
	}
}
