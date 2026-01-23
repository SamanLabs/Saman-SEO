<?php
/**
 * Breadcrumb Schema class.
 *
 * Generates BreadcrumbList schema for posts with proper ListItem positioning.
 *
 * @package Saman\SEO\Schema\Types
 * @since   1.0.0
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Breadcrumb (BreadcrumbList) schema type.
 *
 * Outputs BreadcrumbList structured data with itemListElement array
 * containing Home, ancestors, and current page as ListItems.
 */
class Breadcrumb_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'BreadcrumbList';
	}

	/**
	 * Determine if Breadcrumb schema should be output.
	 *
	 * Only outputs when we have a post context.
	 *
	 * @return bool True if we have a post.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post;
	}

	/**
	 * Generate BreadcrumbList schema.
	 *
	 * Includes: @type, @id, and itemListElement array with:
	 * - Home crumb (position 1)
	 * - Ancestors in order (if hierarchical)
	 * - Current page (last position)
	 *
	 * @return array Schema.org BreadcrumbList structured data.
	 */
	public function generate(): array {
		$post   = $this->context->post;
		$crumbs = [];
		$rank   = 1;

		// Home crumb first.
		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank++,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		];

		// Ancestors in reverse order (parent -> grandparent becomes grandparent -> parent).
		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = [
				'@type'    => 'ListItem',
				'position' => $rank++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			];
		}

		// Current page last.
		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		];

		return [
			'@type'           => $this->get_type(),
			'@id'             => Schema_IDs::breadcrumb( $this->context->canonical ),
			'itemListElement' => $crumbs,
		];
	}
}
