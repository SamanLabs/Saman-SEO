<?php
/**
 * Import/export integrations with other SEO plugins.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Import/export controller.
 */
class Importers {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_post_wpseopilot_import', [ $this, 'handle_import' ] );
		add_action( 'admin_post_wpseopilot_export', [ $this, 'handle_export' ] );
		add_action( 'admin_notices', [ $this, 'import_notice' ] );
	}

	/**
	 * Handle import form submission.
	 *
	 * @return void
	 */
	public function handle_import() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_import' );

		$vendor = isset( $_POST['vendor'] ) ? sanitize_key( wp_unslash( $_POST['vendor'] ) ) : '';
		$count  = 0;

		$dry_run = ! empty( $_POST['dry_run'] );

		switch ( $vendor ) {
			case 'yoast':
				$count = $this->import_yoast( $dry_run );
				break;
			case 'rankmath':
				$count = $this->import_rankmath( $dry_run );
				break;
			case 'aioseo':
				$count = $this->import_aioseo( $dry_run );
				break;
		}

		wp_redirect(
			add_query_arg(
				[
				 'wpseopilot_imported' => $count,
				 'wpseopilot_vendor'   => $vendor,
				 'wpseopilot_dry_run'  => $dry_run ? '1' : '0',
				],
				admin_url( 'admin.php?page=wpseopilot' )
			)
		);
		exit;
	}

	/**
	 * Display admin notice after import.
	 *
	 * @return void
	 */
	public function import_notice() {
		if ( ! isset( $_GET['wpseopilot_imported'], $_GET['wpseopilot_vendor'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$count  = absint( $_GET['wpseopilot_imported'] );
		$vendor = sanitize_text_field( wp_unslash( $_GET['wpseopilot_vendor'] ) );
		$dry    = ! empty( $_GET['wpseopilot_dry_run'] );

		printf(
			'<div class="notice notice-success"><p>%s</p></div>',
			esc_html(
				sprintf(
					$dry ? __( 'Dry run complete: %1$d records detected from %2$s.', 'wp-seo-pilot' ) : __( 'Imported %1$d records from %2$s.', 'wp-seo-pilot' ),
					$count,
					ucfirst( $vendor )
				)
			)
		);
	}

	/**
	 * Handle export request.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'wp-seo-pilot' ) );
		}

		check_admin_referer( 'wpseopilot_export' );

		$options = [
			'wpseopilot_default_title_template',
			'wpseopilot_post_type_title_templates',
			'wpseopilot_post_type_meta_descriptions',
			'wpseopilot_post_type_keywords',
			'wpseopilot_post_type_settings',
			'wpseopilot_taxonomy_settings',
			'wpseopilot_archive_settings',
			'wpseopilot_ai_model',
			'wpseopilot_ai_prompt_system',
			'wpseopilot_ai_prompt_title',
			'wpseopilot_ai_prompt_description',
			'wpseopilot_openai_api_key',
			'wpseopilot_homepage_title',
			'wpseopilot_homepage_description',
			'wpseopilot_homepage_description_prompt',
			'wpseopilot_homepage_knowledge_type',
			'wpseopilot_homepage_organization_name',
			'wpseopilot_homepage_organization_logo',
			'wpseopilot_default_meta_description',
			'wpseopilot_default_og_image',
			'wpseopilot_default_social_width',
			'wpseopilot_default_social_height',
			'wpseopilot_default_noindex',
			'wpseopilot_default_nofollow',
			'wpseopilot_global_robots',
			'wpseopilot_hreflang_map',
			'wpseopilot_robots_txt',
		];

		$data = [
			'options' => array_map(
				static function ( $key ) {
					return [
						'key'   => $key,
						'value' => get_option( $key ),
					];
				},
				$options
			),
			'meta'    => $this->export_meta(),
		];

		nocache_headers();
		header( 'Content-Type: application/json' );
		header( 'Content-Disposition: attachment; filename="wpseopilot-export-' . gmdate( 'Ymd-His' ) . '.json"' );
		echo wp_json_encode( $data );
		exit;
	}

	/**
	 * Export post meta snapshot.
	 *
	 * @return array
	 */
	private function export_meta() {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			]
		);

		$meta = [];

		foreach ( $posts as $post_id ) {
			$value = get_post_meta( $post_id, Post_Meta::META_KEY, true );
			if ( $value ) {
				$meta[ $post_id ] = $value;
			}
		}

		return $meta;
	}

	/**
	 * Import Yoast SEO per-post data.
	 *
	 * @return int Updated count.
	 */
	private function import_yoast( $dry_run = false ) {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			]
		);

		$count = 0;

		foreach ( $posts as $post ) {
			$meta = [
				'title'       => get_post_meta( $post->ID, '_yoast_wpseo_title', true ),
				'description' => get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true ),
				'canonical'   => get_post_meta( $post->ID, '_yoast_wpseo_canonical', true ),
				'noindex'     => get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-noindex', true ) ? '1' : '',
				'nofollow'    => get_post_meta( $post->ID, '_yoast_wpseo_meta-robots-nofollow', true ) ? '1' : '',
				'og_image'    => get_post_meta( $post->ID, '_yoast_wpseo_opengraph-image', true ),
			];

			if ( implode( '', $meta ) ) {
				if ( ! $dry_run ) {
					update_post_meta( $post->ID, Post_Meta::META_KEY, $meta );
				}
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Import Rank Math metadata.
	 *
	 * @return int
	 */
	private function import_rankmath( $dry_run = false ) {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			]
		);

		$count = 0;

		foreach ( $posts as $post ) {
			$robots = get_post_meta( $post->ID, 'rank_math_robots', true );
			$robots = is_string( $robots ) ? strtolower( $robots ) : '';

			$meta = [
				'title'       => get_post_meta( $post->ID, 'rank_math_title', true ),
				'description' => get_post_meta( $post->ID, 'rank_math_description', true ),
				'canonical'   => get_post_meta( $post->ID, 'rank_math_canonical_url', true ),
				'noindex'     => false !== strpos( $robots, 'noindex' ) ? '1' : '',
				'nofollow'    => false !== strpos( $robots, 'nofollow' ) ? '1' : '',
				'og_image'    => get_post_meta( $post->ID, 'rank_math_facebook_image', true ),
			];

			if ( implode( '', $meta ) ) {
				if ( ! $dry_run ) {
					update_post_meta( $post->ID, Post_Meta::META_KEY, $meta );
				}
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Import All in One SEO data.
	 *
	 * @return int
	 */
	private function import_aioseo( $dry_run = false ) {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'any',
				'posts_per_page' => -1,
			]
		);

		$count = 0;

		foreach ( $posts as $post ) {
			$aioseo = get_post_meta( $post->ID, '_aioseo_title', true );
			$og     = get_post_meta( $post->ID, '_aioseop_opengraph_settings', true );

			$meta   = [
				'title'       => $aioseo ?: get_post_meta( $post->ID, '_aioseop_title', true ),
				'description' => get_post_meta( $post->ID, '_aioseop_description', true ),
				'canonical'   => get_post_meta( $post->ID, '_aioseop_custom_link', true ),
				'noindex'     => get_post_meta( $post->ID, '_aioseop_noindex', true ) ? '1' : '',
				'nofollow'    => get_post_meta( $post->ID, '_aioseop_nofollow', true ) ? '1' : '',
				'og_image'    => is_array( $og ) ? ( $og['image'] ?? '' ) : '',
			];

			if ( implode( '', $meta ) ) {
				if ( ! $dry_run ) {
					update_post_meta( $post->ID, Post_Meta::META_KEY, $meta );
				}
				++$count;
			}
		}

		return $count;
	}
}
