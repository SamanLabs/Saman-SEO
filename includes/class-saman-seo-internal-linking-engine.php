<?php
/**
 * Internal linking runtime engine.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Internal_Linking;

use DOMDocument;
use DOMElement;
use DOMNode;
use WP_Post;

defined( 'ABSPATH' ) || exit;

/**
 * Applies internal linking rules at render time and powers previews.
 */
class Engine {

	/**
	 * Chunk threshold before splitting long documents.
	 */
	private const CHUNK_THRESHOLD = 120000;

	/**
	 * Cache lifetime for processed content.
	 */
	private const CACHE_TTL = HOUR_IN_SECONDS;

	/**
	 * Data store.
	 *
	 * @var Repository
	 */
	private $repository;

	/**
	 * Cached settings.
	 *
	 * @var array|null
	 */
	private $settings;

	/**
	 * Cached site host.
	 *
	 * @var string|null
	 */
	private $site_host;

	/**
	 * Replacement report used for previews.
	 *
	 * @var array<int,array>
	 */
	private $report = [];

	/**
	 * Constructor.
	 *
	 * @param Repository $repository Repository instance.
	 */
	public function __construct( Repository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Filter frontend content.
	 *
	 * @param string $content Content string.
	 * @param array  $args    Context args (post, url, context).
	 *
	 * @return string
	 */
	public function filter( $content, array $args = [] ) {
		if ( ! is_string( $content ) || '' === trim( $content ) ) {
			return $content;
		}

		$context_type = $args['context'] ?? 'content';

		if ( 'content' === $context_type ) {
			if ( is_admin() && ! wp_doing_ajax() ) {
				return $content;
			}

			if ( is_feed() ) {
				return $content;
			}
		}

		$post = $args['post'] ?? get_post();
		$url  = $args['url'] ?? ( $post instanceof WP_Post ? get_permalink( $post ) : '' );

		$rules = $this->get_prepared_rules( $post, $url, $context_type );
		if ( empty( $rules ) ) {
			return $content;
		}

		$cache_enabled = ( 'content' === $context_type ) && ! empty( $this->get_settings()['cache_rendered_content'] ) && $post instanceof WP_Post;
		if ( $cache_enabled ) {
			$cache_key = $this->get_cache_key( $post->ID );
			$cached    = get_transient( $cache_key );

			if ( is_array( $cached ) && isset( $cached['version'], $cached['content'] ) && $cached['version'] === $this->repository->get_version() ) {
				return $cached['content'];
			}
		}

		$this->reset_report();
		$state  = $this->bootstrap_state( $rules );
		$chunks = $this->split_content( $content );
		$output = '';

		foreach ( $chunks as $chunk ) {
			$output .= $this->process_chunk( $chunk, $rules, $state, $post, $url, $context_type );
		}

		if ( $cache_enabled ) {
			set_transient(
				$cache_key,
				[
					'content' => $output,
					'version' => $this->repository->get_version(),
				],
				self::CACHE_TTL
			);
		}

		return $output;
	}

	/**
	 * Generate preview output for an unsaved rule.
	 *
	 * @param array $rule    Sanitized rule data.
	 * @param array $context {
	 *     @type string  $content Raw HTML.
	 *     @type WP_Post $post    Optional post context.
	 *     @type string  $url     URL for scope checks.
	 *     @type string  $context Context key (content|widget).
	 * }
	 *
	 * @return array{content:string,replacements:array<int,array{keyword:string,rule_id:string,rule:string,url:string,count:int}>}
	 */
	public function preview( array $rule, array $context ) {
		$content = $context['content'] ?? '';
		if ( '' === trim( (string) $content ) ) {
			return [ 'content' => $content, 'replacements' => [] ];
		}

		$post = $context['post'] ?? null;
		$url  = $context['url'] ?? ( $post instanceof WP_Post ? get_permalink( $post ) : '' );
		$type = $context['context'] ?? 'content';

		$categories = $this->repository->get_categories();
		$templates  = $this->repository->get_templates();
		$runtime    = $this->prepare_runtime_rule( $rule, $post, $url, $type, $categories, $templates );

		if ( ! $runtime ) {
			return [ 'content' => $content, 'replacements' => [] ];
		}

		$this->reset_report();
		$state = $this->bootstrap_state( [ $runtime['id'] => $runtime ] );
		$html  = $this->process_chunk( $content, [ $runtime['id'] => $runtime ], $state, $post, $url, $type );

		return [
			'content'      => $html,
			'replacements' => $this->summarize_report(),
		];
	}

	/**
	 * Memoized settings.
	 *
	 * @return array
	 */
	private function get_settings() {
		if ( null === $this->settings ) {
			$this->settings = $this->repository->get_settings();
		}

		return $this->settings;
	}

	/**
	 * Return site host for internal link detection.
	 *
	 * @return string
	 */
	private function get_site_host() {
		if ( null === $this->site_host ) {
			$host = wp_parse_url( home_url(), PHP_URL_HOST );
			$this->site_host = strtolower( $host ?? '' );
		}

		return $this->site_host;
	}

	/**
	 * Build runtime-ready rules.
	 *
	 * @param WP_Post|null $post Post context.
	 * @param string       $url  Requested URL.
	 * @param string       $type Context key.
	 *
	 * @return array<string,array>
	 */
	private function get_prepared_rules( $post, $url, $type ) {
		$rules = $this->repository->get_rules();
		if ( empty( $rules ) ) {
			return [];
		}

		$categories = $this->repository->get_categories();
		$templates  = $this->repository->get_templates();
		$prepared   = [];
		$post_type  = $post instanceof WP_Post ? $post->post_type : null;

		foreach ( $rules as $rule ) {
			if ( 'active' !== ( $rule['status'] ?? 'inactive' ) ) {
				continue;
			}

			$runtime = $this->prepare_runtime_rule( $rule, $post, $url, $type, $categories, $templates );
			if ( ! $runtime ) {
				continue;
			}

			if ( ! $this->matches_scope( $runtime, $post_type, $url, $type ) ) {
				continue;
			}

			$prepared[ $runtime['id'] ] = $runtime;
		}

		uasort(
			$prepared,
			static function ( $a, $b ) {
				if ( $a['priority'] === $b['priority'] ) {
					return strcmp( $a['title'], $b['title'] );
				}

				return ( $a['priority'] > $b['priority'] ) ? -1 : 1;
			}
		);

		return $prepared;
	}

	/**
	 * Convert a stored rule to a runtime representation.
	 *
	 * @param array        $rule       Stored rule.
	 * @param WP_Post|null $post       Post context.
	 * @param string       $url        URL.
	 * @param string       $type       Context key.
	 * @param array        $categories Category list.
	 * @param array        $templates  Template list.
	 *
	 * @return array|null
	 */
	private function prepare_runtime_rule( array $rule, $post, $url, $type, array $categories, array $templates ) {
		$keywords = array_values( array_filter( $rule['keywords'] ?? [] ) );
		if ( empty( $keywords ) ) {
			return null;
		}

		$destination = $rule['destination'] ?? [];
		$resolved_url = '';
		$is_internal = true;

		if ( 'post' === ( $destination['type'] ?? 'post' ) ) {
			$target_post = get_post( $destination['post'] ?? 0 );
			if ( ! $target_post instanceof WP_Post ) {
				return null;
			}

			$resolved_url = get_permalink( $target_post );
		} else {
			$resolved_url = $destination['url'] ?? '';
		}

		$resolved_url = trim( (string) $resolved_url );
		if ( '' === $resolved_url ) {
			return null;
		}

		$parsed = wp_parse_url( $resolved_url );
		if ( isset( $parsed['host'] ) ) {
			$is_internal = ( strtolower( $parsed['host'] ) === $this->get_site_host() );
		}

		$category_id      = $rule['category'] ?? '';
		$category         = $categories[ $category_id ] ?? null;
		$category_cap     = (int) ( $category['category_cap'] ?? 0 );
		$category_default = $category['default_utm'] ?? '';

		$template_id = $rule['utm_template'] ?? 'inherit';
		if ( 'inherit' === $template_id ) {
			$template_id = $category_default;
		}

		$template = ( $template_id && isset( $templates[ $template_id ] ) ) ? $templates[ $template_id ] : null;

		$settings = $this->get_settings();

		usort(
			$keywords,
			static function ( $a, $b ) {
				return mb_strlen( $b, 'UTF-8' ) <=> mb_strlen( $a, 'UTF-8' );
			}
		);

		$limit_page = $rule['limits']['max_page'] ?? null;
		if ( '' === $limit_page ) {
			$limit_page = null;
		}
		if ( null === $limit_page ) {
			$limit_page = $settings['default_max_links_per_page'] ?? 1;
		}

		$limit_page = (int) $limit_page;
		if ( $limit_page < 0 ) {
			$limit_page = 0;
		}

		if ( 0 === $limit_page ) {
			return null;
		}

		$limit_block = $rule['limits']['max_block'] ?? null;
		$limit_block = ( null === $limit_block ) ? null : (int) $limit_block;
		if ( null !== $limit_block && $limit_block < 0 ) {
			$limit_block = 0;
		}

		$placement          = $rule['placement'] ?? [];
		$headings_behavior  = $placement['headings'] ?? 'none';
		$heading_levels     = $placement['heading_levels'] ?? [];
		$settings_headings  = $settings['default_heading_levels'] ?? [];

		if ( empty( $heading_levels ) && 'selected' === $headings_behavior ) {
			$heading_levels = $settings_headings;
		}

		return [
			'id'        => $rule['id'],
			'title'     => $rule['title'] ?? '',
			'keywords'  => $keywords,
			'destination' => [
				'url'         => $resolved_url,
				'is_internal' => $is_internal,
			],
			'attributes' => $rule['attributes'] ?? [],
			'limits'     => [
				'page'     => $limit_page,
				'block'    => $limit_block,
				'category' => $category_cap,
			],
			'placement'  => [
				'headings'       => $headings_behavior,
				'heading_levels' => array_map( 'strtolower', (array) $heading_levels ),
				'paragraphs'     => ! empty( $placement['paragraphs'] ),
				'lists'          => ! empty( $placement['lists'] ),
				'captions'       => ! empty( $placement['captions'] ),
				'widgets'        => ! empty( $placement['widgets'] ),
			],
			'scope'      => $rule['scope'] ?? [ 'post_types' => [], 'whitelist' => [], 'blacklist' => [] ],
			'utm'        => [
				'apply_to' => $rule['utm_apply_to'] ?? 'both',
				'template' => $template,
			],
			'category'   => $category_id,
			'priority'   => (int) ( $rule['priority'] ?? 0 ),
		];
	}

	/**
	 * Determine if rule applies to current request.
	 *
	 * @param array       $rule      Runtime rule.
	 * @param string|null $post_type Current post type.
	 * @param string      $url       URL.
	 * @param string      $context   Context key.
	 *
	 * @return bool
	 */
	private function matches_scope( array $rule, $post_type, $url, $context ) {
		if ( 'widget' === $context && empty( $rule['placement']['widgets'] ) ) {
			return false;
		}

		$scope      = $rule['scope'] ?? [];
		$post_types = $scope['post_types'] ?? [];

		if ( ! empty( $post_types ) ) {
			if ( $post_type && ! in_array( $post_type, $post_types, true ) ) {
				return false;
			}

			if ( ! $post_type && 'widget' !== $context ) {
				return false;
			}
		}

		$normalized = $this->normalize_url_for_match( $url );
		$whitelist  = array_filter( $scope['whitelist'] ?? [] );

		if ( ! empty( $whitelist ) ) {
			$allowed = false;
			foreach ( $whitelist as $pattern ) {
				if ( $this->url_matches_pattern( $normalized, $pattern ) ) {
					$allowed = true;
					break;
				}
			}

			if ( ! $allowed ) {
				return false;
			}
		}

		$blacklist = array_filter( $scope['blacklist'] ?? [] );
		foreach ( $blacklist as $pattern ) {
			if ( $this->url_matches_pattern( $normalized, $pattern ) ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Normalize URL for wildcard matching.
	 *
	 * @param string $url URL.
	 *
	 * @return string
	 */
	private function normalize_url_for_match( $url ) {
		if ( empty( $url ) ) {
			return '';
		}

		$parts = wp_parse_url( $url );
		if ( isset( $parts['scheme'] ) && ! in_array( strtolower( $parts['scheme'] ), [ 'http', 'https' ], true ) ) {
			return $url;
		}
		$path  = $parts['path'] ?? '';
		$query = isset( $parts['query'] ) ? '?' . $parts['query'] : '';

		if ( '' === $path ) {
			$path = '/';
		}

		return strtolower( $path . $query );
	}

	/**
	 * Wildcard pattern match helper.
	 *
	 * @param string $url     Normalized URL.
	 * @param string $pattern Pattern with * and **.
	 *
	 * @return bool
	 */
	private function url_matches_pattern( $url, $pattern ) {
		$pattern = trim( (string) $pattern );
		if ( '' === $pattern ) {
			return false;
		}

		if ( 0 === strpos( $pattern, 'http' ) ) {
			$pattern = $this->normalize_url_for_match( $pattern );
		}

		$pattern = strtolower( $pattern );
		$escaped = preg_quote( $pattern, '/' );
		$escaped = str_replace( '\*\*', '___DOUBLE___', $escaped );
		$escaped = str_replace( '\*', '[^/]*', $escaped );
		$escaped = str_replace( '___DOUBLE___', '.*', $escaped );
		$regex   = '#^' . $escaped . '$#i';

		return (bool) preg_match( $regex, $url );
	}

	/**
	 * Split long documents into smaller chunks.
	 *
	 * @param string $content HTML.
	 *
	 * @return array<int,string>
	 */
	private function split_content( $content ) {
		$settings = $this->get_settings();
		if ( empty( $settings['chunk_long_documents'] ) ) {
			return [ $content ];
		}

		if ( mb_strlen( $content, 'UTF-8' ) <= self::CHUNK_THRESHOLD ) {
			return [ $content ];
		}

		$pieces = preg_split( '/(<\/p>)/i', $content, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
		if ( empty( $pieces ) ) {
			return [ $content ];
		}

		$chunks = [];
		$current = '';

		foreach ( $pieces as $piece ) {
			$current .= $piece;
			if ( mb_strlen( $current, 'UTF-8' ) >= ( self::CHUNK_THRESHOLD / 2 ) ) {
				$chunks[] = $current;
				$current  = '';
			}
		}

		if ( '' !== $current ) {
			$chunks[] = $current;
		}

		return $chunks;
	}

	/**
	 * Initialize counters per rule/category.
	 *
	 * @param array $rules Runtime rules.
	 *
	 * @return array
	 */
	private function bootstrap_state( array $rules ) {
		$state = [
			'rules'           => [],
			'category_caps'   => [],
			'category_counts' => [],
		];

		foreach ( $rules as $rule ) {
			$category_id  = $rule['category'] ?? '';
			$category_cap = (int) ( $rule['limits']['category'] ?? 0 );

			if ( $category_id && $category_cap > 0 && ! isset( $state['category_caps'][ $category_id ] ) ) {
				$state['category_caps'][ $category_id ]   = $category_cap;
				$state['category_counts'][ $category_id ] = 0;
			}

			$state['rules'][ $rule['id'] ] = [
				'remaining'   => (int) $rule['limits']['page'],
				'block_limit' => $rule['limits']['block'],
				'block_counts'=> [],
				'category'    => $category_id,
			];
		}

		return $state;
	}

	/**
	 * Process one chunk of HTML.
	 *
	 * @param string      $chunk   HTML chunk.
	 * @param array       $rules   Runtime rules.
	 * @param array       $state   Mutable counters.
	 * @param WP_Post|null $post   Post context.
	 * @param string      $url     URL context.
	 * @param string      $context Context key.
	 *
	 * @return string
	 */
	private function process_chunk( $chunk, array $rules, array &$state, $post, $url, $context ) {
		libxml_use_internal_errors( true );
		$dom = new DOMDocument();
		$wrapper = '<div id="saman-seo-link-root">' . $chunk . '</div>';
		$dom->loadHTML( '<?xml encoding="utf-8" ?>' . $wrapper, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		$root = $dom->getElementById( 'saman-seo-link-root' );

		if ( ! $root instanceof DOMElement ) {
			return $chunk;
		}

		foreach ( $rules as $rule ) {
			$this->apply_rule_to_dom( $root, $rule, $state, $post, $url, $context );
		}

		$output = '';
		foreach ( iterator_to_array( $root->childNodes ) as $child ) {
			$output .= $dom->saveHTML( $child );
		}

		libxml_clear_errors();

		return $output;
	}

	/**
	 * Apply a single rule to DOM tree.
	 *
	 * @param DOMNode     $root    Root node.
	 * @param array       $rule    Runtime rule.
	 * @param array       $state   Counters.
	 * @param WP_Post|null $post   Post context.
	 * @param string      $url     URL.
	 * @param string      $context Context key.
	 */
	private function apply_rule_to_dom( DOMNode $root, array $rule, array &$state, $post, $url, $context ) {
		$rule_state = &$state['rules'][ $rule['id'] ];
		if ( $rule_state['remaining'] <= 0 ) {
			return;
		}

		foreach ( iterator_to_array( $root->childNodes ) as $child ) {
			$this->traverse_node( $child, $rule, $state, $post, $url, $context, $child );
			if ( $rule_state['remaining'] <= 0 ) {
				break;
			}
		}
	}

	/**
	 * Depth-first traversal applying replacements.
	 *
	 * @param DOMNode      $node    Current node.
	 * @param array        $rule    Runtime rule.
	 * @param array        $state   Counters.
	 * @param WP_Post|null $post    Post context.
	 * @param string       $url     URL.
	 * @param string       $context Context key.
	 * @param DOMNode|null $block   Top-level block reference.
	 */
	private function traverse_node( DOMNode $node, array $rule, array &$state, $post, $url, $context, $block = null ) {
		if ( $state['rules'][ $rule['id'] ]['remaining'] <= 0 ) {
			return;
		}

		if ( XML_TEXT_NODE === $node->nodeType ) {
			$this->replace_in_text_node( $node, $rule, $state, $post, $url, $context, $block );
			return;
		}

		if ( XML_ELEMENT_NODE !== $node->nodeType ) {
			return;
		}

		$tag = strtolower( $node->nodeName );

		// Skip disallowed containers entirely.
		if ( in_array( $tag, [ 'a', 'code', 'pre', 'style', 'script' ], true ) ) {
			return;
		}

		$children = iterator_to_array( $node->childNodes );

		foreach ( $children as $child ) {
			$this->traverse_node( $child, $rule, $state, $post, $url, $context, $block );
			if ( $state['rules'][ $rule['id'] ]['remaining'] <= 0 ) {
				break;
			}
		}
	}

	/**
	 * Replace keywords inside a text node.
	 *
	 * @param DOMNode      $node    Text node.
	 * @param array        $rule    Runtime rule.
	 * @param array        $state   Counters.
	 * @param WP_Post|null $post    Post context.
	 * @param string       $url     URL.
	 * @param string       $context Context key.
	 * @param DOMNode|null $block   Block reference.
	 */
	private function replace_in_text_node( DOMNode $node, array $rule, array &$state, $post, $url, $context, $block ) {
		if ( ! $node->parentNode ) {
			return;
		}

		$text = $node->nodeValue;
		if ( '' === trim( $text ) ) {
			return;
		}

		$node_context = $this->get_node_context( $node );

		if ( 'widget' !== $context ) {
			if ( $node_context['is_heading'] ) {
				$behavior = $rule['placement']['headings'];
				if ( 'none' === $behavior ) {
					return;
				}

				if ( 'selected' === $behavior && ! in_array( $node_context['heading_level'], $rule['placement']['heading_levels'], true ) ) {
					return;
				}
			} else {
				if ( $node_context['in_paragraph'] && empty( $rule['placement']['paragraphs'] ) ) {
					return;
				}

				if ( $node_context['in_list'] && empty( $rule['placement']['lists'] ) ) {
					return;
				}

				if ( $node_context['in_caption'] && empty( $rule['placement']['captions'] ) ) {
					return;
				}
			}
		}

		$block_key = $block instanceof DOMNode ? spl_object_hash( $block ) : 'root';
		$rule_state = &$state['rules'][ $rule['id'] ];

		if ( null !== $rule_state['block_limit'] ) {
			$count = $rule_state['block_counts'][ $block_key ] ?? 0;
			if ( $count >= $rule_state['block_limit'] ) {
				return;
			}
		}

		$category_id = $rule_state['category'];
		if ( $category_id && isset( $state['category_caps'][ $category_id ] ) ) {
			if ( $state['category_counts'][ $category_id ] >= $state['category_caps'][ $category_id ] ) {
				return;
			}
		}

		$offset = 0;
		$encoding = 'UTF-8';

		while ( $rule_state['remaining'] > 0 ) {
			$match = $this->find_next_match( $text, $rule['keywords'], $offset );
			if ( ! $match ) {
				break;
			}

			$before = mb_substr( $text, 0, $match['start'], $encoding );
			$after  = mb_substr( $text, $match['start'] + $match['length'], null, $encoding );

			if ( '' !== $before ) {
				$node->parentNode->insertBefore( $node->ownerDocument->createTextNode( $before ), $node );
			}

			$link = $this->create_link_node( $node->ownerDocument, $match['text'], $rule, $match['keyword'], $post, $url );
			$node->parentNode->insertBefore( $link, $node );

			$text = $after;
			$node->nodeValue = $text;

			$rule_state['remaining']--;
			if ( $category_id && isset( $state['category_counts'][ $category_id ] ) ) {
				$state['category_counts'][ $category_id ]++;
			}

			if ( null !== $rule_state['block_limit'] ) {
				$rule_state['block_counts'][ $block_key ] = ( $rule_state['block_counts'][ $block_key ] ?? 0 ) + 1;
			}

			$this->record_replacement( $rule, $match['keyword'], $link->getAttribute( 'href' ) );

			if ( '' === $text ) {
				break;
			}

			$offset = 0;
		}
	}

	/**
	 * Find next keyword match within text.
	 *
	 * @param string $text     Haystack.
	 * @param array  $keywords Ordered keywords.
	 * @param int    $start    Offset.
	 *
	 * @return array|null
	 */
	private function find_next_match( $text, array $keywords, $start ) {
		$encoding = 'UTF-8';
		$settings = $this->get_settings();
		$subject  = $settings['normalize_accents'] ? remove_accents( $text ) : $text;
		$subject_lower = mb_strtolower( $subject, $encoding );
		$best = null;

		foreach ( $keywords as $keyword ) {
			$needle       = $settings['normalize_accents'] ? remove_accents( $keyword ) : $keyword;
			$needle_lower = mb_strtolower( $needle, $encoding );
			$offset       = $start;

			while ( true ) {
				$pos = mb_stripos( $subject_lower, $needle_lower, $offset, $encoding );
				if ( false === $pos ) {
					break;
				}

				$before_char = ( $pos > 0 ) ? mb_substr( $subject, $pos - 1, 1, $encoding ) : '';
				$after_char  = mb_substr( $subject, $pos + mb_strlen( $needle_lower, $encoding ), 1, $encoding );
				$boundary_fail = false;

				if ( ! empty( $settings['prefer_word_boundaries'] ) ) {
					if ( '' !== $before_char && preg_match( '/[\p{L}\p{N}_]/u', $before_char ) ) {
						$boundary_fail = true;
					}

					if ( '' !== $after_char && preg_match( '/[\p{L}\p{N}_]/u', $after_char ) ) {
						$boundary_fail = true;
					}
				}

				if ( $boundary_fail ) {
					$offset = $pos + 1;
					continue;
				}

				$length = mb_strlen( $keyword, $encoding );
				if ( ! $best || $pos < $best['start'] || ( $pos === $best['start'] && $length > $best['length'] ) ) {
					$best = [
						'keyword' => $keyword,
						'start'   => $pos,
						'length'  => $length,
					];

					if ( 0 === $pos ) {
						break 2;
					}
				}

				break;
			}
		}

		if ( ! $best ) {
			return null;
		}

		return [
			'keyword' => $best['keyword'],
			'start'   => $best['start'],
			'length'  => $best['length'],
			'text'    => mb_substr( $text, $best['start'], $best['length'], $encoding ),
		];
	}

	/**
	 * Build link node with applied attributes/UTMs.
	 *
	 * @param DOMDocument $dom     Document.
	 * @param string      $text    Anchor text.
	 * @param array       $rule    Runtime rule.
	 * @param string      $keyword Matched keyword.
	 * @param WP_Post|null $post   Post context.
	 * @param string      $url     URL context.
	 *
	 * @return DOMElement
	 */
	private function create_link_node( DOMDocument $dom, $text, array $rule, $keyword, $post, $url ) {
		$link = $dom->createElement( 'a' );
		$href = $this->apply_utms_to_url( $rule['destination']['url'], $rule, $keyword, $post );
		$link->setAttribute( 'href', $href );
		$link->appendChild( $dom->createTextNode( $text ) );

		$attributes = $rule['attributes'] ?? [];

		if ( ! empty( $attributes['title'] ) ) {
			$link->setAttribute( 'title', $attributes['title'] );
		}

		$rel_parts = [];
		if ( ! empty( $attributes['nofollow'] ) ) {
			$rel_parts[] = 'nofollow';
		}

		if ( ! empty( $attributes['new_tab'] ) ) {
			$link->setAttribute( 'target', '_blank' );
			$rel_parts[] = 'noopener';
		}

		if ( ! empty( $rel_parts ) ) {
			$link->setAttribute( 'rel', implode( ' ', array_unique( $rel_parts ) ) );
		}

		return $link;
	}

	/**
	 * Apply UTMs (if template exists + matches target).
	 *
	 * @param string      $url     Destination.
	 * @param array       $rule    Rule.
	 * @param string      $keyword Keyword.
	 * @param WP_Post|null $post   Post context.
	 *
	 * @return string
	 */
	private function apply_utms_to_url( $url, array $rule, $keyword, $post ) {
		$template = $rule['utm']['template'];
		if ( ! $template ) {
			return $url;
		}

		$is_internal = $rule['destination']['is_internal'];
		if ( ! $this->should_apply_utms( $rule['utm']['apply_to'], $template['apply_to'] ?? 'both', $is_internal ) ) {
			return $url;
		}

		$parts = wp_parse_url( $url );
		$query = [];
		if ( ! empty( $parts['query'] ) ) {
			parse_str( $parts['query'], $query );
		}

		$params = [
			'utm_source'   => $template['utm_source'] ?? '',
			'utm_medium'   => $template['utm_medium'] ?? '',
			'utm_campaign' => $template['utm_campaign'] ?? '',
			'utm_term'     => $template['utm_term'] ?? '',
			'utm_content'  => $template['utm_content'] ?? '',
		];

		$tokens = $this->build_token_map( $rule, $keyword, $post );
		foreach ( $params as $key => $value ) {
			if ( '' === $value ) {
				unset( $params[ $key ] );
				continue;
			}

			$params[ $key ] = strtr( $value, $tokens );
		}

		$mode = $template['append_mode'] ?? 'append_if_missing';

		foreach ( $params as $param => $value ) {
			if ( '' === $value ) {
				continue;
			}

			$exists = array_key_exists( $param, $query );

			if ( 'never' === $mode && $exists ) {
				continue;
			}

			if ( 'append_if_missing' === $mode && $exists ) {
				continue;
			}

			$query[ $param ] = $value;
		}

		$fragment = isset( $parts['fragment'] ) ? '#' . $parts['fragment'] : '';
		$base     = $url;

		if ( ! empty( $parts['scheme'] ) ) {
			$host = $parts['host'] ?? '';
			$port = isset( $parts['port'] ) ? ':' . $parts['port'] : '';
			$path = $parts['path'] ?? '';
			$base = sprintf( '%s://%s%s%s', $parts['scheme'], $host, $port, $path );
		} else {
			$base = $parts['path'] ?? $url;
		}

		$built = $query ? $base . '?' . http_build_query( $query ) : $base;
		return $built . $fragment;
	}

	/**
	 * Determine whether UTMs should apply based on rule/template settings.
	 *
	 * @param string $rule_target     Rule-level apply_to.
	 * @param string $template_target Template-level apply_to.
	 * @param bool   $is_internal     Destination scope.
	 *
	 * @return bool
	 */
	private function should_apply_utms( $rule_target, $template_target, $is_internal ) {
		$rule_ok = $this->matches_apply_to( $rule_target, $is_internal );
		$template_ok = $this->matches_apply_to( $template_target, $is_internal );

		return $rule_ok && $template_ok;
	}

	/**
	 * Helper to evaluate apply_to.
	 *
	 * @param string $target      Target value.
	 * @param bool   $is_internal Internal flag.
	 *
	 * @return bool
	 */
	private function matches_apply_to( $target, $is_internal ) {
		switch ( $target ) {
			case 'internal':
				return (bool) $is_internal;
			case 'external':
				return ! $is_internal;
			default:
				return true;
		}
	}

	/**
	 * Build token replacements for UTMs.
	 *
	 * @param array       $rule    Runtime rule.
	 * @param string      $keyword Matched keyword.
	 * @param WP_Post|null $post   Post context.
	 *
	 * @return array<string,string>
	 */
	private function build_token_map( array $rule, $keyword, $post ) {
		$post   = $post instanceof WP_Post ? $post : null;
		$author = $post ? get_the_author_meta( 'display_name', $post->post_author ) : '';
		$primary_cat = '';

		if ( $post ) {
			$categories = get_the_category( $post->ID );
			if ( ! empty( $categories ) && ! is_wp_error( $categories ) ) {
				$primary_cat = $categories[0]->name;
			}
		}

		$tokens = [
			'{keyword}'          => $keyword,
			'{rule_id}'          => $rule['id'],
			'{site_name}'        => get_bloginfo( 'name' ),
			'{post_id}'          => $post ? (string) $post->ID : '',
			'{post_slug}'        => $post ? $post->post_name : '',
			'{post_type}'        => $post ? $post->post_type : '',
			'{post_title}'       => $post ? $post->post_title : '',
			'{primary_category}' => $primary_cat,
			'{author}'          => $author,
		];

		// Support {date:format} custom tokens.
		$tokens = array_merge( $tokens, $this->build_date_tokens() );

		return $tokens;
	}

	/**
	 * Build {date:format} tokens lazily.
	 *
	 * @return array<string,string>
	 */
	private function build_date_tokens() {
		return [
			'{date:Ymd}' => date_i18n( 'Ymd', current_time( 'timestamp' ) ),
		];
	}

	/**
	 * Track replacements for previews.
	 *
	 * @param array  $rule    Runtime rule.
	 * @param string $keyword Keyword.
	 * @param string $url     Final URL.
	 */
	private function record_replacement( array $rule, $keyword, $url ) {
		$this->report[] = [
			'rule_id' => $rule['id'],
			'rule'    => $rule['title'],
			'keyword' => $keyword,
			'url'     => $url,
		];
	}

	/**
	 * Reset report store.
	 */
	private function reset_report() {
		$this->report = [];
	}

	/**
	 * Summarize recorded replacements.
	 *
	 * @return array<int,array>
	 */
	private function summarize_report() {
		if ( empty( $this->report ) ) {
			return [];
		}

		$summary = [];
		foreach ( $this->report as $entry ) {
			$key = $entry['rule_id'] . '|' . strtolower( $entry['keyword'] ) . '|' . $entry['url'];
			if ( ! isset( $summary[ $key ] ) ) {
				$summary[ $key ] = [
					'rule_id' => $entry['rule_id'],
					'rule'    => $entry['rule'],
					'keyword' => $entry['keyword'],
					'url'     => $entry['url'],
					'count'   => 0,
				];
			}

			$summary[ $key ]['count']++;
		}

		return array_values( $summary );
	}

	/**
	 * Build cache key per post.
	 *
	 * @param int $post_id Post ID.
	 *
	 * @return string
	 */
	private function get_cache_key( $post_id ) {
		return 'SAMAN_SEO_linked_' . absint( $post_id );
	}

	/**
	 * Inspect node context for placement checks.
	 *
	 * @param DOMNode $node Node.
	 *
	 * @return array{
	 *     is_heading:bool,
	 *     heading_level:string|null,
	 *     in_paragraph:bool,
	 *     in_list:bool,
	 *     in_caption:bool
	 * }
	 */
	private function get_node_context( DOMNode $node ) {
		$context = [
			'is_heading'    => false,
			'heading_level' => null,
			'in_paragraph'  => false,
			'in_list'       => false,
			'in_caption'    => false,
		];

		$parent = $node->parentNode;
		while ( $parent instanceof DOMNode ) {
			if ( XML_ELEMENT_NODE === $parent->nodeType ) {
				$tag = strtolower( $parent->nodeName );

				if ( in_array( $tag, [ 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ], true ) ) {
					$context['is_heading']    = true;
					$context['heading_level'] = $tag;
					break;
				}

				if ( 'p' === $tag ) {
					$context['in_paragraph'] = true;
				}

				if ( in_array( $tag, [ 'ul', 'ol', 'li' ], true ) ) {
					$context['in_list'] = true;
				}

				if ( 'figcaption' === $tag ) {
					$context['in_caption'] = true;
				}
			}

			$parent = $parent->parentNode;
		}

		return $context;
	}
}
