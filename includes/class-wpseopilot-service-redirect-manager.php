<?php
/**
 * Redirect manager with custom storage + frontend hook.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Redirect controller.
 */
class Redirect_Manager {

	/**
	 * Cache settings shared with CLI helpers.
	 */
	public const CACHE_GROUP     = 'wpseopilot_redirects';
	public const CACHE_KEY_ADMIN = 'redirect_manager_admin_list';
	public const CACHE_KEY_CLI   = 'redirect_manager_cli_list';
	public const CACHE_TTL       = 30;

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
		$this->table = $wpdb->prefix . 'wpseopilot_redirects';
	}

	/**
	 * Flush redirect caches.
	 *
	 * @return void
	 */
	public static function flush_cache() {
		wp_cache_delete( self::CACHE_KEY_ADMIN, self::CACHE_GROUP );
		wp_cache_delete( self::CACHE_KEY_CLI, self::CACHE_GROUP );
	}

	/**
	 * Redirect manager admin page URL.
	 *
	 * @return string
	 */
	private function get_admin_redirect_url() {
		return admin_url( 'admin.php?page=wpseopilot-redirects' );
	}

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( '1' !== get_option( 'wpseopilot_enable_redirect_manager', '1' ) ) {
			return;
		}

		if ( ! apply_filters( 'wpseopilot_feature_toggle', true, 'redirects' ) ) {
			return;
		}

		add_action( 'template_redirect', [ $this, 'maybe_redirect' ], 0 );
		add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_post_wpseopilot_save_redirect', [ $this, 'handle_save' ] );
		add_action( 'admin_post_wpseopilot_delete_redirect', [ $this, 'handle_delete' ] );
	}

	/**
	 * Create DB tables on activation.
	 *
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$charset = $wpdb->get_charset_collate();
		$sql     = "CREATE TABLE {$this->table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			source varchar(255) NOT NULL,
			target varchar(255) NOT NULL,
			status_code int(3) NOT NULL DEFAULT 301,
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			last_hit datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY source (source)
		) $charset;";

		dbDelta( $sql );
	}

	/**
	 * Register admin UI.
	 *
	 * @return void
	 */
	public function register_menu() {
		add_submenu_page(
			'wpseopilot',
			__( 'Redirect Manager', 'wp-seo-pilot' ),
			__( 'Redirects', 'wp-seo-pilot' ),
			'manage_options',
			'wpseopilot-redirects',
			[ $this, 'render_page' ]
		);
	}

	/**
	 * Render redirects list + form.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		global $wpdb;
		$redirects = wp_cache_get( self::CACHE_KEY_ADMIN, self::CACHE_GROUP );

		if ( false === $redirects ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Custom redirect table listing requires a direct query. Results are cached just below.
			$redirects = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$this->table} ORDER BY id DESC LIMIT %d",
					200
				)
			);

			wp_cache_set( self::CACHE_KEY_ADMIN, $redirects, self::CACHE_GROUP, self::CACHE_TTL );
		}

		include WPSEOPILOT_PATH . 'templates/redirects.php';
	}

	/**
	 * Handle save request.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_redirect' );

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';
		$status = isset( $_POST['status_code'] ) ? absint( $_POST['status_code'] ) : 301;

		if ( empty( $source ) || empty( $target ) ) {
			$redirect_url = wp_get_referer();
			$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();
			wp_safe_redirect( $redirect_url );
			exit;
		}

		global $wpdb;
		$normalized = '/' . ltrim( $source, '/' );
		$normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Writing to the custom redirects table requires a direct query.
		$wpdb->insert(
			$this->table,
			[
				'source'      => $normalized ?: '/',
				'target'      => $target,
				'status_code' => in_array( $status, [ 301, 302, 307, 410 ], true ) ? $status : 301,
			],
			[ '%s', '%s', '%d' ]
		);

		self::flush_cache();

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle delete request.
	 *
	 * @return void
	 */
	public function handle_delete() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_redirect_delete' );

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $id ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting rows from the custom redirects table requires a direct query.
			$wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
		}

		self::flush_cache();

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Maybe perform redirect based on stored rules.
	 *
	 * @return void
	 */
	public function maybe_redirect() {
		if ( is_admin() ) {
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

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Each request URI must be checked directly against the redirect table.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT * FROM ' . $this->table . ' WHERE source = %s LIMIT 1',
				$request
			)
		);

		if ( ! $row ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating redirect hit metadata requires manipulating the custom table directly.
		$wpdb->update(
			$this->table,
			[
				'hits'     => (int) $row->hits + 1,
				'last_hit' => current_time( 'mysql' ),
			],
			[ 'id' => $row->id ]
		);

		$target = esc_url_raw( $row->target );
		add_filter(
			'allowed_redirect_hosts',
			static function ( $hosts ) use ( $target ) {
				$host = wp_parse_url( $target, PHP_URL_HOST );
				if ( $host && ! in_array( $host, $hosts, true ) ) {
					$hosts[] = $host;
				}
				return $hosts;
			}
		);

		wp_safe_redirect( $target, (int) $row->status_code );
		exit;
	}

	/**
	 * Expose table name for WP-CLI.
	 *
	 * @return string
	 */
	public function get_table() {
		return $this->table;
	}
}
