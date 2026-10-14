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
		add_action( 'init', array( $this, 'register_meta' ), 99 );
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
				'sitemap_exclude'      => array(
					'type' => 'string',
				),
				// Snippet controls. Loose types so a boolean/integer sent
				// by the editor is normalized by sanitize() instead of
				// failing REST validation and blocking the post save.
				'nosnippet'            => array(
					'type' => array( 'string', 'boolean' ),
				),
				'max_snippet'          => array(
					'type' => array( 'string', 'integer' ),
				),
				'max_image_preview'    => array(
					'type' => 'string',
				),
				'noimageindex'         => array(
					'type' => array( 'string', 'boolean' ),
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
				'recipe'               => array(
					'type' => 'object',
				),
				'event'                => array(
					'type' => 'object',
				),
			),
		);

		// Register for every post type the editor panel appears on, not just
		// posts and pages, so block-editor saves on custom post types persist.
		// The REST API only exposes meta for types supporting custom-fields.
		foreach ( self::get_supported_post_types() as $post_type ) {
			if ( ! post_type_supports( $post_type, 'custom-fields' ) ) {
				add_post_type_support( $post_type, 'custom-fields' );
			}

			register_post_meta(
				$post_type,
				self::META_KEY,
				array(
					'type'              => 'object',
					'single'            => true,
					'show_in_rest'      => array(
						'schema' => $schema,
					),
					'default'           => array(),
					'auth_callback'     => static function ( $allowed, $meta_key, $object_id ) {
						return current_user_can( 'edit_post', $object_id );
					},
					'sanitize_callback' => array( $this, 'sanitize' ),
				)
			);
		}
	}

	/**
	 * Post types that get the SEO meta field (public, with an admin UI).
	 *
	 * Mirrors the editor panel's post types and honours the same filter.
	 *
	 * @return string[]
	 */
	public static function get_supported_post_types() {
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true,
			),
			'names'
		);

		unset( $post_types['attachment'] );

		$post_types = array_values( array_unique( array_merge( array( 'post', 'page' ), $post_types ) ) );

		return (array) saman_seo_apply_filters( 'saman_seo_editor_post_types', $post_types );
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
		$clean['sitemap_exclude'] = ! empty( $value['sitemap_exclude'] ) ? '1' : '';
		$clean['og_image']        = isset( $value['og_image'] ) ? esc_url_raw( $value['og_image'] ) : '';
		$clean['schema_type']     = isset( $value['schema_type'] ) ? sanitize_text_field( $value['schema_type'] ) : '';
		$clean['custom_schema']   = isset( $value['custom_schema'] ) ? self::sanitize_custom_schema( $value['custom_schema'] ) : '';

		// Snippet controls (also govern Google AI Overviews / AI Mode).
		$clean['nosnippet']         = self::sanitize_flag( $value['nosnippet'] ?? '' );
		$clean['max_snippet']       = self::sanitize_max_snippet( $value['max_snippet'] ?? '' );
		$clean['max_image_preview'] = self::sanitize_max_image_preview( $value['max_image_preview'] ?? '' );
		$clean['noimageindex']      = self::sanitize_flag( $value['noimageindex'] ?? '' );

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

		// Rich-result data blocks.
		if ( isset( $value['recipe'] ) && is_array( $value['recipe'] ) ) {
			$recipe = $this->sanitize_recipe( $value['recipe'] );

			if ( ! empty( $recipe ) ) {
				$clean['recipe'] = $recipe;
			}
		}

		if ( isset( $value['event'] ) && is_array( $value['event'] ) ) {
			$event = $this->sanitize_event( $value['event'] );

			if ( ! empty( $event ) ) {
				$clean['event'] = $event;
			}
		}

		return $clean;
	}

	/**
	 * Normalize a boolean-ish flag to the stored '1' / '' format.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_flag( $value ): string {
		if ( is_string( $value ) ) {
			$value = strtolower( trim( $value ) );

			return ( '' === $value || '0' === $value || 'false' === $value ) ? '' : '1';
		}

		return empty( $value ) ? '' : '1';
	}

	/**
	 * Normalize the per-post max-snippet value.
	 *
	 * Empty means "inherit the global setting"; -1 means no limit; 0 and up
	 * is a character limit.
	 *
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_max_snippet( $value ): string {
		if ( is_bool( $value ) || null === $value || is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		$value = trim( (string) $value );
		if ( '' === $value || ! preg_match( '/^-?\d+$/', $value ) ) {
			return '';
		}

		return (string) max( -1, (int) $value );
	}

	/**
	 * Normalize the per-post max-image-preview value.
	 *
	 * @param mixed $value Raw value.
	 * @return string One of none|standard|large, or '' to inherit.
	 */
	public static function sanitize_max_image_preview( $value ): string {
		$value = is_string( $value ) ? strtolower( trim( $value ) ) : '';

		return in_array( $value, array( 'none', 'standard', 'large' ), true ) ? $value : '';
	}

	/**
	 * Validate and canonicalize the custom JSON-LD schema.
	 *
	 * The value is JSON, not HTML: running it through an HTML sanitizer
	 * turns "&" into "&amp;" inside URLs and can corrupt the document.
	 * Decode it, reject anything that is not a JSON object/array, and store
	 * the re-encoded canonical JSON. Output escaping (JSON_HEX_TAG etc.)
	 * happens when the graph is printed.
	 *
	 * @param mixed $value Raw value.
	 * @return string Canonical JSON, or '' when the input is not valid JSON.
	 */
	public static function sanitize_custom_schema( $value ): string {
		if ( is_array( $value ) ) {
			$decoded = $value;
		} else {
			if ( ! is_string( $value ) ) {
				return '';
			}

			$value = trim( $value );
			if ( '' === $value ) {
				return '';
			}

			$decoded = json_decode( $value, true );
			if ( JSON_ERROR_NONE !== json_last_error() ) {
				return '';
			}
		}

		if ( ! is_array( $decoded ) || empty( $decoded ) ) {
			return '';
		}

		$encoded = wp_json_encode( $decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );

		return is_string( $encoded ) ? $encoded : '';
	}

	/**
	 * Sanitize the Recipe rich-result data block.
	 *
	 * @param array $recipe Raw recipe values.
	 * @return array<string,mixed> Empty when no usable name is present.
	 */
	public function sanitize_recipe( array $recipe ): array {
		$clean = array();

		$text_fields = array(
			'name',
			'description',
			'recipeYield',
			'keywords',
			'recipeCategory',
			'recipeCuisine',
			'nutrition_calories',
			'prepTime',
			'cookTime',
			'totalTime',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $recipe[ $field ] ) ) {
				$value = sanitize_text_field( (string) $recipe[ $field ] );

				if ( '' !== $value ) {
					$clean[ $field ] = $value;
				}
			}
		}

		if ( isset( $recipe['image'] ) ) {
			$image = esc_url_raw( (string) $recipe['image'] );
			if ( '' !== $image ) {
				$clean['image'] = $image;
			}
		} elseif ( isset( $recipe['images'] ) ) {
			$images = array_filter( array_map( 'esc_url_raw', (array) $recipe['images'] ) );
			if ( ! empty( $images ) ) {
				$clean['images'] = array_values( $images );
			}
		}

		if ( isset( $recipe['rating_value'] ) && is_numeric( $recipe['rating_value'] ) ) {
			$clean['rating_value'] = min( 5.0, max( 0.0, (float) $recipe['rating_value'] ) );
		}
		if ( isset( $recipe['rating_count'] ) ) {
			$count = absint( $recipe['rating_count'] );
			if ( $count > 0 ) {
				$clean['rating_count'] = $count;
			}
		}

		if ( isset( $recipe['recipeIngredient'] ) && is_array( $recipe['recipeIngredient'] ) ) {
			$ingredients = array();
			foreach ( $recipe['recipeIngredient'] as $ingredient ) {
				$value = sanitize_text_field( (string) $ingredient );
				if ( '' !== $value ) {
					$ingredients[] = $value;
				}
			}

			if ( ! empty( $ingredients ) ) {
				$clean['recipeIngredient'] = $ingredients;
			}
		}

		if ( isset( $recipe['recipeInstructions'] ) && is_array( $recipe['recipeInstructions'] ) ) {
			$steps = array();

			foreach ( $recipe['recipeInstructions'] as $step ) {
				if ( is_array( $step ) ) {
					$name = isset( $step['name'] ) ? sanitize_text_field( (string) $step['name'] ) : '';
					$text = isset( $step['text'] ) ? sanitize_textarea_field( (string) $step['text'] ) : '';

					if ( '' !== $name || '' !== $text ) {
						$steps[] = array(
							'name' => $name,
							'text' => $text,
						);
					}
				} else {
					$value = sanitize_textarea_field( (string) $step );
					if ( '' !== $value ) {
						$steps[] = $value;
					}
				}
			}

			if ( ! empty( $steps ) ) {
				$clean['recipeInstructions'] = $steps;
			}
		}

		// A recipe without a name cannot generate valid schema.
		if ( empty( $clean['name'] ) ) {
			return array();
		}

		return $clean;
	}

	/**
	 * Sanitize the Event rich-result data block.
	 *
	 * @param array $event Raw event values.
	 * @return array<string,mixed> Empty when no usable name/date pair exists.
	 */
	public function sanitize_event( array $event ): array {
		$clean = array();

		$text_fields = array(
			'name',
			'description',
			'venue_name',
			'venue_street_address',
			'venue_city',
			'venue_region',
			'venue_postal_code',
			'venue_country',
			'performer_name',
			'organizer_name',
			'price',
			'valid_from',
			'start_date',
			'end_date',
		);

		foreach ( $text_fields as $field ) {
			if ( isset( $event[ $field ] ) ) {
				$value = sanitize_text_field( (string) $event[ $field ] );

				if ( '' !== $value ) {
					$clean[ $field ] = $value;
				}
			}
		}

		foreach ( array( 'image', 'online_url', 'offer_url', 'organizer_url' ) as $url_field ) {
			if ( isset( $event[ $url_field ] ) ) {
				$url = esc_url_raw( (string) $event[ $url_field ] );
				if ( '' !== $url ) {
					$clean[ $url_field ] = $url;
				}
			}
		}

		if ( isset( $event['status'] ) && in_array( $event['status'], \Saman\SEO\Schema\Types\Event_Schema::STATUSES, true ) ) {
			$clean['status'] = $event['status'];
		}
		if ( isset( $event['attendance_mode'] ) && in_array( $event['attendance_mode'], \Saman\SEO\Schema\Types\Event_Schema::ATTENDANCE_MODES, true ) ) {
			$clean['attendance_mode'] = $event['attendance_mode'];
		}
		if ( isset( $event['availability'] ) && in_array( $event['availability'], \Saman\SEO\Schema\Types\Event_Schema::AVAILABILITIES, true ) ) {
			$clean['availability'] = $event['availability'];
		}
		if ( isset( $event['performer_type'] ) && 'Organization' === $event['performer_type'] ) {
			$clean['performer_type'] = 'Organization';
		}
		if ( isset( $event['price_currency'] ) ) {
			$currency = strtoupper( substr( preg_replace( '/[^A-Za-z]/', '', (string) $event['price_currency'] ), 0, 3 ) );
			if ( '' !== $currency ) {
				$clean['price_currency'] = $currency;
			}
		}

		// An event without a name or start date cannot generate valid schema.
		if ( empty( $clean['name'] ) || empty( $clean['start_date'] ) ) {
			return array();
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
			$decoded = json_decode( wp_unslash( $_POST['SAMAN_SEO_meta_json'] ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON is decoded here and every field is sanitized by sanitize().
			if ( is_array( $decoded ) ) {
				// Merge over the stored meta so keys the modal does not
				// send (recipe/event blocks, ...) survive the save.
				$existing = get_post_meta( $post_id, self::META_KEY, true );
				$existing = is_array( $existing ) ? $existing : array();

				// update_post_meta() unslashes its value; slash it so the
				// backslashes inside JSON strings (custom schema) survive.
				update_post_meta( $post_id, self::META_KEY, wp_slash( $this->sanitize( array_merge( $existing, $decoded ) ) ) );
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
			'custom_schema' => isset( $_POST['SAMAN_SEO_custom_schema'] ) ? self::sanitize_custom_schema( wp_unslash( $_POST['SAMAN_SEO_custom_schema'] ) ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON validated and re-encoded by sanitize_custom_schema().
		);

		// The classic form only knows a subset of the meta blob. Merge the
		// posted fields over the stored meta and run the shared sanitizer so
		// keys the form does not render (focus_keyphrase, sitemap_exclude,
		// secondary_keyphrases, recipe/event blocks) survive the save instead
		// of being silently wiped by an 8-key overwrite.
		$existing = get_post_meta( $post_id, self::META_KEY, true );
		$existing = is_array( $existing ) ? $existing : array();

		update_post_meta( $post_id, self::META_KEY, wp_slash( $this->sanitize( array_merge( $existing, $data ) ) ) );
	}
}
