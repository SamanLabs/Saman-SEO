<?php
/**
 * Improvements Hub REST Controller
 *
 * Aggregates the state of every optimization the plugin offers into a single
 * payload so the Improvements page can show "here is everything we can help
 * with", each item's activation status, its measured metrics where available,
 * and a jump-off point to configure it.
 *
 * @package Saman\SEO
 * @since 2.1.0
 */

namespace Saman\SEO\Api;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for the Improvements hub.
 */
class Improvements_Controller extends REST_Controller {

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/improvements',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_improvements' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);
	}

	/**
	 * Build the full improvements payload.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_improvements( $request ) {
		$data = array(
			'groups' => array(
				array(
					'id'    => 'speed',
					'label' => __( 'Speed', 'saman-seo' ),
					'items' => array(
						$this->item_page_cache(),
						$this->item_image_seo(),
					),
				),
				array(
					'id'    => 'traffic',
					'label' => __( 'Traffic & Insights', 'saman-seo' ),
					'items' => array(
						$this->item_search_console(),
						$this->item_weekly_report(),
						$this->item_indexnow(),
					),
				),
				array(
					'id'    => 'content',
					'label' => __( 'Content & Crawling', 'saman-seo' ),
					'items' => array(
						$this->item_sitemap(),
						$this->item_llm_txt(),
						$this->item_seo_scores(),
						$this->item_content_coverage(),
					),
				),
				array(
					'id'    => 'structure',
					'label' => __( 'Site Structure', 'saman-seo' ),
					'items' => array(
						$this->item_redirects(),
						$this->item_404_log(),
						$this->item_link_health(),
						$this->item_internal_links(),
						$this->item_breadcrumbs(),
					),
				),
				array(
					'id'    => 'presence',
					'label' => __( 'Rich Results & Presence', 'saman-seo' ),
					'items' => array(
						$this->item_schema(),
						$this->item_social_cards(),
						$this->item_local_seo(),
					),
				),
			),
			'counts' => array(
				'on'        => 0,
				'partial'   => 0,
				'attention' => 0,
				'off'       => 0,
			),
		);

		foreach ( $data['groups'] as $group ) {
			foreach ( $group['items'] as $item ) {
				++$data['counts'][ $item['status'] ];
			}
		}

		return $this->success( $data );
	}

	/**
	 * Item builders.
	 */

	/**
	 * Static page cache with measured TTFB improvement.
	 *
	 * @return array<string,mixed>
	 */
	private function item_page_cache() {
		$plugin  = \Saman\SEO\Plugin::instance();
		$cache   = $plugin->get( 'page_cache' );
		$status  = 'off';
		$metrics = array();

		if ( $cache instanceof \Saman\SEO\Service\Page_Cache ) {
			$conflicts = $cache->detect_conflicts();

			if ( ! empty( $conflicts ) ) {
				$status    = 'attention';
				$metrics[] = array(
					'label' => __( 'Conflict', 'saman-seo' ),
					'value' => implode( ', ', array_values( $conflicts ) ),
				);
			} elseif ( $cache->is_enabled() ) {
				$status = 'on';

				$stats = $cache->get_stats();

				if ( (int) $stats['ttfb_cold_ms'] > 0 && (int) $stats['ttfb_warm_ms'] > 0 ) {
					$speedup = max( 1, round( $stats['ttfb_cold_ms'] / max( 1, $stats['ttfb_warm_ms'] ) ) );

					$metrics[] = array(
						'label' => __( 'TTFB', 'saman-seo' ),
						/* translators: 1: before ms, 2: after ms */
						'value' => sprintf( __( '%1$dms → %2$dms (%3$d×)', 'saman-seo' ), (int) $stats['ttfb_cold_ms'], (int) $stats['ttfb_warm_ms'], (int) $speedup ),
					);
				}

				if ( (float) $stats['hit_rate'] > 0 ) {
					$metrics[] = array(
						'label' => __( 'Hit rate', 'saman-seo' ),
						'value' => sprintf( '%s%%', $stats['hit_rate'] ),
					);
				}

				$metrics[] = array(
					'label' => __( 'Pages cached', 'saman-seo' ),
					'value' => number_format_i18n( (int) $stats['pages_cached'] ),
				);
			}
		}

		return array(
			'id'          => 'page_cache',
			'title'       => __( 'Static Page Cache', 'saman-seo' ),
			'description' => __( 'Snapshot public pages as static HTML and serve them instantly. Rebuilt automatically when content changes.', 'saman-seo' ),
			'impact'      => 'high',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'dashboard',
		);
	}

	/**
	 * Image SEO auto-alt.
	 *
	 * @return array<string,mixed>
	 */
	private function item_image_seo() {
		$settings = get_option( 'SAMAN_SEO_image_seo_settings', array() );
		$active   = \Saman\SEO\Helpers\module_enabled( 'image_seo' )
			&& is_array( $settings ) && ! empty( $settings['auto_alt'] );

		return array(
			'id'          => 'image_seo',
			'title'       => __( 'Image SEO (Auto Alt Text)', 'saman-seo' ),
			'description' => __( 'Automatically audit and fill missing alt text so images rank in Google Images and pass accessibility checks.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $active ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'image-seo',
		);
	}

	/**
	 * Google Search Console connection.
	 *
	 * @return array<string,mixed>
	 */
	private function item_search_console() {
		$gsc     = \Saman\SEO\Plugin::instance()->get( 'search_console' );
		$status  = 'off';
		$metrics = array();

		if ( $gsc instanceof \Saman\SEO\Service\Search_Console ) {
			if ( $gsc->is_connected() ) {
				$status = 'on';

				$deltas = $gsc->get_weekly_deltas();

				if ( ! is_wp_error( $deltas ) && isset( $deltas['current']['clicks'] ) ) {
					$metrics[] = array(
						'label' => __( 'Clicks this week', 'saman-seo' ),
						'value' => number_format_i18n( (int) $deltas['current']['clicks'] ),
					);

					$metrics[] = array(
						'label' => __( 'Impressions', 'saman-seo' ),
						'value' => number_format_i18n( (int) $deltas['current']['impressions'] ),
					);
				}
			} elseif ( $gsc->is_configured() ) {
				$status = 'partial';
			}
		}

		return array(
			'id'          => 'search_console',
			'title'       => __( 'Google Search Console', 'saman-seo' ),
			'description' => __( 'Ground your dashboard in real clicks, impressions, and rankings straight from Google.', 'saman-seo' ),
			'impact'      => 'high',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'dashboard',
		);
	}

	/**
	 * Weekly email digest schedule.
	 *
	 * @return array<string,mixed>
	 */
	private function item_weekly_report() {
		$enabled = '1' === (string) get_option( \Saman\SEO\Service\Weekly_Report::OPTION_ENABLED, '0' );
		$next    = wp_next_scheduled( \Saman\SEO\Service\Weekly_Report::HOOK );

		$metrics = array();

		if ( $enabled && $next ) {
			$metrics[] = array(
				'label' => __( 'Next digest', 'saman-seo' ),
				'value' => sprintf(
					/* translators: %s: day name */
					__( 'Every %s', 'saman-seo' ),
					ucfirst( (string) get_option( \Saman\SEO\Service\Weekly_Report::OPTION_DAY, 'monday' ) )
				),
			);
		}

		return array(
			'id'          => 'weekly_report',
			'title'       => __( 'Weekly Email Digest', 'saman-seo' ),
			'description' => __( 'A short Monday-morning email: score trend, errors, broken links, and search movement.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $enabled ? 'on' : 'off',
			'metrics'     => $metrics,
			'view'        => 'dashboard',
		);
	}

	/**
	 * IndexNow instant indexing.
	 *
	 * @return array<string,mixed>
	 */
	private function item_indexnow() {
		$settings = get_option( 'SAMAN_SEO_indexnow_settings', array() );
		$enabled  = is_array( $settings ) && ! empty( $settings['enabled'] );
		$has_key  = is_array( $settings ) && ! empty( $settings['api_key'] );

		$status = 'off';

		if ( $enabled && $has_key ) {
			$status = 'on';
		} elseif ( $enabled || $has_key ) {
			$status = 'partial';
		}

		return array(
			'id'          => 'indexnow',
			'title'       => __( 'Instant Indexing (IndexNow)', 'saman-seo' ),
			'description' => __( 'Ping Bing, Yandex, and Seznam the moment you publish or update, instead of waiting for crawls.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $status,
			'metrics'     => array(),
			'view'        => 'instant-indexing',
		);
	}

	/**
	 * XML sitemap enhancement.
	 *
	 * @return array<string,mixed>
	 */
	private function item_sitemap() {
		global $wpdb;

		$enabled = \Saman\SEO\Helpers\module_enabled( 'sitemap' );
		$metrics = array();

		if ( $enabled ) {
			$total_urls = 0;
			$post_types = get_option( 'SAMAN_SEO_sitemap_post_types', array( 'post', 'page' ) );

			if ( ! is_array( $post_types ) || empty( $post_types ) ) {
				$post_types = array( 'post', 'page' );
			}

			foreach ( $post_types as $pt ) {
				$count = wp_count_posts( $pt );

				if ( $count && isset( $count->publish ) ) {
					$total_urls += (int) $count->publish;
				}
			}

			$metrics[] = array(
				'label' => __( 'URLs in sitemap', 'saman-seo' ),
				'value' => number_format_i18n( $total_urls ),
			);
		}

		unset( $wpdb );

		return array(
			'id'          => 'sitemap',
			'title'       => __( 'XML Sitemap', 'saman-seo' ),
			'description' => __( 'Help search engines discover every important page, with automatic pings on publish.', 'saman-seo' ),
			'impact'      => 'high',
			'status'      => $enabled ? 'on' : 'off',
			'metrics'     => $metrics,
			'view'        => 'sitemap',
		);
	}

	/**
	 * LLM.txt generator for AI crawlers.
	 *
	 * @return array<string,mixed>
	 */
	private function item_llm_txt() {
		return array(
			'id'          => 'llm_txt',
			'title'       => __( 'LLM.txt for AI Search', 'saman-seo' ),
			'description' => __( 'Publish a curated, markdown index of your site for AI assistants and answer engines.', 'saman-seo' ),
			'impact'      => 'low',
			'status'      => \Saman\SEO\Helpers\module_enabled( 'llm_txt' ) ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'sitemap',
		);
	}

	/**
	 * Average SEO score from the dashboard cache when warm.
	 *
	 * @return array<string,mixed>
	 */
	private function item_seo_scores() {
		$cached = get_transient( 'SAMAN_SEO_dashboard_seo_score' );

		$status  = 'partial';
		$metrics = array();

		if ( is_array( $cached ) && isset( $cached['score'] ) ) {
			$score = (int) $cached['score'];

			$status = $score >= 80 ? 'on' : ( $score >= 60 ? 'partial' : 'attention' );

			$metrics[] = array(
				'label' => __( 'Average score', 'saman-seo' ),
				'value' => sprintf( '%d/100', $score ),
			);

			if ( ! empty( $cached['issues'] ) ) {
				$metrics[] = array(
					'label' => __( 'Posts to improve', 'saman-seo' ),
					'value' => number_format_i18n( (int) $cached['issues'] ),
				);
			}
		}

		return array(
			'id'          => 'seo_scores',
			'title'       => __( 'On-Page SEO Scores', 'saman-seo' ),
			'description' => __( 'Score every post against SEO best practices and fix issues in bulk.', 'saman-seo' ),
			'impact'      => 'high',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'audit',
		);
	}

	/**
	 * Title/description coverage from the dashboard cache when warm.
	 *
	 * @return array<string,mixed>
	 */
	private function item_content_coverage() {
		$cached = get_transient( 'SAMAN_SEO_content_coverage' );

		$status  = 'partial';
		$metrics = array();

		if ( is_array( $cached ) && isset( $cached['coverage_pct'] ) ) {
			$pct = (int) $cached['coverage_pct'];

			$status = $pct >= 90 ? 'on' : 'partial';

			$metrics[] = array(
				'label' => __( 'Pages optimized', 'saman-seo' ),
				'value' => sprintf( '%d%%', $pct ),
			);
		}

		return array(
			'id'          => 'content_coverage',
			'title'       => __( 'Meta Coverage', 'saman-seo' ),
			'description' => __( 'Every page deserves a title and description. Find and fill the gaps in one sweep.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'bulk-editor',
		);
	}

	/**
	 * Redirect manager activity.
	 *
	 * @return array<string,mixed>
	 */
	private function item_redirects() {
		global $wpdb;

		$enabled = \Saman\SEO\Helpers\module_enabled( 'redirects' );
		$status  = $enabled ? 'on' : 'off';
		$metrics = array();

		$table = $wpdb->prefix . 'SAMAN_SEO_redirects';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence probe.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $enabled && $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate read from custom table.
			$active = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );

			$metrics[] = array(
				'label' => __( 'Active redirects', 'saman-seo' ),
				'value' => number_format_i18n( $active ),
			);

			if ( 0 === $active ) {
				$status = 'partial';
			}
		}

		return array(
			'id'          => 'redirects',
			'title'       => __( 'Redirect Manager', 'saman-seo' ),
			'description' => __( 'Preserve link equity when URLs change, with automatic suggestions from the URL monitor.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'redirects',
		);
	}

	/**
	 * 404 monitoring health.
	 *
	 * @return array<string,mixed>
	 */
	private function item_404_log() {
		global $wpdb;

		$logging = \Saman\SEO\Helpers\module_enabled( '404_log' );

		if ( ! $logging ) {
			return array(
				'id'          => 'errors_404',
				'title'       => __( '404 Monitoring', 'saman-seo' ),
				'description' => __( 'See every dead link visitors and bots hit, then fix it with one click.', 'saman-seo' ),
				'impact'      => 'medium',
				'status'      => 'off',
				'metrics'     => array(),
				'view'        => '404-log',
			);
		}

		$table   = $wpdb->prefix . 'SAMAN_SEO_404_log';
		$metrics = array();
		$status  = 'partial';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence probe.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $exists ) {
			$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Aggregate read from custom table.
			$recent = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE last_seen >= %s", $cutoff )
			);

			$status    = $recent >= 20 ? 'attention' : ( $recent > 0 ? 'partial' : 'on' );
			$metrics[] = array(
				'label' => __( '404s this week', 'saman-seo' ),
				'value' => number_format_i18n( $recent ),
			);
		}

		return array(
			'id'          => 'errors_404',
			'title'       => __( '404 Monitoring', 'saman-seo' ),
			'description' => __( 'See every dead link visitors and bots hit, then fix it with one click.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => '404-log',
		);
	}

	/**
	 * Link health scan results.
	 *
	 * @return array<string,mixed>
	 */
	private function item_link_health() {
		global $wpdb;

		$table   = $wpdb->prefix . 'SAMAN_SEO_link_scans';
		$metrics = array();
		$status  = 'off';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Existence probe.
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );

		if ( $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Single-row read from custom table.
			$scan = $wpdb->get_row(
				"SELECT total_links, broken_links FROM {$table} WHERE status = 'completed' ORDER BY id DESC LIMIT 1",
				ARRAY_A
			);

			if ( $scan ) {
				$broken = (int) $scan['broken_links'];
				$total  = (int) $scan['total_links'];

				$status    = $broken > 0 ? 'attention' : 'on';
				$metrics[] = array(
					'label' => __( 'Broken links', 'saman-seo' ),
					'value' => sprintf( '%d / %s', $broken, number_format_i18n( $total ) ),
				);
			} else {
				$status = 'partial';
			}
		}

		return array(
			'id'          => 'link_health',
			'title'       => __( 'Link Health Scanner', 'saman-seo' ),
			'description' => __( 'Crawl every link on your site and catch broken or redirected ones before visitors do.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => $status,
			'metrics'     => $metrics,
			'view'        => 'link-health',
		);
	}

	/**
	 * Internal linking suggestions engine.
	 *
	 * @return array<string,mixed>
	 */
	private function item_internal_links() {
		return array(
			'id'          => 'internal_links',
			'title'       => __( 'Internal Linking', 'saman-seo' ),
			'description' => __( 'Get smart suggestions that connect related posts and spread ranking power across your site.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => \Saman\SEO\Helpers\module_enabled( 'internal_links' ) ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'internal-linking',
		);
	}

	/**
	 * Breadcrumb navigation + schema.
	 *
	 * @return array<string,mixed>
	 */
	private function item_breadcrumbs() {
		$settings = get_option( 'SAMAN_SEO_breadcrumb_settings', array() );
		$enabled  = is_array( $settings ) && ! empty( $settings['enabled'] );

		return array(
			'id'          => 'breadcrumbs',
			'title'       => __( 'Breadcrumbs', 'saman-seo' ),
			'description' => __( 'Show visitors (and Google) where they are with breadcrumb trails and matching schema.', 'saman-seo' ),
			'impact'      => 'low',
			'status'      => $enabled ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'search-appearance',
		);
	}

	/**
	 * Schema graph output.
	 *
	 * @return array<string,mixed>
	 */
	private function item_schema() {
		$types = 0;

		foreach ( array(
			'SAMAN_SEO_module_schema_video',
			'SAMAN_SEO_module_schema_course',
			'SAMAN_SEO_module_schema_software',
			'SAMAN_SEO_module_schema_book',
			'SAMAN_SEO_module_schema_music',
			'SAMAN_SEO_module_schema_movie',
			'SAMAN_SEO_module_schema_restaurant',
			'SAMAN_SEO_module_schema_service',
			'SAMAN_SEO_module_schema_job_posting',
		) as $key ) {
			if ( '1' === (string) get_option( $key, '1' ) ) {
				++$types;
			}
		}

		$master = '1' === (string) get_option( 'SAMAN_SEO_module_schema', '1' );

		$org = get_option( 'SAMAN_SEO_homepage_organization_name', '' );

		return array(
			'id'          => 'schema',
			'title'       => __( 'Schema Markup (JSON-LD)', 'saman-seo' ),
			'description' => __( 'Structured data that unlocks rich results: articles, FAQs, products, recipes, events, and more.', 'saman-seo' ),
			'impact'      => 'high',
			'status'      => $master ? ( $org ? 'on' : 'partial' ) : 'off',
			'metrics'     => $master
				? array(
					array(
						'label' => __( 'Extra types active', 'saman-seo' ),
						'value' => number_format_i18n( $types ),
					),
				)
				: array(),
			'view'        => 'schema-builder',
		);
	}

	/**
	 * Social share cards.
	 *
	 * @return array<string,mixed>
	 */
	private function item_social_cards() {
		return array(
			'id'          => 'social_cards',
			'title'       => __( 'Social Share Cards', 'saman-seo' ),
			'description' => __( 'Open Graph and Twitter tags with auto-generated card images so shares always look sharp.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => \Saman\SEO\Helpers\module_enabled( 'social_cards' ) ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'search-appearance',
		);
	}

	/**
	 * Local business SEO.
	 *
	 * @return array<string,mixed>
	 */
	private function item_local_seo() {
		return array(
			'id'          => 'local_seo',
			'title'       => __( 'Local Business SEO', 'saman-seo' ),
			'description' => __( 'LocalBusiness schema with hours, address, and geo data for map-pack visibility.', 'saman-seo' ),
			'impact'      => 'medium',
			'status'      => \Saman\SEO\Helpers\module_enabled( 'local_seo' ) ? 'on' : 'off',
			'metrics'     => array(),
			'view'        => 'local-seo',
		);
	}
}
