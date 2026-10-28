<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * Data is only removed when the site opted in via the
 * "Delete data on uninstall" setting. On multisite every site is checked
 * and cleaned on its own opt-in; network-wide data (user meta, the backups
 * directory under wp-content) follows the main site's choice.
 *
 * @package Saman\SEO
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Whether the current site opted in to data removal.
 *
 * @return bool
 */
function saman_seo_uninstall_opted_in() {
	return (bool) get_option( 'SAMAN_SEO_delete_data_on_uninstall', '0' );
}

/**
 * Cron hooks the plugin schedules (wp_schedule_event / wp_schedule_single_event).
 *
 * @return string[]
 */
function saman_seo_uninstall_cron_hooks() {
	return array(
		'SAMAN_SEO_404_cleanup',
		'saman_seo_daily_maintenance',
		'saman_seo_weekly_report',
		'SAMAN_SEO_link_health_scan',
		'SAMAN_SEO_link_health_process',
		'SAMAN_SEO_link_health_single',
		'SAMAN_SEO_indexnow_submit',
		'SAMAN_SEO_sitemap_cron',
		'saman_seo_check_youtube_thumbnails',
	);
}

/**
 * Build an escaped "starts with" LIKE pattern.
 *
 * @param string $prefix Literal prefix.
 * @return string
 */
function saman_seo_uninstall_like_prefix( $prefix ) {
	global $wpdb;

	return $wpdb->esc_like( $prefix ) . '%';
}

/**
 * Remove the plugin's data from the current site.
 *
 * @return void
 */
function saman_seo_uninstall_site() {
	global $wpdb;

	$tables = array(
		$wpdb->prefix . 'SAMAN_SEO_redirects',
		$wpdb->prefix . 'SAMAN_SEO_404_log',
		$wpdb->prefix . 'SAMAN_SEO_404_ignore_patterns',
		$wpdb->prefix . 'SAMAN_SEO_link_health',
		$wpdb->prefix . 'SAMAN_SEO_link_scans',
		$wpdb->prefix . 'SAMAN_SEO_indexnow_log',
		$wpdb->prefix . 'SAMAN_SEO_custom_assistants',
		$wpdb->prefix . 'SAMAN_SEO_assistant_usage',
	);

	foreach ( $tables as $table ) {
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.DirectDatabaseQuery.SchemaChange,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Uninstall cleanup requires direct queries.
		$wpdb->query( "DROP TABLE IF EXISTS {$table}" );
	}

	// Scheduled events. wp_unschedule_hook() clears every instance whatever
	// its arguments; wp_clear_scheduled_hook() only matches empty args.
	foreach ( saman_seo_uninstall_cron_hooks() as $hook ) {
		if ( function_exists( 'wp_unschedule_hook' ) ) {
			wp_unschedule_hook( $hook );
		} else {
			wp_clear_scheduled_hook( $hook );
		}
	}

	// Plugin options, including caches and migration flags such as
	// SAMAN_SEO_analytics_opt_in_migrated. Both spellings are listed so the
	// match does not depend on the options table collation.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$options = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			saman_seo_uninstall_like_prefix( 'SAMAN_SEO_' ),
			saman_seo_uninstall_like_prefix( 'saman_seo_' )
		)
	);
	foreach ( (array) $options as $option ) {
		delete_option( $option );
	}

	// Transients (and their timeouts) whose keys carry the plugin prefix,
	// e.g. _transient_SAMAN_SEO_audit_results, _transient_saman_seo_gsc_*.
	$transient_patterns = array();
	foreach ( array( '_transient_', '_transient_timeout_', '_site_transient_', '_site_transient_timeout_' ) as $transient_prefix ) {
		foreach ( array( 'SAMAN_SEO_', 'saman_seo_' ) as $plugin_prefix ) {
			$transient_patterns[] = saman_seo_uninstall_like_prefix( $transient_prefix . $plugin_prefix );
		}
	}
	$where = implode( ' OR ', array_fill( 0, count( $transient_patterns ), 'option_name LIKE %s' ) );
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- Uninstall cleanup; $where holds only %s placeholders.
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->options} WHERE {$where}", $transient_patterns ) );

	// Post meta: _SAMAN_SEO_meta, _SAMAN_SEO_link_cache_ver, the per-type
	// schema fields (_saman_seo_course_*, _saman_seo_book_*, ...) and the rest.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
			saman_seo_uninstall_like_prefix( '_saman_seo_' ),
			saman_seo_uninstall_like_prefix( '_SAMAN_SEO_' )
		)
	);

	// Custom capabilities granted to roles (roles are stored per site).
	$roles = function_exists( 'wp_roles' ) ? wp_roles() : null;
	if ( $roles && ! empty( $roles->role_objects ) ) {
		foreach ( $roles->role_objects as $role ) {
			if ( $role->has_cap( 'manage_seopilot_links' ) ) {
				$role->remove_cap( 'manage_seopilot_links' );
			}
		}
	}
}

/**
 * Remove data shared by the whole install: user meta and the backups
 * directory under wp-content.
 *
 * @return void
 */
function saman_seo_uninstall_global() {
	global $wpdb;

	// User meta such as SAMAN_SEO_ai_notice_dismissed.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE %s OR meta_key LIKE %s",
			saman_seo_uninstall_like_prefix( 'SAMAN_SEO_' ),
			saman_seo_uninstall_like_prefix( 'saman_seo_' )
		)
	);

	saman_seo_uninstall_delete_backups();
}

/**
 * Delete wp-content/saman-seo-backups/ (htaccess backups).
 *
 * WP_Filesystem's recursive delete is not used on purpose: it walks into
 * symlinked directories (dirlist() types them as 'd'), so a link planted in
 * the backups folder could make uninstall delete files elsewhere. The
 * walker below never leaves the directory and never follows symlinks.
 *
 * @return void
 */
function saman_seo_uninstall_delete_backups() {
	if ( ! defined( 'WP_CONTENT_DIR' ) ) {
		return;
	}

	$dir = WP_CONTENT_DIR . '/saman-seo-backups';

	if ( ! is_dir( $dir ) || is_link( $dir ) ) {
		return;
	}

	$content_root = realpath( WP_CONTENT_DIR );
	$target       = realpath( $dir );

	// Only ever delete the exact backups directory inside wp-content.
	if ( false === $content_root || false === $target || dirname( $target ) !== $content_root || 'saman-seo-backups' !== basename( $target ) ) {
		return;
	}

	saman_seo_uninstall_rmdir( $target, $target );
}

/**
 * Recursively remove a directory without following symlinks.
 *
 * @param string $dir  Directory to remove.
 * @param string $root Directory the removal must stay inside.
 * @return void
 */
function saman_seo_uninstall_rmdir( $dir, $root ) {
	$entries = scandir( $dir );

	if ( false === $entries ) {
		return;
	}

	foreach ( $entries as $entry ) {
		if ( '.' === $entry || '..' === $entry ) {
			continue;
		}

		$path = $dir . DIRECTORY_SEPARATOR . $entry;

		if ( is_link( $path ) || is_file( $path ) ) {
			// A symlink is removed itself; its target is left alone.
			wp_delete_file( $path );
			continue;
		}

		if ( is_dir( $path ) ) {
			$real = realpath( $path );

			if ( false !== $real && 0 === strpos( $real, $root . DIRECTORY_SEPARATOR ) ) {
				saman_seo_uninstall_rmdir( $real, $root );
			}
		}
	}

	// A non-empty or locked directory must not abort uninstall, hence "@".
	@rmdir( $dir ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_rmdir,WordPress.PHP.NoSilencedErrors.Discouraged -- Symlink-safe walker, see saman_seo_uninstall_delete_backups().
}

if ( is_multisite() ) {
	// Network-wide data follows the main site's opt-in; read it before the
	// per-site pass deletes that option.
	$saman_seo_main_opted_in = (bool) get_blog_option( get_main_site_id(), 'SAMAN_SEO_delete_data_on_uninstall', '0' );
	$saman_seo_any_cleaned   = false;
	$saman_seo_offset        = 0;

	do {
		$saman_seo_site_ids = get_sites(
			array(
				'fields' => 'ids',
				'number' => 100,
				'offset' => $saman_seo_offset,
			)
		);

		foreach ( $saman_seo_site_ids as $saman_seo_site_id ) {
			switch_to_blog( (int) $saman_seo_site_id );

			if ( saman_seo_uninstall_opted_in() ) {
				saman_seo_uninstall_site();
				$saman_seo_any_cleaned = true;
			}

			restore_current_blog();
		}

		$saman_seo_offset += 100;
	} while ( count( $saman_seo_site_ids ) === 100 );

	if ( $saman_seo_main_opted_in ) {
		saman_seo_uninstall_global();
	}

	if ( $saman_seo_any_cleaned || $saman_seo_main_opted_in ) {
		wp_cache_flush();
	}

	return;
}

// Only remove data if the user explicitly opted in.
if ( ! saman_seo_uninstall_opted_in() ) {
	return;
}

saman_seo_uninstall_site();
saman_seo_uninstall_global();

wp_cache_flush();
