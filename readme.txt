=== Saman SEO ===
Contributors: juandenis
Tags: seo, meta tags, sitemap, redirects, schema
Requires at least: 5.8
Tested up to: 6.9
Stable tag: 2.0.3
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

The Open Standard for WordPress SEO. A comprehensive, transparent SEO solution built for developers who believe SEO tooling should be open source.

== Description ==

Saman SEO is a comprehensive, transparent SEO solution built for developers who believe SEO tooling should be open source, not a black box.

For too long, WordPress SEO has been dominated by proprietary solutions that operate as black boxes. Each plugin guards its methods as trade secrets, fragmenting the ecosystem and forcing developers to work around opaque systems.

**Saman SEO takes a different approach**: We believe the SEO industry benefits from transparency, shared standards, and collaborative improvement. By open-sourcing our complete SEO workflow, we're establishing a foundation that the entire WordPress community can build upon, inspect, and enhance.

= Core SEO Management =

* **Per-Post SEO Fields**: Complete meta control with Gutenberg sidebar and classic editor support
* **Server-Rendered Output**: Titles, meta descriptions, canonical URLs, robots directives, Open Graph, Twitter Cards, and JSON-LD structured data
* **Site-Wide Templates**: Centralized defaults for titles, descriptions, social images, robots directives, and hreflang attributes
* **Post-Type Granularity**: Dedicated defaults for each content type

= Advanced Capabilities =

* **AI-Powered Suggestions**: OpenAI integration for intelligent title and meta description generation
* **Internal Linking Engine**: Automated keyword-to-link conversion with rule-based controls
* **Advanced Sitemap Manager**: Full XML sitemap control with post type/taxonomy selection, RSS and Google News sitemaps
* **Audit Dashboard**: Visual severity graphs, issue logging, and automatic fallback generation
* **Redirect Manager**: Database-backed 301 redirects with WP-CLI support and 404 logging
* **Developer Tools**: Snippet previews, social media card previews, internal link suggestions

= Content Analysis =

* **Real-Time Previews**: SERP snippet and social media card simulations
* **Link Opportunity Detection**: AI-powered internal linking suggestions
* **Quick Actions**: Streamlined workflow for common SEO tasks
* **Compatibility Checks**: Automatic detection and graceful coexistence with other SEO plugins

= Privacy & Security =

* **404 Logging**: Opt-in feature that stores only hashed referrers
* **No Telemetry**: Zero external tracking or analytics
* **No External Requests**: All processing happens on your server (except optional AI features)

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/saman-seo` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Navigate to **Saman SEO > Defaults** to configure site-wide settings.
4. Edit any post or page to configure per-post SEO settings.

== Frequently Asked Questions ==

= Does this plugin work with other SEO plugins? =

Saman SEO includes automatic compatibility detection and can coexist with other SEO plugins. However, for best results, we recommend using only one SEO plugin at a time to avoid conflicts.

= Does the plugin support custom post types? =

Yes, Saman SEO fully supports custom post types with dedicated default settings for each content type.

= Is there WP-CLI support? =

Yes, Saman SEO includes comprehensive WP-CLI commands for managing redirects, sitemaps, and other features programmatically.

= Does the AI feature require an API key? =

Yes, the AI-powered suggestions feature requires an OpenAI API key, which you can configure in the plugin settings. This feature is entirely optional.

== Screenshots ==

1. SEO meta box in the post editor
2. Site-wide defaults configuration
3. Redirect manager interface
4. 404 monitoring dashboard
5. Sitemap settings

== Changelog ==

= 2.0.0 =
* Complete rebrand from WP SEO Pilot to Saman SEO
* Updated all function prefixes and text domains
* Improved WordPress coding standards compliance
* Enhanced security with proper escaping and sanitization

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 2.0.0 =
Major update with complete rebrand. Please backup your site before upgrading.

== Developer Documentation ==

= Template Tags =

`php
// Render breadcrumbs in your theme
if ( function_exists( 'saman_seo_breadcrumbs' ) ) {
    saman_seo_breadcrumbs();
}
`

= Filter Hooks =

`php
// Modify SEO title dynamically
add_filter( 'saman_seo_title', function( $title, $post ) {
    if ( is_singular( 'product' ) ) {
        return $title . ' | Buy Now';
    }
    return $title;
}, 10, 2 );
`

For complete developer documentation, visit our GitHub repository.
