<?php
/**
 * Adds enrichment to WP core sitemaps plus optional custom sitemap.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Sitemap enhancements.
 */
class Sitemap_Enhancer {

	/**
	 * Internal flag allowing temporary access to WP core sitemaps.
	 *
	 * @var bool
	 */
	private $allow_core_sitemaps = false;

	/**
	 * Cached instance of the WP sitemaps server.
	 *
	 * @var \WP_Sitemaps|null
	 */
	private $sitemap_server = null;

	/**
	 * Cached map of sitemap groups (post types, taxonomies, authors).
	 *
	 * @var array<int,array<string,mixed>>
	 */
	private $sitemap_map = [];

	/**
	 * Cache for generated sitemap page URL lists.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	private $sitemap_page_cache = [];

	/**
	 * Cached max URLs per sitemap page.
	 *
	 * @var int|null
	 */
	private $max_urls_per_page = null;

	/**
	 * Default number of URLs per sitemap page.
	 *
	 * @var int
	 */
	private $default_max_urls_per_page = 1000;

	/**
	 * Cached item counts per sitemap group.
	 *
	 * @var array<string,int>
	 */
	private $group_item_counts = [];

	/**
	 * Default excluded term slugs per taxonomy.
	 *
	 * @var array<string,array<int,string>>
	 */
	private $default_excluded_term_slugs = [
		'category' => [ 'uncategorized' ],
	];

	/**
	 * Cached excluded term IDs per taxonomy.
	 *
	 * @var array<string,array<int,int>>
	 */
	private $excluded_term_ids = [];

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'sitemap' ) ) {
			return;
		}

		if ( ! apply_filters( 'SAMAN_SEO_feature_toggle', true, 'sitemaps' ) ) {
			return;
		}

		add_filter( 'wp_sitemaps_posts_entry', [ $this, 'include_media_fields' ], 10, 3 );
		add_filter( 'wp_sitemaps_additional_namespaces', [ $this, 'add_namespaces' ] );
		add_filter( 'wp_sitemaps_max_urls', [ $this, 'limit_sitemap_page_size' ] );
		add_filter( 'wp_sitemaps_posts_query_args', [ $this, 'filter_posts_query_args' ], 10, 2 );
		add_filter( 'wp_sitemaps_taxonomies_query_args', [ $this, 'filter_taxonomy_query_args' ], 10, 2 );
		add_filter( 'wp_sitemaps_enabled', [ $this, 'disable_core_sitemaps' ] );
		add_filter( 'wp_sitemaps_stylesheet_url', [ $this, 'filter_stylesheet_url' ] );
		add_filter( 'wp_sitemaps_stylesheet_index_url', [ $this, 'filter_stylesheet_url' ] );
		add_action( 'init', [ $this, 'register_custom_sitemap' ] );
		add_action( 'template_redirect', [ $this, 'render_custom_sitemap' ], 0 );
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

		$exclude_images = get_option( 'SAMAN_SEO_sitemap_exclude_images', '0' );

		if ( '1' !== $exclude_images ) {
			$content = get_post_field( 'post_content', $post_id );
			$images  = $this->collect_post_images( $post_id, $content );

			if ( ! empty( $images ) ) {
				$entry['image:image'] = $images;
			}
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

		return apply_filters( 'SAMAN_SEO_sitemap_entry', $entry, $post_id, $post_type );
	}

	/**
	 * Collect sitemap-ready image entries for a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Optional post content to parse.
	 * @return array<int,array<string,string>>
	 */
	private function collect_post_images( $post_id, $content = '' ) {
		$images    = [];
		$seen_urls = [];

		$add_image = static function ( $url, $caption = '' ) use ( &$images, &$seen_urls ) {
			$url = esc_url_raw( $url );

			if ( empty( $url ) ) {
				return;
			}

			$key = md5( $url );

			if ( isset( $seen_urls[ $key ] ) ) {
				return;
			}

			$seen_urls[ $key ] = true;

			$image_entry = [
				'image:loc' => $url,
			];

			if ( $caption ) {
				$image_entry['image:caption'] = wp_strip_all_tags( $caption );
			}

			$images[] = $image_entry;
		};

		if ( has_post_thumbnail( $post_id ) ) {
			$add_image(
				wp_get_attachment_image_url( get_post_thumbnail_id( $post_id ), 'full' ),
				get_the_title( $post_id )
			);
		}

		$attachments = get_attached_media( 'image', $post_id );

		if ( ! empty( $attachments ) ) {
			foreach ( $attachments as $attachment ) {
				$caption = $attachment->post_excerpt ?: $attachment->post_title;
				$add_image( wp_get_attachment_url( $attachment->ID ), $caption );
			}
		}

		if ( '' === $content ) {
			$content = get_post_field( 'post_content', $post_id );
		}

		if ( $content && preg_match_all( '#<img[^>]+src=["\']([^"\']+)["\'][^>]*>#i', $content, $matches ) ) {
			foreach ( $matches[1] as $src ) {
				$add_image( $src );
			}
		}

		/**
		 * Filter the images included for a post's sitemap entry.
		 *
		 * @param array<int,array<string,string>> $images  Image entries.
		 * @param int                             $post_id Post ID.
		 */
		return apply_filters( 'SAMAN_SEO_sitemap_images', $images, $post_id );
	}

	/**
	 * Reduce sitemap page size to keep large indexes paginated.
	 *
	 * @param int $max_urls Core-provided limit.
	 *
	 * @return int
	 */
	public function limit_sitemap_page_size( $max_urls ) {
		return $this->get_max_urls_per_page( $max_urls );
	}

	/**
	 * Exclude unwanted taxonomy terms from sitemap queries.
	 *
	 * @param array  $args     WP_Term_Query arguments.
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return array
	 */
	public function filter_taxonomy_query_args( $args, $taxonomy ) {
		$exclude_ids = $this->resolve_excluded_term_ids( $taxonomy );

		if ( empty( $exclude_ids ) ) {
			return $args;
		}

		if ( isset( $args['exclude'] ) ) {
			$args['exclude'] = array_unique(
				array_merge( (array) $args['exclude'], $exclude_ids )
			);
		} else {
			$args['exclude'] = $exclude_ids;
		}

		return $args;
	}

	/**
	 * Filter posts query args used by WP sitemaps.
	 *
	 * @param array  $args      Query args.
	 * @param string $post_type Post type.
	 *
	 * @return array
	 */
	public function filter_posts_query_args( $args, $post_type ) {
		/**
		 * Filter the WP_Query args for sitemap generation.
		 *
		 * @param array  $args      WP_Query args.
		 * @param string $post_type Post type slug.
		 */
		return apply_filters( 'SAMAN_SEO_sitemap_post_query_args', $args, $post_type );
	}

	/**
	 * Register pretty URL for custom sitemap.
	 *
	 * @return void
	 */
	public function register_custom_sitemap() {
		add_rewrite_rule( '^sitemap_index\.xml$', 'index.php?SAMAN_SEO_sitemap_index=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_index%', '1' );
		add_rewrite_rule( '^sitemap\.xml$', 'index.php?SAMAN_SEO_sitemap_root=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_root%', '1' );

		add_rewrite_rule(
			'^([a-z0-9_-]+)-sitemap([0-9]+)\.xml$',
			'index.php?SAMAN_SEO_sitemap_slug=$matches[1]&SAMAN_SEO_sitemap_page=$matches[2]',
			'top'
		);
		add_rewrite_rule(
			'^([a-z0-9_-]+)-sitemap\.xml$',
			'index.php?SAMAN_SEO_sitemap_slug=$matches[1]',
			'top'
		);
		add_rewrite_tag( '%SAMAN_SEO_sitemap_slug%', '([a-z0-9_-]+)' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_page%', '([0-9]+)' );

		add_rewrite_rule( '^sitemap-style\.xsl$', 'index.php?SAMAN_SEO_sitemap_stylesheet=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_stylesheet%', '1' );

		// RSS Sitemap
		add_rewrite_rule( '^sitemap-rss\.xml$', 'index.php?SAMAN_SEO_sitemap_rss=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_rss%', '1' );

		// Google News Sitemap
		add_rewrite_rule( '^sitemap-news\.xml$', 'index.php?SAMAN_SEO_sitemap_news=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_news%', '1' );

		// Video Sitemap
		add_rewrite_rule( '^sitemap-video\.xml$', 'index.php?SAMAN_SEO_sitemap_video=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_video%', '1' );

		// Additional Pages Sitemap
		add_rewrite_rule( '^additional-sitemap\.xml$', 'index.php?SAMAN_SEO_sitemap_additional=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_sitemap_additional%', '1' );
	}

	/**
	 * Render custom sitemap when requested.
	 *
	 * @return void
	 */
	public function render_custom_sitemap() {
		if ( $this->is_wp_core_sitemap_request() ) {
			$this->redirect_core_sitemap();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_stylesheet' ) ) {
			$this->render_sitemap_stylesheet();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_root' ) ) {
			$this->redirect_pretty_sitemap();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_index' ) ) {
			$this->render_sitemap_index();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_rss' ) ) {
			$this->render_rss_sitemap();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_news' ) ) {
			$this->render_google_news_sitemap();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_video' ) ) {
			$this->render_video_sitemap();
			return;
		}

		if ( get_query_var( 'SAMAN_SEO_sitemap_additional' ) ) {
			$this->render_additional_pages_sitemap();
			return;
		}

		$slug = get_query_var( 'SAMAN_SEO_sitemap_slug' );
		if ( $slug ) {
			$page = absint( get_query_var( 'SAMAN_SEO_sitemap_page' ) );
			$page = max( 1, $page );

			$this->render_single_sitemap( $slug, $page );
			return;
		}
	}

	/**
	 * Render Saman SEO sitemap index with entries pulled from core providers.
	 *
	 * @return void
	 */
	private function render_sitemap_index() {
		$items = $this->get_sitemap_index_items();

		if ( empty( $items ) ) {
			$this->bail_404();
			return;
		}

		$renderer = $this->get_renderer();

		if ( ! $renderer ) {
			$this->bail_404();
			return;
		}

		nocache_headers();

		$renderer->render_index( $items );

		exit;
	}

	/**
	 * Compile sitemap index items (core + plugin).
	 *
	 * @return array<int,array<string,string>>
	 */
	private function get_sitemap_index_items() {
		$groups = $this->get_sitemap_map();

		if ( empty( $groups ) ) {
			return [];
		}

		$items = [];

		foreach ( $groups as $group ) {
			$max_pages = $this->get_max_pages_for_group( $group );

			if ( $max_pages < 1 ) {
				continue;
			}

			for ( $page = 1; $page <= $max_pages; $page++ ) {
				$item = [
					'loc' => $this->build_sitemap_url( $group['slug'], $page ),
				];
				$lastmod = $this->get_sitemap_lastmod( $group, $page );

				if ( $lastmod ) {
					$item['lastmod'] = $lastmod;
				}

				$items[] = $item;
			}
		}

		// Add additional pages if configured
		$additional_pages = get_option( 'SAMAN_SEO_sitemap_additional_pages', [] );
		if ( ! empty( $additional_pages ) && is_array( $additional_pages ) ) {
			// Create a custom sitemap for additional pages
			$items[] = [
				'loc' => home_url( '/additional-sitemap.xml' ),
			];
		}

		return apply_filters( 'SAMAN_SEO_sitemap_index_items', $items );
	}

	/**
	 * Render a specific sitemap page (posts, pages, taxonomies, authors).
	 *
	 * @param string $slug Requested slug (post, page, category, etc).
	 * @param int    $page Page number.
	 * @return void
	 */
	private function render_single_sitemap( $slug, $page ) {
		$group = $this->resolve_sitemap_group( $slug );

		if ( ! $group ) {
			$this->bail_404();
			return;
		}

		$max_pages = $this->get_max_pages_for_group( $group );

		if ( $max_pages < 1 || $page > $max_pages ) {
			$this->bail_404();
			return;
		}

		$url_list = $this->fetch_sitemap_urls( $group, $page );

		if ( empty( $url_list ) ) {
			$this->bail_404();
			return;
		}

		$renderer = $this->get_renderer();

		if ( ! $renderer ) {
			$this->bail_404();
			return;
		}

		nocache_headers();

		$renderer->render_sitemap( $url_list );

		exit;
	}

	/**
	 * Resolve slug to sitemap group metadata.
	 *
	 * @param string $slug Slug.
	 *
	 * @return array<string,mixed>|null
	 */
	private function resolve_sitemap_group( $slug ) {
		$slug   = $this->sanitize_slug( $slug );
		$groups = $this->get_sitemap_map();

		foreach ( $groups as $group ) {
			if ( $group['slug'] === $slug ) {
				return $group;
			}
		}

		return null;
	}

	/**
	 * Build sitemap map from WP core providers using Yoast-like naming.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function get_sitemap_map() {
		if ( ! empty( $this->sitemap_map ) ) {
			return $this->sitemap_map;
		}

		$server = $this->get_sitemaps_server();

		if ( ! $server ) {
			return [];
		}

		$map       = [];
		$providers = $server->registry->get_providers();

		// Get enabled post types and taxonomies from settings
		$enabled_post_types = get_option( 'SAMAN_SEO_sitemap_post_types', null );
		$enabled_taxonomies = get_option( 'SAMAN_SEO_sitemap_taxonomies', null );
		$include_author     = get_option( 'SAMAN_SEO_sitemap_include_author_pages', '0' );

		// If null, this is first time - get all available post types
		if ( null === $enabled_post_types ) {
			if ( isset( $providers['posts'] ) ) {
				$post_provider = $providers['posts'];
				$subtypes      = $post_provider->get_object_subtypes();
				$enabled_post_types = array_keys( $subtypes );
			} else {
				$enabled_post_types = [];
			}
		}

		// If null, this is first time - get all available taxonomies
		if ( null === $enabled_taxonomies ) {
			if ( isset( $providers['taxonomies'] ) ) {
				$tax_provider = $providers['taxonomies'];
				$taxonomies_list = $tax_provider->get_object_subtypes();
				$enabled_taxonomies = array_keys( $taxonomies_list );
			} else {
				$enabled_taxonomies = [];
			}
		}

		if ( isset( $providers['posts'] ) ) {
			$post_provider = $providers['posts'];
			$subtypes      = $post_provider->get_object_subtypes();

			foreach ( $subtypes as $name => $object ) {
				// Only include if in the enabled list
				if ( ! in_array( $name, $enabled_post_types, true ) ) {
					continue;
				}

				$map[] = [
					'slug'     => $this->sanitize_slug( $name ),
					'label'    => $object->label ?? $name,
					'provider' => 'posts',
					'subtype'  => $name,
				];
			}
		}

		if ( isset( $providers['taxonomies'] ) ) {
			$tax_provider = $providers['taxonomies'];
			$taxonomies   = $tax_provider->get_object_subtypes();

			foreach ( $taxonomies as $name => $object ) {
				// Only include if in the enabled list
				if ( ! in_array( $name, $enabled_taxonomies, true ) ) {
					continue;
				}

				$map[] = [
					'slug'     => $this->sanitize_slug( $name ),
					'label'    => $object->label ?? $name,
					'provider' => 'taxonomies',
					'subtype'  => $name,
				];
			}
		}

		if ( isset( $providers['users'] ) && '1' === $include_author ) {
			$map[] = [
				'slug'     => 'author',
				'label'    => __( 'Authors', 'saman-seo' ),
				'provider' => 'users',
				'subtype'  => '',
			];
		}

		/**
		 * Filter the sitemap groups before output.
		 *
		 * @param array<int,array<string,mixed>> $map Sitemap map.
		 */
		$this->sitemap_map = apply_filters( 'SAMAN_SEO_sitemap_map', $map );

		return $this->sitemap_map;
	}

	/**
	 * Retrieve the WordPress sitemap server while temporarily allowing it to run.
	 *
	 * @return \WP_Sitemaps|null
	 */
	private function get_sitemaps_server() {
		if ( $this->sitemap_server instanceof \WP_Sitemaps ) {
			return $this->sitemap_server;
		}

		if ( ! function_exists( 'wp_sitemaps_get_server' ) ) {
			return null;
		}

		$this->allow_core_sitemaps = true;
		$server                    = wp_sitemaps_get_server();

		if ( $server && empty( $server->registry->get_providers() ) && method_exists( $server, 'register_sitemaps' ) ) {
			$server->register_sitemaps();
		}

		$this->allow_core_sitemaps = false;

		if ( ! $server ) {
			return null;
		}

		$this->sitemap_server = $server;

		return $this->sitemap_server;
	}

	/**
	 * Retrieve the WordPress sitemap renderer.
	 *
	 * @return \WP_Sitemaps_Renderer|null
	 */
	private function get_renderer() {
		$server = $this->get_sitemaps_server();

		return $server ? $server->renderer : null;
	}

	/**
	 * Retrieve a provider by name.
	 *
	 * @param string $name Provider name.
	 *
	 * @return \WP_Sitemaps_Provider|null
	 */
	private function get_provider( $name ) {
		$server = $this->get_sitemaps_server();

		if ( ! $server ) {
			return null;
		}

		return $server->registry->get_provider( $name );
	}

	/**
	 * Determine the maximum number of pages for a sitemap group.
	 *
	 * @param array<string,mixed> $group Group metadata.
	 *
	 * @return int
	 */
	private function get_max_pages_for_group( $group ) {
		$count = $this->get_group_item_count( $group );
		$limit = $this->get_max_urls_per_page();

		if ( null !== $count ) {
			if ( $count < 1 || $limit < 1 ) {
				return 0;
			}

			return (int) ceil( $count / $limit );
		}

		$provider = $this->get_provider( $group['provider'] );

		if ( ! $provider ) {
			return 0;
		}

		$this->allow_core_sitemaps = true;

		if ( 'users' === $group['provider'] ) {
			$max_pages = (int) $provider->get_max_num_pages();
		} else {
			$max_pages = (int) $provider->get_max_num_pages( $group['subtype'] );
		}

		$this->allow_core_sitemaps = false;

		return $max_pages;
	}

	/**
	 * Fetch URL list for a sitemap page.
	 *
	 * @param array<string,mixed> $group Group metadata.
	 * @param int                 $page  Page number.
	 * @param bool                $use_cache Whether to reuse cached results.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function fetch_sitemap_urls( $group, $page, $use_cache = true ) {
		$page      = max( 1, (int) $page );
		$cache_key = sprintf( '%s|%d', $group['slug'], $page );

		if ( $use_cache && isset( $this->sitemap_page_cache[ $cache_key ] ) ) {
			return $this->sitemap_page_cache[ $cache_key ];
		}

		$provider = $this->get_provider( $group['provider'] );

		if ( ! $provider ) {
			return [];
		}

		$this->allow_core_sitemaps = true;

		if ( 'users' === $group['provider'] ) {
			$list = $provider->get_url_list( $page );
		} else {
			$list = $provider->get_url_list( $page, $group['subtype'] );
		}

		$this->allow_core_sitemaps = false;

		if ( ! is_array( $list ) ) {
			$list = [];
		}

		if ( $use_cache ) {
			$this->sitemap_page_cache[ $cache_key ] = $list;
		}

		return $list;
	}

	/**
	 * Determine last modified timestamp for a sitemap page.
	 *
	 * @param array<string,mixed> $group Group metadata.
	 * @param int                 $page  Page number.
	 *
	 * @return string
	 */
	private function get_sitemap_lastmod( $group, $page ) {
		$filtered_lastmod = apply_filters( 'SAMAN_SEO_sitemap_lastmod', '', $group, $page );
		$timestamp        = $this->parse_lastmod_timestamp( $filtered_lastmod );

		if ( ! $timestamp ) {
			$url_list = $this->fetch_sitemap_urls( $group, $page );
			$timestamp = $this->get_latest_lastmod_from_list( $url_list );
		}

		if ( ! $timestamp ) {
			return '';
		}

		return $this->format_lastmod_timestamp( $timestamp );
	}

	/**
	 * Retrieve the newest valid lastmod timestamp from a sitemap entry list.
	 *
	 * @param array<int,array<string,string>> $url_list Sitemap entry list.
	 * @return int
	 */
	private function get_latest_lastmod_from_list( $url_list ) {
		$latest = 0;

		if ( empty( $url_list ) ) {
			return $latest;
		}

		foreach ( $url_list as $entry ) {
			if ( empty( $entry['lastmod'] ) ) {
				continue;
			}

			$timestamp = $this->parse_lastmod_timestamp( $entry['lastmod'] );

			if ( $timestamp > $latest ) {
				$latest = $timestamp;
			}
		}

		return $latest;
	}

	/**
	 * Normalize a provided lastmod value to a Unix timestamp.
	 *
	 * @param mixed $value Raw lastmod value.
	 * @return int
	 */
	private function parse_lastmod_timestamp( $value ) {
		if ( empty( $value ) ) {
			return 0;
		}

		if ( is_numeric( $value ) ) {
			$timestamp = (int) $value;
		} else {
			$timestamp = strtotime( (string) $value );
		}

		return $timestamp ? $timestamp : 0;
	}

	/**
	 * Convert a timestamp to the RFC3339 format expected by sitemap consumers.
	 *
	 * @param int $timestamp Unix timestamp.
	 * @return string
	 */
	private function format_lastmod_timestamp( $timestamp ) {
		if ( empty( $timestamp ) ) {
			return '';
		}

		return gmdate( 'c', (int) $timestamp );
	}

	/**
	 * Build a Yoast-style sitemap URL.
	 *
	 * @param string $slug Slug.
	 * @param int    $page Page number.
	 *
	 * @return string
	 */
	private function build_sitemap_url( $slug, $page ) {
		$page_suffix = ( $page > 1 ) ? $page : '';

		return home_url( sprintf( '/%s-sitemap%s.xml', $slug, $page_suffix ) );
	}

	/**
	 * Sanitize sitemap slug.
	 *
	 * @param string $slug Raw slug.
	 *
	 * @return string
	 */
	private function sanitize_slug( $slug ) {
		$slug = strtolower( (string) $slug );

		return preg_replace( '#[^a-z0-9_-]#', '', $slug );
	}

	/**
	 * Resolve the maximum URLs per sitemap page.
	 *
	 * @return int
	 */
	private function get_max_urls_per_page( $core_default = null ) {
		if ( null !== $this->max_urls_per_page ) {
			return $this->max_urls_per_page;
		}

		// Get from settings first
		$limit = (int) get_option( 'SAMAN_SEO_sitemap_max_urls', $this->default_max_urls_per_page );

		if ( null !== $core_default ) {
			$limit = min( (int) $core_default, $limit );
		}

		if ( defined( 'SAMAN_SEO_SITEMAP_MAX_URLS' ) ) {
			$limit = (int) SAMAN_SEO_SITEMAP_MAX_URLS;
		}

		$limit = (int) apply_filters(
			'SAMAN_SEO_sitemap_max_urls',
			$limit,
			$core_default ?? $this->default_max_urls_per_page
		);

		if ( $limit < 1 ) {
			$limit = 1;
		}

		$this->max_urls_per_page = $limit;

		return $this->max_urls_per_page;
	}

	/**
	 * Build taxonomy query args that respect core filters.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<string,mixed>
	 */
	private function get_taxonomy_query_args_for_counts( $taxonomy ) {
		$args = [
			'taxonomy'               => $taxonomy,
			'orderby'                => 'term_order',
			'number'                 => wp_sitemaps_get_max_urls( 'term' ),
			'hide_empty'             => true,
			'hierarchical'           => false,
			'update_term_meta_cache' => false,
		];

		return apply_filters( 'wp_sitemaps_taxonomies_query_args', $args, $taxonomy );
	}

	/**
	 * Resolve excluded term IDs for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int,int>
	 */
	private function resolve_excluded_term_ids( $taxonomy ) {
		if ( isset( $this->excluded_term_ids[ $taxonomy ] ) ) {
			return $this->excluded_term_ids[ $taxonomy ];
		}

		if ( ! taxonomy_exists( $taxonomy ) ) {
			$this->excluded_term_ids[ $taxonomy ] = [];

			return [];
		}

		$slugs = $this->get_excluded_term_slugs( $taxonomy );

		if ( empty( $slugs ) ) {
			$this->excluded_term_ids[ $taxonomy ] = [];

			return [];
		}

		$terms = get_terms(
			[
				'taxonomy'   => $taxonomy,
				'slug'       => $slugs,
				'hide_empty' => false,
				'fields'     => 'ids',
			]
		);

		if ( is_wp_error( $terms ) ) {
			$terms = [];
		}

		$this->excluded_term_ids[ $taxonomy ] = array_map( 'intval', (array) $terms );

		return $this->excluded_term_ids[ $taxonomy ];
	}

	/**
	 * Excluded term slugs per taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return array<int,string>
	 */
	private function get_excluded_term_slugs( $taxonomy ) {
		$defaults = $this->default_excluded_term_slugs[ $taxonomy ] ?? [];

		$slugs = (array) apply_filters(
			'SAMAN_SEO_sitemap_excluded_terms',
			$defaults,
			$taxonomy
		);

		$slugs = array_filter(
			array_map(
				static function ( $slug ) {
					return sanitize_title( (string) $slug );
				},
				$slugs
			)
		);

		return array_values( $slugs );
	}

	/**
	 * Return the total number of objects for a sitemap group via cheap counts.
	 *
	 * @param array<string,mixed> $group Group metadata.
	 *
	 * @return int|null
	 */
	private function get_group_item_count( $group ) {
		$cache_key = sprintf( '%s|%s', $group['provider'], $group['subtype'] );

		if ( isset( $this->group_item_counts[ $cache_key ] ) ) {
			return $this->group_item_counts[ $cache_key ];
		}

		$count = null;

		switch ( $group['provider'] ) {
			case 'posts':
				$count = $this->count_posts_for_sitemap( $group['subtype'] );
				break;
			case 'taxonomies':
				$count = $this->count_terms_for_sitemap( $group['subtype'] );
				break;
			case 'users':
				$count = $this->count_users_for_sitemap();
				break;
		}

		/**
		 * Allow custom sitemap providers to supply their total counts.
		 *
		 * @param int|null                 $count Current count (null when unknown).
		 * @param array<string,mixed>      $group Group metadata.
		 */
		$count = apply_filters( 'SAMAN_SEO_sitemap_group_count', $count, $group );

		if ( null !== $count ) {
			$count = max( 0, (int) $count );
			$this->group_item_counts[ $cache_key ] = $count;
		}

		return $count;
	}

	/**
	 * Count published posts for a given post type.
	 *
	 * @param string $post_type Post type.
	 * @return int
	 */
	private function count_posts_for_sitemap( $post_type ) {
		if ( ! post_type_exists( $post_type ) ) {
			return 0;
		}

		$counts            = wp_count_posts( $post_type, 'readable' );
		$allowed_statuses  = (array) apply_filters(
			'SAMAN_SEO_sitemap_count_statuses',
			[ 'publish', 'inherit' ],
			$post_type
		);
		$total             = 0;

		if ( $counts instanceof \stdClass ) {
			foreach ( $allowed_statuses as $status ) {
				if ( isset( $counts->{$status} ) ) {
					$total += (int) $counts->{$status};
				}
			}
		} else {
			$total = (int) $counts;
		}

		return $total;
	}

	/**
	 * Count public terms for a taxonomy.
	 *
	 * @param string $taxonomy Taxonomy slug.
	 * @return int
	 */
	private function count_terms_for_sitemap( $taxonomy ) {
		if ( ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}

		$args = $this->get_taxonomy_query_args_for_counts( $taxonomy );

		$count = wp_count_terms( $args );

		if ( is_wp_error( $count ) ) {
			return 0;
		}

		return (int) $count;
	}

	/**
	 * Count the number of authors with published posts.
	 *
	 * @return int
	 */
	private function count_users_for_sitemap() {
		$query = new \WP_User_Query(
			[
				'has_published_posts' => true,
				'fields'              => 'ID',
				'number'              => 1,
				'count_total'         => true,
			]
		);

		return (int) $query->get_total();
	}

	/**
	 * Force stylesheet URL to the Saman SEO version.
	 *
	 * @param string $url Original URL.
	 *
	 * @return string
	 */
	public function filter_stylesheet_url( $url ) {
		return $this->get_stylesheet_url();
	}

	/**
	 * Send a 404 header when a sitemap cannot be built.
	 *
	 * @return void
	 */
	private function bail_404() {
		global $wp_query;

		if ( $wp_query instanceof \WP_Query ) {
			$wp_query->set_404();
		}

		status_header( 404 );
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

	/**
	 * Force-disable WordPress core sitemaps while this module is active.
	 *
	 * @param bool $enabled Whether core sitemaps are enabled.
	 *
	 * @return bool
	 */
	public function disable_core_sitemaps( $enabled ) {
		if ( $this->allow_core_sitemaps ) {
			return $enabled;
		}

		$should_disable = apply_filters( 'SAMAN_SEO_disable_core_sitemaps', true );

		return $should_disable ? false : $enabled;
	}

	/**
	 * Whether the current request targets WP core sitemaps.
	 *
	 * @return bool
	 */
	private function is_wp_core_sitemap_request() {
		return (bool) ( get_query_var( 'sitemap' ) || get_query_var( 'sitemap-stylesheet' ) );
	}

	/**
	 * Redirect the core sitemap endpoint to the Saman SEO equivalent.
	 *
	 * @return void
	 */
	private function redirect_core_sitemap() {
		$target = apply_filters( 'SAMAN_SEO_sitemap_redirect', home_url( '/sitemap_index.xml' ) );
		$this->send_sitemap_redirect( $target );
	}

	/**
	 * Redirect pretty /sitemap.xml requests to the sitemap index.
	 *
	 * @return void
	 */
	private function redirect_pretty_sitemap() {
		$target = apply_filters(
			'SAMAN_SEO_pretty_sitemap_redirect',
			apply_filters( 'SAMAN_SEO_sitemap_redirect', home_url( '/sitemap_index.xml' ) )
		);

		$this->send_sitemap_redirect( $target );
	}

	/**
	 * Send a 301 redirect to the provided sitemap destination.
	 *
	 * @param string $target Destination URL.
	 * @return void
	 */
	private function send_sitemap_redirect( $target ) {
		if ( empty( $target ) || headers_sent() ) {
			return;
		}

		nocache_headers();
		wp_safe_redirect( esc_url_raw( $target ), 301 );
		exit;
	}

	/**
	 * Render human-friendly XSL stylesheet.
	 *
	 * @return void
	 */
	private function render_sitemap_stylesheet() {
		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
<xsl:stylesheet version="1.0"
	xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
	xmlns:sitemap="http://www.sitemaps.org/schemas/sitemap/0.9">
	<xsl:output method="html" indent="yes" />

	<xsl:template match="/">
		<html lang="en">
			<head>
				<meta charset="utf-8" />
				<title>Saman SEO Sitemap</title>
				<style>
					body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;background:#f5f6fa;margin:0;padding:2rem;color:#1f2933;}
					h1{margin-top:0;font-size:1.8rem;}
					p.description{color:#4b5563;margin-bottom:1.5rem;}
					table{width:100%;border-collapse:collapse;background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 15px 35px rgba(31,45,61,.08);}
					th,td{padding:1rem;text-align:left;}
					th{background:#0f172a;color:#f8fafc;text-transform:uppercase;font-size:.75rem;letter-spacing:.08em;}
					tbody tr:nth-child(even){background:#fff;}
					tbody tr:nth-child(odd){background:#f8fafc;}
					tbody tr:hover{background:#e0f2fe;}
					.loc{word-break:break-all;color:#0f62fe;font-weight:500;text-decoration:none;}
					.badge{display:inline-flex;align-items:center;background:#0f172a;color:#fff;border-radius:999px;padding:0 .65rem;font-size:.72rem;font-weight:600;margin-left:.5rem;}
					.meta{display:flex;align-items:center;gap:.5rem;font-size:.9rem;color:#475569;margin-bottom:.5rem;}
				</style>
			</head>
			<body>
				<main>
					<h1>XML Sitemap Overview</h1>
					<p class="description">
						This sitemap is generated by Saman SEO and helps search engines discover your most important content quickly.
					</p>
					<xsl:choose>
						<xsl:when test="count(/sitemap:sitemapindex/sitemap:sitemap) &gt; 0">
							<div class="meta">
								<strong>Sitemaps:</strong>
								<span class="badge">
									<xsl:value-of select="count(/sitemap:sitemapindex/sitemap:sitemap)" />
								</span>
							</div>
							<table>
								<thead>
									<tr>
										<th scope="col">Sitemap URL</th>
										<th scope="col">Last Modified</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="/sitemap:sitemapindex/sitemap:sitemap">
										<tr>
											<td>
												<a class="loc">
													<xsl:attribute name="href"><xsl:value-of select="sitemap:loc" /></xsl:attribute>
													<xsl:value-of select="sitemap:loc" />
												</a>
											</td>
											<td>
												<xsl:choose>
													<xsl:when test="sitemap:lastmod">
														<xsl:value-of select="sitemap:lastmod" />
													</xsl:when>
													<xsl:otherwise>ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â</xsl:otherwise>
												</xsl:choose>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</xsl:when>
						<xsl:when test="count(/sitemap:urlset/sitemap:url) &gt; 0">
							<div class="meta">
								<strong>URLs:</strong>
								<span class="badge">
									<xsl:value-of select="count(/sitemap:urlset/sitemap:url)" />
								</span>
							</div>
							<table>
								<thead>
									<tr>
										<th scope="col">Page URL</th>
										<th scope="col">Last Modified</th>
									</tr>
								</thead>
								<tbody>
									<xsl:for-each select="/sitemap:urlset/sitemap:url">
										<tr>
											<td>
												<a class="loc">
													<xsl:attribute name="href"><xsl:value-of select="sitemap:loc" /></xsl:attribute>
													<xsl:value-of select="sitemap:loc" />
												</a>
											</td>
											<td>
												<xsl:choose>
													<xsl:when test="sitemap:lastmod">
														<xsl:value-of select="sitemap:lastmod" />
													</xsl:when>
													<xsl:otherwise>ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â</xsl:otherwise>
												</xsl:choose>
											</td>
										</tr>
									</xsl:for-each>
								</tbody>
							</table>
						</xsl:when>
						<xsl:otherwise>
							<p>No sitemap entries were found.</p>
						</xsl:otherwise>
					</xsl:choose>
				</main>
			</body>
		</html>
	</xsl:template>
</xsl:stylesheet>
		<?php
		exit;
	}

	/**
	 * Get stylesheet URL.
	 *
	 * @return string
	 */
	private function get_stylesheet_url() {
		return apply_filters( 'SAMAN_SEO_sitemap_stylesheet', home_url( '/sitemap-style.xsl' ) );
	}

	/**
	 * Render RSS sitemap.
	 *
	 * @return void
	 */
	private function render_rss_sitemap() {
		if ( '1' !== get_option( 'SAMAN_SEO_sitemap_enable_rss', '0' ) ) {
			$this->bail_404();
			return;
		}

		$posts = get_posts(
			[
				'posts_per_page' => 50,
				'post_status'    => 'publish',
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $posts ) ) {
			$this->bail_404();
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/rss+xml; charset=UTF-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/">
	<channel>
		<title><?php echo esc_html( get_bloginfo( 'name' ) ); ?></title>
		<link><?php echo esc_url( home_url( '/' ) ); ?></link>
		<description><?php echo esc_html( get_bloginfo( 'description' ) ); ?></description>
		<language><?php echo esc_attr( get_locale() ); ?></language>
		<?php foreach ( $posts as $post ) : ?>
		<item>
			<title><?php echo esc_html( get_the_title( $post ) ); ?></title>
			<link><?php echo esc_url( get_permalink( $post ) ); ?></link>
			<guid><?php echo esc_url( get_permalink( $post ) ); ?></guid>
			<pubDate><?php echo esc_html( get_post_time( 'r', true, $post ) ); ?></pubDate>
			<description><![CDATA[<?php echo wp_kses_post( get_the_excerpt( $post ) ); ?>]]></description>
			<content:encoded><![CDATA[<?php echo wp_kses_post( $post->post_content ); ?>]]></content:encoded>
		</item>
		<?php endforeach; ?>
	</channel>
</rss>
		<?php
		exit;
	}

	/**
	 * Render Google News sitemap.
	 *
	 * @return void
	 */
	private function render_google_news_sitemap() {
		if ( '1' !== get_option( 'SAMAN_SEO_sitemap_enable_google_news', '0' ) ) {
			$this->bail_404();
			return;
		}

		$post_types    = get_option( 'SAMAN_SEO_sitemap_google_news_post_types', [] );
		$pub_name      = get_option( 'SAMAN_SEO_sitemap_google_news_name', get_bloginfo( 'name' ) );

		if ( empty( $post_types ) ) {
			$post_types = [ 'post' ];
		}

		$posts = get_posts(
			[
				'post_type'      => $post_types,
				'posts_per_page' => 1000,
				'post_status'    => 'publish',
				'date_query'     => [
					[
						'after' => '2 days ago',
					],
				],
				'orderby'        => 'date',
				'order'          => 'DESC',
			]
		);

		if ( empty( $posts ) ) {
			$this->bail_404();
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">
	<?php foreach ( $posts as $post ) : ?>
	<url>
		<loc><?php echo esc_url( get_permalink( $post ) ); ?></loc>
		<news:news>
			<news:publication>
				<news:name><?php echo esc_html( $pub_name ); ?></news:name>
				<news:language><?php echo esc_attr( get_locale() ); ?></news:language>
			</news:publication>
			<news:publication_date><?php echo esc_html( get_post_time( DATE_W3C, true, $post ) ); ?></news:publication_date>
			<news:title><?php echo esc_html( get_the_title( $post ) ); ?></news:title>
		</news:news>
	</url>
	<?php endforeach; ?>
</urlset>
		<?php
		exit;
	}

	/**
	 * Render video sitemap.
	 *
	 * Generates a sitemap for posts containing YouTube/Vimeo videos.
	 *
	 * @return void
	 */
	private function render_video_sitemap() {
		// Get video schema service.
		$video_service = \Saman\SEO\Plugin::instance()->get( 'video_schema' );

		if ( ! $video_service ) {
			$this->bail_404();
			return;
		}

		// Get posts with videos.
		$posts_with_videos = $video_service->get_posts_with_videos( 1000 );

		if ( empty( $posts_with_videos ) ) {
			$this->bail_404();
			return;
		}

		nocache_headers();
		header( 'Content-Type: application/xml; charset=UTF-8' );

		echo '<?xml version="1.0" encoding="UTF-8"?>';
		?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">
	<?php foreach ( $posts_with_videos as $item ) :
		$post = $item['post'];
		$videos = $item['videos'];
		foreach ( $videos as $video ) :
			$thumbnail = '';
			$title = get_the_title( $post );
			$description = wp_trim_words( wp_strip_all_tags( $post->post_content ), 50 );

			if ( 'youtube' === $video['platform'] ) {
				$thumbnail = 'https://img.youtube.com/vi/' . $video['id'] . '/maxresdefault.jpg';
				$content_loc = 'https://www.youtube.com/watch?v=' . $video['id'];
				$player_loc = 'https://www.youtube.com/embed/' . $video['id'];
			} elseif ( 'vimeo' === $video['platform'] ) {
				$content_loc = 'https://vimeo.com/' . $video['id'];
				$player_loc = 'https://player.vimeo.com/video/' . $video['id'];
			} else {
				continue;
			}
	?>
	<url>
		<loc><?php echo esc_url( get_permalink( $post ) ); ?></loc>
		<video:video>
			<video:title><?php echo esc_html( $title ); ?></video:title>
			<video:description><?php echo esc_html( $description ); ?></video:description>
			<?php if ( $thumbnail ) : ?>
			<video:thumbnail_loc><?php echo esc_url( $thumbnail ); ?></video:thumbnail_loc>
			<?php endif; ?>
			<video:content_loc><?php echo esc_url( $content_loc ); ?></video:content_loc>
			<video:player_loc><?php echo esc_url( $player_loc ); ?></video:player_loc>
			<video:publication_date><?php echo esc_html( get_post_time( DATE_W3C, true, $post ) ); ?></video:publication_date>
		</video:video>
	</url>
	<?php endforeach; endforeach; ?>
</urlset>
		<?php
		exit;
	}

	/**
	 * Render additional pages sitemap.
	 *
	 * @return void
	 */
	private function render_additional_pages_sitemap() {
		$additional_pages = get_option( 'SAMAN_SEO_sitemap_additional_pages', [] );

		if ( empty( $additional_pages ) || ! is_array( $additional_pages ) ) {
			$this->bail_404();
			return;
		}

		$url_list = [];

		foreach ( $additional_pages as $page ) {
			if ( empty( $page['url'] ) ) {
				continue;
			}

			$url_list[] = [
				'loc'      => esc_url_raw( $page['url'] ),
				'priority' => floatval( $page['priority'] ?? 0.5 ),
			];
		}

		if ( empty( $url_list ) ) {
			$this->bail_404();
			return;
		}

		$renderer = $this->get_renderer();

		if ( ! $renderer ) {
			$this->bail_404();
			return;
		}

		nocache_headers();

		$renderer->render_sitemap( $url_list );

		exit;
	}
}
