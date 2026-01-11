<?php
/**
 * Internal Linking module service provider.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

use WPSEOPilot\Internal_Linking\Engine as Linking_Engine;
use WPSEOPilot\Internal_Linking\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Registers admin UI, handlers, and runtime hooks for Internal Linking.
 */
class Internal_Linking {

	/**
	 * Capability constant.
	 */
	public const CAPABILITY = 'manage_seopilot_links';

	/**
	 * Page slug.
	 */
	private const PAGE_SLUG = 'wpseopilot-links';

	/**
	 * Flash transient key.
	 */
	private const NOTICE_TRANSIENT = 'wpseopilot_links_notices';

	/**
	 * Admin page hook suffix.
	 *
	 * @var string|null
	 */
	private $page_hook = null;

	/**
	 * Data repository.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Runtime engine.
	 *
	 * @var Linking_Engine
	 */
	private $engine;

	/**
	 * Constructor.
	 *
	 * @param Repository|null $repository Optional custom repository for testing.
	 */
	public function __construct( Repository $repository = null ) {
		$this->repository = $repository ?: new Repository();
		$this->engine     = new Linking_Engine( $this->repository );
	}

	/**
	 * Activation tasks.
	 *
	 * @return void
	 */
	public static function activate() {
		add_option( 'wpseopilot_link_rules', [] );
		add_option( 'wpseopilot_link_categories', [] );
		add_option( 'wpseopilot_link_utm_templates', [] );

		$repository = new Repository();
		add_option( 'wpseopilot_link_settings', $repository->get_default_settings() );
		$repository->get_version();

		foreach ( [ 'administrator' ] as $role_name ) {
			$role = get_role( $role_name );
			if ( $role && ! $role->has_cap( self::CAPABILITY ) ) {
				$role->add_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * Hook registrations.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', [ $this, 'ensure_role_capabilities' ] );
		// V1 menu disabled - React UI handles menu registration
		// add_action( 'admin_menu', [ $this, 'register_menu' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
		add_action( 'admin_post_wpseopilot_save_link_rule', [ $this, 'handle_save_rule' ] );
		add_action( 'admin_post_wpseopilot_delete_link_rule', [ $this, 'handle_delete_rule' ] );
		add_action( 'admin_post_wpseopilot_bulk_link_rules', [ $this, 'handle_bulk_rules' ] );
		add_action( 'admin_post_wpseopilot_duplicate_link_rule', [ $this, 'handle_duplicate_rule' ] );
		add_action( 'admin_post_wpseopilot_toggle_link_rule', [ $this, 'handle_toggle_rule' ] );
		add_action( 'admin_post_wpseopilot_save_link_category', [ $this, 'handle_save_category' ] );
		add_action( 'admin_post_wpseopilot_delete_link_category', [ $this, 'handle_delete_category' ] );
		add_action( 'admin_post_wpseopilot_save_link_template', [ $this, 'handle_save_template' ] );
		add_action( 'admin_post_wpseopilot_delete_link_template', [ $this, 'handle_delete_template' ] );
		add_action( 'admin_post_wpseopilot_save_link_settings', [ $this, 'handle_save_settings' ] );
		add_action( 'wp_ajax_wpseopilot_link_destination_search', [ $this, 'ajax_destination_search' ] );
		add_action( 'wp_ajax_wpseopilot_link_preview', [ $this, 'handle_preview' ] );
		add_filter( 'the_content', [ $this, 'filter_frontend_content' ], 20 );
		add_filter( 'widget_text', [ $this, 'filter_widget_content' ], 20, 3 );
		add_filter( 'widget_text_content', [ $this, 'filter_widget_content' ], 20, 2 );
		add_filter( 'widget_block_content', [ $this, 'filter_widget_content' ], 20, 2 );
	}

	/**
	 * Add submenu entry.
	 *
	 * @return void
	 */
	public function register_menu() {
		$this->page_hook = add_submenu_page(
			'wpseopilot',
			__( 'Internal Linking', 'wp-seo-pilot' ),
			__( 'Internal Linking', 'wp-seo-pilot' ),
			self::CAPABILITY,
			self::PAGE_SLUG,
			[ $this, 'render_page' ],
			10
		);
	}

	/**
	 * Enqueue scripts/styles for module page.
	 *
	 * @param string $hook Current admin hook.
	 *
	 * @return void
	 */
	public function enqueue_assets( $hook ) {
		if ( empty( $this->page_hook ) || $this->page_hook !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_style(
			'wpseopilot-plugin',
			WPSEOPILOT_URL . 'assets/css/plugin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_style(
			'wpseopilot-internal-linking',
			WPSEOPILOT_URL . 'assets/css/internal-linking.css',
			[ 'wpseopilot-admin' ],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_script(
			'wpseopilot-internal-linking',
			WPSEOPILOT_URL . 'assets/js/internal-linking.js',
			[ 'jquery', 'wp-util' ],
			WPSEOPILOT_VERSION,
			true
		);

		wp_localize_script(
			'wpseopilot-internal-linking',
			'WPSEOPilotLinks',
			[
				'ajax'   => admin_url( 'admin-ajax.php' ),
				'nonce'  => wp_create_nonce( 'wpseopilot_link_admin' ),
				'labels' => [
					'empty'           => __( 'No rules yet. Create your first internal link rule.', 'wp-seo-pilot' ),
					'keyword_hint'    => __( 'Use Enter to add each keyword. Exact phrase match; word boundaries recommended.', 'wp-seo-pilot' ),
					'preview_note'    => __( 'Preview simulates replacements without saving changes.', 'wp-seo-pilot' ),
					'previewSelect'   => __( 'Select a post or enter a URL to preview.', 'wp-seo-pilot' ),
					'previewRunning'  => __( 'Generating preview…', 'wp-seo-pilot' ),
					'previewEmpty'    => __( 'No replacements found.', 'wp-seo-pilot' ),
					'previewError'    => __( 'Unable to run preview.', 'wp-seo-pilot' ),
					'previewSuccess'  => __( 'Preview complete: %d replacement(s).', 'wp-seo-pilot' ),
					'save_success'    => __( 'Rule saved.', 'wp-seo-pilot' ),
					'category_prompt' => __( 'Provide a category name to continue.', 'wp-seo-pilot' ),
					'remove'          => __( 'Remove keyword', 'wp-seo-pilot' ),
				],
			]
		);
	}

	/**
	 * Filter main content.
	 *
	 * @param string $content Content string.
	 *
	 * @return string
	 */
	public function filter_frontend_content( $content ) {
		if ( ! $this->engine ) {
			return $content;
		}

		return $this->engine->filter(
			$content,
			[
				'context' => 'content',
				'post'    => get_post(),
			]
		);
	}

	/**
	 * Filter widget content.
	 *
	 * @param string $content Widget string.
	 * @param mixed  ...$rest  Ignored args.
	 *
	 * @return string
	 */
	public function filter_widget_content( $content, ...$rest ) {
		if ( ! $this->engine ) {
			return $content;
		}

		return $this->engine->filter(
			$content,
			[
				'context' => 'widget',
			]
		);
	}

	/**
	 * Render the Internal Linking admin screen.
	 *
	 * @return void
	 */
	public function render_page() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			return;
		}

		foreach ( $this->consume_notices() as $notice ) {
			add_settings_error( 'wpseopilot_links', $notice['code'], $notice['message'], $notice['type'] );
		}

		$active_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'rules';
		$filters    = $this->parse_rule_filters();

		$rules            = $this->repository->get_rules( $filters );
		$all_rules        = $this->repository->get_rules();
		$categories       = $this->repository->get_categories();
		$templates        = $this->repository->get_templates();
		$settings         = $this->repository->get_settings();
		$rule_defaults    = $this->repository->get_rule_defaults();
		$category_default = $this->repository->get_category_defaults();
		$template_default = $this->repository->get_template_defaults();

		$rule_to_edit = null;
		if ( isset( $_GET['rule'] ) ) {
			$rule_to_edit = $this->repository->get_rule( sanitize_key( wp_unslash( $_GET['rule'] ) ) );
		}

		if ( ! $rule_to_edit ) {
			$rule_defaults = $this->apply_rule_settings_defaults( $rule_defaults, $settings );
		}

		if ( 'edit' === $active_tab && ! $rule_to_edit ) {
			$active_tab = 'new';
		}

		$category_to_edit = null;
		if ( isset( $_GET['category'] ) ) {
			$category_to_edit = $this->repository->get_category( sanitize_key( wp_unslash( $_GET['category'] ) ) );
		}

		$template_to_edit = null;
		if ( isset( $_GET['template'] ) ) {
			$template_to_edit = $this->repository->get_template( sanitize_key( wp_unslash( $_GET['template'] ) ) );
		}

		$post_types = $this->get_supported_post_types();

		$category_usage = [];
		foreach ( $categories as $category ) {
			$count = 0;
			foreach ( $all_rules as $rule ) {
				if ( ( $rule['category'] ?? '' ) === $category['id'] ) {
					++$count;
				}
			}
			$category_usage[ $category['id'] ] = $count;
		}

		$context = [
			'rules'             => $rules,
			'all_rules'         => $all_rules,
			'categories'        => $categories,
			'category_usage'    => $category_usage,
			'category_to_edit'  => $category_to_edit,
			'utm_templates'     => $templates,
			'template_to_edit'  => $template_to_edit,
			'settings'          => $settings,
			'post_types'        => $post_types,
			'filters'           => $filters,
			'active_tab'        => $active_tab,
			'rule_to_edit'      => $rule_to_edit,
			'rule_defaults'     => $rule_defaults,
			'category_default'  => $category_default,
			'template_default'  => $template_default,
			'page_slug'         => self::PAGE_SLUG,
			'page_url'          => $this->get_admin_url(),
			'capability'        => self::CAPABILITY,
		];

		extract( $context ); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract -- Template convenience.
		include WPSEOPILOT_PATH . 'templates/internal-linking.php';
	}

	/**
	 * Handle Rule create/update.
	 *
	 * @return void
	 */
	public function handle_save_rule() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_save_link_rule' );

		$payload = isset( $_POST['rule'] ) ? (array) $_POST['rule'] : [];
		$payload = wp_unslash( $payload );

		if ( isset( $payload['category'] ) && '__new__' === $payload['category'] ) {
			$new_category = isset( $_POST['new_category'] ) ? (array) wp_unslash( $_POST['new_category'] ) : [];
			$category     = $this->repository->save_category( $new_category );

			if ( is_wp_error( $category ) ) {
				$this->flash_notice( $category->get_error_message(), 'error' );
				$this->redirect_back();
			}

			$payload['category'] = $category['id'];
		}

		$result = $this->repository->save_rule( $payload );

		if ( is_wp_error( $result ) ) {
			$this->flash_notice( $result->get_error_message(), 'error' );
			$this->redirect_back();
		}

		$this->flash_notice( __( 'Rule saved.', 'wp-seo-pilot' ) );
		$redirect = $this->get_admin_url(
			[
				'tab'  => 'edit',
				'rule' => $result['id'],
			]
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	/**
	 * Handle rule deletion.
	 *
	 * @return void
	 */
	public function handle_delete_rule() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_delete_link_rule' );

		$rule_id = isset( $_GET['rule'] ) ? sanitize_key( wp_unslash( $_GET['rule'] ) ) : '';
		if ( $rule_id ) {
			$this->repository->delete_rule( $rule_id );
			$this->flash_notice( __( 'Rule deleted.', 'wp-seo-pilot' ) );
		}

		wp_safe_redirect( $this->get_admin_url() );
		exit;
	}

	/**
	 * Handle bulk actions on rules.
	 *
	 * @return void
	 */
	public function handle_bulk_rules() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_bulk_link_rules' );

		$rule_ids = isset( $_POST['rule_ids'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['rule_ids'] ) ) : [];
		$action   = isset( $_POST['bulk_action'] ) ? sanitize_key( wp_unslash( $_POST['bulk_action'] ) ) : '';

		if ( 'change_category' === $action ) {
			$target_category = isset( $_POST['bulk_category'] ) ? sanitize_key( wp_unslash( $_POST['bulk_category'] ) ) : '';
			$this->bulk_assign_category( $rule_ids, $target_category );
			return;
		}

		$affected = $this->repository->bulk_update_rules( $rule_ids, $action );

		if ( $affected > 0 ) {
			$message = sprintf( _n( '%d rule updated.', '%d rules updated.', $affected, 'wp-seo-pilot' ), $affected );
			$this->flash_notice( $message );
		}

		wp_safe_redirect( $this->get_admin_url() );
		exit;
	}

	/**
	 * Duplicate rule handler.
	 *
	 * @return void
	 */
	public function handle_duplicate_rule() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_duplicate_link_rule' );

		$rule_id = isset( $_GET['rule'] ) ? sanitize_key( wp_unslash( $_GET['rule'] ) ) : '';
		if ( $rule_id ) {
			$result = $this->repository->duplicate_rule( $rule_id );
			if ( is_wp_error( $result ) ) {
				$this->flash_notice( $result->get_error_message(), 'error' );
			} else {
				$this->flash_notice( __( 'Rule duplicated. Remember to review before activating.', 'wp-seo-pilot' ) );
			}
		}

		wp_safe_redirect( $this->get_admin_url() );
		exit;
	}

	/**
	 * Toggle rule status.
	 *
	 * @return void
	 */
	public function handle_toggle_rule() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_toggle_link_rule' );

		$rule_id = isset( $_GET['rule'] ) ? sanitize_key( wp_unslash( $_GET['rule'] ) ) : '';
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : 'active';

		if ( $rule_id && in_array( $status, [ 'active', 'inactive' ], true ) ) {
			$rule = $this->repository->get_rule( $rule_id );
			if ( $rule ) {
				$rule['status'] = $status;
				$this->repository->save_rule( $rule );
				$message = 'active' === $status ? __( 'Rule activated.', 'wp-seo-pilot' ) : __( 'Rule deactivated.', 'wp-seo-pilot' );
				$this->flash_notice( $message );
			}
		}

		wp_safe_redirect( $this->get_admin_url() );
		exit;
	}

	/**
	 * Save category handler.
	 *
	 * @return void
	 */
	public function handle_save_category() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_save_link_category' );

		$data   = isset( $_POST['category'] ) ? (array) wp_unslash( $_POST['category'] ) : [];
		$result = $this->repository->save_category( $data );

		if ( is_wp_error( $result ) ) {
			$this->flash_notice( $result->get_error_message(), 'error' );
		} else {
			$this->flash_notice( __( 'Category saved.', 'wp-seo-pilot' ) );
		}

		wp_safe_redirect( $this->get_admin_url( [ 'tab' => 'categories' ] ) );
		exit;
	}

	/**
	 * Delete category handler.
	 *
	 * @return void
	 */
	public function handle_delete_category() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_delete_link_category' );

		$category_id = isset( $_GET['category'] ) ? sanitize_key( wp_unslash( $_GET['category'] ) ) : '';
		$reassign    = isset( $_GET['reassign'] ) ? sanitize_key( wp_unslash( $_GET['reassign'] ) ) : null;

		if ( $category_id ) {
			$result = $this->repository->delete_category( $category_id, $reassign );
			if ( is_wp_error( $result ) ) {
				$this->flash_notice( $result->get_error_message(), 'error' );
			} else {
				$this->flash_notice( __( 'Category deleted.', 'wp-seo-pilot' ) );
			}
		}

		wp_safe_redirect( $this->get_admin_url( [ 'tab' => 'categories' ] ) );
		exit;
	}

	/**
	 * Save UTM template handler.
	 *
	 * @return void
	 */
	public function handle_save_template() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_save_link_template' );

		$data   = isset( $_POST['template'] ) ? (array) wp_unslash( $_POST['template'] ) : [];
		$result = $this->repository->save_template( $data );

		if ( is_wp_error( $result ) ) {
			$this->flash_notice( $result->get_error_message(), 'error' );
		} else {
			$this->flash_notice( __( 'Template saved.', 'wp-seo-pilot' ) );
		}

		wp_safe_redirect( $this->get_admin_url( [ 'tab' => 'utms' ] ) );
		exit;
	}

	/**
	 * Delete template handler.
	 *
	 * @return void
	 */
	public function handle_delete_template() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_delete_link_template' );

		$template_id = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : '';
		if ( $template_id ) {
			$this->repository->delete_template( $template_id );
			$this->flash_notice( __( 'Template deleted.', 'wp-seo-pilot' ) );
		}

		wp_safe_redirect( $this->get_admin_url( [ 'tab' => 'utms' ] ) );
		exit;
	}

	/**
	 * Save module settings.
	 *
	 * @return void
	 */
	public function handle_save_settings() {
		$this->guard_capability();
		check_admin_referer( 'wpseopilot_save_link_settings' );

		$data = isset( $_POST['settings'] ) ? (array) wp_unslash( $_POST['settings'] ) : [];
		$this->repository->save_settings( $data );

		$this->flash_notice( __( 'Settings updated.', 'wp-seo-pilot' ) );
		wp_safe_redirect( $this->get_admin_url( [ 'tab' => 'settings' ] ) );
		exit;
	}

	/**
	 * AJAX preview handler.
	 *
	 * @return void
	 */
	public function handle_preview() {
		$this->guard_capability();
		check_ajax_referer( 'wpseopilot_link_admin', 'nonce' );

		$payload = isset( $_POST['rule'] ) ? (array) wp_unslash( $_POST['rule'] ) : [];
		$rule    = $this->repository->validate_rule( $payload );

		if ( is_wp_error( $rule ) ) {
			wp_send_json_error( $rule->get_error_message(), 400 );
		}

		$preview   = isset( $_POST['preview'] ) ? (array) wp_unslash( $_POST['preview'] ) : [];
		$post_id   = isset( $preview['post'] ) ? absint( $preview['post'] ) : 0;
		$preview_url = isset( $preview['url'] ) ? esc_url_raw( $preview['url'] ) : '';

		if ( ! $post_id && '' === $preview_url ) {
			wp_send_json_error( __( 'Select a post or enter a URL to preview.', 'wp-seo-pilot' ), 400 );
		}

		$post = $post_id ? get_post( $post_id ) : null;
		if ( $post_id && ! $post instanceof WP_Post ) {
			wp_send_json_error( __( 'Post not found.', 'wp-seo-pilot' ), 404 );
		}

		if ( $preview_url && ! $post ) {
			$preview_url = $this->normalize_preview_url( $preview_url );
		}

		$content = $post ? $this->render_post_content( $post ) : $this->fetch_preview_body( $preview_url );

		if ( is_wp_error( $content ) ) {
			wp_send_json_error( $content->get_error_message(), 400 );
		}

		$target_url = $post ? get_permalink( $post ) : $preview_url;

		$result = $this->engine->preview(
			$rule,
			[
				'content' => $content,
				'post'    => $post,
				'url'     => $target_url,
				'context' => 'content',
			]
		);

		wp_send_json_success( $result );
	}

	/**
	 * Ajax: destination search helper.
	 *
	 * @return void
	 */
	public function ajax_destination_search() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_send_json_error( __( 'Permission denied.', 'wp-seo-pilot' ), 403 );
		}

		check_ajax_referer( 'wpseopilot_link_admin', 'nonce' );

		$term       = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$post_types = $this->get_supported_post_types();
		$args       = [
			'post_type'      => array_keys( $post_types ),
			'posts_per_page' => 20,
			'post_status'    => [ 'publish', 'future', 'draft' ],
			's'              => $term,
		];

		$query = new \WP_Query( $args );
		$results = [];

		while ( $query->have_posts() ) {
			$query->the_post();
			$results[] = [
				'id'     => get_the_ID(),
				'title'  => get_the_title(),
				'type'   => get_post_type_object( get_post_type() )->labels->singular_name ?? get_post_type(),
				'url'    => get_permalink(),
			];
		}

		wp_reset_postdata();

		wp_send_json_success( $results );
	}

	/**
	 * Normalize preview URLs (allow relative paths).
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function normalize_preview_url( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		if ( 0 === strpos( $url, '/' ) || 0 === strpos( $url, '?' ) ) {
			return home_url( $url );
		}

		return $url;
	}

	/**
	 * Prepare rendered post content for preview.
	 *
	 * @param WP_Post $post Post object.
	 *
	 * @return string
	 */
	private function render_post_content( WP_Post $post ) {
		$content = $post->post_content;

		if ( function_exists( 'do_blocks' ) && has_blocks( $post ) ) {
			$content = do_blocks( $content );
		}

		if ( function_exists( 'do_shortcode' ) ) {
			$content = do_shortcode( $content );
		}

		return wpautop( $content );
	}

	/**
	 * Fetch remote body for preview URLs (same host only).
	 *
	 * @param string $url URL.
	 *
	 * @return string|\WP_Error
	 */
	private function fetch_preview_body( $url ) {
		if ( empty( $url ) ) {
			return new \WP_Error( 'wpseopilot_preview_url', __( 'Enter a preview URL.', 'wp-seo-pilot' ) );
		}

		$site_host   = wp_parse_url( home_url(), PHP_URL_HOST );
		$target_host = wp_parse_url( $url, PHP_URL_HOST );

		if ( $target_host && $site_host && strtolower( $target_host ) !== strtolower( $site_host ) ) {
			return new \WP_Error( 'wpseopilot_preview_host', __( 'Preview URLs must be on this site.', 'wp-seo-pilot' ) );
		}

		$response = wp_remote_get( $url, [ 'timeout' => 10 ] );
		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = wp_remote_retrieve_body( $response );
		if ( empty( $body ) ) {
			return new \WP_Error( 'wpseopilot_preview_body', __( 'No content returned for preview URL.', 'wp-seo-pilot' ) );
		}

		if ( strlen( $body ) > 200000 ) {
			$body = substr( $body, 0, 200000 );
		}

		return $body;
	}

	/**
	 * Helper: ensure current user can manage links.
	 *
	 * @return void
	 */
	private function guard_capability() {
		if ( ! current_user_can( self::CAPABILITY ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}
	}

	/**
	 * Guarantee supported roles inherit the manage capability.
	 *
	 * @return void
	 */
	public function ensure_role_capabilities() {
		$roles = apply_filters( 'wpseopilot_internal_link_roles', [ 'administrator' ] );
		$roles = array_unique( array_filter( (array) $roles ) );

		foreach ( $roles as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}

			if ( ! $role->has_cap( self::CAPABILITY ) ) {
				$role->add_cap( self::CAPABILITY );
			}
		}
	}

	/**
	 * Compute rule filters from query string.
	 *
	 * @return array
	 */
	private function parse_rule_filters() {
		$filters = [];

		if ( isset( $_GET['status'] ) ) {
			$status = sanitize_key( wp_unslash( $_GET['status'] ) );
			if ( in_array( $status, [ 'active', 'inactive' ], true ) ) {
				$filters['status'] = $status;
			}
		}

		if ( isset( $_GET['category'] ) ) {
			$filters['category'] = sanitize_key( wp_unslash( $_GET['category'] ) );
		}

		if ( isset( $_GET['post_type'] ) ) {
			$post_type_raw = wp_unslash( $_GET['post_type'] );
			if ( is_array( $post_type_raw ) ) {
				$sanitized = [];
				foreach ( $post_type_raw as $value ) {
					$value = sanitize_key( $value );
					if ( '__all__' === $value || post_type_exists( $value ) ) {
						$sanitized[] = $value;
					}
				}
				if ( ! empty( $sanitized ) ) {
					$filters['post_type'] = array_values( array_unique( $sanitized ) );
				}
			} else {
				$value = sanitize_key( $post_type_raw );
				if ( '__all__' === $value || post_type_exists( $value ) ) {
					$filters['post_type'] = [ $value ];
				}
			}
		}

		if ( isset( $_GET['s'] ) ) {
			$filters['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) );
		}

		return $filters;
	}

	/**
	 * Post types eligible for targeting rules.
	 *
	 * @return array<string,string>
	 */
	private function get_supported_post_types() {
		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'objects'
		);

		$allowed = [];
		foreach ( $post_types as $type => $object ) {
			if ( 'attachment' === $type ) {
				continue;
			}
			$allowed[ $type ] = $object->labels->name;
		}

		return $allowed;
	}

	/**
	 * Bulk category reassignment helper.
	 *
	 * @param array  $rule_ids Rule IDs.
	 * @param string $category Target category ID.
	 *
	 * @return void
	 */
	private function bulk_assign_category( array $rule_ids, $category ) {
		$category = sanitize_key( $category );
		$categories = $this->repository->get_categories();
		$category_ids = array_column( $categories, 'name', 'id' );

		if ( ! empty( $category ) && ! isset( $category_ids[ $category ] ) && '__none__' !== $category ) {
			$this->flash_notice( __( 'Category not found.', 'wp-seo-pilot' ), 'error' );
			wp_safe_redirect( $this->get_admin_url() );
			exit;
		}

		$updated = 0;
		foreach ( $rule_ids as $rule_id ) {
			$rule = $this->repository->get_rule( $rule_id );
			if ( ! $rule ) {
				continue;
			}
			$rule['category'] = '__none__' === $category ? '' : $category;
			$this->repository->save_rule( $rule );
			++$updated;
		}

		if ( $updated > 0 ) {
			$this->flash_notice( __( 'Categories updated.', 'wp-seo-pilot' ) );
		}

		wp_safe_redirect( $this->get_admin_url() );
		exit;
	}

	/**
	 * Consume queued notices from transient.
	 *
	 * @return array<int,array{code:string,message:string,type:string}>
	 */
	private function consume_notices() {
		$notices = get_transient( self::NOTICE_TRANSIENT );
		if ( ! is_array( $notices ) ) {
			return [];
		}

		delete_transient( self::NOTICE_TRANSIENT );

		return $notices;
	}

	/**
	 * Apply module-level defaults to a blank rule scaffold.
	 *
	 * @param array $rule     Rule defaults.
	 * @param array $settings Module settings.
	 *
	 * @return array
	 */
	private function apply_rule_settings_defaults( array $rule, array $settings ) {
		$default_limit = $settings['default_max_links_per_page'] ?? '';
		if ( '' === ( $rule['limits']['max_page'] ?? '' ) && '' !== $default_limit ) {
			$rule['limits']['max_page'] = $default_limit;
		}

		$heading_behavior = $settings['default_heading_behavior'] ?? 'none';
		$rule['placement']['headings'] = $heading_behavior;

		if ( 'selected' === $heading_behavior ) {
			$rule['placement']['heading_levels'] = $settings['default_heading_levels'] ?? [];
		}

		return $rule;
	}

	/**
	 * Queue notice for next page load.
	 *
	 * @param string $message Text.
	 * @param string $type    updated|error.
	 *
	 * @return void
	 */
	private function flash_notice( $message, $type = 'updated' ) {
		$notices   = get_transient( self::NOTICE_TRANSIENT );
		$notices   = is_array( $notices ) ? $notices : [];
		$notices[] = [
			'code'    => uniqid( 'wpseopilot_links_', true ),
			'message' => wp_kses_post( $message ),
			'type'    => ( 'error' === $type ) ? 'error' : 'updated',
		];

		set_transient( self::NOTICE_TRANSIENT, $notices, MINUTE_IN_SECONDS );
	}

	/**
	 * Redirect back to referring admin page.
	 *
	 * @return void
	 */
	private function redirect_back() {
		$referer = wp_get_referer();
		wp_safe_redirect( $referer ? $referer : $this->get_admin_url() );
		exit;
	}

	/**
	 * Compose admin URL for module.
	 *
	 * @param array $args Optional query args.
	 *
	 * @return string
	 */
	private function get_admin_url( array $args = [] ) {
		$base = admin_url( 'admin.php?page=' . self::PAGE_SLUG );
		if ( empty( $args ) ) {
			return $base;
		}

		return add_query_arg( $args, $base );
	}
}
