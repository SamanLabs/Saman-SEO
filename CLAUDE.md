# Saman SEO – Architecture Rules

## Admin Pages

All admin pages are rendered by the **Admin_V2 React SPA** (`includes/class-saman-seo-admin-v2.php`).
Services MUST NOT call `add_submenu_page()` or `add_menu_page()`.
Doing so creates a duplicate slug that breaks the React app on page refresh.

## Adding a New Admin Page

Every new page requires three things:

1. **React component** – `src-v2/pages/{PageName}.js`
2. **PHP view map entry** – add the slug to `$view_map` in `class-saman-seo-admin-v2.php`
3. **PHP hidden subpage** – add the slug to `$hidden_subpages` in Admin_V2's `register_menu()` method
4. **JS routing** – add entry to `viewToPage` in `src-v2/App.js` and a `case` in `renderView()`

Use `/react-page` to scaffold all of this correctly.

## Services

Service classes (`includes/class-saman-seo-service-*.php`) handle **backend only**:
settings registration, schema output, REST endpoints, WP hooks.
They must never render pages or register menu items.

## Key Constants

- `SAMAN_SEO_PATH` – plugin directory path
- `SAMAN_SEO_URL` – plugin directory URL
- `SAMAN_SEO_VERSION` – current version string

## Module Toggles

Use `\Saman\SEO\Helpers\module_enabled( 'key' )` to check whether a feature is enabled.

## Build

React app lives in `src-v2/` and builds to `build/v2/` via `@wordpress/scripts`.
