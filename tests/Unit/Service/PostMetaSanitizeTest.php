<?php
/**
 * Tests for Post_Meta sanitization: custom schema JSON and snippet controls.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Tests\TestCase;

/**
 * Post meta sanitizer coverage.
 */
class PostMetaSanitizeTest extends TestCase {

	/**
	 * Custom schema keeps "&" in URLs and escaped quotes intact.
	 */
	public function test_custom_schema_keeps_ampersands_and_escapes(): void {
		$json = '{"@type":"WebPage","url":"https://example.org/?a=1&b=2","name":"He said \"hi\" <b>"}';

		$clean = ( new Post_Meta() )->sanitize( array( 'custom_schema' => $json ) );

		$this->assertStringContainsString( 'https://example.org/?a=1&b=2', $clean['custom_schema'] );
		$this->assertStringNotContainsString( '&amp;', $clean['custom_schema'] );
		$this->assertSame( json_decode( $json, true ), json_decode( $clean['custom_schema'], true ) );
	}

	/**
	 * Sanitizing twice (save_meta + registered callback) is idempotent.
	 */
	public function test_custom_schema_sanitize_is_idempotent(): void {
		$meta  = new Post_Meta();
		$once  = $meta->sanitize( array( 'custom_schema' => '[{"@type":"Thing","name":"A & B"}]' ) );
		$twice = $meta->sanitize( $once );

		$this->assertSame( $once['custom_schema'], $twice['custom_schema'] );
	}

	/**
	 * Invalid or scalar JSON is rejected.
	 */
	public function test_invalid_custom_schema_is_rejected(): void {
		$meta = new Post_Meta();

		$this->assertSame( '', $meta->sanitize( array( 'custom_schema' => '{"@type": "Thing",' ) )['custom_schema'] );
		$this->assertSame( '', $meta->sanitize( array( 'custom_schema' => '"just a string"' ) )['custom_schema'] );
		$this->assertSame( '', $meta->sanitize( array( 'custom_schema' => '   ' ) )['custom_schema'] );
	}

	/**
	 * Snippet controls are normalized to their stored formats.
	 */
	public function test_snippet_controls_are_normalized(): void {
		$clean = ( new Post_Meta() )->sanitize(
			array(
				'nosnippet'         => true,
				'max_snippet'       => '-5',
				'max_image_preview' => 'LARGE',
				'noimageindex'      => 'false',
			)
		);

		$this->assertSame( '1', $clean['nosnippet'] );
		$this->assertSame( '-1', $clean['max_snippet'] );
		$this->assertSame( 'large', $clean['max_image_preview'] );
		$this->assertSame( '', $clean['noimageindex'] );
	}

	/**
	 * Empty / invalid snippet values mean "inherit".
	 */
	public function test_snippet_controls_default_to_inherit(): void {
		$clean = ( new Post_Meta() )->sanitize(
			array(
				'max_snippet'       => 'abc',
				'max_image_preview' => 'huge',
			)
		);

		$this->assertSame( '', $clean['nosnippet'] );
		$this->assertSame( '', $clean['max_snippet'] );
		$this->assertSame( '', $clean['max_image_preview'] );
		$this->assertSame( '', $clean['noimageindex'] );
		$this->assertSame( '160', Post_Meta::sanitize_max_snippet( 160 ) );
		$this->assertSame( '0', Post_Meta::sanitize_max_snippet( '0' ) );
	}
}
