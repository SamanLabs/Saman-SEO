<?php
/**
 * Tests for sitemap image/video extension output.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Sitemap_Enhancer;
use Saman\SEO\Tests\TestCase;

/**
 * Video sitemap entries follow Google's required tags.
 */
class SitemapVideoEntriesTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var Sitemap_Enhancer
	 */
	private $enhancer;

	/**
	 * Transient store.
	 *
	 * @var array<string,mixed>
	 */
	private static $transients = array();

	/**
	 * Boot service and media stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$transients = array();
		$this->enhancer   = new Sitemap_Enhancer();

		Functions\when( 'get_post_field' )->alias(
			static function ( $field, $post_id ) {
				$post = self::$posts[ (int) $post_id ] ?? null;

				return $post ? $post->{$field} : '';
			}
		);
		Functions\when( 'has_post_thumbnail' )->justReturn( false );
		Functions\when( 'get_attached_media' )->justReturn( array() );
		Functions\when( 'get_transient' )->alias(
			static function ( $key ) {
				return self::$transients[ $key ] ?? false;
			}
		);
		Functions\when( 'set_transient' )->alias(
			static function ( $key, $value ) {
				self::$transients[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'wp_next_scheduled' )->justReturn( false );
		Functions\when( 'wp_schedule_single_event' )->justReturn( true );
	}

	/**
	 * Post entries get a complete YouTube video node, without remote calls
	 * and without hard-coded priority/changefreq.
	 */
	public function test_youtube_entry_has_required_tags(): void {
		Functions\expect( 'wp_remote_head' )->never();

		$this->make_post(
			array(
				'ID'           => 5,
				'post_title'   => 'Launch video',
				'post_excerpt' => 'Watch the launch.',
				'post_content' => '<!-- wp:embed {"url":"https://www.youtube.com/watch?v=dQw4w9WgXcQ"} -->',
			)
		);

		$entry = $this->enhancer->include_media_fields( array( 'loc' => 'https://example.org/launch/' ), 5, 'post' );

		$this->assertArrayNotHasKey( 'priority', $entry );
		$this->assertArrayNotHasKey( 'changefreq', $entry );
		$this->assertSame(
			array(
				'video:thumbnail_loc' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
				'video:title'         => 'Launch video',
				'video:description'   => 'Watch the launch.',
				'video:player_loc'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
			),
			$entry['video:video']
		);
	}

	/**
	 * A cached maxres check is honored.
	 */
	public function test_cached_maxres_thumbnail_used(): void {
		self::$transients[ 'saman_seo_yt_thumb_' . md5( 'dQw4w9WgXcQ' ) ] = 'maxres';

		$this->make_post(
			array(
				'ID'           => 6,
				'post_content' => 'https://youtu.be/dQw4w9WgXcQ',
			)
		);

		$entry = $this->enhancer->include_media_fields( array( 'loc' => 'https://example.org/x/' ), 6, 'post' );

		$this->assertSame( 'https://img.youtube.com/vi/dQw4w9WgXcQ/maxresdefault.jpg', $entry['video:video']['video:thumbnail_loc'] );
	}

	/**
	 * A self-hosted file without any thumbnail source emits no video node.
	 */
	public function test_file_video_without_thumbnail_skipped(): void {
		$this->make_post(
			array(
				'ID'           => 8,
				'post_content' => '<video src="https://example.org/clip.mp4"></video>',
			)
		);

		$entry = $this->enhancer->include_media_fields( array( 'loc' => 'https://example.org/clip/' ), 8, 'post' );

		$this->assertArrayNotHasKey( 'video:video', $entry );
	}

	/**
	 * Self-hosted files use the featured image and content_loc.
	 */
	public function test_file_video_uses_featured_image(): void {
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.org/poster.jpg' );

		$this->make_post(
			array(
				'ID'           => 9,
				'post_title'   => 'Clip',
				'post_content' => '<video src="https://example.org/clip.mp4"></video>',
			)
		);

		$video = $this->enhancer->include_media_fields( array( 'loc' => 'https://example.org/clip/' ), 9, 'post' )['video:video'];

		$this->assertSame( 'https://example.org/poster.jpg', $video['video:thumbnail_loc'] );
		$this->assertSame( 'https://example.org/clip.mp4', $video['video:content_loc'] );
		$this->assertSame( 'Clip', $video['video:description'] );
	}

	/**
	 * The urlset writer emits image/video extensions and escapes values.
	 */
	public function test_urlset_xml_includes_extensions(): void {
		$xml = $this->enhancer->build_urlset_xml(
			array(
				array(
					'loc'         => 'https://example.org/a/?x=1&y=2',
					'lastmod'     => '2026-10-01T00:00:00+00:00',
					'image:image' => array(
						array(
							'image:loc'     => 'https://example.org/a.jpg',
							'image:caption' => 'Ignored',
						),
					),
					'video:video' => array(
						'video:thumbnail_loc' => 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
						'video:title'         => 'Tom & Jerry',
						'video:description'   => 'Desc',
						'video:player_loc'    => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
					),
				),
				array(
					'loc'         => 'https://example.org/b/',
					'priority'    => 0.8,
					'video:video' => array(
						'video:title'       => 'No thumbnail',
						'video:description' => 'Desc',
						'video:content_loc' => 'https://example.org/b.mp4',
					),
				),
			),
			'https://example.org/sitemap.xsl'
		);

		$doc = simplexml_load_string( $xml );
		$this->assertNotFalse( $doc, 'urlset must be well-formed XML' );

		$this->assertStringContainsString( '<?xml-stylesheet type="text/xsl" href="https://example.org/sitemap.xsl" ?>', $xml );
		$this->assertStringContainsString( '<loc>https://example.org/a/?x=1&amp;y=2</loc>', $xml );
		$this->assertStringContainsString( '<image:image><image:loc>https://example.org/a.jpg</image:loc></image:image>', $xml );
		$this->assertStringNotContainsString( 'image:caption', $xml );
		$this->assertStringContainsString( '<video:title>Tom &amp; Jerry</video:title>', $xml );
		$this->assertLessThan(
			strpos( $xml, '<video:title>' ),
			strpos( $xml, '<video:thumbnail_loc>' ),
			'thumbnail_loc comes first'
		);
		$this->assertSame( 1, substr_count( $xml, '<video:video>' ), 'incomplete video nodes are dropped' );
		$this->assertStringContainsString( '<priority>0.8</priority>', $xml );
	}

	/**
	 * Entity-encoded titles (as get_the_title() returns them) are encoded
	 * exactly once in the XML.
	 */
	public function test_titles_are_not_double_encoded(): void {
		$this->make_post(
			array(
				'ID'           => 11,
				'post_title'   => 'Tom &amp; Jerry&#8217;s <em>big</em> day',
				'post_excerpt' => 'Fish &amp; chips &hellip;',
				'post_content' => 'https://youtu.be/dQw4w9WgXcQ',
			)
		);

		$entry = $this->enhancer->include_media_fields( array( 'loc' => 'https://example.org/tj/' ), 11, 'post' );
		$video = $entry['video:video'];

		$this->assertSame( "Tom & Jerry\u{2019}s big day", $video['video:title'] );
		$this->assertSame( "Fish & chips \u{2026}", $video['video:description'] );

		$xml = $this->enhancer->build_urlset_xml( array( $entry ) );

		$this->assertNotFalse( simplexml_load_string( $xml ) );
		$this->assertStringContainsString( "<video:title>Tom &amp; Jerry\u{2019}s big day</video:title>", $xml );
		$this->assertStringNotContainsString( '&amp;amp;', $xml );
		$this->assertStringNotContainsString( '&amp;#8217;', $xml );
	}

	/**
	 * Characters XML 1.0 forbids are dropped instead of breaking the file.
	 */
	public function test_invalid_xml_characters_are_stripped(): void {
		$xml = $this->enhancer->build_urlset_xml(
			array(
				array(
					'loc'         => 'https://example.org/c/',
					'video:video' => array(
						'video:thumbnail_loc' => 'https://example.org/t.jpg',
						'video:title'         => "Bell\x07 and form\x0C feed\tok",
						'video:description'   => "Bad \xC3\x28 bytes",
						'video:content_loc'   => 'https://example.org/c.mp4',
					),
				),
			)
		);

		$this->assertNotFalse( simplexml_load_string( $xml ), 'urlset must be well-formed XML' );
		$this->assertStringContainsString( "<video:title>Bell and form feed\tok</video:title>", $xml );
		$this->assertStringContainsString( '<video:description>Bad ', $xml );
	}

	/**
	 * The background check caches the maxres result long-term.
	 */
	public function test_background_thumbnail_check_caches_result(): void {
		Functions\when( 'wp_remote_head' )->justReturn( array( 'response' => array( 'code' => 200 ) ) );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );

		$this->enhancer->check_youtube_thumbnails( array( 'dQw4w9WgXcQ', 'bad id' ) );

		$this->assertSame( 'maxres', self::$transients[ 'saman_seo_yt_thumb_' . md5( 'dQw4w9WgXcQ' ) ] );
		$this->assertCount( 1, self::$transients );
	}
}
