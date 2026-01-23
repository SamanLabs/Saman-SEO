<?php
/**
 * BlogPosting Schema class.
 *
 * Generates BlogPosting schema for blog posts.
 * Extends Article_Schema, changing only the @type value.
 *
 * @package Saman\SEO\Schema\Types
 * @since   1.0.0
 */

namespace Saman\SEO\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BlogPosting schema type.
 *
 * Outputs BlogPosting structured data. Inherits all properties
 * from Article_Schema; only differs in @type value.
 */
class BlogPosting_Schema extends Article_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'BlogPosting';
	}

	/**
	 * Determine if BlogPosting schema should be output.
	 *
	 * Only outputs when we have a post and schema_type is 'BlogPosting'.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& 'BlogPosting' === $this->context->schema_type;
	}
}
