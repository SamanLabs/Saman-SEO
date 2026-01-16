<?php
/**
 * Export integrations with other SEO plugins.
 *
 * @package SamanLabs\SEO
 */

namespace SamanLabs\SEO\Service;

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
		add_action( 'admin_post_wpseopilot_export', [ $this, 'handle_export' ] );
	}

	/**
	 * Handle export request.
	 *
	 * @return void
	 */
	public function handle_export() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'saman-labs-seo' ) );
		}

		check_admin_referer( 'samanlabs_seo_export' );

		$options = [
			'samanlabs_seo_default_title_template',
			'samanlabs_seo_post_type_title_templates',
			'samanlabs_seo_post_type_meta_descriptions',
			'samanlabs_seo_post_type_keywords',
			'samanlabs_seo_post_type_settings',
			'samanlabs_seo_taxonomy_settings',
			'samanlabs_seo_archive_settings',
			'samanlabs_seo_ai_model',
			'samanlabs_seo_ai_prompt_system',
			'samanlabs_seo_ai_prompt_title',
			'samanlabs_seo_ai_prompt_description',
			'samanlabs_seo_openai_api_key',
			'samanlabs_seo_homepage_title',
			'samanlabs_seo_homepage_description',
			'samanlabs_seo_homepage_keywords',
			'samanlabs_seo_homepage_description_prompt',
			'samanlabs_seo_homepage_knowledge_type',
			'samanlabs_seo_homepage_organization_name',
			'samanlabs_seo_homepage_organization_logo',
			'samanlabs_seo_default_meta_description',
			'samanlabs_seo_default_og_image',
			'samanlabs_seo_social_defaults',
			'samanlabs_seo_post_type_social_defaults',
			'samanlabs_seo_default_social_width',
			'samanlabs_seo_default_social_height',
			'samanlabs_seo_default_noindex',
			'samanlabs_seo_default_nofollow',
			'samanlabs_seo_global_robots',
			'samanlabs_seo_hreflang_map',
			'samanlabs_seo_robots_txt',
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

}
