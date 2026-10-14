<?php
/**
 * Tests for Schema Validator severities and rich-result eligibility notices.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Saman\SEO\Api\Schema_Validator_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Validator coverage for retired rich results and updated requirements.
 */
class SchemaValidatorEligibilityTest extends TestCase {

	/**
	 * Controller under test.
	 *
	 * @var Schema_Validator_Controller
	 */
	private $controller;

	/**
	 * Boot controller.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->controller = new Schema_Validator_Controller();
	}

	/**
	 * FAQPage gets an info notice; a missing mainEntity is only a warning.
	 */
	public function test_faq_page_is_info_and_warning_not_error(): void {
		$result = $this->controller->validate_schema( array( '@type' => 'FAQPage' ) );

		$this->assertSame( array(), $result['errors'] );
		$this->assertCount( 1, $result['warnings'] );
		$this->assertCount( 1, $result['notices'] );
		$this->assertStringContainsString( 'May 2026', $result['notices'][0] );
	}

	/**
	 * HowTo missing fields are warnings, plus an info notice.
	 */
	public function test_howto_missing_fields_are_warnings(): void {
		$result = $this->controller->validate_schema( array( '@type' => 'HowTo' ) );

		$this->assertSame( array(), $result['errors'] );
		$this->assertCount( 2, $result['warnings'] );
		$this->assertCount( 1, $result['notices'] );
	}

	/**
	 * Each retired type produces an info notice and no error.
	 *
	 * @dataProvider retired_types
	 *
	 * @param string $type Schema type.
	 */
	public function test_retired_types_get_info_notice( string $type ): void {
		$result = $this->controller->validate_schema(
			array(
				'@type' => $type,
				'name'  => 'Example',
			)
		);

		$this->assertSame( array(), $result['errors'] );
		$this->assertNotEmpty( $result['notices'] );
	}

	/**
	 * Retired rich-result types.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function retired_types(): array {
		$types = array( 'FAQPage', 'HowTo', 'Course', 'Book', 'ClaimReview', 'EstimatedSalary', 'Occupation', 'LearningResource', 'SpecialAnnouncement', 'Vehicle', 'Car', 'Quiz', 'Dataset' );

		return array_combine( $types, array_map( static fn( $type ) => array( $type ), $types ) );
	}

	/**
	 * Supported rich result types carry no eligibility notice.
	 */
	public function test_product_has_no_notice(): void {
		$result = $this->controller->validate_schema(
			array(
				'@type'  => 'Product',
				'name'   => 'Widget',
				'offers' => array( '@type' => 'Offer' ),
				'image'  => 'https://example.org/w.jpg',
			)
		);

		$this->assertSame( array(), $result['notices'] );
		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( array(), $result['warnings'] );
	}

	/**
	 * VideoObject thumbnailUrl is required.
	 */
	public function test_video_thumbnail_is_required(): void {
		$result = $this->controller->validate_schema(
			array(
				'@type'      => 'VideoObject',
				'name'       => 'Clip',
				'uploadDate' => '2026-01-01',
			)
		);

		$this->assertContains( 'Missing required "thumbnailUrl" property', $result['errors'] );
	}

	/**
	 * Article headline is recommended, not required.
	 */
	public function test_article_headline_is_a_warning(): void {
		$result = $this->controller->validate_schema( array( '@type' => 'BlogPosting' ) );

		$this->assertSame( array(), $result['errors'] );
		$this->assertContains( 'Missing recommended "headline" property', $result['warnings'] );
	}

	/**
	 * @type arrays are checked per type and notices apply to each.
	 */
	public function test_type_array_is_handled(): void {
		$result = $this->controller->validate_schema(
			array(
				'@type'      => array( 'VideoObject', 'LearningResource' ),
				'name'       => 'Lesson',
				'uploadDate' => '2026-01-01',
			)
		);

		$this->assertSame( 'VideoObject, LearningResource', $result['type'] );
		$this->assertSame( array( 'VideoObject', 'LearningResource' ), $result['types'] );
		$this->assertContains( 'Missing required "thumbnailUrl" property', $result['errors'] );
		$this->assertCount( 1, $result['notices'] );
		$this->assertStringContainsString( 'Learning video', $result['notices'][0] );
	}

	/**
	 * @graph members, arrays and IRI-prefixed types are flattened.
	 */
	public function test_graph_and_array_extraction(): void {
		$html  = '<script type="application/ld+json">{"@context":"https://schema.org","@graph":[{"@type":"WebPage"},{"@type":"schema:FAQPage","mainEntity":[{"@type":"Question"}]}]}</script>';
		$html .= '<script type="application/ld+json">[{"@type":"Dataset","name":"D"},{"@graph":[{"@type":"HowTo"}]}]</script>';

		$nodes = $this->controller->extract_jsonld( $html );

		$this->assertCount( 4, $nodes );

		$faq = $this->controller->validate_schema( $nodes[1] );
		$this->assertSame( array( 'FAQPage' ), $faq['types'] );
		$this->assertSame( array(), $faq['warnings'] );
		$this->assertCount( 1, $faq['notices'] );

		$this->assertNotEmpty( $this->controller->validate_schema( $nodes[2] )['notices'] );
		$this->assertNotEmpty( $this->controller->validate_schema( $nodes[3] )['notices'] );
	}

	/**
	 * Event datetimes without an offset get a warning; online-only gets info.
	 */
	public function test_event_timezone_and_online_notice(): void {
		$result = $this->controller->validate_schema(
			array(
				'@type'               => 'Event',
				'name'                => 'Webinar',
				'startDate'           => '2026-09-15T19:00',
				'location'            => array( '@type' => 'VirtualLocation' ),
				'eventAttendanceMode' => 'https://schema.org/OnlineEventAttendanceMode',
			)
		);

		$this->assertSame( array(), $result['errors'] );
		$this->assertCount( 1, $result['warnings'] );
		$this->assertCount( 1, $result['notices'] );

		$this->assertTrue( Schema_Validator_Controller::datetime_lacks_offset( '2026-09-15T19:00:00' ) );
		$this->assertFalse( Schema_Validator_Controller::datetime_lacks_offset( '2026-09-15T19:00:00-05:00' ) );
		$this->assertFalse( Schema_Validator_Controller::datetime_lacks_offset( '2026-09-15T19:00:00Z' ) );
		$this->assertFalse( Schema_Validator_Controller::datetime_lacks_offset( '2026-09-15' ) );
	}

	/**
	 * Missing @type is still an error.
	 */
	public function test_missing_type_is_error(): void {
		$result = $this->controller->validate_schema( array( 'name' => 'x' ) );

		$this->assertSame( 'Unknown', $result['type'] );
		$this->assertNotEmpty( $result['errors'] );
	}
}
