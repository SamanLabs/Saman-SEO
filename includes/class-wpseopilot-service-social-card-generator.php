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
		// Check if module is enabled
		if ( '1' !== get_option( 'wpseopilot_enable_og_preview', '1' ) ) {
			return;
		}

		// Allow further filtering
		if ( ! apply_filters( 'wpseopilot_feature_toggle', true, 'social_card_generator' ) ) {
			return;
		}

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

		// Load design settings
		$design_settings = get_option( 'wpseopilot_social_card_design', [] );
		if ( ! is_array( $design_settings ) ) {
			$design_settings = [];
		}

		$design_defaults = [
			'background_color' => '#1a1a36',
			'accent_color'     => '#5a84ff',
			'text_color'       => '#ffffff',
			'title_font_size'  => 48,
			'site_font_size'   => 24,
			'logo_url'         => '',
			'logo_position'    => 'bottom-left',
			'layout'           => 'default',
		];

		$design = wp_parse_args( $design_settings, $design_defaults );

		// Convert hex colors to RGB
		$bg_rgb     = $this->hex_to_rgb( $design['background_color'] );
		$accent_rgb = $this->hex_to_rgb( $design['accent_color'] );
		$text_rgb   = $this->hex_to_rgb( $design['text_color'] );

		// Create image
		$img = imagecreatetruecolor( $width, $height );
		$bg  = imagecolorallocate( $img, $bg_rgb[0], $bg_rgb[1], $bg_rgb[2] );
		$accent = imagecolorallocate( $img, $accent_rgb[0], $accent_rgb[1], $accent_rgb[2] );
		$text = imagecolorallocate( $img, $text_rgb[0], $text_rgb[1], $text_rgb[2] );

		// Render layout
		$this->render_layout( $img, $design['layout'], $width, $height, $title, $bg, $accent, $text );

		// Add logo if provided
		if ( ! empty( $design['logo_url'] ) ) {
			$this->add_logo( $img, $design['logo_url'], $design['logo_position'], $width, $height );
		}

		nocache_headers();
		header( 'Content-Type: image/png' );
		imagepng( $img );
		imagedestroy( $img );
		exit;
	}

	/**
	 * Convert hex color to RGB array.
	 *
	 * @param string $hex Hex color code.
	 * @return array RGB values.
	 */
	private function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		return [
			hexdec( substr( $hex, 0, 2 ) ),
			hexdec( substr( $hex, 2, 2 ) ),
			hexdec( substr( $hex, 4, 2 ) ),
		];
	}

	/**
	 * Render layout based on selected style.
	 *
	 * @param resource $img Image resource.
	 * @param string   $layout Layout style.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 * @param string   $title Title text.
	 * @param int      $bg Background color.
	 * @param int      $accent Accent color.
	 * @param int      $text Text color.
	 */
	private function render_layout( $img, $layout, $width, $height, $title, $bg, $accent, $text ) {
		imagefilledrectangle( $img, 0, 0, $width, $height, $bg );

		switch ( $layout ) {
			case 'centered':
				$this->render_centered_layout( $img, $width, $height, $title, $accent, $text );
				break;
			case 'minimal':
				$this->render_minimal_layout( $img, $width, $height, $title, $text );
				break;
			case 'bold':
				$this->render_bold_layout( $img, $width, $height, $title, $accent, $text );
				break;
			default:
				$this->render_default_layout( $img, $width, $height, $title, $accent, $text );
				break;
		}
	}

	/**
	 * Render default layout.
	 *
	 * @param resource $img Image resource.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 * @param string   $title Title text.
	 * @param int      $accent Accent color.
	 * @param int      $text Text color.
	 */
	private function render_default_layout( $img, $width, $height, $title, $accent, $text ) {
		// Accent bar at bottom
		imagefilledrectangle( $img, 0, $height - 80, $width, $height, $accent );
		// Title and site name
		imagestring( $img, 5, 40, 40, $title, $text );
		imagestring( $img, 3, 40, $height - 60, get_bloginfo( 'name' ), $text );
	}

	/**
	 * Render centered layout.
	 *
	 * @param resource $img Image resource.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 * @param string   $title Title text.
	 * @param int      $accent Accent color.
	 * @param int      $text Text color.
	 */
	private function render_centered_layout( $img, $width, $height, $title, $accent, $text ) {
		// Top accent line
		imagefilledrectangle( $img, 0, 40, $width, 50, $accent );
		// Centered text (approximate centering)
		imagestring( $img, 5, 40, ( $height / 2 ) - 20, $title, $text );
		imagestring( $img, 3, 40, ( $height / 2 ) + 20, get_bloginfo( 'name' ), $text );
	}

	/**
	 * Render minimal layout.
	 *
	 * @param resource $img Image resource.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 * @param string   $title Title text.
	 * @param int      $text Text color.
	 */
	private function render_minimal_layout( $img, $width, $height, $title, $text ) {
		// Text only, no accent
		imagestring( $img, 5, 40, 40, $title, $text );
		imagestring( $img, 3, 40, $height - 40, get_bloginfo( 'name' ), $text );
	}

	/**
	 * Render bold layout.
	 *
	 * @param resource $img Image resource.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 * @param string   $title Title text.
	 * @param int      $accent Accent color.
	 * @param int      $text Text color.
	 */
	private function render_bold_layout( $img, $width, $height, $title, $accent, $text ) {
		// Large accent block on left
		imagefilledrectangle( $img, 0, 0, 200, $height, $accent );
		// Title and site name offset
		imagestring( $img, 5, 240, 40, $title, $text );
		imagestring( $img, 3, 240, $height - 60, get_bloginfo( 'name' ), $text );
	}

	/**
	 * Add logo to image.
	 *
	 * @param resource $img Image resource.
	 * @param string   $logo_url Logo URL.
	 * @param string   $position Logo position.
	 * @param int      $width Image width.
	 * @param int      $height Image height.
	 */
	private function add_logo( $img, $logo_url, $position, $width, $height ) {
		$logo_path = str_replace( home_url(), ABSPATH, $logo_url );

		if ( ! file_exists( $logo_path ) ) {
			return;
		}

		$logo_info = getimagesize( $logo_path );
		if ( ! $logo_info ) {
			return;
		}

		// Create logo resource based on type
		$logo = null;
		switch ( $logo_info[2] ) {
			case IMAGETYPE_JPEG:
				$logo = imagecreatefromjpeg( $logo_path );
				break;
			case IMAGETYPE_PNG:
				$logo = imagecreatefrompng( $logo_path );
				break;
			case IMAGETYPE_GIF:
				$logo = imagecreatefromgif( $logo_path );
				break;
		}

		if ( ! $logo ) {
			return;
		}

		$logo_width  = imagesx( $logo );
		$logo_height = imagesy( $logo );
		$max_logo_size = 150;

		// Resize if needed
		if ( $logo_width > $max_logo_size || $logo_height > $max_logo_size ) {
			$ratio = min( $max_logo_size / $logo_width, $max_logo_size / $logo_height );
			$new_width = (int) ( $logo_width * $ratio );
			$new_height = (int) ( $logo_height * $ratio );
		} else {
			$new_width = $logo_width;
			$new_height = $logo_height;
		}

		// Calculate position
		$x = 40;
		$y = 40;

		switch ( $position ) {
			case 'top-left':
				$x = 40;
				$y = 40;
				break;
			case 'top-right':
				$x = $width - $new_width - 40;
				$y = 40;
				break;
			case 'bottom-left':
				$x = 40;
				$y = $height - $new_height - 40;
				break;
			case 'bottom-right':
				$x = $width - $new_width - 40;
				$y = $height - $new_height - 40;
				break;
			case 'center':
				$x = ( $width - $new_width ) / 2;
				$y = ( $height - $new_height ) / 2;
				break;
		}

		imagecopyresampled( $img, $logo, $x, $y, 0, 0, $new_width, $new_height, $logo_width, $logo_height );
		imagedestroy( $logo );
	}
}
