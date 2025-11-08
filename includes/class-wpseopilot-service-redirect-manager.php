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
		$redirects = $wpdb->get_results( "SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 200" );

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
			wp_redirect( wp_get_referer() );
			exit;
		}

		global $wpdb;
		$normalized = '/' . ltrim( $source, '/' );
		$normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );

		$wpdb->insert(
			$this->table,
			[
				'source'      => $normalized ?: '/',
				'target'      => $target,
				'status_code' => in_array( $status, [ 301, 302, 307, 410 ], true ) ? $status : 301,
			],
			[ '%s', '%s', '%d' ]
		);

		wp_redirect( wp_get_referer() );
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
			$wpdb->delete( $this->table, [ 'id' => $id ], [ '%d' ] );
		}

		wp_redirect( wp_get_referer() );
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

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$this->table} WHERE source = %s LIMIT 1",
				$request
			)
		);

		if ( ! $row ) {
			return;
		}

		$wpdb->update(
			$this->table,
			[
				'hits'     => (int) $row->hits + 1,
				'last_hit' => current_time( 'mysql' ),
			],
			[ 'id' => $row->id ]
		);

		wp_redirect( esc_url_raw( $row->target ), (int) $row->status_code );
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
