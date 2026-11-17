<?php
/**
 * Reviews Schema class.
 *
 * Outputs site-level AggregateRating and Review nodes from review data the
 * site owner types into the plugin (Local SEO > Reviews). Nothing here reads
 * from, or talks to, Google or any external review service.
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
 * Reviews schema type.
 *
 * Emits one AggregateRating node and one Review node per complete review.
 * Each node points at the business entity already in the graph through
 * `itemReviewed: { "@id": ... }`, so the existing LocalBusiness / Organization
 * / Person node is left exactly as it was.
 *
 * IMPORTANT - eligibility: this is valid schema.org markup, but reviews that a
 * business writes or curates about itself are NOT eligible for Google's review
 * star rich results (Google's "self-serving reviews" rule for LocalBusiness and
 * Organization). The markup is therefore disabled by default, limited to the
 * pages the site owner chooses, and must only describe reviews that are shown
 * to visitors on those pages.
 */
class Reviews_Schema extends Abstract_Schema {

	/**
	 * Highest number of reviews stored or emitted.
	 */
	const MAX_REVIEWS = 50;

	/**
	 * Rating scale. Reviews are entered on a 1-5 scale.
	 */
	const BEST_RATING  = 5;
	const WORST_RATING = 1;

	/**
	 * Google requires the review author name to be shorter than 100 characters.
	 */
	const MAX_AUTHOR_LENGTH = 99;

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'Review';
	}

	/**
	 * Determine if review schema should be output.
	 *
	 * Requires the feature to be switched on, the current page to be one the
	 * owner chose, a business entity to attach to, and something to output.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		if ( '1' !== (string) get_option( 'SAMAN_SEO_reviews_enabled', '' ) ) {
			return false;
		}

		if ( ! $this->is_in_scope() ) {
			return false;
		}

		if ( '' === $this->get_item_reviewed_id() ) {
			return false;
		}

		return ! empty( $this->get_aggregate() ) || ! empty( self::get_valid_reviews() );
	}

	/**
	 * Generate the AggregateRating and Review nodes.
	 *
	 * @return array List of graph pieces (empty when there is nothing valid).
	 */
	public function generate(): array {
		$target = $this->get_item_reviewed_id();
		if ( '' === $target ) {
			return array();
		}

		$pieces = array();

		$aggregate = $this->get_aggregate();
		if ( ! empty( $aggregate ) ) {
			$pieces[] = array(
				'@type'        => 'AggregateRating',
				'@id'          => Schema_IDs::aggregate_rating(),
				'itemReviewed' => array( '@id' => $target ),
				'ratingValue'  => $aggregate['rating'],
				'reviewCount'  => $aggregate['count'],
				'bestRating'   => self::BEST_RATING,
				'worstRating'  => self::WORST_RATING,
			);
		}

		$position = 0;
		foreach ( self::get_valid_reviews() as $item ) {
			++$position;

			$review = array(
				'@type'        => 'Review',
				'@id'          => Schema_IDs::review( $position ),
				'itemReviewed' => array( '@id' => $target ),
				'author'       => array(
					'@type' => 'Person',
					'name'  => $item['author'],
				),
				'reviewRating' => array(
					'@type'       => 'Rating',
					'ratingValue' => $item['rating'],
					'bestRating'  => self::BEST_RATING,
					'worstRating' => self::WORST_RATING,
				),
				'reviewBody'   => $item['text'],
			);

			if ( '' !== $item['date'] ) {
				$review['datePublished'] = $item['date'];
			}

			$pieces[] = $review;
		}

		return $pieces;
	}

	/**
	 * Whether the current page is one the owner chose to output reviews on.
	 *
	 * @return bool
	 */
	private function is_in_scope(): bool {
		$scope = (string) get_option( 'SAMAN_SEO_reviews_scope', 'front_page' );

		if ( 'pages' === $scope ) {
			if ( ! $this->context->post instanceof \WP_Post ) {
				return false;
			}

			$ids = array_map( 'intval', \Saman\SEO\Helpers\get_array_option( 'SAMAN_SEO_reviews_page_ids' ) );

			return in_array( (int) $this->context->post->ID, $ids, true );
		}

		return is_front_page() && ! is_paged();
	}

	/**
	 * Resolve the @id of the business entity the reviews are about.
	 *
	 * Uses the node the schema graph actually contains on this page, most
	 * specific first: the single-location LocalBusiness, then Organization,
	 * then Person. Returns an empty string when none is output, so a Review
	 * never points at a node that is missing from the graph.
	 *
	 * @return string Entity @id, or '' when there is nothing to attach to.
	 */
	private function get_item_reviewed_id(): string {
		$id = '';

		$local = new LocalBusiness_Schema( $this->context );
		if ( $local->is_needed() ) {
			$piece = $local->generate();
			// Multi-location output is a list of pieces and has no top-level @id.
			if ( ! empty( $piece['@id'] ) && is_string( $piece['@id'] ) ) {
				$id = $piece['@id'];
			}
		}

		if ( '' === $id && ( new Organization_Schema( $this->context ) )->is_needed() ) {
			$id = Schema_IDs::organization();
		}

		if ( '' === $id && ( new Person_Schema( $this->context ) )->is_needed() ) {
			$id = Schema_IDs::person();
		}

		/**
		 * Filter the @id the review nodes point at.
		 *
		 * The @id must belong to a node that is present in the same @graph.
		 *
		 * @param string         $id      Entity @id, or '' to output nothing.
		 * @param Schema_Context $context The current context.
		 */
		$id = saman_seo_apply_filters( 'saman_seo_reviews_item_reviewed_id', $id, $this->context );

		return is_string( $id ) ? $id : '';
	}

	/**
	 * Build the aggregate rating.
	 *
	 * The owner-entered rating and review count win. When the rating is left
	 * blank it is the average of the complete reviews, but only if the review
	 * count does not claim more reviews than are listed (averaging a sample of
	 * a larger set would misstate the rating). The count never drops below the
	 * number of complete reviews listed.
	 *
	 * @return array{rating: float, count: int}|array Empty when no valid aggregate exists.
	 */
	private function get_aggregate(): array {
		$reviews = self::get_valid_reviews();
		$listed  = count( $reviews );

		$manual_rating = self::parse_rating( get_option( 'SAMAN_SEO_reviews_rating_value', '' ) );
		$manual_count  = max( 0, (int) get_option( 'SAMAN_SEO_reviews_count', 0 ) );

		if ( null !== $manual_rating ) {
			$rating = $manual_rating;
		} elseif ( $listed > 0 && $manual_count <= $listed ) {
			$sum = 0.0;
			foreach ( $reviews as $review ) {
				$sum += $review['rating'];
			}
			$rating = round( $sum / $listed, 1 );
		} else {
			return array();
		}

		$count = max( $manual_count, $listed );
		if ( $count < 1 ) {
			return array();
		}

		return array(
			'rating' => $rating,
			'count'  => $count,
		);
	}

	/**
	 * Complete reviews saved in the settings.
	 *
	 * A review is complete when it has an author name, review text and a
	 * rating within the scale. Anything else is skipped.
	 *
	 * @return array<int,array{author:string,text:string,rating:float,date:string}>
	 */
	public static function get_valid_reviews(): array {
		$valid = array();

		foreach ( self::normalize_items( get_option( 'SAMAN_SEO_reviews_items', array() ) ) as $item ) {
			if ( '' === $item['author'] || '' === $item['text'] || null === $item['rating'] ) {
				continue;
			}

			$valid[] = $item;

			if ( count( $valid ) >= self::MAX_REVIEWS ) {
				break;
			}
		}

		return $valid;
	}

	/**
	 * Sanitize the review list for storage.
	 *
	 * Partially filled rows are kept so a draft survives a save; they are
	 * skipped at output time. Rows with no content at all are dropped.
	 *
	 * @param mixed $value Raw value (array, or a JSON string of an array).
	 * @return array<int,array{author:string,text:string,rating:float|string,date:string}>
	 */
	public static function sanitize_items( $value ): array {
		$clean = array();

		foreach ( self::normalize_items( $value ) as $item ) {
			if ( '' === $item['author'] && '' === $item['text'] && null === $item['rating'] && '' === $item['date'] ) {
				continue;
			}

			$item['rating'] = null === $item['rating'] ? '' : $item['rating'];
			$clean[]        = $item;

			if ( count( $clean ) >= self::MAX_REVIEWS ) {
				break;
			}
		}

		return $clean;
	}

	/**
	 * Sanitize the owner-entered rating value (blank or 1-5).
	 *
	 * @param mixed $value Raw value.
	 * @return string Rating as a string, or '' when blank/invalid.
	 */
	public static function sanitize_rating_value( $value ): string {
		$rating = self::parse_rating( $value );

		return null === $rating ? '' : (string) $rating;
	}

	/**
	 * Sanitize a list of page IDs.
	 *
	 * @param mixed $value Raw value.
	 * @return int[]
	 */
	public static function sanitize_page_ids( $value ): array {
		if ( is_string( $value ) && '' !== $value ) {
			$value = json_decode( $value, true );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		return array_values( array_unique( array_filter( array_map( 'absint', $value ) ) ) );
	}

	/**
	 * Normalize raw review rows into a predictable shape.
	 *
	 * @param mixed $value Raw value (array, or a JSON string of an array).
	 * @return array<int,array{author:string,text:string,rating:float|null,date:string}>
	 */
	private static function normalize_items( $value ): array {
		if ( is_string( $value ) && '' !== $value ) {
			$value = json_decode( $value, true );
		}

		if ( ! is_array( $value ) ) {
			return array();
		}

		$items = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$author = sanitize_text_field( isset( $row['author'] ) ? (string) $row['author'] : '' );
			if ( function_exists( 'mb_substr' ) ) {
				$author = mb_substr( $author, 0, self::MAX_AUTHOR_LENGTH );
			}

			$items[] = array(
				'author' => $author,
				'text'   => sanitize_textarea_field( isset( $row['text'] ) ? (string) $row['text'] : '' ),
				'rating' => self::parse_rating( isset( $row['rating'] ) ? $row['rating'] : '' ),
				'date'   => self::parse_date( isset( $row['date'] ) ? $row['date'] : '' ),
			);
		}

		return $items;
	}

	/**
	 * Parse a rating into a float within the 1-5 scale.
	 *
	 * @param mixed $value Raw value.
	 * @return float|null Null when blank, non-numeric or out of range.
	 */
	private static function parse_rating( $value ) {
		if ( is_string( $value ) ) {
			$value = trim( $value );
		}

		if ( '' === $value || null === $value || ! is_numeric( $value ) ) {
			return null;
		}

		$rating = round( (float) $value, 1 );

		if ( $rating < self::WORST_RATING || $rating > self::BEST_RATING ) {
			return null;
		}

		return $rating;
	}

	/**
	 * Parse a review date into Y-m-d.
	 *
	 * @param mixed $value Raw value.
	 * @return string Empty when blank or not a real calendar date.
	 */
	private static function parse_date( $value ): string {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) || ! checkdate( (int) $m[2], (int) $m[3], (int) $m[1] ) ) {
			return '';
		}

		return $value;
	}
}
