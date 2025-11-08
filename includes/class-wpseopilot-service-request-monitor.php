<?php
/**
 * Logs 404s and surfaces suggestions.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Request monitoring service.
 */
class Request_Monitor {

	/**
	 * Cache settings for the admin report.
	 */
	private const CACHE_GROUP = 'wpseopilot_request_monitor';
	private const CACHE_KEY   = 'request_monitor_report';
	private const CACHE_TTL   = 60;

	/**
	 * Table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table = $wpdb->prefix . 'wpseopilot_404_log';
	}

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( '1' !== get_option( 'wpseopilot_enable_404_logging', '0' ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'maybe_log_404' ] );
		add_action( 'admin_menu', [ $this, 'register_page' ] );
	}

	/**
	 * Create required tables.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$this->table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_uri varchar(255) NOT NULL,
			referrer_hash varchar(64) DEFAULT '',
			hits bigint(20) unsigned NOT NULL DEFAULT 1,
			last_seen datetime NOT NULL,
			PRIMARY KEY (id),
			KEY request_uri (request_uri)
		) {$charset};";

		dbDelta( $sql );
	}

	/**
	 * Register admin report page.
	 *
	 * @return void
	 */
	public function register_page() {
		add_submenu_page(
			'wpseopilot',
			__( '404 Monitor', 'wp-seo-pilot' ),
			__( '404 Monitor', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-404',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render 404 report.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$rows = wp_cache_get( self::CACHE_KEY, self::CACHE_GROUP );

		if ( false === $rows ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Totalling custom 404 log entries requires a direct query. Results are cached just below.
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} ORDER BY hits DESC LIMIT %d",
					100
				)
			);

			wp_cache_set( self::CACHE_KEY, $rows, self::CACHE_GROUP, self::CACHE_TTL );
		}

		include WPSEOPILOT_PATH . 'templates/404-log.php';
	}

	/**
	 * Maybe log a 404 event.
	 *
	 * @return void
	 */
	public function maybe_log_404() {
		if ( ! is_404() ) {
			return;
		}

		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';
		$request     = wp_parse_url( $request_uri, PHP_URL_PATH );
		if ( $request ) {
			$request = sanitize_text_field( $request );
		} else {
			$request = '/';
		}
		if ( '/' !== $request ) {
			$request = rtrim( $request, '/' );
			if ( '' === $request ) {
				$request = '/';
			}
		}
		$ref     = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$hash    = $ref ? hash( 'sha256', $ref ) : '';

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Each request URI must be checked against the custom 404 log table directly.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->table . ' WHERE request_uri = %s LIMIT 1',
				$request
			)
		);

		if ( $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating the custom 404 log table requires a direct query.
			$wpdb->update(
				$this->table,
				[
					'hits'     => (int) $row->hits + 1,
					'last_seen' => current_time( 'mysql' ),
				],
				[ 'id' => $row->id ]
			);

			$this->flush_cache();
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserting new 404 rows requires writing to the custom table directly.
			$wpdb->insert(
				$this->table,
				[
					'request_uri'  => $request,
					'referrer_hash' => $hash,
					'last_seen'     => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s' ]
			);

			$this->flush_cache();
		}
	}

	/**
	 * Clear cached report data.
	 *
	 * @return void
	 */
	private function flush_cache() {
		wp_cache_delete( self::CACHE_KEY, self::CACHE_GROUP );
	}
}
