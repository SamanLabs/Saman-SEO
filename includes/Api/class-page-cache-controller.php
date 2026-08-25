<?php
/**
 * Page Cache REST Controller
 *
 * @package Saman\SEO
 * @since 2.1.0
 */

namespace Saman\SEO\Api;

use Saman\SEO\Service\Page_Cache;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API controller for the opt-in static page cache.
 */
class Page_Cache_Controller extends REST_Controller {

	/**
	 * Page cache service.
	 *
	 * @var Page_Cache
	 */
	private $service;

	/**
	 * Constructor.
	 *
	 * @param Page_Cache|null $service Optional service injection.
	 */
	public function __construct( ?Page_Cache $service = null ) {
		$this->service = $service ?? new Page_Cache();
	}

	/**
	 * Register routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/page-cache/status',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_status' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/page-cache/toggle',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'toggle' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'enabled' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/page-cache/purge',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'purge' ),
					'permission_callback' => array( $this, 'permission_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/page-cache/settings',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'permission_check' ),
					'args'                => array(
						'ttl'          => array(
							'required'          => false,
							'type'              => 'integer',
							'sanitize_callback' => 'absint',
						),
						'purge_on_save' => array(
							'required'          => false,
							'type'              => 'boolean',
							'sanitize_callback' => 'rest_sanitize_boolean',
						),
						'excluded_urls' => array(
							'required'          => false,
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_textarea_field',
						),
					),
				),
			)
		);
	}

	/**
	 * Full status payload for the dashboard card.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function get_status( $request ) {
		return $this->success( $this->get_status_data() );
	}

	/**
	 * Enable or disable the cache.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function toggle( $request ) {
		if ( $request->get_param( 'enabled' ) ) {
			$result = $this->service->enable();

			if ( is_wp_error( $result ) ) {
				return $result;
			}
		} else {
			$this->service->disable();
		}

		return $this->success( $this->get_status_data() );
	}

	/**
	 * Purge every stored snapshot.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function purge( $request ) {
		$deleted = $this->service->purge_all();

		return $this->success(
			$this->get_status_data(),
			sprintf(
				/* translators: %d: number of pages purged */
				__( 'Cache cleared — %d pages will regenerate on next visit.', 'saman-seo' ),
				(int) $deleted
			)
		);
	}

	/**
	 * Save cache settings. Enabling purge-on-save rebinds hooks lazily on
	 * next boot; values take effect immediately for storage decisions.
	 *
	 * @param \WP_REST_Request $request Request.
	 *
	 * @return \WP_REST_Response
	 */
	public function save_settings( $request ) {
		$ttl = $request->get_param( 'ttl' );
		if ( null !== $ttl ) {
			update_option( Page_Cache::OPTION_TTL, max( 1, min( 168, (int) $ttl ) ) );
		}

		$purge_on_save = $request->get_param( 'purge_on_save' );
		if ( null !== $purge_on_save ) {
			update_option( Page_Cache::OPTION_PURGE_ON_SAVE, $purge_on_save ? '1' : '0' );
		}

		$excluded_urls = $request->get_param( 'excluded_urls' );
		if ( null !== $excluded_urls ) {
			update_option( Page_Cache::OPTION_EXCLUSIONS, (string) $excluded_urls, false );
		}

		return $this->success( $this->get_status_data(), __( 'Cache settings saved.', 'saman-seo' ) );
	}

	/**
	 * Build the shared status payload.
	 *
	 * @return array<string,mixed>
	 */
	private function get_status_data() {
		$tier = $this->service->get_tier();

		// A drop-in may physically exist while the module is off (crashed
		// disable); surface it so the UI can offer cleanup.
		$dropin_path     = WP_CONTENT_DIR . '/advanced-cache.php';
		$orphaned_dropin = file_exists( $dropin_path )
			&& false !== strpos( (string) file_get_contents( $dropin_path ), Page_Cache::DROPIN_MARKER )
			&& ! $this->service->is_enabled();

		return array(
			'enabled'         => $this->service->is_enabled(),
			'tier'            => $tier,
			'tier_label'      => $this->tier_label( $tier ),
			'conflicts'       => $this->service->detect_conflicts(),
			'stats'           => $this->service->get_stats(),
			'orphaned_dropin' => $orphaned_dropin,
			'settings'        => array(
				'ttl'           => max( 1, min( 168, absint( get_option( Page_Cache::OPTION_TTL, 24 ) ) ) ),
				'purge_on_save' => '1' === (string) get_option( Page_Cache::OPTION_PURGE_ON_SAVE, '1' ),
				'excluded_urls' => (string) get_option( Page_Cache::OPTION_EXCLUSIONS, '' ),
			),
		);
	}

	/**
	 * Human label per tier.
	 *
	 * @param string $tier Tier slug.
	 *
	 * @return string
	 */
	private function tier_label( $tier ) {
		switch ( $tier ) {
			case 'dropin':
				return __( 'Advanced (serves before WordPress loads)', 'saman-seo' );
			case 'late':
				return __( 'Standard (serves before page render)', 'saman-seo' );
			default:
				return __( 'Off', 'saman-seo' );
		}
	}
}
