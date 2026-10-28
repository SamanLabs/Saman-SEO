<?php
/**
 * Tests for which titles the public social card endpoint will draw.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Social_Card_Generator;
use Saman\SEO\Tests\TestCase;

/**
 * Social_Card_Generator signature coverage.
 */
class SocialCardGeneratorTest extends TestCase {

	/**
	 * Install stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'wp_unslash' )->returnArg();
		Functions\when( 'add_query_arg' )->alias(
			static function ( $args, $url ) {
				return $url . '?' . http_build_query( $args );
			}
		);
	}

	/**
	 * Restore the query string.
	 */
	protected function tearDown(): void {
		unset( $_GET['title'], $_GET['sig'] );
		parent::tearDown();
	}

	/**
	 * The title the endpoint would draw for the current query string.
	 *
	 * @return string
	 */
	private function drawn_title() {
		$method = new \ReflectionMethod( Social_Card_Generator::class, 'requested_title' );
		$method->setAccessible( true );

		return $method->invoke( new Social_Card_Generator() );
	}

	/**
	 * A card URL built by the plugin draws its title.
	 */
	public function test_signed_title_is_drawn() {
		parse_str( (string) wp_parse_url( Social_Card_Generator::card_url( 'Hello World' ), PHP_URL_QUERY ), $query );
		$_GET['title'] = rawurldecode( $query['title'] );
		$_GET['sig']   = $query['sig'];

		$this->assertSame( 'Hello World', $this->drawn_title() );
	}

	/**
	 * Every new made-up title used to render and store another PNG; now it gets the one site card.
	 */
	public function test_made_up_or_unsigned_titles_get_the_site_name() {
		$_GET['title'] = 'random-' . mt_rand();
		$this->assertSame( 'Test Site', $this->drawn_title(), 'no signature' );

		parse_str( (string) wp_parse_url( Social_Card_Generator::card_url( 'Hello World' ), PHP_URL_QUERY ), $query );
		$_GET['title'] = 'Another title';
		$_GET['sig']   = $query['sig'];
		$this->assertSame( 'Test Site', $this->drawn_title(), 'a signature for a different title' );
	}
}
