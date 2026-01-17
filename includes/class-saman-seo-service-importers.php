<?php
/**
 * Export integrations with other SEO plugins.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Export controller.
 */
class Importers {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_post_SAMAN_SEO_export', [ $this, 'handle_export' ] );
	}

	/**
	 * Handle export request.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'saman-seo' ) );
		}

		check_admin_referer( 'SAMAN_SEO_export' );

		$options = [
			'SAMAN_SEO_default_title_template',
			'SAMAN_SEO_post_type_title_templates',
			'SAMAN_SEO_post_type_meta_descriptions',
			'SAMAN_SEO_post_type_keywords',
			'SAMAN_SEO_post_type_settings',
			'SAMAN_SEO_taxonomy_settings',
			'SAMAN_SEO_archive_settings',
			'SAMAN_SEO_ai_model',
			'SAMAN_SEO_ai_prompt_system',
			'SAMAN_SEO_ai_prompt_title',
			'SAMAN_SEO_ai_prompt_description',
			'SAMAN_SEO_openai_api_key',
			'SAMAN_SEO_homepage_title',
			'SAMAN_SEO_homepage_description',
			'SAMAN_SEO_homepage_keywords',
			'SAMAN_SEO_homepage_description_prompt',
			'SAMAN_SEO_homepage_knowledge_type',
			'SAMAN_SEO_homepage_organization_name',
			'SAMAN_SEO_homepage_organization_logo',
			'SAMAN_SEO_default_meta_description',
			'SAMAN_SEO_default_og_image',
			'SAMAN_SEO_social_defaults',
			'SAMAN_SEO_post_type_social_defaults',
			'SAMAN_SEO_default_social_width',
			'SAMAN_SEO_default_social_height',
			'SAMAN_SEO_default_noindex',
			'SAMAN_SEO_default_nofollow',
			'SAMAN_SEO_global_robots',
			'SAMAN_SEO_hreflang_map',
			'SAMAN_SEO_robots_txt',
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
		header( 'Content-Disposition: attachment; filename="saman-seo-export-' . gmdate( 'Ymd-His' ) . '.json"' );
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

}
