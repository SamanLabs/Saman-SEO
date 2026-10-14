<?php
/**
 * Logs 404s and surfaces suggestions.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Request monitoring service.
 */
class Request_Monitor {

	private const SCHEMA_VERSION = 8;
	private const SCHEMA_OPTION  = 'SAMAN_SEO_404_log_schema';

	/**
	 * Transient that serialises schema upgrades and backs off after a failure.
	 */
	private const MIGRATION_LOCK = 'SAMAN_SEO_404_log_migrating';

	/**
	 * Non-autoloaded option holding the compiled ignore-pattern list.
	 */
	public const PATTERNS_CACHE_OPTION = 'SAMAN_SEO_404_ignore_patterns_cache';

	/**
	 * Daily cleanup cron hook.
	 */
	public const CLEANUP_HOOK = 'SAMAN_SEO_404_cleanup';

	/**
	 * Retention used when the cleanup settings have never been saved.
	 */
	public const DEFAULT_RETENTION_DAYS = 30;

	/**
	 * Default hard cap on stored rows (filter: saman_seo_404_log_max_rows).
	 */
	public const DEFAULT_MAX_ROWS = 10000;

	/**
	 * Column lengths, mirrored from create_tables(). Values are truncated
	 * before writing so strict SQL modes never reject a hit.
	 */
	private const FIELD_LENGTHS = array(
		'request_uri'  => 255,
		'user_agent'   => 255,
		'device_label' => 80,
		'referrer'     => 500,
		'ip_address'   => 100,
	);

	/**
	 * Main log table name.
	 *
	 * @var string
	 */
	private $table;

	/**
	 * Ignore patterns table name.
	 *
	 * @var string
	 */
	private $patterns_table;

	/**
	 * Constructor.
	 */
	public function __construct() {
		global $wpdb;
		$this->table          = $wpdb->prefix . 'SAMAN_SEO_404_log';
		$this->patterns_table = $wpdb->prefix . 'SAMAN_SEO_404_ignore_patterns';
	}

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		$this->maybe_upgrade_schema();

		if ( ! \Saman\SEO\Helpers\module_enabled( '404_log' ) ) {
			$this->unschedule_cleanup();
			return;
		}

		add_action( 'template_redirect', array( $this, 'maybe_log_404' ) );

		// Setup scheduled cleanup.
		add_action( self::CLEANUP_HOOK, array( $this, 'run_scheduled_cleanup' ) );
		$this->maybe_schedule_cleanup();
	}

	/**
	 * Whether the current request is a browser prefetch/prerender.
	 *
	 * Speculative loading (core since WP 6.8) makes browsers fetch links the
	 * visitor may never open. Those requests should still be answered
	 * normally but must not count as visits in 404 logs, redirect hits or
	 * cache statistics.
	 *
	 * Recognised headers: `Sec-Purpose` (e.g. `prefetch`,
	 * `prefetch;prerender`), the legacy `Purpose` and `X-Purpose`, and
	 * Firefox's `X-Moz`.
	 *
	 * @param array|null $server Server vars to inspect. Defaults to $_SERVER.
	 *
	 * @return bool
	 */
	public static function is_speculative_request( $server = null ) {
		if ( ! is_array( $server ) ) {
			$server = $_SERVER; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Only matched against a fixed token below.
		}

		foreach ( array( 'HTTP_SEC_PURPOSE', 'HTTP_PURPOSE', 'HTTP_X_PURPOSE', 'HTTP_X_MOZ' ) as $header ) {
			if ( empty( $server[ $header ] ) || ! is_string( $server[ $header ] ) ) {
				continue;
			}

			// Structured-field list: items separated by commas, parameters by semicolons.
			if ( preg_match( '/(?:^|[\s,;])prefetch(?:$|[\s,;])/i', $server[ $header ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Keep the daily cleanup scheduled while the 404 log is active.
	 *
	 * The cron always runs: even when age-based retention is switched off, it
	 * enforces the hard row cap so the table cannot grow without bound.
	 *
	 * @return void
	 */
	public function maybe_schedule_cleanup() {
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CLEANUP_HOOK );
		}
	}

	/**
	 * Unschedule cleanup cron.
	 *
	 * @return void
	 */
	public function unschedule_cleanup() {
		$timestamp = wp_next_scheduled( self::CLEANUP_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CLEANUP_HOOK );
		}
	}

	/**
	 * Run scheduled cleanup: age-based retention, then the row cap.
	 *
	 * @return void
	 */
	public function run_scheduled_cleanup() {
		$days = $this->get_retention_days();

		if ( $days > 0 ) {
			$this->cleanup_old_entries( $days );
		}

		$this->enforce_row_cap();
	}

	/**
	 * Resolve how many days of 404 history to keep.
	 *
	 * When the cleanup settings have never been saved (neither the individual
	 * option nor the legacy consolidated array holds them), retention defaults
	 * to DEFAULT_RETENTION_DAYS. An explicitly saved "off" is respected.
	 *
	 * @return int Days to keep, or 0 when age-based cleanup is disabled.
	 */
	public function get_retention_days() {
		$enabled = $this->get_404_option( 'enable_404_cleanup', null );
		$days    = (int) $this->get_404_option( 'cleanup_404_days', self::DEFAULT_RETENTION_DAYS );

		if ( $days < 1 ) {
			$days = self::DEFAULT_RETENTION_DAYS;
		}

		if ( null === $enabled ) {
			return $days;
		}

		return $this->is_truthy( $enabled ) ? $days : 0;
	}

	/**
	 * Hard cap on stored 404 rows.
	 *
	 * @return int Maximum rows, or 0 to disable the cap.
	 */
	public function get_max_rows() {
		/**
		 * Filter the maximum number of rows kept in the 404 log.
		 *
		 * The daily cleanup deletes the least recently seen rows beyond this
		 * count. Return 0 to disable the cap.
		 *
		 * @param int $max_rows Maximum rows. Default 10000.
		 */
		$max = (int) saman_seo_apply_filters( 'saman_seo_404_log_max_rows', self::DEFAULT_MAX_ROWS );

		return max( 0, $max );
	}

	/**
	 * Delete entries older than the specified number of days.
	 *
	 * @param int $days Days threshold.
	 * @return int Number of deleted entries.
	 */
	public function cleanup_old_entries( $days = 30 ) {
		global $wpdb;

		$days = max( 1, (int) $days );

		// last_seen is written with current_time( 'mysql' ) (site time), so
		// the cutoff must be in site time too.
		$cutoff = wp_date( 'Y-m-d H:i:s', time() - ( $days * DAY_IN_SECONDS ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table maintenance, table name is safe.
		return (int) $wpdb->query( $wpdb->prepare( "DELETE FROM {$this->table} WHERE last_seen < %s", $cutoff ) );
	}

	/**
	 * Trim the log to the configured row cap, dropping the least recently
	 * seen rows first.
	 *
	 * @return int Number of deleted entries.
	 */
	public function enforce_row_cap() {
		global $wpdb;

		$max = $this->get_max_rows();
		if ( $max < 1 ) {
			return 0;
		}

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Custom table maintenance, table name is safe.
		$total = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->table}" );
		if ( $total <= $max ) {
			return 0;
		}

		// First row past the cap, in "most recently seen first" order.
		$cut = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, last_seen FROM {$this->table} ORDER BY last_seen DESC, id DESC LIMIT 1 OFFSET %d",
				$max
			)
		);

		if ( ! $cut || empty( $cut->last_seen ) ) {
			return 0;
		}

		$deleted = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE last_seen < %s OR ( last_seen = %s AND id <= %d )",
				$cut->last_seen,
				$cut->last_seen,
				(int) $cut->id
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		return (int) $deleted;
	}

	/**
	 * Interpret a stored boolean-ish option value.
	 *
	 * @param mixed $value Stored value.
	 * @return bool
	 */
	private function is_truthy( $value ) {
		if ( is_string( $value ) ) {
			return ! in_array( strtolower( trim( $value ) ), array( '', '0', 'false', 'no', 'off' ), true );
		}

		return ! empty( $value );
	}

	/**
	 * Maybe send a notification for a 404 URL reaching threshold.
	 *
	 * @param string $request_uri The 404 URL.
	 * @param int    $hits        Current hit count.
	 * @param int    $entry_id    The log entry ID.
	 * @return void
	 */
	private function maybe_send_notification( $request_uri, $hits, $entry_id ) {
		$enabled = $this->get_404_option( 'enable_404_notifications', false );

		if ( ! $enabled ) {
			return;
		}

		$threshold = (int) $this->get_404_option( 'notification_404_threshold', 10 );

		// Only notify exactly when threshold is reached (not every hit after)
		if ( $hits !== $threshold ) {
			return;
		}

		// Check if we've already notified for this entry
		$notified_key = 'SAMAN_SEO_404_notified_' . $entry_id;
		if ( get_transient( $notified_key ) ) {
			return;
		}

		// Mark as notified (expires in 7 days)
		set_transient( $notified_key, true, 7 * DAY_IN_SECONDS );

		$this->send_notification_email( $request_uri, $hits );
	}

	/**
	 * Send the notification email.
	 *
	 * @param string $request_uri The 404 URL.
	 * @param int    $hits        Current hit count.
	 * @return bool Whether email was sent.
	 */
	private function send_notification_email( $request_uri, $hits ) {
		$email = $this->get_404_option( 'notification_404_email', '' );
		$email = ! empty( $email ) ? sanitize_email( $email ) : get_option( 'admin_email' );

		$site_name = get_bloginfo( 'name' );
		$site_url  = home_url();
		$admin_url = admin_url( 'admin.php?page=saman-seo-404-log' );

		$subject = sprintf(
			/* translators: %s: Site name */
			__( '[%s] 404 Error Alert - URL Needs Attention', 'saman-seo' ),
			$site_name
		);

		/* translators: 1: request URI, 2: hit count, 3: site URL, 4: admin URL */
		$message = sprintf(
			__( "Hello,\n\nA 404 error on your site has reached the notification threshold.\n\nURL: %1\$s\nHits: %2\$d\n\nSite: %3\$s\n\nYou may want to create a redirect for this URL to improve user experience and SEO.\n\nView and manage 404 errors:\n%4\$s\n\n--\nThis notification was sent by Saman SEO.\nYou can disable these notifications in Settings > Advanced > 404 Monitor.", 'saman-seo' ),
			$request_uri,
			$hits,
			$site_url,
			$admin_url
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		return wp_mail( $email, $subject, $message, $headers );
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

		// Main 404 log table. The UNIQUE KEY on uri_hash is deliberately not
		// part of this definition: dbDelta would try to add it before existing
		// duplicates are merged. migrate_to_version_8() adds it afterwards.
		$sql = "CREATE TABLE {$this->table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_uri varchar(255) NOT NULL,
			uri_hash char(32) NOT NULL DEFAULT '',
			user_agent varchar(255) DEFAULT '',
			device_label varchar(80) DEFAULT '',
			hits bigint(20) unsigned NOT NULL DEFAULT 1,
			last_seen datetime NOT NULL,
			is_bot tinyint(1) NOT NULL DEFAULT 0,
			is_ignored tinyint(1) NOT NULL DEFAULT 0,
			referrer varchar(500) DEFAULT '',
			ip_address varchar(100) DEFAULT '',
			first_seen datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY request_uri (request_uri),
			KEY is_bot (is_bot),
			KEY is_ignored (is_ignored),
			KEY last_seen (last_seen)
		) {$charset};";

		dbDelta( $sql );

		// Ignore patterns table
		$patterns_sql = "CREATE TABLE {$this->patterns_table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			pattern varchar(255) NOT NULL,
			is_regex tinyint(1) NOT NULL DEFAULT 0,
			reason varchar(255) DEFAULT '',
			created_at datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY pattern (pattern)
		) {$charset};";

		dbDelta( $patterns_sql );

		$this->flush_ignore_patterns_cache();

		// The stored schema version is bumped by maybe_upgrade_schema() once
		// the data migrations (including the uri_hash unique index) succeed.
	}

	/**
	 * Get patterns table name.
	 *
	 * @return string
	 */
	public function get_patterns_table() {
		return $this->patterns_table;
	}

	/**
	 * Ensure the custom 404 table schema is current.
	 *
	 * @return void
	 */
	private function maybe_upgrade_schema() {
		// Common path: one autoloaded option read, no SHOW TABLES / dbDelta.
		$current = (int) get_option( self::SCHEMA_OPTION, 0 );

		if ( $current >= self::SCHEMA_VERSION ) {
			return;
		}

		// One upgrade at a time; after a failure the lock doubles as a
		// back-off so a broken migration is not retried on every request.
		if ( get_transient( self::MIGRATION_LOCK ) ) {
			return;
		}
		set_transient( self::MIGRATION_LOCK, 1, 10 * MINUTE_IN_SECONDS );

		$this->create_tables();

		// Migrate existing entries: backfill first_seen = last_seen for old entries
		if ( $current > 0 && $current < 5 ) {
			$this->migrate_to_version_5();
		}

		if ( $current > 0 && $current < 6 ) {
			$this->migrate_to_version_6();
		}

		if ( $current > 0 && $current < 7 ) {
			$this->migrate_to_version_7();
		}

		if ( ! $this->migrate_to_version_8() ) {
			return;
		}

		update_option( self::SCHEMA_OPTION, (string) self::SCHEMA_VERSION );
		delete_transient( self::MIGRATION_LOCK );
	}

	/**
	 * Migrate data for version 5 schema.
	 *
	 * @return void
	 */
	private function migrate_to_version_5() {
		global $wpdb;

		// Backfill first_seen = last_seen for existing entries where first_seen is NULL
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Data migration requires direct queries.
		$wpdb->query( "UPDATE {$this->table} SET first_seen = last_seen WHERE first_seen IS NULL" );

		// Backfill is_bot flag based on user_agent for existing entries
		$bot_patterns = $this->get_bot_patterns();
		foreach ( $bot_patterns as $pattern ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Data migration requires direct queries.
			$wpdb->query(
				$wpdb->prepare(
					"UPDATE {$this->table} SET is_bot = 1 WHERE is_bot = 0 AND LOWER(user_agent) LIKE %s",
					'%' . $wpdb->esc_like( $pattern ) . '%'
				)
			);
		}
	}

	/**
	 * Migrate data for version 6 schema.
	 *
	 * @return void
	 */
	private function migrate_to_version_6() {
		global $wpdb;

		if ( ! $this->has_column( 'ip_address' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Schema migration requires direct queries.
			$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN ip_address varchar(100) DEFAULT ''" );
		}
	}

	/**
	 * Migrate data for version 7 schema.
	 *
	 * Ensures the device_label column exists. Previously this was checked on
	 * every request via SHOW COLUMNS; it now runs only through the version gate.
	 *
	 * @return void
	 */
	private function migrate_to_version_7() {
		global $wpdb;

		if ( ! $this->has_column( 'device_label' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Schema migration requires direct queries.
			$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN device_label varchar(80) DEFAULT ''" );
		}
	}

	/**
	 * Migrate data for version 8 schema: one row per URI.
	 *
	 * The request_uri column has no unique key, so concurrent hits on the
	 * same URL used to insert duplicate rows. This backfills uri_hash, merges duplicates
	 * into the earliest row (summed hits, latest last_seen, earliest
	 * first_seen, sticky bot/ignored flags) and only then adds the UNIQUE
	 * index that lets maybe_log_404() write with a single upsert.
	 *
	 * The hash is case-folded to keep the old dedupe semantics: lookups used
	 * `request_uri = %s` on a case-insensitive collation.
	 *
	 * Idempotent; safe to re-run after a partial failure.
	 *
	 * @return bool Whether the unique index is in place.
	 */
	private function migrate_to_version_8() {
		global $wpdb;

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Schema migration on the plugin's own table, table name is safe.
		if ( ! $this->has_column( 'uri_hash' ) ) {
			$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN uri_hash char(32) NOT NULL DEFAULT '' AFTER request_uri" );
		}

		if ( $this->has_uri_hash_index() ) {
			return true;
		}

		$wpdb->query( "UPDATE {$this->table} SET uri_hash = MD5(LOWER(request_uri)) WHERE uri_hash = ''" );

		// Fold duplicate rows into the earliest one. Derived tables with
		// GROUP BY are materialised, so self-joins are allowed here.
		$wpdb->query(
			"UPDATE {$this->table} AS keep_row
			INNER JOIN (
				SELECT uri_hash,
					MIN(id) AS keep_id,
					SUM(hits) AS total_hits,
					MAX(last_seen) AS latest_seen,
					MIN(COALESCE(first_seen, last_seen)) AS earliest_seen,
					MAX(is_bot) AS any_bot,
					MAX(is_ignored) AS any_ignored
				FROM {$this->table}
				GROUP BY uri_hash
				HAVING COUNT(*) > 1
			) AS dupes ON keep_row.id = dupes.keep_id
			SET keep_row.hits = dupes.total_hits,
				keep_row.last_seen = dupes.latest_seen,
				keep_row.first_seen = dupes.earliest_seen,
				keep_row.is_bot = dupes.any_bot,
				keep_row.is_ignored = dupes.any_ignored"
		);

		$wpdb->query(
			"DELETE extra_row FROM {$this->table} AS extra_row
			INNER JOIN (
				SELECT uri_hash, MIN(id) AS keep_id
				FROM {$this->table}
				GROUP BY uri_hash
				HAVING COUNT(*) > 1
			) AS dupes ON extra_row.uri_hash = dupes.uri_hash AND extra_row.id <> dupes.keep_id"
		);

		$wpdb->query( "ALTER TABLE {$this->table} ADD UNIQUE KEY uri_hash (uri_hash)" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		return $this->has_uri_hash_index();
	}

	/**
	 * Whether the unique uri_hash index exists.
	 *
	 * @return bool
	 */
	private function has_uri_hash_index() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Inspecting table schema requires a direct query, table name is safe.
		$index = $wpdb->get_row( "SHOW INDEX FROM {$this->table} WHERE Key_name = 'uri_hash'" );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- MySQL column name.
		return $index && isset( $index->Non_unique ) && 0 === (int) $index->Non_unique;
	}

	/**
	 * Dedupe key for a logged request path.
	 *
	 * Case-folded so "/Foo" and "/foo" share a row, matching how the log
	 * deduplicated under the table's case-insensitive collation before the
	 * uri_hash column existed. Must stay in step with the MD5(LOWER())
	 * backfill in migrate_to_version_8().
	 *
	 * @param string $request_uri Normalised request path.
	 * @return string 32-char hex hash.
	 */
	public static function uri_hash( $request_uri ) {
		return md5( strtolower( (string) $request_uri ) );
	}

	/**
	 * Truncate a value to a column's length.
	 *
	 * @param string $field Column name (key of FIELD_LENGTHS).
	 * @param string $value Value.
	 * @return string
	 */
	private function fit_column( $field, $value ) {
		$value  = (string) $value;
		$length = self::FIELD_LENGTHS[ $field ];

		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, 0, $length, 'UTF-8' );
		}

		return substr( $value, 0, $length );
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

		// Prefetches are not visits; the 404 response itself is still sent.
		if ( self::is_speculative_request() ) {
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

		$request = $this->fit_column( 'request_uri', $request );

		// Do not log URLs that match an ignore pattern (cached list, no query
		// once warm).
		if ( $this->is_url_ignored( $request ) ) {
			return;
		}

		$settings   = $this->get_404_log_settings();
		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$is_bot     = $this->detect_is_bot( $user_agent ) ? 1 : 0;

		// Optionally skip bot requests entirely.
		if ( $settings['ignore_bots'] && $is_bot ) {
			return;
		}

		$referrer   = ! empty( $settings['log_referer'] ) && isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$device     = $this->describe_device_from_user_agent( $user_agent );
		$now        = current_time( 'mysql' );
		$ip_address = $this->get_client_ip( $settings );
		$uri_hash   = self::uri_hash( $request );

		global $wpdb;

		// One atomic upsert keyed on the unique uri_hash: concurrent hits on
		// the same URL increment a single row instead of racing a
		// SELECT-then-INSERT into duplicates. Existing user agent / referrer
		// are kept when this hit has none; the bot flag is sticky.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Writing the custom 404 log table, table name is safe.
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$this->table}
					(request_uri, uri_hash, user_agent, device_label, hits, last_seen, first_seen, is_bot, referrer, ip_address)
				VALUES (%s, %s, %s, %s, 1, %s, %s, %d, %s, %s)
				ON DUPLICATE KEY UPDATE
					hits = hits + 1,
					last_seen = VALUES(last_seen),
					user_agent = IF(VALUES(user_agent) = '', user_agent, VALUES(user_agent)),
					device_label = VALUES(device_label),
					ip_address = VALUES(ip_address),
					referrer = IF(VALUES(referrer) = '', referrer, VALUES(referrer)),
					is_bot = GREATEST(is_bot, VALUES(is_bot))",
				$request,
				$uri_hash,
				$this->fit_column( 'user_agent', $user_agent ),
				$this->fit_column( 'device_label', $device ),
				$now,
				$now,
				$is_bot,
				$this->fit_column( 'referrer', $referrer ),
				$this->fit_column( 'ip_address', $ip_address )
			)
		);

		// Only read the row back when a notification could be due.
		if ( ! $this->get_404_option( 'enable_404_notifications', false ) ) {
			return;
		}

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT id, hits FROM {$this->table} WHERE uri_hash = %s ORDER BY id ASC LIMIT 1", $uri_hash )
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter

		if ( $row ) {
			$this->maybe_send_notification( $request, (int) $row->hits, (int) $row->id );
		}
	}

	/**
	 * Get 404 log privacy/logging settings.
	 *
	 * @return array
	 */
	private function get_404_log_settings() {
		$ip_level = get_option( 'SAMAN_SEO_404_log_ip_level', 'none' );
		if ( ! in_array( $ip_level, array( 'none', 'anonymized', 'full' ), true ) ) {
			$ip_level = 'none';
		}

		return array(
			'ip_level'    => $ip_level,
			'ip_header'   => get_option( 'SAMAN_SEO_404_log_ip_header', 'REMOTE_ADDR' ),
			'log_referer' => ! empty( get_option( 'SAMAN_SEO_404_log_referer', '1' ) ),
			'ignore_bots' => ! empty( get_option( 'SAMAN_SEO_404_log_ignore_bots', '1' ) ),
		);
	}

	/**
	 * Read a 404-related setting from the modern individual option, falling back
	 * to the legacy consolidated option when needed.
	 *
	 * @param string $key     Setting key without SAMAN_SEO_ prefix.
	 * @param mixed  $default Default value.
	 * @return mixed
	 */
	private function get_404_option( $key, $default = false ) {
		$individual = get_option( 'SAMAN_SEO_' . $key );
		if ( false !== $individual ) {
			return $individual;
		}

		$legacy = get_option( 'SAMAN_SEO_settings', array() );
		if ( isset( $legacy[ $key ] ) ) {
			return $legacy[ $key ];
		}

		return $default;
	}

	/**
	 * Get the client IP address based on settings.
	 *
	 * @param array $settings 404 log settings.
	 * @return string
	 */
	private function get_client_ip( $settings ) {
		if ( 'none' === $settings['ip_level'] ) {
			return '';
		}

		$header = $settings['ip_header'];
		if ( empty( $header ) ) {
			$header = 'REMOTE_ADDR';
		}

		$ip = '';
		if ( 'REMOTE_ADDR' === $header && isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		} elseif ( isset( $_SERVER[ $header ] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
		}

		// X-Forwarded-For can contain multiple IPs; use the first one.
		if ( false !== strpos( $ip, ',' ) ) {
			$ips = explode( ',', $ip );
			$ip  = trim( $ips[0] );
		}

		if ( 'anonymized' === $settings['ip_level'] ) {
			$ip = $this->anonymize_ip( $ip );
		}

		return $ip;
	}

	/**
	 * Anonymize an IP address by zeroing the last octet (IPv4) or last group (IPv6).
	 *
	 * @param string $ip IP address.
	 * @return string
	 */
	private function anonymize_ip( $ip ) {
		if ( empty( $ip ) || ! filter_var( $ip, FILTER_VALIDATE_IP ) ) {
			return '';
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
			$parts    = explode( '.', $ip );
			$parts[3] = '0';
			return implode( '.', $parts );
		}

		if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
			$parts = explode( ':', $ip );
			if ( count( $parts ) >= 2 ) {
				$parts[ count( $parts ) - 1 ] = '0';
			}
			return implode( ':', $parts );
		}

		return '';
	}

	/**
	 * Build a simplified device label from a user agent string.
	 *
	 * @param string $user_agent Raw user agent string.
	 * @return string
	 */
	private function describe_device_from_user_agent( $user_agent ) {
		if ( empty( $user_agent ) ) {
			return __( 'Unknown device', 'saman-seo' );
		}

		$browser_label  = $this->detect_browser_label( $user_agent );
		$platform_label = $this->detect_platform_label( $user_agent );

		$parts = array_filter( array( $platform_label, $browser_label ) );

		if ( ! empty( $parts ) ) {
			return trim( implode( ' ', $parts ) );
		}

		return substr( $user_agent, 0, 80 );
	}

	/**
	 * Ensure device labels are present for the provided rows, backfilling as needed.
	 *
	 * @param array<int,\stdClass> $rows Log rows.
	 * @return array<int,\stdClass>
	 */
	private function hydrate_device_labels( $rows ) {
		global $wpdb;

		foreach ( $rows as $row ) {
			if ( ! empty( $row->device_label ) ) {
				continue;
			}

			$label = $this->describe_device_from_user_agent( $row->user_agent ?? '' );

			if ( empty( $label ) || empty( $row->id ) ) {
				continue;
			}

			$row->device_label = $label;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Backfilling derived column requires direct updates.
			$wpdb->update(
				$this->table,
				array( 'device_label' => $label ),
				array( 'id' => (int) $row->id ),
				array( '%s' ),
				array( '%d' )
			);
		}

		return $rows;
	}

	/**
	 * Mark rows that already have redirects configured.
	 *
	 * @param array<int,\stdClass> $rows Log rows.
	 * @return array<int,\stdClass>
	 */
	private function annotate_redirect_status( $rows ) {
		global $wpdb;

		$redirect_table = $wpdb->prefix . 'SAMAN_SEO_redirects';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Table existence check avoids hard errors on older installs.
		$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $redirect_table ) );
		if ( $redirect_table !== $has_table ) {
			foreach ( $rows as $row ) {
				$row->redirect_exists = false;
			}
			return $rows;
		}

		$requests = array();
		foreach ( $rows as $row ) {
			if ( ! empty( $row->request_uri ) ) {
				$requests[] = $row->request_uri;
			}
		}

		if ( ! $requests ) {
			foreach ( $rows as $row ) {
				$row->redirect_exists = false;
			}
			return $rows;
		}

		$requests     = array_values( array_unique( $requests ) );
		$placeholders = implode( ',', array_fill( 0, count( $requests ), '%s' ) );
		$sql          = "SELECT source FROM {$redirect_table} WHERE source IN ({$placeholders})";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name safe, matching redirects against 404 log requires direct query.
		$sources = $wpdb->get_col( $wpdb->prepare( $sql, $requests ) );

		$lookup = $sources ? array_fill_keys( $sources, true ) : array();
		foreach ( $rows as $row ) {
			$row->redirect_exists = isset( $lookup[ $row->request_uri ] );
		}

		return $rows;
	}

	/**
	 * Spammy file and path patterns to suppress in the 404 log view.
	 *
	 * @return string[]
	 */
	private function get_spam_url_patterns() {
		return array(
			// Executables and configs
			'%.php',
			'%.env',
			'%.ini',
			'%.log',
			'%.bak',
			'%.old',
			'%.sql',
			'%.zip',
			'%.tar',
			'%.gz',
			'%.rar',
			'%.exe',
			'%.sh',
			'%.bat',
			'%.cmd',
			'%.bin',
			'%.dll',
			'%.com',
			'%.scr',
			'%.sys',
			'%.htaccess',
			'%.htpasswd',

			// Git and server internals
			'%/.git/config',
			'%/.git%',
			'%/.svn%',
			'%/.hg%',
			'%/.DS_Store',

			// WordPress core attack targets
			'%/wp-admin%',
			'%/wp-includes%',
			'%/wp-login%',
			'%/xmlrpc.php%',
			'%/readme.html%',
			'%/license.txt%',
			'%/wp-config.php%',
			'%/wp-content/plugins/%',
			'%/wp-content/themes/%',
			'%/wp-content/mu-plugins/%',
			'%/wp-content/debug.log%',
			'%/wp-json/%',
			'%/wp-cron.php%',
			'%/wp-trackback.php%',

			// Fonts
			'%.ttf',
			'%.woff',
			'%.woff2',
			'%.eot',
			'%.otf',
			'%.sfnt',
			'%.fnt',
			'%.fon',

			// Frontend assets
			'%.css',
			'%.js',
			'%.map',
			'%.less',
			'%.scss',
			'%.sass',
			'%.styl',
			'%.xml',
			'%.json',
			'%.rss',
			'%.atom',
			'%.yaml',
			'%.yml',
			'%.csv',
			'%.txt',
			'%.md',
			'%.markdown',
			'%.pdf',
			'%.doc',
			'%.docx',
			'%.xls',
			'%.xlsx',
			'%.ppt',
			'%.pptx',

			// WordPress content noise
			'%/wp-content/uploads%',
			'%/wp-content/cache%',
			'%/wp-content/plugins%',
			'%/wp-content/themes%',
			'%/wp-content/ai1wm-backups%',
			'%/wp-content/backup%',
			'%/wp-content/debug.log%',

			// Common bot probes
			'%/cgi-bin%',
			'%/vendor%',
			'%/node_modules%',
			'%/composer.json%',
			'%/package.json%',
		);
	}

	/**
	 * Image and static asset file patterns to optionally suppress in the 404 log view.
	 *
	 * @return string[]
	 */
	private function get_image_url_patterns() {
		return array(
			// Images
			'%.png',
			'%.jpg',
			'%.jpeg',
			'%.gif',
			'%.webp',
			'%.svg',
			'%.ico',
			'%.bmp',
			'%.tiff',
			'%.heic',
			'%.avif',
			'%.psd',
			'%.ai',
			'%.eps',

			// Media
			'%.mp4',
			'%.webm',
			'%.mp3',
			'%.wav',
			'%.ogg',
		);
	}

	/**
	 * Bot/crawler user agent patterns for detection.
	 *
	 * @return string[]
	 */
	private function get_bot_patterns() {
		return array(
			// Search engine bots
			'googlebot',
			'bingbot',
			'slurp',
			'duckduckbot',
			'baiduspider',
			'yandexbot',
			'sogou',
			'exabot',
			'ia_archiver',

			// Social media crawlers
			'facebookexternalhit',
			'twitterbot',
			'linkedinbot',
			'pinterest',
			'whatsapp',
			'telegrambot',
			'discordbot',
			'slackbot',

			// SEO tools
			'ahrefsbot',
			'semrushbot',
			'mj12bot',
			'dotbot',
			'rogerbot',
			'screaming frog',
			'seokicks',
			'sistrix',
			'blexbot',
			'petalbot',
			'dataforseo',
			'serpstatbot',

			// Generic bot patterns
			'bot',
			'spider',
			'crawler',
			'scraper',
			'headless',

			// HTTP clients / tools
			'wget',
			'curl',
			'python-requests',
			'python-urllib',
			'java/',
			'libwww',
			'httpclient',
			'http_request',
			'go-http-client',
			'okhttp',
			'axios',
			'node-fetch',
			'postman',
			'insomnia',

			// Monitoring / Uptime
			'uptimerobot',
			'pingdom',
			'statuscake',
			'newrelic',
			'datadog',
			'site24x7',
			'gtmetrix',

			// Feed readers
			'feedfetcher',
			'feedly',
			'newsblur',
		);
	}

	/**
	 * Detect if a user agent belongs to a bot/crawler.
	 *
	 * @param string $user_agent Raw user agent string.
	 * @return bool
	 */
	public function detect_is_bot( $user_agent ) {
		if ( empty( $user_agent ) ) {
			return false;
		}

		$ua_lower = strtolower( $user_agent );

		foreach ( $this->get_bot_patterns() as $pattern ) {
			if ( false !== strpos( $ua_lower, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Check if a URL should be ignored based on patterns.
	 *
	 * @param string $request_uri The request URI to check.
	 * @return bool
	 */
	public function is_url_ignored( $request_uri ) {
		$request_uri = (string) $request_uri;

		foreach ( $this->get_compiled_ignore_patterns() as $rule ) {
			if ( 'exact' === $rule['type'] ) {
				if ( $request_uri === $rule['value'] ) {
					return true;
				}
				continue;
			}

			if ( $this->regex_matches_safely( $rule['value'], $request_uri ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Ignore patterns compiled for matching, cached in a non-autoloaded option.
	 *
	 * The table's existence is guaranteed by the schema version gate, so no
	 * SHOW TABLES probe is needed. The cache is dropped whenever a pattern is
	 * added or deleted, or the tables are (re)created.
	 *
	 * @return array<int,array{type:string,value:string}>
	 */
	public function get_compiled_ignore_patterns() {
		$cached = get_option( self::PATTERNS_CACHE_OPTION, false );

		if ( is_array( $cached ) ) {
			return $cached;
		}

		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Rebuilding the pattern cache from the plugin's own table, table name is safe.
		$rows = $wpdb->get_results( "SELECT pattern, is_regex FROM {$this->patterns_table} ORDER BY id ASC" );

		$compiled = self::compile_ignore_patterns( is_array( $rows ) ? $rows : array() );

		update_option( self::PATTERNS_CACHE_OPTION, $compiled, false );

		return $compiled;
	}

	/**
	 * Turn stored ignore pattern rows into ready-to-run matchers.
	 *
	 * - regex rows:   `/pattern/i`
	 * - wildcards:    `*` becomes `.*`, anchored, case-insensitive
	 * - anything else is an exact, case-sensitive comparison
	 *
	 * @param array<int,object|array> $rows Rows with pattern and is_regex.
	 * @return array<int,array{type:string,value:string}>
	 */
	public static function compile_ignore_patterns( array $rows ) {
		$compiled = array();

		foreach ( $rows as $row ) {
			$row     = (array) $row;
			$pattern = isset( $row['pattern'] ) ? (string) $row['pattern'] : '';

			if ( '' === $pattern ) {
				continue;
			}

			if ( ! empty( $row['is_regex'] ) ) {
				$compiled[] = array(
					'type'  => 'regex',
					'value' => '/' . str_replace( '/', '\\/', $pattern ) . '/i',
				);
			} elseif ( false !== strpos( $pattern, '*' ) ) {
				// preg_quote() already escapes the "/" delimiter. The old code
				// escaped it a second time, which ended the regex early, so
				// wildcards containing "/" (e.g. "/old/*") never matched.
				$compiled[] = array(
					'type'  => 'regex',
					'value' => '/^' . str_replace( '\\*', '.*', preg_quote( $pattern, '/' ) ) . '$/i',
				);
			} else {
				$compiled[] = array(
					'type'  => 'exact',
					'value' => $pattern,
				);
			}
		}

		return $compiled;
	}

	/**
	 * Drop the compiled ignore-pattern cache.
	 *
	 * @return void
	 */
	public function flush_ignore_patterns_cache() {
		delete_option( self::PATTERNS_CACHE_OPTION );
	}

	/**
	 * Safely test whether a regex matches a subject.
	 *
	 * Invalid regex patterns are treated as "no match" instead of raising
	 * a fatal error or warning.
	 *
	 * @param string $regex   Full regex including delimiters and flags.
	 * @param string $subject Subject string.
	 * @return bool
	 */
	private function regex_matches_safely( $regex, $subject ) {
		$message  = '';
		$previous = set_error_handler(
			static function ( $severity, $err_message ) use ( &$message ) {
				$message = $err_message;
				return true;
			}
		);

		try {
			$result = preg_match( $regex, $subject );
		} catch ( \Throwable $e ) {
			$result  = false;
			$message = $e->getMessage();
		} finally {
			if ( false !== $previous ) {
				restore_error_handler();
			}
		}

		if ( false === $result ) {
			return false;
		}

		return 1 === $result;
	}

	/**
	 * Validate a regex pattern intended for ignore matching.
	 *
	 * @param string $pattern Regex pattern without delimiters.
	 * @return true|\WP_Error True if valid, WP_Error if invalid.
	 */
	public function validate_regex_pattern( $pattern ) {
		if ( empty( $pattern ) ) {
			return new \WP_Error( 'empty_pattern', __( 'Regex pattern cannot be empty.', 'saman-seo' ) );
		}

		$regex   = '/' . str_replace( '/', '\\/', (string) $pattern ) . '/i';
		$message = '';

		$previous = set_error_handler(
			static function ( $severity, $err_message ) use ( &$message ) {
				$message = $err_message;
				return true;
			}
		);

		try {
			$result = preg_match( $regex, '' );
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
	 * Get all ignore patterns.
	 *
	 * @return array
	 */
	public function get_ignore_patterns() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Admin listing of the plugin's own table, table name is safe.
		$rows = $wpdb->get_results( "SELECT * FROM {$this->patterns_table} ORDER BY created_at DESC" );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Add an ignore pattern.
	 *
	 * @param string $pattern  The pattern to add.
	 * @param bool   $is_regex Whether it's a regex pattern.
	 * @param string $reason   Optional reason/note.
	 * @return int|false The inserted ID or false on failure.
	 */
	public function add_ignore_pattern( $pattern, $is_regex = false, $reason = '' ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$result = $wpdb->insert(
			$this->patterns_table,
			array(
				'pattern'    => $pattern,
				'is_regex'   => $is_regex ? 1 : 0,
				'reason'     => $reason,
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%d', '%s', '%s' )
		);

		$this->flush_ignore_patterns_cache();

		if ( false === $result ) {
			return false;
		}

		return $wpdb->insert_id;
	}

	/**
	 * Delete an ignore pattern.
	 *
	 * @param int $id Pattern ID.
	 * @return bool
	 */
	public function delete_ignore_pattern( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$deleted = (bool) $wpdb->delete(
			$this->patterns_table,
			array( 'id' => $id ),
			array( '%d' )
		);

		$this->flush_ignore_patterns_cache();

		return $deleted;
	}

	/**
	 * Mark a 404 entry as ignored.
	 *
	 * @param int $id Entry ID.
	 * @return bool
	 */
	public function ignore_entry( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update(
			$this->table,
			array( 'is_ignored' => 1 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Unignore a 404 entry.
	 *
	 * @param int $id Entry ID.
	 * @return bool
	 */
	public function unignore_entry( $id ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return (bool) $wpdb->update(
			$this->table,
			array( 'is_ignored' => 0 ),
			array( 'id' => $id ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Detect browser name using the bundled UA parser fallback.
	 *
	 * @param string $user_agent Raw user agent.
	 * @return string
	 */
	private function detect_browser_label( $user_agent ) {
		if ( $this->load_browser_parser() ) {
			try {
				$result = \useragent_detect_browser::analyze( $user_agent );
			} catch ( \Throwable $e ) {
				$result = null;
			}

			if ( is_array( $result ) ) {
				if ( ! empty( $result['title'] ) ) {
					return trim( $result['title'] );
				}

				if ( ! empty( $result['name'] ) ) {
					return trim( $result['name'] );
				}
			}
		}

		if ( preg_match( '/^([A-Za-z0-9\\-\\.]+)(?:\\/[0-9A-Za-z\\.]+)?/i', $user_agent, $matches ) ) {
			$token = $matches[1];

			if ( stripos( $token, 'mozilla' ) !== 0 ) {
				return $token;
			}
		}

		return '';
	}

	/**
	 * Attempt to determine the platform/OS from the user agent.
	 *
	 * @param string $user_agent Raw user agent.
	 * @return string
	 */
	private function detect_platform_label( $user_agent ) {
		$ua = strtolower( $user_agent );

		$map = array(
			'windows phone'  => 'Windows Phone',
			'windows nt 10'  => 'Windows 10',
			'windows nt 6.3' => 'Windows 8.1',
			'windows nt 6.2' => 'Windows 8',
			'windows nt 6.1' => 'Windows 7',
			'windows nt 6.0' => 'Windows Vista',
			'windows nt 5.1' => 'Windows XP',
			'android'        => 'Android',
			'iphone'         => 'iPhone',
			'ipad'           => 'iPad',
			'ipod'           => 'iPod',
			'mac os x'       => 'macOS',
			'macintosh'      => 'macOS',
			'cros'           => 'Chrome OS',
			'linux'          => 'Linux',
			'bb10'           => 'BlackBerry',
			'blackberry'     => 'BlackBerry',
			'playstation'    => 'PlayStation',
			'nintendo'       => 'Nintendo',
			'xbox'           => 'Xbox',
			'go-http-client' => 'Go-http-client',
		);

		foreach ( $map as $needle => $label ) {
			if ( false !== strpos( $ua, $needle ) ) {
				return $label;
			}
		}

		return '';
	}

	/**
	 * Load the third-party browser detection class if needed.
	 *
	 * @return bool
	 */
	private function load_browser_parser() {
		if ( class_exists( '\useragent_detect_browser' ) ) {
			return true;
		}

		$parser = SAMAN_SEO_PATH . 'includes/useragent_detect_browser.php';

		if ( file_exists( $parser ) ) {
			require_once $parser;
		}

		return class_exists( '\useragent_detect_browser' );
	}

	/**
	 * Whether the custom 404 table contains a specific column.
	 *
	 * @param string $column Column name.
	 * @return bool
	 */
	private function has_column( $column ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Inspecting table schema requires a direct query, table name is safe.
		$existing = $wpdb->get_var( $wpdb->prepare( 'SHOW COLUMNS FROM ' . $this->table . ' LIKE %s', $column ) );

		return ! empty( $existing );
	}
}
