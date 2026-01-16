<?php
/**
 * WP-CLI integration.
 *
 * @package SamanLabs\SEO
 */

namespace SamanLabs\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * CLI bootstrap.
 */
class CLI {

	/**
	 * Boot CLI commands.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! class_exists( '\WP_CLI' ) ) {
			return;
		}

		\WP_CLI::add_command(
			'wpseopilot redirects',
			new class() extends \WP_CLI_Command {

				/**
				 * List redirects.
				 *
				 * ## OPTIONS
				 *
				 * [--format=<format>]
				 * : Table, json, csv.
				 *
				 * @subcommand list
				 */
					public function list_( $args, $assoc_args ) {
						$data = array_map( [ $this, 'sanitize_redirect_row' ], $this->get_redirect_rows() );
						\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $data, [ 'id', 'source', 'target', 'status_code', 'hits', 'last_hit' ] );
					}

				/**
				 * Export redirects as JSON.
				 *
				 * ## OPTIONS
				 *
				 * <file>
				 * : Destination file path.
				 */
				public function export( $args ) {
					list( $file ) = $args;
					$redirects = array_map(
						[ $this, 'sanitize_redirect_row_for_export' ],
						$this->get_redirect_rows()
					);
					file_put_contents( $file, wp_json_encode( $redirects, JSON_PRETTY_PRINT ) );
					\WP_CLI::success( sprintf( 'Exported %d redirects.', count( $redirects ) ) );
				}

				/**
				 * Import redirects from JSON.
				 *
				 * ## OPTIONS
				 *
				 * <file>
				 * : Source file path.
				 */
				public function import( $args ) {
					list( $file ) = $args;
					if ( ! file_exists( $file ) ) {
						\WP_CLI::error( 'File not found.' );
					}

					$data = json_decode( file_get_contents( $file ), true );
					if ( ! is_array( $data ) ) {
						\WP_CLI::error( 'Invalid JSON.' );
					}

					global $wpdb;
					$table = $wpdb->prefix . 'wpseopilot_redirects';

					foreach ( $data as $row ) {
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Importing redirect rows requires direct writes to the custom table.
						$wpdb->insert(
							$table,
							[
								'source'      => sanitize_text_field( $row['source'] ?? '' ),
								'target'      => esc_url_raw( $row['target'] ?? '' ),
								'status_code' => absint( $row['status_code'] ?? 301 ),
							],
							[ '%s', '%s', '%d' ]
						);
					}

					Redirect_Manager::flush_cache();

					\WP_CLI::success( sprintf( 'Imported %d redirects.', count( $data ) ) );
				}

				/**
				 * Get redirect rows with shared caching.
				 *
				 * @return array[]
				 */
				private function get_redirect_rows() {
					$data = wp_cache_get( Redirect_Manager::CACHE_KEY_CLI, Redirect_Manager::CACHE_GROUP );

					if ( false === $data ) {
						global $wpdb;
						$table = esc_sql( $wpdb->prefix . 'wpseopilot_redirects' );
						$query = "SELECT id, source, target, status_code, hits, last_hit FROM `{$table}`";
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Table name already sanitized via esc_sql(), and results are cached immediately after.
						$raw_data = $wpdb->get_results( $query, ARRAY_A );

						$data = array_map( [ $this, 'sanitize_redirect_row' ], $raw_data );

						wp_cache_set( Redirect_Manager::CACHE_KEY_CLI, $data, Redirect_Manager::CACHE_GROUP, Redirect_Manager::CACHE_TTL );
					}

					return $data;
				}

				/**
				 * Sanitize redirect database row.
				 *
				 * @param array $row Row data.
				 *
				 * @return array
				 */
				private function sanitize_redirect_row( array $row ) {
					return [
						'id'          => isset( $row['id'] ) ? (int) $row['id'] : 0,
						'source'      => isset( $row['source'] ) ? sanitize_text_field( $row['source'] ) : '',
						'target'      => isset( $row['target'] ) ? esc_url_raw( $row['target'] ) : '',
						'status_code' => isset( $row['status_code'] ) ? (int) $row['status_code'] : 301,
						'hits'        => isset( $row['hits'] ) ? (int) $row['hits'] : 0,
						'last_hit'    => isset( $row['last_hit'] ) ? sanitize_text_field( $row['last_hit'] ) : '',
					];
				}

				/**
				 * Sanitize fields specifically for export payload.
				 *
				 * @param array $row Row data.
				 *
				 * @return array
				 */
				private function sanitize_redirect_row_for_export( array $row ) {
					return [
						'source'      => isset( $row['source'] ) ? sanitize_text_field( $row['source'] ) : '',
						'target'      => isset( $row['target'] ) ? esc_url_raw( $row['target'] ) : '',
						'status_code' => isset( $row['status_code'] ) ? (int) $row['status_code'] : 301,
					];
				}
			}
		);
	}
}
