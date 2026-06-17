<?php
/**
 * Yoast SEO compatibility bridge.
 *
 * Lets a site move from Yoast SEO to Saman SEO without losing any data. When a
 * post (or a site-wide setting) has no Saman value yet, the value left behind by
 * Yoast is served at read time instead — from Yoast's `_yoast_wpseo_*` post meta
 * and its `wpseo_*` options. Nothing is written, so the switch is fully
 * reversible: keep Yoast's rows in the database (deactivate Yoast, or delete its
 * plugin files, but do NOT use Yoast's own "Delete" action — that purges the very
 * data this bridge reads).
 *
 * Saman always wins. The bridge only fills values that are still empty, so the
 * moment an editor saves a field in the Saman meta box, or a setting is saved on
 * the Saman settings screen, that value takes over and the Yoast fallback stops
 * applying to it. The bridged values also surface in the editor, so the previous
 * titles and descriptions are visible (and editable) rather than blank.
 *
 * The bridge is inert on sites that never used Yoast: it only adds its hooks when
 * Yoast data is detected in the database.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Reads leftover Yoast SEO data and serves it through Saman when Saman is empty.
 */
class Yoast_Bridge {

	/**
	 * Yoast option that signals a Yoast install (present whether Yoast is active
	 * or merely deactivated, since plugin options survive deactivation).
	 *
	 * @var string
	 */
	const SIGNAL_OPTION = 'wpseo_titles';

	/**
	 * Per-request cache of the merged meta array, keyed by post ID.
	 * A value of false means "nothing to contribute for this post".
	 *
	 * @var array<int,array|false>
	 */
	private $meta_cache = array();

	/**
	 * Re-entry guard so reading the stored Saman meta inside the
	 * `get_post_metadata` filter does not recurse.
	 *
	 * @var bool
	 */
	private $resolving = false;

	/**
	 * Map of Saman meta sub-key => Yoast post-meta key (simple value fields).
	 *
	 * @var array<string,string>
	 */
	private $meta_map = array(
		'title'           => '_yoast_wpseo_title',
		'description'     => '_yoast_wpseo_metadesc',
		'canonical'       => '_yoast_wpseo_canonical',
		'og_image'        => '_yoast_wpseo_opengraph-image',
		'focus_keyphrase' => '_yoast_wpseo_focuskw',
	);

	/**
	 * Register hooks (only when Yoast data is present).
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! $this->has_yoast_data() ) {
			return;
		}

		add_filter( 'get_post_metadata', array( $this, 'bridge_post_meta' ), 10, 3 );

		// Site-wide settings: fill from Yoast when Saman has no value of its own.
		// The title separator is baked directly into the bridged title templates
		// (translate_vars resolves %%sep%% to Yoast's actual character), so titles
		// match Yoast regardless of Saman's own separator option.
		$this->bridge_option( 'SAMAN_SEO_post_type_title_templates', 'yoast_title_templates' );
		$this->bridge_option( 'SAMAN_SEO_homepage_social_profiles', 'yoast_social_profiles' );
	}

	/**
	 * Whether this site carries Yoast data worth bridging.
	 *
	 * @return bool
	 */
	private function has_yoast_data() {
		static $has = null;

		if ( null === $has ) {
			$has = (bool) get_option( self::SIGNAL_OPTION );
		}

		return $has;
	}

	/* --------------------------------------------------------------------- */
	/* Per-post meta bridge                                                   */
	/* --------------------------------------------------------------------- */

	/**
	 * Short-circuit `get_post_meta( $id, '_SAMAN_SEO_meta', true )` to merge in
	 * Yoast values for any field Saman has left empty.
	 *
	 * @param mixed  $value     The pre-filter value (null to fall through).
	 * @param int    $object_id Post ID.
	 * @param string $meta_key  Meta key being requested.
	 * @return mixed
	 */
	public function bridge_post_meta( $value, $object_id, $meta_key ) {
		if ( Post_Meta::META_KEY !== $meta_key || $this->resolving ) {
			return $value;
		}

		$object_id = (int) $object_id;
		if ( $object_id <= 0 ) {
			return $value;
		}

		if ( ! array_key_exists( $object_id, $this->meta_cache ) ) {
			$this->meta_cache[ $object_id ] = $this->build_merged_meta( $object_id );
		}

		$merged = $this->meta_cache[ $object_id ];
		if ( false === $merged ) {
			return $value; // No Yoast data for this post; let core read normally.
		}

		// get_metadata() takes element [0] when $single is true and returns the
		// array as-is when false, so wrapping in one array is correct for both.
		return array( $merged );
	}

	/**
	 * Build the Saman meta array for a post with Yoast values merged into any
	 * empty fields. Returns false when there is nothing to add.
	 *
	 * @param int $post_id Post ID.
	 * @return array|false
	 */
	private function build_merged_meta( $post_id ) {
		$this->resolving = true;
		$stored          = get_post_meta( $post_id, Post_Meta::META_KEY, true );
		$this->resolving = false;

		$merged  = is_array( $stored ) ? $stored : array();
		$changed = false;

		foreach ( $this->meta_map as $saman_key => $yoast_key ) {
			if ( ! empty( $merged[ $saman_key ] ) ) {
				continue; // A Saman value already exists; it wins.
			}

			$yoast_value = get_post_meta( $post_id, $yoast_key, true );
			if ( '' === (string) $yoast_value ) {
				continue;
			}

			$yoast_value = (string) $yoast_value;
			if ( 'title' === $saman_key || 'description' === $saman_key ) {
				// Per-post titles/descriptions occasionally embed Yoast %%vars%%.
				$yoast_value = $this->translate_vars( $yoast_value );
			}

			if ( '' !== $yoast_value ) {
				$merged[ $saman_key ] = $yoast_value;
				$changed              = true;
			}
		}

		// Robots: Yoast stores '1' = noindex / '1' = nofollow (2 = index, 0 = default).
		if ( empty( $merged['noindex'] ) && '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) ) {
			$merged['noindex'] = '1';
			$changed           = true;
		}
		if ( empty( $merged['nofollow'] ) && '1' === (string) get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true ) ) {
			$merged['nofollow'] = '1';
			$changed            = true;
		}

		return $changed ? $merged : false;
	}

	/* --------------------------------------------------------------------- */
	/* Site-wide option bridge                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Wire an option so that, when Saman has no value of its own, the value is
	 * derived from Yoast. `default_option_*` handles the "row absent" case;
	 * `option_*` handles the "row present but empty" case. A real saved Saman
	 * value always wins.
	 *
	 * @param string $option  Saman option name.
	 * @param string $builder Method on this class that returns the Yoast-derived value.
	 * @return void
	 */
	private function bridge_option( $option, $builder ) {
		add_filter(
			'default_option_' . $option,
			function ( $default_value ) use ( $builder ) {
				$bridged = $this->$builder();
				return $this->is_empty_value( $bridged ) ? $default_value : $bridged;
			}
		);

		add_filter(
			'option_' . $option,
			function ( $value ) use ( $builder ) {
				if ( ! $this->is_empty_value( $value ) ) {
					return $value;
				}
				$bridged = $this->$builder();
				return $this->is_empty_value( $bridged ) ? $value : $bridged;
			}
		);
	}

	/**
	 * The Saman title separator derived from Yoast's separator code.
	 *
	 * @return string
	 */
	private function yoast_separator() {
		$titles = $this->yoast_titles();
		$code   = isset( $titles['separator'] ) ? (string) $titles['separator'] : '';

		$map = array(
			'sc-dash'   => '-',
			'sc-ndash'  => '–',
			'sc-mdash'  => '—',
			'sc-colon'  => ':',
			'sc-middot' => '·',
			'sc-bull'   => '•',
			'sc-star'   => '*',
			'sc-smstar' => '⋆',
			'sc-pipe'   => '|',
			'sc-tilde'  => '~',
			'sc-laquo'  => '«',
			'sc-raquo'  => '»',
			'sc-lt'     => '<',
			'sc-gt'     => '>',
		);

		return isset( $map[ $code ] ) ? $map[ $code ] : '';
	}

	/**
	 * Per-post-type title templates derived from Yoast `title-{type}`, translated
	 * into Saman's {{variable}} syntax.
	 *
	 * @return array<string,string>
	 */
	private function yoast_title_templates() {
		$titles = $this->yoast_titles();
		if ( empty( $titles ) ) {
			return array();
		}

		$out = array();
		foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) {
			$key = 'title-' . $post_type;
			if ( empty( $titles[ $key ] ) ) {
				continue;
			}

			$template = $this->translate_vars( (string) $titles[ $key ] );
			if ( '' !== $template ) {
				$out[ $post_type ] = $template;
			}
		}

		return $out;
	}

	/**
	 * Social profile URLs (for Organization/Person sameAs) gathered from Yoast,
	 * one per line — the format Saman's Knowledge Graph profiles option expects.
	 *
	 * @return string
	 */
	private function yoast_social_profiles() {
		$social = get_option( 'wpseo_social' );
		if ( ! is_array( $social ) ) {
			return '';
		}

		$urls = array();
		foreach ( array( 'facebook_site', 'instagram_url', 'linkedin_url', 'youtube_url', 'pinterest_url', 'wikipedia_url', 'mastodon_url' ) as $field ) {
			if ( ! empty( $social[ $field ] ) ) {
				$urls[] = (string) $social[ $field ];
			}
		}

		if ( ! empty( $social['twitter_site'] ) ) {
			$handle = ltrim( (string) $social['twitter_site'], '@' );
			if ( '' !== $handle ) {
				$urls[] = ( false === strpos( $handle, '/' ) ) ? 'https://twitter.com/' . $handle : $handle;
			}
		}

		if ( ! empty( $social['other_social_urls'] ) && is_array( $social['other_social_urls'] ) ) {
			foreach ( $social['other_social_urls'] as $url ) {
				$urls[] = (string) $url;
			}
		}

		$urls = array_values( array_unique( array_filter( array_map( 'trim', $urls ) ) ) );

		return empty( $urls ) ? '' : implode( "\n", $urls );
	}

	/* --------------------------------------------------------------------- */
	/* Helpers                                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Translate Yoast `%%variable%%` replacement tags into Saman's `{{variable}}`
	 * syntax, dropping any tag Saman has no equivalent for (rendered output strips
	 * unresolved tags anyway, so a partial translation degrades gracefully).
	 *
	 * @param string $text Source string.
	 * @return string
	 */
	private function translate_vars( $text ) {
		if ( false === strpos( $text, '%%' ) ) {
			return $text;
		}

		// Resolve %%sep%% to Yoast's actual separator character so bridged titles
		// match Yoast exactly; fall back to Saman's own {{separator}} token if
		// Yoast has no separator configured.
		$separator = $this->yoast_separator();
		$separator = ( '' !== $separator ) ? $separator : '{{separator}}';

		$map = array(
			'%%title%%'            => '{{post_title}}',
			'%%sitename%%'         => '{{site_title}}',
			'%%sep%%'              => $separator,
			'%%sitedesc%%'         => '{{tagline}}',
			'%%excerpt%%'          => '{{post_excerpt}}',
			'%%excerpt_only%%'     => '{{post_excerpt}}',
			'%%category%%'         => '{{category}}',
			'%%primary_category%%' => '{{category}}',
			'%%term_title%%'       => '{{term_title}}',
			'%%term_description%%' => '{{term_description}}',
			'%%date%%'             => '{{post_date}}',
			'%%modified%%'         => '{{modified}}',
			'%%name%%'             => '{{author}}',
			'%%searchphrase%%'     => '{{search_term}}',
			'%%page%%'             => '',
			'%%pagetotal%%'        => '',
			'%%pagenumber%%'       => '',
		);

		$text = strtr( $text, $map );

		// Remove any remaining unmapped Yoast tags, then tidy whitespace.
		$text = preg_replace( '/%%[^%]+%%/', '', $text );
		$text = trim( preg_replace( '/\s{2,}/', ' ', (string) $text ) );

		return $text;
	}

	/**
	 * The Yoast titles option as an array.
	 *
	 * @return array
	 */
	private function yoast_titles() {
		$titles = get_option( 'wpseo_titles' );

		return is_array( $titles ) ? $titles : array();
	}

	/**
	 * Whether a bridged value should be treated as "no value".
	 *
	 * @param mixed $value Value to test.
	 * @return bool
	 */
	private function is_empty_value( $value ) {
		if ( is_array( $value ) ) {
			return empty( $value );
		}

		return '' === trim( (string) $value );
	}
}
