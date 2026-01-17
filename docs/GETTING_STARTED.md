# Getting Started with Saman SEO

This guide will help you install, configure, and start using Saman SEO for your WordPress site.

---

## Installation

### Via WordPress Admin

1. Download the latest release from the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot/releases)
2. Navigate to **Plugins → Add New → Upload Plugin** in your WordPress admin
3. Choose the downloaded ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Via FTP/File Manager

1. Download and extract the plugin ZIP file
2. Upload the `wp-seo-pilot` folder to `/wp-content/plugins/`
3. Navigate to **Plugins** in your WordPress admin
4. Find **Saman SEO** and click **Activate**

### Via WP-CLI

```bash
wp plugin install wp-seo-pilot --activate
```

---

## Initial Configuration

After activation, Saman SEO will automatically:
- Create necessary database tables for redirects and 404 logging
- Register default settings with sensible fallbacks
- Set up sitemap functionality
- Configure rewrite rules

### Step 1: Configure Site-Wide Defaults

Navigate to **Saman SEO → General Settings** to configure:

#### Title Templates

Set how page titles should be generated across your site:

- **Default Title Template**: `{{post_title}} | {{site_title}}`
- **Title Separator**: Choose from `-`, `|`, `•`, `>`, or custom

**Available Template Variables:**
- `{{post_title}}` - Post/page title
- `{{site_title}}` - Site name from WordPress settings
- `{{post_type}}` - Post type label
- `{{category}}` - Primary category name
- `{{tag}}` - Primary tag name
- `{{author}}` - Author display name
- `{{date}}` - Publication date
- `{{excerpt}}` - Post excerpt

**Example Title Templates:**
```
Posts: {{post_title}} | {{site_title}}
Pages: {{post_title}} - {{site_title}}
Products: {{post_title}} | Buy Online | {{site_title}}
```

#### Default Meta Description

Provide a fallback description used when individual posts don't have custom descriptions:

```
Your site tagline or a brief description of what your site offers.
```

#### Default Robots Directives

- **Default Noindex**: Check to prevent search engine indexing by default
- **Default Nofollow**: Check to prevent following links by default
- **Global Robots**: Set site-wide robots meta tag (e.g., `index, follow`)

### Step 2: Configure Search Appearance

Navigate to **Saman SEO → Search Appearance** to set:

#### Homepage SEO

- **Homepage Title**: Custom title for your homepage (overrides template)
- **Homepage Description**: Meta description for homepage
- **Homepage Keywords**: Comma-separated keywords (optional, not widely used by search engines)

#### Knowledge Graph

Tell search engines about your site's entity:

- **Type**: Organization or Person
- **Organization Name**: Your business/brand name
- **Organization Logo**: URL to your logo (recommended: 600x60px minimum)

#### Post Type Defaults

For each post type (Posts, Pages, Custom Post Types), configure:

- **Title Template**: How titles should be generated
- **Meta Description Template**: Default description pattern
- **Keywords Template**: Default keywords pattern
- **Robots Settings**: Default indexing behavior

### Step 3: Configure Social Media

Navigate to **Saman SEO → Social Settings**:

#### Open Graph Defaults

- **Default OG Title Template**: Fallback for Facebook sharing
- **Default OG Description Template**: Fallback for Facebook description
- **Default OG Image**: Default image for social sharing (recommended: 1200x630px)

#### Twitter Card Defaults

- **Default Twitter Title Template**
- **Default Twitter Description Template**
- **Image Source**: Choose between featured image, custom, or default

#### Social Image Dimensions

- **Width**: Default 1200px
- **Height**: Default 630px

---

## Post-Level Configuration

### Using the Classic Editor

When editing any post or page, look for the **Saman SEO** meta box in the sidebar:

#### Basic Fields

- **SEO Title**: Override the default template-generated title
- **Meta Description**: Custom description for this specific post
- **Canonical URL**: Set if this content is duplicated elsewhere
- **Focus Keyword**: Target keyword for analysis (optional)

#### Robots Directives

- **Noindex**: Prevent this page from appearing in search results
- **Nofollow**: Tell search engines not to follow links on this page

#### Social Media

- **Custom OG Image**: Upload a custom image for social sharing

### Using the Block Editor (Gutenberg)

1. Open any post or page in the block editor
2. Look for the **Saman SEO** panel in the right sidebar
3. Configure the same options as classic editor
4. See live preview of how your content will appear in search results

### SEO Score

Saman SEO calculates an SEO score based on:
- Title length (optimal: 50-60 characters)
- Description length (optimal: 150-160 characters)
- Keyword usage
- Content length
- Internal/external links
- Image optimization

Scores appear in the post list table for quick overview.

---

## Core Features Overview

### 1. Per-Post SEO Fields

Every post and page gets granular control over:
- SEO title and meta description
- Canonical URLs
- Robots indexing directives
- Open Graph and Twitter Card metadata
- Custom social images

**Stored as:** Single JSON object in `_wpseopilot_meta` post meta key

### 2. XML Sitemaps

Navigate to **Saman SEO → Sitemaps**:

- **Enable/Disable Sitemaps**: Toggle sitemap generation
- **Post Types**: Choose which content types to include
- **Taxonomies**: Include category/tag archives
- **Author Pages**: Include author archive pages
- **Date Archives**: Include date-based archives
- **Max URLs**: Limit URLs per sitemap page (default: 2000)
- **Dynamic Generation**: Generate on-the-fly vs. scheduled

**Access Your Sitemap:**
```
https://yoursite.com/wp-sitemap.xml
```

**Advanced Features:**
- Image inclusion in sitemaps
- Google News sitemap
- RSS feed sitemap
- Custom page additions
- Scheduled regeneration

See **[Sitemap Configuration](SITEMAPS.md)** for advanced options.

### 3. Redirect Manager

Navigate to **Saman SEO → Redirects**:

- Create 301, 302, 307, or 308 redirects
- Automatic redirect suggestions when you change post slugs
- Track redirect hits and analytics
- Import/export redirects via WP-CLI

**Creating a Redirect:**

1. Enter **Source Path**: `/old-url`
2. Enter **Target URL**: `/new-url` or `https://external.com`
3. Choose **Status Code**: 301 (permanent) or 302 (temporary)
4. Click **Add Redirect**

See **[Redirect Manager](REDIRECTS.md)** for programmatic usage.

### 4. 404 Monitoring

Navigate to **Saman SEO → 404 Monitor**:

- View all 404 errors on your site
- See hit counts and last occurrence
- Detect user agents and devices
- Privacy-focused: only hashed referrers stored

**Using 404 Data:**
- Identify broken links
- Create redirects for common 404s
- Monitor crawl errors

### 5. SEO Audit Dashboard

Navigate to **Saman SEO → Audit**:

- Site-wide SEO analysis
- Missing meta title/description detection
- Internal linking opportunities
- Issue severity categorization
- Bulk recommendations

### 6. Internal Linking Engine

Navigate to **Saman SEO → Internal Linking**:

Automatically insert internal links based on rules:

1. **Create Link Rules**: Define keyword → URL mappings
2. **Organize with Categories**: Group related rules
3. **Configure Settings**: Control link insertion behavior
4. **Add UTM Templates**: Track internal link performance

See **[Internal Linking Guide](INTERNAL_LINKING.md)** for details.

### 7. AI-Powered Suggestions

Navigate to **Saman SEO → AI Assistant**:

**Requirements:**
- OpenAI API key (configure in General Settings)

**Features:**
- AI-generated title suggestions
- AI-generated meta descriptions
- Batch generation for multiple posts
- Customizable prompts and models

See **[AI Assistant Guide](AI_ASSISTANT.md)** for configuration.

---

## Basic Workflow

### For New Posts

1. Write your content in the WordPress editor
2. Scroll to the **Saman SEO** meta box
3. Review the auto-generated title (from template)
4. Write a compelling meta description (150-160 chars)
5. Add a focus keyword (optional)
6. Check the SEO score and address any issues
7. Preview how it looks in search results
8. Publish

### For Existing Content

1. Navigate to **Saman SEO → Audit**
2. Review flagged issues (missing descriptions, etc.)
3. Click through to edit posts with problems
4. Fill in missing metadata
5. Re-run audit to verify fixes

### For Site-Wide Changes

1. Navigate to **Saman SEO → General Settings**
2. Update title templates or defaults
3. Changes apply immediately to all posts without custom values
4. Use **Saman SEO → Search Appearance** to configure post type defaults

---

## Post Meta Storage

All SEO metadata is stored in a single post meta key: `_wpseopilot_meta`

**Structure:**
```json
{
  "title": "Custom SEO Title",
  "description": "Custom meta description",
  "canonical": "https://example.com/canonical-url",
  "noindex": "1",
  "nofollow": "0",
  "og_image": "https://example.com/image.jpg"
}
```

This format is:
- **REST API compatible**: Exposed via WordPress REST API
- **Portable**: Easy to export/import
- **Efficient**: Single database query per post
- **Extensible**: Additional fields can be added programmatically

---

## Compatibility

### Other SEO Plugins

Saman SEO automatically detects and gracefully coexists with:
- Yoast SEO
- Rank Math
- All in One SEO Pack

**Behavior:**
- Admin notice displays if conflicts detected
- Certain features disable to prevent duplication
- You can run multiple plugins, but it's recommended to choose one

### Theme Compatibility

Saman SEO works with any WordPress theme because:
- Meta tags render via `wp_head` hook (standard)
- No theme modifications required
- Template tags available for breadcrumbs if desired

### Page Builder Compatibility

Works seamlessly with:
- Gutenberg (Block Editor)
- Classic Editor
- Elementor
- Beaver Builder
- Divi Builder
- WPBakery

---

## Minimum Requirements

- **WordPress**: 5.8 or higher
- **PHP**: 7.4 or higher
- **MySQL**: 5.6 or higher (5.7+ recommended)

---

## Next Steps

Now that you have Saman SEO configured, explore these guides:

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Extend functionality with filters and hooks
- **[Filter Reference](FILTERS.md)** - Complete filter documentation
- **[Template Tags](TEMPLATE_TAGS.md)** - Add SEO features to your theme
- **[WP-CLI Commands](WP_CLI.md)** - Manage SEO via command line
- **[Sitemap Configuration](SITEMAPS.md)** - Advanced sitemap customization
- **[AI Assistant](AI_ASSISTANT.md)** - Configure AI-powered features
- **[Internal Linking](INTERNAL_LINKING.md)** - Automate internal link building
- **[Redirects](REDIRECTS.md)** - Manage 301 redirects
- **[Local SEO](LOCAL_SEO.md)** - Configure local business schema

---

## Getting Help

- **Issues**: [GitHub Issues](https://github.com/jhd3197/WP-SEO-Pilot/issues)
- **Documentation**: [Full Documentation](https://github.com/jhd3197/WP-SEO-Pilot/tree/main/docs)
- **Community**: [Discussions](https://github.com/jhd3197/WP-SEO-Pilot/discussions)

---

**Welcome to transparent, open-source SEO for WordPress!**
