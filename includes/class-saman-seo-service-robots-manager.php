<?php
/**
 * Robots.txt overrides.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Robots manager.
 */
class Robots_Manager {

	/**
	 * Boot filter.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'robots_txt', [ $this, 'filter_robots' ], 10, 2 );
	}

	/**
	 * Override robots content if option provided.
	 *
	 * @param string $output Default.
	 * @param bool   $public Public flag.
	 *
	 * @return string
	 */
	public function filter_robots( $output, $public ) {
		$custom = get_option( 'SAMAN_SEO_robots_txt', '' );

		if ( $custom ) {
			return $custom;
		}

		return $output;
	}
}
