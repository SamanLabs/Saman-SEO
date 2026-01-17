<?php
/**
 * Schema Blocks Service.
 *
 * Registers FAQ and HowTo Gutenberg blocks with schema markup support.
 *
 * @package SamanLabs\SEO
 */

namespace SamanLabs\SEO\Service;

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
			'samanlabs-seo/faq',
			[
				'editor_script' => 'samanlabs-seo-faq-block',
				'editor_style'  => 'samanlabs-seo-schema-blocks-editor',
				'style'         => 'samanlabs-seo-schema-blocks',
			]
		);

		// Register HowTo block.
		register_block_type(
			'samanlabs-seo/howto',
			[
				'editor_script' => 'samanlabs-seo-howto-block',
				'editor_style'  => 'samanlabs-seo-schema-blocks-editor',
				'style'         => 'samanlabs-seo-schema-blocks',
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
			'samanlabs-seo-faq-block',
			SAMANLABS_SEO_URL . 'blocks/faq/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			SAMANLABS_SEO_VERSION,
			true
		);

		// HowTo Block.
		wp_register_script(
			'samanlabs-seo-howto-block',
			SAMANLABS_SEO_URL . 'blocks/howto/index.js',
			[ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ],
			SAMANLABS_SEO_VERSION,
			true
		);

		// Editor styles.
		wp_register_style(
			'samanlabs-seo-schema-blocks-editor',
			SAMANLABS_SEO_URL . 'assets/css/schema-blocks-editor.css',
			[],
			SAMANLABS_SEO_VERSION
		);

		// Create inline editor styles if file doesn't exist.
		if ( ! file_exists( SAMANLABS_SEO_PATH . 'assets/css/schema-blocks-editor.css' ) ) {
			wp_add_inline_style( 'samanlabs-seo-schema-blocks-editor', $this->get_editor_styles() );
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

		$has_faq   = has_block( 'samanlabs-seo/faq', $post );
		$has_howto = has_block( 'samanlabs-seo/howto', $post );

		if ( $has_faq || $has_howto ) {
			wp_register_style(
				'samanlabs-seo-schema-blocks',
				SAMANLABS_SEO_URL . 'assets/css/schema-blocks.css',
				[],
				SAMANLABS_SEO_VERSION
			);

			// Create inline styles if file doesn't exist.
			if ( ! file_exists( SAMANLABS_SEO_PATH . 'assets/css/schema-blocks.css' ) ) {
				wp_add_inline_style( 'samanlabs-seo-schema-blocks', $this->get_frontend_styles() );
			}

			wp_enqueue_style( 'samanlabs-seo-schema-blocks' );
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
			.samanlabs-seo-faq-block {
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 8px;
				background: #f9f9f9;
			}
			.samanlabs-seo-faq-header,
			.samanlabs-seo-howto-header {
				display: flex;
				align-items: center;
				gap: 10px;
				margin-bottom: 16px;
				padding-bottom: 12px;
				border-bottom: 1px solid #ddd;
			}
			.samanlabs-seo-faq-icon,
			.samanlabs-seo-howto-icon {
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
			.samanlabs-seo-faq-label,
			.samanlabs-seo-howto-label {
				font-weight: 600;
				font-size: 14px;
				color: #1d2327;
			}
			.samanlabs-seo-faq-badge,
			.samanlabs-seo-howto-badge {
				margin-left: auto;
				padding: 2px 8px;
				background: #00a32a;
				color: #fff;
				font-size: 11px;
				border-radius: 3px;
			}
			.samanlabs-seo-faq-items {
				display: flex;
				flex-direction: column;
				gap: 16px;
			}
			.samanlabs-seo-faq-item {
				padding: 16px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
			}
			.samanlabs-seo-faq-item-header,
			.samanlabs-seo-howto-step-header {
				display: flex;
				justify-content: space-between;
				align-items: center;
				margin-bottom: 10px;
			}
			.samanlabs-seo-faq-number,
			.samanlabs-seo-howto-step-number {
				font-weight: 600;
				color: #2271b1;
			}
			.samanlabs-seo-faq-controls,
			.samanlabs-seo-howto-controls {
				display: flex;
				gap: 4px;
			}
			.samanlabs-seo-faq-question {
				font-weight: 600;
				font-size: 15px;
				margin-bottom: 8px;
				padding: 8px;
				background: #f5f5f5;
				border-radius: 4px;
			}
			.samanlabs-seo-faq-answer {
				font-size: 14px;
				color: #50575e;
				padding: 8px;
			}
			.samanlabs-seo-faq-add,
			.samanlabs-seo-howto-add {
				margin-top: 16px;
			}

			/* HowTo Block Editor */
			.samanlabs-seo-howto-block {
				padding: 20px;
				border: 1px solid #ddd;
				border-radius: 8px;
				background: #f9f9f9;
			}
			.samanlabs-seo-howto-title {
				font-size: 20px;
				margin: 0 0 10px;
			}
			.samanlabs-seo-howto-description {
				color: #50575e;
				margin: 0 0 16px;
			}
			.samanlabs-seo-howto-meta {
				padding: 12px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				margin-bottom: 16px;
				font-size: 13px;
			}
			.samanlabs-seo-howto-steps {
				margin: 0;
				padding: 0;
				list-style: none;
			}
			.samanlabs-seo-howto-step {
				padding: 16px;
				background: #fff;
				border: 1px solid #e0e0e0;
				border-radius: 6px;
				margin-bottom: 12px;
			}
			.samanlabs-seo-howto-step-title {
				font-weight: 600;
				font-size: 15px;
				margin-bottom: 8px;
				padding: 8px;
				background: #f5f5f5;
				border-radius: 4px;
			}
			.samanlabs-seo-howto-step-description {
				font-size: 14px;
				color: #50575e;
				padding: 8px;
			}
			.samanlabs-seo-howto-step-image img {
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
			.samanlabs-seo-faq {
				margin: 2em 0;
			}
			.samanlabs-seo-faq-list {
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				overflow: hidden;
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-item {
				border-bottom: 1px solid #e0e0e0;
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-item:last-child {
				border-bottom: none;
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-question {
				display: block;
				padding: 16px 40px 16px 16px;
				font-weight: 600;
				cursor: pointer;
				position: relative;
				list-style: none;
				background: #f9f9f9;
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-question::-webkit-details-marker {
				display: none;
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-question::after {
				content: "+";
				position: absolute;
				right: 16px;
				top: 50%;
				transform: translateY(-50%);
				font-size: 20px;
				color: #666;
			}
			.samanlabs-seo-faq details[open] .samanlabs-seo-faq-question::after {
				content: "−";
			}
			.samanlabs-seo-faq .samanlabs-seo-faq-answer {
				padding: 16px;
				background: #fff;
			}

			/* HowTo Block Frontend */
			.samanlabs-seo-howto {
				margin: 2em 0;
			}
			.samanlabs-seo-howto-content {
				border: 1px solid #e0e0e0;
				border-radius: 8px;
				padding: 24px;
				background: #fff;
			}
			.samanlabs-seo-howto-title {
				margin: 0 0 12px;
				font-size: 24px;
			}
			.samanlabs-seo-howto-description {
				color: #666;
				margin: 0 0 20px;
			}
			.samanlabs-seo-howto-meta {
				display: flex;
				flex-wrap: wrap;
				gap: 16px;
				padding: 16px;
				background: #f5f5f5;
				border-radius: 6px;
				margin-bottom: 24px;
				font-size: 14px;
			}
			.samanlabs-seo-howto-meta > * {
				flex: 1 1 auto;
			}
			.samanlabs-seo-howto-steps {
				margin: 0;
				padding: 0;
				list-style: none;
				counter-reset: step-counter;
			}
			.samanlabs-seo-howto-step {
				position: relative;
				padding: 20px 20px 20px 60px;
				margin-bottom: 16px;
				background: #f9f9f9;
				border-radius: 8px;
				counter-increment: step-counter;
			}
			.samanlabs-seo-howto-step::before {
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
			.samanlabs-seo-howto-step-title {
				display: block;
				font-weight: 600;
				font-size: 16px;
				margin-bottom: 8px;
			}
			.samanlabs-seo-howto-step-description {
				color: #444;
			}
			.samanlabs-seo-howto-step-image {
				max-width: 100%;
				height: auto;
				border-radius: 6px;
				margin-top: 12px;
			}
		';
	}
}
