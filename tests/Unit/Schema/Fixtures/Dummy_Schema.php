<?php
/**
 * Schema test fixtures.
 *
 * @package Saman\SEO\Tests\Unit\Schema\Fixtures
 */

namespace Saman\SEO\Tests\Unit\Schema\Fixtures;

use Saman\SEO\Schema\Abstract_Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Always-needed dummy schema used to verify registry ordering.
 */
class Dummy_Schema extends Abstract_Schema {

	/**
	 * @return string
	 */
	public function get_type() {
		return 'Dummy';
	}

	/**
	 * @return bool
	 */
	public function is_needed(): bool {
		return true;
	}

	/**
	 * @return array
	 */
	public function generate(): array {
		return array(
			'@type' => 'Dummy',
			'url'   => $this->context->canonical,
		);
	}
}

/**
 * Never-needed dummy schema.
 */
class Never_Needed_Schema extends Abstract_Schema {

	/**
	 * @return string
	 */
	public function get_type() {
		return 'NeverNeeded';
	}

	/**
	 * @return bool
	 */
	public function is_needed(): bool {
		return false;
	}

	/**
	 * @return array
	 */
	public function generate(): array {
		return array();
	}
}
