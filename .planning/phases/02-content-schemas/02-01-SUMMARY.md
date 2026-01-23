---
phase: 02-content-schemas
plan: 01
subsystem: schema
tags: [json-ld, article-schema, person, author, wordcount, structured-data]

# Dependency graph
requires:
  - phase: 01-schema-engine-foundation
    provides: Abstract_Schema base class, Schema_IDs helper, Schema_Context
provides:
  - Article_Schema base class for content types
  - Full author Person object pattern
  - wordCount and articleBody excerpt helpers
  - Publisher reference pattern
affects: [02-02 BlogPosting, 02-03 NewsArticle, future content schemas]

# Tech tracking
tech-stack:
  added: []
  patterns: [full-author-person, word-count-calculation, article-body-excerpt]

key-files:
  created:
    - includes/Schema/Types/class-article-schema.php
  modified: []

key-decisions:
  - "Author is full Person object (not name string) for Google rich results eligibility"
  - "wordCount uses strip_shortcodes + wp_strip_all_tags before counting"
  - "articleBody excerpt is ~150 words using wp_trim_words with do_shortcode processing"
  - "Publisher is @id reference (not inline) to Organization or Person based on knowledge type"
  - "Protected methods allow BlogPosting/NewsArticle subclasses to override behavior"

patterns-established:
  - "Content schema pattern: is_needed checks post + schema_type match"
  - "Author pattern: full Person with @type, @id, name, url, optional image"
  - "Publisher pattern: @id reference to existing graph node"

# Metrics
duration: 2min
completed: 2026-01-23
---

# Phase 02 Plan 01: Article Schema Base Class Summary

**Article_Schema with full author Person object, wordCount calculation, articleBody excerpt, and publisher @id reference for Google rich results eligibility**

## Performance

- **Duration:** 2 min
- **Started:** 2026-01-23T19:00:54Z
- **Completed:** 2026-01-23T19:02:31Z
- **Tasks:** 1
- **Files created:** 1

## Accomplishments

- Created Article_Schema base class extending Abstract_Schema
- Full author Person object with @type, @id, name, url, and optional Gravatar image
- wordCount calculated from stripped post content (no shortcodes, no HTML)
- articleBody excerpt (~150 words) with shortcode processing
- Publisher reference via @id to Organization or Person based on knowledge type

## Task Commits

Each task was committed atomically:

1. **Task 1: Create Article_Schema base class** - `cacd58b` (feat)

## Files Created/Modified

- `includes/Schema/Types/class-article-schema.php` - Article schema with author, publisher, wordCount, articleBody

## Decisions Made

- **Full Person for author:** Google recommends full Person objects (not just name strings) for rich results eligibility
- **Protected helpers:** get_author(), get_publisher_reference(), get_word_count(), get_article_body_excerpt(), get_images() are protected so BlogPosting/NewsArticle can override
- **URL fallback:** Author URL prefers user_url meta, falls back to author posts URL
- **Content processing:** wordCount strips shortcodes before counting; articleBody processes shortcodes for accurate text extraction

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Article_Schema ready for BlogPosting_Schema (02-02) to extend
- Pattern established for all content schema types
- Author Person pattern can be reused across content schemas

---
*Phase: 02-content-schemas*
*Plan: 01*
*Completed: 2026-01-23*
