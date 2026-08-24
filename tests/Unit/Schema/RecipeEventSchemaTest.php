<?php
/**
 * Tests for Recipe and Event schema types.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Types\Event_Schema;
use Saman\SEO\Schema\Types\Recipe_Schema;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Rich-result schema coverage (Recipe/Event).
 */
class RecipeEventSchemaTest extends TestCase {

	/**
	 * Build a context carrying arbitrary meta.
	 *
	 * @param array $meta Meta values.
	 * @return Schema_Context
	 */
	private function make_context( array $meta ): Schema_Context {
		$post                = $this->make_post();
		self::$authors[1]   = 'Chef Jane';

		$context              = new Schema_Context();
		$context->site_url    = 'https://example.org/';
		$context->site_name   = 'Test Site';
		$context->post        = $post;
		$context->post_type   = 'post';
		$context->canonical   = 'https://example.org/sample-post/';
		$context->meta        = $meta;
		$context->schema_type = 'Article';

		return $context;
	}

	/**
	 * Complete recipe data produces the full schema shape.
	 */
	public function test_recipe_schema_full_shape(): void {
		$context = $this->make_context(
			array(
				'recipe' => array(
					'name'               => 'Classic Pancakes',
					'description'        => 'Fluffy weekend pancakes.',
					'images'             => array( 'https://cdn.example.org/pancakes.jpg' ),
					'recipeYield'        => '4 servings',
					'prepTime'           => '15 minutes',
					'cookTime'           => '20 min',
					'totalTime'          => '35 minutes',
					'recipeCategory'     => 'Breakfast',
					'recipeCuisine'      => 'American',
					'keywords'           => 'pancakes, breakfast',
					'nutrition_calories' => '250 calories',
					'recipeIngredient'   => array( '2 cups flour', '', '1 egg' ),
					'recipeInstructions' => array( 'Whisk the batter', 'Cook on a hot griddle' ),
					'rating_value'       => 4.5,
					'rating_count'       => 23,
				),
			)
		);

		$schema = new Recipe_Schema( $context );

		$this->assertTrue( $schema->is_needed() );

		$data = $schema->generate();

		$this->assertSame( 'Recipe', $data['@type'] );
		$this->assertSame( 'https://example.org/sample-post/#recipe', $data['@id'] );
		$this->assertSame( 'Classic Pancakes', $data['name'] );
		$this->assertSame( array( 'https://cdn.example.org/pancakes.jpg' ), $data['image'] );
		$this->assertSame( 'PT15M', $data['prepTime'] );
		$this->assertSame( 'PT20M', $data['cookTime'] );
		$this->assertSame( 'PT35M', $data['totalTime'] );
		$this->assertSame( '4 servings', $data['recipeYield'] );
		$this->assertSame( 'Breakfast', $data['recipeCategory'] );
		$this->assertSame( 'American', $data['recipeCuisine'] );
		$this->assertSame( '250 calories', $data['nutrition']['calories'] );
		$this->assertSame( array( '2 cups flour', '1 egg' ), $data['recipeIngredient'] );
		$this->assertCount( 2, $data['recipeInstructions'] );
		$this->assertSame( 1, $data['recipeInstructions'][0]['position'] );
		$this->assertSame( 'Whisk the batter', $data['recipeInstructions'][0]['text'] );
		$this->assertSame( 4.5, $data['aggregateRating']['ratingValue'] );
		$this->assertSame( 23, $data['aggregateRating']['ratingCount'] );
	}

	/**
	 * UTC datetimes normalize to the compact Z form.
	 */
	public function test_utc_dates_normalize_to_z(): void {
		$context = $this->make_context(
			array(
				'event' => array(
					'name'       => 'Normalized Event',
					'start_date' => '2026-09-15T18:00:00+00:00',
					'end_date'   => '2026-09-15T21:00:00+00:00',
				),
			)
		);

		$data = ( new Event_Schema( $context ) )->generate();

		$this->assertSame( '2026-09-15T18:00:00Z', $data['startDate'] );
		$this->assertSame( '2026-09-15T21:00:00Z', $data['endDate'] );
	}

	/**
	 * Combined duration strings convert to ISO 8601 with hours + minutes.
	 */
	public function test_recipe_duration_parsing(): void {
		$this->assertSame( 'PT45M', Recipe_Schema::parse_duration( '45 minutes' ) );
		$this->assertSame( 'PT2H', Recipe_Schema::parse_duration( '2 hours' ) );
		$this->assertSame( 'PT1H30M', Recipe_Schema::parse_duration( '1 hour 30 minutes' ) );
		$this->assertSame( 'PT30M', Recipe_Schema::parse_duration( '30' ) );
		$this->assertSame( 'PT1H30M', Recipe_Schema::parse_duration( 'PT1h30m' ) );
		$this->assertNull( Recipe_Schema::parse_duration( '' ) );
		$this->assertNull( Recipe_Schema::parse_duration( 'whenever it is done' ) );
	}

	/**
	 * Recipes without a name never emit schema.
	 */
	public function test_recipe_requires_name(): void {
		$context = $this->make_context(
			array(
				'recipe' => array( 'recipeIngredient' => array( 'flour' ) ),
			)
		);

		$schema = new Recipe_Schema( $context );

		$this->assertFalse( $schema->is_needed() );
		$this->assertSame( array(), $schema->generate() );
	}

	/**
	 * Incomplete ratings are omitted entirely.
	 */
	public function test_recipe_rating_omitted_when_incomplete(): void {
		$context = $this->make_context(
			array(
				'recipe' => array(
					'name'         => 'No Rating Cake',
					'rating_value' => 5,
				),
			)
		);

		$data = ( new Recipe_Schema( $context ) )->generate();

		$this->assertArrayNotHasKey( 'aggregateRating', $data );
	}

	/**
	 * The recipe fields filter can modify output.
	 */
	public function test_recipe_fields_filter_applies(): void {
		add_filter(
			'saman_seo_schema_recipe_fields',
			static function ( array $data ) {
				$data['servesKilograms'] = 'lots';

				return $data;
			}
		);

		$context = $this->make_context(
			array(
				'recipe' => array( 'name' => 'Filtered Fudge' ),
			)
		);

		$data = ( new Recipe_Schema( $context ) )->generate();

		$this->assertSame( 'lots', $data['servesKilograms'] );
	}

	/**
	 * A complete offline event renders status, location and offers.
	 */
	public function test_event_schema_offline_shape(): void {
		$context = $this->make_context(
			array(
				'event' => array(
					'name'                  => 'WordPress Meetup',
					'description'           => 'Monthly meetup.',
					'image'                 => 'https://cdn.example.org/meetup.jpg',
					'start_date'            => '2026-09-15T18:00:00+00:00',
					'end_date'              => '2026-09-15T21:00:00+00:00',
					'status'                => 'EventScheduled',
					'attendance_mode'       => 'OfflineEventAttendanceMode',
					'venue_name'            => 'Downtown Hall',
					'venue_street_address'  => '12 Main St',
					'venue_city'            => 'Springfield',
					'venue_region'          => 'IL',
					'venue_postal_code'     => '62701',
					'venue_country'         => 'US',
					'performer_name'        => 'Jane Speaker',
					'performer_type'        => 'Person',
					'organizer_name'        => 'WP Community',
					'organizer_url'         => 'https://example.org/wp-community/',
					'price'                 => '10.00',
					'price_currency'        => 'usd',
					'availability'          => 'InStock',
				),
			)
		);

		$schema = new Event_Schema( $context );

		$this->assertTrue( $schema->is_needed() );

		$data = $schema->generate();

		$this->assertSame( 'Event', $data['@type'] );
		$this->assertSame( 'https://example.org/sample-post/#event', $data['@id'] );
		$this->assertSame( '2026-09-15T18:00:00Z', $data['startDate'] );
		$this->assertSame( '2026-09-15T21:00:00Z', $data['endDate'] );
		$this->assertSame( 'https://schema.org/EventScheduled', $data['eventStatus'] );
		$this->assertSame( 'https://schema.org/OfflineEventAttendanceMode', $data['eventAttendanceMode'] );
		$this->assertSame( 'Place', $data['location']['@type'] );
		$this->assertSame( 'Springfield', $data['location']['address']['addressLocality'] );
		$this->assertSame( 'Person', $data['performer']['@type'] );
		$this->assertSame( 'Organization', $data['organizer']['@type'] );
		$this->assertSame( 'USD', $data['offers'][0]['priceCurrency'] );
		$this->assertSame( 'https://schema.org/InStock', $data['offers'][0]['availability'] );
	}

	/**
	 * Online events use VirtualLocation.
	 */
	public function test_event_schema_online_location(): void {
		$context = $this->make_context(
			array(
				'event' => array(
					'name'            => 'Webinar',
					'start_date'      => '2026-10-01T14:00:00Z',
					'attendance_mode' => 'OnlineEventAttendanceMode',
					'online_url'      => 'https://stream.example.org/webinar',
				),
			)
		);

		$data = ( new Event_Schema( $context ) )->generate();

		$this->assertSame( 'VirtualLocation', $data['location']['@type'] );
		$this->assertArrayNotHasKey( 'venue_name', $data['location'] );
	}

	/**
	 * Events missing a start date never emit schema.
	 */
	public function test_event_requires_start_date(): void {
		$context = $this->make_context(
			array(
				'event' => array( 'name' => 'Undated Event' ),
			)
		);

		$schema = new Event_Schema( $context );

		$this->assertFalse( $schema->is_needed() );
		$this->assertSame( array(), $schema->generate() );
	}

	/**
	 * Unknown enum values fall back to safe defaults or are dropped.
	 */
	public function test_event_enum_whitelisting(): void {
		$context = $this->make_context(
			array(
				'event' => array(
					'name'            => 'Cancelled Thing',
					'start_date'      => '2026-11-05T10:00:00+00:00',
					'status'          => 'TotallyMadeUpStatus',
					'attendance_mode' => 'BeamMeUpMode',
				),
			)
		);

		$data = ( new Event_Schema( $context ) )->generate();

		// Invalid enums are dropped rather than emitted as bogus IRIs.
		$this->assertArrayNotHasKey( 'eventStatus', $data );
		$this->assertArrayNotHasKey( 'eventAttendanceMode', $data );
	}

	/**
	 * ISO8601 sanitizer passes valid values and converts loose ones.
	 */
	public function test_iso8601_sanitizer(): void {
		$this->assertSame( '2026-01-02T03:04:05Z', Event_Schema::sanitize_iso8601( '2026-01-02T03:04:05Z' ) );
		$this->assertSame( '', Event_Schema::sanitize_iso8601( '' ) );
		$this->assertSame( '', Event_Schema::sanitize_iso8601( 'not a date at all ever' ) );

		$converted = Event_Schema::sanitize_iso8601( 'March 3rd, 2026 5pm' );
		$this->assertMatchesRegularExpression( '/^2026-03-03T\d{2}:\d{2}:\d{2}/', $converted );
	}

	/**
	 * Post_Meta sanitizes and stores recipe/event blocks end-to-end.
	 */
	public function test_post_meta_sanitize_rich_results(): void {
		$service = new Post_Meta();

		$clean = $service->sanitize(
			array(
				'title'  => 'My Post',
				'recipe' => array(
					'name'     => '<b>Sanitized</b> Soup',
					'cookTime' => '40 minutes',
				),
				'event'  => array(
					'name'       => 'Soup Tasting',
					'start_date' => '2026-12-01T18:00:00Z',
					'price'      => 'free-ish?',
				),
			)
		);

		$this->assertSame( 'Sanitized Soup', $clean['recipe']['name'] );
		$this->assertSame( '40 minutes', $clean['recipe']['cookTime'] );
		$this->assertSame( 'Soup Tasting', $clean['event']['name'] );

		// Incomplete blocks are dropped entirely.
		$empty = $service->sanitize(
			array(
				'recipe' => array( 'description' => 'no name' ),
				'event'  => array( 'description' => 'no name/date' ),
			)
		);

		$this->assertArrayNotHasKey( 'recipe', $empty );
		$this->assertArrayNotHasKey( 'event', $empty );
	}
}
