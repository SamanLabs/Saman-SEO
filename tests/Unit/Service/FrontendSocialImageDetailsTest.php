<?php
/**
 * Tests for og:image width/height/type/alt and twitter:image:alt.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace {
	/*
	 * Neutral media/cache fallbacks. Brain Monkey declares a stubbed function
	 * for the rest of the run, so once this file's tests stub these, every
	 * later social-tag test would hit an un-mocked function. Real no-op
	 * definitions (redefinable by Patchwork) keep the other tests working.
	 */
	if ( ! function_exists( 'wp_get_upload_dir' ) ) {
		function wp_get_upload_dir() { // phpcs:ignore
			return array( 'baseurl' => 'https://example.org/wp-content/uploads' );
		}
	}
	if ( ! function_exists( 'attachment_url_to_postid' ) ) {
		function attachment_url_to_postid( $url ) { // phpcs:ignore
			return 0;
		}
	}
	if ( ! function_exists( 'wp_cache_get' ) ) {
		function wp_cache_get( $key, $group = '' ) { // phpcs:ignore
			return false;
		}
	}
	if ( ! function_exists( 'wp_cache_set' ) ) {
		function wp_cache_set( $key, $data, $group = '', $expire = 0 ) { // phpcs:ignore
			return true;
		}
	}
	if ( ! function_exists( 'wp_get_attachment_image_src' ) ) {
		function wp_get_attachment_image_src( $attachment_id, $size = 'thumbnail' ) { // phpcs:ignore
			return false;
		}
	}
	if ( ! function_exists( 'wp_get_attachment_metadata' ) ) {
		function wp_get_attachment_metadata( $attachment_id ) { // phpcs:ignore
			return false;
		}
	}
	if ( ! function_exists( 'get_post_mime_type' ) ) {
		function get_post_mime_type( $post = null ) { // phpcs:ignore
			return false;
		}
	}
	if ( ! function_exists( 'wp_basename' ) ) {
		function wp_basename( $path, $suffix = '' ) { // phpcs:ignore
			return basename( (string) $path, $suffix );
		}
	}
}

namespace Saman\SEO\Tests\Unit\Service {

use Brain\Monkey\Functions;
use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Social image detail coverage.
 */
class FrontendSocialImageDetailsTest extends TestCase {

	/**
	 * Number of attachment_url_to_postid() calls.
	 *
	 * @var int
	 */
	private $lookups = 0;

	/**
	 * Install media stubs: attachment 77 is uploads/cover.jpg (1200x630)
	 * with a 600x315 intermediate size.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->lookups = 0;
		$cache         = array();

		Functions\when( 'wp_get_upload_dir' )->justReturn(
			array( 'baseurl' => 'https://example.org/wp-content/uploads' )
		);
		Functions\when( 'attachment_url_to_postid' )->alias(
			function ( $url ) {
				++$this->lookups;

				return 'https://example.org/wp-content/uploads/cover.jpg' === $url ? 77 : 0;
			}
		);
		Functions\when( 'wp_cache_get' )->alias(
			static function ( $key ) use ( &$cache ) {
				return $cache[ $key ] ?? false;
			}
		);
		Functions\when( 'wp_cache_set' )->alias(
			static function ( $key, $value ) use ( &$cache ) {
				$cache[ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'get_post_mime_type' )->justReturn( 'image/jpeg' );
		Functions\when( 'wp_get_attachment_image_src' )->justReturn(
			array( 'https://example.org/wp-content/uploads/cover.jpg', 1200, 630, false )
		);
		Functions\when( 'wp_get_attachment_metadata' )->justReturn(
			array(
				'width'  => 1200,
				'height' => 630,
				'sizes'  => array(
					'medium_large' => array(
						'file'      => 'cover-600x315.jpg',
						'width'     => 600,
						'height'    => 315,
						'mime-type' => 'image/jpeg',
					),
				),
			)
		);
		Functions\when( 'wp_basename' )->alias(
			static function ( $path ) {
				return basename( (string) $path );
			}
		);
	}

	/**
	 * Parse rendered meta tags into property => content pairs.
	 *
	 * @param string $html Rendered output.
	 * @return array<string,string>
	 */
	private function parse_tags( string $html ): array {
		$tags = array();
		preg_match_all( '/<meta (?:property|name)="([^"]+)" content="([^"]*)"/', $html, $matches, PREG_SET_ORDER );
		foreach ( $matches as $match ) {
			$tags[ $match[1] ] = $match[2];
		}

		return $tags;
	}

	/**
	 * Render the social tags for the current post.
	 *
	 * @param Frontend|null $frontend Instance to reuse.
	 * @return array<string,string>
	 */
	private function render( Frontend $frontend = null ): array {
		$frontend = $frontend ?: new Frontend();

		return $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );
	}

	/**
	 * A local og_image URL gets dimensions, type and alt text.
	 */
	public function test_local_image_emits_dimensions_type_and_alt(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://example.org/wp-content/uploads/cover.jpg' );
		self::$post_meta[77]['_wp_attachment_image_alt'] = 'A harbour at dusk';

		$tags = $this->render();

		$this->assertSame( '1200', $tags['og:image:width'] );
		$this->assertSame( '630', $tags['og:image:height'] );
		$this->assertSame( 'image/jpeg', $tags['og:image:type'] );
		$this->assertSame( 'A harbour at dusk', $tags['og:image:alt'] );
		$this->assertSame( 'A harbour at dusk', $tags['twitter:image:alt'] );
	}

	/**
	 * An intermediate-size URL resolves to that size's dimensions.
	 */
	public function test_intermediate_size_uses_size_dimensions(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://example.org/wp-content/uploads/cover-600x315.jpg' );

		$tags = $this->render();

		$this->assertSame( '600', $tags['og:image:width'] );
		$this->assertSame( '315', $tags['og:image:height'] );
	}

	/**
	 * Featured images reuse the known attachment ID (no URL lookup).
	 */
	public function test_featured_image_skips_url_lookup(): void {
		$this->make_post();
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 77 );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( 'https://example.org/wp-content/uploads/cover.jpg' );

		$tags = $this->render();

		$this->assertSame( '1200', $tags['og:image:width'] );
		$this->assertSame( 0, $this->lookups );
	}

	/**
	 * Lookups are cached across renders.
	 */
	public function test_lookup_is_cached(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://example.org/wp-content/uploads/cover.jpg' );

		$frontend = new Frontend();
		$this->render( $frontend );
		$this->render( $frontend );
		$this->render();

		$this->assertSame( 1, $this->lookups );
	}

	/**
	 * External images get no dimension tags and no title-as-alt.
	 */
	public function test_external_image_omits_details_and_alt(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://cdn.example.net/cover.jpg' );

		$tags = $this->render();

		$this->assertSame( 'https://cdn.example.net/cover.jpg', $tags['og:image'] );
		$this->assertArrayNotHasKey( 'og:image:width', $tags );
		$this->assertArrayNotHasKey( 'og:image:alt', $tags );
		$this->assertArrayNotHasKey( 'twitter:image:alt', $tags );
		$this->assertSame( 0, $this->lookups );
	}

	/**
	 * A local image without alt text omits the alt tags instead of using
	 * the page title.
	 */
	public function test_missing_alt_is_omitted(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://example.org/wp-content/uploads/cover.jpg' );

		$tags = $this->render();

		$this->assertSame( '1200', $tags['og:image:width'] );
		$this->assertArrayNotHasKey( 'twitter:image:alt', $tags );
		$this->assertArrayNotHasKey( 'og:image:alt', $tags );
	}

	/**
	 * A filter returning an array with a 'url' key still gets details,
	 * without an "Array to string conversion" notice.
	 */
	public function test_filtered_array_image_with_url_gets_details(): void {
		$this->make_post();
		add_filter(
			'saman_seo_og_image',
			static function () {
				return array( 'url' => 'https://example.org/wp-content/uploads/cover.jpg' );
			}
		);

		$tags = $this->render();

		$this->assertSame( '1200', $tags['og:image:width'] );
	}

	/**
	 * Other non-string filter results are skipped for details.
	 */
	public function test_filtered_non_string_image_skips_details(): void {
		$this->make_post();
		add_filter(
			'saman_seo_og_image',
			static function () {
				return array( 'https://example.org/wp-content/uploads/cover.jpg', 'https://example.org/b.jpg' );
			}
		);
		add_filter(
			'saman_seo_twitter_image',
			static function () {
				return 42;
			}
		);

		$tags = $this->render();

		$this->assertArrayNotHasKey( 'og:image:width', $tags );
		$this->assertArrayNotHasKey( 'twitter:image:alt', $tags );
		$this->assertSame( 0, $this->lookups );
	}
}
}
