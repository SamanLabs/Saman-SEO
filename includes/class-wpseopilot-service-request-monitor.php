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

		global $wpdb;
		$rows = $wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY hits DESC LIMIT 100" );

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

		$request = wp_parse_url( $_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH );
		if ( ! $request ) {
			$request = '/';
		}
		if ( '/' !== $request ) {
			$request = rtrim( $request, '/' );
			if ( '' === $request ) {
				$request = '/';
			}
		}
		$ref     = isset( $_SERVER['HTTP_REFERER'] ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ) : '';
		$hash    = $ref ? hash( 'sha256', $ref ) : '';

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE request_uri = %s LIMIT 1",
				$request
			)
		);

		if ( $row ) {
			$wpdb->update(
				$this->table,
				[
					'hits'     => (int) $row->hits + 1,
					'last_seen'=> current_time( 'mysql' ),
				],
				[ 'id' => $row->id ]
			);
		} else {
			$wpdb->insert(
				$this->table,
				[
					'request_uri'  => $request,
					'referrer_hash'=> $hash,
					'last_seen'    => current_time( 'mysql' ),
				],
				[ '%s', '%s', '%s' ]
			);
		}
	}
}
