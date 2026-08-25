<?php
/**
 * Google Search Console integration.
 *
 * Handles the OAuth 2.0 device-agnostic web-flow against Google, token
 * lifecycle (storage, refresh, revocation on disconnect), and a thin cached
 * client for the Search Console Search Analytics API.
 *
 * Credentials model: the site owner creates their own OAuth client in Google
 * Cloud Console and pastes the Client ID/Secret here. No plugin-hosted proxy,
 * no shared credentials — tokens never leave the site database.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Search Console service.
 */
class Search_Console {

	/**
	 * OAuth authorize endpoint.
	 *
	 * @var string
	 */
	public const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

	/**
	 * OAuth token endpoint.
	 *
	 * @var string
	 */
	public const TOKEN_URL = 'https://oauth2.googleapis.com/token';

	/**
	 * Search Console API root.
	 *
	 * @var string
	 */
	public const API_ROOT = 'https://searchconsole.googleapis.com/webmasters/v3';

	/**
	 * Read-only scope: enough for site list + search analytics.
	 *
	 * @var string
	 */
	public const SCOPE = 'https://www.googleapis.com/auth/webmasters.readonly';

	/**
	 * Option holding the OAuth client id.
	 *
	 * @var string
	 */
	public const OPTION_CLIENT_ID = 'SAMAN_SEO_gsc_client_id';

	/**
	 * Option holding the encrypted OAuth client secret (not autoloaded).
	 *
	 * @var string
	 */
	public const OPTION_CLIENT_SECRET = 'SAMAN_SEO_gsc_client_secret';

	/**
	 * Option holding token data (not autoloaded).
	 *
	 * @var string
	 */
	public const OPTION_TOKENS = 'SAMAN_SEO_gsc_tokens';

	/**
	 * Option holding the selected Search Console property URL.
	 *
	 * @var string
	 */
	public const OPTION_SELECTED_SITE = 'SAMAN_SEO_gsc_selected_site';

	/**
	 * Transient group prefix used for all API caches.
	 *
	 * @var string
	 */
	public const CACHE_PREFIX = 'saman_seo_gsc_';

	/**
	 * How long analytics responses are cached, in seconds.
	 *
	 * @var int
	 */
	public const CACHE_TTL_ANALYTICS = 6 * HOUR_IN_SECONDS;

	/**
	 * How long the property list is cached, in seconds.
	 *
	 * @var int
	 */
	public const CACHE_TTL_SITES = 12 * HOUR_IN_SECONDS;

	/**
	 * Refresh the access token this many seconds before actual expiry.
	 *
	 * @var int
	 */
	private const TOKEN_EXPIRY_MARGIN = 120;

	/**
	 * Boot hooks.
	 *
	 * The OAuth redirect back from Google lands on the plugin dashboard page;
	 * admin_init is the earliest reliable hook where $_GET is populated and
	 * the user is authenticated.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'admin_init', array( $this, 'maybe_handle_oauth_callback' ) );
	}

	/*
	---------------------------------------------------------------------
	 * Configuration & status
	 * -------------------------------------------------------------------
	 */

	/**
	 * Whether an OAuth client has been configured.
	 *
	 * @return bool
	 */
	public function is_configured() {
		return '' !== $this->get_client_id() && '' !== $this->get_client_secret();
	}

	/**
	 * Whether a valid token set exists.
	 *
	 * @return bool
	 */
	public function is_connected() {
		$tokens = $this->get_tokens();

		return is_array( $tokens )
			&& ! empty( $tokens['refresh_token'] )
			&& ! empty( $tokens['access_token'] );
	}

	/**
	 * Get the stored OAuth client id.
	 *
	 * @return string
	 */
	public function get_client_id() {
		return (string) get_option( self::OPTION_CLIENT_ID, '' );
	}

	/**
	 * Get the decrypted OAuth client secret.
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return $this->decrypt( (string) get_option( self::OPTION_CLIENT_SECRET, '' ) );
	}

	/**
	 * Store OAuth client credentials. Secret is encrypted at rest when the
	 * environment provides OpenSSL.
	 *
	 * @param string $client_id     OAuth client id.
	 * @param string $client_secret OAuth client secret.
	 *
	 * @return void
	 */
	public function set_credentials( $client_id, $client_secret ) {
		update_option( self::OPTION_CLIENT_ID, sanitize_text_field( $client_id ) );

		if ( '' !== $client_secret ) {
			update_option( self::OPTION_CLIENT_SECRET, $this->encrypt( sanitize_text_field( $client_secret ) ), false );
		}
	}

	/**
	 * The redirect URI to register in the Google Cloud console.
	 *
	 * @return string
	 */
	public function get_redirect_uri() {
		return add_query_arg(
			array(
				'page'         => 'saman-seo',
				'saman_gsc_cb' => 1,
			),
			admin_url( 'admin.php' )
		);
	}

	/**
	 * Full connection status payload used by the React card.
	 *
	 * @return array<string,mixed>
	 */
	public function get_status() {
		$connected = $this->is_connected();
		$tokens    = $this->get_tokens();

		$status = array(
			'configured'       => $this->is_configured(),
			'connected'        => $connected,
			'redirect_uri'     => $this->get_redirect_uri(),
			'site'             => (string) get_option( self::OPTION_SELECTED_SITE, '' ),
			'email'            => is_array( $tokens ) ? (string) ( $tokens['account_email'] ?? '' ) : '',
			'token_expires_at' => is_array( $tokens ) ? (int) ( $tokens['expires_at'] ?? 0 ) : 0,
			'error'            => '',
		);

		if ( $connected ) {
			$sites = $this->get_sites();
			if ( is_wp_error( $sites ) ) {
				$status['error'] = $sites->get_error_message();
				$status['sites'] = array();
			} else {
				$status['sites'] = $sites;
			}

			// Auto-select the property matching home_url when none chosen yet.
			if ( '' === $status['site'] && ! empty( $status['sites'] ) ) {
				$match = $this->guess_site_match( $status['sites'] );
				if ( $match ) {
					$this->set_site( $match );
					$status['site'] = $match;
				}
			}
		} else {
			$status['sites'] = array();
		}

		return $status;
	}

	/*
	---------------------------------------------------------------------
	 * OAuth flow
	 * -------------------------------------------------------------------
	 */

	/**
	 * Build the Google authorization URL.
	 *
	 * @return string|\WP_Error
	 */
	public function get_auth_url() {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'saman_seo_gsc_not_configured', __( 'Add your Google OAuth client ID and secret first.', 'saman-seo' ) );
		}

		$state = wp_generate_password( 24, false );
		set_transient( self::CACHE_PREFIX . 'oauth_state', $state, 10 * MINUTE_IN_SECONDS );

		return add_query_arg(
			array_filter(
				array(
					'client_id'              => $this->get_client_id(),
					'redirect_uri'           => $this->get_redirect_uri(),
					'response_type'          => 'code',
					'scope'                  => self::SCOPE,
					'access_type'            => 'offline',
					'prompt'                 => 'consent',
					'include_granted_scopes' => 'true',
					'state'                  => $state,
				)
			),
			self::AUTH_URL
		);
	}

	/**
	 * Detect and process the OAuth redirect.
	 *
	 * @return void
	 */
	public function maybe_handle_oauth_callback() {
		if ( empty( $_GET['saman_gsc_cb'] ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! empty( $_GET['error'] ) ) {
			set_transient( self::CACHE_PREFIX . 'oauth_error', sanitize_text_field( wp_unslash( $_GET['error'] ) ), 5 * MINUTE_IN_SECONDS );
			$this->redirect_back();
			return;
		}

		if ( empty( $_GET['code'] ) || empty( $_GET['state'] ) ) {
			$this->redirect_back();
			return;
		}

		$code     = sanitize_text_field( wp_unslash( $_GET['code'] ) );
		$state    = sanitize_text_field( wp_unslash( $_GET['state'] ) );
		$expected = get_transient( self::CACHE_PREFIX . 'oauth_state' );
		delete_transient( self::CACHE_PREFIX . 'oauth_state' );

		if ( ! is_string( $expected ) || ! hash_equals( $expected, $state ) ) {
			set_transient( self::CACHE_PREFIX . 'oauth_error', 'invalid_state', 5 * MINUTE_IN_SECONDS );
			$this->redirect_back();
			return;
		}

		$result = $this->exchange_code( $code );

		if ( is_wp_error( $result ) ) {
			set_transient( self::CACHE_PREFIX . 'oauth_error', $result->get_error_message(), 5 * MINUTE_IN_SECONDS );
		}

		$this->redirect_back();
	}

	/**
	 * Send the admin back to the dashboard without the callback params.
	 *
	 * @return void
	 */
	private function redirect_back() {
		wp_safe_redirect( remove_query_arg( array( 'code', 'state', 'scope', 'error', 'saman_gsc_cb' ), admin_url( 'admin.php?page=saman-seo' ) ) );
		exit;
	}

	/**
	 * Consume any stored OAuth error message (one-shot read).
	 *
	 * @return string
	 */
	public function pop_oauth_error() {
		$error = get_transient( self::CACHE_PREFIX . 'oauth_error' );
		if ( $error ) {
			delete_transient( self::CACHE_PREFIX . 'oauth_error' );
		}

		return (string) $error;
	}

	/**
	 * Exchange an authorization code for tokens.
	 *
	 * @param string $code Authorization code.
	 *
	 * @return true|\WP_Error
	 */
	public function exchange_code( $code ) {
		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 20,
				'body'    => array(
					'code'          => $code,
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'redirect_uri'  => $this->get_redirect_uri(),
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) || empty( $body['refresh_token'] ) ) {
			$message = $body['error_description'] ?? ( $body['error'] ?? __( 'Google did not return tokens.', 'saman-seo' ) );

			return new \WP_Error( 'saman_seo_gsc_token_exchange', sanitize_text_field( (string) $message ) );
		}

		$this->save_tokens( $body );
		$this->flush_cache();

		return true;
	}

	/**
	 * Disconnect: revoke server-side if possible and wipe local state.
	 *
	 * @return void
	 */
	public function disconnect() {
		$tokens = $this->get_tokens();

		if ( ! empty( $tokens['access_token'] ) ) {
			// Best-effort revocation; ignore failures since we delete locally anyway.
			wp_remote_get(
				'https://accounts.google.com/o/oauth2/revoke',
				array(
					'timeout' => 10,
					'body'    => array( 'token' => $tokens['access_token'] ),
				)
			);
		}

		delete_option( self::OPTION_TOKENS );
		delete_option( self::OPTION_SELECTED_SITE );
		$this->flush_cache();
	}

	/*
	---------------------------------------------------------------------
	 * Tokens
	 * -------------------------------------------------------------------
	 */

	/**
	 * Persist a token response body.
	 *
	 * @param array $body Decoded Google token response.
	 *
	 * @return void
	 */
	private function save_tokens( array $body ) {
		$previous = $this->get_tokens();

		$tokens = array(
			'access_token'  => sanitize_text_field( $body['access_token'] ),
			'expires_at'    => time() + (int) ( $body['expires_in'] ?? 3600 ),
			'refresh_token' => sanitize_text_field( $body['refresh_token'] ?? ( $previous['refresh_token'] ?? '' ) ),
			'account_email' => sanitize_email( $body['account_email'] ?? ( $previous['account_email'] ?? '' ) ),
		);

		update_option( self::OPTION_TOKENS, $tokens, false );
	}

	/**
	 * Get stored tokens.
	 *
	 * @return array<string,mixed>
	 */
	private function get_tokens() {
		$tokens = get_option( self::OPTION_TOKENS, array() );

		return is_array( $tokens ) ? $tokens : array();
	}

	/**
	 * Fetch the account email address associated with the token set.
	 *
	 * Cached permanently in the option; only fetched once per connect.
	 *
	 * @return void
	 */
	public function discover_account_email() {
		$tokens = $this->get_tokens();

		if ( ! empty( $tokens['account_email'] ) ) {
			return;
		}

		$token = $this->get_valid_access_token();

		if ( is_wp_error( $token ) ) {
			return;
		}

		$response = wp_remote_get(
			'https://www.googleapis.com/oauth2/v3/userinfo',
			array(
				'timeout' => 15,
				'headers' => array( 'Authorization' => 'Bearer ' . $token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( ! empty( $body['email'] ) ) {
			$tokens['account_email'] = sanitize_email( $body['email'] );
			update_option( self::OPTION_TOKENS, $tokens, false );
		}
	}

	/**
	 * Get an access token that is still valid, refreshing when needed.
	 *
	 * @return string|\WP_Error
	 */
	public function get_valid_access_token() {
		$tokens = $this->get_tokens();

		if ( empty( $tokens['refresh_token'] ) ) {
			return new \WP_Error( 'saman_seo_gsc_not_connected', __( 'Search Console is not connected.', 'saman-seo' ) );
		}

		if ( ! empty( $tokens['access_token'] ) && ( $tokens['expires_at'] ?? 0 ) > time() + self::TOKEN_EXPIRY_MARGIN ) {
			return $tokens['access_token'];
		}

		$refreshed = $this->refresh_access_token( $tokens['refresh_token'] );

		if ( is_wp_error( $refreshed ) ) {
			return $refreshed;
		}

		return $refreshed;
	}

	/**
	 * Exchange the refresh token for a new access token.
	 *
	 * @param string $refresh_token Stored refresh token.
	 *
	 * @return string|\WP_Error New access token.
	 */
	private function refresh_access_token( $refresh_token ) {
		if ( ! $this->is_configured() ) {
			return new \WP_Error( 'saman_seo_gsc_not_configured', __( 'OAuth client credentials are missing.', 'saman-seo' ) );
		}

		$response = wp_remote_post(
			self::TOKEN_URL,
			array(
				'timeout' => 20,
				'body'    => array(
					'client_id'     => $this->get_client_id(),
					'client_secret' => $this->get_client_secret(),
					'refresh_token' => $refresh_token,
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( empty( $body['access_token'] ) ) {
			$message = $body['error_description'] ?? ( $body['error'] ?? __( 'Token refresh failed.', 'saman-seo' ) );

			return new \WP_Error( 'saman_seo_gsc_refresh', sanitize_text_field( (string) $message ) );
		}

		$this->save_tokens( $body );

		return $body['access_token'];
	}

	/*
	---------------------------------------------------------------------
	 * Properties
	 * -------------------------------------------------------------------
	 */

	/**
	 * List verified properties for the account.
	 *
	 * @param bool $force Bypass cache.
	 *
	 * @return array<int,string>|\WP_Error List of property URLs.
	 */
	public function get_sites( $force = false ) {
		$cached = get_transient( self::CACHE_PREFIX . 'sites' );

		if ( ! $force && false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$response = $this->api_request( 'GET', '/sites' );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$sites = array();
		foreach ( ( $response['siteEntry'] ?? array() ) as $entry ) {
			if ( ( $entry['permissionLevel'] ?? '' ) === 'siteUnverifiedUser' ) {
				continue;
			}

			$sites[] = (string) $entry['siteUrl'];
		}

		sort( $sites );
		set_transient( self::CACHE_PREFIX . 'sites', $sites, self::CACHE_TTL_SITES );

		return $sites;
	}

	/**
	 * Choose the active Search Console property.
	 *
	 * @param string $url Property URL exactly as listed by Google.
	 *
	 * @return void
	 */
	public function set_site( $url ) {
		update_option( self::OPTION_SELECTED_SITE, sanitize_text_field( $url ), false );
		$this->flush_cache();
	}

	/**
	 * Best-guess which listed property corresponds to this WordPress site.
	 *
	 * @param array<int,string> $sites Property URLs.
	 *
	 * @return string Matching property or empty string.
	 */
	private function guess_site_match( array $sites ) {
		$host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );

		if ( ! $host ) {
			return '';
		}

		foreach ( $sites as $site ) {
			// Exact domain-property match wins outright.
			if ( 'sc-domain:' . preg_replace( '/^www\./', '', strtolower( $host ) ) === strtolower( $site ) ) {
				return $site;
			}
		}

		foreach ( $sites as $site ) {
			$site_host = wp_parse_url( $site, PHP_URL_HOST );

			if ( $site_host && str_replace( 'www.', '', strtolower( $site_host ) ) === str_replace( 'www.', '', strtolower( $host ) ) ) {
				return $site;
			}
		}

		return '';
	}

	/*
	---------------------------------------------------------------------
	 * Search Analytics
	 * -------------------------------------------------------------------
	 */

	/**
	 * Run a Search Analytics query.
	 *
	 * @param array $args {
	 *     Query arguments.
	 *
	 *     @type string   $start_date Y-m-d.
	 *     @type string   $end_date   Y-m-d.
	 *     @type string[] $dimensions e.g. ['date'] or ['query'].
	 *     @type int      $row_limit  Max rows, default 25.
	 *     @type int      $start_row  Paging offset.
	 * }
	 *
	 * @return array<string,mixed>|\WP_Error Decoded rows response.
	 */
	public function query_search_analytics( array $args ) {
		$site = (string) get_option( self::OPTION_SELECTED_SITE, '' );

		if ( '' === $site ) {
			return new \WP_Error( 'saman_seo_gsc_no_site', __( 'Select a Search Console property first.', 'saman-seo' ) );
		}

		$args = wp_parse_args(
			$args,
			array(
				'start_date'  => gmdate( 'Y-m-d', strtotime( '-28 days' ) ),
				'end_date'    => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				'dimensions'  => array( 'query' ),
				'row_limit'   => 25,
				'start_row'   => 0,
				'search_type' => 'web',
			)
		);

		ksort( $args );
		$cache_key = self::CACHE_PREFIX . 'q_' . md5( wp_json_encode( array( $site, $args ) ) );
		$cached    = get_transient( $cache_key );

		if ( false !== $cached && is_array( $cached ) ) {
			return $cached;
		}

		$path = '/sites/' . rawurlencode( $site ) . '/searchAnalytics/query';

		$body = array(
			'startDate'  => $args['start_date'],
			'endDate'    => $args['end_date'],
			'dimensions' => array_values( (array) $args['dimensions'] ),
			'rowLimit'   => min( 25000, max( 1, (int) $args['row_limit'] ) ),
			'startRow'   => max( 0, (int) $args['start_row'] ),
			'searchType' => sanitize_key( $args['search_type'] ),
			'dataState'  => 'all',
		);

		$response = $this->api_request( 'POST', $path, $body );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		set_transient( $cache_key, $response, self::CACHE_TTL_ANALYTICS );

		return $response;
	}

	/**
	 * Dashboard summary: totals, daily series, top queries and top pages for
	 * a trailing window.
	 *
	 * @param int $days Trailing day count.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_summary( $days = 28 ) {
		$days       = min( 90, max( 3, (int) $days ) );
		$start_date = gmdate( 'Y-m-d', strtotime( '-' . $days . ' days' ) );
		$end_date   = gmdate( 'Y-m-d', strtotime( '-1 day' ) );

		$totals = $this->query_search_analytics(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'dimensions' => array(),
				'row_limit'  => 1,
			)
		);

		if ( is_wp_error( $totals ) ) {
			return $totals;
		}

		$daily = $this->query_search_analytics(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'dimensions' => array( 'date' ),
				'row_limit'  => $days,
			)
		);

		if ( is_wp_error( $daily ) ) {
			return $daily;
		}

		$queries = $this->query_search_analytics(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'dimensions' => array( 'query' ),
				'row_limit'  => 10,
			)
		);

		if ( is_wp_error( $queries ) ) {
			return $queries;
		}

		$pages = $this->query_search_analytics(
			array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'dimensions' => array( 'page' ),
				'row_limit'  => 10,
			)
		);

		if ( is_wp_error( $pages ) ) {
			return $pages;
		}

		return array(
			'range'   => array(
				'start_date' => $start_date,
				'end_date'   => $end_date,
				'days'       => $days,
			),
			'totals'  => $this->first_row_metrics( $totals ),
			'series'  => $this->normalize_rows_by_date( $daily ),
			'queries' => $this->normalize_dimension_rows( $queries, 'query' ),
			'pages'   => $this->normalize_dimension_rows( $pages, 'page' ),
		);
	}

	/**
	 * Week-over-week deltas for the email digest.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function get_weekly_deltas() {
		$this_week = $this->query_search_analytics(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				'dimensions' => array(),
				'row_limit'  => 1,
			)
		);

		if ( is_wp_error( $this_week ) ) {
			return $this_week;
		}

		$top_queries = $this->query_search_analytics(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '-7 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-1 day' ) ),
				'dimensions' => array( 'query' ),
				'row_limit'  => 5,
			)
		);

		if ( is_wp_error( $top_queries ) ) {
			return $top_queries;
		}

		$last_week = $this->query_search_analytics(
			array(
				'start_date' => gmdate( 'Y-m-d', strtotime( '-14 days' ) ),
				'end_date'   => gmdate( 'Y-m-d', strtotime( '-8 days' ) ),
				'dimensions' => array(),
				'row_limit'  => 1,
			)
		);

		if ( is_wp_error( $last_week ) ) {
			return $last_week;
		}

		return array(
			'current'     => $this->first_row_metrics( $this_week ),
			'top_queries' => $this->normalize_dimension_rows( $top_queries, 'query' ),
			'previous'    => $this->first_row_metrics( $last_week ),
		);
	}

	/**
	 * Extract metrics from a zero-row-safe totals response.
	 *
	 * @param array $response API response.
	 *
	 * @return array{clicks:int,impressions:int,ctr:float,position:float}
	 */
	private function first_row_metrics( array $response ) {
		$row = $response['rows'][0] ?? null;

		if ( ! $row ) {
			return array(
				'clicks'      => 0,
				'impressions' => 0,
				'ctr'         => 0.0,
				'position'    => 0.0,
			);
		}

		return array(
			'clicks'      => (int) round( (float) ( $row['clicks'] ?? 0 ) ),
			'impressions' => (int) round( (float) ( $row['impressions'] ?? 0 ) ),
			'ctr'         => round( (float) ( $row['ctr'] ?? 0 ) * 100, 2 ),
			'position'    => round( (float) ( $row['position'] ?? 0 ), 1 ),
		);
	}

	/**
	 * Convert date-dimension rows into a keyed series.
	 *
	 * @param array $response API response with date dimension.
	 *
	 * @return array<string,array> date => metrics.
	 */
	private function normalize_rows_by_date( array $response ) {
		$series = array();

		foreach ( ( $response['rows'] ?? array() ) as $row ) {
			$date = (string) ( $row['keys'][0] ?? '' );

			if ( '' === $date ) {
				continue;
			}

			$series[ $date ] = array(
				'clicks'      => (int) round( (float) ( $row['clicks'] ?? 0 ) ),
				'impressions' => (int) round( (float) ( $row['impressions'] ?? 0 ) ),
				'ctr'         => round( (float) ( $row['ctr'] ?? 0 ) * 100, 2 ),
				'position'    => round( (float) ( $row['position'] ?? 0 ), 1 ),
			);
		}

		ksort( $series );

		return $series;
	}

	/**
	 * Convert dimension rows into simple arrays.
	 *
	 * @param array  $response API response.
	 * @param string $label_key Dimension key name for output.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	private function normalize_dimension_rows( array $response, $label_key ) {
		$rows = array();

		foreach ( ( $response['rows'] ?? array() ) as $row ) {
			$rows[] = array(
				$label_key    => (string) ( $row['keys'][0] ?? '' ),
				'clicks'      => (int) round( (float) ( $row['clicks'] ?? 0 ) ),
				'impressions' => (int) round( (float) ( $row['impressions'] ?? 0 ) ),
				'ctr'         => round( (float) ( $row['ctr'] ?? 0 ) * 100, 2 ),
				'position'    => round( (float) ( $row['position'] ?? 0 ), 1 ),
			);
		}

		return $rows;
	}

	/*
	---------------------------------------------------------------------
	 * HTTP plumbing
	 * -------------------------------------------------------------------
	 */

	/**
	 * Perform an authenticated Search Console API request with a single
	 * transparent token-refresh retry on 401.
	 *
	 * @param string     $method HTTP method.
	 * @param string     $path   API path beginning with /.
	 * @param array|null $body   JSON body for POST requests.
	 * @param int|null   $attempt Internal retry counter.
	 *
	 * @return array<string,mixed>|\WP_Error
	 */
	public function api_request( $method, $path, $body = null, $attempt = 1 ) {
		$token = $this->get_valid_access_token();

		if ( is_wp_error( $token ) ) {
			return $token;
		}

		$args = array(
			'method'  => strtoupper( $method ),
			'timeout' => 20,
			'headers' => array(
				'Authorization' => 'Bearer ' . $token,
				'Content-Type'  => 'application/json',
			),
		);

		if ( null !== $body ) {
			$args['body'] = wp_json_encode( $body );
		}

		$response = wp_remote_request( self::API_ROOT . $path, $args );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$code = (int) wp_remote_retrieve_response_code( $response );

		if ( 401 === $code && $attempt < 2 ) {
			// Force a refresh then retry once.
			$tokens = $this->get_tokens();

			if ( ! empty( $tokens['refresh_token'] ) ) {
				$this->refresh_access_token( $tokens['refresh_token'] );

				return $this->api_request( $method, $path, $body, $attempt + 1 );
			}
		}

		$payload = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $code < 200 || $code >= 300 ) {
			$message = $payload['error']['message'] ?? sprintf( /* translators: %d: HTTP status code. */ __( 'Search Console API error (HTTP %d).', 'saman-seo' ), $code );

			return new \WP_Error( 'saman_seo_gsc_api', sanitize_text_field( (string) $message ), array( 'status' => $code ) );
		}

		return is_array( $payload ) ? $payload : array();
	}

	/**
	 * Clear every cached API response.
	 *
	 * @return void
	 */
	public function flush_cache() {
		global $wpdb;

		delete_transient( self::CACHE_PREFIX . 'sites' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Bulk transient cleanup requires direct access; core offers no grouped delete.
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
				'_transient_' . self::CACHE_PREFIX . '%',
				'_transient_timeout_' . self::CACHE_PREFIX . '%'
			)
		);
	}

	/*
	---------------------------------------------------------------------
	 * At-rest encryption helpers
	 * -------------------------------------------------------------------
	 */

	/**
	 * Encrypt a secret for database storage using AUTH_KEY-derived AES-256.
	 *
	 * Falls back to plain storage when OpenSSL is unavailable; values are
	 * still protected by DB-level access controls and non-autoloading.
	 *
	 * @param string $plaintext Value to encrypt.
	 *
	 * @return string Encrypted blob (base64 iv:cipher) or plaintext marker.
	 */
	public function encrypt( $plaintext ) {
		$plaintext = (string) $plaintext;

		if ( '' === $plaintext || ! function_exists( 'openssl_encrypt' ) || ! defined( 'AUTH_KEY' ) ) {
			return 'plain:' . $plaintext;
		}

		$key        = hash( 'sha256', AUTH_KEY, true );
		$iv         = random_bytes( 12 );
		$ciphertext = openssl_encrypt( $plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

		if ( false === $ciphertext ) {
			return 'plain:' . $plaintext;
		}

		return 'enc:' . base64_encode( $iv . $tag . $ciphertext );
	}

	/**
	 * Decrypt a value produced by encrypt().
	 *
	 * @param string $blob Stored blob.
	 *
	 * @return string Plaintext or original value when unencrypted.
	 */
	public function decrypt( $blob ) {
		$blob = (string) $blob;

		if ( 0 !== strpos( $blob, 'enc:' ) ) {
			return 0 === strpos( $blob, 'plain:' ) ? substr( $blob, 6 ) : $blob;
		}

		if ( ! function_exists( 'openssl_decrypt' ) || ! defined( 'AUTH_KEY' ) ) {
			return '';
		}

		$raw = base64_decode( substr( $blob, 4 ), true ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode -- Decrypting locally-encrypted secrets.

		if ( false === $raw || strlen( $raw ) <= 28 ) {
			return '';
		}

		$key        = hash( 'sha256', AUTH_KEY, true );
		$iv         = substr( $raw, 0, 12 );
		$tag        = substr( $raw, 12, 16 );
		$ciphertext = substr( $raw, 28 );

		$plaintext = openssl_decrypt( $ciphertext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag );

		return false === $plaintext ? '' : $plaintext;
	}
}
