<?php
/**
 * Schema Blocks Service.
 *
 * Registers FAQ and HowTo Gutenberg blocks with schema markup support.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * Schema Blocks service class.
 */
class Schema_Blocks {

	/**
	 * Style handle shared by the front end and the block editor.
	 *
	 * @var string
	 */
	const STYLE_HANDLE = 'saman-seo-schema-blocks';

	/**
	 * Style handle loaded only inside the block editor.
	 *
	 * @var string
	 */
	const EDITOR_STYLE_HANDLE = 'saman-seo-schema-blocks-editor';

	/**
	 * Boot the service.
	 *
	 * Scripts, styles and block types are all registered on `init`. Handing the
	 * style handles to register_block_type() lets core load them where the blocks
	 * are actually used: on the front end when a block renders, and inside the
	 * iframed post editor (always iframed from WP 7.1, and for apiVersion 3
	 * blocks from WP 7.0). Styles enqueued on enqueue_block_editor_assets land in
	 * the outer admin document and never reach that iframe.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', array( $this, 'register_blocks' ) );
	}

	/**
	 * Register block scripts and styles.
	 *
	 * The stylesheets are compiled from assets/less/schema-blocks*.less into
	 * build/css by `npm run build:less`.
	 *
	 * @return void
	 */
	public function register_assets() {
		$deps = array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' );

		wp_register_script( 'saman-seo-faq-block', SAMAN_SEO_URL . 'blocks/faq/index.js', $deps, SAMAN_SEO_VERSION, true );
		wp_register_script( 'saman-seo-howto-block', SAMAN_SEO_URL . 'blocks/howto/index.js', $deps, SAMAN_SEO_VERSION, true );
		wp_set_script_translations( 'saman-seo-faq-block', 'saman-seo', SAMAN_SEO_PATH . 'languages' );
		wp_set_script_translations( 'saman-seo-howto-block', 'saman-seo', SAMAN_SEO_PATH . 'languages' );

		wp_register_style( self::STYLE_HANDLE, SAMAN_SEO_URL . 'build/css/schema-blocks.css', array(), SAMAN_SEO_VERSION );
		wp_register_style( self::EDITOR_STYLE_HANDLE, SAMAN_SEO_URL . 'build/css/schema-blocks-editor.css', array(), SAMAN_SEO_VERSION );
	}

	/**
	 * Register Gutenberg blocks.
	 *
	 * @return void
	 */
	public function register_blocks() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		$this->register_assets();

		$faq_json   = SAMAN_SEO_PATH . 'blocks/faq/block.json';
		$howto_json = SAMAN_SEO_PATH . 'blocks/howto/block.json';

		$faq_args = array(
			'editor_script' => 'saman-seo-faq-block',
			'editor_style'  => self::EDITOR_STYLE_HANDLE,
			'style'         => self::STYLE_HANDLE,
		);

		$howto_args = array(
			'editor_script' => 'saman-seo-howto-block',
			'editor_style'  => self::EDITOR_STYLE_HANDLE,
			'style'         => self::STYLE_HANDLE,
		);

		// Register FAQ block from block.json metadata when available.
		if ( file_exists( $faq_json ) ) {
			register_block_type( $faq_json, $faq_args );
		} else {
			register_block_type( 'saman-seo/faq', $faq_args );
		}

		// Register HowTo block from block.json metadata when available.
		if ( file_exists( $howto_json ) ) {
			register_block_type( $howto_json, $howto_args );
		} else {
			register_block_type( 'saman-seo/howto', $howto_args );
		}
	}
}
