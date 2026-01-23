---
phase: "05-admin-ui"
plan: "01"
subsystem: "api"
tags: ["rest-api", "schema", "post-meta", "gutenberg"]
depends_on:
  requires: ["01-schema-engine-foundation", "04-localbusiness-schema"]
  provides: ["schema-preview-endpoint", "schema-type-persistence"]
  affects: ["05-02", "05-03"]
tech_stack:
  added: []
  patterns: ["per-post permission check", "schema type override"]
key_files:
  created:
    - "includes/Api/class-schema-preview-controller.php"
  modified:
    - "includes/class-saman-seo-admin-v2.php"
    - "includes/class-saman-seo-service-post-meta.php"
decisions:
  - key: "edit_post_permission"
    choice: "Use edit_post capability (not manage_options)"
    reason: "Editors need access to preview schema for posts they can edit"
metrics:
  duration: "2 min"
  completed: "2026-01-23"
---

# Phase 05 Plan 01: REST API & Post Meta Summary

REST endpoint for schema preview with schema_type persistence in post meta.

## What Was Built

### Schema_Preview_Controller

Created `includes/Api/class-schema-preview-controller.php`:

- **Endpoint:** `POST /saman-seo/v1/schema/preview/{post_id}`
- **Parameters:**
  - `post_id` (URL, required): Post to preview schema for
  - `schema_type` (body, optional): Override schema type for preview
- **Permission:** `edit_post` capability for the specific post
- **Response:** `{ success: true, data: { json_ld: {...}, schema_type: "Article" } }`

Key implementation:

```php
public function get_preview( $request ) {
    $post_id     = absint( $request->get_param( 'post_id' ) );
    $schema_type = $request->get_param( 'schema_type' );

    $post = get_post( $post_id );
    if ( ! $post ) {
        return $this->error( 'Post not found.', 'not_found', 404 );
    }

    $context = Schema_Context::from_post( $post );

    if ( ! empty( $schema_type ) ) {
        $context->schema_type = $schema_type;
    }

    $manager = new Schema_Graph_Manager( Schema_Registry::instance() );
    $graph   = $manager->build( $context );

    return $this->success( [
        'json_ld'     => $graph,
        'schema_type' => $context->schema_type,
    ] );
}
```

### Post_Meta schema_type Field

Updated `includes/class-saman-seo-service-post-meta.php`:

1. Added `schema_type` to REST schema properties
2. Added `schema_type` sanitization in `sanitize()` method
3. Added `schema_type` handling in `save_meta()` for classic editor

This enables:
- Gutenberg to read/write schema_type via REST API
- Classic editor to persist schema_type via form POST
- Schema_Context to respect per-post schema_type override

## Commits

| Hash | Description |
|------|-------------|
| 60243c7 | feat(05-01): create Schema_Preview_Controller |
| 863a76e | feat(05-01): add schema_type to Post_Meta |

## Deviations from Plan

None - plan executed exactly as written.

## Must-Have Verification

| Truth | Status |
|-------|--------|
| REST endpoint returns JSON-LD preview for a given post ID | Verified |
| schema_type is persisted when user saves a post via Gutenberg | Verified |
| Schema_Context respects schema_type override from request parameter | Verified |

## Next Phase Readiness

Plan 05-02 (Editor Sidebar UI) can now:
- Fetch live JSON-LD preview via `POST /saman-seo/v1/schema/preview/{post_id}`
- Pass `schema_type` parameter for instant preview of type changes
- Save schema_type to post meta via standard Gutenberg REST API
- Read schema_type from `_SAMAN_SEO_meta.schema_type`

## Files Changed

- `includes/Api/class-schema-preview-controller.php` (created)
- `includes/class-saman-seo-admin-v2.php` (modified - controller registration)
- `includes/class-saman-seo-service-post-meta.php` (modified - schema_type field)
