<?php
/**
 * Tests for the post-type schema services (Course, JobPosting, ...).
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Book_Schema;
use Saman\SEO\Service\Course_Schema;
use Saman\SEO\Service\Job_Posting_Schema;
use Saman\SEO\Service\Movie_Schema;
use Saman\SEO\Service\Music_Schema;
use Saman\SEO\Service\Restaurant_Schema;
use Saman\SEO\Service\Service_Schema;
use Saman\SEO\Service\Software_Schema;
use Saman\SEO\Tests\TestCase;

/**
 * No placeholder values; required-field gating.
 */
class PostTypeSchemaTest extends TestCase {

	/**
	 * Placeholder strings the services used to hard-code.
	 *
	 * @var string[]
	 */
	private const PLACEHOLDERS = array(
		'Example University',
		'CS101',
		'978-3-16-148410-0',
		'1st Edition',
		'Example Director',
		'American',
		'$$',
		'Web Development',
		'Worldwide',
		'Windows, macOS, Linux',
		'Productivity',
		'New York, NY',
		'Full-time',
	);

	/**
	 * Store a schema meta field for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $key     Full meta key.
	 * @param mixed  $value   Value.
	 * @return void
	 */
	private function set_meta( int $post_id, string $key, $value ): void {
		self::$post_meta[ $post_id ][ $key ] = $value;
	}

	/**
	 * A course without meta emits only real post data.
	 */
	public function test_course_without_meta_has_no_provider_or_code(): void {
		$post = $this->make_post(
			array(
				'post_type'    => 'course',
				'post_title'   => 'Intro to PHP',
				'post_name'    => 'intro-to-php',
				'post_excerpt' => 'Learn PHP basics.',
			)
		);

		$graph = ( new Course_Schema() )->add_course_schema_to_graph( array(), $post );

		$this->assertCount( 1, $graph );
		$node = $graph[0];

		$this->assertSame( 'Course', $node['@type'] );
		$this->assertSame( 'https://example.org/intro-to-php/#course', $node['@id'] );
		$this->assertSame( 'Intro to PHP', $node['name'] );
		$this->assertSame( 'Learn PHP basics.', $node['description'] );
		$this->assertArrayNotHasKey( 'provider', $node );
		$this->assertArrayNotHasKey( 'courseCode', $node );
		$this->assertArrayNotHasKey( '@context', $node );
	}

	/**
	 * Course meta fields populate provider and courseCode.
	 */
	public function test_course_reads_meta(): void {
		$post = $this->make_post( array( 'post_type' => 'course' ) );
		$this->set_meta( $post->ID, '_saman_seo_course_provider', 'State College' );
		$this->set_meta( $post->ID, '_saman_seo_course_provider_url', 'https://college.example/' );
		$this->set_meta( $post->ID, '_saman_seo_course_code', 'PHP-200' );

		$node = ( new Course_Schema() )->get_node( $post );

		$this->assertSame(
			array(
				'@type'  => 'Organization',
				'name'   => 'State College',
				'sameAs' => 'https://college.example/',
			),
			$node['provider']
		);
		$this->assertSame( 'PHP-200', $node['courseCode'] );
	}

	/**
	 * The per-type filter can supply data and is applied to the node.
	 */
	public function test_course_filter_supplies_data(): void {
		$post = $this->make_post( array( 'post_type' => 'course' ) );

		add_filter(
			'saman_seo_schema_course',
			static function ( $node ) {
				$node['@context']   = 'https://schema.org';
				$node['courseCode'] = 'FROM-FILTER';
				return $node;
			}
		);

		$node = ( new Course_Schema() )->get_node( $post );

		$this->assertSame( 'FROM-FILTER', $node['courseCode'] );
		$this->assertArrayNotHasKey( '@context', $node );
	}

	/**
	 * Services ignore other post types and non-singular (null post) calls.
	 */
	public function test_course_ignores_other_post_types_and_null(): void {
		$post    = $this->make_post( array( 'post_type' => 'post' ) );
		$service = new Course_Schema();

		$this->assertSame( array( 'x' ), $service->add_course_schema_to_graph( array( 'x' ), $post ) );
		$this->assertSame( array( 'x' ), $service->add_course_schema_to_graph( array( 'x' ), null ) );
	}

	/**
	 * Every service emits no placeholder values when no meta is stored.
	 */
	public function test_no_service_emits_placeholders(): void {
		$services = array(
			'course'      => new Course_Schema(),
			'book'        => new Book_Schema(),
			'movie'       => new Movie_Schema(),
			'music'       => new Music_Schema(),
			'restaurant'  => new Restaurant_Schema(),
			'service'     => new Service_Schema(),
			'software'    => new Software_Schema(),
			'job_posting' => new Job_Posting_Schema(),
		);

		foreach ( $services as $post_type => $service ) {
			$post = $this->make_post(
				array(
					'post_type'  => $post_type,
					'post_title' => 'Thing',
				)
			);

			$json = (string) wp_json_encode( $service->get_node( $post ) );

			foreach ( self::PLACEHOLDERS as $placeholder ) {
				$this->assertStringNotContainsString( wp_json_encode( $placeholder ), $json, $post_type . ' leaked ' . $placeholder );
			}
			$this->assertStringNotContainsString( '"numTracks"', $json );
			$this->assertStringNotContainsString( '"@context"', $json );
		}
	}

	/**
	 * A job posting without a location is not emitted at all.
	 */
	public function test_job_posting_without_required_data_emits_nothing(): void {
		$post = $this->make_post(
			array(
				'post_type'    => 'job_posting',
				'post_title'   => 'Backend Engineer',
				'post_content' => '<p>Build APIs.</p>',
			)
		);

		$graph = ( new Job_Posting_Schema() )->add_job_posting_schema_to_graph( array(), $post );

		$this->assertSame( array(), $graph );
	}

	/**
	 * A job posting without a description is not emitted.
	 */
	public function test_job_posting_without_description_emits_nothing(): void {
		$post = $this->make_post(
			array(
				'post_type'    => 'job_posting',
				'post_content' => '',
			)
		);
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_locality', 'Austin' );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_country', 'US' );

		$this->assertSame( array(), ( new Job_Posting_Schema() )->get_node( $post ) );
	}

	/**
	 * With an address the posting is emitted, with Google enum employment types.
	 */
	public function test_job_posting_with_location_is_emitted(): void {
		$post = $this->make_post(
			array(
				'post_type'    => 'job_posting',
				'post_title'   => 'Backend Engineer',
				'post_name'    => 'backend-engineer',
				'post_content' => '<p>Build APIs.</p>',
			)
		);
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_locality', 'Austin' );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_country', 'US' );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_employment_type', 'Full-time, contract, bogus' );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_valid_through', '2099-01-31' );

		$node = ( new Job_Posting_Schema() )->get_node( $post );

		$this->assertSame( 'JobPosting', $node['@type'] );
		$this->assertSame( 'https://example.org/backend-engineer/#jobposting', $node['@id'] );
		$this->assertSame( 'Build APIs.', $node['description'] );
		$this->assertSame( array( 'FULL_TIME', 'CONTRACTOR' ), $node['employmentType'] );
		$this->assertSame( 'Austin', $node['jobLocation']['address']['addressLocality'] );
		$this->assertArrayNotHasKey( 'streetAddress', $node['jobLocation']['address'] );
		$this->assertStringStartsWith( '2099-01-31', $node['validThrough'] );
		$this->assertSame( 'Test Site', $node['hiringOrganization']['name'] );
		$this->assertArrayNotHasKey( '@context', $node );
	}

	/**
	 * Remote jobs need applicantLocationRequirements instead of an address.
	 */
	public function test_remote_job_posting_requires_applicant_location(): void {
		$post = $this->make_post( array( 'post_type' => 'job_posting' ) );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_location_type', 'telecommute' );

		$service = new Job_Posting_Schema();
		$this->assertSame( array(), $service->get_node( $post ) );

		$this->set_meta( $post->ID, '_saman_seo_job_posting_applicant_location', 'USA, Canada' );
		$node = $service->get_node( $post );

		$this->assertSame( 'TELECOMMUTE', $node['jobLocationType'] );
		$this->assertSame( 'Canada', $node['applicantLocationRequirements'][1]['name'] );
		$this->assertArrayNotHasKey( 'jobLocation', $node );
	}

	/**
	 * Without a hiring organization (site is a person) nothing is emitted.
	 */
	public function test_job_posting_requires_hiring_organization(): void {
		self::$options['SAMAN_SEO_homepage_knowledge_type'] = 'person';

		$post = $this->make_post( array( 'post_type' => 'job_posting' ) );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_locality', 'Austin' );

		$this->assertSame( array(), ( new Job_Posting_Schema() )->get_node( $post ) );

		$this->set_meta( $post->ID, '_saman_seo_job_posting_hiring_organization', 'Acme Corp' );
		$node = ( new Job_Posting_Schema() )->get_node( $post );

		$this->assertSame( 'Acme Corp', $node['hiringOrganization']['name'] );
	}

	/**
	 * Expired postings are dropped.
	 */
	public function test_expired_job_posting_emits_nothing(): void {
		$post = $this->make_post( array( 'post_type' => 'job_posting' ) );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_address_locality', 'Austin' );
		$this->set_meta( $post->ID, '_saman_seo_job_posting_valid_through', '2001-01-01' );

		$this->assertSame( array(), ( new Job_Posting_Schema() )->get_node( $post ) );
	}

	/**
	 * Employment type normalization maps to Google's enum only.
	 */
	public function test_employment_type_normalization(): void {
		$this->assertSame( 'FULL_TIME', Job_Posting_Schema::normalize_employment_type( 'Full-time' ) );
		$this->assertSame( 'PART_TIME', Job_Posting_Schema::normalize_employment_type( 'part time' ) );
		$this->assertSame( 'PER_DIEM', Job_Posting_Schema::normalize_employment_type( 'per diem' ) );
		$this->assertSame( 'INTERN', Job_Posting_Schema::normalize_employment_type( 'Internship' ) );
		$this->assertSame( '', Job_Posting_Schema::normalize_employment_type( 'Gig' ) );
	}

	/**
	 * The @id base follows the per-post canonical override.
	 */
	public function test_node_id_uses_canonical_override(): void {
		$post = $this->make_post( array( 'post_type' => 'book' ) );
		$this->set_meta( $post->ID, '_SAMAN_SEO_meta', array( 'canonical' => 'https://example.org/the-book/' ) );

		$node = ( new Book_Schema() )->get_node( $post );

		$this->assertSame( 'https://example.org/the-book/#book', $node['@id'] );
		$this->assertSame( 'https://example.org/the-book/', $node['url'] );
		$this->assertArrayNotHasKey( 'author', $node );
		$this->assertArrayNotHasKey( 'isbn', $node );
	}
}
