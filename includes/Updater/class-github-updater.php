<?php
/**
 * GitHub Plugin Updater
 *
 * Checks GitHub releases for plugin updates and integrates
 * with WordPress update system.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot\Updater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * GitHub_Updater class - Manages plugin updates from GitHub.
 */
class GitHub_Updater {

    /**
     * Managed plugins configuration.
     *
     * @var array
     */
    private $plugins = [];

    /**
     * GitHub API base URL.
     */
    private const GITHUB_API = 'https://api.github.com';

    /**
     * Cache duration (12 hours).
     */
    private const CACHE_DURATION = 43200;

    /**
     * Singleton instance.
     *
     * @var GitHub_Updater|null
     */
    private static $instance = null;

    /**
     * Get singleton instance.
     *
     * @return GitHub_Updater
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Private for singleton.
     */
    private function __construct() {
        $this->register_plugins();
        $this->init_hooks();
    }

    /**
     * Register managed plugins.
     */
    private function register_plugins() {
        $this->plugins = [
            'wp-seo-pilot/wp-seo-pilot.php' => [
                'slug'        => 'wp-seo-pilot',
                'repo'        => 'jhd3197/WP-SEO-Pilot',
                'name'        => 'WP SEO Pilot',
                'description' => 'AI-powered SEO optimization for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/images/banner-772x250.png',
            ],
            'wp-ai-pilot/wp-ai-pilot.php' => [
                'slug'        => 'wp-ai-pilot',
                'repo'        => 'jhd3197/WP-AI-Pilot',
                'name'        => 'WP AI Pilot',
                'description' => 'Centralized AI management for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/images/banner-772x250.png',
            ],
            'wp-security-pilot/wp-security-pilot.php' => [
                'slug'        => 'wp-security-pilot',
                'repo'        => 'jhd3197/WP-Security-Pilot',
                'name'        => 'WP Security Pilot',
                'description' => 'Core security suite with firewall, malware scans, and hardening',
                'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-Security-Pilot/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-Security-Pilot/main/assets/images/banner-772x250.png',
            ],
        ];

        // Allow filtering.
        $this->plugins = apply_filters( 'wpseopilot_managed_plugins', $this->plugins );
    }

    /**
     * Initialize WordPress hooks.
     */
    private function init_hooks() {
        // Hook into WordPress update system.
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_updates' ] );

        // Add plugin info for "View details" link.
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );

        // Rename folder after update (GitHub zips have branch name).
        add_filter( 'upgrader_source_selection', [ $this, 'fix_folder_name' ], 10, 4 );

        // Daily cron check.
        add_action( 'wpseopilot_check_updates', [ $this, 'cron_check_updates' ] );

        // Schedule cron if not scheduled.
        if ( ! wp_next_scheduled( 'wpseopilot_check_updates' ) ) {
            wp_schedule_event( time(), 'daily', 'wpseopilot_check_updates' );
        }
    }

    /**
     * Check GitHub for updates.
     *
     * @param object $transient Update transient.
     * @return object Modified transient.
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            // Skip if plugin not installed.
            if ( ! isset( $transient->checked[ $plugin_file ] ) ) {
                continue;
            }

            $current_version = $transient->checked[ $plugin_file ];
            $remote_version  = $this->get_remote_version( $plugin_data['repo'] );

            if ( $remote_version && version_compare( $remote_version['version'], $current_version, '>' ) ) {
                $transient->response[ $plugin_file ] = (object) [
                    'slug'        => $plugin_data['slug'],
                    'plugin'      => $plugin_file,
                    'new_version' => $remote_version['version'],
                    'url'         => 'https://github.com/' . $plugin_data['repo'],
                    'package'     => $remote_version['download_url'],
                    'icons'       => [
                        '1x' => $plugin_data['icon'],
                        '2x' => $plugin_data['icon'],
                    ],
                    'banners'     => [
                        'low'  => $plugin_data['banner'],
                        'high' => $plugin_data['banner'],
                    ],
                    'tested'       => get_bloginfo( 'version' ),
                    'requires_php' => '7.4',
                ];
            }
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub.
     *
     * @param string $repo GitHub repository (owner/repo).
     * @return array|null Remote version data or null on error.
     */
    public function get_remote_version( string $repo ): ?array {
        $cache_key = 'wpseopilot_gh_' . md5( $repo );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $url = self::GITHUB_API . '/repos/' . $repo . '/releases/latest';

        $response = wp_remote_get( $url, [
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WP-SEO-Pilot-Updater',
            ],
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['tag_name'] ) ) {
            return null;
        }

        // Remove 'v' prefix from tag.
        $version = ltrim( $body['tag_name'], 'v' );

        // Find the zip asset.
        $download_url = null;
        if ( ! empty( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                if ( str_ends_with( $asset['name'], '.zip' ) ) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback to zipball.
        if ( ! $download_url ) {
            $download_url = $body['zipball_url'] ?? null;
        }

        $result = [
            'version'      => $version,
            'download_url' => $download_url,
            'changelog'    => $body['body'] ?? '',
            'published_at' => $body['published_at'] ?? '',
            'html_url'     => $body['html_url'] ?? '',
        ];

        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Plugin info for "View details" popup.
     *
     * @param mixed  $result Default result.
     * @param string $action API action.
     * @param object $args   API arguments.
     * @return mixed Plugin info or default result.
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        // Find our plugin.
        $plugin_data = null;
        foreach ( $this->plugins as $file => $data ) {
            if ( $data['slug'] === $args->slug ) {
                $plugin_data = $data;
                break;
            }
        }

        if ( ! $plugin_data ) {
            return $result;
        }

        $remote = $this->get_remote_version( $plugin_data['repo'] );

        if ( ! $remote ) {
            return $result;
        }

        return (object) [
            'name'           => $plugin_data['name'],
            'slug'           => $plugin_data['slug'],
            'version'        => $remote['version'],
            'author'         => '<a href="https://github.com/jhd3197">Juan Denis</a>',
            'author_profile' => 'https://github.com/jhd3197',
            'requires'       => '5.0',
            'tested'         => get_bloginfo( 'version' ),
            'requires_php'   => '7.4',
            'homepage'       => 'https://github.com/' . $plugin_data['repo'],
            'download_link'  => $remote['download_url'],
            'trunk'          => $remote['download_url'],
            'last_updated'   => $remote['published_at'],
            'sections'       => [
                'description' => $plugin_data['description'],
                'changelog'   => $this->parse_changelog( $remote['changelog'] ),
            ],
            'banners'        => [
                'low'  => $plugin_data['banner'],
                'high' => $plugin_data['banner'],
            ],
            'icons'          => [
                '1x' => $plugin_data['icon'],
                '2x' => $plugin_data['icon'],
            ],
        ];
    }

    /**
     * Fix folder name after extraction.
     * GitHub zips extract to repo-name-tag, we need just repo-name.
     *
     * @param string $source        Source path.
     * @param string $remote_source Remote source path.
     * @param object $upgrader      Upgrader instance.
     * @param array  $hook_extra    Extra hook data.
     * @return string Modified source path.
     */
    public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        // Only for our plugins.
        if ( ! isset( $hook_extra['plugin'] ) ) {
            return $source;
        }

        $plugin_file = $hook_extra['plugin'];
        if ( ! isset( $this->plugins[ $plugin_file ] ) ) {
            return $source;
        }

        $correct_folder = dirname( $plugin_file );

        // Check if folder name needs fixing.
        $source_folder = basename( $source );
        if ( $source_folder === $correct_folder ) {
            return $source;
        }

        // Rename folder.
        $new_source = trailingslashit( dirname( $source ) ) . $correct_folder;

        if ( $wp_filesystem->move( $source, $new_source ) ) {
            return $new_source;
        }

        return $source;
    }

    /**
     * Parse changelog markdown to HTML.
     *
     * @param string $markdown Changelog in markdown.
     * @return string Changelog as HTML.
     */
    private function parse_changelog( string $markdown ): string {
        $html = esc_html( $markdown );
        $html = preg_replace( '/^## (.+)$/m', '<h4>$1</h4>', $html );
        $html = preg_replace( '/^### (.+)$/m', '<h5>$1</h5>', $html );
        $html = preg_replace( '/^\* (.+)$/m', '<li>$1</li>', $html );
        $html = preg_replace( '/^- (.+)$/m', '<li>$1</li>', $html );
        $html = preg_replace( '/(<li>.*<\/li>\n?)+/', '<ul>$0</ul>', $html );
        $html = nl2br( $html );

        return $html;
    }

    /**
     * Cron job to check updates.
     */
    public function cron_check_updates() {
        // Clear transients to force fresh check.
        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            delete_transient( 'wpseopilot_gh_' . md5( $plugin_data['repo'] ) );
        }

        // Trigger WordPress update check.
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();
    }

    /**
     * Manual update check - clears cache and returns fresh data.
     *
     * @return array Update check results.
     */
    public function force_check_updates(): array {
        $results = [];

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            // Clear cache.
            delete_transient( 'wpseopilot_gh_' . md5( $plugin_data['repo'] ) );

            // Get fresh version.
            $remote = $this->get_remote_version( $plugin_data['repo'] );

            // Get current version.
            $current_version = null;
            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $plugin_info     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
                $current_version = $plugin_info['Version'];
            }

            $results[ $plugin_data['slug'] ] = [
                'name'             => $plugin_data['name'],
                'installed'        => $current_version !== null,
                'current_version'  => $current_version,
                'remote_version'   => $remote['version'] ?? null,
                'update_available' => $remote && $current_version && version_compare( $remote['version'], $current_version, '>' ),
                'download_url'     => $remote['download_url'] ?? null,
                'changelog'        => $remote['changelog'] ?? '',
            ];
        }

        // Update transient.
        delete_site_transient( 'update_plugins' );

        return $results;
    }

    /**
     * Get all managed plugins with status.
     *
     * @return array Plugins status.
     */
    public function get_plugins_status(): array {
        $status = [];

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            $installed       = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
            $active          = is_plugin_active( $plugin_file );
            $current_version = null;

            if ( $installed ) {
                $plugin_info     = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
                $current_version = $plugin_info['Version'];
            }

            $remote = $this->get_remote_version( $plugin_data['repo'] );

            $status[ $plugin_data['slug'] ] = [
                'plugin_file'      => $plugin_file,
                'name'             => $plugin_data['name'],
                'description'      => $plugin_data['description'],
                'repo'             => $plugin_data['repo'],
                'installed'        => $installed,
                'active'           => $active,
                'current_version'  => $current_version,
                'remote_version'   => $remote['version'] ?? null,
                'update_available' => $installed && $remote && version_compare( $remote['version'] ?? '0', $current_version ?? '0', '>' ),
                'download_url'     => $remote['download_url'] ?? null,
                'github_url'       => 'https://github.com/' . $plugin_data['repo'],
                'icon'             => $plugin_data['icon'],
            ];
        }

        return $status;
    }

    /**
     * Get managed plugins configuration.
     *
     * @return array Plugins configuration.
     */
    public function get_plugins(): array {
        return $this->plugins;
    }
}
