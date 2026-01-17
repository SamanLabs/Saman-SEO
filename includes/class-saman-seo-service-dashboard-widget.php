<?php
/**
 * Dashboard Widget Service for 404 Monitor.
 *
 * @package WP_SEO_Pilot
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Dashboard Widget Class
 *
 * Displays 404 error summary on the WordPress admin dashboard.
 */
class Dashboard_Widget {

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
        $this->log_table = $wpdb->prefix . 'SAMAN_SEO_404_log';
    }

    /**
     * Boot the service.
     */
    public function boot() {
        // Only show widget if enabled (default is true)
        $settings = get_option( 'SAMAN_SEO_settings', [] );
        $show_widget = isset( $settings['show_404_dashboard_widget'] ) ? $settings['show_404_dashboard_widget'] : true;

        if ( ! $show_widget ) {
            return;
        }

        add_action( 'wp_dashboard_setup', [ $this, 'register_widget' ] );
    }

    /**
     * Register the dashboard widget.
     */
    public function register_widget() {
        // Only for users who can manage options
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        wp_add_dashboard_widget(
            'SAMAN_SEO_404_widget',
            __( '404 Monitor - Saman SEO', 'saman-seo' ),
            [ $this, 'render_widget' ]
        );
    }

    /**
     * Render the widget content.
     */
    public function render_widget() {
        $stats = $this->get_summary_stats();
        $recent = $this->get_recent_404s( 5 );
        $admin_url = admin_url( 'admin.php?page=saman-seo-v2#/404-log' );
        ?>
        <div class="saman-seo-dashboard-widget">
            <div class="saman-seo-widget-stats">
                <div class="saman-seo-widget-stat">
                    <span class="saman-seo-widget-stat__value"><?php echo esc_html( number_format_i18n( $stats['total'] ) ); ?></span>
                    <span class="saman-seo-widget-stat__label"><?php esc_html_e( 'Total 404s', 'saman-seo' ); ?></span>
                </div>
                <div class="saman-seo-widget-stat">
                    <span class="saman-seo-widget-stat__value"><?php echo esc_html( number_format_i18n( $stats['need_redirect'] ) ); ?></span>
                    <span class="saman-seo-widget-stat__label"><?php esc_html_e( 'Need Redirect', 'saman-seo' ); ?></span>
                </div>
                <div class="saman-seo-widget-stat">
                    <span class="saman-seo-widget-stat__value"><?php echo esc_html( number_format_i18n( $stats['last_24h'] ) ); ?></span>
                    <span class="saman-seo-widget-stat__label"><?php esc_html_e( 'Last 24h', 'saman-seo' ); ?></span>
                </div>
                <div class="saman-seo-widget-stat">
                    <span class="saman-seo-widget-stat__value"><?php echo esc_html( number_format_i18n( $stats['bots'] ) ); ?></span>
                    <span class="saman-seo-widget-stat__label"><?php esc_html_e( 'Bots', 'saman-seo' ); ?></span>
                </div>
            </div>

            <?php if ( ! empty( $recent ) ) : ?>
                <div class="saman-seo-widget-recent">
                    <h4><?php esc_html_e( 'Recent 404s', 'saman-seo' ); ?></h4>
                    <ul>
                        <?php foreach ( $recent as $entry ) : ?>
                            <li>
                                <code><?php echo esc_html( $this->truncate_url( $entry->request_uri, 40 ) ); ?></code>
                                <span class="saman-seo-widget-hits"><?php echo esc_html( number_format_i18n( $entry->hits ) ); ?> <?php esc_html_e( 'hits', 'saman-seo' ); ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php elseif ( 0 === $stats['total'] ) : ?>
                <div class="saman-seo-widget-empty">
                    <p><?php esc_html_e( 'No 404 errors recorded yet. Great!', 'saman-seo' ); ?></p>
                </div>
            <?php endif; ?>

            <p class="saman-seo-widget-footer">
                <a href="<?php echo esc_url( $admin_url ); ?>" class="button button-primary">
                    <?php esc_html_e( 'View All 404s', 'saman-seo' ); ?>
                </a>
            </p>
        </div>

        <style>
            .saman-seo-dashboard-widget {
                margin: -12px;
            }
            .saman-seo-widget-stats {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 0;
                border-bottom: 1px solid #c3c4c7;
                background: #f6f7f7;
            }
            .saman-seo-widget-stat {
                padding: 12px 8px;
                text-align: center;
                border-right: 1px solid #c3c4c7;
            }
            .saman-seo-widget-stat:last-child {
                border-right: none;
            }
            .saman-seo-widget-stat__value {
                display: block;
                font-size: 20px;
                font-weight: 600;
                color: #1d2327;
                line-height: 1.2;
            }
            .saman-seo-widget-stat__label {
                display: block;
                font-size: 11px;
                color: #646970;
                margin-top: 2px;
            }
            .saman-seo-widget-recent {
                padding: 12px;
            }
            .saman-seo-widget-recent h4 {
                font-size: 12px;
                font-weight: 600;
                color: #1d2327;
                margin: 0 0 8px 0;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            .saman-seo-widget-recent ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            .saman-seo-widget-recent li {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 6px 0;
                border-bottom: 1px solid #f0f0f1;
            }
            .saman-seo-widget-recent li:last-child {
                border-bottom: none;
            }
            .saman-seo-widget-recent code {
                font-size: 12px;
                background: none;
                padding: 0;
                color: #50575e;
                max-width: 70%;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }
            .saman-seo-widget-hits {
                font-size: 11px;
                color: #646970;
                white-space: nowrap;
            }
            .saman-seo-widget-empty {
                padding: 16px 12px;
                text-align: center;
            }
            .saman-seo-widget-empty p {
                margin: 0;
                color: #646970;
            }
            .saman-seo-widget-footer {
                padding: 12px;
                margin: 0;
                background: #f6f7f7;
                border-top: 1px solid #c3c4c7;
                text-align: center;
            }
        </style>
        <?php
    }

    /**
     * Get summary statistics for the widget.
     *
     * @return array
     */
    public function get_summary_stats() {
        global $wpdb;

        $stats = [
            'total'         => 0,
            'need_redirect' => 0,
            'last_24h'      => 0,
            'bots'          => 0,
        ];

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->log_table
        ) );

        if ( ! $table_exists ) {
            return $stats;
        }

        // Total count
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats['total'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->log_table}" );

        // Bot count (if column exists)
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $has_is_bot = $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s AND COLUMN_NAME = 'is_bot'",
            DB_NAME,
            $this->log_table
        ) );

        if ( $has_is_bot ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $stats['bots'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$this->log_table} WHERE is_bot = 1" );
        }

        // Last 24 hours
        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( '-24 hours' ) );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $stats['last_24h'] = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->log_table} WHERE last_seen > %s",
            $cutoff
        ) );

        // Need redirect - entries without an existing redirect
        $redirects_table = $wpdb->prefix . 'SAMAN_SEO_redirects';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $redirects_exist = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $redirects_table
        ) );

        if ( $redirects_exist ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery
            $stats['need_redirect'] = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM {$this->log_table} l
                WHERE NOT EXISTS (
                    SELECT 1 FROM {$redirects_table} r WHERE r.source = l.request_uri
                )"
            );
        } else {
            $stats['need_redirect'] = $stats['total'];
        }

        return $stats;
    }

    /**
     * Get recent 404 entries.
     *
     * @param int $limit Number of entries.
     * @return array
     */
    public function get_recent_404s( $limit = 5 ) {
        global $wpdb;

        // Check if table exists
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        $table_exists = $wpdb->get_var( $wpdb->prepare(
            "SHOW TABLES LIKE %s",
            $this->log_table
        ) );

        if ( ! $table_exists ) {
            return [];
        }

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT request_uri, hits FROM {$this->log_table} ORDER BY last_seen DESC LIMIT %d",
            $limit
        ) );
    }

    /**
     * Truncate a URL for display.
     *
     * @param string $url    The URL.
     * @param int    $length Max length.
     * @return string
     */
    private function truncate_url( $url, $length = 50 ) {
        if ( strlen( $url ) <= $length ) {
            return $url;
        }
        return substr( $url, 0, $length - 3 ) . '...';
    }
}
