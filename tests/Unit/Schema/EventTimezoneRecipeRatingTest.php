<?php
/**
 * Tests for Event timezone handling and Recipe aggregateRating guards.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Types\Event_Schema;
use Saman\SEO\Schema\Types\Recipe_Schema;
use Saman\SEO\Tests\TestCase;

require_once __DIR__ . '/Fixtures/wp-timezone-stub.php';

/**
 * Event datetimes carry an offset; Recipe ratings must look genuine.
 */
class EventTimezoneRecipeRatingTest extends TestCase {

	/**
	 * Use a non-UTC site timezone.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$options['timezone_string'] = 'America/Chicago';
	}

	/**
	 * Build a context carrying arbitrary meta.
	 *
	 * @param array $meta Meta values.
	 * @return Schema_Context
	 */
	private function make_context( array $meta ): Schema_Context {
		$context            = new Schema_Context();
		$context->site_url  = 'https://example.org/';
		$context->site_name = 'Test Site';
		$context->post      = $this->make_post();
		$context->post_type = 'post';
		$context->canonical = 'https://example.org/sample-post/';
		$context->meta      = $meta;

		return $context;
	}

	/**
	 * Datetimes without an offset are read in the site timezone.
	 */
	public function test_naive_datetime_gets_site_offset(): void {
		// September: America/Chicago is on CDT (-05:00).
		$this->assertSame( '2026-09-15T19:00:00-05:00', Event_Schema::sanitize_iso8601( '2026-09-15T19:00' ) );
		$this->assertSame( '2026-09-15T19:00:30-05:00', Event_Schema::sanitize_iso8601( '2026-09-15T19:00:30' ) );

		// December: CST (-06:00).
		$this->assertSame( '2026-12-01T09:30:00-06:00', Event_Schema::sanitize_iso8601( '2026-12-01 09:30' ) );
	}

	/**
	 * Explicit offsets are kept, UTC is compact, dates pass through.
	 */
	public function test_explicit_offsets_preserved(): void {
		$this->assertSame( '2026-09-15T19:00:00+02:00', Event_Schema::sanitize_iso8601( '2026-09-15T19:00:00+02:00' ) );
		$this->assertSame( '2026-09-15T19:00:00+02:00', Event_Schema::sanitize_iso8601( '2026-09-15T19:00:00+0200' ) );
		$this->assertSame( '2026-09-15T18:00:00Z', Event_Schema::sanitize_iso8601( '2026-09-15T18:00:00+00:00' ) );
		$this->assertSame( '2026-09-15T18:00:00Z', Event_Schema::sanitize_iso8601( '2026-09-15T18:00:00Z' ) );
		$this->assertSame( '2026-09-15', Event_Schema::sanitize_iso8601( '2026-09-15' ) );
		$this->assertSame( '', Event_Schema::sanitize_iso8601( 'definitely not a date' ) );
	}

	/**
	 * Generated Event start/end carry the site offset.
	 */
	public function test_event_output_has_offset(): void {
		$data = ( new Event_Schema(
			$this->make_context(
				array(
					'event' => array(
						'name'       => 'Local Meetup',
						'start_date' => '2026-09-15T18:00',
						'end_date'   => '2026-09-15T21:00',
					),
				)
			)
		) )->generate();

		$this->assertSame( '2026-09-15T18:00:00-05:00', $data['startDate'] );
		$this->assertSame( '2026-09-15T21:00:00-05:00', $data['endDate'] );
	}

	/**
	 * An end that precedes the start (after offsets) is dropped.
	 */
	public function test_end_before_start_dropped_across_offsets(): void {
		$data = ( new Event_Schema(
			$this->make_context(
				array(
					'event' => array(
						'name'       => 'Offsets',
						// 18:00 Chicago = 23:00 UTC; end 20:00 UTC is earlier.
						'start_date' => '2026-09-15T18:00',
						'end_date'   => '2026-09-15T20:00:00Z',
					),
				)
			)
		) )->generate();

		$this->assertArrayNotHasKey( 'endDate', $data );
	}

	/**
	 * Recipe data with rating fields.
	 *
	 * @param array $rating Rating fields.
	 * @return array|null aggregateRating or null.
	 */
	private function recipe_rating( array $rating ) {
		$data = ( new Recipe_Schema(
			$this->make_context(
				array(
					'recipe' => array_merge( array( 'name' => 'Pancakes' ), $rating ),
				)
			)
		) )->generate();

		return $data['aggregateRating'] ?? null;
	}

	/**
	 * Valid ratings are emitted on the default 1-5 scale.
	 */
	public function test_valid_rating_output(): void {
		$rating = $this->recipe_rating(
			array(
				'rating_value' => 4.6,
				'rating_count' => 12,
			)
		);

		$this->assertSame( 4.6, $rating['ratingValue'] );
		$this->assertSame( 12, $rating['ratingCount'] );
		$this->assertArrayNotHasKey( 'bestRating', $rating );
	}

	/**
	 * Zero counts or out-of-range values are never emitted.
	 */
	public function test_invalid_ratings_omitted(): void {
		$this->assertNull( $this->recipe_rating( array( 'rating_value' => 4, 'rating_count' => 0 ) ) );
		$this->assertNull( $this->recipe_rating( array( 'rating_value' => 0.5, 'rating_count' => 3 ) ) );
		$this->assertNull( $this->recipe_rating( array( 'rating_value' => 7, 'rating_count' => 3 ) ) );
		$this->assertNull( $this->recipe_rating( array( 'rating_value' => 'abc', 'rating_count' => 3 ) ) );
	}

	/**
	 * A custom bestRating widens the allowed range and is output.
	 */
	public function test_custom_best_rating(): void {
		$rating = $this->recipe_rating(
			array(
				'rating_value' => 8,
				'rating_count' => 3,
				'best_rating'  => 10,
			)
		);

		$this->assertSame( 8.0, $rating['ratingValue'] );
		$this->assertSame( 10.0, $rating['bestRating'] );
	}

	/**
	 * Sites can disable aggregateRating with the filter.
	 */
	public function test_filter_can_disable_rating(): void {
		add_filter(
			'saman_seo_recipe_aggregate_rating',
			static function () {
				return false;
			}
		);

		$this->assertNull(
			$this->recipe_rating(
				array(
					'rating_value' => 4.5,
					'rating_count' => 10,
				)
			)
		);
	}
}
