# Phase 4: LocalBusiness Schema - Research

**Researched:** 2026-01-23
**Domain:** Schema.org LocalBusiness, WordPress options integration, JSON-LD graph structure
**Confidence:** HIGH

## Summary

This research examines implementing LocalBusiness schema that pulls from existing Local SEO settings stored in WordPress options. The plugin already has a comprehensive Local SEO module (`class-saman-seo-service-local-seo.php`) that stores business information, opening hours, and geo coordinates. Currently, this service hooks into the legacy `SAMAN_SEO_jsonld_graph` filter to output schema, but the output includes `@context` in individual pieces and bypasses the new Schema Registry architecture.

The phase goal is to create a new `LocalBusiness_Schema` class following the established `Abstract_Schema` pattern that reads from existing options without requiring re-entry. The class should support business type variants (Restaurant, Dentist, Store, etc.) and properly format nested types (PostalAddress, OpeningHoursSpecification, GeoCoordinates).

Key finding: Google requires only `name` and `address` as truly required properties, but recommends `geo`, `telephone`, and `openingHoursSpecification` for better rich results eligibility. The existing Local SEO settings store all these fields, making integration straightforward.

**Primary recommendation:** Create `LocalBusiness_Schema` class that reads from `SAMAN_SEO_local_*` options, respects the `SAMAN_SEO_local_business_type` setting for specific subtypes, and disables the legacy `add_local_business_to_graph` filter to avoid duplicate output.

## Standard Stack

### Core
| Component | Implementation | Purpose | Why Standard |
|-----------|----------------|---------|--------------|
| LocalBusiness_Schema | `extends Abstract_Schema` | Main LocalBusiness schema output | Follows established Saman SEO schema architecture |
| PostalAddress nested type | Inline array in `generate()` | Address formatting | Google requires `@type: PostalAddress` structure |
| OpeningHoursSpecification | Array of specification objects | Business hours | Schema.org standard for hours representation |
| GeoCoordinates nested type | Inline array with lat/lng | Location pinpoint | Google recommends 5+ decimal places |

### Supporting
| Component | Implementation | When to Use |
|-----------|----------------|-------------|
| Schema_IDs::localbusiness() | New static method | Generate consistent `@id` for LocalBusiness |
| Business type mapping | Config array | Map stored type to `@type` value |

### Alternatives Considered
| Instead of | Could Use | Tradeoff |
|------------|-----------|----------|
| Single LocalBusiness class | Separate classes per business type | Single class with configurable type is simpler; subtypes inherit same properties |
| Reading options directly | Creating a Local SEO settings service | Direct option reads are simpler; the existing service already parses/validates |

## Architecture Patterns

### Recommended Project Structure
```
includes/Schema/Types/
  class-localbusiness-schema.php   # New LocalBusiness schema class
includes/Schema/
  class-schema-ids.php             # Add localbusiness() method
```

### Pattern 1: Options-Based Data Source

**What:** Schema class reads from WordPress options instead of post content or meta.

**When to use:** When schema data comes from global site settings rather than per-post content.

**Example:**
```php
// Source: Existing Organization_Schema pattern in codebase
class LocalBusiness_Schema extends Abstract_Schema {

    public function generate(): array {
        $business_name = get_option( 'SAMAN_SEO_local_business_name', '' );
        $business_type = get_option( 'SAMAN_SEO_local_business_type', 'LocalBusiness' );

        // Require business name minimum
        if ( empty( $business_name ) ) {
            return [];
        }

        return [
            '@type' => $business_type,
            '@id'   => Schema_IDs::localbusiness(),
            'name'  => $business_name,
            // ... more properties from options
        ];
    }
}
```

### Pattern 2: Conditional Page Targeting

**What:** Schema outputs only on specific page types (homepage by default for local business).

**When to use:** When schema represents site-wide entity that shouldn't appear on every page.

**Example:**
```php
// Source: Existing Local_SEO service behavior
public function is_needed(): bool {
    // Only output on homepage/front page by default
    if ( ! is_front_page() && ! is_home() ) {
        return false;
    }

    // Require minimum data
    $business_name = get_option( 'SAMAN_SEO_local_business_name', '' );
    return ! empty( $business_name );
}
```

### Pattern 3: Nested Type Building

**What:** Build nested schema types (PostalAddress, GeoCoordinates) as separate private methods.

**When to use:** When schema includes complex nested structures that benefit from encapsulation.

**Example:**
```php
// Source: Existing build_address() in Local_SEO service
private function build_address(): ?array {
    $street  = get_option( 'SAMAN_SEO_local_street', '' );
    $city    = get_option( 'SAMAN_SEO_local_city', '' );

    // Require at least street and city
    if ( empty( $street ) || empty( $city ) ) {
        return null;
    }

    $address = [
        '@type'           => 'PostalAddress',
        'streetAddress'   => $street,
        'addressLocality' => $city,
    ];

    // Add optional fields only if present
    $state = get_option( 'SAMAN_SEO_local_state', '' );
    if ( ! empty( $state ) ) {
        $address['addressRegion'] = $state;
    }

    return $address;
}
```

### Anti-Patterns to Avoid

- **Duplicating @context in LocalBusiness output:** Only Graph_Manager adds @context. Individual pieces must NOT include it.
- **Using generic LocalBusiness when specific type is set:** Honor the `SAMAN_SEO_local_business_type` option.
- **Outputting schema with empty required fields:** Return empty array if `name` or `address` is incomplete.
- **Hardcoding @id values:** Use Schema_IDs helper for consistent fragment identifiers.
- **Dual output from legacy and new systems:** Must disable legacy `add_local_business_to_graph` filter when new schema is active.

## Don't Hand-Roll

| Problem | Don't Build | Use Instead | Why |
|---------|-------------|-------------|-----|
| Opening hours grouping | Custom day-grouping algorithm | Existing `build_opening_hours()` logic | Already handles grouping days with same hours |
| Time format conversion | Custom formatter | Store/use 24-hour format directly (HH:MM) | Schema.org expects 24-hour format |
| Business type validation | Custom validation | Existing `get_business_types()` list | Already curated valid Schema.org LocalBusiness subtypes |
| Geo coordinate validation | Custom numeric checks | Existing `sanitize_coordinate()` method | Already validates and sanitizes lat/lng |

**Key insight:** The existing `Local_SEO` service class already has helper methods for building PostalAddress, GeoCoordinates, and OpeningHoursSpecification. The new schema class should reuse this logic pattern but call options directly (since the service methods are private). Alternatively, make the service methods public and inject the service.

## Common Pitfalls

### Pitfall 1: @context Included in LocalBusiness Output

**What goes wrong:** LocalBusiness schema includes `@context: https://schema.org`, causing duplicate @context in the graph.

**Why it happens:** The existing `build_schema()` method in Local_SEO service includes @context because it was written before the graph architecture.

**How to avoid:** New `LocalBusiness_Schema::generate()` must NOT include @context. Graph_Manager handles this at the root level.

**Warning signs:** JSON-LD output shows @context inside @graph pieces.

### Pitfall 2: Duplicate LocalBusiness Output

**What goes wrong:** Both the legacy `SAMAN_SEO_jsonld_graph` filter and new Schema Registry output LocalBusiness.

**Why it happens:** Legacy Local_SEO service still hooked at priority 20.

**How to avoid:** Either:
1. Disable the legacy hook when new LocalBusiness_Schema is registered, OR
2. Remove the legacy `add_local_business_to_graph` method call

**Warning signs:** Two LocalBusiness objects in @graph array.

### Pitfall 3: Using Generic Type When Specific Type Exists

**What goes wrong:** Output always shows `@type: LocalBusiness` even when user selected Restaurant.

**Why it happens:** Hardcoding type instead of reading from option.

**How to avoid:** Always read `SAMAN_SEO_local_business_type` option and use that value for @type.

**Warning signs:** Schema validator shows generic type despite specific setting.

### Pitfall 4: Missing Required Address Properties

**What goes wrong:** Google rich results test fails with "Missing field 'address'".

**Why it happens:** Schema outputs without checking that street + city are present.

**How to avoid:** `is_needed()` should verify minimum required data exists (name + street + city).

**Warning signs:** Rich Results Test warnings.

### Pitfall 5: Coordinates with Insufficient Precision

**What goes wrong:** GeoCoordinates appear in schema but map location is imprecise.

**Why it happens:** Coordinates stored or output with fewer than 5 decimal places.

**How to avoid:** Cast to float and ensure UI allows sufficient decimal precision. Google recommends minimum 5 decimal places.

**Warning signs:** Map pin appears in wrong location when tested.

### Pitfall 6: Organization vs LocalBusiness Conflict

**What goes wrong:** Both Organization schema and LocalBusiness schema output for same entity, confusing search engines.

**Why it happens:** LocalBusiness is a subtype of Organization; both shouldn't output separately for same business.

**How to avoid:** When LocalBusiness is active and valid, Organization schema should reference it or be suppressed. LocalBusiness inherits from Organization, so it satisfies Organization requirements.

**Warning signs:** Duplicate Organization-like entities in graph.

## Code Examples

### Complete LocalBusiness Schema Implementation

```php
// Source: Based on existing FAQPage_Schema pattern + Local_SEO service logic

namespace Saman\SEO\Schema\Types;

use Saman\SEO\Schema\Abstract_Schema;
use Saman\SEO\Schema\Schema_IDs;

class LocalBusiness_Schema extends Abstract_Schema {

    /**
     * Get the schema @type value.
     *
     * Returns the specific business type from settings.
     *
     * @return string Business type (LocalBusiness, Restaurant, Dentist, etc.)
     */
    public function get_type() {
        return get_option( 'SAMAN_SEO_local_business_type', 'LocalBusiness' );
    }

    /**
     * Determine if LocalBusiness schema should be output.
     *
     * Only outputs on homepage/front_page when business name exists.
     *
     * @return bool
     */
    public function is_needed(): bool {
        // Only on homepage
        if ( ! is_front_page() && ! is_home() ) {
            return false;
        }

        // Require business name
        $business_name = get_option( 'SAMAN_SEO_local_business_name', '' );
        return ! empty( $business_name );
    }

    /**
     * Generate LocalBusiness schema.
     *
     * @return array LocalBusiness schema or empty array.
     */
    public function generate(): array {
        $business_name = get_option( 'SAMAN_SEO_local_business_name', '' );

        if ( empty( $business_name ) ) {
            return [];
        }

        $schema = [
            '@type' => $this->get_type(),
            '@id'   => Schema_IDs::localbusiness(),
            'name'  => $business_name,
            'url'   => $this->context->site_url,
        ];

        // Optional properties
        $this->add_logo( $schema );
        $this->add_image( $schema );
        $this->add_description( $schema );
        $this->add_contact( $schema );
        $this->add_price_range( $schema );
        $this->add_address( $schema );
        $this->add_geo( $schema );
        $this->add_opening_hours( $schema );
        $this->add_social_profiles( $schema );

        return $schema;
    }

    private function add_logo( array &$schema ): void {
        $logo = get_option( 'SAMAN_SEO_local_logo', '' );
        if ( ! empty( $logo ) ) {
            $schema['logo'] = [
                '@type' => 'ImageObject',
                'url'   => $logo,
            ];
        }
    }

    private function add_image( array &$schema ): void {
        $image = get_option( 'SAMAN_SEO_local_image', '' );
        if ( ! empty( $image ) ) {
            $schema['image'] = $image;
        }
    }

    private function add_description( array &$schema ): void {
        $description = get_option( 'SAMAN_SEO_local_description', '' );
        if ( ! empty( $description ) ) {
            $schema['description'] = $description;
        }
    }

    private function add_contact( array &$schema ): void {
        $phone = get_option( 'SAMAN_SEO_local_phone', '' );
        $email = get_option( 'SAMAN_SEO_local_email', '' );

        if ( ! empty( $phone ) ) {
            $schema['telephone'] = $phone;
        }
        if ( ! empty( $email ) ) {
            $schema['email'] = $email;
        }
    }

    private function add_price_range( array &$schema ): void {
        $price_range = get_option( 'SAMAN_SEO_local_price_range', '' );
        if ( ! empty( $price_range ) ) {
            $schema['priceRange'] = $price_range;
        }
    }

    private function add_address( array &$schema ): void {
        $street  = get_option( 'SAMAN_SEO_local_street', '' );
        $city    = get_option( 'SAMAN_SEO_local_city', '' );

        // Require at least street and city
        if ( empty( $street ) || empty( $city ) ) {
            return;
        }

        $address = [
            '@type'           => 'PostalAddress',
            'streetAddress'   => $street,
            'addressLocality' => $city,
        ];

        $state = get_option( 'SAMAN_SEO_local_state', '' );
        if ( ! empty( $state ) ) {
            $address['addressRegion'] = $state;
        }

        $zip = get_option( 'SAMAN_SEO_local_zip', '' );
        if ( ! empty( $zip ) ) {
            $address['postalCode'] = $zip;
        }

        $country = get_option( 'SAMAN_SEO_local_country', '' );
        if ( ! empty( $country ) ) {
            $address['addressCountry'] = $country;
        }

        $schema['address'] = $address;
    }

    private function add_geo( array &$schema ): void {
        $latitude  = get_option( 'SAMAN_SEO_local_latitude', '' );
        $longitude = get_option( 'SAMAN_SEO_local_longitude', '' );

        if ( empty( $latitude ) || empty( $longitude ) ) {
            return;
        }

        $schema['geo'] = [
            '@type'     => 'GeoCoordinates',
            'latitude'  => (float) $latitude,
            'longitude' => (float) $longitude,
        ];
    }

    private function add_opening_hours( array &$schema ): void {
        $hours = get_option( 'SAMAN_SEO_local_opening_hours', [] );

        if ( empty( $hours ) || ! is_array( $hours ) ) {
            return;
        }

        $day_map = [
            'monday'    => 'Monday',
            'tuesday'   => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday'  => 'Thursday',
            'friday'    => 'Friday',
            'saturday'  => 'Saturday',
            'sunday'    => 'Sunday',
        ];

        $specifications = [];
        $grouped_hours  = [];

        // Group days with same hours
        foreach ( $hours as $day => $data ) {
            if ( empty( $data['enabled'] ) || '1' !== $data['enabled'] ) {
                continue;
            }

            $open  = $data['open'] ?? '';
            $close = $data['close'] ?? '';

            if ( empty( $open ) || empty( $close ) ) {
                continue;
            }

            $key = $open . '-' . $close;

            if ( ! isset( $grouped_hours[ $key ] ) ) {
                $grouped_hours[ $key ] = [
                    'days'   => [],
                    'opens'  => $open,
                    'closes' => $close,
                ];
            }

            $grouped_hours[ $key ]['days'][] = $day_map[ $day ];
        }

        // Build specifications
        foreach ( $grouped_hours as $group ) {
            $specifications[] = [
                '@type'     => 'OpeningHoursSpecification',
                'dayOfWeek' => count( $group['days'] ) === 1 ? $group['days'][0] : $group['days'],
                'opens'     => $group['opens'],
                'closes'    => $group['closes'],
            ];
        }

        if ( ! empty( $specifications ) ) {
            $schema['openingHoursSpecification'] = $specifications;
        }
    }

    private function add_social_profiles( array &$schema ): void {
        $profiles = get_option( 'SAMAN_SEO_local_social_profiles', [] );

        if ( ! empty( $profiles ) && is_array( $profiles ) ) {
            $filtered = array_values( array_filter( $profiles ) );
            if ( ! empty( $filtered ) ) {
                $schema['sameAs'] = $filtered;
            }
        }
    }
}
```

### Schema_IDs Addition

```php
// Add to class-schema-ids.php
/**
 * Get the LocalBusiness schema @id.
 *
 * @return string URL#localbusiness identifier.
 */
public static function localbusiness(): string {
    return home_url( '/' ) . '#localbusiness';
}
```

### Registration in Plugin Bootstrap

```php
// In class-saman-seo-plugin.php boot() method
// Priority 5 - after Organization (2) but before WebPage (10)
$registry->register(
    'localbusiness',
    LocalBusiness_Schema::class,
    [
        'label'    => __( 'Local Business', 'saman-seo' ),
        'priority' => 5,
    ]
);
```

### Disabling Legacy Filter

```php
// Option A: In Local_SEO::boot() - conditional registration
public function boot() {
    if ( ! \Saman\SEO\Helpers\module_enabled( 'local_seo' ) ) {
        return;
    }

    add_action( 'admin_menu', [ $this, 'register_menu' ], 100 );
    add_action( 'admin_init', [ $this, 'register_settings' ] );

    // DON'T add legacy filter - new LocalBusiness_Schema handles output
    // Removed: add_filter( 'SAMAN_SEO_jsonld_graph', [ $this, 'add_local_business_to_graph' ], 20, 1 );
}
```

## State of the Art

| Old Approach | Current Approach | When Changed | Impact |
|--------------|------------------|--------------|--------|
| @context in each piece | Single @context at graph root | JSON-LD 1.1 (2019) | Must remove @context from build_schema() |
| Standalone script tag | Part of unified @graph | Yoast 11.0+ standard | Already follows this in new architecture |
| Generic LocalBusiness | Most specific subtype | Google 2019+ guidance | Use SAMAN_SEO_local_business_type option |

**Deprecated/outdated:**
- Multiple `<script type="application/ld+json">` blocks: Current `add_local_business_to_graph` might create separate block
- @context inside graph pieces: Existing `build_schema()` includes it incorrectly
- `openingHours` text format: Use `openingHoursSpecification` array instead (already done)

## Open Questions

1. **Organization and LocalBusiness Relationship**
   - What we know: LocalBusiness is a subtype of Organization; both shouldn't output as separate entities for the same business
   - What's unclear: Should Organization_Schema be suppressed when LocalBusiness is active? Or should LocalBusiness be referenced from Organization?
   - Recommendation: For now, let both output. Organization provides publisher context for WebSite, LocalBusiness provides local business data. Future enhancement could link them via @id reference.

2. **Multi-Location Support**
   - What we know: Existing service has `SAMAN_SEO_local_enable_locations` and `SAMAN_SEO_local_locations` options
   - What's unclear: How should multiple locations integrate with new schema architecture?
   - Recommendation: Defer multi-location to future phase. Single location first. Each location would need unique @id.

3. **Page Targeting Beyond Homepage**
   - What we know: Current service only outputs on is_front_page() or is_home()
   - What's unclear: Should users be able to control which pages show LocalBusiness?
   - Recommendation: Keep homepage-only default. Future filter hook could allow per-page control.

## Sources

### Primary (HIGH confidence)
- [Google LocalBusiness Structured Data Documentation](https://developers.google.com/search/docs/appearance/structured-data/local-business) - Required/recommended properties, validation guidelines
- [Schema.org LocalBusiness](https://schema.org/LocalBusiness) - Complete property list, type hierarchy
- [Yoast LocalBusiness Schema Piece](https://developer.yoast.com/features/schema/pieces/localbusiness/) - Implementation pattern, fallback behavior
- Existing codebase: `class-saman-seo-service-local-seo.php` - Current option names, data structures, helper methods

### Secondary (MEDIUM confidence)
- [Schema App LocalBusiness Guide](https://www.schemaapp.com/schema-markup/how-to-do-schema-markup-for-local-business/) - Implementation best practices
- [Localo LocalBusiness Guide](https://localo.com/blog/local-business-schema) - Common mistakes, validation workflow

### Tertiary (LOW confidence)
- Example files in `jsonld_examples/` - Pattern reference only

## Existing Local SEO Settings Reference

For planner reference, here are all the options the LocalBusiness schema will read from:

| Option Name | Purpose | Format |
|-------------|---------|--------|
| `SAMAN_SEO_local_business_name` | Business name (required) | string |
| `SAMAN_SEO_local_business_type` | Schema.org type | string (e.g., 'Restaurant') |
| `SAMAN_SEO_local_description` | Business description | string |
| `SAMAN_SEO_local_logo` | Logo URL | URL string |
| `SAMAN_SEO_local_image` | Cover image URL | URL string |
| `SAMAN_SEO_local_price_range` | Price range | string (e.g., '$$') |
| `SAMAN_SEO_local_phone` | Phone number | string |
| `SAMAN_SEO_local_email` | Email address | string |
| `SAMAN_SEO_local_street` | Street address | string |
| `SAMAN_SEO_local_city` | City | string |
| `SAMAN_SEO_local_state` | State/Province | string |
| `SAMAN_SEO_local_zip` | Postal code | string |
| `SAMAN_SEO_local_country` | Country code | string (2-letter) |
| `SAMAN_SEO_local_latitude` | Latitude | numeric string |
| `SAMAN_SEO_local_longitude` | Longitude | numeric string |
| `SAMAN_SEO_local_social_profiles` | Social URLs | array of URLs |
| `SAMAN_SEO_local_opening_hours` | Business hours | array with day keys |

## Metadata

**Confidence breakdown:**
- Standard stack: HIGH - Direct mapping to existing architecture + Google docs
- Architecture: HIGH - Follows established patterns in codebase
- Pitfalls: HIGH - Based on existing code issues + Google validation requirements
- Integration: HIGH - Options already exist and are well-documented

**Research date:** 2026-01-23
**Valid until:** 2026-03-23 (60 days - Schema.org LocalBusiness spec is stable)
