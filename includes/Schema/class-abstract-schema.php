<?php
/**
 * Abstract Schema base class.
 *
 * Base class that all schema types extend. Provides the common interface
 * and shared logic for schema generation following Yoast's proven pattern.
 *
 * @package Saman\SEO\Schema
 * @since   1.0.0
 */

namespace Saman\SEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Abstract base class for all schema implementations.
 *
 * Every schema type (Article, WebPage, etc.) extends this class and
 * implements the required methods. This establishes the contract for
 * the registry-based architecture.
 *
 * IMPORTANT: The generate() method must NOT include @context in its return.
 * Only the root JSON-LD object (in Graph_Manager) should have @context.
 * Individual schema pieces are combined into a @graph array.
 */
abstract class Abstract_Schema {

	/**
	 * Environment context with all data schemas need.
	 *
	 * @var Schema_Context
	 */
	protected $context;

	/**
	 * Constructor receives context with all environment data.
	 *
	 * @param Schema_Context $context Environment context.
	 */
	public function __construct( Schema_Context $context ) {
		$this->context = $context;
	}

	/**
	 * Determine if this schema should be output.
	 *
	 * Each schema type implements its own logic for when it applies.
	 * For example, Article schema needs a post, WebSite schema is always needed.
	 *
	 * @return bool True if schema should be included in output.
	 */
	abstract public function is_needed(): bool;

	/**
	 * Generate the schema array.
	 *
	 * Returns the schema.org structured data array for this piece.
	 *
	 * CRITICAL: Do NOT include @context in the returned array.
	 * Only the root JSON-LD object should have @context.
	 * This method returns a single graph piece.
	 *
	 * @return array Schema.org structured data array (without @context).
	 */
	abstract public function generate(): array;

	/**
	 * Get the schema @type value.
	 *
	 * Returns the schema.org type for this schema piece.
	 * Most schemas return a string, but some return an array
	 * for multi-typed entities (e.g., ['Article', 'NewsArticle']).
	 *
	 * @return string|array The @type value(s) for this schema.
	 */
	abstract public function get_type();

	/**
	 * Generate a standard @id for this schema.
	 *
	 * Default implementation creates URL#type identifier using the
	 * canonical URL and lowercased type name. Concrete classes can
	 * override this to use Schema_IDs helper methods for standard entities.
	 *
	 * @return string URL#fragment identifier.
	 */
	protected function get_id(): string {
		$type = $this->get_type();

		// Handle array types (use first element for @id).
		if ( is_array( $type ) ) {
			$type = reset( $type );
		}

		return $this->context->canonical . '#' . strtolower( $type );
	}
}
