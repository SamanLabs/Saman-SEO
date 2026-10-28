<?php
/**
 * Book Schema service for book schema optimization.
 *
 * Emits a Book node for posts of the `book` post type. Optional fields are
 * read from post meta and omitted when empty:
 *
 * - `_saman_seo_book_author`     Author name (the book's author, not the post author).
 * - `_saman_seo_book_author_url` Author URL (sameAs).
 * - `_saman_seo_book_isbn`       ISBN.
 * - `_saman_seo_book_edition`    Edition (bookEdition).
 * - `_saman_seo_book_pages`      Number of pages.
 * - `_saman_seo_book_publisher`  Publisher name.
 *
 * Use the `saman_seo_schema_book` filter to supply or change data.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Book Schema controller.
 */
class Book_Schema extends Abstract_Post_Type_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'schema_book' ) ) {
			return;
		}

		add_filter( 'SAMAN_SEO_jsonld_graph', array( $this, 'add_book_schema_to_graph' ), 20, 2 );
	}

	/**
	 * Add Book schema to the JSON-LD graph.
	 *
	 * @param array         $graph The existing JSON-LD graph.
	 * @param \WP_Post|null $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_book_schema_to_graph( $graph, $post = null ) {
		return $this->add_node_to_graph( $graph, $post );
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_post_type(): string {
		return 'book';
	}

	/**
	 * {@inheritDoc}
	 */
	protected function get_schema_slug(): string {
		return 'book';
	}

	/**
	 * Build Book schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array
	 */
	protected function build_node( \WP_Post $post ): array {
		$pages = absint( $this->get_field( $post, 'pages' ) );

		return array(
			'@type'         => 'Book',
			'@id'           => $this->get_node_id( $post ),
			'name'          => wp_strip_all_tags( (string) get_the_title( $post ) ),
			'description'   => $this->get_description( $post ),
			'url'           => $this->get_url( $post ),
			'image'         => $this->get_image( $post ),
			'author'        => $this->get_entity( $post, 'Person', 'author', 'author_url' ),
			'publisher'     => $this->get_entity( $post, 'Organization', 'publisher' ),
			'isbn'          => $this->get_field( $post, 'isbn' ),
			'bookEdition'   => $this->get_field( $post, 'edition' ),
			'numberOfPages' => $pages > 0 ? $pages : null,
		);
	}
}
