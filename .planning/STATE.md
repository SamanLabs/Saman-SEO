# Project State

## Project Reference

See: .planning/PROJECT.md (updated 2026-01-23)

**Core value:** Enable Product rich results in Google search for WooCommerce stores with price, availability, images, and review ratings.
**Current focus:** Phase 8 - Simple Products

## Current Position

Phase: 8 of 10 (Simple Products)
Plan: 1 of ? in current phase
Status: In progress
Last activity: 2026-01-24 - Completed 08-01-PLAN.md

Progress: [||||||||||..........] v1.0 100% | [|||.................] v1.1 30%

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
- Plans completed: 2
- Total execution time: 5 min
- Estimated remaining: 2-4 plans across 2 phases

| Phase | Plans | Total | Avg/Plan |
|-------|-------|-------|----------|
| 07-foundation | 1 | 2 min | 2 min |
| 08-simple-products | 1 | 3 min | 3 min |

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

### Pending Todos

None.

### Blockers/Concerns

None.

## Session Continuity

Last session: 2026-01-24
Stopped at: Completed 08-01-PLAN.md
Resume file: None
Next action: Execute 08-02-PLAN.md (Offers for simple products)
