# Roadmap: Saman SEO Schema Engine

## Overview

This roadmap transforms the Saman SEO plugin's hardcoded JSON-LD implementation into an extensible, registry-based schema engine. We start by building the core architecture (registry, base class, graph manager), then layer on schema types in order of Google rich results impact: content schemas (Article variants), interactive schemas (FAQ/HowTo with block integration), and LocalBusiness. Finally, we add admin UI for configuration and a developer API for third-party extension.

## Phases

**Phase Numbering:**
- Integer phases (1, 2, 3): Planned milestone work
- Decimal phases (2.1, 2.2): Urgent insertions (marked with INSERTED)

Decimal phases appear between their surrounding integers in numeric order.

- [x] **Phase 1: Schema Engine Foundation** - Registry, base class, graph manager, migration
- [x] **Phase 2: Content Schemas** - Article, BlogPosting, NewsArticle with full properties
- [x] **Phase 3: Interactive Schemas** - FAQ and HowTo with Gutenberg block integration
- [x] **Phase 4: LocalBusiness Schema** - Full LocalBusiness with Local SEO integration
- [ ] **Phase 5: Admin UI** - Post type defaults, editor sidebar, live preview
- [ ] **Phase 6: Developer API** - Hooks, filters, documentation for extensibility

## Phase Details

### Phase 1: Schema Engine Foundation
**Goal**: Establish the extensible architecture that all schema types will use
**Depends on**: Nothing (first phase)
**Requirements**: ENG-01, ENG-02, ENG-03, ENG-04
**Success Criteria** (what must be TRUE):
  1. Developer can register a new schema type via the registry and it appears in available types
  2. A schema object renders valid JSON-LD with @context, @id, and @type properties
  3. Multiple schema objects combine into a single @graph array in page output
  4. Existing WebSite, WebPage, and Breadcrumb schemas still output correctly (migration complete)
**Plans**: 3 plans

Plans:
- [x] 01-01-PLAN.md - Core schema infrastructure (Abstract_Schema, Schema_Context, Schema_IDs)
- [x] 01-02-PLAN.md - Registry and Graph Manager (Schema_Registry, Schema_Graph_Manager)
- [x] 01-03-PLAN.md - Migration (WebSite, WebPage, Breadcrumb, Organization, Person schemas + wiring)

### Phase 2: Content Schemas
**Goal**: Users get enhanced Article schemas that improve Google rich results eligibility
**Depends on**: Phase 1
**Requirements**: ART-01, ART-02, ART-03, ART-04, ART-05
**Success Criteria** (what must be TRUE):
  1. Post displays Article schema with full author Person object (not just name string)
  2. Article schema includes wordCount and articleBody excerpt automatically
  3. User can select BlogPosting as schema type for a post and output uses BlogPosting
  4. User can select NewsArticle as schema type and output includes dateline/printSection
  5. Post type settings determine default schema type for new posts
**Plans**: 2 plans

Plans:
- [x] 02-01-PLAN.md - Article_Schema base class with author Person, wordCount, articleBody
- [x] 02-02-PLAN.md - BlogPosting and NewsArticle extensions + registry

### Phase 3: Interactive Schemas
**Goal**: FAQ and HowTo blocks generate valid, complete schemas automatically
**Depends on**: Phase 1
**Requirements**: FAQ-01, FAQ-02, HOW-01, HOW-02
**Success Criteria** (what must be TRUE):
  1. FAQPage schema outputs with mainEntity containing Question/Answer pairs
  2. Adding FAQ block to post automatically generates FAQPage schema from block content
  3. HowTo schema outputs with step, tool, supply, and totalTime properties
  4. Adding HowTo block to post automatically generates HowTo schema from block content
**Plans**: 2 plans

Plans:
- [x] 03-01-PLAN.md - FAQPage schema with block parsing and registry integration
- [x] 03-02-PLAN.md - HowTo schema with block parsing and registry integration

### Phase 4: LocalBusiness Schema
**Goal**: LocalBusiness schema pulls from existing Local SEO settings automatically
**Depends on**: Phase 1
**Requirements**: LOC-01, LOC-02, LOC-03, LOC-04
**Success Criteria** (what must be TRUE):
  1. LocalBusiness schema renders with all standard properties (name, address, phone, etc.)
  2. Business hours from Local SEO settings appear as OpeningHoursSpecification
  3. Location coordinates appear as GeoCoordinates in schema
  4. Existing Local SEO settings (address, phone, email) populate LocalBusiness fields without re-entry
**Plans**: 2 plans

Plans:
- [x] 04-01-PLAN.md - LocalBusiness_Schema class with Schema_IDs method
- [x] 04-02-PLAN.md - Registry integration and legacy filter removal

### Phase 5: Admin UI
**Goal**: Users can configure and preview schemas through the WordPress admin
**Depends on**: Phase 1, Phase 2
**Requirements**: UI-01, UI-02, UI-03, UI-04, UI-05
**Success Criteria** (what must be TRUE):
  1. Search Appearance settings include post type schema defaults dropdown
  2. Post editor sidebar shows schema type selector with available types
  3. Post editor displays live JSON-LD preview panel showing rendered schema
  4. Preview updates automatically as user edits post content
  5. REST API endpoint returns schema preview data for current post
**Plans**: TBD

Plans:
- [ ] 05-01: TBD
- [ ] 05-02: TBD

### Phase 6: Developer API
**Goal**: Developers can extend the schema system with custom types and modifications
**Depends on**: Phase 1, Phase 2, Phase 3, Phase 4
**Requirements**: DEV-01, DEV-02, DEV-03, DEV-04, DEV-05
**Success Criteria** (what must be TRUE):
  1. Third-party plugin can register custom schema type via saman_seo_register_schema_type action
  2. Developer can modify schema fields via saman_seo_schema_{type}_fields filter
  3. Developer can modify final output via saman_seo_schema_{type}_output filter
  4. Developer can filter available types via saman_seo_schema_types filter
  5. Documentation exists explaining how to extend the schema system
**Plans**: TBD

Plans:
- [ ] 06-01: TBD
- [ ] 06-02: TBD

## Progress

**Execution Order:**
Phases execute in numeric order: 1 -> 2 -> 3 -> 4 -> 5 -> 6
Note: Phases 2, 3, 4 can potentially run in parallel after Phase 1 completes.

| Phase | Plans Complete | Status | Completed |
|-------|----------------|--------|-----------|
| 1. Schema Engine Foundation | 3/3 | Complete | 2026-01-23 |
| 2. Content Schemas | 2/2 | Complete | 2026-01-23 |
| 3. Interactive Schemas | 2/2 | Complete | 2026-01-23 |
| 4. LocalBusiness Schema | 2/2 | Complete | 2026-01-23 |
| 5. Admin UI | 0/? | Not started | - |
| 6. Developer API | 0/? | Not started | - |

---
*Roadmap created: 2026-01-23*
*Last updated: 2026-01-23 - Phase 4 complete (verified)*
