<?php
/**
 * Tests for the FAQ/HowTo block registration and stylesheets.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Schema_Blocks;
use Saman\SEO\Tests\TestCase;

/**
 * Style handles reach the iframed editor; CSS matches the saved markup.
 */
class SchemaBlocksTest extends TestCase {

	/**
	 * Blocks are registered on init with their style handles attached.
	 */
	public function test_blocks_register_style_handles_on_init(): void {
		$styles = array();
		$blocks = array();

		Functions\when( 'wp_register_script' )->justReturn( true );
		Functions\when( 'wp_set_script_translations' )->justReturn( true );
		Functions\when( 'wp_register_style' )->alias(
			static function ( $handle, $src ) use ( &$styles ) {
				$styles[ $handle ] = $src;
				return true;
			}
		);
		Functions\when( 'register_block_type' )->alias(
			static function ( $name, $args = array() ) use ( &$blocks ) {
				$blocks[ $name ] = $args;
				return true;
			}
		);

		$service = new Schema_Blocks();
		$service->boot();

		$this->assertArrayHasKey( 'init', self::$hooks );
		$this->assertArrayNotHasKey( 'enqueue_block_editor_assets', self::$hooks );
		$this->assertArrayNotHasKey( 'wp_enqueue_scripts', self::$hooks );

		$service->register_blocks();

		$this->assertSame( SAMAN_SEO_URL . 'build/css/schema-blocks.css', $styles['saman-seo-schema-blocks'] );
		$this->assertSame( SAMAN_SEO_URL . 'build/css/schema-blocks-editor.css', $styles['saman-seo-schema-blocks-editor'] );

		$this->assertCount( 2, $blocks );
		foreach ( $blocks as $args ) {
			$this->assertSame( 'saman-seo-schema-blocks', $args['style'] );
			$this->assertSame( 'saman-seo-schema-blocks-editor', $args['editor_style'] );
		}
	}

	/**
	 * Provide stylesheet/script pairs.
	 *
	 * @return array
	 */
	public function stylesheet_provider(): array {
		return array(
			'front end' => array( 'schema-blocks.less', 'save' ),
			'editor'    => array( 'schema-blocks-editor.less', 'edit' ),
		);
	}

	/**
	 * Every class the stylesheets target is one the blocks actually output.
	 *
	 * The saved markup lives in post content, so the CSS follows the markup
	 * (`samanseo-*`), never the other way around.
	 *
	 * @dataProvider stylesheet_provider
	 *
	 * @param string $less     LESS file name.
	 * @param string $function Block function whose output it styles.
	 */
	public function test_stylesheet_selectors_match_block_markup( string $less, string $function ): void {
		$css = file_get_contents( SAMAN_SEO_PATH . 'assets/less/' . $less ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents

		$this->assertDoesNotMatchRegularExpression( '/\.saman-seo-(faq|howto)/', $css );

		preg_match_all( '/\.(samanseo-[a-z0-9-]+)/', $css, $matches );
		$selectors = array_unique( $matches[1] );
		$this->assertNotEmpty( $selectors );

		$markup = '';
		foreach ( array( 'faq', 'howto' ) as $block ) {
			$js    = file_get_contents( SAMAN_SEO_PATH . 'blocks/' . $block . '/index.js' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
			$start = strpos( $js, "\t\t" . $function . ': function' );
			$this->assertNotFalse( $start );
			$end     = 'edit' === $function ? strpos( $js, "\t\tsave: function", $start ) : strlen( $js );
			$markup .= substr( $js, $start, $end - $start );
		}

		foreach ( $selectors as $class ) {
			$this->assertStringContainsString( "'" . $class . "'", $markup, $class . ' is not output by the blocks\' ' . $function . '()' );
		}
	}
}
