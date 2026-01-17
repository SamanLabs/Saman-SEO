<?php
/**
 * Data store for Internal Linking module.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Internal_Linking;

use WP_Error;

defined( 'ABSPATH' ) || exit;

/**
 * Centralized CRUD for link rules, categories, UTM templates, and settings.
 */
class Repository {

	/**
	 * Option keys.
	 */
	private const OPTION_RULES      = 'SAMAN_SEO_link_rules';
	private const OPTION_CATEGORIES = 'SAMAN_SEO_link_categories';
	private const OPTION_TEMPLATES  = 'SAMAN_SEO_link_utm_templates';
	private const OPTION_SETTINGS   = 'SAMAN_SEO_link_settings';
	private const OPTION_VERSION    = 'SAMAN_SEO_link_version';

	/**
	 * Cached lookups.
	 *
	 * @var array<string,mixed>
	 */
	private $cache = [];

	/**
	 * Fetch all rules with optional filtering args.
	 *
	 * @param array $args {
	 *     @type string   $status    Optional. Filter by status (active|inactive).
	 *     @type string   $category  Optional. Filter by category ID.
	 *     @type string   $search    Optional. Search term across title/keywords.
	 *     @type string   $post_type Optional. Post type filter.
	 *     @type string   $orderby   Optional. Field to order by.
	 *     @type string   $order     Optional. ASC|DESC.
	 * }
	 *
	 * @return array<int,array>
	 */
	public function get_rules( array $args = [] ) {
		$rules = $this->get_option_array( self::OPTION_RULES );

		if ( empty( $rules ) ) {
			return [];
		}

		$rules = array_map( [ $this, 'apply_rule_defaults' ], $rules );

		if ( ! empty( $args['status'] ) ) {
			$status = ( 'inactive' === $args['status'] ) ? 'inactive' : 'active';
			$rules  = array_filter(
				$rules,
				static function ( $rule ) use ( $status ) {
					return $status === ( $rule['status'] ?? 'active' );
				}
			);
		}

		if ( ! empty( $args['category'] ) && '__all__' !== $args['category'] ) {
			$rules = array_filter(
				$rules,
				static function ( $rule ) use ( $args ) {
					return ( $rule['category'] ?? '' ) === $args['category'];
				}
			);
		}

		if ( ! empty( $args['post_type'] ) ) {
			$post_filter = array_filter( (array) $args['post_type'] );
			if ( ! empty( $post_filter ) && ! in_array( '__all__', $post_filter, true ) ) {
				$rules = array_filter(
					$rules,
					static function ( $rule ) use ( $post_filter ) {
						$post_types = $rule['scope']['post_types'] ?? [];
						if ( empty( $post_types ) ) {
							return true;
						}

						return (bool) array_intersect( $post_filter, $post_types );
					}
				);
			}
		}

		if ( ! empty( $args['search'] ) ) {
			$needle = wp_unslash( strtolower( $args['search'] ) );
			$rules  = array_filter(
				$rules,
				static function ( $rule ) use ( $needle ) {
					$haystacks = [
						strtolower( $rule['title'] ?? '' ),
						implode( ' ', array_map( 'strtolower', $rule['keywords'] ?? [] ) ),
					];

					foreach ( $haystacks as $haystack ) {
						if ( false !== strpos( $haystack, $needle ) ) {
							return true;
						}
					}

					return false;
				}
			);
		}

		$orderby = isset( $args['orderby'] ) ? sanitize_key( $args['orderby'] ) : 'priority';
		$order   = isset( $args['order'] ) && 'asc' === strtolower( $args['order'] ) ? 'asc' : 'desc';

		usort(
			$rules,
			static function ( $a, $b ) use ( $orderby, $order ) {
				$av = $a[ $orderby ] ?? ( $a['meta'][ $orderby ] ?? null );
				$bv = $b[ $orderby ] ?? ( $b['meta'][ $orderby ] ?? null );

				if ( $av === $bv ) {
					$av = $a['title'] ?? '';
					$bv = $b['title'] ?? '';
				}

				if ( $av === $bv ) {
					return 0;
				}

				if ( 'asc' === $order ) {
					return ( $av < $bv ) ? -1 : 1;
				}

				return ( $av > $bv ) ? -1 : 1;
			}
		);

		return $rules;
	}

	/**
	 * Blueprint for new rule forms.
	 *
	 * @return array
	 */
	public function get_rule_defaults() {
		return $this->apply_rule_defaults( [] );
	}

	/**
	 * Fetch single rule by ID.
	 *
	 * @param string $rule_id Rule identifier.
	 *
	 * @return array|null
	 */
	public function get_rule( $rule_id ) {
		$rules = $this->get_option_array( self::OPTION_RULES );
		if ( isset( $rules[ $rule_id ] ) ) {
			return $this->apply_rule_defaults( $rules[ $rule_id ] );
		}

		return null;
	}

	/**
	 * Remove a rule.
	 *
	 * @param string $rule_id Rule identifier.
	 *
	 * @return bool
	 */
	public function delete_rule( $rule_id ) {
		$rules = $this->get_option_array( self::OPTION_RULES );

		if ( ! isset( $rules[ $rule_id ] ) ) {
			return false;
		}

		unset( $rules[ $rule_id ] );
		$this->update_option( self::OPTION_RULES, $rules );
		$this->bump_version();

		return true;
	}

	/**
	 * Save or update rule data.
	 *
	 * @param array $data Raw rule payload.
	 *
	 * @return array|WP_Error
	 */
	public function save_rule( array $data ) {
		$rules = $this->get_option_array( self::OPTION_RULES );
		$rule  = $this->sanitize_rule( $data );

		if ( is_wp_error( $rule ) ) {
			return $rule;
		}
		$rules[ $rule['id'] ] = $rule;
		$this->update_option( self::OPTION_RULES, $rules );
		$this->bump_version();

		return $rule;
	}

	/**
	 * Validate rule payload without persisting.
	 *
	 * @param array $data Raw payload.
	 *
	 * @return array|WP_Error
	 */
	public function validate_rule( array $data ) {
		return $this->sanitize_rule( $data );
	}

	/**
	 * Bulk toggle or delete list of rules.
	 *
	 * @param array  $rule_ids Rule identifiers.
	 * @param string $action   Action key.
	 *
	 * @return int Number of affected rules.
	 */
	public function bulk_update_rules( array $rule_ids, $action ) {
		if ( empty( $rule_ids ) ) {
			return 0;
		}

		$rules = $this->get_option_array( self::OPTION_RULES );
		$action = sanitize_key( $action );
		$count = 0;

		foreach ( $rule_ids as $rule_id ) {
			if ( ! isset( $rules[ $rule_id ] ) ) {
				continue;
			}

			switch ( $action ) {
				case 'activate':
					$rules[ $rule_id ]['status'] = 'active';
					$count++;
					break;
				case 'deactivate':
					$rules[ $rule_id ]['status'] = 'inactive';
					$count++;
					break;
				case 'delete':
					unset( $rules[ $rule_id ] );
					$count++;
					break;
			}
		}

		if ( $count > 0 ) {
			$this->update_option( self::OPTION_RULES, $rules );
			$this->bump_version();
		}

		return $count;
	}

	/**
	 * Copy a rule.
	 *
	 * @param string $rule_id Rule identifier.
	 *
	 * @return array|WP_Error
	 */
	public function duplicate_rule( $rule_id ) {
		$rule = $this->get_rule( $rule_id );

		if ( ! $rule ) {
			return new WP_Error( 'SAMAN_SEO_rule_missing', __( 'Rule not found.', 'saman-seo' ) );
		}

		unset( $rule['id'] );
		$rule['title']      = sprintf( '%s %s', $rule['title'], __( '(Copy)', 'saman-seo' ) );
		$rule['created_at'] = time();
		$rule['status']     = 'inactive';

		return $this->save_rule( $rule );
	}

	/**
	 * Return all categories.
	 *
	 * @return array
	 */
	public function get_categories() {
		$categories = $this->get_option_array( self::OPTION_CATEGORIES );
		return array_map( [ $this, 'apply_category_defaults' ], $categories );
	}

	/**
	 * Default category shape.
	 *
	 * @return array
	 */
	public function get_category_defaults() {
		return $this->apply_category_defaults( [] );
	}

	/**
	 * Fetch single category.
	 *
	 * @param string $category_id Category identifier.
	 *
	 * @return array|null
	 */
	public function get_category( $category_id ) {
		$categories = $this->get_option_array( self::OPTION_CATEGORIES );

		if ( isset( $categories[ $category_id ] ) ) {
			return $this->apply_category_defaults( $categories[ $category_id ] );
		}

		return null;
	}

	/**
	 * Create or update a category.
	 *
	 * @param array $data Category payload.
	 *
	 * @return array|WP_Error
	 */
	public function save_category( array $data ) {
		$categories = $this->get_option_array( self::OPTION_CATEGORIES );
		$category   = $this->sanitize_category( $data );

		if ( is_wp_error( $category ) ) {
			return $category;
		}

		$categories[ $category['id'] ] = $category;
		$this->update_option( self::OPTION_CATEGORIES, $categories );
		$this->bump_version();

		return $category;
	}

	/**
	 * Delete a category if empty or reassigning rules.
	 *
	 * @param string      $category_id Category identifier.
	 * @param string|null $reassign    Rule reassignment category ID.
	 *
	 * @return true|WP_Error
	 */
	public function delete_category( $category_id, $reassign = null ) {
		$categories = $this->get_option_array( self::OPTION_CATEGORIES );

		if ( ! isset( $categories[ $category_id ] ) ) {
			return new WP_Error( 'SAMAN_SEO_category_missing', __( 'Category not found.', 'saman-seo' ) );
		}

		$rules  = $this->get_option_array( self::OPTION_RULES );
		$in_use = array_filter(
			$rules,
			static function ( $rule ) use ( $category_id ) {
				return ( $rule['category'] ?? '' ) === $category_id;
			}
		);

		if ( ! empty( $in_use ) ) {
			if ( empty( $reassign ) ) {
				return new WP_Error( 'SAMAN_SEO_category_in_use', __( 'Category still assigned to rules.', 'saman-seo' ) );
			}

			if ( '__none__' !== $reassign && ! isset( $categories[ $reassign ] ) ) {
				return new WP_Error( 'SAMAN_SEO_category_reassign', __( 'Reassignment category not found.', 'saman-seo' ) );
			}

			foreach ( $rules as $rule_id => $rule ) {
				if ( ( $rule['category'] ?? '' ) === $category_id ) {
					$rules[ $rule_id ]['category'] = '__none__' === $reassign ? '' : $reassign;
				}
			}

			$this->update_option( self::OPTION_RULES, $rules );
		}

		unset( $categories[ $category_id ] );
		$this->update_option( self::OPTION_CATEGORIES, $categories );
		$this->bump_version();

		return true;
	}

	/**
	 * Retrieve stored UTM templates.
	 *
	 * @return array
	 */
	public function get_templates() {
		$templates = $this->get_option_array( self::OPTION_TEMPLATES );
		return array_map( [ $this, 'apply_template_defaults' ], $templates );
	}

	/**
	 * Default UTM template structure.
	 *
	 * @return array
	 */
	public function get_template_defaults() {
		return $this->apply_template_defaults( [] );
	}

	/**
	 * Fetch template by ID.
	 *
	 * @param string $template_id Template identifier.
	 *
	 * @return array|null
	 */
	public function get_template( $template_id ) {
		$templates = $this->get_option_array( self::OPTION_TEMPLATES );
		if ( isset( $templates[ $template_id ] ) ) {
			return $this->apply_template_defaults( $templates[ $template_id ] );
		}
		return null;
	}

	/**
	 * Persist template changes.
	 *
	 * @param array $data Template payload.
	 *
	 * @return array|WP_Error
	 */
	public function save_template( array $data ) {
		$templates = $this->get_option_array( self::OPTION_TEMPLATES );
		$template  = $this->sanitize_template( $data );

		if ( is_wp_error( $template ) ) {
			return $template;
		}

		$templates[ $template['id'] ] = $template;
		$this->update_option( self::OPTION_TEMPLATES, $templates );
		$this->bump_version();

		return $template;
	}

	/**
	 * Delete template.
	 *
	 * @param string $template_id Template ID.
	 *
	 * @return bool
	 */
	public function delete_template( $template_id ) {
		$templates = $this->get_option_array( self::OPTION_TEMPLATES );

		if ( ! isset( $templates[ $template_id ] ) ) {
			return false;
		}

		unset( $templates[ $template_id ] );
		$this->update_option( self::OPTION_TEMPLATES, $templates );
		$this->bump_version();

		return true;
	}

	/**
	 * Retrieve settings merged with defaults.
	 *
	 * @return array
	 */
	public function get_settings() {
		$settings = $this->get_option_array( self::OPTION_SETTINGS );
		$defaults = $this->get_default_settings();
		$settings = wp_parse_args( $settings, $defaults );

		$settings['default_heading_levels'] = array_values(
			array_intersect( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], (array) $settings['default_heading_levels'] )
		);

		return $settings;
	}

	/**
	 * Persist settings.
	 *
	 * @param array $data Raw settings.
	 *
	 * @return array
	 */
	public function save_settings( array $data ) {
		$defaults = $this->get_default_settings();
		$settings = wp_parse_args( $data, $defaults );

		$settings['default_max_links_per_page'] = $this->clamp( absint( $settings['default_max_links_per_page'] ), 0, 50 );
		$settings['default_heading_behavior']   = in_array( $settings['default_heading_behavior'], [ 'none', 'selected', 'all' ], true ) ? $settings['default_heading_behavior'] : 'none';
		$settings['default_heading_levels']     = array_values( array_intersect( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], (array) $settings['default_heading_levels'] ) );
		$settings['avoid_existing_links']       = ! empty( $settings['avoid_existing_links'] );
		$settings['prefer_word_boundaries']     = ! empty( $settings['prefer_word_boundaries'] );
		$settings['normalize_accents']          = ! empty( $settings['normalize_accents'] );
		$settings['cache_rendered_content']     = ! empty( $settings['cache_rendered_content'] );
		$settings['chunk_long_documents']       = ! empty( $settings['chunk_long_documents'] );

		$this->update_option( self::OPTION_SETTINGS, $settings );
		$this->bump_version();

		return $settings;
	}

	/**
	 * Settings defaults per spec.
	 *
	 * @return array
	 */
	public function get_default_settings() {
		return [
			'default_max_links_per_page' => 0,
			'default_heading_behavior'   => 'none',
			'default_heading_levels'     => [ 'h2', 'h3' ],
			'avoid_existing_links'       => true,
			'prefer_word_boundaries'     => true,
			'normalize_accents'          => false,
			'cache_rendered_content'     => true,
			'chunk_long_documents'       => true,
		];
	}

	/**
	 * Return current rules version hash.
	 *
	 * @return string
	 */
	public function get_version() {
		$version = get_option( self::OPTION_VERSION, '' );
		if ( empty( $version ) ) {
			$version = (string) time();
			update_option( self::OPTION_VERSION, $version );
		}
		return (string) $version;
	}

	/**
	 * Force a new version string.
	 *
	 * @return void
	 */
	public function bump_version() {
		update_option( self::OPTION_VERSION, (string) time() );
	}

	/**
	 * Ensure option value is an array.
	 *
	 * @param string $option Option name.
	 *
	 * @return array
	 */
	private function get_option_array( $option ) {
		if ( isset( $this->cache[ $option ] ) ) {
			return $this->cache[ $option ];
		}

		$value = get_option( $option, [] );

		if ( ! is_array( $value ) ) {
			$value = [];
		}

		$this->cache[ $option ] = $value;

		return $value;
	}

	/**
	 * Update option and invalidate cache.
	 *
	 * @param string $option Option name.
	 * @param array  $value  Value to persist.
	 *
	 * @return void
	 */
	private function update_option( $option, array $value ) {
		update_option( $option, $value );
		unset( $this->cache[ $option ] );
	}

	/**
	 * Guarantee rule contains expected keys.
	 *
	 * @param array $rule Rule payload.
	 *
	 * @return array
	 */
	private function apply_rule_defaults( array $rule ) {
		$defaults = [
			'id'         => '',
			'title'      => '',
			'category'   => '',
			'created_at' => time(),
			'updated_at' => time(),
			'keywords'   => [],
			'destination' => [
				'type' => 'post',
				'post' => 0,
				'url'  => '',
			],
			'utm_template' => 'inherit',
			'utm_apply_to' => 'both',
			'attributes'   => [
				'title'    => '',
				'no_title' => false,
				'nofollow' => false,
				'new_tab'  => false,
			],
			'limits' => [
				'max_page'  => 1,
				'max_block' => null,
			],
			'priority' => 10,
			'status'   => 'active',
			'placement' => [
				'headings'       => 'none',
				'heading_levels' => [],
				'paragraphs'     => true,
				'lists'          => false,
				'captions'       => false,
				'widgets'        => false,
			],
			'scope' => [
				'post_types' => [],
				'whitelist'  => [],
				'blacklist'  => [],
			],
		];

		$rule = wp_parse_args( $rule, $defaults );
		$rule['keywords'] = array_values( array_filter( (array) $rule['keywords'] ) );

		return $rule;
	}

	/**
	 * Guarantee category keys.
	 *
	 * @param array $category Category payload.
	 *
	 * @return array
	 */
	private function apply_category_defaults( array $category ) {
		$defaults = [
			'id'           => '',
			'name'         => '',
			'color'        => '#4F46E5',
			'description'  => '',
			'default_utm'  => '',
			'category_cap' => 0,
			'created_at'   => time(),
			'updated_at'   => time(),
		];

		$category = wp_parse_args( $category, $defaults );
		$category['color']        = $category['color'] ? $category['color'] : '#4F46E5';
		$category['category_cap'] = $this->clamp( absint( $category['category_cap'] ), 0, 50 );

		return $category;
	}

	/**
	 * Guarantee template keys.
	 *
	 * @param array $template Template payload.
	 *
	 * @return array
	 */
	private function apply_template_defaults( array $template ) {
		$defaults = [
			'id'           => '',
			'name'         => '',
			'utm_source'   => '',
			'utm_medium'   => '',
			'utm_campaign' => '',
			'utm_term'     => '',
			'utm_content'  => '',
			'apply_to'     => 'both',
			'append_mode'  => 'append_if_missing',
			'created_at'   => time(),
			'updated_at'   => time(),
		];

		$template = wp_parse_args( $template, $defaults );
		$template['apply_to']    = in_array( $template['apply_to'], [ 'internal', 'external', 'both' ], true ) ? $template['apply_to'] : 'both';
		$template['append_mode'] = in_array( $template['append_mode'], [ 'append_if_missing', 'always_overwrite', 'never' ], true ) ? $template['append_mode'] : 'append_if_missing';

		return $template;
	}

	/**
	 * Sanitize rule payload.
	 *
	 * @param array $data Raw data.
	 *
	 * @return array|WP_Error
	 */
	private function sanitize_rule( array $data ) {
		$data = wp_unslash( $data );

		$existing_id = isset( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		$rule_id     = $existing_id ?: $this->generate_id( 'rule' );
		$title       = isset( $data['title'] ) ? sanitize_text_field( $data['title'] ) : '';
		$category    = isset( $data['category'] ) ? sanitize_key( $data['category'] ) : '';

		$keywords = $this->sanitize_keywords( $data['keywords'] ?? [] );

		if ( empty( $title ) ) {
			return new WP_Error( 'SAMAN_SEO_rule_title', __( 'Internal Title is required.', 'saman-seo' ) );
		}

		if ( empty( $keywords ) ) {
			return new WP_Error( 'SAMAN_SEO_rule_keywords', __( 'Add at least one keyword.', 'saman-seo' ) );
		}

		$destination_type = ( isset( $data['destination']['type'] ) && 'url' === $data['destination']['type'] ) ? 'url' : 'post';
		$destination_post = isset( $data['destination']['post'] ) ? absint( $data['destination']['post'] ) : 0;
		$destination_url  = isset( $data['destination']['url'] ) ? esc_url_raw( $data['destination']['url'] ) : '';

		if ( 'post' === $destination_type ) {
			if ( ! $destination_post ) {
				return new WP_Error( 'SAMAN_SEO_rule_destination', __( 'Select a destination post.', 'saman-seo' ) );
			}

			if ( ! get_post( $destination_post ) ) {
				return new WP_Error( 'SAMAN_SEO_rule_destination', __( 'Destination post not found.', 'saman-seo' ) );
			}
		} else {
			if ( empty( $destination_url ) ) {
				return new WP_Error( 'SAMAN_SEO_rule_destination', __( 'Enter a destination URL.', 'saman-seo' ) );
			}
		}

		$utm_template = isset( $data['utm_template'] ) ? sanitize_key( $data['utm_template'] ) : 'inherit';
		if ( empty( $utm_template ) ) {
			$utm_template = 'inherit';
		}

		$utm_apply_to = isset( $data['utm_apply_to'] ) ? sanitize_key( $data['utm_apply_to'] ) : 'both';
		$utm_apply_to = in_array( $utm_apply_to, [ 'internal', 'external', 'both' ], true ) ? $utm_apply_to : 'both';

		$attribute_title = isset( $data['attributes']['title'] ) ? sanitize_text_field( $data['attributes']['title'] ) : '';
		$attributes      = [
			'title'    => $attribute_title,
			'no_title' => ! empty( $data['attributes']['no_title'] ),
			'nofollow' => ! empty( $data['attributes']['nofollow'] ),
			'new_tab'  => ! empty( $data['attributes']['new_tab'] ),
		];

		if ( $attributes['no_title'] ) {
			$attributes['title'] = '';
		}

		$max_page_raw = $data['limits']['max_page'] ?? '';
		$max_page     = ( '' === $max_page_raw ) ? '' : $this->clamp( absint( $max_page_raw ), 0, 50 );
		$limits = [
			'max_page'  => $max_page,
			'max_block' => isset( $data['limits']['max_block'] ) && '' !== $data['limits']['max_block'] ? $this->clamp( absint( $data['limits']['max_block'] ), 0, 50 ) : null,
		];

		$priority = isset( $data['priority'] ) ? intval( $data['priority'] ) : 10;
		$priority = $this->clamp( $priority, -100, 100 );

		$status = isset( $data['status'] ) && 'inactive' === $data['status'] ? 'inactive' : 'active';

		$placement = [
			'headings'       => isset( $data['placement']['headings'] ) ? sanitize_key( $data['placement']['headings'] ) : 'none',
			'heading_levels' => array_values( array_intersect( [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], (array) ( $data['placement']['heading_levels'] ?? [] ) ) ),
			'paragraphs'     => ! empty( $data['placement']['paragraphs'] ),
			'lists'          => ! empty( $data['placement']['lists'] ),
			'captions'       => ! empty( $data['placement']['captions'] ),
			'widgets'        => ! empty( $data['placement']['widgets'] ),
		];

		if ( ! in_array( $placement['headings'], [ 'none', 'selected', 'all' ], true ) ) {
			$placement['headings'] = 'none';
		}

		if ( 'selected' !== $placement['headings'] ) {
			$placement['heading_levels'] = [];
		}

		$scope_post_types = array_filter(
			(array) ( $data['scope']['post_types'] ?? [] ),
			static function ( $post_type ) {
				$post_type = sanitize_key( $post_type );
				return post_type_exists( $post_type );
			}
		);

		$urls_to_array = static function ( $value ) {
			if ( is_array( $value ) ) {
				$list = $value;
			} else {
				$list = preg_split( '/\r\n|\r|\n/', (string) $value );
			}

			$list = array_map( 'trim', $list );
			$list = array_filter( $list );

			return array_values( $list );
		};

		sort( $scope_post_types );

		$scope = [
			'post_types' => array_values( $scope_post_types ),
			'whitelist'  => $urls_to_array( $data['scope']['whitelist'] ?? [] ),
			'blacklist'  => $urls_to_array( $data['scope']['blacklist'] ?? [] ),
		];

		return [
			'id'          => $rule_id,
			'title'       => $title,
			'category'    => $category,
			'created_at'  => isset( $data['created_at'] ) ? absint( $data['created_at'] ) : time(),
			'updated_at'  => time(),
			'keywords'    => $keywords,
			'destination' => [
				'type' => $destination_type,
				'post' => $destination_post,
				'url'  => $destination_url,
			],
			'utm_template' => $utm_template,
			'utm_apply_to' => $utm_apply_to,
			'attributes'   => $attributes,
			'limits'       => $limits,
			'priority'     => $priority,
			'status'       => $status,
			'placement'    => $placement,
			'scope'        => $scope,
		];
	}

	/**
	 * Sanitize category payload.
	 *
	 * @param array $data Raw data.
	 *
	 * @return array|WP_Error
	 */
	private function sanitize_category( array $data ) {
		$data = wp_unslash( $data );

		$existing_id = isset( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		$id          = $existing_id ?: $this->generate_id( 'cat' );
		$name        = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';
		$description = isset( $data['description'] ) ? sanitize_textarea_field( $data['description'] ) : '';
		$color       = isset( $data['color'] ) ? sanitize_hex_color( $data['color'] ) : '';
		$default_utm = isset( $data['default_utm'] ) ? sanitize_key( $data['default_utm'] ) : '';
		$cap         = isset( $data['category_cap'] ) ? absint( $data['category_cap'] ) : 0;

		if ( empty( $name ) ) {
			return new WP_Error( 'SAMAN_SEO_category_name', __( 'Name is required.', 'saman-seo' ) );
		}

		return [
			'id'           => $id,
			'name'         => $name,
			'color'        => $color ?: '#4F46E5',
			'description'  => $description,
			'default_utm'  => $default_utm,
			'category_cap' => $this->clamp( $cap, 0, 50 ),
			'created_at'   => isset( $data['created_at'] ) ? absint( $data['created_at'] ) : time(),
			'updated_at'   => time(),
		];
	}

	/**
	 * Sanitize template payload.
	 *
	 * @param array $data Raw data.
	 *
	 * @return array|WP_Error
	 */
	private function sanitize_template( array $data ) {
		$data = wp_unslash( $data );

		$existing_id = isset( $data['id'] ) ? sanitize_key( $data['id'] ) : '';
		$id          = $existing_id ?: $this->generate_id( 'utm' );
		$name        = isset( $data['name'] ) ? sanitize_text_field( $data['name'] ) : '';

		if ( empty( $name ) ) {
			return new WP_Error( 'SAMAN_SEO_template_name', __( 'Template name is required.', 'saman-seo' ) );
		}

		$apply_to    = isset( $data['apply_to'] ) ? sanitize_key( $data['apply_to'] ) : 'both';
		$append_mode = isset( $data['append_mode'] ) ? sanitize_key( $data['append_mode'] ) : 'append_if_missing';

		if ( ! in_array( $apply_to, [ 'internal', 'external', 'both' ], true ) ) {
			$apply_to = 'both';
		}

		if ( ! in_array( $append_mode, [ 'append_if_missing', 'always_overwrite', 'never' ], true ) ) {
			$append_mode = 'append_if_missing';
		}

		return [
			'id'           => $id,
			'name'         => $name,
			'utm_source'   => isset( $data['utm_source'] ) ? sanitize_text_field( $data['utm_source'] ) : '',
			'utm_medium'   => isset( $data['utm_medium'] ) ? sanitize_text_field( $data['utm_medium'] ) : '',
			'utm_campaign' => isset( $data['utm_campaign'] ) ? sanitize_text_field( $data['utm_campaign'] ) : '',
			'utm_term'     => isset( $data['utm_term'] ) ? sanitize_text_field( $data['utm_term'] ) : '',
			'utm_content'  => isset( $data['utm_content'] ) ? sanitize_text_field( $data['utm_content'] ) : '',
			'apply_to'     => $apply_to,
			'append_mode'  => $append_mode,
			'created_at'   => isset( $data['created_at'] ) ? absint( $data['created_at'] ) : time(),
			'updated_at'   => time(),
		];
	}

	/**
	 * Normalize keywords list.
	 *
	 * @param mixed $value Keywords.
	 *
	 * @return array
	 */
	private function sanitize_keywords( $value ) {
		if ( is_string( $value ) ) {
			$parts = preg_split( '/\r\n|\r|\n|,/', $value );
		} elseif ( is_array( $value ) ) {
			$parts = $value;
		} else {
			$parts = [];
		}

		$parts = array_map( 'sanitize_text_field', $parts );
		$parts = array_filter( array_map( 'trim', $parts ) );

		return array_values( array_unique( $parts ) );
	}

	/**
	 * Simple helper to generate IDs.
	 *
	 * @param string $prefix Prefix slug.
	 *
	 * @return string
	 */
	private function generate_id( $prefix ) {
		return sanitize_key( $prefix . '_' . wp_generate_uuid4() );
	}

	/**
	 * Limit numeric input to range.
	 *
	 * @param int $value Value.
	 * @param int $min   Min.
	 * @param int $max   Max.
	 *
	 * @return int
	 */
	private function clamp( $value, $min, $max ) {
		$value = (int) $value;
		if ( $value < $min ) {
			return $min;
		}
		if ( $value > $max ) {
			return $max;
		}
		return $value;
	}
}
