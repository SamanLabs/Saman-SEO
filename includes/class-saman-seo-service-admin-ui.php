<?php
/**
 * Admin UX: meta boxes, Gutenberg sidebar, quick actions.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

use Saman\SEO\Integration\AI_Pilot;
use function Saman\SEO\Helpers\calculate_seo_score;
use function Saman\SEO\Helpers\generate_title_from_template;
use function Saman\SEO\Helpers\replace_template_variables;

/**
 * Admin UI controller.
 */
class Admin_UI {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		$metabox_enabled = apply_filters( 'SAMAN_SEO_feature_toggle', true, 'metabox' );

		if ( $metabox_enabled ) {
			add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'init', [ $this, 'register_score_columns' ] );
		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-post', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-post', [ $this, 'handle_bulk_actions' ], 10, 3 );
		add_action( 'admin_post_SAMAN_SEO_toggle_noindex', [ $this, 'handle_toggle_noindex' ] );
		add_action( 'wp_ajax_SAMAN_SEO_render_preview', [ $this, 'ajax_render_preview' ] );

		// Add admin notice for Saman Labs AI installation
		add_action( 'admin_notices', [ $this, 'ai_installation_notice' ] );
		add_action( 'wp_ajax_SAMAN_SEO_dismiss_ai_notice', [ $this, 'dismiss_ai_notice' ] );
	}

	/**
	 * AJAX handler to dismiss AI installation notice.
	 *
	 * @return void
	 */
	public function dismiss_ai_notice() {
		check_ajax_referer( 'dismiss_ai_notice', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		update_user_meta( get_current_user_id(), 'SAMAN_SEO_ai_notice_dismissed', true );
		wp_send_json_success();
	}

	/**
	 * Display admin notice if Saman Labs AI is not installed/configured.
	 *
	 * Shows on SEO-related pages to prompt users to install Saman Labs AI
	 * for AI-powered features.
	 *
	 * @return void
	 */
	public function ai_installation_notice() {
		// Only show to users who can manage options
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Only show on relevant pages
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Show on plugin pages and post editor
		$relevant_pages = [
			'saman-seo',
			'post',
			'page',
		];

		$is_relevant = false;
		foreach ( $relevant_pages as $page ) {
			if ( false !== strpos( $screen->id, $page ) ) {
				$is_relevant = true;
				break;
			}
		}

		if ( ! $is_relevant ) {
			return;
		}

		// Check if notice was dismissed
		$dismissed = get_user_meta( get_current_user_id(), 'SAMAN_SEO_ai_notice_dismissed', true );
		if ( $dismissed ) {
			return;
		}

		// Check Saman Labs AI status
		if ( AI_Pilot::is_ready() ) {
			return; // AI is working, no notice needed
		}

		$notice_class = 'notice notice-info is-dismissible saman-seo-ai-notice';
		$notice_id    = 'saman-seo-ai-notice';

		if ( ! AI_Pilot::is_installed() ) {
			$message = sprintf(
				/* translators: %s: link to install Saman Labs AI */
				__( '<strong>Saman SEO:</strong> Install %s to enable AI-powered SEO features like automatic title and description generation.', 'saman-seo' ),
				'<a href="' . esc_url( admin_url( 'plugin-install.php?s=saman-ai&tab=search&type=term' ) ) . '">Saman Labs AI</a>'
			);
		} elseif ( ! AI_Pilot::is_active() ) {
			$message = sprintf(
				/* translators: %s: link to plugins page */
				__( '<strong>Saman SEO:</strong> Saman Labs AI is installed but not active. %s to enable AI features.', 'saman-seo' ),
				'<a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . __( 'Activate it', 'saman-seo' ) . '</a>'
			);
		} else {
			$message = sprintf(
				/* translators: %s: link to settings */
				__( '<strong>Saman SEO:</strong> Saman Labs AI needs configuration. %s to enable AI features.', 'saman-seo' ),
				'<a href="' . esc_url( admin_url( 'admin.php?page=Saman-ai' ) ) . '">' . __( 'Configure it', 'saman-seo' ) . '</a>'
			);
		}

		printf(
			'<div id="%s" class="%s" data-nonce="%s"><p>%s</p></div>',
			esc_attr( $notice_id ),
			esc_attr( $notice_class ),
			esc_attr( wp_create_nonce( 'dismiss_ai_notice' ) ),
			wp_kses_post( $message )
		);

		// Add inline script to handle dismiss
		?>
		<script>
		jQuery(function($) {
			$(document).on('click', '#saman-seo-ai-notice .notice-dismiss', function() {
				$.post(ajaxurl, {
					action: 'SAMAN_SEO_dismiss_ai_notice',
					nonce: $('#saman-seo-ai-notice').data('nonce')
				});
			});
		});
		</script>
		<?php
	}

	/**
	 * Register SEO score column hooks for post types.
	 *
	 * @return void
	 */
	public function register_score_columns() {
		if ( ! is_admin() ) {
			return;
		}

		$post_types = get_post_types(
			[
				'public'  => true,
				'show_ui' => true,
			],
			'names'
		);

		if ( isset( $post_types['attachment'] ) ) {
			unset( $post_types['attachment'] );
		}

		$post_types = apply_filters( 'SAMAN_SEO_score_post_types', array_values( $post_types ) );

		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_posts_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_posts_column' ], 10, 2 );
		}
	}

	/**
	 * Register classic meta box (only for classic editor).
	 *
	 * In the block editor, we use the React sidebar instead.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		$screen = get_current_screen();

		// Skip registration if we're in the block editor
		if ( $screen && method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
			return;
		}

		add_meta_box(
			'saman-seo-meta',
			__( 'Saman SEO', 'saman-seo' ),
			[ $this, 'render_meta_box' ],
			[ 'post', 'page' ],
			'side',
			'high'
		);
	}

	/**
	 * Render meta box fields + previews.
	 *
	 * @param \WP_Post $post Current post.
	 *
	 * @return void
	 */
	public function render_meta_box( $post ) {
		$meta = get_post_meta( $post->ID, Post_Meta::META_KEY, true );
		$meta = wp_parse_args(
			(array) $meta,
			[
				'title'       => '',
				'description' => '',
				'canonical'   => '',
				'noindex'     => '',
				'nofollow'    => '',
				'og_image'    => '',
			]
		);

		wp_nonce_field( 'SAMAN_SEO_meta', 'SAMAN_SEO_meta_nonce' );

		$ai_enabled = AI_Pilot::is_ready();
		$seo_score  = calculate_seo_score( $post );

		include SAMAN_SEO_PATH . 'templates/meta-box.php';
	}

	/**
	 * Enqueue admin styles (post edit, settings).
	 *
	 * @param string $hook Hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		// Exclude sitemap settings page from rich input transformation
		if ( 'wp-seo-pilot_page_saman-seo-sitemap' === $hook ) {
			return;
		}

		$should_enqueue = ( false !== strpos( $hook, 'saman-seo' ) );

		if ( ! $should_enqueue ) {
			foreach ( [ 'post.php', 'post-new.php', 'edit.php' ] as $needle ) {
				if ( false !== strpos( $hook, $needle ) ) {
					$should_enqueue = true;
					break;
				}
			}
		}

		if ( ! $should_enqueue ) {
			return;
		}

		wp_enqueue_style(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/css/admin.css',
			[],
			SAMAN_SEO_VERSION
		);

		wp_enqueue_script(
			'saman-seo-admin',
			SAMAN_SEO_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			SAMAN_SEO_VERSION,
			true
		);

		wp_enqueue_script(
			'saman-seo-seo-tags',
			SAMAN_SEO_URL . 'assets/js/seo-tags.js',
			[ 'jquery', 'saman-seo-admin' ],
			SAMAN_SEO_VERSION,
			true
		);

		if ( '1' === get_option( 'SAMAN_SEO_show_tour', '0' ) && ( false !== strpos( $hook, 'post.php' ) || false !== strpos( $hook, 'post-new.php' ) ) && apply_filters( 'SAMAN_SEO_feature_toggle', true, 'metabox' ) ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			add_action( 'admin_print_footer_scripts', [ $this, 'print_pointer' ] );
			update_option( 'SAMAN_SEO_show_tour', '0' );
		}

		$ai_enabled = AI_Pilot::is_ready();
		$settings_svc = new Settings();
		wp_localize_script(
			'saman-seo-admin',
			'SamanSEOAdmin',
			[
				'mediaTitle'  => __( 'Select image', 'saman-seo' ),
				'mediaButton' => __( 'Use image', 'saman-seo' ),
				'variables'   => $settings_svc->get_context_variables(),
				'ai'          => [
					'enabled' => $ai_enabled,
					'ajax'    => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'SAMAN_SEO_ai_generate' ),
					'strings' => [
						'disabled' => __( 'Install Saman Labs AI to enable AI-powered suggestions.', 'saman-seo' ),
						'running'  => __( 'Asking AI for ideasÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦', 'saman-seo' ),
						'success'  => __( 'AI suggestion inserted.', 'saman-seo' ),
						'error'    => __( 'Unable to fetch suggestion.', 'saman-seo' ),
					],
				],
			]
		);

		// Enqueue React admin list badge for edit.php (posts list).
		if ( 'edit.php' === $hook || false !== strpos( $hook, 'edit.php' ) ) {
			$this->enqueue_admin_list_assets();
		}
	}

	/**
	 * Admin list React badge assets.
	 *
	 * @return void
	 */
	public function enqueue_admin_list_assets() {
		$build_dir = SAMAN_SEO_PATH . 'build-admin-list/';
		$build_url = SAMAN_SEO_URL . 'build-admin-list/';

		$asset_file = $build_dir . 'index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [
				'dependencies' => [ 'wp-element', 'react', 'react-dom' ],
				'version'      => SAMAN_SEO_VERSION,
			];

		wp_enqueue_script(
			'saman-seo-admin-list',
			$build_url . 'index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'saman-seo-admin-list',
			$build_url . 'index.css',
			[],
			$asset['version']
		);
	}

	/**
	 * Gutenberg sidebar assets (V2 React).
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// Load V2 React editor sidebar
		$build_dir = SAMAN_SEO_PATH . 'build-editor/';
		$build_url = SAMAN_SEO_URL . 'build-editor/';

		$asset_file = $build_dir . 'index.asset.php';
		$asset      = file_exists( $asset_file )
			? require $asset_file
			: [
				'dependencies' => [ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch' ],
				'version'      => SAMAN_SEO_VERSION,
			];

		wp_enqueue_script(
			'saman-seo-editor-v2',
			$build_url . 'index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);

		wp_enqueue_style(
			'saman-seo-editor-v2',
			$build_url . 'index.css',
			[],
			$asset['version']
		);

		// Get variables for the editor
		$settings_svc = new Settings();
		$variables    = $settings_svc->get_context_variables();

		// Get AI status from integration
		$ai_status   = AI_Pilot::get_status();
		$ai_enabled  = AI_Pilot::ai_enabled();
		$ai_provider = AI_Pilot::get_provider();

		// Localize data for the React editor
		wp_localize_script(
			'saman-seo-editor-v2',
			'saman-seoEditor',
			[
				'variables'  => $variables,
				'aiEnabled'  => $ai_enabled,
				'aiProvider' => $ai_provider, // 'Saman-ai', 'native', or 'none'
				'aiPilot'    => [
					'installed'   => $ai_status['installed'],
					'active'      => $ai_status['active'],
					'ready'       => $ai_status['ready'],
					'version'     => $ai_status['version'] ?? null,
					'settingsUrl' => admin_url( 'admin.php?page=Saman-ai' ),
				],
				'siteTitle'  => get_bloginfo( 'name' ),
				'tagline'    => get_bloginfo( 'description' ),
				'separator'  => get_option( 'SAMAN_SEO_title_separator', '|' ),
			]
		);

		$post_type_templates    = get_option( 'SAMAN_SEO_post_type_title_templates', [] );
		$post_type_descriptions = get_option( 'SAMAN_SEO_post_type_meta_descriptions', [] );

		if ( ! is_array( $post_type_templates ) ) {
			$post_type_templates = [];
		}

		if ( ! is_array( $post_type_descriptions ) ) {
			$post_type_descriptions = [];
		}

		// AI enabled status is already set above via AI_Pilot integration

		// Check for pending slug change redirect for this user.
		$user_id     = get_current_user_id();
		$slug_change = get_transient( 'SAMAN_SEO_slug_changed_' . $user_id );

		if ( $slug_change ) {
			// Clear it so it doesn't persist.
			delete_transient( 'SAMAN_SEO_slug_changed_' . $user_id );
			$slug_change['nonce'] = wp_create_nonce( 'SAMAN_SEO_create_redirect' );
		}

		wp_localize_script(
			'saman-seo-editor',
			'SamanSEOEditor',
			[
				'defaultTitle'       => get_option( 'SAMAN_SEO_default_title_template', '{{post_title}} | {{site_title}}' ),
				'defaultDescription' => get_option( 'SAMAN_SEO_default_meta_description', '' ),
				'defaultOg'          => get_option( 'SAMAN_SEO_default_og_image', '' ),
				'postTypeTemplates'  => $post_type_templates,
				'postTypeDescriptions' => $post_type_descriptions,
				'postTypeDescriptions' => $post_type_descriptions,
				'slugChange'         => $slug_change,
				'redirectNonce'      => wp_create_nonce( 'SAMAN_SEO_create_redirect' ),
				'ai'                 => [
					'enabled' => $ai_enabled,
					'ajax'    => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'SAMAN_SEO_ai_generate' ),
					'strings' => [
						'disabled' => __( 'Install Saman Labs AI to enable AI-powered suggestions.', 'saman-seo' ),
						'running'  => __( 'Asking AI for ideasÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¦', 'saman-seo' ),
						'success'  => __( 'AI suggestion inserted.', 'saman-seo' ),
						'error'    => __( 'Unable to fetch suggestion.', 'saman-seo' ),
					],
				],
			]
		);
	}

	/**
	 * Add custom column to posts list.
	 *
	 * @param array $columns Columns.
	 *
	 * @return array
	 */
	public function add_posts_column( $columns ) {
		$columns['saman-seo'] = __( 'SEO', 'saman-seo' );
		return $columns;
	}

	/**
	 * Render column content.
	 *
	 * @param string $column Column key.
	 * @param int    $post_id Post ID.
	 *
	 * @return void
	 */
	public function render_posts_column( $column, $post_id ) {
		if ( 'saman-seo' !== $column ) {
			return;
		}

		$meta  = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$flags = [];

		if ( ! empty( $meta['noindex'] ) ) {
			$flags[] = 'noindex';
		}
		if ( ! empty( $meta['nofollow'] ) ) {
			$flags[] = 'nofollow';
		}

		$score = calculate_seo_score( $post_id );

		$issues = array_values(
			array_filter(
				$score['metrics'],
				static function ( $metric ) {
					return empty( $metric['is_pass'] );
				}
			)
		);

		$issue_labels = array_map(
			static function ( $metric ) {
				return $metric['issue_label'];
			},
			$issues
		);

		// Output placeholder for React hydration.
		printf(
			'<div class="saman-seo-badge-placeholder" data-post-id="%d" data-score="%d" data-level="%s" data-label="%s" data-issues="%s" data-flags="%s"></div>',
			absint( $post_id ),
			absint( $score['score'] ),
			esc_attr( $score['level'] ),
			esc_attr( $score['label'] ),
			esc_attr( wp_json_encode( $issue_labels ) ),
			esc_attr( wp_json_encode( $flags ) )
		);
	}

	/**
	 * Inject quick actions in post rows.
	 *
	 * @param array   $actions Actions.
	 * @param \WP_Post $post   Post.
	 *
	 * @return array
	 */
	public function post_row_actions( $actions, $post ) {
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return $actions;
		}

		$actions['SAMAN_SEO_edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $post->ID ) . '#saman-seo' ),
			esc_html__( 'Edit SEO', 'saman-seo' )
		);

		$actions['SAMAN_SEO_noindex'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						[
							'action'  => 'SAMAN_SEO_toggle_noindex',
							'post_id' => $post->ID,
						],
						admin_url( 'admin-post.php' )
					),
					'SAMAN_SEO_toggle_noindex'
				)
			),
			esc_html__( 'Toggle noindex', 'saman-seo' )
		);
		
		if ( '1' === get_option( 'SAMAN_SEO_enable_og_preview', '1' ) ) {
			$actions['SAMAN_SEO_og_preview'] = sprintf(
				'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
				esc_url(
					add_query_arg(
						[
							'SAMAN_SEO_social_card' => 1,
							'title'                  => $post->post_title,
						],
						home_url( '/' )
					)
				),
				esc_html__( 'OG preview', 'saman-seo' )
			);
		}

		return $actions;
	}

	/**
	 * Register bulk actions.
	 *
	 * @param array $actions Actions.
	 *
	 * @return array
	 */
	public function bulk_actions( $actions ) {
		$actions['SAMAN_SEO_noindex'] = __( 'Mark as noindex', 'saman-seo' );
		$actions['SAMAN_SEO_index']   = __( 'Mark as index', 'saman-seo' );
		$actions['SAMAN_SEO_regen_canonical'] = __( 'Regenerate canonical', 'saman-seo' );
		$actions['SAMAN_SEO_apply_template']  = __( 'Apply title template', 'saman-seo' );
		return $actions;
	}

	/**
	 * Handle bulk index toggles.
	 *
	 * @param string $redirect Redirect.
	 * @param string $action   Action.
	 * @param array  $post_ids Posts.
	 *
	 * @return string
	 */
	public function handle_bulk_actions( $redirect, $action, $post_ids ) {
		if ( ! in_array( $action, [ 'SAMAN_SEO_noindex', 'SAMAN_SEO_index', 'SAMAN_SEO_regen_canonical', 'SAMAN_SEO_apply_template' ], true ) ) {
			return $redirect;
		}

		foreach ( $post_ids as $post_id ) {
			$meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

			if ( in_array( $action, [ 'SAMAN_SEO_noindex', 'SAMAN_SEO_index' ], true ) ) {
				$meta['noindex'] = 'SAMAN_SEO_noindex' === $action ? '1' : '';
			}

			if ( 'SAMAN_SEO_regen_canonical' === $action ) {
				$meta['canonical'] = '';
			}

			if ( 'SAMAN_SEO_apply_template' === $action ) {
				$post              = get_post( $post_id );
				$meta['title']     = $post ? generate_title_from_template( $post ) : '';
				$meta['description'] = $post ? wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) : '';
			}

			update_post_meta( $post_id, Post_Meta::META_KEY, $meta );
		}

		return add_query_arg( 'SAMAN_SEO_bulk_updated', count( $post_ids ), $redirect );
	}

	/**
	 * Handle quick-toggle noindex action.
	 *
	 * @return void
	 */
	public function handle_toggle_noindex() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_toggle_noindex' );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			$redirect_url = wp_get_referer();
			$redirect_url = $redirect_url ? $redirect_url : admin_url();
			wp_safe_redirect( $redirect_url );
			exit;
		}

		$meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$meta['noindex'] = empty( $meta['noindex'] ) ? '1' : '';
		update_post_meta( $post_id, Post_Meta::META_KEY, $meta );

		$redirect_url = wp_get_referer();
		$redirect_url = $redirect_url ? $redirect_url : admin_url();
		wp_safe_redirect( $redirect_url );
		exit;
	}

	/**
	 * Output guided tour pointer.
	 *
	 * @return void
	 */
	public function print_pointer() {
		?>
		<script>
			jQuery(function ($) {
				$('#saman-seo-meta .hndle').pointer({
					content: '<h3><?php echo esc_js( __( 'SEO fields live here', 'saman-seo' ) ); ?></h3><p><?php echo esc_js( __( 'Update title, description, and previews without scrolling.', 'saman-seo' ) ); ?></p>',
					position: { edge: 'left', align: 'center' },
					close: function () {}
				}).pointer('open');
			});
		</script>
		<?php
	}

	/**
	 * AJAX handler for live template preview.
	 */
	public function ajax_render_preview() {
		check_ajax_referer( 'SAMAN_SEO_ai_generate', 'nonce' );
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$template = isset( $_POST['template'] ) ? wp_unslash( $_POST['template'] ) : '';
		$context  = isset( $_POST['context'] ) ? sanitize_text_field( $_POST['context'] ) : 'global';
		$object_id = isset( $_POST['object_id'] ) ? absint( $_POST['object_id'] ) : 0;

		$mock_object = null;

		// If explicit object ID provided, try to fetch it
		if ( $object_id > 0 ) {
			// Try post first
			$post = get_post( $object_id );
			if ( $post ) {
				$mock_object = $post;
			} else {
				// Try term
				$term = get_term( $object_id );
				if ( $term && ! is_wp_error( $term ) ) {
					$mock_object = $term;
				}
			}
		}

		// Fallback to auto-detection if no specific object found
		if ( ! $mock_object ) {
			if ( strpos( $context, 'post_type:' ) === 0 ) {
				$pt = str_replace( 'post_type:', '', $context );
				$posts = get_posts( [ 'post_type' => $pt, 'posts_per_page' => 1 ] );
				if ( $posts ) {
					$mock_object = $posts[0];
				}
			} elseif ( strpos( $context, 'taxonomy:' ) === 0 ) {
				$tax = str_replace( 'taxonomy:', '', $context );
				$terms = get_terms( [ 'taxonomy' => $tax, 'number' => 1, 'hide_empty' => false ] );
				$mock_object = null;
				if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
					$mock_object = $terms[0];
				}

				// Always use custom taxonomy renderer for admin preview
				// (replace_template_variables won't detect taxonomy context in admin)
				$rendered = $this->render_taxonomy_preview( $template, $tax, $mock_object );
				wp_send_json_success( [ 'preview' => $rendered ] );
				return;
			} elseif ( strpos( $context, 'archive:' ) === 0 ) {
				// Handle archive contexts (404, search, author, date)
				// For archives, we need to manually render the template with mock variables
				$archive_type = str_replace( 'archive:', '', $context );
				$rendered = $this->render_archive_preview( $template, $archive_type );
				wp_send_json_success( [ 'preview' => $rendered ] );
				return;
			}
		}

		$rendered = replace_template_variables( $template, $mock_object );

		wp_send_json_success( [ 'preview' => $rendered ] );
	}

	/**
	 * Render archive preview with mock variables.
	 *
	 * @param string $template Template string.
	 * @param string $archive_type Archive type (404, search, author, date).
	 *
	 * @return string
	 */
	private function render_archive_preview( $template, $archive_type ) {
		if ( ! class_exists( 'Twiglet\Twiglet' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'src/Twiglet.php';
		}

		// Build mock variables based on archive type
		$vars = [
			'site_title'    => get_bloginfo( 'name' ),
			'sitename'      => get_bloginfo( 'name' ),
			'tagline'       => get_bloginfo( 'description' ),
			'separator'     => get_option( 'SAMAN_SEO_title_separator', '-' ),
			'current_year'  => date_i18n( 'Y' ),
			'current_month' => date_i18n( 'F' ),
			'current_day'   => date_i18n( 'j' ),
		];

		// Add archive-type specific variables
		switch ( $archive_type ) {
			case '404':
				$vars['request_url'] = home_url( '/example-page' );
				break;
			case 'search':
				$vars['search_term'] = 'example search query';
				break;
			case 'author':
				// Get a real author if possible
				$users = get_users( [ 'number' => 1, 'capability' => 'edit_posts' ] );
				if ( ! empty( $users ) ) {
					$vars['author']      = $users[0]->display_name;
					$vars['author_name'] = $users[0]->display_name;
					$vars['author_bio']  = get_user_meta( $users[0]->ID, 'description', true );
				} else {
					$vars['author']      = 'Example Author';
					$vars['author_name'] = 'Example Author';
					$vars['author_bio']  = 'Author biography';
				}
				break;
			case 'date':
				$vars['date']          = date_i18n( 'F Y' );
				$vars['archive_date']  = date_i18n( 'F Y' );
				$vars['archive_title'] = date_i18n( 'F Y' );
				break;
		}

		$twiglet = new \Twiglet\Twiglet();
		return $twiglet->render_string( $template, $vars );
	}

	/**
	 * Render taxonomy preview with mock variables.
	 *
	 * @param string        $template Template string.
	 * @param string        $taxonomy Taxonomy name.
	 * @param \WP_Term|null $term Existing term or null.
	 *
	 * @return string
	 */
	private function render_taxonomy_preview( $template, $taxonomy, $term = null ) {
		if ( ! class_exists( 'Twiglet\Twiglet' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'src/Twiglet.php';
		}

		// Build mock variables
		$vars = [
			'site_title'    => get_bloginfo( 'name' ),
			'sitename'      => get_bloginfo( 'name' ),
			'tagline'       => get_bloginfo( 'description' ),
			'separator'     => get_option( 'SAMAN_SEO_title_separator', '-' ),
			'current_year'  => date_i18n( 'Y' ),
			'current_month' => date_i18n( 'F' ),
			'current_day'   => date_i18n( 'j' ),
		];

		// Use real term data if available, otherwise use mock data
		if ( $term instanceof \WP_Term ) {
			$vars['term']       = $term->name;
			$vars['term_title'] = $term->name;
			// Provide mock description if term has no description
			$vars['term_description'] = ! empty( $term->description )
				? wp_strip_all_tags( $term->description )
				: 'Browse articles in the ' . $term->name . ' category.';
		} else {
			// No term exists, use generic mock data
			$tax_object = get_taxonomy( $taxonomy );
			$term_name = $tax_object && isset( $tax_object->labels->singular_name )
				? $tax_object->labels->singular_name
				: 'Example Term';

			$vars['term']             = $term_name;
			$vars['term_title']       = $term_name;
			$vars['term_description'] = 'Browse articles in the ' . $term_name . ' category.';
		}

		$twiglet = new \Twiglet\Twiglet();
		return $twiglet->render_string( $template, $vars );
	}
}
