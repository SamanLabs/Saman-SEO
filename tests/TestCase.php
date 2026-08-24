<?php
/**
 * Base test case for Saman SEO unit tests.
 *
 * Boots Brain Monkey, installs lightweight WordPress function shims backed
 * by deterministic in-memory stores (options, post meta, posts, request
 * context), and provides a working hook registry so filters/actions behave
 * like the real thing without loading WordPress core.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Tests;

use Brain\Monkey;
use Brain\Monkey\Functions;

/**
 * Shared test harness.
 */
abstract class TestCase extends \PHPUnit\Framework\TestCase {

	/**
	 * In-memory option store.
	 *
	 * @var array<string,mixed>
	 */
	public static $options = array();

	/**
	 * In-memory post meta store: [ post_id ][ meta_key ] => value.
	 *
	 * @var array<int,array<string,mixed>>
	 */
	public static $post_meta = array();

	/**
	 * In-memory attachment alt text store: [ attachment_id ] => string.
	 *
	 * @var array<int,string>
	 */
	public static $alt_texts = array();

	/**
	 * Post object store: [ post_id ] => WP_Post.
	 *
	 * @var array<int,\WP_Post>
	 */
	public static $posts = array();

	/**
	 * Permalink overrides: [ post_id ] => URL.
	 *
	 * @var array<int,string>
	 */
	public static $permalinks = array();

	/**
	 * Ancestor IDs per post: [ post_id ] => int[].
	 *
	 * @var array<int,int[]>
	 */
	public static $ancestors = array();

	/**
	 * Author display names: [ user_id ] => string.
	 *
	 * @var array<int,string>
	 */
	public static $authors = array();

	/**
	 * Request-context flags consumed by conditional tag stubs.
	 *
	 * @var array<string,bool|int>
	 */
	public static $context = array(
		'singular'            => false,
		'front_page'          => false,
		'home'                => false,
		'archive'             => false,
		'search'              => false,
		'is_404'              => false,
		'category'            => false,
		'tag'                 => false,
		'tax'                 => false,
		'post_type_archive'   => false,
		'author_archive'      => false,
		'date'                => false,
		'password_required'   => false,
	);

	/**
	 * Current queried object (term) for archive contexts.
	 *
	 * @var \WP_Term|null
	 */
	public static $queried_term = null;

	/**
	 * Search query string.
	 *
	 * @var string
	 */
	public static $search_query = '';

	/**
	 * Archive title string.
	 *
	 * @var string
	 */
	public static $archive_title = '';

	/**
	 * Post type archive label.
	 *
	 * @var string
	 */
	public static $post_type_archive_label = '';

	/**
	 * Query var values.
	 *
	 * @var array<string,int>
	 */
	public static $query_vars = array();

	/**
	 * Registered filters/actions: [ tag ] => callable[].
	 *
	 * @var array<string,callable[]>
	 */
	public static $hooks = array();

	/**
	 * ID of the post treated as "current" for global queries.
	 *
	 * @var int
	 */
	public static $current_post_id = 0;

	/**
	 * Counters of fired actions: [ tag ] => int.
	 *
	 * @var array<string,int>
	 */
	public static $fired_actions = array();

	/**
	 * Set up each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		self::$options              = array();
		self::$post_meta            = array();
		self::$posts                = array();
		self::$permalinks           = array();
		self::$ancestors            = array();
		self::$authors              = array();
		self::$alt_texts            = array();
		self::$hooks                = array();
		self::$fired_actions        = array();
		self::$current_post_id      = 0;
		self::$queried_term         = null;
		self::$search_query         = '';
		self::$archive_title        = '';
		self::$post_type_archive_label = '';
		self::$query_vars           = array();
		self::$context              = array(
			'singular'          => false,
			'front_page'        => false,
			'home'              => false,
			'archive'           => false,
			'search'            => false,
			'is_404'            => false,
			'category'          => false,
			'tag'               => false,
			'tax'               => false,
			'post_type_archive' => false,
			'author_archive'    => false,
			'date'              => false,
			'password_required' => false,
		);

		$this->install_stubs();
	}

	/**
	 * Tear down each test.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Install all WP function stubs.
	 *
	 * @return void
	 */
	protected function install_stubs(): void {
		// -----------------------------------------------------------------
		// Options & storage.
		// -----------------------------------------------------------------
		Functions\when( 'get_option' )->alias(
			static function ( $option, $default = false ) {
				return array_key_exists( $option, self::$options ) ? self::$options[ $option ] : $default;
			}
		);
		Functions\when( 'update_option' )->alias(
			static function ( $option, $value ) {
				self::$options[ $option ] = $value;
				return true;
			}
		);
		Functions\when( 'delete_option' )->alias(
			static function ( $option ) {
				unset( self::$options[ $option ] );
				return true;
			}
		);
		Functions\when( 'get_post_meta' )->alias(
			static function ( $post_id, $key = '', $single = false ) {
				if ( '' === $key ) {
					return self::$post_meta[ (int) $post_id ] ?? array();
				}

				if ( isset( self::$post_meta[ (int) $post_id ][ $key ] ) ) {
					$value = self::$post_meta[ (int) $post_id ][ $key ];
					return $single ? $value : array( $value );
				}

				return $single ? '' : array();
			}
		);
		Functions\when( 'update_post_meta' )->alias(
			static function ( $post_id, $key, $value ) {
				self::$post_meta[ (int) $post_id ][ $key ] = $value;
				return true;
			}
		);
		Functions\when( 'get_post' )->alias(
			static function ( $post = null ) {
				return self::resolve_post( $post );
			}
		);
		Functions\when( 'get_post_type' )->alias(
			static function ( $post = null ) {
				$post = self::resolve_post( $post );

				return $post ? $post->post_type : false;
			}
		);
		Functions\when( 'get_permalink' )->alias(
			static function ( $post = null ) {
				$post = self::resolve_post( $post );

				if ( ! $post ) {
					return false;
				}

				return self::$permalinks[ $post->ID ]
					?? 'https://example.org/' . ( $post->post_name ?: (string) $post->ID ) . '/';
			}
		);
		Functions\when( 'get_the_title' )->alias(
			static function ( $post = null ) {
				$post = self::resolve_post( $post );

				return $post ? $post->post_title : '';
			}
		);
		Functions\when( 'get_post_ancestors' )->alias(
			static function ( $post ) {
				$post = self::resolve_post( $post );

				return $post ? ( self::$ancestors[ $post->ID ] ?? array() ) : array();
			}
		);

		// -----------------------------------------------------------------
		// URLs & site info.
		// -----------------------------------------------------------------
		Functions\when( 'home_url' )->alias(
			static function ( $path = '' ) {
				$path = (string) $path;

				if ( '' !== $path && '/' !== $path[0] && ! preg_match( '#^[a-z][a-z0-9+.-]*:#i', $path ) ) {
					$path = '/' . $path;
				}

				return 'https://example.org' . $path;
			}
		);
		Functions\when( 'get_bloginfo' )->alias(
			static function ( $show = '' ) {
				switch ( $show ) {
					case 'name':
						return 'Test Site';
					case 'description':
						return 'Just another test site';
					default:
						return '';
				}
			}
		);
		Functions\when( 'get_locale' )->justReturn( 'en_US' );
		Functions\when( 'trailingslashit' )->alias(
			static function ( $value ) {
				return rtrim( (string) $value, '/\\' ) . '/';
			}
		);
		Functions\when( 'user_trailingslashit' )->alias(
			static function ( $value, $type_of_url = '' ) {
				$value = (string) $value;

				// Pretty permalinks end with a slash; numeric fragments keep theirs.
				if ( '' === $value || ctype_digit( $value ) ) {
					return $value;
				}

				return rtrim( $value, '/\\' ) . '/';
			}
		);
		Functions\when( 'wp_parse_url' )->alias(
			static function ( $url, $component = -1 ) {
				return parse_url( (string) $url, $component );
			}
		);
		Functions\when( 'add_query_arg' )->alias(
			static function ( ...$args ) {
				if ( 1 === count( $args ) && is_array( $args[0] ) ) {
					$params = $args[0];
					$url    = '';
				} elseif ( 2 === count( $args ) && is_array( $args[0] ) ) {
					$params = $args[0];
					$url    = (string) $args[1];
				} else {
					$params = array( (string) $args[0] => $args[1] );
					$url    = (string) $args[2];
				}

				$query = http_build_query( $params );

				return $url . ( strpos( $url, '?' ) === false ? '?' : '&' ) . $query;
			}
		);
		Functions\when( 'get_pagenum_link' )->alias(
			static function ( $pagenum = 1 ) {
				return 1 === (int) $pagenum
					? 'https://example.org/'
					: 'https://example.org/page/' . (int) $pagenum . '/';
			}
		);
		Functions\when( 'get_author_posts_url' )->alias(
			static function ( $author_id ) {
				return 'https://example.org/author/' . (int) $author_id . '/';
			}
		);
		Functions\when( 'get_avatar_url' )->justReturn( '' );
		Functions\when( 'get_site_icon_url' )->justReturn( '' );
		Functions\when( 'plugin_dir_path' )->alias(
			static function ( $file ) {
				return rtrim( (string) dirname( (string) $file ), '/\\' ) . '/';
			}
		);

		// -----------------------------------------------------------------
		// Conditional tags & query state.
		// -----------------------------------------------------------------
		foreach ( array(
			'is_singular'        => 'singular',
			'is_front_page'      => 'front_page',
			'is_home'            => 'home',
			'is_archive'         => 'archive',
			'is_search'          => 'search',
			'is_404'             => 'is_404',
			'is_category'        => 'category',
			'is_tag'             => 'tag',
			'is_tax'             => 'tax',
			'is_post_type_archive' => 'post_type_archive',
			'is_author'          => 'author_archive',
			'is_date'            => 'date',
		) as $function => $flag ) {
			Functions\when( $function )->alias(
				static function () use ( $flag ) {
					return (bool) ( self::$context[ $flag ] ?? false );
				}
			);
		}

		Functions\when( 'post_password_required' )->alias(
			static function () {
				return (bool) self::$context['password_required'];
			}
		);
		Functions\when( 'current_theme_supports' )->justReturn( false );
		Functions\when( 'wp_is_block_theme' )->justReturn( false );
		Functions\when( 'get_queried_object' )->alias(
			static function () {
				return self::$queried_term;
			}
		);
		Functions\when( 'get_search_query' )->alias(
			static function () {
				return self::$search_query;
			}
		);
		Functions\when( 'get_the_archive_title' )->alias(
			static function () {
				return self::$archive_title;
			}
		);
		Functions\when( 'post_type_archive_title' )->alias(
			static function () {
				return self::$post_type_archive_label;
			}
		);
		Functions\when( 'get_query_var' )->alias(
			static function ( $var ) {
				return self::$query_vars[ $var ] ?? 0;
			}
		);

		// -----------------------------------------------------------------
		// Sanitizing & escaping.
		// -----------------------------------------------------------------
		Functions\when( 'esc_attr' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
			}
		);
		Functions\when( 'esc_html' )->alias(
			static function ( $text ) {
				return htmlspecialchars( (string) $text, ENT_QUOTES, 'UTF-8', false );
			}
		);
		Functions\when( 'esc_url' )->alias(
			static function ( $url ) {
				$url = trim( (string) $url );

				return preg_match( '#^(javascript|data):#i', $url ) ? '' : $url;
			}
		);
		Functions\when( 'esc_url_raw' )->alias(
			static function ( $url ) {
				return trim( (string) $url );
			}
		);
		Functions\when( 'sanitize_text_field' )->alias(
			static function ( $str ) {
				$str = (string) $str;
				$str = preg_replace( '/<[^>]*>/s', '', $str );
				$str = preg_replace( '/[\r\n\t ]+/', ' ', $str );

				return trim( $str );
			}
		);
		Functions\when( 'sanitize_textarea_field' )->alias(
			static function ( $str ) {
				$str = (string) $str;
				$str = preg_replace( '/<[^>]*>/s', '', $str );

				return trim( $str );
			}
		);
		Functions\when( 'sanitize_key' )->alias(
			static function ( $key ) {
				return strtolower( preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) ) );
			}
		);
		Functions\when( 'sanitize_title' )->alias(
			static function ( $title ) {
				$title = strtolower( trim( (string) $title ) );
				$title = preg_replace( '/[^a-z0-9]+/', '-', $title );

				return trim( $title, '-' );
			}
		);
		Functions\when( 'wp_kses_post' )->alias(
			static function ( $content ) {
				return (string) $content;
			}
		);
		Functions\when( 'wp_strip_all_tags' )->alias(
			static function ( $string, $remove_breaks = false ) {
				$string = (string) preg_replace( '@<(script|style)[^>]*?>.*?</\\1>@si', '', (string) $string );
				$string = strip_tags( $string );

				if ( $remove_breaks ) {
					$string = preg_replace( '/[\r\n\t ]+/', ' ', $string );
				}

				return trim( $string );
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ) {
				if ( is_object( $args ) ) {
					$args = get_object_vars( $args );
				} elseif ( is_string( $args ) ) {
					parse_str( $args, $args_parsed );
					$args = $args_parsed;
				} elseif ( ! is_array( $args ) ) {
					$args = array();
				}

				return array_merge( (array) $defaults, (array) $args );
			}
		);
		Functions\when( 'wp_json_encode' )->alias(
			static function ( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			}
		);
		Functions\when( 'wp_list_pluck' )->alias(
			static function ( $list, $field ) {
				$values = array();
				foreach ( $list as $item ) {
					if ( is_object( $item ) && isset( $item->{$field} ) ) {
						$values[] = $item->{$field};
					} elseif ( is_array( $item ) && isset( $item[ $field ] ) ) {
						$values[] = $item[ $field ];
					}
				}

				return $values;
			}
		);
		Functions\when( 'wp_trim_words' )->alias(
			static function ( $text, $num_words = 55, $more = '&hellip;' ) {
				$text = trim( (string) $text );
				$words = preg_split( '/\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY );

				if ( count( $words ) <= $num_words ) {
					return $text;
				}

				return implode( ' ', array_slice( $words, 0, $num_words ) ) . $more;
			}
		);
		Functions\when( 'strip_shortcodes' )->alias(
			static function ( $content ) {
				return (string) preg_replace( '/\[\/?[a-z0-9_-]+[^\]]*\]/i', '', (string) $content );
			}
		);
		Functions\when( 'has_blocks' )->alias(
			static function ( $post ) {
				$content = ( $post instanceof \WP_Post ) ? $post->post_content : (string) $post;

				return false !== strpos( $content, '<!-- wp:' );
			}
		);
		Functions\when( 'do_blocks' )->alias(
			static function ( $content ) {
				return $content;
			}
		);
		Functions\when( 'do_shortcode' )->alias(
			static function ( $content ) {
				return $content;
			}
		);
		Functions\when( 'wpautop' )->alias(
			static function ( $content ) {
				return $content;
			}
		);
		Functions\when( 'is_protected_meta' )->alias(
			static function ( $key ) {
				return '_' === substr( (string) $key, 0, 1 );
			}
		);
		Functions\when( 'is_serialized' )->justReturn( false );
		Functions\when( 'maybe_unserialize' )->alias(
			static function ( $value ) {
				return $value;
			}
		);
		Functions\when( 'sanitize_hex_color' )->alias(
			static function ( $color ) {
				return is_string( $color ) && preg_match( '/^#([A-Fa-f0-9]{3}){1,2}$/', $color ) ? $color : '';
			}
		);

		// -----------------------------------------------------------------
		// Content helpers (author/date/excerpt/media).
		// -----------------------------------------------------------------
		Functions\when( 'get_the_excerpt' )->alias(
			static function ( $post = null ) {
				$post = self::resolve_post( $post );

				return $post ? $post->post_excerpt : '';
			}
		);
		Functions\when( 'get_the_date' )->alias(
			static function ( $format = '', $post = null ) {
				$post = self::resolve_post( $post );

				if ( ! $post || empty( $post->post_date ) ) {
					return '';
				}

				return date( $format ?: 'F j, Y', strtotime( $post->post_date ) );
			}
		);
		Functions\when( 'get_the_modified_date' )->alias(
			static function ( $format = '', $post = null ) {
				$post = self::resolve_post( $post );

				if ( ! $post || empty( $post->post_modified ) ) {
					return '';
				}

				return date( $format ?: 'F j, Y', strtotime( $post->post_modified ) );
			}
		);
		Functions\when( 'get_the_author_meta' )->alias(
			static function ( $field, $user_id = 0 ) {
				if ( 'display_name' === $field ) {
					return self::$authors[ (int) $user_id ] ?? '';
				}

				return '';
			}
		);
		Functions\when( 'get_the_author' )->justReturn( '' );
		Functions\when( 'get_the_tags' )->justReturn( null );
		Functions\when( 'get_the_category' )->justReturn( array() );
		Functions\when( 'term_description' )->alias(
			static function ( $term_id = 0 ) {
				return ( self::$queried_term && (int) $term_id === (int) self::$queried_term->term_id )
					? self::$queried_term->description
					: '';
			}
		);
		Functions\when( 'get_post_thumbnail_id' )->justReturn( 0 );
		Functions\when( 'wp_get_attachment_image_url' )->justReturn( false );
		Functions\when( 'get_the_post_thumbnail_url' )->justReturn( false );
		Functions\when( 'date_i18n' )->alias(
			static function ( $format ) {
				return date( $format );
			}
		);

		// -----------------------------------------------------------------
		// i18n.
		// -----------------------------------------------------------------
		Functions\when( '__' )->alias(
			static function ( $text ) {
				return $text;
			}
		);
		Functions\when( '_x' )->alias(
			static function ( $text ) {
				return $text;
			}
		);
		Functions\when( 'esc_html__' )->alias(
			static function ( $text ) {
				return $text;
			}
		);
		Functions\when( 'esc_attr__' )->alias(
			static function ( $text ) {
				return $text;
			}
		);
		Functions\when( '_n' )->alias(
			static function ( $single, $plural, $number ) {
				return 1 === (int) $number ? $single : $plural;
			}
		);

		$this->install_hook_stubs();
	}

	/**
	 * Install a functional filter/action registry.
	 *
	 * Unlike plain no-op stubs, callbacks registered in tests via
	 * add_filter()/add_action() actually run, letting tests exercise the
	 * plugin's filter surface end-to-end.
	 *
	 * @return void
	 */
	protected function install_hook_stubs(): void {
		Functions\when( 'add_filter' )->alias(
			static function ( $tag, $callback, $priority = 10, $accepted_args = 1 ) {
				self::$hooks[ $tag ][] = static function ( $value, ...$args ) use ( $callback, $accepted_args ) {
					$extra = array_slice( $args, 0, max( 0, (int) $accepted_args - 1 ) );

					return call_user_func_array( $callback, array_merge( array( $value ), $extra ) );
				};

				return true;
			}
		);
		Functions\when( 'has_filter' )->alias(
			static function ( $tag ) {
				return ! empty( self::$hooks[ $tag ] );
			}
		);
		Functions\when( 'apply_filters' )->alias(
			static function ( $tag, $value, ...$args ) {
				foreach ( self::$hooks[ $tag ] ?? array() as $callback ) {
					$value = call_user_func( $callback, $value, ...$args );
				}

				return $value;
			}
		);
		Functions\when( 'add_action' )->alias(
			static function ( $tag, $callback ) {
				self::$hooks[ $tag ][] = $callback;
				return true;
			}
		);
		Functions\when( 'has_action' )->alias(
			static function ( $tag ) {
				return ! empty( self::$hooks[ $tag ] );
			}
		);
		Functions\when( 'do_action' )->alias(
			static function ( $tag, ...$args ) {
				self::$fired_actions[ $tag ] = ( self::$fired_actions[ $tag ] ?? 0 ) + 1;

				foreach ( self::$hooks[ $tag ] ?? array() as $callback ) {
					call_user_func_array( $callback, $args );
				}
			}
		);
		Functions\when( 'did_action' )->alias(
			static function ( $tag ) {
				return self::$fired_actions[ $tag ] ?? 0;
			}
		);
	}

	/**
	 * Resolve a mixed post reference to a stored WP_Post.
	 *
	 * @param \WP_Post|int|null $post Post reference.
	 * @return \WP_Post|null
	 */
	protected static function resolve_post( $post ) {
		if ( $post instanceof \WP_Post ) {
			return $post;
		}

		if ( null === $post ) {
			return self::$posts[ self::$current_post_id ] ?? null;
		}

		$id = is_numeric( $post ) ? (int) $post : 0;

		return self::$posts[ $id ] ?? null;
	}

	/**
	 * Register a post into the in-memory stores and select it as the
	 * current singular context.
	 *
	 * @param array $overrides Post fields.
	 * @return \WP_Post
	 */
	protected function make_post( array $overrides = array() ): \WP_Post {
		$args = array_merge(
			array(
				'ID'           => 42,
				'post_title'   => 'Sample Post',
				'post_content' => '<p>Hello world content.</p>',
				'post_excerpt' => '',
				'post_type'    => 'post',
				'post_status'  => 'publish',
				'post_author'  => 1,
				'post_name'    => 'sample-post',
				'post_date'    => '2026-01-01 08:00:00',
			),
			$overrides
		);

		$post = new \WP_Post( $args );
		self::$posts[ $post->ID ] = $post;
		self::$context['singular'] = true;
		self::$current_post_id     = $post->ID;

		return $post;
	}

	/**
	 * Capture output emitted by an echo/printf-based method.
	 *
	 * @param callable $callback Callable to run.
	 * @return string Captured output.
	 */
	protected function capture_output( callable $callback ): string {
		ob_start();

		try {
			$callback();

			return (string) ob_get_clean();
		} catch ( \Throwable $throwable ) {
			ob_end_clean();
			throw $throwable;
		}
	}

	/**
	 * Set a single context flag.
	 *
	 * @param string $flag  Flag name.
	 * @param bool   $value Value.
	 * @return void
	 */
	protected function set_context( string $flag, bool $value ): void {
		self::$context[ $flag ] = $value;
	}
}
