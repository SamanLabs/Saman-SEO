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
		add_filter( 'robots_txt', array( $this, 'filter_robots' ), 10, 2 );
	}

	/**
	 * Override robots content if option provided.
	 *
	 * @param string $output    Default.
	 * @param bool   $is_public Public flag.
	 *
	 * @return string
	 */
	public function filter_robots( $output, $is_public ) {
		$custom = get_option( 'SAMAN_SEO_robots_txt', '' );

		if ( $custom ) {
			$output = $custom;
		}

		// Advertise the Saman SEO sitemap index in robots.txt so search engines can
		// discover it. WordPress core only references its own wp-sitemap.xml, and a
		// custom robots.txt would otherwise drop the sitemap reference entirely.
		if ( $is_public ) {
			$sitemap_url = home_url( '/sitemap_index.xml' );

			if ( false === stripos( (string) $output, $sitemap_url ) ) {
				$output = rtrim( (string) $output ) . "\n\nSitemap: " . esc_url_raw( $sitemap_url ) . "\n";
			}
		}

		return $output;
	}
}
