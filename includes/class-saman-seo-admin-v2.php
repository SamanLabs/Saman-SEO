<?php
/**
 * Saman SEO Admin Loader
 *
 * Handles the React-based admin interface.
 *
 * @package Saman\SEO
 * @since 0.2.0
 */

namespace Saman\SEO;

use Saman\SEO\Integration\AI_Pilot;

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
    const MENU_SLUG = 'saman-seo';

    /**
     * View mapping for WordPress pages to React views.
     * Maps both new URLs and legacy V2 URLs for backwards compatibility.
     *
     * @var array
     */
    private $view_map = [
        // New URLs (primary)
        'saman-seo'                    => 'dashboard',
        'saman-seo-dashboard'          => 'dashboard',
        'saman-seo-search-appearance'  => 'search-appearance',
        'saman-seo-sitemap'            => 'sitemap',
        'saman-seo-tools'              => 'tools',
        'saman-seo-redirects'          => 'redirects',
        'saman-seo-404-log'            => '404-log',
        'saman-seo-internal-linking'   => 'internal-linking',
        'saman-seo-audit'              => 'audit',
        'saman-seo-ai-assistant'       => 'ai-assistant',
        'saman-seo-assistants'         => 'assistants',
        'saman-seo-settings'           => 'settings',
        'saman-seo-more'               => 'more',
        'saman-seo-bulk-editor'        => 'bulk-editor',
        'saman-seo-content-gaps'       => 'content-gaps',
        'saman-seo-schema-builder'     => 'schema-builder',
        'saman-seo-link-health'        => 'link-health',
        'saman-seo-local-seo'          => 'local-seo',
        'saman-seo-robots-txt'         => 'robots-txt',
        'saman-seo-image-seo'          => 'image-seo',
        'saman-seo-instant-indexing'   => 'instant-indexing',
        'saman-seo-schema-validator'   => 'schema-validator',
        'saman-seo-htaccess-editor'    => 'htaccess-editor',
        'saman-seo-mobile-friendly'    => 'mobile-friendly',
        // Legacy V2 URLs (backwards compatibility)
        'saman-seo-v2'                    => 'dashboard',
        'saman-seo-v2-dashboard'          => 'dashboard',
        'saman-seo-v2-search-appearance'  => 'search-appearance',
        'saman-seo-v2-sitemap'            => 'sitemap',
        'saman-seo-v2-tools'              => 'tools',
        'saman-seo-v2-redirects'          => 'redirects',
        'saman-seo-v2-404-log'            => '404-log',
        'saman-seo-v2-internal-linking'   => 'internal-linking',
        'saman-seo-v2-audit'              => 'audit',
        'saman-seo-v2-ai-assistant'       => 'ai-assistant',
        'saman-seo-v2-assistants'         => 'assistants',
        'saman-seo-v2-settings'           => 'settings',
        'saman-seo-v2-more'               => 'more',
        'saman-seo-v2-bulk-editor'        => 'bulk-editor',
        'saman-seo-v2-content-gaps'       => 'content-gaps',
        'saman-seo-v2-schema-builder'     => 'schema-builder',
        'saman-seo-v2-link-health'        => 'link-health',
    ];

    /**
     * Legacy V1 URL to new URL mapping for redirects.
     *
     * @var array
     */
    private $legacy_redirects = [
        // Only include redirects where old URL differs from new URL
        'saman-seo-types'        => 'saman-seo-search-appearance',
        'saman-seo-404-errors'   => 'saman-seo-404-log',
        'saman-seo-internal'     => 'saman-seo-internal-linking',
        'saman-seo-ai'           => 'saman-seo-ai-assistant',
        'saman-seo-links'        => 'saman-seo-internal-linking',
        'saman-seo-404'          => 'saman-seo-404-log',
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

        // GitHub Updater removed for WordPress.org compatibility.
    }

    /**
     * Load updater classes.
     */
    private function load_updater_classes() {
        $updater_dir = SAMAN_SEO_PATH . 'includes/Updater/';

        // Plugin registry for "More" page plugin promotion.
        if ( file_exists( $updater_dir . 'class-plugin-registry.php' ) ) {
            require_once $updater_dir . 'class-plugin-registry.php';
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

        $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

        // Redirect legacy V1 URLs
        if ( isset( $this->legacy_redirects[ $page ] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=' . $this->legacy_redirects[ $page ] ) );
            exit;
        }

        // Redirect old V2 URLs to new URLs (remove -v2 prefix)
        if ( strpos( $page, 'saman-seo-v2' ) === 0 ) {
            $new_page = str_replace( 'saman-seo-v2', 'saman-seo', $page );
            wp_safe_redirect( admin_url( 'admin.php?page=' . $new_page ) );
            exit;
        }
    }

    /**
     * Register admin menu and submenus.
     */
    public function register_menu() {
        // Custom SVG icon (base64 encoded)
        $icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="491 491 2048 2048"><path fill="black" d="M2009.2,1575.62c-20.77,97.29-56.47,168.25-111.98,249.43l44.32,45.17c29.65-3.23,51.62,10.65,71.38,30.51l247,248.18c33.25,33.41,45.11,80.99,29.16,125.89-16.43,46.25-59.94,78.94-109.58,81.07-36.57,1.57-68.29-15.42-93.1-40.42l-49.57-49.96-198.34-199.1c-17.61-17.68-26.2-39.72-25.72-64.12l-48.52-44.54c-4.29,2.17-7.75,4.08-11.36,6.68-174.71,125.76-394.51,153.76-595.73,77.03-41.9-15.98-80.46-36.62-117.89-60.92-63.98-41.55-119.43-93.05-165.46-153.92-39.66-52.45-70.22-110.63-92.23-172.79-47.86-135.17-47.62-277.57-3.08-413.39,37.19-113.4,110.51-217.97,202.41-293.57,55.77-45.87,107.56-76.55,174.88-103.29,50.67-20.12,102.36-32.12,156.56-38.67,106.97-12.93,225.92,4.33,324.11,48.58,42.42,19.12,82.29,39.78,119.05,69.06l-59.71,61.31-28.09,29.35-42.37-24.49c-33.48-19.35-69.27-31.7-106.93-42.27-89.28-25.07-184.2-23.74-273.39,1.03-28.97,8.05-54.55,18.2-81.3,31.09-257.28,123.98-366.11,433.13-239.74,690.72,19.64,40.04,44.9,75.12,73.49,108.79,15.75,18.54,32.2,34.45,50.38,50.37,141.85,124.23,340.92,162.64,517.54,93.77,51.48-20.07,99.06-46.68,141.65-81.9,36.95-30.56,69.48-63.27,97.07-102.83,25.12-36.02,45.92-74.51,61.13-116.11,26.8-73.29,36.13-151.35,28.93-229.05-6.93-72.22-27.7-140.49-63.88-204.66l89.83-92.32c22.95,35.88,40.95,72.55,57.2,111.34,12.81,33.54,23.36,65.9,30.61,101.04,20.68,88.49,20.24,179.09,1.28,267.92Z"/><path fill="black" d="M958.58,1578.07c-9.76-28.54-16.85-55.64-17.74-85.19-.19-6.31,2.22-10.1,6.66-14.52l149.64-148.92,152.41-152.13c7.93-7.92,49.65-51.13,55.97-50.64,2.04.16,6.05,2.26,8.05,4.25l166.59,165.17,214.54-218.28,165.52-167.93-97.61-98.1,39.87-6.7,150.59-23.78,131.62-19.78-1.87,11.77-26.41,158.67-24.61,149.71-45.07-44.26-50.09-50.26-250.57,254.99-137.84,140.16-65.36,65.68-19.39-19.26-157.09-156.33c-20.87,18.85-38.51,38.26-57.83,57.76l-204.48,206.48-67.63,68.54c-7.72,7.82-15.16-19.12-17.88-27.08Z"/><path fill="black" d="M1478.64,1861.67c0,7.32-2.73,10.09-8.61,11.13-64.81,11.48-107.08,11.42-171.66-.54-3.65-.68-6.87-3.89-6.87-8.31l.06-475.69c0-7.98,10.04-15.35,16.58-12.66l143.78,144.8c15.11,15.21,26.64,7.34,26.64,27.5l.06,313.78Z"/><path fill="black" d="M1735.84,1707.89c-38.05,54.54-99.32,100.25-158.33,129.87-3.97,1.99-7.09,4.54-11.89,1.92l.43-359.73c0-7.78.53-17.63,4.55-23.32,7.96-11.27,16.97-19.9,26.69-29.73l153.03-154.62c4.23,8.13,4.92,16.39,4.93,25.6l.3,337.95c.02,17.31-4.09,33.32-8.31,49.63-2,7.74-6.81,15.85-11.39,22.41Z"/><path fill="black" d="M1032.27,1708.49c-8.34-12.18-12.95-27.87-7.29-41.4,3.62-8.64,11.57-15.25,17.95-21.98l57.64-60.7,91.89-95.92c.91-.95,4.39-3.67,5.19-2.7s2.27,4.21,2.27,5.91l.03,350.17c-43.86-16.17-87.21-49.17-119.56-78.46-18.32-16.58-33.8-34-48.12-54.92Z"/></svg>';
        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode -- Required for SVG data URI.
        $icon_data_uri = 'data:image/svg+xml;base64,' . base64_encode( $icon_svg );

        // Main menu
        add_menu_page(
            __( 'Saman SEO', 'saman-seo' ),
            __( 'Saman SEO', 'saman-seo' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_app' ],
            $icon_data_uri,
            58
        );

        // Visible submenu items - matching Header.js navItems
        $visible_subpages = [
            'dashboard'          => __( 'Dashboard', 'saman-seo' ),
            'search-appearance'  => __( 'Search Appearance', 'saman-seo' ),
            'tools'              => __( 'Tools', 'saman-seo' ),
            'settings'           => __( 'Settings', 'saman-seo' ),
            'more'               => __( 'More', 'saman-seo' ),
        ];

        // Conditionally add sitemap menu based on module toggle.
        if ( \Saman\SEO\Helpers\module_enabled( 'sitemap' ) ) {
            $visible_subpages['sitemap'] = __( 'Sitemap', 'saman-seo' );
        }

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
            'audit'            => __( 'Site Audit', 'saman-seo' ),
            'bulk-editor'      => __( 'Bulk Editor', 'saman-seo' ),
            'content-gaps'     => __( 'Content Gaps', 'saman-seo' ),
            'schema-builder'   => __( 'Schema Builder', 'saman-seo' ),
            'link-health'      => __( 'Link Health', 'saman-seo' ),
            'robots-txt'        => __( 'robots.txt Editor', 'saman-seo' ),
            'image-seo'         => __( 'Image SEO', 'saman-seo' ),
            'instant-indexing'  => __( 'Instant Indexing', 'saman-seo' ),
            'schema-validator'  => __( 'Schema Validator', 'saman-seo' ),
            'htaccess-editor'   => __( '.htaccess Editor', 'saman-seo' ),
            'mobile-friendly'   => __( 'Mobile Friendly Test', 'saman-seo' ),
        ];

        // Conditionally add module-dependent hidden pages.
        if ( \Saman\SEO\Helpers\module_enabled( 'redirects' ) ) {
            $hidden_subpages['redirects'] = __( 'Redirects', 'saman-seo' );
        }
        if ( \Saman\SEO\Helpers\module_enabled( '404_log' ) ) {
            $hidden_subpages['404-log'] = __( '404 Log', 'saman-seo' );
        }
        if ( \Saman\SEO\Helpers\module_enabled( 'internal_links' ) ) {
            $hidden_subpages['internal-linking'] = __( 'Internal Linking', 'saman-seo' );
        }
        if ( \Saman\SEO\Helpers\module_enabled( 'local_seo' ) ) {
            $hidden_subpages['local-seo'] = __( 'Local SEO', 'saman-seo' );
        }
        if ( \Saman\SEO\Helpers\module_enabled( 'ai_assistant' ) ) {
            $hidden_subpages['ai-assistant'] = __( 'AI Assistant', 'saman-seo' );
            $hidden_subpages['assistants'] = __( 'AI Assistants', 'saman-seo' );
        }

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
        echo '<div id="saman-seo-v2-root"></div>';
    }

    /**
     * Enqueue React app assets.
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_assets( $hook ) {
        // Only load on our plugin pages (both new and legacy URLs)
        if ( strpos( $hook, 'saman-seo' ) === false ) {
            return;
        }

        // @wordpress/scripts outputs to build/v2/ folder
        $build_dir = SAMAN_SEO_PATH . 'build/v2/';
        $build_url = SAMAN_SEO_URL . 'build/v2/';

        $asset_file = $build_dir . 'index.asset.php';
        $asset = file_exists( $asset_file )
            ? require $asset_file
            : [
                'dependencies' => [ 'wp-api-fetch', 'wp-element' ],
                'version'      => SAMAN_SEO_VERSION,
            ];

        // Enqueue React app script
        wp_enqueue_script(
            'saman-seo-admin-v2',
            $build_url . 'index.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        // Enqueue React app styles (bundled by webpack)
        wp_enqueue_style(
            'saman-seo-admin-v2',
            $build_url . 'index.css',
            [],
            $asset['version']
        );

        // Determine initial view from page parameter
        $page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : self::MENU_SLUG;
        $initial_view = isset( $this->view_map[ $page ] ) ? $this->view_map[ $page ] : 'dashboard';

        // Get AI status
        $ai_status   = AI_Pilot::get_status();
        $ai_enabled  = AI_Pilot::ai_enabled();
        $ai_provider = AI_Pilot::get_provider();

        // Get module status for React UI
        $modules = [
            'sitemap'        => \Saman\SEO\Helpers\module_enabled( 'sitemap' ),
            'redirects'      => \Saman\SEO\Helpers\module_enabled( 'redirects' ),
            '404_log'        => \Saman\SEO\Helpers\module_enabled( '404_log' ),
            'llm_txt'        => \Saman\SEO\Helpers\module_enabled( 'llm_txt' ),
            'local_seo'      => \Saman\SEO\Helpers\module_enabled( 'local_seo' ),
            'social_cards'   => \Saman\SEO\Helpers\module_enabled( 'social_cards' ),
            'analytics'      => \Saman\SEO\Helpers\module_enabled( 'analytics' ),
            'admin_bar'      => \Saman\SEO\Helpers\module_enabled( 'admin_bar' ),
            'internal_links' => \Saman\SEO\Helpers\module_enabled( 'internal_links' ),
            'ai_assistant'   => \Saman\SEO\Helpers\module_enabled( 'ai_assistant' ),
        ];

        // Pass configuration to React app
        wp_localize_script( 'saman-seo-admin-v2', 'saman-seoV2Settings', [
            'initialView' => $initial_view,
            'restUrl'     => rest_url( 'saman-seo/v1/' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'adminUrl'    => admin_url(),
            'pluginUrl'   => SAMAN_SEO_URL,
            'version'     => SAMAN_SEO_VERSION,
            'viewMap'     => $this->view_map,
            'menuSlug'    => self::MENU_SLUG,
            'aiEnabled'   => $ai_enabled,
            'aiProvider'  => $ai_provider,
            'aiPilot'     => [
                'installed'   => $ai_status['installed'],
                'active'      => $ai_status['active'],
                'ready'       => $ai_status['ready'],
                'version'     => $ai_status['version'] ?? null,
                'settingsUrl' => admin_url( 'admin.php?page=Saman-ai' ),
            ],
            'modules'     => $modules,
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
        $controllers_dir = SAMAN_SEO_PATH . 'includes/Api/';

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
            'More'             => 'class-more-controller.php',
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
                $class = "\\Saman\SEO\\Api\\{$controller}_Controller";
                if ( class_exists( $class ) ) {
                    ( new $class() )->register_routes();
                }
            }
        }
    }
}
