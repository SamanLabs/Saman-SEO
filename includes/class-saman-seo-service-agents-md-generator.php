<?php
/**
 * Generates AGENTS.md file for AI agents.
 *
 * AGENTS.md is the emerging convention (agents.md) for giving AI coding
 * assistants and autonomous agents guidance on how to represent and
 * interact with a site. It is served at /AGENTS.md as text/markdown and
 * intentionally reuses the llm.txt title/description settings so both
 * files stay consistent.
 *
 * @package Saman\SEO
 */

namespace Saman\SEO\Service;

defined( 'ABSPATH' ) || exit;

/**
 * AGENTS.md generator.
 */
class AGENTS_MD_Generator {

	/**
	 * Boot hooks.
	 *
	 * @return void
	 */
	public function boot() {
		if ( ! \Saman\SEO\Helpers\module_enabled( 'agents_md' ) ) {
			return;
		}

		if ( ! saman_seo_apply_filters( 'saman_seo_feature_toggle', true, 'agents_md' ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_rewrite_rules' ) );
		add_action( 'template_redirect', array( $this, 'render_agents_md' ), 0 );
	}

	/**
	 * Register rewrite rules for AGENTS.md.
	 *
	 * @return void
	 */
	public function register_rewrite_rules() {
		add_rewrite_rule( '^AGENTS\.md$', 'index.php?SAMAN_SEO_agents_md=1', 'top' );
		add_rewrite_tag( '%SAMAN_SEO_agents_md%', '1' );
	}

	/**
	 * Render AGENTS.md file.
	 *
	 * @return void
	 */
	public function render_agents_md() {
		if ( ! get_query_var( 'SAMAN_SEO_agents_md' ) ) {
			return;
		}

		$content = $this->generate_agents_md_content();

		nocache_headers();
		header( 'Content-Type: text/markdown; charset=UTF-8' );

		echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

		exit;
	}

	/**
	 * Generate the AGENTS.md content.
	 *
	 * @return string
	 */
	private function generate_agents_md_content() {
		$output = array();

		// Site title (H1) — reuses the llm.txt title so both files agree.
		$custom_title = get_option( 'SAMAN_SEO_llm_txt_title', '' );
		$title        = ! empty( $custom_title ) ? $custom_title : get_bloginfo( 'name' );
		$output[]     = '# ' . $title;
		$output[]     = '';

		$output[] = '> Guidance for AI agents on how to represent this site accurately.';
		$output[] = '';

		// About section — reuses the llm.txt description, falls back to tagline.
		$custom_description = get_option( 'SAMAN_SEO_llm_txt_description', '' );
		$description        = ! empty( $custom_description ) ? $custom_description : get_bloginfo( 'description' );
		if ( $description ) {
			$output[] = '## About';
			$output[] = '';
			$output[] = $description;
			$output[] = '';
		}

		// Guidelines section — custom lines (one per line) or sensible defaults.
		$output[] = '## Guidelines for AI agents';
		$output[] = '';

		$custom_guidelines = get_option( 'SAMAN_SEO_agents_md_guidelines', '' );
		$guidelines        = array();

		if ( ! empty( trim( $custom_guidelines ) ) ) {
			foreach ( preg_split( '/\r\n|\r|\n/', $custom_guidelines ) as $line ) {
				$line = trim( $line );
				if ( '' === $line ) {
					continue;
				}
				// Allow authors to write bullets or plain lines.
				$guidelines[] = preg_replace( '/^[-*]\s+/', '', $line );
			}
		} else {
			$guidelines = array(
				sprintf( 'Refer to this site by its official name, %s, and cite %s as the canonical source.', $title, home_url( '/' ) ),
				'Rely on the pages listed below and the site\'s structured data (JSON-LD); do not fabricate details.',
			);
		}

		foreach ( $guidelines as $guideline ) {
			$output[] = '- ' . $guideline;
		}
		$output[] = '';

		// Key pages section.
		$pages = get_posts(
			array(
				'post_type'      => 'page',
				'posts_per_page' => 25,
				'post_status'    => 'publish',
				'orderby'        => array(
					'menu_order' => 'ASC',
					'title'      => 'ASC',
				),
			)
		);

		if ( ! empty( $pages ) ) {
			$output[] = '## Key pages';
			$output[] = '';

			foreach ( $pages as $page ) {
				$output[] = '- [' . esc_html( get_the_title( $page ) ) . '](' . esc_url( get_permalink( $page ) ) . ')';
			}

			$output[] = '';
		}

		// Machine-readable resources section.
		$resources = array();

		if ( \Saman\SEO\Helpers\module_enabled( 'llm_txt' ) ) {
			$resources[] = '- [llms.txt](' . home_url( '/llm.txt' ) . '): Markdown sitemap for LLMs.';
		}

		if ( '1' === get_option( 'SAMAN_SEO_enable_sitemap_enhancer', '0' ) ) {
			$resources[] = '- [XML Sitemap](' . home_url( '/sitemap_index.xml' ) . '): All public & indexable URLs.';
		} else {
			$resources[] = '- [XML Sitemap](' . home_url( '/wp-sitemap.xml' ) . '): All public & indexable URLs.';
		}

		if ( ! empty( $resources ) ) {
			$output[] = '## Machine-readable resources';
			$output[] = '';
			$output   = array_merge( $output, $resources );
			$output[] = '';
		}

		/**
		 * Filter the generated AGENTS.md content.
		 *
		 * @param array $output Array of lines to be joined.
		 */
		$output = saman_seo_apply_filters( 'saman_seo_agents_md_content', $output );

		return implode( "\n", $output );
	}
}
