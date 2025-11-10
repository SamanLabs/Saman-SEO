<?php
/**
 * Admin UX: meta boxes, Gutenberg sidebar, quick actions.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

use function WPSEOPilot\Helpers\calculate_seo_score;
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
		add_action( 'init', [ $this, 'register_score_columns' ] );
		add_filter( 'post_row_actions', [ $this, 'post_row_actions' ], 10, 2 );
		add_filter( 'bulk_actions-edit-post', [ $this, 'bulk_actions' ] );
		add_filter( 'handle_bulk_actions-edit-post', [ $this, 'handle_bulk_actions' ], 10, 3 );
		add_action( 'admin_post_wpseopilot_toggle_noindex', [ $this, 'handle_toggle_noindex' ] );
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

		$post_types = apply_filters( 'wpseopilot_score_post_types', array_values( $post_types ) );

		foreach ( $post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ $this, 'add_posts_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ $this, 'render_posts_column' ], 10, 2 );
		}
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
		$seo_score  = calculate_seo_score( $post );

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
		$should_enqueue = ( 'toplevel_page_wpseopilot' === $hook );

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

		$meta  = (array) get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$flags = [];

		if ( ! empty( $meta['noindex'] ) ) {
			$flags[] = 'noindex';
		}
		if ( ! empty( $meta['nofollow'] ) ) {
			$flags[] = 'nofollow';
		}

		$score       = calculate_seo_score( $post_id );
		$badge_class = 'wpseopilot-score-badge--' . sanitize_html_class( $score['level'] );

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

		$issue_summary = $issue_labels ? implode( ' • ', array_slice( $issue_labels, 0, 2 ) ) : __( 'All baseline checks look good.', 'wp-seo-pilot' );
		if ( count( $issue_labels ) > 2 ) {
			$issue_summary .= sprintf(
				/* translators: %d is the number of remaining issues. */
				__( ' +%d more', 'wp-seo-pilot' ),
				count( $issue_labels ) - 2
			);
		}

		$details = implode(
			' | ',
			array_map(
				static function ( $metric ) {
					return $metric['label'] . ': ' . $metric['status'];
				},
				$score['metrics']
			)
		);

		?>
		<div class="wpseopilot-score-cell">
			<span class="wpseopilot-score-badge <?php echo esc_attr( $badge_class ); ?>" title="<?php echo esc_attr( $details ); ?>">
				<strong><?php echo esc_html( $score['score'] ); ?></strong>
				<span>/100</span>
			</span>
			<span class="wpseopilot-score-label"><?php echo esc_html( $score['label'] ); ?></span>
		</div>
		<p class="wpseopilot-score-issues">
			<?php echo esc_html( $issue_summary ); ?>
			<?php if ( $flags ) : ?>
				<span class="wpseopilot-flag"><?php echo esc_html( implode( ', ', $flags ) ); ?></span>
			<?php endif; ?>
		</p>
		<?php
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
