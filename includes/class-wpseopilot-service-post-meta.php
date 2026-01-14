<?php
/**
 * Handles per-post SEO metadata registration and persistence.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Post meta controller.
 */
class Post_Meta {

	/**
	 * Meta key.
	 *
	 * @var string
	 */
	const META_KEY = '_wpseopilot_meta';

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', [ $this, 'register_meta' ] );
		add_action( 'save_post', [ $this, 'save_meta' ], 10, 2 );
	}

	/**
	 * Register post meta for REST + Gutenberg.
	 *
	 * @return void
	 */
	public function register_meta() {
		$schema = [
			'type'       => 'object',
			'properties' => [
				'title'           => [
					'type' => 'string',
				],
				'description'     => [
					'type' => 'string',
				],
				'focus_keyphrase' => [
					'type' => 'string',
				],
				'secondary_keyphrases' => [
					'type'  => 'array',
					'items' => [
						'type' => 'string',
					],
				],
				'canonical'       => [
					'type' => 'string',
				],
				'noindex'         => [
					'type' => 'string',
				],
				'nofollow'        => [
					'type' => 'string',
				],
				'og_image'        => [
					'type' => 'string',
				],
			],
		];

		register_post_meta(
			'post',
			self::META_KEY,
			[
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => [
					'schema' => $schema,
				],
				'default'           => [],
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => [ $this, 'sanitize' ],
			]
		);

		register_post_meta(
			'page',
			self::META_KEY,
			[
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => [
					'schema' => $schema,
				],
				'default'           => [],
				'auth_callback'     => function () {
					return current_user_can( 'edit_pages' );
				},
				'sanitize_callback' => [ $this, 'sanitize' ],
			]
		);
	}

	/**
	 * Sanitize stored meta before persistence.
	 *
	 * @param mixed $value Value.
	 *
	 * @return array<string,string>
	 */
	public function sanitize( $value ) {
		if ( ! is_array( $value ) ) {
			return [];
		}

		$clean = [];

		$clean['title']           = isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '';
		$clean['description']     = isset( $value['description'] ) ? sanitize_textarea_field( $value['description'] ) : '';
		$clean['focus_keyphrase'] = isset( $value['focus_keyphrase'] ) ? sanitize_text_field( $value['focus_keyphrase'] ) : '';
		$clean['canonical']       = isset( $value['canonical'] ) ? esc_url_raw( $value['canonical'] ) : '';
		$clean['noindex']         = ! empty( $value['noindex'] ) ? '1' : '';
		$clean['nofollow']        = ! empty( $value['nofollow'] ) ? '1' : '';
		$clean['og_image']        = isset( $value['og_image'] ) ? esc_url_raw( $value['og_image'] ) : '';

		// Handle secondary keyphrases (max 4 additional keywords).
		$clean['secondary_keyphrases'] = [];
		if ( isset( $value['secondary_keyphrases'] ) && is_array( $value['secondary_keyphrases'] ) ) {
			$secondary = array_slice( $value['secondary_keyphrases'], 0, 4 );
			foreach ( $secondary as $keyphrase ) {
				$sanitized = sanitize_text_field( $keyphrase );
				if ( ! empty( $sanitized ) ) {
					$clean['secondary_keyphrases'][] = $sanitized;
				}
			}
		}

		return $clean;
	}

	/**
	 * Save meta from classic editor form posts.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post    Post object.
	 *
	 * @return void
	 */
	public function save_meta( $post_id, $post ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( ! isset( $_POST['wpseopilot_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['wpseopilot_meta_nonce'] ), 'wpseopilot_meta' ) ) {
			return;
		}

		$data = [
			'title'       => isset( $_POST['wpseopilot_title'] ) ? sanitize_text_field( wp_unslash( $_POST['wpseopilot_title'] ) ) : '',
			'description' => isset( $_POST['wpseopilot_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['wpseopilot_description'] ) ) : '',
			'canonical'   => isset( $_POST['wpseopilot_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['wpseopilot_canonical'] ) ) : '',
			'noindex'     => ! empty( $_POST['wpseopilot_noindex'] ) ? '1' : '',
			'nofollow'    => ! empty( $_POST['wpseopilot_nofollow'] ) ? '1' : '',
			'og_image'    => isset( $_POST['wpseopilot_og_image'] ) ? esc_url_raw( wp_unslash( $_POST['wpseopilot_og_image'] ) ) : '',
		];

		update_post_meta( $post_id, self::META_KEY, $data );
	}
}
