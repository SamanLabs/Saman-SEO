<?php
/**
 * Tests for the site-level Reviews schema.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Settings_Controller;
use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Schema_Graph_Manager;
use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Types\LocalBusiness_Schema;
use Saman\SEO\Schema\Types\Organization_Schema;
use Saman\SEO\Schema\Types\Reviews_Schema;
use Saman\SEO\Tests\TestCase;

/**
 * Reviews schema: opt-in output, entity linking, validity and de-duplication.
 */
class ReviewsSchemaTest extends TestCase {

	/**
	 * Stub the helpers the schema classes call that the base harness lacks.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'is_paged' )->justReturn( false );
		Functions\when( 'rest_ensure_response' )->returnArg();
		Functions\when( 'sanitize_email' )->returnArg();

		$this->reset_registry();

		self::$context['front_page']                 = true;
		self::$options['SAMAN_SEO_module_local_seo'] = '1';
	}

	/**
	 * Reset the registry singleton so registrations never leak between tests.
	 */
	protected function tearDown(): void {
		$this->reset_registry();
		parent::tearDown();
	}

	/**
	 * Front-page context.
	 *
	 * @return Schema_Context
	 */
	private function front_context(): Schema_Context {
		$context            = new Schema_Context();
		$context->site_url  = 'https://example.org/';
		$context->site_name = 'Test Site';
		$context->post      = null;
		$context->post_type = '';
		$context->canonical = 'https://example.org/';
		$context->meta      = array();

		return $context;
	}

	/**
	 * Register the business + reviews types the way the plugin does.
	 *
	 * @param bool $with_reviews Whether the Reviews type is registered.
	 * @return void
	 */
	private function register_types( bool $with_reviews = true ): void {
		$registry = Schema_Registry::instance();
		$registry->register( 'organization', Organization_Schema::class, array( 'priority' => 2 ) );
		$registry->register( 'localbusiness', LocalBusiness_Schema::class, array( 'priority' => 5 ) );

		if ( $with_reviews ) {
			$registry->register( 'reviews', Reviews_Schema::class, array( 'priority' => 6 ) );
		}
	}

	/**
	 * Build the graph for a context.
	 *
	 * @param Schema_Context $context Context.
	 * @return array[] Graph pieces.
	 */
	private function graph( Schema_Context $context ): array {
		$manager = new Schema_Graph_Manager( Schema_Registry::instance() );
		$result  = $manager->build( $context );

		// The JSON-LD must survive an encode/decode round trip.
		$json = json_encode( $result );
		$this->assertNotFalse( $json );
		$this->assertSame( $json, json_encode( json_decode( $json, true ) ) );
		$this->assertSame( JSON_ERROR_NONE, json_last_error() );

		return $result['@graph'];
	}

	/**
	 * Pieces of a given @type.
	 *
	 * @param array[] $graph Graph.
	 * @param string  $type  @type.
	 * @return array[]
	 */
	private function of_type( array $graph, string $type ): array {
		return array_values(
			array_filter(
				$graph,
				static function ( $piece ) use ( $type ) {
					return ( $piece['@type'] ?? '' ) === $type;
				}
			)
		);
	}

	/**
	 * A complete review row.
	 *
	 * @param string $author Name.
	 * @param mixed  $rating Rating.
	 * @return array
	 */
	private function review( string $author, $rating ): array {
		return array(
			'author' => $author,
			'text'   => 'Great service from ' . $author . '.',
			'rating' => $rating,
			'date'   => '',
		);
	}

	/**
	 * Enable the feature with a business entity and some reviews.
	 *
	 * @param array $reviews Review rows.
	 */
	private function configure( array $reviews ): void {
		self::$options['SAMAN_SEO_homepage_knowledge_type']    = 'organization';
		self::$options['SAMAN_SEO_homepage_organization_name'] = 'Acme';
		self::$options['SAMAN_SEO_reviews_enabled']            = '1';
		self::$options['SAMAN_SEO_reviews_items']              = $reviews;
	}

	/**
	 * Off by default: the graph is byte-for-byte what it was without the type.
	 */
	public function test_disabled_by_default_leaves_graph_unchanged(): void {
		self::$options['SAMAN_SEO_homepage_knowledge_type'] = 'organization';
		self::$options['SAMAN_SEO_reviews_items']           = array( $this->review( 'Ann', 5 ) );

		$this->register_types( false );
		$without = $this->graph( $this->front_context() );

		$this->reset_registry();
		$this->register_types( true );
		$with = $this->graph( $this->front_context() );

		$this->assertSame( $without, $with );
		$this->assertSame( array(), $this->of_type( $with, 'Review' ) );
		$this->assertSame( array(), $this->of_type( $with, 'AggregateRating' ) );
	}

	/**
	 * Multiple reviews: one aggregate + one Review each, linked to Organization.
	 */
	public function test_multiple_reviews_link_to_organization(): void {
		$this->configure( array( $this->review( 'Ann', 5 ), $this->review( 'Bob', 4 ), $this->review( 'Cy', 3 ) ) );
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$aggregate = $this->of_type( $graph, 'AggregateRating' );
		$reviews   = $this->of_type( $graph, 'Review' );

		$this->assertCount( 1, $aggregate );
		$this->assertCount( 3, $reviews );

		// Computed from the reviews, not hardcoded.
		$this->assertSame( 4.0, $aggregate[0]['ratingValue'] );
		$this->assertSame( 3, $aggregate[0]['reviewCount'] );
		$this->assertSame( 5, $aggregate[0]['bestRating'] );
		$this->assertSame( 1, $aggregate[0]['worstRating'] );

		$this->assertSame( array( '@id' => 'https://example.org/#organization' ), $aggregate[0]['itemReviewed'] );

		$first = $reviews[0];
		$this->assertSame( 'Person', $first['author']['@type'] );
		$this->assertSame( 'Ann', $first['author']['name'] );
		$this->assertSame( 'Rating', $first['reviewRating']['@type'] );
		$this->assertSame( 5.0, $first['reviewRating']['ratingValue'] );
		$this->assertSame( 'Great service from Ann.', $first['reviewBody'] );
		$this->assertSame( 'https://example.org/#review-1', $first['@id'] );
		$this->assertArrayNotHasKey( 'datePublished', $first );
	}

	/**
	 * Every reference resolves to a node in the graph and @ids are unique.
	 */
	public function test_references_resolve_and_ids_are_unique(): void {
		$this->configure( array( $this->review( 'Ann', 5 ), $this->review( 'Bob', 4 ) ) );
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$ids = array();
		foreach ( $graph as $piece ) {
			if ( isset( $piece['@id'] ) ) {
				$ids[] = $piece['@id'];
			}
		}
		$this->assertSame( $ids, array_values( array_unique( $ids ) ), 'duplicate @id in graph' );

		foreach ( array_merge( $this->of_type( $graph, 'Review' ), $this->of_type( $graph, 'AggregateRating' ) ) as $piece ) {
			$this->assertContains( $piece['itemReviewed']['@id'], $ids );
		}
	}

	/**
	 * The existing business node is not modified by the additions.
	 */
	public function test_existing_nodes_are_untouched(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		$this->register_types();
		$with = $this->graph( $this->front_context() );

		$this->reset_registry();
		self::$options['SAMAN_SEO_reviews_enabled'] = '';
		$this->register_types();
		$baseline = $this->graph( $this->front_context() );

		$added = array_values(
			array_filter(
				$with,
				static function ( $piece ) {
					return ! in_array( $piece['@type'], array( 'Review', 'AggregateRating' ), true );
				}
			)
		);

		$this->assertSame( $baseline, $added );
		$this->assertArrayNotHasKey( 'aggregateRating', $added[0] );
		$this->assertArrayNotHasKey( 'review', $added[0] );
	}

	/**
	 * A single-location LocalBusiness is preferred over Organization.
	 */
	public function test_prefers_single_location_local_business(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		self::$options['SAMAN_SEO_local_business_name'] = 'Acme Plumbing';
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$this->assertSame(
			'https://example.org/#localbusiness',
			$this->of_type( $graph, 'Review' )[0]['itemReviewed']['@id']
		);
	}

	/**
	 * With multiple locations the brand-level Organization is reviewed.
	 */
	public function test_multi_location_falls_back_to_organization(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		self::$options['SAMAN_SEO_local_enable_locations'] = '1';
		self::$options['SAMAN_SEO_local_locations']        = array(
			array(
				'name'    => 'North',
				'enabled' => '1',
			),
			array(
				'name'    => 'South',
				'enabled' => '1',
			),
		);
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$this->assertSame(
			'https://example.org/#organization',
			$this->of_type( $graph, 'Review' )[0]['itemReviewed']['@id']
		);
	}

	/**
	 * One review: aggregate is that review.
	 */
	public function test_single_review(): void {
		$this->configure( array( $this->review( 'Ann', 4.5 ) ) );
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$this->assertCount( 1, $this->of_type( $graph, 'Review' ) );
		$aggregate = $this->of_type( $graph, 'AggregateRating' )[0];
		$this->assertSame( 4.5, $aggregate['ratingValue'] );
		$this->assertSame( 1, $aggregate['reviewCount'] );
	}

	/**
	 * No reviews and no manual values: nothing is emitted.
	 */
	public function test_no_reviews_outputs_nothing(): void {
		$this->configure( array() );
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$this->assertSame( array(), $this->of_type( $graph, 'Review' ) );
		$this->assertSame( array(), $this->of_type( $graph, 'AggregateRating' ) );
	}

	/**
	 * Incomplete and out-of-range rows are excluded from output and the average.
	 */
	public function test_incomplete_reviews_are_excluded(): void {
		$this->configure(
			array(
				$this->review( 'Ann', 5 ),
				array(
					'author' => '',
					'text'   => 'No name',
					'rating' => 5,
					'date'   => '',
				),
				array(
					'author' => 'NoText',
					'text'   => '',
					'rating' => 5,
					'date'   => '',
				),
				array(
					'author' => 'NoRating',
					'text'   => 'Hi',
					'rating' => '',
					'date'   => '',
				),
				array(
					'author' => 'TooHigh',
					'text'   => 'Hi',
					'rating' => 6,
					'date'   => '',
				),
				array(
					'author' => 'Zero',
					'text'   => 'Hi',
					'rating' => 0,
					'date'   => '',
				),
				array(
					'author' => 'Junk',
					'text'   => 'Hi',
					'rating' => 'abc',
					'date'   => '',
				),
			)
		);
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$reviews = $this->of_type( $graph, 'Review' );
		$this->assertCount( 1, $reviews );
		$this->assertSame( 'Ann', $reviews[0]['author']['name'] );
		$this->assertSame( 1, $this->of_type( $graph, 'AggregateRating' )[0]['reviewCount'] );
	}

	/**
	 * Owner-entered rating and count are used as typed.
	 */
	public function test_manual_aggregate_is_used(): void {
		$this->configure( array( $this->review( 'Ann', 5 ), $this->review( 'Bob', 4 ) ) );
		self::$options['SAMAN_SEO_reviews_rating_value'] = '4.8';
		self::$options['SAMAN_SEO_reviews_count']        = 120;
		$this->register_types();

		$aggregate = $this->of_type( $this->graph( $this->front_context() ), 'AggregateRating' )[0];

		$this->assertSame( 4.8, $aggregate['ratingValue'] );
		$this->assertSame( 120, $aggregate['reviewCount'] );
	}

	/**
	 * A count below the listed reviews is raised to match.
	 */
	public function test_count_never_below_listed_reviews(): void {
		$this->configure( array( $this->review( 'Ann', 5 ), $this->review( 'Bob', 4 ) ) );
		self::$options['SAMAN_SEO_reviews_rating_value'] = '4.5';
		self::$options['SAMAN_SEO_reviews_count']        = 1;
		$this->register_types();

		$aggregate = $this->of_type( $this->graph( $this->front_context() ), 'AggregateRating' )[0];

		$this->assertSame( 2, $aggregate['reviewCount'] );
	}

	/**
	 * A larger total count with no manual rating must not average a sample.
	 */
	public function test_no_aggregate_when_count_exceeds_listed_and_rating_blank(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		self::$options['SAMAN_SEO_reviews_count'] = 80;
		$this->register_types();

		$graph = $this->graph( $this->front_context() );

		$this->assertSame( array(), $this->of_type( $graph, 'AggregateRating' ) );
		$this->assertCount( 1, $this->of_type( $graph, 'Review' ) );
	}

	/**
	 * A valid date is passed through, an invalid one is dropped.
	 */
	public function test_review_date_validation(): void {
		$good         = $this->review( 'Ann', 5 );
		$good['date'] = '2026-03-04';
		$bad          = $this->review( 'Bob', 4 );
		$bad['date']  = '2026-02-31';
		$this->configure( array( $good, $bad ) );
		$this->register_types();

		$reviews = $this->of_type( $this->graph( $this->front_context() ), 'Review' );

		$this->assertSame( '2026-03-04', $reviews[0]['datePublished'] );
		$this->assertArrayNotHasKey( 'datePublished', $reviews[1] );
	}

	/**
	 * Front-page scope: no output on other pages.
	 */
	public function test_front_page_scope_skips_other_pages(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		$this->register_types();

		self::$context['front_page'] = false;
		$graph                       = $this->graph( $this->front_context() );

		$this->assertSame( array(), $this->of_type( $graph, 'Review' ) );
	}

	/**
	 * Selected-pages scope: output only on the chosen pages.
	 */
	public function test_selected_pages_scope(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		self::$options['SAMAN_SEO_reviews_scope']    = 'pages';
		self::$options['SAMAN_SEO_reviews_page_ids'] = array( 7 );
		$this->register_types();
		self::$context['front_page'] = false;

		$chosen             = $this->make_post(
			array(
				'ID'        => 7,
				'post_type' => 'page',
				'post_name' => 'reviews',
			)
		);
		$context            = $this->front_context();
		$context->post      = $chosen;
		$context->canonical = 'https://example.org/reviews/';
		$this->assertCount( 1, $this->of_type( $this->graph( $context ), 'Review' ) );

		$other              = $this->make_post(
			array(
				'ID'        => 8,
				'post_type' => 'page',
				'post_name' => 'about',
			)
		);
		$context->post      = $other;
		$context->canonical = 'https://example.org/about/';
		$this->assertSame( array(), $this->of_type( $this->graph( $context ), 'Review' ) );

		// Not output on the front page when only selected pages are chosen.
		self::$context['front_page'] = true;
		$this->assertSame( array(), $this->of_type( $this->graph( $this->front_context() ), 'Review' ) );
	}

	/**
	 * No business entity in the graph: no dangling references.
	 */
	public function test_no_entity_means_no_output(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		self::$options['SAMAN_SEO_homepage_knowledge_type'] = 'none';
		$this->register_types();

		$this->assertSame( array(), $this->of_type( $this->graph( $this->front_context() ), 'Review' ) );
	}

	/**
	 * The reviewed entity can be overridden by a filter.
	 */
	public function test_item_reviewed_filter(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		add_filter(
			'saman_seo_reviews_item_reviewed_id',
			static function () {
				return '';
			}
		);
		$this->register_types();

		$this->assertSame( array(), $this->of_type( $this->graph( $this->front_context() ), 'Review' ) );
	}

	/**
	 * Output reflects edits: changing the stored reviews changes the graph.
	 */
	public function test_output_follows_stored_data(): void {
		$this->configure( array( $this->review( 'Ann', 5 ) ) );
		$this->register_types();
		$this->assertCount( 1, $this->of_type( $this->graph( $this->front_context() ), 'Review' ) );

		self::$options['SAMAN_SEO_reviews_items'][] = $this->review( 'Bob', 3 );
		$graph                                      = $this->graph( $this->front_context() );
		$this->assertCount( 2, $this->of_type( $graph, 'Review' ) );
		$this->assertSame( 4.0, $this->of_type( $graph, 'AggregateRating' )[0]['ratingValue'] );

		self::$options['SAMAN_SEO_reviews_items'] = array();
		$this->assertSame( array(), $this->of_type( $this->graph( $this->front_context() ), 'Review' ) );
	}

	/**
	 * Review text is stored as plain text; markup is stripped.
	 */
	public function test_sanitize_items(): void {
		$items = Reviews_Schema::sanitize_items(
			array(
				array(
					'author' => '<b>Ann</b>',
					'text'   => 'Nice <script>x</script>',
					'rating' => '4.26',
					'date'   => '2026-01-02',
				),
				array(
					'author' => '',
					'text'   => '',
					'rating' => '',
					'date'   => '',
				),
				array(
					'author' => 'Draft',
					'text'   => '',
					'rating' => '9',
					'date'   => 'nope',
				),
				'not-an-array',
			)
		);

		$this->assertCount( 2, $items );
		$this->assertSame( 'Ann', $items[0]['author'] );
		$this->assertStringNotContainsString( '<', $items[0]['text'] );
		$this->assertSame( 4.3, $items[0]['rating'] );
		$this->assertSame( '', $items[1]['rating'] );
		$this->assertSame( '', $items[1]['date'] );

		$this->assertSame( '', Reviews_Schema::sanitize_rating_value( '7' ) );
		$this->assertSame( '4.5', Reviews_Schema::sanitize_rating_value( '4.5' ) );
		$this->assertSame( array( 3, 9 ), Reviews_Schema::sanitize_page_ids( array( '3', 'x', 9, 3, -0 ) ) );
	}

	/**
	 * The settings API accepts, sanitizes and stores the reviews settings.
	 */
	public function test_settings_api_stores_reviews(): void {
		$request = new class() {
			/**
			 * JSON body.
			 *
			 * @return array
			 */
			public function get_json_params() {
				return array(
					'reviews_enabled'      => true,
					'reviews_scope'        => 'bogus',
					'reviews_page_ids'     => array( 4, '5' ),
					'reviews_rating_value' => '9',
					'reviews_count'        => '-3',
					'reviews_items'        => array(
						array(
							'author' => 'Ann',
							'text'   => 'Fine',
							'rating' => 5,
							'date'   => '',
						),
					),
				);
			}
		};

		Functions\when( 'rest_sanitize_boolean' )->alias(
			static function ( $value ) {
				return (bool) $value;
			}
		);

		$response = ( new Settings_Controller() )->update_settings( $request );

		$this->assertArrayNotHasKey( 'rejected', $response['data'] );
		$this->assertSame( '1', self::$options['SAMAN_SEO_reviews_enabled'] );
		$this->assertSame( 'front_page', self::$options['SAMAN_SEO_reviews_scope'] );
		$this->assertSame( array( 4, 5 ), self::$options['SAMAN_SEO_reviews_page_ids'] );
		$this->assertSame( '', self::$options['SAMAN_SEO_reviews_rating_value'] );
		$this->assertSame( 0, self::$options['SAMAN_SEO_reviews_count'] );
		$this->assertSame( 'Ann', self::$options['SAMAN_SEO_reviews_items'][0]['author'] );
	}

	/**
	 * Reset the schema registry singleton via reflection.
	 *
	 * @return void
	 */
	private function reset_registry(): void {
		$ref      = new \ReflectionClass( Schema_Registry::class );
		$instance = $ref->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}
}
