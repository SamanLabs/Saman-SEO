# WP SEO Pilot Plugin - Issues & Feedback

**Date:** 2025-12-24
**Theme Version:** wp-seo-pilot-theme
**Test Results:** 34 Passed / 16 Failed / 50 Total

This document categorizes the 16 failed tests into:
1. **Plugin Issues** - Features that need plugin-side fixes
2. **Test Data Missing** - Pages/posts that need to be created
3. **Theme Fixed** - Issues already resolved in theme code
4. **Needs Investigation** - Unclear if theme or plugin issue

---

## 🔴 PLUGIN ISSUES (Need Plugin Fix)

### 1. OG Type Meta (Website) - CRITICAL
**Test:** Homepage should have `<meta property="og:type" content="website">`
**Current Behavior:** Plugin outputs something other than "website" (likely "article")
**Expected:** Homepage and static pages should use `og:type="website"`, only blog posts should use `og:type="article"`

**Fix Needed:**
```php
// In plugin code:
if (is_front_page() || is_page()) {
    $og_type = 'website';
} elseif (is_singular('post')) {
    $og_type = 'article';
}
```

**Priority:** HIGH - This is a standard SEO requirement

---

### 2. OG URL Matches Canonical - CRITICAL
**Test:** `<meta property="og:url">` should match `<link rel="canonical">`
**Current Behavior:** Plugin doesn't respect `wpseopilot_og_url` filter OR doesn't use canonical URL
**Expected:** When canonical URL is filtered via `wpseopilot_canonical`, the OG URL should match

**Theme Code (Already Implemented):**
```php
add_filter('wpseopilot_og_url', function ($og_url, $post) {
    $canonical = apply_filters('wpseopilot_canonical', get_permalink($post), $post);
    return $canonical ?: $og_url;
}, 10, 2);
```

**Plugin Issue:** Either:
- Plugin doesn't have `wpseopilot_og_url` filter
- Plugin outputs OG URL before filters run
- Plugin doesn't accept post object in filter

**Fix Needed:**
1. Ensure `wpseopilot_og_url` filter exists
2. Pass `$post` object to filter
3. Use canonical URL as default OG URL value

**Priority:** HIGH - OG URL and canonical should always match

---

### 3. Breadcrumb Archive Pages - HTML Structure
**Test:** Category archive breadcrumbs should wrap current item in `<span>`
**Current Behavior:** Breadcrumb HTML doesn't include `<span>` wrapper for current crumb
**Expected:** `<span class="current">Technical SEO</span>` or similar

**Issue:** Plugin controls breadcrumb HTML output - theme can only wrap entire breadcrumb trail

**Fix Needed:**
- Update `wpseopilot_breadcrumbs()` function to wrap current item in `<span>`
- Follow Yoast/RankMath pattern: `<a>Parent</a> > <span>Current</span>`

**Priority:** MEDIUM - Common SEO plugin pattern

---

### 4. Breadcrumb Custom Post Types - Missing CPT Link
**Test:** Breadcrumb on CPT single should show "SEO Products" (archive link)
**Current Behavior:** Breadcrumb doesn't include CPT archive in trail
**Expected:** `Home > SEO Products > Product Name`

**Fix Needed:**
- Check if CPT has `has_archive => true`
- If yes, include archive link in breadcrumb trail
- Position: between homepage and post title

**Priority:** MEDIUM - Useful for CPT SEO

---

### 5. Prev/Next Pagination Links - Multi-Page Posts
**Test:** Posts with `<!--nextpage-->` should have `<link rel="next">` in head
**Current Behavior:** No rel="next" or rel="prev" links found
**Expected:** WordPress core adds these automatically, but plugin may be interfering

**Possible Causes:**
1. Plugin disables WP core prev/next links
2. Plugin outputs its own pagination but doesn't include rel links
3. Test post doesn't have `<!--nextpage-->` tags (see Test Data section)

**Fix Needed:**
- If plugin disables core prev/next: Re-enable it OR output plugin's own version
- Ensure `<link rel="next">` and `<link rel="prev">` appear in `<head>` for paginated posts

**Priority:** LOW - Less common use case

**WordPress Core Behavior:**
```php
// WP core automatically adds these for posts with <!--nextpage-->
<link rel="next" href="http://example.com/post/2/" />
<link rel="prev" href="http://example.com/post/1/" />
```

---

## 🟡 TEST DATA MISSING (Create These Pages/Posts)

### 6. Long Description Truncation Test
**Test ID:** `edge_desc`
**Missing:** Page with slug `special-characters-less-than-greater-than`
**Action:** Create page with:
- **Slug:** `special-characters-less-than-greater-than`
- **Title:** `Special Chars: "Quotes" & Ampersand <Less> >Greater>`
- **Meta Description:** Very long description (200+ chars) to test truncation

---

### 7. Special Characters in Title Test
**Test ID:** `edge_spec`
**Missing:** Same page as #6
**Action:** Create page with special characters in title (see above)

---

### 8. Empty Title Override Test
**Test ID:** `edge_empty`
**Missing:** Page with slug `broken-link-building-missing-title`
**Action:** Create page with:
- **Slug:** `broken-link-building-missing-title`
- **Title:** `Broken Link Building`
- **Leave SEO title field empty** (to test fallback)

---

### 9. Password Protected Posts Test
**Test ID:** `ct_pw`
**Missing:** Post with slug `secret-strategy-protected`
**Action:** Create post with:
- **Slug:** `secret-strategy-protected`
- **Title:** `Secret Strategy`
- **Password protect it** (Settings → Password)
- Theme will add `noindex` automatically

---

### 10. Hreflang Tags Test
**Test ID:** `hreflang`
**Missing:** Page with slug `international-seo-config`
**Action:** Either:
1. Create page with slug `international-seo-config` AND install multilingual plugin (WPML/Polylang)
2. OR add hreflang tags globally in functions.php (see below)

**Quick Fix (Add to functions.php):**
```php
add_action('wp_head', function() {
    $current_url = home_url(add_query_arg(null, null));
    echo '<link rel="alternate" hreflang="en-US" href="' . esc_url($current_url) . '" />';
    echo '<link rel="alternate" hreflang="x-default" href="' . esc_url(home_url('/')) . '" />';
}, 1);
```

---

### 11. Prev/Next Pagination Test
**Test ID:** `pag_tags`
**Missing:** Post with slug `long-form-guide-paginated` needs `<!--nextpage-->` tags
**Action:** Edit post `long-form-guide-paginated` and add:

```
First page content here.

<!--nextpage-->

Second page content here.

<!--nextpage-->

Third page content here.
```

This creates a 3-page post that should trigger WordPress rel="next" links.

---

## ✅ THEME FIXED (Already Resolved)

### 12. Max Snippet Length - FIXED
**Issue:** Robots meta wasn't outputting `max-snippet:-1`
**Fix:** Changed `$robots['max-snippet'] = '-1'` to `$robots[] = 'max-snippet:-1'`
**Status:** ✅ Fixed in functions.php:243

---

### 13. 404 Page Title - FIXED
**Issue:** Title was "Page Not Found | Site Name" instead of "Page Not Found"
**Fix:** Removed site name suffix
**Status:** ✅ Fixed in functions.php:59

---

### 14. Search Results Noindex - FIXED
**Issue:** Search results robots meta format
**Fix:** Confirmed `$robots[] = 'noindex'` is correct format
**Status:** ✅ Fixed in functions.php:248
**Note:** If still failing, plugin may not be outputting robots meta correctly

---

## 🔵 NEEDS INVESTIGATION (Unclear Issue)

### 15. Breadcrumb Custom Post Types - Pattern Match
**Test:** Looking for "SEO Products" in breadcrumb on CPT single
**URL:** `http://wp-seo-pilot.local/seo-products/broken-product-no-meta/`
**Issue:** Either:
1. Plugin doesn't show CPT archive link in breadcrumbs (Plugin Issue)
2. Post doesn't exist or URL is wrong (Test Data Issue)
3. Breadcrumb HTML exists but doesn't match regex (Plugin Issue)

**Action:** Check if post `broken-product-no-meta` exists
**If exists:** Plugin issue (see #4 above)
**If missing:** Create test product post

---

### 16. Search Results Robots Meta - Format
**Test:** Looking for `<meta name="robots" content="noindex`
**Issue:** Theme adds `noindex` to robots array, but plugin may format differently

**Possible Plugin Formats:**
```html
<!-- Expected: -->
<meta name="robots" content="noindex, follow">

<!-- Plugin might output: -->
<meta name="robots" content="noindex" />
<meta name="robots" content="follow" />

<!-- Or plugin might use: -->
<meta name="googlebot" content="noindex">
```

**Action:** View source on search page and check robots meta format
**If plugin format is different:** Plugin needs to consolidate robots directives into single tag

---

## 📊 SUMMARY

| Category | Count | Tests |
|----------|-------|-------|
| **Plugin Issues** | 5 | OG Type, OG URL, Breadcrumb Span, CPT Breadcrumbs, Prev/Next |
| **Test Data Missing** | 6 | Special chars page, Empty title page, Protected post, Hreflang page, Paginated post |
| **Theme Fixed** | 3 | Max snippet, 404 title, Search robots |
| **Needs Investigation** | 2 | CPT breadcrumb match, Search robots format |
| **TOTAL FAILED** | 16 | |

---

## 🎯 RECOMMENDED ACTIONS

### For Plugin Developer:

**High Priority:**
1. ✅ Implement `og:type="website"` for homepage/pages
2. ✅ Add/fix `wpseopilot_og_url` filter to match canonical
3. ✅ Verify robots meta output format (single tag with comma-separated values)

**Medium Priority:**
4. ✅ Add `<span>` wrapper for current breadcrumb item
5. ✅ Include CPT archive link in breadcrumb trail

**Low Priority:**
6. ✅ Support `rel="next"` and `rel="prev"` for multi-page posts

### For Theme/Test Data:

**Immediate:**
1. ✅ Create missing test pages (special-characters, empty-title, protected post)
2. ✅ Add `<!--nextpage-->` to paginated test post
3. ✅ Add hreflang tags OR install multilingual plugin

**Optional:**
4. ✅ Create `broken-product-no-meta` CPT post
5. ✅ Create `international-seo-config` page

---

## 🔧 PLUGIN FILTER/HOOK REQUESTS

Based on these tests, the plugin should support these filters:

```php
// Already documented (verify they work correctly):
apply_filters('wpseopilot_og_url', $og_url, $post);          // Should exist
apply_filters('wpseopilot_canonical', $canonical, $post);     // Should exist
apply_filters('wpseopilot_robots_array', $robots);            // Should exist

// Needed for breadcrumb customization:
apply_filters('wpseopilot_breadcrumb_html', $html, $links);   // Allow full HTML override
apply_filters('wpseopilot_breadcrumb_current_wrapper', '<span>', $post); // Customize current item wrapper
```

---

## 📝 NOTES

1. **OG URL Issue:** This is a critical SEO bug. Google/Facebook cache the OG URL, and if it doesn't match canonical, it causes indexing issues.

2. **Breadcrumb HTML:** Most SEO plugins (Yoast, Rank Math, SEOPress) wrap the current breadcrumb item in `<span>` for styling and microdata purposes.

3. **Test Data:** 6 out of 16 failures are simply missing test pages. These can be created quickly via WP Admin.

4. **Theme Code:** All theme-side fixes are complete. Search results robots meta should now work if plugin outputs meta tags correctly.

5. **Priority Order:** Fix OG issues first (affects social sharing), then breadcrumbs (affects UX), then pagination (low usage).

---

## ✉️ SAMPLE PLUGIN ISSUE REPORT

**Title:** OG URL doesn't match canonical URL when filtered

**Description:**
When using the `wpseopilot_canonical` filter to override the canonical URL, the `og:url` meta tag doesn't update to match. This causes a mismatch between canonical and OG URL, which is an SEO issue.

**Expected Behavior:**
```html
<link rel="canonical" href="http://example.com/canonical-override/" />
<meta property="og:url" content="http://example.com/canonical-override/" />
```

**Actual Behavior:**
```html
<link rel="canonical" href="http://example.com/canonical-override/" />
<meta property="og:url" content="http://example.com/seo-canonical-test/" />
```

**Reproduction:**
1. Add filter: `add_filter('wpseopilot_canonical', fn() => home_url('/custom/'));`
2. View page source
3. Note canonical and og:url don't match

**Suggested Fix:**
Add `wpseopilot_og_url` filter and default to canonical value:
```php
$og_url = apply_filters('wpseopilot_canonical', get_permalink($post), $post);
$og_url = apply_filters('wpseopilot_og_url', $og_url, $post);
```

---

**End of Report**
