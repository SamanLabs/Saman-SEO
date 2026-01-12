# Plugin Updater - UI Reference

This document covers the frontend UI for the "Managed Plugins" / "Pilot Plugins" section.

---

## Visual Design

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  Pilot Plugins                                    ┌─────────────────────┐   │
│  Install and manage plugins from the Pilot        │ Check for Updates   │   │
│  ecosystem.                                       └─────────────────────┘   │
│                                                                             │
├─────────────────────────────────────────────────────────────────────────────┤
│                                                                             │
│  ┌─────────────────────────────────┐   ┌─────────────────────────────────┐  │
│  │ ┌────┐                          │   │ ┌────┐                          │  │
│  │ │icon│  WP AI Pilot   [Active]  │   │ │icon│  WP SEO Pilot [Inactive] │  │
│  │ └────┘  v0.0.1                  │   │ └────┘  v0.1.41                 │  │
│  │                                 │   │                                 │  │
│  │ Centralized AI management for   │   │ AI-powered SEO optimization    │  │
│  │ WordPress                       │   │ for WordPress                  │  │
│  │                                 │   │                                 │  │
│  │ ┌────────────┐  ┌────────┐      │   │ ┌──────────┐  ┌────────┐       │  │
│  │ │ Deactivate │  │ GitHub │      │   │ │ Activate │  │ GitHub │       │  │
│  │ └────────────┘  └────────┘      │   │ └──────────┘  └────────┘       │  │
│  └─────────────────────────────────┘   └─────────────────────────────────┘  │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## Card States

### Not Installed
```
┌─────────────────────────────────┐
│ [icon]  Plugin Name             │
│         v1.0.0 available        │
│                                 │
│ Description text...             │
│                                 │
│ [Install]  [GitHub]             │
└─────────────────────────────────┘
Border: default gray
Background: white
```

### Installed - Inactive
```
┌─────────────────────────────────┐
│ [icon]  Plugin Name   [Inactive]│
│         v1.0.0                  │
│                                 │
│ Description text...             │
│                                 │
│ [Activate]  [GitHub]            │
└─────────────────────────────────┘
Border: default gray
Background: white
Badge: gray background
```

### Installed - Active
```
┌─────────────────────────────────┐
│ [icon]  Plugin Name   [Active]  │  ← Green badge
│         v1.0.0                  │
│                                 │
│ Description text...             │
│                                 │
│ [Deactivate]  [GitHub]          │
└─────────────────────────────────┘
Border: light green tint
Background: subtle green gradient
Badge: green background
```

### Update Available
```
┌─────────────────────────────────┐
│ [icon]  Plugin Name             │
│         [Update Available]      │  ← Orange badge
│         v1.0.0 → v1.1.0         │
│                                 │
│ Description text...             │
│                                 │
│ [Update]  [Activate]  [GitHub]  │
└─────────────────────────────────┘
Border: light orange tint
Background: subtle orange gradient
Badge: orange background
Button: orange "Update" button
```

---

## Color Palette

```less
// Primary
@color-primary: #2271b1;        // WordPress blue

// Status Colors
@color-success: #2f8f5b;        // Green - active state
@color-warning: #c07c1a;        // Orange - update available
@color-danger: #c0392b;         // Red - errors

// Surfaces
@color-surface: #ffffff;        // Card background
@color-surface-muted: #f4f6fb;  // Icon background, badge default
@color-background: #f5f7fb;     // Page background

// Text
@color-text: #1d2327;           // Primary text
@color-muted: #5f6b7a;          // Secondary text, descriptions

// Border
@color-border: #d6d9e0;         // Default border
```

---

## Typography

```less
// Font Family
@font-family-base: "Space Grotesk", "Segoe UI", sans-serif;

// Sizes
@font-size-xs: 11px;    // Badges
@font-size-sm: 12px;    // Version, descriptions
@font-size-base: 14px;  // Plugin name
@font-size-md: 16px;    // Section title

// Weights
@font-weight-medium: 500;
@font-weight-semibold: 600;
```

---

## Spacing

```less
@spacing-xs: 4px;
@spacing-sm: 8px;
@spacing-md: 12px;
@spacing-lg: 16px;
@spacing-xl: 20px;
@spacing-2xl: 24px;
```

---

## Component Dimensions

| Element | Size |
|---------|------|
| Plugin icon | 48x48px |
| Icon border-radius | 12px |
| Card border-radius | 16px |
| Card padding | 20px |
| Grid gap | 16px |
| Card min-width | 340px |

---

## Full LESS Styles

```less
// ==========================================================================
// Managed Plugins Section
// ==========================================================================

.managed-plugins-section {
    margin-bottom: 24px;
    padding-bottom: 24px;
    border-bottom: 1px solid #d6d9e0;
}

.managed-plugins-header {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    margin-bottom: 16px;
    gap: 16px;

    h2 {
        font-size: 16px;
        font-weight: 600;
        color: #1d2327;
        margin: 0 0 4px 0;
    }

    .managed-plugins-desc {
        font-size: 12px;
        color: #5f6b7a;
        margin: 0;
    }
}

.managed-plugins-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(340px, 1fr));
    gap: 16px;
}

// ==========================================================================
// Plugin Card
// ==========================================================================

.managed-plugin-card {
    background: #ffffff;
    border: 1px solid #d6d9e0;
    border-radius: 16px;
    padding: 20px;
    transition: box-shadow 0.2s ease, border-color 0.2s ease;

    &:hover {
        box-shadow: 0 12px 24px rgba(20, 30, 45, 0.06);
    }

    // Active state
    &.active {
        border-color: rgba(47, 143, 91, 0.4);
        background: linear-gradient(135deg, rgba(47, 143, 91, 0.03) 0%, #ffffff 100%);
    }

    // Update available state
    &.has-update {
        border-color: rgba(192, 124, 26, 0.5);
        background: linear-gradient(135deg, rgba(192, 124, 26, 0.05) 0%, #ffffff 100%);
    }
}

.managed-plugin-header {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 12px;
}

.managed-plugin-icon {
    flex-shrink: 0;
    width: 48px;
    height: 48px;
    border-radius: 12px;
    overflow: hidden;
    background: #f4f6fb;
    display: flex;
    align-items: center;
    justify-content: center;

    img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    svg {
        width: 28px;
        height: 28px;
        color: #5f6b7a;
    }
}

.managed-plugin-icon-fallback {
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: linear-gradient(135deg, #2271b1 0%, #195d90 100%);

    svg {
        color: white;
    }
}

.managed-plugin-info {
    flex: 1;
    min-width: 0;
}

.managed-plugin-title {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;

    h3 {
        font-size: 14px;
        font-weight: 600;
        color: #1d2327;
        margin: 0;
    }
}

.managed-plugin-version {
    font-size: 12px;
    color: #5f6b7a;
    margin: 4px 0 0 0;
}

.managed-plugin-description {
    font-size: 12px;
    color: #5f6b7a;
    line-height: 1.5;
    margin: 0 0 16px 0;
}

// ==========================================================================
// Badges
// ==========================================================================

.badge {
    display: inline-block;
    font-size: 11px;
    font-weight: 500;
    padding: 2px 8px;
    border-radius: 999px;
    background: #f4f6fb;
    color: #5f6b7a;

    &.success {
        background: rgba(47, 143, 91, 0.15);
        color: #2f8f5b;
    }

    &.warning {
        background: rgba(192, 124, 26, 0.15);
        color: #c07c1a;
    }
}

// ==========================================================================
// Action Buttons
// ==========================================================================

.managed-plugin-actions {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;

    .button {
        font-size: 12px;
        padding: 8px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.2s ease;

        &.primary {
            background: #2271b1;
            color: white;
            border: 1px solid #2271b1;

            &:hover {
                background: #195d90;
            }
        }

        &.ghost {
            background: transparent;
            color: #5f6b7a;
            border: 1px solid #d6d9e0;

            &:hover {
                background: #f4f6fb;
                color: #1d2327;
            }
        }

        &.warning {
            background: #c07c1a;
            color: white;
            border: 1px solid #c07c1a;

            &:hover {
                background: #a66a15;
            }
        }

        &:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
    }
}

// ==========================================================================
// Responsive
// ==========================================================================

@media (max-width: 760px) {
    .managed-plugins-header {
        flex-direction: column;
        align-items: stretch;

        .button {
            align-self: flex-start;
        }
    }

    .managed-plugins-grid {
        grid-template-columns: 1fr;
    }

    .managed-plugin-actions {
        .button {
            flex: 1;
            min-width: 0;
            text-align: center;
        }
    }
}
```

---

## React Component (Simplified)

```jsx
const ManagedPlugins = () => {
    const [plugins, setPlugins] = useState({});
    const [loading, setLoading] = useState(true);
    const [checking, setChecking] = useState(false);
    const [actionLoading, setActionLoading] = useState({});

    // ... fetch logic

    return (
        <div className="managed-plugins-section">
            {/* Header */}
            <div className="managed-plugins-header">
                <div>
                    <h2>Pilot Plugins</h2>
                    <p className="managed-plugins-desc">
                        Install and manage plugins from the Pilot ecosystem.
                    </p>
                </div>
                <button className="button ghost" onClick={checkForUpdates} disabled={checking}>
                    {checking ? 'Checking...' : 'Check for Updates'}
                </button>
            </div>

            {/* Plugin Grid */}
            <div className="managed-plugins-grid">
                {Object.entries(plugins).map(([slug, plugin]) => (
                    <div
                        key={slug}
                        className={`managed-plugin-card ${plugin.active ? 'active' : ''} ${plugin.update_available ? 'has-update' : ''}`}
                    >
                        {/* Header with icon and title */}
                        <div className="managed-plugin-header">
                            <div className="managed-plugin-icon">
                                <img src={plugin.icon} alt={plugin.name} />
                            </div>
                            <div className="managed-plugin-info">
                                <div className="managed-plugin-title">
                                    <h3>{plugin.name}</h3>
                                    {/* Badge */}
                                    {plugin.active && <span className="badge success">Active</span>}
                                    {!plugin.installed && <span className="badge">Not Installed</span>}
                                    {plugin.update_available && <span className="badge warning">Update Available</span>}
                                </div>
                                <p className="managed-plugin-version">
                                    {plugin.installed
                                        ? (plugin.update_available
                                            ? `v${plugin.current_version} → v${plugin.remote_version}`
                                            : `v${plugin.current_version}`)
                                        : `v${plugin.remote_version} available`
                                    }
                                </p>
                            </div>
                        </div>

                        {/* Description */}
                        <p className="managed-plugin-description">{plugin.description}</p>

                        {/* Actions */}
                        <div className="managed-plugin-actions">
                            {!plugin.installed && (
                                <button className="button primary" onClick={() => handleAction(slug, 'install')}>
                                    Install
                                </button>
                            )}
                            {plugin.installed && plugin.update_available && (
                                <button className="button warning" onClick={() => handleAction(slug, 'update')}>
                                    Update
                                </button>
                            )}
                            {plugin.installed && !plugin.active && (
                                <button className="button primary" onClick={() => handleAction(slug, 'activate')}>
                                    Activate
                                </button>
                            )}
                            {plugin.installed && plugin.active && (
                                <button className="button ghost" onClick={() => handleAction(slug, 'deactivate')}>
                                    Deactivate
                                </button>
                            )}
                            <a href={plugin.github_url} target="_blank" rel="noopener noreferrer" className="button ghost">
                                GitHub
                            </a>
                        </div>
                    </div>
                ))}
            </div>
        </div>
    );
};
```

---

## Loading States

```jsx
// Button loading state
{actionLoading[slug] === 'install' ? (
    <>
        <span className="spinner is-active" style={{ margin: 0, marginRight: 8 }}></span>
        Installing...
    </>
) : (
    'Install'
)}

// Check for updates button
{checking ? (
    <>
        <span className="spinner is-active"></span>
        Checking...
    </>
) : (
    'Check for Updates'
)}
```

The WordPress spinner class `is-active` makes it visible. Style it:

```less
.spinner {
    float: none;
    margin: 0;
}
```

---

## Notice Component

```jsx
{notice && (
    <div className={`notice notice-${notice.type}`}>
        <p>{notice.message}</p>
        <button type="button" className="notice-dismiss" onClick={() => setNotice(null)}>
            <span className="screen-reader-text">Dismiss</span>
        </button>
    </div>
)}
```

Notice types: `success`, `error`, `warning`, `info`
