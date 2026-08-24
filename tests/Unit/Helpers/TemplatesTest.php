<?php
/**
 * Tests for shared helper functions (template rendering, snippets, analysis).
 *
 * @package Saman\SEO\Tests\Unit\Helpers
 */

namespace Saman\SEO\Tests\Unit\Helpers;

use Saman\SEO\Tests\TestCase;
use function Saman\SEO\Helpers\calculate_keyphrase_density;
use function Saman\SEO\Helpers\contains_keyphrase;
use function Saman\SEO\Helpers\count_external_links;
use function Saman\SEO\Helpers\count_headings;
use function Saman\SEO\Helpers\extract_first_paragraph;
use function Saman\SEO\Helpers\extract_h1_text;
use function Saman\SEO\Helpers\generate_content_snippet;
use function Saman\SEO\Helpers\generate_title_from_template;
use function Saman\SEO\Helpers\has_unreplaced_variables;
use function Saman\SEO\Helpers\render_template_safely;
use function Saman\SEO\Helpers\replace_template_variables;
use function Saman\SEO\Helpers\strip_content_to_text;
use function Saman\SEO\Helpers\strip_unreplaced_variables;
use function Saman\SEO\Helpers\tidy_rendered_template;

/**
 * Helper function coverage.
 */
class TemplatesTest extends TestCase {

	/**
	 * tidy_rendered_template collapses duplicate separators and trims edges.
	 */
	public function test_tidy_collapses_duplicate_separators(): void {
		$this->assertSame( 'A | B', tidy_rendered_template( 'A  |  |  B', '|' ) );
	}

	/**
	 * Leading/trailing separators are stripped.
	 */
	public function test_tidy_trims_leading_and_trailing_separators(): void {
		$this->assertSame( 'My Site', tidy_rendered_template( ' | My Site | ', '|' ) );
	}

	/**
	 * Legitimate punctuation inside content is preserved.
	 */
	public function test_tidy_preserves_inner_punctuation(): void {
		$this->assertSame( 'Wait - what? Yes!', tidy_rendered_template( 'Wait - what? Yes!' ) );
	}

	/**
	 * has_unreplaced_variables detects both token syntaxes.
	 */
	public function test_has_unreplaced_variables_detects_tokens(): void {
		$this->assertTrue( has_unreplaced_variables( 'Hello {{name}}' ) );
		$this->assertTrue( has_unreplaced_variables( '%post_title% review' ) );
		$this->assertFalse( has_unreplaced_variables( 'Clean title' ) );
		// Natural "50% off" is not a token.
		$this->assertFalse( has_unreplaced_variables( 'Save 50% off today' ) );
	}

	/**
	 * strip_unreplaced_variables removes tokens and tidies leftovers.
	 */
	public function test_strip_unreplaced_variables(): void {
		$this->assertSame( '', strip_unreplaced_variables( '{{missing}}' ) );
		$this->assertSame( 'My Site', strip_unreplaced_variables( '{{missing}} My Site %gone%' ) );
	}

	/**
	 * replace_template_variables resolves global and post variables.
	 */
	public function test_replace_template_variables_resolves_post_context(): void {
		$post = $this->make_post( array( 'post_title' => 'Cold Brew Guide' ) );

		$rendered = replace_template_variables( '{{post_title}} | {{site_title}}', $post );

		$this->assertSame( 'Cold Brew Guide | Test Site', $rendered );
	}

	/**
	 * Unknown tokens resolve to empty strings (never leak).
	 */
	public function test_replace_template_variables_unknown_token_becomes_empty(): void {
		$this->assertSame( 'Hello', replace_template_variables( 'Hello {{unknown_var}}' ) );
	}

	/**
	 * render_template_safely falls back when rendering produces nothing.
	 */
	public function test_render_template_safely_fallback_on_empty(): void {
		$this->assertSame(
			'Fallback',
			render_template_safely( '{{does_not_exist}} {{either}}', null, 'Fallback' )
		);
	}

	/**
	 * generate_title_from_template honours per-post-type templates.
	 */
	public function test_generate_title_from_template_uses_post_type_template(): void {
		$post = $this->make_post();

		self::$options['SAMAN_SEO_default_title_templates']            = array();
		self::$options['SAMAN_SEO_post_type_title_templates']          = array();
		self::$options['SAMAN_SEO_default_title_template']             = '';
		self::$options['SAMAN_SEO_post_type_title_templates']['post'] = '%title% - %sitename%';

		$this->assertSame( 'Sample Post - Test Site', generate_title_from_template( $post ) );
	}

	/**
	 * generate_title_from_template falls back to the post title when the
	 * configured template renders to nothing.
	 */
	public function test_generate_title_from_template_falls_back_to_post_title(): void {
		$post = $this->make_post();

		self::$options['SAMAN_SEO_post_type_title_templates'] = array();
		self::$options['SAMAN_SEO_default_title_template']    = '{{unknown_token_only}}';

		$this->assertSame( 'Sample Post', generate_title_from_template( $post ) );
	}

	/**
	 * strip_content_to_text keeps word boundaries between block elements.
	 */
	public function test_strip_content_to_text_keeps_word_boundaries(): void {
		$html   = '<p>Venezuelan</p><p>culinary</p>';
		$result = strip_content_to_text( $html );

		$this->assertStringContainsString( 'Venezuelan culinary', $result );
		$this->assertStringNotContainsString( 'Venezuelanculinary', $result );
	}

	/**
	 * Script and style contents are dropped entirely.
	 */
	public function test_strip_content_to_text_drops_script_style(): void {
		$html = '<p>Visible</p><script>var secret = 1;</script><style>.hidden{}</style>';

		$this->assertSame( 'Visible', strip_content_to_text( $html ) );
	}

	/**
	 * Comments are removed before text extraction.
	 */
	public function test_strip_content_to_text_removes_comments(): void {
		$this->assertSame( 'Text', strip_content_to_text( '<!-- a comment --><p>Text</p>' ) );
	}

	/**
	 * generate_content_snippet trims to the requested word count.
	 */
	public function test_generate_content_snippet_trims_words(): void {
		$post = $this->make_post(
			array(
				'post_content' => '<p>' . implode( ' ', array_fill( 0, 50, 'word' ) ) . '</p>',
			)
		);

		$snippet = generate_content_snippet( $post, 30 );

		$this->assertSame( 30, substr_count( str_replace( '&hellip;', '', $snippet ), 'word' ) + 0 );
		$this->assertStringEndsWith( '&hellip;', $snippet );
	}

	/**
	 * generate_content_snippet falls back to the excerpt for empty content.
	 */
	public function test_generate_content_snippet_falls_back_to_excerpt(): void {
		$post = $this->make_post(
			array(
				'post_content' => '',
				'post_excerpt' => 'Excerpt text only.',
			)
		);

		$this->assertSame( 'Excerpt text only.', generate_content_snippet( $post ) );
	}

	/**
	 * contains_keyphrase is case-insensitive.
	 */
	public function test_contains_keyphrase_case_insensitive(): void {
		$this->assertTrue( contains_keyphrase( 'Best Cold Brew Coffee', 'cold brew' ) );
		$this->assertFalse( contains_keyphrase( 'Hot coffee only', 'cold brew' ) );
		$this->assertFalse( contains_keyphrase( 'Anything', '' ) );
	}

	/**
	 * calculate_keyphrase_density computes percentage of keyphrase words.
	 */
	public function test_calculate_keyphrase_density(): void {
		// "cold brew" appears once in 10 words; keyphrase is 2 words.
		$density = calculate_keyphrase_density( 'cold brew guide with nine more words here', 'cold brew', 10 );

		$this->assertEqualsWithDelta( 20.0, $density, 0.01 );
		$this->assertSame( 0.0, calculate_keyphrase_density( 'text', 'kw', 0 ) );
	}

	/**
	 * count_headings counts headings at the requested level.
	 */
	public function test_count_headings_by_level(): void {
		$html = '<h2>One</h2><h2>Two</h2><h3>Sub</h3><h4>Deep</h4>';

		$this->assertSame( 2, count_headings( $html, 2 ) );
		$this->assertSame( 1, count_headings( $html, 3 ) );
		$this->assertSame( 0, count_headings( $html, 5 ) );
	}

	/**
	 * extract_h1_text returns the first H1 contents.
	 */
	public function test_extract_h1_text(): void {
		$this->assertSame( 'Main Title', extract_h1_text( '<div><h1 class="x">Main Title</h1></div>' ) );
		$this->assertSame( '', extract_h1_text( '<p>No heading</p>' ) );
	}

	/**
	 * extract_first_paragraph returns the first paragraph's plain text.
	 */
	public function test_extract_first_paragraph(): void {
		$html = '<p>First para.</p><p>Second para.</p>';

		$this->assertSame( 'First para.', extract_first_paragraph( $html ) );
	}

	/**
	 * count_external_links counts links to other hosts only.
	 */
	public function test_count_external_links(): void {
		$html = '<a href="https://example.org/internal/">In</a>'
			. '<a href="https://external.com/page">Out</a>'
			. '<a href="/relative/path">Rel</a>'
			. '<a href="mailto:a@b.com">Mail</a>'
			. '<a href="#anchor">Anchor</a>'
			. '<a href="https://www.example.org/www">WWW In</a>';

		$this->assertSame( 1, count_external_links( $html ) );
	}
}
