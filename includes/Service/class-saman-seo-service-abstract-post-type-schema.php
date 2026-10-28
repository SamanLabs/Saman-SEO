<?php
/**
 * Shared base for the post-type driven schema services.
 *
 * The Course, Book, JobPosting, Movie, Music, Restaurant, Service and
 * Software services each add one node to the JSON-LD @graph for posts of a
 * matching post type. This class holds what they share: reading the
 * per-field post meta, building a stable @id, running the node through its
 * developer filter, and dropping empty values so nothing invented or blank
 * reaches the page.
 *
 * Field values come from post meta keys named
 * `_saman_seo_{type}_{field}` (for example `_saman_seo_course_provider`).
 * Every node is passed through `saman_seo_schema_{type}` before output, so
 * sites that keep this data elsewhere can supply it from code.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use Saman\SEO\Schema\Schema_IDs;

defined( 'ABSPATH' ) || exit;

/**
 * Base class for post-type schema services.
 */
abstract class Abstract_Post_Type_Schema {

	/**
	 * Post type the node is emitted for (e.g. 'course').
	 *
	 * @return string
	 */
	abstract protected function get_post_type(): string;

	/**
	 * Schema slug used for meta keys, the filter name and the @id fragment
	 * (e.g. 'course', 'job_posting').
	 *
	 * @return string
	 */
	abstract protected function get_schema_slug(): string;

	/**
	 * Build the raw node for a post, before filtering and cleanup.
	 *
	 * @param \WP_Post $post Post.
	 * @return array
	 */
	abstract protected function build_node( \WP_Post $post ): array;

	/**
	 * Whether a filtered, cleaned node carries everything the type needs.
	 *
	 * Defaults to requiring a name. Types with stricter Google requirements
	 * override this.
	 *
	 * @param array $node Node.
	 * @return bool
	 */
	protected function is_valid_node( array $node ): bool {
		return ! empty( $node['name'] );
	}

	/**
	 * Add this service's node to the JSON-LD graph.
	 *
	 * @param array         $graph Graph pieces.
	 * @param \WP_Post|null $post  Post the graph is built for; null off singular views.
	 * @return array
	 */
	protected function add_node_to_graph( $graph, $post ) {
		if ( ! is_array( $graph ) ) {
			return $graph;
		}

		$node = $this->get_node( $post );

		if ( ! empty( $node ) ) {
			$graph[] = $node;
		}

		return $graph;
	}

	/**
	 * Build the final node for a post, or an empty array when it must not
	 * be emitted.
	 *
	 * @param \WP_Post|null $post Post.
	 * @return array
	 */
	public function get_node( $post ): array {
		if ( ! $post instanceof \WP_Post || $this->get_post_type() !== get_post_type( $post ) ) {
			return array();
		}

		$node = $this->build_node( $post );

		/**
		 * Filter a post-type schema node before it is added to the graph.
		 *
		 * The hook name is `saman_seo_schema_{type}`, e.g.
		 * `saman_seo_schema_course` or `saman_seo_schema_job_posting`. Return
		 * an empty array to suppress the node.
		 *
		 * @param array    $node Node built from post data and `_saman_seo_{type}_*` meta.
		 * @param \WP_Post $post Post the node describes.
		 */
		$node = saman_seo_apply_filters( 'saman_seo_schema_' . $this->get_schema_slug(), $node, $post );

		if ( ! is_array( $node ) || empty( $node ) ) {
			return array();
		}

		// Nodes live inside the root @graph, which carries the only @context.
		unset( $node['@context'] );

		$node = $this->remove_empty_values( $node );

		if ( empty( $node['@type'] ) || ! $this->is_valid_node( $node ) ) {
			return array();
		}

		return $node;
	}

	/**
	 * Read a single schema meta field for the post.
	 *
	 * @param \WP_Post $post  Post.
	 * @param string   $field Field name (meta key suffix).
	 * @return string Trimmed value, or empty string.
	 */
	protected function get_field( \WP_Post $post, string $field ): string {
		$value = get_post_meta( $post->ID, $this->get_meta_key( $field ), true );

		if ( is_array( $value ) || is_object( $value ) ) {
			return '';
		}

		return trim( wp_strip_all_tags( (string) $value ) );
	}

	/**
	 * Full meta key for a field, e.g. `_saman_seo_course_provider`.
	 *
	 * @param string $field Field name.
	 * @return string
	 */
	protected function get_meta_key( string $field ): string {
		return '_saman_seo_' . $this->get_schema_slug() . '_' . $field;
	}

	/**
	 * URL the node describes: the per-post canonical override when set,
	 * otherwise the permalink. Mirrors Schema_Context so @id values share a
	 * base with the WebPage node.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	protected function get_url( \WP_Post $post ): string {
		$meta = get_post_meta( $post->ID, Post_Meta::META_KEY, true );

		if ( is_array( $meta ) && ! empty( $meta['canonical'] ) ) {
			return esc_url_raw( $meta['canonical'] );
		}

		return (string) get_permalink( $post );
	}

	/**
	 * Stable @id for the node, e.g. https://example.org/intro-to-php/#course.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	protected function get_node_id( \WP_Post $post ): string {
		return Schema_IDs::fragment( $this->get_url( $post ), str_replace( '_', '', $this->get_schema_slug() ) );
	}

	/**
	 * Description: the post's SEO description when set, else its excerpt.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	protected function get_description( \WP_Post $post ): string {
		$meta = get_post_meta( $post->ID, Post_Meta::META_KEY, true );

		if ( is_array( $meta ) && ! empty( $meta['description'] ) ) {
			return trim( wp_strip_all_tags( (string) $meta['description'] ) );
		}

		return trim( wp_strip_all_tags( (string) get_the_excerpt( $post ) ) );
	}

	/**
	 * Featured image URL, if the post has one.
	 *
	 * @param \WP_Post $post Post.
	 * @return string
	 */
	protected function get_image( \WP_Post $post ): string {
		$url = get_the_post_thumbnail_url( $post, 'full' );

		return $url ? (string) $url : '';
	}

	/**
	 * Reference to the site's Organization node, when the site is
	 * configured as an organization (and so that node is in the graph).
	 *
	 * @return array Empty when the site represents a person.
	 */
	protected function get_site_organization(): array {
		if ( 'organization' !== get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' ) ) {
			return array();
		}

		$name = get_option( 'SAMAN_SEO_homepage_organization_name', '' );
		if ( empty( $name ) ) {
			$name = get_bloginfo( 'name' );
		}

		if ( empty( $name ) ) {
			return array();
		}

		return array(
			'@type' => 'Organization',
			'@id'   => Schema_IDs::organization(),
			'name'  => $name,
		);
	}

	/**
	 * Build a typed entity (Person, Organization, ...) from a name meta
	 * field and an optional URL field.
	 *
	 * @param \WP_Post $post       Post.
	 * @param string   $type       Schema type.
	 * @param string   $name_field Field holding the name.
	 * @param string   $url_field  Optional field holding a URL.
	 * @return array Empty when no name is stored.
	 */
	protected function get_entity( \WP_Post $post, string $type, string $name_field, string $url_field = '' ): array {
		$name = $this->get_field( $post, $name_field );

		if ( '' === $name ) {
			return array();
		}

		$entity = array(
			'@type' => $type,
			'name'  => $name,
		);

		if ( '' !== $url_field ) {
			$url = esc_url_raw( $this->get_field( $post, $url_field ) );
			if ( '' !== $url ) {
				$entity['sameAs'] = $url;
			}
		}

		return $entity;
	}

	/**
	 * Recursively drop null, empty-string and empty-array values.
	 *
	 * @param array $data Data.
	 * @return array
	 */
	protected function remove_empty_values( array $data ): array {
		foreach ( $data as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = $this->remove_empty_values( $value );
			} elseif ( is_string( $value ) ) {
				$value = trim( $value );
			}

			if ( null === $value || '' === $value || array() === $value ) {
				unset( $data[ $key ] );
				continue;
			}

			// A nested entity reduced to a bare @type carries no data.
			if ( is_array( $value ) && array( '@type' ) === array_keys( $value ) ) {
				unset( $data[ $key ] );
				continue;
			}

			$data[ $key ] = $value;
		}

		// Re-index lists so they stay JSON arrays after removals.
		if ( $this->is_list( $data ) ) {
			$data = array_values( $data );
		}

		return $data;
	}

	/**
	 * Whether an array has only integer keys (possibly with gaps).
	 *
	 * @param array $data Data.
	 * @return bool
	 */
	private function is_list( array $data ): bool {
		if ( empty( $data ) ) {
			return false;
		}

		foreach ( array_keys( $data ) as $key ) {
			if ( ! is_int( $key ) ) {
				return false;
			}
		}

		return true;
	}
}
