<?php
/**
 * Admin UX: meta boxes, Gutenberg sidebar, quick actions.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

use function WPSEOPilot\Helpers\generate_title_from_template;

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
		$metabox_enabled = apply_filters( 'wpseopilot_feature_toggle', true, 'metabox' );

		if ( $metabox_enabled ) {
			add_action( 'add_meta_boxes', [ $this, 'register_meta_box' ] );
			add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_filter( 'manage_post_posts_columns', [ $this, 'add_posts_column' ] );
		add_action( 'manage_post_posts_custom_column', [ $this, 'render_posts_column' ], 10, 2 );
		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-post', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-post', [ $this, 'handle_bulk_actions' ], 10, 3 );
		add_action( 'admin_post_wpseopilot_toggle_noindex', [ $this, 'handle_toggle_noindex' ] );
	}

	/**
	 * Register classic meta box.
	 *
	 * @return void
	 */
	public function register_meta_box() {
		add_meta_box(
			'wpseopilot-meta',
			__( 'WP SEO Pilot', 'wp-seo-pilot' ),
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

		wp_nonce_field( 'wpseopilot_meta', 'wpseopilot_meta_nonce' );

		$ai_enabled = ! empty( get_option( 'wpseopilot_openai_api_key', '' ) );

		include WPSEOPILOT_PATH . 'templates/meta-box.php';
	}

	/**
	 * Enqueue admin styles (post edit, settings).
	 *
	 * @param string $hook Hook suffix.
	 *
	 * @return void
	 */
	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'post.php' ) && false === strpos( $hook, 'post-new.php' ) && 'toplevel_page_wpseopilot' !== $hook ) {
			return;
		}

		wp_enqueue_style(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/css/admin.css',
			[],
			WPSEOPILOT_VERSION
		);

		wp_enqueue_script(
			'wpseopilot-admin',
			WPSEOPILOT_URL . 'assets/js/admin.js',
			[ 'jquery' ],
			WPSEOPILOT_VERSION,
			true
		);

		if ( '1' === get_option( 'wpseopilot_show_tour', '0' ) && ( false !== strpos( $hook, 'post.php' ) || false !== strpos( $hook, 'post-new.php' ) ) && apply_filters( 'wpseopilot_feature_toggle', true, 'metabox' ) ) {
			wp_enqueue_style( 'wp-pointer' );
			wp_enqueue_script( 'wp-pointer' );
			add_action( 'admin_print_footer_scripts', [ $this, 'print_pointer' ] );
			update_option( 'wpseopilot_show_tour', '0' );
		}

		$ai_enabled = ! empty( get_option( 'wpseopilot_openai_api_key', '' ) );
		wp_localize_script(
			'wpseopilot-admin',
			'WPSEOPilotAdmin',
			[
				'mediaTitle'  => __( 'Select image', 'wp-seo-pilot' ),
				'mediaButton' => __( 'Use image', 'wp-seo-pilot' ),
				'ai'          => [
					'enabled' => $ai_enabled,
					'ajax'    => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpseopilot_ai_generate' ),
					'strings' => [
						'disabled' => __( 'Add your OpenAI key under WP SEO Pilot → AI Assistant to enable suggestions.', 'wp-seo-pilot' ),
						'running'  => __( 'Asking AI for ideas…', 'wp-seo-pilot' ),
						'success'  => __( 'AI suggestion inserted.', 'wp-seo-pilot' ),
						'error'    => __( 'Unable to fetch suggestion.', 'wp-seo-pilot' ),
					],
				],
			]
		);
	}

	/**
	 * Gutenberg sidebar assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		wp_enqueue_script(
			'wpseopilot-editor',
			WPSEOPILOT_URL . 'assets/js/editor-sidebar.js',
			[ 'wp-plugins', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-compose' ],
			WPSEOPILOT_VERSION,
			true
		);

		wp_enqueue_style(
			'wpseopilot-editor',
			WPSEOPILOT_URL . 'assets/css/editor.css',
			[],
			WPSEOPILOT_VERSION
		);

		$post_type_templates    = get_option( 'wpseopilot_post_type_title_templates', [] );
		$post_type_descriptions = get_option( 'wpseopilot_post_type_meta_descriptions', [] );

		if ( ! is_array( $post_type_templates ) ) {
			$post_type_templates = [];
		}

		if ( ! is_array( $post_type_descriptions ) ) {
			$post_type_descriptions = [];
		}

		$ai_enabled = ! empty( get_option( 'wpseopilot_openai_api_key', '' ) );

		wp_localize_script(
			'wpseopilot-editor',
			'WPSEOPilotEditor',
			[
				'defaultTitle'       => get_option( 'wpseopilot_default_title_template', '%post_title% | %site_title%' ),
				'defaultDescription' => get_option( 'wpseopilot_default_meta_description', '' ),
				'defaultOg'          => get_option( 'wpseopilot_default_og_image', '' ),
				'postTypeTemplates'  => $post_type_templates,
				'postTypeDescriptions' => $post_type_descriptions,
				'ai'                 => [
					'enabled' => $ai_enabled,
					'ajax'    => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'wpseopilot_ai_generate' ),
					'strings' => [
						'disabled' => __( 'Add your OpenAI key under WP SEO Pilot → AI Assistant to enable suggestions.', 'wp-seo-pilot' ),
						'running'  => __( 'Asking AI for ideas…', 'wp-seo-pilot' ),
						'success'  => __( 'AI suggestion inserted.', 'wp-seo-pilot' ),
						'error'    => __( 'Unable to fetch suggestion.', 'wp-seo-pilot' ),
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
		$columns['wpseopilot'] = __( 'SEO', 'wp-seo-pilot' );
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
		if ( 'wpseopilot' !== $column ) {
			return;
		}

		$meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$title       = isset( $meta['title'] ) ? mb_strlen( $meta['title'] ) : 0;
		$description = isset( $meta['description'] ) ? mb_strlen( $meta['description'] ) : 0;
		$flags       = [];

		if ( ! empty( $meta['noindex'] ) ) {
			$flags[] = 'noindex';
		}
		if ( ! empty( $meta['nofollow'] ) ) {
			$flags[] = 'nofollow';
		}

		printf(
			'<span class="wpseopilot-chip">%s</span><span class="wpseopilot-chip">%s</span> %s',
			esc_html( sprintf( __( 'Title: %d', 'wp-seo-pilot' ), $title ) ),
			esc_html( sprintf( __( 'Desc: %d', 'wp-seo-pilot' ), $description ) ),
			$flags ? '<span class="wpseopilot-flag">' . esc_html( implode( ', ', $flags ) ) . '</span>' : ''
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

		$actions['wpseopilot_edit'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url( get_edit_post_link( $post->ID ) . '#wpseopilot' ),
			esc_html__( 'Edit SEO', 'wp-seo-pilot' )
		);

		$actions['wpseopilot_noindex'] = sprintf(
			'<a href="%s">%s</a>',
			esc_url(
				wp_nonce_url(
					add_query_arg(
						[
							'action'  => 'wpseopilot_toggle_noindex',
							'post_id' => $post->ID,
						],
						admin_url( 'admin-post.php' )
					),
					'wpseopilot_toggle_noindex'
				)
			),
			esc_html__( 'Toggle noindex', 'wp-seo-pilot' )
		);

		$actions['wpseopilot_og_preview'] = sprintf(
			'<a href="%1$s" target="_blank" rel="noopener noreferrer">%2$s</a>',
			esc_url(
				add_query_arg(
					[
						'wpseopilot_social_card' => 1,
						'title'                  => $post->post_title,
					],
					home_url( '/' )
				)
			),
			esc_html__( 'OG preview', 'wp-seo-pilot' )
		);

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
		$actions['wpseopilot_noindex'] = __( 'Mark as noindex', 'wp-seo-pilot' );
		$actions['wpseopilot_index']   = __( 'Mark as index', 'wp-seo-pilot' );
		$actions['wpseopilot_regen_canonical'] = __( 'Regenerate canonical', 'wp-seo-pilot' );
		$actions['wpseopilot_apply_template']  = __( 'Apply title template', 'wp-seo-pilot' );
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
		if ( ! in_array( $action, [ 'wpseopilot_noindex', 'wpseopilot_index', 'wpseopilot_regen_canonical', 'wpseopilot_apply_template' ], true ) ) {
			return $redirect;
		}

		foreach ( $post_ids as $post_id ) {
			$meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );

			if ( in_array( $action, [ 'wpseopilot_noindex', 'wpseopilot_index' ], true ) ) {
				$meta['noindex'] = 'wpseopilot_noindex' === $action ? '1' : '';
			}

			if ( 'wpseopilot_regen_canonical' === $action ) {
				$meta['canonical'] = '';
			}

			if ( 'wpseopilot_apply_template' === $action ) {
				$post              = get_post( $post_id );
				$meta['title']     = $post ? generate_title_from_template( $post ) : '';
				$meta['description'] = $post ? wp_trim_words( wp_strip_all_tags( $post->post_content ), 30 ) : '';
			}

			update_post_meta( $post_id, Post_Meta::META_KEY, $meta );
		}

		return add_query_arg( 'wpseopilot_bulk_updated', count( $post_ids ), $redirect );
	}

	/**
	 * Handle quick-toggle noindex action.
	 *
	 * @return void
	 */
	public function handle_toggle_noindex() {
		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_toggle_noindex' );

		$post_id = isset( $_GET['post_id'] ) ? absint( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_redirect( wp_get_referer() );
			exit;
		}

		$meta = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$meta['noindex'] = empty( $meta['noindex'] ) ? '1' : '';
		update_post_meta( $post_id, Post_Meta::META_KEY, $meta );

		wp_redirect( wp_get_referer() );
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
				$('#wpseopilot-meta .hndle').pointer({
					content: '<h3><?php echo esc_js( __( 'SEO fields live here', 'wp-seo-pilot' ) ); ?></h3><p><?php echo esc_js( __( 'Update title, description, and previews without scrolling.', 'wp-seo-pilot' ) ); ?></p>',
					position: { edge: 'left', align: 'center' },
					close: function () {}
				}).pointer('open');
			});
		</script>
		<?php
	}
}
