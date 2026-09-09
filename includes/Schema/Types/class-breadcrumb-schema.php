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
use Saman\SEO\Schema\Schema_Context;
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
	 * Needs a post context, the breadcrumb feature switched on, and -- on the
	 * front page -- the show_on_front setting. Without that last check the front
	 * page gets a two-item list whose only crumbs are the site name and the page
	 * itself, which is a self-referential trail that search engines flag rather
	 * than use.
	 *
	 * Themes can override the decision through `saman_seo_breadcrumb_schema_needed`,
	 * which is what a theme supplying its own trail via
	 * `saman_seo_breadcrumb_trail` needs in order to emit on a post type whose
	 * hierarchy this class cannot infer.
	 *
	 * @return bool True when the BreadcrumbList should be emitted.
	 */
	public function is_needed(): bool {
		$needed = $this->context->post instanceof \WP_Post;

		if ( $needed ) {
			$plugin  = \Saman\SEO\Plugin::instance();
			$service = $plugin->get( 'breadcrumbs' );

			if ( $service ) {
				$settings = $service->get_settings();
				$needed   = ! empty( $settings['enabled'] );

				if ( $needed && is_front_page() && empty( $settings['show_on_front'] ) ) {
					$needed = false;
				}
			}
		}

		return (bool) saman_seo_apply_filters( 'saman_seo_breadcrumb_schema_needed', $needed, $this->context );
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
		$crumbs = array();
		$rank   = 1;

		// Home crumb first.
		$crumbs[] = array(
			'@type'    => 'ListItem',
			'position' => $rank++,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		);

		// Ancestors in reverse order (parent -> grandparent becomes grandparent -> parent).
		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = array(
				'@type'    => 'ListItem',
				'position' => $rank++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			);
		}

		// Current page last.
		$crumbs[] = array(
			'@type'    => 'ListItem',
			'position' => $rank,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		);

		/**
		 * Filter the breadcrumb trail before it is written into the graph.
		 *
		 * The default trail is built from get_post_ancestors(), which returns nothing
		 * for a post type that is flat in the database but presented as nested on the
		 * front end. The list then holds only the site root and the current entry,
		 * with every intermediate level missing. A theme that knows the real
		 * hierarchy can return the full trail here; positions are renumbered below,
		 * so a filter only has to get the order right.
		 *
		 * @param array          $crumbs  List of ListItem arrays.
		 * @param Schema_Context $context Current schema context.
		 */
		$crumbs = saman_seo_apply_filters( 'saman_seo_breadcrumb_trail', $crumbs, $this->context );
		$crumbs = is_array( $crumbs ) ? array_values( array_filter( $crumbs, 'is_array' ) ) : array();

		// Renumber so a filtered trail can never ship a broken position sequence.
		$rank = 1;
		foreach ( $crumbs as &$crumb ) {
			$crumb['@type']    = 'ListItem';
			$crumb['position'] = $rank++;
		}
		unset( $crumb );

		return array(
			'@type'           => $this->get_type(),
			'@id'             => Schema_IDs::breadcrumb( $this->context->canonical ),
			'itemListElement' => $crumbs,
		);
	}
}
