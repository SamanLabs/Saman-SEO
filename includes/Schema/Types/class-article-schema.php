<?php
/**
 * Article Schema class.
 *
 * Generates Article schema for posts with full author Person object,
 * wordCount, articleBody excerpt, and publisher reference.
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
 * Article schema type.
 *
 * Outputs Article structured data with headline, dates, author (full Person),
 * publisher reference, wordCount, articleBody excerpt, and image.
 *
 * This is the base class for all content schema types. BlogPosting and
 * NewsArticle extend this class and override get_type() to return their
 * specific schema type.
 */
class Article_Schema extends Abstract_Schema {

	/**
	 * Get the schema @type value.
	 *
	 * @return string The @type value.
	 */
	public function get_type() {
		return 'Article';
	}

	/**
	 * Determine if Article schema should be output.
	 *
	 * Only outputs when we have a post context AND the schema_type
	 * is set to 'Article'.
	 *
	 * @return bool True if schema should be included.
	 */
	public function is_needed(): bool {
		return $this->context->post instanceof \WP_Post
			&& 'Article' === $this->context->schema_type;
	}

	/**
	 * Generate Article schema.
	 *
	 * Includes: @type, @id, headline, datePublished, dateModified,
	 * mainEntityOfPage, author (full Person), publisher reference,
	 * wordCount, articleBody, and image.
	 *
	 * @return array Schema.org Article structured data.
	 */
	public function generate(): array {
		$post = $this->context->post;

		$schema = [
			'@type'            => $this->get_type(),
			'@id'              => Schema_IDs::article( $this->context->canonical ),
			'headline'         => get_the_title( $post ),
			'datePublished'    => get_the_date( DATE_W3C, $post ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post ),
			'mainEntityOfPage' => [
				'@id' => Schema_IDs::webpage( $this->context->canonical ),
			],
		];

		// Add author (full Person object).
		$author = $this->get_author();
		if ( ! empty( $author ) ) {
			$schema['author'] = $author;
		}

		// Add publisher reference.
		$schema['publisher'] = $this->get_publisher_reference();

		// Add wordCount.
		$word_count = $this->get_word_count();
		if ( $word_count > 0 ) {
			$schema['wordCount'] = $word_count;
		}

		// Add articleBody excerpt.
		$article_body = $this->get_article_body_excerpt();
		if ( ! empty( $article_body ) ) {
			$schema['articleBody'] = $article_body;
		}

		// Add image.
		$images = $this->get_images();
		if ( ! empty( $images ) ) {
			$schema['image'] = $images;
		}

		return $schema;
	}

	/**
	 * Build full Person object for the author.
	 *
	 * Google recommends full Person objects for author (not just name strings)
	 * for rich results eligibility.
	 *
	 * @return array Person schema for the author, or empty array if no author.
	 */
	protected function get_author(): array {
		$author_id = (int) $this->context->post->post_author;
		if ( ! $author_id ) {
			return [];
		}

		$author = [
			'@type' => 'Person',
			'@id'   => Schema_IDs::author( $author_id ),
			'name'  => get_the_author_meta( 'display_name', $author_id ),
		];

		// URL: prefer user_url, fallback to author posts URL.
		$url = get_the_author_meta( 'user_url', $author_id );
		if ( empty( $url ) ) {
			$url = get_author_posts_url( $author_id );
		}
		$author['url'] = $url;

		// Optional image from Gravatar.
		$avatar = get_avatar_url( $author_id, [ 'size' => 96 ] );
		if ( $avatar ) {
			$author['image'] = $avatar;
		}

		return $author;
	}

	/**
	 * Get publisher with inline name and logo.
	 *
	 * Includes both @id reference (for @graph linking) and inline
	 * name/logo (for validators that don't resolve @id references).
	 *
	 * @return array Publisher object with @id, name, and logo.
	 */
	protected function get_publisher_reference(): array {
		$type = get_option( 'SAMAN_SEO_homepage_knowledge_type', 'organization' );

		if ( 'person' === $type ) {
			$name = get_option( 'SAMAN_SEO_homepage_person_name', '' );
			if ( empty( $name ) ) {
				$name = get_bloginfo( 'name' );
			}
			$publisher = [
				'@type' => 'Person',
				'@id'   => Schema_IDs::person(),
				'name'  => $name,
			];
			$image = get_option( 'SAMAN_SEO_homepage_person_image', '' );
			if ( ! empty( $image ) ) {
				$publisher['logo'] = $image;
			}
			return $publisher;
		}

		$name = get_option( 'SAMAN_SEO_homepage_organization_name', '' );
		if ( empty( $name ) ) {
			$name = get_bloginfo( 'name' );
		}
		$publisher = [
			'@type' => 'Organization',
			'@id'   => Schema_IDs::organization(),
			'name'  => $name,
		];
		$logo = get_option( 'SAMAN_SEO_homepage_organization_logo', '' );
		if ( empty( $logo ) ) {
			$logo = get_site_icon_url();
		}
		if ( ! empty( $logo ) ) {
			$publisher['logo'] = $logo;
		}
		return $publisher;
	}

	/**
	 * Calculate word count from post content.
	 *
	 * Strips shortcodes and HTML tags before counting words.
	 *
	 * @return int Word count of the post content.
	 */
	protected function get_word_count(): int {
		$content = $this->context->post->post_content;
		$content = strip_shortcodes( $content );
		$content = wp_strip_all_tags( $content );
		return str_word_count( $content );
	}

	/**
	 * Get article body excerpt.
	 *
	 * Processes shortcodes and strips HTML, then trims to ~150 words.
	 *
	 * @return string Article body excerpt.
	 */
	protected function get_article_body_excerpt(): string {
		$content = $this->context->post->post_content;
		$content = do_shortcode( $content );
		$content = wp_strip_all_tags( $content );
		return wp_trim_words( $content, 150, '...' );
	}

	/**
	 * Get featured image URL.
	 *
	 * Falls back to default OG image if no featured image is set.
	 * Returns a plain URL string per Google's schema requirements.
	 *
	 * @return string Image URL, or empty string if no image.
	 */
	protected function get_images(): string {
		$image_url = get_the_post_thumbnail_url( $this->context->post, 'full' );
		if ( empty( $image_url ) ) {
			$image_url = get_option( 'SAMAN_SEO_default_og_image', '' );
		}
		if ( empty( $image_url ) ) {
			return '';
		}
		return $image_url;
	}
}
