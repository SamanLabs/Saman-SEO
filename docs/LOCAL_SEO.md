# Local SEO Guide

Complete guide to configuring local business SEO with Saman SEO.

---

## Table of Contents

- [Overview](#overview)
- [Enabling Local SEO](#enabling-local-seo)
- [Business Information](#business-information)
- [Location & Map Settings](#location--map-settings)
- [Social Profiles](#social-profiles)
- [Opening Hours](#opening-hours)
- [Schema Output](#schema-output)
- [Best Practices](#best-practices)
- [Examples](#examples)

---

## Overview

Saman SEO's Local SEO module helps local businesses improve their visibility in local search results by:

- Generating LocalBusiness schema markup
- Providing accurate NAP (Name, Address, Phone) data
- Including geo-coordinates for maps
- Listing opening hours
- Connecting social profiles
- Supporting Google My Business integration

**Location:** Navigate to **Saman SEO → Local SEO**

**File:** `includes/class-wpseopilot-service-local-seo.php`

---

## Enabling Local SEO

### Step 1: Enable the Module

Navigate to **Saman SEO → General Settings**

Find **Local SEO Settings** and check **Enable Local SEO**.

```php
update_option( 'wpseopilot_enable_local_seo', '1' );
```

---

### Step 2: Configure Business Details

Navigate to **Saman SEO → Local SEO** to access the configuration panel.

---

## Business Information

### Business Name

**Option:** `wpseopilot_local_business_name`

Your official business name (as registered).

**Example:**
```
Joe's Coffee Shop
```

```php
update_option( 'wpseopilot_local_business_name', 'Joe\'s Coffee Shop' );
```

---

### Business Type

**Option:** `wpseopilot_local_business_type`

Select from Schema.org business types:

**Common Types:**
- Restaurant
- Store
- LocalBusiness (generic)
- ProfessionalService
- HealthAndBeautyBusiness
- HomeAndConstructionBusiness
- AutomativeBusiness
- FoodEstablishment
- LodgingBusiness

**Example:**
```
Restaurant
```

```php
update_option( 'wpseopilot_local_business_type', 'Restaurant' );
```

**Full List:** [Schema.org LocalBusiness Types](https://schema.org/LocalBusiness)

---

### Business Description

**Option:** `wpseopilot_local_description`

A brief description of your business (1-2 sentences).

**Example:**
```
Joe's Coffee Shop is a family-owned café serving artisan coffee and fresh pastries since 2010. We pride ourselves on quality ingredients and friendly service.
```

```php
update_option( 'wpseopilot_local_description', 'Joe\'s Coffee Shop is a family-owned café...' );
```

---

### Business Logo

**Option:** `wpseopilot_local_logo`

URL to your business logo image.

**Requirements:**
- Recommended: 600x60px minimum
- Format: JPG, PNG
- Aspect ratio: 1:1 (square) preferred

**Example:**
```
https://example.com/wp-content/uploads/logo.png
```

```php
update_option( 'wpseopilot_local_logo', 'https://example.com/logo.png' );
```

---

### Business Image

**Option:** `wpseopilot_local_image`

URL to a representative image of your business (storefront, interior, etc.).

**Example:**
```
https://example.com/wp-content/uploads/storefront.jpg
```

```php
update_option( 'wpseopilot_local_image', 'https://example.com/storefront.jpg' );
```

---

### Price Range

**Option:** `wpseopilot_local_price_range`

Indicate your price range using dollar signs.

**Format:**
- `$` - Inexpensive
- `$$` - Moderate
- `$$$` - Expensive
- `$$$$` - Very Expensive

**Example:**
```
$$
```

```php
update_option( 'wpseopilot_local_price_range', '$$' );
```

---

## Contact Information

### Phone Number

**Option:** `wpseopilot_local_phone`

Business phone number.

**Format:** Use international format when possible

**Examples:**
```
+1-555-123-4567
(555) 123-4567
555-123-4567
```

```php
update_option( 'wpseopilot_local_phone', '+1-555-123-4567' );
```

---

### Email Address

**Option:** `wpseopilot_local_email`

Business email contact.

**Example:**
```
contact@joescoffee.com
```

```php
update_option( 'wpseopilot_local_email', 'contact@joescoffee.com' );
```

---

## Location & Map Settings

### Street Address

**Option:** `wpseopilot_local_street`

Street address line.

**Example:**
```
123 Main Street
```

```php
update_option( 'wpseopilot_local_street', '123 Main Street' );
```

---

### City

**Option:** `wpseopilot_local_city`

**Example:**
```
San Francisco
```

```php
update_option( 'wpseopilot_local_city', 'San Francisco' );
```

---

### State/Province

**Option:** `wpseopilot_local_state`

**Example:**
```
CA
California
```

```php
update_option( 'wpseopilot_local_state', 'CA' );
```

---

### ZIP/Postal Code

**Option:** `wpseopilot_local_zip`

**Example:**
```
94102
```

```php
update_option( 'wpseopilot_local_zip', '94102' );
```

---

### Country

**Option:** `wpseopilot_local_country`

**Example:**
```
United States
USA
US
```

```php
update_option( 'wpseopilot_local_country', 'United States' );
```

---

### Geographic Coordinates

**Option:** `wpseopilot_local_latitude`, `wpseopilot_local_longitude`

Precise geo-coordinates for map display.

**Find Your Coordinates:**
1. Go to [Google Maps](https://maps.google.com)
2. Right-click your business location
3. Select "What's here?"
4. Copy coordinates

**Example:**
```
Latitude: 37.7749
Longitude: -122.4194
```

```php
update_option( 'wpseopilot_local_latitude', '37.7749' );
update_option( 'wpseopilot_local_longitude', '-122.4194' );
```

---

## Social Profiles

**Option:** `wpseopilot_local_social_profiles`

List of your business's social media profiles.

**Supported Platforms:**
- Facebook
- Twitter
- Instagram
- LinkedIn
- YouTube
- Pinterest
- TikTok

**Format:** JSON array

**Example:**

```php
$social_profiles = [
    'https://www.facebook.com/joescoffee',
    'https://twitter.com/joescoffee',
    'https://www.instagram.com/joescoffee'
];

update_option( 'wpseopilot_local_social_profiles', json_encode( $social_profiles ) );
```

**Via Admin UI:**

Enter each URL on a new line:
```
https://www.facebook.com/joescoffee
https://twitter.com/joescoffee
https://www.instagram.com/joescoffee
```

---

## Opening Hours

**Option:** `wpseopilot_local_opening_hours`

Specify when your business is open.

**Format:** JSON array

**Example:**

```php
$opening_hours = [
    'Monday' => [ 'open' => '08:00', 'close' => '18:00' ],
    'Tuesday' => [ 'open' => '08:00', 'close' => '18:00' ],
    'Wednesday' => [ 'open' => '08:00', 'close' => '18:00' ],
    'Thursday' => [ 'open' => '08:00', 'close' => '18:00' ],
    'Friday' => [ 'open' => '08:00', 'close' => '20:00' ],
    'Saturday' => [ 'open' => '09:00', 'close' => '20:00' ],
    'Sunday' => [ 'open' => '10:00', 'close' => '16:00' ]
];

update_option( 'wpseopilot_local_opening_hours', json_encode( $opening_hours ) );
```

**Time Format:** 24-hour format (HH:MM)

**For Closed Days:**

```php
'Monday' => [ 'open' => 'closed', 'close' => 'closed' ]
```

---

## Schema Output

### What Gets Generated

Saman SEO outputs JSON-LD structured data based on your configuration:

```json
{
  "@context": "https://schema.org",
  "@type": "Restaurant",
  "name": "Joe's Coffee Shop",
  "description": "Joe's Coffee Shop is a family-owned café...",
  "image": "https://example.com/storefront.jpg",
  "logo": "https://example.com/logo.png",
  "priceRange": "$$",
  "telephone": "+1-555-123-4567",
  "email": "contact@joescoffee.com",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "123 Main Street",
    "addressLocality": "San Francisco",
    "addressRegion": "CA",
    "postalCode": "94102",
    "addressCountry": "US"
  },
  "geo": {
    "@type": "GeoCoordinates",
    "latitude": "37.7749",
    "longitude": "-122.4194"
  },
  "url": "https://example.com",
  "sameAs": [
    "https://www.facebook.com/joescoffee",
    "https://twitter.com/joescoffee",
    "https://www.instagram.com/joescoffee"
  ],
  "openingHoursSpecification": [
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "Monday",
      "opens": "08:00",
      "closes": "18:00"
    },
    {
      "@type": "OpeningHoursSpecification",
      "dayOfWeek": "Tuesday",
      "opens": "08:00",
      "closes": "18:00"
    }
  ]
}
```

### Where It Appears

This schema is output in the `<head>` section of your website on:
- Homepage
- About page (if configured)
- Contact page (if configured)

---

## Best Practices

### 1. Use Accurate Business Type

Choose the most specific type that matches your business:

**Examples:**
- Coffee shop: `CafeOrCoffeeShop`
- Law firm: `Attorney`
- Dental office: `Dentist`
- Hair salon: `BeautySalon`

---

### 2. Keep NAP Consistent

Ensure your Name, Address, Phone are identical across:
- Your website
- Google My Business
- Social profiles
- Directory listings

**Inconsistencies harm local SEO.**

---

### 3. Use Complete Address

Include all address components:
- Street address
- City
- State/Province
- ZIP/Postal code
- Country

---

### 4. Add Accurate Coordinates

Geo-coordinates help search engines and maps display your exact location.

**How to Verify:**
1. Enter coordinates in [Google Maps](https://maps.google.com)
2. Confirm it points to your business

---

### 5. Update Opening Hours Regularly

- Update for holidays
- Update for seasonal changes
- Mark closed days

**Impact:** Google shows accurate hours in search results.

---

### 6. Link All Active Social Profiles

Only include profiles you actively maintain.

**Don't include:**
- Inactive profiles
- Personal profiles (use business accounts)

---

### 7. Use High-Quality Images

- Logo: Clean, professional
- Business image: Well-lit, clear representation

---

## Examples

### Example 1: Restaurant

```php
update_option( 'wpseopilot_enable_local_seo', '1' );
update_option( 'wpseopilot_local_business_name', 'Bella Italian Bistro' );
update_option( 'wpseopilot_local_business_type', 'Restaurant' );
update_option( 'wpseopilot_local_description', 'Authentic Italian cuisine in the heart of downtown. Family recipes since 1985.' );
update_option( 'wpseopilot_local_price_range', '$$$' );
update_option( 'wpseopilot_local_phone', '+1-555-987-6543' );
update_option( 'wpseopilot_local_email', 'info@bellabistro.com' );
update_option( 'wpseopilot_local_street', '456 Oak Avenue' );
update_option( 'wpseopilot_local_city', 'Chicago' );
update_option( 'wpseopilot_local_state', 'IL' );
update_option( 'wpseopilot_local_zip', '60601' );
update_option( 'wpseopilot_local_country', 'United States' );
update_option( 'wpseopilot_local_latitude', '41.8781' );
update_option( 'wpseopilot_local_longitude', '-87.6298' );

$opening_hours = [
    'Monday' => [ 'open' => 'closed', 'close' => 'closed' ],
    'Tuesday' => [ 'open' => '17:00', 'close' => '22:00' ],
    'Wednesday' => [ 'open' => '17:00', 'close' => '22:00' ],
    'Thursday' => [ 'open' => '17:00', 'close' => '22:00' ],
    'Friday' => [ 'open' => '17:00', 'close' => '23:00' ],
    'Saturday' => [ 'open' => '12:00', 'close' => '23:00' ],
    'Sunday' => [ 'open' => '12:00', 'close' => '21:00' ]
];
update_option( 'wpseopilot_local_opening_hours', json_encode( $opening_hours ) );
```

---

### Example 2: Law Firm

```php
update_option( 'wpseopilot_enable_local_seo', '1' );
update_option( 'wpseopilot_local_business_name', 'Smith & Associates Law' );
update_option( 'wpseopilot_local_business_type', 'Attorney' );
update_option( 'wpseopilot_local_description', 'Experienced legal representation in family law, estate planning, and business matters.' );
update_option( 'wpseopilot_local_phone', '+1-555-321-9876' );
update_option( 'wpseopilot_local_email', 'contact@smithlaw.com' );
update_option( 'wpseopilot_local_street', '789 Legal Plaza, Suite 200' );
update_option( 'wpseopilot_local_city', 'Boston' );
update_option( 'wpseopilot_local_state', 'MA' );
update_option( 'wpseopilot_local_zip', '02108' );
update_option( 'wpseopilot_local_country', 'United States' );

$social_profiles = [
    'https://www.linkedin.com/company/smithlaw',
    'https://www.facebook.com/smithlawfirm'
];
update_option( 'wpseopilot_local_social_profiles', json_encode( $social_profiles ) );
```

---

### Example 3: Retail Store

```php
update_option( 'wpseopilot_enable_local_seo', '1' );
update_option( 'wpseopilot_local_business_name', 'Green Thumb Garden Center' );
update_option( 'wpseopilot_local_business_type', 'Store' );
update_option( 'wpseopilot_local_description', 'Your local source for plants, gardening supplies, and expert advice.' );
update_option( 'wpseopilot_local_price_range', '$$' );
update_option( 'wpseopilot_local_phone', '+1-555-111-2222' );
update_option( 'wpseopilot_local_street', '321 Garden Road' );
update_option( 'wpseopilot_local_city', 'Portland' );
update_option( 'wpseopilot_local_state', 'OR' );
update_option( 'wpseopilot_local_zip', '97201' );

$opening_hours = [
    'Monday' => [ 'open' => '09:00', 'close' => '18:00' ],
    'Tuesday' => [ 'open' => '09:00', 'close' => '18:00' ],
    'Wednesday' => [ 'open' => '09:00', 'close' => '18:00' ],
    'Thursday' => [ 'open' => '09:00', 'close' => '18:00' ],
    'Friday' => [ 'open' => '09:00', 'close' => '18:00' ],
    'Saturday' => [ 'open' => '09:00', 'close' => '17:00' ],
    'Sunday' => [ 'open' => '10:00', 'close' => '16:00' ]
];
update_option( 'wpseopilot_local_opening_hours', json_encode( $opening_hours ) );
```

---

## Validation

### Test Your Schema

Use these tools to validate your LocalBusiness schema:

1. **[Google Rich Results Test](https://search.google.com/test/rich-results)**
   - Enter your homepage URL
   - Verify LocalBusiness schema appears

2. **[Schema.org Validator](https://validator.schema.org/)**
   - Paste your homepage URL or schema JSON
   - Check for errors

3. **[Google Search Console](https://search.google.com/search-console)**
   - Navigate to Enhancements
   - Check for structured data errors

---

## Troubleshooting

### Schema Not Appearing

**Check:**
1. Local SEO module is enabled
2. Required fields are filled (name, address, phone)
3. View page source and search for `"@type": "LocalBusiness"`

---

### Google Not Showing Business Info

**Possible Causes:**
1. Google hasn't recrawled your site yet
2. Incomplete information
3. NAP inconsistencies

**Solution:**
1. Submit sitemap to Google Search Console
2. Request indexing of homepage
3. Ensure NAP consistency across the web

---

### Wrong Business Type in Results

**Issue:** Google shows incorrect business category

**Solution:**
1. Verify `wpseopilot_local_business_type` is set correctly
2. Update Google My Business profile to match
3. Request re-indexing

---

## Disable Local SEO

To disable the Local SEO module:

```php
update_option( 'wpseopilot_enable_local_seo', '0' );
```

Or via admin: **Saman SEO → General Settings** → Uncheck **Enable Local SEO**

---

## Related Documentation

- **[Getting Started](GETTING_STARTED.md)** - Basic plugin setup
- **[Developer Guide](DEVELOPER_GUIDE.md)** - Extending Local SEO features
- **[Filter Reference](FILTERS.md)** - Schema customization filters

---

## External Resources

- **[Schema.org LocalBusiness](https://schema.org/LocalBusiness)**
- **[Google My Business](https://www.google.com/business/)**
- **[Local SEO Guide by Moz](https://moz.com/learn/seo/local)**

---

**For more help, visit the [GitHub repository](https://github.com/jhd3197/WP-SEO-Pilot).**
