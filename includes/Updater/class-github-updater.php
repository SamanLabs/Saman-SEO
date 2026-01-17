<?php
/**
 * GitHub Plugin Updater
 *
 * Checks GitHub releases for plugin updates and integrates
 * with WordPress update system. Supports both stable and beta releases.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO\Updater;

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
     * Cache duration for stable releases (12 hours).
     */
    private const CACHE_DURATION = 43200;

    /**
     * Cache duration for beta releases (6 hours).
     */
    private const BETA_CACHE_DURATION = 21600;

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
            'saman-seo/saman-seo.php' => [
                'slug'        => 'saman-seo',
                'repo'        => 'SamanLabs/Saman-SEO',
                'name'        => 'Saman SEO',
                'description' => 'AI-powered SEO optimization for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-SEO/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-SEO/main/assets/images/banner-772x250.png',
            ],
            'saman-ai/saman-ai.php' => [
                'slug'        => 'saman-ai',
                'repo'        => 'Saman/Saman-AI',
                'name'        => 'Saman AI',
                'description' => 'Centralized AI management for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-AI/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-AI/main/assets/images/banner-772x250.png',
            ],
            'saman-security/saman-security.php' => [
                'slug'        => 'saman-security',
                'repo'        => 'SamanLabs/Saman-Security',
                'name'        => 'Saman Security',
                'description' => 'Core security suite with firewall, malware scans, and hardening',
                'icon'        => 'https://raw.githubusercontent.com/SamanLabs/Saman-Security/main/assets/images/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/SamanLabs/Saman-Security/main/assets/images/banner-772x250.png',
            ],
        ];

        // Allow filtering.
        $this->plugins = apply_filters( 'SAMAN_SEO_managed_plugins', $this->plugins );
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
        add_action( 'SAMAN_SEO_check_updates', [ $this, 'cron_check_updates' ] );

        // Schedule cron if not scheduled.
        if ( ! wp_next_scheduled( 'SAMAN_SEO_check_updates' ) ) {
            wp_schedule_event( time(), 'daily', 'SAMAN_SEO_check_updates' );
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
            $slug            = $plugin_data['slug'];
            $beta_enabled    = $this->is_beta_enabled( $slug );

            // Get stable version.
            $remote = $this->get_remote_version( $plugin_data['repo'] );

            // Get beta version if enabled.
            $beta = $beta_enabled ? $this->get_beta_version( $plugin_data['repo'] ) : null;

            // Determine which version to offer.
            $update_version  = null;
            $update_url      = null;

            if ( $beta_enabled && $beta && $this->compare_versions( $beta['version'], $current_version ) > 0 ) {
                // Beta is newer than current and beta is enabled.
                if ( ! $remote || $this->compare_versions( $beta['version'], $remote['version'] ) >= 0 ) {
                    $update_version = $beta['version'];
                    $update_url     = $beta['download_url'];
                }
            }

            // Fall back to stable if no beta update or stable is newer.
            if ( ! $update_version && $remote && $this->compare_versions( $remote['version'], $current_version ) > 0 ) {
                $update_version = $remote['version'];
                $update_url     = $remote['download_url'];
            }

            if ( $update_version && $update_url ) {
                $transient->response[ $plugin_file ] = (object) [
                    'slug'        => $plugin_data['slug'],
                    'plugin'      => $plugin_file,
                    'new_version' => $update_version,
                    'url'         => 'https://github.com/' . $plugin_data['repo'],
                    'package'     => $update_url,
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
     * Get remote stable version from GitHub.
     *
     * @param string $repo GitHub repository (owner/repo).
     * @return array|null Remote version data or null on error.
     */
    public function get_remote_version( string $repo ): ?array {
        $cache_key = 'SAMAN_SEO_gh_' . md5( $repo );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached ?: null;
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
            set_transient( $cache_key, [], self::CACHE_DURATION );
            return null;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status_code ) {
            set_transient( $cache_key, [], self::CACHE_DURATION );
            return null;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $body['tag_name'] ) ) {
            set_transient( $cache_key, [], self::CACHE_DURATION );
            return null;
        }

        // Remove 'v' prefix from tag.
        $version = ltrim( $body['tag_name'], 'v' );

        // Find the zip asset.
        $download_url = null;
        if ( ! empty( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                if ( isset( $asset['name'] ) && substr( $asset['name'], -4 ) === '.zip' ) {
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
            'is_beta'      => false,
        ];

        set_transient( $cache_key, $result, self::CACHE_DURATION );

        return $result;
    }

    /**
     * Get beta version from GitHub pre-releases.
     *
     * @param string $repo GitHub repository (owner/repo).
     * @return array|null Beta version data or null if not found.
     */
    public function get_beta_version( string $repo ): ?array {
        $cache_key = 'SAMAN_SEO_gh_beta_' . md5( $repo );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached ?: null;
        }

        // Get all releases (includes pre-releases).
        $url = self::GITHUB_API . '/repos/' . $repo . '/releases';

        $response = wp_remote_get( $url, [
            'headers' => [
                'Accept'     => 'application/vnd.github.v3+json',
                'User-Agent' => 'WP-SEO-Pilot-Updater',
            ],
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            set_transient( $cache_key, [], self::BETA_CACHE_DURATION );
            return null;
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        if ( 200 !== $status_code ) {
            set_transient( $cache_key, [], self::BETA_CACHE_DURATION );
            return null;
        }

        $releases = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( empty( $releases ) || ! is_array( $releases ) ) {
            set_transient( $cache_key, [], self::BETA_CACHE_DURATION );
            return null;
        }

        // Find the latest pre-release.
        $latest_beta = null;
        foreach ( $releases as $release ) {
            if ( ! empty( $release['prerelease'] ) && ! empty( $release['tag_name'] ) ) {
                $latest_beta = $release;
                break; // Releases are sorted by date, first prerelease is latest.
            }
        }

        if ( ! $latest_beta ) {
            set_transient( $cache_key, [], self::BETA_CACHE_DURATION );
            return null;
        }

        // Parse version from tag.
        $version = ltrim( $latest_beta['tag_name'], 'v' );

        // Find the zip asset.
        $download_url = null;
        if ( ! empty( $latest_beta['assets'] ) ) {
            foreach ( $latest_beta['assets'] as $asset ) {
                if ( isset( $asset['name'] ) && substr( $asset['name'], -4 ) === '.zip' ) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback to zipball.
        if ( ! $download_url && ! empty( $latest_beta['zipball_url'] ) ) {
            $download_url = $latest_beta['zipball_url'];
        }

        if ( ! $download_url ) {
            set_transient( $cache_key, [], self::BETA_CACHE_DURATION );
            return null;
        }

        $result = [
            'version'      => $version,
            'download_url' => $download_url,
            'changelog'    => $latest_beta['body'] ?? '',
            'published_at' => $latest_beta['published_at'] ?? '',
            'html_url'     => $latest_beta['html_url'] ?? '',
            'is_beta'      => true,
        ];

        set_transient( $cache_key, $result, self::BETA_CACHE_DURATION );

        return $result;
    }

    /**
     * Compare two version strings, handling beta/alpha/rc tags.
     *
     * @param string $version1 First version.
     * @param string $version2 Second version.
     * @return int -1, 0, or 1.
     */
    public function compare_versions( string $version1, string $version2 ): int {
        // Normalize versions.
        $v1 = $this->normalize_version( $version1 );
        $v2 = $this->normalize_version( $version2 );

        return version_compare( $v1, $v2 );
    }

    /**
     * Normalize version string for comparison.
     *
     * @param string $version Version string.
     * @return string Normalized version.
     */
    private function normalize_version( string $version ): string {
        // Replace common pre-release tags with comparable versions.
        $version = strtolower( $version );
        $version = str_replace( [ '-alpha', '-beta', '-rc' ], [ '.alpha.', '.beta.', '.rc.' ], $version );
        $version = preg_replace( '/\.+/', '.', $version );
        $version = trim( $version, '.' );

        return $version;
    }

    /**
     * Check if beta is enabled for a plugin.
     *
     * @param string $slug Plugin slug.
     * @return bool
     */
    public function is_beta_enabled( string $slug ): bool {
        $beta_settings = get_option( 'SAMAN_SEO_beta_plugins', [] );
        return ! empty( $beta_settings[ $slug ] );
    }

    /**
     * Set beta enabled/disabled for a plugin.
     *
     * @param string $slug    Plugin slug.
     * @param bool   $enabled Whether beta is enabled.
     * @return bool Success.
     */
    public function set_beta_enabled( string $slug, bool $enabled ): bool {
        $beta_settings = get_option( 'SAMAN_SEO_beta_plugins', [] );

        if ( $enabled ) {
            $beta_settings[ $slug ] = true;
        } else {
            unset( $beta_settings[ $slug ] );
        }

        return update_option( 'SAMAN_SEO_beta_plugins', $beta_settings );
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
            'author'         => '<a href="https://Saman.com">Saman</a>',
            'author_profile' => 'https://Saman.com',
            'requires'       => '5.0',
            'tested'         => get_bloginfo( 'version' ),
            'requires_php'   => '7.4',
            'homepage'       => 'https://github.com/Saman/Saman-SEO',
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
            delete_transient( 'SAMAN_SEO_gh_' . md5( $plugin_data['repo'] ) );
            delete_transient( 'SAMAN_SEO_gh_beta_' . md5( $plugin_data['repo'] ) );
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
            delete_transient( 'SAMAN_SEO_gh_' . md5( $plugin_data['repo'] ) );
            delete_transient( 'SAMAN_SEO_gh_beta_' . md5( $plugin_data['repo'] ) );

            // Get fresh versions.
            $remote = $this->get_remote_version( $plugin_data['repo'] );
            $beta   = $this->get_beta_version( $plugin_data['repo'] );

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
                'beta_version'     => $beta['version'] ?? null,
                'update_available' => $remote && $current_version && $this->compare_versions( $remote['version'], $current_version ) > 0,
                'download_url'     => $remote['download_url'] ?? null,
                'changelog'        => $remote['changelog'] ?? '',
            ];
        }

        // Update transient.
        delete_site_transient( 'update_plugins' );

        return $results;
    }

    /**
     * Get all managed plugins with status including beta info.
     *
     * @return array Plugins status.
     */
    public function get_plugins_status(): array {
        if ( ! function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // Clear the plugins cache to get fresh data.
        wp_cache_delete( 'plugins', 'plugins' );

        // Get fresh list of installed plugins.
        $installed_plugins = get_plugins();

        $status = [];

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            $slug            = $plugin_data['slug'];
            $current_version = null;

            // First, try exact match.
            $actual_plugin_file = $plugin_file;
            $installed          = isset( $installed_plugins[ $plugin_file ] );

            // If not found, search by main file name (handles folder rename issues).
            if ( ! $installed ) {
                $main_file = basename( $plugin_file );
                foreach ( $installed_plugins as $installed_file => $installed_data ) {
                    if ( basename( $installed_file ) === $main_file ) {
                        $actual_plugin_file = $installed_file;
                        $installed          = true;
                        break;
                    }
                }
            }

            // If still not found, search by slug in folder name.
            if ( ! $installed ) {
                foreach ( $installed_plugins as $installed_file => $installed_data ) {
                    $folder = dirname( $installed_file );
                    if ( stripos( $folder, $slug ) !== false || stripos( $folder, str_replace( '-', '_', $slug ) ) !== false ) {
                        $actual_plugin_file = $installed_file;
                        $installed          = true;
                        break;
                    }
                }
            }

            $active = $installed ? is_plugin_active( $actual_plugin_file ) : false;

            if ( $installed ) {
                $current_version = $installed_plugins[ $actual_plugin_file ]['Version'];
            }

            // Get stable version.
            $remote = $this->get_remote_version( $plugin_data['repo'] );

            // Get beta version.
            $beta = $this->get_beta_version( $plugin_data['repo'] );

            // Check beta settings.
            $beta_enabled = $this->is_beta_enabled( $slug );

            // Determine what version to show as update.
            $update_version   = null;
            $update_url       = null;
            $update_is_beta   = false;
            $update_available = false;

            if ( $installed && $current_version ) {
                // Check stable update.
                $stable_update = $remote && $this->compare_versions( $remote['version'], $current_version ) > 0;

                // Check beta update (only if beta enabled).
                $beta_update = false;
                if ( $beta_enabled && $beta ) {
                    $beta_update = $this->compare_versions( $beta['version'], $current_version ) > 0;

                    // If beta is newer than stable, prefer beta.
                    if ( $beta_update && ( ! $remote || $this->compare_versions( $beta['version'], $remote['version'] ) > 0 ) ) {
                        $update_version   = $beta['version'];
                        $update_url       = $beta['download_url'];
                        $update_is_beta   = true;
                        $update_available = true;
                    } elseif ( $stable_update ) {
                        $update_version   = $remote['version'];
                        $update_url       = $remote['download_url'];
                        $update_is_beta   = false;
                        $update_available = true;
                    }
                } elseif ( $stable_update ) {
                    $update_version   = $remote['version'];
                    $update_url       = $remote['download_url'];
                    $update_is_beta   = false;
                    $update_available = true;
                }
            }

            $status[ $slug ] = [
                'plugin_file'       => $actual_plugin_file,
                'expected_file'     => $plugin_file,
                'name'              => $plugin_data['name'],
                'description'       => $plugin_data['description'],
                'repo'              => $plugin_data['repo'],
                'installed'         => $installed,
                'active'            => $active,
                'current_version'   => $current_version,
                'remote_version'    => $remote['version'] ?? null,
                'update_available'  => $update_available,
                'update_version'    => $update_version,
                'update_is_beta'    => $update_is_beta,
                'download_url'      => $update_url ?? ( $remote['download_url'] ?? null ),
                'github_url'        => 'https://github.com/' . $plugin_data['repo'],
                'icon'              => $plugin_data['icon'],
                // Beta info.
                'beta_enabled'      => $beta_enabled,
                'beta_available'    => $beta !== null,
                'beta_version'      => $beta['version'] ?? null,
                'beta_download_url' => $beta['download_url'] ?? null,
                'beta_changelog'    => $beta['changelog'] ?? null,
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

    /**
     * Get managed plugins (alias for compatibility).
     *
     * @return array Plugins configuration.
     */
    public function get_managed_plugins(): array {
        return $this->plugins;
    }
}
