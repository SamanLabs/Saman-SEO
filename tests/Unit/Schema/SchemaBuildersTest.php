<?php
/**
 * Tests for Schema\Types builders.
 *
 * @package Saman\SEO\Tests\Unit\Schema
 */

namespace Saman\SEO\Tests\Unit\Schema;

use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Tests\TestCase;
use Saman\SEO\Tests\Unit\Schema\Fixtures\Dummy_Schema;
use Saman\SEO\Tests\Unit\Schema\Fixtures\Never_Needed_Schema;
use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Schema_Graph_Manager;

/**
 * Breadcrumb/Article/Graph coverage.
 */
class SchemaBuildersTest extends TestCase {

	/**
	 * Build a context for a post with the given meta.
	 *
	 * @param \WP_Post|null $post Post.
	 * @return Schema_Context
	 */
	private function make_context( $post ): Schema_Context {
		$context             = new Schema_Context();
		$context->site_url   = 'https://example.org/';
		$context->site_name  = 'Test Site';
		$context->post       = $post;
		$context->post_type  = $post ? $post->post_type : '';
		$context->canonical  = $post ? 'https://example.org/sample-post/' : 'https://example.org/';
		$context->meta       = array();
		$context->schema_type = 'Article';

		return $context;
	}

	/**
	 * Breadcrumb schema generates ordered ListItems with @id fragment.
	 */
	public function test_breadcrumb_schema_structure(): void {
		$post = $this->make_post();
		self::$ancestors[ $post->ID ] = array( 100 );
		self::$posts[100]            = new \WP_Post(
			array(
				'ID'         => 100,
				'post_title' => 'Parent Page',
				'post_name'  => 'parent-page',
				'post_type'  => 'page',
			)
		);
		self::$permalinks[100] = 'https://example.org/parent-page/';

		$schema   = new \Saman\SEO\Schema\Types\Breadcrumb_Schema( $this->make_context( $post ) );
		$generated = $schema->generate();

		$this->assertSame( 'BreadcrumbList', $generated['@type'] );
		$this->assertSame( 'https://example.org/sample-post/#breadcrumb', $generated['@id'] );

		$items = $generated['itemListElement'];
		$this->assertCount( 3, $items );

		$this->assertSame( 1, $items[0]['position'] );
		$this->assertSame( 'Test Site', $items[0]['name'] );
		$this->assertSame( 'https://example.org/', $items[0]['item'] );

		$this->assertSame( 2, $items[1]['position'] );
		$this->assertSame( 'Parent Page', $items[1]['name'] );

		$this->assertSame( 3, $items[2]['position'] );
		$this->assertSame( 'Sample Post', $items[2]['name'] );
		$this->assertSame( 'https://example.org/sample-post/', $items[2]['item'] );
	}

	/**
	 * Breadcrumb schema is skipped without a post context.
	 */
	public function test_breadcrumb_not_needed_without_post(): void {
		$schema = new \Saman\SEO\Schema\Types\Breadcrumb_Schema( $this->make_context( null ) );

		$this->assertFalse( $schema->is_needed() );
	}

	/**
	 * Article schema emits headline, dates, author and publisher.
	 */
	public function test_article_schema_shape(): void {
		$post = $this->make_post(
			array(
				'post_content' => '<p>' . implode( ' ', array_fill( 0, 200, 'lorem' ) ) . '</p>',
			)
		);
		self::$authors[1] = 'Jane Author';
		self::$options['SAMAN_SEO_default_og_image'] = 'https://cdn.example.org/default.jpg';

		$context              = $this->make_context( $post );
		$context->schema_type = 'Article';

		$schema    = new \Saman\SEO\Schema\Types\Article_Schema( $context );
		$generated = $schema->generate();

		$this->assertSame( 'Article', $generated['@type'] );
		$this->assertSame( 'https://example.org/sample-post/#article', $generated['@id'] );
		$this->assertSame( 'Sample Post', $generated['headline'] );
		$this->assertSame( 'Jane Author', $generated['author']['name'] );
		$this->assertSame( 'Person', $generated['author']['@type'] );
		$this->assertSame( 'Organization', $generated['publisher']['@type'] );
		$this->assertSame( 'Test Site', $generated['publisher']['name'] );
		$this->assertGreaterThan( 0, $generated['wordCount'] );
		$this->assertSame( 'https://cdn.example.org/default.jpg', $generated['image'] );
	}

	/**
	 * Article schema only applies when the context type is Article.
	 */
	public function test_article_schema_respects_context_type(): void {
		$post = $this->make_post();

		$context              = $this->make_context( $post );
		$context->schema_type = 'Product';

		$schema = new \Saman\SEO\Schema\Types\Article_Schema( $context );

		$this->assertFalse( $schema->is_needed() );

		$context->schema_type = 'Article';
		$this->assertTrue( $schema->is_needed() );
	}

	/**
	 * BlogPosting overrides only the @type; it intentionally keeps the
	 * inherited #article @id fragment from Article_Schema::generate().
	 */
	public function test_blogposting_type_override(): void {
		$post = $this->make_post();

		$context              = $this->make_context( $post );
		$context->schema_type = 'BlogPosting';

		$schema    = new \Saman\SEO\Schema\Types\BlogPosting_Schema( $context );
		$generated = $schema->generate();

		$this->assertSame( 'BlogPosting', $generated['@type'] );
		$this->assertSame( 'https://example.org/sample-post/#article', $generated['@id'] );
	}

	/**
	 * The graph manager orders pieces by priority and wraps them in @graph.
	 */
	public function test_graph_manager_orders_and_wraps(): void {
		$registry = Schema_Registry::instance();
		$registry->register( 'late', Dummy_Schema::class, array( 'priority' => 50, 'label' => 'Late' ) );
		$registry->register( 'early', Dummy_Schema::class, array( 'priority' => 1, 'label' => 'Early' ) );
		$registry->register( 'never', Never_Needed_Schema::class, array( 'priority' => 5 ) );

		$post    = $this->make_post();
		$manager = new Schema_Graph_Manager( $registry );
		$result  = $manager->build( $this->make_context( $post ) );

		$this->assertSame( 'https://schema.org', $result['@context'] );
		$this->assertCount( 2, $result['@graph'] );
		$this->assertSame( 'Dummy', $result['@graph'][0]['@type'] ); // priority 1 first.
		$this->assertSame( 'https://example.org/sample-post/', $result['@graph'][0]['url'] );

		// Cleanup singleton state between tests.
		$this->reset_registry();
	}

	/**
	 * The graph manager honours per-type output filters.
	 */
	public function test_graph_manager_applies_output_filter(): void {
		add_filter(
			'saman_seo_schema_early_output',
			static function () {
				return array(); // Filtering to empty drops the piece.
			}
		);

		$registry = Schema_Registry::instance();
		$registry->register( 'early', Dummy_Schema::class, array( 'priority' => 1 ) );

		$post    = $this->make_post();
		$manager = new Schema_Graph_Manager( $registry );
		$result  = $manager->build( $this->make_context( $post ) );

		$this->assertSame( array(), $result['@graph'] );

		$this->reset_registry();
	}

	/**
	 * Reset the schema registry singleton via reflection.
	 *
	 * @return void
	 */
	private function reset_registry(): void {
		$ref      = new \ReflectionClass( Schema_Registry::class );
		$instance = $ref->getProperty( 'instance' );
		$instance->setAccessible( true );
		$instance->setValue( null, null );
	}
}
