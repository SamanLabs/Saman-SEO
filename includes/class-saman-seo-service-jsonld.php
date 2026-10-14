<?php
/**
 * JSON-LD payload builder.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use Saman\SEO\Schema\Schema_Registry;
use Saman\SEO\Schema\Schema_Graph_Manager;
use Saman\SEO\Schema\Schema_Context;

defined( 'ABSPATH' ) || exit;

/**
 * JSON-LD service.
 */
class JsonLD {

	/**
	 * Hook filters.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld', array( $this, 'build_payload' ), 10, 3 );
	}

	/**
	 * Build JSON-LD graph.
	 *
	 * Delegates to Schema_Graph_Manager for graph construction.
	 * The new schema engine handles all registered types and applies filters.
	 *
	 * @param array         $payload   Existing payload (ignored, kept for filter signature).
	 * @param \WP_Post|null $post      Post the page describes; null on archives,
	 *                                 the blog home and search results.
	 * @param string        $canonical Canonical URL the head tag printed. When
	 *                                 given it replaces the context URL so
	 *                                 WebPage url/@id match <link rel="canonical">.
	 *
	 * @return array Complete JSON-LD structure with @context and @graph.
	 */
	public function build_payload( $payload, $post, $canonical = '' ) {
		$context = $post instanceof \WP_Post ? Schema_Context::from_post( $post ) : Schema_Context::from_current();

		if ( is_string( $canonical ) && '' !== $canonical ) {
			$context->canonical = $canonical;
		}

		$registry = Schema_Registry::instance();
		$manager  = new Schema_Graph_Manager( $registry );

		$graph = $manager->build( $context );

		// Inject user-defined custom schema graph items from post meta.
		if ( ! empty( $context->meta['custom_schema'] ) ) {
			$graph = $this->inject_custom_schema( $graph, $context->meta['custom_schema'] );
		}

		return $graph;
	}

	/**
	 * Inject custom schema items into the JSON-LD graph.
	 *
	 * @param array  $graph  JSON-LD graph structure.
	 * @param string $custom Raw custom schema JSON or array string.
	 *
	 * @return array
	 */
	private function inject_custom_schema( $graph, $custom ) {
		if ( ! is_array( $graph ) || ! isset( $graph['@graph'] ) ) {
			return $graph;
		}

		$custom = is_string( $custom ) ? trim( $custom ) : $custom;
		if ( '' === $custom ) {
			return $graph;
		}

		$decoded = json_decode( $custom, true );
		if ( null === $decoded && '' !== $custom ) {
			return $graph;
		}

		// Support both a single object and an array of objects.
		if ( isset( $decoded['@type'] ) ) {
			$graph['@graph'][] = $this->strip_schema_context( $decoded );
		} elseif ( is_array( $decoded ) ) {
			foreach ( $decoded as $item ) {
				if ( is_array( $item ) && isset( $item['@type'] ) ) {
					$graph['@graph'][] = $this->strip_schema_context( $item );
				}
			}
		}

		return $graph;
	}

	/**
	 * Remove a schema.org @context from a piece placed inside @graph; the
	 * root object already declares it.
	 *
	 * @param array $item Graph piece.
	 * @return array
	 */
	private function strip_schema_context( array $item ): array {
		if ( isset( $item['@context'] ) && is_string( $item['@context'] )
			&& preg_match( '#^https?://schema\.org/?$#i', $item['@context'] ) ) {
			unset( $item['@context'] );
		}

		return $item;
	}
}
