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
		add_filter( 'SAMAN_SEO_jsonld', array( $this, 'build_payload' ), 10, 2 );
	}

	/**
	 * Build JSON-LD graph.
	 *
	 * Delegates to Schema_Graph_Manager for graph construction.
	 * The new schema engine handles all registered types and applies filters.
	 *
	 * @param array         $payload Existing payload (ignored, kept for filter signature).
	 * @param \WP_Post|null $post    Post.
	 *
	 * @return array Complete JSON-LD structure with @context and @graph.
	 */
	public function build_payload( $payload, $post ) {
		$context  = $post ? Schema_Context::from_post( $post ) : Schema_Context::from_current();
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
			$graph['@graph'][] = $decoded;
		} elseif ( is_array( $decoded ) ) {
			foreach ( $decoded as $item ) {
				if ( is_array( $item ) && isset( $item['@type'] ) ) {
					$graph['@graph'][] = $item;
				}
			}
		}

		return $graph;
	}
}
