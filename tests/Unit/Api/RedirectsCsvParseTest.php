<?php
/**
 * Tests for CSV parsing in the redirects import.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Saman\SEO\Api\Redirects_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Redirects_Controller::parse_csv_line() coverage.
 */
class RedirectsCsvParseTest extends TestCase {

	/**
	 * Parse with a deprecation trap so PHP 8.4's str_getcsv() escape
	 * deprecation would fail the test.
	 *
	 * @param string $line CSV line.
	 * @return array
	 */
	private function parse( string $line ): array {
		$deprecations = array();

		set_error_handler(
			static function ( $errno, $message ) use ( &$deprecations ) {
				$deprecations[] = $message;
				return true;
			},
			E_DEPRECATED | E_USER_DEPRECATED
		);

		try {
			$row = Redirects_Controller::parse_csv_line( $line );
		} finally {
			restore_error_handler();
		}

		$this->assertSame( array(), $deprecations );

		return $row;
	}

	/**
	 * Plain and quoted cells.
	 */
	public function test_parses_basic_row(): void {
		$this->assertSame(
			array( '/old', '/new', '301', '0', 'Group, with comma' ),
			$this->parse( '"/old","/new",301,0,"Group, with comma"' )
		);
	}

	/**
	 * Doubled quotes (as written by the exporter) round-trip.
	 */
	public function test_doubled_quotes_unescape(): void {
		$this->assertSame(
			array( 'say "hi"', 'x' ),
			$this->parse( '"say ""hi""","x"' )
		);
	}

	/**
	 * A cell ending in a backslash keeps its closing quote (regex sources).
	 */
	public function test_trailing_backslash_does_not_escape_quote(): void {
		$this->assertSame(
			array( '^/path\\', '/target' ),
			$this->parse( '"^/path\\","/target"' )
		);
	}

	/**
	 * Regex sources with backslashes survive intact.
	 */
	public function test_regex_backslashes_survive(): void {
		$this->assertSame(
			array( '^/blog/(\\d+)$', '/posts/$1' ),
			$this->parse( '"^/blog/(\\d+)$","/posts/$1"' )
		);
	}
}
