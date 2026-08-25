<?php
/**
 * Opt-in static page cache.
 *
 * Snapshots fully rendered HTML for cacheable public pages and serves it on
 * later requests, invalidating surgically when content changes. Two serving
 * tiers:
 *
 *  - drop-in: an advanced-cache.php file answers before WordPress boots
 *    (requires WP_CACHE in wp-config.php; installed/removed automatically).
 *  - late: the cached snapshot is served from template_redirect after WP has
 *    booted but before the theme renders. Always works; smaller win.
 *
 * The module is strictly opt-in, refuses to activate alongside other cache
 * plugins (same detection philosophy as Compatibility), and reports measured
 * cold vs warm TTFB so users can see the actual improvement.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Page cache service.
 */
class Page_Cache {

	/**
	 * Module toggle option.
	 *
	 * @var string
	 */
	public const OPTION_ENABLED = 'SAMAN_SEO_module_page_cache';

	/**
	 * TTL option (hours).
	 *
	 * @var string
	 */
	public const OPTION_TTL = 'SAMAN_SEO_page_cache_ttl';

	/**
	 * Purge-on-content-save option.
	 *
	 * @var string
	 */
	public const OPTION_PURGE_ON_SAVE = 'SAMAN_SEO_page_cache_purge_on_save';

	/**
	 * Newline-separated URL paths to always exclude.
	 *
	 * @var string
	 */
	public const OPTION_EXCLUSIONS = 'SAMAN_SEO_page_cache_excluded_urls';

	/**
	 * Non-autoloaded rolling performance totals.
	 *
	 * @var string
	 */
	public const OPTION_TOTALS = 'SAMAN_SEO_page_cache_totals';

	/**
	 * Marker embedded in the generated drop-in so we can recognize our own.
	 *
	 * @var string
	 */
	public const DROPIN_MARKER = 'Saman SEO page cache';

	/**
	 * Drop-in version tag.
	 *
	 * @var int
	 */
	public const DROPIN_VERSION = 1;

	/**
	 * Whether the current request is being captured.
	 *
	 * @var bool
	 */
	private $capturing = false;

	/**
	 * Cache key for the current request (set during capture).
	 *
	 * @var string|null
	 */
	private $current_key = null;

	/**
	 * True server-side render time of this request in ms (cold TTFB sample).
	 *
	 * @var float|null
	 */
	private $render_ms = null;

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		// Late-tier serve: answer from disk before the theme starts rendering.
		add_action( 'template_redirect', array( $this, 'serve_late' ), 0 );

		// Generation: buffer output once a real template was chosen.
		add_action( 'template_redirect', array( $this, 'maybe_start_capture' ), 999 );

		// Invalidation.
		if ( '1' === (string) get_option( self::OPTION_PURGE_ON_SAVE, '1' ) ) {
			add_action( 'save_post', array( $this, 'purge_post_cache' ), 20, 2 );
			add_action( 'delete_post', array( $this, 'purge_post_cache' ), 20, 2 );
			add_action( 'edited_term', array( $this, 'purge_term_cache' ), 20, 3 );
			add_action( 'delete_term', array( $this, 'purge_term_cache' ), 20, 3 );
			add_action( 'transition_comment_status', array( $this, 'purge_comment_cache' ), 20, 2 );
		}
		add_action( 'switch_theme', array( $this, 'purge_all' ) );

		// Ride the shared daily janitor for expiry cleanup.
		add_action( 'saman_seo_daily_maintenance_complete', array( $this, 'gc' ) );
	}

	/* ---------------------------------------------------------------------
	 * State & conflicts
	 * ------------------------------------------------------------------- */

	/**
	 * Whether the module toggle is on.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return '1' === (string) get_option( self::OPTION_ENABLED, '0' );
	}

	/**
	 * Known third-party cache plugins that must not be stacked with ours.
	 *
	 * @return array<string,string> slug => label
	 */
	public function detect_conflicts() {
		$conflicts = array();

		$known = array(
			'w3tc'      => array(
				'label' => 'W3 Total Cache',
				'check' => static function () {
					return defined( 'W3TC' );
				},
			),
			'supercache'=> array(
				'label' => 'WP Super Cache',
				'check' => static function () {
					return defined( 'WPCACHEHOME' );
				},
			),
			'wp-rocket' => array(
				'label' => 'WP Rocket',
				'check' => static function () {
					return defined( 'WP_ROCKET_VERSION' );
				},
			),
			'litespeed' => array(
				'label' => 'LiteSpeed Cache',
				'check' => static function () {
					return defined( 'LSCACHE_VERSION' ) || class_exists( '\LiteSpeed\Core' );
				},
			),
			'breeze'    => array(
				'label' => 'Breeze',
				'check' => static function () {
					return defined( 'BREEZE_VERSION' );
				},
			),
			'sg-optimizer' => array(
				'label' => 'SiteGround Optimizer',
				'check' => static function () {
					return defined( 'SG_OPTIMIZER_VERSION' ) || class_exists( '\SiteGround_Optimizer\Loader' );
				},
			),
		);

		foreach ( $known as $slug => $entry ) {
			try {
				if ( call_user_func( $entry['check'] ) ) {
					$conflicts[ $slug ] = $entry['label'];
				}
			} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Vendor autoload edge cases must never break admin loads.
				continue;
			}
		}

		// Universal check: WP_CACHE is on and the existing drop-in is not ours.
		if ( ! isset( $conflicts['occupied'] )
			&& defined( 'WP_CACHE' ) && WP_CACHE
			&& file_exists( WP_CONTENT_DIR . '/advanced-cache.php' )
			&& false === strpos( (string) file_get_contents( WP_CONTENT_DIR . '/advanced-cache.php' ), self::DROPIN_MARKER )
		) {
			$conflicts['occupied'] = __( 'Another plugin already owns advanced-cache.php', 'saman-seo' );
		}

		/**
		 * Filters detected cache-plugin conflicts.
		 *
		 * Lets hosts/add-ons register additional incompatible caches.
		 *
		 * @since 2.1.0
		 *
		 * @param array<string,string> $conflicts slug => human label.
		 */
		return saman_seo_apply_filters( 'saman_seo_page_cache_conflicts', $conflicts );
	}

	/**
	 * Current serving tier.
	 *
	 * @return string 'dropin'|'late'|'off'
	 */
	public function get_tier() {
		if ( ! $this->is_enabled() ) {
			return 'off';
		}

		$dropin = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( defined( 'WP_CACHE' ) && WP_CACHE && file_exists( $dropin )
			&& false !== strpos( (string) file_get_contents( $dropin ), self::DROPIN_MARKER ) ) {
			return 'dropin';
		}

		return 'late';
	}

	/* ---------------------------------------------------------------------
	 * Enable / disable
	 * ------------------------------------------------------------------- */

	/**
	 * Turn the cache on. Refuses when conflicts are detected.
	 *
	 * @return true|\WP_Error True on success; error lists conflicting plugins.
	 */
	public function enable() {
		$conflicts = $this->detect_conflicts();

		if ( ! empty( $conflicts ) ) {
			return new \WP_Error(
				'saman_seo_page_cache_conflict',
				sprintf(
					/* translators: %s: comma-separated plugin list */
					__( 'Another caching layer is active (%s). Disable it first — stacking caches causes stale-page bugs.', 'saman-seo' ),
					implode( ', ', array_values( $conflicts ) )
				)
			);
		}

		update_option( self::OPTION_ENABLED, '1' );

		if ( ! $this->ensure_storage_dir() ) {
			// Late tier still functions without a pre-writable dir? No dir means no storage at all.
			update_option( self::OPTION_ENABLED, '0' );

			return new \WP_Error(
				'saman_seo_page_cache_storage',
				sprintf(
					/* translators: %s: directory path */
					__( 'Could not create the cache directory at %s. Check folder permissions.', 'saman-seo' ),
					$this->cache_dir()
				)
			);
		}

		$this->install_drop_in();

		$this->purge_all();

		return true;
	}

	/**
	 * Turn the cache off and remove all artifacts.
	 *
	 * @return void
	 */
	public function disable() {
		update_option( self::OPTION_ENABLED, '0' );
		$this->uninstall_drop_in();
		$this->flush_storage();
	}

	/* ---------------------------------------------------------------------
	 * Drop-in management
	 * ------------------------------------------------------------------- */

	/**
	 * Attempt to install the early-serving drop-in plus WP_CACHE constant.
	 *
	 * Failure is non-fatal: the late tier covers those environments.
	 *
	 * @return bool True when the drop-in is live.
	 */
	public function install_drop_in() {
		$source = $this->build_drop_in_source();

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Drop-ins live outside plugin dirs; WP_Filesystem is unavailable this early.
		if ( false === @file_put_contents( WP_CONTENT_DIR . '/advanced-cache.php', $source ) ) {
			return false;
		}

		if ( ! defined( 'WP_CACHE' ) || ! WP_CACHE ) {
			$this->set_wp_cache_constant( true );
		}

		clearstatcache();

		return defined( 'WP_CACHE' ) && WP_CACHE;
	}

	/**
	 * Remove the drop-in and the WP_CACHE line we added.
	 *
	 * @return void
	 */
	public function uninstall_drop_in() {
		$dropin = WP_CONTENT_DIR . '/advanced-cache.php';

		if ( file_exists( $dropin )
			&& false !== strpos( (string) file_get_contents( $dropin ), self::DROPIN_MARKER )
		) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Removing our own generated artifact.
			@unlink( $dropin );
		}

		$config = $this->locate_wp_config();

		if ( $config ) {
			$contents = (string) file_get_contents( $config );
			$needle   = "// Added by Saman SEO page cache\n";

			if ( false !== strpos( $contents, $needle ) ) {
				$updated = str_replace(
					array(
						"define( 'WP_CACHE', true ); {$needle}",
						"{$needle}define( 'WP_CACHE', true );",
					),
					'',
					$contents
				);

				if ( $updated !== $contents ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Restoring wp-config to its prior state.
					@file_put_contents( $config, $updated );
				}
			}
		}
	}

	/**
	 * Generate standalone drop-in PHP. No WP functions may be used inside.
	 *
	 * @return string PHP source.
	 */
	public function build_drop_in_source() {
		$dir     = $this->cache_dir();
		$version = self::DROPIN_VERSION;

		return <<<PHP
<?php
/**
 * Saman SEO page cache drop-in. Auto-generated - do not edit.
 *
 * @version {$version}
 */

if ( PHP_SAPI === 'cli' ) {
	return;
}

if ( ! isset( \$_SERVER['REQUEST_METHOD'] ) || 0 !== strcasecmp( 'GET', \$_SERVER['REQUEST_METHOD'] ) ) {
	return;
}

if ( ! empty( \$_COOKIE ) ) {
	foreach ( \$_COOKIE as \$cookie_name => \$ignore ) {
		if ( 0 === strpos( \$cookie_name, 'wordpress_logged_in_' )
			|| 0 === strpos( \$cookie_name, 'comment_author_' )
			|| 0 === strpos( \$cookie_name, 'wp-postpass_' )
		) {
			return;
		}
	}
}

\$uri = isset( \$_SERVER['REQUEST_URI'] ) ? \$_SERVER['REQUEST_URI'] : '';
if ( '' === \$uri || false !== strpos( \$uri, '?' ) ) {
	return;
}

\$path = parse_url( \$uri, PHP_URL_PATH );
if ( null === \$path || '' === \$path ) {
	\$path = '/';
}

if ( preg_match( '#/(wp-admin|wp-login\\.php|xmlrpc\\.php|admin-ajax\\.php|wp-cron\\.php)([/?#]|\$)#i', \$path )
	|| preg_match( '#/(feed|[a-z0-9_-]+-sitemap[0-9]*\\.xml|sitemap(_index)?\\.xml|robots\\.txt)(\$|/)#i', \$path ) ) {
	return;
}

\$scheme = ( ! empty( \$_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) \$_SERVER['HTTPS'] ) ) ? 'https://' : 'http://';
\$host   = isset( \$_SERVER['HTTP_HOST'] ) ? strtolower( (string) \$_SERVER['HTTP_HOST'] ) : '';

\$key  = md5( \$scheme . \$host . \$path );
\$base = '{$dir}/c/' . substr( \$key, 0, 2 ) . '/';
\$meta_file = \$base . \$key . '.json';

if ( ! is_readable( \$meta_file ) ) {
	return;
}

\$meta = json_decode( (string) file_get_contents( \$meta_file ), true );
if ( ! is_array( \$meta ) || empty( \$meta['expires'] ) || \$meta['expires'] < time() ) {
	return;
}

\$html_file = \$base . \$key . '.html';
if ( ! is_readable( \$html_file ) ) {
	return;
}

\$ttfb = microtime( true ) - ( isset( \$_SERVER['REQUEST_TIME_FLOAT'] ) ? (float) \$_SERVER['REQUEST_TIME_FLOAT'] : microtime( true ) );

@header( 'Content-Type: text/html; charset=UTF-8' );
@header( 'X-Saman-Cache: hit' );

@file_put_contents( '{$dir}/hits.log', gmdate( 'YmdHis' ) . ' ' . number_format( \$ttfb * 1000, 1, '.', '' ) . PHP_EOL, FILE_APPEND | LOCK_EX );

readfile( \$html_file );
exit;
PHP;
	}

	/**
	 * Add or remove the WP_CACHE constant in wp-config.php.
	 *
	 * Only lines carrying our marker are ever removed, so coexisting with
	 * tools that also manage the constant stays safe.
	 *
	 * @param bool $on True to add, false to remove ours.
	 *
	 * @return bool Whether wp-config.php is (or was) already correct.
	 */
	private function set_wp_cache_constant( $on ) {
		$config = $this->locate_wp_config();

		if ( ! $config ) {
			return false;
		}

		$contents = (string) file_get_contents( $config );
		$pattern  = '/define\s*\(\s*[\'"]WP_CACHE[\'"]\s*,\s*(true|false)\s*\)\s*;/';

		if ( $on ) {
			if ( preg_match( $pattern, $contents ) ) {
				$updated = preg_replace( $pattern, "define( 'WP_CACHE', true );", $contents );
			} else {
				$marker  = "// Added by Saman SEO page cache\n";
				$line    = "define( 'WP_CACHE', true ); {$marker}";
				$anchor  = "/* That's all, stop editing! Happy publishing. */";

				if ( false === strpos( $contents, $anchor ) ) {
					return false;
				}

				$updated = str_replace( $anchor, $line . $anchor, $contents );
			}
		} else {
			return true; // Removal handled by uninstall_drop_in().
		}

		if ( ! is_string( $updated ) || $updated === $contents ) {
			return false !== strpos( $contents, "define( 'WP_CACHE', true )" );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- wp-config.php cannot be managed through WP_Filesystem abstractions reliably here.
		return false !== @file_put_contents( $config, $updated );
	}

	/**
	 * Locate wp-config.php one level above or beside ABSPATH.
	 *
	 * @return string|null
	 */
	private function locate_wp_config() {
		$candidates = array(
			ABSPATH . 'wp-config.php',
			dirname( ABSPATH ) . '/wp-config.php',
		);

		foreach ( $candidates as $candidate ) {
			if ( is_readable( $candidate ) ) {
				return $candidate;
			}
		}

		return null;
	}

	/* ---------------------------------------------------------------------
	 * Serving & generation
	 * ------------------------------------------------------------------- */

	/**
	 * Late-tier serve: emit the cached page instead of rendering the theme.
	 *
	 * @return void
	 */
	public function serve_late() {
		if ( is_admin() || defined( 'DOING_AJAX' ) || defined( 'REST_REQUEST' ) || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->is_cacheable_request() ) {
			return;
		}

		$entry = $this->load_entry( $this->make_key_from_request() );

		if ( ! $entry ) {
			return;
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' );
			header( 'X-Saman-Cache: hit-late' );
		}

		$this->record_hit( microtime( true ) - $this->request_start() );

		echo $entry['html']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Cached full HTML snapshot of a previously rendered trusted response.

		exit;
	}

	/**
	 * Begin capturing output if this request should be snapshotted.
	 *
	 * @return void
	 */
	public function maybe_start_capture() {
		if ( is_admin() || defined( 'DOING_AJAX' ) || defined( 'REST_REQUEST' ) || wp_doing_cron() ) {
			return;
		}

		if ( ! $this->is_cacheable_request() ) {
			return;
		}

		$this->capturing   = true;
		$this->current_key = $this->make_key_from_request();
		$this->render_ms   = ( microtime( true ) - $this->request_start() ) * 1000;

		ob_start( array( $this, 'capture_buffer' ) );
	}

	/**
	 * Output buffer callback storing the finished HTML.
	 *
	 * @param string $html Full rendered page.
	 *
	 * @return string Untouched HTML.
	 */
	public function capture_buffer( $html ) {
		$this->capturing = false;

		if ( '' === $html || null === $this->current_key ) {
			return $html;
		}

		$status = function_exists( 'http_response_code' ) ? http_response_code() : 200;

		if ( 200 !== (int) $status ) {
			return $html;
		}

		if ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) {
			return $html;
		}

		// Only snapshot complete HTML documents.
		if ( false === stripos( $html, '<html' ) ) {
			return $html;
		}

		$this->store_entry( $this->current_key, $html, (float) $this->render_ms );

		return $html;
	}

	/**
	 * Whether the incoming request qualifies for caching.
	 *
	 * @return bool
	 */
	public function is_cacheable_request() {
		if ( ! $this->is_enabled() ) {
			return false;
		}

		if ( ! empty( $_POST ) || ! empty( $_GET ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, WordPress.Security.NonceVerification.Missing -- Presence check only, never reading values.
			return false;
		}

		if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'GET' !== strtoupper( (string) $_SERVER['REQUEST_METHOD'] ) ) {
			return false;
		}

		if ( is_user_logged_in() ) {
			return false;
		}

		if ( is_feed() || is_404() || is_search() || is_preview() ) {
			return false;
		}

		if ( is_singular() && post_password_required() ) {
			return false;
		}

		// WooCommerce dynamic endpoints.
		if ( function_exists( 'wc_get_page_permalink' )
			&& ( ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() )
				|| ( function_exists( 'is_account_page' ) && is_account_page() ) )
		) {
			return false;
		}

		if ( $this->is_excluded_path() ) {
			return false;
		}

		/**
		 * Filters whether the current request may be snapshotted.
		 *
		 * Return false to exclude the request (e.g., membership plugins,
		 * geolocation, A/B tests).
		 *
		 * @since 2.1.0
		 *
		 * @param bool $cacheable Default decision.
		 */
		return (bool) saman_seo_apply_filters( 'saman_seo_page_cache_skippable_inverse', true )
			&& ! saman_seo_apply_filters( 'saman_seo_page_cache_skip_request', false );
	}

	/**
	 * Path-based exclusions (defaults + user list).
	 *
	 * @return bool
	 */
	public function is_excluded_path() {
		global $wp;

		$path = isset( $wp->request ) ? '/' . trim( (string) $wp->request, '/' ) . '/' : '/';

		$defaults = array(
			'/cart/',
			'/checkout/',
			'/my-account/',
			'/basket/',
			'/wishlist/',
		);

		$user_raw = (string) get_option( self::OPTION_EXCLUSIONS, '' );
		$user     = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', $user_raw ) ?: array() ) );

		$needles = array_merge( $defaults, $user );

		foreach ( $needles as $needle ) {
			if ( '' === $needle ) {
				continue;
			}

			if ( '/' !== substr( $needle, 0, 1 ) ) {
				$needle = '/' . $needle;
			}

			if ( '/' !== substr( $needle, -1 ) ) {
				$needle .= '/';
			}

			if ( 0 === strpos( $path, strtolower( $needle ) ) ) {
				return true;
			}
		}

		return false;
	}

	/* ---------------------------------------------------------------------
	 * Storage
	 * ------------------------------------------------------------------- */

	/**
	 * Cache directory (under wp-content, survives plugin updates).
	 *
	 * @return string
	 */
	public function cache_dir() {
		return WP_CONTENT_DIR . '/cache/saman-seo-page-cache';
	}

	/**
	 * Ensure the storage directory exists with basic web protection.
	 *
	 * @return bool
	 */
	private function ensure_storage_dir() {
		$dir = $this->cache_dir();

		if ( ! wp_mkdir_p( $dir . '/c' ) ) {
			return false;
		}

		// Best-effort deny rules; nginx ignores these harmlessly.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Guard file inside our own storage dir.
		@file_put_contents( $dir . '/.htaccess', "Deny from all\n" );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Empty index guard.
		@file_put_contents( $dir . '/index.html', '' );

		return true;
	}

	/**
	 * Stable cache key for a URL.
	 *
	 * @param string $url Absolute URL.
	 *
	 * @return string
	 */
	public function make_key( $url ) {
		$parts = wp_parse_url( $url );
		$scheme = strtolower( $parts['scheme'] ?? 'http' );
		$host   = strtolower( $parts['host'] ?? '' );
		$path   = $parts['path'] ?? '/';

		if ( '' === $path ) {
			$path = '/';
		}

		return md5( $scheme . '://' . $host . $path );
	}

	/**
	 * Key for the currently-served request.
	 *
	 * @return string
	 */
	private function make_key_from_request() {
		$scheme = is_ssl() ? 'https://' : 'http://';
		$host   = strtolower( (string) ( $_SERVER['HTTP_HOST'] ?? '' ) );
		$uri    = (string) ( $_SERVER['REQUEST_URI'] ?? '/' );
		$path   = (string) ( wp_parse_url( $uri, PHP_URL_PATH ) ?: '/' );

		return md5( $scheme . $host . $path );
	}

	/**
	 * Load a fresh cache entry by key.
	 *
	 * @param string $key Cache key.
	 *
	 * @return array{html:string}|null Null on miss/expiry.
	 */
	private function load_entry( $key ) {
		$meta = $this->read_meta( $key );

		if ( ! $meta || (int) $meta['expires'] < time() ) {
			return null;
		}

		$file = $this->entry_paths( $key )['html'];

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading our own cache artifact.
		$html = @file_get_contents( $file );

		if ( false === $html || '' === $html ) {
			return null;
		}

		return compact( 'html' );
	}

	/**
	 * Persist an entry pair plus meta.
	 *
	 * @param string $key      Cache key.
	 * @param string $html     Rendered document.
	 * @param float  $render_ms Server render time of the snapshot.
	 *
	 * @return void
	 */
	private function store_entry( $key, $html, $render_ms ) {
		$paths = $this->entry_paths( $key );

		$ttl_hours = max( 1, min( 168, absint( get_option( self::OPTION_TTL, 24 ) ) ) );

		$meta = array(
			'expires'  => time() + $ttl_hours * HOUR_IN_SECONDS,
			'ms'       => round( $render_ms, 1 ),
			'bytes'    => strlen( $html ),
		);

		if ( ! is_dir( dirname( $paths['html'] ) ) ) {
			wp_mkdir_p( dirname( $paths['html'] ) );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Writing our own cache artifact.
		if ( false === @file_put_contents( $paths['html'], $html, LOCK_EX ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Writing our own cache artifact.
		@file_put_contents( $paths['meta'], wp_json_encode( $meta ), LOCK_EX );

		$this->bump_totals( 'misses' );
		$this->bump_totals( 'cold_sum', (float) $render_ms );
		$this->bump_totals( 'cold_count', 1, true );
	}

	/**
	 * Read entry metadata.
	 *
	 * @param string $key Cache key.
	 *
	 * @return array|null
	 */
	private function read_meta( $key ) {
		$file = $this->entry_paths( $key )['meta'];

		if ( ! is_readable( $file ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading our own cache artifact.
		$raw  = @file_get_contents( $file );
		$meta = json_decode( (string) $raw, true );

		return is_array( $meta ) ? $meta : null;
	}

	/**
	 * Shard-safe paths for a key.
	 *
	 * @param string $key Cache key.
	 *
	 * @return array{html:string,meta:string}
	 */
	private function entry_paths( $key ) {
		$base = $this->cache_dir() . '/c/' . substr( $key, 0, 2 );

		return array(
			'html' => $base . '/' . $key . '.html',
			'meta' => $base . '/' . $key . '.json',
		);
	}

	/* ---------------------------------------------------------------------
	 * Invalidation
	 * ------------------------------------------------------------------- */

	/**
	 * Delete every stored page.
	 *
	 * @return int Deleted entry count.
	 */
	public function purge_all() {
		$deleted = 0;
		$root    = $this->cache_dir() . '/c';

		if ( ! is_dir( $root ) ) {
			return 0;
		}

		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ) ) as $file ) {
			if ( $file->isFile() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage.
				@unlink( $file->getPathname() );
				++$deleted;
			}
		}

		// Reset measurement windows so before/after numbers stay meaningful.
		update_option( self::OPTION_TOTALS, $this->fresh_totals(), false );

		return (int) ( $deleted / 2 );
	}

	/**
	 * Purge one URL.
	 *
	 * @param string $url Absolute URL.
	 *
	 * @return void
	 */
	public function purge_url( $url ) {
		$key   = $this->make_key( $url );
		$paths = $this->entry_paths( $key );

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage.
		@unlink( $paths['html'] );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage.
		@unlink( $paths['meta'] );
	}

	/**
	 * Purge everything related to a saved/deleted post.
	 *
	 * @param int     $post_id Post ID.
	 * @param mixed   $post    Post object when available.
	 *
	 * @return void
	 */
	public function purge_post_cache( $post_id, $post = null ) {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
			return;
		}

		$post = $post instanceof \WP_Post ? $post : get_post( $post_id );

		if ( ! $post ) {
			return;
		}

		$urls = array( home_url( '/' ) );

		$permalink = get_permalink( $post_id );

		if ( $permalink ) {
			$urls[] = $permalink;
		}

		$archive = get_post_type_archive_link( $post->post_type );

		if ( $archive ) {
			$urls[] = $archive;
		}

		$taxonomies = get_object_taxonomies( $post->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			$terms = get_the_terms( $post_id, $taxonomy );

			if ( is_array( $terms ) ) {
				foreach ( $terms as $term ) {
					$link = get_term_link( $term );

					if ( ! is_wp_error( $link ) ) {
						$urls[] = $link;
					}
				}
			}
		}

		foreach ( array_unique( $urls ) as $url ) {
			$this->purge_url( $url );
		}
	}

	/**
	 * Purge term archives on term edits.
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $ttax_id  Taxonomy ID (unused).
	 * @param string $taxonomy Taxonomy slug.
	 *
	 * @return void
	 */
	public function purge_term_cache( $term_id, $ttax_id, $taxonomy ) {
		unset( $ttax_id );

		$link = get_term_link( (int) $term_id, $taxonomy );

		if ( ! is_wp_error( $link ) ) {
			$this->purge_url( $link );
		}

		$this->purge_url( home_url( '/' ) );
	}

	/**
	 * Purge a post page when its comments change approval state.
	 *
	 * @param string     $new_status New comment status.
	 * @param \WP_Comment $comment   Comment object.
	 *
	 * @return void
	 */
	public function purge_comment_cache( $new_status, $comment ) {
		unset( $new_status );

		if ( $comment && (int) $comment->comment_post_ID ) {
			$permalink = get_permalink( (int) $comment->comment_post_ID );

			if ( $permalink ) {
				$this->purge_url( $permalink );
			}
		}
	}

	/**
	 * Daily GC via the shared maintenance janitor.
	 *
	 * @return void
	 */
	public function gc() {
		if ( ! $this->is_enabled() ) {
			return;
		}

		$root = $this->cache_dir() . '/c';

		if ( ! is_dir( $root ) ) {
			return;
		}

		$now = time();

		foreach ( glob( $root . '/*/*.json' ) ?: array() as $meta_file ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Reading our own cache artifact during GC.
			$meta = json_decode( (string) @file_get_contents( $meta_file ), true );

			if ( ! is_array( $meta ) || (int) ( $meta['expires'] ?? 0 ) < $now ) {
				$html_file = substr( $meta_file, 0, -5 ) . '.html';

				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage.
				@unlink( $meta_file );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage.
				@unlink( $html_file );
			}
		}
	}

	/* ---------------------------------------------------------------------
	 * Measurement
	 * ------------------------------------------------------------------- */

	/**
	 * Fresh zeroed totals structure.
	 *
	 * @return array<string,float|int>
	 */
	public function fresh_totals() {
		return array(
			'hits'       => 0,
			'misses'     => 0,
			'warm_sum'   => 0.0,
			'warm_count' => 0,
			'cold_sum'   => 0.0,
			'cold_count' => 0,
		);
	}

	/**
	 * Increment a counter in the totals option.
	 *
	 * @param string $field Totals field.
	 * @param float  $delta Amount.
	 * @param bool   $as_int Store as int.
	 *
	 * @return void
	 */
	private function bump_totals( $field, $delta = 1, $as_int = false ) {
		$totals = get_option( self::OPTION_TOTALS, array() );

		if ( ! is_array( $totals ) ) {
			$totals = $this->fresh_totals();
		}

		$totals[ $field ] = ( $totals[ $field ] ?? 0 ) + ( $as_int ? absint( $delta ) : (float) $delta );

		update_option( self::OPTION_TOTALS, $totals, false );
	}

	/**
	 * Fold any pending drop-in hit log entries into the totals.
	 *
	 * @return void
	 */
	public function aggregate_hits() {
		$log = $this->cache_dir() . '/hits.log';

		if ( ! is_readable( $log ) ) {
			return;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Aggregating our own append-only log.
		$raw = (string) @file_get_contents( $log );

		if ( '' === trim( $raw ) ) {
			return;
		}

		$count = 0;
		$sum   = 0.0;

		foreach ( preg_split( '/\r?\n/', trim( $raw ) ) ?: array() as $line ) {
			$parts = explode( ' ', $line );

			if ( count( $parts ) < 2 || ! is_numeric( $parts[1] ) ) {
				continue;
			}

			++$count;
			$sum += (float) $parts[1];
		}

		if ( $count > 0 ) {
			$this->bump_totals( 'hits', $count, true );
			$this->bump_totals( 'warm_sum', $sum );
			$this->bump_totals( 'warm_count', $count, true );
		}

		// Window consumed: reset the log.
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__file_put_contents -- Rotating our own append-only log.
		@file_put_contents( $log, '' );
	}

	/**
	 * Dashboard-facing statistics payload.
	 *
	 * @return array<string,mixed>
	 */
	public function get_stats() {
		$this->aggregate_hits();

		$t          = get_option( self::OPTION_TOTALS, array() );
		$t          = is_array( $t ) ? array_merge( $this->fresh_totals(), $t ) : $this->fresh_totals();
		$total_reqs = (int) $t['hits'] + (int) $t['misses'];

		return array(
			'hits'         => (int) $t['hits'],
			'misses'       => (int) $t['misses'],
			'hit_rate'     => $total_reqs > 0 ? round( ( (int) $t['hits'] / $total_reqs ) * 100, 1 ) : 0.0,
			'ttfb_cold_ms' => (int) $t['cold_count'] > 0 ? round( (float) $t['cold_sum'] / (int) $t['cold_count'], 0 ) : 0,
			'ttfb_warm_ms' => (int) $t['warm_count'] > 0 ? round( (float) $t['warm_sum'] / (int) $t['warm_count'], 0 ) : 0,
			'pages_cached' => $this->count_entries(),
			'disk_bytes'   => $this->disk_usage(),
		);
	}

	/**
	 * Count stored pages (bounded scan).
	 *
	 * @return int
	 */
	private function count_entries() {
		$files = glob( $this->cache_dir() . '/c/*/*.json' ) ?: array();

		return count( $files );
	}

	/**
	 * Approximate disk footprint of stored pages.
	 *
	 * @return int Bytes.
	 */
	private function disk_usage() {
		$bytes = 0;
		$seen  = 0;

		try {
			$iterator = new \RecursiveIteratorIterator(
				new \RecursiveDirectoryIterator( $this->cache_dir(), \FilesystemIterator::SKIP_DOTS )
			);
		} catch ( \Throwable $e ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch -- Missing dir simply means zero usage.
			return 0;
		}

		foreach ( $iterator as $file ) {
			if ( ! $file->isFile() ) {
				continue;
			}

			if ( ++$seen > 20000 ) {
				break;
			}

			$bytes += (int) $file->getSize();
		}

		return $bytes;
	}

	/**
	 * Monotonic-ish request start time.
	 *
	 * @return float
	 */
	private function request_start() {
		return isset( $_SERVER['REQUEST_TIME_FLOAT'] ) ? (float) $_SERVER['REQUEST_TIME_FLOAT'] : microtime( true );
	}

	/**
	 * Record a late-tier hit directly (no log file involved).
	 *
	 * @param float $seconds Elapsed since REQUEST_TIME_FLOAT.
	 *
	 * @return void
	 */
	private function record_hit( $seconds ) {
		$this->bump_totals( 'hits', 1, true );
		$this->bump_totals( 'warm_sum', $seconds * 1000 );
		$this->bump_totals( 'warm_count', 1, true );
	}

	/**
	 * Flush every artifact from storage.
	 *
	 * @return void
	 */
	private function flush_storage() {
		$root = $this->cache_dir();

		if ( ! is_dir( $root ) ) {
			return;
		}

		foreach ( new \RecursiveIteratorIterator( new \RecursiveDirectoryIterator( $root, \FilesystemIterator::SKIP_DOTS ), \RecursiveIteratorIterator::CHILD_FIRST ) as $file ) {
			if ( $file->isDir() ) {
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__rmdir -- Clearing our own storage tree.
				@rmdir( $file->getPathname() );
				continue;
			}

			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__unlink -- Clearing our own storage tree.
			@unlink( $file->getPathname() );
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations__rmdir -- Removing our own storage root.
		@rmdir( $root );
	}
}
