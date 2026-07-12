<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package Saman\SEO
 */

// If uninstall not called from WordPress, exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Only remove data if the user explicitly opted in.
if ( ! get_option( 'SAMAN_SEO_delete_data_on_uninstall', '0' ) ) {
	return;
}

// Ensure the plugin constants are available.
if ( ! defined( 'SAMAN_SEO_PATH' ) ) {
	define( 'SAMAN_SEO_PATH', plugin_dir_path( __FILE__ ) );
}

// Load the plugin so service classes can be used.
if ( file_exists( SAMAN_SEO_PATH . 'vendor/autoload.php' ) ) {
	require_once SAMAN_SEO_PATH . 'vendor/autoload.php';
}
require_once SAMAN_SEO_PATH . 'includes/helpers.php';

/**
 * Drop plugin tables and clean up options and post meta.
 *
 * @return void
 */
function saman_seo_uninstall_cleanup() {
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

	// Delete all plugin options.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$options = $wpdb->get_col( "SELECT option_name FROM {$wpdb->options} WHERE option_name LIKE 'SAMAN_SEO_%'" );
	foreach ( $options as $option ) {
		delete_option( $option );
	}

	// Delete plugin post meta.
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE '_saman_seo_%'" );

	// Clean up scheduled events.
	wp_clear_scheduled_hook( 'SAMAN_SEO_404_cleanup' );
	wp_clear_scheduled_hook( 'saman_seo_daily_maintenance' );
	wp_clear_scheduled_hook( 'SAMAN_SEO_link_health_scan' );
	wp_clear_scheduled_hook( 'SAMAN_SEO_link_health_process' );
	wp_clear_scheduled_hook( 'SAMAN_SEO_link_health_single' );
	wp_clear_scheduled_hook( 'SAMAN_SEO_sitemap_cron' );

	// Delete plugin transients missed by the option LIKE above (the leading
	// _transient_ / _transient_timeout_ prefixes defeat that match).
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Uninstall cleanup requires direct queries.
	$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%SAMAN\_SEO\_%' OR option_name LIKE '\_transient\_timeout\_%SAMAN\_SEO\_%' OR option_name LIKE '\_transient\_%saman\_seo\_%' OR option_name LIKE '\_transient\_timeout\_%saman\_seo\_%'" );

	wp_cache_flush();
}

saman_seo_uninstall_cleanup();
