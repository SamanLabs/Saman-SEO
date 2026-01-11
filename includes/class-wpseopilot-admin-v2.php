<?php
/**
 * WP SEO Pilot V2 Admin Loader
 *
 * Handles the React-based admin interface for V2.
 *
 * @package WPSEOPilot
 * @since 0.2.0
 */

namespace WPSEOPilot;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Admin_V2 class - Manages the V2 React admin interface.
 */
class Admin_V2 {

    /**
     * Singleton instance.
     *
     * @var Admin_V2|null
     */
    private static $instance = null;

    /**
     * View mapping for WordPress pages to React views.
     *
     * @var array
     */
    private $view_map = [
        'wpseopilot-v2'                    => 'dashboard',
        'wpseopilot-v2-dashboard'          => 'dashboard',
        'wpseopilot-v2-search-appearance'  => 'search-appearance',
        'wpseopilot-v2-sitemap'            => 'sitemap',
        'wpseopilot-v2-redirects'          => 'redirects',
        'wpseopilot-v2-internal-linking'   => 'internal-linking',
        'wpseopilot-v2-audit'              => 'audit',
        'wpseopilot-v2-ai-assistant'       => 'ai-assistant',
        'wpseopilot-v2-settings'           => 'settings',
        'wpseopilot-v2-more'               => 'more',
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
        add_action( 'admin_menu', [ $this, 'register_menu' ], 11 );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
        add_action( 'rest_api_init', [ $this, 'register_routes' ] );
    }

    /**
     * Register admin menu and submenus.
     */
    public function register_menu() {
        // Main V2 menu
        add_menu_page(
            __( 'WP SEO Pilot V2', 'wp-seo-pilot' ),
            __( 'WP SEO Pilot V2', 'wp-seo-pilot' ),
            'manage_options',
            'wpseopilot-v2',
            [ $this, 'render_app' ],
            'dashicons-airplane',
            99
        );

        // Submenu items - matching Header.js navItems
        $subpages = [
            'dashboard'          => __( 'Dashboard', 'wp-seo-pilot' ),
            'search-appearance'  => __( 'Search Appearance', 'wp-seo-pilot' ),
            'sitemap'            => __( 'Sitemap', 'wp-seo-pilot' ),
            'redirects'          => __( 'Redirects', 'wp-seo-pilot' ),
            'internal-linking'   => __( 'Internal Linking', 'wp-seo-pilot' ),
            'audit'              => __( 'Audit', 'wp-seo-pilot' ),
            'ai-assistant'       => __( 'AI Assistant', 'wp-seo-pilot' ),
            'settings'           => __( 'Settings', 'wp-seo-pilot' ),
            'more'               => __( 'More', 'wp-seo-pilot' ),
        ];

        foreach ( $subpages as $slug => $title ) {
            add_submenu_page(
                'wpseopilot-v2',
                $title,
                $title,
                'manage_options',
                'wpseopilot-v2-' . $slug,
                [ $this, 'render_app' ]
            );
        }

        // Remove duplicate first submenu item (WordPress auto-creates it)
        remove_submenu_page( 'wpseopilot-v2', 'wpseopilot-v2' );
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
        // Only load on our plugin pages
        if ( strpos( $hook, 'wpseopilot-v2' ) === false ) {
            return;
        }

        // @wordpress/scripts outputs to build-v2/ folder
        $build_dir = WPSEOPILOT_PATH . 'build-v2/';
        $build_url = WPSEOPILOT_URL . 'build-v2/';

        $asset_file = $build_dir . 'index.asset.php';
        $asset = file_exists( $asset_file )
            ? require $asset_file
            : [
                'dependencies' => [ 'wp-api-fetch', 'wp-element' ],
                'version'      => WPSEOPILOT_VERSION,
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
        $page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : 'wpseopilot-v2';
        $initial_view = isset( $this->view_map[ $page ] ) ? $this->view_map[ $page ] : 'dashboard';

        // Pass configuration to React app
        wp_localize_script( 'wpseopilot-admin-v2', 'wpseopilotV2Settings', [
            'initialView' => $initial_view,
            'restUrl'     => rest_url( 'wpseopilot/v2/' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'adminUrl'    => admin_url(),
            'pluginUrl'   => WPSEOPILOT_URL,
            'version'     => WPSEOPILOT_VERSION,
            'viewMap'     => $this->view_map,
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
        $controllers_dir = WPSEOPILOT_PATH . 'includes/Api/';

        // Only proceed if directory exists
        if ( ! is_dir( $controllers_dir ) ) {
            return;
        }

        $controllers = [
            'Settings',
            'Redirects',
            'InternalLinks',
            'Sitemap',
            'Audit',
            'Ai',
        ];

        foreach ( $controllers as $controller ) {
            $class = "\\WPSEOPilot\\Api\\{$controller}_Controller";
            if ( class_exists( $class ) ) {
                ( new $class() )->register_routes();
            }
        }
    }
}
