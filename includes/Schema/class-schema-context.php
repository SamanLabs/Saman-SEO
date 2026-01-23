<?php
/**
 * Schema Context class.
 *
 * Value object containing all environment data schemas need.
 * Decouples schema generation from WordPress global state.
 *
 * @package Saman\SEO\Schema
 * @since   1.0.0
 */

namespace Saman\SEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Context value object for schema generation.
 *
 * Contains all environment data (URLs, post info, meta, site info) that
 * schema classes need for generation. Passed via dependency injection
 * instead of accessing WordPress globals directly.
 */
class Schema_Context {

	/**
	 * Canonical URL for the current page.
	 *
	 * @var string
	 */
	public $canonical;

	/**
	 * Site home URL.
	 *
	 * @var string
	 */
	public $site_url;

	/**
	 * Site name from bloginfo.
	 *
	 * @var string
	 */
	public $site_name;

	/**
	 * Current post object or null if not on a singular page.
	 *
	 * @var \WP_Post|null
	 */
	public $post;

	/**
	 * Post type slug or empty string if no post.
	 *
	 * @var string
	 */
	public $post_type;

	/**
	 * Post SEO meta from _SAMAN_SEO_meta.
	 *
	 * @var array
	 */
	public $meta;

	/**
	 * Determined schema type for the post.
	 *
	 * @var string
	 */
	public $schema_type;

	/**
	 * Create context from current WordPress state.
	 *
	 * Captures the current environment (URL, post, site info) for use
	 * in schema generation without requiring global state access.
	 *
	 * @return Schema_Context Populated context object.
	 */
	public static function from_current(): Schema_Context {
		$context = new self();

		$context->site_url  = home_url( '/' );
		$context->site_name = get_bloginfo( 'name' );
		$context->post      = get_post();
		$context->post_type = $context->post instanceof \WP_Post ? get_post_type( $context->post ) : '';
		$context->canonical = $context->post instanceof \WP_Post ? get_permalink( $context->post ) : $context->site_url;

		// Get post meta, ensure array.
		$raw_meta       = $context->post instanceof \WP_Post
			? get_post_meta( $context->post->ID, '_SAMAN_SEO_meta', true )
			: [];
		$context->meta  = is_array( $raw_meta ) ? $raw_meta : [];

		// Determine schema type: post meta > post type settings > default.
		$context->schema_type = self::determine_schema_type( $context );

		return $context;
	}

	/**
	 * Create context from an explicit post.
	 *
	 * Use when you need context for a specific post rather than
	 * relying on WordPress global post state.
	 *
	 * @param \WP_Post $post The post to create context for.
	 * @return Schema_Context Populated context object.
	 */
	public static function from_post( \WP_Post $post ): Schema_Context {
		$context = new self();

		$context->site_url  = home_url( '/' );
		$context->site_name = get_bloginfo( 'name' );
		$context->post      = $post;
		$context->post_type = get_post_type( $post );
		$context->canonical = get_permalink( $post );

		// Get post meta, ensure array.
		$raw_meta       = get_post_meta( $post->ID, '_SAMAN_SEO_meta', true );
		$context->meta  = is_array( $raw_meta ) ? $raw_meta : [];

		// Determine schema type: post meta > post type settings > default.
		$context->schema_type = self::determine_schema_type( $context );

		return $context;
	}

	/**
	 * Determine the schema type for this context.
	 *
	 * Priority order:
	 * 1. Post-specific override from _SAMAN_SEO_meta['schema_type']
	 * 2. Post type default from SAMAN_SEO_post_type_settings option
	 * 3. Fallback to 'Article'
	 *
	 * @param Schema_Context $context The context to determine type for.
	 * @return string The schema type.
	 */
	private static function determine_schema_type( Schema_Context $context ): string {
		// Check post-specific override first.
		if ( ! empty( $context->meta['schema_type'] ) ) {
			return $context->meta['schema_type'];
		}

		// Check post type defaults.
		if ( ! empty( $context->post_type ) ) {
			$type_settings = get_option( 'SAMAN_SEO_post_type_settings', [] );
			if ( ! empty( $type_settings[ $context->post_type ]['schema_type'] ) ) {
				return $type_settings[ $context->post_type ]['schema_type'];
			}
		}

		// Default to Article.
		return 'Article';
	}
}
