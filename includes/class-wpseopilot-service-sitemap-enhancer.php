<?php
/**
 * Adds enrichment to WP core sitemaps plus optional custom sitemap.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap enhancements.
 */
class Sitemap_Enhancer {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( '1' !== get_option( 'wpseopilot_enable_sitemap_enhancer', '0' ) ) {
			return;
		}

		if ( ! apply_filters( 'wpseopilot_feature_toggle', true, 'sitemaps' ) ) {
			return;
		}

		add_filter( 'wp_sitemaps_posts_entry', [ $this, 'include_media_fields' ], 10, 3 );
		add_filter( 'wp_sitemaps_additional_namespaces', [ $this, 'add_namespaces' ] );
		add_action( 'init', [ $this, 'register_custom_sitemap' ] );
		add_action( 'template_redirect', [ $this, 'render_custom_sitemap' ] );
	}

	/**
	 * Register extra namespaces for news/video.
	 *
	 * @param array $namespaces Existing.
	 *
	 * @return array
	 */
	public function add_namespaces( $namespaces ) {
		$namespaces['news']  = 'http://www.google.com/schemas/sitemap-news/0.9';
		$namespaces['video'] = 'http://www.google.com/schemas/sitemap-video/1.1';

		return $namespaces;
	}

	/**
	 * Append changefreq, priority, and image nodes.
	 *
	 * @param array $entry Sitemap entry.
	 * @param int   $post_id Post ID.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public function include_media_fields( $entry, $post_id, $post_type ) {
		$entry['priority']   = 0.7;
		$entry['changefreq'] = 'weekly';

		if ( has_post_thumbnail( $post_id ) ) {
			$entry['image:image'] = [
				'image:loc'     => wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' ),
				'image:caption' => get_the_title( $post_id ),
			];
		}

		if ( 'post' === $post_type ) {
			$entry['news:news'] = [
				'news:publication'      => [
					'news:name'     => get_bloginfo( 'name' ),
					'news:language' => get_locale(),
				],
				'news:publication_date' => get_post_time( DATE_W3C, true, $post_id ),
				'news:title'            => get_the_title( $post_id ),
			];
		}

		$first_video = $this->detect_video( get_post_field( 'post_content', $post_id ) );
		if ( $first_video ) {
			$entry['video:video'] = [
				'video:content_loc' => esc_url_raw( $first_video ),
				'video:title'       => get_the_title( $post_id ),
			];
		}

		return apply_filters( 'wpseopilot_sitemap_entry', $entry, $post_id, $post_type );
	}

	/**
	 * Register pretty URL for custom sitemap.
	 *
	 * @return void
	 */
	public function register_custom_sitemap() {
		add_rewrite_rule( '^wpseopilot-sitemap\.xml$', 'index.php?wpseopilot_sitemap=1', 'top' );
		add_rewrite_tag( '%wpseopilot_sitemap%', '1' );
	}

	/**
	 * Render custom sitemap when requested.
	 *
	 * @return void
	 */
	public function render_custom_sitemap() {
		if ( ! get_query_var( 'wpseopilot_sitemap' ) ) {
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		$items = apply_filters( 'wpseopilot_custom_sitemap_items', $this->default_items() );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		foreach ( $items as $item ) {
			printf(
				'<url><loc>%s</loc><lastmod>%s</lastmod><priority>%s</priority></url>',
				esc_url( $item['loc'] ),
				esc_html( $item['lastmod'] ),
				esc_html( $item['priority'] )
			);
		}
		echo '</urlset>';
		exit;
	}

	/**
	 * Provide default urls.
	 *
	 * @return array
	 */
	private function default_items() {
		$posts = get_posts(
			[
				'post_type'      => 'any',
				'post_status'    => 'publish',
				'posts_per_page' => 100,
			]
		);

		$data = [];
		foreach ( $posts as $post ) {
			$data[] = [
				'loc'      => get_permalink( $post ),
				'lastmod'  => get_post_modified_time( DATE_W3C, true, $post ),
				'priority' => '0.5',
			];
		}

		return $data;
	}

	/**
	 * Extract first video URL from content.
	 *
	 * @param string $content
	 * @return string
	 */
	private function detect_video( $content ) {
		if ( preg_match( '#https?://[^\s"]+\.(mp4|mov|webm)#i', $content, $matches ) ) {
			return $matches[0];
		}

		return '';
	}
}
