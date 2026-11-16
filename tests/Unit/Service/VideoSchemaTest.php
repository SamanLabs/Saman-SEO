<?php
/**
 * Tests for VideoObject schema generation.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Video_Schema;
use Saman\SEO\Tests\TestCase;

/**
 * VideoObject nodes carry Google's required fields and never block a render.
 */
class VideoSchemaTest extends TestCase {

	/**
	 * Transient store.
	 *
	 * @var array<string,mixed>
	 */
	private static $transients = array();

	/**
	 * Events passed to wp_schedule_single_event().
	 *
	 * @var array<int,array>
	 */
	private static $scheduled = array();

	/**
	 * Stub transients, cron and HTTP.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$transients = array();
		self::$scheduled  = array();

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
		Functions\when( 'wp_schedule_single_event' )->alias(
			static function ( $time, $hook, $args ) {
				self::$scheduled[] = array( $hook, $args );
				return true;
			}
		);
		Functions\when( 'wp_remote_get' )->alias(
			static function () {
				throw new \RuntimeException( 'No remote request may run while the page renders.' );
			}
		);
	}

	/**
	 * Build the graph for a post with the given content.
	 *
	 * @param string $content Post content.
	 * @return array
	 */
	private function graph_for( $content ): array {
		$post = $this->make_post( array( 'post_content' => $content ) );

		return ( new Video_Schema() )->add_video_schema_to_graph( array(), $post );
	}

	/**
	 * An uncached YouTube video renders from local data and queues a fetch.
	 */
	public function test_uncached_youtube_video_renders_without_remote_call(): void {
		$graph = $this->graph_for( 'Watch https://www.youtube.com/watch?v=dQw4w9WgXcQ now' );

		$this->assertCount( 1, $graph );
		$this->assertSame( 'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', $graph[0]['thumbnailUrl'] );
		$this->assertNotSame( '', trim( (string) $graph[0]['description'] ) );

		$this->assertSame( Video_Schema::OEMBED_FETCH_HOOK, self::$scheduled[0][0] );
		$this->assertSame(
			array(
				'platform' => 'youtube',
				'id'       => 'dQw4w9WgXcQ',
			),
			self::$scheduled[0][1][0][0]
		);
	}

	/**
	 * A Vimeo video with no thumbnail anywhere is left out rather than
	 * emitted without the required thumbnailUrl.
	 */
	public function test_vimeo_without_any_thumbnail_is_skipped(): void {
		$this->assertSame( array(), $this->graph_for( 'See https://vimeo.com/123456789' ) );
	}

	/**
	 * Without platform metadata a Vimeo video uses the featured image.
	 */
	public function test_vimeo_falls_back_to_featured_image(): void {
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( 'https://example.org/featured.jpg' );

		$graph = $this->graph_for( 'See https://vimeo.com/123456789' );

		$this->assertSame( 'https://example.org/featured.jpg', $graph[0]['thumbnailUrl'] );
	}

	/**
	 * The cron handler caches metadata, which the next render then uses.
	 */
	public function test_background_fetch_feeds_the_next_render(): void {
		Functions\when( 'wp_remote_get' )->justReturn( array() );
		Functions\when( 'wp_remote_retrieve_response_code' )->justReturn( 200 );
		Functions\when( 'wp_remote_retrieve_body' )->justReturn(
			json_encode(
				array(
					'title'         => 'Platform Title',
					'thumbnail_url' => 'https://i.vimeocdn.com/video/1.jpg',
					'author_name'   => 'Channel',
					'author_url'    => 'https://vimeo.com/channel',
				)
			)
		);

		( new Video_Schema() )->fetch_pending_oembed(
			array(
				array(
					'platform' => 'vimeo',
					'id'       => '123456789',
				),
				array(
					'platform' => 'vimeo',
					'id'       => 'not-an-id',
				),
			)
		);

		$this->assertCount( 1, self::$transients );

		$graph = $this->graph_for( 'See https://vimeo.com/123456789' );

		$this->assertSame( 'Platform Title', $graph[0]['name'] );
		$this->assertSame( 'https://i.vimeocdn.com/video/1.jpg', $graph[0]['thumbnailUrl'] );
		$this->assertSame( 'https://vimeo.com/channel', $graph[0]['author']['url'] );
		$this->assertSame( array(), self::$scheduled );
	}
}
