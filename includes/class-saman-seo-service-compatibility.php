<?php
/**
 * Detects other SEO plugins to avoid double-outputting.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Compatibility helper.
 */
class Compatibility {

	/**
	 * Conflicting plugin slugs.
	 *
	 * @var array<string,string>
	 */
	private $conflicts = [
		'yoast'      => 'WPSEO_Frontend',
		'rank-math'  => 'RankMath',
		'aioseo'     => 'All_in_One_SEO_Pack',
	];

	/**
	 * Whether conflict detected.
	 *
	 * @var string|null
	 */
	private $active_conflict = null;

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		foreach ( $this->conflicts as $slug => $class ) {
			if ( class_exists( $class ) ) {
				$this->active_conflict = $slug;
				break;
			}
		}

		if ( $this->active_conflict ) {
			add_action( 'admin_notices', [ $this, 'conflict_notice' ] );
			add_filter( 'SAMAN_SEO_feature_toggle', [ $this, 'maybe_disable' ], 10, 2 );
		}
	}

	/**
	 * Maybe disable features for conflicting installs.
	 *
	 * @param bool   $enabled Current state.
	 * @param string $feature Feature key.
	 *
	 * @return bool
	 */
	public function maybe_disable( $enabled, $feature ) {
		if ( ! $this->active_conflict ) {
			return $enabled;
		}

		$conflict_sensitive = [
			'frontend_head',
			'sitemaps',
			'metabox',
			'redirects',
		];

		if ( in_array( $feature, $conflict_sensitive, true ) ) {
			return false;
		}

		return $enabled;
	}

	/**
	 * Admin notice about conflicts.
	 *
	 * @return void
	 */
	public function conflict_notice() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		printf(
			'<div class="notice notice-warning"><p>%s</p></div>',
			wp_kses_post(
				sprintf(
					/* translators: %s: plugin name. */
					__( 'Saman SEO detected another SEO plugin. Some overlapping features are disabled until you deactivate %s or run the migration.', 'saman-seo' ),
					esc_html( $this->active_conflict )
				)
			)
		);
	}
}
