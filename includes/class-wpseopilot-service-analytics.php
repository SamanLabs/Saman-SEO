<?php

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

class Analytics {

	private $matomo_url = 'https://matomo.builditdesign.com';
	private $site_id = 1;

	public function boot() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_matomo_tracking' ] );
		add_filter( 'script_loader_tag', [ $this, 'add_async_defer_attribute' ], 10, 2 );
		add_filter( 'wp_resource_hints', [ $this, 'add_resource_hints' ], 10, 2 );
	}

	public function is_enabled() {
		$setting_enabled = '1' === get_option( 'wpseopilot_enable_analytics', '1' );
		return apply_filters( 'wpseopilot_analytics_enabled', $setting_enabled );
	}

	public static function track_activation() {
		$analytics = new self();
		if ( ! $analytics->is_enabled() ) {
			return;
		}

		update_option( 'wpseopilot_track_activation', time() );
	}

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

		if ( strpos( $page, 'wpseopilot' ) === false && $page !== 'wp-seo-pilot' ) {
			return;
		}

		wp_enqueue_script(
			'wpseopilot-matomo',
			$this->matomo_url . '/matomo.js',
			[],
			WPSEOPILOT_VERSION,
			true
		);

		$activation_time = get_option( 'wpseopilot_track_activation', 0 );
		$page_name = str_replace( 'wpseopilot-', '', $page );
		$page_name = str_replace( 'wpseopilot', 'dashboard', $page_name );
		$page_title = ucwords( str_replace( '-', ' ', $page_name ) );

		$visitor_id = substr( md5( home_url() . get_current_user_id() ), 0, 16 );

		$matomo_config = "
			var _paq = window._paq = window._paq || [];
			_paq.push(['setTrackerUrl', '{$this->matomo_url}/matomo.php']);
			_paq.push(['setSiteId', '{$this->site_id}']);
			_paq.push(['setVisitorId', '{$visitor_id}']);
			_paq.push(['setCustomUrl', '" . admin_url( 'admin.php?page=' . esc_js( $page ) ) . "']);
			_paq.push(['setDocumentTitle', 'WP SEO Pilot - {$page_title}']);
			_paq.push(['setDoNotTrack', false]);
			_paq.push(['disableCookies']);
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);
			_paq.push(['enableHeartBeatTimer']);
			console.log('WP SEO Pilot Analytics: Initialized', {
				siteId: '{$this->site_id}',
				visitorId: '{$visitor_id}',
				trackerUrl: '{$this->matomo_url}/matomo.php'
			});
		";

		if ( $activation_time && ( time() - $activation_time ) < 300 ) {
			$matomo_config .= "
				_paq.push(['trackEvent', 'Plugin', 'Activate', '" . WPSEOPILOT_VERSION . "']);
			";
			delete_option( 'wpseopilot_track_activation' );
		}

		wp_add_inline_script( 'wpseopilot-matomo', $matomo_config, 'before' );
	}

	public function add_async_defer_attribute( $tag, $handle ) {
		if ( 'wpseopilot-matomo' !== $handle ) {
			return $tag;
		}
		return str_replace( ' src', ' defer async src', $tag );
	}

	public function add_resource_hints( $urls, $relation_type ) {
		if ( 'preconnect' === $relation_type ) {
			$urls[] = $this->matomo_url;
		}
		return $urls;
	}


}
