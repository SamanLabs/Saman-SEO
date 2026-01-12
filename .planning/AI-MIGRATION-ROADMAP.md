# WP SEO Pilot → WP AI Pilot Migration Roadmap

## Executive Summary

This document outlines the comprehensive migration plan to move all AI functionality from WP SEO Pilot to WP AI Pilot, establishing WP AI Pilot as the centralized AI hub for the Pilot ecosystem.

### Current State

**WP SEO Pilot has:**
- Duplicate AI provider logic (OpenAI, Anthropic, Google, Ollama, LM Studio)
- Custom models management (`wpseopilot_custom_models` table)
- Built-in assistants (General SEO, SEO Reporter)
- Custom assistants system (`wpseopilot_custom_assistants` table)
- Usage tracking (`wpseopilot_assistant_usage` table)
- ~1300 lines of duplicate AI API calling code

**WP AI Pilot has:**
- Centralized AI Engine with all providers
- Public API for external plugins (`wp_ai_pilot()`)
- Assistant Registry for cross-plugin assistants
- Plugin Registry for usage tracking
- Conversation history system
- Unified models management

### Target State

**WP AI Pilot becomes the single source of truth for:**
- All AI providers and API credentials
- All AI models (built-in and custom)
- All assistants (from all Pilot plugins)
- Usage tracking across the ecosystem
- Conversation history

**WP SEO Pilot retains only:**
- SEO-specific system prompts (the "personality" of SEO assistants)
- Reference IDs to assistants registered in WP AI Pilot
- Thin integration layer that delegates to WP AI Pilot
- SEO-specific UI components

---

## Phase 1: Foundation - Use WP AI Pilot for All AI Operations

**Goal:** Stop using native API calls, always use WP AI Pilot when available.

### 1.1 Update AI Controller Generate Method

**File:** `includes/Api/class-ai-controller.php`

**Current:** The `generate()` method checks for WP AI Pilot but also has fallback logic with 200+ lines of provider code.

**Change:** Make WP AI Pilot the primary path, show clear error if not available.

```php
// BEFORE (line 395-451)
public function generate( $request ) {
    // ... lots of fallback logic
    if ( AI_Pilot::is_ready() ) {
        return $this->generate_via_ai_pilot( $content, $type );
    }
    // ... 100+ lines of OpenAI/custom model calling
}

// AFTER
public function generate( $request ) {
    $params = $request->get_json_params();
    $content = $params['content'] ?? '';
    $type = $params['type'] ?? 'both';

    if ( ! AI_Pilot::is_ready() ) {
        return $this->error(
            __( 'Please configure WP AI Pilot to use AI features.', 'wp-seo-pilot' ),
            'ai_not_configured',
            400
        );
    }

    return $this->generate_via_ai_pilot( $content, $type );
}
```

### 1.2 Update Status Endpoint

**Current:** Shows complex status with native API checks.

**Change:** Simply report WP AI Pilot status.

```php
public function get_status( $request ) {
    $status = AI_Pilot::get_status();

    return $this->success([
        'ready'     => $status['ready'],
        'provider'  => $status['ready'] ? 'wp-ai-pilot' : 'none',
        'version'   => $status['version'],
        'models'    => $status['ready'] ? count( AI_Pilot::get_models() ) : 0,
        'configure_url' => admin_url( 'admin.php?page=wp-ai-pilot' ),
    ]);
}
```

### 1.3 Deprecate Native Settings

**Current Settings to Deprecate:**
- `wpseopilot_openai_api_key`
- `wpseopilot_ai_model`
- `wpseopilot_ai_active_provider`
- `wpseopilot_anthropic_api_key`
- `wpseopilot_google_api_key`

**Migration Path:**
1. Add deprecation notice in Settings page
2. Show "Configure in WP AI Pilot" link
3. Keep reading old values for 2 versions (backward compatibility)
4. Remove in v1.0.0

### Tasks for Phase 1

- [ ] Update `generate()` to require WP AI Pilot
- [ ] Update `get_status()` to report WP AI Pilot status only
- [ ] Add deprecation notices to Settings page for API keys
- [ ] Update Settings.js to link to WP AI Pilot settings
- [ ] Test AI generation flows with WP AI Pilot only

**Estimated Scope:** ~150 lines changed

---

## Phase 2: Migrate Custom Models

**Goal:** Move custom models from SEO Pilot to AI Pilot.

### 2.1 Current Custom Models Architecture

**WP SEO Pilot:**
- Table: `wp_wpseopilot_custom_models`
- Fields: id, name, provider, model_id, api_url, api_key, temperature, max_tokens, is_active, extra_config

**WP AI Pilot:**
- Already has a similar system in the AI Engine
- Custom models can be registered programmatically

### 2.2 Migration Strategy

**Option A: One-time Migration (Recommended)**
1. On plugin update, check for existing custom models
2. Migrate each model to WP AI Pilot via API
3. Store mapping: `{old_id: new_ai_pilot_id}`
4. Update any references in SEO Pilot
5. Mark original table as deprecated

**Option B: Sync on Load**
- Don't migrate, just always fetch from WP AI Pilot
- Simpler but loses SEO Pilot independence

### 2.3 Implementation

**New Migration Class:**

```php
// includes/Migration/class-models-migrator.php

class Models_Migrator {

    public static function migrate_to_ai_pilot(): array {
        global $wpdb;

        if ( ! AI_Pilot::is_ready() ) {
            return ['success' => false, 'error' => 'WP AI Pilot not ready'];
        }

        $old_models = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpseopilot_custom_models",
            ARRAY_A
        );

        $migrated = [];
        $failed = [];

        foreach ( $old_models as $model ) {
            $result = wp_ai_pilot()->register_model([
                'name'        => $model['name'],
                'provider'    => $model['provider'],
                'model_id'    => $model['model_id'],
                'api_url'     => $model['api_url'],
                'api_key'     => $model['api_key'],
                'temperature' => $model['temperature'],
                'max_tokens'  => $model['max_tokens'],
                'source'      => 'wp-seo-pilot',
            ]);

            if ( ! is_wp_error( $result ) ) {
                $migrated[] = [
                    'old_id' => $model['id'],
                    'new_id' => $result['id'],
                ];
            } else {
                $failed[] = $model;
            }
        }

        // Store mapping
        update_option( 'wpseopilot_migrated_models', $migrated );

        return [
            'success'  => true,
            'migrated' => count( $migrated ),
            'failed'   => count( $failed ),
        ];
    }
}
```

### 2.4 Update Custom Models Endpoints

**Remove from SEO Pilot:**
- `GET /ai/custom-models`
- `POST /ai/custom-models`
- `PUT /ai/custom-models/{id}`
- `DELETE /ai/custom-models/{id}`
- `POST /ai/custom-models/{id}/test`

**Replace with:**
- Proxy to WP AI Pilot API, or
- Link to WP AI Pilot UI for model management

### Tasks for Phase 2

- [ ] Create `Models_Migrator` class
- [ ] Add migration trigger on plugin update
- [ ] Update `get_models()` to fetch from WP AI Pilot
- [ ] Add "Manage Models in WP AI Pilot" link in UI
- [ ] Deprecate custom models endpoints (return redirect notice)
- [ ] Update Settings.js CustomModels section to link to AI Pilot

**Estimated Scope:** ~200 lines new, ~500 lines deprecated

---

## Phase 3: Migrate Assistants

**Goal:** Register all assistants with WP AI Pilot, keep only references locally.

### 3.1 Current Assistant Architecture

**WP SEO Pilot has:**
```
includes/Api/Assistants/
├── class-base-assistant.php         # Abstract base class
├── class-general-seo-assistant.php  # Built-in: General SEO help
└── class-seo-reporter-assistant.php # Built-in: SEO analysis reports
```

Plus custom assistants in `wp_wpseopilot_custom_assistants` table.

### 3.2 Target Architecture

**WP AI Pilot manages:**
- All assistant definitions
- All assistant chat sessions
- Conversation history
- Usage tracking

**WP SEO Pilot provides:**
- SEO-specific system prompts (the expertise/personality)
- Registration of SEO assistants with WP AI Pilot
- SEO-specific actions (analyze post, generate meta, etc.)

### 3.3 Register Built-in Assistants

**Update Integration/class-ai-pilot.php:**

```php
public static function register_seo_assistants(): void {
    if ( ! function_exists( 'wp_ai_pilot' ) ) {
        return;
    }

    // General SEO Assistant
    wp_ai_pilot()->register_assistant([
        'id'            => 'seo-general',
        'name'          => 'General SEO Assistant',
        'description'   => 'Get expert advice on SEO best practices, keyword optimization, and search strategy.',
        'plugin'        => 'wp-seo-pilot/wp-seo-pilot.php',
        'system_prompt' => self::get_general_seo_prompt(),
        'icon'          => 'dashicons-search',
        'color'         => '#3b82f6',
        'suggested_prompts' => [
            'How do I improve my site\'s SEO score?',
            'What are the most important ranking factors?',
            'Help me choose keywords for my niche',
            'Review my meta description strategy',
        ],
        'supports_actions' => true,
        'actions' => [
            'analyze_post' => [
                'label' => 'Analyze Current Post',
                'icon'  => 'analytics',
            ],
            'generate_meta' => [
                'label' => 'Generate Meta Tags',
                'icon'  => 'edit',
            ],
        ],
    ]);

    // SEO Reporter Assistant
    wp_ai_pilot()->register_assistant([
        'id'            => 'seo-reporter',
        'name'          => 'SEO Reporter',
        'description'   => 'Get comprehensive SEO analysis reports for your content.',
        'plugin'        => 'wp-seo-pilot/wp-seo-pilot.php',
        'system_prompt' => self::get_reporter_prompt(),
        'icon'          => 'dashicons-chart-bar',
        'color'         => '#8b5cf6',
        'suggested_prompts' => [
            'Audit my homepage SEO',
            'What technical SEO issues should I fix?',
            'Compare my page to competitors',
            'Generate a full SEO report',
        ],
    ]);
}

private static function get_general_seo_prompt(): string {
    return "You are an expert SEO consultant with 10+ years of experience...";
    // Move content from class-general-seo-assistant.php
}

private static function get_reporter_prompt(): string {
    return "You are an SEO analyst who creates detailed reports...";
    // Move content from class-seo-reporter-assistant.php
}
```

### 3.4 Migrate Custom Assistants

Similar to models migration:

```php
class Assistants_Migrator {

    public static function migrate_custom_assistants(): array {
        global $wpdb;

        $custom = $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}wpseopilot_custom_assistants",
            ARRAY_A
        );

        foreach ( $custom as $assistant ) {
            wp_ai_pilot()->register_assistant([
                'id'              => 'seo-custom-' . $assistant['id'],
                'name'            => $assistant['name'],
                'description'     => $assistant['description'],
                'system_prompt'   => $assistant['system_prompt'],
                'plugin'          => 'wp-seo-pilot/wp-seo-pilot.php',
                'icon'            => $assistant['icon'],
                'color'           => $assistant['color'],
                'suggested_prompts' => json_decode( $assistant['suggested_prompts'], true ),
            ]);
        }

        update_option( 'wpseopilot_assistants_migrated', true );
    }
}
```

### 3.5 Update Chat Endpoint

**Current:** `Assistants_Controller::chat()` handles everything locally.

**After:**

```php
public function chat( $request ) {
    $params = $request->get_json_params();
    $assistant_id = $params['assistant'];
    $message = $params['message'];
    $context = $params['context'] ?? [];

    // Handle action requests locally (SEO-specific actions)
    if ( ! empty( $context['action'] ) ) {
        return $this->handle_seo_action( $assistant_id, $context );
    }

    // Delegate chat to WP AI Pilot
    if ( ! AI_Pilot::is_ready() ) {
        return $this->error( 'WP AI Pilot not configured', 'not_ready', 400 );
    }

    // Map old assistant IDs to new ones
    $ai_pilot_id = $this->map_assistant_id( $assistant_id );

    $response = wp_ai_pilot()->assistant_chat(
        $ai_pilot_id,
        $message,
        $context
    );

    if ( is_wp_error( $response ) ) {
        return $this->error( $response->get_error_message(), 'chat_error', 500 );
    }

    return $this->success( $response );
}

private function handle_seo_action( string $assistant_id, array $context ): \WP_REST_Response {
    $action = $context['action'];

    switch ( $action ) {
        case 'analyze_post':
            return $this->analyze_post( $context['post_id'] ?? 0 );
        case 'generate_meta':
            return $this->generate_meta( $context['post_id'] ?? 0 );
        default:
            return $this->error( 'Unknown action', 'invalid_action', 400 );
    }
}
```

### 3.6 Update Assistants Page UI

**Current:** `src-v2/pages/Assistants.js` manages assistants locally.

**After:**
- Show assistants from WP AI Pilot that belong to SEO Pilot
- "Create Assistant" links to WP AI Pilot
- Local management only for SEO-specific settings

```javascript
// Assistants.js - Updated to use WP AI Pilot

const Assistants = () => {
    const [assistants, setAssistants] = useState([]);

    useEffect(() => {
        // Fetch SEO Pilot's assistants from WP AI Pilot
        apiFetch({ path: '/wpseopilot/v2/assistants' })
            .then(setAssistants);
    }, []);

    // Filter to show only SEO Pilot assistants
    const seoAssistants = assistants.filter(a =>
        a.plugin === 'wp-seo-pilot' || a.id.startsWith('seo-')
    );

    return (
        <div className="page">
            <div className="page-header">
                <h1>SEO Assistants</h1>
                <a
                    href={wpAiPilotUrl + '/assistants/new?plugin=wp-seo-pilot'}
                    className="button primary"
                >
                    Create in WP AI Pilot
                </a>
            </div>
            {/* ... */}
        </div>
    );
};
```

### Tasks for Phase 3

- [ ] Update `AI_Pilot::register_seo_assistants()` with full assistant configs
- [ ] Create `Assistants_Migrator` class
- [ ] Move system prompts from assistant classes to Integration layer
- [ ] Update `Assistants_Controller::chat()` to delegate to WP AI Pilot
- [ ] Keep SEO action handlers local (`analyze_post`, `generate_meta`, etc.)
- [ ] Update Assistants.js to show/manage via WP AI Pilot
- [ ] Update AiAssistant.js chat UI to use WP AI Pilot conversations

**Estimated Scope:** ~300 lines changed, ~800 lines deprecated

---

## Phase 4: Update Frontend Components

**Goal:** Simplify React components to use WP AI Pilot as backend.

### 4.1 Components to Update

| Component | Current State | Target State |
|-----------|---------------|--------------|
| `AiAssistant.js` | Local chat handling | Delegate to WP AI Pilot |
| `Assistants.js` | Full CRUD | View + link to AI Pilot |
| `AiGenerateModal.js` | Local API calls | Use AI_Pilot integration |
| `Settings.js` (AI section) | API key management | Link to WP AI Pilot settings |

### 4.2 AiGenerateModal.js Changes

**Current:** Calls `/wpseopilot/v2/ai/generate` directly.

**After:** Same endpoint, but backend uses WP AI Pilot.

No frontend changes needed if backend handles routing.

### 4.3 Settings.js AI Section

**Current:**
```javascript
<Section title="AI Settings">
    <ApiKeyInput ... />
    <ModelSelector ... />
    <PromptEditor ... />
</Section>
```

**After:**
```javascript
<Section title="AI Settings">
    <div className="ai-settings-notice">
        <p>AI is powered by WP AI Pilot.</p>
        <a href={aiPilotSettingsUrl} className="button">
            Configure AI Settings
        </a>
    </div>

    {/* Keep SEO-specific prompt customization */}
    <h4>SEO Prompts</h4>
    <PromptEditor
        label="Title Generation Prompt"
        value={titlePrompt}
        onChange={...}
    />
    <PromptEditor
        label="Description Generation Prompt"
        value={descPrompt}
        onChange={...}
    />
</Section>
```

### Tasks for Phase 4

- [ ] Update Settings.js to link to WP AI Pilot for API keys
- [ ] Keep SEO prompt customization in Settings
- [ ] Update Assistants.js to view-only + link to AI Pilot
- [ ] Verify AiGenerateModal works with updated backend
- [ ] Add "Powered by WP AI Pilot" badge where appropriate

**Estimated Scope:** ~200 lines changed

---

## Phase 5: Cleanup & Deprecation

**Goal:** Remove dead code, finalize migration.

### 5.1 Files to Remove

After migration is complete and stable:

```
includes/Api/class-ai-controller.php
├── Remove: All provider-specific methods (call_openai, call_anthropic, etc.)
├── Remove: Custom models CRUD (get_custom_models, create_custom_model, etc.)
├── Remove: Models database sync (sync_models_from_api)
├── Keep: generate(), get_status() (thin wrappers)

includes/Api/class-assistants-controller.php
├── Remove: All call_* methods (call_ai, call_openai, call_anthropic, etc.)
├── Remove: Custom assistants CRUD
├── Keep: chat() (delegates to AI Pilot), execute_action() (SEO actions)

includes/Api/Assistants/
├── Remove: class-base-assistant.php (prompts moved to Integration)
├── Remove: class-general-seo-assistant.php (registered in AI Pilot)
├── Remove: class-seo-reporter-assistant.php (registered in AI Pilot)
```

### 5.2 Database Tables

**Migration status table:**
```sql
-- Add to track migration status
ALTER TABLE wp_options ADD COLUMN IF NOT EXISTS ...
wpseopilot_migration_status = {
    'custom_models_migrated': true,
    'custom_assistants_migrated': true,
    'migration_date': '2024-01-15',
    'ai_pilot_version': '1.0.0'
}
```

**Tables to keep but stop using:**
- `wp_wpseopilot_custom_models` - Keep for rollback, delete after 3 versions
- `wp_wpseopilot_custom_assistants` - Keep for rollback, delete after 3 versions
- `wp_wpseopilot_assistant_usage` - Can keep for historical data, or migrate

### 5.3 Options Cleanup

**Remove:**
```php
delete_option( 'wpseopilot_openai_api_key' );
delete_option( 'wpseopilot_anthropic_api_key' );
delete_option( 'wpseopilot_google_api_key' );
delete_option( 'wpseopilot_ai_model' );
delete_option( 'wpseopilot_ai_active_provider' );
delete_option( 'wpseopilot_models_database_cache' );
delete_option( 'wpseopilot_models_database_last_sync' );
```

**Keep:**
```php
// SEO-specific prompts
'wpseopilot_ai_prompt_title'
'wpseopilot_ai_prompt_description'
'wpseopilot_ai_prompt_system'
```

### 5.4 Lines of Code Reduction

| File | Current Lines | After Migration |
|------|---------------|-----------------|
| class-ai-controller.php | 1328 | ~200 |
| class-assistants-controller.php | 1137 | ~300 |
| Assistants/* (3 files) | ~400 | 0 |
| **Total** | ~2865 | ~500 |

**Net reduction: ~2300 lines of duplicate code**

### Tasks for Phase 5

- [ ] Remove deprecated provider methods from AI Controller
- [ ] Remove deprecated CRUD from Assistants Controller
- [ ] Delete Assistant class files (after prompts migrated)
- [ ] Add uninstall cleanup for old options
- [ ] Update version requirements (require WP AI Pilot 1.0+)
- [ ] Add migration notices for users on old versions

**Estimated Scope:** ~2300 lines removed

---

## Migration Timeline

### Version 0.3.0 (Phase 1)
- WP AI Pilot becomes primary AI provider
- Native API keys deprecated (still work)
- Add deprecation notices in Settings

### Version 0.4.0 (Phase 2-3)
- Custom models migrated to AI Pilot
- Assistants registered with AI Pilot
- Chat delegates to AI Pilot

### Version 0.5.0 (Phase 4)
- Frontend updated
- Settings simplified
- Remove dead code paths

### Version 1.0.0 (Phase 5)
- Full cleanup
- Remove old tables (or offer migration)
- WP AI Pilot required dependency

---

## Backward Compatibility

### Users Without WP AI Pilot

For users who don't have WP AI Pilot installed:

1. **Detection:** Check on plugin load
2. **Notice:** Show admin notice recommending WP AI Pilot installation
3. **Fallback (v0.3-0.5):** Keep legacy code working but deprecated
4. **Hard requirement (v1.0):** WP AI Pilot becomes required dependency

```php
// In main plugin file
add_action( 'admin_notices', function() {
    if ( ! AI_Pilot::is_installed() ) {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>WP SEO Pilot:</strong>
                AI features require WP AI Pilot.
                <a href="<?php echo admin_url( 'plugin-install.php?s=wp-ai-pilot' ); ?>">
                    Install now
                </a>
            </p>
        </div>
        <?php
    }
});
```

### Data Migration Safety

1. **Never delete original data automatically**
2. **Store migration mapping** for rollback
3. **Add "Undo Migration" option** in tools
4. **Keep backup tables for 3 versions**

---

## Testing Checklist

### Phase 1 Tests
- [ ] AI generation works with WP AI Pilot
- [ ] Status endpoint shows correct provider
- [ ] Deprecation notices appear in Settings
- [ ] Legacy API keys still work (backward compat)

### Phase 2 Tests
- [ ] Custom models migrate correctly
- [ ] Model references update properly
- [ ] Generation works with migrated models

### Phase 3 Tests
- [ ] Assistants appear in WP AI Pilot
- [ ] Chat works through AI Pilot
- [ ] SEO actions (analyze, generate) work
- [ ] Custom assistants migrate correctly

### Phase 4 Tests
- [ ] Settings page shows AI Pilot link
- [ ] Assistants page works with AI Pilot data
- [ ] AiGenerateModal works unchanged

### Phase 5 Tests
- [ ] Plugin works without legacy code
- [ ] No errors on fresh install
- [ ] Migration from old version works

---

## Files Reference

### Files to Modify

```
includes/
├── Integration/
│   └── class-ai-pilot.php          # Expand with full assistant registration
├── Api/
│   ├── class-ai-controller.php     # Simplify to thin wrapper
│   └── class-assistants-controller.php  # Delegate to AI Pilot
├── Migration/
│   ├── class-models-migrator.php   # NEW: Migrate custom models
│   └── class-assistants-migrator.php  # NEW: Migrate assistants

src-v2/
├── pages/
│   ├── Settings.js                 # Add AI Pilot links
│   └── Assistants.js               # View-only + AI Pilot links
```

### Files to Delete (Phase 5)

```
includes/Api/Assistants/
├── class-base-assistant.php
├── class-general-seo-assistant.php
└── class-seo-reporter-assistant.php
```

---

## Summary

This migration consolidates all AI functionality in WP AI Pilot while keeping SEO-specific features in WP SEO Pilot. The result is:

1. **Cleaner architecture:** Single source of truth for AI
2. **Less code:** ~2300 lines removed from SEO Pilot
3. **Better UX:** Unified AI management in one place
4. **Easier maintenance:** Provider updates only in AI Pilot
5. **Ecosystem ready:** Other Pilot plugins can integrate the same way

The migration is designed to be gradual and backward-compatible, allowing users to transition at their own pace while we phase out legacy code.
