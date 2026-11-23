<?php
/**
 * Tests for search engine site verification meta tags.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;
use function Saman\SEO\Helpers\sanitize_verification_code;

/**
 * Verification tag output and code sanitizing.
 */
class FrontendVerificationTagsTest extends TestCase {

	/**
	 * Every saved code prints its service's meta tag on the home page.
	 */
	public function test_saved_codes_render_on_front_page(): void {
		self::$context['front_page']                        = true;
		self::$options['SAMAN_SEO_google_verification']    = 'abc123_DEF-456';
		self::$options['SAMAN_SEO_bing_verification']      = '0123456789ABCDEF';
		self::$options['SAMAN_SEO_pinterest_verification'] = 'deadbeef';
		self::$options['SAMAN_SEO_yandex_verification']    = 'feedface';
		self::$options['SAMAN_SEO_baidu_verification']     = 'codeva-Xy12';

		$html = $this->capture_output( array( new Frontend(), 'render_verification_tags' ) );

		$this->assertStringContainsString( '<meta name="google-site-verification" content="abc123_DEF-456" />', $html );
		$this->assertStringContainsString( '<meta name="msvalidate.01" content="0123456789ABCDEF" />', $html );
		$this->assertStringContainsString( '<meta name="p:domain_verify" content="deadbeef" />', $html );
		$this->assertStringContainsString( '<meta name="yandex-verification" content="feedface" />', $html );
		$this->assertStringContainsString( '<meta name="baidu-site-verification" content="codeva-Xy12" />', $html );
	}

	/**
	 * Tags stay off every page except the home page.
	 */
	public function test_nothing_renders_off_front_page(): void {
		self::$context['singular']                      = true;
		self::$options['SAMAN_SEO_google_verification'] = 'abc123';

		$html = $this->capture_output( array( new Frontend(), 'render_verification_tags' ) );

		$this->assertSame( '', $html );
	}

	/**
	 * Empty and invalid stored values print nothing.
	 */
	public function test_empty_and_invalid_values_are_skipped(): void {
		self::$context['front_page']                    = true;
		self::$options['SAMAN_SEO_google_verification'] = '';
		self::$options['SAMAN_SEO_bing_verification']   = '"><script>x</script>';

		$html = $this->capture_output( array( new Frontend(), 'render_verification_tags' ) );

		$this->assertSame( '', $html );
	}

	/**
	 * A bare code passes through unchanged.
	 */
	public function test_bare_code_is_kept(): void {
		$this->assertSame( 'b97d32814b303bb4', sanitize_verification_code( '  b97d32814b303bb4 ' ) );
	}

	/**
	 * A pasted meta tag is reduced to its content value.
	 */
	public function test_meta_tag_is_reduced_to_code(): void {
		$this->assertSame(
			'AbC_12-3',
			sanitize_verification_code( '<meta name="google-site-verification" content="AbC_12-3" />' )
		);
		$this->assertSame(
			'0123ABCD',
			sanitize_verification_code( "<meta name='msvalidate.01' content='0123ABCD'>" )
		);
	}

	/**
	 * The HTML file method's name and its file body are rejected.
	 */
	public function test_html_file_name_is_rejected(): void {
		$this->assertSame( '', sanitize_verification_code( 'googleb97d32814b303bb4.html' ) );
		$this->assertSame( '', sanitize_verification_code( 'google-site-verification: googleb97d32814b303bb4.html' ) );
	}

	/**
	 * Markup, whitespace and non-scalar values are rejected.
	 */
	public function test_unsafe_values_are_rejected(): void {
		$this->assertSame( '', sanitize_verification_code( 'abc def' ) );
		$this->assertSame( '', sanitize_verification_code( '"><script>' ) );
		$this->assertSame( '', sanitize_verification_code( array( 'abc' ) ) );
		$this->assertSame( '', sanitize_verification_code( '' ) );
	}
}
