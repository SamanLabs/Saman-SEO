<?php
/**
 * Tests for the Image SEO service.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Image_SEO;
use Saman\SEO\Tests\TestCase;

/**
 * Image SEO analyzer coverage.
 */
class ImageSEOTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var Image_SEO
	 */
	private Image_SEO $service;

	/**
	 * Boot service before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		$this->service = new Image_SEO();
	}

	/**
	 * Missing alt text is a high-severity issue.
	 */
	public function test_audit_flags_missing_alt(): void {
		self::$posts[11]  = new \WP_Post( array( 'ID' => 11, 'post_type' => 'attachment', 'post_title' => 'IMG_4021' ) );
		self::$post_meta[11]['_wp_attached_file']        = '2026/01/IMG_4021.jpg';
		self::$post_meta[11]['_wp_attachment_image_alt'] = '';

		$report = $this->service->audit_attachment( 11 );

		$this->assertSame( 'missing_alt', $report['issues'][0]['code'] );
		$this->assertSame( 'high', $report['issues'][0]['severity'] );
	}

	/**
	 * Camera-default filenames are flagged as generic.
	 */
	public function test_audit_flags_camera_filenames(): void {
		self::$posts[12]  = new \WP_Post( array( 'ID' => 12, 'post_type' => 'attachment', 'post_title' => 'DSC_0042' ) );
		self::$post_meta[12]['_wp_attached_file']        = '2026/02/DSC_0042.jpg';
		self::$post_meta[12]['_wp_attachment_image_alt'] = 'Sunset over the harbour';

		$issues = wp_list_pluck( $this->service->audit_attachment( 12 )['issues'], 'code' );

		$this->assertContains( 'generic_filename', $issues );
		$this->assertNotContains( 'missing_alt', $issues );
	}

	/**
	 * Alt text that merely repeats a generic filename is flagged.
	 */
	public function test_audit_flags_alt_repeating_generic_filename(): void {
		self::$posts[13] = new \WP_Post( array( 'ID' => 13, 'post_type' => 'attachment', 'post_title' => 'screenshot' ) );
		self::$post_meta[13]['_wp_attached_file']        = '2026/03/screenshot.jpg';
		self::$post_meta[13]['_wp_attachment_image_alt'] = 'screenshot';

		$issues = wp_list_pluck( $this->service->audit_attachment( 13 )['issues'], 'code' );

		$this->assertContains( 'generic_alt', $issues );
	}

	/**
	 * A fully healthy image yields no issues.
	 */
	public function test_audit_clean_attachment(): void {
		self::$posts[14] = new \WP_Post( array( 'ID' => 14, 'post_type' => 'attachment', 'post_title' => 'Red bicycle' ) );
		self::$post_meta[14]['_wp_attached_file']        = '2026/04/red-bicycle-leaning-on-wall.jpg';
		self::$post_meta[14]['_wp_attachment_image_alt'] = 'A red bicycle leaning against a brick wall';

		$report = $this->service->audit_attachment( 14 );

		$this->assertSame( array(), $report['issues'] );
		$this->assertSame( 'red-bicycle-leaning-on-wall', $report['suggested_filename'] );
	}

	/**
	 * Filename heuristics cover hashes, numbers, and generic tokens.
	 */
	public function test_generic_filename_detection(): void {
		$service = $this->service;

		$this->assertTrue( $service->is_generic_filename( 'IMG_2045.jpg' ) );
		$this->assertTrue( $service->is_generic_filename( 'Screenshot_2026-01-01-at-10.00.00.png' ) );
		$this->assertTrue( $service->is_generic_filename( '3f8a1c9d7e2b4a6f.png' ) ); // hex words? letters only via [a-z].
		$this->assertTrue( $service->is_generic_filename( '12345.jpg' ) );
		$this->assertTrue( $service->is_generic_filename( 'image.png' ) );
		$this->assertTrue( $service->is_generic_filename( 'photo (1).jpg' ) );

		$this->assertFalse( $service->is_generic_filename( 'cold-brew-coffee-guide.jpg' ) );
		$this->assertFalse( $service->is_generic_filename( 'team-photo-office.jpg' ) );
		$this->assertFalse( $service->is_generic_filename( '' ) );
	}

	/**
	 * Suggested filenames are slugs without scaling artefacts.
	 */
	public function test_suggest_filename_slugifies(): void {
		$service = $this->service;

		$this->assertSame( 'my-red-bike', $service->suggest_filename( 'My Red Bike.JPG' ) );
		$this->assertSame( 'hero-shot', $service->suggest_filename( 'hero-shot-scaled.jpg' ) );
		$this->assertSame(
			'under-scores-become-dashes',
			$service->suggest_filename( 'under_scores__become dashes.png' )
		);
	}

	/**
	 * Alt suggestions prefer titles over filenames.
	 */
	public function test_suggest_alt_prefers_descriptive_title(): void {
		self::$posts[15] = new \WP_Post( array( 'ID' => 15, 'post_type' => 'attachment', 'post_title' => 'Golden retriever puppy' ) );
		self::$post_meta[15]['_wp_attached_file'] = '2026/05/IMG_9001.jpeg';

		$this->assertSame( 'Golden retriever puppy', $this->service->suggest_alt_text( 15 ) );
	}

	/**
	 * Generic titles fall back to filename-derived phrases.
	 */
	public function test_suggest_alt_falls_back_to_filename_phrase(): void {
		self::$posts[16] = new \WP_Post( array( 'ID' => 16, 'post_type' => 'attachment', 'post_title' => 'IMG_9002' ) );
		self::$post_meta[16]['_wp_attached_file'] = '2026/06/puppy-first-day-home.jpg';

		$this->assertSame( 'puppy first day home', $this->service->suggest_alt_text( 16 ) );
	}

	/**
	 * No suggestion is produced when both title and filename are junk.
	 */
	public function test_suggest_alt_returns_empty_for_junk(): void {
		self::$posts[17] = new \WP_Post( array( 'ID' => 17, 'post_type' => 'attachment', 'post_title' => '' ) );
		self::$post_meta[17]['_wp_attached_file'] = '2026/07/IMG_9003.jpg';

		$this->assertSame( '', $this->service->suggest_alt_text( 17 ) );
	}

	/**
	 * Content audit detects missing alts, lazy loading, and bad filenames.
	 */
	public function test_content_audit(): void {
		$html = '<img src="https://example.org/wp-content/uploads/red-kite-beach.jpg" alt="Red kite at the beach" loading="lazy">'
			. '<img src="https://example.org/wp-content/uploads/IMG_2045.jpg">'
			. '<img src="https://cdn.example.org/sunset-pier.jpg" alt="">';

		$report = $this->service->audit_content( $html );

		$this->assertSame( 3, $report['total'] );
		$this->assertSame( 1, $report['with_alt'] );
		$this->assertSame( 1, $report['lazy_count'] );

		$first  = $report['images'][0];
		$second = $report['images'][1];
		$third  = $report['images'][2];

		$this->assertSame( array(), $first['issues'] );

		$second_codes = wp_list_pluck( $second['issues'], 'code' );
		$this->assertContains( 'missing_alt_attribute', $second_codes );
		$this->assertContains( 'missing_lazy_loading', $second_codes );
		$this->assertContains( 'generic_filename', $second_codes );

		$third_codes = wp_list_pluck( $third['issues'], 'code' );
		$this->assertContains( 'empty_alt', $third_codes );
		$this->assertContains( 'missing_lazy_loading', $third_codes );
	}

	/**
	 * Empty content produces an empty report instead of warnings.
	 */
	public function test_content_audit_empty_html(): void {
		$report = $this->service->audit_content( '' );

		$this->assertSame( 0, $report['total'] );
		$this->assertSame( array(), $report['images'] );
	}

	/**
	 * Auto-fill writes suggested alt text on upload when enabled.
	 */
	public function test_auto_fill_on_upload(): void {
		self::$posts[18] = new \WP_Post( array( 'ID' => 18, 'post_type' => 'attachment', 'post_title' => 'Harbour bridge at dusk' ) );
		self::$post_meta[18]['_wp_attached_file'] = '2026/08/harbour-shot.jpg';

		$this->service->auto_fill_alt_on_upload( 18 );

		$this->assertSame(
			'Harbour bridge at dusk',
			self::$post_meta[18]['_wp_attachment_image_alt']
		);
	}

	/**
	 * Auto-fill never overwrites existing alt text.
	 */
	public function test_auto_fill_respects_existing_alt(): void {
		self::$posts[19] = new \WP_Post( array( 'ID' => 19, 'post_type' => 'attachment', 'post_title' => 'Something else' ) );
		self::$post_meta[19]['_wp_attached_file']        = '2026/09/manual.jpg';
		self::$post_meta[19]['_wp_attachment_image_alt'] = 'Human written alt';

		$this->service->auto_fill_alt_on_upload( 19 );

		$this->assertSame( 'Human written alt', self::$post_meta[19]['_wp_attachment_image_alt'] );
	}

	/**
	 * Auto-fill can be disabled in settings.
	 */
	public function test_auto_fill_respects_disabled_setting(): void {
		update_option( Image_SEO::OPTION_SETTINGS, array( 'auto_alt' => false ) );

		self::$posts[20] = new \WP_Post( array( 'ID' => 20, 'post_type' => 'attachment', 'post_title' => 'Disabled case' ) );

		$this->service->auto_fill_alt_on_upload( 20 );

		$this->assertArrayNotHasKey( '_wp_attachment_image_alt', self::$post_meta[20] ?? array() );
	}
}
