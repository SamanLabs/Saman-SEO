# Internal Linking Guide

Complete guide to automating and managing internal links with Saman SEO.

---

## Table of Contents

- [Overview](#overview)
- [How It Works](#how-it-works)
- [Setup & Configuration](#setup--configuration)
- [Creating Link Rules](#creating-link-rules)
- [Link Categories](#link-categories)
- [UTM Templates](#utm-templates)
- [Settings & Options](#settings--options)
- [Best Practices](#best-practices)
- [Examples](#examples)

---

## Overview

Saman SEO's Internal Linking Engine automatically inserts contextual internal links into your content based on customizable rules.

**Benefits:**
- Improved site architecture
- Better crawlability
- Enhanced user navigation
- Increased page views
- Automated link building

**Location:** Navigate to **Saman SEO → Internal Linking**

**Files:**
- `includes/class-wpseopilot-service-internal-linking.php`
- `includes/class-wpseopilot-internal-linking-engine.php`
- `includes/class-wpseopilot-internal-linking-repository.php`

---

## How It Works

The Internal Linking Engine:

1. **Scans** your content when displayed on the frontend
2. **Matches** keywords defined in your link rules
3. **Inserts** links to specified target URLs
4. **Respects** limits (max links per post, first match only, etc.)

**Process:**

```
Post Content → Scan for Keywords → Match Rules → Insert Links → Display
```

**Important:** Links are inserted dynamically on page load, not stored in the database.

---

## Setup & Configuration

### Step 1: Enable Internal Linking

Navigate to **Saman SEO → Internal Linking**

The feature is enabled by default once the plugin is activated.

---

### Step 2: Configure Global Settings

**Settings Tab** allows you to control:

- Maximum links per post
- Link insertion behavior
- Nofollow/sponsored attributes
- Case sensitivity
- Link opening behavior (same tab/new tab)

---

### Step 3: Create Your First Link Rule

Click **Add New Rule** to create a keyword → URL mapping.

---

## Creating Link Rules

### Basic Rule Structure

| Field | Description | Example |
|-------|-------------|---------|
| **Keyword** | Word or phrase to match | "WordPress SEO" |
| **Target URL** | Where the link should point | `/wordpress-seo-guide/` |
| **Title** | Link title attribute (optional) | "Complete WordPress SEO Guide" |
| **Anchor Text** | Override keyword with custom text | "SEO guide for WordPress" |
| **Status** | Active or Inactive | Active |

---

### Adding a Link Rule

1. Navigate to **Saman SEO → Internal Linking → Rules**
2. Click **Add New Rule**
3. Fill in the form:
   - **Keyword**: Enter the word/phrase to match
   - **Target URL**: Enter the destination URL
   - **Anchor Text** (optional): Override displayed text
   - **Title** (optional): Link title attribute
   - **Category** (optional): Assign to category for organization
4. Click **Save Rule**

---

### Rule Options

#### Max Links Per Post

Limit how many times this keyword gets linked in a single post.

**Default:** 1 (first occurrence only)

**Example:**
- Keyword: "WordPress"
- Max Links: 1
- Result: Only the first occurrence of "WordPress" gets linked

---

#### Case Sensitive

Whether keyword matching should be case-sensitive.

**Default:** No (case-insensitive)

**Example:**
- Keyword: "SEO"
- Case Sensitive: No
- Matches: "SEO", "seo", "Seo"

---

#### Whole Word Only

Match only complete words, not partial matches.

**Default:** Yes

**Example:**
- Keyword: "link"
- Whole Word: Yes
- Matches: "link" ✓
- Doesn't Match: "linking" ✗, "hyperlink" ✗

---

#### Skip If Already Linked

Skip this keyword if it's already part of another link.

**Default:** Yes

**Example:**
- Content: `<a href="/page">WordPress SEO guide</a>`
- Keyword: "SEO"
- Skip If Linked: Yes
- Result: "SEO" not linked because it's already in a link

---

#### Priority

Higher priority rules are processed first.

**Default:** 10

**Example:**
- Rule A: Keyword "WordPress SEO", Priority 20
- Rule B: Keyword "SEO", Priority 10
- "WordPress SEO" matched first, "SEO" won't match within it

---

### Advanced Rule Options

#### Limit to Post Types

Restrict this rule to specific post types.

**Options:**
- All post types (default)
- Posts only
- Pages only
- Custom post types

**Example:**
- Keyword: "Product catalog"
- Limit to: Products
- Result: Only links in product posts, not blog posts

---

#### Limit to Categories

Only apply this rule to posts in specific categories.

**Example:**
- Keyword: "Hosting guide"
- Categories: Web Hosting, Tutorials
- Result: Only posts in these categories get the link

---

#### Exclude Post IDs

Exclude specific posts from this rule.

**Example:**
- Keyword: "Premium features"
- Exclude: 42, 100, 250
- Result: Posts with these IDs won't get the link

---

## Link Categories

Organize your link rules into categories for easier management.

### Creating Categories

1. Navigate to **Internal Linking → Categories**
2. Click **Add Category**
3. Enter:
   - **Name**: Category name (e.g., "Product Links")
   - **Description**: Optional description
4. Click **Save**

---

### Assigning Rules to Categories

When creating or editing a rule, select a category from the dropdown.

---

### Category Benefits

- **Organization**: Group related rules
- **Bulk Management**: Enable/disable entire categories
- **Reporting**: Track performance by category

---

## UTM Templates

Create reusable UTM parameter templates for tracking internal link performance.

### Creating UTM Templates

1. Navigate to **Internal Linking → UTM Templates**
2. Click **Add Template**
3. Fill in:
   - **Name**: Template identifier
   - **utm_source**: Traffic source
   - **utm_medium**: Marketing medium
   - **utm_campaign**: Campaign name
   - **utm_content**: Content identifier
4. Click **Save**

---

### Example UTM Template

**Name:** Internal Blog Links

**Parameters:**
- utm_source: `blog`
- utm_medium: `internal_link`
- utm_campaign: `content_discovery`
- utm_content: `{{keyword}}`

**Result:**
```
/target-page/?utm_source=blog&utm_medium=internal_link&utm_campaign=content_discovery&utm_content=WordPress+SEO
```

---

### Applying UTM Templates to Rules

When creating a link rule, select a UTM template from the dropdown.

The template parameters will be automatically appended to the target URL.

---

### Variable Substitution

Use these variables in UTM parameters:

- `{{keyword}}` - Matched keyword
- `{{post_id}}` - Current post ID
- `{{post_title}}` - Current post title

---

## Settings & Options

Navigate to **Internal Linking → Settings**

### Global Settings

| Setting | Description | Default |
|---------|-------------|---------|
| **Enable Internal Linking** | Master toggle | Enabled |
| **Max Links Per Post** | Maximum total links inserted per post | 10 |
| **Link in Excerpts** | Apply rules to post excerpts | No |
| **Link in Widgets** | Apply rules to widget content | No |
| **Link in Comments** | Apply rules to comments | No |
| **Target Attribute** | `_blank` (new tab) or `_self` (same tab) | _self |
| **Add Nofollow** | Add rel="nofollow" to all inserted links | No |
| **Add Sponsored** | Add rel="sponsored" to all inserted links | No |

---

### Content Filtering

| Setting | Description | Default |
|---------|-------------|---------|
| **Skip Headings** | Don't link keywords in H1-H6 tags | Yes |
| **Skip Quotes** | Don't link keywords in blockquotes | No |
| **Skip Code Blocks** | Don't link keywords in `<code>` or `<pre>` | Yes |

---

### Performance

| Setting | Description | Default |
|---------|-------------|---------|
| **Cache Results** | Cache processed content for performance | Yes |
| **Cache Duration** | How long to cache (in seconds) | 3600 |

---

## Best Practices

### 1. Start with High-Value Pages

Create rules linking to:
- Cornerstone content
- High-converting pages
- Pillar posts

**Example:**
- Keyword: "WordPress hosting guide"
- Target: `/ultimate-wordpress-hosting-guide/`

---

### 2. Use Specific Keywords

**Bad:**
- Keyword: "click here"

**Good:**
- Keyword: "WordPress SEO best practices"

---

### 3. Limit Links Per Post

**Recommended:** 5-10 automatic links per post

Too many links:
- Dilutes link value
- Looks spammy
- Confuses users

---

### 4. Prioritize Rules Properly

Higher priority for:
- Longer, more specific keywords
- High-value pages
- Branded terms

**Example:**
- Priority 20: "Saman SEO tutorial"
- Priority 15: "WordPress SEO plugin"
- Priority 10: "SEO plugin"

---

### 5. Use Whole Word Matching

Avoid partial matches that create awkward links.

**Example with Whole Word OFF:**
- Keyword: "link"
- Content: "The thinking behind this..."
- Result: "The thin**king** behind this..." ✗

---

### 6. Review Automatically

Set up regular reviews:
- Monthly: Check link relevance
- Quarterly: Update target URLs
- Annually: Audit all rules

---

### 7. Combine with Manual Links

Internal linking engine should complement, not replace, manual editorial links.

**Best Approach:**
- Manual: Primary contextual links
- Automated: Supporting keyword links

---

### 8. Monitor Performance

Track with UTM parameters:
- Which keywords drive clicks
- Which target pages get traffic
- Conversion from internal links

---

## Examples

### Example 1: Product Cross-Linking

**Goal:** Link from blog posts to product pages

**Rules:**

| Keyword | Target URL | Category |
|---------|-----------|----------|
| "premium WordPress themes" | `/shop/themes/` | Products |
| "WordPress hosting" | `/hosting/` | Products |
| "SEO tools" | `/tools/seo/` | Products |

**Settings:**
- Max Links Per Post: 3
- UTM Template: "Product Links"
- Target: _self

---

### Example 2: Tutorial Interlinking

**Goal:** Connect related tutorials

**Rules:**

| Keyword | Target URL | Priority |
|---------|-----------|----------|
| "install WordPress" | `/tutorials/install-wordpress/` | 15 |
| "WordPress settings" | `/tutorials/configure-wordpress/` | 10 |
| "WordPress themes" | `/tutorials/choose-theme/` | 10 |

**Settings:**
- Limit to Post Type: Posts
- Limit to Category: Tutorials
- Max Links Per Post: 5

---

### Example 3: Glossary Linking

**Goal:** Link technical terms to glossary

**Rules:**

| Keyword | Target URL | Whole Word |
|---------|-----------|------------|
| "SEO" | `/glossary/seo/` | Yes |
| "meta description" | `/glossary/meta-description/` | Yes |
| "canonical URL" | `/glossary/canonical-url/` | Yes |

**Settings:**
- Max Links: 1 per keyword
- Title: "Learn more about [keyword]"
- Add to all post types

---

### Example 4: Campaign Tracking

**Goal:** Track internal blog traffic

**UTM Template:**

- Name: "Blog Discovery"
- utm_source: `blog`
- utm_medium: `internal`
- utm_campaign: `{{post_id}}`
- utm_content: `{{keyword}}`

**Rule:**

- Keyword: "WordPress performance"
- Target: `/optimize-wordpress-performance/`
- UTM Template: Blog Discovery

**Result Link:**
```
/optimize-wordpress-performance/?utm_source=blog&utm_medium=internal&utm_campaign=123&utm_content=WordPress+performance
```

---

### Example 5: Category-Specific Linking

**Goal:** Link recipes to meal type pages

**Rules:**

| Keyword | Target | Limit to Category |
|---------|--------|-------------------|
| "breakfast recipes" | `/breakfast/` | Recipes |
| "dinner ideas" | `/dinner/` | Recipes |
| "desserts" | `/desserts/` | Recipes |

---

## Programmatic Management

### Get All Rules

```php
$linking_service = \WPSEOPilot\Plugin::get_instance()->get_service( 'internal_linking' );
$repository = $linking_service->get_repository();

$rules = $repository->get_rules();
```

---

### Create Rule Programmatically

```php
$rule = [
    'keyword' => 'WordPress SEO',
    'target_url' => '/wordpress-seo-guide/',
    'anchor_text' => 'Complete SEO Guide',
    'title' => 'Learn WordPress SEO',
    'max_links' => 1,
    'case_sensitive' => false,
    'whole_word' => true,
    'priority' => 10,
    'status' => 'active'
];

$repository->save_rule( $rule );
```

---

### Delete Rule

```php
$repository->delete_rule( $rule_id );
```

---

### Bulk Update Rules

```php
$updates = [
    [ 'id' => 1, 'status' => 'inactive' ],
    [ 'id' => 2, 'priority' => 20 ],
    [ 'id' => 3, 'max_links' => 2 ]
];

$repository->bulk_update_rules( $updates );
```

---

## Filters & Customization

### Filter Link Suggestions

```php
add_filter( 'wpseopilot_link_suggestions', function( $suggestions, $post_id ) {
    // Add custom suggestion
    $suggestions[] = [
        'url' => '/custom-page/',
        'title' => 'Custom Suggestion',
        'excerpt' => 'This is a custom link suggestion',
        'keyword' => 'custom keyword'
    ];

    return $suggestions;
}, 10, 2 );
```

---

### Filter User Roles

```php
add_filter( 'wpseopilot_internal_link_roles', function( $roles ) {
    $roles[] = 'editor';
    $roles[] = 'author';

    return $roles;
});
```

---

## Troubleshooting

### Links Not Appearing

**Check:**

1. Internal linking is enabled
2. Rules are set to "Active"
3. Content contains the exact keyword
4. Max links per post not exceeded
5. Post type/category matches rule filters

**Debug:**

```php
add_filter( 'the_content', function( $content ) {
    error_log( 'Content before linking: ' . substr( $content, 0, 500 ) );
    return $content;
}, 5 ); // Run before internal linking filter
```

---

### Too Many Links

**Solution:**

1. Reduce "Max Links Per Post" in settings
2. Set lower "Max Links" on individual rules
3. Use more specific keywords
4. Increase rule priority for important links

---

### Wrong Links Inserted

**Cause:** Lower priority rule matching first

**Solution:**

Adjust priorities:
- Specific keywords: Higher priority (20+)
- Generic keywords: Lower priority (5-10)

---

## Performance Considerations

### Caching

Enable caching in settings to avoid re-processing content on every page load.

---

### Exclude Heavy Pages

For very long posts (10,000+ words), consider excluding from automatic linking:

```php
add_filter( 'wpseopilot_sitemap_post_query_args', function( $args, $post_type ) {
    // Exclude posts over 5000 words
    $args['meta_query'][] = [
        'key' => '_word_count',
        'value' => 5000,
        'compare' => '<'
    ];

    return $args;
}, 10, 2 );
```

---

## Related Documentation

- **[Developer Guide](DEVELOPER_GUIDE.md)** - Internal linking filters and hooks
- **[Filter Reference](FILTERS.md#internal-linking-filters)** - Complete filter docs
- **[Getting Started](GETTING_STARTED.md)** - Basic internal linking setup

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
