<?php
/**
 * Export integrations with other SEO plugins.
 *
 * @package WPSEOPilot
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
			'wpseopilot_homepage_keywords',
			'wpseopilot_homepage_description_prompt',
			'wpseopilot_homepage_knowledge_type',
			'wpseopilot_homepage_organization_name',
			'wpseopilot_homepage_organization_logo',
			'wpseopilot_default_meta_description',
			'wpseopilot_default_og_image',
			'wpseopilot_social_defaults',
			'wpseopilot_post_type_social_defaults',
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

}
