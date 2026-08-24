<?php
/**
 * Tests for Frontend document-title generation.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Title pipeline coverage.
 */
class FrontendTitleTest extends TestCase {

	/**
	 * Per-post meta title wins over templates.
	 */
	public function test_meta_title_takes_priority(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'title' => 'Custom Meta Title' );

		$frontend = new Frontend();

		$this->assertSame( 'Custom Meta Title', $frontend->filter_document_title( 'Original' ) );
	}

	/**
	 * Without a meta title, the default template renders post + site name.
	 */
	public function test_template_title_used_as_fallback(): void {
		$this->make_post();
		self::$options['SAMAN_SEO_post_type_title_templates'] = array();
		unset( self::$options['SAMAN_SEO_default_title_template'] );

		$frontend = new Frontend();
		$title    = $frontend->filter_document_title( 'Original' );

		$this->assertSame( 'Sample Post | Test Site', $title );
	}

	/**
	 * The homepage override option is used on the home view.
	 */
	public function test_homepage_override_on_home_view(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'home', true );
		self::$options['SAMAN_SEO_homepage_title'] = 'Homepage SEO Title';

		$frontend = new Frontend();

		$this->assertSame( 'Homepage SEO Title', $frontend->filter_document_title( 'Original' ) );
	}

	/**
	 * Homepage without an override gets the "Home | Site" default.
	 */
	public function test_homepage_default_when_no_override(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'home', true );
		self::$options['SAMAN_SEO_homepage_title'] = '';

		$frontend = new Frontend();

		$this->assertSame( 'Home | Test Site', $frontend->filter_document_title( 'Original' ) );
	}

	/**
	 * Search results use the search default template.
	 */
	public function test_search_results_title(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'search', true );
		self::$options['SAMAN_SEO_title_separator'] = '|';
		self::$search_query                         = 'cold brew';

		$frontend = new Frontend();

		$this->assertSame( 'Search Results: cold brew | Test Site', $frontend->filter_document_title( '' ) );
	}

	/**
	 * 404 pages get the not-found default.
	 */
	public function test_404_title(): void {
		$this->set_context( 'singular', false );
		$this->set_context( 'is_404', true );
		self::$options['SAMAN_SEO_title_separator'] = '|';

		$frontend = new Frontend();

		$this->assertSame( 'Page Not Found | Test Site', $frontend->filter_document_title( '' ) );
	}

	/**
	 * Non-page contexts are passed through untouched.
	 */
	public function test_non_page_context_passthrough(): void {
		foreach ( array( 'singular', 'home', 'archive', 'search', 'is_404' ) as $flag ) {
			$this->set_context( $flag, false );
		}

		$frontend = new Frontend();

		$this->assertSame( 'Untouched', $frontend->filter_document_title( 'Untouched' ) );
	}

	/**
	 * Overly long titles truncate at the filtered max length.
	 */
	public function test_long_titles_truncate_at_max_length(): void {
		$post = $this->make_post(
			array( 'post_title' => str_repeat( 'word ', 30 ) )
		);
		self::$options['SAMAN_SEO_post_type_title_templates'] = array();
		unset( self::$options['SAMAN_SEO_default_title_template'] );

		$frontend = new Frontend();
		$title    = $frontend->filter_document_title( '' );

		$this->assertLessThanOrEqual( 63, strlen( $title ) ); // 60 chars + "...".
		$this->assertStringEndsWith( '...', $title );
	}

	/**
	 * The saman_seo_title filter can override the final value.
	 */
	public function test_saman_seo_title_filter_applies(): void {
		$this->make_post();

		add_filter(
			'saman_seo_title',
			static function () {
				return 'Filtered Title';
			}
		);

		$frontend = new Frontend();

		$this->assertSame( 'Filtered Title', $frontend->filter_document_title( 'Original' ) );
	}

	/**
	 * Unreplaced variables never leak into the final title.
	 */
	public function test_unreplaced_variables_stripped(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'title' => 'Title {{nonexistent}} here' );

		$frontend = new Frontend();

		$this->assertSame( 'Title here', $frontend->filter_document_title( '' ) );
	}

	/**
	 * wp_title() receives the full computed SEO title (legacy themes).
	 */
	public function test_wp_title_filter_returns_computed_title(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'title' => 'Legacy Theme Title' );

		$frontend = new Frontend();

		$this->assertSame( 'Legacy Theme Title', $frontend->filter_wp_title( ' | Blog' ) );
	}

	/**
	 * The fallback renderer stays silent when the theme already rendered
	 * a title via wp_get_document_title().
	 */
	public function test_plugin_title_tag_silent_after_document_title_call(): void {
		$this->make_post();

		$frontend = new Frontend();
		$frontend->filter_document_title( 'Original' ); // Marks theme_renders_title.

		$output = $this->capture_output( array( $frontend, 'render_plugin_title_tag' ) );

		$this->assertSame( '', $output );
	}

	/**
	 * The fallback renderer emits exactly one <title> for themes with no
	 * title mechanism.
	 */
	public function test_plugin_title_tag_renders_once_for_themeless_titles(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'title' => 'Fallback Tag Title' );

		$frontend = new Frontend();
		$output   = $this->capture_output( array( $frontend, 'render_plugin_title_tag' ) );

		$this->assertSame( 1, substr_count( $output, '<title>' ) );
		$this->assertStringContainsString( 'Fallback Tag Title', $output );
	}

	/**
	 * Escaped output: HTML in titles cannot inject tags.
	 */
	public function test_plugin_title_tag_escapes_html(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'title' => '<script>alert(1)</script> Safe' );

		$frontend = new Frontend();
		$output   = $this->capture_output( array( $frontend, 'render_plugin_title_tag' ) );

		$this->assertStringNotContainsString( '<script>', $output );
		$this->assertStringContainsString( '&lt;script&gt;', $output );
	}
}
