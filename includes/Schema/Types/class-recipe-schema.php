<?php
/**
 * Recipe Schema class.
 *
 * Generates Recipe structured data from the `recipe` key of the plugin's
 * post meta (`_SAMAN_SEO_meta`). Data can be supplied by the editor sidebar,
 * a custom metabox, or programmatically via update_post_meta().
 *
 * @package Saman\SEO\Schema\Types
 * @since   2.1.0
 */

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Recipe schema type.
 *
 * Outputs Recipe structured data with ingredients, HowToStep instructions,
 * timing (ISO 8601 durations), nutrition, yield, cuisine/category, ratings,
 * and author/publisher cross references.
 */
class Recipe_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The Recipe type.
	 */
	public function get_type() {
		return 'Recipe';
	}

	/**
	 * Determine if Recipe schema should be output.
	 *
	 * Requires a post context and a recipe entry with at least a name.
	 *
	 * @return bool True when the post has recipe data.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& ! empty( $this->get_recipe_data()['name'] );
	}

	/**
	 * Generate the Recipe schema array.
	 *
	 * @return array Schema.org Recipe structured data.
	 */
	public function generate(): array {
		$recipe = $this->get_recipe_data();
		$post   = $this->context->post;

		if ( empty( $recipe['name'] ) ) {
			return array();
		}

		$schema = array(
			'@type'            => $this->get_type(),
			'@id'              => Schema_IDs::recipe( $this->context->canonical ),
			'name'             => wp_strip_all_tags( (string) $recipe['name'] ),
			'mainEntityOfPage' => array(
				'@id' => Schema_IDs::webpage( $this->context->canonical ),
			),
		);

		// Description.
		$description = trim( wp_strip_all_tags( (string) ( $recipe['description'] ?? '' ) ) );
		if ( '' !== $description ) {
			$schema['description'] = $description;
		}

		// Image (single URL or list of URLs).
		$images = $this->normalize_images( $recipe['images'] ?? ( isset( $recipe['image'] ) ? $recipe['image'] : '' ) );
		if ( ! empty( $images ) ) {
			$schema['image'] = $images;
		}

		// Author reference (full Person for rich results).
		$author_id = (int) $post->post_author;
		if ( $author_id ) {
			$schema['author'] = array(
				'@type' => 'Person',
				'@id'   => Schema_IDs::author( $author_id ),
				'name'  => get_the_author_meta( 'display_name', $author_id ),
			);
		}

		// Dates.
		$date_published = get_the_date( DATE_W3C, $post );
		if ( $date_published ) {
			$schema['datePublished'] = $date_published;
		}
		$date_modified = get_the_modified_date( DATE_W3C, $post );
		if ( $date_modified ) {
			$schema['dateModified'] = $date_modified;
		}

		// Timing (ISO 8601 durations).
		foreach ( array( 'prepTime', 'cookTime', 'totalTime' ) as $time_key ) {
			$duration = $this->parse_duration( (string) ( $recipe[ $time_key ] ?? '' ) );
			if ( null !== $duration ) {
				$schema[ $time_key ] = $duration;
			}
		}

		// Yield, keywords, taxonomy-ish fields.
		foreach ( array( 'recipeYield', 'keywords', 'recipeCategory', 'recipeCuisine' ) as $text_key ) {
			$value = trim( wp_strip_all_tags( (string) ( $recipe[ $text_key ] ?? '' ) ) );
			if ( '' !== $value ) {
				$schema[ $text_key ] = $value;
			}
		}

		// Nutrition.
		$calories = trim( (string) ( $recipe['nutrition_calories'] ?? '' ) );
		if ( '' !== $calories ) {
			$schema['nutrition'] = array(
				'@type'    => 'NutritionInformation',
				'calories' => $calories,
			);
		}

		// Ingredients: plain text list.
		$ingredients = $this->sanitize_string_list( $recipe['recipeIngredient'] ?? array() );
		if ( ! empty( $ingredients ) ) {
			$schema['recipeIngredient'] = $ingredients;
		}

		// Instructions: HowToStep list with positions.
		$instructions = $this->build_instructions( $recipe['recipeInstructions'] ?? array() );
		if ( ! empty( $instructions ) ) {
			$schema['recipeInstructions'] = $instructions;
		}

		// Aggregate rating.
		$rating = $this->build_rating( $recipe );
		if ( ! empty( $rating ) ) {
			$schema['aggregateRating'] = $rating;
		}

		return $this->apply_fields_filter( $schema );
	}

	/**
	 * Fetch the raw recipe data from post meta.
	 *
	 * @return array<string,mixed>
	 */
	private function get_recipe_data(): array {
		$data = $this->context->meta['recipe'] ?? array();

		return is_array( $data ) ? $data : array();
	}

	/**
	 * Normalize an image value (string|array) into a URL list.
	 *
	 * @param mixed $value Image value.
	 * @return array<int,string>
	 */
	private function normalize_images( $value ): array {
		$values = is_array( $value ) ? $value : array_filter( array( $value ) );

		$urls = array();
		foreach ( $values as $entry ) {
			$url = esc_url_raw( (string) $entry );
			if ( '' !== $url ) {
				$urls[] = $url;
			}
		}

		return $urls;
	}

	/**
	 * Sanitize a list of free-text values.
	 *
	 * @param mixed $values Raw list.
	 * @return array<int,string>
	 */
	private function sanitize_string_list( $values ): array {
		if ( ! is_array( $values ) ) {
			return array();
		}

		$clean = array();

		foreach ( $values as $item ) {
			$text = trim( wp_strip_all_tags( (string) $item ) );

			if ( '' !== $text ) {
				$clean[] = $text;
			}
		}

		return $clean;
	}

	/**
	 * Build HowToStep instruction objects.
	 *
	 * Accepts either an array of strings ("Whisk the eggs") or arrays with
	 * name/text keys.
	 *
	 * @param mixed $steps Raw steps value.
	 * @return array<int,array>
	 */
	private function build_instructions( $steps ): array {
		if ( ! is_array( $steps ) ) {
			return array();
		}

		$result   = array();
		$position = 1;

		foreach ( $steps as $step ) {
			if ( is_array( $step ) ) {
				$name = trim( wp_strip_all_tags( (string) ( $step['name'] ?? '' ) ) );
				$text = trim( wp_strip_all_tags( (string) ( $step['text'] ?? '' ) ) );
			} else {
				$name = '';
				$text = trim( wp_strip_all_tags( (string) $step ) );
			}

			if ( '' === $name && '' === $text ) {
				continue;
			}

			$step_schema = array(
				'@type'    => 'HowToStep',
				'position' => $position++,
			);

			if ( '' !== $name ) {
				$step_schema['name'] = $name;
			}
			if ( '' !== $text ) {
				$step_schema['text'] = $text;
			}

			$result[] = $step_schema;
		}

		return $result;
	}

	/**
	 * Build the aggregateRating block when rating data exists.
	 *
	 * @param array $recipe Raw recipe data.
	 * @return array Empty when incomplete.
	 */
	private function build_rating( array $recipe ): array {
		$rating_value = isset( $recipe['rating_value'] ) ? (float) $recipe['rating_value'] : 0.0;
		$rating_count = isset( $recipe['rating_count'] ) ? absint( $recipe['rating_count'] ) : 0;

		if ( $rating_value <= 0 || $rating_count <= 0 ) {
			return array();
		}

		return array(
			'@type'       => 'AggregateRating',
			'ratingValue' => round( $rating_value, 1 ),
			'ratingCount' => $rating_count,
		);
	}

	/**
	 * Parse human-readable or ISO 8601 durations into ISO 8601 format.
	 *
	 * Supported inputs:
	 * - Already valid ISO durations: "PT30M", "PT1H30M".
	 * - "45 min", "45 minutes".
	 * - "2 hours", "2h".
	 * - Combined: "1 hour 30 minutes", "1h 30m".
	 *
	 * @param string $value Raw duration value.
	 * @return string|null ISO 8601 duration or null when unparseable/empty.
	 */
	public static function parse_duration( string $value ): ?string {
		$value = strtolower( trim( $value ) );

		if ( '' === $value ) {
			return null;
		}

		// Pass through already-valid ISO 8601 durations.
		if ( preg_match( '/^pt(?=\d)(?:(\d+)h)?(?:(\d+)m)?(?:([\d.]+)s)?$/', $value, $iso ) ) {
			$has_component = ! empty( $iso[1] ) || ! empty( $iso[2] ) || ! empty( $iso[3] );

			return $has_component ? strtoupper( $value ) : null;
		}

		$hours   = 0.0;
		$minutes = 0;

		if ( preg_match( '/(\d+(?:[.,]\d+)?)\s*(?:hours?|hrs?|h)\b/', $value, $match ) ) {
			$hours = (float) str_replace( ',', '.', $match[1] );
		}

		if ( preg_match( '/(\d+)\s*(?:minutes?|mins?|m)\b/', $value, $match ) ) {
			$minutes = (int) $match[1];
		}

		// Bare number means minutes by convention.
		if ( 0.0 === $hours && 0 === $minutes && preg_match( '/^\d+$/', $value ) ) {
			$minutes = (int) $value;
		}

		// Normalize overflow (e.g. "0.5 hours").
		$total_minutes = (int) round( $hours * 60 + $minutes );

		if ( $total_minutes <= 0 ) {
			return null;
		}

		$iso_hours   = intdiv( $total_minutes, 60 );
		$iso_minutes = $total_minutes % 60;

		$duration = 'PT';

		if ( $iso_hours > 0 ) {
			$duration .= $iso_hours . 'H';
		}
		if ( $iso_minutes > 0 ) {
			$duration .= $iso_minutes . 'M';
		}

		return $duration;
	}
}
