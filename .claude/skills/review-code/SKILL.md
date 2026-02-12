---
name: review-code
description: Review code changes in the Saman SEO plugin for quality, security, and adherence to WordPress coding standards. Use when reviewing PRs, checking code quality, or before committing changes.
context: fork
agent: Explore
---

# Code Review for Saman SEO

Review the specified code or recent changes for quality, security, and standards compliance.

## Review Checklist

### 1. WordPress Coding Standards (WPCS)

Check PHP code for:
- [ ] Proper indentation (tabs, not spaces)
- [ ] Yoda conditions (`if ( 'value' === $var )`)
- [ ] Space inside parentheses (`function( $param )`)
- [ ] Proper docblocks for functions and classes
- [ ] No trailing whitespace
- [ ] File header comments with `@package`

### 2. Security Review

Check for vulnerabilities:
- [ ] **Input Sanitization**: All `$_GET`, `$_POST`, `$_REQUEST` sanitized
  - `sanitize_text_field()`, `absint()`, `sanitize_email()`, etc.
  - `wp_unslash()` before sanitization
- [ ] **Output Escaping**: All output escaped
  - `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses_post()`
- [ ] **Nonce Verification**: Forms and AJAX have nonce checks
  - `wp_verify_nonce()` or `check_ajax_referer()`
- [ ] **Capability Checks**: `current_user_can()` for privileged operations
- [ ] **SQL Safety**: Use `$wpdb->prepare()` for queries
- [ ] **File Operations**: No direct file inclusion from user input

### 3. Saman SEO Patterns

Check adherence to project patterns:
- [ ] **Namespace**: Uses `Saman\SEO` namespace hierarchy
- [ ] **Service Pattern**: New features use service classes with `boot()` method
- [ ] **Filter Hooks**: Extensibility points use `SAMAN_SEO_` prefix
- [ ] **Options**: Use `SAMAN_SEO_` prefix for options
- [ ] **Meta Keys**: Use `_SAMAN_SEO_` prefix for post meta
- [ ] **Text Domain**: All strings use `'saman-seo'` text domain
- [ ] **Helper Usage**: Uses `\Saman\SEO\Helpers\` functions where appropriate
- [ ] **Routing**: Services must NOT call `add_submenu_page()` or `add_menu_page()` — only `Admin_V2` registers pages. Check for duplicate slug registrations that would break the React SPA.

### 4. JavaScript/React Standards

For `src-v2/` files:
- [ ] Uses `@wordpress/element` for React
- [ ] Uses `@wordpress/i18n` for translations (`__()`, `_n()`)
- [ ] Uses `@wordpress/api-fetch` for API calls
- [ ] Proper component structure with hooks
- [ ] No console.log in production code
- [ ] Error handling for async operations

### 5. Performance

- [ ] No unnecessary database queries in loops
- [ ] Proper use of transients/caching for expensive operations
- [ ] Lazy loading where appropriate
- [ ] No blocking operations on frontend

### 6. Documentation

- [ ] Functions have proper PHPDoc
- [ ] Complex logic has inline comments
- [ ] New filters documented in `FILTERS.md`
- [ ] New CLI commands documented in `WP_CLI.md`
- [ ] Public functions documented in `TEMPLATE_TAGS.md`

## Review Process

1. **Read the changes** - Understand what was modified
2. **Check each file** against the checklist above
3. **Test mentally** - Consider edge cases
4. **Security scan** - Look for common vulnerabilities
5. **Summarize findings** with specific line references

## Common Issues to Flag

### Security Issues (Critical)
```php
// BAD: Unsanitized input
$title = $_POST['title'];

// GOOD: Sanitized input
$title = isset( $_POST['title'] ) ? sanitize_text_field( wp_unslash( $_POST['title'] ) ) : '';
```

### Escaping Issues
```php
// BAD: Unescaped output
echo $user_data;

// GOOD: Escaped output
echo esc_html( $user_data );
```

### SQL Injection Risk
```php
// BAD: Direct variable in query
$wpdb->query( "SELECT * FROM table WHERE id = $id" );

// GOOD: Prepared statement
$wpdb->query( $wpdb->prepare( "SELECT * FROM table WHERE id = %d", $id ) );
```

### Missing Capability Check
```php
// BAD: No permission check
function delete_item( $id ) {
    // Delete directly
}

// GOOD: With permission check
function delete_item( $id ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'forbidden', 'Access denied' );
    }
    // Delete
}
```

## Output Format

Provide review in this format:

```markdown
## Code Review Summary

**Files Reviewed:** [list files]
**Overall Assessment:** [PASS/NEEDS CHANGES/CRITICAL ISSUES]

### Issues Found

#### Critical (Must Fix)
- [file:line] Description of security/critical issue

#### Important (Should Fix)
- [file:line] Description of standards violation

#### Minor (Nice to Have)
- [file:line] Description of style/preference issue

### Positive Notes
- What was done well

### Recommendations
- Suggested improvements
```

## Arguments
$ARGUMENTS - File paths or "recent" to review git changes
