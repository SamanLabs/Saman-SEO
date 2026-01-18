# WP-CLI Commands

Complete reference for Saman SEO WP-CLI commands.

---

## Installation

WP-CLI commands are automatically available when Saman SEO is active. No additional setup required.

Verify installation:
```bash
wp saman-seo 
```

---

## Redirect Management

### List Redirects

Display all configured redirects.

```bash
wp saman-seo redirects list [--format=<format>]
```

**Options:**
- `--format=<format>` - Output format: table, csv, json, yaml (default: table)

**Examples:**
```bash
# Display as table
wp saman-seo redirects list

# Export as CSV
wp saman-seo redirects list --format=csv > redirects.csv

# JSON output
wp saman-seo redirects list --format=json
```

**Sample Output (Table):**
```
+----+------------------+------------------+-------------+------+
| ID | Source           | Target           | Status Code | Hits |
+----+------------------+------------------+-------------+------+
| 1  | /old-page        | /new-page        | 301         | 142  |
| 2  | /about-us        | /company/about   | 301         | 87   |
| 3  | /contact-old     | /contact         | 301         | 23   |
+----+------------------+------------------+-------------+------+
```

### Export Redirects

Export all redirects to a JSON file.

```bash
wp saman-seo redirects export <file>
```

**Arguments:**
- `<file>` - Output filename (e.g., `redirects.json`)

**Example:**
```bash
wp saman-seo redirects export /tmp/redirects.json
```

**Export Format:**
```json
[
  {
    "source": "/old-page",
    "target": "/new-page",
    "status_code": 301,
    "hits": 142,
    "created": "2024-01-15 10:30:00",
    "modified": "2024-01-20 14:22:00"
  },
  {
    "source": "/about-us",
    "target": "/company/about",
    "status_code": 301,
    "hits": 87,
    "created": "2024-01-16 09:15:00",
    "modified": "2024-01-16 09:15:00"
  }
]
```

### Import Redirects

Import redirects from a JSON file.

```bash
wp saman-seo redirects import <file> [--skip-duplicates] [--update-existing]
```

**Arguments:**
- `<file>` - Input filename

**Options:**
- `--skip-duplicates` - Skip redirects that already exist
- `--update-existing` - Update existing redirects with new data

**Example:**
```bash
# Import new redirects only
wp saman-seo redirects import redirects.json --skip-duplicates

# Update existing redirects
wp saman-seo redirects import redirects.json --update-existing
```

**Import File Format:**
```json
[
  {
    "source": "/old-url",
    "target": "/new-url",
    "status_code": 301
  },
  {
    "source": "/another-old",
    "target": "/another-new",
    "status_code": 302
  }
]
```

### Add Redirect

Create a new redirect.

```bash
wp saman-seo redirects add <source> <target> [--status=<code>]
```

**Arguments:**
- `<source>` - Source path (e.g., `/old-page`)
- `<target>` - Target URL (e.g., `/new-page`)

**Options:**
- `--status=<code>` - HTTP status code (default: 301)

**Examples:**
```bash
# Create 301 redirect
wp saman-seo redirects add /old-page /new-page

# Create 302 temporary redirect
wp saman-seo redirects add /temp-page /final-page --status=302
```

### Delete Redirect

Remove a redirect.

```bash
wp saman-seo redirects delete <source>
```

**Arguments:**
- `<source>` - Source path of redirect to delete

**Example:**
```bash
wp saman-seo redirects delete /old-page
```

### Clear All Redirects

Delete all redirects (with confirmation).

```bash
wp saman-seo redirects clear [--yes]
```

**Options:**
- `--yes` - Skip confirmation prompt

**Example:**
```bash
wp saman-seo redirects clear --yes
```

---

## Sitemap Management

### Regenerate Sitemaps

Force regeneration of all sitemaps.

```bash
wp saman-seo sitemaps regenerate
```

**Example:**
```bash
wp saman-seo sitemaps regenerate
```

### Ping Search Engines

Notify search engines of sitemap updates.

```bash
wp saman-seo sitemaps ping
```

**Example:**
```bash
wp saman-seo sitemaps ping
```

### List Sitemap URLs

Display all sitemap URLs.

```bash
wp saman-seo sitemaps list
```

**Example:**
```bash
wp saman-seo sitemaps list
```

**Sample Output:**
```
Main Sitemap Index:
https://example.com/sitemap_index.xml

Post Type Sitemaps:
https://example.com/post-sitemap.xml
https://example.com/page-sitemap.xml
https://example.com/product-sitemap.xml

Taxonomy Sitemaps:
https://example.com/category-sitemap.xml
https://example.com/post_tag-sitemap.xml

Other Sitemaps:
https://example.com/sitemap-rss.xml
https://example.com/sitemap-news.xml
```

---

## SEO Audit

### Run Audit

Scan site for SEO issues.

```bash
wp saman-seo audit run [--post-type=<type>] [--limit=<number>]
```

**Options:**
- `--post-type=<type>` - Audit specific post type (default: all)
- `--limit=<number>` - Limit number of posts to audit

**Examples:**
```bash
# Audit all content
wp saman-seo audit run

# Audit only posts
wp saman-seo audit run --post-type=post

# Audit first 100 pages
wp saman-seo audit run --post-type=page --limit=100
```

### List Issues

Display audit issues.

```bash
wp saman-seo audit list [--severity=<level>] [--format=<format>]
```

**Options:**
- `--severity=<level>` - Filter by severity: critical, high, medium, low
- `--format=<format>` - Output format: table, csv, json

**Examples:**
```bash
# Show all issues
wp saman-seo audit list

# Show only critical issues
wp saman-seo audit list --severity=critical

# Export issues as CSV
wp saman-seo audit list --format=csv > seo-issues.csv
```

### Fix Issues

Auto-fix common SEO issues.

```bash
wp saman-seo audit fix [--issue-type=<type>] [--dry-run]
```

**Options:**
- `--issue-type=<type>` - Fix specific issue type: missing-title, missing-description, duplicate-title
- `--dry-run` - Show what would be fixed without making changes

**Examples:**
```bash
# Auto-generate missing titles
wp saman-seo audit fix --issue-type=missing-title

# Preview fixes without applying
wp saman-seo audit fix --dry-run
```

---

## Bulk Operations

### Bulk Update Meta

Update SEO metadata for multiple posts.

```bash
wp saman-seo meta update <field> <value> [--post-type=<type>] [--post-id=<ids>]
```

**Arguments:**
- `<field>` - Meta field: title, description, canonical, robots
- `<value>` - New value

**Options:**
- `--post-type=<type>` - Target specific post type
- `--post-id=<ids>` - Comma-separated post IDs

**Examples:**
```bash
# Set robots to noindex for all drafts
wp saman-seo meta update robots "noindex,nofollow" --post-type=post --post-status=draft

# Update canonical for specific posts
wp saman-seo meta update canonical "https://example.com/new-url" --post-id=123,456,789
```

### Bulk Generate Titles

Auto-generate SEO titles for posts missing them.

```bash
wp saman-seo meta generate-titles [--post-type=<type>] [--overwrite]
```

**Options:**
- `--post-type=<type>` - Target specific post type
- `--overwrite` - Replace existing titles

**Examples:**
```bash
# Generate missing titles for posts
wp saman-seo meta generate-titles --post-type=post

# Regenerate all page titles
wp saman-seo meta generate-titles --post-type=page --overwrite
```

### Bulk Generate Descriptions

Auto-generate meta descriptions.

```bash
wp saman-seo meta generate-descriptions [--post-type=<type>] [--overwrite]
```

**Options:**
- `--post-type=<type>` - Target specific post type
- `--overwrite` - Replace existing descriptions

**Examples:**
```bash
# Generate missing descriptions
wp saman-seo meta generate-descriptions --post-type=post

# Regenerate all descriptions
wp saman-seo meta generate-descriptions --overwrite
```

---

## Migration & Import

### Import from Yoast

Import SEO data from Yoast SEO.

```bash
wp saman-seo import yoast [--dry-run] [--post-type=<type>]
```

**Options:**
- `--dry-run` - Preview import without making changes
- `--post-type=<type>` - Import specific post type only

**Example:**
```bash
# Preview Yoast import
wp saman-seo import yoast --dry-run

# Import Yoast data
wp saman-seo import yoast
```

### Import from Rank Math

Import SEO data from Rank Math.

```bash
wp saman-seo import rankmath [--dry-run] [--post-type=<type>]
```

**Example:**
```bash
wp saman-seo import rankmath
```

### Import from All in One SEO

Import SEO data from All in One SEO Pack.

```bash
wp saman-seo import aioseo [--dry-run] [--post-type=<type>]
```

**Example:**
```bash
wp saman-seo import aioseo
```

---

## Cache Management

### Clear SEO Cache

Clear all Saman SEO cached data.

```bash
wp saman-seo cache clear [--type=<cache-type>]
```

**Options:**
- `--type=<cache-type>` - Clear specific cache: sitemaps, metadata, redirects, all

**Examples:**
```bash
# Clear all caches
wp saman-seo cache clear

# Clear only sitemap cache
wp saman-seo cache clear --type=sitemaps
```

---

## Scheduled Tasks

### List Cron Jobs

Show scheduled Saman SEO tasks.

```bash
wp saman-seo cron list
```

**Example:**
```bash
wp saman-seo cron list
```

**Sample Output:**
```
Scheduled Tasks:
- samanseo_sitemap_update: Next run in 2 hours
- samanseo_audit_scan: Next run in 1 day
- samanseo_cleanup_logs: Next run in 7 days
```

---

## Advanced Usage

### Chain Commands

Combine multiple commands for complex operations:

```bash
# Export redirects, clear all, import new set
wp saman-seo redirects export backup.json && \
wp saman-seo redirects clear --yes && \
wp saman-seo redirects import new-redirects.json
```

### Cron Integration

Run maintenance tasks via system cron:

```bash
# Add to crontab
0 2 * * * /usr/local/bin/wp saman-seo sitemaps regenerate --path=/var/www/html
0 3 * * 0 /usr/local/bin/wp saman-seo audit run --path=/var/www/html
```

### Scripted Bulk Updates

Automate SEO updates:

```bash
#!/bin/bash

# Backup current redirects
wp saman-seo redirects export "/backups/redirects-$(date +%Y%m%d).json"

# Run audit
wp saman-seo audit run

# Auto-fix issues
wp saman-seo audit fix

# Regenerate sitemaps
wp saman-seo sitemaps regenerate

# Ping search engines
wp saman-seo sitemaps ping

echo "SEO maintenance complete"
```

---

## Debugging

### Enable Verbose Output

Add `--debug` flag to any command:

```bash
wp saman-seo redirects list --debug
```

### Test Individual Commands

Use `--dry-run` when available:

```bash
wp saman-seo audit fix --dry-run
wp saman-seo import yoast --dry-run
```

---

## Return Codes

WP-CLI commands use standard exit codes:

- `0` - Success
- `1` - General error
- `2` - Invalid argument

**Example in Scripts:**
```bash
if wp saman-seo redirects add /old /new; then
    echo "Redirect created successfully"
else
    echo "Failed to create redirect"
    exit 1
fi
```

---

## Further Reading

- **[Developer Guide](DEVELOPER_GUIDE.md)** - PHP integration and filters
- **[Sitemap Configuration](SITEMAPS.md)** - Advanced sitemap customization
- **[Filter Reference](FILTERS.md)** - Complete filter documentation
