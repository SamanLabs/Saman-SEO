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
	 * Boot the service.
	 *
	 * @return void
	 */
	public function boot() {
		add_action( 'init', [ $this, 'register_blocks' ] );
		add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_styles' ] );
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

		// Register FAQ block.
		register_block_type(
			'saman-seo/faq',
			[
				'editor_script' => 'saman-seo-faq-block',
				'editor_style'  => 'saman-seo-schema-blocks-editor',
				'style'         => 'saman-seo-schema-blocks',
			]
		);

		// Register HowTo block.
		register_block_type(
			'saman-seo/howto',
			[
				'editor_script' => 'saman-seo-howto-block',
				'editor_style'  => 'saman-seo-schema-blocks-editor',
				'style'         => 'saman-seo-schema-blocks',
			]
		);
	}

	/**
	 * Enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_editor_assets() {
		// FAQ Block.
		wp_register_script(
			'saman-seo-faq-block',
			SAMAN_SEO_URL . 'blocks/faq/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			SAMAN_SEO_VERSION,
			true
		);

		// HowTo Block.
		wp_register_script(
			'saman-seo-howto-block',
			SAMAN_SEO_URL . 'blocks/howto/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			SAMAN_SEO_VERSION,
			true
		);

		// Editor styles.
		wp_register_style(
			'saman-seo-schema-blocks-editor',
			SAMAN_SEO_URL . 'assets/css/schema-blocks-editor.css',
			[],
			SAMAN_SEO_VERSION
		);

		// Create inline editor styles if file doesn't exist.
		if ( ! file_exists( SAMAN_SEO_PATH . 'assets/css/schema-blocks-editor.css' ) ) {
			wp_add_inline_style( 'saman-seo-schema-blocks-editor', $this->get_editor_styles() );
		}
	}

	/**
	 * Enqueue frontend styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend_styles() {
		// Only enqueue if post has our blocks.
		if ( ! is_singular() ) {
			return;
		}

		global $post;
		if ( ! $post || ! has_blocks( $post->post_content ) ) {
			return;
		}

		$has_faq   = has_block( 'saman-seo/faq', $post );
		$has_howto = has_block( 'saman-seo/howto', $post );

		if ( $has_faq || $has_howto ) {
			wp_register_style(
				'saman-seo-schema-blocks',
				SAMAN_SEO_URL . 'assets/css/schema-blocks.css',
				[],
				SAMAN_SEO_VERSION
			);

			// Create inline styles if file doesn't exist.
			if ( ! file_exists( SAMAN_SEO_PATH . 'assets/css/schema-blocks.css' ) ) {
				wp_add_inline_style( 'saman-seo-schema-blocks', $this->get_frontend_styles() );
			}

			wp_enqueue_style( 'saman-seo-schema-blocks' );
		}
	}

	/**
	 * Get editor styles.
	 *
	 * @return string
	 */
	private function get_editor_styles() {
		return '
			/* FAQ Block Editor */
			.saman-seo-faq-block {
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 8px;
				background: #f9f9f9;
			}
			.saman-seo-faq-header,
			.saman-seo-howto-header {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 16px;
				padding-bottom: 12px;
				border-bottom: 1px solid #ddd;
			}
			.saman-seo-faq-icon,
			.saman-seo-howto-icon {
				display: flex;
				align-items: center;
				justify-content: center;
				width: 32px;
				height: 32px;
				background: #2271b1;
				color: #fff;
				border-radius: 6px;
				font-weight: bold;
				font-size: 14px;
			}
			.saman-seo-faq-label,
			.saman-seo-howto-label {
				font-weight: 600;
				font-size: 14px;
				color: #1d2327;
			}
			.saman-seo-faq-badge,
			.saman-seo-howto-badge {
				margin-left: auto;
				padding: 2px 8px;
				background: #00a32a;
				color: #fff;
				font-size: 11px;
				border-radius: 3px;
			}
			.saman-seo-faq-items {
				display: flex;
				flex-direction: column;
				gap: 16px;
			}
			.saman-seo-faq-item {
				padding: 16px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
			}
			.saman-seo-faq-item-header,
			.saman-seo-howto-step-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			.saman-seo-faq-number,
			.saman-seo-howto-step-number {
				font-weight: 600;
				color: #2271b1;
			}
			.saman-seo-faq-controls,
			.saman-seo-howto-controls {
				display: flex;
				gap: 4px;
			}
			.saman-seo-faq-question {
				font-weight: 600;
				font-size: 15px;
				margin-bottom: 8px;
				padding: 8px;
				background: #f5f5f5;
				border-radius: 4px;
			}
			.saman-seo-faq-answer {
				font-size: 14px;
				color: #50575e;
				padding: 8px;
			}
			.saman-seo-faq-add,
			.saman-seo-howto-add {
				margin-top: 16px;
			}

			/* HowTo Block Editor */
			.saman-seo-howto-block {
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 8px;
				background: #f9f9f9;
			}
			.saman-seo-howto-title {
				font-size: 20px;
				margin: 0 0 10px;
			}
			.saman-seo-howto-description {
				color: #50575e;
				margin: 0 0 16px;
			}
			.saman-seo-howto-meta {
				padding: 12px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				margin-bottom: 16px;
				font-size: 13px;
			}
			.saman-seo-howto-steps {
				margin: 0;
				padding: 0;
				list-style: none;
			}
			.saman-seo-howto-step {
				padding: 16px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				margin-bottom: 12px;
			}
			.saman-seo-howto-step-title {
				font-weight: 600;
				font-size: 15px;
				margin-bottom: 8px;
				padding: 8px;
				background: #f5f5f5;
				border-radius: 4px;
			}
			.saman-seo-howto-step-description {
				font-size: 14px;
				color: #50575e;
				padding: 8px;
			}
			.saman-seo-howto-step-image img {
				max-width: 200px;
				height: auto;
				border-radius: 4px;
				margin-top: 8px;
			}
		';
	}

	/**
	 * Get frontend styles.
	 *
	 * @return string
	 */
	private function get_frontend_styles() {
		return '
			/* FAQ Block Frontend */
			.saman-seo-faq {
				margin: 2em 0;
			}
			.saman-seo-faq-list {
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				overflow: hidden;
			}
			.saman-seo-faq .saman-seo-faq-item {
				border-bottom: 1px solid #e0e0e0;
			}
			.saman-seo-faq .saman-seo-faq-item:last-child {
				border-bottom: none;
			}
			.saman-seo-faq .saman-seo-faq-question {
				display: block;
				padding: 16px 40px 16px 16px;
				font-weight: 600;
				cursor: pointer;
				position: relative;
				list-style: none;
				background: #f9f9f9;
			}
			.saman-seo-faq .saman-seo-faq-question::-webkit-details-marker {
				display: none;
			}
			.saman-seo-faq .saman-seo-faq-question::after {
				content: "+";
				position: absolute;
				right: 16px;
				top: 50%;
				transform: translateY(-50%);
				font-size: 20px;
				color: #666;
			}
			.saman-seo-faq details[open] .saman-seo-faq-question::after {
				content: "ÃƒÂ¢Ã‹â€ Ã¢â‚¬â„¢";
			}
			.saman-seo-faq .saman-seo-faq-answer {
				padding: 16px;
				background: #fff;
			}

			/* HowTo Block Frontend */
			.saman-seo-howto {
				margin: 2em 0;
			}
			.saman-seo-howto-content {
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				padding: 24px;
				background: #fff;
			}
			.saman-seo-howto-title {
				margin: 0 0 12px;
				font-size: 24px;
			}
			.saman-seo-howto-description {
				color: #666;
				margin: 0 0 20px;
			}
			.saman-seo-howto-meta {
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				padding: 16px;
				background: #f5f5f5;
				border-radius: 6px;
				margin-bottom: 24px;
				font-size: 14px;
			}
			.saman-seo-howto-meta > * {
				flex: 1 1 auto;
			}
			.saman-seo-howto-steps {
				margin: 0;
				padding: 0;
				list-style: none;
				counter-reset: step-counter;
			}
			.saman-seo-howto-step {
				position: relative;
				padding: 20px 20px 20px 60px;
				margin-bottom: 16px;
				background: #f9f9f9;
				border-radius: 8px;
				counter-increment: step-counter;
			}
			.saman-seo-howto-step::before {
				content: counter(step-counter);
				position: absolute;
				left: 16px;
				top: 20px;
				width: 32px;
				height: 32px;
				background: #2271b1;
				color: #fff;
				border-radius: 50%;
				display: flex;
				align-items: center;
				justify-content: center;
				font-weight: bold;
			}
			.saman-seo-howto-step-title {
				display: block;
				font-weight: 600;
				font-size: 16px;
				margin-bottom: 8px;
			}
			.saman-seo-howto-step-description {
				color: #444;
			}
			.saman-seo-howto-step-image {
				max-width: 100%;
				height: auto;
				border-radius: 6px;
				margin-top: 12px;
			}
		';
	}
}
