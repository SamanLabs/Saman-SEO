# Phase 5: AI Integration Refactor - Plan 1

## Objective
Remove built-in AI API client code and fully delegate to Saman Labs AI (renamed from WP AI Pilot). Update integration hooks, add graceful degradation, and prompt users to install Saman Labs AI.

## Discovery Findings

### Current State
The plugin already has a partial delegation architecture via `Integration\AI_Pilot` class. However:

1. **Direct API calls still exist in 3 files:**
   - `class-samanlabs-seo-service-ai-assistant.php` (lines 172+) - Direct OpenAI call
   - `class-setup-controller.php` (lines 234+) - Test methods for OpenAI, Anthropic, Ollama
   - `class-tools-controller.php` (lines 1070+) - Direct OpenAI call for AI tools

2. **AI settings still stored locally:**
   - `samanlabs_seo_openai_api_key` - API key in options
   - `samanlabs_seo_ai_model` - Model selection
   - `samanlabs_seo_ai_prompt_*` - Prompt templates

3. **Integration file needs rebranding:**
   - `Integration\AI_Pilot` class references "WP AI Pilot" - should be "Saman Labs AI"
   - Function checks: `wp_ai_pilot()`, `wp_ai_pilot_is_ready()`, etc.
   - Hook name: `wp_ai_pilot_loaded`

### What's Already Delegated
- `Api\Ai_Controller` - Properly delegates to `AI_Pilot` class
- `Api\Assistants_Controller` - Properly delegates chat to `AI_Pilot`
- Built-in assistants registered via `AI_Pilot::register_seo_assistants()`

---

## Tasks

### Task 1: Rename integration references from WP AI Pilot to Saman Labs AI
**Files:**
- `includes/Integration/class-ai-pilot.php`
- All PHP files referencing the integration

Replace:
```
WP AI Pilot → Saman Labs AI
wp_ai_pilot → samanlabs_ai
wp_ai_pilot_loaded → samanlabs_ai_loaded
wp_ai_pilot_is_installed → samanlabs_ai_is_installed
wp_ai_pilot_is_active → samanlabs_ai_is_active
wp_ai_pilot_is_ready → samanlabs_ai_is_ready
wp_ai_pilot_get_status → samanlabs_ai_get_status
```

**Note:** The actual function names depend on what the Saman Labs AI plugin exposes. If the plugin hasn't been renamed yet, create constants/abstractions that can be updated later.

**Commit:** `refactor(5-1): rename integration references to Saman Labs AI`

---

### Task 2: Remove direct OpenAI API call from AI_Assistant service
**File:** `includes/class-samanlabs-seo-service-ai-assistant.php`

The `handle_generation()` method (lines 112-210) makes a direct `wp_remote_post` to OpenAI.

Options:
1. **Delegate to AI_Pilot integration** (preferred)
2. **Remove the V1 AI page entirely** (it's already commented out in boot())

Since the React UI handles AI via REST endpoints that already delegate to AI_Pilot, we should:
- Remove the AJAX handler `handle_generation()`
- Remove the `render_page()` method
- Keep only the asset enqueuing if needed elsewhere

**Commit:** `refactor(5-1): remove direct OpenAI calls from AI_Assistant service`

---

### Task 3: Remove direct API test methods from Setup_Controller
**File:** `includes/Api/class-setup-controller.php`

Remove the test methods that make direct API calls:
- `test_openai()` (lines 233-260)
- `test_anthropic()` (lines 268-297)
- `test_ollama()` (lines 305-328)

Replace the test endpoint with a check against Saman Labs AI:
- If Saman Labs AI is installed and ready, report success
- If not installed, prompt to install
- If not configured, link to Saman Labs AI settings

**Commit:** `refactor(5-1): replace direct API tests with Saman Labs AI status check`

---

### Task 4: Remove direct OpenAI call from Tools_Controller
**File:** `includes/Api/class-tools-controller.php`

The `call_ai()` method (around line 1050) makes a direct OpenAI call.

Replace with delegation to `AI_Pilot::generate()` or `AI_Pilot::chat()`.

**Commit:** `refactor(5-1): delegate Tools_Controller AI calls to integration layer`

---

### Task 5: Remove legacy AI option keys
**Files:** Settings service, AI-related templates

The following options should be removed or deprecated:
- `samanlabs_seo_openai_api_key` - No longer needed (handled by Saman Labs AI)
- `samanlabs_seo_ai_model` - Could keep for prompt customization, but model selection moves to AI plugin

Keep:
- `samanlabs_seo_ai_prompt_system`
- `samanlabs_seo_ai_prompt_title`
- `samanlabs_seo_ai_prompt_description`
(These are SEO-specific prompts, not AI config)

Update settings service to:
- Remove API key from settings page
- Remove model selection dropdown
- Keep prompt customization

**Commit:** `refactor(5-1): remove legacy AI configuration options`

---

### Task 6: Add admin notice for Saman Labs AI installation
**File:** Create new or update `includes/class-samanlabs-seo-service-admin-ui.php`

Add a dismissible admin notice when:
- AI features are accessed (AI assistant page, editor AI generation)
- Saman Labs AI is not installed

Notice should:
- Explain AI features require Saman Labs AI
- Provide link to install/configure
- Be dismissible (store in user meta)

**Commit:** `refactor(5-1): add admin notice prompting Saman Labs AI installation`

---

### Task 7: Update AI-related UI strings
**Files:** JS source files, PHP templates

Update user-facing strings from:
- "WP AI Pilot" → "Saman Labs AI"
- "Install WP AI Pilot" → "Install Saman Labs AI"
- Related messaging

**Commit:** `refactor(5-1): update AI-related UI strings to Saman Labs AI`

---

### Task 8: Verify graceful degradation
**Verification only - no commit**

Test scenarios:
1. Saman Labs AI not installed → AI features disabled, notice shown
2. Saman Labs AI installed but not active → Notice to activate
3. Saman Labs AI active but not configured → Link to settings
4. Saman Labs AI ready → AI features work

Grep for any remaining direct API calls.

---

## Verification

After all changes:
```bash
# No direct API calls should remain
grep -r "api.openai.com" --include="*.php" . | grep -v ".planning"
grep -r "api.anthropic.com" --include="*.php" . | grep -v ".planning"
grep -r "localhost:11434" --include="*.php" . | grep -v ".planning"

# No wp_ai_pilot references should remain
grep -r "wp_ai_pilot" --include="*.php" . | grep -v ".planning"

# No old API key option references (except migration/cleanup)
grep -r "samanlabs_seo_openai_api_key" --include="*.php" . | grep -v ".planning"
```

---

## Success Criteria

- [ ] Integration class renamed to use Saman Labs AI naming
- [ ] No direct OpenAI/Anthropic/Ollama API calls in source
- [ ] AI_Assistant service cleaned up (no direct API calls)
- [ ] Setup_Controller uses integration layer for testing
- [ ] Tools_Controller uses integration layer
- [ ] Legacy API key option removed from settings
- [ ] Admin notice added for missing Saman Labs AI
- [ ] UI strings updated
- [ ] Graceful degradation verified

---

## Output

- Updated PHP files with clean integration layer
- Removed direct API client code
- Admin notice for AI plugin installation
- 7 commits with clear messages
- Ready for Phase 6 (Feature Toggle Fix)

---

## Dependencies

**Important:** This plan assumes the external AI plugin will be renamed from "WP AI Pilot" to "Saman Labs AI" with corresponding function name changes. If the AI plugin hasn't been renamed yet:
- Option A: Complete this phase with current names, update later
- Option B: Pause this phase until AI plugin is renamed

**Recommendation:** Proceed with current WP AI Pilot names for now, as the integration is already abstracted through the AI_Pilot class. When the AI plugin is renamed, only the Integration class needs updating.
