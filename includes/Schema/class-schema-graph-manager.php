<?php
/**
 * Schema Graph Manager class.
 *
 * Orchestrates schema collection and combines them into a single
 * JSON-LD @graph output with proper structure.
 *
 * @package Saman\SEO\Schema
 * @since   1.0.0
 */

namespace Saman\SEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Graph manager that builds complete JSON-LD output.
 *
 * Collects all applicable schemas from the registry, runs them through
 * their is_needed() checks, and combines them into a single @graph array.
 *
 * CRITICAL: This is the ONLY place @context should be added. Individual
 * schema pieces must NOT include @context - they are combined here.
 */
class Schema_Graph_Manager {

	/**
	 * Schema registry instance.
	 *
	 * @var Schema_Registry
	 */
	private Schema_Registry $registry;

	/**
	 * Constructor.
	 *
	 * @param Schema_Registry $registry The schema registry instance.
	 */
	public function __construct( Schema_Registry $registry ) {
		$this->registry = $registry;
	}

	/**
	 * Check whether a generated schema value is a list of schema pieces.
	 *
	 * @param array $piece Schema piece or list of pieces.
	 * @return bool True when the value is a sequential list of arrays.
	 */
	private function is_schema_list( array $piece ): bool {
		if ( empty( $piece ) ) {
			return false;
		}

		$keys = array_keys( $piece );
		if ( $keys !== range( 0, count( $piece ) - 1 ) ) {
			return false;
		}

		return isset( $piece[0] ) && is_array( $piece[0] );
	}

	/**
	 * Build complete JSON-LD graph for the current context.
	 *
	 * Collects all applicable schemas (where is_needed() returns true),
	 * applies filters, and returns the complete JSON-LD structure.
	 *
	 * @param Schema_Context $context Environment context for schema generation.
	 * @return array Complete JSON-LD structure with @context and @graph.
	 */
	public function build( Schema_Context $context ): array {
		$graph = [];

		// Get all registered types and sort by priority.
		$types = $this->registry->get_types();
		uasort( $types, fn( $a, $b ) => $a['priority'] <=> $b['priority'] );

		// Collect schemas that should be output.
		foreach ( $types as $slug => $config ) {
			$schema = $this->registry->make( $slug, $context );

			if ( ! $schema || ! $schema->is_needed() ) {
				continue;
			}

			$piece = $schema->generate();

			/**
			 * Filter the output of a specific schema type.
			 *
			 * @param array          $piece   The schema piece array, or a list of pieces.
			 * @param Schema_Context $context The current context.
			 */
			$piece = apply_filters( "saman_seo_schema_{$slug}_output", $piece, $context );

			if ( empty( $piece ) ) {
				continue;
			}

			// Allow a schema type to output multiple graph entries (e.g. multi-location LocalBusiness).
			if ( $this->is_schema_list( $piece ) ) {
				foreach ( $piece as $sub_piece ) {
					if ( ! empty( $sub_piece ) ) {
						$graph[] = $sub_piece;
					}
				}
			} else {
				$graph[] = $piece;
			}
		}

		/**
		 * Filter the complete schema graph before output.
		 *
		 * @param array          $graph   All schema pieces.
		 * @param Schema_Context $context The current context.
		 */
		$graph = apply_filters( 'saman_seo_schema_graph', $graph, $context );

		/**
		 * Legacy filter for backward compatibility.
		 *
		 * CRITICAL: Maintain this filter for existing code that hooks into
		 * the old JSON-LD service. Pass the post object as second parameter
		 * to match original signature.
		 *
		 * @param array         $graph All schema pieces.
		 * @param \WP_Post|null $post  The current post or null.
		 */
		$graph = apply_filters( 'SAMAN_SEO_jsonld_graph', $graph, $context->post );

		// Return complete JSON-LD structure.
		// @context is ONLY added here at root level - never in individual pieces.
		return [
			'@context' => 'https://schema.org',
			'@graph'   => $graph,
		];
	}
}
