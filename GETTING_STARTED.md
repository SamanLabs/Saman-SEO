# Getting Started with Saman SEO

This guide will help you install and configure Saman SEO for your WordPress site.

---

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the latest release from [GitHub Releases](https://github.com/jhd3197/WP-SEO-Pilot/releases)
2. Log in to your WordPress admin dashboard
3. Navigate to **Plugins → Add New → Upload Plugin**
4. Click **Choose File** and select the downloaded ZIP file
5. Click **Install Now**
6. Click **Activate Plugin**

### Method 2: Manual Installation

1. Download and extract the plugin ZIP file
2. Upload the `wp-seo-pilot` folder to `/wp-content/plugins/` via FTP/SFTP
3. Navigate to **Plugins** in WordPress admin
4. Activate **Saman SEO**

### Method 3: WP-CLI

```bash
wp plugin install wp-seo-pilot --activate
```

---

## Initial Configuration

### Step 1: Configure Global Settings

Navigate to **Saman SEO → Defaults** in your WordPress admin.

#### Site-Wide Defaults

**Title Template:**
- Default: `{{post_title}} | {{site_name}}`
- Variables: `{{post_title}}`, `{{site_name}}`, `{{site_description}}`, `{{post_type}}`, `{{category}}`, `{{tag}}`, `{{author}}`

**Meta Description Template:**
- Default: Auto-generated from post excerpt
- Max length: 160 characters recommended

**Homepage Settings:**
- Custom title for homepage (overrides template)
- Custom description for homepage
- Homepage-specific Open Graph image

#### Social Media

**Open Graph:**
- Default OG image (1200×630px recommended)
- Facebook App ID (optional)
- Default OG type: `website` or `article`

**Twitter Cards:**
- Default Twitter image (1200×675px recommended)
- Twitter site handle (e.g., `@yoursite`)
- Twitter creator handle (optional)
- Card type: `summary` or `summary_large_image`

#### Robots & Indexing

**Global Robots:**
- Default: `index, follow`
- Options: `noindex`, `nofollow`, `noarchive`, `nosnippet`

**Post Type Settings:**
Configure default behavior for each post type:
- Posts: `index, follow` (recommended)
- Pages: `index, follow` (recommended)
- Media: `noindex, follow` (recommended)
- Custom post types: Configure individually

### Step 2: Configure Sitemaps

Navigate to **Saman SEO → Sitemap**.

#### Basic Sitemap Settings

**Enable Sitemaps:** ✓ (Recommended)

**Post Types to Include:**
- ✓ Posts
- ✓ Pages
- ✓ Products (if WooCommerce is active)
- Select any custom post types you want included

**Taxonomies to Include:**
- ✓ Categories
- ✓ Tags
- Custom taxonomies as needed

**Archive Pages:**
- ☐ Date archives (disable if not using)
- ☐ Author archives (enable if you have multiple authors)

**Update Schedule:**
- Recommended: `Daily` for active blogs
- `Weekly` for less frequently updated sites
- `Hourly` only for news sites with constant updates

#### Additional Sitemaps

**RSS Sitemap:** ✓ Enable for feed readers

**Google News Sitemap:**
- Enable only if you're a Google News publisher
- Configure publication name
- Select relevant post types (typically just `post`)

### Step 3: Configure Per-Post Defaults

Navigate to **Saman SEO → Post Type Defaults**.

For each post type, configure:

**Title Template:**
```
Posts: {{post_title}} | {{site_name}}
Pages: {{post_title}} | {{site_name}}
Products: {{post_title}} - Buy Now | {{site_name}}
```

**Description Template:**
```
Posts: Auto-excerpt (leave blank)
Pages: Auto-excerpt (leave blank)
Products: {{excerpt}} | Shop at {{site_name}}
```

**Keywords (Optional):**
- Most search engines ignore meta keywords
- Enable only if required by specific tools

---

## Configure Individual Posts

### Gutenberg Editor

1. Open any post or page
2. Look for the **Saman SEO** panel in the right sidebar
3. Configure:
   - SEO Title (overrides template)
   - Meta Description
   - Canonical URL (leave blank to auto-generate)
   - Robots directives
   - Open Graph image

**Preview:**
- Click **Preview Snippet** to see SERP preview
- Click **Preview Social** to see Facebook/Twitter card preview

### Classic Editor

1. Open any post or page
2. Scroll to the **Saman SEO** meta box below the editor
3. Configure the same fields as Gutenberg
4. Click **Update** to save

---

## Key Features Setup

### Internal Linking

Navigate to **Saman SEO → Internal Links**.

1. **Create Linking Rule:**
   - Keyword: `WordPress`
   - Link to: `https://yoursite.com/wordpress-guide`
   - Maximum links per post: `3`
   - Apply to categories: `Tutorials`, `Guides`

2. **Preview:** Click **Preview** to see where links will be added

3. **Enable Rule:** Toggle to activate

**Best Practices:**
- Start with 5-10 strategic keywords
- Link to your most important pages
- Limit to 1-3 links per post to avoid over-optimization

### AI-Powered Suggestions

Navigate to **Saman SEO → AI Settings**.

1. **Enable AI:** ✓
2. **API Provider:** OpenAI
3. **API Key:** Enter your OpenAI API key
4. **Model:** `gpt-4` (recommended) or `gpt-3.5-turbo` (faster, cheaper)
5. **Custom Prompts:** Optionally customize the prompts used for title and description generation

**Using AI:**
1. Open any post
2. In the Saman SEO panel, click **Generate Title** or **Generate Description**
3. Review the AI suggestion
4. Accept or edit as needed

### SEO Audit

Navigate to **Saman SEO → Audit**.

**Run Your First Audit:**
1. Click **Run Audit**
2. Wait for completion (may take a few minutes on large sites)
3. Review issues by severity:
   - **Critical:** Fix immediately
   - **High:** Fix soon
   - **Medium:** Address when possible
   - **Low:** Nice to have

**Common Issues:**
- Missing titles → Auto-generate or write manually
- Missing descriptions → Auto-generate or write manually
- Duplicate titles → Make unique
- Broken canonical URLs → Fix or remove
- Missing Open Graph images → Add images

**Auto-Fix:**
Click **Auto-Fix** to automatically generate missing titles and descriptions based on your templates.

### Redirects

Navigate to **Saman SEO → Redirects**.

**Add a Redirect:**
1. Enter **Source Path:** `/old-page`
2. Enter **Target URL:** `/new-page` or full URL
3. Select **Status Code:** `301` (permanent) or `302` (temporary)
4. Click **Add Redirect**

**Import Bulk Redirects:**
1. Prepare a CSV file:
   ```csv
   source,target,status
   /old-1,/new-1,301
   /old-2,/new-2,301
   ```
2. Click **Import** and select your file
3. Review and confirm

**404 Monitoring:**
- Enable to track 404 errors
- Review logs to identify broken links
- Create redirects for frequently hit 404s

---

## Integration with Other Plugins

### WooCommerce

Saman SEO automatically detects WooCommerce and provides:
- Product-specific templates
- Product schema markup
- Product image inclusion in sitemaps

**Recommended Product Settings:**
```
Title: {{post_title}} - {{category}} | {{site_name}}
Description: Buy {{post_title}} at {{site_name}}. {{excerpt}}
```

### Multilingual Plugins (Polylang, WPML)

Saman SEO works with multilingual plugins. For best results:

1. Set different defaults per language (if supported)
2. Configure hreflang tags in global settings
3. Set canonical URLs to point to primary language version

**Example Filter (Advanced):**
```php
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( function_exists( 'pll_current_language' ) ) {
        $lang = pll_current_language();
        $suffixes = [
            'fr' => ' | Version Française',
            'es' => ' | Versión Español',
        ];
        return $title . ( $suffixes[ $lang ] ?? '' );
    }
    return $title;
}, 10, 2 );
```

### Page Builders (Elementor, Divi, Beaver Builder)

Saman SEO integrates seamlessly with page builders:

1. SEO fields appear in page builder sidebars
2. Content is analyzed from builder output
3. Structured data works with builder content

---

## Verification & Testing

### Verify Installation

**Check Frontend Output:**
1. Visit any page on your site
2. View page source (right-click → View Page Source)
3. Look for Saman SEO meta tags:
   ```html
   <!-- Saman SEO -->
   <title>Your Page Title | Site Name</title>
   <meta name="description" content="...">
   <meta property="og:title" content="...">
   <!-- /Saman SEO -->
   ```

**Check Sitemaps:**
1. Visit `https://yoursite.com/sitemap_index.xml`
2. Verify sitemap loads without errors
3. Check individual sitemaps are listed

### Test with SEO Tools

**Google Search Console:**
1. Submit sitemap: `https://yoursite.com/sitemap_index.xml`
2. Request indexing for key pages
3. Monitor for errors

**Rich Results Test:**
1. Visit [Rich Results Test](https://search.google.com/test/rich-results)
2. Enter a page URL
3. Verify structured data is detected

**Facebook Debugger:**
1. Visit [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
2. Enter a page URL
3. Verify Open Graph tags are correct

**Twitter Card Validator:**
1. Visit [Twitter Card Validator](https://cards-dev.twitter.com/validator)
2. Enter a page URL
3. Verify Twitter Card displays correctly

---

## Recommended Settings by Site Type

### Blog/Magazine

```
Title Template: {{post_title}} | {{site_name}}
Description: Auto-excerpt
Sitemaps: Posts, Categories, Tags
RSS Sitemap: Enabled
Update Schedule: Daily
Internal Linking: Enabled (5-10 keywords)
AI Suggestions: Enabled
```

### Business/Corporate

```
Title Template: {{post_title}} | {{site_name}}
Description: Custom per page
Sitemaps: Pages only
RSS Sitemap: Disabled
Update Schedule: Weekly
Internal Linking: Enabled (service pages)
AI Suggestions: Optional
```

### E-commerce

```
Title Template: {{post_title}} - Buy {{category}} | {{site_name}}
Description: {{excerpt}} | Free shipping on orders over $50
Sitemaps: Products, Categories
RSS Sitemap: Optional
Update Schedule: Daily
Internal Linking: Enabled (product categories)
AI Suggestions: Enabled for product descriptions
```

### News Site

```
Title Template: {{post_title}} | {{site_name}} News
Description: Auto-excerpt
Sitemaps: Posts, Categories, Tags, Authors
RSS Sitemap: Enabled
Google News Sitemap: Enabled
Update Schedule: Hourly
Internal Linking: Enabled
AI Suggestions: Enabled
```

---

## Common Issues

### Sitemaps Not Working

**Problem:** 404 error on sitemap URLs

**Solution:**
1. Navigate to **Settings → Permalinks**
2. Click **Save Changes** (flushes rewrite rules)
3. Test sitemap URL again

### Meta Tags Not Appearing

**Problem:** Saman SEO tags not in page source

**Solution:**
1. Check **Saman SEO → Defaults** → ensure enabled
2. Disable other SEO plugins temporarily
3. Check theme's `wp_head()` function is present
4. Clear caching plugins

### Redirects Not Working

**Problem:** Redirects not triggering

**Solution:**
1. Ensure redirect path matches exactly (including trailing slash)
2. Clear browser cache
3. Test in incognito/private window
4. Check .htaccess for conflicts

### Performance Issues

**Problem:** Slow admin or frontend

**Solution:**
1. Disable AI features if not needed
2. Reduce sitemap update frequency
3. Use object caching (Redis/Memcached)
4. Increase PHP memory limit to 256M+

---

## Next Steps

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Advanced customization with filters and hooks
- **[Filter Reference](FILTERS.md)** - Complete filter documentation
- **[Sitemap Configuration](SITEMAPS.md)** - Advanced sitemap options
- **[WP-CLI Commands](WP_CLI.md)** - Command-line management

---

## Getting Help

- **Documentation:** [docs/](.)
- **Issues:** [GitHub Issues](https://github.com/jhd3197/WP-SEO-Pilot/issues)
- **Discussions:** [GitHub Discussions](https://github.com/jhd3197/WP-SEO-Pilot/discussions)

