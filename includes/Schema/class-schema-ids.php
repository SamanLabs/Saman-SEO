<?php
/**
 * Schema IDs helper class.
 *
 * Provides consistent URL#fragment identifiers for JSON-LD @id values.
 * Following Yoast's Schema_IDs pattern for predictable cross-references.
 *
 * @package Saman\SEO\Schema
 * @since   1.0.0
 */

namespace Saman\SEO\Schema;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static helper class for generating schema @id values.
 *
 * All schema @id values should use these methods to ensure consistency
 * across the graph. Fragment identifiers follow the pattern URL#type.
 */
class Schema_IDs {

	/**
	 * Get the WebSite schema @id.
	 *
	 * @return string URL#website identifier.
	 */
	public static function website(): string {
		return home_url( '/' ) . '#website';
	}

	/**
	 * Get the Organization schema @id.
	 *
	 * @return string URL#organization identifier.
	 */
	public static function organization(): string {
		return home_url( '/' ) . '#organization';
	}

	/**
	 * Get the Person schema @id.
	 *
	 * @return string URL#person identifier.
	 */
	public static function person(): string {
		return home_url( '/' ) . '#person';
	}

	/**
	 * Get the WebPage schema @id for a specific URL.
	 *
	 * @param string $url The canonical URL of the page.
	 * @return string URL#webpage identifier.
	 */
	public static function webpage( string $url ): string {
		return $url . '#webpage';
	}

	/**
	 * Get the Article schema @id for a specific URL.
	 *
	 * @param string $url The canonical URL of the article.
	 * @return string URL#article identifier.
	 */
	public static function article( string $url ): string {
		return $url . '#article';
	}

	/**
	 * Get the BreadcrumbList schema @id for a specific URL.
	 *
	 * @param string $url The canonical URL of the page.
	 * @return string URL#breadcrumb identifier.
	 */
	public static function breadcrumb( string $url ): string {
		return $url . '#breadcrumb';
	}

	/**
	 * Get the Author schema @id for a specific user.
	 *
	 * @param int $user_id The WordPress user ID.
	 * @return string Author URL#author identifier.
	 */
	public static function author( int $user_id ): string {
		return get_author_posts_url( $user_id ) . '#author';
	}
}
