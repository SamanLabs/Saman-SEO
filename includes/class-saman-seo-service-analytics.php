<?php

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Analytics Service
 *
 * Tracks anonymous usage data to help improve the plugin.
 *
 * Privacy measures:
 * - No cookies used (disableCookies)
 * - No personal data collected
 * - Visitor ID is hashed from site URL (not identifiable)
 * - Only tracks within plugin admin pages
 * - Can be disabled in Settings > Modules
 *
 * Data collected:
 * - Page views within the plugin
 * - Feature usage events (create redirect, generate AI content, etc.)
 * - Plugin version
 * - WordPress version (major.minor only)
 * - PHP version (major.minor only)
 *
 * Data NOT collected:
 * - IP addresses (anonymized by Matomo)
 * - Site URL or domain name
 * - Content of posts/pages
 * - API keys or credentials
 * - User emails or names
 */
class Analytics {

	private $matomo_url = 'https://matomo.builditdesign.com';
	private $site_id = 1;

	// Custom dimension IDs (configured in Matomo)
	const DIMENSION_PLUGIN_VERSION = 1;
	const DIMENSION_WP_VERSION = 2;
	const DIMENSION_PHP_VERSION = 3;
	const DIMENSION_INTERFACE = 4;

	public function boot() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_matomo_tracking' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_async_defer_attribute' ], 10, 2 );
		add_filter( 'wp_resource_hints', [ $this, 'add_resource_hints' ], 10, 2 );
	}

	/**
	 * Check if analytics tracking is enabled.
	 */
	public function is_enabled() {
		$enabled = \Saman\SEO\Helpers\module_enabled( 'analytics' );

		return apply_filters( 'SAMAN_SEO_analytics_enabled', $enabled );
	}

	/**
	 * Track plugin activation
	 */
	public static function track_activation() {
		$analytics = new self();
		if ( ! $analytics->is_enabled() ) {
			return;
		}

		update_option( 'SAMAN_SEO_track_activation', time() );
	}

	/**
	 * Get WordPress version (major.minor only for privacy)
	 */
	private function get_wp_version() {
		global $wp_version;
		$parts = explode( '.', $wp_version );
		return isset( $parts[0], $parts[1] ) ? $parts[0] . '.' . $parts[1] : $wp_version;
	}

	/**
	 * Get PHP version (major.minor only for privacy)
	 */
	private function get_php_version() {
		$parts = explode( '.', PHP_VERSION );
		return isset( $parts[0], $parts[1] ) ? $parts[0] . '.' . $parts[1] : PHP_VERSION;
	}

	/**
	 * Determine which interface is being used
	 */
	private function get_interface_type( $page ) {
		if ( strpos( $page, 'saman-seo-v2' ) !== false ) {
			return 'React';
		}
		return 'Legacy';
	}

	/**
	 * Enqueue Matomo tracking script
	 */
	public function enqueue_matomo_tracking( $hook ) {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) ) {
			return;
		}

		$page = sanitize_text_field( $_GET['page'] );

		if ( strpos( $page, 'saman-seo' ) === false && $page !== 'saman-seo' ) {
			return;
		}

		wp_enqueue_script(
			'saman-seo-matomo',
			$this->matomo_url . '/matomo.js',
			[],
			SAMAN_SEO_VERSION,
			true
		);

		$activation_time = get_option( 'SAMAN_SEO_track_activation', 0 );
		$page_name       = str_replace( 'saman-seo-', '', $page );
		$page_name       = str_replace( 'saman-seo', 'dashboard', $page_name );
		$page_name       = str_replace( 'v2-', '', $page_name ); // Clean up v2 prefix
		$page_title      = ucwords( str_replace( '-', ' ', $page_name ) );

		// Anonymous visitor ID based on site URL hash (not identifiable)
		$visitor_id = substr( md5( home_url() . get_current_user_id() ), 0, 16 );

		// Get version info
		$plugin_version = SAMAN_SEO_VERSION;
		$wp_version     = $this->get_wp_version();
		$php_version    = $this->get_php_version();
		$interface      = $this->get_interface_type( $page );

		$dim_plugin  = self::DIMENSION_PLUGIN_VERSION;
		$dim_wp      = self::DIMENSION_WP_VERSION;
		$dim_php     = self::DIMENSION_PHP_VERSION;
		$dim_ui      = self::DIMENSION_INTERFACE;

		$matomo_config = "
			var _paq = window._paq = window._paq || [];

			// Tracker configuration
			_paq.push(['setTrackerUrl', '{$this->matomo_url}/matomo.php']);
			_paq.push(['setSiteId', '{$this->site_id}']);
			_paq.push(['setVisitorId', '{$visitor_id}']);

			// Privacy settings
			_paq.push(['setDoNotTrack', false]);
			_paq.push(['disableCookies']);

			// Custom dimensions for version tracking
			_paq.push(['setCustomDimension', {$dim_plugin}, '{$plugin_version}']);
			_paq.push(['setCustomDimension', {$dim_wp}, '{$wp_version}']);
			_paq.push(['setCustomDimension', {$dim_php}, '{$php_version}']);
			_paq.push(['setCustomDimension', {$dim_ui}, '{$interface}']);

			// Page tracking
			_paq.push(['setCustomUrl', '" . admin_url( 'admin.php?page=' . esc_js( $page ) ) . "']);
			_paq.push(['setDocumentTitle', 'Saman SEO - {$page_title}']);
			_paq.push(['trackPageView']);

			// Enable features
			_paq.push(['enableLinkTracking']);
			_paq.push(['enableHeartBeatTimer']);

			// Debug flag for development
			window.SamanSEODebug = " . ( defined( 'WP_DEBUG' ) && WP_DEBUG ? 'true' : 'false' ) . ";

			if (window.SamanSEODebug) {
				console.log('Saman SEO Analytics: Initialized', {
					siteId: '{$this->site_id}',
					visitorId: '{$visitor_id}',
					version: '{$plugin_version}',
					interface: '{$interface}'
				});
			}
		";

		// Track activation event
		if ( $activation_time && ( time() - $activation_time ) < 300 ) {
			$matomo_config .= "
				_paq.push(['trackEvent', 'Plugin', 'Activate', '{$plugin_version}']);
			";
			delete_option( 'SAMAN_SEO_track_activation' );
		}

		wp_add_inline_script( 'saman-seo-matomo', $matomo_config, 'before' );
	}

	/**
	 * Add async/defer to Matomo script for performance
	 */
	public function add_async_defer_attribute( $tag, $handle ) {
		if ( 'saman-seo-matomo' !== $handle ) {
			return $tag;
		}
		return str_replace( ' src', ' defer async src', $tag );
	}

	/**
	 * Add preconnect hint for faster loading
	 */
	public function add_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = $this->matomo_url;
		}
		return $urls;
	}
}
