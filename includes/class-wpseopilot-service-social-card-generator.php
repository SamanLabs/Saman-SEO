<?php
/**
 * Simple dynamic OG/Twitter card builder.
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Social card generator.
 */
class Social_Card_Generator {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'template_redirect', [ $this, 'maybe_render_card' ] );
	}

	/**
	 * Output dynamic PNG when requested.
	 *
	 * @return void
	 */
	public function maybe_render_card() {
		if ( empty( $_GET['wpseopilot_social_card'] ) ) {
			return;
		}

		if ( ! function_exists( 'imagecreatetruecolor' ) ) {
			status_header( 501 );
			exit;
		}

		$title = sanitize_text_field( wp_unslash( $_GET['title'] ?? get_bloginfo( 'name' ) ) );
		$width = (int) get_option( 'wpseopilot_default_social_width', 1200 );
		$height = (int) get_option( 'wpseopilot_default_social_height', 630 );

		$img = imagecreatetruecolor( $width, $height );
		$bg  = imagecolorallocate( $img, 26, 26, 54 );
		$accent = imagecolorallocate( $img, 90, 132, 255 );
		$text = imagecolorallocate( $img, 255, 255, 255 );

		imagefilledrectangle( $img, 0, 0, $width, $height, $bg );
		imagefilledrectangle( $img, 0, $height - 80, $width, $height, $accent );

		imagestring( $img, 5, 40, 40, $title, $text );
		imagestring( $img, 3, 40, $height - 60, get_bloginfo( 'name' ), $text );

		nocache_headers();
		header( 'Content-Type: image/png' );
		imagepng( $img );
		imagedestroy( $img );
		exit;
	}
}
