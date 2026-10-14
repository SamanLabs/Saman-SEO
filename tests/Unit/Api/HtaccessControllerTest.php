<?php
/**
 * Tests for the .htaccess editor's safety checks.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Htaccess_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Htaccess editor hardening coverage.
 */
class HtaccessControllerTest extends TestCase {

	/**
	 * Controller under test.
	 *
	 * @var Htaccess_Controller
	 */
	private Htaccess_Controller $controller;

	/**
	 * Boot controller.
	 */
	protected function setUp(): void {
		parent::setUp();

		if ( ! defined( 'WP_CONTENT_DIR' ) ) {
			define( 'WP_CONTENT_DIR', sys_get_temp_dir() . '/saman-seo-test-content' );
		}

		Functions\when( 'is_wp_error' )->alias(
			static function ( $thing ) {
				return $thing instanceof \WP_Error;
			}
		);

		$this->controller = new Htaccess_Controller();
	}

	/**
	 * Directives that can turn uploads into executable code.
	 *
	 * @return array<string,array{0:string}>
	 */
	public function unsafe_rules(): array {
		return array(
			'addtype php'           => array( 'AddType application/x-httpd-php .jpg' ),
			'addtype indented'      => array( "<IfModule mod_mime.c>\n    AddType application/x-httpd-php5 .png\n</IfModule>" ),
			'addtype cgi'           => array( 'AddType application/x-httpd-cgi .txt' ),
			'addhandler'            => array( 'AddHandler cgi-script .gif' ),
			'sethandler'            => array( "<Files x.jpg>\nSetHandler application/x-httpd-php\n</Files>" ),
			'forcetype'             => array( 'ForceType application/x-httpd-php' ),
			'options plus execcgi'  => array( 'Options +ExecCGI' ),
			'options execcgi'       => array( 'Options Indexes ExecCGI' ),
			'php_value prepend'     => array( 'php_value auto_prepend_file /tmp/x.jpg' ),
			'php_flag'              => array( 'php_flag engine on' ),
			'rewrite handler flag'  => array( 'RewriteRule ^(.*)\.jpg$ - [H=application/x-httpd-php]' ),
			'rewrite handler long'  => array( 'RewriteRule \.png$ - [L,handler=php-script]' ),
			'rewrite type php'      => array( 'RewriteRule \.png$ - [T=application/x-httpd-php]' ),
			'line continuation'     => array( "Add\\\nType application/x-httpd-php .jpg" ),
		);
	}

	/**
	 * Every dangerous directive is rejected.
	 *
	 * @dataProvider unsafe_rules
	 *
	 * @param string $content .htaccess content.
	 */
	public function test_blocks_unsafe_directives( string $content ): void {
		$result = $this->controller->validate_content( $content );

		$this->assertInstanceOf( \WP_Error::class, $result );
	}

	/**
	 * The stock WordPress rules and common safe directives still pass.
	 */
	public function test_allows_standard_rules(): void {
		$content = implode(
			"\n",
			array(
				'# BEGIN WordPress',
				'<IfModule mod_rewrite.c>',
				'RewriteEngine On',
				'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]',
				'RewriteBase /',
				'RewriteRule ^index\.php$ - [L]',
				'RewriteCond %{REQUEST_FILENAME} !-f',
				'RewriteCond %{REQUEST_FILENAME} !-d',
				'RewriteRule . /index.php [L]',
				'</IfModule>',
				'# END WordPress',
				'Options -Indexes -ExecCGI',
				'AddType image/webp .webp',
				'Redirect 301 /old /new',
			)
		);

		$this->assertTrue( $this->controller->validate_content( $content ) );
	}

	/**
	 * Writes are refused when the site disallows file modifications.
	 */
	public function test_write_permission_respects_file_mods(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( false );

		$this->assertInstanceOf( \WP_Error::class, $this->controller->write_permission_check() );
	}

	/**
	 * Writes are allowed for an admin when file mods are allowed.
	 */
	public function test_write_permission_allows_admin(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( false );
		Functions\when( 'wp_is_file_mod_allowed' )->justReturn( true );

		$this->assertTrue( $this->controller->write_permission_check() );
	}

	/**
	 * On multisite only super admins may touch the shared file.
	 */
	public function test_multisite_requires_super_admin(): void {
		Functions\when( 'current_user_can' )->justReturn( true );
		Functions\when( 'is_multisite' )->justReturn( true );
		Functions\when( 'is_super_admin' )->justReturn( false );

		$this->assertFalse( $this->controller->permission_check() );
	}

	/**
	 * Loopback responses: 5xx and transport errors mean the site is down.
	 */
	public function test_site_responds(): void {
		Functions\when( 'wp_remote_retrieve_response_code' )->alias(
			static function ( $response ) {
				return $response['response']['code'];
			}
		);

		$this->assertTrue( Htaccess_Controller::site_responds( array( 'response' => array( 'code' => 200 ) ) ) );
		$this->assertTrue( Htaccess_Controller::site_responds( array( 'response' => array( 'code' => 404 ) ) ) );
		$this->assertFalse( Htaccess_Controller::site_responds( array( 'response' => array( 'code' => 500 ) ) ) );
		$this->assertFalse( Htaccess_Controller::site_responds( new \WP_Error( 'http_request_failed', 'timeout' ) ) );
	}
}
