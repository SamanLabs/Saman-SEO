<?php
/**
 * Saman SEO — Theme Config Example
 *
 * Copy this file into your active theme's root as `saman-seo.config.php`:
 *
 *     wp-content/themes/your-theme/saman-seo.config.php
 *
 * Saman SEO loads it automatically and treats whatever it returns as the
 * default layer between the plugin's compile-time defaults and any value
 * the user saves in the admin UI:
 *
 *     admin-saved value  →  this file  →  plugin compile-time default
 *
 * - If the admin has never touched a field, the value from this file wins.
 * - As soon as the user saves an override in the React admin, the admin
 *   value wins and the theme value goes back to being a fallback.
 * - For array options (e.g. social defaults), the merge is per-sub-key:
 *   the theme can fill in the keys the admin has left blank without
 *   stomping on the rest.
 *
 * You can drop the `SAMAN_SEO_` prefix from keys; both forms work.
 *
 * @package Saman\SEO
 */

defined( 'ABSPATH' ) || exit;

return [

	// ---------------------------------------------------------------------
	// Title & description templates
	// ---------------------------------------------------------------------

	// Default <title> template. Available tokens documented in docs/TEMPLATE_TAGS.md.
	'default_title_template' => '{{post_title}} | {{site_title}}',

	// Site-wide meta description fallback (used when a post has no excerpt
	// and no per-post override).
	'default_meta_description' => sprintf(
		/* translators: %s is the site name. */
		__( 'Stay up to date with the latest from %s.', 'your-theme' ),
		get_bloginfo( 'name' )
	),

	// Title separator used by templates that include `{{separator}}`.
	'title_separator' => '–',

	// ---------------------------------------------------------------------
	// Homepage
	// ---------------------------------------------------------------------

	'homepage_title'       => '', // Defaults to the site title when empty.
	'homepage_description' => '',
	'homepage_keywords'    => '',

	// Knowledge graph entity (used by the Organization/Person schema block).
	'homepage_knowledge_type'       => 'organization', // 'organization' or 'person'
	'homepage_organization_name'    => get_bloginfo( 'name' ),
	'homepage_organization_logo'    => '', // Absolute URL to a square logo, 600×600+.
	'homepage_person_name'          => '',
	'homepage_person_job_title'     => '',
	'homepage_person_url'           => '',

	// Newline-separated list of social profile URLs (Twitter, LinkedIn, etc.).
	'homepage_social_profiles' => '',

	// ---------------------------------------------------------------------
	// Open Graph / Twitter defaults
	// ---------------------------------------------------------------------

	// Fallback OG image when a post has no featured image. Absolute URL.
	'default_og_image' => get_template_directory_uri() . '/assets/social-card.jpg',

	// Default social-card dimensions (rarely needs changing).
	'default_social_width'  => 1200,
	'default_social_height' => 630,

	// Per-network text defaults. Empty sub-keys fall back to the page title
	// or description automatically — only set the ones you actually want
	// to override site-wide.
	'social_defaults' => [
		'og_title'            => '',
		'og_description'      => '',
		'twitter_title'       => '',
		'twitter_description' => '',
		'image_source'        => '', // 'featured' | 'first_image' | absolute URL.
		'schema_itemtype'     => '', // e.g. 'WebPage', 'Article'.
	],

	// ---------------------------------------------------------------------
	// Robots
	// ---------------------------------------------------------------------

	// Site-wide robots directive. Common: 'index, follow' or 'noindex, nofollow'.
	'global_robots'     => 'index, follow',
	'default_noindex'   => '0', // '1' to noindex everything by default.
	'default_nofollow'  => '0',

	// ---------------------------------------------------------------------
	// Module toggles (only set the ones you want to force on/off site-wide;
	// otherwise leave them out and the admin UI controls them).
	// ---------------------------------------------------------------------

	// 'module_sitemap'        => '1',
	// 'module_redirects'      => '1',
	// 'module_404_log'        => '1',
	// 'module_llm_txt'        => '1',
	// 'module_local_seo'      => '0',
	// 'module_social_cards'   => '1',
	// 'module_analytics'      => '1',
	// 'module_admin_bar'      => '1',
	// 'module_internal_links' => '1',
	// 'module_ai_assistant'   => '1',
];
