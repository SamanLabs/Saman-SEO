# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-23)

**Core value:** Enable Product rich results in Google search for WooCommerce stores with price, availability, images, and review ratings.
**Current focus:** Phase 9 - Variable Products

## Current Position

Phase: 9 of 10 (Variable Products)
Plan: 1 of 1 in current phase
Status: Phase complete
Last activity: 2026-01-24 - Completed 09-01-PLAN.md

Progress: [||||||||||..........] v1.0 100% | [||||||..............] v1.1 60%

## Performance Metrics

**v1.0 Velocity:**
- Total plans completed: 14
- Average duration: 3 min
- Total execution time: 47 min

**By Phase:**

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 01-schema-engine-foundation | 3 | 9 min | 3 min |
| 02-content-schemas | 2 | 5 min | 2.5 min |
| 03-interactive-schemas | 2 | 6 min | 3 min |
| 04-localbusiness-schema | 2 | 4 min | 2 min |
| 05-admin-ui | 3 | 11 min | 3.7 min |
| 06-developer-api | 2 | 7 min | 3.5 min |

**v1.1 Velocity:**
- Plans completed: 4
- Total execution time: 11 min
- Estimated remaining: 1-2 plans (Phase 10)

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 07-foundation | 1 | 2 min | 2 min |
| 08-simple-products | 2 | 6 min | 3 min |
| 09-variable-products | 1 | 3 min | 3 min |

## Accumulated Context

### Decisions

v1.0 decisions archived in PROJECT.md Key Decisions table.

v1.1 decisions:
- AggregateOffer approach for variable products (not ProductGroup)
- Disable WC native schema first (duplicate schema is #1 failure cause)
- Only output on is_singular('product')
- WC schema disable at init priority 0 (earliest reliable hook)
- Product schema priority 16 (after Article at 15)
- itemCondition uses full URL format (https://schema.org/NewCondition)
- Default condition is NewCondition (most WC products are new)
- Brand fallback: meta > attribute > global option
- Use get_price() for active price (handles sale price automatically)
- Skip offers entirely if price is zero/empty
- onbackorder maps to PreOrder (Google-recommended)
- Seller only added when Organization type is active
- Omit priceValidUntil from AggregateOffer (variations have different dates)
- Omit seller from AggregateOffer (not standard practice)
- offerCount uses count(get_children()) for variation count

### Pending Todos

None.

### Blockers/Concerns

None.

## Session Continuity

Last session: 2026-01-24
Stopped at: Completed 09-01-PLAN.md (Phase 9 complete)
Resume file: None
Next action: Plan and execute Phase 10 (Reviews with AggregateRating)
