<?php
/**
 * Tests for schema context resolution, WebPage references and graph cleanup.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Schema_Graph_Manager;
use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Types\WebPage_Schema;
use Saman\SEO\Service\Frontend;
use Saman\SEO\Service\JsonLD;
use Saman\SEO\Tests\TestCase;
use Saman\SEO\Tests\Unit\Schema\Fixtures\Dummy_Schema;

/**
 * Archive context, canonical override, breadcrumb pruning.
 */
class SchemaContextCanonicalTest extends TestCase {

	/**
	 * Reset the registry singleton after each test.
	 */
	protected function tearDown(): void {
		$ref      = new \ReflectionClass( Schema_Registry::class );
		$instance = $ref->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );

		parent::tearDown();
	}

	/**
	 * On an archive the first loop post must not become the context post.
	 */
	public function test_from_current_ignores_loop_post_on_archives(): void {
		$this->make_post();
		$this->set_context( 'singular', false );
		$this->set_context( 'archive', true );
		$this->set_context( 'category', true );

		$context = Schema_Context::from_current();

		$this->assertNull( $context->post );
		$this->assertSame( '', $context->post_type );
		$this->assertSame( 'https://example.org/', $context->canonical );
	}

	/**
	 * On singular views the post is used.
	 */
	public function test_from_current_uses_post_on_singular(): void {
		$post = $this->make_post();

		$context = Schema_Context::from_current();

		$this->assertSame( $post, $context->post );
		$this->assertSame( 'https://example.org/sample-post/', $context->canonical );
	}

	/**
	 * The per-post canonical override becomes the schema URL base.
	 */
	public function test_from_post_honors_canonical_override(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'canonical' => 'https://example.org/original/' );

		$context = Schema_Context::from_post( $post );

		$this->assertSame( 'https://example.org/original/', $context->canonical );

		$webpage = ( new WebPage_Schema( $context ) )->generate();
		$this->assertSame( 'https://example.org/original/#webpage', $webpage['@id'] );
		$this->assertSame( 'https://example.org/original/', $webpage['url'] );
	}

	/**
	 * primaryImageOfPage prefers the per-post og:image override.
	 */
	public function test_primary_image_honors_og_image_override(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta']   = array( 'og_image' => 'https://cdn.example.org/og.jpg' );
		self::$options['SAMAN_SEO_default_og_image']        = 'https://cdn.example.org/default.jpg';

		$webpage = ( new WebPage_Schema( Schema_Context::from_post( $post ) ) )->generate();

		$this->assertSame( 'https://cdn.example.org/og.jpg', $webpage['primaryImageOfPage']['url'] );
	}

	/**
	 * The breadcrumb reference is dropped when no BreadcrumbList is emitted.
	 */
	public function test_breadcrumb_reference_pruned_without_breadcrumb_node(): void {
		$post     = $this->make_post();
		$registry = Schema_Registry::instance();
		$registry->register( 'webpage', WebPage_Schema::class, array( 'priority' => 10 ) );

		$result = ( new Schema_Graph_Manager( $registry ) )->build( Schema_Context::from_post( $post ) );

		$this->assertSame( 'WebPage', $result['@graph'][0]['@type'] );
		$this->assertArrayNotHasKey( 'breadcrumb', $result['@graph'][0] );
	}

	/**
	 * The breadcrumb reference stays when the BreadcrumbList is present.
	 */
	public function test_breadcrumb_reference_kept_with_breadcrumb_node(): void {
		$post     = $this->make_post();
		$registry = Schema_Registry::instance();
		$registry->register( 'webpage', WebPage_Schema::class, array( 'priority' => 10 ) );

		add_filter(
			'saman_seo_schema_graph',
			static function ( $graph ) {
				$graph[] = array(
					'@type'           => 'BreadcrumbList',
					'@id'             => 'https://example.org/sample-post/#breadcrumb',
					'itemListElement' => array(),
				);
				return $graph;
			}
		);

		$result = ( new Schema_Graph_Manager( $registry ) )->build( Schema_Context::from_post( $post ) );

		$this->assertSame(
			array( '@id' => 'https://example.org/sample-post/#breadcrumb' ),
			$result['@graph'][0]['breadcrumb']
		);
	}

	/**
	 * Legacy graph filter nodes are included and lose a nested @context.
	 */
	public function test_legacy_graph_filter_nodes_lose_nested_context(): void {
		$post     = $this->make_post();
		$registry = Schema_Registry::instance();
		$registry->register( 'dummy', Dummy_Schema::class, array( 'priority' => 1 ) );

		add_filter(
			'SAMAN_SEO_jsonld_graph',
			static function ( $graph, $graph_post ) {
				$graph[] = array(
					'@context' => 'https://schema.org',
					'@type'    => 'Course',
					'name'     => $graph_post ? $graph_post->post_title : 'none',
				);
				return $graph;
			},
			20,
			2
		);

		$result = ( new Schema_Graph_Manager( $registry ) )->build( Schema_Context::from_post( $post ) );

		$this->assertSame( 'Course', $result['@graph'][1]['@type'] );
		$this->assertSame( 'Sample Post', $result['@graph'][1]['name'] );
		$this->assertArrayNotHasKey( '@context', $result['@graph'][1] );
	}

	/**
	 * The JSON-LD payload uses the canonical the head tag resolved.
	 */
	public function test_payload_uses_passed_canonical(): void {
		$post     = $this->make_post();
		$registry = Schema_Registry::instance();
		$registry->register( 'webpage', WebPage_Schema::class, array( 'priority' => 10 ) );

		$payload = ( new JsonLD() )->build_payload( array(), $post, 'https://example.org/sample-post/2/' );

		$this->assertSame( 'https://example.org/sample-post/2/#webpage', $payload['@graph'][0]['@id'] );
	}

	/**
	 * render_json_ld passes no post on archives and the head canonical.
	 */
	public function test_render_json_ld_passes_null_post_on_archives(): void {
		$this->make_post();
		$this->set_context( 'singular', false );
		$this->set_context( 'archive', true );
		$this->set_context( 'category', true );
		self::$query_vars['paged'] = 2;

		$captured = array();
		add_filter(
			'saman_seo_jsonld',
			static function ( $payload, $post, $canonical ) use ( &$captured ) {
				$captured = array( $post, $canonical );
				return array();
			},
			10,
			3
		);

		$this->capture_output(
			static function () {
				( new Frontend() )->render_json_ld();
			}
		);

		$this->assertNull( $captured[0] );
		$this->assertSame( 'https://example.org/page/2/', $captured[1] );
	}

	/**
	 * On a singular view render_json_ld passes the post and its canonical override.
	 */
	public function test_render_json_ld_passes_canonical_override(): void {
		$post = $this->make_post();
		self::$post_meta[ $post->ID ]['_SAMAN_SEO_meta'] = array( 'canonical' => 'https://example.org/original/' );

		$captured = array();
		add_filter(
			'saman_seo_jsonld',
			static function ( $payload, $passed_post, $canonical ) use ( &$captured ) {
				$captured = array( $passed_post, $canonical );
				return array();
			},
			10,
			3
		);

		$this->capture_output(
			static function () {
				( new Frontend() )->render_json_ld();
			}
		);

		$this->assertSame( $post, $captured[0] );
		$this->assertSame( 'https://example.org/original/', $captured[1] );
	}
}
