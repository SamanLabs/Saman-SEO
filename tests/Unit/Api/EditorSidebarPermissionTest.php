<?php
/**
 * Tests that the post editor's SEO sidebar works for everyone who may edit the post.
 *
 * @package Saman\SEO\Tests\Unit\Api
 */

namespace Saman\SEO\Tests\Unit\Api;

use Brain\Monkey\Functions;
use Saman\SEO\Api\Audit_Controller;
use Saman\SEO\Tests\TestCase;

/**
 * Editor sidebar permission coverage.
 */
class EditorSidebarPermissionTest extends TestCase {

	/**
	 * An Author editing their own post gets the SEO score; another author's post stays closed.
	 */
	public function test_post_routes_follow_edit_post_not_manage_options() {
		Functions\when( 'current_user_can' )->alias(
			static function ( $cap, $post_id = null ) {
				return 'edit_post' === $cap && 5 === $post_id;
			}
		);
		$controller = new Audit_Controller();

		$this->assertFalse( $controller->permission_check(), 'an Author is not an administrator' );
		$this->assertTrue( $controller->edit_post_permission_check( new \ArrayObject( array( 'id' => 5 ) ) ) );
		$this->assertFalse( $controller->edit_post_permission_check( new \ArrayObject( array( 'id' => 6 ) ) ) );
		$this->assertFalse( $controller->edit_post_permission_check( new \ArrayObject( array( 'id' => 0 ) ) ) );
	}
}
