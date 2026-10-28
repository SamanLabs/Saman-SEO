<?php
/**
 * Tests that the bulk editor's "missing" filters cover every post, not one page.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Tools_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Bulk editor filter and pagination coverage.
 */
class BulkEditorFilterTest extends TestCase {

	/**
	 * Install stubs.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'rest_ensure_response' )->returnArg();
		Functions\when( 'get_edit_post_link' )->justReturn( '' );
		\WP_Query::$fixture_posts = array();
	}

	/**
	 * Clear the query fixture.
	 */
	protected function tearDown(): void {
		\WP_Query::$fixture_posts = array();
		parent::tearDown();
	}

	/**
	 * Minimal request double.
	 *
	 * @param array $params Query params.
	 * @return object
	 */
	private function request( array $params ) {
		return new class( $params ) {
			/**
			 * Params.
			 *
			 * @var array
			 */
			private $params;

			/**
			 * Constructor.
			 *
			 * @param array $params Params.
			 */
			public function __construct( $params ) {
				$this->params = $params;
			}

			/**
			 * Read a param.
			 *
			 * @param string $key Key.
			 * @return mixed
			 */
			public function get_param( $key ) {
				return $this->params[ $key ] ?? null;
			}
		};
	}

	/**
	 * 300 posts, of which only the last two lack an SEO title: the filter finds both and
	 * reports the page count the admin's pager reads.
	 */
	public function test_missing_title_filter_searches_past_the_first_page() {
		for ( $id = 1; $id <= 300; $id++ ) {
			\WP_Query::$fixture_posts[]              = $this->make_post( array( 'ID' => $id ) );
			self::$post_meta[ $id ]['_SAMAN_SEO_meta'] = array( 'title' => $id > 298 ? '' : "Title {$id}" );
		}

		$response = ( new Tools_Controller() )->get_posts_for_bulk_edit(
			$this->request(
				array(
					'filter'   => 'missing_title',
					'per_page' => 20,
				)
			)
		);

		$this->assertSame( array( 299, 300 ), array_column( $response['data']['posts'], 'id' ) );
		$this->assertSame( 2, $response['data']['total'] );
		$this->assertSame( 1, $response['data']['pages'] );
	}

	/**
	 * per_page=-1 must not load every post and its meta at once.
	 */
	public function test_per_page_is_bounded() {
		for ( $id = 1; $id <= 150; $id++ ) {
			\WP_Query::$fixture_posts[] = $this->make_post( array( 'ID' => $id ) );
		}

		$response = ( new Tools_Controller() )->get_posts_for_bulk_edit( $this->request( array( 'per_page' => -1 ) ) );

		$this->assertCount( 1, $response['data']['posts'] );

		$response = ( new Tools_Controller() )->get_posts_for_bulk_edit( $this->request( array( 'per_page' => 1000 ) ) );

		$this->assertCount( 100, $response['data']['posts'] );
	}
}
