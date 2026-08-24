<?php
/**
 * Redirect manager with custom storage + frontend hook.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Redirect controller.
 */
class Redirect_Manager {

	/**
	 * Schema version for migrations.
	 */
	public const SCHEMA_VERSION = 2;

	/**
	 * Cache settings shared with CLI helpers.
	 */
	public const CACHE_GROUP     = 'SAMAN_SEO_redirects';
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
		$this->table = $wpdb->prefix . 'SAMAN_SEO_redirects';
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
		return admin_url( 'admin.php?page=saman-seo-redirects' );
	}

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		// Keep the schema current on any boot, like the other table-backed
		// services, rather than only on the activation hook.
		$this->maybe_upgrade_schema();

		if ( ! \Saman\SEO\Helpers\module_enabled( 'redirects' ) ) {
			return;
		}

		if ( ! saman_seo_apply_filters( 'saman_seo_feature_toggle', true, 'redirects' ) ) {
			return;
		}

		add_action( 'template_redirect', array( $this, 'maybe_redirect' ), 0 );
		add_action( 'admin_post_SAMAN_SEO_save_redirect', array( $this, 'handle_save' ) );
		add_action( 'admin_post_SAMAN_SEO_delete_redirect', array( $this, 'handle_delete' ) );
		add_action( 'admin_post_SAMAN_SEO_dismiss_slug', array( $this, 'handle_dismiss_slug' ) );

		// Slug change detection.
		add_action( 'post_updated', array( $this, 'detect_slug_change' ), 10, 3 );
		add_action( 'wp_trash_post', array( $this, 'detect_trash_redirect' ), 10, 1 );
		add_action( 'admin_notices', array( $this, 'render_slug_change_notice' ) );
		add_action( 'wp_ajax_SAMAN_SEO_create_automatic_redirect', array( $this, 'ajax_create_redirect' ) );
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

		// Respect monitored post types setting.
		$monitored = get_option( 'SAMAN_SEO_redirect_monitor_post_types', array( 'post', 'page' ) );
		if ( ! in_array( $post_after->post_type, (array) $monitored, true ) ) {
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
			'SAMAN_SEO_slug_changed_' . $user_id,
			array(
				'post_id' => $post_id,
				'old_url' => $source,
				'new_url' => $new_url, // Use full URL for target as per existing redirect logic preference often.
			),
			60
		);

		// Also store in persistent option for the "Recommended Redirects" list.
		$suggestions = get_option( 'SAMAN_SEO_monitor_slugs', array() );
		$key         = md5( $source ); // Use hash of source as key to avoid special char issues in keys.

		$suggestions[ $key ] = array(
			'source'  => $source,
			'target'  => $new_url,
			'post_id' => $post_id,
			'date'    => current_time( 'mysql' ),
		);

		update_option( 'SAMAN_SEO_monitor_slugs', $suggestions );
	}

	/**
	 * Detect when a monitored post is trashed and store a suggestion.
	 *
	 * @param int $post_id Post ID.
	 * @return void
	 */
	public function detect_trash_redirect( $post_id ) {
		if ( ! get_option( 'SAMAN_SEO_redirect_monitor_trash', '0' ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post || 'trash' !== $post->post_status ) {
			return;
		}

		$monitored = get_option( 'SAMAN_SEO_redirect_monitor_post_types', array( 'post', 'page' ) );
		if ( ! in_array( $post->post_type, (array) $monitored, true ) ) {
			return;
		}

		$permalink = get_permalink( $post_id );
		$source    = wp_parse_url( $permalink, PHP_URL_PATH );
		if ( ! $source ) {
			return;
		}

		$target = home_url( '/' );
		if ( $post->post_parent ) {
			$parent_url  = get_permalink( $post->post_parent );
			$parent_path = wp_parse_url( $parent_url, PHP_URL_PATH );
			if ( $parent_path ) {
				$target = $parent_url;
			}
		}

		$suggestions = get_option( 'SAMAN_SEO_monitor_slugs', array() );
		$key         = md5( $source );

		$suggestions[ $key ] = array(
			'source'  => $source,
			'target'  => $target,
			'post_id' => $post_id,
			'date'    => current_time( 'mysql' ),
		);

		update_option( 'SAMAN_SEO_monitor_slugs', $suggestions );
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
		$data    = get_transient( 'SAMAN_SEO_slug_changed_' . $user_id );

		if ( ! $data ) {
			return;
		}

		// Clear it immediately effectively (or keep it until dismissed? Better to keep until page reload or action).
		// We'll delete it in the AJAX handler or let it expire.
		// Actually, if we don't delete different page loads might show it.
		// Let's delete it NOW so it only shows once.
		delete_transient( 'SAMAN_SEO_slug_changed_' . $user_id );

		?>
		<div class="notice notice-info is-dismissible saman-seo-slug-notice">
			<p>
				<?php
				printf(
					/* translators: 1: Old path, 2: New path */
					esc_html__( 'We noticed the post slug changed from %1$s to %2$s. Would you like to create a redirect?', 'saman-seo' ),
					'<strong>' . esc_html( $data['old_url'] ) . '</strong>',
					'<strong>' . esc_html( wp_parse_url( $data['new_url'], PHP_URL_PATH ) ) . '</strong>'
				);
				?>
			</p>
			<p>
				<button type="button" 
					class="button button-primary saman-seo-create-redirect-btn"
					data-source="<?php echo esc_attr( $data['old_url'] ); ?>"
					data-target="<?php echo esc_attr( $data['new_url'] ); ?>"
					data-nonce="<?php echo esc_attr( wp_create_nonce( 'SAMAN_SEO_create_redirect' ) ); ?>">
					<?php esc_html_e( 'Create Redirect', 'saman-seo' ); ?>
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
	 * @param array  $extra       Optional extra fields (is_regex, group_name, start_date, end_date, notes).
	 *
	 * @return int|\WP_Error Inserted redirect ID or WP_Error on failure.
	 */
	public function create_redirect( $source, $target, $status_code = 301, $extra = array() ) {
		if ( empty( $source ) ) {
			return new \WP_Error( 'invalid_data', __( 'Source is required.', 'saman-seo' ) );
		}

		if ( empty( $target ) ) {
			$target = $this->auto_generate_target();
			if ( empty( $target ) ) {
				return new \WP_Error( 'invalid_data', __( 'Target is required when auto-generate URL is not configured.', 'saman-seo' ) );
			}
		}

		global $wpdb;

		$is_regex   = ! empty( $extra['is_regex'] );
		$normalized = $source;

		// Only normalize non-regex sources.
		if ( ! $is_regex ) {
			$normalized = '/' . ltrim( $source, '/' );
			$normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );
		}

		// Validate regex pattern if applicable.
		if ( $is_regex ) {
			$validation = $this->validate_regex( $source );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name safe, checking redirect existence requires direct query.
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE source = %s", $normalized ) );

		if ( $exists ) {
			return new \WP_Error( 'redirect_exists', __( 'Redirect already exists.', 'saman-seo' ) );
		}

		$status_code = in_array( $status_code, array( 301, 302, 307, 410 ), true ) ? $status_code : 301;

		$data = array(
			'source'      => $normalized,
			'target'      => $target,
			'status_code' => $status_code,
			'is_regex'    => $is_regex ? 1 : 0,
			'group_name'  => isset( $extra['group_name'] ) ? sanitize_text_field( $extra['group_name'] ) : '',
			'start_date'  => ! empty( $extra['start_date'] ) ? sanitize_text_field( $extra['start_date'] ) : null,
			'end_date'    => ! empty( $extra['end_date'] ) ? sanitize_text_field( $extra['end_date'] ) : null,
			'notes'       => isset( $extra['notes'] ) ? sanitize_textarea_field( $extra['notes'] ) : null,
			'created_at'  => current_time( 'mysql' ),
		);

		$formats = array( '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s', '%s' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$inserted = $wpdb->insert( $this->table, $data, $formats );

		if ( ! $inserted ) {
			return new \WP_Error( 'db_error', __( 'Could not insert redirect into database.', 'saman-seo' ) );
		}

		self::flush_cache();

		// Cleanup from suggestions if exists.
		$suggestions = get_option( 'SAMAN_SEO_monitor_slugs', array() );
		$key         = md5( $normalized );
		if ( isset( $suggestions[ $key ] ) ) {
			unset( $suggestions[ $key ] );
			update_option( 'SAMAN_SEO_monitor_slugs', $suggestions );
		}

		return $wpdb->insert_id;
	}

	/**
	 * Update an existing redirect.
	 *
	 * @param int   $id   Redirect ID.
	 * @param array $data Data to update.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function update_redirect( $id, $data ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new \WP_Error( 'invalid_id', __( 'Invalid redirect ID.', 'saman-seo' ) );
		}

		// Check if redirect exists.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE id = %d", $id ) );
		if ( ! $exists ) {
			return new \WP_Error( 'not_found', __( 'Redirect not found.', 'saman-seo' ) );
		}

		$update  = array();
		$formats = array();

		// Source.
		if ( isset( $data['source'] ) ) {
			$is_regex   = ! empty( $data['is_regex'] );
			$normalized = $data['source'];

			if ( ! $is_regex ) {
				$normalized = '/' . ltrim( $data['source'], '/' );
				$normalized = '/' === $normalized ? '/' : rtrim( $normalized, '/' );
			}

			// Validate regex if applicable.
			if ( $is_regex ) {
				$validation = $this->validate_regex( $data['source'] );
				if ( is_wp_error( $validation ) ) {
					return $validation;
				}
			}

			// Check if new source conflicts with another redirect.
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$conflict = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$this->table} WHERE source = %s AND id != %d", $normalized, $id ) );
			if ( $conflict ) {
				return new \WP_Error( 'redirect_exists', __( 'Another redirect with this source already exists.', 'saman-seo' ) );
			}

			$update['source'] = $normalized;
			$formats[]        = '%s';
		}

		// Target.
		if ( isset( $data['target'] ) ) {
			$update['target'] = esc_url_raw( $data['target'] );
			$formats[]        = '%s';
		}

		// Status code.
		if ( isset( $data['status_code'] ) ) {
			$status_code           = absint( $data['status_code'] );
			$update['status_code'] = in_array( $status_code, array( 301, 302, 307, 410 ), true ) ? $status_code : 301;
			$formats[]             = '%d';
		}

		// Is regex.
		if ( isset( $data['is_regex'] ) ) {
			$update['is_regex'] = ! empty( $data['is_regex'] ) ? 1 : 0;
			$formats[]          = '%d';
		}

		// Group name.
		if ( isset( $data['group_name'] ) ) {
			$update['group_name'] = sanitize_text_field( $data['group_name'] );
			$formats[]            = '%s';
		}

		// Start date.
		if ( array_key_exists( 'start_date', $data ) ) {
			$update['start_date'] = ! empty( $data['start_date'] ) ? sanitize_text_field( $data['start_date'] ) : null;
			$formats[]            = '%s';
		}

		// End date.
		if ( array_key_exists( 'end_date', $data ) ) {
			$update['end_date'] = ! empty( $data['end_date'] ) ? sanitize_text_field( $data['end_date'] ) : null;
			$formats[]          = '%s';
		}

		// Notes.
		if ( isset( $data['notes'] ) ) {
			$update['notes'] = sanitize_textarea_field( $data['notes'] );
			$formats[]       = '%s';
		}

		if ( empty( $update ) ) {
			return new \WP_Error( 'no_data', __( 'No data to update.', 'saman-seo' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->update( $this->table, $update, array( 'id' => $id ), $formats, array( '%d' ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Could not update redirect.', 'saman-seo' ) );
		}

		self::flush_cache();

		return true;
	}

	/**
	 * Get a single redirect by ID.
	 *
	 * @param int $id Redirect ID.
	 *
	 * @return object|null Redirect object or null if not found.
	 */
	public function get_redirect( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$this->table} WHERE id = %d", absint( $id ) ) );
	}

	/**
	 * Delete a redirect by ID.
	 *
	 * @param int $id Redirect ID.
	 *
	 * @return bool|\WP_Error True on success, WP_Error on failure.
	 */
	public function delete_redirect( $id ) {
		global $wpdb;

		$id = absint( $id );
		if ( ! $id ) {
			return new \WP_Error( 'invalid_id', __( 'Invalid redirect ID.', 'saman-seo' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );

		if ( false === $result ) {
			return new \WP_Error( 'db_error', __( 'Could not delete redirect.', 'saman-seo' ) );
		}

		self::flush_cache();

		return true;
	}

	/**
	 * Validate a regex pattern.
	 *
	 * @param string $pattern Regex pattern to validate.
	 *
	 * @return true|\WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_regex( $pattern ) {
		if ( empty( $pattern ) ) {
			return new \WP_Error( 'empty_pattern', __( 'Regex pattern cannot be empty.', 'saman-seo' ) );
		}

		$pattern = (string) $pattern;
		$message = '';

		// Temporarily silence warnings without using @ so we can surface a
		// clean error. PHP 8 throws ValueError for invalid patterns, PHP 7
		// emits warnings and returns false.
		$previous = set_error_handler(
			static function ( $severity, $err_message ) use ( &$message ) {
				$message = $err_message;
				return true;
			}
		);

		try {
			$result = preg_match( '#' . $pattern . '#', '' );
		} catch ( \Throwable $e ) {
			$result  = false;
			$message = $e->getMessage();
		} finally {
			if ( false !== $previous ) {
				restore_error_handler();
			}
		}

		if ( false === $result ) {
			if ( empty( $message ) ) {
				$message = preg_last_error_msg();
			}
			if ( empty( $message ) ) {
				$message = __( 'Invalid regex pattern. Please check the syntax.', 'saman-seo' );
			}
			return new \WP_Error( 'invalid_regex', $message );
		}

		return true;
	}

	/**
	 * Validate a redirect before it is saved.
	 *
	 * Checks for loops, multi-hop chains, and targets that do not resolve to
	 * live local content (a redirect to a 404 is worse than no redirect).
	 *
	 * @param string     $source      Source path.
	 * @param string     $target      Target URL or path.
	 * @param int        $exclude_id  Rule ID to ignore (when editing).
	 * @param bool|null  $is_regex    Whether the source is a regex pattern.
	 * @param array|null $rules       Preloaded source=>rule map (avoids DB).
	 *
	 * @return array<int,array{type:string,severity:string,message:string,final?:string,hops?:int}>
	 */
	public function validate_redirect( $source, $target, $exclude_id = 0, $is_regex = null, array $rules = null ) {
		$warnings = array();

		$source = (string) $source;

		if ( null === $is_regex ) {
			$is_regex = false;
		}

		$normalized_source = $is_regex
			? $source
			: $this->normalize_source_path( $source );

		$target_path = $this->target_path( $target );

		// Direct loop: source equals target.
		if ( '' !== $target_path && $target_path === $normalized_source ) {
			$warnings[] = array(
				'type'     => 'loop',
				'severity' => 'high',
				'message'  => __( 'Source and target are the same URL. This will create an infinite redirect loop.', 'saman-seo' ),
			);
		}

		$rules = is_array( $rules ) ? $rules : $this->get_rules_map();

		// Walk the chain starting at the target.
		if ( '' !== $target_path ) {
			$walk = $this->walk_chain( $target_path, $rules, (int) $exclude_id );

			if ( ! empty( $walk['loop'] ) ) {
				$warnings[] = array(
					'type'     => 'loop',
					'severity' => 'high',
					'message'  => sprintf(
						// translators: %s is the path where the loop closes.
						__( 'This redirect closes a loop back to %s and will fail in browsers.', 'saman-seo' ),
						$walk['loop']
					),
				);
			} elseif ( $walk['hops'] > 0 ) {
				$warnings[] = array(
					'type'     => 'chain',
					'severity' => 'medium',
					'message'  => sprintf(
						// translators: 1: number of hops, 2: final destination.
						__( 'Target is itself redirected (%1$d hop(s)). Redirect directly to the final destination %2$s to avoid a chain.', 'saman-seo' ),
						$walk['hops'],
						$walk['final']
					),
					'hops'     => $walk['hops'],
					'final'    => $walk['final'],
				);
			}
		}

		// Target resolution: does anything actually live there?
		$resolution = $this->inspect_target( (string) $target );

		if ( 'not_found' === $resolution['type'] ) {
			$warnings[] = array(
				'type'     => 'dead_target',
				'severity' => 'high',
				'message'  => sprintf(
					// translators: %s is the target path.
					__( 'Target %s does not resolve to any published content. Redirecting to a 404 is worse than leaving the URL broken.', 'saman-seo' ),
					$resolution['path']
				),
			);
		} elseif ( 'unpublished' === $resolution['type'] ) {
			$warnings[] = array(
				'type'     => 'unpublished_target',
				'severity' => 'medium',
				'message'  => __( 'Target exists but is not published. Publish it or pick a different destination.', 'saman-seo' ),
			);
		} elseif ( 'external' === $resolution['type'] ) {
			$warnings[] = array(
				'type'     => 'external',
				'severity' => 'info',
				'message'  => __( 'Target points to an external site.', 'saman-seo' ),
			);
		}

		/**
		 * Filter the validation warnings for a redirect.
		 *
		 * @param array  $warnings   Warning list.
		 * @param string $source     Source path.
		 * @param string $target     Target URL.
		 * @param int    $exclude_id Rule being edited (0 when creating).
		 */
		return saman_seo_apply_filters( 'saman_seo_redirect_validation_warnings', $warnings, $source, $target, $exclude_id );
	}

	/**
	 * Walk a chain of redirects starting at a target path.
	 *
	 * Pure function: rules are supplied as a source-path => rule map so it
	 * is fully unit-testable.
	 *
	 * @param string               $target_path    Starting path.
	 * @param array<string,object> $rules_by_source Rules keyed by normalized source.
	 * @param int                  $exclude_id     Rule ID to ignore.
	 * @return array{hops:int,final:string,loop:string}
	 */
	public function walk_chain( $target_path, array $rules_by_source, $exclude_id = 0 ) {
		$hops    = 0;
		$visited = array();
		$loop_at = '';

		// Normalize both the cursor and the map keys so callers may supply
		// keys with or without trailing slashes.
		$map = array();

		foreach ( $rules_by_source as $key => $rule ) {
			$map[ $this->normalize_source_path( $key ) ] = $rule;
		}

		$current = $this->normalize_source_path( $target_path );

		while ( $hops < self::MAX_CHAIN_DEPTH ) {
			if ( isset( $visited[ $current ] ) ) {
				$loop_at = $current;
				break;
			}

			$visited[ $current ] = true;

			$rule = $map[ $current ] ?? null;

			if ( ! $rule || (int) $rule->id === (int) $exclude_id ) {
				break;
			}

			if ( ! empty( $rule->is_regex ) ) {
				// Regex hops cannot be resolved statically; stop walking.
				break;
			}

			$next = $this->target_path( $rule->target );

			if ( '' === $next ) {
				break;
			}

			$current = $next;
			++$hops;
		}

		return array(
			'hops'  => $hops,
			'final' => $current,
			'loop'  => $loop_at,
		);
	}

	/**
	 * Rewrite a rule's target to the final destination of its chain.
	 *
	 * @param int $id Rule ID.
	 * @return array{hops_removed:int,final:string}|\WP_Error
	 */
	public function flatten_chain( $id ) {
		$rule = $this->get_redirect( $id );

		if ( ! $rule ) {
			return new \WP_Error( 'not_found', __( 'Redirect not found.', 'saman-seo' ) );
		}

		$rules = $this->get_rules_map();
		$start = $this->target_path( $rule->target );

		if ( '' === $start ) {
			return new \WP_Error( 'nothing_to_flatten', __( 'Target is not a local path, nothing to flatten.', 'saman-seo' ) );
		}

		$walk = $this->walk_chain( $start, $rules, (int) $id );

		if ( $walk['hops'] < 1 || '' !== $walk['loop'] ) {
			return new \WP_Error(
				'nothing_to_flatten',
				__( 'No safe chain found to flatten (or the chain loops).', 'saman-seo' )
			);
		}

		// Preserve scheme/host when the original target was absolute-local.
		$new_target = $this->to_absolute_if_needed( $rule->target, $walk['final'] );

		$result = $this->update_redirect( $id, array( 'target' => $new_target ) );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return array(
			'hops_removed' => $walk['hops'],
			'final'        => $new_target,
		);
	}

	/**
	 * Scan all rules for problems: loops, chains, dead targets, and expired
	 * date windows.
	 *
	 * @param int $limit Maximum rules to inspect.
	 * @return array{issues:array<int,array>,scanned:int}
	 */
	public function health_check( $limit = 2000 ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name safe, health scan requires full table read.
		$rules = $wpdb->get_results(
			$wpdb->prepare( "SELECT * FROM {$this->table} ORDER BY id ASC LIMIT %d", absint( $limit ) )
		);

		$rules = is_array( $rules ) ? $rules : array();

		$map = array();
		foreach ( $rules as $rule ) {
			if ( empty( $rule->is_regex ) ) {
				$map[ $this->normalize_source_path( $rule->source ) ] = $rule;
			}
		}

		$issues  = array();
		$now_ymd = current_time( 'Ymd' );

		foreach ( $rules as $rule ) {
			$rule_issues = array();

			if ( empty( $rule->is_regex ) ) {
				$walk = $this->walk_chain( $this->target_path( $rule->target ), $map, (int) $rule->id );

				if ( '' !== $walk['loop'] ) {
					$rule_issues[] = array(
						'type'     => 'loop',
						'severity' => 'high',
						'message'  => sprintf(
							// translators: %s is the path where the loop closes.
							__( 'Chain loops back at %s.', 'saman-seo' ),
							$walk['loop']
						),
					);
				} elseif ( $walk['hops'] > 0 ) {
					$rule_issues[] = array(
						'type'     => 'chain',
						'severity' => 'medium',
						'message'  => sprintf(
							// translators: %s is the final destination.
							__( 'Chain of %1$d hop(s); final destination %2$s. Flatten to a single hop.', 'saman-seo' ),
							$walk['hops'],
							$walk['final']
						),
						'final'    => $walk['final'],
					);
				}

				$resolution = $this->inspect_target( (string) $rule->target );

				if ( 'not_found' === $resolution['type'] ) {
					$rule_issues[] = array(
						'type'     => 'dead_target',
						'severity' => 'high',
						'message'  => sprintf(
							// translators: %s is the target path.
							__( 'Target %s resolves to a 404.', 'saman-seo' ),
							$resolution['path']
						),
					);
				} elseif ( 'unpublished' === $resolution['type'] ) {
					$rule_issues[] = array(
						'type'     => 'unpublished_target',
						'severity' => 'medium',
						'message'  => __( 'Target is not published.', 'saman-seo' ),
					);
				}
			}

			// Date-window sanity.
			if ( ! empty( $rule->end_date ) ) {
				$end = (int) preg_replace( '/[^0-9]/', '', (string) $rule->end_date );

				if ( $end && $end < $now_ymd ) {
					$rule_issues[] = array(
						'type'     => 'expired',
						'severity' => 'low',
						'message'  => __( 'End date has passed; the rule is inert.', 'saman-seo' ),
					);
				}
			}

			if ( ! empty( $rule_issues ) ) {
				$issues[] = array(
					'id'          => (int) $rule->id,
					'source'      => $rule->source,
					'target'      => $rule->target,
					'status_code' => (int) $rule->status_code,
					'issues'      => $rule_issues,
				);
			}
		}

		return array(
			'issues'  => $issues,
			'scanned' => count( $rules ),
		);
	}

	/**
	 * Load all non-regex rules into a normalized source => rule map.
	 *
	 * @return array<string,object>
	 */
	public function get_rules_map() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name safe, chain analysis requires full table read.
		$rows = $wpdb->get_results( "SELECT id, source, target, is_regex FROM {$this->table}" );

		$map = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				if ( ! empty( $row->is_regex ) ) {
					continue;
				}

				$map[ $this->normalize_source_path( $row->source ) ] = $row;
			}
		}

		return $map;
	}

	/**
	 * Normalize a source path the same way create/update does.
	 *
	 * @param string $path Raw path.
	 * @return string Normalized path.
	 */
	private function normalize_source_path( $path ) {
		$normalized = '/' . ltrim( (string) $path, '/' );

		return '/' === $normalized ? '/' : rtrim( $normalized, '/' );
	}

	/**
	 * Extract and normalize the path portion of a target.
	 *
	 * @param string $target Target URL or path.
	 * @return string Normalized path, or empty string when not local/parseable.
	 */
	private function target_path( $target ) {
		$path = wp_parse_url( trim( (string) $target ), PHP_URL_PATH );

		// Plain relative paths parse without a host; parse_url returns the
		// string itself for "path" inputs. Missing path on an absolute URL
		// means the site root.
		if ( null === $path ) {
			$host = wp_parse_url( trim( (string) $target ), PHP_URL_HOST );

			return $host ? '/' : '';
		}

		$path = '/' . ltrim( (string) $path, '/' );

		return '/' === $path ? '/' : rtrim( $path, '/' );
	}

	/**
	 * Resolve where a target leads.
	 *
	 * @param string $target Target URL or path.
	 * @return array{type:string,path?:string,post_id?:int,url?:string}
	 */
	public function inspect_target( $target ) {
		$target = trim( (string) $target );

		if ( '' === $target ) {
			return array( 'type' => 'empty' );
		}

		$target_host = wp_parse_url( $target, PHP_URL_HOST );
		$home_host   = wp_parse_url( home_url(), PHP_URL_HOST );

		$same_host = null === $target_host
			|| $this->canonical_host( (string) $target_host ) === $this->canonical_host( (string) $home_host );

		if ( ! $same_host ) {
			return array(
				'type' => 'external',
				'url'  => $target,
			);
		}

		$path = $this->target_path( $target );

		if ( '' === $path ) {
			return array( 'type' => 'unknown' );
		}

		return $this->resolve_local_path( $path );
	}

	/**
	 * Resolve whether a local path maps to live content.
	 *
	 * @param string $path Normalized local path.
	 * @return array{type:string,path:string,post_id?:int}
	 */
	public function resolve_local_path( $path ) {
		if ( '/' === $path ) {
			return array(
				'type' => 'home',
				'path' => $path,
			);
		}

		// Strip pagination and feed suffixes before resolving the base.
		$base = (string) preg_replace( '#(/page/\d+|/feed/?|/comment-page-\d+)/?$#', '/', $path );
		$base = '/' === $base ? '/' : rtrim( $base, '/' );

		if ( '/' !== $base ) {
			// Posts page (blog archive).
			$posts_page_id = (int) get_option( 'page_for_posts' );

			if ( $posts_page_id ) {
				$posts_page_path = wp_parse_url( get_permalink( $posts_page_id ), PHP_URL_PATH );

				if ( $posts_page_path && $this->normalize_source_path( $posts_page_path ) === $base ) {
					return array(
						'type'    => 'post',
						'post_id' => $posts_page_id,
						'path'    => $base,
					);
				}
			}

			// Core post/lookup (posts, pages, CPT items).
			$post_id = url_to_postid( home_url( $base ) );

			if ( $post_id ) {
				$status = get_post_status( $post_id );

				return array(
					'type'    => in_array( $status, array( 'publish', 'inherit' ), true ) ? 'post' : 'unpublished',
					'post_id' => (int) $post_id,
					'path'    => $base,
				);
			}

			// Date archives: /YYYY/, /YYYY/MM/, /YYYY/MM/DD/.
			if ( preg_match( '#^/\d{4}(/\d{1,2}){0,2}/?$#', $base ) ) {
				return array(
					'type' => 'archive',
					'path' => $base,
				);
			}

			// Post type archives (compare against registered rewrite slugs).
			foreach ( get_post_types( array( 'has_archive' => true ), 'objects' ) as $post_type ) {
				$archive_slug = $post_type->has_archive;

				if ( true !== $archive_slug ) {
					$archive_slug = is_string( $archive_slug ) ? $archive_slug : $post_type->name;
				} else {
					$archive_slug = $post_type->name;
				}

				if ( trim( $archive_slug, '/' ) === trim( $base, '/' ) ) {
					return array(
						'type' => 'archive',
						'path' => $base,
					);
				}
			}

			// Taxonomy term archives: match the last path segment as a slug.
			$segments = array_filter( explode( '/', trim( $base, '/' ) ) );
			$slug     = end( $segments );

			if ( false !== $slug && '' !== $slug ) {
				$terms = get_terms(
					array(
						'slug'       => sanitize_title( $slug ),
						'hide_empty' => false,
						'fields'     => 'all',
						'number'     => 5,
					)
				);

				if ( ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						$link = get_term_link( $term );

						if ( ! is_wp_error( $link ) ) {
							$term_path = wp_parse_url( $link, PHP_URL_PATH );

							if ( $term_path && $this->normalize_source_path( $term_path ) === $base ) {
								return array(
									'type' => 'term',
									'path' => $base,
								);
							}
						}
					}
				}
			}

			$not_found = array(
				'type' => 'not_found',
				'path' => $base,
			);

			/**
			 * Filter the resolution of a local redirect target path.
			 *
			 * Lets integrations teach the validator about custom rewrite
			 * endpoints (e.g. virtual pages, REST-driven routes).
			 *
			 * @param array  $not_found Default resolution (type not_found).
			 * @param string $base      Normalized base path being resolved.
			 */
			return saman_seo_apply_filters( 'saman_seo_redirect_target_resolution', $not_found, $base );
		}

		return array(
			'type' => 'home',
			'path' => $base,
		);
	}

	/**
	 * Compare hosts without their www prefix.
	 *
	 * @param string $host Hostname.
	 * @return string Lowercase host without leading www.
	 */
	private function canonical_host( $host ) {
		return strtolower( preg_replace( '/^www\./i', '', trim( (string) $host ) ) );
	}

	/**
	 * Keep the absolute form of a target when the original was absolute.
	 *
	 * @param string $original_target Original rule target.
	 * @param string $final_path      Final normalized path from the chain walk.
	 * @return string Final target preserving the original absolute/relative form.
	 */
	private function to_absolute_if_needed( $original_target, $final_path ) {
		$original = trim( (string) $original_target );

		// Relative path targets stay relative.
		if ( null === wp_parse_url( $original, PHP_URL_HOST ) ) {
			return $final_path;
		}

		return home_url( $final_path );
	}

	/**
	 * Maximum hops followed when walking a redirect chain.
	 *
	 * @var int
	 */
	public const MAX_CHAIN_DEPTH = 10;

	/**
	 * AJAX handler to create the redirect.
	 *
	 * @return void
	 */
	public function ajax_create_redirect() {
		check_ajax_referer( 'SAMAN_SEO_create_redirect', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( __( 'Permission denied.', 'saman-seo' ) );
		}

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';

		$result = $this->create_redirect( $source, $target );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		}

		// Cleanup transient just in case it wasn't cleared by the render method (e.g. if we moved to dismissing manually).
		delete_transient( 'SAMAN_SEO_slug_changed_' . get_current_user_id() );

		wp_send_json_success( __( 'Redirect created successfully.', 'saman-seo' ) );
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
			target varchar(500) NOT NULL,
			status_code int(3) NOT NULL DEFAULT 301,
			hits bigint(20) unsigned NOT NULL DEFAULT 0,
			last_hit datetime DEFAULT NULL,
			is_regex tinyint(1) NOT NULL DEFAULT 0,
			group_name varchar(100) DEFAULT '',
			start_date datetime DEFAULT NULL,
			end_date datetime DEFAULT NULL,
			notes text DEFAULT NULL,
			created_at datetime DEFAULT NULL,
			PRIMARY KEY  (id),
			KEY source (source),
			KEY is_regex (is_regex),
			KEY group_name (group_name)
		) $charset;";

		dbDelta( $sql );

		// Run migrations for existing installations.
		$this->maybe_migrate_schema();
	}

	/**
	 * Recreate/upgrade the schema when the stored version is behind. Cheap on
	 * the common path: one option read and an integer comparison.
	 *
	 * @return void
	 */
	private function maybe_upgrade_schema() {
		if ( (int) get_option( 'SAMAN_SEO_redirects_schema_version', 0 ) < self::SCHEMA_VERSION ) {
			$this->create_tables();
		}
	}

	/**
	 * Check and run schema migrations if needed.
	 *
	 * @return void
	 */
	private function maybe_migrate_schema() {
		$current_version = (int) get_option( 'SAMAN_SEO_redirects_schema_version', 1 );

		if ( $current_version >= self::SCHEMA_VERSION ) {
			return;
		}

		global $wpdb;

		// Migration from version 1 to 2: Add new columns.
		if ( $current_version < 2 ) {
			$columns = $wpdb->get_col( "DESCRIBE {$this->table}", 0 ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

			// Add is_regex column if missing.
			if ( ! in_array( 'is_regex', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN is_regex tinyint(1) NOT NULL DEFAULT 0" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$this->table} ADD KEY is_regex (is_regex)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Add group_name column if missing.
			if ( ! in_array( 'group_name', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN group_name varchar(100) DEFAULT ''" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				$wpdb->query( "ALTER TABLE {$this->table} ADD KEY group_name (group_name)" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Add start_date column if missing.
			if ( ! in_array( 'start_date', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN start_date datetime DEFAULT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Add end_date column if missing.
			if ( ! in_array( 'end_date', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN end_date datetime DEFAULT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Add notes column if missing.
			if ( ! in_array( 'notes', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN notes text DEFAULT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Add created_at column if missing.
			if ( ! in_array( 'created_at', $columns, true ) ) {
				$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN created_at datetime DEFAULT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
				// Set created_at to current time for existing rows.
				$wpdb->query( $wpdb->prepare( "UPDATE {$this->table} SET created_at = %s WHERE created_at IS NULL", current_time( 'mysql' ) ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
			}

			// Extend target column to 500 chars if needed.
			$wpdb->query( "ALTER TABLE {$this->table} MODIFY COLUMN target varchar(500) NOT NULL" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		}

		update_option( 'SAMAN_SEO_redirects_schema_version', self::SCHEMA_VERSION );
	}

	/**
	 * Handle save request.
	 *
	 * @return void
	 */
	public function handle_save() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_redirect' );

		$source = isset( $_POST['source'] ) ? sanitize_text_field( wp_unslash( $_POST['source'] ) ) : '';
		$target = isset( $_POST['target'] ) ? esc_url_raw( wp_unslash( $_POST['target'] ) ) : '';
		$status = isset( $_POST['status_code'] ) ? absint( $_POST['status_code'] ) : 301;

		$result = $this->create_redirect( $source, $target, $status );

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : $this->get_admin_redirect_url();

		if ( is_wp_error( $result ) ) {
			// We could add an error query arg here to show admin notice.
			wp_safe_redirect( add_query_arg( 'error', rawurlencode( $result->get_error_message() ), $redirect_url ) );
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
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_redirect_delete' );

		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( $id ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Deleting rows from the custom redirects table requires a direct query.
			$wpdb->delete( $this->table, array( 'id' => $id ), array( '%d' ) );

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
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_dismiss_slug' );

		$key = isset( $_GET['key'] ) ? sanitize_text_field( wp_unslash( $_GET['key'] ) ) : '';

		if ( $key ) {
			$suggestions = get_option( 'SAMAN_SEO_monitor_slugs', array() );
			if ( isset( $suggestions[ $key ] ) ) {
				unset( $suggestions[ $key ] );
				update_option( 'SAMAN_SEO_monitor_slugs', $suggestions );
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

		$settings    = $this->get_redirect_settings();
		$request_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '/';

		$request_path  = wp_parse_url( $request_uri, PHP_URL_PATH );
		$request_path  = $request_path ? sanitize_text_field( $request_path ) : '/';
		$request_query = wp_parse_url( $request_uri, PHP_URL_QUERY );
		$request_query = $request_query ? $request_query : '';

		$request_path = $this->normalize_path( $request_path, $settings['ignore_trailing_slashes'] );
		$lookup_key   = $this->build_lookup_key( $request_path, $request_query, $settings );

		// Object-cache lookup.
		if ( $settings['object_cache'] ) {
			$cached = wp_cache_get( $lookup_key, self::CACHE_GROUP );
			if ( false !== $cached ) {
				if ( ! empty( $cached['row'] ) ) {
					$this->execute_redirect( $cached['row'], $cached['target'], $request_query, $settings );
				}
				return;
			}
		}

		global $wpdb;

		$row = $this->find_exact_redirect( $request_path, $request_query, $settings );
		if ( ! $row ) {
			$row = $this->find_regex_redirect( $request_path, $request_query, $settings );
		}

		if ( ! $row ) {
			if ( $settings['object_cache'] ) {
				wp_cache_set(
					$lookup_key,
					array(
						'row'    => null,
						'target' => null,
					),
					self::CACHE_GROUP,
					self::CACHE_TTL
				);
			}
			return;
		}

		$target = $this->resolve_target( $row, $request_path, $request_query, $settings );

		if ( $settings['object_cache'] ) {
			// Store a snapshot so backreference replacements are not cached.
			$cache_row = array(
				'id'          => (int) $row->id,
				'status_code' => (int) $row->status_code,
				'hits'        => (int) $row->hits,
			);
			wp_cache_set(
				$lookup_key,
				array(
					'row'    => $cache_row,
					'target' => $target,
				),
				self::CACHE_GROUP,
				self::CACHE_TTL
			);
		}

		$this->execute_redirect( $row, $target, $request_query, $settings );
	}

	/**
	 * Get redirect-related settings.
	 *
	 * @return array
	 */
	private function get_redirect_settings() {
		$query_matching = get_option( 'SAMAN_SEO_redirect_query_matching', 'ignore' );
		if ( ! in_array( $query_matching, array( 'exact', 'ignore', 'pass' ), true ) ) {
			$query_matching = 'ignore';
		}

		// Default the redirect object cache on when a persistent external object
		// cache is present (where it genuinely saves per-request queries); off
		// otherwise, since a non-persistent cache adds little and risks staleness.
		$object_cache_default = wp_using_ext_object_cache() ? '1' : '0';

		return array(
			'case_insensitive'        => ! empty( get_option( 'SAMAN_SEO_redirect_case_insensitive', '0' ) ),
			'ignore_trailing_slashes' => ! empty( get_option( 'SAMAN_SEO_redirect_ignore_trailing_slashes', '0' ) ),
			'query_matching'          => $query_matching,
			'cache_header_hours'      => (int) get_option( 'SAMAN_SEO_redirect_cache_header_hours', 1 ),
			'object_cache'            => ! empty( get_option( 'SAMAN_SEO_redirect_object_cache', $object_cache_default ) ),
		);
	}

	/**
	 * Normalize a URL path.
	 *
	 * @param string $path                 Path.
	 * @param bool   $ignore_trailing_slash Whether to strip trailing slash.
	 * @return string
	 */
	private function normalize_path( $path, $ignore_trailing_slash ) {
		$path = '/' . ltrim( $path, '/' );
		if ( $ignore_trailing_slash && '/' !== $path ) {
			$path = rtrim( $path, '/' );
		}
		if ( '' === $path ) {
			$path = '/';
		}
		return $path;
	}

	/**
	 * Normalize a query string for comparison.
	 *
	 * @param string $query Raw query string.
	 * @return string
	 */
	private function normalize_query( $query ) {
		if ( empty( $query ) ) {
			return '';
		}
		parse_str( $query, $params );
		if ( empty( $params ) ) {
			return '';
		}
		ksort( $params );
		return http_build_query( $params );
	}

	/**
	 * Build a cache / lookup key from request parts and settings.
	 *
	 * @param string $path     Normalized path.
	 * @param string $query    Raw query string.
	 * @param array  $settings Redirect settings.
	 * @return string
	 */
	private function build_lookup_key( $path, $query, $settings ) {
		if ( 'exact' === $settings['query_matching'] ) {
			$query = $this->normalize_query( $query );
			return $query ? $path . '?' . $query : $path;
		}
		return $path;
	}

	/**
	 * Find a non-regex redirect matching the request.
	 *
	 * @param string $path    Normalized request path.
	 * @param string $query   Raw request query string.
	 * @param array  $settings Redirect settings.
	 * @return object|null
	 */
	private function find_exact_redirect( $path, $query, $settings ) {
		global $wpdb;

		// Stored sources are always saved without a trailing slash (see
		// add_redirect()/update_redirect()), so mirror that here regardless of
		// the ignore_trailing_slashes setting. Otherwise a request for "/foo/"
		// never matches a redirect stored as "/foo" — the redirect appears to
		// exist yet never fires.
		$source_path = ( '/' === $path ) ? '/' : rtrim( $path, '/' );

		$lookup = 'exact' === $settings['query_matching'] && $query ? $source_path . '?' . $this->normalize_query( $query ) : $source_path;

		if ( $settings['case_insensitive'] ) {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe, built from $wpdb->prefix.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . $this->table . ' WHERE LOWER(source) = LOWER(%s) AND is_regex = 0 LIMIT 1',
					$lookup
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		} else {
			// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe, built from $wpdb->prefix.
			$row = $wpdb->get_row(
				$wpdb->prepare(
					'SELECT * FROM ' . $this->table . ' WHERE source = %s AND is_regex = 0 LIMIT 1',
					$lookup
				)
			);
			// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
		}

		if ( ! $row || ! $this->is_redirect_active( $row ) ) {
			return null;
		}

		return $row;
	}

	/**
	 * Find a regex redirect matching the request.
	 *
	 * @param string $path     Normalized request path.
	 * @param string $query    Raw request query string.
	 * @param array  $settings Redirect settings.
	 * @return object|null
	 */
	private function find_regex_redirect( $path, $query, $settings ) {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Table name is safe, built from $wpdb->prefix.
		$regex_redirects = $wpdb->get_results(
			'SELECT * FROM ' . $this->table . ' WHERE is_regex = 1'
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		if ( ! $regex_redirects ) {
			return null;
		}

		$lookup = 'exact' === $settings['query_matching'] && $query ? $path . '?' . $this->normalize_query( $query ) : $path;

		foreach ( $regex_redirects as $regex_row ) {
			if ( ! $this->is_redirect_active( $regex_row ) ) {
				continue;
			}

			$modifiers = $settings['case_insensitive'] ? 'i' : '';

			$matched = false;
			$matches = array();
			set_error_handler(
				static function () {
					return true;
				}
			);
			try {
				$matched = (bool) preg_match( '#' . $regex_row->source . '#' . $modifiers, $lookup, $matches );
			} catch ( \Throwable $e ) {
				$matched = false;
			} finally {
				restore_error_handler();
			}

			if ( $matched ) {
				$match_count = count( $matches );
				if ( $match_count > 1 ) {
					for ( $i = 1; $i < $match_count; $i++ ) {
						$regex_row->target = str_replace( '$' . $i, $matches[ $i ], $regex_row->target );
					}
				}
				return $regex_row;
			}
		}

		return null;
	}

	/**
	 * Resolve the final target URL for a matched redirect.
	 *
	 * @param object $row     Matched redirect row.
	 * @param string $path    Normalized request path.
	 * @param string $query   Raw request query string.
	 * @param array  $settings Redirect settings.
	 * @return string
	 */
	private function resolve_target( $row, $path, $query, $settings ) {
		$target = $row->target;

		if ( 'pass' === $settings['query_matching'] && $query ) {
			$target = $this->append_query_to_target( $target, $query );
		}

		return $target;
	}

	/**
	 * Merge a request query string into a target URL.
	 *
	 * @param string $target Target URL.
	 * @param string $query  Query string to append/merge.
	 * @return string
	 */
	private function append_query_to_target( $target, $query ) {
		if ( empty( $query ) ) {
			return $target;
		}

		parse_str( $query, $request_params );
		if ( empty( $request_params ) ) {
			return $target;
		}

		$target_query  = wp_parse_url( $target, PHP_URL_QUERY );
		$target_params = array();
		if ( $target_query ) {
			parse_str( $target_query, $target_params );
		}

		$merged = array_merge( $target_params, $request_params );
		$target = add_query_arg( $merged, $target );

		return $target;
	}

	/**
	 * Execute a matched redirect.
	 *
	 * @param object $row     Matched redirect row.
	 * @param string $target  Resolved target URL.
	 * @param string $query   Raw request query string.
	 * @param array  $settings Redirect settings.
	 * @return void
	 */
	private function execute_redirect( $row, $target, $query, $settings ) {
		$redirect    = is_array( $row ) ? $row : (array) $row;
		$status_code = (int) $redirect['status_code'];

		// Target-less status codes (410 Gone, 451).
		$targetless_statuses = array( 410, 451 );

		if ( ! $target && ! in_array( $status_code, $targetless_statuses, true ) ) {
			return;
		}

		global $wpdb;

		// Update hit stats.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$this->table,
			array(
				'hits'     => (int) $redirect['hits'] + 1,
				'last_hit' => current_time( 'mysql' ),
			),
			array( 'id' => $redirect['id'] )
		);

		// Bail if output has already started.
		if ( headers_sent() ) {
			return;
		}

		// Handle target-less statuses.
		if ( ! $target && in_array( $status_code, $targetless_statuses, true ) ) {
			status_header( $status_code );
			exit;
		}

		$target = esc_url_raw( $target );

		// Send Expires header for 301 redirects.
		if ( 301 === $status_code && $settings['cache_header_hours'] > 0 ) {
			$expires = gmdate( 'D, d M Y H:i:s', time() + ( $settings['cache_header_hours'] * HOUR_IN_SECONDS ) ) . ' GMT';
			header( 'Expires: ' . $expires );
			header( 'Cache-Control: max-age=' . ( $settings['cache_header_hours'] * HOUR_IN_SECONDS ) );
		}

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

		wp_safe_redirect( $target, $status_code );
		exit;
	}

	/**
	 * Auto-generate a target URL from the configured template.
	 *
	 * @return string
	 */
	private function auto_generate_target() {
		$template = get_option( 'SAMAN_SEO_redirect_auto_generate_url', '' );
		if ( empty( $template ) ) {
			return '';
		}

		$unique = wp_rand( 100000, 999999 );
		$target = str_replace( array( '$dec$', '$hex$' ), array( $unique, dechex( $unique ) ), $template );
		return esc_url_raw( $target );
	}

	/**
	 * Check if a timed redirect is currently active.
	 *
	 * @param object $redirect Redirect row object.
	 *
	 * @return bool True if active, false otherwise.
	 */
	public function is_redirect_active( $redirect ) {
		$now = current_time( 'mysql' );

		// Check start date - if set and in the future, redirect is not active yet.
		if ( ! empty( $redirect->start_date ) && $now < $redirect->start_date ) {
			return false;
		}

		// Check end date - if set and in the past, redirect has expired.
		if ( ! empty( $redirect->end_date ) && $now > $redirect->end_date ) {
			return false;
		}

		return true;
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
