<?php
/**
 * JSON-LD payload builder.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

use function Saman\SEO\Helpers\breadcrumbs;

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
		add_filter( 'SAMAN_SEO_jsonld', [ $this, 'build_payload' ], 10, 2 );
	}

	/**
	 * Build JSON-LD graph.
	 *
	 * @param array        $payload Existing payload.
	 * @param \WP_Post|null $post   Post.
	 *
	 * @return array
	 */
	public function build_payload( $payload, $post ) {
		$graph   = [];
		$site_id = home_url( '/' ) . '#website';

		$graph[] = [
			'@type' => 'WebSite',
			'@id'   => $site_id,
			'url'   => home_url( '/' ),
			'name'  => get_bloginfo( 'name' ),
			'description' => get_option( 'SAMAN_SEO_default_meta_description', get_bloginfo( 'description' ) ),
			'publisher'   => [
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'logo'  => [
					'@type' => 'ImageObject',
					'url'   => get_site_icon_url(),
				],
			],
		];

		if ( $post ) {
			$url    = get_permalink( $post );
			$post_id = $url . '#webpage';

			$webpage_schema = [
				'@type'         => 'WebPage',
				'@id'           => $post_id,
				'url'           => $url,
				'name'          => get_the_title( $post ),
				'datePublished' => get_the_date( DATE_W3C, $post ),
				'dateModified'  => get_the_modified_date( DATE_W3C, $post ),
				'isPartOf'      => [ '@id' => $site_id ],
				'breadcrumb'    => [ '@id' => $url . '#breadcrumb' ],
				'primaryImageOfPage' => [
					'@type' => 'ImageObject',
					'url'   => get_the_post_thumbnail_url( $post, 'full' ) ?: get_option( 'SAMAN_SEO_default_og_image' ),
				],
			];

			$graph[] = apply_filters( 'SAMAN_SEO_schema_webpage', $webpage_schema, $post );

			// Determine schema type - check post type settings for custom type.
			$schema_type   = 'Article';
			$post_type     = get_post_type( $post );
			$type_settings = get_option( 'SAMAN_SEO_post_type_seo_settings', [] );

			if ( isset( $type_settings[ $post_type ]['schema_type'] ) && ! empty( $type_settings[ $post_type ]['schema_type'] ) ) {
				$schema_type = $type_settings[ $post_type ]['schema_type'];
			}

			// Output article-type schema for posts and related content types.
			if ( in_array( $post_type, [ 'post', 'article' ], true ) || in_array( $schema_type, [ 'Article', 'BlogPosting', 'NewsArticle' ], true ) ) {
				$article_schema = [
					'@type'        => $schema_type,
					'@id'          => $url . '#article',
					'headline'     => get_the_title( $post ),
					'author'       => [
						'@type' => 'Person',
						'name'  => get_the_author_meta( 'display_name', $post->post_author ),
					],
					'image'        => [
						get_the_post_thumbnail_url( $post, 'full' ) ?: get_option( 'SAMAN_SEO_default_og_image' ),
					],
					'datePublished' => get_the_date( DATE_W3C, $post ),
					'dateModified'  => get_the_modified_date( DATE_W3C, $post ),
					'isPartOf'      => [ '@id' => $post_id ],
				];

				// Add additional fields for NewsArticle.
				if ( 'NewsArticle' === $schema_type ) {
					$article_schema['mainEntityOfPage'] = [
						'@type' => 'WebPage',
						'@id'   => $url,
					];
					$article_schema['publisher'] = [
						'@type' => 'Organization',
						'name'  => get_bloginfo( 'name' ),
						'logo'  => [
							'@type' => 'ImageObject',
							'url'   => get_site_icon_url(),
						],
					];
					// Word count for NewsArticle.
					$article_schema['wordCount'] = str_word_count( wp_strip_all_tags( $post->post_content ) );
				}

				$graph[] = apply_filters( 'SAMAN_SEO_schema_article', $article_schema, $post );
			}

			$graph[] = $this->breadcrumb_ld( $post );
		}

		return [
			'@context' => 'https://schema.org',
			'@graph'   => apply_filters( 'SAMAN_SEO_jsonld_graph', $graph ),
		];
	}

	/**
	 * Build breadcrumb list.
	 *
	 * @param \WP_Post $post Post obj.
	 *
	 * @return array
	 */
	private function breadcrumb_ld( $post ) {
		$crumbs = [];
		$rank   = 1;

		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank++,
			'name'     => get_bloginfo( 'name' ),
			'item'     => home_url( '/' ),
		];

		$ancestors = array_reverse( get_post_ancestors( $post ) );
		foreach ( $ancestors as $ancestor_id ) {
			$crumbs[] = [
				'@type'    => 'ListItem',
				'position' => $rank++,
				'name'     => get_the_title( $ancestor_id ),
				'item'     => get_permalink( $ancestor_id ),
			];
		}

		$crumbs[] = [
			'@type'    => 'ListItem',
			'position' => $rank,
			'name'     => get_the_title( $post ),
			'item'     => get_permalink( $post ),
		];

		return [
			'@type'    => 'BreadcrumbList',
			'@id'      => get_permalink( $post ) . '#breadcrumb',
			'itemListElement' => $crumbs,
		];
	}
}
