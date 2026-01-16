<?php
/**
 * WP SEO Pilot Admin Loader
 *
 * Handles the React-based admin interface.
 *
 * @package SamanLabs\SEO
 * @since 0.2.0
 */

namespace SamanLabs\SEO;

use SamanLabs\SEO\Integration\AI_Pilot;
use SamanLabs\SEO\Updater\GitHub_Updater;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin_V2 class - Manages the React admin interface.
 */
class Admin_V2 {

    /**
     * Singleton instance.
     *
     * @var Admin_V2|null
     */
    private static $instance = null;

    /**
     * Main menu slug.
     *
     * @var string
     */
    const MENU_SLUG = 'wpseopilot';

    /**
     * View mapping for WordPress pages to React views.
     * Maps both new URLs and legacy V2 URLs for backwards compatibility.
     *
     * @var array
     */
    private $view_map = [
        // New URLs (primary)
        'wpseopilot'                    => 'dashboard',
        'wpseopilot-dashboard'          => 'dashboard',
        'wpseopilot-search-appearance'  => 'search-appearance',
        'wpseopilot-sitemap'            => 'sitemap',
        'wpseopilot-tools'              => 'tools',
        'wpseopilot-redirects'          => 'redirects',
        'wpseopilot-404-log'            => '404-log',
        'wpseopilot-internal-linking'   => 'internal-linking',
        'wpseopilot-audit'              => 'audit',
        'wpseopilot-ai-assistant'       => 'ai-assistant',
        'wpseopilot-assistants'         => 'assistants',
        'wpseopilot-settings'           => 'settings',
        'wpseopilot-more'               => 'more',
        'wpseopilot-bulk-editor'        => 'bulk-editor',
        'wpseopilot-content-gaps'       => 'content-gaps',
        'wpseopilot-schema-builder'     => 'schema-builder',
        'wpseopilot-link-health'        => 'link-health',
        'wpseopilot-local-seo'          => 'local-seo',
        'wpseopilot-robots-txt'         => 'robots-txt',
        'wpseopilot-image-seo'          => 'image-seo',
        'wpseopilot-instant-indexing'   => 'instant-indexing',
        'wpseopilot-schema-validator'   => 'schema-validator',
        'wpseopilot-htaccess-editor'    => 'htaccess-editor',
        'wpseopilot-mobile-friendly'    => 'mobile-friendly',
        // Legacy V2 URLs (backwards compatibility)
        'wpseopilot-v2'                    => 'dashboard',
        'wpseopilot-v2-dashboard'          => 'dashboard',
        'wpseopilot-v2-search-appearance'  => 'search-appearance',
        'wpseopilot-v2-sitemap'            => 'sitemap',
        'wpseopilot-v2-tools'              => 'tools',
        'wpseopilot-v2-redirects'          => 'redirects',
        'wpseopilot-v2-404-log'            => '404-log',
        'wpseopilot-v2-internal-linking'   => 'internal-linking',
        'wpseopilot-v2-audit'              => 'audit',
        'wpseopilot-v2-ai-assistant'       => 'ai-assistant',
        'wpseopilot-v2-assistants'         => 'assistants',
        'wpseopilot-v2-settings'           => 'settings',
        'wpseopilot-v2-more'               => 'more',
        'wpseopilot-v2-bulk-editor'        => 'bulk-editor',
        'wpseopilot-v2-content-gaps'       => 'content-gaps',
        'wpseopilot-v2-schema-builder'     => 'schema-builder',
        'wpseopilot-v2-link-health'        => 'link-health',
    ];

    /**
     * Legacy V1 URL to new URL mapping for redirects.
     *
     * @var array
     */
    private $legacy_redirects = [
        // Only include redirects where old URL differs from new URL
        'wpseopilot-types'        => 'wpseopilot-search-appearance',
        'wpseopilot-404-errors'   => 'wpseopilot-404-log',
        'wpseopilot-internal'     => 'wpseopilot-internal-linking',
        'wpseopilot-ai'           => 'wpseopilot-ai-assistant',
        'wpseopilot-local-seo'    => 'wpseopilot-settings',
        'wpseopilot-links'        => 'wpseopilot-internal-linking',
        'wpseopilot-404'          => 'wpseopilot-404-log',
    ];

    /**
     * Get singleton instance.
     *
     * @return Admin_V2
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Register hooks.
     */
    private function __construct() {
        $this->load_updater_classes();

        add_action( 'admin_menu', [ $this, 'register_menu' ], 5 ); // Priority 5 to run before V1
        add_action( 'admin_init', [ $this, 'handle_legacy_redirects' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );

        // Initialize GitHub Updater.
        GitHub_Updater::get_instance();
    }

    /**
     * Load updater classes.
     */
    private function load_updater_classes() {
        $updater_dir = SAMANLABS_SEO_PATH . 'includes/Updater/';

        if ( file_exists( $updater_dir . 'class-github-updater.php' ) ) {
            require_once $updater_dir . 'class-github-updater.php';
        }
        if ( file_exists( $updater_dir . 'class-plugin-installer.php' ) ) {
            require_once $updater_dir . 'class-plugin-installer.php';
        }
    }

    /**
     * Handle redirects from legacy V1 and V2 URLs.
     */
    public function handle_legacy_redirects() {
        if ( ! is_admin() || ! isset( $_GET['page'] ) ) {
            return;
        }

        $page = sanitize_text_field( $_GET['page'] );

        // Redirect legacy V1 URLs
        if ( isset( $this->legacy_redirects[ $page ] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . $this->legacy_redirects[ $page ] ) );
            exit;
        }

        // Redirect old V2 URLs to new URLs (remove -v2 prefix)
        if ( strpos( $page, 'wpseopilot-v2' ) === 0 ) {
            $new_page = str_replace( 'wpseopilot-v2', 'wpseopilot', $page );
            wp_safe_redirect( admin_url( 'admin.php?page=' . $new_page ) );
            exit;
        }
    }

    /**
     * Register admin menu and submenus.
     */
    public function register_menu() {
        // Main menu
        add_menu_page(
            __( 'WP SEO Pilot', 'saman-labs-seo' ),
            __( 'WP SEO Pilot', 'saman-labs-seo' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_app' ],
            'dashicons-airplane',
            58
        );

        // Visible submenu items - matching Header.js navItems
        $visible_subpages = [
            'dashboard'          => __( 'Dashboard', 'saman-labs-seo' ),
            'search-appearance'  => __( 'Search Appearance', 'saman-labs-seo' ),
            'sitemap'            => __( 'Sitemap', 'saman-labs-seo' ),
            'tools'              => __( 'Tools', 'saman-labs-seo' ),
            'settings'           => __( 'Settings', 'saman-labs-seo' ),
            'more'               => __( 'More', 'saman-labs-seo' ),
        ];

        foreach ( $visible_subpages as $slug => $title ) {
            add_submenu_page(
                self::MENU_SLUG,
                $title,
                $title,
                'manage_options',
                self::MENU_SLUG . '-' . $slug,
                [ $this, 'render_app' ]
            );
        }

        // Hidden subpages - accessible via React navigation but not shown in WP menu
        $hidden_subpages = [
            'redirects'        => __( 'Redirects', 'saman-labs-seo' ),
            '404-log'          => __( '404 Log', 'saman-labs-seo' ),
            'internal-linking' => __( 'Internal Linking', 'saman-labs-seo' ),
            'audit'            => __( 'Site Audit', 'saman-labs-seo' ),
            'ai-assistant'     => __( 'AI Assistant', 'saman-labs-seo' ),
            'assistants'       => __( 'AI Assistants', 'saman-labs-seo' ),
            'bulk-editor'      => __( 'Bulk Editor', 'saman-labs-seo' ),
            'content-gaps'     => __( 'Content Gaps', 'saman-labs-seo' ),
            'schema-builder'   => __( 'Schema Builder', 'saman-labs-seo' ),
            'link-health'      => __( 'Link Health', 'saman-labs-seo' ),
            'local-seo'        => __( 'Local SEO', 'saman-labs-seo' ),
            'robots-txt'        => __( 'robots.txt Editor', 'saman-labs-seo' ),
            'image-seo'         => __( 'Image SEO', 'saman-labs-seo' ),
            'instant-indexing'  => __( 'Instant Indexing', 'saman-labs-seo' ),
            'schema-validator'  => __( 'Schema Validator', 'saman-labs-seo' ),
            'htaccess-editor'   => __( '.htaccess Editor', 'saman-labs-seo' ),
            'mobile-friendly'   => __( 'Mobile Friendly Test', 'saman-labs-seo' ),
        ];

        foreach ( $hidden_subpages as $slug => $title ) {
            add_submenu_page(
                null, // null parent = hidden from menu
                $title,
                $title,
                'manage_options',
                self::MENU_SLUG . '-' . $slug,
                [ $this, 'render_app' ]
            );
        }

        // Remove duplicate first submenu item (WordPress auto-creates it)
        remove_submenu_page( self::MENU_SLUG, self::MENU_SLUG );
    }

    /**
     * Render the React app mount point.
     */
    public function render_app() {
        echo '<div id="wpseopilot-v2-root"></div>';
    }

    /**
     * Enqueue React app assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        // Only load on our plugin pages (both new and legacy URLs)
        if ( strpos( $hook, 'wpseopilot' ) === false ) {
            return;
        }

        // @wordpress/scripts outputs to build-v2/ folder
        $build_dir = SAMANLABS_SEO_PATH . 'build-v2/';
        $build_url = SAMANLABS_SEO_URL . 'build-v2/';

        $asset_file = $build_dir . 'index.asset.php';
        $asset = file_exists( $asset_file )
            ? require $asset_file
            : [
                'dependencies' => [ 'wp-api-fetch', 'wp-element' ],
                'version'      => SAMANLABS_SEO_VERSION,
            ];

        // Enqueue React app script
        wp_enqueue_script(
            'wpseopilot-admin-v2',
            $build_url . 'index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        // Enqueue React app styles (bundled by webpack)
        wp_enqueue_style(
            'wpseopilot-admin-v2',
            $build_url . 'index.css',
            [],
            $asset['version']
        );

        // Determine initial view from page parameter
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : self::MENU_SLUG;
        $initial_view = isset( $this->view_map[ $page ] ) ? $this->view_map[ $page ] : 'dashboard';

        // Get AI status
        $ai_status   = AI_Pilot::get_status();
        $ai_enabled  = AI_Pilot::ai_enabled();
        $ai_provider = AI_Pilot::get_provider();

        // Pass configuration to React app
        wp_localize_script( 'wpseopilot-admin-v2', 'wpseopilotV2Settings', [
            'initialView' => $initial_view,
            'restUrl'     => rest_url( 'wpseopilot/v2/' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'adminUrl'    => admin_url(),
            'pluginUrl'   => SAMANLABS_SEO_URL,
            'version'     => SAMANLABS_SEO_VERSION,
            'viewMap'     => $this->view_map,
            'menuSlug'    => self::MENU_SLUG,
            'aiEnabled'   => $ai_enabled,
            'aiProvider'  => $ai_provider,
            'aiPilot'     => [
                'installed'   => $ai_status['installed'],
                'active'      => $ai_status['active'],
                'ready'       => $ai_status['ready'],
                'version'     => $ai_status['version'] ?? null,
                'settingsUrl' => admin_url( 'admin.php?page=wp-ai-pilot' ),
            ],
        ] );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Load REST controllers
        $this->load_rest_controllers();
    }

    /**
     * Load and initialize REST API controllers.
     */
    private function load_rest_controllers() {
        $controllers_dir = SAMANLABS_SEO_PATH . 'includes/Api/';

        // Only proceed if directory exists
        if ( ! is_dir( $controllers_dir ) ) {
            return;
        }

        // Load base controller first
        $base_file = $controllers_dir . 'class-rest-controller.php';
        if ( file_exists( $base_file ) ) {
            require_once $base_file;
        }

        $controllers = [
            'Settings'         => 'class-settings-controller.php',
            'Redirects'        => 'class-redirects-controller.php',
            'InternalLinks'    => 'class-internallinks-controller.php',
            'Sitemap'          => 'class-sitemap-controller.php',
            'Audit'            => 'class-audit-controller.php',
            'Ai'               => 'class-ai-controller.php',
            'SearchAppearance' => 'class-searchappearance-controller.php',
            'Dashboard'        => 'class-dashboard-controller.php',
            'Assistants'       => 'class-assistants-controller.php',
            'Setup'            => 'class-setup-controller.php',
            'Tools'            => 'class-tools-controller.php',
            'Updater'          => 'class-updater-controller.php',
            'Link_Health'      => 'class-link-health-controller.php',
            'Breadcrumbs'      => 'class-breadcrumbs-controller.php',
            'IndexNow'         => 'class-indexnow-controller.php',
            'Schema_Validator' => 'class-schema-validator-controller.php',
            'Htaccess'         => 'class-htaccess-controller.php',
            'Mobile_Test'      => 'class-mobile-test-controller.php',
        ];

        foreach ( $controllers as $controller => $file ) {
            $file_path = $controllers_dir . $file;
            if ( file_exists( $file_path ) ) {
                require_once $file_path;
                $class = "\\SamanLabs\SEO\\Api\\{$controller}_Controller";
                if ( class_exists( $class ) ) {
                    ( new $class() )->register_routes();
                }
            }
        }
    }
}
