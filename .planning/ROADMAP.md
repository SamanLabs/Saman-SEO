# WP SEO Pilot - Feature Roadmap

## Current Status (v0.1.41)

### Completed Features

**Core SEO**
- [x] Meta title & description management
- [x] Open Graph & Twitter Card tags
- [x] JSON-LD structured data (Article, Organization, WebSite, BreadcrumbList)
- [x] Canonical URL management
- [x] Robots meta directives (noindex, nofollow)
- [x] Focus keyword tracking

**Technical SEO**
- [x] XML Sitemap generation
- [x] Robots.txt editor
- [x] LLM.txt generator (for AI crawlers)
- [x] Redirect manager (301, 302, 307)
- [x] 404 request logging/monitoring

**AI Integration**
- [x] WP AI Pilot integration
- [x] AI-powered title generation
- [x] AI-powered description generation
- [x] Custom prompt configuration
- [x] SEO Assistant registration with WP AI Pilot

**Content Tools**
- [x] Internal linking suggestions
- [x] SEO score calculation (basic)
- [x] Post meta box in classic editor
- [x] Gutenberg sidebar panel

**Admin UI**
- [x] V2 React-based admin dashboard
- [x] Search Appearance settings
- [x] Social settings page
- [x] Sitemap settings page
- [x] SEO score badge in admin post list

**Import/Export**
- [x] Yoast SEO importer
- [x] Rank Math importer (basic)
- [x] All in One SEO importer (basic)

---

## Phase 1: Core Polish (Priority: High)

### 1.1 Enhanced SEO Scoring
- [ ] Comprehensive SEO score algorithm
  - Title length analysis (50-60 chars optimal)
  - Description length analysis (120-155 chars optimal)
  - Keyword density calculation
  - Heading structure analysis (H1, H2, H3)
  - Image alt text checks
  - Internal link count
  - External link count
  - Content length scoring
- [ ] Real-time score updates in editor
- [ ] Score history tracking
- [ ] Score comparison with competitors

### 1.2 Content Analysis
- [ ] Readability analysis (Flesch-Kincaid score)
- [ ] Passive voice detection
- [ ] Sentence length analysis
- [ ] Paragraph length recommendations
- [ ] Transition word usage
- [ ] Keyword placement analysis (title, headings, first paragraph)

### 1.3 Focus Keyword Improvements
- [ ] Multiple focus keywords support
- [ ] Related keywords suggestions (via AI)
- [ ] Keyword difficulty indicator
- [ ] Keyword tracking across posts
- [ ] Keyword cannibalization warnings

### 1.4 UI/UX Improvements
- [ ] Onboarding wizard for new users
- [ ] Contextual help tooltips
- [ ] Dark mode support
- [ ] Keyboard shortcuts
- [ ] Bulk actions on SEO scores page
- [ ] Search/filter for all admin tables

---

## Phase 2: Advanced Features (Priority: Medium)

### 2.1 Schema Markup Expansion
- [ ] FAQ Schema
- [ ] How-To Schema
- [ ] Product Schema (basic, without WooCommerce)
- [ ] Recipe Schema
- [ ] Event Schema
- [ ] Video Schema
- [ ] Review Schema
- [ ] Local Business Schema (multiple locations)
- [ ] Person Schema
- [ ] Course Schema
- [ ] Job Posting Schema
- [ ] Custom schema builder (JSON-LD editor)

### 2.2 Breadcrumbs
- [ ] Breadcrumb generation
- [ ] Shortcode support `[wpseopilot_breadcrumbs]`
- [ ] Block support for Gutenberg
- [ ] Customizable separator
- [ ] Schema.org BreadcrumbList output
- [ ] Template override support

### 2.3 Content Insights Dashboard
- [ ] Posts without meta descriptions
- [ ] Posts with thin content (<300 words)
- [ ] Posts with missing images
- [ ] Posts with missing alt text
- [ ] Orphan pages (no internal links)
- [ ] Duplicate titles/descriptions
- [ ] Broken internal links checker

### 2.4 SEO Audit System
- [ ] Site-wide SEO audit
- [ ] Technical SEO checks
  - HTTPS verification
  - Mobile-friendliness
  - Page speed indicators
  - Crawlability issues
- [ ] Exportable audit reports (PDF/CSV)
- [ ] Scheduled audits with email notifications
- [ ] Historical audit comparison

---

## Phase 3: Integrations (Priority: Medium)

### 3.1 Google Search Console Integration
- [ ] OAuth authentication
- [ ] Search performance data
- [ ] Index coverage status
- [ ] Sitemap submission status
- [ ] Manual action alerts
- [ ] Top performing pages/queries
- [ ] Dashboard widget

### 3.2 Google Analytics Integration
- [ ] GA4 property connection
- [ ] Basic traffic stats in dashboard
- [ ] Top pages by organic traffic
- [ ] Bounce rate by page
- [ ] Link with SEO scores

### 3.3 WooCommerce Integration
- [ ] Product schema generation
- [ ] Product meta fields
- [ ] Category/tag SEO settings
- [ ] Shop page SEO settings
- [ ] Product review schema
- [ ] Price/availability in search results

### 3.4 Third-Party Services
- [ ] IndexNow support (Bing instant indexing)
- [ ] Google Indexing API support
- [ ] Cloudflare cache purge on update

---

## Phase 4: Advanced Workflows (Priority: Low)

### 4.1 Content Templates
- [ ] SEO templates for post types
- [ ] Variable system ({{title}}, {{site_name}}, {{date}}, etc.)
- [ ] Category-specific templates
- [ ] Author-specific templates
- [ ] Custom field integration

### 4.2 Bulk Editing
- [ ] Bulk edit titles/descriptions
- [ ] Bulk regenerate AI content
- [ ] Bulk update canonical URLs
- [ ] Bulk set robots meta
- [ ] CSV import/export for metadata

### 4.3 Redirect Manager Enhancements
- [ ] Regex redirect support
- [ ] Redirect chain detection
- [ ] Auto-redirect on slug change
- [ ] Import redirects from CSV
- [ ] Export redirects to CSV
- [ ] Redirect analytics (hit count)

### 4.4 Multi-site Support
- [ ] Network-wide settings
- [ ] Per-site overrides
- [ ] Centralized SEO reporting
- [ ] Shared redirect rules

---

## Phase 5: Enterprise Features (Priority: Future)

### 5.1 Team Collaboration
- [ ] Role-based access control
- [ ] SEO task assignments
- [ ] Editorial workflow integration
- [ ] Change history/audit log
- [ ] Approval workflows

### 5.2 Advanced AI Features
- [ ] AI content optimization suggestions
- [ ] AI-powered internal link suggestions
- [ ] AI competitor analysis
- [ ] AI keyword research assistant
- [ ] AI content brief generator

### 5.3 Reporting & Analytics
- [ ] Custom SEO dashboards
- [ ] Scheduled email reports
- [ ] White-label report generation
- [ ] Client reporting mode
- [ ] API access for external tools

---

## Bug Fixes & Technical Debt

### Known Issues
- [ ] SEO score not updating in real-time in some cases
- [ ] Import from Yoast may miss some custom fields
- [ ] Sitemap may timeout on very large sites (>50k posts)
- [ ] LLM.txt generation can be slow on large sites

### Technical Improvements
- [ ] Add comprehensive unit tests
- [ ] Add integration tests for REST API
- [ ] Performance optimization for large sites
- [ ] Database query optimization
- [ ] Lazy load admin components
- [ ] Reduce main bundle size (currently 294 KB)
- [ ] Add proper error boundaries in React
- [ ] Implement proper caching strategy

---

## Version Planning

| Version | Focus Area | Target Features |
|---------|------------|-----------------|
| 0.2.0 | AI Integration | WP AI Pilot integration complete |
| 0.3.0 | Core Polish | Enhanced scoring, content analysis |
| 0.4.0 | Schema | FAQ, How-To, Product schemas |
| 0.5.0 | Breadcrumbs | Full breadcrumb support |
| 0.6.0 | Integrations | Google Search Console |
| 0.7.0 | WooCommerce | Product SEO support |
| 0.8.0 | Bulk Tools | Bulk editing, CSV import/export |
| 0.9.0 | Audits | Site-wide SEO audit system |
| 1.0.0 | Stable | Production-ready release |

---

## Notes

- All AI features depend on WP AI Pilot being installed and configured
- WooCommerce integration only loads when WooCommerce is active
- Google integrations require OAuth setup in Google Cloud Console
- Enterprise features may be gated behind a pro version

---

*Last updated: January 2026*
