<?php
/**
 * wp_timezone() stand-in backed by the in-memory option store.
 *
 * Declared as a real function (not a Brain Monkey stub) so it stays usable
 * by later tests: a Brain Monkey stub for an undefined function throws
 * once its test has finished. Tests pick a zone through the
 * `timezone_string` option; it defaults to UTC.
 *
 * @package Saman\SEO\Tests\Unit\Schema\Fixtures
 */

// phpcs:ignoreFile -- test double.

if ( ! function_exists( 'wp_timezone' ) ) {
	/**
	 * Site timezone from the test option store.
	 *
	 * @return DateTimeZone
	 */
	function wp_timezone() {
		$zone = \Saman\SEO\Tests\TestCase::$options['timezone_string'] ?? '';

		return new DateTimeZone( '' !== $zone ? $zone : 'UTC' );
	}
}
