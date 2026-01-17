# Redirect Manager Guide

Complete guide to managing 301 redirects and monitoring 404 errors with Saman SEO.

---

## Table of Contents

- [Overview](#overview)
- [Creating Redirects](#creating-redirects)
- [Managing Redirects](#managing-redirects)
- [Automatic Redirect Suggestions](#automatic-redirect-suggestions)
- [404 Error Monitoring](#404-error-monitoring)
- [WP-CLI Integration](#wp-cli-integration)
- [Programmatic Usage](#programmatic-usage)
- [Best Practices](#best-practices)
- [Troubleshooting](#troubleshooting)

---

## Overview

Saman SEO includes a powerful redirect manager that:

- Creates and manages 301, 302, 307, and 308 redirects
- Automatically suggests redirects when post slugs change
- Tracks redirect hit counts and analytics
- Monitors 404 errors
- Exports/imports redirects via WP-CLI
- Stores redirects in a dedicated database table

**Location:** Navigate to **Saman SEO → Redirects**

**File:** `includes/class-wpseopilot-service-redirect-manager.php`

**Database Table:** `wp_wpseopilot_redirects`

---

## Creating Redirects

### Via Admin UI

1. Navigate to **Saman SEO → Redirects**
2. Click **Add New Redirect**
3. Fill in the form:
   - **Source Path**: The URL to redirect from (e.g., `/old-page`)
   - **Target URL**: Where to redirect to (e.g., `/new-page` or `https://external.com`)
   - **Status Code**: Select redirect type
4. Click **Add Redirect**

---

### Redirect Types

| Status Code | Type | Usage |
|-------------|------|-------|
| **301** | Permanent Redirect | Default. Use when content permanently moved |
| **302** | Temporary Redirect | Use for temporary moves (sales, promos) |
| **307** | Temporary Redirect (HTTP/1.1) | Same as 302 but preserves request method |
| **308** | Permanent Redirect (HTTP/1.1) | Same as 301 but preserves request method |

**Recommendation:** Use 301 for most cases.

---

### Source Path Format

**Correct:**
```
/old-page
/old-page/
/category/old-post
/blog/2024/01/article
```

**Incorrect:**
```
https://example.com/old-page  (Don't include domain)
old-page  (Must start with /)
```

---

### Target URL Format

**Internal Redirects:**
```
/new-page
/new-page/
/category/new-post
```

**External Redirects:**
```
https://newsite.com/page
https://example.com/external-resource
```

---

## Managing Redirects

### Viewing All Redirects

Navigate to **Saman SEO → Redirects** to see:

- Source path
- Target URL
- Status code
- Hit count (how many times triggered)
- Last hit timestamp
- Actions (Edit, Delete)

---

### Editing Redirects

1. Find the redirect in the list
2. Click **Edit**
3. Modify fields
4. Click **Save Changes**

---

### Deleting Redirects

1. Find the redirect in the list
2. Click **Delete**
3. Confirm deletion

**Bulk Delete:**
1. Check multiple redirects
2. Select **Delete** from bulk actions
3. Click **Apply**

---

### Searching Redirects

Use the search box to filter by:
- Source path
- Target URL

**Example:**
- Search: `/old` - Shows all redirects with "/old" in source or target

---

### Sorting Redirects

Click column headers to sort by:
- Source (alphabetical)
- Target (alphabetical)
- Status Code
- Hits (most/least triggered)
- Last Hit (most/least recent)

---

## Automatic Redirect Suggestions

Saman SEO automatically detects when you change a post's slug and suggests creating a redirect.

### How It Works

1. You edit a post and change the permalink/slug
2. Post is updated
3. Saman SEO detects the slug change
4. A redirect suggestion appears in **Saman SEO → Redirects**

---

### Accepting Redirect Suggestions

1. Navigate to **Saman SEO → Redirects**
2. Look for the **Pending Suggestions** section
3. Review the suggested redirect:
   - Old slug → New slug
4. Click **Create Redirect** to accept
5. Click **Dismiss** to ignore

---

### Example Scenario

**Original Slug:** `/how-to-use-wordpress-2024/`

You update it to:

**New Slug:** `/wordpress-guide/`

**Suggestion:**
```
Source: /how-to-use-wordpress-2024/
Target: /wordpress-guide/
Status: 301 (Permanent)
```

Click **Create Redirect** to implement.

---

## 404 Error Monitoring

Track 404 errors (page not found) to identify broken links and redirect opportunities.

**Location:** Navigate to **Saman SEO → 404 Monitor**

**File:** `includes/class-wpseopilot-service-request-monitor.php`

**Database Table:** `wp_wpseopilot_404_log`

---

### 404 Log Features

View all 404 errors with:
- Request URI (the broken URL)
- Hit count (how many times requested)
- Last seen timestamp
- User agent summary
- Device type (desktop, mobile, tablet)

---

### Using 404 Data

#### 1. Identify Popular Broken Links

Sort by hit count to find frequently requested 404s.

**Example:**
```
/old-popular-post  |  42 hits  |  Desktop
```

**Action:** Create a redirect to the new location.

---

#### 2. Find Crawl Errors

Look for 404s from search engine bots:

**Example:**
```
User Agent: Googlebot
Request: /deleted-page
```

**Action:** Redirect or restore the page.

---

#### 3. Detect Typos

Look for common typos in URLs:

**Example:**
```
/wordpres-hosting  (typo)
/wordpress-hosting  (correct)
```

**Action:** Create redirect for the typo.

---

### Creating Redirects from 404 Log

1. Navigate to **Saman SEO → 404 Monitor**
2. Find a 404 error worth redirecting
3. Click **Create Redirect**
4. Enter target URL
5. Click **Save**

---

### Privacy Considerations

**What's Logged:**
- Request URI
- User agent (browser/bot info)
- Device type
- Hit count
- Last seen timestamp

**What's NOT Logged:**
- IP addresses
- Full referrer URLs (only hashed)
- Personal information

**Disable 404 Logging:**

```php
update_option( 'wpseopilot_enable_404_logging', '0' );
```

---

## WP-CLI Integration

Manage redirects via command line for bulk operations and automation.

**See:** [WP-CLI Commands Documentation](WP_CLI.md) for complete details.

---

### List All Redirects

```bash
wp wpseopilot redirects list --format=table
```

---

### Export Redirects

```bash
wp wpseopilot redirects export redirects.json
```

---

### Import Redirects

```bash
wp wpseopilot redirects import redirects.json
```

---

## Programmatic Usage

### Create Redirect

```php
// Create a 301 redirect
$result = wpseopilot_create_redirect( '/old-url', '/new-url', 301 );

if ( is_wp_error( $result ) ) {
    echo 'Error: ' . $result->get_error_message();
} else {
    echo 'Redirect created successfully!';
}
```

**Function:** `wpseopilot_create_redirect()`

**Location:** `includes/helpers.php:733`

---

### Create Multiple Redirects

```php
$redirects = [
    [ '/old-1', '/new-1', 301 ],
    [ '/old-2', '/new-2', 301 ],
    [ '/old-3', 'https://external.com', 301 ]
];

foreach ( $redirects as $redirect ) {
    wpseopilot_create_redirect( $redirect[0], $redirect[1], $redirect[2] );
}
```

---

### Auto-Redirect on Post Deletion

```php
add_action( 'trashed_post', function( $post_id ) {
    $post = get_post( $post_id );
    $old_url = '/' . $post->post_name;

    // Redirect deleted posts to homepage or category
    wpseopilot_create_redirect( $old_url, home_url( '/' ), 301 );
});
```

---

### Query Redirects Directly

```php
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_redirects';

$redirects = $wpdb->get_results( "
    SELECT * FROM {$table}
    WHERE status_code = 301
    ORDER BY hits DESC
    LIMIT 10
" );

foreach ( $redirects as $redirect ) {
    echo "{$redirect->source} → {$redirect->target} ({$redirect->hits} hits)\n";
}
```

---

### Delete Redirect Programmatically

```php
global $wpdb;
$table = $wpdb->prefix . 'wpseopilot_redirects';

$wpdb->delete( $table, [ 'source' => '/old-url' ], [ '%s' ] );
```

---

## Best Practices

### 1. Use 301 for Permanent Changes

When content is permanently moved:
- Changed permalink
- Merged posts
- Deleted pages with replacement

**Don't use 301 for:**
- Temporary promotions (use 302)
- A/B testing (use 302)

---

### 2. Avoid Redirect Chains

**Bad:**
```
/page-a → /page-b → /page-c → /final-page
```

**Good:**
```
/page-a → /final-page
/page-b → /final-page
/page-c → /final-page
```

**Fix chains:**

1. Audit redirects
2. Update old redirects to point directly to final destination
3. Remove intermediate redirects

---

### 3. Clean Up Unused Redirects

Periodically remove redirects with 0 hits after 6+ months:

```bash
wp wpseopilot redirects list --format=csv | awk -F',' '$5 == 0 {print $0}'
```

---

### 4. Monitor High-Traffic Redirects

Redirects with 100+ hits may indicate:
- Outdated external links
- Popular old URLs still being shared
- Broken navigation

**Action:** Update external backlinks to point directly to new URL.

---

### 5. Test Redirects

Always test after creating:

```bash
curl -I https://yoursite.com/old-url
```

Look for:
```
HTTP/1.1 301 Moved Permanently
Location: https://yoursite.com/new-url
```

---

### 6. Document Important Redirects

For critical redirects, document:
- Why redirect was created
- When it was created
- Expected duration (temporary redirects)

---

### 7. Backup Before Bulk Operations

Before importing or bulk deleting:

```bash
wp wpseopilot redirects export backup-$(date +%Y%m%d).json
```

---

## Common Use Cases

### Use Case 1: Site Migration

Moving from old domain to new domain:

```php
// Redirect all old site URLs to new site
add_action( 'template_redirect', function() {
    if ( $_SERVER['HTTP_HOST'] === 'oldsite.com' ) {
        $new_url = 'https://newsite.com' . $_SERVER['REQUEST_URI'];
        wp_redirect( $new_url, 301 );
        exit;
    }
});
```

---

### Use Case 2: URL Structure Change

Changed from `/blog/post-name` to `/post-name`:

```php
// Bulk create redirects
$posts = get_posts( [ 'numberposts' => -1 ] );

foreach ( $posts as $post ) {
    $old_url = '/blog/' . $post->post_name;
    $new_url = '/' . $post->post_name;

    wpseopilot_create_redirect( $old_url, $new_url, 301 );
}
```

---

### Use Case 3: Seasonal Redirects

Redirect old seasonal content:

```php
// Redirect 2024 holiday guide to 2025
wpseopilot_create_redirect(
    '/holiday-shopping-guide-2024',
    '/holiday-shopping-guide-2025',
    302  // Temporary, since it's seasonal
);
```

---

### Use Case 4: Product Discontinuation

Product no longer available, redirect to category:

```php
wpseopilot_create_redirect(
    '/product/discontinued-item',
    '/product-category/similar-products',
    301
);
```

---

### Use Case 5: Consolidating Content

Merged multiple posts into comprehensive guide:

```php
$old_posts = [ '/post-1', '/post-2', '/post-3' ];
$new_guide = '/complete-guide';

foreach ( $old_posts as $old_post ) {
    wpseopilot_create_redirect( $old_post, $new_guide, 301 );
}
```

---

## Troubleshooting

### Redirect Not Working

**Check:**

1. Source path is exact match (case-sensitive)
2. Redirect is saved in database (check admin list)
3. No conflicting redirects
4. `.htaccess` not overriding
5. Caching plugin cleared

**Debug:**

```php
// Add to functions.php temporarily
add_action( 'template_redirect', function() {
    global $wpdb;
    $table = $wpdb->prefix . 'wpseopilot_redirects';
    $path = $_SERVER['REQUEST_URI'];

    $redirect = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM {$table} WHERE source = %s",
        $path
    ));

    error_log( 'Redirect lookup for ' . $path . ': ' . print_r( $redirect, true ) );
}, 1 );
```

---

### Redirect Loop

**Symptoms:** Browser shows "Too many redirects" error

**Cause:** Source and target are the same, or chain loops back

**Example:**
```
/page-a → /page-b
/page-b → /page-a
```

**Fix:**

1. Identify the loop
2. Delete or update one redirect
3. Clear browser cache

---

### 404s Still Appearing in Google

**Issue:** Old 404 URLs still in Google Search Console

**Explanation:** It takes time for Google to recrawl and update

**Action:**

1. Create redirects for the 404s
2. Submit sitemap to Google Search Console
3. Request re-indexing of specific URLs
4. Wait for next crawl (can take days/weeks)

---

### Redirect Suggestions Not Appearing

**Check:**

1. Feature is enabled
2. You're actually changing the slug, not the title
3. Post is published (not draft)

**Enable manually:**

```php
update_option( 'wpseopilot_enable_redirect_manager', '1' );
```

---

## Performance Considerations

### Caching

Redirects are cached in WordPress object cache for performance.

**Cache Key:** `wpseopilot_redirects`

**Clear cache:**

```php
wp_cache_delete( 'wpseopilot_redirects', 'wpseopilot_redirects' );
```

---

### Database Indexing

The redirects table is indexed on `source` for fast lookups.

---

### High-Volume Redirects

For sites with 1,000+ redirects:

1. Consider server-level redirects (Nginx/Apache)
2. Use redirect plugins with caching
3. Implement lazy-loading of redirect rules

---

## Import/Export Format

### JSON Structure

```json
[
  {
    "source": "/old-url",
    "target": "/new-url",
    "status_code": 301,
    "hits": 0,
    "last_hit": null
  },
  {
    "source": "/another-old",
    "target": "https://external.com/page",
    "status_code": 302,
    "hits": 15,
    "last_hit": "2025-12-15 10:30:00"
  }
]
```

**Required Fields:**
- `source` (string)
- `target` (string)
- `status_code` (int)

**Optional Fields:**
- `hits` (int, defaults to 0)
- `last_hit` (datetime, defaults to null)

---

## Related Documentation

- **[WP-CLI Commands](WP_CLI.md)** - Command-line redirect management
- **[Developer Guide](DEVELOPER_GUIDE.md)** - Programmatic redirect creation
- **[Getting Started](GETTING_STARTED.md)** - Basic redirect setup

---

## External Resources

- **[HTTP Status Codes (MDN)](https://developer.mozilla.org/en-US/docs/Web/HTTP/Status)**
- **[Google: Redirects and Search](https://developers.google.com/search/docs/crawling-indexing/301-redirects)**

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
