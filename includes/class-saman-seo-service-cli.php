<?php
/**
 * WP-CLI integration.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
			'saman-seo redirects',
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
					$data = array_map( array( $this, 'sanitize_redirect_row' ), $this->get_redirect_rows() );
					\WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $data, array( 'id', 'source', 'target', 'status_code', 'hits', 'last_hit' ) );
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
					$redirects    = array_map(
						array( $this, 'sanitize_redirect_row_for_export' ),
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

					// Through the manager, like the admin: it normalizes sources, validates regex
					// rules and skips duplicates, so importing twice no longer doubles every row.
					$manager  = new Redirect_Manager();
					$imported = 0;
					$skipped  = 0;

					foreach ( $data as $row ) {
						$row    = is_array( $row ) ? $row : array();
						$result = $manager->create_redirect(
							sanitize_text_field( $row['source'] ?? '' ),
							esc_url_raw( $row['target'] ?? '' ),
							absint( $row['status_code'] ?? 301 ),
							array_intersect_key( $row, array_flip( array( 'is_regex', 'priority', 'group_name', 'start_date', 'end_date', 'notes' ) ) )
						);

						if ( is_wp_error( $result ) ) {
							++$skipped;
							\WP_CLI::warning( sprintf( '%s: %s', $row['source'] ?? '', $result->get_error_message() ) );
						} else {
							++$imported;
						}
					}

					Redirect_Manager::flush_cache();

					\WP_CLI::success( sprintf( 'Imported %d redirects, skipped %d.', $imported, $skipped ) );
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
						$table = esc_sql( $wpdb->prefix . 'SAMAN_SEO_redirects' );
						$query = "SELECT id, source, target, status_code, hits, last_hit, is_regex, priority, group_name, start_date, end_date, notes FROM `{$table}`";
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,PluginCheck.Security.DirectDB.UnescapedDBParameter,WordPress.DB.PreparedSQL.NotPrepared -- Table name already sanitized via esc_sql(), and results are cached immediately after.
						$raw_data = $wpdb->get_results( $query, ARRAY_A );

						$data = array_map( array( $this, 'sanitize_redirect_row' ), $raw_data );

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
					return array(
						'id'          => isset( $row['id'] ) ? (int) $row['id'] : 0,
						'source'      => isset( $row['source'] ) ? sanitize_text_field( $row['source'] ) : '',
						'target'      => isset( $row['target'] ) ? esc_url_raw( $row['target'] ) : '',
						'status_code' => isset( $row['status_code'] ) ? (int) $row['status_code'] : 301,
						'hits'        => isset( $row['hits'] ) ? (int) $row['hits'] : 0,
						'last_hit'    => isset( $row['last_hit'] ) ? sanitize_text_field( $row['last_hit'] ) : '',
						'is_regex'    => ! empty( $row['is_regex'] ) ? 1 : 0,
						'priority'    => isset( $row['priority'] ) ? (int) $row['priority'] : null,
						'group_name'  => isset( $row['group_name'] ) ? sanitize_text_field( $row['group_name'] ) : '',
						'start_date'  => isset( $row['start_date'] ) ? sanitize_text_field( $row['start_date'] ) : null,
						'end_date'    => isset( $row['end_date'] ) ? sanitize_text_field( $row['end_date'] ) : null,
						'notes'       => isset( $row['notes'] ) ? sanitize_textarea_field( $row['notes'] ) : null,
					);
				}

				/**
				 * Sanitize fields specifically for export payload.
				 *
				 * @param array $row Row data.
				 *
				 * @return array
				 */
				private function sanitize_redirect_row_for_export( array $row ) {
					// Everything import() reads back, so a regex rule stays a regex rule.
					return array_filter(
						array(
							'source'      => isset( $row['source'] ) ? sanitize_text_field( $row['source'] ) : '',
							'target'      => isset( $row['target'] ) ? esc_url_raw( $row['target'] ) : '',
							'status_code' => isset( $row['status_code'] ) ? (int) $row['status_code'] : 301,
							'is_regex'    => ! empty( $row['is_regex'] ) ? 1 : 0,
							'priority'    => $row['priority'] ?? null,
							'group_name'  => $row['group_name'] ?? '',
							'start_date'  => $row['start_date'] ?? null,
							'end_date'    => $row['end_date'] ?? null,
							'notes'       => $row['notes'] ?? null,
						),
						static function ( $value ) {
							return null !== $value && '' !== $value;
						}
					);
				}
			}
		);

		\WP_CLI::add_command(
			'saman-seo profile',
			new class() extends \WP_CLI_Command {

				/**
				 * Profile autoloaded options and cron events.
				 *
				 * Every autoloaded option is loaded on every request, so a few
				 * bloated ones can dominate TTFB. This reports the heaviest
				 * entries plus the plugin's scheduled events.
				 *
				 * ## OPTIONS
				 *
				 * [--top=<count>]
				 * : How many of the largest autoloaded options to list. Default 15.
				 *
				 * [--all-plugins]
				 * : Include options from every plugin, not just Saman SEO.
				 *
				 * @param array $args       Positional args.
				 * @param array $assoc_args Associative args.
				 */
				public function __invoke( $args, $assoc_args ) {
					global $wpdb;

					$top = isset( $assoc_args['top'] ) ? max( 1, (int) $assoc_args['top'] ) : 15;
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Diagnostic read for CLI profiling.
					$rows = $wpdb->get_results(
						"SELECT option_name, LENGTH(option_value) AS bytes FROM {$wpdb->options} WHERE autoload = 'yes' ORDER BY bytes DESC",
						ARRAY_A
					);

					if ( ! $rows ) {
						\WP_CLI::warning( 'No autoloaded options found.' );
						return;
					}

					$total_bytes = array_sum( array_column( $rows, 'bytes' ) );
					$ours        = array_filter(
						$rows,
						static function ( $row ) {
							return 0 === strpos( (string) $row['option_name'], 'SAMAN_SEO_' )
								|| 0 === strpos( (string) $row['option_name'], 'saman_seo_' );
						}
					);
					$ours_bytes  = array_sum( array_column( $ours, 'bytes' ) );

					\WP_CLI::log(
						sprintf(
							'Autoloaded options: %d entries, %s total. Saman SEO share: %d entries, %s.',
							count( $rows ),
							size_format( $total_bytes ),
							count( $ours ),
							size_format( $ours_bytes )
						)
					);

					$listing = empty( $assoc_args['all-plugins'] ) ? array_slice( $ours, 0, $top ) : array_slice( $rows, 0, $top );

					if ( ! $listing ) {
						\WP_CLI::log( 'No Saman SEO options are autoloaded.' );
					} else {
						$items = array_map(
							static function ( $row ) {
								return array(
									'option' => (string) $row['option_name'],
									'size'   => size_format( (float) $row['bytes'] ),
								);
							},
							$listing
						);
						\WP_CLI\Utils\format_items( 'table', $items, array( 'option', 'size' ) );
					}

					$cron   = _get_cron_array();
					$events = array();

					foreach ( $cron as $timestamp => $hooks ) {
						foreach ( $hooks as $hook => $instances ) {
							foreach ( $instances as $signature => $instance ) {
								if ( 0 !== strpos( (string) $hook, 'SAMAN_SEO_' ) && 0 !== strpos( (string) $hook, 'saman_seo_' ) ) {
									continue;
								}

								$events[] = array(
									'hook'     => (string) $hook,
									'next_run' => get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $timestamp ), 'Y-m-d H:i:s' ),
									'interval' => empty( $instance['interval'] ) ? 'single' : (string) $instance['interval'],
								);
							}
						}
					}

					if ( ! $events ) {
						\WP_CLI::log( 'No Saman SEO cron events are scheduled.' );
						return;
					}

					\WP_CLI::log( '' );
					\WP_CLI::log( 'Scheduled Saman SEO events:' );
					\WP_CLI\Utils\format_items( 'table', $events, array( 'hook', 'next_run', 'interval' ) );

					// Flag the classic silent failure: an event on a schedule
					// that no longer exists never fires.
					$schedules = wp_get_schedules();
					foreach ( $events as $event ) {
						if ( 'single' !== $event['interval'] && ! isset( $schedules[ $event['interval'] ] ) ) {
							\WP_CLI::warning( sprintf( "Event '%s' uses unknown interval '%s' and will never run.", $event['hook'], $event['interval'] ) );
						}
					}
				}
			}
		);
	}
}
