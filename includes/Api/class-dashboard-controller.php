<?php
/**
 * Dashboard REST Controller
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Api;

use WPSEOPilot\Service\Post_Meta;
use function WPSEOPilot\Helpers\calculate_seo_score;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * REST API controller for Dashboard data aggregation.
 */
class Dashboard_Controller extends REST_Controller {

    /**
     * Redirects table name.
     *
     * @var string
     */
    private $redirects_table;

    /**
     * 404 log table name.
     *
     * @var string
     */
    private $log_table;

    /**
     * Constructor.
     */
    public function __construct() {
        global $wpdb;
        $this->redirects_table = $wpdb->prefix . 'wpseopilot_redirects';
        $this->log_table       = $wpdb->prefix . 'wpseopilot_404_log';
    }

    /**
     * Register routes.
     */
    public function register_routes() {
        // Get full dashboard data
        register_rest_route( $this->namespace, '/dashboard', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_dashboard' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get SEO score data
        register_rest_route( $this->namespace, '/dashboard/seo-score', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_seo_score' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get notifications
        register_rest_route( $this->namespace, '/dashboard/notifications', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_notifications' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Dismiss notification
        register_rest_route( $this->namespace, '/dashboard/notifications/(?P<id>[a-zA-Z0-9_-]+)/dismiss', [
            [
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => [ $this, 'dismiss_notification' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );

        // Get content coverage stats
        register_rest_route( $this->namespace, '/dashboard/content-coverage', [
            [
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => [ $this, 'get_content_coverage' ],
                'permission_callback' => [ $this, 'permission_check' ],
            ],
        ] );
    }

    /**
     * Get full dashboard data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_dashboard( $request ) {
        // Check cache first (2 minute cache for dashboard overview)
        $cached = get_transient( 'wpseopilot_dashboard_data' );
        if ( $cached !== false ) {
            return $this->success( $cached );
        }

        $data = [
            'seo_score'        => $this->calculate_overall_seo_score(),
            'content_coverage' => $this->get_content_coverage_data(),
            'sitemap'          => $this->get_sitemap_data(),
            'redirects'        => $this->get_redirects_data(),
            'errors_404'       => $this->get_404_data(),
            'schema'           => $this->get_schema_data(),
            'notifications'    => $this->get_notifications_data(),
        ];

        // Cache for 2 minutes
        set_transient( 'wpseopilot_dashboard_data', $data, 2 * MINUTE_IN_SECONDS );

        return $this->success( $data );
    }

    /**
     * Get SEO score data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_seo_score( $request ) {
        return $this->success( $this->calculate_overall_seo_score() );
    }

    /**
     * Get notifications.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_notifications( $request ) {
        return $this->success( $this->get_notifications_data() );
    }

    /**
     * Dismiss a notification.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function dismiss_notification( $request ) {
        $id = $request->get_param( 'id' );

        $dismissed = get_option( 'wpseopilot_dismissed_notifications', [] );
        if ( ! is_array( $dismissed ) ) {
            $dismissed = [];
        }

        $dismissed[ $id ] = time();
        update_option( 'wpseopilot_dismissed_notifications', $dismissed );

        return $this->success( null, __( 'Notification dismissed.', 'wp-seo-pilot' ) );
    }

    /**
     * Get content coverage data.
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response
     */
    public function get_content_coverage( $request ) {
        return $this->success( $this->get_content_coverage_data() );
    }

    /**
     * Calculate overall SEO score from published posts.
     *
     * @return array
     */
    private function calculate_overall_seo_score() {
        // Check cache first
        $cached = get_transient( 'wpseopilot_dashboard_seo_score' );
        if ( $cached ) {
            return $cached;
        }

        $scores     = [];
        $score_dist = [ 'excellent' => 0, 'good' => 0, 'fair' => 0, 'poor' => 0 ];
        $issues     = 0;

        // Get recent published posts
        $posts = get_posts( [
            'post_type'      => [ 'post', 'page' ],
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        foreach ( $posts as $post ) {
            $score_data = null;
            if ( function_exists( 'WPSEOPilot\Helpers\calculate_seo_score' ) ) {
                $score_data = calculate_seo_score( $post );
            }

            if ( $score_data && isset( $score_data['score'] ) ) {
                $score = $score_data['score'];
                $scores[] = $score;

                if ( $score >= 80 ) {
                    $score_dist['excellent']++;
                } elseif ( $score >= 60 ) {
                    $score_dist['good']++;
                } elseif ( $score >= 40 ) {
                    $score_dist['fair']++;
                } else {
                    $score_dist['poor']++;
                }

                // Count issues
                if ( $score < 80 ) {
                    $issues++;
                }
            }
        }

        $average = count( $scores ) > 0 ? round( array_sum( $scores ) / count( $scores ) ) : 0;

        // Determine level
        $level = 'poor';
        $label = 'Needs Work';
        if ( $average >= 80 ) {
            $level = 'excellent';
            $label = 'Excellent';
        } elseif ( $average >= 60 ) {
            $level = 'good';
            $label = 'Good';
        } elseif ( $average >= 40 ) {
            $level = 'fair';
            $label = 'Fair';
        }

        $result = [
            'score'        => $average,
            'level'        => $level,
            'label'        => $label,
            'distribution' => $score_dist,
            'posts_scored' => count( $scores ),
            'issues'       => $issues,
            'trend'        => $this->get_score_trend(),
        ];

        // Cache for 30 minutes
        set_transient( 'wpseopilot_dashboard_seo_score', $result, 30 * MINUTE_IN_SECONDS );

        return $result;
    }

    /**
     * Get SEO score trend (compare to last week).
     *
     * @return array
     */
    private function get_score_trend() {
        $history = get_option( 'wpseopilot_score_history', [] );
        if ( ! is_array( $history ) ) {
            $history = [];
        }

        // Get current week average
        $current = $this->get_current_week_score();

        // Store current score
        $today = date( 'Y-m-d' );
        $history[ $today ] = $current;

        // Keep only last 30 days
        $cutoff = strtotime( '-30 days' );
        foreach ( $history as $date => $score ) {
            if ( strtotime( $date ) < $cutoff ) {
                unset( $history[ $date ] );
            }
        }
        update_option( 'wpseopilot_score_history', $history );

        // Calculate trend
        $last_week = array_filter( $history, function( $date ) {
            return strtotime( $date ) >= strtotime( '-7 days' ) && strtotime( $date ) < strtotime( '-1 day' );
        }, ARRAY_FILTER_USE_KEY );

        $previous_avg = count( $last_week ) > 0 ? array_sum( $last_week ) / count( $last_week ) : $current;
        $change       = $current - $previous_avg;

        return [
            'direction' => $change > 0 ? 'up' : ( $change < 0 ? 'down' : 'stable' ),
            'change'    => round( abs( $change ) ),
            'history'   => array_slice( $history, -7, 7, true ),
        ];
    }

    /**
     * Get current week's average score.
     *
     * @return int
     */
    private function get_current_week_score() {
        $cached = get_transient( 'wpseopilot_dashboard_seo_score' );
        return $cached['score'] ?? 0;
    }

    /**
     * Get content coverage data.
     *
     * @return array
     */
    private function get_content_coverage_data() {
        // Check cache first (5 minute cache for coverage data)
        $cached = get_transient( 'wpseopilot_content_coverage' );
        if ( $cached !== false ) {
            return $cached;
        }

        global $wpdb;

        // Count posts with SEO meta vs without
        $total_posts = wp_count_posts( 'post' )->publish + wp_count_posts( 'page' )->publish;

        // Count posts with meta title
        $with_title = $wpdb->get_var(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_wpseopilot_meta'
             AND p.post_status = 'publish'
             AND pm.meta_value LIKE '%\"title\"%'
             AND pm.meta_value NOT LIKE '%\"title\":\"\"%'"
        );

        // Count posts with meta description
        $with_desc = $wpdb->get_var(
            "SELECT COUNT(DISTINCT pm.post_id)
             FROM {$wpdb->postmeta} pm
             JOIN {$wpdb->posts} p ON pm.post_id = p.ID
             WHERE pm.meta_key = '_wpseopilot_meta'
             AND p.post_status = 'publish'
             AND pm.meta_value LIKE '%\"description\"%'
             AND pm.meta_value NOT LIKE '%\"description\":\"\"%'"
        );

        // Get posts by day for the last 7 days
        $daily_stats = [];
        for ( $i = 6; $i >= 0; $i-- ) {
            $date = date( 'Y-m-d', strtotime( "-{$i} days" ) );
            $day_label = date( 'D', strtotime( $date ) );

            $optimized = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(DISTINCT pm.post_id)
                 FROM {$wpdb->postmeta} pm
                 JOIN {$wpdb->posts} p ON pm.post_id = p.ID
                 WHERE pm.meta_key = '_wpseopilot_meta'
                 AND p.post_status = 'publish'
                 AND DATE(p.post_date) <= %s
                 AND pm.meta_value LIKE '%\"title\"%'
                 AND pm.meta_value NOT LIKE '%\"title\":\"\"%'",
                $date
            ) );

            $daily_stats[] = [
                'date'      => $date,
                'label'     => $day_label,
                'optimized' => (int) $optimized,
                'total'     => $total_posts,
            ];
        }

        $result = [
            'total'           => (int) $total_posts,
            'with_title'      => (int) $with_title,
            'with_description'=> (int) $with_desc,
            'optimized'       => (int) min( $with_title, $with_desc ),
            'pending'         => (int) ( $total_posts - min( $with_title, $with_desc ) ),
            'coverage_pct'    => $total_posts > 0 ? round( ( min( $with_title, $with_desc ) / $total_posts ) * 100 ) : 0,
            'daily_stats'     => $daily_stats,
        ];

        // Cache for 5 minutes
        set_transient( 'wpseopilot_content_coverage', $result, 5 * MINUTE_IN_SECONDS );

        return $result;
    }

    /**
     * Get sitemap data.
     *
     * @return array
     */
    private function get_sitemap_data() {
        $enabled = get_option( 'wpseopilot_sitemap_enabled', '1' ) === '1';
        $last_regen = get_option( 'wpseopilot_sitemap_last_regenerated', 0 );

        // Count URLs
        $post_types = get_option( 'wpseopilot_sitemap_post_types', [ 'post', 'page' ] );
        if ( ! is_array( $post_types ) ) {
            $post_types = [ 'post', 'page' ];
        }

        $total_urls = 0;
        foreach ( $post_types as $pt ) {
            $count = wp_count_posts( $pt );
            if ( $count ) {
                $total_urls += $count->publish;
            }
        }

        // Add taxonomy URLs
        $taxonomies = get_option( 'wpseopilot_sitemap_taxonomies', [ 'category' ] );
        if ( is_array( $taxonomies ) ) {
            foreach ( $taxonomies as $tax ) {
                $terms = wp_count_terms( [ 'taxonomy' => $tax, 'hide_empty' => true ] );
                if ( ! is_wp_error( $terms ) ) {
                    $total_urls += $terms;
                }
            }
        }

        // Check for validation errors (placeholder - could add actual validation)
        $errors = 0;

        return [
            'enabled'        => $enabled,
            'status'         => $enabled ? ( $errors > 0 ? 'warning' : 'active' ) : 'disabled',
            'status_label'   => $enabled ? ( $errors > 0 ? 'Has Issues' : 'Active' ) : 'Disabled',
            'total_urls'     => $total_urls,
            'last_generated' => $last_regen ? human_time_diff( $last_regen ) . ' ago' : 'Never',
            'last_timestamp' => $last_regen,
            'errors'         => $errors,
            'types_enabled'  => [
                'main'   => $enabled,
                'rss'    => get_option( 'wpseopilot_sitemap_enable_rss', '0' ) === '1',
                'news'   => get_option( 'wpseopilot_sitemap_enable_google_news', '0' ) === '1',
                'llm'    => get_option( 'wpseopilot_enable_llm_txt', '0' ) === '1',
            ],
        ];
    }

    /**
     * Get redirects data.
     *
     * @return array
     */
    private function get_redirects_data() {
        global $wpdb;

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->redirects_table
        ) );

        if ( ! $table_exists ) {
            return [
                'total'          => 0,
                'active'         => 0,
                'hits_today'     => 0,
                'hits_week'      => 0,
                'broken'         => 0,
                'suggestions'    => 0,
                'status'         => 'inactive',
                'top_redirects'  => [],
            ];
        }

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->redirects_table}" );
        $active = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->redirects_table} WHERE status_code IN (301, 302, 307)" );

        // Hits today
        $today = date( 'Y-m-d' );
        $hits_today = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(hits) FROM {$this->redirects_table} WHERE DATE(last_hit) = %s",
            $today
        ) );

        // Hits this week
        $week_start = date( 'Y-m-d', strtotime( '-7 days' ) );
        $hits_week = $wpdb->get_var( $wpdb->prepare(
            "SELECT SUM(hits) FROM {$this->redirects_table} WHERE last_hit >= %s",
            $week_start
        ) );

        // Get pending suggestions
        $suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
        $suggestions_count = is_array( $suggestions ) ? count( $suggestions ) : 0;

        // Top redirects by hits
        $top_redirects = $wpdb->get_results(
            "SELECT source, target, hits, last_hit
             FROM {$this->redirects_table}
             ORDER BY hits DESC
             LIMIT 5",
            ARRAY_A
        );

        return [
            'total'          => (int) $total,
            'active'         => (int) $active,
            'hits_today'     => (int) $hits_today,
            'hits_week'      => (int) $hits_week,
            'broken'         => 0, // Placeholder for broken redirect detection
            'suggestions'    => $suggestions_count,
            'status'         => $active > 0 ? 'active' : 'inactive',
            'top_redirects'  => $top_redirects ?: [],
        ];
    }

    /**
     * Get 404 error data.
     *
     * @return array
     */
    private function get_404_data() {
        global $wpdb;

        $logging_enabled = get_option( 'wpseopilot_enable_404_logging', '1' ) === '1';

        // Check if table exists
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->log_table
        ) );

        if ( ! $table_exists || ! $logging_enabled ) {
            return [
                'enabled'      => $logging_enabled,
                'total'        => 0,
                'last_30_days' => 0,
                'last_7_days'  => 0,
                'status'       => 'inactive',
                'top_errors'   => [],
            ];
        }

        $total = $wpdb->get_var( "SELECT COUNT(*) FROM {$this->log_table}" );

        // Last 30 days
        $thirty_days_ago = date( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
        $last_30 = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table} WHERE last_seen >= %s",
            $thirty_days_ago
        ) );

        // Last 7 days
        $seven_days_ago = date( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
        $last_7 = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table} WHERE last_seen >= %s",
            $seven_days_ago
        ) );

        // Top 404s
        $top_errors = $wpdb->get_results(
            "SELECT request_uri, hits, last_seen, device_label
             FROM {$this->log_table}
             ORDER BY hits DESC
             LIMIT 5",
            ARRAY_A
        );

        return [
            'enabled'      => $logging_enabled,
            'total'        => (int) $total,
            'last_30_days' => (int) $last_30,
            'last_7_days'  => (int) $last_7,
            'status'       => $total > 0 ? 'warning' : 'good',
            'top_errors'   => $top_errors ?: [],
        ];
    }

    /**
     * Get schema markup data.
     *
     * @return array
     */
    private function get_schema_data() {
        $org_name = get_option( 'wpseopilot_homepage_organization_name', '' );
        $local_enabled = get_option( 'wpseopilot_enable_local_seo', '0' ) === '1';

        $schema_types = [ 'Website', 'WebPage' ];
        if ( ! empty( $org_name ) ) {
            $schema_types[] = 'Organization';
        }
        if ( $local_enabled ) {
            $schema_types[] = 'LocalBusiness';
        }

        // Check for posts (Articles schema)
        $has_posts = wp_count_posts( 'post' )->publish > 0;
        if ( $has_posts ) {
            $schema_types[] = 'Article';
        }

        return [
            'status'       => ! empty( $org_name ) || $local_enabled ? 'valid' : 'partial',
            'status_label' => ! empty( $org_name ) || $local_enabled ? 'Valid' : 'Basic',
            'types'        => $schema_types,
            'errors'       => 0, // Placeholder for validation errors
            'local_seo'    => $local_enabled,
        ];
    }

    /**
     * Get notifications data.
     *
     * Priority levels: 1 = critical (error), 2 = important (warning), 3 = informational (info)
     *
     * @return array
     */
    private function get_notifications_data() {
        $notifications = [];
        $dismissed = get_option( 'wpseopilot_dismissed_notifications', [] );
        if ( ! is_array( $dismissed ) ) {
            $dismissed = [];
        }

        // Clean up old dismissed notifications (older than 30 days)
        $cutoff = time() - ( 30 * DAY_IN_SECONDS );
        $dismissed = array_filter( $dismissed, function( $timestamp ) use ( $cutoff ) {
            return $timestamp > $cutoff;
        } );
        update_option( 'wpseopilot_dismissed_notifications', $dismissed );

        // Priority 1: Check for slug change suggestions (redirects needed)
        $slug_suggestions = get_option( 'wpseopilot_monitor_slugs', [] );
        if ( is_array( $slug_suggestions ) && count( $slug_suggestions ) > 0 ) {
            // Only show one notification for multiple slug changes
            $count = count( $slug_suggestions );
            $notif_id = 'slug_changes_pending';
            if ( ! isset( $dismissed[ $notif_id ] ) ) {
                $first_suggestion = reset( $slug_suggestions );
                $notifications[] = [
                    'id'       => $notif_id,
                    'type'     => 'warning',
                    'priority' => 1,
                    'category' => 'redirects',
                    'title'    => __( 'URL Changes Detected', 'wp-seo-pilot' ),
                    'message'  => $count === 1
                        ? sprintf(
                            __( '"%s" has a new URL. Create a redirect to avoid broken links.', 'wp-seo-pilot' ),
                            get_the_title( $first_suggestion['post_id'] ?? 0 )
                        )
                        : sprintf(
                            __( '%d pages have new URLs. Create redirects to avoid broken links.', 'wp-seo-pilot' ),
                            $count
                        ),
                    'action'   => [
                        'label' => __( 'Review Changes', 'wp-seo-pilot' ),
                    ],
                ];
            }
        }

        // Priority 2: Check for high 404 count (only if significant)
        $errors_404 = $this->get_404_data();
        if ( $errors_404['last_7_days'] >= 5 && ! isset( $dismissed['high_404_count'] ) ) {
            $notifications[] = [
                'id'       => 'high_404_count',
                'type'     => $errors_404['last_7_days'] >= 20 ? 'error' : 'warning',
                'priority' => $errors_404['last_7_days'] >= 20 ? 1 : 2,
                'category' => '404',
                'title'    => __( '404 Errors Detected', 'wp-seo-pilot' ),
                'message'  => sprintf(
                    __( '%d broken links found this week. Fix them to improve user experience.', 'wp-seo-pilot' ),
                    $errors_404['last_7_days']
                ),
                'action'   => [
                    'label' => __( 'View Errors', 'wp-seo-pilot' ),
                ],
            ];
        }

        // Priority 3: Check for very low SEO score (only if really bad)
        $seo_score = $this->calculate_overall_seo_score();
        if ( $seo_score['score'] > 0 && $seo_score['score'] < 40 && $seo_score['posts_scored'] >= 3 && ! isset( $dismissed['low_seo_score'] ) ) {
            $notifications[] = [
                'id'       => 'low_seo_score',
                'type'     => 'error',
                'priority' => 1,
                'category' => 'seo',
                'title'    => __( 'Low SEO Score', 'wp-seo-pilot' ),
                'message'  => sprintf(
                    __( 'Average score is %d%%. Run an audit to find quick wins.', 'wp-seo-pilot' ),
                    $seo_score['score']
                ),
                'action'   => [
                    'label' => __( 'Run Audit', 'wp-seo-pilot' ),
                ],
            ];
        }

        // Priority 4: Sitemap disabled (info only)
        $sitemap = $this->get_sitemap_data();
        if ( ! $sitemap['enabled'] && ! isset( $dismissed['sitemap_disabled'] ) ) {
            $notifications[] = [
                'id'       => 'sitemap_disabled',
                'type'     => 'info',
                'priority' => 3,
                'category' => 'sitemap',
                'title'    => __( 'Sitemap Not Active', 'wp-seo-pilot' ),
                'message'  => __( 'Enable your XML sitemap to help search engines discover content.', 'wp-seo-pilot' ),
                'action'   => [
                    'label' => __( 'Enable', 'wp-seo-pilot' ),
                ],
            ];
        }

        // Priority 5: Low coverage (only if really low and have enough content)
        $coverage = $this->get_content_coverage_data();
        $pending_pct = $coverage['total'] > 0 ? ( $coverage['pending'] / $coverage['total'] ) * 100 : 0;
        if ( $coverage['total'] >= 5 && $pending_pct > 70 && ! isset( $dismissed['low_coverage'] ) ) {
            $notifications[] = [
                'id'       => 'low_coverage',
                'type'     => 'warning',
                'priority' => 2,
                'category' => 'content',
                'title'    => __( 'Missing SEO Data', 'wp-seo-pilot' ),
                'message'  => sprintf(
                    __( '%d of %d pages need SEO optimization.', 'wp-seo-pilot' ),
                    $coverage['pending'],
                    $coverage['total']
                ),
                'action'   => [
                    'label' => __( 'View Audit', 'wp-seo-pilot' ),
                ],
            ];
        }

        // Sort by priority
        usort( $notifications, function( $a, $b ) {
            return ( $a['priority'] ?? 99 ) - ( $b['priority'] ?? 99 );
        } );

        return $notifications;
    }
}
