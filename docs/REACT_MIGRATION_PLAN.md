# Saman SEO - React Migration Plan (V2)

## Executive Summary

This document outlines the complete migration strategy for Saman SEO from the current jQuery/PHP-based admin interface to a modern React-based Single Page Application (SPA), following the architecture established in WP-Security-Pilot.

**Migration Approach:** Gradual transition with dual-tab system
- **Tab 1:** "Saman SEO" - Current V1 (legacy, maintained during transition)
- **Tab 2:** "Saman SEO V2" - New React-based interface

---

## Table of Contents

1. [Architecture Overview](#1-architecture-overview)
2. [Directory Structure](#2-directory-structure)
3. [Phase 1: Boilerplate Setup](#phase-1-boilerplate-setup)
4. [Phase 2: Core Infrastructure](#phase-2-core-infrastructure)
5. [Phase 3: Dashboard & Navigation](#phase-3-dashboard--navigation)
6. [Phase 4: Settings Migration](#phase-4-settings-migration)
7. [Phase 5: Search Appearance Migration](#phase-5-search-appearance-migration)
8. [Phase 6: Sitemap Settings Migration](#phase-6-sitemap-settings-migration)
9. [Phase 7: Redirects & 404 Log Migration](#phase-7-redirects--404-log-migration)
10. [Phase 8: Internal Linking Migration](#phase-8-internal-linking-migration)
11. [Phase 9: AI Assistant Migration](#phase-9-ai-assistant-migration)
12. [Phase 10: Audit & Local SEO Migration](#phase-10-audit--local-seo-migration)
13. [Phase 11: Editor Integration](#phase-11-editor-integration)
14. [Phase 12: Testing & QA](#phase-12-testing--qa)
15. [Phase 13: Final Cutover](#phase-13-final-cutover)
16. [API Reference](#api-reference)
17. [Component Mapping](#component-mapping)
18. [Settings Migration Matrix](#settings-migration-matrix)
19. [Database Considerations](#database-considerations)

---

## 1. Architecture Overview

### Current Architecture (V1)
```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress Admin                          │
├─────────────────────────────────────────────────────────────┤
│  PHP Templates (15 files)                                    │
│  ├── settings-page.php                                       │
│  ├── search-appearance.php                                   │
│  ├── sitemap-settings.php                                    │
│  └── ... (12 more)                                          │
├─────────────────────────────────────────────────────────────┤
│  jQuery + Vanilla JS (4 files, 2,272 lines)                 │
│  ├── admin.js                                               │
│  ├── editor-sidebar.js                                      │
│  └── ...                                                    │
├─────────────────────────────────────────────────────────────┤
│  AJAX Endpoints (8) + Admin Post Actions (15)               │
├─────────────────────────────────────────────────────────────┤
│  PHP Services (26 classes)                                  │
└─────────────────────────────────────────────────────────────┘
```

### Target Architecture (V2)
```
┌─────────────────────────────────────────────────────────────┐
│                     WordPress Admin                          │
├─────────────────────────────────────────────────────────────┤
│  React SPA (Single div mount point)                         │
│  ├── App.js (Router/Orchestrator)                           │
│  ├── Header.js (Navigation)                                 │
│  └── Pages/ (10 page components)                            │
├─────────────────────────────────────────────────────────────┤
│  REST API Endpoints (/wp-json/wpseopilot/v2/)              │
│  ├── /settings                                              │
│  ├── /redirects                                             │
│  ├── /internal-links                                        │
│  └── ... (more endpoints)                                   │
├─────────────────────────────────────────────────────────────┤
│  PHP Services (26 classes - unchanged)                      │
└─────────────────────────────────────────────────────────────┘
```

### Dual-Tab System During Migration
```
WordPress Admin Menu:
├── Saman SEO (V1)          ← Current legacy interface
│   ├── SEO Defaults
│   ├── Search Appearance
│   ├── Sitemap
│   ├── Redirects
│   ├── 404 Log
│   ├── Internal Linking
│   ├── Audit
│   ├── AI Tuning
│   └── Local SEO
│
└── Saman SEO V2            ← New React interface
    ├── Dashboard
    ├── SEO Defaults
    ├── Search Appearance
    ├── Sitemap
    ├── Redirects
    ├── Internal Linking
    ├── Audit
    ├── AI Assistant
    ├── Local SEO
    └── Settings
```

---

## 2. Directory Structure

### New V2 Directory Layout
```
wp-seo-pilot/
├── wp-seo-pilot.php                    # Main plugin file (updated)
├── package.json                        # Updated for React build
│
├── includes/                           # PHP Backend (mostly unchanged)
│   ├── class-wpseopilot-plugin.php     # Updated to register V2 admin
│   ├── class-wpseopilot-admin-v2.php   # NEW: V2 admin loader
│   ├── Api/                            # NEW: REST API controllers
│   │   ├── class-rest-controller.php   # Base controller
│   │   ├── class-settings-controller.php
│   │   ├── class-redirects-controller.php
│   │   ├── class-internal-links-controller.php
│   │   ├── class-sitemap-controller.php
│   │   ├── class-audit-controller.php
│   │   ├── class-ai-controller.php
│   │   └── class-local-seo-controller.php
│   └── Services/                       # Existing services (unchanged)
│       └── ... (26 service classes)
│
├── src-v2/                             # NEW: React source
│   ├── index.js                        # React entry point
│   ├── index.css                       # Global styles (V2 design)
│   ├── App.js                          # Main app component
│   ├── components/                     # Reusable components
│   │   ├── Header.js
│   │   ├── SubTabs.js
│   │   ├── Card.js
│   │   ├── Toggle.js
│   │   ├── DataTable.js
│   │   ├── GooglePreview.js
│   │   ├── CharacterCounter.js
│   │   ├── MediaPicker.js
│   │   └── ...
│   ├── hooks/                          # Custom React hooks
│   │   ├── useUrlTab.js
│   │   ├── useSettings.js
│   │   ├── useApi.js
│   │   └── ...
│   ├── pages/                          # Page components
│   │   ├── Dashboard.js
│   │   ├── SeoDefaults.js
│   │   ├── SearchAppearance.js
│   │   ├── Sitemap.js
│   │   ├── Redirects.js
│   │   ├── InternalLinking.js
│   │   ├── Audit.js
│   │   ├── AiAssistant.js
│   │   ├── LocalSeo.js
│   │   └── Settings.js
│   └── utils/                          # Utility functions
│       ├── api.js
│       ├── constants.js
│       └── helpers.js
│
├── assets/
│   ├── js/                             # Built React output
│   │   ├── admin-v2.js
│   │   ├── admin-v2.css
│   │   └── admin-v2.asset.php
│   ├── css/                            # V1 compiled CSS (keep)
│   └── less/                           # V1 LESS source (keep)
│
├── templates/                          # V1 templates (keep)
│
└── docs/
    ├── REACT_MIGRATION.md              # This file
    └── ... (other docs)
```

---

## Phase 1: Boilerplate Setup

### Step 1.1: Update package.json
```json
{
  "name": "wp-seo-pilot",
  "version": "0.2.0",
  "scripts": {
    "start": "wp-scripts start src-v2/index.js --output-path=assets/js",
    "build": "wp-scripts build src-v2/index.js --output-path=assets/js",
    "build:less": "lessc assets/less/admin.less assets/css/admin.css && lessc assets/less/plugin.less assets/css/plugin.css",
    "watch:less": "less-watch-compiler assets/less assets/css",
    "build:all": "npm run build && npm run build:less"
  },
  "dependencies": {
    "@wordpress/api-fetch": "^6.50.0",
    "react": "^18.2.0",
    "react-dom": "^18.2.0"
  },
  "devDependencies": {
    "@wordpress/scripts": "^27.6.0",
    "less": "^4.2.0",
    "less-watch-compiler": "^1.16.3"
  }
}
```

### Step 1.2: Create V2 Admin Loader Class
**File:** `includes/class-wpseopilot-admin-v2.php`

```php
<?php
namespace WPSEOPilot;

class Admin_V2 {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('admin_menu', [$this, 'register_menu'], 11);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_menu() {
        // Main V2 menu
        add_menu_page(
            __('Saman SEO V2', 'wp-seo-pilot'),
            __('Saman SEO V2', 'wp-seo-pilot'),
            'manage_options',
            'wpseopilot-v2',
            [$this, 'render_app'],
            'dashicons-chart-line',
            99
        );

        // Submenu items
        $subpages = [
            'dashboard'         => __('Dashboard', 'wp-seo-pilot'),
            'seo-defaults'      => __('SEO Defaults', 'wp-seo-pilot'),
            'search-appearance' => __('Search Appearance', 'wp-seo-pilot'),
            'sitemap'           => __('Sitemap', 'wp-seo-pilot'),
            'redirects'         => __('Redirects', 'wp-seo-pilot'),
            'internal-linking'  => __('Internal Linking', 'wp-seo-pilot'),
            'audit'             => __('Audit', 'wp-seo-pilot'),
            'ai-assistant'      => __('AI Assistant', 'wp-seo-pilot'),
            'local-seo'         => __('Local SEO', 'wp-seo-pilot'),
            'settings'          => __('Settings', 'wp-seo-pilot'),
        ];

        foreach ($subpages as $slug => $title) {
            add_submenu_page(
                'wpseopilot-v2',
                $title,
                $title,
                'manage_options',
                'wpseopilot-v2-' . $slug,
                [$this, 'render_app']
            );
        }

        // Remove duplicate first submenu
        remove_submenu_page('wpseopilot-v2', 'wpseopilot-v2');
    }

    public function render_app() {
        echo '<div id="wpseopilot-v2-root"></div>';
    }

    public function enqueue_assets($hook) {
        if (strpos($hook, 'wpseopilot-v2') === false) {
            return;
        }

        $asset_file = WPSEOPILOT_PATH . 'assets/js/admin-v2.asset.php';
        $asset = file_exists($asset_file)
            ? require $asset_file
            : ['dependencies' => ['wp-api-fetch', 'wp-element'], 'version' => WPSEOPILOT_VERSION];

        wp_enqueue_script(
            'wpseopilot-admin-v2',
            WPSEOPILOT_URL . 'assets/js/admin-v2.js',
            $asset['dependencies'],
            $asset['version'],
            true
        );

        wp_enqueue_style(
            'wpseopilot-admin-v2',
            WPSEOPILOT_URL . 'assets/js/admin-v2.css',
            [],
            $asset['version']
        );

        // Determine initial view from page slug
        $screen = get_current_screen();
        $page = str_replace('toplevel_page_wpseopilot-v2', 'dashboard', $screen->id);
        $page = str_replace('wp-seo-pilot-v2_page_wpseopilot-v2-', '', $page);

        wp_localize_script('wpseopilot-admin-v2', 'wpseopilotV2Settings', [
            'initialView' => $page,
            'restUrl'     => rest_url('wpseopilot/v2/'),
            'nonce'       => wp_create_nonce('wp_rest'),
            'adminUrl'    => admin_url(),
            'pluginUrl'   => WPSEOPILOT_URL,
            'version'     => WPSEOPILOT_VERSION,
        ]);
    }

    public function register_routes() {
        // Load and register all REST controllers
        $controllers = [
            'Settings',
            'Redirects',
            'InternalLinks',
            'Sitemap',
            'Audit',
            'Ai',
            'LocalSeo',
        ];

        foreach ($controllers as $controller) {
            $class = "\\WPSEOPilot\\Api\\{$controller}_Controller";
            if (class_exists($class)) {
                (new $class())->register_routes();
            }
        }
    }
}
```

### Step 1.3: Create React Entry Point
**File:** `src-v2/index.js`

```jsx
import { render } from '@wordpress/element';
import App from './App';
import './index.css';

const settings = window?.wpseopilotV2Settings || {};
const initialView = settings.initialView || 'dashboard';

render(
    <App initialView={initialView} settings={settings} />,
    document.getElementById('wpseopilot-v2-root')
);
```

### Step 1.4: Create Main App Component
**File:** `src-v2/App.js`

```jsx
import { useState, useEffect } from '@wordpress/element';
import Header from './components/Header';
import Dashboard from './pages/Dashboard';
import SeoDefaults from './pages/SeoDefaults';
import SearchAppearance from './pages/SearchAppearance';
import Sitemap from './pages/Sitemap';
import Redirects from './pages/Redirects';
import InternalLinking from './pages/InternalLinking';
import Audit from './pages/Audit';
import AiAssistant from './pages/AiAssistant';
import LocalSeo from './pages/LocalSeo';
import Settings from './pages/Settings';

const viewToPage = {
    'dashboard': 'wpseopilot-v2-dashboard',
    'seo-defaults': 'wpseopilot-v2-seo-defaults',
    'search-appearance': 'wpseopilot-v2-search-appearance',
    'sitemap': 'wpseopilot-v2-sitemap',
    'redirects': 'wpseopilot-v2-redirects',
    'internal-linking': 'wpseopilot-v2-internal-linking',
    'audit': 'wpseopilot-v2-audit',
    'ai-assistant': 'wpseopilot-v2-ai-assistant',
    'local-seo': 'wpseopilot-v2-local-seo',
    'settings': 'wpseopilot-v2-settings',
};

export default function App({ initialView, settings }) {
    const [view, setView] = useState(initialView);

    useEffect(() => {
        const handlePopState = () => {
            const params = new URLSearchParams(window.location.search);
            const page = params.get('page') || 'wpseopilot-v2';
            const newView = Object.entries(viewToPage)
                .find(([, p]) => p === page)?.[0] || 'dashboard';
            setView(newView);
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, []);

    const navigateTo = (newView) => {
        const page = viewToPage[newView];
        const url = `${settings.adminUrl}admin.php?page=${page}`;
        window.history.pushState({}, '', url);
        setView(newView);

        // Sync WordPress admin menu highlighting
        syncAdminMenu(page);
    };

    const syncAdminMenu = (page) => {
        document.querySelectorAll('#adminmenu .current').forEach(el => {
            el.classList.remove('current');
        });
        const menuItem = document.querySelector(`#adminmenu a[href*="${page}"]`);
        if (menuItem) {
            menuItem.parentElement.classList.add('current');
        }
    };

    const renderPage = () => {
        switch (view) {
            case 'dashboard': return <Dashboard />;
            case 'seo-defaults': return <SeoDefaults />;
            case 'search-appearance': return <SearchAppearance />;
            case 'sitemap': return <Sitemap />;
            case 'redirects': return <Redirects />;
            case 'internal-linking': return <InternalLinking />;
            case 'audit': return <Audit />;
            case 'ai-assistant': return <AiAssistant />;
            case 'local-seo': return <LocalSeo />;
            case 'settings': return <Settings />;
            default: return <Dashboard />;
        }
    };

    return (
        <div className="wpseopilot-app">
            <Header currentView={view} onNavigate={navigateTo} />
            <main className="content-area">
                {renderPage()}
            </main>
        </div>
    );
}
```

### Step 1.5: Create Header Component
**File:** `src-v2/components/Header.js`

```jsx
export default function Header({ currentView, onNavigate }) {
    const navItems = [
        { id: 'dashboard', label: 'Dashboard', icon: '📊' },
        { id: 'seo-defaults', label: 'SEO Defaults', icon: '⚙️' },
        { id: 'search-appearance', label: 'Search Appearance', icon: '🔍' },
        { id: 'sitemap', label: 'Sitemap', icon: '🗺️' },
        { id: 'redirects', label: 'Redirects', icon: '↪️' },
        { id: 'internal-linking', label: 'Internal Linking', icon: '🔗' },
        { id: 'audit', label: 'Audit', icon: '📋' },
        { id: 'ai-assistant', label: 'AI Assistant', icon: '🤖' },
        { id: 'local-seo', label: 'Local SEO', icon: '📍' },
        { id: 'settings', label: 'Settings', icon: '🔧' },
    ];

    return (
        <header className="top-bar">
            <div className="brand">
                <span className="brand-icon">🚀</span>
                <span className="brand-text">Saman SEO <span className="version-badge">V2</span></span>
            </div>
            <nav className="main-nav">
                {navItems.map(item => (
                    <button
                        key={item.id}
                        className={`nav-tab ${currentView === item.id ? 'active' : ''}`}
                        onClick={() => onNavigate(item.id)}
                        aria-current={currentView === item.id ? 'page' : undefined}
                    >
                        <span className="nav-icon">{item.icon}</span>
                        <span className="nav-label">{item.label}</span>
                    </button>
                ))}
            </nav>
            <div className="header-actions">
                <a
                    href="https://github.com/jhd3197/wp-seo-pilot"
                    target="_blank"
                    rel="noopener noreferrer"
                    className="button ghost"
                >
                    GitHub
                </a>
            </div>
        </header>
    );
}
```

### Step 1.6: Create Initial CSS Framework
**File:** `src-v2/index.css`

```css
/* ==========================================================================
   Saman SEO V2 - Global Styles
   Based on WP Security Pilot design system
   ========================================================================== */

/* --------------------------------------------------------------------------
   CSS Variables (Design Tokens)
   -------------------------------------------------------------------------- */
:root {
    /* Primary colors - SEO Pilot brand */
    --color-primary: #2563eb;
    --color-primary-strong: #1d4ed8;
    --color-primary-light: #3b82f6;

    /* Surface colors */
    --color-surface: #ffffff;
    --color-surface-muted: #f8fafc;
    --color-surface-raised: #ffffff;

    /* Border colors */
    --color-border: #e2e8f0;
    --color-border-strong: #cbd5e1;

    /* Text colors */
    --color-text: #0f172a;
    --color-text-secondary: #475569;
    --color-muted: #64748b;

    /* Semantic colors */
    --color-success: #10b981;
    --color-success-bg: #ecfdf5;
    --color-warning: #f59e0b;
    --color-warning-bg: #fffbeb;
    --color-danger: #ef4444;
    --color-danger-bg: #fef2f2;
    --color-info: #3b82f6;
    --color-info-bg: #eff6ff;

    /* Shadows */
    --shadow-sm: 0 1px 2px rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    --shadow-soft: 0 16px 40px rgba(15, 23, 42, 0.08);

    /* Typography */
    --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
    --font-mono: 'JetBrains Mono', 'Fira Code', monospace;

    /* Spacing */
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --spacing-xl: 2rem;
    --spacing-2xl: 3rem;

    /* Border radius */
    --radius-sm: 4px;
    --radius-md: 8px;
    --radius-lg: 12px;
    --radius-full: 9999px;

    /* Transitions */
    --transition-fast: 0.15s ease;
    --transition-normal: 0.2s ease;
}

/* --------------------------------------------------------------------------
   Reset & Base
   -------------------------------------------------------------------------- */
.wpseopilot-app {
    font-family: var(--font-sans);
    font-size: 14px;
    line-height: 1.5;
    color: var(--color-text);
    background: var(--color-surface-muted);
    min-height: 100vh;
    margin-left: -20px;
    margin-right: -20px;
    margin-top: -10px;
}

.wpseopilot-app *,
.wpseopilot-app *::before,
.wpseopilot-app *::after {
    box-sizing: border-box;
}

/* --------------------------------------------------------------------------
   Top Bar / Header
   -------------------------------------------------------------------------- */
.top-bar {
    display: grid;
    grid-template-columns: auto 1fr auto;
    align-items: center;
    gap: var(--spacing-lg);
    padding: var(--spacing-md) var(--spacing-xl);
    background: var(--color-surface);
    border-bottom: 1px solid var(--color-border);
    position: sticky;
    top: 32px; /* WordPress admin bar height */
    z-index: 100;
}

.brand {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
}

.brand-icon {
    font-size: 1.5rem;
}

.brand-text {
    font-size: 1.125rem;
    font-weight: 600;
    color: var(--color-text);
    letter-spacing: -0.02em;
}

.version-badge {
    display: inline-block;
    padding: 2px 8px;
    font-size: 0.75rem;
    font-weight: 500;
    background: var(--color-primary);
    color: white;
    border-radius: var(--radius-full);
    margin-left: var(--spacing-xs);
}

/* --------------------------------------------------------------------------
   Navigation
   -------------------------------------------------------------------------- */
.main-nav {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    flex-wrap: wrap;
}

.nav-tab {
    display: flex;
    align-items: center;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm) var(--spacing-md);
    background: transparent;
    border: none;
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.nav-tab:hover {
    background: var(--color-surface-muted);
    color: var(--color-text);
}

.nav-tab.active,
.nav-tab[aria-current="page"] {
    background: var(--color-primary);
    color: white;
}

.nav-icon {
    font-size: 1rem;
}

.nav-label {
    white-space: nowrap;
}

/* Responsive nav */
@media (max-width: 1400px) {
    .nav-label {
        display: none;
    }
    .nav-tab {
        padding: var(--spacing-sm);
    }
    .nav-icon {
        font-size: 1.25rem;
    }
}

/* --------------------------------------------------------------------------
   Content Area
   -------------------------------------------------------------------------- */
.content-area {
    padding: var(--spacing-xl);
    max-width: 1400px;
    margin: 0 auto;
}

/* --------------------------------------------------------------------------
   Page Structure
   -------------------------------------------------------------------------- */
.page {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(4px); }
    to { opacity: 1; transform: translateY(0); }
}

.page-header {
    margin-bottom: var(--spacing-xl);
}

.page-title {
    font-size: 1.5rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0 0 var(--spacing-sm) 0;
}

.page-description {
    color: var(--color-text-secondary);
    margin: 0;
}

/* --------------------------------------------------------------------------
   Cards
   -------------------------------------------------------------------------- */
.card {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    padding: var(--spacing-lg);
    box-shadow: var(--shadow-sm);
}

.card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: var(--spacing-md);
}

.card-title {
    font-size: 1rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.card-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: var(--spacing-lg);
}

/* --------------------------------------------------------------------------
   Forms
   -------------------------------------------------------------------------- */
.form-group {
    margin-bottom: var(--spacing-lg);
}

.form-label {
    display: block;
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: var(--spacing-sm);
}

.form-description {
    font-size: 0.875rem;
    color: var(--color-muted);
    margin-top: var(--spacing-xs);
}

.form-input,
.form-select,
.form-textarea {
    width: 100%;
    padding: var(--spacing-sm) var(--spacing-md);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-size: 0.875rem;
    color: var(--color-text);
    transition: border-color var(--transition-fast), box-shadow var(--transition-fast);
}

.form-input:focus,
.form-select:focus,
.form-textarea:focus {
    outline: none;
    border-color: var(--color-primary);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
}

.form-textarea {
    min-height: 100px;
    resize: vertical;
}

/* --------------------------------------------------------------------------
   Buttons
   -------------------------------------------------------------------------- */
.button {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: var(--spacing-sm);
    padding: var(--spacing-sm) var(--spacing-lg);
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-fast);
    text-decoration: none;
}

.button.primary {
    background: var(--color-primary);
    color: white;
    border: none;
}

.button.primary:hover {
    background: var(--color-primary-strong);
}

.button.secondary {
    background: var(--color-surface);
    color: var(--color-text);
    border: 1px solid var(--color-border);
}

.button.secondary:hover {
    background: var(--color-surface-muted);
    border-color: var(--color-border-strong);
}

.button.ghost {
    background: transparent;
    color: var(--color-text-secondary);
    border: none;
}

.button.ghost:hover {
    background: var(--color-surface-muted);
    color: var(--color-text);
}

.button.danger {
    background: var(--color-danger);
    color: white;
    border: none;
}

.button.danger:hover {
    background: #dc2626;
}

/* --------------------------------------------------------------------------
   Toggle Switch
   -------------------------------------------------------------------------- */
.toggle {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider {
    position: absolute;
    inset: 0;
    background: var(--color-border-strong);
    border-radius: var(--radius-full);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.toggle-slider::before {
    content: '';
    position: absolute;
    width: 18px;
    height: 18px;
    left: 3px;
    top: 3px;
    background: white;
    border-radius: 50%;
    transition: transform var(--transition-fast);
}

.toggle input:checked + .toggle-slider {
    background: var(--color-primary);
}

.toggle input:checked + .toggle-slider::before {
    transform: translateX(20px);
}

/* --------------------------------------------------------------------------
   Data Tables
   -------------------------------------------------------------------------- */
.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th,
.data-table td {
    padding: var(--spacing-md);
    text-align: left;
    border-bottom: 1px solid var(--color-border);
}

.data-table th {
    font-weight: 600;
    color: var(--color-text);
    background: var(--color-surface-muted);
}

.data-table tr:hover td {
    background: var(--color-surface-muted);
}

/* --------------------------------------------------------------------------
   Sub-tabs
   -------------------------------------------------------------------------- */
.sub-tabs {
    display: flex;
    gap: var(--spacing-xs);
    padding: var(--spacing-sm);
    background: var(--color-surface-muted);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.sub-tab {
    padding: var(--spacing-sm) var(--spacing-md);
    background: transparent;
    border: none;
    border-radius: var(--radius-sm);
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text-secondary);
    cursor: pointer;
    transition: all var(--transition-fast);
}

.sub-tab:hover {
    color: var(--color-text);
}

.sub-tab.active {
    background: var(--color-surface);
    color: var(--color-text);
    box-shadow: var(--shadow-sm);
}

/* --------------------------------------------------------------------------
   Panels
   -------------------------------------------------------------------------- */
.panel {
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.panel-header {
    padding: var(--spacing-md) var(--spacing-lg);
    background: var(--color-surface-muted);
    border-bottom: 1px solid var(--color-border);
}

.panel-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--color-text);
    margin: 0;
}

.panel-body {
    padding: var(--spacing-lg);
}

/* --------------------------------------------------------------------------
   Pills & Badges
   -------------------------------------------------------------------------- */
.pill {
    display: inline-flex;
    align-items: center;
    padding: 2px 10px;
    font-size: 0.75rem;
    font-weight: 500;
    border-radius: var(--radius-full);
}

.pill.success {
    background: var(--color-success-bg);
    color: var(--color-success);
}

.pill.warning {
    background: var(--color-warning-bg);
    color: var(--color-warning);
}

.pill.danger {
    background: var(--color-danger-bg);
    color: var(--color-danger);
}

.pill.info {
    background: var(--color-info-bg);
    color: var(--color-info);
}

/* --------------------------------------------------------------------------
   Two-column Layout
   -------------------------------------------------------------------------- */
.two-column-layout {
    display: grid;
    grid-template-columns: 1fr 350px;
    gap: var(--spacing-xl);
}

@media (max-width: 1200px) {
    .two-column-layout {
        grid-template-columns: 1fr;
    }
}

.main-column {
    min-width: 0;
}

.side-column {
    display: flex;
    flex-direction: column;
    gap: var(--spacing-lg);
}

/* --------------------------------------------------------------------------
   Google Preview
   -------------------------------------------------------------------------- */
.google-preview {
    padding: var(--spacing-lg);
    background: var(--color-surface);
    border: 1px solid var(--color-border);
    border-radius: var(--radius-md);
    font-family: Arial, sans-serif;
}

.google-preview-title {
    font-size: 18px;
    color: #1a0dab;
    text-decoration: none;
    line-height: 1.3;
    margin-bottom: 4px;
}

.google-preview-url {
    font-size: 14px;
    color: #006621;
    margin-bottom: 4px;
}

.google-preview-description {
    font-size: 13px;
    color: #545454;
    line-height: 1.4;
}

/* --------------------------------------------------------------------------
   Character Counter
   -------------------------------------------------------------------------- */
.char-counter {
    display: flex;
    align-items: center;
    gap: var(--spacing-sm);
    font-size: 0.75rem;
    color: var(--color-muted);
    margin-top: var(--spacing-xs);
}

.char-counter.warning {
    color: var(--color-warning);
}

.char-counter.danger {
    color: var(--color-danger);
}

.char-bar {
    flex: 1;
    height: 4px;
    background: var(--color-border);
    border-radius: var(--radius-full);
    overflow: hidden;
}

.char-bar-fill {
    height: 100%;
    background: var(--color-success);
    transition: width var(--transition-fast), background var(--transition-fast);
}

.char-bar-fill.warning {
    background: var(--color-warning);
}

.char-bar-fill.danger {
    background: var(--color-danger);
}

/* --------------------------------------------------------------------------
   Accordion
   -------------------------------------------------------------------------- */
.accordion {
    border: 1px solid var(--color-border);
    border-radius: var(--radius-lg);
    overflow: hidden;
}

.accordion-item {
    border-bottom: 1px solid var(--color-border);
}

.accordion-item:last-child {
    border-bottom: none;
}

.accordion-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: var(--spacing-md) var(--spacing-lg);
    background: var(--color-surface);
    border: none;
    font-size: 0.875rem;
    font-weight: 500;
    color: var(--color-text);
    cursor: pointer;
    transition: background var(--transition-fast);
}

.accordion-header:hover {
    background: var(--color-surface-muted);
}

.accordion-icon {
    transition: transform var(--transition-fast);
}

.accordion-item.open .accordion-icon {
    transform: rotate(180deg);
}

.accordion-content {
    display: none;
    padding: var(--spacing-lg);
    background: var(--color-surface-muted);
}

.accordion-item.open .accordion-content {
    display: block;
}

/* --------------------------------------------------------------------------
   Settings Row
   -------------------------------------------------------------------------- */
.settings-row {
    display: grid;
    grid-template-columns: 1fr auto;
    align-items: center;
    gap: var(--spacing-lg);
    padding: var(--spacing-lg);
    border-bottom: 1px solid var(--color-border);
}

.settings-row:last-child {
    border-bottom: none;
}

.settings-info {
    min-width: 0;
}

.settings-label {
    font-weight: 500;
    color: var(--color-text);
    margin-bottom: var(--spacing-xs);
}

.settings-description {
    font-size: 0.875rem;
    color: var(--color-muted);
}

/* --------------------------------------------------------------------------
   Loading States
   -------------------------------------------------------------------------- */
.loading {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: var(--spacing-2xl);
}

.spinner {
    width: 24px;
    height: 24px;
    border: 2px solid var(--color-border);
    border-top-color: var(--color-primary);
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* --------------------------------------------------------------------------
   Alerts / Notices
   -------------------------------------------------------------------------- */
.alert {
    padding: var(--spacing-md) var(--spacing-lg);
    border-radius: var(--radius-md);
    margin-bottom: var(--spacing-lg);
}

.alert.success {
    background: var(--color-success-bg);
    border: 1px solid var(--color-success);
    color: var(--color-success);
}

.alert.warning {
    background: var(--color-warning-bg);
    border: 1px solid var(--color-warning);
    color: var(--color-warning);
}

.alert.danger {
    background: var(--color-danger-bg);
    border: 1px solid var(--color-danger);
    color: var(--color-danger);
}

.alert.info {
    background: var(--color-info-bg);
    border: 1px solid var(--color-info);
    color: var(--color-info);
}

/* --------------------------------------------------------------------------
   WordPress Admin Overrides
   -------------------------------------------------------------------------- */
#wpseopilot-v2-root {
    margin: 0;
    padding: 0;
}

/* Hide WordPress update nags in our plugin pages */
.wpseopilot-app .update-nag,
.wpseopilot-app .updated,
.wpseopilot-app .notice:not(.wpseopilot-notice) {
    display: none !important;
}
```

### Step 1.7: Create Initial Dashboard Page
**File:** `src-v2/pages/Dashboard.js`

```jsx
export default function Dashboard() {
    return (
        <div className="page">
            <header className="page-header">
                <h1 className="page-title">Dashboard</h1>
                <p className="page-description">
                    Overview of your site's SEO health and performance.
                </p>
            </header>

            <div className="card-grid">
                <div className="card">
                    <div className="card-header">
                        <h2 className="card-title">SEO Score</h2>
                    </div>
                    <div className="card-body">
                        <p>Coming soon: Overall SEO health score</p>
                    </div>
                </div>

                <div className="card">
                    <div className="card-header">
                        <h2 className="card-title">Issues Found</h2>
                    </div>
                    <div className="card-body">
                        <p>Coming soon: SEO issues summary</p>
                    </div>
                </div>

                <div className="card">
                    <div className="card-header">
                        <h2 className="card-title">Redirects</h2>
                    </div>
                    <div className="card-body">
                        <p>Coming soon: Redirect statistics</p>
                    </div>
                </div>

                <div className="card">
                    <div className="card-header">
                        <h2 className="card-title">404 Errors</h2>
                    </div>
                    <div className="card-body">
                        <p>Coming soon: 404 error tracking</p>
                    </div>
                </div>
            </div>
        </div>
    );
}
```

### Step 1.8: Create Placeholder Pages
Create minimal placeholder files for all pages in `src-v2/pages/`:

- `SeoDefaults.js`
- `SearchAppearance.js`
- `Sitemap.js`
- `Redirects.js`
- `InternalLinking.js`
- `Audit.js`
- `AiAssistant.js`
- `LocalSeo.js`
- `Settings.js`

Each follows this template:
```jsx
export default function PageName() {
    return (
        <div className="page">
            <header className="page-header">
                <h1 className="page-title">Page Title</h1>
                <p className="page-description">Page description here.</p>
            </header>
            <div className="card">
                <p>Coming soon: Full feature migration</p>
            </div>
        </div>
    );
}
```

---

## Phase 2: Core Infrastructure

### Step 2.1: Create REST API Base Controller
**File:** `includes/Api/class-rest-controller.php`

```php
<?php
namespace WPSEOPilot\Api;

abstract class REST_Controller {
    protected $namespace = 'wpseopilot/v2';

    abstract public function register_routes();

    protected function permission_check() {
        return current_user_can('manage_options');
    }

    protected function success($data = null, $message = '') {
        return rest_ensure_response([
            'success' => true,
            'data'    => $data,
            'message' => $message,
        ]);
    }

    protected function error($message, $code = 'error', $status = 400) {
        return new \WP_Error($code, $message, ['status' => $status]);
    }
}
```

### Step 2.2: Create Settings REST Controller
**File:** `includes/Api/class-settings-controller.php`

```php
<?php
namespace WPSEOPilot\Api;

class Settings_Controller extends REST_Controller {
    public function register_routes() {
        register_rest_route($this->namespace, '/settings', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => 'POST',
                'callback'            => [$this, 'update_settings'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);

        register_rest_route($this->namespace, '/settings/(?P<key>[a-zA-Z0-9_-]+)', [
            [
                'methods'             => 'GET',
                'callback'            => [$this, 'get_setting'],
                'permission_callback' => [$this, 'permission_check'],
            ],
            [
                'methods'             => 'PUT',
                'callback'            => [$this, 'update_setting'],
                'permission_callback' => [$this, 'permission_check'],
            ],
        ]);
    }

    public function get_settings() {
        // Fetch all wpseopilot_ options
        global $wpdb;

        $options = $wpdb->get_results(
            "SELECT option_name, option_value
             FROM {$wpdb->options}
             WHERE option_name LIKE 'wpseopilot_%'"
        );

        $settings = [];
        foreach ($options as $opt) {
            $key = str_replace('wpseopilot_', '', $opt->option_name);
            $settings[$key] = maybe_unserialize($opt->option_value);
        }

        return $this->success($settings);
    }

    public function get_setting($request) {
        $key = $request->get_param('key');
        $value = get_option('wpseopilot_' . $key);

        return $this->success([
            'key'   => $key,
            'value' => $value,
        ]);
    }

    public function update_settings($request) {
        $settings = $request->get_json_params();

        foreach ($settings as $key => $value) {
            update_option('wpseopilot_' . $key, $value);
        }

        return $this->success(null, __('Settings saved successfully.', 'wp-seo-pilot'));
    }

    public function update_setting($request) {
        $key = $request->get_param('key');
        $value = $request->get_json_params()['value'] ?? null;

        update_option('wpseopilot_' . $key, $value);

        return $this->success([
            'key'   => $key,
            'value' => $value,
        ], __('Setting saved.', 'wp-seo-pilot'));
    }
}
```

### Step 2.3: Create useSettings Hook
**File:** `src-v2/hooks/useSettings.js`

```jsx
import { useState, useEffect, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';

export function useSettings() {
    const [settings, setSettings] = useState({});
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [error, setError] = useState(null);

    const fetchSettings = useCallback(async () => {
        try {
            setLoading(true);
            const response = await apiFetch({
                path: '/wpseopilot/v2/settings',
            });
            setSettings(response.data || {});
            setError(null);
        } catch (err) {
            setError(err.message);
        } finally {
            setLoading(false);
        }
    }, []);

    const saveSettings = useCallback(async (newSettings) => {
        try {
            setSaving(true);
            await apiFetch({
                path: '/wpseopilot/v2/settings',
                method: 'POST',
                data: newSettings,
            });
            setSettings(prev => ({ ...prev, ...newSettings }));
            setError(null);
            return true;
        } catch (err) {
            setError(err.message);
            return false;
        } finally {
            setSaving(false);
        }
    }, []);

    const saveSetting = useCallback(async (key, value) => {
        try {
            setSaving(true);
            await apiFetch({
                path: `/wpseopilot/v2/settings/${key}`,
                method: 'PUT',
                data: { value },
            });
            setSettings(prev => ({ ...prev, [key]: value }));
            setError(null);
            return true;
        } catch (err) {
            setError(err.message);
            return false;
        } finally {
            setSaving(false);
        }
    }, []);

    useEffect(() => {
        fetchSettings();
    }, [fetchSettings]);

    return {
        settings,
        loading,
        saving,
        error,
        fetchSettings,
        saveSettings,
        saveSetting,
    };
}
```

### Step 2.4: Create useUrlTab Hook
**File:** `src-v2/hooks/useUrlTab.js`

```jsx
import { useState, useEffect, useCallback } from '@wordpress/element';

export function useUrlTab(tabs, defaultTab) {
    const getTabFromUrl = () => {
        const params = new URLSearchParams(window.location.search);
        const tab = params.get('tab');
        return tabs.includes(tab) ? tab : defaultTab;
    };

    const [activeTab, setActiveTab] = useState(getTabFromUrl);

    const setTab = useCallback((newTab) => {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', newTab);
        window.history.pushState({}, '', url);
        setActiveTab(newTab);
    }, []);

    useEffect(() => {
        const handlePopState = () => {
            setActiveTab(getTabFromUrl());
        };

        window.addEventListener('popstate', handlePopState);
        return () => window.removeEventListener('popstate', handlePopState);
    }, [tabs, defaultTab]);

    return [activeTab, setTab];
}
```

---

## Phase 3: Dashboard & Navigation

### Step 3.1: Implement Dashboard with Real Data
- Fetch SEO score from audit service
- Display redirect count and hit statistics
- Show 404 error count
- Display recent activity

### Step 3.2: Create SubTabs Component
**File:** `src-v2/components/SubTabs.js`

```jsx
export default function SubTabs({ tabs, activeTab, onTabChange }) {
    return (
        <div className="sub-tabs">
            {tabs.map(tab => (
                <button
                    key={tab.id}
                    className={`sub-tab ${activeTab === tab.id ? 'active' : ''}`}
                    onClick={() => onTabChange(tab.id)}
                >
                    {tab.label}
                </button>
            ))}
        </div>
    );
}
```

---

## Phase 4: Settings Migration

### Features to Migrate:
1. **Global Robots Settings**
   - `wpseopilot_global_robots`
   - `wpseopilot_default_noindex`
   - `wpseopilot_default_nofollow`

2. **Knowledge Graph / Organization**
   - `wpseopilot_homepage_knowledge_type`
   - `wpseopilot_homepage_organization_name`
   - `wpseopilot_homepage_organization_logo`

3. **Title Settings**
   - `wpseopilot_title_separator`
   - `wpseopilot_default_title_template`

4. **AI Configuration**
   - `wpseopilot_openai_api_key`
   - `wpseopilot_ai_model`
   - `wpseopilot_ai_prompt_system`
   - `wpseopilot_ai_prompt_title`
   - `wpseopilot_ai_prompt_description`

5. **Feature Toggles**
   - All `wpseopilot_enable_*` options

### REST Endpoints Needed:
- `GET/POST /wpseopilot/v2/settings` (already created)

---

## Phase 5: Search Appearance Migration

### Sub-tabs to Implement:
1. **Global Settings** - Homepage defaults
2. **Content Types** - Per-post-type templates
3. **Taxonomies** - Per-taxonomy templates
4. **Archives** - 404, search, author, date templates
5. **Social Settings** - OG/Twitter defaults
6. **Social Cards** - Card design options

### Components Needed:
- `GooglePreview.js` - SERP preview simulation
- `TemplateEditor.js` - Template variable editor
- `Accordion.js` - Collapsible sections for post types

### REST Endpoints:
- `GET/POST /wpseopilot/v2/search-appearance`
- `GET/POST /wpseopilot/v2/post-type-defaults/{type}`
- `GET/POST /wpseopilot/v2/taxonomy-defaults/{taxonomy}`

---

## Phase 6: Sitemap Settings Migration

### Features:
- Enable/disable sitemap
- Post types selection
- Taxonomies selection
- Archive pages inclusion
- RSS sitemap
- Google News sitemap
- Additional custom pages
- LLM.txt configuration

### REST Endpoints:
- `GET/POST /wpseopilot/v2/sitemap/settings`
- `POST /wpseopilot/v2/sitemap/regenerate`

---

## Phase 7: Redirects & 404 Log Migration

### Features - Redirects:
- Create redirect form
- Redirects list with pagination
- Edit/delete redirects
- Hit count display
- Slug change detection

### Features - 404 Log:
- 404 list with pagination
- Device type filtering
- Suggest redirects
- Clear old entries

### REST Endpoints:
- `GET /wpseopilot/v2/redirects`
- `POST /wpseopilot/v2/redirects`
- `PUT /wpseopilot/v2/redirects/{id}`
- `DELETE /wpseopilot/v2/redirects/{id}`
- `GET /wpseopilot/v2/404-log`
- `DELETE /wpseopilot/v2/404-log/{id}`
- `DELETE /wpseopilot/v2/404-log` (bulk clear)

### Components:
- `DataTable.js` - Reusable data table with pagination
- `RedirectForm.js` - Create/edit redirect form

---

## Phase 8: Internal Linking Migration

### Features:
- Rules manager (CRUD)
- Categories manager
- Templates manager
- Link preview
- Settings

### REST Endpoints:
- `GET/POST /wpseopilot/v2/internal-links/rules`
- `PUT/DELETE /wpseopilot/v2/internal-links/rules/{id}`
- `GET/POST /wpseopilot/v2/internal-links/categories`
- `GET/POST /wpseopilot/v2/internal-links/templates`
- `POST /wpseopilot/v2/internal-links/preview`

---

## Phase 9: AI Assistant Migration

### Features:
- API key configuration
- Model selection
- Prompt customization
- Test generation
- Reset to defaults

### REST Endpoints:
- `GET/POST /wpseopilot/v2/ai/settings`
- `POST /wpseopilot/v2/ai/generate`
- `POST /wpseopilot/v2/ai/reset`

---

## Phase 10: Audit & Local SEO Migration

### Audit Features:
- Issue scanner
- Issue list by severity
- Per-post issues
- Quick fixes

### Local SEO Features:
- Business information
- Contact details
- Address & geo
- Opening hours
- Social profiles

### REST Endpoints:
- `GET /wpseopilot/v2/audit/scan`
- `GET /wpseopilot/v2/audit/issues`
- `POST /wpseopilot/v2/audit/resolve/{id}`
- `GET/POST /wpseopilot/v2/local-seo`

---

## Phase 11: Editor Integration

### Block Editor Sidebar:
- Keep existing `editor-sidebar.js` for V1
- Create React-based sidebar for V2 using `@wordpress/plugins`
- Register as separate panel

### Meta Box (Classic Editor):
- Keep existing meta box PHP template
- Consider migrating to React if time permits

---

## Phase 12: Testing & QA

### Unit Tests:
- REST API endpoint tests
- Component rendering tests
- Hook behavior tests

### Integration Tests:
- Settings save/load
- Redirect creation flow
- Sitemap generation

### Manual QA Checklist:
- [ ] All pages render correctly
- [ ] Settings persist across page loads
- [ ] Navigation works (back/forward buttons)
- [ ] All forms submit successfully
- [ ] Error states display properly
- [ ] Loading states appear during API calls
- [ ] Mobile responsive layout works
- [ ] WordPress admin menu stays in sync

---

## Phase 13: Final Cutover

### Migration Path:
1. **Phase A: Parallel Operation**
   - Both V1 and V2 menus available
   - Users can choose which to use
   - V1 remains the default

2. **Phase B: V2 Default**
   - V2 becomes the default menu
   - V1 available under "Legacy" submenu
   - Deprecation notice on V1 pages

3. **Phase C: V1 Removal**
   - Remove V1 menu entirely
   - Keep V1 PHP templates as fallback
   - Clean up legacy code

### Data Compatibility:
- V2 uses exact same wp_options keys
- V2 uses exact same database tables
- No data migration needed
- V1 and V2 share all data seamlessly

---

## API Reference

### REST API Namespace
`/wp-json/wpseopilot/v2/`

### Endpoints Summary

| Endpoint | Methods | Description |
|----------|---------|-------------|
| `/settings` | GET, POST | All plugin settings |
| `/settings/{key}` | GET, PUT | Single setting |
| `/search-appearance` | GET, POST | Search appearance settings |
| `/post-type-defaults/{type}` | GET, POST | Post type SEO defaults |
| `/taxonomy-defaults/{tax}` | GET, POST | Taxonomy SEO defaults |
| `/sitemap/settings` | GET, POST | Sitemap configuration |
| `/sitemap/regenerate` | POST | Force regenerate sitemap |
| `/redirects` | GET, POST | List/create redirects |
| `/redirects/{id}` | GET, PUT, DELETE | Single redirect |
| `/404-log` | GET, DELETE | 404 log entries |
| `/internal-links/rules` | GET, POST | Linking rules |
| `/internal-links/rules/{id}` | PUT, DELETE | Single rule |
| `/internal-links/preview` | POST | Preview link injection |
| `/audit/scan` | GET | Run SEO audit |
| `/audit/issues` | GET | List issues |
| `/ai/settings` | GET, POST | AI configuration |
| `/ai/generate` | POST | Generate content |
| `/local-seo` | GET, POST | Local SEO settings |

---

## Component Mapping

### V1 Template → V2 Component

| V1 Template | V2 Component | Status |
|-------------|--------------|--------|
| `settings-page.php` | `SeoDefaults.js` | Pending |
| `search-appearance.php` | `SearchAppearance.js` | Pending |
| `sitemap-settings.php` | `Sitemap.js` | Pending |
| `redirects.php` | `Redirects.js` | Pending |
| `404-log.php` | `Redirects.js` (sub-tab) | Pending |
| `internal-linking.php` | `InternalLinking.js` | Pending |
| `audit.php` | `Audit.js` | Pending |
| `ai-assistant.php` | `AiAssistant.js` | Pending |
| `local-seo.php` | `LocalSeo.js` | Pending |
| - | `Dashboard.js` | New |
| - | `Settings.js` | New |

### V1 JS → V2 Equivalent

| V1 JavaScript | V2 Implementation |
|---------------|-------------------|
| `admin.js` char counters | `CharacterCounter.js` component |
| `admin.js` preview | `GooglePreview.js` component |
| `admin.js` AI requests | `useAi.js` hook + API |
| `admin.js` tabs | `useUrlTab.js` hook |
| `admin.js` media picker | `MediaPicker.js` component |
| `editor-sidebar.js` | `EditorSidebar.js` (block editor) |
| `seo-tags.js` | Server-side only (no migration) |
| `internal-linking.js` | `InternalLinking.js` page |

---

## Settings Migration Matrix

### Core Settings (40+ options)

| V1 Option Key | V2 API Key | Notes |
|---------------|------------|-------|
| `wpseopilot_default_title_template` | `default_title_template` | Same |
| `wpseopilot_title_separator` | `title_separator` | Same |
| `wpseopilot_global_robots` | `global_robots` | Same |
| `wpseopilot_openai_api_key` | `openai_api_key` | Encrypted |
| `wpseopilot_ai_model` | `ai_model` | Same |
| `wpseopilot_enable_sitemap_enhancer` | `enable_sitemap` | Renamed |
| `wpseopilot_enable_redirect_manager` | `enable_redirects` | Renamed |
| `wpseopilot_enable_404_logging` | `enable_404_logging` | Same |
| `wpseopilot_enable_local_seo` | `enable_local_seo` | Same |
| ... | ... | ... |

---

## Database Considerations

### Existing Tables (No Changes)
- `{prefix}wpseopilot_redirects` - Used directly by V2
- `{prefix}wpseopilot_404_log` - Used directly by V2
- `{prefix}wpseopilot_internal_links` - Used directly by V2

### Post Meta (No Changes)
- `_wpseopilot_meta` - Same key, same structure

### Options (No Changes)
- All `wpseopilot_*` options remain unchanged
- V2 reads/writes to same options as V1

---

## Development Workflow

### Local Development
```bash
# Start React dev server with hot reload
npm run start

# Watch LESS files for V1 styles
npm run watch:less

# Build for production
npm run build:all
```

### File Watching
The React dev server watches `src-v2/` directory and outputs to `assets/js/`.

### Testing
```bash
# Run React component tests
npm run test

# Build and verify no errors
npm run build
```

---

## Timeline & Milestones

### Phase 1-2: Foundation (Boilerplate + Infrastructure)
- V2 admin loader
- React app shell
- REST API base
- Basic navigation

### Phase 3-4: Core Features (Dashboard + Settings)
- Dashboard with stats
- Full settings page
- AI configuration

### Phase 5-6: Content Management (Search Appearance + Sitemap)
- All template editors
- Sitemap controls
- Social card designer

### Phase 7-8: Data Features (Redirects + Internal Linking)
- Redirect manager
- 404 log viewer
- Internal linking rules

### Phase 9-10: Advanced Features (AI + Audit + Local)
- AI generation interface
- Audit dashboard
- Local SEO form

### Phase 11-12: Polish (Editor + Testing)
- Block editor sidebar
- Full QA pass
- Bug fixes

### Phase 13: Cutover
- Documentation
- User migration guide
- V1 deprecation

---

## Notes for Developer

1. **Keep V1 Working**: Never break V1 during migration
2. **Share Data**: V1 and V2 use same options/tables
3. **Incremental Migration**: One page at a time
4. **Test Thoroughly**: Each phase needs QA before next
5. **Mobile First**: Ensure responsive design from start
6. **Accessibility**: Follow WordPress a11y guidelines
7. **Performance**: Lazy load heavy components
8. **Error Handling**: Graceful degradation on API failures

---

## Author & Credits

**Saman SEO V2 Migration**
- Based on WP Security Pilot architecture
- Developed by Juan
- React + WordPress integration pattern

---

*Document Version: 1.0*
*Last Updated: January 2026*
