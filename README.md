## WP SEO Pilot

<img width="1280" height="640" alt="WP-SEO-Pilot" src="https://github.com/user-attachments/assets/0eb73154-5c3a-4920-b25f-0d413e4c147b" />

WP SEO Pilot is an all-in-one SEO workflow plugin focused on fast editorial UX and crawler-friendly output.

### Highlights

- Per-post SEO fields stored in `_wpseopilot_meta` (title, description, canonical, robots, OG image) with Gutenberg sidebar + classic meta box.
- Server-rendered `<title>`, meta description, canonical, robots, Open Graph, Twitter Card, and JSON-LD (WebSite, WebPage, Article, Breadcrumb).
- Site-wide defaults for templates, descriptions, social images, robots, hreflang, and module toggles — plus dedicated per-post-type defaults for titles, descriptions, and keywords.
- Snippet + social previews, internal link suggestions, quick actions, and compatibility detection for other SEO plugins.
- Internal Linking: create rules that automatically convert chosen keywords into links across your content, complete with categories, limits, and preview tools.
- AI assistant connects to OpenAI for one-click title & meta description suggestions, with configurable prompts, model selection, and inline editor buttons.
- SEO Audit dashboard with severity graph, issue log, and auto-generated fallback titles/descriptions/tags for posts that are missing metadata.
- Redirect manager (DB table `wpseopilot_redirects`), WP-CLI commands, 404 logging with hashed referrers, sitemap enhancer module, robots.txt editor, and JSON export for quick backups.

### Template Tags & Shortcodes

- `wpseopilot_breadcrumbs( $post = null, $echo = true )` renders breadcrumb trail markup.
- `[wpseopilot_breadcrumbs]` shortcode outputs the same breadcrumb list.

### Filters

- `wpseopilot_title`, `wpseopilot_description`, `wpseopilot_canonical` allow programmatic overrides.
- `wpseopilot_keywords` filters the meta keywords tag derived from post-type defaults.
- `wpseopilot_jsonld` filters the Structured Data graph before output.
- `wpseopilot_feature_toggle` receives feature keys (`frontend_head`, `metabox`, `redirects`, `sitemaps`) for compatibility fallbacks.
- `wpseopilot_link_suggestions` lets you augment/replace link suggestions in the meta box.
- `wpseopilot_sitemap_map` adjusts which post types, taxonomies, or custom groups appear in the Yoast-style sitemap structure.
- `wpseopilot_sitemap_max_urls` changes how many URLs are emitted per sitemap page (defaults to 1,000).
- `wpseopilot_sitemap_group_count` overrides the calculated object totals per sitemap group.
- `wpseopilot_sitemap_count_statuses` customizes which post statuses count toward post-type totals.
- `wpseopilot_sitemap_lastmod` supplies last modified timestamps without forcing full URL hydration.
- `wpseopilot_sitemap_excluded_terms` removes specific taxonomy slugs (e.g. `uncategorized`) from sitemap queries.
- `wpseopilot_sitemap_index_items` filters the compiled sitemap index entries before rendering.
- `wpseopilot_sitemap_stylesheet` swaps the pretty XSL front-end for `/sitemap_index.xml` and individual sitemaps.
- `wpseopilot_sitemap_redirect` overrides the destination when requests hit WordPress core's `/wp-sitemap*.xml`.

### WP-CLI

```
wp wpseopilot redirects list --format=table
wp wpseopilot redirects export redirects.json
wp wpseopilot redirects import redirects.json
```

### Export

Export site defaults + postmeta as JSON via **WP SEO Pilot → SEO Defaults** for easy backups or migrations.

### Privacy

404 logging is opt-in and stores hashed referrers only. No telemetry or external requests are performed.

### Asset build

The plugin styles now compile from Less sources located in `assets/less`.

1. Install dependencies once with `npm install`.
2. Run `npm run build` to regenerate the CSS in `assets/css`.
3. Use `npm run watch` during development to recompile files on change.
