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
use Saman\SEO\Updater\GitHub_Updater;

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
        'saman-seo-local-seo'    => 'saman-seo-settings',
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

        // Initialize GitHub Updater.
        GitHub_Updater::get_instance();
    }

    /**
     * Load updater classes.
     */
    private function load_updater_classes() {
        $updater_dir = SAMAN_SEO_PATH . 'includes/Updater/';

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
        // Main menu
        add_menu_page(
            __( 'Saman SEO', 'saman-seo' ),
            __( 'Saman SEO', 'saman-seo' ),
            'manage_options',
            self::MENU_SLUG,
            [ $this, 'render_app' ],
            'dashicons-airplane',
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

        // @wordpress/scripts outputs to build-v2/ folder
        $build_dir = SAMAN_SEO_PATH . 'build-v2/';
        $build_url = SAMAN_SEO_URL . 'build-v2/';

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
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : self::MENU_SLUG;
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
                $class = "\\Saman\SEO\\Api\\{$controller}_Controller";
                if ( class_exists( $class ) ) {
                    ( new $class() )->register_routes();
                }
            }
        }
    }
}
