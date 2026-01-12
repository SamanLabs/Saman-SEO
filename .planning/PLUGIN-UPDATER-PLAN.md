# WP AI Pilot - GitHub Plugin Updater System

## Executive Summary

Build a self-hosted plugin update system using GitHub releases, independent of WordPress.org repository. This allows immediate updates, full control, and works for private/open-source plugins.

---

## Table of Contents

1. [WordPress.org Store vs Self-Hosted](#1-wordpressorg-store-vs-self-hosted)
2. [Architecture Overview](#2-architecture-overview)
3. [Implementation Plan](#3-implementation-plan)
4. [Phase 1: Update Checker Core](#4-phase-1-update-checker-core)
5. [Phase 2: Plugin Installer](#5-phase-2-plugin-installer)
6. [Phase 3: Admin UI Integration](#6-phase-3-admin-ui-integration)
7. [Phase 4: Auto-Updates & Cron](#7-phase-4-auto-updates--cron)
8. [Security Considerations](#8-security-considerations)
9. [Database Schema](#9-database-schema)
10. [File Structure](#10-file-structure)
11. [API Reference](#11-api-reference)

---

## 1. WordPress.org Store vs Self-Hosted

### WordPress.org Store

**Pros:**
- Built-in auto-updates
- Discoverability (millions of users)
- Trust/credibility
- Free hosting

**Cons:**
- Review process takes 1-4 weeks (sometimes months)
- Strict guidelines (no external API calls without disclosure)
- No control over update timing
- Must be GPL licensed
- No premium upsells in free version

**Tips for faster approval:**
- Follow [Plugin Guidelines](https://developer.wordpress.org/plugins/wordpress-org/detailed-plugin-guidelines/) exactly
- No "powered by" links
- No tracking without consent
- Sanitize ALL inputs
- Escape ALL outputs
- Use nonces everywhere
- No external resources without disclosure

### Self-Hosted (GitHub)

**Pros:**
- Immediate updates
- Full control
- Works for private plugins
- No review process
- Can include premium features
- Open source friendly

**Cons:**
- Must build update infrastructure
- Users must trust your domain
- No WordPress.org discoverability

### Recommendation

**Do both:**
1. Submit to WordPress.org for discoverability
2. Build GitHub updater for immediate updates and control
3. GitHub version can have more features (AI integrations, etc.)

---

## 2. Architecture Overview

### How WordPress Plugin Updates Work

WordPress checks for updates via `pre_set_site_transient_update_plugins` filter. We hook into this to add our GitHub-hosted plugins.

```
┌─────────────────────────────────────────────────────────────┐
│                    WordPress Core                            │
│                                                              │
│  1. Cron runs update check (every 12 hours)                 │
│  2. Fires: pre_set_site_transient_update_plugins            │
│  3. Our hook checks GitHub API                               │
│  4. If new version, adds to $transient->response            │
│  5. WordPress shows "Update available"                       │
│  6. User clicks "Update Now"                                 │
│  7. WordPress downloads zip from GitHub                      │
│  8. WordPress extracts and replaces plugin                   │
└─────────────────────────────────────────────────────────────┘
```

### GitHub API Flow

```
┌──────────────┐      ┌──────────────┐      ┌──────────────┐
│  WordPress   │      │  GitHub API  │      │   Release    │
│   Plugin     │ ──── │   /releases  │ ──── │   Assets     │
│              │      │   /latest    │      │   (.zip)     │
└──────────────┘      └──────────────┘      └──────────────┘
       │                     │                      │
       │   1. GET /releases/latest                  │
       │ ──────────────────────>                    │
       │                     │                      │
       │   2. Response: tag_name, assets[]          │
       │ <──────────────────────                    │
       │                     │                      │
       │   3. Compare versions                      │
       │                     │                      │
       │   4. If newer, download asset              │
       │ ──────────────────────────────────────────>│
       │                     │                      │
       │   5. Install update                        │
       │ <──────────────────────────────────────────│
```

### Managed Plugins

| Plugin | GitHub Repo | Current |
|--------|-------------|---------|
| WP AI Pilot | `jhd3197/WP-AI-Pilot` | This plugin |
| WP SEO Pilot | `jhd3197/WP-SEO-Pilot` | External |

---

## 3. Implementation Plan

### Phase Overview

| Phase | Description | Effort |
|-------|-------------|--------|
| Phase 1 | Update Checker Core | 1-2 days |
| Phase 2 | Plugin Installer | 1 day |
| Phase 3 | Admin UI Integration | 1-2 days |
| Phase 4 | Auto-Updates & Cron | 0.5 day |
| Testing | Full integration testing | 1 day |
| **Total** | | **4-6 days** |

### Dependencies

- WordPress 5.0+
- PHP 7.4+
- `wp_remote_get` for API calls
- `Plugin_Upgrader` class for installation

---

## 4. Phase 1: Update Checker Core

### 4.1 Create Update Checker Class

**File:** `includes/Updater/class-github-updater.php`

```php
<?php
/**
 * GitHub Plugin Updater
 *
 * Checks GitHub releases for plugin updates and integrates
 * with WordPress update system.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Pilot_GitHub_Updater {

    /**
     * Managed plugins configuration
     */
    private $plugins = [];

    /**
     * GitHub API base URL
     */
    private const GITHUB_API = 'https://api.github.com';

    /**
     * Cache duration (12 hours)
     */
    private const CACHE_DURATION = 43200;

    /**
     * Singleton instance
     */
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        $this->register_plugins();
        $this->init_hooks();
    }

    /**
     * Register managed plugins
     */
    private function register_plugins() {
        $this->plugins = [
            'WP-AI-Pilot/wp-ai-pilot.php' => [
                'slug'        => 'wp-ai-pilot',
                'repo'        => 'jhd3197/WP-AI-Pilot',
                'name'        => 'WP AI Pilot',
                'description' => 'Centralized AI management for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/banner-772x250.png',
            ],
            'WP-SEO-Pilot/wp-seo-pilot.php' => [
                'slug'        => 'wp-seo-pilot',
                'repo'        => 'jhd3197/WP-SEO-Pilot',
                'name'        => 'WP SEO Pilot',
                'description' => 'AI-powered SEO optimization for WordPress',
                'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/icon-128.png',
                'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/banner-772x250.png',
            ],
        ];

        // Allow filtering
        $this->plugins = apply_filters( 'wp_ai_pilot_managed_plugins', $this->plugins );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into WordPress update system
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_updates' ] );

        // Add plugin info for "View details" link
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 20, 3 );

        // Rename folder after update (GitHub zips have branch name)
        add_filter( 'upgrader_source_selection', [ $this, 'fix_folder_name' ], 10, 4 );

        // Daily cron check
        add_action( 'wp_ai_pilot_check_updates', [ $this, 'cron_check_updates' ] );

        // Schedule cron if not scheduled
        if ( ! wp_next_scheduled( 'wp_ai_pilot_check_updates' ) ) {
            wp_schedule_event( time(), 'daily', 'wp_ai_pilot_check_updates' );
        }
    }

    /**
     * Check GitHub for updates
     */
    public function check_for_updates( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            // Skip if plugin not installed
            if ( ! isset( $transient->checked[ $plugin_file ] ) ) {
                continue;
            }

            $current_version = $transient->checked[ $plugin_file ];
            $remote_version = $this->get_remote_version( $plugin_data['repo'] );

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
                    'tested'      => get_bloginfo( 'version' ),
                    'requires_php'=> '7.4',
                ];
            }
        }

        return $transient;
    }

    /**
     * Get remote version from GitHub
     */
    public function get_remote_version( string $repo ): ?array {
        $cache_key = 'wpaipilot_gh_' . md5( $repo );
        $cached = get_transient( $cache_key );

        if ( false !== $cached ) {
            return $cached;
        }

        $url = self::GITHUB_API . '/repos/' . $repo . '/releases/latest';

        $response = wp_remote_get( $url, [
            'headers' => [
                'Accept' => 'application/vnd.github.v3+json',
                'User-Agent' => 'WP-AI-Pilot-Updater',
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

        // Remove 'v' prefix from tag
        $version = ltrim( $body['tag_name'], 'v' );

        // Find the zip asset
        $download_url = null;
        if ( ! empty( $body['assets'] ) ) {
            foreach ( $body['assets'] as $asset ) {
                if ( str_ends_with( $asset['name'], '.zip' ) ) {
                    $download_url = $asset['browser_download_url'];
                    break;
                }
            }
        }

        // Fallback to zipball
        if ( ! $download_url ) {
            $download_url = $body['zipball_url'];
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
     * Plugin info for "View details" popup
     */
    public function plugin_info( $result, $action, $args ) {
        if ( 'plugin_information' !== $action ) {
            return $result;
        }

        // Find our plugin
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
            'name'          => $plugin_data['name'],
            'slug'          => $plugin_data['slug'],
            'version'       => $remote['version'],
            'author'        => '<a href="https://github.com/jhd3197">Juan Denis</a>',
            'author_profile'=> 'https://github.com/jhd3197',
            'requires'      => '5.0',
            'tested'        => get_bloginfo( 'version' ),
            'requires_php'  => '7.4',
            'homepage'      => 'https://github.com/' . $plugin_data['repo'],
            'download_link' => $remote['download_url'],
            'trunk'         => $remote['download_url'],
            'last_updated'  => $remote['published_at'],
            'sections'      => [
                'description' => $plugin_data['description'],
                'changelog'   => $this->parse_changelog( $remote['changelog'] ),
            ],
            'banners'       => [
                'low'  => $plugin_data['banner'],
                'high' => $plugin_data['banner'],
            ],
            'icons'         => [
                '1x' => $plugin_data['icon'],
                '2x' => $plugin_data['icon'],
            ],
        ];
    }

    /**
     * Fix folder name after extraction
     * GitHub zips extract to repo-name-tag, we need just repo-name
     */
    public function fix_folder_name( $source, $remote_source, $upgrader, $hook_extra ) {
        global $wp_filesystem;

        // Only for our plugins
        if ( ! isset( $hook_extra['plugin'] ) ) {
            return $source;
        }

        $plugin_file = $hook_extra['plugin'];
        if ( ! isset( $this->plugins[ $plugin_file ] ) ) {
            return $source;
        }

        $plugin_data = $this->plugins[ $plugin_file ];
        $correct_folder = dirname( $plugin_file );

        // Check if folder name needs fixing
        $source_folder = basename( $source );
        if ( $source_folder === $correct_folder ) {
            return $source;
        }

        // Rename folder
        $new_source = trailingslashit( dirname( $source ) ) . $correct_folder;

        if ( $wp_filesystem->move( $source, $new_source ) ) {
            return $new_source;
        }

        return $source;
    }

    /**
     * Parse changelog markdown to HTML
     */
    private function parse_changelog( string $markdown ): string {
        // Simple markdown to HTML
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
     * Cron job to check updates
     */
    public function cron_check_updates() {
        // Clear transients to force fresh check
        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            delete_transient( 'wpaipilot_gh_' . md5( $plugin_data['repo'] ) );
        }

        // Trigger WordPress update check
        delete_site_transient( 'update_plugins' );
        wp_update_plugins();
    }

    /**
     * Manual update check
     */
    public function force_check_updates(): array {
        $results = [];

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            // Clear cache
            delete_transient( 'wpaipilot_gh_' . md5( $plugin_data['repo'] ) );

            // Get fresh version
            $remote = $this->get_remote_version( $plugin_data['repo'] );

            // Get current version
            $current_version = null;
            if ( file_exists( WP_PLUGIN_DIR . '/' . $plugin_file ) ) {
                $plugin_info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
                $current_version = $plugin_info['Version'];
            }

            $results[ $plugin_data['slug'] ] = [
                'name'            => $plugin_data['name'],
                'installed'       => $current_version !== null,
                'current_version' => $current_version,
                'remote_version'  => $remote['version'] ?? null,
                'update_available'=> $remote && $current_version && version_compare( $remote['version'], $current_version, '>' ),
                'download_url'    => $remote['download_url'] ?? null,
                'changelog'       => $remote['changelog'] ?? '',
            ];
        }

        // Update transient
        delete_site_transient( 'update_plugins' );

        return $results;
    }

    /**
     * Get all managed plugins with status
     */
    public function get_plugins_status(): array {
        $status = [];

        foreach ( $this->plugins as $plugin_file => $plugin_data ) {
            $installed = file_exists( WP_PLUGIN_DIR . '/' . $plugin_file );
            $active = is_plugin_active( $plugin_file );
            $current_version = null;

            if ( $installed ) {
                $plugin_info = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin_file );
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
                'update_available' => $installed && $remote && version_compare( $remote['version'], $current_version, '>' ),
                'download_url'     => $remote['download_url'] ?? null,
                'github_url'       => 'https://github.com/' . $plugin_data['repo'],
                'icon'             => $plugin_data['icon'],
            ];
        }

        return $status;
    }
}
```

### 4.2 Version Comparison Logic

The updater compares versions using PHP's `version_compare()`:

```php
// Examples:
version_compare( '1.0.1', '1.0.0', '>' )  // true - update available
version_compare( '1.0.0', '1.0.0', '>' )  // false - same version
version_compare( '0.9.9', '1.0.0', '>' )  // false - older
```

GitHub tags should follow semver: `v1.0.0`, `v1.0.1`, etc.

---

## 5. Phase 2: Plugin Installer

### 5.1 Plugin Installer Class

**File:** `includes/Updater/class-plugin-installer.php`

```php
<?php
/**
 * Plugin Installer
 *
 * Handles installation, activation, and updates of managed plugins.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class WP_AI_Pilot_Plugin_Installer {

    /**
     * Install a plugin from GitHub
     */
    public static function install( string $download_url, string $plugin_file ): array {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
        require_once ABSPATH . 'wp-admin/includes/plugin-install.php';

        // Create upgrader with skin that captures output
        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );

        // Install the plugin
        $result = $upgrader->install( $download_url );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        if ( ! $result ) {
            return [
                'success' => false,
                'message' => 'Installation failed. Check file permissions.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Plugin installed successfully.',
        ];
    }

    /**
     * Update a plugin
     */
    public static function update( string $plugin_file ): array {
        require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

        $skin = new WP_Ajax_Upgrader_Skin();
        $upgrader = new Plugin_Upgrader( $skin );

        $result = $upgrader->upgrade( $plugin_file );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        if ( ! $result ) {
            return [
                'success' => false,
                'message' => 'Update failed.',
            ];
        }

        return [
            'success' => true,
            'message' => 'Plugin updated successfully.',
        ];
    }

    /**
     * Activate a plugin
     */
    public static function activate( string $plugin_file ): array {
        $result = activate_plugin( $plugin_file );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Plugin activated successfully.',
        ];
    }

    /**
     * Deactivate a plugin
     */
    public static function deactivate( string $plugin_file ): array {
        deactivate_plugins( $plugin_file );

        return [
            'success' => true,
            'message' => 'Plugin deactivated.',
        ];
    }

    /**
     * Delete a plugin
     */
    public static function delete( string $plugin_file ): array {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
        require_once ABSPATH . 'wp-admin/includes/file.php';

        // Deactivate first
        deactivate_plugins( $plugin_file );

        // Delete
        $result = delete_plugins( [ $plugin_file ] );

        if ( is_wp_error( $result ) ) {
            return [
                'success' => false,
                'message' => $result->get_error_message(),
            ];
        }

        return [
            'success' => true,
            'message' => 'Plugin deleted.',
        ];
    }
}
```

### 5.2 Data Preservation

Plugin data is stored in:
1. **wp_options table** - Settings (preserved automatically)
2. **Custom database tables** - Data (preserved automatically)

The WordPress upgrader only replaces plugin files, not database data. Options and tables persist through updates.

---

## 6. Phase 3: Admin UI Integration

### 6.1 REST API Endpoints

**File:** `includes/Api/class-updater-controller.php`

```php
<?php
/**
 * Updater REST Controller
 */

class WP_AI_Pilot_Updater_Controller extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'wp-ai-pilot/v1';
        $this->rest_base = 'updater';
    }

    public function register_routes() {
        // Get all managed plugins status
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/plugins', [
            'methods'             => 'GET',
            'callback'            => [ $this, 'get_plugins' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );

        // Force check for updates
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/check', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'check_updates' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );

        // Install a plugin
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/install', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'install_plugin' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );

        // Update a plugin
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/update', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'update_plugin' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );

        // Activate a plugin
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/activate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'activate_plugin' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );

        // Deactivate a plugin
        register_rest_route( $this->namespace, '/' . $this->rest_base . '/deactivate', [
            'methods'             => 'POST',
            'callback'            => [ $this, 'deactivate_plugin' ],
            'permission_callback' => [ $this, 'admin_permission' ],
        ] );
    }

    public function admin_permission() {
        return current_user_can( 'install_plugins' );
    }

    public function get_plugins() {
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        return rest_ensure_response( $updater->get_plugins_status() );
    }

    public function check_updates() {
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        return rest_ensure_response( $updater->force_check_updates() );
    }

    public function install_plugin( $request ) {
        $slug = $request->get_param( 'slug' );
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return new WP_Error( 'invalid_plugin', 'Plugin not found' );
        }

        $plugin = $plugins[ $slug ];

        if ( $plugin['installed'] ) {
            return new WP_Error( 'already_installed', 'Plugin is already installed' );
        }

        $result = WP_AI_Pilot_Plugin_Installer::install(
            $plugin['download_url'],
            $plugin['plugin_file']
        );

        return rest_ensure_response( $result );
    }

    public function update_plugin( $request ) {
        $slug = $request->get_param( 'slug' );
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return new WP_Error( 'invalid_plugin', 'Plugin not found' );
        }

        $plugin = $plugins[ $slug ];

        if ( ! $plugin['update_available'] ) {
            return new WP_Error( 'no_update', 'No update available' );
        }

        $result = WP_AI_Pilot_Plugin_Installer::update( $plugin['plugin_file'] );

        return rest_ensure_response( $result );
    }

    public function activate_plugin( $request ) {
        $slug = $request->get_param( 'slug' );
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return new WP_Error( 'invalid_plugin', 'Plugin not found' );
        }

        $result = WP_AI_Pilot_Plugin_Installer::activate( $plugins[ $slug ]['plugin_file'] );

        return rest_ensure_response( $result );
    }

    public function deactivate_plugin( $request ) {
        $slug = $request->get_param( 'slug' );
        $updater = WP_AI_Pilot_GitHub_Updater::get_instance();
        $plugins = $updater->get_plugins_status();

        if ( ! isset( $plugins[ $slug ] ) ) {
            return new WP_Error( 'invalid_plugin', 'Plugin not found' );
        }

        $result = WP_AI_Pilot_Plugin_Installer::deactivate( $plugins[ $slug ]['plugin_file'] );

        return rest_ensure_response( $result );
    }
}
```

### 6.2 React Component for Connected Plugins Page

**File:** `src/components/ManagedPlugins.js`

```jsx
import { useState, useEffect } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

const ManagedPlugins = () => {
    const [plugins, setPlugins] = useState({});
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [actionLoading, setActionLoading] = useState({});

    // Load plugins on mount
    useEffect(() => {
        loadPlugins();
    }, []);

    const loadPlugins = async () => {
        try {
            const data = await apiFetch({ path: '/wp-ai-pilot/v1/updater/plugins' });
            setPlugins(data);
        } catch (error) {
            console.error('Failed to load plugins:', error);
        } finally {
            setLoading(false);
        }
    };

    const checkForUpdates = async () => {
        setChecking(true);
        try {
            await apiFetch({ path: '/wp-ai-pilot/v1/updater/check', method: 'POST' });
            await loadPlugins();
        } catch (error) {
            console.error('Failed to check updates:', error);
        } finally {
            setChecking(false);
        }
    };

    const handleAction = async (slug, action) => {
        setActionLoading({ ...actionLoading, [slug]: action });
        try {
            await apiFetch({
                path: `/wp-ai-pilot/v1/updater/${action}`,
                method: 'POST',
                data: { slug },
            });
            await loadPlugins();
        } catch (error) {
            alert(`Failed to ${action} plugin: ${error.message}`);
        } finally {
            setActionLoading({ ...actionLoading, [slug]: null });
        }
    };

    if (loading) {
        return <div className="loading">Loading plugins...</div>;
    }

    return (
        <div className="managed-plugins">
            <div className="managed-plugins-header">
                <h2>Managed Plugins</h2>
                <button
                    className="btn btn-secondary"
                    onClick={checkForUpdates}
                    disabled={checking}
                >
                    {checking ? 'Checking...' : 'Check for Updates'}
                </button>
            </div>

            <div className="plugins-grid">
                {Object.entries(plugins).map(([slug, plugin]) => (
                    <div key={slug} className="plugin-card">
                        <div className="plugin-icon">
                            <img src={plugin.icon} alt={plugin.name} />
                        </div>

                        <div className="plugin-info">
                            <h3>{plugin.name}</h3>
                            <p>{plugin.description}</p>

                            <div className="plugin-meta">
                                {plugin.installed ? (
                                    <>
                                        <span className="version">v{plugin.current_version}</span>
                                        {plugin.update_available && (
                                            <span className="update-badge">
                                                Update available: v{plugin.remote_version}
                                            </span>
                                        )}
                                        <span className={`status ${plugin.active ? 'active' : 'inactive'}`}>
                                            {plugin.active ? 'Active' : 'Inactive'}
                                        </span>
                                    </>
                                ) : (
                                    <span className="status not-installed">Not Installed</span>
                                )}
                            </div>
                        </div>

                        <div className="plugin-actions">
                            {!plugin.installed ? (
                                <button
                                    className="btn btn-primary"
                                    onClick={() => handleAction(slug, 'install')}
                                    disabled={actionLoading[slug]}
                                >
                                    {actionLoading[slug] === 'install' ? 'Installing...' : 'Install'}
                                </button>
                            ) : (
                                <>
                                    {plugin.update_available && (
                                        <button
                                            className="btn btn-warning"
                                            onClick={() => handleAction(slug, 'update')}
                                            disabled={actionLoading[slug]}
                                        >
                                            {actionLoading[slug] === 'update' ? 'Updating...' : 'Update'}
                                        </button>
                                    )}

                                    {plugin.active ? (
                                        <button
                                            className="btn btn-secondary"
                                            onClick={() => handleAction(slug, 'deactivate')}
                                            disabled={actionLoading[slug]}
                                        >
                                            Deactivate
                                        </button>
                                    ) : (
                                        <button
                                            className="btn btn-success"
                                            onClick={() => handleAction(slug, 'activate')}
                                            disabled={actionLoading[slug]}
                                        >
                                            {actionLoading[slug] === 'activate' ? 'Activating...' : 'Activate'}
                                        </button>
                                    )}
                                </>
                            )}

                            <a
                                href={plugin.github_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="btn btn-link"
                            >
                                GitHub
                            </a>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};

export default ManagedPlugins;
```

---

## 7. Phase 4: Auto-Updates & Cron

### 7.1 Cron Schedule

```php
// In class-github-updater.php __construct()

// Schedule daily check
if ( ! wp_next_scheduled( 'wp_ai_pilot_check_updates' ) ) {
    wp_schedule_event( time(), 'daily', 'wp_ai_pilot_check_updates' );
}

add_action( 'wp_ai_pilot_check_updates', [ $this, 'cron_check_updates' ] );
```

### 7.2 WordPress Auto-Updates Integration

WordPress 5.5+ supports auto-updates. Enable for our plugins:

```php
// Enable auto-updates for managed plugins
add_filter( 'auto_update_plugin', function( $update, $item ) {
    $managed = [
        'WP-AI-Pilot/wp-ai-pilot.php',
        'WP-SEO-Pilot/wp-seo-pilot.php',
    ];

    if ( in_array( $item->plugin, $managed, true ) ) {
        return true; // Auto-update enabled
    }

    return $update;
}, 10, 2 );
```

### 7.3 Email Notifications

```php
// Notify admin when update is available
add_action( 'wp_ai_pilot_update_available', function( $plugin, $new_version ) {
    $admin_email = get_option( 'admin_email' );

    wp_mail(
        $admin_email,
        "Update available: {$plugin['name']} v{$new_version}",
        "A new version of {$plugin['name']} is available.\n\n" .
        "Current version: {$plugin['current_version']}\n" .
        "New version: {$new_version}\n\n" .
        "Update from: " . admin_url( 'admin.php?page=wp-ai-pilot-connected' )
    );
}, 10, 2 );
```

---

## 8. Security Considerations

### 8.1 API Rate Limiting

GitHub API has rate limits:
- **Unauthenticated:** 60 requests/hour
- **Authenticated:** 5,000 requests/hour

With daily checks for 2 plugins = 2 requests/day. Well within limits.

### 8.2 Download Verification

```php
// Verify download is from GitHub
private function verify_download_url( string $url ): bool {
    $allowed_hosts = [
        'github.com',
        'objects.githubusercontent.com',
        'github-releases.githubusercontent.com',
    ];

    $host = parse_url( $url, PHP_URL_HOST );
    return in_array( $host, $allowed_hosts, true );
}
```

### 8.3 Capability Checks

All REST endpoints require `install_plugins` capability (administrator only).

### 8.4 Nonce Verification

WordPress REST API handles nonces automatically via `X-WP-Nonce` header.

---

## 9. Database Schema

No new tables needed. Uses:

1. **Transients** for caching GitHub responses:
   - `wpaipilot_gh_{md5(repo)}` - Cached release info

2. **Options** for settings:
   - `wp_ai_pilot_auto_updates` - Auto-update preferences

---

## 10. File Structure

```
wp-ai-pilot/
├── includes/
│   ├── Updater/
│   │   ├── class-github-updater.php      # Core updater logic
│   │   └── class-plugin-installer.php    # Installation helpers
│   └── Api/
│       └── class-updater-controller.php  # REST endpoints
├── src/
│   ├── pages/
│   │   └── Connected.js                  # Updated page component
│   └── components/
│       └── ManagedPlugins.js             # Plugin cards component
```

---

## 11. API Reference

### REST Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-ai-pilot/v1/updater/plugins` | GET | Get all managed plugins status |
| `/wp-ai-pilot/v1/updater/check` | POST | Force check for updates |
| `/wp-ai-pilot/v1/updater/install` | POST | Install a plugin |
| `/wp-ai-pilot/v1/updater/update` | POST | Update a plugin |
| `/wp-ai-pilot/v1/updater/activate` | POST | Activate a plugin |
| `/wp-ai-pilot/v1/updater/deactivate` | POST | Deactivate a plugin |

### Response Format

```json
{
    "wp-ai-pilot": {
        "plugin_file": "WP-AI-Pilot/wp-ai-pilot.php",
        "name": "WP AI Pilot",
        "description": "Centralized AI management",
        "repo": "jhd3197/WP-AI-Pilot",
        "installed": true,
        "active": true,
        "current_version": "0.0.1",
        "remote_version": "0.0.2",
        "update_available": true,
        "download_url": "https://github.com/.../wp-ai-pilot-0-0-2.zip",
        "github_url": "https://github.com/jhd3197/WP-AI-Pilot",
        "icon": "https://raw.githubusercontent.com/.../icon-128.png"
    },
    "wp-seo-pilot": { ... }
}
```

---

## Implementation Checklist

### Phase 1: Core
- [ ] Create `class-github-updater.php`
- [ ] Add WordPress update hooks
- [ ] Implement GitHub API calls
- [ ] Add version comparison
- [ ] Add folder name fixer

### Phase 2: Installer
- [ ] Create `class-plugin-installer.php`
- [ ] Implement install method
- [ ] Implement update method
- [ ] Implement activate/deactivate

### Phase 3: UI
- [ ] Create `class-updater-controller.php`
- [ ] Add REST endpoints
- [ ] Create `ManagedPlugins.js` component
- [ ] Update Connected page
- [ ] Add styles

### Phase 4: Automation
- [ ] Add daily cron job
- [ ] Add manual check button
- [ ] Add auto-update option
- [ ] Add email notifications

### Testing
- [ ] Test fresh install
- [ ] Test update from older version
- [ ] Test activate/deactivate
- [ ] Test with both plugins
- [ ] Test rate limiting

---

## Next Steps

1. **Start with Phase 1** - Build the core updater class
2. **Add to admin loader** - Register the updater
3. **Build REST API** - Phase 2 + 3
4. **Update React UI** - Connected plugins page
5. **Test thoroughly** - All scenarios

Would you like me to proceed with implementation?
