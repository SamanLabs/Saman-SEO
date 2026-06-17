<?php
/**
 * Theme-provided SEO defaults loader.
 *
 * Lets a theme ship a single PHP file (saman-seo.config.php) at the theme root
 * that returns an associative array of SAMAN_SEO_* option keys → default values.
 * The plugin treats those values as the fallback layer between its own
 * compile-time defaults and any admin-saved override:
 *
 *     admin-saved value (DB)  →  theme config  →  plugin compile-time default
 *
 * If the user has touched a setting in the React admin, that wins. If they
 * haven't, the theme's value shows up everywhere the plugin reads the option.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Theme config loader.
 */
class Theme_Config {

	/**
	 * Theme-provided overrides, keyed by option name.
	 *
	 * @var array<string,mixed>
	 */
	private $overrides = array();

	/**
	 * Absolute path of the loaded config file, or empty string if none.
	 *
	 * @var string
	 */
	private $loaded_from = '';

	/**
	 * Settings service (source of plugin compile-time defaults).
	 *
	 * @var Settings|null
	 */
	private $settings;

	/**
	 * Boot hooks. Called by the plugin bootstrap.
	 *
	 * @return void
	 */
	public function boot() {
		$plugin         = \Saman\SEO\Plugin::instance();
		$this->settings = $plugin->get( 'settings' );

		$this->load();

		foreach ( array_keys( $this->overrides ) as $key ) {
			add_filter( 'option_' . $key, array( $this, 'apply_override' ), 10, 2 );
			add_filter( 'default_option_' . $key, array( $this, 'apply_default' ), 10, 3 );
		}
	}

	/**
	 * Locate and include the theme config file.
	 *
	 * Resolution order:
	 *   1. Child theme: get_stylesheet_directory() . '/saman-seo.config.php'
	 *   2. Parent theme: get_template_directory() . '/saman-seo.config.php'
	 *   3. Anything returned by the 'saman_seo_config_path' filter.
	 *
	 * @return void
	 */
	private function load() {
		$candidates = array();

		if ( function_exists( 'get_stylesheet_directory' ) ) {
			$candidates[] = get_stylesheet_directory() . '/saman-seo.config.php';
		}

		if ( function_exists( 'get_template_directory' ) ) {
			$candidates[] = get_template_directory() . '/saman-seo.config.php';
		}

		/**
		 * Filter the list of candidate config file paths.
		 *
		 * Themes or mu-plugins can prepend a custom path here (e.g. to load the
		 * config from /includes/seo.config.php instead of the theme root).
		 *
		 * @param string[] $candidates Absolute file paths, in priority order.
		 */
		$candidates = (array) saman_seo_apply_filters( 'saman_seo_config_path', $candidates );

		foreach ( $candidates as $path ) {
			$path = (string) $path;
			if ( '' === $path || ! file_exists( $path ) ) {
				continue;
			}

			$data = $this->include_isolated( $path );

			if ( ! is_array( $data ) ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					// translators: %s is the absolute path of the offending file.
					$msg = sprintf( __( '[Saman SEO] Theme config %s did not return an array; ignoring.', 'saman-seo' ), $path );
					error_log( $msg ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				continue;
			}

			$this->overrides   = $this->normalize_keys( $data );
			$this->loaded_from = $path;
			break;
		}
	}

	/**
	 * Include a file in an isolated scope so it can only return data — it
	 * cannot reach $this or any outer variables.
	 *
	 * @param string $path Absolute file path.
	 *
	 * @return mixed Whatever the file returns.
	 */
	private function include_isolated( $path ) {
		return ( static function ( $__path ) {
			return include $__path;
		} )( $path );
	}

	/**
	 * Allow theme authors to write either fully-qualified option names
	 * (SAMAN_SEO_default_og_image) or short keys (default_og_image). Both
	 * resolve to the same option.
	 *
	 * @param array<string,mixed> $data Raw config returned by the theme.
	 *
	 * @return array<string,mixed>
	 */
	private function normalize_keys( array $data ) {
		$out = array();
		foreach ( $data as $key => $value ) {
			if ( ! is_string( $key ) || '' === $key ) {
				continue;
			}

			if ( 0 === strpos( $key, 'SAMAN_SEO_' ) ) {
				$out[ $key ] = $value;
			} else {
				$out[ 'SAMAN_SEO_' . $key ] = $value;
			}
		}
		return $out;
	}

	/**
	 * Hooked into option_{key}. Substitutes the theme value when the
	 * stored DB value still matches the plugin's compile-time default
	 * (i.e. the admin has never touched it).
	 *
	 * For array options, shallow-merges so the theme can fill keys the
	 * admin has left blank.
	 *
	 * @param mixed  $value  Current DB value.
	 * @param string $option Option name.
	 *
	 * @return mixed
	 */
	public function apply_override( $value, $option ) {
		if ( ! array_key_exists( $option, $this->overrides ) ) {
			return $value;
		}

		$theme_value    = $this->overrides[ $option ];
		$plugin_default = $this->plugin_default( $option );

		if ( is_array( $theme_value ) && is_array( $value ) ) {
			return $this->merge_array_value( $value, $theme_value, $plugin_default );
		}

		if ( $this->is_untouched( $value, $plugin_default ) ) {
			return $theme_value;
		}

		return $value;
	}

	/**
	 * Hooked into default_option_{key}. Fires only when no row exists in
	 * the options table for this key — in that case we hand back the
	 * theme value as if it were the built-in default.
	 *
	 * @param mixed  $default     The default value WordPress was going to use.
	 * @param string $option      Option name.
	 * @param bool   $passed_default Whether get_option() was called with an explicit default.
	 *
	 * @return mixed
	 */
	public function apply_default( $default, $option, $passed_default = false ) {
		unset( $passed_default );

		if ( ! array_key_exists( $option, $this->overrides ) ) {
			return $default;
		}

		return $this->overrides[ $option ];
	}

	/**
	 * Decide whether the stored value is still the plugin's compile-time
	 * default. We compare loosely against empty so that the very common
	 * pattern of an empty-string default doesn't require a separate code
	 * path.
	 *
	 * @param mixed $stored          Current DB value.
	 * @param mixed $plugin_default  Plugin compile-time default.
	 *
	 * @return bool
	 */
	private function is_untouched( $stored, $plugin_default ) {
		if ( null === $stored ) {
			return true;
		}

		// Empty string is the most common "never set" sentinel.
		if ( '' === $stored ) {
			return true;
		}

		if ( null === $plugin_default ) {
			return false;
		}

		// Loose equality is intentional: '1' === 1 should count as a match,
		// and false === '' should not (so we explicitly handled '' above).
		return $stored == $plugin_default; // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual
	}

	/**
	 * Shallow-merge a stored array option with the theme's version.
	 *
	 * For each key the theme provides, the stored value wins when it has
	 * a non-empty entry; otherwise the theme value fills the gap. Keys
	 * the theme doesn't mention are left untouched.
	 *
	 * @param array<string,mixed> $stored         Current DB value.
	 * @param array<string,mixed> $theme_value    Theme-provided overrides.
	 * @param mixed               $plugin_default Plugin compile-time default (for reference).
	 *
	 * @return array<string,mixed>
	 */
	private function merge_array_value( array $stored, array $theme_value, $plugin_default ) {
		$plugin_default = is_array( $plugin_default ) ? $plugin_default : array();

		foreach ( $theme_value as $sub_key => $sub_value ) {
			$current = $stored[ $sub_key ] ?? null;

			// Sub-key not set, or still matches the plugin's compile-time
			// default, or empty string — fill it in from the theme.
			$pd = $plugin_default[ $sub_key ] ?? null;
			if ( null === $current || '' === $current || $current === $pd ) {
				$stored[ $sub_key ] = $sub_value;
			}
		}

		return $stored;
	}

	/**
	 * Look up the plugin compile-time default for an option key.
	 *
	 * @param string $option Option name.
	 *
	 * @return mixed
	 */
	private function plugin_default( $option ) {
		if ( $this->settings && method_exists( $this->settings, 'get_default' ) ) {
			return $this->settings->get_default( $option );
		}
		return null;
	}

	/**
	 * Return the parsed theme overrides (read-only).
	 *
	 * @return array<string,mixed>
	 */
	public function get_overrides() {
		return $this->overrides;
	}

	/**
	 * Absolute path of the config file that was loaded, or '' if none.
	 *
	 * @return string
	 */
	public function get_loaded_path() {
		return $this->loaded_from;
	}

	/**
	 * Whether a particular option is currently being supplied by the theme.
	 *
	 * @param string $option Option name.
	 *
	 * @return bool
	 */
	public function has_override( $option ) {
		return array_key_exists( $option, $this->overrides );
	}
}
