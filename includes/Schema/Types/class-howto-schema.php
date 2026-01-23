<?php
/**
 * HowTo Schema class.
 *
 * Generates HowTo schema from samanseo/howto Gutenberg blocks.
 * Uses the first HowTo block found in the post.
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
 * HowTo schema implementation.
 *
 * Parses HowTo block from post content and generates HowTo schema
 * with steps, tools, supplies, and timing. Uses first block only
 * (multiple HowTo schemas on one page is semantically unclear).
 */
class HowTo_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The HowTo type.
	 */
	public function get_type() {
		return 'HowTo';
	}

	/**
	 * Determine if this schema should be output.
	 *
	 * Only output if we have a post and it contains HowTo blocks.
	 *
	 * @return bool True if post contains HowTo blocks.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& has_block( 'samanseo/howto', $this->context->post );
	}

	/**
	 * Generate the HowTo schema array.
	 *
	 * @return array HowTo schema or empty array if no valid data.
	 */
	public function generate(): array {
		$howto_data = $this->get_first_howto_block();

		if ( empty( $howto_data ) ) {
			return [];
		}

		$schema = [
			'@type' => $this->get_type(),
			'@id'   => Schema_IDs::howto( $this->context->canonical ),
		];

		// Add name (required).
		$name = trim( wp_strip_all_tags( $howto_data['title'] ?? '' ) );
		if ( ! empty( $name ) ) {
			$schema['name'] = $name;
		}

		// Add description.
		$description = trim( wp_strip_all_tags( $howto_data['description'] ?? '' ) );
		if ( ! empty( $description ) ) {
			$schema['description'] = $description;
		}

		// Add totalTime in ISO 8601 duration format.
		if ( ! empty( $howto_data['totalTime'] ) ) {
			$iso_time = $this->parse_time_to_iso( $howto_data['totalTime'] );
			if ( $iso_time ) {
				$schema['totalTime'] = $iso_time;
			}
		}

		// Add estimatedCost as MonetaryAmount.
		if ( ! empty( $howto_data['estimatedCost'] ) ) {
			$schema['estimatedCost'] = [
				'@type'    => 'MonetaryAmount',
				'currency' => $howto_data['currency'] ?? 'USD',
				'value'    => $howto_data['estimatedCost'],
			];
		}

		// Add tools as HowToTool array.
		$tools = $howto_data['tools'] ?? [];
		if ( ! empty( $tools ) && is_array( $tools ) ) {
			$schema['tool'] = array_map(
				fn( $t ) => [
					'@type' => 'HowToTool',
					'name'  => $t,
				],
				array_filter( $tools )
			);
		}

		// Add supplies as HowToSupply array.
		$supplies = $howto_data['supplies'] ?? [];
		if ( ! empty( $supplies ) && is_array( $supplies ) ) {
			$schema['supply'] = array_map(
				fn( $s ) => [
					'@type' => 'HowToSupply',
					'name'  => $s,
				],
				array_filter( $supplies )
			);
		}

		// Add steps as HowToStep array.
		$steps = $howto_data['steps'] ?? [];
		if ( ! empty( $steps ) ) {
			$built_steps = $this->build_steps( $steps );
			if ( ! empty( $built_steps ) ) {
				$schema['step'] = $built_steps;
			}
		}

		return $schema;
	}

	/**
	 * Get the first HowTo block data from post content.
	 *
	 * Only uses the first HowTo block with showSchema enabled.
	 * Multiple HowTo schemas on one page is semantically unclear.
	 *
	 * @return array|null Block attributes or null if not found.
	 */
	private function get_first_howto_block(): ?array {
		$blocks = parse_blocks( $this->context->post->post_content );
		return $this->find_howto_block( $blocks );
	}

	/**
	 * Recursively find the first HowTo block (handles inner blocks).
	 *
	 * @param array $blocks Blocks to search.
	 * @return array|null Block attributes or null if not found.
	 */
	private function find_howto_block( array $blocks ): ?array {
		foreach ( $blocks as $block ) {
			if ( 'samanseo/howto' === $block['blockName'] ) {
				$attrs = $block['attrs'] ?? [];

				// Respect showSchema toggle.
				if ( ! empty( $attrs['showSchema'] ) ) {
					return $attrs;
				}
			}

			// Check nested blocks.
			if ( ! empty( $block['innerBlocks'] ) ) {
				$found = $this->find_howto_block( $block['innerBlocks'] );
				if ( $found ) {
					return $found;
				}
			}
		}

		return null;
	}

	/**
	 * Build HowToStep array from steps data.
	 *
	 * @param array $steps Steps from block attributes.
	 * @return array Array of HowToStep objects.
	 */
	private function build_steps( array $steps ): array {
		$result   = [];
		$position = 1;

		foreach ( $steps as $step ) {
			$title       = trim( wp_strip_all_tags( $step['title'] ?? '' ) );
			$description = trim( wp_strip_all_tags( $step['description'] ?? '' ) );

			// Skip steps with no content.
			if ( empty( $title ) && empty( $description ) ) {
				continue;
			}

			$step_schema = [
				'@type'    => 'HowToStep',
				'position' => $position,
			];

			if ( ! empty( $title ) ) {
				$step_schema['name'] = $title;
			}

			if ( ! empty( $description ) ) {
				$step_schema['text'] = $description;
			}

			if ( ! empty( $step['image'] ) ) {
				$step_schema['image'] = $step['image'];
			}

			$result[] = $step_schema;
			$position++;
		}

		return $result;
	}

	/**
	 * Parse human-readable time string to ISO 8601 duration.
	 *
	 * Supports formats like "30 minutes", "2 hours", "30 min", "2 h".
	 *
	 * @param string $time_str The time string to parse.
	 * @return string|null ISO 8601 duration (e.g., "PT30M") or null if unparseable.
	 */
	private function parse_time_to_iso( string $time_str ): ?string {
		if ( preg_match( '/(\d+)\s*(min|minute|minutes|m)\b/i', $time_str, $match ) ) {
			return 'PT' . (int) $match[1] . 'M';
		}
		if ( preg_match( '/(\d+)\s*(hour|hours|h)\b/i', $time_str, $match ) ) {
			return 'PT' . (int) $match[1] . 'H';
		}
		return null;
	}
}
