# Saman SEO

<p align="center">
  <img width="640" alt="Saman SEO" src="https://github.com/user-attachments/assets/5d0ac010-07d2-41bf-9793-321523a225f4" />

</p>

<p align="center">
  <strong>The Open Standard for WordPress SEO</strong>
</p>

<p align="center">
  <a href="https://github.com/SamanLabs/Saman-SEO/releases">
    <img src="https://img.shields.io/github/v/release/SamanLabs/Saman-SEO?style=flat-square&color=blue" alt="Latest Release">
  </a>
  <a href="https://github.com/SamanLabs/Saman-SEO/blob/main/LICENSE">
    <img src="https://img.shields.io/github/license/SamanLabs/Saman-SEO?style=flat-square&color=green" alt="License">
  </a>
  <a href="https://github.com/SamanLabs/Saman-SEO/stargazers">
    <img src="https://img.shields.io/github/stars/SamanLabs/Saman-SEO?style=flat-square&color=yellow" alt="Stars">
  </a>
  <a href="https://github.com/SamanLabs/Saman-SEO/network/members">
    <img src="https://img.shields.io/github/forks/SamanLabs/Saman-SEO?style=flat-square&color=orange" alt="Forks">
  </a>
  <a href="https://github.com/SamanLabs/Saman-SEO/issues">
    <img src="https://img.shields.io/github/issues/SamanLabs/Saman-SEO?style=flat-square&color=red" alt="Issues">
  </a>
</p>

<p align="center">
  <a href="https://wordpress.org/">
    <img src="https://img.shields.io/badge/WordPress-5.8%2B-21759B?style=flat-square&logo=wordpress" alt="WordPress Version">
  </a>
  <a href="https://php.net/">
    <img src="https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat-square&logo=php" alt="PHP Version">
  </a>
  <a href="https://github.com/SamanLabs/Saman-SEO/graphs/contributors">
    <img src="https://img.shields.io/github/contributors/SamanLabs/Saman-SEO?style=flat-square&color=blueviolet" alt="Contributors">
  </a>
</p>

<p align="center">
  <a href="https://github.com/SamanLabs/Saman-SEO/discussions">
    <img src="https://img.shields.io/badge/Discussions-Join%20Us-brightgreen?style=flat-square&logo=github" alt="Discussions">
  </a>
</p>

<p align="center">
  A comprehensive, transparent SEO solution built for developers who believe SEO tooling should be open source, not a black box.
</p>

---

## Why Open Source SEO?

For too long, WordPress SEO has been dominated by proprietary solutions that operate as black boxes. Each plugin guards its methods as trade secrets, fragmenting the ecosystem and forcing developers to work around opaque systems.

**Saman SEO takes a different approach**: We believe the SEO industry benefits from transparency, shared standards, and collaborative improvement. By open-sourcing our complete SEO workflow, we're establishing a foundation that the entire WordPress community can build upon, inspect, and enhance.

This is SEO without secrets—because better SEO comes from better collaboration, not better secrecy.

---

## Features

### Core SEO Management
- **Per-Post SEO Fields**: Complete meta control stored in `_wpseopilot_meta` with Gutenberg sidebar and classic editor support
- **Server-Rendered Output**: `<title>`, meta descriptions, canonical URLs, robots directives, Open Graph, Twitter Cards, and JSON-LD structured data
- **Site-Wide Templates**: Centralized defaults for titles, descriptions, social images, robots directives, and hreflang attributes
- **Post-Type Granularity**: Dedicated defaults for each content type—no more one-size-fits-all configurations

### Advanced Capabilities
- **AI-Powered Suggestions**: OpenAI integration for intelligent title and meta description generation with customizable prompts and models
- **Internal Linking Engine**: Automated keyword-to-link conversion with rule-based controls, category targeting, and comprehensive previews
- **Advanced Sitemap Manager**: Full XML sitemap control with dedicated admin UI, post type/taxonomy selection, archive support, RSS & Google News sitemaps, custom pages, and scheduled regeneration
- **Audit Dashboard**: Visual severity graphs, issue logging, and automatic fallback generation for missing metadata
- **Redirect Manager**: Database-backed 301 redirects with WP-CLI support and 404 logging
- **Developer Tools**: Snippet previews, social media card previews, internal link suggestions, and compatibility detection

### Content Analysis
- **Real-Time Previews**: SERP snippet and social media card simulations
- **Link Opportunity Detection**: AI-powered internal linking suggestions
- **Quick Actions**: Streamlined workflow for common SEO tasks
- **Compatibility Checks**: Automatic detection and graceful coexistence with other SEO plugins

---

## Documentation

### Getting Started
- **[Getting Started](docs/GETTING_STARTED.md)** - Installation, configuration, and basic usage

### Developer Resources
- **[Developer Guide](docs/DEVELOPER_GUIDE.md)** - Filters, hooks, and programmatic control
- **[Filter Reference](docs/FILTERS.md)** - Complete filter documentation with examples
- **[Template Tags & Shortcodes](docs/TEMPLATE_TAGS.md)** - Theme integration reference
- **[WP-CLI Commands](docs/WP_CLI.md)** - Command-line interface documentation

### Feature Guides
- **[Sitemap Configuration](docs/SITEMAPS.md)** - Advanced sitemap customization
- **[AI Assistant](docs/AI_ASSISTANT.md)** - AI-powered metadata generation
- **[Internal Linking](docs/INTERNAL_LINKING.md)** - Automated internal link management
- **[Redirect Manager](docs/REDIRECTS.md)** - 301 redirects and 404 monitoring
- **[Local SEO](docs/LOCAL_SEO.md)** - Local business schema and settings

---

## Quick Start

### Installation

1. Download the latest release or clone this repository
2. Upload to `/wp-content/plugins/wp-seo-pilot/`
3. Activate through the WordPress admin interface
4. Navigate to **Saman SEO → Defaults** to configure site-wide settings

### Basic Usage

**Per-Post Configuration:**
Edit any post or page and locate the "Saman SEO" meta box or Gutenberg sidebar panel. Configure:
- SEO Title
- Meta Description  
- Canonical URL
- Robots Directives
- Open Graph Image

**Site-Wide Defaults:**
Navigate to **Saman SEO → Defaults** to set template-driven defaults that apply when per-post values aren't specified.

---

## Developer Integration

### Template Tags

```php
// Render breadcrumbs in your theme
if ( function_exists( 'wpseopilot_breadcrumbs' ) ) {
    wpseopilot_breadcrumbs();
}
```

### Programmatic Redirects

```php
// Create a 301 redirect programmatically
$result = wpseopilot_create_redirect( '/old-url', '/new-url' );

if ( is_wp_error( $result ) ) {
    error_log( 'Redirect failed: ' . $result->get_error_message() );
}
```

### Filter Hooks

```php
// Modify SEO title dynamically
add_filter( 'wpseopilot_title', function( $title, $post ) {
    if ( is_singular( 'product' ) ) {
        return $title . ' | Buy Now';
    }
    return $title;
}, 10, 2 );

// Override Open Graph image
add_filter( 'wpseopilot_og_image', function( $image, $post ) {
    if ( $post && $post->ID === 42 ) {
        return 'https://cdn.example.com/special-image.jpg';
    }
    return $image;
}, 10, 2 );
```

For comprehensive filter documentation, see **[docs/FILTERS.md](docs/FILTERS.md)**.

---

## WP-CLI Support

```bash
# List all redirects
wp wpseopilot redirects list --format=table

# Export redirects to JSON
wp wpseopilot redirects export redirects.json

# Import redirects from JSON
wp wpseopilot redirects import redirects.json
```

Full WP-CLI documentation: **[docs/WP_CLI.md](docs/WP_CLI.md)**

---

## Contributing

We welcome contributions from the community. Whether you're fixing bugs, adding features, improving documentation, or suggesting enhancements, your input helps establish better standards for WordPress SEO.

See **[CONTRIBUTING.md](CONTRIBUTING.md)** for guidelines.

---

## Privacy & Security

- **404 Logging**: Opt-in feature that stores only hashed referrers
- **No Telemetry**: Zero external tracking or analytics
- **No External Requests**: All processing happens on your server (except optional AI features when explicitly enabled)

---

## Asset Development

The plugin uses Less for stylesheet compilation.

```bash
# Install dependencies
npm install

# Build CSS from Less sources
npm run build

# Watch for changes during development
npm run watch
```

---

## License

[Your License Here]

---

## Support

- **Issues**: [GitHub Issues](https://github.com/SamanLabs/Saman-SEO/issues)
- **Documentation**: [Full Documentation](docs/)
- **Community**: [Discussions](https://github.com/SamanLabs/Saman-SEO/discussions)

---

**Built with transparency. Built for the community. Built to be better.**
