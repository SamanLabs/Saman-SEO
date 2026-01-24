---
phase: 09-variable-products
plan: 01
subsystem: schema
tags: [woocommerce, product, aggregateoffer, variable-products, schema.org]

# Dependency graph
requires:
  - phase: 08-simple-products
    provides: Product_Schema with build_offer() for simple products
provides:
  - AggregateOffer schema for variable products
  - Price range output (lowPrice/highPrice)
  - Aggregated availability via child_is_in_stock()
affects: [10-reviews, future-productgroup-enhancement]

# Tech tracking
tech-stack:
  added: []
  patterns:
    - "Product type branching: is_type('simple') vs is_type('variable')"
    - "Variation price API: get_variation_price('min'|'max')"
    - "Aggregated stock: child_is_in_stock() for any-variation-in-stock"

key-files:
  created: []
  modified:
    - includes/Schema/Types/class-product-schema.php

key-decisions:
  - "Omit priceValidUntil from AggregateOffer (variations may have different sale dates)"
  - "Omit seller from AggregateOffer (not standard practice)"
  - "Include offerCount using count(get_children())"

patterns-established:
  - "build_aggregate_offer() pattern for variable products"
  - "Type-specific offer handling in generate() with elseif chain"

# Metrics
duration: 3min
completed: 2026-01-24
---

# Phase 9 Plan 1: AggregateOffer Schema Summary

**AggregateOffer schema for variable products with lowPrice/highPrice from variation prices and child_is_in_stock() availability**

## Performance

- **Duration:** 3 min
- **Started:** 2026-01-24
- **Completed:** 2026-01-24
- **Tasks:** 3
- **Files modified:** 1

## Accomplishments
- Added build_aggregate_offer() method for variable product price ranges
- Integrated variable product handling into generate() method
- Availability correctly shows InStock if ANY variation is in stock
- Optional offerCount property included for variation count

## Task Commits

Each task was committed atomically:

1. **Task 1-3: AggregateOffer implementation** - `aba513b` (feat)
   - Combines all tasks as single atomic implementation

**Plan metadata:** (pending)

## Files Created/Modified
- `includes/Schema/Types/class-product-schema.php` - Added build_aggregate_offer() method and variable product branch in generate()

## Decisions Made
- Omit priceValidUntil from AggregateOffer - variations may have different sale end dates
- Omit seller from AggregateOffer - not standard practice per research
- Use count(get_children()) for offerCount - simple approach, includes all variations

## Deviations from Plan

None - plan executed exactly as written.

## Issues Encountered

None.

## User Setup Required

None - no external service configuration required.

## Next Phase Readiness
- Variable products now output AggregateOffer with price ranges
- Simple products continue to output single Offer (no regression)
- Ready for Phase 10 (Reviews) or additional product type support

---
*Phase: 09-variable-products*
*Completed: 2026-01-24*
