<?php
/**
 * Tests that archive templates and breadcrumb titles keep the text the site set.
 *
 * @package Saman\SEO\Tests\Unit\Service
 */

namespace Saman\SEO\Tests\Unit\Service;

use Saman\SEO\Service\Breadcrumbs;
use Saman\SEO\Service\Frontend;
use Saman\SEO\Tests\TestCase;

/**
 * Archive template and breadcrumb text coverage.
 */
class ArchiveTextKeptTest extends TestCase {

	/**
	 * A saved archive title without {{variables}} is used, not swapped for the default.
	 */
	public function test_plain_archive_title_is_kept() {
		self::$options['SAMAN_SEO_archive_defaults'] = array(
			'404' => array( 'title_template' => 'Oops, not found' ),
		);

		$defaults = new \ReflectionMethod( Frontend::class, 'get_archive_defaults' );
		$defaults->setAccessible( true );
		$archives = $defaults->invoke( ( new \ReflectionClass( Frontend::class ) )->newInstanceWithoutConstructor() );

		$this->assertSame( 'Oops, not found', $archives['404']['title_template'] );
		$this->assertSame( 'The page you are looking for could not be found.', $archives['404']['description_template'], 'empty fields still get defaults' );
	}

	/**
	 * Truncation counts characters: a byte cut inside "é" left invalid UTF-8 and an empty crumb.
	 */
	public function test_breadcrumb_truncation_keeps_multibyte_titles_valid() {
		$truncate = new \ReflectionMethod( Breadcrumbs::class, 'truncate_titles' );
		$truncate->setAccessible( true );
		$crumbs = $truncate->invoke(
			( new \ReflectionClass( Breadcrumbs::class ) )->newInstanceWithoutConstructor(),
			array( array( 'title' => 'Café crème brûlée' ) ),
			8
		);

		$this->assertSame( 'Café ...', $crumbs[0]['title'] );
		$this->assertTrue( mb_check_encoding( $crumbs[0]['title'], 'UTF-8' ) );
	}
}
