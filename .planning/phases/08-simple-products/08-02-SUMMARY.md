---
phase: 08-simple-products
plan: 02
subsystem: schema
tags: [woocommerce, product-schema, offer, price, availability, structured-data]

# Dependency graph
requires:
  - phase: 08-01
    provides: Product_Schema skeleton with base properties (name, description, images, SKU, brand, identifiers, condition)
provides:
  - Offer schema for simple products with price and priceCurrency
  - Availability URL mapping (InStock/OutOfStock/PreOrder)
  - priceValidUntil from sale end dates
  - Seller reference to Organization schema
affects: [09-variable-products]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Availability URL mapping via stock status"
    - "Price validation before Offer output (skip zero/empty)"
    - "Seller references Organization @id for graph linking"

key-files:
  created: []
  modified:
    - includes/Schema/Types/class-product-schema.php

key-decisions:
  - "Use get_price() not get_regular_price() for active price (handles sale price automatically)"
  - "Skip offers entirely if price is zero or empty (Google requires valid price)"
  - "Map onbackorder to PreOrder (Google-recommended mapping)"
  - "Seller only added when Organization type is active in settings"

patterns-established:
  - "Availability mapping: instock->InStock, outofstock->OutOfStock, onbackorder->PreOrder"
  - "Price validation: empty or <= 0 returns empty Offer array"
  - "priceValidUntil format: Y-m-d (ISO 8601 date only)"

# Metrics
duration: 3min
completed: 2026-01-24
---

# Phase 8 Plan 02: Offer Schema Summary

**Simple products output Offer schema with price, priceCurrency, availability, priceValidUntil, and seller reference to Organization**

## Performance

- **Duration:** 3 min
- **Started:** 2026-01-24T10:00:00Z
- **Completed:** 2026-01-24T10:03:00Z
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments

- Availability URL mapping for InStock, OutOfStock, and PreOrder statuses
- Offer schema with price, priceCurrency, availability, and url
- priceValidUntil from WooCommerce sale end date
- Seller reference linking to Organization schema via Schema_IDs
- Price validation (zero/empty products do not output offers)
- Simple product type check (variable products excluded for Phase 9)

## Task Commits

Each task was committed atomically:

1. **Task 1: Implement availability URL mapping** - `a7bea25` (feat)
2. **Task 2: Implement Offer building for simple products** - `7e1120d` (feat)
3. **Task 3: Final verification and code review** - no changes needed (verification only)

## Files Created/Modified

- `includes/Schema/Types/class-product-schema.php` - Added get_availability_url() and build_offer() methods, integrated offers into generate() for simple products

## Decisions Made

- **get_price() for active price**: Uses WooCommerce's built-in price resolution (automatically returns sale price when applicable)
- **onbackorder -> PreOrder**: Google's recommended mapping for backorder status
- **Seller conditional on Organization type**: Only outputs seller reference when store has Organization schema active (not Person)
- **priceValidUntil date-only format**: ISO 8601 Y-m-d without time component per schema.org spec

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness

- Simple product schema complete with full Offer support
- Product_Schema has 14 methods covering all requirements
- Ready for Phase 9: Variable Products (will use AggregateOffer for price ranges)
- Variable products currently excluded via is_type('simple') check

---
*Phase: 08-simple-products*
*Completed: 2026-01-24*
