<?php
/**
 * Core plugin bootstrap.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot;

defined( 'ABSPATH' ) || exit;

/**
 * Main plugin orchestrator.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Registered subsystems.
	 *
	 * @var array<string,object>
	 */
	private $services = [];

	/**
	 * Retrieve singleton instance.
	 *
	 * @return Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Boot the plugin.
	 *
	 * @return void
	 */
	public function boot() {
		if ( did_action( 'wpseopilot_booted' ) ) {
			return;
		}

		$this->register( 'compatibility', new Service\Compatibility() );
		$this->register( 'settings', new Service\Settings() );
		$this->register( 'meta', new Service\Post_Meta() );
		$this->register( 'frontend', new Service\Frontend() );
		$this->register( 'jsonld', new Service\JsonLD() );
		$this->register( 'admin', new Service\Admin_UI() );
		$this->register( 'importer', new Service\Importers() );
		$this->register( 'redirects', new Service\Redirect_Manager() );
		$this->register( 'onboarding', new Service\Onboarding() );
		$this->register( 'audit', new Service\Audit() );
		$this->register( 'sitemap', new Service\Sitemap_Enhancer() );
		$this->register( 'robots', new Service\Robots_Manager() );
		$this->register( 'monitor', new Service\Request_Monitor() );
		$this->register( 'social_card', new Service\Social_Card_Generator() );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$this->register( 'cli', new Service\CLI() );
		}

		do_action( 'wpseopilot_booted', $this );
	}

	/**
	 * Store a service for later retrieval.
	 *
	 * @param string $key Identifier.
	 * @param object $service Service instance.
	 *
	 * @return void
	 */
	private function register( $key, $service ) {
		if ( method_exists( $service, 'boot' ) ) {
			$service->boot();
		}

		$this->services[ $key ] = $service;
	}

	/**
	 * Retrieve service by key.
	 *
	 * @param string $key ID.
	 *
	 * @return object|null
	 */
	public function get( $key ) {
		return $this->services[ $key ] ?? null;
	}

	/**
	 * Plugin activation handler.
	 *
	 * @return void
	 */
	public static function activate() {
		( new Service\Redirect_Manager() )->create_tables();
		( new Service\Request_Monitor() )->create_tables();

		add_option( 'wpseopilot_default_title_template', '%post_title% | %site_title%' );
		add_option( 'wpseopilot_default_meta_description', '' );
		add_option( 'wpseopilot_default_og_image', '' );
		add_option( 'wpseopilot_show_onboarding', '1' );
		add_option( 'wpseopilot_show_tour', '1' );

		flush_rewrite_rules();
	}

	/**
	 * Plugin deactivation handler.
	 *
	 * @return void
	 */
	public static function deactivate() {
		flush_rewrite_rules();
	}
}
