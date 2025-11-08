<?php
/**
 * WP-CLI integration.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

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
					global $wpdb;
					$table = $wpdb->prefix . 'wpseopilot_redirects';
					$data  = $wpdb->get_results( "SELECT id, source, target, status_code, hits, last_hit FROM {$table}" );
					\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', array_map( 'get_object_vars', $data ), [ 'id', 'source', 'target', 'status_code', 'hits', 'last_hit' ] );
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
					global $wpdb;
					$table     = $wpdb->prefix . 'wpseopilot_redirects';
					$redirects = $wpdb->get_results( "SELECT source, target, status_code FROM {$table}", ARRAY_A );
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

					\WP_CLI::success( sprintf( 'Imported %d redirects.', count( $data ) ) );
				}
			}
		);
	}
}
