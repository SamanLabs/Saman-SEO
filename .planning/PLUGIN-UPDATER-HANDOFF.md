# Plugin Updater System - Integration Guide

This document explains the GitHub-based plugin updater system built for WP AI Pilot. Use this guide to implement the same functionality in other plugins (like WP SEO Pilot).

---

## Overview

The updater system allows users to:
- **Install** plugins directly from GitHub releases
- **Update** plugins when new versions are available
- **Activate/Deactivate** plugins
- **Check for updates** manually or via daily cron

All managed from a "Connected Plugins" or "More" page in the WordPress admin.

---

## Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress Admin                           │
│                                                                  │
│  ┌──────────────────┐    ┌──────────────────┐                   │
│  │  React Frontend  │───▶│   REST API       │                   │
│  │  (ManagedPlugins)│    │  (/updater/...)  │                   │
│  └──────────────────┘    └────────┬─────────┘                   │
│                                   │                              │
│                    ┌──────────────┴──────────────┐              │
│                    │                             │              │
│            ┌───────▼───────┐          ┌─────────▼────────┐     │
│            │ GitHub Updater │          │ Plugin Installer │     │
│            │ (version check)│          │ (install/update) │     │
│            └───────┬───────┘          └──────────────────┘     │
│                    │                                            │
└────────────────────┼────────────────────────────────────────────┘
                     │
                     ▼
            ┌────────────────┐
            │  GitHub API    │
            │  /releases     │
            └────────────────┘
```

---

## Backend Implementation

### 1. GitHub Updater Class

**File:** `includes/Updater/class-github-updater.php`

This class:
- Hooks into WordPress update system (`pre_set_site_transient_update_plugins`)
- Checks GitHub releases API for new versions
- Caches results for 12 hours using transients
- Schedules daily cron checks
- Fixes folder names after extraction (GitHub zips have version suffixes)

**Key Configuration - Managed Plugins:**

```php
$this->plugins = array(
    'WP-AI-Pilot/wp-ai-pilot.php' => array(
        'slug'        => 'wp-ai-pilot',
        'repo'        => 'jhd3197/WP-AI-Pilot',      // GitHub owner/repo
        'name'        => 'WP AI Pilot',
        'description' => 'Centralized AI management for WordPress',
        'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/images/icon-128.png',
        'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-AI-Pilot/main/assets/images/banner-772x250.png',
    ),
    'WP-SEO-Pilot/wp-seo-pilot.php' => array(
        'slug'        => 'wp-seo-pilot',
        'repo'        => 'jhd3197/WP-SEO-Pilot',
        'name'        => 'WP SEO Pilot',
        'description' => 'AI-powered SEO optimization for WordPress',
        'icon'        => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/images/icon-128.png',
        'banner'      => 'https://raw.githubusercontent.com/jhd3197/WP-SEO-Pilot/main/assets/images/banner-772x250.png',
    ),
);
```

**Important:** The array key (e.g., `WP-SEO-Pilot/wp-seo-pilot.php`) must match exactly:
- The folder name in `wp-content/plugins/`
- The main plugin file name

### 2. Plugin Installer Class

**File:** `includes/Updater/class-plugin-installer.php`

Static methods:
- `install($download_url, $plugin_file)` - Install from GitHub zip
- `update($plugin_file)` - Update existing plugin
- `activate($plugin_file)` - Activate plugin
- `deactivate($plugin_file)` - Deactivate plugin
- `delete($plugin_file)` - Delete plugin

**Critical:** Must load WordPress admin files before operations:

```php
private static function load_wp_admin_files() {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/misc.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
    require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
    WP_Filesystem();
}
```

### 3. REST API Controller

**File:** `includes/Api/class-updater-controller.php`

**Endpoints:**

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/wp-ai-pilot/v1/updater/plugins` | GET | Get all managed plugins with status |
| `/wp-ai-pilot/v1/updater/check` | POST | Force check for updates (clears cache) |
| `/wp-ai-pilot/v1/updater/install` | POST | Install a plugin (`{slug: "wp-seo-pilot"}`) |
| `/wp-ai-pilot/v1/updater/update` | POST | Update a plugin |
| `/wp-ai-pilot/v1/updater/activate` | POST | Activate a plugin |
| `/wp-ai-pilot/v1/updater/deactivate` | POST | Deactivate a plugin |
| `/wp-ai-pilot/v1/updater/settings` | GET/POST | Auto-update settings |

**Response Format (GET /plugins):**

```json
{
    "wp-ai-pilot": {
        "plugin_file": "WP-AI-Pilot/wp-ai-pilot.php",
        "name": "WP AI Pilot",
        "description": "Centralized AI management for WordPress",
        "repo": "jhd3197/WP-AI-Pilot",
        "installed": true,
        "active": true,
        "current_version": "0.0.1",
        "remote_version": "0.0.2",
        "update_available": true,
        "download_url": "https://github.com/.../wp-ai-pilot-0-0-2.zip",
        "github_url": "https://github.com/jhd3197/WP-AI-Pilot",
        "icon": "https://raw.githubusercontent.com/.../icon-128.png"
    },
    "wp-seo-pilot": { ... }
}
```

### 4. Registration in Admin Loader

```php
// In load_dependencies()
require_once $includes_path . 'Updater/class-github-updater.php';
require_once $includes_path . 'Updater/class-plugin-installer.php';
require_once $includes_path . 'Api/class-updater-controller.php';

// In run()
WP_AI_Pilot_GitHub_Updater::get_instance();

// In register_api_routes()
$updater_controller = new WP_AI_Pilot_Updater_Controller();
$updater_controller->register_routes();
```

### 5. Cleanup on Deactivation

```php
// In class-deactivator.php
wp_clear_scheduled_hook( 'wp_ai_pilot_check_updates' );

// In uninstall
delete_option( 'wp_ai_pilot_auto_updates' );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_wpaipilot_gh_%'" );
```

---

## Frontend Implementation (React)

### Component: ManagedPlugins.js

**Location:** `src/components/ManagedPlugins.js`

```jsx
import { useState, useEffect, useCallback } from 'react';
import apiFetch from '@wordpress/api-fetch';

const ManagedPlugins = () => {
    const [plugins, setPlugins] = useState({});
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [actionLoading, setActionLoading] = useState({});
    const [notice, setNotice] = useState(null);

    // Load plugins on mount
    const loadPlugins = useCallback(async () => {
        try {
            setLoading(true);
            const data = await apiFetch({ path: '/wp-ai-pilot/v1/updater/plugins' });
            setPlugins(data);
        } catch (error) {
            setNotice({ type: 'error', message: 'Failed to load managed plugins' });
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        loadPlugins();
    }, [loadPlugins]);

    // Check for updates
    const checkForUpdates = async () => {
        setChecking(true);
        try {
            await apiFetch({ path: '/wp-ai-pilot/v1/updater/check', method: 'POST' });
            await loadPlugins();
            setNotice({ type: 'success', message: 'Update check complete' });
        } catch (error) {
            setNotice({ type: 'error', message: 'Failed to check for updates' });
        } finally {
            setChecking(false);
        }
    };

    // Handle install/update/activate/deactivate
    const handleAction = async (slug, action) => {
        setActionLoading(prev => ({ ...prev, [slug]: action }));
        try {
            const response = await apiFetch({
                path: `/wp-ai-pilot/v1/updater/${action}`,
                method: 'POST',
                data: { slug },
            });
            setNotice({ type: 'success', message: response.message });
            await loadPlugins();
        } catch (error) {
            setNotice({ type: 'error', message: error.message });
        } finally {
            setActionLoading(prev => ({ ...prev, [slug]: null }));
        }
    };

    // ... render JSX
};
```

### UI Structure

```
┌─────────────────────────────────────────────────────────────────┐
│  Pilot Plugins                          [Check for Updates]     │
│  Install and manage plugins from the Pilot ecosystem.           │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  ┌─────────────────────────┐  ┌─────────────────────────┐      │
│  │ [Icon]                  │  │ [Icon]                  │      │
│  │ WP AI Pilot    [Active] │  │ WP SEO Pilot [Inactive] │      │
│  │ v0.0.1                  │  │ v0.1.41                 │      │
│  │                         │  │                         │      │
│  │ Centralized AI mgmt...  │  │ AI-powered SEO...       │      │
│  │                         │  │                         │      │
│  │ [Deactivate] [GitHub]   │  │ [Activate] [GitHub]     │      │
│  └─────────────────────────┘  └─────────────────────────┘      │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

---

## UI Styles (LESS)

**File:** `src/less/components/managed-plugins.less`

### Variables Used

```less
// From variables.less
@color-primary: #2271b1;
@color-success: #2f8f5b;
@color-warning: #c07c1a;
@color-surface: #ffffff;
@color-surface-muted: #f4f6fb;
@color-border: #d6d9e0;
@color-text: #1d2327;
@color-muted: #5f6b7a;

@spacing-xs: 4px;
@spacing-sm: 8px;
@spacing-md: 12px;
@spacing-lg: 16px;
@spacing-xl: 20px;
@spacing-2xl: 24px;

@radius-md: 12px;
@radius-lg: 16px;

@font-size-xs: 11px;
@font-size-sm: 12px;
@font-size-base: 14px;
@font-size-md: 16px;

@shadow-card: 0 12px 24px rgba(20, 30, 45, 0.06);
@transition-fast: 0.2s ease;
```

### Key Styles

```less
// Section container
.managed-plugins-section {
    margin-bottom: @spacing-2xl;
    padding-bottom: @spacing-2xl;
    border-bottom: 1px solid @color-border;
}

// Header with title and button
.managed-plugins-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: @spacing-lg;
    gap: @spacing-lg;

    h2 {
        font-size: @font-size-md;
        font-weight: 600;
        color: @color-text;
        margin: 0 0 @spacing-xs 0;
    }
}

// Grid of plugin cards
.managed-plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: @spacing-lg;
}

// Individual plugin card
.managed-plugin-card {
    background: @color-surface;
    border: 1px solid @color-border;
    border-radius: @radius-lg;
    padding: @spacing-xl;
    transition: box-shadow @transition-fast, border-color @transition-fast;

    &:hover {
        box-shadow: @shadow-card;
    }

    // Active state - green tint
    &.active {
        border-color: fade(@color-success, 40%);
        background: linear-gradient(135deg, fade(@color-success, 3%) 0%, @color-surface 100%);
    }

    // Update available state - orange tint
    &.has-update {
        border-color: fade(@color-warning, 50%);
        background: linear-gradient(135deg, fade(@color-warning, 5%) 0%, @color-surface 100%);
    }
}

// Plugin icon (48x48)
.managed-plugin-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: @radius-md;
    overflow: hidden;
    background: @color-surface-muted;
    display: flex;
    align-items: center;
    justify-content: center;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
}

// Badge styles (Active, Inactive, Update Available)
.badge {
    font-size: @font-size-xs;
    padding: 2px 8px;
    border-radius: 999px;
    background: @color-surface-muted;
    color: @color-muted;

    &.success {
        background: fade(@color-success, 15%);
        color: @color-success;
    }

    &.warning {
        background: fade(@color-warning, 15%);
        color: @color-warning;
    }
}

// Action buttons
.managed-plugin-actions {
    display: flex;
    align-items: center;
    gap: @spacing-sm;
    flex-wrap: wrap;

    .button.warning {
        background: @color-warning;
        color: white;
        border-color: @color-warning;
    }
}
```

---

## GitHub Release Workflow

For the updater to work, your GitHub release must create a zip with:

1. **Consistent folder name** inside the zip (e.g., `wp-seo-pilot/`)
2. **Zip attached as release asset** (not just the auto-generated zipball)

**Example release.yml step:**

```yaml
- name: Create plugin zip
  run: |
    # Create folder with EXACT plugin folder name
    mkdir -p wp-seo-pilot

    # Copy production files
    cp -r assets wp-seo-pilot/
    cp -r includes wp-seo-pilot/
    cp wp-seo-pilot.php wp-seo-pilot/
    cp readme.txt wp-seo-pilot/ 2>/dev/null || true

    # Create zip (folder inside will be wp-seo-pilot/)
    zip -r wp-seo-pilot-${{ steps.version.outputs.zip_version }}.zip wp-seo-pilot

    rm -rf wp-seo-pilot
```

**Important:** The folder name inside the zip must match the `plugin_file` key in the managed plugins config (e.g., `WP-SEO-Pilot` or `wp-seo-pilot`).

---

## Checklist for Implementation

### Backend
- [ ] Create `includes/Updater/class-github-updater.php`
- [ ] Create `includes/Updater/class-plugin-installer.php`
- [ ] Create `includes/Api/class-updater-controller.php`
- [ ] Register classes in admin loader
- [ ] Add cron cleanup to deactivator
- [ ] Update managed plugins list with your plugins

### Frontend
- [ ] Create `ManagedPlugins.js` component
- [ ] Add component to your "More" or "Connected" page
- [ ] Create LESS styles for managed plugins
- [ ] Import styles in main LESS file

### GitHub
- [ ] Update release workflow to create consistent folder names
- [ ] Ensure zip is attached as release asset
- [ ] Add plugin icon and banner images to repo

### Testing
- [ ] Test fresh install of plugin
- [ ] Test update when new version available
- [ ] Test activate/deactivate
- [ ] Test "Check for Updates" button
- [ ] Verify daily cron runs

---

## Files to Copy

From WP AI Pilot, you need these files:

```
includes/
├── Updater/
│   ├── class-github-updater.php    # Core updater (modify managed plugins list)
│   └── class-plugin-installer.php  # Installation helper (copy as-is)
└── Api/
    └── class-updater-controller.php # REST endpoints (change namespace)

src/
├── components/
│   └── ManagedPlugins.js           # React component (change API path)
└── less/
    └── components/
        └── managed-plugins.less    # Styles (copy as-is or adjust colors)
```

---

## Support

- WP AI Pilot GitHub: https://github.com/jhd3197/WP-AI-Pilot
- Full implementation plan: `.planning/PLUGIN-UPDATER-PLAN.md`
