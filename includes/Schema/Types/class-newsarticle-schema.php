<?php
/**
 * NewsArticle Schema class.
 *
 * Generates NewsArticle schema for news content.
 * Extends Article_Schema, adding dateline and printSection.
 *
 * @package Saman\SEO\Schema\Types
 * @since   1.0.0
 */

namespace Saman\SEO\Schema\Types;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * NewsArticle schema type.
 *
 * Outputs NewsArticle structured data with news-specific
 * properties like dateline and printSection.
 */
class NewsArticle_Schema extends Article_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'NewsArticle';
	}

	/**
	 * Determine if NewsArticle schema should be output.
	 *
	 * Only outputs when we have a post and schema_type is 'NewsArticle'.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& 'NewsArticle' === $this->context->schema_type;
	}

	/**
	 * Generate NewsArticle schema.
	 *
	 * Extends Article schema with news-specific properties.
	 *
	 * @return array Schema.org NewsArticle structured data.
	 */
	public function generate(): array {
		$schema = parent::generate();

		// Add dateline if available.
		$dateline = $this->get_dateline();
		if ( ! empty( $dateline ) ) {
			$schema['dateline'] = $dateline;
		}

		// Add printSection if available.
		$print_section = $this->get_print_section();
		if ( ! empty( $print_section ) ) {
			$schema['printSection'] = $print_section;
		}

		return $schema;
	}

	/**
	 * Get the dateline for this news article.
	 *
	 * Tries post meta first, then auto-generates from Local SEO city.
	 * Dateline format is typically "CITY, Date" (e.g., "WASHINGTON, Jan 23").
	 *
	 * @return string The dateline or empty string.
	 */
	protected function get_dateline(): string {
		// Check post meta first.
		$dateline = $this->context->meta['dateline'] ?? '';

		if ( empty( $dateline ) ) {
			// Auto-generate from Local SEO city.
			$city = get_option( 'SAMAN_SEO_local_city', '' );
			if ( ! empty( $city ) ) {
				$dateline = strtoupper( $city ) . ', ' . get_the_date( 'M j', $this->context->post );
			}
		}

		return $dateline;
	}

	/**
	 * Get the print section for this news article.
	 *
	 * Retrieved from post meta if set.
	 *
	 * @return string The print section or empty string.
	 */
	protected function get_print_section(): string {
		return $this->context->meta['print_section'] ?? '';
	}
}
