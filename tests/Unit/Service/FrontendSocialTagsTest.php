<?php
/**
 * Tests for Frontend Open Graph / Twitter output.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Social tag pipeline coverage.
 */
class FrontendSocialTagsTest extends TestCase {

	/**
	 * Parse rendered meta tags into property => content pairs.
	 *
	 * @param string $html Rendered head output.
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
	 * A blog post renders the full OG/Twitter tag set with og:type article.
	 */
	public function test_post_renders_article_social_tags(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'og_image' => 'https://cdn.example.org/cover.jpg' );

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_social_tags' ) );
		$tags     = $this->parse_tags( $html );

		$this->assertSame( 'Sample Post', $tags['og:title'] );
		$this->assertSame( 'article', $tags['og:type'] );
		$this->assertSame( 'Test Site', $tags['og:site_name'] );
		$this->assertSame( 'https://example.org/sample-post/', $tags['og:url'] );
		$this->assertSame( 'https://cdn.example.org/cover.jpg', $tags['og:image'] );
		$this->assertSame( 'summary_large_image', $tags['twitter:card'] );
	}

	/**
	 * Pages use og:type website.
	 */
	public function test_page_uses_website_type(): void {
		$this->make_post( array( 'post_type' => 'page', 'post_name' => 'about' ) );

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertSame( 'website', $tags['og:type'] );
	}

	/**
	 * Meta description feeds og:description before content snippets.
	 */
	public function test_meta_description_used_for_og_description(): void {
		$post = $this->make_post(
			array(
				'post_content' => '<p>' . implode( ' ', array_fill( 0, 60, 'filler' ) ) . '</p>',
			)
		);
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'description' => 'Hand written description.' );

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertSame( 'Hand written description.', $tags['og:description'] );
	}

	/**
	 * Image priority: featured image wins when no meta override exists.
	 */
	public function test_featured_image_fallback(): void {
		$post = $this->make_post();

		Functions\when( 'get_post_thumbnail_id' )->justReturn( 555 );
		Functions\when( 'wp_get_attachment_image_url' )->alias(
			static function () {
				return 'https://example.org/wp-content/uploads/featured.jpg';
			}
		);

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertSame( 'https://example.org/wp-content/uploads/featured.jpg', $tags['og:image'] );
	}

	/**
	 * Site-wide default OG image is used as a fallback.
	 */
	public function test_default_og_image_fallback(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_default_og_image'] = 'https://cdn.example.org/default.jpg';

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertSame( 'https://cdn.example.org/default.jpg', $tags['og:image'] );
	}

	/**
	 * With no image sources at all the dynamic card generator is the last resort.
	 */
	public function test_dynamic_social_card_last_resort(): void {
		$post = $this->make_post();

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertStringContainsString( 'SAMAN_SEO_social_card=1', $tags['og:image'] );
		$this->assertStringContainsString( 'title=', $tags['og:image'] );
		unset( $post );
	}

	/**
	 * Duplicate single-value tags collapse to the last occurrence while
	 * multi-value tags are preserved.
	 */
	public function test_dedupe_and_multivalue_tags(): void {
		$post    = $this->make_post();
		$frontend = new Frontend();

		add_filter(
			'saman_seo_social_tags',
			static function ( array $tags ) {
				// Simulate two plugins fighting over twitter:card plus extra images.
				$tags['twitter:card'] = 'summary_large_image';
				$tags[]               = array(
					'property' => 'og:image',
					'name'     => '',
					'content'  => 'https://cdn.example.org/a.jpg',
				);
				$tags[]               = array(
					'property' => 'og:image',
					'name'     => '',
					'content'  => 'https://cdn.example.org/b.jpg',
				);

				return $tags;
			}
		);

		$html = $this->capture_output( array( $frontend, 'render_social_tags' ) );

		$this->assertSame( 1, substr_count( $html, '"twitter:card"' ) );
		$this->assertStringContainsString( 'a.jpg', $html );
		$this->assertStringContainsString( 'b.jpg', $html );
		unset( $post );
	}

	/**
	 * Empty values are never emitted.
	 */
	public function test_empty_values_skipped(): void {
		$this->make_post();

		add_filter(
			'saman_seo_og_title',
			static function () {
				return '';
			}
		);

		$frontend = new Frontend();
		$html     = $this->capture_output( array( $frontend, 'render_social_tags' ) );

		$this->assertStringNotContainsString( '"og:title"', $html );
	}

	/**
	 * Archive views render website type and archive-derived titles.
	 */
	public function test_archive_view_social_tags(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'archive', true );
		$this->set_context( 'category', true );
		self::$queried_term                = new \WP_Term(
			array(
				'term_id'     => 7,
				'name'        => 'Coffee',
				'taxonomy'    => 'category',
				'description' => 'All things coffee.',
			)
		);
		self::$options['SAMAN_SEO_title_separator'] = '|';

		$frontend = new Frontend();
		$tags     = $this->parse_tags( $this->capture_output( array( $frontend, 'render_social_tags' ) ) );

		$this->assertSame( 'website', $tags['og:type'] );
		$this->assertSame( 'Coffee | Test Site', $tags['og:title'] );
	}
}
