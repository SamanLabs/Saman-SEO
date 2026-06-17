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

	private const SCHEMA_VERSION = 5;
	private const SCHEMA_OPTION  = 'SAMAN_SEO_404_log_schema';

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
			return;
		}

		add_action( 'template_redirect', array( $this, 'maybe_log_404' ) );

		// Setup scheduled cleanup
		add_action( 'SAMAN_SEO_404_cleanup', array( $this, 'run_scheduled_cleanup' ) );
		$this->maybe_schedule_cleanup();
	}

	/**
	 * Schedule or unschedule cleanup based on settings.
	 *
	 * @return void
	 */
	public function maybe_schedule_cleanup() {
		$settings = get_option( 'SAMAN_SEO_settings', array() );
		$enabled  = isset( $settings['enable_404_cleanup'] ) ? $settings['enable_404_cleanup'] : false;

		if ( $enabled ) {
			if ( ! wp_next_scheduled( 'SAMAN_SEO_404_cleanup' ) ) {
				wp_schedule_event( time(), 'daily', 'SAMAN_SEO_404_cleanup' );
			}
		} else {
			$this->unschedule_cleanup();
		}
	}

	/**
	 * Unschedule cleanup cron.
	 *
	 * @return void
	 */
	public function unschedule_cleanup() {
		$timestamp = wp_next_scheduled( 'SAMAN_SEO_404_cleanup' );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, 'SAMAN_SEO_404_cleanup' );
		}
	}

	/**
	 * Run scheduled cleanup.
	 *
	 * @return void
	 */
	public function run_scheduled_cleanup() {
		$settings = get_option( 'SAMAN_SEO_settings', array() );
		$enabled  = isset( $settings['enable_404_cleanup'] ) ? $settings['enable_404_cleanup'] : false;

		if ( ! $enabled ) {
			return;
		}

		$days = isset( $settings['cleanup_404_days'] ) ? (int) $settings['cleanup_404_days'] : 30;
		$this->cleanup_old_entries( $days );
	}

	/**
	 * Delete entries older than the specified number of days.
	 *
	 * @param int $days Days threshold.
	 * @return int Number of deleted entries.
	 */
	public function cleanup_old_entries( $days = 30 ) {
		global $wpdb;

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table} WHERE last_seen < %s",
				$cutoff
			)
		);
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
		$settings = get_option( 'SAMAN_SEO_settings', array() );
		$enabled  = isset( $settings['enable_404_notifications'] ) ? $settings['enable_404_notifications'] : false;

		if ( ! $enabled ) {
			return;
		}

		$threshold = isset( $settings['notification_404_threshold'] ) ? (int) $settings['notification_404_threshold'] : 10;

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
		$settings = get_option( 'SAMAN_SEO_settings', array() );
		$email    = isset( $settings['notification_404_email'] ) && ! empty( $settings['notification_404_email'] )
			? sanitize_email( $settings['notification_404_email'] )
			: get_option( 'admin_email' );

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

		// Main 404 log table
		$sql = "CREATE TABLE {$this->table} (
			id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
			request_uri varchar(255) NOT NULL,
			user_agent varchar(255) DEFAULT '',
			device_label varchar(80) DEFAULT '',
			hits bigint(20) unsigned NOT NULL DEFAULT 1,
			last_seen datetime NOT NULL,
			is_bot tinyint(1) NOT NULL DEFAULT 0,
			is_ignored tinyint(1) NOT NULL DEFAULT 0,
			referrer varchar(500) DEFAULT '',
			first_seen datetime DEFAULT NULL,
			PRIMARY KEY (id),
			KEY request_uri (request_uri),
			KEY is_bot (is_bot),
			KEY is_ignored (is_ignored)
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

		update_option( self::SCHEMA_OPTION, (string) self::SCHEMA_VERSION );
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
		$current = (int) get_option( self::SCHEMA_OPTION, 0 );

		if ( $current < self::SCHEMA_VERSION ) {
			$this->create_tables();

			// Migrate existing entries: backfill first_seen = last_seen for old entries
			if ( $current > 0 && $current < 5 ) {
				$this->migrate_to_version_5();
			}

			return;
		}

		if ( ! $this->has_column( 'device_label' ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, PluginCheck.Security.DirectDB.UnescapedDBParameter -- Schema corrections require direct queries.
			$wpdb->query( "ALTER TABLE {$this->table} ADD COLUMN device_label varchar(80) DEFAULT ''" );
		}
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

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$referrer   = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		$device     = $this->describe_device_from_user_agent( $user_agent );
		$is_bot     = $this->detect_is_bot( $user_agent ) ? 1 : 0;
		$now        = current_time( 'mysql' );

		// Do not log URLs that match an ignore pattern.
		if ( $this->is_url_ignored( $request ) ) {
			return;
		}

		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared -- Each request URI must be checked against the custom 404 log table directly, table name is safe.
		$row = $wpdb->get_row(
			$wpdb->prepare(
				'SELECT id, hits, user_agent, is_bot FROM ' . $this->table . ' WHERE request_uri = %s LIMIT 1',
				$request
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared

		if ( $row ) {
			$update_data = array(
				'hits'         => (int) $row->hits + 1,
				'last_seen'    => $now,
				'user_agent'   => $user_agent ?: $row->user_agent,
				'device_label' => $device,
			);

			// Update referrer if not empty and we don't have one yet
			if ( $referrer ) {
				$update_data['referrer'] = $referrer;
			}

			// If this hit is from a bot and the entry wasn't already marked as bot, update it
			if ( $is_bot && ! (int) $row->is_bot ) {
				$update_data['is_bot'] = 1;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Updating the custom 404 log table requires a direct query.
			$wpdb->update(
				$this->table,
				$update_data,
				array( 'id' => $row->id )
			);

			// Check if we should send a notification
			$new_hits = (int) $row->hits + 1;
			$this->maybe_send_notification( $request, $new_hits, (int) $row->id );
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Inserting new 404 rows requires writing to the custom table directly.
			$wpdb->insert(
				$this->table,
				array(
					'request_uri'  => $request,
					'user_agent'   => $user_agent,
					'device_label' => $device,
					'hits'         => 1,
					'last_seen'    => $now,
					'first_seen'   => $now,
					'is_bot'       => $is_bot,
					'referrer'     => $referrer,
				),
				array( '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s' )
			);
		}
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
		global $wpdb;

		// Check if patterns table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->patterns_table ) );
		if ( $this->patterns_table !== $has_table ) {
			return false;
		}

		// Get all patterns
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$patterns = $wpdb->get_results( "SELECT pattern, is_regex FROM {$this->patterns_table}" );

		if ( ! $patterns ) {
			return false;
		}

		foreach ( $patterns as $p ) {
			$pattern = isset( $p->pattern ) ? (string) $p->pattern : '';

			if ( '' === $pattern ) {
				continue;
			}

			if ( ! empty( $p->is_regex ) ) {
				// Regex pattern.
				$regex = '/' . str_replace( '/', '\\/', $pattern ) . '/i';
				if ( $this->regex_matches_safely( $regex, $request_uri ) ) {
					return true;
				}
			} else {
				// Simple wildcard pattern - convert * to regex.
				if ( false !== strpos( $pattern, '*' ) ) {
					$regex = '/^' . str_replace( array( '\\*', '/' ), array( '.*', '\\/' ), preg_quote( $pattern, '/' ) ) . '$/i';
					if ( $this->regex_matches_safely( $regex, $request_uri ) ) {
						return true;
					}
				} elseif ( $request_uri === $pattern ) {
					// Exact match.
					return true;
				}
			}
		}

		return false;
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

		// Check if patterns table exists
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$has_table = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $this->patterns_table ) );
		if ( $this->patterns_table !== $has_table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return $wpdb->get_results( "SELECT * FROM {$this->patterns_table} ORDER BY created_at DESC" );
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
		return (bool) $wpdb->delete(
			$this->patterns_table,
			array( 'id' => $id ),
			array( '%d' )
		);
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
