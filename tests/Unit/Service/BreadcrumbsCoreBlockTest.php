<?php
/**
 * Tests for the breadcrumbs service's WP 7.0 core/breadcrumbs integration
 * and the BreadcrumbList schema built from the same trail.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Brain\Monkey\Functions;
use Saman\SEO\Schema\Schema_Context;
use Saman\SEO\Schema\Types\Breadcrumb_Schema;
use Saman\SEO\Service\Breadcrumbs;
use Saman\SEO\Tests\TestCase;

/**
 * Core block items, double-output guard, schema/visible trail agreement.
 */
class BreadcrumbsCoreBlockTest extends TestCase {

	/**
	 * Items core would render on its own.
	 *
	 * @var array
	 */
	private const CORE_ITEMS = array(
		array(
			'label' => 'Home',
			'url'   => 'https://example.org/',
		),
		array( 'label' => 'Sample Post' ),
	);

	/**
	 * Set up a singular post in category "News" with breadcrumbs enabled.
	 */
	protected function setUp(): void {
		parent::setUp();

		self::$options['SAMAN_SEO_breadcrumb_settings'] = array( 'enabled' => true );

		$post = $this->make_post();
		$term = new \WP_Term(
			array(
				'term_id'  => 7,
				'name'     => 'News',
				'slug'     => 'news',
				'taxonomy' => 'category',
			)
		);

		Functions\when( 'get_queried_object_id' )->justReturn( $post->ID );
		Functions\when( 'get_the_ID' )->justReturn( $post->ID );
		Functions\when( 'is_admin' )->justReturn( false );
		Functions\when( 'is_feed' )->justReturn( false );
		Functions\when( 'is_main_query' )->justReturn( true );
		Functions\when( 'in_the_loop' )->justReturn( true );
		Functions\when( 'get_post_type_object' )->justReturn( null );
		Functions\when( 'get_the_category' )->justReturn( array( $term ) );
		Functions\when( 'get_ancestors' )->justReturn( array() );
		Functions\when( 'get_term_link' )->justReturn( 'https://example.org/category/news/' );
		Functions\when( 'is_wp_error' )->justReturn( false );
		Functions\when( 'esc_attr_e' )->alias(
			static function ( $text ) {
				echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			}
		);
		Functions\when( 'wp_style_is' )->justReturn( false );
	}

	/**
	 * Reset the plugin singleton's services.
	 */
	protected function tearDown(): void {
		$this->set_plugin_service( null );
		$GLOBALS['_wp_current_template_content'] = null;
		parent::tearDown();
	}

	/**
	 * Put a breadcrumbs service on the plugin singleton (or clear it).
	 *
	 * @param Breadcrumbs|null $service Service.
	 * @return void
	 */
	private function set_plugin_service( $service ): void {
		$plugin   = \Saman\SEO\Plugin::instance();
		$property = new \ReflectionProperty( $plugin, 'services' );
		$property->setAccessible( true );
		$property->setValue( $plugin, $service ? array( 'breadcrumbs' => $service ) : array() );
	}

	/**
	 * The core block shows the plugin trail, including the category.
	 */
	public function test_core_block_items_use_plugin_trail(): void {
		$service = new Breadcrumbs();
		$service->capture_core_block(
			array( 'postId' => 42 ),
			array(
				'blockName' => 'core/breadcrumbs',
				'attrs'     => array(),
			)
		);

		$items = $service->filter_core_block_items( self::CORE_ITEMS );

		$this->assertSame(
			array(
				array(
					'label' => 'Home',
					'url'   => 'https://example.org/',
				),
				array(
					'label' => 'News',
					'url'   => 'https://example.org/category/news/',
				),
				array( 'label' => 'Sample Post' ),
			),
			$items
		);
	}

	/**
	 * The block's own home/current toggles are respected.
	 */
	public function test_core_block_toggles_are_respected(): void {
		$service = new Breadcrumbs();
		$service->capture_core_block(
			array( 'postId' => 42 ),
			array(
				'blockName' => 'core/breadcrumbs',
				'attrs'     => array(
					'showHomeItem'    => false,
					'showCurrentItem' => false,
				),
			)
		);

		$items = $service->filter_core_block_items( self::CORE_ITEMS );

		$this->assertSame(
			array(
				array(
					'label' => 'News',
					'url'   => 'https://example.org/category/news/',
				),
			),
			$items
		);
	}

	/**
	 * A core block rendering for another post (Query Loop) keeps core's items.
	 */
	public function test_core_block_for_other_post_is_untouched(): void {
		$service = new Breadcrumbs();
		$service->capture_core_block(
			array( 'postId' => 99 ),
			array(
				'blockName' => 'core/breadcrumbs',
				'attrs'     => array(),
			)
		);

		$this->assertSame( self::CORE_ITEMS, $service->filter_core_block_items( self::CORE_ITEMS ) );
	}

	/**
	 * With breadcrumbs disabled in settings, core's items are kept.
	 */
	public function test_core_block_items_kept_when_disabled(): void {
		self::$options['SAMAN_SEO_breadcrumb_settings'] = array( 'enabled' => false );

		$service = new Breadcrumbs();

		$this->assertSame( self::CORE_ITEMS, $service->filter_core_block_items( self::CORE_ITEMS ) );
	}

	/**
	 * A rendered core block suppresses the automatic the_content trail.
	 */
	public function test_rendered_core_block_suppresses_auto_output(): void {
		$service = new Breadcrumbs();

		$this->assertStringContainsString( 'saman-seo-breadcrumbs', $service->maybe_prepend_breadcrumbs( '<p>Body</p>' ) );

		$service = new Breadcrumbs();
		$service->mark_core_block_rendered( '<nav class="wp-block-breadcrumbs"></nav>' );

		$this->assertSame( '<p>Body</p>', $service->maybe_prepend_breadcrumbs( '<p>Body</p>' ) );
		$this->assertSame( '', $this->capture_output( array( $service, 'maybe_footer_breadcrumbs' ) ) );
	}

	/**
	 * An empty core block render does not suppress the plugin trail.
	 */
	public function test_empty_core_block_render_does_not_suppress(): void {
		$service = new Breadcrumbs();
		$service->mark_core_block_rendered( '' );

		$this->assertStringContainsString( 'saman-seo-breadcrumbs', $service->maybe_prepend_breadcrumbs( '<p>Body</p>' ) );
	}

	/**
	 * A block theme template containing core/breadcrumbs suppresses the auto trail.
	 */
	public function test_template_with_core_block_suppresses_auto_output(): void {
		$GLOBALS['_wp_current_template_content'] = '<!-- wp:breadcrumbs /--><!-- wp:post-content /-->';

		$service = new Breadcrumbs();

		$this->assertSame( '<p>Body</p>', $service->maybe_prepend_breadcrumbs( '<p>Body</p>' ) );
	}

	/**
	 * BreadcrumbList schema follows the visible trail (category included).
	 */
	public function test_schema_uses_visible_trail(): void {
		$this->set_plugin_service( new Breadcrumbs() );

		$context            = new Schema_Context();
		$context->site_name = 'Test Site';
		$context->post      = self::$posts[42];
		$context->canonical = 'https://example.org/sample-post/';

		$items = ( new Breadcrumb_Schema( $context ) )->generate()['itemListElement'];

		$this->assertCount( 3, $items );
		$this->assertSame( 'Home', $items[0]['name'] );
		$this->assertSame( 'https://example.org/', $items[0]['item'] );
		$this->assertSame( 'News', $items[1]['name'] );
		$this->assertSame( 'https://example.org/category/news/', $items[1]['item'] );
		$this->assertSame( 2, $items[1]['position'] );
		$this->assertSame( 'Sample Post', $items[2]['name'] );
		$this->assertSame( 'https://example.org/sample-post/', $items[2]['item'] );
		$this->assertSame( 3, $items[2]['position'] );
	}

	/**
	 * Schema for a post other than the queried one falls back to the hierarchy.
	 */
	public function test_schema_falls_back_for_non_queried_post(): void {
		$this->set_plugin_service( new Breadcrumbs() );
		Functions\when( 'get_queried_object_id' )->justReturn( 1 );

		$context            = new Schema_Context();
		$context->site_name = 'Test Site';
		$context->post      = self::$posts[42];
		$context->canonical = 'https://example.org/sample-post/';

		$items = ( new Breadcrumb_Schema( $context ) )->generate()['itemListElement'];

		$this->assertCount( 2, $items );
		$this->assertSame( 'Test Site', $items[0]['name'] );
		$this->assertSame( 'Sample Post', $items[1]['name'] );
	}
}
