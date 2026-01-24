<?php
/**
 * WebPage Schema class.
 *
 * Generates WebPage schema for posts with dates, breadcrumb reference,
 * and primaryImageOfPage.
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
 * WebPage schema type.
 *
 * Outputs WebPage structured data with URL, name, dates,
 * isPartOf (WebSite), breadcrumb reference, and primaryImageOfPage.
 */
class WebPage_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'WebPage';
	}

	/**
	 * Determine if WebPage schema should be output.
	 *
	 * Only outputs when we have a post context.
	 *
	 * @return bool True if we have a post.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post;
	}

	/**
	 * Generate WebPage schema.
	 *
	 * Includes: @type, @id, url, name, datePublished, dateModified,
	 * isPartOf, breadcrumb reference, and primaryImageOfPage.
	 *
	 * @return array Schema.org WebPage structured data.
	 */
	public function generate(): array {
		$post = $this->context->post;

		$schema = [
			'@type'         => $this->get_type(),
			'@id'           => Schema_IDs::webpage( $this->context->canonical ),
			'url'           => $this->context->canonical,
			'name'          => get_the_title( $post ),
			'datePublished' => get_the_date( DATE_W3C, $post ),
			'dateModified'  => get_the_modified_date( DATE_W3C, $post ),
			'isPartOf'      => [
				'@id' => Schema_IDs::website(),
			],
			'breadcrumb'    => [
				'@id' => Schema_IDs::breadcrumb( $this->context->canonical ),
			],
		];

		// Add primaryImageOfPage.
		$image_url = get_the_post_thumbnail_url( $post, 'full' );
		if ( empty( $image_url ) ) {
			$image_url = get_option( 'SAMAN_SEO_default_og_image', '' );
		}

		if ( ! empty( $image_url ) ) {
			$schema['primaryImageOfPage'] = [
				'@type' => 'ImageObject',
				'url'   => $image_url,
			];
		}

		return $schema;
	}
}
