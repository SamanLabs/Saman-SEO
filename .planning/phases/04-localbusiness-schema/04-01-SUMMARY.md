---
phase: 04-localbusiness-schema
plan: 01
subsystem: schema-types
tags: [localbusiness, schema.org, json-ld, local-seo]

# Dependency graph
requires: [01-01, 01-02]
provides: [LocalBusiness_Schema class, Schema_IDs::localbusiness()]
affects: [04-02]

# Tech tracking
tech-stack:
  added: []
  patterns: [options-based-data-source, nested-schema-types, day-grouping]

# File tracking
key-files:
  created:
    - includes/Schema/Types/class-localbusiness-schema.php
  modified:
    - includes/Schema/class-schema-ids.php

# Decisions
decisions:
  - id: localbusiness-homepage-only
    choice: "Output LocalBusiness schema only on homepage/front_page"
    reason: "LocalBusiness represents business entity, not a page - matches Organization pattern"
  - id: address-requires-street-city
    choice: "Require both street and city for PostalAddress output"
    reason: "Google requires minimum address fields for rich results eligibility"
  - id: opening-hours-grouping
    choice: "Group days with identical hours into single OpeningHoursSpecification"
    reason: "Produces cleaner, more compact JSON-LD output"

# Metrics
metrics:
  duration: 2 min
  completed: 2026-01-23
---

# Phase 04 Plan 01: LocalBusiness Schema Class Summary

LocalBusiness_Schema class reads from existing SAMAN_SEO_local_* options with full support for PostalAddress, GeoCoordinates, and OpeningHoursSpecification nested types.

## What Was Built

### Task 1: Schema_IDs::localbusiness() method
Added static method to Schema_IDs class for generating consistent LocalBusiness @id values.

```php
public static function localbusiness(): string {
    return home_url( '/' ) . '#localbusiness';
}
```

Uses site root URL (like Organization) because LocalBusiness represents the business entity, not a specific page.

### Task 2: LocalBusiness_Schema class
Created complete LocalBusiness schema class with:

**Core methods:**
- `get_type()` - Returns business type from `SAMAN_SEO_local_business_type` option (supports Restaurant, Dentist, Store, etc.)
- `is_needed()` - True only on homepage when business name is configured
- `generate()` - Builds full LocalBusiness schema array

**Private helper methods (all pass schema by reference):**
- `add_logo()` - ImageObject nested type from SAMAN_SEO_local_logo
- `add_image()` - Direct image URL from SAMAN_SEO_local_image
- `add_description()` - Business description
- `add_contact()` - telephone and email properties
- `add_price_range()` - priceRange property (e.g., "$$")
- `add_address()` - PostalAddress nested type (requires street + city)
- `add_geo()` - GeoCoordinates nested type (requires both lat + lng)
- `add_opening_hours()` - OpeningHoursSpecification array with day grouping
- `add_social_profiles()` - sameAs array of social URLs

**Key implementation details:**
- No @context in output (Graph_Manager handles at root level)
- Float casting for geo coordinates
- Day grouping for opening hours (days with same hours combined)
- Schema only outputs when business name is configured

## Files Changed

| File | Change | Lines |
|------|--------|-------|
| `includes/Schema/class-schema-ids.php` | Added localbusiness() method | +9 |
| `includes/Schema/Types/class-localbusiness-schema.php` | New file | +325 |

## Decisions Made

1. **Homepage-only output** - LocalBusiness schema outputs only on `is_front_page() || is_home()` to match the Organization schema pattern. The business entity is site-wide, not page-specific.

2. **Address requires street + city** - PostalAddress nested type only outputs when both street and city are present. This ensures minimum data for Google rich results eligibility.

3. **Opening hours day grouping** - Days with identical open/close times are grouped into single OpeningHoursSpecification objects. For example, if Mon-Fri all open 9:00-17:00, one specification with dayOfWeek array is output instead of five separate ones.

## Verification Results

All checks passed:
- Schema_IDs::localbusiness() returns `home_url('/') . '#localbusiness'`
- LocalBusiness_Schema extends Abstract_Schema
- All three abstract methods implemented (get_type, is_needed, generate)
- No @context anywhere in LocalBusiness_Schema
- Uses Schema_IDs::localbusiness() for @id generation
- Reads from 19 different SAMAN_SEO_local_* options

## Deviations from Plan

None - plan executed exactly as written.

## Next Phase Readiness

Ready for 04-02 (Registration & Conflict Resolution):
- LocalBusiness_Schema class is complete and follows Abstract_Schema contract
- Schema_IDs::localbusiness() provides consistent @id generation
- Class needs to be registered in Schema_Registry with appropriate priority
- Organization/LocalBusiness relationship needs handling (both can output for same business)
