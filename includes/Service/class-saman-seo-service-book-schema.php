<?php
/**
 * Book Schema service for book schema optimization.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Book Schema controller.
 */
class Book_Schema {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_book_schema_to_graph' ], 20, 2 );
	}

	/**
	 * Add Book schema to the JSON-LD graph.
	 *
	 * @param array    $graph The existing JSON-LD graph.
	 * @param \WP_Post $post  The current post object.
	 * @return array The modified JSON-LD graph.
	 */
	public function add_book_schema_to_graph( $graph, $post ) {
		// This is just a placeholder for the logic to identify a book post.
		if ( 'book' !== get_post_type( $post->ID ) ) {
			return $graph;
		}

		$schema = $this->build_schema( $post );

		if ( ! empty( $schema ) ) {
			$graph[] = $schema;
		}

		return $graph;
	}

	/**
	 * Build Book schema.
	 *
	 * @param \WP_Post $post The current post object.
	 * @return array|null
	 */
	private function build_schema( $post ) {
		$book_name = get_the_title( $post );
		$author_name = get_the_author_meta( 'display_name', $post->post_author );

		// In a real implementation, these would come from post meta.
		$isbn = '978-3-16-148410-0';
		$book_edition = '1st Edition';

		if ( empty( $book_name ) ) {
			return null;
		}

		$schema = [
			'@context'    => 'https://schema.org',
			'@type'       => 'Book',
			'name'        => $book_name,
			'author'      => [
				'@type' => 'Person',
				'name'  => $author_name,
			],
			'isbn'        => $isbn,
			'bookEdition' => $book_edition,
		];

		return $schema;
	}
}
