## WP SEO Pilot

WP SEO Pilot is an all-in-one SEO workflow plugin focused on fast editorial UX and crawler-friendly output.

### Highlights

- Per-post SEO fields stored in `_wpseopilot_meta` (title, description, canonical, robots, OG image) with Gutenberg sidebar + classic meta box.
- Server-rendered `<title>`, meta description, canonical, robots, Open Graph, Twitter Card, and JSON-LD (WebSite, WebPage, Article, Breadcrumb).
- Site-wide defaults for title templates, descriptions, social images, robots, hreflang, and module toggles.
- Snippet + social previews, internal link suggestions, guided onboarding, bulk editor, quick actions, and compatibility detection for other SEO plugins.
- Redirect manager (DB table `wpseopilot_redirects`), WP-CLI commands, 404 logging with hashed referrers, sitemap enhancer module, robots.txt editor, and import/export (including Yoast/Rank Math/AIOSEO with dry-run previews).

### Template Tags & Shortcodes

- `wpseopilot_breadcrumbs( $post = null, $echo = true )` renders breadcrumb trail markup.
- `[wpseopilot_breadcrumbs]` shortcode outputs the same breadcrumb list.

### Filters

- `wpseopilot_title`, `wpseopilot_description`, `wpseopilot_canonical` allow programmatic overrides.
- `wpseopilot_jsonld` filters the Structured Data graph before output.
- `wpseopilot_feature_toggle` receives feature keys (`frontend_head`, `metabox`, `redirects`, `sitemaps`) for compatibility fallbacks.
- `wpseopilot_link_suggestions` lets you augment/replace link suggestions in the meta box.
- `wpseopilot_custom_sitemap_items` filters URLs exposed via `wpseopilot-sitemap.xml`.

### WP-CLI

```
wp wpseopilot redirects list --format=table
wp wpseopilot redirects export redirects.json
wp wpseopilot redirects import redirects.json
```

### Import / Export

Export site defaults + postmeta as JSON via **WP SEO Pilot → SEO Defaults**, or import from Yoast SEO, Rank Math, or All in One SEO with optional dry-run counts.

### Privacy

404 logging is opt-in and stores hashed referrers only. No telemetry or external requests are performed.
