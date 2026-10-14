<?php
/**
 * Tests for the llms.txt path, legacy redirect and rewrite flushing.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Service\LLM_TXT_Generator;
use Saman\SEO\Tests\TestCase;

/**
 * llms.txt routing coverage.
 */
class LlmsTxtGeneratorTest extends TestCase {

	/**
	 * Rewrite rules registered during a test.
	 *
	 * @var array<string,string>
	 */
	private $rules = array();

	/**
	 * Number of flush_rewrite_rules() calls.
	 *
	 * @var int
	 */
	private $flushes = 0;

	/**
	 * Install rewrite stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		$this->rules   = array();
		$this->flushes = 0;

		Functions\when( 'add_rewrite_rule' )->alias(
			function ( $regex, $query ) {
				$this->rules[ $regex ] = $query;
			}
		);
		Functions\when( 'add_rewrite_tag' )->justReturn( null );
		Functions\when( 'flush_rewrite_rules' )->alias(
			function () {
				++$this->flushes;
			}
		);
	}

	/**
	 * /llms.txt is served, /llm.txt is routed to the redirect branch.
	 */
	public function test_rewrite_rules_serve_llms_and_route_legacy(): void {
		( new LLM_TXT_Generator() )->register_rewrite_rules();

		$this->assertSame( 'index.php?SAMAN_SEO_llm_txt=1', $this->rules['^llms\.txt$'] );
		$this->assertSame( 'index.php?SAMAN_SEO_llm_txt=legacy', $this->rules['^llm\.txt$'] );

		// The patterns match only their own path.
		$this->assertSame( 1, preg_match( '#^llms\.txt$#', 'llms.txt' ) );
		$this->assertSame( 0, preg_match( '#^llm\.txt$#', 'llms.txt' ) );
	}

	/**
	 * Query values map to serve / redirect / ignore.
	 */
	public function test_resolve_request(): void {
		$this->assertSame( 'serve', LLM_TXT_Generator::resolve_request( '1' ) );
		$this->assertSame( 'serve', LLM_TXT_Generator::resolve_request( 1 ) );
		$this->assertSame( 'redirect', LLM_TXT_Generator::resolve_request( 'legacy' ) );
		$this->assertSame( '', LLM_TXT_Generator::resolve_request( 0 ) );
		$this->assertSame( '', LLM_TXT_Generator::resolve_request( '' ) );
		$this->assertSame( '', LLM_TXT_Generator::resolve_request( 'other' ) );
	}

	/**
	 * The canonical URL is /llms.txt.
	 */
	public function test_url_uses_llms_path(): void {
		$this->assertSame( 'https://example.org/llms.txt', LLM_TXT_Generator::get_url() );
	}

	/**
	 * Rules are flushed once per rewrite version, not on every request.
	 */
	public function test_rewrite_rules_flushed_once(): void {
		$service = new LLM_TXT_Generator();

		$this->assertTrue( $service->maybe_flush_rewrite_rules() );
		$this->assertSame( 1, $this->flushes );
		$this->assertSame( LLM_TXT_Generator::REWRITE_VERSION, self::$options[ LLM_TXT_Generator::REWRITE_VERSION_OPTION ] );

		$this->assertFalse( $service->maybe_flush_rewrite_rules() );
		$this->assertFalse( $service->maybe_flush_rewrite_rules() );
		$this->assertSame( 1, $this->flushes );
	}

	/**
	 * An older stored version triggers a fresh flush.
	 */
	public function test_outdated_version_triggers_flush(): void {
		self::$options[ LLM_TXT_Generator::REWRITE_VERSION_OPTION ] = '1';

		$this->assertTrue( ( new LLM_TXT_Generator() )->maybe_flush_rewrite_rules() );
		$this->assertSame( 1, $this->flushes );
	}

	/**
	 * Non-matching requests fall through without output or redirect.
	 */
	public function test_render_ignores_other_requests(): void {
		Functions\expect( 'wp_safe_redirect' )->never();

		$output = $this->capture_output(
			static function () {
				( new LLM_TXT_Generator() )->render_llm_txt();
			}
		);

		$this->assertSame( '', $output );
	}
}
