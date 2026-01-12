# WP SEO Pilot + WP AI Pilot Integration Plan

## Overview

Integrate WP SEO Pilot's AI features with WP AI Pilot plugin using the official Public API.

---

## Integration Summary

| Feature | Method |
|---------|--------|
| Check if available | `wp_ai_pilot_is_ready()` |
| Simple generation | `wp_ai_pilot()->generate($prompt, $options)` |
| Chat with history | `wp_ai_pilot()->chat($messages, $options)` |
| Get status | `wp_ai_pilot_get_status()` |
| Register plugin | `wp_ai_pilot()->register_plugin($config)` |
| Register assistant | `wp_ai_pilot()->register_assistant($config)` |

---

## Phase 1: Integration Helper Class

### File: `includes/Integration/class-ai-pilot.php`

```php
<?php
/**
 * WP AI Pilot Integration
 *
 * @package WPSEOPilot
 */

namespace WPSEOPilot\Integration;

defined( 'ABSPATH' ) || exit;

/**
 * Handles integration with WP AI Pilot plugin.
 */
class AI_Pilot {

    /**
     * Plugin source identifier for usage tracking.
     */
    const SOURCE = 'wp-seo-pilot';

    /**
     * Check if WP AI Pilot is installed.
     */
    public static function is_installed(): bool {
        return function_exists( 'wp_ai_pilot_is_installed' )
            && wp_ai_pilot_is_installed();
    }

    /**
     * Check if WP AI Pilot is active.
     */
    public static function is_active(): bool {
        return function_exists( 'wp_ai_pilot_is_active' )
            && wp_ai_pilot_is_active();
    }

    /**
     * Check if WP AI Pilot is ready (active + configured).
     */
    public static function is_ready(): bool {
        return function_exists( 'wp_ai_pilot_is_ready' )
            && wp_ai_pilot_is_ready();
    }

    /**
     * Get complete status information.
     *
     * @return array Status array with installed, active, ready, version, providers, models.
     */
    public static function get_status(): array {
        if ( function_exists( 'wp_ai_pilot_get_status' ) ) {
            return wp_ai_pilot_get_status();
        }

        return [
            'installed' => false,
            'active'    => false,
            'ready'     => false,
            'version'   => null,
            'providers' => [],
            'models'    => [],
        ];
    }

    /**
     * Check if AI features should be enabled.
     * Uses WP AI Pilot if available, falls back to native keys.
     */
    public static function ai_enabled(): bool {
        // First check WP AI Pilot
        if ( self::is_ready() ) {
            return true;
        }

        // Fallback to SEO Pilot's own keys (legacy support)
        return ! empty( get_option( 'wpseopilot_openai_api_key', '' ) )
            || ! empty( get_option( 'wpseopilot_anthropic_api_key', '' ) )
            || ! empty( get_option( 'wpseopilot_google_api_key', '' ) );
    }

    /**
     * Get the AI provider being used.
     *
     * @return string 'wp-ai-pilot', 'native', or 'none'.
     */
    public static function get_provider(): string {
        if ( self::is_ready() ) {
            return 'wp-ai-pilot';
        }

        if ( ! empty( get_option( 'wpseopilot_openai_api_key', '' ) )
            || ! empty( get_option( 'wpseopilot_anthropic_api_key', '' ) )
            || ! empty( get_option( 'wpseopilot_google_api_key', '' ) ) ) {
            return 'native';
        }

        return 'none';
    }

    /**
     * Get available models from WP AI Pilot.
     *
     * @return array Array of model configurations.
     */
    public static function get_models(): array {
        if ( ! self::is_ready() ) {
            return [];
        }

        return wp_ai_pilot()->get_models();
    }

    /**
     * Generate text using WP AI Pilot.
     *
     * @param string $prompt       The prompt to send.
     * @param string $type         'title' or 'description'.
     * @param array  $context      Optional context data.
     *
     * @return string|WP_Error Generated text or error.
     */
    public static function generate( string $prompt, string $type = 'title', array $context = [] ) {
        if ( ! self::is_ready() ) {
            return new \WP_Error( 'ai_not_ready', __( 'WP AI Pilot is not configured.', 'wp-seo-pilot' ) );
        }

        $system_prompt = self::get_system_prompt( $type );

        return wp_ai_pilot()->generate( $prompt, [
            'source'        => self::SOURCE,
            'system_prompt' => $system_prompt,
            'max_tokens'    => $type === 'title' ? 100 : 250,
            'temperature'   => 0.7,
        ]);
    }

    /**
     * Chat with message history using WP AI Pilot.
     *
     * @param array $messages Message array with role/content.
     * @param array $options  Optional settings.
     *
     * @return array|WP_Error Response array or error.
     */
    public static function chat( array $messages, array $options = [] ) {
        if ( ! self::is_ready() ) {
            return new \WP_Error( 'ai_not_ready', __( 'WP AI Pilot is not configured.', 'wp-seo-pilot' ) );
        }

        $defaults = [
            'source'      => self::SOURCE,
            'max_tokens'  => 500,
            'temperature' => 0.7,
        ];

        return wp_ai_pilot()->chat( $messages, array_merge( $defaults, $options ) );
    }

    /**
     * Get system prompt for SEO generation.
     *
     * @param string $type 'title' or 'description'.
     *
     * @return string System prompt.
     */
    private static function get_system_prompt( string $type ): string {
        if ( 'title' === $type ) {
            return "You are an SEO expert specializing in writing compelling page titles.

Requirements:
- Maximum 60 characters (strict limit)
- Include the primary keyword near the beginning
- Make it click-worthy but not clickbait
- Use power words when appropriate
- Match search intent

Return ONLY the title text. No quotes, no explanation, no alternatives.";
        }

        return "You are an SEO expert specializing in writing meta descriptions.

Requirements:
- Maximum 155 characters (strict limit)
- Include a clear call-to-action
- Summarize the page content accurately
- Include the primary keyword naturally
- Create urgency or curiosity when appropriate

Return ONLY the description text. No quotes, no explanation, no alternatives.";
    }

    /**
     * Get usage statistics for WP SEO Pilot.
     *
     * @param string $period '24hours', '7days', '30days', '90days', 'all'.
     *
     * @return array Usage statistics.
     */
    public static function get_usage( string $period = '30days' ): array {
        if ( ! self::is_ready() ) {
            return [];
        }

        return wp_ai_pilot()->get_usage( self::SOURCE, $period );
    }
}
```

---

## Phase 2: Plugin Registration

### File: `includes/class-wpseopilot.php` (Main Plugin Class)

Add registration on `plugins_loaded`:

```php
/**
 * Register with WP AI Pilot.
 */
public function register_with_ai_pilot() {
    if ( ! function_exists( 'wp_ai_pilot' ) ) {
        return;
    }

    // Register our plugin
    wp_ai_pilot()->register_plugin([
        'slug'        => 'wp-seo-pilot',
        'file'        => 'wp-seo-pilot/wp-seo-pilot.php',
        'name'        => 'WP SEO Pilot',
        'permissions' => [ 'generate', 'chat', 'assistants' ],
    ]);
}

// In boot() method:
add_action( 'plugins_loaded', [ $this, 'register_with_ai_pilot' ], 20 );
```

---

## Phase 3: Register SEO Assistant

### File: `includes/class-wpseopilot.php`

Register an SEO assistant in WP AI Pilot:

```php
/**
 * Register SEO Assistant with WP AI Pilot.
 */
public function register_seo_assistant() {
    if ( ! function_exists( 'wp_ai_pilot' ) ) {
        return;
    }

    wp_ai_pilot()->register_assistant([
        'id'            => 'seo-pilot-assistant',
        'name'          => 'SEO Assistant',
        'description'   => 'Expert SEO advice for titles, descriptions, and content optimization.',
        'plugin'        => 'wp-seo-pilot/wp-seo-pilot.php',
        'system_prompt' => "You are an expert SEO consultant with deep knowledge of:
- On-page SEO optimization
- Meta title and description best practices
- Keyword research and placement
- Content optimization
- Technical SEO fundamentals
- Search intent matching
- SERP feature optimization

Help users optimize their WordPress content for search engines. Provide specific, actionable advice. When generating titles or descriptions, always respect character limits (60 for titles, 155 for descriptions).

Current site: " . get_bloginfo( 'name' ) . "
Site tagline: " . get_bloginfo( 'description' ),
        'icon'               => 'search',
        'color'              => '#10b981',
        'model'              => 'gpt-4o-mini',
        'temperature'        => 0.7,
        'max_tokens'         => 1000,
        'supports_vision'    => false,
        'save_conversations' => true,
        'suggested_prompts'  => [
            'Analyze my homepage SEO',
            'Write a meta title for my About page',
            'How can I improve my keyword targeting?',
            'What makes a good meta description?',
        ],
    ]);
}

// In boot() method:
add_action( 'init', [ $this, 'register_seo_assistant' ] );
```

---

## Phase 4: Update AI Controller

### File: `includes/Api/class-ai-controller.php`

Update to use WP AI Pilot when available:

```php
use WPSEOPilot\Integration\AI_Pilot;

/**
 * Generate SEO content.
 */
public function generate( $request ) {
    $params  = $request->get_params();
    $content = sanitize_textarea_field( $params['content'] ?? '' );
    $type    = sanitize_text_field( $params['type'] ?? 'title' );

    // Use WP AI Pilot if available
    if ( AI_Pilot::is_ready() ) {
        return $this->generate_via_ai_pilot( $content, $type );
    }

    // Fallback to native implementation
    return $this->generate_native( $content, $type );
}

/**
 * Generate via WP AI Pilot.
 */
private function generate_via_ai_pilot( string $content, string $type ) {
    $result = AI_Pilot::generate( $content, $type );

    if ( is_wp_error( $result ) ) {
        return new \WP_REST_Response([
            'success' => false,
            'message' => $result->get_error_message(),
        ], 500 );
    }

    // Format response
    $data = $type === 'title'
        ? [ 'title' => trim( $result ) ]
        : [ 'description' => trim( $result ) ];

    return new \WP_REST_Response([
        'success'  => true,
        'data'     => $data,
        'provider' => 'wp-ai-pilot',
    ]);
}
```

---

## Phase 5: Update Admin UI Enqueue

### File: `includes/class-wpseopilot-service-admin-ui.php`

Update `enqueue_editor_assets()`:

```php
use WPSEOPilot\Integration\AI_Pilot;

// In enqueue_editor_assets():

// Get AI status from integration
$ai_status   = AI_Pilot::get_status();
$ai_enabled  = AI_Pilot::ai_enabled();
$ai_provider = AI_Pilot::get_provider();

wp_localize_script(
    'wpseopilot-editor-v2',
    'wpseopilotEditor',
    [
        'variables'    => $variables,
        'aiEnabled'    => $ai_enabled,
        'aiProvider'   => $ai_provider, // 'wp-ai-pilot', 'native', or 'none'
        'aiPilot'      => [
            'installed'  => $ai_status['installed'],
            'active'     => $ai_status['active'],
            'ready'      => $ai_status['ready'],
            'version'    => $ai_status['version'] ?? null,
            'settingsUrl' => admin_url( 'admin.php?page=wp-ai-pilot' ),
        ],
        'siteTitle'    => get_bloginfo( 'name' ),
        'tagline'      => get_bloginfo( 'description' ),
        'separator'    => get_option( 'wpseopilot_title_separator', '|' ),
    ]
);
```

---

## Phase 6: Update Frontend Components

### File: `src-v2/editor/components/AiGenerateModal.js`

Add provider badge and status handling:

```jsx
const AiGenerateModal = ({
    isOpen,
    onClose,
    onGenerate,
    fieldType,
    postTitle,
    postContent,
    aiProvider,  // 'wp-ai-pilot', 'native', or 'none'
    aiPilot,     // { installed, active, ready, settingsUrl }
}) => {
    // ... existing state ...

    // Show configuration notice if not ready
    if (aiProvider === 'none') {
        return (
            <div className="wpseopilot-ai-modal-overlay" onClick={onClose}>
                <div className="wpseopilot-ai-modal" onClick={e => e.stopPropagation()}>
                    <div className="wpseopilot-ai-modal__header">
                        <h3>AI Not Configured</h3>
                        <button onClick={onClose}>×</button>
                    </div>
                    <div className="wpseopilot-ai-modal__body">
                        {aiPilot?.installed ? (
                            <div className="wpseopilot-ai-notice">
                                <p>WP AI Pilot is installed but not configured.</p>
                                <a
                                    href={aiPilot.settingsUrl}
                                    className="wpseopilot-ai-modal__btn wpseopilot-ai-modal__btn--primary"
                                >
                                    Configure WP AI Pilot
                                </a>
                            </div>
                        ) : (
                            <div className="wpseopilot-ai-notice">
                                <p>Install WP AI Pilot to enable AI-powered SEO suggestions.</p>
                                <a
                                    href="/wp-admin/plugin-install.php?s=wp+ai+pilot&tab=search"
                                    className="wpseopilot-ai-modal__btn wpseopilot-ai-modal__btn--primary"
                                >
                                    Install WP AI Pilot
                                </a>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="wpseopilot-ai-modal-overlay" onClick={onClose}>
            <div className="wpseopilot-ai-modal" onClick={e => e.stopPropagation()}>
                <div className="wpseopilot-ai-modal__header">
                    <h3>
                        <svg>...</svg>
                        Generate {fieldType === 'title' ? 'Title' : 'Description'}
                    </h3>
                    {aiProvider === 'wp-ai-pilot' && (
                        <span className="wpseopilot-ai-badge">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                            </svg>
                            WP AI Pilot
                        </span>
                    )}
                    <button onClick={onClose}>×</button>
                </div>
                {/* ... rest of modal body ... */}
            </div>
        </div>
    );
};
```

---

## Phase 7: Settings Page Notice

### File: `src-v2/pages/AISettings.js` (if exists) or new component

Show integration status on AI settings:

```jsx
const AISettingsNotice = ({ aiPilot }) => {
    if (aiPilot.ready) {
        return (
            <div className="wpseopilot-notice wpseopilot-notice--success">
                <div className="wpseopilot-notice__icon">
                    <svg>✓</svg>
                </div>
                <div className="wpseopilot-notice__content">
                    <h4>Connected to WP AI Pilot</h4>
                    <p>
                        AI features are powered by WP AI Pilot v{aiPilot.version}.
                        <a href={aiPilot.settingsUrl}>Manage AI Settings</a>
                    </p>
                </div>
            </div>
        );
    }

    if (aiPilot.installed && !aiPilot.ready) {
        return (
            <div className="wpseopilot-notice wpseopilot-notice--warning">
                <div className="wpseopilot-notice__icon">
                    <svg>!</svg>
                </div>
                <div className="wpseopilot-notice__content">
                    <h4>WP AI Pilot Needs Configuration</h4>
                    <p>
                        Add an API key in WP AI Pilot to enable AI-powered SEO features.
                        <a href={aiPilot.settingsUrl}>Configure Now</a>
                    </p>
                </div>
            </div>
        );
    }

    return (
        <div className="wpseopilot-notice wpseopilot-notice--info">
            <div className="wpseopilot-notice__icon">
                <svg>i</svg>
            </div>
            <div className="wpseopilot-notice__content">
                <h4>Enhance with WP AI Pilot</h4>
                <p>
                    Install WP AI Pilot for AI-powered title and description generation.
                    <a href="/wp-admin/plugin-install.php?s=wp+ai+pilot&tab=search">
                        Install Plugin
                    </a>
                </p>
            </div>
        </div>
    );
};
```

---

## Implementation Checklist

### Phase 1: Integration Class
- [ ] Create `includes/Integration/class-ai-pilot.php`
- [ ] Add namespace autoloading if needed
- [ ] Test `is_ready()`, `is_active()`, `is_installed()`

### Phase 2: Plugin Registration
- [ ] Add `register_with_ai_pilot()` method
- [ ] Hook to `plugins_loaded` priority 20
- [ ] Verify registration in WP AI Pilot admin

### Phase 3: SEO Assistant
- [ ] Add `register_seo_assistant()` method
- [ ] Hook to `init`
- [ ] Test assistant appears in WP AI Pilot
- [ ] Test suggested prompts work

### Phase 4: AI Controller
- [ ] Update `generate()` to check AI_Pilot first
- [ ] Add `generate_via_ai_pilot()` method
- [ ] Test generation works through WP AI Pilot
- [ ] Test fallback to native still works

### Phase 5: Editor Localization
- [ ] Update `enqueue_editor_assets()`
- [ ] Pass `aiProvider` and `aiPilot` status
- [ ] Verify data in browser console

### Phase 6: Frontend Modal
- [ ] Update `AiGenerateModal.js`
- [ ] Add provider badge
- [ ] Add "not configured" state
- [ ] Test all states render correctly

### Phase 7: Settings Notice
- [ ] Create notice component
- [ ] Show on AI/More settings page
- [ ] Test links work correctly

### Final Steps
- [ ] Build assets: `npm run build:editor`
- [ ] Test with WP AI Pilot installed + configured
- [ ] Test with WP AI Pilot installed + not configured
- [ ] Test without WP AI Pilot
- [ ] Test native fallback still works

---

## File Changes Summary

| File | Action | Description |
|------|--------|-------------|
| `includes/Integration/class-ai-pilot.php` | **Create** | Integration helper class |
| `includes/class-wpseopilot.php` | Modify | Add registration hooks |
| `includes/Api/class-ai-controller.php` | Modify | Route to WP AI Pilot |
| `includes/class-wpseopilot-service-admin-ui.php` | Modify | Update localization |
| `src-v2/editor/components/AiGenerateModal.js` | Modify | Add provider status |
| `src-v2/editor/components/SEOPanel.js` | Modify | Pass aiPilot props |
| `src-v2/editor/index.js` | Modify | Read aiPilot from localized data |
| `src-v2/editor/editor.css` | Modify | Add badge and notice styles |

---

## Testing Scenarios

### Scenario 1: WP AI Pilot Ready
- AI buttons visible
- "WP AI Pilot" badge shows in modal
- Generation uses WP AI Pilot API
- Usage tracked under 'wp-seo-pilot' source
- SEO Assistant available in WP AI Pilot

### Scenario 2: WP AI Pilot Installed, Not Configured
- AI buttons visible but show config notice
- Modal shows "Configure WP AI Pilot" link
- Link goes to WP AI Pilot settings

### Scenario 3: WP AI Pilot Not Installed
- AI buttons visible if native keys exist
- Modal shows "Install WP AI Pilot" notice
- Native generation works as fallback

### Scenario 4: No AI Available
- AI buttons hidden
- No errors thrown
- SEO features work without AI

---

## Notes

- Always use `source: 'wp-seo-pilot'` for usage tracking
- SEO Assistant ID: `seo-pilot-assistant`
- Character limits enforced in system prompts
- Temperature 0.7 for balanced creativity
- Use `gpt-4o-mini` as default for cost efficiency
