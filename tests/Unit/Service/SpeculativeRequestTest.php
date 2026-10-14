<?php
/**
 * Tests for browser prefetch (speculative loading) detection.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Page_Cache;
use Saman\SEO\Service\Request_Monitor;
use Saman\SEO\Tests\TestCase;

/**
 * Request_Monitor::is_speculative_request() and the page cache drop-in copy.
 */
class SpeculativeRequestTest extends TestCase {

	/**
	 * Header sets that must be recognised as prefetches.
	 *
	 * @return array<string,array{0:array<string,string>}>
	 */
	public function prefetch_headers() {
		return array(
			'sec-purpose prefetch'           => array( array( 'HTTP_SEC_PURPOSE' => 'prefetch' ) ),
			'sec-purpose prefetch;prerender' => array( array( 'HTTP_SEC_PURPOSE' => 'prefetch;prerender' ) ),
			'sec-purpose with params'        => array( array( 'HTTP_SEC_PURPOSE' => 'prefetch; anonymous-client-ip' ) ),
			'sec-purpose upper case'         => array( array( 'HTTP_SEC_PURPOSE' => 'Prefetch' ) ),
			'legacy purpose'                 => array( array( 'HTTP_PURPOSE' => 'prefetch' ) ),
			'x-purpose'                      => array( array( 'HTTP_X_PURPOSE' => 'prefetch' ) ),
			'firefox x-moz'                  => array( array( 'HTTP_X_MOZ' => 'prefetch' ) ),
		);
	}

	/**
	 * Header sets that are ordinary navigations.
	 *
	 * @return array<string,array{0:array<string,mixed>}>
	 */
	public function normal_headers() {
		return array(
			'no headers'            => array( array() ),
			'unrelated headers'     => array( array( 'HTTP_USER_AGENT' => 'Mozilla/5.0 prefetch' ) ),
			'empty sec-purpose'     => array( array( 'HTTP_SEC_PURPOSE' => '' ) ),
			'other purpose token'   => array( array( 'HTTP_SEC_PURPOSE' => 'preview' ) ),
			'token as substring'    => array( array( 'HTTP_PURPOSE' => 'noprefetch' ) ),
			'non-string header'     => array( array( 'HTTP_SEC_PURPOSE' => array( 'prefetch' ) ) ),
		);
	}

	/**
	 * Prefetch headers are detected.
	 *
	 * @dataProvider prefetch_headers
	 *
	 * @param array $server Server vars.
	 * @return void
	 */
	public function test_detects_prefetch( array $server ) {
		$this->assertTrue( Request_Monitor::is_speculative_request( $server ) );
	}

	/**
	 * Regular requests are not flagged.
	 *
	 * @dataProvider normal_headers
	 *
	 * @param array $server Server vars.
	 * @return void
	 */
	public function test_ignores_normal_requests( array $server ) {
		$this->assertFalse( Request_Monitor::is_speculative_request( $server ) );
	}

	/**
	 * Without an argument the live $_SERVER is inspected.
	 *
	 * @return void
	 */
	public function test_defaults_to_server_superglobal() {
		$backup = $_SERVER;

		try {
			$_SERVER['HTTP_SEC_PURPOSE'] = 'prefetch;prerender';
			$this->assertTrue( Request_Monitor::is_speculative_request() );

			unset( $_SERVER['HTTP_SEC_PURPOSE'] );
			$this->assertFalse( Request_Monitor::is_speculative_request() );
		} finally {
			$_SERVER = $backup;
		}
	}

	/**
	 * The standalone drop-in carries an equivalent check (it runs before
	 * the plugin loads, so it cannot call the helper).
	 *
	 * @return void
	 */
	public function test_drop_in_skips_hit_log_for_prefetch() {
		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', '/tmp/wp-content' );
		}

		$source = ( new Page_Cache() )->build_drop_in_source();

		$this->assertStringContainsString( 'HTTP_SEC_PURPOSE', $source );
		$this->assertStringContainsString( 'if ( ! $is_prefetch )', $source );
		$this->assertSame( 1, preg_match( "/preg_match\\( '([^']*prefetch[^']*)'/", $source, $m ) );

		$pattern = $m[1];
		foreach ( $this->prefetch_headers() as $case ) {
			$this->assertSame( 1, preg_match( $pattern, (string) reset( $case[0] ) ) );
		}
		$this->assertSame( 0, preg_match( $pattern, 'noprefetch' ) );
		$this->assertSame( 0, preg_match( $pattern, 'preview' ) );
	}
}
