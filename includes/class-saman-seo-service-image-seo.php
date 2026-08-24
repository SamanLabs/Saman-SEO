<?php
/**
 * Image SEO service.
 *
 * Audits media library attachments and inline content images for alt text,
 * descriptive filenames, and lazy-loading attributes. Provides auto-fill of
 * alt text on upload and REST-facing helpers used by the Image SEO tool.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Image SEO analyzer.
 */
class Image_SEO {

	/**
	 * Settings option key.
	 *
	 * @var string
	 */
	const OPTION_SETTINGS = 'SAMAN_SEO_image_seo_settings';

	/**
	 * Filename prefixes/tokens considered non-descriptive.
	 *
	 * @var array<int,string>
	 */
	const GENERIC_TOKENS = array(
		'img',
		'image',
		'images',
		'photo',
		'photos',
		'picture',
		'pic',
		'untitled',
		'final',
		'new',
		'download',
		'logo-scaled',
		'screenshot',
		'capture',
	);

	/**
	 * Camera-generated filename prefixes.
	 *
	 * @var array<int,string>
	 */
	const CAMERA_PREFIXES = array(
		'IMG_',
		'DSC_',
		'DSCN',
		'DJI_',
		'PXL_',
		'SAM_',
		'MVIMG',
		'Screenshot_',
	);

	/**
	 * Filler words that do not make a filename descriptive.
	 *
	 * @var array<int,string>
	 */
	const STOPWORDS = array(
		'at',
		'am',
		'pm',
		'the',
		'a',
		'an',
		'and',
		'of',
		'copy',
		'edit',
		'edited',
		'version',
	);

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'image_seo' ) ) {
			return;
		}

		add_action( 'add_attachment', array( $this, 'auto_fill_alt_on_upload' ) );
	}

	/**
	 * Fetch plugin settings merged with defaults.
	 *
	 * @return array{auto_alt:bool,min_alt_length:int}
	 */
	public function get_settings(): array {
		$stored = get_option( self::OPTION_SETTINGS, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}

		return wp_parse_args(
			$stored,
			array(
				'auto_alt'       => true,
				'min_alt_length' => 10,
			)
		);
	}

	/**
	 * Persist settings.
	 *
	 * @param array $settings Incoming settings.
	 * @return bool
	 */
	public function save_settings( array $settings ): bool {
		$clean = array(
			'auto_alt'       => ! empty( $settings['auto_alt'] ),
			'min_alt_length' => max( 1, min( 100, absint( $settings['min_alt_length'] ?? 10 ) ) ),
		);

		return update_option( self::OPTION_SETTINGS, $clean );
	}

	/**
	 * Auto-populate alt text for freshly uploaded attachments.
	 *
	 * Skips attachments that already carry alt text.
	 *
	 * @param int $attachment_id Newly uploaded attachment ID.
	 * @return void
	 */
	public function auto_fill_alt_on_upload( $attachment_id ): void {
		if ( ! $this->get_settings()['auto_alt'] ) {
			return;
		}

		$existing = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		if ( '' !== trim( (string) $existing ) ) {
			return;
		}

		$suggestion = $this->suggest_alt_text( $attachment_id );

		if ( '' !== $suggestion ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $suggestion ) );
		}
	}

	/**
	 * Build a human-friendly alt text suggestion for an attachment.
	 *
	 * Priority: attachment title > cleaned filename.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return string Suggested alt text (may be empty).
	 */
	public function suggest_alt_text( int $attachment_id ): string {
		$post = get_post( $attachment_id );

		if ( ! $post ) {
			return '';
		}

		$title = trim( (string) $post->post_title );

		if ( '' !== $title && ! $this->is_generic_filename( $title ) ) {
			return $title;
		}

		// Fall back to a phrase derived from the filename, but never emit
		// junk like "img 1234" — an empty suggestion beats a generic one.
		$phrase = $this->filename_to_phrase( $this->attachment_filename( $post ) );

		if ( '' !== $phrase && ! $this->is_generic_filename( $phrase ) ) {
			return $phrase;
		}

		return '';
	}

	/**
	 * Build a descriptive filename suggestion.
	 *
	 * @param string $filename Current filename (with or without extension).
	 * @return string Slug-style suggestion without extension.
	 */
	public function suggest_filename( string $filename ): string {
		$without_ext = pathinfo( $filename, PATHINFO_FILENAME );

		$slug = strtolower( $without_ext );
		$slug = preg_replace( '/[^a-z0-9]+/', '-', $slug );
		$slug = trim( $slug, '-' );
		$slug = preg_replace( '/-{2,}/', '-', $slug );

		// Drop trailing "-scaled/-rotated" artefacts added by WordPress.
		$slug = preg_replace( '/-(scaled|rotated)$/', '', $slug ?? '' );

		return $slug ?? '';
	}

	/**
	 * Audit a single media library attachment.
	 *
	 * @param int $attachment_id Attachment ID.
	 * @return array{
	 *   id:int,
	 *   alt:string,
	 *   filename:string,
	 *   suggested_alt:string,
	 *   suggested_filename:string,
	 *   issues:array<int,array{code:string,message:string,severity:string}>
	 * }
	 */
	public function audit_attachment( int $attachment_id ): array {
		$post     = get_post( $attachment_id );
		$filename = $post ? $this->attachment_filename( $post ) : '';
		$alt      = (string) get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );

		$issues = array();

		if ( '' === trim( $alt ) ) {
			$issues[] = array(
				'code'     => 'missing_alt',
				'message'  => __( 'Image is missing alt text.', 'saman-seo' ),
				'severity' => 'high',
			);
		} elseif ( strlen( $alt ) < (int) $this->get_settings()['min_alt_length'] ) {
			$issues[] = array(
				'code'     => 'short_alt',
				'message'  => __( 'Alt text is too short to be descriptive.', 'saman-seo' ),
				'severity' => 'medium',
			);
		} elseif ( '' !== $filename && strtolower( pathinfo( $filename, PATHINFO_FILENAME ) ) === strtolower( $alt )
			&& $this->is_generic_filename( $filename ) ) {
			$issues[] = array(
				'code'     => 'generic_alt',
				'message'  => __( 'Alt text just repeats a generic filename.', 'saman-seo' ),
				'severity' => 'low',
			);
		}

		if ( '' !== $filename && $this->is_generic_filename( $filename ) ) {
			$issues[] = array(
				'code'     => 'generic_filename',
				'message'  => __( 'Filename is not descriptive (camera default, hash, or generic token).', 'saman-seo' ),
				'severity' => 'low',
			);
		}

		return array(
			'id'                 => $attachment_id,
			'alt'                => $alt,
			'filename'           => $filename,
			'suggested_alt'      => $this->suggest_alt_text( $attachment_id ),
			'suggested_filename' => $this->suggest_filename( $filename ),
			'issues'             => $issues,
		);
	}

	/**
	 * Audit all <img> tags inside a chunk of HTML content.
	 *
	 * Checks per image: alt presence, lazy-loading attribute, and whether
	 * the src filename looks non-descriptive.
	 *
	 * @param string $html Post content HTML.
	 * @return array{
	 *   total:int,
	 *   with_alt:int,
	 *   lazy_count:int,
	 *   images:array<int,array{src:string,alt:string,lazy:bool,issues:array<int,array{code:string,message:string,severity:string}>}>
	 * }
	 */
	public function audit_content( string $html ): array {
		if ( '' === trim( $html ) ) {
			return array(
				'total'      => 0,
				'with_alt'   => 0,
				'lazy_count' => 0,
				'images'     => array(),
			);
		}

		if ( ! preg_match_all( '/<img\s[^>]*>/i', $html, $matches ) ) {
			return array(
				'total'      => 0,
				'with_alt'   => 0,
				'lazy_count' => 0,
				'images'     => array(),
			);
		}

		$report = array(
			'total'      => count( $matches[0] ),
			'with_alt'   => 0,
			'lazy_count' => 0,
			'images'     => array(),
		);

		foreach ( $matches[0] as $img_tag ) {
			$src = '';

			if ( preg_match( '/src\s*=\s*(["\'])(.*?)\1/iu', $img_tag, $src_match ) ) {
				$src = trim( $src_match[2] );
			}

			$alt     = '';
			$has_alt = false;

			if ( preg_match( '/alt\s*=\s*(["\'])(.*?)\1/iu', $img_tag, $alt_match ) ) {
				$alt     = trim( $alt_match[2] );
				$has_alt = true;
			}

			$lazy   = (bool) preg_match( '/loading\s*=\s*(["\'])\s*lazy\s*\1/iu', $img_tag );
			$issues = array();

			if ( ! $has_alt ) {
				$issues[] = array(
					'code'     => 'missing_alt_attribute',
					'message'  => __( 'Image tag has no alt attribute.', 'saman-seo' ),
					'severity' => 'high',
				);
			} elseif ( '' === $alt ) {
				$issues[] = array(
					'code'     => 'empty_alt',
					'message'  => __( 'Alt attribute exists but is empty.', 'saman-seo' ),
					'severity' => 'high',
				);
			} else {
				++$report['with_alt'];
			}

			if ( $lazy ) {
				++$report['lazy_count'];
			} else {
				$issues[] = array(
					'code'     => 'missing_lazy_loading',
					'message'  => __( 'Add loading="lazy" so offscreen images do not block rendering.', 'saman-seo' ),
					'severity' => 'medium',
				);
			}

			$filename = basename( parse_url( $src, PHP_URL_PATH ) ?: '' );

			if ( '' !== $filename && $this->is_generic_filename( $filename ) ) {
				$issues[] = array(
					'code'     => 'generic_filename',
					'message'  => sprintf(
						// translators: %s is the current filename.
						__( 'Consider renaming "%s" to something descriptive before publishing.', 'saman-seo' ),
						$filename
					),
					'severity' => 'low',
				);
			}

			$report['images'][] = array(
				'src'    => $src,
				'alt'    => $alt,
				'lazy'   => $lazy,
				'issues' => $issues,
			);
		}

		return $report;
	}

	/**
	 * Determine whether a filename is non-descriptive.
	 *
	 * Flags camera defaults, hashes, pure numbers, timestamps, and generic
	 * tokens such as "IMG" / "image".
	 *
	 * @param string $name Filename (extension optional) or title.
	 * @return bool True when generic.
	 */
	public function is_generic_filename( string $name ): bool {
		$name = trim( $name );

		if ( '' === $name ) {
			return false; // Nothing to judge here.
		}

		foreach ( self::CAMERA_PREFIXES as $prefix ) {
			if ( 0 === stripos( basename( $name ), $prefix ) ) {
				return true;
			}
		}

		$stem = strtolower( pathinfo( $name, PATHINFO_FILENAME ) );

		// Strip scaling artefacts and numeric suffixes for inspection.
		$stem = (string) preg_replace( '/-(scaled|rotated)$/', '', $stem );
		$core = (string) preg_replace( '/[^a-z]+/', ' ', $stem );

		$core_words = array_filter(
			array_map( 'trim', explode( ' ', $core ) ),
			static function ( $word ) {
				// Single stray letters (from hashes like 3f8a1c...) carry no meaning.
				return strlen( $word ) > 1;
			}
		);

		// Pure numbers / hex hashes / timestamp-like names have no words.
		if ( empty( $core_words ) ) {
			return true;
		}

		$skip = array_merge( self::GENERIC_TOKENS, self::STOPWORDS );

		$meaningful = array_diff( $core_words, $skip );

		return empty( $meaningful );
	}

	/**
	 * Turn a filename into a readable phrase ("my-red-bike.jpg" → "my red bike").
	 *
	 * @param string $filename Raw filename.
	 * @return string Human-readable phrase.
	 */
	private function filename_to_phrase( string $filename ): string {
		if ( '' === $filename ) {
			return '';
		}

		$phrase = $this->suggest_filename( $filename );
		$phrase = str_replace( '-', ' ', $phrase );

		return trim( $phrase );
	}

	/**
	 * Resolve the source filename for an attachment.
	 *
	 * @param \WP_Post $post Attachment post.
	 * @return string Filename or empty string.
	 */
	private function attachment_filename( $post ): string {
		$file = get_post_meta( $post->ID, '_wp_attached_file', true );

		if ( ! empty( $file ) && is_string( $file ) ) {
			return basename( $file );
		}

		$title = (string) $post->post_title;

		return '' !== $title ? $title : '';
	}
}
