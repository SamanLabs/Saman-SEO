---
phase: 04-localbusiness-schema
verified: 2026-01-23T20:29:44Z
status: passed
score: 4/4 must-haves verified
---

# Phase 4: LocalBusiness Schema Verification Report

**Phase Goal:** LocalBusiness schema pulls from existing Local SEO settings automatically
**Verified:** 2026-01-23T20:29:44Z
**Status:** PASSED
**Re-verification:** No - initial verification

## Goal Achievement

### Observable Truths

| # | Truth | Status | Evidence |
|---|-------|--------|----------|
| 1 | LocalBusiness schema renders with all standard properties (name, address, phone, etc.) | VERIFIED | LocalBusiness_Schema::generate() includes name, url, logo, image, description, telephone, email, priceRange, address (PostalAddress), geo (GeoCoordinates), openingHoursSpecification, and sameAs properties |
| 2 | Business hours from Local SEO settings appear as OpeningHoursSpecification | VERIFIED | add_opening_hours() method reads SAMAN_SEO_local_opening_hours array, groups days with identical hours, and outputs properly formatted OpeningHoursSpecification array with @type, dayOfWeek, opens, closes |
| 3 | Location coordinates appear as GeoCoordinates in schema | VERIFIED | add_geo() method reads SAMAN_SEO_local_latitude and SAMAN_SEO_local_longitude, casts to float, outputs GeoCoordinates nested type with @type, latitude, longitude |
| 4 | Existing Local SEO settings (address, phone, email) populate LocalBusiness fields without re-entry | VERIFIED | All 17 SAMAN_SEO_local_* options read via get_option() - no duplicate settings required. Options match exactly those registered in Local_SEO service |

**Score:** 4/4 truths verified

### Required Artifacts

| Artifact | Expected | Status | Details |
|----------|----------|--------|---------|
| includes/Schema/Types/class-localbusiness-schema.php | LocalBusiness schema class with full property support | VERIFIED | 325 lines, extends Abstract_Schema, implements all 3 abstract methods (get_type, is_needed, generate), 9 private helper methods for optional properties, no @context, no stub patterns |
| includes/Schema/class-schema-ids.php | localbusiness() method for @id generation | VERIFIED | Method exists at line 118, returns home_url('/') . '#localbusiness', matches pattern of other entity-level IDs (organization, person) |
| includes/class-saman-seo-plugin.php | LocalBusiness_Schema registration | VERIFIED | Use statement line 21, registered line 74-81 with priority 5 (after Organization 2, before WebPage 10), includes label for UI |
| includes/class-saman-seo-service-local-seo.php | Legacy filter disabled | VERIFIED | add_filter line commented out (line 33), explanatory comment present, method preserved for backward compatibility |

### Key Link Verification

| From | To | Via | Status | Details |
|------|-----|-----|--------|---------|
| LocalBusiness_Schema::generate() | Schema_IDs::localbusiness() | @id property assignment | WIRED | Line 85: '@id' => Schema_IDs::localbusiness() |
| LocalBusiness_Schema | SAMAN_SEO_local_* options | get_option() calls | WIRED | 17 get_option() calls across 9 methods reading business_type, business_name, logo, image, description, phone, email, price_range, street, city, state, zip, country, latitude, longitude, opening_hours, social_profiles |
| Plugin::boot() | LocalBusiness_Schema::class | registry->register() | WIRED | Line 74-81: registry->register('localbusiness', LocalBusiness_Schema::class, ...) |
| LocalBusiness_Schema | Abstract_Schema | inheritance | WIRED | Line 29: class LocalBusiness_Schema extends Abstract_Schema |

### Requirements Coverage

| Requirement | Status | Supporting Evidence |
|-------------|--------|---------------------|
| LOC-01: LocalBusiness schema type with full property support | SATISFIED | LocalBusiness_Schema generates all standard properties plus nested types |
| LOC-02: OpeningHoursSpecification for business hours | SATISFIED | add_opening_hours() implements day grouping and proper specification format |
| LOC-03: GeoCoordinates for location data | SATISFIED | add_geo() implements GeoCoordinates with float-cast coordinates |
| LOC-04: Integration with existing Local SEO settings as data source | SATISFIED | All data from existing SAMAN_SEO_local_* options, zero duplicate entry |

### Anti-Patterns Found

No anti-patterns detected.

**Scanned files:**
- includes/Schema/Types/class-localbusiness-schema.php - No TODO/FIXME comments, no placeholder content, no stub patterns, no inappropriate empty returns
- includes/Schema/class-schema-ids.php - Clean implementation
- includes/class-saman-seo-plugin.php - Clean registration
- includes/class-saman-seo-service-local-seo.php - Legacy filter properly disabled with comments

**PHP Syntax Validation:**
- class-localbusiness-schema.php: No syntax errors detected
- class-schema-ids.php: No syntax errors detected
- class-saman-seo-plugin.php: No syntax errors detected

### Human Verification Required

#### 1. Visual Output Verification

**Test:** Configure Local SEO settings with complete business information (name, address, phone, hours, coordinates). Visit homepage. View page source, find JSON-LD script tag, locate LocalBusiness in @graph array.

**Expected:** 
- LocalBusiness appears in @graph (NOT as separate script with own @context)
- All configured properties appear in output
- Nested types (PostalAddress, GeoCoordinates, OpeningHoursSpecification) properly formatted
- Days with same hours are grouped (e.g., Mon-Fri 9:00-17:00 = single specification with dayOfWeek array)

**Why human:** Requires WordPress environment running with Local SEO module enabled and settings configured. Cannot verify actual runtime output programmatically without running PHP/WordPress.

#### 2. Business Type Variant Verification

**Test:** Change SAMAN_SEO_local_business_type option to different values (Restaurant, Dentist, Store, etc.). View homepage source after each change.

**Expected:** @type in JSON-LD changes to match the selected business type (Restaurant, Dentist, etc. instead of generic LocalBusiness).

**Why human:** Requires testing multiple configuration states and comparing runtime output.

#### 3. Conditional Property Behavior

**Test:** Remove optional properties one by one (logo, coordinates, opening hours, social profiles). View output after each removal.

**Expected:** Schema output still valid with only required properties. Missing optional properties should not appear as empty strings or null values - they should be completely absent from output.

**Why human:** Requires testing multiple edge cases and negative scenarios (what happens when data is missing).

#### 4. Homepage-Only Output

**Test:** View JSON-LD on homepage vs. other pages (post, category, etc.) with Local SEO configured.

**Expected:** LocalBusiness schema appears ONLY on homepage/front_page. Should NOT appear on blog posts, archives, or other pages.

**Why human:** Requires navigating multiple page types and comparing outputs across different contexts.

## Verification Details

### Must-Have Derivation (Goal-Backward)

**Phase Goal:** LocalBusiness schema pulls from existing Local SEO settings automatically

**Truth 1:** "LocalBusiness schema renders with all standard properties"
- **Artifact:** class-localbusiness-schema.php
- **Evidence:** generate() method builds schema with @type, @id, name, url, plus 9 optional property groups (logo, image, description, contact, price_range, address, geo, opening_hours, social_profiles)

**Truth 2:** "Business hours appear as OpeningHoursSpecification"
- **Artifact:** add_opening_hours() method
- **Evidence:** Reads SAMAN_SEO_local_opening_hours array, day mapping exists (monday->Monday), grouping logic for identical hours, specification array with @type/dayOfWeek/opens/closes

**Truth 3:** "Location coordinates appear as GeoCoordinates"
- **Artifact:** add_geo() method
- **Evidence:** Reads SAMAN_SEO_local_latitude and SAMAN_SEO_local_longitude, requires both, casts to float, builds GeoCoordinates with @type/latitude/longitude

**Truth 4:** "Existing settings populate fields without re-entry"
- **Artifacts:** All get_option() calls match registered options in Local_SEO service
- **Evidence:** Cross-referenced 17 option names against class-saman-seo-service-local-seo.php register_settings() - exact match on business_name, business_type, logo, image, description, phone, email, price_range, street, city, state, zip, country, latitude, longitude, opening_hours, social_profiles

### Three-Level Verification Results

**Level 1: Existence**
- class-localbusiness-schema.php EXISTS (325 lines)
- class-schema-ids.php EXISTS (122 lines)
- LocalBusiness_Schema use statement EXISTS (line 21 of plugin.php)
- LocalBusiness_Schema registration EXISTS (line 74-81 of plugin.php)

**Level 2: Substantive**
- class-localbusiness-schema.php SUBSTANTIVE (325 lines > 15 minimum, no stubs, has exports)
- class-schema-ids.php SUBSTANTIVE (localbusiness() method complete, 9 lines including PHPDoc)
- Registration SUBSTANTIVE (full config with label and priority)
- Legacy filter PROPERLY DISABLED (commented with explanation, method preserved)

**Level 3: Wired**
- LocalBusiness_Schema IMPORTED (use statement in plugin.php line 21)
- LocalBusiness_Schema USED (registered in plugin.php line 76)
- Schema_IDs::localbusiness() CALLED (from generate() line 85)
- SAMAN_SEO_local_* options READ (17 different get_option() calls)
- Abstract_Schema EXTENDED (proper inheritance, all 3 abstract methods implemented)

### Priority Ordering Verification

Registration priority order in plugin.php:
1. WebSite (priority 1)
2. Organization (priority 2)
3. Person (priority 2)
4. LocalBusiness (priority 5) - Correct position
5. WebPage (priority 10)
6. Article/BlogPosting/NewsArticle (priority 15)
7. FAQPage/HowTo (priority 18)
8. Breadcrumb (priority 20)

Priority 5 correctly positions LocalBusiness after Organization (which it conceptually extends in Schema.org) and before page-specific schemas.

---

## Summary

**All automated checks passed.** Phase 4 goal fully achieved at code level:

1. LocalBusiness_Schema class complete with all standard properties
2. OpeningHoursSpecification implemented with day grouping
3. GeoCoordinates implemented with float casting
4. All 17 Local SEO options integrated as data source
5. Schema_IDs::localbusiness() method exists
6. Registry integration complete with priority 5
7. Legacy filter properly disabled
8. No @context in schema output (Graph_Manager pattern)
9. No stub patterns or anti-patterns
10. All PHP files pass syntax validation

**Human verification recommended** for 4 runtime scenarios (see Human Verification Required section above). These require a running WordPress environment with Local SEO module enabled and cannot be verified through static code analysis.

---

_Verified: 2026-01-23T20:29:44Z_
_Verifier: Claude (gsd-verifier)_
