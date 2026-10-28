<?php
/**
 * Tests that saved sitemap pages, robots.txt rules and per-post robots choices are kept as entered.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Sitemap_Controller;
use Saman\SEO\Api\Tools_Controller;
use Saman\SEO\Service\Post_Meta;
use Saman\SEO\Service\Yoast_Bridge;
use Saman\SEO\Tests\TestCase;

/**
 * Saved-content regression coverage.
 */
class SavedContentKeptTest extends TestCase {

	/**
	 * Install the stubs the controllers need.
	 */
	protected function setUp(): void {
		parent::setUp();

		Functions\when( 'rest_ensure_response' )->returnArg();
		Functions\when( 'delete_transient' )->justReturn( true );
	}

	/**
	 * Minimal request double.
	 *
	 * @param array $json JSON body.
	 * @return object
	 */
	private function request( array $json ) {
		return new class( $json ) {
			/**
			 * JSON body.
			 *
			 * @var array
			 */
			private $json;

			/**
			 * Constructor.
			 *
			 * @param array $json JSON body.
			 */
			public function __construct( $json ) {
				$this->json = $json;
			}

			/**
			 * JSON body.
			 *
			 * @return array
			 */
			public function get_json_params() {
				return $this->json;
			}

			/**
			 * Params.
			 *
			 * @return array
			 */
			public function get_params() {
				return $this->json;
			}
		};
	}

	/**
	 * The Sitemap page sends { url, priority } rows; each used to be flattened to ''.
	 */
	public function test_additional_sitemap_pages_survive_a_save() {
		( new Sitemap_Controller() )->update_settings(
			$this->request(
				array(
					'additional_pages' => array(
						array(
							'url'      => 'https://example.org/landing/',
							'priority' => '0.8',
						),
						array(
							'url'      => 'https://example.org/clamped/',
							'priority' => 7,
						),
						array( 'url' => '' ),
						'not a row',
					),
				)
			)
		);

		$this->assertSame(
			array(
				array(
					'url'      => 'https://example.org/landing/',
					'priority' => 0.8,
				),
				array(
					'url'      => 'https://example.org/clamped/',
					'priority' => 1.0,
				),
			),
			self::$options['SAMAN_SEO_sitemap_additional_pages']
		);
	}

	/**
	 * robots.txt is plain text: & and %xx in a rule must reach crawlers unchanged.
	 */
	public function test_robots_txt_keeps_ampersands_and_encoded_characters() {
		$rules = "User-agent: *\nDisallow: /*?*&replytocom=\nDisallow: /search%20results/";

		( new Tools_Controller() )->save_robots_txt( $this->request( array( 'content' => $rules . '<script>x</script>' ) ) );

		$this->assertSame( $rules, self::$options['SAMAN_SEO_robots_txt'] );
	}

	/**
	 * Once Saman has saved a post, its empty fields are choices: Yoast must not fill them back.
	 * A post Saman never saved still inherits the Yoast values.
	 */
	public function test_yoast_fills_only_fields_saman_never_saved() {
		self::$options[ Yoast_Bridge::SIGNAL_OPTION ] = array( 'separator' => 'sc-dash' );

		foreach ( array( 5, 6 ) as $post_id ) {
			self::$post_meta[ $post_id ]['_yoast_wpseo_meta-robots-noindex'] = '1';
			self::$post_meta[ $post_id ]['_yoast_wpseo_canonical']           = 'https://example.org/yoast/';
		}
		self::$post_meta[6][ Post_Meta::META_KEY ] = array(
			'canonical' => '',
			'noindex'   => '',
			'nofollow'  => '',
		);

		$bridge = new Yoast_Bridge();

		$inherited = $bridge->bridge_post_meta( null, 5, Post_Meta::META_KEY );
		$this->assertSame( '1', $inherited[0]['noindex'] );
		$this->assertSame( 'https://example.org/yoast/', $inherited[0]['canonical'] );

		$this->assertNull(
			$bridge->bridge_post_meta( null, 6, Post_Meta::META_KEY ),
			'nothing is merged into a post whose noindex and canonical were cleared in Saman'
		);
	}
}
