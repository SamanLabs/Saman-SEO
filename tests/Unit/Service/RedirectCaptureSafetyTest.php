<?php
/**
 * Tests for regex capture substitution and redirect host trust.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\Redirect_Manager;
use Saman\SEO\Tests\TestCase;

/**
 * Open-redirect hardening coverage.
 */
class RedirectCaptureSafetyTest extends TestCase {

	/**
	 * Service under test.
	 *
	 * @var Redirect_Manager
	 */
	private Redirect_Manager $service;

	/**
	 * Original $wpdb global.
	 *
	 * @var mixed
	 */
	private $original_wpdb;

	/**
	 * Boot the service.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->service       = new Redirect_Manager();
		$this->original_wpdb = $GLOBALS['wpdb'];

		Functions\when( 'current_time' )->alias(
			static function ( $format ) {
				return date( 'mysql' === $format ? 'Y-m-d H:i:s' : $format );
			}
		);
	}

	/**
	 * Restore the $wpdb global.
	 */
	protected function tearDown(): void {
		$GLOBALS['wpdb'] = $this->original_wpdb;
		parent::tearDown();
	}

	/**
	 * Hostile captures that would make "/$1" leave the site.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function hostile_captures(): array {
		return array(
			'double slash'      => array( '/evil.com' ),
			'many slashes'      => array( '///evil.com/x' ),
			'backslash'         => array( '\\evil.com' ),
			'tab smuggling'     => array( "\t/evil.com" ),
			'newline smuggling' => array( "\n/evil.com" ),
			'absolute url'      => array( 'https://evil.com/' ),
		);
	}

	/**
	 * A relative target can never become protocol-relative or absolute.
	 *
	 * @dataProvider hostile_captures
	 *
	 * @param string $capture Capture value.
	 */
	public function test_relative_target_stays_on_site( string $capture ): void {
		$result = $this->service->apply_regex_captures( '/$1', array( '/old/x', $capture ) );

		$this->assertSame( '/', $result[0] );
		$this->assertNotSame( '/', $result[1] ?? '' );
		$this->assertNotSame( '\\', $result[1] ?? '' );
		$this->assertStringStartsNotWith( '//', $result );
		// Resolved against the site, the result must stay on the site's host.
		$this->assertSame( 'example.org', parse_url( 'https://example.org' . $result, PHP_URL_HOST ) );
	}

	/**
	 * Captures substitute in order and $10 is not mangled by $1.
	 */
	public function test_captures_substitute_by_index(): void {
		$matches = array( 'full', 'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h', 'i', 'j' );

		$this->assertSame( '/j/a/b', $this->service->apply_regex_captures( '/$10/$1/$2', $matches ) );
		$this->assertSame( '/new/', $this->service->apply_regex_captures( '/new/$9', array( 'full' ) ) );
	}

	/**
	 * An admin-configured absolute target keeps working.
	 */
	public function test_absolute_target_keeps_configured_host(): void {
		$result = $this->service->apply_regex_captures( 'https://other.example/$1', array( 'full', 'page' ) );

		$this->assertSame( 'https://other.example/page', $result );
	}

	/**
	 * Only a literal configured host is trusted, never a capture-built one.
	 */
	public function test_configured_redirect_host(): void {
		$this->assertSame( 'other.example', $this->service->configured_redirect_host( 'https://Other.example/$1' ) );
		$this->assertSame( '', $this->service->configured_redirect_host( 'https://$1.example.com/' ) );
		$this->assertSame( '', $this->service->configured_redirect_host( 'https://example.org$1' ) );
		$this->assertSame( '', $this->service->configured_redirect_host( '/relative/$1' ) );
		$this->assertSame( '', $this->service->configured_redirect_host( '//evil.com/' ) );
		$this->assertSame( '', $this->service->configured_redirect_host( 'https://user@evil.com/' ) );
	}

	/**
	 * The configured host is allowed as typed and lowercased, because
	 * wp_validate_redirect() compares hosts case-sensitively.
	 */
	public function test_configured_redirect_hosts_keep_original_case(): void {
		$this->assertSame(
			array( 'shop.example.com', 'Shop.Example.com' ),
			$this->service->configured_redirect_hosts( 'https://Shop.Example.com/x' )
		);
		$this->assertSame( array( 'shop.example.com' ), $this->service->configured_redirect_hosts( 'https://shop.example.com/x' ) );
		$this->assertSame( array(), $this->service->configured_redirect_hosts( 'https://$1.Example.com/' ) );
		$this->assertSame( array(), $this->service->configured_redirect_hosts( '/relative/' ) );
	}

	/**
	 * A broken admin regex is skipped instead of breaking the request.
	 */
	public function test_invalid_regex_is_skipped(): void {
		$rows = array(
			(object) array(
				'id'          => 1,
				'source'      => '^/broken/(',
				'target'      => '/never/',
				'status_code' => 301,
				'hits'        => 0,
				'is_regex'    => 1,
				'start_date'  => null,
				'end_date'    => null,
			),
			(object) array(
				'id'          => 2,
				'source'      => '^/old/(.*)$',
				'target'      => '/$1',
				'status_code' => 301,
				'hits'        => 0,
				'is_regex'    => 1,
				'start_date'  => null,
				'end_date'    => null,
			),
		);

		$GLOBALS['wpdb'] = new class( $rows ) {
			/**
			 * Rows to return.
			 *
			 * @var array
			 */
			private $rows;

			/**
			 * Table prefix.
			 *
			 * @var string
			 */
			public $prefix = 'wp_';

			/**
			 * Constructor.
			 *
			 * @param array $rows Rows.
			 */
			public function __construct( $rows ) {
				$this->rows = $rows;
			}

			/**
			 * Return the seeded rows.
			 *
			 * @return array
			 */
			public function get_results() {
				return $this->rows;
			}
		};

		$method = new \ReflectionMethod( Redirect_Manager::class, 'find_regex_redirect' );
		$method->setAccessible( true );

		$row = $method->invoke(
			$this->service,
			'/old//evil.com',
			'',
			array(
				'query_matching'   => 'ignore',
				'case_insensitive' => false,
			)
		);

		$this->assertIsObject( $row );
		$this->assertSame( 2, $row->id );
		$this->assertSame( '/evil.com', $row->target );
		$this->assertSame( '/$1', $row->configured_target );
	}
}
