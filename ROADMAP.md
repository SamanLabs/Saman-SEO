# Saman SEO - Development Roadmap

> Last Updated: October 2026
> Current Version: 2.0.32

---

## Recently Done

- [x] Restored admin JSX deleted by the earlier i18n codemod (codemod fixed)
- [x] Security hardening: redirects, .htaccess editing, link cache, noindex handling
- [x] Usage analytics is now strictly opt-in (existing installs switched off once)
- [x] Schema: placeholder data removed; post schema output on singular views only
- [x] WordPress 7.1 compatibility: block editor, admin toolbar, PHP 8.5 updates
- [x] Google 2026 updates: FAQ/HowTo rich-result copy, llms.txt path, AI crawler robots presets, per-post snippet controls
- [x] 404 log and redirect lookup performance improvements

---

## Vision

Build AI-powered SEO assistants that feel like helpful teammates, not robots. Create a foundation that can eventually become a standalone AI Assistant plugin powering all Pilot ecosystem tools.

**Core Philosophy:**
- AI that sounds human, not like ChatGPT
- Little helpers that do specific jobs well
- Reports you'd actually want to read
- React-powered interactive experiences

---

## Migration Status

### What's Done
- [x] Dashboard with real data
- [x] Settings page
- [x] Search Appearance
- [x] Sitemap & LLM.txt
- [x] Redirects & 404 Log
- [x] Internal Linking
- [x] AI Assistant (basic)
- [x] SEO Audit
- [x] Editor Sidebar (Gutenberg)
- [x] Tools discovery page

### Migration Approach (Simple)
Since it's internal websites only:
- [ ] Swap menu links from V1 to V2
- [ ] Map URLs: `?page=samanseo-*` → `?page=samanseo-v2-*`
- [ ] Remove V1 menu items
- [ ] Done. No migration wizard needed.

---

## Phase 1: Foundation (COMPLETE)

### 1.1 Setup Wizard
First-time experience when plugin activates.

```
Step 1: Welcome
├── "Let's get your SEO started"
├── Quick intro (30 seconds)
└── Skip option for power users

Step 2: Site Info
├── Site type (blog, business, ecommerce, portfolio)
├── Primary goal (traffic, leads, sales, brand)
└── Industry/niche

Step 3: Connect AI
├── Choose provider (OpenAI, local, skip)
├── API key input
├── Test connection
└── "You can do this later"

Step 4: Quick Wins
├── Enable sitemap? [Yes]
├── Enable 404 logging? [Yes]
├── Enable redirects? [Yes]
└── Basic title template

Step 5: Done
├── "You're all set!"
├── Go to Dashboard
└── Meet your AI Assistant
```

**Files created:**
- [x] `src-v2/pages/Setup.js` - Setup wizard with 5 steps
- [x] `src-v2/less/pages/_setup.less` - Setup wizard styles
- [x] `includes/Api/class-setup-controller.php` - Save setup data
- [x] Track `samanseo_setup_completed` option
- [x] Reset wizard button in Settings > Tools

### 1.2 Matomo Analytics (Opt-in)

Track plugin usage for improvements. **100% opt-in, privacy-first.**

```
What we track (if opted in):
├── Plugin version
├── WordPress version
├── PHP version
├── Active features (which modules enabled)
├── Feature usage (which tools used)
├── Error logs (sanitized, no personal data)
└── Performance metrics

What we NEVER track:
├── Content
├── URLs
├── User data
├── API keys
├── Personal info
```

**Implementation:**
- [ ] Add Matomo tracking code (only when opted in)
- [ ] Settings toggle: "Help improve Saman SEO"
- [ ] Privacy-first: disabled by default
- [ ] Clear explanation of what's tracked
- [ ] One-click disable anytime

**Files:**
- [ ] `includes/class-samanseo-service-analytics.php` (update existing)
- [ ] Add opt-in UI to Setup wizard and Settings

---

## Phase 2: AI Assistants Platform (COMPLETE)

This is the core innovation. Build a system of specialized AI assistants.

### 2.1 Assistant Architecture

**Frontend (Implemented):**
```
src-v2/
├── assistants/
│   ├── AssistantProvider.js      # [x] Context for all assistants
│   ├── AssistantChat.js          # [x] Reusable chat interface
│   ├── AssistantMessage.js       # [x] Message bubble component
│   ├── AssistantTyping.js        # [x] Typing indicator
│   ├── index.js                  # [x] Exports
│   └── agents/
│       ├── GeneralSEO.js         # [x] General SEO assistant
│       ├── SEOReporter.js        # [x] Weekly SEO reports
│       ├── index.js              # [x] Agent registry
│       ├── ContentAuditor.js     # [ ] Content analysis
│       ├── KeywordScout.js       # [ ] Keyword research
│       ├── LinkDoctor.js         # [ ] Link health checker
│       └── CompetitorSpy.js      # [ ] Competitor insights
├── pages/
│   └── Assistants.js             # [x] Management view with create + stats
```

**Backend (Implemented):**
```
includes/Api/
├── class-assistants-controller.php   # [x] Full CRUD + usage tracking
├── Assistants/
│   ├── class-base-assistant.php      # [x] Base class
│   ├── class-general-seo-assistant.php  # [x] General SEO logic
│   ├── class-seo-reporter-assistant.php # [x] Reporter logic
│   ├── class-content-auditor.php     # [ ] Auditor logic
│   └── ...
```

**Database Tables:**
- [x] `wp_samanseo_custom_assistants` - Custom assistants storage
- [x] `wp_samanseo_assistant_usage` - Usage tracking

**Features Completed:**
- [x] Built-in assistants (General SEO, SEO Reporter)
- [x] Custom assistants CRUD (create, edit, delete)
- [x] Usage statistics tracking
- [x] Icon and color picker for custom assistants
- [x] Chat interface with message history
- [x] Action buttons in responses
- [x] Tools page AI Assistants section

**Styling:**
- [x] `src-v2/less/pages/_assistants.less` - Full management + chat styles

### 2.2 The Assistants

#### SEO Reporter (Priority: High)
Your weekly SEO buddy that gives you the rundown.

```
Personality:
├── Friendly, casual tone
├── Uses simple language
├── Celebrates wins
├── Prioritizes what matters
└── Never sounds robotic

Example output:
"Hey! Here's what happened this week:

📈 Good news first - your 'Best Coffee Makers' post
   is climbing. It jumped from page 3 to page 2.
   Keep building links to it.

⚠️ Heads up - 3 pages lost their descriptions somehow.
   Probably that theme update. I can fix them if you want.

🔗 Found 2 broken links on your About page.
   One's an old Twitter link (they're X now, remember?).

Want me to fix the descriptions? [Fix them] [Show me first]"
```

**Features:**
- [ ] Weekly digest generation
- [ ] Score changes tracking
- [ ] Issue detection
- [ ] Actionable recommendations
- [ ] One-click fixes
- [ ] Scheduled email reports (optional)

#### Content Auditor
Analyzes your content like a helpful editor.

```
Example output:
"I looked at your 'How to Make Cold Brew' post.

The good:
- Great length (1,847 words)
- You're using your keyword naturally
- Nice internal links to your coffee gear posts

Could be better:
- Your intro is 4 paragraphs before you get to the point.
  People bounce. Maybe cut to 'Here's how:' faster?
- No images in the first 500 words. Add that hero shot.
- You mention 'french press' but never link to your
  french press guide. Want me to add it?

Overall: 7/10 - solid post, small tweaks needed."
```

#### Keyword Scout
Finds keyword opportunities in plain English.

```
Example output:
"I dug around for keywords related to 'cold brew coffee'.

Easy wins (low competition, decent volume):
├── 'cold brew ratio' - 2,400/mo - you could rank fast
├── 'cold brew vs iced coffee' - 5,100/mo - comparison post?
└── 'how long cold brew lasts' - 1,900/mo - FAQ content

Your competitors rank for these, you don't:
├── 'cold brew concentrate' - 3,600/mo
└── 'cold brew maker' - 8,100/mo (product roundup opportunity)

Want me to draft an outline for any of these?"
```

#### Link Doctor
Keeps your links healthy.

```
Example output:
"Did a checkup on your links. Here's the diagnosis:

🔴 Critical (fix now):
├── 3 broken external links (sites went down)
└── 1 redirect chain (3 hops - slow)

🟡 Worth fixing:
├── 12 links to HTTP (should be HTTPS)
└── 5 orphan pages (no internal links pointing to them)

🟢 Healthy:
├── 847 internal links working
└── 234 external links valid

[Fix critical issues] [Show me details]"
```

#### Competitor Spy
Keeps tabs on competitors (ethically).

```
Example output:
"Checked on your competitors this week.

CoffeeGeek.com published 3 new posts:
├── 'Best Espresso Machines 2025' - they beat you to it
├── 'Latte Art Tutorial' - 47 comments already
└── 'Coffee Subscription Review' - affiliate play

HomeBarista.net is ranking above you for:
├── 'burr grinder' - they're #3, you're #7
└── 'coffee scale' - they're #5, you're #12

Opportunity: Neither has content about 'coffee to water ratio'.
You could own that keyword. Want an outline?"
```

### 2.3 Assistant Chat Interface

A beautiful, React-powered chat experience.

```jsx
// Example usage
<AssistantChat
  agent="seo-reporter"
  initialMessage="Hey! Want your weekly SEO update?"
  actions={[
    { label: "Yes, show me", action: "generate_report" },
    { label: "Just the highlights", action: "generate_summary" },
  ]}
/>
```

**UI Features:**
- [ ] Smooth message animations
- [ ] Typing indicator with personality
- [ ] Quick action buttons
- [ ] Code/data blocks for technical info
- [ ] Collapsible detailed sections
- [ ] Copy to clipboard
- [ ] Share report (generate link)

### 2.4 Prompt Engineering

Make AI sound human, not robotic.

```php
// System prompts that work
$system_prompt = "You are a helpful SEO assistant for a WordPress site.

IMPORTANT RULES:
- Write like a friendly coworker, not a robot
- Never start with 'Certainly!' or 'Of course!'
- Never say 'I hope this helps'
- Use contractions (you're, it's, don't)
- Be specific, not generic
- If something is wrong, say it plainly
- Celebrate wins genuinely
- Keep it brief unless asked for details
- Use emoji sparingly (1-2 per message max)
- Never use corporate speak

BAD: 'I'd be happy to help you optimize your meta descriptions!'
GOOD: 'Your meta descriptions need work. Here's what I found.'

BAD: 'Here are some suggestions for improvement.'
GOOD: 'Three things to fix: [specific list]'";
```

---

## Phase 3: Tools (AI-Powered)

### 3.1 Smart Bulk Editor

Not just a spreadsheet - an AI-assisted editor.

```
Features:
├── Spreadsheet view of all posts
├── AI suggestions column
├── "Fix all" with one click
├── Before/after preview
├── Undo support
└── Progress indicator

AI integration:
├── "Generate missing titles" button
├── "Improve these descriptions" button
├── Suggestions appear inline
└── Accept/reject per item
```

### 3.2 Content Gaps Finder

AI finds what you should write about.

```
Features:
├── Analyze existing content
├── Find missing topics
├── Suggest content clusters
├── Priority scoring
└── Draft outlines

Output:
"Based on your coffee content, you're missing:
1. Beginner's guide (you jump to advanced topics)
2. Equipment comparisons (people search these)
3. Troubleshooting content (common problems)

Recommended next post: 'Coffee Troubleshooting Guide'
[Generate outline]"
```

### 3.3 Schema Builder (Visual)

Drag-and-drop schema creation with AI assistance.

```
Features:
├── Visual schema builder
├── AI auto-detection
├── Preview in Google
├── Validation
└── One-click apply

AI: "This looks like a recipe post.
     Want me to generate Recipe schema?"
```

---

## Phase 4: Reports & Insights

### 4.1 Scheduled Reports

Automated reports that actually get read.

```
Report types:
├── Weekly SEO Summary (email)
├── Monthly Performance (PDF)
├── Content Calendar Suggestions
└── Competitor Movement Alerts

Delivery:
├── Email
├── Dashboard notification
├── Slack webhook (future)
└── PDF download
```

### 4.2 Dashboard Widgets

Quick insights on the dashboard.

```
Widgets:
├── This Week's Wins (positive changes)
├── Needs Attention (issues to fix)
├── Quick Actions (one-click fixes)
├── AI Suggestion of the Day
└── Competitor Alert (if configured)
```

---

## Phase 5: Standalone Preparation

Prepare the AI Assistant system to become its own plugin.

### 5.1 Modular Architecture

```
wp-pilot-ai/ (future standalone)
├── Core assistant engine
├── Provider connections
├── Prompt management
├── Chat interface
└── API for other plugins

wp-seo-pilot/
├── Uses wp-pilot-ai
├── SEO-specific assistants
└── SEO-specific actions

wp-security-pilot/ (future)
├── Uses wp-pilot-ai
├── Security-specific assistants
└── Security-specific actions
```

### 5.2 Plugin Communication

```php
// How plugins will request AI
do_action('pilot_ai_request', [
    'assistant' => 'seo-reporter',
    'action' => 'weekly_report',
    'context' => $site_data,
    'callback' => 'my_callback_function'
]);

// How plugins register assistants
add_filter('pilot_ai_assistants', function($assistants) {
    $assistants['my-custom-assistant'] = [
        'name' => 'My Helper',
        'class' => 'My_Assistant_Class',
        'prompts' => [...],
    ];
    return $assistants;
});
```

---

## Implementation Priority

### Completed
1. [x] Setup Wizard (full 5-step version)
2. [x] SEO Reporter assistant
3. [x] General SEO assistant
4. [x] Chat interface component
5. [x] Custom assistants management with CRUD
6. [x] Usage statistics tracking
7. [x] Reset wizard in Settings

### Next (This Month)
1. [ ] Content Auditor assistant
2. [ ] Matomo analytics opt-in
3. [ ] Dashboard widgets
4. [ ] Weekly email reports

### Soon (Next 2-3 Months)
1. [ ] Keyword Scout assistant
2. [ ] Link Doctor assistant
3. [ ] Bulk Editor with AI
4. [ ] Schema Builder

### Later (3-6 Months)
1. [ ] Competitor Spy assistant
2. [ ] Content Gaps Finder
3. [ ] Standalone plugin extraction
4. [ ] More report types

---

## Technical Notes

### Assistant API Structure

```php
// POST /samanseo/v2/assistants/chat
{
    "assistant": "seo-reporter",
    "message": "Give me this week's report",
    "context": {
        "post_id": null, // or specific post
        "date_range": "week"
    }
}

// Response
{
    "success": true,
    "data": {
        "message": "Hey! Here's what happened...",
        "actions": [
            {"label": "Fix issues", "action": "fix_all"},
            {"label": "More details", "action": "expand"}
        ],
        "data": { /* structured data for UI */ }
    }
}
```

### React Components

```jsx
// Main assistant page
<AssistantsPage>
  <AssistantSelector agents={['reporter', 'auditor', 'scout']} />
  <AssistantChat agent={selectedAgent} />
  <AssistantActions />
</AssistantsPage>

// Embeddable chat widget
<AssistantWidget
  agent="content-auditor"
  context={{ postId: 123 }}
  position="bottom-right"
/>
```

---

## Success Metrics

- Users actually use the assistants (track in Matomo)
- Reports get opened (email tracking)
- Actions get taken (fix buttons clicked)
- Time saved (before/after surveys)
- Plugin becomes indispensable

---

## Notes

- Keep it simple, ship fast
- One assistant at a time
- Make it delightful
- Sound human always
- Build for extraction later

---

*"Little helpers that do specific jobs well."*
