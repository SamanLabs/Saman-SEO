<?php
/**
 * WebSite Schema class.
 *
 * Generates WebSite schema for all pages with publisher reference.
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
 * WebSite schema type.
 *
 * Outputs WebSite structured data with url, name, description,
 * and publisher reference to Organization or Person.
 */
class WebSite_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'WebSite';
	}

	/**
	 * Determine if WebSite schema should be output.
	 *
	 * WebSite schema is always needed on every page.
	 *
	 * @return bool Always returns true.
	 */
	public function is_needed(): bool {
		return true;
	}

	/**
	 * Generate WebSite schema.
	 *
	 * Includes: @type, @id, url, name, description, and publisher reference.
	 *
	 * @return array Schema.org WebSite structured data.
	 */
	public function generate(): array {
		$description = get_option( 'SAMAN_SEO_default_meta_description', '' );
		if ( empty( $description ) ) {
			$description = get_bloginfo( 'description' );
		}

		return [
			'@type'       => $this->get_type(),
			'@id'         => Schema_IDs::website(),
			'url'         => $this->context->site_url,
			'name'        => $this->context->site_name,
			'description' => $description,
			'publisher'   => [
				'@id' => $this->get_publisher_id(),
			],
		];
	}

	/**
	 * Get the publisher @id based on Knowledge Graph settings.
	 *
	 * @return string Publisher @id (Organization or Person).
	 */
	private function get_publisher_id(): string {
		$knowledge_type = get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' );

		if ( 'person' === $knowledge_type ) {
			return Schema_IDs::person();
		}

		return Schema_IDs::organization();
	}
}
