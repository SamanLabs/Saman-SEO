<?php
/**
 * Tests for internal linking content splitting and accent matching.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Internal_Linking\Engine;
use Saman\SEO\Tests\TestCase;

/**
 * Long documents are cut only where no element is open, and accent-stripped
 * matches map back to the right characters.
 */
class InternalLinkingSplitTest extends TestCase {

	/**
	 * Pieces always rejoin to the original content.
	 */
	public function test_pieces_rejoin_to_the_original(): void {
		$html = str_repeat( '<p>Paragraph text here.</p>', 20 );

		$pieces = Engine::split_at_top_level( $html, 100 );

		$this->assertGreaterThan( 1, count( $pieces ) );
		$this->assertSame( $html, implode( '', $pieces ) );
	}

	/**
	 * A wrapper that spans the cut point is never split.
	 */
	public function test_wrapper_spanning_the_cut_stays_whole(): void {
		$wrapper = '<div class="columns">' . str_repeat( '<p>Inside the wrapper.</p>', 20 ) . '</div>';
		$html    = '<p>Before.</p>' . $wrapper . '<p>After.</p>';

		$pieces = Engine::split_at_top_level( $html, 100 );

		$this->assertSame( $html, implode( '', $pieces ) );

		foreach ( $pieces as $piece ) {
			$this->assertSame(
				substr_count( $piece, '<div' ),
				substr_count( $piece, '</div>' ),
				'Every piece must be balanced.'
			);
		}
	}

	/**
	 * Markup-like text inside script and pre does not confuse the splitter.
	 */
	public function test_raw_text_elements_are_single_tokens(): void {
		$html = '<p>One.</p><script>if ( a < b ) { document.write( "<div>" ); }</script>'
			. str_repeat( '<p>More text.</p>', 20 )
			. '<pre><p>not markup</pre><p>End.</p>';

		$pieces = Engine::split_at_top_level( $html, 100 );

		$this->assertSame( $html, implode( '', $pieces ) );

		foreach ( $pieces as $piece ) {
			$this->assertSame( substr_count( $piece, '<script' ), substr_count( $piece, '</script>' ) );
			$this->assertSame( substr_count( $piece, '<pre' ), substr_count( $piece, '</pre>' ) );
		}
	}

	/**
	 * A match found after an expanding character lands on the right text.
	 */
	public function test_accent_match_maps_back_to_original_positions(): void {
		Functions\when( 'remove_accents' )->alias(
			static function ( $value ) {
				return strtr( $value, array( 'ß' => 'ss', 'é' => 'e' ) );
			}
		);

		$text    = 'Die Straße: rote Schuhe kaufen';
		$subject = remove_accents( $text );
		$map     = Engine::accent_position_map( $text, $subject );

		$this->assertIsArray( $map );

		$pos  = mb_strpos( $subject, 'rote Schuhe' );
		$span = Engine::original_span( $map, $pos, mb_strlen( 'rote Schuhe' ), mb_strlen( $text ) );

		$this->assertSame( 'rote Schuhe', mb_substr( $text, $span[0], $span[1] ) );

		// A match that covers the expanded character takes the whole original.
		$pos  = mb_strpos( $subject, 'Strasse' );
		$span = Engine::original_span( $map, $pos, mb_strlen( 'Strasse' ), mb_strlen( $text ) );

		$this->assertSame( 'Straße', mb_substr( $text, $span[0], $span[1] ) );

		// A match ending in the middle of "ss" would split the "ß": rejected.
		$pos = mb_strpos( $subject, 'Stras' );

		$this->assertNull( Engine::original_span( $map, $pos, mb_strlen( 'Stras' ), mb_strlen( $text ) ) );
	}

	/**
	 * Same-length replacements need no map at all.
	 */
	public function test_no_map_when_lengths_match(): void {
		$this->assertNull( Engine::accent_position_map( 'café', 'cafe' ) );
		$this->assertNull( Engine::accent_position_map( 'plain', 'plain' ) );
	}
}
