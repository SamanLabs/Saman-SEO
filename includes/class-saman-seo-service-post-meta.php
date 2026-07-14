<?php
/**
 * Handles per-post SEO metadata registration and persistence.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

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
	const META_KEY = '_SAMAN_SEO_meta';

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );
	}

	/**
	 * Register post meta for REST + Gutenberg.
	 *
	 * @return void
	 */
	public function register_meta() {
		$schema = array(
			'type'       => 'object',
			'properties' => array(
				'title'                => array(
					'type' => 'string',
				),
				'description'          => array(
					'type' => 'string',
				),
				'focus_keyphrase'      => array(
					'type' => 'string',
				),
				'secondary_keyphrases' => array(
					'type'  => 'array',
					'items' => array(
						'type' => 'string',
					),
				),
				'canonical'            => array(
					'type' => 'string',
				),
				'noindex'              => array(
					'type' => 'string',
				),
				'nofollow'             => array(
					'type' => 'string',
				),
				'og_image'             => array(
					'type' => 'string',
				),
				'schema_type'          => array(
					'type' => 'string',
				),
				'custom_schema'        => array(
					'type' => 'string',
				),
			),
		);

		register_post_meta(
			'post',
			self::META_KEY,
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => $schema,
				),
				'default'           => array(),
				'auth_callback'     => function () {
					return current_user_can( 'edit_posts' );
				},
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);

		register_post_meta(
			'page',
			self::META_KEY,
			array(
				'type'              => 'object',
				'single'            => true,
				'show_in_rest'      => array(
					'schema' => $schema,
				),
				'default'           => array(),
				'auth_callback'     => function () {
					return current_user_can( 'edit_pages' );
				},
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
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
			return array();
		}

		$clean = array();

		$clean['title']           = isset( $value['title'] ) ? sanitize_text_field( $value['title'] ) : '';
		$clean['description']     = isset( $value['description'] ) ? sanitize_textarea_field( $value['description'] ) : '';
		$clean['focus_keyphrase'] = isset( $value['focus_keyphrase'] ) ? sanitize_text_field( $value['focus_keyphrase'] ) : '';
		$clean['canonical']       = isset( $value['canonical'] ) ? esc_url_raw( $value['canonical'] ) : '';
		$clean['noindex']         = ! empty( $value['noindex'] ) ? '1' : '';
		$clean['nofollow']        = ! empty( $value['nofollow'] ) ? '1' : '';
		$clean['og_image']        = isset( $value['og_image'] ) ? esc_url_raw( $value['og_image'] ) : '';
		$clean['schema_type']     = isset( $value['schema_type'] ) ? sanitize_text_field( $value['schema_type'] ) : '';
		$clean['custom_schema']   = isset( $value['custom_schema'] ) ? wp_kses_post( wp_unslash( $value['custom_schema'] ) ) : '';

		// Handle secondary keyphrases (max 4 additional keywords).
		$clean['secondary_keyphrases'] = array();
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

		if ( ! isset( $_POST['SAMAN_SEO_meta_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['SAMAN_SEO_meta_nonce'] ) ), 'SAMAN_SEO_meta' ) ) {
			return;
		}

		// Modal posts the full meta object as JSON. Decode and run through the
		// shared sanitizer so every field (focus keyphrase, schema, social,
		// secondary keyphrases, etc.) persists for any post type.
		if ( isset( $_POST['SAMAN_SEO_meta_json'] ) ) {
			$decoded = json_decode( wp_unslash( $_POST['SAMAN_SEO_meta_json'] ), true );
			if ( is_array( $decoded ) ) {
				update_post_meta( $post_id, self::META_KEY, $this->sanitize( $decoded ) );
				return;
			}
		}

		$data = array(
			'title'         => isset( $_POST['SAMAN_SEO_title'] ) ? sanitize_text_field( wp_unslash( $_POST['SAMAN_SEO_title'] ) ) : '',
			'description'   => isset( $_POST['SAMAN_SEO_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['SAMAN_SEO_description'] ) ) : '',
			'canonical'     => isset( $_POST['SAMAN_SEO_canonical'] ) ? esc_url_raw( wp_unslash( $_POST['SAMAN_SEO_canonical'] ) ) : '',
			'noindex'       => ! empty( $_POST['SAMAN_SEO_noindex'] ) ? '1' : '',
			'nofollow'      => ! empty( $_POST['SAMAN_SEO_nofollow'] ) ? '1' : '',
			'og_image'      => isset( $_POST['SAMAN_SEO_og_image'] ) ? esc_url_raw( wp_unslash( $_POST['SAMAN_SEO_og_image'] ) ) : '',
			'schema_type'   => isset( $_POST['SAMAN_SEO_schema_type'] ) ? sanitize_text_field( wp_unslash( $_POST['SAMAN_SEO_schema_type'] ) ) : '',
			'custom_schema' => isset( $_POST['SAMAN_SEO_custom_schema'] ) ? wp_kses_post( wp_unslash( $_POST['SAMAN_SEO_custom_schema'] ) ) : '',
		);

		update_post_meta( $post_id, self::META_KEY, $data );
	}
}
