<?php
/**
 * Detects other SEO plugins to avoid double-outputting.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility helper.
 */
class Compatibility {

	/**
	 * Conflicting plugin slugs.
	 *
	 * @var array<string,string>
	 */
	private $conflicts = [
		'yoast'      => 'WPSEO_Frontend',
		'rank-math'  => 'RankMath',
		'aioseo'     => 'All_in_One_SEO_Pack',
	];

	/**
	 * Whether conflict detected.
	 *
	 * @var string|null
	 */
	private $active_conflict = null;

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( $this->conflicts as $slug => $class ) {
			if ( class_exists( $class ) ) {
				$this->active_conflict = $slug;
				break;
			}
		}

		if ( $this->active_conflict ) {
			add_action( 'admin_notices', [ $this, 'conflict_notice' ] );
			add_filter( 'SAMAN_SEO_feature_toggle', [ $this, 'maybe_disable' ], 10, 2 );
		}

		// Local WP on Windows can be very slow resolving .local domains,
		// causing the validation suite's wp_remote_get calls to timeout.
		// Force IPv4, a static host-to-127.0.0.1 resolution, and a longer
		// timeout for same-domain requests so the suite can finish reliably.
		add_filter( 'http_request_args', [ $this, 'extend_local_timeout' ], 10, 2 );
		add_action( 'http_api_curl', [ $this, 'force_fast_local_resolve' ], 10, 3 );
	}

	/**
	 * Extend the HTTP timeout for same-domain requests on slow Local stacks.
	 *
	 * @param array  $parsed_args Request arguments.
	 * @param string $url         Request URL.
	 *
	 * @return array
	 */
	public function extend_local_timeout( $parsed_args, $url ) {
		$host      = wp_parse_url( $url, PHP_URL_HOST );
		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( ! $host || $host !== $site_host ) {
			return $parsed_args;
		}

		$parsed_args['timeout'] = 20;
		return $parsed_args;
	}

	/**
	 * Force fast loopback resolution for requests to the current site's domain.
	 *
	 * @param resource|\CurlHandle $handle      cURL handle.
	 * @param array                $parsed_args Request arguments.
	 * @param string               $url         Request URL.
	 *
	 * @return void
	 */
	public function force_fast_local_resolve( $handle, $parsed_args, $url ) {
		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return;
		}

		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		if ( $host !== $site_host ) {
			return;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		$port   = wp_parse_url( $url, PHP_URL_PORT );
		$port   = $port ?: ( 'https' === $scheme ? 443 : 80 );

		curl_setopt( $handle, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4 );
		curl_setopt( $handle, CURLOPT_RESOLVE, [ "$host:$port:127.0.0.1" ] );
		curl_setopt( $handle, CURLOPT_CONNECT_TO, [ "$host:$port:127.0.0.1:$port" ] );
	}

	/**
	 * Maybe disable features for conflicting installs.
	 *
	 * @param bool   $enabled Current state.
	 * @param string $feature Feature key.
	 *
	 * @return bool
	 */
	public function maybe_disable( $enabled, $feature ) {
		if ( ! $this->active_conflict ) {
			return $enabled;
		}

		$conflict_sensitive = [
			'frontend_head',
			'sitemaps',
			'metabox',
			'redirects',
		];

		if ( in_array( $feature, $conflict_sensitive, true ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Admin notice about conflicts.
	 *
	 * @return void
	 */
	public function conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: plugin name. */
					__( 'Saman SEO detected another SEO plugin. Some overlapping features are disabled until you deactivate %s or run the migration.', 'saman-seo' ),
					esc_html( $this->active_conflict )
				)
			)
		);
	}
}
