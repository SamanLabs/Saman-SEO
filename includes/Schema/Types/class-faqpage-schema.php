<?php
/**
 * FAQPage Schema class.
 *
 * Generates FAQPage schema from samanseo/faq Gutenberg blocks.
 * Combines questions from all FAQ blocks in the post.
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
 * FAQPage schema implementation.
 *
 * Parses FAQ blocks from post content and generates FAQPage schema
 * with Question/Answer pairs in mainEntity. Respects showSchema
 * attribute on individual blocks.
 */
class FAQPage_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The FAQPage type.
	 */
	public function get_type() {
		return 'FAQPage';
	}

	/**
	 * Determine if this schema should be output.
	 *
	 * Only output if we have a post and it contains FAQ blocks.
	 *
	 * @return bool True if post contains FAQ blocks.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& has_block( 'samanseo/faq', $this->context->post );
	}

	/**
	 * Generate the FAQPage schema array.
	 *
	 * @return array FAQPage schema or empty array if no valid questions.
	 */
	public function generate(): array {
		$questions = $this->collect_questions_from_blocks();

		if ( empty( $questions ) ) {
			return [];
		}

		return [
			'@type'      => $this->get_type(),
			'@id'        => Schema_IDs::faqpage( $this->context->canonical ),
			'mainEntity' => $questions,
		];
	}

	/**
	 * Collect all FAQ questions from FAQ blocks in post content.
	 *
	 * Iterates through all samanseo/faq blocks, respects showSchema
	 * attribute, and combines all valid Q&A pairs.
	 *
	 * @return array Array of Question objects for mainEntity.
	 */
	private function collect_questions_from_blocks(): array {
		$blocks    = parse_blocks( $this->context->post->post_content );
		$questions = [];

		$this->extract_faq_blocks( $blocks, $questions );

		return $questions;
	}

	/**
	 * Recursively extract FAQ blocks (handles inner blocks).
	 *
	 * @param array $blocks    Blocks to search.
	 * @param array $questions Reference to questions array to populate.
	 */
	private function extract_faq_blocks( array $blocks, array &$questions ): void {
		foreach ( $blocks as $block ) {
			if ( 'samanseo/faq' === $block['blockName'] ) {
				$attrs = $block['attrs'] ?? [];

				// Respect showSchema toggle - only include if true.
				if ( empty( $attrs['showSchema'] ) ) {
					continue;
				}

				$faqs = $attrs['faqs'] ?? [];
				foreach ( $faqs as $faq ) {
					$q = trim( wp_strip_all_tags( $faq['question'] ?? '' ) );
					$a = trim( wp_strip_all_tags( $faq['answer'] ?? '' ) );

					if ( ! empty( $q ) && ! empty( $a ) ) {
						$questions[] = [
							'@type'          => 'Question',
							'name'           => $q,
							'acceptedAnswer' => [
								'@type' => 'Answer',
								'text'  => $a,
							],
						];
					}
				}
			}

			// Handle nested blocks (e.g., inside columns, groups).
			if ( ! empty( $block['innerBlocks'] ) ) {
				$this->extract_faq_blocks( $block['innerBlocks'], $questions );
			}
		}
	}
}
