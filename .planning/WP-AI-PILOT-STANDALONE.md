# WP AI Pilot - Standalone Plugin Game Plan

## Overview

Extract the AI infrastructure from WP SEO Pilot into a standalone plugin that any WordPress plugin can use. This creates a centralized AI management system with usage tracking, multiple provider support, and reusable assistant components.

---

## What Already Exists (Built in WP SEO Pilot)

### Backend Infrastructure

#### 1. AI Controller (`includes/Api/class-ai-controller.php`)
Already implemented:
- **Multi-Provider Support**
  - OpenAI (GPT-4o, GPT-4o-mini, GPT-3.5)
  - Anthropic (Claude)
  - Google AI (Gemini)
  - Ollama (local models)
  - LM Studio (local models)
  - OpenAI-Compatible APIs (any endpoint)

- **Custom Models System**
  - Database table: `wp_wpseopilot_custom_models`
  - CRUD operations for custom model configurations
  - Per-model settings: temperature, max_tokens, API URL, API key
  - Model testing endpoint

- **Models Database Integration**
  - Syncs with models.dev API
  - Caches model catalog locally
  - Search/filter available models
  - Auto-sync every 2 weeks

- **REST API Endpoints**
  ```
  GET/POST  /wpseopilot/v2/ai/settings        - AI settings
  GET       /wpseopilot/v2/ai/models          - Available models list
  POST      /wpseopilot/v2/ai/generate        - Generate content
  GET       /wpseopilot/v2/ai/status          - API status check
  POST      /wpseopilot/v2/ai/reset           - Reset to defaults

  GET/POST  /wpseopilot/v2/ai/custom-models   - Custom models CRUD
  GET/PUT/DELETE /wpseopilot/v2/ai/custom-models/{id}
  POST      /wpseopilot/v2/ai/custom-models/{id}/test

  GET       /wpseopilot/v2/ai/providers       - Available providers
  GET       /wpseopilot/v2/ai/models-database - Models catalog
  POST      /wpseopilot/v2/ai/models-database/sync
  GET       /wpseopilot/v2/ai/models-database/search
  ```

#### 2. Assistants Controller (`includes/Api/class-assistants-controller.php`)
Already implemented:
- Chat endpoint for assistants
- Message routing to appropriate assistant
- Context passing (post_id, actions)
- Response with optional action buttons

- **REST API Endpoints**
  ```
  POST /wpseopilot/v2/assistants/chat
  ```

#### 3. Base Assistant Class (`includes/Api/Assistants/class-base-assistant.php`)
Already implemented:
- Abstract base for all assistants
- `get_id()`, `get_name()`, `get_system_prompt()`
- `process_message()` using AI controller
- `get_context()` for gathering relevant data

#### 4. Example Assistants
- `class-general-seo-assistant.php` - General SEO help
- `class-seo-reporter-assistant.php` - Weekly reports with actions

### Frontend Infrastructure

#### 1. AI Settings Page (`src-v2/pages/AiAssistant.js`)
Already implemented:
- API key configuration
- Model selection (built-in + custom)
- Custom prompts for title/description generation
- Provider selection
- Test generation functionality

#### 2. Custom Models Management (`src-v2/pages/AiAssistant.js`)
Already implemented:
- Add/Edit/Delete custom models
- Model configuration form (provider, API URL, API key, etc.)
- Connection testing
- Models database browser with search
- Import from models.dev catalog

#### 3. Chat Components (`src-v2/assistants/`)
Already implemented:
- `AssistantProvider.js` - React Context for state
- `AssistantChat.js` - Main chat interface
- `AssistantMessage.js` - Message bubbles with markdown
- `AssistantTyping.js` - Typing indicator

#### 4. AI Generate Modal (`src-v2/components/AiGenerateModal.js`)
Already implemented:
- Reusable modal for AI generation
- Context inclusion toggle
- Custom prompt support
- Result preview and apply

---

## What Needs to Be Built for Standalone Plugin

### Phase 1: Core Plugin Setup

#### 1.1 Plugin Structure
```
wp-ai-pilot/
├── wp-ai-pilot.php              # Main plugin file
├── includes/
│   ├── class-wp-ai-pilot.php    # Main plugin class
│   ├── class-activator.php      # Activation hooks
│   ├── class-deactivator.php    # Deactivation hooks
│   ├── Api/
│   │   ├── class-rest-controller.php
│   │   ├── class-ai-controller.php
│   │   ├── class-usage-controller.php    # NEW
│   │   ├── class-assistants-controller.php
│   │   └── Assistants/
│   │       ├── class-base-assistant.php
│   │       └── class-assistant-registry.php  # NEW
│   ├── Admin/
│   │   ├── class-admin.php
│   │   └── class-settings.php
│   └── Integrations/
│       └── class-integration-api.php    # NEW - Public API for other plugins
├── src/
│   ├── index.js
│   ├── pages/
│   │   ├── Dashboard.js         # Usage overview
│   │   ├── Models.js            # Model management
│   │   ├── Assistants.js        # Assistant hub
│   │   ├── Usage.js             # Detailed usage stats
│   │   └── Settings.js          # Plugin settings
│   ├── components/
│   │   ├── AiChat.js
│   │   ├── AiGenerateModal.js
│   │   ├── UsageChart.js        # NEW
│   │   └── ModelCard.js
│   └── assistants/
│       ├── AssistantProvider.js
│       ├── AssistantChat.js
│       └── AssistantMessage.js
├── assets/
│   ├── css/
│   └── js/
└── languages/
```

#### 1.2 Database Tables
```sql
-- Usage tracking table
CREATE TABLE {prefix}_wpaipilot_usage (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    model_id varchar(100) NOT NULL,
    provider varchar(50) NOT NULL,
    tokens_input int(11) NOT NULL DEFAULT 0,
    tokens_output int(11) NOT NULL DEFAULT 0,
    tokens_total int(11) NOT NULL DEFAULT 0,
    cost_estimate decimal(10,6) DEFAULT 0,
    request_type varchar(50) NOT NULL,      -- 'chat', 'generate', 'assistant'
    source_plugin varchar(100) DEFAULT NULL, -- Which plugin made the request
    assistant_id varchar(100) DEFAULT NULL,
    user_id bigint(20) unsigned NOT NULL,
    created_at datetime NOT NULL,
    metadata longtext,                       -- JSON for extra data
    PRIMARY KEY (id),
    KEY model_id (model_id),
    KEY provider (provider),
    KEY source_plugin (source_plugin),
    KEY user_id (user_id),
    KEY created_at (created_at)
);

-- Custom models table (already exists, migrate)
CREATE TABLE {prefix}_wpaipilot_models (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    name varchar(255) NOT NULL,
    provider varchar(100) NOT NULL,
    model_id varchar(255) NOT NULL,
    api_url varchar(500) DEFAULT '',
    api_key varchar(500) DEFAULT '',
    temperature decimal(3,2) DEFAULT 0.30,
    max_tokens int(11) DEFAULT 1000,
    is_active tinyint(1) DEFAULT 1,
    is_default tinyint(1) DEFAULT 0,
    extra_config longtext,
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id)
);

-- Registered assistants from other plugins
CREATE TABLE {prefix}_wpaipilot_assistants (
    id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
    assistant_id varchar(100) NOT NULL UNIQUE,
    name varchar(255) NOT NULL,
    description text,
    system_prompt text NOT NULL,
    source_plugin varchar(100) NOT NULL,
    icon varchar(500) DEFAULT NULL,
    is_active tinyint(1) DEFAULT 1,
    config longtext,                         -- JSON for extra settings
    created_at datetime NOT NULL,
    updated_at datetime NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY assistant_id (assistant_id)
);
```

### Phase 2: Usage Tracking System

#### 2.1 Usage Controller (`class-usage-controller.php`)
```php
class Usage_Controller extends REST_Controller {

    // Log usage after each AI call
    public function log_usage( $data ) {
        // $data includes: model, provider, tokens, request_type, source_plugin, etc.
    }

    // REST Endpoints
    public function register_routes() {
        // GET /wp-ai-pilot/v1/usage              - Get usage summary
        // GET /wp-ai-pilot/v1/usage/daily        - Daily breakdown
        // GET /wp-ai-pilot/v1/usage/by-model     - Usage by model
        // GET /wp-ai-pilot/v1/usage/by-plugin    - Usage by source plugin
        // GET /wp-ai-pilot/v1/usage/by-assistant - Usage by assistant
        // GET /wp-ai-pilot/v1/usage/costs        - Cost estimates
        // DELETE /wp-ai-pilot/v1/usage/clear     - Clear old usage data
    }

    // Get usage summary
    public function get_summary( $request ) {
        // Returns: total requests, total tokens, costs, top models, etc.
    }

    // Get daily usage for charts
    public function get_daily( $request ) {
        // Parameters: start_date, end_date, group_by
    }
}
```

#### 2.2 Cost Estimation
```php
// Pricing data (per 1M tokens, update periodically)
private $pricing = [
    'gpt-4o' => ['input' => 2.50, 'output' => 10.00],
    'gpt-4o-mini' => ['input' => 0.15, 'output' => 0.60],
    'gpt-4-turbo' => ['input' => 10.00, 'output' => 30.00],
    'claude-3-opus' => ['input' => 15.00, 'output' => 75.00],
    'claude-3-sonnet' => ['input' => 3.00, 'output' => 15.00],
    'claude-3-haiku' => ['input' => 0.25, 'output' => 1.25],
    // Local models = $0
    'ollama/*' => ['input' => 0, 'output' => 0],
    'lmstudio/*' => ['input' => 0, 'output' => 0],
];
```

#### 2.3 Usage Dashboard Component
```jsx
// src/pages/Usage.js
const Usage = () => {
    // Show:
    // - Total requests this period
    // - Total tokens used
    // - Estimated costs
    // - Usage chart (line/bar)
    // - Breakdown by model
    // - Breakdown by plugin
    // - Breakdown by assistant
    // - Export to CSV
};
```

### Phase 3: Public Integration API

#### 3.1 Integration API Class (`class-integration-api.php`)
```php
/**
 * Public API for other plugins to use WP AI Pilot
 *
 * Usage:
 * $ai = wp_ai_pilot();
 * $response = $ai->generate('Write a product description', ['max_tokens' => 200]);
 */
class Integration_API {

    /**
     * Simple text generation
     */
    public function generate( $prompt, $options = [] ) {
        // Options: model, max_tokens, temperature, system_prompt
        // Returns: string response
    }

    /**
     * Chat completion (with message history)
     */
    public function chat( $messages, $options = [] ) {
        // $messages = [['role' => 'user', 'content' => '...'], ...]
        // Returns: assistant message
    }

    /**
     * Register a custom assistant from another plugin
     */
    public function register_assistant( $config ) {
        // $config = [
        //     'id' => 'my-plugin-assistant',
        //     'name' => 'My Assistant',
        //     'description' => 'Helps with...',
        //     'system_prompt' => '...',
        //     'icon' => 'dashicons-admin-generic',
        //     'plugin' => 'my-plugin/my-plugin.php',
        // ]
    }

    /**
     * Unregister an assistant
     */
    public function unregister_assistant( $assistant_id ) {}

    /**
     * Get available models
     */
    public function get_models() {
        // Returns array of available models
    }

    /**
     * Get default model
     */
    public function get_default_model() {}

    /**
     * Check if AI is configured
     */
    public function is_configured() {
        // Returns true if at least one provider is set up
    }

    /**
     * Get usage stats for a plugin
     */
    public function get_usage( $plugin_slug, $period = '30days' ) {}
}

/**
 * Global helper function
 */
function wp_ai_pilot() {
    return WP_AI_Pilot::get_instance()->api();
}
```

#### 3.2 WordPress Hooks for Integration
```php
// Filters
apply_filters( 'wp_ai_pilot_before_request', $request_data );
apply_filters( 'wp_ai_pilot_after_response', $response, $request_data );
apply_filters( 'wp_ai_pilot_system_prompt', $prompt, $context );
apply_filters( 'wp_ai_pilot_models', $models );

// Actions
do_action( 'wp_ai_pilot_request_complete', $response, $usage_data );
do_action( 'wp_ai_pilot_assistant_registered', $assistant_id, $config );
do_action( 'wp_ai_pilot_usage_logged', $usage_record );
```

#### 3.3 Example: How WP SEO Pilot Would Use It
```php
// In WP SEO Pilot plugin

// Check if WP AI Pilot is available
if ( function_exists( 'wp_ai_pilot' ) ) {

    // Register SEO assistants
    add_action( 'init', function() {
        wp_ai_pilot()->register_assistant([
            'id' => 'seo-general',
            'name' => 'SEO Assistant',
            'description' => 'Get help with SEO tasks',
            'system_prompt' => 'You are an SEO expert...',
            'icon' => 'dashicons-chart-line',
            'plugin' => 'wp-seo-pilot/wp-seo-pilot.php',
        ]);

        wp_ai_pilot()->register_assistant([
            'id' => 'seo-reporter',
            'name' => 'SEO Reporter',
            'description' => 'Weekly SEO reports',
            'system_prompt' => 'You generate SEO reports...',
            'icon' => 'dashicons-analytics',
            'plugin' => 'wp-seo-pilot/wp-seo-pilot.php',
        ]);
    });

    // Generate meta description
    $description = wp_ai_pilot()->generate(
        "Write a meta description for: {$post_title}",
        [
            'max_tokens' => 160,
            'source' => 'wp-seo-pilot',
        ]
    );
}
```

### Phase 4: Admin Interface

#### 4.1 Menu Structure
```
WP AI Pilot
├── Dashboard        - Overview, quick stats, recent activity
├── Models           - Manage AI models (built-in + custom)
├── Assistants       - View all registered assistants
├── Usage            - Detailed usage analytics
└── Settings         - API keys, defaults, preferences
```

#### 4.2 Dashboard Page
- Total requests (today/week/month)
- Token usage chart
- Cost estimate
- Top models used
- Active assistants count
- Quick actions (test API, add model)
- Recent activity feed

#### 4.3 Models Page
- Built-in models list
- Custom models management
- Models database browser
- Import from catalog
- Set default model

#### 4.4 Assistants Page
- List all registered assistants
- Show source plugin for each
- Enable/disable assistants
- Test assistant chat
- View assistant usage

#### 4.5 Usage Page
- Date range selector
- Usage charts (tokens, requests, costs)
- Breakdown by:
  - Model
  - Provider
  - Plugin
  - Assistant
  - User
- Export functionality

#### 4.6 Settings Page
- Default provider selection
- API keys for each provider
- Default model settings
- Usage tracking toggle
- Data retention settings
- Rate limiting options

---

## Migration Plan from WP SEO Pilot

### Step 1: Extract Core Files
1. Copy AI controller to new plugin
2. Copy assistants system
3. Copy custom models CRUD
4. Copy models database sync

### Step 2: Update Database
1. Create new tables with `wpaipilot_` prefix
2. Migration script for existing custom models
3. Add usage tracking table

### Step 3: Update WP SEO Pilot
1. Check for WP AI Pilot dependency
2. Use `wp_ai_pilot()` API instead of direct calls
3. Register SEO assistants via API
4. Remove duplicated AI code

### Step 4: Build New UI
1. Create standalone React app
2. Build dashboard, models, usage pages
3. Add assistant hub
4. Implement usage charts

---

## API Reference (For Other Plugin Developers)

### Basic Usage
```php
// Check availability
if ( ! function_exists( 'wp_ai_pilot' ) ) {
    // WP AI Pilot not installed
    return;
}

$ai = wp_ai_pilot();

// Check if configured
if ( ! $ai->is_configured() ) {
    // No AI provider configured
    return;
}
```

### Text Generation
```php
// Simple generation
$result = $ai->generate( 'Write a tagline for a coffee shop' );

// With options
$result = $ai->generate( 'Write a tagline for a coffee shop', [
    'model' => 'gpt-4o-mini',
    'max_tokens' => 50,
    'temperature' => 0.8,
    'system_prompt' => 'You are a creative copywriter.',
]);
```

### Chat Completion
```php
$response = $ai->chat([
    ['role' => 'system', 'content' => 'You are a helpful assistant.'],
    ['role' => 'user', 'content' => 'What is WordPress?'],
    ['role' => 'assistant', 'content' => 'WordPress is...'],
    ['role' => 'user', 'content' => 'How do I install plugins?'],
]);
```

### Register Assistant
```php
add_action( 'init', function() {
    if ( ! function_exists( 'wp_ai_pilot' ) ) return;

    wp_ai_pilot()->register_assistant([
        'id' => 'my-assistant',
        'name' => 'My Custom Assistant',
        'description' => 'Helps users with my plugin features',
        'system_prompt' => 'You are an expert in...',
        'icon' => 'dashicons-admin-generic', // or URL
        'plugin' => 'my-plugin/my-plugin.php',
        'suggested_prompts' => [
            'How do I get started?',
            'What features are available?',
        ],
    ]);
});
```

### Get Usage Stats
```php
$usage = $ai->get_usage( 'my-plugin', '30days' );
// Returns: [
//     'total_requests' => 150,
//     'total_tokens' => 45000,
//     'estimated_cost' => 0.45,
//     'by_model' => [...],
// ]
```

---

## Estimated Development Timeline

| Phase | Tasks | Effort |
|-------|-------|--------|
| Phase 1 | Plugin setup, extract existing code | 2-3 days |
| Phase 2 | Usage tracking system | 2-3 days |
| Phase 3 | Public integration API | 2-3 days |
| Phase 4 | Admin interface | 4-5 days |
| Testing | Integration testing, bug fixes | 2-3 days |
| **Total** | | **12-17 days** |

---

## Future Enhancements

1. **Rate Limiting** - Per-user, per-plugin limits
2. **Caching** - Cache common responses
3. **Streaming** - Stream responses for long generations
4. **Image Generation** - DALL-E, Midjourney API support
5. **Embeddings** - Vector search for semantic queries
6. **Fine-tuning** - Support for fine-tuned models
7. **Webhooks** - Notify on usage thresholds
8. **Multi-site** - Network-wide settings and usage

---

## File Locations (Current WP SEO Pilot)

Files to extract/reference:
```
includes/Api/class-ai-controller.php          # Main AI logic
includes/Api/class-assistants-controller.php  # Assistants routing
includes/Api/Assistants/class-base-assistant.php
includes/Api/Assistants/class-general-seo-assistant.php
includes/Api/Assistants/class-seo-reporter-assistant.php

src-v2/pages/AiAssistant.js                   # Settings UI
src-v2/components/AiGenerateModal.js          # Generate modal
src-v2/assistants/AssistantProvider.js        # Chat context
src-v2/assistants/AssistantChat.js            # Chat component
src-v2/assistants/AssistantMessage.js         # Message bubble
src-v2/assistants/AssistantTyping.js          # Typing indicator
src-v2/assistants/agents/GeneralSEO.js        # Assistant config
src-v2/assistants/agents/SEOReporter.js       # Assistant config

src-v2/less/pages/_ai-assistant.less          # AI settings styles
src-v2/less/pages/_assistants.less            # Chat styles
src-v2/less/components/_ai-generate-modal.less
```
