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
		add_action( 'admin_post_wpseopilot_dismiss_slug', [ $this, 'handle_dismiss_slug' ] );

		// Slug change detection.
		add_action( 'post_updated', [ $this, 'detect_slug_change' ], 10, 3 );
		add_action( 'admin_notices', [ $this, 'render_slug_change_notice' ] );
		add_action( 'wp_ajax_wpseopilot_create_automatic_redirect', [ $this, 'ajax_create_redirect' ] );
	}

	/**
	 * Detect if a post slug has changed and store a transient to prompt the user.
	 *
	 * @param int      $post_id     Post ID.
	 * @param \WP_Post $post_after  Post object after update.
	 * @param \WP_Post $post_before Post object before update.
	 *
	 * @return void
	 */
	public function detect_slug_change( $post_id, $post_after, $post_before ) {
		// Only check for published posts.
		if ( 'publish' !== $post_after->post_status || 'publish' !== $post_before->post_status ) {
			return;
		}

		// Check if slug changed.
		if ( $post_after->post_name === $post_before->post_name ) {
			return;
		}

		// Don't trigger on post type changes, revisions, etc.
		if ( $post_after->post_type !== $post_before->post_type ) {
			return;
		}

		$start_slug = $post_before->post_name;
		$end_slug   = $post_after->post_name;

		// Calculate relative paths.
		// We can't rely solely on get_permalink() here because it might already reflect the new slug,
		// or complex permalink structures.
		// However, for the purpose of the redirect, we need the *old* URL path.
		// The most reliable way for the OLD path is to assume the same structure but with the old name.
		// But get_permalink($post_id) will return the NEW permalink.
		// Let's try to construct the old permalink by replacing the new slug with the old one in the new permalink.
		// This handles most standard permalink structures.

		$new_url = get_permalink( $post_id );
		$old_url = str_replace( $end_slug, $start_slug, $new_url );

		// Normalize to paths.
		$source = wp_parse_url( $old_url, PHP_URL_PATH );
		$target = wp_parse_url( $new_url, PHP_URL_PATH ); // Or full URL? The existing manager supports full URLs in 'target'.

		if ( ! $source || ! $target ) {
			return;
		}

		// Store in transient for the current user.
		$user_id = get_current_user_id();
		set_transient(
			'wpseopilot_slug_changed_' . $user_id,
			[
				'post_id' => $post_id,
				'old_url' => $source,
				'new_url' => $new_url, // Use full URL for target as per existing redirect logic preference often.
			],
			60
		);

		// Also store in persistent option for the "Recommended Redirects" list.
		$suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
		$key         = md5( $source ); // Use hash of source as key to avoid special char issues in keys.
		
		$suggestions[ $key ] = [
			'source'  => $source,
			'target'  => $new_url,
			'post_id' => $post_id,
			'date'    => current_time( 'mysql' ),
		];

		update_option( 'wpseopilot_monitor_slugs', $suggestions );
	}

	/**
	 * Render admin notice if a slug change was detected.
	 *
	 * @return void
	 */
	public function render_slug_change_notice() {
		$screen = get_current_screen();
		if ( $screen && $screen->is_block_editor() ) {
			return;
		}

		$user_id = get_current_user_id();
		$data    = get_transient( 'wpseopilot_slug_changed_' . $user_id );

		if ( ! $data ) {
			return;
		}

		// Clear it immediately effectively (or keep it until dismissed? Better to keep until page reload or action).
		// We'll delete it in the AJAX handler or let it expire.
		// Actually, if we don't delete different page loads might show it.
		// Let's delete it NOW so it only shows once.
		delete_transient( 'wpseopilot_slug_changed_' . $user_id );

		?>
		<div class="notice notice-info is-dismissible wpseopilot-slug-notice">
			<p>
				<?php
				printf(
					/* translators: 1: Old path, 2: New path */
					esc_html__( 'We noticed the post slug changed from %1$s to %2$s. Would you like to create a redirect?', 'wp-seo-pilot' ),
					'<strong>' . esc_html( $data['old_url'] ) . '</strong>',
					'<strong>' . esc_html( wp_parse_url( $data['new_url'], PHP_URL_PATH ) ) . '</strong>'
				);
				?>
			</p>
			<p>
				<button type="button" 
					class="button button-primary wpseopilot-create-redirect-btn"
					data-source="<?php echo esc_attr( $data['old_url'] ); ?>"
					data-target="<?php echo esc_attr( $data['new_url'] ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'wpseopilot_create_redirect' ) ); ?>">
					<?php esc_html_e( 'Create Redirect', 'wp-seo-pilot' ); ?>
				</button>
			</p>
		</div>
		<?php
	}

	/**
	 * Create a new redirect programmatically.
	 *
	 * @param string $source      Source URL path.
	 * @param string $target      Target URL.
	 * @param int    $status_code HTTP status code (301, 302, 307, 410).
	 *
	 * @return int|\WP_Error Inserted redirect ID or WP_Error on failure.
	 */
	public function create_redirect( $source, $target, $status_code = 301 ) {
		if ( empty( $source ) || empty( $target ) ) {
			return new \WP_Error( 'invalid_data', __( 'Source and target are required.', 'wp-seo-pilot' ) );
		}

		global $wpdb;

		$normalized = '/' . ltrim( $source, '/' );
		$normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE source = %s", $normalized ) );

		if ( $exists ) {
			return new \WP_Error( 'redirect_exists', __( 'Redirect already exists.', 'wp-seo-pilot' ) );
		}

		$status_code = in_array( $status_code, [ 301, 302, 307, 410 ], true ) ? $status_code : 301;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert(
			$this->table,
			[
				'source'      => $normalized,
				'target'      => $target,
				'status_code' => $status_code,
			],
			[ '%s', '%s', '%d' ]
		);

		if ( ! $inserted ) {
			return new \WP_Error( 'db_error', __( 'Could not insert redirect into database.', 'wp-seo-pilot' ) );
		}

		self::flush_cache();

		// Cleanup from suggestions if exists.
		$suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
		$key         = md5( $normalized );
		if ( isset( $suggestions[ $key ] ) ) {
			unset( $suggestions[ $key ] );
			update_option( 'wpseopilot_monitor_slugs', $suggestions );
		}

		return $wpdb->insert_id;
	}

	/**
	 * AJAX handler to create the redirect.
	 *
	 * @return void
	 */
	public function ajax_create_redirect() {
		check_ajax_referer( 'wpseopilot_create_redirect', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';

		$result = $this->create_redirect( $source, $target );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Cleanup transient just in case it wasn't cleared by the render method (e.g. if we moved to dismissing manually).
		delete_transient( 'wpseopilot_slug_changed_' . get_current_user_id() );

		wp_send_json_success( __( 'Redirect created successfully.', 'wp-seo-pilot' ) );
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
			[ $this, 'render_page' ],
			11
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

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

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

		$result = $this->create_redirect( $source, $target, $status );

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();

		if ( is_wp_error( $result ) ) {
			// We could add an error query arg here to show admin notice.
			wp_safe_redirect( add_query_arg( 'error', urlencode( $result->get_error_message() ), $redirect_url ) );
			exit;
		}

		wp_safe_redirect( add_query_arg( 'updated', '1', $redirect_url ) );
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

			self::flush_cache();
		}

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Handle slug suggestion dismissal.
	 *
	 * @return void
	 */
	public function handle_dismiss_slug() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_dismiss_slug' );

		$key = isset( $_GET['key'] ) ? sanitize_text_field( $_GET['key'] ) : '';

		if ( $key ) {
			$suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
			if ( isset( $suggestions[ $key ] ) ) {
				unset( $suggestions[ $key ] );
				update_option( 'wpseopilot_monitor_slugs', $suggestions );
			}
		}

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
