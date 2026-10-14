<?php
/**
 * Tests for get_array_option(), which reads list/map options stored either as
 * arrays or as JSON strings.
 *
 * @package Saman\SEO\Tests\Unit\Helpers
 */

namespace Saman\SEO\Tests\Unit\Helpers;

use Saman\SEO\Tests\TestCase;
use function Saman\SEO\Helpers\get_array_option;

/**
 * get_array_option coverage.
 */
class ArrayOptionTest extends TestCase {

	/**
	 * Arrays saved through update_option or a settings sanitizer pass through.
	 */
	public function test_array_value_is_returned_as_is(): void {
		self::$options['SAMAN_SEO_local_social_profiles'] = array( 'https://example.com/a' );

		$this->assertSame( array( 'https://example.com/a' ), get_array_option( 'SAMAN_SEO_local_social_profiles' ) );
	}

	/**
	 * JSON strings saved by the admin screen are decoded.
	 */
	public function test_json_string_is_decoded(): void {
		self::$options['SAMAN_SEO_local_opening_hours'] = '{"monday":{"enabled":"1","open":"07:00","close":"19:00"}}';

		$this->assertSame(
			array( 'monday' => array( 'enabled' => '1', 'open' => '07:00', 'close' => '19:00' ) ),
			get_array_option( 'SAMAN_SEO_local_opening_hours' )
		);
	}

	/**
	 * Missing, empty and malformed values all read as an empty array.
	 */
	public function test_unset_empty_and_invalid_values_read_as_empty(): void {
		$this->assertSame( array(), get_array_option( 'SAMAN_SEO_missing' ) );

		self::$options['SAMAN_SEO_empty'] = '';
		$this->assertSame( array(), get_array_option( 'SAMAN_SEO_empty' ) );

		self::$options['SAMAN_SEO_invalid'] = '{not json';
		$this->assertSame( array(), get_array_option( 'SAMAN_SEO_invalid' ) );

		self::$options['SAMAN_SEO_scalar'] = '"just a string"';
		$this->assertSame( array(), get_array_option( 'SAMAN_SEO_scalar' ) );
	}
}
